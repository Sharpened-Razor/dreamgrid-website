<?php

/*
 ============================================================
 AUSTRALIA DREAMGRID FORMREGION NATIVE SYNC V2

 Creates:
   - OpenSim estate_settings row
   - OpenSim estate_map row
   - DreamGrid Region\RegionName.ini

 Does NOT:
   - manufacture a Robust regions row
   - start the simulator
   - alter another Region INI
 ============================================================
*/

require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/region-ini-import.php';
$GLOBALS['ag_create_region_committed'] = false;

/*
 * ============================================================
 * DREAMGRID CREATE OPENSIM INSTANCE V4D
 *
 * The existing Create Region backend writes the Region INI
 * and database records.
 *
 * DreamGrid one-exe-per-region instances also require:
 *
 *     Regions\<RegionName>\Opensim.ini
 *
 * Clone a known-good native DreamGrid Opensim.ini and modify
 * ONLY the real [Const] instance values:
 *
 *     http_listener_port
 *     RegionFolderName
 *
 * The later Network setting:
 *
 *     http_listener_port=${Const|http_listener_port}
 *
 * remains untouched.
 * ============================================================
 */

if (
    !function_exists(
        'ag_create_region_opensim_v4d'
    )
) {

    function ag_create_region_opensim_v4d(): void
    {
        if (empty($GLOBALS['ag_create_region_committed'])) return;


        if (
            ($_SERVER['REQUEST_METHOD'] ?? '')
            !==
            'POST'
        ) {
            return;
        }


        $regionName =
            trim(
                (string)(
                    $_POST['regionName'] ??
                    ''
                )
            );

        $groupName =
            trim(
                (string)(
                    $_POST['groupName'] ??
                    $regionName
                )
            );

        if ($groupName === '') {
            $groupName = $regionName;
        }


        if ($regionName === '' || $groupName === '') {
            return;
        }


        if (
            preg_match('/[\\\\\/:*?"<>|]/', $regionName) ||
            preg_match('/[\\\\\/:*?"<>|]/', $groupName)
        ) {
            return;
        }


        $regionsRoot =
            ag_dg_regions_root();


        if (
            !is_string($regionsRoot) ||
            $regionsRoot === '' ||
            !is_dir($regionsRoot)
        ) {
            return;
        }


        $regionFolder =
            $regionsRoot .
            DIRECTORY_SEPARATOR .
            $groupName;


        $regionIni =
            $regionFolder .
            DIRECTORY_SEPARATOR .
            'Region' .
            DIRECTORY_SEPARATOR .
            $regionName .
            '.ini';


        $openSimIni =
            $regionFolder .
            DIRECTORY_SEPARATOR .
            'Opensim.ini';


        /*
         * Only complete regions which the normal creator
         * actually created.
         */

        if (!is_file($regionIni)) {
            return;
        }


        /*
         * Never replace an existing native Opensim.ini.
         */

        if (is_file($openSimIni)) {
            return;
        }


        $regionText =
            @file_get_contents(
                $regionIni
            );


        if (!is_string($regionText)) {
            return;
        }


        if (
            !preg_match(
                '/^\s*InternalPort\s*=\s*([0-9]+)\s*$/mi',
                $regionText,
                $portMatch
            )
        ) {
            return;
        }


        $port =
            (int)$portMatch[1];


        if (
            $port < 1 ||
            $port > 65535
        ) {
            return;
        }


        /*
         * Welcome is the known-good native DreamGrid instance
         * template on this grid.
         */

        $template =
            $regionsRoot .
            DIRECTORY_SEPARATOR .
            'Welcome' .
            DIRECTORY_SEPARATOR .
            'Opensim.ini';


        if (!is_file($template)) {

            $candidates =
                glob(
                    $regionsRoot .
                    DIRECTORY_SEPARATOR .
                    '*' .
                    DIRECTORY_SEPARATOR .
                    'Opensim.ini'
                );


            if (!is_array($candidates)) {
                $candidates = [];
            }


            $template =
                '';


            foreach ($candidates as $candidate) {

                if (is_file($candidate)) {

                    $template =
                        $candidate;

                    break;
                }
            }
        }


        if (
            $template === '' ||
            !is_file($template)
        ) {
            return;
        }


        $sourceName =
            basename(
                dirname(
                    $template
                )
            );


        $openSimText =
            @file_get_contents(
                $template
            );


        if (!is_string($openSimText)) {
            return;
        }


        /*
         * Change ONLY [Const] http_listener_port.
         */

        $openSimText =
            preg_replace_callback(
                '/(?ms)(^\[Const\]\s*$.*?^\s*http_listener_port\s*=\s*)[^\r\n]*/',
                static function(array $match) use ($port): string {

                    return
                        $match[1] .
                        (string)$port;
                },
                $openSimText,
                1,
                $portCount
            );


        if (
            !is_string($openSimText) ||
            $portCount !== 1
        ) {
            return;
        }


        /*
         * Change ONLY [Const] RegionFolderName.
         */

        $openSimText =
            preg_replace_callback(
                '/(?ms)(^\[Const\]\s*$.*?^\s*RegionFolderName\s*=\s*)[^\r\n]*/',
                static function(array $match) use ($groupName): string {

                    return
                        $match[1] .
                        $groupName;
                },
                $openSimText,
                1,
                $folderCount
            );


        if (
            !is_string($openSimText) ||
            $folderCount !== 1
        ) {
            return;
        }


        /*
         * Native generated files have a region-named override
         * section at the end.
         */

        if ($sourceName !== '') {

            $sectionPattern =
                '/^\[' .
                preg_quote(
                    $sourceName,
                    '/'
                ) .
                '\]\s*$/mi';


            $openSimText =
                preg_replace_callback(
                    $sectionPattern,
                    static fn(array $match): string => '[' . $groupName . ']',
                    $openSimText,
                    1,
                    $sectionCount
                );


            if (
                !is_string($openSimText) ||
                $sectionCount !== 1
            ) {
                return;
            }
        }


        /*
         * Critical safety check:
         * the Network reference must still exist unchanged.
         */

        if (
            !preg_match(
                '/^\s*http_listener_port\s*=\s*\$\{Const\|http_listener_port\}\s*$/mi',
                $openSimText
            )
        ) {
            return;
        }


        $temporary =
            $openSimIni .
            '.v4d.tmp';


        @unlink(
            $temporary
        );


        $written =
            @file_put_contents(
                $temporary,
                $openSimText,
                LOCK_EX
            );


        if (
            $written === false ||
            $written !== strlen($openSimText)
        ) {

            @unlink(
                $temporary
            );

            return;
        }


        if (
            !@rename(
                $temporary,
                $openSimIni
            )
        ) {

            @unlink(
                $temporary
            );

            return;
        }
    }


    register_shutdown_function(
        'ag_create_region_opensim_v4d'
    );
}


require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/database.php';


if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');


function agCreateFail(
    string $message,
    int $status = 400
): void {

    http_response_code($status);

    echo json_encode(
        [
            'ok' =>
                false,

            'error' =>
                $message
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


function agValidUuid(
    string $uuid
): bool {

    return
        preg_match(
            '/^[0-9a-fA-F]{8}-' .
            '[0-9a-fA-F]{4}-' .
            '[0-9a-fA-F]{4}-' .
            '[0-9a-fA-F]{4}-' .
            '[0-9a-fA-F]{12}$/',
            $uuid
        ) === 1;
}


function agNewUuid(): string {

    $data =
        random_bytes(16);

    $data[6] =
        chr(
            (
                ord($data[6]) &
                0x0f
            ) |
            0x40
        );

    $data[8] =
        chr(
            (
                ord($data[8]) &
                0x3f
            ) |
            0x80
        );


    return
        vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(
                bin2hex($data),
                4
            )
        );
}


function agSafeWindowsName(
    string $value
): bool {

    if (
        $value === '' ||
        $value === '.' ||
        $value === '..'
    ) {

        return false;
    }


    if (
        preg_match(
            '/[<>:"\/\\\\|?*\x00-\x1F]/',
            $value
        )
    ) {

        return false;
    }


    if (
        preg_match(
            '/[\. ]$/',
            $value
        )
    ) {

        return false;
    }


    return true;
}


function agIniValue(
    string $raw,
    string $key
): ?string {

    if (
        preg_match(
            '/^\s*' .
            preg_quote(
                $key,
                '/'
            ) .
            '\s*=\s*(.*?)\s*$/mi',
            $raw,
            $match
        )
    ) {

        return
            trim(
                $match[1]
            );
    }


    return null;
}


function agReplaceIniKey(
    string $raw,
    string $key,
    string $value
): string {

    $pattern =
        '/^(\s*' .
        preg_quote(
            $key,
            '/'
        ) .
        '\s*=\s*).*$/mi';


    $count =
        preg_match_all(
            $pattern,
            $raw
        );


    if ($count !== 1) {

        throw new RuntimeException(
            $key .
            ' does not occur exactly once in the Region INI template.'
        );
    }


    $result =
        preg_replace_callback(
            $pattern,
            static function(
                array $match
            ) use (
                $value
            ): string {

                return
                    $match[1] .
                    $value;
            },
            $raw,
            1
        );


    if (!is_string($result)) {

        throw new RuntimeException(
            'Could not update ' .
            $key .
            '.'
        );
    }


    return $result;
}


function agParseConnection(
    string $connection
): array {

    $parts = [];


    foreach (
        explode(
            ';',
            $connection
        )
        as
        $piece
    ) {

        $piece =
            trim($piece);


        if (
            $piece === '' ||
            strpos(
                $piece,
                '='
            ) === false
        ) {

            continue;
        }


        [
            $key,
            $value
        ] =
            array_map(
                'trim',
                explode(
                    '=',
                    $piece,
                    2
                )
            );


        $parts[
            strtolower($key)
        ] =
            $value;
    }


    return $parts;
}


function agConnValue(
    array $parts,
    array $keys,
    string $default = ''
): string {

    foreach (
        $keys
        as
        $key
    ) {

        $key =
            strtolower($key);


        if (
            array_key_exists(
                $key,
                $parts
            )
        ) {

            return
                (string)$parts[$key];
        }
    }


    return $default;
}


function agOpenSimConnectionString(
    string $gridCommon
): string {

    $lines =
        @file(
            $gridCommon,
            FILE_IGNORE_NEW_LINES
        );


    if (!is_array($lines)) {

        throw new RuntimeException(
            'Could not read GridCommon.ini.'
        );
    }


    $matches = [];


    foreach (
        $lines
        as
        $line
    ) {

        $trimmed =
            trim($line);


        if (
            $trimmed === '' ||
            str_starts_with(
                $trimmed,
                ';'
            ) ||
            str_starts_with(
                $trimmed,
                '#'
            )
        ) {

            continue;
        }


        if (
            !preg_match(
                '/^ConnectionString\s*=\s*"?(.+?)"?\s*$/i',
                $trimmed,
                $match
            )
        ) {

            continue;
        }


        $value =
            trim(
                $match[1],
                "\" \t"
            );


        if (
            preg_match(
                '/(?:Database|Initial Catalog)\s*=\s*OpenSim(?:;|$)/i',
                $value
            )
        ) {

            $matches[] =
                $value;
        }
    }


    if (count($matches) !== 1) {

        throw new RuntimeException(
            'Expected exactly one active OpenSim database connection.'
        );
    }


    return $matches[0];
}


function agConnectOpenSim(
    string $gridCommon
): mysqli {

    $parts =
        agParseConnection(
            agOpenSimConnectionString(
                $gridCommon
            )
        );


    $host =
        agConnValue(
            $parts,
            [
                'data source',
                'server',
                'host'
            ],
            ag_web_local_host()
        );


    $database =
        agConnValue(
            $parts,
            [
                'database',
                'initial catalog'
            ]
        );


    $user =
        agConnValue(
            $parts,
            [
                'user id',
                'uid',
                'user'
            ]
        );


    $password =
        agConnValue(
            $parts,
            [
                'password',
                'pwd'
            ]
        );


    $port =
        (int)agConnValue(
            $parts,
            ['port'],
            (string)ag_web_database_port('region')
        );


    if (
        $database === '' ||
        $user === ''
    ) {

        throw new RuntimeException(
            'Could not parse the OpenSim database connection.'
        );
    }


    $con =
        @new mysqli(
            $host,
            $user,
            $password,
            $database,
            $port
        );


    if ($con->connect_errno) {

        throw new RuntimeException(
            'Could not connect to the OpenSim database.'
        );
    }


    $con->set_charset(
        'utf8mb4'
    );


    return $con;
}


function agRegionIniFiles(
    string $regionsRoot
): array {

    $result = [];


    $iterator =
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $regionsRoot,
                FilesystemIterator::SKIP_DOTS
            )
        );


    foreach (
        $iterator
        as
        $file
    ) {

        if (!$file->isFile()) {
            continue;
        }


        $path =
            $file->getPathname();


        if (
            !preg_match(
                '/[\\\\\/]Region[\\\\\/][^\\\\\/]+\.ini$/i',
                $path
            )
        ) {

            continue;
        }


        $raw =
            @file_get_contents($path);


        if (!is_string($raw)) {
            continue;
        }


        if (
            !preg_match(
                '/^\s*\[([^\]]+)\]\s*$/m',
                $raw,
                $section
            )
        ) {

            continue;
        }


        $result[] =
            [
                'path' =>
                    $path,

                'name' =>
                    trim($section[1]),

                'uuid' =>
                    trim(
                        (string)(
                            agIniValue(
                                $raw,
                                'RegionUUID'
                            ) ??
                            ''
                        )
                    ),

                'port' =>
                    (int)(
                        agIniValue(
                            $raw,
                            'InternalPort'
                        ) ??
                        0
                    ),

                'x' =>
                    (int)(
                        agIniValue(
                            $raw,
                            'CoordX'
                        ) ??
                        0
                    ),

                'y' =>
                    (int)(
                        agIniValue(
                            $raw,
                            'CoordY'
                        ) ??
                        0
                    ),

                'size_x' =>
                    (int)(
                        agIniValue(
                            $raw,
                            'SizeX'
                        ) ??
                        256
                    ),

                'size_y' =>
                    (int)(
                        agIniValue(
                            $raw,
                            'SizeY'
                        ) ??
                        256
                    )
            ];
    }


    return $result;
}


function agRegionsOverlap(
    int $x1,
    int $y1,
    int $sizeX1,
    int $sizeY1,
    int $x2,
    int $y2,
    int $sizeX2,
    int $sizeY2
): bool {

    $cellsX1 =
        max(
            1,
            (int)ceil(
                $sizeX1 /
                256
            )
        );

    $cellsY1 =
        max(
            1,
            (int)ceil(
                $sizeY1 /
                256
            )
        );

    $cellsX2 =
        max(
            1,
            (int)ceil(
                $sizeX2 /
                256
            )
        );

    $cellsY2 =
        max(
            1,
            (int)ceil(
                $sizeY2 /
                256
            )
        );


    return
        $x1 < ($x2 + $cellsX2) &&
        ($x1 + $cellsX1) > $x2 &&
        $y1 < ($y2 + $cellsY2) &&
        ($y1 + $cellsY1) > $y2;
}


function agSetIniKey(
    string $raw,
    string $key,
    string $value
): string {

    $pattern =
        '/^(\s*' .
        preg_quote(
            $key,
            '/'
        ) .
        '\s*=\s*).*$/mi';


    $count =
        preg_match_all(
            $pattern,
            $raw
        );


    if ($count > 1) {

        throw new RuntimeException(
            $key .
            ' occurs more than once in the Region INI template.'
        );
    }


    if ($count === 1) {

        $result =
            preg_replace_callback(
                $pattern,
                static function(
                    array $match
                ) use (
                    $value
                ): string {

                    return
                        $match[1] .
                        $value;
                },
                $raw,
                1
            );


        if (!is_string($result)) {

            throw new RuntimeException(
                'Could not update ' .
                $key .
                '.'
            );
        }


        return $result;
    }


    $lineEnding =
        str_contains(
            $raw,
            "\r\n"
        )
            ? "\r\n"
            : "\n";


    return
        rtrim(
            $raw,
            "\r\n"
        ) .
        $lineEnding .
        $key .
        '=' .
        $value .
        $lineEnding;
}


function agQuoteIdentifier(
    string $identifier
): string {

    return
        '`' .
        str_replace(
            '`',
            '``',
            $identifier
        ) .
        '`';
}


$session =
    ag_current_session();


if (!$session) {

    agCreateFail(
        'Not logged in.',
        401
    );
}


if (
    !ag_is_admin(
        $session
    )
) {

    agCreateFail(
        'Grid owner access only.',
        403
    );
}


if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !==
    'POST'
) {

    agCreateFail(
        'POST required.',
        405
    );
}


foreach ($_POST as $field => $value) {
    if ($field === 'cpu' && is_array($value) && count(array_filter($value, 'is_string')) === count($value)) continue;
    if (!is_string($value)) agCreateFail('Invalid Create Region field.', 400);
}

$postedCsrf =
    trim(
        (string)(
            $_POST['csrf_token'] ??
            ''
        )
    );


$sessionCsrf =
    trim(
        (string)(
            $_SESSION[
                'ag_create_region_csrf'
            ] ??
            ''
        )
    );


if (
    $postedCsrf === '' ||
    $sessionCsrf === '' ||
    !hash_equals(
        $sessionCsrf,
        $postedCsrf
    )
) {

    agCreateFail(
        'Your security token expired. Reload Create Region and try again.',
        403
    );
}


$agImportedIni = null;
$agImportToken = trim((string)($_POST['region_ini_import_token'] ?? ''));
if ($agImportToken !== '') {
    $staged = $_SESSION['ag_region_ini_import'] ?? null;
    if (!is_array($staged) || !hash_equals((string)($staged['token'] ?? ''), $agImportToken) || time() - (int)($staged['created'] ?? 0) > 3600) {
        agCreateFail('The INI preview expired. Select and preview the file again.', 400);
    }
    try {
        $agImportedIni = ag_region_import_parse((string)$staged['raw']);
    } catch (Throwable $error) {
        agCreateFail('The INI could not be validated: ' . $error->getMessage(), 400);
    }
}




$regionsRoot =
    (string)(
        ag_dg_regions_root()
        ??
        ''
    );


$gridCommon =
    (string)(
        ag_dg_path(
            'Opensim' .
            DIRECTORY_SEPARATOR .
            'bin' .
            DIRECTORY_SEPARATOR .
            'config-include' .
            DIRECTORY_SEPARATOR .
            'GridCommon.ini'
        )
        ??
        ''
    );


if (!is_dir($regionsRoot)) {

    agCreateFail(
        'Regions folder was not found.',
        500
    );
}


if (!is_file($gridCommon)) {

    agCreateFail(
        'GridCommon.ini was not found.',
        500
    );
}


$ownerUuid =
    trim(
        (string)(
            $_POST['regionOwner'] ??
            ''
        )
    );


$regionName =
    trim(
        (string)(
            $_POST['regionName'] ??
            ''
        )
    );

$groupName =
    trim(
        (string)(
            $_POST['groupName'] ??
            $regionName
        )
    );

if ($groupName === '') {
    $groupName = $regionName;
}

$applyEstateAll =
    (string)(
        $_POST['applyEstateAll'] ??
        ''
    ) === '1';


$estateName =
    trim(
        (string)(
            $_POST['estateName'] ??
            ''
        )
    );





$regionSizeText =
    trim(
        (string)(
            $_POST['regionSize'] ??
            ''
        )
    );


$coordXText =
    trim(
        (string)(
            $_POST['coordX'] ??
            ''
        )
    );


$coordYText =
    trim(
        (string)(
            $_POST['coordY'] ??
            ''
        )
    );


$portText =
    trim(
        (string)(
            $_POST['port'] ??
            ''
        )
    );


$uuid =
    trim(
        (string)(
            $_POST['regionUuid'] ??
            ''
        )
    );


$nonPhysicalText =
    trim(
        (string)(
            $_POST['nonPhysicalPrimMax'] ??
            '1024'
        )
    );


$physicalText =
    trim(
        (string)(
            $_POST['physicalPrimMax'] ??
            '64'
        )
    );


$maxPrimsText =
    trim(
        (string)(
            $_POST['maxPrims'] ??
            '45000'
        )
    );


$maxAgentsText =
    trim(
        (string)(
            $_POST['maxAgents'] ??
            '100'
        )
    );





$locked =
    isset(
        $_POST['locked']
    );


$clampPrimSize =
    isset(
        $_POST['clampPrimSize']
    );


$enabled =
    isset(
        $_POST['enabled']
    );


$smartMode =
    strtolower(
        trim(
            (string)(
                $_POST['smartStart'] ??
                'off'
            )
        )
    );


$mapType =
    trim(
        (string)(
            $_POST['mapType'] ??
            'Default'
        )
    );


$maptileStatic =
    trim(
        (string)(
            $_POST['maptileStatic'] ??
            ''
        )
    );


$landingSpot =
    trim(
        (string)(
            $_POST['landingSpot'] ??
            '<128,128,30>'
        )
    );


if ($landingSpot === '') {
    $landingSpot = '<128,128,30>';
}


$physics =
    trim(
        (string)(
            $_POST['physics'] ??
            ''
        )
    );


$scriptEngine =
    trim(
        (string)(
            $_POST['scriptEngine'] ??
            ''
        )
    );


$asyncScriptText =
    trim(
        (string)(
            $_POST['asyncScript'] ??
            '100'
        )
    );


$timerRateText =
    trim(
        (string)(
            $_POST['timerRate'] ??
            '0.2'
        )
    );


$frameRateText =
    trim(
        (string)(
            $_POST['frameRate'] ??
            '0.09090909090909091'
        )
    );


$permissionMode =
    strtolower(
        trim(
            (string)(
                $_POST['permissionMode'] ??
                'default'
            )
        )
    );


$publicityMode =
    strtolower(
        trim(
            (string)(
                $_POST['publicity'] ??
                'default'
            )
        )
    );


$opensimWorldKey =
    trim(
        (string)(
            $_POST['opensimWorldKey'] ??
            ''
        )
    );


$birds = isset($_POST['birds']);
$tides = isset($_POST['tides']);
$teleport = isset($_POST['teleport']);
$disableGloebits = isset($_POST['disableGloebits']);
$disallowForeigners = isset($_POST['disallowForeigners']);
$disallowResidents = isset($_POST['disallowResidents']);
$skipAutoBackup = isset($_POST['skipAutoBackup']);
$concierge = isset($_POST['concierge']);


$cpuValues =
    $_POST['cpu'] ??
    [];


if (!is_array($cpuValues)) {

    agCreateFail(
        'CPU affinity selection is invalid.'
    );
}


$coreMask = 0;


foreach ($cpuValues as $coreRaw) {

    if (
        !is_string($coreRaw) ||
        !preg_match(
            '/^\d+$/',
            $coreRaw
        )
    ) {

        agCreateFail(
            'CPU affinity selection is invalid.'
        );
    }


    $core =
        (int)$coreRaw;


    if (
        $core < 1 ||
        $core > 24
    ) {

        agCreateFail(
            'CPU affinity core must be between 1 and 24.'
        );
    }


    $coreMask |=
        (1 << ($core - 1));
}


if (!agValidUuid($ownerUuid)) {

    agCreateFail(
        'Select a valid local Region Owner.'
    );
}


if (
    strlen($regionName) > 64 ||
    !agSafeWindowsName(
        $regionName
    )
) {

    agCreateFail(
        'Enter a valid Region Name up to 64 characters.'
    );
}


if (
    strlen($groupName) > 64 ||
    !agSafeWindowsName(
        $groupName
    )
) {
    agCreateFail(
        'Enter a valid Group up to 64 characters.'
    );
}


if (
    strlen($estateName) > 64 ||
    !agSafeWindowsName(
        $estateName
    )
) {

    agCreateFail(
        'Enter a valid Estate Name up to 64 characters.'
    );
}


if (
    !preg_match(
        '/^\d+$/',
        $regionSizeText
    )
) {

    agCreateFail(
        'Region Size is invalid.'
    );
}


$regionSize =
    (int)$regionSizeText;


if (
    $regionSize < 1 ||
    $regionSize > 16
) {

    agCreateFail(
        'Region Size must be between 1x1 and 16x16.'
    );
}


if (
    !preg_match(
        '/^\d+$/',
        $coordXText
    ) ||
    !preg_match(
        '/^\d+$/',
        $coordYText
    )
) {

    agCreateFail(
        'Grid X and Grid Y are required whole numbers.'
    );
}


$coordX =
    (int)$coordXText;

$coordY =
    (int)$coordYText;


foreach (
    [
        'NonPhysicalPrimMax' =>
            $nonPhysicalText,

        'PhysicalPrimMax' =>
            $physicalText,

        'MaxPrims' =>
            $maxPrimsText,

        'MaxAgents' =>
            $maxAgentsText
    ]
    as
    $label =>
    $value
) {

    if (
        !preg_match(
            '/^\d+$/',
            $value
        )
    ) {

        agCreateFail(
            $label .
            ' must be a whole number.'
        );
    }
}


$nonPhysical =
    (int)$nonPhysicalText;

$physical =
    (int)$physicalText;

$maxPrims =
    (int)$maxPrimsText;

$maxAgents =
    (int)$maxAgentsText;


if (
    $maxAgents < 1 ||
    $maxAgents > 500
) {

    agCreateFail(
        'Max Agents must be between 1 and 500.'
    );
}


if (
    $maxPrims < 1000 ||
    $maxPrims > 10000000
) {

    agCreateFail(
        'Max Prims must be between 1,000 and 10,000,000.'
    );
}


if (
    !in_array(
        $smartMode,
        [
            'off',
            'boot',
            'suspend',
        ],
        true
    )
) {

    agCreateFail(
        'Smart Start mode is invalid.'
    );
}


if (
    !in_array(
        $mapType,
        [
            'Default',
            'None',
            'Simple',
            'Good',
            'Better',
            'Best',
        ],
        true
    )
) {

    agCreateFail(
        'Map generation setting is invalid.'
    );
}


if (
    !preg_match(
        '/^<\d*\.?\d*,\d*\.?\d*,\d*\.?\d*>$/',
        $landingSpot
    )
) {

    agCreateFail(
        'Default Landing Spot must look like <128,128,30>.'
    );
}


if (
    strlen($maptileStatic) > 1024 ||
    preg_match(
        '/[\r\n]/',
        $maptileStatic
    )
) {

    agCreateFail(
        'Custom map image value is invalid.'
    );
}


if (
    !in_array(
        $physics,
        [
            '',
            '2',
            '3',
            '4',
            '5',
        ],
        true
    )
) {

    agCreateFail(
        'Physics setting is invalid.'
    );
}


if (
    !in_array(
        $scriptEngine,
        [
            '',
            'Off',
            'YEngine',
        ],
        true
    )
) {

    agCreateFail(
        'Script engine setting is invalid.'
    );
}


foreach (
    [
        'Async LL Script Time' =>
            $asyncScriptText,

        'Script Timer Rate' =>
            $timerRateText,

        'Frame Rate' =>
            $frameRateText,
    ]
    as
    $label =>
    $numberText
) {

    if (
        $numberText === '' ||
        !is_numeric($numberText) ||
        (float)$numberText < 0
    ) {

        agCreateFail(
            $label .
            ' must be a number greater than or equal to zero.'
        );
    }
}


if (
    !in_array(
        $permissionMode,
        [
            'default',
            'level',
            'owner',
            'manager',
        ],
        true
    )
) {

    agCreateFail(
        'God permissions setting is invalid.'
    );
}


if (
    !in_array(
        $publicityMode,
        [
            'default',
            'off',
            'search',
        ],
        true
    )
) {

    agCreateFail(
        'Publicity setting is invalid.'
    );
}


if (
    strlen($opensimWorldKey) > 255 ||
    preg_match(
        '/[\r\n]/',
        $opensimWorldKey
    )
) {

    agCreateFail(
        'OpenSimWorld API Key is invalid.'
    );
}


$asyncScriptValue =
    rtrim(
        rtrim(
            sprintf(
                '%.12F',
                (float)$asyncScriptText
            ),
            '0'
        ),
        '.'
    );


$smartBootValue =
    $smartMode === 'boot'
        ? 'True'
        : 'False';


$smartStartValue =
    $smartMode === 'suspend'
        ? 'True'
        : 'False';


$allowGodsValue = '';
$godDefaultValue = 'True';
$managerGodValue = '';
$regionGodValue = '';


if ($permissionMode === 'level') {

    $allowGodsValue = 'True';
    $godDefaultValue = 'False';
    $managerGodValue = 'False';
    $regionGodValue = 'False';
}
elseif ($permissionMode === 'owner') {

    $allowGodsValue = 'False';
    $godDefaultValue = 'False';
    $managerGodValue = 'False';
    $regionGodValue = 'True';
}
elseif ($permissionMode === 'manager') {

    $allowGodsValue = 'False';
    $godDefaultValue = 'False';
    $managerGodValue = 'True';
    $regionGodValue = 'False';
}


$publicityValue = '';
$regionSnapshotValue = '';


if ($publicityMode === 'off') {

    $publicityValue = '';
    $regionSnapshotValue = 'False';
}
elseif ($publicityMode === 'search') {

    $publicityValue = 'True';
    $regionSnapshotValue = 'True';
}


if ($uuid === '') {

    $uuid =
        agNewUuid();
}


if (!agValidUuid($uuid)) {

    agCreateFail(
        'Region UUID is invalid.'
    );
}


/*
 * Validate owner against the local grid account database.
 */

$robust =
    ag_db_connect();


if (!$robust) {

    agCreateFail(
        'Account database is unavailable.',
        500
    );
}


mysqli_set_charset(
    $robust,
    'utf8mb4'
);


$ownerStmt =
    mysqli_prepare(
        $robust,
        'SELECT FirstName, LastName ' .
        'FROM UserAccounts ' .
        'WHERE PrincipalID = ? ' .
        'AND UserLevel >= 0 ' .
        'LIMIT 1'
    );


if (!$ownerStmt) {

    mysqli_close($robust);

    agCreateFail(
        'Could not validate Region Owner.',
        500
    );
}


mysqli_stmt_bind_param(
    $ownerStmt,
    's',
    $ownerUuid
);


mysqli_stmt_execute(
    $ownerStmt
);


$ownerResult =
    mysqli_stmt_get_result(
        $ownerStmt
);


$ownerRow =
    $ownerResult
        ? mysqli_fetch_assoc(
            $ownerResult
        )
        : null;


if ($ownerResult) {
    mysqli_free_result($ownerResult);
}


mysqli_stmt_close($ownerStmt);


if (!$ownerRow) {

    mysqli_close($robust);

    agCreateFail(
        'Selected Region Owner is not a valid local avatar.'
    );
}


$ownerName =
    trim(
        (string)$ownerRow['FirstName'] .
        ' ' .
        (string)$ownerRow['LastName']
    );


/*
 * Collision check against currently registered Robust regions.
 */

$robustRegionStmt =
    mysqli_prepare(
        $robust,
        'SELECT uuid ' .
        'FROM regions ' .
        'WHERE LOWER(regionName) = LOWER(?) ' .
        'OR uuid = ? ' .
        'LIMIT 1'
    );


if ($robustRegionStmt) {

    mysqli_stmt_bind_param(
        $robustRegionStmt,
        'ss',
        $regionName,
        $uuid
    );


    mysqli_stmt_execute(
        $robustRegionStmt
    );


    $rr =
        mysqli_stmt_get_result(
            $robustRegionStmt
        );


    if (
        $rr &&
        mysqli_fetch_assoc($rr)
    ) {

        mysqli_free_result($rr);
        mysqli_stmt_close($robustRegionStmt);
        mysqli_close($robust);

        agCreateFail(
            'That Region Name or UUID is already registered.'
        );
    }


    if ($rr) {
        mysqli_free_result($rr);
    }


    mysqli_stmt_close(
        $robustRegionStmt
    );
}


mysqli_close($robust);


/*
 * Scan DreamGrid Region INIs.
 */

try {

    $existingRegions =
        agRegionIniFiles(
            $regionsRoot
        );
}
catch (Throwable $error) {

    agCreateFail(
        'Could not scan existing regions.',
        500
    );
}


$newSizeX =
    $regionSize *
    256;

$newSizeY =
    $newSizeX;

$usedPorts = [];


foreach (
    $existingRegions
    as
    $existing
) {

    if (
        strcasecmp(
            (string)$existing['name'],
            $regionName
        ) === 0
    ) {

        agCreateFail(
            'A region already uses that Region Name.'
        );
    }


    if (
        strcasecmp(
            (string)$existing['uuid'],
            $uuid
        ) === 0
    ) {

        agCreateFail(
            'That Region UUID is already used.'
        );
    }


    $existingPort =
        (int)$existing['port'];


    if ($existingPort > 0) {

        $usedPorts[
            $existingPort
        ] =
            true;
    }


    if (
        agRegionsOverlap(
            $coordX,
            $coordY,
            $newSizeX,
            $newSizeY,
            (int)$existing['x'],
            (int)$existing['y'],
            (int)$existing['size_x'],
            (int)$existing['size_y']
        )
    ) {

        agCreateFail(
            'The requested region footprint overlaps "' .
            (string)$existing['name'] .
            '".'
        );
    }
}


/*
 * Port assignment.
 */

if ($portText === '') {

    $port = 0;


    for (
        $candidate = (int)(ag_web_setting(array('RegionPort','RegionPortWas')) ?: (max(ag_dg_robust_port(),ag_dg_private_robust_port(),ag_dg_diagnostics_port())+1));
        $candidate <= 65535;
        $candidate++
    ) {

        if (
            !isset(
                $usedPorts[
                    $candidate
                ]
            )
        ) {

            $port =
                $candidate;

            break;
        }
    }


    if ($port === 0) {

        agCreateFail(
            'No free Region Port could be found.',
            500
        );
    }
}
else {

    if (
        !preg_match(
            '/^\d+$/',
            $portText
        )
    ) {

        agCreateFail(
            'Region Port must be a whole number.'
        );
    }


    $port =
        (int)$portText;


    if (
        $port < 1 ||
        $port > 65535
    ) {

        agCreateFail(
            'Region Port must be between 1 and 65535.'
        );
    }


    if (
        isset(
            $usedPorts[
                $port
            ]
        )
    ) {

        agCreateFail(
            'That Region Port is already in use.'
        );
    }
}


/*
 * New DreamGrid directory.
 */

$dosBox =
    $regionsRoot .
    DIRECTORY_SEPARATOR .
    $groupName;


$regionDirectory =
    $dosBox .
    DIRECTORY_SEPARATOR .
    'Region';


$targetIni =
    $regionDirectory .
    DIRECTORY_SEPARATOR .
    $regionName .
    '.ini';


if (file_exists($targetIni)) {
    agCreateFail(
        'That Region already exists in the selected Group.'
    );
}

$groupExistedBefore = is_dir($dosBox);
$regionDirectoryExistedBefore = is_dir($regionDirectory);


/*
 * Use Welcome Region INI as this grid's template.
 */

$template =
    $regionsRoot .
    DIRECTORY_SEPARATOR .
    'Welcome' .
    DIRECTORY_SEPARATOR .
    'Region' .
    DIRECTORY_SEPARATOR .
    'Welcome.ini';


if (!is_file($template)) {

    if (count($existingRegions) < 1) {

        agCreateFail(
            'No Region INI template is available.',
            500
        );
    }


    $template =
        (string)$existingRegions[0]['path'];
}


$templateRaw =
    @file_get_contents($template);


if (!is_string($templateRaw)) {

    agCreateFail(
        'Could not read the Region INI template.',
        500
    );
}


try {

    if ($agImportedIni !== null) {
        $templateRaw = ag_region_import_merge($templateRaw, $agImportedIni);
    }

    $sectionCount =
        preg_match_all(
            '/^\s*\[[^\]]+\]\s*$/m',
            $templateRaw
        );


    if ($sectionCount !== 1) {

        throw new RuntimeException(
            'Template must contain exactly one Region section.'
        );
    }


    $newRaw =
        preg_replace_callback(
            '/^\s*\[[^\]]+\]\s*$/m',
            static fn(array $match): string => '[' . $regionName . ']',
            $templateRaw,
            1
        );


    if (!is_string($newRaw)) {

        throw new RuntimeException(
            'Could not set Region section name.'
        );
    }





    $changes =
        [
            'AllowGods' =>
                $allowGodsValue,

            'Birds' =>
                $birds
                    ? 'True'
                    : '',

            'ClampPrimSize' =>
                $clampPrimSize
                    ? 'True'
                    : 'False',

            'Concierge' =>
                $concierge
                    ? 'True'
                    : 'False',

            'CoordX' =>
                (string)$coordX,

            'CoordY' =>
                (string)$coordY,

            'Cores' =>
                (string)$coreMask,

            'DisableGloebits' =>
                $disableGloebits
                    ? 'True'
                    : '',

            'DisallowForeigners' =>
                $disallowForeigners
                    ? 'True'
                    : '',

            'DisallowResidents' =>
                $disallowResidents
                    ? 'True'
                    : '',

            'Enabled' =>
                $enabled
                    ? 'True'
                    : 'False',

            'Estate' =>
                $estateName,

            'FrameTime' =>
                $frameRateText,

            'GodDefault' =>
                $godDefaultValue,

            'GroupPort' =>
                (string)$port,

            'InternalPort' =>
                (string)$port,

            'LandingSpot' =>
                $landingSpot,

            'DefaultLanding' =>
                $landingSpot,

            'Location' =>
                $coordX .
                ',' .
                $coordY,

            'Locked' =>
                $locked
                    ? 'True'
                    : 'False',

            'ManagerGod' =>
                $managerGodValue,

            'MaptileStaticFile' =>
                $maptileStatic,

            'MapType' =>
                $mapType,

            'MaxAgents' =>
                (string)$maxAgents,

            'MaxPrims' =>
                (string)$maxPrims,

            'MinTimerInterval' =>
                $timerRateText,

            'NonPhysicalPrimMax' =>
                (string)$nonPhysical,

            'OpenSimWorldAPIKey' =>
                $opensimWorldKey,

            'PhysicalPrimMax' =>
                (string)$physical,

            'Physics' =>
                $physics,

            'Publicity' =>
                $publicityValue,

            'RegionGod' =>
                $regionGodValue,

            'RegionSnapShot' =>
                $regionSnapshotValue,

            'RegionUUID' =>
                $uuid,

            'ScriptEngine' =>
                $scriptEngine,

            'SizeX' =>
                (string)$newSizeX,

            'SizeY' =>
                (string)$newSizeY,

            'SkipAutoBackup' =>
                $skipAutoBackup
                    ? 'True'
                    : '',

            'SmartStart' =>
                $smartStartValue,

            'SmartBoot' =>
                $smartBootValue,

            'Teleport' =>
                $teleport
                    ? 'True'
                    : 'False',

            'Tides' =>
                $tides
                    ? 'True'
                    : ''
        ];


    foreach (
        $changes
        as
        $key =>
        $value
    ) {

        $newRaw =
            agReplaceIniKey(
                $newRaw,
                $key,
                $value
            );
    }


    $newRaw =
        agSetIniKey(
            $newRaw,
            'AsyncScriptLLTimeMs',
            $asyncScriptValue
        );
}
catch (Throwable $error) {

    agCreateFail(
        'Could not build Region INI: ' .
        $error->getMessage(),
        500
    );
}


/*
 * Connect to the actual OpenSim database.
 */

try {

    $opensim =
        agConnectOpenSim(
            $gridCommon
        );
}
catch (Throwable $error) {

    agCreateFail(
        $error->getMessage(),
        500
    );
}


/*
 * DreamGrid FormRegion allows Estate Name to refer to either an
 * existing Estate or a new Estate. Existing Estates are reused.
 */
$existingEstateId = 0;
$estateLookup = $opensim->prepare(
    'SELECT EstateID FROM estate_settings WHERE LOWER(EstateName) = LOWER(?) LIMIT 1'
);

if (!$estateLookup) {
    $opensim->close();
    agCreateFail('Could not validate Estate Name.', 500);
}

$estateLookup->bind_param('s', $estateName);
$estateLookup->execute();
$estateResult = $estateLookup->get_result();
if ($estateResult && ($estateRow = $estateResult->fetch_assoc())) {
    $existingEstateId = (int)($estateRow['EstateID'] ?? 0);
}
if ($estateResult) $estateResult->free();
$estateLookup->close();


/*
 * Choose an existing healthy public estate only as a SETTINGS
 * TEMPLATE. Name and owner will be replaced.
 */

$templateEstateResult =
    $opensim->query(
        "SELECT EstateID " .
        "FROM estate_settings " .
        "WHERE PublicAccess = 1 " .
        "AND EstateOwner <> " .
        "'00000000-0000-0000-0000-000000000000' " .
        "ORDER BY EstateID " .
        "LIMIT 1"
    );


$templateEstateId = 0;


if (
    $templateEstateResult &&
    (
        $row =
            $templateEstateResult->fetch_assoc()
    )
) {

    $templateEstateId =
        (int)$row['EstateID'];
}


if ($templateEstateResult) {
    $templateEstateResult->free();
}


if ($templateEstateId < 1) {

    $opensim->close();

    agCreateFail(
        'No suitable estate settings template exists.',
        500
    );
}


/*
 * Dynamically clone every estate_settings column except
 * EstateID, replacing EstateName and EstateOwner.
 */

$columnsResult =
    $opensim->query(
        'SHOW COLUMNS FROM estate_settings'
    );


if (!$columnsResult) {

    $opensim->close();

    agCreateFail(
        'Could not read estate_settings schema.',
        500
    );
}


$insertColumns = [];
$selectExpressions = [];


while (
    $column =
        $columnsResult->fetch_assoc()
) {

    $name =
        (string)$column['Field'];


    if (
        strcasecmp(
            $name,
            'EstateID'
        ) === 0
    ) {

        continue;
    }


    $insertColumns[] =
        agQuoteIdentifier($name);


    if (
        strcasecmp(
            $name,
            'EstateName'
        ) === 0
    ) {

        $selectExpressions[] =
            '?';
    }
    elseif (
        strcasecmp(
            $name,
            'EstateOwner'
        ) === 0
    ) {

        $selectExpressions[] =
            '?';
    }
    else {

        $selectExpressions[] =
            agQuoteIdentifier($name);
    }
}


$columnsResult->free();


$insertEstateSql =
    'INSERT INTO estate_settings (' .
    implode(
        ',',
        $insertColumns
    ) .
    ') SELECT ' .
    implode(
        ',',
        $selectExpressions
    ) .
    ' FROM estate_settings ' .
    'WHERE EstateID = ?';


$newEstateId = 0;
$tempIni = null;
$fileCreated = false;
$directoryCreated = false;


try {

    $opensim->begin_transaction();


    if ($existingEstateId > 0) {
        $newEstateId = $existingEstateId;
    }
    else {
        $insertEstate = $opensim->prepare($insertEstateSql);

        if (!$insertEstate) {
            throw new RuntimeException('Could not prepare the new estate.');
        }

        $insertEstate->bind_param('ssi', $estateName, $ownerUuid, $templateEstateId);

        if (!$insertEstate->execute()) {
            $detail = $insertEstate->error;
            $insertEstate->close();
            throw new RuntimeException('Could not create estate: ' . $detail);
        }

        $insertEstate->close();
        $newEstateId = (int)$opensim->insert_id;

        if ($newEstateId < 1) {
            throw new RuntimeException('OpenSim did not return the new EstateID.');
        }
    }


    $mapStmt =
        $opensim->prepare(
            'INSERT INTO estate_map ' .
            '(RegionID, EstateID) ' .
            'VALUES (?, ?)'
        );


    if (!$mapStmt) {

        throw new RuntimeException(
            'Could not prepare the region/estate mapping.'
        );
    }


    $mapStmt->bind_param(
        'si',
        $uuid,
        $newEstateId
    );


    if (!$mapStmt->execute()) {

        $detail =
            $mapStmt->error;

        $mapStmt->close();

        throw new RuntimeException(
            'Could not create region/estate mapping: ' .
            $detail
        );
    }


    $mapStmt->close();

    if ($applyEstateAll) {
        $allDelete = $opensim->prepare('DELETE FROM estate_map WHERE RegionID = ?');
        $allInsert = $opensim->prepare('INSERT INTO estate_map (RegionID, EstateID) VALUES (?, ?)');

        if (!$allDelete || !$allInsert) {
            if ($allDelete) $allDelete->close();
            if ($allInsert) $allInsert->close();
            throw new RuntimeException('Could not prepare Apply Estate to all Enabled Regions.');
        }

        foreach ($existingRegions as $existingRegion) {
            $existingPath = (string)($existingRegion['path'] ?? '');
            $existingUuid = trim((string)($existingRegion['uuid'] ?? ''));
            if ($existingPath === '' || !agValidUuid($existingUuid)) continue;

            $existingRaw = @file_get_contents($existingPath);
            if (!is_string($existingRaw)) continue;
            if (strcasecmp(trim((string)(agIniValue($existingRaw, 'Enabled') ?? '')), 'True') !== 0) continue;

            $allDelete->bind_param('s', $existingUuid);
            if (!$allDelete->execute()) {
                throw new RuntimeException('Could not clear an existing Estate assignment.');
            }

            $allInsert->bind_param('si', $existingUuid, $newEstateId);
            if (!$allInsert->execute()) {
                throw new RuntimeException('Could not apply the new Estate to an Enabled Region.');
            }
        }

        $allDelete->close();
        $allInsert->close();
    }


    if (!is_dir($regionDirectory)) {
        if (!@mkdir($regionDirectory, 0770, true) && !is_dir($regionDirectory)) {
            throw new RuntimeException(
                'Could not create the Region directory.'
            );
        }
        $directoryCreated = true;
    }


    $tempIni =
        $regionDirectory .
        DIRECTORY_SEPARATOR .
        '.create-' .
        bin2hex(
            random_bytes(4)
        ) .
        '.tmp';


    if (
        @file_put_contents(
            $tempIni,
            $newRaw,
            LOCK_EX
        ) === false
    ) {

        throw new RuntimeException(
            'Could not write temporary Region INI.'
        );
    }


    $verify =
        @file_get_contents(
            $tempIni
        );


    if (
        !is_string($verify) ||
        $verify !== $newRaw ||
        agIniValue(
            $verify,
            'Enabled'
        ) !==
            (
                $enabled
                    ? 'True'
                    : 'False'
            ) ||
        agIniValue(
            $verify,
            'SmartBoot'
        ) !==
            $smartBootValue ||
        agIniValue(
            $verify,
            'SmartStart'
        ) !==
            $smartStartValue ||
        agIniValue(
            $verify,
            'MapType'
        ) !==
            $mapType ||
        agIniValue(
            $verify,
            'Physics'
        ) !==
            $physics ||
        agIniValue(
            $verify,
            'ScriptEngine'
        ) !==
            $scriptEngine ||
        agIniValue(
            $verify,
            'Cores'
        ) !==
            (string)$coreMask ||
        agIniValue(
            $verify,
            'RegionUUID'
        ) !==
            $uuid ||
        agIniValue(
            $verify,
            'InternalPort'
        ) !==
            (string)$port ||
        agIniValue(
            $verify,
            'Estate'
        ) !==
            $estateName ||
        agIniValue(
            $verify,
            'CoordX'
        ) !==
            (string)$coordX ||
        agIniValue(
            $verify,
            'CoordY'
        ) !==
            (string)$coordY
    ) {

        @unlink($tempIni);

        throw new RuntimeException(
            'Region INI verification failed before commit.'
        );
    }


    if (
        !@rename(
            $tempIni,
            $targetIni
        )
    ) {

        @unlink($tempIni);

        throw new RuntimeException(
            'Could not commit final Region INI.'
        );
    }


    $fileCreated = true;


    if (!$opensim->commit()) {

        throw new RuntimeException(
            'Could not commit OpenSim estate transaction.'
        );
    }
}
catch (Throwable $error) {

    try {
        $opensim->rollback();
    }
    catch (Throwable $ignored) {
    }


    if (
        $fileCreated &&
        is_file($targetIni)
    ) {

        @unlink($targetIni);
    }


    if (is_string($tempIni) && is_file($tempIni)) @unlink($tempIni);

    if ($directoryCreated && !$regionDirectoryExistedBefore) {
        @rmdir($regionDirectory);
    }

    if (!$groupExistedBefore && is_dir($dosBox)) {
        @rmdir($dosBox);
    }


    $opensim->close();


    agCreateFail(
        'Region creation was rolled back: ' .
        $error->getMessage(),
        500
    );
}


$opensim->close();


/*
 * Keep creation metadata outside live OpenSim folders.
 */

$backupRoot = ag_dg_path('_REGION_CREATE_BACKUPS');


if (!is_dir($backupRoot)) {

    @mkdir(
        $backupRoot,
        0770,
        true
    );
}


$safeBackupName =
    preg_replace(
        '/[^A-Za-z0-9._-]+/',
        '-',
        $regionName
    );


$backupFolder =
    $backupRoot .
    DIRECTORY_SEPARATOR .
    gmdate('Ymd-His') .
    '-' .
    $safeBackupName;


if (
    @mkdir(
        $backupFolder,
        0770,
        true
    )
) {

    $metadata =
        [
            'format' =>
                'AUSTRALIA-DREAMGRID-FORMREGION-NATIVE-SYNC-V2',

            'created_utc' =>
                gmdate('c'),

            'region_name' =>
                $regionName,

            'group_name' =>
                $groupName,

            'region_uuid' =>
                $uuid,

            'owner_uuid' =>
                $ownerUuid,

            'owner_name' =>
                $ownerName,

            'estate_name' =>
                $estateName,

            'estate_id' =>
                $newEstateId,

            'estate_created' =>
                $existingEstateId <= 0,

            'coord_x' =>
                $coordX,

            'coord_y' =>
                $coordY,

            'size_x' =>
                $newSizeX,

            'size_y' =>
                $newSizeY,

            'port' =>
                $port,

            'region_ini' =>
                $targetIni,

            'enabled' =>
                $enabled,

            'smart_mode' =>
                $smartMode,

            'map_type' =>
                $mapType,

            'physics' =>
                $physics,

            'script_engine' =>
                $scriptEngine,

            'permission_mode' =>
                $permissionMode,

            'publicity_mode' =>
                $publicityMode,

            'cores' =>
                $coreMask,

            'started' =>
                false
        ];


    @file_put_contents(
        $backupFolder .
        DIRECTORY_SEPARATOR .
        'CREATED-REGION.json',
        json_encode(
            $metadata,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        ),
        LOCK_EX
    );


    @copy(
        $targetIni,
        $backupFolder .
        DIRECTORY_SEPARATOR .
        basename($targetIni)
    );
}


$GLOBALS['ag_create_region_committed'] = true;
if ($agImportToken !== '') unset($_SESSION['ag_region_ini_import']);

unset(
    $_SESSION[
        'ag_create_region_csrf'
    ]
);


echo json_encode(
    [
        'ok' =>
            true,

        'message' =>
            'Region definition created successfully. It has NOT been started.',

        'region' =>
            [
                'name' =>
                    $regionName,

                'group' =>
                    $groupName,

                'uuid' =>
                    $uuid,

                'owner' =>
                    $ownerName,

                'estate' =>
                    $estateName,

                'estate_id' =>
                    $newEstateId,

                'port' =>
                    $port,

                'location' =>
                    $coordX .
                    ',' .
                    $coordY,

                'size' =>
                    $regionSize .
                    'x' .
                    $regionSize,

                'ini' =>
                    $targetIni
            ]
    ],
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE
);
