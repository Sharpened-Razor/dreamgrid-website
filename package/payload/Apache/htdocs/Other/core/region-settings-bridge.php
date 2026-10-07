<?php
declare(strict_types=1);

require_once __DIR__ . '/dreamgrid-env.php';

/*
 * Local bridge from Apache/PHP to the interactive native
 * Regions window.
 *
 * No database credentials or grid secrets are stored here.
 */

function ag_region_bridge_request(
    string $region,
    string $setting,
    string $value
): array {

    if (
        $region === '' ||
        strlen($region) > 128 ||
        preg_match('/[\x00-\x1F]/', $region)
    ) {
        return [
            'ok' => false,
            'message' => 'Invalid region name.',
        ];
    }


    if (
        !in_array(
            $setting,
            [
                'maps',
                'maps_snapshot',
'physics',
'physics_snapshot',
'birds',
'birds_snapshot',
'tides',
'tides_snapshot',
'teleport',
'teleport_snapshot',
'allow',
'allow_snapshot',
'owner_god',
'owner_god_snapshot',
'manager_god',
'manager_god_snapshot',
'auto_backup',
'auto_backup_snapshot',
'publicity',
'publicity_snapshot',
'region_snapshot',
                'smart_mode',
            ],
            true
        )
    ) {
        return [
            'ok' => false,
            'message' => 'Unsupported bridge setting.',
        ];
    }


    $packageRoot =
        dirname(__DIR__);

    $queueDir =
        $packageRoot .
        DIRECTORY_SEPARATOR .
        'private' .
        DIRECTORY_SEPARATOR .
        'region-bridge';


    if (
        !is_dir($queueDir) &&
        !@mkdir(
            $queueDir,
            0775,
            true
        ) &&
        !is_dir($queueDir)
    ) {
        return [
            'ok' => false,
            'message' => 'Could not create the local bridge queue.',
        ];
    }


    /*
     * Generate a 36-character request identifier compatible
     * with the Windows bridge.
     */

    try {

        $hex =
            bin2hex(
                random_bytes(16)
            );
    }
    catch (Throwable $e) {

        return [
            'ok' => false,
            'message' => 'Could not create a bridge request identifier.',
        ];
    }


    $requestId =
        substr($hex, 0, 8) .
        '-' .
        substr($hex, 8, 4) .
        '-' .
        substr($hex, 12, 4) .
        '-' .
        substr($hex, 16, 4) .
        '-' .
        substr($hex, 20, 12);


    $requestPath =
        $queueDir .
        DIRECTORY_SEPARATOR .
        $requestId .
        '.request.json';

    $responsePath =
        $queueDir .
        DIRECTORY_SEPARATOR .
        $requestId .
        '.response.json';

    $tempPath =
        $requestPath .
        '.tmp';


    $request =
        [
            'id' => $requestId,
            'region' => $region,
            'setting' => $setting,
            'value' => $value,
            'created' => gmdate('c'),
        ];


    $json =
        json_encode(
            $request,
            JSON_UNESCAPED_SLASHES
        );


    if (!is_string($json)) {

        return [
            'ok' => false,
            'message' => 'Could not encode the bridge request.',
        ];
    }


    if (
        @file_put_contents(
            $tempPath,
            $json,
            LOCK_EX
        ) === false
    ) {
        return [
            'ok' => false,
            'message' => 'Could not write the bridge request.',
        ];
    }


    if (
        !@rename(
            $tempPath,
            $requestPath
        )
    ) {

        @unlink($tempPath);

        return [
            'ok' => false,
            'message' => 'Could not queue the bridge request.',
        ];
    }


    /*
     * Start the already-installed interactive scheduled task.
     *
     * The task name is constant. No request data is passed
     * through the command line.
     */

    if (!function_exists('exec')) {

        @unlink($requestPath);

        return [
            'ok' => false,
            'message' => 'PHP process execution is unavailable.',
        ];
    }


    $systemRoot =
        getenv('SystemRoot');

    if (
        !is_string($systemRoot) ||
        trim($systemRoot) === ''
    ) {

        $systemRoot =
            getenv('WINDIR');
    }


    if (
        !is_string($systemRoot) ||
        trim($systemRoot) === ''
    ) {

        $systemRoot =
            (ag_dg_windows_root() ?? '');
    }


    $schtasks =
        rtrim(
            $systemRoot,
            '/\\'
        ) .
        DIRECTORY_SEPARATOR .
        'System32' .
        DIRECTORY_SEPARATOR .
        'schtasks.exe';


    $command =
        '"' .
        str_replace(
            '"',
            '',
            $schtasks
        ) .
        '"' .
        ' /Run /TN "DreamGrid - Region Settings Bridge"';


    $taskOutput =
        [];

    $taskExit =
        1;


    @exec(
        $command .
        ' 2>&1',
        $taskOutput,
        $taskExit
    );


    if ($taskExit !== 0) {

        @unlink($requestPath);

        return [
            'ok' => false,
            'message' => 'Could not start the local region settings bridge.',
        ];
    }


    /*
     * Wait for the local bridge response.
     *
     * Normal completion takes only a few seconds.
     */

    $deadline =
        microtime(true) +
        15.0;


    do {

        clearstatcache(
            true,
            $responsePath
        );


        if (is_file($responsePath)) {

            $responseRaw =
                @file_get_contents(
                    $responsePath
                );

            @unlink($responsePath);


            if (!is_string($responseRaw)) {

                return [
                    'ok' => false,
                    'message' => 'Could not read the bridge response.',
                ];
            }


            $response =
                json_decode(
                    $responseRaw,
                    true
                );


            if (!is_array($response)) {

                return [
                    'ok' => false,
                    'message' => 'The bridge returned an invalid response.',
                ];
            }


            return $response;
        }


        usleep(
            100000
        );

    } while (
        microtime(true) <
        $deadline
    );


    /*
     * Do not leave a pending request behind after timeout.
     */

    @unlink($requestPath);


    return [
        'ok' => false,
        'message' => 'The local region settings bridge timed out.',
    ];
}