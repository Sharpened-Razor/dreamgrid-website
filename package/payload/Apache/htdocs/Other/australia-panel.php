<?php
require_once __DIR__ . '/core/grid-branding.php';

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
$siteHeaderTitle = "CONTROL PANEL";
$siteHeaderButton = "BACK TO DASHBOARD";
$siteHeaderLink = "/Other/admin-dashboard.php";
require_once __DIR__ . "/includes/site-header.php";
?>

  <nav class="dashboard-nav" aria-label="Control panel sections">
    <a href="#panel-inventory">MY INVENTORY</a>
    <a href="#panel-backup-files">MY FILES</a>
    <?php if ($level >= 200): ?>
      <a href="#panel-overview">GRID OVERVIEW</a>
      <a href="#panel-alerts">ALERTS</a>
      <a href="#panel-scheduler">SCHEDULER</a>
      <a href="#panel-backup-health">HEALTH</a>
      <a href="#panel-region-backup">GRID BACKUP</a>
      <a href="#panel-history">HISTORY</a>
      <a href="#panel-storage">STORAGE</a>
      <a href="#panel-restore-history">RESTORES</a>
    <?php endif; ?>
  </nav>

  <?php if ($level >= 200): ?>
  <section class="grid-overview" id="panel-overview" aria-label="Live grid overview">
    <div class="overview-heading">
      <div>
        <span class="overview-kicker">LIVE GRID OVERVIEW</span>
        <strong>GRID STATUS</strong>
      </div>

<div class="php-level">

    <strong>
        <?=($level >= 200 ? 'GRID OWNER' : 'MEMBER')?>
    </strong>

    USER LEVEL
    <?=ag_h($level)?>

</div>

      <button type="button" id="overview-refresh">REFRESH</button>
    </div>
    <div class="overview-cards">
      <div class="overview-card" id="ov-regions">
        <span class="overview-label">REGIONS</span>
        <strong class="overview-value">â€”</strong>
        <span class="overview-detail">Loadingâ€¦</span>
      </div>
      <div class="overview-card" id="ov-avatars">
        <span class="overview-label">AVATARS ONLINE</span>
        <strong class="overview-value">â€”</strong>
        <span class="overview-detail">Across running regions</span>
      </div>
      <div class="overview-card" id="ov-prims">
        <span class="overview-label">TOTAL PRIMS</span>
        <strong class="overview-value">â€”</strong>
        <span class="overview-detail">Across all regions</span>
      </div>
      <div class="overview-card" id="ov-health">
        <span class="overview-label">BACKUP HEALTH</span>
        <strong class="overview-value">â€”</strong>
        <span class="overview-detail">Checkingâ€¦</span>
      </div>
      <div class="overview-card" id="ov-last-backup">
        <span class="overview-label">LAST SCHEDULED BACKUP</span>
        <strong class="overview-value overview-value-small">â€”</strong>
        <span class="overview-detail">Checkingâ€¦</span>
      </div>
      <div class="overview-card" id="ov-storage">
        <span class="overview-label">BACKUP STORAGE</span>
        <strong class="overview-value">â€”</strong>
        <span class="overview-detail">Free space</span>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($level >= 200): ?>
  <section class="grid-alerts" id="panel-alerts" aria-label="Live grid alerts">
    <div class="grid-alerts-head">
      <div>
        <span class="grid-alerts-kicker">LIVE GRID ALERTS</span>
        <strong id="grid-alerts-title">CHECKING SYSTEMSâ€¦</strong>
      </div>
      <div class="grid-alerts-actions">
        <span id="grid-alerts-lastcheck">Waiting for first checkâ€¦</span>
        <button type="button" id="alerts-refresh">CHECK NOW</button>
      </div>
    </div>
    <div id="grid-alerts-body" class="grid-alerts-body">
      <div class="grid-alert-item neutral">
        <span class="grid-alert-level">CHECKING</span>
        <span class="grid-alert-message">Reading region and backup health informationâ€¦</span>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($level < 200): ?>
  <section class="member-welcome">
    <div>
      <span class="member-welcome-kicker">MY <?=ag_grid_name_html()?></span>
      <strong>Welcome, <?=htmlspecialchars($avatar)?></strong>
      <p>Your dashboard only shows regions, inventory tools and backup files that belong to your signed-in account.</p>
    </div>
    <span class="member-role"><?=htmlspecialchars($roleName)?> Â· LEVEL <?=htmlspecialchars((string)$level)?></span>
  </section>

  <section class="member-access" aria-label="Your dashboard permissions">
    <div class="member-access-card">
      <span>REGIONS</span>
      <strong>OWNED ONLY</strong>
      <small>You only see regions where you are listed as Estate Owner.</small>
    </div>

    <div class="member-access-card">
      <span>REGION CONTROLS</span>
      <strong><?= $level > 1 ? 'START / STOP / RESTART' : 'VIEW ONLY' ?></strong>
      <small><?= $level > 1 ? 'Controls apply only to your own regions.' : 'Your current UserLevel does not permit region controls.' ?></small>
    </div>

    <div class="member-access-card">
      <span>SAVE OAR</span>
      <strong><?= $level > 100 ? 'ALLOWED' : 'NOT ALLOWED' ?></strong>
      <small><?= $level > 100 ? 'You may save OARs for your own regions.' : 'UserLevel above 100 is required for Save OAR.' ?></small>
    </div>

    <div class="member-access-card">
      <span>INVENTORY / FILES</span>
      <strong>YOUR IAR ONLY</strong>
      <small>You may create, view and download only your own inventory archives.</small>
    </div>
  </section>
  <?php endif; ?>

  <main class="dashboard-grid">

  <section class="panel dash-span-3 dash-compact" id="panel-inventory">
    <div class="panel-title"><span>MY INVENTORY</span></div>
    <div class="inventory-row">
      <div><h2>Inventory Archive</h2><p>Create a server-side IAR backup of your inventory.</p></div>
      <button type="button" class="gold" data-command="SaveIAR">SAVE MY IAR</button>
    </div>
    <div id="iar-panel-status" class="iar-panel-status" hidden></div>
  </section>

  <?php if ($level >= 200): ?>
  <section class="panel admin dash-span-9" id="panel-scheduler">
    <div class="panel-title">
      <span>GRID OWNER â€” BACKUP SCHEDULER</span>
      <button type="button" id="schedule-refresh">REFRESH</button>
    </div>
    <p class="backup-help">Configure automatic region backups. V57 uses a Windows background runner so scheduled backups work even when this browser is closed.</p>

    <div class="schedule-grid">
      <label class="schedule-field schedule-enable"><input type="checkbox" id="schedule-enabled"> ENABLE AUTOMATIC BACKUPS</label>

      <label class="schedule-field">SCHEDULE
        <select id="schedule-type">
          <option value="daily">DAILY</option>
          <option value="weekly">WEEKLY</option>
          <option value="selected">SELECTED DAYS</option>
        </select>
      </label>

      <label class="schedule-field">TIME
        <input type="time" id="schedule-time" value="02:00">
      </label>

      <label class="schedule-field">KEEP LAST
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

    <div id="schedule-days" class="schedule-days">
      <label><input type="checkbox" value="0"> SUN</label>
      <label><input type="checkbox" value="1"> MON</label>
      <label><input type="checkbox" value="2"> TUE</label>
      <label><input type="checkbox" value="3"> WED</label>
      <label><input type="checkbox" value="4"> THU</label>
      <label><input type="checkbox" value="5"> FRI</label>
      <label><input type="checkbox" value="6"> SAT</label>
    </div>

    <div class="schedule-subtitle">REGIONS TO BACK UP</div>
    <div class="schedule-toolbar">
      <button type="button" id="schedule-select-all">SELECT ALL ONLINE</button>
      <button type="button" id="schedule-clear">CLEAR</button>
      <span id="schedule-region-count">0 selected</span>
    </div>
    <div id="schedule-regions" class="schedule-regions"><div class="loading">Loading regionsâ€¦</div></div>

    <div class="schedule-actions">
      <button type="button" class="gold" id="schedule-save">SAVE SCHEDULE</button>
      <button type="button" id="schedule-run-now">RUN SELECTED NOW</button>
    </div>

    <div id="schedule-status" class="iar-panel-status" hidden></div>
  </section>
  <?php endif; ?>

  <?php if ($level >= 200): ?>
  <section class="panel admin dash-span-8" id="panel-region-backup">
    <div class="panel-title">
      <span>GRID OWNER â€” REGION BACKUP</span>
      <button type="button" id="grid-backup-refresh">REFRESH REGIONS</button>
    </div>
    <p class="backup-help">Select one or more running regions. V55 backs them up one at a time using the same proven SAVE OAR system.</p>
    <div class="grid-backup-toolbar">
      <button type="button" id="grid-backup-select-all">SELECT ALL ONLINE</button>
      <button type="button" id="grid-backup-clear">CLEAR</button>
      <span id="grid-backup-count">0 selected</span>
      <button type="button" class="gold" id="grid-backup-start" disabled>START SELECTED BACKUPS</button>
    </div>
    <div id="grid-backup-regions" class="grid-backup-regions"><div class="loading">Loading regionsâ€¦</div></div>
    <div id="grid-backup-summary" class="iar-panel-status" hidden></div>
  </section>
  <?php endif; ?>

  <?php if ($level >= 200): ?>
  <section class="panel admin dash-span-4" id="panel-backup-health">
    <div class="panel-title">
      <span>GRID OWNER â€” BACKUP HEALTH</span>
      <button type="button" id="health-refresh">REFRESH</button>
    </div>
    <p class="backup-help">Read-only health checks for the working backup scheduler, recent backup results and backup-drive free space.</p>
    <div id="backup-health-summary" class="backup-health-summary">Checking backup healthâ€¦</div>
    <div id="backup-health-checks" class="backup-health-checks"><div class="loading">Checking backup healthâ€¦</div></div>
  </section>
  <?php endif; ?>

  <?php if ($level >= 200): ?>
  <section class="panel admin dash-span-12" id="panel-history">
    <div class="panel-title">
      <span>GRID OWNER â€” BACKUP HISTORY</span>
      <button type="button" id="history-refresh">REFRESH</button>
    </div>
    <p class="backup-help">Activity record for OAR and IAR backups. CLEAR HISTORY resets this displayed history without deleting the actual backup files.</p>
    <div class="history-toolbar">
      <input type="search" id="history-search" placeholder="Search region, avatar or filenameâ€¦">
      <select id="history-type"><option value="ALL">ALL TYPES</option><option value="OAR">OAR</option><option value="IAR">IAR</option></select>
      <select id="history-status"><option value="ALL">ALL RESULTS</option><option value="SUCCESS">SUCCESS</option><option value="FAILED">FAILED</option><option value="SKIPPED">SKIPPED</option></select>
      <select id="history-source"><option value="ALL">ALL SOURCES</option><option value="MANUAL">MANUAL</option><option value="SCHEDULED">SCHEDULED</option><option value="IMPORTED">IMPORTED</option></select>
      <button type="button" id="history-clear">CLEAR HISTORY</button>
    </div>
    <div id="history-summary" class="history-summary">Loading backup historyâ€¦</div>
    <div id="backup-history" class="history-list"><div class="loading">Loading backup historyâ€¦</div></div>
  </section>
  <?php endif; ?>

  <section class="panel dash-span-12" id="panel-backup-files">
    <div class="panel-title">
      <span><?= $level >= 200 ? 'BACKUP FILES â€” GRID OWNER' : 'MY BACKUP FILES' ?></span>
      <button type="button" id="refresh-backups">REFRESH</button>
    </div>
    <p class="backup-help"><?= $level >= 200
      ? 'Recent panel-created IARs and grid OARs. Download files directly from the server.'
      : 'Your IAR backups created by this panel. Only your signed-in account can download them.' ?></p>
    <div class="backup-tools">
      <input id="backup-search" type="search" placeholder="Search backupsâ€¦" autocomplete="off">
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
    <?php if ($level >= 200): ?>
    <div id="backup-cleanup-tools" class="backup-cleanup-tools">
      <div class="backup-cleanup-summary"><strong id="backup-selected-count">0 selected</strong><span id="backup-selected-size">0 B</span></div>
      <div class="backup-cleanup-actions">
        <button type="button" id="backup-select-old">SELECT OLD BACKUPS</button>
        <button type="button" id="backup-select-visible">SELECT VISIBLE</button>
        <button type="button" id="backup-clear-selected">CLEAR</button>
        <button type="button" id="backup-delete-selected" class="danger" disabled>DELETE SELECTED</button>
      </div>
    </div>
    <?php endif; ?>
    <div id="backup-files" class="backup-files"><div class="loading">Loading backup filesâ€¦</div></div>
  </section>


  <?php if ($level >= 200): ?>
  <section class="panel admin dash-span-4 dash-compact" id="panel-user-iar">
    <div class="panel-title"><span>GRID OWNER â€” USER IAR BACKUP</span></div>

    <div class="admin-iar-row">
      <div class="admin-iar-copy">
        <h2>Backup Local User Inventory</h2>
        <p>Select a local avatar and create their server-side IAR backup.</p>
      </div>

      <div class="admin-iar-controls">
        <select id="admin-iar-avatar" aria-label="Select local avatar">
          <option value="">Loading local avatarsâ€¦</option>
        </select>
        <button type="button" class="gold" id="admin-save-iar">BACKUP USER IAR</button>
      </div>
    </div>

    <div id="admin-iar-status" class="iar-panel-status" hidden></div>
  </section>
  <?php endif; ?>

  <?php if ($level >= 200): ?>
  <section class="panel admin dash-span-8" id="panel-storage">
    <div class="panel-title"><span>GRID OWNER â€” BACKUP STORAGE</span><button type="button" id="refresh-backup-storage">REFRESH</button></div>
    <p class="backup-help">Storage totals for your backup archive. V54 can select older backups while protecting the newest backup for each region or user; deletion still requires your confirmation.</p>
    <div id="backup-storage"><div class="loading">Calculating backup storageâ€¦</div></div>
  </section>
  <?php endif; ?>

  <?php if ($level >= 200): ?>
  <section class="panel admin dash-span-8" id="panel-restore-history">
    <div class="panel-title"><span>GRID OWNER â€” RESTORE HISTORY</span><div><button type="button" id="clear-restore-history">CLEAR RESTORE HISTORY</button> <button type="button" id="refresh-restore-history">REFRESH</button></div></div>
    <p class="backup-help">Recent OAR and IAR restores, their destination, who ran them, and whether they completed successfully.</p>
    <div id="restore-active" class="restore-active" hidden></div>
    <div id="restore-history" class="restore-history"><div class="loading">Loading restore historyâ€¦</div></div>
  </section>
  <?php endif; ?>

  <?php if ($level >= 200): ?>
  <section class="panel admin dash-span-4 dash-compact" id="panel-admin">
    <div class="panel-title"><span>GRID OWNER â€” EXTENDED ADMIN</span></div>
    <div class="admin-actions">
      <button type="button" data-command="Backup">BACKUP ALL</button>
      <button type="button" data-command="RestartAll">RESTART ALL</button>
      <button type="button" data-command="Freeze">FREEZE GRID</button>
      <button type="button" data-command="Thaw">THAW GRID</button>
    </div>
    <p class="warning">LIVE CONTROLS â€” grid-wide commands affect every region. Use them deliberately.</p>
  </section>
  <?php endif; ?>

  <div id="result" class="result dash-span-12">No command run yet.</div>
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







