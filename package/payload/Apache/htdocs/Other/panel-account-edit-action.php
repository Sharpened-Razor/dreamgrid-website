<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/database.php';

$session =
    ag_require_admin();

ag_no_cache();

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !==
    'POST'
) {
    http_response_code(405);
    header('Allow: POST');
    exit('POST required.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


function cae_same_origin(): bool
{
    $fetchSite =
        strtolower(
            trim(
                (string)(
                    $_SERVER['HTTP_SEC_FETCH_SITE'] ??
                    ''
                )
            )
        );

    if ($fetchSite === 'same-origin') {
        return true;
    }

    $host =
        strtolower(
            trim(
                (string)(
                    $_SERVER['HTTP_HOST'] ??
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
                    $_SERVER['HTTPS'] ??
                    ''
                )
            )
        );

    $scheme =
        (
            $https !== '' &&
            $https !== 'off' &&
            $https !== '0'
        )
            ? 'https'
            : 'http';

    $expected =
        $scheme .
        '://' .
        $host;

    $expectedAlt =
        preg_replace(
            '/:(80|443)$/',
            '',
            $expected
        );

    $origin =
        strtolower(
            rtrim(
                trim(
                    (string)(
                        $_SERVER['HTTP_ORIGIN'] ??
                        ''
                    )
                ),
                '/'
            )
        );

    if ($origin !== '') {
        return
            $origin === $expected ||
            $origin === $expectedAlt;
    }

    $referer =
        strtolower(
            trim(
                (string)(
                    $_SERVER['HTTP_REFERER'] ??
                    ''
                )
            )
        );

    if ($referer !== '') {

        return
            str_starts_with(
                $referer,
                $expected . '/'
            )
            ||
            str_starts_with(
                $referer,
                $expectedAlt . '/'
            );
    }

    return false;
}


function cae_redirect(
    string $result,
    string $sid = ''
): void
{
    $url =
        '/Other/panel-account-edit.php?result=' .
        rawurlencode($result);

    if ($sid !== '') {
        $url .=
            '&sid=' .
            rawurlencode($sid);
    }

    header(
        'Location: ' . $url,
        true,
        303
    );

    exit;
}


$sid =
    trim(
        (string)(
            $_POST['sid'] ??
            ''
        )
    );


if (!cae_same_origin()) {
    cae_redirect(
        'security-error',
        $sid
    );
}


$expectedCsrf =
    (string)(
        $_SESSION['cc_account_csrf'] ??
        ''
    );

$postedCsrf =
    trim(
        (string)(
            $_POST['csrf'] ??
            ''
        )
    );

if (
    $expectedCsrf === '' ||
    $postedCsrf === '' ||
    !hash_equals(
        $expectedCsrf,
        $postedCsrf
    )
) {
    cae_redirect(
        'security-error',
        $sid
    );
}


$principalId =
    trim(
        (string)(
            $session['principalId'] ??
            ''
        )
    );

if ($principalId === '') {
    cae_redirect(
        'security-error',
        $sid
    );
}


$operation =
    trim(
        (string)(
            $_POST['operation'] ??
            ''
        )
    );

$con =
    ag_db_connect();

if (!$con) {
    cae_redirect(
        'db-error',
        $sid
    );
}

mysqli_set_charset(
    $con,
    'utf8mb4'
);


/*
 * ============================================================
 * EMAIL
 * ============================================================
 */

if ($operation === 'email') {

    $email =
        trim(
            (string)(
                $_POST['email'] ??
                ''
            )
        );

    if (
        $email !== '' &&
        filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        ) === false
    ) {
        mysqli_close($con);

        cae_redirect(
            'bad-email',
            $sid
        );
    }

    $stmt =
        @mysqli_prepare(
            $con,
            'UPDATE UserAccounts
             SET Email = ?
             WHERE PrincipalID = ?
             LIMIT 1'
        );

    if (!$stmt) {

        mysqli_close($con);

        cae_redirect(
            'db-error',
            $sid
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ss',
        $email,
        $principalId
    );

    $ok =
        @mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    cae_redirect(
        $ok
            ? 'email-ok'
            : 'db-error',
        $sid
    );
}


/*
 * ============================================================
 * PASSWORD
 * ============================================================
 */

if ($operation === 'password') {

    $currentPassword =
        (string)(
            $_POST['current_password'] ??
            ''
        );

    $newPassword =
        (string)(
            $_POST['new_password'] ??
            ''
        );

    $confirmPassword =
        (string)(
            $_POST['confirm_password'] ??
            ''
        );

    if ($newPassword !== $confirmPassword) {

        mysqli_close($con);

        cae_redirect(
            'password-mismatch',
            $sid
        );
    }

    if (
        strlen($newPassword) < 8 ||
        strlen($newPassword) > 128
    ) {

        mysqli_close($con);

        cae_redirect(
            'password-short',
            $sid
        );
    }

    $stmt =
        @mysqli_prepare(
            $con,
            'SELECT passwordHash, passwordSalt
             FROM auth
             WHERE UUID = ?
             LIMIT 1'
        );

    if (!$stmt) {

        mysqli_close($con);

        cae_redirect(
            'db-error',
            $sid
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        's',
        $principalId
    );

    $storedHash = '';
    $storedSalt = '';

    if (@mysqli_stmt_execute($stmt)) {

        mysqli_stmt_bind_result(
            $stmt,
            $storedHash,
            $storedSalt
        );

        mysqli_stmt_fetch($stmt);
    }

    mysqli_stmt_close($stmt);

    $storedHash =
        strtolower(
            trim((string)$storedHash)
        );

    $storedSalt =
        trim((string)$storedSalt);

    if (
        $storedHash === '' ||
        $storedSalt === ''
    ) {

        mysqli_close($con);

        cae_redirect(
            'auth-missing',
            $sid
        );
    }

    $currentHash =
        md5(
            md5($currentPassword) .
            ':' .
            $storedSalt
        );

    if (
        !hash_equals(
            $storedHash,
            strtolower($currentHash)
        )
    ) {

        mysqli_close($con);

        cae_redirect(
            'bad-current-password',
            $sid
        );
    }

    $newSalt =
        md5(
            bin2hex(
                random_bytes(32)
            )
        );

    $newHash =
        md5(
            md5($newPassword) .
            ':' .
            $newSalt
        );

    $stmt =
        @mysqli_prepare(
            $con,
            'UPDATE auth
             SET passwordHash = ?,
                 passwordSalt = ?
             WHERE UUID = ?
             LIMIT 1'
        );

    if (!$stmt) {

        mysqli_close($con);

        cae_redirect(
            'db-error',
            $sid
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sss',
        $newHash,
        $newSalt,
        $principalId
    );

    $ok =
        @mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    if ($ok) {

        $_SESSION['cc_account_csrf'] =
            bin2hex(
                random_bytes(32)
            );
    }

    cae_redirect(
        $ok
            ? 'password-ok'
            : 'db-error',
        $sid
    );
}


mysqli_close($con);

cae_redirect(
    'security-error',
    $sid
);