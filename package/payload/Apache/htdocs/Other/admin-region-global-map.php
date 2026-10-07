<?php

require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/bootstrap.php';

ag_no_cache();

$session =
    ag_require_admin();


function dgm_h($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function dgm_true($value): bool
{
    return in_array(
        strtolower(
            trim(
                (string)$value
            )
        ),
        [
            '1',
            'true',
            'yes',
            'on',
            'enabled'
        ],
        true
    );
}


function dgm_ini_value(
    string $raw,
    string $key
): ?string
{
    $pattern =
        '/^[ \t]*' .
        preg_quote(
            $key,
            '/'
        ) .
        '[ \t]*=[ \t]*(.*?)[ \t]*$/mi';

    if (
        !preg_match(
            $pattern,
            $raw,
            $match
        )
    ) {
        return null;
    }

    $value =
        trim(
            (string)$match[1]
        );

    if (
        strlen($value) >= 2 &&
        (
            (
                $value[0] === '"' &&
                $value[strlen($value) - 1] === '"'
            ) ||
            (
                $value[0] === "'" &&
                $value[strlen($value) - 1] === "'"
            )
        )
    ) {
        $value =
            substr(
                $value,
                1,
                -1
            );
    }

    return trim($value);
}


function dgm_section_name(
    string $raw,
    string $fallback
): string
{
    if (
        preg_match(
            '/^[ \t]*\[([^\]]+)\][ \t]*$/m',
            $raw,
            $match
        )
    ) {
        return trim(
            (string)$match[1]
        );
    }

    return $fallback;
}


function dgm_region_files(): array
{
    $root =
        ag_dg_regions_root();

    if (
        $root === null ||
        !is_dir($root)
    ) {
        return [];
    }

    $pattern =
        rtrim(
            $root,
            '/\\'
        ) .
        DIRECTORY_SEPARATOR .
        '*' .
        DIRECTORY_SEPARATOR .
        'Region' .
        DIRECTORY_SEPARATOR .
        '*.ini';

    $files =
        glob(
            $pattern
        ) ?: [];

    $result = [];

    foreach (
        $files
        as $file
    ) {
        if (
            is_file($file) &&
            !preg_match(
                '/\.bak$/i',
                $file
            )
        ) {
            $result[] =
                $file;
        }
    }

    sort(
        $result,
        SORT_NATURAL |
        SORT_FLAG_CASE
    );

    return $result;
}


function dgm_live_statuses(): array
{
    $url =
        rtrim(
            ag_dg_diagnostics_base(),
            '/'
        ) .
        '/?command=regionlist&page=1&rp=500&sortorder=asc';

    $json =
        null;

    if (
        function_exists(
            'curl_init'
        )
    ) {
        $curl =
            curl_init(
                $url
            );

        if ($curl !== false) {

            curl_setopt_array(
                $curl,
                [
                    CURLOPT_RETURNTRANSFER =>
                        true,

                    CURLOPT_CONNECTTIMEOUT =>
                        2,

                    CURLOPT_TIMEOUT =>
                        5
                ]
            );

            $response =
                curl_exec(
                    $curl
                );

            curl_close(
                $curl
            );

            if (
                is_string($response) &&
                trim($response) !== ''
            ) {
                $json =
                    $response;
            }
        }
    }

    if (
        !is_string($json) ||
        trim($json) === ''
    ) {
        $context =
            stream_context_create(
                [
                    'http' =>
                        [
                            'method' =>
                                'GET',

                            'timeout' =>
                                5,

                            'ignore_errors' =>
                                true
                        ]
                ]
            );

        $response =
            @file_get_contents(
                $url,
                false,
                $context
            );

        if (
            is_string($response) &&
            trim($response) !== ''
        ) {
            $json =
                $response;
        }
    }

    if (!is_string($json)) {
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

    $result = [];

    foreach (
        $data['rows']
        as $row
    ) {
        $cell =
            is_array(
                $row['cell'] ??
                null
            )
                ? $row['cell']
                : [];

        $name =
            trim(
                (string)(
                    $cell['RegionName'] ??
                    $cell['Region Name'] ??
                    $cell['Name'] ??
                    ''
                )
            );

        if ($name === '') {
            continue;
        }

        $status =
            trim(
                strip_tags(
                    (string)(
                        $cell['Status'] ??
                        $cell['RegionStatus'] ??
                        'Registered'
                    )
                )
            );

        if ($status === '') {
            $status =
                'Registered';
        }

        $result[
            strtolower($name)
        ] =
            $status;
    }

    return $result;
}



/* dreamgrid-web-deregistered-region-v11 */
function dgm_web_region_is_deregistered(
    string $uuid
): bool
{
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

    $marker =
        ag_dg_path(
            '_WEB_CONTROL',
            'DeregisteredRegions',
            $uuid .
            '.flag'
        );

    return
        is_string(
            $marker
        ) &&
        is_file(
            $marker
        );
}

function dgm_regions(): array
{
    $live =
        dgm_live_statuses();

    $regions = [];

    foreach (
        dgm_region_files()
        as $file
    ) {
        $raw =
            @file_get_contents(
                $file
            );

        if (
            !is_string($raw) ||
            trim($raw) === ''
        ) {
            continue;
        }

        $fallbackName =
            pathinfo(
                $file,
                PATHINFO_FILENAME
            );

        $name =
            dgm_section_name(
                $raw,
                $fallbackName
            );

        if ($name === '') {
            continue;
        }

        /*
         * Start.dll FormMapEditor MoveRegionTo persists:
         *
         * Location=X,Y
         *
         * Location is therefore preferred here.
         * CoordX / CoordY remain the compatibility fallback.
         */

        $location =
            dgm_ini_value(
                $raw,
                'Location'
            );

        $x =
            null;

        $y =
            null;

        if (
            is_string($location) &&
            preg_match(
                '/^\s*(-?\d+)\s*,\s*(-?\d+)\s*$/',
                $location,
                $match
            )
        ) {
            $x =
                $match[1];

            $y =
                $match[2];
        }

        if (
            $x === null ||
            $y === null
        ) {
            $x =
                dgm_ini_value(
                    $raw,
                    'CoordX'
                );

            $y =
                dgm_ini_value(
                    $raw,
                    'CoordY'
                );
        }

        if (
            $x === null ||
            $y === null
        ) {
            continue;
        }

        if (
            !preg_match(
                '/^-?\d+$/',
                (string)$x
            ) ||
            !preg_match(
                '/^-?\d+$/',
                (string)$y
            )
        ) {
            continue;
        }

        $sizeX =
            (int)(
                dgm_ini_value(
                    $raw,
                    'SizeX'
                ) ??
                '256'
            );

        $sizeY =
            (int)(
                dgm_ini_value(
                    $raw,
                    'SizeY'
                ) ??
                '256'
            );

        if ($sizeX < 256) {
            $sizeX = 256;
        }

        if ($sizeY < 256) {
            $sizeY = 256;
        }

        $status =
            $live[
                strtolower(
                    $name
                )
            ] ??
            'Stopped';

        $webRegionUuid =
            trim(
                (string)(
                    dgm_ini_value(
                        $raw,
                        'RegionUUID'
                    ) ??
                    ''
                )
            );

        if (
            $webRegionUuid !== ''
            &&
            dgm_web_region_is_deregistered(
                $webRegionUuid
            )
        ) {
            continue;
        }

        $regions[] =
            [
                'name' =>
                    $name,

                'x' =>
                    (int)$x,

                'y' =>
                    (int)$y,

                'sizeX' =>
                    $sizeX,

                'sizeY' =>
                    $sizeY,

                'cellsX' =>
                    max(
                        1,
                        (int)ceil(
                            $sizeX /
                            256
                        )
                    ),

                'cellsY' =>
                    max(
                        1,
                        (int)ceil(
                            $sizeY /
                            256
                        )
                    ),

                'locked' =>
                    dgm_true(
                        dgm_ini_value(
                            $raw,
                            'Locked'
                        ) ??
                        'False'
                    ),

                'enabled' =>
                    dgm_true(
                        dgm_ini_value(
                            $raw,
                            'Enabled'
                        ) ??
                        'True'
                    ),

                'uuid' =>
                    trim(
                        (string)(
                            dgm_ini_value(
                                $raw,
                                'RegionUUID'
                            ) ??
                            ''
                        )
                    ),

                'group' =>
                    basename(
                        dirname(
                            dirname(
                                $file
                            )
                        )
                    ),

                'status' =>
                    $status
            ];
    }

    usort(
        $regions,
        static function(
            array $a,
            array $b
        ): int {
            return strnatcasecmp(
                $a['name'],
                $b['name']
            );
        }
    );

    return $regions;
}


function dgm_region_exists(
    string $name
): bool
{
    foreach (
        dgm_regions()
        as $region
    ) {
        if (
            strcasecmp(
                $region['name'],
                $name
            ) === 0
        ) {
            return true;
        }
    }

    return false;
}


function dgm_reply(
    array $data,
    int $status = 200
): never
{
    http_response_code(
        $status
    );

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


function dgm_native_command(
    string $command,
    string $region
): array
{
    $machineHash =
        trim(
            (string)(
                ag_dg_setting(
                    'MachineHash'
                ) ??
                ''
            )
        );

    if ($machineHash === '') {
        return [
            false,
            'DreamGrid MachineHash is unavailable.'
        ];
    }

    $url =
        rtrim(
            ag_dg_diagnostics_base(),
            '/'
        ) .
        '/API/?' .
        http_build_query(
            [
                'command' =>
                    $command,

                'password' =>
                    $machineHash,

                'RegionName' =>
                    $region
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        );

    $context =
        stream_context_create(
            [
                'http' =>
                    [
                        'method' =>
                            'GET',

                        'timeout' =>
                            20,

                        'ignore_errors' =>
                            true
                    ]
            ]
        );

    $response =
        @file_get_contents(
            $url,
            false,
            $context
        );

    if ($response === false) {

        /*
         * Native SaveOAR may continue after its HTTP request
         * has timed out, so do not report a false failure.
         */

        if ($command === 'SaveOAR') {
            return [
                false,
                'Save OAR outcome is unconfirmed for ' .
                $region .
                '. The request may still be running; check backups before retrying.'
            ];
        }

        return [
            false,
            'DreamGrid diagnostics API did not respond.'
        ];
    }

    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $match)) $status = (int)$match[1];
    }
    if ($status < 200 || $status >= 300) return [false, 'DreamGrid diagnostics API returned HTTP ' . $status . '.'];

    $response =
        trim(
            (string)$response
        );

    if (
        stripos(
            $response,
            'NAK'
        ) === 0
    ) {
        return [
            false,
            $response
        ];
    }

    return [
        true,
        $command .
        ' ' .
        $region .
        (
            $response !== ''
                ? ': ' .
                    $response
                : ''
        )
    ];
}



function dgm_fetch_outworldz(
    string $url,
    int $timeout = 15
) {
    if (
        stripos(
            $url,
            'https://outworldz.com/'
        ) !== 0
    ) {
        return false;
    }

    if (
        function_exists(
            'curl_init'
        )
    ) {
        foreach (
            [true, false]
            as $verify
        ) {
            $curl =
                curl_init(
                    $url
                );

            if ($curl === false) {
                continue;
            }

            curl_setopt_array(
                $curl,
                [
                    CURLOPT_RETURNTRANSFER =>
                        true,

                    CURLOPT_FOLLOWLOCATION =>
                        true,

                    CURLOPT_CONNECTTIMEOUT =>
                        5,

                    CURLOPT_TIMEOUT =>
                        $timeout,

                    CURLOPT_USERAGENT =>
                        'DreamGrid Global Map',

                    CURLOPT_SSL_VERIFYPEER =>
                        $verify,

                    CURLOPT_SSL_VERIFYHOST =>
                        $verify
                            ? 2
                            : 0
                ]
            );

            $response =
                curl_exec(
                    $curl
                );

            $status =
                (int)curl_getinfo(
                    $curl,
                    CURLINFO_RESPONSE_CODE
                );

            curl_close(
                $curl
            );

            if (
                is_string($response) &&
                $response !== '' &&
                $status >= 200 &&
                $status < 400
            ) {
                return $response;
            }
        }
    }

    foreach (
        [true, false]
        as $verify
    ) {
        $context =
            stream_context_create(
                [
                    'http' =>
                        [
                            'method' =>
                                'GET',

                            'timeout' =>
                                $timeout,

                            'ignore_errors' =>
                                true,

                            'header' =>
                                "User-Agent: DreamGrid Global Map\r\n"
                        ],

                    'ssl' =>
                        [
                            'verify_peer' =>
                                $verify,

                            'verify_peer_name' =>
                                $verify,

                            'allow_self_signed' =>
                                !$verify
                        ]
                ]
            );

        $response =
            @file_get_contents(
                $url,
                false,
                $context
            );

        if (
            is_string($response) &&
            $response !== ''
        ) {
            return $response;
        }
    }

    return false;
}

$mode =
    trim(
        (string)(
            $_GET['mode'] ??
            ''
        )
    );

/*
 * ==========================================================
 * DREAMGRID NATIVE OAR CATALOGUE V1 - PHP
 *
 * Start.dll FormMapEditor.FetchOarList()
 * ==========================================================
 */

if ($mode === 'oars') {

    $catalogueUrl =
        'https://outworldz.com' .
        '/Outworldz_Installer/Endless/RoadJSON.plx';


    $raw =
        dgm_fetch_outworldz(
            $catalogueUrl,
            15
        );


    if (
        $raw === false ||
        trim(
            (string)$raw
        ) === ''
    ) {

        dgm_reply(
            [
                'ok' =>
                    false,

                'error' =>
                    'Available OAR catalogue could not be loaded.'
            ],
            502
        );
    }


    $decoded =
        json_decode(
            (string)$raw,
            true
        );


    if (!is_array($decoded)) {

        dgm_reply(
            [
                'ok' =>
                    false,

                'error' =>
                    'Available OAR catalogue returned invalid JSON.'
            ],
            502
        );
    }


    $baseUrl =
        'https://outworldz.com';


    $oars =
        [];


    foreach ($decoded as $entry) {

        if (!is_array($entry)) {
            continue;
        }


        $path =
            trim(
                (string)(
                    $entry['path'] ??
                    ''
                )
            );


        $imagePath =
            trim(
                (string)(
                    $entry['image'] ??
                    ''
                )
            );


        $oars[] =
            [
                'folder' =>
                    (string)(
                        $entry['folder'] ??
                        ''
                    ),

                'shortname' =>
                    (string)(
                        $entry['shortname'] ??
                        ''
                    ),

                'sizex' =>
                    (int)(
                        $entry['sizex'] ??
                        0
                    ),

                'sizey' =>
                    (int)(
                        $entry['sizey'] ??
                        0
                    ),

                'roadcode' =>
                    array_key_exists(
                        'roadcode',
                        $entry
                    ) &&
                    $entry['roadcode'] !== null
                        ? (string)$entry['roadcode']
                        : null,

                'url' =>
                    $path !== ''
                        ? $baseUrl . $path
                        : null,

                'image' =>
                    $imagePath !== ''
                        ? '/Other/admin-region-global-map.php' .
                            '?mode=oar-image&path=' .
                            rawurlencode(
                                $imagePath
                            )
                        : null
            ];
    }


    dgm_reply(
        [
            'ok' =>
                true,

            'oars' =>
                $oars
        ]
    );
}






/*
 * ==========================================================
 * OAR IMAGE PROXY
 *
 * Keeps the browser on localhost instead of requiring it
 * to contact outworldz.com directly for every thumbnail.
 * ==========================================================
 */

if ($mode === 'oar-image') {

    $imagePath =
        trim(
            (string)(
                $_GET['path'] ??
                ''
            )
        );

    if (
        $imagePath === '' ||
        strpos(
            $imagePath,
            '..'
        ) !== false ||
        preg_match(
            '/[\x00-\x1F\x7F]/',
            $imagePath
        )
    ) {
        http_response_code(400);
        exit;
    }


    /*
     * DreamGrid's OAR catalogue contains more than one
     * image-path format.
     *
     * Accept:
     *
     * /folder/image.jpg
     * folder/image.jpg
     * https://outworldz.com/folder/image.jpg
     * https://www.outworldz.com/folder/image.jpg
     *
     * Still restrict absolute URLs to Outworldz only.
     */

    if (
        preg_match(
            '#^https?://(?:www\.)?outworldz\.com/#i',
            $imagePath
        )
    ) {

        $imageUrl =
            preg_replace(
                '#^http://#i',
                'https://',
                $imagePath
            );
    }
    elseif (
        substr(
            $imagePath,
            0,
            1
        ) === '/'
    ) {

        $imageUrl =
            'https://outworldz.com' .
            $imagePath;
    }
    else {

        $imageUrl =
            'https://outworldz.com/' .
            ltrim(
                $imagePath,
                '/'
            );
    }

    /*
     * cURL/PHP require spaces in HTTP URLs to be escaped.
     * DreamGrid's OAR catalogue contains many filenames
     * with literal spaces.
     */
    $imageUrl =
        str_replace(
            ' ',
            '%20',
            $imageUrl
        );


    $image =
        dgm_fetch_outworldz(
            $imageUrl,
            15
        );

    if (
        !is_string($image) ||
        strlen($image) < 20
    ) {
        http_response_code(404);
        exit;
    }

    $imageInfo =
        function_exists(
            'getimagesizefromstring'
        )
            ? @getimagesizefromstring(
                $image
            )
            : false;

    $mime =
        is_array($imageInfo) &&
        isset($imageInfo['mime']) &&
        is_string($imageInfo['mime']) &&
        stripos(
            $imageInfo['mime'],
            'image/'
        ) === 0
            ? $imageInfo['mime']
            : 'image/jpeg';

    header(
        'Content-Type: ' .
        $mime
    );

    header(
        'Cache-Control: public, max-age=3600'
    );

    echo $image;
    exit;
}

/*
 * ==========================================================
 * NATIVE MAP TILE PROXY
 *
 * DreamGrid/OpenSim:
 * map-1-X-Y-objects.jpg
 * ==========================================================
 */

if ($mode === 'tile') {

    $x =
        filter_input(
            INPUT_GET,
            'x',
            FILTER_VALIDATE_INT
        );

    $y =
        filter_input(
            INPUT_GET,
            'y',
            FILTER_VALIDATE_INT
        );

    if (
        $x === false ||
        $x === null ||
        $y === false ||
        $y === null
    ) {
        http_response_code(400);
        exit;
    }

    $url =
        ag_web_local_base(ag_dg_robust_port()) .
        '/map-1-' .
        $x .
        '-' .
        $y .
        '-objects.jpg';

    $context =
        stream_context_create(
            [
                'http' =>
                    [
                        'method' =>
                            'GET',

                        'timeout' =>
                            5,

                        'ignore_errors' =>
                            true
                    ]
            ]
        );

    $image =
        @file_get_contents(
            $url,
            false,
            $context
        );

    if (
        !is_string($image) ||
        strlen($image) < 100
    ) {
        http_response_code(404);
        exit;
    }

    header(
        'Content-Type: image/jpeg'
    );

    header(
        'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
    );

    echo $image;
    exit;
}


/*
 * ==========================================================
 * REFRESH DATA
 * ==========================================================
 */

if ($mode === 'data') {

    dgm_reply(
        [
            'ok' =>
                true,

            'welcomeRegion' =>
                trim(
                    (string)(
                        ag_dg_setting(
                            'WelcomeRegion'
                        ) ??
                        ''
                    )
                ),

            'regions' =>
                dgm_regions()
        ]
    );
}


/*
 * ==========================================================
 * PROVEN NATIVE REGION COMMANDS
 * ==========================================================
 */

if (
    (
        $_SERVER['REQUEST_METHOD'] ??
        ''
    ) ===
    'POST'
) {

    if (
        function_exists(
            'ag_require_same_origin_post'
        )
    ) {
        ag_require_same_origin_post();
    }

    $command =
        trim(
            (string)(
                $_POST['command'] ??
                ''
            )
        );

    $region =
        trim(
            (string)(
                $_POST['region'] ??
                ''
            )
        );

    $allowed =
        [
            'StartRegion',
            'StopRegion',
            'RestartRegion',
            'SaveOAR'
        ];

    if (
        !in_array(
            $command,
            $allowed,
            true
        )
    ) {
        dgm_reply(
            [
                'ok' =>
                    false,

                'error' =>
                    'Unsupported Global Map command.'
            ],
            400
        );
    }

    if (
        $region === '' ||
        !dgm_region_exists(
            $region
        )
    ) {
        dgm_reply(
            [
                'ok' =>
                    false,

                'error' =>
                    'DreamGrid region was not found.'
            ],
            404
        );
    }

    [
        $ok,
        $message
    ] =
        dgm_native_command(
            $command,
            $region
        );

    dgm_reply(
        $ok
            ? [
                'ok' =>
                    true,

                'message' =>
                    $message
            ]
            : [
                'ok' =>
                    false,

                'error' =>
                    $message
            ],
        $ok
            ? 200
            : 502
    );
}


$regions =
    dgm_regions();

$welcomeRegion =
    trim(
        (string)(
            ag_dg_setting(
                'WelcomeRegion'
            ) ??
            ''
        )
    );

?>
<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Global Map</title>

<style id="dreamgrid-formmapeditor-native-layout-v2b">

:root{
    --black:#050706;
    --panel:#0a0c0b;
    --panel2:#111310;
    --gold:#a87b25;
    --gold2:#e2b64f;
    --border:#5d512f;
    --text:#dedbd2;
    --muted:#8f918b;
    --mapblue:#24516b;
    --blue:#0788da;
}

*{
    box-sizing:border-box;
}

html,
body{
    width:100%;
    height:100%;
    margin:0;
}

body{
    padding:10px;

    overflow:hidden;

    background:#050706;

    color:var(--text);

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;
}

.dg-window{
    width:100%;
    height:100%;

    min-width:1080px;
    min-height:620px;

    display:grid;

    grid-template-rows:
        36px
        minmax(0,1fr);

    border:
        1px solid
        var(--border);

    background:#090b09;
}

.dg-title{
    display:flex;

    align-items:center;

    padding:
        0 13px;

    border-bottom:
        1px solid
        #473c21;

    background:
        linear-gradient(
            180deg,
            #171914,
            #0d0f0d
        );

    color:#f1cc72;

    font-size:15px;
    font-weight:800;
}

.dg-body{
    min-height:0;

    display:grid;

    grid-template-columns:
        300px
        minmax(520px,1fr)
        324px;

    gap:8px;

    padding:8px;
}


/* =========================================================
   LEFT - NATIVE FORMMAPEDITOR ORDER
   ========================================================= */

.dg-left{
    min-height:0;

    display:grid;

    grid-template-rows:
        20px
        27px
        154px
        199px
        31px
        minmax(110px,1fr);

    gap:7px;
}

.dg-help{
    display:flex;
    align-items:center;

    color:#ddd;
    font-size:11px;
}

.dg-help span:first-child{
    margin-right:5px;
    color:#72aee1;
}

.dg-search{
    display:grid;

    grid-template-columns:
        52px
        minmax(0,1fr);

    gap:7px;

    align-items:center;
}

.dg-search label{
    font-size:11px;
}

.dg-search input{
    width:100%;
    height:25px;

    padding:
        0 7px;

    border:
        1px solid
        #55574f;

    background:#050706;
    color:#ddd;

    outline:none;
}

.dg-region-list{
    min-height:0;

    overflow:auto;

    border:
        1px solid
        #55574f;

    background:#050706;
}

.dg-region{
    display:block;

    width:100%;

    padding:
        2px 5px;

    border:0;

    background:transparent;

    color:#e1e1dc;

    text-align:left;

    font-size:12px;
    line-height:1.1;

    cursor:pointer;
}

.dg-region:hover{
    background:#202018;
}

.dg-region.selected{
    background:#0b82d3;
    color:white;
}


/* =========================================================
   LEFT CONTROLS
   ========================================================= */

.dg-controls{
    display:grid;

    grid-template-columns:
        88px
        minmax(0,1fr);

    gap:10px;
}

.dg-command-stack{
    display:grid;

    grid-template-rows:
        repeat(5,31px);

    gap:7px;
}

.dg-button{
    height:31px;

    padding:
        0 7px;

    border:
        1px solid
        #4e504a;

    border-radius:2px;

    background:
        linear-gradient(
            180deg,
            #292a25 0,
            #20211d 7px,
            #111310 8px,
            #0d0f0d 100%
        );

    color:#ddd;

    font-size:11px;
    font-weight:600;

    cursor:pointer;
}

.dg-button:hover:not(:disabled){
    border-color:#c18f2c;
    color:#ffe3a0;
}

.dg-button:disabled{
    opacity:.38;
    cursor:not-allowed;
}

.dg-pad-side{
    display:grid;

    grid-template-rows:
        150px
        31px;

    gap:7px;
}

.dg-pad{
    width:150px;

    display:grid;

    grid-template-columns:
        repeat(3,42px);

    grid-template-rows:
        repeat(3,42px);

    gap:
        7px 8px;

    justify-content:center;
}

.dg-nav{
    width:42px;
    height:42px;

    padding:0;

    border:
        1px solid
        #0b7bbb;

    border-radius:50%;

    background:
        radial-gradient(
            circle at 36% 28%,
            #28adf5,
            #0786d4 55%,
            #0065aa
        );

    color:white;

    font-size:24px;
    font-weight:900;

    line-height:40px;

    text-align:center;

    cursor:pointer;

    box-shadow:
        inset 0 1px 2px
        rgba(255,255,255,.35),
        0 1px 2px
        rgba(0,0,0,.45);
}

.dg-nav:hover{
    filter:brightness(1.12);
}

.dg-pad-bottom{
    display:grid;

    grid-template-columns:
        1fr
        1fr;

    gap:9px;
}

.dg-bottom-buttons{
    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:9px;
}

.dg-log{
    min-height:0;

    overflow:auto;

    padding:6px;

    border:
        1px solid
        #55574f;

    background:#050706;

    color:#d5d5d0;

    font:
        10px
        Consolas,
        monospace;

    white-space:pre-wrap;
}


/* =========================================================
   CENTER MAP
   ========================================================= */

.dg-centre{
    min-width:0;
    min-height:0;

    display:grid;

    grid-template-rows:
        56px
        minmax(0,1fr);

    gap:8px;
}

.dg-top-map-icons{
    display:flex;

    justify-content:center;
    align-items:center;

    gap:22px;
}

.dg-top-map-icon{
    width:50px;
    height:50px;

    display:flex;

    justify-content:center;
    align-items:center;

    color:#1498e7;

    font-size:42px;
    font-weight:900;

    line-height:1;

    text-shadow:
        0 1px 2px
        #003d62;

    user-select:none;
}

.dg-map{
    position:relative;

    min-width:0;
    min-height:0;

    overflow:hidden;

    border:
        1px solid
        #45545c;

    background:
        var(--mapblue);
}


/*
   Every OpenSim 256x256 map cell is one CSS cell.
   Large regions are composites, not stretched single images.
*/

.dg-footprint{
    position:absolute;

    overflow:hidden;

    background:
        #18394a;

    cursor:pointer;
}

.dg-footprint.selected{
    outline:
        2px solid
        #f0c24d;

    z-index:20;
}

.dg-composite{
    position:absolute;
    inset:0;

    width:100%;
    height:100%;

    display:grid;
}

.dg-composite img{
    display:block;

    width:100%;
    height:100%;

    min-width:0;
    min-height:0;

    object-fit:cover;
}


/* =========================================================
   RIGHT - AVAILABLE OARS
   ========================================================= */
.dg-oars{
    min-width:0;
    min-height:0;

    display:grid;

    grid-template-rows:
        25px
        minmax(0,1fr);
}
.dg-oars-title{
    display:flex;

    align-items:center;
    justify-content:center;

    font-size:11px;
}
.dg-oars-body{
    min-width:0;
    min-height:0;

    display:grid;

    grid-template-columns:
        38px
        minmax(0,1fr);

    gap:6px;
}
.dg-oar-scroll{
    min-height:0;

    display:grid;

    grid-template-rows:
        58px
        minmax(0,1fr)
        58px;

    align-items:center;
    justify-items:center;
}
.dg-oar-arrow{
    width:38px;
    height:58px;

    display:flex;

    align-items:center;
    justify-content:center;

    cursor:pointer;
    user-select:none;
}
.dg-oar-thumb{
    width:8px;
    height:72px;

    align-self:center;

    border:
        1px solid
        #665427;

    border-radius:4px;

    background:
        linear-gradient(
            90deg,
            #111310,
            #55564e,
            #111310
        );
}
.dg-oar-grid{
    min-width:0;
    min-height:0;

    display:grid;

    grid-template-columns:
        repeat(2,128px);

    grid-auto-rows:
        128px;

    align-content:start;
    justify-content:start;

    overflow:auto;

    background:#050706;

    border:
        1px solid
        #484a44;
}
.dg-oar-cell{
    box-sizing:border-box;

    position:relative;

    width:128px;
    height:128px;

    overflow:hidden;

    border:
        1px solid
        #2d302b;

    background:#0a0c0b;
}

/* DREAMGRID NATIVE OAR CATALOGUE V1 - CSS */

.dg-oar-cell img{
    display:block;

    width:100%;
    height:100%;

    object-fit:contain;

    user-select:none;
    pointer-events:none;
}

.dg-oar-cell.dg-oar-ready{
    cursor:grab;
}

.dg-oar-cell.dg-oar-ready:active{
    cursor:grabbing;
}

.dg-oar-cell.dg-oar-selected{
    outline:
        2px solid
        #e2b64f;

    outline-offset:-2px;

    z-index:2;
}

.dg-oar-cell:focus-visible{
    outline:
        2px solid
        #c18f2c;

    outline-offset:-2px;
}

.dg-oar-cell.dg-oar-blank{
    cursor:default;
}





/*
   Unproven native catalogue data is deliberately not replaced
   by a different backup source.
*/

@media(max-width:1150px){

    body{
        overflow:auto;
    }

    .dg-window{
        min-width:1080px;
        min-height:680px;
    }
}


/*
 ============================================================
 DREAMGRID NATIVE MAP GRID FIT V4
 ============================================================
*/

html,
body{
    width:100% !important;
    height:100% !important;

    max-width:100vw !important;
    max-height:100vh !important;

    overflow:hidden !important;
}

body{
    padding:6px !important;
}

.dg-window{
    width:100% !important;
    height:100% !important;

    min-width:0 !important;
    min-height:0 !important;

    max-width:100% !important;
    max-height:100% !important;

    overflow:hidden !important;
}

.dg-body{
    width:100% !important;

    min-width:0 !important;
    min-height:0 !important;

    overflow:hidden !important;

    padding:6px !important;
    gap:6px !important;

    grid-template-columns:
        minmax(245px,23%)
        minmax(0,1fr)
        minmax(324px,24%) !important;
}

.dg-left,
.dg-centre,
.dg-oars,
.dg-oars-body,
.dg-oar-grid{
    min-width:0 !important;
    max-width:100% !important;
}


/*
 ------------------------------------------------------------
 ACTUAL DREAMGRID CELLS
 ------------------------------------------------------------
*/

.dg-map{
    background:
        #17394a !important;

    background-image:
        none !important;

    overflow:
        hidden !important;
}

.dg-native-map-cell-v4{
    position:absolute;

    overflow:hidden;

    box-sizing:border-box;

    background:
        #2c6079;

    cursor:pointer;

    z-index:1;
}

.dg-native-map-cell-v4 img{
    position:absolute;

    inset:0;

    display:block;

    width:100%;
    height:100%;

    min-width:0;
    min-height:0;

    object-fit:cover;
}


/*
 * The map's own dark background is visible in the one-pixel
 * space between these cells. That is the actual cell grid.
 */

.dg-native-map-cell-v4:hover{
    filter:
        brightness(1.05);
}


/*
 ------------------------------------------------------------
 SELECTED REGION OUTER EDGE
 ------------------------------------------------------------
*/

.dg-selected-n-v4{
    border-top:
        2px solid
        #e2b64f;
}

.dg-selected-e-v4{
    border-right:
        2px solid
        #e2b64f;
}

.dg-selected-s-v4{
    border-bottom:
        2px solid
        #e2b64f;
}

.dg-selected-w-v4{
    border-left:
        2px solid
        #e2b64f;
}


/*
 ------------------------------------------------------------
 REMAINING MAP ARROW ICONS
 ------------------------------------------------------------
*/

.dg-map-control-icon{
    display:block;

    width:30px;
    height:30px;

    object-fit:contain;

    pointer-events:none;
}

.dg-map-zoom-icon{
    display:block;

    width:24px;
    height:24px;

    object-fit:contain;

    pointer-events:none;
}

.dg-map-zoom-down{
    transform:
        rotate(180deg);
}

.dg-map-rotate-left{
    transform:
        scaleX(-1);
}

.dg-map-rotate-centre{
    transform:
        rotate(90deg);
}


/*
 * Keep these controls as their existing clickable elements.
 */

.dg-top-map-icon,
.dg-oar-arrow{
    display:flex !important;

    align-items:center !important;
    justify-content:center !important;

    cursor:pointer !important;
}


/*
 ------------------------------------------------------------
 SMALLER VIEWPORT
 ------------------------------------------------------------
*/

@media(max-width:1150px){

    html,
    body{
        overflow:hidden !important;
    }

    .dg-window{
        min-width:0 !important;
        min-height:0 !important;
    }

    .dg-body{
        grid-template-columns:
            minmax(235px,24%)
            minmax(0,1fr)
            minmax(324px,24%) !important;
    }
}

/* END DREAMGRID NATIVE MAP GRID FIT V4 */

/*
 ============================================================
 DREAMGRID SENTINEL MAP NAV ICONS V1
 ============================================================
*/

.dg-nav{
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
}

.dg-map-direction-icon{
    display:block;

    width:22px;
    height:22px;

    object-fit:contain;

    pointer-events:none;

    transform-origin:
        50% 50%;
}

.dg-map-home-icon{
    display:block;

    width:25px;
    height:25px;

    object-fit:contain;

    pointer-events:none;
}


/*
 * Shared NORTH-pointing Sentinel arrow rotated for
 * the eight DreamGrid movement directions.
 */

.dg-dir-n{
    transform:rotate(0deg);
}

.dg-dir-ne{
    transform:rotate(45deg);
}

.dg-dir-e{
    transform:rotate(90deg);
}

.dg-dir-se{
    transform:rotate(135deg);
}

.dg-dir-s{
    transform:rotate(180deg);
}

.dg-dir-sw{
    transform:rotate(225deg);
}

.dg-dir-w{
    transform:rotate(270deg);
}

.dg-dir-nw{
    transform:rotate(315deg);
}

/* END DREAMGRID SENTINEL MAP NAV ICONS V1 */

/*
 ============================================================
 DREAMGRID BLUE MAP CONTROL ICONS V1
 ============================================================
*/

.dg-map-control-icon{
    width:42px !important;
    height:42px !important;

    display:block !important;

    object-fit:contain !important;

    filter:none !important;

    pointer-events:none !important;
}

.dg-map-zoom-icon{
    width:32px !important;
    height:32px !important;

    display:block !important;

    object-fit:contain !important;

    filter:none !important;

    pointer-events:none !important;
}


/*
 * Existing functional direction transforms remain:
 *
 * dg-map-zoom-down     = rotate(180deg)
 * dg-map-rotate-left  = scaleX(-1)
 *
 * Centre uses its own proper circular icon now.
 */

.dg-map-rotate-centre{
    transform:none !important;
}

/* END DREAMGRID BLUE MAP CONTROL ICONS V1 */

/*
 ============================================================
 DREAMGRID CUSTOM PNG MAP ICON PACK V3B
 ============================================================
*/

#dgNW img,
#dgN img,
#dgNE img,
#dgW img,
#dgHome img,
#dgE img,
#dgSW img,
#dgS img,
#dgSE img{
    width:31px !important;
    height:31px !important;
    display:block !important;
    object-fit:contain !important;
    object-position:center !important;
    transform:none !important;
    filter:none !important;
    pointer-events:none !important;
    user-select:none !important;
}


#dgZoomIn img,
#dgZoomOut img{
    width:38px !important;
    height:38px !important;
    display:block !important;
    object-fit:contain !important;
    object-position:center !important;
    transform:none !important;
    filter:none !important;
    pointer-events:none !important;
    user-select:none !important;
}


#dgRotate90 img,
#dgRotate180 img,
#dgRotateMinus90 img{
    width:52px !important;
    height:52px !important;
    display:block !important;
    object-fit:contain !important;
    object-position:center !important;
    transform:none !important;
    filter:none !important;
    pointer-events:none !important;
    user-select:none !important;
}


/* END DREAMGRID CUSTOM PNG MAP ICON PACK V3B */

/*
 ============================================================
 DREAMGRID CHARCOAL MOVEMENT BUTTONS V1

 Replaces only the blue circular background on the
 3x3 movement / Home pad.

 Icons, IDs and JavaScript behaviour are unchanged.
 ============================================================
*/

#dgNW,
#dgN,
#dgNE,
#dgW,
#dgHome,
#dgE,
#dgSW,
#dgS,
#dgSE{
    background:
        linear-gradient(
            180deg,
            #292a25 0,
            #20211d 7px,
            #111310 8px,
            #0d0f0d 100%
        ) !important;

    border:
        1px solid #665427 !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.08),
        inset 0 -2px 4px rgba(0,0,0,0.72),
        0 1px 2px rgba(0,0,0,0.85) !important;

    color:
        #dedbd2 !important;

    border-radius:
        50% !important;

    transition:
        border-color 120ms ease,
        box-shadow 120ms ease,
        background 120ms ease !important;
}


#dgNW:hover,
#dgN:hover,
#dgNE:hover,
#dgW:hover,
#dgHome:hover,
#dgE:hover,
#dgSW:hover,
#dgS:hover,
#dgSE:hover{
    background:
        linear-gradient(
            180deg,
            #31322c 0,
            #25261f 7px,
            #151711 8px,
            #0e100d 100%
        ) !important;

    border-color:
        #c18f2c !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.10),
        inset 0 -2px 4px rgba(0,0,0,0.75),
        0 0 7px rgba(193,143,44,0.24) !important;
}


#dgNW:active,
#dgN:active,
#dgNE:active,
#dgW:active,
#dgHome:active,
#dgE:active,
#dgSW:active,
#dgS:active,
#dgSE:active{
    background:
        linear-gradient(
            180deg,
            #111310 0,
            #0b0d0b 100%
        ) !important;

    border-color:
        #e2b64f !important;

    box-shadow:
        inset 0 2px 5px rgba(0,0,0,0.90),
        0 0 5px rgba(226,182,79,0.22) !important;
}


#dgNW:focus-visible,
#dgN:focus-visible,
#dgNE:focus-visible,
#dgW:focus-visible,
#dgHome:focus-visible,
#dgE:focus-visible,
#dgSW:focus-visible,
#dgS:focus-visible,
#dgSE:focus-visible{
    outline:
        1px solid #e2b64f !important;

    outline-offset:
        2px !important;
}


/* END DREAMGRID CHARCOAL MOVEMENT BUTTONS V1 */

/*
 * DREAMGRID AVAILABLE OARS WIDTH V1
 * Right column minimum = 324px
 * OAR cells remain native 2 x 128px
 */
</style>

<link rel="stylesheet" href="/Other/australia-modal.css">
</head>

<body>

<div class="dg-window">

    <div class="dg-title">
        Global Map
    </div>


    <main class="dg-body">

        <!-- LEFT -->

        <section class="dg-left">

            <div class="dg-help">
                <span>❔</span>
                <span>Help</span>
            </div>


            <div class="dg-search">

                <label for="dgSearch">
                    Search
                </label>

                <input
                    id="dgSearch"
                    type="search"
                    autocomplete="off">

            </div>


            <div
                id="dgRegionList"
                class="dg-region-list">
            </div>


            <div class="dg-controls">

                <div class="dg-command-stack">

                    <button
                        id="dgConsole"
                        class="dg-button"
                        type="button">
                        Console
                    </button>

                    <button
                        id="dgEdit"
                        class="dg-button"
                        type="button">
                        Edit
                    </button>

                    <button
                        id="dgStart"
                        class="dg-button"
                        type="button">
                        Start
                    </button>

                    <button
                        id="dgRestart"
                        class="dg-button"
                        type="button">
                        Restart
                    </button>

                    <button
                        id="dgStop"
                        class="dg-button"
                        type="button">
                        Stop
                    </button>

                </div>


                <div class="dg-pad-side">

                    <div class="dg-pad">

                        <button id="dgNW" class="dg-nav" type="button" title="North West"><img class="dg-map-direction-icon dg-dir-nw" src="/Other/assets/icons/sentinel/map-arrow-nw.png" alt="" aria-hidden="true" draggable="false"></button>
                        <button id="dgN" class="dg-nav" type="button" title="North"><img class="dg-map-direction-icon dg-dir-n" src="/Other/assets/icons/sentinel/map-arrow-up.png" alt="" aria-hidden="true" draggable="false"></button>
                        <button id="dgNE" class="dg-nav" type="button" title="North East"><img class="dg-map-direction-icon dg-dir-ne" src="/Other/assets/icons/sentinel/map-arrow-ne.png" alt="" aria-hidden="true" draggable="false"></button>

                        <button id="dgW" class="dg-nav" type="button" title="West"><img class="dg-map-direction-icon dg-dir-w" src="/Other/assets/icons/sentinel/map-arrow-left.png" alt="" aria-hidden="true" draggable="false"></button>
                        <button id="dgHome" class="dg-nav" type="button" title="Home / Center"><img class="dg-map-home-icon" src="/Other/assets/icons/sentinel/map-home.png" alt="" aria-hidden="true" draggable="false"></button>
                        <button id="dgE" class="dg-nav" type="button" title="East"><img class="dg-map-direction-icon dg-dir-e" src="/Other/assets/icons/sentinel/map-arrow-right.png" alt="" aria-hidden="true" draggable="false"></button>

                        <button id="dgSW" class="dg-nav" type="button" title="South West"><img class="dg-map-direction-icon dg-dir-sw" src="/Other/assets/icons/sentinel/map-arrow-sw.png" alt="" aria-hidden="true" draggable="false"></button>
                        <button id="dgS" class="dg-nav" type="button" title="South"><img class="dg-map-direction-icon dg-dir-s" src="/Other/assets/icons/sentinel/map-arrow-down.png" alt="" aria-hidden="true" draggable="false"></button>
                        <button id="dgSE" class="dg-nav" type="button" title="South East"><img class="dg-map-direction-icon dg-dir-se" src="/Other/assets/icons/sentinel/map-arrow-se.png" alt="" aria-hidden="true" draggable="false"></button>

                    </div>


                    <div class="dg-pad-bottom">

                        <button
                            id="dgAdd"
                            class="dg-button"
                            type="button"
                            disabled
                            title="Select an empty map square first.">
                            Add
                        </button>

                        <button
                            id="dgRefresh"
                            class="dg-button"
                            type="button">
                            Refresh
                        </button>

                    </div>

                </div>

            </div>


            <div class="dg-bottom-buttons">

                <button
                    class="dg-button"
                    type="button"
                    disabled
                    title="Native FormMapEditor zoom behavior has not been substituted.">
                    Zoom
                </button>

                <button
                    id="dgLoadOar"
                    class="dg-button"
                    type="button"
                    disabled
                    title="Load the selected DreamGrid OAR onto the selected region.">
                    Load OAR
                </button>

                <button
                    id="dgSaveOar"
                    class="dg-button"
                    type="button">
                    Save OAR
                </button>

            </div>


            <div
                id="dgLog"
                class="dg-log">
            </div>

        </section>


        <!-- CENTER -->

        <section class="dg-centre">

            <div class="dg-top-map-icons">

                <div
                    id="dgRotate90"
                    class="dg-top-map-icon"
                    title="Rotate selected region +90 degrees">
                    <img
                        class="dg-map-control-icon"
                        src="/Other/assets/icons/sentinel/map-rotate-90.png"
                        alt=""
                        aria-hidden="true"
                        draggable="false">
                </div>
                <div
                    id="dgRotate180"
                    class="dg-top-map-icon"
                    title="Rotate selected region 180 degrees">
                    <img
                        class="dg-map-control-icon dg-map-rotate-centre"
                        src="/Other/assets/icons/sentinel/map-rotate-180.png"
                        alt=""
                        aria-hidden="true"
                        draggable="false">
                </div>
                <div
                    id="dgRotateMinus90"
                    class="dg-top-map-icon"
                    title="Rotate selected region -90 degrees">
                    <img
                        class="dg-map-control-icon dg-map-rotate-left"
                        src="/Other/assets/icons/sentinel/ap-rotate-minus90.png"
                        alt=""
                        aria-hidden="true"
                        draggable="false">
                </div>

            </div>


            <div
                id="dgMap"
                class="dg-map">
            </div>

        </section>


        <!-- RIGHT -->

        <section class="dg-oars">

            <!-- DREAMGRID NATIVE OAR CATALOGUE V1 - HTML -->

            <div class="dg-oars-title">
                Available OARs
            </div>

            <div class="dg-oars-body">

                <div class="dg-oar-scroll">

                    <div
                        id="dgZoomIn"
                        class="dg-oar-arrow"
                        title="Zoom in">
                        <img
                            class="dg-map-zoom-icon"
                            src="/Other/assets/icons/sentinel/map-arrow-up.png"
                            alt=""
                            aria-hidden="true"
                            draggable="false">
                    </div>

                    <div class="dg-oar-thumb"></div>

                    <div
                        id="dgZoomOut"
                        class="dg-oar-arrow"
                        title="Zoom out">
                        <img
                            class="dg-map-zoom-icon dg-map-zoom-down"
                            src="/Other/assets/icons/sentinel/map-arrow-down.png"
                            alt=""
                            aria-hidden="true"
                            draggable="false">
                    </div>

                </div>

                <div
                    id="dgOarGrid"
                    class="dg-oar-grid"
                    role="grid"
                    aria-label="Available OARs">
                </div>

            </div>

        </section>

    </main>

</div>


<script src="/Other/australia-modal.js"></script>
<script>

(function(){

    "use strict";


    /*
     * DREAMGRID START.DLL FORMMAPEDITOR NATIVE LAYOUT V2B
     */


    let regions =
        <?=json_encode(
            $regions,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        )?>;


    const welcomeRegion =
        <?=json_encode(
            $welcomeRegion,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        )?>;


    /* DREAMGRID START.DLL MAP CONTROLS V2 */
    let gridSize = 7;
    const MIN_GRID_SIZE = 1;
    const MAX_GRID_SIZE = 19;


    let regionActionPending = false;
    let refreshRequest = null;

    let selected =
        null;


    /*
     * ========================================================
     * DREAMGRID NATIVE BLANK TILE SELECTION V1
     *
     * Native FormMapEditor keeps a separate selected blank
     * tile. Add is disabled until an empty grid square has
     * been selected.
     * ========================================================
     */

    let selectedBlankCell =
        null;


    let centreX =
        0;


    let centreY =
        0;


    const list =
        document.getElementById(
            "dgRegionList"
        );


    const search =
        document.getElementById(
            "dgSearch"
        );


    const map =
        document.getElementById(
            "dgMap"
        );


    const logBox =
        document.getElementById(
            "dgLog"
        );


    const consoleButton =
        document.getElementById(
            "dgConsole"
        );


    const editButton =
        document.getElementById(
            "dgEdit"
        );


    const startButton =
        document.getElementById(
            "dgStart"
        );


    const restartButton =
        document.getElementById(
            "dgRestart"
        );


    const stopButton =
        document.getElementById(
            "dgStop"
        );


    const loadOarButton =
        document.getElementById(
            "dgLoadOar"
        );


    const saveOarButton =
        document.getElementById(
            "dgSaveOar"
        );


    function normalise(value){

        return String(
            value ||
            ""
        )
        .trim()
        .toLowerCase();
    }


    function writeLog(message){

        if (
            logBox.textContent &&
            !logBox.textContent.endsWith(
                "\n"
            )
        ) {
            logBox.textContent +=
                "\n";
        }


        logBox.textContent +=
            String(
                message ||
                ""
            );


        logBox.scrollTop =
            logBox.scrollHeight;
    }


    function findRegion(name){

        const wanted =
            normalise(
                name
            );


        return (
            regions.find(
                function(region){

                    return (
                        normalise(
                            region.name
                        ) ===
                        wanted
                    );
                }
            ) ||
            null
        );
    }


    function regionCentre(region){

        /*
         * ====================================================
         * DREAMGRID NATIVE REGION CENTER RESTORED V1
         * ====================================================
         *
         * Start.dll FormMapEditor:
         *
         * SetCenter(region)
         *     Xloc = Coord_X
         *     Yloc = Coord_Y
         *
         * Do not substitute the centre of a VarRegion.
         * Native DreamGrid centres using the base coordinate.
         */

        return {

            x:
                Math.trunc(
                    Number(
                        region.x
                    )
                ),

            y:
                Math.trunc(
                    Number(
                        region.y
                    )
                )
        };
    }








    function updateButtons(){

        const active =
            !!selected && !regionActionPending;


        const addButton =
            document.getElementById(
                "dgAdd"
            );


        if (addButton) {

            addButton.disabled =
                !selectedBlankCell;
        }


        consoleButton.disabled =
            !active;


        editButton.disabled =
            !active;


        startButton.disabled =
            !active;


        restartButton.disabled =
            !active;


        stopButton.disabled =
            !active;


        loadOarButton.disabled =
            !(
                active &&
                dgSelectedOarName &&
                dgOarData.has(
                    dgSelectedOarName
                )
            );


        saveOarButton.disabled =
            !active;
    }


    function renderList(){

        const query =
            normalise(
                search.value
            );


        list.innerHTML =
            "";


        regions.forEach(
            function(region){

                if (
                    query &&
                    !normalise(
                        region.name
                    )
                    .includes(
                        query
                    )
                ) {
                    return;
                }


                const button =
                    document.createElement(
                        "button"
                    );


                button.type =
                    "button";


                button.className =
                    "dg-region" +
                    (
                        selected &&
                        selected.name ===
                        region.name
                            ? " selected"
                            : ""
                    );


                button.textContent =
                    region.name;


                button.addEventListener(
                    "click",
                    function(){

                        selectRegion(
                            region,
                            true
                        );
                    }
                );


                list.appendChild(
                    button
                );
            }
        );
    }


    /*
 * ==========================================================
 * DREAMGRID NATIVE OAR CATALOGUE V1 - JS
 * ==========================================================
 */

    const dgOarGrid =
        document.getElementById(
            "dgOarGrid"
        );

    const dgOarData =
        new Map();

    let dgSelectedOarName =
        "";


    function clearOarSelection(){

        dgSelectedOarName =
            "";

        if (!dgOarGrid) {
            return;
        }

        dgOarGrid
            .querySelectorAll(
                ".dg-oar-selected"
            )
            .forEach(
                function(cell){

                    cell.classList.remove(
                        "dg-oar-selected"
                    );
                }
            );
    }


    function selectOarCell(
        cell,
        shortName
    ){

        if (
            !cell ||
            !shortName
        ) {
            return;
        }

        clearOarSelection();

        dgSelectedOarName =
            shortName;

        cell.classList.add(
            "dg-oar-selected"
        );

        writeLog(
            "Selected OAR " +
            shortName
        );

        updateButtons();
    }


    function makeNativeOarCell(
        item
    ){

        const cell =
            document.createElement(
                "div"
            );

        cell.className =
            "dg-oar-cell";

        cell.setAttribute(
            "role",
            "gridcell"
        );


        if (
            !item ||
            !item.image
        ) {

            cell.classList.add(
                "dg-oar-blank"
            );

            return cell;
        }


        const shortName =
            typeof item.shortname ===
                "string"
                ? item.shortname
                : "";


        const image =
            document.createElement(
                "img"
            );


        image.alt =
            "";

        image.draggable =
            false;


        image.addEventListener(
            "load",
            function(){

                if (!shortName) {
                    return;
                }

                cell.classList.add(
                    "dg-oar-ready"
                );

                cell.title =
                    shortName;

                cell.tabIndex =
                    0;

                cell.draggable =
                    true;


                cell.addEventListener(
                    "click",
                    function(){

                        selectOarCell(
                            cell,
                            shortName
                        );
                    }
                );


                cell.addEventListener(
                    "dragstart",
                    function(event){

                        if (!event.dataTransfer) {

                            event.preventDefault();

                            return;
                        }

                        selectOarCell(
                            cell,
                            shortName
                        );

                        event.dataTransfer.effectAllowed =
                            "copy";

                        event.dataTransfer.setData(
                            "text/plain",
                            "OAR:" +
                            shortName
                        );
                    }
                );
            },
            {
                once:true
            }
        );


        image.addEventListener(
            "error",
            function(){

                image.remove();

                if (!shortName) {

                    cell.classList.add(
                        "dg-oar-blank"
                    );

                    return;
                }

                cell.classList.add(
                    "dg-oar-ready"
                );

                cell.title =
                    shortName;

                cell.tabIndex =
                    0;

                cell.textContent =
                    shortName;

                cell.style.display =
                    "flex";

                cell.style.alignItems =
                    "center";

                cell.style.justifyContent =
                    "center";

                cell.style.padding =
                    "8px";

                cell.style.color =
                    "#e2b64f";

                cell.style.fontSize =
                    "10px";

                cell.style.textAlign =
                    "center";

                cell.addEventListener(
                    "click",
                    function(){

                        selectOarCell(
                            cell,
                            shortName
                        );
                    }
                );
            },
            {
                once:true
            }
        );


        image.src =
            item.image;


        cell.appendChild(
            image
        );


        return cell;
    }


    async function loadNativeOarCatalogue(){

        if (!dgOarGrid) {
            return;
        }

        dgOarGrid.replaceChildren();

        dgOarData.clear();

        dgSelectedOarName =
            "";

        updateButtons();


        try {

            const response =
                await fetch(
                    "/Other/admin-region-global-map.php" +
                    "?mode=oars&_=" +
                    Date.now(),
                    {
                        credentials:
                            "same-origin",

                        cache:
                            "no-store"
                    }
                );


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.ok ||
                !Array.isArray(
                    data.oars
                )
            ) {

                throw new Error(
                    data.error ||
                    "Available OAR catalogue failed."
                );
            }


            data.oars.forEach(
                function(item){

                    const shortName =
                        item &&
                        typeof item.shortname ===
                            "string"
                            ? item.shortname
                            : "";


                    if (
                        shortName &&
                        !dgOarData.has(
                            shortName
                        )
                    ) {

                        dgOarData.set(
                            shortName,
                            item
                        );
                    }


                    dgOarGrid.appendChild(
                        makeNativeOarCell(
                            item
                        )
                    );
                }
            );


            if (
                data.oars.length % 2 !==
                0
            ) {

                const blankCell =
                    document.createElement(
                        "div"
                    );

                blankCell.className =
                    "dg-oar-cell dg-oar-blank";

                blankCell.setAttribute(
                    "role",
                    "gridcell"
                );

                dgOarGrid.appendChild(
                    blankCell
                );
            }
        }
        catch(error) {

            dgOarGrid.replaceChildren();

            writeLog(
                error &&
                error.message
                    ? "OAR catalogue: " +
                        error.message
                    : "OAR catalogue could not be loaded."
            );

            updateButtons();
        }
    }


    window.addEventListener(
        "load",
        function(){

            void loadNativeOarCatalogue();
        },
        {
            once:true
        }
    );

function tileUrl(
        x,
        y
    ){

        return (
            "/Other/admin-region-global-map.php" +
            "?mode=tile" +
            "&x=" +
            encodeURIComponent(
                x
            ) +
            "&y=" +
            encodeURIComponent(
                y
            ) +
            "&_=" +
            Date.now()
        );
    }


    function stepGridSize(delta){

        /*
         * ====================================================
         * DREAMGRID NATIVE ZOOM STATE FIX V1
         *
         * Native FormMapEditor changes GridSize in steps of 2.
         *
         * Zoom does NOT change the selected region.
         * Zoom does NOT change centreX / centreY.
         * Zoom does NOT rebuild the region list.
         *
         * The existing V4 cell renderer performs the redraw.
         * ====================================================
         */

        let next =
            gridSize +
            delta;


        next =
            Math.max(
                MIN_GRID_SIZE,
                Math.min(
                    MAX_GRID_SIZE,
                    next
                )
            );


        /*
         * DreamGrid map sizes are odd so there is always
         * one true centre grid cell.
         */
        if ((next % 2) === 0) {

            if (delta < 0) {
                next -= 1;
            }
            else {
                next += 1;
            }
        }


        next =
            Math.max(
                MIN_GRID_SIZE,
                Math.min(
                    MAX_GRID_SIZE,
                    next
                )
            );


        if (next === gridSize) {
            return;
        }


        /*
         * Only GridSize changes.
         *
         * Preserve:
         *   selected
         *   centreX
         *   centreY
         */
        gridSize =
            next;


        renderMap();

        updateButtons();


        writeLog(
            "Map GridSize " +
            gridSize +
            "."
        );
    }



function nativeGridSizeV4(){

        if (
            typeof gridSize ===
                "number" &&
            Number.isFinite(
                gridSize
            )
        ) {

            return Math.max(
                1,
                Math.min(
                    19,
                    Math.trunc(
                        gridSize
                    )
                )
            );
        }


        return 7;
    }


    function nativeCellSizeV4(
        size
    ){

        const cellWidth =
            Math.round(
                (
                    map.clientWidth -
                    (
                        size +
                        1
                    )
                ) /
                size
            );


        const cellHeight =
            Math.round(
                (
                    map.clientHeight -
                    (
                        size +
                        1
                    )
                ) /
                size
            );


        return Math.max(
            16,
            Math.min(
                cellWidth,
                cellHeight
            )
        );
    }


    function regionAtWorldCellV4(
        worldX,
        worldY
    ){

        return (
            regions.find(
                function(region){

                    const baseX =
                        Math.trunc(
                            Number(
                                region.x
                            )
                        );


                    const baseY =
                        Math.trunc(
                            Number(
                                region.y
                            )
                        );


                    const cellsX =
                        Math.max(
                            1,
                            Math.trunc(
                                Number(
                                    region.cellsX ||
                                    1
                                )
                            )
                        );


                    const cellsY =
                        Math.max(
                            1,
                            Math.trunc(
                                Number(
                                    region.cellsY ||
                                    1
                                )
                            )
                        );


                    return (
                        worldX >=
                            baseX &&
                        worldX <
                            baseX +
                            cellsX &&
                        worldY >=
                            baseY &&
                        worldY <
                            baseY +
                            cellsY
                    );
                }
            ) ||
            null
        );
    }


    function renderMap(){

        /*
         * ====================================================
         * DREAMGRID NATIVE CELL GRID V4
         * ====================================================
         */

        map.innerHTML =
            "";


        const size =
            nativeGridSizeV4();


        const half =
            Math.floor(
                size /
                2
            );


        const cell =
            nativeCellSizeV4(
                size
            );


        const step =
            cell +
            1;


        const xloc =
            Math.trunc(
                Number(
                    centreX
                )
            );


        const yloc =
            Math.trunc(
                Number(
                    centreY
                )
            );


        for (
            let row = 0;
            row < size;
            row++
        ) {

            for (
                let column = 0;
                column < size;
                column++
            ) {

                /*
                 * Exact DreamGrid world-cell mapping.
                 */

                const worldX =
                    xloc +
                    column -
                    half;


                const worldY =
                    yloc -
                    row +
                    half;


                const region =
                    regionAtWorldCellV4(
                        worldX,
                        worldY
                    );


                const mapCell =
                    document.createElement(
                        "div"
                    );


                mapCell.className =
                    "dg-native-map-cell-v4";


                /*
                 * Exact cell anchoring.
                 *
                 * No centering on an intersection.
                 * Every world coordinate owns one complete box.
                 */

                mapCell.style.left =
                    (
                        1 +
                        (
                            column *
                            step
                        )
                    ) +
                    "px";


                mapCell.style.top =
                    (
                        1 +
                        (
                            row *
                            step
                        )
                    ) +
                    "px";


                mapCell.style.width =
                    cell +
                    "px";


                mapCell.style.height =
                    cell +
                    "px";


                mapCell.dataset.worldX =
                    String(
                        worldX
                    );


                mapCell.dataset.worldY =
                    String(
                        worldY
                    );


                if (region) {

                    const image =
                        document.createElement(
                            "img"
                        );


                    image.alt =
                        "";


                    image.draggable =
                        false;


                    /*
                     * Each OpenSim world cell gets its own
                     * map-1-X-Y-objects.jpg image.
                     */

                    image.src =
                        tileUrl(
                            worldX,
                            worldY
                        );


                    image.addEventListener(
                        "error",
                        function(){

                            image.style.visibility =
                                "hidden";
                        }
                    );


                    mapCell.appendChild(
                        image
                    );


                    mapCell.title =
                        region.name +
                        "(" +
                        worldX +
                        "," +
                        worldY +
                        ")";


                    if (
                        selected &&
                        selected.name ===
                            region.name
                    ) {

                        const baseX =
                            Math.trunc(
                                Number(
                                    region.x
                                )
                            );


                        const baseY =
                            Math.trunc(
                                Number(
                                    region.y
                                )
                            );


                        const cellsX =
                            Math.max(
                                1,
                                Math.trunc(
                                    Number(
                                        region.cellsX ||
                                        1
                                    )
                                )
                            );


                        const cellsY =
                            Math.max(
                                1,
                                Math.trunc(
                                    Number(
                                        region.cellsY ||
                                        1
                                    )
                                )
                            );


                        if (
                            worldY ===
                            baseY +
                            cellsY -
                            1
                        ) {

                            mapCell.classList.add(
                                "dg-selected-n-v4"
                            );
                        }


                        if (
                            worldX ===
                            baseX +
                            cellsX -
                            1
                        ) {

                            mapCell.classList.add(
                                "dg-selected-e-v4"
                            );
                        }


                        if (
                            worldY ===
                            baseY
                        ) {

                            mapCell.classList.add(
                                "dg-selected-s-v4"
                            );
                        }


                        if (
                            worldX ===
                            baseX
                        ) {

                            mapCell.classList.add(
                                "dg-selected-w-v4"
                            );
                        }
                    }


                    mapCell.addEventListener(
                        "click",
                        function(){

                            selectRegion(
                                region,
                                false
                            );
                        }
                    );
                }
                else {

                    mapCell.title =
                        "(" +
                        worldX +
                        "," +
                        worldY +
                        ")";


                    /*
                     * Preserve the selected empty square when
                     * the map is redrawn.
                     */
                    if (
                        selectedBlankCell &&
                        selectedBlankCell.x ===
                            worldX &&
                        selectedBlankCell.y ===
                            worldY
                    ) {

                        mapCell.style.boxShadow =
                            "inset 0 0 0 2px #e2b64f";

                        mapCell.style.zIndex =
                            "3";
                    }


                    /*
                     * Native FormMapEditor behaviour:
                     *
                     * Click an EMPTY map square -> select it.
                     * Add becomes available for that location.
                     */
                    mapCell.addEventListener(
                        "click",
                        function(){

                            selected =
                                null;


                            selectedBlankCell = {
                                x:
                                    worldX,

                                y:
                                    worldY
                            };


                            renderList();

                            renderMap();

                            updateButtons();


                            writeLog(
                                "Selected empty location (" +
                                worldX +
                                "," +
                                worldY +
                                ")"
                            );
                        }
                    );
                }


                map.appendChild(
                    mapCell
                );
            }
        }

        /* END DREAMGRID NATIVE CELL GRID V4 */
    }




    function selectRegion(
        region,
        recenter
    ){

        selected =
            region ||
            null;


        /*
         * Selecting a real region cancels Add mode.
         */
        if (selected) {

            selectedBlankCell =
                null;
        }


        if (
            selected &&
            recenter
        ) {

            const point =
                regionCentre(
                    selected
                );


            centreX =
                point.x;


            centreY =
                point.y;


            writeLog(
                selected.name +
                " Centered."
            );
        }


        renderList();
        renderMap();
        updateButtons();
    }


    function pan(
        dx,
        dy
    ){

        centreX +=
            dx;


        centreY +=
            dy;


        renderMap();
    }


    function centreSelected(){

        if (selected) {

            selectRegion(
                selected,
                true
            );

            return;
        }


        const welcome =
            findRegion(
                welcomeRegion
            );


        if (welcome) {

            selectRegion(
                welcome,
                true
            );
        }
    }


    async function rotateSelectedRegion(angle){

        if (regionActionPending) return;
        if (!selected) {

            writeLog(
                "Select a region."
            );

            return;
        }

        regionActionPending = true;
        updateButtons();
        const body =
            new URLSearchParams();

        body.set(
            "region",
            selected.name
        );

        body.set(
            "angle",
            String(angle)
        );

        writeLog(
            "rotate scene " +
            angle +
            " " +
            selected.name +
            "..."
        );

        try {

            const response =
                await fetch(
                    "/Other/admin-region-global-map-rotate.php",
                    {
                        method:
                            "POST",

                        credentials:
                            "same-origin",

                        cache:
                            "no-store",

                        headers:
                            {
                                "Content-Type":
                                    "application/x-www-form-urlencoded;charset=UTF-8"
                            },

                        body:
                            body.toString()
                    }
                );

            const data =
                await response.json();

            if (
                !response.ok ||
                !data.ok
            ) {

                throw new Error(
                    data.error ||
                    "DreamGrid rotate scene failed."
                );
            }

            writeLog(
                data.message ||
                "rotate scene command sent."
            );
        }
        catch(error) {

            writeLog(
                error &&
                error.message
                    ? error.message
                    : String(error)
            );
        }
        finally { regionActionPending = false; updateButtons(); }
    }

function refreshMap(){
        if (refreshRequest) return refreshRequest;
        refreshRequest = (async function(){




        writeLog(
            "Refreshing..."
        );


        try {

            const response =
                await fetch(
                    "/Other/admin-region-global-map.php" +
                    "?mode=data&_=" +
                    Date.now(),
                    {
                        credentials:
                            "same-origin",

                        cache:
                            "no-store"
                    }
                );


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.ok ||
                !Array.isArray(
                    data.regions
                )
            ) {

                throw new Error(
                    data.error ||
                    "Refresh failed."
                );
            }


        const selectedName =
            selected
                ? selected.name
                : "";

            regions =
                data.regions;
            if (selectedBlankCell && regionAtWorldCellV4(selectedBlankCell.x, selectedBlankCell.y)) selectedBlankCell = null;


            selected =
                selectedName
                    ? findRegion(
                        selectedName
                    )
                    : null;


            renderList();
            renderMap();
            updateButtons();


            writeLog(
                "Refreshed."
            );
        }
        catch(error){

            writeLog(
                error &&
                error.message
                    ? error.message
                    : String(error)
            );
        }
        finally { refreshRequest = null; }
        })();
        return refreshRequest;
    }


    async function regionCommand(
        command
    ){

        if (regionActionPending) return;
        if (!selected) {

            writeLog(
                "Select a region."
            );

            return;
        }


        regionActionPending = true;
        updateButtons();
        const body =
            new URLSearchParams();


        body.set(
            "command",
            command
        );


        body.set(
            "region",
            selected.name
        );


        writeLog(
            command +
            " " +
            selected.name +
            "..."
        );


        try {

            const response =
                await fetch(
                    "/Other/admin-region-global-map.php",
                    {
                        method:
                            "POST",

                        credentials:
                            "same-origin",

                        cache:
                            "no-store",

                        headers:
                            {
                                "Content-Type":
                                    "application/x-www-form-urlencoded;charset=UTF-8"
                            },

                        body:
                            body.toString()
                    }
                );


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.ok
            ) {

                throw new Error(
                    data.error ||
                    data.message ||
                    "DreamGrid command failed."
                );
            }


            writeLog(
                data.message ||
                (
                    command +
                    " completed."
                )
            );


            if (
                command ===
                    "StartRegion" ||
                command ===
                    "StopRegion" ||
                command ===
                    "RestartRegion"
            ) {

                window.setTimeout(
                    refreshMap,
                    900
                );
            }
        }
        catch(error){

            writeLog(
                error &&
                error.message
                    ? error.message
                    : String(error)
            );
        }
        finally { regionActionPending = false; updateButtons(); }
    }


    async function loadSelectedOar(){

        if (regionActionPending) return;
        if (!selected) {

            writeLog(
                "Select a region."
            );

            return;
        }


        if (!dgSelectedOarName) {

            writeLog(
                "Select an OAR."
            );

            return;
        }


        const item =
            dgOarData.get(
                dgSelectedOarName
            );


        if (
            !item ||
            !item.url
        ) {

            writeLog(
                "The selected OAR URL is unavailable."
            );

            return;
        }


        const target = {...selected};
        const oarName = dgSelectedOarName;
        regionActionPending = true;
        updateButtons();
        const approved = await window.auConfirm({
            title:'LOAD OAR',
            message:'Load ' + oarName + ' onto ' + target.name + '?',
            warning:'This replaces region content. Confirm the region and your backup before continuing.',
            confirmText:'LOAD OAR'
        });
        if (!approved) { regionActionPending = false; updateButtons(); return; }

        const body =
            new URLSearchParams();


        body.set(
            "region",
            target.name
        );


        body.set(
            "uuid",
            target.uuid ||
                ""
        );


        body.set(
            "group",
            target.group ||
                ""
        );


        body.set(
            "oar",
            oarName
        );


        body.set(
            "url",
            item.url
        );


        writeLog(
            "Load OAR " +
            oarName +
            " -> " +
            target.name +
            "..."
        );


        loadOarButton.disabled =
            true;


        try {

            const response =
                await fetch(
                    "/Other/admin-region-load-oar-action.php",
                    {
                        method:
                            "POST",

                        credentials:
                            "same-origin",

                        cache:
                            "no-store",

                        headers:
                            {
                                "Content-Type":
                                    "application/x-www-form-urlencoded;charset=UTF-8"
                            },

                        body:
                            body.toString()
                    }
                );


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.ok
            ) {

                throw new Error(
                    data.error ||
                    data.message ||
                    "Load OAR request failed."
                );
            }


            writeLog(
                data.message ||
                (
                    "Load OAR started for " +
                    target.name +
                    "."
                )
            );
        }
        catch(error){

            writeLog(
                error &&
                error.message
                    ? error.message
                    : String(error)
            );
        }
        finally {
            regionActionPending = false;

            updateButtons();
        }
    }


    async function openConsole(){

        if (!selected) {

            writeLog(
                "Select a region."
            );

            return;
        }


        const body =
            new URLSearchParams();


        body.set(
            "region",
            selected.name
        );


        try {

            const response =
                await fetch(
                    "/Other/admin-region-console-action.php",
                    {
                        method:
                            "POST",

                        credentials:
                            "same-origin",

                        cache:
                            "no-store",

                        headers:
                            {
                                "Content-Type":
                                    "application/x-www-form-urlencoded;charset=UTF-8"
                            },

                        body:
                            body.toString()
                    }
                );


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.ok
            ) {

                throw new Error(
                    data.error ||
                    data.message ||
                    "Console request failed."
                );
            }


            writeLog(
                data.message ||
                (
                    "Console opened for " +
                    selected.name +
                    "."
                )
            );
        }
        catch(error){

            writeLog(
                error &&
                error.message
                    ? error.message
                    : String(error)
            );
        }
    }


    /*
     * ========================================================
     * DREAMGRID GLOBAL MAP - ADD REGION V1
     *
     * Native FormMapEditor creates a new region from the map
     * location. Keep the actual creation inside the existing
     * protected Create Region workflow.
     * ========================================================
     */

    const addRegionButton =
        document.getElementById(
            "dgAdd"
        );


    /*
     * ========================================================
     * DREAMGRID REGION NAME POPUP V2B
     *
     * Native-style child dialog used instead of the browser's
     * built-in name prompt.
     * ========================================================
     */

    let dgRegionNameBackdropV2B =
        null;

    let dgRegionNameDialogV2B =
        null;

    let dgRegionNameInputV2B =
        null;

    let dgRegionNameErrorV2B =
        null;


    function dgBuildRegionNamePopupV2B(){

        if (dgRegionNameDialogV2B) {
            return;
        }


        const style =
            document.createElement(
                "style"
            );


        style.id =
            "dgRegionNamePopupStyleV2B";


        style.textContent =
            [
                "#dgRegionNameBackdropV2B{",
                "position:fixed;",
                "inset:0;",
                "z-index:50000;",
                "display:none;",
                "background:rgba(0,0,0,.60);",
                "}",

                "#dgRegionNameBackdropV2B.open{",
                "display:block;",
                "}",

                "#dgRegionNameDialogV2B{",
                "position:fixed;",
                "z-index:50001;",
                "top:50%;",
                "left:50%;",
                "width:min(420px,calc(100vw - 30px));",
                "transform:translate(-50%,-50%);",
                "display:none;",
                "overflow:hidden;",
                "border:1px solid #66552d;",
                "border-radius:7px;",
                "background:#171917;",
                "color:#dedbd2;",
                "box-shadow:0 20px 65px rgba(0,0,0,.88);",
                "font-family:'Segoe UI',Arial,sans-serif;",
                "}",

                "#dgRegionNameDialogV2B.open{",
                "display:block;",
                "}",

                "#dgRegionNameTitleV2B{",
                "height:38px;",
                "display:flex;",
                "align-items:center;",
                "gap:9px;",
                "padding:0 13px;",
                "border-bottom:1px solid #66552d;",
                "background:linear-gradient(180deg,#1d1d18,#10110e);",
                "color:#e2b64f;",
                "font-size:12px;",
                "font-weight:700;",
                "letter-spacing:.045em;",
                "text-transform:uppercase;",
                "}",

                "#dgRegionNameTitleV2B:before{",
                "content:'';",
                "width:7px;",
                "height:7px;",
                "border:1px solid #e2b64f;",
                "background:#a87b25;",
                "}",

                "#dgRegionNameContentV2B{",
                "padding:16px 15px 15px;",
                "}",

                "#dgRegionNameLabelV2B{",
                "display:block;",
                "margin:0 0 8px;",
                "color:#eeeeea;",
                "font-size:12px;",
                "}",

                "#dgRegionNameInputV2B{",
                "box-sizing:border-box;",
                "width:100%;",
                "height:34px;",
                "padding:5px 8px;",
                "border:1px solid #515852;",
                "border-radius:4px;",
                "outline:none;",
                "background:#111411;",
                "color:#f2f2ed;",
                "font-family:'Segoe UI',Arial,sans-serif;",
                "font-size:13px;",
                "}",

                "#dgRegionNameInputV2B:focus{",
                "border-color:#d6a83d;",
                "box-shadow:0 0 0 1px rgba(214,168,61,.25);",
                "}",

                "#dgRegionNameErrorV2B{",
                "min-height:17px;",
                "margin-top:5px;",
                "color:#e2b64f;",
                "font-size:11px;",
                "}",

                "#dgRegionNameButtonsV2B{",
                "display:flex;",
                "justify-content:flex-end;",
                "gap:8px;",
                "margin-top:8px;",
                "}",

                "#dgRegionNameButtonsV2B button{",
                "min-width:72px;",
                "height:30px;",
                "padding:0 14px;",
                "border:1px solid #5b5f58;",
                "border-radius:4px;",
                "background:linear-gradient(180deg,#2b2e2a,#191c19);",
                "color:#eeeeea;",
                "font-family:'Segoe UI',Arial,sans-serif;",
                "font-size:12px;",
                "font-weight:600;",
                "cursor:pointer;",
                "}",

                "#dgRegionNameButtonsV2B button:hover,",
                "#dgRegionNameButtonsV2B button:focus-visible{",
                "border-color:#d6a83d;",
                "color:#e2b64f;",
                "outline:none;",
                "}",

                "#dgRegionNameOkV2B{",
                "border-color:#806622!important;",
                "}"
            ].join(
                ""
            );


        document.head.appendChild(
            style
        );


        dgRegionNameBackdropV2B =
            document.createElement(
                "div"
            );


        dgRegionNameBackdropV2B.id =
            "dgRegionNameBackdropV2B";


        dgRegionNameDialogV2B =
            document.createElement(
                "div"
            );


        dgRegionNameDialogV2B.id =
            "dgRegionNameDialogV2B";


        dgRegionNameDialogV2B.setAttribute(
            "role",
            "dialog"
        );


        dgRegionNameDialogV2B.setAttribute(
            "aria-modal",
            "true"
        );


        const title =
            document.createElement(
                "div"
            );


        title.id =
            "dgRegionNameTitleV2B";


        title.textContent =
            "Create a new region";


        const content =
            document.createElement(
                "div"
            );


        content.id =
            "dgRegionNameContentV2B";


        const label =
            document.createElement(
                "label"
            );


        label.id =
            "dgRegionNameLabelV2B";


        label.htmlFor =
            "dgRegionNameInputV2B";


        label.textContent =
            "Name for the new region:";


        dgRegionNameInputV2B =
            document.createElement(
                "input"
            );


        dgRegionNameInputV2B.id =
            "dgRegionNameInputV2B";


        dgRegionNameInputV2B.type =
            "text";


        dgRegionNameInputV2B.maxLength =
            64;


        dgRegionNameInputV2B.autocomplete =
            "off";


        dgRegionNameInputV2B.spellcheck =
            false;


        dgRegionNameErrorV2B =
            document.createElement(
                "div"
            );


        dgRegionNameErrorV2B.id =
            "dgRegionNameErrorV2B";


        const buttons =
            document.createElement(
                "div"
            );


        buttons.id =
            "dgRegionNameButtonsV2B";


        const okButton =
            document.createElement(
                "button"
            );


        okButton.id =
            "dgRegionNameOkV2B";


        okButton.type =
            "button";


        okButton.textContent =
            "OK";


        const cancelButton =
            document.createElement(
                "button"
            );


        cancelButton.id =
            "dgRegionNameCancelV2B";


        cancelButton.type =
            "button";


        cancelButton.textContent =
            "Cancel";


        buttons.appendChild(
            okButton
        );


        buttons.appendChild(
            cancelButton
        );


        content.appendChild(
            label
        );


        content.appendChild(
            dgRegionNameInputV2B
        );


        content.appendChild(
            dgRegionNameErrorV2B
        );


        content.appendChild(
            buttons
        );


        dgRegionNameDialogV2B.appendChild(
            title
        );


        dgRegionNameDialogV2B.appendChild(
            content
        );


        document.body.appendChild(
            dgRegionNameBackdropV2B
        );


        document.body.appendChild(
            dgRegionNameDialogV2B
        );


        okButton.addEventListener(
            "click",
            dgConfirmRegionNameV2B
        );


        cancelButton.addEventListener(
            "click",
            function(){

                dgCloseRegionNamePopupV2B(
                    true
                );
            }
        );


        dgRegionNameInputV2B.addEventListener(
            "keydown",
            function(event){

                if (event.key === "Enter") {

                    event.preventDefault();

                    event.stopPropagation();

                    dgConfirmRegionNameV2B();

                    return;
                }


                if (event.key === "Escape") {

                    event.preventDefault();

                    event.stopPropagation();

                    dgCloseRegionNamePopupV2B(
                        true
                    );
                }
            }
        );
    }


    function dgCloseRegionNamePopupV2B(cancelled){

        if (
            !dgRegionNameDialogV2B ||
            !dgRegionNameBackdropV2B
        ) {
            return;
        }


        dgRegionNameDialogV2B
            .classList
            .remove(
                "open"
            );


        dgRegionNameBackdropV2B
            .classList
            .remove(
                "open"
            );


        if (cancelled) {

            writeLog(
                "Add Region cancelled."
            );
        }
    }


    function dgConfirmRegionNameV2B(){

        if (!dgRegionNameInputV2B) {
            return;
        }


        const regionName =
            dgRegionNameInputV2B
                .value
                .trim();


        if (!regionName) {

            dgRegionNameErrorV2B.textContent =
                "Region name is required.";


            dgRegionNameInputV2B.focus();

            return;
        }


        dgCloseRegionNamePopupV2B(
            false
        );


        openCreateRegion(regionName);
    }


    function openAddRegion(){

        if (!selectedBlankCell) {

            writeLog(
                "Select an empty map square first."
            );

            updateButtons();

            return;
        }


        dgBuildRegionNamePopupV2B();


        dgRegionNameInputV2B.value =
            "";


        dgRegionNameErrorV2B.textContent =
            "";


        dgRegionNameBackdropV2B
            .classList
            .add(
                "open"
            );


        dgRegionNameDialogV2B
            .classList
            .add(
                "open"
            );


        window.setTimeout(
            function(){

                dgRegionNameInputV2B.focus();

            },
            0
        );
    }

    if (addRegionButton) {

        addRegionButton.addEventListener(
            "click",
            openAddRegion
        );
    }


    consoleButton.addEventListener(
        "click",
        openConsole
    );


    editButton.addEventListener(
        "click",
        function(){

            if (!selected) {
                return;
            }


            const editor =
                window.dgOpenInternalPopup(
                    '/Other/admin-region-edit.php?region=' + encodeURIComponent(selected.name),
                    'Edit Region — ' + selected.name
                );


            if (editor) {
                editor.focus();
            }
        }
    );


    startButton.addEventListener(
        "click",
        function(){

            regionCommand(
                "StartRegion"
            );
        }
    );


    restartButton.addEventListener(
        "click",
        function(){

            regionCommand(
                "RestartRegion"
            );
        }
    );


    stopButton.addEventListener(
        "click",
        function(){

            regionCommand(
                "StopRegion"
            );
        }
    );


    loadOarButton.addEventListener(
        "click",
        loadSelectedOar
    );


    saveOarButton.addEventListener(
        "click",
        function(){

            regionCommand(
                "SaveOAR"
            );
        }
    );


    function openCreateRegion(regionName){
        if (!selectedBlankCell) return;
        const x = Math.trunc(Number(selectedBlankCell.x));
        const y = Math.trunc(Number(selectedBlankCell.y));
        if (!Number.isFinite(x) || !Number.isFinite(y)) return;
        const url = new URL('/Other/admin-create-region.php', window.location.origin);
        url.search = new URLSearchParams({mapadd:'1', regionName, coordX:String(x), coordY:String(y)});
        window.dgOpenInternalPopup(url.href, 'Create Region', {
            layout:'create',
            onClose:async function(result){
                if (!result.changed) return;
                selectedBlankCell = null;
                await refreshMap();
                const created = findRegion(regionName);
                if (created) selectRegion(created);
            }
        });
    }

    // Region Edit's native completion handlers refresh this workspace.
    window.refreshMap = refreshMap;

    document
        .getElementById(
            "dgRefresh"
        )
        .addEventListener(
            "click",
            refreshMap
        );


    document
        .getElementById(
            "dgNW"
        )
        .addEventListener(
            "click",
            function(){
                pan(-1,1);
            }
        );


    document
        .getElementById(
            "dgN"
        )
        .addEventListener(
            "click",
            function(){
                pan(0,1);
            }
        );


    document
        .getElementById(
            "dgNE"
        )
        .addEventListener(
            "click",
            function(){
                pan(1,1);
            }
        );


    document
        .getElementById(
            "dgW"
        )
        .addEventListener(
            "click",
            function(){
                pan(-1,0);
            }
        );


    document
        .getElementById(
            "dgHome"
        )
        .addEventListener(
            "click",
            centreSelected
        );


    document
        .getElementById(
            "dgE"
        )
        .addEventListener(
            "click",
            function(){
                pan(1,0);
            }
        );


    document
        .getElementById(
            "dgSW"
        )
        .addEventListener(
            "click",
            function(){
                pan(-1,-1);
            }
        );


    document
        .getElementById(
            "dgS"
        )
        .addEventListener(
            "click",
            function(){
                pan(0,-1);
            }
        );


    document
        .getElementById(
            "dgSE"
        )
        .addEventListener(
            "click",
            function(){
                pan(1,-1);
            }
        );


    document
        .getElementById(
            "dgZoomIn"
        )
        .addEventListener(
            "click",
            function(){

                stepGridSize(
                    -2
                );
            }
        );


    document
        .getElementById(
            "dgZoomOut"
        )
        .addEventListener(
            "click",
            function(){

                stepGridSize(
                    2
                );
            }
        );


    document
        .getElementById(
            "dgRotate90"
        )
        .addEventListener(
            "click",
            function(){

                rotateSelectedRegion(
                    90
                );
            }
        );


    document
        .getElementById(
            "dgRotate180"
        )
        .addEventListener(
            "click",
            function(){

                rotateSelectedRegion(
                    180
                );
            }
        );


    document
        .getElementById(
            "dgRotateMinus90"
        )
        .addEventListener(
            "click",
            function(){

                rotateSelectedRegion(
                    -90
                );
            }
        );

search.addEventListener(
        "input",
        renderList
    );


    window.addEventListener(
        "resize",
        renderMap
    );


    /*
     * Native:
     *
     * FormMapEditor.Init(Settings.WelcomeRegion)
     */

    // Region Manager can open the catalogue for one explicit region.
    const requestedRegion = new URLSearchParams(window.location.search).get('region');
    const initialRegion = requestedRegion
        ? findRegion(requestedRegion)
        : (findRegion(welcomeRegion) || regions[0] || null);

    if (initialRegion) {
        selectRegion(initialRegion, true);
        if (new URLSearchParams(window.location.search).get('task') === 'load-oar') {
            writeLog('Select an OAR from Available OARs, then use Load OAR for ' + initialRegion.name + '.');
        }
    } else {
        renderList();
        renderMap();
        updateButtons();
        writeLog(requestedRegion ? 'The requested region was not found. Select a region before loading an OAR.' : 'No regions found.');
    }

})();

</script>

<script src="/Other/assets/js/dg-internal-popup-v1.js?v=<?= filemtime(__DIR__ . '/assets/js/dg-internal-popup-v1.js') ?>"></script>
</body>
</html>