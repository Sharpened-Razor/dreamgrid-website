<?php

require_once __DIR__ . '/core/bootstrap.php';

$session =
    ag_require_admin();





$level =
    ag_user_level(
        $session
    );


$avatar =
    ag_avatar_name(
        $session
    );


header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


/* AUSTRALIA ADMIN SID CONTINUITY */

$agAdminSid =
    trim(
        (string)(
            $_GET['sid'] ??
            ''
        )
    );

$agAdminSidPattern =
    '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';

if(
    $agAdminSid !== '' &&
    preg_match(
        $agAdminSidPattern,
        $agAdminSid
    )
){

    setcookie(
        'ag_admin_sid',
        $agAdminSid,
        [
            'expires'  => 0,
            'path'     => '/Other/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );

}else{

    $agAdminSid =
        trim(
            (string)(
                $_COOKIE['ag_admin_sid'] ??
                ''
            )
        );

    if(
        $agAdminSid === '' ||
        !preg_match(
            $agAdminSidPattern,
            $agAdminSid
        )
    ){
        $agAdminSid = '';
    }
}

$agAdminSidSuffix =
    $agAdminSid !== ''
        ? '/?sid=' . rawurlencode($agAdminSid)
        : '';
?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>Admin Tools</title>

<link rel="stylesheet" href="/Other/australia-3d-theme.css?v=20260820-022508">
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">

<style>

*{
    box-sizing:border-box;
}


html,
body{
    min-height:100%;
}


body{

    margin:0;

    background:
        radial-gradient(
            circle at 50% -10%,
            #29363e 0,
            #11191e 36%,
            #070a0d 72%
        );

    color:#edf2f4;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}


/* ============================================================
   PAGE
   ============================================================ */

.dashboard-shell{

    width:
        min(
            1200px,
            calc(100% - 34px)
        );

    margin:
        34px auto 65px;

}


/* ============================================================
   HEADER
   ============================================================ */


































/* ============================================================
   SECTION
   ============================================================ */

.dashboard-section{

    margin-top:23px;

}


.section-title{

    margin:
        0 0 11px 2px;

    color:#daa334;

    font-size:10px;

    font-weight:900;

    letter-spacing:.17em;

}


/* ============================================================
   CARDS
   ============================================================ */

.dashboard-grid{

    display:grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0,1fr)
        );

    gap:14px;

}


.dashboard-card{

    min-height:220px;

    display:flex;

    flex-direction:column;

    padding:19px;

    border:
        1px solid
        rgba(255,255,255,.10);

    border-radius:13px;

    background:
        linear-gradient(
            145deg,
            rgba(19,28,34,.98),
            rgba(7,12,15,.98)
        );

    box-shadow:
        0 13px 30px
        rgba(0,0,0,.29);

    transition:
        .16s ease;

}


.dashboard-card:hover{

    transform:
        translateY(-2px);

    border-color:
        rgba(223,168,51,.38);

}


.card-heading{

    display:flex;

    align-items:center;

    gap:12px;

    margin-bottom:13px;

}


.card-icon{

    width:44px;

    height:44px;

    flex:
        0 0 44px;

    display:flex;

    align-items:center;

    justify-content:center;

    border:
        1px solid
        rgba(224,169,52,.35);

    border-radius:10px;

    background:
        rgba(224,169,52,.08);

    color:#efb841;

    font-size:10px;

    font-weight:900;

}


.card-title{

    margin:0;

    color:#f1bb49;

    font-size:15px;

}


.card-status{

    display:inline-block;

    margin-top:5px;

    padding:
        3px 7px;

    border-radius:
        999px;

    font-size:8px;

    font-weight:900;

    letter-spacing:.07em;

}


.card-status.live{

    border:
        1px solid
        rgba(70,185,103,.30);

    background:
        rgba(70,185,103,.08);

    color:#98dbaa;

}


.card-status.next{

    border:
        1px solid
        rgba(220,166,54,.30);

    background:
        rgba(220,166,54,.07);

    color:#e4bd6b;

}


.card-status.public{

    border:
        1px solid
        rgba(88,154,215,.30);

    background:
        rgba(88,154,215,.08);

    color:#aed1ef;

}


.dashboard-card p{

    flex:1;

    margin:
        0 0 16px;

    color:#a9b4ba;

    font-size:12px;

    line-height:1.58;

}


.card-actions{

    display:flex;

    gap:8px;

}


.card-button{

    flex:1;

    min-height:39px;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:
        9px;

    border:
        1px solid
        rgba(255,255,255,.14);

    border-radius:
        8px;

    background:#141d22;

    color:#e9eef0;

    text-decoration:none;

    text-align:center;

    font-size:10px;

    font-weight:900;

}


.card-button.primary{

    border-color:#d79e2c;

    background:
        linear-gradient(
            #f3bb42,
            #b9770e
        );

    color:#181109;

}


.card-button.disabled{

    opacity:.42;

    cursor:default;

    pointer-events:none;

}


/* ============================================================
   FOOT NOTE
   ============================================================ */

.dashboard-note{

    margin-top:18px;

    padding:
        14px 16px;

    border:
        1px solid
        rgba(222,167,52,.16);

    border-left:
        3px solid
        #d89e29;

    border-radius:8px;

    background:
        rgba(216,158,41,.05);

    color:#a8b2b8;

    font-size:11px;

    line-height:1.6;

}


/* ============================================================
   RESPONSIVE
   ============================================================ */

@media(
    max-width:950px
){

    .dashboard-grid{

        grid-template-columns:
            repeat(
                2,
                minmax(0,1fr)
            );

    }

}


@media(
    max-width:650px
){

    


    


    .dashboard-grid{

        grid-template-columns:
            1fr;

    }

}

</style>
<style>

/* AUSTRALIA USER DASHBOARD DESIGN V2 START */


/* ============================================================
   FULL PAGE BACKGROUND
   ============================================================ */

body{

    min-height:100vh !important;

    background:

        linear-gradient(
            180deg,
            rgba(0,0,0,.42),
            rgba(0,0,0,.62)
        ),

        url(
            "/Other/custom/Branding/AUSTRALIA-BACKGROUND.png"
        )

        center center /
        cover
        fixed
        no-repeat !important;

}


/* ============================================================
   ONE LARGE OUTER Grid BOX
   ============================================================ */

.dashboard-shell{

    width:
        min(
            1480px,
            calc(100% - 60px)
        ) !important;

    margin:
        42px auto 70px !important;

    padding:
        30px !important;

    border:
        1px solid
        rgba(220,165,54,.45) !important;

    border-radius:
        18px !important;

    background:

        linear-gradient(
            145deg,
            rgba(15,21,25,.96),
            rgba(5,9,12,.98)
        ) !important;

    box-shadow:

        0 30px 90px
        rgba(0,0,0,.72) !important;

    backdrop-filter:
        blur(14px) !important;

    -webkit-backdrop-filter:
        blur(14px) !important;

}


/* ============================================================
   HEADER
   ============================================================ */













/* ============================================================
   TOP BUTTONS
   ============================================================ */




/* ============================================================
   SECTION HEADING
   ============================================================ */

.dashboard-section{

    margin-top:
        28px !important;

}


.section-title{

    margin:
        0 0 15px 3px !important;

    font-size:
        13px !important;

    letter-spacing:
        .17em !important;

}


/* ============================================================
   CARD GRID
   ============================================================ */

.dashboard-grid{

    grid-template-columns:

        repeat(
            3,
            minmax(0,1fr)
        ) !important;

    gap:
        18px !important;

}


/* ============================================================
   BIGGER CARDS
   ============================================================ */

.dashboard-card{

    min-height:
        265px !important;

    padding:
        23px !important;

    border-radius:
        14px !important;

    border:
        1px solid
        rgba(255,255,255,.11) !important;

    background:

        linear-gradient(
            145deg,
            rgba(19,27,32,.97),
            rgba(6,11,14,.98)
        ) !important;

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.025),

        0 14px 34px
        rgba(0,0,0,.34) !important;

}


.dashboard-card:hover{

    border-color:
        rgba(224,169,52,.55) !important;

    box-shadow:

        0 17px 40px
        rgba(0,0,0,.42) !important;

}


/* ============================================================
   CARD ICONS / TITLES
   ============================================================ */

.card-heading{

    gap:
        14px !important;

    margin-bottom:
        17px !important;

}


.card-icon{

    width:
        52px !important;

    height:
        52px !important;

    flex:
        0 0 52px !important;

    border-radius:
        11px !important;

    font-size:
        28px !important;

    line-height:
        1 !important;

}


.card-title{

    font-size:
        19px !important;

    line-height:
        1.15 !important;

}


.card-status{

    margin-top:
        7px !important;

    padding:
        4px 8px !important;

    font-size:
        9px !important;

}


/* ============================================================
   CARD DESCRIPTION
   ============================================================ */

.dashboard-card p{

    margin:
        0 0 20px !important;

    color:
        #bcc5ca !important;

    font-size:
        14px !important;

    line-height:
        1.65 !important;

}


/* ============================================================
   CARD BUTTONS
   ============================================================ */

.card-actions{

    gap:
        10px !important;

}


.card-button{

    min-height:
        45px !important;

    padding:
        11px 12px !important;

    border-radius:
        9px !important;

    font-size:
        11px !important;

    letter-spacing:
        .035em !important;

}


/* ============================================================
   BOTTOM NOTE
   ============================================================ */

.dashboard-note{

    margin-top:
        22px !important;

    padding:
        17px 19px !important;

    font-size:
        13px !important;

    line-height:
        1.6 !important;

    border-radius:
        9px !important;

}


/* ============================================================
   RESPONSIVE
   ============================================================ */

@media(
    max-width:1100px
){

    .dashboard-grid{

        grid-template-columns:

            repeat(
                2,
                minmax(0,1fr)
            ) !important;

    }

}


@media(
    max-width:700px
){

    .dashboard-shell{

        width:
            calc(100% - 26px) !important;

        margin:
            18px auto 45px !important;

        padding:
            17px !important;

    }


    


    


    .dashboard-grid{

        grid-template-columns:
            1fr !important;

    }


    .dashboard-card{

        min-height:
            230px !important;

    }

}


/* AUSTRALIA USER DASHBOARD DESIGN V2 END */

</style>
<style>

/* AUSTRALIA USER DASHBOARD 3D CARDS V1 START */


/* ============================================================
   LARGE OUTER DASHBOARD BOX
   ============================================================ */

.dashboard-shell{

    position:relative !important;

    border:
        2px solid
        #8f6a22 !important;

    background:
        linear-gradient(
            145deg,
            rgba(24,30,34,.98),
            rgba(5,8,10,.99)
        ) !important;

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.22),

        inset 0 -3px 8px
        rgba(0,0,0,.75),

        0 3px 0
        #3c2c10,

        0 8px 0
        rgba(0,0,0,.68),

        0 28px 65px
        rgba(0,0,0,.72) !important;

}


/* ============================================================
   HEADER - RAISED METALLIC PANEL
   ============================================================ */







/* ============================================================
   EACH CARD - REAL 3D RAISED PANEL
   ============================================================ */

.dashboard-card{

    position:relative !important;

    overflow:hidden !important;

    isolation:isolate;

    border:
        2px solid
        #80601f !important;

    background:

        linear-gradient(
            145deg,
            #273036 0%,
            #151c20 34%,
            #090d10 58%,
            #050809 100%
        ) !important;

    box-shadow:

        inset 0 2px 0
        rgba(255,255,255,.22),

        inset 2px 0 0
        rgba(255,255,255,.04),

        inset -3px -5px 10px
        rgba(0,0,0,.88),

        0 3px 0
        #3d2d0f,

        0 7px 0
        rgba(0,0,0,.58),

        0 12px 22px
        rgba(0,0,0,.52) !important;

    transform:
        translateY(0);

    transition:
        transform .17s ease,
        box-shadow .17s ease,
        border-color .17s ease !important;

}


/* Gloss across top face */

.dashboard-card:before{

    content:"";

    position:absolute;

    z-index:-1;

    left:2px;

    right:2px;

    top:2px;

    height:42%;

    border-radius:
        10px 10px 50% 50%;

    background:

        linear-gradient(
            180deg,
            rgba(255,255,255,.12),
            rgba(255,255,255,.025) 45%,
            rgba(255,255,255,0)
        );

    pointer-events:none;

}


/* Gold edge shine */

.dashboard-card:after{

    content:"";

    position:absolute;

    left:5%;

    right:5%;

    top:-1px;

    height:2px;

    background:
        linear-gradient(
            90deg,
            transparent,
            rgba(255,196,68,.85),
            rgba(255,230,151,.95),
            rgba(255,196,68,.85),
            transparent
        );

    box-shadow:
        0 0 10px
        rgba(237,174,51,.55);

    pointer-events:none;

}


.dashboard-card:hover{

    transform:
        translateY(-4px) !important;

    border-color:
        #c7942e !important;

    box-shadow:

        inset 0 2px 0
        rgba(255,255,255,.27),

        inset -3px -5px 10px
        rgba(0,0,0,.88),

        0 4px 0
        #4b3610,

        0 10px 0
        rgba(0,0,0,.50),

        0 19px 32px
        rgba(0,0,0,.62),

        0 0 14px
        rgba(220,162,43,.18) !important;

}


/* ============================================================
   ICONS - RECESSED METAL BADGES
   ============================================================ */

.card-icon{

    border:
        2px solid
        #9a7222 !important;

    background:

        linear-gradient(
            145deg,
            #2c3031,
            #0d1011 48%,
            #050606
        ) !important;

    color:
        #f2b938 !important;

    text-shadow:
        0 1px 2px
        #000 !important;

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.24),

        inset 0 -4px 6px
        rgba(0,0,0,.82),

        0 2px 0
        #3e2d0c,

        0 5px 9px
        rgba(0,0,0,.62) !important;

}


/* ============================================================
   TITLES
   ============================================================ */

.card-title{

    color:
        #f0b83d !important;

    text-shadow:

        0 1px 0
        #4b3409,

        0 2px 3px
        rgba(0,0,0,.85) !important;

}


/* ============================================================
   NORMAL BUTTONS - RAISED BLACK METAL
   ============================================================ */

.card-button{

    border:
        1px solid
        #4e575c !important;

    background:

        linear-gradient(
            180deg,
            #273036,
            #101619 52%,
            #070a0c 53%,
            #151b1e
        ) !important;

    color:
        #f1f1f1 !important;

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.22),

        inset 0 -3px 5px
        rgba(0,0,0,.80),

        0 2px 0
        #030404,

        0 5px 8px
        rgba(0,0,0,.58) !important;

    text-shadow:
        0 1px 2px
        #000 !important;

}


.card-button:hover{

    transform:
        translateY(-1px);

    border-color:
        #b0832a !important;

}


/* ============================================================
   GOLD OPEN BUTTONS - REAL 3D GOLD
   ============================================================ */

.card-button.primary{

    border:
        1px solid
        #ffd46c !important;

    background:

        linear-gradient(
            180deg,
            #ffe08a 0%,
            #efb638 15%,
            #c88710 58%,
            #925b05 100%
        ) !important;

    color:
        #171007 !important;

    text-shadow:
        0 1px 0
        rgba(255,255,255,.34) !important;

    box-shadow:

        inset 0 2px 0
        rgba(255,255,255,.58),

        inset 0 -4px 5px
        rgba(100,53,0,.48),

        0 2px 0
        #5c3702,

        0 6px 9px
        rgba(0,0,0,.55),

        0 0 8px
        rgba(230,168,43,.23) !important;

}


.card-button.primary:hover{

    filter:
        brightness(1.10);

    transform:
        translateY(-2px);

}


/* ============================================================
   COMING NEXT - RECESSED
   ============================================================ */

.card-button.disabled{

    background:

        linear-gradient(
            180deg,
            #101619,
            #080b0d
        ) !important;

    border-color:
        #273136 !important;

    box-shadow:

        inset 0 3px 7px
        rgba(0,0,0,.85) !important;

    color:
        #6f777b !important;

}


/* ============================================================
   LIVE / NEXT / PUBLIC STATUS PILLS
   ============================================================ */

.card-status{

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.12),

        0 2px 4px
        rgba(0,0,0,.50) !important;

}


/* ============================================================
   HOME BUTTON - GOLD 3D
   ============================================================ */




/* ============================================================
   LOGOUT BUTTON - DARK RED 3D
   ============================================================ */




/* ============================================================
   BOTTOM NOTE - 3D RECESSED PANEL
   ============================================================ */

.dashboard-note{

    border:
        2px solid
        #75571c !important;

    border-left:
        4px solid
        #e2a72f !important;

    background:

        linear-gradient(
            180deg,
            #171c1e,
            #090c0e
        ) !important;

    box-shadow:

        inset 0 3px 8px
        rgba(0,0,0,.60),

        0 2px 0
        #35270d,

        0 6px 12px
        rgba(0,0,0,.48) !important;

}


/* AUSTRALIA USER DASHBOARD 3D CARDS V1 END */

</style>
<style id="australia-admin-my-map-click-fix">

#admin-my-map-card{
    position:relative !important;
    z-index:5000 !important;
}

#admin-my-map-card .card-actions{
    position:relative !important;
    z-index:5001 !important;
}

#admin-my-map-card #admin-my-map-open{
    position:relative !important;
    z-index:5002 !important;
    pointer-events:auto !important;
    cursor:pointer !important;
}

</style>



<style>

/* ============================================================
   AUSTRALIA ADMIN TOOLS PAGE
   ============================================================ */

.admin-tools-page .dashboard-shell{

    width:
        min(
            1480px,
            calc(100% - 28px)
        ) !important;

    margin:
        14px auto 30px !important;
}


.admin-tools-page 


.admin-tools-page .admin-tools-intro{

    margin:
        0 0 12px;

    padding:
        10px 12px;

    border:
        1px solid
        rgba(214,160,40,.34);

    border-radius:
        9px;

    background:
        rgba(6,11,14,.88);

    color:
        #b8c0c4;

    font-size:
        11px;

    line-height:
        1.4;
}


.admin-tools-page .dashboard-section{

    padding:
        11px !important;
}


.admin-tools-page .dashboard-grid{

    display:
        grid !important;

    grid-template-columns:
        repeat(
            3,
            minmax(0,1fr)
        ) !important;

    gap:
        10px !important;
}


.admin-tools-page .dashboard-card{

    min-height:
        178px !important;

    height:
        100% !important;
}


@media(max-width:1100px){

    .admin-tools-page .dashboard-grid{

        grid-template-columns:
            repeat(
                2,
                minmax(0,1fr)
            ) !important;
    }
}


@media(max-width:700px){

    .admin-tools-page .dashboard-grid{

        grid-template-columns:
            1fr !important;
    }
}

</style>

<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=<?= filemtime(__DIR__ . '/assets/css/ag-uniform-site-v12.css') ?>">
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


<script>
document.body.classList.add("admin-tools-page");
</script>


<div class="dashboard-shell">


<?php
$siteHeaderTitle = "ADMIN TOOLS";
$siteHeaderButton = "ADMIN HOME";
$siteHeaderLink = "/Other/admin-home.php";
require_once __DIR__ . "/includes/site-header.php";
?>


<div class="admin-tools-intro">

    Grid Owner administration tools are kept on this page so
    the Admin Dashboard can stay focused on the administrator's
    personal grid account, inventory, regions, profile and
    everyday Grid activity.

</div>


<section class="dashboard-section">


<div class="section-title">
    ADMIN TOOLS
</div>


<div class="dashboard-grid">

<article class="dashboard-card">

    <div class="card-heading">

        <div class="card-icon">
            <img class="ag-sentinel-direct-icon ag-sentinel-xl" src="/Other/assets/icons/sentinel/control-panel.png" alt="" aria-hidden="true" draggable="false" decoding="async">
        </div>

        <div>

            <h2 class="card-title">
                CONTROL PANEL
            </h2>

            <span class="card-status live">
                ADMIN
            </span>

        </div>

    </div>


    <p>
        Open the Grid Control Panel
        for grid-owner administration and
        management tools.
    </p>


    <div class="card-actions">

        <a
            class="card-button primary"
            href="/Other/australia-panel.php">

            OPEN

        </a>

    </div>

</article>

<article class="dashboard-card">

    <div class="card-heading">

        <div class="card-icon">
            <img class="ag-sentinel-direct-icon ag-sentinel-xl" src="/Other/assets/icons/sentinel/statistics.png" alt="" aria-hidden="true" draggable="false" decoding="async">
        </div>

        <div>

            <h2 class="card-title">
                STATS
            </h2>

            <span class="card-status live">
                ADMIN
            </span>

        </div>

    </div>


    <p>
        Open the current Grid
        statistics and health diagnostics
        tools.
    </p>


    <div class="card-actions">

        <a
            class="card-button primary"
            href="/Other/health-diagnostics.php">

            OPEN

        </a>

    </div>

</article>

<article class="dashboard-card admin-tool-card">

    <div class="card-heading">

        <div class="card-icon icon-gold"><img class="ag-sentinel-direct-icon ag-sentinel-xl" src="/Other/assets/icons/sentinel/region.png" alt="" aria-hidden="true" draggable="false" decoding="async"></div>

        <div>

            <h2 class="card-title">
                REGION MANAGER
            </h2>

            <span class="card-status live">
                ADMIN
            </span>

        </div>

    </div>


    <p>
        View and manage all Grid regions.
        This becomes the OpenSimulator-first replacement
        for the main Regions window.
    </p>


    <div class="card-actions">

        <a
            class="card-button primary"
            href="/Other/admin-regions.php"
        >
            OPEN
        </a>

    </div>

</article>

<article class="dashboard-card admin-tool-card">

    <div class="card-heading">

        <div class="card-icon icon-gold"><img class="ag-sentinel-direct-icon ag-sentinel-xl" src="/Other/assets/icons/sentinel/add-region.png" alt="" aria-hidden="true" draggable="false" decoding="async"></div>

        <div>

            <h2 class="card-title">
                CREATE REGION
            </h2>

            <span class="card-status next">
                BUILDING
            </span>

        </div>

    </div>


    <p>
        Create a new OpenSimulator region with
        size, coordinates, ports, prim limits,
        maps, physics, scripts and permissions.
    </p>


    <div class="card-actions">

        <a
            class="card-button primary"
            href="/Other/admin-create-region.php"
        >
            OPEN
        </a>

    </div>

</article>

<article class="dashboard-card admin-tool-card">

    <div class="card-heading">

        <div class="card-icon icon-silver"><img class="ag-sentinel-direct-icon ag-sentinel-xl" src="/Other/assets/icons/sentinel/image.png" alt="" aria-hidden="true" draggable="false" decoding="async"></div>

        <div>

            <h2 class="card-title">
                REGION MAP TEXTURES
            </h2>

            <span class="card-status next">
                PLANNED
            </span>

        </div>

    </div>


    <p>
        Upload and manage custom web-map artwork
        for individual Grid regions.
    </p>


    <div class="card-actions">

        <span class="card-button disabled">
            COMING NEXT
        </span>

    </div>

</article>

<article class="dashboard-card admin-tool-card">

    <div class="card-heading">

        <div class="card-icon icon-silver"><img class="ag-sentinel-direct-icon ag-sentinel-xl" src="/Other/assets/icons/sentinel/edit.png" alt="" aria-hidden="true" draggable="false" decoding="async"></div>

        <div>

            <h2 class="card-title">
                SITE &amp; PAGE DESIGN
            </h2>

            <span class="card-status next">
                PLANNED
            </span>

        </div>

    </div>


    <p>
        Change front-page artwork, dashboard backgrounds,
        page textures and other grid branding without
        editing website files manually.
    </p>


    <div class="card-actions">

        <span class="card-button disabled">
            COMING NEXT
        </span>

    </div>

</article>

</div>


</section>


</div>


<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>
</body>

</html>






