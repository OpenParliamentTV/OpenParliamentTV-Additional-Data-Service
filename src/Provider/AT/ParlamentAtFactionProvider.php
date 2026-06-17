<?php

class ParlamentAtFactionProvider implements MemberFactionProviderInterface
{
    /**
     * Klub shortcode → full official klub name (parlament.gv.at FR dimension).
     */
    private const KLUB_NAMES = [
        'GRÜNE' => 'Der Grüne Klub im Parlament',
        'SPÖ'   => 'Die Sozialdemokratische Parlamentsfraktion',
        'FPÖ'   => 'Freiheitlicher Parlamentsklub',
        'OK'    => 'ohne Klubzugehörigkeit',
        'NEOS'  => 'NEOS Parlamentsklub',
        'ÖVP'   => 'Parlamentsklub der Österreichischen Volkspartei',
    ];

    /** Klub shortcode → Wikidata Q-ID (authoritative faction entities). */
    private const KLUB_WIKIDATA_IDS = [
        'SPÖ'   => 'Q37994784',
        'FPÖ'   => 'Q37994791',
        'GRÜNE' => 'Q37994795',
        'NEOS'  => 'Q37994797',
        'ÖVP'   => 'Q59617931',
        'TS'    => 'Q37995025',
        'BZÖ'   => 'Q37995029',
        'PILZ'  => 'Q46945398',
        'OK'    => 'Q4316268',
    ];

    public function __construct(
        private WikidataRestClient $restClient,
        private ParlamentAtClient  $parlamentClient,
        private FactionMapper      $mapper
    ) {}

    public function getMemberFaction(array $item, array $input): ?array
    {
        $padId = $this->restClient->getPropertyValue($item, WikidataProperties::AUSTRIAN_PARLIAMENT);
        if (empty($padId)) {
            return null;
        }

        $padIntern = ltrim((string)$padId, '0');
        if ($padIntern === '') {
            return null;
        }

        $shortcode = $this->parlamentClient->getFactionShortcode($padIntern);
        if ($shortcode === null) {
            return ['factionID' => null, 'factionLabel' => null];
        }

        $label      = self::KLUB_NAMES[$shortcode] ?? $shortcode;
        $parliament = $input['parliament'] ?? 'AT';
        $factionID  = self::KLUB_WIKIDATA_IDS[$shortcode]
            ?? $this->mapper->getFactionWikidataID($label, $parliament);

        return ['factionID' => $factionID, 'factionLabel' => $label];
    }
}
