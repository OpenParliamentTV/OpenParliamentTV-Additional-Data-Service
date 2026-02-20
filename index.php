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
require_once __DIR__ . '/src/Api/AbgeordnetenwatchClient.php';
require_once __DIR__ . '/src/Api/DipBundestagClient.php';
require_once __DIR__ . '/src/Handler/PersonHandler.php';
require_once __DIR__ . '/src/Handler/OrganisationHandler.php';
require_once __DIR__ . '/src/Handler/OfficialDocumentHandler.php';

// Normalize input (match current behavior)
$input = $_REQUEST;
$input['thumbWidth'] = !empty($input['thumbWidth']) ? $input['thumbWidth'] : ($config['thumb']['defaultWidth'] ?? '300');
$input['language']   = strtolower(!empty($input['language']) ? $input['language'] : ($config['thumb']['defaultLanguage'] ?? 'de'));

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

    // Create shared clients
    $userAgent     = 'OpenParliamentTV-Additional-Data-Service/1.0 (https://github.com/OpenParliamentTV/OpenParliamentTV-Additional-Data-Service) PHP/' . PHP_VERSION;
    $restClient    = new WikidataRestClient($userAgent);
    $actionClient  = new WikidataActionClient($userAgent);
    $wikiClient    = new WikipediaClient($userAgent);
    $commonsClient = new WikimediaCommonsClient($userAgent);

    // Route to handler
    switch ($input['type']) {
        case 'person':
        case 'memberOfParliament':
            $awClient = new AbgeordnetenwatchClient($userAgent);
            $handler  = new PersonHandler($restClient, $actionClient, $wikiClient, $commonsClient, $awClient);
            return $handler->handle($input);

        case 'organisation':
        case 'term':
        case 'legalDocument':
            $handler = new OrganisationHandler($restClient, $wikiClient, $commonsClient);
            return $handler->handle($input);

        case 'officialDocument':
            $dipClient = new DipBundestagClient($config['dip-key'] ?? '', $userAgent);
            $handler   = new OfficialDocumentHandler($dipClient, $config['optvAPI'] ?? '');
            return $handler->handle($input);
    }

    return ApiResponse::error('unknown type', 'type');
}
