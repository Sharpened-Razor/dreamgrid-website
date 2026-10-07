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
Grid - Login Help / FAQ
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
            #1b2730 0,
            #090d10 55%,
            #050709 100%
        );

    color:#edf1f4;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}

.shell{

    width:min(
        950px,
        calc(100% - 30px)
    );

    margin:
        35px auto 60px;

}

.header,
.card{

    border:
        1px solid
        rgba(255,255,255,.10);

    border-radius:13px;

    background:
        rgba(10,15,19,.96);

}

.header{

    padding:24px;

    border-color:
        rgba(244,179,35,.28);

}

.eyebrow{

    color:#f4b323;

    font-size:10px;

    font-weight:900;

    letter-spacing:.15em;

}

h1{

    margin:
        6px 0 4px;

}

.header p,
.card p{

    color:#aeb9c0;

    line-height:1.6;

}

.back{

    display:inline-block;

    margin-top:15px;

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

.cards{

    display:grid;

    gap:12px;

    margin-top:16px;

}

.card{

    padding:18px;

}

.card h2{

    margin:
        0 0 7px;

    color:#f4b323;

    font-size:17px;

}

.address{

    padding:11px;

    border-radius:7px;

    background:#05090c;

    color:#fff;

    font-family:
        Consolas,
        monospace;

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
        Login Help / FAQ
    </h1>

    <p>
        Help getting connected to Grid.
        You do not need to be logged in to use this page.
    </p>

    <a
        class="back ag-return-button"
        href="javascript:history.back()">
        BACK
    </a>

</div>


<div class="cards">


<div class="card">

    <h2>
        What is the Grid address?
    </h2>

    <div class="address">
        <?= htmlspecialchars(ag_dg_login_url(), ENT_QUOTES, 'UTF-8') ?>
    </div>

</div>


<div class="card">

    <h2>
        What name do I use to log in?
    </h2>

    <p>
        Use the First Name and Last Name of the avatar
        account you created on Grid.
    </p>

</div>


<div class="card">

    <h2>
        I forgot my password.
    </h2>

    <p>
        Return to the main WiFi page and use
        FORGOT PASSWORD underneath the login box.
    </p>

</div>


<div class="card">

    <h2>
        My viewer will not log in.
    </h2>

    <p>
        Check that Grid is selected,
        check both avatar names,
        re-enter your password,
        and make sure the Grid address
        has been added correctly to your viewer.
    </p>

</div>


<div class="card">

    <h2>
        Where are the full connection instructions?
    </h2>

    <p>
        Open GETTING STARTED from the main WiFi page
        for the complete step-by-step setup.
    </p>

</div>


</div>

</div>


<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>



