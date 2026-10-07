<?php

/* DREAMGRID FORMREGION NATIVE SYNC V2 - New Region mode. */

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/region-database.php';
$embedded = ($_GET['embedded'] ?? '') === '1';

$session =
    ag_require_admin();




$avatar =
    ag_avatar_name(
        $session
    );


$level =
    ag_user_level(
        $session
    );


header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


$cpuCount =
    (int)getenv(
        'NUMBER_OF_PROCESSORS'
    );

if ($cpuCount < 1) {
    $cpuCount = 1;
}

$cpuVisible = 24;
$templateCoreMask = 0;
$templateIni =
    ag_dg_regions_root() .
    DIRECTORY_SEPARATOR .
    'Welcome' .
    DIRECTORY_SEPARATOR .
    'Region' .
    DIRECTORY_SEPARATOR .
    'Welcome.ini';

if (is_file($templateIni)) {
    $templateText = @file_get_contents($templateIni);
    if (
        is_string($templateText) &&
        preg_match('/^\s*Cores\s*=\s*(\d+)\s*$/mi', $templateText, $coreMatch)
    ) {
        $templateCoreMask = (int)$coreMatch[1];
    }
}

if ($templateCoreMask < 1) {
    $fallbackCoreCount = min(max($cpuCount, 1), $cpuVisible);
    $templateCoreMask = (1 << $fallbackCoreCount) - 1;
}

/*
 * ============================================================
 * DREAMGRID FORMREGION PARITY V2
 *
 * Start.dll: Outworldz.Forms.FormRegion
 * Name of Region selects New Region or an existing Region.
 * Existing Regions are handed to the same native-style editor.
 * ============================================================
 */
$requestedExistingRegion = trim((string)($_GET['region'] ?? ''));
if ($requestedExistingRegion !== '') {
    $target =
        '/Other/admin-region-edit.php?region=' .
        rawurlencode($requestedExistingRegion) .
        ($embedded ? '&embedded=1' : '');

    header('Location: ' . $target, true, 302);
    exit;
}

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>Regions</title>

<style id="create-region-charcoal-gold-v4">

*{
    box-sizing:border-box;
}

html,
body{
    min-height:100%;
}

html{
    background:#070907;
}

body{
    margin:0;
    background:#070907;
    color:#dedbd2;
    font-family:Arial,Helvetica,sans-serif;
    font-size:14px;
}

.region-shell{
    width:min(1500px,calc(100% - 28px));
    margin:14px auto 28px;
    padding:16px;
    background:#080a08;
    border:1px solid #40371f;
    border-radius:10px;
    box-shadow:0 12px 32px rgba(0,0,0,.46);
}

.tabs{
    display:flex;
    flex-wrap:wrap;
    gap:5px;
    margin:0 0 10px;
    padding:5px;
    background:#080a08;
    border:1px solid #40371f;
    border-radius:8px;
}

.tab-button{
    appearance:none;
    min-height:36px;
    padding:8px 14px;
    border:1px solid #40371f;
    border-radius:6px;
    background:#10120f;
    color:#d9ae48;
    font-weight:700;
    font-size:12px;
    cursor:pointer;
}

.tab-button:hover{
    background:#18170f;
    border-color:#5b4820;
    color:#f0ca62;
}

.tab-button.active{
    background:#18170f;
    border-color:#8c6a26;
    color:#f0ca62;
    box-shadow:inset 0 0 0 1px rgba(217,174,72,.12);
}

.tab-button:disabled{
    cursor:not-allowed;
    opacity:.45;
}

.tab-panel{
    display:none;
}

.tab-panel.active{
    display:block;
}

.panel{
    padding:15px;
    background:#0d0f0c;
    border:1px solid #40371f;
    border-radius:9px;
}

.panel-title{
    margin:0 0 14px;
    padding:0 0 9px;
    border-bottom:1px solid #40371f;
    color:#f0ca62;
    font-size:14px;
    font-weight:800;
    letter-spacing:.04em;
}

.form-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:12px 24px;
}

.form-grid.three{
    grid-template-columns:repeat(3,minmax(0,1fr));
}

.field{
    min-width:0;
}

.field.full{
    grid-column:1 / -1;
}

.field label{
    display:block;
    margin:0 0 6px;
    color:#dedbd2;
    font-size:12px;
    font-weight:800;
}

input[type="text"],
input[type="number"],
input[type="password"],
input[type="email"],
input[type="url"],
select,
textarea{
    width:100%;
    min-height:38px;
    padding:8px 10px;
    border:1px solid #40371f;
    border-radius:6px;
    outline:none;
    background:#10120f;
    color:#dedbd2;
    box-shadow:none;
}

textarea{
    min-height:100px;
    resize:vertical;
}

input::placeholder,
textarea::placeholder{
    color:#817d74;
    opacity:1;
}

input:focus,
select:focus,
textarea:focus{
    border-color:#8c6a26;
    box-shadow:0 0 0 2px rgba(140,106,38,.18);
}

input:disabled,
select:disabled,
textarea:disabled{
    opacity:.55;
    cursor:not-allowed;
}

.toggle-list{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:8px;
}

.toggle,
.radio-row,
.cpu-item{
    display:flex;
    align-items:center;
    gap:9px;
    min-height:42px;
    padding:8px 10px;
    border:1px solid #40371f;
    border-radius:7px;
    background:#080a08;
    color:#dedbd2;
}

.toggle:hover,
.radio-row:hover,
.cpu-item:hover{
    background:#10120f;
    border-color:#5b4820;
}

.toggle input,
.radio-row input,
.cpu-item input{
    accent-color:#8c6a26;
}

.radio-list{
    display:grid;
    gap:8px;
}

.cpu-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(90px,1fr));
    gap:8px;
}

.sim-size-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:8px;
}

.sim-size-option{
    justify-content:flex-start;
    min-height:38px;
}

.native-region-actions{
    display:flex;
    flex-wrap:wrap;
    gap:9px;
    margin-top:4px;
}

.button{
    appearance:none;
    min-width:118px;
    min-height:38px;
    padding:8px 16px;
    border:1px solid #5b4820;
    border-radius:7px;
    background:#10120f;
    color:#d9ae48;
    font-weight:800;
    cursor:pointer;
}

.button:hover{
    background:#18170f;
    border-color:#8c6a26;
    color:#f0ca62;
}

.button.primary{
    background:#18170f;
    border-color:#8c6a26;
    color:#f0ca62;
}

.button:disabled{
    opacity:.42;
    cursor:not-allowed;
}

small{
    display:block;
    margin-top:8px;
    color:#9e998e;
    line-height:1.45;
}

.footer-actions{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    margin-top:22px;
    padding-top:20px;
    border-top:1px solid #40371f;
}

.footer-right{
    display:flex;
    flex-wrap:wrap;
    justify-content:flex-end;
    gap:10px;
}

.php-level{
    min-width:128px;
    padding:8px 12px;
    text-align:center;
    border:1px solid #40371f;
    border-radius:8px;
    background:#10120f;
    color:#dedbd2;
    font-size:10px;
    font-weight:800;
}

.php-level strong{
    display:block;
    margin-bottom:2px;
    color:#f0ca62;
}

a{
    color:#d9ae48;
}

a:hover{
    color:#f0ca62;
}

@media(max-width:900px){

    .form-grid,
    .form-grid.three,
    .toggle-list{
        grid-template-columns:1fr;
    }

    .sim-size-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .footer-actions{
        flex-direction:column;
        align-items:stretch;
    }

    .footer-right{
        justify-content:flex-start;
    }
}

</style>

<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<style id="create-region-custom-confirm-style">

/* ==========================================================
   SAVE REGION CONFIRMATION V1
   ========================================================== */

body.ag-confirm-open{
    overflow:hidden !important;
}


#createRegionConfirmModal[hidden]{
    display:none !important;
}


#createRegionConfirmModal{

    position:fixed !important;

    inset:0 !important;

    z-index:99999 !important;

    display:flex !important;

    align-items:center !important;

    justify-content:center !important;

    padding:24px !important;
}


#createRegionConfirmModal .ag-confirm-backdrop{

    position:absolute !important;

    inset:0 !important;

    background:
        rgba(0,0,0,.76) !important;

    backdrop-filter:
        blur(6px) !important;
}


#createRegionConfirmModal .ag-confirm-card{

    position:relative !important;

    z-index:2 !important;

    width:min(560px,100%) !important;

    padding:26px !important;

    box-sizing:border-box !important;

    border:
        1px solid rgba(244,179,35,.72) !important;

    border-radius:
        16px !important;

    background:
        linear-gradient(
            145deg,
            rgba(28,36,41,.995),
            rgba(7,12,15,.995) 58%,
            rgba(2,5,7,1)
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.08),
        0 24px 70px rgba(0,0,0,.72) !important;
}


#createRegionConfirmModal .ag-confirm-eyebrow{

    margin:
        0 0 7px !important;

    color:
        #f4b323 !important;

    font-size:
        12px !important;

    font-weight:
        900 !important;

    letter-spacing:
        .13em !important;

    text-transform:
        uppercase !important;
}


#createRegionConfirmModal h2{

    margin:
        0 0 10px !important;

    color:
        #ffffff !important;

    font-size:
        27px !important;

    font-weight:
        900 !important;

    line-height:
        1.1 !important;
}


#createRegionConfirmModal .ag-confirm-text{

    margin:
        0 0 18px !important;

    color:
        #dedbd2 !important;

    font-size:
        14px !important;

    line-height:
        1.55 !important;
}


#createRegionConfirmModal .ag-confirm-summary{

    display:grid !important;

    grid-template-columns:
        1fr !important;

    gap:
        8px !important;

    margin:
        0 0 20px !important;
}


#createRegionConfirmModal .ag-confirm-row{

    display:grid !important;

    grid-template-columns:
        115px minmax(0,1fr) !important;

    gap:
        12px !important;

    align-items:center !important;

    padding:
        10px 12px !important;

    border:
        1px solid rgba(255,255,255,.10) !important;

    border-radius:
        9px !important;

    background:
        rgba(2,7,10,.88) !important;
}


#createRegionConfirmModal .ag-confirm-label{

    color:
        #9f9a8d !important;

    font-size:
        11px !important;

    font-weight:
        900 !important;

    letter-spacing:
        .05em !important;

    text-transform:
        uppercase !important;
}


#createRegionConfirmModal .ag-confirm-value{

    min-width:0 !important;

    color:
        #ffffff !important;

    font-size:
        14px !important;

    font-weight:
        800 !important;

    overflow-wrap:
        anywhere !important;
}


#createRegionConfirmModal .ag-confirm-warning{

    margin:
        0 0 20px !important;

    padding:
        12px 14px !important;

    border:
        1px solid rgba(244,179,35,.33) !important;

    border-radius:
        9px !important;

    background:
        rgba(244,179,35,.08) !important;

    color:
        #e8d49f !important;

    font-size:
        13px !important;

    line-height:
        1.5 !important;
}


#createRegionConfirmModal .ag-confirm-warning strong{

    color:
        #f4b323 !important;
}


#createRegionConfirmModal .ag-confirm-actions{

    display:flex !important;

    align-items:center !important;

    justify-content:flex-end !important;

    gap:
        10px !important;
}


#createRegionConfirmModal .ag-confirm-actions .button{

    min-width:
        125px !important;

    min-height:
        42px !important;

    display:inline-flex !important;

    align-items:center !important;

    justify-content:center !important;
}


#createRegionConfirmModal .ag-confirm-actions .primary{

    min-width:
        165px !important;
}


@media(max-width:600px){

    #createRegionConfirmModal{

        padding:
            14px !important;
    }


    #createRegionConfirmModal .ag-confirm-card{

        padding:
            19px !important;
    }


    #createRegionConfirmModal .ag-confirm-row{

        grid-template-columns:
            1fr !important;

        gap:
            4px !important;
    }


    #createRegionConfirmModal .ag-confirm-actions{

        flex-direction:
            column-reverse !important;
    }


    #createRegionConfirmModal .ag-confirm-actions .button{

        width:
            100% !important;
    }
}

</style>
<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=<?= filemtime(__DIR__ . '/assets/css/ag-uniform-site-v12.css') ?>">

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


</head>


<body>


<div class="region-shell">


<?php
if (!$embedded) {
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "REGIONS";
$siteHeaderRole = "GRID OWNER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "REGION MANAGER";
$siteHeaderLink = "/Other/admin-regions.php";
require_once __DIR__ . "/includes/site-header.php";
}
?>


<div
    class="tabs"
    role="tablist"
>

    <button class="tab-button active cc-nav-button" data-tab="regions">
        REGIONS
    </button>

    <button class="tab-button cc-nav-button" data-tab="options">
        OPTIONS
    </button>

    <button class="tab-button cc-nav-button" data-tab="maps">
        MAPS
    </button>

    <button class="tab-button cc-nav-button" data-tab="physics">
        PHYSICS
    </button>

    <button class="tab-button cc-nav-button" data-tab="scripts">
        SCRIPTS
    </button>

    <button class="tab-button cc-nav-button" data-tab="permissions">
        PERMISSIONS
    </button>

    <button class="tab-button cc-nav-button" data-tab="publicity">
        PUBLICITY
    </button>

    <button class="tab-button cc-nav-button" data-tab="modules">
        MODULES
    </button>

    <button class="tab-button cc-nav-button" data-tab="cpu">
        CPU %
    </button>

</div>


<form
    id="createRegionForm"
    autocomplete="off"
>
<input
    type="hidden"
    id="createRegionCsrf"
    name="csrf_token"
    value=""
>


<!-- ========================================================
     REGIONS - DREAMGRID FORMREGION PARITY V2
     ======================================================== -->

<section
    class="tab-panel active panel"
    data-panel="regions"
>

    <div class="panel-title">
        REGIONS
    </div>

    <!-- Region owner is intentionally internal only. Start.dll does not expose it here. -->
    <select id="regionOwner" name="regionOwner" hidden aria-hidden="true">
        <option value="">Loading signed-in Grid owner...</option>
    </select>
    <select id="smartStart" hidden aria-hidden="true"><option value="off">Off</option><option value="suspend">Freeze/Thaw</option><option value="boot">Shut Down/Boot</option></select>
    <select id="regionSize" hidden aria-hidden="true"><?php for($compatSize=1;$compatSize<=16;$compatSize++): ?><option value="<?=$compatSize?>"><?=$compatSize?></option><?php endfor; ?></select>

    <div class="form-grid">

        <div class="field full">
            <label for="regionSelector">NAME OF REGION</label>
            <input
    id="regionSelector"
    type="text"
    value="Name Of Region"
    maxlength="64"
    autocomplete="off"
    spellcheck="false"
>
        </div>

        <div class="field full">
            <div class="toggle-list">
                <label class="toggle">
                    <input type="checkbox" name="enabled" checked>
                    Enabled
                </label>
                <label class="toggle">
                    <input type="checkbox" name="locked">
                    Locked
                </label>
            </div>
        </div>

        <div class="field">
            <label for="regionName">NAME</label>
            <input id="regionName" name="regionName" type="text" required maxlength="64" placeholder="New Region">
        </div>

        <div class="field">
            <label for="groupName">GROUP</label>
            <input id="groupName" name="groupName" type="text" required maxlength="64" placeholder="New Region">
        </div>

        <div class="field">
            <label for="estateName">ESTATE NAME</label>
            <input id="estateName" name="estateName" type="text" required maxlength="64" placeholder="Estate name">
        </div>

        <label class="toggle">
            <input type="checkbox" id="applyEstateAll" name="applyEstateAll" value="1">
            Apply Estate to all Enabled Regions
        </label>

        <div class="field">
            <label>SMART START</label>
            <div class="radio-list">
                <label class="radio-row"><input type="radio" name="smartStart" value="off" checked>Off</label>
                <label class="radio-row"><input type="radio" name="smartStart" value="suspend">Freeze/Thaw</label>
                <label class="radio-row"><input type="radio" name="smartStart" value="boot">Shut Down/Boot</label>
            </div>
        </div>

        <div class="field">
            <label>SIM SIZE</label>
            <div class="sim-size-grid">
                <?php for ($i = 1; $i <= 16; $i++): ?>
                    <label class="radio-row sim-size-option">
                        <input type="radio" name="regionSize" value="<?=$i?>" <?=$i === 1 ? 'checked' : ''?>>
                        <?=$i?> x <?=$i?>
                    </label>
                <?php endfor; ?>
            </div>
        </div>

        <div class="field full">
            <div class="native-region-actions">
                <button class="button primary" id="createRegionButton" type="submit">SAVE</button>
                <button class="button" id="newDeregisterButton" type="button">DEREGISTER</button>
                <button class="button" id="newDeleteButton" type="button">DELETE</button>
                <button class="button" id="newEditButton" type="button">EDIT</button>
            </div>
        </div>

    </div>

</section>


<!-- ========================================================
     OPTIONS
     ======================================================== -->

<section
    class="tab-panel panel"
    data-panel="options"
>

    <div class="panel-title">
        REGION OPTIONS
    </div>


    <div class="form-grid three">

        <div class="field">

            <label for="coordX">
                GRID X
            </label>

            <input
                id="coordX"
                name="coordX"
                type="number"
                required
                min="0"
                step="1"
                placeholder="100"
            >

        </div>


        <div class="field">

            <label for="coordY">
                GRID Y
            </label>

            <input
                id="coordY"
                name="coordY"
                type="number"
                required
                min="0"
                step="1"
                placeholder="100"
            >

        </div>


        <div class="field">

            <label for="port">
                REGION / GROUP PORT
            </label>

            <input
                id="port"
                name="port"
                type="number"
                min="1"
                max="65535"
                placeholder="AUTO"
            >

            <small>
                Blank will mean automatic safe port assignment.
            </small>

        </div>


        <div class="field">

            <label for="nonPhysicalPrimMax">
                NONPHYSICAL PRIM MAX SIZE
            </label>

            <input
                id="nonPhysicalPrimMax"
                name="nonPhysicalPrimMax"
                type="number"
                min="0"
                step="1"
                value="1024"
            >

        </div>


        <div class="field">

            <label for="physicalPrimMax">
                PHYSICAL PRIM MAX SIZE
            </label>

            <input
                id="physicalPrimMax"
                name="physicalPrimMax"
                type="number"
                min="0"
                step="1"
                value="64"
            >

        </div>


        <div class="field">

            <label for="maxPrims">
                MAX PRIMS IN REGION
            </label>

            <input
                id="maxPrims"
                name="maxPrims"
                type="number"
                min="0"
                step="1"
                value="45000"
            >

        </div>


        <div class="field">

            <label for="maxAgents">
                MAX AVATARS + NPCs
            </label>

            <input
                id="maxAgents"
                name="maxAgents"
                type="number"
                min="1"
                step="1"
                value="100"
            >

        </div>


        <div class="field">

            <label for="regionUuid">
                REGION UUID
            </label>

            <input
                id="regionUuid"
                name="regionUuid"
                type="text"
                maxlength="36"
                placeholder="Generated automatically"
            >

        </div>


        <div class="field">

            <label>
                PRIM SIZE CONTROL
            </label>

            <label class="toggle">

                <input
                    type="checkbox"
                    name="clampPrimSize"
                >

                Clamp Prim Size

            </label>

        </div>

    </div>

</section>



<!-- ========================================================
     MAPS
     ======================================================== -->

<section
    class="tab-panel panel"
    data-panel="maps"
>

    <div class="panel-title">
        MAPS
    </div>


    <div class="form-grid">

        <div class="field full">

            <label for="mapType">
                MAP GENERATION
            </label>

            <select
                id="mapType"
                name="mapType"
            >

                <option value="Default">
                    Use Default
                </option>

                <option value="None">
                    None
                </option>

                <option value="Simple">
                    Simple but Fast
                </option>

                <option value="Good">
                    Good - Warp3D
                </option>

                <option value="Better">
                    Better - Prims, Slow
                </option>

                <option value="Best">
                    Best - Prims + Mesh, Very Slow
                </option>

            </select>

        </div>


        <div class="field">

            <label for="maptileStatic">
                CUSTOM MAP IMAGE
            </label>

            <input
                id="maptileStatic"
                name="maptileStatic"
                type="text"
                placeholder="Optional static map file"
            >

        </div>


        <div class="field">

            <label for="landingSpot">
                DEFAULT LANDING SPOT
            </label>

            <input
                id="landingSpot"
                name="landingSpot"
                type="text"
                value="<128,128,30>"
            >

        </div>

    </div>

</section>



<!-- ========================================================
     PHYSICS
     ======================================================== -->

<section
    class="tab-panel panel"
    data-panel="physics"
>

    <div class="panel-title">
        PHYSICS
    </div>


    <div class="radio-list">

        <label class="radio-row">

            <input
                type="radio"
                name="physics"
                value=""
            >

            Use Default

        </label>


        <label class="radio-row">

            <input
                type="radio"
                name="physics"
                value="4"
            >

            Ubit Open Dynamic Engine

        </label>


        <label class="radio-row">

            <input
                type="radio"
                name="physics"
                value="5"
            >

            Ubit ODE / Bullet Hybrid

        </label>


        <label class="radio-row">

            <input
                type="radio"
                name="physics"
                value="2"
            >

            Bullet Physics

        </label>


        <label class="radio-row">

            <input
                type="radio"
                name="physics"
                value="3"
                checked
            >

            Bullet Physics - Separate Thread

        </label>

    </div>

</section>



<!-- ========================================================
     SCRIPTS
     ======================================================== -->

<section
    class="tab-panel panel"
    data-panel="scripts"
>

    <div class="panel-title">
        SCRIPTS
    </div>


    <div class="form-grid three">

        <div class="field">

            <label for="scriptEngine">
                SCRIPT ENGINE
            </label>

            <select
                id="scriptEngine"
                name="scriptEngine"
            >

                <option value="">
                    Use Default
                </option>

                <option value="Off">
                    Off
                </option>

                <option value="YEngine">
                    On
                </option>

            </select>

        </div>


        <div class="field">

            <label for="asyncScript">
                ASYNC LL SCRIPT TIME (ms)
            </label>

            <input
                id="asyncScript"
                name="asyncScript"
                type="number"
                min="0"
                value="100"
            >

        </div>


        <div class="field">

            <label for="timerRate">
                SCRIPT TIMER RATE
            </label>

            <input
                id="timerRate"
                name="timerRate"
                type="number"
                min="0"
                step="0.01"
                value="0.2"
            >

        </div>


        <div class="field">

            <label for="frameRate">
                FRAME TIME
            </label>

            <input
                id="frameRate"
                name="frameRate"
                type="number"
                min="0"
                step="0.000001"
                value="0.09"
            >

        </div>

    </div>

</section>



<!-- ========================================================
     PERMISSIONS
     ======================================================== -->

<section
    class="tab-panel panel"
    data-panel="permissions"
>

    <div class="panel-title">
        PERMISSIONS
    </div>


    <div class="form-grid">

        <div class="field full">

            <label for="permissionMode">
                GOD PERMISSIONS
            </label>

            <select
                id="permissionMode"
                name="permissionMode"
            >

                <option value="default">
                    Use Default
                </option>

                <option value="level">
                    Allow Level-based Gods
                </option>

                <option value="owner">
                    Region Owner is God
                </option>

                <option value="manager">
                    Estate Manager is God
                </option>

            </select>

        </div>

    </div>

</section>



<!-- ========================================================
     PUBLICITY
     ======================================================== -->

<section
    class="tab-panel panel"
    data-panel="publicity"
>

    <div class="panel-title">
        PUBLICITY
    </div>


    <div class="form-grid">

        <div class="field">

            <label for="publicity">
                PUBLICITY
            </label>

            <select
                id="publicity"
                name="publicity"
            >

                <option value="default">
                    Use Default
                </option>

                <option value="off">
                    Do Not Publish
                </option>

                <option value="search" selected>
                    Publish Items Marked for Search
                </option>

            </select>

        </div>


        <div class="field">

            <label for="opensimWorldKey">
                OPENSIMWORLD / SEARCH KEY
            </label>

            <input
                id="opensimWorldKey"
                name="opensimWorldKey"
                type="text"
                maxlength="255"
                placeholder="Optional API key"
            >

        </div>

    </div>

</section>



<!-- ========================================================
     MODULES
     ======================================================== -->

<section
    class="tab-panel panel"
    data-panel="modules"
>

    <div class="panel-title">
        MODULES
    </div>


    <div class="toggle-list">

        <label class="toggle">
            <input type="checkbox" name="birds">
            Bird Module
        </label>

        <label class="toggle">
            <input type="checkbox" name="tides">
            Tides
        </label>

        <label class="toggle" title="Teleport module compatibility option">
            <input type="checkbox" name="teleport" checked>
            Teleport
        </label>

        <label class="toggle">
            <input type="checkbox" name="disableGloebits">
            Disable Gloebit
        </label>

        <label class="toggle">
            <input type="checkbox" name="disallowForeigners">
            Disable Foreign Visitors
        </label>

        <label class="toggle">
            <input type="checkbox" name="disallowResidents">
            Disable Residents
        </label>

        <label class="toggle">
            <input type="checkbox" name="skipAutoBackup">
            Skip Automatic OAR Backup
        </label>

        <label class="toggle">
            <input type="checkbox" name="concierge">
            Announce Visitors in Region Chat
        </label>

    </div>

</section>



<!-- ========================================================
     CPU
     ======================================================== -->

<section
    class="tab-panel panel"
    data-panel="cpu"
>

    <div class="panel-title">
        CPU %
    </div>


    <p style="color:#9f9a8d;font-size:12px;line-height:1.5;">

        Logical processors detected on this server:
        <strong style="color:#f0ca62;">
            <?=$cpuCount?>
        </strong>
        &nbsp;|&nbsp; Region affinity controls available:
        <strong style="color:#f0ca62;">
            <?=$cpuVisible?>
        </strong>

    </p>


    <div class="cpu-grid">

        <?php for ($cpu = 1; $cpu <= $cpuVisible; $cpu++): ?>

            <label class="cpu-item">

                <input
                    type="checkbox"
                    name="cpu[]"
                    value="<?=$cpu?>"
                    <?=($templateCoreMask & (1 << ($cpu - 1))) !== 0 ? 'checked' : ''?>
                >

                Core <?=$cpu?>

            </label>

        <?php endfor; ?>

    </div>

</section>

</form>


</div>



<script>

(function(){

    "use strict";


    const buttons =
        Array.from(
            document.querySelectorAll(
                ".tab-button"
            )
        );


    const panels =
        Array.from(
            document.querySelectorAll(
                ".tab-panel"
            )
        );


    buttons.forEach(
        function(button){

            button.addEventListener(
                "click",
                function(){

                    const target =
                        button.dataset.tab;


                    buttons.forEach(
                        function(item){

                            item.classList.toggle(
                                "active",
                                item === button
                            );
                        }
                    );


                    panels.forEach(
                        function(panel){

                            panel.classList.toggle(
                                "active",
                                panel.dataset.panel === target
                            );
                        }
                    );
                }
            );
        }
    );


    const smartStartCompat = document.getElementById("smartStart");
    const regionSizeCompat = document.getElementById("regionSize");

    function syncCompatToNative(){
        if (smartStartCompat) {
            const radio = document.querySelector('input[name="smartStart"][value="' + smartStartCompat.value.replace(/"/g, '\"') + '"]');
            if (radio) radio.checked = true;
        }
        if (regionSizeCompat) {
            const radio = document.querySelector('input[name="regionSize"][value="' + regionSizeCompat.value.replace(/"/g, '\"') + '"]');
            if (radio) radio.checked = true;
        }
    }

    if (smartStartCompat) smartStartCompat.addEventListener("change", syncCompatToNative);
    if (regionSizeCompat) regionSizeCompat.addEventListener("change", syncCompatToNative);

    document.querySelectorAll('input[name="smartStart"]').forEach(function(radio){
        radio.addEventListener("change", function(){ if (radio.checked && smartStartCompat) smartStartCompat.value = radio.value; });
    });
    document.querySelectorAll('input[name="regionSize"]').forEach(function(radio){
        radio.addEventListener("change", function(){ if (radio.checked && regionSizeCompat) regionSizeCompat.value = radio.value; });
    });
    const regionSelector =
        document.getElementById(
            "regionSelector"
        );


    if(regionSelector){

        regionSelector.addEventListener(
            "focus",
            function(){

                if(
                    regionSelector.value.trim().toLowerCase() ===
                    "name of region"
                ){

                    regionSelector.select();
                }
            }
        );
    }


    function dgNativeRegionName(){

        if(!regionSelector){
            return "";
        }


        const value =
            String(
                regionSelector.value || ""
            ).trim();


        if(
            value === "" ||
            value.toLowerCase() ===
                "name of region"
        ){

            window.alert(
                "Enter the Region Name first."
            );

            return "";
        }


        return value;
    }


    function dgEmbeddedFlag(){

        return (
            new URLSearchParams(
                window.location.search
            ).get(
                "embedded"
            ) ===
            "1"
        );
    }


    function dgRegionEditUrl(
        regionName
    ){

        let url =
            "/Other/admin-region-edit.php" +
            "?region=" +
            encodeURIComponent(
                regionName
            );


        if(dgEmbeddedFlag()){

            url +=
                "&embedded=1";
        }


        return url;
    }


    async function dgLoadNativeRegionContext(
        regionName
    ){

        const response =
            await fetch(
                dgRegionEditUrl(
                    regionName
                ) +
                "&_=" +
                Date.now(),
                {
                    credentials:
                        "same-origin",

                    cache:
                        "no-store"
                }
            );


        const html =
            await response.text();


        if(!response.ok){

            throw new Error(
                "Region \"" +
                regionName +
                "\" could not be loaded."
            );
        }


        const documentCopy =
            new DOMParser()
                .parseFromString(
                    html,
                    "text/html"
                );


        const uuidField =
            documentCopy.getElementById(
                "uuidField"
            ) ||
            documentCopy.querySelector(
                'input[name="uuid"]'
            );


        const csrfField =
            documentCopy.querySelector(
                'input[name="csrf_token"]'
            );


        const uuid =
            uuidField
                ? String(
                    uuidField.value || ""
                ).trim()
                : "";


        const csrf =
            csrfField
                ? String(
                    csrfField.value || ""
                ).trim()
                : "";


        if(uuid === ""){

            throw new Error(
                "Region \"" +
                regionName +
                "\" was not found."
            );
        }


        return {
            uuid:
                uuid,

            csrf:
                csrf
        };
    }


    async function dgRunNativeRegionAction(
        action,
        regionName
    ){

        const context =
            await dgLoadNativeRegionContext(
                regionName
            );


        const body =
            new URLSearchParams();


        /*
         * Send the compatible field aliases.
         * The native endpoint ignores fields it does not use.
         */
        body.set(
            "region",
            regionName
        );

        body.set(
            "regionName",
            regionName
        );

        body.set(
            "region_name",
            regionName
        );


        body.set(
            "uuid",
            context.uuid
        );

        body.set(
            "regionUuid",
            context.uuid
        );

        body.set(
            "region_uuid",
            context.uuid
        );


        if(context.csrf !== ""){

            body.set(
                "csrf_token",
                context.csrf
            );

            body.set(
                "csrf",
                context.csrf
            );

            body.set(
                "token",
                context.csrf
            );
        }


        const response =
            await fetch(
                "/Other/admin-region-native-action.php" +
                "?action=" +
                encodeURIComponent(
                    action
                ),
                {
                    method:
                        "POST",

                    credentials:
                        "same-origin",

                    cache:
                        "no-store",

                    headers:{
                        "Content-Type":
                            "application/x-www-form-urlencoded;charset=UTF-8"
                    },

                    body:
                        body.toString()
                }
            );


        const raw =
            await response.text();


        let data =
            null;


        try{

            data =
                JSON.parse(
                    raw
                );
        }
        catch(error){
        }


        if(
            !response.ok ||
            !data ||
            data.ok !== true
        ){

            throw new Error(
                data &&
                (
                    data.error ||
                    data.message
                )
                    ?
                    String(
                        data.error ||
                        data.message
                    )
                    :
                    (
                        raw ||
                        "DreamGrid native operation failed."
                    )
            );
        }


        if(data.message){

            window.alert(
                String(
                    data.message
                )
            );
        }


        try{

            if(
                window.parent &&
                window.parent !== window
            ){

                window.parent.postMessage(
                    {
                        type:
                            "dg-region-changed",

                        region:
                            regionName
                    },
                    window.location.origin
                );
            }
        }
        catch(error){
        }


        return data;
    }


    const newDeregisterButton =
        document.getElementById(
            "newDeregisterButton"
        );


    const newDeleteButton =
        document.getElementById(
            "newDeleteButton"
        );


    const newEditButton =
        document.getElementById(
            "newEditButton"
        );


    if(newEditButton){

        newEditButton.disabled =
            false;


        newEditButton.addEventListener(
            "click",
            function(){

                const regionName =
                    dgNativeRegionName();


                if(!regionName){
                    return;
                }


                window.location.href =
                    dgRegionEditUrl(
                        regionName
                    );
            }
        );
    }


    if(newDeregisterButton){

        newDeregisterButton.disabled =
            false;


        newDeregisterButton.addEventListener(
            "click",
            async function(){

                if(
                    !window.confirm(
                        "This will allow another region to be placed at this spot. Continue?"
                    )
                ){

                    return;
                }


                const regionName =
                    dgNativeRegionName();


                if(!regionName){
                    return;
                }


                newDeregisterButton.disabled =
                    true;


                try{

                    await dgRunNativeRegionAction(
                        "deregister",
                        regionName
                    );
                }
                catch(error){

                    window.alert(
                        error &&
                        error.message
                            ?
                            error.message
                            :
                            String(error)
                    );
                }
                finally{

                    newDeregisterButton.disabled =
                        false;
                }
            }
        );
    }


    if(newDeleteButton){

        newDeleteButton.disabled =
            false;


        newDeleteButton.addEventListener(
            "click",
            async function(){

                if(
                    !window.confirm(
                        "Are you sure you want to delete this region?"
                    )
                ){

                    return;
                }


                const regionName =
                    dgNativeRegionName();


                if(!regionName){
                    return;
                }


                newDeleteButton.disabled =
                    true;


                try{

                    await dgRunNativeRegionAction(
                        "delete",
                        regionName
                    );


                    regionSelector.value =
                        "Name Of Region";
                }
                catch(error){

                    window.alert(
                        error &&
                        error.message
                            ?
                            error.message
                            :
                            String(error)
                    );
                }
                finally{

                    newDeleteButton.disabled =
                        false;
                }
            }
        );
    }

const groupName =
        document.getElementById(
            "groupName"
        );

    const regionName =
        document.getElementById(
            "regionName"
        );


    const estateName =
        document.getElementById(
            "estateName"
        );


    regionName.addEventListener(
        "input",
        function(){

            if (
                groupName &&
                groupName.dataset.manual !== "1"
            ) {
                groupName.value = regionName.value;
            }

            if(
                estateName.dataset.manual !==
                "1"
            ){
                estateName.value =
                    regionName.value;
            }
        }
    );


    estateName.addEventListener(
        "input",
        function(){

            estateName.dataset.manual =
                estateName.value === ""
                    ? "0"
                    : "1";
        }
    );


    if (groupName) {
        groupName.addEventListener(
            "input",
            function(){
                groupName.dataset.manual =
                    groupName.value === ""
                        ? "0"
                        : "1";
            }
        );
    }


    const uuid =
        document.getElementById(
            "regionUuid"
        );


    if(
        uuid &&
        !uuid.value &&
        window.crypto &&
        typeof window.crypto.randomUUID ===
            "function"
    ){
        uuid.value =
            window.crypto.randomUUID();
    }

/* ========================================================
       CREATE REGION BACKEND
       ======================================================== */

    const ownerSelect =
        document.getElementById(
            "regionOwner"
        );

    const csrfField =
        document.getElementById(
            "createRegionCsrf"
        );

    const createButton =
        document.getElementById(
            "createRegionButton"
        );

    const createForm =
        document.getElementById(
            "createRegionForm"
        );



    /* ========================================================
       SAVE REGION CUSTOM CONFIRM V2
       ======================================================== */

    function showCreateRegionConfirm(){

        return new Promise(
            function(resolve){

                const modal =
                    document.getElementById(
                        "createRegionConfirmModal"
                    );

                const backdrop =
                    document.getElementById(
                        "createRegionConfirmBackdrop"
                    );

                const okButton =
                    document.getElementById(
                        "createRegionConfirmOk"
                    );

                const cancelButton =
                    document.getElementById(
                        "createRegionConfirmCancel"
                    );


                const nameBox =
                    document.getElementById(
                        "createRegionConfirmName"
                    );

                const estateBox =
                    document.getElementById(
                        "createRegionConfirmEstate"
                    );

                const locationBox =
                    document.getElementById(
                        "createRegionConfirmLocation"
                    );

                const portBox =
                    document.getElementById(
                        "createRegionConfirmPort"
                    );


                const currentRegionName =
                    document.getElementById(
                        "regionName"
                    );

                const currentEstateName =
                    document.getElementById(
                        "estateName"
                    );

                const currentX =
                    document.getElementById(
                        "coordX"
                    );

                const currentY =
                    document.getElementById(
                        "coordY"
                    );

                const currentPort =
                    document.getElementById(
                        "port"
                    );


                if(
                    !modal ||
                    !okButton ||
                    !cancelButton
                ){

                    resolve(
                        false
                    );

                    return;
                }


                nameBox.textContent =
                    currentRegionName
                        ? currentRegionName.value
                        : "";

                estateBox.textContent =
                    currentEstateName
                        ? currentEstateName.value
                        : "";

                locationBox.textContent =
                    (
                        currentX
                            ? currentX.value
                            : ""
                    ) +
                    "," +
                    (
                        currentY
                            ? currentY.value
                            : ""
                    );

                portBox.textContent =
                    (
                        currentPort &&
                        currentPort.value
                    )
                        ? currentPort.value
                        : "AUTO";


                let finished =
                    false;


                function closeConfirm(
                    result
                ){

                    if(finished){
                        return;
                    }


                    finished =
                        true;


                    modal.hidden =
                        true;

                    modal.setAttribute(
                        "aria-hidden",
                        "true"
                    );


                    document.body.classList.remove(
                        "ag-confirm-open"
                    );


                    document.removeEventListener(
                        "keydown",
                        keyHandler
                    );


                    okButton.onclick =
                        null;

                    cancelButton.onclick =
                        null;


                    if(backdrop){

                        backdrop.onclick =
                            null;
                    }


                    resolve(
                        result
                    );
                }


                function keyHandler(
                    event
                ){

                    if(
                        event.key ===
                        "Escape"
                    ){

                        closeConfirm(
                            false
                        );
                    }
                }


                okButton.onclick =
                    function(){

                        closeConfirm(
                            true
                        );
                    };


                cancelButton.onclick =
                    function(){

                        closeConfirm(
                            false
                        );
                    };


                if(backdrop){

                    backdrop.onclick =
                        function(){

                            closeConfirm(
                                false
                            );
                        };
                }


                document.addEventListener(
                    "keydown",
                    keyHandler
                );


                modal.hidden =
                    false;

                modal.setAttribute(
                    "aria-hidden",
                    "false"
                );


                document.body.classList.add(
                    "ag-confirm-open"
                );


                window.setTimeout(
                    function(){

                        okButton.focus();
                    },
                    0
                );
            }
        );
    }

    function showBackendResult(
        text,
        isError
    ){

        if(isError){

            window.alert(
                String(
                    text || "Region operation failed."
                )
            );

            return;
        }

        console.log(
            String(
                text || ""
            )
        );
    }

let createPending = false;
    let createComplete = false;

    async function loadRegionOwners(){

        if(
            !ownerSelect ||
            !csrfField
        ){
            return;
        }

        const previousOwner = ownerSelect.value;
        ownerSelect.disabled =
            true;

        ownerSelect.innerHTML =
            '<option value="">Loading local avatars...</option>';

        try{

            const response =
                await fetch(
                    "/Other/admin-create-region-owners.php",
                    {
                        credentials:
                            "same-origin",

                        cache:
                            "no-store"
                    }
                );

            const data =
                await response.json();

            if(
                !response.ok ||
                !data.ok
            ){

                throw new Error(
                    data.error ||
                    "Could not load local avatars."
                );
            }

            csrfField.value =
                data.csrf ||
                "";

            ownerSelect.innerHTML =
                '<option value="">Select local avatar...</option>';

            (data.owners || []).forEach(
                function(owner){

                    const option =
                        document.createElement(
                            "option"
                        );

                    option.value =
                        owner.uuid;

                    option.textContent =
                        owner.name +
                        " - Level " +
                        owner.level;

                    ownerSelect.appendChild(
                        option
                    );
                }
            );

            const signedInAvatar =
                <?=json_encode($avatar, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;

            const signedInOption =
                Array.from(ownerSelect.options).find(
                    function(option){
                        return option.textContent
                            .split(" - Level ")[0]
                            .trim()
                            .toLowerCase() === signedInAvatar.trim().toLowerCase();
                    }
                );

            if (signedInOption) {
                ownerSelect.value = signedInOption.value;
            }
            else if (Array.from(ownerSelect.options).some(option => option.value === previousOwner)) {
                ownerSelect.value = previousOwner;
            }
            else if (ownerSelect.options.length > 1) {
                ownerSelect.selectedIndex = 1;
            }

            ownerSelect.disabled =
                false;
        }
        catch(error){

            ownerSelect.innerHTML =
                '<option value="">Owner list unavailable</option>';

            showBackendResult(
                error.message ||
                "Could not load local avatars.",
                true
            );
        }
    }


    if(createForm){

        createForm.addEventListener(
            "submit",
            async function(event){

                event.preventDefault();
                if (createPending || createComplete) return;

                if(
                    !ownerSelect ||
                    !ownerSelect.value
                ){

                    showBackendResult(
                        "Could not resolve the signed-in Grid owner.",
                        true
                    );

                    return;
                }


                if(
                    !createForm.reportValidity()
                ){
                    return;
                }


                createPending = true;
                const confirmed =
                    await showCreateRegionConfirm();


                if(!confirmed){
                    createPending = false;
                    return;
                }


                createButton.disabled =
                    true;

                createButton.textContent =
                    "SAVING...";


                try{

                    const response =
                        await fetch(
                            "/Other/admin-create-region-action.php",
                            {
                                method:
                                    "POST",

                                credentials:
                                    "same-origin",

                                cache:
                                    "no-store",

                                body:
                                    new FormData(
                                        createForm
                                    )
                            }
                        );


                    const data =
                        await response.json();


                    if(
                        !response.ok ||
                        !data.ok
                    ){

                        throw new Error(
                            data.error ||
                            "Region creation failed."
                        );
                    }


                    createComplete = true;
                    const region =
                        data.region ||
                        {};


                    showBackendResult(
                        "REGION CREATED SUCCESSFULLY\n\n" +
                        "Name: " +
                        (region.name || "") +
                        "\n" +
                        "UUID: " +
                        (region.uuid || "") +
                        "\n" +
                        "Group: " +
                        (region.group || "") +
                        "\n" +
                        "Estate: " +
                        (region.estate || "") +
                        "\n" +
                        "Estate ID: " +
                        (region.estate_id || "") +
                        "\n" +
                        "Port: " +
                        (region.port || "") +
                        "\n" +
                        "Location: " +
                        (region.location || "") +
                        "\n" +
                        "Size: " +
                        (region.size || "") +
                        "\n\n" +
                        "The region has NOT been started.\n" +
                        "Open REGION MANAGER to verify it.",
                        false
                    );


                    createButton.textContent =
                        "SAVED";

                    ownerSelect.disabled =
                        true;


                    if (window.parent !== window) {
                        document.body.setAttribute('data-dg-region-changed', 'true');
                        window.parent.postMessage({type:'dg-region-changed'}, window.location.origin);
                    } else {
                        setTimeout(function(){ window.location.href = '/Other/admin-regions.php'; }, 5000);
                    }
                }
                catch(error){

                    showBackendResult(
                        error.message ||
                        "Region creation failed.",
                        true
                    );

                    createButton.disabled =
                        false;

                    createButton.textContent =
                        "SAVE";

                    await loadRegionOwners();
                }
                finally { createPending = false; }
            }
        );
    }


    loadRegionOwners();
})();

</script>



<!-- =========================================================
     SAVE REGION CONFIRMATION
     ========================================================= -->

<div
    id="createRegionConfirmModal"
    hidden
    aria-hidden="true"
>

    <div
        class="ag-confirm-backdrop"
        id="createRegionConfirmBackdrop"
    ></div>


    <section
        class="ag-confirm-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="createRegionConfirmTitle"
    >

        <div class="ag-confirm-eyebrow">
            REGION SAVE
        </div>


        <h2 id="createRegionConfirmTitle">
            SAVE THIS NEW REGION?
        </h2>


        <p class="ag-confirm-text">
            Check these details before the new Region definition and Estate assignment are saved.
        </p>


        <div class="ag-confirm-summary">

            <div class="ag-confirm-row">

                <div class="ag-confirm-label">
                    REGION
                </div>

                <div
                    class="ag-confirm-value"
                    id="createRegionConfirmName"
                >
                </div>

            </div>


            <div class="ag-confirm-row">

                <div class="ag-confirm-label">
                    ESTATE
                </div>

                <div
                    class="ag-confirm-value"
                    id="createRegionConfirmEstate"
                >
                </div>

            </div>


            <div class="ag-confirm-row">

                <div class="ag-confirm-label">
                    GRID LOCATION
                </div>

                <div
                    class="ag-confirm-value"
                    id="createRegionConfirmLocation"
                >
                </div>

            </div>


            <div class="ag-confirm-row">

                <div class="ag-confirm-label">
                    PORT
                </div>

                <div
                    class="ag-confirm-value"
                    id="createRegionConfirmPort"
                >
                </div>

            </div>

        </div>


        <div class="ag-confirm-warning">

            The Region INI and OpenSimulator estate mapping
            will be created.

            <strong>
                The region will NOT be started.
            </strong>

        </div>

<div class="php-level">

    <strong>
        <?=($level >= 200 ? 'GRID OWNER' : 'MEMBER')?>
    </strong>

    USER LEVEL
    <?=ag_h($level)?>

</div>



        <div class="ag-confirm-actions">

            <button
                class="button"
                id="createRegionConfirmCancel"
                type="button"
            >
                CANCEL
            </button>


            <button
                class="button primary"
                id="createRegionConfirmOk"
                type="button"
            >
                SAVE
            </button>

        </div>

    </section>

</div>
<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>
<script id="dgGlobalMapAddPrefill">
(function(){

    "use strict";


    const params =
        new URLSearchParams(
            window.location.search
        );


    if (
        params.get(
            "mapadd"
        ) !== "1"
    ) {

        return;
    }


    function setDreamGridField(
        id,
        value
    ){

        if (
            value === null ||
            value === ""
        ) {

            return;
        }


        const field =
            document.getElementById(
                id
            );


        if (!field) {

            return;
        }


        field.value =
            value;


        field.dispatchEvent(
            new Event(
                "input",
                {
                    bubbles:true
                }
            )
        );


        field.dispatchEvent(
            new Event(
                "change",
                {
                    bubbles:true
                }
            )
        );
    }


    setDreamGridField(
        "regionName",
        params.get(
            "regionName"
        )
    );


    setDreamGridField(
        "coordX",
        params.get(
            "coordX"
        )
    );


    setDreamGridField(
        "coordY",
        params.get(
            "coordY"
        )
    );


    const regionNameField =
        document.getElementById(
            "regionName"
        );


    if (regionNameField) {

        regionNameField.focus();

        regionNameField.select();
    }

})();
</script>
<script src="/Other/assets/js/dg-region-ini-import.js?v=20260925-v1"></script>
</body>

</html>






