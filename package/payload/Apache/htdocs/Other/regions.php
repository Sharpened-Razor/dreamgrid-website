<?php
require_once __DIR__ . '/core/bootstrap.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$s = ag_current_session();
if (!$s) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Not logged in']);
    exit;
}

function dgRegionStatus($regionName) {
    $url =
        rtrim(
            ag_dg_diagnostics_base(),
            '/'
        ) .
        '/?command=RegionStatus&RegionName=' .
        rawurlencode($regionName);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 3,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        return 'Unknown';
    }

    $status = trim($response);
    return $status !== '' ? $status : 'Unknown';
}

$url =
    rtrim(
        ag_dg_diagnostics_base(),
        '/'
    ) .
    '/?command=regionlist&page=1&rp=500&sortorder=asc';
$json = @file_get_contents($url);
if ($json === false) {
    http_response_code(502);
    echo json_encode(['ok'=>false,'error'=>'Region list unavailable']);
    exit;
}

$data = json_decode($json, true);
$rows = $data['rows'] ?? [];
$out = [];

foreach ($rows as $row) {
    $c = $row['cell'] ?? [];
    $owner = trim((string)($c['EstateOwner'] ?? ''));

    if (ag_is_admin($s) || strcasecmp($owner, $s['avatar']) === 0) {
        $regionName = (string)($c['RegionName'] ?? '');

        $out[] = [
            'RegionName'   => $regionName,
            'EstateName'   => (string)($c['EstateName'] ?? ''),
            'EstateOwner'  => $owner,
            'Ram'          => (string)($c['Ram'] ?? ''),
            'PrimCount'    => (int)($c['PrimCount'] ?? 0),
            'AvatarCount'  => (int)($c['AvatarCount'] ?? 0),
            'Size'         => (string)($c['Size'] ?? ''),
            'Status'       => dgRegionStatus($regionName)
        ];
    }
}

echo json_encode([
    'ok'      => true,
    'regions' => $out,
    'admin'   => ag_is_admin($s)
]);
