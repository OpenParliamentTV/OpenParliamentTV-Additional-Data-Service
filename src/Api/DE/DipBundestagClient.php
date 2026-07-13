<?php

class DipBundestagClient
{
    private const BASE_URL = 'https://search.dip.bundestag.de/api/v1';

    public function __construct(
        private string $apiKey,
        private string $userAgent
    ) {}

    public function getDrucksache(string $dipId): ?array
    {
        $url = self::BASE_URL . '/drucksache/' . urlencode($dipId) . '?' . http_build_query([
            'format' => 'json',
            'apikey' => $this->apiKey,
        ]);

        return $this->fetch($url);
    }

    public function searchDrucksacheByNumber(string $documentNumber): ?array
    {
        $url = self::BASE_URL . '/drucksache?' . http_build_query([
            'f.dokumentnummer' => $documentNumber,
            'f.zuordnung'      => 'BT',
            'format'           => 'json',
            'apikey'           => $this->apiKey,
        ]);

        $data = $this->fetch($url);
        if ($data === null) {
            return null;
        }

        $doc = $data['documents'][0] ?? null;
        if ($doc === null) {
            return null;
        }

        $doc['numFound'] = $data['numFound'] ?? 0;
        return $doc;
    }

    /**
     * Fetch multiple Drucksachen in one query via repeated f.dokumentnummer (OR search).
     * Returns ['documents' => [...], 'numFound' => int] or null on transport failure.
     * DIP error envelopes ({code, message}) are passed through for the caller to map.
     */
    public function searchDrucksachenByNumbers(array $documentNumbers): ?array
    {
        // http_build_query would emit f.dokumentnummer[0]=..., which DIP rejects,
        // so the repeated parameter is assembled manually.
        $query = implode('&', array_merge(
            [
                'f.zuordnung=BT',
                'format=json',
                'apikey=' . urlencode($this->apiKey),
            ],
            array_map(fn($n) => 'f.dokumentnummer=' . urlencode($n), $documentNumbers)
        ));

        return $this->fetchAllPages(self::BASE_URL . '/drucksache?' . $query);
    }

    /**
     * Fetch all Vorgänge linked to a Drucksache (f.drucksache takes a single DIP
     * document id). Used to complete procedure lists beyond DIP's embedded
     * vorgangsbezug cap. Returns the Vorgang array or null on failure.
     */
    public function getVorgaengeForDrucksache(string $dipDocId): ?array
    {
        $url = self::BASE_URL . '/vorgang?' . http_build_query([
            'f.drucksache' => $dipDocId,
            'format'       => 'json',
            'apikey'       => $this->apiKey,
        ]);

        $data = $this->fetchAllPages($url);
        if ($data === null || !empty($data['code'])) {
            return null;
        }

        return $data['documents'] ?? [];
    }

    /**
     * Fetch a list URL and follow DIP cursor pagination until all entities
     * (max 100 per response) are collected. Returns the merged response or null.
     */
    private function fetchAllPages(string $baseUrl): ?array
    {
        $data = $this->fetch($baseUrl);
        if ($data === null) {
            return null;
        }
        if (!empty($data['code'])) {
            return $data; // error envelope, caller maps it
        }

        $documents = $data['documents'] ?? [];
        $numFound  = $data['numFound'] ?? count($documents);
        $cursor    = $data['cursor'] ?? null;

        while (count($documents) < $numFound && !empty($cursor)) {
            $page = $this->fetch($baseUrl . '&cursor=' . urlencode($cursor));
            if ($page === null || !empty($page['code']) || empty($page['documents'])) {
                break;
            }
            // DIP signals the last page by returning the same cursor again
            if (($page['cursor'] ?? null) === $cursor) {
                $documents = array_merge($documents, $page['documents']);
                break;
            }
            $documents = array_merge($documents, $page['documents']);
            $cursor    = $page['cursor'] ?? null;
        }

        return ['documents' => $documents, 'numFound' => $numFound];
    }

    private function fetch(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'header'        => "User-Agent: {$this->userAgent}\r\nAccept: application/json",
                'timeout'       => 10,
                'ignore_errors' => true,  // read body even on 4xx/5xx responses
            ]
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        if ($data === null) {
            return null;
        }

        return $data;
    }
}
