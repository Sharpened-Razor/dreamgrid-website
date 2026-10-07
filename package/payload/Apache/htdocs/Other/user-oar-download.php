<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/oar-user-tools.php';

$session = ag_current_session();

if (!$session) {
    http_response_code(401);
    exit('Not logged in.');
}

$avatar =
    trim(
        (string)(
            $session['avatar'] ??
            ''
        )
    );

$level =
    function_exists('ag_user_level')
        ? (int)ag_user_level($session)
        : (int)($session['level'] ?? 0);

$region =
    trim(
        (string)(
            $_GET['region'] ??
            ''
        )
    );

$slot =
    (int)(
        $_GET['slot'] ??
        0
    );

if ($avatar === '') {
    http_response_code(403);
    exit('Avatar name is unavailable.');
}

if ($region === '') {
    http_response_code(400);
    exit('Region name is required.');
}

if ($slot < 1 || $slot > 2) {
    http_response_code(400);
    exit('Invalid OAR backup slot.');
}


/*
 * Normal resident:
 * only their own region OAR folder is accessible.
 */
$target =
    australiaUserOarSlotPath(
        $avatar,
        $region,
        $slot
    );


/*
 * Admin / Grid Owner:
 * allow locating the same region under its EstateOwner folder.
 */
if (
    !is_file($target) &&
    $level >= 100
) {

    $backupRoot =
        australiaUserOarBackupRoot();

    $regionSafe =
        australiaUserOarSafeComponent(
            $region
        );

    $ownerFolders =
        @glob(
            $backupRoot .
            DIRECTORY_SEPARATOR .
            '*',
            GLOB_ONLYDIR
        );

    if (is_array($ownerFolders)) {

        foreach ($ownerFolders as $ownerFolder) {

            $candidate =
                $ownerFolder .
                DIRECTORY_SEPARATOR .
                $regionSafe .
                DIRECTORY_SEPARATOR .
                'Backup-' .
                $slot .
                '.oar';

            if (is_file($candidate)) {
                $target = $candidate;
                break;
            }
        }
    }
}


if (!is_file($target)) {
    http_response_code(404);
    exit('Saved OAR backup not found.');
}


$rootReal =
    realpath(
        australiaUserOarBackupRoot()
    );

$fileReal =
    realpath(
        $target
    );

if (
    $rootReal === false ||
    $fileReal === false
) {
    http_response_code(404);
    exit('Saved OAR backup not found.');
}


$rootPrefix =
    rtrim(
        $rootReal,
        "\\/"
    ) .
    DIRECTORY_SEPARATOR;


if (
    strncasecmp(
        $fileReal,
        $rootPrefix,
        strlen($rootPrefix)
    ) !== 0
) {
    http_response_code(403);
    exit('Invalid OAR backup path.');
}


$downloadName =
    australiaUserOarSafeComponent(
        $region
    ) .
    '-Backup-' .
    $slot .
    '.oar';


header(
    'Content-Type: application/octet-stream'
);

header(
    'Content-Disposition: attachment; filename="' .
    str_replace(
        '"',
        '',
        $downloadName
    ) .
    '"'
);

header(
    'Content-Length: ' .
    (string)filesize($fileReal)
);

header(
    'Cache-Control: private, no-store, no-cache, must-revalidate'
);

header(
    'Pragma: no-cache'
);


readfile(
    $fileReal
);

exit;