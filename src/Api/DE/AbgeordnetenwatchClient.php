<?php

class AbgeordnetenwatchClient
{
    private const BASE_URL = 'https://www.abgeordnetenwatch.de/api/v2';

    public function __construct(private string $userAgent) {}

    public function getCandidaciesMandates(string $politicianId): ?array
    {
        $url = self::BASE_URL . '/candidacies-mandates?' . http_build_query([
            'politician[entity.politician.id]' => $politicianId,
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

        return json_decode($raw, true);
    }

    public function getFactionLabel(array $response): ?string
    {
        return $response['data'][0]['fraction_membership'][0]['label'] ?? null;
    }
}
