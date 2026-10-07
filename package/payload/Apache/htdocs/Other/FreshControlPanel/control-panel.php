<?php
require_once dirname(__DIR__) . '/core/grid-branding.php';


require_once dirname(__DIR__) . '/core/bootstrap.php';

ag_no_cache();

$session = ag_require_admin();

$avatar = ag_avatar_name($session);
$level  = ag_user_level($session);

if (
    session_status() !==
    PHP_SESSION_ACTIVE
) {
    @session_start();
}

if (
    empty(
        $_SESSION[
            'australia_user_iar_csrf'
        ]
    )
) {

    $_SESSION[
        'australia_user_iar_csrf'
    ] =
        bin2hex(
            random_bytes(32)
        );
}

$iarCsrf =
    (string)
    $_SESSION[
        'australia_user_iar_csrf'
    ];

$canPrivilegedIar =
    function_exists(
        'ag_can_use_privileged_user_tools'
    )
    ?
    ag_can_use_privileged_user_tools(
        $session
    )
    :
    (
        $level >= 50
    );


function agcp_asset_version(
    string $file
): string {

    $path =
        __DIR__ .
        DIRECTORY_SEPARATOR .
        $file;

    if (!is_file($path)) {
        return '1';
    }

    $mtime =
        filemtime($path);

    return
        $mtime !== false
        ?
        (string) $mtime
        :
        '1';
}
?>
<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title><?=ag_grid_name_html()?> Control Panel</title>

<link
    rel="stylesheet"
    href="/Other/FreshControlPanel/control-panel.css?v=<?=htmlspecialchars(agcp_asset_version('control-panel.css'), ENT_QUOTES, 'UTF-8')?>">

<link rel="stylesheet" href="/Other/FreshControlPanel/grid-backups.css?v=<?=htmlspecialchars(agcp_asset_version('grid-backups.css'), ENT_QUOTES, 'UTF-8')?>">
<link rel="stylesheet" href="/Other/FreshControlPanel/backup-scheduler.css?v=<?=htmlspecialchars(agcp_asset_version('backup-scheduler.css'), ENT_QUOTES, 'UTF-8')?>">
<link rel="stylesheet" href="/Other/FreshControlPanel/backup-storage.css?v=<?=htmlspecialchars(agcp_asset_version('backup-storage.css'), ENT_QUOTES, 'UTF-8')?>">
<link rel="stylesheet" href="/Other/FreshControlPanel/backup-history.css?v=<?=htmlspecialchars(agcp_asset_version('backup-history.css'), ENT_QUOTES, 'UTF-8')?>">
<link rel="stylesheet" href="/Other/FreshControlPanel/backup-files-health.css?v=<?=htmlspecialchars(agcp_asset_version('backup-files-health.css'), ENT_QUOTES, 'UTF-8')?>">
<link rel="stylesheet" href="/Other/FreshControlPanel/restore-user-iar.css?v=<?=htmlspecialchars(agcp_asset_version('restore-user-iar.css'), ENT_QUOTES, 'UTF-8')?>">
<link rel="stylesheet" href="/Other/FreshControlPanel/my-iar-backups.css?v=<?=htmlspecialchars(agcp_asset_version('my-iar-backups.css'), ENT_QUOTES, 'UTF-8')?>">
</head>

<body>

<div class="agcp-app">

    <aside class="agcp-sidebar">

        <div class="agcp-brand">

            <div class="agcp-brand-kicker">
                <?=ag_grid_name_html()?>
            </div>

            <div class="agcp-brand-title">
                CONTROL PANEL
            </div>

            <div class="agcp-brand-user">
                GRID OWNER
                <strong>
                    <?=htmlspecialchars(
                        $avatar,
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    )?>
                </strong>
            </div>

        </div>

        <div class="agcp-nav-group">
            GRID
        </div>

        <button
            class="agcp-nav active"
            data-page="grid-view"
            type="button">

            <span class="agcp-nav-icon">
                <img
                    src="/Other/assets/icons/sentinel/dashboard.png"
                    alt="">
            </span>

            <span>GRID VIEW</span>

        </button>

        <button
            class="agcp-nav"
            data-page="grid-control"
            type="button">

            <span class="agcp-nav-icon">
                <img
                    src="/Other/assets/icons/sentinel/control-panel.png"
                    alt="">
            </span>

            <span>GRID CONTROL</span>

        </button>

        <button
            class="agcp-nav"
            data-page="grid-alerts"
            type="button">

            <span class="agcp-nav-icon">
                <img
                    src="/Other/assets/icons/sentinel/alert.png"
                    alt="">
            </span>

            <span>GRID ALERTS</span>

        </button>

        <button
            class="agcp-nav"
            data-page="grid-backups"
            type="button">

            <span class="agcp-nav-icon">
                <img
                    src="/Other/assets/icons/sentinel/backup.png"
                    alt="">
            </span>

            <span>GRID BACKUPS</span>

        </button>


        <div class="agcp-nav-group">
            BACKUPS
        </div>

        <button
            class="agcp-nav"
            data-page="backup-scheduler"
            type="button">

            <span class="agcp-nav-icon">
                <img
                    src="/Other/assets/icons/sentinel/calendar.png"
                    alt="">
            </span>

            <span>BACKUP SCHEDULER</span>

        </button>

        <button
            class="agcp-nav"
            data-page="backup-storage"
            type="button">

            <span class="agcp-nav-icon">
                <img
                    src="/Other/assets/icons/sentinel/database.png"
                    alt="">
            </span>

            <span>BACKUP STORAGE</span>

        </button>

        <button
            class="agcp-nav"
            data-page="backup-history"
            type="button">

            <span class="agcp-nav-icon">
                <img
                    src="/Other/assets/icons/sentinel/files.png"
                    alt="">
            </span>

            <span>BACKUP HISTORY</span>

        </button>

        <button
            class="agcp-nav"
            data-page="backup-files"
            type="button">

            <span class="agcp-nav-icon">
                <img
                    src="/Other/assets/icons/sentinel/files.png"
                    alt="">
            </span>

            <span>BACKUP FILES</span>

        </button>

        <button
            class="agcp-nav"
            data-page="backup-health"
            type="button">

            <span class="agcp-nav-icon">
                <img
                    src="/Other/assets/icons/sentinel/alert.png"
                    alt="">
            </span>

            <span>BACKUP HEALTH</span>

        </button>

        <button
            class="agcp-nav"
            data-page="restore-history"
            type="button">

            <span class="agcp-nav-icon">
                <img
                    src="/Other/assets/icons/sentinel/backup.png"
                    alt="">
            </span>

            <span>RESTORE HISTORY</span>

        </button>


        <div class="agcp-nav-group">
            INVENTORY
        </div>

        <button
            class="agcp-nav"
            data-page="user-iar"
            type="button">

            <span class="agcp-nav-icon">
                <img
                    src="/Other/assets/icons/sentinel/users.png"
                    alt="">
            </span>

            <span>USER IAR BACKUPS</span>

        </button>

        <button
            class="agcp-nav"
            data-page="my-iar"
            type="button">

            <span class="agcp-nav-icon">
                <img
                    src="/Other/assets/icons/sentinel/inventory.png"
                    alt="">
            </span>

            <span>MY IAR BACKUPS</span>

        </button>

    </aside>


    <main class="agcp-main">

        <!-- ==================================================
             GRID VIEW
             ================================================== -->

        <section
            id="agcp-page-grid-view"
            class="agcp-page active">

            <div class="agcp-titlebar">

                <div>

                    <div class="agcp-title-kicker">
                        GRID
                    </div>

                    <h1>
                        Grid View
                    </h1>

                </div>

                <button
                    id="agcp-grid-refresh"
                    class="agcp-button agcp-button-gold"
                    type="button">
                    REFRESH
                </button>

            </div>

            <div class="agcp-panel">

                <div class="agcp-panel-head">

                    <div>

                        <div class="agcp-panel-kicker">
                            DASHBOARD
                        </div>

                        <div class="agcp-panel-title">
                            Grid Status
                        </div>

                    </div>

                    <div
                        id="agcp-grid-state"
                        class="agcp-status-pill">
                        CHECKING
                    </div>

                </div>

                <div class="agcp-summary">

                    <div class="agcp-summary-item">

                        <span>
                            TOTAL REGIONS
                        </span>

                        <strong id="agcp-total-regions">
                            -
                        </strong>

                    </div>

                    <div class="agcp-summary-item">

                        <span>
                            RUNNING
                        </span>

                        <strong
                            id="agcp-running-regions"
                            class="good">
                            -
                        </strong>

                    </div>

                    <div class="agcp-summary-item">

                        <span>
                            STOPPED
                        </span>

                        <strong id="agcp-stopped-regions">
                            -
                        </strong>

                    </div>

                    <div class="agcp-summary-item">

                        <span>
                            BACKUP HEALTH
                        </span>

                        <strong id="agcp-backup-health">
                            -
                        </strong>

                    </div>

                </div>

            </div>


            <div class="agcp-two-column">

                <div class="agcp-panel">

                    <div class="agcp-panel-head">

                        <div>

                            <div class="agcp-panel-kicker">
                                GRID
                            </div>

                            <div class="agcp-panel-title">
                                Live Regions
                            </div>

                        </div>

                    </div>

                    <div
                        id="agcp-region-list"
                        class="agcp-region-list">

                        <div class="agcp-empty">
                            Loading regions...
                        </div>

                    </div>

                </div>


                <div class="agcp-panel">

                    <div class="agcp-panel-head">

                        <div>

                            <div class="agcp-panel-kicker">
                                BACKUPS
                            </div>

                            <div class="agcp-panel-title">
                                Backup Summary
                            </div>

                        </div>

                    </div>

                    <div
                        id="agcp-backup-summary"
                        class="agcp-detail-list">

                        <div class="agcp-empty">
                            Loading backup health...
                        </div>

                    </div>

                </div>

            </div>

            <div
                id="agcp-grid-message"
                class="agcp-message">
                Control Panel ready.
            </div>

        </section>


        <!-- ==================================================
             CLEAN EMPTY SECTION CONTAINERS
             These are NEW pages, not old-page imports.
             ================================================== -->

        <section
    id="agcp-page-grid-control"
    class="agcp-page">

    <div class="agcp-titlebar">

        <div>
            <div class="agcp-title-kicker">
                GRID
            </div>

            <h1>
                Grid Control
            </h1>
        </div>

        <div class="agcp-title-actions">

            <button
                id="agcp-control-select-all"
                class="agcp-button"
                type="button">
                SELECT ALL
            </button>

            <button
                id="agcp-control-clear"
                class="agcp-button"
                type="button">
                CLEAR
            </button>

            <button
                id="agcp-control-refresh"
                class="agcp-button agcp-button-gold"
                type="button">
                REFRESH
            </button>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    LIVE DREAMGRID
                </div>

                <div class="agcp-panel-title">
                    Region Control
                </div>
            </div>

            <div
                id="agcp-control-state"
                class="agcp-status-pill">
                READY
            </div>

        </div>


        <div class="agcp-summary">

            <div class="agcp-summary-item">
                <span>TOTAL REGIONS</span>
                <strong id="agcp-control-total">-</strong>
            </div>

            <div class="agcp-summary-item">
                <span>RUNNING</span>
                <strong
                    id="agcp-control-running"
                    class="good">
                    -
                </strong>
            </div>

            <div class="agcp-summary-item">
                <span>STOPPED / OTHER</span>
                <strong id="agcp-control-other">-</strong>
            </div>

            <div class="agcp-summary-item">
                <span>SELECTED</span>
                <strong id="agcp-control-selected">0</strong>
            </div>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    COMMANDS
                </div>

                <div class="agcp-panel-title">
                    Selected Regions
                </div>
            </div>

        </div>

        <div class="agcp-command-bar">

            <button
                id="agcp-control-start"
                class="agcp-button"
                type="button"
                disabled>
                START
            </button>

            <button
                id="agcp-control-stop"
                class="agcp-button"
                type="button"
                disabled>
                STOP
            </button>

            <button
                id="agcp-control-restart"
                class="agcp-button"
                type="button"
                disabled>
                RESTART
            </button>

            <button
                id="agcp-control-freeze"
                class="agcp-button"
                type="button"
                disabled>
                FREEZE
            </button>

            <button
                id="agcp-control-thaw"
                class="agcp-button"
                type="button"
                disabled>
                THAW
            </button>

            <div class="agcp-command-spacer"></div>

            <button
                id="agcp-control-restart-all"
                class="agcp-button agcp-button-danger"
                type="button">
                RESTART ALL
            </button>

        </div>

        <div
            id="agcp-control-message"
            class="agcp-message">
            Grid Control ready.
        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    REGIONS
                </div>

                <div class="agcp-panel-title">
                    Live Region List
                </div>
            </div>

        </div>

        <div class="agcp-control-head">
            <span></span>
            <span>REGION</span>
            <span>STATUS</span>
            <span>OWNER</span>
            <span>AVATARS</span>
            <span>PRIMS</span>
        </div>

        <div
            id="agcp-control-list"
            class="agcp-control-list">

            <div class="agcp-empty">
                Loading regions...
            </div>

        </div>

    </div>

</section>


        <section
    id="agcp-page-grid-alerts"
    class="agcp-page">

    <div class="agcp-titlebar">

        <div>
            <div class="agcp-title-kicker">
                MONITORING
            </div>

            <h1>
                Grid Alerts
            </h1>
        </div>

        <div class="agcp-title-actions">

            <span class="agcp-live-marker">
                LIVE - 60 SEC
            </span>

            <button
                id="agcp-alerts-refresh"
                class="agcp-button agcp-button-gold"
                type="button">
                REFRESH
            </button>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    GRID WATCH
                </div>

                <div class="agcp-panel-title">
                    Monitoring Summary
                </div>
            </div>

            <div
                id="agcp-alerts-state"
                class="agcp-status-pill">
                CHECKING
            </div>

        </div>


        <div class="agcp-summary">

            <div class="agcp-summary-item">
                <span>CRITICAL</span>
                <strong
                    id="agcp-alerts-critical"
                    class="bad">
                    0
                </strong>
            </div>

            <div class="agcp-summary-item">
                <span>WARNINGS</span>
                <strong
                    id="agcp-alerts-warning"
                    class="warn">
                    0
                </strong>
            </div>

            <div class="agcp-summary-item">
                <span>REGIONS ONLINE</span>
                <strong id="agcp-alerts-regions">-</strong>
            </div>

            <div class="agcp-summary-item">
                <span>LAST CHECK</span>
                <strong id="agcp-alerts-last-check">-</strong>
            </div>

        </div>

    </div>


    <div class="agcp-two-column">

        <div class="agcp-panel">

            <div class="agcp-panel-head">

                <div>
                    <div class="agcp-panel-kicker">
                        SYSTEM
                    </div>

                    <div class="agcp-panel-title">
                        System Watch
                    </div>
                </div>

            </div>

            <div class="agcp-watch-list">

                <div class="agcp-watch-row">
                    <div>
                        <span>REGIONS</span>
                        <small id="agcp-watch-regions-copy">
                            Checking regions...
                        </small>
                    </div>

                    <strong
                        id="agcp-watch-regions"
                        class="agcp-watch-value">
                        -
                    </strong>
                </div>


                <div class="agcp-watch-row">
                    <div>
                        <span>BACKUP HEALTH</span>
                        <small id="agcp-watch-health-copy">
                            Checking backup health...
                        </small>
                    </div>

                    <strong
                        id="agcp-watch-health"
                        class="agcp-watch-value">
                        -
                    </strong>
                </div>


                <div class="agcp-watch-row">
                    <div>
                        <span>SCHEDULER</span>
                        <small id="agcp-watch-scheduler-copy">
                            Checking scheduler...
                        </small>
                    </div>

                    <strong
                        id="agcp-watch-scheduler"
                        class="agcp-watch-value">
                        -
                    </strong>
                </div>


                <div class="agcp-watch-row">
                    <div>
                        <span>STORAGE</span>
                        <small id="agcp-watch-storage-copy">
                            Checking backup storage...
                        </small>
                    </div>

                    <strong
                        id="agcp-watch-storage"
                        class="agcp-watch-value">
                        -
                    </strong>
                </div>


                <div class="agcp-watch-row">
                    <div>
                        <span>BACKUP ERRORS</span>
                        <small id="agcp-watch-errors-copy">
                            Checking errors...
                        </small>
                    </div>

                    <strong
                        id="agcp-watch-errors"
                        class="agcp-watch-value">
                        -
                    </strong>
                </div>

            </div>

        </div>


        <div class="agcp-panel">

            <div class="agcp-panel-head">

                <div>
                    <div class="agcp-panel-kicker">
                        ATTENTION
                    </div>

                    <div class="agcp-panel-title">
                        Active Alerts
                    </div>
                </div>

                <div
                    id="agcp-alerts-active"
                    class="agcp-status-pill">
                    0 ACTIVE
                </div>

            </div>

            <div
                id="agcp-alert-list"
                class="agcp-alert-list">

                <div class="agcp-empty">
                    Checking monitored systems...
                </div>

            </div>

        </div>

    </div>

</section>


        <?php require __DIR__ . '/sections/grid-backups.php'; ?>


        <?php require __DIR__ . '/sections/backup-scheduler.php'; ?>


        <?php require __DIR__ . '/sections/backup-storage.php'; ?>


        <?php require __DIR__ . '/sections/backup-history.php'; ?>


        <?php require __DIR__ . '/sections/backup-files.php'; ?>


        <?php require __DIR__ . '/sections/backup-health.php'; ?>


        <?php require __DIR__ . '/sections/restore-history.php'; ?>


        <?php require __DIR__ . '/sections/user-iar-backups.php'; ?>


        <?php require __DIR__ . '/sections/my-iar-backups.php'; ?>

    </main>

</div>


<div
    id="agcp-confirm-modal"
    class="agcp-modal"
    aria-hidden="true">

    <div class="agcp-modal-shade"></div>

    <div
        class="agcp-modal-box"
        role="dialog"
        aria-modal="true">

        <div class="agcp-modal-head">

            <div>
                <div class="agcp-modal-kicker">
                    CONTROL PANEL
                </div>

                <div
                    id="agcp-confirm-title"
                    class="agcp-modal-title">
                    Confirm Action
                </div>
            </div>

            <button
                id="agcp-confirm-close"
                class="agcp-modal-close"
                type="button">
                X
            </button>

        </div>

        <div class="agcp-modal-body">

            <div
                id="agcp-confirm-message"
                class="agcp-modal-message">
            </div>

            <div
                id="agcp-confirm-detail"
                class="agcp-modal-detail">
            </div>

        </div>

        <div class="agcp-modal-actions">

            <button
                id="agcp-confirm-cancel"
                class="agcp-button"
                type="button">
                CANCEL
            </button>

            <button
                id="agcp-confirm-ok"
                class="agcp-button agcp-button-gold"
                type="button">
                CONTINUE
            </button>

        </div>

    </div>

</div>

<script>
window.AGCP = {
    avatar: <?=json_encode(
        $avatar,
        JSON_UNESCAPED_SLASHES
    )?>,

    level: <?=json_encode(
        $level
    )?>,

    iarCsrf: <?=json_encode(
        $iarCsrf,
        JSON_UNESCAPED_SLASHES
    )?>,

    canPrivilegedIar: <?=json_encode(
        $canPrivilegedIar
    )?>
};
</script>

<script
    src="/Other/FreshControlPanel/control-panel.js?v=<?=htmlspecialchars(agcp_asset_version('control-panel.js'), ENT_QUOTES, 'UTF-8')?>">
</script>

<script src="/Other/FreshControlPanel/grid-backups.js?v=<?=htmlspecialchars(agcp_asset_version('grid-backups.js'), ENT_QUOTES, 'UTF-8')?>"></script>
<script src="/Other/FreshControlPanel/backup-scheduler.js?v=<?=htmlspecialchars(agcp_asset_version('backup-scheduler.js'), ENT_QUOTES, 'UTF-8')?>"></script>
<script src="/Other/FreshControlPanel/backup-storage.js?v=<?=htmlspecialchars(agcp_asset_version('backup-storage.js'), ENT_QUOTES, 'UTF-8')?>"></script>
<script src="/Other/FreshControlPanel/backup-history.js?v=<?=htmlspecialchars(agcp_asset_version('backup-history.js'), ENT_QUOTES, 'UTF-8')?>"></script>
<script src="/Other/FreshControlPanel/backup-files-health.js?v=<?=htmlspecialchars(agcp_asset_version('backup-files-health.js'), ENT_QUOTES, 'UTF-8')?>"></script>
<script src="/Other/FreshControlPanel/restore-user-iar.js?v=<?=htmlspecialchars(agcp_asset_version('restore-user-iar.js'), ENT_QUOTES, 'UTF-8')?>"></script>
<script src="/Other/FreshControlPanel/my-iar-backups.js?v=<?=htmlspecialchars(agcp_asset_version('my-iar-backups.js'), ENT_QUOTES, 'UTF-8')?>"></script>
</body>
</html>