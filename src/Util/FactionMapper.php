<?php

class FactionMapper
{
    private array $cache = [];

    public function getFactionWikidataID(string $label, string $parliament = 'de'): ?string
    {
        $factions = $this->loadMapping($parliament);
        $normalized = preg_replace('/[^a-z\d ]/i', '', $label);

        foreach ($factions as $factionLabel => $wikidataId) {
            $normalizedKey = preg_replace('/[^a-z\d ]/i', '', $factionLabel);
            if (preg_match('~' . $normalizedKey . '~i', $normalized) ||
                preg_match('~' . $normalized . '~i', $normalizedKey)) {
                return $wikidataId;
            }
        }

        return null;
    }

    private function loadMapping(string $parliament): array
    {
        if (!isset($this->cache[$parliament])) {
            $path = __DIR__ . '/../../data/faction_to_wikidata_' . $parliament . '.json';
            $raw  = @file_get_contents($path);
            $this->cache[$parliament] = $raw ? json_decode($raw, true) ?? [] : [];
        }

        return $this->cache[$parliament];
    }
}
