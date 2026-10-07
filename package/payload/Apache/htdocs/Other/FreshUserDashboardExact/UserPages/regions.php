<?php

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';
require_once dirname(__DIR__, 2) . '/core/icons.php';


$session =
    ag_current_session();

if (!$session) {

    header(
        'Location: /Other/login.php'
    );

    exit;
}


ag_no_cache();


$sid =
    trim(
        (string)(
            $_GET['sid']
            ??
            ''
        )
    );


$regionIcon =
    ag_icon(
        'regions',
        null,
        'rp-region-icon-svg'
    );

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Regions</title>


<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-panel-v1.css?v=8">


<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-regions-v5.css?v=6">

<style id="australia-oar-two-slot-style">

/* AUSTRALIA OAR TWO SLOT UI V1 */

.rp-oar-two-slot{
    margin:12px 0 14px;
    padding:10px;
    border:1px solid rgba(199,148,0,.28);
    border-radius:5px;
    background:rgba(3,8,8,.48);
}

.rp-oar-two-slot-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:8px;
}

.rp-oar-two-slot-title{
    color:#e6b72e;
    font-size:11px;
    font-weight:900;
    letter-spacing:.08em;
}

.rp-oar-two-slot-sub{
    font-size:9px;
    opacity:.62;
    letter-spacing:.05em;
}

.rp-oar-two-slot-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:8px;
}

.rp-oar-slot{
    min-width:0;
    min-height:96px;
    padding:10px;
    border:1px solid rgba(93,104,107,.38);
    border-radius:4px;
    background:rgba(12,17,17,.72);
    display:flex;
    flex-direction:column;
    gap:5px;
}

.rp-oar-slot.empty{
    align-items:center;
    justify-content:center;
    text-align:center;
    border-style:dashed;
    opacity:.62;
}

.rp-oar-slot-number{
    color:#e5b83f;
    font-size:10px;
    font-weight:900;
    letter-spacing:.08em;
}

.rp-oar-slot-name{
    overflow-wrap:anywhere;
    font-size:11px;
    font-weight:700;
}

.rp-oar-slot-info{
    font-size:9px;
    line-height:1.45;
    opacity:.72;
}

.rp-oar-slot-download{
    margin-top:auto;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:max-content;
    min-width:84px;
    padding:5px 10px;
    border:1px solid rgba(204,155,0,.45);
    border-radius:3px;
    color:#e7bd45;
    background:linear-gradient(#171a19,#0b0d0d);
    font-size:9px;
    font-weight:900;
    letter-spacing:.05em;
    text-decoration:none;
}

.rp-oar-slot-download:hover{
    border-color:#d6a500;
    color:#ffe28a;
}

.rp-oar-two-slot-safety{
    margin-top:8px;
    padding-top:7px;
    border-top:1px solid rgba(199,148,0,.18);
    font-size:9px;
    line-height:1.45;
    opacity:.68;
}

.rp-oar-two-slot-safety strong{
    color:#e5b83f;
    margin-right:5px;
}

@media(max-width:760px){

    .rp-oar-two-slot-grid{
        grid-template-columns:1fr;
    }
}


/* ============================================================
   REGION ACTION CONFIRMATION MODAL
   ============================================================ */

.rp-oar-slot-delete{
    display:block;
    width:100%;
    margin-top:6px;
    padding:7px 9px;

    border:1px solid rgba(205,75,75,.58);
    border-radius:4px;

    background:rgba(80,22,22,.28);
    color:#ffabab;

    font-size:9px;
    font-weight:800;
    letter-spacing:.06em;

    cursor:pointer;
}

.rp-oar-slot-delete:hover{
    border-color:#d65a5a;
    background:rgba(115,31,31,.44);
    color:#ffe0e0;
}

.rp-confirm-backdrop{
    position:fixed;
    inset:0;

    z-index:2147483000;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:22px;

    background:rgba(0,0,0,.74);

    opacity:0;
    visibility:hidden;

    transition:
        opacity .14s ease,
        visibility .14s ease;
}

.rp-confirm-backdrop.open{
    opacity:1;
    visibility:visible;
}

.rp-confirm-dialog{
    width:min(520px,100%);

    overflow:hidden;

    border:1px solid rgba(205,164,70,.62);
    border-radius:8px;

    background:#171a1c;

    box-shadow:
        0 24px 72px
        rgba(0,0,0,.76);
}

.rp-confirm-head{
    padding:15px 18px 13px;

    border-bottom:
        1px solid rgba(205,164,70,.24);

    background:#101315;
}

.rp-confirm-title{
    margin:0;

    color:#d7ad51;

    font-size:13px;
    font-weight:900;
    letter-spacing:.08em;
}

.rp-confirm-body{
    padding:18px;

    color:#dbe1e4;

    font-size:12px;
    line-height:1.6;
}

.rp-confirm-region{
    display:block;

    margin-top:10px;

    color:#f0c865;

    font-weight:800;
}

.rp-confirm-buttons{
    display:flex;
    justify-content:flex-end;

    gap:9px;

    padding:13px 18px 16px;

    border-top:
        1px solid rgba(255,255,255,.06);

    background:#111518;
}

.rp-confirm-button{
    min-width:105px;

    padding:9px 13px;

    border-radius:4px;

    font-size:10px;
    font-weight:900;
    letter-spacing:.05em;

    cursor:pointer;
}

.rp-confirm-cancel{
    border:1px solid #465057;
    background:#252b2f;
    color:#d5dde1;
}

.rp-confirm-accept{
    border:1px solid #b98b2c;
    background:#7c5c1d;
    color:#fff1bf;
}

.rp-confirm-accept:hover{
    background:#987127;
}

.rp-confirm-accept.danger{
    border-color:#a94343;
    background:#742d2d;
    color:#ffd5d5;
}

.rp-confirm-accept.danger:hover{
    background:#913737;
}
</style>
</head>


<body>


<main class="cp-page rp-page">


<section class="cp-intro">


    <div class="cp-intro-left">


        <div class="cp-intro-icon">

            <?=ag_icon(
                'regions',
                null,
                'cp-intro-svg'
            )?>

        </div>


        <div>

            <div class="cp-intro-kicker">
                DASHBOARD
            </div>

            <div class="cp-intro-title">
                Regions
            </div>

        </div>


    </div>


    <div class="cp-intro-note">
        Live Region Operations
    </div>


</section>



<section class="rp-overview">


    <div class="rp-overview-heading">


        <div>

            <span class="rp-eyebrow">
                REGION OPERATIONS
            </span>

            <h2>
                Grid Performance & Region Control
            </h2>

            <p>
                Live region and Windows process performance.
            </p>

        </div>


        <div class="rp-overview-tools">


            <input
                id="rp-search"
                type="search"
                autocomplete="off"
                placeholder="Search regions">


            <button
                id="rp-refresh"
                type="button"
                class="cp-button"
            >
                REFRESH
            </button>


        </div>


    </div>


    <div class="rp-summary">


        <div class="rp-summary-item">

            <span>
                REGIONS
            </span>

            <strong id="rp-total">
                —
            </strong>

            <small>
                Total regions
            </small>

        </div>


        <div class="rp-summary-item">

            <span>
                ONLINE
            </span>

            <strong
                id="rp-online"
                class="online"
            >
                —
            </strong>

            <small>
                Running now
            </small>

        </div>


        <div class="rp-summary-item">

            <span>
                AVATARS
            </span>

            <strong id="rp-avatars">
                —
            </strong>

            <small>
                Across regions
            </small>

        </div>


        <div class="rp-summary-item">

            <span>
                HOST CPU
            </span>

            <strong
                id="rp-host-cpu"
                class="host-value"
            >
                —
            </strong>

            <small id="rp-host-cpu-note">
                Physical / logical
            </small>

        </div>


        <div class="rp-summary-item">

            <span>
                HOST MEMORY
            </span>

            <strong
                id="rp-host-memory"
                class="host-value"
            >
                —
            </strong>

            <small>
                Installed RAM
            </small>

        </div>


        <div class="rp-summary-item">

            <span>
                REGION CONTROL
            </span>

            <strong
                id="rp-control"
                class="rp-control-state"
            >
                CHECKING
            </strong>

            <small id="rp-control-note">
                Checking permissions
            </small>

        </div>


    </div>


</section>



<div
    id="rp-message"
    class="rp-message"
    aria-live="polite"
>
    Loading live region information...
</div>



<section
    id="rp-grid"
    class="rp-grid"
>


    <div class="rp-loading">

        <div class="rp-loader"></div>

        <strong>
            Loading Region Performance
        </strong>

        <span>
            Reading region and process statistics...
        </span>

    </div>


</section>


</main>



<style id="australia-regions-v72d-live-meters">

/* ============================================================
   AUSTRALIA REGIONS V7.2D LIVE METER CSS
   ============================================================ */

.rp-simulator-grid{
    display:grid !important;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(180px,1fr)
        ) !important;

    gap:14px !important;
}


.rp-sim-item{
    position:relative;

    display:flex !important;
    flex-direction:column;

    min-height:155px;

    padding:16px !important;

    overflow:hidden;

    border:
        1px solid
        rgba(255,193,58,.25) !important;

    border-radius:10px !important;

    background:
        linear-gradient(
            180deg,
            rgba(21,28,33,.98),
            rgba(7,11,14,.98)
        ) !important;

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.04),
        0 8px 22px
        rgba(0,0,0,.30);
}


.rp-sim-item::before{
    content:"";

    position:absolute;

    top:0;
    left:0;
    right:0;

    height:2px;

    background:
        linear-gradient(
            90deg,
            transparent,
            #d99b16,
            #ffc13a,
            transparent
        );

    opacity:.70;
}


.rp-sim-item > span{
    color:#95a5ae !important;

    font-size:10px !important;
    font-weight:800 !important;

    letter-spacing:.09em !important;
}


.rp-sim-item > strong{
    display:block;

    margin-top:7px;

    color:#f1f8fb !important;

    font-size:28px !important;
    line-height:1 !important;

    font-weight:900 !important;
}


.rp-sim-item > small{
    display:block;

    min-height:25px;

    margin-top:7px;

    color:#71808a !important;

    font-size:10px !important;
}


.rp-v72d-meter{
    margin-top:auto;
    padding-top:13px;
}


.rp-v72d-track{
    position:relative;

    width:100%;
    height:10px;

    overflow:hidden;

    border-radius:999px;

    background:
        linear-gradient(
            180deg,
            #040708,
            #11181c
        );

    border:
        1px solid
        rgba(255,255,255,.08);

    box-shadow:
        inset 0 2px 5px
        rgba(0,0,0,.85);
}


.rp-v72d-fill{
    position:relative;

    width:0;
    height:100%;

    border-radius:999px;

    transition:
        width .9s
        cubic-bezier(.2,.8,.2,1);

    background:
        linear-gradient(
            90deg,
            #167ca6,
            #55dfff
        );

    box-shadow:
        0 0 12px
        rgba(69,214,255,.54);
}


.rp-v72d-fill.good{
    background:
        linear-gradient(
            90deg,
            #16875a,
            #53e39d
        );

    box-shadow:
        0 0 12px
        rgba(83,227,157,.50);
}


.rp-v72d-fill.warning{
    background:
        linear-gradient(
            90deg,
            #a96c12,
            #ffc13a
        );

    box-shadow:
        0 0 12px
        rgba(255,193,58,.50);
}


.rp-v72d-fill.critical{
    background:
        linear-gradient(
            90deg,
            #932d2d,
            #ff6161
        );

    box-shadow:
        0 0 12px
        rgba(255,90,90,.52);
}


.rp-v72d-fill::after{
    content:"";

    position:absolute;

    top:0;
    bottom:0;

    width:35%;

    background:
        linear-gradient(
            90deg,
            transparent,
            rgba(255,255,255,.40),
            transparent
        );

    animation:
        rp-v72d-sweep
        2.4s
        linear
        infinite;
}


.rp-v72d-scale{
    display:flex;

    align-items:center;
    justify-content:space-between;

    gap:8px;

    margin-top:6px;

    color:#61717b;

    font-size:9px;
    font-weight:700;
}


.rp-v72d-state{
    color:#91a2ac;
}


.rp-meter{
    height:10px !important;

    overflow:hidden !important;

    border-radius:999px !important;

    border:
        1px solid
        rgba(255,255,255,.08) !important;

    background:#071014 !important;

    box-shadow:
        inset 0 2px 5px
        rgba(0,0,0,.85) !important;
}


.rp-meter > span{
    height:100% !important;

    border-radius:999px !important;

    background:
        linear-gradient(
            90deg,
            #167ca6,
            #55dfff
        ) !important;

    box-shadow:
        0 0 12px
        rgba(69,214,255,.48) !important;

    transition:
        width .9s
        cubic-bezier(.2,.8,.2,1) !important;
}


.rp-health i{
    animation:
        rp-v72d-pulse
        2s
        ease-out
        infinite;
}


@keyframes rp-v72d-sweep{

    from{
        left:-45%;
    }

    to{
        left:120%;
    }
}


@keyframes rp-v72d-pulse{

    0%{
        box-shadow:
            0 0 0 0
            rgba(83,227,157,.45);
    }

    70%{
        box-shadow:
            0 0 0 8px
            rgba(83,227,157,0);
    }

    100%{
        box-shadow:
            0 0 0 0
            rgba(83,227,157,0);
    }
}


@media(max-width:850px){

    .rp-simulator-grid{
        grid-template-columns:
            repeat(
                2,
                minmax(0,1fr)
            ) !important;
    }
}


@media(max-width:540px){

    .rp-simulator-grid{
        grid-template-columns:
            1fr !important;
    }
}

</style>


<style id="australia-regions-v73-dashboard">

/* ============================================================
   AUSTRALIA REGIONS V7.3 PERFORMANCE DASHBOARD
   ============================================================ */


/* Retire the V7.2 progress bars completely */

.rp-v72d-meter,
.rp-v72-meter,
.rp-live-meter-wrap{
    display:none !important;
}


/* ------------------------------------------------------------
   MAIN SIMULATOR DASHBOARD
   ------------------------------------------------------------ */

.rp-simulator{
    padding-bottom:18px !important;
}


.rp-simulator-grid{
    display:grid !important;

    grid-template-columns:
        repeat(
            12,
            minmax(0,1fr)
        ) !important;

    gap:14px !important;

    align-items:stretch;
}


/* ------------------------------------------------------------
   THREE MAIN LIVE GRAPH PANELS
   ------------------------------------------------------------ */

.rp-sim-item.rp-v73-primary{
    grid-column:
        span 4 !important;

    min-height:270px !important;

    padding:18px 18px 16px !important;

    display:flex !important;

    flex-direction:column !important;

    position:relative;

    overflow:hidden;

    border-radius:10px !important;

    border:
        1px solid
        rgba(38,132,178,.32) !important;

    background:
        linear-gradient(
            180deg,
            #0c171e 0%,
            #071016 100%
        ) !important;

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.04),
        0 12px 30px
        rgba(0,0,0,.34);
}


.rp-sim-item.rp-v73-primary::before{
    content:"";

    position:absolute;

    left:0;
    right:0;
    top:0;

    height:2px;

    background:
        linear-gradient(
            90deg,
            transparent,
            #18a9e0,
            #50dcff,
            transparent
        );

    opacity:.9;
}


.rp-v73-primary[data-v73-metric="PHYSICS FPS"]::before{
    background:
        linear-gradient(
            90deg,
            transparent,
            #20a96a,
            #54e39d,
            transparent
        );
}


.rp-v73-primary[data-v73-metric="SCRIPT EVENTS/S"]::before{
    background:
        linear-gradient(
            90deg,
            transparent,
            #c78519,
            #ffc13a,
            transparent
        );
}


.rp-v73-primary > span{
    color:#a5b5c0 !important;

    font-size:11px !important;

    font-weight:800 !important;

    letter-spacing:.08em !important;
}


.rp-v73-primary > strong{
    margin-top:8px !important;

    color:#f4fbff !important;

    font-size:34px !important;

    line-height:1 !important;

    font-weight:900 !important;

    letter-spacing:-.02em;
}


.rp-v73-primary > small{
    margin-top:7px !important;

    color:#718795 !important;

    font-size:10px !important;
}


/* ------------------------------------------------------------
   GRAPH AREA
   ------------------------------------------------------------ */

.rp-v73-chart-shell{
    position:relative;

    width:100%;

    height:132px;

    margin-top:auto;

    padding-top:14px;
}


.rp-v73-canvas{
    display:block;

    width:100%;

    height:118px;

    border-radius:4px;
}


.rp-v73-chart-meta{
    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:12px;

    margin-top:7px;

    color:#647986;

    font-size:9px;

    font-weight:700;

    letter-spacing:.04em;
}


.rp-v73-live-dot{
    display:inline-flex;

    align-items:center;

    gap:6px;

    color:#75dffb;
}


.rp-v73-live-dot::before{
    content:"";

    width:6px;

    height:6px;

    border-radius:50%;

    background:#4ddaff;

    box-shadow:
        0 0 8px
        rgba(77,218,255,.9);

    animation:
        rp-v73-live-pulse
        1.6s
        ease-out
        infinite;
}


/* ------------------------------------------------------------
   SECONDARY TELEMETRY
   ------------------------------------------------------------ */

.rp-sim-item.rp-v73-secondary{
    grid-column:
        span 3 !important;

    min-height:112px !important;

    padding:15px !important;

    display:flex !important;

    flex-direction:column !important;

    justify-content:center;

    position:relative;

    border-radius:8px !important;

    border:
        1px solid
        rgba(255,255,255,.08) !important;

    background:
        linear-gradient(
            180deg,
            rgba(15,21,25,.98),
            rgba(8,12,15,.98)
        ) !important;

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.025);
}


.rp-v73-secondary > span{
    color:#81919b !important;

    font-size:9px !important;

    font-weight:800 !important;

    letter-spacing:.08em !important;
}


.rp-v73-secondary > strong{
    margin-top:7px !important;

    color:#e9f2f6 !important;

    font-size:22px !important;

    line-height:1 !important;

    font-weight:850 !important;
}


.rp-v73-secondary > small{
    margin-top:7px !important;

    color:#61717b !important;

    font-size:9px !important;
}


/* ------------------------------------------------------------
   SECTION HEADING
   ------------------------------------------------------------ */

.rp-simulator .rp-section-heading{
    margin-bottom:14px !important;
}


.rp-simulator .rp-section-heading > div:first-child > span{
    color:#ffc13a !important;

    font-weight:850 !important;

    letter-spacing:.10em !important;
}


.rp-v73-session-label{
    display:inline-flex;

    align-items:center;

    gap:7px;

    margin-left:10px;

    padding:
        5px 9px;

    border-radius:5px;

    border:
        1px solid
        rgba(43,174,223,.28);

    background:
        rgba(16,89,119,.15);

    color:#64dfff;

    font-size:9px;

    font-weight:800;

    letter-spacing:.07em;
}


/* ------------------------------------------------------------
   EXISTING WINDOWS CPU/RAM BARS
   No tiny simulator bars.
   ------------------------------------------------------------ */

.rp-performance-item .rp-meter{
    height:5px !important;

    margin-top:11px;

    border-radius:999px !important;

    overflow:hidden;

    background:
        #071016 !important;

    border:
        1px solid
        rgba(255,255,255,.08) !important;
}


.rp-performance-item .rp-meter > span{
    height:100% !important;

    border-radius:999px !important;

    background:
        linear-gradient(
            90deg,
            #168ebf,
            #54dcff
        ) !important;

    box-shadow:
        0 0 8px
        rgba(64,210,255,.42);
}


/* ------------------------------------------------------------
   ANIMATION
   ------------------------------------------------------------ */

@keyframes rp-v73-live-pulse{

    0%{
        box-shadow:
            0 0 0 0
            rgba(77,218,255,.50);
    }

    70%{
        box-shadow:
            0 0 0 7px
            rgba(77,218,255,0);
    }

    100%{
        box-shadow:
            0 0 0 0
            rgba(77,218,255,0);
    }
}


/* ------------------------------------------------------------
   RESPONSIVE
   ------------------------------------------------------------ */

@media(max-width:1200px){

    .rp-sim-item.rp-v73-primary{
        grid-column:
            span 6 !important;
    }

    .rp-sim-item.rp-v73-secondary{
        grid-column:
            span 4 !important;
    }
}


@media(max-width:760px){

    .rp-sim-item.rp-v73-primary{
        grid-column:
            span 12 !important;
    }

    .rp-sim-item.rp-v73-secondary{
        grid-column:
            span 6 !important;
    }
}


@media(max-width:520px){

    .rp-sim-item.rp-v73-secondary{
        grid-column:
            span 12 !important;
    }
}

</style>


<style id="australia-regions-v74-performance">

/* ============================================================
   AUSTRALIA REGIONS V7.4 COMPACT PERFORMANCE
   ============================================================ */


/*
 * Hide all previous oversized simulator layouts.
 * Their values remain in the DOM for the live dashboard engine.
 */

.rp-simulator .rp-section-heading{
    display:none !important;
}

.rp-simulator-grid{
    display:none !important;
}

.rp-v72d-meter,
.rp-v72-meter,
.rp-live-meter-wrap,
.rp-v73-chart-shell{
    display:none !important;
}


/*
 * Compact simulator area.
 */

.rp-simulator{
    padding:
        14px 18px 18px !important;

    border-top:
        1px solid
        rgba(255,255,255,.09);
}


/*
 * Main panel.
 *
 * Deliberately capped in width so it looks like the
 * reference dashboard instead of filling the entire region card.
 */

.rp-v74-performance{
    width:
        min(
            760px,
            100%
        );

    overflow:hidden;

    border-radius:8px;

    border:
        1px solid
        rgba(28,126,185,.42);

    background:
        linear-gradient(
            180deg,
            #071728 0%,
            #04101d 100%
        );

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.035),
        0 8px 24px
        rgba(0,0,0,.26);
}


/*
 * Header.
 */

.rp-v74-header{
    height:38px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:
        0 13px;

    border-bottom:
        1px solid
        rgba(70,140,185,.22);

    background:
        linear-gradient(
            180deg,
            rgba(12,39,64,.84),
            rgba(5,23,39,.72)
        );
}


.rp-v74-title{
    display:flex;

    align-items:center;

    gap:9px;

    color:#d6e8f5;

    font-size:12px;

    font-weight:800;

    letter-spacing:.04em;
}


.rp-v74-bars{
    display:flex;

    align-items:flex-end;

    gap:2px;

    width:18px;

    height:18px;
}


.rp-v74-bars i{
    display:block;

    width:3px;

    border-radius:1px;

    background:#1bc8ff;

    box-shadow:
        0 0 7px
        rgba(27,200,255,.55);
}


.rp-v74-bars i:nth-child(1){
    height:7px;
}

.rp-v74-bars i:nth-child(2){
    height:13px;
}

.rp-v74-bars i:nth-child(3){
    height:18px;
}

.rp-v74-bars i:nth-child(4){
    height:10px;
}


/*
 * Range controls.
 */

.rp-v74-ranges{
    display:flex;

    align-items:center;

    gap:2px;
}


.rp-v74-range{
    min-width:31px;

    height:24px;

    padding:
        0 7px;

    border:0;

    border-radius:3px;

    background:transparent;

    color:#708ca1;

    font-size:9px;

    font-weight:700;

    cursor:pointer;
}


.rp-v74-range:hover{
    color:#d7efff;

    background:
        rgba(17,116,173,.20);
}


.rp-v74-range.active{
    color:#ffffff;

    background:
        linear-gradient(
            180deg,
            #168dea,
            #0872ce
        );

    box-shadow:
        0 0 8px
        rgba(20,144,240,.40);
}


/*
 * Three metric columns.
 */

.rp-v74-body{
    display:grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0,1fr)
        );

    min-height:158px;
}


.rp-v74-cell{
    min-width:0;

    padding:
        13px 14px 11px;

    border-right:
        1px solid
        rgba(72,129,163,.18);
}


.rp-v74-cell:last-child{
    border-right:0;
}


/*
 * Metric heading.
 */

.rp-v74-metric-head{
    display:flex;

    align-items:center;

    gap:10px;

    min-height:31px;
}


.rp-v74-icon{
    width:28px;

    height:28px;

    display:flex;

    align-items:center;

    justify-content:center;

    flex:
        0 0 28px;

    color:#22ccff;

    font-size:18px;

    font-weight:900;

    text-shadow:
        0 0 8px
        rgba(34,204,255,.48);
}


.rp-v74-icon.green{
    color:#52e69b;

    text-shadow:
        0 0 8px
        rgba(82,230,155,.44);
}


.rp-v74-icon.gold{
    color:#ffc13a;

    text-shadow:
        0 0 8px
        rgba(255,193,58,.42);
}


.rp-v74-label{
    color:#9eb5c6;

    font-size:10px;

    font-weight:700;

    white-space:nowrap;
}


/*
 * Main value.
 */

.rp-v74-value{
    margin:
        2px 0 6px
        38px;

    color:#f0f7ff;

    font-size:21px;

    line-height:1;

    font-weight:850;

    letter-spacing:-.02em;
}


.rp-v74-value small{
    margin-left:3px;

    color:#8299aa;

    font-size:9px;

    font-weight:700;
}


/*
 * Graph.
 */

.rp-v74-chart-row{
    display:grid;

    grid-template-columns:
        26px minmax(0,1fr);

    gap:5px;

    align-items:stretch;

    margin-top:3px;
}


.rp-v74-yaxis{
    height:76px;

    display:flex;

    flex-direction:column;

    justify-content:space-between;

    align-items:flex-end;

    padding:
        1px 1px 1px 0;

    color:#6f8ba0;

    font-size:8px;

    font-weight:600;
}


.rp-v74-canvas{
    display:block;

    width:100%;

    height:76px;
}


/*
 * Small live status strip.
 */

.rp-v74-footer{
    height:24px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:
        0 13px;

    border-top:
        1px solid
        rgba(57,119,157,.15);

    color:#587488;

    font-size:8px;

    font-weight:700;

    letter-spacing:.03em;
}


.rp-v74-live{
    display:flex;

    align-items:center;

    gap:6px;

    color:#65cce9;
}


.rp-v74-live::before{
    content:"";

    width:5px;

    height:5px;

    border-radius:50%;

    background:#32d6ff;

    box-shadow:
        0 0 7px
        rgba(50,214,255,.8);
}


/*
 * Responsive.
 */

@media(max-width:700px){

    .rp-v74-performance{
        width:100%;
    }

    .rp-v74-body{
        grid-template-columns:
            1fr;
    }

    .rp-v74-cell{
        border-right:0;

        border-bottom:
            1px solid
            rgba(72,129,163,.18);
    }
}

</style>


<style id="australia-regions-v75-performance">

/* ============================================================
   AUSTRALIA REGIONS V7.5 SEPARATE PERFORMANCE CARDS
   ============================================================ */


/* Hide previous V7.2 / V7.3 / V7.4 dashboards */

.rp-v72d-meter,
.rp-v72-meter,
.rp-live-meter-wrap,
.rp-v73-chart-shell,
.rp-v74-performance{
    display:none !important;
}


/* Hide the old simulator metric grid but keep it in DOM
   because it supplies the live values to V7.5 */

.rp-simulator > .rp-simulator-grid{
    display:none !important;
}


.rp-simulator > .rp-section-heading{
    display:none !important;
}


.rp-simulator{
    padding:
        16px 18px 20px !important;

    border-top:
        1px solid
        rgba(255,255,255,.08);
}


/* ============================================================
   PERFORMANCE SECTION
   ============================================================ */

.rp-v75-section{
    width:100%;
}


.rp-v75-section-header{
    display:flex;

    align-items:center;

    justify-content:space-between;

    margin-bottom:12px;
}


.rp-v75-section-title{
    display:flex;

    align-items:center;

    gap:9px;

    color:#d8e8f2;

    font-size:11px;

    font-weight:850;

    letter-spacing:.07em;
}


.rp-v75-bars{
    display:flex;

    align-items:flex-end;

    gap:2px;

    width:17px;

    height:16px;
}


.rp-v75-bars i{
    display:block;

    width:3px;

    border-radius:1px;

    background:#24cfff;

    box-shadow:
        0 0 6px
        rgba(36,207,255,.48);
}


.rp-v75-bars i:nth-child(1){
    height:6px;
}

.rp-v75-bars i:nth-child(2){
    height:10px;
}

.rp-v75-bars i:nth-child(3){
    height:16px;
}

.rp-v75-bars i:nth-child(4){
    height:8px;
}


.rp-v75-refresh{
    color:#627b8a;

    font-size:8px;

    font-weight:750;

    letter-spacing:.05em;
}


/* ============================================================
   CARD GRID
   ============================================================ */

.rp-v75-grid{
    display:grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0,260px)
        );

    gap:14px;

    align-items:start;
}


/* ============================================================
   INDIVIDUAL CARD
   ============================================================ */

.rp-v75-card{
    position:relative;

    width:100%;

    min-width:0;

    height:190px;

    overflow:hidden;

    border-radius:8px;

    border:
        1px solid
        rgba(29,125,179,.42);

    background:
        linear-gradient(
            180deg,
            #081829 0%,
            #04101c 100%
        );

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.035),
        0 8px 20px
        rgba(0,0,0,.28);
}


.rp-v75-card::before{
    content:"";

    position:absolute;

    left:0;
    right:0;
    top:0;

    height:2px;

    background:
        linear-gradient(
            90deg,
            transparent,
            #20cfff,
            transparent
        );

    opacity:.75;
}


.rp-v75-card.physics::before{
    background:
        linear-gradient(
            90deg,
            transparent,
            #42df98,
            transparent
        );
}


.rp-v75-card.scripts::before{
    background:
        linear-gradient(
            90deg,
            transparent,
            #ffc13a,
            transparent
        );
}


/* ============================================================
   CARD HEADER
   ============================================================ */

.rp-v75-card-header{
    height:38px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:
        0 12px;

    border-bottom:
        1px solid
        rgba(71,133,170,.18);

    background:
        linear-gradient(
            180deg,
            rgba(12,41,66,.65),
            rgba(7,24,40,.20)
        );
}


.rp-v75-card-title{
    display:flex;

    align-items:center;

    gap:8px;

    color:#a8c0d0;

    font-size:10px;

    font-weight:800;

    letter-spacing:.04em;
}


.rp-v75-card-icon{
    display:flex;

    align-items:center;

    justify-content:center;

    width:22px;

    height:22px;

    color:#27ceff;

    font-size:14px;

    font-weight:900;

    text-shadow:
        0 0 7px
        rgba(39,206,255,.55);
}


.rp-v75-card.physics .rp-v75-card-icon{
    color:#53e39d;

    text-shadow:
        0 0 7px
        rgba(83,227,157,.48);
}


.rp-v75-card.scripts .rp-v75-card-icon{
    color:#ffc13a;

    text-shadow:
        0 0 7px
        rgba(255,193,58,.48);
}


.rp-v75-live{
    display:flex;

    align-items:center;

    gap:5px;

    color:#65cde8;

    font-size:7px;

    font-weight:800;

    letter-spacing:.06em;
}


.rp-v75-live::before{
    content:"";

    width:5px;

    height:5px;

    border-radius:50%;

    background:#35d8ff;

    box-shadow:
        0 0 7px
        rgba(53,216,255,.85);
}


/* ============================================================
   CARD VALUE
   ============================================================ */

.rp-v75-card-value{
    display:flex;

    align-items:flex-end;

    gap:4px;

    height:42px;

    padding:
        8px 12px 0;
}


.rp-v75-card-value strong{
    color:#f1f8fc;

    font-size:24px;

    line-height:1;

    font-weight:900;

    letter-spacing:-.02em;
}


.rp-v75-card-value small{
    padding-bottom:2px;

    color:#718a9a;

    font-size:8px;

    font-weight:700;
}


/* ============================================================
   GRAPH
   ============================================================ */

.rp-v75-chart-area{
    display:grid;

    grid-template-columns:
        24px minmax(0,1fr);

    gap:5px;

    height:80px;

    padding:
        5px 10px 0 8px;
}


.rp-v75-axis{
    height:70px;

    display:flex;

    flex-direction:column;

    justify-content:space-between;

    align-items:flex-end;

    color:#637e90;

    font-size:7px;

    font-weight:650;

    padding-top:1px;
}


.rp-v75-canvas{
    display:block;

    width:100%;

    height:70px;
}


/* ============================================================
   FOOTER
   ============================================================ */

.rp-v75-card-footer{
    height:29px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:
        0 11px;

    border-top:
        1px solid
        rgba(62,121,156,.15);

    color:#587286;

    font-size:7px;

    font-weight:700;

    letter-spacing:.03em;
}


.rp-v75-minmax{
    color:#69889b;
}


/* ============================================================
   RESPONSIVE
   ============================================================ */

@media(max-width:980px){

    .rp-v75-grid{
        grid-template-columns:
            repeat(
                2,
                minmax(0,260px)
            );
    }
}


@media(max-width:620px){

    .rp-v75-grid{
        grid-template-columns:
            minmax(0,280px);
    }
}

</style>


<!-- AUSTRALIA REGIONS V7.8 FAST LIVE GRAPHS -->
<script>

(function(){

    "use strict";


    const sid =
        <?=json_encode(
            $sid,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        )?>;


    const regionIcon =
        <?=json_encode(
            $regionIcon,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        )?>;


    const grid =
        document.getElementById(
            "rp-grid"
        );


    const search =
        document.getElementById(
            "rp-search"
        );


    const refresh =
        document.getElementById(
            "rp-refresh"
        );


    const message =
        document.getElementById(
            "rp-message"
        );


    let regions =
        [];


    let performanceRows =
        [];


    let telemetryRows =
        [];


    let host =
        {};


    let controlsAllowed =
        false;


    let busy =
        false;

    let saveOarBusy =
        false;



    function esc(value){

        return String(
            value ??
            ""
        )
        .replace(/&/g,"&amp;")
        .replace(/</g,"&lt;")
        .replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;")
        .replace(/'/g,"&#039;");
    }



    function num(value){

        const parsed =
            Number(value);


        return Number.isFinite(parsed)
            ?
            parsed
            :
            0;
    }



    function txt(
        value,
        fallback = "—"
    ){

        const result =
            String(
                value ??
                ""
            ).trim();


        return result !== ""
            ?
            result
            :
            fallback;
    }



    function key(value){

        return String(
            value ??
            ""
        )
        .trim()
        .toLowerCase()
        .replace(
            /[^a-z0-9]+/g,
            ""
        );
    }



    function nameOf(row){

        return txt(
            row.RegionName ??
            row.regionName ??
            row.Name ??
            row.name,
            "Unnamed Region"
        );
    }



    function estateOf(row){

        return txt(
            row.EstateName ??
            row.estateName ??
            row.Estate ??
            row.estate,
            "Estate"
        );
    }



    function ownerOf(row){

        return txt(
            row.EstateOwner ??
            row.estateOwner ??
            row.Owner ??
            row.owner
        );
    }



    function primsOf(row){

        return num(
            row.PrimCount ??
            row.primCount ??
            row.Prims ??
            row.prims
        );
    }



    function avatarsOf(row){

        return num(
            row.AvatarCount ??
            row.avatarCount ??
            row.Avatars ??
            row.avatars
        );
    }



    function cellsX(row){

        const direct =
            num(
                row.CellsX ??
                row.cellsX
            );


        if (direct > 0) {
            return direct;
        }


        const size =
            num(
                row.SizeX ??
                row.sizeX
            );


        return size > 0
            ?
            Math.max(
                1,
                Math.round(
                    size /
                    256
                )
            )
            :
            1;
    }



    function cellsY(row){

        const direct =
            num(
                row.CellsY ??
                row.cellsY
            );


        if (direct > 0) {
            return direct;
        }


        const size =
            num(
                row.SizeY ??
                row.sizeY
            );


        return size > 0
            ?
            Math.max(
                1,
                Math.round(
                    size /
                    256
                )
            )
            :
            1;
    }



    function statusOf(row){

        return txt(
            row.Status ??
            row.status ??
            row.State ??
            row.state,
            "Unknown"
        );
    }



    function isOnline(row){

        const state =
            statusOf(row)
                .toLowerCase();


        return (
            state.includes("booted")
            ||
            state.includes("running")
            ||
            state.includes("online")
            ||
            state.includes("started")
            ||
            state === "up"
        );
    }



    function isOffline(row){

        const state =
            statusOf(row)
                .toLowerCase();


        return (
            state.includes("stopped")
            ||
            state.includes("offline")
            ||
            state.includes("down")
            ||
            state.includes("disabled")
        );
    }



    function stateClass(row){

        if (isOnline(row)) {
            return "online";
        }


        if (isOffline(row)) {
            return "offline";
        }


        return "unknown";
    }



    function stateLabel(row){

        if (isOnline(row)) {
            return "ONLINE";
        }


        if (isOffline(row)) {
            return "OFFLINE";
        }


        return statusOf(row)
            .toUpperCase();
    }



    function performanceFor(row){

        const wanted =
            key(
                nameOf(row)
            );


        /*
         * DreamGrid compatibility:
         *
         * The existing region folder is named:
         *
         *     Offical Region
         *
         * while OpenSim and the website correctly report:
         *
         *     Official Region
         *
         * Treat both spellings as the same region when
         * joining Windows process telemetry.
         */

        const compatibleKeys =
            new Set(
                [
                    wanted
                ]
            );


        if (
            wanted ===
            "officialregion"
        ) {

            compatibleKeys.add(
                "officalregion"
            );
        }


        if (
            wanted ===
            "officalregion"
        ) {

            compatibleKeys.add(
                "officialregion"
            );
        }


        return performanceRows.find(
            function(item){

                const itemKey =
                    key(
                        item.RegionName
                    );


                return compatibleKeys.has(
                    itemKey
                );
            }
        )
        ||
        null;
    }


    function telemetryFor(row){

        const wanted =
            key(
                nameOf(row)
            );


        return telemetryRows.find(
            function(item){

                return (
                    key(
                        item.RegionName
                    )
                    ===
                    wanted
                );
            }
        )
        ||
        null;
    }



    function metricNumber(value){

        const match =
            String(
                value ??
                ""
            ).match(
                /-?\d+(?:\.\d+)?/
            );


        if (!match) {
            return null;
        }


        const result =
            Number(
                match[0]
            );


        return Number.isFinite(result)
            ?
            result
            :
            null;
    }



    function metricValue(
        telemetry,
        property
    ){

        if (
            !telemetry
            ||
            !telemetry.Available
        ) {

            return "—";
        }


        return txt(
            telemetry[property]
        );
    }



    function simulatorHealth(
        telemetry
    ){

        if (
            !telemetry
            ||
            !telemetry.Available
        ) {

            return {
                state:"unknown",
                label:"NO DATA"
            };
        }


        const sim =
            metricNumber(
                telemetry.SimFPS
            );


        const physics =
            metricNumber(
                telemetry.PhysicsFPS
            );


        const dilation =
            metricNumber(
                telemetry.TimeDilation
            );


        if (
            (sim !== null && sim < 20)
            ||
            (physics !== null && physics < 20)
            ||
            (dilation !== null && dilation < 0.80)
        ) {

            return {
                state:"critical",
                label:"CRITICAL"
            };
        }


        if (
            (sim !== null && sim < 40)
            ||
            (physics !== null && physics < 40)
            ||
            (dilation !== null && dilation < 0.95)
        ) {

            return {
                state:"warning",
                label:"WARNING"
            };
        }


        return {
            state:"good",
            label:"GOOD"
        };
    }



    function extractRows(data){

        const candidates = [

            data?.regions,
            data?.myRegions,
            data?.rows,
            data?.data?.regions,
            data?.data?.rows
        ];


        for (const candidate of candidates) {

            if (!Array.isArray(candidate)) {
                continue;
            }


            return candidate.map(
                function(row){

                    if (
                        row
                        &&
                        typeof row === "object"
                        &&
                        row.cell
                        &&
                        typeof row.cell === "object"
                    ) {

                        return {
                            ...row,
                            ...row.cell
                        };
                    }


                    return row;
                }
            );
        }


        return [];
    }



    function extractControls(data){

        const candidates = [

            data?.regionControls,
            data?.controlsAllowed,
            data?.canControl,
            data?.permissions?.regionControls,
            data?.data?.regionControls
        ];


        for (const value of candidates) {

            if (
                value === true
                ||
                value === 1
                ||
                value === "1"
                ||
                String(value).toLowerCase() === "true"
            ) {

                return true;
            }
        }


        return false;
    }



    function updateSummary(){

        document.getElementById(
            "rp-total"
        ).textContent =
            regions.length.toLocaleString();


        document.getElementById(
            "rp-online"
        ).textContent =
            regions
                .filter(
                    function(row){

                        if (isOnline(row)) {
                            return true;
                        }

                        const perf =
                            performanceFor(row);

                        return Boolean(
                            perf?.Running
                        );
                    }
                )
                .length
                .toLocaleString();


        document.getElementById(
            "rp-avatars"
        ).textContent =
            regions
                .reduce(
                    function(total,row){

                        return (
                            total +
                            avatarsOf(row)
                        );
                    },
                    0
                )
                .toLocaleString();


        const physical =
            num(
                host.PhysicalCores
            );


        const logical =
            num(
                host.LogicalThreads
            );


        document.getElementById(
            "rp-host-cpu"
        ).textContent =
            (
                physical > 0
                &&
                logical > 0
            )
                ?
                (
                    physical +
                    "C / " +
                    logical +
                    "T"
                )
                :
                "—";


        document.getElementById(
            "rp-host-cpu-note"
        ).textContent =
            txt(
                host.CpuModel,
                "Physical / logical"
            );


        const memory =
            num(
                host.TotalMemoryGb
            );


        document.getElementById(
            "rp-host-memory"
        ).textContent =
            memory > 0
                ?
                memory.toFixed(0) +
                " GB"
                :
                "—";


        const control =
            document.getElementById(
                "rp-control"
            );


        const controlNote =
            document.getElementById(
                "rp-control-note"
            );


        if (controlsAllowed) {

            control.textContent =
                "AVAILABLE";


            control.className =
                "rp-control-state available";


            controlNote.textContent =
                "Full operations";

        }
        else {

            control.textContent =
                "READ ONLY";


            control.className =
                "rp-control-state readonly";


            controlNote.textContent =
                "Controls unavailable";
        }
    }



    function cpuBar(perf){

        const percent =
            perf
                ?
                Math.max(
                    0,
                    Math.min(
                        100,
                        num(
                            perf.CpuHostPercent
                        )
                    )
                )
                :
                0;


        return `
        <div class="rp-meter">
            <span
                style="width:${percent}%"
            ></span>
        </div>
        `;
    }



    function ramBar(perf){

        const percent =
            perf
                ?
                Math.max(
                    0,
                    Math.min(
                        100,
                        num(
                            perf.MemoryPercent
                        )
                    )
                )
                :
                0;


        return `
        <div class="rp-meter">
            <span
                style="width:${percent}%"
            ></span>
        </div>
        `;
    }



    
    /*
     * ============================================================
     * AUSTRALIA REGIONS V7.2D LIVE METER ENGINE
     * ============================================================
     */

    function rpV72dClamp(value){

        return Math.max(
            0,
            Math.min(
                100,
                value
            )
        );
    }


    function rpV72dProfile(
        label,
        value
    ){

        const name =
            String(
                label || ""
            )
            .trim()
            .toUpperCase();


        let percent = 0;
        let maximum = "";
        let state = "LIVE";
        let tone = "";


        if (
            name === "SIM FPS"
            ||
            name === "PHYSICS FPS"
        ) {

            percent =
                rpV72dClamp(
                    value / 60 * 100
                );

            maximum = "60";


            if (value >= 40) {

                state = "HEALTHY";
                tone = "good";
            }
            else if (value >= 20) {

                state = "LOW";
                tone = "warning";
            }
            else {

                state = "CRITICAL";
                tone = "critical";
            }
        }


        else if (
            name === "TIME DILATION"
        ) {

            percent =
                rpV72dClamp(
                    value * 100
                );

            maximum = "1.00";


            if (value >= .95) {

                state = "HEALTHY";
                tone = "good";
            }
            else if (value >= .80) {

                state = "LOW";
                tone = "warning";
            }
            else {

                state = "CRITICAL";
                tone = "critical";
            }
        }


        else if (
            name === "FRAME TIME"
        ) {

            percent =
                rpV72dClamp(
                    value / 50 * 100
                );

            maximum = "50";


            if (value <= 22) {

                state = "HEALTHY";
                tone = "good";
            }
            else if (value <= 35) {

                state = "BUSY";
                tone = "warning";
            }
            else {

                state = "HIGH";
                tone = "critical";
            }
        }


        else if (
            name === "ACTIVE SCRIPTS"
            ||
            name === "SCRIPT EVENTS/S"
        ) {

            const scale =
                Math.max(
                    1000,
                    Math.ceil(
                        value / 1000
                    ) * 1000
                );


            percent =
                rpV72dClamp(
                    value / scale * 100
                );


            maximum =
                scale.toLocaleString();


            state =
                "LIVE LOAD";
        }


        else if (
            name === "ROOT AGENTS"
            ||
            name === "CHILD AGENTS"
        ) {

            const scale =
                Math.max(
                    10,
                    Math.ceil(
                        value / 5
                    ) * 5
                );


            percent =
                rpV72dClamp(
                    value / scale * 100
                );


            maximum =
                scale.toLocaleString();


            state =
                value > 0
                    ? "ACTIVE"
                    : "IDLE";
        }


        else if (
            name === "NETWORK IN"
            ||
            name === "NETWORK OUT"
        ) {

            const scale =
                Math.max(
                    1000,
                    Math.ceil(
                        value / 1000
                    ) * 1000
                );


            percent =
                rpV72dClamp(
                    value / scale * 100
                );


            maximum =
                scale.toLocaleString();


            state =
                value > 0
                    ? "ACTIVE"
                    : "IDLE";
        }


        else {

            return null;
        }


        return {
            percent:percent,
            maximum:maximum,
            state:state,
            tone:tone
        };
    }


    function decorateLiveMeters(){

        grid
            .querySelectorAll(
                ".rp-sim-item"
            )
            .forEach(
                function(item){

                    if (
                        item.querySelector(
                            ".rp-v72d-meter"
                        )
                    ) {
                        return;
                    }


                    const labelElement =
                        item.querySelector(
                            ":scope > span"
                        );


                    const valueElement =
                        item.querySelector(
                            ":scope > strong"
                        );


                    if (
                        !labelElement
                        ||
                        !valueElement
                    ) {
                        return;
                    }


                    const value =
                        metricNumber(
                            valueElement.textContent
                        );


                    if (value === null) {
                        return;
                    }


                    const profile =
                        rpV72dProfile(
                            labelElement.textContent,
                            value
                        );


                    if (!profile) {
                        return;
                    }


                    const meter =
                        document.createElement(
                            "div"
                        );


                    meter.className =
                        "rp-v72d-meter";


                    meter.innerHTML =
                        '<div class="rp-v72d-track">' +
                            '<span class="rp-v72d-fill ' +
                            profile.tone +
                            '"></span>' +
                        '</div>' +

                        '<div class="rp-v72d-scale">' +
                            '<span>0</span>' +

                            '<span class="rp-v72d-state">' +
                                profile.state +
                            '</span>' +

                            '<span>' +
                                profile.maximum +
                            '</span>' +
                        '</div>';


                    item.appendChild(
                        meter
                    );


                    const fill =
                        meter.querySelector(
                            ".rp-v72d-fill"
                        );


                    if (fill) {

                        requestAnimationFrame(
                            function(){

                                fill.style.width =
                                    profile.percent +
                                    "%";
                            }
                        );
                    }
                }
            );
    }


    /*
     * ============================================================
     * AUSTRALIA REGIONS V7.3 LIVE GRAPH ENGINE
     *
     * 60 live samples at 10 second refresh ~= 10 minute history.
     * ============================================================
     */

    const rpV73History =
        Object.create(null);


    function rpV73Number(value){

        const match =
            String(
                value ?? ""
            ).match(
                /-?\d+(?:\.\d+)?/
            );


        if (!match) {
            return null;
        }


        const number =
            Number(
                match[0]
            );


        return Number.isFinite(number)
            ?
            number
            :
            null;
    }


    function rpV73Key(
        region,
        metric
    ){

        return (
            String(region) +
            "::" +
            String(metric)
        );
    }


    function rpV73AddSample(
        region,
        metric,
        value
    ){

        if (
            value === null
            ||
            !Number.isFinite(value)
        ) {
            return [];
        }


        const historyKey =
            rpV73Key(
                region,
                metric
            );


        if (!rpV73History[historyKey]) {

            rpV73History[historyKey] =
                {
                    last:0,
                    values:[]
                };
        }


        const bucket =
            rpV73History[historyKey];


        const now =
            Date.now();


        /*
         * render() may run for search/filter events as well.
         * Do not duplicate samples faster than every 7 seconds.
         */

        if (
            now -
            bucket.last
            >=
            7000
        ) {

            bucket.values.push(
                value
            );


            bucket.last =
                now;


            while (
                bucket.values.length >
                60
            ) {

                bucket.values.shift();
            }
        }


        return bucket.values;
    }


    function rpV73ChartScale(
        metric,
        values
    ){

        const upper =
            String(metric)
            .toUpperCase();


        if (
            upper === "SIM FPS"
            ||
            upper === "PHYSICS FPS"
        ) {

            return {
                min:0,
                max:60
            };
        }


        if (
            upper === "TIME DILATION"
        ) {

            return {
                min:0,
                max:1
            };
        }


        if (
            upper === "FRAME TIME"
        ) {

            return {
                min:0,
                max:50
            };
        }


        let maxValue =
            Math.max(
                1,
                ...values
            );


        maxValue =
            maxValue * 1.18;


        return {
            min:0,
            max:maxValue
        };
    }


    function rpV73Colours(metric){

        const upper =
            String(metric)
            .toUpperCase();


        if (
            upper === "PHYSICS FPS"
        ) {

            return {
                line:"#53e39d",
                fill:"rgba(53,190,125,.15)"
            };
        }


        if (
            upper === "SCRIPT EVENTS/S"
        ) {

            return {
                line:"#ffc13a",
                fill:"rgba(255,193,58,.13)"
            };
        }


        return {
            line:"#49d9ff",
            fill:"rgba(40,176,225,.14)"
        };
    }


    function rpV73DrawGraph(
        canvas,
        values,
        metric
    ){

        if (!canvas) {
            return;
        }


        const rect =
            canvas.getBoundingClientRect();


        const width =
            Math.max(
                240,
                Math.floor(
                    rect.width
                )
            );


        const height =
            118;


        const dpr =
            Math.max(
                1,
                Math.min(
                    2,
                    window.devicePixelRatio ||
                    1
                )
            );


        canvas.width =
            Math.floor(
                width *
                dpr
            );


        canvas.height =
            Math.floor(
                height *
                dpr
            );


        const ctx =
            canvas.getContext(
                "2d"
            );


        if (!ctx) {
            return;
        }


        ctx.setTransform(
            dpr,
            0,
            0,
            dpr,
            0,
            0
        );


        ctx.clearRect(
            0,
            0,
            width,
            height
        );


        const left =
            1;

        const right =
            width - 1;

        const top =
            5;

        const bottom =
            height - 5;


        /*
         * Dashboard grid.
         */

        ctx.lineWidth =
            1;


        ctx.strokeStyle =
            "rgba(96,140,160,.14)";


        for (
            let row = 0;
            row <= 4;
            row++
        ) {

            const y =
                top +
                (
                    bottom -
                    top
                ) *
                row /
                4;


            ctx.beginPath();

            ctx.moveTo(
                left,
                y
            );

            ctx.lineTo(
                right,
                y
            );

            ctx.stroke();
        }


        for (
            let column = 0;
            column <= 6;
            column++
        ) {

            const x =
                left +
                (
                    right -
                    left
                ) *
                column /
                6;


            ctx.beginPath();

            ctx.moveTo(
                x,
                top
            );

            ctx.lineTo(
                x,
                bottom
            );

            ctx.stroke();
        }


        if (
            !Array.isArray(values)
            ||
            values.length === 0
        ) {

            ctx.fillStyle =
                "rgba(145,165,175,.55)";


            ctx.font =
                "10px sans-serif";


            ctx.fillText(
                "WAITING FOR LIVE DATA",
                10,
                height / 2
            );


            return;
        }


        const scale =
            rpV73ChartScale(
                metric,
                values
            );


        const colours =
            rpV73Colours(
                metric
            );


        const count =
            values.length;


        const points =
            [];


        for (
            let index = 0;
            index < count;
            index++
        ) {

            const x =
                count <= 1
                    ?
                    right
                    :
                    left +
                    (
                        right -
                        left
                    ) *
                    index /
                    (
                        count -
                        1
                    );


            const ratio =
                Math.max(
                    0,
                    Math.min(
                        1,
                        (
                            values[index] -
                            scale.min
                        )
                        /
                        Math.max(
                            .000001,
                            scale.max -
                            scale.min
                        )
                    )
                );


            const y =
                bottom -
                ratio *
                (
                    bottom -
                    top
                );


            points.push(
                {
                    x:x,
                    y:y
                }
            );
        }


        /*
         * Filled area.
         */

        if (points.length > 1) {

            ctx.beginPath();

            ctx.moveTo(
                points[0].x,
                bottom
            );


            for (
                const point
                of
                points
            ) {

                ctx.lineTo(
                    point.x,
                    point.y
                );
            }


            ctx.lineTo(
                points[
                    points.length - 1
                ].x,
                bottom
            );


            ctx.closePath();


            ctx.fillStyle =
                colours.fill;


            ctx.fill();
        }


        /*
         * Live line.
         */

        ctx.beginPath();


        for (
            let index = 0;
            index < points.length;
            index++
        ) {

            const point =
                points[index];


            if (index === 0) {

                ctx.moveTo(
                    point.x,
                    point.y
                );
            }
            else {

                ctx.lineTo(
                    point.x,
                    point.y
                );
            }
        }


        ctx.lineWidth =
            2;


        ctx.strokeStyle =
            colours.line;


        ctx.shadowColor =
            colours.line;


        ctx.shadowBlur =
            7;


        ctx.stroke();


        ctx.shadowBlur =
            0;


        /*
         * Current-value point.
         */

        const last =
            points[
                points.length - 1
            ];


        ctx.beginPath();

        ctx.arc(
            last.x,
            last.y,
            3,
            0,
            Math.PI * 2
        );


        ctx.fillStyle =
            colours.line;


        ctx.fill();
    }


    function rpV73FormatNumber(
        value
    ){

        if (
            value === null
            ||
            !Number.isFinite(value)
        ) {

            return "—";
        }


        if (
            Math.abs(value) >=
            1000
        ) {

            return value.toLocaleString(
                undefined,
                {
                    maximumFractionDigits:0
                }
            );
        }


        return value.toLocaleString(
            undefined,
            {
                maximumFractionDigits:2
            }
        );
    }


    function rpV73BuildDashboard(){

        /*
         * Kill any old V7.2 bar meters if they exist.
         */

        grid
            .querySelectorAll(
                ".rp-v72d-meter, .rp-v72-meter, .rp-live-meter-wrap"
            )
            .forEach(
                function(oldMeter){

                    oldMeter.remove();
                }
            );


        const primaryMetrics =
            [
                "SIM FPS",
                "PHYSICS FPS",
                "SCRIPT EVENTS/S"
            ];


        grid
            .querySelectorAll(
                "article"
            )
            .forEach(
                function(card){

                    const heading =
                        card.querySelector(
                            "h3"
                        );


                    const regionName =
                        heading
                            ?
                            heading.textContent.trim()
                            :
                            "Region";


                    const items =
                        card.querySelectorAll(
                            ".rp-sim-item"
                        );


                    items.forEach(
                        function(item){

                            const labelElement =
                                item.querySelector(
                                    ":scope > span"
                                );


                            const valueElement =
                                item.querySelector(
                                    ":scope > strong"
                                );


                            if (
                                !labelElement
                                ||
                                !valueElement
                            ) {
                                return;
                            }


                            const metric =
                                labelElement
                                    .textContent
                                    .trim()
                                    .toUpperCase();


                            item.setAttribute(
                                "data-v73-metric",
                                metric
                            );


                            if (
                                primaryMetrics.includes(
                                    metric
                                )
                            ) {

                                item.classList.add(
                                    "rp-v73-primary"
                                );


                                item.classList.remove(
                                    "rp-v73-secondary"
                                );


                                const value =
                                    rpV73Number(
                                        valueElement.textContent
                                    );


                                const values =
                                    rpV73AddSample(
                                        regionName,
                                        metric,
                                        value
                                    );


                                let chartShell =
                                    item.querySelector(
                                        ".rp-v73-chart-shell"
                                    );


                                if (!chartShell) {

                                    chartShell =
                                        document.createElement(
                                            "div"
                                        );


                                    chartShell.className =
                                        "rp-v73-chart-shell";


                                    chartShell.innerHTML =
                                        '<canvas class="rp-v73-canvas"></canvas>' +
                                        '<div class="rp-v73-chart-meta">' +
                                            '<span class="rp-v73-live-dot">LIVE • 10 SECOND REFRESH</span>' +
                                            '<span class="rp-v73-range"></span>' +
                                        '</div>';


                                    item.appendChild(
                                        chartShell
                                    );
                                }


                                const canvas =
                                    chartShell.querySelector(
                                        ".rp-v73-canvas"
                                    );


                                const range =
                                    chartShell.querySelector(
                                        ".rp-v73-range"
                                    );


                                if (
                                    range
                                    &&
                                    values.length > 0
                                ) {

                                    const minimum =
                                        Math.min(
                                            ...values
                                        );


                                    const maximum =
                                        Math.max(
                                            ...values
                                        );


                                    range.textContent =
                                        "MIN " +
                                        rpV73FormatNumber(
                                            minimum
                                        ) +
                                        "  •  MAX " +
                                        rpV73FormatNumber(
                                            maximum
                                        );
                                }


                                rpV73DrawGraph(
                                    canvas,
                                    values,
                                    metric
                                );
                            }
                            else {

                                item.classList.add(
                                    "rp-v73-secondary"
                                );


                                item.classList.remove(
                                    "rp-v73-primary"
                                );


                                const chart =
                                    item.querySelector(
                                        ".rp-v73-chart-shell"
                                    );


                                if (chart) {
                                    chart.remove();
                                }
                            }
                        }
                    );
                }
            );
    }


    /*
     * ============================================================
     * AUSTRALIA REGIONS V7.4 COMPACT GRAPH ENGINE
     * ============================================================
     */


    window.rpV74History =
        window.rpV74History ||
        Object.create(null);


    window.rpV74Range =
        window.rpV74Range ||
        60;


    function rpV74Number(value){

        const match =
            String(
                value ?? ""
            ).match(
                /-?\d+(?:\.\d+)?/
            );


        if (!match) {
            return null;
        }


        const number =
            Number(
                match[0]
            );


        return Number.isFinite(number)
            ? number
            : null;
    }


    function rpV74FindMetric(
        card,
        wanted
    ){

        const items =
            card.querySelectorAll(
                ".rp-sim-item"
            );


        for (
            const item
            of
            items
        ) {

            const label =
                item.querySelector(
                    ":scope > span"
                );


            const value =
                item.querySelector(
                    ":scope > strong"
                );


            if (
                !label
                ||
                !value
            ) {
                continue;
            }


            if (
                label
                    .textContent
                    .trim()
                    .toUpperCase()
                ===
                wanted
            ) {

                return rpV74Number(
                    value.textContent
                );
            }
        }


        return null;
    }


    function rpV74Push(
        region,
        metric,
        value
    ){

        const key =
            region +
            "::" +
            metric;


        if (!window.rpV74History[key]) {

            window.rpV74History[key] =
                {
                    time:0,
                    values:[]
                };
        }


        const bucket =
            window.rpV74History[key];


        if (
            value !== null
            &&
            Number.isFinite(value)
        ) {

            const now =
                Date.now();


            if (
                now -
                bucket.time
                >=
                7000
            ) {

                bucket.values.push(
                    value
                );


                bucket.time =
                    now;


                while (
                    bucket.values.length >
                    60
                ) {

                    bucket.values.shift();
                }
            }
        }


        return bucket.values;
    }


    function rpV74MetricConfig(metric){

        if (metric === "SIM FPS") {

            return {
                maximum:60,
                middle:30,
                minimum:0,
                line:"#20cfff",
                fill:"rgba(20,190,235,.18)"
            };
        }


        if (metric === "PHYSICS FPS") {

            return {
                maximum:60,
                middle:30,
                minimum:0,
                line:"#20cfff",
                fill:"rgba(20,190,235,.18)"
            };
        }


        return {
            maximum:null,
            middle:null,
            minimum:0,
            line:"#58df78",
            fill:"rgba(59,207,104,.14)"
        };
    }


    function rpV74VisibleValues(
        values
    ){

        const count =
            window.rpV74Range;


        if (
            count >=
            values.length
        ) {

            return values;
        }


        return values.slice(
            -count
        );
    }


    function rpV74Draw(
        canvas,
        values,
        metric
    ){

        if (!canvas) {
            return;
        }


        const rect =
            canvas.getBoundingClientRect();


        const width =
            Math.max(
                120,
                Math.floor(
                    rect.width
                )
            );


        const height =
            76;


        const dpr =
            Math.max(
                1,
                Math.min(
                    2,
                    window.devicePixelRatio ||
                    1
                )
            );


        canvas.width =
            width *
            dpr;


        canvas.height =
            height *
            dpr;


        const ctx =
            canvas.getContext(
                "2d"
            );


        if (!ctx) {
            return;
        }


        ctx.setTransform(
            dpr,
            0,
            0,
            dpr,
            0,
            0
        );


        ctx.clearRect(
            0,
            0,
            width,
            height
        );


        /*
         * Grid matching the reference image.
         */

        ctx.strokeStyle =
            "rgba(66,117,150,.22)";


        ctx.lineWidth =
            1;


        for (
            let row = 0;
            row <= 2;
            row++
        ) {

            const y =
                row *
                height /
                2;


            ctx.beginPath();

            ctx.moveTo(
                0,
                y
            );

            ctx.lineTo(
                width,
                y
            );

            ctx.stroke();
        }


        for (
            let column = 0;
            column <= 4;
            column++
        ) {

            const x =
                column *
                width /
                4;


            ctx.beginPath();

            ctx.moveTo(
                x,
                0
            );

            ctx.lineTo(
                x,
                height
            );

            ctx.stroke();
        }


        const visible =
            rpV74VisibleValues(
                values
            );


        if (
            !visible ||
            visible.length === 0
        ) {
            return;
        }


        const config =
            rpV74MetricConfig(
                metric
            );


        let top =
            config.maximum;


        if (top === null) {

            top =
                Math.max(
                    10,
                    ...visible
                );


            top =
                Math.ceil(
                    top *
                    1.15
                );
        }


        const bottom =
            0;


        const points =
            [];


        const spacingCount =
            Math.max(
                window.rpV74Range - 1,
                1
            );


        const offset =
            window.rpV74Range -
            visible.length;


        for (
            let index = 0;
            index < visible.length;
            index++
        ) {

            const x =
                (
                    offset +
                    index
                )
                /
                spacingCount
                *
                width;


            const ratio =
                Math.max(
                    0,
                    Math.min(
                        1,
                        (
                            visible[index] -
                            bottom
                        )
                        /
                        Math.max(
                            .00001,
                            top -
                            bottom
                        )
                    )
                );


            const y =
                height -
                ratio *
                height;


            points.push(
                {
                    x:x,
                    y:y
                }
            );
        }


        if (points.length > 1) {

            ctx.beginPath();

            ctx.moveTo(
                points[0].x,
                height
            );


            for (
                const point
                of
                points
            ) {

                ctx.lineTo(
                    point.x,
                    point.y
                );
            }


            ctx.lineTo(
                points[
                    points.length - 1
                ].x,
                height
            );


            ctx.closePath();


            ctx.fillStyle =
                config.fill;


            ctx.fill();
        }


        ctx.beginPath();


        for (
            let index = 0;
            index < points.length;
            index++
        ) {

            const point =
                points[index];


            if (index === 0) {

                ctx.moveTo(
                    point.x,
                    point.y
                );
            }
            else {

                ctx.lineTo(
                    point.x,
                    point.y
                );
            }
        }


        ctx.strokeStyle =
            config.line;


        ctx.lineWidth =
            2;


        ctx.shadowColor =
            config.line;


        ctx.shadowBlur =
            5;


        ctx.stroke();


        ctx.shadowBlur =
            0;
    }


    function rpV74Format(value){

        if (
            value === null
            ||
            !Number.isFinite(value)
        ) {
            return "—";
        }


        if (value >= 1000) {

            return value.toLocaleString(
                undefined,
                {
                    maximumFractionDigits:0
                }
            );
        }


        return value.toLocaleString(
            undefined,
            {
                maximumFractionDigits:1
            }
        );
    }


    function rpV74CreateCell(
        region,
        metric,
        label,
        unit,
        icon,
        iconClass,
        value
    ){

        const values =
            rpV74Push(
                region,
                metric,
                value
            );


        let maximum =
            60;


        let middle =
            30;


        if (
            metric ===
            "SCRIPT EVENTS/S"
        ) {

            maximum =
                Math.max(
                    10,
                    ...values
                );


            maximum =
                Math.ceil(
                    maximum *
                    1.15
                );


            middle =
                Math.round(
                    maximum /
                    2
                );
        }


        const cell =
            document.createElement(
                "div"
            );


        cell.className =
            "rp-v74-cell";


        cell.innerHTML =
            '<div class="rp-v74-metric-head">' +

                '<div class="rp-v74-icon ' +
                    iconClass +
                '">' +
                    icon +
                '</div>' +

                '<div class="rp-v74-label">' +
                    label +
                '</div>' +

            '</div>' +

            '<div class="rp-v74-value">' +
                rpV74Format(
                    value
                ) +
                '<small>' +
                    unit +
                '</small>' +
            '</div>' +

            '<div class="rp-v74-chart-row">' +

                '<div class="rp-v74-yaxis">' +
                    '<span>' +
                        maximum +
                    '</span>' +

                    '<span>' +
                        middle +
                    '</span>' +

                    '<span>0</span>' +
                '</div>' +

                '<canvas class="rp-v74-canvas"></canvas>' +

            '</div>';


        const canvas =
            cell.querySelector(
                ".rp-v74-canvas"
            );


        requestAnimationFrame(
            function(){

                rpV74Draw(
                    canvas,
                    values,
                    metric
                );
            }
        );


        return cell;
    }


    function rpV74BuildDashboard(){

        grid
            .querySelectorAll(
                ".rp-v74-performance"
            )
            .forEach(
                function(existing){

                    existing.remove();
                }
            );


        grid
            .querySelectorAll(
                "article"
            )
            .forEach(
                function(card){

                    const simulator =
                        card.querySelector(
                            ".rp-simulator"
                        );


                    if (!simulator) {
                        return;
                    }


                    const heading =
                        card.querySelector(
                            "h3"
                        );


                    const region =
                        heading
                            ?
                            heading.textContent.trim()
                            :
                            "Region";


                    const sim =
                        rpV74FindMetric(
                            card,
                            "SIM FPS"
                        );


                    const physics =
                        rpV74FindMetric(
                            card,
                            "PHYSICS FPS"
                        );


                    const scripts =
                        rpV74FindMetric(
                            card,
                            "SCRIPT EVENTS/S"
                        );


                    const panel =
                        document.createElement(
                            "div"
                        );


                    panel.className =
                        "rp-v74-performance";


                    panel.innerHTML =
                        '<div class="rp-v74-header">' +

                            '<div class="rp-v74-title">' +

                                '<span class="rp-v74-bars">' +
                                    '<i></i>' +
                                    '<i></i>' +
                                    '<i></i>' +
                                    '<i></i>' +
                                '</span>' +

                                '<span>PERFORMANCE</span>' +

                            '</div>' +

                            '<div class="rp-v74-ranges">' +

                                '<button class="rp-v74-range" data-count="6">1m</button>' +

                                '<button class="rp-v74-range" data-count="30">5m</button>' +

                                '<button class="rp-v74-range active" data-count="60">10m</button>' +

                                '<button class="rp-v74-range" data-count="999">ALL</button>' +

                            '</div>' +

                        '</div>' +

                        '<div class="rp-v74-body"></div>' +

                        '<div class="rp-v74-footer">' +

                            '<span class="rp-v74-live">LIVE TELEMETRY</span>' +

                            '<span>10 SECOND REFRESH</span>' +

                        '</div>';


                    const body =
                        panel.querySelector(
                            ".rp-v74-body"
                        );


                    body.appendChild(
                        rpV74CreateCell(
                            region,
                            "SIM FPS",
                            "Sim FPS",
                            "",
                            "▥",
                            "",
                            sim
                        )
                    );


                    body.appendChild(
                        rpV74CreateCell(
                            region,
                            "PHYSICS FPS",
                            "Physics FPS",
                            "",
                            "▦",
                            "",
                            physics
                        )
                    );


                    body.appendChild(
                        rpV74CreateCell(
                            region,
                            "SCRIPT EVENTS/S",
                            "Script Activity",
                            "/s",
                            "↕",
                            "green",
                            scripts
                        )
                    );


                    panel
                        .querySelectorAll(
                            ".rp-v74-range"
                        )
                        .forEach(
                            function(button){

                                button.addEventListener(
                                    "click",
                                    function(){

                                        const count =
                                            Number(
                                                button.dataset.count
                                            );


                                        window.rpV74Range =
                                            count;


                                        panel
                                            .querySelectorAll(
                                                ".rp-v74-range"
                                            )
                                            .forEach(
                                                function(other){

                                                    other.classList.remove(
                                                        "active"
                                                    );
                                                }
                                            );


                                        button.classList.add(
                                            "active"
                                        );


                                    }
                                );
                            }
                        );


                    simulator.prepend(
                        panel
                    );
                }
            );
    }


    /*
     * ============================================================
     * AUSTRALIA REGIONS V7.5 SEPARATE GRAPH ENGINE
     * ============================================================
     */


    window.rpV75History =
        window.rpV75History ||
        Object.create(null);


    function rpV75Number(value){

        const match =
            String(
                value ?? ""
            ).match(
                /-?\d+(?:\.\d+)?/
            );


        if (!match) {
            return null;
        }


        const number =
            Number(
                match[0]
            );


        return Number.isFinite(number)
            ?
            number
            :
            null;
    }


    function rpV75Metric(
        card,
        name
    ){

        const items =
            card.querySelectorAll(
                ".rp-sim-item"
            );


        for (
            const item
            of
            items
        ) {

            const label =
                item.querySelector(
                    ":scope > span"
                );


            const value =
                item.querySelector(
                    ":scope > strong"
                );


            if (
                !label
                ||
                !value
            ) {
                continue;
            }


            if (
                label
                    .textContent
                    .trim()
                    .toUpperCase()
                ===
                name
            ) {

                return rpV75Number(
                    value.textContent
                );
            }
        }


        return null;
    }


    function rpV75Sample(
        region,
        metric,
        value
    ){

        const key =
            region +
            "::" +
            metric;


        if (!window.rpV75History[key]) {

            window.rpV75History[key] =
                {
                    last:0,
                    values:[]
                };
        }


        const bucket =
            window.rpV75History[key];


        if (
            value !== null
            &&
            Number.isFinite(value)
        ) {

            const now =
                Date.now();


            if (
                now -
                bucket.last
                >=
                1800
            ) {

                bucket.values.push(
                    value
                );


                bucket.last =
                    now;


                while (
                    bucket.values.length >
                    60
                ) {

                    bucket.values.shift();
                }
            }
        }


        return bucket.values;
    }


    function rpV75Config(metric){

        if (metric === "SIM FPS") {

            return {
                top:60,
                mid:30,
                line:"#20cfff",
                fill:"rgba(30,191,233,.18)"
            };
        }


        if (metric === "PHYSICS FPS") {

            return {
                top:60,
                mid:30,
                line:"#53e39d",
                fill:"rgba(56,199,132,.16)"
            };
        }


        return {
            top:null,
            mid:null,
            line:"#ffc13a",
            fill:"rgba(255,193,58,.14)"
        };
    }


    function rpV75Draw(
        canvas,
        values,
        metric
    ){

        if (!canvas) {
            return;
        }


        const rect =
            canvas.getBoundingClientRect();


        const width =
            Math.max(
                120,
                Math.floor(
                    rect.width
                )
            );


        const height =
            70;


        const dpr =
            Math.max(
                1,
                Math.min(
                    2,
                    window.devicePixelRatio ||
                    1
                )
            );


        canvas.width =
            width *
            dpr;


        canvas.height =
            height *
            dpr;


        const ctx =
            canvas.getContext(
                "2d"
            );


        if (!ctx) {
            return;
        }


        ctx.setTransform(
            dpr,
            0,
            0,
            dpr,
            0,
            0
        );


        ctx.clearRect(
            0,
            0,
            width,
            height
        );


        /*
         * Grid
         */

        ctx.strokeStyle =
            "rgba(65,120,153,.20)";


        ctx.lineWidth =
            1;


        for (
            let row = 0;
            row <= 2;
            row++
        ) {

            const y =
                row *
                height /
                2;


            ctx.beginPath();

            ctx.moveTo(
                0,
                y
            );

            ctx.lineTo(
                width,
                y
            );

            ctx.stroke();
        }


        for (
            let column = 0;
            column <= 4;
            column++
        ) {

            const x =
                column *
                width /
                4;


            ctx.beginPath();

            ctx.moveTo(
                x,
                0
            );

            ctx.lineTo(
                x,
                height
            );

            ctx.stroke();
        }


        if (
            !values
            ||
            values.length === 0
        ) {
            return;
        }


        const config =
            rpV75Config(
                metric
            );


        let top =
            config.top;


        if (top === null) {

            top =
                Math.max(
                    10,
                    ...values
                );


            top =
                Math.ceil(
                    top *
                    1.15
                );
        }


        const points =
            [];


        const count =
            Math.max(
                59,
                values.length - 1
            );


        const startOffset =
            60 -
            values.length;


        for (
            let index = 0;
            index < values.length;
            index++
        ) {

            const x =
                (
                    startOffset +
                    index
                )
                /
                59
                *
                width;


            const ratio =
                Math.max(
                    0,
                    Math.min(
                        1,
                        values[index] /
                        Math.max(
                            .0001,
                            top
                        )
                    )
                );


            const y =
                height -
                ratio *
                height;


            points.push(
                {
                    x:x,
                    y:y
                }
            );
        }


        /*
         * Filled area
         */

        if (points.length > 1) {

            ctx.beginPath();


            ctx.moveTo(
                points[0].x,
                height
            );


            for (
                const point
                of
                points
            ) {

                ctx.lineTo(
                    point.x,
                    point.y
                );
            }


            ctx.lineTo(
                points[
                    points.length - 1
                ].x,
                height
            );


            ctx.closePath();


            ctx.fillStyle =
                config.fill;


            ctx.fill();
        }


        /*
         * Live trace
         */

        ctx.beginPath();


        points.forEach(
            function(point,index){

                if (index === 0) {

                    ctx.moveTo(
                        point.x,
                        point.y
                    );
                }
                else {

                    ctx.lineTo(
                        point.x,
                        point.y
                    );
                }
            }
        );


        ctx.lineWidth =
            2;


        ctx.strokeStyle =
            config.line;


        ctx.shadowColor =
            config.line;


        ctx.shadowBlur =
            5;


        ctx.stroke();


        ctx.shadowBlur =
            0;
    }


    function rpV75Format(value){

        if (
            value === null
            ||
            !Number.isFinite(value)
        ) {

            return "—";
        }


        return value.toLocaleString(
            undefined,
            {
                maximumFractionDigits:1
            }
        );
    }


    function rpV75CreateCard(
        region,
        metric,
        title,
        unit,
        type,
        icon,
        value
    ){

        const values =
            rpV75Sample(
                region,
                metric,
                value
            );


        const config =
            rpV75Config(
                metric
            );


        let top =
            config.top;


        if (top === null) {

            top =
                Math.max(
                    10,
                    ...values
                );


            top =
                Math.ceil(
                    top *
                    1.15
                );
        }


        const mid =
            config.mid !== null
                ?
                config.mid
                :
                Math.round(
                    top /
                    2
                );


        const minimum =
            values.length > 0
                ?
                Math.min(
                    ...values
                )
                :
                null;


        const maximum =
            values.length > 0
                ?
                Math.max(
                    ...values
                )
                :
                null;


        const card =
            document.createElement(
                "div"
            );


        card.className =
            "rp-v75-card " +
            type;


        card.innerHTML =
            '<div class="rp-v75-card-header">' +

                '<div class="rp-v75-card-title">' +

                    '<span class="rp-v75-card-icon">' +
                        icon +
                    '</span>' +

                    '<span>' +
                        title +
                    '</span>' +

                '</div>' +

                '<span class="rp-v75-live">LIVE</span>' +

            '</div>' +


            '<div class="rp-v75-card-value">' +

                '<strong>' +
                    rpV75Format(
                        value
                    ) +
                '</strong>' +

                '<small>' +
                    unit +
                '</small>' +

            '</div>' +


            '<div class="rp-v75-chart-area">' +

                '<div class="rp-v75-axis">' +

                    '<span>' +
                        top +
                    '</span>' +

                    '<span>' +
                        mid +
                    '</span>' +

                    '<span>0</span>' +

                '</div>' +

                '<canvas class="rp-v75-canvas"></canvas>' +

            '</div>' +


            '<div class="rp-v75-card-footer">' +

                '<span>2 SEC REFRESH</span>' +

                '<span class="rp-v75-minmax">' +

                    'MIN ' +
                    rpV75Format(
                        minimum
                    ) +

                    '  /  MAX ' +
                    rpV75Format(
                        maximum
                    ) +

                '</span>' +

            '</div>';


        const canvas =
            card.querySelector(
                ".rp-v75-canvas"
            );


        requestAnimationFrame(
            function(){

                rpV75Draw(
                    canvas,
                    values,
                    metric
                );
            }
        );


        return card;
    }


    function rpV75BuildDashboard(){

        /*
         * Remove earlier generated dashboards.
         */

        grid
            .querySelectorAll(
                ".rp-v75-section, .rp-v74-performance"
            )
            .forEach(
                function(oldPanel){

                    oldPanel.remove();
                }
            );


        grid
            .querySelectorAll(
                "article"
            )
            .forEach(
                function(regionCard){

                    const simulator =
                        regionCard.querySelector(
                            ".rp-simulator"
                        );


                    if (!simulator) {
                        return;
                    }


                    const title =
                        regionCard.querySelector(
                            "h3"
                        );


                    const region =
                        title
                            ?
                            title.textContent.trim()
                            :
                            "Region";


                    const sim =
                        rpV75Metric(
                            regionCard,
                            "SIM FPS"
                        );


                    const physics =
                        rpV75Metric(
                            regionCard,
                            "PHYSICS FPS"
                        );


                    const scripts =
                        rpV75Metric(
                            regionCard,
                            "SCRIPT EVENTS/S"
                        );


                    const section =
                        document.createElement(
                            "div"
                        );


                    section.className =
                        "rp-v75-section";


                    section.innerHTML =
                        '<div class="rp-v75-section-header">' +

                            '<div class="rp-v75-section-title">' +

                                '<span class="rp-v75-bars">' +
                                    '<i></i>' +
                                    '<i></i>' +
                                    '<i></i>' +
                                    '<i></i>' +
                                '</span>' +

                                '<span>PERFORMANCE</span>' +

                            '</div>' +

                            '<span class="rp-v75-refresh">' +
                                'LIVE OPENSIM TELEMETRY' +
                            '</span>' +

                        '</div>' +

                        '<div class="rp-v75-grid"></div>';


                    const dashboard =
                        section.querySelector(
                            ".rp-v75-grid"
                        );


                    dashboard.appendChild(
                        rpV75CreateCard(
                            region,
                            "SIM FPS",
                            "SIM FPS",
                            "FPS",
                            "sim",
                            "▥",
                            sim
                        )
                    );


                    dashboard.appendChild(
                        rpV75CreateCard(
                            region,
                            "PHYSICS FPS",
                            "PHYSICS FPS",
                            "FPS",
                            "physics",
                            "▦",
                            physics
                        )
                    );


                    dashboard.appendChild(
                        rpV75CreateCard(
                            region,
                            "SCRIPT EVENTS/S",
                            "SCRIPT ACTIVITY",
                            "/s",
                            "scripts",
                            "↕",
                            scripts
                        )
                    );


                    simulator.prepend(
                        section
                    );
                }
            );
    }


    /*
     * ============================================================
     * AUSTRALIA REGIONS V7.6 SCRIPT CARD ENHANCER
     *
     * Large value = number of active scripts.
     * Graph        = script events per second.
     * ============================================================
     */

    function rpV76EnhanceScriptCards(){

        grid
            .querySelectorAll(
                "article"
            )
            .forEach(
                function(regionCard){

                    if (
                        typeof rpV75Metric !==
                        "function"
                    ) {
                        return;
                    }


                    const scriptCard =
                        regionCard.querySelector(
                            ".rp-v75-card.scripts"
                        );


                    if (!scriptCard) {
                        return;
                    }


                    const activeScripts =
                        rpV75Metric(
                            regionCard,
                            "ACTIVE SCRIPTS"
                        );


                    const scriptEvents =
                        rpV75Metric(
                            regionCard,
                            "SCRIPT EVENTS/S"
                        );


                    const value =
                        scriptCard.querySelector(
                            ".rp-v75-card-value strong"
                        );


                    const unit =
                        scriptCard.querySelector(
                            ".rp-v75-card-value small"
                        );


                    if (value) {

                        value.textContent =
                            activeScripts === null
                                ?
                                "—"
                                :
                                activeScripts.toLocaleString(
                                    undefined,
                                    {
                                        maximumFractionDigits:0
                                    }
                                );
                    }


                    if (unit) {

                        unit.textContent =
                            " ACTIVE";
                    }


                    const footer =
                        scriptCard.querySelector(
                            ".rp-v75-card-footer > span:first-child"
                        );


                    if (footer) {

                        footer.textContent =
                            "EVENT RATE " +
                            (
                                scriptEvents === null
                                    ?
                                    "—"
                                    :
                                    scriptEvents.toLocaleString(
                                        undefined,
                                        {
                                            maximumFractionDigits:1
                                        }
                                    )
                            ) +
                            " /s";
                    }
                }
            );
    }

    /* ========================================================
       AUSTRALIA OAR TWO SLOT UI V1
       ======================================================== */

    function oarTwoSlotBytes(value){

        const bytes =
            Number(
                value ||
                0
            );

        if(
            !Number.isFinite(bytes) ||
            bytes <= 0
        ){
            return "0 B";
        }

        const units =
            [
                "B",
                "KB",
                "MB",
                "GB",
                "TB"
            ];

        let size =
            bytes;

        let unit =
            0;

        while(
            size >= 1024 &&
            unit < units.length - 1
        ){

            size =
                size / 1024;

            unit++;
        }

        return (
            (
                unit === 0
                    ? size.toFixed(0)
                    : size.toFixed(2)
            ) +
            " " +
            units[unit]
        );
    }


    function oarTwoSlotDate(value){

        const timestamp =
            Number(
                value ||
                0
            );

        if(
            !Number.isFinite(timestamp) ||
            timestamp <= 0
        ){
            return "—";
        }

        try{

            return new Date(
                timestamp * 1000
            ).toLocaleString();

        }
        catch(error){

            return "—";
        }
    }


    function oarSlotsHtml(
        row,
        regionName
    ){

        const backups =
            Array.isArray(
                row &&
                row.OarBackups
            )
                ? row.OarBackups
                : [];


        let html =
            '<section class="rp-oar-two-slot">' +
            '<div class="rp-oar-two-slot-head">' +
            '<div class="rp-oar-two-slot-title">MY OAR BACKUPS</div>' +
            '<div class="rp-oar-two-slot-sub">TWO VERIFIED REGION BACKUP SLOTS</div>' +
            '</div>' +
            '<div class="rp-oar-two-slot-grid">';


        for(
            let slot = 1;
            slot <= 2;
            slot++
        ){

            const item =
                backups.find(
                    function(candidate){

                        return (
                            Number(
                                candidate &&
                                candidate.slot
                            ) === slot
                        );
                    }
                );


            if(
                !item ||
                item.empty
            ){

                html +=
                    '<div class="rp-oar-slot empty">' +
                    '<div class="rp-oar-slot-number">SLOT ' +
                    slot +
                    ' — EMPTY</div>' +
                    '<div class="rp-oar-slot-info">Save an OAR to use this backup slot.</div>' +
                    '</div>';

                continue;
            }


            const downloadUrl =
                '/Other/FreshUserDashboardExact/UserPages/region-oar-download.php?region=' +
                encodeURIComponent(
                    regionName
                ) +
                '&slot=' +
                slot;


            html +=
                '<div class="rp-oar-slot">' +
                '<div class="rp-oar-slot-number">SLOT ' +
                slot +
                '</div>' +
                '<div class="rp-oar-slot-name">' +
                esc(
                    String(
                        item.name ||
                        (
                            'Backup-' +
                            slot +
                            '.oar'
                        )
                    )
                ) +
                '</div>' +
                '<div class="rp-oar-slot-info">' +
                esc(
                    oarTwoSlotBytes(
                        item.size
                    )
                ) +
                '<br>' +
                esc(
                    oarTwoSlotDate(
                        item.timestamp
                    )
                ) +
                '</div>' +
                '<a class="rp-oar-slot-download" href="' +
                downloadUrl +
                '" data-oar-action="download" data-region="' +
                esc(
                    regionName
                ) +
                '" data-slot="' +
                slot +
                '">DOWNLOAD OAR</a>' +
                '<button type="button" class="rp-oar-slot-delete" data-oar-action="delete" data-region="' +
                esc(
                    regionName
                ) +
                '" data-slot="' +
                slot +
                '">DELETE OAR</button>' +
                '</div>';
        }


        html +=
            '</div>' +
            '<div class="rp-oar-two-slot-safety">' +
            '<strong>TWO-BACKUP SAFETY</strong>' +
            'When both slots are full, the oldest saved OAR is replaced only after the new OAR has completed and been verified.' +
            '</div>' +
            '</section>';


        return html;
    }

function cardHtml(row){

        const regionName =
            nameOf(row);


        const state =
            stateClass(row);


        let running = isOnline(row);


        let stopped = isOffline(row);


        const perf =
            performanceFor(row);


        const telemetry =
            telemetryFor(row);


        const health =
            simulatorHealth(
                telemetry
            );


        const processRunning = Boolean(perf?.Running);

        /* AUSTRALIA OAR STATE FIX V3 */
        if (processRunning) {
            running = true;
            stopped = false;
        }


        const hostThreads =
            num(
                host.LogicalThreads
            );


        const affinity =
            num(
                perf?.AffinityThreads
            );


        const cpuHost =
            num(
                perf?.CpuHostPercent
            );


        const cpuCores =
            num(
                perf?.CpuEquivalentCores
            );


        const processRam =
            num(
                perf?.WorkingSetGb
            );


        const threadCount =
            num(
                perf?.ThreadCount
            );


        const pid =
            num(
                perf?.ProcessId
            );


        const port =
            num(
                perf?.Port
            );


        const uptime =
            txt(
                perf?.UptimeText
            );


        const startDisabled =
            !controlsAllowed
            ||
            busy
            ||
            running;


        const stopDisabled =
            !controlsAllowed
            ||
            busy
            ||
            stopped;


        const restartDisabled =
            !controlsAllowed
            ||
            busy
            ||
            !running;


        const saveDisabled =
            !controlsAllowed
            ||
            busy
            ||
            saveOarBusy
            ||
            !running;


        return `
        <article class="rp-region">


            <header class="rp-region-header">


                <div class="rp-region-identity">


                    <div class="rp-region-icon">
                        ${regionIcon}
                    </div>


                    <div class="rp-region-title">


                        <span class="rp-region-label">
                            REGION
                        </span>


                        <h3>
                            ${esc(regionName)}
                        </h3>


                        <div class="rp-region-meta">

                            <span>
                                ${esc(
                                    cellsX(row) +
                                    " × " +
                                    cellsY(row)
                                )}
                            </span>

                            <i></i>

                            <span>
                                ${esc(
                                    estateOf(row)
                                )}
                            </span>

                            ${
                                port > 0
                                    ?
                                    `
                                    <i></i>
                                    <span>
                                        PORT ${port}
                                    </span>
                                    `
                                    :
                                    ""
                            }

                        </div>


                    </div>


                </div>


                <div class="rp-header-state">


                    ${
                        processRunning
                            ?
                            `
                            <div class="rp-process-state">
                                PROCESS ACTIVE
                            </div>
                            `
                            :
                            ""
                    }


                    <div
                        class="rp-status ${state}"
                    >

                        <span></span>

                        <strong>
                            ${esc(
                                stateLabel(row)
                            )}
                        </strong>

                    </div>


                </div>


            </header>



            <section class="rp-performance">


                <div class="rp-section-heading">

                    <div>

                        <span>
                            PROCESS PERFORMANCE
                        </span>

                        <small>
                            Live Windows process statistics
                        </small>

                    </div>


                    <div class="rp-process-id">

                        ${
                            pid > 0
                                ?
                                "PID " +
                                pid
                                :
                                "PROCESS —"
                        }

                    </div>

                </div>


                <div class="rp-performance-grid">


                    <div class="rp-performance-item">

                        <span>
                            CPU
                        </span>

                        <strong>
                            ${
                                processRunning
                                    ?
                                    cpuHost.toFixed(1) +
                                    "%"
                                    :
                                    "—"
                            }
                        </strong>

                        <small>
                            Host CPU
                        </small>

                        ${cpuBar(perf)}

                    </div>


                    <div class="rp-performance-item">

                        <span>
                            CPU LOAD
                        </span>

                        <strong>
                            ${
                                processRunning
                                    ?
                                    cpuCores.toFixed(2)
                                    :
                                    "—"
                            }
                        </strong>

                        <small>
                            Core equivalent
                        </small>

                    </div>


                    <div class="rp-performance-item">

                        <span>
                            PROCESS RAM
                        </span>

                        <strong>
                            ${
                                processRunning
                                    ?
                                    processRam.toFixed(2) +
                                    " GB"
                                    :
                                    "—"
                            }
                        </strong>

                        <small>
                            Working set
                        </small>

                        ${ramBar(perf)}

                    </div>


                    <div class="rp-performance-item">

                        <span>
                            CPU ACCESS
                        </span>

                        <strong>
                            ${
                                processRunning
                                    ?
                                    (
                                        affinity +
                                        " / " +
                                        hostThreads
                                    )
                                    :
                                    "—"
                            }
                        </strong>

                        <small>
                            Logical threads
                        </small>

                    </div>


                    <div class="rp-performance-item">

                        <span>
                            THREADS
                        </span>

                        <strong>
                            ${
                                processRunning
                                    ?
                                    threadCount.toLocaleString()
                                    :
                                    "—"
                            }
                        </strong>

                        <small>
                            Process threads
                        </small>

                    </div>


                    <div class="rp-performance-item">

                        <span>
                            UPTIME
                        </span>

                        <strong class="uptime">
                            ${esc(uptime)}
                        </strong>

                        <small>
                            Process uptime
                        </small>

                    </div>


                </div>


            </section>



            <section class="rp-simulator">


                <div class="rp-section-heading">


                    <div>

                        <span>
                            LIVE SIMULATOR PERFORMANCE
                        </span>

                        <small>
                            Real-time OpenSim performance with rolling live history
                        </small>

                    </div>


                    <div
                        class="rp-health ${health.state}"
                    >

                        <i></i>

                        <strong>
                            ${health.label}
                        </strong>

                    </div>


                </div>


                <div class="rp-simulator-grid">


                    <div class="rp-sim-item">

                        <span>SIM FPS</span>

                        <strong>
                            ${esc(metricValue(telemetry,"SimFPS"))}
                        </strong>

                        <small>
                            Simulator frame rate
                        </small>

                    </div>


                    <div class="rp-sim-item">

                        <span>PHYSICS FPS</span>

                        <strong>
                            ${esc(metricValue(telemetry,"PhysicsFPS"))}
                        </strong>

                        <small>
                            Physics frame rate
                        </small>

                    </div>


                    <div class="rp-sim-item">

                        <span>TIME DILATION</span>

                        <strong>
                            ${esc(metricValue(telemetry,"TimeDilation"))}
                        </strong>

                        <small>
                            Simulator timing
                        </small>

                    </div>


                    <div class="rp-sim-item">

                        <span>FRAME TIME</span>

                        <strong>
                            ${esc(metricValue(telemetry,"FrameTime"))}
                        </strong>

                        <small>
                            Frame processing
                        </small>

                    </div>


                    <div class="rp-sim-item">

                        <span>ACTIVE SCRIPTS</span>

                        <strong>
                            ${esc(metricValue(telemetry,"ActiveScripts"))}
                        </strong>

                        <small>
                            Running scripts
                        </small>

                    </div>


                    <div class="rp-sim-item">

                        <span>SCRIPT EVENTS/S</span>

                        <strong>
                            ${esc(metricValue(telemetry,"ScriptEvents"))}
                        </strong>

                        <small>
                            Script event load
                        </small>

                    </div>


                    <div class="rp-sim-item">

                        <span>ROOT AGENTS</span>

                        <strong>
                            ${esc(metricValue(telemetry,"RootAgents"))}
                        </strong>

                        <small>
                            Local avatars
                        </small>

                    </div>


                    <div class="rp-sim-item">

                        <span>CHILD AGENTS</span>

                        <strong>
                            ${esc(metricValue(telemetry,"ChildAgents"))}
                        </strong>

                        <small>
                            Neighbor agents
                        </small>

                    </div>


                    <div class="rp-sim-item">

                        <span>NETWORK IN</span>

                        <strong>
                            ${esc(metricValue(telemetry,"NetIn"))}
                        </strong>

                        <small>
                            Incoming traffic
                        </small>

                    </div>


                    <div class="rp-sim-item">

                        <span>NETWORK OUT</span>

                        <strong>
                            ${esc(metricValue(telemetry,"NetOut"))}
                        </strong>

                        <small>
                            Outgoing traffic
                        </small>

                    </div>


                </div>


            </section>



            <section class="rp-world-stats">


                <div class="rp-section-heading">

                    <div>

                        <span>
                            REGION STATISTICS
                        </span>

                        <small>
                            Live region information
                        </small>

                    </div>

                </div>


                <div class="rp-world-grid">


                    <div class="rp-world-item">

                        <span>
                            PRIMS
                        </span>

                        <strong>
                            ${primsOf(row).toLocaleString()}
                        </strong>

                    </div>


                    <div class="rp-world-item">

                        <span>
                            AVATARS
                        </span>

                        <strong>
                            ${avatarsOf(row).toLocaleString()}
                        </strong>

                    </div>


                    <div class="rp-world-item">

                        <span>
                            SIZE
                        </span>

                        <strong>
                            ${
                                cellsX(row) *
                                256
                            }
                            ×
                            ${
                                cellsY(row) *
                                256
                            }
                        </strong>

                        <small>
                            metres
                        </small>

                    </div>


                    <div class="rp-world-item">

                        <span>
                            PORT
                        </span>

                        <strong>
                            ${
                                port > 0
                                    ?
                                    port
                                    :
                                    "—"
                            }
                        </strong>

                    </div>


                    <div class="rp-world-item owner">

                        <span>
                            ESTATE OWNER
                        </span>

                        <strong>
                            ${esc(
                                ownerOf(row)
                            )}
                        </strong>

                    </div>


                </div>


            </section>



            <footer class="rp-region-footer">


                <div class="rp-operation-title">

                    <span>
                        REGION OPERATIONS
                    </span>

                    <small>
                        Live controls
                    </small>

                </div>


                <!-- AUSTRALIA REGIONS V8.5 OAR SLOT START -->

                <div
                    class="rp-v85-backup"
                    data-oar-region="${esc(regionName)}"
                >
                    ${
                        window.rpV85BackupHtml
                            ?
                            window.rpV85BackupHtml(
                                regionName
                            )
                            :
                            ""
                    }
                </div>

                <!-- AUSTRALIA REGIONS V8.5 OAR SLOT END -->

                ${oarSlotsHtml(row, regionName)}

                <div class="rp-actions">


                    <button
                        type="button"
                        class="rp-action start"
                        data-command="StartRegion"
                        data-region="${esc(regionName)}"
                        ${startDisabled ? "disabled" : ""}
                    >
                        START
                    </button>


                    <button
                        type="button"
                        class="rp-action stop"
                        data-command="StopRegion"
                        data-region="${esc(regionName)}"
                        ${stopDisabled ? "disabled" : ""}
                    >
                        STOP
                    </button>


                    <button
                        type="button"
                        class="rp-action restart"
                        data-command="RestartRegion"
                        data-region="${esc(regionName)}"
                        ${restartDisabled ? "disabled" : ""}
                    >
                        RESTART
                    </button>


                    <button
                        type="button"
                        class="rp-action save"
                        data-command="SaveOAR"
                        data-region="${esc(regionName)}"
                        ${saveDisabled ? "disabled" : ""}
                    >
                        SAVE OAR
                    </button>


                </div>


            </footer>


        </article>
        `;
    }



    function render(){

        const query =
            search
                .value
                .trim()
                .toLowerCase();


        const visible =
            regions.filter(
                function(row){

                    if (query === "") {
                        return true;
                    }


                    return (
                        nameOf(row) +
                        " " +
                        estateOf(row) +
                        " " +
                        ownerOf(row)
                    )
                    .toLowerCase()
                    .includes(query);
                }
            );


        if (!visible.length) {

            grid.innerHTML =
                `
                <div class="rp-empty">

                    <strong>
                        No Regions Found
                    </strong>

                    <span>
                        No regions match the current search.
                    </span>

                </div>
                `;


            return;
        }


        grid.innerHTML =
            visible
                .map(cardHtml)
                .join("");

        // V7.5 SEPARATE LIVE PERFORMANCE CARDS
        rpV75BuildDashboard();

        // V7.6 ACTIVE SCRIPT VALUE + EVENT RATE
        rpV76EnhanceScriptCards();



        // V7.4 COMPACT PERFORMANCE DASHBOARD


        // V7.2D LIVE METER RENDER HOOK



        grid
            .querySelectorAll(
                "[data-command]"
            )
            .forEach(
                function(button){

                    button.addEventListener(
                        "click",
                        handleCommand
                    );
                }
            );
    }



    function setMessage(
        value,
        type = ""
    ){

        message.textContent =
            value;


        message.className =
            "rp-message";


        if (type !== "") {
            message.classList.add(type);
        }
    }



    let refreshInProgress = false;

    async function loadAll(
        quiet = false
    ){

        if (busy || refreshInProgress) {
            return;
        }

        refreshInProgress = true;

        refresh.disabled =
            true;


        if (!quiet) {

            setMessage(
                "Refreshing region and process statistics..."
            );
        }


        try {

            const regionUrl =
                new URL(
                    "/Other/user-regions-data.php",
                    window.location.origin
                );


            regionUrl.searchParams.set(
                "_",
                Date.now().toString()
            );


            if (sid !== "") {

                regionUrl.searchParams.set(
                    "sid",
                    sid
                );
            }


            const performanceUrl =
                new URL(
                    "/Other/FreshUserDashboardExact/UserPages/region-performance.php",
                    window.location.origin
                );


            performanceUrl.searchParams.set(
                "_",
                Date.now().toString()
            );


            const telemetryUrl =
                new URL(
                    "/Other/FreshUserDashboardExact/UserPages/region-live-direct.php",
                    window.location.origin
                );


            telemetryUrl.searchParams.set(
                "_",
                Date.now().toString()
            );


            const responses =
                await Promise.all([
                    fetch(
                        regionUrl.toString(),
                        {
                            credentials:
                                "same-origin",

                            cache:
                                "no-store"
                        }
                    ),

                    fetch(
                        performanceUrl.toString(),
                        {
                            credentials:
                                "same-origin",

                            cache:
                                "no-store"
                        }
                    ),

                    fetch(
                        telemetryUrl.toString(),
                        {
                            credentials:
                                "same-origin",

                            cache:
                                "no-store"
                        }
                    )
                ]);


            const regionRaw =
                await responses[0].text();


            const performanceRaw =
                await responses[1].text();


            const telemetryRaw =
                await responses[2].text();


            let regionData;


            let performanceData;


            let telemetryData;


            try {

                regionData =
                    JSON.parse(
                        regionRaw
                    );

            }
            catch(error){

                throw new Error(
                    "Region data returned invalid JSON."
                );
            }


            try {

                performanceData =
                    JSON.parse(
                        performanceRaw
                    );

            }
            catch(error){

                performanceData =
                    {
                        ok:false,
                        regions:[],
                        host:{}
                    };
            }


            try {

                telemetryData =
                    JSON.parse(
                        telemetryRaw
                    );

            }
            catch(error){

                telemetryData =
                    {
                        ok:false,
                        regions:[]
                    };
            }


            if (
                !responses[0].ok
                ||
                regionData?.ok === false
            ) {

                throw new Error(
                    regionData?.error
                    ||
                    "Region information unavailable."
                );
            }


            regions =
                extractRows(
                    regionData
                );


            controlsAllowed =
                extractControls(
                    regionData
                );


            if (
                responses[1].ok
                &&
                performanceData?.ok !== false
            ) {

                performanceRows =
                    Array.isArray(
                        performanceData.regions
                    )
                        ?
                        performanceData.regions
                        :
                        [];


                host =
                    performanceData.host
                    ||
                    {};

            }
            else {

                performanceRows =
                    [];


                host =
                    {};
            }


            if (
                responses[2].ok
                &&
                telemetryData?.ok !== false
            ) {

                telemetryRows =
                    Array.isArray(
                        telemetryData.regions
                    )
                        ?
                        telemetryData.regions
                        :
                        [];

            }
            else {

                telemetryRows =
                    [];
            }


            regions.sort(
                function(a,b){

                    return nameOf(a)
                        .localeCompare(
                            nameOf(b),
                            undefined,
                            {
                                numeric:true,
                                sensitivity:"base"
                            }
                        );
                }
            );


            updateSummary();

            render();


            setMessage(
                regions.length +
                " regions loaded • Windows + OpenSim telemetry active",
                "success"
            );

        }
        catch(error){

            setMessage(
                error?.message
                ||
                "Unable to load regions.",
                "error"
            );

        }
        finally {

            refreshInProgress = false;
            refresh.disabled =
                false;
        }
    }



    async function sendCommand(
        command,
        region
    ){

        const allowed = [
            "StartRegion",
            "StopRegion",
            "RestartRegion",
            "SaveOAR"
        ];


        if (!allowed.includes(command)) {

            throw new Error(
                "Unsupported command."
            );
        }


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


        if (sid !== "") {

            body.set(
                "sid",
                sid
            );
        }


        const response =
            await fetch(
                "/Other/command.php",
                {
                    method:"POST",

                    credentials:
                        "same-origin",

                    cache:
                        "no-store",

                    headers:{
                        "Content-Type":
                            "application/x-www-form-urlencoded;charset=UTF-8",

                        "Accept":
                            "application/json"
                    },

                    body:
                        body
                }
            );


        const raw =
            await response.text();


        let data = null;


        try {

            data =
                JSON.parse(raw);

        }
        catch(error){
        }


        if (
            !response.ok
            ||
            data?.ok === false
        ) {

            throw new Error(
                data?.error
                ||
                data?.message
                ||
                raw
                ||
                "Region command failed."
            );
        }


        return data;
    }




    function regionActionConfirm(options){

        const settings =
            options ||
            {};

        return new Promise(
            function(resolve){

                const backdrop =
                    document.createElement(
                        "div"
                    );

                backdrop.className =
                    "rp-confirm-backdrop";

                backdrop.innerHTML =
                    '<div class="rp-confirm-dialog" role="dialog" aria-modal="true">' +
                    '<div class="rp-confirm-head">' +
                    '<h3 class="rp-confirm-title"></h3>' +
                    '</div>' +
                    '<div class="rp-confirm-body">' +
                    '<div class="rp-confirm-message"></div>' +
                    '<strong class="rp-confirm-region"></strong>' +
                    '</div>' +
                    '<div class="rp-confirm-buttons">' +
                    '<button type="button" class="rp-confirm-button rp-confirm-cancel">CANCEL</button>' +
                    '<button type="button" class="rp-confirm-button rp-confirm-accept"></button>' +
                    '</div>' +
                    '</div>';

                const title =
                    backdrop.querySelector(
                        ".rp-confirm-title"
                    );

                const message =
                    backdrop.querySelector(
                        ".rp-confirm-message"
                    );

                const regionText =
                    backdrop.querySelector(
                        ".rp-confirm-region"
                    );

                const cancel =
                    backdrop.querySelector(
                        ".rp-confirm-cancel"
                    );

                const accept =
                    backdrop.querySelector(
                        ".rp-confirm-accept"
                    );

                title.textContent =
                    String(
                        settings.title ||
                        "CONFIRM"
                    );

                message.textContent =
                    String(
                        settings.message ||
                        ""
                    );

                regionText.textContent =
                    String(
                        settings.region ||
                        ""
                    );

                accept.textContent =
                    String(
                        settings.confirmText ||
                        "CONFIRM"
                    );

                if (settings.danger) {

                    accept.classList.add(
                        "danger"
                    );
                }

                let finished =
                    false;

                function finish(value){

                    if (finished) {
                        return;
                    }

                    finished =
                        true;

                    document.removeEventListener(
                        "keydown",
                        onKey
                    );

                    backdrop.remove();

                    resolve(
                        value
                    );
                }

                function onKey(event){

                    if (event.key === "Escape") {

                        event.preventDefault();

                        finish(
                            false
                        );
                    }
                }

                cancel.addEventListener(
                    "click",
                    function(){
                        finish(false);
                    }
                );

                accept.addEventListener(
                    "click",
                    function(){
                        finish(true);
                    }
                );

                backdrop.addEventListener(
                    "click",
                    function(event){

                        if (
                            event.target ===
                            backdrop
                        ) {
                            finish(false);
                        }
                    }
                );

                document.addEventListener(
                    "keydown",
                    onKey
                );

                document.body.appendChild(
                    backdrop
                );

                window.requestAnimationFrame(
                    function(){

                        backdrop.classList.add(
                            "open"
                        );

                        accept.focus();
                    }
                );
            }
        );
    }


    async function deleteOarSlot(
        region,
        slot
    ){

        const body =
            new URLSearchParams();

        body.set(
            "command",
            "DeleteOAR"
        );

        body.set(
            "region",
            region
        );

        body.set(
            "slot",
            String(slot)
        );

        if (sid !== "") {

            body.set(
                "sid",
                sid
            );
        }

        const response =
            await fetch(
                "/Other/command.php",
                {
                    method:
                        "POST",

                    credentials:
                        "same-origin",

                    cache:
                        "no-store",

                    headers:{
                        "Content-Type":
                            "application/x-www-form-urlencoded;charset=UTF-8",

                        "Accept":
                            "application/json"
                    },

                    body:
                        body
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

        if (
            !response.ok ||
            !data ||
            data.ok !== true
        ) {

            throw new Error(
                data?.error ||
                data?.message ||
                raw ||
                "OAR backup delete failed."
            );
        }

        return data;
    }


    async function handleOarSlotAction(event){

        const control =
            event.target.closest(
                "[data-oar-action]"
            );

        if (!control) {
            return;
        }

        event.preventDefault();

        if (busy) {
            return;
        }

        const action =
            String(
                control.dataset.oarAction ||
                ""
            );

        const region =
            String(
                control.dataset.region ||
                ""
            ).trim();

        const slot =
            Number(
                control.dataset.slot ||
                0
            );

        if (
            !region ||
            slot < 1 ||
            slot > 2
        ) {
            return;
        }

        if (action === "download") {

            const confirmed =
                await regionActionConfirm(
                    {
                        title:
                            "DOWNLOAD OAR",

                        message:
                            "Download this saved OAR backup to your computer?",

                        region:
                            region +
                            " — Backup-" +
                            slot +
                            ".oar",

                        confirmText:
                            "DOWNLOAD"
                    }
                );

            if (!confirmed) {
                return;
            }

            const href =
                control.getAttribute(
                    "href"
                );

            if (href) {

                window.location.href =
                    href;
            }

            return;
        }

        if (action !== "delete") {
            return;
        }

        const confirmed =
            await regionActionConfirm(
                {
                    title:
                        "DELETE OAR BACKUP",

                    message:
                        "Delete this saved dashboard OAR slot? DreamGrid Autobackup OAR files are not deleted.",

                    region:
                        region +
                        " — Backup-" +
                        slot +
                        ".oar",

                    confirmText:
                        "DELETE OAR",

                    danger:
                        true
                }
            );

        if (!confirmed) {
            return;
        }

        busy =
            true;

        render();

        try{

            const data =
                await deleteOarSlot(
                    region,
                    slot
                );

            setMessage(
                data?.message ||
                (
                    "Backup " +
                    slot +
                    " deleted for " +
                    region +
                    "."
                ),
                "success"
            );
        }
        catch(error){

            setMessage(
                error?.message ||
                "OAR backup delete failed.",
                "error"
            );
        }
        finally{

            busy =
                false;

            try{

                await loadAll(
                    true
                );
            }
            catch(refreshError){

                console.error(
                    "Region refresh failed after OAR delete:",
                    refreshError
                );

                render();
            }
        }
    }
    async function handleCommand(event){

        const button =
            event.currentTarget;


        if (
            busy
            ||
            button.disabled
        ) {
            return;
        }


        const command =
            button.dataset.command;


        const region =
            button.dataset.region;






        if (command === "StopRegion") {

            const confirmed =
                await regionActionConfirm(
                    {
                        title:
                            "STOP REGION",

                        message:
                            "Stop this region now? Residents currently inside the region will be disconnected.",

                        region:
                            region,

                        confirmText:
                            "STOP REGION",

                        danger:
                            true
                    }
                );

            if (!confirmed) {
                return;
            }
        }


        if (command === "RestartRegion") {

            const confirmed =
                await regionActionConfirm(
                    {
                        title:
                            "RESTART REGION",

                        message:
                            "Restart this region now? The simulator will stop and start again.",

                        region:
                            region,

                        confirmText:
                            "RESTART"
                    }
                );

            if (!confirmed) {
                return;
            }
        }


        if (command === "SaveOAR") {

            const confirmed =
                await regionActionConfirm(
                    {
                        title:
                            "SAVE OAR",

                        message:
                            "Create a new verified OAR backup? An empty slot is used first. When both slots are full, the oldest slot is replaced only after the new OAR completes and verifies.",

                        region:
                            region,

                        confirmText:
                            "SAVE OAR"
                    }
                );

            if (!confirmed) {
                return;
            }
        }
        /*
         * SAVE OAR DOES NOT USE THE GLOBAL REGION BUSY LOCK.
         */
        if (command === "SaveOAR") {

            saveOarBusy =
                true;

            render();

            /*
             * Show the active Save OAR state directly in this
             * region's existing REGION OPERATIONS OAR card.
             *
             * This deliberately does not change the shared
             * LAST OAR renderer or introduce cross-script state.
             */

            document
                .querySelectorAll(
                    ".rp-v85-backup[data-oar-region]"
                )
                .forEach(
                    function(element){

                        const cardRegion =
                            String(
                                element.dataset.oarRegion
                                ||
                                ""
                            )
                            .trim()
                            .toLowerCase();

                        const wantedRegion =
                            String(
                                region
                                ||
                                ""
                            )
                            .trim()
                            .toLowerCase();

                        if (cardRegion !== wantedRegion) {
                            return;
                        }

                        element.innerHTML =
                            '<div class="rp-v85-main">'
                            +
                            '<i class="rp-v85-dot"></i>'
                            +
                            '<span class="rp-v85-label">SAVE OAR</span>'
                            +
                            '<strong class="rp-v85-age">SAVING</strong>'
                            +
                            '</div>'
                            +
                            '<small class="rp-v85-sub">DreamGrid OAR backup in progress</small>';
                    }
                );

            setMessage(
                "Saving OAR for " +
                region +
                "...",
                "success"
            );

            try {

                const data =
                    await sendCommand(
                        command,
                        region
                    );

                setMessage(
                    data?.message
                    ||
                    (
                        "Saving OAR for " +
                        region +
                        "."
                    ),
                    "success"
                );

            }
            catch(error){

                setMessage(
                    error?.message
                    ||
                    "Save OAR failed.",
                    "error"
                );

            }
            finally {

                saveOarBusy =
                    false;

                try {

                    await loadAll(
                        true
                    );

                }
                catch(refreshError){

                    console.error(
                        "Region refresh failed after Save OAR:",
                        refreshError
                    );

                    render();
                }
            }

            return;
        }

busy = true;

        render();


        try {

            await sendCommand(
                command,
                region
            );


            setMessage(
                "Command accepted for " +
                region +
                ".",
                "success"
            );


            await new Promise(
                function(resolve){

                    window.setTimeout(
                        resolve,
                        1600
                    );
                }
            );

        }
        catch(error){

            setMessage(
                error?.message
                ||
                "Region command failed.",
                "error"
            );

        }
        finally {
            busy = false;

            try {
                await loadAll(true);
            } catch (refreshError) {
                console.error('Region refresh failed after command:', refreshError);

                if (typeof render === 'function') {
                    render();
                }
            }
        }
    }



    document.addEventListener(
        "click",
        handleOarSlotAction
    );


    search.addEventListener(
        "input",
        render
    );


    refresh.addEventListener(
        "click",
        function(){

            loadAll();
        }
    );


    loadAll();


    window.setInterval(
        function(){

            if (
                !busy
                &&
                document.visibilityState === "visible"
            ) {

                loadAll(true);
            }

        },
        2000
    );

})();

</script>


</body>

</html>

<!-- AUSTRALIA REGIONS V8.0B START -->

<style>

/* ============================================================
   BIGGER PROCESS PERFORMANCE TEXT
   ============================================================ */

.rp-performance-item > span {
    font-size: 12px !important;
    line-height: 1.25 !important;
    letter-spacing: .06em !important;
}

.rp-performance-item > strong {
    display: block !important;
    font-size: 20px !important;
    line-height: 1.15 !important;
    margin-top: 6px !important;
}

.rp-performance-item > small {
    display: block !important;
    font-size: 11px !important;
    line-height: 1.35 !important;
    margin-top: 5px !important;
    opacity: .82 !important;
}


/* Region/card secondary text */

.rp-region-card small,
.rp-card small,
.region-card small {
    font-size: 11px !important;
    line-height: 1.4 !important;
}


/* Give process cells a little more room */

.rp-performance-item {
    padding-top: 14px !important;
    padding-bottom: 14px !important;
}


/* ============================================================
   PROCESS METERS
   ============================================================ */

.rp-v80b-meter {
    position: relative;
    width: 100%;
    height: 10px;
    margin-top: 11px;
    overflow: hidden;

    border: 1px solid rgba(72, 160, 200, .38);
    border-radius: 6px;

    background: rgba(4, 17, 25, .92);

    box-shadow:
        inset 0 1px 3px rgba(0,0,0,.75),
        0 0 5px rgba(0,120,170,.08);
}


.rp-v80b-meter::after {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;

    background:
        repeating-linear-gradient(
            90deg,
            transparent 0,
            transparent calc(25% - 1px),
            rgba(130, 200, 220, .10) calc(25% - 1px),
            rgba(130, 200, 220, .10) 25%
        );
}


.rp-v80b-fill {
    display: block;
    width: 0%;
    height: 100%;
    border-radius: 5px;

    transition: width .45s ease;

    box-shadow:
        0 0 9px currentColor;
}


/* CPU */

.rp-v80b-meter[data-kind="CPU"] .rp-v80b-fill {
    color: #28d7ff;
    background: linear-gradient(90deg,#087da8,#28d7ff);
}


/* CPU LOAD */

.rp-v80b-meter[data-kind="CPU LOAD"] .rp-v80b-fill {
    color: #29ddcf;
    background: linear-gradient(90deg,#087e78,#29ddcf);
}


/* RAM */

.rp-v80b-meter[data-kind="PROCESS RAM"] .rp-v80b-fill {
    color: #52e58d;
    background: linear-gradient(90deg,#138448,#52e58d);
}


/* CPU ACCESS */

.rp-v80b-meter[data-kind="CPU ACCESS"] .rp-v80b-fill {
    color: #76e978;
    background: linear-gradient(90deg,#24883b,#76e978);
}


/* THREADS */

.rp-v80b-meter[data-kind="THREADS"] .rp-v80b-fill {
    color: #f4bc36;
    background: linear-gradient(90deg,#a56a08,#f4bc36);
}

</style>


<script>

(function(){

    "use strict";


    function v80bNumber(text){

        const match =
            String(text || "")
            .replace(/,/g,"")
            .match(/-?\d+(?:\.\d+)?/);

        if (!match) {
            return null;
        }

        const value = Number(match[0]);

        return Number.isFinite(value)
            ? value
            : null;
    }


    function v80bPercent(label,text){

        label =
            String(label || "")
            .trim()
            .toUpperCase();


        if (label === "CPU") {

            const value = v80bNumber(text);

            if (value === null) {
                return null;
            }

            return Math.max(0,Math.min(100,value));
        }


        if (label === "CPU LOAD") {

            const value = v80bNumber(text);

            if (value === null) {
                return null;
            }

            /*
             * One whole CPU core = 100% visual meter.
             */

            return Math.max(
                0,
                Math.min(
                    100,
                    value * 100
                )
            );
        }


        if (label === "PROCESS RAM") {

            const value = v80bNumber(text);

            if (value === null) {
                return null;
            }

            /*
             * 8 GB = full meter.
             */

            return Math.max(
                0,
                Math.min(
                    100,
                    (value / 8) * 100
                )
            );
        }


        if (label === "CPU ACCESS") {

            const numbers =
                String(text || "")
                .match(/\d+(?:\.\d+)?/g);

            if (!numbers || numbers.length < 2) {
                return null;
            }

            const used = Number(numbers[0]);
            const total = Number(numbers[1]);

            if (!Number.isFinite(used)) {
                return null;
            }

            if (!Number.isFinite(total)) {
                return null;
            }

            if (total <= 0) {
                return null;
            }

            return Math.max(
                0,
                Math.min(
                    100,
                    (used / total) * 100
                )
            );
        }


        if (label === "THREADS") {

            const value = v80bNumber(text);

            if (value === null) {
                return null;
            }

            /*
             * 64 process threads = full meter.
             */

            return Math.max(
                0,
                Math.min(
                    100,
                    (value / 64) * 100
                )
            );
        }


        return null;
    }


    function v80bItem(item){

        const labelElement =
            item.querySelector("span");

        const valueElement =
            item.querySelector("strong");

        if (!labelElement || !valueElement) {
            return;
        }


        const label =
            labelElement.textContent
            .trim()
            .toUpperCase();


        const supported = [
            "CPU",
            "CPU LOAD",
            "PROCESS RAM",
            "CPU ACCESS",
            "THREADS"
        ];


        if (!supported.includes(label)) {

            const existing =
                item.querySelector(".rp-v80b-meter");

            if (existing) {
                existing.remove();
            }

            return;
        }


        const valueText =
            valueElement.textContent.trim();


        const percent =
            v80bPercent(
                label,
                valueText
            );


        let meter =
            item.querySelector(".rp-v80b-meter");


        if (!meter) {

            meter =
                document.createElement("div");

            meter.className =
                "rp-v80b-meter";

            meter.setAttribute(
                "aria-hidden",
                "true"
            );


            const fill =
                document.createElement("span");

            fill.className =
                "rp-v80b-fill";

            meter.appendChild(fill);

            item.appendChild(meter);
        }


        meter.dataset.kind =
            label;


        const fill =
            meter.querySelector(".rp-v80b-fill");

        if (!fill) {
            return;
        }


        if (percent === null) {

            fill.style.width = "0%";
            meter.style.opacity = ".45";
            meter.title = "Waiting for live data";

            return;
        }


        meter.style.opacity = "1";


        /*
         * Very small non-zero values would be invisible,
         * so display at least 2% while preserving the actual
         * numeric value above the meter.
         */

        let visual =
            percent;

        if (percent > 0 && percent < 2) {
            visual = 2;
        }


        fill.style.width =
            visual.toFixed(2) + "%";


        if (label === "CPU") {

            meter.title =
                valueText + " CPU";
        }
        else if (label === "CPU LOAD") {

            meter.title =
                valueText + " core equivalent";
        }
        else if (label === "PROCESS RAM") {

            meter.title =
                valueText + " / 8 GB scale";
        }
        else if (label === "CPU ACCESS") {

            meter.title =
                valueText + " logical threads";
        }
        else if (label === "THREADS") {

            meter.title =
                valueText + " / 64 scale";
        }
    }


    function v80bUpdate(){

        document
            .querySelectorAll(".rp-performance-item")
            .forEach(v80bItem);
    }


    /*
     * Run immediately if possible.
     */

    if (document.readyState === "loading") {

        document.addEventListener(
            "DOMContentLoaded",
            v80bUpdate,
            {once:true}
        );
    }
    else {

        v80bUpdate();
    }


    /*
     * Existing Regions refresh is every two seconds.
     * Reapply meters after the cards redraw.
     */

    let scheduled =
        false;


    const observer =
        new MutationObserver(
            function(){

                if (scheduled) {
                    return;
                }

                scheduled =
                    true;

                requestAnimationFrame(
                    function(){

                        scheduled =
                            false;

                        v80bUpdate();
                    }
                );
            }
        );


    observer.observe(
        document.body,
        {
            childList:true,
            subtree:true
        }
    );


    window.setInterval(
        v80bUpdate,
        2200
    );


    window.v80bUpdateProcessMeters =
        v80bUpdate;

})();

</script>

<!-- AUSTRALIA REGIONS V8.0B END -->

<!-- AUSTRALIA REGIONS V8.1 START -->

<style>

/* ============================================================
   V8.1 PERFORMANCE ALARM
   ============================================================ */


/*
 * Whole card red state.
 */

.rp-v81-bad {
    border-color: rgba(255, 54, 72, .95) !important;

    box-shadow:
        0 0 0 1px rgba(255, 54, 72, .18),
        0 0 16px rgba(255, 30, 50, .30),
        inset 0 0 22px rgba(120, 0, 12, .12) !important;

    background:
        linear-gradient(
            180deg,
            rgba(47, 8, 14, .96),
            rgba(13, 11, 17, .98)
        ) !important;

    transition:
        border-color .35s ease,
        box-shadow .35s ease,
        background .35s ease;
}


/*
 * Headings / live indicator.
 */

.rp-v81-bad [class*="title"],
.rp-v81-bad [class*="name"],
.rp-v81-bad [class*="live"],
.rp-v81-bad header span {
    color: #ff5969 !important;
}


/*
 * Large current value.
 */

.rp-v81-bad strong {
    color: #ff6675 !important;

    text-shadow:
        0 0 10px rgba(255, 40, 60, .42) !important;
}


/*
 * SVG graph line/area.
 */

.rp-v81-bad svg polyline,
.rp-v81-bad svg path {
    stroke: #ff4054 !important;
}


/*
 * Area fills where the graph engine uses SVG fills.
 */

.rp-v81-bad svg path[fill]:not([fill="none"]) {
    fill: rgba(255, 45, 65, .18) !important;
}


/*
 * Dots / current point.
 */

.rp-v81-bad svg circle {
    fill: #ff4054 !important;
    stroke: #ff7a87 !important;
}


/*
 * Keep grid lines neutral.
 */

.rp-v81-bad svg line {
    stroke: rgba(80, 115, 135, .25) !important;
}


/*
 * Alarm badge.
 */

.rp-v81-alarm {
    display: inline-flex;
    align-items: center;
    gap: 5px;

    margin-left: 7px;
    padding: 2px 6px;

    border: 1px solid rgba(255, 69, 84, .65);
    border-radius: 9px;

    background: rgba(100, 0, 10, .55);

    color: #ff7581 !important;

    font-size: 8px !important;
    font-weight: 700;
    letter-spacing: .08em;

    box-shadow:
        0 0 8px rgba(255, 35, 55, .22);
}


.rp-v81-alarm::before {
    content: "";

    width: 5px;
    height: 5px;

    border-radius: 50%;

    background: #ff4054;

    box-shadow:
        0 0 7px #ff4054;

    animation:
        rpV81Pulse 1.15s ease-in-out infinite;
}


@keyframes rpV81Pulse {

    0%,
    100% {
        opacity: .45;
    }

    50% {
        opacity: 1;
    }
}

</style>


<script>

(function(){

    "use strict";


    /*
     * ========================================================
     * THRESHOLDS
     *
     * Easy to alter later.
     * ========================================================
     */

    const limits = {

        simFpsBad:
            40,

        physicsFpsBad:
            40,

        scriptEventsBad:
            500,

        activeScriptsBad:
            15000
    };


    /*
     * ========================================================
     * NUMBER PARSER
     * ========================================================
     */

    function v81Number(value){

        const match =
            String(value ?? "")
            .replace(/,/g,"")
            .match(/-?\d+(?:\.\d+)?/);


        if (!match) {
            return null;
        }


        const number =
            Number(
                match[0]
            );


        return Number.isFinite(number)
            ?
            number
            :
            null;
    }


    /*
     * ========================================================
     * READ HIDDEN OPENSIM METRIC
     * ========================================================
     */

    function v81Metric(region,label){

        if (!region) {
            return null;
        }


        const wanted =
            String(label)
            .trim()
            .toUpperCase();


        const items =
            region.querySelectorAll(
                ".rp-sim-item"
            );


        for (const item of items) {

            const heading =
                item.querySelector(
                    "span"
                );


            const value =
                item.querySelector(
                    "strong"
                );


            if (!heading || !value) {
                continue;
            }


            if (
                heading.textContent
                .trim()
                .toUpperCase()
                ===
                wanted
            ) {

                return v81Number(
                    value.textContent
                );
            }
        }


        return null;
    }


    /*
     * ========================================================
     * FIND REGION CARD
     * ========================================================
     */

    function v81RegionFor(element){

        if (!element) {
            return null;
        }


        return (
            element.closest(
                ".rp-region-card"
            )
            ||
            element.closest(
                "[class*='region-card']"
            )
            ||
            element.closest(
                "[class*='region'][class*='card']"
            )
        );
    }


    /*
     * ========================================================
     * IDENTIFY PERFORMANCE GRAPH CARD
     * ========================================================
     */

    function v81GraphCard(labelElement){

        if (!labelElement) {
            return null;
        }


        /*
         * Try known card-style ancestors first.
         */

        const selectors = [
            ".rp-v75-card",
            ".rp-v76-card",
            ".rp-performance-card",
            ".rp-live-card",
            "[class*='metric-card']",
            "[class*='perf-card']"
        ];


        for (const selector of selectors) {

            const match =
                labelElement.closest(
                    selector
                );


            if (match) {
                return match;
            }
        }


        /*
         * Generic fallback.
         *
         * Walk upward until we find the small graph container.
         */

        let node =
            labelElement;


        for (let level = 0; level < 7; level++) {

            node =
                node.parentElement;


            if (!node) {
                break;
            }


            const text =
                node.textContent
                .toUpperCase();


            const hasGraph =
                Boolean(
                    node.querySelector(
                        "svg,canvas"
                    )
                );


            const looksLikePerformanceCard =
                text.includes("LIVE")
                &&
                (
                    text.includes("REFRESH")
                    ||
                    text.includes("MIN")
                    ||
                    text.includes("EVENT RATE")
                );


            if (
                hasGraph
                &&
                looksLikePerformanceCard
            ) {

                return node;
            }
        }


        return null;
    }


    /*
     * ========================================================
     * FIND GRAPH CARD BY TITLE
     * ========================================================
     */

    function v81FindCard(region,title){

        const wanted =
            title
            .trim()
            .toUpperCase();


        const candidates =
            region.querySelectorAll(
                "span,div,strong,h1,h2,h3,h4,h5"
            );


        for (const element of candidates) {

            if (
                element.children.length > 0
            ) {

                continue;
            }


            if (
                element.textContent
                .trim()
                .toUpperCase()
                !==
                wanted
            ) {

                continue;
            }


            const card =
                v81GraphCard(
                    element
                );


            if (card) {
                return card;
            }
        }


        return null;
    }


    /*
     * ========================================================
     * ALARM BADGE
     * ========================================================
     */

    function v81Badge(card,bad,reason){

        if (!card) {
            return;
        }


        let badge =
            card.querySelector(
                ".rp-v81-alarm"
            );


        if (!bad) {

            if (badge) {
                badge.remove();
            }

            card.removeAttribute(
                "data-v81-reason"
            );

            return;
        }


        if (!badge) {

            badge =
                document.createElement(
                    "span"
                );


            badge.className =
                "rp-v81-alarm";


            badge.textContent =
                "BAD";


            /*
             * Put it near the title.
             */

            const title =
                Array.from(
                    card.querySelectorAll(
                        "span,div,strong"
                    )
                )
                .find(
                    function(element){

                        const text =
                            element.textContent
                            .trim()
                            .toUpperCase();


                        return (
                            text === "SIM FPS"
                            ||
                            text === "PHYSICS FPS"
                            ||
                            text === "SCRIPT ACTIVITY"
                        );
                    }
                );


            if (
                title
                &&
                title.parentElement
            ) {

                title.parentElement.appendChild(
                    badge
                );
            }
            else {

                card.insertBefore(
                    badge,
                    card.firstChild
                );
            }
        }


        badge.title =
            reason;


        card.setAttribute(
            "data-v81-reason",
            reason
        );


        card.title =
            reason;
    }


    /*
     * ========================================================
     * SET CARD STATE
     * ========================================================
     */

    function v81SetState(card,bad,reason){

        if (!card) {
            return;
        }


        card.classList.toggle(
            "rp-v81-bad",
            bad
        );


        v81Badge(
            card,
            bad,
            reason
        );


        if (!bad) {

            /*
             * Remove our alarm tooltip, but only when it was
             * placed by V8.1.
             */

            if (
                card.hasAttribute(
                    "data-v81-reason"
                )
            ) {

                card.removeAttribute(
                    "title"
                );
            }
        }
    }


    /*
     * ========================================================
     * UPDATE ONE REGION
     * ========================================================
     */

    function v81Region(region){

        if (!region) {
            return;
        }


        const sim =
            v81Metric(
                region,
                "SIM FPS"
            );


        const physics =
            v81Metric(
                region,
                "PHYSICS FPS"
            );


        const activeScripts =
            v81Metric(
                region,
                "ACTIVE SCRIPTS"
            );


        const scriptEvents =
            v81Metric(
                region,
                "SCRIPT EVENTS/S"
            );


        const simCard =
            v81FindCard(
                region,
                "SIM FPS"
            );


        const physicsCard =
            v81FindCard(
                region,
                "PHYSICS FPS"
            );


        const scriptCard =
            v81FindCard(
                region,
                "SCRIPT ACTIVITY"
            );


        /*
         * SIM FPS
         */

        const simBad =
            sim !== null
            &&
            sim < limits.simFpsBad;


        v81SetState(
            simCard,
            simBad,
            simBad
                ?
                "SIM FPS is below "
                +
                limits.simFpsBad
                :
                ""
        );


        /*
         * PHYSICS FPS
         */

        const physicsBad =
            physics !== null
            &&
            physics < limits.physicsFpsBad;


        v81SetState(
            physicsCard,
            physicsBad,
            physicsBad
                ?
                "Physics FPS is below "
                +
                limits.physicsFpsBad
                :
                ""
        );


        /*
         * SCRIPT ACTIVITY
         *
         * Red if either event pressure or running script
         * count becomes unusually high.
         */

        const scriptEventBad =
            scriptEvents !== null
            &&
            scriptEvents >= limits.scriptEventsBad;


        const scriptCountBad =
            activeScripts !== null
            &&
            activeScripts >= limits.activeScriptsBad;


        const scriptBad =
            scriptEventBad
            ||
            scriptCountBad;


        let scriptReason =
            "";


        if (scriptEventBad) {

            scriptReason =
                "Script events are "
                +
                scriptEvents
                +
                "/s";
        }


        if (scriptCountBad) {

            if (scriptReason !== "") {

                scriptReason +=
                    " • ";
            }


            scriptReason +=
                "Active scripts are "
                +
                activeScripts;
        }


        v81SetState(
            scriptCard,
            scriptBad,
            scriptReason
        );
    }


    /*
     * ========================================================
     * UPDATE ALL REGIONS
     * ========================================================
     */

    let busy =
        false;


    function v81Update(){

        if (busy) {
            return;
        }


        busy =
            true;


        try {

            const regions =
                document.querySelectorAll(
                    ".rp-region-card, [class*='region-card']"
                );


            regions.forEach(
                v81Region
            );
        }
        finally {

            busy =
                false;
        }
    }


    /*
     * Initial update.
     */

    if (
        document.readyState
        ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            v81Update,
            {
                once:true
            }
        );
    }
    else {

        v81Update();
    }


    /*
     * Existing dashboard telemetry updates every ~2 sec.
     */

    window.setInterval(
        v81Update,
        2100
    );


    /*
     * Also react immediately when the region cards redraw.
     */

    let scheduled =
        false;


    const observer =
        new MutationObserver(
            function(){

                if (busy || scheduled) {
                    return;
                }


                scheduled =
                    true;


                requestAnimationFrame(
                    function(){

                        scheduled =
                            false;

                        v81Update();
                    }
                );
            }
        );


    observer.observe(
        document.body,
        {
            childList:true,
            subtree:true
        }
    );


    /*
     * Console/debug helper.
     */

    window.rpV81UpdatePerformanceAlarms =
        v81Update;


    window.rpV81Limits =
        limits;

})();

</script>

<!-- AUSTRALIA REGIONS V8.1 END -->

<!-- AUSTRALIA REGIONS V8.2D START -->

<style>

/* ============================================================
   V8.2D
   REAL V7.5 LIVE CARDS
   ============================================================ */

.rp-v82d-dashboard {
    display: grid !important;
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;

    width: 100% !important;
    max-width: none !important;
    min-width: 0 !important;

    gap: 18px !important;

    margin: 0 !important;

    box-sizing: border-box !important;

    align-items: stretch !important;
    justify-content: stretch !important;
}


.rp-v82d-dashboard > .rp-v75-card {
    width: 100% !important;
    max-width: none !important;
    min-width: 0 !important;

    flex: none !important;

    margin: 0 !important;

    box-sizing: border-box !important;
}


.rp-v82d-dashboard .rp-v75-canvas {
    width: 100% !important;
    max-width: none !important;
}


.rp-v82d-section {
    width: 100% !important;
    max-width: none !important;
    min-width: 0 !important;

    box-sizing: border-box !important;
}


@media (max-width: 900px) {

    .rp-v82d-dashboard {
        grid-template-columns:
            repeat(2, minmax(0, 1fr)) !important;
    }
}


@media (max-width: 650px) {

    .rp-v82d-dashboard {
        grid-template-columns:
            1fr !important;
    }
}

</style>


<script>

(function(){

    "use strict";


    function rpV82DFixRegion(regionCard){

        if (!regionCard) {
            return;
        }


        /*
         * THESE ARE THE ACTUAL V7.5 VISIBLE GRAPH CARDS.
         */

        const cards =
            Array.from(
                regionCard.querySelectorAll(
                    ".rp-v75-card"
                )
            );


        if (cards.length < 3) {
            return;
        }


        /*
         * All three visible cards are created inside the same
         * V7.5 dashboard element.
         */

        const dashboard =
            cards[0].parentElement;


        if (!dashboard) {
            return;
        }


        /*
         * Make absolutely sure the three cards belong
         * to this same dashboard.
         */

        const dashboardCards =
            Array.from(
                dashboard.children
            )
            .filter(
                function(element){

                    return element.classList
                        .contains(
                            "rp-v75-card"
                        );
                }
            );


        if (dashboardCards.length < 3) {
            return;
        }


        dashboard.classList.add(
            "rp-v82d-dashboard"
        );


        dashboard.style.setProperty(
            "display",
            "grid",
            "important"
        );


        dashboard.style.setProperty(
            "grid-template-columns",
            "repeat(3, minmax(0, 1fr))",
            "important"
        );


        dashboard.style.setProperty(
            "width",
            "100%",
            "important"
        );


        dashboard.style.setProperty(
            "max-width",
            "none",
            "important"
        );


        dashboard.style.setProperty(
            "min-width",
            "0",
            "important"
        );


        dashboard.style.setProperty(
            "gap",
            "18px",
            "important"
        );


        dashboardCards.forEach(
            function(card){

                card.style.setProperty(
                    "width",
                    "100%",
                    "important"
                );


                card.style.setProperty(
                    "max-width",
                    "none",
                    "important"
                );


                card.style.setProperty(
                    "min-width",
                    "0",
                    "important"
                );


                card.style.setProperty(
                    "margin",
                    "0",
                    "important"
                );


                card.style.setProperty(
                    "flex",
                    "none",
                    "important"
                );
            }
        );


        /*
         * V7.5 builds:
         *
         * SECTION
         *   DASHBOARD
         *      3 x .rp-v75-card
         *
         * Expand that section as well.
         */

        const section =
            dashboard.parentElement;


        if (section) {

            section.classList.add(
                "rp-v82d-section"
            );


            section.style.setProperty(
                "width",
                "100%",
                "important"
            );


            section.style.setProperty(
                "max-width",
                "none",
                "important"
            );


            section.style.setProperty(
                "min-width",
                "0",
                "important"
            );


            /*
             * Also expand the simulator container that
             * V7.5 prepends the section into.
             */

            const simulator =
                section.parentElement;


            if (simulator) {

                simulator.style.setProperty(
                    "width",
                    "100%",
                    "important"
                );


                simulator.style.setProperty(
                    "max-width",
                    "none",
                    "important"
                );


                simulator.style.setProperty(
                    "min-width",
                    "0",
                    "important"
                );
            }
        }
    }


    function rpV82DUpdate(){

        document
            .querySelectorAll(
                "article"
            )
            .forEach(
                rpV82DFixRegion
            );
    }


    if (
        document.readyState
        ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            rpV82DUpdate,
            {
                once:true
            }
        );
    }
    else {

        rpV82DUpdate();
    }


    /*
     * The page rebuilds the V7.5 dashboard every refresh,
     * so reapply after each DOM redraw.
     */

    let scheduled =
        false;


    const observer =
        new MutationObserver(
            function(){

                if (scheduled) {
                    return;
                }


                scheduled =
                    true;


                requestAnimationFrame(
                    function(){

                        scheduled =
                            false;

                        rpV82DUpdate();
                    }
                );
            }
        );


    observer.observe(
        document.body,
        {
            childList:true,
            subtree:true
        }
    );


    window.setInterval(
        rpV82DUpdate,
        2100
    );


    window.rpV82DActualCardWidth =
        rpV82DUpdate;

})();

</script>

<!-- AUSTRALIA REGIONS V8.2D END -->

<!-- AUSTRALIA REGIONS V8.3 START -->

<style>

/* ============================================================
   V8.3 LIVE REGION HEALTH STRIP
   ============================================================ */

.rp-v83-health-strip {
    display: grid;

    grid-template-columns:
        1.15fr
        repeat(5, minmax(0, 1fr));

    gap: 1px;

    width: 100%;
    max-width: none;

    margin:
        0
        0
        12px
        0;

    overflow: hidden;

    border:
        1px solid
        rgba(70, 110, 125, .35);

    border-radius: 6px;

    background:
        rgba(5, 13, 17, .96);

    box-sizing: border-box;
}


.rp-v83-health-item {
    position: relative;

    min-width: 0;

    padding:
        10px
        13px;

    background:
        linear-gradient(
            180deg,
            rgba(15, 25, 29, .96),
            rgba(8, 15, 18, .96)
        );

    border-right:
        1px solid
        rgba(65, 90, 100, .28);

    box-sizing: border-box;
}


.rp-v83-health-item:last-child {
    border-right: 0;
}


.rp-v83-health-label {
    display: block;

    margin-bottom: 5px;

    color: #718894;

    font-size: 9px;
    font-weight: 750;

    letter-spacing: .08em;

    text-transform: uppercase;
}


.rp-v83-health-value {
    display: flex;
    align-items: center;

    gap: 7px;

    min-width: 0;

    color: #dbe7ec;

    font-size: 14px;
    font-weight: 800;

    line-height: 1.1;
}


.rp-v83-health-sub {
    display: block;

    margin-top: 4px;

    overflow: hidden;

    color: #5f7782;

    font-size: 8px;
    font-weight: 650;

    line-height: 1.25;

    white-space: nowrap;
    text-overflow: ellipsis;
}


/* ============================================================
   STATUS DOT
   ============================================================ */

.rp-v83-dot {
    flex: 0 0 auto;

    width: 7px;
    height: 7px;

    border-radius: 50%;
}


/* GOOD */

.rp-v83-good .rp-v83-health-value {
    color: #65e891;
}

.rp-v83-good .rp-v83-dot {
    background: #3be777;

    box-shadow:
        0 0 8px
        rgba(59, 231, 119, .75);
}


/* WARNING */

.rp-v83-warning {
    background:
        linear-gradient(
            180deg,
            rgba(50, 38, 10, .94),
            rgba(19, 16, 8, .96)
        );
}

.rp-v83-warning .rp-v83-health-value {
    color: #ffc44d;
}

.rp-v83-warning .rp-v83-dot {
    background: #ffbd39;

    box-shadow:
        0 0 8px
        rgba(255, 189, 57, .75);
}


/* CRITICAL */

.rp-v83-critical {
    background:
        linear-gradient(
            180deg,
            rgba(60, 13, 18, .95),
            rgba(23, 8, 11, .97)
        );
}

.rp-v83-critical .rp-v83-health-value {
    color: #ff6473;
}

.rp-v83-critical .rp-v83-dot {
    background: #ff4054;

    box-shadow:
        0 0 9px
        rgba(255, 50, 70, .9);
}


/* INFO */

.rp-v83-info .rp-v83-health-value {
    color: #74d8f3;
}

.rp-v83-info .rp-v83-dot {
    background: #35cef2;

    box-shadow:
        0 0 8px
        rgba(53, 206, 242, .6);
}


/* ============================================================
   OVERALL HEALTH
   ============================================================ */

.rp-v83-overall .rp-v83-health-value {
    font-size: 15px;
}


/* ============================================================
   RESPONSIVE
   ============================================================ */

@media (max-width: 1200px) {

    .rp-v83-health-strip {
        grid-template-columns:
            repeat(
                3,
                minmax(0, 1fr)
            );
    }
}


@media (max-width: 700px) {

    .rp-v83-health-strip {
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }
}

</style>


<script>

(function(){

    "use strict";


    /* ========================================================
       HELPERS
       ======================================================== */

    function v83Number(value){

        const match =
            String(
                value ?? ""
            )
            .replace(
                /,/g,
                ""
            )
            .match(
                /-?\d+(?:\.\d+)?/
            );


        if (!match) {
            return null;
        }


        const number =
            Number(
                match[0]
            );


        return Number.isFinite(number)
            ?
            number
            :
            null;
    }


    function v83Metric(region,label){

        const wanted =
            String(label)
            .trim()
            .toUpperCase();


        const items =
            region.querySelectorAll(
                ".rp-sim-item"
            );


        for (const item of items) {

            const heading =
                item.querySelector(
                    ":scope > span"
                );


            const value =
                item.querySelector(
                    ":scope > strong"
                );


            if (!heading || !value) {
                continue;
            }


            if (
                heading.textContent
                .trim()
                .toUpperCase()
                ===
                wanted
            ) {

                return v83Number(
                    value.textContent
                );
            }
        }


        return null;
    }


    function v83FirstMetric(region,names){

        for (const name of names) {

            const value =
                v83Metric(
                    region,
                    name
                );


            if (value !== null) {
                return value;
            }
        }


        return null;
    }


    function v83Format(value,decimals){

        if (value === null) {
            return "—";
        }


        return Number(value)
            .toLocaleString(
                undefined,
                {
                    minimumFractionDigits:
                        decimals,

                    maximumFractionDigits:
                        decimals
                }
            );
    }


    /* ========================================================
       STATUS CALCULATIONS
       ======================================================== */

    function v83FpsState(value){

        if (value === null) {
            return "info";
        }


        if (value < 20) {
            return "critical";
        }


        if (value < 40) {
            return "warning";
        }


        return "good";
    }


    function v83DilationState(value){

        if (value === null) {
            return "info";
        }


        if (value < .80) {
            return "critical";
        }


        if (value < .95) {
            return "warning";
        }


        return "good";
    }


    function v83FrameState(value){

        if (value === null) {
            return "info";
        }


        if (value >= 50) {
            return "critical";
        }


        if (value >= 30) {
            return "warning";
        }


        return "good";
    }


    function v83ScriptCountState(value){

        if (value === null) {
            return "info";
        }


        if (value >= 15000) {
            return "critical";
        }


        if (value >= 12000) {
            return "warning";
        }


        return "good";
    }


    function v83ScriptEventState(value){

        if (value === null) {
            return "info";
        }


        if (value >= 1000) {
            return "critical";
        }


        if (value >= 500) {
            return "warning";
        }


        return "good";
    }


    function v83Worst(states){

        if (
            states.includes(
                "critical"
            )
        ) {

            return "critical";
        }


        if (
            states.includes(
                "warning"
            )
        ) {

            return "warning";
        }


        if (
            states.every(
                function(state){

                    return state ===
                        "info";
                }
            )
        ) {

            return "info";
        }


        return "good";
    }


    function v83HealthLabel(state){

        if (state === "critical") {
            return "CRITICAL";
        }


        if (state === "warning") {
            return "WARNING";
        }


        if (state === "good") {
            return "GOOD";
        }


        return "NO DATA";
    }


    /* ========================================================
       ITEM HTML
       ======================================================== */

    function v83Item(
        label,
        value,
        sub,
        state,
        extraClass
    ){

        const item =
            document.createElement(
                "div"
            );


        item.className =
            "rp-v83-health-item "
            +
            "rp-v83-"
            +
            state
            +
            (
                extraClass
                    ?
                    " " + extraClass
                    :
                    ""
            );


        const heading =
            document.createElement(
                "span"
            );


        heading.className =
            "rp-v83-health-label";


        heading.textContent =
            label;


        const valueLine =
            document.createElement(
                "div"
            );


        valueLine.className =
            "rp-v83-health-value";


        const dot =
            document.createElement(
                "i"
            );


        dot.className =
            "rp-v83-dot";


        const valueText =
            document.createElement(
                "span"
            );


        valueText.textContent =
            value;


        valueLine.appendChild(
            dot
        );


        valueLine.appendChild(
            valueText
        );


        const subText =
            document.createElement(
                "span"
            );


        subText.className =
            "rp-v83-health-sub";


        subText.textContent =
            sub;


        item.appendChild(
            heading
        );


        item.appendChild(
            valueLine
        );


        item.appendChild(
            subText
        );


        return item;
    }


    /* ========================================================
       FIND REAL V7.5 DASHBOARD
       ======================================================== */

    function v83Dashboard(region){

        const card =
            region.querySelector(
                ".rp-v75-card"
            );


        if (!card) {
            return null;
        }


        return card.parentElement;
    }


    /* ========================================================
       BUILD / UPDATE ONE REGION
       ======================================================== */

    function v83Region(region){

        const dashboard =
            v83Dashboard(
                region
            );


        if (!dashboard) {
            return;
        }


        const section =
            dashboard.parentElement;


        if (!section) {
            return;
        }


        const sim =
            v83Metric(
                region,
                "SIM FPS"
            );


        const physics =
            v83Metric(
                region,
                "PHYSICS FPS"
            );


        const dilation =
            v83Metric(
                region,
                "TIME DILATION"
            );


        const frame =
            v83Metric(
                region,
                "FRAME TIME"
            );


        const activeScripts =
            v83Metric(
                region,
                "ACTIVE SCRIPTS"
            );


        const scriptEvents =
            v83Metric(
                region,
                "SCRIPT EVENTS/S"
            );


        const rootAgents =
            v83FirstMetric(
                region,
                [
                    "ROOT AGENTS",
                    "ROOT AGENT"
                ]
            );


        const childAgents =
            v83FirstMetric(
                region,
                [
                    "CHILD AGENTS",
                    "CHILD AGENT"
                ]
            );


        const npcAgents =
            v83FirstMetric(
                region,
                [
                    "NPC AGENTS",
                    "NPC AGENT",
                    "NPCS"
                ]
            );


        const simState =
            v83FpsState(
                sim
            );


        const physicsState =
            v83FpsState(
                physics
            );


        const dilationState =
            v83DilationState(
                dilation
            );


        const frameState =
            v83FrameState(
                frame
            );


        const activeState =
            v83ScriptCountState(
                activeScripts
            );


        const eventState =
            v83ScriptEventState(
                scriptEvents
            );


        const overallState =
            v83Worst(
                [
                    simState,
                    physicsState,
                    dilationState,
                    frameState,
                    activeState,
                    eventState
                ]
            );


        let strip =
            section.querySelector(
                ":scope > .rp-v83-health-strip"
            );


        if (!strip) {

            strip =
                document.createElement(
                    "div"
                );


            strip.className =
                "rp-v83-health-strip";


            section.insertBefore(
                strip,
                dashboard
            );
        }


        /*
         * Rebuild the six very small cells.
         */

        strip.replaceChildren();


        strip.appendChild(
            v83Item(
                "REGION HEALTH",
                v83HealthLabel(
                    overallState
                ),
                "Live simulator status",
                overallState,
                "rp-v83-overall"
            )
        );


        strip.appendChild(
            v83Item(
                "TIME DILATION",
                dilation === null
                    ?
                    "—"
                    :
                    v83Format(
                        dilation,
                        2
                    ),
                "Ideal 1.00",
                dilationState,
                ""
            )
        );


        strip.appendChild(
            v83Item(
                "FRAME TIME",
                frame === null
                    ?
                    "—"
                    :
                    v83Format(
                        frame,
                        1
                    )
                    +
                    " ms",
                "Simulator frame processing",
                frameState,
                ""
            )
        );


        const root =
            rootAgents === null
                ?
                0
                :
                rootAgents;


        const child =
            childAgents === null
                ?
                0
                :
                childAgents;


        const npc =
            npcAgents === null
                ?
                0
                :
                npcAgents;


        strip.appendChild(
            v83Item(
                "AGENTS",
                "R "
                +
                root
                +
                "  /  C "
                +
                child
                +
                "  /  N "
                +
                npc,
                "Root / Child / NPC",
                "info",
                ""
            )
        );


        strip.appendChild(
            v83Item(
                "ACTIVE SCRIPTS",
                activeScripts === null
                    ?
                    "—"
                    :
                    Math.round(
                        activeScripts
                    ).toLocaleString(),
                "Running scripts",
                activeState,
                ""
            )
        );


        strip.appendChild(
            v83Item(
                "SCRIPT EVENTS",
                scriptEvents === null
                    ?
                    "—"
                    :
                    v83Format(
                        scriptEvents,
                        0
                    )
                    +
                    " /s",
                "Current event rate",
                eventState,
                ""
            )
        );
    }


    /* ========================================================
       UPDATE ALL REGIONS
       ======================================================== */

    let v83Busy =
        false;


    function v83Update(){

        if (v83Busy) {
            return;
        }


        v83Busy =
            true;


        try {

            document
                .querySelectorAll(
                    "article"
                )
                .forEach(
                    v83Region
                );
        }
        finally {

            v83Busy =
                false;
        }
    }


    /* ========================================================
       INITIAL LOAD
       ======================================================== */

    if (
        document.readyState
        ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            v83Update,
            {
                once:true
            }
        );
    }
    else {

        v83Update();
    }


    /*
     * Region cards are rebuilt by the normal two-second
     * refresh, so automatically restore/update the strip.
     */

    let v83Scheduled =
        false;


    const v83Observer =
        new MutationObserver(
            function(){

                if (
                    v83Busy
                    ||
                    v83Scheduled
                ) {

                    return;
                }


                v83Scheduled =
                    true;


                requestAnimationFrame(
                    function(){

                        v83Scheduled =
                            false;

                        v83Update();
                    }
                );
            }
        );


    v83Observer.observe(
        document.body,
        {
            childList:true,
            subtree:true
        }
    );


    window.setInterval(
        v83Update,
        2100
    );


    window.rpV83UpdateHealth =
        v83Update;

})();

</script>

<!-- AUSTRALIA REGIONS V8.3 END -->

<!-- AUSTRALIA REGIONS V8.4C START -->

<style>

/* ============================================================
   V8.4C - STABLE REGION DETAILS
   ============================================================ */

article.rp-region {
    align-self: start !important;
    height: auto !important;
}


/* ============================================================
   DETAILS BUTTON
   ============================================================ */

.rp-v84c-details-button {
    border-color: rgba(205, 164, 70, .62) !important;
    color: #e7c768 !important;
    background: linear-gradient(180deg, rgba(55, 48, 27, .96), rgba(25, 24, 19, .96)) !important;
}

.rp-v84c-details-button:hover {
    border-color: rgba(231, 199, 104, .92) !important;
    color: #fff0b0 !important;
    box-shadow: 0 0 12px rgba(205, 164, 70, .22) !important;
}

.rp-v84c-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 2147483000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    box-sizing: border-box;
    background: rgba(4, 5, 6, .82);
    backdrop-filter: blur(5px);
}

.rp-v84c-modal {
    width: min(1080px, calc(100vw - 32px));
    max-height: min(860px, calc(100vh - 48px));
    overflow: auto;
    border: 1px solid rgba(205, 164, 70, .66);
    border-radius: 9px;
    background: linear-gradient(180deg, #1b1d1f 0%, #131516 100%);
    color: #dedede;
    box-shadow: 0 30px 90px rgba(0, 0, 0, .78), 0 0 28px rgba(205, 164, 70, .08);
}

.rp-v84c-header {
    min-height: 62px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 14px 17px;
    box-sizing: border-box;
    border-bottom: 1px solid rgba(205, 164, 70, .28);
    background: linear-gradient(180deg, #232426, #191b1c);
}

.rp-v84c-heading { min-width: 0; }
.rp-v84c-heading strong { display: block; color: #e2bd5b; font-size: 13px; font-weight: 900; letter-spacing: .08em; }
.rp-v84c-heading small { display: block; margin-top: 4px; color: #91866b; font-size: 10px; }
.rp-v84c-header-actions { display: flex; align-items: center; gap: 8px; }

.rp-v84c-copy,
.rp-v84c-close,
.rp-v84c-modal-action {
    min-height: 31px;
    border: 1px solid rgba(205, 164, 70, .48);
    border-radius: 4px;
    background: linear-gradient(180deg, #2d2b24, #1b1b18);
    color: #dfbe65;
    font-size: 9px;
    font-weight: 900;
    letter-spacing: .06em;
    cursor: pointer;
}

.rp-v84c-copy { padding: 0 11px; }
.rp-v84c-close { width: 34px; padding: 0; font-size: 20px; line-height: 1; }
.rp-v84c-copy:hover,
.rp-v84c-close:hover,
.rp-v84c-modal-action:hover {
    border-color: rgba(231, 199, 104, .9);
    color: #fff0b0;
    background: linear-gradient(180deg, #383326, #23211b);
}

.rp-v84c-groups {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    padding: 15px;
}

.rp-v84c-group {
    min-width: 0;
    overflow: hidden;
    border: 1px solid rgba(205, 164, 70, .20);
    border-radius: 6px;
    background: #101213;
}

.rp-v84c-group-title {
    min-height: 35px;
    display: flex;
    align-items: center;
    padding: 0 11px;
    border-bottom: 1px solid rgba(205, 164, 70, .20);
    background: #1d1e1f;
    color: #cda446;
    font-size: 9px;
    font-weight: 900;
    letter-spacing: .08em;
}

.rp-v84c-row {
    min-height: 35px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 0 11px;
    border-bottom: 1px solid rgba(255, 255, 255, .045);
    box-sizing: border-box;
}

.rp-v84c-row:last-child { border-bottom: 0; }
.rp-v84c-label { min-width: 0; color: #817c70; font-size: 9px; font-weight: 700; letter-spacing: .04em; white-space: nowrap; }
.rp-v84c-value { flex: 0 0 auto; max-width: 62%; overflow: hidden; color: #dedede; font-size: 10px; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
.rp-v84c-value.good { color: #64e990; }
.rp-v84c-value.warning { color: #ffc34b; }
.rp-v84c-value.critical { color: #ff6373; }
.rp-v84c-value.info { color: #e2bd5b; }

.rp-v84c-health-panel {
    margin: 15px 15px 0;
    overflow: hidden;
    border: 1px solid rgba(205, 164, 70, .24);
    border-radius: 6px;
    background: #101213;
}

.rp-v84c-health-panel.good {
    border-color: rgba(100, 233, 144, .28);
}

.rp-v84c-health-panel.warning {
    border-color: rgba(255, 195, 75, .42);
}

.rp-v84c-health-panel.critical {
    border-color: rgba(255, 99, 115, .46);
}

.rp-v84c-health-head {
    min-height: 52px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 10px 12px;
    box-sizing: border-box;
    border-bottom: 1px solid rgba(205, 164, 70, .16);
    background: linear-gradient(180deg, #202123, #181a1b);
}

.rp-v84c-health-heading {
    min-width: 0;
}

.rp-v84c-health-heading strong {
    display: block;
    color: #cda446;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .08em;
}

.rp-v84c-health-heading small {
    display: block;
    margin-top: 4px;
    color: #8c8678;
    font-size: 9px;
}

.rp-v84c-health-badge {
    flex: 0 0 auto;
    min-width: 78px;
    padding: 6px 9px;
    border: 1px solid rgba(205, 164, 70, .30);
    border-radius: 4px;
    background: #18191a;
    color: #b6b0a3;
    font-size: 9px;
    font-weight: 900;
    letter-spacing: .07em;
    text-align: center;
}

.rp-v84c-health-badge.good {
    border-color: rgba(100, 233, 144, .36);
    color: #64e990;
}

.rp-v84c-health-badge.warning {
    border-color: rgba(255, 195, 75, .45);
    color: #ffc34b;
}

.rp-v84c-health-badge.critical {
    border-color: rgba(255, 99, 115, .48);
    color: #ff6373;
}

.rp-v84c-health-badge.unknown {
    color: #aaa59b;
}

.rp-v84c-health-body {
    padding: 8px;
}

.rp-v84c-health-alert {
    min-height: 36px;
    display: grid;
    grid-template-columns: 78px minmax(0, 1fr) auto;
    align-items: center;
    gap: 10px;
    padding: 6px 8px;
    box-sizing: border-box;
    border-bottom: 1px solid rgba(255, 255, 255, .045);
}

.rp-v84c-health-alert:last-child {
    border-bottom: 0;
}

.rp-v84c-health-severity {
    font-size: 8px;
    font-weight: 900;
    letter-spacing: .07em;
}

.rp-v84c-health-alert.warning .rp-v84c-health-severity,
.rp-v84c-health-alert.warning .rp-v84c-health-alert-value {
    color: #ffc34b;
}

.rp-v84c-health-alert.critical .rp-v84c-health-severity,
.rp-v84c-health-alert.critical .rp-v84c-health-alert-value {
    color: #ff6373;
}

.rp-v84c-health-alert-label {
    min-width: 0;
    overflow: hidden;
    color: #aaa396;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .04em;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rp-v84c-health-alert-value {
    max-width: 280px;
    overflow: hidden;
    font-size: 10px;
    font-weight: 900;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rp-v84c-health-clear {
    padding: 8px;
    color: #aaa59b;
    font-size: 9px;
    font-weight: 700;
}

.rp-v84c-health-clear.good {
    color: #64e990;
}

.rp-v84c-health-note {
    padding: 7px 12px;
    border-top: 1px solid rgba(205, 164, 70, .12);
    background: #151718;
    color: #716d64;
    font-size: 8px;
    letter-spacing: .035em;
}
.rp-v84c-modal-actions {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 8px;
    padding: 0 15px 15px;
}

.rp-v84c-modal-action { min-width: 104px; padding: 0 12px; }
.rp-v84c-modal-close { border-color: rgba(205, 164, 70, .72); }

.rp-v84c-footer {
    min-height: 34px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 0 15px;
    border-top: 1px solid rgba(205, 164, 70, .18);
    background: #111314;
    color: #746f63;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: .06em;
}

@media (max-width: 700px) {

    .rp-v84c-health-panel {
        margin: 10px 10px 0;
    }

    .rp-v84c-health-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .rp-v84c-health-alert {
        grid-template-columns: 1fr;
        gap: 4px;
    }

    .rp-v84c-health-alert-value {
        max-width: 100%;
    }
}
@media (max-width: 1050px) {
    .rp-v84c-groups { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 700px) {
    .rp-v84c-modal-backdrop { padding: 10px; }
    .rp-v84c-modal { width: calc(100vw - 20px); max-height: calc(100vh - 20px); }
    .rp-v84c-header { align-items: flex-start; flex-direction: column; }
    .rp-v84c-header-actions { width: 100%; justify-content: space-between; }
    .rp-v84c-groups { grid-template-columns: 1fr; }
    .rp-v84c-modal-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rp-v84c-modal-action { width: 100%; }
}


</style>


<script>

(function(){

    "use strict";


    window.rpV84COpen =
        window.rpV84COpen
        ||
        Object.create(null);


    function clean(value){

        return String(
            value ?? ""
        )
        .replace(
            /\s+/g,
            " "
        )
        .trim();
    }


    function regionName(article){

        const button =
            article.querySelector(
                '[data-command="SaveOAR"][data-region]'
            )
            ||
            article.querySelector(
                '[data-region]'
            );


        if (!button) {
            return "";
        }


        return clean(
            button.dataset.region
        );
    }


    function regionPort(article){

        const elements =
            article.querySelectorAll(
                "span"
            );


        for (const element of elements) {

            const text =
                clean(
                    element.textContent
                );


            const match =
                text.match(
                    /^PORT\s+(\d+)$/i
                );


            if (match) {
                return match[1];
            }
        }


        return "—";
    }


    function regionPid(article){

        const element =
            article.querySelector(
                ".rp-process-id"
            );


        if (!element) {
            return "—";
        }


        const match =
            clean(
                element.textContent
            )
            .match(
                /PID\s+(\d+)/i
            );


        return match
            ?
            match[1]
            :
            "—";
    }


    function regionStatus(article){

        const element =
            article.querySelector(
                ".rp-status strong"
            );


        return element
            ?
            clean(
                element.textContent
            )
            :
            "—";
    }


    function processStatus(article){

        const element =
            article.querySelector(
                ".rp-process-state"
            );


        return element
            ?
            clean(
                element.textContent
            )
            :
            "PROCESS INACTIVE";
    }


    function regionHealth(article){

        const element =
            article.querySelector(
                ".rp-health strong"
            );


        return element
            ?
            clean(
                element.textContent
            )
            :
            "—";
    }


    function collectMetrics(
        article,
        selector
    ){

        const rows =
            [];


        article
            .querySelectorAll(
                selector
            )
            .forEach(
                function(item){

                    const label =
                        item.querySelector(
                            ":scope > span"
                        );


                    const value =
                        item.querySelector(
                            ":scope > strong"
                        );


                    if (!label || !value) {
                        return;
                    }


                    const labelText =
                        clean(
                            label.textContent
                        );


                    const valueText =
                        clean(
                            value.textContent
                        );


                    if (!labelText) {
                        return;
                    }


                    rows.push(
                        {
                            label:
                                labelText,

                            value:
                                valueText || "—"
                        }
                    );
                }
            );


        return rows;
    }


    function numberValue(value){

        const match =
            String(
                value ?? ""
            )
            .replace(
                /,/g,
                ""
            )
            .match(
                /-?\d+(?:\.\d+)?/
            );


        if (!match) {
            return null;
        }


        const number =
            Number(
                match[0]
            );


        return Number.isFinite(number)
            ?
            number
            :
            null;
    }


    function valueState(
        label,
        value
    ){

        const key =
            clean(
                label
            )
            .toUpperCase();


        const number =
            numberValue(
                value
            );


        if (number === null) {
            return "";
        }


        if (
            key === "SIM FPS"
            ||
            key === "PHYSICS FPS"
        ) {

            if (number < 20) {
                return "critical";
            }

            if (number < 40) {
                return "warning";
            }

            return "good";
        }


        if (key === "TIME DILATION") {

            if (number < .80) {
                return "critical";
            }

            if (number < .95) {
                return "warning";
            }

            return "good";
        }


        if (key === "FRAME TIME") {

            if (number >= 50) {
                return "critical";
            }

            if (number >= 30) {
                return "warning";
            }

            return "good";
        }


        if (key === "ACTIVE SCRIPTS") {

            if (number >= 15000) {
                return "critical";
            }

            if (number >= 12000) {
                return "warning";
            }

            return "good";
        }


        if (key === "SCRIPT EVENTS/S") {

            if (number >= 1000) {
                return "critical";
            }

            if (number >= 500) {
                return "warning";
            }

            return "good";
        }


        return "";
    }


    function createRow(
        label,
        value,
        state
    ){

        const row =
            document.createElement(
                "div"
            );


        row.className =
            "rp-v84c-row";


        const left =
            document.createElement(
                "span"
            );


        left.className =
            "rp-v84c-label";


        left.textContent =
            label;


        const right =
            document.createElement(
                "strong"
            );


        right.className =
            "rp-v84c-value";


        if (state) {

            right.classList.add(
                state
            );
        }


        right.textContent =
            value || "—";


        row.appendChild(
            left
        );


        row.appendChild(
            right
        );


        return row;
    }


    function createGroup(
        title,
        rows
    ){

        const group =
            document.createElement(
                "section"
            );


        group.className =
            "rp-v84c-group";


        const heading =
            document.createElement(
                "div"
            );


        heading.className =
            "rp-v84c-group-title";


        heading.textContent =
            title;


        group.appendChild(
            heading
        );


        rows.forEach(
            function(row){

                group.appendChild(
                    createRow(
                        row.label,
                        row.value,
                        row.state
                        ||
                        valueState(
                            row.label,
                            row.value
                        )
                    )
                );
            }
        );


        return group;
    }


    function diagnosticText(article){

        const name =
            regionName(
                article
            );


        const process =
            collectMetrics(
                article,
                ".rp-performance-item"
            );


        const simulator =
            collectMetrics(
                article,
                ".rp-sim-item"
            );


        const lines =
            [
                "REGION DIAGNOSTICS",
                "========================================",
                "Region: " + name,
                "State: " + regionStatus(article),
                "Process: " + processStatus(article),
                "Health: " + regionHealth(article),
                "PID: " + regionPid(article),
                "Port: " + regionPort(article),
                "",
                "WINDOWS PROCESS",
                "----------------------------------------"
            ];


        process.forEach(
            function(item){

                lines.push(
                    item.label
                    +
                    ": "
                    +
                    item.value
                );
            }
        );


        lines.push(
            "",
            "OPENSIM TELEMETRY",
            "----------------------------------------"
        );


        simulator.forEach(
            function(item){

                lines.push(
                    item.label
                    +
                    ": "
                    +
                    item.value
                );
            }
        );


        lines.push(
            "",
            "Captured: "
            +
            new Date()
                .toLocaleString()
        );


        return lines.join(
            "\n"
        );
    }


    async function copyDiagnostics(
        article,
        button
    ){

        const text =
            diagnosticText(
                article
            );


        let success =
            false;


        try {

            if (
                navigator.clipboard
                &&
                navigator.clipboard.writeText
            ) {

                await navigator.clipboard
                    .writeText(
                        text
                    );


                success =
                    true;
            }
        }
        catch (error) {

            success =
                false;
        }


        if (!success) {

            const area =
                document.createElement(
                    "textarea"
                );


            area.value =
                text;


            area.style.position =
                "fixed";


            area.style.left =
                "-9999px";


            document.body.appendChild(
                area
            );


            area.select();


            try {

                success =
                    document.execCommand(
                        "copy"
                    );
            }
            catch (error) {

                success =
                    false;
            }


            area.remove();
        }


        button.textContent =
            success
                ?
                "COPIED"
                :
                "COPY FAILED";


        window.setTimeout(
            function(){

                button.textContent =
                    "COPY DIAGNOSTICS";
            },
            1400
        );
    }


    function dashboardDocument(){

        try {

            if (
                window.parent
                &&
                window.parent !== window
                &&
                window.parent.document
            ) {

                return window.parent.document;
            }
        }
        catch(error){
        }

        return document;
    }


    function openDashboardView(view){

        const doc =
            dashboardDocument();

        const control =
            doc.querySelector(
                '[data-view="' +
                view +
                '"]'
            );

        if (!control) {
            return false;
        }

        control.click();

        return true;
    }


    function currentRegionArticle(name){

        const wanted =
            clean(
                name
            );

        return Array.from(
            document.querySelectorAll(
                "article.rp-region"
            )
        ).find(
            function(candidate){

                const save =
                    candidate.querySelector(
                        '[data-command="SaveOAR"][data-region]'
                    );

                if (!save) {
                    return false;
                }

                return (
                    clean(
                        save.dataset.region
                    )
                    ===
                    wanted
                );
            }
        ) || null;
    }


    function healthAlertRows(article){

        const alerts =
            [];

        const seen =
            new Set();

        const add =
            function(
                label,
                value,
                state
            ){

                const labelText =
                    clean(
                        label
                    );

                const valueText =
                    clean(
                        value
                    )
                    ||
                    "—";

                const tone =
                    state
                    ||
                    valueState(
                        labelText,
                        valueText
                    );

                if (
                    tone !== "warning"
                    &&
                    tone !== "critical"
                ) {
                    return;
                }

                const signature =
                    labelText +
                    "\u0000" +
                    valueText +
                    "\u0000" +
                    tone;

                if (
                    seen.has(
                        signature
                    )
                ) {
                    return;
                }

                seen.add(
                    signature
                );

                alerts.push(
                    {
                        label:
                            labelText,

                        value:
                            valueText,

                        state:
                            tone
                    }
                );
            };


        const regionState =
            regionStatus(
                article
            );

        add(
            "REGION STATE",
            regionState,
            valueState(
                "STATE",
                regionState
            )
        );


        const processState =
            processStatus(
                article
            );

        add(
            "PROCESS",
            processState,
            valueState(
                "PROCESS",
                processState
            )
        );


        const healthState =
            regionHealth(
                article
            );

        add(
            "HEALTH",
            healthState,
            valueState(
                "HEALTH",
                healthState
            )
        );


        collectMetrics(
            article,
            ".rp-performance-item"
        )
        .forEach(
            function(row){

                add(
                    row.label,
                    row.value,
                    valueState(
                        row.label,
                        row.value
                    )
                );
            }
        );


        collectMetrics(
            article,
            ".rp-sim-item"
        )
        .forEach(
            function(row){

                add(
                    row.label,
                    row.value,
                    valueState(
                        row.label,
                        row.value
                    )
                );
            }
        );


        alerts.sort(
            function(a,b){

                const rankA =
                    a.state === "critical"
                        ?
                        0
                        :
                        1;

                const rankB =
                    b.state === "critical"
                        ?
                        0
                        :
                        1;

                return rankA - rankB;
            }
        );


        return alerts;
    }


    function healthSummary(article){

        const processRows =
            collectMetrics(
                article,
                ".rp-performance-item"
            );

        const simulatorRows =
            collectMetrics(
                article,
                ".rp-sim-item"
            );

        const alerts =
            healthAlertRows(
                article
            );


        if (
            alerts.some(
                function(item){
                    return item.state === "critical";
                }
            )
        ) {

            return {
                state:
                    "critical",

                label:
                    "CRITICAL",

                message:
                    "One or more monitored region metrics are in a critical state.",

                alerts:
                    alerts
            };
        }


        if (
            alerts.some(
                function(item){
                    return item.state === "warning";
                }
            )
        ) {

            return {
                state:
                    "warning",

                label:
                    "WARNING",

                message:
                    "One or more monitored region metrics require attention.",

                alerts:
                    alerts
            };
        }


        if (
            processRows.length === 0
            &&
            simulatorRows.length === 0
        ) {

            return {
                state:
                    "unknown",

                label:
                    "NO DATA",

                message:
                    "Waiting for live process and OpenSim telemetry.",

                alerts:
                    []
            };
        }


        return {
            state:
                "good",

            label:
                "GOOD",

            message:
                "No warning or critical metrics are currently detected.",

            alerts:
                []
        };
    }


    function createHealthPanel(article){

        const summary =
            healthSummary(
                article
            );

        const panel =
            document.createElement(
                "section"
            );

        panel.className =
            "rp-v84c-health-panel " +
            summary.state;


        const head =
            document.createElement(
                "div"
            );

        head.className =
            "rp-v84c-health-head";


        const heading =
            document.createElement(
                "div"
            );

        heading.className =
            "rp-v84c-health-heading";


        const title =
            document.createElement(
                "strong"
            );

        title.textContent =
            "REGION HEALTH / ALERTS";


        const message =
            document.createElement(
                "small"
            );

        message.textContent =
            summary.message;


        heading.appendChild(
            title
        );

        heading.appendChild(
            message
        );


        const badge =
            document.createElement(
                "span"
            );

        badge.className =
            "rp-v84c-health-badge " +
            summary.state;

        badge.textContent =
            summary.label;


        head.appendChild(
            heading
        );

        head.appendChild(
            badge
        );

        panel.appendChild(
            head
        );


        const body =
            document.createElement(
                "div"
            );

        body.className =
            "rp-v84c-health-body";


        if (
            summary.alerts.length
        ) {

            summary.alerts.forEach(
                function(alert){

                    const row =
                        document.createElement(
                            "div"
                        );

                    row.className =
                        "rp-v84c-health-alert " +
                        alert.state;


                    const severity =
                        document.createElement(
                            "span"
                        );

                    severity.className =
                        "rp-v84c-health-severity";

                    severity.textContent =
                        alert.state.toUpperCase();


                    const label =
                        document.createElement(
                            "span"
                        );

                    label.className =
                        "rp-v84c-health-alert-label";

                    label.textContent =
                        alert.label;


                    const value =
                        document.createElement(
                            "strong"
                        );

                    value.className =
                        "rp-v84c-health-alert-value";

                    value.textContent =
                        alert.value;


                    row.appendChild(
                        severity
                    );

                    row.appendChild(
                        label
                    );

                    row.appendChild(
                        value
                    );

                    body.appendChild(
                        row
                    );
                }
            );
        }
        else {

            const quiet =
                document.createElement(
                    "div"
                );

            quiet.className =
                "rp-v84c-health-clear " +
                summary.state;

            quiet.textContent =
                summary.message;

            body.appendChild(
                quiet
            );
        }


        panel.appendChild(
            body
        );


        const note =
            document.createElement(
                "div"
            );

        note.className =
            "rp-v84c-health-note";

        note.textContent =
            "Uses the existing live Regions health thresholds.";

        panel.appendChild(
            note
        );


        return panel;
    }


    function renderDetailsModal(
        article,
        modal,
        closeModal
    ){

        const name =
            regionName(
                article
            );

        modal.replaceChildren();

        const header =
            document.createElement(
                "div"
            );

        header.className =
            "rp-v84c-header";

        const heading =
            document.createElement(
                "div"
            );

        heading.className =
            "rp-v84c-heading";

        const title =
            document.createElement(
                "strong"
            );

        title.textContent =
            "REGION DETAILS";

        const subtitle =
            document.createElement(
                "small"
            );

        subtitle.textContent =
            name +
            " • live diagnostic information";

        heading.appendChild(
            title
        );

        heading.appendChild(
            subtitle
        );

        const headerActions =
            document.createElement(
                "div"
            );

        headerActions.className =
            "rp-v84c-header-actions";

        const copy =
            document.createElement(
                "button"
            );

        copy.type =
            "button";

        copy.className =
            "rp-v84c-copy";

        copy.textContent =
            "COPY DIAGNOSTICS";

        copy.addEventListener(
            "click",
            function(){

                copyDiagnostics(
                    article,
                    copy
                );
            }
        );

        const closeTop =
            document.createElement(
                "button"
            );

        closeTop.type =
            "button";

        closeTop.className =
            "rp-v84c-close";

        closeTop.setAttribute(
            "aria-label",
            "Close region details"
        );

        closeTop.textContent =
            "×";

        closeTop.addEventListener(
            "click",
            closeModal
        );

        headerActions.appendChild(
            copy
        );

        headerActions.appendChild(
            closeTop
        );

        header.appendChild(
            heading
        );

        header.appendChild(
            headerActions
        );

        modal.appendChild(
            header
        );


        modal.appendChild(
            createHealthPanel(
                article
            )
        );


        const groups =
            document.createElement(
                "div"
            );

        groups.className =
            "rp-v84c-groups";

        const regionRows =
            [
                {
                    label:
                        "REGION",

                    value:
                        name,

                    state:
                        "info"
                },

                {
                    label:
                        "STATE",

                    value:
                        regionStatus(
                            article
                        )
                },

                {
                    label:
                        "PROCESS",

                    value:
                        processStatus(
                            article
                        )
                },

                {
                    label:
                        "HEALTH",

                    value:
                        regionHealth(
                            article
                        )
                },

                {
                    label:
                        "PID",

                    value:
                        regionPid(
                            article
                        )
                },

                {
                    label:
                        "HTTP PORT",

                    value:
                        regionPort(
                            article
                        )
                }
            ];

        const processRows =
            collectMetrics(
                article,
                ".rp-performance-item"
            );

        const simulatorRows =
            collectMetrics(
                article,
                ".rp-sim-item"
            );

        groups.appendChild(
            createGroup(
                "REGION / PROCESS",
                regionRows
            )
        );

        groups.appendChild(
            createGroup(
                "WINDOWS PROCESS",
                processRows.length
                    ?
                    processRows
                    :
                    [
                        {
                            label:
                                "STATUS",

                            value:
                                "Waiting for process data"
                        }
                    ]
            )
        );

        groups.appendChild(
            createGroup(
                "OPENSIM TELEMETRY",
                simulatorRows.length
                    ?
                    simulatorRows
                    :
                    [
                        {
                            label:
                                "STATUS",

                            value:
                                "Waiting for telemetry"
                        }
                    ]
            )
        );

        modal.appendChild(
            groups
        );

        const actions =
            document.createElement(
                "div"
            );

        actions.className =
            "rp-v84c-modal-actions";

        const mapButton =
            document.createElement(
                "button"
            );

        mapButton.type =
            "button";

        mapButton.className =
            "rp-v84c-modal-action";

        mapButton.textContent =
            "OPEN MAP";

        mapButton.addEventListener(
            "click",
            function(){

                if (openDashboardView("map")) {
                    closeModal();
                }
            }
        );

        const map3dButton =
            document.createElement(
                "button"
            );

        map3dButton.type =
            "button";

        map3dButton.className =
            "rp-v84c-modal-action";

        map3dButton.textContent =
            "3D MAP";

        map3dButton.addEventListener(
            "click",
            function(){

                if (openDashboardView("3d-map")) {
                    closeModal();
                }
            }
        );

        const refreshButton =
            document.createElement(
                "button"
            );

        refreshButton.type =
            "button";

        refreshButton.className =
            "rp-v84c-modal-action";

        refreshButton.textContent =
            "REFRESH";

        refreshButton.addEventListener(
            "click",
            function(){

                const current =
                    currentRegionArticle(
                        name
                    )
                    ||
                    article;

                renderDetailsModal(
                    current,
                    modal,
                    closeModal
                );
            }
        );

        const closeButton =
            document.createElement(
                "button"
            );

        closeButton.type =
            "button";

        closeButton.className =
            "rp-v84c-modal-action rp-v84c-modal-close";

        closeButton.textContent =
            "CLOSE";

        closeButton.addEventListener(
            "click",
            closeModal
        );

        actions.appendChild(
            mapButton
        );

        actions.appendChild(
            map3dButton
        );

        actions.appendChild(
            refreshButton
        );

        actions.appendChild(
            closeButton
        );

        modal.appendChild(
            actions
        );

        const footer =
            document.createElement(
                "div"
            );

        footer.className =
            "rp-v84c-footer";

        const footerLeft =
            document.createElement(
                "span"
            );

        footerLeft.textContent =
            "LIVE DETAILS";

        const footerRight =
            document.createElement(
                "span"
            );

        footerRight.textContent =
            "UPDATED " +
            new Date()
                .toLocaleTimeString();

        footer.appendChild(
            footerLeft
        );

        footer.appendChild(
            footerRight
        );

        modal.appendChild(
            footer
        );
    }


    function openDetailsModal(article){

        if (!article) {
            return;
        }

        const existing =
            document.querySelector(
                ".rp-v84c-modal-backdrop"
            );

        if (existing) {
            existing.remove();
        }

        const backdrop =
            document.createElement(
                "div"
            );

        backdrop.className =
            "rp-v84c-modal-backdrop";

        const modal =
            document.createElement(
                "section"
            );

        modal.className =
            "rp-v84c-modal";

        modal.setAttribute(
            "role",
            "dialog"
        );

        modal.setAttribute(
            "aria-modal",
            "true"
        );

        modal.setAttribute(
            "aria-label",
            "Region details"
        );

        let closed =
            false;

        const onKeyDown =
            function(event){

                if (event.key === "Escape") {
                    closeModal();
                }
            };

        const closeModal =
            function(){

                if (closed) {
                    return;
                }

                closed =
                    true;

                document.removeEventListener(
                    "keydown",
                    onKeyDown
                );

                backdrop.remove();
            };

        backdrop.addEventListener(
            "click",
            function(event){

                if (event.target === backdrop) {
                    closeModal();
                }
            }
        );

        document.addEventListener(
            "keydown",
            onKeyDown
        );

        backdrop.appendChild(
            modal
        );

        document.body.appendChild(
            backdrop
        );

        renderDetailsModal(
            article,
            modal,
            closeModal
        );
    }


    function decorateRegion(article){

        if (
            !article
            ||
            !article.matches(
                "article.rp-region"
            )
        ) {

            return;
        }

        if (
            article.dataset.v84cDecorated
            ===
            "1"
        ) {

            return;
        }

        const saveButton =
            article.querySelector(
                '[data-command="SaveOAR"][data-region]'
            );

        if (!saveButton) {
            return;
        }

        const name =
            clean(
                saveButton.dataset.region
            );

        if (!name) {
            return;
        }

        const actions =
            saveButton.parentElement;

        if (!actions) {
            return;
        }

        article.dataset.v84cDecorated =
            "1";

        const button =
            document.createElement(
                "button"
            );

        button.type =
            "button";

        button.className =
            "rp-action rp-v84c-details-button";

        button.textContent =
            "MORE DETAILS";

        button.setAttribute(
            "aria-haspopup",
            "dialog"
        );

        actions.appendChild(
            button
        );

        button.addEventListener(
            "click",
            function(){

                const current =
                    currentRegionArticle(
                        name
                    )
                    ||
                    article;

                openDetailsModal(
                    current
                );
            }
        );
    }



    function decorateExisting(){

        document
            .querySelectorAll(
                "article.rp-region"
            )
            .forEach(
                decorateRegion
            );
    }


    if (
        document.readyState
        ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            decorateExisting,
            {
                once:true
            }
        );
    }
    else {

        decorateExisting();
    }


    const observer =
        new MutationObserver(
            function(mutations){

                const newArticles =
                    new Set();


                mutations.forEach(
                    function(mutation){

                        mutation.addedNodes
                            .forEach(
                                function(node){

                                    if (
                                        !node
                                        ||
                                        node.nodeType !== 1
                                    ) {

                                        return;
                                    }


                                    if (
                                        node.matches
                                        &&
                                        node.matches(
                                            "article.rp-region"
                                        )
                                    ) {

                                        newArticles.add(
                                            node
                                        );
                                    }


                                    if (
                                        node.querySelectorAll
                                    ) {

                                        node
                                            .querySelectorAll(
                                                "article.rp-region"
                                            )
                                            .forEach(
                                                function(article){

                                                    newArticles.add(
                                                        article
                                                    );
                                                }
                                            );
                                    }
                                }
                            );
                    }
                );


                newArticles.forEach(
                    decorateRegion
                );
            }
        );


    observer.observe(
        document.body,
        {
            childList:
                true,

            subtree:
                true
        }
    );


    window.rpV84CDecorateExisting =
        decorateExisting;

})();

</script>

<!-- AUSTRALIA REGIONS V8.4C END -->

<!-- AUSTRALIA REGIONS V8.5A START -->

<style>

.rp-v85-backup {
    flex: 0 0 auto !important;

    min-width: 205px;

    margin-left: auto;
    margin-right: 14px;

    padding: 7px 11px;

    border:
        1px solid
        rgba(66, 95, 106, .30);

    border-radius: 5px;

    background:
        rgba(5, 14, 18, .76);

    box-sizing: border-box;
}


.rp-v85-main {
    display: flex;
    align-items: center;

    gap: 6px;

    min-width: 0;
}


.rp-v85-dot {
    flex: 0 0 auto;

    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: #627985;
}


.rp-v85-label {
    color: #718793;

    font-size: 8px;
    font-weight: 800;

    letter-spacing: .07em;

    white-space: nowrap;
}


.rp-v85-age {
    margin-left: auto;

    color: #d8e5ea;

    font-size: 10px;
    font-weight: 850;

    white-space: nowrap;
}


.rp-v85-sub {
    display: block;

    margin-top: 4px;

    color: #607783;

    font-size: 8px;
    font-weight: 650;

    text-align: right;

    white-space: nowrap;
}


.rp-v85-backup.good {
    border-color:
        rgba(47, 190, 102, .34);
}

.rp-v85-backup.good .rp-v85-dot {
    background: #42e47c;

    box-shadow:
        0 0 7px
        rgba(66, 228, 124, .72);
}

.rp-v85-backup.good .rp-v85-age {
    color: #69e895;
}


.rp-v85-backup.warning {
    border-color:
        rgba(224, 165, 48, .40);
}

.rp-v85-backup.warning .rp-v85-dot {
    background: #ffc04a;

    box-shadow:
        0 0 7px
        rgba(255, 192, 74, .68);
}

.rp-v85-backup.warning .rp-v85-age {
    color: #ffc85c;
}


.rp-v85-backup.critical {
    border-color:
        rgba(230, 70, 84, .45);
}

.rp-v85-backup.critical .rp-v85-dot {
    background: #ff5364;

    box-shadow:
        0 0 8px
        rgba(255, 83, 100, .72);
}

.rp-v85-backup.critical .rp-v85-age {
    color: #ff6978;
}


.rp-v85-backup.unknown .rp-v85-age {
    color: #8297a0;
}


@media (max-width: 950px) {

    .rp-v85-backup {
        min-width: 170px;

        margin-right: 8px;
    }
}


@media (max-width: 700px) {

    .rp-v85-backup {
        width: 100%;

        margin: 8px 0;
    }
}

</style>


<script>

(function(){

    "use strict";


    window.rpV85BackupMap =
        window.rpV85BackupMap
        ||
        Object.create(null);


    window.rpV85BackupError =
        "";


    function regionKey(value){

        let key =
            String(
                value ?? ""
            )
            .trim()
            .toLowerCase()
            .replace(
                /[^a-z0-9]+/g,
                ""
            );


        if (
            key ===
            "officalregion"
        ) {

            key =
                "officialregion";
        }


        return key;
    }


    function escHtml(value){

        return String(
            value ?? ""
        )
        .replace(
            /&/g,
            "&amp;"
        )
        .replace(
            /</g,
            "&lt;"
        )
        .replace(
            />/g,
            "&gt;"
        )
        .replace(
            /"/g,
            "&quot;"
        )
        .replace(
            /'/g,
            "&#039;"
        );
    }


    function fileSize(bytes){

        const value =
            Number(
                bytes
            );


        if (
            !Number.isFinite(value)
            ||
            value <= 0
        ) {

            return "0 B";
        }


        if (
            value >=
            1073741824
        ) {

            return (
                (
                    value /
                    1073741824
                )
                .toFixed(2)
                +
                " GB"
            );
        }


        if (
            value >=
            1048576
        ) {

            return (
                (
                    value /
                    1048576
                )
                .toFixed(1)
                +
                " MB"
            );
        }


        if (
            value >=
            1024
        ) {

            return (
                (
                    value /
                    1024
                )
                .toFixed(1)
                +
                " KB"
            );
        }


        return (
            Math.round(value)
            +
            " B"
        );
    }


    function ageText(seconds){

        const value =
            Math.max(
                0,
                Number(seconds)
                ||
                0
            );


        if (value < 60) {
            return "JUST NOW";
        }


        if (value < 3600) {

            return (
                Math.floor(
                    value /
                    60
                )
                +
                " MIN AGO"
            );
        }


        if (value < 86400) {

            return (
                Math.floor(
                    value /
                    3600
                )
                +
                " H AGO"
            );
        }


        const days =
            Math.floor(
                value /
                86400
            );


        return (
            days
            +
            (
                days === 1
                    ?
                    " DAY AGO"
                    :
                    " DAYS AGO"
            )
        );
    }


    function statusState(row){

        if (
            !row
            ||
            !row.Available
        ) {

            return "unknown";
        }


        const size =
            Number(
                row.SizeBytes
            )
            ||
            0;


        if (size <= 0) {
            return "critical";
        }


        const age =
            Number(
                row.AgeSeconds
            )
            ||
            0;


        if (age < 86400) {
            return "good";
        }


        if (age < 259200) {
            return "warning";
        }


        return "critical";
    }


    window.rpV85BackupHtml =
        function(regionName){

            const row =
                window.rpV85BackupMap[
                    regionKey(
                        regionName
                    )
                ]
                ||
                null;


            if (!row) {

                if (
                    window.rpV85BackupError
                ) {

                    return (
                        '<div class="rp-v85-main">'
                        +
                        '<i class="rp-v85-dot"></i>'
                        +
                        '<span class="rp-v85-label">LAST OAR</span>'
                        +
                        '<strong class="rp-v85-age">UNAVAILABLE</strong>'
                        +
                        '</div>'
                        +
                        '<small class="rp-v85-sub">'
                        +
                        escHtml(
                            window.rpV85BackupError
                        )
                        +
                        '</small>'
                    );
                }


                return (
                    '<div class="rp-v85-main">'
                    +
                    '<i class="rp-v85-dot"></i>'
                    +
                    '<span class="rp-v85-label">LAST OAR</span>'
                    +
                    '<strong class="rp-v85-age">CHECKING</strong>'
                    +
                    '</div>'
                    +
                    '<small class="rp-v85-sub">DreamGrid backup status</small>'
                );
            }


            if (!row.Available) {

                return (
                    '<div class="rp-v85-main">'
                    +
                    '<i class="rp-v85-dot"></i>'
                    +
                    '<span class="rp-v85-label">LAST OAR</span>'
                    +
                    '<strong class="rp-v85-age">NO OAR</strong>'
                    +
                    '</div>'
                    +
                    '<small class="rp-v85-sub">No DreamGrid backup found</small>'
                );
            }


            const size =
                fileSize(
                    row.SizeBytes
                );


            const age =
                ageText(
                    row.AgeSeconds
                );


            const status =
                String(
                    row.Status
                    ||
                    "OK"
                );


            return (
                '<div class="rp-v85-main">'
                +
                '<i class="rp-v85-dot"></i>'
                +
                '<span class="rp-v85-label">LAST OAR</span>'
                +
                '<strong class="rp-v85-age">'
                +
                escHtml(age)
                +
                '</strong>'
                +
                '</div>'
                +
                '<small class="rp-v85-sub">'
                +
                escHtml(size)
                +
                ' • '
                +
                escHtml(status)
                +
                '</small>'
            );
        };


    function updateCards(){

        document
            .querySelectorAll(
                ".rp-v85-backup[data-oar-region]"
            )
            .forEach(
                function(element){

                    const name =
                        element.dataset.oarRegion
                        ||
                        "";


                    const row =
                        window.rpV85BackupMap[
                            regionKey(name)
                        ]
                        ||
                        null;


                    element.classList.remove(
                        "good",
                        "warning",
                        "critical",
                        "unknown"
                    );


                    element.classList.add(
                        statusState(row)
                    );


                    element.innerHTML =
                        window.rpV85BackupHtml(
                            name
                        );


                    if (
                        row
                        &&
                        row.Available
                    ) {

                        const modified =
                            row.ModifiedIso
                                ?
                                new Date(
                                    row.ModifiedIso
                                )
                                .toLocaleString()
                                :
                                "";


                        element.title =
                            String(
                                row.FileName
                                ||
                                ""
                            )
                            +
                            (
                                modified
                                    ?
                                    "\n" + modified
                                    :
                                    ""
                            );
                    }
                    else {

                        element.title =
                            "";
                    }
                }
            );
    }


    function currentRegionNames(){

        const names =
            new Set();


        document
            .querySelectorAll(
                '[data-command="SaveOAR"][data-region]'
            )
            .forEach(
                function(button){

                    const name =
                        String(
                            button.dataset.region
                            ||
                            ""
                        )
                        .trim();


                    if (name) {
                        names.add(name);
                    }
                }
            );


        return Array.from(
            names
        );
    }


    let loading =
        false;


    async function refreshOarStatus(){

        if (loading) {
            return;
        }


        const names =
            currentRegionNames();


        if (!names.length) {
            return;
        }


        loading =
            true;


        try {

            const url =
                new URL(
                    "/Other/FreshUserDashboardExact/UserPages/region-oar-status.php",
                    window.location.origin
                );


            url.searchParams.set(
                "regions",
                JSON.stringify(names)
            );


            url.searchParams.set(
                "_",
                String(Date.now())
            );


            const response =
                await fetch(
                    url,
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


            try {

                data =
                    JSON.parse(raw);
            }
            catch (error) {

                throw new Error(
                    "Invalid OAR status response"
                );
            }


            if (
                !response.ok
                ||
                !data
                ||
                !data.ok
            ) {

                throw new Error(
                    data?.error
                    ||
                    "Unable to read OAR status"
                );
            }


            const map =
                Object.create(null);


            (
                Array.isArray(data.rows)
                    ?
                    data.rows
                    :
                    []
            )
            .forEach(
                function(row){

                    const key =
                        regionKey(
                            row.RegionName
                        );


                    if (key) {
                        map[key] = row;
                    }
                }
            );


            window.rpV85BackupMap =
                map;


            window.rpV85BackupError =
                "";


            updateCards();
        }
        catch (error) {

            window.rpV85BackupError =
                error?.message
                ||
                "Backup status unavailable";


            updateCards();
        }
        finally {

            loading =
                false;
        }
    }


    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            function(){

                updateCards();

                refreshOarStatus();
            },
            {
                once:
                    true
            }
        );
    }
    else {

        updateCards();

        refreshOarStatus();
    }


    window.setInterval(
        refreshOarStatus,
        60000
    );


    document.addEventListener(
        "click",
        function(event){

            const button =
                event.target.closest(
                    '[data-command="SaveOAR"]'
                );


            if (!button) {
                return;
            }


            window.setTimeout(
                refreshOarStatus,
                15000
            );


            window.setTimeout(
                refreshOarStatus,
                45000
            );


            window.setTimeout(
                refreshOarStatus,
                90000
            );
        }
    );


    const observer =
        new MutationObserver(
            function(mutations){

                let found =
                    false;


                mutations.forEach(
                    function(mutation){

                        mutation.addedNodes
                            .forEach(
                                function(node){

                                    if (
                                        found
                                        ||
                                        !node
                                        ||
                                        node.nodeType !== 1
                                    ) {

                                        return;
                                    }


                                    if (
                                        node.matches
                                        &&
                                        node.matches(
                                            ".rp-v85-backup"
                                        )
                                    ) {

                                        found =
                                            true;

                                        return;
                                    }


                                    if (
                                        node.querySelector
                                        &&
                                        node.querySelector(
                                            ".rp-v85-backup"
                                        )
                                    ) {

                                        found =
                                            true;
                                    }
                                }
                            );
                    }
                );


                if (found) {

                    requestAnimationFrame(
                        updateCards
                    );
                }
            }
        );


    observer.observe(
        document.body,
        {
            childList:
                true,

            subtree:
                true
        }
    );


    window.rpV85RefreshOarStatus =
        refreshOarStatus;

})();

</script>

<!-- AUSTRALIA REGIONS V8.5A END -->
