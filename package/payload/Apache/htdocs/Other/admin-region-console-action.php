<?php
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/region-inspection.php';

require_once __DIR__ . '/core/bootstrap.php';

ag_require_same_origin_post();

ag_no_cache();

header(
    'Content-Type: application/json; charset=utf-8'
);

$session = ag_require_admin();


function console_reply(
    bool $ok,
    string $message,
    int $status = 200
): never
{
    http_response_code($status);

    echo json_encode(
        [
            'ok' => $ok,

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

    console_reply(
        false,
        'POST required.',
        405
    );
}


try { $target=ri_select(ri_regions(),$_POST['region']??'',$_POST['uuid']??''); }
catch (InvalidArgumentException $e) { console_reply(false,'Invalid region selection.',400); }
catch (Throwable $e) { console_reply(false,'Region not found.',404); }
$regionName=$target['RegionName']; $iniPath=$target['IniPath'];

/*
 * Resolve actual DreamGrid folder.
 *
 * Important for cases such as:
 *
 * Displayed: Official Region
 * Folder:    Offical Region
 */
$regionFolderPath =
    dirname(
        dirname(
            $iniPath
        )
    );


$regionFolder =
    basename(
        $regionFolderPath
    );


$requestFile =
    ag_dg_path('_WEB_CONTROL' . DIRECTORY_SEPARATOR . 'WebConsoleRequest.txt');


$written =
    @file_put_contents(
        $requestFile,
        $regionFolder,
        LOCK_EX
    );


if ($written === false) {

    console_reply(
        false,
        'Console request could not be written.',
        500
    );
}


$taskName =
    'DreamGrid - Web Console Launcher';


$command =
    '"' . str_replace('"', '', ag_dg_schtasks_exe()) . '"' .
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

    console_reply(
        false,
        'Windows could not trigger the console task: ' .
        implode(
            ' ',
            $output
        ),
        500
    );
}


console_reply(
    true,
    'Console opened for ' .
    $regionName .
    '.'
);