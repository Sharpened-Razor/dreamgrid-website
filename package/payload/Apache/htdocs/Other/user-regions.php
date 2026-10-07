<?php

/*
 ============================================================
 Grid
 MY REGIONS V1
 ============================================================

 Resident region management page.

 Region list:
   /Other/user-regions-data.php

 Region commands:
   /Other/command.php

 command.php independently re-checks:
   - signed session
   - region exists
   - EstateOwner
   - UserLevel permissions
 ============================================================
*/

require_once __DIR__ . '/core/bootstrap.php';


$session =
    ag_current_session();


if(
    !$session
){

    header(
        'Location: /Other/login.php'
    );

    exit;
}


$avatar =
    trim(
        (string)(
            $session['avatar'] ??
            ''
        )
    );


$level =
    (int)(
        $session['level'] ??
        0
    );


if(
    $avatar === ''
){

    header(
        'Location: /Other/login.php'
    );

    exit;
}

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1">

<title>
    Grid - My Regions
</title>

<style>

/* ============================================================
   Grid - MY REGIONS V1
   ============================================================ */

*{
    box-sizing:border-box;
}

html,
body{
    margin:0;
    min-height:100%;
}

body{

    min-height:100vh;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    color:#ecf8ff;

    background:
        linear-gradient(
            rgba(1,12,18,.68),
            rgba(1,10,16,.80)
        ),
        url('/Other/images/australia-grid-hero.png')
        center center / cover fixed
        no-repeat;

    padding:
        34px 22px 48px;
}


/* ============================================================
   MAIN SHELL
   ============================================================ */

.regions-shell{

    width:
        min(1380px, 100%);

    margin:
        0 auto;

    border:
        1px solid rgba(83,198,231,.30);

    border-radius:
        24px;

    background:
        linear-gradient(
            180deg,
            rgba(7,24,32,.94),
            rgba(3,14,20,.96)
        );

    box-shadow:
        0 22px 60px rgba(0,0,0,.62),
        inset 0 1px 0 rgba(255,255,255,.05);

    overflow:
        hidden;
}


/* ============================================================
   HEADER
   ============================================================ */




.regions-kicker{

    margin-bottom:
        7px;

    color:
        #ffce54;

    font-size:
        13px;

    font-weight:
        800;

    letter-spacing:
        2.2px;
}


.regions-user{

    margin-top:
        12px;

    color:
        #87aeba;

    font-size:
        13px;
}


.regions-user strong{

    color:
        #ffffff;
}


.header-actions{

    flex:
        0 0 auto;
}


.button,
button{

    border:
        1px solid rgba(87,208,240,.42);

    border-radius:
        10px;

    background:
        linear-gradient(
            180deg,
            #153b49,
            #0b2732
        );

    color:
        #eafaff;

    font-family:
        inherit;

    font-size:
        12px;

    font-weight:
        800;

    letter-spacing:
        .7px;

    text-decoration:
        none;

    cursor:
        pointer;

    transition:
        transform .12s ease,
        border-color .12s ease,
        filter .12s ease;

    padding:
        11px 17px;
}


.button:hover,
button:hover:not(:disabled){

    transform:
        translateY(-1px);

    border-color:
        rgba(108,227,255,.78);

    filter:
        brightness(1.12);
}


button:disabled{

    opacity:
        .34;

    cursor:
        not-allowed;

    transform:
        none;
}


/* ============================================================
   SUMMARY
   ============================================================ */

.regions-summary{

    display:
        grid;

    grid-template-columns:
        repeat(4, minmax(0,1fr));

    gap:
        14px;

    padding:
        24px 34px 8px;
}


.summary-card{

    min-height:
        105px;

    padding:
        19px 20px;

    border:
        1px solid rgba(77,181,211,.20);

    border-radius:
        16px;

    background:
        linear-gradient(
            180deg,
            rgba(15,42,52,.78),
            rgba(5,22,29,.82)
        );

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.035);
}


.summary-label{

    color:
        #86afbd;

    font-size:
        11px;

    font-weight:
        800;

    letter-spacing:
        1.35px;
}


.summary-value{

    margin-top:
        9px;

    color:
        #ffffff;

    font-size:
        31px;

    font-weight:
        900;
}


.summary-note{

    margin-top:
        5px;

    color:
        #7fa7b5;

    font-size:
        11px;
}


/* ============================================================
   TOOLBAR
   ============================================================ */

.regions-toolbar{

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        18px;

    padding:
        21px 34px;
}


.toolbar-copy strong{

    display:
        block;

    color:
        #f4fbff;

    font-size:
        16px;
}


.toolbar-copy span{

    display:
        block;

    margin-top:
        5px;

    color:
        #7fa7b5;

    font-size:
        12px;
}


/* ============================================================
   MESSAGE
   ============================================================ */

.regions-message{

    display:
        none;

    margin:
        0 34px 18px;

    padding:
        13px 15px;

    border:
        1px solid rgba(77,181,211,.30);

    border-radius:
        12px;

    background:
        rgba(12,41,52,.76);

    color:
        #dff7ff;

    white-space:
        pre-wrap;

    font-size:
        13px;

    line-height:
        1.5;
}


.regions-message.show{

    display:
        block;
}


.regions-message.error{

    border-color:
        rgba(224,83,83,.50);

    background:
        rgba(82,18,18,.58);

    color:
        #ffd1d1;
}


.regions-message.success{

    border-color:
        rgba(71,195,114,.46);

    background:
        rgba(16,69,38,.58);

    color:
        #c9ffd9;
}


/* ============================================================
   REGION GRID
   ============================================================ */

.regions-grid{

    display:
        grid;

    grid-template-columns:
        repeat(2, minmax(0,1fr));

    gap:
        18px;

    padding:
        0 34px 34px;
}


.region-card{

    position:
        relative;

    overflow:
        hidden;

    border:
        1px solid rgba(81,190,220,.24);

    border-radius:
        18px;

    background:
        linear-gradient(
            155deg,
            rgba(14,40,51,.92),
            rgba(4,20,27,.94)
        );

    box-shadow:
        0 14px 28px rgba(0,0,0,.26),
        inset 0 1px 0 rgba(255,255,255,.035);
}


.region-card::before{

    content:
        "";

    position:
        absolute;

    top:
        0;

    left:
        0;

    right:
        0;

    height:
        3px;

    background:
        linear-gradient(
            90deg,
            #159dd2,
            #63e7ff,
            #e4b33a
        );
}


.region-head{

    display:
        flex;

    align-items:
        flex-start;

    justify-content:
        space-between;

    gap:
        18px;

    padding:
        22px 22px 16px;
}


.region-name{

    margin:
        0;

    color:
        #ffffff;

    font-size:
        22px;

    line-height:
        1.15;
}


.region-subtitle{

    margin-top:
        7px;

    color:
        #83aab8;

    font-size:
        12px;
}


.region-status{

    flex:
        0 0 auto;

    border:
        1px solid rgba(120,160,175,.30);

    border-radius:
        999px;

    padding:
        7px 10px;

    background:
        rgba(15,35,42,.72);

    color:
        #b5ccd5;

    font-size:
        10px;

    font-weight:
        900;

    letter-spacing:
        .9px;

    text-transform:
        uppercase;
}


.region-status.online{

    color:
        #9ff0b8;

    border-color:
        rgba(72,198,112,.42);

    background:
        rgba(22,93,49,.28);
}


.region-status.offline{

    color:
        #ffadad;

    border-color:
        rgba(221,82,82,.44);

    background:
        rgba(103,26,26,.28);
}


.region-status.busy{

    color:
        #ffe39a;

    border-color:
        rgba(223,177,55,.45);

    background:
        rgba(101,72,13,.30);
}


/* ============================================================
   DETAILS
   ============================================================ */

.region-details{

    display:
        grid;

    grid-template-columns:
        repeat(4, minmax(0,1fr));

    border-top:
        1px solid rgba(79,173,199,.14);

    border-bottom:
        1px solid rgba(79,173,199,.14);
}


.region-detail{

    padding:
        15px 16px;

    border-right:
        1px solid rgba(79,173,199,.12);
}


.region-detail:last-child{

    border-right:
        0;
}


.detail-label{

    color:
        #7199a7;

    font-size:
        9px;

    font-weight:
        800;

    letter-spacing:
        1px;
}


.detail-value{

    margin-top:
        6px;

    color:
        #eaf8fc;

    font-size:
        14px;

    font-weight:
        800;

    overflow-wrap:
        anywhere;
}


/* ============================================================
   CONTROLS
   ============================================================ */

.region-controls{

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        9px;

    padding:
        18px 22px 22px;
}


.region-controls button{

    flex:
        1 1 105px;
}


.region-controls .start{

    border-color:
        rgba(64,190,106,.46);

    background:
        linear-gradient(
            180deg,
            #17653a,
            #0c4025
        );
}


.region-controls .stop{

    border-color:
        rgba(220,82,82,.48);

    background:
        linear-gradient(
            180deg,
            #772c2c,
            #481919
        );
}


.region-controls .restart{

    border-color:
        rgba(73,168,232,.48);

    background:
        linear-gradient(
            180deg,
            #155a7d,
            #0b3850
        );
}


.region-controls .oar{

    border-color:
        rgba(226,179,55,.56);

    background:
        linear-gradient(
            180deg,
            #836219,
            #4e390c
        );

    color:
        #fff5c5;
}


/* ============================================================
   EMPTY / LOADING
   ============================================================ */

.regions-empty{

    grid-column:
        1 / -1;

    padding:
        46px 24px;

    border:
        1px dashed rgba(85,184,211,.28);

    border-radius:
        16px;

    background:
        rgba(4,22,29,.66);

    text-align:
        center;

    color:
        #88aeba;

    font-size:
        14px;
}


/* ============================================================
   FOOT NOTE
   ============================================================ */

.regions-note{

    margin:
        0 34px 32px;

    padding:
        14px 16px;

    border-left:
        3px solid #d9ab32;

    background:
        rgba(86,65,10,.16);

    color:
        #a9c2cb;

    font-size:
        12px;

    line-height:
        1.55;
}


/* ============================================================
   RESPONSIVE
   ============================================================ */

@media(max-width:1000px){

    .regions-summary{
        grid-template-columns:
            repeat(2, minmax(0,1fr));
    }

    .regions-grid{
        grid-template-columns:
            1fr;
    }
}


@media(max-width:720px){

    body{
        padding:
            14px 10px 30px;
    }

    

    .header-actions .button{
        display:
            block;

        text-align:
            center;
    }

    .regions-summary{
        grid-template-columns:
            1fr 1fr;

        padding:
            18px 20px 6px;
    }

    .regions-toolbar{
        align-items:
            stretch;

        flex-direction:
            column;

        padding:
            18px 20px;
    }

    .regions-message{
        margin:
            0 20px 16px;
    }

    .regions-grid{
        padding:
            0 20px 24px;
    }

    .regions-note{
        margin:
            0 20px 24px;
    }

    .region-details{
        grid-template-columns:
            1fr 1fr;
    }

    .region-detail:nth-child(2){
        border-right:
            0;
    }

    .region-detail:nth-child(-n+2){
        border-bottom:
            1px solid rgba(79,173,199,.12);
    }
}


@media(max-width:430px){

    .regions-summary{
        grid-template-columns:
            1fr;
    }

    .region-head{
        flex-direction:
            column;
    }

    .region-details{
        grid-template-columns:
            1fr;
    }

    .region-detail,
    .region-detail:nth-child(2){

        border-right:
            0;

        border-bottom:
            1px solid rgba(79,173,199,.12);
    }

    .region-detail:last-child{
        border-bottom:
            0;
    }
}


/* ==========================================================
   AUSTRALIA USER BUTTON NO TEXT SHADOW V1
   ========================================================== */

button,
input[type="button"],
input[type="submit"],
input[type="reset"],
a[class*="button"],
a[class*="btn"],
a[class*="back"],
a[class*="logout"] {
    text-shadow: none !important;
}

/* END AUSTRALIA USER BUTTON NO TEXT SHADOW V1 */


/* ============================================================
   Grid CUSTOM COMMAND CONFIRM V1
   ============================================================ */

.australia-confirm-overlay{
    position:fixed;
    inset:0;
    z-index:99999;

    display:none;
    align-items:center;
    justify-content:center;

    padding:24px;

    background:
        rgba(0, 8, 13, 0.78);

    backdrop-filter:
        blur(5px);
}

.australia-confirm-overlay.show{
    display:flex;
}

.australia-confirm-box{
    width:min(540px, 100%);

    overflow:hidden;

    border:
        1px solid rgba(255, 197, 46, 0.85);

    border-radius:
        22px;

    background:
        linear-gradient(
            180deg,
            rgba(7, 39, 50, 0.98),
            rgba(2, 20, 28, 0.99)
        );

    box-shadow:
        0 24px 70px rgba(0, 0, 0, 0.72),
        0 0 30px rgba(255, 187, 35, 0.10);
}

.australia-confirm-head{
    padding:
        24px 28px 18px;

    border-bottom:
        1px solid rgba(80, 198, 226, 0.20);
}

.australia-confirm-kicker{
    margin-bottom:
        7px;

    color:
        #ffc83f;

    font-size:
        12px;

    font-weight:
        900;

    letter-spacing:
        2px;

    text-transform:
        uppercase;
}

.australia-confirm-title{
    margin:
        0;

    color:
        #ffffff;

    font-size:
        28px;

    font-weight:
        900;

    letter-spacing:
        .5px;
}

.australia-confirm-body{
    padding:
        25px 28px 12px;
}

.australia-confirm-region{
    margin-bottom:
        18px;

    padding:
        14px 16px;

    border:
        1px solid rgba(54, 196, 226, 0.28);

    border-radius:
        12px;

    background:
        rgba(0, 0, 0, 0.22);

    color:
        #ffffff;

    font-size:
        19px;

    font-weight:
        900;
}

.australia-confirm-message{
    margin:
        0;

    color:
        #bfe9f3;

    font-size:
        15px;

    line-height:
        1.65;
}

.australia-confirm-actions{
    display:flex;
    justify-content:flex-end;
    gap:12px;

    padding:
        22px 28px 28px;
}

.australia-confirm-actions button{
    min-width:
        130px;

    min-height:
        48px;

    padding:
        11px 20px;

    border-radius:
        11px;

    font-family:
        inherit;

    font-size:
        13px;

    font-weight:
        900;

    letter-spacing:
        .7px;

    cursor:
        pointer;

    text-shadow:
        none !important;
}

.australia-confirm-cancel{
    border:
        1px solid rgba(88, 195, 220, 0.42);

    background:
        linear-gradient(
            180deg,
            #123b48,
            #082630
        );

    color:
        #ffffff;
}

.australia-confirm-yes{
    border:
        1px solid #ffcf45;

    background:
        linear-gradient(
            180deg,
            #ffe66d 0%,
            #ffc72f 40%,
            #dc9100 100%
        );

    color:
        #000000;

    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.72),
        0 5px 14px rgba(0, 0, 0, 0.34);

    text-shadow:
        none !important;
}

.australia-confirm-yes:hover,
.australia-confirm-cancel:hover{
    transform:
        translateY(-1px);
}

@media (max-width:600px){

    .australia-confirm-actions{
        flex-direction:
            column-reverse;
    }

    .australia-confirm-actions button{
        width:
            100%;
    }
}
</style>


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

<body>

<?php
$siteHeaderKicker = "ACCOUNT";
$siteHeaderTitle = "MY REGIONS";
$siteHeaderRole = "MEMBER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "BACK TO DASHBOARD";
$siteHeaderLink = "/Other/dashboard-return.php";
require_once __DIR__ . "/includes/site-header.php";
?>

<main class="regions-shell">

<section class="regions-summary">

    <div class="summary-card">

        <div class="summary-label">
            MY REGIONS
        </div>

        <div
            id="summaryRegions"
            class="summary-value">
            &#8212;
        </div>

        <div class="summary-note">
            Assigned to your avatar
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            ONLINE
        </div>

        <div
            id="summaryOnline"
            class="summary-value">
            &#8212;
        </div>

        <div class="summary-note">
            Currently available
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            AVATARS
        </div>

        <div
            id="summaryAvatars"
            class="summary-value">
            &#8212;
        </div>

        <div class="summary-note">
            Across your regions
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            REGION CONTROLS
        </div>

        <div
            id="summaryControls"
            class="summary-value"
            style="font-size:21px">

            CHECKING

        </div>

        <div
            id="summaryControlNote"
            class="summary-note">

            Checking permissions

        </div>

    </div>

</section>


<section class="regions-toolbar">

    <div class="toolbar-copy">

        <strong>
            YOUR REGIONS
        </strong>

        <span>
            Region information is read directly from the grid service.
        </span>

    </div>


    <button
        type="button"
        id="refreshButton">

        REFRESH

    </button>

</section>


<div
    id="message"
    class="regions-message">
</div>


<section
    id="regionsGrid"
    class="regions-grid">

    <div class="regions-empty">
        Loading your regions...
    </div>

</section>


<div class="regions-note">

    Only regions where your signed-in avatar is listed as
    <strong>Estate Owner</strong> appear here.
    Region commands are verified again by the Grid server
    before the grid service executes them.

</div>


</main>


<script>

"use strict";


const DATA_URL =
    "/Other/user-regions-data.php";


const COMMAND_URL =
    "/Other/command.php";


const grid =
    document.getElementById(
        "regionsGrid"
    );


const message =
    document.getElementById(
        "message"
    );


const refreshButton =
    document.getElementById(
        "refreshButton"
    );


let currentData =
    null;


/* ============================================================
   ESCAPING
   ============================================================ */

function esc(
    value
){

    return String(
        value ?? ""
    )
    .replaceAll(
        "&",
        "&amp;"
    )
    .replaceAll(
        "<",
        "&lt;"
    )
    .replaceAll(
        ">",
        "&gt;"
    )
    .replaceAll(
        '"',
        "&quot;"
    )
    .replaceAll(
        "'",
        "&#039;"
    );
}


/* ============================================================
   STATUS
   ============================================================ */

function statusText(
    value
){

    const text =
        String(
            value ?? ""
        ).trim();

    return text !== ""
        ? text
        : "Unknown";
}


function statusKind(
    value
){

    const text =
        statusText(
            value
        ).toLowerCase();


    if(
        text.includes("backingup") ||
        text.includes("backing up") ||
        text.includes("starting") ||
        text.includes("stopping") ||
        text.includes("restart") ||
        text.includes("loading") ||
        text.includes("saving")
    ){

        return "busy";
    }


    if(
        text.includes("offline") ||
        text.includes("stopped") ||
        text.includes("shutdown") ||
        text.includes("not running") ||
        text === "down"
    ){

        return "offline";
    }


    if(
        text === "booted" ||
        text.includes("online") ||
        text.includes("running") ||
        text.includes("started") ||
        text === "up"
    ){

        return "online";
    }


    return "unknown";
}


function isOnline(
    value
){

    return statusKind(
        value
    ) === "online";
}


function isOffline(
    value
){

    return statusKind(
        value
    ) === "offline";
}


function isBusy(
    value
){

    return statusKind(
        value
    ) === "busy";
}


/* ============================================================
   MESSAGE
   ============================================================ */

function showMessage(
    text,
    type = ""
){

    message.textContent =
        String(
            text ?? ""
        );


    message.className =
        "regions-message show" +
        (
            type
                ? " " + type
                : ""
        );
}


function clearMessage(){

    message.textContent =
        "";

    message.className =
        "regions-message";
}


/* ============================================================
   NUMBER FORMAT
   ============================================================ */

function formatNumber(
    value
){

    const n =
        Number(
            value
        );


    if(
        !Number.isFinite(
            n
        )
    ){

        return "0";
    }


    return n.toLocaleString(
        "en-AU"
    );
}


/* ============================================================
   SUMMARY
   ============================================================ */

function updateSummary(
    data
){

    const regions =
        Array.isArray(
            data.regions
        )
            ? data.regions
            : [];


    const online =
        regions.filter(
            region =>
                isOnline(
                    region.Status
                ) ||
                isBusy(
                    region.Status
                )
        ).length;


    const avatars =
        regions.reduce(
            (
                total,
                region
            ) =>
                total +
                (
                    Number(
                        region.AvatarCount
                    ) || 0
                ),
            0
        );


    document.getElementById(
        "summaryRegions"
    ).textContent =
        String(
            regions.length
        );


    document.getElementById(
        "summaryOnline"
    ).textContent =
        String(
            online
        );


    document.getElementById(
        "summaryAvatars"
    ).textContent =
        String(
            avatars
        );


    const controls =
        Boolean(
            data.permissions &&
            data.permissions.regionControls
        );


    const saveOar =
        Boolean(
            data.permissions &&
            data.permissions.saveOar
        );


    document.getElementById(
        "summaryControls"
    ).textContent =
        controls
            ? "ALLOWED"
            : "LIMITED";


    document.getElementById(
        "summaryControlNote"
    ).textContent =
        controls
            ? (
                saveOar
                    ? "Start, stop, restart and Save OAR available"
                    : "Start, stop and restart available"
            )
            : "Your UserLevel does not allow region controls";
}


/* ============================================================
   REGION CARD
   ============================================================ */

function buildRegionCard(
    region,
    permissions
){

    const name =
        statusText(
            region.RegionName
        );


    const status =
        statusText(
            region.Status
        );


    const kind =
        statusKind(
            status
        );


    const canControl =
        Boolean(
            permissions &&
            permissions.regionControls
        );


    const canSaveOar =
        Boolean(
            permissions &&
            permissions.saveOar
        );


    const busy =
        isBusy(
            status
        );


    const online =
        isOnline(
            status
        );


    const offline =
        isOffline(
            status
        );


    const startDisabled =
        !canControl ||
        online ||
        busy;


    const stopDisabled =
        !canControl ||
        offline ||
        busy;


    const restartDisabled =
        !canControl ||
        offline ||
        busy;


    const oarDisabled =
        !canSaveOar ||
        offline ||
        busy;


    return `
        <article class="region-card">

            <div class="region-head">

                <div>

                    <h2 class="region-name">
                        ${esc(name)}
                    </h2>

                    <div class="region-subtitle">
                        ${esc(region.Size || "Unknown size")}
                        &nbsp;&#8226;&nbsp;
                        ${esc(region.EstateName || "No estate name")}
                    </div>

                </div>

                <div class="region-status ${esc(kind)}">
                    ${esc(status)}
                </div>

            </div>


            <div class="region-details">

                <div class="region-detail">

                    <div class="detail-label">
                        PRIMS
                    </div>

                    <div class="detail-value">
                        ${formatNumber(region.PrimCount)}
                    </div>

                </div>


                <div class="region-detail">

                    <div class="detail-label">
                        AVATARS
                    </div>

                    <div class="detail-value">
                        ${formatNumber(region.AvatarCount)}
                    </div>

                </div>


                <div class="region-detail">

                    <div class="detail-label">
                        RAM
                    </div>

                    <div class="detail-value">
                        ${esc(region.Ram || "\u2014")}
                    </div>

                </div>


                <div class="region-detail">

                    <div class="detail-label">
                        ESTATE OWNER
                    </div>

                    <div class="detail-value">
                        ${esc(region.EstateOwner || "\u2014")}
                    </div>

                </div>

            </div>


            <div class="region-controls">

                <button
                    type="button"
                    class="start"
                    data-command="StartRegion"
                    data-region="${esc(name)}"
                    ${startDisabled ? "disabled" : ""}>

                    START

                </button>


                <button
                    type="button"
                    class="stop"
                    data-command="StopRegion"
                    data-region="${esc(name)}"
                    ${stopDisabled ? "disabled" : ""}>

                    STOP

                </button>


                <button
                    type="button"
                    class="restart"
                    data-command="RestartRegion"
                    data-region="${esc(name)}"
                    ${restartDisabled ? "disabled" : ""}>

                    RESTART

                </button>


                <button
                    type="button"
                    class="oar"
                    data-command="SaveOAR"
                    data-region="${esc(name)}"
                    ${oarDisabled ? "disabled" : ""}>

                    SAVE OAR

                </button>

            </div>

        </article>
    `;
}


/* ============================================================
   LOAD REGIONS
   ============================================================ */

async function loadRegions(
    showStatus = false
){

    refreshButton.disabled =
        true;


    if(
        showStatus
    ){

        showMessage(
            "Refreshing your regions..."
        );
    }


    try{

        const response =
            await fetch(
                DATA_URL +
                "?nocache=" +
                Date.now(),
                {
                    credentials:
                        "same-origin",

                    cache:
                        "no-store"
                }
            );


        const raw =
            await response.text();


        let data;


        try{

            data =
                JSON.parse(
                    raw
                );

        }
        catch(error){

            throw new Error(
                raw ||
                "Grid returned an invalid region response."
            );
        }


        if(
            !response.ok ||
            !data.ok
        ){

            throw new Error(
                data.error ||
                "Unable to load your regions."
            );
        }


        currentData =
            data;


        updateSummary(
            data
        );


        const regions =
            Array.isArray(
                data.regions
            )
                ? data.regions
                : [];


        if(
            regions.length === 0
        ){

            grid.innerHTML =
                `
                <div class="regions-empty">
                    No regions are currently assigned to
                    <strong>${esc(data.avatar || "")}</strong>
                    as Estate Owner.
                </div>
                `;


            if(
                showStatus
            ){

                showMessage(
                    "No regions are assigned to this avatar."
                );
            }


            return;
        }


        grid.innerHTML =
            regions
                .map(
                    region =>
                        buildRegionCard(
                            region,
                            data.permissions || {}
                        )
                )
                .join("");


        if(
            showStatus
        ){

            showMessage(
                `Loaded ${regions.length} region${regions.length === 1 ? "" : "s"}.`,
                "success"
            );
        }
        else{

            clearMessage();
        }

    }
    catch(error){

        currentData =
            null;


        grid.innerHTML =
            `
            <div class="regions-empty">
                MY REGIONS could not be loaded.
            </div>
            `;


        showMessage(
            error.message ||
            "Unable to load your regions.",
            "error"
        );
    }
    finally{

        refreshButton.disabled =
            false;
    }
}


/* ============================================================
   COMMAND CONFIRMATION
   ============================================================ */

function australiaGridConfirm(
    title,
    region,
    message,
    confirmText
){
    return new Promise(
        resolve => {

            let overlay =
                document.getElementById(
                    "australiaCommandConfirm"
                );


            if(
                !overlay
            ){
                overlay =
                    document.createElement(
                        "div"
                    );

                overlay.id =
                    "australiaCommandConfirm";

                overlay.className =
                    "australia-confirm-overlay";

                overlay.innerHTML = `
                    <div
                        class="australia-confirm-box"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="australiaConfirmTitle">

                        <div class="australia-confirm-head">

                            <div class="australia-confirm-kicker">
                                Grid
                            </div>

                            <h2
                                id="australiaConfirmTitle"
                                class="australia-confirm-title">
                            </h2>

                        </div>

                        <div class="australia-confirm-body">

                            <div
                                id="australiaConfirmRegion"
                                class="australia-confirm-region">
                            </div>

                            <p
                                id="australiaConfirmMessage"
                                class="australia-confirm-message">
                            </p>

                        </div>

                        <div class="australia-confirm-actions">

                            <button
                                type="button"
                                id="australiaConfirmCancel"
                                class="australia-confirm-cancel">
                                CANCEL
                            </button>

                            <button
                                type="button"
                                id="australiaConfirmYes"
                                class="australia-confirm-yes">
                                CONTINUE
                            </button>

                        </div>

                    </div>
                `;

                document.body.appendChild(
                    overlay
                );
            }


            const titleNode =
                document.getElementById(
                    "australiaConfirmTitle"
                );

            const regionNode =
                document.getElementById(
                    "australiaConfirmRegion"
                );

            const messageNode =
                document.getElementById(
                    "australiaConfirmMessage"
                );

            const yesButton =
                document.getElementById(
                    "australiaConfirmYes"
                );

            const cancelButton =
                document.getElementById(
                    "australiaConfirmCancel"
                );


            titleNode.textContent =
                title;

            regionNode.textContent =
                region;

            messageNode.textContent =
                message;

            yesButton.textContent =
                confirmText;


            let completed =
                false;


            const finish =
                result => {

                    if(
                        completed
                    ){
                        return;
                    }

                    completed =
                        true;

                    overlay.classList.remove(
                        "show"
                    );

                    document.removeEventListener(
                        "keydown",
                        keyHandler
                    );

                    resolve(
                        result
                    );
                };


            const keyHandler =
                event => {

                    if(
                        event.key === "Escape"
                    ){
                        event.preventDefault();

                        finish(
                            false
                        );
                    }
                };


            yesButton.onclick =
                () =>
                    finish(
                        true
                    );

            cancelButton.onclick =
                () =>
                    finish(
                        false
                    );

            overlay.onclick =
                event => {

                    if(
                        event.target === overlay
                    ){
                        finish(
                            false
                        );
                    }
                };


            document.addEventListener(
                "keydown",
                keyHandler
            );


            overlay.classList.add(
                "show"
            );


            window.setTimeout(
                () =>
                    yesButton.focus(),
                0
            );
        }
    );
}


async function confirmCommand(
    command,
    region
){

    if(
        command === "StopRegion"
    ){
        return australiaGridConfirm(
            "STOP REGION",
            region,
            "Residents currently inside this region may be disconnected. Continue with stopping this region?",
            "STOP REGION"
        );
    }


    if(
        command === "RestartRegion"
    ){
        return australiaGridConfirm(
            "RESTART REGION",
            region,
            "Residents currently inside this region may be disconnected during the restart. Continue with restarting this region?",
            "RESTART REGION"
        );
    }


    if(
        command === "SaveOAR"
    ){
        return australiaGridConfirm(
            "SAVE OAR",
            region,
            "Create a new OAR backup of this region?",
            "SAVE OAR"
        );
    }


    return true;
}

/* ============================================================
   RUN REGION COMMAND
   ============================================================ */

async function runRegionCommand(
    button
){

    const command =
        String(
            button.dataset.command ||
            ""
        );


    const region =
        String(
            button.dataset.region ||
            ""
        );


    if(
        command === "" ||
        region === ""
    ){

        return;
    }


    if(
        !(await confirmCommand(
            command,
            region
        ))
    ){

        return;
    }


    const oldText =
        button.textContent;


    button.disabled =
        true;


    button.textContent =
        "WORKING...";


    showMessage(
        `${command} requested for ${region}...`
    );


    try{

        const body =
            new URLSearchParams();


        body.set(
            "command",
            command
        );


        body.set(
            "region",
            region
        );


        const response =
            await fetch(
                COMMAND_URL,
                {
                    method:
                        "POST",

                    credentials:
                        "same-origin",

                    cache:
                        "no-store",

                    headers:
                        {
                            "Content-Type":
                                "application/x-www-form-urlencoded;charset=UTF-8"
                        },

                    body:
                        body.toString()
                }
            );


        const raw =
            await response.text();


        let data;


        try{

            data =
                JSON.parse(
                    raw
                );

        }
        catch(error){

            throw new Error(
                raw ||
                "Grid returned an invalid command response."
            );
        }


        if(
            !response.ok ||
            !data.ok
        ){

            throw new Error(
                data.error ||
                "Region command failed."
            );
        }


        showMessage(
            data.message ||
            `${command} accepted for ${region}.`,
            "success"
        );


        /*
         * Give DreamGrid a moment to change state,
         * then reload the region data.
         */

        setTimeout(
            () =>
                loadRegions(
                    false
                ),
            1200
        );


        setTimeout(
            () =>
                loadRegions(
                    false
                ),
            4500
        );

    }
    catch(error){

        showMessage(
            error.message ||
            "Region command failed.",
            "error"
        );


        button.disabled =
            false;
    }
    finally{

        button.textContent =
            oldText;
    }
}


/* ============================================================
   EVENTS
   ============================================================ */

refreshButton.addEventListener(
    "click",
    () =>
        loadRegions(
            true
        )
);


grid.addEventListener(
    "click",
    event => {

        const button =
            event.target.closest(
                "button[data-command]"
            );


        if(
            !button
        ){

            return;
        }


        runRegionCommand(
            button
        );
    }
);


/* ============================================================
   INITIAL LOAD
   ============================================================ */

loadRegions(
    false
);

</script>

<script defer src="/Other/core/ag-confirm-modal.js?v=1"></script>

<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>







