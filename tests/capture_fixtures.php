<?php
/**
 * Capture current API output as fixtures for regression testing.
 * Run this BEFORE implementing any changes.
 *
 * Usage: php tests/capture_fixtures.php http://localhost/index.php
 */

$baseUrl = $argv[1] ?? 'http://localhost/index.php';

$testCases = require __DIR__ . '/test_cases.php';

$fixtureDir = __DIR__ . '/fixtures';
if (!is_dir($fixtureDir)) {
    mkdir($fixtureDir, 0755, true);
}

foreach ($testCases as $params) {
    $query    = http_build_query($params);
    $url      = $baseUrl . ($query ? '?' . $query : '');
    $filename = fixtureFilename($params);

    echo "Capturing: $filename ... ";
    $response = @file_get_contents($url);
    if ($response === false) {
        echo "FAILED (could not fetch)\n";
        continue;
    }
    $decoded = json_decode($response, true);
    if ($decoded === null) {
        echo "FAILED (invalid JSON)\n";
        continue;
    }

    file_put_contents($fixtureDir . '/' . $filename, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo "OK\n";
}

function fixtureFilename(array $params): string
{
    if (empty($params)) return 'error_no_params.json';
    $parts = [];
    foreach (['type', 'wikidataID', 'dipID', 'language'] as $k) {
        if (!empty($params[$k])) $parts[] = $params[$k];
    }
    return implode('_', $parts) . '.json';
}
