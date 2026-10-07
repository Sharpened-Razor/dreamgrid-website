<?php

declare(strict_types=1);
require_once __DIR__.'/core/website-runtime.php';

/*
 ============================================================
 Grid - LIVE GRID AGENTS API V1

 READ ONLY.

 Queries each local OpenSim simulator with:

     admin_get_agents

 RemoteAdmin passwords NEVER leave the server.
 ============================================================
*/

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


const AUSTRALIA_AGENTS_TOKEN =
    '56ec74dce329f0cb8ea455190dd31444d9bb197010b0c5586f23c0834a3b8bbb';


function replyJson(
    array $data,
    int $status = 200
): void
{
    http_response_code(
        $status
    );

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


    $text =
        @file_get_contents(
            $path
        );


    if ($text === false) {
        return null;
    }


    $inside =
        false;


    foreach (
        preg_split(
            '/\R/',
            $text
        ) as $rawLine
    ) {

        $line =
            trim(
                $rawLine
            );


        if ($line === '') {
            continue;
        }


        $first =
            substr(
                $line,
                0,
                1
            );


        if (
            $first === ';' ||
            $first === '#'
        ) {
            continue;
        }


        if (
            preg_match(
                '/^\[([^\]]+)\]$/',
                $line,
                $match
            )
        ) {

            $inside =
                strcasecmp(
                    trim(
                        $match[1]
                    ),
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


        if (
            count(
                $parts
            ) !== 2
        ) {
            continue;
        }


        if (
            strcasecmp(
                trim(
                    $parts[0]
                ),
                $key
            ) !== 0
        ) {
            continue;
        }


        return trim(
            trim(
                $parts[1]
            ),
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


    $regionName =
        '';


    if (
        preg_match(
            '/^\s*RegionName\s*=\s*"?([^"\r\n]+)"?\s*$/mi',
            $text,
            $match
        )
    ) {

        $regionName =
            trim(
                $match[1]
            );

    }
    elseif (
        preg_match(
            '/^\s*\[([^\]]+)\]\s*$/m',
            $text,
            $match
        )
    ) {

        $regionName =
            trim(
                $match[1]
            );
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
            trim(
                $enabled
            )
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
        trim(
            $password
        ) === '' ||
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

        'name' =>
            $regionName,

        'port' =>
            (int)$port,

        'password' =>
            $password,

        'size_x' =>
            $sizeX,

        'size_y' =>
            $sizeY
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
    if (
        isset(
            $value->string
        )
    ) {

        return (string)$value->string;
    }


    if (
        isset(
            $value->int
        )
    ) {

        return (int)$value->int;
    }


    if (
        isset(
            $value->i4
        )
    ) {

        return (int)$value->i4;
    }


    if (
        isset(
            $value->double
        )
    ) {

        return (float)$value->double;
    }


    if (
        isset(
            $value->boolean
        )
    ) {

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


    if (
        isset(
            $value->array
        )
    ) {

        $result =
            [];


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


    if (
        isset(
            $value->struct
        )
    ) {

        $result =
            [];


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


function queryRegionAgents(
    array $region
): array
{
    $safePassword =
        xmlEscapeValue(
            $region['password']
        );


    $safeRegion =
        xmlEscapeValue(
            $region['name']
        );


    $request =
        '<?xml version="1.0"?>' .

        '<methodCall>' .

        '<methodName>' .
        'admin_get_agents' .
        '</methodName>' .

        '<params>' .
        '<param>' .
        '<value>' .
        '<struct>' .

        '<member>' .
        '<name>password</name>' .
        '<value><string>' .
        $safePassword .
        '</string></value>' .
        '</member>' .

        '<member>' .
        '<name>region_name</name>' .
        '<value><string>' .
        $safeRegion .
        '</string></value>' .
        '</member>' .

        '</struct>' .
        '</value>' .
        '</param>' .
        '</params>' .

        '</methodCall>';


    $context =
        stream_context_create(
            [
                'http' => [

                    'method' =>
                        'POST',

                    'header' =>
                        "Content-Type: text/xml\r\n" .
                        "Connection: close\r\n",

                    'content' =>
                        $request,

                    'timeout' =>
                        0.75,

                    'ignore_errors' =>
                        true
                ]
            ]
        );


    $response =
        @file_get_contents(
            ag_web_local_base((int)$region['port']) .
            '/',
            false,
            $context
        );


    if (
        $response === false ||
        trim(
            $response
        ) === ''
    ) {

        return [];
    }


    if (
        !function_exists(
            'simplexml_load_string'
        )
    ) {

        return [];
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
        return [];
    }


    if (
        !isset(
            $xml->params->param->value
        )
    ) {

        return [];
    }


    $root =
        xmlRpcValue(
            $xml->params->param->value
        );


    if (
        !is_array(
            $root
        ) ||
        !isset(
            $root['regions']
        ) ||
        !is_array(
            $root['regions']
        )
    ) {

        return [];
    }


    $result =
        [];


    foreach (
        $root['regions']
        as $remoteRegion
    ) {

        if (
            !is_array(
                $remoteRegion
            ) ||
            !isset(
                $remoteRegion['agents']
            ) ||
            !is_array(
                $remoteRegion['agents']
            )
        ) {

            continue;
        }


        foreach (
            $remoteRegion['agents']
            as $agent
        ) {

            if (
                !is_array(
                    $agent
                )
            ) {

                continue;
            }


            $name =
                trim(
                    (string)(
                        $agent['name'] ?? ''
                    )
                );


            if ($name === '') {
                continue;
            }


            /*
             * Normal logged-in avatars report:
             *
             *     type = User
             *
             * Do not display NPC/system agents.
             */

            $type =
                trim(
                    (string)(
                        $agent['type'] ?? ''
                    )
                );


            if (
                $type !== '' &&
                strcasecmp(
                    $type,
                    'User'
                ) !== 0
            ) {

                continue;
            }


            $result[] = [

                'id' =>
                    (string)(
                        $agent['id'] ?? ''
                    ),

                'name' =>
                    $name,

                'region' =>
                    $region['name'],

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
                    ),

                'is_flying' =>
                    strtolower(
                        (string)(
                            $agent['is_flying'] ?? 'false'
                        )
                    ) === 'true',

                'size_x' =>
                    $region['size_x'],

                'size_y' =>
                    $region['size_y']
            ];
        }
    }


    return $result;
}


/*
 ============================================================
 TOKEN CHECK
 ============================================================
*/

$requestToken =
    isset(
        $_GET['token']
    )
        ? (string)$_GET['token']
        : '';


if (
    $requestToken === '' ||
    !hash_equals(
        AUSTRALIA_AGENTS_TOKEN,
        $requestToken
    )
) {

    replyJson(
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
 REGION ROOT
 ============================================================
*/

$regionsRoot =
    dirname(
        __DIR__,
        4
    ) .
    DIRECTORY_SEPARATOR .
    'Opensim' .
    DIRECTORY_SEPARATOR .
    'bin' .
    DIRECTORY_SEPARATOR .
    'Regions';


if (
    !is_dir(
        $regionsRoot
    )
) {

    replyJson(
        [
            'ok' => false,
            'error' => 'Regions folder not found'
        ],
        500
    );

    return;
}


/*
 ============================================================
 SCAN ALL REGION CONFIGURATIONS
 ============================================================
*/

$directories =
    glob(
        $regionsRoot .
        DIRECTORY_SEPARATOR .
        '*',
        GLOB_ONLYDIR
    ) ?: [];


$agents =
    [];


/*
 * UUID keyed array prevents the same avatar from being shown
 * more than once if OpenSim ever exposes a child agent in
 * another simulator.
 */

$seen =
    [];


foreach (
    $directories
    as $directory
) {

    $region =
        regionConfiguration(
            $directory
        );


    if (
        $region === null
    ) {

        continue;
    }


    $regionAgents =
        queryRegionAgents(
            $region
        );


    foreach (
        $regionAgents
        as $agent
    ) {

        $id =
            trim(
                (string)$agent['id']
            );


        $dedupeKey =
            $id !== ''
                ? strtolower(
                    $id
                )
                : strtolower(
                    $agent['region'] .
                    '|' .
                    $agent['name']
                );


        if (
            isset(
                $seen[$dedupeKey]
            )
        ) {

            continue;
        }


        $seen[$dedupeKey] =
            true;


        $agents[] =
            $agent;
    }
}


usort(
    $agents,
    function (
        array $a,
        array $b
    ): int {

        $regionCompare =
            strcasecmp(
                $a['region'],
                $b['region']
            );


        if (
            $regionCompare !== 0
        ) {

            return $regionCompare;
        }


        return strcasecmp(
            $a['name'],
            $b['name']
        );
    }
);


replyJson(
    [
        'ok' =>
            true,

        'count' =>
            count(
                $agents
            ),

        'agents' =>
            $agents,

        'updated' =>
            date(
                DATE_ATOM
            )
    ]
);
