<?php

class PersonHandler
{
    public function __construct(
        private WikidataRestClient       $restClient,
        private WikidataActionClient     $actionClient,
        private WikipediaClient          $wikipediaClient,
        private WikimediaCommonsClient   $commonsClient,
        private AbgeordnetenwatchClient  $awClient
    ) {}

    public function handle(array $input): array
    {
        $wikidataID = $input['wikidataID'] ?? '';
        $language   = $input['language'] ?? 'de';
        $thumbWidth = (int)($input['thumbWidth'] ?? 300);
        $type       = $input['type'] ?? 'person';

        if (!preg_match('/^Q\d+$/i', $wikidataID)) {
            return ApiResponse::error('wrong or missing parameter', 'wikidataID');
        }

        // Step 1: Fetch main entity via REST API
        $item = $this->restClient->getItem($wikidataID);

        if ($item === null) {
            return ApiResponse::error('Wikidata entity not found: ' . $wikidataID, 'wikidataID');
        }

        if (isset($item['type']) && $item['type'] === 'item' && isset($item['id'])) {
            // valid item — continue
        } elseif (empty($item['id'])) {
            return ApiResponse::error('Wikidata entity not found: ' . $wikidataID, 'wikidataID');
        }

        // Collect secondary Q-IDs
        $givenNameId  = $this->restClient->getPropertyValue($item, WikidataProperties::GIVEN_NAME);
        $familyNameId = $this->restClient->getPropertyValue($item, WikidataProperties::FAMILY_NAME);
        $degreeId     = $this->restClient->getPropertyValue($item, WikidataProperties::DEGREE);
        $partyId      = ($type === 'memberOfParliament')
            ? $this->restClient->getPropertyValue($item, WikidataProperties::PARTY)
            : null;

        $secondaryIds = array_filter([$givenNameId, $familyNameId, $degreeId, $partyId]);

        // Step 2: Batch-fetch all secondary labels in one Action API call
        $entities = $this->actionClient->getEntities(array_values($secondaryIds), $language);

        // Build data array
        $data = [];
        $data['type'] = $type;
        $data['id']   = $item['id'];

        // Label with fallback
        $labels = $item['labels'] ?? [];
        $data['label'] = $labels[$language] ?? $labels['en'] ?? (!empty($labels) ? reset($labels) : '');

        // Aliases
        $data['labelAlternative'] = [];
        foreach (($item['aliases'][$language] ?? []) as $alias) {
            $data['labelAlternative'][] = $alias;
        }

        // Names from batch
        $data['firstName']  = $entities ? $this->actionClient->getLabel($entities, (string)$givenNameId, $language) : null;
        $data['lastName']   = $entities ? $this->actionClient->getLabel($entities, (string)$familyNameId, $language) : null;
        $data['degreeFull'] = $entities ? $this->actionClient->getLabel($entities, (string)$degreeId, $language) : null;

        $degreeAlias = $entities ? $this->actionClient->getFirstAlias($entities, (string)$degreeId, $language) : null;
        if ($degreeAlias !== null) {
            $parts = explode(' ', $degreeAlias);
            $data['degree'] = reset($parts);
        } else {
            $data['degree'] = '';
        }

        // Gender (map from Q-ID, not resolved via API)
        $genderId = $this->restClient->getPropertyValue($item, WikidataProperties::GENDER);
        if ($genderId !== null) {
            $data['gender'] = WikidataProperties::GENDER_MAP[$genderId] ?? null;
        }

        // Dates
        $birthDate = $this->restClient->getPropertyValue($item, WikidataProperties::BIRTH_DATE);
        $data['birthDate'] = $birthDate;

        $deathDate = $this->restClient->getPropertyValue($item, WikidataProperties::DEATH_DATE);
        $data['deathDate'] = $deathDate;

        // Website
        $data['websiteURI'] = $this->restClient->getPropertyValue($item, WikidataProperties::WEBSITE) ?? '';

        // Abgeordnetenwatch ID
        $awId = $this->restClient->getPropertyValue($item, WikidataProperties::ABGEORDNETENWATCH);
        if ($awId !== null) {
            $data['additionalInformation']['abgeordnetenwatchID'] = $awId;
        }

        // Wikipedia
        $siteKey   = $language . 'wiki';
        $sitelinks = $item['sitelinks'] ?? [];
        if (isset($sitelinks[$siteKey])) {
            $sitelink = $sitelinks[$siteKey];
            $data['additionalInformation']['wikipedia']['title'] = $sitelink['title'] ?? null;
            $data['additionalInformation']['wikipedia']['url']   = $sitelink['url'] ?? null;

            // Extract title from URL (already underscore-encoded) to match old code behavior
            $wikiUrl   = $sitelink['url'] ?? '';
            $urlParts  = explode('wiki/', $wikiUrl);
            $wikiTitle = array_pop($urlParts);
            if ($wikiTitle) {
                $data['abstract'] = $this->wikipediaClient->getSummary($wikiTitle, $language);
            }
        }

        // Thumbnail
        $imageFile = $this->restClient->getPropertyValue($item, WikidataProperties::IMAGE);
        if ($imageFile !== null) {
            $thumb = $this->commonsClient->getThumbnail($imageFile, $thumbWidth);
            if ($thumb !== null) {
                $data['thumbnailURI']     = $thumb['thumbnailURI'];
                $data['thumbnailCreator'] = StringHelper::cleanCreator($thumb['thumbnailCreator']);
                $data['thumbnailLicense'] = StringHelper::cleanLicense($thumb['thumbnailLicense']);
            }
        }

        // Social media
        $data['socialMediaIDs'] = $this->buildSocialMediaIds($item);

        // MemberOfParliament extras
        if ($type === 'memberOfParliament') {
            $data['partyID'] = $partyId;
            $data['party']   = $entities ? $this->actionClient->getLabel($entities, (string)$partyId, $language) : null;

            if (!empty($data['additionalInformation']['abgeordnetenwatchID'])) {
                $awResponse = $this->awClient->getCandidaciesMandates($data['additionalInformation']['abgeordnetenwatchID']);
                if ($awResponse !== null && !empty($awResponse['data'])) {
                    $factionLabel = $this->awClient->getFactionLabel($awResponse);
                    if ($factionLabel !== null) {
                        $data['factionLabel'] = $factionLabel;
                        $mapper = new FactionMapper();
                        $data['factionID'] = $mapper->getFactionWikidataID($factionLabel, $input['parliament'] ?? 'de');
                    } else {
                        $data['factionID'] = null;
                    }
                } else {
                    $data['factionID'] = null;
                }
            }
        }

        return ApiResponse::success($data);
    }

    private function buildSocialMediaIds(array $item): array
    {
        $socialMediaMap = [
            'Instagram' => WikidataProperties::INSTAGRAM,
            'Facebook'  => WikidataProperties::FACEBOOK,
            'Twitter'   => WikidataProperties::TWITTER,
            'Mastodon'  => WikidataProperties::MASTODON,
            'Youtube'   => WikidataProperties::YOUTUBE,
            'Xing'      => WikidataProperties::XING,
        ];

        $result = [];
        foreach ($socialMediaMap as $label => $propertyId) {
            $values = $this->restClient->getAllPropertyValues($item, $propertyId);
            if (!empty($values)) {
                $result[] = ['label' => $label, 'id' => $values[0]];
            }
        }

        return $result;
    }
}
