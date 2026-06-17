<?php
// OpenParliamentTV Additional Data Service

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

// Load classes
require_once __DIR__ . '/src/Util/WikidataProperties.php';
require_once __DIR__ . '/src/Util/StringHelper.php';
require_once __DIR__ . '/src/Util/FactionMapper.php';
require_once __DIR__ . '/src/Response/ApiResponse.php';
require_once __DIR__ . '/src/Api/WikidataRestClient.php';
require_once __DIR__ . '/src/Api/WikidataActionClient.php';
require_once __DIR__ . '/src/Api/WikipediaClient.php';
require_once __DIR__ . '/src/Api/WikimediaCommonsClient.php';
require_once __DIR__ . '/src/Api/DE/AbgeordnetenwatchClient.php';
require_once __DIR__ . '/src/Api/DE/DipBundestagClient.php';
require_once __DIR__ . '/src/Api/SE/RiksdagClient.php';
require_once __DIR__ . '/src/Api/AT/ParlamentAtClient.php';
require_once __DIR__ . '/src/Provider/MemberFactionProviderInterface.php';
require_once __DIR__ . '/src/Provider/OfficialDocumentProviderInterface.php';
require_once __DIR__ . '/src/Provider/ProviderFactory.php';
require_once __DIR__ . '/src/Provider/DE/AbgeordnetenwatchFactionProvider.php';
require_once __DIR__ . '/src/Provider/DE/DipBundestagDocumentProvider.php';
require_once __DIR__ . '/src/Provider/SE/RiksdagFactionProvider.php';
require_once __DIR__ . '/src/Provider/AT/ParlamentAtFactionProvider.php';
require_once __DIR__ . '/src/Provider/SE/RiksdagDocumentProvider.php';
require_once __DIR__ . '/src/Handler/PersonHandler.php';
require_once __DIR__ . '/src/Handler/OrganisationHandler.php';
require_once __DIR__ . '/src/Handler/OfficialDocumentHandler.php';
require_once __DIR__ . '/src/Cache/ResponseCache.php';

// Normalize input (match current behavior)
$input = $_REQUEST;
$input['thumbWidth'] = !empty($input['thumbWidth']) ? $input['thumbWidth'] : ($config['thumb']['defaultWidth'] ?? '300');
$input['language']   = strtolower(!empty($input['language']) ? $input['language'] : ($config['thumb']['defaultLanguage'] ?? 'de'));
// Temporary default: callers may omit parliament (DE-only consumers); remove when all clients send parliament.
$input['parliament'] = !empty($input['parliament']) ? strtoupper($input['parliament']) : 'DE';

// Process request
$response = processRequest($input, $config);

// Output
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function processRequest(array $input, array $config): array
{
    // Validate API key if required
    if (!empty($config['accessNeedsKey'])) {
        if (empty($input['key'])) {
            return ApiResponse::error('key is needed but missing', 'key');
        }
        if (empty($config['keys'][$input['key']]) || !$config['keys'][$input['key']]['enabled']) {
            return ApiResponse::error('key is wrong or disabled', 'key');
        }
    }

    // Validate type
    $allowedTypes = ['memberOfParliament', 'person', 'organisation', 'legalDocument', 'officialDocument', 'term'];
    if (empty($input['type']) || !in_array($input['type'], $allowedTypes)) {
        return ApiResponse::error('wrong or missing parameter', 'type');
    }

    // Validate parliament (must be a configured parliament; omitted → DE, see normalization above)
    if (empty($config['parliaments'][$input['parliament']])) {
        return ApiResponse::error('unknown parliament', 'parliament');
    }

    // Cache setup
    $cache       = null;
    $cacheKey    = null;
    $bypassCache = false;
    $ttl         = 86400;

    if (!empty($config['cache']['enabled'])) {
        $cache = new ResponseCache($config['cache']['path'] ?? __DIR__ . '/cache/cache.sqlite');

        // Bypass only when key auth is enabled and the (already-validated) key is present
        $bypassParam = $config['cache']['bypassParam'] ?? 'nocache';
        if (!empty($config['accessNeedsKey']) && !empty($input[$bypassParam])) {
            $bypassCache = true;
        }

        $ttl = $config['cache']['ttl'][$input['type']] ?? 86400;

        $cacheParams = array_filter(
            array_intersect_key(
                $input,
                array_flip(['type', 'language', 'wikidataID', 'thumbWidth', 'parliament', 'documentID', 'dipID', 'sourceURI', 'id'])
            ),
            fn($v) => $v !== null && $v !== ''
        );
        $cacheKey = ResponseCache::makeKey($cacheParams);

        if (!$bypassCache) {
            $cached = $cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
    }

    // Create shared clients
    $userAgent     = 'OpenParliamentTV-Additional-Data-Service/1.0 (https://github.com/OpenParliamentTV/OpenParliamentTV-Additional-Data-Service) PHP/' . PHP_VERSION;
    $restClient    = new WikidataRestClient($userAgent);
    $actionClient  = new WikidataActionClient($userAgent);
    $wikiClient    = new WikipediaClient($userAgent);
    $commonsClient = new WikimediaCommonsClient($userAgent);

    $factory = new ProviderFactory($config, $userAgent, $restClient, new FactionMapper());

    // Route to handler
    switch ($input['type']) {
        case 'person':
        case 'memberOfParliament':
            $factionProvider = $factory->makeFactionProvider($input['parliament']);
            $handler         = new PersonHandler($restClient, $actionClient, $wikiClient, $commonsClient, $factionProvider);
            break;

        case 'organisation':
        case 'term':
        case 'legalDocument':
            $handler = new OrganisationHandler($restClient, $wikiClient, $commonsClient);
            break;

        case 'officialDocument':
            $docProvider = $factory->makeDocumentProvider($input['parliament']);
            if ($docProvider === null) {
                return ApiResponse::error('officialDocument not supported for ' . $input['parliament'], 'parliament');
            }
            $handler = new OfficialDocumentHandler($docProvider, $factory->getOptvApiUrl($input['parliament']));
            break;

        default:
            return ApiResponse::error('unknown type', 'type');
    }

    $response = $handler->handle($input);

    // Post-handler cache logic
    if ($cache !== null && $cacheKey !== null) {
        if (($response['meta']['requestStatus'] ?? '') === 'success') {
            // Store on success (also refreshes the cache when bypassCache=true)
            $cache->set($cacheKey, $response, $ttl);
        } elseif (!$bypassCache) {
            // Upstream error: serve stale cache entry if one exists
            $stale = $cache->getStale($cacheKey);
            if ($stale !== null) {
                return $stale;
            }
        }
    }

    return $response;
}
