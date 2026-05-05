<?php

class FactionMapper
{
    private array $cache = [];

    public function getFactionWikidataID(string $label, string $parliament = 'DE'): ?string
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
        $key = strtoupper($parliament);
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $base = __DIR__ . '/../../data/faction_to_wikidata_';
        $path = $base . $key . '.json';
        if (!is_file($path)) {
            // Deprecated lowercase fallback (one-release transition)
            $legacy = $base . strtolower($parliament) . '.json';
            if (is_file($legacy)) {
                error_log("FactionMapper: lowercase data file '{$legacy}' is deprecated; rename to uppercase ('{$path}').");
                $path = $legacy;
            }
        }

        $raw = @file_get_contents($path);
        $this->cache[$key] = $raw ? json_decode($raw, true) ?? [] : [];

        return $this->cache[$key];
    }
}
