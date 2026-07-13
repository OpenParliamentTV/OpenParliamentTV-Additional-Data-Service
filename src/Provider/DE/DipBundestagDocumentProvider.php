<?php

class DipBundestagDocumentProvider implements OfficialDocumentProviderInterface, OfficialDocumentBatchProviderInterface
{
    public function __construct(private DipBundestagClient $dipClient) {}

    public function fetch(array $input): array
    {
        $documentID = $input['documentID'] ?? $input['dipID'] ?? '';
        $sourceURI  = $input['sourceURI'] ?? '';

        if (!empty($documentID)) {
            $dip = $this->dipClient->getDrucksache($documentID);
        } else {
            $dip = $this->resolveFromSourceURI($sourceURI);
        }

        if ($dip === null) {
            return ApiResponse::error('Failed to fetch document from DIP API', 'dipID');
        }

        $errorResponse = $this->mapDipError($dip);
        if ($errorResponse !== null) {
            return $errorResponse;
        }

        if (isset($dip['numFound']) && $dip['numFound'] === 0) {
            $response = ApiResponse::error('', '');
            $response['errors'] = [['info' => 'document not found', 'code' => '404']];
            return $response;
        }

        return ApiResponse::success($this->mapDipDocument($dip));
    }

    public function fetchBatch(array $documentNumbers): array
    {
        // Number format is provider-specific: Bundestag Drucksachen use "WP/number".
        $invalid = array_values(array_filter($documentNumbers, fn($n) => !preg_match('#^\d+/\d+$#', $n)));
        if (!empty($invalid)) {
            return ApiResponse::error('invalid document number(s): ' . implode(', ', $invalid), 'documentNumbers');
        }

        $result = $this->dipClient->searchDrucksachenByNumbers($documentNumbers);

        if ($result === null) {
            return ApiResponse::error('Failed to fetch documents from DIP API', 'documentNumbers');
        }

        $errorResponse = $this->mapDipError($result);
        if ($errorResponse !== null) {
            return $errorResponse;
        }

        $items = [];
        foreach (array_values($result['documents'] ?? []) as $dip) {
            $item = $this->mapDipDocument($dip);
            // Generic correlation field: the parliament-native number this item
            // was requested by, so callers need not inspect the raw _sourceItem.
            $item['documentNumber'] = $dip['dokumentnummer'] ?? null;
            $items[] = $item;
        }

        return ApiResponse::success($items);
    }

    private function mapDipError(array $dip): ?array
    {
        // Use loose == to match both int and string codes
        if (!empty($dip['code']) && $dip['code'] == '401') {
            $response = ApiResponse::error('', '');
            $response['errors'] = [['info' => $dip['message'] ?? 'Unauthorized', 'code' => '401']];
            return $response;
        }

        if (!empty($dip['code']) && $dip['code'] == '404') {
            $response = ApiResponse::error('document not found', '');
            $response['errors'] = [['info' => 'document not found', 'code' => '404']];
            return $response;
        }

        return null;
    }

    private function mapDipDocument(array $dip): array
    {
        $data = [];
        $data['id']               = $dip['id'];
        $data['label']            = ($dip['dokumentart'] ?? '') . ' ' . ($dip['dokumentnummer'] ?? '');
        $data['labelAlternative'] = [$dip['titel'] ?? ''];
        $data['type']             = 'officialDocument';
        $data['sourceURI']        = $dip['fundstelle']['pdf_url'] ?? null;

        $data['additionalInformation']['originID']        = $dip['id'];
        $data['additionalInformation']['subType']         = $dip['drucksachetyp'] ?? null;
        $data['additionalInformation']['date']            = $dip['datum'] ?? null;
        $data['additionalInformation']['electoralPeriod'] = $dip['wahlperiode'] ?? null;
        $data['additionalInformation']['creator']         = $dip['fundstelle']['urheber'] ?? null;

        if (!empty($dip['autoren_anzeige'])) {
            $data['additionalInformation']['author'] = $dip['autoren_anzeige'];
        }

        [$procedureIDs, $procedureIDsCount] = $this->resolveProcedureIDs($dip);
        $data['additionalInformation']['procedureIDs']      = $procedureIDs;
        $data['additionalInformation']['procedureIDsCount'] = $procedureIDsCount;

        $data['_sourceItem'] = $dip;

        return $data;
    }

    /**
     * DIP truncates the embedded vorgangsbezug list (currently at 4 entries) in
     * both list and detail responses; vorgangsbezug_anzahl carries the true
     * count. When truncated, the complete list is fetched via /vorgang. If that
     * fetch fails, the truncated list is kept — the stored procedureIDsCount
     * still exceeds the list length, so consumers can detect and retry later.
     *
     * @return array{0: ?array, 1: int}
     */
    private function resolveProcedureIDs(array $dip): array
    {
        $embedded = $dip['vorgangsbezug'] ?? null;
        $count    = (int)($dip['vorgangsbezug_anzahl'] ?? (is_array($embedded) ? count($embedded) : 0));

        if (is_array($embedded) && $count > count($embedded) && !empty($dip['id'])) {
            $vorgaenge = $this->dipClient->getVorgaengeForDrucksache((string)$dip['id']);
            if ($vorgaenge !== null && count($vorgaenge) >= count($embedded)) {
                $embedded = array_map(fn($v) => [
                    'id'          => $v['id'] ?? null,
                    'titel'       => $v['titel'] ?? null,
                    'vorgangstyp' => $v['vorgangstyp'] ?? null,
                ], $vorgaenge);
                $count = count($embedded);
            }
        }

        return [$embedded, $count];
    }

    private function resolveFromSourceURI(string $sourceURI): ?array
    {
        $parts    = explode('/', $sourceURI);
        $filename = array_pop($parts);
        $filename = preg_replace('/\.pdf$/i', '', $filename);

        $part1 = substr($filename, 0, 2);
        $part2 = ltrim(substr($filename, 2), '0');
        $documentNumber = $part1 . '/' . $part2;

        return $this->dipClient->searchDrucksacheByNumber($documentNumber);
    }
}
