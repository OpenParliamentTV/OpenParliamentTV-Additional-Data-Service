<?php

class WikimediaCommonsClient
{
    public function __construct(private string $userAgent) {}

    public function getThumbnail(string $filename, int $thumbWidth): ?array
    {
        $url = 'https://commons.wikimedia.org/w/api.php?' . http_build_query([
            'action'    => 'query',
            'titles'    => 'File:' . $filename,
            'prop'      => 'imageinfo',
            'iiprop'    => 'url|extmetadata',
            'iiurlwidth' => $thumbWidth,
            'format'    => 'json',
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
        if ($data === null) {
            return null;
        }

        $pages = $data['query']['pages'] ?? [];
        if (empty($pages)) {
            return null;
        }

        $page      = reset($pages);
        $imageInfo = $page['imageinfo'][0] ?? null;
        if ($imageInfo === null) {
            return null;
        }

        $extMetadata = $imageInfo['extmetadata'] ?? [];

        return [
            'thumbnailURI'     => $imageInfo['thumburl'] ?? null,
            'thumbnailCreator' => $extMetadata['Artist']['value'] ?? '',
            'thumbnailLicense' => $extMetadata['LicenseShortName']['value'] ?? '',
        ];
    }
}
