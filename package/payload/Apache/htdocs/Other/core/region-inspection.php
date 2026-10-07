<?php
require_once __DIR__ . '/dreamgrid-env.php';
function ri_regions(): array {
    $root = realpath((string)ag_dg_regions_root());
    if ($root === false) throw new RuntimeException('Regions directory unavailable.');
    $prefix = strtolower(str_replace('\\', '/', $root)) . '/';
    $regions = [];
    foreach (glob($root . '/*/Region/*.ini') ?: [] as $file) {
        $real = realpath($file);
        if ($real === false || !str_starts_with(strtolower(str_replace('\\', '/', $real)), $prefix) || filesize($real) > 262144) continue;
        $parsed = @parse_ini_file($real, true, INI_SCANNER_RAW);
        if (!is_array($parsed)) continue;
        foreach ($parsed as $name => $section) {
            if (!is_array($section)) continue;
            $section = array_change_key_case($section, CASE_LOWER);
            $uuid = $section['regionuuid'] ?? '';
            $port = $section['internalport'] ?? '';
            if (!is_string($uuid) || !preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i', $uuid)) continue;
            $regions[] = ['RegionName'=>(string)$name, 'RegionUUID'=>strtolower($uuid), 'Port'=>is_scalar($port)?(int)$port:0, 'IniPath'=>$real];
        }
    }
    return $regions;
}
function ri_select(array $regions, $name, $uuid = ''): array {
    if (!is_string($name) || !is_string($uuid) || strlen($name)>255 || preg_match('/[\x00-\x1f]/', $name)) throw new InvalidArgumentException('Invalid region.');
    $name=trim($name); $uuid=trim($uuid);
    if ($uuid !== '' && !preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i', $uuid)) throw new InvalidArgumentException('Invalid region UUID.');
    if ($name === '' && $uuid === '') throw new InvalidArgumentException('Select a region.');
    $matches = array_values(array_filter($regions, static fn($r) => $uuid !== '' ? strcasecmp($r['RegionUUID'],$uuid)===0 : strcasecmp($r['RegionName'],$name)===0));
    if (count($matches)!==1) throw new RuntimeException('Region was not found or its identity is ambiguous.');
    return $matches[0];
}
