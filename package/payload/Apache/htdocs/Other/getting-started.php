<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

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
Grid - Getting Started
</title>


<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    min-height:100vh;

    background:
        radial-gradient(
            circle at top,
            #1c2830 0,
            #090d10 58%,
            #050709 100%
        );

    color:#eef2f4;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}

.shell{

    width:min(
        1000px,
        calc(100% - 30px)
    );

    margin:
        35px auto 60px;

}

.header{

    padding:24px;

    border:
        1px solid
        rgba(244,179,35,.28);

    border-radius:14px;

    background:
        rgba(10,15,19,.96);

}

.eyebrow{

    color:#f4b323;

    font-size:10px;

    font-weight:900;

    letter-spacing:.15em;

}

h1{

    margin:
        6px 0 5px;

}

.header p,
.step p{

    color:#adb9c0;

    line-height:1.6;

}

.back{

    display:inline-block;

    margin-top:14px;

    padding:10px 15px;

    border-radius:8px;

    background:
        linear-gradient(
            #efb633,
            #ad700b
        );

    color:#171006;

    font-size:12px;

    font-weight:900;

    text-decoration:none;

}

.steps{

    display:grid;

    gap:13px;

    margin-top:17px;

}

.step{

    display:grid;

    grid-template-columns:
        62px 1fr;

    gap:16px;

    padding:19px;

    border:
        1px solid
        rgba(255,255,255,.09);

    border-radius:12px;

    background:
        rgba(10,15,19,.96);

}

.number{

    width:48px;

    height:48px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:50%;

    border:
        1px solid
        rgba(244,179,35,.45);

    background:
        rgba(244,179,35,.08);

    color:#ffc54d;

    font-size:19px;

    font-weight:900;

}

.step h2{

    margin:
        2px 0 6px;

    color:#f4b323;

    font-size:17px;

}

.address{

    margin-top:9px;

    padding:11px;

    border-radius:7px;

    background:#05090c;

    color:#fff;

    font-family:
        Consolas,
        monospace;

}

@media(max-width:600px){

    .step{

        grid-template-columns:
            1fr;

    }

}

</style>
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

</head>


<body>


<div class="shell">


<div class="header">

    <div class="eyebrow">
        Grid
    </div>

    <h1>
        Getting Started
    </h1>

    <p>
        Follow these five steps to connect
        and begin exploring Grid.
    </p>

    <a
        class="back ag-return-button"
        href="javascript:history.back()">
        BACK
    </a>

</div>


<div class="steps">


<div class="step">

    <div class="number">
        1
    </div>

    <div>

        <h2>
            Download an OpenSim-compatible viewer
        </h2>

        <p>
            Firestorm for OpenSim is the recommended
            starting viewer.
        </p>

    </div>

</div>


<div class="step">

    <div class="number">
        2
    </div>

    <div>

        <h2>
            Add Grid
        </h2>

        <p>
            Open the OpenSim Grid Manager in your viewer
            and add this grid address:
        </p>

        <div class="address">
            <?= htmlspecialchars(ag_dg_login_url(), ENT_QUOTES, 'UTF-8') ?>
        </div>

    </div>

</div>


<div class="step">

    <div class="number">
        3
    </div>

    <div>

        <h2>
            Log In
        </h2>

        <p>
            Select Grid,
            enter your avatar First Name,
            Last Name and password,
            then log in.
        </p>

    </div>

</div>


<div class="step">

    <div class="number">
        4
    </div>

    <div>

        <h2>
            Visit Welcome
        </h2>

        <p>
            Once in-world,
            visit the Welcome region to begin.
        </p>

    </div>

</div>


<div class="step">

    <div class="number">
        5
    </div>

    <div>

        <h2>
            Explore the Grid
        </h2>

        <p>
            Use the Grid Map
            and your viewer World Map
            to find regions and destinations.
        </p>

    </div>

</div>


</div>

</div>


<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>



