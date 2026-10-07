<?php
require_once __DIR__ . '/core/grid-branding.php';



/* ============================================================
   ADMIN SESSION TRACE V2
   TEMPORARY DIAGNOSTIC ONLY
   ============================================================ */

$agTraceCookie =
    (string)(
        $_COOKIE['dg_session'] ??
        ''
    );

$agTraceRawCookie =
    (string)(
        $_SERVER['HTTP_COOKIE'] ??
        ''
    );

$agTraceCookieCount =
    preg_match_all(
        '/(?:^|;\s*)dg_session=/',
        $agTraceRawCookie
    );

$agTraceCookieHash =
    $agTraceCookie !== ''
        ? substr(
            hash(
                'sha256',
                $agTraceCookie
            ),
            0,
            16
        )
        : 'NONE';

$agTraceSessionValid =
    false;

$agTraceSessionLevel =
    'NA';

$agTraceSessionFile =
    __DIR__ .
    '/login/session.php';

if(
    is_file(
        $agTraceSessionFile
    )
){

    require_once
        $agTraceSessionFile;

    if(
        function_exists(
            'dreamGridCurrentSession'
        )
    ){

        $agTraceSession =
            dreamGridCurrentSession();

        if(
            is_array(
                $agTraceSession
            )
        ){

            $agTraceSessionValid =
                true;

            $agTraceSessionLevel =
                (string)(
                    $agTraceSession['level'] ??
                    'NA'
                );
        }
    }
}

$agTraceSecretFile =
    __DIR__ .
    '/login/session_secret.php';

$agTraceSecretInfo =
    is_file(
        $agTraceSecretFile
    )
        ? (
            'mtime=' .
            (string)filemtime(
                $agTraceSecretFile
            ) .
            ' hash=' .
            substr(
                hash_file(
                    'sha256',
                    $agTraceSecretFile
                ),
                0,
                16
            )
        )
        : 'MISSING';

$agTraceLine =
    date('Y-m-d H:i:s') .
    ' page=' .
    basename(
        (string)(
            $_SERVER['SCRIPT_NAME'] ??
            ''
        )
    ) .
    ' uri=' .
    (string)(
        $_SERVER['REQUEST_URI'] ??
        ''
    ) .
    ' host=' .
    (string)(
        $_SERVER['HTTP_HOST'] ??
        ''
    ) .
    ' dg_present=' .
    (
        $agTraceCookie !== ''
            ? 'YES'
            : 'NO'
    ) .
    ' dg_len=' .
    strlen(
        $agTraceCookie
    ) .
    ' dg_hash=' .
    $agTraceCookieHash .
    ' dg_count=' .
    (string)$agTraceCookieCount .
    ' signed_valid=' .
    (
        $agTraceSessionValid
            ? 'YES'
            : 'NO'
    ) .
    ' signed_level=' .
    $agTraceSessionLevel .
    ' secret_' .
    $agTraceSecretInfo .
    PHP_EOL;

@file_put_contents(
    __DIR__ .
    '/_admin-session-trace.log',
    $agTraceLine,
    FILE_APPEND |
    LOCK_EX
);

/* END ADMIN SESSION TRACE V2 */


require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/icons.php';

$session =
    ag_require_admin();





$level =
    ag_user_level(
        $session
    );


$avatar =
    ag_avatar_name(
        $session
    );


header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


/* AUSTRALIA ADMIN SID CONTINUITY */

$agAdminSid =
    trim(
        (string)(
            $_GET['sid'] ??
            ''
        )
    );

$agAdminSidPattern =
    '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';

if(
    $agAdminSid !== '' &&
    preg_match(
        $agAdminSidPattern,
        $agAdminSid
    )
){

    setcookie(
        'ag_admin_sid',
        $agAdminSid,
        [
            'expires'  => 0,
            'path'     => '/Other/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );

}else{

    $agAdminSid =
        trim(
            (string)(
                $_COOKIE['ag_admin_sid'] ??
                ''
            )
        );

    if(
        $agAdminSid === '' ||
        !preg_match(
            $agAdminSidPattern,
            $agAdminSid
        )
    ){
        $agAdminSid = '';
    }
}

$agAdminSidSuffix =
    $agAdminSid !== ''
        ? '/?sid=' . rawurlencode($agAdminSid)
        : '';
?>

<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Admin Dashboard</title>

<link
    rel="stylesheet"
    href="/Other/assets/css/admin-command-center-v2.css?v=20260910-230644">

</head>


<body>


<div class="cc-shell" id="top">


<!-- ======================================================
     ONE LEFT MENU
     ====================================================== -->

<aside class="cc-sidebar">


    <div class="cc-brand">

        <div class="cc-brand-icon">

            <?=ag_icon(
                'dashboard',
                null,
                'cc-brand-svg'
            )?>

        </div>


        <div>

            <div class="cc-brand-main">
                OpenSimulator
            </div>

            <div class="cc-brand-sub">
                Control Panel
            </div>

        </div>

    </div>


    <nav class="cc-nav">

<a class="cc-nav-button active" href="#top">
    <span class="cc-nav-icon">
        <?=ag_icon('dashboard', null, 'cc-nav-svg')?>
    </span>
    <span class="cc-nav-label">Admin Dashboard</span>
</a>
<a class="cc-nav-button" href="/Other/admin-home.php">
    <span class="cc-nav-icon">
        <?=ag_icon('admin-home', null, 'cc-nav-svg')?>
    </span>
    <span class="cc-nav-label">Admin Home</span>
</a>
<a class="cc-nav-button" href="/Other/user-account.php?from=admin">
    <span class="cc-nav-icon">
        <?=ag_icon('account', null, 'cc-nav-svg')?>
    </span>
    <span class="cc-nav-label">My Account</span>
</a>
<a class="cc-nav-button" href="/Other/panel-profile.php">
    <span class="cc-nav-icon">
        <?=ag_icon('avatar', null, 'cc-nav-svg')?>
    </span>
    <span class="cc-nav-label">My Profile</span>
</a>
<a class="cc-nav-button" href="/Other/user-inventory.php?from=admin">
    <span class="cc-nav-icon">
        <?=ag_icon('inventory', null, 'cc-nav-svg')?>
    </span>
    <span class="cc-nav-label">My Inventory</span>
</a>
<a class="cc-nav-button" href="/Other/user-iar-backups.php?from=admin">
    <span class="cc-nav-icon">
        <?=ag_icon('backup', null, 'cc-nav-svg')?>
    </span>
    <span class="cc-nav-label">IAR Backups</span>
</a>
<a class="cc-nav-button" href="/Other/admin-offline-messages.php">
    <span class="cc-nav-icon">
        <?=ag_icon('email', null, 'cc-nav-svg')?>
    </span>
    <span class="cc-nav-label">Offline Messages</span>
</a>
<a class="cc-nav-button" href="/Other/user-linked-regions.php?from=admin">
    <span class="cc-nav-icon">
        <?=ag_icon('link', null, 'cc-nav-svg')?>
    </span>
    <span class="cc-nav-label">Linked Regions</span>
</a>


    </nav>


    <div class="cc-user-box">

        <div class="cc-user-role">

            <?=($level >= 250)
                ? 'GRID OWNER'
                : (($level >= 200)
                    ? 'ADMINISTRATOR'
                    : 'MEMBER')?>

        </div>


        <div class="cc-user-name">

            <?=htmlspecialchars(
                $avatar,
                ENT_QUOTES,
                'UTF-8'
            )?>

        </div>


        <div class="cc-user-level">

            USER LEVEL
            <?=htmlspecialchars(
                (string)$level,
                ENT_QUOTES,
                'UTF-8'
            )?>

        </div>

    </div>


</aside>



<!-- ======================================================
     RIGHT MAIN AREA
     ====================================================== -->

<main class="cc-main">


<!-- HEADER -->

<section class="cc-header">

    <div>

        <div class="cc-kicker">
            <?=ag_grid_name_html()?> · ADMINISTRATION
        </div>

        <h1>
            Admin Command Center
        </h1>

        <p>
            Grid administration and account control.
        </p>

    </div>


    <div class="cc-header-status">

        <strong>
            <?=($level >= 250)
                ? 'GRID OWNER'
                : 'ADMINISTRATOR'?>
        </strong>

        <span>
            USER LEVEL
            <?=htmlspecialchars(
                (string)$level,
                ENT_QUOTES,
                'UTF-8'
            )?>
        </span>

    </div>

</section>



<!-- FIVE SAME SHAPE CARDS -->

<section class="cc-info-grid">


    <div class="cc-info-card">

        <div class="cc-info-icon">
            <?=ag_icon('dashboard', null, 'cc-info-svg')?>
        </div>

        <div class="cc-info-small">
            MODE
        </div>

        <div class="cc-info-value">
            Administration
        </div>

    </div>


    <div class="cc-info-card">

        <div class="cc-info-icon">
            <?=ag_icon('avatar', null, 'cc-info-svg')?>
        </div>

        <div class="cc-info-small">
            ADMINISTRATOR
        </div>

        <div class="cc-info-value">

            <?=htmlspecialchars(
                $avatar,
                ENT_QUOTES,
                'UTF-8'
            )?>

        </div>

    </div>


    <div class="cc-info-card">

        <div class="cc-info-icon">
            <?=ag_icon('account', null, 'cc-info-svg')?>
        </div>

        <div class="cc-info-small">
            USER LEVEL
        </div>

        <div class="cc-info-value">

            <?=htmlspecialchars(
                (string)$level,
                ENT_QUOTES,
                'UTF-8'
            )?>

        </div>

    </div>


    <div class="cc-info-card">

        <div class="cc-info-icon">
            <?=ag_icon('region', null, 'cc-info-svg')?>
        </div>

        <div class="cc-info-small">
            GRID
        </div>

        <div class="cc-info-value">
            <?=ag_grid_name_html()?>
        </div>

    </div>


    <div class="cc-info-card">

        <div class="cc-info-icon">
            <?=ag_icon('console', null, 'cc-info-svg')?>
        </div>

        <div class="cc-info-small">
            PANEL STATUS
        </div>

        <div class="cc-info-value">
            Ready
        </div>

    </div>


</section>



<!-- THREE SAME SHAPE BARS -->

<section class="cc-bars">


    <div class="cc-bar">

        <span class="cc-bar-icon">
            <?=ag_icon('account', null, 'cc-bar-svg')?>
        </span>

        Administrator Session

    </div>


    <div class="cc-bar">

        <span class="cc-bar-icon">
            <?=ag_icon('region', null, 'cc-bar-svg')?>
        </span>

        OpenSimulator Grid

    </div>


    <div class="cc-bar">

        <span class="cc-bar-icon">
            <?=ag_icon('dashboard', null, 'cc-bar-svg')?>
        </span>

        Control Panel Ready

    </div>


</section>



<!-- TWO LARGE MATCHING PANELS -->

<section class="cc-bottom">


    <article class="cc-panel">


        <div class="cc-panel-title">

            <span class="cc-panel-icon">
                <?=ag_icon('console', null, 'cc-panel-svg')?>
            </span>

            Administrative Console

        </div>


        <div class="cc-terminal">


            <div class="cc-terminal-ready">

                Panel ready.
                Select a function from the left navigation.

            </div>


            <div class="cc-terminal-grid">


                <div class="cc-terminal-key">
                    AVATAR
                </div>

                <div class="cc-terminal-value">

                    <?=htmlspecialchars(
                        $avatar,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>

                </div>


                <div class="cc-terminal-key">
                    ACCESS
                </div>

                <div class="cc-terminal-value">

                    <?=($level >= 250)
                        ? 'GRID OWNER'
                        : (($level >= 200)
                            ? 'ADMINISTRATOR'
                            : 'MEMBER')?>

                </div>


                <div class="cc-terminal-key">
                    USER LEVEL
                </div>

                <div class="cc-terminal-value">

                    <?=htmlspecialchars(
                        (string)$level,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>

                </div>


                <div class="cc-terminal-key">
                    GRID
                </div>

                <div class="cc-terminal-value">
                    <?=ag_grid_name_html()?>
                </div>


                <div class="cc-terminal-key">
                    STATUS
                </div>

                <div class="cc-terminal-value">
                    READY
                </div>


            </div>


        </div>


    </article>



    <article class="cc-panel">


        <div class="cc-panel-title">

            <span class="cc-panel-icon">
                <?=ag_icon('dashboard', null, 'cc-panel-svg')?>
            </span>

            Administration Information

        </div>


        <div class="cc-details">


            <div class="cc-detail">

                <div class="cc-detail-label">
                    ACCESS MODE
                </div>

                <div class="cc-detail-value">
                    Administrator
                </div>

            </div>


            <div class="cc-detail">

                <div class="cc-detail-label">
                    GRID
                </div>

                <div class="cc-detail-value">
                    <?=ag_grid_name_html()?>
                </div>

            </div>


            <div class="cc-detail">

                <div class="cc-detail-label">
                    AUTHENTICATION
                </div>

                <div class="cc-detail-value">
                    Active
                </div>

            </div>


            <div class="cc-detail">

                <div class="cc-detail-label">
                    SESSION
                </div>

                <div class="cc-detail-value">
                    Secure
                </div>

            </div>


            <div class="cc-detail">

                <div class="cc-detail-label">
                    USER LEVEL
                </div>

                <div class="cc-detail-value">

                    <?=htmlspecialchars(
                        (string)$level,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>

                </div>

            </div>


            <div class="cc-detail">

                <div class="cc-detail-label">
                    PANEL
                </div>

                <div class="cc-detail-value">
                    Ready
                </div>

            </div>


        </div>


    </article>


</section>



<!-- STATUS BAR -->

<footer class="cc-status">


    <div class="cc-status-left">

        <span class="cc-dot"></span>

        <span>
            Admin panel ready.
        </span>

    </div>


    <div class="cc-status-right">

        <span>
            OpenSimulator
        </span>

        <span>
            <?=ag_grid_name_html()?>
        </span>

        <span>
            ADMIN PANEL
        </span>

        <span id="cc-time">
            --:--
        </span>

    </div>


</footer>


</main>


</div>


<script>
(function(){

    "use strict";


    function clock(){

        const target =
            document.getElementById(
                "cc-time"
            );


        if(!target){
            return;
        }


        target.textContent =
            new Date().toLocaleTimeString(
                [],
                {
                    hour:"2-digit",
                    minute:"2-digit"
                }
            );
    }


    clock();

    setInterval(
        clock,
        10000
    );

})();
</script>


</body>

</html>