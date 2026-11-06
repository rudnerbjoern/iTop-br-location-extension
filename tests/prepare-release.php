<?php

declare(strict_types=1);

require_once __DIR__ . '/../scripts/prepare-release.php';

$manifest = file_get_contents(__DIR__ . '/../extension.xml');
$module = file_get_contents(__DIR__ . '/../br-location-extension/module.br-location-extension.php');
$bridge = file_get_contents(__DIR__ . '/../br-location-extension-bridge-for-molkobain-datacenter-view/module.br-location-extension-bridge-for-molkobain-datacenter-view.php');
$xml = simplexml_load_string($manifest);
$current = trim((string) $xml->version);
$parts = explode('.', $current);
$next = $parts[0] . '.' . $parts[1] . '.' . ((int) $parts[2] + 1);

$tests = [
    'prepare matching release metadata without changing other content' => static function () use ($manifest, $module, $bridge, $current, $next): void {
        [$updatedManifest, $updatedModule, $updatedBridge] = prepareReleaseContents($manifest, $module, $bridge, $next, '2028-02-29');
        $xml = simplexml_load_string($updatedManifest);
        if ((string) $xml->version !== $next || (string) $xml->release_date !== '2028-02-29'
            || $updatedModule !== str_replace("'br-location-extension/$current'", "'br-location-extension/$next'", $module)
            || $updatedBridge !== str_replace(
                ["'br-location-extension-bridge-for-datacenterview/$current'", "br-location-extension/$current"],
                ["'br-location-extension-bridge-for-datacenterview/$next'", "br-location-extension/$next"],
                $bridge
            )
            || preg_replace('~<(version|release_date)>.*?</\1>~s', '', $updatedManifest)
                !== preg_replace('~<(version|release_date)>.*?</\1>~s', '', $manifest)) {
            throw new RuntimeException('Unexpected release metadata or unrelated edits.');
        }
    },
];

$cases = [
    'reject malformed version' => [$manifest, $module, 'v3.2.3', '2028-02-29'],
    'reject version with leading zeros' => [$manifest, $module, '03.2.3', '2028-02-29'],
    'reject unchanged version' => [$manifest, $module, $current, '2028-02-29'],
    'reject older version' => [$manifest, $module, '0.0.0', '2028-02-29'],
    'reject invalid calendar date' => [$manifest, $module, $next, '2027-02-29'],
    'reject ambiguous module version' => [$manifest, $module . $module, $next, '2028-02-29'],
    'reject mismatched module version' => [$manifest, str_replace("'br-location-extension/$current'", "'br-location-extension/0.0.0'", $module), $next, '2028-02-29'],
    'reject missing date element' => [preg_replace('~<release_date>.*?</release_date>~s', '', $manifest), $module, $next, '2028-02-29'],
];
foreach ($cases as $name => $arguments) {
    $tests[$name] = static function () use ($arguments, $bridge): void {
        array_splice($arguments, 2, 0, [$bridge]);
        try {
            prepareReleaseContents(...$arguments);
        } catch (RuntimeException $error) {
            return;
        }
        throw new RuntimeException('Invalid release input was accepted.');
    };
}

foreach ([
    'reject mismatched bridge version' => str_replace("'br-location-extension-bridge-for-datacenterview/$current'", "'br-location-extension-bridge-for-datacenterview/0.0.0'", $bridge),
    'reject mismatched bridge dependencies' => str_replace("br-location-extension/$current", 'br-location-extension/0.0.0', $bridge),
    'reject ambiguous bridge registration' => $bridge . $bridge,
    'reject missing bridge dependencies' => str_replace("br-location-extension/$current", 'other-extension/0.0.0', $bridge),
] as $name => $invalidBridge) {
    $tests[$name] = static function () use ($manifest, $module, $invalidBridge, $next): void {
        try {
            prepareReleaseContents($manifest, $module, $invalidBridge, $next, '2028-02-29');
        } catch (RuntimeException $error) {
            return;
        }
        throw new RuntimeException('Invalid bridge metadata was accepted.');
    };
}

return $tests;
