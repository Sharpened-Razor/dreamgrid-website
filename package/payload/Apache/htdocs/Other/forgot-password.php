<?php

/*
 * ============================================================
 * Grid - PUBLIC FORGOT PASSWORD V2
 *
 * Browser stays entirely on the PHP Australia website.
 *
 * DreamGrid's native password recovery backend is contacted
 * server-side through the local DreamGrid Robust service.
 *
 * No cURL extension is required.
 * ============================================================
 */

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/dreamgrid-env.php';

ag_no_cache();


if (
    session_status() !== PHP_SESSION_ACTIVE
) {

    session_start();
}


/*
 * ------------------------------------------------------------
 * SAFE HTML OUTPUT
 * ------------------------------------------------------------
 */

function ag_public_h(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


/*
 * ------------------------------------------------------------
 * SEND RESET REQUEST TO LOCAL DREAMGRID / DIVA.WIFI
 *
 * This uses a direct local TCP connection.
 *
 * The native white DreamGrid page is NEVER displayed.
 * ------------------------------------------------------------
 */

function ag_send_native_password_reset(
    string $email
): array {

    if (!function_exists('fsockopen')) {

        return [
            'ok' => false,
            'error' => 'socket_unavailable',
            'status' => 0,
        ];
    }


    $body =
        http_build_query(
            [
                'email' => $email,
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        );


    $errno = 0;

    $errstr = '';


    $socket =
        @fsockopen(
            ag_web_local_host(),
            ag_dg_robust_port(),
            $errno,
            $errstr,
            5
        );


    if (!$socket) {

        return [
            'ok' => false,
            'error' => 'connect_failed',
            'status' => 0,
        ];
    }


    stream_set_timeout(
        $socket,
        12
    );


    $request =
        "POST /wifi/forgotpassword HTTP/1.1\r\n" .
        "Host: " . ag_dg_hop_host() . "\r\n" .
        "Content-Type: application/x-www-form-urlencoded\r\n" .
        "Content-Length: " .
        strlen($body) .
        "\r\n" .
        "User-Agent: AustraliaGrid-PHP-PasswordRecovery/2.0\r\n" .
        "Connection: close\r\n" .
        "\r\n" .
        $body;


    $length =
        strlen($request);


    $written =
        0;


    while ($written < $length) {

        $result =
            @fwrite(
                $socket,
                substr(
                    $request,
                    $written
                )
            );


        if (
            $result === false ||
            $result === 0
        ) {

            fclose($socket);

            return [
                'ok' => false,
                'error' => 'write_failed',
                'status' => 0,
            ];
        }


        $written +=
            $result;
    }


    $response =
        '';


    while (!feof($socket)) {

        $chunk =
            fread(
                $socket,
                8192
            );


        if ($chunk === false) {

            break;
        }


        $response .=
            $chunk;
    }


    $meta =
        stream_get_meta_data(
            $socket
        );


    fclose(
        $socket
    );


    if (
        !empty(
            $meta['timed_out']
        )
    ) {

        return [
            'ok' => false,
            'error' => 'timeout',
            'status' => 0,
        ];
    }


    $status =
        0;


    if (
        preg_match(
            '/^HTTP\/[0-9.]+\s+([0-9]{3})/i',
            $response,
            $match
        )
    ) {

        $status =
            (int)$match[1];
    }


    /*
     * DreamGrid may answer directly with 200 or return a normal
     * redirect into its notification/recovery presentation.
     *
     * Since our PHP page handles presentation itself, any normal
     * HTTP 2xx or 3xx response means the native request was
     * accepted.
     */

    if (
        $status >= 200 &&
        $status < 400
    ) {

        return [
            'ok' => true,
            'error' => '',
            'status' => $status,
        ];
    }


    return [
        'ok' => false,
        'error' => 'http_error',
        'status' => $status,
    ];
}


/*
 * ------------------------------------------------------------
 * CSRF
 * ------------------------------------------------------------
 *
 * The forgot-password form uses a dedicated random SameSite
 * cookie rather than PHP session storage for its CSRF token.
 *
 * This prevents the token from being lost if the PHP session
 * changes between displaying and submitting this public form.
 */

$csrfCookieName =
    'ag_public_forgot_csrf';


function ag_public_forgot_new_csrf(
    string $cookieName
): string {

    $token =
        bin2hex(
            random_bytes(32)
        );


    setcookie(
        $cookieName,
        $token,
        [
            'expires' =>
                time() + 3600,

            'path' =>
                '/Other/',

            'httponly' =>
                true,

            'samesite' =>
                'Lax',
        ]
    );


    /*
     * Make the newly-issued value available during the current
     * request as well. The browser will send the real cookie
     * automatically on the next request.
     */
    $_COOKIE[
        $cookieName
    ] =
        $token;


    return $token;
}


$csrfToken =
    (string)(
        $_COOKIE[
            $csrfCookieName
        ] ??
        ''
    );


if (
    !preg_match(
        '/\A[a-f0-9]{64}\z/D',
        $csrfToken
    )
) {

    $csrfToken =
        ag_public_forgot_new_csrf(
            $csrfCookieName
        );
}

$email = '';

$error = '';

$sent = false;


/*
 * ------------------------------------------------------------
 * HANDLE FORM
 * ------------------------------------------------------------
 */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    ===
    'POST'
) {

    $postedCsrf =
        (string)(
            $_POST['csrf_token'] ??
            ''
        );


    if (
        $postedCsrf === '' ||
        !hash_equals(
            $csrfToken,
            $postedCsrf
        )
    ) {

        $error =
            'Your security token expired. ' .
            'Reload the page and try again.';
    }
    else {

        $email =
            trim(
                (string)(
                    $_POST['email'] ??
                    ''
                )
            );


        if (
            $email === '' ||
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error =
                'Enter the email address registered ' .
                'with your Grid account.';
        }
        elseif (
            strlen($email) > 254
        ) {

            $error =
                'The email address is too long.';
        }
        else {

            $lastRequest =
                (int)(
                    $_SESSION[
                        'ag_public_forgot_last'
                    ] ??
                    0
                );


            if (
                $lastRequest > 0 &&
                (time() - $lastRequest) < 30
            ) {

                $error =
                    'A password reset request was just submitted. ' .
                    'Please wait a moment before trying again.';
            }
            else {

                $native =
                    ag_send_native_password_reset(
                        $email
                    );


                if (!$native['ok']) {

                    $error =
                        'The Grid password recovery ' .
                        'service could not process the request.';
                }
                else {

                    $_SESSION[
                        'ag_public_forgot_last'
                    ] =
                        time();


                    /*
                     * Generic success message deliberately does not
                     * reveal whether an account exists for the email.
                     */

                    $sent =
                        true;


                    $email =
                        '';
                    $csrfToken =
                        ag_public_forgot_new_csrf(
                            $csrfCookieName
                        );
                }
            }
        }
    }
}


/*
 * ============================================================
 * AUSTRALIA FORGOT PASSWORD CONTROLLER BRIDGE V1
 * ============================================================
 *
 * Direct access continues to display the existing standalone
 * page. The front Login shell defines this constant so only
 * the existing password-reset backend runs.
 */

if (
    defined('AUSTRALIA_FORGOT_PASSWORD_CONTROLLER_ONLY') &&
    AUSTRALIA_FORGOT_PASSWORD_CONTROLLER_ONLY
) {
    return;
}

?><!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>
Grid - Forgot Password
</title>


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

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    color:#edf1f3;

    background:
        linear-gradient(
            rgba(2,7,10,.42),
            rgba(2,7,10,.69)
        ),
        url(
            "/Other/custom/Branding/AUSTRALIA-BACKGROUND.png"
        )
        center center /
        cover fixed
        no-repeat;
}


.ag-shell{

    width:
        min(
            900px,
            calc(100% - 32px)
        );

    margin:
        40px auto;
}


.ag-header{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:20px;

    padding:
        20px 24px;

    border:
        1px solid
        rgba(228,172,48,.43);

    border-radius:14px;

    background:
        rgba(5,13,17,.94);

    box-shadow:
        0 18px 50px
        rgba(0,0,0,.46);
}


.ag-eyebrow{

    color:#e9ad34;

    font-size:10px;

    font-weight:900;

    letter-spacing:.16em;
}


.ag-header h1{

    margin:
        5px 0 0;

    color:#fff;

    font-size:
        clamp(
            26px,
            4vw,
            38px
        );
}


.ag-home{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    min-height:40px;

    padding:
        0 16px;

    border:
        1px solid
        rgba(226,169,48,.46);

    border-radius:8px;

    color:#ffd56b;

    background:
        rgba(220,158,32,.07);

    text-decoration:none;

    font-size:10px;

    font-weight:900;
}


.ag-card{

    width:
        min(
            650px,
            100%
        );

    margin:
        20px auto 0;

    padding:
        30px;

    border:
        1px solid
        rgba(223,166,43,.42);

    border-radius:14px;

    background:
        linear-gradient(
            180deg,
            rgba(18,29,34,.97),
            rgba(5,11,14,.98)
        );

    box-shadow:
        0 18px 55px
        rgba(0,0,0,.48);
}


.ag-card h2{

    margin:
        0 0 8px;

    color:#efb33b;

    font-size:24px;
}


.ag-copy{

    margin:
        0 0 22px;

    color:#aebbc1;

    line-height:1.65;

    font-size:13px;
}


.ag-field{

    display:flex;

    flex-direction:column;

    gap:7px;
}


.ag-field label{

    color:#dce5e9;

    font-size:10px;

    font-weight:900;

    letter-spacing:.07em;
}


.ag-field input{

    width:100%;

    height:45px;

    padding:
        0 12px;

    border:
        1px solid
        rgba(174,193,200,.28);

    border-radius:7px;

    outline:none;

    color:#fff;

    background:
        rgba(0,0,0,.38);

    font-size:13px;
}


.ag-field input:focus{

    border-color:#dda42d;

    box-shadow:
        0 0 0 2px
        rgba(221,164,45,.12);
}


.ag-actions{

    display:flex;

    gap:10px;

    margin-top:20px;
}


.ag-button{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    min-height:42px;

    padding:
        0 18px;

    border-radius:7px;

    font-size:10px;

    font-weight:900;

    letter-spacing:.05em;
}


button.ag-button{

    border:
        1px solid
        #e4aa34;

    cursor:pointer;

    color:#171006;

    background:
        linear-gradient(
            #f7c958,
            #d59018
        );
}


a.ag-button{

    border:
        1px solid
        rgba(255,255,255,.14);

    color:#dbe3e7;

    background:
        rgba(255,255,255,.04);

    text-decoration:none;
}


.ag-message{

    margin:
        0 0 20px;

    padding:
        14px 16px;

    border-radius:8px;

    line-height:1.55;

    font-size:12px;
}


.ag-message.success{

    border:
        1px solid
        rgba(70,195,108,.43);

    color:#cff6da;

    background:
        rgba(37,141,74,.14);
}


.ag-message.error{

    border:
        1px solid
        rgba(232,80,80,.43);

    color:#ffd0d0;

    background:
        rgba(173,42,42,.14);
}


.ag-note{

    margin-top:20px;

    padding-top:17px;

    border-top:
        1px solid
        rgba(255,255,255,.09);

    color:#7f8d93;

    font-size:10px;

    line-height:1.55;
}


@media(max-width:650px){

    .ag-shell{
        margin-top:16px;
    }

    .ag-header{
        align-items:flex-start;
        flex-direction:column;
    }

    .ag-card{
        padding:22px;
    }

    .ag-actions{
        flex-direction:column;
    }
}

</style>

<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=20260830-phase3c">
</head>


<body>


<div class="ag-shell">


<header class="ag-header">

    <div>

        <div class="ag-eyebrow">
            Grid
        </div>

        <h1>
            Forgot Password
        </h1>

    </div>


    <?php if (!$sent): ?>

    <a
        class="ag-home"
        href="/Other/index.php"
    >
        HOME
    </a>

    <?php endif; ?>

</header>


<main class="ag-card">


<?php if ($sent): ?>

<div class="ag-message success">

    <strong>
        PASSWORD RESET REQUEST RECEIVED
    </strong>

    <br><br>

    If that email address belongs to an
    Grid account, password reset
    instructions will be sent to it.

    <br><br>

    Check your inbox and your spam/junk folder.

</div>


<div class="ag-actions">

    <a
        class="ag-button"
        href="/Other/index.php"
    >
        CLOSE
    </a>

</div>

<?php else: ?>


<?php if ($error !== ''): ?>

<div class="ag-message error">

    <?=ag_public_h($error)?>

</div>

<?php endif; ?>


<h2>
Reset Your Password
</h2>


<p class="ag-copy">

Enter the email address registered with your
Grid avatar.

You will receive password recovery instructions
at that address.

</p>


<form
    method="post"
    action="/Other/forgot-password.php"
>

<input
    type="hidden"
    name="csrf_token"
    value="<?=ag_public_h($csrfToken)?>"
>


<div class="ag-field">

<label for="email">
ACCOUNT EMAIL
</label>

<input
    id="email"
    name="email"
    type="email"
    maxlength="254"
    autocomplete="email"
    value="<?=ag_public_h($email)?>"
    required
>

</div>


<div class="ag-actions">

<button
    class="ag-button"
    type="submit"
>
SEND RESET INSTRUCTIONS
</button>


<a
    class="ag-button"
    href="/Other/index.php"
>
CANCEL
</a>

</div>

</form>


<div class="ag-note">

For security, Grid does not reveal
whether a particular email address is registered.

</div>


<?php endif; ?>

</main>


</div>


<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>


