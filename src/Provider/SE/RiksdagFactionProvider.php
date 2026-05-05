<?php

class RiksdagFactionProvider implements MemberFactionProviderInterface
{
    /**
     * Riksdag `parti` shortcode → full Swedish party name.
     *
     * The Riksdag personlista API returns a one- or two-letter shortcode; the
     * platform expects a human-readable label. The shortcodes are also keys
     * in `data/faction_to_wikidata_SE.json` so FactionMapper resolves either
     * form to the same Wikidata Q-ID.
     */
    private const PARTI_NAMES = [
        'S'  => 'Socialdemokraterna',
        'M'  => 'Moderaterna',
        'SD' => 'Sverigedemokraterna',
        'V'  => 'Vänsterpartiet',
        'C'  => 'Centerpartiet',
        'KD' => 'Kristdemokraterna',
        'L'  => 'Liberalerna',
        'MP' => 'Miljöpartiet',
    ];

    public function __construct(
        private WikidataRestClient $restClient,
        private RiksdagClient      $riksdagClient,
        private FactionMapper      $mapper
    ) {}

    public function getMemberFaction(array $item, array $input): ?array
    {
        $iid = $this->restClient->getPropertyValue($item, WikidataProperties::RIKSDAGEN);
        if (empty($iid)) {
            return null;
        }

        $person = $this->riksdagClient->getPerson((string)$iid);
        if ($person === null) {
            return ['factionID' => null, 'factionLabel' => null];
        }

        $parti = $person['parti'] ?? null;
        if (empty($parti)) {
            return ['factionID' => null, 'factionLabel' => null];
        }

        $label      = self::PARTI_NAMES[$parti] ?? $parti;
        $parliament = $input['parliament'] ?? 'SE';
        $factionID  = $this->mapper->getFactionWikidataID($label, $parliament);

        return ['factionID' => $factionID, 'factionLabel' => $label];
    }
}
