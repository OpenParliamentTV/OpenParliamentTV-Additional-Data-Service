<?php

/**
 * Just allow requests to this service with a valid $key (which can be defined in this config)
 */
$config["accessNeedsKey"] = false;

/**
 * Object of keys. Its just needed if $config["accessNeedsKey"] = true
 * Only required parameter for a key is ["enabled"] which can be set to true or false (to disallow the access).
 *
 * $config["keys"]["API-KEY"] API-KEY should be a random String
 *
 */
$config["keys"]["API-KEY"]["enabled"] = true;
$config["keys"]["API-KEY"]["contact"] = "contact@example.com";
$config["keys"]["API-KEY"]["info"] = "Key for company XY";

/**
 * OpenParliamentTV Platform API URL.
 *
 * Used when looking up an OPTV-internal document `id` to its parliament-native
 * `documentID`. When a single ADS deployment serves multiple platform instances
 * (one per parliament), set per-parliament overrides in `$config["parliaments"]`
 * below — otherwise this value is used for all parliaments.
 */
$config["optvAPI"] = "https://de.openparliament.tv/api/v1/";

/**
 * DEPRECATED. Use $config["parliaments"]["DE"]["apiKeys"]["dipBundestag"] instead.
 * Kept for backwards compatibility for one release.
 *
 * DIP-API Key for https://dip.bundestag.de/%C3%BCber-dip/hilfe/api#content
 */
$config["dip-key"] = "";

/**
 * Parliament-specific configuration.
 *
 * Each entry keys upstream providers (member-faction, official-document) per
 * parliament shortcode (ISO 3166 Alpha-2 UPPERCASE — see SHORTCODES.md).
 *
 * `providers.memberFaction` selects the faction-resolution backend used by
 * `memberOfParliament` requests. Supported values: "abgeordnetenwatch".
 *
 * `providers.officialDocument` selects the official-document backend used by
 * `officialDocument` requests. Supported values: "dipBundestag".
 *
 * `apiKeys` holds keys for the parliament's upstream providers, keyed by the
 * provider name above. The DIP-Bundestag key falls back to the deprecated
 * top-level $config["dip-key"] if not set here.
 *
 * `optvAPI` is optional; when present it overrides the top-level optvAPI for
 * this parliament (used when each parliament runs on its own platform host).
 */
$config["parliaments"]["DE"] = [
    "providers" => [
        "memberFaction"    => "abgeordnetenwatch",
        "officialDocument" => "dipBundestag",
    ],
    "apiKeys" => [
        "dipBundestag" => "",  // overrides $config["dip-key"]
    ],
    // "optvAPI" => "https://de.openparliament.tv/api/v1/",
];

$config["parliaments"]["SE"] = [
    "providers" => [
        "memberFaction"    => "riksdag",
        "officialDocument" => "riksdag",
    ],
    "apiKeys" => [],  // Riksdag open data is keyless
    // "optvAPI" => "https://se.openparliament.tv/api/v1/",
];


/**
 * Default width for thumbnails if thumbWidth parameter was not set
 */
$config["thumb"]["defaultWidth"] = "300";

/**
 * Default language of no language was given
 */
$config["thumb"]["defaultLanguage"] = "de";


/**
 * Response cache (SQLite-backed, full response caching).
 *
 * Set enabled to false to disable caching entirely.
 * TTL is in seconds; 0 means the entry never expires.
 * Use ?nocache=1 to force a fresh fetch and refresh the cache.
 * The bypass only works when accessNeedsKey is true and a valid key is provided.
 */
$config["cache"]["enabled"]     = true;
$config["cache"]["path"]        = __DIR__ . "/cache/cache.sqlite";
$config["cache"]["bypassParam"] = "nocache";

$config["cache"]["ttl"]["person"]             = 86400;  // 24 hours
$config["cache"]["ttl"]["memberOfParliament"] = 86400;
$config["cache"]["ttl"]["organisation"]       = 86400;
$config["cache"]["ttl"]["term"]               = 86400;
$config["cache"]["ttl"]["legalDocument"]      = 86400;
$config["cache"]["ttl"]["officialDocument"]   = 0;      // never expires


?>