<?php

class RiksdagClient
{
    private const BASE_URL = 'https://data.riksdagen.se';

    public function __construct(private string $userAgent) {}

    /**
     * Look up a Riksdag member by their `intressent_id` (matches Wikidata P1214).
     *
     * @return array|null The first matching person record, or null if not found.
     */
    public function getPerson(string $intressentId): ?array
    {
        $url = self::BASE_URL . '/personlista/?' . http_build_query([
            'iid'        => $intressentId,
            'utformat'   => 'json',
            'rdlstatus'  => 'samtliga',
        ]);

        $data = $this->fetch($url);
        if ($data === null) {
            return null;
        }

        $persons = $data['personlista']['person'] ?? [];
        if (empty($persons)) {
            return null;
        }

        // The endpoint may return either a single object or a list.
        return isset($persons['intressent_id']) ? $persons : ($persons[0] ?? null);
    }

    /**
     * Fetch a Riksdag document by its native `dok_id`.
     *
     * @return array|null The document object, or null on failure.
     */
    public function getDocument(string $dokId): ?array
    {
        $url = self::BASE_URL . '/dokument/' . urlencode($dokId) . '.json';
        $data = $this->fetch($url);
        if ($data === null) {
            return null;
        }

        return $data['dokumentstatus']['dokument'] ?? null;
    }

    private function fetch(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'header'  => "User-Agent: {$this->userAgent}\r\nAccept: application/json",
                'timeout' => 10,
            ]
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }
}
