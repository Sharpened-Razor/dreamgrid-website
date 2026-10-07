<?php

declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/dreamgrid-env.php';

header('Content-Type: application/json; charset=utf-8');

function agrs_reply(
    int $status,
    bool $ok,
    string $message
): never {
    http_response_code($status);

    echo json_encode(
        [
            'ok'      => $ok,
            'message' => $message,
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    agrs_reply(
        405,
        false,
        'POST required.'
    );
}

ag_require_same_origin_post();
ag_require_admin();


$uuid =
    strtolower(
        trim(
            (string)(
                $_POST['uuid'] ??
                ''
            )
        )
    );

$regionName =
    trim(
        (string)(
            $_POST['region'] ??
            ''
        )
    );

$setting =
    trim(
        (string)(
            $_POST['setting'] ??
            ''
        )
    );

$value =
    trim(
        (string)(
            $_POST['value'] ??
            ''
        )
    );


if (
    !preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
        $uuid
    )
) {
    agrs_reply(
        400,
        false,
        'Invalid region UUID.'
    );
}


function agrs_set_key(
    string $raw,
    string $key,
    string $value
): string {

    $lines =
        preg_split(
            '/\r\n|\n|\r/',
            $raw
        );

    if (!is_array($lines)) {
        $lines = [];
    }

    $out = [];
    $done = false;

    $pattern =
        '/^\s*' .
        preg_quote($key, '/') .
        '\s*=/i';

    foreach ($lines as $line) {

        if (
            preg_match(
                $pattern,
                $line
            )
        ) {

            if (!$done) {

                $out[] =
                    $key .
                    '=' .
                    $value;

                $done = true;
            }

            continue;
        }

        $out[] =
            $line;
    }


    if (!$done) {

        while (
            $out &&
            trim(
                (string)end($out)
            ) === ''
        ) {
            array_pop($out);
        }

        $out[] =
            $key .
            '=' .
            $value;
    }


    return
        rtrim(
            implode(
                "\r\n",
                $out
            ),
            "\r\n"
        ) .
        "\r\n";
}


$regionsRoot =
    ag_dg_regions_root();

if (
    !is_dir(
        $regionsRoot
    )
) {
    agrs_reply(
        500,
        false,
        'Regions folder was not found.'
    );
}


/*
 * Find the Region INI by UUID.
 * This avoids relying on the group/folder name.
 */

$matches = [];

try {

    $iterator =
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $regionsRoot,
                FilesystemIterator::SKIP_DOTS
            )
        );

    foreach ($iterator as $fileInfo) {

        if (
            !$fileInfo->isFile() ||
            strcasecmp(
                $fileInfo->getExtension(),
                'ini'
            ) !== 0
        ) {
            continue;
        }

        $path =
            $fileInfo->getPathname();

        /*
         * Region definitions live under:
         *
         * <Group>\Region\<Region>.ini
         */

        if (
            strcasecmp(
                basename(
                    dirname($path)
                ),
                'Region'
            ) !== 0
        ) {
            continue;
        }

        $raw =
            @file_get_contents(
                $path
            );

        if (!is_string($raw)) {
            continue;
        }

        if (
            preg_match(
                '/^\s*RegionUUID\s*=\s*["\']?([0-9a-fA-F-]{36})["\']?\s*$/mi',
                $raw,
                $m
            )
        ) {

            if (
                strtolower(
                    $m[1]
                ) ===
                $uuid
            ) {

                $matches[] =
                    [
                        'path' => $path,
                        'raw'  => $raw,
                    ];
            }
        }
    }

}
catch (Throwable $e) {

    agrs_reply(
        500,
        false,
        'Could not scan the Regions folder.'
    );
}


if (count($matches) === 0) {

    agrs_reply(
        404,
        false,
        'Region INI was not found.'
    );
}

if (count($matches) > 1) {

    agrs_reply(
        409,
        false,
        'More than one Region INI uses this UUID.'
    );
}


$path =
    $matches[0]['path'];

$originalRaw =
    $matches[0]['raw'];

/*
 * WEB_NATIVE_SMART_START_BRIDGE
 *
 * Manager-owned settings must never be written directly
 * from the web page. Smart Start is routed through the
 * interactive native Regions window.
 */

if ($setting === 'smart_mode') {

    $allowedSmartModes =
        [
            'off',
            'boot',
            'suspend',
        ];


    if (
        !in_array(
            $value,
            $allowedSmartModes,
            true
        )
    ) {
        agrs_reply(
            400,
            false,
            'Invalid Smart Start setting.'
        );
    }


    /*
     * Determine the canonical native region name from the
     * INI section that contains the already-verified UUID.
     *
     * OpenSim region definitions normally use:
     *
     *     [Region Name]
     *     RegionUUID = ...
     *
     * rather than a RegionName= key.
     */

    $nativeRegionName =
        '';

    $currentSection =
        '';

    $iniLines =
        preg_split(
            '/\R/',
            $originalRaw
        );


    if (is_array($iniLines)) {

        foreach ($iniLines as $iniLine) {

            if (
                preg_match(
                    '/^\s*\[([^\]\r\n]+)\]\s*$/',
                    $iniLine,
                    $sectionMatch
                )
            ) {

                $currentSection =
                    trim(
                        (string)$sectionMatch[1]
                    );

                continue;
            }


            if (
                preg_match(
                    '/^\s*RegionUUID\s*=\s*["\']?([0-9a-fA-F-]{36})["\']?\s*$/',
                    $iniLine,
                    $uuidMatch
                )
            ) {

                if (
                    strtolower(
                        (string)$uuidMatch[1]
                    ) ===
                    $uuid
                ) {

                    $nativeRegionName =
                        $currentSection;

                    break;
                }
            }
        }
    }


    if (
        $nativeRegionName === '' ||
        strlen($nativeRegionName) > 128 ||
        preg_match(
            '/[\x00-\x1F]/',
            $nativeRegionName
        )
    ) {

        agrs_reply(
            500,
            false,
            'A valid native region name could not be determined.'
        );
    }


    /*
     * A mismatch means the web row is stale or inconsistent.
     */

    if (
        $regionName !== '' &&
        $regionName !== $nativeRegionName
    ) {

        agrs_reply(
            409,
            false,
            'The web region name does not match the Region INI.'
        );
    }


    require_once
        __DIR__ .
        '/core/region-settings-bridge.php';


    try {

        $bridgeResult =
            ag_region_bridge_request(
                $nativeRegionName,
                'smart_mode',
                $value
            );
    }
    catch (Throwable $e) {

        agrs_reply(
            500,
            false,
            'The local region settings bridge could not be contacted.'
        );
    }


    if (
        empty(
            $bridgeResult['ok']
        )
    ) {

        $bridgeMessage =
            trim(
                (string)(
                    $bridgeResult['message'] ??
                    ''
                )
            );


        if ($bridgeMessage === '') {

            $bridgeMessage =
                'The native Smart Start update failed.';
        }


        agrs_reply(
            502,
            false,
            $bridgeMessage
        );
    }


    agrs_reply(
        200,
        true,
        'Smart Start updated through the native Regions window.'
    );
}


/*
 * WEB_NATIVE_MAPS_COMBO_BRIDGE
 *
 * Maps is manager-owned. The web page sends only the requested
 * native Maps mode to the protected interactive bridge.
 *
 * The native grid manager performs the real update and writes
 * whatever Map configuration it requires.
 */

if ($setting === 'map_type') {

    $allowedMapTypes =
        [
            'Default',
            'None',
            'Simple',
            'Good',
            'Better',
            'Best',
        ];


    if (
        !in_array(
            $value,
            $allowedMapTypes,
            true
        )
    ) {

        agrs_reply(
            400,
            false,
            'Invalid Maps setting.'
        );
    }


    /*
     * Find the INI section containing the already-verified UUID.
     * The section name is the canonical name shown in the native
     * Regions window.
     */

    $nativeRegionName =
        '';

    $currentSection =
        '';


    $regionLines =
        preg_split(
            '/\r\n|\r|\n/',
            $originalRaw
        );


    foreach ($regionLines as $regionLine) {

        if (
            preg_match(
                '/^\s*\[([^\]]+)\]\s*$/',
                $regionLine,
                $sectionMatch
            )
        ) {

            $currentSection =
                trim(
                    (string)$sectionMatch[1]
                );

            continue;
        }


        if (
            preg_match(
                '/^\s*RegionUUID\s*=\s*"?([0-9a-fA-F-]{36})"?/i',
                $regionLine,
                $uuidMatch
            )
        ) {

            if (
                strcasecmp(
                    (string)$uuidMatch[1],
                    (string)$uuid
                ) === 0
            ) {

                $nativeRegionName =
                    $currentSection;

                break;
            }
        }
    }


    if ($nativeRegionName === '') {

        agrs_reply(
            409,
            false,
            'The native region name could not be determined from the Region INI.'
        );
    }


    if (
        $regionName !== '' &&
        strcasecmp(
            $regionName,
            $nativeRegionName
        ) !== 0
    ) {

        agrs_reply(
            409,
            false,
            'The web region name does not match the Region INI.'
        );
    }


    require_once
        __DIR__ .
        '/core/region-settings-bridge.php';


    try {

        $bridgeResult =
            ag_region_bridge_request(
                $nativeRegionName,
                'maps',
                $value
            );
    }
    catch (Throwable $e) {

        agrs_reply(
            500,
            false,
            'The local region settings bridge could not be contacted.'
        );
    }


    if (
        empty(
            $bridgeResult['ok']
        )
    ) {

        $bridgeMessage =
            trim(
                (string)(
                    $bridgeResult['message'] ??
                    ''
                )
            );


        if ($bridgeMessage === '') {

            $bridgeMessage =
                'The native Maps update failed.';
        }


        agrs_reply(
            502,
            false,
            $bridgeMessage
        );
    }


    agrs_reply(
        200,
        true,
        'Maps updated through the native Regions window.'
    );
}


/*
 * WEB_NATIVE_PHYSICS_BRIDGE
 */

if ($setting === 'physics') {

    if (
        !in_array(
            $value,
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

        agrs_reply(
            400,
            false,
            'Invalid Physics setting.'
        );
    }


    $nativeRegionName =
        '';

    $currentSection =
        '';


    $regionLines =
        preg_split(
            '/\r\n|\r|\n/',
            $originalRaw
        );


    foreach ($regionLines as $regionLine) {

        if (
            preg_match(
                '/^\s*\[([^\]]+)\]\s*$/',
                $regionLine,
                $sectionMatch
            )
        ) {

            $currentSection =
                trim(
                    (string)$sectionMatch[1]
                );

            continue;
        }


        if (
            preg_match(
                '/^\s*RegionUUID\s*=\s*"?([0-9a-fA-F-]{36})"?/i',
                $regionLine,
                $uuidMatch
            )
        ) {

            if (
                strcasecmp(
                    (string)$uuidMatch[1],
                    (string)$uuid
                ) === 0
            ) {

                $nativeRegionName =
                    $currentSection;

                break;
            }
        }
    }


    if ($nativeRegionName === '') {

        agrs_reply(
            409,
            false,
            'The GUI region name could not be determined.'
        );
    }


    require_once
        __DIR__ .
        '/core/region-settings-bridge.php';


    try {

        $bridgeResult =
            ag_region_bridge_request(
                $nativeRegionName,
                'physics',
                $value
            );
    }
    catch (Throwable $e) {

        agrs_reply(
            500,
            false,
            'The local region settings bridge could not be contacted.'
        );
    }


    if (empty($bridgeResult['ok'])) {

        agrs_reply(
            502,
            false,
            trim(
                (string)(
                    $bridgeResult['message'] ??
                    'The GUI Physics update failed.'
                )
            )
        );
    }


    agrs_reply(
        200,
        true,
        'Physics updated through the GUI.'
    );
}


/*
 * WEB_NATIVE_BIRDS_BRIDGE
 */

if ($setting === 'birds') {

    $birdsValue =
        strtolower(
            trim(
                (string)$value
            )
        );


    if (
        !in_array(
            $birdsValue,
            [
                '0',
                '1',
                'false',
                'true',
                'off',
                'on',
                'no',
                'yes',
            ],
            true
        )
    ) {

        agrs_reply(
            400,
            false,
            'Invalid Birds setting.'
        );
    }


    $nativeRegionName =
        '';

    $currentSection =
        '';


    $regionLines =
        preg_split(
            '/\r\n|\r|\n/',
            $originalRaw
        );


    foreach ($regionLines as $regionLine) {

        if (
            preg_match(
                '/^\s*\[([^\]]+)\]\s*$/',
                $regionLine,
                $sectionMatch
            )
        ) {

            $currentSection =
                trim(
                    (string)$sectionMatch[1]
                );

            continue;
        }


        if (
            preg_match(
                '/^\s*RegionUUID\s*=\s*"?([0-9a-fA-F-]{36})"?/i',
                $regionLine,
                $uuidMatch
            )
        ) {

            if (
                strcasecmp(
                    (string)$uuidMatch[1],
                    (string)$uuid
                ) === 0
            ) {

                $nativeRegionName =
                    $currentSection;

                break;
            }
        }
    }


    if ($nativeRegionName === '') {

        agrs_reply(
            409,
            false,
            'The GUI region name could not be determined.'
        );
    }


    require_once
        __DIR__ .
        '/core/region-settings-bridge.php';


    try {

        $bridgeResult =
            ag_region_bridge_request(
                $nativeRegionName,
                'birds',
                $birdsValue
            );
    }
    catch (Throwable $e) {

        agrs_reply(
            500,
            false,
            'The local region settings bridge could not be contacted.'
        );
    }


    if (empty($bridgeResult['ok'])) {

        agrs_reply(
            502,
            false,
            trim(
                (string)(
                    $bridgeResult['message'] ??
                    'The GUI Birds update failed.'
                )
            )
        );
    }


    agrs_reply(
        200,
        true,
        'Birds updated through the GUI.'
    );
}


/*
 * WEB_NATIVE_TIDES_BRIDGE
 */

if ($setting === 'tides') {

    $tidesValue =
        strtolower(
            trim(
                (string)$value
            )
        );


    if (
        !in_array(
            $tidesValue,
            [
                '0',
                '1',
                'false',
                'true',
                'off',
                'on',
                'no',
                'yes',
            ],
            true
        )
    ) {

        agrs_reply(
            400,
            false,
            'Invalid Tides setting.'
        );
    }


    $nativeRegionName =
        '';

    $currentSection =
        '';


    $regionLines =
        preg_split(
            '/\r\n|\r|\n/',
            $originalRaw
        );


    foreach ($regionLines as $regionLine) {

        if (
            preg_match(
                '/^\s*\[([^\]]+)\]\s*$/',
                $regionLine,
                $sectionMatch
            )
        ) {

            $currentSection =
                trim(
                    (string)$sectionMatch[1]
                );

            continue;
        }


        if (
            preg_match(
                '/^\s*RegionUUID\s*=\s*"?([0-9a-fA-F-]{36})"?/i',
                $regionLine,
                $uuidMatch
            )
        ) {

            if (
                strcasecmp(
                    (string)$uuidMatch[1],
                    (string)$uuid
                ) === 0
            ) {

                $nativeRegionName =
                    $currentSection;

                break;
            }
        }
    }


    if ($nativeRegionName === '') {

        agrs_reply(
            409,
            false,
            'The GUI region name could not be determined.'
        );
    }


    require_once
        __DIR__ .
        '/core/region-settings-bridge.php';


    try {

        $bridgeResult =
            ag_region_bridge_request(
                $nativeRegionName,
                'tides',
                $tidesValue
            );
    }
    catch (Throwable $e) {

        agrs_reply(
            500,
            false,
            'The local region settings bridge could not be contacted.'
        );
    }


    if (empty($bridgeResult['ok'])) {

        agrs_reply(
            502,
            false,
            trim(
                (string)(
                    $bridgeResult['message'] ??
                    'The GUI Tides update failed.'
                )
            )
        );
    }


    agrs_reply(
        200,
        true,
        'Tides updated through the GUI.'
    );
}


/*
 * WEB_NATIVE_TELEPORT_BRIDGE
 */

if ($setting === 'teleport') {

    $teleportValue =
        strtolower(
            trim(
                (string)$value
            )
        );


    if (
        !in_array(
            $teleportValue,
            [
                '0',
                '1',
                'false',
                'true',
                'off',
                'on',
                'no',
                'yes',
            ],
            true
        )
    ) {

        agrs_reply(
            400,
            false,
            'Invalid Teleport setting.'
        );
    }


    $nativeRegionName =
        '';

    $currentSection =
        '';


    $regionLines =
        preg_split(
            '/\r\n|\r|\n/',
            $originalRaw
        );


    foreach ($regionLines as $regionLine) {

        if (
            preg_match(
                '/^\s*\[([^\]]+)\]\s*$/',
                $regionLine,
                $sectionMatch
            )
        ) {

            $currentSection =
                trim(
                    (string)$sectionMatch[1]
                );

            continue;
        }


        if (
            preg_match(
                '/^\s*RegionUUID\s*=\s*"?([0-9a-fA-F-]{36})"?/i',
                $regionLine,
                $uuidMatch
            )
        ) {

            if (
                strcasecmp(
                    (string)$uuidMatch[1],
                    (string)$uuid
                ) === 0
            ) {

                $nativeRegionName =
                    $currentSection;

                break;
            }
        }
    }


    if ($nativeRegionName === '') {

        agrs_reply(
            409,
            false,
            'The GUI region name could not be determined.'
        );
    }


    require_once
        __DIR__ .
        '/core/region-settings-bridge.php';


    try {

        $bridgeResult =
            ag_region_bridge_request(
                $nativeRegionName,
                'teleport',
                $teleportValue
            );
    }
    catch (Throwable $e) {

        agrs_reply(
            500,
            false,
            'The local region settings bridge could not be contacted.'
        );
    }


    if (empty($bridgeResult['ok'])) {

        agrs_reply(
            502,
            false,
            trim(
                (string)(
                    $bridgeResult['message'] ??
                    'The GUI Teleport update failed.'
                )
            )
        );
    }


    agrs_reply(
        200,
        true,
        'Teleport updated through the GUI.'
    );
}


/*
 * WEB_NATIVE_ALLOW_BRIDGE
 */

if ($setting === 'allow') {

    $allowValue =
        strtolower(
            trim(
                (string)$value
            )
        );

    if (
        !in_array(
            $allowValue,
            [
                '0',
                '1',
                'false',
                'true',
                'off',
                'on',
                'no',
                'yes',
            ],
            true
        )
    ) {

        agrs_reply(
            400,
            false,
            'Invalid Allow setting.'
        );
    }

    $nativeRegionName =
        '';

    $currentSection =
        '';

    $regionLines =
        preg_split(
            '/\r\n|\r|\n/',
            $originalRaw
        );

    foreach ($regionLines as $regionLine) {

        if (
            preg_match(
                '/^\s*\[([^\]]+)\]\s*$/',
                $regionLine,
                $sectionMatch
            )
        ) {

            $currentSection =
                trim(
                    (string)$sectionMatch[1]
                );

            continue;
        }

        if (
            preg_match(
                '/^\s*RegionUUID\s*=\s*"?([0-9a-fA-F-]{36})"?/i',
                $regionLine,
                $uuidMatch
            )
        ) {

            if (
                strcasecmp(
                    (string)$uuidMatch[1],
                    (string)$uuid
                ) === 0
            ) {

                $nativeRegionName =
                    $currentSection;

                break;
            }
        }
    }

    if ($nativeRegionName === '') {

        agrs_reply(
            409,
            false,
            'The GUI region name could not be determined.'
        );
    }

    require_once
        __DIR__ .
        '/core/region-settings-bridge.php';

    try {

        $bridgeResult =
            ag_region_bridge_request(
                $nativeRegionName,
                'allow',
                $allowValue
            );
    }
    catch (Throwable $e) {

        agrs_reply(
            500,
            false,
            'The local region settings bridge could not be contacted.'
        );
    }

    if (empty($bridgeResult['ok'])) {

        agrs_reply(
            502,
            false,
            trim(
                (string)(
                    $bridgeResult['message'] ??
                    'The GUI Allow update failed.'
                )
            )
        );
    }

    agrs_reply(
        200,
        true,
        'Allow updated through the GUI.'
    );
}


/*
 * WEB_NATIVE_OWNER_GOD_BRIDGE
 */

if ($setting === 'owner_god') {

    $ownerGodValue =
        strtolower(
            trim(
                (string)$value
            )
        );

    if (
        !in_array(
            $ownerGodValue,
            [
                '0',
                '1',
                'false',
                'true',
                'off',
                'on',
                'no',
                'yes',
            ],
            true
        )
    ) {

        agrs_reply(
            400,
            false,
            'Invalid Owner God setting.'
        );
    }

    $nativeRegionName =
        '';

    $currentSection =
        '';

    $regionLines =
        preg_split(
            '/\r\n|\r|\n/',
            $originalRaw
        );

    foreach ($regionLines as $regionLine) {

        if (
            preg_match(
                '/^\s*\[([^\]]+)\]\s*$/',
                $regionLine,
                $sectionMatch
            )
        ) {

            $currentSection =
                trim(
                    (string)$sectionMatch[1]
                );

            continue;
        }

        if (
            preg_match(
                '/^\s*RegionUUID\s*=\s*"?([0-9a-fA-F-]{36})"?/i',
                $regionLine,
                $uuidMatch
            )
        ) {

            if (
                strcasecmp(
                    (string)$uuidMatch[1],
                    (string)$uuid
                ) === 0
            ) {

                $nativeRegionName =
                    $currentSection;

                break;
            }
        }
    }

    if ($nativeRegionName === '') {

        agrs_reply(
            409,
            false,
            'The GUI region name could not be determined.'
        );
    }

    require_once
        __DIR__ .
        '/core/region-settings-bridge.php';

    try {

        $bridgeResult =
            ag_region_bridge_request(
                $nativeRegionName,
                'owner_god',
                $ownerGodValue
            );
    }
    catch (Throwable $e) {

        agrs_reply(
            500,
            false,
            'The local region settings bridge could not be contacted.'
        );
    }

    if (empty($bridgeResult['ok'])) {

        agrs_reply(
            502,
            false,
            trim(
                (string)(
                    $bridgeResult['message'] ??
                    'The GUI Owner God update failed.'
                )
            )
        );
    }

    agrs_reply(
        200,
        true,
        'Owner God updated through the GUI.'
    );
}


/*
 * WEB_NATIVE_MANAGER_GOD_BRIDGE
 */

if ($setting === 'manager_god') {

    $managerGodValue =
        strtolower(
            trim(
                (string)$value
            )
        );

    if (
        !in_array(
            $managerGodValue,
            [
                '0',
                '1',
                'false',
                'true',
                'off',
                'on',
                'no',
                'yes',
            ],
            true
        )
    ) {

        agrs_reply(
            400,
            false,
            'Invalid Manager God setting.'
        );
    }

    $nativeRegionName =
        '';

    $currentSection =
        '';

    $regionLines =
        preg_split(
            '/\r\n|\r|\n/',
            $originalRaw
        );

    foreach ($regionLines as $regionLine) {

        if (
            preg_match(
                '/^\s*\[([^\]]+)\]\s*$/',
                $regionLine,
                $sectionMatch
            )
        ) {

            $currentSection =
                trim(
                    (string)$sectionMatch[1]
                );

            continue;
        }

        if (
            preg_match(
                '/^\s*RegionUUID\s*=\s*"?([0-9a-fA-F-]{36})"?/i',
                $regionLine,
                $uuidMatch
            )
        ) {

            if (
                strcasecmp(
                    (string)$uuidMatch[1],
                    (string)$uuid
                ) === 0
            ) {

                $nativeRegionName =
                    $currentSection;

                break;
            }
        }
    }

    if ($nativeRegionName === '') {

        agrs_reply(
            409,
            false,
            'The GUI region name could not be determined.'
        );
    }

    require_once
        __DIR__ .
        '/core/region-settings-bridge.php';

    try {

        $bridgeResult =
            ag_region_bridge_request(
                $nativeRegionName,
                'manager_god',
                $managerGodValue
            );
    }
    catch (Throwable $e) {

        agrs_reply(
            500,
            false,
            'The local region settings bridge could not be contacted.'
        );
    }

    if (empty($bridgeResult['ok'])) {

        agrs_reply(
            502,
            false,
            trim(
                (string)(
                    $bridgeResult['message'] ??
                    'The GUI Manager God update failed.'
                )
            )
        );
    }

    agrs_reply(
        200,
        true,
        'Manager God updated through the GUI.'
    );
}


/*
 * WEB_NATIVE_AUTOBACKUP_BRIDGE
 */

if ($setting === 'auto_backup') {

    $autoBackupValue =
        strtolower(
            trim(
                (string)$value
            )
        );

    if (
        !in_array(
            $autoBackupValue,
            [
                '0',
                '1',
                'false',
                'true',
                'off',
                'on',
                'no',
                'yes',
            ],
            true
        )
    ) {

        agrs_reply(
            400,
            false,
            'Invalid AutoBackup setting.'
        );
    }

    $nativeRegionName =
        '';

    $currentSection =
        '';

    $regionLines =
        preg_split(
            '/\r\n|\r|\n/',
            $originalRaw
        );

    foreach ($regionLines as $regionLine) {

        if (
            preg_match(
                '/^\s*\[([^\]]+)\]\s*$/',
                $regionLine,
                $sectionMatch
            )
        ) {

            $currentSection =
                trim(
                    (string)$sectionMatch[1]
                );

            continue;
        }

        if (
            preg_match(
                '/^\s*RegionUUID\s*=\s*"?([0-9a-fA-F-]{36})"?/i',
                $regionLine,
                $uuidMatch
            )
        ) {

            if (
                strcasecmp(
                    (string)$uuidMatch[1],
                    (string)$uuid
                ) === 0
            ) {

                $nativeRegionName =
                    $currentSection;

                break;
            }
        }
    }

    if ($nativeRegionName === '') {

        agrs_reply(
            409,
            false,
            'The GUI region name could not be determined.'
        );
    }

    require_once
        __DIR__ .
        '/core/region-settings-bridge.php';

    try {

        $bridgeResult =
            ag_region_bridge_request(
                $nativeRegionName,
                'auto_backup',
                $autoBackupValue
            );
    }
    catch (Throwable $e) {

        agrs_reply(
            500,
            false,
            'The local region settings bridge could not be contacted.'
        );
    }

    if (empty($bridgeResult['ok'])) {

        agrs_reply(
            502,
            false,
            trim(
                (string)(
                    $bridgeResult['message'] ??
                    'The GUI AutoBackup update failed.'
                )
            )
        );
    }

    agrs_reply(
        200,
        true,
        'AutoBackup updated through the GUI.'
    );
}


/*
 * WEB_NATIVE_PUBLICITY_BRIDGE
 */

if ($setting === 'publicity') {

    $publicityValue =
        strtolower(
            trim(
                (string)$value
            )
        );

    if (
        !in_array(
            $publicityValue,
            [
                '0',
                '1',
                'false',
                'true',
                'off',
                'on',
                'no',
                'yes',
            ],
            true
        )
    ) {

        agrs_reply(
            400,
            false,
            'Invalid Publicity setting.'
        );
    }

    $nativeRegionName =
        '';

    $currentSection =
        '';

    $regionLines =
        preg_split(
            '/\r\n|\r|\n/',
            $originalRaw
        );

    foreach ($regionLines as $regionLine) {

        if (
            preg_match(
                '/^\s*\[([^\]]+)\]\s*$/',
                $regionLine,
                $sectionMatch
            )
        ) {

            $currentSection =
                trim(
                    (string)$sectionMatch[1]
                );

            continue;
        }

        if (
            preg_match(
                '/^\s*RegionUUID\s*=\s*"?([0-9a-fA-F-]{36})"?/i',
                $regionLine,
                $uuidMatch
            )
        ) {

            if (
                strcasecmp(
                    (string)$uuidMatch[1],
                    (string)$uuid
                ) === 0
            ) {

                $nativeRegionName =
                    $currentSection;

                break;
            }
        }
    }

    if ($nativeRegionName === '') {

        agrs_reply(
            409,
            false,
            'The GUI region name could not be determined.'
        );
    }

    require_once
        __DIR__ .
        '/core/region-settings-bridge.php';

    try {

        $bridgeResult =
            ag_region_bridge_request(
                $nativeRegionName,
                'publicity',
                $publicityValue
            );
    }
    catch (Throwable $e) {

        agrs_reply(
            500,
            false,
            'The local region settings bridge could not be contacted.'
        );
    }

    if (empty($bridgeResult['ok'])) {

        agrs_reply(
            502,
            false,
            trim(
                (string)(
                    $bridgeResult['message'] ??
                    'The GUI Publicity update failed.'
                )
            )
        );
    }

    agrs_reply(
        200,
        true,
        'Publicity updated through the GUI.'
    );
}


/*
 * Remaining manager-owned settings stay locked until their
 * native bridge handlers are installed.
 */

if (
    in_array(
        $setting,
        [
        ],
        true
    )
) {

    agrs_reply(
        409,
        false,
        'This setting is managed by the native Regions window and is not yet enabled through the web bridge.'
    );
}

$newRaw =
    $originalRaw;


/*
 * ----------------------------------------------------------
 * SMART START
 * ----------------------------------------------------------
 */

if ($setting === 'smart_mode') {

    if (
        !in_array(
            $value,
            [
                'off',
                'boot',
                'suspend',
            ],
            true
        )
    ) {
        agrs_reply(
            400,
            false,
            'Invalid Smart Start setting.'
        );
    }

    if ($value === 'boot') {

        $newRaw =
            agrs_set_key(
                $newRaw,
                'SmartBoot',
                'True'
            );

        $newRaw =
            agrs_set_key(
                $newRaw,
                'SmartStart',
                'False'
            );

    }
    elseif ($value === 'suspend') {

        $newRaw =
            agrs_set_key(
                $newRaw,
                'SmartBoot',
                'False'
            );

        $newRaw =
            agrs_set_key(
                $newRaw,
                'SmartStart',
                'True'
            );

    }
    else {

        $newRaw =
            agrs_set_key(
                $newRaw,
                'SmartBoot',
                'False'
            );

        $newRaw =
            agrs_set_key(
                $newRaw,
                'SmartStart',
                'False'
            );
    }
}


/*
 * ----------------------------------------------------------
 * MAPS
 * ----------------------------------------------------------
 */

elseif ($setting === 'map_type') {

    $allowed =
        [
            'Default',
            'Simple',
            'Good',
            'Better',
            'Best',
        ];

    if (
        !in_array(
            $value,
            $allowed,
            true
        )
    ) {
        agrs_reply(
            400,
            false,
            'Invalid map setting.'
        );
    }

    $newRaw =
        agrs_set_key(
            $newRaw,
            'MapType',
            $value
        );
}


/*
 * ----------------------------------------------------------
 * PHYSICS
 * ----------------------------------------------------------
 */

elseif ($setting === 'physics') {

    if (
        !in_array(
            $value,
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
        agrs_reply(
            400,
            false,
            'Invalid physics setting.'
        );
    }

    $newRaw =
        agrs_set_key(
            $newRaw,
            'Physics',
            $value
        );
}


/*
 * ----------------------------------------------------------
 * CHECKBOX SETTINGS
 * ----------------------------------------------------------
 */

elseif (
    in_array(
        $setting,
        [
            'birds',
            'tides',
            'teleport',
            'allow',
            'owner_god',
            'manager_god',
            'auto_backup',
            'publicity',
        ],
        true
    )
) {

    if (
        $value !== '0' &&
        $value !== '1'
    ) {
        agrs_reply(
            400,
            false,
            'Invalid checkbox value.'
        );
    }

    $enabled =
        $value === '1';


    /* WEB_MANAGER_OWNED_SETTINGS_READ_ONLY */
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(409);
    echo json_encode([
        'ok' => false,
        'error' => 'This setting is managed by the native Regions window and is read-only here.'
    ]);
    exit;

    switch ($setting) {

        case 'birds':

            $newRaw =
                agrs_set_key(
                    $newRaw,
                    'Birds',
                    $enabled
                        ? 'True'
                        : ''
                );

            break;


        case 'tides':

            $newRaw =
                agrs_set_key(
                    $newRaw,
                    'Tides',
                    $enabled
                        ? 'True'
                        : ''
                );

            break;


        case 'teleport':

            $newRaw =
                agrs_set_key(
                    $newRaw,
                    'Teleport',
                    $enabled
                        ? 'True'
                        : 'False'
                );

            break;


        case 'allow':

            $newRaw =
                agrs_set_key(
                    $newRaw,
                    'AllowGods',
                    $enabled
                        ? 'True'
                        : 'False'
                );

            break;


        case 'owner_god':

            $newRaw =
                agrs_set_key(
                    $newRaw,
                    'RegionGod',
                    $enabled
                        ? 'True'
                        : 'False'
                );

            break;


        case 'manager_god':

            $newRaw =
                agrs_set_key(
                    $newRaw,
                    'ManagerGod',
                    $enabled
                        ? 'True'
                        : 'False'
                );

            break;


        case 'auto_backup':

            /*
             * Native option is Auto Backup.
             * INI stores the inverse:
             *
             * SkipAutoBackup=True means Auto Backup OFF.
             */

            $newRaw =
                agrs_set_key(
                    $newRaw,
                    'SkipAutoBackup',
                    $enabled
                        ? ''
                        : 'True'
                );

            break;


        case 'publicity':

            /*
             * Match the native Regions GUI exactly.
             *
             * OFF -> RegionSnapShot=
             * ON  -> RegionSnapShot=True
             *
             * The separate Publicity key is intentionally
             * left untouched.
             */

            $newRaw =
                agrs_set_key(
                    $newRaw,
                    'RegionSnapShot',
                    $enabled
                        ? 'True'
                        : ''
                );

            break;
    }

}
else {

    agrs_reply(
        400,
        false,
        'Unknown region setting.'
    );
}


/*
 * No change required.
 */

if ($newRaw === $originalRaw) {

    agrs_reply(
        200,
        true,
        'Setting is already current.'
    );
}


/*
 * Backup before write.
 */

$backupRoot =
    ag_dg_path(
        '_REGION_CHANGE_BACKUPS'
    );

if (
    !is_dir($backupRoot) &&
    !@mkdir(
        $backupRoot,
        0775,
        true
    ) &&
    !is_dir($backupRoot)
) {

    agrs_reply(
        500,
        false,
        'Could not create the region backup folder.'
    );
}


$safeRegion =
    preg_replace(
        '/[^A-Za-z0-9._-]+/',
        '_',
        $regionName
    );

if (
    !is_string($safeRegion) ||
    $safeRegion === ''
) {
    $safeRegion =
        substr(
            $uuid,
            0,
            8
        );
}


$backupFile =
    rtrim(
        $backupRoot,
        '/\\'
    ) .
    DIRECTORY_SEPARATOR .
    date('Ymd-His') .
    '-web-setting-' .
    $safeRegion .
    '-' .
    bin2hex(
        random_bytes(3)
    ) .
    '.ini';


if (
    !@copy(
        $path,
        $backupFile
    )
) {

    agrs_reply(
        500,
        false,
        'Could not back up the Region INI.'
    );
}


/*
 * Save and verify.
 */

$bytes =
    @file_put_contents(
        $path,
        $newRaw,
        LOCK_EX
    );

if ($bytes === false) {

    agrs_reply(
        500,
        false,
        'Could not save the Region INI.'
    );
}


$verify =
    @file_get_contents(
        $path
    );

if (
    !is_string($verify) ||
    $verify !== $newRaw
) {

    agrs_reply(
        500,
        false,
        'Region INI verification failed.'
    );
}


agrs_reply(
    200,
    true,
    'Region setting saved.'
);