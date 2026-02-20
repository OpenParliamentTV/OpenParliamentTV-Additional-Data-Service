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
 * OpenParliamentTV Platform API URL
 */
$config["optvAPI"] = "https://de.openparliament.tv/api/v1/";

/**
 * DIP-API Key
 * This is the API Access Key for https://dip.bundestag.de/%C3%BCber-dip/hilfe/api#content
 */
$config["dip-key"] = "";


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