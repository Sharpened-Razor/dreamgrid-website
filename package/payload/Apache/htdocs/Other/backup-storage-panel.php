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
$siteHeaderTitle = "BACKUP STORAGE";
$siteHeaderButton = "BACK TO ADMIN HOME";
$siteHeaderLink = "/Other/admin-home.php";
require_once __DIR__ . "/includes/site-header.php";
?>
<style id="backup-storage-header-fix">

/* BACKUP STORAGE HEADER ALIGNMENT */

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
<section class="panel admin dash-span-8" id="panel-storage">
    <div class="panel-title"><span>GRID OWNER â€” BACKUP STORAGE</span><button type="button" class="control-hub-button" id="refresh-backup-storage">REFRESH</button></div>
    <p class="backup-help">Storage totals for your backup archive. V54 can select older backups while protecting the newest backup for each region or user; deletion still requires your confirmation.</p>
    <div id="backup-storage"><div class="loading">Calculating backup storageâ€¦</div></div>
  </section>

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
      <button type="button" class="control-hub-button" id="dg-modal-cancel">CANCEL</button>
      <button type="button" class="control-hub-button" id="dg-modal-confirm">CONTINUE</button>
    </div>
  </section>
</div>


<script>
document.addEventListener('DOMContentLoaded', function(){
    loadBackupStorage();
});
</script>
<script src="/Other/australia-panel.js?v=20260826-history-v2"></script>
<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>
</html>























