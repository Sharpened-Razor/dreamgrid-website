<?php

/*
 ============================================================
 AUSTRALIA CREATE REGION PHASE 2 CORE
 LOCAL OWNER API
 ============================================================
*/

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');


function agCreateOwnerFail(
    string $message,
    int $status = 400
): void {

    http_response_code($status);

    echo json_encode(
        [
            'ok' => false,
            'error' => $message
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


$session =
    ag_current_session();


if (!$session) {

    agCreateOwnerFail(
        'Not logged in.',
        401
    );
}


if (
    !ag_is_admin(
        $session
    )
) {

    agCreateOwnerFail(
        'Grid owner access only.',
        403
    );
}


$con =
    ag_db_connect();


if (!$con) {

    agCreateOwnerFail(
        'Account database is unavailable.',
        500
    );
}


mysqli_set_charset(
    $con,
    'utf8mb4'
);


$result =
    mysqli_query(
        $con,
        'SELECT ' .
        'PrincipalID, FirstName, LastName, UserLevel ' .
        'FROM UserAccounts ' .
        'WHERE UserLevel >= 0 ' .
        'ORDER BY FirstName, LastName'
    );


if (!$result) {

    mysqli_close($con);

    agCreateOwnerFail(
        'Could not read local avatars.',
        500
    );
}


$owners = [];


while (
    $row =
        mysqli_fetch_assoc($result)
) {

    $uuid =
        trim(
            (string)(
                $row['PrincipalID'] ??
                ''
            )
        );

    $name =
        trim(
            (string)(
                $row['FirstName'] ??
                ''
            ) .
            ' ' .
            (string)(
                $row['LastName'] ??
                ''
            )
        );


    if (
        preg_match(
            '/^[0-9a-fA-F]{8}-' .
            '[0-9a-fA-F]{4}-' .
            '[0-9a-fA-F]{4}-' .
            '[0-9a-fA-F]{4}-' .
            '[0-9a-fA-F]{12}$/',
            $uuid
        ) !== 1
    ) {

        continue;
    }


    if ($name === '') {
        continue;
    }


    $owners[] =
        [
            'uuid' =>
                $uuid,

            'name' =>
                $name,

            'level' =>
                (int)(
                    $row['UserLevel'] ??
                    0
                )
        ];
}


mysqli_free_result($result);
mysqli_close($con);


$_SESSION['ag_create_region_csrf'] =
    bin2hex(
        random_bytes(32)
    );


echo json_encode(
    [
        'ok' =>
            true,

        'csrf' =>
            $_SESSION[
                'ag_create_region_csrf'
            ],

        'owners' =>
            $owners
    ],
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE
);