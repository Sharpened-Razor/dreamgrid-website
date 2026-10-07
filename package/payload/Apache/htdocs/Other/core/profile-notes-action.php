<?php

require_once __DIR__ . '/bootstrap.php';


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
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !==
    'POST'
) {

    http_response_code(405);

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


function pv4n_redirect(
    string $result,
    string $sid = ''
): never {

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


function pv4n_same_origin(): bool
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
            ?
            'https'
            :
            'http';


    $expected =
        $scheme .
        '://' .
        $host;


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

        return (
            $origin ===
            $expected
        );
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


    return (
        $referer !== ''
        &&
        str_starts_with(
            $referer,
            $expected . '/'
        )
    );
}


$sid =
    trim(
        (string)(
            $_POST['sid']
            ??
            ''
        )
    );


if (
    !pv4n_same_origin()
) {

    pv4n_redirect(
        'security-error',
        $sid
    );
}


$expected =
    (string)(
        $_SESSION[
            'cc_profile_notes_csrf'
        ]
        ??
        ''
    );


$received =
    trim(
        (string)(
            $_POST['csrf']
            ??
            ''
        )
    );


if (
    $expected === ''
    ||
    $received === ''
    ||
    !hash_equals(
        $expected,
        $received
    )
) {

    pv4n_redirect(
        'security-error',
        $sid
    );
}


$principalId =
    strtolower(
        trim(
            (string)(
                $session['principalId']
                ??
                ''
            )
        )
    );


if (
    !preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
        $principalId
    )
) {

    pv4n_redirect(
        'security-error',
        $sid
    );
}


$notes =
    (string)(
        $_POST['notes']
        ??
        ''
    );


if (
    function_exists(
        'mb_strlen'
    )
) {

    $length =
        mb_strlen(
            $notes,
            'UTF-8'
        );

}
else {

    $length =
        strlen(
            $notes
        );
}


if ($length > 20000) {

    pv4n_redirect(
        'too-long',
        $sid
    );
}


$notes =
    str_replace(
        "\0",
        '',
        $notes
    );


$dir =
    dirname(__DIR__) . '/private/profile-notes';


if (
    !is_dir(
        $dir
    )
) {

    @mkdir(
        $dir,
        0700,
        true
    );
}


$file =
    $dir .
    '/' .
    $principalId .
    '.txt';


$temp =
    $file .
    '.tmp-' .
    bin2hex(
        random_bytes(6)
    );


if (
    @file_put_contents(
        $temp,
        $notes,
        LOCK_EX
    )
    ===
    false
) {

    pv4n_redirect(
        'db-error',
        $sid
    );
}


if (
    !@rename(
        $temp,
        $file
    )
) {

    @unlink(
        $temp
    );


    pv4n_redirect(
        'db-error',
        $sid
    );
}


$_SESSION[
    'cc_profile_notes_csrf'
] =
    bin2hex(
        random_bytes(32)
    );


pv4n_redirect(
    'notes-saved',
    $sid
);