<?php

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>
Help Centre
</title>

<link
    rel="stylesheet"
    href="/Other/australia-3d-theme.css?v=20260820-022508">

<style>

:root{

    --hc-gold:#f2b632;
    --hc-gold-dark:#9a6207;

    --hc-text:#f4f6f7;
    --hc-muted:#c2cbd1;

    --hc-bg:#05080a;

    --hc-glass:
        rgba(5,10,14,.94);

    --hc-glass-soft:
        rgba(8,14,18,.91);

    --hc-border:
        rgba(242,182,50,.48);

    --hc-border-soft:
        rgba(255,255,255,.10);

}


*{
    box-sizing:border-box;
}


html{
    scroll-behavior:smooth;
}


body{

    margin:0;

    min-height:100vh;

    color:
        var(--hc-text);

    font-family:
        "Segoe UI",
        Arial,
        Helvetica,
        sans-serif;

    font-size:16px;

    line-height:1.65;

    background:

        radial-gradient(
            circle at 50% 0%,
            #1d2931 0,
            #091015 43%,
            #030506 100%
        );

}


button,
a{
    font-family:inherit;
}


.hc-shell{

    width:
        min(
            1420px,
            calc(100% - 34px)
        );

    margin:
        28px auto 60px;

}


.hc-glass{

    background:

        linear-gradient(
            135deg,
            rgba(35,46,54,.40),
            rgba(3,7,10,.96) 42%,
            rgba(2,5,7,.98)
        );

    border:
        1px solid
        var(--hc-border);

    box-shadow:

        0 18px 50px
        rgba(0,0,0,.38),

        inset 0 1px 0
        rgba(255,255,255,.07);

    backdrop-filter:
        blur(16px);

    -webkit-backdrop-filter:
        blur(16px);

}


.hc-header{

    border-radius:16px;

    padding:
        24px 27px;

}


.hc-eyebrow{

    color:
        var(--hc-gold);

    font-size:12px;

    font-weight:900;

    letter-spacing:.15em;

}


.hc-header h1{

    margin:
        3px 0 4px;

    font-size:
        clamp(
            28px,
            3vw,
            40px
        );

    line-height:1.1;

}


.hc-header p{

    max-width:900px;

    margin:
        8px 0 0;

    color:
        var(--hc-muted);

    font-size:17px;

}


.hc-header-actions{

    display:flex;

    flex-wrap:wrap;

    gap:10px;

    margin-top:18px;

}


.hc-btn{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    min-height:42px;

    padding:
        10px 16px;

    border:
        1px solid
        rgba(242,182,50,.58);

    border-radius:9px;

    background:

        linear-gradient(
            180deg,
            #efb633,
            #a66808
        );

    color:#120d04;

    font-size:14px;

    font-weight:900;

    text-decoration:none;

    cursor:pointer;

    transition:
        transform .15s ease,
        filter .15s ease,
        border-color .15s ease;

}


.hc-btn:hover{

    transform:
        translateY(-1px);

    filter:
        brightness(1.08);

}


.hc-btn-secondary{

    background:
        rgba(5,9,12,.92);

    color:
        #f6f7f8;

    border-color:
        rgba(255,255,255,.17);

}


.hc-layout{

    display:grid;

    grid-template-columns:
        minmax(275px,330px)
        minmax(0,1fr);

    gap:18px;

    margin-top:18px;

    align-items:start;

}


.hc-menu{

    position:sticky;

    top:18px;

    border-radius:15px;

    padding:15px;

}


.hc-menu-title{

    margin:
        4px 6px 13px;

    color:white;

    font-size:19px;

    font-weight:900;

}


.hc-menu-group{

    margin-top:18px;

}


.hc-menu-group:first-of-type{
    margin-top:0;
}


.hc-menu-label{

    margin:
        0 8px 7px;

    color:
        var(--hc-gold);

    font-size:11px;

    font-weight:900;

    letter-spacing:.12em;

    text-transform:uppercase;

}


.hc-topic{

    width:100%;

    display:flex;

    align-items:center;

    gap:11px;

    margin:6px 0;

    padding:
        11px 12px;

    border:
        1px solid
        rgba(255,255,255,.10);

    border-radius:10px;

    background:

        linear-gradient(
            135deg,
            rgba(39,50,57,.30),
            rgba(4,8,11,.94)
        );

    color:
        #f1f4f5;

    font-size:15px;

    font-weight:750;

    line-height:1.3;

    text-align:left;

    cursor:pointer;

    transition:
        border-color .15s ease,
        background .15s ease,
        transform .15s ease;

}


.hc-topic:hover{

    border-color:
        rgba(242,182,50,.55);

    transform:
        translateX(2px);

}


.hc-topic.active{

    color:white;

    border-color:
        rgba(242,182,50,.80);

    background:

        linear-gradient(
            135deg,
            rgba(113,77,11,.48),
            rgba(5,8,10,.97)
        );

    box-shadow:
        inset 3px 0 0
        var(--hc-gold);

}


.hc-topic-icon{

    flex:
        0 0 29px;

    width:29px;

    height:29px;

    display:grid;

    place-items:center;

    border-radius:8px;

    color:
        var(--hc-gold);

    border:
        1px solid
        rgba(242,182,50,.28);

    background:
        rgba(242,182,50,.07);

    font-weight:900;

}


.hc-content{

    min-width:0;

    border-radius:15px;

    padding:
        clamp(
            20px,
            3vw,
            32px
        );

}


.hc-tutorial{
    display:none;
}


.hc-tutorial.active{
    display:block;
}


.hc-kicker{

    color:
        var(--hc-gold);

    font-size:11px;

    font-weight:900;

    letter-spacing:.13em;

    text-transform:uppercase;

}


.hc-tutorial h2{

    margin:
        5px 0 7px;

    color:white;

    font-size:
        clamp(
            25px,
            3vw,
            35px
        );

    line-height:1.15;

}


.hc-lead{

    max-width:900px;

    margin:
        0 0 20px;

    color:
        #d1d8dc;

    font-size:17px;

}


.hc-info-grid{

    display:grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap:13px;

    margin:
        20px 0;

}


.hc-mini-card{

    min-height:120px;

    padding:18px;

    border:
        1px solid
        var(--hc-border-soft);

    border-radius:12px;

    background:
        rgba(2,6,8,.87);

}


.hc-mini-card h3{

    margin:
        0 0 6px;

    color:
        var(--hc-gold);

    font-size:18px;

}


.hc-mini-card p{

    margin:0;

    color:
        var(--hc-muted);

}


.hc-steps{

    margin:
        22px 0 0;

    padding:0;

    list-style:none;

    counter-reset:
        hc-step;

}


.hc-steps li{

    position:relative;

    margin:
        11px 0;

    padding:
        15px 16px
        15px 60px;

    border:
        1px solid
        rgba(255,255,255,.10);

    border-radius:11px;

    background:
        rgba(2,6,8,.88);

    color:
        #dce2e5;

    font-size:16px;

}


.hc-steps li::before{

    counter-increment:
        hc-step;

    content:
        counter(hc-step);

    position:absolute;

    left:15px;

    top:14px;

    width:31px;

    height:31px;

    display:grid;

    place-items:center;

    border-radius:8px;

    background:
        linear-gradient(
            #efb633,
            #a66808
        );

    color:
        #130d03;

    font-weight:900;

}


.hc-note{

    margin:
        18px 0;

    padding:16px 18px;

    border-left:
        4px solid
        var(--hc-gold);

    border-radius:
        0 10px 10px 0;

    background:
        rgba(242,182,50,.075);

    color:
        #d9e0e3;

}


.hc-address{

    display:block;

    max-width:700px;

    margin:
        14px 0;

    padding:
        13px 15px;

    border:
        1px solid
        rgba(242,182,50,.38);

    border-radius:9px;

    background:
        #020405;

    color:
        #f7c553;

    font-family:
        Consolas,
        monospace;

    font-size:16px;

    overflow-wrap:anywhere;

}


.hc-actions{

    display:flex;

    flex-wrap:wrap;

    gap:10px;

    margin-top:22px;

}


.hc-picture-button{

    margin-top:18px;

}


.hc-picture{

    display:none;

    margin-top:14px;

    padding:14px;

    border:
        1px solid
        rgba(242,182,50,.34);

    border-radius:13px;

    background:
        rgba(0,3,5,.95);

}


.hc-picture.open{
    display:block;
}


.hc-picture img{

    display:block;

    width:100%;

    max-height:720px;

    object-fit:contain;

    border-radius:9px;

    background:#000;

}


.hc-picture-missing{

    min-height:250px;

    display:none;

    align-items:center;

    justify-content:center;

    padding:30px;

    border:
        1px dashed
        rgba(242,182,50,.34);

    border-radius:10px;

    color:
        #c8d0d5;

    text-align:center;

    background:

        linear-gradient(
            135deg,
            rgba(24,31,36,.42),
            rgba(2,5,7,.96)
        );

}


.hc-picture-caption{

    margin:
        11px 3px 1px;

    color:
        #aeb9c0;

    font-size:14px;

}


.hc-warning{

    color:
        #ffd16c;

    font-weight:900;

}


.hc-footer{

    margin-top:18px;

    padding:16px 20px;

    border-radius:13px;

    color:
        #aeb9c0;

    font-size:14px;

    text-align:center;

}


@media
(max-width:920px){

    .hc-layout{
        grid-template-columns:1fr;
    }

    .hc-menu{
        position:static;
    }

    .hc-menu-group{
        display:grid;

        grid-template-columns:
            repeat(
                2,
                minmax(0,1fr)
            );

        gap:6px;
    }

    .hc-menu-label{
        grid-column:
            1 / -1;
    }

}


@media
(max-width:650px){

    body{
        font-size:16px;
    }

    .hc-shell{
        width:
            min(
                100% - 18px,
                1420px
            );

        margin-top:10px;
    }

    .hc-header{
        padding:19px;
    }

    .hc-content{
        padding:18px;
    }

    .hc-info-grid{
        grid-template-columns:1fr;
    }

    .hc-menu-group{
        grid-template-columns:1fr;
    }

    .hc-steps li{
        padding-left:57px;
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

</style>


<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">

<style id="australia-help-centre-colour-icons">

/* AUSTRALIA HELP CENTRE COLOUR EMOJI ICONS */

.hc-topic-icon{
    font-family:
        "Segoe UI Emoji",
        "Apple Color Emoji",
        "Noto Color Emoji",
        sans-serif !important;

    font-style:normal !important;
    font-weight:400 !important;

    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;

    width:28px !important;
    min-width:28px !important;

    font-size:20px !important;
    line-height:1 !important;

    filter:none !important;
    text-shadow:none !important;
}

</style>
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">


<style id="help-centre-topic-menu-final">

/* ==========================================================
   HELP CENTRE TOPIC MENU FINAL V4
   SYMBOLS ONLY - NO ICON BOXES
   ========================================================== */


/* BUTTON ROW */

.hc-menu-group .hc-topic{

    position:relative !important;

    display:block !important;

    width:100% !important;

    height:46px !important;
    min-height:46px !important;
    max-height:46px !important;

    padding:0 !important;

    margin-left:0 !important;
    margin-right:0 !important;

    box-sizing:border-box !important;
}


/* SYMBOL AREA - NO BOX */

.hc-menu-group .hc-topic .hc-topic-icon{

    position:absolute !important;

    left:12px !important;
    top:50% !important;

    transform:translateY(-50%) !important;

    width:32px !important;
    min-width:32px !important;
    max-width:32px !important;

    height:32px !important;
    min-height:32px !important;
    max-height:32px !important;

    display:flex !important;

    align-items:center !important;
    justify-content:center !important;

    margin:0 !important;
    padding:0 !important;

    box-sizing:border-box !important;

    font-size:24px !important;
    line-height:1 !important;

    text-align:center !important;

    /* REMOVE THE HOLDER BOX COMPLETELY */

    border:none !important;
    outline:none !important;

    background:transparent !important;

    box-shadow:none !important;

    border-radius:0 !important;
}


/* BUTTON TEXT */

.hc-menu-group .hc-topic .hc-topic-label{

    position:absolute !important;

    left:56px !important;
    right:12px !important;

    top:50% !important;

    transform:translateY(-50%) !important;

    display:block !important;

    margin:0 !important;
    padding:0 !important;

    box-sizing:border-box !important;

    text-align:left !important;

    font-size:15px !important;
    font-weight:800 !important;

    line-height:1.2 !important;

    white-space:nowrap !important;
}


/* KEEP EVERYTHING STILL ON HOVER */

.hc-menu-group .hc-topic:hover .hc-topic-icon{

    transform:translateY(-50%) !important;

    border:none !important;
    background:transparent !important;
    box-shadow:none !important;
}


.hc-menu-group .hc-topic:hover .hc-topic-label{

    transform:translateY(-50%) !important;
}


/* BUTTON SPACING */

.hc-menu-group .hc-topic + .hc-topic{

    margin-top:9px !important;
}


/* CATEGORY HEADINGS */

.hc-menu-group .hc-menu-label{

    margin-left:10px !important;

    font-size:15px !important;
    font-weight:900 !important;
}


/* MOBILE */

@media (max-width:700px){

    .hc-menu-group .hc-topic{

        height:44px !important;
        min-height:44px !important;
        max-height:44px !important;
    }


    .hc-menu-group .hc-topic .hc-topic-icon{

        left:10px !important;

        width:30px !important;
        min-width:30px !important;
        max-width:30px !important;

        height:30px !important;
        min-height:30px !important;
        max-height:30px !important;

        font-size:22px !important;

        border:none !important;
        background:transparent !important;
        box-shadow:none !important;
        border-radius:0 !important;
    }


    .hc-menu-group .hc-topic .hc-topic-label{

        left:52px !important;

        font-size:14px !important;
    }
}

</style>
<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=20260830-phase3c">
<link rel="stylesheet" href="/Other/assets/css/ag-sentinel-icons-v1.css?v=20260902-sentinel-v1">

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


<div class="hc-shell">


<?php
$siteHeaderKicker = "ACCOUNT";
$siteHeaderTitle = "Help Centre";
$siteHeaderRole = "MEMBER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "GETTING STARTED";
$siteHeaderLink = "/Other/getting-started.php";
require_once __DIR__ . "/includes/site-header.php";
?>


<div class="hc-layout">


<nav
    class="hc-menu hc-glass"
    aria-label="Help Centre topics">

    <div class="hc-menu-title">
        Help Topics
    </div>


    <div class="hc-menu-group">

        <div class="hc-menu-label">
            Start Here
        </div>

        <button
            class="hc-topic active"
            type="button"
            data-topic="home">

            <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/home.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

            <span class="hc-topic-label">Help Centre Home</span>

        </button>

        <button
            class="hc-topic"
            type="button"
            data-topic="getting-started">

            <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/help.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

            <span class="hc-topic-label">Getting Started</span>

        </button>

        <button
            class="hc-topic"
            type="button"
            data-topic="firestorm-grid">

            <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/add-region.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

            <span class="hc-topic-label">Add This Grid to Firestorm</span>

        </button>

        <button
            class="hc-topic"
            type="button"
            data-topic="login">

            <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/key.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

            <span class="hc-topic-label">Logging In</span>

        </button>

        <button
            class="hc-topic"
            type="button"
            data-topic="teleport">

            <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/map.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

            <span class="hc-topic-label">Teleporting</span>

        </button>

    </div>


    <div class="hc-menu-group">

        <div class="hc-menu-label">
            Account & Profile
        </div>

        <button
            class="hc-topic"
            type="button"
            data-topic="edit-account">

            <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/edit.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

            <span class="hc-topic-label">Edit My Account</span>

        </button>

        <button
            class="hc-topic"
            type="button"
            data-topic="my-profile">

            <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/account.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

            <span class="hc-topic-label">My Profile</span>

        </button>

    </div>


    <div class="hc-menu-group">

        <div class="hc-menu-label">
            Inventory & Backups
        </div>

        <button
            class="hc-topic"
            type="button"
            data-topic="inventory-browser">

            <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/inventory.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

            <span class="hc-topic-label">Inventory Browser</span>

        </button>

        <button
            class="hc-topic"
            type="button"
            data-topic="clean-inventory">

            <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/delete.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

            <span class="hc-topic-label">Clean Inventory</span>

        </button>

        <button
            class="hc-topic"
            type="button"
            data-topic="safety-iar">

            <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/security.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

            <span class="hc-topic-label">Safety Inventory IAR</span>

        </button>

        <button
            class="hc-topic"
            type="button"
            data-topic="iar-backups">

            <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/backup.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

            <span class="hc-topic-label">IAR Backups</span>

        </button>

        <button
            class="hc-topic"
            type="button"
            data-topic="delete-iar">

            <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/delete.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

            <span class="hc-topic-label">Delete an IAR Backup</span>

        </button>

    </div>


    <div class="hc-menu-group">

        <div class="hc-menu-label">
            Grid Tools
        </div>

        <button
            class="hc-topic"
            type="button"
            data-topic="grid-map">

            <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/map.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

            <span class="hc-topic-label">Grid Map</span>

        </button>

    </div>


<!-- AUSTRALIA-DASHBOARD-MISSING-HELP-TOPICS -->

<div class="hc-menu-group">

    <div class="hc-menu-label">
        Dashboard Features
    </div>

    <button
        class="hc-topic"
        type="button"
        data-topic="my-regions">

        <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/region.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>
        <span class="hc-topic-label">My Regions</span>

    </button>

    <button
        class="hc-topic"
        type="button"
        data-topic="offline-messages">

        <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/email.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>
        <span class="hc-topic-label">Offline Messages</span>

    </button>

    <button
        class="hc-topic"
        type="button"
        data-topic="linked-regions">

        <span class="hc-topic-icon"><img class="ag-sentinel-direct-icon ag-sentinel-md" src="/Other/assets/icons/sentinel/link.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>
        <span class="hc-topic-label">Linked Regions</span>

    </button>

</div>

</nav>


<main class="hc-content hc-glass">


<!-- ===================================================== -->
<!-- HELP CENTRE HOME                                      -->
<!-- ===================================================== -->

<article
    class="hc-tutorial active"
    data-panel="home">

    <div class="hc-kicker">
        HELP CENTRE
    </div>

    <h2>
        What do you need help with?
    </h2>

    <p class="hc-lead">
        Choose a topic from the menu on the left.
        Each guide is designed to give you clear instructions,
        picture tutorials and direct links to the page or tool
        being explained.
    </p>


    <div class="hc-info-grid">

        <div class="hc-mini-card">

            <h3>
                New to This Grid?
            </h3>

            <p>
                Start with Getting Started,
                then learn how to add this grid
                to Firestorm and log in.
            </p>

        </div>


        <div class="hc-mini-card">

            <h3>
                Need account help?
            </h3>

            <p>
                Learn how to edit your email address,
                change your password and use your
                OpenSim profile.
            </p>

        </div>


        <div class="hc-mini-card">

            <h3>
                Inventory & Backups
            </h3>

            <p>
                Learn the Inventory Browser,
                Clean Inventory, Safety IAR
                and normal IAR backup system.
            </p>

        </div>


        <div class="hc-mini-card">

            <h3>
                Picture Tutorials
            </h3>

            <p>
                Picture guides can be opened from each tutorial.
                We will add screenshots of the exact menu,
                page or Firestorm screen being explained.
            </p>

        </div>

    </div>


    <div class="hc-actions">

        <button
            class="hc-btn"
            type="button"
            data-open-topic="firestorm-grid">

            ADD GRID TO FIRESTORM

        </button>

        <button
            class="hc-btn hc-btn-secondary"
            type="button"
            data-open-topic="inventory-browser">

            INVENTORY HELP

        </button>

        <a
            class="hc-btn hc-btn-secondary"
            href="/Other/login-help.php">

            LOGIN HELP / FAQ

        </a>

    </div>

</article>


<!-- ===================================================== -->
<!-- GETTING STARTED                                       -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="getting-started">

    <div class="hc-kicker">
        START HERE
    </div>

    <h2>
        Getting Started
    </h2>

    <p class="hc-lead">
        Follow the basic setup steps before logging into
        this grid for the first time.
    </p>

    <ol class="hc-steps">

        <li>
            Install an OpenSim-compatible viewer such as Firestorm.
        </li>

        <li>
            Add this grid to the viewer's Grid Manager.
        </li>

        <li>
            Enter the First Name and Last Name of your
            avatar.
        </li>

        <li>
            Log in and begin at the grid welcome area.
        </li>

    </ol>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-getting-started">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-getting-started">

        <img
            src="/Other/help-centre-images/getting-started.png"
            alt="Getting Started tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>PICTURE TUTORIAL</strong><br><br>
                Screenshot will be added here.<br>
                File: getting-started.png
            </div>
        </div>

        <div class="hc-picture-caption">
            Getting Started picture guide.
        </div>

    </div>


    <div class="hc-actions">

        <a
            class="hc-btn"
            href="/Other/getting-started.php">

            OPEN FULL GETTING STARTED PAGE

        </a>

        <button
            class="hc-btn hc-btn-secondary"
            type="button"
            data-open-topic="firestorm-grid">

            NEXT: ADD GRID TO FIRESTORM

        </button>

    </div>

</article>


<!-- ===================================================== -->
<!-- ADD GRID TO FIRESTORM                                 -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="firestorm-grid">

    <div class="hc-kicker">
        FIRESTORM / OPENSIM
    </div>

    <h2>
        Add This Grid to Firestorm
    </h2>

    <p class="hc-lead">
        This grid must be added to Firestorm's
        OpenSim Grid Manager before you can select it
        from the viewer login screen.
    </p>


    <span class="hc-address">
        <span class="ag-grid-login-url">Loading grid address...</span>
    </span>


    <ol class="hc-steps">

        <li>
            Open Firestorm and open its Grid Manager
            or OpenSim grid settings.
        </li>

        <li>
            Choose the option to add a new grid.
        </li>

        <li>
            Enter the grid address shown above.
        </li>

        <li>
            Save or apply the grid information.
        </li>

        <li>
            Select this grid from the viewer's
            grid selection and enter your avatar details.
        </li>

    </ol>


    <div class="hc-note">

        Your grid login uses your avatar's
        <strong>First Name</strong>,
        <strong>Last Name</strong>
        and password.

    </div>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-firestorm-grid">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-firestorm-grid">

        <img
            src="/Other/help-centre-images/firestorm-grid-manager.png"
            alt="Firestorm Grid Manager tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>FIRESTORM GRID MANAGER PICTURE TUTORIAL</strong>
                <br><br>
                This will show the exact Firestorm screen
                and where to enter the grid address.
                <br><br>
                File: firestorm-grid-manager.png
            </div>
        </div>

        <div class="hc-picture-caption">
            Firestorm Grid Manager picture tutorial.
        </div>

    </div>


    <div class="hc-actions">

        <a
            class="hc-btn"
            href="https://www.firestormviewer.org/"
            target="_blank"
            rel="noopener">

            OFFICIAL FIRESTORM WEBSITE

        </a>

        <button
            class="hc-btn hc-btn-secondary"
            type="button"
            data-open-topic="login">

            NEXT: LOGGING IN

        </button>

    </div>

</article>


<!-- ===================================================== -->
<!-- LOGIN                                                  -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="login">

    <div class="hc-kicker">
        ACCOUNT ACCESS
    </div>

    <h2>
        Logging In
    </h2>

    <p class="hc-lead">
        Use the avatar account you created on this grid.
    </p>


    <ol class="hc-steps">

        <li>
            Select this grid in your viewer.
        </li>

        <li>
            Enter your avatar First Name.
        </li>

        <li>
            Enter your avatar Last Name.
        </li>

        <li>
            Enter your grid password.
        </li>

        <li>
            Select Log In.
        </li>

    </ol>


    <span class="hc-address">
        <span class="ag-grid-login-url">Loading grid address...</span>
    </span>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-login">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-login">

        <img
            src="/Other/help-centre-images/login-page.png"
            alt="Grid login tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>LOGIN PICTURE TUTORIAL</strong><br><br>
                Screenshot will show the login fields
                and the correct grid selection.
                <br><br>
                File: login-page.png
            </div>
        </div>

        <div class="hc-picture-caption">
            Grid login picture guide.
        </div>

    </div>


    <div class="hc-actions">

        <a
            class="hc-btn"
            href="/Other/login-help.php">

            OPEN LOGIN HELP / FAQ

        </a>

    </div>

</article>


<!-- ===================================================== -->
<!-- TELEPORT                                               -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="teleport">

    <div class="hc-kicker">
        FIRESTORM
    </div>

    <h2>
        Teleport to Another Region
    </h2>

    <p class="hc-lead">
        Use the World Map in your viewer to find
        another region on this grid.
    </p>


    <ol class="hc-steps">

        <li>
            Open the World Map in Firestorm.
        </li>

        <li>
            Search for the region name.
        </li>

        <li>
            Select the region from the map or search result.
        </li>

        <li>
            Choose Teleport.
        </li>

    </ol>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-teleport">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-teleport">

        <img
            src="/Other/help-centre-images/firestorm-world-map.png"
            alt="Firestorm World Map teleport tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>WORLD MAP PICTURE TUTORIAL</strong><br><br>
                File: firestorm-world-map.png
            </div>
        </div>

        <div class="hc-picture-caption">
            Firestorm World Map and teleport guide.
        </div>

    </div>

</article>


<!-- ===================================================== -->
<!-- EDIT ACCOUNT                                           -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="edit-account">

    <div class="hc-kicker">
        MY ACCOUNT
    </div>

    <h2>
        Edit My Account
    </h2>

    <p class="hc-lead">
        Change your account email address
        or password from My Account.
    </p>


    <ol class="hc-steps">

        <li>
            Sign in to this grid.
        </li>

        <li>
            Open <strong>MY ACCOUNT</strong>.
        </li>

        <li>
            Choose <strong>EDIT ACCOUNT</strong>.
        </li>

        <li>
            Change your email address or enter a new password.
        </li>

        <li>
            Enter your current grid password
            before selecting <strong>SAVE CHANGES</strong>.
        </li>

    </ol>


    <div class="hc-note">
        Your avatar name is read-only and cannot be changed
        from this page. If you are only changing your email,
        leave the New Password and Confirm New Password fields empty.
    </div>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-account">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-account">

        <img
            src="/Other/help-centre-images/my-account.png"
            alt="My Account tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>MY ACCOUNT PICTURE TUTORIAL</strong><br><br>
                File: my-account.png
            </div>
        </div>

        <div class="hc-picture-caption">
            My Account and Edit Account picture guide.
        </div>

    </div>


    <div class="hc-actions">

        <a
            class="hc-btn"
            href="/Other/user-account.php">

            OPEN MY ACCOUNT

        </a>

    </div>

</article>


<!-- ===================================================== -->
<!-- MY PROFILE                                             -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="my-profile">

    <div class="hc-kicker">
        FIRESTORM / OPENSIM PROFILE
    </div>

    <h2>
        My Profile
    </h2>

    <p class="hc-lead">
        My Profile displays the signed-in avatar's
        OpenSim profile.
    </p>


    <div class="hc-info-grid">

        <div class="hc-mini-card">
            <h3>Profile</h3>
            <p>
                About, Profile URL, Languages,
                Interests and supported publishing settings.
            </p>
        </div>

        <div class="hc-mini-card">
            <h3>First Life</h3>
            <p>
                Displays the avatar's First Life image
                and First Life text.
            </p>
        </div>

        <div class="hc-mini-card">
            <h3>Picks</h3>
            <p>
                Displays the avatar's actual
                OpenSim profile Picks.
            </p>
        </div>

        <div class="hc-mini-card">
            <h3>Classifieds</h3>
            <p>
                Displays the avatar's actual
                OpenSim Classifieds.
            </p>
        </div>

    </div>


    <div class="hc-note">
        Profile pictures remain controlled in-world through Firestorm.
        Use <strong>REFRESH FROM IN-WORLD</strong>
        after changing a profile picture.
    </div>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-profile">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-profile">

        <img
            src="/Other/help-centre-images/my-profile.png"
            alt="My Profile tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>MY PROFILE PICTURE TUTORIAL</strong><br><br>
                File: my-profile.png
            </div>
        </div>

        <div class="hc-picture-caption">
            My Profile picture tutorial.
        </div>

    </div>


    <div class="hc-actions">

        <a
            class="hc-btn"
            href="/Other/user-profile.php">

            OPEN MY PROFILE

        </a>

    </div>

</article>


<!-- ===================================================== -->
<!-- INVENTORY BROWSER                                      -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="inventory-browser">

    <div class="hc-kicker">
        MY INVENTORY
    </div>

    <h2>
        Inventory Browser
    </h2>

    <p class="hc-lead">
        Browse and search your inventory
        from the website.
    </p>


    <ol class="hc-steps">

        <li>
            Open <strong>MY INVENTORY</strong>
            from your User Dashboard.
        </li>

        <li>
            Choose
            <strong>OPEN INVENTORY BROWSER</strong>.
        </li>

        <li>
            Select a folder on the left.
        </li>

        <li>
            Inventory items from that folder
            appear in the centre.
        </li>

        <li>
            Select an item to view its details on the right.
        </li>

    </ol>


    <div class="hc-note">
        Inventory Browser is
        <strong>READ ONLY</strong>.
        It cannot delete, rename, move or modify
        your inventory.
    </div>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-inventory-browser">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-inventory-browser">

        <img
            src="/Other/help-centre-images/inventory-browser.png"
            alt="Inventory Browser tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>INVENTORY BROWSER PICTURE TUTORIAL</strong>
                <br><br>
                File: inventory-browser.png
            </div>
        </div>

        <div class="hc-picture-caption">
            Inventory Browser picture guide.
        </div>

    </div>


    <div class="hc-actions">

        <a
            class="hc-btn"
            href="/Other/user-inventory.php">

            OPEN MY INVENTORY

        </a>

    </div>

</article>


<!-- ===================================================== -->
<!-- CLEAN INVENTORY                                        -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="clean-inventory">

    <div class="hc-kicker">
        INVENTORY TOOLS
    </div>

    <h2>
        Clean Inventory
    </h2>

    <p class="hc-lead">
        Clean Inventory is a separate staging workspace
        used to prepare a cleaner inventory structure.
    </p>


    <ol class="hc-steps">

        <li>
            Open your User Dashboard and choose
            <strong>MY INVENTORY</strong>.
        </li>

        <li>
            Choose
            <strong>OPEN CLEAN INVENTORY</strong>.
        </li>

        <li>
            Select Main Inventory folders or individual items
            and add them to Clean Inventory.
        </li>

        <li>
            Use <strong>+ NEW FOLDER</strong>
            to create folders inside the Clean workspace.
        </li>

        <li>
            Review the
            <strong>ITEMS LEFT OUT</strong>
            counter before continuing.
        </li>

    </ol>


    <div class="hc-note">
        Building or clearing Clean Inventory does not delete
        anything from your live Main Inventory.
    </div>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-clean-inventory">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-clean-inventory">

        <img
            src="/Other/help-centre-images/clean-inventory.png"
            alt="Clean Inventory tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>CLEAN INVENTORY PICTURE TUTORIAL</strong>
                <br><br>
                File: clean-inventory.png
            </div>
        </div>

        <div class="hc-picture-caption">
            Clean Inventory picture guide.
        </div>

    </div>

</article>


<!-- ===================================================== -->
<!-- SAFETY IAR                                             -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="safety-iar">

    <div class="hc-kicker">
        INVENTORY SAFETY
    </div>

    <h2>
        Safety Inventory IAR
    </h2>

    <p class="hc-lead">
        A verified Safety IAR is required
        before Clean Inventory can replace your current
        Main Inventory.
    </p>


    <ol class="hc-steps">

        <li>
            Open
            <strong>MY INVENTORY</strong>.
        </li>

        <li>
            Open
            <strong>CLEAN INVENTORY</strong>.
        </li>

        <li>
            Choose
            <strong>CREATE SAFETY IAR</strong>.
        </li>

        <li>
            Wait for
            <strong>SAFETY IAR VERIFIED</strong>.
        </li>

        <li>
            Download a copy to your own computer
            if you want an additional emergency backup.
        </li>

    </ol>


    <div class="hc-note">

        <span class="hc-warning">
            NO VERIFIED SAFETY IAR =
            NO INVENTORY REPLACEMENT.
        </span>

    </div>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-safety-iar">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-safety-iar">

        <img
            src="/Other/help-centre-images/safety-iar.png"
            alt="Safety IAR tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>SAFETY IAR PICTURE TUTORIAL</strong>
                <br><br>
                File: safety-iar.png
            </div>
        </div>

        <div class="hc-picture-caption">
            Safety Inventory IAR picture guide.
        </div>

    </div>

</article>


<!-- ===================================================== -->
<!-- IAR BACKUPS                                            -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="iar-backups">

    <div class="hc-kicker">
        INVENTORY BACKUPS
    </div>

    <h2>
        IAR Backups
    </h2>

    <p class="hc-lead">
        Create, download and manage normal inventory archives
        for your signed-in avatar.
    </p>


    <ol class="hc-steps">

        <li>
            Open the IAR Backups page from your User Dashboard.
        </li>

        <li>
            Select
            <strong>SAVE NEW IAR</strong>.
        </li>

        <li>
            Wait for the grid to create
            and verify the new archive.
        </li>

        <li>
            Use
            <strong>DOWNLOAD IAR</strong>
            to save an archive to your own computer.
        </li>

    </ol>


    <div class="hc-note">
        Each avatar can keep a maximum of
        <strong>2 normal IAR backups</strong>
        on this grid. If both slots are full,
        the new archive is created and verified
        before the oldest retained backup is replaced.
    </div>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-iar-backups">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-iar-backups">

        <img
            src="/Other/help-centre-images/iar-backups.png"
            alt="IAR Backups tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>IAR BACKUPS PICTURE TUTORIAL</strong>
                <br><br>
                File: iar-backups.png
            </div>
        </div>

        <div class="hc-picture-caption">
            IAR Backups picture guide.
        </div>

    </div>


    <div class="hc-actions">

        <a
            class="hc-btn"
            href="/Other/user-iar-backups.php">

            OPEN IAR BACKUPS

        </a>

    </div>

</article>


<!-- ===================================================== -->
<!-- DELETE IAR                                             -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="delete-iar">

    <div class="hc-kicker">
        IAR BACKUPS
    </div>

    <h2>
        Delete an IAR Backup
    </h2>

    <p class="hc-lead">
        Remove one of your retained normal IAR backups
        from the grid server.
    </p>


    <ol class="hc-steps">

        <li>
            Open
            <strong>IAR BACKUPS</strong>.
        </li>

        <li>
            Find the saved archive you want to remove.
        </li>

        <li>
            Select
            <strong>DELETE IAR</strong>.
        </li>

        <li>
            Review the filename, date and size.
        </li>

        <li>
            Confirm the deletion.
        </li>

    </ol>


    <div class="hc-note">
        A copy that you already downloaded to your own computer
        is not affected. The separate Clean Inventory Safety IAR
        is also not affected.
    </div>

</article>


<!-- ===================================================== -->
<!-- GRID MAP                                               -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="grid-map">

    <div class="hc-kicker">
        GRID TOOLS
    </div>

    <h2>
        Using the Grid Map
    </h2>

    <p class="hc-lead">
        The grid map lets residents view
        regions from the website.
    </p>


    <ol class="hc-steps">

        <li>
            Sign in to this grid.
        </li>

        <li>
            Open your User Dashboard.
        </li>

        <li>
            Choose
            <strong>GRID MAP</strong>.
        </li>

        <li>
            Use the map to view grid regions.
        </li>

    </ol>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-grid-map">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-grid-map">

        <img
            src="/Other/help-centre-images/grid-map.png"
            alt="Grid Map tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>GRID MAP PICTURE TUTORIAL</strong>
                <br><br>
                File: grid-map.png
            </div>
        </div>

        <div class="hc-picture-caption">
            Grid map picture tutorial.
        </div>

    </div>


    <div class="hc-actions">

        <a
            class="hc-btn"
            href="/Other/grid-map-user.php">

            OPEN GRID MAP

        </a>

    </div>

</article>



<!-- ===================================================== -->
<!-- AUSTRALIA-MY-REGIONS-HELP-PANEL                       -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="my-regions">

    <div class="hc-kicker">
        MY GRID
    </div>

    <h2>
        My Regions
    </h2>

    <p class="hc-lead">
        View the Grid regions assigned to your avatar
        and open the controls available for each region.
    </p>

    <ol class="hc-steps">
        <li>Open <strong>My Regions</strong> from your Dashboard.</li>
        <li>Select the region you want to work with.</li>
        <li>Check its current status and region information.</li>
        <li>Use the available controls for that region.</li>
    </ol>

    <div class="hc-actions">

        <a
            class="hc-btn"
            href="/Other/user-regions.php">

            OPEN MY REGIONS

        </a>

    </div>

</article>


<!-- ===================================================== -->
<!-- AUSTRALIA-OFFLINE-MESSAGES-HELP-PANEL                  -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="offline-messages">

    <div class="hc-kicker">
        MESSAGES
    </div>

    <h2>
        Offline Messages
    </h2>

    <p class="hc-lead">
        Review messages currently waiting in OpenSim's
        offline message system for your avatar.
    </p>

    <ol class="hc-steps">
        <li>Open <strong>Offline Messages</strong> from your Dashboard.</li>
        <li>Review the messages waiting for your avatar.</li>
        <li>Use the available message controls when required.</li>
    </ol>

    <div class="hc-actions">

        <a
            class="hc-btn"
            href="/Other/user-offline-messages.php">

            OPEN OFFLINE MESSAGES

        </a>

    </div>

</article>


<!-- ===================================================== -->
<!-- AUSTRALIA-LINKED-REGIONS-HELP-PANEL                    -->
<!-- ===================================================== -->

<article
    class="hc-tutorial"
    data-panel="linked-regions">

    <div class="hc-kicker">
        GRID TOOLS
    </div>

    <h2>
        Linked Regions
    </h2>

    <p class="hc-lead">
        Open the linked-region tools available to your
        Grid account.
    </p>

    <ol class="hc-steps">
        <li>Open <strong>Linked Regions</strong> from your Dashboard.</li>
        <li>Review the linked-region information shown for your account.</li>
        <li>Use the available tools as they become available.</li>
    </ol>

    <div class="hc-actions">

        <a
            class="hc-btn"
            href="/Other/user-linked-regions.php">

            OPEN LINKED REGIONS

        </a>

    </div>

</article>

</main>


</div>


<footer class="hc-footer hc-glass">

    HELP CENTRE
    &nbsp; &#x2022; &nbsp;
    Step-by-step resident support

</footer>


</div>


<script>

(function(){

    "use strict";


    const topicButtons =
        Array.from(
            document.querySelectorAll(
                ".hc-topic[data-topic]"
            )
        );


    const panels =
        Array.from(
            document.querySelectorAll(
                ".hc-tutorial[data-panel]"
            )
        );


    function openTopic(name){

        let found =
            false;


        panels.forEach(function(panel){

            const active =
                panel.dataset.panel === name;

            panel.classList.toggle(
                "active",
                active
            );

            if(active){
                found = true;
            }

        });


        topicButtons.forEach(function(button){

            button.classList.toggle(
                "active",
                button.dataset.topic === name
            );

        });


        if(!found){
            return;
        }


        if(
            history.replaceState
        ){

            history.replaceState(
                null,
                "",
                "#" + name
            );

        }


        window.scrollTo({
            top:0,
            behavior:"smooth"
        });

    }


    topicButtons.forEach(function(button){

        button.addEventListener(
            "click",
            function(){

                openTopic(
                    button.dataset.topic
                );

            }
        );

    });


    document
        .querySelectorAll(
            "[data-open-topic]"
        )
        .forEach(function(button){

            button.addEventListener(
                "click",
                function(){

                    openTopic(
                        button.dataset.openTopic
                    );

                }
            );

        });


    document
        .querySelectorAll(
            "[data-picture-toggle]"
        )
        .forEach(function(button){

            button.addEventListener(
                "click",
                function(){

                    const id =
                        button.dataset.pictureToggle;

                    const picture =
                        document.getElementById(id);

                    if(!picture){
                        return;
                    }


                    const opening =
                        !picture.classList.contains(
                            "open"
                        );


                    picture.classList.toggle(
                        "open",
                        opening
                    );


                    button.textContent =
                        opening
                        ? "HIDE PICTURE TUTORIAL"
                        : "VIEW PICTURE TUTORIAL";

                }
            );

        });


    const initial =
        location.hash
            .replace("#","")
            .trim();


    if(
        initial &&
        panels.some(function(panel){
            return panel.dataset.panel === initial;
        })
    ){

        openTopic(initial);

    }


    /*
     * AUSTRALIA-DIRECT-HELP-TOPIC-LINK
     * Open the exact tutorial requested by a Dashboard HELP button.
     */
    const requestedTopic =
        new URLSearchParams(window.location.search).get("topic");

    if (requestedTopic) {
        openTopic(requestedTopic);
    }

})();

</script>



<!-- AUSTRALIA PORTABLE DREAMGRID LOGIN URL DISPLAY V1 -->
<script id="ag-grid-login-url-loader">
(function () {
    "use strict";

    function setLoginUrl(value) {

        var nodes =
            document.querySelectorAll(
                ".ag-grid-login-url"
            );

        for (
            var i = 0;
            i < nodes.length;
            i++
        ) {
            nodes[i].textContent = value;
        }
    }


    function loadLoginUrl() {

        var nodes =
            document.querySelectorAll(
                ".ag-grid-login-url"
            );

        if (!nodes.length) {
            return;
        }


        fetch(
            "/Other/grid-login-url.php?ts=" +
            Date.now(),
            {
                cache: "no-store",
                credentials: "same-origin"
            }
        )
        .then(function (response) {

            if (!response.ok) {
                throw new Error(
                    "Login URL request failed"
                );
            }

            return response.json();
        })
        .then(function (data) {

            if (
                !data ||
                data.ok !== true ||
                !data.login_url
            ) {
                throw new Error(
                    "Login URL unavailable"
                );
            }

            setLoginUrl(
                data.login_url
            );
        })
        .catch(function () {

            setLoginUrl(
                "Grid login address unavailable"
            );
        });
    }


    if (
        document.readyState === "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            loadLoginUrl
        );

    }
    else {

        loadLoginUrl();
    }


    /*
     * AUSTRALIA-DIRECT-HELP-TOPIC-LINK
     * Dashboard HELP buttons can open an exact tutorial.
     */
    const requestedTopic =
        new URLSearchParams(window.location.search).get("topic");

    if (requestedTopic) {
        openTopic(requestedTopic);
    }

})();
</script>
<!-- END AUSTRALIA PORTABLE DREAMGRID LOGIN URL DISPLAY V1 -->
<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>




