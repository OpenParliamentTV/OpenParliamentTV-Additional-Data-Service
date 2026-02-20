<?php

class WikipediaClient
{
    public function __construct(private string $userAgent) {}

    public function getSummary(string $title, string $language): ?string
    {
        $encodedTitle = str_replace('/', '%2F', $title);
        $url          = "https://{$language}.wikipedia.org/api/rest_v1/page/summary/{$encodedTitle}";

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

        return $data['extract'] ?? null;
    }
}
