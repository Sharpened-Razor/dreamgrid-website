<?php

require_once __DIR__ . '/core/bootstrap.php';

$session =
    ag_current_session();

if (!$session) {

    header(
        'Location: login.php'
    );

    exit;
}


$level =
    function_exists(
        'ag_user_level'
    )
        ? (int)ag_user_level($session)
        : (int)($session['level'] ?? 0);


if ($level < 200) {

    http_response_code(403);

    exit(
        'Grid Owner access required.'
    );
}


ag_no_cache();


$cssVersion =
    is_file(__DIR__ . '/stats.css')
        ? (string)filemtime(__DIR__ . '/stats.css')
        : '1';


$jsVersion =
    is_file(__DIR__ . '/stats.js')
        ? (string)filemtime(__DIR__ . '/stats.js')
        : '1';

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>
    Grid Stats
</title>

<link
    rel="stylesheet"
    href="stats.css?v=<?=htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8')?>">


<!-- AUSTRALIA STATS GAUGES V2 CSS -->
<link
    rel="stylesheet"
    href="stats-gauges.css?v=<?=is_file(__DIR__ . '/stats-gauges.css') ? (int)filemtime(__DIR__ . '/stats-gauges.css') : 1?>">
<!-- AUSTRALIA STATS GAUGES V2 CSS END -->


<!-- AUSTRALIA TRUE LIVE GAUGES V5 CSS -->
<link
    rel="stylesheet"
    href="stats-live-gauges.css?v=<?=is_file(__DIR__ . '/stats-live-gauges.css') ? (int)filemtime(__DIR__ . '/stats-live-gauges.css') : 1?>">
<!-- AUSTRALIA TRUE LIVE GAUGES V5 CSS END -->


<!-- AUSTRALIA GAUGE HISTORY V1 CSS -->
<link
    rel="stylesheet"
    href="stats-gauge-history.css?v=<?=is_file(__DIR__ . '/stats-gauge-history.css') ? (int)filemtime(__DIR__ . '/stats-gauge-history.css') : 1?>">
<!-- AUSTRALIA GAUGE HISTORY V1 CSS END -->


<!-- AUSTRALIA ACTIVE REGION V1 CSS -->
<link
    rel="stylesheet"
    href="stats-active-region.css?v=<?=is_file(__DIR__ . '/stats-active-region.css') ? (int)filemtime(__DIR__ . '/stats-active-region.css') : 1?>">
<!-- AUSTRALIA ACTIVE REGION V1 CSS END -->


<!-- AUSTRALIA GRID SERVICES V1 CSS -->
<link
    rel="stylesheet"
    href="stats-grid-services.css?v=<?=is_file(__DIR__ . '/stats-grid-services.css') ? (int)filemtime(__DIR__ . '/stats-grid-services.css') : 1?>">
<!-- AUSTRALIA GRID SERVICES V1 CSS END -->


<!-- AUSTRALIA PERFORMANCE HISTORY V1 CSS -->
<link
    rel="stylesheet"
    href="stats-history.css?v=<?=is_file(__DIR__ . '/stats-history.css') ? (int)filemtime(__DIR__ . '/stats-history.css') : 1?>">
<!-- AUSTRALIA PERFORMANCE HISTORY V1 CSS END -->
</head>


<body>

<div class="stats-shell">


    <!-- =====================================================
         HERO
         ===================================================== -->

    <section class="stats-hero">

        <div>

            <div class="stats-kicker">
                AUSTRALIA CONTROL CENTER
            </div>

            <h1>
                GRID HEALTH &amp; STATISTICS
            </h1>

            <p>
                Live server, OpenSim, regions, backups, network and storage telemetry.
            </p>

        </div>


        <div class="hero-actions">

            <div
                id="live-state"
                class="live-state">

                <span class="live-dot"></span>

                LIVE

            </div>


            <button
                id="refresh-button"
                class="gold-button"
                type="button">

                REFRESH ALL

            </button>

        </div>

    </section>



    <!-- =====================================================
         SUMMARY
         ===================================================== -->

    <section class="summary-grid">

        <article class="summary-card">

            <div class="summary-label">
                GRID
            </div>

            <div
                id="summary-grid"
                class="summary-value">

                CHECKING

            </div>

            <div
                id="summary-grid-detail"
                class="summary-detail">

                Reading region state

            </div>

        </article>


        <article class="summary-card">

            <div class="summary-label">
                REGIONS
            </div>

            <div
                id="summary-regions"
                class="summary-value">

                —

            </div>

            <div
                id="summary-regions-detail"
                class="summary-detail">

                Online regions

            </div>

        </article>


        <article class="summary-card">

            <div class="summary-label">
                SIM FPS
            </div>

            <div
                id="summary-fps"
                class="summary-value">

                —

            </div>

            <div class="summary-detail">
                Grid average
            </div>

        </article>


        <article class="summary-card">

            <div class="summary-label">
                CPU
            </div>

            <div
                id="summary-cpu"
                class="summary-value">

                —

            </div>

            <div class="summary-detail">
                Server load
            </div>

        </article>


        <article class="summary-card">

            <div class="summary-label">
                BACKUPS
            </div>

            <div
                id="summary-backups"
                class="summary-value">

                —

            </div>

            <div
                id="summary-backups-detail"
                class="summary-detail">

                Scheduler
            </div>

        </article>


        <article class="summary-card">

            <div class="summary-label">
                DRIVE FREE
            </div>

            <div
                id="summary-storage"
                class="summary-value">

                —

            </div>

            <div
                id="summary-storage-detail"
                class="summary-detail">

                Storage available
            </div>

        </article>

    </section>



    <!-- =====================================================
         MAIN THREE-COLUMN GRID
         ===================================================== -->

    <main class="panel-grid">


        <!-- SERVER LOAD -->

        <section class="stats-panel">

            <header class="panel-head">

                <div>

                    <div class="panel-kicker">
                        HOST
                    </div>

                    <h2>
                        SERVER LOAD
                    </h2>

                </div>

                <span class="panel-live">
                    LIVE
                </span>

            </header>


            <div
                id="server-load"
                class="panel-body">

                <div class="loading">
                    Reading server...
                </div>

            </div>

        </section>



        <!-- OPENSIM PERFORMANCE -->

        <section class="stats-panel">

            <header class="panel-head">

                <div>

                    <div class="panel-kicker">
                        OPENSIM
                    </div>

                    <h2>
                        PERFORMANCE
                    </h2>

                </div>

                <span class="panel-live">
                    LIVE
                </span>

            </header>


            <div
                id="opensim-performance"
                class="panel-body">

                <div class="loading">
                    Reading OpenSim telemetry...
                </div>

            </div>

        </section>



        <!-- REGION HEALTH -->

        <section class="stats-panel">

            <header class="panel-head">

                <div>

                    <div class="panel-kicker">
                        GRID
                    </div>

                    <h2>
                        REGION HEALTH
                    </h2>

                </div>

                <span class="panel-live">
                    LIVE
                </span>

            </header>


            <div
                id="region-health"
                class="panel-body">

                <div class="loading">
                    Analysing regions...
                </div>

            </div>

        </section>



        <!-- NETWORK + SERVICES -->

        <section class="stats-panel">

            <header class="panel-head">

                <div>

                    <div class="panel-kicker">
                        CONNECTIVITY
                    </div>

                    <h2>
                        NETWORK &amp; SERVICES
                    </h2>

                </div>

            </header>


            <div
                id="network-services"
                class="panel-body">

                <div class="loading">
                    Checking services...
                </div>

            </div>

        </section>



        <!-- BACKUP ACTIVITY -->

        <section class="stats-panel">

            <header class="panel-head">

                <div>

                    <div class="panel-kicker">
                        AUTOMATION
                    </div>

                    <h2>
                        BACKUP ACTIVITY
                    </h2>

                </div>

            </header>


            <div
                id="backup-activity"
                class="panel-body">

                <div class="loading">
                    Reading backup system...
                </div>

            </div>

        </section>



        <!-- STORAGE -->

        <section class="stats-panel">

            <header class="panel-head">

                <div>

                    <div class="panel-kicker">
                        DISK
                    </div>

                    <h2>
                        STORAGE BREAKDOWN
                    </h2>

                </div>

            </header>


            <div
                id="storage-breakdown"
                class="panel-body">

                <div class="loading">
                    Calculating storage...
                </div>

            </div>

        </section>



        <!-- =================================================
             REGION TABLE
             ================================================= -->

        <section class="stats-panel panel-full">

            <header class="panel-head">

                <div>

                    <div class="panel-kicker">
                        LIVE REGIONS
                    </div>

                    <h2>
                        REGION PERFORMANCE TABLE
                    </h2>

                </div>


                <div
                    id="region-table-count"
                    class="panel-note">

                    —

                </div>

            </header>


            <div class="table-wrap">

                <table class="regions-table">

                    <thead>

                        <tr>

                            <th>
                                REGION
                            </th>

                            <th>
                                STATUS
                            </th>

                            <th class="number">
                                AVATARS
                            </th>

                            <th class="number">
                                PRIMS
                            </th>

                            <th class="number">
                                SIM FPS
                            </th>

                            <th class="number">
                                PHYSICS FPS
                            </th>

                            <th class="number">
                                FRAME MS
                            </th>

                            <th class="number">
                                SCRIPTS
                            </th>

                            <th class="number">
                                SCRIPT EPS
                            </th>

                        </tr>

                    </thead>


                    <tbody id="region-table-body">

                        <tr>

                            <td
                                colspan="9"
                                class="table-loading">

                                Loading region telemetry...

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </section>



        <!-- ALERTS -->

        <section class="stats-panel panel-half">

            <header class="panel-head">

                <div>

                    <div class="panel-kicker">
                        ATTENTION
                    </div>

                    <h2>
                        ALERTS
                    </h2>

                </div>


                <span
                    id="alert-count"
                    class="alert-count">

                    0

                </span>

            </header>


            <div
                id="alerts"
                class="panel-body">

                <div class="loading">
                    Checking health rules...
                </div>

            </div>

        </section>



        <!-- EVENTS -->

        <section class="stats-panel panel-half">

            <header class="panel-head">

                <div>

                    <div class="panel-kicker">
                        ACTIVITY
                    </div>

                    <h2>
                        RECENT EVENTS
                    </h2>

                </div>

            </header>


            <div
                id="recent-events"
                class="panel-body">

                <div class="loading">
                    Building recent activity...
                </div>

            </div>

        </section>



        <!-- ERRORS -->

        <section class="stats-panel panel-full">

            <header class="panel-head">

                <div>

                    <div class="panel-kicker">
                        DIAGNOSTICS
                    </div>

                    <h2>
                        RECENT IMPORTANT ERRORS
                    </h2>

                </div>

            </header>


            <div
                id="recent-errors"
                class="panel-body">

                <div class="loading">
                    Reading recent logs...
                </div>

            </div>

        </section>


    </main>



    <footer class="stats-footer">

        <span>
            LAST REFRESH
        </span>

        <strong id="last-refresh">
            —
        </strong>

    </footer>


</div>


<script
    src="stats.js?v=<?=htmlspecialchars($jsVersion, ENT_QUOTES, 'UTF-8')?>"></script>


<!-- AUSTRALIA STATS GAUGES V2 JS -->
<script
    src="stats-gauges.js?v=<?=is_file(__DIR__ . '/stats-gauges.js') ? (int)filemtime(__DIR__ . '/stats-gauges.js') : 1?>"></script>
<!-- AUSTRALIA STATS GAUGES V2 JS END -->





<!-- AUSTRALIA TRUE LIVE GAUGES V5 JS -->
<script
    src="stats-live-gauges.js?v=<?=is_file(__DIR__ . '/stats-live-gauges.js') ? (int)filemtime(__DIR__ . '/stats-live-gauges.js') : 1?>"></script>
<!-- AUSTRALIA TRUE LIVE GAUGES V5 JS END -->


<!-- AUSTRALIA GAUGE HISTORY V1 JS -->
<script
    src="stats-gauge-history.js?v=<?=is_file(__DIR__ . '/stats-gauge-history.js') ? (int)filemtime(__DIR__ . '/stats-gauge-history.js') : 1?>"></script>
<!-- AUSTRALIA GAUGE HISTORY V1 JS END -->


<!-- AUSTRALIA ACTIVE REGION V1 JS -->
<script
    src="stats-active-region.js?v=<?=is_file(__DIR__ . '/stats-active-region.js') ? (int)filemtime(__DIR__ . '/stats-active-region.js') : 1?>"></script>
<!-- AUSTRALIA ACTIVE REGION V1 JS END -->


<!-- AUSTRALIA GRID SERVICES V1 JS -->
<script
    src="stats-grid-services.js?v=<?=is_file(__DIR__ . '/stats-grid-services.js') ? (int)filemtime(__DIR__ . '/stats-grid-services.js') : 1?>"></script>
<!-- AUSTRALIA GRID SERVICES V1 JS END -->


<!-- AUSTRALIA PERFORMANCE HISTORY V1 JS -->
<script
    src="stats-history.js?v=<?=is_file(__DIR__ . '/stats-history.js') ? (int)filemtime(__DIR__ . '/stats-history.js') : 1?>"></script>
<!-- AUSTRALIA PERFORMANCE HISTORY V1 JS END -->
</body>

</html>