<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/bootstrap.php';
require_once dirname(__DIR__) . '/core/icons.php';

ag_no_cache();

$session = ag_current_session();

if (!$session) {
    header('Location: /Other/login.php');
    exit;
}

$adminPreview =
    ag_is_admin($session) &&
    isset($_GET['preview']) &&
    (string)$_GET['preview'] === 'user';

if (
    ag_is_admin($session) &&
    !$adminPreview
) {
    ag_redirect(
        ag_route('admin_dashboard')
    );
}

$avatar =
    function_exists('ag_avatar_name')
        ? trim(
            (string)ag_avatar_name(
                $session
            )
        )
        : trim(
            (string)(
                $session['avatar'] ??
                ''
            )
        );

if ($avatar === '') {
    $avatar = 'GRID USER';
}

$level =
    function_exists('ag_user_level')
        ? (int)ag_user_level(
            $session
        )
        : (int)(
            $session['level'] ??
            1
        );

function udx_e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function udx_asset_version(string $relative): string
{
    $base = dirname(__DIR__);

    $path =
        $base .
        DIRECTORY_SEPARATOR .
        str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            ltrim($relative, '/')
        );

    return is_file($path)
        ? (string)filemtime($path)
        : '1';
}

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>
User Control Center
</title>

<!-- EXACT LIVE ADMIN CONTROL CENTER THEME -->
<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-shell-v1.css?v=<?= udx_e(udx_asset_version('assets/css/control-center-shell-v1.css')) ?>">

<!-- LAYOUT ONLY - NO THEME COLOURS -->
<link
    rel="stylesheet"
    href="/Other/FreshUserDashboardExact/user-dashboard-layout.css?v=<?= udx_e(udx_asset_version('FreshUserDashboardExact/user-dashboard-layout.css')) ?>">

</head>

<body>


<div class="cc-shell">


<!-- ========================================================
     USER SIDEBAR
     EXACT ADMIN SHELL CLASS SYSTEM
     ======================================================== -->

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

            <div class="cc-grid-name">

                <?php if (function_exists('ag_grid_name_html')): ?>

                    <?=ag_grid_name_html()?>

                <?php else: ?>

                    GRID

                <?php endif; ?>

            </div>


            <div class="cc-brand-sub">
                CONTROL CENTER
            </div>

        </div>

    </div>



    <section class="cc-menu-section">

        <div class="cc-menu-heading">
            DASHBOARD
        </div>


        <button
            type="button"
            class="cc-nav-button active"
            id="udx-dashboard-button"
            data-title="User Control Center">

            <span class="cc-nav-icon">

                <?=ag_icon(
                    'dashboard',
                    null,
                    'cc-nav-svg'
                )?>

            </span>

            <span class="cc-nav-label">
                DASHBOARD
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Account"
            data-view="account"
            data-src="/Other/FreshUserDashboardExact/UserPages/account.php">

            <span class="cc-nav-icon">

                <?=ag_icon(
                    'account',
                    null,
                    'cc-nav-svg'
                )?>

            </span>

            <span class="cc-nav-label">
                ACCOUNT
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Profile"
            data-view="profile"
            data-src="/Other/FreshUserDashboardExact/UserPages/profile.php">

            <span class="cc-nav-icon">

                <?=ag_icon(
                    'avatar',
                    null,
                    'cc-nav-svg'
                )?>

            </span>

            <span class="cc-nav-label">
                PROFILE
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Regions"
            data-view="regions"
            data-src="/Other/FreshUserDashboardExact/UserPages/regions.php">

            <span class="cc-nav-icon">

                <?=ag_icon(
                    'region',
                    null,
                    'cc-nav-svg'
                )?>

            </span>

            <span class="cc-nav-label">
                REGIONS
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Inventory"
            data-view="inventory"
            data-src="/Other/FreshUserDashboardExact/UserPages/inventory.php">

            <span class="cc-nav-icon">

                <?=ag_icon(
                    'inventory',
                    null,
                    'cc-nav-svg'
                )?>

            </span>

            <span class="cc-nav-label">
                INVENTORY
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="IAR Backups"
            data-view="iar-backups"
            data-src="/Other/FreshUserDashboardExact/UserPages/iar-backups.php">

            <span class="cc-nav-icon">

                <?=ag_icon(
                    'backup',
                    null,
                    'cc-nav-svg'
                )?>

            </span>

            <span class="cc-nav-label">
                IAR BACKUPS
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Offline Messages"
            data-view="offline-messages"
            data-src="/Other/FreshUserDashboardExact/UserPages/offline-messages.php?v=20260918-emotes-filtered-v4">

            <span class="cc-nav-icon">

                <?=ag_icon(
                    'email',
                    null,
                    'cc-nav-svg'
                )?>

            </span>

            <span class="cc-nav-label">
                OFFLINE MESSAGES
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Map"
            data-view="map"
            data-src="/Other/FreshUserDashboardExact/UserPages/map.php?ccfit=2&v=20260918-user-layers-v9c">

            <span class="cc-nav-icon">

                <?=ag_icon(
                    'map',
                    null,
                    'cc-nav-svg'
                )?>

            </span>

            <span class="cc-nav-label">
                MAP
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="3D Map"
            data-view="3d-map"
            data-src="/Other/FreshUserDashboardExact/UserPages/map-3d.php?from=user&v=20260918-user-3d-v1">

            <span class="cc-nav-icon">

                <?=ag_icon(
                    'map',
                    null,
                    'cc-nav-svg'
                )?>

            </span>

            <span class="cc-nav-label">
                3D MAP
            </span>

        </button>
<button
            type="button"
            class="cc-nav-button"
            data-title="Help Centre"
            data-view="help-centre"
            data-src="/Other/FreshUserDashboardExact/UserPages/help-centre.php?v=20260918-user-help-v1">

            <span class="cc-nav-icon">

                <?=ag_icon(
                    'help',
                    null,
                    'cc-nav-svg'
                )?>

            </span>

            <span class="cc-nav-label">
                HELP CENTRE
            </span>

        </button>
        <a
            class="cc-nav-button"
            href="/Other/logout.php">

            <span class="cc-nav-icon" aria-hidden="true">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M10 17l5-5-5-5"></path>
                    <path d="M15 12H3"></path>
                    <path d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"></path>
                </svg>
            </span>

            <span class="cc-nav-label">
                LOGOUT
            </span>

        </a>


    </section>


</aside>



<!-- ========================================================
     RIGHT WORKSPACE
     EXACT ADMIN SHELL CLASS SYSTEM
     ======================================================== -->

<section class="cc-workspace">


    <header class="cc-workspace-header">


        <div>

            <div class="cc-header-kicker">

                <?php if (function_exists('ag_grid_name_html')): ?>

                    <?=ag_grid_name_html()?>

                <?php else: ?>

                    GRID

                <?php endif; ?>

            </div>


            <div
                class="cc-workspace-title"
                id="cc-workspace-title">

                User Control Center

            </div>

        </div>


        <div class="cc-header-state">

            <strong>
                READY
            </strong>

            <span>
                USER SESSION
            </span>

        </div>


    </header>



    <div class="udx-workspace-body">


        <!-- =================================================
             USER HOME
             Uses the SAME cc-stat / cc-panel classes
             as the Admin Control Center.
             ================================================= -->

        <div
            id="cc-home"
            class="udx-home">


            <div class="udx-stat-grid">


                <div class="cc-stat">

                    <div class="cc-stat-icon">

                        <?=ag_icon(
                            'avatar',
                            null,
                            'cc-stat-svg'
                        )?>

                    </div>

                    <div class="cc-stat-label">
                        USER
                    </div>

                    <div class="cc-stat-value">
                        <?=udx_e($avatar)?>
                    </div>

                </div>



                <div class="cc-stat">

                    <div class="cc-stat-icon">

                        <?=ag_icon(
                            'account',
                            null,
                            'cc-stat-svg'
                        )?>

                    </div>

                    <div class="cc-stat-label">
                        ACCESS
                    </div>

                    <div class="cc-stat-value">
                        Grid User
                    </div>

                </div>



                <div class="cc-stat">

                    <div class="cc-stat-icon">

                        <?=ag_icon(
                            'region',
                            null,
                            'cc-stat-svg'
                        )?>

                    </div>

                    <div class="cc-stat-label">
                        GRID
                    </div>

                    <div class="cc-stat-value">

                        <?php if (function_exists('ag_grid_name_html')): ?>

                            <?=ag_grid_name_html()?>

                        <?php else: ?>

                            GRID

                        <?php endif; ?>

                    </div>

                </div>



                <div class="cc-stat">

                    <div class="cc-stat-icon">

                        <?=ag_icon(
                            'statistics',
                            null,
                            'cc-stat-svg'
                        )?>

                    </div>

                    <div class="cc-stat-label">
                        USER LEVEL
                    </div>

                    <div class="cc-stat-value">
                        <?= (int)$level ?>
                    </div>

                </div>



                <div class="cc-stat">

                    <div class="cc-stat-icon">

                        <?=ag_icon(
                            'console',
                            null,
                            'cc-stat-svg'
                        )?>

                    </div>

                    <div class="cc-stat-label">
                        STATUS
                    </div>

                    <div class="cc-stat-value">
                        Ready
                    </div>

                </div>


            </div>



            <div class="udx-strip">

                <div class="cc-panel">
                    USER SESSION ACTIVE
                </div>

                <div class="cc-panel">
                    CONTROL CENTER READY
                </div>

                <div class="cc-panel">
                    SECURE ACCESS
                </div>

            </div>



            <div class="udx-panel-grid">


                <article class="cc-panel">


                    <div class="cc-panel-title">
                        User Control Center
                    </div>


                    <div class="udx-console">


                        <div class="cc-console-line">
                            &gt; User control center ready.
                        </div>

                        <div class="cc-console-line">
                            &gt; Select a command from the left menu.
                        </div>

                        <div class="cc-console-line">
                            &gt; The selected tool will open in this workspace.
                        </div>

                        <div class="cc-console-line">
                            &gt; Left navigation remains available at all times.
                        </div>


                    </div>


                </article>



                <article class="cc-panel">


                    <div class="cc-panel-title">
                        Session
                    </div>


                    <div class="cc-info-row">

                        <div class="cc-info-key">
                            USER
                        </div>

                        <div class="cc-info-value">
                            <?=udx_e($avatar)?>
                        </div>

                    </div>


                    <div class="cc-info-row">

                        <div class="cc-info-key">
                            ACCESS
                        </div>

                        <div class="cc-info-value">
                            Grid User
                        </div>

                    </div>


                    <div class="cc-info-row">

                        <div class="cc-info-key">
                            USER LEVEL
                        </div>

                        <div class="cc-info-value">
                            <?= (int)$level ?>
                        </div>

                    </div>


                    <div class="cc-info-row">

                        <div class="cc-info-key">
                            STATUS
                        </div>

                        <div class="cc-info-value">
                            Ready
                        </div>

                    </div>


                </article>


            </div>


        </div>



        <!-- EXISTING USER PAGES OPEN HERE -->

        <iframe
            class="cc-frame"
            id="cc-frame"
            name="cc-frame"
            title="User Control Center Workspace">

        </iframe>


    </div>



    <footer class="cc-status">


        <div class="cc-status-left">

            <span class="cc-status-dot"></span>

            <span id="cc-status-text">
                User Control Center Ready
            </span>

        </div>


        <div id="cc-clock">
            --:--
        </div>


    </footer>


</section>


</div>


<script
    src="/Other/FreshUserDashboardExact/user-dashboard.js?v=<?= udx_e(udx_asset_version('FreshUserDashboardExact/user-dashboard.js')) ?>">
</script>

</body>

</html>