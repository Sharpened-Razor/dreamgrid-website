<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';

ag_require_same_origin_post();

ag_no_cache();

header(
    'Content-Type: application/json; charset=utf-8'
);

$session = ag_require_admin();


function bt_reply(
    bool $ok,
    string $message,
    int $status = 200
): never
{
    http_response_code($status);

    echo json_encode(
        [
            'ok'      => $ok,
            $ok
                ? 'message'
                : 'error' => $message,
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !== 'POST'
) {
    bt_reply(
        false,
        'POST required.',
        405
    );
}


$regionName =
    trim(
        (string)(
            $_POST['region'] ??
            ''
        )
    );


if ($regionName === '') {
    bt_reply(
        false,
        'Region name is required.',
        400
    );
}


$regionsRoot =
    ag_dg_regions_root();


$files =
    glob(
        $regionsRoot .
        '/*/Region/*.ini'
    );


if (!is_array($files)) {
    $files = [];
}


$iniPath = null;


foreach ($files as $candidate) {

    $text =
        @file_get_contents(
            $candidate
        );


    if (!is_string($text)) {
        continue;
    }


    if (
        !preg_match(
            '/^\s*\[([^\]]+)\]\s*$/m',
            $text,
            $match
        )
    ) {
        continue;
    }


    $candidateName =
        trim(
            (string)$match[1]
        );


    if (
        strcasecmp(
            $candidateName,
            $regionName
        ) === 0
    ) {
        $iniPath =
            $candidate;

        break;
    }
}


if ($iniPath === null) {
    bt_reply(
        false,
        'Region INI was not found.',
        404
    );
}


/*
 * Resolve actual DreamGrid folder from the INI.
 *
 * This is important because a displayed region name
 * does not always equal the folder name.
 */
$regionFolder =
    dirname(
        dirname(
            $iniPath
        )
    );


$logPath =
    $regionFolder .
    DIRECTORY_SEPARATOR .
    'OpenSim.log';


if (!is_file($logPath)) {
    bt_reply(
        false,
        'OpenSim.log was not found for this region.',
        404
    );
}


$requestFile =
    ag_dg_path(
        '_WEB_CONTROL' .
        DIRECTORY_SEPARATOR .
        'WebBareTailRequest.txt'
    );


if ($requestFile === null) {

    bt_reply(
        false,
        'DreamGrid web-control path could not be resolved.',
        500
    );
}


$requestDirectory =
    dirname(
        $requestFile
    );


if (
    !is_dir($requestDirectory) &&
    !@mkdir(
        $requestDirectory,
        0775,
        true
    ) &&
    !is_dir($requestDirectory)
) {

    bt_reply(
        false,
        'DreamGrid web-control directory could not be created.',
        500
    );
}


$written =
    @file_put_contents(
        $requestFile,
        $logPath,
        LOCK_EX
    );


if ($written === false) {
    bt_reply(
        false,
        'BareTail request file could not be written.',
        500
    );
}


/*
 * Trigger the already-tested interactive task.
 *
 * No user-supplied command is executed here.
 */
$taskName =
    'DreamGrid - Web BareTail Launcher';


$schtasks =
    ag_dg_schtasks_exe();


$command =
    escapeshellarg(
        $schtasks
    ) .
    ' /Run /TN ' .
    escapeshellarg(
        $taskName
    );


$output = [];
$exitCode = 0;


exec(
    $command . ' 2>&1',
    $output,
    $exitCode
);


if ($exitCode !== 0) {

    bt_reply(
        false,
        'Windows could not trigger the BareTail task: ' .
        implode(
            ' ',
            $output
        ),
        500
    );
}


bt_reply(
    true,
    'BareTail opened for ' .
    $regionName .
    '.'
);