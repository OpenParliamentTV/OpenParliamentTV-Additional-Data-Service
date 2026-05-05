<?php

class RiksdagDocumentProvider implements OfficialDocumentProviderInterface
{
    public function __construct(private RiksdagClient $riksdagClient) {}

    public function fetch(array $input): array
    {
        $documentID = $input['documentID'] ?? '';
        // SE callers should not use the deprecated DE-only `dipID` alias, but
        // accept it as a courtesy if `documentID` is missing.
        if ($documentID === '') {
            $documentID = $input['dipID'] ?? '';
        }
        // Riksdag has no equivalent of DE's sourceURI-based fallback; if no
        // documentID was provided (and the handler couldn't resolve one from
        // an OPTV id), there's nothing to look up.
        if ($documentID === '') {
            return ApiResponse::error('documentID is required for SE officialDocument lookup', 'documentID');
        }

        $doc = $this->riksdagClient->getDocument($documentID);
        if ($doc === null) {
            $response = ApiResponse::error('document not found', '');
            $response['errors'] = [['info' => 'document not found', 'code' => '404']];
            return $response;
        }

        $data = [];
        $data['id']               = $doc['dok_id'] ?? $documentID;
        $data['label']            = $this->buildLabel($doc);
        $data['labelAlternative'] = [$doc['titel'] ?? ''];
        $data['type']             = 'officialDocument';
        $data['sourceURI']        = $this->normaliseUrl($doc['dokument_url_html'] ?? null);

        $data['additionalInformation']['originID']        = $doc['dok_id'] ?? $documentID;
        $data['additionalInformation']['subType']         = $doc['subtyp'] ?? null;
        $data['additionalInformation']['date']            = $this->extractDate($doc['datum'] ?? null);
        $data['additionalInformation']['electoralPeriod'] = $doc['rm'] ?? null;

        if (!empty($doc['organ'])) {
            $data['additionalInformation']['creator'] = $doc['organ'];
        }

        $data['_sourceItem'] = $doc;

        return ApiResponse::success($data);
    }

    private function buildLabel(array $doc): string
    {
        $typ        = $doc['typ'] ?? '';
        $rm         = $doc['rm'] ?? '';
        $beteckning = $doc['beteckning'] ?? '';

        if ($typ !== '' && $rm !== '' && $beteckning !== '') {
            return $typ . ' ' . $rm . ':' . $beteckning;
        }
        if (!empty($doc['dokumentnamn'])) {
            return $doc['dokumentnamn'];
        }

        return $doc['dok_id'] ?? '';
    }

    private function normaliseUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }
        // Some Riksdag endpoints return protocol-relative URLs ("//data.riksdagen.se/...").
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        return $url;
    }

    private function extractDate(?string $datum): ?string
    {
        if ($datum === null || $datum === '') {
            return null;
        }
        // Riksdag returns "YYYY-MM-DD HH:MM:SS"; keep just the date part to
        // match the DE shape.
        return substr($datum, 0, 10);
    }
}
