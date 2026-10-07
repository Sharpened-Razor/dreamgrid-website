<?php

declare(strict_types=1);

/*
 * DREAMGRID REGION USER ACTION V3
 *
 * Supports:
 * - JSON requests
 * - Server-side Region Manager edit form
 */

require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/database.php';

ag_require_same_origin_post();
ag_no_cache();

$session =
    ag_require_admin();

$dgUserHtmlMode =
    (string)(
        $_POST['html_mode'] ??
        ''
    ) === '1';


function dg_user_reply(
    bool $ok,
    array $data = [],
    string $error = '',
    int $status = 200
): never {

    global $dgUserHtmlMode;


    if ($dgUserHtmlMode) {

        $uuid =
            strtolower(
                trim(
                    (string)(
                        $_POST['uuid'] ??
                        ''
                    )
                )
            );


        $query = [
            'edit_user' =>
                $uuid
        ];


        if ($ok) {

            $query['user_saved'] =
                '1';

        }
        else {

            $query['user_error'] =
                $error !== ''
                    ? $error
                    : 'User operation failed.';
        }


        header(
            'Location: /Other/admin-regions.php?' .
            http_build_query(
                $query,
                '',
                '&',
                PHP_QUERY_RFC3986
            )
        );

        exit;
    }


    header(
        'Content-Type: application/json; charset=utf-8'
    );


    http_response_code(
        $status
    );


    echo json_encode(
        $ok
            ? array_merge(
                [
                    'ok' => true
                ],
                $data
            )
            : [
                'ok' => false,
                'error' => $error
            ],
        JSON_UNESCAPED_SLASHES
    );


    exit;
}


function dg_user_uuid(
    $value
): string {

    $uuid =
        strtolower(
            trim(
                (string)$value
            )
        );


    if (
        !preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $uuid
        )
    ) {

        dg_user_reply(
            false,
            [],
            'Invalid user UUID.',
            400
        );
    }


    return $uuid;
}


function dg_user_skip_names(): array {

    $raw =
        trim(
            (string)(
                ag_dg_setting(
                    'IARsToSkipBackup'
                ) ??
                ''
            )
        );


    if ($raw === '') {
        return [];
    }


    $names = [];


    foreach (
        explode(
            ',',
            $raw
        ) as $name
    ) {

        $name =
            trim(
                $name
            );


        if ($name !== '') {

            $names[] =
                $name;
        }
    }


    return $names;
}


function dg_user_settings_candidate(
    string $originalText,
    string $oldName,
    string $newName,
    bool $autoBackup
): string {

    $next = [];


    foreach (
        dg_user_skip_names()
        as $name
    ) {

        if (
            strcasecmp(
                $name,
                $oldName
            ) === 0 ||
            strcasecmp(
                $name,
                $newName
            ) === 0
        ) {

            continue;
        }


        $next[] =
            $name;
    }


    /*
     * DreamGrid stores USERS TO SKIP.
     *
     * Auto Backup checked:
     * remove name from skip list.
     *
     * Auto Backup unchecked:
     * add name to skip list.
     */
    if (!$autoBackup) {

        $next[] =
            $newName;
    }


    $replacement =
        'IARsToSkipBackup=' .
        implode(
            ',',
            $next
        );


    if (
        preg_match(
            '/^IARsToSkipBackup=.*$/mi',
            $originalText
        )
    ) {

        return
            preg_replace(
                '/^IARsToSkipBackup=.*$/mi',
                $replacement,
                $originalText,
                1
            ) ??
            $originalText;
    }


    return
        rtrim(
            $originalText,
            "\r\n"
        ) .
        PHP_EOL .
        $replacement .
        PHP_EOL;
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


$uuid =
    dg_user_uuid(
        $_POST['uuid'] ??
        ''
    );


$con =
    ag_db_connect();


if (!$con) {

    dg_user_reply(
        false,
        [],
        'Grid database is unavailable.',
        500
    );
}


mysqli_set_charset(
    $con,
    'utf8mb4'
);


if ($action === 'load') {

    $stmt =
        mysqli_prepare(
            $con,
            '
            SELECT
                PrincipalID,
                FirstName,
                LastName,
                Email,
                UserTitle,
                UserLevel
            FROM UserAccounts
            WHERE PrincipalID = ?
            LIMIT 1
            '
        );


    if (!$stmt) {

        dg_user_reply(
            false,
            [],
            'Could not prepare user lookup.',
            500
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        's',
        $uuid
    );


    mysqli_stmt_execute(
        $stmt
    );


    $result =
        mysqli_stmt_get_result(
            $stmt
        );


    $row =
        $result
            ? mysqli_fetch_assoc(
                $result
            )
            : null;


    mysqli_stmt_close(
        $stmt
    );


    if (!$row) {

        dg_user_reply(
            false,
            [],
            'User was not found.',
            404
        );
    }


    $avatarName =
        trim(
            (string)$row['FirstName'] .
            ' ' .
            (string)$row['LastName']
        );


    $skipped =
        false;


    foreach (
        dg_user_skip_names()
        as $skipName
    ) {

        if (
            strcasecmp(
                $skipName,
                $avatarName
            ) === 0
        ) {

            $skipped =
                true;

            break;
        }
    }


    dg_user_reply(
        true,
        [
            'user' => [
                'uuid' =>
                    (string)$row['PrincipalID'],

                'first_name' =>
                    (string)$row['FirstName'],

                'last_name' =>
                    (string)$row['LastName'],

                'email' =>
                    (string)$row['Email'],

                'user_title' =>
                    (string)$row['UserTitle'],

                'user_level' =>
                    (int)$row['UserLevel'],

                'auto_backup' =>
                    !$skipped
            ]
        ]
    );
}


if ($action !== 'save') {

    dg_user_reply(
        false,
        [],
        'Unsupported user action.',
        400
    );
}


$firstName =
    trim(
        (string)(
            $_POST['first_name'] ??
            ''
        )
    );


$lastName =
    trim(
        (string)(
            $_POST['last_name'] ??
            ''
        )
    );


$userTitle =
    trim(
        (string)(
            $_POST['user_title'] ??
            ''
        )
    );


$email =
    trim(
        (string)(
            $_POST['email'] ??
            ''
        )
    );


$userLevel =
    (int)(
        $_POST['user_level'] ??
        0
    );


$autoBackup =
    (string)(
        $_POST['auto_backup'] ??
        '0'
    ) === '1';

$newPassword =
    (string)(
        $_POST['new_password'] ??
        ''
    );


/*
 * DREAMGRID EDIT USER PASSWORD COMPLETE V1
 *
 * Blank password:
 *     keep the existing password.
 *
 * Password supplied:
 *     update OpenSim auth within the same transaction.
 */



if (
    $firstName === '' ||
    strlen(
        $firstName
    ) > 64
) {

    dg_user_reply(
        false,
        [],
        'First Name is required and must be 64 characters or less.',
        400
    );
}


if (
    $lastName === '' ||
    strlen(
        $lastName
    ) > 64
) {

    dg_user_reply(
        false,
        [],
        'Last Name is required and must be 64 characters or less.',
        400
    );
}


if (
    strlen(
        $userTitle
    ) > 64
) {

    dg_user_reply(
        false,
        [],
        'Profile Account Name is too long.',
        400
    );
}


if (
    strlen(
        $email
    ) > 254 ||
    (
        $email !== '' &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    )
) {

    dg_user_reply(
        false,
        [],
        'Enter a valid email address.',
        400
    );
}



if ($newPassword !== '') {

    $passwordLength =
        strlen(
            $newPassword
        );


    if ($passwordLength < 8) {

        dg_user_reply(
            false,
            [],
            'Password must contain at least 8 characters.',
            400
        );
    }


    if ($passwordLength > 128) {

        dg_user_reply(
            false,
            [],
            'Password must contain no more than 128 characters.',
            400
        );
    }
}

$allowedLevels = [
    -1,
    0,
    100,
    200,
    250
];


if (
    !in_array(
        $userLevel,
        $allowedLevels,
        true
    )
) {

    dg_user_reply(
        false,
        [],
        'Invalid user level.',
        400
    );
}


$lookup =
    mysqli_prepare(
        $con,
        '
        SELECT
            FirstName,
            LastName
        FROM UserAccounts
        WHERE PrincipalID = ?
        LIMIT 1
        '
    );


if (!$lookup) {

    dg_user_reply(
        false,
        [],
        'Could not prepare user lookup.',
        500
    );
}


mysqli_stmt_bind_param(
    $lookup,
    's',
    $uuid
);


mysqli_stmt_execute(
    $lookup
);


$lookupResult =
    mysqli_stmt_get_result(
        $lookup
    );


$oldRow =
    $lookupResult
        ? mysqli_fetch_assoc(
            $lookupResult
        )
        : null;


mysqli_stmt_close(
    $lookup
);


if (!$oldRow) {

    dg_user_reply(
        false,
        [],
        'User was not found.',
        404
    );
}


$oldName =
    trim(
        (string)$oldRow['FirstName'] .
        ' ' .
        (string)$oldRow['LastName']
    );


$newName =
    trim(
        $firstName .
        ' ' .
        $lastName
    );


$settingsPath =
    ag_dg_settings_file();


$settingsOriginal =
    null;


$settingsCandidate =
    null;


$settingsWritten =
    false;


if (
    $settingsPath !== null &&
    is_file(
        $settingsPath
    )
) {

    $settingsOriginal =
        @file_get_contents(
            $settingsPath
        );


    if ($settingsOriginal === false) {

        dg_user_reply(
            false,
            [],
            'DreamGrid Settings.ini could not be read.',
            500
        );
    }


    $settingsCandidate =
        dg_user_settings_candidate(
            $settingsOriginal,
            $oldName,
            $newName,
            $autoBackup
        );
}


mysqli_begin_transaction(
    $con
);


try {

    $update =
        mysqli_prepare(
            $con,
            '
            UPDATE UserAccounts
            SET
                Email = ?,
                UserTitle = ?,
                UserLevel = ?,
                FirstName = ?,
                LastName = ?
            WHERE PrincipalID = ?
            LIMIT 1
            '
        );


    if (!$update) {

        throw new RuntimeException(
            'Could not prepare user update.'
        );
    }


    mysqli_stmt_bind_param(
        $update,
        'ssisss',
        $email,
        $userTitle,
        $userLevel,
        $firstName,
        $lastName,
        $uuid
    );


    if (
        !mysqli_stmt_execute(
            $update
        )
    ) {

        mysqli_stmt_close(
            $update
        );


        throw new RuntimeException(
            'User database update failed.'
        );
    }


    mysqli_stmt_close(
        $update
    );


    

    /*
     * --------------------------------------------------------
     * OPENSIM PASSWORD
     * --------------------------------------------------------
     *
     * OpenSimulator authentication:
     *
     *     MD5(
     *         MD5(plain password)
     *         + ":"
     *         + password salt
     *     )
     *
     * Existing DreamGrid/OpenSim accounts use a 32-character
     * hexadecimal salt.
     */

    if ($newPassword !== '') {

        $newSalt =
            bin2hex(
                random_bytes(16)
            );


        $newHash =
            md5(
                md5(
                    $newPassword
                ) .
                ':' .
                $newSalt
            );


        $passwordStmt =
            mysqli_prepare(
                $con,
                '
                UPDATE auth
                SET
                    passwordHash = ?,
                    passwordSalt = ?
                WHERE UUID = ?
                LIMIT 1
                '
            );


        if (!$passwordStmt) {

            throw new RuntimeException(
                'Password update could not be prepared.'
            );
        }


        mysqli_stmt_bind_param(
            $passwordStmt,
            'sss',
            $newHash,
            $newSalt,
            $uuid
        );


        if (
            !mysqli_stmt_execute(
                $passwordStmt
            )
        ) {

            mysqli_stmt_close(
                $passwordStmt
            );


            throw new RuntimeException(
                'Password update failed.'
            );
        }


        $passwordRows =
            mysqli_stmt_affected_rows(
                $passwordStmt
            );


        mysqli_stmt_close(
            $passwordStmt
        );


        if ($passwordRows < 1) {

            throw new RuntimeException(
                'OpenSim auth record was not updated.'
            );
        }


        /*
         * Do not retain the plain password any longer
         * than required.
         */
        $newPassword =
            '';
    }

if (
        $settingsCandidate !== null &&
        $settingsOriginal !== null &&
        $settingsCandidate !==
        $settingsOriginal
    ) {

        $written =
            @file_put_contents(
                $settingsPath,
                $settingsCandidate,
                LOCK_EX
            );


        if ($written === false) {

            throw new RuntimeException(
                'DreamGrid Auto Backup setting could not be saved.'
            );
        }


        $settingsWritten =
            true;
    }


    if (
        !mysqli_commit(
            $con
        )
    ) {

        throw new RuntimeException(
            'Database commit failed.'
        );
    }

}
catch (Throwable $error) {

    @mysqli_rollback(
        $con
    );


    if (
        $settingsWritten &&
        $settingsOriginal !== null &&
        $settingsPath !== null
    ) {

        @file_put_contents(
            $settingsPath,
            $settingsOriginal,
            LOCK_EX
        );
    }


    dg_user_reply(
        false,
        [],
        $error->getMessage(),
        500
    );
}


dg_user_reply(
    true,
    [
        'message' =>
            'User saved.'
    ]
);