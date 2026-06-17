<?php

class ParlamentAtClient
{
    private const LIST_URL = 'https://www.parlament.gv.at/Filter/api/json/post?jsMode=EVAL&FBEZ=WFW_002&listeId=10002&showAll=true';

    /** @var array<string,string>|null padIntern => klub shortcode */
    private ?array $memberMap = null;

    public function __construct(private string $userAgent) {}

    /**
     * Look up the parliamentary klub shortcode for a Nationalrat member.
     *
     * @param string $padIntern Austrian Parliament ID (P2280), without leading zeros.
     */
    public function getFactionShortcode(string $padIntern): ?string
    {
        $map = $this->getMemberMap();
        if ($map === null) {
            return null;
        }

        return $map[$padIntern] ?? null;
    }

    /**
     * @return array<string,string>|null
     */
    private function getMemberMap(): ?array
    {
        if ($this->memberMap !== null) {
            return $this->memberMap;
        }

        $data = $this->fetchMemberList();
        if ($data === null) {
            return null;
        }

        $map = [];
        foreach ($data['rows'] ?? [] as $row) {
            if (!is_array($row) || count($row) < 10) {
                continue;
            }

            $personPath = $row[7] ?? '';
            if (!preg_match('~/person/(\d+)~', (string)$personPath, $matches)) {
                continue;
            }

            $shortcode = trim((string)($row[9] ?? ''));
            if ($shortcode === '') {
                continue;
            }

            $map[$matches[1]] = $shortcode;
        }

        return $this->memberMap = $map;
    }

    private function fetchMemberList(): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => implode("\r\n", [
                    "User-Agent: {$this->userAgent}",
                    'Accept: application/json',
                    'Content-Type: application/json',
                ]),
                'content' => '{"M":["M"],"W":["W"]}',
                'timeout' => 10,
            ],
        ]);

        $raw = @file_get_contents(self::LIST_URL, false, $context);
        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }
}
