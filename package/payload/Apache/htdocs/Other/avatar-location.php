<?php

declare(strict_types=1);

require_once __DIR__ . '/core/dreamgrid-env.php';

/*
 ============================================================
 Grid - LIVE AVATAR LOCATION API V1

 Read-only.

 Uses OpenSim RemoteAdmin admin_get_agents on localhost.

 RemoteAdmin passwords remain SERVER SIDE.
 ============================================================
*/

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

define('AUSTRALIA_MAP_TOKEN', ag_web_map_token());


function jsonReply(array $data, int $status = 200): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );
}


function iniValue(
    string $path,
    string $section,
    string $key
): ?string
{
    if (!is_file($path)) {
        return null;
    }

    $text = @file_get_contents($path);

    if ($text === false) {
        return null;
    }

    $inside = false;

    foreach (preg_split('/\R/', $text) as $rawLine) {

        $line = trim($rawLine);

        if ($line === '') {
            continue;
        }

        if (
            str_starts_with($line, ';') ||
            str_starts_with($line, '#')
        ) {
            continue;
        }

        if (
            preg_match(
                '/^\[([^\]]+)\]$/',
                $line,
                $m
            )
        ) {

            $inside =
                strcasecmp(
                    trim($m[1]),
                    $section
                ) === 0;

            continue;
        }

        if (!$inside) {
            continue;
        }

        $parts =
            explode(
                '=',
                $line,
                2
            );

        if (count($parts) !== 2) {
            continue;
        }

        if (
            strcasecmp(
                trim($parts[0]),
                $key
            ) !== 0
        ) {
            continue;
        }

        return trim(
            trim($parts[1]),
            "\"'"
        );
    }

    return null;
}


function regionConfiguration(
    string $directory
): ?array
{
    $regionFolder =
        $directory .
        DIRECTORY_SEPARATOR .
        'Region';

    $regionFiles =
        glob(
            $regionFolder .
            DIRECTORY_SEPARATOR .
            '*.ini'
        ) ?: [];

    if (!$regionFiles) {
        return null;
    }

    $regionIni =
        $regionFiles[0];

    $text =
        @file_get_contents(
            $regionIni
        );

    if ($text === false) {
        return null;
    }

    $regionName = '';

    if (
        preg_match(
            '/^\s*RegionName\s*=\s*"?([^"\r\n]+)"?\s*$/mi',
            $text,
            $m
        )
    ) {

        $regionName =
            trim($m[1]);

    } elseif (
        preg_match(
            '/^\s*\[([^\]]+)\]\s*$/m',
            $text,
            $m
        )
    ) {

        $regionName =
            trim($m[1]);
    }


    if ($regionName === '') {
        return null;
    }


    $openSimIni =
        $directory .
        DIRECTORY_SEPARATOR .
        'Opensim.ini';


    $enabled =
        iniValue(
            $openSimIni,
            'RemoteAdmin',
            'enabled'
        );


    if (
        $enabled !== null &&
        preg_match(
            '/^(false|0|no)$/i',
            trim($enabled)
        )
    ) {
        return null;
    }


    $password =
        iniValue(
            $openSimIni,
            'RemoteAdmin',
            'access_password'
        );


    $port =
        iniValue(
            $openSimIni,
            'RemoteAdmin',
            'port'
        );


    if (
        $port === null ||
        (int)$port <= 0
    ) {

        $port =
            iniValue(
                $regionIni,
                $regionName,
                'InternalPort'
            );
    }


    if (
        $password === null ||
        trim($password) === '' ||
        $port === null ||
        (int)$port <= 0
    ) {
        return null;
    }


    $sizeX =
        (int)(
            iniValue(
                $regionIni,
                $regionName,
                'SizeX'
            ) ?: 256
        );


    $sizeY =
        (int)(
            iniValue(
                $regionIni,
                $regionName,
                'SizeY'
            ) ?: 256
        );


    if ($sizeX <= 0) {
        $sizeX = 256;
    }

    if ($sizeY <= 0) {
        $sizeY = 256;
    }


    return [
        'name' => $regionName,
        'port' => (int)$port,
        'password' => $password,
        'size_x' => $sizeX,
        'size_y' => $sizeY
    ];
}


function xmlEscapeValue(
    string $value
): string
{
    return htmlspecialchars(
        $value,
        ENT_XML1 | ENT_QUOTES,
        'UTF-8'
    );
}


function xmlRpcValue(
    SimpleXMLElement $value
)
{
    if (isset($value->string)) {
        return (string)$value->string;
    }

    if (isset($value->int)) {
        return (int)$value->int;
    }

    if (isset($value->i4)) {
        return (int)$value->i4;
    }

    if (isset($value->double)) {
        return (float)$value->double;
    }

    if (isset($value->boolean)) {

        $v =
            strtolower(
                trim(
                    (string)$value->boolean
                )
            );

        return (
            $v === '1' ||
            $v === 'true'
        );
    }


    if (isset($value->array)) {

        $result = [];

        foreach (
            $value->array->data->value
            as $item
        ) {

            $result[] =
                xmlRpcValue(
                    $item
                );
        }

        return $result;
    }


    if (isset($value->struct)) {

        $result = [];

        foreach (
            $value->struct->member
            as $member
        ) {

            $name =
                (string)$member->name;

            $result[$name] =
                xmlRpcValue(
                    $member->value
                );
        }

        return $result;
    }


    return trim(
        (string)$value
    );
}


function queryRegion(
    array $region,
    string $avatar
): ?array
{
    $password =
        xmlEscapeValue(
            $region['password']
        );

    $regionName =
        xmlEscapeValue(
            $region['name']
        );


    $request =
        '<?xml version="1.0"?>' .
        '<methodCall>' .
        '<methodName>admin_get_agents</methodName>' .
        '<params><param><value><struct>' .

        '<member>' .
        '<name>password</name>' .
        '<value><string>' .
        $password .
        '</string></value>' .
        '</member>' .

        '<member>' .
        '<name>region_name</name>' .
        '<value><string>' .
        $regionName .
        '</string></value>' .
        '</member>' .

        '</struct></value></param></params>' .
        '</methodCall>';


    $context =
        stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' =>
                    "Content-Type: text/xml\r\n" .
                    "Connection: close\r\n",
                'content' => $request,
                'timeout' => 0.85,
                'ignore_errors' => true
            ]
        ]);


    $response =
        @file_get_contents(
            ag_web_local_base((int)$region['port']) .
            '/',
            false,
            $context
        );


    if (
        $response === false ||
        trim($response) === ''
    ) {
        return null;
    }


    if (
        !function_exists(
            'simplexml_load_string'
        )
    ) {
        return null;
    }


    $old =
        libxml_use_internal_errors(
            true
        );


    $xml =
        simplexml_load_string(
            $response
        );


    libxml_clear_errors();

    libxml_use_internal_errors(
        $old
    );


    if (!$xml) {
        return null;
    }


    if (
        !isset(
            $xml->params->param->value
        )
    ) {
        return null;
    }


    $root =
        xmlRpcValue(
            $xml->params->param->value
        );


    if (
        !is_array($root) ||
        !isset($root['regions']) ||
        !is_array($root['regions'])
    ) {
        return null;
    }


    foreach (
        $root['regions']
        as $remoteRegion
    ) {

        if (
            !is_array($remoteRegion) ||
            !isset($remoteRegion['agents']) ||
            !is_array($remoteRegion['agents'])
        ) {
            continue;
        }


        foreach (
            $remoteRegion['agents']
            as $agent
        ) {

            if (!is_array($agent)) {
                continue;
            }


            $name =
                trim(
                    (string)(
                        $agent['name'] ?? ''
                    )
                );


            if (
                $name === '' ||
                strcasecmp(
                    $name,
                    $avatar
                ) !== 0
            ) {
                continue;
            }


            return [
                'name' => $name,

                'id' =>
                    (string)(
                        $agent['id'] ?? ''
                    ),

                'pos_x' =>
                    (float)(
                        $agent['pos_x'] ?? 0
                    ),

                'pos_y' =>
                    (float)(
                        $agent['pos_y'] ?? 0
                    ),

                'pos_z' =>
                    (float)(
                        $agent['pos_z'] ?? 0
                    ),

                'lookat_x' =>
                    (float)(
                        $agent['lookat_x'] ?? 0
                    ),

                'lookat_y' =>
                    (float)(
                        $agent['lookat_y'] ?? 0
                    )
            ];
        }
    }


    return null;
}


/*
 ============================================================
 SECURITY TOKEN
 ============================================================
*/

$requestToken =
    isset($_GET['token'])
        ? (string)$_GET['token']
        : '';


if (
    $requestToken === '' ||
    !hash_equals(
        AUSTRALIA_MAP_TOKEN,
        $requestToken
    )
) {

    jsonReply(
        [
            'ok' => false,
            'error' => 'Forbidden'
        ],
        403
    );

    return;
}


/*
 ============================================================
 AVATAR
 ============================================================
*/

$avatar =
    trim(
        isset($_GET['avatar'])
            ? (string)$_GET['avatar']
            : ''
    );


if (
    $avatar === '' ||
    strlen($avatar) > 128
) {

    jsonReply(
        [
            'ok' => false,
            'error' => 'Invalid avatar'
        ],
        400
    );

    return;
}


$lastRegion =
    trim(
        isset($_GET['last_region'])
            ? (string)$_GET['last_region']
            : ''
    );


/*
 ============================================================
 DISCOVER REGION CONFIGURATIONS
 ============================================================
*/

$regionsRoot =
    ag_dg_regions_root();


if (!is_dir($regionsRoot)) {

    jsonReply(
        [
            'ok' => false,
            'error' => 'Regions folder not found'
        ],
        500
    );

    return;
}


$directories =
    glob(
        $regionsRoot .
        DIRECTORY_SEPARATOR .
        '*',
        GLOB_ONLYDIR
    ) ?: [];


$configurations = [];


foreach ($directories as $directory) {

    $config =
        regionConfiguration(
            $directory
        );

    if ($config === null) {
        continue;
    }


    if (
        $lastRegion !== '' &&
        strcasecmp(
            $config['name'],
            $lastRegion
        ) === 0
    ) {

        array_unshift(
            $configurations,
            $config
        );

    } else {

        $configurations[] =
            $config;
    }
}


/*
 ============================================================
 FIND AVATAR

 Usually only ONE RemoteAdmin request is needed because the
 last known region is checked first.

 After a teleport the remaining regions are scanned.
 ============================================================
*/

foreach (
    $configurations
    as $region
) {

    $agent =
        queryRegion(
            $region,
            $avatar
        );


    if ($agent === null) {
        continue;
    }


    jsonReply([
        'ok' => true,
        'found' => true,

        'avatar' =>
            $agent['name'],

        'id' =>
            $agent['id'],

        'region' =>
            $region['name'],

        'pos_x' =>
            $agent['pos_x'],

        'pos_y' =>
            $agent['pos_y'],

        'pos_z' =>
            $agent['pos_z'],

        'lookat_x' =>
            $agent['lookat_x'],

        'lookat_y' =>
            $agent['lookat_y'],

        'size_x' =>
            $region['size_x'],

        'size_y' =>
            $region['size_y'],

        'updated' =>
            date(DATE_ATOM)
    ]);

    return;
}


jsonReply([
    'ok' => true,
    'found' => false,
    'avatar' => $avatar,
    'updated' => date(DATE_ATOM)
]);
