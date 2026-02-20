<?php

class WikidataActionClient
{
    private const BASE_URL = 'https://www.wikidata.org/w/api.php';

    public function __construct(private string $userAgent) {}

    public function getEntities(array $ids, string $language, array $props = ['labels', 'aliases']): ?array
    {
        if (empty($ids)) {
            return ['entities' => []];
        }

        $url = self::BASE_URL . '?' . http_build_query([
            'action'           => 'wbgetentities',
            'ids'              => implode('|', array_unique($ids)),
            'languages'        => $language,
            'languagefallback' => 'true',
            'props'            => implode('|', $props),
            'format'           => 'json',
        ]);

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
        if ($data === null || isset($data['error'])) {
            return null;
        }

        return $data;
    }

    public function getLabel(array $entities, string $qId, string $language): ?string
    {
        if (empty($qId) || !isset($entities['entities'][$qId])) {
            return null;
        }

        return $entities['entities'][$qId]['labels'][$language]['value'] ?? null;
    }

    public function getFirstAlias(array $entities, string $qId, string $language): ?string
    {
        if (empty($qId) || !isset($entities['entities'][$qId])) {
            return null;
        }

        return $entities['entities'][$qId]['aliases'][$language][0]['value'] ?? null;
    }
}
