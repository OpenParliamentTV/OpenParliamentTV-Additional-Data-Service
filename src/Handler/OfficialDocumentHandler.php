<?php

class OfficialDocumentHandler
{
    public function __construct(
        private DipBundestagClient $dipClient,
        private string             $optvApiUrl
    ) {}

    public function handle(array $input): array
    {
        $id        = $input['id'] ?? '';
        $dipID     = $input['dipID'] ?? '';
        $sourceURI = $input['sourceURI'] ?? '';

        if (empty($id) && empty($dipID) && empty($sourceURI)) {
            $response = ApiResponse::error('', '');
            // Use the specific plural 'fields' key to preserve existing behavior
            $response['errors'] = [
                ['info' => 'wrong or missing parameter. id, dipID or sourceURI are required', 'fields' => 'id,dipID']
            ];
            return $response;
        }

        // Resolve DIP ID
        if (!empty($id) && empty($dipID) && empty($sourceURI)) {
            $dipID = $this->resolveDipIdFromOptvId($id);
            if ($dipID === null) {
                $response = ApiResponse::error('', '');
                $response['errors'] = [
                    ['info' => 'original document id was not found on platform with internal optv id', 'fields' => 'id']
                ];
                return $response;
            }
        }

        if (!empty($dipID)) {
            $dip = $this->dipClient->getDrucksache($dipID);
        } else {
            // Resolve from sourceURI
            $dip = $this->resolveDipFromSourceURI($sourceURI);
        }

        if ($dip === null) {
            return ApiResponse::error('Failed to fetch document from DIP API', 'dipID');
        }

        // Check for error codes (use loose == to match both int and string codes)
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

        // Handle numFound=0 case (search results)
        if (isset($dip['numFound']) && $dip['numFound'] === 0) {
            $response = ApiResponse::error('', '');
            $response['errors'] = [['info' => 'document not found', 'code' => '404']];
            return $response;
        }

        // Build response
        $data = [];
        $data['id']               = $dip['id'];
        $data['label']            = ($dip['dokumentart'] ?? '') . ' ' . ($dip['dokumentnummer'] ?? '');
        $data['labelAlternative'] = [$dip['titel'] ?? ''];
        $data['type']             = 'officialDocument';
        $data['sourceURI']        = $dip['fundstelle']['pdf_url'] ?? null;

        $data['additionalInformation']['originID']       = $dip['id'];
        $data['additionalInformation']['subType']        = $dip['drucksachetyp'] ?? null;
        $data['additionalInformation']['date']           = $dip['datum'] ?? null;
        $data['additionalInformation']['electoralPeriod'] = $dip['wahlperiode'] ?? null;
        $data['additionalInformation']['creator']        = $dip['fundstelle']['urheber'] ?? null;

        if (!empty($dip['autoren_anzeige'])) {
            $data['additionalInformation']['author'] = $dip['autoren_anzeige'];
        }

        $data['additionalInformation']['procedureIDs'] = $dip['vorgangsbezug'] ?? null;
        $data['_sourceItem'] = $dip;

        return ApiResponse::success($data);
    }

    private function resolveDipIdFromOptvId(string $optvId): ?string
    {
        if (empty($this->optvApiUrl)) {
            return null;
        }

        $url = $this->optvApiUrl . '/document/' . urlencode($optvId);
        $context = stream_context_create([
            'http' => [
                'header'  => "Accept: application/json",
                'timeout' => 10,
            ]
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        $originId = $data['data']['attributes']['additionalInformation']['originID'] ?? null;

        return $originId ? (string)$originId : null;
    }

    private function resolveDipFromSourceURI(string $sourceURI): ?array
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
