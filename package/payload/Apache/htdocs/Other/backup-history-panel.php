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



.control-hub-button{

    display:flex;
    align-items:center;
    justify-content:center;

    min-height:36px;
    width:100%;

    border:1px solid rgba(233,181,58,.52);

    border-radius:8px;

    color:#f4c75f;

    background:
        linear-gradient(
            180deg,
            rgba(126,87,17,.28),
            rgba(63,43,9,.26)
        );

    text-decoration:none;

    font-size:10px;
    font-weight:900;

    letter-spacing:.09em;
}

.control-hub-button:hover{

    border-color:#efbd4d;

    background:
        linear-gradient(
            180deg,
            rgba(153,105,19,.38),
            rgba(77,51,9,.34)
        );
}

.control-hub-button.disabled{

    opacity:.40;
    cursor:default;
    pointer-events:none;
    filter:grayscale(.35);
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



.control-hub-button{

    display:flex;
    align-items:center;
    justify-content:center;

    min-height:36px;
    width:100%;

    border:1px solid rgba(233,181,58,.52);

    border-radius:8px;

    color:#f4c75f;

    background:
        linear-gradient(
            180deg,
            rgba(126,87,17,.28),
            rgba(63,43,9,.26)
        );

    text-decoration:none;

    font-size:10px;
    font-weight:900;

    letter-spacing:.09em;
}

.control-hub-button:hover{

    border-color:#efbd4d;

    background:
        linear-gradient(
            180deg,
            rgba(153,105,19,.38),
            rgba(77,51,9,.34)
        );
}

.control-hub-button.disabled{

    opacity:.40;
    cursor:default;
    pointer-events:none;
    filter:grayscale(.35);
}

</style>

</head>
<body class="control-panel-page">
<div class="shell">
  <?php
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "BACKUP HISTORY";
$siteHeaderButton = "BACK TO ADMIN HOME";
$siteHeaderLink = "/Other/admin-home.php";
require_once __DIR__ . "/includes/site-header.php";
?>
<style id="backup-history-header-fix">

/* BACKUP HISTORY HEADER ALIGNMENT */

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
<section class="panel admin dash-span-8" id="panel-history">
    <div class="panel-title">
      <span>GRID OWNER — BACKUP HISTORY</span>
      <button type="button" class="control-hub-button" id="history-refresh">REFRESH</button>
    </div>
    <p class="backup-help">Activity record for OAR and IAR backups. CLEAR HISTORY resets this displayed history without deleting the actual backup files.</p>
    <div class="history-toolbar">
      <input type="search" id="history-search" placeholder="Search region, avatar or filename…">
      <select id="history-type"><option value="ALL">ALL TYPES</option><option value="OAR">OAR</option><option value="IAR">IAR</option></select>
      <select id="history-status"><option value="ALL">ALL RESULTS</option><option value="SUCCESS">SUCCESS</option><option value="FAILED">FAILED</option><option value="SKIPPED">SKIPPED</option></select>
      <select id="history-source"><option value="ALL">ALL SOURCES</option><option value="MANUAL">MANUAL</option><option value="SCHEDULED">SCHEDULED</option><option value="IMPORTED">IMPORTED</option></select>
      <button type="button" class="control-hub-button" id="history-clear">CLEAR HISTORY</button>
    </div>
    <div id="history-summary" class="history-summary">Loading backup history…</div>
    <div id="backup-history" class="history-list"><div class="loading">Loading backup history…</div></div>
  </section>

  <style id="backup-history-clean-layout-v1">

#panel-history,
#panel-backup-files {
    min-width: 0;
    overflow: hidden;
}

#panel-history .panel-title,
#panel-backup-files .panel-title {
    align-items: center;
    gap: 14px;
}

.history-toolbar {
    display: grid;
    grid-template-columns:
        minmax(240px, 1.4fr)
        minmax(130px, .7fr)
        minmax(140px, .7fr)
        minmax(140px, .7fr)
        auto;
    gap: 10px;
    align-items: center;
    margin-top: 14px;
}

.backup-tools {
    display: grid;
    grid-template-columns:
        minmax(240px, 1.4fr)
        minmax(150px, .75fr)
        minmax(150px, .75fr);
    gap: 10px;
    align-items: center;
    margin-top: 14px;
}

.history-toolbar input,
.history-toolbar select,
.backup-tools input,
.backup-tools select {
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

.history-summary {
    margin-top: 12px;
    padding: 11px 13px;
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 10px;
    background: rgba(255,255,255,.035);
}

.history-list,
#backup-files {
    margin-top: 12px;
    min-width: 0;
}

.backup-selection-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 12px;
    padding: 11px 12px;
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 10px;
    background: rgba(255,255,255,.025);
}

.backup-selection-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.backup-selection-stats {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-left: auto;
}

.backup-selection-stat {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    min-height: 32px;
    padding: 0 10px;
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 8px;
    background: rgba(0,0,0,.16);
    white-space: nowrap;
}

#backup-delete-selected {
    border-color: rgba(255,110,110,.45);
}

@media (max-width: 1100px) {
    .history-toolbar {
        grid-template-columns: 1fr 1fr;
    }

    .history-toolbar #history-search {
        grid-column: 1 / -1;
    }

    .backup-tools {
        grid-template-columns: 1fr 1fr;
    }

    .backup-tools #backup-search {
        grid-column: 1 / -1;
    }
}

@media (max-width: 650px) {
    .history-toolbar,
    .backup-tools {
        grid-template-columns: 1fr;
    }

    .history-toolbar #history-search,
    .backup-tools #backup-search {
        grid-column: auto;
    }

    .backup-selection-bar {
        align-items: stretch;
    }

    .backup-selection-actions {
        width: 100%;
    }

    .backup-selection-actions .control-hub-button {
        flex: 1 1 145px;
    }

    .backup-selection-stats {
        width: 100%;
        margin-left: 0;
    }

    .backup-selection-stat {
        flex: 1 1 auto;
        justify-content: center;
    }
}

</style>

<section class="panel admin dash-span-8" id="panel-backup-files">
    <div class="panel-title">
        <span><?= $level >= 200 ? 'BACKUP FILES - GRID OWNER' : 'MY BACKUP FILES' ?></span>
        <button type="button" class="control-hub-button" id="refresh-backups">REFRESH</button>
    </div>

    <p class="backup-help"><?= $level >= 200
        ? 'Recent panel-created IARs and grid OARs. Download files directly from the server.'
        : 'Your IAR backups created by this panel. Only your signed-in account can download them.' ?></p>

    <div class="backup-tools">
        <input
            id="backup-search"
            type="search"
            placeholder="Search backups..."
            autocomplete="off"
            aria-label="Search backup files">

        <select id="backup-filter" aria-label="Filter backup type">
            <option value="all">ALL FILES</option>
            <option value="OAR">OAR ONLY</option>
            <option value="IAR">IAR ONLY</option>
        </select>

        <select id="backup-sort" aria-label="Sort backups">
            <option value="newest">NEWEST FIRST</option>
            <option value="oldest">OLDEST FIRST</option>
            <option value="largest">LARGEST FIRST</option>
            <option value="smallest">SMALLEST FIRST</option>
        </select>
    </div>

    <div class="backup-selection-bar">
        <div class="backup-selection-actions">
            <button type="button" class="control-hub-button" id="backup-select-visible">
                SELECT VISIBLE
            </button>

            <button type="button" class="control-hub-button" id="backup-select-old">
                SELECT OLD
            </button>

            <button type="button" class="control-hub-button" id="backup-clear-selected">
                CLEAR SELECTION
            </button>

            <button type="button" class="control-hub-button" id="backup-delete-selected">
                DELETE SELECTED
            </button>
        </div>

        <div class="backup-selection-stats">
            <span class="backup-selection-stat">
                <strong id="backup-selected-count">0</strong>
                SELECTED
            </span>

            <span class="backup-selection-stat" id="backup-selected-size">
                0 B
            </span>
        </div>
    </div>

    <div id="backup-files" class="backup-files-list">
        <div class="loading">Loading backup files...</div>
    </div>
</section>

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
      <button type="button" class="dg-modal-x" id="dg-modal-x" aria-label="Close">×</button>
    </div>
    <div id="dg-modal-body" class="dg-modal-body"></div>
    <div id="dg-modal-options" class="dg-modal-options" hidden></div>
    <div class="dg-modal-actions">
      <button type="button" class="control-hub-button" id="dg-modal-cancel">CANCEL</button>
      <button type="button" class="control-hub-button" id="dg-modal-confirm">CONTINUE</button>
    </div>
  </section>
</div>


<script>
document.addEventListener('DOMContentLoaded', function(){
    loadBackupHistory();
});
</script>
<script src="/Other/australia-panel.js?v=20260826-history-v2"></script>
<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>
</html>






















