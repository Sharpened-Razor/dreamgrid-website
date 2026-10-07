<?php

require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';

ag_require_login();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

/*
 * LIVE OPENSIM TERRAIN V6
 * LIVE OPENSIM WATER V7
 *
 * Preferred source:
 *   current terrain exported from the running OpenSim region
 *   with RemoteAdmin -> admin_console_command -> terrain save.
 *
 * Fallbacks:
 *   1. last valid live terrain.r32
 *   2. existing OAR Terrain3DCache manifest entry
 *
 * The renderer/JavaScript does not change.
 */

$regionsRoot =
    (string)ag_dg_path('Opensim' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'Regions');

$liveCacheRoot =
    (string)ag_dg_path('LiveTerrain3DCache');

$oarCacheRoot =
    (string)ag_dg_path('Terrain3DCache');

$oarManifestFile =
    $oarCacheRoot .
    '/terrain-manifest.json';

$region =
    trim(
        (string)($_GET['region'] ?? '')
    );

$cols =
    (int)($_GET['cols'] ?? 0);

$rows =
    (int)($_GET['rows'] ?? 0);

if (
    $region === '' ||
    $cols < 2 ||
    $rows < 2 ||
    $cols > 193 ||
    $rows > 193
) {

    http_response_code(400);

    echo json_encode(
        [
            'ok' => false,
            'error' => 'Invalid terrain request.'
        ]
    );

    exit;
}


function ag3d_live_fail(
    int $status,
    string $message
): never {

    http_response_code(
        $status
    );

    echo json_encode(
        [
            'ok' => false,
            'error' => $message
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


function ag3d_live_region_key(
    string $value
): string {

    $value =
        strtolower(
            trim(
                $value
            )
        );

    $value =
        preg_replace(
            '/\s+/',
            ' ',
            $value
        ) ?? $value;

    /*
     * Existing DreamGrid folder typo.
     *
     * Offical Region == Official Region.
     */

    if (
        $value ===
        'offical region'
    ) {

        $value =
            'official region';
    }

    return $value;
}


function ag3d_live_safe_name(
    string $value
): string {

    $value =
        trim(
            $value
        );

    $value =
        preg_replace(
            '/\s+/',
            '_',
            $value
        ) ?? $value;

    $value =
        preg_replace(
            '/[^A-Za-z0-9_-]/',
            '_',
            $value
        ) ?? $value;

    $value =
        preg_replace(
            '/_+/',
            '_',
            $value
        ) ?? $value;

    $value =
        trim(
            $value,
            '_'
        );

    return
        $value !== ''
            ?
            $value
            :
            'Region';
}


function ag3d_live_read_oar_manifest(
    string $manifestFile
): array {

    if (
        !is_file(
            $manifestFile
        )
    ) {

        return [];
    }

    $text =
        @file_get_contents(
            $manifestFile
        );

    $data =
        json_decode(
            (string)$text,
            true
        );

    if (
        !is_array(
            $data
        )
    ) {

        return [];
    }

    if (
        isset(
            $data['Region']
        )
    ) {

        return [
            $data
        ];
    }

    return $data;
}


function ag3d_live_find_oar_entry(
    array $manifest,
    string $regionKey
): ?array {

    foreach (
        $manifest
        as
        $entry
    ) {

        if (
            !is_array(
                $entry
            )
        ) {

            continue;
        }

        $status =
            strtoupper(
                trim(
                    (string)(
                        $entry['Status'] ??
                        ''
                    )
                )
            );

        if (
            $status !==
            'READY'
        ) {

            continue;
        }

        $entryRegion =
            trim(
                (string)(
                    $entry['Region'] ??
                    ''
                )
            );

        if (
            ag3d_live_region_key(
                $entryRegion
            ) ===
            $regionKey
        ) {

            return $entry;
        }
    }

    return null;
}


function ag3d_live_find_region_folder(
    string $regionsRoot,
    string $regionKey
): ?string {

    $folders =
        glob(
            $regionsRoot .
            '/*',
            GLOB_ONLYDIR
        );

    if (
        !is_array(
            $folders
        )
    ) {

        return null;
    }

    foreach (
        $folders
        as
        $folder
    ) {

        /*
         * First compare the DreamGrid folder itself.
         */

        if (
            ag3d_live_region_key(
                basename(
                    $folder
                )
            ) ===
            $regionKey
        ) {

            return $folder;
        }

        /*
         * Then inspect region INIs for RegionName.
         */

        $iniFiles =
            glob(
                $folder .
                '/*.ini'
            );

        if (
            !is_array(
                $iniFiles
            )
        ) {

            continue;
        }

        foreach (
            $iniFiles
            as
            $iniFile
        ) {

            $text =
                @file_get_contents(
                    $iniFile
                );

            if (
                !is_string(
                    $text
                )
            ) {

                continue;
            }

            if (
                preg_match(
                    '/^\s*RegionName\s*=\s*"?([^"\r\n]+?)"?\s*$/im',
                    $text,
                    $match
                )
            ) {

                $foundName =
                    trim(
                        (string)$match[1]
                    );

                if (
                    ag3d_live_region_key(
                        $foundName
                    ) ===
                    $regionKey
                ) {

                    return $folder;
                }
            }
        }
    }

    return null;
}


function ag3d_live_remote_settings(
    string $openSimIni
): ?array {

    if (
        !is_file(
            $openSimIni
        )
    ) {

        return null;
    }

    $text =
        @file_get_contents(
            $openSimIni
        );

    if (
        !is_string(
            $text
        )
    ) {

        return null;
    }

    if (
        !preg_match(
            '/^\s*\[RemoteAdmin\]\s*(.*?)(?=^\s*\[|\z)/ims',
            $text,
            $sectionMatch
        )
    ) {

        return null;
    }

    $body =
        (string)$sectionMatch[1];

    if (
        preg_match(
            '/^\s*enabled\s*=\s*false\s*$/im',
            $body
        )
    ) {

        return null;
    }

    if (
        !preg_match(
            '/^\s*port\s*=\s*"?(\d+)"?\s*$/im',
            $body,
            $portMatch
        )
    ) {

        return null;
    }

    if (
        !preg_match(
            '/^\s*access_password\s*=\s*"?([^"\r\n]+?)"?\s*$/im',
            $body,
            $passwordMatch
        )
    ) {

        return null;
    }

    $port =
        (int)$portMatch[1];

    $password =
        trim(
            (string)$passwordMatch[1]
        );

    if (
        $port <= 0 ||
        $password === ''
    ) {

        return null;
    }

    return [
        'port' =>
            $port,

        'password' =>
            $password
    ];
}


function ag3d_live_xml_escape(
    string $value
): string {

    return htmlspecialchars(
        $value,
        ENT_XML1 |
        ENT_QUOTES,
        'UTF-8'
    );
}


function ag3d_live_console_command(
    int $port,
    string $password,
    string $command
): void {

    $xml =
        '<?xml version="1.0"?>' .
        '<methodCall>' .
        '<methodName>admin_console_command</methodName>' .
        '<params>' .
        '<param>' .
        '<value>' .
        '<struct>' .

        '<member>' .
        '<name>password</name>' .
        '<value><string>' .
        ag3d_live_xml_escape(
            $password
        ) .
        '</string></value>' .
        '</member>' .

        '<member>' .
        '<name>command</name>' .
        '<value><string>' .
        ag3d_live_xml_escape(
            $command
        ) .
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
                'http' =>
                    [
                        'method' =>
                            'POST',

                        'header' =>
                            "Content-Type: text/xml\r\n" .
                            "Connection: close\r\n",

                        'content' =>
                            $xml,

                        'timeout' =>
                            5,

                        'ignore_errors' =>
                            true
                    ]
            ]
        );

    /*
     * The XML response is intentionally NOT authoritative.
     *
     * Welcome returns:
     *
     * Cannot write to a closed TextWriter.
     *
     * but still creates a valid fresh R32.
     *
     * Fresh valid R32 output is therefore the success test.
     */

    @file_get_contents(
        ag_web_local_base($port) .
        '/',
        false,
        $context
    );
}


/*
 * ==========================================================
 * LIVE OPENSIM WATER V7
 *
 * Ask the currently running region for "show region".
 *
 * Example:
 *
 * Water height               : 20 m
 *
 * The live simulator value overrides the old OAR settings
 * value only when a valid number is returned.
 * ==========================================================
 */

function ag3d_live_water_height(
    array $remoteSettings
): ?float {

    $port =
        (int)(
            $remoteSettings['port'] ??
            0
        );

    $password =
        trim(
            (string)(
                $remoteSettings['password'] ??
                ''
            )
        );

    if (
        $port <= 0 ||
        $password === ''
    ) {

        return null;
    }

    $command =
        'show region';

    $xml =
        '<?xml version="1.0"?>' .
        '<methodCall>' .
        '<methodName>admin_console_command</methodName>' .
        '<params>' .
        '<param>' .
        '<value>' .
        '<struct>' .

        '<member>' .
        '<name>password</name>' .
        '<value><string>' .
        ag3d_live_xml_escape(
            $password
        ) .
        '</string></value>' .
        '</member>' .

        '<member>' .
        '<name>command</name>' .
        '<value><string>' .
        ag3d_live_xml_escape(
            $command
        ) .
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
                'http' =>
                    [
                        'method' =>
                            'POST',

                        'header' =>
                            "Content-Type: text/xml\r\n" .
                            "Connection: close\r\n",

                        'content' =>
                            $xml,

                        'timeout' =>
                            5,

                        'ignore_errors' =>
                            true
                    ]
            ]
        );

    $response =
        @file_get_contents(
            ag_web_local_base($port) .
            '/',
            false,
            $context
        );

    if (
        !is_string(
            $response
        ) ||
        $response === ''
    ) {

        return null;
    }

    /*
     * We deliberately parse the console message itself rather
     * than trusting the XML success flag.
     *
     * This keeps the same defensive behaviour used for the
     * Welcome closed-TextWriter terrain-save case.
     */

    if (
        !preg_match(
            '/Water\s+height\s*:\s*([+-]?[0-9]+(?:\.[0-9]+)?)\s*m/i',
            $response,
            $waterMatch
        )
    ) {

        return null;
    }

    $value =
        (float)$waterMatch[1];

    if (
        !is_finite(
            $value
        )
    ) {

        return null;
    }

    return $value;
}

function ag3d_live_wait_for_r32(
    string $path,
    int $startedAt
): bool {

    $previousSize =
        -1;

    $stableCount =
        0;

    /*
     * Maximum wait:
     * about 10 seconds.
     */

    for (
        $attempt = 0;
        $attempt < 100;
        $attempt++
    ) {

        usleep(
            100000
        );

        clearstatcache(
            true,
            $path
        );

        if (
            !is_file(
                $path
            )
        ) {

            continue;
        }

        $size =
            @filesize(
                $path
            );

        $mtime =
            @filemtime(
                $path
            );

        if (
            !is_int(
                $size
            ) ||
            $size < 4 ||
            (
                $size %
                4
            ) !== 0 ||
            !is_int(
                $mtime
            ) ||
            $mtime <
            (
                $startedAt -
                2
            )
        ) {

            continue;
        }

        if (
            $size ===
            $previousSize
        ) {

            $stableCount++;
        }
        else {

            $stableCount =
                0;
        }

        $previousSize =
            $size;

        if (
            $stableCount >=
            2
        ) {

            return true;
        }
    }

    return false;
}


function ag3d_live_read_r32(
    string $path
): ?array {

    if (
        !is_file(
            $path
        )
    ) {

        return null;
    }

    $raw =
        @file_get_contents(
            $path
        );

    if (
        !is_string(
            $raw
        ) ||
        strlen(
            $raw
        ) < 4 ||
        (
            strlen(
                $raw
            ) %
            4
        ) !== 0
    ) {

        return null;
    }

    $sampleCount =
        intdiv(
            strlen(
                $raw
            ),
            4
        );

    $sourceSize =
        (int)round(
            sqrt(
                (float)$sampleCount
            )
        );

    if (
        (
            $sourceSize *
            $sourceSize
        ) !==
        $sampleCount
    ) {

        return null;
    }

    return [
        'raw' =>
            $raw,

        'sourceSize' =>
            $sourceSize
    ];
}


function ag3d_live_path_is_inside(
    string $path,
    string $root
): bool {

    $pathReal =
        realpath(
            $path
        );

    $rootReal =
        realpath(
            $root
        );

    if (
        $pathReal === false ||
        $rootReal === false
    ) {

        return false;
    }

    $normalPath =
        strtolower(
            str_replace(
                '\\',
                '/',
                $pathReal
            )
        );

    $normalRoot =
        strtolower(
            rtrim(
                str_replace(
                    '\\',
                    '/',
                    $rootReal
                ),
                '/'
            )
        ) .
        '/';

    return strncmp(
        $normalPath,
        $normalRoot,
        strlen(
            $normalRoot
        )
    ) === 0;
}


function ag3d_live_read_water_height(
    ?array $oarEntry,
    string $oarCacheRoot
): array {

    $waterHeight =
        20.0;

    $source =
        'DEFAULT_20';

    if (
        $oarEntry ===
        null
    ) {

        return [
            $waterHeight,
            $source
        ];
    }

    $terrainPath =
        str_replace(
            '\\',
            '/',
            trim(
                (string)(
                    $oarEntry['TerrainFile'] ??
                    ''
                )
            )
        );

    if (
        $terrainPath ===
        ''
    ) {

        return [
            $waterHeight,
            $source
        ];
    }

    $settingsPath =
        preg_replace(
            '/\.r32$/i',
            '.xml',
            $terrainPath
        );

    if (
        !is_string(
            $settingsPath
        ) ||
        !is_file(
            $settingsPath
        ) ||
        !ag3d_live_path_is_inside(
            $settingsPath,
            $oarCacheRoot
        )
    ) {

        return [
            $waterHeight,
            $source
        ];
    }

    $settingsText =
        @file_get_contents(
            $settingsPath
        );

    if (
        is_string(
            $settingsText
        ) &&
        preg_match(
            '/<WaterHeight>\s*([+-]?[0-9]+(?:\.[0-9]+)?)\s*<\/WaterHeight>/i',
            $settingsText,
            $waterMatch
        )
    ) {

        $waterHeight =
            (float)$waterMatch[1];

        $source =
            'OAR_SETTINGS';
    }

    return [
        $waterHeight,
        $source
    ];
}


$regionKey =
    ag3d_live_region_key(
        $region
    );

$oarManifest =
    ag3d_live_read_oar_manifest(
        $oarManifestFile
    );

$oarEntry =
    ag3d_live_find_oar_entry(
        $oarManifest,
        $regionKey
    );

$regionFolder =
    ag3d_live_find_region_folder(
        $regionsRoot,
        $regionKey
    );

/*
 * Request must correspond to either:
 *
 * - a real installed DreamGrid region folder
 *
 * OR
 *
 * - the trusted existing OAR manifest.
 */

if (
    $regionFolder ===
    null &&
    $oarEntry ===
    null
) {

    ag3d_live_fail(
        404,
        'Unknown region.'
    );
}


/*
 * Water is still taken from the known OpenSim settings XML.
 *
 * Land heights below are live.
 */

[
    $waterHeight,
    $waterSource
] =
    ag3d_live_read_water_height(
        $oarEntry,
        $oarCacheRoot
    );


$terrainData =
    null;

$terrainSource =
    '';

$liveAttempted =
    false;

$liveLatestCandidates =
    [];


/*
 * ==========================================================
 * SOURCE 1
 *
 * CURRENT RUNNING OPENSIM TERRAIN
 * ==========================================================
 */

if (
    $regionFolder !==
    null
) {

    $safeFolderName =
        ag3d_live_safe_name(
            basename(
                $regionFolder
            )
        );

    $liveFolder =
        $liveCacheRoot .
        '/' .
        $safeFolderName;

    if (
        is_dir(
            $liveFolder
        ) ||
        @mkdir(
            $liveFolder,
            0775,
            true
        )
    ) {

        $latestLiveFile =
            $liveFolder .
            '/terrain.r32';

        $liveLatestCandidates[] =
            $latestLiveFile;

        /*
         * Remove abandoned request files older than 10 minutes.
         */

        $oldRequests =
            glob(
                $liveFolder .
                '/terrain_req_*.r32'
            );

        if (
            is_array(
                $oldRequests
            )
        ) {

            foreach (
                $oldRequests
                as
                $oldRequest
            ) {

                $mtime =
                    @filemtime(
                        $oldRequest
                    );

                if (
                    is_int(
                        $mtime
                    ) &&
                    $mtime <
                    (
                        time() -
                        600
                    )
                ) {

                    @unlink(
                        $oldRequest
                    );
                }
            }
        }


        $remoteSettings =
            ag3d_live_remote_settings(
                $regionFolder .
                '/OpenSim.ini'
            );


        /*
         * LIVE OPENSIM WATER V7
         *
         * OAR/default water has already been loaded above.
         * Override it only when the running simulator gives us
         * a valid current Water height.
         */

        if (
            $remoteSettings !==
            null
        ) {

            $currentWaterHeight =
                ag3d_live_water_height(
                    $remoteSettings
                );

            if (
                $currentWaterHeight !==
                null &&
                is_finite(
                    $currentWaterHeight
                )
            ) {

                $waterHeight =
                    $currentWaterHeight;

                $waterSource =
                    'LIVE_OPENSIM_SHOW_REGION';
            }
        }

        if (
            $remoteSettings !==
            null
        ) {

            $liveAttempted =
                true;

            try {

                $nonce =
                    bin2hex(
                        random_bytes(
                            8
                        )
                    );
            }
            catch (
                Throwable
                $exception
            ) {

                $nonce =
                    preg_replace(
                        '/[^A-Za-z0-9]/',
                        '',
                        uniqid(
                            '',
                            true
                        )
                    ) ??
                    'request';
            }


            $tempFile =
                $liveFolder .
                '/terrain_req_' .
                getmypid() .
                '_' .
                $nonce .
                '.r32';


            $commandPath =
                str_replace(
                    '\\',
                    '/',
                    $tempFile
                );


            /*
             * OpenSim terrain console command parser must
             * receive a path containing no spaces.
             */

            if (
                strpos(
                    $commandPath,
                    ' '
                ) ===
                false
            ) {

                @unlink(
                    $tempFile
                );

                $startedAt =
                    time();

                $command =
                    'terrain save ' .
                    $commandPath;


                ag3d_live_console_command(
                    (int)$remoteSettings['port'],
                    (string)$remoteSettings['password'],
                    $command
                );


                if (
                    ag3d_live_wait_for_r32(
                        $tempFile,
                        $startedAt
                    )
                ) {

                    $candidate =
                        ag3d_live_read_r32(
                            $tempFile
                        );

                    if (
                        $candidate !==
                        null
                    ) {

                        $terrainData =
                            $candidate;

                        $terrainSource =
                            'LIVE_OPENSIM';

                        /*
                         * Store last-known-good live copy.
                         */

                        @copy(
                            $tempFile,
                            $latestLiveFile
                        );
                    }
                }

                @unlink(
                    $tempFile
                );
            }
        }
    }
}


/*
 * ==========================================================
 * SOURCE 2
 *
 * LAST KNOWN GOOD LIVE TERRAIN
 * ==========================================================
 */

if (
    $terrainData ===
    null
) {

    $requestSafeName =
        ag3d_live_safe_name(
            $region
        );

    $liveLatestCandidates[] =
        $liveCacheRoot .
        '/' .
        $requestSafeName .
        '/terrain.r32';


    /*
     * Support both spellings.
     */

    if (
        $regionKey ===
        'official region'
    ) {

        $liveLatestCandidates[] =
            $liveCacheRoot .
            '/Offical_Region/terrain.r32';

        $liveLatestCandidates[] =
            $liveCacheRoot .
            '/Official_Region/terrain.r32';
    }


    $liveLatestCandidates =
        array_values(
            array_unique(
                $liveLatestCandidates
            )
        );


    foreach (
        $liveLatestCandidates
        as
        $candidatePath
    ) {

        if (
            !is_file(
                $candidatePath
            ) ||
            !ag3d_live_path_is_inside(
                $candidatePath,
                $liveCacheRoot
            )
        ) {

            continue;
        }

        $candidate =
            ag3d_live_read_r32(
                $candidatePath
            );

        if (
            $candidate !==
            null
        ) {

            $terrainData =
                $candidate;

            $terrainSource =
                'LIVE_CACHE';

            break;
        }
    }
}


/*
 * ==========================================================
 * SOURCE 3
 *
 * ORIGINAL OAR CACHE FALLBACK
 * ==========================================================
 */

if (
    $terrainData ===
    null &&
    $oarEntry !==
    null
) {

    $oarTerrainPath =
        str_replace(
            '\\',
            '/',
            trim(
                (string)(
                    $oarEntry['TerrainFile'] ??
                    ''
                )
            )
        );


    if (
        $oarTerrainPath !==
        '' &&
        is_file(
            $oarTerrainPath
        ) &&
        ag3d_live_path_is_inside(
            $oarTerrainPath,
            $oarCacheRoot
        )
    ) {

        $candidate =
            ag3d_live_read_r32(
                $oarTerrainPath
            );

        if (
            $candidate !==
            null
        ) {

            $terrainData =
                $candidate;

            $terrainSource =
                'OAR_FALLBACK';
        }
    }
}


if (
    $terrainData ===
    null
) {

    ag3d_live_fail(
        503,
        'Terrain is temporarily unavailable.'
    );
}


$raw =
    (string)$terrainData['raw'];

$sourceSize =
    (int)$terrainData['sourceSize'];


$heights =
    [];

$minimum =
    INF;

$maximum =
    -INF;


/*
 * Preserve the exact R32 orientation already proven
 * correct by the working V5B terrain system.
 */

for (
    $row = 0;
    $row < $rows;
    $row++
) {

    $v =
        (
            $rows <=
            1
        )
            ?
            0.0
            :
            $row /
            (
                $rows -
                1
            );


    $sourceY =
        (
            $sourceSize -
            1
        ) -
        (int)round(
            $v *
            (
                $sourceSize -
                1
            )
        );


    for (
        $col = 0;
        $col < $cols;
        $col++
    ) {

        $u =
            (
                $cols <=
                1
            )
                ?
                0.0
                :
                $col /
                (
                    $cols -
                    1
                );


        $sourceX =
            (int)round(
                $u *
                (
                    $sourceSize -
                    1
                )
            );


        $sourceIndex =
            (
                $sourceY *
                $sourceSize
            ) +
            $sourceX;


        $offset =
            $sourceIndex *
            4;


        $valueArray =
            unpack(
                'gheight',
                substr(
                    $raw,
                    $offset,
                    4
                )
            );


        $height =
            isset(
                $valueArray['height']
            )
                ?
                (float)$valueArray['height']
                :
                $waterHeight;


        if (
            !is_finite(
                $height
            )
        ) {

            $height =
                $waterHeight;
        }


        $heights[] =
            round(
                $height,
                4
            );


        if (
            $height <
            $minimum
        ) {

            $minimum =
                $height;
        }


        if (
            $height >
            $maximum
        ) {

            $maximum =
                $height;
        }
    }
}


echo json_encode(
    [
        'ok' =>
            true,

        'RegionName' =>
            $region,

        'SourceWidth' =>
            $sourceSize,

        'SourceHeight' =>
            $sourceSize,

        'Columns' =>
            $cols,

        'Rows' =>
            $rows,

        'WaterHeight' =>
            $waterHeight,

        'MinHeight' =>
            round(
                $minimum,
                4
            ),

        'MaxHeight' =>
            round(
                $maximum,
                4
            ),

        'Heights' =>
            $heights,

        /*
         * Extra diagnostics.
         * Existing JavaScript safely ignores these.
         */

        'TerrainSource' =>
            $terrainSource,

        'LiveAttempted' =>
            $liveAttempted,

        'WaterSource' =>
            $waterSource
    ],
    JSON_UNESCAPED_SLASHES
);