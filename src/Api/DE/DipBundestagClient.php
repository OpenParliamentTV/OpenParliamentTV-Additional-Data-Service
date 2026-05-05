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
