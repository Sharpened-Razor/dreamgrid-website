<?php

declare(strict_types=1);

require_once __DIR__ . '/core/dreamgrid-env.php';

/*
 * ============================================================
 * REAL OPENSIM TERRAIN SETTINGS V6C-H26-R2
 *
 * DreamGrid region INIs are nested:
 *
 *   Regions/<Region>/Region/<Region>.ini
 *
 * This endpoint searches recursively.
 *
 * Browser output contains only:
 *   RegionUUID
 *   TerrainTexture1..4
 *   elevation low/high corners
 *   water height
 *
 * Database credentials are NEVER returned.
 * ============================================================
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

$isCli =
    PHP_SAPI === 'cli';


if (!$isCli) {

    require_once __DIR__ . '/core/database.php';
    require_once __DIR__ . '/core/auth.php';

    ag_require_login();

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header(
        'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
    );

    header(
        'Pragma: no-cache'
    );
}


function ag_h26r2_output(
    array $payload,
    int $status = 200
): void {

    global $isCli;

    if (!$isCli) {

        http_response_code(
            $status
        );
    }

    echo json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit(
        $status >= 400
            ? 1
            : 0
    );
}


function ag_h26r2_clean(
    $value
): string {

    return trim(
        (string)$value,
        " \t\n\r\0\x0B\"'"
    );
}


function ag_h26r2_valid_uuid(
    string $value
): bool {

    return preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
        trim(
            $value
        )
    ) === 1;
}


function ag_h26r2_normal_region(
    string $value
): string {

    $value =
        strtolower(
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $value
                ) ?? $value
            )
        );

    if (
        $value === 'official region' ||
        $value === 'offical region'
    ) {

        return 'offical region';
    }

    return $value;
}


function ag_h26r2_ci_value(
    array $values,
    array $names
): ?string {

    foreach (
        $values
        as
        $key => $value
    ) {

        foreach (
            $names
            as
            $name
        ) {

            if (
                strcasecmp(
                    trim(
                        (string)$key
                    ),
                    trim(
                        (string)$name
                    )
                ) === 0
            ) {

                return ag_h26r2_clean(
                    $value
                );
            }
        }
    }

    return null;
}


/*
 * ============================================================
 * RECURSIVE DREAMGRID REGION LOOKUP
 * ============================================================
 */

function ag_h26r2_find_region(
    string $wantedName
): ?array {

    $root =
        (string)ag_dg_path('Opensim' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'Regions');

    if (!is_dir($root)) {
        return null;
    }

    $wanted =
        ag_h26r2_normal_region(
            $wantedName
        );

    try {

        $iterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $root,
                    FilesystemIterator::SKIP_DOTS
                )
            );
    }
    catch (Throwable $error) {

        return null;
    }


    foreach (
        $iterator
        as
        $fileInfo
    ) {

        if (!$fileInfo->isFile()) {
            continue;
        }

        if (
            strcasecmp(
                $fileInfo->getExtension(),
                'ini'
            ) !== 0
        ) {

            continue;
        }

        $file =
            $fileInfo->getPathname();

        $sections =
            @parse_ini_file(
                $file,
                true,
                INI_SCANNER_RAW
            );

        if (!is_array($sections)) {
            continue;
        }


        foreach (
            $sections
            as
            $sectionName => $values
        ) {

            if (!is_array($values)) {
                continue;
            }

            $configuredName =
                ag_h26r2_ci_value(
                    $values,
                    [
                        'RegionName'
                    ]
                );

            $candidateName =
                (
                    $configuredName !== null &&
                    $configuredName !== ''
                )
                    ? $configuredName
                    : (string)$sectionName;


            if (
                ag_h26r2_normal_region(
                    $candidateName
                ) !==
                $wanted
            ) {

                continue;
            }


            $uuid =
                ag_h26r2_ci_value(
                    $values,
                    [
                        'RegionUUID'
                    ]
                );


            if (
                $uuid !== null &&
                ag_h26r2_valid_uuid(
                    $uuid
                )
            ) {

                return [
                    'name' =>
                        $candidateName,

                    'uuid' =>
                        strtolower(
                            $uuid
                        ),

                    'file' =>
                        $file
                ];
            }
        }
    }

    return null;
}


/*
 * ============================================================
 * CONNECTION STRING HELPERS
 * ============================================================
 */

function ag_h26r2_connection_parts(
    string $connection
): array {

    $result =
        [];

    foreach (
        explode(
            ';',
            $connection
        )
        as
        $piece
    ) {

        $piece =
            trim(
                $piece
            );

        if (
            $piece === '' ||
            !str_contains(
                $piece,
                '='
            )
        ) {

            continue;
        }

        [
            $key,
            $value
        ] =
            explode(
                '=',
                $piece,
                2
            );

        $result[
            strtolower(
                trim(
                    $key
                )
            )
        ] =
            ag_h26r2_clean(
                $value
            );
    }

    return $result;
}


function ag_h26r2_part(
    array $parts,
    array $names,
    ?string $fallback = null
): ?string {

    foreach (
        $names
        as
        $name
    ) {

        $key =
            strtolower(
                trim(
                    $name
                )
            );

        if (
            array_key_exists(
                $key,
                $parts
            )
        ) {

            return ag_h26r2_clean(
                $parts[$key]
            );
        }
    }

    return $fallback;
}


/*
 * ============================================================
 * FIND OPENSIM STORAGE CONNECTIONS
 * ============================================================
 */

function ag_h26r2_storage_candidates(): array {

    $bin =
        (string)ag_dg_path('Opensim' . DIRECTORY_SEPARATOR . 'bin');

    $files =
        array_merge(
            glob(
                $bin .
                '/*.ini'
            ) ?: [],

            glob(
                $bin .
                '/config-include/*.ini'
            ) ?: []
        );

    $results =
        [];

    $seen =
        [];


    foreach (
        $files
        as
        $file
    ) {

        $sections =
            @parse_ini_file(
                $file,
                true,
                INI_SCANNER_RAW
            );

        if (!is_array($sections)) {
            continue;
        }


        foreach (
            $sections
            as
            $sectionName => $values
        ) {

            if (!is_array($values)) {
                continue;
            }

            $connection =
                ag_h26r2_ci_value(
                    $values,
                    [
                        'ConnectionString'
                    ]
                );


            if (
                $connection === null ||
                $connection === ''
            ) {

                continue;
            }


            $provider =
                ag_h26r2_ci_value(
                    $values,
                    [
                        'StorageProvider'
                    ]
                ) ?? '';


            $signature =
                strtolower(
                    $provider .
                    '|' .
                    $connection
                );


            if (
                isset(
                    $seen[$signature]
                )
            ) {

                continue;
            }


            $seen[$signature] =
                true;


            $results[] =
                [
                    'provider' =>
                        $provider,

                    'connection' =>
                        $connection,

                    'base' =>
                        dirname(
                            $file
                        ),

                    'section' =>
                        (string)$sectionName
                ];
        }
    }


    /*
     * SQLite fallback.
     */

    foreach (
        array_merge(
            glob(
                $bin .
                '/*.db'
            ) ?: [],

            glob(
                $bin .
                '/*/*.db'
            ) ?: []
        )
        as
        $dbFile
    ) {

        $signature =
            'sqlite|' .
            strtolower(
                $dbFile
            );


        if (
            isset(
                $seen[$signature]
            )
        ) {

            continue;
        }


        $seen[$signature] =
            true;


        $results[] =
            [
                'provider' =>
                    'SQLite',

                'connection' =>
                    'Data Source=' .
                    $dbFile,

                'base' =>
                    dirname(
                        $dbFile
                    ),

                'section' =>
                    'fallback'
            ];
    }


    return $results;
}


/*
 * ============================================================
 * MYSQL / MARIADB
 * ============================================================
 */

function ag_h26r2_try_mysql(
    array $candidate,
    string $regionUuid
): ?array {

    $provider =
        strtolower(
            (string)(
                $candidate['provider'] ??
                ''
            )
        );

    $connection =
        (string)(
            $candidate['connection'] ??
                ''
        );

    $parts =
        ag_h26r2_connection_parts(
            $connection
        );


    if (
        !str_contains(
            $provider,
            'mysql'
        ) &&
        ag_h26r2_part(
            $parts,
            [
                'database',
                'initial catalog'
            ]
        ) === null
    ) {

        return null;
    }


    $host =
        ag_h26r2_part(
            $parts,
            [
                'data source',
                'server',
                'host'
            ],
            ag_web_local_host()
        ) ?? ag_web_local_host();


    $database =
        ag_h26r2_part(
            $parts,
            [
                'database',
                'initial catalog'
            ]
        );


    $user =
        ag_h26r2_part(
            $parts,
            [
                'user id',
                'userid',
                'uid',
                'user'
            ]
        );


    $password =
        ag_h26r2_part(
            $parts,
            [
                'password',
                'pwd'
            ],
            ''
        ) ?? '';


    $port =
        (int)(
            ag_h26r2_part(
                $parts,
                [
                    'port'
                ],
                (string)ag_web_database_port('region')
            ) ?? (string)ag_web_database_port('region')
        );


    if (
        $database === null ||
        $user === null
    ) {

        return null;
    }


    if (
        class_exists(
            'mysqli'
        )
    ) {

        mysqli_report(
            MYSQLI_REPORT_OFF
        );


        $db =
            @new mysqli(
                $host,
                $user,
                $password,
                $database,
                max(
                    1,
                    $port
                )
            );


        if (!$db->connect_errno) {

            $uuid =
                $db->real_escape_string(
                    $regionUuid
                );


            $result =
                @$db->query(
                    "SELECT * FROM regionsettings " .
                    "WHERE regionUUID = '" .
                    $uuid .
                    "' LIMIT 1"
                );


            if (
                $result instanceof mysqli_result
            ) {

                $row =
                    $result->fetch_assoc();

                $result->free();

                $db->close();


                if (is_array($row)) {

                    $row['_ag_provider'] =
                        'mysql/mysqli';

                    return $row;
                }
            }


            $db->close();
        }
    }


    if (
        class_exists(
            'PDO'
        ) &&
        in_array(
            'mysql',
            PDO::getAvailableDrivers(),
            true
        )
    ) {

        try {

            $pdo =
                new PDO(
                    'mysql:host=' .
                    $host .
                    ';port=' .
                    max(
                        1,
                        $port
                    ) .
                    ';dbname=' .
                    $database .
                    ';charset=utf8mb4',

                    $user,
                    $password,

                    [
                        PDO::ATTR_ERRMODE =>
                            PDO::ERRMODE_EXCEPTION,

                        PDO::ATTR_DEFAULT_FETCH_MODE =>
                            PDO::FETCH_ASSOC
                    ]
                );


            $statement =
                $pdo->prepare(
                    'SELECT * FROM regionsettings ' .
                    'WHERE regionUUID = :uuid LIMIT 1'
                );


            $statement->execute(
                [
                    ':uuid' =>
                        $regionUuid
                ]
            );


            $row =
                $statement->fetch();


            if (is_array($row)) {

                $row['_ag_provider'] =
                    'mysql/pdo';

                return $row;
            }
        }
        catch (Throwable $error) {
        }
    }


    return null;
}


/*
 * ============================================================
 * SQLITE
 * ============================================================
 */

function ag_h26r2_try_sqlite(
    array $candidate,
    string $regionUuid
): ?array {

    if (
        !class_exists(
            'PDO'
        ) ||
        !in_array(
            'sqlite',
            PDO::getAvailableDrivers(),
            true
        )
    ) {

        return null;
    }


    $provider =
        strtolower(
            (string)(
                $candidate['provider'] ??
                ''
            )
        );


    $connection =
        (string)(
            $candidate['connection'] ??
                ''
        );


    if (
        !str_contains(
            $provider,
            'sqlite'
        ) &&
        !str_contains(
            strtolower(
                $connection
            ),
            'file:'
        )
    ) {

        return null;
    }


    $parts =
        ag_h26r2_connection_parts(
            $connection
        );


    $path =
        ag_h26r2_part(
            $parts,
            [
                'uri',
                'data source',
                'datasource'
            ]
        );


    if ($path === null) {
        return null;
    }


    if (
        str_starts_with(
            strtolower(
                $path
            ),
            'file:'
        )
    ) {

        $path =
            substr(
                $path,
                5
            );
    }


    $path =
        preg_replace(
            '/,version\s*=\s*\d+.*$/i',
            '',
            $path
        ) ?? $path;


    $path =
        trim(
            $path
        );


    $possiblePaths =
        [];


    if (
        preg_match(
            '/^[A-Za-z]:[\/\\\\]/',
            $path
        ) === 1 ||
        str_starts_with(
            $path,
            '/'
        )
    ) {

        $possiblePaths[] =
            $path;
    }
    else {

        $base =
            (string)(
                $candidate['base'] ??
                    ''
            );


        if ($base !== '') {

            $possiblePaths[] =
                $base .
                DIRECTORY_SEPARATOR .
                $path;
        }


        $possiblePaths[] =
            rtrim((string)ag_dg_path('Opensim' . DIRECTORY_SEPARATOR . 'bin'), '/\\') . DIRECTORY_SEPARATOR .
            $path;
    }


    foreach (
        array_unique(
            $possiblePaths
        )
        as
        $sqliteFile
    ) {

        if (!is_file($sqliteFile)) {
            continue;
        }


        try {

            $pdo =
                new PDO(
                    'sqlite:' .
                    $sqliteFile,
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE =>
                            PDO::ERRMODE_EXCEPTION,

                        PDO::ATTR_DEFAULT_FETCH_MODE =>
                            PDO::FETCH_ASSOC
                    ]
                );


            $statement =
                $pdo->prepare(
                    'SELECT * FROM regionsettings ' .
                    'WHERE regionUUID = :uuid LIMIT 1'
                );


            $statement->execute(
                [
                    ':uuid' =>
                        $regionUuid
                ]
            );


            $row =
                $statement->fetch();


            if (is_array($row)) {

                $row['_ag_provider'] =
                    'sqlite/pdo';

                return $row;
            }
        }
        catch (Throwable $error) {
        }
    }


    return null;
}


/*
 * ============================================================
 * ROW LOOKUP
 * ============================================================
 */

function ag_h26r2_find_settings(
    string $regionUuid
): array {

    $candidates =
        ag_h26r2_storage_candidates();


    foreach (
        $candidates
        as
        $candidate
    ) {

        $row =
            ag_h26r2_try_mysql(
                $candidate,
                $regionUuid
            );


        if (is_array($row)) {

            return [
                'row' =>
                    $row,

                'candidateCount' =>
                    count(
                        $candidates
                    )
            ];
        }


        $row =
            ag_h26r2_try_sqlite(
                $candidate,
                $regionUuid
            );


        if (is_array($row)) {

            return [
                'row' =>
                    $row,

                'candidateCount' =>
                    count(
                        $candidates
                    )
            ];
        }
    }


    return [
        'row' =>
            null,

        'candidateCount' =>
            count(
                $candidates
            )
    ];
}


function ag_h26r2_row_value(
    array $row,
    string $wanted,
    $fallback = null
) {

    foreach (
        $row
        as
        $key => $value
    ) {

        if (
            strcasecmp(
                (string)$key,
                $wanted
            ) === 0
        ) {

            return $value;
        }
    }


    return $fallback;
}


/*
 * ============================================================
 * REQUEST
 * ============================================================
 */

if ($isCli) {

    global $argv;

    $regionName =
        trim(
            (string)(
                $argv[1] ??
                    ''
            )
        );
}
else {

    $regionName =
        trim(
            (string)(
                $_GET['region'] ??
                    ''
            )
        );
}


if (
    $regionName === '' ||
    strlen(
        $regionName
    ) > 128
) {

    ag_h26r2_output(
        [
            'ok' =>
                false,

            'error' =>
                'Invalid region.'
        ],
        400
    );
}


$region =
    ag_h26r2_find_region(
        $regionName
    );


if ($region === null) {

    ag_h26r2_output(
        [
            'ok' =>
                false,

            'error' =>
                'Recursive Region INI / RegionUUID not found.'
        ],
        404
    );
}


$result =
    ag_h26r2_find_settings(
        $region['uuid']
    );


$row =
    $result['row'];


if (!is_array($row)) {

    ag_h26r2_output(
        [
            'ok' =>
                false,

            'error' =>
                'OpenSim regionsettings row not found.',

            'RegionUUID' =>
                $region['uuid'],

            'candidateCount' =>
                $result['candidateCount']
        ],
        404
    );
}


$textures =
    [];


for (
    $index = 1;
    $index <= 4;
    $index++
) {

    $uuid =
        strtolower(
            trim(
                (string)(
                    ag_h26r2_row_value(
                        $row,
                        'terrain_texture_' .
                        $index,
                        ''
                    )
                )
            )
        );


    if (
        !ag_h26r2_valid_uuid(
            $uuid
        )
    ) {

        ag_h26r2_output(
            [
                'ok' =>
                    false,

                'error' =>
                    'Invalid terrain texture UUID ' .
                    $index
            ],
            500
        );
    }


    $textures[] =
        $uuid;
}


function ag_h26r2_corner(
    array $row,
    string $column,
    float $fallback
): float {

    return (float)(
        ag_h26r2_row_value(
            $row,
            $column,
            $fallback
        )
    );
}


$elevation1 =
    [
        'SW' =>
            ag_h26r2_corner(
                $row,
                'elevation_1_sw',
                10
            ),

        'SE' =>
            ag_h26r2_corner(
                $row,
                'elevation_1_se',
                10
            ),

        'NW' =>
            ag_h26r2_corner(
                $row,
                'elevation_1_nw',
                10
            ),

        'NE' =>
            ag_h26r2_corner(
                $row,
                'elevation_1_ne',
                10
            )
    ];


$elevation2 =
    [
        'SW' =>
            ag_h26r2_corner(
                $row,
                'elevation_2_sw',
                60
            ),

        'SE' =>
            ag_h26r2_corner(
                $row,
                'elevation_2_se',
                60
            ),

        'NW' =>
            ag_h26r2_corner(
                $row,
                'elevation_2_nw',
                60
            ),

        'NE' =>
            ag_h26r2_corner(
                $row,
                'elevation_2_ne',
                60
            )
    ];


ag_h26r2_output(
    [
        'ok' =>
            true,

        'marker' =>
            'REAL OPENSIM TERRAIN SETTINGS V6C-H26-R2',

        'RegionName' =>
            $region['name'],

        'RegionUUID' =>
            $region['uuid'],

        /*
         * File path intentionally returned during this proof
         * so we can verify DreamGrid found the correct region.
         */

        'RegionIni' =>
            $region['file'],

        'TerrainTextures' =>
            $textures,

        'Elevation1' =>
            $elevation1,

        'Elevation2' =>
            $elevation2,

        'WaterHeight' =>
            (float)(
                ag_h26r2_row_value(
                    $row,
                    'water_height',
                    20
                )
            ),

        'Provider' =>
            (string)(
                $row['_ag_provider'] ??
                    'unknown'
            )
    ]
);