<?php

require_once dirname(__DIR__, 2) . '/core/dreamgrid-env.php';

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';
require_once dirname(__DIR__, 2) . '/restore-safety.php';


/*
 * ============================================================
 * Grid
 * USER IAR BACKUPS V1
 *
 * TWO RETAINED BACKUPS PER AVATAR.
 *
 * IMPORTANT:
 * Old backup is NEVER deleted before the new IAR has been
 * successfully created and verified.
 * ============================================================
 */


$session =
    ag_current_session();


if (!$session) {

    header(
        'Location: /Other/login.php'
    );

    exit;
}


$canPrivilegedIar =
    ag_can_use_privileged_user_tools(
        $session
    );

$principalId =
    trim(
        (string)(
            $session['principalId'] ??
            ''
        )
    );


$avatar =
    trim(
        (string)(
            $session['avatar'] ??
            ''
        )
    );


if ($principalId === '') {

    http_response_code(403);

    echo 'Signed account ID is unavailable.';

    exit;
}


if (
    session_status() !==
    PHP_SESSION_ACTIVE
) {

    session_start();
}


if (
    empty(
        $_SESSION['australia_user_iar_csrf']
    )
) {

    $_SESSION['australia_user_iar_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    (string)
    $_SESSION['australia_user_iar_csrf'];


/*
 * ============================================================
 * PATH HELPERS
 * ============================================================
 */


function userIarRoot(): string {

    return
        dirname(dirname(__DIR__, 2), 3) .
        DIRECTORY_SEPARATOR .
        'Autobackup' .
        DIRECTORY_SEPARATOR .
        'User-IAR';
}


function userIarJobsRoot(): string {

    return
        dirname(__DIR__, 2) .
        DIRECTORY_SEPARATOR .
        'jobs';
}


function userIarMetaFile(
    string $job
): string {

    return
        userIarJobsRoot() .
        DIRECTORY_SEPARATOR .
        'user_iar_meta_' .
        $job .
        '.json';
}


function userIarLogFile(
    string $job
): string {

    return
        userIarJobsRoot() .
        DIRECTORY_SEPARATOR .
        'user_iar_' .
        $job .
        '.log';
}


function userIarWorkerFile(
    string $job
): string {

    return
        userIarJobsRoot() .
        DIRECTORY_SEPARATOR .
        'user_iar_' .
        $job .
        '.ps1';
}


/*
 * ============================================================
 * GENERIC HELPERS
 * ============================================================
 */


function userIarJson(
    array $data,
    int $status = 200
): void {

    http_response_code(
        $status
    );

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header(
        'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
    );

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


function userIarValidJob(
    string $job
): bool {

    return (
        preg_match(
            '/^\d{8}_\d{6}_[a-f0-9]{8}$/',
            $job
        ) === 1
    );
}


function userIarWriteMeta(
    string $file,
    array $meta
): void {

    $json =
        json_encode(
            $meta,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        );


    if (
        !is_string(
            $json
        )
        ||
        @file_put_contents(
            $file,
            $json,
            LOCK_EX
        ) === false
    ) {

        throw new Exception(
            'IAR backup metadata could not be saved.'
        );
    }
}


function userIarInsideRoot(
    string $path
) {

    $rootReal =
        realpath(
            userIarRoot()
        );


    if ($rootReal === false) {
        return false;
    }


    $real =
        realpath(
            $path
        );


    if ($real === false) {
        return false;
    }


    $prefix =
        rtrim(
            strtolower(
                str_replace(
                    '\\',
                    '/',
                    $rootReal
                )
            ),
            '/'
        ) .
        '/';


    $candidate =
        strtolower(
            str_replace(
                '\\',
                '/',
                $real
            )
        );


    if (
        strpos(
            $candidate,
            $prefix
        ) !==
        0
    ) {

        return false;
    }


    return $real;
}


function userIarGzipHeader(
    string $path
): bool {

    $fh =
        @fopen(
            $path,
            'rb'
        );


    if (!$fh) {
        return false;
    }


    $magic =
        fread(
            $fh,
            2
        );


    fclose(
        $fh
    );


    return (
        is_string(
            $magic
        )
        &&
        strlen(
            $magic
        ) ===
        2
        &&
        ord(
            $magic[0]
        ) ===
        0x1f
        &&
        ord(
            $magic[1]
        ) ===
        0x8b
    );
}


/*
 * ============================================================
 * ACCOUNT
 * ============================================================
 */


function userIarAccount(
    string $principalId
): array {
    $con =
        ag_db_connect();

if (!$con) {

        throw new Exception(
            'Grid account database is unavailable.'
        );
    }
$stmt =
        mysqli_prepare(
            $con,
            'SELECT FirstName, LastName, UserLevel
             FROM UserAccounts
             WHERE PrincipalID = ?
             LIMIT 1'
        );


    if (!$stmt) {

        mysqli_close(
            $con
        );


        throw new Exception(
            'Could not validate the signed-in account.'
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        's',
        $principalId
    );


    mysqli_stmt_execute(
        $stmt
    );


    mysqli_stmt_bind_result(
        $stmt,
        $first,
        $last,
        $level
    );


    $found =
        mysqli_stmt_fetch(
            $stmt
        );


    mysqli_stmt_close(
        $stmt
    );


    mysqli_close(
        $con
    );


    if (
        !$found
        ||
        (int)$level < 0
    ) {

        throw new Exception(
            'The signed-in avatar is not an active local account.'
        );
    }


    $first =
        trim(
            (string)$first
        );


    $last =
        trim(
            (string)$last
        );


    if (
        $first === ''
        ||
        $last === ''
    ) {

        throw new Exception(
            'A valid First Last avatar name is required.'
        );
    }


    return [
        'first' =>
            $first,

        'last' =>
            $last,

        'avatar' =>
            $first .
            ' ' .
            $last
    ];
}


/*
 * ============================================================
 * REMOTE ADMIN
 * ============================================================
 */


function userIarRemoteAdmin(): array {
    $ini =
        '';

    $regionsRoot =
        ag_dg_regions_root();

    $regionFolders =
        glob(
            $regionsRoot .
            DIRECTORY_SEPARATOR .
            '*',
            GLOB_ONLYDIR
        )
        ?: [];

    foreach(
        $regionFolders
        as
        $regionFolder
    ) {

        $candidate =
            $regionFolder .
            DIRECTORY_SEPARATOR .
            'Opensim.ini';

        if (
            !is_file(
                $candidate
            )
        ) {
            continue;
        }

        $candidateText =
            @file_get_contents(
                $candidate
            );

        if (
            !is_string(
                $candidateText
            )
        ) {
            continue;
        }

        if (
            preg_match(
                '/^\s*access_password\s*=\s*(\S+)\s*$/mi',
                $candidateText
            )
            &&
            preg_match(
                '/^\s*port\s*=\s*(\d+)\s*$/mi',
                $candidateText
            )
        ) {

            $ini =
                $candidate;

            break;
        }
    }

if (
        !is_file(
            $ini
        )
    ) {

        throw new Exception(
            'No region Opensim.ini with RemoteAdmin settings was found.'
        );
    }


    $text =
        @file_get_contents(
            $ini
        );


    if (
        !is_string(
            $text
        )
    ) {

        throw new Exception(
            'RemoteAdmin Opensim.ini could not be read.'
        );
    }


    if (
        !preg_match(
            '/^\s*access_password\s*=\s*(\S+)\s*$/mi',
            $text,
            $passwordMatch
        )
    ) {

        throw new Exception(
            'RemoteAdmin access_password was not found.'
        );
    }


    $password =
        trim(
            (string)$passwordMatch[1]
        );


    if ($password === '') {

        throw new Exception(
            'RemoteAdmin access_password is blank.'
        );
    }


    if (
        !preg_match(
            '/^\s*port\s*=\s*(\d+)\s*$/mi',
            $text,
            $portMatch
        )
    ) {

        throw new Exception(
            'RemoteAdmin port was not found.'
        );
    }


    $port =
        (int)$portMatch[1];


    if ($port <= 0) {

        throw new Exception(
            'RemoteAdmin port is invalid.'
        );
    }


    return [
        'password' =>
            $password,

        'port' =>
            $port
    ];
}


/*
 * ============================================================
 * METADATA
 * ============================================================
 */


function userIarOwnedMetas(
    string $principalId
): array {

    $files =
        glob(
            userIarJobsRoot() .
            DIRECTORY_SEPARATOR .
            'user_iar_meta_*.json'
        )
        ?:
        [];


    $result =
        [];


    foreach(
        $files
        as
        $file
    ) {

        $meta =
            json_decode(
                (string)@file_get_contents(
                    $file
                ),
                true
            );


        if (
            !is_array(
                $meta
            )
        ) {
            continue;
        }


        if (
            !hash_equals(
                (string)(
                    $meta['principalId'] ??
                    ''
                ),
                $principalId
            )
        ) {
            continue;
        }


        $result[] =
            $meta;
    }


    usort(
        $result,
        function(
            $a,
            $b
        ){

            return
                strtotime(
                    (string)(
                        $b['created'] ??
                        ''
                    )
                )
                <=>
                strtotime(
                    (string)(
                        $a['created'] ??
                        ''
                    )
                );
        }
    );


    return $result;
}


function userIarVerifiedBackups(
    string $principalId
): array {

    $result =
        [];


    foreach(
        userIarOwnedMetas(
            $principalId
        )
        as
        $meta
    ) {

        if (
            (string)(
                $meta['status'] ??
                ''
            )
            !==
            'verified'
        ) {
            continue;
        }


        $path =
            (string)(
                $meta['path'] ??
                ''
            );


        $real =
            userIarInsideRoot(
                $path
            );


        if (
            $real === false
            ||
            !is_file(
                $real
            )
            ||
            filesize(
                $real
            ) <= 0
        ) {
            continue;
        }


        $result[] = [
            'jobId' =>
                (string)(
                    $meta['jobId'] ??
                    ''
                ),

            'name' =>
                (string)(
                    $meta['name'] ??
                    basename(
                        $real
                    )
                ),

            'created' =>
                (string)(
                    $meta['created'] ??
                    ''
                ),

            'verifiedAt' =>
                (string)(
                    $meta['verifiedAt'] ??
                    ''
                ),

            'size' =>
                (int)filesize(
                    $real
                ),

            'downloadUrl' =>
                '/Other/FreshUserDashboardExact/UserPages/iar-backups.php?download=' .
                rawurlencode(
                    (string)(
                        $meta['jobId'] ??
                        ''
                    )
                )
        ];
    }


    return $result;
}


/*
 * ============================================================
 * LOAD OWNED META
 * ============================================================
 */


function userIarOwnedMeta(
    string $job,
    string $principalId
): array {

    if (
        !userIarValidJob(
            $job
        )
    ) {

        throw new Exception(
            'Invalid IAR backup reference.'
        );
    }


    $meta =
        json_decode(
            (string)@file_get_contents(
                userIarMetaFile(
                    $job
                )
            ),
            true
        );


    if (
        !is_array(
            $meta
        )
    ) {

        throw new Exception(
            'IAR backup record was not found.'
        );
    }


    if (
        !hash_equals(
            (string)(
                $meta['principalId'] ??
                ''
            ),
            $principalId
        )
    ) {

        throw new Exception(
            'Permission denied.'
        );
    }


    return $meta;
}


/*
 * ============================================================
 * ROTATION
 * ============================================================
 */


function userIarFinalizeRotation(
    array &$meta
): string {

    $replaceJob =
        trim(
            (string)(
                $meta['replaceJob'] ??
                ''
            )
        );


    if ($replaceJob === '') {

        $meta['rotationComplete'] =
            true;

        return '';
    }


    if (
        !empty(
            $meta['rotationComplete']
        )
    ) {

        return '';
    }


    if (
        !userIarValidJob(
            $replaceJob
        )
    ) {

        $meta['rotationWarning'] =
            'Old backup reference was invalid. New backup remains preserved.';

        return
            $meta['rotationWarning'];
    }


    $oldMetaFile =
        userIarMetaFile(
            $replaceJob
        );


    $oldMeta =
        json_decode(
            (string)@file_get_contents(
                $oldMetaFile
            ),
            true
        );


    if (
        !is_array(
            $oldMeta
        )
    ) {

        $meta['rotationComplete'] =
            true;

        return '';
    }


    if (
        !hash_equals(
            (string)(
                $oldMeta['principalId'] ??
                ''
            ),
            (string)(
                $meta['principalId'] ??
                ''
            )
        )
    ) {

        $meta['rotationWarning'] =
            'Old backup ownership did not match. It was not deleted.';

        return
            $meta['rotationWarning'];
    }


    $oldPath =
        (string)(
            $oldMeta['path'] ??
            ''
        );


    $oldReal =
        userIarInsideRoot(
            $oldPath
        );


    if (
        $oldReal !== false
        &&
        is_file(
            $oldReal
        )
    ) {

        if (
            !@unlink(
                $oldReal
            )
        ) {

            $meta['rotationWarning'] =
                'New IAR is verified, but the oldest IAR could not be removed.';

            return
                $meta['rotationWarning'];
        }
    }


    @unlink(
        $oldMetaFile
    );


    @unlink(
        userIarLogFile(
            $replaceJob
        )
    );


    @unlink(
        userIarWorkerFile(
            $replaceJob
        )
    );


    $meta['rotationComplete'] =
        true;


    $meta['replacedJob'] =
        $replaceJob;


    return '';
}


/*
 * ============================================================
 * JOB STATUS / VERIFICATION
 * ============================================================
 */


function userIarStatus(
    array $meta
): array {

    $job =
        (string)(
            $meta['jobId'] ??
            ''
        );


    $metaFile =
        userIarMetaFile(
            $job
        );


    $logFile =
        userIarLogFile(
            $job
        );


    $logText =
        is_file(
            $logFile
        )
        ?
        (string)@file_get_contents(
            $logFile
        )
        :
        '';


    $error =
        '';


    if (
        preg_match(
            '/^ERROR\s+.*?\s(.+)$/mi',
            $logText,
            $errorMatch
        )
    ) {

        $error =
            trim(
                (string)$errorMatch[1]
            );
    }


    $connectionClosed =
        stripos(
            $logText,
            'CONNECTION_CLOSED '
        ) !== false;


    $workerFinished =
        (
            strpos(
                $logText,
                "\nEND "
            ) !== false
            ||
            strpos(
                $logText,
                'END '
            ) === 0
        );


    $path =
        (string)(
            $meta['path'] ??
            ''
        );


    clearstatcache(
        true,
        $path
    );


    $real =
        userIarInsideRoot(
            $path
        );


    $exists =
        (
            $real !== false
            &&
            is_file(
                $real
            )
        );


    $size =
        $exists
        ?
        (int)filesize(
            $real
        )
        :
        0;


    $mtime =
        $exists
        ?
        (int)filemtime(
            $real
        )
        :
        0;


    $gzipValid =
        (
            $exists
            &&
            $size > 0
            &&
            userIarGzipHeader(
                $real
            )
        );


    $now =
        time();


    $previousSize =
        (int)(
            $meta['lastObservedSize'] ??
            -1
        );


    $previousAt =
        (int)(
            $meta['lastObservedAt'] ??
            0
        );


    $stable =
        false;


    if ($size > 0) {

        if (
            $previousSize ===
            $size
            &&
            $previousAt > 0
            &&
            (
                $now -
                $previousAt
            ) >= 15
            &&
            $mtime > 0
            &&
            (
                $now -
                $mtime
            ) >= 10
        ) {

            $stable =
                true;
        }


        if (
            $previousSize !==
            $size
        ) {

            $meta['lastObservedSize'] =
                $size;

            $meta['lastObservedAt'] =
                $now;
        }
        elseif (
            $previousAt <= 0
        ) {

            $meta['lastObservedAt'] =
                $now;
        }
    }


    $verified =
        (
            $error ===
            ''
            &&
            $size > 0
            &&
            $gzipValid
            &&
            (
                $workerFinished
                ||
                (
                    $connectionClosed
                    &&
                    $stable
                )
            )
        );


    $createdTime =
        strtotime(
            (string)(
                $meta['created'] ??
                ''
            )
        );


    $timedOut =
        (
            !$verified
            &&
            $createdTime !==
            false
            &&
            (
                $now -
                $createdTime
            ) > 16200
        );


    $failed =
        (
            $error !==
            ''
            ||
            $timedOut
        );


    $rotationWarning =
        '';


    if ($verified) {

        $meta['status'] =
            'verified';


        if (
            empty(
                $meta['verifiedAt']
            )
        ) {

            $meta['verifiedAt'] =
                date(
                    'c'
                );
        }


        $meta['verifiedSize'] =
            $size;


        /*
         * CRITICAL SAFETY RULE:
         *
         * Only now, AFTER the new archive is verified,
         * may the previous oldest retained backup be removed.
         */

        $rotationWarning =
            userIarFinalizeRotation(
                $meta
            );
    }
    elseif ($failed) {

        $meta['status'] =
            'failed';
    }
    else {

        $meta['status'] =
            'processing';
    }


    userIarWriteMeta(
        $metaFile,
        $meta
    );


    $message =
        'IAR backup is processing...';


    if (!$exists) {

        $message =
            'Waiting for OpenSim to create the IAR file...';
    }
    elseif (
        $size > 0
        &&
        !$workerFinished
        &&
        !$connectionClosed
    ) {

        $message =
            'OpenSim is writing your inventory archive...';
    }
    elseif (
        $connectionClosed
        &&
        !$verified
    ) {

        $message =
            'RemoteAdmin connection closed. Verifying that the IAR has finished writing...';
    }


    if ($verified) {

        $message =
            'IAR BACKUP VERIFIED SUCCESSFULLY.';
    }


    if ($rotationWarning !== '') {

        $message =
            'IAR BACKUP VERIFIED. ' .
            $rotationWarning;
    }


    if ($failed) {

        $message =
            $error !==
            ''
            ?
            $error
            :
            'The IAR backup timed out before verification completed.';
    }


    return [
        'ok' =>
            true,

        'jobId' =>
            $job,

        'name' =>
            (string)(
                $meta['name'] ??
                ''
            ),

        'size' =>
            $size,

        'created' =>
            (string)(
                $meta['created'] ??
                ''
            ),

        'verified' =>
            $verified,

        'failed' =>
            $failed,

        'processing' =>
            (
                !$verified
                &&
                !$failed
            ),

        'gzipValid' =>
            $gzipValid,

        'workerFinished' =>
            $workerFinished,

        'connectionClosed' =>
            $connectionClosed,

        'rotationWarning' =>
            $rotationWarning,

        'message' =>
            $message
    ];
}


/*
 * ============================================================
 * DOWNLOAD
 * ============================================================
 */


if (
    isset(
        $_GET['download']
    )
) {

    /*
 * Signed-in users may download only their own verified IAR.
 *
 * Security remains enforced by:
 * - signed-in PrincipalID ownership
 * - verified archive state
 * - User-IAR root containment
 * - existing file validation
 * - gzip/IAR validation
 */
try {

        $job =
            trim(
                (string)
                $_GET['download']
            );


        $meta =
            userIarOwnedMeta(
                $job,
                $principalId
            );


        if (
            (string)(
                $meta['status'] ??
                ''
            )
            !==
            'verified'
        ) {

            http_response_code(409);

            exit(
                'IAR backup has not been verified.'
            );
        }


        $real =
            userIarInsideRoot(
                (string)(
                    $meta['path'] ??
                    ''
                )
            );


        if (
            $real === false
            ||
            !is_file(
                $real
            )
            ||
            filesize(
                $real
            ) <= 0
        ) {

            http_response_code(404);

            exit(
                'IAR backup file is unavailable.'
            );
        }


        if (
            !userIarGzipHeader(
                $real
            )
        ) {

            http_response_code(409);

            exit(
                'IAR backup validation failed.'
            );
        }


        $fh =
            @fopen(
                $real,
                'rb'
            );


        if (!$fh) {

            http_response_code(500);

            exit(
                'IAR backup could not be opened.'
            );
        }


        $size =
            filesize(
                $real
            );


        header(
            'Content-Type: application/octet-stream'
        );


        header(
            'Content-Disposition: attachment; filename="' .
            str_replace(
                '"',
                '',
                basename(
                    $real
                )
            ) .
            '"'
        );


        header(
            'Content-Length: ' .
            $size
        );


        header(
            'X-Content-Type-Options: nosniff'
        );


        header(
            'Cache-Control: private, no-store, no-cache, must-revalidate'
        );


        while (
            !feof(
                $fh
            )
        ) {

            echo fread(
                $fh,
                1024 * 1024
            );

            flush();
        }


        fclose(
            $fh
        );


        exit;

    }
    catch (Throwable $e) {

        http_response_code(500);

        exit(
            $e->getMessage()
        );
    }
}


/*
 * ============================================================
 * API
 * ============================================================
 */


$action =
    strtolower(
        trim(
            (string)(
                $_GET['api'] ??
                ''
            )
        )
    );


if ($action !== '') {

    /*
     * ========================================================
     * USERLEVEL 50 PRIVILEGED IAR OPERATIONS
     * ========================================================
     *
     * LIST / STATUS / RESTORE-STATUS remain available to a
     * normal signed-in user because those operations are
     * already restricted to their own PrincipalID.
     *
     * These operations require UserLevel 50+:
     *
     * - start
     * - restore-start
     * - upload
     * - delete
     *
     * UserLevel does NOT replace ownership checks.
     */

    $privilegedUserActions = [
        'restore-start',
        'upload',
    ];

    if (
        in_array(
            $action,
            $privilegedUserActions,
            true
        )
        &&
        !ag_can_use_privileged_user_tools(
            $session
        )
    ) {

        userIarJson(
            [
                'ok' =>
                    false,

                'error' =>
                    'Permission denied. UserLevel 50 or higher is required for this IAR operation.'
            ],
            403
        );
    }

    try {


        /*
         * --------------------------------------------------------
         * LIST BACKUPS
         * --------------------------------------------------------
         */


        if ($action === 'list') {

            $all =
                userIarOwnedMetas(
                    $principalId
                );


            /*
             * Update any unfinished job before returning list.
             */

            $active =
                null;


            foreach(
                $all
                as
                $candidate
            ) {

                $status =
                    (string)(
                        $candidate['status'] ??
                        ''
                    );


                if (
                    $status ===
                    'starting'
                    ||
                    $status ===
                    'processing'
                ) {

                    $candidateStatus =
                        userIarStatus(
                            $candidate
                        );


                    if (
                        $candidateStatus['processing']
                    ) {

                        $active =
                            $candidateStatus;
                    }

                    break;
                }
            }


            $backups =
                userIarVerifiedBackups(
                    $principalId
                );


            $oldest =
                null;


            if (
                count(
                    $backups
                ) >= 2
            ) {

                $oldest =
                    $backups[
                        count(
                            $backups
                        ) - 1
                    ];
            }


            userIarJson([
                'ok' =>
                    true,

                'maximum' =>
                    2,

                'count' =>
                    count(
                        $backups
                    ),

                'backups' =>
                    array_slice(
                        $backups,
                        0,
                        2
                    ),

                'oldest' =>
                    $oldest,

                'activeJob' =>
                    $active
            ]);
        }


        /*
         * --------------------------------------------------------
         * STATUS
         * --------------------------------------------------------
         */


        if ($action === 'status') {

            $job =
                trim(
                    (string)(
                        $_GET['job'] ??
                        ''
                    )
                );


            $meta =
                userIarOwnedMeta(
                    $job,
                    $principalId
                );


            userIarJson(
                userIarStatus(
                    $meta
                )
            );
        }


        /*
         * --------------------------------------------------------
         * START NEW IAR
         * --------------------------------------------------------
         */


        if ($action === 'start') {

            if (
                $_SERVER['REQUEST_METHOD']
                !==
                'POST'
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'POST request required.'
                    ],
                    405
                );
            }


            $postedCsrf =
                (string)(
                    $_SERVER['HTTP_X_CSRF_TOKEN'] ??
                    ''
                );


            if (
                $postedCsrf ===
                ''
                ||
                !hash_equals(
                    $csrfToken,
                    $postedCsrf
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'Security token expired. Reload the IAR Backups page.'
                    ],
                    403
                );
            }


            $raw =
                file_get_contents(
                    'php://input'
                );


            $post =
                json_decode(
                    (string)$raw,
                    true
                );


            if (
                !is_array(
                    $post
                )
            ) {

                $post =
                    [];
            }


            $confirmReplace =
                !empty(
                    $post['confirmReplace']
                );


            /*
             * AUSTRALIA USER IAR RESTORE LOCK GUARD - START V1
             */
            $activeRestore =
                dgRestoreActiveInfo();

            if ($activeRestore !== null) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'An inventory or region restore is currently running. Wait for the restore to finish before creating another IAR backup.'
                    ],
                    409
                );
            }

            /*
             * Ensure there is not already an active backup.
             */

            foreach(
                userIarOwnedMetas(
                    $principalId
                )
                as
                $existing
            ) {

                $existingStatus =
                    (string)(
                        $existing['status'] ??
                        ''
                    );


                if (
                    $existingStatus ===
                    'starting'
                    ||
                    $existingStatus ===
                    'processing'
                ) {

                    $checked =
                        userIarStatus(
                            $existing
                        );


                    if (
                        $checked['processing']
                    ) {

                        userIarJson(
                            [
                                'ok' =>
                                    false,

                                'error' =>
                                    'An IAR backup is already running.',

                                'jobId' =>
                                    $checked['jobId']
                            ],
                            409
                        );
                    }
                }
            }


            $backups =
                userIarVerifiedBackups(
                    $principalId
                );


            $replaceJob =
                '';


            $replaceName =
                '';


            $replaceCreated =
                '';


            if (
                count(
                    $backups
                ) >= 2
            ) {

                $oldest =
                    $backups[
                        count(
                            $backups
                        ) - 1
                    ];


                if (!$confirmReplace) {

                    userIarJson(
                        [
                            'ok' =>
                                false,

                            'needsConfirmation' =>
                                true,

                            'error' =>
                                'Both IAR backup slots are full.',

                            'oldest' =>
                                $oldest
                        ],
                        409
                    );
                }


                $replaceJob =
                    (string)
                    $oldest['jobId'];


                $replaceName =
                    (string)
                    $oldest['name'];


                $replaceCreated =
                    (string)
                    $oldest['created'];
            }


            $account =
                userIarAccount(
                    $principalId
                );


            $remote =
                userIarRemoteAdmin();


            $root =
                userIarRoot();


            if (
                !is_dir(
                    $root
                )
                &&
                !@mkdir(
                    $root,
                    0775,
                    true
                )
            ) {

                throw new Exception(
                    'Could not create User IAR storage.'
                );
            }


            $safeAvatar =
                preg_replace(
                    '/[^A-Za-z0-9_-]+/',
                    '_',
                    $account['avatar']
                );


            $safeAvatar =
                trim(
                    $safeAvatar,
                    '_'
                );


            if ($safeAvatar === '') {

                $safeAvatar =
                    'Avatar';
            }


            $ownerFolder =
                $root .
                DIRECTORY_SEPARATOR .
                $safeAvatar .
                '_' .
                substr(
                    hash(
                        'sha256',
                        $principalId
                    ),
                    0,
                    12
                );


            if (
                !is_dir(
                    $ownerFolder
                )
                &&
                !@mkdir(
                    $ownerFolder,
                    0775,
                    true
                )
            ) {

                throw new Exception(
                    'Could not create personal IAR backup folder.'
                );
            }


            $job =
                date(
                    'Ymd_His'
                ) .
                '_' .
                bin2hex(
                    random_bytes(4)
                );


            $fileName =
                $safeAvatar .
                '_IAR_' .
                date(
                    'Y-m-d_H_i_s'
                ) .
                '.iar';


            $fullPath =
                $ownerFolder .
                DIRECTORY_SEPARATOR .
                $fileName;


            $consolePath =
                str_replace(
                    '\\',
                    '/',
                    $fullPath
                );


            /*
             * Uses the same proven Grid full-IAR
             * command format already used by the grid.
             */

            $command =
                'save iar --home ' . ag_dg_hostname() . ' ' .
                $account['first'] .
                ' ' .
                $account['last'] .
                ' / "' .
                $consolePath .
                '"';


            $psFile =
                userIarWorkerFile(
                    $job
                );


            $logFile =
                userIarLogFile(
                    $job
                );


            $metaFile =
                userIarMetaFile(
                    $job
                );


            $worker =
<<<'POWERSHELL'
param(
    [string]$RemotePass,
    [int]$RemotePort,
    [string]$ConsoleCommand,
    [string]$LogFile
)

$ErrorActionPreference = 'Stop'
$GridDirectory = [IO.DirectoryInfo]$PSScriptRoot
while($GridDirectory -and -not (Test-Path -LiteralPath (Join-Path $GridDirectory.FullName 'Settings.ini'))){$GridDirectory=$GridDirectory.Parent}
if(-not $GridDirectory){throw 'Grid settings were not found.'}
. (Join-Path $GridDirectory.FullName 'Apache/htdocs/Other/windows/WebsiteRuntime.ps1')
$RemoteHost = (Get-DgWebsiteRuntime -StartDirectory $PSScriptRoot).host

function XmlEscape([string]$s) {
    return [System.Security.SecurityElement]::Escape($s)
}

try {

    $xml =
        '<?xml version="1.0"?>' +
        '<methodCall><methodName>admin_console_command</methodName><params><param><value><struct>' +
        '<member><name>password</name><value><string>' +
        (XmlEscape $RemotePass) +
        '</string></value></member>' +
        '<member><name>command</name><value><string>' +
        (XmlEscape $ConsoleCommand) +
        '</string></value></member>' +
        '</struct></value></param></params></methodCall>'

    "START $(Get-Date -Format o)" |
        Set-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

    "COMMAND $ConsoleCommand" |
        Add-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

    $response =
        Invoke-WebRequest `
            -UseBasicParsing `
            -Uri ("http://{1}:{0}/" -f $RemotePort, $RemoteHost) `
            -Method POST `
            -ContentType "text/xml" `
            -Body $xml `
            -TimeoutSec 14400

    "HTTP $($response.StatusCode)" |
        Add-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

    $response.Content |
        Add-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

    "END $(Get-Date -Format o)" |
        Add-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

}
catch {

    $message =
        $_.Exception.Message

    if(
        $message -match
        'connection.*closed|closed.*unexpected|underlying connection'
    ){

        "CONNECTION_CLOSED $(Get-Date -Format o) $message" |
            Add-Content `
                -LiteralPath $LogFile `
                -Encoding UTF8
    }
    else{

        "ERROR $(Get-Date -Format o) $message" |
            Add-Content `
                -LiteralPath $LogFile `
                -Encoding UTF8
    }
}
finally {

    Remove-Variable RemotePass `
        -ErrorAction SilentlyContinue
}
POWERSHELL;


            if (
                @file_put_contents(
                    $psFile,
                    $worker
                ) ===
                false
            ) {

                throw new Exception(
                    'Could not create the IAR background worker.'
                );
            }


            $meta = [
                'type' =>
                    'user-iar',

                'jobId' =>
                    $job,

                'principalId' =>
                    $principalId,

                'avatar' =>
                    $account['avatar'],

                'name' =>
                    $fileName,

                'path' =>
                    $fullPath,

                'created' =>
                    date(
                        'c'
                    ),

                'status' =>
                    'starting',

                'lastObservedSize' =>
                    -1,

                'lastObservedAt' =>
                    0,

                'verifiedAt' =>
                    '',

                'verifiedSize' =>
                    0,

                'replaceJob' =>
                    $replaceJob,

                'replaceName' =>
                    $replaceName,

                'replaceCreated' =>
                    $replaceCreated,

                'rotationComplete' =>
                    false
            ];


            userIarWriteMeta(
                $metaFile,
                $meta
            );


            $args =
                '-NoProfile -NonInteractive -ExecutionPolicy Bypass -File ' .
                escapeshellarg(
                    $psFile
                ) .
                ' -RemotePass ' .
                escapeshellarg(
                    $remote['password']
                ) .
                ' -RemotePort ' .
                (int)$remote['port'] .
                ' -ConsoleCommand ' .
                escapeshellarg(
                    $command
                ) .
                ' -LogFile ' .
                escapeshellarg(
                    $logFile
                );


            $cmdLine =
                'start "" /B powershell.exe ' .
                $args .
                ' >NUL 2>&1';


            $pipe =
                @popen(
                    $cmdLine,
                    'r'
                );


            if ($pipe === false) {

                $meta['status'] =
                    'failed';


                userIarWriteMeta(
                    $metaFile,
                    $meta
                );


                throw new Exception(
                    'Could not launch the IAR backup worker.'
                );
            }


            @pclose(
                $pipe
            );


            usleep(
                400000
            );


            userIarJson([
                'ok' =>
                    true,

                'jobId' =>
                    $job,

                'name' =>
                    $fileName,

                'processing' =>
                    true,

                'verified' =>
                    false,

                'failed' =>
                    false,

                'message' =>
                    'IAR backup started. OpenSim is preparing your full inventory archive.'
            ]);
        }



        /*
         * ========================================================
         * AUSTRALIA USER IAR RESTORE V1
         * ========================================================
         *
         * Restores ONLY:
         *
         * - a VERIFIED User-IAR
         * - owned by the signed-in PrincipalID
         * - from inside the User-IAR storage root
         *
         * The destination avatar is derived server-side from the
         * signed-in account. The browser cannot supply another
         * avatar name.
         * ========================================================
         */


        if ($action === 'restore-start') {

            if (
                $_SERVER['REQUEST_METHOD']
                !==
                'POST'
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'POST request required.'
                    ],
                    405
                );
            }


            $postedCsrf =
                (string)(
                    $_SERVER['HTTP_X_CSRF_TOKEN'] ??
                    ''
                );


            if (
                $postedCsrf === ''
                ||
                !hash_equals(
                    $csrfToken,
                    $postedCsrf
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'Security token expired. Reload the IAR Backups page.'
                    ],
                    403
                );
            }


            /*
             * ====================================================
             * AUSTRALIA USER IAR MERGE V1
             * ====================================================
             *
             * Normal restore:
             *   load iar ...
             *
             * Merge restore:
             *   load iar --merge ...
             *
             * The browser may choose ONLY the restore mode.
             * Avatar and IAR ownership remain server controlled.
             * ====================================================
             */

            $restoreRaw =
                file_get_contents(
                    'php://input'
                );


            $restorePost =
                json_decode(
                    (string)$restoreRaw,
                    true
                );


            if (
                !is_array(
                    $restorePost
                )
            ) {

                $restorePost =
                    [];
            }


            $restoreMerge =
                !empty(
                    $restorePost['merge']
                );

            $sourceJob =
                trim(
                    (string)(
                        $_GET['job'] ??
                        ''
                    )
                );


            $sourceMeta =
                userIarOwnedMeta(
                    $sourceJob,
                    $principalId
                );


            if (
                (string)(
                    $sourceMeta['status'] ??
                    ''
                )
                !==
                'verified'
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'Only a completed VERIFIED IAR backup can be restored.'
                    ],
                    409
                );
            }


            $sourcePath =
                (string)(
                    $sourceMeta['path'] ??
                    ''
                );


            $sourceReal =
                userIarInsideRoot(
                    $sourcePath
                );


            if (
                $sourceReal === false
                ||
                !is_file(
                    $sourceReal
                )
                ||
                filesize(
                    $sourceReal
                ) <= 0
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'The selected IAR backup file is unavailable.'
                    ],
                    404
                );
            }


            if (
                !userIarGzipHeader(
                    $sourceReal
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'The selected IAR failed archive validation.'
                    ],
                    409
                );
            }


            /*
             * Do not restore while this avatar is currently
             * creating another User-IAR.
             */
            foreach(
                userIarOwnedMetas(
                    $principalId
                )
                as
                $candidate
            ) {

                $candidateStatus =
                    (string)(
                        $candidate['status'] ??
                        ''
                    );


                if (
                    $candidateStatus ===
                    'starting'
                    ||
                    $candidateStatus ===
                    'processing'
                ) {

                    $checked =
                        userIarStatus(
                            $candidate
                        );


                    if (
                        !empty(
                            $checked['processing']
                        )
                    ) {

                        userIarJson(
                            [
                                'ok' =>
                                    false,

                                'error' =>
                                    'Wait for the current IAR backup to finish before starting a restore.'
                            ],
                            409
                        );
                    }
                }
            }


            $account =
                userIarAccount(
                    $principalId
                );


            $remote =
                userIarRemoteAdmin();


            $restoreJob =
                date(
                    'Ymd_His'
                ) .
                '_' .
                bin2hex(
                    random_bytes(4)
                );


            $restoreName =
                (string)(
                    $sourceMeta['name'] ??
                    basename(
                        $sourceReal
                    )
                );


            list(
                $restoreLockOk,
                $restoreLockDesc
            ) =
                dgRestoreAcquireLock(
                    $restoreJob,
                    'iar',
                    $account['avatar'],
                    $restoreName,
                    $account['avatar']
                );


            if (!$restoreLockOk) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'Another restore is already running' .
                            (
                                $restoreLockDesc !== ''
                                ?
                                ': ' .
                                $restoreLockDesc
                                :
                                '.'
                            )
                    ],
                    409
                );
            }


            $restoreJobsRoot =
                userIarJobsRoot();


            $restoreMetaFile =
                $restoreJobsRoot .
                DIRECTORY_SEPARATOR .
                'user_iar_restore_meta_' .
                $restoreJob .
                '.json';


            $restoreLogFile =
                $restoreJobsRoot .
                DIRECTORY_SEPARATOR .
                'iar_restore_' .
                $restoreJob .
                '.log';


            $restoreWorkerFile =
                $restoreJobsRoot .
                DIRECTORY_SEPARATOR .
                'user_iar_restore_' .
                $restoreJob .
                '.ps1';


            try {

                $consolePath =
                    str_replace(
                        '\\',
                        '/',
                        $sourceReal
                    );


                $safeFirst =
                    str_replace(
                        '"',
                        '',
                        $account['first']
                    );


                $safeLast =
                    str_replace(
                        '"',
                        '',
                        $account['last']
                    );


                /*
                 * =================================================
                 * RESTORE COMMAND
                 * =================================================
                 *
                 * NORMAL:
                 * load iar <first> <last> "/" <IAR>
                 *
                 * MERGE:
                 * load iar --merge <first> <last> "/" <IAR>
                 * =================================================
                 */

                $restoreCommand =
                    'load iar ' .
                    (
                        $restoreMerge
                        ?
                        '--merge '
                        :
                        ''
                    ) .
                    '"' .
                    $safeFirst .
                    '" "' .
                    $safeLast .
                    '" "/" "' .
                    str_replace(
                        '"',
                        '',
                        $consolePath
                    ) .
                    '"';

                $restoreMeta = [

                    'type' =>
                        'user-iar-restore',

                    'jobId' =>
                        $restoreJob,

                    'sourceJob' =>
                        $sourceJob,

                    'principalId' =>
                        $principalId,

                    'avatar' =>
                        $account['avatar'],

                    'name' =>
                        $restoreName,

                    'path' =>
                        $sourceReal,

                    'created' =>
                        date(
                            'c'
                        ),

                    'status' =>
                        'starting'
                ];


                userIarWriteMeta(
                    $restoreMetaFile,
                    $restoreMeta
                );


                $restoreWorker =
<<<'POWERSHELL'
param(
    [string]$RemotePass,
    [int]$RemotePort,
    [string]$ConsoleCommand,
    [string]$LogFile
)

$ErrorActionPreference = 'Stop'
$GridDirectory = [IO.DirectoryInfo]$PSScriptRoot
while($GridDirectory -and -not (Test-Path -LiteralPath (Join-Path $GridDirectory.FullName 'Settings.ini'))){$GridDirectory=$GridDirectory.Parent}
if(-not $GridDirectory){throw 'Grid settings were not found.'}
. (Join-Path $GridDirectory.FullName 'Apache/htdocs/Other/windows/WebsiteRuntime.ps1')
$RemoteHost = (Get-DgWebsiteRuntime -StartDirectory $PSScriptRoot).host

function XmlEscape([string]$s) {
    return [System.Security.SecurityElement]::Escape($s)
}

try {

    $xml =
        '<?xml version="1.0"?>' +
        '<methodCall><methodName>admin_console_command</methodName><params><param><value><struct>' +
        '<member><name>password</name><value><string>' +
        (XmlEscape $RemotePass) +
        '</string></value></member>' +
        '<member><name>command</name><value><string>' +
        (XmlEscape $ConsoleCommand) +
        '</string></value></member>' +
        '</struct></value></param></params></methodCall>'

    "START $(Get-Date -Format o)" |
        Set-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

    "COMMAND $ConsoleCommand" |
        Add-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

    $response =
        Invoke-WebRequest `
            -UseBasicParsing `
            -Uri ("http://{1}:{0}/" -f $RemotePort, $RemoteHost) `
            -Method POST `
            -ContentType "text/xml" `
            -Body $xml `
            -TimeoutSec 14400

    "HTTP $($response.StatusCode)" |
        Add-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

    $response.Content |
        Add-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

    "END $(Get-Date -Format o)" |
        Add-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8
}
catch {

    $message =
        $_.Exception.Message

    "ERROR $(Get-Date -Format o) $message" |
        Add-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8
}
finally {

    Remove-Variable RemotePass `
        -ErrorAction SilentlyContinue
}
POWERSHELL;


                if (
                    @file_put_contents(
                        $restoreWorkerFile,
                        $restoreWorker
                    )
                    ===
                    false
                ) {

                    throw new Exception(
                        'Could not create the IAR restore worker.'
                    );
                }


                $restoreArgs =
                    '-NoProfile -NonInteractive -ExecutionPolicy Bypass -File ' .
                    escapeshellarg(
                        $restoreWorkerFile
                    ) .
                    ' -RemotePass ' .
                    escapeshellarg(
                        $remote['password']
                    ) .
                    ' -RemotePort ' .
                    (int)$remote['port'] .
                    ' -ConsoleCommand ' .
                    escapeshellarg(
                        $restoreCommand
                    ) .
                    ' -LogFile ' .
                    escapeshellarg(
                        $restoreLogFile
                    );


                $restoreCmdLine =
                    'start "" /B powershell.exe ' .
                    $restoreArgs .
                    ' >NUL 2>&1';


                $restorePipe =
                    @popen(
                        $restoreCmdLine,
                        'r'
                    );


                if ($restorePipe === false) {

                    throw new Exception(
                        'Could not launch the IAR restore worker.'
                    );
                }


                @pclose(
                    $restorePipe
                );


                $restoreMeta['status'] =
                    'processing';


                userIarWriteMeta(
                    $restoreMetaFile,
                    $restoreMeta
                );


                usleep(
                    300000
                );


                userIarJson(
                    [
                        'ok' =>
                            true,

                        'restoreJobId' =>
                            $restoreJob,

                        'sourceJob' =>
                            $sourceJob,

                        'name' =>
                            $restoreName,

                        'avatar' =>
                            $account['avatar'],

                        'processing' =>
                            true,

                        'message' =>
                            'IAR restore started.'
                    ]
                );
            }
            catch (Throwable $restoreError) {

                dgRestoreReleaseLock(
                    $restoreJob
                );


                @unlink(
                    $restoreWorkerFile
                );


                throw
                    $restoreError;
            }
        }


        /*
         * --------------------------------------------------------
         * RESTORE STATUS
         * --------------------------------------------------------
         */
        if ($action === 'restore-status') {

            $restoreJob =
                trim(
                    (string)(
                        $_GET['job'] ??
                        ''
                    )
                );


            if (
                !preg_match(
                    '/^\d{8}_\d{6}_[a-f0-9]{8}$/',
                    $restoreJob
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'Invalid IAR restore reference.'
                    ],
                    400
                );
            }


            $restoreMetaFile =
                userIarJobsRoot() .
                DIRECTORY_SEPARATOR .
                'user_iar_restore_meta_' .
                $restoreJob .
                '.json';


            $restoreLogFile =
                userIarJobsRoot() .
                DIRECTORY_SEPARATOR .
                'iar_restore_' .
                $restoreJob .
                '.log';


            $restoreWorkerFile =
                userIarJobsRoot() .
                DIRECTORY_SEPARATOR .
                'user_iar_restore_' .
                $restoreJob .
                '.ps1';


            $restoreMeta =
                json_decode(
                    (string)@file_get_contents(
                        $restoreMetaFile
                    ),
                    true
                );


            if (
                !is_array(
                    $restoreMeta
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'IAR restore job was not found.'
                    ],
                    404
                );
            }


            if (
                !hash_equals(
                    (string)(
                        $restoreMeta['principalId'] ??
                        ''
                    ),
                    $principalId
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'Permission denied.'
                    ],
                    403
                );
            }


            $restoreLog =
                is_file(
                    $restoreLogFile
                )
                ?
                (string)@file_get_contents(
                    $restoreLogFile
                )
                :
                '';


            $restoreError =
                '';


            if (
                preg_match(
                    '/(?:^|\R)ERROR\s+[^\r\n]*\s(.+?)(?:\R|$)/i',
                    $restoreLog,
                    $restoreErrorMatch
                )
            ) {

                $restoreError =
                    trim(
                        (string)$restoreErrorMatch[1]
                    );
            }


            $restoreHasEnd =
                (bool)preg_match(
                    '/(?:^|\R)END\s+/m',
                    $restoreLog
                );


            $restoreXmlSuccess =
                (bool)preg_match(
                    '/<name>\s*success\s*<\/name>\s*<value>\s*<boolean>\s*1\s*<\/boolean>/i',
                    $restoreLog
                );


            $restoreComplete =
                false;


            $restoreFailed =
                false;


            $restoreMessage =
                'IAR restore is processing...';


            if ($restoreError !== '') {

                $restoreFailed =
                    true;

                $restoreMessage =
                    'IAR restore failed: ' .
                    $restoreError;
            }
            elseif (
                $restoreHasEnd
                &&
                $restoreXmlSuccess
            ) {

                $restoreComplete =
                    true;

                $restoreMessage =
                    'IAR RESTORE COMPLETED SUCCESSFULLY.';
            }
            elseif ($restoreHasEnd) {

                $restoreFailed =
                    true;

                $restoreMessage =
                    'RemoteAdmin finished but did not confirm a successful IAR restore.';
            }
            else {

                $restoreCreated =
                    strtotime(
                        (string)(
                            $restoreMeta['created'] ??
                            ''
                        )
                    );


                if (
                    $restoreCreated !== false
                    &&
                    (
                        time() -
                        $restoreCreated
                    ) > 15000
                ) {

                    $restoreFailed =
                        true;

                    $restoreMessage =
                        'The IAR restore timed out before completion was confirmed.';
                }
            }


            if ($restoreComplete) {

                $restoreMeta['status'] =
                    'complete';

                $restoreMeta['finished'] =
                    date(
                        'c'
                    );


                userIarWriteMeta(
                    $restoreMetaFile,
                    $restoreMeta
                );


                dgRestoreReleaseLock(
                    $restoreJob
                );


                @unlink(
                    $restoreWorkerFile
                );
            }


            if ($restoreFailed) {

                $restoreMeta['status'] =
                    'failed';

                $restoreMeta['finished'] =
                    date(
                        'c'
                    );


                userIarWriteMeta(
                    $restoreMetaFile,
                    $restoreMeta
                );


                dgRestoreReleaseLock(
                    $restoreJob
                );


                @unlink(
                    $restoreWorkerFile
                );
            }


            userIarJson(
                [
                    'ok' =>
                        true,

                    'restoreJobId' =>
                        $restoreJob,

                    'processing' =>
                        !$restoreComplete
                        &&
                        !$restoreFailed,

                    'complete' =>
                        $restoreComplete,

                    'failed' =>
                        $restoreFailed,

                    'name' =>
                        (string)(
                            $restoreMeta['name'] ??
                            ''
                        ),

                    'avatar' =>
                        (string)(
                            $restoreMeta['avatar'] ??
                            ''
                        ),

                    'message' =>
                        $restoreMessage
                ]
            );
        }

        /*
         * ========================================================
         * AUSTRALIA USER IAR UPLOAD V1
         * ========================================================
         *
         * Uploaded archives:
         *
         * - must have .iar extension
         * - maximum 512 MB
         * - must pass gzip/IAR validation
         * - are locked to the signed-in PrincipalID
         * - are stored inside that avatar's User-IAR directory
         * - count toward the normal TWO IAR slots
         * - NEVER delete an existing slot before the uploaded IAR
         *   has arrived and passed validation
         * ========================================================
         */


        if ($action === 'upload') {

            if (
                $_SERVER['REQUEST_METHOD']
                !==
                'POST'
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'POST request required.'
                    ],
                    405
                );
            }


            /*
             * ----------------------------------------------------
             * CSRF
             * ----------------------------------------------------
             */

            $postedCsrf =
                (string)(
                    $_SERVER['HTTP_X_CSRF_TOKEN'] ??
                    ''
                );


            if (
                $postedCsrf === ''
                ||
                !hash_equals(
                    $csrfToken,
                    $postedCsrf
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'Security token expired. Reload the IAR Backups page.'
                    ],
                    403
                );
            }


            /*
             * ----------------------------------------------------
             * DO NOT UPLOAD WHILE A RESTORE IS RUNNING
             * ----------------------------------------------------
             */

            $activeRestore =
                dgRestoreActiveInfo();


            if ($activeRestore !== null) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'Wait for the current restore to finish before uploading an IAR.'
                    ],
                    409
                );
            }


            /*
             * ----------------------------------------------------
             * DO NOT UPLOAD WHILE THIS USER IS SAVING AN IAR
             * ----------------------------------------------------
             */

            foreach(
                userIarOwnedMetas(
                    $principalId
                )
                as
                $candidate
            ) {

                $candidateStatus =
                    (string)(
                        $candidate['status'] ??
                        ''
                    );


                if (
                    $candidateStatus ===
                    'starting'
                    ||
                    $candidateStatus ===
                    'processing'
                ) {

                    $checked =
                        userIarStatus(
                            $candidate
                        );


                    if (
                        !empty(
                            $checked['processing']
                        )
                    ) {

                        userIarJson(
                            [
                                'ok' =>
                                    false,

                                'error' =>
                                    'Wait for the current IAR backup to finish before uploading another archive.'
                            ],
                            409
                        );
                    }
                }
            }


            /*
             * ----------------------------------------------------
             * CHECK PHP UPLOAD
             * ----------------------------------------------------
             */

            if (
                !isset(
                    $_FILES['iarFile']
                )
                ||
                !is_array(
                    $_FILES['iarFile']
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'No IAR file was received.'
                    ],
                    400
                );
            }


            $upload =
                $_FILES['iarFile'];


            $uploadError =
                (int)(
                    $upload['error'] ??
                    UPLOAD_ERR_NO_FILE
                );


            if ($uploadError !== UPLOAD_ERR_OK) {

                $uploadErrorMessage =
                    'IAR upload failed.';


                if (
                    $uploadError ===
                    UPLOAD_ERR_INI_SIZE
                    ||
                    $uploadError ===
                    UPLOAD_ERR_FORM_SIZE
                ) {

                    $uploadErrorMessage =
                        'The selected IAR exceeds the 512 MB upload limit.';
                }
                elseif (
                    $uploadError ===
                    UPLOAD_ERR_PARTIAL
                ) {

                    $uploadErrorMessage =
                        'The IAR upload was interrupted before the complete file arrived.';
                }
                elseif (
                    $uploadError ===
                    UPLOAD_ERR_NO_FILE
                ) {

                    $uploadErrorMessage =
                        'No IAR file was selected.';
                }
                elseif (
                    $uploadError ===
                    UPLOAD_ERR_NO_TMP_DIR
                ) {

                    $uploadErrorMessage =
                        'The server upload temporary folder is unavailable.';
                }
                elseif (
                    $uploadError ===
                    UPLOAD_ERR_CANT_WRITE
                ) {

                    $uploadErrorMessage =
                        'The server could not write the uploaded IAR.';
                }
                elseif (
                    $uploadError ===
                    UPLOAD_ERR_EXTENSION
                ) {

                    $uploadErrorMessage =
                        'A PHP extension stopped the IAR upload.';
                }


                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            $uploadErrorMessage
                    ],
                    400
                );
            }


            /*
             * ----------------------------------------------------
             * FILE NAME
             * ----------------------------------------------------
             */

            $originalName =
                basename(
                    (string)(
                        $upload['name'] ??
                        ''
                    )
                );


            if (
                $originalName === ''
                ||
                !preg_match(
                    '/\.iar$/i',
                    $originalName
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'Only .iar inventory archive files can be uploaded.'
                    ],
                    400
                );
            }


            /*
             * ----------------------------------------------------
             * PHP TEMPORARY FILE
             * ----------------------------------------------------
             */

            $temporaryFile =
                (string)(
                    $upload['tmp_name'] ??
                    ''
                );


            if (
                $temporaryFile === ''
                ||
                !is_uploaded_file(
                    $temporaryFile
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'The uploaded IAR could not be verified by PHP.'
                    ],
                    400
                );
            }


            /*
             * ----------------------------------------------------
             * SIZE
             * ----------------------------------------------------
             */

            $uploadSize =
                (int)(
                    $upload['size'] ??
                    0
                );


            if (
                $uploadSize <= 0
                ||
                !is_file(
                    $temporaryFile
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'The uploaded IAR is empty.'
                    ],
                    400
                );
            }


            if (
                $uploadSize >
                536870912
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'The selected IAR exceeds the 512 MB upload limit.'
                    ],
                    413
                );
            }


            /*
             * ----------------------------------------------------
             * VALIDATE COMPRESSED IAR BEFORE MOVING
             * ----------------------------------------------------
             */

            if (
                !userIarGzipHeader(
                    $temporaryFile
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'The selected file does not appear to be a valid compressed IAR archive.'
                    ],
                    400
                );
            }


            /*
             * ----------------------------------------------------
             * TWO-SLOT SAFETY
             * ----------------------------------------------------
             */

            $confirmReplace =
                !empty(
                    $_POST['confirmReplace']
                );


            $backups =
                userIarVerifiedBackups(
                    $principalId
                );


            $replaceJob =
                '';

            $replaceName =
                '';

            $replaceCreated =
                '';


            if (
                count(
                    $backups
                ) >= 2
            ) {

                $oldest =
                    $backups[
                        count(
                            $backups
                        ) - 1
                    ];


                if (!$confirmReplace) {

                    userIarJson(
                        [
                            'ok' =>
                                false,

                            'needsConfirmation' =>
                                true,

                            'error' =>
                                'Both IAR backup slots are full.',

                            'oldest' =>
                                $oldest
                        ],
                        409
                    );
                }


                $replaceJob =
                    (string)
                    $oldest['jobId'];


                $replaceName =
                    (string)
                    $oldest['name'];


                $replaceCreated =
                    (string)
                    $oldest['created'];
            }


            /*
             * ----------------------------------------------------
             * SIGNED-IN ACCOUNT
             * ----------------------------------------------------
             */

            $account =
                userIarAccount(
                    $principalId
                );


            $root =
                userIarRoot();


            if (
                !is_dir(
                    $root
                )
                &&
                !@mkdir(
                    $root,
                    0775,
                    true
                )
            ) {

                throw new Exception(
                    'Could not create User IAR storage.'
                );
            }


            /*
             * ----------------------------------------------------
             * PERSONAL USER-IAR FOLDER
             * ----------------------------------------------------
             */

            $safeAvatar =
                preg_replace(
                    '/[^A-Za-z0-9_-]+/',
                    '_',
                    $account['avatar']
                );


            $safeAvatar =
                trim(
                    (string)$safeAvatar,
                    '_'
                );


            if ($safeAvatar === '') {

                $safeAvatar =
                    'Avatar';
            }


            $ownerFolder =
                $root .
                DIRECTORY_SEPARATOR .
                $safeAvatar .
                '_' .
                substr(
                    hash(
                        'sha256',
                        $principalId
                    ),
                    0,
                    12
                );


            if (
                !is_dir(
                    $ownerFolder
                )
                &&
                !@mkdir(
                    $ownerFolder,
                    0775,
                    true
                )
            ) {

                throw new Exception(
                    'Could not create personal IAR upload folder.'
                );
            }


            /*
             * ----------------------------------------------------
             * NEW JOB ID
             * ----------------------------------------------------
             */

            $job =
                date(
                    'Ymd_His'
                ) .
                '_' .
                bin2hex(
                    random_bytes(4)
                );


            /*
             * ----------------------------------------------------
             * SAFE ORIGINAL NAME
             * ----------------------------------------------------
             */

            $safeOriginal =
                preg_replace(
                    '/[^A-Za-z0-9._-]+/',
                    '_',
                    $originalName
                );


            $safeOriginal =
                trim(
                    (string)$safeOriginal,
                    '._-'
                );


            $safeOriginal =
                preg_replace(
                    '/\.iar$/i',
                    '',
                    $safeOriginal
                );


            if ($safeOriginal === '') {

                $safeOriginal =
                    'Uploaded';
            }


            /*
             * Keep Windows path lengths under control.
             */
            $safeOriginal =
                substr(
                    $safeOriginal,
                    0,
                    80
                );


            $fileName =
                $safeAvatar .
                '_UPLOADED_' .
                date(
                    'Y-m-d_H_i_s'
                ) .
                '_' .
                substr(
                    $job,
                    -8
                ) .
                '_' .
                $safeOriginal .
                '.iar';


            $fullPath =
                $ownerFolder .
                DIRECTORY_SEPARATOR .
                $fileName;


            if (
                file_exists(
                    $fullPath
                )
            ) {

                throw new Exception(
                    'An IAR with the generated upload name already exists.'
                );
            }


            $metaFile =
                userIarMetaFile(
                    $job
                );


            $moved =
                false;

            $metaSaved =
                false;


            try {

                /*
                 * ------------------------------------------------
                 * MOVE FROM PHP TEMP INTO SECURE USER-IAR STORAGE
                 * ------------------------------------------------
                 */

                if (
                    !@move_uploaded_file(
                        $temporaryFile,
                        $fullPath
                    )
                ) {

                    throw new Exception(
                        'Grid could not move the uploaded IAR into secure storage.'
                    );
                }


                $moved =
                    true;


                /*
                 * ------------------------------------------------
                 * VERIFY FINAL FILE EXISTS
                 * ------------------------------------------------
                 */

                if (
                    !is_file(
                        $fullPath
                    )
                    ||
                    filesize(
                        $fullPath
                    ) <= 0
                ) {

                    throw new Exception(
                        'The uploaded IAR was not saved correctly.'
                    );
                }


                /*
                 * ------------------------------------------------
                 * VALIDATE AGAIN AFTER FINAL MOVE
                 * ------------------------------------------------
                 */

                if (
                    !userIarGzipHeader(
                        $fullPath
                    )
                ) {

                    throw new Exception(
                        'The uploaded IAR failed final archive validation.'
                    );
                }


                $finalSize =
                    (int)filesize(
                        $fullPath
                    );


                /*
                 * ------------------------------------------------
                 * VERIFIED METADATA
                 * ------------------------------------------------
                 */

                $meta = [

                    'type' =>
                        'user-iar-upload',

                    'jobId' =>
                        $job,

                    'principalId' =>
                        $principalId,

                    'avatar' =>
                        $account['avatar'],

                    'name' =>
                        $fileName,

                    'originalName' =>
                        $originalName,

                    'path' =>
                        $fullPath,

                    'created' =>
                        date(
                            'c'
                        ),

                    'status' =>
                        'verified',

                    'lastObservedSize' =>
                        $finalSize,

                    'lastObservedAt' =>
                        time(),

                    'verifiedAt' =>
                        date(
                            'c'
                        ),

                    'verifiedSize' =>
                        $finalSize,

                    'replaceJob' =>
                        $replaceJob,

                    'replaceName' =>
                        $replaceName,

                    'replaceCreated' =>
                        $replaceCreated,

                    'rotationComplete' =>
                        false,

                    'uploaded' =>
                        true
                ];


                userIarWriteMeta(
                    $metaFile,
                    $meta
                );


                $metaSaved =
                    true;


                /*
                 * ------------------------------------------------
                 * TWO-BACKUP ROTATION
                 *
                 * IMPORTANT:
                 * The uploaded IAR already exists and has passed
                 * validation BEFORE the oldest backup can be
                 * removed.
                 * ------------------------------------------------
                 */

                $rotationWarning =
                    userIarFinalizeRotation(
                        $meta
                    );


                /*
                 * userIarFinalizeRotation modifies $meta by
                 * reference. Save its final rotation state.
                 */
                userIarWriteMeta(
                    $metaFile,
                    $meta
                );


                $message =
                    'IAR uploaded and verified successfully.';


                if ($rotationWarning !== '') {

                    $message .=
                        ' ' .
                        $rotationWarning;
                }


                userIarJson(
                    [
                        'ok' =>
                            true,

                        'jobId' =>
                            $job,

                        'name' =>
                            $fileName,

                        'originalName' =>
                            $originalName,

                        'size' =>
                            $finalSize,

                        'verified' =>
                            true,

                        'message' =>
                            $message
                    ]
                );
            }
            catch (Throwable $uploadException) {

                /*
                 * Remove a newly moved file if metadata was never
                 * successfully committed.
                 */

                if (
                    $moved
                    &&
                    !$metaSaved
                    &&
                    is_file(
                        $fullPath
                    )
                ) {

                    @unlink(
                        $fullPath
                    );
                }


                if (
                    !$metaSaved
                    &&
                    is_file(
                        $metaFile
                    )
                ) {

                    @unlink(
                        $metaFile
                    );
                }


                throw
                    $uploadException;
            }
        }

        /*
         * ========================================================
         * AUSTRALIA USER IAR DELETE V1
         * ========================================================
         */


        if ($action === 'delete') {

            if (
                $_SERVER['REQUEST_METHOD']
                !==
                'POST'
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'POST request required.'
                    ],
                    405
                );
            }


            $postedCsrf =
                (string)(
                    $_SERVER['HTTP_X_CSRF_TOKEN'] ??
                    ''
                );


            if (
                $postedCsrf === ''
                ||
                !hash_equals(
                    $csrfToken,
                    $postedCsrf
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'Security token expired. Reload the IAR Backups page.'
                    ],
                    403
                );
            }


            $raw =
                file_get_contents(
                    'php://input'
                );


            $post =
                json_decode(
                    (string)$raw,
                    true
                );


            if (
                !is_array(
                    $post
                )
            ) {

                $post =
                    [];
            }


            $job =
                trim(
                    (string)(
                        $post['jobId'] ??
                        ''
                    )
                );


            /*
             * AUSTRALIA USER IAR RESTORE LOCK GUARD - DELETE V1
             */
            $activeRestore =
                dgRestoreActiveInfo();

            if ($activeRestore !== null) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'Wait for the current restore to finish before deleting an IAR backup.'
                    ],
                    409
                );
            }

            /*
             * Do not allow deletion while another normal IAR
             * backup is still being created.
             */


            foreach(
                userIarOwnedMetas(
                    $principalId
                )
                as
                $candidate
            ) {

                $candidateJob =
                    (string)(
                        $candidate['jobId'] ??
                        ''
                    );


                $candidateStatus =
                    (string)(
                        $candidate['status'] ??
                        ''
                    );


                if (
                    $candidateJob ===
                    $job
                ) {
                    continue;
                }


                if (
                    $candidateStatus ===
                    'starting'
                    ||
                    $candidateStatus ===
                    'processing'
                ) {

                    $checked =
                        userIarStatus(
                            $candidate
                        );


                    if (
                        $checked['processing']
                    ) {

                        userIarJson(
                            [
                                'ok' =>
                                    false,

                                'error' =>
                                    'Wait for the current IAR backup to finish before deleting a saved backup.'
                            ],
                            409
                        );
                    }
                }
            }


            $meta =
                userIarOwnedMeta(
                    $job,
                    $principalId
                );


            if (
                (string)(
                    $meta['status'] ??
                    ''
                )
                !==
                'verified'
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'Only a completed verified IAR backup can be deleted.'
                    ],
                    409
                );
            }


            $path =
                (string)(
                    $meta['path'] ??
                    ''
                );


            $real =
                userIarInsideRoot(
                    $path
                );


            if (
                $real ===
                false
                ||
                !is_file(
                    $real
                )
            ) {

                userIarJson(
                    [
                        'ok' =>
                            false,

                        'error' =>
                            'The IAR backup file could not be found.'
                    ],
                    404
                );
            }


            $deletedName =
                (string)(
                    $meta['name'] ??
                    basename(
                        $real
                    )
                );


            /*
             * Delete the actual IAR first.
             *
             * If this fails, metadata is deliberately retained.
             */


            if (
                !@unlink(
                    $real
                )
            ) {

                throw new Exception(
                    'Grid could not delete the IAR file. Nothing else was removed.'
                );
            }


            /*
             * File deletion succeeded.
             * Remove its private job records.
             */


            @unlink(
                userIarMetaFile(
                    $job
                )
            );


            @unlink(
                userIarLogFile(
                    $job
                )
            );


            @unlink(
                userIarWorkerFile(
                    $job
                )
            );


            userIarJson([
                'ok' =>
                    true,

                'deleted' =>
                    true,

                'name' =>
                    $deletedName,

                'message' =>
                    'IAR backup deleted successfully.'
            ]);
        }

        userIarJson(
            [
                'ok' =>
                    false,

                'error' =>
                    'Unknown IAR request.'
            ],
            400
        );

    }
    catch (Throwable $e) {

        userIarJson(
            [
                'ok' =>
                    false,

                'error' =>
                    $e->getMessage()
            ],
            500
        );
    }
}


/*
 * ============================================================
 * HTML
 * ============================================================
 */


header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>
Grid - IAR Backups
</title>


<link
    rel="stylesheet"
    href="/Other/australia-3d-theme.css?v=31">


<style>

*{
    box-sizing:border-box;
}


html,
body{
    min-height:100%;
}


body{

    margin:0;

    background:

        linear-gradient(
            rgba(0,0,0,.48),
            rgba(0,0,0,.70)
        ),

        url(
            "/Other/assets/images/control-center-teal-bg.png"
        )

        center center /
        cover
        fixed
        no-repeat;

    color:#edf2f4;

    font-family:
        Arial,
        Helvetica,
        sans-serif;
}


.iar-shell{

    width:
        min(
            1250px,
            calc(100% - 34px)
        );

    margin:
        30px auto 70px;

    padding:
        25px;
}





.iar-kicker{

    color:#efb83c;

    font-size:10px;

    font-weight:900;

    letter-spacing:.20em;
}


.iar-header h1{

    margin:
        8px 0 6px;

    font-size:
        40px;
}


.iar-header p{

    margin:0;

    color:#b7c2c7;

    font-size:13px;
}


.iar-user{

    margin-top:7px;

    color:#98a6ac;

    font-size:11px;
}


.iar-user strong{
    color:#fff;
}


.header-actions{

    display:flex;

    gap:9px;
}


.header-actions a{

    min-width:120px;

    min-height:44px;

    display:flex;

    align-items:center;

    justify-content:center;

    text-decoration:none;
}


.iar-summary{

    display:grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0,1fr)
        );

    gap:
        12px;

    margin-bottom:
        17px;
}


.summary-card{

    padding:
        16px 18px;
}


.summary-label{

    color:#eeb23b;

    font-size:9px;

    font-weight:900;

    letter-spacing:.08em;
}


.summary-value{

    margin-top:5px;

    color:#fff;

    font-size:27px;

    font-weight:900;
}


.backup-control{

    padding:
        23px;

    margin-bottom:
        17px;

    text-align:center;
}


.backup-control h2{

    margin:
        0 0 8px;

    font-size:20px;
}


.backup-control p{

    max-width:
        850px;

    margin:
        0 auto 17px;

    color:#aeb9be;

    font-size:11px;

    line-height:1.6;
}


.save-button{

    min-width:
        260px;

    min-height:
        50px;

    cursor:pointer;
}


.job-panel{

    margin-top:
        18px;

    padding:
        17px;

    border:
        1px solid
        rgba(231,173,49,.34);

    border-radius:
        10px;

    background:
        rgba(0,0,0,.23);

    text-align:left;
}


.job-top{

    display:flex;

    align-items:center;

    justify-content:
        space-between;

    gap:12px;
}


.job-badge{

    display:inline-flex;

    padding:
        6px 10px;

    border:
        1px solid
        rgba(71,167,235,.44);

    border-radius:
        999px;

    background:
        rgba(71,167,235,.10);

    color:#91d1ff;

    font-size:9px;

    font-weight:900;
}


.job-message{

    margin-top:
        12px;

    color:#c1cacf;

    font-size:11px;

    line-height:1.6;
}


.job-file{

    margin-top:8px;

    color:#e5b54b;

    font-size:10px;

    overflow-wrap:anywhere;
}


.slots-title{

    margin:
        3px 0 12px;

    color:#edb33c;

    font-size:12px;

    font-weight:900;

    letter-spacing:.08em;
}


.slot-grid{

    display:grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap:
        15px;
}


.slot-card{

    min-height:
        270px;

    padding:
        22px;

    display:flex;

    flex-direction:column;
}


.slot-top{

    display:flex;

    justify-content:
        space-between;

    align-items:flex-start;

    gap:
        10px;
}


.slot-number{

    width:49px;

    height:49px;

    display:flex;

    align-items:center;

    justify-content:center;

    border:
        1px solid
        #a87e27;

    border-radius:
        9px;

    background:

        linear-gradient(
            145deg,
            #3d4447,
            #111618 56%,
            #050708
        );

    color:#ffc348;

    font-size:11px;

    font-weight:900;

    box-shadow:

        inset 0 2px 0
        rgba(255,255,255,.19),

        inset 0 -4px 6px
        rgba(0,0,0,.8),

        0 5px 8px
        rgba(0,0,0,.45);
}


.slot-status{

    padding:
        5px 9px;

    border:
        1px solid
        rgba(73,202,111,.38);

    border-radius:
        999px;

    background:
        rgba(73,202,111,.09);

    color:#91e8aa;

    font-size:8px;

    font-weight:900;
}


.slot-card h2{

    margin:
        17px 0 5px;

    color:#fff;

    font-size:17px;

    overflow-wrap:anywhere;
}


.slot-date{

    color:#e6ae3b;

    font-size:10px;

    font-weight:700;
}


.slot-size{

    margin-top:12px;

    color:#aab6bb;

    font-size:11px;
}


.slot-spacer{
    flex:1;
}


.slot-download{

    min-height:46px;

    display:flex;

    align-items:center;

    justify-content:center;

    margin-top:19px;

    text-decoration:none;
}


.empty-slot{

    justify-content:center;

    text-align:center;

    color:#849198;
}


.empty-slot strong{

    display:block;

    margin-bottom:7px;

    color:#afb9bd;

    font-size:15px;
}


.rotation-note{

    margin-top:
        17px;

    padding:
        15px 17px;

    border-left:
        4px solid
        #e4aa30;

    color:#b9c3c7;

    font-size:11px;

    line-height:1.65;
}


.future-tools{

    margin-top:
        17px;

    padding:
        20px;
}


.future-tools h2{

    margin:
        0 0 7px;

    font-size:17px;
}


.future-tools p{

    margin:
        0;

    color:#9fabb0;

    font-size:11px;

    line-height:1.6;
}


.future-badges{

    display:flex;

    gap:8px;

    margin-top:13px;

    flex-wrap:wrap;
}


.future-badge{

    padding:
        7px 10px;

    border:
        1px solid
        rgba(233,174,50,.25);

    border-radius:
        7px;

    background:
        rgba(233,174,50,.05);

    color:#c69e4d;

    font-size:9px;

    font-weight:900;
}


.status-bar{

    margin-top:
        17px;

    padding:
        13px 15px;

    border-left:
        4px solid
        #e4aa30;

    color:#b4bec2;

    font-size:11px;
}


.status-bar.error{

    border-left-color:#dc5159;

    color:#ffc3c7;
}



/* ============================================================
   AUSTRALIA USER IAR DELETE V1
   ============================================================ */


.slot-actions{

    display:grid;

    grid-template-columns:
        1fr 1fr 1fr;

    gap:
        10px;

    margin-top:
        19px;
}


.slot-actions .slot-download{

    margin-top:
        0;
}



/* ============================================================
   AUSTRALIA USER IAR UPLOAD UI V1
   ============================================================ */

.iar-upload-panel{

    margin-top:
        22px;

    padding:
        20px;

    border:
        1px solid
        rgba(255,190,58,.30);

    border-radius:
        12px;

    background:
        rgba(0,0,0,.18);
}


.iar-upload-panel h3{

    margin:
        0 0 8px;

    font-size:
        15px;

    letter-spacing:
        .08em;
}


.iar-upload-panel p{

    margin:
        0 0 16px;

    opacity:
        .82;
}


.iar-upload-row{

    display:grid;

    grid-template-columns:
        minmax(0,1fr)
        auto
        auto;

    gap:
        12px;

    align-items:
        center;
}


.iar-upload-file{

    width:
        100%;

    min-height:
        46px;

    box-sizing:
        border-box;

    padding:
        10px;

    border:
        1px solid
        rgba(255,255,255,.18);

    border-radius:
        8px;

    background:
        rgba(0,0,0,.26);

    color:
        inherit;
}


.iar-upload-clear{

    min-height:
        46px;

    min-width:
        105px;

    padding:
        8px 18px;

    cursor:
        pointer;

    border:
        1px solid
        rgba(210,145,50,.72);

    border-radius:
        8px;

    background:
        linear-gradient(
            180deg,
            #4b4d4e 0%,
            #292d2f 50%,
            #15191b 100%
        );

    color:
        #ffd06a;

    font-weight:
        900;

    text-shadow:
        0 1px 2px
        #000;

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.15),

        inset 0 -3px 5px
        rgba(0,0,0,.45),

        0 4px 8px
        rgba(0,0,0,.30);
}


.iar-upload-clear:hover:not(:disabled){

    border-color:
        #ffc247;

    background:
        linear-gradient(
            180deg,
            #5d6062 0%,
            #34393c 50%,
            #1b2022 100%
        );
}


.iar-upload-clear:disabled{

    opacity:
        .35;

    cursor:
        not-allowed;
}

.iar-upload-button{

    min-height:
        46px;

    min-width:
        160px;
}


.iar-upload-progress{

    width:
        100%;

    height:
        12px;

    margin-top:
        16px;

    overflow:
        hidden;

    border:
        1px solid
        rgba(255,255,255,.12);

    border-radius:
        999px;

    background:
        rgba(0,0,0,.35);
}


.iar-upload-progress-fill{

    width:
        0%;

    height:
        100%;

    transition:
        width .15s linear;

    background:
        linear-gradient(
            90deg,
            #a66c12,
            #ffc247
        );
}


.iar-upload-progress-text{

    margin-top:
        8px;

    font-size:
        12px;

    opacity:
        .82;
}


@media(max-width:750px){

    .iar-upload-row{

        grid-template-columns:
            1fr;
    }


    .iar-upload-button,
    .iar-upload-clear{

        width:
            100%;
    }
}

/* ============================================================
   AUSTRALIA USER IAR MERGE MODE UI V1
   ============================================================ */

.iar-restore-mode-overlay{

    position:
        fixed;

    inset:
        0;

    z-index:
        99999;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        24px;

    box-sizing:
        border-box;

    background:
        rgba(0,0,0,.78);

    backdrop-filter:
        blur(4px);
}


.iar-restore-mode-card{

    width:
        min(720px,100%);

    padding:
        26px;

    box-sizing:
        border-box;

    border:
        1px solid
        rgba(255,184,37,.76);

    border-radius:
        14px;

    background:
        linear-gradient(
            145deg,
            #283239,
            #11191e 58%,
            #080d10
        );

    box-shadow:
        0 20px 70px
        rgba(0,0,0,.72),
        inset 0 1px 0
        rgba(255,255,255,.16);
}


.iar-restore-mode-card h2{

    margin:
        0 0 10px;

    color:
        #ffc447;

    text-align:
        center;
}


.iar-restore-mode-card p{

    margin:
        8px 0;

    line-height:
        1.5;
}


.iar-restore-mode-file{

    margin:
        18px 0;

    padding:
        14px;

    border:
        1px solid
        rgba(255,184,37,.28);

    border-radius:
        9px;

    background:
        rgba(0,0,0,.25);

    color:
        #ffd06a;

    font-weight:
        800;

    word-break:
        break-word;
}


.iar-restore-mode-warning{

    margin:
        16px 0;

    padding:
        14px;

    border-left:
        4px solid
        #d28f25;

    background:
        rgba(210,143,37,.10);

    line-height:
        1.5;
}


.iar-restore-mode-actions{

    display:
        grid;

    grid-template-columns:
        1fr 1fr 1fr;

    gap:
        12px;

    margin-top:
        22px;
}


.iar-mode-button{

    min-height:
        48px;

    padding:
        10px 14px;

    border-radius:
        8px;

    cursor:
        pointer;

    font-weight:
        900;

    text-shadow:
        0 1px 2px
        #000;
}


.iar-mode-normal{

    border:
        1px solid
        #e0a429;

    background:
        linear-gradient(
            180deg,
            #efbd43,
            #a86b0d
        );

    color:
        #111;
}


.iar-mode-merge{

    border:
        1px solid
        #62c48d;

    background:
        linear-gradient(
            180deg,
            #33865d,
            #123d29
        );

    color:
        #effff5;
}


.iar-mode-cancel{

    border:
        1px solid
        rgba(255,255,255,.25);

    background:
        linear-gradient(
            180deg,
            #414b51,
            #181e22
        );

    color:
        #fff;
}


@media(max-width:700px){

    .iar-restore-mode-actions{

        grid-template-columns:
            1fr;
    }
}

/* ============================================================
   AUSTRALIA USER IAR RESTORE BUTTON V1
   ============================================================ */

.slot-restore{

    min-height:
        46px;

    cursor:pointer;

    border:
        1px solid
        rgba(60,154,102,.90) !important;

    background:
        linear-gradient(
            180deg,
            #286b49 0%,
            #17452f 50%,
            #0b291b 100%
        ) !important;

    color:
        #e2ffed !important;

    font-weight:
        900 !important;

    text-shadow:
        0 1px 2px
        #000 !important;

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.18),

        inset 0 -4px 6px
        rgba(0,0,0,.55),

        0 5px 9px
        rgba(0,0,0,.40) !important;
}


.slot-restore:hover{

    border-color:
        #72d19b !important;

    background:
        linear-gradient(
            180deg,
            #33875d 0%,
            #1d563a 50%,
            #0d3121 100%
        ) !important;
}


.slot-restore:disabled{

    opacity:.35;

    cursor:not-allowed;
}


.future-badge.ready{

    opacity:
        1 !important;

    color:
        #ffc13d !important;

    border-color:
        rgba(255,184,37,.85) !important;

    background:
        rgba(255,184,37,.10) !important;

    box-shadow:
        0 0 12px
        rgba(255,174,0,.15);
}


@media(max-width:900px){

    .slot-actions{

        grid-template-columns:
            1fr;
    }
}

.slot-delete{

    min-height:
        46px;

    cursor:pointer;

    border:
        1px solid
        rgba(184,61,67,.78) !important;

    background:

        linear-gradient(
            180deg,
            #67282c 0%,
            #43171a 50%,
            #260b0d 100%
        ) !important;

    color:
        #ffd9dc !important;

    font-weight:
        900 !important;

    text-shadow:
        0 1px 2px
        #000 !important;

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.18),

        inset 0 -4px 6px
        rgba(0,0,0,.55),

        0 5px 9px
        rgba(0,0,0,.40) !important;
}


.slot-delete:hover{

    border-color:
        #e56870 !important;

    background:

        linear-gradient(
            180deg,
            #843238 0%,
            #521b1f 50%,
            #300c0f 100%
        ) !important;
}


.slot-delete:disabled{

    opacity:.35;

    cursor:not-allowed;
}

@media(max-width:750px){

    


    .header-actions{

        margin-top:15px;
    }


    .iar-summary,
    .slot-grid{

        grid-template-columns:
            1fr;
    }


    .iar-shell{

        width:
            calc(100% - 18px);

        padding:12px;
    }
}


/* ==========================================================
   AUSTRALIA USER BUTTON NO TEXT SHADOW V1
   ========================================================== */

button,
input[type="button"],
input[type="submit"],
input[type="reset"],
a[class*="button"],
a[class*="btn"],
a[class*="back"],
a[class*="logout"] {
    text-shadow: none !important;
}

/* END AUSTRALIA USER BUTTON NO TEXT SHADOW V1 */

</style>


<link
    rel="stylesheet"
    href="/Other/australia-modal.css?v=1">

<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">
<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=20260830-phase3c">

<style id="australia-sentinel-sitewide-v3">


/* ============================================================
   AUSTRALIA SENTINEL 3D CARD SYSTEM V3
   SITE WIDE
   ============================================================ */


.dashboard-card,
.control-hub-card,
.stat-card,
.summary-card,
.inventory-card,
.profile-card,
.region-panel,
.section-card,
.manage-card{


position:relative !important;

overflow:hidden !important;


border-radius:15px !important;


border:

2px solid
rgba(210,218,220,.55) !important;


background:

linear-gradient(
145deg,
#4d5559,
#090b0c
) !important;


box-shadow:

inset 0 2px 0
rgba(255,255,255,.25),

inset 0 -22px 35px
rgba(0,0,0,.8),

0 18px 40px
rgba(0,0,0,.75) !important;


}



.dashboard-card:after,
.control-hub-card:after,
.stat-card:after,
.summary-card:after,
.inventory-card:after,
.profile-card:after,
.region-panel:after,
.section-card:after,
.manage-card:after{


content:"";

position:absolute;

inset:7px;

border-radius:10px;

pointer-events:none;


border:

1px solid
rgba(255,193,58,.45);

}



.card-title,
.card-heading,
.panel-title,
.summary-title{


color:#ffd167 !important;

font-weight:900 !important;

text-shadow:

0 2px 5px
rgba(0,0,0,.8);

}



.card-icon,
.card-icon.svg-badge{


background:

linear-gradient(
145deg,
#70777a,
#111415
) !important;


border:

1px solid
rgba(255,255,255,.3) !important;


box-shadow:

inset 0 2px 4px
rgba(255,255,255,.25),

0 8px 18px
rgba(0,0,0,.6);


}



.dashboard-card:hover,
.control-hub-card:hover,
.stat-card:hover,
.summary-card:hover{


transform:
translateY(-3px);


}


</style>


<style id="australia-user-level-badge-v1">

.php-level{

    min-width:128px;

    padding:
        8px 12px;

    text-align:center;

    border:
        1px solid
        rgba(67,151,220,.38);

    border-radius:
        8px;

    background:
        rgba(15,68,104,.22);

    font-size:10px;

    font-weight:800;

}


.php-level strong{

    display:block;

    margin-bottom:2px;

    color:#7bc6ff;

}


</style>


<style id="ag-role-display-standard-v4">

/* ============================================================
   STANDARD ROLE DISPLAY V4

   250+        GRID OWNER
   other admin ADMIN
   normal user USER
   ============================================================ */

.php-level,
.ag-account-role-box{
    display:inline-flex !important;
    flex-direction:column !important;
    align-items:center !important;
    justify-content:center !important;
    min-width:145px !important;
    min-height:54px !important;
    padding:7px 14px !important;
    box-sizing:border-box !important;
    border:1px solid rgba(55,150,215,.70) !important;
    border-radius:8px !important;
    background:rgba(5,28,43,.88) !important;
    box-shadow:none !important;
    text-align:center !important;
    line-height:1.15 !important;
    color:#ffffff !important;
}

.php-level strong,
.ag-account-role-box strong{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    width:auto !important;
    height:auto !important;
    margin:0 0 4px 0 !important;
    padding:0 !important;
    color:#75c9ff !important;
    background:none !important;
    font-size:12px !important;
    font-weight:900 !important;
    line-height:1.1 !important;
    letter-spacing:.03em !important;
    text-indent:0 !important;
    clip:auto !important;
    overflow:visible !important;
}

.php-level span,
.ag-account-role-box span{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    margin:0 !important;
    padding:0 !important;
    color:#ffffff !important;
    background:none !important;
    font-size:11px !important;
    font-weight:800 !important;
    line-height:1.1 !important;
    white-space:nowrap !important;
}

</style>

<link rel="stylesheet" href="/Other/assets/css/control-center-panel-v1.css?v=8"><link rel="stylesheet" href="/Other/assets/css/control-center-regions-v5.css?v=6"><style id="australia-iar-control-center-v2">
/* AUSTRALIA IAR CONTROL CENTER STYLE V3 START */
.ag-iar-v2{
    width:100%;
    box-sizing:border-box;
    padding:12px 12px 18px;
    color:#d7dde0;
    background:linear-gradient(180deg,rgba(9,11,13,.97) 0%,rgba(4,5,6,.99) 100%);
    border:1px solid rgba(185,140,46,.22);
    border-radius:10px;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.03),0 12px 28px rgba(0,0,0,.42);
}
.ag-iar-v2 *{box-sizing:border-box}

.ag-iar-v2-summary{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:12px;
    margin:0 0 14px;
}
.ag-iar-v2-stat{
    min-width:0;
    padding:13px 15px;
    border:1px solid rgba(191,146,53,.30);
    border-radius:8px;
    background:linear-gradient(180deg,#15181a 0%,#0a0c0e 100%);
    box-shadow:0 6px 15px rgba(0,0,0,.32);
}
.ag-iar-v2-stat-label{
    color:#cda246;
    font-size:7px;
    font-weight:900;
    letter-spacing:.12em;
}
.ag-iar-v2-stat-value{
    margin-top:6px;
    color:#eef2f3;
    font-size:18px;
    font-weight:900;
    line-height:1;
}
.ag-iar-v2-stat-sub{
    margin-top:6px;
    color:#6e787d;
    font-size:7px;
    font-weight:700;
}

.ag-iar-v2-region{
    margin:0 0 14px;
    border:1px solid rgba(191,146,53,.30);
    border-radius:8px;
    overflow:hidden;
    background:linear-gradient(180deg,#121517 0%,#080a0c 100%);
    box-shadow:0 8px 18px rgba(0,0,0,.34);
}
.ag-iar-v2-header{
    min-height:62px;
    padding:12px 15px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    border-bottom:1px solid rgba(255,255,255,.06);
    background:linear-gradient(180deg,rgba(22,25,28,.98),rgba(11,13,15,.98));
}
.ag-iar-v2-identity{
    display:flex;
    align-items:center;
    gap:10px;
    min-width:0;
}
.ag-iar-v2-icon{
    width:32px;
    height:32px;
    display:block;
    object-fit:contain;
    filter:drop-shadow(0 1px 2px rgba(0,0,0,.4));
}
.ag-iar-v2-title{
    color:#edf1f2;
    font-size:12px;
    font-weight:900;
    line-height:1.1;
}
.ag-iar-v2-subtitle{
    margin-top:4px;
    color:#737d82;
    font-size:7px;
    font-weight:800;
    letter-spacing:.10em;
}
.ag-iar-v2-ready{
    position:relative;
    padding-left:14px;
    color:#7be39d;
    font-size:7px;
    font-weight:900;
    letter-spacing:.10em;
}
.ag-iar-v2-ready:before{
    content:"";
    position:absolute;
    left:0;
    top:50%;
    width:7px;
    height:7px;
    margin-top:-4px;
    border-radius:50%;
    background:#5fe28f;
    box-shadow:0 0 8px rgba(95,226,143,.85);
}
.ag-iar-v2-body{
    padding:15px;
}
.ag-iar-v2-save .ag-iar-v2-body{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    align-items:center;
    gap:14px;
}
.ag-iar-v2-copy{
    min-width:0;
}
.ag-iar-v2-copy strong{
    display:block;
    color:#d7a844;
    font-size:9px;
    font-weight:900;
    letter-spacing:.05em;
}
.ag-iar-v2-copy span{
    display:block;
    margin-top:5px;
    color:#889397;
    font-size:8px;
    line-height:1.45;
}

.ag-iar-v2 .rp-action,
.ag-iar-v2-button,
.ag-iar-v2-clear{
    min-height:32px;
    padding:0 14px;
    border:1px solid rgba(196,151,58,.52)!important;
    border-radius:5px!important;
    background:linear-gradient(180deg,#272c2f 0%,#121517 55%,#090b0d 100%)!important;
    color:#eef2f3!important;
    font-size:7px!important;
    font-weight:900!important;
    letter-spacing:.05em!important;
    text-shadow:0 1px 1px #000!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.06),0 4px 8px rgba(0,0,0,.28)!important;
    cursor:pointer;
}
.ag-iar-v2 .rp-action:hover,
.ag-iar-v2-button:hover,
.ag-iar-v2-clear:hover{
    border-color:#e0ae43!important;
    background:linear-gradient(180deg,#32383b 0%,#171b1e 55%,#0b0d10 100%)!important;
}
.ag-iar-v2 .rp-action:disabled,
.ag-iar-v2-button:disabled,
.ag-iar-v2-clear:disabled{
    opacity:.35!important;
    cursor:not-allowed!important;
}

#saveIarButton,
#uploadIarButton,
#clearIarUploadButton{
    width:auto!important;
    min-width:0!important;
    max-width:none!important;
    min-height:32px!important;
    padding-left:12px!important;
    padding-right:12px!important;
    display:inline-flex!important;
    flex:0 0 auto!important;
    align-items:center!important;
    justify-content:center!important;
}


.ag-iar-v2-job{
    grid-column:1/-1;
    margin-top:2px;
    padding:12px 13px;
    border:1px solid rgba(191,146,53,.20);
    border-radius:6px;
    background:#07090b;
}
.ag-iar-v2-job-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
}
.ag-iar-v2-job-top strong{
    color:#d3a342;
    font-size:8px;
    letter-spacing:.06em;
}
.ag-iar-v2-job-badge{
    padding:4px 8px;
    border:1px solid rgba(191,146,53,.35);
    border-radius:999px;
    color:#d7ab49;
    font-size:7px;
    font-weight:900;
}
.ag-iar-v2-job-message{
    margin-top:8px;
    color:#cdd3d6;
    font-size:8px;
}
.ag-iar-v2-job-file{
    margin-top:5px;
    color:#727c81;
    font-size:7px;
    overflow-wrap:anywhere;
}

.ag-iar-v2-slot-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:12px;
}
.ag-iar-v2-slot-grid .card{
    background:none!important;
    background-image:none!important;
    box-shadow:none!important;
    filter:none!important;
}
.ag-iar-v2-slot-grid .card:before,
.ag-iar-v2-slot-grid .card:after{
    display:none!important;
    content:none!important;
}
.ag-iar-v2-slot-grid .slot-card{
    min-width:0;
    min-height:200px;
    margin:0!important;
    padding:15px!important;
    display:flex!important;
    flex-direction:column!important;
    border:1px solid rgba(191,146,53,.24)!important;
    border-radius:8px!important;
    background:linear-gradient(180deg,#101315 0%,#06080a 100%)!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.03),0 6px 14px rgba(0,0,0,.28)!important;
}
.ag-iar-v2-slot-grid .slot-card.empty-slot{
    min-height:150px;
    align-items:center;
    justify-content:center;
    text-align:center;
    border-style:dashed!important;
    background:#07090b!important;
    color:#677277!important;
}
.ag-iar-v2-slot-grid .empty-slot strong{
    display:block;
    margin-bottom:6px;
    color:#9ca6aa;
    font-size:9px;
    font-weight:900;
    letter-spacing:.04em;
}
.ag-iar-v2-slot-grid .slot-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
}
.ag-iar-v2-slot-grid .slot-number{
    width:auto!important;
    height:26px!important;
    min-width:0!important;
    min-height:26px!important;
    max-width:none!important;
    padding:0 10px!important;
    margin:0!important;
    display:inline-flex!important;
    flex:0 0 auto!important;
    align-items:center!important;
    justify-content:center!important;
    border:1px solid rgba(201,156,61,.48)!important;
    border-radius:5px!important;
    background:linear-gradient(180deg,#25221d 0%,#12100d 100%)!important;
    color:#e0b252!important;
    font-size:8px!important;
    font-weight:900!important;
    line-height:1!important;
    letter-spacing:.08em!important;
    text-transform:uppercase!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.05),0 2px 5px rgba(0,0,0,.24)!important;
}
.ag-iar-v2-slot-grid .slot-status{
    padding:4px 8px;
    border:1px solid rgba(72,188,112,.36);
    border-radius:999px;
    background:rgba(34,114,65,.12);
    color:#72d795;
    font-size:7px;
    font-weight:900;
    letter-spacing:.05em;
}
.ag-iar-v2-slot-grid .slot-date,
.ag-iar-v2-slot-grid .slot-size{
    margin-top:7px;
    color:#788489;
    font-size:7px;
}
.ag-iar-v2-slot-grid .slot-card h2{
    margin:12px 0 3px!important;
    color:#e8edef!important;
    font-size:10px!important;
    font-weight:900!important;
    line-height:1.35!important;
    overflow-wrap:anywhere;
}
.ag-iar-v2-slot-grid .slot-spacer{
    flex:1;
}
.ag-iar-v2-slot-grid .slot-actions{
    width:auto!important;
    display:flex!important;
    flex:0 0 auto!important;
    flex-wrap:wrap!important;
    gap:8px!important;
    margin-top:14px!important;
    align-items:center!important;
    justify-content:flex-start!important;
}
.ag-iar-v2-slot-grid .slot-download,
.ag-iar-v2-slot-grid .slot-restore,
.ag-iar-v2-slot-grid .slot-delete{
    width:auto!important;
    height:32px!important;
    min-width:0!important;
    min-height:32px!important;
    max-width:none!important;
    margin:0!important;
    padding:0 12px!important;
    flex:0 0 auto!important;
    border-radius:5px!important;
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    gap:7px!important;
    background:linear-gradient(180deg,#262c2f 0%,#121518 55%,#090b0d 100%)!important;
    color:#edf1f2!important;
    font-size:7px!important;
    font-weight:900!important;
    line-height:1!important;
    letter-spacing:.04em!important;
    text-shadow:0 1px 1px #000!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.06),0 3px 7px rgba(0,0,0,.28)!important;
    text-decoration:none!important;
    white-space:nowrap!important;
}
.ag-iar-v2-slot-grid .slot-download{border:1px solid rgba(191,146,53,.52)!important}
.ag-iar-v2-slot-grid .slot-restore{border:1px solid rgba(191,146,53,.52)!important}
.ag-iar-v2-slot-grid .slot-delete{
    border:1px solid rgba(171,67,72,.56)!important;
    color:#e6b9bb!important;
}
.ag-iar-v2-slot-grid .slot-download:hover,
.ag-iar-v2-slot-grid .slot-restore:hover{
    border-color:#e1b04a!important;
}
.ag-iar-v2-slot-grid .slot-delete:hover{
    border-color:#d56d72!important;
}

.ag-iar-v2-safety{
    margin-top:12px;
    padding:10px 12px;
    display:flex;
    align-items:flex-start;
    gap:10px;
    border:1px solid rgba(191,146,53,.16);
    border-left:3px solid #b88a2d;
    border-radius:5px;
    background:rgba(191,146,53,.035);
}
.ag-iar-v2-safety strong{
    flex:0 0 auto;
    color:#d0a039;
    font-size:7px;
    font-weight:900;
    letter-spacing:.05em;
}
.ag-iar-v2-safety span{
    color:#79868a;
    font-size:7px;
    line-height:1.45;
}

.ag-iar-v2-feature-row{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:10px;
    margin-bottom:12px;
}
.ag-iar-v2-feature{
    min-width:0;
    padding:11px 12px;
    border:1px solid rgba(255,255,255,.06);
    border-radius:6px;
    background:#090b0d;
}
.ag-iar-v2-feature strong{
    display:block;
    color:#d6a740;
    font-size:8px;
    font-weight:900;
}
.ag-iar-v2-feature span{
    display:block;
    margin-top:4px;
    color:#747f83;
    font-size:7px;
    line-height:1.4;
}
.ag-iar-v2-upload{
    padding:12px;
    border:1px solid rgba(191,146,53,.18);
    border-radius:6px;
    background:#07090b;
}
.ag-iar-v2-upload-heading{
    color:#deaf47;
    font-size:9px;
    font-weight:900;
    letter-spacing:.05em;
}
.ag-iar-v2-upload-copy{
    margin-top:5px;
    color:#778488;
    font-size:7px;
}
.ag-iar-v2-upload-row{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto auto;
    gap:8px;
    align-items:center;
    margin-top:10px;
}
.ag-iar-v2-file{
    width:100%;
    min-width:0;
    height:34px;
    padding:4px 6px;
    border:1px solid rgba(191,146,53,.18);
    border-radius:5px;
    background:#050608;
    color:#9ea8ac;
    font-size:7px;
}
.ag-iar-v2-file::file-selector-button{
    height:24px;
    margin-right:8px;
    padding:0 10px;
    border:1px solid rgba(191,146,53,.36);
    border-radius:4px;
    background:#171a1d;
    color:#dfe5e7;
    font-size:7px;
    font-weight:900;
    cursor:pointer;
}
.ag-iar-v2-progress{
    height:6px;
    margin-top:9px;
    overflow:hidden;
    border-radius:999px;
    background:#040607;
}
.ag-iar-v2-progress-fill{
    width:0;
    height:100%;
    background:linear-gradient(90deg,#8c6620,#d2a23a);
}
.ag-iar-v2-progress-text{
    margin-top:6px;
    color:#6b777b;
    font-size:7px;
}
.ag-iar-v2-status{
    padding:10px 12px;
    border:1px solid rgba(255,255,255,.06);
    border-radius:5px;
    background:#080a0c;
    color:#6a767a;
    font-size:7px;
}

.iar-restore-mode-overlay{
    background:rgba(0,0,0,.78)!important;
    backdrop-filter:blur(2px);
}
.iar-restore-mode-card{
    border:1px solid rgba(191,146,53,.38)!important;
    border-radius:8px!important;
    background:linear-gradient(180deg,#171a1d,#090b0d)!important;
    background-image:none!important;
    box-shadow:0 16px 45px rgba(0,0,0,.65)!important;
}
.iar-restore-mode-card:before,
.iar-restore-mode-card:after{
    display:none!important;
    content:none!important;
}
.iar-restore-mode-actions button{
    min-height:32px!important;
    border-radius:5px!important;
    background:linear-gradient(180deg,#262c2f,#0b0d10)!important;
    border:1px solid rgba(191,146,53,.44)!important;
    color:#eef2f3!important;
    font-size:7px!important;
    font-weight:900!important;
}

@media(max-width:900px){
    .ag-iar-v2-summary{grid-template-columns:1fr}
    .ag-iar-v2-slot-grid{grid-template-columns:1fr}
    .ag-iar-v2-feature-row{grid-template-columns:1fr}
    .ag-iar-v2-save .ag-iar-v2-body{grid-template-columns:1fr}
    .ag-iar-v2-upload-row{grid-template-columns:1fr}
    .ag-iar-v2-slot-grid .slot-actions{display:grid!important;grid-template-columns:1fr!important}
    .ag-iar-v2-slot-grid .slot-download,
    .ag-iar-v2-slot-grid .slot-restore,
    .ag-iar-v2-slot-grid .slot-delete{
        width:100%!important;
        min-width:0!important;
    }
    .ag-iar-v2-safety{display:block}
    .ag-iar-v2-safety span{display:block;margin-top:5px}
}

.ag-iar-v2-slot-grid .slot-download::after,
.ag-iar-v2-slot-grid .slot-restore::after,
.ag-iar-v2-slot-grid .slot-delete::after{
    width:20px!important;
    height:20px!important;
    min-width:20px!important;
    min-height:20px!important;
    background-size:20px 20px!important;
    flex:0 0 20px!important;
}
/* AUSTRALIA IAR CONTROL CENTER STYLE V3 END */
</style>
</head>


<body>


<?php
$level = ag_user_level($session);
$isAdmin = ag_is_admin($session);

$siteHeaderKicker = $isAdmin ? "ADMINISTRATION" : "ACCOUNT";
$siteHeaderTitle = "
IAR BACKUPS
";
$siteHeaderRole = ((int)$level >= 250) ? "GRID OWNER" : ($isAdmin ? "ADMIN" : "USER");
$siteHeaderLevel = $level;
$siteHeaderButton = "BACK TO DASHBOARD";
$siteHeaderLink = "/Other/dashboard-return.php";
require_once dirname(__DIR__, 2) . "/includes/site-header.php";
?>

<!-- AUSTRALIA IAR CONTROL CENTER V2 START --><main class="ag-iar-v2"><section class="ag-iar-v2-summary"><article class="ag-iar-v2-stat"><div class="ag-iar-v2-stat-label">SAVED BACKUPS</div><div id="savedCount" class="ag-iar-v2-stat-value">&mdash;</div><div class="ag-iar-v2-stat-sub">Verified server archives</div></article><article class="ag-iar-v2-stat"><div class="ag-iar-v2-stat-label">MAXIMUM SLOTS</div><div class="ag-iar-v2-stat-value">2</div><div class="ag-iar-v2-stat-sub">Protected rotating backups</div></article><article class="ag-iar-v2-stat"><div class="ag-iar-v2-stat-label">BACKUP STATUS</div><div id="backupStatus" class="ag-iar-v2-stat-value">READY</div><div class="ag-iar-v2-stat-sub">Inventory archive system</div></article></section><section class="ag-iar-v2-region ag-iar-v2-save"><header class="ag-iar-v2-header"><div class="ag-iar-v2-identity"><img src="/Other/assets/icons/sentinel/inventory.png" alt="" class="ag-iar-v2-icon"><div><div class="ag-iar-v2-title">Save Inventory Backup</div><div class="ag-iar-v2-subtitle">FULL AVATAR INVENTORY</div></div></div><div class="ag-iar-v2-ready">READY</div></header><div class="ag-iar-v2-body"><div class="ag-iar-v2-copy"><strong>Create a new verified IAR</strong><span>Save a complete archive of your current Grid inventory. Two verified backups can be retained safely on the server.</span></div><button id="saveIarButton" class="rp-action save ag-iar-v2-button" type="button">SAVE NEW IAR</button><div id="jobPanel" class="ag-iar-v2-job" hidden><div class="ag-iar-v2-job-top"><strong>INVENTORY BACKUP</strong><span id="jobBadge" class="ag-iar-v2-job-badge">PROCESSING</span></div><div id="jobMessage" class="ag-iar-v2-job-message"></div><div id="jobFile" class="ag-iar-v2-job-file"></div></div></div></section><section class="ag-iar-v2-region ag-iar-v2-backups"><header class="ag-iar-v2-header"><div class="ag-iar-v2-identity"><img src="/Other/assets/icons/sentinel/files.png" alt="" class="ag-iar-v2-icon"><div><div class="ag-iar-v2-title">My IAR Backups</div><div class="ag-iar-v2-subtitle">TWO VERIFIED BACKUP SLOTS</div></div></div></header><div class="ag-iar-v2-body"><div id="slotGrid" class="ag-iar-v2-slot-grid"></div><div class="ag-iar-v2-safety"><strong>TWO-BACKUP SAFETY</strong><span>If both slots are full, the oldest backup is replaced only after the new IAR has completed and passed verification. A failed backup never removes an existing verified archive.</span></div></div></section><section class="ag-iar-v2-region ag-iar-v2-tools" <?= $canPrivilegedIar ? '' : 'hidden' ?>><header class="ag-iar-v2-header"><div class="ag-iar-v2-identity"><img src="/Other/assets/icons/sentinel/link.png" alt="" class="ag-iar-v2-icon"><div><div class="ag-iar-v2-title">Upload &amp; Restore IAR</div><div class="ag-iar-v2-subtitle">ARCHIVE MANAGEMENT</div></div></div><div class="ag-iar-v2-ready">READY</div></header><div class="ag-iar-v2-body"><div class="ag-iar-v2-feature-row"><div class="ag-iar-v2-feature"><strong>UPLOAD IAR</strong><span>Import an inventory archive from your computer.</span></div><div class="ag-iar-v2-feature"><strong>RESTORE IAR</strong><span>Restore one of your verified saved archives.</span></div><div class="ag-iar-v2-feature"><strong>MERGE RESTORE</strong><span>Merge an archive into the existing inventory.</span></div></div><div class="ag-iar-v2-upload"><div class="ag-iar-v2-upload-heading">UPLOAD IAR FROM PC</div><div class="ag-iar-v2-upload-copy">Select a .iar inventory archive from your computer. Maximum upload size: 512 MB.</div><div class="ag-iar-v2-upload-row"><input id="iarUploadFile" class="ag-iar-v2-file" type="file" accept=".iar"><button id="clearIarUploadButton" class="rp-action ag-iar-v2-clear" type="button" disabled>CLEAR</button><button id="uploadIarButton" class="rp-action save ag-iar-v2-button" type="button">UPLOAD IAR</button></div><div id="iarUploadProgress" class="ag-iar-v2-progress" aria-hidden="true"><div id="iarUploadProgressFill" class="ag-iar-v2-progress-fill"></div></div><div id="iarUploadProgressText" class="ag-iar-v2-progress-text">No file selected.</div></div></div></section><section id="statusBar" class="ag-iar-v2-status">Loading your IAR backup slots...</section></main><!-- AUSTRALIA IAR CONTROL CENTER V2 END -->

<script>

(function(){


const API =
    "/Other/FreshUserDashboardExact/UserPages/iar-backups.php";


const CSRF =
    <?=json_encode(
        $csrfToken,
        JSON_UNESCAPED_SLASHES
    )?>;


const saveButton =
    document.getElementById(
        "saveIarButton"
    );


const slotGrid =
    document.getElementById(
        "slotGrid"
    );


const savedCount =
    document.getElementById(
        "savedCount"
    );


const backupStatus =
    document.getElementById(
        "backupStatus"
    );


const statusBar =
    document.getElementById(
        "statusBar"
    );


const jobPanel =
    document.getElementById(
        "jobPanel"
    );


const jobBadge =
    document.getElementById(
        "jobBadge"
    );


const jobMessage =
    document.getElementById(
        "jobMessage"
    );


const jobFile =
    document.getElementById(
        "jobFile"
    );


let activeJob =
    "";


let timer =
    null;


const canPrivilegedIar =
    <?=json_encode($canPrivilegedIar)?>;


let currentData =
    null;

let activeRestoreJob =
    "";

let restoreTimer =
    null;

let restoreBusy =
    false;


const uploadFileInput =
    document.getElementById(
        "iarUploadFile"
    );


const uploadIarButton =
    document.getElementById(
        "uploadIarButton"
    );


const clearIarUploadButton =
    document.getElementById(
        "clearIarUploadButton"
    );

const uploadProgress =
    document.getElementById(
        "iarUploadProgress"
    );


const uploadProgressFill =
    document.getElementById(
        "iarUploadProgressFill"
    );


const uploadProgressText =
    document.getElementById(
        "iarUploadProgressText"
    );


let uploadBusy =
    false;


function setStatus(
    text,
    error
){

    statusBar.textContent =
        text;


    statusBar.classList.toggle(
        "error",
        !!error
    );
}


function bytes(
    value
){

    const n =
        Number(
            value ||
            0
        );


    if(n < 1024){
        return n + " bytes";
    }


    if(
        n <
        1024 * 1024
    ){

        return (
            n /
            1024
        ).toFixed(1) +
        " KB";
    }


    if(
        n <
        1024 * 1024 * 1024
    ){

        return (
            n /
            1024 /
            1024
        ).toFixed(2) +
        " MB";
    }


    return (
        n /
        1024 /
        1024 /
        1024
    ).toFixed(2) +
    " GB";
}


function dateText(
    value
){

    if(!value){
        return "Unknown date";
    }


    const parsed =
        new Date(
            value
        );


    if(
        Number.isNaN(
            parsed.getTime()
        )
    ){
        return value;
    }


    return parsed.toLocaleString();
}


async function request(
    action,
    options
){

    const url =
        new URL(
            API,
            window.location.origin
        );


    url.searchParams.set(
        "api",
        action
    );


    if(
        options &&
        options.job
    ){

        url.searchParams.set(
            "job",
            options.job
        );
    }


    const fetchOptions = {

        cache:
            "no-store",

        credentials:
            "same-origin"
    };


    if(
        options &&
        options.post
    ){

        fetchOptions.method =
            "POST";


        fetchOptions.headers = {

            "Content-Type":
                "application/json",

            "X-CSRF-Token":
                CSRF
        };


        fetchOptions.body =
            JSON.stringify(
                options.body ||
                {}
            );
    }


    const response =
        await fetch(
            url.toString(),
            fetchOptions
        );


    let data;


    try{

        data =
            await response.json();

    }
    catch(error){

        throw new Error(
            "Grid returned an invalid IAR response."
        );
    }


    if(
        !response.ok
        ||
        !data.ok
    ){

        const error =
            new Error(
                data.error ||
                "IAR request failed."
            );


        error.data =
            data;


        throw error;
    }


    return data;
}


function emptySlot(
    number
){

    const card =
        document.createElement(
            "article"
        );


    card.className =
        "slot-card card empty-slot";


    card.innerHTML =
        '<div>' +
        '<strong>SLOT ' +
        number +
        ' &mdash; EMPTY</strong>' +
        'Save a new IAR to use this backup slot.' +
        '</div>';


    return card;
}


function backupSlot(
    backup,
    number
){

    const card =
        document.createElement(
            "article"
        );


    card.className =
        "slot-card card";


    const top =
        document.createElement(
            "div"
        );


    top.className =
        "slot-top";


    const slotNumber =
        document.createElement(
            "div"
        );


    slotNumber.className =
        "slot-number";


    slotNumber.textContent =
        "IAR " +
        number;


    const verified =
        document.createElement(
            "span"
        );


    verified.className =
        "slot-status";


    verified.textContent =
        "VERIFIED";


    top.appendChild(
        slotNumber
    );


    top.appendChild(
        verified
    );


    const title =
        document.createElement(
            "h2"
        );


    title.textContent =
        backup.name;


    const created =
        document.createElement(
            "div"
        );


    created.className =
        "slot-date";


    created.textContent =
        dateText(
            backup.created
        );


    const size =
        document.createElement(
            "div"
        );


    size.className =
        "slot-size";


    size.textContent =
        "Archive size: " +
        bytes(
            backup.size
        );


    const spacer =
        document.createElement(
            "div"
        );


    spacer.className =
        "slot-spacer";


    const actions =
        document.createElement(
            "div"
        );


    actions.className =
        "slot-actions";


    const download =
        document.createElement(
            "a"
        );


    download.className =
        "button-primary slot-download";


    download.href =
        backup.downloadUrl;


    download.textContent =
        "DOWNLOAD IAR";


    const restoreButton =
        document.createElement(
            "button"
        );


    restoreButton.type =
        "button";


    restoreButton.className =
        "slot-restore";


    restoreButton.textContent =
        "RESTORE IAR";


    if(
        restoreBusy ||
        (
            currentData &&
            currentData.activeJob
        )
    ){

        restoreButton.disabled =
            true;

        restoreButton.title =
            "Wait for the current IAR operation to finish.";
    }


    restoreButton.addEventListener(
        "click",
        function(){

            restoreBackup(
                backup
            );
        }
    );

    const deleteButton =
        document.createElement(
            "button"
        );


    deleteButton.type =
        "button";


    deleteButton.className =
        "slot-delete";


    deleteButton.textContent =
        "DELETE IAR";


    /*
     * DELETE BUTTON AVAILABILITY
     *
     * Do not disable Delete from browser-side currentData.activeJob.
     *
     * The PHP delete handler remains authoritative and independently
     * blocks deletion while:
     * - a restore is active
     * - this owner's IAR backup is genuinely processing
     * - the archive is not verified
     * - ownership validation fails
     */
    if(
        restoreBusy
    ){

        deleteButton.disabled =
            true;

        deleteButton.title =
            "Wait for the current restore to finish.";
    }


    deleteButton.addEventListener(
        "click",
        function(){

            deleteBackup(
                backup
            );
        }
    );


    /*
 * Signed-in users may download their own verified IAR.
 *
 * Restore and Delete remain privileged operations.
 * Backend PrincipalID ownership checks remain authoritative.
 */
if(
    !canPrivilegedIar
){

    restoreButton.hidden =
        true;

    deleteButton.hidden =
        true;

    actions.style.setProperty(
        "grid-template-columns",
        "1fr",
        "important"
    );
}
actions.appendChild(
        download
    );


    actions.appendChild(
        restoreButton
    );


    actions.appendChild(
        deleteButton
    );


    card.appendChild(
        top
    );


    card.appendChild(
        title
    );


    card.appendChild(
        created
    );


    card.appendChild(
        size
    );


    card.appendChild(
        spacer
    );


    card.appendChild(
        actions
    );


    return card;
}


function renderSlots(
    data
){

    currentData =
        data;


    slotGrid.innerHTML =
        "";


    savedCount.textContent =
        data.count +
        " / 2";


    const backups =
        data.backups ||
        [];


    for(
        let i = 0;
        i < 2;
        i++
    ){

        if(backups[i]){

            slotGrid.appendChild(
                backupSlot(
                    backups[i],
                    i + 1
                )
            );

        }
        else{

            slotGrid.appendChild(
                emptySlot(
                    i + 1
                )
            );
        }
    }


    if(data.activeJob){

        activeJob =
            data.activeJob.jobId;


        showJob(
            data.activeJob
        );


        schedulePoll();

    }
    else{

        jobPanel.hidden =
            true;


        backupStatus.textContent =
            "READY";


        saveButton.disabled =
            false;
    }
}


function showJob(
    data
){

    jobPanel.hidden =
        false;


    jobFile.textContent =
        data.name ||
        "";


    jobMessage.textContent =
        data.message ||
        "Inventory backup processing...";


    if(data.verified){

        jobBadge.textContent =
            "VERIFIED";


        jobBadge.style.color =
            "#91e9aa";


        backupStatus.textContent =
            "VERIFIED";


        saveButton.disabled =
            false;


        saveButton.textContent =
            "SAVE NEW IAR";


        return;
    }


    if(data.failed){

        jobBadge.textContent =
            "FAILED";


        jobBadge.style.color =
            "#ffafb5";


        backupStatus.textContent =
            "FAILED";


        saveButton.disabled =
            false;


        saveButton.textContent =
            "TRY AGAIN";


        return;
    }


    jobBadge.textContent =
        "PROCESSING";


    backupStatus.textContent =
        "WORKING";


    saveButton.disabled =
        true;


    saveButton.textContent =
        "IAR BACKUP PROCESSING...";
}


function schedulePoll(){

    if(timer){

        clearTimeout(
            timer
        );
    }


    timer =
        setTimeout(
            poll,
            4000
        );
}


async function poll(){

    if(!activeJob){
        return;
    }


    try{

        const data =
            await request(
                "status",
                {
                    job:
                        activeJob
                }
            );


        showJob(
            data
        );


        if(data.verified){

            activeJob =
                "";


            setStatus(
                data.message,
                false
            );


            setTimeout(
                load,
                1200
            );


            return;
        }


        if(data.failed){

            activeJob =
                "";


            setStatus(
                data.message,
                true
            );


            return;
        }


        schedulePoll();

    }
    catch(error){

        setStatus(
            error.message,
            true
        );


        schedulePoll();
    }
}


async function load(){

    try{

        const data =
            await request(
                "list"
            );


        renderSlots(
            data
        );


        setStatus(
            "Your IAR backup slots are ready.",
            false
        );

    }
    catch(error){

        setStatus(
            error.message,
            true
        );
    }
}


async function start(
    confirmReplace
){

    try{

        saveButton.disabled =
            true;


        saveButton.textContent =
            "STARTING IAR...";


        const data =
            await request(
                "start",
                {
                    post:
                        true,

                    body:{
                        confirmReplace:
                            !!confirmReplace
                    }
                }
            );


        activeJob =
            data.jobId;


        showJob(
            data
        );


        setStatus(
            "Your IAR backup has started. Existing backups remain protected while the new archive is created.",
            false
        );


        schedulePoll();

    }
    catch(error){

        if(
            error.data &&
            error.data.needsConfirmation &&
            error.data.oldest
        ){

            const old =
                error.data.oldest;


            const message =
                "YOU ALREADY HAVE THE MAXIMUM OF 2 IAR BACKUPS.\n\n" +
                "OLDEST BACKUP:\n" +
                old.name +
                "\n" +
                dateText(
                    old.created
                ) +
                "\n\n" +
                "The oldest backup will NOT be deleted yet.\n\n" +
                "Grid will first create and verify the NEW backup. " +
                "Only after the new IAR succeeds will this oldest backup be replaced.\n\n" +
                "If the new backup fails, your existing two backups remain untouched.\n\n" +
                "Do you wish to SAVE A NEW IAR?";


            const replaceConfirmed =
                await auConfirm({

                    title:
                        "TWO IAR BACKUP SLOTS ARE FULL",

                    message:
                        "You already have the maximum of 2 saved IAR backups.",

                    highlight:
                        "OLDEST BACKUP TO BE REPLACED AFTER SUCCESS:\n" +
                        old.name +
                        "\n" +
                        dateText(
                            old.created
                        ),

                    warning:
                        "The old backup will NOT be deleted first. Grid will create and verify the new IAR. Only after the new backup succeeds will the oldest backup be removed. If the new backup fails, your existing two backups remain untouched.",

                    confirmText:
                        "YES - SAVE NEW IAR",

                    cancelText:
                        "CANCEL"
                });


            if(
                replaceConfirmed
            ){

                start(
                    true
                );


                return;
            }
        }


        if(
            error.data &&
            error.data.jobId
        ){

            activeJob =
                error.data.jobId;


            setStatus(
                error.message,
                true
            );


            schedulePoll();


            return;
        }


        saveButton.disabled =
            false;


        saveButton.textContent =
            "SAVE NEW IAR";


        setStatus(
            error.message,
            true
        );
    }
}



/*
 * ============================================================
 * AUSTRALIA USER IAR UPLOAD JAVASCRIPT V1
 * ============================================================
 */


function uploadProgressSet(
    percent,
    text
){

    const safePercent =
        Math.max(
            0,
            Math.min(
                100,
                Number(
                    percent ||
                    0
                )
            )
        );


    uploadProgressFill.style.width =
        safePercent +
        "%";


    uploadProgressText.textContent =
        text ||
        (
            safePercent.toFixed(
                0
            ) +
            "%"
        );
}


function sendIarUpload(
    file,
    confirmReplace
){

    return new Promise(
        function(
            resolve,
            reject
        ){

            const url =
                new URL(
                    API,
                    window.location.origin
                );


            url.searchParams.set(
                "api",
                "upload"
            );


            const form =
                new FormData();


            form.append(
                "iarFile",
                file,
                file.name
            );


            form.append(
                "confirmReplace",
                confirmReplace
                ?
                "1"
                :
                "0"
            );


            const xhr =
                new XMLHttpRequest();


            xhr.open(
                "POST",
                url.toString(),
                true
            );


            xhr.withCredentials =
                true;


            xhr.setRequestHeader(
                "X-CSRF-Token",
                CSRF
            );


            xhr.upload.addEventListener(
                "progress",
                function(event){

                    if(
                        event.lengthComputable
                        &&
                        event.total > 0
                    ){

                        const percent =
                            (
                                event.loaded /
                                event.total
                            ) *
                            100;


                        uploadProgressSet(
                            percent,
                            "Uploading: " +
                            percent.toFixed(
                                0
                            ) +
                            "%"
                        );
                    }
                    else{

                        uploadProgressText.textContent =
                            "Uploading IAR...";
                    }
                }
            );


            xhr.addEventListener(
                "load",
                function(){

                    let data;


                    try{

                        data =
                            JSON.parse(
                                xhr.responseText
                            );
                    }
                    catch(error){

                        reject(
                            new Error(
                                "Grid returned an invalid upload response."
                            )
                        );

                        return;
                    }


                    if(
                        xhr.status >= 200
                        &&
                        xhr.status < 300
                        &&
                        data.ok
                    ){

                        resolve(
                            data
                        );

                        return;
                    }


                    const uploadError =
                        new Error(
                            data.error ||
                            "IAR upload failed."
                        );


                    uploadError.data =
                        data;


                    reject(
                        uploadError
                    );
                }
            );


            xhr.addEventListener(
                "error",
                function(){

                    reject(
                        new Error(
                            "The IAR upload connection failed."
                        )
                    );
                }
            );


            xhr.addEventListener(
                "abort",
                function(){

                    reject(
                        new Error(
                            "The IAR upload was cancelled."
                        )
                    );
                }
            );


            xhr.send(
                form
            );
        }
    );
}


async function uploadSelectedIar(){

    if(uploadBusy){

        return;
    }


    if(
        typeof restoreBusy !==
        "undefined"
        &&
        restoreBusy
    ){

        setStatus(
            "Wait for the current IAR restore to finish before uploading.",
            true
        );

        return;
    }


    const file =
        uploadFileInput.files &&
        uploadFileInput.files.length
        ?
        uploadFileInput.files[0]
        :
        null;


    if(!file){

        setStatus(
            "Select an .iar file from your computer first.",
            true
        );

        return;
    }


    if(
        !/\.iar$/i.test(
            file.name
        )
    ){

        setStatus(
            "Only .iar inventory archive files can be uploaded.",
            true
        );

        return;
    }


    if(file.size <= 0){

        setStatus(
            "The selected IAR file is empty.",
            true
        );

        return;
    }


    if(
        file.size >
        536870912
    ){

        setStatus(
            "The selected IAR exceeds the 512 MB upload limit.",
            true
        );

        return;
    }


    const backups =
        currentData &&
        Array.isArray(
            currentData.backups
        )
        ?
        currentData.backups
        :
        [];


    let confirmReplace =
        false;


    /*
     * --------------------------------------------------------
     * TWO SLOTS ALREADY FULL
     * --------------------------------------------------------
     */

    if(backups.length >= 2){

        const oldest =
            backups[
                backups.length - 1
            ];


        confirmReplace =
            await auConfirm({

                title:
                    "TWO IAR BACKUP SLOTS ARE FULL",

                message:
                    "Upload this IAR from your computer?",

                highlight:
                    "UPLOAD:\n" +
                    file.name +
                    "\n" +
                    bytes(
                        file.size
                    ) +
                    "\n\n" +
                    "OLDEST BACKUP TO BE REPLACED AFTER SUCCESS:\n" +
                    oldest.name +
                    "\n" +
                    dateText(
                        oldest.created
                    ),

                warning:
                    "The oldest backup will NOT be deleted first. Grid will upload and validate the new IAR. Only after the new upload succeeds will the oldest backup be removed.",

                confirmText:
                    "UPLOAD IAR",

                cancelText:
                    "CANCEL"
            });


        if(!confirmReplace){

            return;
        }
    }
    else{

        /*
         * ----------------------------------------------------
         * EMPTY SLOT AVAILABLE
         * ----------------------------------------------------
         */

        const uploadConfirmed =
            await auConfirm({

                title:
                    "UPLOAD IAR FROM PC",

                message:
                    "Upload this inventory archive into your Grid IAR backups?",

                highlight:
                    file.name +
                    "\n" +
                    bytes(
                        file.size
                    ),

                warning:
                    "Grid will validate the archive before adding it to your saved IAR slots.",

                confirmText:
                    "UPLOAD IAR",

                cancelText:
                    "CANCEL"
            });


        if(!uploadConfirmed){

            return;
        }
    }


    try{

        uploadBusy =
            true;

        clearIarUploadButton.disabled =
            true;


        uploadIarButton.disabled =
            true;


        uploadFileInput.disabled =
            true;


        saveButton.disabled =
            true;


        if(
            typeof setRestoreButtonsDisabled ===
            "function"
        ){

            setRestoreButtonsDisabled(
                true
            );
        }


        backupStatus.textContent =
            "UPLOADING";


        uploadProgressSet(
            0,
            "Starting upload..."
        );


        setStatus(
            "Uploading " +
            file.name +
            "...",
            false
        );


        const data =
            await sendIarUpload(
                file,
                confirmReplace
            );


        uploadProgressSet(
            100,
            "Upload complete - IAR verified."
        );


        uploadFileInput.value =
            "";

        clearIarUploadButton.disabled =
            true;


        uploadBusy =
            false;


        uploadIarButton.disabled =
            false;


        uploadFileInput.disabled =
            false;


        saveButton.disabled =
            false;


        await load();


        setStatus(
            data.message ||
            "IAR uploaded and verified successfully.",
            false
        );
    }
    catch(error){

        uploadBusy =
            false;


        uploadIarButton.disabled =
            false;


        uploadFileInput.disabled =
            false;

        clearIarUploadButton.disabled =
            !(
                uploadFileInput.files &&
                uploadFileInput.files.length > 0
            );


        saveButton.disabled =
            false;


        if(
            typeof setRestoreButtonsDisabled ===
            "function"
        ){

            setRestoreButtonsDisabled(
                false
            );
        }


        backupStatus.textContent =
            "READY";


        uploadProgressSet(
            0,
            "Upload failed."
        );


        setStatus(
            error.message,
            true
        );
    }
}


/*
 * ============================================================
 * AUSTRALIA USER IAR RESTORE JAVASCRIPT V1
 * ============================================================
 */


function setRestoreButtonsDisabled(
    disabled
){

    const buttons =
        document.querySelectorAll(
            ".slot-restore, .slot-delete"
        );


    buttons.forEach(
        function(button){

            button.disabled =
                !!disabled;
        }
    );
}


function scheduleRestorePoll(){

    if(restoreTimer){

        clearTimeout(
            restoreTimer
        );
    }


    restoreTimer =
        setTimeout(
            pollRestore,
            2000
        );
}


async function pollRestore(){

    if(!activeRestoreJob){

        return;
    }


    try{

        const data =
            await request(
                "restore-status",
                {
                    job:
                        activeRestoreJob
                }
            );


        if(data.complete){

            activeRestoreJob =
                "";

            restoreBusy =
                false;

            saveButton.disabled =
                false;


            setStatus(
                data.message ||
                "IAR RESTORE COMPLETED SUCCESSFULLY.",
                false
            );


            backupStatus.textContent =
                "READY";


            await load();

            return;
        }


        if(data.failed){

            activeRestoreJob =
                "";

            restoreBusy =
                false;

            saveButton.disabled =
                false;


            setStatus(
                data.message ||
                "IAR restore failed.",
                true
            );


            backupStatus.textContent =
                "READY";


            await load();

            return;
        }


        backupStatus.textContent =
            "RESTORING";


        setStatus(
            data.message ||
            "IAR restore is processing...",
            false
        );


        scheduleRestorePoll();
    }
    catch(error){

        setStatus(
            "Restore status check: " +
            error.message,
            true
        );


        scheduleRestorePoll();
    }
}


/*
 * ============================================================
 * AUSTRALIA USER IAR RESTORE MODE CHOOSER V1
 * ============================================================
 */


function chooseRestoreMode(
    backup
){

    return new Promise(
        function(resolve){

            const overlay =
                document.createElement(
                    "div"
                );


            overlay.className =
                "iar-restore-mode-overlay";


            const card =
                document.createElement(
                    "div"
                );


            card.className =
                "iar-restore-mode-card";


            const title =
                document.createElement(
                    "h2"
                );


            title.textContent =
                "CHOOSE IAR RESTORE MODE";


            const intro =
                document.createElement(
                    "p"
                );


            intro.textContent =
                "Choose how Grid should load this verified IAR into your signed-in avatar.";


            const file =
                document.createElement(
                    "div"
                );


            file.className =
                "iar-restore-mode-file";


            file.textContent =
                backup.name +
                " | " +
                dateText(
                    backup.created
                ) +
                " | " +
                bytes(
                    backup.size
                );


            const normalInfo =
                document.createElement(
                    "p"
                );


            normalInfo.textContent =
                "NORMAL RESTORE: Uses the standard OpenSim load iar command. This is the restore mode you have already tested.";


            const mergeInfo =
                document.createElement(
                    "p"
                );


            mergeInfo.textContent =
                "MERGE RESTORE: Uses OpenSim load iar --merge so the archive is merged into the existing inventory structure.";


            const warning =
                document.createElement(
                    "div"
                );


            warning.className =
                "iar-restore-mode-warning";


            warning.textContent =
                "Neither mode deletes your existing live inventory first. A restore or merge can still create duplicate folders or items when matching content already exists.";


            const actions =
                document.createElement(
                    "div"
                );


            actions.className =
                "iar-restore-mode-actions";


            const normalButton =
                document.createElement(
                    "button"
                );


            normalButton.type =
                "button";


            normalButton.className =
                "iar-mode-button iar-mode-normal";


            normalButton.textContent =
                "NORMAL RESTORE";


            const mergeButton =
                document.createElement(
                    "button"
                );


            mergeButton.type =
                "button";


            mergeButton.className =
                "iar-mode-button iar-mode-merge";


            mergeButton.textContent =
                "MERGE RESTORE";


            const cancelButton =
                document.createElement(
                    "button"
                );


            cancelButton.type =
                "button";


            cancelButton.className =
                "iar-mode-button iar-mode-cancel";


            cancelButton.textContent =
                "CANCEL";


            let finished =
                false;


            function finish(
                value
            ){

                if(finished){

                    return;
                }


                finished =
                    true;


                document.removeEventListener(
                    "keydown",
                    onKey
                );


                overlay.remove();


                resolve(
                    value
                );
            }


            function onKey(
                event
            ){

                if(
                    event.key ===
                    "Escape"
                ){

                    finish(
                        null
                    );
                }
            }


            normalButton.addEventListener(
                "click",
                function(){

                    finish(
                        "normal"
                    );
                }
            );


            mergeButton.addEventListener(
                "click",
                function(){

                    finish(
                        "merge"
                    );
                }
            );


            cancelButton.addEventListener(
                "click",
                function(){

                    finish(
                        null
                    );
                }
            );


            overlay.addEventListener(
                "click",
                function(event){

                    if(
                        event.target ===
                        overlay
                    ){

                        finish(
                            null
                        );
                    }
                }
            );


            document.addEventListener(
                "keydown",
                onKey
            );


            actions.appendChild(
                normalButton
            );


            actions.appendChild(
                mergeButton
            );


            actions.appendChild(
                cancelButton
            );


            card.appendChild(
                title
            );


            card.appendChild(
                intro
            );


            card.appendChild(
                file
            );


            card.appendChild(
                normalInfo
            );


            card.appendChild(
                mergeInfo
            );


            card.appendChild(
                warning
            );


            card.appendChild(
                actions
            );


            overlay.appendChild(
                card
            );


            document.body.appendChild(
                overlay
            );
        }
    );
}


async function restoreBackup(
    backup
){

    if(
        !backup ||
        !backup.jobId ||
        restoreBusy
    ){

        return;
    }


    /*
     * --------------------------------------------------------
     * CHOOSE NORMAL / MERGE / CANCEL
     * --------------------------------------------------------
     */

    const restoreMode =
        await chooseRestoreMode(
            backup
        );


    if(!restoreMode){

        return;
    }


    const mergeMode =
        restoreMode ===
        "merge";


    const modeTitle =
        mergeMode
        ?
        "MERGE RESTORE IAR"
        :
        "NORMAL RESTORE IAR";


    const modeText =
        mergeMode
        ?
        "MERGE RESTORE"
        :
        "NORMAL RESTORE";


    const modeWarning =
        mergeMode
        ?
        "MERGE mode uses OpenSim load iar --merge. Existing live inventory is not deleted first, but matching archive contents can still produce duplicate folders or items. Do not restart OpenSim while the merge is running."
        :
        "NORMAL mode uses the standard OpenSim load iar command that has already been tested. Existing live inventory is not deleted first and restored contents may create duplicate folders or items. Do not restart OpenSim while the restore is running.";


    /*
     * --------------------------------------------------------
     * FINAL CONFIRMATION
     * --------------------------------------------------------
     */

    const restoreConfirmed =
        await auConfirm({

            title:
                modeTitle,

            message:
                modeText +
                " this VERIFIED IAR into your signed-in Grid avatar?",

            highlight:
                backup.name +
                "\n" +
                dateText(
                    backup.created
                ) +
                "\n" +
                bytes(
                    backup.size
                ),

            warning:
                modeWarning,

            confirmText:
                modeText,

            cancelText:
                "CANCEL"
        });


    if(!restoreConfirmed){

        return;
    }


    try{

        restoreBusy =
            true;


        saveButton.disabled =
            true;


        setRestoreButtonsDisabled(
            true
        );


        backupStatus.textContent =
            mergeMode
            ?
            "MERGING"
            :
            "RESTORING";


        setStatus(
            mergeMode
            ?
            "Preparing verified IAR merge restore..."
            :
            "Preparing verified IAR restore...",
            false
        );


        /*
         * ----------------------------------------------------
         * SERVER RECEIVES ONLY:
         *
         * job = verified owned backup job
         * merge = true / false
         *
         * Avatar remains server controlled.
         * ----------------------------------------------------
         */

        const data =
            await request(
                "restore-start",
                {
                    job:
                        backup.jobId,

                    post:
                        true,

                    body:{
                        merge:
                            mergeMode
                    }
                }
            );


        activeRestoreJob =
            data.restoreJobId;


        setStatus(
            (
                mergeMode
                ?
                "Merging "
                :
                "Restoring "
            ) +
            data.name +
            " into " +
            data.avatar +
            "...",
            false
        );


        scheduleRestorePoll();
    }
    catch(error){

        restoreBusy =
            false;


        saveButton.disabled =
            false;


        setRestoreButtonsDisabled(
            false
        );


        backupStatus.textContent =
            "READY";


        setStatus(
            error.message,
            true
        );
    }
}

/*
 * ============================================================
 * AUSTRALIA USER IAR DELETE V1
 * ============================================================
 */


async function deleteBackup(
    backup
){

    if(
        !backup ||
        !backup.jobId
    ){
        return;
    }


    const warning =
        "DELETE THIS IAR BACKUP?\n\n" +
        backup.name +
        "\n" +
        dateText(
            backup.created
        ) +
        "\n" +
        bytes(
            backup.size
        ) +
        "\n\n" +
        "This will permanently remove this saved IAR from the Grid server.\n\n" +
        "A copy you previously downloaded to your own computer will NOT be affected.\n\n" +
        "The separate Clean Inventory Safety IAR will NOT be affected.\n\n" +
        "Do you want to permanently DELETE this IAR?";


    const deleteConfirmed =
        await auConfirm({

            title:
                "DELETE IAR BACKUP",

            message:
                "Are you sure you want to permanently delete this saved IAR backup?",

            highlight:
                backup.name +
                "\n" +
                dateText(
                    backup.created
                ) +
                "\n" +
                bytes(
                    backup.size
                ),

            warning:
                "This permanently removes this IAR from the Grid server. A copy already downloaded to your computer is not affected. Your separate Clean Inventory Safety IAR is not affected.",

            confirmText:
                "DELETE IAR",

            cancelText:
                "KEEP BACKUP",

            danger:
                true
        });


    if(
        !deleteConfirmed
    ){
        return;
    }


    try{

        backupStatus.textContent =
            "DELETING";


        saveButton.disabled =
            true;


        setStatus(
            "Deleting selected IAR backup...",
            false
        );


        const data =
            await request(
                "delete",
                {
                    post:
                        true,

                    body:{
                        jobId:
                            backup.jobId
                    }
                }
            );


        setStatus(
            data.message ||
            "IAR backup deleted successfully.",
            false
        );


        await load();

    }
    catch(error){

        backupStatus.textContent =
            "READY";


        saveButton.disabled =
            false;


        setStatus(
            error.message,
            true
        );
    }
}

saveButton.addEventListener(
    "click",
    async function(){

        const saveConfirmed =
            await auConfirm({

                title:
                    "SAVE NEW IAR",

                message:
                    "Create a full IAR backup of your current Grid inventory?",

                warning:
                    "This is a BACKUP operation only. It will not delete, move or replace anything in your live inventory.",

                confirmText:
                    "SAVE NEW IAR",

                cancelText:
                    "CANCEL"
            });


        if(
            !saveConfirmed
        ){
            return;
        }


        start(
            false
        );
    }
);


uploadFileInput.addEventListener(
    "change",
    function(){

        const file =
            uploadFileInput.files &&
            uploadFileInput.files.length
            ?
            uploadFileInput.files[0]
            :
            null;


        if(!file){

            uploadProgressSet(
                0,
                "No file selected."
            );

            return;
        }


        uploadProgressSet(
            0,
            file.name +
            " - " +
            bytes(
                file.size
            )
        );
    }
);


/*
 * ============================================================
 * AUSTRALIA USER IAR UPLOAD CLEAR JAVASCRIPT V1
 * ============================================================
 */

uploadFileInput.addEventListener(
    "change",
    function(){

        const hasFile =
            uploadFileInput.files &&
            uploadFileInput.files.length > 0;


        clearIarUploadButton.disabled =
            !hasFile ||
            uploadBusy;
    }
);


clearIarUploadButton.addEventListener(
    "click",
    function(){

        if(uploadBusy){

            return;
        }


        uploadFileInput.value =
            "";


        clearIarUploadButton.disabled =
            true;


        uploadProgressSet(
            0,
            "No file selected."
        );


        setStatus(
            "Upload file selection cleared.",
            false
        );
    }
);


uploadIarButton.addEventListener(
    "click",
    uploadSelectedIar
);


window.addEventListener(
    "pagehide",
    function(){

        if(restoreTimer){

            clearTimeout(
                restoreTimer
            );
        }

        if(timer){

            clearTimeout(
                timer
            );
        }
    }
);


load();


})();

</script>



<script src="/Other/australia-modal.js?v=1"></script>
<script defer src="/Other/core/ag-confirm-modal.js?v=1"></script>

<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>






