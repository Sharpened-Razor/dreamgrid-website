<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';

ag_require_same_origin_post();

ag_no_cache();

header('Content-Type: application/json; charset=utf-8');

$session = ag_require_admin();

function enabledReply(
    bool $ok,
    string $message,
    int $status = 200,
    array $extra = []
): never
{
    http_response_code($status);

    echo json_encode(
        array_merge(
            [
                'ok' =>
                    $ok,

                $ok
                    ? 'message'
                    : 'error' =>
                    $message,
            ],
            $extra
        ),
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !==
    'POST'
) {
    enabledReply(
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

$enabledRaw =
    trim(
        (string)(
            $_POST['enabled'] ??
            ''
        )
    );


if ($regionName === '') {
    enabledReply(
        false,
        'Region name is required.',
        400
    );
}


if (
    !in_array(
        $enabledRaw,
        [
            '0',
            '1'
        ],
        true
    )
) {
    enabledReply(
        false,
        'Invalid Enabled value.',
        400
    );
}


$enabled =
    $enabledRaw === '1';


$regionsRoot =
    ag_dg_regions_root();


$pattern =
    $regionsRoot .
    '/*/Region/*.ini';


$files =
    glob(
        $pattern
    );

if (!is_array($files)) {
    $files = [];
}


$matches = [];


foreach ($files as $path) {

    $text =
        @file_get_contents(
            $path
        );

    if ($text === false) {
        continue;
    }


    if (
        !preg_match(
            '/^\s*\[([^\]]+)\]\s*$/m',
            $text,
            $section
        )
    ) {
        continue;
    }


    $iniRegionName =
        trim(
            (string)$section[1]
        );


    if (
        strcasecmp(
            $iniRegionName,
            $regionName
        ) === 0
    ) {
        $matches[] =
            $path;
    }
}


if (count($matches) === 0) {

    enabledReply(
        false,
        'Region INI was not found.',
        404
    );
}


if (count($matches) !== 1) {

    enabledReply(
        false,
        'More than one Region INI matched this region.',
        409
    );
}


$path =
    $matches[0];


$handle =
    @fopen(
        $path,
        'c+'
    );


if (!$handle) {

    enabledReply(
        false,
        'Region INI could not be opened.',
        500
    );
}


if (
    !flock(
        $handle,
        LOCK_EX
    )
) {

    fclose(
        $handle
    );

    enabledReply(
        false,
        'Region INI could not be locked.',
        500
    );
}


rewind(
    $handle
);


$current =
    stream_get_contents(
        $handle
    );


if ($current === false) {

    flock(
        $handle,
        LOCK_UN
    );

    fclose(
        $handle
    );

    enabledReply(
        false,
        'Region INI could not be read.',
        500
    );
}


$enabledMatches = [];

preg_match_all(
    '/^\s*Enabled\s*=\s*(True|False)\s*$/mi',
    $current,
    $enabledMatches
);


if (
    count(
        $enabledMatches[0]
    ) !== 1
) {

    flock(
        $handle,
        LOCK_UN
    );

    fclose(
        $handle
    );

    enabledReply(
        false,
        'Expected exactly one Enabled=True/False setting in the Region INI.',
        500
    );
}


$newValue =
    $enabled
        ? 'True'
        : 'False';


$newText =
    preg_replace(
        '/^\s*Enabled\s*=\s*(True|False)\s*$/mi',
        'Enabled=' .
        $newValue,
        $current,
        1,
        $replaceCount
    );


if (
    !is_string($newText) ||
    $replaceCount !== 1
) {

    flock(
        $handle,
        LOCK_UN
    );

    fclose(
        $handle
    );

    enabledReply(
        false,
        'Enabled setting could not be changed.',
        500
    );
}


/*
 * Keep the most recent pre-change copy in the jobs folder.
 */
$backupDir =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'jobs' .
    DIRECTORY_SEPARATOR .
    'region-enabled-backups';


if (
    !is_dir($backupDir) &&
    !@mkdir(
        $backupDir,
        0775,
        true
    ) &&
    !is_dir($backupDir)
) {

    flock(
        $handle,
        LOCK_UN
    );

    fclose(
        $handle
    );

    enabledReply(
        false,
        'Enable-setting backup folder could not be created.',
        500
    );
}


$backupFile =
    $backupDir .
    DIRECTORY_SEPARATOR .
    sha1($path) .
    '.ini.bak';


if (
    @file_put_contents(
        $backupFile,
        $current,
        LOCK_EX
    ) === false
) {

    flock(
        $handle,
        LOCK_UN
    );

    fclose(
        $handle
    );

    enabledReply(
        false,
        'Region INI backup could not be written.',
        500
    );
}


rewind(
    $handle
);

ftruncate(
    $handle,
    0
);


$written =
    fwrite(
        $handle,
        $newText
    );


fflush(
    $handle
);


flock(
    $handle,
    LOCK_UN
);


fclose(
    $handle
);


if (
    $written === false ||
    $written !== strlen($newText)
) {

    @file_put_contents(
        $path,
        $current,
        LOCK_EX
    );

    enabledReply(
        false,
        'Region INI write failed. Previous contents were restored.',
        500
    );
}


/*
 * Verify the saved setting.
 */
$verify =
    @file_get_contents(
        $path
    );


$expected =
    '/^\s*Enabled\s*=\s*' .
    preg_quote(
        $newValue,
        '/'
    ) .
    '\s*$/mi';


if (
    !is_string($verify) ||
    !preg_match(
        $expected,
        $verify
    )
) {

    @file_put_contents(
        $path,
        $current,
        LOCK_EX
    );

    enabledReply(
        false,
        'Enabled setting did not verify. Previous contents were restored.',
        500
    );
}


enabledReply(
    true,
    $regionName .
    ' boot setting changed to Enabled=' .
    $newValue .
    '.',
    200,
    [
        'region' =>
            $regionName,

        'enabled' =>
            $enabled,

        'value' =>
            $newValue,
    ]
);