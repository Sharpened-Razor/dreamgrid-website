<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/icons.php';
require_once __DIR__ . '/core/navigation.php';

ag_no_cache();

$session = ag_require_admin();
$avatar = ag_avatar_name($session);
$level = ag_user_level($session);
/* AUSTRALIA ADMIN REGIONS FINAL NAV */
$nav = [
    [
        'label' => 'ADMIN DASHBOARD',
        'url'   => '/Other/admin-dashboard.php',
    ],
    [
        'label' => 'MANAGE ACCOUNTS',
        'url'   => '/Other/admin-accounts.php',
    ],
    [
        'label' => 'MANAGE GROUPS',
        'url'   => '/Other/admin-groups.php',
    ],
    [
        'label' => 'CONSOLE',
        'url'   => '/Other/admin-console.php',
    ],
    [
        'label' => 'ADMIN HOME',
        'url'   => '/Other/admin-home.php',
    ],
];

$zeroUuid =
    '00000000-0000-0000-0000-000000000000';

$regionsRoot =
    ag_dg_regions_root();

$query =
    trim(
        (string)($_GET['q'] ?? '')
    );

$selectedId =
    trim(
        (string)($_GET['id'] ?? '')
    );

function validUuid(string $value): bool
{
    return (bool)preg_match(
        '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
        $value
    );
}

function iniScalar(array $section, array $names): ?string
{
    foreach ($names as $name) {
        foreach ($section as $key => $value) {
            if (strcasecmp((string)$key, $name) === 0) {
                if (is_array($value)) {
                    return null;
                }

                return trim((string)$value);
            }
        }
    }

    return null;
}

function parseRegionIni(string $path): ?array
{
    $parsed =
        @parse_ini_file(
            $path,
            true,
            INI_SCANNER_RAW
        );

    if (!is_array($parsed) || !$parsed) {
        return null;
    }

    /*
     * DreamGrid rule: one region section per Region INI.
     * Ignore DEFAULT-like/global scalar values if present.
     */
    foreach ($parsed as $sectionName => $section) {
        if (!is_array($section)) {
            continue;
        }

        $uuid =
            iniScalar(
                $section,
                ['RegionUUID']
            );

        if (!$uuid || !validUuid($uuid)) {
            continue;
        }

        return [
            'section_name' =>
                (string)$sectionName,
            'region_uuid' =>
                strtolower($uuid),
            'location' =>
                iniScalar(
                    $section,
                    ['Location']
                ),
            'internal_port' =>
                iniScalar(
                    $section,
                    ['InternalPort']
                ),
            'internal_address' =>
                iniScalar(
                    $section,
                    ['InternalAddress']
                ),
            'external_host' =>
                iniScalar(
                    $section,
                    ['ExternalHostName']
                ),
            'max_agents' =>
                iniScalar(
                    $section,
                    ['MaxAgents']
                ),
            'max_prims' =>
                iniScalar(
                    $section,
                    ['MaxPrims']
                ),
            'size_x' =>
                iniScalar(
                    $section,
                    ['SizeX','RegionSizeX']
                ),
            'size_y' =>
                iniScalar(
                    $section,
                    ['SizeY','RegionSizeY']
                ),

            /*
             * Region Manager settings.
             * These mirror the native Region window controls.
             */

            'smart_boot' =>
                iniScalar(
                    $section,
                    ['SmartBoot']
                ),

            'smart_start' =>
                iniScalar(
                    $section,
                    ['SmartStart']
                ),

            'group_port' =>
                iniScalar(
                    $section,
                    ['GroupPort']
                ),

            'map_type' =>
                iniScalar(
                    $section,
                    ['MapType']
                ),

            'physics' =>
                iniScalar(
                    $section,
                    ['Physics']
                ),

            'birds' =>
                iniScalar(
                    $section,
                    ['Birds']
                ),

            'tides' =>
                iniScalar(
                    $section,
                    ['Tides']
                ),

            'teleport' =>
                iniScalar(
                    $section,
                    ['Teleport']
                ),

            'allow_gods' =>
                iniScalar(
                    $section,
                    ['AllowGods']
                ),

            'region_god' =>
                iniScalar(
                    $section,
                    ['RegionGod']
                ),

            'manager_god' =>
                iniScalar(
                    $section,
                    ['ManagerGod']
                ),

            'skip_auto_backup' =>
                iniScalar(
                    $section,
                    ['SkipAutoBackup']
                ),

            'publicity' =>
                iniScalar(
                    $section,
                    ['Publicity']
                ),

            'region_snapshot' =>
                iniScalar(
                    $section,
                    ['RegionSnapShot']
                ),

            'script_rate' =>
                iniScalar(
                    $section,
                    ['MinTimerInterval']
                ),

            'frame_rate' =>
                iniScalar(
                    $section,
                    ['FrameTime']
                ),

            'path' =>
                str_replace('\\', '/', $path),
            'file_name' =>
                basename($path),
            'dos_box' =>
                basename(
                    dirname(
                        dirname($path)
                    )
                ),
        ];
    }

    return null;
}

function scanRegionInis(string $root): array
{
    $byUuid = [];
    $all = [];

    if (!is_dir($root)) {
        return [
            'by_uuid' => [],
            'all' => [],
        ];
    }

    $dosBoxes =
        @scandir($root);

    if (!is_array($dosBoxes)) {
        return [
            'by_uuid' => [],
            'all' => [],
        ];
    }

    foreach ($dosBoxes as $dosBox) {

        if (
            $dosBox === '.' ||
            $dosBox === '..'
        ) {
            continue;
        }

        $regionDir =
            $root .
            '/' .
            $dosBox .
            '/Region';

        if (!is_dir($regionDir)) {
            continue;
        }

        $files =
            @glob(
                $regionDir .
                '/*.ini'
            );

        if (!is_array($files)) {
            continue;
        }

        foreach ($files as $path) {

            $info =
                parseRegionIni($path);

            if (!$info) {
                continue;
            }

            $all[] =
                $info;

            $uuid =
                strtolower(
                    $info['region_uuid']
                );

            if (!isset($byUuid[$uuid])) {
                $byUuid[$uuid] = [];
            }

            $byUuid[$uuid][] =
                $info;
        }
    }

    return [
        'by_uuid' => $byUuid,
        'all' => $all,
    ];
}

function humanSeen($value): string
{
    $stamp = (int)$value;

    if ($stamp <= 0) {
        return 'Never / unknown';
    }

    return date(
        'Y-m-d H:i:s',
        $stamp
    );
}

function gridCoord($value): string
{
    if (!is_numeric($value)) {
        return '?';
    }

    return (string)(
        ((int)$value) / 256
    );
}

function regionSizeLabel($x, $y): string
{
    $sx = (int)$x;
    $sy = (int)$y;

    if ($sx <= 0 || $sy <= 0) {
        return 'Unknown';
    }

    $gx =
        $sx / 256;

    $gy =
        $sy / 256;

    return
        $sx .
        ' x ' .
        $sy .
        ' m (' .
        rtrim(rtrim(number_format($gx, 2, '.', ''), '0'), '.') .
        ' x ' .
        rtrim(rtrim(number_format($gy, 2, '.', ''), '0'), '.') .
        ')';
}

function regionSearchText(array $region): string
{
    return strtolower(
        implode(
            ' ',
            [
                (string)$region['regionName'],
                (string)$region['uuid'],
                (string)$region['owner_name'],
                (string)$region['owner_uuid'],
                (string)$region['serverIP'],
                (string)$region['serverPort'],
                (string)$region['grid_x'],
                (string)$region['grid_y'],
                (string)($region['ini']['dos_box'] ?? ''),
                (string)($region['ini']['path'] ?? ''),
            ]
        )
    );
}

$iniScan =
    scanRegionInis(
        $regionsRoot
    );


/*
 ============================================================
 DREAMGRID LIVE REGION REGISTRATION

 Reads DreamGrid's existing port-8001 region list.
 READ / DETECTION ONLY.
 ============================================================
*/

function dreamGridLiveRegionList(): array
{
    $url =
        ag_dg_diagnostics_base() . '/?command=regionlist&page=1&rp=500&sortorder=asc';

    $json = null;

    if (function_exists('curl_init')) {

        $ch =
            curl_init(
                $url
            );

        curl_setopt_array(
            $ch,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_TIMEOUT => 4,
            ]
        );

        $response =
            curl_exec(
                $ch
            );

        if (is_string($response)) {
            $json = $response;
        }

        curl_close(
            $ch
        );
    }

    if ($json === null) {

        $context =
            stream_context_create(
                [
                    'http' =>
                        [
                            'timeout' => 4,
                            'ignore_errors' => true,
                        ],
                ]
            );

        $response =
            @file_get_contents(
                $url,
                false,
                $context
            );

        if (is_string($response)) {
            $json = $response;
        }
    }

    if (
        !is_string($json) ||
        trim($json) === ''
    ) {
        return [];
    }

    $data =
        json_decode(
            $json,
            true
        );

    if (
        !is_array($data) ||
        !isset($data['rows']) ||
        !is_array($data['rows'])
    ) {
        return [];
    }

    $live = [];

    foreach ($data['rows'] as $row) {

        $cell =
            $row['cell'] ??
            null;

        if (!is_array($cell)) {
            continue;
        }

        $name =
            trim(
                (string)(
                    $cell['RegionName'] ??
                    ''
                )
            );

        if ($name === '') {
            continue;
        }

        $live[
            strtolower($name)
        ] =
            $cell;
    }

    return $live;
}


$dreamGridRegions =
    dreamGridLiveRegionList();


function australiaRegionFreezeState(): array
{
    $file =
        __DIR__ .
        DIRECTORY_SEPARATOR .
        'jobs' .
        DIRECTORY_SEPARATOR .
        'region_freeze_state.json';

    if (!is_file($file)) {
        return [];
    }

    $raw =
        @file_get_contents($file);

    if (
        $raw === false ||
        trim($raw) === ''
    ) {
        return [];
    }

    $decoded =
        json_decode(
            $raw,
            true
        );

    return
        is_array($decoded)
            ? $decoded
            : [];
}


$regionFreezeState =
    australiaRegionFreezeState();



function dgRegionBootEnabled(
    array $region,
    bool $fallback
): bool
{
    $path =
        trim(
            (string)(
                $region['ini']['path'] ??
                ''
            )
        );

    if (
        $path === '' ||
        !is_file($path)
    ) {
        return $fallback;
    }

    $text =
        @file_get_contents(
            $path
        );

    if (!is_string($text)) {
        return $fallback;
    }

    if (
        preg_match(
            '/^\s*Enabled\s*=\s*(True|False)\s*$/mi',
            $text,
            $match
        )
    ) {
        return
            strcasecmp(
                $match[1],
                'True'
            ) === 0;
    }

    return $fallback;
}

$con =
    ag_db_connect();

if (!$con) {
    http_response_code(500);
    exit('Grid database is unavailable.');
}

mysqli_set_charset(
    $con,
    'utf8mb4'
);

$sql =
    'SELECT r.*, ua.FirstName, ua.LastName, ua.UserLevel ' .
    'FROM regions r ' .
    'LEFT JOIN UserAccounts ua ' .
    'ON ua.PrincipalID = r.owner_uuid ' .
    'ORDER BY r.regionName';

$result =
    mysqli_query(
        $con,
        $sql
    );

if (!$result) {
    mysqli_close($con);
    http_response_code(500);
    exit('Could not read the Robust regions table.');
}

require_once __DIR__ . '/core/region-database.php';

$regions = [];

$regionDb =
    ag_region_db_connect();

while ($row = mysqli_fetch_assoc($result)) {

    $uuid =
        strtolower(
            (string)$row['uuid']
        );

    $ownerName =
        trim(
            (string)($row['FirstName'] ?? '') .
            ' ' .
            (string)($row['LastName'] ?? '')
        );

    $iniMatches =
        $iniScan['by_uuid'][$uuid] ?? [];

    $ini =
        count($iniMatches) === 1
            ? $iniMatches[0]
            : null;

    $dbX =
        gridCoord(
            $row['locX']
        );

    $dbY =
        gridCoord(
            $row['locY']
        );

    $issues = [];

    if (count($iniMatches) === 0) {
        $issues[] =
            'No Region INI matched this UUID.';
    }
    elseif (count($iniMatches) > 1) {
        $issues[] =
            'More than one Region INI uses this UUID.';
    }
    else {
        if (
            strcasecmp(
                trim((string)$row['regionName']),
                trim((string)$ini['section_name'])
            ) !== 0
        ) {
            $issues[] =
                'Database name and INI section name differ.';
        }

        if (
            strcasecmp(
                trim((string)$ini['file_name']),
                trim((string)$ini['section_name']) . '.ini'
            ) !== 0
        ) {
            $issues[] =
                'INI filename does not match its region section name.';
        }

        if (
            $ini['internal_port'] !== null &&
            (int)$ini['internal_port'] !==
                (int)$row['serverPort']
        ) {
            $issues[] =
                'Database port and INI InternalPort differ.';
        }

        if ($ini['location'] !== null) {

            $parts =
                array_map(
                    'trim',
                    explode(
                        ',',
                        $ini['location']
                    )
                );

            if (
                count($parts) >= 2 &&
                is_numeric($parts[0]) &&
                is_numeric($parts[1])
            ) {
                if (
                    (int)$parts[0] !== (int)$dbX ||
                    (int)$parts[1] !== (int)$dbY
                ) {
                    $issues[] =
                        'Database grid location and INI Location differ.';
                }
            }
        }

        if (
            $ini['external_host'] !== null &&
            trim((string)$row['serverIP']) !== '' &&
            strcasecmp(
                trim((string)$ini['external_host']),
                trim((string)$row['serverIP'])
            ) !== 0
        ) {
            $issues[] =
                'Database server host and INI ExternalHostName differ.';
        }
    }

    $region = $row;

    $region['owner_name'] =
        $ownerName !== ''
            ? $ownerName
            : 'Unknown / UUID only';

    $region['grid_x'] =
        $dbX;

    $region['grid_y'] =
        $dbY;

    $region['ini_matches'] =
        $iniMatches;

    $region['ini'] =
        $ini;

    $region['issues'] =
        $issues;

    $region['status'] =
        $issues
            ? 'CHECK'
            : 'MATCHED';

    
    /*
     * Read-only native-compatible parcel status.
     */

    $region['parcel_settings'] =
        ag_region_parcel_settings(
            $regionDb,
            $uuid
        );
$regions[] =
        $region;
}

if (
    $regionDb instanceof mysqli
) {

    try {

        $regionDb->close();

    }
    catch (Throwable $ignored) {
    }
}
mysqli_free_result($result);
mysqli_close($con);


/*
 ============================================================
 REGION MANAGER INI ONLY NORMAL LIST V1

 DreamGrid Region INIs that have not yet registered with
 Robust are represented as normal selectable Region Manager
 rows with status INI ONLY.

 DISPLAY ONLY.
 No Region INI or database data is changed here.
 ============================================================
*/

/* dreamgrid-region-manager-deregister-filter-v2 */

$dgWebDeregisteredMarkerRoot =
    ag_dg_path(
        '_WEB_CONTROL',
        'DeregisteredRegions'
    );


$dgRegionManagerIsDeregistered =
    static function(
        string
        $uuid
    ) use (
        $dgWebDeregisteredMarkerRoot
    ): bool {

        $uuid =
            strtolower(
                trim(
                    $uuid
                )
            );


        if (
            !preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
                $uuid
            )
        ) {

            return false;
        }


        if (
            !is_string(
                $dgWebDeregisteredMarkerRoot
            ) ||
            $dgWebDeregisteredMarkerRoot === ''
        ) {

            return false;
        }


        return
            is_file(
                $dgWebDeregisteredMarkerRoot .
                DIRECTORY_SEPARATOR .
                $uuid .
                '.flag'
            );
    };


/*
 * Remove any deregistered database-backed row before the
 * Region Manager builds its visible workspace.
 */
$regions =
    array_values(
        array_filter(
            $regions,
            static function(
                array
                $region
            ) use (
                $dgRegionManagerIsDeregistered
            ): bool {

                $uuid =
                    (string)(
                        $region['uuid'] ??
                        ''
                    );


                return
                    !$dgRegionManagerIsDeregistered(
                        $uuid
                    );
            }
        )
    );

$registeredUuids = [];

foreach ($regions as $registeredRegion) {

    $registeredUuid =
        strtolower(
            trim(
                (string)(
                    $registeredRegion['uuid'] ??
                    ''
                )
            )
        );


    if ($registeredUuid !== '') {

        $registeredUuids[
            $registeredUuid
        ] =
            true;
    }
}


$iniOnlyRegions = [];


foreach (
    $iniScan['all']
    as
    $ini
) {

    $iniUuid =
        strtolower(
            trim(
                (string)(
                    $ini['region_uuid'] ??
                    ''
                )
            )
        );


    if (
        $dgRegionManagerIsDeregistered(
            $iniUuid
        )
    ) {

        continue;
    }

    if (
        $iniUuid === '' ||
        isset(
            $registeredUuids[
                $iniUuid
            ]
        )
    ) {

        continue;
    }


    $gridX =
        '?';

    $gridY =
        '?';


    $location =
        trim(
            (string)(
                $ini['location'] ??
                ''
            )
        );


    if ($location !== '') {

        $locationParts =
            array_map(
                'trim',
                explode(
                    ',',
                    $location
                )
            );


        if (
            count(
                $locationParts
            ) >= 2 &&
            is_numeric(
                $locationParts[0]
            ) &&
            is_numeric(
                $locationParts[1]
            )
        ) {

            $gridX =
                (string)(
                    (int)$locationParts[0]
                );

            $gridY =
                (string)(
                    (int)$locationParts[1]
                );
        }
    }


    $sizeX =
        (int)(
            $ini['size_x'] ??
            256
        );

    $sizeY =
        (int)(
            $ini['size_y'] ??
            256
        );


    if ($sizeX <= 0) {
        $sizeX = 256;
    }


    if ($sizeY <= 0) {
        $sizeY = 256;
    }


    $port =
        trim(
            (string)(
                $ini['internal_port'] ??
                ''
            )
        );


    if ($port === '') {
        $port = 'Not set';
    }


    $host =
        trim(
            (string)(
                $ini['external_host'] ??
                ''
            )
        );


    if ($host === '') {
        $host = 'Not set';
    }



    $dreamGridKey =
        strtolower(
            trim(
                (string)(
                    $ini['section_name'] ??
                    ''
                )
            )
        );

    $dreamGridRegion =
        $dreamGridRegions[
            $dreamGridKey
        ] ??
        null;

    $dreamGridRegistered =
        is_array(
            $dreamGridRegion
        );
    $region =
        [
            'uuid' =>
                $iniUuid,

            'regionName' =>
                (string)(
                    $ini['section_name'] ??
                    'Unnamed Region'
                ),

            'owner_uuid' =>
                $zeroUuid,

            'owner_name' =>
                'Pending Robust registration',

            'grid_x' =>
                $gridX,

            'grid_y' =>
                $gridY,

            'sizeX' =>
                $sizeX,

            'sizeY' =>
                $sizeY,

            'serverIP' =>
                $host,

            'serverPort' =>
                $port,

            'serverHttpPort' =>
                'Not registered',

            'serverURI' =>
                'Not registered',

            'access' =>
                'Not registered',

            'flags' =>
                'Not registered',

            'last_seen' =>
                0,

            'regionMapTexture' =>
                $zeroUuid,

            'ini_matches' =>
                [
                    $ini
                ],

            'ini' =>
                $ini,

            'issues' =>
                [
                    $dreamGridRegistered
                        ? 'Region is registered with the grid service but Robust registration has not occurred yet.'
                        : 'Region INI exists but grid-service and Robust registration have not occurred yet.',
                ],

            'status' =>
                $dreamGridRegistered
                    ? 'GRID REGISTERED'
                    : 'INI ONLY',

            'ini_only' =>
                true,

            'dreamgrid_registered' =>
                $dreamGridRegistered,

            'dreamgrid' =>
                $dreamGridRegion,
        ];


    $regions[] =
        $region;

    $iniOnlyRegions[] =
        $region;
}


/*
 * Keep the combined Region Manager list alphabetical.
 */

usort(
    $regions,
    static function(
        array $a,
        array $b
    ): int {

        return
            strcasecmp(
                (string)(
                    $a['regionName'] ??
                    ''
                ),
                (string)(
                    $b['regionName'] ??
                    ''
                )
            );
    }
);

$filtered = [];

foreach ($regions as $region) {

    if ($query === '') {
        $filtered[] =
            $region;

        continue;
    }

    if (
        strpos(
            regionSearchText($region),
            strtolower($query)
        ) !== false
    ) {
        $filtered[] =
            $region;
    }
}

$selected = null;

if ($selectedId !== '' && validUuid($selectedId)) {
    foreach ($regions as $region) {
        if (
            strcasecmp(
                (string)$region['uuid'],
                $selectedId
            ) === 0
        ) {
            $selected = $region;
            break;
        }
    }
}

if (!$selected && $filtered) {
    $selected =
        $filtered[0];
}

$matchedCount = 0;
$dreamGridCount = 0;
$checkCount = 0;
$iniOnlyCount = 0;

foreach ($regions as $region) {

    if (
        $region['status'] ===
        'MATCHED'
    ) {

        $matchedCount++;
    }
    elseif (
        $region['status'] ===
        'GRID REGISTERED'
    ) {

        $dreamGridCount++;
    }
    elseif (
        $region['status'] ===
        'INI ONLY'
    ) {

        $iniOnlyCount++;
    }
    else {

        $checkCount++;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Manage Regions</title>

<link
    rel="stylesheet"
    href="/Other/core/site-shell.css?v=1">
<link rel="stylesheet" href="/Other/australia-3d-theme.css?v=20260820-022508">

<style>
.region-summary{
    display:grid;
    grid-template-columns:repeat(5,minmax(0,1fr));
    gap:12px;
    margin-bottom:18px;
}
.summary-card{
    padding:14px;
    border:1px solid rgba(255,255,255,.11);
    border-radius:11px;
    background:rgba(8,13,16,.82);
}
.summary-card span{
    display:block;
    margin-bottom:5px;
    color:#90a4ae;
    font-size:11px;
    font-weight:900;
}
.summary-card strong{
    font-size:24px;
}
.region-layout{
    display:grid;
    grid-template-columns:minmax(310px,.85fr) minmax(0,1.5fr);
    gap:18px;
    align-items:start;
}
.region-list,
.region-detail{
    border:1px solid rgba(255,255,255,.11);
    border-radius:14px;
    background:rgba(8,13,16,.84);
}
.region-list{
    overflow:hidden;
}
.search{
    padding:14px;
    border-bottom:1px solid rgba(255,255,255,.10);
}
.search form{
    display:flex;
    gap:8px;
}
.search input{
    flex:1;
    min-width:0;
    padding:10px 12px;
    border:1px solid rgba(255,255,255,.15);
    border-radius:8px;
    color:#fff;
    background:#080d10;
}
.region-row{
    display:block;
    padding:13px 14px;
    border-bottom:1px solid rgba(255,255,255,.08);
    color:#fff;
    text-decoration:none;
}
.region-row:hover,
.region-row.active{
    background:rgba(255,255,255,.055);
}
.region-row:last-child{
    border-bottom:0;
}
.row-top{
    display:flex;
    gap:8px;
    align-items:center;
    justify-content:space-between;
}
.row-name{
    font-weight:900;
}
.row-meta{
    margin-top:5px;
    color:#92a5ae;
    font-size:12px;
}
.badge{
    display:inline-flex;
    align-items:center;
    min-height:23px;
    padding:0 8px;
    border-radius:999px;
    font-size:10px;
    font-weight:900;
}
.badge.ok{
    color:#bff9ce;
    border:1px solid rgba(78,220,119,.28);
    background:rgba(45,132,68,.16);
}
.badge.warn{
    color:#ffe1a5;
    border:1px solid rgba(244,179,35,.30);
    background:rgba(244,179,35,.10);
}
.region-detail{
    padding:20px;
}
.detail-title{
    display:flex;
    gap:12px;
    align-items:flex-start;
    justify-content:space-between;
    margin-bottom:15px;
}
.detail-title h2{
    margin:0;
}
.detail-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:10px;
}
.detail-item{
    min-width:0;
    padding:11px 12px;
    border:1px solid rgba(255,255,255,.10);
    border-radius:9px;
    background:rgba(0,0,0,.16);
}
.detail-item.full{
    grid-column:1/-1;
}
.detail-item span{
    display:block;
    margin-bottom:4px;
    color:#8fa3ad;
    font-size:10px;
    font-weight:900;
}
.detail-item strong,
.detail-item div{
    overflow-wrap:anywhere;
}
.uuid,
.path{
    font-family:Consolas,monospace;
}
.section-title{
    margin:20px 0 9px;
    color:#b9c7ce;
    font-size:12px;
    font-weight:900;
    letter-spacing:.04em;
}
.issue{
    padding:10px 12px;
    margin:7px 0;
    border-left:4px solid #f2b843;
    background:rgba(244,179,35,.08);
}
.good{
    padding:10px 12px;
    border-left:4px solid #52d477;
    background:rgba(45,132,68,.10);
}
@media(max-width:950px){
    .region-summary{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .region-layout{
        grid-template-columns:1fr;
    }
}
@media(max-width:650px){
    .region-summary,
    .detail-grid{
        grid-template-columns:1fr;
    }
    .detail-item.full{
        grid-column:auto;
    }
}

/* AUSTRALIA ADMIN REGIONS SITE STYLE */
.ag-nav{
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:9px;
    margin-top:16px;
    margin-bottom:18px;
}

.ag-nav a{
    min-height:42px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:0 18px;
    border-radius:9px;
    font-weight:900;
    text-decoration:none;
    white-space:nowrap;
}

.ag-nav a:first-child{
    color:#17120a;
    border-color:#d9a932;
    background:
        linear-gradient(
            180deg,
            #f2bd45,
            #b67d14
        );
}
/* END AUSTRALIA ADMIN REGIONS SITE STYLE */

/* ============================================================
   AUSTRALIA REGION MANAGER CLEAN STYLE V2
   ============================================================ */
.region-tool-button{
    display:inline-flex;
    align-items:center;
    justify-content:center;

    min-height:42px;
    padding:0 18px;

    border:
        1px solid
        rgba(255,255,255,.16);

    border-radius:9px;

    background:
        linear-gradient(
            180deg,
            rgba(37,48,54,.96),
            rgba(15,21,25,.96)
        );

    color:#f2f4f5 !important;

    font-size:13px;
    font-weight:900;
    letter-spacing:.025em;

    text-decoration:none !important;

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.08),
        0 4px 10px
        rgba(0,0,0,.30);
}

.region-tool-button:hover{
    transform:translateY(-1px);

    border-color:
        rgba(226,173,57,.55);
}

.region-tool-button.primary{
    color:#171208 !important;

    border-color:#d6a12d;

    background:
        linear-gradient(
            180deg,
            #f5bd3e,
            #b77910
        );

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.40),
        0 5px 12px
        rgba(0,0,0,.35);
}
.region-summary{
    gap:10px !important;
    margin-bottom:14px !important;
}

.summary-card{
    min-height:72px !important;

    padding:13px 15px !important;

    border:
        1px solid
        rgba(210,158,43,.45) !important;

    border-radius:11px !important;

    background:
        linear-gradient(
            180deg,
            rgba(28,37,42,.94),
            rgba(9,14,17,.94)
        ) !important;

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.06),
        0 4px 10px
        rgba(0,0,0,.35) !important;
}

.summary-card span{
    color:#aeb9bf !important;
}

.summary-card strong{
    color:#ffffff !important;
    font-size:22px !important;
}

.region-layout{
    margin-top:0 !important;
}

.region-list,
.region-detail{
    border:
        1px solid
        rgba(255,255,255,.10) !important;

    background:
        rgba(6,11,14,.86) !important;

    box-shadow:
        0 8px 22px
        rgba(0,0,0,.34) !important;
}

/* Remove any old duplicated navigation spacing */
.ag-nav{
    display:none !important;
}
/* END AUSTRALIA REGION MANAGER CLEAN STYLE V2 */

/* ============================================================
   REGION MANAGER PAUSE / RESUME PNG ICON
   ============================================================ */

#dgPauseButton .dg-pause-real-icon{
    width:32px;
    min-width:32px;
    height:24px;

    display:flex;
    align-items:center;
    justify-content:center;

    margin:0;
    padding:0;
}

#dgPauseButton .dg-pause-real-icon img{
    display:block;

    width:24px;
    height:24px;

    max-width:24px;
    max-height:24px;

    object-fit:contain;

    margin:0;
    padding:0;

    border:0;
    background:none;
}

</style>

<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">

<style id="australia-region-manager-design-v3">

/* ============================================================
   Grid - REGION MANAGER DESIGN V3
   ============================================================ */


/* ------------------------------------------------------------
   MAIN PAGE FRAME
   ------------------------------------------------------------ */

body{

    overflow-x:hidden !important;
}


.ag-shell{

    width:
        min(
            1500px,
            calc(100% - 44px)
        ) !important;

    max-width:
        1500px !important;

    margin:
        26px auto
        48px !important;

    padding:
        22px !important;

    border:
        1px solid
        rgba(220,166,45,.48) !important;

    border-radius:
        20px !important;

    background:
        linear-gradient(
            145deg,
            rgba(18,25,29,.87),
            rgba(5,10,13,.82)
        ) !important;

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.09),

        0 18px 55px
        rgba(0,0,0,.50),

        0 0 0 1px
        rgba(0,0,0,.40) !important;

    backdrop-filter:
        blur(9px);

}
/* ------------------------------------------------------------
   NEW MASTER HEADER
   ------------------------------------------------------------ */

.rm-master-header{

    display:flex;

    align-items:center;

    justify-content:
        space-between;

    gap:28px;

    padding:
        22px 24px;

    margin-bottom:
        10px;

    border:
        1px solid
        rgba(222,169,49,.42);

    border-radius:
        15px;

    background:

        linear-gradient(
            120deg,
            rgba(31,42,48,.96),
            rgba(10,16,19,.94)
        );

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.10),

        inset 0 -1px 0
        rgba(214,158,33,.18),

        0 8px 25px
        rgba(0,0,0,.35);
}


.rm-master-title{

    min-width:0;
}


.rm-kicker{

    margin-bottom:
        5px;

    color:
        #efb32d;

    font-size:
        12px;

    font-weight:
        900;

    letter-spacing:
        .18em;
}


.rm-master-title h1{

    margin:
        0 0 6px;

    color:
        #ffffff;

    font-size:
        clamp(
            27px,
            2.6vw,
            42px
        );

    line-height:
        1;

    font-weight:
        900;

    text-transform:
        uppercase;

    text-shadow:
        0 3px 8px
        rgba(0,0,0,.65);
}


.rm-master-title p{

    margin:0;

    color:
        #bfc8cc;

    font-size:
        14px;

    line-height:
        1.45;
}


/* ------------------------------------------------------------
   HEADER ACTION BUTTONS
   ------------------------------------------------------------ */

.rm-master-actions{

    display:flex;

    align-items:center;

    justify-content:
        flex-end;

    gap:10px;

    flex-wrap:
        wrap;
}


.rm-action-button{

    min-height:
        44px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    padding:
        0 17px;

    border:
        1px solid
        rgba(255,255,255,.17);

    border-radius:
        9px;

    color:
        #ffffff !important;

    background:

        linear-gradient(
            180deg,
            #27333a,
            #11181c
        );

    font-size:
        12px;

    font-weight:
        900;

    letter-spacing:
        .025em;

    text-decoration:
        none !important;

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.10),

        0 4px 12px
        rgba(0,0,0,.35);
}


.rm-action-button:hover{

    transform:
        translateY(-1px);

    border-color:
        rgba(239,179,45,.65);
}


.rm-action-button.primary{

    color:
        #161008 !important;

    border-color:
        #e1ad33;

    background:

        linear-gradient(
            180deg,
            #ffd45d,
            #eba91d 52%,
            #ad7009
        );

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.55),

        0 5px 14px
        rgba(0,0,0,.38);
}


.rm-action-symbol{

    font-size:
        19px;

    line-height:
        1;

    font-weight:
        900;
}


/* ------------------------------------------------------------
   STATUS STRIP
   ------------------------------------------------------------ */

.rm-status-strip{

    display:flex;

    align-items:center;

    gap:9px;

    margin:
        0 0 14px;

    padding:
        10px 14px;

    border:
        1px solid
        rgba(75,183,114,.30);

    border-radius:
        9px;

    background:
        rgba(7,22,15,.78);

    color:
        #aeb9bd;

    font-size:
        12px;
}


.rm-status-strip strong{

    color:
        #a9e8bd;
}


.rm-status-dot{

    width:
        9px;

    height:
        9px;

    flex:
        0 0 9px;

    border-radius:
        50%;

    background:
        #63df8b;

    box-shadow:
        0 0 9px
        rgba(99,223,139,.70);
}


/* ------------------------------------------------------------
   SUMMARY BOXES
   ------------------------------------------------------------ */

.region-summary{

    display:grid !important;

    grid-template-columns:
        repeat(
            4,
            minmax(0,1fr)
        ) !important;

    gap:
        11px !important;

    margin:
        0 0 14px !important;
}


.summary-card{

    min-height:
        78px !important;

    display:flex;

    flex-direction:
        column;

    justify-content:
        center;

    padding:
        13px 16px !important;

    border:
        1px solid
        rgba(207,154,34,.34) !important;

    border-radius:
        11px !important;

    background:

        linear-gradient(
            145deg,
            rgba(34,45,50,.95),
            rgba(10,16,19,.96)
        ) !important;

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.07),

        0 5px 15px
        rgba(0,0,0,.32) !important;
}


.summary-card span{

    color:
        #9caab0 !important;

    font-size:
        10px !important;

    font-weight:
        900 !important;

    letter-spacing:
        .04em;
}


.summary-card strong{

    margin-top:
        4px;

    color:
        #ffffff !important;

    font-size:
        24px !important;

    line-height:
        1 !important;
}


/* ------------------------------------------------------------
   TWO COLUMN REGION MANAGER
   ------------------------------------------------------------ */

.region-layout{

    display:grid !important;

    grid-template-columns:

        minmax(
            340px,
            .78fr
        )

        minmax(
            0,
            1.55fr
        )

        !important;

    gap:
        14px !important;

    align-items:
        start;
}


/* ------------------------------------------------------------
   REGION LIST
   ------------------------------------------------------------ */

.region-list{

    overflow:
        hidden;

    border:
        1px solid
        rgba(255,255,255,.12) !important;

    border-radius:
        14px !important;

    background:

        linear-gradient(
            180deg,
            rgba(14,21,25,.96),
            rgba(6,11,14,.96)
        ) !important;

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.05),

        0 10px 25px
        rgba(0,0,0,.36) !important;
}


.search{

    padding:
        12px !important;

    border-bottom:
        1px solid
        rgba(255,255,255,.08);

    background:
        rgba(255,255,255,.025);
}


.search form{

    display:grid !important;

    grid-template-columns:
        minmax(0,1fr)
        auto !important;

    gap:
        8px;
}


.search input{

    min-height:
        42px;

    border:
        1px solid
        rgba(255,255,255,.16) !important;

    border-radius:
        8px !important;

    background:
        rgba(0,0,0,.42) !important;

    color:
        #fff !important;
}


.region-row{

    padding:
        13px 14px !important;

    border-bottom:
        1px solid
        rgba(255,255,255,.07) !important;

    background:
        transparent !important;

    transition:
        .15s ease;
}


.region-row:hover{

    background:
        rgba(230,171,42,.08)
        !important;
}


.region-row.active{

    background:

        linear-gradient(
            90deg,
            rgba(224,164,37,.17),
            rgba(224,164,37,.03)
        ) !important;

    border-left:
        4px solid
        #e2a72f !important;
}


.row-name{

    font-size:
        14px !important;

    font-weight:
        900 !important;

    color:
        #ffffff !important;
}


/* ------------------------------------------------------------
   REGION DETAIL PANEL
   ------------------------------------------------------------ */

.region-detail{

    padding:
        18px !important;

    border:
        1px solid
        rgba(255,255,255,.12) !important;

    border-radius:
        14px !important;

    background:

        linear-gradient(
            145deg,
            rgba(15,22,26,.96),
            rgba(5,10,13,.95)
        ) !important;

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.06),

        0 10px 28px
        rgba(0,0,0,.38) !important;
}


.detail-title{

    padding-bottom:
        14px;

    margin-bottom:
        14px;

    border-bottom:
        1px solid
        rgba(255,255,255,.08);
}


.detail-title h2{

    color:
        #f0b12d !important;

    font-size:
        24px !important;
}


.detail-grid{

    gap:
        9px !important;
}


.detail-item{

    padding:
        11px 12px !important;

    border:
        1px solid
        rgba(255,255,255,.09) !important;

    border-radius:
        9px !important;

    background:
        rgba(0,0,0,.22) !important;
}


.detail-item span{

    color:
        #91a0a7 !important;

    font-size:
        9px !important;

    font-weight:
        900 !important;

    letter-spacing:
        .04em;
}


.detail-item strong{

    color:
        #f4f5f5 !important;

    font-size:
        13px !important;
}


/* ------------------------------------------------------------
   BUTTONS
   ------------------------------------------------------------ */

.ag-button.primary{

    border:
        1px solid
        #e3ad31 !important;

    color:
        #171109 !important;

    background:

        linear-gradient(
            180deg,
            #ffd45c,
            #eaa91c 52%,
            #ad7109
        ) !important;

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.55),

        0 4px 11px
        rgba(0,0,0,.38) !important;
}


/* ------------------------------------------------------------
   RESPONSIVE
   ------------------------------------------------------------ */

@media(max-width:1450px){

    .dg-toolbar{
        grid-template-columns:minmax(0,1fr) 300px;
        grid-template-areas:
            "filters search"
            "buttons buttons";
    }

    .dg-buttons{
        grid-template-columns:repeat(5,minmax(105px,1fr));
    }
}


@media(max-width:1000px){

    .dg-toolbar{
        grid-template-columns:1fr;
        grid-template-areas:
            "filters"
            "search"
            "buttons";
    }

    .dg-filters{
        grid-template-columns:repeat(3,max-content);
    }

    .dg-searchbar{
        width:100%;
        max-width:520px;
    }

    .dg-buttons{
        grid-template-columns:repeat(3,minmax(105px,1fr));
    }
}


@media(max-width:600px){

    .ag-shell{

        width:
            calc(100% - 18px)
            !important;

        padding:
            12px !important;

        margin:
            9px auto
            25px !important;
    }


    .region-summary{

        grid-template-columns:
            1fr !important;
    }


    .rm-master-actions{

        width:100%;
    }


    .rm-action-button{

        flex:
            1 1
            180px;
    }
}


/* ============================================================
   END REGION MANAGER DESIGN V3
   ============================================================ */

</style>
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">

<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=<?= filemtime(__DIR__ . '/assets/css/ag-uniform-site-v12.css') ?>">

<style id="australia-sentinel-sitewide-v3">


/* ============================================================
   AUSTRALIA SENTINEL 3D CARD SYSTEM V3
   SITE WIDE
   ============================================================ */


.dashboard-card,
.control-hub-card,
.stat-card,
.summary-card,
.inventory-card,
.profile-card,
.region-panel,
.section-card,
.manage-card{


position:relative !important;

overflow:hidden !important;


border-radius:15px !important;


border:

2px solid
rgba(210,218,220,.55) !important;


background:

linear-gradient(
145deg,
#4d5559,
#090b0c
) !important;


box-shadow:

inset 0 2px 0
rgba(255,255,255,.25),

inset 0 -22px 35px
rgba(0,0,0,.8),

0 18px 40px
rgba(0,0,0,.75) !important;


}



.dashboard-card:after,
.control-hub-card:after,
.stat-card:after,
.summary-card:after,
.inventory-card:after,
.profile-card:after,
.region-panel:after,
.section-card:after,
.manage-card:after{


content:"";

position:absolute;

inset:7px;

border-radius:10px;

pointer-events:none;


border:

1px solid
rgba(255,193,58,.45);

}



.card-title,
.card-heading,
.panel-title,
.summary-title{


color:#ffd167 !important;

font-weight:900 !important;

text-shadow:

0 2px 5px
rgba(0,0,0,.8);

}



.card-icon,
.card-icon.svg-badge{


background:

linear-gradient(
145deg,
#70777a,
#111415
) !important;


border:

1px solid
rgba(255,255,255,.3) !important;


box-shadow:

inset 0 2px 4px
rgba(255,255,255,.25),

0 8px 18px
rgba(0,0,0,.6);


}



.dashboard-card:hover,
.control-hub-card:hover,
.stat-card:hover,
.summary-card:hover{


transform:
translateY(-3px);


}


</style>


<style id="australia-user-level-badge-v1">

.php-level{

    min-width:128px;

    padding:
        8px 12px;

    text-align:center;

    border:
        1px solid
        rgba(67,151,220,.38);

    border-radius:
        8px;

    background:
        rgba(15,68,104,.22);

    font-size:10px;

    font-weight:800;

}


.php-level strong{

    display:block;

    margin-bottom:2px;

    color:#7bc6ff;

}


</style>








<!-- AUSTRALIA REGION LIVE OPS V1 CSS -->
<!-- AUSTRALIA REGION LIVE OPS V1 CSS END -->

</head>

<body id="ag-region-manager-page">

<div class="ag-shell">

<?php
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "REGION MANAGER";
$siteHeaderRole = "GRID OWNER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "CREATE REGION";
$siteHeaderLink = "/Other/admin-create-region.php";
require_once __DIR__ . "/includes/site-header.php";
?>





<!-- DREAMGRID STYLE REGION MANAGER V1 -->

<style id="dreamgrid-region-gui-v1">

body{
    overflow-x:hidden !important;
}

.ag-shell{
    width:calc(100% - 20px) !important;
    max-width:none !important;
    margin:10px auto 30px !important;
    padding:10px !important;
    border:1px solid rgba(205,154,42,.42) !important;
    border-radius:12px !important;
    background:rgba(7,11,14,.93) !important;
}

/* ---------------------------------------------------------
   DREAMGRID TOP STATUS LINE
   --------------------------------------------------------- */

.dg-statusline{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    min-height:32px;
    padding:5px 9px;
    margin-bottom:8px;
    border:1px solid rgba(255,255,255,.12);
    border-radius:6px;
    background:linear-gradient(180deg,#20282c,#101619);
    color:#cbd3d7;
    font-size:11px;
}

.dg-statusline strong{
    color:#fff;
}
/* ---------------------------------------------------------
   DREAMGRID CONTROL AREA
   --------------------------------------------------------- */

.dg-toolbar{
    display:grid;
    grid-template-columns:360px minmax(0,1fr) 300px;
    grid-template-areas:"filters buttons search";
    gap:12px;
    align-items:start;
    width:100%;
    box-sizing:border-box;
    padding:8px;
    margin-bottom:7px;
    border:1px solid rgba(255,255,255,.12);
    border-radius:6px;
    background:rgba(19,25,29,.96);
}

.dg-filters{
    grid-area:filters;
    display:grid;
    grid-template-columns:repeat(3,max-content);
    gap:7px 12px;
    align-items:center;
    align-content:start;
    justify-content:start;
    min-width:0;
    padding:3px 2px;
    font-size:11px;
    color:#d9dfe2;
}

.dg-filter{
    display:flex;
    align-items:center;
    gap:5px;
    white-space:nowrap;
}

.dg-filter input{
    margin:0;
}

.dg-selectall{
    grid-column:1/-1;
    padding-top:2px;
}

.dg-buttons{
    grid-area:buttons;
    display:grid;
    grid-template-columns:repeat(5,minmax(115px,1fr));
    gap:6px;
    min-width:0;
    width:100%;
}

.dg-button,
.dg-button-link{
    min-height:32px;

    display:grid !important;
    grid-template-columns:32px minmax(0,1fr);
    column-gap:10px;

    align-items:center;
    justify-content:stretch !important;

    padding:0 12px;

    border:1px solid rgba(255,255,255,.17);
    border-radius:4px;

    background:linear-gradient(180deg,#344047,#1b2429);

    color:#f2f5f6;

    font-family:Arial,Helvetica,sans-serif;
    font-size:11px;
    font-weight:700;

    text-align:left !important;
    text-decoration:none;

    box-shadow:inset 0 1px 0 rgba(255,255,255,.08);
}

.dg-button-link:hover,
.dg-button:not(:disabled):hover{
    border-color:#dca839;
    background:linear-gradient(180deg,#404d54,#242e33);
}

.dg-button:disabled{
    opacity:.78;
    cursor:not-allowed;
}

.dg-icon{
    width:32px;
    min-width:32px;
    height:24px;

    display:flex;
    align-items:center;
    justify-content:center;

    margin:0;
    padding:0;

    color:#e9eef1;
    line-height:1;
}

.dg-icon svg{
    width:19px;
    height:19px;
    display:block;
    fill:none;
    stroke:currentColor;
    stroke-width:1.9;
    stroke-linecap:round;
    stroke-linejoin:round;
}

.dg-label{
    display:block;

    width:100%;
    min-width:0;

    margin:0;
    padding:0;

    justify-self:start;
    align-self:center;

    text-align:left !important;
    white-space:nowrap;

    line-height:1;
}



.dg-add .dg-icon{
    color:#ffffff;
}

.dg-add .dg-icon svg{
    width:21px;
    height:21px;
    stroke-width:2.2;
}

.dg-add{
    border-color:#b98b25;
    background:linear-gradient(180deg,#66501d,#352a13);
}


/* ============================================================
   DREAMGRID ICON COLOUR PALETTE V1
   ============================================================ */

/* Details */
.dg-buttons > button:nth-of-type(1) .dg-icon{
    color:#42a5f5;
}

/* Icons */
.dg-buttons > button:nth-of-type(2) .dg-icon{
    color:#f7c948;
}

/* Users */
.dg-buttons > button:nth-of-type(3) .dg-icon{
    color:#55c979;
}

/* Avatars */
.dg-buttons > button:nth-of-type(4) .dg-icon{
    color:#b875e8;
}

/* Email */
.dg-buttons > button:nth-of-type(5) .dg-icon{
    color:#ff7b62;
}

/* Run All */
.dg-buttons > button:nth-of-type(6) .dg-icon{
    color:#45d06f;
}

/* Stop All */
.dg-buttons > button:nth-of-type(7) .dg-icon{
    color:#ff5d62;
}

/* Map */
.dg-buttons > button:nth-of-type(8) .dg-icon{
    color:#35c4e8;
}

/* Visitors */
.dg-buttons > button:nth-of-type(9) .dg-icon{
    color:#ff9f43;
}

/* Pause */
.dg-buttons > button:nth-of-type(10) .dg-icon{
    color:#ffd44f;
}

/* Add */
.dg-buttons > .dg-add .dg-icon{
    color:#fff5cf;
}

/* Export */
.dg-buttons > button:nth-of-type(11) .dg-icon{
    color:#39d0c5;
}

/* Refresh */
.dg-buttons > a.dg-button-link:not(.dg-add) .dg-icon{
    color:#55c7ff;
}

/* Restart */
.dg-buttons > button:nth-of-type(12) .dg-icon{
    color:#ffae42;
}

/* Import INI */
.dg-buttons > button:nth-of-type(13) .dg-icon{
    color:#9b7cff;
}


/* Give icons a very small coloured glow */

.dg-buttons .dg-icon{
    filter:
        drop-shadow(
            0 0 3px
            currentColor
        );
}


/* Keep disabled controls colourful but clearly disabled */

.dg-button:disabled .dg-icon{
    opacity:.82;
}


/* END DREAMGRID ICON COLOUR PALETTE V1 */

.dg-searchbar{
    grid-area:search;
    width:100%;
    min-width:0;
    display:block;
    margin:0;
    padding:0;
    align-self:start;
}

.dg-searchbar form{
    width:100%;
    display:grid;
    grid-template-columns:minmax(0,1fr) 88px;
    gap:6px;
    align-items:center;
    margin:0;
}

.dg-searchbar input{
    width:100%;
    min-height:30px;
    padding:4px 9px;
    border:1px solid rgba(255,255,255,.18);
    border-radius:4px;
    background:#080d10;
    color:#fff;
    font-size:11px;
}

.dg-searchbar button{
    min-height:30px;
    padding:0 12px;
    border:1px solid rgba(255,255,255,.17);
    border-radius:4px;
    color:#fff;
    background:#253138;
    font-size:10px;
    font-weight:800;
}

/* ---------------------------------------------------------
   DREAMGRID REGION TABLE
   --------------------------------------------------------- */

.dg-table-frame{
    width:100%;
    max-width:100%;
    min-width:0;
    min-height:430px;
    overflow:hidden;
    border:1px solid rgba(255,255,255,.18);
    background:#0b1013;
}

.dg-table{
    width:100%;
    min-width:0;
    max-width:100%;
    border-collapse:collapse;
    table-layout:fixed;
    font-family:Arial,Helvetica,sans-serif;
    font-size:10px;
    color:#e7ecee;
}

.dg-table th{
    position:sticky;
    top:0;
    z-index:3;
    height:34px;
    padding:3px 3px;
    border-right:1px solid #637079;
    border-bottom:1px solid #637079;
    color:#f0f3f4;
    background:linear-gradient(180deg,#39454b,#242d32);
    text-align:left;
    white-space:normal;
    font-weight:700;

    overflow:hidden;

    text-overflow:clip;

    line-height:1.08;

    font-size:8px;

    vertical-align:middle;

    box-sizing:border-box;
}

.dg-table td{
    height:28px;
    padding:3px 4px;
    border-right:1px solid rgba(131,147,155,.40);
    border-bottom:1px solid rgba(131,147,155,.38);
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    background:#11181c;

    box-sizing:border-box;
}

.dg-table tbody tr:nth-child(even) td{
    background:#182126;
}

.dg-table tbody tr:hover td{
    background:#27363e;
}

.dg-table tbody tr.dg-selected td{
    background:#1476b6 !important;
    color:#fff;
}

.dg-table tbody tr{
    cursor:pointer;
    outline:none;
}

.dg-center{
    text-align:center !important;
}

.dg-enable-box{
    pointer-events:auto;
    cursor:pointer;
    accent-color:#4fc978;
}

.dg-state-running{
    color:#b9f4c7;
}

.dg-state-stopped{
    color:#ffd790;
}

.dg-state-disabled{
    color:#d7d7d7;
}

.dg-state-ini{
    color:#ffb7a9;
}

.dg-region-name-cell{
    cursor:pointer;
}

.dg-region-name-link{
    font-weight:600;
}

.dg-region-name-cell:hover .dg-region-name-link{
    text-decoration:underline;
    color:#ffffff;
}
.dg-region-dot{
    display:inline-block;
    width:8px;
    height:8px;
    margin-right:5px;
    border-radius:50%;
    vertical-align:middle;
    background:#69d786;
    box-shadow:0 0 4px rgba(105,215,134,.5);
}

.dg-region-dot.stopped{
    background:#d9aa3e;
}

.dg-region-dot.disabled{
    background:#7f8b90;
}

.dg-region-dot.ini{
    background:#d36f55;
}

/* ---------------------------------------------------------
   REGION CONTROLS POPUP
   --------------------------------------------------------- */

.dg-modal-backdrop{
    position:fixed;
    inset:0;
    z-index:9998;
    display:none;
    background:rgba(0,0,0,.42);
}

.dg-modal-backdrop.open{
    display:block;
}

.dg-region-dialog{
    position:fixed;
    z-index:9999;
    top:50%;
    left:50%;
    width:min(390px,calc(100% - 28px));
    transform:translate(-50%,-50%);
    display:none;
    padding:9px;
    border:1px solid #56636a;
    border-radius:7px;
    background:#171d20;
    color:#f4f6f7;
    box-shadow:0 18px 55px rgba(0,0,0,.65);
}

.dg-region-dialog.open{
    display:block;
}

.dg-dialog-title{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    min-height:31px;
    padding:0 4px 7px;
    font-size:12px;
    font-weight:700;
}

.dg-dialog-close{
    width:26px;
    height:26px;
    border:1px solid rgba(255,255,255,.14);
    border-radius:4px;
    background:#283237;
    color:#fff;
    cursor:pointer;
}

.dg-dialog-group-title{
    margin:2px 0 7px;
    padding:6px 7px;
    border:1px solid rgba(255,255,255,.10);
    background:#20282c;
    font-size:11px;
    font-weight:700;
}

.dg-dialog-buttons{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:6px;
}

.dg-control-btn{
    min-height:34px;
    display:flex;
    align-items:center;
    gap:8px;
    padding:0 9px;
    border:1px solid rgba(255,255,255,.17);
    border-radius:4px;
    color:#f2f4f5;
    background:linear-gradient(180deg,#303a40,#1f282c);
    font-size:11px;
    font-weight:700;
    text-align:left;
}

.dg-control-btn:disabled{
    opacity:.46;
}

.dg-control-start{
    border-color:#317bb0;
}

.dg-control-stop{
    border-color:#9b523f;
}


/* ==========================================================
   REGION CONTROL SVG ICONS V1
   ========================================================== */

.dg-control-btn{
    min-height:40px;
    gap:16px;
    padding:0 14px;
}

.dg-control-icon{
    width:30px;
    min-width:30px;
    height:30px;

    display:inline-flex;
    align-items:center;
    justify-content:center;

    filter:drop-shadow(0 0 4px currentColor);
}

.dg-control-icon svg{
    width:24px;
    height:24px;

    display:block;

    fill:none;
    stroke:currentColor;
    stroke-width:2;
    stroke-linecap:round;
    stroke-linejoin:round;
}

.dg-control-label{
    display:inline-block;
}


/* individual colours */

.dg-ci-console{
    color:#42a5f5;
}

.dg-ci-statistics{
    color:#35d1e8;
}

.dg-ci-start{
    color:#4ee378;
}

.dg-ci-load{
    color:#b77cff;
}

.dg-ci-restart{
    color:#ffad42;
}

.dg-ci-save{
    color:#d57cff;
}

.dg-ci-stop{
    color:#ff5c64;
}

.dg-ci-log{
    color:#ffd34e;
}

.dg-ci-teleport{
    color:#b378ff;
}

.dg-ci-map{
    color:#39d2c3;
}

.dg-ci-edit{
    color:#ff9d43;
}

.dg-ci-alert{
    color:#ff718f;
}

.dg-ci-freeze{
    color:#55d7ff;
}

.dg-ci-thaw{
    color:#ffba45;
}


/* Disabled buttons stay disabled but icons remain visible */

.dg-control-btn:disabled{
    opacity:.62;
}

.dg-control-btn:disabled .dg-control-icon{
    opacity:.85;
}


/* Active Start/Stop slightly brighter */

.dg-control-start:not(:disabled) .dg-control-icon,
.dg-control-stop:not(:disabled) .dg-control-icon{
    filter:
        drop-shadow(0 0 6px currentColor)
        drop-shadow(0 0 10px currentColor);
}


/* END REGION CONTROL SVG ICONS V1 */


/* ==========================================================
   REGION CONTROL ICON ALIGNMENT V1
   ========================================================== */

.dg-dialog-buttons .dg-control-btn{
    display:grid !important;
    grid-template-columns:32px minmax(0,1fr);
    column-gap:16px;

    align-items:center;
    justify-content:stretch;

    padding-left:18px;
    padding-right:14px;

    text-align:left;
}

.dg-dialog-buttons .dg-control-icon{
    width:32px;
    min-width:32px;
    height:30px;

    display:flex;
    align-items:center;
    justify-content:center;

    margin:0;
}

.dg-dialog-buttons .dg-control-label{
    width:100%;
    margin:0;
    text-align:left;
}

/* END REGION CONTROL ICON ALIGNMENT V1 */

/* ==========================================================
   DREAMGRID REGION MANAGER VIEW FOUNDATION V4
   ========================================================== */

.dg-manager-view{
    display:none;
    width:100%;
    min-width:0;
}

.dg-manager-view.active{
    display:block;
}

#ag-region-manager-page .dg-view-button.dg-view-active{
    border-color:#c18f2c !important;
    color:#ffe3a0 !important;

    background:
        linear-gradient(
            180deg,
            #4a402d 0,
            #4a402d 7px,
            #211b10 8px,
            #15110b 100%
        ) !important;

    box-shadow:
        inset 0 0 0 1px #59451a,
        inset 0 1px 0 #81704b !important;
}

.dg-icon-view{
    width:100%;
    min-height:430px;

    display:flex;
    align-content:flex-start;
    align-items:flex-start;
    flex-wrap:wrap;

    gap:8px 18px;

    padding:10px;

    overflow:auto;

    border:1px solid #665427;

    background:#050706;
}

.dg-icon-region{
    min-width:145px;
    height:32px;

    display:flex;
    align-items:center;

    gap:7px;

    margin:0;
    padding:3px 8px;

    color:#dedbd2;

    border:1px solid transparent;
    border-radius:4px;

    background:transparent;

    cursor:pointer;

    font-family:"Segoe UI",Arial,Helvetica,sans-serif;
    font-size:10px;

    text-align:left;
}

.dg-icon-region:hover{
    border-color:#665427;
    color:#ffe3a0;
    background:#111310;
}

.dg-icon-region:focus{
    outline:none;
    border-color:#c18f2c;
}

.dg-icon-region-name{
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.dg-coming-view{
    min-height:430px;

    display:flex;
    align-items:center;
    justify-content:center;

    color:#8f918b;

    border:1px solid #665427;

    background:#050706;

    font-size:12px;
    font-weight:700;
    letter-spacing:.12em;
}

/* END DREAMGRID REGION MANAGER VIEW FOUNDATION V4 */

.dg-dialog-note{
    margin-top:8px;
    color:#9dabb1;
    font-size:10px;
    line-height:1.4;
}

@media(max-width:1000px){
    .dg-toolbar{
        grid-template-columns:1fr;
    }

    .dg-buttons{
        grid-template-columns:repeat(3,minmax(105px,1fr));
    }
}


/* ==========================================================
   AUSTRALIA REGION MANAGER CHARCOAL GOLD THEME V1

   Canonical Region Manager visual theme.

   CHARCOAL:
       #070907
       #080a08
       #0d0e0b
       #10120f
       #18170f

   GOLD:
       #5b4820
       #6e5723
       #8c6a26
       #b17b13
       #e1b33b
       #f0ca62

   Live green/cyan telemetry colours are deliberately retained.
   ========================================================== */


/* ----------------------------------------------------------
   PAGE / PRIMARY SURFACES
   ---------------------------------------------------------- */

#ag-region-manager-page{
    color:#dedbd2 !important;
}


#ag-region-manager-page > .ag-shell{
    margin:0 !important;
    min-width:0 !important;
    max-width:none !important;
    width:100% !important;
    box-sizing:border-box !important;
    background:
        linear-gradient(
            180deg,
            #10120f 0%,
            #080a08 42%,
            #070907 100%
        ) !important;

    border-color:#5b4820 !important;

    box-shadow:
        inset 0 1px 0 rgba(255,241,177,.04),
        0 12px 28px rgba(0,0,0,.42) !important;
}


#ag-region-manager-page .dg-statusline,
#ag-region-manager-page .dg-toolbar,
#ag-region-manager-page #rm-live-ops{
    background:#080a08 !important;

    border-color:#5b4820 !important;

    color:#dedbd2 !important;

    box-shadow:
        inset 0 1px 0 rgba(255,241,177,.035),
        0 4px 14px rgba(0,0,0,.22) !important;
}


#ag-region-manager-page .dg-statusline strong{
    color:#f0ca62 !important;
}


/* ----------------------------------------------------------
   OLD REGION MANAGER TOOLBAR BUTTONS
   ---------------------------------------------------------- */

#ag-region-manager-page .dg-button,
#ag-region-manager-page .dg-button-link,
#ag-region-manager-page .dg-searchbar button,
#ag-region-manager-page .dg-control-btn{
    color:#dedbd2 !important;

    border:
        1px solid #5b4820 !important;

    border-radius:
        5px !important;

    background:
        linear-gradient(
            180deg,
            #18170f 0%,
            #10120f 52%,
            #0d0e0b 100%
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,241,177,.08),
        0 2px 5px rgba(0,0,0,.32) !important;

    text-shadow:none !important;
}


#ag-region-manager-page .dg-button-link:hover,
#ag-region-manager-page .dg-button:not(:disabled):hover,
#ag-region-manager-page .dg-searchbar button:hover,
#ag-region-manager-page .dg-control-btn:not(:disabled):hover{
    color:#fff4cf !important;

    border-color:#8c6a26 !important;

    background:
        linear-gradient(
            180deg,
            #252016 0%,
            #18170f 52%,
            #10120f 100%
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,241,177,.12),
        0 0 8px rgba(225,179,59,.16) !important;
}


#ag-region-manager-page .dg-add{
    color:#dedbd2 !important;

    border-color:#5b4820 !important;

    background:
        linear-gradient(
            180deg,
            #18170f 0%,
            #10120f 52%,
            #0d0e0b 100%
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,241,177,.08),
        0 2px 5px rgba(0,0,0,.32) !important;
}


#ag-region-manager-page .dg-add:hover{
    color:#fff4cf !important;

    border-color:#8c6a26 !important;

    background:
        linear-gradient(
            180deg,
            #252016 0%,
            #18170f 52%,
            #10120f 100%
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,241,177,.12),
        0 0 8px rgba(225,179,59,.16) !important;
}


#ag-region-manager-page .dg-add .dg-icon{
    color:inherit !important;
}


#ag-region-manager-page .dg-button:disabled,
#ag-region-manager-page .dg-control-btn:disabled{
    color:#56574f !important;

    border-color:#252820 !important;

    background:#0d0e0b !important;

    opacity:.52 !important;

    box-shadow:none !important;
}


/* ----------------------------------------------------------
   INPUTS / SEARCH
   ---------------------------------------------------------- */

#ag-region-manager-page .dg-searchbar input,
#ag-region-manager-page .rm-search{
    background:#080a08 !important;

    color:#dedbd2 !important;

    border:
        1px solid #56574f !important;

    border-radius:
        5px !important;

    box-shadow:
        inset 0 1px 4px rgba(0,0,0,.52) !important;
}


#ag-region-manager-page .dg-searchbar input:focus,
#ag-region-manager-page .rm-search:focus{
    outline:none !important;

    border-color:#d9ae48 !important;

    box-shadow:
        0 0 0 1px rgba(225,179,59,.22),
        inset 0 1px 4px rgba(0,0,0,.52) !important;
}


/* ----------------------------------------------------------
   LIVE OPERATIONS MAIN AREA
   ---------------------------------------------------------- */

#ag-region-manager-page #rm-live-ops{
    border:
        1px solid #5b4820 !important;

    border-radius:
        7px !important;
}


#ag-region-manager-page .rm-live-toolbar{
    background:#080a08 !important;
}


#ag-region-manager-page .rm-summary-card{
    background:
        linear-gradient(
            180deg,
            #10120f 0%,
            #080a08 100%
        ) !important;

    border:
        1px solid #6e5723 !important;

    border-radius:
        6px !important;

    box-shadow:
        inset 0 1px 0 rgba(255,241,177,.05),
        0 3px 8px rgba(0,0,0,.28) !important;
}


#ag-region-manager-page .rm-summary-label{
    color:#aaa186 !important;
}


/*
   DO NOT override:
       .rm-summary-value.green
       .rm-summary-value.cyan
       .rm-summary-value.red

   Those remain live telemetry/status colours.
*/


/* ----------------------------------------------------------
   LIVE OPS FILTER BUTTONS
   ---------------------------------------------------------- */

#ag-region-manager-page .rm-filter{
    color:#dedbd2 !important;

    border:
        1px solid #5b4820 !important;

    background:
        linear-gradient(
            180deg,
            #18170f,
            #0d0e0b
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,241,177,.06) !important;
}


#ag-region-manager-page .rm-filter:hover{
    border-color:#8c6a26 !important;

    color:#fff4cf !important;
}


#ag-region-manager-page .rm-filter.active{
    color:#171006 !important;

    border-color:#e0b448 !important;

    background:
        linear-gradient(
            180deg,
            #e1b33b 0%,
            #b17b13 52%,
            #825306 100%
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,241,177,.45),
        0 0 8px rgba(225,179,59,.18) !important;
}


/* ----------------------------------------------------------
   REGION CARDS
   ---------------------------------------------------------- */

#ag-region-manager-page .rm-region-card{
    background:
        linear-gradient(
            180deg,
            #10120f 0%,
            #080a08 38%,
            #070907 100%
        ) !important;

    border:
        1px solid #5b4820 !important;

    border-radius:
        6px !important;

    box-shadow:
        inset 0 1px 0 rgba(255,241,177,.025),
        0 4px 10px rgba(0,0,0,.25) !important;
}


#ag-region-manager-page .rm-region-card:hover{
    border-color:#8c6a26 !important;

    box-shadow:
        inset 0 1px 0 rgba(255,241,177,.05),
        0 0 10px rgba(225,179,59,.12),
        0 4px 12px rgba(0,0,0,.30) !important;
}


/*
   Running state is shown by its live green indicator,
   not by turning the complete panel green/cyan.
*/

#ag-region-manager-page .rm-region-card.running{
    border-color:#5b4820 !important;
}


#ag-region-manager-page .rm-region-card.stopped{
    border-color:#6e5723 !important;
}


#ag-region-manager-page .rm-region-card.disabled{
    border-color:#252820 !important;
}


#ag-region-manager-page .rm-region-card.alert{
    border-color:#8a3f3f !important;
}


#ag-region-manager-page .rm-card-head{
    background:#10120f !important;

    border-bottom:
        1px solid #5b4820 !important;
}


#ag-region-manager-page .rm-card-name{
    color:#dedbd2 !important;
}


#ag-region-manager-page .rm-card-metrics{
    background:#070907 !important;
}


#ag-region-manager-page .rm-card-metrics > *{
    border-color:#252820 !important;
}


#ag-region-manager-page .rm-card-actions{
    border-top:
        1px solid #5b4820 !important;

    background:#080a08 !important;
}


#ag-region-manager-page .rm-card-footer{
    border-top:
        1px solid #5b4820 !important;

    background:#070907 !important;
}


/* ----------------------------------------------------------
   LIVE REGION CARD BUTTONS
   ---------------------------------------------------------- */

#ag-region-manager-page .rm-card-actions button,
#ag-region-manager-page .rm-card-actions a{
    color:#dedbd2 !important;

    border:
        1px solid #5b4820 !important;

    border-radius:
        5px !important;

    background:
        linear-gradient(
            180deg,
            #18170f 0%,
            #10120f 52%,
            #0d0e0b 100%
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,241,177,.07),
        0 2px 5px rgba(0,0,0,.30) !important;

    text-shadow:none !important;
}


#ag-region-manager-page .rm-card-actions button:not(:disabled):hover,
#ag-region-manager-page .rm-card-actions a:hover{
    color:#fff4cf !important;

    border-color:#8c6a26 !important;

    background:
        linear-gradient(
            180deg,
            #252016 0%,
            #18170f 52%,
            #10120f 100%
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,241,177,.12),
        0 0 7px rgba(225,179,59,.14) !important;
}


#ag-region-manager-page .rm-card-actions button:disabled{
    color:#56574f !important;

    border-color:#252820 !important;

    background:#0d0e0b !important;

    opacity:.50 !important;

    box-shadow:none !important;
}


/* ----------------------------------------------------------
   OLD REGION TABLE
   ---------------------------------------------------------- */

#ag-region-manager-page .dg-table-frame{
    background:#070907 !important;

    border:
        1px solid #5b4820 !important;
}


#ag-region-manager-page .dg-table{
    color:#dedbd2 !important;
}


#ag-region-manager-page .dg-table th{
    color:#f0ca62 !important;

    background:
        linear-gradient(
            180deg,
            #18170f,
            #10120f
        ) !important;

    border-color:#5b4820 !important;
}


#ag-region-manager-page .dg-table td{
    background:#080a08 !important;

    border-color:#252820 !important;
}


#ag-region-manager-page .dg-table tbody tr:nth-child(even) td{
    background:#0d0e0b !important;
}


#ag-region-manager-page .dg-table tbody tr:hover td{
    background:#18170f !important;
}


#ag-region-manager-page .dg-table tbody tr.dg-selected td{
    color:#fff4cf !important;

    background:#29220e !important;

    box-shadow:
        inset 3px 0 0 #d9ae48 !important;
}


/* ----------------------------------------------------------
   REGION CONTROL DIALOG
   ---------------------------------------------------------- */

#ag-region-manager-page .dg-region-dialog{
    color:#dedbd2 !important;

    background:#10120f !important;

    border:
        1px solid #8c6a26 !important;

    border-radius:
        6px !important;

    box-shadow:
        0 18px 55px rgba(0,0,0,.70) !important;
}


#ag-region-manager-page .dg-dialog-group-title{
    color:#f0ca62 !important;

    background:#18170f !important;

    border:
        1px solid #5b4820 !important;
}


#ag-region-manager-page .dg-dialog-close{
    color:#dedbd2 !important;

    background:#0d0e0b !important;

    border:
        1px solid #5b4820 !important;
}


#ag-region-manager-page .dg-dialog-close:hover{
    color:#171006 !important;

    background:#d9ae48 !important;

    border-color:#f0ca62 !important;
}


/* ----------------------------------------------------------
   KEEP TELEMETRY STATUS COLOURS
   ---------------------------------------------------------- */

#ag-region-manager-page .rm-card-live{
    /* live colour remains owned by Live Ops telemetry */
}


#ag-region-manager-page .dg-region-dot{
    /* running/stopped state dots remain status coloured */
}


/* END AUSTRALIA REGION MANAGER CHARCOAL GOLD THEME V1 */


/* DREAMGRID REGION DETAILS CONTROL FIT START */

/*
 * Native DreamGrid-style compact row controls.
 * Prevent Control Center global form styling from turning
 * the Region Details combo boxes into oversized buttons.
 */

#ag-region-manager-page
#dgRegionTable
select.dg-region-setting-control{
    display:block !important;

    box-sizing:border-box !important;

    width:calc(100% - 6px) !important;
    max-width:calc(100% - 6px) !important;
    min-width:0 !important;

    height:24px !important;
    min-height:24px !important;
    max-height:24px !important;

    margin:0 3px !important;

    padding:
        0
        18px
        0
        6px !important;

    font-family:
        Arial,
        Helvetica,
        sans-serif !important;

    font-size:9px !important;
    line-height:22px !important;

    border-radius:4px !important;

    vertical-align:middle !important;
}


/*
 * Keep row height compact even when a select is present.
 */

#ag-region-manager-page
#dgRegionTable
tbody td{
    height:30px !important;
    min-height:30px !important;
    max-height:30px !important;

    padding:
        2px
        3px !important;

    vertical-align:middle !important;
}


/*
 * These three columns contain combo boxes.
 * Give their cells no extra internal horizontal squeeze.
 */

#ag-region-manager-page
#dgRegionTable
th:nth-child(6),
#ag-region-manager-page
#dgRegionTable
td:nth-child(6),

#ag-region-manager-page
#dgRegionTable
th:nth-child(17),
#ag-region-manager-page
#dgRegionTable
td:nth-child(17),

#ag-region-manager-page
#dgRegionTable
th:nth-child(18),
#ag-region-manager-page
#dgRegionTable
td:nth-child(18){
    padding-left:2px !important;
    padding-right:2px !important;
}


/*
 * RAM and OpensimWorld Key remain readable.
 */

#ag-region-manager-page
#dgRegionTable
th:nth-child(7),
#ag-region-manager-page
#dgRegionTable
td:nth-child(7){
    padding-left:5px !important;
    padding-right:4px !important;
}

#ag-region-manager-page
#dgRegionTable
th:nth-child(29),
#ag-region-manager-page
#dgRegionTable
td:nth-child(29){
    padding-left:5px !important;
    padding-right:4px !important;
}


/*
 * Long header labels may wrap cleanly.
 */

#ag-region-manager-page
#dgRegionTable
thead th{
    white-space:normal !important;

    overflow:hidden !important;

    line-height:1.05 !important;

    vertical-align:middle !important;
}


/* DREAMGRID REGION DETAILS CONTROL FIT END */


/* ==========================================================
   DREAMGRID REGION MANAGER USERS VIEW V2
   ========================================================== */

#ag-region-manager-page .dg-users-table-frame{
    width:100%;
    max-width:100%;
    min-width:0;

    overflow:hidden;

    border:1px solid #665427;

    background:#050706;
}


#ag-region-manager-page .dg-users-table{
    width:100%;
    max-width:100%;
    min-width:0;

    border-collapse:collapse;
    table-layout:fixed;

    color:#dedbd2;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size:9px;
}


#ag-region-manager-page .dg-users-table th{
    height:34px;

    padding:4px 4px;

    overflow:hidden;

    border-right:1px solid #55451f;
    border-bottom:1px solid #8c6b29;

    background:#17150e;

    color:#e2b64f;

    font-size:8px;
    font-weight:800;

    line-height:1.05;

    text-align:left;
    vertical-align:middle;

    white-space:normal;
}


#ag-region-manager-page .dg-users-table td{
    height:29px;

    padding:3px 5px;

    overflow:hidden;

    border-right:1px solid #252820;
    border-bottom:1px solid #20231d;

    background:#080a08;

    color:#d7d7d1;

    text-overflow:ellipsis;
    white-space:nowrap;

    vertical-align:middle;
}


#ag-region-manager-page
.dg-users-table
tbody tr:nth-child(even) td{
    background:#0d0e0b;
}


#ag-region-manager-page
.dg-users-table
tbody tr:hover td{
    background:#18170f;

    color:#fff4cf;
}


#ag-region-manager-page .dg-user-row{
    cursor:pointer;
}


#ag-region-manager-page .dg-user-name-cell{
    color:#f1d071;

    font-weight:800;

    cursor:pointer;

    text-decoration:underline;
    text-decoration-color:rgba(226,182,79,.45);
    text-underline-offset:2px;
}


#ag-region-manager-page .dg-user-uuid{
    color:#989d97;

    font-family:
        Consolas,
        "Courier New",
        monospace;

    font-size:8px;
}


#ag-region-manager-page .dg-number{
    text-align:right;

    font-variant-numeric:
        tabular-nums;
}


#ag-region-manager-page .dg-center{
    text-align:center;
}


#ag-region-manager-page .dg-user-select-cell,
#ag-region-manager-page .dg-users-select-head{
    padding-left:0 !important;
    padding-right:0 !important;

    text-align:center;
}


#ag-region-manager-page .dg-user-select{
    width:12px !important;
    height:12px !important;

    min-width:12px !important;
    min-height:12px !important;

    margin:0 !important;

    vertical-align:middle;
}


#ag-region-manager-page .dg-users-empty,
#ag-region-manager-page .dg-users-warning{
    padding:18px;

    color:#8f918b;

    text-align:center;
}


#ag-region-manager-page .dg-users-warning{
    margin-bottom:7px;

    border:1px solid #665427;

    background:#111310;
}

/* END DREAMGRID REGION MANAGER USERS VIEW V2 */


/* ==========================================================
   DREAMGRID SERVER EDIT USER V3
   ========================================================== */

#ag-region-manager-page .dg-user-name-link{
    display:block !important;

    width:100% !important;
    max-width:100% !important;

    margin:0 !important;
    padding:0 !important;

    border:0 !important;

    background:transparent !important;

    color:#f1d071 !important;

    font-family:
        Arial,
        Helvetica,
        sans-serif !important;

    font-size:9px !important;
    font-weight:800 !important;

    line-height:22px !important;

    text-align:left !important;

    text-decoration:none !important;

    white-space:nowrap !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;

    cursor:pointer !important;
}


#ag-region-manager-page .dg-user-name-link:hover{
    color:#ffe3a0 !important;

    text-decoration:underline !important;
    text-decoration-color:#e2b64f !important;
    text-underline-offset:2px !important;
}


#ag-region-manager-page .dg-user-modal-backdrop{
    display:none;

    position:fixed;
    inset:0;

    z-index:2147483000;

    background:
        rgba(0,0,0,.78);
}


#ag-region-manager-page .dg-user-modal-backdrop.open{
    display:block !important;
}


#ag-region-manager-page .dg-user-dialog{
    display:none;

    position:fixed;

    left:50%;
    top:50%;

    z-index:2147483001;

    box-sizing:border-box;

    width:min(
        720px,
        calc(100vw - 50px)
    );

    max-height:none !important;

    transform:
        translate(-50%,-50%);

    overflow:visible !important;

    border:
        1px solid
        #a87b25;

    border-radius:7px;

    background:
        linear-gradient(
            180deg,
            #111310 0%,
            #090b09 100%
        );

    box-shadow:
        0 18px 55px
        rgba(0,0,0,.85);

    color:#dedbd2;
}


#ag-region-manager-page .dg-user-dialog.open{
    display:block !important;

    visibility:visible !important;

    opacity:1 !important;
}


#ag-region-manager-page .dg-user-dialog-titlebar{
    height:34px;
    min-height:34px;

    display:flex;

    align-items:center;
    justify-content:space-between;

    box-sizing:border-box;

    padding:
        0
        8px
        0
        12px;

    border-bottom:
        1px solid
        #665427;

    background:
        linear-gradient(
            180deg,
            #272317 0%,
            #14130e 100%
        );

    color:#e2b64f;

    font-size:11px;
    font-weight:800;
}


#ag-region-manager-page .dg-user-dialog-close{
    display:flex !important;

    align-items:center;
    justify-content:center;

    box-sizing:border-box;

    width:26px;
    height:26px;

    padding:0;

    border:
        1px solid
        #484a44;

    border-radius:4px;

    background:#111310;

    color:#d7d7d1 !important;

    font-size:16px;

    line-height:1;

    text-decoration:none !important;
}


#ag-region-manager-page .dg-user-dialog-close:hover{
    border-color:#c18f2c;

    color:#ffe3a0 !important;
}


#ag-region-manager-page .dg-user-dialog-body{
    box-sizing:border-box;

    padding:
        9px
        12px
        10px;

    overflow:visible !important;
}


#ag-region-manager-page .dg-user-dialog-message{
    display:none;

    margin-bottom:6px;

    padding:
        6px
        8px;

    border:
        1px solid
        #665427;

    background:#0d0f0d;

    color:#dedbd2;

    font-size:9px;
}


#ag-region-manager-page .dg-user-dialog-message.show{
    display:block;
}


#ag-region-manager-page .dg-user-dialog-message.error{
    border-color:#7c3535;

    color:#f1aaaa;
}


#ag-region-manager-page .dg-user-dialog-message.success{
    border-color:#416f3d;

    color:#9add96;
}


#ag-region-manager-page .dg-user-form-grid{
    display:grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap:
        6px
        10px;
}


#ag-region-manager-page .dg-user-form-grid label{
    display:flex;

    flex-direction:column;

    gap:2px;

    min-width:0;
}


#ag-region-manager-page .dg-user-form-grid label > span{
    color:#bba66d;

    font-size:8px;
    font-weight:700;

    line-height:11px;
}


#ag-region-manager-page .dg-user-field-wide{
    grid-column:
        1 / -1;
}


#ag-region-manager-page .dg-user-form-grid input{
    box-sizing:border-box;

    width:100%;
    height:27px;
    min-height:27px;

    padding:
        2px
        7px;

    border:
        1px solid
        #484a44;

    border-radius:4px;

    background:#090b09;

    color:#dedbd2;

    font-size:9px;

    line-height:21px;
}


#ag-region-manager-page .dg-user-form-grid input:focus{
    outline:none;

    border-color:#c18f2c;

    box-shadow:
        inset 0 0 0 1px
        #59451a;
}


#ag-region-manager-page .dg-user-form-grid input[readonly],
#ag-region-manager-page .dg-user-form-grid input[disabled]{
    color:#81867f;

    background:#070807;
}


#ag-region-manager-page .dg-user-auto-row{
    margin-top:7px;

    padding:
        6px
        8px;

    min-height:26px;

    box-sizing:border-box;

    border:
        1px solid
        #3d3f39;

    background:#0b0d0b;
}


#ag-region-manager-page .dg-user-auto-row label{
    display:flex;

    align-items:center;

    gap:7px;

    cursor:pointer;

    font-size:9px;

    line-height:14px;
}


#ag-region-manager-page .dg-user-auto-row input{
    width:13px !important;
    height:13px !important;

    min-width:13px !important;
    min-height:13px !important;

    margin:0 !important;
}


#ag-region-manager-page .dg-user-level-box{
    margin-top:7px;

    padding:
        6px
        8px
        7px;

    display:grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap:
        4px
        12px;

    border:
        1px solid
        #665427;
}


#ag-region-manager-page .dg-user-level-box legend{
    padding:
        0
        5px;

    color:#e2b64f;

    font-size:8px;
    font-weight:800;

    line-height:10px;
}


#ag-region-manager-page .dg-user-level-box label{
    display:flex;

    align-items:center;

    gap:6px;

    min-height:18px;

    font-size:9px;

    line-height:14px;
}


#ag-region-manager-page .dg-user-level-box input{
    width:13px !important;
    height:13px !important;

    min-width:13px !important;
    min-height:13px !important;

    margin:0 !important;
}


#ag-region-manager-page .dg-user-dialog-actions{
    margin-top:8px;

    display:flex;

    justify-content:flex-end;

    gap:7px;
}


#ag-region-manager-page .dg-user-action-button{
    box-sizing:border-box;

    min-width:84px;
    height:27px;

    display:inline-flex !important;

    align-items:center;
    justify-content:center;

    padding:
        0
        12px;

    border:
        1px solid
        #c18f2c;

    border-radius:4px;

    background:
        linear-gradient(
            180deg,
            #4a402d 0%,
            #211b10 100%
        );

    color:#ffe3a0 !important;

    cursor:pointer;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size:8px;
    font-weight:800;

    text-decoration:none !important;
}


#ag-region-manager-page .dg-user-action-button.secondary{
    border-color:#484a44;

    background:
        linear-gradient(
            180deg,
            #292a25 0%,
            #111310 100%
        );

    color:#d7d7d1 !important;
}


@media(max-width:700px){

    #ag-region-manager-page .dg-user-dialog{
        width:
            calc(100vw - 24px);
    }

    #ag-region-manager-page .dg-user-form-grid,
    #ag-region-manager-page .dg-user-level-box{
        grid-template-columns:1fr;
    }
}

/* END DREAMGRID SERVER EDIT USER V3 */











/* DREAMGRID NATIVE VISITORS V2 START */

#ag-region-manager-page .dg-native-visitors-datebar{
    box-sizing:border-box !important;

    width:100% !important;

    margin:
        0
        0
        7px !important;

    padding:
        5px
        8px !important;

    border:
        1px solid
        #484a44 !important;

    background:
        linear-gradient(
            180deg,
            #171914 0%,
            #0d0f0d 100%
        ) !important;
}


#ag-region-manager-page #dgNativeVisitorsDateForm{
    display:flex !important;

    align-items:flex-end !important;

    gap:8px !important;

    margin:0 !important;

    padding:0 !important;
}


#ag-region-manager-page .dg-native-visitor-date{
    display:flex !important;

    flex-direction:column !important;

    gap:2px !important;

    min-width:150px !important;
}


#ag-region-manager-page .dg-native-visitor-date label{
    margin:0 !important;

    padding:0 !important;

    color:#e2b64f !important;

    font-family:
        Arial,
        Helvetica,
        sans-serif !important;

    font-size:8px !important;

    font-weight:800 !important;

    line-height:11px !important;
}


#ag-region-manager-page .dg-native-visitor-date input[type="date"]{
    box-sizing:border-box !important;

    width:150px !important;

    height:27px !important;

    margin:0 !important;

    padding:
        2px
        6px !important;

    border:
        1px solid
        #484a44 !important;

    border-radius:3px !important;

    background:#090b09 !important;

    color:#dedbd2 !important;

    font-family:
        Arial,
        Helvetica,
        sans-serif !important;

    font-size:9px !important;

    color-scheme:dark !important;
}


#ag-region-manager-page .dg-native-visitor-date input[type="date"]:focus{
    outline:none !important;

    border-color:#c18f2c !important;

    box-shadow:
        inset 0 0 0 1px
        #59451a !important;
}


#ag-region-manager-page .dg-native-visitor-apply{
    height:27px !important;

    min-width:70px !important;

    border:
        1px solid
        #484a44 !important;

    border-radius:3px !important;

    background:
        linear-gradient(
            180deg,
            #292a25 0,
            #20211d 7px,
            #111310 8px,
            #0d0f0d 100%
        ) !important;

    color:#d7d7d1 !important;

    font-size:8px !important;

    font-weight:800 !important;
}


#ag-region-manager-page .dg-native-visitors-table th,
#ag-region-manager-page .dg-native-visitors-table td{
    vertical-align:middle !important;
}


#ag-region-manager-page .dg-native-visitors-table th:last-child,
#ag-region-manager-page .dg-native-visitors-table td:last-child{
    text-align:left !important;
}


#ag-region-manager-page .dg-native-visits-count{
    font-weight:800 !important;
}

/* DREAMGRID NATIVE VISITORS V2 END */

</style>

<?php

$dgGuiEnabledCount = 0;
$dgGuiSmartCount   = 0;
$dgGuiArea         = 0;
$dgGuiRamMb        = 0.0;

foreach ($regions as $dgSummaryRegion) {

    $dgSummaryName =
        strtolower(
            trim(
                (string)(
                    $dgSummaryRegion['regionName'] ??
                    ''
                )
            )
        );

    $dgSummaryCell =
        $dreamGridRegions[$dgSummaryName] ??
        [];

    $dgEnabledHtml =
        (string)(
            $dgSummaryCell['Enabled'] ??
            ''
        );

    $dgSummaryLiveEnabled =
        stripos(
            $dgEnabledHtml,
            'checked'
        ) !== false;

    $dgSummaryBootEnabled =
        dgRegionBootEnabled(
            $dgSummaryRegion,
            $dgSummaryLiveEnabled
        );

    if ($dgSummaryBootEnabled) {
        $dgGuiEnabledCount++;
    }

    $dgSmart =
        trim(
            strip_tags(
                (string)(
                    $dgSummaryCell['SmartStart'] ??
                    ''
                )
            )
        );

    if (
        $dgSmart !== '' &&
        $dgSmart !== '-' &&
        strcasecmp($dgSmart, 'Off') !== 0
    ) {
        $dgGuiSmartCount++;
    }

    $dgSx =
        (int)(
            $dgSummaryRegion['sizeX'] ??
            256
        );

    $dgSy =
        (int)(
            $dgSummaryRegion['sizeY'] ??
            256
        );

    if ($dgSx > 0 && $dgSy > 0) {
        $dgGuiArea +=
            ($dgSx / 256) *
            ($dgSy / 256);
    }

    $dgRamText =
        trim(
            strip_tags(
                (string)(
                    $dgSummaryCell['Ram'] ??
                    ''
                )
            )
        );

    if (
        preg_match(
            '/([0-9.]+)\s*(GB|MB)/i',
            $dgRamText,
            $dgRamMatch
        )
    ) {

        $dgRamValue =
            (float)$dgRamMatch[1];

        if (
            strtoupper($dgRamMatch[2]) ===
            'GB'
        ) {
            $dgRamValue *= 1024;
        }

        $dgGuiRamMb +=
            $dgRamValue;
    }
}

?>

<div class="dg-statusline">

    <div>
        <strong id="dgRegionCount"><?=ag_h(count($regions))?></strong> Regions.
        <strong id="dgEnabledCount"><?=ag_h($dgGuiEnabledCount)?></strong> Enabled Regions.
        <strong id="dgSmartCount"><?=ag_h($dgGuiSmartCount)?></strong> Smart Start Regions.
        Total Area:
        <strong><?=ag_h(rtrim(rtrim(number_format($dgGuiArea,2,'.',''),'0'),'.'))?></strong>
        Regions.
        Total RAM Used:
        <strong><?=ag_h(number_format($dgGuiRamMb,1,'.',''))?> MB</strong>
    </div>

<div class="php-level">

    <strong>
        <?=($level >= 200 ? 'GRID OWNER' : 'MEMBER')?>
    </strong>

    USER LEVEL
    <?=ag_h($level)?>

</div>


</div>


<div class="dg-toolbar">

    <div class="dg-filters">

        <label class="dg-filter">
            <input
                type="radio"
                name="dg-filter"
                value="all"
                checked>
            All
        </label>

        <label class="dg-filter">
            <input
                type="radio"
                name="dg-filter"
                value="enabled">
            Enabled
        </label>

        <label class="dg-filter">
            <input
                type="radio"
                name="dg-filter"
                value="running">
            Running
        </label>

        <label class="dg-filter">
            <input
                type="radio"
                name="dg-filter"
                value="smart">
            Smart Start
        </label>

        <label class="dg-filter">
            <input
                type="radio"
                name="dg-filter"
                value="disabled">
            Disabled
        </label>

        <label class="dg-filter">
            <input
                type="radio"
                name="dg-filter"
                value="stopped">
            Stopped
        </label>

        <label class="dg-filter dg-selectall">
            <input
                id="dgSelectAll"
                type="checkbox">
            Select All/None
        </label>

    </div>


    <div class="dg-buttons">

    <button
        class="dg-button dg-view-button dg-view-active"
        type="button"
        id="dgDetailsButton">
        <?=ag_icon('details', null, 'dg-icon')?>
        <span class="dg-label">Details</span>
    </button>

    <button
        class="dg-button dg-view-button"
        type="button"
        id="dgIconsButton">
        <?=ag_icon('dashboard', null, 'dg-icon')?>
        <span class="dg-label">Icons</span>
    </button>

    <button
        class="dg-button dg-view-button"
        type="button"
        id="dgUsersButton">
        <?=ag_icon('users', null, 'dg-icon')?>
        <span class="dg-label">Users</span>
    </button>

    <button
        class="dg-button dg-view-button"
        type="button"
        id="dgAvatarsButton">
        <?=ag_icon('avatar', null, 'dg-icon')?>
        <span class="dg-label">Avatars</span>
    </button>

    <button
        class="dg-button"
        type="button"
        id="dgEmailButton"
        title="Email the checked users">
        <?=ag_icon('email', null, 'dg-icon')?>
        <span class="dg-label">Email</span>
    </button>


    <button
        class="dg-button"
        type="button"
        id="dgRunAllButton">
        <?=ag_icon('start', null, 'dg-icon')?>
        <span class="dg-label">Run All</span>
    </button>

    <button
        class="dg-button"
        type="button"
        id="dgStopAllButton">
        <?=ag_icon('stop', null, 'dg-icon')?>
        <span class="dg-label">Stop All</span>
    </button>

    <button
        class="dg-button dg-action-button"
        type="button"
        id="dgMapButton">
        <?=ag_icon('map', null, 'dg-icon')?>
        <span class="dg-label">Map</span>
    </button>

    <button
        class="dg-button dg-view-button"
        type="button"
        id="dgVisitorsButton">
        <?=ag_icon('users', null, 'dg-icon')?>
        <span class="dg-label">Visitors</span>
    </button>

    <span></span>


    <button
        class="dg-button"
        type="button"
        id="dgPauseButton"
        data-system-paused="0"
        title="Pause the selected running regions by freezing them in RAM.">

        <span class="dg-icon dg-pause-real-icon">
            <img
                id="dgPauseStateImage"
                src="/Other/assets/icons/sentinel/region-pause-native.png"
                alt=""
                aria-hidden="true"
                draggable="false">
        </span>

        <span class="dg-label">Pause</span>
    </button>

    <button
        class="dg-button dg-action-button dg-add"
        type="button"
        id="dgAddRegionButton">
        <?=ag_icon('add', null, 'dg-icon')?>
        <span class="dg-label">Add</span>
    </button>

    <button
        class="dg-button"
        type="button"
        id="dgExportButton"
        title="Export the selected DreamGrid Region INI file or files.">
        <?=ag_icon('export', null, 'dg-icon')?>
        <span class="dg-label">Export</span>
    </button>

    <span></span>
    <span></span>


    <a
        class="dg-button-link"
        href="<?=ag_h(ag_route('admin_regions'))?>">
        <?=ag_icon('refresh', null, 'dg-icon')?>
        <span class="dg-label">Refresh</span>
    </a>

    <button
        class="dg-button"
        type="button"
        id="dgRestartAllButton">
        <?=ag_icon('restart', null, 'dg-icon')?>
        <span class="dg-label">Restart</span>
    </button>

    <button
        class="dg-button dg-action-button"
        type="button"
        id="dgImportIniButton">
        <?=ag_icon('import', null, 'dg-icon')?>
        <span class="dg-label">Import INI</span>
    </button>

</div>

<div class="dg-searchbar">

    <form method="get">

        <input
            type="search"
            name="q"
            value="<?=ag_h($query)?>"
            placeholder="Search">

        <button type="submit">
            SEARCH
        </button>

    </form>

</div>

<!-- AUSTRALIA REGION TOOLBAR STRUCTURE V1 -->
</div>
<!-- AUSTRALIA REGION TOOLBAR STRUCTURE V1 END -->























<?php
/*
 * WEB_GUI_REGION_SETTINGS_SNAPSHOT
 *
 * All GUI-backed region settings are now read with ONE
 * scheduled bridge request per Web refresh.
 */

$dgGuiMaps       = [];
$dgGuiPhysics    = [];
$dgGuiBirds      = [];
$dgGuiTides      = [];
$dgGuiTeleport   = [];
$dgGuiAllow      = [];
$dgGuiOwnerGod   = [];
$dgGuiManagerGod = [];
$dgGuiAutoBackup = [];
$dgGuiPublicity  = [];
$dgGuiScriptRate = [];
$dgGuiFrameRate  = [];


try {

    require_once
        __DIR__ .
        '/core/region-settings-bridge.php';


    $dgGuiRegionNames =
        [];


    foreach ($regions as $dgGuiRegion) {

        $path =
            trim(
                (string)(
                    $dgGuiRegion['ini']['path'] ??
                    ''
                )
            );


        if ($path === '') {
            continue;
        }


        $name =
            trim(
                (string)pathinfo(
                    $path,
                    PATHINFO_FILENAME
                )
            );


        if ($name !== '') {
            $dgGuiRegionNames[] = $name;
        }
    }


    $dgGuiRegionNames =
        array_values(
            array_unique(
                $dgGuiRegionNames
            )
        );


    /* REGION_MANAGER_FAST_LOAD_V1
     *
     * Native GUI snapshot readback is deliberately opt-in.
     * The scheduled bridge can take about seven seconds when
     * the native DreamGrid Regions grid is not available.
     *
     * Normal Region Manager loads use the existing INI / DB
     * fallback values immediately.
     *
     * Diagnostic native readback remains available with:
     *     ?native_snapshot=1
     */
    if ($dgGuiRegionNames !== [] && (string)($_GET['native_snapshot'] ?? '') === '1') {

        $payload =
            json_encode(
                $dgGuiRegionNames,
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE
            );


        if (is_string($payload)) {

            $result =
                ag_region_bridge_request(
                    '__REGION_SETTINGS_SNAPSHOT__',
                    'region_snapshot',
                    $payload
                );


            if (
                !empty($result['ok']) &&
                isset($result['native_values']) &&
                is_array($result['native_values'])
            ) {

                $native =
                    $result['native_values'];


                $normalize =
                    static function ($values): array {

                        $output = [];


                        if (!is_array($values)) {
                            return $output;
                        }


                        foreach (
                            $values
                            as
                            $regionName =>
                            $regionValue
                        ) {

                            $key =
                                strtolower(
                                    trim(
                                        (string)$regionName
                                    )
                                );


                            if ($key === '') {
                                continue;
                            }


                            $output[$key] =
                                trim(
                                    (string)$regionValue
                                );
                        }


                        return $output;
                    };


                $dgGuiMaps =
                    $normalize(
                        $native['maps'] ??
                        []
                    );


                $dgGuiPhysics =
                    $normalize(
                        $native['physics'] ??
                        []
                    );


                $dgGuiBirds =
                    $normalize(
                        $native['birds'] ??
                        []
                    );


                $dgGuiTides =
                    $normalize(
                        $native['tides'] ??
                        []
                    );


                $dgGuiTeleport =
                    $normalize(
                        $native['teleport'] ??
                        []
                    );


                $dgGuiAllow =
                    $normalize(
                        $native['allow'] ??
                        []
                    );


                $dgGuiOwnerGod =
                    $normalize(
                        $native['owner_god'] ??
                        []
                    );


                $dgGuiManagerGod =
                    $normalize(
                        $native['manager_god'] ??
                        []
                    );


                $dgGuiAutoBackup =
                    $normalize(
                        $native['auto_backup'] ??
                        []
                    );


                $dgGuiPublicity =
                    $normalize(
                        $native['publicity'] ??
                        []
                    );

                $dgGuiScriptRate =
                    $normalize(
                        $native['script_rate'] ??
                        []
                    );


                $dgGuiFrameRate =
                    $normalize(
                        $native['frame_rate'] ??
                        []
                    );
            }
        }
    }
}
catch (Throwable $e) {
}
?>

<section
    class="dg-manager-view active"
    id="dgDetailsView">

<div class="dg-table-frame">

<table class="dg-table" id="dgRegionTable">

<colgroup id="dgRegionColumnWidths">
    <col style="width:1.8%">  <!-- Enable -->
    <col style="width:9.4%">  <!-- Region Name -->
    <col style="width:6.0%">  <!-- DOS Box -->
    <col style="width:2.0%">  <!-- Agents -->
    <col style="width:3.8%">  <!-- Status -->
    <col style="width:4.2%">  <!-- Smart Start -->
    <col style="width:4.2%">  <!-- RAM -->
    <col style="width:1.8%">  <!-- X -->
    <col style="width:1.8%">  <!-- Y -->
    <col style="width:2.6%">  <!-- Size -->
    <col style="width:5.5%">  <!-- Estate -->
    <col style="width:6.5%">  <!-- Owner -->
    <col style="width:7.0%">  <!-- Parcels Settings -->
    <col style="width:3.0%">  <!-- Prims -->
    <col style="width:2.6%">  <!-- Region -->
    <col style="width:2.6%">  <!-- Group -->
    <col style="width:5.0%">  <!-- Maps -->
    <col style="width:6.0%">  <!-- Physics -->
    <col style="width:1.6%">  <!-- Birds -->
    <col style="width:1.6%">  <!-- Tides -->
    <col style="width:2.2%">  <!-- Teleport -->
    <col style="width:1.7%">  <!-- Allow -->
    <col style="width:1.9%">  <!-- Owner God -->
    <col style="width:2.0%">  <!-- Manager God -->
    <col style="width:2.2%">  <!-- AutoBackup -->
    <col style="width:2.0%">  <!-- Publicity -->
    <col style="width:2.4%">  <!-- Script Rate -->
    <col style="width:2.4%">  <!-- Frame Rate -->
    <col style="width:4.2%">  <!-- OpensimWorld Key -->
</colgroup>

<thead>

<tr>
    <th class="dg-center">Enable</th>
    <th>Region Name</th>
    <th>DOS Box</th>
    <th>Agents</th>
    <th>Status</th>
    <th>Smart Start</th>
    <th>RAM</th>
    <th>X</th>
    <th>Y</th>
    <th>Size</th>
    <th>Estate</th>
    <th>Owner</th>
    <th>Parcels Settings</th>
    <th>Prims</th>
    <th>Region</th>
    <th>Group</th>
    <th>Maps</th>
    <th>Physics</th>
    <th>Birds</th>
    <th>Tides</th>
    <th>Teleport</th>
    <th>Allow</th>
    <th>Owner God</th>
    <th>Manager God</th>
    <th>AutoBackup</th>
    <th>Publicity</th>
    <th>Script Rate</th>
    <th>Frame Rate</th>
    <th>OpensimWorld Key</th>
</tr>

</thead>

<tbody>

<?php foreach ($filtered as $region): ?>

<?php

$dgName =
    (string)(
        $region['regionName'] ??
        ''
    );

$dgKey =
    strtolower(
        trim(
            $dgName
        )
    );

$dgCell =
    $dreamGridRegions[$dgKey] ??
    [];

$dgFreezeRecord =
    $regionFreezeState[$dgKey] ??
    null;

$dgFrozen =
    is_array($dgFreezeRecord) &&
    !empty($dgFreezeRecord['frozen']);

$dgEnabledHtml =
    (string)(
        $dgCell['Enabled'] ??
        ''
    );

$dgEnabled =
    stripos(
        $dgEnabledHtml,
        'checked'
    ) !== false;

$dgBootEnabled =
    dgRegionBootEnabled(
        $region,
        $dgEnabled
    );

$dgSmart =
    trim(
        strip_tags(
            (string)(
                $dgCell['SmartStart'] ??
                ''
            )
        )
    );

if (
    $dgSmart === '' ||
    $dgSmart === '-'
) {
    $dgSmart = 'Off';
}

$dgRam =
    trim(
        strip_tags(
            (string)(
                $dgCell['Ram'] ??
                '-'
            )
        )
    );

if ($dgRam === '') {
    $dgRam = '-';
}

$dgSize =
    trim(
        strip_tags(
            (string)(
                $dgCell['Size'] ??
                ''
            )
        )
    );

if ($dgSize === '') {

    $dgSize =
        rtrim(
            rtrim(
                number_format(
                    ((int)$region['sizeX']) / 256,
                    2,
                    '.',
                    ''
                ),
                '0'
            ),
            '.'
        )
        .
        'X'
        .
        rtrim(
            rtrim(
                number_format(
                    ((int)$region['sizeY']) / 256,
                    2,
                    '.',
                    ''
                ),
                '0'
            ),
            '.'
        );
}

$dgEstate =
    trim(
        strip_tags(
            (string)(
                $dgCell['EstateName'] ??
                ''
            )
        )
    );

if ($dgEstate === '') {
    $dgEstate = '-';
}

$dgOwner =
    trim(
        strip_tags(
            (string)(
                $dgCell['EstateOwner'] ??
                $region['owner_name'] ??
                '-'
            )
        )
    );

$dgAgents =
    (string)(
        $dgCell['AvatarCount'] ??
        '0'
    );

$dgPrims =
    (string)(
        $dgCell['PrimCount'] ??
        '0'
    );

$dgDosBox =
    (string)(
        $region['ini']['dos_box'] ??
        $dgName
    );

$dgMap =
    trim(
        strip_tags(
            (string)(
                $dgCell['Map'] ??
                ''
            )
        )
    );

if ($dgMap === '') {
    $dgMap = '-';
}

/*
 * Native Region Manager settings.
 *
 * These are read directly from the Region INI so the values
 * also exist for stopped regions.
 */

$dgIni =
    is_array($region['ini'] ?? null)
        ? $region['ini']
        : [];

$dgIniTrue =
    static function ($value): bool {

        return
            strcasecmp(
                trim((string)$value),
                'True'
            ) === 0;
    };


/*
 * Smart Start
 */

$dgSmartBootIni =
    $dgIniTrue(
        $dgIni['smart_boot'] ?? ''
    );

$dgSmartStartIni =
    $dgIniTrue(
        $dgIni['smart_start'] ?? ''
    );

if ($dgSmartStartIni) {

    $dgSmart =
        'Smart Suspend';

}
elseif ($dgSmartBootIni) {

    $dgSmart =
        'Smart Boot';

}
else {

    $dgSmart =
        'Off';
}


/*
 * Group Port
 */

$dgGroupPort =
    trim(
        (string)(
            $dgIni['group_port'] ??
            ''
        )
    );

if ($dgGroupPort === '') {
    $dgGroupPort = '-';
}


/*
 * Map renderer
 */

/*
 * WEB_NATIVE_MAPS_READBACK
 *
 * Region MapType= is not authoritative for the native manager's
 * Maps selection. The manager expands the selected Maps mode into
 * the generated per-region Opensim.ini [Map] section.
 *
 * Unknown signatures continue to fall back to Region MapType.
 */

$dgMapTypeRaw =
    trim(
        (string)(
            $dgIni['map_type'] ??
            ''
        )
    );


$dgRegionIniPath =
    trim(
        (string)(
            $region['ini']['path'] ??
            ''
        )
    );


$dgGeneratedOpensimIni =
    '';


if ($dgRegionIniPath !== '') {

    $dgGeneratedOpensimIni =
        dirname(
            dirname(
                $dgRegionIniPath
            )
        ) .
        DIRECTORY_SEPARATOR .
        'Opensim.ini';
}


$dgNativeMapSettings =
    [];


if (
    $dgGeneratedOpensimIni !== '' &&
    is_file($dgGeneratedOpensimIni)
) {

    $dgMapIniLines =
        @file(
            $dgGeneratedOpensimIni,
            FILE_IGNORE_NEW_LINES
        );


    if (is_array($dgMapIniLines)) {

        $dgInsideMapSection =
            false;


        foreach ($dgMapIniLines as $dgMapIniLine) {

            $dgMapIniTrim =
                trim(
                    (string)$dgMapIniLine
                );


            if (
                preg_match(
                    '/^\s*\[([^\]]+)\]\s*$/',
                    $dgMapIniTrim,
                    $dgMapSectionMatch
                )
            ) {

                $dgInsideMapSection =
                    strcasecmp(
                        trim(
                            (string)$dgMapSectionMatch[1]
                        ),
                        'Map'
                    ) === 0;

                continue;
            }


            if (!$dgInsideMapSection) {
                continue;
            }


            if (
                $dgMapIniTrim === '' ||
                substr($dgMapIniTrim, 0, 1) === ';' ||
                substr($dgMapIniTrim, 0, 1) === '#'
            ) {
                continue;
            }


            if (
                preg_match(
                    '/^\s*([^=]+?)\s*=\s*(.*?)\s*$/',
                    $dgMapIniLine,
                    $dgMapSettingMatch
                )
            ) {

                $dgNativeMapSettings[
                    strtolower(
                        trim(
                            (string)$dgMapSettingMatch[1]
                        )
                    )
                ] =
                    trim(
                        (string)$dgMapSettingMatch[2]
                    );
            }
        }
    }
}


/*
 * VERIFIED NATIVE SIGNATURE:
 *
 * Simple but fast
 *
 * GenerateMaptiles=True
 * MapImageModule=MapImageModule
 * TextureOnMapTile=False
 * DrawPrimOnMapTile=False
 * TexturePrims=False
 * RenderMeshes=False
 */

if (
    strcasecmp(
        (string)(
            $dgNativeMapSettings['generatemaptiles'] ??
            ''
        ),
        'True'
    ) === 0 &&

    strcasecmp(
        (string)(
            $dgNativeMapSettings['mapimagemodule'] ??
            ''
        ),
        'MapImageModule'
    ) === 0 &&

    strcasecmp(
        (string)(
            $dgNativeMapSettings['textureonmaptile'] ??
            ''
        ),
        'False'
    ) === 0 &&

    strcasecmp(
        (string)(
            $dgNativeMapSettings['drawprimonmaptile'] ??
            ''
        ),
        'False'
    ) === 0 &&

    strcasecmp(
        (string)(
            $dgNativeMapSettings['textureprims'] ??
            ''
        ),
        'False'
    ) === 0 &&

    strcasecmp(
        (string)(
            $dgNativeMapSettings['rendermeshes'] ??
            ''
        ),
        'False'
    ) === 0
) {

    $dgMapTypeRaw =
        'Simple';
}

$dgMapLabels =
    [
        ''        => 'Use Default',
        'Default' => 'Use Default',
        'Simple'  => 'Simple But Fast',
        'Good'    => 'Good (Warp 3D)',
        'Better'  => 'Better (Prims, Slow)',
        'Best'    => 'Best (Prims + Mesh, Very Slow)',
    ];

$dgMapType =
    $dgMapLabels[$dgMapTypeRaw] ??
    (
        $dgMapTypeRaw !== ''
            ? $dgMapTypeRaw
            : 'Use Default'
    );


/*
 * Physics engine
 */

$dgPhysicsRaw =
    trim(
        (string)(
            $dgIni['physics'] ??
            ''
        )
    );

$dgPhysicsLabels =
    [
        ''  => 'Use Default',
        '2' => 'Bullet',
        '3' => 'Bullet Threaded',
        '4' => 'ubODE',
        '5' => 'ubODE Hybrid',
    ];

$dgPhysics =
    $dgPhysicsLabels[$dgPhysicsRaw] ??
    (
        $dgPhysicsRaw !== ''
            ? $dgPhysicsRaw
            : 'Use Default'
    );


/*
 * Checkbox settings
 */

$dgBirds =
    $dgIniTrue(
        $dgIni['birds'] ?? ''
    );

$dgTides =
    $dgIniTrue(
        $dgIni['tides'] ?? ''
    );

$dgTeleport =
    $dgIniTrue(
        $dgIni['teleport'] ?? 'True'
    );

$dgAllow =
    $dgIniTrue(
        $dgIni['allow_gods'] ?? ''
    );

$dgOwnerGod =
    $dgIniTrue(
        $dgIni['region_god'] ?? ''
    );

$dgManagerGod =
    $dgIniTrue(
        $dgIni['manager_god'] ?? ''
    );

/*
 * SkipAutoBackup is inverse logic:
 *
 * SkipAutoBackup=True  -> AutoBackup unchecked
 * anything else        -> AutoBackup checked
 */

$dgAutoBackup =
    !$dgIniTrue(
        $dgIni['skip_auto_backup'] ?? ''
    );

/*
 * Native Regions GUI Publicity checkbox:
 *
 * OFF -> RegionSnapShot=
 * ON  -> RegionSnapShot=True
 *
 * Do not infer this checkbox from the separate
 * Publicity key.
 */
$dgPublicity =
    $dgIniTrue(
        $dgIni['region_snapshot'] ?? ''
    );


/*
 * Script / frame rates
 */

$dgScriptRate =
    trim(
        (string)(
            $dgIni['script_rate'] ??
            ''
        )
    );

if ($dgScriptRate === '') {
    $dgScriptRate = '-';
}

$dgFrameRate =
    trim(
        (string)(
            $dgIni['frame_rate'] ??
            ''
        )
    );

if ($dgFrameRate === '') {
    $dgFrameRate = '-';
}

/*
 * DREAMGRID LIVE REGION STATE
 *
 * DreamGrid's regionlist currently shows:
 *
 *   Running region:
 *       Enabled = checked
 *       Ram     = e.g. "3396 MB"
 *
 *   Stopped region:
 *       Enabled = checked
 *       Ram     = "-"
 *
 *   Disabled region:
 *       Enabled = unchecked
 *
 * Robust registration does NOT mean the region is running.
 */

$dgHasLiveRecord =
    is_array($dgCell) &&
    !empty($dgCell);

$dgRamIsRunning =
    (
        $dgRam !== '' &&
        $dgRam !== '-' &&
        preg_match(
            '/[0-9.]+\s*(MB|GB)/i',
            $dgRam
        )
    );

if ($dgHasLiveRecord) {

    if (!$dgEnabled) {

        $dgState =
            'Disabled';

        $dgStateClass =
            'disabled';

    }
    elseif ($dgRamIsRunning) {

        $dgState =
            'Running';

        $dgStateClass =
            'running';

    }
    else {

        $dgState =
            'Stopped';

        $dgStateClass =
            'stopped';
    }

}
elseif (
    $region['status'] ===
    'INI ONLY'
) {

    $dgState =
        'INI Only';

    $dgStateClass =
        'ini';

}
elseif (
    $region['status'] ===
    'MATCHED'
) {

    /*
     * Registered with Robust, but no live DreamGrid
     * process record was returned.
     */
    $dgState =
        'Stopped';

    $dgStateClass =
        'stopped';

}
else {

    $dgState =
        'Check';

    $dgStateClass =
        'stopped';
}

$dgFrozen =
    $dgFrozen &&
    $dgState === 'Running';

$dgSmartActive =
    (
        $dgSmart !== 'Off' &&
        $dgSmart !== '-'
    );


$dgHop =
    '';

$dgHopRaw =
    trim(
        (string)(
            $dgCell['Hop'] ??
            ''
        )
    );


if ($dgHopRaw !== '') {

    if (
        preg_match(
            "~href\s*=\s*['\"]([^'\"]+)['\"]~i",
            $dgHopRaw,
            $dgHopMatch
        )
    ) {

        $dgHop =
            html_entity_decode(
                trim(
                    (string)$dgHopMatch[1]
                ),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

    }
    elseif (
        stripos(
            $dgHopRaw,
            'hop://'
        ) === 0
    ) {

        $dgHop =
            $dgHopRaw;
    }
}


if (
    stripos(
        $dgHop,
        'hop://'
    ) !== 0
) {

    $dgHop =
        '';
}

?>

<tr
    class="dg-row"
    tabindex="0"
    data-name="<?=ag_h($dgName)?>"
    data-uuid="<?=ag_h($region['uuid'])?>"
    data-state="<?=ag_h(strtolower($dgState))?>"
    data-status="<?=ag_h($dgState)?>"
    data-enabled="<?=$dgBootEnabled ? '1' : '0'?>"
    data-smart="<?=$dgSmartActive ? '1' : '0'?>"
    data-frozen="<?=$dgFrozen ? '1' : '0'?>"
    data-hop="<?=ag_h($dgHop)?>"
>

<td class="dg-center">
    <input
        class="dg-enable-box"
        type="checkbox"
        data-region="<?=ag_h($dgName)?>"
        title="Enable this region for grid startup"
        <?=$dgBootEnabled ? 'checked' : ''?>>
</td>

<td class="dg-region-name-cell" title="Open Region Controls">
    <span class="dg-region-dot <?=$dgStateClass?>"></span>
    <span class="dg-region-name-link"><?=ag_h($dgName)?></span>
</td>

<td><?=ag_h($dgDosBox)?></td>

<td><?=ag_h($dgAgents)?></td>

<td class="dg-state-<?=$dgStateClass?>">
    <?=ag_h($dgState)?>
</td>

<td>
    <select
        class="dg-region-setting-control"
        data-setting="smart_mode"
        aria-label="Smart Start" title="Changes are applied through the native Regions window">

        <option
            value="off"
            <?=$dgSmart === 'Off' ? 'selected' : ''?>>
            Off
        </option>

        <option
            value="boot"
            <?=$dgSmart === 'Smart Boot' ? 'selected' : ''?>>
            Smart Boot
        </option>

        <option
            value="suspend"
            <?=$dgSmart === 'Smart Suspend' ? 'selected' : ''?>>
            Smart Suspend
        </option>

    </select>
</td>

<td><?=ag_h($dgRam)?></td>

<td><?=ag_h($region['grid_x'])?></td>

<td><?=ag_h($region['grid_y'])?></td>

<td><?=ag_h($dgSize)?></td>

<td><?=ag_h($dgEstate)?></td>

<td><?=ag_h($dgOwner)?></td>

<td><?=ag_h($region['parcel_settings'] ?? '-')?></td>

<td><?=ag_h($dgPrims)?></td>

<td><?=ag_h($region['serverPort'])?></td>

<td><?=ag_h($dgGroupPort)?></td>

<td>
    <?php
    /*
     * The native Regions window stores its Maps choices
     * using the visible native labels. Normalize those
     * values back to the compact values used by this
     * web dropdown.
     */

    /*
     * WEB_GUI_MAPS_VALUE_OVERRIDE
     *
     * Use the real value displayed by the GUI.
     */

    $dgGuiMapCurrentIniPath =
        trim(
            (string)(
                $region['ini']['path'] ??
                ''
            )
        );


    $dgGuiMapCurrentRegion =
        '';


    if ($dgGuiMapCurrentIniPath !== '') {

        $dgGuiMapCurrentRegion =
            trim(
                (string)pathinfo(
                    $dgGuiMapCurrentIniPath,
                    PATHINFO_FILENAME
                )
            );
    }


    $dgGuiMapCurrentKey =
        strtolower(
            $dgGuiMapCurrentRegion
        );


    if (
        $dgGuiMapCurrentKey !== '' &&
        array_key_exists(
            $dgGuiMapCurrentKey,
            $dgGuiMaps
        )
    ) {

        $dgMapTypeRaw =
            trim(
                (string)$dgGuiMaps[
                    $dgGuiMapCurrentKey
                ]
            );
    }

    $dgMapSelected = 'Default';

    if (
        in_array(
            $dgMapTypeRaw,
            [
                '',
                'Default',
                'Use Default',
            ],
            true
        )
    ) {
        $dgMapSelected = 'Default';
    }
    elseif ($dgMapTypeRaw === 'None') {
        $dgMapSelected = 'None';
    }
    elseif (
        in_array(
            $dgMapTypeRaw,
            [
                'Simple',
                'Simple but fast',
                'Simple But Fast',
            ],
            true
        )
    ) {
        $dgMapSelected = 'Simple';
    }
    elseif (
        in_array(
            $dgMapTypeRaw,
            [
                'Good',
                'Good (Warp3D)',
                'Good (Warp 3D)',
            ],
            true
        )
    ) {
        $dgMapSelected = 'Good';
    }
    elseif (
        in_array(
            $dgMapTypeRaw,
            [
                'Better',
                'Better (Prims, Slow)',
            ],
            true
        )
    ) {
        $dgMapSelected = 'Better';
    }
    elseif (
        in_array(
            $dgMapTypeRaw,
            [
                'Best',
                'Best (Prims + Mesh, Very Slow)',
            ],
            true
        )
    ) {
        $dgMapSelected = 'Best';
    }
    ?>

    <select
        class="dg-region-setting-control"
        data-setting="map_type"
        aria-label="Maps">

        <option value="Default"
            <?=$dgMapSelected === 'Default' ? 'selected' : ''?>>
            Use Default
        </option>
        <option value="None"
            <?=$dgMapSelected === 'None' ? 'selected' : ''?>>
            None
        </option>

        <option value="Simple"
            <?=$dgMapSelected === 'Simple' ? 'selected' : ''?>>
            Simple but fast
        </option>

        <option value="Good"
            <?=$dgMapSelected === 'Good' ? 'selected' : ''?>>
            Good (Warp3D)
        </option>

        <option value="Better"
            <?=$dgMapSelected === 'Better' ? 'selected' : ''?>>
            Better (Prims, Slow)
        </option>

        <option value="Best"
            <?=$dgMapSelected === 'Best' ? 'selected' : ''?>>
            Best (Prims + Mesh, Very Slow)
        </option>

    </select>
</td>

<?php
/*
 * WEB_GUI_PHYSICS_VALUE_OVERRIDE
 */

$dgGuiPhysicsCurrentPath =
    trim(
        (string)(
            $region['ini']['path'] ??
            ''
        )
    );


if ($dgGuiPhysicsCurrentPath !== '') {

    $dgGuiPhysicsCurrentName =
        strtolower(
            trim(
                (string)pathinfo(
                    $dgGuiPhysicsCurrentPath,
                    PATHINFO_FILENAME
                )
            )
        );


    if (
        $dgGuiPhysicsCurrentName !== '' &&
        array_key_exists(
            $dgGuiPhysicsCurrentName,
            $dgGuiPhysics
        )
    ) {

        $dgGuiPhysicsValue =
            strtolower(
                trim(
                    (string)$dgGuiPhysics[
                        $dgGuiPhysicsCurrentName
                    ]
                )
            );


        if (
            in_array(
                $dgGuiPhysicsValue,
                [
                    'use default',
                    'default',
                ],
                true
            )
        ) {
            $dgPhysicsRaw = '';
        }
        elseif ($dgGuiPhysicsValue === 'bullet') {
            $dgPhysicsRaw = '2';
        }
        elseif ($dgGuiPhysicsValue === 'bullet threaded') {
            $dgPhysicsRaw = '3';
        }
        elseif ($dgGuiPhysicsValue === 'ubode') {
            $dgPhysicsRaw = '4';
        }
        elseif ($dgGuiPhysicsValue === 'ubode hybrid') {
            $dgPhysicsRaw = '5';
        }
    }
}
?>

<td>
    <select
        class="dg-region-setting-control"
        data-setting="physics"
        aria-label="Physics" title="Changes are applied through the GUI">

        <option value=""
            <?=$dgPhysicsRaw === '' ? 'selected' : ''?>>
            Use Default
        </option>

        <option value="2"
            <?=$dgPhysicsRaw === '2' ? 'selected' : ''?>>
            Bullet
        </option>

        <option value="3"
            <?=$dgPhysicsRaw === '3' ? 'selected' : ''?>>
            Bullet Threaded
        </option>

        <option value="4"
            <?=$dgPhysicsRaw === '4' ? 'selected' : ''?>>
            ubODE
        </option>

        <option value="5"
            <?=$dgPhysicsRaw === '5' ? 'selected' : ''?>>
            ubODE Hybrid
        </option>

    </select>
</td>

<?php
/*
 * WEB_GUI_BIRDS_VALUE_OVERRIDE
 */

$dgGuiBirdCurrentPath =
    trim(
        (string)(
            $region['ini']['path'] ??
            ''
        )
    );


if ($dgGuiBirdCurrentPath !== '') {

    $dgGuiBirdCurrentName =
        strtolower(
            trim(
                (string)pathinfo(
                    $dgGuiBirdCurrentPath,
                    PATHINFO_FILENAME
                )
            )
        );


    if (
        $dgGuiBirdCurrentName !== '' &&
        array_key_exists(
            $dgGuiBirdCurrentName,
            $dgGuiBirds
        )
    ) {

        $dgBirds =
            ((string)$dgGuiBirds[
                $dgGuiBirdCurrentName
            ]) === '1';
    }
}
?>

<td class="dg-center">
    <input
        type="checkbox"
        class="dg-region-setting-control"
        data-setting="birds"
        <?=$dgBirds ? 'checked' : ''?>
        aria-label="Birds"
        title="Changes are applied through the GUI">
        
</td>

<?php
/*
 * WEB_GUI_TIDES_VALUE_OVERRIDE
 */

$dgGuiTideCurrentPath =
    trim(
        (string)(
            $region['ini']['path'] ??
            ''
        )
    );


if ($dgGuiTideCurrentPath !== '') {

    $dgGuiTideCurrentName =
        strtolower(
            trim(
                (string)pathinfo(
                    $dgGuiTideCurrentPath,
                    PATHINFO_FILENAME
                )
            )
        );


    if (
        $dgGuiTideCurrentName !== '' &&
        array_key_exists(
            $dgGuiTideCurrentName,
            $dgGuiTides
        )
    ) {

        $dgTides =
            ((string)$dgGuiTides[
                $dgGuiTideCurrentName
            ]) === '1';
    }
}
?>

<td class="dg-center">
    <input
        type="checkbox"
        class="dg-region-setting-control"
        data-setting="tides"
        <?=$dgTides ? 'checked' : ''?>
        aria-label="Tides" title="Changes are applied through the GUI">
</td>

<?php
/*
 * WEB_GUI_TELEPORT_VALUE_OVERRIDE
 */

$dgGuiTeleportCurrentPath =
    trim(
        (string)(
            $region['ini']['path'] ??
            ''
        )
    );


if ($dgGuiTeleportCurrentPath !== '') {

    $dgGuiTeleportCurrentName =
        strtolower(
            trim(
                (string)pathinfo(
                    $dgGuiTeleportCurrentPath,
                    PATHINFO_FILENAME
                )
            )
        );


    if (
        $dgGuiTeleportCurrentName !== '' &&
        array_key_exists(
            $dgGuiTeleportCurrentName,
            $dgGuiTeleport
        )
    ) {

        $dgTeleport =
            ((string)$dgGuiTeleport[
                $dgGuiTeleportCurrentName
            ]) === '1';
    }
}
?>

<td class="dg-center">
    <input
        type="checkbox"
        class="dg-region-setting-control"
        data-setting="teleport"
        <?=$dgTeleport ? 'checked' : ''?>
        aria-label="Teleport" title="Changes are applied through the GUI">
</td>

<?php
/*
 * WEB_GUI_ALLOW_VALUE_OVERRIDE
 */

$dgGuiAllowCurrentPath =
    trim(
        (string)(
            $region['ini']['path'] ??
            ''
        )
    );

if ($dgGuiAllowCurrentPath !== '') {

    $dgGuiAllowCurrentName =
        strtolower(
            trim(
                (string)pathinfo(
                    $dgGuiAllowCurrentPath,
                    PATHINFO_FILENAME
                )
            )
        );

    if (
        $dgGuiAllowCurrentName !== '' &&
        array_key_exists(
            $dgGuiAllowCurrentName,
            $dgGuiAllow
        )
    ) {

        $dgAllow =
            ((string)$dgGuiAllow[
                $dgGuiAllowCurrentName
            ]) === '1';
    }
}
?>

<td class="dg-center">
    <input
        type="checkbox"
        class="dg-region-setting-control"
        data-setting="allow"
        <?=$dgAllow ? 'checked' : ''?>
        aria-label="Allow" title="Changes are applied through the GUI">
</td>

<?php
/*
 * WEB_GUI_OWNER_GOD_VALUE_OVERRIDE
 */

$dgGuiOwnerGodCurrentPath =
    trim(
        (string)(
            $region['ini']['path'] ??
            ''
        )
    );

if ($dgGuiOwnerGodCurrentPath !== '') {

    $dgGuiOwnerGodCurrentName =
        strtolower(
            trim(
                (string)pathinfo(
                    $dgGuiOwnerGodCurrentPath,
                    PATHINFO_FILENAME
                )
            )
        );

    if (
        $dgGuiOwnerGodCurrentName !== '' &&
        array_key_exists(
            $dgGuiOwnerGodCurrentName,
            $dgGuiOwnerGod
        )
    ) {

        $dgOwnerGod =
            ((string)$dgGuiOwnerGod[
                $dgGuiOwnerGodCurrentName
            ]) === '1';
    }
}
?>

<td class="dg-center">
    <input
        type="checkbox"
        class="dg-region-setting-control"
        data-setting="owner_god"
        <?=$dgOwnerGod ? 'checked' : ''?>
        aria-label="Owner God" title="Changes are applied through the GUI">
</td>

<?php
/*
 * WEB_GUI_MANAGER_GOD_VALUE_OVERRIDE
 */

$dgGuiManagerGodCurrentPath =
    trim(
        (string)(
            $region['ini']['path'] ??
            ''
        )
    );

if ($dgGuiManagerGodCurrentPath !== '') {

    $dgGuiManagerGodCurrentName =
        strtolower(
            trim(
                (string)pathinfo(
                    $dgGuiManagerGodCurrentPath,
                    PATHINFO_FILENAME
                )
            )
        );

    if (
        $dgGuiManagerGodCurrentName !== '' &&
        array_key_exists(
            $dgGuiManagerGodCurrentName,
            $dgGuiManagerGod
        )
    ) {

        $dgManagerGod =
            ((string)$dgGuiManagerGod[
                $dgGuiManagerGodCurrentName
            ]) === '1';
    }
}
?>

<td class="dg-center">
    <input
        type="checkbox"
        class="dg-region-setting-control"
        data-setting="manager_god"
        <?=$dgManagerGod ? 'checked' : ''?>
        aria-label="Manager God" title="Changes are applied through the GUI">
</td>

<?php
/*
 * WEB_GUI_AUTOBACKUP_VALUE_OVERRIDE
 */

$dgGuiAutoBackupCurrentPath =
    trim(
        (string)(
            $region['ini']['path'] ??
            ''
        )
    );

if ($dgGuiAutoBackupCurrentPath !== '') {

    $dgGuiAutoBackupCurrentName =
        strtolower(
            trim(
                (string)pathinfo(
                    $dgGuiAutoBackupCurrentPath,
                    PATHINFO_FILENAME
                )
            )
        );

    if (
        $dgGuiAutoBackupCurrentName !== '' &&
        array_key_exists(
            $dgGuiAutoBackupCurrentName,
            $dgGuiAutoBackup
        )
    ) {

        $dgAutoBackup =
            ((string)$dgGuiAutoBackup[
                $dgGuiAutoBackupCurrentName
            ]) === '1';
    }
}
?>

<td class="dg-center">
    <input
        type="checkbox"
        class="dg-region-setting-control"
        data-setting="auto_backup"
        <?=$dgAutoBackup ? 'checked' : ''?>
        aria-label="Auto Backup" title="Changes are applied through the GUI">
</td>

<?php
/*
 * WEB_GUI_PUBLICITY_VALUE_OVERRIDE
 */

$dgGuiPublicityCurrentPath =
    trim(
        (string)(
            $region['ini']['path'] ??
            ''
        )
    );

if ($dgGuiPublicityCurrentPath !== '') {

    $dgGuiPublicityCurrentName =
        strtolower(
            trim(
                (string)pathinfo(
                    $dgGuiPublicityCurrentPath,
                    PATHINFO_FILENAME
                )
            )
        );

    if (
        $dgGuiPublicityCurrentName !== '' &&
        array_key_exists(
            $dgGuiPublicityCurrentName,
            $dgGuiPublicity
        )
    ) {

        $dgPublicity =
            ((string)$dgGuiPublicity[
                $dgGuiPublicityCurrentName
            ]) === '1';
    }
}
?>

<td class="dg-center">
    <input
        type="checkbox"
        class="dg-region-setting-control"
        data-setting="publicity"
        <?=$dgPublicity ? 'checked' : ''?>
        aria-label="Publicity" title="Changes are applied through the GUI">
</td>

<?php
/*
 * WEB_GUI_SCRIPT_RATE_VALUE_OVERRIDE
 */

$dgGuiScriptRateCurrentPath =
    trim(
        (string)(
            $region['ini']['path'] ??
            ''
        )
    );

if ($dgGuiScriptRateCurrentPath !== '') {

    $dgGuiScriptRateCurrentName =
        strtolower(
            trim(
                (string)pathinfo(
                    $dgGuiScriptRateCurrentPath,
                    PATHINFO_FILENAME
                )
            )
        );

    if (
        $dgGuiScriptRateCurrentName !== '' &&
        array_key_exists(
            $dgGuiScriptRateCurrentName,
            $dgGuiScriptRate
        )
    ) {

        $dgScriptRate =
            (string)$dgGuiScriptRate[
                $dgGuiScriptRateCurrentName
            ];
    }
}
?>

<td><?=ag_h($dgScriptRate)?></td>

<?php
/*
 * WEB_GUI_FRAME_RATE_VALUE_OVERRIDE
 */

$dgGuiFrameRateCurrentPath =
    trim(
        (string)(
            $region['ini']['path'] ??
            ''
        )
    );

if ($dgGuiFrameRateCurrentPath !== '') {

    $dgGuiFrameRateCurrentName =
        strtolower(
            trim(
                (string)pathinfo(
                    $dgGuiFrameRateCurrentPath,
                    PATHINFO_FILENAME
                )
            )
        );

    if (
        $dgGuiFrameRateCurrentName !== '' &&
        array_key_exists(
            $dgGuiFrameRateCurrentName,
            $dgGuiFrameRate
        )
    ) {

        $dgFrameRate =
            (string)$dgGuiFrameRate[
                $dgGuiFrameRateCurrentName
            ];
    }
}
?>

<td><?=ag_h($dgFrameRate)?></td>

<td>
    <?=ag_h(
        $dgCell['OpensimWorld'] ??
        '-'
    )?>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>


</section>

<!-- DREAMGRID REGION MANAGER VIEW FOUNDATION V4 -->

<section
    class="dg-manager-view"
    id="dgIconsView">

<div class="dg-icon-view">

<?php foreach ($filtered as $dgIconRegion): ?>

<?php

$dgIconName =
    trim(
        (string)(
            $dgIconRegion['regionName'] ??
            ''
        )
    );

$dgIconKey =
    strtolower(
        $dgIconName
    );

$dgIconCell =
    $dreamGridRegions[$dgIconKey] ??
    [];

$dgIconEnabledHtml =
    (string)(
        $dgIconCell['Enabled'] ??
        ''
    );

$dgIconLiveEnabled =
    stripos(
        $dgIconEnabledHtml,
        'checked'
    ) !== false;

$dgIconBootEnabled =
    dgRegionBootEnabled(
        $dgIconRegion,
        $dgIconLiveEnabled
    );

$dgIconRam =
    trim(
        strip_tags(
            (string)(
                $dgIconCell['Ram'] ??
                ''
            )
        )
    );

$dgIconHasLive =
    is_array(
        $dgIconCell
    ) &&
    !empty(
        $dgIconCell
    );

$dgIconRunning =
    (
        $dgIconRam !== '' &&
        $dgIconRam !== '-' &&
        preg_match(
            '/[0-9.]+\s*(MB|GB)/i',
            $dgIconRam
        )
    );

if ($dgIconHasLive) {

    if (!$dgIconBootEnabled) {

        $dgIconState =
            'Disabled';

        $dgIconStateClass =
            'disabled';

    }
    elseif ($dgIconRunning) {

        $dgIconState =
            'Running';

        $dgIconStateClass =
            'running';

    }
    else {

        $dgIconState =
            'Stopped';

        $dgIconStateClass =
            'stopped';
    }

}
elseif (
    ($dgIconRegion['status'] ?? '') ===
    'INI ONLY'
) {

    $dgIconState =
        'INI Only';

    $dgIconStateClass =
        'ini';

}
elseif (
    ($dgIconRegion['status'] ?? '') ===
    'MATCHED'
) {

    $dgIconState =
        'Stopped';

    $dgIconStateClass =
        'stopped';

}
else {

    $dgIconState =
        'Check';

    $dgIconStateClass =
        'stopped';
}

$dgIconFreezeRecord =
    $regionFreezeState[$dgIconKey] ??
    null;

$dgIconFrozen =
    is_array(
        $dgIconFreezeRecord
    ) &&
    !empty(
        $dgIconFreezeRecord['frozen']
    ) &&
    $dgIconState === 'Running';

$dgIconHop =
    '';

$dgIconHopRaw =
    trim(
        (string)(
            $dgIconCell['Hop'] ??
            ''
        )
    );

if ($dgIconHopRaw !== '') {

    if (
        preg_match(
            "~href\s*=\s*['\"]([^'\"]+)['\"]~i",
            $dgIconHopRaw,
            $dgIconHopMatch
        )
    ) {

        $dgIconHop =
            html_entity_decode(
                trim(
                    (string)$dgIconHopMatch[1]
                ),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

    }
    elseif (
        stripos(
            $dgIconHopRaw,
            'hop://'
        ) === 0
    ) {

        $dgIconHop =
            $dgIconHopRaw;
    }
}

if (
    stripos(
        $dgIconHop,
        'hop://'
    ) !== 0
) {

    $dgIconHop =
        '';
}

?>

<button
    type="button"
    class="dg-icon-region"

    data-name="<?=ag_h($dgIconName)?>"

    data-uuid="<?=ag_h(
        (string)(
            $dgIconRegion['uuid'] ??
            ''
        )
    )?>"

    data-state="<?=ag_h(
        strtolower(
            $dgIconState
        )
    )?>"

    data-status="<?=ag_h(
        $dgIconState
    )?>"

    data-enabled="<?=$dgIconBootEnabled ? '1' : '0'?>"

    data-smart="0"

    data-frozen="<?=$dgIconFrozen ? '1' : '0'?>"

    data-hop="<?=ag_h(
        $dgIconHop
    )?>">

    <span class="dg-region-dot <?=$dgIconStateClass?>"></span>

    <span class="dg-icon-region-name">
        <?=ag_h($dgIconName)?>
    </span>

</button>

<?php endforeach; ?>

</div>

</section>


<section
    class="dg-manager-view"
    id="dgUsersView">

<!-- DREAMGRID REGION MANAGER USERS VIEW V2 -->

<?php

$dgUsersRows = [];
$dgUsersWarning = '';

$dgUsersInventory = [];
$dgUsersVisited = [];
$dgUsersBackup = [];


/*
 * ============================================================
 * NATIVE DREAMGRID IAR BACKUP USER SETTING
 * ============================================================
 *
 * DreamGrid stores the avatar names in:
 *
 *     IARsToSkipBackup
 *
 * Native FormRegionlist displays users found in that setting
 * as "Backup" in its Auto Backup IAR column.
 */

$dgIarBackupNames = [];

$dgIarBackupRaw =
    trim(
        (string)(
            ag_dg_setting(
                'IARsToSkipBackup'
            ) ??
            ''
        )
    );

if ($dgIarBackupRaw !== '') {

    foreach (
        explode(
            ',',
            $dgIarBackupRaw
        ) as $dgIarName
    ) {

        $dgIarName =
            strtolower(
                trim(
                    $dgIarName
                )
            );

        if ($dgIarName !== '') {

            $dgIarBackupNames[
                $dgIarName
            ] = true;
        }
    }
}


try {

    $dgUsersDb =
        ag_db_connect();

    if (!$dgUsersDb) {

        $dgUsersWarning =
            'User database is unavailable.';

    }
    else {

        mysqli_set_charset(
            $dgUsersDb,
            'utf8mb4'
        );


        /*
         * ====================================================
         * USER ACCOUNTS
         *
         * Native DreamGrid query has NO ORDER BY.
         * Preserve database/native ordering.
         * ====================================================
         */

        $dgUsersResult =
            mysqli_query(
                $dgUsersDb,
                '
                SELECT
                    PrincipalID,
                    FirstName,
                    LastName,
                    Email,
                    UserTitle,
                    UserLevel,
                    Created
                FROM UserAccounts
                '
            );


        if ($dgUsersResult) {

            while (
                $dgUser =
                    mysqli_fetch_assoc(
                        $dgUsersResult
                    )
            ) {

                /*
                 * Native DreamGrid keeps its internal
                 * GRID SERVICES account out of UserGrid.
                 */
                $dgFirst =
                    trim(
                        (string)(
                            $dgUser['FirstName'] ??
                            ''
                        )
                    );

                $dgLast =
                    trim(
                        (string)(
                            $dgUser['LastName'] ??
                            ''
                        )
                    );

                if (
                    strcasecmp(
                        $dgFirst,
                        'GRID'
                    ) === 0 &&
                    strcasecmp(
                        $dgLast,
                        'SERVICES'
                    ) === 0
                ) {
                    continue;
                }


                $dgUsersRows[] =
                    $dgUser;
            }

            mysqli_free_result(
                $dgUsersResult
            );
        }


        /*
         * ====================================================
         * INVENTORY COUNTS
         * ====================================================
         */

        $dgInventoryResult =
            @mysqli_query(
                $dgUsersDb,
                '
                SELECT
                    avatarID,
                    invType,
                    COUNT(*) AS ItemCount
                FROM inventoryitems
                GROUP BY
                    avatarID,
                    invType
                '
            );


        if ($dgInventoryResult) {

            while (
                $dgInventoryRow =
                    mysqli_fetch_assoc(
                        $dgInventoryResult
                    )
            ) {

                $dgInventoryUuid =
                    strtolower(
                        trim(
                            (string)(
                                $dgInventoryRow['avatarID'] ??
                                ''
                            )
                        )
                    );

                $dgInventoryType =
                    (int)(
                        $dgInventoryRow['invType'] ??
                        -999
                    );

                $dgInventoryCount =
                    (int)(
                        $dgInventoryRow['ItemCount'] ??
                        0
                    );


                if ($dgInventoryUuid === '') {
                    continue;
                }


                if (
                    !isset(
                        $dgUsersInventory[
                            $dgInventoryUuid
                        ]
                    )
                ) {

                    $dgUsersInventory[
                        $dgInventoryUuid
                    ] = [
                        'total' => 0,
                        'types' => []
                    ];
                }


                $dgUsersInventory[
                    $dgInventoryUuid
                ]['total'] +=
                    $dgInventoryCount;


                $dgUsersInventory[
                    $dgInventoryUuid
                ]['types'][
                    $dgInventoryType
                ] =
                    $dgInventoryCount;
            }

            mysqli_free_result(
                $dgInventoryResult
            );
        }


        /*
         * ====================================================
         * LAST VISIT
         * ====================================================
         */

        $dgVisitorResult =
            @mysqli_query(
                $dgUsersDb,
                '
                SELECT
                    visitorid,
                    MAX(dateupdated) AS LastVisited
                FROM visitor
                GROUP BY visitorid
                '
            );


        if ($dgVisitorResult) {

            while (
                $dgVisitorRow =
                    mysqli_fetch_assoc(
                        $dgVisitorResult
                    )
            ) {

                $dgVisitorUuid =
                    strtolower(
                        trim(
                            (string)(
                                $dgVisitorRow['visitorid'] ??
                                ''
                            )
                        )
                    );


                if ($dgVisitorUuid !== '') {

                    $dgUsersVisited[
                        $dgVisitorUuid
                    ] =
                        (string)(
                            $dgVisitorRow['LastVisited'] ??
                            ''
                        );
                }
            }

            mysqli_free_result(
                $dgVisitorResult
            );
        }


        /*
         * ====================================================
         * LAST IAR BACKUP
         * ====================================================
         */

        $dgBackupResult =
            @mysqli_query(
                $dgUsersDb,
                '
                SELECT
                    avataruuid,
                    MAX(lastbackup) AS LastBackup
                FROM avatarbackup
                GROUP BY avataruuid
                '
            );


        if ($dgBackupResult) {

            while (
                $dgBackupRow =
                    mysqli_fetch_assoc(
                        $dgBackupResult
                    )
            ) {

                $dgBackupUuid =
                    strtolower(
                        trim(
                            (string)(
                                $dgBackupRow['avataruuid'] ??
                                ''
                            )
                        )
                    );


                if ($dgBackupUuid !== '') {

                    $dgUsersBackup[
                        $dgBackupUuid
                    ] =
                        (string)(
                            $dgBackupRow['LastBackup'] ??
                            ''
                        );
                }
            }

            mysqli_free_result(
                $dgBackupResult
            );
        }
    }

}
catch (Throwable $dgUsersException) {

    $dgUsersWarning =
        'Users data could not be loaded.';
}


/*
 * ============================================================
 * NATIVE DREAMGRID DISPLAY HELPERS
 * ============================================================
 */

$dgUserInventoryCount =
    static function(
        array $inventory,
        int $type
    ): int {

        return
            (int)(
                $inventory['types'][$type] ??
                0
            );
    };


$dgUserCountDisplay =
    static function(
        int $value
    ): string {

        if ($value <= 0) {
            return '-';
        }

        return
            str_pad(
                (string)$value,
                5,
                '0',
                STR_PAD_LEFT
            );
    };


$dgUserItemsDisplay =
    static function(
        int $value
    ): string {

        return
            str_pad(
                (string)max(
                    0,
                    $value
                ),
                5,
                '0',
                STR_PAD_LEFT
            );
    };


$dgUserAgeDisplay =
    static function(
        int $created
    ): string {

        if ($created <= 0) {
            return '000000';
        }

        $days =
            max(
                0,
                (int)floor(
                    (
                        time() -
                        $created
                    ) /
                    86400
                )
            );

        return
            str_pad(
                (string)$days,
                6,
                '0',
                STR_PAD_LEFT
            );
    };


$dgUserCreatedDisplay =
    static function(
        int $created
    ): string {

        if ($created <= 0) {
            return '-';
        }

        /*
         * date(), not gmdate().
         * Native DreamGrid uses the Windows/local grid time.
         */
        return
            date(
                'n/j/Y g:i:s A',
                $created
            );
    };


$dgUserDateDisplay =
    static function(
        $value
    ): string {

        $text =
            trim(
                (string)$value
            );

        if (
            $text === '' ||
            $text === '0' ||
            $text === '0000-00-00 00:00:00'
        ) {
            return '-';
        }


        if (ctype_digit($text)) {

            $stamp =
                (int)$text;

        }
        else {

            $stamp =
                strtotime(
                    $text
                );
        }


        if (
            !isset($stamp) ||
            $stamp === false ||
            $stamp <= 0
        ) {
            return '-';
        }


        return
            date(
                'n/j/Y g:i:s A',
                $stamp
            );
    };


$dgUserLevelDisplay =
    static function(
        int $level
    ): string {

        if ($level < 0) {
            return 'No Login';
        }

        if ($level >= 250) {
            return 'God';
        }

        if ($level === 100) {
            return 'Wifi';
        }

        if ($level >= 200) {
            return 'WebAdmin';
        }

        return 'Enabled';
    };

?>

<?php if ($dgUsersWarning !== ''): ?>

<div class="dg-users-warning">
    <?=ag_h($dgUsersWarning)?>
</div>

<?php endif; ?>


<div class="dg-users-table-frame">

<table
    class="dg-users-table"
    id="dgUsersTable">

<colgroup>
    <col style="width:1.6%">
    <col style="width:8.2%">
    <col style="width:6%">
    <col style="width:10%">
    <col style="width:7.5%">
    <col style="width:7.5%">
    <col style="width:5.5%">
    <col style="width:4%">
    <col style="width:4%">
    <col style="width:7.5%">
    <col style="width:3.5%">
    <col style="width:12%">
    <col style="width:3%">
    <col style="width:3%">
    <col style="width:3%">
    <col style="width:3%">
    <col style="width:3%">
    <col style="width:3%">
    <col style="width:3%">
    <col style="width:3.7%">
</colgroup>

<thead>

<tr>
    <th class="dg-users-select-head"></th>
    <th>Avatar Name</th>
    <th>Title</th>
    <th>Email</th>
    <th>Date Visited</th>
    <th>Last Backup</th>
    <th>Auto Backup IAR</th>
    <th>Items</th>
    <th>Level</th>
    <th>Birthday</th>
    <th>Age</th>
    <th>UUID</th>
    <th>Textures</th>
    <th>Sounds</th>
    <th>Calling</th>
    <th>Landmarks</th>
    <th>Objects</th>
    <th>Notecards</th>
    <th>Scripts</th>
    <th>Photo</th>
</tr>

</thead>

<tbody>

<?php foreach ($dgUsersRows as $dgUser): ?>

<?php

$dgUserUuid =
    trim(
        (string)(
            $dgUser['PrincipalID'] ??
            ''
        )
    );

$dgUserKey =
    strtolower(
        $dgUserUuid
    );

$dgUserFirst =
    trim(
        (string)(
            $dgUser['FirstName'] ??
            ''
        )
    );

$dgUserLast =
    trim(
        (string)(
            $dgUser['LastName'] ??
            ''
        )
    );

$dgUserName =
    trim(
        $dgUserFirst .
        ' ' .
        $dgUserLast
    );

$dgUserNameKey =
    strtolower(
        $dgUserName
    );

$dgUserTitle =
    trim(
        (string)(
            $dgUser['UserTitle'] ??
            ''
        )
    );

$dgUserEmail =
    trim(
        (string)(
            $dgUser['Email'] ??
            ''
        )
    );

$dgUserLevel =
    (int)(
        $dgUser['UserLevel'] ??
        0
    );

$dgUserCreated =
    (int)(
        $dgUser['Created'] ??
        0
    );

$dgUserInv =
    $dgUsersInventory[
        $dgUserKey
    ] ??
    [
        'total' => 0,
        'types' => []
    ];

$dgUserVisited =
    $dgUsersVisited[
        $dgUserKey
    ] ??
    '';

$dgUserBackup =
    $dgUsersBackup[
        $dgUserKey
    ] ??
    '';

$dgUserAutoBackup =
    isset(
        $dgIarBackupNames[
            $dgUserNameKey
        ]
    )
        ? 'Skip'
        : 'Backup';

?>

<tr
    class="dg-user-row"
    tabindex="0"

    data-user-uuid="<?=ag_h($dgUserUuid)?>"
    data-user-name="<?=ag_h($dgUserName)?>"
    data-user-email="<?=ag_h($dgUserEmail)?>">

    <td class="dg-user-select-cell">

        <input
            type="checkbox"
            class="dg-user-select"
            aria-label="Select <?=ag_h($dgUserName)?>">

    </td>

    <td class="dg-user-name-cell">

    <a
        class="dg-user-name-link"
        href="/Other/admin-regions.php?edit_user=<?=rawurlencode($dgUserUuid)?>"
        title="Edit <?=ag_h($dgUserName)?>">

        <?=ag_h($dgUserName)?>

    </a>

</td>

    <td>
        <?=ag_h(
            $dgUserTitle !== ''
                ? $dgUserTitle
                : '-'
        )?>
    </td>

    <td>
        <?=ag_h(
            $dgUserEmail !== ''
                ? $dgUserEmail
                : '-'
        )?>
    </td>

    <td>
        <?=ag_h(
            $dgUserDateDisplay(
                $dgUserVisited
            )
        )?>
    </td>

    <td>
        <?=ag_h(
            $dgUserDateDisplay(
                $dgUserBackup
            )
        )?>
    </td>

    <td class="dg-center">
        <?=ag_h($dgUserAutoBackup)?>
    </td>

    <td class="dg-number">
        <?=ag_h(
            $dgUserItemsDisplay(
                (int)(
                    $dgUserInv['total'] ??
                    0
                )
            )
        )?>
    </td>

    <td>
        <?=ag_h(
            $dgUserLevelDisplay(
                $dgUserLevel
            )
        )?>
    </td>

    <td>
        <?=ag_h(
            $dgUserCreatedDisplay(
                $dgUserCreated
            )
        )?>
    </td>

    <td class="dg-number">
        <?=ag_h(
            $dgUserAgeDisplay(
                $dgUserCreated
            )
        )?>
    </td>

    <td class="dg-user-uuid">
        <?=ag_h($dgUserUuid)?>
    </td>

    <td class="dg-number">
        <?=ag_h(
            $dgUserCountDisplay(
                $dgUserInventoryCount(
                    $dgUserInv,
                    0
                )
            )
        )?>
    </td>

    <td class="dg-number">
        <?=ag_h(
            $dgUserCountDisplay(
                $dgUserInventoryCount(
                    $dgUserInv,
                    1
                )
            )
        )?>
    </td>

    <td class="dg-number">
        <?=ag_h(
            $dgUserCountDisplay(
                $dgUserInventoryCount(
                    $dgUserInv,
                    2
                )
            )
        )?>
    </td>

    <td class="dg-number">
        <?=ag_h(
            $dgUserCountDisplay(
                $dgUserInventoryCount(
                    $dgUserInv,
                    3
                )
            )
        )?>
    </td>

    <td class="dg-number">
        <?=ag_h(
            $dgUserCountDisplay(
                $dgUserInventoryCount(
                    $dgUserInv,
                    6
                )
            )
        )?>
    </td>

    <td class="dg-number">
        <?=ag_h(
            $dgUserCountDisplay(
                $dgUserInventoryCount(
                    $dgUserInv,
                    7
                )
            )
        )?>
    </td>

    <td class="dg-number">
        <?=ag_h(
            $dgUserCountDisplay(
                $dgUserInventoryCount(
                    $dgUserInv,
                    10
                )
            )
        )?>
    </td>

    <td class="dg-number">
        <?=ag_h(
            $dgUserCountDisplay(
                $dgUserInventoryCount(
                    $dgUserInv,
                    15
                )
            )
        )?>
    </td>

</tr>

<?php endforeach; ?>


<?php if (empty($dgUsersRows)): ?>

<tr>

    <td
        colspan="20"
        class="dg-users-empty">

        No users found.

    </td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</section>


<section
    class="dg-manager-view"
    id="dgAvatarsView">

<!-- DREAMGRID REGION MANAGER AVATARS VIEW V1B -->

<?php

$dgAvatarRows = [];
$dgAvatarWarning = '';

try {

    $dgAvatarDb =
        ag_db_connect();


    if (!$dgAvatarDb) {

        $dgAvatarWarning =
            'Avatar database is unavailable.';

    }
    else {

        mysqli_set_charset(
            $dgAvatarDb,
            'utf8mb4'
        );


        /*
         * ====================================================
         * NATIVE DREAMGRID GETPRESENCE
         *
         * Native DreamGrid reads current Presence records
         * joined to UserAccounts.
         *
         * Region table is joined only so the UUID can be
         * displayed as its region name.
         * ====================================================
         */

        $dgAvatarSql = <<<'SQL'
SELECT
    ua.PrincipalID,
    ua.FirstName,
    ua.LastName,
    p.RegionID,
    r.regionName
FROM Presence AS p

INNER JOIN UserAccounts AS ua
    ON p.UserID = ua.PrincipalID

LEFT JOIN regions AS r
    ON r.uuid = p.RegionID

WHERE
    p.RegionID IS NOT NULL

AND p.RegionID <> '00000000-0000-0000-0000-000000000000'

ORDER BY
    r.regionName,
    ua.FirstName,
    ua.LastName
SQL;


        $dgAvatarResult =
            @mysqli_query(
                $dgAvatarDb,
                $dgAvatarSql
            );


        if (!$dgAvatarResult) {

            $dgAvatarWarning =
                'Current avatars could not be read.';

        }
        else {

            while (
                $dgAvatarRow =
                    mysqli_fetch_assoc(
                        $dgAvatarResult
                    )
            ) {

                $dgAvatarRows[] =
                    $dgAvatarRow;
            }


            mysqli_free_result(
                $dgAvatarResult
            );
        }
    }

}
catch (Throwable $dgAvatarException) {

    $dgAvatarWarning =
        'Current avatars could not be loaded.';
}

?>


<?php if ($dgAvatarWarning !== ''): ?>

<div class="dg-users-warning">
    <?=ag_h($dgAvatarWarning)?>
</div>

<?php endif; ?>


<div class="dg-users-table-frame">

<table
    class="dg-users-table"
    id="dgAvatarsTable">

    <colgroup>

        <col style="width:50%">

        <col style="width:50%">

    </colgroup>


    <thead>

        <tr>

            <th>
                Agents
            </th>

            <th>
                Region
            </th>

        </tr>

    </thead>


    <tbody>

    <?php foreach ($dgAvatarRows as $dgAvatarRow): ?>

        <?php

        $dgAvatarName =
            trim(
                (string)(
                    $dgAvatarRow['FirstName'] ??
                    ''
                ) .
                ' ' .
                (string)(
                    $dgAvatarRow['LastName'] ??
                    ''
                )
            );


        $dgAvatarRegion =
            trim(
                (string)(
                    $dgAvatarRow['regionName'] ??
                    ''
                )
            );


        if ($dgAvatarRegion === '') {

            $dgAvatarRegion =
                trim(
                    (string)(
                        $dgAvatarRow['RegionID'] ??
                        ''
                    )
                );
        }

        ?>

        <tr>

            <td class="dg-user-name-cell">

                <?=ag_h(
                    $dgAvatarName !== ''
                        ? $dgAvatarName
                        : '-'
                )?>

            </td>


            <td>

                <?=ag_h(
                    $dgAvatarRegion !== ''
                        ? $dgAvatarRegion
                        : '-'
                )?>

            </td>

        </tr>

    <?php endforeach; ?>


    <?php if (empty($dgAvatarRows)): ?>

        <tr>

            <td
                colspan="2"
                class="dg-users-empty">

                No Avatars

            </td>

        </tr>

    <?php endif; ?>

    </tbody>

</table>

</div>

</section>


<section
    class="dg-manager-view"
    id="dgVisitorsView">

<!-- DREAMGRID NATIVE VISITORS V2 -->

<?php

$dgNativeVisitorRows = [];
$dgNativeVisitorWarning = '';

/*
 * ==========================================================
 * DREAMGRID / START.DLL VISITORS
 *
 * Native Region List behaviour:
 *
 * Start Date = Today
 * End Date   = Today
 *
 * Query:
 *   visitor
 *   grouped by name + region + calendar day
 *
 * Columns:
 *   Avatar Name
 *   Region Name
 *   Date Visited
 *   Visits
 * ==========================================================
 */

$dgNativeToday = '';

try {

    $dgNativeClockDb =
        ag_db_connect();


    if ($dgNativeClockDb) {

        mysqli_set_charset(
            $dgNativeClockDb,
            'utf8mb4'
        );


        $dgNativeClockResult =
            @mysqli_query(
                $dgNativeClockDb,
                'SELECT CURDATE() AS today_date'
            );


        if ($dgNativeClockResult) {

            $dgNativeClockRow =
                mysqli_fetch_assoc(
                    $dgNativeClockResult
                );


            $dgNativeToday =
                trim(
                    (string)(
                        $dgNativeClockRow['today_date'] ??
                        ''
                    )
                );


            mysqli_free_result(
                $dgNativeClockResult
            );
        }


        mysqli_close(
            $dgNativeClockDb
        );
    }

}
catch (Throwable $dgNativeClockException) {

    $dgNativeToday = '';
}


if ($dgNativeToday === '') {

    $dgNativeToday =
        date(
            'Y-m-d'
        );
}


/*
 * ----------------------------------------------------------
 * DATE VALIDATION
 * ----------------------------------------------------------
 */

$dgNativeDateValid =
    static function (
        string $value
    ): bool {

        if (
            !preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $value
            )
        ) {
            return false;
        }


        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $value
            );


        return
            $date instanceof DateTimeImmutable
            &&
            $date->format(
                'Y-m-d'
            ) ===
            $value;
    };


$dgNativeVisitorStart =
    trim(
        (string)(
            $_GET['visitors_start'] ??
            $dgNativeToday
        )
    );


$dgNativeVisitorEnd =
    trim(
        (string)(
            $_GET['visitors_end'] ??
            $dgNativeToday
        )
    );


if (
    !$dgNativeDateValid(
        $dgNativeVisitorStart
    )
) {

    $dgNativeVisitorStart =
        $dgNativeToday;
}


if (
    !$dgNativeDateValid(
        $dgNativeVisitorEnd
    )
) {

    $dgNativeVisitorEnd =
        $dgNativeToday;
}


/*
 * End date in the native calendar is inclusive.
 *
 * SQL uses:
 *
 *     dateupdated >= SINCE
 *     dateupdated <  UNTIL
 *
 * Therefore UNTIL is midnight after the selected End Date.
 */

$dgNativeEndObject =
    DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $dgNativeVisitorEnd
    );


$dgNativeSince =
    $dgNativeVisitorStart .
    ' 00:00:00';


$dgNativeUntil =
    $dgNativeEndObject
        ->modify(
            '+1 day'
        )
        ->format(
            'Y-m-d'
        ) .
    ' 00:00:00';


/*
 * ----------------------------------------------------------
 * NATIVE DREAMGRID GETALLVISITORS QUERY
 * ----------------------------------------------------------
 */

try {

    $dgNativeVisitorDb =
        ag_db_connect();


    if (!$dgNativeVisitorDb) {

        $dgNativeVisitorWarning =
            'Visitor database is unavailable.';

    }
    else {

        mysqli_set_charset(
            $dgNativeVisitorDb,
            'utf8mb4'
        );


        $dgNativeVisitorSql = <<<'SQL'
SELECT
    COUNT(*) AS visit_count,
    name,
    regionname,
    YEAR(dateupdated) AS Y,
    MONTH(dateupdated) AS M,
    DAY(dateupdated) AS D

FROM visitor

WHERE dateupdated >= ?
  AND dateupdated < ?

GROUP BY
    name,
    regionname,
    YEAR(dateupdated),
    MONTH(dateupdated),
    DAY(dateupdated)

ORDER BY
    regionname,
    YEAR(dateupdated),
    MONTH(dateupdated),
    DAY(dateupdated)
SQL;


        $dgNativeVisitorStmt =
            mysqli_prepare(
                $dgNativeVisitorDb,
                $dgNativeVisitorSql
            );


        if (!$dgNativeVisitorStmt) {

            $dgNativeVisitorWarning =
                'Visitor history query could not be prepared.';

        }
        else {

            mysqli_stmt_bind_param(
                $dgNativeVisitorStmt,
                'ss',
                $dgNativeSince,
                $dgNativeUntil
            );


            if (
                !mysqli_stmt_execute(
                    $dgNativeVisitorStmt
                )
            ) {

                $dgNativeVisitorWarning =
                    'Visitor history could not be read.';

            }
            else {

                $dgNativeVisitorResult =
                    mysqli_stmt_get_result(
                        $dgNativeVisitorStmt
                    );


                if ($dgNativeVisitorResult) {

                    while (
                        $dgNativeVisitorRow =
                            mysqli_fetch_assoc(
                                $dgNativeVisitorResult
                            )
                    ) {

                        $dgNativeVisitorRows[] =
                            $dgNativeVisitorRow;
                    }


                    mysqli_free_result(
                        $dgNativeVisitorResult
                    );
                }
            }


            mysqli_stmt_close(
                $dgNativeVisitorStmt
            );
        }


        mysqli_close(
            $dgNativeVisitorDb
        );
    }

}
catch (Throwable $dgNativeVisitorException) {

    $dgNativeVisitorWarning =
        'Visitor history could not be loaded.';
}

?>


<div class="dg-native-visitors-datebar">

    <form
        method="get"
        action="/Other/admin-regions.php"
        id="dgNativeVisitorsDateForm">

        <div class="dg-native-visitor-date">

            <label for="dgVisitorsStart">
                Start Date
            </label>

            <input
                type="date"
                id="dgVisitorsStart"
                name="visitors_start"
                value="<?=ag_h($dgNativeVisitorStart)?>">

        </div>


        <div class="dg-native-visitor-date">

            <label for="dgVisitorsEnd">
                End Date
            </label>

            <input
                type="date"
                id="dgVisitorsEnd"
                name="visitors_end"
                value="<?=ag_h($dgNativeVisitorEnd)?>">

        </div>


        <noscript>

            <button
                type="submit"
                class="dg-native-visitor-apply">

                Apply

            </button>

        </noscript>

    </form>

</div>


<?php if ($dgNativeVisitorWarning !== ''): ?>

<div class="dg-users-warning">

    <?=ag_h(
        $dgNativeVisitorWarning
    )?>

</div>

<?php endif; ?>


<div class="dg-users-table-frame">

<table
    class="dg-users-table dg-native-visitors-table"
    id="dgVisitorsTable">

    <colgroup>

        <col style="width:31%">

        <col style="width:31%">

        <col style="width:23%">

        <col style="width:15%">

    </colgroup>


    <thead>

        <tr>

            <th>
                Avatar Name
            </th>

            <th>
                Region Name
            </th>

            <th>
                Date Visited
            </th>

            <th>
                Visits
            </th>

        </tr>

    </thead>


    <tbody>

    <?php foreach ($dgNativeVisitorRows as $dgNativeVisitorRow): ?>

        <?php

        $dgNativeVisitorName =
            trim(
                (string)(
                    $dgNativeVisitorRow['name'] ??
                    ''
                )
            );


        $dgNativeVisitorRegion =
            trim(
                (string)(
                    $dgNativeVisitorRow['regionname'] ??
                    ''
                )
            );


        $dgNativeVisitorYear =
            (int)(
                $dgNativeVisitorRow['Y'] ??
                0
            );


        $dgNativeVisitorMonth =
            (int)(
                $dgNativeVisitorRow['M'] ??
                0
            );


        $dgNativeVisitorDay =
            (int)(
                $dgNativeVisitorRow['D'] ??
                0
            );


        /*
         * Native DreamGrid display:
         *
         *     2026/9/21
         */

        $dgNativeVisitorDate =
            sprintf(
                '%04d/%d/%d',
                $dgNativeVisitorYear,
                $dgNativeVisitorMonth,
                $dgNativeVisitorDay
            );

        ?>

        <tr>

            <td>

                <?=ag_h(
                    $dgNativeVisitorName !== ''
                        ? $dgNativeVisitorName
                        : '-'
                )?>

            </td>


            <td>

                <?=ag_h(
                    $dgNativeVisitorRegion !== ''
                        ? $dgNativeVisitorRegion
                        : '-'
                )?>

            </td>


            <td>

                <?=ag_h(
                    $dgNativeVisitorDate
                )?>

            </td>


            <td class="dg-native-visits-count">

                <?=number_format(
                    (int)(
                        $dgNativeVisitorRow['visit_count'] ??
                        0
                    )
                )?>

            </td>

        </tr>

    <?php endforeach; ?>


    <?php if (empty($dgNativeVisitorRows)): ?>

        <tr>

            <td
                colspan="4"
                class="dg-users-empty">

                No visitors for selected dates.

            </td>

        </tr>

    <?php endif; ?>

    </tbody>

</table>

</div>


<script id="dreamgrid-native-visitors-v2">

(function(){

    "use strict";

    const form =
        document.getElementById(
            "dgNativeVisitorsDateForm"
        );

    const start =
        document.getElementById(
            "dgVisitorsStart"
        );

    const end =
        document.getElementById(
            "dgVisitorsEnd"
        );


    if(
        !form ||
        !start ||
        !end
    ){
        return;
    }


    function submitDates(){

        if(
            typeof form.requestSubmit === "function"
        ){

            form.requestSubmit();

        }else{

            form.submit();
        }
    }


    start.addEventListener(
        "change",
        submitDates
    );


    end.addEventListener(
        "change",
        submitDates
    );

})();

</script>

</section>


<!-- DREAMGRID SERVER EDIT USER V3 -->

<?php

$dgEditRequested =
    isset(
        $_GET['edit_user']
    );

$dgEditUser =
    null;

$dgEditUserError =
    trim(
        (string)(
            $_GET['user_error'] ??
            ''
        )
    );

$dgEditUserSaved =
    (string)(
        $_GET['user_saved'] ??
        ''
    ) === '1';


if ($dgEditRequested) {

    $dgEditUuid =
        strtolower(
            trim(
                (string)(
                    $_GET['edit_user'] ??
                    ''
                )
            )
        );


    if (
        !preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $dgEditUuid
        )
    ) {

        $dgEditUserError =
            'Invalid user UUID.';

    }
    else {

        try {

            $dgEditDb =
                ag_db_connect();


            if (!$dgEditDb) {

                $dgEditUserError =
                    'Grid database is unavailable.';

            }
            else {

                mysqli_set_charset(
                    $dgEditDb,
                    'utf8mb4'
                );


                $dgEditStmt =
                    mysqli_prepare(
                        $dgEditDb,
                        '
                        SELECT
                            PrincipalID,
                            FirstName,
                            LastName,
                            Email,
                            UserTitle,
                            UserLevel
                        FROM UserAccounts
                        WHERE PrincipalID = ?
                        LIMIT 1
                        '
                    );


                if (!$dgEditStmt) {

                    $dgEditUserError =
                        'Could not prepare user lookup.';

                }
                else {

                    mysqli_stmt_bind_param(
                        $dgEditStmt,
                        's',
                        $dgEditUuid
                    );


                    mysqli_stmt_execute(
                        $dgEditStmt
                    );


                    $dgEditResult =
                        mysqli_stmt_get_result(
                            $dgEditStmt
                        );


                    $dgEditRow =
                        $dgEditResult
                            ? mysqli_fetch_assoc(
                                $dgEditResult
                            )
                            : null;


                    mysqli_stmt_close(
                        $dgEditStmt
                    );


                    if (!$dgEditRow) {

                        $dgEditUserError =
                            'User was not found.';

                    }
                    else {

                        $dgEditAvatarName =
                            trim(
                                (string)$dgEditRow['FirstName'] .
                                ' ' .
                                (string)$dgEditRow['LastName']
                            );


                        $dgEditSkipRaw =
                            trim(
                                (string)(
                                    ag_dg_setting(
                                        'IARsToSkipBackup'
                                    ) ??
                                    ''
                                )
                            );


                        $dgEditSkipped =
                            false;


                        if ($dgEditSkipRaw !== '') {

                            foreach (
                                explode(
                                    ',',
                                    $dgEditSkipRaw
                                ) as $dgEditSkipName
                            ) {

                                if (
                                    strcasecmp(
                                        trim(
                                            $dgEditSkipName
                                        ),
                                        $dgEditAvatarName
                                    ) === 0
                                ) {

                                    $dgEditSkipped =
                                        true;

                                    break;
                                }
                            }
                        }


                        $dgEditUser = [
                            'uuid' =>
                                (string)$dgEditRow['PrincipalID'],

                            'first_name' =>
                                (string)$dgEditRow['FirstName'],

                            'last_name' =>
                                (string)$dgEditRow['LastName'],

                            'email' =>
                                (string)$dgEditRow['Email'],

                            'title' =>
                                (string)$dgEditRow['UserTitle'],

                            'level' =>
                                (int)$dgEditRow['UserLevel'],

                            'auto_backup' =>
                                !$dgEditSkipped
                        ];
                    }
                }
            }

        }
        catch (Throwable $dgEditException) {

            $dgEditUserError =
                'User data could not be loaded.';
        }
    }
}

?>


<?php if ($dgEditRequested): ?>

<div
    class="dg-user-modal-backdrop open"
    id="dgUserModalBackdrop">
</div>


<div
    class="dg-user-dialog open"
    id="dgUserDialog"
    role="dialog"
    aria-modal="true"
    aria-labelledby="dgUserDialogTitle">

    <div class="dg-user-dialog-titlebar">

        <span id="dgUserDialogTitle">
            Edit User
        </span>

        <a
            class="dg-user-dialog-close"
            href="/Other/admin-regions.php"
            aria-label="Close">
            ×
        </a>

    </div>


    <div class="dg-user-dialog-body">

        <?php if ($dgEditUserSaved): ?>

            <div class="dg-user-dialog-message show success">
                User saved.
            </div>

        <?php endif; ?>


        <?php if ($dgEditUserError !== ''): ?>

            <div class="dg-user-dialog-message show error">
                <?=ag_h($dgEditUserError)?>
            </div>

        <?php endif; ?>


        <?php if ($dgEditUser !== null): ?>

        <form
            method="post"
            action="/Other/admin-region-user-action.php"
            class="dg-user-edit-form">

            <input
                type="hidden"
                name="action"
                value="save">

            <input
                type="hidden"
                name="html_mode"
                value="1">

            <input
                type="hidden"
                name="uuid"
                value="<?=ag_h($dgEditUser['uuid'])?>">


            <div class="dg-user-form-grid">

                <label>

                    <span>
                        First Name
                    </span>

                    <input
                        type="text"
                        name="first_name"
                        maxlength="64"
                        required
                        value="<?=ag_h($dgEditUser['first_name'])?>">

                </label>


                <label>

                    <span>
                        Last Name
                    </span>

                    <input
                        type="text"
                        name="last_name"
                        maxlength="64"
                        required
                        value="<?=ag_h($dgEditUser['last_name'])?>">

                </label>


                <label class="dg-user-field-wide">

                    <span>
                        Profile Account Name
                    </span>

                    <input
                        type="text"
                        name="user_title"
                        maxlength="64"
                        value="<?=ag_h($dgEditUser['title'])?>">

                </label>


                <label class="dg-user-field-wide">

                    <span>
                        UUID
                    </span>

                    <input
                        type="text"
                        readonly
                        value="<?=ag_h($dgEditUser['uuid'])?>">

                </label>


                <label class="dg-user-field-wide">

                    <span>
                        Email
                    </span>

                    <input
                        type="email"
                        name="email"
                        maxlength="254"
                        value="<?=ag_h($dgEditUser['email'])?>">

                </label>


                <label class="dg-user-field-wide">

    <span>
        Password
    </span>

    <input
        type="password"
        name="new_password"
        minlength="8"
        maxlength="128"
        autocomplete="new-password"
        placeholder="Leave blank to keep current password">

</label>

            </div>


            <div class="dg-user-auto-row">

                <label>

                    <input
                        type="checkbox"
                        name="auto_backup"
                        value="1"
                        <?=$dgEditUser['auto_backup'] ? 'checked' : ''?>>

                    <span>
                        Auto Backup IAR
                    </span>

                </label>

            </div>


            <fieldset class="dg-user-level-box">

                <legend>
                    Level
                </legend>


                <label>

                    <input
                        type="radio"
                        name="user_level"
                        value="-1"
                        <?=$dgEditUser['level'] === -1 ? 'checked' : ''?>>

                    No Login

                </label>


                <label>

                    <input
                        type="radio"
                        name="user_level"
                        value="0"
                        <?=$dgEditUser['level'] === 0 ? 'checked' : ''?>>

                    Login Allowed

                </label>


                <label>

                    <input
                        type="radio"
                        name="user_level"
                        value="100"
                        <?=$dgEditUser['level'] === 100 ? 'checked' : ''?>>

                    Diva WiFi Page Admin

                </label>


                <label>

                    <input
                        type="radio"
                        name="user_level"
                        value="250"
                        <?=$dgEditUser['level'] === 250 ? 'checked' : ''?>>

                    Level God Enabled

                </label>


                <label>

                    <input
                        type="radio"
                        name="user_level"
                        value="200"
                        <?=$dgEditUser['level'] === 200 ? 'checked' : ''?>>

                    Level WebAdmin

                </label>

            </fieldset>


            <div class="dg-user-dialog-actions">

                <button
                    type="submit"
                    class="dg-user-action-button">

                    Save

                </button>


                <a
                    href="/Other/admin-regions.php"
                    class="dg-user-action-button secondary">

                    Cancel

                </a>

            </div>

        </form>

        <?php else: ?>

            <div class="dg-user-dialog-actions">

                <a
                    href="/Other/admin-regions.php"
                    class="dg-user-action-button secondary">

                    Close

                </a>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php endif; ?>

<div
    class="dg-modal-backdrop"
    id="dgModalBackdrop">
</div>


<div
    class="dg-region-dialog"
    id="dgRegionDialog"
    role="dialog"
    aria-modal="true"
    aria-labelledby="dgDialogTitle">

    <div class="dg-dialog-title">

        <span id="dgDialogTitle">
            Region Controls
        </span>

        <button
            class="dg-dialog-close"
            type="button"
            id="dgDialogClose">
            X
        </button>

    </div>

    <div class="dg-dialog-group-title">
        Region Controls
    </div>

    <div class="dg-dialog-buttons">

    <button class="dg-control-btn" type="button" id="dgViewConsoleButton" disabled>
        <?=ag_icon('console', null, 'dg-control-icon')?>
        <span class="dg-control-label">View Console</span>
    </button>

    <button class="dg-control-btn" type="button" id="dgViewStatisticsButton" disabled>
        <?=ag_icon('statistics', null, 'dg-control-icon')?>
        <span class="dg-control-label">View Statistics</span>
    </button>

    <button
        class="dg-control-btn dg-control-start"
        type="button"
        id="dgStartRegionButton"
        disabled>
        <?=ag_icon('start', null, 'dg-control-icon')?>
        <span class="dg-control-label">Start</span>
    </button>

    <button class="dg-control-btn" type="button" id="dgLoadOarButton">
        <?=ag_icon('load', null, 'dg-control-icon')?>
        <span class="dg-control-label">Load Region OAR</span>
    </button>

    <button
        class="dg-control-btn dg-control-restart"
        type="button"
        id="dgRestartRegionButton"
        disabled>
        <?=ag_icon('restart', null, 'dg-control-icon')?>
        <span class="dg-control-label">Restart</span>
    </button>

    <button class="dg-control-btn" type="button" id="dgSaveOarButton" disabled>
        <?=ag_icon('save', null, 'dg-control-icon')?>
        <span class="dg-control-label">Save Region OAR</span>
    </button>

    <button
        class="dg-control-btn"
        type="button"
        id="dgDownloadOarButton"
        disabled>
        <?=ag_icon('save', null, 'dg-control-icon')?>
        <span class="dg-control-label">Download Latest OAR</span>
    </button>

    <button
        class="dg-control-btn dg-control-stop"
        type="button"
        id="dgStopRegionButton"
        disabled>
        <?=ag_icon('stop', null, 'dg-control-icon')?>
        <span class="dg-control-label">Stop</span>
    </button>

    <button class="dg-control-btn" type="button" id="dgViewLogButton">
        <?=ag_icon('log', null, 'dg-control-icon')?>
        <span class="dg-control-label">View Log</span>
    </button>

    <button
        class="dg-control-btn"
        type="button"
        id="dgTeleportButton"
        disabled>
        <?=ag_icon('teleport', null, 'dg-control-icon')?>
        <span class="dg-control-label">Teleport</span>
    </button>

    <button
        class="dg-control-btn dg-control-map"
        type="button"
        id="dgViewMapButton">
        <?=ag_icon('map', null, 'dg-control-icon')?>
        <span class="dg-control-label">View Map</span>
    </button>

    <button class="dg-control-btn" type="button" id="dgEditRegionButton" disabled>
        <?=ag_icon('edit', null, 'dg-control-icon')?>
        <span class="dg-control-label">Edit</span>
    </button>

    <button class="dg-control-btn" type="button" disabled>
        <?=ag_icon('alert', null, 'dg-control-icon')?>
        <span class="dg-control-label">Send Alert Message</span>
    </button>

    <button
        class="dg-control-btn dg-control-freeze"
        type="button"
        id="dgFreezeRegionButton"
        disabled>
        <?=ag_icon('freeze', null, 'dg-control-icon')?>
        <span class="dg-control-label">Freeze</span>
    </button>

    <button
        class="dg-control-btn dg-control-thaw"
        type="button"
        id="dgThawRegionButton"
        disabled>
        <?=ag_icon('thaw', null, 'dg-control-icon')?>
        <span class="dg-control-label">Thaw</span>
    </button>

</div>
<div
        class="dg-dialog-note"
        id="dgDialogNote">
        Select a region control.
    </div>

</div>





<!-- DREAMGRID REGION MANAGER EMAIL V1 -->
<!-- DREAMGRID REGION MANAGER SMTP SETUP V1 -->
<style>
.dg-email-overlay{
    position:fixed;
    inset:0;
    z-index:2147483600;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
    background:rgba(0,0,0,.72);
    box-sizing:border-box;
}

.dg-email-card{
    width:min(760px,96vw);
    max-height:90vh;
    overflow:auto;
    border:1px solid #9c7520;
    border-radius:8px;
    background:linear-gradient(180deg,#171b1d,#090b0c);
    box-shadow:0 24px 80px rgba(0,0,0,.72);
    color:#e8edf0;
    font-family:Arial,sans-serif;
}

.dg-email-head{
    padding:15px 18px;
    border-bottom:1px solid rgba(207,160,47,.38);
    background:#101412;
}

.dg-email-kicker{
    margin-bottom:4px;
    color:#d8aa3c;
    font-size:10px;
    font-weight:800;
    letter-spacing:.12em;
}

.dg-email-title{
    margin:0;
    color:#f4d77a;
    font-size:20px;
}

.dg-email-body{
    padding:18px;
}

.dg-email-field{
    display:block;
    margin-bottom:15px;
}

.dg-email-field > span{
    display:block;
    margin-bottom:6px;
    color:#d6b55b;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
}

.dg-email-recipients{
    max-height:105px;
    overflow:auto;
    padding:10px 12px;
    border:1px solid #394247;
    border-radius:5px;
    background:#080b0c;
    color:#cbd4d8;
    font-size:12px;
    line-height:1.45;
}

.dg-email-input,
.dg-email-textarea{
    width:100%;
    box-sizing:border-box;
    border:1px solid #465158;
    border-radius:5px;
    outline:none;
    background:#07090a;
    color:#f0f3f4;
    font:13px Arial,sans-serif;
}

.dg-email-input{
    min-height:38px;
    padding:8px 10px;
}

.dg-email-textarea{
    min-height:260px;
    resize:vertical;
    padding:10px;
    line-height:1.45;
}

.dg-email-input:focus,
.dg-email-textarea:focus{
    border-color:#c99b2d;
    box-shadow:0 0 0 1px rgba(201,155,45,.28);
}

.dg-email-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    padding:14px 18px 18px;
}

.dg-email-action{
    min-width:110px;
    min-height:36px;
    border:1px solid #5b666b;
    border-radius:5px;
    cursor:pointer;
    background:linear-gradient(180deg,#30383c,#1b2225);
    color:#e8edef;
    font-size:11px;
    font-weight:800;
}

.dg-email-action:hover{
    border-color:#d2a536;
}

.dg-email-action.send{
    border-color:#ad8425;
    background:linear-gradient(180deg,#4b3b13,#282008);
    color:#f6d66c;
}

.dg-email-action:disabled{
    opacity:.55;
    cursor:wait;
}

.dg-email-note{
    margin-top:8px;
    color:#94a3a9;
    font-size:10px;
}

/* ==========================================================
   REGION MANAGER SMTP V4
   CANONICAL AUSTRALIA CHARCOAL / GOLD

   This replaces the SMTP V3 styling in place.
   ========================================================== */


/* ----------------------------------------------------------
   OVERLAYS
   ---------------------------------------------------------- */

#dgSmtpSetup.dg-smtp-overlay,
#dgSmtpNotice.dg-smtp-overlay{
    position:fixed !important;
    inset:0 !important;
    z-index:2147483600 !important;

    display:flex !important;
    align-items:center !important;
    justify-content:center !important;

    padding:24px !important;
    box-sizing:border-box !important;

    background:rgba(0,0,0,.78) !important;
    background-image:none !important;
}


/* ----------------------------------------------------------
   MAIN SMTP WINDOW
   ---------------------------------------------------------- */

#dgSmtpSetup .dg-smtp-card{
    width:min(920px,96vw) !important;
    max-height:92vh !important;
    overflow:auto !important;

    color:#dedbd2 !important;

    border:1px solid #5b4820 !important;
    border-radius:8px !important;

    background:#070907 !important;
    background-image:none !important;

    box-shadow:
        0 18px 44px rgba(0,0,0,.66) !important;

    font-family:
        Arial,
        Helvetica,
        sans-serif !important;
}


#dgSmtpSetup .dg-smtp-head{
    padding:17px 20px !important;

    border-bottom:
        1px solid #5b4820 !important;

    background:#080a08 !important;
    background-image:none !important;

    box-shadow:none !important;
}


#dgSmtpSetup .dg-smtp-head h2{
    margin:0 !important;

    color:#f0ca62 !important;

    font-size:20px !important;
    font-weight:900 !important;
    line-height:1.2 !important;

    text-shadow:none !important;
}


#dgSmtpSetup .dg-smtp-body{
    display:grid !important;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        ) !important;

    gap:16px !important;

    padding:18px !important;

    background:#070907 !important;
    background-image:none !important;
}


/* ----------------------------------------------------------
   INNER PANELS
   ---------------------------------------------------------- */

#dgSmtpSetup .dg-smtp-panel{
    padding:14px !important;

    color:#dedbd2 !important;

    border:
        1px solid #5b4820 !important;

    border-radius:
        7px !important;

    background:#080a08 !important;
    background-image:none !important;

    box-shadow:none !important;

    text-shadow:none !important;
}


#dgSmtpSetup .dg-smtp-panel h3{
    margin:
        0 0 12px !important;

    color:#f0ca62 !important;

    font-size:12px !important;
    font-weight:900 !important;

    text-shadow:none !important;
}


/* ----------------------------------------------------------
   ROWS / LABELS
   ---------------------------------------------------------- */

#dgSmtpSetup .dg-smtp-row{
    display:grid !important;

    grid-template-columns:
        1fr 1.3fr !important;

    gap:10px !important;

    align-items:center !important;

    margin:
        8px 0 !important;

    color:#dedbd2 !important;

    font-size:11px !important;
}


#dgSmtpSetup .dg-smtp-row > span,
#dgSmtpSetup .dg-smtp-check,
#dgSmtpSetup .dg-smtp-radio{
    color:#dedbd2 !important;
    text-shadow:none !important;
}


#dgSmtpSetup .dg-smtp-check,
#dgSmtpSetup .dg-smtp-radio{
    display:flex !important;

    gap:8px !important;

    align-items:center !important;

    margin:
        8px 0 !important;

    font-size:11px !important;
}


/* ----------------------------------------------------------
   INPUTS
   ---------------------------------------------------------- */

#dgSmtpSetup .dg-smtp-input{
    width:100% !important;
    min-height:34px !important;

    box-sizing:border-box !important;

    padding:
        6px 8px !important;

    color:#dedbd2 !important;

    border:
        1px solid #40371f !important;

    border-radius:
        4px !important;

    outline:none !important;

    background:#0d0f0c !important;
    background-image:none !important;

    box-shadow:
        inset 0 1px 2px
        rgba(0,0,0,.55) !important;
}


#dgSmtpSetup .dg-smtp-input:hover{
    border-color:#5b4820 !important;
}


#dgSmtpSetup .dg-smtp-input:focus{
    border-color:#8c6a26 !important;

    box-shadow:
        0 0 0 1px
        #5b4820 !important;
}


#dgSmtpSetup .dg-smtp-input::placeholder{
    color:#777a73 !important;
    opacity:1 !important;
}


/* ----------------------------------------------------------
   CHECKBOXES / RADIO BUTTONS
   ---------------------------------------------------------- */

#dgSmtpSetup input[type="checkbox"],
#dgSmtpSetup input[type="radio"]{
    accent-color:#d9ae48 !important;
}


/* ----------------------------------------------------------
   NOTE
   ---------------------------------------------------------- */

#dgSmtpSetup .dg-smtp-note{
    margin-top:8px !important;

    color:#8f918b !important;

    font-size:10px !important;
}


/* ----------------------------------------------------------
   ACTION BAR
   ---------------------------------------------------------- */

#dgSmtpSetup .dg-smtp-actions{
    display:flex !important;

    justify-content:flex-end !important;

    gap:10px !important;

    padding:
        0 18px 18px !important;

    background:#070907 !important;
    background-image:none !important;
}


#dgSmtpSetup .dg-smtp-btn,
#dgSmtpNotice .dg-smtp-btn{
    min-width:120px !important;
    min-height:36px !important;

    padding:
        7px 14px !important;

    cursor:pointer !important;

    color:#dedbd2 !important;

    border:
        1px solid #5b4820 !important;

    border-radius:
        5px !important;

    background:
        linear-gradient(
            180deg,
            #18170f 0%,
            #10120f 52%,
            #0d0f0c 100%
        ) !important;

    box-shadow:
        inset 0 1px 0
        rgba(255,241,177,.06),
        0 2px 4px
        rgba(0,0,0,.32) !important;

    font-size:10px !important;
    font-weight:900 !important;

    text-shadow:none !important;
}


#dgSmtpSetup .dg-smtp-btn:hover,
#dgSmtpNotice .dg-smtp-btn:hover{
    color:#fff4cf !important;

    border-color:#8c6a26 !important;

    background:
        linear-gradient(
            180deg,
            #252016 0%,
            #18170f 52%,
            #10120f 100%
        ) !important;
}


#dgSmtpSetup .dg-smtp-btn.save,
#dgSmtpNotice .dg-smtp-btn.save{
    color:#f0ca62 !important;

    border-color:#8c6a26 !important;

    background:
        linear-gradient(
            180deg,
            #211c11 0%,
            #18170f 52%,
            #10120f 100%
        ) !important;
}


#dgSmtpSetup .dg-smtp-btn.save:hover,
#dgSmtpNotice .dg-smtp-btn.save:hover{
    color:#fff4cf !important;

    border-color:#d9ae48 !important;

    background:
        linear-gradient(
            180deg,
            #2d2516 0%,
            #211c11 52%,
            #18170f 100%
        ) !important;
}


#dgSmtpSetup .dg-smtp-btn:disabled,
#dgSmtpNotice .dg-smtp-btn:disabled{
    cursor:wait !important;
    opacity:.50 !important;
}


/* ----------------------------------------------------------
   SMTP NOTICE WINDOW
   ---------------------------------------------------------- */

#dgSmtpNotice .dg-smtp-notice-card{
    width:
        min(
            560px,
            calc(100vw - 34px)
        ) !important;

    overflow:hidden !important;

    color:#dedbd2 !important;

    border:
        1px solid #5b4820 !important;

    border-radius:
        8px !important;

    background:#070907 !important;
    background-image:none !important;

    box-shadow:
        0 18px 44px
        rgba(0,0,0,.66) !important;
}


#dgSmtpNotice .dg-smtp-head{
    padding:
        17px 20px !important;

    border-bottom:
        1px solid #5b4820 !important;

    background:#080a08 !important;
    background-image:none !important;
}


#dgSmtpNotice .dg-smtp-head h2{
    margin:0 !important;

    color:#f0ca62 !important;

    font-size:20px !important;
    font-weight:900 !important;

    text-shadow:none !important;
}


#dgSmtpNotice .dg-smtp-notice-body{
    padding:
        22px 20px !important;

    color:#dedbd2 !important;

    background:#070907 !important;
    background-image:none !important;

    font-size:13px !important;
    line-height:1.55 !important;

    white-space:pre-line !important;
}


#dgSmtpNotice .dg-smtp-notice-actions{
    display:flex !important;

    justify-content:flex-end !important;

    padding:
        0 20px 20px !important;

    background:#070907 !important;
    background-image:none !important;
}


#dgSmtpNotice .dg-smtp-notice-actions .dg-smtp-btn{
    min-width:150px !important;
}


/* ----------------------------------------------------------
   MOBILE
   ---------------------------------------------------------- */

@media(max-width:760px){

    #dgSmtpSetup .dg-smtp-body{
        grid-template-columns:
            1fr !important;
    }


    #dgSmtpSetup .dg-smtp-row{
        grid-template-columns:
            1fr !important;
    }
}

</style>

<script>
(function(){

"use strict";

const emailButton =
    document.getElementById(
        "dgEmailButton"
    );

if (!emailButton) {
    return;
}


async function dgEmailAlert(message){

    await dgOpenSmtpNotice(
        String(
            message || ""
        )
    );
}

async function dgEmailPost(values){

    const body =
        new URLSearchParams();

    Object.keys(values).forEach(
        function(key){

            body.set(
                key,
                String(values[key])
            );
        }
    );


    const response =
        await fetch(
            "/Other/admin-region-email-action.php",
            {
                method:
                    "POST",

                credentials:
                    "same-origin",

                headers:
                    {
                        "Content-Type":
                            "application/x-www-form-urlencoded;charset=UTF-8"
                    },

                body:
                    body.toString()
            }
        );


    let data =
        null;

    try {

        data =
            await response.json();

    }
    catch (error) {

        throw new Error(
            "DreamGrid email endpoint returned invalid data."
        );
    }


    if (
        !response.ok ||
        !data ||
        data.ok !== true
    ) {

        throw new Error(
            data &&
            data.error
                ?
                String(data.error)
                :
                "DreamGrid email request failed."
        );
    }


    return data;
}


function dgCheckedEmailUsers(){

    const selected =
        Array.from(
            document.querySelectorAll(
                ".dg-user-select:checked"
            )
        );


    return selected
        .map(
            function(box){

                const row =
                    box.closest(
                        ".dg-user-row"
                    );

                if (!row) {
                    return null;
                }


                const name =
                    String(
                        row.dataset.userName ||
                        ""
                    ).trim();


                const email =
                    String(
                        row.dataset.userEmail ||
                        ""
                    ).trim();


                const uuid =
                    String(
                        row.dataset.userUuid ||
                        ""
                    ).trim();


                if (
                    !name ||
                    !email ||
                    email === "-"
                ) {
                    return null;
                }


                return {
                    name:
                        name,

                    email:
                        email,

                    uuid:
                        uuid
                };
            }
        )
        .filter(Boolean);
}


function dgOpenEmailComposer(users){

    const existing =
        document.getElementById(
            "dgEmailComposer"
        );

    if (existing) {
        existing.remove();
    }


    const overlay =
        document.createElement(
            "div"
        );

    overlay.id =
        "dgEmailComposer";

    overlay.className =
        "dg-email-overlay";


    overlay.innerHTML =
        '<div class="dg-email-card" role="dialog" aria-modal="true">' +
            '<div class="dg-email-head">' +
                '<div class="dg-email-kicker">DREAMGRID EMAIL</div>' +
                '<h2 class="dg-email-title">Email Checked Users</h2>' +
            '</div>' +

            '<div class="dg-email-body">' +

                '<label class="dg-email-field">' +
                    '<span>Recipients</span>' +
                    '<div id="dgEmailRecipients" class="dg-email-recipients"></div>' +
                '</label>' +

                '<label class="dg-email-field">' +
                    '<span>Subject</span>' +
                    '<input id="dgEmailSubject" class="dg-email-input" type="text" maxlength="200">' +
                '</label>' +

                '<label class="dg-email-field">' +
                    '<span>Message</span>' +
                    '<textarea id="dgEmailMessage" class="dg-email-textarea" maxlength="10000"></textarea>' +
                '</label>' +

                '<div class="dg-email-note">' +
                    'Recipients are sent as BCC, matching DreamGrid behaviour.' +
                '</div>' +

            '</div>' +

            '<div class="dg-email-actions">' +
                '<button id="dgEmailCancel" class="dg-email-action" type="button">CANCEL</button>' +
                '<button id="dgEmailSend" class="dg-email-action send" type="button">SEND EMAIL</button>' +
            '</div>' +
        '</div>';


    document.body.appendChild(
        overlay
    );


    const recipients =
        overlay.querySelector(
            "#dgEmailRecipients"
        );

    const subject =
        overlay.querySelector(
            "#dgEmailSubject"
        );

    const message =
        overlay.querySelector(
            "#dgEmailMessage"
        );

    const cancel =
        overlay.querySelector(
            "#dgEmailCancel"
        );

    const send =
        overlay.querySelector(
            "#dgEmailSend"
        );


    recipients.textContent =
        users
            .map(
                function(user){

                    return (
                        user.name +
                        " <" +
                        user.email +
                        ">"
                    );
                }
            )
            .join("\n");


    function closeComposer(){

        overlay.remove();
    }


    cancel.addEventListener(
        "click",
        closeComposer
    );


    overlay.addEventListener(
        "click",
        function(event){

            if (
                event.target ===
                overlay
            ) {
                closeComposer();
            }
        }
    );


    send.addEventListener(
        "click",
        async function(){

            const emailSubject =
                subject.value.trim();

            const emailMessage =
                message.value.trim();


            if (!emailSubject) {

                await dgEmailAlert(
                    "Enter an email subject."
                );

                subject.focus();

                return;
            }


            if (!emailMessage) {

                await dgEmailAlert(
                    "Enter an email message."
                );

                message.focus();

                return;
            }


            send.disabled =
                true;

            cancel.disabled =
                true;


            try {

                const result =
                    await dgEmailPost({
                        action:
                            "send",

                        recipients:
                            JSON.stringify(
                                users
                            ),

                        subject:
                            emailSubject,

                        message:
                            emailMessage
                    });


                closeComposer();


                await dgEmailAlert(
                    "Email sent successfully to " +
                    String(result.sent || users.length) +
                    " checked user(s)."
                );
            }
            catch (error) {

                send.disabled =
                    false;

                cancel.disabled =
                    false;


                await dgEmailAlert(
                    error &&
                    error.message
                        ?
                        error.message
                        :
                        String(error)
                );
            }
        }
    );


    window.setTimeout(
        function(){

            subject.focus();

        },
        40
    );
}


/* ============================================================
   DREAMGRID REGION MANAGER SMTP SETUP V1
   ============================================================ */

async function dgSmtpSettingsPost(values){

    const body = new URLSearchParams();

    Object.keys(values).forEach(function(key){
        body.set(key, String(values[key]));
    });

    const response =
        await fetch(
            "/Other/admin-region-email-settings.php",
            {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type":
                        "application/x-www-form-urlencoded;charset=UTF-8"
                },
                body: body.toString()
            }
        );

    let data;

    try {
        data = await response.json();
    }
    catch (error) {
        throw new Error(
            "DreamGrid SMTP settings endpoint returned invalid data."
        );
    }

    if (
        !response.ok ||
        !data ||
        data.ok !== true
    ) {
        throw new Error(
            data && data.error
                ? String(data.error)
                : "DreamGrid SMTP settings request failed."
        );
    }

    return data;
}


function dgOpenSmtpNotice(message){

    document.getElementById(
        "dgSmtpNotice"
    )?.remove();


    const overlay =
        document.createElement(
            "div"
        );


    overlay.id =
        "dgSmtpNotice";


    overlay.className =
        "dg-smtp-overlay";


    overlay.innerHTML = `
<div
    class="dg-smtp-notice-card"
    role="dialog"
    aria-modal="true"
    aria-labelledby="dgSmtpNoticeTitle"
    aria-describedby="dgSmtpNoticeMessage">

    <div class="dg-smtp-head">

        <h2 id="dgSmtpNoticeTitle">
            SMTP Send Email Account
        </h2>

    </div>

    <div
        id="dgSmtpNoticeMessage"
        class="dg-smtp-notice-body">
    </div>

    <div class="dg-smtp-notice-actions">

        <button
            id="dgSmtpNoticeOk"
            class="dg-smtp-btn save"
            type="button">
            OK
        </button>

    </div>

</div>
`;


    document.body.appendChild(
        overlay
    );


    overlay.querySelector(
        "#dgSmtpNoticeMessage"
    ).textContent =
        String(
            message || ""
        );


    const ok =
        overlay.querySelector(
            "#dgSmtpNoticeOk"
        );


    return new Promise(
        function(resolve){

            let finished =
                false;


            function closeNotice(){

                if (
                    finished
                ) {
                    return;
                }


                finished =
                    true;


                document.removeEventListener(
                    "keydown",
                    keyHandler
                );


                overlay.remove();


                resolve(
                    true
                );
            }


            function keyHandler(event){

                if (
                    event.key !== "Enter" &&
                    event.key !== "Escape"
                ) {
                    return;
                }


                event.preventDefault();

                closeNotice();
            }


            ok.addEventListener(
                "click",
                closeNotice,
                {
                    once:true
                }
            );


            document.addEventListener(
                "keydown",
                keyHandler
            );


            window.setTimeout(
                function(){

                    ok.focus();
                },
                20
            );
        }
    );
}

async function dgOpenSmtpSetup(){


    let result;

    try {

        result =
            await dgSmtpSettingsPost({
                action: "get"
            });

    }
    catch (error) {

        await dgEmailAlert(
            error && error.message
                ? error.message
                : String(error)
        );

        return;
    }


    const s =
        result.settings || {};


    document.getElementById(
        "dgSmtpSetup"
    )?.remove();


    const overlay =
        document.createElement("div");

    overlay.id =
        "dgSmtpSetup";

    overlay.className =
        "dg-smtp-overlay";


    overlay.innerHTML = `
<div class="dg-smtp-card">

    <div class="dg-smtp-head">
        <h2>SMTP Send Email Account</h2>
    </div>

    <div class="dg-smtp-body">

        <div class="dg-smtp-panel">

            <h3>Enable Email To SMTP</h3>

            <label class="dg-smtp-check">
                <input id="dgSmtpEnabled" type="checkbox">
                Email Enable
            </label>

            <div class="dg-smtp-row">
                <span>User Name</span>
                <input id="dgSmtpUser"
                       class="dg-smtp-input"
                       type="text">
            </div>

            <div class="dg-smtp-row">
                <span>SMTP Password</span>
                <input id="dgSmtpPassword"
                       class="dg-smtp-input"
                       type="password">
            </div>

            <div class="dg-smtp-row">
                <span>SMTP Host</span>
                <input id="dgSmtpHost"
                       class="dg-smtp-input"
                       type="text">
            </div>

            <div class="dg-smtp-row">
                <span>SMTP Port</span>
                <input id="dgSmtpPort"
                       class="dg-smtp-input"
                       type="number"
                       min="1"
                       max="65535">
            </div>

            <h3 style="margin-top:18px">
                Security Options
            </h3>

            <label class="dg-smtp-radio">
                <input name="dgSmtpSecurity"
                       type="radio"
                       value="0">
                None
            </label>

            <label class="dg-smtp-radio">
                <input name="dgSmtpSecurity"
                       type="radio"
                       value="1">
                Automatic
            </label>

            <label class="dg-smtp-radio">
                <input name="dgSmtpSecurity"
                       type="radio"
                       value="2">
                SSL On Connect
            </label>

            <label class="dg-smtp-radio">
                <input name="dgSmtpSecurity"
                       type="radio"
                       value="3">
                Start TLS
            </label>

            <label class="dg-smtp-radio">
                <input name="dgSmtpSecurity"
                       type="radio"
                       value="4">
                Start TLS When Available
            </label>

            <label class="dg-smtp-check">
                <input id="dgSmtpVerify"
                       type="checkbox">
                Verify Certificate
            </label>

            <div id="dgSmtpPasswordNote"
                 class="dg-smtp-note">
            </div>

        </div>


        <div class="dg-smtp-panel">

            <h3>OpenSim Options</h3>

            <label class="dg-smtp-check">
                <input id="dgSmtpToObjects"
                       type="checkbox">
                Email To Objects Enabled
            </label>

            <label class="dg-smtp-check">
                <input id="dgSmtpFromObjects"
                       type="checkbox">
                Email From Objects Enabled
            </label>

            <div class="dg-smtp-row">
                <span>Emails From Owner Per Hour</span>
                <input id="dgSmtpOwnerHour"
                       class="dg-smtp-input"
                       type="number"
                       min="0">
            </div>

            <div class="dg-smtp-row">
                <span>Emails To Prim Address Per Hour</span>
                <input id="dgSmtpPrimHour"
                       class="dg-smtp-input"
                       type="number"
                       min="0">
            </div>

            <div class="dg-smtp-row">
                <span>Emails Per Day</span>
                <input id="dgSmtpPerDay"
                       class="dg-smtp-input"
                       type="number"
                       min="0">
            </div>

            <div class="dg-smtp-row">
                <span>Emails To SMTP Address Per Hour</span>
                <input id="dgSmtpAddressHour"
                       class="dg-smtp-input"
                       type="number"
                       min="0">
            </div>

            <div class="dg-smtp-row">
                <span>Email Pause Time in Seconds</span>
                <input id="dgSmtpPause"
                       class="dg-smtp-input"
                       type="number"
                       min="0">
            </div>

            <div class="dg-smtp-row">
                <span>Max Email Size</span>
                <input id="dgSmtpMax"
                       class="dg-smtp-input"
                       type="number"
                       min="1">
            </div>

        </div>

    </div>

    <div class="dg-smtp-actions">

        <button id="dgSmtpClose"
                class="dg-smtp-btn"
                type="button">
            CLOSE
        </button>

        <button id="dgSmtpSave"
                class="dg-smtp-btn save"
                type="button">
            SAVE SETTINGS
        </button>

    </div>

</div>
`;


    document.body.appendChild(
        overlay
    );


    const enabled =
        overlay.querySelector("#dgSmtpEnabled");

    const user =
        overlay.querySelector("#dgSmtpUser");

    const password =
        overlay.querySelector("#dgSmtpPassword");

    const host =
        overlay.querySelector("#dgSmtpHost");

    const port =
        overlay.querySelector("#dgSmtpPort");

    const verify =
        overlay.querySelector("#dgSmtpVerify");

    const toObjects =
        overlay.querySelector("#dgSmtpToObjects");

    const fromObjects =
        overlay.querySelector("#dgSmtpFromObjects");

    const ownerHour =
        overlay.querySelector("#dgSmtpOwnerHour");

    const primHour =
        overlay.querySelector("#dgSmtpPrimHour");

    const perDay =
        overlay.querySelector("#dgSmtpPerDay");

    const addressHour =
        overlay.querySelector("#dgSmtpAddressHour");

    const pause =
        overlay.querySelector("#dgSmtpPause");

    const maxSize =
        overlay.querySelector("#dgSmtpMax");

    const passwordNote =
        overlay.querySelector("#dgSmtpPasswordNote");

    const close =
        overlay.querySelector("#dgSmtpClose");

    const save =
        overlay.querySelector("#dgSmtpSave");


    enabled.checked =
        Boolean(s.emailEnabled);

    user.value =
        String(
            s.username ||
            "LoginName@somewhere.net"
        );

    host.value =
        String(
            s.host ||
            "smtp.somewhere.net"
        );

    port.value =
        String(
            s.port ||
            587
        );

    verify.checked =
        s.verifyCertificate !== false;

    toObjects.checked =
        Boolean(
            s.emailToObjectsEnabled
        );

    fromObjects.checked =
        Boolean(
            s.emailFromObjectsEnabled
        );

    ownerHour.value =
        String(
            s.mailsFromOwnerPerHour ??
            500
        );

    primHour.value =
        String(
            s.mailsToPrimAddressPerHour ??
            20
        );

    perDay.value =
        String(
            s.mailsPerDay ??
            100
        );

    addressHour.value =
        String(
            s.mailsToSmtpAddressPerHour ??
            10
        );

    pause.value =
        String(
            s.emailPauseTime ??
            20
        );

    maxSize.value =
        String(
            s.maxMailSize ??
            4096
        );


    password.value =
        "";

    password.placeholder =
        s.passwordSet
            ? "Leave blank to keep current password"
            : "Enter SMTP password";


    passwordNote.textContent =
        s.passwordSet
            ? "A password is already stored. Leave blank to keep it."
            : "Enter the SMTP password for this account.";


    const securityValue =
        String(
            Number(
                s.sslType ?? 3
            )
        );


    const securityRadio =
        overlay.querySelector(
            'input[name="dgSmtpSecurity"][value="' +
            securityValue +
            '"]'
        );


    if (securityRadio) {

        securityRadio.checked =
            true;

    }
    else {

        overlay.querySelector(
            'input[name="dgSmtpSecurity"][value="3"]'
        ).checked =
            true;
    }


    function closeSetup(){

        overlay.remove();
    }


    close.addEventListener(
        "click",
        closeSetup
    );


    save.addEventListener(
        "click",
        async function(){

            const security =
                overlay.querySelector(
                    'input[name="dgSmtpSecurity"]:checked'
                );


            if (!user.value.trim()) {

                await dgEmailAlert(
                    "Enter the SMTP user name."
                );

                user.focus();

                return;
            }


            if (!host.value.trim()) {

                await dgEmailAlert(
                    "Enter the SMTP host."
                );

                host.focus();

                return;
            }


            save.disabled =
                true;

            close.disabled =
                true;


            try {

                await dgSmtpSettingsPost({

                    action:
                        "save",

                    emailEnabled:
                        enabled.checked ? "1" : "0",

                    username:
                        user.value.trim(),

                    password:
                        password.value,

                    host:
                        host.value.trim(),

                    port:
                        port.value,

                    sslType:
                        security ? security.value : "3",

                    verifyCertificate:
                        verify.checked ? "1" : "0",

                    emailToObjectsEnabled:
                        toObjects.checked ? "1" : "0",

                    emailFromObjectsEnabled:
                        fromObjects.checked ? "1" : "0",

                    mailsFromOwnerPerHour:
                        ownerHour.value,

                    mailsToPrimAddressPerHour:
                        primHour.value,

                    mailsPerDay:
                        perDay.value,

                    mailsToSmtpAddressPerHour:
                        addressHour.value,

                    emailPauseTime:
                        pause.value,

                    maxMailSize:
                        maxSize.value
                });


                closeSetup();


                await dgEmailAlert(
                    "DreamGrid SMTP settings saved."
                );
            }
            catch (error) {

                save.disabled =
                    false;

                close.disabled =
                    false;


                await dgEmailAlert(
                    error && error.message
                        ? error.message
                        : String(error)
                );
            }
        }
    );
}

emailButton.addEventListener(
    "click",
    async function(){

        if (emailButton.disabled) {
            return;
        }


        emailButton.disabled =
            true;


        try {

            /*
             * DreamGrid checks SMTP configuration before it opens
             * the checked-users email composer.
             */
            const status =
                await dgEmailPost({
                    action:
                        "status"
                });


            if (!status.configured) {

                await dgOpenSmtpNotice(
                    "Email Server is not yet set up."
                );

                await dgOpenSmtpSetup();

                return;
            }


            if (!status.enabled) {

                await dgOpenSmtpNotice(
                    "Email to SMTP is not enabled."
                );

                await dgOpenSmtpSetup();

                return;
            }


            const users =
                dgCheckedEmailUsers();


            if (!users.length) {

                await dgEmailAlert(
                    "No email users selected.\n\n" +
                    "Open Users, check the users you want to email, " +
                    "then click Email again."
                );


                const usersButton =
                    document.getElementById(
                        "dgUsersButton"
                    );

                if (usersButton) {

                    usersButton.click();
                }

                return;
            }


            dgOpenEmailComposer(
                users
            );
        }
        catch (error) {

            await dgEmailAlert(
                error &&
                error.message
                    ?
                    error.message
                    :
                    String(error)
            );
        }
        finally {

            emailButton.disabled =
                false;
        }
    }
);

})();
</script>
<!-- DREAMGRID REGION MANAGER EMAIL V1 END -->
<!-- AUSTRALIA REGION BULK FUNCTIONS V5 -->
<script>
(function(){

"use strict";


const table =
    document.getElementById(
        "dgRegionTable"
    );


if (!table) {
    return;
}


const rows =
    Array.from(
        table.querySelectorAll(
            "tbody tr.dg-row"
        )
    );


const runAllButton =
    document.getElementById(
        "dgRunAllButton"
    );


const stopAllButton =
    document.getElementById(
        "dgStopAllButton"
    );


const restartButton =
    document.getElementById(
        "dgRestartAllButton"
    );


let bulkBusy =
    false;


/* ============================================================
   HELPERS
   ============================================================ */

function regionName(row){

    return String(
        row &&
        row.dataset &&
        row.dataset.name
            ? row.dataset.name
            : ""
    ).trim();
}


function regionState(row){

    return String(
        row &&
        row.dataset &&
        row.dataset.state
            ? row.dataset.state
            : ""
    )
    .trim()
    .toLowerCase();
}


function regionEnabled(row){

    return (
        String(
            row &&
            row.dataset
                ? row.dataset.enabled || ""
                : ""
        ) ===
        "1"
    );
}


function selectedRows(){

    return rows.filter(
        function(row){

            return row.isConnected && row.classList.contains(
                "dg-selected"
            );
        }
    );
}


function setBulkBusy(value){

    bulkBusy =
        value;


    [
        runAllButton,
        stopAllButton,
        restartButton
    ].forEach(
        function(button){

            if (button) {
                button.disabled = value;
            }
        }
    );
}


async function sendCommand(
    command,
    region
){

    const body =
        new URLSearchParams();


    body.set(
        "command",
        command
    );


    if (region) {

        body.set(
            "region",
            region
        );
    }


    const response =
        await fetch(
            "/Other/command.php",
            {
                method:
                    "POST",

                credentials:
                    "same-origin",

                headers: {
                    "Content-Type":
                        "application/x-www-form-urlencoded;charset=UTF-8"
                },

                body:
                    body.toString()
            }
        );


    const raw =
        await response.text();


    let data =
        null;


    try {

        data =
            JSON.parse(
                raw
            );

    }
    catch (error) {

        data =
            null;
    }


    if (
        !response.ok ||
        !data ||
        data.ok !== true
    ) {

        throw new Error(
            data &&
            (
                data.error ||
                data.message
            )
                ?
                (
                    data.error ||
                    data.message
                )
                :
                (
                    raw ||
                    "DreamGrid rejected the command."
                )
        );
    }


    return data;
}


/* ============================================================
   SEQUENTIAL BULK COMMAND
   ============================================================ */

async function bulkCommand(
    command,
    targets,
    heading,
    reloadDelay
){

    if (bulkBusy) {
        return;
    }


    const names =
        targets
            .map(regionName)
            .filter(Boolean);


    if (!names.length) {

        window.alert(
            "There are no matching regions for " +
            heading +
            "."
        );

        return;
    }


    if (
        !await window.auConfirm(
            heading +
            "\n\n" +
            names.join("\n") +
            "\n\nContinue?"
        )
    ) {

        return;
    }


    setBulkBusy(
        true
    );


    const successful =
        [];


    const failed =
        [];


    for (
        const name of
        names
    ) {

        try {

            await sendCommand(
                command,
                name
            );


            successful.push(
                name
            );

        }
        catch (error) {

            failed.push(
                name +
                ": " +
                (
                    error &&
                    error.message
                        ?
                        error.message
                        :
                        String(error)
                )
            );
        }
    }


    let message =
        heading +
        "\n\nSuccessful: " +
        successful.length;


    if (failed.length) {

        message +=
            "\nFailed: " +
            failed.length +
            "\n\n" +
            failed.join("\n");
    }


    /*
     * Successful bulk operations do not need a second popup.
     *
     * The confirmation dialog has already been shown before
     * the operation starts.
     *
     * Only show a result dialog when something actually fails.
     */
    if (failed.length) {

        if (
            typeof window.auAlert ===
            "function"
        ) {

            await window.auAlert(
                message
            );
        }
        else {

            window.alert(
                message
            );
        }


        setBulkBusy(
            false
        );

        return;
    }


    window.setTimeout(
        function(){

            window.location.reload();

        },
        reloadDelay
    );
}


/* ============================================================
   RUN ALL
   DreamGrid native behaviour:
   Runs the CHECKED regions.
   ============================================================ */

if (runAllButton) {

    runAllButton.addEventListener(
        "click",
        function(){

            const selected =
                selectedRows();


            if (!selected.length) {

                window.alert(
                    "Select the regions to run first.\n\n" +
                    "Use the region rows or Select All/None."
                );

                return;
            }


            const targets =
                selected.filter(
                    function(row){

                        return (
                            regionEnabled(row) &&
                            regionState(row) !==
                                "running"
                        );
                    }
                );


            bulkCommand(
                "StartRegion",
                targets,
                "RUN SELECTED REGIONS",
                8000
            );
        }
    );
}


/* ============================================================
   STOP ALL
   DreamGrid native behaviour:
   Stops ALL running regions.
   ============================================================ */

if (stopAllButton) {

    stopAllButton.addEventListener(
        "click",
        function(){

            const targets =
                rows.filter(
                    function(row){

                        return (
                            regionState(row) ===
                            "running"
                        );
                    }
                );


            bulkCommand(
                "StopRegion",
                targets,
                "STOP ALL RUNNING REGIONS",
                12000
            );
        }
    );
}


/* ============================================================
   RESTART
   DreamGrid native behaviour:
   Restarts the CHECKED regions.
   ============================================================ */

if (restartButton) {

    restartButton.addEventListener(
        "click",
        function(){

            const selected =
                selectedRows();


            if (!selected.length) {

                window.alert(
                    "Select the regions to restart first.\n\n" +
                    "Use the region rows or Select All/None."
                );

                return;
            }


            const targets =
                selected.filter(
                    function(row){

                        return (
                            regionState(row) ===
                            "running"
                        );
                    }
                );


            bulkCommand(
                "RestartRegion",
                targets,
                "RESTART SELECTED REGIONS",
                30000
            );
        }
    );
}




/* ============================================================
   PAUSE + EXPORT
   Native Region Manager bulk actions.
   ============================================================ */


const pauseBulkButton =
    document.getElementById(
        "dgPauseButton"
    );


const exportBulkButton =
    document.getElementById(
        "dgExportButton"
    );




function dgBulkRenderPauseIcon(
    paused
){

    if (!pauseBulkButton) {
        return;
    }


    const image =
        pauseBulkButton.querySelector(
            "#dgPauseStateImage"
        );


    if (!image) {
        return;
    }


    if (
        paused
    ) {

        image.src =
            "/Other/assets/icons/sentinel/region-resume-native.png";


        pauseBulkButton.dataset.systemPaused =
            "1";


        pauseBulkButton.title =
            "Resume the selected frozen running regions.";
    }
    else {

        image.src =
            "/Other/assets/icons/sentinel/region-pause-native.png";


        pauseBulkButton.dataset.systemPaused =
            "0";


        pauseBulkButton.title =
            "Pause the selected running regions by freezing them in RAM.";
    }
}

function dgBulkSelectedRows(){

    return rows.filter(
        function(row){

            return row.classList.contains(
                "dg-selected"
            );
        }
    );
}


function dgBulkRegionNames(
    selectedRows
){

    const names =
        [];


    selectedRows.forEach(
        function(row){

            const name =
                String(
                    row.dataset.name ||
                    ""
                ).trim();


            if (
                name &&
                !names.includes(
                    name
                )
            ) {

                names.push(
                    name
                );
            }
        }
    );


    return names;
}


function dgBulkSetButtonText(
    button,
    text
){

    if (
        !button
    ) {
        return;
    }


    const label =
        button.querySelector(
            ".dg-label"
        );


    if (
        label
    ) {

        label.textContent =
            text;
    }
}


async function dgBulkPauseCommand(
    regionName,
    command
){

    if (
        command !== "Freeze" &&
        command !== "Thaw"
    ) {

        throw new Error(
            "Unsupported Pause command: " +
            command
        );
    }


    const body =
        new URLSearchParams();


    body.set(
        "command",
        command
    );


    body.set(
        "region",
        regionName
    );


    const response =
        await fetch(
            "/Other/command.php",
            {
                method:
                    "POST",

                credentials:
                    "same-origin",

                headers:
                    {
                        "Content-Type":
                            "application/x-www-form-urlencoded;charset=UTF-8"
                    },

                body:
                    body.toString()
            }
        );


    const raw =
        await response.text();


    let data =
        null;


    try {

        data =
            JSON.parse(
                raw
            );
    }
    catch (error) {

        data =
            null;
    }


    if (
        !response.ok ||
        !data ||
        data.ok !== true
    ) {

        throw new Error(
            data &&
            (
                data.message ||
                data.error
            )
                ?
                String(
                    data.message ||
                    data.error
                )
                :
                (
                    raw ||
                    (
                        "DreamGrid rejected " +
                        command +
                        " for " +
                        regionName +
                        "."
                    )
                )
        );
    }


    return data;
}



async function dgBulkPauseAlert(
    title,
    message
){

    if (
        typeof window.auAlert ===
        "function"
    ) {

        await window.auAlert(
            {
                title:
                    title,

                message:
                    message,

                confirmText:
                    "OK",

                cancelText:
                    ""
            }
        );

        return;
    }


    window.alert(
        message
    );
}



if (
    pauseBulkButton
) {

    pauseBulkButton.disabled =
        false;


    if (
        typeof dgBulkRenderPauseIcon ===
        "function"
    ) {

        dgBulkRenderPauseIcon(false);
    }


    pauseBulkButton.addEventListener(
        "click",
        async function(){

            if (
                pauseBulkButton.disabled
            ) {
                return;
            }


            const selected =
                dgBulkSelectedRows();


            if (
                !selected.length
            ) {

                await dgBulkPauseAlert(
                    "REGION PAUSE",
                    "Select one or more regions first."
                );

                return;
            }


            const running =
                selected.filter(
                    function(row){

                        return (
                            row.dataset.state ===
                            "running"
                        );
                    }
                );


            if (
                !running.length
            ) {

                await dgBulkPauseAlert(
                    "REGION PAUSE",
                    "None of the selected regions are running."
                );

                return;
            }


            const unfrozen =
                running.filter(
                    function(row){

                        return (
                            row.dataset.frozen !==
                            "1"
                        );
                    }
                );


            const frozen =
                running.filter(
                    function(row){

                        return (
                            row.dataset.frozen ===
                            "1"
                        );
                    }
                );


            /*
             * Toggle behaviour:
             *
             * If ANY selected running region is not frozen,
             * Pause freezes the unfrozen regions.
             *
             * Once ALL selected running regions are frozen,
             * pressing Pause again thaws them.
             */

            const command =
                unfrozen.length
                    ?
                    "Freeze"
                    :
                    "Thaw";


            const targets =
                command === "Freeze"
                    ?
                    unfrozen
                    :
                    frozen;


            if (
                !targets.length
            ) {

                return;
            }


            pauseBulkButton.disabled =
                true;


            const failures =
                [];


            let successCount =
                0;


            try {

                for (
                    const row of targets
                ) {

                    const regionName =
                        String(
                            row.dataset.name ||
                            ""
                        ).trim();


                    if (
                        !regionName
                    ) {

                        failures.push(
                            "Unknown region: region name is missing."
                        );

                        continue;
                    }


                    try {

                        await dgBulkPauseCommand(
                            regionName,
                            command
                        );


                        row.dataset.frozen =
                            command === "Freeze"
                                ?
                                "1"
                                :
                                "0";


                        successCount++;


                        if (
                            typeof dgBulkRenderPauseIcon ===
                            "function"
                        ) {

                            dgBulkRenderPauseIcon(command === "Freeze");
                        }
                    }
                    catch (error) {

                        failures.push(
                            regionName +
                            ": " +
                            (
                                error &&
                                error.message
                                    ?
                                    error.message
                                    :
                                    String(
                                        error
                                    )
                            )
                        );
                    }
                }
            }
            finally {

                pauseBulkButton.disabled =
                    false;


                /*
                 * Native DreamGrid keeps the button label
                 * as Pause. Only the icon/state changes.
                 */

                dgBulkSetButtonText(
                    pauseBulkButton,
                    "Pause"
                );


                if (
                    typeof dgBulkRenderPauseIcon ===
                    "function"
                ) {

                    dgBulkRenderPauseIcon(command === "Freeze");
                }
            }


            if (
                failures.length
            ) {

                await dgBulkPauseAlert(
                    command === "Freeze"
                        ?
                        "REGION PAUSE"
                        :
                        "REGION RESUME",

                    (
                        command === "Freeze"
                            ?
                            "Pause completed with errors."
                            :
                            "Resume completed with errors."
                    ) +
                    "\n\nCompleted: " +
                    successCount +
                    "\n\n" +
                    failures.join(
                        "\n"
                    )
                );
            }
        }
    );
}


/* ============================================================
   EXPORT REGION INI TO DREAMGRID AUTOBACKUP
   ============================================================ */



/* ============================================================
   NATIVE WINDOWS REGION INI EXPORT V4
   ============================================================ */



/* ============================================================
   NATIVE WINDOWS REGION INI EXPORT V5

   Fire-and-return architecture:
   PHP queues the request and launches the interactive Windows
   picker. Region Manager does not wait for the Windows dialog.
   ============================================================ */


async function dgLaunchNativeRegionExport(
    regionNames
){

    const body =
        new URLSearchParams();


    body.set(
        "action",
        "start"
    );


    body.set(
        "regions",
        JSON.stringify(
            regionNames
        )
    );


    const response =
        await fetch(
            "/Other/admin-region-export.php",
            {
                method:
                    "POST",

                credentials:
                    "same-origin",

                headers:
                    {
                        "Content-Type":
                            "application/x-www-form-urlencoded;charset=UTF-8"
                    },

                body:
                    body.toString()
            }
        );


    const raw =
        await response.text();


    let data =
        null;


    try {

        data =
            JSON.parse(
                raw
            );
    }
    catch (error) {

        data =
            null;
    }


    if (
        !response.ok ||
        !data ||
        data.ok !== true
    ) {

        throw new Error(
            data &&
            data.message
                ?
                String(
                    data.message
                )
                :
                (
                    raw ||
                    "Could not open the Windows Region Export folder selector."
                )
        );
    }


    return data;
}



if (
    exportBulkButton
) {

    exportBulkButton.disabled =
        false;


    exportBulkButton.title =
        "Export selected Region INI files using the Windows folder selector.";


    exportBulkButton.addEventListener(
        "click",
        async function(){

            if (
                exportBulkButton.disabled
            ) {
                return;
            }


            const selected =
                dgBulkSelectedRows();


            const regionNames =
                dgBulkRegionNames(
                    selected
                );


            if (
                !regionNames.length
            ) {

                dgBulkSetButtonText(
                    exportBulkButton,
                    "Select Region"
                );


                window.setTimeout(
                    function(){

                        dgBulkSetButtonText(
                            exportBulkButton,
                            "Export"
                        );
                    },
                    1200
                );


                return;
            }


            exportBulkButton.disabled =
                true;


            dgBulkSetButtonText(
                exportBulkButton,
                "Opening..."
            );


            try {

                await dgLaunchNativeRegionExport(
                    regionNames
                );


                dgBulkSetButtonText(
                    exportBulkButton,
                    "Export"
                );


                exportBulkButton.title =
                    "Windows Region Export folder selector opened.";
            }
            catch (error) {

                console.error(
                    error
                );


                dgBulkSetButtonText(
                    exportBulkButton,
                    "Export Failed"
                );


                exportBulkButton.title =
                    error &&
                    error.message
                        ?
                        error.message
                        :
                        String(
                            error
                        );


                window.setTimeout(
                    function(){

                        dgBulkSetButtonText(
                            exportBulkButton,
                            "Export"
                        );
                    },
                    2200
                );
            }
            finally {

                /*
                 * Deliberately release immediately.
                 * We do NOT wait for the Windows dialog.
                 */

                exportBulkButton.disabled =
                    false;
            }
        }
    );
}

window.AustraliaRegionBulkFunctionsV1 = {

    runSelected:
        function(){

            runAllButton?.click();
        },

    stopAll:
        function(){

            stopAllButton?.click();
        },

    restartSelected:
        function(){

            restartButton?.click();
        }
};

})();
</script>
<!-- AUSTRALIA REGION BULK FUNCTIONS V5 END -->

<!-- END DREAMGRID STYLE REGION MANAGER V1 -->

</div>

<link rel="stylesheet" href="/Other/australia-modal.css?v=1">
<style>#auGlobalModal{z-index:2147483500} #auModalMessage{white-space:pre-line}</style>
<script src="/Other/australia-modal.js?v=1"></script>
<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>


<!-- WEB_REGION_TABLE_SCROLL_MEMORY -->

<script src="/Other/assets/js/dg-internal-popup-v1.js?v=<?= filemtime(__DIR__ . '/assets/js/dg-internal-popup-v1.js') ?>"></script>


<script src="/Other/assets/js/dg-region-manager.js?v=<?= filemtime(__DIR__ . '/assets/js/dg-region-manager.js') ?>"></script>
</body>
</html>








