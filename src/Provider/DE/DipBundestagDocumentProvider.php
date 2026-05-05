<?php

class DipBundestagDocumentProvider implements OfficialDocumentProviderInterface
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

        if (isset($dip['numFound']) && $dip['numFound'] === 0) {
            $response = ApiResponse::error('', '');
            $response['errors'] = [['info' => 'document not found', 'code' => '404']];
            return $response;
        }

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

        $data['additionalInformation']['procedureIDs'] = $dip['vorgangsbezug'] ?? null;
        $data['_sourceItem'] = $dip;

        return ApiResponse::success($data);
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
