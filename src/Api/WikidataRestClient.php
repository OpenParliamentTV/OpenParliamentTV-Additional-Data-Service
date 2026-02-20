<?php

class WikidataRestClient
{
    private const BASE_URL = 'https://www.wikidata.org/w/rest.php/wikibase/v1';

    public function __construct(private string $userAgent) {}

    public function getItem(string $itemId): ?array
    {
        $url  = self::BASE_URL . '/entities/items/' . urlencode($itemId);
        $data = $this->fetch($url);

        if ($data === null || isset($data['code'])) {
            return null;
        }

        return $data;
    }

    public function getLabel(string $itemId, string $language): ?string
    {
        $url  = self::BASE_URL . '/entities/items/' . urlencode($itemId) . '/labels/' . urlencode($language);
        $data = $this->fetch($url);

        if (is_string($data)) {
            return $data;
        }

        return null;
    }

    public function getSitelink(string $itemId, string $siteId): ?array
    {
        $url  = self::BASE_URL . '/entities/items/' . urlencode($itemId) . '/sitelinks/' . urlencode($siteId);
        $data = $this->fetch($url);

        if ($data === null || isset($data['code'])) {
            return null;
        }

        return [
            'title' => $data['title'] ?? null,
            'url'   => $data['url'] ?? null,
        ];
    }

    public function getPropertyValue(array $item, string $propertyId, string $language = 'de'): mixed
    {
        $statements = $item['statements'][$propertyId] ?? [];
        if (empty($statements)) {
            return null;
        }

        // Check for monolingual text (P1813-style) — language-aware selection
        $firstContent = $statements[0]['value']['content'] ?? null;
        if (is_array($firstContent) && isset($firstContent['text'], $firstContent['language'])) {
            return $this->getPreferredMonolingualValue($statements, $language);
        }

        // Select by rank: preferred > normal, skip deprecated
        $preferred = null;
        $normal    = null;

        foreach ($statements as $stmt) {
            $rank = $stmt['rank'] ?? 'normal';
            if ($rank === 'deprecated') {
                continue;
            }
            $value = $this->extractValue($stmt);
            if ($value === null) {
                continue;
            }
            if ($rank === 'preferred') {
                $preferred = $value;
                break;
            }
            if ($normal === null) {
                $normal = $value;
            }
        }

        return $preferred ?? $normal;
    }

    public function getAllPropertyValues(array $item, string $propertyId): array
    {
        $statements = $item['statements'][$propertyId] ?? [];
        $values     = [];

        foreach ($statements as $stmt) {
            if (($stmt['rank'] ?? 'normal') === 'deprecated') {
                continue;
            }
            $value = $this->extractValue($stmt);
            if ($value !== null) {
                $values[] = $value;
            }
        }

        return $values;
    }

    public function extractLabel(array $item, string $language): ?string
    {
        $labels = $item['labels'] ?? [];

        return $labels[$language]
            ?? $labels['en']
            ?? (!empty($labels) ? reset($labels) : null);
    }

    private function extractValue(array $statement): mixed
    {
        $value = $statement['value'] ?? null;
        if ($value === null || ($value['type'] ?? '') !== 'value') {
            return null;
        }

        $content = $value['content'];

        if (is_array($content) && isset($content['id'])) {
            return $content['id'];
        }
        if (is_array($content) && isset($content['time'])) {
            return $content['time'];
        }
        if (is_array($content) && isset($content['amount'])) {
            return $content['amount'];
        }
        if (is_array($content) && isset($content['text'])) {
            return ['text' => $content['text'], 'language' => $content['language'] ?? null];
        }

        return $content;
    }

    private function getPreferredMonolingualValue(array $statements, string $language): ?string
    {
        $fallbackEn  = null;
        $fallbackAny = null;

        foreach ($statements as $stmt) {
            if (($stmt['rank'] ?? 'normal') === 'deprecated') {
                continue;
            }
            $content = $stmt['value']['content'] ?? null;
            if (!is_array($content) || !isset($content['text'])) {
                continue;
            }

            if ($content['language'] === $language) {
                return $content['text'];
            }
            if ($content['language'] === 'en') {
                $fallbackEn = $content['text'];
            }
            $fallbackAny ??= $content['text'];
        }

        return $fallbackEn ?? $fallbackAny;
    }

    private function fetch(string $url): mixed
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

        return json_decode($raw, true);
    }
}
