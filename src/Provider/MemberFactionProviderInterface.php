<?php

interface MemberFactionProviderInterface
{
    /**
     * Resolve the parliamentary faction for a given Wikidata person item.
     *
     * Return values:
     *   null
     *     The parliament-specific lookup did not apply (e.g. the person has no
     *     identifier for this parliament's source). The handler should not set
     *     factionID/factionLabel on the response.
     *
     *   ['factionID' => ?string, 'factionLabel' => ?string]
     *     The lookup ran. Either field may be null. The handler always sets
     *     factionID; factionLabel is only set when non-null.
     *
     * @param array<string,mixed> $item  Wikidata item as returned by WikidataRestClient::getItem().
     * @param array<string,mixed> $input Original request input (parliament, language, etc.).
     * @return array{factionID: ?string, factionLabel: ?string}|null
     */
    public function getMemberFaction(array $item, array $input): ?array;
}
