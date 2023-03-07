<?php


header('Content-Type: application/json');

require_once(__DIR__."/config.php");
require_once(__DIR__."/additionalDataService.php");

//Set default width of thumbnail
$_REQUEST["thumbWidth"] = strtolower(!empty($_REQUEST["thumbWidth"]) ? $_REQUEST["thumbWidth"] : $config["thumb"]["defaultWidth"]);

echo json_encode(additionalDataService($_REQUEST), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


?>