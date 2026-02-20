<?php
/**
 * Compare current API output against saved fixtures.
 * Run this AFTER implementing changes to verify nothing broke.
 *
 * Usage: php tests/compare_fixtures.php http://localhost/index.php
 *
 * Exit code 0 = all pass, 1 = failures found
 */

$baseUrl = $argv[1] ?? 'http://localhost/index.php';

$testCases  = require __DIR__ . '/test_cases.php';
$fixtureDir = __DIR__ . '/fixtures';

// Fields that are intentionally allowed to differ (volatile or improved)
// Add field paths here if a known/acceptable change exists
$ignoredPaths = [
    // Example: 'data.abstract' // Wikipedia text changes over time
];

$failures = 0;

foreach ($testCases as $params) {
    $filename    = fixtureFilename($params);
    $fixturePath = $fixtureDir . '/' . $filename;

    if (!file_exists($fixturePath)) {
        echo "SKIP  $filename (no fixture)\n";
        continue;
    }

    $query    = http_build_query($params);
    $url      = $baseUrl . ($query ? '?' . $query : '');
    $response = @file_get_contents($url);
    $actual   = json_decode($response, true);
    $expected = json_decode(file_get_contents($fixturePath), true);

    $diffs = deepDiff($expected, $actual, '', $ignoredPaths);

    if (empty($diffs)) {
        echo "PASS  $filename\n";
    } else {
        echo "FAIL  $filename\n";
        foreach ($diffs as $diff) {
            echo "      $diff\n";
        }
        $failures++;
    }
}

echo "\n" . ($failures === 0 ? 'All tests passed.' : "$failures test(s) failed.") . "\n";
exit($failures > 0 ? 1 : 0);

function deepDiff(mixed $expected, mixed $actual, string $path, array $ignored): array
{
    if (in_array(ltrim($path, '.'), $ignored)) return [];

    $diffs = [];

    if (is_array($expected) && is_array($actual)) {
        foreach ($expected as $key => $value) {
            $childPath = $path . '.' . $key;
            if (!array_key_exists($key, $actual)) {
                $diffs[] = "Missing key: $childPath";
            } else {
                $diffs = array_merge($diffs, deepDiff($value, $actual[$key], $childPath, $ignored));
            }
        }
        foreach ($actual as $key => $value) {
            if (!array_key_exists($key, $expected)) {
                $diffs[] = "Extra key: {$path}.{$key}";
            }
        }
    } elseif ($expected !== $actual) {
        $exp = is_null($expected) ? 'null' : json_encode($expected);
        $act = is_null($actual)   ? 'null' : json_encode($actual);
        $diffs[] = "Value mismatch at $path: expected $exp, got $act";
    }

    return $diffs;
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
