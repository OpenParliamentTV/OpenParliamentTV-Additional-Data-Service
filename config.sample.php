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
 * Openparliament TV API URL
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


?>