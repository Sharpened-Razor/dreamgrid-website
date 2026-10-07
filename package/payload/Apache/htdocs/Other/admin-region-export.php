<?php
declare(strict_types=1);

/*
 * ============================================================
 * AUSTRALIA CONTROL CENTER
 * DREAMGRID REGION INI EXPORT V5
 *
 * Queues an interactive Windows export request and returns
 * immediately.
 *
 * No browser download.
 * No ZIP.
 * No web folder picker.
 * No browser status polling.
 * ============================================================
 */

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/dreamgrid-env.php';

ag_no_cache();

ag_require_admin();


function dg_export_reply(
    array $data,
    int $status = 200
): never {

    http_response_code(
        $status
    );

    header(
        'Content-Type: application/json; charset=UTF-8'
    );

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


function dg_export_fail(
    string $message,
    int $status = 400
): never {

    dg_export_reply(
        [
            'ok' =>
                false,

            'message' =>
                $message
        ],
        $status
    );
}


if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !==
    'POST'
) {

    dg_export_fail(
        'POST is required.',
        405
    );
}


$action =
    strtolower(
        trim(
            (string)(
                $_POST['action'] ??
                ''
            )
        )
    );


if (
    $action !==
    'start'
) {

    dg_export_fail(
        'Invalid Region Export action.'
    );
}


$decoded =
    json_decode(
        (string)(
            $_POST['regions'] ??
            ''
        ),
        true
    );


if (
    !is_array(
        $decoded
    ) ||
    !$decoded
) {

    dg_export_fail(
        'No regions were selected.'
    );
}


$regions =
    [];


foreach (
    $decoded as
    $name
) {

    if (
        !is_string(
            $name
        )
    ) {
        continue;
    }


    $name =
        trim(
            $name
        );


    if (
        $name === '' ||
        strlen(
            $name
        ) > 255 ||
        preg_match(
            '/[\x00-\x1F]/',
            $name
        )
    ) {
        continue;
    }


    $regions[
        strtolower(
            $name
        )
    ] =
        $name;
}


if (
    !$regions
) {

    dg_export_fail(
        'No valid regions were selected.'
    );
}


if (
    count(
        $regions
    ) > 500
) {

    dg_export_fail(
        'Too many regions were selected.'
    );
}


$queueDir =
    ag_dg_path(
        '_WEB_CONTROL',
        'region-export-picker'
    );


if (
    !is_string(
        $queueDir
    ) ||
    $queueDir === ''
) {

    dg_export_fail(
        'Region Export queue path could not be resolved.',
        500
    );
}


if (
    !is_dir(
        $queueDir
    ) &&
    !@mkdir(
        $queueDir,
        0775,
        true
    ) &&
    !is_dir(
        $queueDir
    )
) {

    dg_export_fail(
        'Region Export queue could not be created.',
        500
    );
}


/*
 * Delete abandoned queue files older than one day.
 */

$cutoff =
    time() -
    86400;


$oldFiles =
    @glob(
        rtrim(
            $queueDir,
            '/\\'
        ) .
        DIRECTORY_SEPARATOR .
        '*'
    );


if (
    is_array(
        $oldFiles
    )
) {

    foreach (
        $oldFiles as
        $oldFile
    ) {

        if (
            !is_string(
                $oldFile
            ) ||
            !is_file(
                $oldFile
            )
        ) {
            continue;
        }


        $mtime =
            @filemtime(
                $oldFile
            );


        if (
            $mtime !== false &&
            $mtime <
            $cutoff
        ) {

            @unlink(
                $oldFile
            );
        }
    }
}


try {

    $requestId =
        bin2hex(
            random_bytes(
                16
            )
        );

}
catch (
    Throwable $error
) {

    dg_export_fail(
        'Could not create the Region Export request identifier.',
        500
    );
}


$requestPath =
    $queueDir .
    DIRECTORY_SEPARATOR .
    $requestId .
    '.request.json';


$tempPath =
    $requestPath .
    '.tmp';


$json =
    json_encode(
        [
            'id' =>
                $requestId,

            'regions' =>
                array_values(
                    $regions
                ),

            'created' =>
                gmdate(
                    'c'
                )
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE |
        JSON_PRETTY_PRINT
    );


if (
    !is_string(
        $json
    ) ||
    @file_put_contents(
        $tempPath,
        $json,
        LOCK_EX
    ) === false
) {

    dg_export_fail(
        'Could not write the Region Export request.',
        500
    );
}


if (
    !@rename(
        $tempPath,
        $requestPath
    )
) {

    @unlink(
        $tempPath
    );


    dg_export_fail(
        'Could not activate the Region Export request.',
        500
    );
}


$taskName =
    'DreamGrid - Region Export Picker Bridge';


if (
    function_exists(
        'ag_dg_schtasks_exe'
    )
) {

    $schtasks =
        ag_dg_schtasks_exe();
}
else {

    $systemRoot =
        (string)(
            getenv(
                'SystemRoot'
            ) ?:
            ''
        );


    $schtasks =
        rtrim(
            $systemRoot,
            '/\\'
        ) .
        DIRECTORY_SEPARATOR .
        'System32' .
        DIRECTORY_SEPARATOR .
        'schtasks.exe';
}


if (
    !is_file(
        $schtasks
    )
) {

    @unlink(
        $requestPath
    );


    dg_export_fail(
        'Windows Task Scheduler could not be located.',
        500
    );
}


$command =
    '"' .
    str_replace(
        '"',
        '',
        $schtasks
    ) .
    '"' .
    ' /Run /TN ' .
    escapeshellarg(
        $taskName
    );


$output =
    [];


$exitCode =
    0;


@exec(
    $command .
    ' 2>&1',
    $output,
    $exitCode
);


if (
    $exitCode !== 0
) {

    @unlink(
        $requestPath
    );


    dg_export_fail(
        'Could not launch the Windows Region Export folder selector.' .
        (
            $output
                ?
                "\n" .
                implode(
                    "\n",
                    $output
                )
                :
                ''
        ),
        500
    );
}


dg_export_reply(
    [
        'ok' =>
            true,

        'requestId' =>
            $requestId,

        'message' =>
            'Windows Region Export folder selector launched.'
    ]
);