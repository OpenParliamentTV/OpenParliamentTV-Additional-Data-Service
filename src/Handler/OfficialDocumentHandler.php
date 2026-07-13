<?php

class OfficialDocumentHandler
{
    public function __construct(
        private OfficialDocumentProviderInterface $docProvider,
        private string                            $optvApiUrl
    ) {}

    private const BATCH_MAX_NUMBERS = 100;

    public function handle(array $input): array
    {
        if (!empty($input['documentNumbers'])) {
            return $this->handleBatch($input['documentNumbers']);
        }

        $id         = $input['id'] ?? '';
        $documentID = $input['documentID'] ?? $input['dipID'] ?? '';
        $sourceURI  = $input['sourceURI'] ?? '';

        if (empty($input['documentID']) && !empty($input['dipID'])) {
            error_log("OfficialDocumentHandler: 'dipID' parameter is deprecated; use 'documentID' instead.");
        }

        if (empty($id) && empty($documentID) && empty($sourceURI)) {
            $response = ApiResponse::error('', '');
            // Use the specific plural 'fields' key to preserve existing behavior
            $response['errors'] = [
                ['info' => 'wrong or missing parameter. id, dipID or sourceURI are required', 'fields' => 'id,dipID']
            ];
            return $response;
        }

        // Resolve OPTV id → parliament-native documentID
        if (!empty($id) && empty($documentID) && empty($sourceURI)) {
            $documentID = $this->resolveDocumentIdFromOptvId($id);
            if ($documentID === null) {
                $response = ApiResponse::error('', '');
                $response['errors'] = [
                    ['info' => 'original document id was not found on platform with internal optv id', 'fields' => 'id']
                ];
                return $response;
            }
            $input['documentID'] = $documentID;
        }

        return $this->docProvider->fetch($input);
    }

    /**
     * Batch lookup by comma-separated parliament-native document numbers.
     * Always fetches fresh from the upstream source; the caller (index.php)
     * writes the per-item results back into the single-request cache.
     * Number format validation is provider-specific and happens in fetchBatch().
     */
    private function handleBatch(string $documentNumbers): array
    {
        $numbers = array_values(array_filter(array_map('trim', explode(',', $documentNumbers)), fn($n) => $n !== ''));

        if (empty($numbers)) {
            return ApiResponse::error('documentNumbers must contain at least one document number', 'documentNumbers');
        }

        if (count($numbers) > self::BATCH_MAX_NUMBERS) {
            return ApiResponse::error('documentNumbers exceeds maximum of ' . self::BATCH_MAX_NUMBERS . ' numbers', 'documentNumbers');
        }

        if (!($this->docProvider instanceof OfficialDocumentBatchProviderInterface)) {
            return ApiResponse::error('batch lookup not supported for this parliament', 'documentNumbers');
        }

        return $this->docProvider->fetchBatch($numbers);
    }

    private function resolveDocumentIdFromOptvId(string $optvId): ?string
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
}
