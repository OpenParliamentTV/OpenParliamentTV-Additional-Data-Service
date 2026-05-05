<?php

class ProviderFactory
{
    private array $factionProviderCache = [];
    private array $documentProviderCache = [];

    public function __construct(
        private array              $config,
        private string             $userAgent,
        private WikidataRestClient $restClient,
        private FactionMapper      $factionMapper
    ) {}

    public function isParliamentSupported(string $parliament): bool
    {
        return isset($this->config['parliaments'][$parliament]);
    }

    public function makeFactionProvider(string $parliament): ?MemberFactionProviderInterface
    {
        if (array_key_exists($parliament, $this->factionProviderCache)) {
            return $this->factionProviderCache[$parliament];
        }

        $providerKey = $this->config['parliaments'][$parliament]['providers']['memberFaction'] ?? null;
        $provider    = match ($providerKey) {
            'abgeordnetenwatch' => new AbgeordnetenwatchFactionProvider(
                $this->restClient,
                new AbgeordnetenwatchClient($this->userAgent),
                $this->factionMapper
            ),
            'riksdag' => new RiksdagFactionProvider(
                $this->restClient,
                new RiksdagClient($this->userAgent),
                $this->factionMapper
            ),
            default => null,
        };

        return $this->factionProviderCache[$parliament] = $provider;
    }

    public function makeDocumentProvider(string $parliament): ?OfficialDocumentProviderInterface
    {
        if (array_key_exists($parliament, $this->documentProviderCache)) {
            return $this->documentProviderCache[$parliament];
        }

        $providerKey = $this->config['parliaments'][$parliament]['providers']['officialDocument'] ?? null;
        $apiKeys     = $this->config['parliaments'][$parliament]['apiKeys'] ?? [];
        $provider    = match ($providerKey) {
            'dipBundestag' => new DipBundestagDocumentProvider(
                new DipBundestagClient(
                    !empty($apiKeys['dipBundestag']) ? $apiKeys['dipBundestag'] : ($this->config['dip-key'] ?? ''),
                    $this->userAgent
                )
            ),
            'riksdag' => new RiksdagDocumentProvider(
                new RiksdagClient($this->userAgent)
            ),
            default => null,
        };

        return $this->documentProviderCache[$parliament] = $provider;
    }

    public function getOptvApiUrl(string $parliament): string
    {
        return $this->config['parliaments'][$parliament]['optvAPI']
            ?? $this->config['optvAPI']
            ?? '';
    }
}
