<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/database.php';


$session =
    (defined('AG_USER_PROFILE_ACTION') && AG_USER_PROFILE_ACTION === true)
        ? ag_require_login()
        : ag_require_admin();


ag_no_cache();

foreach ($_POST as $value) {
    if (!is_string($value)) {
        http_response_code(400);
        exit('Invalid profile request.');
    }
}


if (
    ($_SERVER[
        'REQUEST_METHOD'
    ] ?? '')
    !==
    'POST'
) {

    http_response_code(
        405
    );

    header(
        'Allow: POST'
    );

    exit(
        'POST required.'
    );
}


if (
    session_status() !==
    PHP_SESSION_ACTIVE
) {

    session_start();
}


function cpp_redirect(
    string $result,
    string $sid = ''
): void {

    $url =
        ((defined('AG_USER_PROFILE_ACTION') && AG_USER_PROFILE_ACTION === true)
            ? '/Other/FreshUserDashboardExact/UserPages/profile.php?result='
            : '/Other/panel-profile.php?result=') .
        rawurlencode(
            $result
        );


    if ($sid !== '') {

        $url .=
            '&sid=' .
            rawurlencode(
                $sid
            );
    }


    header(
        'Location: ' .
        $url,
        true,
        303
    );


    exit;
}


function cpp_same_origin(): bool
{

    $fetchSite =
        strtolower(
            trim(
                (string)(
                    $_SERVER[
                        'HTTP_SEC_FETCH_SITE'
                    ]
                    ??
                    ''
                )
            )
        );


    if (
        $fetchSite ===
        'same-origin'
    ) {

        return true;
    }


    $host =
        strtolower(
            trim(
                (string)(
                    $_SERVER[
                        'HTTP_HOST'
                    ]
                    ??
                    ''
                )
            )
        );


    if ($host === '') {
        return false;
    }


    $https =
        strtolower(
            trim(
                (string)(
                    $_SERVER[
                        'HTTPS'
                    ]
                    ??
                    ''
                )
            )
        );


    $scheme =
        (
            $https !== ''
            &&
            $https !== 'off'
            &&
            $https !== '0'
        )
            ? 'https'
            : 'http';


    $expected =
        strtolower(
            $scheme .
            '://' .
            $host
        );


    $origin =
        strtolower(
            rtrim(
                trim(
                    (string)(
                        $_SERVER[
                            'HTTP_ORIGIN'
                        ]
                        ??
                        ''
                    )
                ),
                '/'
            )
        );


    if ($origin !== '') {

        return
            $origin ===
            $expected;
    }


    $referer =
        strtolower(
            trim(
                (string)(
                    $_SERVER[
                        'HTTP_REFERER'
                    ]
                    ??
                    ''
                )
            )
        );


    if ($referer !== '') {

        return
            str_starts_with(
                $referer,
                $expected . '/'
            );
    }


    return false;
}


function cpp_len(
    string $value
): int {

    if (
        function_exists(
            'mb_strlen'
        )
    ) {

        return
            mb_strlen(
                $value,
                'UTF-8'
            );
    }


    return strlen(
        $value
    );
}


$sid =
    trim(
        (string)(
            $_POST[
                'sid'
            ]
            ??
            ''
        )
    );


if (
    !cpp_same_origin()
) {

    cpp_redirect(
        'security-error',
        $sid
    );
}


$expectedCsrf =
    (string)(
        $_SESSION[
            'cc_profile_csrf'
        ]
        ??
        ''
    );


$postedCsrf =
    trim(
        (string)(
            $_POST[
                'csrf'
            ]
            ??
            ''
        )
    );


if (
    $expectedCsrf === ''
    ||
    $postedCsrf === ''
    ||
    !hash_equals(
        $expectedCsrf,
        $postedCsrf
    )
) {

    cpp_redirect(
        'security-error',
        $sid
    );
}


$principalId =
    trim(
        (string)(
            $session[
                'principalId'
            ]
            ??
            ''
        )
    );


if (
    !preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $principalId)
) {

    cpp_redirect(
        'security-error',
        $sid
    );
}


/*
 * ============================================================
 * ACCEPT ONLY KNOWN PROFILE FIELDS
 * ============================================================
 */

$about =
    trim(
        (string)(
            $_POST[
                'profileAboutText'
            ]
            ??
            ''
        )
    );


$url =
    trim(
        (string)(
            $_POST[
                'profileURL'
            ]
            ??
            ''
        )
    );


$languages =
    trim(
        (string)(
            $_POST[
                'profileLanguages'
            ]
            ??
            ''
        )
    );


$wantTo =
    trim(
        (string)(
            $_POST[
                'profileWantToText'
            ]
            ??
            ''
        )
    );


$skills =
    trim(
        (string)(
            $_POST[
                'profileSkillsText'
            ]
            ??
            ''
        )
    );


$firstLife =
    trim(
        (string)(
            $_POST[
                'profileFirstText'
            ]
            ??
            ''
        )
    );


$allowPublish =
    isset(
        $_POST[
            'profileAllowPublish'
        ]
    )
        ? 1
        : 0;


$maturePublish =
    isset(
        $_POST[
            'profileMaturePublish'
        ]
    )
        ? 1
        : 0;


/*
 * Same field-size limits used by
 * the new Control Center form.
 */

if (
    cpp_len($about) > 8000
    ||
    cpp_len($url) > 255
    ||
    cpp_len($languages) > 2000
    ||
    cpp_len($wantTo) > 4000
    ||
    cpp_len($skills) > 4000
    ||
    cpp_len($firstLife) > 8000
) {

    cpp_redirect(
        'too-long',
        $sid
    );
}


/*
 * Strip embedded NUL bytes.
 */

$about =
    str_replace(
        "\0",
        '',
        $about
    );


$url =
    str_replace(
        "\0",
        '',
        $url
    );


$languages =
    str_replace(
        "\0",
        '',
        $languages
    );


$wantTo =
    str_replace(
        "\0",
        '',
        $wantTo
    );


$skills =
    str_replace(
        "\0",
        '',
        $skills
    );


$firstLife =
    str_replace(
        "\0",
        '',
        $firstLife
    );


$db =
    ag_db_connect();


if (!$db) {

    cpp_redirect(
        'db-error',
        $sid
    );
}


mysqli_set_charset(
    $db,
    'utf8mb4'
);


/*
 * ============================================================
 * REQUIRE EXISTING PROFILE RECORD
 *
 * We do NOT invent a new OpenSim profile row here.
 * Existing profile creation remains with OpenSim's
 * normal profile service.
 * ============================================================
 */

$check =
    @mysqli_prepare(
        $db,

        'SELECT COUNT(*)
         FROM userprofile
         WHERE useruuid = ?'
    );


if (!$check) {

    mysqli_close(
        $db
    );


    cpp_redirect(
        'db-error',
        $sid
    );
}


mysqli_stmt_bind_param(
    $check,
    's',
    $principalId
);


$count =
    0;


if (
    @mysqli_stmt_execute(
        $check
    )
) {

    mysqli_stmt_bind_result(
        $check,
        $count
    );


    mysqli_stmt_fetch(
        $check
    );
}


mysqli_stmt_close(
    $check
);


if (
    (int)$count < 1
) {

    mysqli_close(
        $db
    );


    cpp_redirect(
        'profile-missing',
        $sid
    );
}


/*
 * ============================================================
 * UPDATE EXISTING OPENSIM PROFILE
 * ============================================================
 */

$sql =
    'UPDATE userprofile
     SET
        profileAboutText = ?,
        profileURL = ?,
        profileLanguages = ?,
        profileWantToText = ?,
        profileSkillsText = ?,
        profileFirstText = ?,
        profileAllowPublish = ?,
        profileMaturePublish = ?
     WHERE useruuid = ?
     LIMIT 1';


$stmt =
    @mysqli_prepare(
        $db,
        $sql
    );


if (!$stmt) {

    mysqli_close(
        $db
    );


    cpp_redirect(
        'db-error',
        $sid
    );
}


mysqli_stmt_bind_param(

    $stmt,

    'ssssssiis',

    $about,
    $url,
    $languages,
    $wantTo,
    $skills,
    $firstLife,
    $allowPublish,
    $maturePublish,
    $principalId
);


$ok =
    @mysqli_stmt_execute(
        $stmt
    );


mysqli_stmt_close(
    $stmt
);


mysqli_close(
    $db
);


if (!$ok) {

    cpp_redirect(
        'db-error',
        $sid
    );
}


/*
 * Rotate CSRF after successful save.
 */

$_SESSION[
    'cc_profile_csrf'
] =
    bin2hex(
        random_bytes(32)
    );


cpp_redirect(
    'saved',
    $sid
);