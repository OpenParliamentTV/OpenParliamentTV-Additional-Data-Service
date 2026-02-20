<?php

class OrganisationHandler
{
    public function __construct(
        private WikidataRestClient     $restClient,
        private WikipediaClient        $wikipediaClient,
        private WikimediaCommonsClient $commonsClient
    ) {}

    public function handle(array $input): array
    {
        $wikidataID = $input['wikidataID'] ?? '';
        $language   = $input['language'] ?? 'de';
        $thumbWidth = (int)($input['thumbWidth'] ?? 300);
        $type       = $input['type'] ?? 'organisation';

        if (!preg_match('/^Q\d+$/i', $wikidataID)) {
            return ApiResponse::error('wrong or missing parameter', 'wikidataID');
        }

        // Fetch main entity via REST API
        $item = $this->restClient->getItem($wikidataID);

        if ($item === null || empty($item['id'])) {
            return ApiResponse::error('Wikidata entity not found: ' . $wikidataID, 'wikidataID');
        }

        $data = [];
        $data['type'] = $type;
        $data['id']   = $item['id'];

        // Main label
        $labels = $item['labels'] ?? [];
        $mainLabel = $labels[$language] ?? $labels['en'] ?? (!empty($labels) ? reset($labels) : '');
        $data['label'] = $mainLabel;
        $data['labelAlternative'] = [];

        // Short name (P1813) — language-aware monolingual text
        $shortName = $this->restClient->getPropertyValue($item, WikidataProperties::SHORT_NAME, $language);
        if ($shortName !== null && $shortName !== $mainLabel) {
            $data['label'] = $shortName;
            if (!in_array($mainLabel, $data['labelAlternative'])) {
                $data['labelAlternative'][] = $mainLabel;
            }
        }

        // Aliases
        foreach (($item['aliases'][$language] ?? []) as $alias) {
            if ($alias !== $data['label'] && !in_array($alias, $data['labelAlternative'])) {
                $data['labelAlternative'][] = $alias;
            }
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

        // Website
        $data['websiteURI'] = $this->restClient->getPropertyValue($item, WikidataProperties::WEBSITE) ?? '';

        // Social media
        $data['socialMediaIDs'] = $this->buildSocialMediaIds($item);

        // FragDenStaat ID
        $fdsId = $this->restClient->getPropertyValue($item, WikidataProperties::FRAGDENSTAAT);
        if ($fdsId !== null) {
            $data['additionalInformation']['fragDenStaatID'] = $fdsId;
        }

        // Thumbnail: for organisation try logo (P154) first, then image (P18); for others use P18 only
        $imageFile = null;
        if ($type === 'organisation') {
            $logoFile  = $this->restClient->getPropertyValue($item, WikidataProperties::LOGO);
            $imageFile = $logoFile ?? $this->restClient->getPropertyValue($item, WikidataProperties::IMAGE);
        } else {
            $imageFile = $this->restClient->getPropertyValue($item, WikidataProperties::IMAGE);
        }

        if ($imageFile !== null) {
            $thumb = $this->commonsClient->getThumbnail($imageFile, $thumbWidth);
            if ($thumb !== null) {
                $data['thumbnailURI']     = $thumb['thumbnailURI'];
                $data['thumbnailCreator'] = StringHelper::cleanCreator($thumb['thumbnailCreator']);
                $data['thumbnailLicense'] = StringHelper::cleanLicense($thumb['thumbnailLicense']);
            }
        }

        // Legal document source URI
        if ($type === 'legalDocument') {
            $p7677 = $this->restClient->getPropertyValue($item, WikidataProperties::GESETZE_IM_INTERNET);
            if ($p7677 !== null) {
                $data['sourceURI'] = 'http://www.gesetze-im-internet.de/' . $p7677 . '/';
            } else {
                $p9696 = $this->restClient->getPropertyValue($item, WikidataProperties::BUZER);
                if ($p9696 !== null) {
                    $data['sourceURI'] = 'https://www.buzer.de/gesetz/' . $p9696 . '/';
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
