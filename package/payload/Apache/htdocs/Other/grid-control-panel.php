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


<style id="grid-control-custom-confirm-v1">

/* ============================================================
   GRID CONTROL CUSTOM CONFIRM MODAL V1
   ============================================================ */

body.gc-confirm-lock{
    overflow:hidden !important;
}

.gc-confirm-modal[hidden]{
    display:none !important;
}

.gc-confirm-modal{
    position:fixed;
    inset:0;
    z-index:999999;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:28px;

    background:
        radial-gradient(
            circle at center,
            rgba(20,26,29,.42),
            rgba(0,0,0,.82)
        );

    backdrop-filter:
        blur(7px);

    opacity:0;
    transition:
        opacity .16s ease;
}

.gc-confirm-modal.is-open{
    opacity:1;
}

.gc-confirm-dialog{
    position:relative;

    width:min(
        620px,
        calc(100vw - 44px)
    );

    overflow:hidden;

    border:
        2px solid
        rgba(255,190,35,.90);

    border-radius:
        18px;

    background:
        linear-gradient(
            145deg,
            #41494d 0%,
            #171c1f 24%,
            #090b0d 72%,
            #030405 100%
        );

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.24),

        inset 0 -24px 50px
        rgba(0,0,0,.80),

        0 0 0 5px
        rgba(0,0,0,.46),

        0 0 34px
        rgba(255,174,0,.20),

        0 28px 75px
        rgba(0,0,0,.82);

    transform:
        translateY(10px)
        scale(.985);

    transition:
        transform .16s ease;
}

.gc-confirm-modal.is-open
.gc-confirm-dialog{
    transform:
        translateY(0)
        scale(1);
}

.gc-confirm-dialog:before{
    content:"";

    position:absolute;
    inset:7px;

    border:
        1px solid
        rgba(255,190,35,.28);

    border-radius:12px;

    pointer-events:none;
}

.gc-confirm-top{
    position:relative;

    display:flex;
    align-items:center;
    gap:15px;

    padding:
        20px 24px 17px;

    border-bottom:
        1px solid
        rgba(255,183,20,.35);

    background:
        linear-gradient(
            180deg,
            rgba(255,255,255,.10),
            rgba(255,255,255,.018)
        );
}

.gc-confirm-icon{
    width:46px;
    height:46px;

    flex:
        0 0 46px;

    display:flex;
    align-items:center;
    justify-content:center;

    border:
        1px solid
        rgba(255,188,30,.68);

    border-radius:11px;

    background:
        linear-gradient(
            145deg,
            #3f4548,
            #090b0c
        );

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.20),

        0 0 18px
        rgba(255,177,0,.14);

    color:#ffc53f;

    font-size:22px;
}

.gc-confirm-heading{
    min-width:0;
}

.gc-confirm-kicker{
    margin-bottom:3px;

    color:#ffc43e;

    font-size:11px;
    font-weight:900;

    letter-spacing:
        1.6px;
}

.gc-confirm-title{
    margin:0;

    color:#f4f7f8;

    font-size:23px;
    font-weight:900;

    letter-spacing:
        .7px;

    text-transform:
        uppercase;
}

.gc-confirm-body{
    position:relative;

    padding:
        25px 26px 24px;
}

.gc-confirm-message{
    min-height:68px;

    padding:
        17px 18px;

    border:
        1px solid
        rgba(255,190,45,.25);

    border-radius:
        11px;

    background:
        rgba(0,0,0,.30);

    color:
        #d7dddf;

    font-size:
        14px;

    font-weight:
        600;

    line-height:
        1.65;

    white-space:
        pre-line;

    box-shadow:
        inset 0 0 24px
        rgba(0,0,0,.30);
}

.gc-confirm-warning{
    margin-top:
        15px;

    color:
        #d6aa42;

    font-size:
        12px;

    font-weight:
        700;

    letter-spacing:
        .2px;
}

.gc-confirm-actions{
    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:
        14px;

    margin-top:
        24px;
}

.gc-confirm-button{
    min-height:
        49px;

    border-radius:
        10px;

    cursor:
        pointer;

    font-size:
        13px;

    font-weight:
        900;

    letter-spacing:
        .7px;

    text-transform:
        uppercase;

    transition:
        transform .12s ease,
        filter .12s ease,
        box-shadow .12s ease;
}

.gc-confirm-button:hover{
    transform:
        translateY(-1px);

    filter:
        brightness(1.08);
}

.gc-confirm-button:active{
    transform:
        translateY(1px);
}

.gc-confirm-cancel{
    border:
        1px solid
        rgba(154,169,175,.48);

    background:
        linear-gradient(
            180deg,
            #2e383c,
            #101518
        );

    color:
        #d8dfe2;

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.14);
}

.gc-confirm-accept{
    border:
        1px solid
        #ffbf2e;

    background:
        linear-gradient(
            180deg,
            #ffd264,
            #e89d05
        );

    color:
        #151000;

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.55),

        0 5px 18px
        rgba(216,143,0,.24);
}

.gc-confirm-modal.is-danger
.gc-confirm-title{
    color:
        #ffcb50;
}

.gc-confirm-modal.is-danger
.gc-confirm-message{
    border-color:
        rgba(255,153,45,.42);
}

.gc-confirm-modal.is-danger
.gc-confirm-icon{
    color:
        #ffad3d;
}

@media(max-width:600px){

    .gc-confirm-modal{
        padding:
            16px;
    }

    .gc-confirm-actions{
        grid-template-columns:
            1fr;
    }

    .gc-confirm-title{
        font-size:
            19px;
    }
}

</style>
</head>
<body class="control-panel-page">
<div class="shell">
  <?php
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "GRID CONTROL";
$siteHeaderButton = "BACK TO ADMIN HOME";
$siteHeaderLink = "/Other/admin-home.php";
require_once __DIR__ . "/includes/site-header.php";
?>
<style id="grid-control-header-fix">

/* GRID CONTROL HEADER ALIGNMENT */

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
<style id="grid-control-page-style">

.grid-control-summary{
    display:grid;
    grid-template-columns:repeat(4,minmax(120px,1fr));
    gap:10px;
    margin:15px 0;
}

.grid-control-stat{
    padding:13px 15px;
    border:1px solid rgba(255,255,255,.13);
    border-radius:9px;
    background:rgba(0,0,0,.22);
}

.grid-control-stat strong{
    display:block;
    color:#ffd167;
    font-size:20px;
    margin-bottom:3px;
}

.grid-control-stat span{
    color:#aeb8bd;
    font-size:10px;
    font-weight:800;
    letter-spacing:.06em;
}

.grid-control-toolbar{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:8px;
    margin:14px 0;
}

.grid-control-toolbar .spacer{
    flex:1 1 auto;
}

.grid-control-count{
    color:#c9d2d6;
    font-size:11px;
    font-weight:800;
    margin-left:4px;
}

.grid-control-actions{
    display:grid;
    grid-template-columns:repeat(3,minmax(150px,1fr));
    gap:9px;
    margin:16px 0;
}

.grid-control-actions button{
    min-height:42px;
}

.grid-control-actions .gc-restart-all{
    border-color:#d59b31 !important;
    background:
        linear-gradient(
            180deg,
            rgba(160,105,20,.55),
            rgba(91,55,8,.55)
        ) !important;
}

.grid-control-warning{
    margin:15px 0;
    padding:12px 14px;
    border:1px solid rgba(221,167,53,.42);
    border-radius:8px;
    background:rgba(120,75,8,.13);
    color:#e3c982;
    font-size:11px;
    line-height:1.55;
}

.grid-control-region-list{
    display:grid;
    gap:7px;
    margin-top:12px;
}

.grid-control-region{
    display:grid;
    grid-template-columns:34px minmax(180px,2fr) 130px minmax(130px,1fr) 80px 90px;
    gap:10px;
    align-items:center;

    min-height:49px;
    padding:8px 11px;

    border:1px solid rgba(255,255,255,.11);
    border-radius:7px;

    background:
        linear-gradient(
            180deg,
            rgba(31,39,43,.86),
            rgba(14,20,23,.92)
        );
}

.grid-control-region:hover{
    border-color:rgba(221,167,53,.40);
}

.grid-control-region.gc-running{
    border-color:rgba(71,174,110,.28);
}

.grid-control-region-name{
    color:#fff;
    font-weight:900;
    overflow-wrap:anywhere;
}

.grid-control-region-meta{
    color:#aeb8bd;
    font-size:10px;
}

.grid-control-status{
    display:inline-flex;
    align-items:center;
    justify-content:center;

    min-height:24px;
    padding:3px 8px;

    border:1px solid rgba(255,255,255,.15);
    border-radius:999px;

    background:rgba(255,255,255,.05);

    color:#cbd4d8;

    font-size:9px;
    font-weight:900;

    text-align:center;
}

.grid-control-status.running{
    border-color:rgba(75,192,116,.42);
    background:rgba(35,123,68,.18);
    color:#7ee29d;
}

.grid-control-status.stopped{
    border-color:rgba(220,111,83,.38);
    background:rgba(125,48,30,.16);
    color:#e6a18e;
}

.grid-control-row-state{
    grid-column:2/-1;
    display:none;
    color:#7bc6ff;
    font-size:10px;
    font-weight:800;
}

.grid-control-row-state.show{
    display:block;
}

.grid-control-row-state.error{
    color:#ff9688;
}

.grid-control-result{
    white-space:pre-wrap;
}

#gc-refresh,
#gc-select-all,
#gc-clear-selection{
    white-space:nowrap;
}

@media(max-width:900px){

    .grid-control-summary{
        grid-template-columns:repeat(2,1fr);
    }

    .grid-control-actions{
        grid-template-columns:repeat(2,1fr);
    }

    .grid-control-region{
        grid-template-columns:32px minmax(150px,1fr) 110px;
    }

    .grid-control-region .gc-owner,
    .grid-control-region .gc-avatars,
    .grid-control-region .gc-prims{
        display:none;
    }

    .grid-control-row-state{
        grid-column:2/-1;
    }
}

@media(max-width:600px){

    .grid-control-summary,
    .grid-control-actions{
        grid-template-columns:1fr;
    }

    .grid-control-region{
        grid-template-columns:30px minmax(120px,1fr);
    }

    .grid-control-region .grid-control-status{
        grid-column:2;
    }

    .grid-control-row-state{
        grid-column:2;
    }
}

</style>

  <main class="dashboard-grid">

<?php if ($level >= 200): ?>

<section
    class="panel admin dash-span-12"
    id="panel-grid-control">

    <div class="panel-title">

        <span>
            GRID OWNER - GRID CONTROL
        </span>

        <button
            type="button"
            class="control-hub-button"
            id="gc-refresh">
            REFRESH
        </button>

    </div>


    <p class="backup-help">
        Live region controls. Select one or more regions,
        then start, stop, restart, freeze or thaw the selected regions.
        RESTART ALL uses the native grid-wide Restart All command.
    </p>


    <div class="grid-control-summary">

        <div class="grid-control-stat">
            <strong id="gc-total">0</strong>
            <span>TOTAL REGIONS</span>
        </div>

        <div class="grid-control-stat">
            <strong id="gc-running">0</strong>
            <span>RUNNING</span>
        </div>

        <div class="grid-control-stat">
            <strong id="gc-stopped">0</strong>
            <span>STOPPED / OTHER</span>
        </div>

        <div class="grid-control-stat">
            <strong id="gc-selected">0</strong>
            <span>SELECTED</span>
        </div>

    </div>


    <div class="grid-control-toolbar">

        <button
            type="button"
            class="control-hub-button"
            id="gc-select-all">
            SELECT ALL
        </button>

        <button
            type="button"
            class="control-hub-button"
            id="gc-clear-selection">
            CLEAR
        </button>

        <span
            class="grid-control-count"
            id="gc-selection-text">
            0 regions selected
        </span>

        <span class="spacer"></span>

    </div>


    <div class="grid-control-actions">

        <button
            type="button"
            class="control-hub-button"
            id="gc-start"
            disabled>
            START SELECTED
        </button>

        <button
            type="button"
            class="control-hub-button"
            id="gc-stop"
            disabled>
            STOP SELECTED
        </button>

        <button
            type="button"
            class="control-hub-button"
            id="gc-restart"
            disabled>
            RESTART SELECTED
        </button>

        <button
            type="button"
            class="control-hub-button"
            id="gc-freeze"
            disabled>
            FREEZE SELECTED
        </button>

        <button
            type="button"
            class="control-hub-button"
            id="gc-thaw"
            disabled>
            THAW SELECTED
        </button>

        <button
            type="button"
            class="control-hub-button gc-restart-all"
            id="gc-restart-all">
            RESTART ALL
        </button>

    </div>


    <div class="grid-control-warning">
        LIVE GRID CONTROLS: Start, Stop, Restart, Freeze and Thaw are
        sent directly to the grid service. RESTART ALL affects every region.
        Every command requires confirmation before it is sent.
    </div>


    <div
        id="gc-regions"
        class="grid-control-region-list">

        <div class="loading">
            Loading regions...
        </div>

    </div>

</section>


<?php else: ?>

<section class="panel dash-span-12">

    <div class="panel-title">
        <span>GRID CONTROL</span>
    </div>

    <p class="backup-help">
        Grid Owner access is required to use Grid Control.
    </p>

</section>

<?php endif; ?>


<div
    id="result"
    class="result dash-span-12 grid-control-result">
    Grid Control ready.
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
      <button type="button" class="control-hub-button" id="dg-modal-cancel">CANCEL</button>
      <button type="button" class="control-hub-button" id="dg-modal-confirm">CONTINUE</button>
    </div>
  </section>
</div>


<script>

(function(){

'use strict';

let gcRegions = [];
let gcSelected = new Set();
let gcBusy = false;


/* ------------------------------------------------------------
   HELPERS
   ------------------------------------------------------------ */

function gcEsc(value){

    return String(value ?? '').replace(
        /[&<>"']/g,
        function(ch){

            return {
                '&':'&amp;',
                '<':'&lt;',
                '>':'&gt;',
                '"':'&quot;',
                "'":'&#39;'
            }[ch];

        }
    );
}


function gcSetResult(message, isError){

    const node =
        document.getElementById('result');

    if(!node){
        return;
    }

    node.textContent =
        String(message || '');

    node.style.color =
        isError ? '#ff9688' : '';

}


function gcIsRunning(status){

    const s =
        String(status || '').toLowerCase();

    if(
        s.includes('offline') ||
        s.includes('stopped') ||
        s.includes('down') ||
        s.includes('not running')
    ){
        return false;
    }

    return (
        s.includes('running') ||
        s.includes('online') ||
        s.includes('started') ||
        s.includes('booted')
    );
}


function gcIsTransitioning(status){

    const s =
        String(status || '')
            .toLowerCase()
            .replace(/\s+/g, '');

    return (
        s.includes('booting') ||
        s.includes('starting') ||
        s.includes('stopping') ||
        s.includes('shuttingdown') ||
        s.includes('shuttingdownforgood')
    );
}


function gcIsStopped(status){

    const s =
        String(status || '').toLowerCase();

    return (
        s.includes('offline') ||
        s.includes('stopped') ||
        s.includes('down') ||
        s.includes('not running')
    );
}


function gcCurrentNames(){

    return new Set(
        gcRegions.map(
            function(r){
                return String(r.RegionName || '');
            }
        )
    );

}


function gcCleanSelection(){

    const valid =
        gcCurrentNames();

    gcSelected =
        new Set(
            Array.from(gcSelected).filter(
                function(name){
                    return valid.has(name);
                }
            )
        );

}


function gcUpdateControls(){

    const count =
        gcSelected.size;

    const selected =
        document.getElementById('gc-selected');

    const text =
        document.getElementById('gc-selection-text');

    if(selected){
        selected.textContent =
            String(count);
    }

    if(text){
        text.textContent =
            count +
            ' region' +
            (count === 1 ? '' : 's') +
            ' selected';
    }

    const selectedRegions =
        gcRegions.filter(
            function(region){

                return gcSelected.has(
                    String(
                        region.RegionName ||
                        ''
                    )
                );

            }
        );


    const allRunning =
        count > 0 &&
        selectedRegions.length === count &&
        selectedRegions.every(
            function(region){

                return gcIsRunning(
                    region.Status
                );

            }
        );


    const allStopped =
        count > 0 &&
        selectedRegions.length === count &&
        selectedRegions.every(
            function(region){

                return gcIsStopped(
                    region.Status
                );

            }
        );


    const start =
        document.getElementById(
            'gc-start'
        );

    const stop =
        document.getElementById(
            'gc-stop'
        );

    const restart =
        document.getElementById(
            'gc-restart'
        );

    const freeze =
        document.getElementById(
            'gc-freeze'
        );

    const thaw =
        document.getElementById(
            'gc-thaw'
        );


    if(start){

        start.disabled =
            gcBusy ||
            !allStopped;

        start.title =
            allStopped
                ? 'Start the selected stopped region(s).'
                : 'Start is available only when every selected region is stopped.';

    }


    [
        stop,
        restart,
        freeze,
        thaw
    ].forEach(
        function(button){

            if(!button){
                return;
            }

            button.disabled =
                gcBusy ||
                !allRunning;

        }
    );


    if(stop){

        stop.title =
            allRunning
                ? 'Stop the selected running region(s).'
                : 'Stop is available only when every selected region is running.';

    }


    if(restart){

        restart.title =
            allRunning
                ? 'Restart the selected running region(s).'
                : 'Restart is available only when every selected region is running.';

    }


    if(freeze){

        freeze.title =
            allRunning
                ? 'Freeze the selected running region(s).'
                : 'Freeze is available only when every selected region is running.';

    }


    if(thaw){

        thaw.title =
            allRunning
                ? 'Thaw the selected running region(s).'
                : 'Thaw is available only when every selected region is running.';

    }

    const restartAll =
        document.getElementById(
            'gc-restart-all'
        );

    const refresh =
        document.getElementById(
            'gc-refresh'
        );

    const selectAll =
        document.getElementById(
            'gc-select-all'
        );

    const clear =
        document.getElementById(
            'gc-clear-selection'
        );

    if(restartAll){
        restartAll.disabled = gcBusy;
    }

    if(refresh){
        refresh.disabled = gcBusy;
    }

    if(selectAll){
        selectAll.disabled = gcBusy;
    }

    if(clear){
        clear.disabled = gcBusy;
    }

}


/* ------------------------------------------------------------
   REGION LIST
   ------------------------------------------------------------ */

function gcRenderRegions(){

    const holder =
        document.getElementById(
            'gc-regions'
        );

    if(!holder){
        return;
    }

    gcCleanSelection();

    let running = 0;

    gcRegions.forEach(
        function(region){

            if(
                gcIsRunning(
                    region.Status
                )
            ){
                running++;
            }

        }
    );

    const total =
        gcRegions.length;

    const totalNode =
        document.getElementById(
            'gc-total'
        );

    const runningNode =
        document.getElementById(
            'gc-running'
        );

    const stoppedNode =
        document.getElementById(
            'gc-stopped'
        );

    if(totalNode){
        totalNode.textContent =
            String(total);
    }

    if(runningNode){
        runningNode.textContent =
            String(running);
    }

    if(stoppedNode){
        stoppedNode.textContent =
            String(
                Math.max(
                    0,
                    total - running
                )
            );
    }

    if(!gcRegions.length){

        holder.innerHTML =
            '<div class="loading">' +
            'No regions were returned by the grid service.' +
            '</div>';

        gcUpdateControls();

        return;
    }

    holder.innerHTML =
        gcRegions.map(
            function(region){

                const name =
                    String(
                        region.RegionName || ''
                    );

                const status =
                    String(
                        region.Status || 'Unknown'
                    );

                const owner =
                    String(
                        region.EstateOwner || ''
                    );

                const avatars =
                    Number(
                        region.AvatarCount || 0
                    );

                const prims =
                    Number(
                        region.PrimCount || 0
                    );

                const checked =
                    gcSelected.has(name)
                        ? ' checked'
                        : '';

                const runningClass =
                    gcIsRunning(status)
                        ? ' gc-running'
                        : '';

                const statusClass =
                    gcIsRunning(status)
                        ? 'running'
                        : 'stopped';

                return (
                    '<div class="grid-control-region' +
                    runningClass +
                    '" data-region="' +
                    gcEsc(name) +
                    '">' +

                        '<div>' +
                            '<input ' +
                            'type="checkbox" ' +
                            'class="gc-region-check" ' +
                            'data-region="' +
                            gcEsc(name) +
                            '"' +
                            checked +
                            '>' +
                        '</div>' +

                        '<div class="grid-control-region-name">' +
                            gcEsc(name) +
                        '</div>' +

                        '<div>' +
                            '<span class="grid-control-status ' +
                            statusClass +
                            '">' +
                                gcEsc(status) +
                            '</span>' +
                        '</div>' +

                        '<div class="grid-control-region-meta gc-owner">' +
                            gcEsc(owner || '-') +
                        '</div>' +

                        '<div class="grid-control-region-meta gc-avatars">' +
                            'AV: ' +
                            avatars +
                        '</div>' +

                        '<div class="grid-control-region-meta gc-prims">' +
                            'PRIMS: ' +
                            prims +
                        '</div>' +

                        '<div class="grid-control-row-state" ' +
                        'data-state-for="' +
                        gcEsc(name) +
                        '"></div>' +

                    '</div>'
                );

            }
        ).join('');

    gcUpdateControls();

}


async function gcLoadRegions(showMessage){

    if(showMessage !== false){
        gcSetResult(
            'Loading regions...',
            false
        );
    }

    try{

        const response =
            await fetch(
                '/Other/regions.php',
                {
                    credentials:'same-origin',
                    cache:'no-store'
                }
            );

        const raw =
            await response.text();

        let data;

        try{
            data = JSON.parse(raw);
        }
        catch(e){
            throw new Error(
                raw ||
                'Invalid response from regions.php'
            );
        }

        if(
            !response.ok ||
            !data.ok
        ){
            throw new Error(
                data.error ||
                'Unable to load regions.'
            );
        }

        gcRegions =
            Array.isArray(data.regions)
                ? data.regions
                : [];

        gcRenderRegions();


        /*
         * The grid service can temporarily report states such as:
         *
         *   Booting
         *   Starting
         *   ShuttingDownForGood
         *
         * Keep polling while a region is changing state so the
         * control panel automatically reaches the final status.
         */
        const hasTransitioningRegion =
            gcRegions.some(
                function(region){

                    return gcIsTransitioning(
                        region.Status
                    );

                }
            );


        if(window.gcTransitionRefreshTimer){

            clearTimeout(
                window.gcTransitionRefreshTimer
            );

            window.gcTransitionRefreshTimer =
                null;

        }


        if(hasTransitioningRegion){

            window.gcTransitionRefreshTimer =
                setTimeout(
                    function(){

                        window.gcTransitionRefreshTimer =
                            null;

                        gcLoadRegions(
                            false
                        );

                    },
                    1500
                );

        }


        if(showMessage !== false){

            gcSetResult(
                'Loaded ' +
                gcRegions.length +
                ' region' +
                (gcRegions.length === 1 ? '.' : 's.'),
                false
            );

        }

    }
    catch(error){

        gcRegions = [];

        const holder =
            document.getElementById(
                'gc-regions'
            );

        if(holder){

            holder.innerHTML =
                '<div class="loading">' +
                gcEsc(error.message) +
                '</div>';

        }

        gcRenderRegions();

        gcSetResult(
            error.message,
            true
        );

    }

}


/* ------------------------------------------------------------
   COMMAND BRIDGE
   ------------------------------------------------------------ */

async function gcSendCommand(
    command,
    region
){

    const body =
        new URLSearchParams();

    body.set(
        'command',
        command
    );

    if(region){
        body.set(
            'region',
            region
        );
    }

    const response =
        await fetch(
            '/Other/command.php',
            {
                method:'POST',
                headers:{
                    'Content-Type':
                        'application/x-www-form-urlencoded'
                },
                body:body,
                credentials:'same-origin',
                cache:'no-store'
            }
        );

    const raw =
        await response.text();

    let data;

    try{
        data = JSON.parse(raw);
    }
    catch(e){

        throw new Error(
            raw ||
            'The grid service returned an invalid response.'
        );

    }

    if(
        !response.ok ||
        !data.ok
    ){

        throw new Error(
            data.error ||
            'The grid service rejected the command.'
        );

    }

    return data;

}


function gcSetRowState(
    region,
    message,
    isError
){

    document
        .querySelectorAll(
            '[data-state-for]'
        )
        .forEach(
            function(node){

                if(
                    node.getAttribute(
                        'data-state-for'
                    ) === region
                ){

                    node.textContent =
                        message;

                    node.classList.add(
                        'show'
                    );

                    node.classList.toggle(
                        'error',
                        !!isError
                    );

                }

            }
        );

}


/* ------------------------------------------------------------
   SELECTED REGION ACTIONS
   ------------------------------------------------------------ */

async function gcRunSelected(
    command,
    label
){

    if(
        gcBusy ||
        gcSelected.size === 0
    ){
        return;
    }

    const names =
        Array.from(gcSelected);

    const warning =
        label +
        ' will be sent to ' +
        names.length +
        ' selected region' +
        (names.length === 1 ? '' : 's') +
        ':\n\n' +
        names.join('\n') +
        '\n\nContinue?';

    if(
        !(await gcConfirm(
            label,
            warning,
            false
        ))
    ){
        return;
    }

    gcBusy = true;
    gcUpdateControls();

    const success = [];
    const failed = [];

    for(
        let i = 0;
        i < names.length;
        i++
    ){

        const region =
            names[i];

        gcSetRowState(
            region,
            label + '...',
            false
        );

        gcSetResult(
            label +
            ' - ' +
            (i + 1) +
            ' of ' +
            names.length +
            '\nCurrent region: ' +
            region,
            false
        );

        try{

            const data =
                await gcSendCommand(
                    command,
                    region
                );

            success.push(region);

            gcSetRowState(
                region,
                data.message ||
                'Command accepted.',
                false
            );

        }
        catch(error){

            failed.push({
                region:region,
                error:error.message
            });

            gcSetRowState(
                region,
                'ERROR - ' +
                error.message,
                true
            );

        }

        await new Promise(
            function(resolve){
                setTimeout(
                    resolve,
                    350
                );
            }
        );

    }

    gcBusy = false;
    gcUpdateControls();

    let message =
        label +
        ' FINISHED\n' +
        'Successful: ' +
        success.length +
        ' of ' +
        names.length +
        '\nErrors: ' +
        failed.length;

    if(failed.length){

        message +=
            '\n\n' +
            failed.map(
                function(item){
                    return (
                        item.region +
                        ': ' +
                        item.error
                    );
                }
            ).join('\n');

    }

    gcSetResult(
        message,
        failed.length > 0
    );

    setTimeout(
        function(){
            gcLoadRegions(false);
        },
        2000
    );

}


/* ------------------------------------------------------------
   NATIVE RESTART ALL
   ------------------------------------------------------------ */

async function gcRestartAll(){

    if(gcBusy){
        return;
    }

    if(
        !(await gcConfirm(
            'RESTART ALL',
            'The grid will restart EVERY REGION.' +
            '\n\nAll running regions will be affected.' +
            '\n\nDo you want to continue?',
            true
        ))
    ){
        return;
    }

    gcBusy = true;
    gcUpdateControls();

    gcSetResult(
        'Sending Restart All to the grid service...',
        false
    );

    try{

        const data =
            await gcSendCommand(
                'RestartAll',
                ''
            );

        gcSetResult(
            data.message ||
            'The grid accepted Restart All.',
            false
        );

        setTimeout(
            function(){
                gcLoadRegions(false);
            },
            4000
        );

    }
    catch(error){

        gcSetResult(
            error.message,
            true
        );

    }
    finally{

        gcBusy = false;
        gcUpdateControls();

    }

}


/* ------------------------------------------------------------
   EVENTS
   ------------------------------------------------------------ */

document.addEventListener(
    'change',
    function(event){

        const check =
            event.target.closest(
                '.gc-region-check'
            );

        if(!check){
            return;
        }

        const name =
            String(
                check.getAttribute(
                    'data-region'
                ) || ''
            );

        if(!name){
            return;
        }

        if(check.checked){
            gcSelected.add(name);
        }
        else{
            gcSelected.delete(name);
        }

        gcUpdateControls();

    }
);


document.addEventListener(
    'DOMContentLoaded',
    function(){

        const refresh =
            document.getElementById(
                'gc-refresh'
            );

        const selectAll =
            document.getElementById(
                'gc-select-all'
            );

        const clear =
            document.getElementById(
                'gc-clear-selection'
            );

        const start =
            document.getElementById(
                'gc-start'
            );

        const stop =
            document.getElementById(
                'gc-stop'
            );

        const restart =
            document.getElementById(
                'gc-restart'
            );

        const freeze =
            document.getElementById(
                'gc-freeze'
            );

        const thaw =
            document.getElementById(
                'gc-thaw'
            );

        const restartAll =
            document.getElementById(
                'gc-restart-all'
            );


        refresh?.addEventListener(
            'click',
            function(){
                gcLoadRegions(true);
            }
        );


        selectAll?.addEventListener(
            'click',
            function(){

                gcSelected =
                    new Set(
                        gcRegions.map(
                            function(region){
                                return String(
                                    region.RegionName || ''
                                );
                            }
                        ).filter(Boolean)
                    );

                gcRenderRegions();

            }
        );


        clear?.addEventListener(
            'click',
            function(){

                gcSelected.clear();
                gcRenderRegions();

            }
        );


        start?.addEventListener(
            'click',
            function(){
                gcRunSelected(
                    'StartRegion',
                    'START SELECTED'
                );
            }
        );


        stop?.addEventListener(
            'click',
            function(){
                gcRunSelected(
                    'StopRegion',
                    'STOP SELECTED'
                );
            }
        );


        restart?.addEventListener(
            'click',
            function(){
                gcRunSelected(
                    'RestartRegion',
                    'RESTART SELECTED'
                );
            }
        );


        freeze?.addEventListener(
            'click',
            function(){
                gcRunSelected(
                    'Freeze',
                    'FREEZE SELECTED'
                );
            }
        );


        thaw?.addEventListener(
            'click',
            function(){
                gcRunSelected(
                    'Thaw',
                    'THAW SELECTED'
                );
            }
        );


        restartAll?.addEventListener(
            'click',
            gcRestartAll
        );


        gcLoadRegions(true);

    }
);

})();

</script>
<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>

<!-- =========================================================
     GRID CONTROL CUSTOM CONFIRM MODAL V1
     ========================================================= -->

<div
    id="gcConfirmModal"
    class="gc-confirm-modal"
    hidden>

    <div
        class="gc-confirm-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="gcConfirmTitle"
        aria-describedby="gcConfirmMessage">

        <div class="gc-confirm-top">

            <div class="gc-confirm-icon">
                ⚠
            </div>

            <div class="gc-confirm-heading">

                <div class="gc-confirm-kicker">
                    GRID CONTROL CONFIRMATION
                </div>

                <h2
                    id="gcConfirmTitle"
                    class="gc-confirm-title">
                    CONFIRM COMMAND
                </h2>

            </div>

        </div>


        <div class="gc-confirm-body">

            <div
                id="gcConfirmMessage"
                class="gc-confirm-message">
            </div>

            <div class="gc-confirm-warning">
                This command will be sent directly to the grid service.
            </div>

            <div class="gc-confirm-actions">

                <button
                    type="button"
                    id="gcConfirmCancel"
                    class="gc-confirm-button gc-confirm-cancel">
                    CANCEL
                </button>

                <button
                    type="button"
                    id="gcConfirmAccept"
                    class="gc-confirm-button gc-confirm-accept">
                    CONFIRM
                </button>

            </div>

        </div>

    </div>

</div>


<script id="grid-control-custom-confirm-js-v1">

(function(){

    let gcConfirmResolver =
        null;

    const modal =
        document.getElementById(
            'gcConfirmModal'
        );

    const title =
        document.getElementById(
            'gcConfirmTitle'
        );

    const message =
        document.getElementById(
            'gcConfirmMessage'
        );

    const cancelButton =
        document.getElementById(
            'gcConfirmCancel'
        );

    const acceptButton =
        document.getElementById(
            'gcConfirmAccept'
        );


    function closeConfirm(
        result
    ){

        if(!modal){
            return;
        }

        modal.classList.remove(
            'is-open'
        );

        document.body.classList.remove(
            'gc-confirm-lock'
        );

        const resolver =
            gcConfirmResolver;

        gcConfirmResolver =
            null;

        setTimeout(
            function(){

                modal.hidden =
                    true;

                if(resolver){
                    resolver(
                        result
                    );
                }

            },
            140
        );

    }


    window.gcConfirm =
        function(
            actionTitle,
            text,
            danger
        ){

            return new Promise(
                function(resolve){

                    if(
                        !modal ||
                        !title ||
                        !message ||
                        !cancelButton ||
                        !acceptButton
                    ){
                        resolve(
                            false
                        );

                        return;
                    }

                    gcConfirmResolver =
                        resolve;

                    title.textContent =
                        actionTitle ||
                        'CONFIRM COMMAND';

                    message.textContent =
                        text ||
                        'Confirm this grid command.';

                    modal.classList.toggle(
                        'is-danger',
                        danger === true
                    );

                    modal.hidden =
                        false;

                    document.body.classList.add(
                        'gc-confirm-lock'
                    );

                    requestAnimationFrame(
                        function(){

                            modal.classList.add(
                                'is-open'
                            );

                            acceptButton.focus();

                        }
                    );

                }
            );

        };


    cancelButton?.addEventListener(
        'click',
        function(){

            closeConfirm(
                false
            );

        }
    );


    acceptButton?.addEventListener(
        'click',
        function(){

            closeConfirm(
                true
            );

        }
    );


    modal?.addEventListener(
        'click',
        function(event){

            if(
                event.target === modal
            ){
                closeConfirm(
                    false
                );
            }

        }
    );


    document.addEventListener(
        'keydown',
        function(event){

            if(
                !modal ||
                modal.hidden
            ){
                return;
            }

            if(
                event.key ===
                'Escape'
            ){
                event.preventDefault();

                closeConfirm(
                    false
                );
            }

        }
    );

})();

</script>
</body>
</html>























