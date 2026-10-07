<?php
require_once __DIR__ . '/core/bootstrap.php';
$session =
    ag_require_admin();

$avatar = ag_avatar_name($session);
$level = ag_user_level($session);
if ($level >= 200) {
    $roleName = 'GRID OWNER';
} elseif ($level > 100) {
    $roleName = 'ESTATE MANAGER';
} elseif ($level > 1) {
    $roleName = 'REGION MANAGER';
} else {
    $roleName = 'MEMBER';
}

?>
<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta charset="utf-8">
<title>Control Panel</title>
<link rel="stylesheet" href="/Other/australia-panel.css?v=20260826-ultra-compact-v2">
<!-- AUSTRALIA MASTER 3D THEME V3.1 -->
<link rel="stylesheet" href="/Other/australia-3d-theme.css?v=20260820-022508">

<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">
<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=20260830-phase3c">

<style id="australia-sentinel-sitewide-v3">


/* ============================================================
   AUSTRALIA SENTINEL 3D CARD SYSTEM V3
   SITE WIDE
   ============================================================ */


.dashboard-card,
.control-hub-card,
.stat-card,
.summary-card,
.inventory-card,
.profile-card,
.region-panel,
.section-card,
.manage-card{


position:relative !important;

overflow:hidden !important;


border-radius:15px !important;


border:

2px solid
rgba(210,218,220,.55) !important;


background:

linear-gradient(
145deg,
#4d5559,
#090b0c
) !important;


box-shadow:

inset 0 2px 0
rgba(255,255,255,.25),

inset 0 -22px 35px
rgba(0,0,0,.8),

0 18px 40px
rgba(0,0,0,.75) !important;


}



.dashboard-card:after,
.control-hub-card:after,
.stat-card:after,
.summary-card:after,
.inventory-card:after,
.profile-card:after,
.region-panel:after,
.section-card:after,
.manage-card:after{


content:"";

position:absolute;

inset:7px;

border-radius:10px;

pointer-events:none;


border:

1px solid
rgba(255,193,58,.45);

}



.card-title,
.card-heading,
.panel-title,
.summary-title{


color:#ffd167 !important;

font-weight:900 !important;

text-shadow:

0 2px 5px
rgba(0,0,0,.8);

}



.card-icon,
.card-icon.svg-badge{


background:

linear-gradient(
145deg,
#70777a,
#111415
) !important;


border:

1px solid
rgba(255,255,255,.3) !important;


box-shadow:

inset 0 2px 4px
rgba(255,255,255,.25),

0 8px 18px
rgba(0,0,0,.6);


}



.dashboard-card:hover,
.control-hub-card:hover,
.stat-card:hover,
.summary-card:hover{


transform:
translateY(-3px);


}


</style>


<style id="australia-user-level-badge-v1">

.php-level{

    min-width:128px;

    padding:
        8px 12px;

    text-align:center;

    border:
        1px solid
        rgba(67,151,220,.38);

    border-radius:
        8px;

    background:
        rgba(15,68,104,.22);

    font-size:10px;

    font-weight:800;

}


.php-level strong{

    display:block;

    margin-bottom:2px;

    color:#7bc6ff;

}


</style>

</head>
<body class="control-panel-page">
<div class="shell">
  <?php
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "BACKUP SCHEDULER";
$siteHeaderButton = "BACK TO ADMIN HOME";
$siteHeaderLink = "/Other/admin-home.php";
require_once __DIR__ . "/includes/site-header.php";
?>
<style id="backup-scheduler-header-fix">

.dashboard-heading,
.php-level,
.dashboard-actions {
    position: relative !important;
    z-index: 1 !important;
}

.dashboard-actions {
    position: relative !important;

    right: auto !important;
    top: auto !important;

    transform: none !important;

    display: flex !important;

    gap: 9px;

    flex-wrap: wrap;
    align-items: center;
}

@media (max-width:650px) {

    .dashboard-header {
        display: block;
    }

    .php-level,
    .dashboard-actions {
        margin-top: 16px;
    }
}

</style>

  <main class="dashboard-grid">

<?php if ($level >= 200): ?>

<section class="panel admin dash-span-12" id="panel-scheduler">

    <div class="panel-title">
        <span>GRID OWNER - BACKUP SCHEDULER</span>

        <button
            type="button"
            id="schedule-refresh">
            REFRESH
        </button>
    </div>


    <p class="backup-help">
        Configure automatic OAR backups for selected running regions.
        Scheduled backups run through the Windows background scheduler
        even when this browser is closed.
    </p>


    <div class="schedule-grid">

        <label class="schedule-field schedule-enable">

            <input
                type="checkbox"
                id="schedule-enabled">

            ENABLE AUTOMATIC BACKUPS

        </label>


        <label class="schedule-field">

            SCHEDULE

            <select id="schedule-type">

                <option value="daily">
                    DAILY
                </option>

                <option value="weekly">
                    WEEKLY
                </option>

                <option value="selected">
                    SELECTED DAYS
                </option>

            </select>

        </label>


        <label class="schedule-field">

            TIME

            <input
                type="time"
                id="schedule-time"
                value="02:00">

        </label>


        <label class="schedule-field">

            KEEP LAST

            <select id="schedule-keep">

                <option value="1">1 BACKUP</option>
                <option value="2">2 BACKUPS</option>
                <option value="3" selected>3 BACKUPS</option>
                <option value="5">5 BACKUPS</option>
                <option value="7">7 BACKUPS</option>
                <option value="10">10 BACKUPS</option>
                <option value="15">15 BACKUPS</option>
                <option value="20">20 BACKUPS</option>

            </select>

        </label>

    </div>


    <div
        id="schedule-days"
        class="schedule-days">

        <label>
            <input type="checkbox" value="0">
            SUN
        </label>

        <label>
            <input type="checkbox" value="1">
            MON
        </label>

        <label>
            <input type="checkbox" value="2">
            TUE
        </label>

        <label>
            <input type="checkbox" value="3">
            WED
        </label>

        <label>
            <input type="checkbox" value="4">
            THU
        </label>

        <label>
            <input type="checkbox" value="5">
            FRI
        </label>

        <label>
            <input type="checkbox" value="6">
            SAT
        </label>

    </div>


    <div class="schedule-subtitle">
        REGIONS TO BACK UP
    </div>


    <div class="schedule-toolbar">

        <button
            type="button"
            id="schedule-select-all">
            SELECT ALL ONLINE
        </button>

        <button
            type="button"
            id="schedule-clear">
            CLEAR
        </button>

        <span id="schedule-region-count">
            0 selected
        </span>

    </div>


    <div
        id="schedule-regions"
        class="schedule-regions">

        <div class="loading">
            Loading regions...
        </div>

    </div>


    <div class="schedule-actions">

        <button
            type="button"
            class="gold"
            id="schedule-save">
            SAVE SCHEDULE
        </button>

        <button
            type="button"
            id="schedule-run-now">
            RUN SELECTED NOW
        </button>

    </div>


    <div
        id="schedule-status"
        class="iar-panel-status"
        hidden>
    </div>


    <!--
        Hidden compatibility bridge.

        australia-panel.js currently uses the proven Grid Backup
        engine for RUN SELECTED NOW. These elements keep that engine
        working without displaying the old Grid Backup panel.
    -->

    <div hidden aria-hidden="true">

        <div id="grid-backup-regions"></div>

        <div
            id="grid-backup-summary"
            class="iar-panel-status">
        </div>

    </div>

</section>


<?php else: ?>

<section class="panel dash-span-12">

    <div class="panel-title">
        <span>BACKUP SCHEDULER</span>
    </div>

    <p class="backup-help">
        Grid Owner access is required to manage scheduled backups.
    </p>

</section>

<?php endif; ?>


<div
    id="result"
    class="result dash-span-12">
    Scheduler ready.
</div>

</main>
</div>
<script>
window.DG = {
  avatar: <?=json_encode($avatar)?>,
  level: <?=json_encode($level)?>
};
</script>
<div id="dg-modal" class="dg-modal" hidden aria-hidden="true">
  <div class="dg-modal-backdrop" data-modal-cancel></div>
  <section class="dg-modal-card" role="dialog" aria-modal="true" aria-labelledby="dg-modal-title">
    <div class="dg-modal-head">
      <div>
        <div class="dg-modal-kicker">CONTROL PANEL</div>
        <h2 id="dg-modal-title">CONFIRM ACTION</h2>
      </div>
      <button type="button" class="dg-modal-x" id="dg-modal-x" aria-label="Close">Ã—</button>
    </div>
    <div id="dg-modal-body" class="dg-modal-body"></div>
    <div id="dg-modal-options" class="dg-modal-options" hidden></div>
    <div class="dg-modal-actions">
      <button type="button" id="dg-modal-cancel">CANCEL</button>
      <button type="button" class="gold" id="dg-modal-confirm">CONTINUE</button>
    </div>
  </section>
</div>

<script src="/Other/australia-panel.js?v=20260826-history-v2"></script>
<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>
</html>









