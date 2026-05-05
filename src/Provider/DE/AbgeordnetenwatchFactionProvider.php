<?php

class AbgeordnetenwatchFactionProvider implements MemberFactionProviderInterface
{
    public function __construct(
        private WikidataRestClient      $restClient,
        private AbgeordnetenwatchClient $awClient,
        private FactionMapper           $mapper
    ) {}

    public function getMemberFaction(array $item, array $input): ?array
    {
        $awId = $this->restClient->getPropertyValue($item, WikidataProperties::ABGEORDNETENWATCH);
        if (empty($awId)) {
            return null;
        }

        $awResponse = $this->awClient->getCandidaciesMandates((string)$awId);
        if ($awResponse === null || empty($awResponse['data'])) {
            return ['factionID' => null, 'factionLabel' => null];
        }

        $factionLabel = $this->awClient->getFactionLabel($awResponse);
        if ($factionLabel === null) {
            return ['factionID' => null, 'factionLabel' => null];
        }

        $parliament = $input['parliament'] ?? 'de';
        $factionID  = $this->mapper->getFactionWikidataID($factionLabel, $parliament);

        return ['factionID' => $factionID, 'factionLabel' => $factionLabel];
    }
}
