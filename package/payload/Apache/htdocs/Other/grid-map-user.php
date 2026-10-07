<?php

require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/bootstrap.php';

$session = ag_current_session();

if (!$session) {
    header('Location: /Other/login.php');
    exit;
}

$avatar = $session['avatar'];
$level  = (int)$session['level'];

/*
 * AUSTRALIA USER MAP V1
 * Any valid signed-in Grid account may view this page.
 */
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Grid - Live Map</title>

<link rel="stylesheet" href="/Other/australia-panel.css?v=69">

<style>

/* =========================================================
   Grid MAP - DARK PROFESSIONAL THEME
   ========================================================= */

.gridmap-toolbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    margin:14px 0;
    flex-wrap:wrap;
    padding:10px 12px;
    background:rgba(8,13,17,.92);
    border:1px solid rgba(255,255,255,.09);
    border-radius:10px;
}

.gridmap-toolbar-left{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
}

.gridmap-toolbar button{
    border:1px solid rgba(255,255,255,.14);
    border-radius:6px;
    padding:9px 14px;
    font-weight:800;
    font-size:11px;
    cursor:pointer;
    color:#f4f7fa;
    background:linear-gradient(180deg,#25313b,#151c22);
    box-shadow:inset 0 1px rgba(255,255,255,.05);
}

.gridmap-toolbar button:hover{
    border-color:#d99b16;
    color:#ffc13a;
}

#gridmap-updated{
    color:#aeb8c0;
    font-size:11px;
}

.gridmap-legend{
    display:flex;
    gap:16px;
    align-items:center;
    flex-wrap:wrap;
    font-size:10px;
    font-weight:800;
    color:#dce3e8;
}

.map-legend-item{
    display:flex;
    align-items:center;
    gap:7px;
}

.map-dot{
    width:9px;
    height:9px;
    min-width:9px;
    border-radius:50%;
    display:inline-block;
}

.map-dot.online{
    background:#20b7ff;
    box-shadow:0 0 7px rgba(32,183,255,.9);
}

.map-dot.warning{
    background:#ffc12e;
    box-shadow:0 0 7px rgba(255,193,46,.8);
}

.map-dot.offline{
    background:#ff4b4b;
    box-shadow:0 0 7px rgba(255,75,75,.8);
}


/* MAIN MAP FRAME */

.gridmap-shell{
    background:rgba(7,11,14,.95);
    border:1px solid rgba(255,255,255,.10);
    border-radius:12px;
    padding:12px;
    box-shadow:
        0 10px 28px rgba(0,0,0,.32),
        inset 0 1px rgba(255,255,255,.03);
}

.gridmap-scroll{
    width:100%;
    overflow:auto;
    border-radius:8px;
    border:1px solid rgba(255,255,255,.10);

    background-color:#090e12;

    background-image:
        linear-gradient(rgba(63,92,110,.15) 1px, transparent 1px),
        linear-gradient(90deg, rgba(63,92,110,.15) 1px, transparent 1px);

    background-size:42px 42px;

    box-shadow:
        inset 0 0 35px rgba(0,0,0,.45);
}

#grid-map{
    position:relative;
    min-width:900px;
    min-height:600px;
}


/* REGION TILES */

.map-region{
    position:absolute;

    border:1px solid rgba(65,190,255,.55);
    border-radius:7px;

    background:
        linear-gradient(
            145deg,
            rgba(22,31,38,.98),
            rgba(9,14,18,.98)
        );

    box-shadow:
        0 4px 12px rgba(0,0,0,.45),
        inset 0 1px rgba(255,255,255,.04);

    cursor:pointer;
    overflow:hidden;

    transition:
        transform .12s ease,
        box-shadow .12s ease,
        border-color .12s ease;
}

.map-region:hover{
    transform:translateY(-2px);

    border-color:#4dc8ff;

    box-shadow:
        0 0 0 1px rgba(32,183,255,.35),
        0 0 15px rgba(32,183,255,.22),
        0 8px 20px rgba(0,0,0,.55);

    z-index:10;
}

.map-region.selected{
    border-color:#ffc12e;

    box-shadow:
        0 0 0 1px rgba(255,193,46,.45),
        0 0 18px rgba(255,193,46,.25),
        0 8px 20px rgba(0,0,0,.55);

    z-index:11;
}


/* REGION HEADER */

.map-region-head{
    display:flex;
    align-items:center;
    gap:7px;

    min-height:25px;

    padding:6px 7px;

    border-bottom:1px solid rgba(255,255,255,.08);

    background:
        linear-gradient(
            180deg,
            rgba(33,44,52,.96),
            rgba(18,25,30,.96)
        );
}

.map-region-name{
    color:#f4f7f9;
    font-weight:900;
    font-size:10px;
    letter-spacing:.15px;

    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}


/* REGION INFORMATION */

.map-region-body{
    padding:7px;

    color:#aebbc4;

    font-size:9px;
    line-height:1.5;
}

.map-region-body strong{
    color:#ffc12e;
    font-weight:900;
}


/* SMALL REGION TILES */

.map-region[style*="width: 42px"] .map-region-body,
.map-region[style*="height: 42px"] .map-region-body{
    display:none;
}

.map-region[style*="width: 42px"] .map-region-head{
    border-bottom:0;
    height:100%;
    padding:5px;
}


/* DETAILS PANEL */

.gridmap-details{
    margin-top:14px;

    background:rgba(8,13,17,.95);

    border:1px solid rgba(255,255,255,.10);
    border-radius:12px;

    padding:16px;

    color:#dce3e8;

    box-shadow:
        0 8px 24px rgba(0,0,0,.30),
        inset 0 1px rgba(255,255,255,.03);
}

.gridmap-details h2{
    margin:0 0 14px;

    color:#ffc12e;

    font-size:13px;
    font-weight:900;

    letter-spacing:.6px;
}

.gridmap-detail-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:9px;
}

.gridmap-detail{
    background:
        linear-gradient(
            145deg,
            rgba(22,30,36,.95),
            rgba(12,17,21,.95)
        );

    border:1px solid rgba(255,255,255,.07);
    border-radius:7px;

    padding:10px;
}

.gridmap-detail span{
    display:block;

    color:#87959f;

    font-size:9px;
    font-weight:800;

    margin-bottom:5px;
}

.gridmap-detail strong{
    display:block;

    color:#eef3f6;

    font-size:11px;

    word-break:break-word;
}


/* LOADING / ERROR MESSAGE */

.gridmap-message{
    padding:30px;

    color:#c9d2d8;

    text-align:center;

    font-weight:800;
}


/* SCROLLBARS */

.gridmap-scroll::-webkit-scrollbar{
    width:10px;
    height:10px;
}

.gridmap-scroll::-webkit-scrollbar-track{
    background:#080c0f;
}

.gridmap-scroll::-webkit-scrollbar-thumb{
    background:#303c44;
    border-radius:8px;
    border:2px solid #080c0f;
}

.gridmap-scroll::-webkit-scrollbar-thumb:hover{
    background:#465761;
}


/* RESPONSIVE */

@media(max-width:900px){

    .gridmap-detail-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

}


/* =========================================================
   Grid MAP - OCEAN WATER EFFECT
   ========================================================= */

.gridmap-scroll{
    position:relative;
    overflow:auto;
    border-radius:10px;
    border:1px solid rgba(70,180,220,.20);

    background:
        radial-gradient(
            circle at 18% 22%,
            rgba(80,210,235,.12),
            transparent 26%
        ),
        radial-gradient(
            circle at 78% 68%,
            rgba(20,120,160,.18),
            transparent 34%
        ),
        linear-gradient(
            180deg,
            #0b3446 0%,
            #082a3b 30%,
            #061f30 65%,
            #051822 100%
        );

    box-shadow:
        inset 0 0 55px rgba(0,0,0,.48),
        inset 0 0 90px rgba(0,45,70,.30),
        0 8px 24px rgba(0,0,0,.28);
}

/* soft moving surface highlights */
.gridmap-scroll::before{
    content:"";
    position:absolute;
    inset:0;
    pointer-events:none;
    z-index:0;

    background:
        repeating-radial-gradient(
            ellipse at 20% 30%,
            rgba(255,255,255,.045) 0 1px,
            transparent 1px 16px
        ),
        repeating-radial-gradient(
            ellipse at 75% 65%,
            rgba(100,220,255,.035) 0 1px,
            transparent 1px 22px
        );

    opacity:.55;
    mix-blend-mode:screen;
    animation:waterDrift 18s linear infinite;
}

/* faint directional wave lines */
.gridmap-scroll::after{
    content:"";
    position:absolute;
    inset:0;
    pointer-events:none;
    z-index:0;

    background:
        repeating-linear-gradient(
            8deg,
            rgba(255,255,255,.028) 0 1px,
            transparent 1px 11px
        );

    opacity:.38;
    animation:waterShift 14s linear infinite;
}

#grid-map{
    position:relative;
    z-index:1;
    min-width:900px;
    min-height:600px;
}

/* give regions a slight island-like shadow */
.map-region{
    box-shadow:
        0 0 0 1px rgba(95,210,255,.18),
        0 8px 18px rgba(0,0,0,.55),
        0 0 18px rgba(25,140,190,.18),
        inset 0 1px rgba(255,255,255,.04);
}

.map-region:hover{
    box-shadow:
        0 0 0 1px rgba(65,200,255,.45),
        0 0 20px rgba(32,183,255,.24),
        0 10px 24px rgba(0,0,0,.62);
}

@keyframes waterDrift{
    0%{
        background-position:0 0,0 0;
    }
    50%{
        background-position:18px 10px,-16px 8px;
    }
    100%{
        background-position:36px 20px,-32px 16px;
    }
}

@keyframes waterShift{
    0%{
        background-position:0 0;
    }
    100%{
        background-position:120px 40px;
    }
}


/* =========================================================
   Grid MAP - 3D REALISTIC WATER V2
   CSS ONLY - NO IMAGE ASSETS
   ========================================================= */

/* Ocean frame */
.gridmap-scroll{
    position:relative;
    overflow:auto;

    background:
        radial-gradient(
            ellipse at 72% 12%,
            rgba(135,225,255,.22) 0%,
            rgba(45,155,195,.10) 18%,
            transparent 42%
        ),
        radial-gradient(
            ellipse at 22% 76%,
            rgba(0,85,125,.28) 0%,
            transparent 46%
        ),
        radial-gradient(
            ellipse at 50% 50%,
            rgba(8,82,108,.20) 0%,
            transparent 64%
        ),
        linear-gradient(
            180deg,
            #12617b 0%,
            #0b4b65 18%,
            #07394f 42%,
            #052c40 68%,
            #031e2e 100%
        ) !important;

    border:1px solid rgba(76,195,235,.30);

    box-shadow:
        inset 0 22px 60px rgba(90,210,245,.06),
        inset 0 -70px 100px rgba(0,0,0,.35),
        inset 0 0 90px rgba(0,35,55,.35),
        0 10px 28px rgba(0,0,0,.35);
}


/* The actual ocean surface */
#grid-map{
    position:relative;
    isolation:isolate;

    background:
        repeating-radial-gradient(
            ellipse at 45% 55%,
            rgba(255,255,255,.032) 0px,
            rgba(255,255,255,.032) 1px,
            transparent 2px,
            transparent 18px
        ),
        repeating-linear-gradient(
            7deg,
            rgba(120,220,245,.028) 0px,
            rgba(120,220,245,.028) 1px,
            transparent 2px,
            transparent 13px
        );

    box-shadow:
        inset 0 0 120px rgba(0,0,0,.22);
}


/* Moving underwater caustics */
#grid-map::before{
    content:"";
    position:absolute;
    inset:0;

    pointer-events:none;
    z-index:0;

    background:
        repeating-radial-gradient(
            ellipse at 18% 25%,
            rgba(130,225,255,.075) 0px,
            rgba(130,225,255,.035) 2px,
            transparent 4px,
            transparent 30px
        ),
        repeating-radial-gradient(
            ellipse at 75% 62%,
            rgba(255,255,255,.045) 0px,
            transparent 3px,
            transparent 27px
        );

    background-size:
        190px 95px,
        250px 120px;

    opacity:.55;

    filter:
        blur(.2px)
        contrast(1.15);

    animation:
        australiaWaterCaustics 22s linear infinite;
}


/* Surface sheen / reflected sky */
#grid-map::after{
    content:"";
    position:absolute;
    inset:0;

    pointer-events:none;
    z-index:1;

    background:
        linear-gradient(
            115deg,
            transparent 0%,
            transparent 28%,
            rgba(170,235,255,.055) 38%,
            rgba(255,255,255,.10) 44%,
            rgba(120,215,245,.045) 51%,
            transparent 61%,
            transparent 100%
        ),
        repeating-linear-gradient(
            -8deg,
            transparent 0px,
            transparent 17px,
            rgba(255,255,255,.026) 18px,
            transparent 20px
        );

    opacity:.70;

    animation:
        australiaWaterReflection 18s ease-in-out infinite alternate;
}


/* Regions sit above ocean overlays */
#grid-map > .map-region{
    z-index:2;
}


/* Give region cards a shoreline / raised-island appearance */
.map-region{
    border:
        1px solid rgba(70,200,255,.72) !important;

    background:
        linear-gradient(
            145deg,
            rgba(26,36,43,.98),
            rgba(8,13,17,.99)
        ) !important;

    box-shadow:
        0 0 0 1px rgba(125,225,255,.12),
        0 0 9px rgba(45,180,235,.18),
        0 7px 0 rgba(0,16,25,.28),
        0 14px 24px rgba(0,0,0,.48),
        inset 0 1px rgba(255,255,255,.06) !important;
}


/* Slight wet shoreline glow */
.map-region::after{
    content:"";
    position:absolute;
    inset:0;

    pointer-events:none;

    border-radius:inherit;

    box-shadow:
        inset 0 0 0 1px rgba(115,220,255,.10),
        inset 0 0 8px rgba(70,190,230,.07);
}


/* Hover feels like lifting above the water */
.map-region:hover{
    transform:translateY(-3px);

    border-color:#76d9ff !important;

    box-shadow:
        0 0 0 1px rgba(110,220,255,.32),
        0 0 18px rgba(50,190,245,.26),
        0 9px 0 rgba(0,15,22,.24),
        0 18px 30px rgba(0,0,0,.58) !important;
}


/* Selected region = gold shoreline */
.map-region.selected{
    border-color:#ffc43d !important;

    box-shadow:
        0 0 0 1px rgba(255,198,65,.38),
        0 0 22px rgba(255,190,45,.22),
        0 9px 0 rgba(0,15,22,.24),
        0 18px 30px rgba(0,0,0,.58) !important;
}


/* Slow realistic water movement */
@keyframes australiaWaterCaustics{

    0%{
        background-position:
            0px 0px,
            0px 0px;
    }

    25%{
        background-position:
            24px 8px,
            -18px 10px;
    }

    50%{
        background-position:
            50px 18px,
            -36px 4px;
    }

    75%{
        background-position:
            74px 7px,
            -54px 15px;
    }

    100%{
        background-position:
            100px 0px,
            -72px 0px;
    }
}


@keyframes australiaWaterReflection{

    0%{
        transform:translateX(-2%);
        opacity:.48;
    }

    50%{
        opacity:.72;
    }

    100%{
        transform:translateX(2%);
        opacity:.55;
    }
}


/* Respect browsers/users with reduced motion */
@media (prefers-reduced-motion: reduce){

    #grid-map::before,
    #grid-map::after{
        animation:none;
    }

}

/* END Grid MAP - 3D REALISTIC WATER V2 */


/* ============================================================
   Grid MAP - REAL MOVING WATER V1
   Real water texture with animated depth layers
   ============================================================ */

.gridmap-scroll{
    position:relative;
    overflow:auto;

    background:#031722 !important;

    border:1px solid rgba(65,185,230,.35) !important;

    box-shadow:
        inset 0 0 55px rgba(0,0,0,.48),
        0 10px 28px rgba(0,0,0,.38) !important;
}


/* Main real ocean surface */

#grid-map{
    position:relative !important;
    isolation:isolate;

    background-color:#07354c !important;

    background-image:
        linear-gradient(
            rgba(0,25,40,.10),
            rgba(0,20,32,.18)
        ),
        url('/Other/images/Water-Texture.png?v=2') !important;

    background-repeat:
        repeat,
        repeat !important;

    background-size:
        auto,
        700px 700px !important;

    background-position:
        0 0,
        0 0 !important;

    box-shadow:
        inset 0 0 110px rgba(0,20,30,.30);
}


/* Slow deep-water movement */

#grid-map::before{
    content:"" !important;

    position:absolute;
    inset:0;

    pointer-events:none;

    z-index:0;

    background-image:
        url('/Other/images/Water-Texture.png?v=2') !important;

    background-repeat:repeat !important;
    background-size:790px 790px !important;

    opacity:.28;

    filter:
        brightness(.82)
        saturate(1.18)
        contrast(1.08);

    animation:
        australiaWaterDeep 72s linear infinite !important;
}


/* Faster shimmering surface */

#grid-map::after{
    content:"" !important;

    position:absolute;
    inset:0;

    pointer-events:none;

    z-index:1;

    background-image:
        url('/Other/images/Water-Texture.png?v=2') !important;

    background-repeat:repeat !important;
    background-size:560px 560px !important;

    opacity:.16;

    mix-blend-mode:screen;

    filter:
        brightness(1.25)
        contrast(1.12)
        saturate(1.08);

    animation:
        australiaWaterSurface 38s linear infinite !important;
}


/* Regions remain above the moving ocean */

#grid-map > .map-region{
    position:absolute;
    z-index:4 !important;
}


/* Give regions a slightly raised island effect */

.map-region{
    border-color:rgba(75,205,255,.75) !important;

    box-shadow:
        0 0 0 1px rgba(110,225,255,.12),
        0 0 14px rgba(40,180,235,.20),
        0 7px 0 rgba(0,16,25,.34),
        0 15px 26px rgba(0,0,0,.52),
        inset 0 1px rgba(255,255,255,.05) !important;
}

.map-region:hover{
    border-color:#79e0ff !important;

    box-shadow:
        0 0 0 1px rgba(110,225,255,.35),
        0 0 23px rgba(40,190,250,.30),
        0 9px 0 rgba(0,18,27,.28),
        0 19px 31px rgba(0,0,0,.60) !important;
}

.map-region.selected{
    border-color:#ffc13a !important;

    box-shadow:
        0 0 0 1px rgba(255,193,58,.42),
        0 0 24px rgba(255,185,35,.30),
        0 9px 0 rgba(0,18,27,.28),
        0 19px 31px rgba(0,0,0,.60) !important;
}


/* Moving deep layer */

@keyframes australiaWaterDeep{

    0%{
        background-position:0px 0px;
    }

    25%{
        background-position:95px 42px;
    }

    50%{
        background-position:190px 84px;
    }

    75%{
        background-position:285px 126px;
    }

    100%{
        background-position:380px 168px;
    }
}


/* Moving reflection layer */

@keyframes australiaWaterSurface{

    0%{
        background-position:0px 0px;
        opacity:.12;
    }

    25%{
        background-position:-80px 55px;
        opacity:.18;
    }

    50%{
        background-position:-160px 110px;
        opacity:.14;
    }

    75%{
        background-position:-240px 165px;
        opacity:.19;
    }

    100%{
        background-position:-320px 220px;
        opacity:.12;
    }
}


@media (prefers-reduced-motion:reduce){

    #grid-map::before,
    #grid-map::after{
        animation:none !important;
    }

}

/* END Grid MAP - REAL MOVING WATER V1 */


/* ============================================================
   Grid MAP - RIGHT EDGE WATER FIX V1
   Covers the extra visible area on the right side
   ============================================================ */

.gridmap-scroll{
    position:relative !important;
    overflow:auto !important;

    background-color:#07354c !important;
    background-image:
        linear-gradient(rgba(0,25,40,.10), rgba(0,20,32,.18)),
        url('/Other/images/Water-Texture.png?v=3') !important;
    background-repeat:repeat, repeat !important;
    background-size:auto, 700px 700px !important;
    background-position:0 0, 0 0 !important;
}

#grid-map{
    min-width:100% !important;
    box-sizing:border-box !important;

    background-color:#07354c !important;
    background-image:
        linear-gradient(rgba(0,25,40,.10), rgba(0,20,32,.18)),
        url('/Other/images/Water-Texture.png?v=3') !important;
    background-repeat:repeat, repeat !important;
    background-size:auto, 700px 700px !important;
    background-position:0 0, 0 0 !important;
}

/* END Grid MAP - RIGHT EDGE WATER FIX V1 */


/* ============================================================
   Grid MAP - VISIBLE MOVING WATER V2
   ============================================================ */


/* Move the water underneath the whole map */

.gridmap-scroll{
    animation:
        australiaOceanWrapper 24s linear infinite !important;
}


/* Move the main water texture */

#grid-map{
    animation:
        australiaOceanMain 18s linear infinite !important;
}


/* Stronger deep moving layer */

#grid-map::before{
    opacity:.38 !important;

    background-size:
        760px 760px !important;

    animation:
        australiaOceanDeep 28s linear infinite !important;
}


/* Stronger surface ripple layer */

#grid-map::after{
    opacity:.24 !important;

    background-size:
        520px 520px !important;

    mix-blend-mode:screen !important;

    animation:
        australiaOceanSurface 14s linear infinite !important;
}


/* Whole visible map / right edge */

@keyframes australiaOceanWrapper{

    0%{
        background-position:
            0 0,
            0px 0px;
    }

    25%{
        background-position:
            0 0,
            70px 30px;
    }

    50%{
        background-position:
            0 0,
            140px 60px;
    }

    75%{
        background-position:
            0 0,
            210px 90px;
    }

    100%{
        background-position:
            0 0,
            280px 120px;
    }
}


/* Main ocean */

@keyframes australiaOceanMain{

    0%{
        background-position:
            0 0,
            0px 0px;
    }

    25%{
        background-position:
            0 0,
            55px 35px;
    }

    50%{
        background-position:
            0 0,
            110px 70px;
    }

    75%{
        background-position:
            0 0,
            165px 105px;
    }

    100%{
        background-position:
            0 0,
            220px 140px;
    }
}


/* Deep current travelling one direction */

@keyframes australiaOceanDeep{

    0%{
        background-position:
            0px 0px;
    }

    25%{
        background-position:
            80px 30px;
    }

    50%{
        background-position:
            160px 60px;
    }

    75%{
        background-position:
            240px 90px;
    }

    100%{
        background-position:
            320px 120px;
    }
}


/* Surface reflections travelling opposite direction */

@keyframes australiaOceanSurface{

    0%{
        background-position:
            0px 0px;
        opacity:.18;
    }

    25%{
        background-position:
            -65px 45px;
        opacity:.28;
    }

    50%{
        background-position:
            -130px 90px;
        opacity:.21;
    }

    75%{
        background-position:
            -195px 135px;
        opacity:.30;
    }

    100%{
        background-position:
            -260px 180px;
        opacity:.18;
    }
}


/* END Grid MAP - VISIBLE MOVING WATER V2 */


/* ============================================================
   Grid MAP - REGION ISLAND POLISH V1
   Raised region tiles + shoreline glow
   ============================================================ */


/* Slightly slower bright surface shimmer */

#grid-map::after{
    animation:
        australiaOceanSurface 18s linear infinite !important;

    opacity:.20 !important;
}


/* ============================================================
   REGION TILE BODY
   ============================================================ */

.map-region{
    position:absolute;

    overflow:hidden;

    border:
        1px solid rgba(83,205,255,.78) !important;

    border-radius:
        7px !important;

    background:
        linear-gradient(
            145deg,
            rgba(20,35,45,.97) 0%,
            rgba(10,21,29,.98) 48%,
            rgba(4,12,18,.99) 100%
        ) !important;

    backdrop-filter:
        blur(4px);

    -webkit-backdrop-filter:
        blur(4px);

    box-shadow:

        /* thin waterline */
        0 0 0 1px rgba(110,225,255,.16),

        /* shoreline glow */
        0 0 12px rgba(40,185,240,.24),

        /* raised lower edge */
        0 5px 0 rgba(1,15,23,.72),

        /* main depth shadow */
        0 12px 22px rgba(0,0,0,.58),

        /* subtle top reflection */
        inset 0 1px 0 rgba(255,255,255,.10),

        /* dark lower depth */
        inset 0 -12px 18px rgba(0,0,0,.18)

        !important;

    transition:
        transform .18s ease,
        border-color .18s ease,
        box-shadow .18s ease,
        filter .18s ease;
}


/* ============================================================
   INNER WATERLINE / GLASS EDGE
   ============================================================ */

.map-region::before{
    content:"";

    position:absolute;
    inset:2px;

    pointer-events:none;

    border-radius:5px;

    border:
        1px solid rgba(255,255,255,.035);

    background:
        linear-gradient(
            180deg,
            rgba(120,220,255,.045),
            transparent 22%
        );

    box-shadow:
        inset 0 0 12px rgba(75,190,225,.035);

    z-index:0;
}


/* ============================================================
   SOFT WATER HALO
   Helps the regions appear to float over the ocean
   ============================================================ */

.map-region::after{
    content:"";

    position:absolute;

    left:-7px;
    right:-7px;
    bottom:-7px;

    height:14px;

    pointer-events:none;

    background:
        radial-gradient(
            ellipse at center,
            rgba(80,215,255,.25) 0%,
            rgba(30,145,200,.10) 42%,
            transparent 72%
        );

    filter:
        blur(5px);

    opacity:.60;

    z-index:-1;
}


/* ============================================================
   KEEP REGION CONTENT ABOVE INTERNAL EFFECTS
   ============================================================ */

.map-region > *{
    position:relative;
    z-index:2;
}


/* ============================================================
   REGION TITLES
   ============================================================ */

.map-region strong,
.map-region .region-name,
.map-region .map-region-name{
    color:#ffffff !important;

    text-shadow:
        0 1px 2px rgba(0,0,0,.95),
        0 0 5px rgba(70,190,235,.14);
}


/* Gold size / secondary highlight */

.map-region .region-size,
.map-region .map-region-size{
    color:#ffc94a !important;

    text-shadow:
        0 1px 2px rgba(0,0,0,.90);
}


/* ============================================================
   HOVER
   ============================================================ */

.map-region:hover{
    transform:
        translateY(-3px);

    border-color:
        #7be2ff !important;

    filter:
        brightness(1.07);

    box-shadow:

        0 0 0 1px rgba(130,230,255,.26),

        0 0 18px rgba(50,195,250,.34),

        0 7px 0 rgba(1,15,23,.64),

        0 17px 29px rgba(0,0,0,.62),

        inset 0 1px 0 rgba(255,255,255,.13),

        inset 0 -12px 18px rgba(0,0,0,.15)

        !important;
}


/* ============================================================
   SELECTED REGION
   ============================================================ */

.map-region.selected{
    border-color:
        #ffc83d !important;

    box-shadow:

        0 0 0 1px rgba(255,205,75,.28),

        0 0 19px rgba(255,188,45,.34),

        0 7px 0 rgba(1,15,23,.64),

        0 17px 29px rgba(0,0,0,.62),

        inset 0 1px 0 rgba(255,255,255,.13),

        inset 0 -12px 18px rgba(0,0,0,.15)

        !important;
}


/* ============================================================
   ONLINE REGION EDGE
   ============================================================ */

.map-region.status-online,
.map-region.online{
    border-color:
        rgba(74,205,255,.88) !important;
}


/* ============================================================
   WARNING REGION EDGE
   ============================================================ */

.map-region.status-busy,
.map-region.status-warning,
.map-region.warning{
    border-color:
        rgba(255,197,55,.90) !important;
}


/* ============================================================
   OFFLINE REGION EDGE
   ============================================================ */

.map-region.status-offline,
.map-region.offline{
    border-color:
        rgba(255,80,80,.88) !important;

    filter:
        saturate(.70)
        brightness(.84);
}


/* END Grid MAP - REGION ISLAND POLISH V1 */


/* ============================================================
   Grid MAP - SAND ISLANDS V1
   Little sand islands under each floating region box
   ============================================================ */


/* Let the island shape show outside the box */
.map-region{
    overflow: visible !important;
}


/* Keep the floating box itself above the island */
.map-region > *{
    position: relative;
    z-index: 3 !important;
}


/* Sand island behind each region */
.map-region::after{
    content: "" !important;

    position: absolute !important;

    left: -10px;
    right: -10px;
    top: -8px;
    bottom: -12px;

    pointer-events: none;

    border-radius: 18px;

    z-index: -1 !important;

    background:
        radial-gradient(
            ellipse at 50% 50%,
            rgba(246,226,176,.98) 0%,
            rgba(239,213,154,.98) 34%,
            rgba(229,194,126,.95) 54%,
            rgba(202,166,102,.70) 68%,
            rgba(185,149,88,.28) 76%,
            transparent 84%
        ),
        radial-gradient(
            ellipse at 18% 38%,
            rgba(255,239,196,.52) 0%,
            transparent 44%
        ),
        radial-gradient(
            ellipse at 82% 62%,
            rgba(214,175,104,.26) 0%,
            transparent 46%
        );

    transform:
        translateY(6px)
        scale(1.04,1.12);

    box-shadow:
        0 2px 0 rgba(255,244,213,.18),
        0 0 0 2px rgba(80,195,235,.16),
        0 10px 16px rgba(0,0,0,.28);

    filter:
        saturate(1.04)
        brightness(1.02);
}


/* Small regions get slightly tighter little islands */
.map-region[style*="width: 42px"]::after,
.map-region[style*="height: 42px"]::after{
    left: -7px;
    right: -7px;
    top: -6px;
    bottom: -8px;

    border-radius: 14px;

    transform:
        translateY(4px)
        scale(1.05,1.10);
}


/* Medium regions */
.map-region[style*="width: 84px"]::after,
.map-region[style*="height: 84px"]::after{
    left: -8px;
    right: -8px;
    top: -7px;
    bottom: -10px;
}


/* Keep the floating panel look strong over the sand */
.map-region{
    box-shadow:
        0 0 0 1px rgba(110,225,255,.12),
        0 0 14px rgba(40,180,235,.18),
        0 8px 0 rgba(0,16,25,.44),
        0 15px 26px rgba(0,0,0,.56),
        inset 0 1px rgba(255,255,255,.06) !important;
}


/* Hover = a bit more lift */
.map-region:hover{
    transform: translateY(-4px) !important;
}


/* Selected region still keeps its gold highlight */
.map-region.selected{
    box-shadow:
        0 0 0 1px rgba(255,193,58,.38),
        0 0 24px rgba(255,185,35,.28),
        0 9px 0 rgba(0,18,27,.28),
        0 18px 30px rgba(0,0,0,.60),
        inset 0 1px rgba(255,255,255,.08) !important;
}

/* END Grid MAP - SAND ISLANDS V1 */


/* ============================================================
   Grid MAP - REAL SAND ISLANDS V2
   Visible sand islands + shallow turquoise shoreline
   Floating region boxes remain above them
   ============================================================ */

.map-region{
    overflow:visible !important;
    isolation:isolate;
}


/* ============================================================
   SHALLOW TURQUOISE WATER AROUND EACH ISLAND
   ============================================================ */

.map-region::before{
    content:"" !important;

    position:absolute !important;

    left:-32px !important;
    right:-32px !important;
    top:-27px !important;
    bottom:-31px !important;

    display:block !important;

    pointer-events:none !important;

    z-index:-2 !important;

    border:none !important;

    border-radius:
        48% 52% 44% 56% /
        55% 43% 57% 45% !important;

    background:
        radial-gradient(
            ellipse at center,
            rgba(75,220,230,.80) 0%,
            rgba(58,205,221,.68) 55%,
            rgba(95,225,233,.48) 67%,
            rgba(210,248,245,.24) 75%,
            rgba(46,167,197,.16) 82%,
            transparent 88%
        ) !important;

    box-shadow:none !important;

    filter:blur(1px);

    transform:
        translateY(7px)
        rotate(-1deg) !important;
}


/* ============================================================
   SAND BODY
   ============================================================ */

.map-region::after{
    content:"" !important;

    position:absolute !important;

    left:-21px !important;
    right:-21px !important;
    top:-18px !important;
    bottom:-22px !important;

    display:block !important;

    pointer-events:none !important;

    z-index:-1 !important;

    border-radius:
        44% 56% 50% 50% /
        54% 45% 55% 46% !important;

    background:

        /* small natural sand variations */
        radial-gradient(
            circle at 23% 30%,
            rgba(255,248,218,.55) 0px,
            rgba(255,248,218,.55) 2px,
            transparent 3px
        ),

        radial-gradient(
            circle at 68% 72%,
            rgba(175,125,62,.22) 0px,
            rgba(175,125,62,.22) 2px,
            transparent 3px
        ),

        radial-gradient(
            circle at 77% 25%,
            rgba(255,239,190,.38) 0px,
            rgba(255,239,190,.38) 3px,
            transparent 4px
        ),

        /* actual beach sand */
        linear-gradient(
            145deg,
            #f8e3a7 0%,
            #f0cf83 30%,
            #e1b566 58%,
            #cd9548 82%,
            #b87a38 100%
        ) !important;

    background-size:
        31px 31px,
        43px 43px,
        57px 57px,
        auto !important;

    box-shadow:
        inset 0 2px 2px rgba(255,255,255,.32),
        inset 0 -3px 5px rgba(122,74,26,.20),
        0 5px 9px rgba(0,0,0,.30) !important;

    filter:
        saturate(1.08)
        brightness(1.04);

    transform:
        translateY(6px)
        rotate(1deg) !important;
}


/* ============================================================
   GIVE THE ISLANDS DIFFERENT NATURAL SHAPES
   ============================================================ */

.map-region:nth-child(3n+1)::after{
    border-radius:
        42% 58% 46% 54% /
        58% 43% 57% 42% !important;

    transform:
        translateY(6px)
        rotate(-2deg) !important;
}

.map-region:nth-child(3n+1)::before{
    border-radius:
        47% 53% 42% 58% /
        57% 44% 56% 43% !important;

    transform:
        translateY(8px)
        rotate(-2.5deg) !important;
}


.map-region:nth-child(3n+2)::after{
    border-radius:
        55% 45% 59% 41% /
        44% 59% 41% 56% !important;

    transform:
        translateY(5px)
        rotate(1.5deg) !important;
}

.map-region:nth-child(3n+2)::before{
    border-radius:
        57% 43% 54% 46% /
        46% 57% 43% 54% !important;

    transform:
        translateY(7px)
        rotate(2deg) !important;
}


.map-region:nth-child(3n+3)::after{
    border-radius:
        48% 52% 43% 57% /
        60% 42% 58% 40% !important;

    transform:
        translateY(7px)
        rotate(-.5deg) !important;
}

.map-region:nth-child(3n+3)::before{
    border-radius:
        50% 50% 45% 55% /
        58% 44% 56% 42% !important;

    transform:
        translateY(8px)
        rotate(.5deg) !important;
}


/* ============================================================
   KEEP FLOATING REGION BOX CLEARLY ABOVE THE ISLAND
   ============================================================ */

.map-region{
    box-shadow:
        0 0 0 1px rgba(85,210,255,.22),
        0 0 13px rgba(50,190,240,.22),
        0 8px 0 rgba(0,13,20,.55),
        0 15px 26px rgba(0,0,0,.62),
        inset 0 1px rgba(255,255,255,.08) !important;
}


/* Float slightly higher when hovered */

.map-region:hover{
    transform:translateY(-4px) !important;

    box-shadow:
        0 0 0 1px rgba(100,225,255,.35),
        0 0 22px rgba(45,195,250,.34),
        0 10px 0 rgba(0,13,20,.50),
        0 20px 32px rgba(0,0,0,.66),
        inset 0 1px rgba(255,255,255,.10) !important;
}


/* Keep selected region gold */

.map-region.selected{
    border-color:#ffc13a !important;
}


/* END Grid MAP - REAL SAND ISLANDS V2 */


/* ============================================================
   Grid MAP - SEPARATE SAND ISLANDS V3
   Islands are separate objects underneath the region boxes
   ============================================================ */


/* Completely disable the failed island effects attached
   directly to the region boxes */

.map-region::before,
.map-region::after{
    content:none !important;
    display:none !important;
    background:none !important;
    box-shadow:none !important;
}


/* Restore the floating region boxes */

.map-region{
    overflow:hidden !important;
    z-index:5 !important;

    background:
        linear-gradient(
            145deg,
            rgba(20,32,40,.98),
            rgba(5,12,17,.99)
        ) !important;

    border:
        1px solid rgba(65,205,255,.78) !important;

    box-shadow:
        0 0 0 1px rgba(100,220,255,.12),
        0 0 12px rgba(40,180,235,.20),
        0 7px 0 rgba(0,14,22,.50),
        0 15px 25px rgba(0,0,0,.58),
        inset 0 1px rgba(255,255,255,.07)
        !important;
}


/* ============================================================
   ACTUAL ISLAND OBJECT
   ============================================================ */

.region-island{
    position:absolute;

    pointer-events:none;

    z-index:2;

    border-radius:
        48% 52% 43% 57% /
        55% 44% 56% 45%;

    /*
       Outer turquoise water / reef
    */

    background:
        radial-gradient(
            ellipse at center,
            rgba(242,218,157,.98) 0%,
            rgba(239,207,137,.98) 48%,
            rgba(218,174,91,.96) 62%,
            rgba(82,218,223,.80) 67%,
            rgba(45,193,211,.62) 74%,
            rgba(43,160,191,.26) 82%,
            transparent 89%
        );

    filter:
        drop-shadow(0 9px 7px rgba(0,0,0,.32));

    opacity:.98;
}


/* lighter centre of the sand */

.region-island::before{
    content:"";

    position:absolute;

    left:14%;
    right:14%;
    top:13%;
    bottom:13%;

    border-radius:
        51% 49% 56% 44% /
        47% 57% 43% 53%;

    background:

        radial-gradient(
            circle at 24% 30%,
            rgba(255,248,213,.75) 0px,
            rgba(255,248,213,.75) 2px,
            transparent 3px
        ),

        radial-gradient(
            circle at 68% 66%,
            rgba(170,115,48,.18) 0px,
            rgba(170,115,48,.18) 2px,
            transparent 3px
        ),

        linear-gradient(
            145deg,
            #f8e4ab 0%,
            #efd08d 38%,
            #ddb165 72%,
            #c88d42 100%
        );

    background-size:
        29px 29px,
        41px 41px,
        auto;

    box-shadow:
        inset 0 2px 2px rgba(255,255,255,.32),
        inset 0 -3px 5px rgba(121,73,25,.18);
}


/* beach / surf highlight */

.region-island::after{
    content:"";

    position:absolute;

    inset:6%;

    border-radius:
        45% 55% 52% 48% /
        58% 43% 57% 42%;

    border:
        2px solid rgba(220,250,245,.36);

    box-shadow:
        0 0 5px rgba(135,245,245,.25),
        inset 0 0 5px rgba(255,245,215,.24);
}


/* Give different islands different natural outlines */

.region-island.island-a{
    border-radius:
        42% 58% 48% 52% /
        57% 43% 61% 39%;

    transform:rotate(-2deg);
}

.region-island.island-b{
    border-radius:
        56% 44% 59% 41% /
        44% 58% 42% 56%;

    transform:rotate(1.5deg);
}

.region-island.island-c{
    border-radius:
        49% 51% 42% 58% /
        61% 40% 60% 39%;

    transform:rotate(-.7deg);
}


/* Floating region stays above the island */

#grid-map > .map-region{
    z-index:5 !important;
}


/* Selected region remains gold */

.map-region.selected{
    border-color:#ffc13a !important;
}


/* END Grid MAP - SEPARATE SAND ISLANDS V3 */


/* ============================================================
   Grid MAP - REAL SAND CAYS V1
   Larger white sand islands with aqua shallows under each box
   ============================================================ */

/* Keep the info boxes floating cleanly above everything */
.map-region{
    z-index: 5 !important;
    overflow: hidden !important;
    background: linear-gradient(145deg, rgba(15,29,38,.97), rgba(5,12,18,.99)) !important;
    border: 1px solid rgba(65,205,255,.78) !important;
    box-shadow:
        0 0 0 1px rgba(110,220,255,.12),
        0 0 12px rgba(40,180,235,.18),
        0 10px 18px rgba(0,0,0,.55),
        inset 0 1px rgba(255,255,255,.06) !important;
}

/* Any old direct box island effects - disable them */
.map-region::before,
.map-region::after{
    content: none !important;
    display: none !important;
}

/* Separate island under each region */
.region-island{
    position: absolute;
    pointer-events: none;
    z-index: 2;
    opacity: 1;
    filter: drop-shadow(0 12px 10px rgba(0,0,0,.30));
    transform-origin: center center;

    border-radius:
        49% 51% 45% 55% /
        56% 43% 57% 44%;

    /* This is the OUTER water / reef ring */
    background:
        radial-gradient(
            ellipse at center,
            rgba(255,255,255,0) 0%,
            rgba(255,255,255,0) 37%,
            rgba(230,248,243,.35) 44%,
            rgba(139,244,235,.72) 53%,
            rgba(79,223,223,.62) 63%,
            rgba(28,174,198,.30) 73%,
            rgba(10,108,149,0) 86%
        );
}

/* White sand body */
.region-island::before{
    content: "";
    position: absolute;

    left: 16%;
    right: 16%;
    top: 14%;
    bottom: 14%;

    border-radius:
        48% 52% 46% 54% /
        55% 44% 56% 45%;

    background:
        radial-gradient(
            ellipse at 35% 30%,
            rgba(255,255,255,.98) 0%,
            rgba(255,255,255,.96) 28%,
            rgba(251,247,238,.96) 55%,
            rgba(240,234,221,.95) 78%,
            rgba(225,215,191,.92) 100%
        );

    box-shadow:
        inset 0 2px 6px rgba(255,255,255,.55),
        inset 0 -8px 10px rgba(200,186,150,.18),
        0 0 0 1px rgba(255,255,255,.20);
}

/* Shallow shoreline / surf band */
.region-island::after{
    content: "";
    position: absolute;

    left: 10%;
    right: 10%;
    top: 9%;
    bottom: 9%;

    border-radius:
        50% 50% 47% 53% /
        58% 42% 58% 42%;

    background:
        radial-gradient(
            ellipse at center,
            rgba(255,255,255,0) 0%,
            rgba(255,255,255,0) 57%,
            rgba(255,255,255,.28) 63%,
            rgba(223,249,248,.32) 67%,
            rgba(148,241,235,.24) 74%,
            rgba(255,255,255,0) 82%
        );
}

/* Variation shapes so every island does not look identical */
.region-island.island-a{
    border-radius:
        43% 57% 49% 51% /
        55% 41% 59% 45%;
    transform: rotate(-4deg);
}
.region-island.island-b{
    border-radius:
        55% 45% 57% 43% /
        47% 59% 41% 53%;
    transform: rotate(3deg);
}
.region-island.island-c{
    border-radius:
        47% 53% 42% 58% /
        60% 41% 59% 40%;
    transform: rotate(-2deg);
}

/* Selected region still stands out */
.map-region.selected{
    border-color:#ffc13a !important;
    box-shadow:
        0 0 0 1px rgba(255,193,58,.18),
        0 0 14px rgba(255,193,58,.22),
        0 12px 22px rgba(0,0,0,.55),
        inset 0 1px rgba(255,255,255,.06) !important;
}

/* END Grid MAP - REAL SAND CAYS V1 */


/* ============================================================
   Grid MAP - REAL PNG ISLANDS V1

   Moving ocean
          â†“
   Real transparent island PNG
          â†“
   Floating region information box
   ============================================================ */


/* Kill every old fake bubble/island layer */

.region-island{
    display:none !important;
}

.map-region::before,
.map-region::after{
    content:none !important;
    display:none !important;
    background:none !important;
    border:none !important;
    box-shadow:none !important;
}


/* ============================================================
   REAL ISLAND IMAGE
   ============================================================ */

.region-island-image{
    position:absolute;

    pointer-events:none;

    z-index:2;

    background-repeat:no-repeat;

    background-position:center center;

    /*
       We deliberately stretch each island slightly to match
       the actual square region footprint.
    */
    background-size:100% 100%;

    opacity:1;

    filter:
        saturate(1.05)
        brightness(1.02)
        drop-shadow(0 10px 9px rgba(0,0,0,.28));

    transform-origin:center center;
}


/* SMALL - 1x1 and 2x2 */

.region-island-image.island-small{
    background-image:
        url('/Other/images/island-small.png?v=1');
}


/* MEDIUM - 3x3 and 4x4 */

.region-island-image.island-medium{
    background-image:
        url('/Other/images/island-medium.png?v=1');
}


/* LARGE - 5x5, 6x6 and 7x7 */

.region-island-image.island-large{
    background-image:
        url('/Other/images/island-large.png?v=1');
}


/* ============================================================
   REGION INFO BOXES FLOAT ABOVE THE REAL ISLANDS
   ============================================================ */

#grid-map > .map-region{
    z-index:5 !important;

    overflow:hidden !important;

    background:
        linear-gradient(
            145deg,
            rgba(17,31,40,.98) 0%,
            rgba(7,16,22,.99) 55%,
            rgba(3,10,15,.99) 100%
        ) !important;

    border:
        1px solid rgba(67,205,255,.80) !important;

    box-shadow:

        0 0 0 1px rgba(110,225,255,.12),

        0 0 12px rgba(35,180,235,.18),

        0 7px 0 rgba(0,13,20,.48),

        0 15px 25px rgba(0,0,0,.58),

        inset 0 1px rgba(255,255,255,.07)

        !important;
}


/* Hover still lifts the information box */

#grid-map > .map-region:hover{
    transform:translateY(-3px);

    border-color:#79e2ff !important;

    box-shadow:

        0 0 0 1px rgba(120,230,255,.28),

        0 0 18px rgba(45,195,250,.28),

        0 9px 0 rgba(0,13,20,.44),

        0 19px 30px rgba(0,0,0,.62),

        inset 0 1px rgba(255,255,255,.09)

        !important;
}


/* Selected region remains gold */

#grid-map > .map-region.selected{
    border-color:#ffc13a !important;

    box-shadow:

        0 0 0 1px rgba(255,193,58,.35),

        0 0 20px rgba(255,185,35,.25),

        0 9px 0 rgba(0,13,20,.44),

        0 19px 30px rgba(0,0,0,.62),

        inset 0 1px rgba(255,255,255,.09)

        !important;
}


/* END Grid MAP - REAL PNG ISLANDS V1 */








/* ============================================================
   Grid MAP - FIRESTORM CANVAS PAN ZOOM V2

   NO internal scrollbars.
   The map itself is translated/scaled like a real map canvas.
   ============================================================ */


/* ============================================================
   MAP VIEWPORT
   ============================================================ */

.gridmap-scroll{

    position:relative !important;

    /*
       IMPORTANT:
       Firestorm-style viewport.
       The map is clipped instead of using scrollbars.
    */

    overflow:hidden !important;

    width:100% !important;

    height:clamp(
        560px,
        68vh,
        820px
    ) !important;

    min-height:560px !important;

    cursor:grab !important;

    touch-action:none !important;

    user-select:none !important;
    -webkit-user-select:none !important;

    overscroll-behavior:contain !important;

}


/* absolutely no water-panel scrollbars */

.gridmap-scroll::-webkit-scrollbar{
    display:none !important;
    width:0 !important;
    height:0 !important;
}

.gridmap-scroll{
    scrollbar-width:none !important;
}


/* ============================================================
   ACTUAL MAP WORLD
   ============================================================ */

#grid-map{

    /*
       The whole world is now moved using one matrix.
       No scrollLeft/scrollTop.
    */

    transform-origin:0 0 !important;

    will-change:transform !important;

    /*
       Don't let old CSS centering/transforms interfere.
    */

    margin:0 !important;

}


/* ============================================================
   CURSORS
   ============================================================ */

.gridmap-scroll .map-region{
    cursor:grab !important;
}

.gridmap-scroll.firestorm-map-grabbing,
.gridmap-scroll.firestorm-map-grabbing *,
.gridmap-scroll.firestorm-map-grabbing .map-region{

    cursor:grabbing !important;

}


/* ============================================================
   ZOOM CONTROL PANEL
   ============================================================ */

.firestorm-map-controls{

    display:flex;

    align-items:center;

    gap:6px;

    margin-left:auto;

    padding:5px 7px;

    border:
        1px solid rgba(255,193,58,.28);

    border-radius:8px;

    background:
        rgba(5,10,15,.91);

    box-shadow:
        inset 0 1px rgba(255,255,255,.035),
        0 5px 15px rgba(0,0,0,.22);

}


.firestorm-map-control-title{

    margin-right:3px;

    color:#85939d;

    font-size:10px;

    font-weight:800;

    letter-spacing:.08em;

}


.firestorm-map-button{

    height:29px;

    min-width:32px;

    padding:0 9px;

    border:
        1px solid rgba(255,193,58,.32);

    border-radius:6px;

    background:
        linear-gradient(
            180deg,
            rgba(38,32,18,.96),
            rgba(14,14,13,.98)
        );

    color:#f4f6f7;

    font-size:12px;

    font-weight:800;

    cursor:pointer;

}


.firestorm-map-button:hover{

    border-color:#ffc13a;

    color:#ffc13a;

}


.firestorm-map-zoom-value{

    min-width:52px;

    text-align:center;

    color:#ffc13a;

    font-size:12px;

    font-weight:800;

}


.firestorm-map-help{

    margin-left:4px;

    color:#697985;

    font-size:10px;

    white-space:nowrap;

}


/* END Grid MAP - FIRESTORM CANVAS PAN ZOOM V2 */


/* ============================================================
   Grid MAP - FIRESTORM OCEAN SYNC V3

   ONE ocean coordinate system.

   Ocean + islands now pan/zoom together.
   ============================================================ */


/* ============================================================
   FIRESTORM VIEWPORT OCEAN
   ============================================================ */

.gridmap-scroll{

    --fs-ocean-x: 0px;
    --fs-ocean-y: 0px;

    --fs-ocean-base-size: 700px;
    --fs-ocean-deep-size: 790px;
    --fs-ocean-surface-size: 560px;

    isolation:isolate !important;

    overflow:hidden !important;

    /*
       This becomes the REAL ocean base.
    */

    background-color:#07354c !important;

    background-image:

        linear-gradient(
            rgba(0,25,40,.08),
            rgba(0,18,30,.14)
        ),

        url('/Other/images/Water-Texture.png?v=20')

        !important;

    background-repeat:
        repeat,
        repeat

        !important;

    background-size:

        auto,

        var(--fs-ocean-base-size)
        var(--fs-ocean-base-size)

        !important;

    background-position:

        0 0,

        var(--fs-ocean-x)
        var(--fs-ocean-y)

        !important;


    /*
       Disable all older wrapper animation.
       Pan/zoom is now controlled by the Firestorm engine.
    */

    animation:none !important;

}


/* ============================================================
   REMOVE WATER FROM THE MOVING MAP OBJECT
   ============================================================ */

#grid-map{

    /*
       The GRID itself is now transparent.

       Regions + islands move.
       Ocean is handled by the viewport.
    */

    background:
        transparent !important;

    background-color:
        transparent !important;

    background-image:
        none !important;

    animation:
        none !important;

    box-shadow:
        none !important;

    position:relative !important;

    z-index:3 !important;

}


/*
   Kill all old water pseudo-layers attached to #grid-map.
*/

#grid-map::before,
#grid-map::after{

    content:none !important;

    display:none !important;

    background:none !important;

    animation:none !important;

}


/* ============================================================
   DEEP MOVING WATER LAYER
   ============================================================ */

.gridmap-scroll::before{

    content:"" !important;

    display:block !important;

    position:absolute;

    /*
       Oversize it so movement never exposes an edge.
    */

    left:-400px;
    top:-400px;
    right:-400px;
    bottom:-400px;

    z-index:0;

    pointer-events:none;


    background-image:

        url('/Other/images/Water-Texture.png?v=20')

        !important;


    background-repeat:
        repeat !important;


    background-size:

        var(--fs-ocean-deep-size)
        var(--fs-ocean-deep-size)

        !important;


    background-position:

        var(--fs-ocean-x)
        var(--fs-ocean-y)

        !important;


    opacity:.30;


    filter:

        brightness(.82)
        saturate(1.15)
        contrast(1.06);


    /*
       Natural water drift is separate from user pan/zoom.
    */

    animation:

        firestormOceanDeepDrift
        32s
        linear
        infinite

        !important;

}


/* ============================================================
   SURFACE SHIMMER
   ============================================================ */

.gridmap-scroll::after{

    content:"" !important;

    display:block !important;

    position:absolute;

    left:-400px;
    top:-400px;
    right:-400px;
    bottom:-400px;

    z-index:1;

    pointer-events:none;


    background-image:

        url('/Other/images/Water-Texture.png?v=20')

        !important;


    background-repeat:
        repeat !important;


    background-size:

        var(--fs-ocean-surface-size)
        var(--fs-ocean-surface-size)

        !important;


    background-position:

        var(--fs-ocean-x)
        var(--fs-ocean-y)

        !important;


    opacity:.16;

    mix-blend-mode:screen;


    filter:

        brightness(1.22)
        contrast(1.10)
        saturate(1.05);


    animation:

        firestormOceanSurfaceDrift
        18s
        linear
        infinite

        !important;

}


/* ============================================================
   WATER ANIMATION
   ============================================================ */

@keyframes firestormOceanDeepDrift{

    0%{
        transform:
            translate3d(0px,0px,0);
    }

    25%{
        transform:
            translate3d(45px,18px,0);
    }

    50%{
        transform:
            translate3d(90px,36px,0);
    }

    75%{
        transform:
            translate3d(135px,54px,0);
    }

    100%{
        transform:
            translate3d(180px,72px,0);
    }

}


@keyframes firestormOceanSurfaceDrift{

    0%{
        transform:
            translate3d(0px,0px,0);
    }

    25%{
        transform:
            translate3d(-38px,26px,0);
    }

    50%{
        transform:
            translate3d(-76px,52px,0);
    }

    75%{
        transform:
            translate3d(-114px,78px,0);
    }

    100%{
        transform:
            translate3d(-152px,104px,0);
    }

}


/* ============================================================
   KEEP MAP CONTENT ABOVE OCEAN
   ============================================================ */

#grid-map > .region-island-image{

    z-index:2 !important;

}


#grid-map > .map-region{

    z-index:5 !important;

}


/* END Grid MAP - FIRESTORM OCEAN SYNC V3 */


/* ============================================================
   Grid MAP - FIRESTORM MOVING OCEAN V4

   Makes ALL water layers move.

   Pan + zoom offsets are preserved.
   Natural water drift is added separately.
   ============================================================ */

.gridmap-scroll{

    --fs-water-base-x:0px;
    --fs-water-base-y:0px;

    --fs-water-deep-x:0px;
    --fs-water-deep-y:0px;

    --fs-water-surface-x:0px;
    --fs-water-surface-y:0px;


    /*
       Main ocean.

       IMPORTANT:
       panX/panY still come from the Firestorm map engine.
       Water drift gets ADDED to those coordinates.
    */

    background-position:

        0 0,

        calc(
            var(--fs-ocean-x) +
            var(--fs-water-base-x)
        )

        calc(
            var(--fs-ocean-y) +
            var(--fs-water-base-y)
        )

        !important;

}


/* ============================================================
   DEEP CURRENT
   ============================================================ */

.gridmap-scroll::before{

    /*
       Disable the old transform animation.
       The new water engine controls movement directly.
    */

    animation:none !important;

    transform:none !important;


    background-position:

        calc(
            var(--fs-ocean-x) +
            var(--fs-water-deep-x)
        )

        calc(
            var(--fs-ocean-y) +
            var(--fs-water-deep-y)
        )

        !important;


    opacity:.36 !important;

}


/* ============================================================
   BRIGHT SURFACE RIPPLE
   ============================================================ */

.gridmap-scroll::after{

    animation:none !important;

    transform:none !important;


    background-position:

        calc(
            var(--fs-ocean-x) +
            var(--fs-water-surface-x)
        )

        calc(
            var(--fs-ocean-y) +
            var(--fs-water-surface-y)
        )

        !important;


    opacity:.22 !important;

    mix-blend-mode:screen !important;

}


/* END Grid MAP - FIRESTORM MOVING OCEAN V4 */


/* ============================================================
   Grid MAP - HOP TO REGION V1
   ============================================================ */

.gridmap-hop-actions{
    grid-column:1 / -1;

    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;

    margin-top:14px;
    padding-top:14px;

    border-top:
        1px solid rgba(255,193,58,.16);
}


.gridmap-hop-button{
    display:inline-flex;

    align-items:center;
    justify-content:center;

    gap:8px;

    min-height:38px;

    padding:0 18px;

    border:
        1px solid rgba(255,193,58,.55);

    border-radius:7px;

    background:
        linear-gradient(
            180deg,
            rgba(63,48,19,.98),
            rgba(25,20,12,.98)
        );

    color:#ffc13a;

    font-size:12px;
    font-weight:850;

    letter-spacing:.04em;

    cursor:pointer;

    box-shadow:
        inset 0 1px rgba(255,255,255,.06),
        0 5px 14px rgba(0,0,0,.24);

    transition:
        border-color .15s ease,
        background .15s ease,
        color .15s ease,
        transform .15s ease;
}


.gridmap-hop-button:hover:not(:disabled){
    border-color:#ffd66d;

    color:#fff2bf;

    background:
        linear-gradient(
            180deg,
            rgba(91,67,23,.98),
            rgba(33,24,11,.98)
        );

    transform:translateY(-1px);
}


.gridmap-hop-button:disabled{
    opacity:.38;
    cursor:not-allowed;
    transform:none;
}


.gridmap-hop-help{
    color:#81909a;

    font-size:11px;
}


.gridmap-hop-destination{
    color:#aab7bf;

    font-size:11px;
}


.gridmap-hop-destination strong{
    color:#ffffff;
}


/* Give region cards a subtle teleport hint */

.map-region{
    position:absolute;
}

.map-region:hover{
    cursor:pointer;
}


/* END Grid MAP - HOP TO REGION V1 */


/* ============================================================
   Grid MAP - SEARCH SELECT MEMORY V2
   ============================================================ */

.gridmap-region-search{

    display:flex;
    align-items:center;
    gap:6px;

    margin-left:8px;
    padding:5px 7px;

    border:
        1px solid rgba(255,193,58,.25);

    border-radius:8px;

    background:
        rgba(5,10,15,.91);

    box-shadow:
        inset 0 1px rgba(255,255,255,.035),
        0 4px 14px rgba(0,0,0,.22);

}


.gridmap-search-title{

    margin:0 3px;

    color:#80909b;

    font-size:10px;
    font-weight:850;

    letter-spacing:.08em;

}


.gridmap-search-input{

    width:190px;
    height:29px;

    padding:0 10px;

    border:
        1px solid rgba(255,193,58,.28);

    border-radius:6px;

    outline:none;

    background:
        rgba(3,8,12,.96);

    color:#f5f7f8;

    font-size:12px;
    font-weight:650;

}


.gridmap-search-input:focus{

    border-color:#ffc13a;

    box-shadow:
        0 0 0 2px
        rgba(255,193,58,.12);

}


.gridmap-search-button{

    height:29px;

    padding:0 11px;

    border:
        1px solid rgba(255,193,58,.38);

    border-radius:6px;

    background:
        linear-gradient(
            180deg,
            rgba(45,35,17,.98),
            rgba(15,14,12,.98)
        );

    color:#f4f6f7;

    font-size:11px;
    font-weight:850;

    cursor:pointer;

}


.gridmap-search-button:hover{

    border-color:#ffc13a;
    color:#ffc13a;

}


.gridmap-search-status{

    min-width:68px;

    color:#75848e;

    font-size:10px;
    font-weight:800;

}


.gridmap-search-status.found{

    color:#56c9ff;

}


.gridmap-search-status.error{

    color:#ff6c63;

}


/* ============================================================
   SELECTED REGION
   ============================================================ */

#grid-map .map-region.gridmap-selected-region{

    outline:
        3px solid #ffc13a !important;

    outline-offset:
        4px !important;

    box-shadow:

        0 0 0 1px
        rgba(255,193,58,.65),

        0 0 18px
        rgba(255,193,58,.70),

        0 0 38px
        rgba(255,193,58,.30),

        0 9px 22px
        rgba(0,0,0,.58)

        !important;

    z-index:
        30 !important;

}


/* END Grid MAP - SEARCH SELECT MEMORY V2 */


/* ============================================================
   Grid MAP - READABLE SMALL REGION BOXES V1

   Visual box sizes only.

   1x1 = minimum 132 x 102
   2x2 = minimum 174 x 144
   3x3 and larger = untouched
   ============================================================ */


#grid-map .map-region.australia-small-region-card{

    box-sizing:
        border-box !important;

}


/*
   Make sure the region NAME is allowed to use the new width
   instead of keeping the tiny-region ellipsis width.
*/

#grid-map
.map-region.australia-small-region-card
[class*="name"]{

    max-width:
        100% !important;

    text-overflow:
        clip !important;

}


#grid-map
.map-region.australia-small-region-card
[class*="title"]{

    max-width:
        100% !important;

    text-overflow:
        clip !important;

}


/*
   Keep all text contained cleanly inside the enlarged card.
*/

#grid-map
.map-region.australia-small-region-card{

    overflow:
        hidden !important;

}


/*
   Don't let child text retain an old tiny maximum width.
*/

#grid-map
.map-region.australia-small-region-card
> *{

    max-width:
        100%;

}


/* END Grid MAP - READABLE SMALL REGION BOXES V1 */


/* ============================================================
   Grid MAP - FULL SCREEN MAP V1
   ============================================================ */


/* ------------------------------------------------------------
   FULL SCREEN ENTRY BUTTON
   ------------------------------------------------------------ */

.gridmap-fullscreen-button{

    display:inline-flex;

    align-items:center;
    justify-content:center;

    gap:7px;

    min-height:34px;

    padding:0 14px;

    margin-left:8px;

    border:
        1px solid rgba(255,193,58,.48);

    border-radius:7px;

    background:
        linear-gradient(
            180deg,
            rgba(54,41,18,.98),
            rgba(18,16,12,.98)
        );

    color:#ffc13a;

    font-size:11px;
    font-weight:850;

    letter-spacing:.045em;

    cursor:pointer;

    box-shadow:
        inset 0 1px rgba(255,255,255,.05),
        0 5px 14px rgba(0,0,0,.24);

    transition:
        border-color .15s ease,
        background .15s ease,
        color .15s ease,
        transform .15s ease;

}


.gridmap-fullscreen-button:hover{

    border-color:#ffd66d;

    color:#fff2be;

    background:
        linear-gradient(
            180deg,
            rgba(83,61,21,.98),
            rgba(29,22,12,.98)
        );

    transform:
        translateY(-1px);

}


/* ------------------------------------------------------------
   FULL SCREEN MAP ITSELF
   ------------------------------------------------------------ */

.gridmap-scroll:fullscreen{

    width:100vw !important;
    height:100vh !important;

    min-width:100vw !important;
    min-height:100vh !important;

    max-width:none !important;
    max-height:none !important;

    margin:0 !important;

    border:none !important;
    border-radius:0 !important;

    overflow:hidden !important;

    background-color:#06131a !important;

}


/*
   Chromium / older browser compatibility
*/

.gridmap-scroll:-webkit-full-screen{

    width:100vw !important;
    height:100vh !important;

    min-width:100vw !important;
    min-height:100vh !important;

    max-width:none !important;
    max-height:none !important;

    margin:0 !important;

    border:none !important;
    border-radius:0 !important;

    overflow:hidden !important;

}


/* ------------------------------------------------------------
   FULLSCREEN FLOATING TOOLBAR
   ------------------------------------------------------------ */

.gridmap-fullscreen-dock{

    position:absolute;

    top:16px;
    left:16px;
    right:16px;

    z-index:10000;

    display:none;

    align-items:center;

    gap:10px;

    flex-wrap:wrap;

    padding:9px 10px;

    pointer-events:none;

}


.gridmap-scroll:fullscreen
.gridmap-fullscreen-dock{

    display:flex;

}


.gridmap-scroll:-webkit-full-screen
.gridmap-fullscreen-dock{

    display:flex;

}


.gridmap-fullscreen-dock > *{

    pointer-events:auto;

}


/* ------------------------------------------------------------
   FULLSCREEN TITLE PLATE
   ------------------------------------------------------------ */

.gridmap-fullscreen-label{

    display:flex;

    align-items:center;

    gap:8px;

    min-height:34px;

    padding:0 13px;

    border:
        1px solid rgba(255,193,58,.34);

    border-radius:7px;

    background:
        rgba(3,9,13,.91);

    color:#f4f7f8;

    font-size:11px;
    font-weight:800;

    letter-spacing:.05em;

    box-shadow:
        0 5px 18px rgba(0,0,0,.32);

    backdrop-filter:
        blur(5px);

}


.gridmap-fullscreen-label strong{

    color:#ffc13a;

}


/* ------------------------------------------------------------
   EXIT BUTTON
   ------------------------------------------------------------ */

.gridmap-fullscreen-exit{

    min-height:34px;

    padding:0 14px;

    border:
        1px solid rgba(255,193,58,.58);

    border-radius:7px;

    background:
        rgba(14,12,9,.94);

    color:#ffc13a;

    font-size:11px;
    font-weight:900;

    letter-spacing:.05em;

    cursor:pointer;

    box-shadow:
        0 5px 18px rgba(0,0,0,.32);

}


.gridmap-fullscreen-exit:hover{

    border-color:#ffe095;

    color:#fff5cc;

}


/* ------------------------------------------------------------
   SEARCH BAR WHILE FULLSCREEN
   ------------------------------------------------------------ */

.gridmap-fullscreen-dock
.gridmap-region-search{

    margin-left:0 !important;

    background:
        rgba(3,9,13,.93) !important;

    box-shadow:
        0 5px 18px rgba(0,0,0,.32) !important;

    backdrop-filter:
        blur(5px);

}


/* ------------------------------------------------------------
   ENSURE REGION BOXES REMAIN ABOVE WATER
   ------------------------------------------------------------ */

.gridmap-scroll:fullscreen
#grid-map{

    z-index:3 !important;

}


.gridmap-scroll:fullscreen
.map-region{

    z-index:5;

}


.gridmap-scroll:fullscreen
.map-region.gridmap-selected-region{

    z-index:30 !important;

}


/* END Grid MAP - FULL SCREEN MAP V1 */


/* ============================================================
   Grid MAP - REGION HOVER INFO V1
   ============================================================ */

.gridmap-region-hover{

    position:fixed;

    z-index:2147483000;

    display:none;

    width:248px;

    padding:11px 12px 12px;

    border:
        1px solid rgba(255,193,58,.62);

    border-radius:9px;

    background:
        linear-gradient(
            180deg,
            rgba(10,18,23,.97),
            rgba(4,9,13,.98)
        );

    color:#eaf0f3;

    box-shadow:
        0 12px 30px rgba(0,0,0,.48),
        inset 0 1px rgba(255,255,255,.045);

    pointer-events:none;

    backdrop-filter:
        blur(6px);

}


.gridmap-region-hover.is-visible{

    display:block;

}


.gridmap-hover-header{

    display:flex;

    align-items:center;

    gap:8px;

    padding-bottom:8px;

    margin-bottom:8px;

    border-bottom:
        1px solid rgba(255,193,58,.16);

}


.gridmap-hover-dot{

    flex:0 0 auto;

    width:8px;
    height:8px;

    border-radius:50%;

    background:#72818a;

    box-shadow:
        0 0 7px rgba(114,129,138,.55);

}


.gridmap-hover-dot.online{

    background:#26c5ff;

    box-shadow:
        0 0 9px rgba(38,197,255,.75);

}


.gridmap-hover-dot.warning{

    background:#ffb52d;

    box-shadow:
        0 0 9px rgba(255,181,45,.72);

}


.gridmap-hover-dot.offline{

    background:#ff544c;

    box-shadow:
        0 0 9px rgba(255,84,76,.72);

}


.gridmap-hover-name{

    min-width:0;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    color:#ffffff;

    font-size:13px;
    font-weight:900;

    letter-spacing:.025em;

}


.gridmap-hover-grid{

    display:grid;

    grid-template-columns:
        76px minmax(0,1fr);

    gap:5px 9px;

    align-items:center;

}


.gridmap-hover-label{

    color:#71808a;

    font-size:9px;
    font-weight:850;

    letter-spacing:.07em;

}


.gridmap-hover-value{

    min-width:0;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    color:#dce5e9;

    font-size:10px;
    font-weight:700;

}


.gridmap-hover-value.highlight{

    color:#ffc13a;

}


.gridmap-hover-footer{

    margin-top:9px;
    padding-top:7px;

    border-top:
        1px solid rgba(255,193,58,.12);

    color:#65747e;

    font-size:9px;
    font-weight:700;

    text-align:center;

    letter-spacing:.04em;

}


/* END Grid MAP - REGION HOVER INFO V1 */


/* ============================================================
   Grid MAP - FULLSCREEN HOVER FIX V2
   ============================================================ */

.gridmap-scroll:fullscreen
.gridmap-region-hover{

    z-index:2147483647 !important;

}


.gridmap-scroll:-webkit-full-screen
.gridmap-region-hover{

    z-index:2147483647 !important;

}


/* END Grid MAP - FULLSCREEN HOVER FIX V2 */


/* ============================================================
   Grid MAP - COMPASS V2
   ============================================================ */

.gridmap-compass{

    position:absolute !important;

    right:20px !important;
    bottom:24px !important;

    width:100px;
    height:100px;

    z-index:12000 !important;

    pointer-events:none !important;
    user-select:none !important;

    filter:
        drop-shadow(
            0 8px 14px
            rgba(0,0,0,.62)
        );

}


/* ============================================================
   OUTER COMPASS
   ============================================================ */

.gridmap-compass-ring{

    position:absolute;

    inset:0;

    border:
        2px solid rgba(255,193,58,.68);

    border-radius:50%;

    background:

        radial-gradient(
            circle at center,
            rgba(11,23,30,.96) 0%,
            rgba(5,13,18,.97) 60%,
            rgba(2,7,10,.99) 100%
        );

    box-shadow:

        inset 0 0 0 4px
        rgba(255,193,58,.045),

        inset 0 0 24px
        rgba(0,0,0,.80),

        0 0 18px
        rgba(255,193,58,.16);

}


/* INNER GOLD RING */

.gridmap-compass-ring::before{

    content:"";

    position:absolute;

    inset:11px;

    border:
        1px solid rgba(255,193,58,.22);

    border-radius:50%;

}


/* NORTH/SOUTH LINE */

.gridmap-compass-ring::after{

    content:"";

    position:absolute;

    left:49px;
    top:16px;

    width:1px;
    height:68px;

    background:

        linear-gradient(
            180deg,
            rgba(255,193,58,.42),
            rgba(255,193,58,.05),
            rgba(255,193,58,.42)
        );

}


/* EAST/WEST LINE */

.gridmap-compass-cross{

    position:absolute;

    left:16px;
    top:49px;

    width:68px;
    height:1px;

    background:

        linear-gradient(
            90deg,
            rgba(255,193,58,.42),
            rgba(255,193,58,.05),
            rgba(255,193,58,.42)
        );

}


/* ============================================================
   COMPASS NEEDLE
   ============================================================ */

.gridmap-compass-needle-north{

    position:absolute;

    left:50%;
    top:25px;

    width:0;
    height:0;

    transform:
        translateX(-50%);

    border-left:
        7px solid transparent;

    border-right:
        7px solid transparent;

    border-bottom:
        25px solid #ffc13a;

    filter:
        drop-shadow(
            0 0 5px
            rgba(255,193,58,.60)
        );

}


.gridmap-compass-needle-south{

    position:absolute;

    left:50%;
    top:50px;

    width:0;
    height:0;

    transform:
        translateX(-50%);

    border-left:
        6px solid transparent;

    border-right:
        6px solid transparent;

    border-top:
        22px solid #74838b;

}


/* CENTRE PIN */

.gridmap-compass-centre{

    position:absolute;

    left:50%;
    top:50%;

    width:9px;
    height:9px;

    transform:
        translate(-50%,-50%);

    border:
        1px solid #ffd66e;

    border-radius:50%;

    background:#10191e;

    box-shadow:

        0 0 0 2px
        rgba(0,0,0,.45),

        0 0 8px
        rgba(255,193,58,.65);

}


/* ============================================================
   DIRECTION LETTERS
   ============================================================ */

.gridmap-compass-direction{

    position:absolute;

    color:#9faeb6;

    font-family:
        Arial,
        sans-serif;

    font-size:10px;
    font-weight:900;

    line-height:1;

    text-shadow:
        0 2px 4px #000;

}


.gridmap-compass-north{

    left:50%;
    top:6px;

    transform:
        translateX(-50%);

    color:#ffc13a;

    font-size:13px;

}


.gridmap-compass-south{

    left:50%;
    bottom:7px;

    transform:
        translateX(-50%);

}


.gridmap-compass-east{

    right:7px;
    top:50%;

    transform:
        translateY(-50%);

}


.gridmap-compass-west{

    left:7px;
    top:50%;

    transform:
        translateY(-50%);

}


/* ============================================================
   GRID NORTH LABEL
   ============================================================ */

.gridmap-compass-caption{

    position:absolute;

    left:50%;
    bottom:-18px;

    transform:
        translateX(-50%);

    white-space:nowrap;

    color:
        rgba(210,221,227,.76);

    font-size:8px;
    font-weight:800;

    letter-spacing:.10em;

    text-shadow:
        0 2px 4px #000;

}


/* ============================================================
   FULLSCREEN
   ============================================================ */

.gridmap-scroll:fullscreen
.gridmap-compass{

    right:28px !important;
    bottom:36px !important;

    transform:
        scale(1.15);

    transform-origin:
        bottom right;

}


.gridmap-scroll:-webkit-full-screen
.gridmap-compass{

    right:28px !important;
    bottom:36px !important;

    transform:
        scale(1.15);

    transform-origin:
        bottom right;

}


/* END Grid MAP - COMPASS V2 */


/* ============================================================
   Grid MAP - YOU ARE HERE V1
   ============================================================ */


/* ------------------------------------------------------------
   LIVE AVATAR MARKER
   ------------------------------------------------------------ */

.australia-you-are-here{

    position:absolute;

    z-index:100000 !important;

    width:14px;
    height:14px;

    transform:
        translate(-50%,-50%);

    pointer-events:none !important;

    user-select:none !important;

}


/* OUTER LIVE PULSE */

.australia-you-are-here-pulse{

    position:absolute;

    left:50%;
    top:50%;

    width:24px;
    height:24px;

    transform:
        translate(-50%,-50%);

    border:
        2px solid
        rgba(255,193,58,.92);

    border-radius:50%;

    box-shadow:
        0 0 12px
        rgba(255,193,58,.65);

    animation:
        australiaYouAreHerePulse
        1.6s
        ease-out
        infinite;

}


/* INNER DOT */

.australia-you-are-here-dot{

    position:absolute;

    left:50%;
    top:50%;

    width:12px;
    height:12px;

    transform:
        translate(-50%,-50%);

    border:
        2px solid #fff3bf;

    border-radius:50%;

    background:#ffc13a;

    box-shadow:

        0 0 0 2px
        rgba(0,0,0,.70),

        0 0 12px
        rgba(255,193,58,.95);

}


/* CENTRE */

.australia-you-are-here-dot::after{

    content:"";

    position:absolute;

    left:50%;
    top:50%;

    width:4px;
    height:4px;

    transform:
        translate(-50%,-50%);

    border-radius:50%;

    background:#ffffff;

}


/* ------------------------------------------------------------
   LABEL
   ------------------------------------------------------------ */

.australia-you-are-here-label{

    position:absolute;

    left:50%;
    bottom:18px;

    transform:
        translateX(-50%);

    min-width:126px;

    padding:
        6px 9px;

    border:
        1px solid
        rgba(255,193,58,.76);

    border-radius:7px;

    background:
        rgba(3,9,13,.95);

    color:#ffffff;

    text-align:center;

    white-space:nowrap;

    box-shadow:
        0 6px 16px
        rgba(0,0,0,.55);

}


.australia-you-are-here-title{

    display:block;

    color:#ffc13a;

    font-size:9px;
    font-weight:950;

    letter-spacing:.09em;

}


.australia-you-are-here-name{

    display:block;

    margin-top:2px;

    color:#ffffff;

    font-size:10px;
    font-weight:850;

}


.australia-you-are-here-position{

    display:block;

    margin-top:2px;

    color:#84939b;

    font-size:8px;
    font-weight:700;

}


/* LABEL POINTER */

.australia-you-are-here-label::after{

    content:"";

    position:absolute;

    left:50%;
    bottom:-6px;

    width:10px;
    height:10px;

    transform:
        translateX(-50%)
        rotate(45deg);

    border-right:
        1px solid
        rgba(255,193,58,.76);

    border-bottom:
        1px solid
        rgba(255,193,58,.76);

    background:
        rgba(3,9,13,.95);

}


/* ------------------------------------------------------------
   PULSE
   ------------------------------------------------------------ */

@keyframes australiaYouAreHerePulse{

    0%{

        opacity:.95;

        transform:
            translate(-50%,-50%)
            scale(.60);

    }

    70%{

        opacity:.10;

        transform:
            translate(-50%,-50%)
            scale(1.55);

    }

    100%{

        opacity:0;

        transform:
            translate(-50%,-50%)
            scale(1.70);

    }

}


/* END Grid MAP - YOU ARE HERE V1 */


/* ============================================================
   Grid MAP - LIVE AVATAR DOTS V1
   ============================================================ */


/* ------------------------------------------------------------
   OTHER LIVE AVATARS
   ------------------------------------------------------------ */

.australia-grid-agent{

    position:absolute;

    z-index:90000 !important;

    width:10px;
    height:10px;

    transform:
        translate(-50%,-50%);

    pointer-events:auto;

    cursor:default;

    transition:
        left .65s linear,
        top .65s linear;

}


/* ------------------------------------------------------------
   AVATAR DOT
   ------------------------------------------------------------ */

.australia-grid-agent-dot{

    position:absolute;

    left:50%;
    top:50%;

    width:9px;
    height:9px;

    transform:
        translate(-50%,-50%);

    border:
        2px solid
        rgba(226,255,229,.96);

    border-radius:50%;

    background:#66ff77;

    box-shadow:

        0 0 0 2px
        rgba(0,0,0,.72),

        0 0 9px
        rgba(102,255,119,.95);

}


/* ------------------------------------------------------------
   PULSE
   ------------------------------------------------------------ */

.australia-grid-agent-pulse{

    position:absolute;

    left:50%;
    top:50%;

    width:18px;
    height:18px;

    transform:
        translate(-50%,-50%);

    border:
        1px solid
        rgba(102,255,119,.78);

    border-radius:50%;

    animation:
        australiaGridAgentPulse
        2.2s
        ease-out
        infinite;

}


@keyframes australiaGridAgentPulse{

    0%{

        opacity:.75;

        transform:
            translate(-50%,-50%)
            scale(.55);

    }

    75%{

        opacity:.08;

        transform:
            translate(-50%,-50%)
            scale(1.45);

    }

    100%{

        opacity:0;

        transform:
            translate(-50%,-50%)
            scale(1.65);

    }

}


/* ------------------------------------------------------------
   HOVER INFORMATION
   ------------------------------------------------------------ */

.australia-grid-agent-label{

    position:absolute;

    left:50%;
    bottom:17px;

    transform:
        translateX(-50%);

    min-width:150px;

    padding:
        7px 9px;

    border:
        1px solid
        rgba(102,255,119,.72);

    border-radius:7px;

    background:
        rgba(3,10,12,.97);

    box-shadow:
        0 7px 18px
        rgba(0,0,0,.62);

    text-align:center;

    white-space:nowrap;

    opacity:0;

    visibility:hidden;

    pointer-events:none;

    transition:
        opacity .12s ease,
        visibility .12s ease;

}


.australia-grid-agent:hover
.australia-grid-agent-label{

    opacity:1;

    visibility:visible;

}


.australia-grid-agent-name{

    display:block;

    color:#8dff98;

    font-size:10px;
    font-weight:900;

}


.australia-grid-agent-region{

    display:block;

    margin-top:2px;

    color:#ffffff;

    font-size:9px;
    font-weight:750;

}


.australia-grid-agent-position{

    display:block;

    margin-top:2px;

    color:#89979d;

    font-size:8px;
    font-weight:700;

}


/* LABEL ARROW */

.australia-grid-agent-label::after{

    content:"";

    position:absolute;

    left:50%;
    bottom:-5px;

    width:8px;
    height:8px;

    transform:
        translateX(-50%)
        rotate(45deg);

    border-right:
        1px solid
        rgba(102,255,119,.72);

    border-bottom:
        1px solid
        rgba(102,255,119,.72);

    background:
        rgba(3,10,12,.97);

}


/* ------------------------------------------------------------
   FLYING AVATAR
   ------------------------------------------------------------ */

.australia-grid-agent.is-flying
.australia-grid-agent-dot{

    box-shadow:

        0 0 0 2px
        rgba(0,0,0,.72),

        0 0 13px
        rgba(102,255,119,1);

}


/* END Grid MAP - LIVE AVATAR DOTS V1 */


/* ============================================================
   Grid MAP - REAL COORDINATE SCALE V1
   ============================================================ */


/* ------------------------------------------------------------
   MASTER SCALE LAYER
   ------------------------------------------------------------ */

#gridmap-coordinate-scale{

    position:absolute;

    inset:0;

    z-index:70000;

    pointer-events:none !important;

    overflow:hidden;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}


/* ------------------------------------------------------------
   EDGE SHADE
   ------------------------------------------------------------ */

.gridmap-coordinate-top,
.gridmap-coordinate-bottom{

    position:absolute;

    left:0;
    right:0;

    height:30px;

    pointer-events:none;

}


.gridmap-coordinate-top{

    top:0;

    background:
        linear-gradient(
            to bottom,
            rgba(2,8,12,.94),
            rgba(2,8,12,.52),
            transparent
        );

}


.gridmap-coordinate-bottom{

    bottom:0;

    background:
        linear-gradient(
            to top,
            rgba(2,8,12,.94),
            rgba(2,8,12,.52),
            transparent
        );

}


.gridmap-coordinate-left,
.gridmap-coordinate-right{

    position:absolute;

    top:0;
    bottom:0;

    width:42px;

    pointer-events:none;

}


.gridmap-coordinate-left{

    left:0;

    background:
        linear-gradient(
            to right,
            rgba(2,8,12,.94),
            rgba(2,8,12,.48),
            transparent
        );

}


.gridmap-coordinate-right{

    right:0;

    background:
        linear-gradient(
            to left,
            rgba(2,8,12,.94),
            rgba(2,8,12,.48),
            transparent
        );

}


/* ------------------------------------------------------------
   X AXIS LABELS
   ------------------------------------------------------------ */

.gridmap-coordinate-x{

    position:absolute;

    top:5px;

    transform:
        translateX(-50%);

    min-width:28px;

    color:#ffc13a;

    font-size:9px;

    font-weight:900;

    line-height:14px;

    text-align:center;

    text-shadow:

        0 1px 2px
        rgba(0,0,0,1),

        0 0 5px
        rgba(255,193,58,.45);

    white-space:nowrap;

}


.gridmap-coordinate-x.bottom{

    top:auto;

    bottom:5px;

}


/* X TICK */

.gridmap-coordinate-x::after{

    content:"";

    position:absolute;

    left:50%;

    top:-5px;

    width:1px;

    height:5px;

    background:
        rgba(255,193,58,.78);

}


.gridmap-coordinate-x.bottom::after{

    top:auto;

    bottom:-5px;

}


/* ------------------------------------------------------------
   Y AXIS LABELS
   ------------------------------------------------------------ */

.gridmap-coordinate-y{

    position:absolute;

    left:5px;

    transform:
        translateY(-50%);

    width:30px;

    color:#ffc13a;

    font-size:9px;

    font-weight:900;

    line-height:14px;

    text-align:center;

    text-shadow:

        0 1px 2px
        rgba(0,0,0,1),

        0 0 5px
        rgba(255,193,58,.45);

    white-space:nowrap;

}


.gridmap-coordinate-y.right{

    left:auto;

    right:5px;

}


/* Y TICK */

.gridmap-coordinate-y::after{

    content:"";

    position:absolute;

    top:50%;

    left:-5px;

    width:5px;

    height:1px;

    background:
        rgba(255,193,58,.78);

}


.gridmap-coordinate-y.right::after{

    left:auto;

    right:-5px;

}


/* ------------------------------------------------------------
   CORNER AXIS BADGES
   ------------------------------------------------------------ */

.gridmap-coordinate-badge{

    position:absolute;

    z-index:2;

    display:flex;

    align-items:center;

    justify-content:center;

    width:25px;

    height:20px;

    border:
        1px solid
        rgba(255,193,58,.72);

    border-radius:5px;

    background:
        rgba(3,9,13,.94);

    color:#ffc13a;

    font-size:9px;

    font-weight:950;

    letter-spacing:.08em;

    box-shadow:
        0 3px 10px
        rgba(0,0,0,.55);

}


.gridmap-coordinate-badge.x{

    left:8px;
    top:7px;

}


.gridmap-coordinate-badge.y{

    left:8px;
    bottom:7px;

}


/* ------------------------------------------------------------
   SUBTLE GRID EDGE LINES
   ------------------------------------------------------------ */

.gridmap-coordinate-line-x{

    position:absolute;

    top:27px;

    bottom:27px;

    width:1px;

    background:
        linear-gradient(
            to bottom,
            rgba(255,193,58,.24),
            rgba(255,193,58,.055) 18%,
            rgba(255,193,58,.035) 82%,
            rgba(255,193,58,.24)
        );

}


.gridmap-coordinate-line-y{

    position:absolute;

    left:38px;

    right:38px;

    height:1px;

    background:
        linear-gradient(
            to right,
            rgba(255,193,58,.24),
            rgba(255,193,58,.055) 18%,
            rgba(255,193,58,.035) 82%,
            rgba(255,193,58,.24)
        );

}


/* FULLSCREEN */

.gridmap-scroll:fullscreen
#gridmap-coordinate-scale,

.gridmap-scroll:-webkit-full-screen
#gridmap-coordinate-scale{

    position:absolute;

    inset:0;

}


/* END Grid MAP - REAL COORDINATE SCALE V1 */


/* ============================================================
   Grid MAP - ONLINE NOW PANEL V2
   ============================================================ */

#gridmap-online-now-v2{

    position:absolute;

    top:48px;
    right:48px;

    z-index:190000;

    width:285px;

    max-height:
        min(
            420px,
            calc(100% - 105px)
        );

    display:flex;
    flex-direction:column;

    overflow:hidden;

    border:
        1px solid
        rgba(255,193,58,.68);

    border-radius:10px;

    background:
        linear-gradient(
            180deg,
            rgba(4,13,18,.97),
            rgba(2,8,12,.97)
        );

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.62);

    color:#fff;

    pointer-events:auto;

    user-select:none;

}


#gridmap-online-now-v2.is-collapsed{

    width:190px;

}


#gridmap-online-now-v2.is-collapsed
.gridmap-online-v2-body,

#gridmap-online-now-v2.is-collapsed
.gridmap-online-v2-footer{

    display:none;

}


/* HEADER */

.gridmap-online-v2-header{

    display:flex;

    align-items:center;

    gap:8px;

    min-height:44px;

    padding:
        7px 8px
        7px 10px;

    border-bottom:
        1px solid
        rgba(255,193,58,.18);

}


.gridmap-online-v2-live{

    position:relative;

    flex:0 0 auto;

    width:9px;
    height:9px;

    border:
        2px solid #eaffec;

    border-radius:50%;

    background:#65ff77;

    box-shadow:
        0 0 10px
        rgba(101,255,119,.9);

}


.gridmap-online-v2-live::after{

    content:"";

    position:absolute;

    inset:-7px;

    border:
        1px solid
        rgba(101,255,119,.5);

    border-radius:50%;

    animation:
        gridOnlineV2Pulse
        2s
        ease-out
        infinite;

}


@keyframes gridOnlineV2Pulse{

    0%{
        opacity:.8;
        transform:scale(.5);
    }

    75%{
        opacity:.08;
        transform:scale(1.25);
    }

    100%{
        opacity:0;
        transform:scale(1.45);
    }

}


.gridmap-online-v2-heading{

    flex:1 1 auto;

    min-width:0;

}


.gridmap-online-v2-title{

    display:block;

    color:#ffc13a;

    font-size:10px;

    font-weight:950;

    letter-spacing:.09em;

}


.gridmap-online-v2-subtitle{

    display:block;

    margin-top:2px;

    color:#84939b;

    font-size:8px;

    font-weight:700;

}


.gridmap-online-v2-count{

    min-width:28px;

    padding:4px 7px;

    border:
        1px solid
        rgba(101,255,119,.42);

    border-radius:999px;

    background:
        rgba(101,255,119,.10);

    color:#8dff98;

    font-size:10px;

    font-weight:950;

    text-align:center;

}


.gridmap-online-v2-collapse{

    width:27px;
    height:27px;

    border:
        1px solid
        rgba(255,193,58,.38);

    border-radius:6px;

    background:
        rgba(255,193,58,.06);

    color:#ffc13a;

    font-size:15px;

    font-weight:900;

    cursor:pointer;

}


.gridmap-online-v2-collapse:hover{

    background:
        rgba(255,193,58,.15);

}


/* BODY */

.gridmap-online-v2-body{

    flex:1 1 auto;

    min-height:0;

    overflow-y:auto;

    padding:6px;

    scrollbar-width:thin;

    scrollbar-color:
        rgba(255,193,58,.35)
        transparent;

}


.gridmap-online-v2-region{

    margin-bottom:6px;

    overflow:hidden;

    border:
        1px solid
        rgba(255,255,255,.08);

    border-radius:7px;

    background:
        rgba(255,255,255,.025);

}


.gridmap-online-v2-region:last-child{

    margin-bottom:0;

}


.gridmap-online-v2-region-button{

    width:100%;

    display:flex;

    align-items:center;

    gap:7px;

    padding:8px;

    border:0;

    background:
        rgba(255,255,255,.025);

    color:#fff;

    text-align:left;

    cursor:pointer;

}


.gridmap-online-v2-region-button:hover{

    background:
        rgba(255,193,58,.09);

}


.gridmap-online-v2-region-dot{

    width:7px;
    height:7px;

    flex:0 0 auto;

    border-radius:50%;

    background:#65ff77;

    box-shadow:
        0 0 7px
        rgba(101,255,119,.8);

}


.gridmap-online-v2-region-name{

    flex:1 1 auto;

    min-width:0;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    color:#fff;

    font-size:9px;

    font-weight:900;

}


.gridmap-online-v2-region-count{

    color:#8dff98;

    font-size:9px;

    font-weight:950;

}


.gridmap-online-v2-avatar{

    display:flex;

    align-items:center;

    gap:7px;

    padding:
        6px 8px
        6px 22px;

    border-top:
        1px solid
        rgba(255,255,255,.045);

}


.gridmap-online-v2-avatar-dot{

    width:5px;
    height:5px;

    flex:0 0 auto;

    border-radius:50%;

    background:#65ff77;

}


.gridmap-online-v2-avatar-name{

    flex:1 1 auto;

    min-width:0;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    color:#d7dfe3;

    font-size:9px;

    font-weight:700;

}


.gridmap-online-v2-you{

    padding:
        2px 5px;

    border:
        1px solid
        rgba(255,193,58,.55);

    border-radius:4px;

    background:
        rgba(255,193,58,.10);

    color:#ffc13a;

    font-size:7px;

    font-weight:950;

    letter-spacing:.05em;

}


.gridmap-online-v2-empty{

    padding:
        18px 12px;

    color:#84939b;

    font-size:9px;

    line-height:1.5;

    text-align:center;

}


/* FOOTER */

.gridmap-online-v2-footer{

    display:flex;

    justify-content:space-between;

    gap:8px;

    padding:
        6px 9px;

    border-top:
        1px solid
        rgba(255,193,58,.15);

    color:#74838a;

    font-size:7px;

    font-weight:750;

}


.gridmap-online-v2-footer strong{

    color:#8dff98;

}


/* FULLSCREEN */

.gridmap-scroll:fullscreen
#gridmap-online-now-v2,

.gridmap-scroll:-webkit-full-screen
#gridmap-online-now-v2{

    top:52px;

    right:52px;

    max-height:
        calc(100vh - 110px);

}


/* END Grid MAP - ONLINE NOW PANEL V2 */


/* ============================================================
   Grid MAP - MAP LAYERS V1
   ============================================================ */


/* ------------------------------------------------------------
   LAYERS BUTTON
   ------------------------------------------------------------ */

#gridmap-layers-v1{

    position:absolute;

    left:48px;
    bottom:48px;

    z-index:250000;

    pointer-events:auto;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    user-select:none;

}


#gridmap-layers-button-v1{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    min-width:104px;

    height:34px;

    padding:
        0 12px;

    border:
        1px solid
        rgba(255,193,58,.72);

    border-radius:8px;

    background:
        linear-gradient(
            180deg,
            rgba(12,22,28,.98),
            rgba(3,10,14,.98)
        );

    color:#ffc13a;

    box-shadow:
        0 7px 18px
        rgba(0,0,0,.55);

    font-size:9px;

    font-weight:950;

    letter-spacing:.08em;

    cursor:pointer;

}


#gridmap-layers-button-v1:hover{

    border-color:#ffc13a;

    background:
        linear-gradient(
            180deg,
            rgba(35,33,20,.98),
            rgba(8,13,14,.98)
        );

}


.gridmap-layers-icon-v1{

    font-size:15px;

    line-height:1;

}


/* ------------------------------------------------------------
   MENU
   ------------------------------------------------------------ */

#gridmap-layers-menu-v1{

    position:absolute;

    left:0;
    bottom:42px;

    width:260px;

    overflow:hidden;

    border:
        1px solid
        rgba(255,193,58,.68);

    border-radius:10px;

    background:
        linear-gradient(
            180deg,
            rgba(5,14,19,.985),
            rgba(2,8,12,.985)
        );

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.68);

    opacity:0;

    visibility:hidden;

    transform:
        translateY(7px);

    transition:
        opacity .14s ease,
        transform .14s ease,
        visibility .14s ease;

}


#gridmap-layers-v1.is-open
#gridmap-layers-menu-v1{

    opacity:1;

    visibility:visible;

    transform:
        translateY(0);

}


/* ------------------------------------------------------------
   MENU HEADER
   ------------------------------------------------------------ */

.gridmap-layers-header-v1{

    display:flex;

    align-items:center;

    gap:8px;

    padding:
        10px 11px;

    border-bottom:
        1px solid
        rgba(255,193,58,.18);

    background:
        rgba(255,193,58,.035);

}


.gridmap-layers-header-icon-v1{

    color:#ffc13a;

    font-size:15px;

}


.gridmap-layers-header-copy-v1{

    flex:1 1 auto;

}


.gridmap-layers-title-v1{

    display:block;

    color:#ffc13a;

    font-size:10px;

    font-weight:950;

    letter-spacing:.08em;

}


.gridmap-layers-subtitle-v1{

    display:block;

    margin-top:2px;

    color:#7f8e95;

    font-size:7px;

    font-weight:700;

}


/* ------------------------------------------------------------
   LAYER ROW
   ------------------------------------------------------------ */

.gridmap-layer-row-v1{

    display:flex;

    align-items:center;

    gap:9px;

    min-height:34px;

    padding:
        5px 10px;

    border-bottom:
        1px solid
        rgba(255,255,255,.045);

    cursor:pointer;

}


.gridmap-layer-row-v1:hover{

    background:
        rgba(255,193,58,.055);

}


.gridmap-layer-row-v1.is-unavailable{

    opacity:.40;

    cursor:default;

}


.gridmap-layer-copy-v1{

    flex:1 1 auto;

    min-width:0;

}


.gridmap-layer-name-v1{

    display:block;

    color:#e4eaed;

    font-size:9px;

    font-weight:850;

}


.gridmap-layer-note-v1{

    display:block;

    margin-top:1px;

    color:#74838a;

    font-size:7px;

    font-weight:650;

}


/* ------------------------------------------------------------
   SWITCH
   ------------------------------------------------------------ */

.gridmap-layer-switch-v1{

    position:relative;

    flex:0 0 auto;

    width:32px;
    height:17px;

    border:
        1px solid
        rgba(255,255,255,.20);

    border-radius:20px;

    background:
        rgba(255,255,255,.09);

    transition:
        background .12s ease,
        border-color .12s ease;

}


.gridmap-layer-switch-v1::after{

    content:"";

    position:absolute;

    left:2px;
    top:2px;

    width:11px;
    height:11px;

    border-radius:50%;

    background:#8a979c;

    transition:
        left .12s ease,
        background .12s ease,
        box-shadow .12s ease;

}


.gridmap-layer-row-v1.is-on
.gridmap-layer-switch-v1{

    border-color:
        rgba(102,255,119,.50);

    background:
        rgba(102,255,119,.13);

}


.gridmap-layer-row-v1.is-on
.gridmap-layer-switch-v1::after{

    left:17px;

    background:#66ff77;

    box-shadow:
        0 0 7px
        rgba(102,255,119,.80);

}


/* ------------------------------------------------------------
   FOOTER
   ------------------------------------------------------------ */

.gridmap-layers-footer-v1{

    display:flex;

    gap:6px;

    padding:
        8px;

}


.gridmap-layers-reset-v1{

    flex:1 1 auto;

    height:28px;

    border:
        1px solid
        rgba(255,193,58,.35);

    border-radius:6px;

    background:
        rgba(255,193,58,.055);

    color:#ffc13a;

    font-size:8px;

    font-weight:900;

    cursor:pointer;

}


.gridmap-layers-reset-v1:hover{

    border-color:
        rgba(255,193,58,.75);

    background:
        rgba(255,193,58,.12);

}


/* ============================================================
   ACTUAL LAYER VISIBILITY
   ============================================================ */


/* REGION CARDS */

.gridmap-scroll.australia-layer-hide-region-cards
#grid-map
.map-region{

    opacity:0 !important;

    visibility:hidden !important;

    pointer-events:none !important;

}


/* ISLANDS */

.gridmap-scroll.australia-layer-hide-islands
.australia-layer-island-target{

    opacity:0 !important;

    visibility:hidden !important;

}


/* YOU ARE HERE */

.gridmap-scroll.australia-layer-hide-you
#australia-you-are-here{

    display:none !important;

}


/* OTHER AVATARS */

.gridmap-scroll.australia-layer-hide-avatars
.australia-grid-agent{

    display:none !important;

}


/* COORDINATE SCALE */

.gridmap-scroll.australia-layer-hide-coordinates
#gridmap-coordinate-scale{

    display:none !important;

}


/* COMPASS */

.gridmap-scroll.australia-layer-hide-compass
#gridmap-compass{

    display:none !important;

}


/* ONLINE NOW */

.gridmap-scroll.australia-layer-hide-online
#gridmap-online-now-v2,

.gridmap-scroll.australia-layer-hide-online
#gridmap-online-now{

    display:none !important;

}


/* MAP GRID LINES */

.gridmap-scroll.australia-layer-hide-grid-lines
.australia-layer-grid-line-target{

    opacity:0 !important;

    visibility:hidden !important;

}


/* REGION NAMES */

.gridmap-scroll.australia-layer-hide-region-names
.australia-layer-region-name-target{

    opacity:0 !important;

    visibility:hidden !important;

}


/* FULLSCREEN */

.gridmap-scroll:fullscreen
#gridmap-layers-v1,

.gridmap-scroll:-webkit-full-screen
#gridmap-layers-v1{

    left:54px;

    bottom:54px;

}


/* END Grid MAP - MAP LAYERS V1 */


/* ============================================================
   Grid MAP - REGION TRAFFIC HEATMAP V1
   ============================================================ */


/* ------------------------------------------------------------
   HEAT LAYER
   ------------------------------------------------------------ */

#gridmap-traffic-heat-layer-v1{

    position:absolute;

    inset:0;

    z-index:45000;

    pointer-events:none;

}


.australia-grid-traffic-heat-v1{

    position:absolute;

    box-sizing:border-box;

    pointer-events:none;

    border-radius:6px;

    transition:
        left .25s ease,
        top .25s ease,
        width .25s ease,
        height .25s ease,
        background .25s ease,
        box-shadow .25s ease;

}


.australia-grid-traffic-heat-v1.level-1{

    border:
        1px solid
        rgba(255,227,138,.60);

    background:
        rgba(255,227,138,.045);

    box-shadow:
        0 0 12px
        rgba(255,227,138,.32);

}


.australia-grid-traffic-heat-v1.level-2{

    border:
        1px solid
        rgba(255,193,58,.75);

    background:
        rgba(255,193,58,.065);

    box-shadow:
        0 0 17px
        rgba(255,193,58,.46);

}


.australia-grid-traffic-heat-v1.level-3{

    border:
        1px solid
        rgba(255,159,45,.84);

    background:
        rgba(255,159,45,.085);

    box-shadow:
        0 0 22px
        rgba(255,159,45,.55);

}


.australia-grid-traffic-heat-v1.level-4{

    border:
        1px solid
        rgba(255,112,67,.90);

    background:
        rgba(255,112,67,.105);

    box-shadow:
        0 0 27px
        rgba(255,112,67,.64);

}


.australia-grid-traffic-heat-v1.level-5{

    border:
        2px solid
        rgba(255,61,129,.94);

    background:
        rgba(255,61,129,.125);

    box-shadow:
        0 0 34px
        rgba(255,61,129,.75);

}


/* ------------------------------------------------------------
   TRAFFIC CONTROL
   ------------------------------------------------------------ */

#gridmap-traffic-control-v1{

    position:absolute;

    left:168px;
    bottom:48px;

    z-index:260000;

    pointer-events:auto;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    user-select:none;

}


#gridmap-traffic-button-v1{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    min-width:116px;

    height:34px;

    padding:
        0 11px;

    border:
        1px solid
        rgba(255,193,58,.72);

    border-radius:8px;

    background:
        linear-gradient(
            180deg,
            rgba(13,22,28,.98),
            rgba(3,10,14,.98)
        );

    color:#ffc13a;

    box-shadow:
        0 7px 18px
        rgba(0,0,0,.55);

    font-size:9px;

    font-weight:950;

    letter-spacing:.06em;

    cursor:pointer;

}


#gridmap-traffic-button-v1:hover{

    border-color:#ffc13a;

    background:
        linear-gradient(
            180deg,
            rgba(36,32,19,.98),
            rgba(8,13,14,.98)
        );

}


.gridmap-traffic-flame-v1{

    font-size:14px;

}


.gridmap-traffic-mode-v1{

    color:#ffffff;

    font-size:8px;

}


/* ------------------------------------------------------------
   TRAFFIC PANEL
   ------------------------------------------------------------ */

#gridmap-traffic-panel-v1{

    position:absolute;

    left:0;
    bottom:42px;

    width:305px;

    max-height:
        min(
            470px,
            calc(100vh - 150px)
        );

    display:flex;

    flex-direction:column;

    overflow:hidden;

    border:
        1px solid
        rgba(255,193,58,.68);

    border-radius:10px;

    background:
        linear-gradient(
            180deg,
            rgba(5,14,19,.985),
            rgba(2,8,12,.985)
        );

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.68);

    opacity:0;

    visibility:hidden;

    transform:
        translateY(7px);

    transition:
        opacity .14s ease,
        transform .14s ease,
        visibility .14s ease;

}


#gridmap-traffic-control-v1.is-open
#gridmap-traffic-panel-v1{

    opacity:1;

    visibility:visible;

    transform:
        translateY(0);

}


/* HEADER */

.gridmap-traffic-header-v1{

    padding:
        10px 11px;

    border-bottom:
        1px solid
        rgba(255,193,58,.18);

}


.gridmap-traffic-title-v1{

    display:block;

    color:#ffc13a;

    font-size:10px;

    font-weight:950;

    letter-spacing:.08em;

}


.gridmap-traffic-subtitle-v1{

    display:block;

    margin-top:2px;

    color:#7f8e95;

    font-size:7px;

    font-weight:700;

}


/* WINDOWS */

.gridmap-traffic-windows-v1{

    display:grid;

    grid-template-columns:
        repeat(5,1fr);

    gap:4px;

    padding:7px;

    border-bottom:
        1px solid
        rgba(255,255,255,.055);

}


.gridmap-traffic-window-v1{

    height:28px;

    border:
        1px solid
        rgba(255,255,255,.12);

    border-radius:5px;

    background:
        rgba(255,255,255,.035);

    color:#8d9ba1;

    font-size:8px;

    font-weight:900;

    cursor:pointer;

}


.gridmap-traffic-window-v1:hover{

    border-color:
        rgba(255,193,58,.48);

    color:#ffc13a;

}


.gridmap-traffic-window-v1.is-active{

    border-color:
        rgba(255,193,58,.76);

    background:
        rgba(255,193,58,.11);

    color:#ffc13a;

}


.gridmap-traffic-window-v1.is-off{

    color:#ff8f8f;

}


/* LEGEND */

.gridmap-traffic-legend-v1{

    display:flex;

    align-items:center;

    gap:4px;

    padding:
        7px 10px;

    color:#74838a;

    font-size:7px;

    font-weight:700;

}


.gridmap-traffic-legend-block-v1{

    width:18px;
    height:6px;

    border-radius:4px;

}


.gridmap-traffic-legend-block-v1.l1{
    background:#ffe38a;
}

.gridmap-traffic-legend-block-v1.l2{
    background:#ffc13a;
}

.gridmap-traffic-legend-block-v1.l3{
    background:#ff9f2d;
}

.gridmap-traffic-legend-block-v1.l4{
    background:#ff7043;
}

.gridmap-traffic-legend-block-v1.l5{
    background:#ff3d81;
}


/* SUMMARY */

.gridmap-traffic-summary-v1{

    display:flex;

    justify-content:space-between;

    gap:8px;

    padding:
        5px 10px
        8px;

    color:#7d8b91;

    font-size:7px;

    font-weight:750;

}


.gridmap-traffic-summary-v1 strong{

    color:#ffffff;

}


/* RANKING */

.gridmap-traffic-ranking-v1{

    flex:1 1 auto;

    min-height:0;

    overflow-y:auto;

    padding:
        0 7px
        7px;

    scrollbar-width:thin;

    scrollbar-color:
        rgba(255,193,58,.35)
        transparent;

}


.gridmap-traffic-row-v1{

    width:100%;

    display:flex;

    align-items:center;

    gap:8px;

    padding:
        7px 8px;

    margin-top:4px;

    border:
        1px solid
        rgba(255,255,255,.065);

    border-radius:6px;

    background:
        rgba(255,255,255,.025);

    color:#ffffff;

    font-family:inherit;

    text-align:left;

    cursor:pointer;

}


.gridmap-traffic-row-v1:hover{

    border-color:
        rgba(255,193,58,.42);

    background:
        rgba(255,193,58,.055);

}


.gridmap-traffic-rank-v1{

    flex:0 0 auto;

    width:19px;

    color:#ffc13a;

    font-size:8px;

    font-weight:950;

}


.gridmap-traffic-row-copy-v1{

    flex:1 1 auto;

    min-width:0;

}


.gridmap-traffic-region-v1{

    display:block;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    color:#ffffff;

    font-size:9px;

    font-weight:900;

}


.gridmap-traffic-meta-v1{

    display:block;

    margin-top:2px;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    color:#7f8e95;

    font-size:7px;

    font-weight:700;

}


.gridmap-traffic-avg-v1{

    flex:0 0 auto;

    color:#ffc13a;

    font-size:9px;

    font-weight:950;

    text-align:right;

}


.gridmap-traffic-empty-v1{

    padding:
        24px 14px;

    color:#829096;

    font-size:9px;

    font-weight:700;

    line-height:1.5;

    text-align:center;

}


/* FOOTER */

.gridmap-traffic-footer-v1{

    padding:
        7px 10px;

    border-top:
        1px solid
        rgba(255,193,58,.13);

    color:#65747a;

    font-size:7px;

    font-weight:700;

    line-height:1.4;

}


/* FULLSCREEN */

.gridmap-scroll:fullscreen
#gridmap-traffic-control-v1,

.gridmap-scroll:-webkit-full-screen
#gridmap-traffic-control-v1{

    left:176px;

    bottom:54px;

}


/* END Grid MAP - REGION TRAFFIC HEATMAP V1 */


/* ============================================================
   Grid MAP - REGION UPTIME V1
   ============================================================ */


#gridmap-uptime-control-v1{

    position:absolute;

    left:296px;
    bottom:48px;

    z-index:270000;

    pointer-events:auto;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    user-select:none;

}


#gridmap-uptime-button-v1{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    min-width:116px;

    height:34px;

    padding:
        0 11px;

    border:
        1px solid
        rgba(255,193,58,.72);

    border-radius:8px;

    background:
        linear-gradient(
            180deg,
            rgba(13,22,28,.98),
            rgba(3,10,14,.98)
        );

    color:#ffc13a;

    box-shadow:
        0 7px 18px
        rgba(0,0,0,.55);

    font-size:9px;

    font-weight:950;

    letter-spacing:.06em;

    cursor:pointer;

}


#gridmap-uptime-button-v1:hover{

    border-color:#ffc13a;

    background:
        rgba(255,193,58,.10);

}


.gridmap-uptime-heart-v1{

    font-size:13px;

    color:#66ff77;

}


.gridmap-uptime-mode-v1{

    color:#ffffff;

    font-size:8px;

}


/* PANEL */

#gridmap-uptime-panel-v1{

    position:absolute;

    left:0;
    bottom:42px;

    width:410px;

    max-height:
        min(
            540px,
            calc(100vh - 150px)
        );

    display:flex;

    flex-direction:column;

    overflow:hidden;

    border:
        1px solid
        rgba(255,193,58,.68);

    border-radius:10px;

    background:
        linear-gradient(
            180deg,
            rgba(5,14,19,.99),
            rgba(2,8,12,.99)
        );

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.70);

    opacity:0;

    visibility:hidden;

    transform:
        translateY(7px);

    transition:
        opacity .14s ease,
        transform .14s ease,
        visibility .14s ease;

}


#gridmap-uptime-control-v1.is-open
#gridmap-uptime-panel-v1{

    opacity:1;

    visibility:visible;

    transform:
        translateY(0);

}


/* HEADER */

.gridmap-uptime-header-v1{

    padding:
        10px 11px;

    border-bottom:
        1px solid
        rgba(255,193,58,.18);

}


.gridmap-uptime-title-v1{

    display:block;

    color:#ffc13a;

    font-size:10px;

    font-weight:950;

    letter-spacing:.08em;

}


.gridmap-uptime-subtitle-v1{

    display:block;

    margin-top:2px;

    color:#7f8e95;

    font-size:7px;

    font-weight:700;

}


/* TIME BUTTONS */

.gridmap-uptime-windows-v1{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:4px;

    padding:7px;

    border-bottom:
        1px solid
        rgba(255,255,255,.05);

}


.gridmap-uptime-window-v1{

    height:28px;

    border:
        1px solid
        rgba(255,255,255,.12);

    border-radius:5px;

    background:
        rgba(255,255,255,.035);

    color:#8d9ba1;

    font-size:8px;

    font-weight:900;

    cursor:pointer;

}


.gridmap-uptime-window-v1.is-active{

    border-color:
        rgba(255,193,58,.78);

    background:
        rgba(255,193,58,.11);

    color:#ffc13a;

}


/* LEGEND */

.gridmap-uptime-legend-v1{

    display:flex;

    align-items:center;

    gap:10px;

    padding:
        7px 10px;

    color:#7b898f;

    font-size:7px;

    font-weight:750;

}


.gridmap-uptime-legend-item-v1{

    display:flex;

    align-items:center;

    gap:4px;

}


.gridmap-uptime-dot-v1{

    width:7px;
    height:7px;

    border-radius:50%;

}


.gridmap-uptime-dot-v1.online{
    background:#49b9ff;
    box-shadow:0 0 6px rgba(73,185,255,.75);
}

.gridmap-uptime-dot-v1.warning{
    background:#ffc13a;
    box-shadow:0 0 6px rgba(255,193,58,.70);
}

.gridmap-uptime-dot-v1.offline{
    background:#ff5252;
    box-shadow:0 0 6px rgba(255,82,82,.70);
}

.gridmap-uptime-dot-v1.unknown{
    background:#66747a;
}


/* REGION LIST */

.gridmap-uptime-list-v1{

    flex:1 1 auto;

    min-height:0;

    overflow-y:auto;

    padding:
        0 7px
        7px;

    scrollbar-width:thin;

    scrollbar-color:
        rgba(255,193,58,.35)
        transparent;

}


.gridmap-uptime-region-v1{

    margin-top:5px;

    padding:
        8px;

    border:
        1px solid
        rgba(255,255,255,.07);

    border-radius:7px;

    background:
        rgba(255,255,255,.025);

}


.gridmap-uptime-region-top-v1{

    display:flex;

    align-items:center;

    gap:7px;

}


.gridmap-uptime-region-button-v1{

    flex:1 1 auto;

    min-width:0;

    border:0;

    padding:0;

    background:none;

    color:#ffffff;

    font-family:inherit;

    font-size:9px;

    font-weight:900;

    text-align:left;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    cursor:pointer;

}


.gridmap-uptime-region-button-v1:hover{

    color:#ffc13a;

}


.gridmap-uptime-status-v1{

    flex:0 0 auto;

    font-size:8px;

    font-weight:950;

}


.gridmap-uptime-status-v1.online{
    color:#5cc1ff;
}

.gridmap-uptime-status-v1.warning{
    color:#ffc13a;
}

.gridmap-uptime-status-v1.offline{
    color:#ff6868;
}

.gridmap-uptime-status-v1.unknown{
    color:#829096;
}


.gridmap-uptime-percent-v1{

    flex:0 0 auto;

    min-width:54px;

    color:#ffffff;

    font-size:10px;

    font-weight:950;

    text-align:right;

}


/* TIMELINE */

.gridmap-uptime-timeline-v1{

    display:grid;

    grid-template-columns:
        repeat(48,1fr);

    gap:1px;

    height:8px;

    margin-top:7px;

    overflow:hidden;

    border-radius:3px;

    background:#11191d;

}


.gridmap-uptime-segment-v1{

    min-width:1px;

    background:#354147;

}


.gridmap-uptime-segment-v1.online{

    background:#49b9ff;

}


.gridmap-uptime-segment-v1.warning{

    background:#ffc13a;

}


.gridmap-uptime-segment-v1.offline{

    background:#ff5252;

}


.gridmap-uptime-segment-v1.unknown{

    background:#283238;

}


/* META */

.gridmap-uptime-meta-v1{

    display:flex;

    flex-wrap:wrap;

    gap:
        3px 12px;

    margin-top:6px;

    color:#79878d;

    font-size:7px;

    font-weight:700;

}


.gridmap-uptime-meta-v1 strong{

    color:#c9d2d6;

    font-weight:850;

}


.gridmap-uptime-empty-v1{

    padding:
        24px 14px;

    color:#829096;

    font-size:9px;

    line-height:1.5;

    text-align:center;

}


/* FOOTER */

.gridmap-uptime-footer-v1{

    padding:
        7px 10px;

    border-top:
        1px solid
        rgba(255,193,58,.13);

    color:#65747a;

    font-size:7px;

    line-height:1.45;

}


/* FULLSCREEN */

.gridmap-scroll:fullscreen
#gridmap-uptime-control-v1,

.gridmap-scroll:-webkit-full-screen
#gridmap-uptime-control-v1{

    left:310px;

    bottom:54px;

}


/* END Grid MAP - REGION UPTIME V1 */


/* ============================================================
   Grid MAP - ALERTS INCIDENTS V1
   ============================================================ */


#gridmap-incidents-control-v1{

    position:absolute;

    left:424px;
    bottom:48px;

    z-index:280000;

    pointer-events:auto;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    user-select:none;

}


/* ------------------------------------------------------------
   BUTTON
   ------------------------------------------------------------ */

#gridmap-incidents-button-v1{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    min-width:122px;

    height:34px;

    padding:
        0 11px;

    border:
        1px solid
        rgba(102,255,119,.55);

    border-radius:8px;

    background:
        linear-gradient(
            180deg,
            rgba(10,26,18,.98),
            rgba(3,11,8,.98)
        );

    color:#8dff98;

    box-shadow:
        0 7px 18px
        rgba(0,0,0,.55);

    font-size:9px;

    font-weight:950;

    letter-spacing:.05em;

    cursor:pointer;

}


#gridmap-incidents-button-v1:hover{

    border-color:
        rgba(102,255,119,.90);

}


#gridmap-incidents-control-v1.has-warning
#gridmap-incidents-button-v1{

    border-color:
        rgba(255,193,58,.85);

    background:
        linear-gradient(
            180deg,
            rgba(41,31,9,.98),
            rgba(16,11,3,.98)
        );

    color:#ffc13a;

}


#gridmap-incidents-control-v1.has-offline
#gridmap-incidents-button-v1{

    border-color:
        rgba(255,82,82,.90);

    background:
        linear-gradient(
            180deg,
            rgba(48,13,13,.98),
            rgba(18,5,5,.98)
        );

    color:#ff7777;

}


.gridmap-incidents-icon-v1{

    font-size:14px;

    line-height:1;

}


.gridmap-incidents-count-v1{

    min-width:20px;

    padding:
        2px 5px;

    border-radius:999px;

    background:
        rgba(255,255,255,.08);

    color:inherit;

    font-size:8px;

    text-align:center;

}


/* ------------------------------------------------------------
   PANEL
   ------------------------------------------------------------ */

#gridmap-incidents-panel-v1{

    position:absolute;

    left:0;
    bottom:42px;

    width:430px;

    max-height:
        min(
            570px,
            calc(100vh - 150px)
        );

    display:flex;

    flex-direction:column;

    overflow:hidden;

    border:
        1px solid
        rgba(255,193,58,.68);

    border-radius:10px;

    background:
        linear-gradient(
            180deg,
            rgba(5,14,19,.99),
            rgba(2,8,12,.99)
        );

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.72);

    opacity:0;

    visibility:hidden;

    transform:
        translateY(7px);

    transition:
        opacity .14s ease,
        transform .14s ease,
        visibility .14s ease;

}


#gridmap-incidents-control-v1.is-open
#gridmap-incidents-panel-v1{

    opacity:1;

    visibility:visible;

    transform:
        translateY(0);

}


/* ------------------------------------------------------------
   HEADER
   ------------------------------------------------------------ */

.gridmap-incidents-header-v1{

    display:flex;

    align-items:center;

    gap:9px;

    padding:
        10px 11px;

    border-bottom:
        1px solid
        rgba(255,193,58,.18);

}


.gridmap-incidents-header-dot-v1{

    width:9px;
    height:9px;

    flex:0 0 auto;

    border-radius:50%;

    background:#66ff77;

    box-shadow:
        0 0 8px
        rgba(102,255,119,.75);

}


.gridmap-incidents-header-dot-v1.warning{

    background:#ffc13a;

    box-shadow:
        0 0 8px
        rgba(255,193,58,.75);

}


.gridmap-incidents-header-dot-v1.offline{

    background:#ff5252;

    box-shadow:
        0 0 8px
        rgba(255,82,82,.80);

}


.gridmap-incidents-heading-v1{

    flex:1 1 auto;

    min-width:0;

}


.gridmap-incidents-title-v1{

    display:block;

    color:#ffc13a;

    font-size:10px;

    font-weight:950;

    letter-spacing:.08em;

}


.gridmap-incidents-subtitle-v1{

    display:block;

    margin-top:2px;

    color:#7f8e95;

    font-size:7px;

    font-weight:700;

}


/* ------------------------------------------------------------
   WINDOW BUTTONS
   ------------------------------------------------------------ */

.gridmap-incidents-windows-v1{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:4px;

    padding:7px;

    border-bottom:
        1px solid
        rgba(255,255,255,.05);

}


.gridmap-incidents-window-v1{

    height:28px;

    border:
        1px solid
        rgba(255,255,255,.12);

    border-radius:5px;

    background:
        rgba(255,255,255,.035);

    color:#8c9aa0;

    font-size:8px;

    font-weight:900;

    cursor:pointer;

}


.gridmap-incidents-window-v1:hover{

    border-color:
        rgba(255,193,58,.45);

    color:#ffc13a;

}


.gridmap-incidents-window-v1.is-active{

    border-color:
        rgba(255,193,58,.78);

    background:
        rgba(255,193,58,.11);

    color:#ffc13a;

}


/* ------------------------------------------------------------
   CURRENT ALERT SUMMARY
   ------------------------------------------------------------ */

.gridmap-incidents-current-v1{

    padding:7px;

    border-bottom:
        1px solid
        rgba(255,255,255,.05);

}


.gridmap-incidents-current-title-v1{

    padding:
        2px 3px
        6px;

    color:#8a999f;

    font-size:7px;

    font-weight:900;

    letter-spacing:.08em;

}


.gridmap-current-alert-v1{

    display:flex;

    align-items:center;

    gap:8px;

    padding:
        7px 8px;

    margin-top:4px;

    border:
        1px solid
        rgba(255,255,255,.07);

    border-radius:6px;

    background:
        rgba(255,255,255,.025);

}


.gridmap-current-alert-v1.offline{

    border-color:
        rgba(255,82,82,.30);

    background:
        rgba(255,82,82,.045);

}


.gridmap-current-alert-v1.warning{

    border-color:
        rgba(255,193,58,.26);

    background:
        rgba(255,193,58,.04);

}


.gridmap-current-alert-dot-v1{

    width:7px;
    height:7px;

    flex:0 0 auto;

    border-radius:50%;

}


.gridmap-current-alert-dot-v1.offline{

    background:#ff5252;

    box-shadow:
        0 0 7px
        rgba(255,82,82,.75);

}


.gridmap-current-alert-dot-v1.warning{

    background:#ffc13a;

    box-shadow:
        0 0 7px
        rgba(255,193,58,.72);

}


.gridmap-current-alert-button-v1{

    flex:1 1 auto;

    min-width:0;

    border:0;

    padding:0;

    background:none;

    color:#ffffff;

    font-family:inherit;

    font-size:9px;

    font-weight:900;

    text-align:left;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    cursor:pointer;

}


.gridmap-current-alert-button-v1:hover{

    color:#ffc13a;

}


.gridmap-current-alert-status-v1{

    flex:0 0 auto;

    color:#9aa7ad;

    font-size:7px;

    font-weight:850;

}


.gridmap-current-alert-duration-v1{

    flex:0 0 auto;

    min-width:56px;

    color:#ffffff;

    font-size:8px;

    font-weight:900;

    text-align:right;

}


/* ALL CLEAR */

.gridmap-incidents-clear-v1{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    padding:
        12px 10px;

    border:
        1px solid
        rgba(102,255,119,.18);

    border-radius:6px;

    background:
        rgba(102,255,119,.035);

    color:#8dff98;

    font-size:9px;

    font-weight:900;

}


.gridmap-incidents-clear-dot-v1{

    width:8px;
    height:8px;

    border-radius:50%;

    background:#66ff77;

    box-shadow:
        0 0 8px
        rgba(102,255,119,.75);

}


/* ------------------------------------------------------------
   INCIDENT HISTORY
   ------------------------------------------------------------ */

.gridmap-incidents-history-title-v1{

    padding:
        8px 10px
        4px;

    color:#89979d;

    font-size:7px;

    font-weight:900;

    letter-spacing:.08em;

}


.gridmap-incidents-list-v1{

    flex:1 1 auto;

    min-height:0;

    overflow-y:auto;

    padding:
        0 7px
        7px;

    scrollbar-width:thin;

    scrollbar-color:
        rgba(255,193,58,.35)
        transparent;

}


.gridmap-incident-row-v1{

    display:flex;

    align-items:center;

    gap:8px;

    padding:
        7px 8px;

    margin-top:4px;

    border:
        1px solid
        rgba(255,255,255,.065);

    border-radius:6px;

    background:
        rgba(255,255,255,.022);

}


.gridmap-incident-severity-v1{

    flex:0 0 auto;

    width:7px;
    height:28px;

    border-radius:3px;

}


.gridmap-incident-severity-v1.offline{

    background:#ff5252;

}


.gridmap-incident-severity-v1.warning{

    background:#ffc13a;

}


.gridmap-incident-copy-v1{

    flex:1 1 auto;

    min-width:0;

}


.gridmap-incident-region-v1{

    display:block;

    width:100%;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    border:0;

    padding:0;

    background:none;

    color:#ffffff;

    font-family:inherit;

    font-size:9px;

    font-weight:900;

    text-align:left;

    cursor:pointer;

}


.gridmap-incident-region-v1:hover{

    color:#ffc13a;

}


.gridmap-incident-meta-v1{

    display:block;

    margin-top:2px;

    color:#77868c;

    font-size:7px;

    font-weight:700;

}


.gridmap-incident-result-v1{

    flex:0 0 auto;

    min-width:72px;

    text-align:right;

}


.gridmap-incident-result-v1.active{

    color:#ff7272;

    font-size:7px;

    font-weight:950;

}


.gridmap-incident-result-v1.recovered{

    color:#79ef8b;

    font-size:7px;

    font-weight:950;

}


/* ------------------------------------------------------------
   EMPTY HISTORY
   ------------------------------------------------------------ */

.gridmap-incidents-empty-v1{

    padding:
        18px 12px;

    color:#7e8d93;

    font-size:9px;

    line-height:1.5;

    text-align:center;

}


/* ------------------------------------------------------------
   FOOTER
   ------------------------------------------------------------ */

.gridmap-incidents-footer-v1{

    display:flex;

    justify-content:space-between;

    gap:10px;

    padding:
        7px 10px;

    border-top:
        1px solid
        rgba(255,193,58,.13);

    color:#65747a;

    font-size:7px;

    font-weight:700;

}


/* FULLSCREEN */

.gridmap-scroll:fullscreen
#gridmap-incidents-control-v1,

.gridmap-scroll:-webkit-full-screen
#gridmap-incidents-control-v1{

    left:444px;

    bottom:54px;

}


/* END Grid MAP - ALERTS INCIDENTS V1 */


/* ============================================================
   Grid MAP - STATISTICS DASHBOARD V1
   ============================================================ */


#gridmap-stats-control-v1{

    position:absolute;

    left:558px;
    bottom:48px;

    z-index:290000;

    pointer-events:auto;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    user-select:none;

}


#gridmap-stats-button-v1{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    min-width:112px;

    height:34px;

    padding:
        0 11px;

    border:
        1px solid
        rgba(255,193,58,.72);

    border-radius:8px;

    background:
        linear-gradient(
            180deg,
            rgba(13,22,28,.98),
            rgba(3,10,14,.98)
        );

    color:#ffc13a;

    box-shadow:
        0 7px 18px
        rgba(0,0,0,.55);

    font-size:9px;

    font-weight:950;

    letter-spacing:.06em;

    cursor:pointer;

}


#gridmap-stats-button-v1:hover{

    border-color:#ffc13a;

    background:
        rgba(255,193,58,.10);

}


.gridmap-stats-icon-v1{

    font-size:14px;

}


.gridmap-stats-mode-v1{

    color:#ffffff;

    font-size:8px;

}


/* ============================================================
   PANEL
   ============================================================ */

#gridmap-stats-panel-v1{

    position:absolute;

    left:-430px;
    bottom:42px;

    width:680px;

    max-width:
        calc(100vw - 100px);

    max-height:
        min(
            610px,
            calc(100vh - 145px)
        );

    display:flex;

    flex-direction:column;

    overflow:hidden;

    border:
        1px solid
        rgba(255,193,58,.70);

    border-radius:11px;

    background:
        linear-gradient(
            180deg,
            rgba(5,14,19,.995),
            rgba(2,8,12,.995)
        );

    box-shadow:
        0 16px 38px
        rgba(0,0,0,.74);

    opacity:0;

    visibility:hidden;

    transform:
        translateY(7px);

    transition:
        opacity .14s ease,
        transform .14s ease,
        visibility .14s ease;

}


#gridmap-stats-control-v1.is-open
#gridmap-stats-panel-v1{

    opacity:1;

    visibility:visible;

    transform:
        translateY(0);

}


/* HEADER */

.gridmap-stats-header-v1{

    display:flex;

    align-items:center;

    gap:10px;

    padding:
        11px 12px;

    border-bottom:
        1px solid
        rgba(255,193,58,.18);

    background:
        rgba(255,193,58,.025);

}


.gridmap-stats-header-icon-v1{

    color:#ffc13a;

    font-size:18px;

}


.gridmap-stats-heading-v1{

    flex:1 1 auto;

}


.gridmap-stats-title-v1{

    display:block;

    color:#ffc13a;

    font-size:11px;

    font-weight:950;

    letter-spacing:.08em;

}


.gridmap-stats-subtitle-v1{

    display:block;

    margin-top:2px;

    color:#819097;

    font-size:7px;

    font-weight:700;

}


.gridmap-stats-updated-v1{

    color:#74838a;

    font-size:7px;

    font-weight:700;

}


/* WINDOW */

.gridmap-stats-windows-v1{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:4px;

    padding:
        7px;

    border-bottom:
        1px solid
        rgba(255,255,255,.05);

}


.gridmap-stats-window-v1{

    height:28px;

    border:
        1px solid
        rgba(255,255,255,.12);

    border-radius:5px;

    background:
        rgba(255,255,255,.035);

    color:#89989e;

    font-size:8px;

    font-weight:900;

    cursor:pointer;

}


.gridmap-stats-window-v1:hover{

    border-color:
        rgba(255,193,58,.5);

    color:#ffc13a;

}


.gridmap-stats-window-v1.is-active{

    border-color:
        rgba(255,193,58,.78);

    background:
        rgba(255,193,58,.11);

    color:#ffc13a;

}


/* TABS */

.gridmap-stats-tabs-v1{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    border-bottom:
        1px solid
        rgba(255,255,255,.06);

}


.gridmap-stats-tab-v1{

    height:34px;

    border:0;

    border-right:
        1px solid
        rgba(255,255,255,.045);

    background:
        rgba(255,255,255,.018);

    color:#7e8c92;

    font-size:8px;

    font-weight:950;

    cursor:pointer;

}


.gridmap-stats-tab-v1:hover{

    color:#ffc13a;

    background:
        rgba(255,193,58,.04);

}


.gridmap-stats-tab-v1.is-active{

    color:#ffc13a;

    background:
        rgba(255,193,58,.08);

    box-shadow:
        inset 0 -2px 0
        rgba(255,193,58,.75);

}


/* BODY */

#gridmap-stats-body-v1{

    flex:1 1 auto;

    min-height:0;

    overflow-y:auto;

    padding:9px;

    scrollbar-width:thin;

    scrollbar-color:
        rgba(255,193,58,.35)
        transparent;

}


/* SUMMARY CARDS */

.gridmap-stats-cards-v1{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:7px;

}


.gridmap-stats-card-v1{

    min-height:73px;

    padding:9px;

    border:
        1px solid
        rgba(255,255,255,.07);

    border-radius:7px;

    background:
        rgba(255,255,255,.025);

}


.gridmap-stats-card-label-v1{

    color:#74838a;

    font-size:7px;

    font-weight:900;

    letter-spacing:.06em;

}


.gridmap-stats-card-value-v1{

    display:block;

    margin-top:6px;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    color:#ffffff;

    font-size:17px;

    font-weight:950;

}


.gridmap-stats-card-value-v1.gold{

    color:#ffc13a;

}


.gridmap-stats-card-value-v1.green{

    color:#74f786;

}


.gridmap-stats-card-value-v1.red{

    color:#ff6d6d;

}


.gridmap-stats-card-note-v1{

    display:block;

    margin-top:4px;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    color:#69787e;

    font-size:7px;

    font-weight:700;

}


/* SECTION */

.gridmap-stats-section-v1{

    margin-top:9px;

    padding:8px;

    border:
        1px solid
        rgba(255,255,255,.06);

    border-radius:7px;

    background:
        rgba(255,255,255,.018);

}


.gridmap-stats-section-title-v1{

    margin-bottom:6px;

    color:#ffc13a;

    font-size:8px;

    font-weight:950;

    letter-spacing:.07em;

}


/* TABLE */

.gridmap-stats-table-v1{

    width:100%;

    border-collapse:collapse;

}


.gridmap-stats-table-v1 th{

    padding:
        6px 7px;

    border-bottom:
        1px solid
        rgba(255,255,255,.08);

    color:#708087;

    font-size:7px;

    font-weight:900;

    text-align:left;

}


.gridmap-stats-table-v1 td{

    padding:
        7px;

    border-bottom:
        1px solid
        rgba(255,255,255,.045);

    color:#cbd5d9;

    font-size:8px;

    font-weight:700;

}


.gridmap-stats-table-v1 tr:last-child td{

    border-bottom:0;

}


.gridmap-stats-region-link-v1{

    border:0;

    padding:0;

    background:none;

    color:#ffffff;

    font-family:inherit;

    font-size:8px;

    font-weight:900;

    cursor:pointer;

}


.gridmap-stats-region-link-v1:hover{

    color:#ffc13a;

}


.gridmap-stats-status-v1{

    font-weight:950;

}


.gridmap-stats-status-v1.online{

    color:#59bdff;

}


.gridmap-stats-status-v1.warning{

    color:#ffc13a;

}


.gridmap-stats-status-v1.offline{

    color:#ff6565;

}


.gridmap-stats-status-v1.unknown{

    color:#78878d;

}


/* INCIDENTS */

.gridmap-stats-incident-v1{

    display:flex;

    align-items:center;

    gap:8px;

    padding:8px;

    margin-top:5px;

    border:
        1px solid
        rgba(255,255,255,.06);

    border-radius:6px;

    background:
        rgba(255,255,255,.022);

}


.gridmap-stats-incident-bar-v1{

    width:6px;
    height:30px;

    flex:0 0 auto;

    border-radius:3px;

}


.gridmap-stats-incident-bar-v1.warning{

    background:#ffc13a;

}


.gridmap-stats-incident-bar-v1.offline{

    background:#ff5252;

}


.gridmap-stats-incident-copy-v1{

    flex:1 1 auto;

    min-width:0;

}


.gridmap-stats-incident-meta-v1{

    display:block;

    margin-top:2px;

    color:#74838a;

    font-size:7px;

}


.gridmap-stats-incident-result-v1{

    flex:0 0 auto;

    font-size:7px;

    font-weight:950;

}


.gridmap-stats-incident-result-v1.active{

    color:#ff7070;

}


.gridmap-stats-incident-result-v1.recovered{

    color:#75ee87;

}


/* EMPTY */

.gridmap-stats-empty-v1{

    padding:
        24px 14px;

    color:#7e8c92;

    font-size:9px;

    line-height:1.5;

    text-align:center;

}


/* FOOTER */

.gridmap-stats-footer-v1{

    display:flex;

    justify-content:space-between;

    gap:10px;

    padding:
        7px 10px;

    border-top:
        1px solid
        rgba(255,193,58,.12);

    color:#65747a;

    font-size:7px;

    font-weight:700;

}


/* FULL SCREEN */

.gridmap-scroll:fullscreen
#gridmap-stats-control-v1,

.gridmap-scroll:-webkit-full-screen
#gridmap-stats-control-v1{

    left:580px;

    bottom:54px;

}


/* END Grid MAP - STATISTICS DASHBOARD V1 */


/* ============================================================
   Grid MAP - REGION PERFORMANCE V2
   ============================================================ */

.gridmap-stats-tabs-v1{
    grid-template-columns:repeat(5,1fr) !important;
}

.gridmap-performance-root-v2{
    width:100%;
}

.gridmap-performance-summary-v2{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:7px;
    margin-bottom:9px;
}

.gridmap-performance-card-v2{
    padding:9px;
    border:1px solid rgba(255,255,255,.07);
    border-radius:7px;
    background:rgba(255,255,255,.025);
}

.gridmap-performance-label-v2{
    display:block;
    color:#74838a;
    font-size:7px;
    font-weight:900;
    letter-spacing:.06em;
}

.gridmap-performance-value-v2{
    display:block;
    margin-top:5px;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    color:#ffffff;
    font-size:14px;
    font-weight:950;
}

.gridmap-performance-value-v2.gold{
    color:#ffc13a;
}

.gridmap-performance-note-v2{
    display:block;
    margin-top:3px;
    color:#6d7b81;
    font-size:7px;
    font-weight:700;
}

.gridmap-performance-change-v2{
    font-weight:950;
}

.gridmap-performance-change-v2.positive{
    color:#7def8b;
}

.gridmap-performance-change-v2.negative{
    color:#ff8585;
}

.gridmap-performance-change-v2.neutral{
    color:#849299;
}

.gridmap-performance-warning-v2{
    display:inline-block;
    margin-left:5px;
    padding:2px 5px;
    border:1px solid rgba(255,193,58,.48);
    border-radius:999px;
    background:rgba(255,193,58,.09);
    color:#ffc13a;
    font-size:6px;
    font-weight:950;
}

.gridmap-performance-activity-v2{
    display:inline-block;
    min-width:50px;
    padding:2px 6px;
    border-radius:999px;
    font-size:7px;
    font-weight:950;
    text-align:center;
}

.gridmap-performance-activity-v2.high{
    background:rgba(255,193,58,.12);
    color:#ffc13a;
}

.gridmap-performance-activity-v2.medium{
    background:rgba(73,185,255,.10);
    color:#63c3ff;
}

.gridmap-performance-activity-v2.low{
    background:rgba(126,239,139,.08);
    color:#7def8b;
}

.gridmap-performance-overview-v2{
    border-color:rgba(255,193,58,.14) !important;
}

/* END Grid MAP - REGION PERFORMANCE V2 */


/* ============================================================
   Grid MAP - USER SECURITY LAYER V1
   ============================================================ */

/*
 * These tools belong only on the Grid Owner map.
 * The client scripts are removed as well; these rules are
 * an additional visual safeguard.
 */

#gridmap-online-now-v2,
#gridmap-traffic-control-v1,
#gridmap-uptime-control-v1,
#gridmap-incidents-control-v1,
#gridmap-stats-control-v1,
#gridmap-layers-v1,
.australia-grid-agent,
.australia-grid-traffic-heat-v1{
    display:none !important;
}

/* END Grid MAP - USER SECURITY LAYER V1 */




/* ============================================================
   Grid MAP - USER QUICK NAV V2
   ============================================================ */

#gridmap-user-quicknav-v2{

    display:flex;

    align-items:center;

    gap:7px;

    flex-wrap:wrap;

    position:relative;

    z-index:500000;

    pointer-events:auto;

}


.gridmap-user-nav-btn-v2{

    min-height:34px;

    padding:
        8px
        11px;

    border:
        1px solid
        rgba(255,193,58,.38);

    border-radius:6px;

    background:
        linear-gradient(
            180deg,
            #25313b,
            #151c22
        );

    color:#f4f7fa;

    box-shadow:
        inset 0 1px
        rgba(255,255,255,.05);

    font-size:9px;

    font-weight:900;

    letter-spacing:.025em;

    cursor:pointer;

    white-space:nowrap;

}


.gridmap-user-nav-btn-v2:hover{

    border-color:#d99b16;

    color:#ffc13a;

}


.gridmap-user-nav-btn-v2:disabled{

    opacity:.40;

    cursor:default;

}


#gridmap-user-favorite-v2.is-favorite{

    border-color:#ffc13a;

    color:#ffc13a;

}


/* ------------------------------------------------------------
   QUICK JUMP SELECT
   ------------------------------------------------------------ */

#gridmap-user-jump-v2{

    height:34px;

    min-width:190px;

    max-width:280px;

    padding:
        0
        32px
        0
        10px;

    border:
        1px solid
        rgba(255,193,58,.38);

    border-radius:6px;

    outline:none;

    background:
        #151c22;

    color:#f4f7fa;

    font-size:9px;

    font-weight:850;

    cursor:pointer;

}


#gridmap-user-jump-v2:hover,
#gridmap-user-jump-v2:focus{

    border-color:#ffc13a;

}


#gridmap-user-jump-v2 option{

    background:#11181d;

    color:#f4f7fa;

}


/* ------------------------------------------------------------
   SMALL STATUS MESSAGE
   ------------------------------------------------------------ */

#gridmap-user-nav-status-v2{

    min-width:0;

    color:#91a0a6;

    font-size:9px;

    font-weight:750;

    white-space:nowrap;

}


/* ------------------------------------------------------------
   FULLSCREEN
   ------------------------------------------------------------ */

.gridmap-scroll:fullscreen
#gridmap-user-quicknav-v2{

    padding:
        4px
        0;

}


.gridmap-scroll:fullscreen
#gridmap-user-jump-v2{

    background:#10171c;

}


@media(max-width:800px){

    #gridmap-user-jump-v2{

        min-width:150px;

        max-width:190px;

    }

}


/* END Grid MAP - USER QUICK NAV V2 */








/* ============================================================
   AUSTRALIA USER MAP - REGION DETAIL ACTION BAR V1
   ============================================================ */

.gridmap-hop-actions
#australia-user-detail-center-v1,

.gridmap-hop-actions
#australia-user-detail-favorite-v1{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    min-height:38px;

    padding:
        0
        15px;

    border:
        1px solid
        rgba(255,193,58,.52);

    border-radius:6px;

    background:
        linear-gradient(
            180deg,
            rgba(49,42,17,.96),
            rgba(25,21,8,.96)
        );

    color:#ffc13a;

    font-size:10px;

    font-weight:950;

    letter-spacing:.03em;

    cursor:pointer;

    white-space:nowrap;

    box-shadow:
        inset 0 1px
        rgba(255,255,255,.045);

}


.gridmap-hop-actions
#australia-user-detail-center-v1:hover,

.gridmap-hop-actions
#australia-user-detail-favorite-v1:hover{

    border-color:#ffc13a;

    background:
        linear-gradient(
            180deg,
            rgba(69,56,17,.98),
            rgba(32,26,8,.98)
        );

}


.gridmap-hop-actions
#australia-user-detail-favorite-v1.is-favorite{

    color:#ffffff;

    border-color:#ffc13a;

    box-shadow:
        0 0 14px
        rgba(255,193,58,.15),
        inset 0 1px
        rgba(255,255,255,.05);

}


.gridmap-hop-actions
#australia-user-detail-center-v1:disabled,

.gridmap-hop-actions
#australia-user-detail-favorite-v1:disabled{

    opacity:.38;

    cursor:default;

}


@media(max-width:750px){

    .gridmap-hop-actions
    #australia-user-detail-center-v1,

    .gridmap-hop-actions
    #australia-user-detail-favorite-v1{

        min-height:34px;

        padding:
            0
            10px;

        font-size:9px;

    }

}


/* END AUSTRALIA USER MAP - REGION DETAIL ACTION BAR V1 */


/* ============================================================
   AUSTRALIA USER MAP - DISPLAY CONTROLS V1
   ============================================================ */

#australia-user-display-v1{

    position:relative;

    display:inline-flex;

    align-items:center;

    z-index:650000;

    pointer-events:auto;

}


#australia-user-display-button-v1{

    min-height:34px;

    padding:
        8px
        11px;

    border:
        1px solid
        rgba(255,193,58,.38);

    border-radius:6px;

    background:
        linear-gradient(
            180deg,
            #25313b,
            #151c22
        );

    color:#f4f7fa;

    font-size:9px;

    font-weight:900;

    letter-spacing:.035em;

    cursor:pointer;

    white-space:nowrap;

}


#australia-user-display-button-v1:hover,
#australia-user-display-button-v1.active{

    border-color:#ffc13a;

    color:#ffc13a;

}


#australia-user-display-panel-v1{

    position:absolute;

    top:42px;
    right:0;

    width:255px;

    display:none;

    padding:9px;

    border:
        1px solid
        rgba(255,193,58,.42);

    border-radius:9px;

    background:
        linear-gradient(
            180deg,
            rgba(16,22,27,.99),
            rgba(7,11,14,.99)
        );

    box-shadow:
        0 18px 42px
        rgba(0,0,0,.62);

    backdrop-filter:
        blur(8px);

}


#australia-user-display-panel-v1.open{

    display:block;

}


.australia-user-display-title-v1{

    padding:
        4px
        6px
        9px;

    color:#ffc13a;

    font-size:9px;

    font-weight:950;

    letter-spacing:.07em;

    border-bottom:
        1px solid
        rgba(255,255,255,.07);

    margin-bottom:5px;

}


.australia-user-display-row-v1{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:12px;

    min-height:36px;

    padding:
        4px
        6px;

    color:#e5ebee;

    font-size:9px;

    font-weight:850;

    cursor:pointer;

}


.australia-user-display-row-v1:hover{

    background:
        rgba(255,255,255,.025);

    border-radius:5px;

}


.australia-user-display-switch-v1{

    position:relative;

    flex:
        0 0
        34px;

    width:34px;
    height:18px;

    border:
        1px solid
        rgba(255,255,255,.14);

    border-radius:20px;

    background:#242d32;

    transition:
        background .15s ease,
        border-color .15s ease;

}


.australia-user-display-switch-v1::after{

    content:"";

    position:absolute;

    top:2px;
    left:2px;

    width:12px;
    height:12px;

    border-radius:50%;

    background:#98a4aa;

    transition:
        transform .15s ease,
        background .15s ease;

}


.australia-user-display-row-v1.on
.australia-user-display-switch-v1{

    background:
        rgba(255,193,58,.20);

    border-color:
        rgba(255,193,58,.65);

}


.australia-user-display-row-v1.on
.australia-user-display-switch-v1::after{

    transform:
        translateX(16px);

    background:#ffc13a;

}


/* ============================================================
   VISUAL LAYER CLASSES
   ============================================================ */


/* REGION NAMES */

body.australia-user-hide-region-names-v1
#grid-map
.map-region-name{

    visibility:hidden !important;

}


/* ISLAND ELEMENTS DISCOVERED BY CLIENT */

body.australia-user-hide-islands-v1
.australia-user-island-layer-v1{

    display:none !important;

}


/* COMPASS ELEMENT DISCOVERED BY CLIENT */

body.australia-user-hide-compass-v1
.australia-user-compass-layer-v1{

    display:none !important;

}


/* COORDINATE ELEMENTS DISCOVERED BY CLIENT */

body.australia-user-hide-coordinates-v1
.australia-user-coordinate-layer-v1{

    display:none !important;

}


/* PERSONAL YOU ARE HERE ELEMENT */

body.australia-user-hide-you-v1
.australia-user-you-layer-v1{

    display:none !important;

}


.gridmap-scroll:fullscreen
#australia-user-display-panel-v1{

    top:42px;

    right:0;

}


@media(max-width:760px){

    #australia-user-display-panel-v1{

        width:225px;

        right:auto;
        left:0;

    }

}


/* END AUSTRALIA USER MAP - DISPLAY CONTROLS V1 */


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
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">
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

<div class="shell">

<?php
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "GRID MAP";
$siteHeaderButton = "BACK TO DASHBOARD";
$siteHeaderLink = "/Other/dashboard-return.php";
require_once __DIR__ . "/includes/site-header.php";
?>


<section class="panel admin">

    <div class="panel-title">
        <span>Grid â€” LIVE MAP</span>
    </div>

    <p class="backup-help">
        Live read-only map of the Grid.
        Region positions and sizes are read from the OpenSim region configuration
        and combined with live region status information.
    </p>

</section>


<div class="gridmap-toolbar">

    <div class="gridmap-toolbar-left">

        <button type="button" id="gridmap-refresh">
            REFRESH MAP
        </button>

        <span id="gridmap-updated">
            Waiting for first refresh...
        </span>

    </div>

    <div class="gridmap-legend">

        <span class="map-legend-item">
            <span class="map-dot online"></span>
            ONLINE
        </span>

        <span class="map-legend-item">
            <span class="map-dot warning"></span>
            WARNING
        </span>

        <span class="map-legend-item">
            <span class="map-dot offline"></span>
            OFFLINE
        </span>

    </div>

</div>


<section class="gridmap-shell">

    <div class="gridmap-scroll">
        <div id="grid-map">
            <div class="gridmap-message">
                Loading grid map...
            </div>
        </div>
    </div>

</section>


<section class="gridmap-details">

    <h2>REGION DETAILS</h2>

    <div id="gridmap-details-body">
        Select a region on the map to view its details.
    </div>

</section>

</div>


<script>
window.DG_GRID_MAP = {
    level: <?=json_encode($level)?>,
    avatar: <?=json_encode($avatar)?>
};
</script>

<script src="/Other/grid-map-user.js?v=20260818232050"></script>








<script>

/* ============================================================
   Grid MAP - NATURAL PNG ISLANDS V7

   REAL DreamGrid coordinates remain untouched.

   Adds:
   - different island rotations
   - slightly different island sizes
   - slight island-only offsets
   - floating region boxes remain perfectly straight
   ============================================================ */

(function(){

    const GRID_CELL = 42;

    let rebuildTimer = null;
    let building = false;


    function removeRealIslands(){

        document
            .querySelectorAll(".region-island-image")
            .forEach(function(el){
                el.remove();
            });

    }


    /* ========================================================
       BASE ISLAND SIZE
       ======================================================== */

    function getIslandSettings(width,height){

        const cells = Math.max(
            1,
            Math.round(
                Math.max(width,height) / GRID_CELL
            )
        );


        /* 1x1 + 2x2 */

        if(cells <= 2){

            return {

                imageClass:"island-small",

                marginX:Math.max(
                    100,
                    width * 1.30
                ),

                marginY:Math.max(
                    100,
                    height * 1.30
                )

            };

        }


        /* 3x3 + 4x4 */

        if(cells <= 4){

            return {

                imageClass:"island-medium",

                marginX:Math.max(
                    125,
                    width * 1.00
                ),

                marginY:Math.max(
                    125,
                    height * 1.00
                )

            };

        }


        /* 5x5+ including TESTER */

        return {

            imageClass:"island-large",

            marginX:Math.max(
                130,
                width * 0.60
            ),

            marginY:Math.max(
                130,
                height * 0.60
            )

        };

    }


    /* ========================================================
       BUILD NATURAL ISLANDS
       ======================================================== */

    function buildRealIslands(){

        if(building){
            return;
        }


        const map =
            document.getElementById("grid-map");


        if(!map){
            return;
        }


        building = true;

        removeRealIslands();


        const regions =
            Array.from(
                map.querySelectorAll(
                    ":scope > .map-region"
                )
            );


        /*
           Strong variation between islands.
           ONLY the island PNG rotates.
        */

        const rotations = [
            -18,
             11,
             -7,
             22,
            -13,
              6,
            -25,
             15,
              4,
            -20
        ];


        /*
           Slightly different island sizes.

           1.00 = normal approved size.
           Some islands slightly larger,
           some slightly smaller.
        */

        const sizeVariation = [
            1.00,
            1.12,
            0.96,
            1.18,
            1.05,
            0.93,
            1.10,
            0.98,
            1.15,
            1.03
        ];


        /*
           Move ONLY the island image slightly.

           The floating region box remains on the
           exact real OpenSim coordinate.
        */

        const offsets = [

            {x:-12, y:  8},
            {x: 14, y: -9},
            {x: -8, y:-13},
            {x: 16, y:  7},
            {x:-14, y: 12},

            {x:  9, y: -6},
            {x:  2, y: 14},
            {x:-15, y: -7},
            {x: 13, y: 10},
            {x:  6, y:-12}

        ];


        regions.forEach(function(region,index){

            const width =
                region.offsetWidth;

            const height =
                region.offsetHeight;


            if(width <= 0 || height <= 0){
                return;
            }


            const cfg =
                getIslandSettings(
                    width,
                    height
                );


            const variation =
                sizeVariation[
                    index %
                    sizeVariation.length
                ];


            const offset =
                offsets[
                    index %
                    offsets.length
                ];


            /*
               Vary the beach/reef size rather than
               changing the black information box.
            */

            const marginX =
                cfg.marginX *
                variation;


            const marginY =
                cfg.marginY *
                variation;


            const island =
                document.createElement("div");


            island.className =
                "region-island-image " +
                cfg.imageClass;


            island.style.left =
                (
                    region.offsetLeft -
                    marginX +
                    offset.x
                ) + "px";


            island.style.top =
                (
                    region.offsetTop -
                    marginY +
                    offset.y
                ) + "px";


            island.style.width =
                (
                    width +
                    marginX * 2
                ) + "px";


            island.style.height =
                (
                    height +
                    marginY * 2
                ) + "px";


            island.style.transform =
                "rotate(" +
                rotations[
                    index %
                    rotations.length
                ] +
                "deg)";


            map.appendChild(
                island
            );

        });


        building = false;

    }


    /* ========================================================
       REBUILD SYSTEM
       ======================================================== */

    function scheduleIslandBuild(){

        clearTimeout(
            rebuildTimer
        );


        rebuildTimer =
            setTimeout(
                buildRealIslands,
                150
            );

    }


    function startRealIslandSystem(){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){
            return;
        }


        scheduleIslandBuild();


        setTimeout(
            scheduleIslandBuild,
            500
        );


        /*
           Watch normal Grid Map refreshes.
        */

        const observer =
            new MutationObserver(
                function(mutations){

                    if(building){
                        return;
                    }


                    let regionChanged = false;


                    mutations.forEach(function(mutation){

                        mutation.addedNodes.forEach(function(node){

                            if(
                                node.nodeType === 1 &&
                                node.classList &&
                                node.classList.contains(
                                    "map-region"
                                )
                            ){
                                regionChanged = true;
                            }

                        });


                        mutation.removedNodes.forEach(function(node){

                            if(
                                node.nodeType === 1 &&
                                node.classList &&
                                node.classList.contains(
                                    "map-region"
                                )
                            ){
                                regionChanged = true;
                            }

                        });

                    });


                    if(regionChanged){
                        scheduleIslandBuild();
                    }

                }
            );


        observer.observe(
            map,
            {
                childList:true
            }
        );


        window.addEventListener(
            "resize",
            scheduleIslandBuild
        );

    }


    if(document.readyState === "loading"){

        document.addEventListener(
            "DOMContentLoaded",
            startRealIslandSystem
        );

    }
    else{

        startRealIslandSystem();

    }

})();

/* END Grid MAP - NATURAL PNG ISLANDS V7 */

</script>





<script>



</script>


<script>



</script>


<script>

/* ============================================================
   Grid MAP - FIRESTORM CANVAS PAN ZOOM V2

   TRUE CANVAS-STYLE MAP:

   LEFT DRAG:
       move map freely in X and Y

   MOUSE WHEEL:
       zoom around the mouse cursor

   CLICK:
       region click still works

   NO INTERNAL SCROLLBARS

   DreamGrid coordinates remain untouched.
   ============================================================ */

(function(){

    const MIN_ZOOM = 0.12;
    const MAX_ZOOM = 3.00;

    /*
       Multiplicative zoom feels much more like a viewer map
       than fixed 10% jumps.
    */

    const ZOOM_FACTOR = 1.12;

    const DRAG_THRESHOLD = 4;
    /* ========================================================
       Grid MAP - VIEW MEMORY HOOK V2
       ======================================================== */

    const AUSTRALIA_MAP_VIEW_KEY =
        "AustraliaGridMapViewV2";


    function saveAustraliaMapView(){

        try{

            localStorage.setItem(
                AUSTRALIA_MAP_VIEW_KEY,
                JSON.stringify({
                    zoom:zoom,
                    panX:panX,
                    panY:panY
                })
            );

        }
        catch(error){}

    }


    function loadAustraliaMapView(){

        try{

            const raw =
                localStorage.getItem(
                    AUSTRALIA_MAP_VIEW_KEY
                );


            if(!raw){
                return null;
            }


            const value =
                JSON.parse(raw);


            const z =
                Number(value.zoom);

            const x =
                Number(value.panX);

            const y =
                Number(value.panY);


            if(
                !Number.isFinite(z) ||
                !Number.isFinite(x) ||
                !Number.isFinite(y)
            ){
                return null;
            }


            return {
                zoom:
                    clamp(
                        z,
                        MIN_ZOOM,
                        MAX_ZOOM
                    ),

                panX:x,
                panY:y
            };

        }
        catch(error){

            return null;

        }

    }




    let viewport = null;
    let map = null;
    let readout = null;


    /*
       Screen transform:

       screenX = worldX * zoom + panX
       screenY = worldY * zoom + panY
    */

    let zoom = 1.00;

    let panX = 0;
    let panY = 0;


    let pointerDown = false;
    let dragging = false;

    let activePointerId = null;

    let pointerStartX = 0;
    let pointerStartY = 0;

    let panStartX = 0;
    let panStartY = 0;

    let suppressNextClick = false;


    /* ========================================================
       HELPERS
       ======================================================== */

    function clamp(value,min,max){

        return Math.max(
            min,
            Math.min(max,value)
        );

    }


    function updateReadout(){

        if(readout){

            readout.textContent =
                Math.round(
                    zoom * 100
                ) + "%";

        }

    }


    function applyTransform(){

        if(!map || !viewport){
            return;
        }


        /*
           Move and scale the REAL grid world.
        */

        map.style.transform =
            "matrix(" +
            zoom + ",0,0," +
            zoom + "," +
            panX + "," +
            panY + ")";


        /*
           =====================================================
           FIRESTORM OCEAN SYNC

           The ocean now follows the SAME pan and zoom as
           the map instead of having a separate static layer.
           =====================================================
        */

        const baseSize =
            Math.max(
                220,
                700 * zoom
            );


        const deepSize =
            Math.max(
                250,
                790 * zoom
            );


        const surfaceSize =
            Math.max(
                190,
                560 * zoom
            );


        /*
           Keep the background coordinates manageable.
        */

        const oceanX =
            panX % baseSize;


        const oceanY =
            panY % baseSize;


        viewport.style.setProperty(
            "--fs-ocean-x",
            oceanX + "px"
        );


        viewport.style.setProperty(
            "--fs-ocean-y",
            oceanY + "px"
        );


        viewport.style.setProperty(
            "--fs-ocean-base-size",
            baseSize + "px"
        );


        viewport.style.setProperty(
            "--fs-ocean-deep-size",
            deepSize + "px"
        );


        viewport.style.setProperty(
            "--fs-ocean-surface-size",
            surfaceSize + "px"
        );


        updateReadout();

        saveAustraliaMapView();

    }


    function getMapSize(){

        return {

            width:
                Math.max(
                    1,
                    map.offsetWidth
                ),

            height:
                Math.max(
                    1,
                    map.offsetHeight
                )

        };

    }


    /* ========================================================
       INITIAL POSITION
       ======================================================== */

    function positionInitialMap(){
        /*
           Restore the last Firestorm-style pan/zoom view.
        */

        const savedAustraliaMapView =
            loadAustraliaMapView();


        if(savedAustraliaMapView){

            zoom =
                savedAustraliaMapView.zoom;

            panX =
                savedAustraliaMapView.panX;

            panY =
                savedAustraliaMapView.panY;

            applyTransform();

            return;
        }



        const size =
            getMapSize();


        /*
           Centre horizontally when possible.

           Start near the TOP vertically because your
           regenerated DreamGrid now runs a long way
           north/south.
        */

        panX =
            Math.max(
                24,
                (
                    viewport.clientWidth -
                    size.width
                ) / 2
            );


        panY = 24;


        zoom = 1.00;

        applyTransform();

    }


    /* ========================================================
       ZOOM AROUND A SCREEN POSITION
       ======================================================== */

    function zoomAt(
        screenX,
        screenY,
        newZoom
    ){

        newZoom =
            clamp(
                newZoom,
                MIN_ZOOM,
                MAX_ZOOM
            );


        if(
            Math.abs(
                newZoom - zoom
            ) < 0.0001
        ){
            return;
        }


        /*
           Which point in the unscaled map is currently
           beneath the mouse?
        */

        const worldX =
            (
                screenX -
                panX
            ) / zoom;


        const worldY =
            (
                screenY -
                panY
            ) / zoom;


        /*
           Keep that SAME world point beneath the mouse
           after changing zoom.
        */

        panX =
            screenX -
            worldX * newZoom;


        panY =
            screenY -
            worldY * newZoom;


        zoom = newZoom;


        applyTransform();

    }


    function zoomAtCentre(newZoom){

        zoomAt(

            viewport.clientWidth / 2,

            viewport.clientHeight / 2,

            newZoom

        );

    }


    /* ========================================================
       MOUSE WHEEL ZOOM
       ======================================================== */

    function handleWheel(event){

        event.preventDefault();
        event.stopPropagation();


        const rect =
            viewport.getBoundingClientRect();


        const mouseX =
            event.clientX -
            rect.left;


        const mouseY =
            event.clientY -
            rect.top;


        const newZoom =
            event.deltaY < 0

                ? zoom * ZOOM_FACTOR

                : zoom / ZOOM_FACTOR;


        zoomAt(
            mouseX,
            mouseY,
            newZoom
        );

    }


    /* ========================================================
       LEFT BUTTON GRAB / PAN
       ======================================================== */

    function isUiControl(target){

        return !!target.closest(
            "button, a, input, select, textarea, label"
        );

    }


    function handlePointerDown(event){

        /*
           LEFT button only.
        */

        if(event.button !== 0){
            return;
        }


        if(
            isUiControl(
                event.target
            )
        ){
            return;
        }


        pointerDown = true;

        dragging = false;

        suppressNextClick = false;

        activePointerId =
            event.pointerId;


        pointerStartX =
            event.clientX;

        pointerStartY =
            event.clientY;


        panStartX =
            panX;

        panStartY =
            panY;


        /*
           Do NOT capture the pointer yet.

           A normal click must remain targeted at the
           actual region box so Region Details still works.

           Pointer capture begins only after we detect
           a real drag in handlePointerMove().
        */

    }


    function handlePointerMove(event){

        if(
            !pointerDown ||
            event.pointerId !== activePointerId
        ){
            return;
        }


        const dx =
            event.clientX -
            pointerStartX;


        const dy =
            event.clientY -
            pointerStartY;


        if(
            !dragging &&
            (
                Math.abs(dx) >= DRAG_THRESHOLD ||
                Math.abs(dy) >= DRAG_THRESHOLD
            )
        ){

            dragging = true;

            viewport.classList.add(
                "firestorm-map-grabbing"
            );


            /*
               We now know this is a REAL drag.

               Capture the pointer only now so ordinary
               region clicks still reach the region's
               original click handler.
            */

            try{

                viewport.setPointerCapture(
                    event.pointerId
                );

            }
            catch(e){}

        }


        if(!dragging){
            return;
        }


        event.preventDefault();


        /*
           THIS is the main difference from the old system.

           We are moving the MAP ITSELF,
           not changing scrollbar positions.
        */

        panX =
            panStartX +
            dx;


        panY =
            panStartY +
            dy;


        applyTransform();

    }


    function finishPointer(event){

        if(!pointerDown){
            return;
        }


        if(
            event.pointerId !== undefined &&
            activePointerId !== null &&
            event.pointerId !== activePointerId
        ){
            return;
        }


        if(dragging){

            suppressNextClick = true;

        }


        pointerDown = false;

        dragging = false;


        viewport.classList.remove(
            "firestorm-map-grabbing"
        );


        try{

            if(
                activePointerId !== null &&
                viewport.hasPointerCapture(
                    activePointerId
                )
            ){

                viewport.releasePointerCapture(
                    activePointerId
                );

            }

        }
        catch(e){}


        activePointerId = null;

    }


    /* ========================================================
       DO NOT SELECT REGION AFTER A DRAG
       ======================================================== */

    function captureClick(event){

        if(!suppressNextClick){
            return;
        }


        suppressNextClick = false;


        event.preventDefault();

        event.stopPropagation();

        event.stopImmediatePropagation();

    }


    /* ========================================================
       FIT ENTIRE REAL GRID INTO THE VIEWPORT
       ======================================================== */

    function fitMap(){

        const size =
            getMapSize();


        const availableWidth =
            Math.max(
                100,
                viewport.clientWidth - 40
            );


        const availableHeight =
            Math.max(
                100,
                viewport.clientHeight - 40
            );


        zoom =
            clamp(
                Math.min(

                    availableWidth /
                    size.width,

                    availableHeight /
                    size.height

                ),

                MIN_ZOOM,
                1.00
            );


        panX =
            (
                viewport.clientWidth -
                size.width * zoom
            ) / 2;


        panY =
            (
                viewport.clientHeight -
                size.height * zoom
            ) / 2;


        applyTransform();

    }


    /* ========================================================
       100% VIEW
       ======================================================== */

    function reset100(){

        zoom = 1.00;


        const size =
            getMapSize();


        panX =
            Math.max(
                24,
                (
                    viewport.clientWidth -
                    size.width
                ) / 2
            );


        panY = 24;


        applyTransform();

    }


    /* ========================================================
       CONTROLS
       ======================================================== */

    function addControls(){

        if(
            document.getElementById(
                "firestorm-map-controls"
            )
        ){
            return;
        }


        let toolbar =
            document.querySelector(
                ".gridmap-toolbar"
            );


        if(!toolbar){

            toolbar =
                viewport.parentElement;

        }


        const controls =
            document.createElement("div");


        controls.id =
            "firestorm-map-controls";


        controls.className =
            "firestorm-map-controls";


        controls.innerHTML = `

            <span class="firestorm-map-control-title">
                MAP
            </span>

            <button
                type="button"
                id="firestorm-map-minus"
                class="firestorm-map-button"
                title="Zoom out">
                âˆ’
            </button>

            <span
                id="firestorm-map-zoom-value"
                class="firestorm-map-zoom-value">
                100%
            </span>

            <button
                type="button"
                id="firestorm-map-plus"
                class="firestorm-map-button"
                title="Zoom in">
                +
            </button>

            <button
                type="button"
                id="firestorm-map-reset"
                class="firestorm-map-button">
                100%
            </button>

            <button
                type="button"
                id="firestorm-map-fit"
                class="firestorm-map-button">
                FIT
            </button>

            <span class="firestorm-map-help">
                Wheel = zoom &nbsp; | &nbsp;
                Left drag = move
            </span>

        `;


        toolbar.appendChild(
            controls
        );


        readout =
            document.getElementById(
                "firestorm-map-zoom-value"
            );


        document
            .getElementById(
                "firestorm-map-minus"
            )
            .addEventListener(
                "click",
                function(){

                    zoomAtCentre(
                        zoom /
                        ZOOM_FACTOR
                    );

                }
            );


        document
            .getElementById(
                "firestorm-map-plus"
            )
            .addEventListener(
                "click",
                function(){

                    zoomAtCentre(
                        zoom *
                        ZOOM_FACTOR
                    );

                }
            );


        document
            .getElementById(
                "firestorm-map-reset"
            )
            .addEventListener(
                "click",
                reset100
            );


        document
            .getElementById(
                "firestorm-map-fit"
            )
            .addEventListener(
                "click",
                fitMap
            );

    }


    /* ========================================================
       START
       ======================================================== */

    
    /* ========================================================
       REGION SEARCH CONTROL API

       Uses the SAME panX / panY / zoom engine that already
       controls the map. Nothing competes with drag or wheel.
       ======================================================== */

    function centreAustraliaRegion(
        element
    ){

        if(
            !viewport ||
            !map ||
            !element
        ){
            return;
        }


        const width =
            Math.max(
                1,
                element.offsetWidth
            );


        const height =
            Math.max(
                1,
                element.offsetHeight
            );


        const centreX =
            element.offsetLeft +
            width / 2;


        const centreY =
            element.offsetTop +
            height / 2;


        /*
           Pick a useful zoom depending on region size.
        */

        const fitX =
            (
                viewport.clientWidth *
                0.45
            ) /
            width;


        const fitY =
            (
                viewport.clientHeight *
                0.45
            ) /
            height;


        zoom =
            clamp(
                Math.min(
                    1.20,
                    fitX,
                    fitY
                ),
                0.50,
                1.20
            );


        panX =
            viewport.clientWidth / 2 -
            centreX * zoom;


        panY =
            viewport.clientHeight / 2 -
            centreY * zoom;


        applyTransform();

    }


    window.AustraliaGridMapView = {

        centreRegion:
            centreAustraliaRegion,

        /* AUSTRALIA USER MAP SET VIEW API V1 */

        setView:function(
            nextZoom,
            nextPanX,
            nextPanY
        ){

            const z =
                Number(
                    nextZoom
                );

            const x =
                Number(
                    nextPanX
                );

            const y =
                Number(
                    nextPanY
                );


            if(
                !Number.isFinite(z) ||
                !Number.isFinite(x) ||
                !Number.isFinite(y)
            ){

                return false;

            }


            zoom =
                clamp(
                    z,
                    MIN_ZOOM,
                    MAX_ZOOM
                );


            panX =
                x;


            panY =
                y;


            applyTransform();


            try{

                saveAustraliaMapView();

            }
            catch(error){
            }


            return true;

        },

        /* END AUSTRALIA USER MAP SET VIEW API V1 */


        current:function(){

            return {
                zoom:zoom,
                panX:panX,
                panY:panY
            };

        }

    };

function start(){

        viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        map =
            document.getElementById(
                "grid-map"
            );


        if(!viewport || !map){
            return;
        }


        if(
            viewport.dataset.firestormCanvasReady ===
            "1"
        ){
            return;
        }


        viewport.dataset.firestormCanvasReady =
            "1";


        /*
           Any old browser scrollbar state is irrelevant now.
        */

        viewport.scrollLeft = 0;
        viewport.scrollTop = 0;


        viewport.addEventListener(
            "wheel",
            handleWheel,
            {
                passive:false
            }
        );


        viewport.addEventListener(
            "pointerdown",
            handlePointerDown
        );


        viewport.addEventListener(
            "pointermove",
            handlePointerMove,
            {
                passive:false
            }
        );


        viewport.addEventListener(
            "pointerup",
            finishPointer
        );


        viewport.addEventListener(
            "pointercancel",
            finishPointer
        );


        viewport.addEventListener(
            "click",
            captureClick,
            true
        );


        viewport.addEventListener(
            "dragstart",
            function(event){
                event.preventDefault();
            }
        );


        addControls();


        /*
           Wait until the normal Grid Map renderer has
           calculated its natural dimensions.
        */

        setTimeout(
            positionInitialMap,
            250
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            start
        );

    }
    else{

        start();

    }

})();

/* END Grid MAP - FIRESTORM CANVAS PAN ZOOM V2 */

</script>


<script>

/* ============================================================
   Grid MAP - FIRESTORM WATER ENGINE V4

   Continuously moves:

   1. Main ocean
   2. Deep current
   3. Surface shimmer

   Firestorm pan/zoom coordinates remain independent.
   ============================================================ */

(function(){

    let viewport = null;

    let started = false;

    let startTime = 0;

    let lastFrame = 0;


    /*
       Around 30 FPS is plenty for background water
       and keeps CPU/GPU usage sensible.
    */

    const FRAME_TIME = 1000 / 30;


    function animateWater(now){

        if(!viewport){
            return;
        }


        requestAnimationFrame(
            animateWater
        );


        if(
            now - lastFrame <
            FRAME_TIME
        ){
            return;
        }


        lastFrame = now;


        const seconds =
            (
                now -
                startTime
            ) / 1000;


        /*
           =====================================================
           MAIN OCEAN

           Slow steady diagonal movement.
           This is the layer that was previously stationary.
           =====================================================
        */

        const baseX =
            seconds * 18;

        const baseY =
            seconds * 8;


        /*
           =====================================================
           DEEP CURRENT

           Slower than surface.
           =====================================================
        */

        const deepX =
            seconds * 10;

        const deepY =
            seconds * 5;


        /*
           =====================================================
           SURFACE RIPPLE

           Moves in a different direction and faster.
           =====================================================
        */

        const surfaceX =
            seconds * -25;

        const surfaceY =
            seconds * 14;


        viewport.style.setProperty(
            "--fs-water-base-x",
            baseX.toFixed(2) + "px"
        );


        viewport.style.setProperty(
            "--fs-water-base-y",
            baseY.toFixed(2) + "px"
        );


        viewport.style.setProperty(
            "--fs-water-deep-x",
            deepX.toFixed(2) + "px"
        );


        viewport.style.setProperty(
            "--fs-water-deep-y",
            deepY.toFixed(2) + "px"
        );


        viewport.style.setProperty(
            "--fs-water-surface-x",
            surfaceX.toFixed(2) + "px"
        );


        viewport.style.setProperty(
            "--fs-water-surface-y",
            surfaceY.toFixed(2) + "px"
        );

    }


    function startWaterEngine(){

        if(started){
            return;
        }


        viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        if(!viewport){
            return;
        }


        started = true;

        startTime =
            performance.now();


        requestAnimationFrame(
            animateWater
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            startWaterEngine
        );

    }
    else{

        startWaterEngine();

    }

})();

/* END Grid MAP - FIRESTORM WATER ENGINE V4 */

</script>


<script>

/* ============================================================
   Grid MAP - HOP TO REGION V1

   Single click:
       Select region / show normal Region Details

   Double click:
       Launch Firestorm hop:// teleport

   Region Details button:
       HOP TO REGION

   Does NOT modify:
       map pan
       map zoom
       moving water
       DreamGrid coordinates
       island positions
   ============================================================ */

(function(){

    const GRID_HOP_HOST =
        <?php echo json_encode(ag_dg_hop_host(), JSON_UNESCAPED_SLASHES); ?>;

    const LANDING_Z = 25;


    let regionData = [];
    let regionPromise = null;

    let selectedRegion = null;

    let hopButton = null;
    let hopDestination = null;


    /* ========================================================
       NAME NORMALISER
       ======================================================== */

    function normalise(value){

        return String(value || "")
            .trim()
            .replace(/\s+/g," ")
            .toLowerCase();

    }


    /* ========================================================
       LOAD THE SAME REAL REGION DATA USED BY THE MAP
       ======================================================== */

    async function loadRegionData(){

        if(regionData.length){
            return regionData;
        }


        if(regionPromise){
            return regionPromise;
        }


        regionPromise =
            fetch(
                "/Other/grid-map-data-user.php",
                {
                    credentials:"same-origin",
                    cache:"no-store"
                }
            )
            .then(function(response){

                if(!response.ok){

                    throw new Error(
                        "HTTP " +
                        response.status
                    );

                }

                return response.json();

            })
            .then(function(data){

                if(
                    !data ||
                    !data.ok ||
                    !Array.isArray(data.regions)
                ){

                    throw new Error(
                        "Invalid region map response"
                    );

                }


                /*
                   Longest names first.

                   This prevents:
                   "NORTI FARM"
                   from matching
                   "Norti Farm 2"
                   too early.
                */

                regionData =
                    data.regions
                        .slice()
                        .sort(function(a,b){

                            return String(
                                b.RegionName || ""
                            ).length -
                            String(
                                a.RegionName || ""
                            ).length;

                        });


                return regionData;

            })
            .catch(function(error){

                console.error(
                    "HOP region data error:",
                    error
                );

                regionPromise = null;

                return [];

            });


        return regionPromise;

    }


    /* ========================================================
       RESOLVE A CLICKED MAP CARD TO ITS REAL REGION
       ======================================================== */

    async function resolveRegion(tile){

        if(!tile){
            return null;
        }


        const regions =
            await loadRegionData();


        if(!regions.length){
            return null;
        }


        /*
           First try common data attributes in case the
           existing Grid Map renderer already supplied one.
        */

        const candidates = [

            tile.dataset.regionName,

            tile.dataset.region,

            tile.dataset.name,

            tile.getAttribute(
                "data-region-name"
            ),

            tile.getAttribute(
                "title"
            ),

            tile.getAttribute(
                "aria-label"
            )

        ];


        for(
            let i = 0;
            i < candidates.length;
            i++
        ){

            const candidate =
                normalise(
                    candidates[i]
                );


            if(!candidate){
                continue;
            }


            const exact =
                regions.find(
                    function(region){

                        return normalise(
                            region.RegionName
                        ) === candidate;

                    }
                );


            if(exact){

                tile.dataset.regionName =
                    exact.RegionName;

                return exact;

            }

        }


        /*
           Fallback:
           compare the visible card text against known
           real region names.
        */

        const cardText =
            normalise(
                tile.innerText ||
                tile.textContent
            );


        for(
            let i = 0;
            i < regions.length;
            i++
        ){

            const region =
                regions[i];


            const name =
                normalise(
                    region.RegionName
                );


            if(
                cardText === name ||
                cardText.startsWith(
                    name + " "
                ) ||
                cardText.includes(
                    name
                )
            ){

                tile.dataset.regionName =
                    region.RegionName;

                return region;

            }

        }


        return null;

    }


    /* ========================================================
       CREATE HOP URL
       ======================================================== */

    function buildHop(region){

        if(!region){
            return "";
        }


        const sizeX =
            Math.max(
                256,
                Number(region.SizeX || 256)
            );


        const sizeY =
            Math.max(
                256,
                Number(region.SizeY || 256)
            );


        /*
           Land approximately in the centre of the
           real region / varregion.
        */

        const landingX =
            Math.floor(
                sizeX / 2
            );


        const landingY =
            Math.floor(
                sizeY / 2
            );


        const encodedRegion =
            encodeURIComponent(
                String(
                    region.RegionName || ""
                )
            );


        return (
            "hop://" +
            GRID_HOP_HOST +
            "/" +
            encodedRegion +
            "/" +
            landingX +
            "/" +
            landingY +
            "/" +
            LANDING_Z +
            "/"
        );

    }


    /* ========================================================
       LAUNCH FIRESTORM
       ======================================================== */

    function launchHop(region){

        const hop =
            buildHop(region);


        if(!hop){
            return;
        }


        /*
           Use an anchor rather than window.open().

           This avoids ordinary popup-window behaviour and
           lets Windows/browser hand the hop:// protocol
           to the registered viewer.
        */

        const link =
            document.createElement("a");


        link.href = hop;

        link.style.display =
            "none";


        document.body.appendChild(
            link
        );


        link.click();


        setTimeout(
            function(){

                link.remove();

            },
            1000
        );

    }


    /* ========================================================
       REGION DETAILS BUTTON
       ======================================================== */

    function findDetailsPanel(){

        /*
           Prefer obvious IDs/classes first.
        */

        const direct =
            document.querySelector(
                "#region-details," +
                ".region-details," +
                ".gridmap-details," +
                "[data-region-details]"
            );


        if(direct){
            return direct;
        }


        /*
           Fallback:
           Find the existing REGION DETAILS heading and
           walk upward until we reach the full panel that
           also contains STATUS / LOCATION / OWNER.
        */

        const elements =
            Array.from(
                document.querySelectorAll(
                    "h1,h2,h3,h4,h5,h6,div,span"
                )
            );


        const heading =
            elements.find(
                function(el){

                    return (
                        el.children.length === 0 &&
                        el.textContent
                            .trim()
                            .toUpperCase() ===
                        "REGION DETAILS"
                    );

                }
            );


        if(!heading){
            return null;
        }


        let node =
            heading.parentElement;


        while(
            node &&
            node !== document.body
        ){

            const text =
                String(
                    node.textContent || ""
                ).toUpperCase();


            if(
                text.includes(
                    "REGION DETAILS"
                ) &&
                text.includes(
                    "STATUS"
                ) &&
                text.includes(
                    "LOCATION"
                ) &&
                text.includes(
                    "OWNER"
                )
            ){

                return node;

            }


            node =
                node.parentElement;

        }


        return heading.parentElement;

    }


    function installHopButton(){

        if(
            document.getElementById(
                "gridmap-hop-button"
            )
        ){

            hopButton =
                document.getElementById(
                    "gridmap-hop-button"
                );

            hopDestination =
                document.getElementById(
                    "gridmap-hop-destination"
                );

            return;

        }


        const details =
            findDetailsPanel();


        if(!details){
            return;
        }


        const actions =
            document.createElement(
                "div"
            );


        actions.className =
            "gridmap-hop-actions";


        actions.innerHTML = `

            <button
                type="button"
                id="gridmap-hop-button"
                class="gridmap-hop-button"
                disabled>

                â†— HOP TO REGION

            </button>

            <span
                id="gridmap-hop-destination"
                class="gridmap-hop-destination">

                Select a region first.

            </span>

            <span
                class="gridmap-hop-help">

                Double-click a region on the map to hop directly.

            </span>

        `;


        details.appendChild(
            actions
        );


        hopButton =
            document.getElementById(
                "gridmap-hop-button"
            );


        hopDestination =
            document.getElementById(
                "gridmap-hop-destination"
            );


        hopButton.addEventListener(
            "click",
            function(){

                if(selectedRegion){

                    launchHop(
                        selectedRegion
                    );

                }

            }
        );

    }


    function updateHopButton(region){

        installHopButton();


        selectedRegion =
            region || null;


        if(!hopButton){
            return;
        }


        if(!region){

            hopButton.disabled =
                true;


            if(hopDestination){

                hopDestination.textContent =
                    "Select a region first.";

            }

            return;

        }


        hopButton.disabled =
            false;


        if(hopDestination){

            hopDestination.innerHTML =
                "Destination: <strong>" +
                String(
                    region.RegionName || ""
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
                ) +
                "</strong>";

        }

    }


    /* ========================================================
       SINGLE CLICK
       Keep normal Region Details behaviour.
       We only remember which region was selected.
       ======================================================== */

    async function handleSingleClick(event){

        const tile =
            event.target.closest(
                ".map-region"
            );


        if(!tile){
            return;
        }


        const region =
            await resolveRegion(
                tile
            );


        if(region){

            updateHopButton(
                region
            );

        }

    }


    /* ========================================================
       DOUBLE CLICK
       Direct HOP to Firestorm.
       ======================================================== */

    async function handleDoubleClick(event){

        const tile =
            event.target.closest(
                ".map-region"
            );


        if(!tile){
            return;
        }


        const region =
            await resolveRegion(
                tile
            );


        if(!region){
            return;
        }


        event.preventDefault();

        event.stopPropagation();


        updateHopButton(
            region
        );


        launchHop(
            region
        );

    }


    /* ========================================================
       START
       ======================================================== */

    function startHopSystem(){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){
            return;
        }


        if(
            map.dataset.hopSystemReady ===
            "1"
        ){
            return;
        }


        map.dataset.hopSystemReady =
            "1";


        /*
           Preload the real region metadata.
        */

        loadRegionData();


        /*
           Delegated events continue working after
           REFRESH MAP recreates the region cards.
        */

        map.addEventListener(
            "click",
            handleSingleClick
        );


        map.addEventListener(
            "dblclick",
            handleDoubleClick
        );


        installHopButton();


        /*
           Region Details may render slightly later.
        */

        setTimeout(
            installHopButton,
            500
        );


        setTimeout(
            installHopButton,
            1500
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            startHopSystem
        );

    }
    else{

        startHopSystem();

    }

})();

/* END Grid MAP - HOP TO REGION V1 */

</script>


<script>

/* ============================================================
   Grid MAP - SEARCH SELECT MEMORY V2
   ============================================================ */

(function(){

    let regions = [];

    let selectedName = "";

    const SELECTED_KEY =
        "AustraliaGridMapSelectedRegionV2";


    function norm(value){

        return String(
            value || ""
        )
        .trim()
        .replace(
            /\s+/g,
            " "
        )
        .toLowerCase();

    }


    async function loadRegionsForSearch(){

        try{

            const response =
                await fetch(
                    "/Other/grid-map-data-user.php",
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
                data &&
                Array.isArray(
                    data.regions
                )
            ){

                regions =
                    data.regions
                    .slice()
                    .sort(
                        function(a,b){

                            return String(
                                b.RegionName || ""
                            ).length -
                            String(
                                a.RegionName || ""
                            ).length;

                        }
                    );

            }

        }
        catch(error){

            console.error(
                "Region search data:",
                error
            );

        }

    }


    function nameFromTile(tile){

        if(!tile){
            return "";
        }


        const saved =
            tile.dataset.regionName ||
            tile.dataset.region ||
            tile.dataset.name;


        if(saved){

            return String(saved);

        }


        const text =
            norm(
                tile.innerText ||
                tile.textContent
            );


        for(
            let i = 0;
            i < regions.length;
            i++
        ){

            const name =
                String(
                    regions[i].RegionName ||
                    ""
                );


            const n =
                norm(name);


            if(
                n &&
                (
                    text === n ||
                    text.startsWith(
                        n + " "
                    ) ||
                    text.includes(n)
                )
            ){

                tile.dataset.regionName =
                    name;

                return name;

            }

        }


        return "";

    }


    function findTile(name){

        const wanted =
            norm(name);


        const tiles =
            Array.from(
                document.querySelectorAll(
                    "#grid-map .map-region"
                )
            );


        for(
            let i = 0;
            i < tiles.length;
            i++
        ){

            if(
                norm(
                    nameFromTile(
                        tiles[i]
                    )
                ) ===
                wanted
            ){

                return tiles[i];

            }

        }


        return null;

    }


    function selectTile(
        tile,
        name
    ){

        document
            .querySelectorAll(
                "#grid-map .gridmap-selected-region"
            )
            .forEach(
                function(item){

                    item.classList.remove(
                        "gridmap-selected-region"
                    );

                }
            );


        if(!tile){

            selectedName = "";

            try{

                localStorage.removeItem(
                    SELECTED_KEY
                );

            }
            catch(error){}

            return;

        }


        tile.classList.add(
            "gridmap-selected-region"
        );


        selectedName =
            String(
                name ||
                nameFromTile(tile) ||
                ""
            );


        try{

            localStorage.setItem(
                SELECTED_KEY,
                selectedName
            );

        }
        catch(error){}

    }


    function status(
        text,
        state
    ){

        const el =
            document.getElementById(
                "gridmap-search-status"
            );


        if(!el){
            return;
        }


        el.textContent =
            text || "";


        el.classList.remove(
            "found",
            "error"
        );


        if(state){

            el.classList.add(
                state
            );

        }

    }


    function metadataMatch(query){

        const wanted =
            norm(query);


        let match =
            regions.find(
                function(region){

                    return norm(
                        region.RegionName
                    ) === wanted;

                }
            );


        if(match){
            return match;
        }


        match =
            regions.find(
                function(region){

                    return norm(
                        region.RegionName
                    ).startsWith(
                        wanted
                    );

                }
            );


        if(match){
            return match;
        }


        return (
            regions.find(
                function(region){

                    return norm(
                        region.RegionName
                    ).includes(
                        wanted
                    );

                }
            ) ||
            null
        );

    }


    async function searchRegion(){

        const input =
            document.getElementById(
                "gridmap-search-input"
            );


        if(!input){
            return;
        }


        const query =
            String(
                input.value || ""
            ).trim();


        if(!query){

            status(
                "TYPE NAME",
                "error"
            );

            return;

        }


        if(!regions.length){

            await loadRegionsForSearch();

        }


        const region =
            metadataMatch(
                query
            );


        if(!region){

            status(
                "NOT FOUND",
                "error"
            );

            return;

        }


        const name =
            String(
                region.RegionName || ""
            );


        const tile =
            findTile(
                name
            );


        if(!tile){

            status(
                "NOT ON MAP",
                "error"
            );

            return;

        }


        input.value =
            name;


        selectTile(
            tile,
            name
        );


        if(
            window.AustraliaGridMapView &&
            typeof
            window.AustraliaGridMapView
                .centreRegion ===
            "function"
        ){

            window
                .AustraliaGridMapView
                .centreRegion(
                    tile
                );

        }


        /*
           Use the map's EXISTING normal click handler
           to populate Region Details.

           This is one click only, so it cannot activate
           the double-click HOP system.
        */

        setTimeout(
            function(){

                tile.click();

            },
            80
        );


        status(
            "FOUND",
            "found"
        );

    }


    function addSearchBar(){

        if(
            document.getElementById(
                "gridmap-region-search-v2"
            )
        ){
            return;
        }


        let toolbar =
            document.querySelector(
                ".gridmap-toolbar"
            );


        const viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        if(!viewport){
            return;
        }


        if(!toolbar){

            toolbar =
                viewport.parentElement;

        }


        const box =
            document.createElement(
                "div"
            );


        box.id =
            "gridmap-region-search-v2";


        box.className =
            "gridmap-region-search";


        box.innerHTML = `

            <span class="gridmap-search-title">
                FIND REGION
            </span>

            <input
                type="text"
                id="gridmap-search-input"
                class="gridmap-search-input"
                placeholder="NORTI FARM, TESTER..."
                autocomplete="off"
                spellcheck="false">

            <button
                type="button"
                id="gridmap-search-find"
                class="gridmap-search-button">
                FIND
            </button>

            <button
                type="button"
                id="gridmap-search-clear"
                class="gridmap-search-button">
                CLEAR
            </button>

            <span
                id="gridmap-search-status"
                class="gridmap-search-status">
            </span>

        `;


        toolbar.appendChild(
            box
        );


        const input =
            document.getElementById(
                "gridmap-search-input"
            );


        document
            .getElementById(
                "gridmap-search-find"
            )
            .addEventListener(
                "click",
                searchRegion
            );


        document
            .getElementById(
                "gridmap-search-clear"
            )
            .addEventListener(
                "click",
                function(){

                    input.value = "";

                    status("");

                    selectTile(
                        null,
                        ""
                    );

                    input.focus();

                }
            );


        input.addEventListener(
            "keydown",
            function(event){

                if(
                    event.key ===
                    "Enter"
                ){

                    event.preventDefault();

                    searchRegion();

                }

            }
        );

    }


    function addSelectionHandler(){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){
            return;
        }


        map.addEventListener(
            "click",
            function(event){

                const tile =
                    event.target.closest(
                        ".map-region"
                    );


                if(!tile){
                    return;
                }


                selectTile(
                    tile,
                    nameFromTile(tile)
                );

            }
        );


        /*
           Region cards are recreated by REFRESH MAP.
           Reapply the gold selection afterwards.
        */

        const observer =
            new MutationObserver(
                function(){

                    if(!selectedName){
                        return;
                    }


                    const replacement =
                        findTile(
                            selectedName
                        );


                    if(replacement){

                        replacement.classList.add(
                            "gridmap-selected-region"
                        );

                    }

                }
            );


        observer.observe(
            map,
            {
                childList:true,
                subtree:true
            }
        );

    }


    async function startSearchFeatures(){

        await loadRegionsForSearch();

        addSearchBar();

        addSelectionHandler();


        /*
           Restore selected region highlight after browser
           refresh. It does NOT automatically teleport.
        */

        try{

            selectedName =
                localStorage.getItem(
                    SELECTED_KEY
                ) || "";

        }
        catch(error){

            selectedName = "";

        }


        if(selectedName){

            setTimeout(
                function(){

                    const tile =
                        findTile(
                            selectedName
                        );


                    if(tile){

                        tile.classList.add(
                            "gridmap-selected-region"
                        );

                    }

                },
                400
            );

        }

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            startSearchFeatures
        );

    }
    else{

        startSearchFeatures();

    }

})();

/* END Grid MAP - SEARCH SELECT MEMORY V2 */

</script>


<script>

/* ============================================================
   Grid MAP - READABLE SMALL REGION BOXES CLIENT V1

   Enlarges ONLY:

       1x1 region visual boxes
       2x2 region visual boxes

   The box remains centred on the original real OpenSim
   region position.

   DOES NOT CHANGE:

       Real region coordinates
       Island position
       Island size
       Map pan
       Map zoom
       Moving ocean
       Search
       Gold selection
       Region Details
       Double-click HOP
   ============================================================ */

(function(){

    const ONE_BY_ONE_WIDTH  = 132;
    const ONE_BY_ONE_HEIGHT = 102;

    const TWO_BY_TWO_WIDTH  = 174;
    const TWO_BY_TWO_HEIGHT = 144;


    let regionData = [];

    let processing = false;


    /* ========================================================
       NORMALISE REGION NAMES
       ======================================================== */

    function normalise(value){

        return String(
            value || ""
        )
        .trim()
        .replace(
            /\s+/g,
            " "
        )
        .toLowerCase();

    }


    /* ========================================================
       LOAD THE REAL REGION SIZES
       ======================================================== */

    async function loadRegionData(){

        try{

            const response =
                await fetch(
                    "/Other/grid-map-data-user.php",
                    {
                        credentials:
                            "same-origin",

                        cache:
                            "no-store"
                    }
                );


            if(!response.ok){

                throw new Error(
                    "HTTP " +
                    response.status
                );

            }


            const data =
                await response.json();


            let found = [];


            if(
                data &&
                Array.isArray(
                    data.regions
                )
            ){

                found =
                    data.regions;

            }
            else if(
                Array.isArray(data)
            ){

                found =
                    data;

            }


            /*
               Longest names first so:

                   Norti Farm 2

               is matched before:

                   NORTI FARM
            */

            regionData =
                found
                .slice()
                .sort(
                    function(a,b){

                        return String(
                            b.RegionName || ""
                        ).length -
                        String(
                            a.RegionName || ""
                        ).length;

                    }
                );


        }
        catch(error){

            console.error(
                "Small region card metadata:",
                error
            );

        }

    }


    /* ========================================================
       RESOLVE A MAP BOX TO ITS REAL REGION
       ======================================================== */

    function resolveRegion(tile){

        if(!tile){
            return null;
        }


        const possibleNames = [

            tile.dataset.regionName,

            tile.dataset.region,

            tile.dataset.name,

            tile.getAttribute(
                "data-region-name"
            ),

            tile.getAttribute(
                "aria-label"
            ),

            tile.getAttribute(
                "title"
            )

        ];


        /*
           Try an exact data attribute first.
        */

        for(
            let i = 0;
            i < possibleNames.length;
            i++
        ){

            const candidate =
                normalise(
                    possibleNames[i]
                );


            if(!candidate){
                continue;
            }


            const exact =
                regionData.find(
                    function(region){

                        return normalise(
                            region.RegionName
                        ) === candidate;

                    }
                );


            if(exact){

                tile.dataset.regionName =
                    exact.RegionName;

                return exact;

            }

        }


        /*
           Otherwise identify it from the visible box text.
        */

        const boxText =
            normalise(
                tile.innerText ||
                tile.textContent
            );


        for(
            let i = 0;
            i < regionData.length;
            i++
        ){

            const region =
                regionData[i];


            const regionName =
                normalise(
                    region.RegionName
                );


            if(
                regionName &&
                (
                    boxText ===
                        regionName
                    ||
                    boxText.startsWith(
                        regionName + " "
                    )
                    ||
                    boxText.includes(
                        regionName
                    )
                )
            ){

                tile.dataset.regionName =
                    region.RegionName;

                return region;

            }

        }


        return null;

    }


    /* ========================================================
       DETERMINE REGION CELL SIZE
       ======================================================== */

    function regionCellsX(region){

        let cells =
            Number(
                region.CellsX
            );


        if(
            !Number.isFinite(cells) ||
            cells <= 0
        ){

            cells =
                Math.max(
                    1,
                    Math.round(
                        Number(
                            region.SizeX ||
                            256
                        ) /
                        256
                    )
                );

        }


        return cells;

    }


    function regionCellsY(region){

        let cells =
            Number(
                region.CellsY
            );


        if(
            !Number.isFinite(cells) ||
            cells <= 0
        ){

            cells =
                Math.max(
                    1,
                    Math.round(
                        Number(
                            region.SizeY ||
                            256
                        ) /
                        256
                    )
                );

        }


        return cells;

    }


    /* ========================================================
       READ ORIGINAL REGION BOX GEOMETRY
       ======================================================== */

    function numberValue(value){

        const result =
            parseFloat(
                String(
                    value || ""
                )
            );


        return Number.isFinite(result)
            ?
            result
            :
            null;

    }


    function originalGeometry(tile){

        /*
           Save the original map geometry once.

           That means running this code again can never keep
           making the box larger and larger.
        */

        let left =
            numberValue(
                tile.dataset.australiaOriginalLeft
            );


        let top =
            numberValue(
                tile.dataset.australiaOriginalTop
            );


        let width =
            numberValue(
                tile.dataset.australiaOriginalWidth
            );


        let height =
            numberValue(
                tile.dataset.australiaOriginalHeight
            );


        if(left === null){

            left =
                numberValue(
                    tile.style.left
                );

        }


        if(top === null){

            top =
                numberValue(
                    tile.style.top
                );

        }


        if(width === null){

            width =
                numberValue(
                    tile.style.width
                );

        }


        if(height === null){

            height =
                numberValue(
                    tile.style.height
                );

        }


        if(left === null){

            left =
                tile.offsetLeft;

        }


        if(top === null){

            top =
                tile.offsetTop;

        }


        if(
            width === null ||
            width <= 0
        ){

            width =
                tile.offsetWidth;

        }


        if(
            height === null ||
            height <= 0
        ){

            height =
                tile.offsetHeight;

        }


        /*
           Only store ORIGINAL values.
        */

        if(
            !tile.dataset
                .australiaOriginalLeft
        ){

            tile.dataset
                .australiaOriginalLeft =
                String(left);

        }


        if(
            !tile.dataset
                .australiaOriginalTop
        ){

            tile.dataset
                .australiaOriginalTop =
                String(top);

        }


        if(
            !tile.dataset
                .australiaOriginalWidth
        ){

            tile.dataset
                .australiaOriginalWidth =
                String(width);

        }


        if(
            !tile.dataset
                .australiaOriginalHeight
        ){

            tile.dataset
                .australiaOriginalHeight =
                String(height);

        }


        return {
            left:left,
            top:top,
            width:width,
            height:height
        };

    }


    /* ========================================================
       ENLARGE SMALL REGION BOX
       ======================================================== */

    function resizeRegionCard(
        tile,
        region
    ){

        if(
            !tile ||
            !region
        ){
            return;
        }


        const cellsX =
            regionCellsX(
                region
            );


        const cellsY =
            regionCellsY(
                region
            );


        /*
           ONLY 1x1 and 2x2.

           3x3, 4x4, 7x7 etc stay EXACTLY as they are.
        */

        let minWidth = 0;
        let minHeight = 0;


        if(
            cellsX === 1 &&
            cellsY === 1
        ){

            minWidth =
                ONE_BY_ONE_WIDTH;

            minHeight =
                ONE_BY_ONE_HEIGHT;

        }
        else if(
            cellsX <= 2 &&
            cellsY <= 2
        ){

            minWidth =
                TWO_BY_TWO_WIDTH;

            minHeight =
                TWO_BY_TWO_HEIGHT;

        }
        else{

            return;

        }


        const original =
            originalGeometry(
                tile
            );


        const newWidth =
            Math.max(
                original.width,
                minWidth
            );


        const newHeight =
            Math.max(
                original.height,
                minHeight
            );


        /*
           Keep EXACTLY the same centre point.

           Example:

           Original region:
                    [ ]

           Enlarged visual card:
                [       ]

           The centre does not move.
        */

        const newLeft =
            original.left -
            (
                newWidth -
                original.width
            ) / 2;


        const newTop =
            original.top -
            (
                newHeight -
                original.height
            ) / 2;


        tile.style.left =
            newLeft + "px";


        tile.style.top =
            newTop + "px";


        tile.style.width =
            newWidth + "px";


        tile.style.height =
            newHeight + "px";


        tile.classList.add(
            "australia-small-region-card"
        );


        tile.dataset
            .australiaReadableSmallCard =
            "1";

    }


    /* ========================================================
       PROCESS EVERY CURRENT REGION
       ======================================================== */

    function processRegionCards(){

        if(processing){
            return;
        }


        processing = true;


        try{

            const tiles =
                document.querySelectorAll(
                    "#grid-map .map-region"
                );


            tiles.forEach(
                function(tile){

                    const region =
                        resolveRegion(
                            tile
                        );


                    if(region){

                        resizeRegionCard(
                            tile,
                            region
                        );

                    }

                }
            );

        }
        finally{

            processing = false;

        }

    }


    /* ========================================================
       START
       ======================================================== */

    async function start(){

        await loadRegionData();


        processRegionCards();


        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){
            return;
        }


        /*
           REFRESH MAP recreates region boxes.

           Automatically resize the new 1x1 and 2x2 boxes.
        */

        const observer =
            new MutationObserver(
                function(){

                    setTimeout(
                        processRegionCards,
                        20
                    );

                }
            );


        observer.observe(
            map,
            {
                childList:true,
                subtree:true
            }
        );


        /*
           A couple of delayed passes cover map rendering
           that finishes after DOMContentLoaded.
        */

        setTimeout(
            processRegionCards,
            300
        );


        setTimeout(
            processRegionCards,
            1000
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            start
        );

    }
    else{

        start();

    }

})();

/* END Grid MAP - READABLE SMALL REGION BOXES CLIENT V1 */

</script>


<script>

/* ============================================================
   Grid MAP - FULL SCREEN MAP CLIENT V1

   Uses the browser Fullscreen API on .gridmap-scroll.

   ESC exits normally.

   The existing FIND REGION box is temporarily moved into the
   fullscreen toolbar and returned to its original position
   when fullscreen closes.

   DOES NOT MODIFY:
       pan / zoom engine
       water engine
       islands
       region coordinates
       selection
       HOP system
   ============================================================ */

(function(){

    let searchBox = null;

    let searchOriginalParent = null;

    let searchOriginalNextSibling = null;

    let dock = null;

    let viewport = null;


    /* ========================================================
       FIND SEARCH BAR
       ======================================================== */

    function findSearchBox(){

        return (
            document.getElementById(
                "gridmap-region-search-v2"
            )
            ||
            document.getElementById(
                "gridmap-region-search"
            )
            ||
            document.querySelector(
                ".gridmap-region-search"
            )
        );

    }


    /* ========================================================
       BUILD FULL SCREEN DOCK
       ======================================================== */

    function createDock(){

        if(!viewport){
            return;
        }


        dock =
            document.getElementById(
                "gridmap-fullscreen-dock"
            );


        if(dock){
            return;
        }


        dock =
            document.createElement(
                "div"
            );


        dock.id =
            "gridmap-fullscreen-dock";


        dock.className =
            "gridmap-fullscreen-dock";


        dock.innerHTML = `

            <div class="gridmap-fullscreen-label">
                <strong>Grid</strong>
                FULL SCREEN MAP
            </div>

            <button
                type="button"
                id="gridmap-fullscreen-exit"
                class="gridmap-fullscreen-exit">

                EXIT FULL SCREEN

            </button>

        `;


        viewport.appendChild(
            dock
        );


        document
            .getElementById(
                "gridmap-fullscreen-exit"
            )
            .addEventListener(
                "click",
                exitFullscreenMap
            );

    }


    /* ========================================================
       MOVE SEARCH INTO FULL SCREEN
       ======================================================== */

    function moveSearchIntoDock(){

        searchBox =
            findSearchBox();


        if(
            !searchBox ||
            !dock
        ){
            return;
        }


        if(
            searchBox.parentElement ===
            dock
        ){
            return;
        }


        searchOriginalParent =
            searchBox.parentElement;


        searchOriginalNextSibling =
            searchBox.nextSibling;


        dock.appendChild(
            searchBox
        );

    }


    /* ========================================================
       PUT SEARCH BACK EXACTLY WHERE IT WAS
       ======================================================== */

    function restoreSearch(){

        if(
            !searchBox ||
            !searchOriginalParent
        ){
            return;
        }


        if(
            searchOriginalNextSibling &&
            searchOriginalNextSibling.parentNode ===
            searchOriginalParent
        ){

            searchOriginalParent.insertBefore(
                searchBox,
                searchOriginalNextSibling
            );

        }
        else{

            searchOriginalParent.appendChild(
                searchBox
            );

        }


        searchOriginalParent = null;
        searchOriginalNextSibling = null;

    }


    /* ========================================================
       ENTER
       ======================================================== */

    async function enterFullscreenMap(){

        if(!viewport){
            return;
        }


        try{

            if(viewport.requestFullscreen){

                await viewport.requestFullscreen();

            }
            else if(
                viewport.webkitRequestFullscreen
            ){

                viewport.webkitRequestFullscreen();

            }

        }
        catch(error){

            console.error(
                "Grid Map fullscreen:",
                error
            );

        }

    }


    /* ========================================================
       EXIT
       ======================================================== */

    async function exitFullscreenMap(){

        try{

            if(document.fullscreenElement){

                await document.exitFullscreen();

            }
            else if(
                document.webkitFullscreenElement &&
                document.webkitExitFullscreen
            ){

                document.webkitExitFullscreen();

            }

        }
        catch(error){

            console.error(
                "Grid Map fullscreen exit:",
                error
            );

        }

    }


    /* ========================================================
       FULLSCREEN STATE CHANGE
       ======================================================== */

    function fullscreenChanged(){

        const active =
            document.fullscreenElement ===
                viewport
            ||
            document.webkitFullscreenElement ===
                viewport;


        if(active){

            moveSearchIntoDock();


            document.body.classList.add(
                "australia-map-fullscreen-active"
            );

        }
        else{

            restoreSearch();


            document.body.classList.remove(
                "australia-map-fullscreen-active"
            );

        }


        /*
           Browser dimensions have just changed.

           The existing map engine still owns pan/zoom.
           A resize event simply lets any existing layout
           listeners react normally.
        */

        setTimeout(
            function(){

                window.dispatchEvent(
                    new Event(
                        "resize"
                    )
                );

            },
            80
        );

    }


    /* ========================================================
       ADD ENTRY BUTTON
       ======================================================== */

    function addFullscreenButton(){

        if(
            document.getElementById(
                "gridmap-fullscreen-enter"
            )
        ){
            return;
        }


        const button =
            document.createElement(
                "button"
            );


        button.id =
            "gridmap-fullscreen-enter";


        button.type =
            "button";


        button.className =
            "gridmap-fullscreen-button";


        button.innerHTML =
            '<img class="ag-sentinel-direct-icon ag-sentinel-xs" src="/Other/assets/icons/sentinel/view.png" alt="" aria-hidden="true" draggable="false" decoding="async"> FULL SCREEN MAP';


        /*
           Prefer the existing search toolbar because it places
           the control directly beside the map tools.

           If that does not exist, place it immediately before
           the map viewport.
        */

        const search =
            findSearchBox();


        if(
            search &&
            search.parentElement
        ){

            search.parentElement.insertBefore(
                button,
                search.nextSibling
            );

        }
        else{

            viewport.parentElement.insertBefore(
                button,
                viewport
            );

        }


        button.addEventListener(
            "click",
            enterFullscreenMap
        );

    }


    /* ========================================================
       START
       ======================================================== */

    function startFullscreenMap(){

        viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        if(!viewport){
            return;
        }


        createDock();

        addFullscreenButton();


        document.addEventListener(
            "fullscreenchange",
            fullscreenChanged
        );


        document.addEventListener(
            "webkitfullscreenchange",
            fullscreenChanged
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            startFullscreenMap
        );

    }
    else{

        startFullscreenMap();

    }

})();

/* END Grid MAP - FULL SCREEN MAP CLIENT V1 */

</script>


<script>

/* ============================================================
   Grid MAP - REGION HOVER INFO CLIENT V1

   Read-only visual feature.

   DOES NOT MODIFY:
       coordinates
       region boxes
       islands
       pan
       zoom
       water
       fullscreen
       search
       selection
       HOP
   ============================================================ */

(function(){

    let mapRegions = [];

    let liveRegions = [];

    let tooltip = null;

    let currentTile = null;


    /* ========================================================
       HELPERS
       ======================================================== */

    function normalise(value){

        return String(
            value || ""
        )
        .trim()
        .replace(
            /\s+/g,
            " "
        )
        .toLowerCase();

    }


    function safe(value){

        const text =
            String(
                value === undefined ||
                value === null ||
                value === ""
                    ? "â€”"
                    : value
            );

        return text
            .replace(/&/g,"&amp;")
            .replace(/</g,"&lt;")
            .replace(/>/g,"&gt;")
            .replace(/"/g,"&quot;")
            .replace(/'/g,"&#39;");

    }


    function numberOrDash(value){

        const n =
            Number(value);

        if(!Number.isFinite(n)){
            return "â€”";
        }

        return n.toLocaleString();

    }


    /* ========================================================
       LOAD REAL MAP DATA
       ======================================================== */

    async function loadMapData(){

        try{

            const response =
                await fetch(
                    "/Other/grid-map-data-user.php",
                    {
                        credentials:"same-origin",
                        cache:"no-store"
                    }
                );


            const data =
                await response.json();


            if(
                data &&
                Array.isArray(data.regions)
            ){

                mapRegions =
                    data.regions.slice();

            }
            else if(Array.isArray(data)){

                mapRegions =
                    data.slice();

            }

        }
        catch(error){

            console.error(
                "Hover map metadata:",
                error
            );

        }

    }


    /* ========================================================
       LOAD LIVE REGION INFORMATION
       ======================================================== */

    async function loadLiveData(){

        try{

            const response =
                await fetch(
                    "/Other/grid-regions-user.php?hover=1&nocache=" +
                    Date.now(),
                    {
                        credentials:"same-origin",
                        cache:"no-store"
                    }
                );


            const data =
                await response.json();


            if(
                data &&
                Array.isArray(data.regions)
            ){

                liveRegions =
                    data.regions.slice();

            }
            else if(Array.isArray(data)){

                liveRegions =
                    data.slice();

            }

        }
        catch(error){

            console.error(
                "Hover live metadata:",
                error
            );

        }

    }


    /* ========================================================
       REGION LOOKUP
       ======================================================== */

    function regionNameFromTile(tile){

        if(!tile){
            return "";
        }


        const saved =

            tile.dataset.regionName ||
            tile.dataset.region ||
            tile.dataset.name ||
            tile.getAttribute(
                "data-region-name"
            );


        if(saved){

            return String(saved);

        }


        const cardText =
            normalise(
                tile.innerText ||
                tile.textContent
            );


        const sorted =
            mapRegions
            .slice()
            .sort(
                function(a,b){

                    return String(
                        b.RegionName || ""
                    ).length -
                    String(
                        a.RegionName || ""
                    ).length;

                }
            );


        for(
            let i = 0;
            i < sorted.length;
            i++
        ){

            const name =
                String(
                    sorted[i].RegionName ||
                    ""
                );


            const n =
                normalise(name);


            if(
                n &&
                (
                    cardText === n ||
                    cardText.startsWith(
                        n + " "
                    ) ||
                    cardText.includes(n)
                )
            ){

                tile.dataset.regionName =
                    name;

                return name;

            }

        }


        return "";

    }


    function getMapRegion(name){

        const wanted =
            normalise(name);


        return (
            mapRegions.find(
                function(region){

                    return normalise(
                        region.RegionName
                    ) === wanted;

                }
            ) ||
            null
        );

    }


    function getLiveRegion(name){

        const wanted =
            normalise(name);


        return (
            liveRegions.find(
                function(region){

                    return normalise(
                        region.RegionName
                    ) === wanted;

                }
            ) ||
            null
        );

    }


    /* ========================================================
       STATUS
       ======================================================== */

    function getStatus(region){

        if(!region){
            return "Unknown";
        }


        return String(
            region.Status ||
            region.status ||
            region.State ||
            region.state ||
            "Unknown"
        );

    }


    function statusClass(status){

        const s =
            normalise(status);


        if(
            [
                "booted",
                "running",
                "online"
            ].includes(s)
        ){
            return "online";
        }


        if(
            [
                "stopped",
                "offline",
                "shutdown"
            ].includes(s)
        ){
            return "offline";
        }


        if(
            [
                "warning",
                "booting",
                "starting",
                "restarting",
                "stopping",
                "backingup",
                "savingoar"
            ].includes(s)
        ){
            return "warning";
        }


        return "";
    }


    function statusDisplay(status){

        const s =
            normalise(status);


        if(s === "booted"){
            return "ONLINE";
        }

        if(!s){
            return "UNKNOWN";
        }

        return String(status).toUpperCase();

    }


    /* ========================================================
       PICK FIELD FROM DIFFERENT DREAMGRID API NAMES
       ======================================================== */

    function firstValue(object, names){

        if(!object){
            return "";
        }


        for(
            let i = 0;
            i < names.length;
            i++
        ){

            const value =
                object[
                    names[i]
                ];


            if(
                value !== undefined &&
                value !== null &&
                String(value) !== ""
            ){

                return value;

            }

        }


        return "";

    }


    /* ========================================================
       TOOLTIP
       ======================================================== */

    function createTooltip(){

        tooltip =
            document.getElementById(
                "gridmap-region-hover"
            );


        if(tooltip){
            return;
        }


        tooltip =
            document.createElement(
                "div"
            );


        tooltip.id =
            "gridmap-region-hover";


        tooltip.className =
            "gridmap-region-hover";


        
        /* ====================================================
           Grid MAP - FULLSCREEN HOVER FIX V2

           The tooltip must be a CHILD of the fullscreen map
           element or Chromium will hide it in fullscreen mode.
           ==================================================== */

        const tooltipHost =
            document.querySelector(
                ".gridmap-scroll"
            ) ||
            document.body;


        tooltipHost.appendChild(
            tooltip
        );


    }


    function buildTooltip(tile){

        const name =
            regionNameFromTile(
                tile
            );


        if(!name){
            return false;
        }


        const mapRegion =
            getMapRegion(name);


        const live =
            getLiveRegion(name);


        const status =
            getStatus(live);


        const cellsX =
            Number(
                firstValue(
                    mapRegion,
                    ["CellsX"]
                )
            ) ||
            Math.max(
                1,
                Math.round(
                    Number(
                        firstValue(
                            mapRegion,
                            ["SizeX"]
                        ) ||
                        256
                    ) /
                    256
                )
            );


        const cellsY =
            Number(
                firstValue(
                    mapRegion,
                    ["CellsY"]
                )
            ) ||
            Math.max(
                1,
                Math.round(
                    Number(
                        firstValue(
                            mapRegion,
                            ["SizeY"]
                        ) ||
                        256
                    ) /
                    256
                )
            );


        const avatars =
            firstValue(
                live,
                [
                    "Avatars",
                    "avatars",
                    "AvatarCount",
                    "avatarCount",
                    "Users",
                    "users"
                ]
            );


        const prims =
            firstValue(
                live,
                [
                    "Prims",
                    "prims",
                    "PrimCount",
                    "primCount",
                    "Objects",
                    "objects"
                ]
            );


        const estate =
            firstValue(
                live,
                [
                    "EstateName",
                    "estateName",
                    "Estate",
                    "estate"
                ]
            );


        const owner =
            firstValue(
                live,
                [
                    "EstateOwner",
                    "estateOwner",
                    "Owner",
                    "owner"
                ]
            );


        const port =
            firstValue(
                mapRegion,
                [
                    "InternalPort",
                    "Port",
                    "port"
                ]
            );


        const location =
            (
                firstValue(
                    mapRegion,
                    ["X","x"]
                ) !== ""
                &&
                firstValue(
                    mapRegion,
                    ["Y","y"]
                ) !== ""
            )
            ?
            firstValue(
                mapRegion,
                ["X","x"]
            ) +
            ", " +
            firstValue(
                mapRegion,
                ["Y","y"]
            )
            :
            "â€”";


        tooltip.innerHTML = `

            <div class="gridmap-hover-header">

                <span
                    class="gridmap-hover-dot ${safe(
                        statusClass(status)
                    )}">
                </span>

                <span
                    class="gridmap-hover-name">

                    ${safe(name)}

                </span>

            </div>


            <div class="gridmap-hover-grid">

                <span class="gridmap-hover-label">
                    STATUS
                </span>

                <span class="gridmap-hover-value highlight">
                    ${safe(
                        statusDisplay(status)
                    )}
                </span>


                <span class="gridmap-hover-label">
                    SIZE
                </span>

                <span class="gridmap-hover-value">
                    ${safe(
                        cellsX + " Ã— " + cellsY
                    )}
                </span>


                <span class="gridmap-hover-label">
                    LOCATION
                </span>

                <span class="gridmap-hover-value">
                    ${safe(location)}
                </span>


                <span class="gridmap-hover-label">
                    AVATARS
                </span>

                <span class="gridmap-hover-value">
                    ${safe(
                        avatars === ""
                            ? "â€”"
                            : avatars
                    )}
                </span>


                <span class="gridmap-hover-label">
                    PRIMS
                </span>

                <span class="gridmap-hover-value">
                    ${safe(
                        prims === ""
                            ? "â€”"
                            : numberOrDash(prims)
                    )}
                </span>


                <span class="gridmap-hover-label">
                    ESTATE
                </span>

                <span class="gridmap-hover-value">
                    ${safe(estate)}
                </span>


                <span class="gridmap-hover-label">
                    OWNER
                </span>

                <span class="gridmap-hover-value">
                    ${safe(owner)}
                </span>


                <span class="gridmap-hover-label">
                    PORT
                </span>

                <span class="gridmap-hover-value">
                    ${safe(port)}
                </span>

            </div>


            <div class="gridmap-hover-footer">
                CLICK FOR DETAILS &nbsp; â€¢ &nbsp; DOUBLE CLICK TO HOP
            </div>

        `;


        return true;

    }


    /* ========================================================
       POSITION NEXT TO CURSOR
       ======================================================== */

    function positionTooltip(event){

        if(!tooltip){
            return;
        }


        const margin = 14;

        const width =
            tooltip.offsetWidth ||
            248;


        const height =
            tooltip.offsetHeight ||
            250;


        let left =
            event.clientX +
            16;


        let top =
            event.clientY +
            16;


        if(
            left + width + margin >
            window.innerWidth
        ){

            left =
                event.clientX -
                width -
                16;

        }


        if(
            top + height + margin >
            window.innerHeight
        ){

            top =
                event.clientY -
                height -
                16;

        }


        left =
            Math.max(
                margin,
                left
            );


        top =
            Math.max(
                margin,
                top
            );


        tooltip.style.left =
            left + "px";


        tooltip.style.top =
            top + "px";

    }


    /* ========================================================
       EVENTS
       ======================================================== */

    function mouseMove(event){

        const tile =
            event.target.closest(
                "#grid-map .map-region"
            );


        if(!tile){

            currentTile = null;

            tooltip.classList.remove(
                "is-visible"
            );

            return;
        }


        if(tile !== currentTile){

            currentTile = tile;


            if(
                buildTooltip(tile)
            ){

                tooltip.classList.add(
                    "is-visible"
                );

            }

        }


        positionTooltip(
            event
        );

    }


    function mouseLeave(){

        currentTile = null;

        if(tooltip){

            tooltip.classList.remove(
                "is-visible"
            );

        }

    }


    /* ========================================================
       START
       ======================================================== */

    async function startHoverInfo(){

        createTooltip();


        await Promise.all([
            loadMapData(),
            loadLiveData()
        ]);


        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){
            return;
        }


        map.addEventListener(
            "mousemove",
            mouseMove
        );


        map.addEventListener(
            "mouseleave",
            mouseLeave
        );


        /*
           Refresh live hover data every 15 seconds.

           This does NOT redraw or alter the map.
        */

        setInterval(
            loadLiveData,
            15000
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            startHoverInfo
        );

    }
    else{

        startHoverInfo();

    }

})();

/* END Grid MAP - REGION HOVER INFO CLIENT V1 */

</script>


<script>

/* ============================================================
   Grid MAP - COMPASS CLIENT V2

   Fixed viewport overlay.

   It is deliberately attached to .gridmap-scroll so:

       Pan does NOT move it
       Zoom does NOT resize it
       Fullscreen DOES show it
       Moving water does NOT affect it

   North = top of the OpenSim grid.
   ============================================================ */

(function(){

    function installAustraliaCompass(){

        const viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        if(!viewport){

            console.warn(
                "Grid Compass: viewport not found."
            );

            return;

        }


        /*
           Never create duplicates.
        */

        if(
            document.getElementById(
                "gridmap-compass"
            )
        ){

            return;

        }


        const compass =
            document.createElement(
                "div"
            );


        compass.id =
            "gridmap-compass";


        compass.className =
            "gridmap-compass";


        compass.setAttribute(
            "aria-hidden",
            "true"
        );


        compass.innerHTML = `

            <div
                class="gridmap-compass-ring">
            </div>


            <div
                class="gridmap-compass-cross">
            </div>


            <div
                class="gridmap-compass-needle-north">
            </div>


            <div
                class="gridmap-compass-needle-south">
            </div>


            <div
                class="gridmap-compass-centre">
            </div>


            <span
                class="
                    gridmap-compass-direction
                    gridmap-compass-north
                ">
                N
            </span>


            <span
                class="
                    gridmap-compass-direction
                    gridmap-compass-east
                ">
                E
            </span>


            <span
                class="
                    gridmap-compass-direction
                    gridmap-compass-south
                ">
                S
            </span>


            <span
                class="
                    gridmap-compass-direction
                    gridmap-compass-west
                ">
                W
            </span>


            <span
                class="gridmap-compass-caption">
                GRID NORTH
            </span>

        `;


        /*
           IMPORTANT:

           Compass is a direct child of the SAME viewport
           used by the Full Screen Map.

           This is the same principle we used to fix
           Hover Info in fullscreen.
        */

        viewport.appendChild(
            compass
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            installAustraliaCompass
        );

    }
    else{

        installAustraliaCompass();

    }

})();

/* END Grid MAP - COMPASS CLIENT V2 */

</script>


<script>

/* ============================================================
   Grid MAP - YOU ARE HERE CLIENT V1

   Actual OpenSim avatar X/Y location.

   Marker is placed INSIDE #grid-map so it naturally follows:

       pan
       zoom
       fullscreen

   It does NOT alter the existing Firestorm map engine.
   ============================================================ */

(function(){

    const API_TOKEN =
        "USER_SESSION_BOUND_TOKEN";


    /*
       Only used if the existing page header/session object
       cannot provide the signed-in avatar name.
    */

    const FALLBACK_AVATAR =
        "Sharpened Razor";


    let marker =
        null;

    let lastRegion =
        "";

    let missCount =
        0;

    let stopped =
        false;


    /* ========================================================
       NORMALISE
       ======================================================== */

    function normalise(value){

        return String(
            value || ""
        )
        .trim()
        .replace(
            /\s+/g,
            " "
        )
        .toLowerCase();

    }


    /* ========================================================
       FIND SIGNED-IN AVATAR
       ======================================================== */

    function getAvatarName(){

        /*
           Existing Australia API pages commonly expose DG.
        */

        try{

            if(
                window.DG &&
                typeof window.DG === "object"
            ){

                const candidate =
                    window.DG.avatar ||
                    window.DG.username ||
                    window.DG.user ||
                    window.DG.name;


                if(
                    candidate &&
                    String(candidate).trim()
                ){

                    return String(
                        candidate
                    ).trim();

                }

            }

        }
        catch(error){
        }


        /*
           Fall back to the visible:

               Signed in as Sharpened Razor
        */

        const bodyText =
            String(
                document.body ?
                document.body.innerText :
                ""
            );


        const match =
            bodyText.match(
                /Signed\s+in\s+as\s+([^\r\n]{1,100})/i
            );


        if(match){

            let candidate =
                String(
                    match[1] || ""
                ).trim();


            candidate =
                candidate
                .replace(
                    /\s+(GRID OWNER|USER LEVEL|HOME|BACK|LOG OUT).*$/i,
                    ""
                )
                .trim();


            if(
                candidate.length >= 2 &&
                candidate.length <= 80
            ){

                return candidate;

            }

        }


        return FALLBACK_AVATAR;

    }


    /* ========================================================
       FIND REGION TILE
       ======================================================== */

    function findRegionTile(
        regionName
    ){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){
            return null;
        }


        const wanted =
            normalise(
                regionName
            );


        const tiles =
            Array.from(
                map.querySelectorAll(
                    ".map-region"
                )
            );


        for(
            let i = 0;
            i < tiles.length;
            i++
        ){

            const tile =
                tiles[i];


            const stored =
                tile.dataset.regionName ||
                tile.dataset.region ||
                tile.dataset.name ||
                tile.getAttribute(
                    "data-region-name"
                ) ||
                "";


            if(
                stored &&
                normalise(stored) ===
                wanted
            ){

                return tile;

            }


            const cardText =
                normalise(
                    tile.innerText ||
                    tile.textContent
                );


            if(
                cardText === wanted ||
                cardText.startsWith(
                    wanted + " "
                )
            ){

                return tile;

            }

        }


        return null;

    }


    /* ========================================================
       NUMBER
       ======================================================== */

    function numberValue(value){

        const n =
            parseFloat(
                value
            );


        return Number.isFinite(n)
            ? n
            : null;

    }


    function firstNumber(){

        for(
            let i = 0;
            i < arguments.length;
            i++
        ){

            const n =
                numberValue(
                    arguments[i]
                );


            if(n !== null){
                return n;
            }

        }


        return null;

    }


    /* ========================================================
       TRUE REGION GEOMETRY
       ======================================================== */

    function regionGeometry(tile){

        if(!tile){
            return null;
        }


        /*
           Our readable 1x1 / 2x2 card patch stores the REAL
           region footprint before visually enlarging the card.

           This is important: the YOU ARE HERE marker must use
           the real OpenSim footprint, not the enlarged label.
        */

        const left =
            firstNumber(
                tile.dataset.australiaOriginalLeft,
                tile.style.left,
                tile.offsetLeft
            );


        const top =
            firstNumber(
                tile.dataset.australiaOriginalTop,
                tile.style.top,
                tile.offsetTop
            );


        const width =
            firstNumber(
                tile.dataset.australiaOriginalWidth,
                tile.style.width,
                tile.offsetWidth
            );


        const height =
            firstNumber(
                tile.dataset.australiaOriginalHeight,
                tile.style.height,
                tile.offsetHeight
            );


        if(
            left === null ||
            top === null ||
            width === null ||
            height === null ||
            width <= 0 ||
            height <= 0
        ){

            return null;

        }


        return {
            left:left,
            top:top,
            width:width,
            height:height
        };

    }


    /* ========================================================
       CREATE MARKER
       ======================================================== */

    function ensureMarker(){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){
            return null;
        }


        if(
            marker &&
            marker.isConnected &&
            marker.parentElement === map
        ){

            return marker;

        }


        marker =
            document.createElement(
                "div"
            );


        marker.id =
            "australia-you-are-here";


        marker.className =
            "australia-you-are-here";


        marker.innerHTML = `

            <div
                class="australia-you-are-here-pulse">
            </div>

            <div
                class="australia-you-are-here-dot">
            </div>

            <div
                class="australia-you-are-here-label">

                <span
                    class="australia-you-are-here-title">
                    YOU ARE HERE
                </span>

                <span
                    class="australia-you-are-here-name">
                </span>

                <span
                    class="australia-you-are-here-position">
                </span>

            </div>

        `;


        marker.style.display =
            "none";


        map.appendChild(
            marker
        );


        return marker;

    }


    /* ========================================================
       PLACE LIVE MARKER
       ======================================================== */

    function placeMarker(data){

        const tile =
            findRegionTile(
                data.region
            );


        if(!tile){
            return false;
        }


        const geometry =
            regionGeometry(
                tile
            );


        if(!geometry){
            return false;
        }


        const sizeX =
            Math.max(
                1,
                Number(
                    data.size_x ||
                    256
                )
            );


        const sizeY =
            Math.max(
                1,
                Number(
                    data.size_y ||
                    256
                )
            );


        let localX =
            Number(
                data.pos_x
            );


        let localY =
            Number(
                data.pos_y
            );


        if(
            !Number.isFinite(localX) ||
            !Number.isFinite(localY)
        ){

            return false;

        }


        /*
           Keep marker within actual region footprint.
        */

        localX =
            Math.max(
                0,
                Math.min(
                    sizeX,
                    localX
                )
            );


        localY =
            Math.max(
                0,
                Math.min(
                    sizeY,
                    localY
                )
            );


        const fractionX =
            localX /
            sizeX;


        const fractionY =
            localY /
            sizeY;


        /*
           Browser Y increases DOWN.

           OpenSim Y increases NORTH / UP.

           Therefore local Y must be flipped.
        */

        const mapX =
            geometry.left +
            (
                fractionX *
                geometry.width
            );


        const mapY =
            geometry.top +
            (
                (
                    1 -
                    fractionY
                ) *
                geometry.height
            );


        const liveMarker =
            ensureMarker();


        if(!liveMarker){
            return false;
        }


        liveMarker.style.left =
            mapX + "px";


        liveMarker.style.top =
            mapY + "px";


        liveMarker.style.display =
            "block";


        const nameElement =
            liveMarker.querySelector(
                ".australia-you-are-here-name"
            );


        const positionElement =
            liveMarker.querySelector(
                ".australia-you-are-here-position"
            );


        if(nameElement){

            const nameText =
                String(
                    data.avatar ||
                    ""
                );


            if(
                nameElement.textContent !==
                nameText
            ){

                nameElement.textContent =
                    nameText;

            }

        }


        if(positionElement){

            const positionText =
                String(
                    data.region
                ) +
                "  |  " +
                Math.round(
                    Number(data.pos_x)
                ) +
                ", " +
                Math.round(
                    Number(data.pos_y)
                ) +
                ", " +
                Math.round(
                    Number(data.pos_z)
                );


            if(
                positionElement.textContent !==
                positionText
            ){

                positionElement.textContent =
                    positionText;

            }

        }


        return true;

    }


    /* ========================================================
       HIDE
       ======================================================== */

    function hideMarker(){

        const liveMarker =
            ensureMarker();


        if(liveMarker){

            liveMarker.style.display =
                "none";

        }

    }


    /* ========================================================
       LIVE REQUEST
       ======================================================== */

    async function updateLocation(){

        const avatar =
            getAvatarName();


        if(!avatar){
            return;
        }


        const params =
            new URLSearchParams();


        params.set(
            "token",
            API_TOKEN
        );


        params.set(
            "avatar",
            avatar
        );


        if(lastRegion){

            params.set(
                "last_region",
                lastRegion
            );

        }


        params.set(
            "nocache",
            String(
                Date.now()
            )
        );


        try{

            const response =
                await fetch(
                    "/Other/avatar-location-user.php?" +
                    params.toString(),
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
                !data ||
                !data.ok
            ){

                return;

            }


            if(
                data.found
            ){

                missCount =
                    0;


                lastRegion =
                    String(
                        data.region ||
                        ""
                    );


                placeMarker(
                    data
                );

            }
            else{

                missCount++;


                /*
                   Do not make the marker disappear instantly
                   during a region crossing / teleport.
                */

                if(
                    missCount >= 3
                ){

                    hideMarker();

                    lastRegion =
                        "";

                }

            }

        }
        catch(error){

            console.debug(
                "You Are Here update:",
                error
            );

        }

    }


    /* ========================================================
       POLL

       Same-region updates normally hit only ONE localhost
       RemoteAdmin port because lastRegion is checked first.
       ======================================================== */

    async function poll(){

        if(stopped){
            return;
        }


        await updateLocation();


        setTimeout(
            poll,
            3000
        );

    }


    /* ========================================================
       START
       ======================================================== */

    function startYouAreHere(){

        ensureMarker();


        setTimeout(
            poll,
            700
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            startYouAreHere
        );

    }
    else{

        startYouAreHere();

    }

})();

/* END Grid MAP - YOU ARE HERE CLIENT V1 */

</script>





<script>

/* ============================================================
   Grid MAP - REAL COORDINATE SCALE CLIENT V1

   Real OpenSim X / Y coordinates.

   Reads the existing region geometry and current #grid-map
   transform. It does NOT change pan, zoom or region positions.
   ============================================================ */

(function(){

    const DATA_URL =
        "/Other/grid-map-data-user.php";


    let viewport =
        null;

    let map =
        null;

    let scaleLayer =
        null;

    let regionData =
        [];


    let gridCellPixels =
        null;

    let originX =
        null;

    let originY =
        null;


    let renderPending =
        false;


    /* ========================================================
       NORMALISE
       ======================================================== */

    function normalise(
        value
    ){

        return String(
            value || ""
        )
        .trim()
        .replace(
            /\s+/g,
            " "
        )
        .toLowerCase();

    }


    /* ========================================================
       NUMBER
       ======================================================== */

    function numeric(
        value
    ){

        const n =
            parseFloat(
                value
            );


        return Number.isFinite(n)
            ? n
            : null;

    }


    function firstNumber(){

        for(
            let i = 0;
            i < arguments.length;
            i++
        ){

            const n =
                numeric(
                    arguments[i]
                );


            if(n !== null){
                return n;
            }

        }


        return null;

    }


    /* ========================================================
       FIND REGION TILE
       ======================================================== */

    function findRegionTile(
        regionName
    ){

        if(!map){
            return null;
        }


        const wanted =
            normalise(
                regionName
            );


        const tiles =
            Array.from(
                map.querySelectorAll(
                    ".map-region"
                )
            );


        for(
            const tile of tiles
        ){

            const stored =
                tile.dataset.regionName ||
                tile.dataset.region ||
                tile.dataset.name ||
                tile.getAttribute(
                    "data-region-name"
                ) ||
                "";


            if(
                stored &&
                normalise(stored) ===
                wanted
            ){

                return tile;

            }


            const text =
                normalise(
                    tile.innerText ||
                    tile.textContent
                );


            if(
                text === wanted ||
                text.startsWith(
                    wanted + " "
                )
            ){

                return tile;

            }

        }


        return null;

    }


    /* ========================================================
       REAL TILE GEOMETRY
       ======================================================== */

    function tileGeometry(
        tile
    ){

        if(!tile){
            return null;
        }


        /*
           The readable-small-region patch stores the original
           geometry before enlarging Welcome/FARM SHOP/etc.
        */

        const left =
            firstNumber(
                tile.dataset.australiaOriginalLeft,
                tile.style.left,
                tile.offsetLeft
            );


        const top =
            firstNumber(
                tile.dataset.australiaOriginalTop,
                tile.style.top,
                tile.offsetTop
            );


        const width =
            firstNumber(
                tile.dataset.australiaOriginalWidth,
                tile.style.width,
                tile.offsetWidth
            );


        const height =
            firstNumber(
                tile.dataset.australiaOriginalHeight,
                tile.style.height,
                tile.offsetHeight
            );


        if(
            left === null ||
            top === null ||
            width === null ||
            height === null ||
            width <= 0 ||
            height <= 0
        ){

            return null;

        }


        return {
            left:left,
            top:top,
            width:width,
            height:height
        };

    }


    /* ========================================================
       REGION VALUES
       ======================================================== */

    function regionName(
        region
    ){

        return String(
            region.RegionName ||
            region.regionName ||
            region.Name ||
            region.name ||
            ""
        ).trim();

    }


    function regionX(
        region
    ){

        return firstNumber(
            region.X,
            region.x,
            region.GridX,
            region.gridX
        );

    }


    function regionY(
        region
    ){

        return firstNumber(
            region.Y,
            region.y,
            region.GridY,
            region.gridY
        );

    }


    function regionCellsX(
        region
    ){

        const cells =
            firstNumber(
                region.CellsX,
                region.cellsX,
                region.cells_x
            );


        if(
            cells !== null &&
            cells > 0
        ){

            return cells;

        }


        const size =
            firstNumber(
                region.SizeX,
                region.sizeX,
                region.size_x
            );


        if(
            size !== null &&
            size > 0
        ){

            return Math.max(
                1,
                size / 256
            );

        }


        return 1;

    }


    function regionCellsY(
        region
    ){

        const cells =
            firstNumber(
                region.CellsY,
                region.cellsY,
                region.cells_y
            );


        if(
            cells !== null &&
            cells > 0
        ){

            return cells;

        }


        const size =
            firstNumber(
                region.SizeY,
                region.sizeY,
                region.size_y
            );


        if(
            size !== null &&
            size > 0
        ){

            return Math.max(
                1,
                size / 256
            );

        }


        return 1;

    }


    /* ========================================================
       CALIBRATE OPENSim COORDINATES TO MAP PIXELS

       Browser X increases right.
       OpenSim X increases east/right.

       Browser Y increases down.
       OpenSim Y increases north/up.

       tile.left:
           originX + X * cell

       tile.top:
           originY - (Y + CellsY) * cell
       ======================================================== */

    function calibrate(){

        if(
            !map ||
            !Array.isArray(regionData) ||
            !regionData.length
        ){

            return false;

        }


        const cellSamples =
            [];

        const xOrigins =
            [];

        const yOrigins =
            [];


        for(
            const region of regionData
        ){

            const name =
                regionName(
                    region
                );


            const x =
                regionX(
                    region
                );


            const y =
                regionY(
                    region
                );


            if(
                !name ||
                x === null ||
                y === null
            ){

                continue;

            }


            const tile =
                findRegionTile(
                    name
                );


            const geometry =
                tileGeometry(
                    tile
                );


            if(!geometry){
                continue;
            }


            const cellsX =
                regionCellsX(
                    region
                );


            const cellsY =
                regionCellsY(
                    region
                );


            const cellFromWidth =
                geometry.width /
                cellsX;


            const cellFromHeight =
                geometry.height /
                cellsY;


            if(
                Number.isFinite(cellFromWidth) &&
                cellFromWidth > 1
            ){

                cellSamples.push(
                    cellFromWidth
                );

            }


            if(
                Number.isFinite(cellFromHeight) &&
                cellFromHeight > 1
            ){

                cellSamples.push(
                    cellFromHeight
                );

            }

        }


        if(!cellSamples.length){
            return false;
        }


        cellSamples.sort(
            (a,b) =>
                a - b
        );


        const cell =
            cellSamples[
                Math.floor(
                    cellSamples.length / 2
                )
            ];


        for(
            const region of regionData
        ){

            const name =
                regionName(
                    region
                );


            const x =
                regionX(
                    region
                );


            const y =
                regionY(
                    region
                );


            if(
                !name ||
                x === null ||
                y === null
            ){

                continue;

            }


            const tile =
                findRegionTile(
                    name
                );


            const geometry =
                tileGeometry(
                    tile
                );


            if(!geometry){
                continue;
            }


            const cellsY =
                regionCellsY(
                    region
                );


            xOrigins.push(
                geometry.left -
                (
                    x *
                    cell
                )
            );


            yOrigins.push(
                geometry.top +
                (
                    (
                        y +
                        cellsY
                    ) *
                    cell
                )
            );

        }


        if(
            !xOrigins.length ||
            !yOrigins.length
        ){

            return false;

        }


        xOrigins.sort(
            (a,b) =>
                a - b
        );


        yOrigins.sort(
            (a,b) =>
                a - b
        );


        gridCellPixels =
            cell;


        originX =
            xOrigins[
                Math.floor(
                    xOrigins.length / 2
                )
            ];


        originY =
            yOrigins[
                Math.floor(
                    yOrigins.length / 2
                )
            ];


        return true;

    }


    /* ========================================================
       MAP TRANSFORM
       ======================================================== */

    function transformValues(){

        if(!map){

            return {
                zoom:1,
                panX:0,
                panY:0
            };

        }


        const transform =
            getComputedStyle(
                map
            ).transform;


        if(
            !transform ||
            transform === "none"
        ){

            return {
                zoom:1,
                panX:0,
                panY:0
            };

        }


        try{

            const matrix =
                new DOMMatrixReadOnly(
                    transform
                );


            return {

                zoom:
                    matrix.a || 1,

                panX:
                    matrix.e || 0,

                panY:
                    matrix.f || 0
            };

        }
        catch(error){

            const match =
                transform.match(
                    /matrix\(\s*([^,]+),\s*([^,]+),\s*([^,]+),\s*([^,]+),\s*([^,]+),\s*([^)]+)\)/
                );


            if(match){

                return {

                    zoom:
                        parseFloat(
                            match[1]
                        ) || 1,

                    panX:
                        parseFloat(
                            match[5]
                        ) || 0,

                    panY:
                        parseFloat(
                            match[6]
                        ) || 0
                };

            }

        }


        return {
            zoom:1,
            panX:0,
            panY:0
        };

    }


    /* ========================================================
       CHOOSE LABEL INTERVAL
       ======================================================== */

    function chooseStep(
        pixelsPerGrid,
        minimumSpacing
    ){

        const steps =
            [
                1,
                2,
                5,
                10,
                20,
                50,
                100
            ];


        for(
            const step of steps
        ){

            if(
                pixelsPerGrid *
                step >=
                minimumSpacing
            ){

                return step;

            }

        }


        return 100;

    }


    /* ========================================================
       CREATE SCALE
       ======================================================== */

    function ensureScale(){

        if(!viewport){
            return null;
        }


        scaleLayer =
            document.getElementById(
                "gridmap-coordinate-scale"
            );


        if(scaleLayer){
            return scaleLayer;
        }


        scaleLayer =
            document.createElement(
                "div"
            );


        scaleLayer.id =
            "gridmap-coordinate-scale";


        scaleLayer.innerHTML = `

            <div
                class="gridmap-coordinate-top">
            </div>

            <div
                class="gridmap-coordinate-bottom">
            </div>

            <div
                class="gridmap-coordinate-left">
            </div>

            <div
                class="gridmap-coordinate-right">
            </div>

            <div
                class="gridmap-coordinate-badge x">
                X
            </div>

            <div
                class="gridmap-coordinate-badge y">
                Y
            </div>

        `;


        viewport.appendChild(
            scaleLayer
        );


        return scaleLayer;

    }


    /* ========================================================
       RENDER
       ======================================================== */

    function render(){

        renderPending =
            false;


        if(
            !viewport ||
            !map ||
            !ensureScale()
        ){

            return;
        }


        if(
            gridCellPixels === null ||
            originX === null ||
            originY === null
        ){

            if(!calibrate()){
                return;
            }

        }


        /*
           Clear dynamic numbers but preserve the four
           background strips and X/Y badges.
        */

        scaleLayer
            .querySelectorAll(
                ".gridmap-coordinate-x," +
                ".gridmap-coordinate-y," +
                ".gridmap-coordinate-line-x," +
                ".gridmap-coordinate-line-y"
            )
            .forEach(
                element =>
                    element.remove()
            );


        const bounds =
            viewport.getBoundingClientRect();


        const width =
            bounds.width;


        const height =
            bounds.height;


        if(
            width <= 0 ||
            height <= 0
        ){

            return;
        }


        const transform =
            transformValues();


        const zoom =
            Math.max(
                0.0001,
                transform.zoom
            );


        const panX =
            transform.panX;


        const panY =
            transform.panY;


        const pixelsPerGrid =
            gridCellPixels *
            zoom;


        const xStep =
            chooseStep(
                pixelsPerGrid,
                58
            );


        const yStep =
            chooseStep(
                pixelsPerGrid,
                38
            );


        /*
           Visible X range.
        */

        const leftLocal =
            (
                0 -
                panX
            ) /
            zoom;


        const rightLocal =
            (
                width -
                panX
            ) /
            zoom;


        const minVisibleX =
            (
                leftLocal -
                originX
            ) /
            gridCellPixels;


        const maxVisibleX =
            (
                rightLocal -
                originX
            ) /
            gridCellPixels;


        /*
           Visible Y range.

           OpenSim Y increases in the opposite direction.
        */

        const topLocal =
            (
                0 -
                panY
            ) /
            zoom;


        const bottomLocal =
            (
                height -
                panY
            ) /
            zoom;


        const maxVisibleY =
            (
                originY -
                topLocal
            ) /
            gridCellPixels;


        const minVisibleY =
            (
                originY -
                bottomLocal
            ) /
            gridCellPixels;


        /* ====================================================
           X LABELS
           ==================================================== */

        let startX =
            Math.floor(
                minVisibleX /
                xStep
            ) *
            xStep;


        let xSafety =
            0;


        for(
            let coordinate = startX;
            coordinate <= maxVisibleX + xStep;
            coordinate += xStep
        ){

            xSafety++;


            if(xSafety > 300){
                break;
            }


            const localX =
                originX +
                (
                    coordinate *
                    gridCellPixels
                );


            const screenX =
                (
                    localX *
                    zoom
                ) +
                panX;


            if(
                screenX < 38 ||
                screenX > width - 38
            ){

                continue;
            }


            const topLabel =
                document.createElement(
                    "div"
                );


            topLabel.className =
                "gridmap-coordinate-x";


            topLabel.textContent =
                Math.round(
                    coordinate
                );


            topLabel.style.left =
                screenX +
                "px";


            scaleLayer.appendChild(
                topLabel
            );


            const bottomLabel =
                document.createElement(
                    "div"
                );


            bottomLabel.className =
                "gridmap-coordinate-x bottom";


            bottomLabel.textContent =
                Math.round(
                    coordinate
                );


            bottomLabel.style.left =
                screenX +
                "px";


            scaleLayer.appendChild(
                bottomLabel
            );


            const line =
                document.createElement(
                    "div"
                );


            line.className =
                "gridmap-coordinate-line-x";


            line.style.left =
                Math.round(
                    screenX
                ) +
                "px";


            scaleLayer.appendChild(
                line
            );

        }


        /* ====================================================
           Y LABELS
           ==================================================== */

        let startY =
            Math.floor(
                minVisibleY /
                yStep
            ) *
            yStep;


        let ySafety =
            0;


        for(
            let coordinate = startY;
            coordinate <= maxVisibleY + yStep;
            coordinate += yStep
        ){

            ySafety++;


            if(ySafety > 300){
                break;
            }


            const localY =
                originY -
                (
                    coordinate *
                    gridCellPixels
                );


            const screenY =
                (
                    localY *
                    zoom
                ) +
                panY;


            if(
                screenY < 31 ||
                screenY > height - 31
            ){

                continue;
            }


            const leftLabel =
                document.createElement(
                    "div"
                );


            leftLabel.className =
                "gridmap-coordinate-y";


            leftLabel.textContent =
                Math.round(
                    coordinate
                );


            leftLabel.style.top =
                screenY +
                "px";


            scaleLayer.appendChild(
                leftLabel
            );


            const rightLabel =
                document.createElement(
                    "div"
                );


            rightLabel.className =
                "gridmap-coordinate-y right";


            rightLabel.textContent =
                Math.round(
                    coordinate
                );


            rightLabel.style.top =
                screenY +
                "px";


            scaleLayer.appendChild(
                rightLabel
            );


            const line =
                document.createElement(
                    "div"
                );


            line.className =
                "gridmap-coordinate-line-y";


            line.style.top =
                Math.round(
                    screenY
                ) +
                "px";


            scaleLayer.appendChild(
                line
            );

        }

    }


    /* ========================================================
       THROTTLED RENDER
       ======================================================== */

    function requestRender(){

        if(renderPending){
            return;
        }


        renderPending =
            true;


        requestAnimationFrame(
            render
        );

    }


    /* ========================================================
       LOAD REAL REGION DATA
       ======================================================== */

    async function loadRegionData(){

        try{

            const response =
                await fetch(
                    DATA_URL +
                    "?coordinateScale=1&nocache=" +
                    Date.now(),
                    {
                        credentials:
                            "same-origin",

                        cache:
                            "no-store"
                    }
                );


            const data =
                await response.json();


            const possible =
                Array.isArray(data)
                    ? data
                    : (
                        Array.isArray(data.regions)
                            ? data.regions
                            : (
                                Array.isArray(data.data)
                                    ? data.data
                                    : []
                            )
                    );


            regionData =
                possible;


            gridCellPixels =
                null;


            originX =
                null;


            originY =
                null;


            setTimeout(
                requestRender,
                250
            );

        }
        catch(error){

            console.debug(
                "Coordinate Scale:",
                error
            );

        }

    }


    /* ========================================================
       INSTALL
       ======================================================== */

    function installCoordinateScale(){

        viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        map =
            document.getElementById(
                "grid-map"
            );


        if(
            !viewport ||
            !map
        ){

            return;
        }


        ensureScale();


        /*
           Watch transform changes generated by the existing
           Firestorm pan/zoom engine.
        */

        const mapObserver =
            new MutationObserver(
                requestRender
            );


        mapObserver.observe(
            map,
            {
                attributes:true,
                attributeFilter:[
                    "style",
                    "class"
                ],
                childList:true,
                subtree:false
            }
        );


        /*
           Region tiles can be recreated after map refresh.
        */

        const regionObserver =
            new MutationObserver(
                function(){

                    gridCellPixels =
                        null;

                    originX =
                        null;

                    originY =
                        null;

                    requestRender();

                }
            );


        regionObserver.observe(
            map,
            {
                childList:true,
                subtree:true
            }
        );


        /*
           Viewport size/fullscreen changes.
        */

        if(
            window.ResizeObserver
        ){

            const resizeObserver =
                new ResizeObserver(
                    requestRender
                );


            resizeObserver.observe(
                viewport
            );

        }


        window.addEventListener(
            "resize",
            requestRender
        );


        document.addEventListener(
            "fullscreenchange",
            function(){

                setTimeout(
                    requestRender,
                    80
                );

            }
        );


        document.addEventListener(
            "webkitfullscreenchange",
            function(){

                setTimeout(
                    requestRender,
                    80
                );

            }
        );


        /*
           Extra hooks for very fast wheel/drag activity.
        */

        viewport.addEventListener(
            "wheel",
            requestRender,
            {
                passive:true
            }
        );


        viewport.addEventListener(
            "pointermove",
            function(event){

                if(
                    event.buttons &
                    1
                ){

                    requestRender();

                }

            },
            {
                passive:true
            }
        );


        loadRegionData();


        setTimeout(
            requestRender,
            700
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            installCoordinateScale
        );

    }
    else{

        installCoordinateScale();

    }

})();

/* END Grid MAP - REAL COORDINATE SCALE CLIENT V1 */

</script>























<script>
/* ============================================================
   Grid MAP - USER IDENTITY V1
   ============================================================ */

window.AUSTRALIA_USER_MAP = true;

window.DG =
    window.DG || {};

window.DG.avatar =
    <?=json_encode($avatar)?>;

window.DG.level =
    <?=json_encode($level)?>;

window.DG.userMap =
    true;

/* END Grid MAP - USER IDENTITY V1 */
</script>




<script>

/* ============================================================
   Grid MAP - USER QUICK NAV CLIENT V2

   V2 FIX:
   Normal mode controls live OUTSIDE .gridmap-scroll.

   This prevents the map pan / drag engine from receiving
   pointer events from Quick Navigation.

   In fullscreen, the controls use the existing fullscreen dock.
   ============================================================ */

(function(){

    const DATA_URL =
        "/Other/grid-map-data-user.php";


    const LOCATION_URL =
        "/Other/avatar-location-user.php";


    let regions =
        [];


    let favorites =
        new Set();


    let selectedRegion =
        "";


    let nav =
        null;


    let normalHome =
        null;


    let jumpSelect =
        null;


    let favoriteButton =
        null;


    let statusHolder =
        null;


    let selectionObserver =
        null;


    /* ========================================================
       TEXT HELPERS
       ======================================================== */

    function normalise(value){

        return String(
            value || ""
        )
        .trim()
        .replace(
            /\s+/g,
            " "
        )
        .toLowerCase();

    }


    function signedInAvatar(){

        try{

            if(
                window.DG &&
                window.DG.avatar
            ){

                return String(
                    window.DG.avatar
                ).trim();

            }

        }
        catch(error){
        }


        try{

            if(
                window.DG_GRID_MAP &&
                window.DG_GRID_MAP.avatar
            ){

                return String(
                    window.DG_GRID_MAP.avatar
                ).trim();

            }

        }
        catch(error){
        }


        return "AustraliaGridUser";

    }


    function storageKey(){

        return (
            "AustraliaGridUserFavoritesV2::" +
            normalise(
                signedInAvatar()
            )
        );

    }


    function showStatus(
        message
    ){

        if(!statusHolder){

            return;

        }


        statusHolder.textContent =
            message || "";


        if(message){

            clearTimeout(
                showStatus.timer
            );


            showStatus.timer =
                setTimeout(
                    function(){

                        if(statusHolder){

                            statusHolder.textContent =
                                "";

                        }

                    },
                    3000
                );

        }

    }


    /* ========================================================
       FAVORITES
       ======================================================== */

    function loadFavorites(){

        favorites =
            new Set();


        try{

            const raw =
                localStorage.getItem(
                    storageKey()
                );


            if(!raw){

                return;

            }


            const list =
                JSON.parse(
                    raw
                );


            if(
                Array.isArray(
                    list
                )
            ){

                list.forEach(
                    function(name){

                        name =
                            String(
                                name || ""
                            ).trim();


                        if(name){

                            favorites.add(
                                name
                            );

                        }

                    }
                );

            }

        }
        catch(error){
        }

    }


    function saveFavorites(){

        try{

            localStorage.setItem(
                storageKey(),
                JSON.stringify(
                    Array.from(
                        favorites
                    )
                )
            );

        }
        catch(error){
        }

    }


    function isFavorite(
        name
    ){

        const wanted =
            normalise(
                name
            );


        for(
            const item
            of favorites
        ){

            if(
                normalise(
                    item
                ) === wanted
            ){

                return true;

            }

        }


        return false;

    }


    function removeFavorite(
        name
    ){

        const wanted =
            normalise(
                name
            );


        for(
            const item
            of Array.from(
                favorites
            )
        ){

            if(
                normalise(
                    item
                ) === wanted
            ){

                favorites.delete(
                    item
                );

            }

        }

    }


    function toggleFavorite(){

        if(
            !selectedRegion
        ){

            showStatus(
                "Select a region first."
            );

            return;

        }


        if(
            isFavorite(
                selectedRegion
            )
        ){

            removeFavorite(
                selectedRegion
            );


            showStatus(
                selectedRegion +
                " removed from Favorites"
            );

        }
        else{

            favorites.add(
                selectedRegion
            );


            showStatus(
                selectedRegion +
                " added to Favorites"
            );

        }


        saveFavorites();

        updateFavoriteButton();

        rebuildJumpList();

    }


    function updateFavoriteButton(){

        if(!favoriteButton){

            return;

        }


        if(
            !selectedRegion
        ){

            favoriteButton.disabled =
                true;


            favoriteButton.textContent =
                "â˜† FAVORITE";


            favoriteButton.classList.remove(
                "is-favorite"
            );


            return;

        }


        favoriteButton.disabled =
            false;


        if(
            isFavorite(
                selectedRegion
            )
        ){

            favoriteButton.textContent =
                "â˜… FAVORITED";


            favoriteButton.classList.add(
                "is-favorite"
            );

        }
        else{

            favoriteButton.textContent =
                "â˜† FAVORITE";


            favoriteButton.classList.remove(
                "is-favorite"
            );

        }

    }


    /* ========================================================
       REGION DATA
       ======================================================== */

    function regionName(
        region
    ){

        if(!region){

            return "";

        }


        return String(

            region.RegionName ||
            region.regionName ||
            region.Name ||
            region.name ||
            region.Region ||
            region.region ||
            ""

        ).trim();

    }


    function extractRegionNames(
        data
    ){

        let list =
            [];


        if(
            Array.isArray(
                data
            )
        ){

            list =
                data;

        }
        else if(
            data &&
            Array.isArray(
                data.regions
            )
        ){

            list =
                data.regions;

        }


        const seen =
            new Set();


        const names =
            [];


        for(
            const item
            of list
        ){

            const name =
                regionName(
                    item
                );


            const key =
                normalise(
                    name
                );


            if(
                !name ||
                seen.has(
                    key
                )
            ){

                continue;

            }


            seen.add(
                key
            );


            names.push(
                name
            );

        }


        names.sort(
            function(a,b){

                return a.localeCompare(
                    b,
                    undefined,
                    {
                        sensitivity:"base"
                    }
                );

            }
        );


        return names;

    }


    async function loadRegions(){

        try{

            const response =
                await fetch(
                    DATA_URL +
                    "?quicknav=2&nocache=" +
                    Date.now(),
                    {
                        credentials:
                            "same-origin",

                        cache:
                            "no-store"
                    }
                );


            const data =
                await response.json();


            regions =
                extractRegionNames(
                    data
                );


            rebuildJumpList();

        }
        catch(error){

            showStatus(
                "Region list unavailable."
            );

        }

    }


    /* ========================================================
       TILE LOOKUP
       ======================================================== */

    function tileName(
        tile
    ){

        if(!tile){

            return "";

        }


        const direct = [

            tile.dataset.regionName,
            tile.dataset.region,
            tile.dataset.name,

            tile.getAttribute(
                "data-region-name"
            ),

            tile.getAttribute(
                "data-region"
            )

        ];


        for(
            const value
            of direct
        ){

            if(
                value &&
                String(value).trim()
            ){

                return String(
                    value
                ).trim();

            }

        }


        const tileText =
            normalise(
                tile.innerText ||
                tile.textContent ||
                ""
            );


        const ordered =
            regions
                .slice()
                .sort(
                    function(a,b){

                        return (
                            b.length -
                            a.length
                        );

                    }
                );


        for(
            const name
            of ordered
        ){

            const wanted =
                normalise(
                    name
                );


            if(
                tileText === wanted ||
                tileText.startsWith(
                    wanted + " "
                )
            ){

                return name;

            }

        }


        return "";

    }


    function findTile(
        name
    ){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){

            return null;

        }


        const wanted =
            normalise(
                name
            );


        for(
            const tile
            of map.querySelectorAll(
                ".map-region"
            )
        ){

            if(
                normalise(
                    tileName(
                        tile
                    )
                ) === wanted
            ){

                return tile;

            }

        }


        return null;

    }


    /* ========================================================
       CURRENT SELECTION
       ======================================================== */

    function updateSelectedRegion(){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){

            return;

        }


        const tile =
            map.querySelector(
                ".map-region.gridmap-selected-region, .map-region.selected"
            );


        if(!tile){

            return;

        }


        const name =
            tileName(
                tile
            );


        if(
            name &&
            name !== selectedRegion
        ){

            selectedRegion =
                name;


            updateFavoriteButton();

        }

    }


    /* ========================================================
       JUMP
       ======================================================== */

    function jumpToRegion(
        name
    ){

        name =
            String(
                name || ""
            ).trim();


        if(!name){

            return;

        }


        /*
         * QUICK JUMP V5
         *
         * IMPORTANT:
         * centreRegion() requires the actual .map-region
         * DOM element - NOT the region name string.
         */

        const tile =
            findTile(
                name
            );


        if(!tile){

            showStatus(
                "Region could not be found."
            );

            return;

        }


        try{

            if(
                window.AustraliaGridMapView &&
                typeof
                    window.AustraliaGridMapView
                    .centreRegion ===
                    "function"
            ){

                window
                    .AustraliaGridMapView
                    .centreRegion(
                        tile
                    );

            }

        }
        catch(error){

            console.debug(
                "Quick Jump centre error:",
                error
            );


            showStatus(
                "Unable to centre region."
            );

            return;

        }


        /*
         * Use the EXISTING region click behaviour.
         *
         * This opens Region Details and applies the
         * normal selected/gold state.
         */

        setTimeout(
            function(){

                try{

                    tile.click();

                    selectedRegion =
                        name;

                    updateFavoriteButton();

                    showStatus(
                        "Centered on " +
                        name
                    );

                }
                catch(error){

                    console.debug(
                        "Quick Jump selection error:",
                        error
                    );

                }

            },
            100
        );

    }

/* ========================================================
       QUICK JUMP DROPDOWN
       ======================================================== */

    function addOption(
        group,
        name,
        prefix
    ){

        const option =
            document.createElement(
                "option"
            );


        option.value =
            name;


        option.textContent =
            (
                prefix || ""
            ) +
            name;


        group.appendChild(
            option
        );

    }


    function rebuildJumpList(){

        if(!jumpSelect){

            return;

        }


        const old =
            jumpSelect.value;


        jumpSelect.innerHTML =
            "";


        const first =
            document.createElement(
                "option"
            );


        first.value =
            "";


        first.textContent =
            "â˜… QUICK JUMP";


        jumpSelect.appendChild(
            first
        );


        const favoriteNames =
            regions.filter(
                function(name){

                    return isFavorite(
                        name
                    );

                }
            );


        if(
            favoriteNames.length
        ){

            const favoriteGroup =
                document.createElement(
                    "optgroup"
                );


            favoriteGroup.label =
                "â˜… FAVORITES";


            favoriteNames.forEach(
                function(name){

                    addOption(
                        favoriteGroup,
                        name,
                        "â˜… "
                    );

                }
            );


            jumpSelect.appendChild(
                favoriteGroup
            );

        }


        const allGroup =
            document.createElement(
                "optgroup"
            );


        allGroup.label =
            "ALL REGIONS";


        regions.forEach(
            function(name){

                addOption(
                    allGroup,
                    name,
                    ""
                );

            }
        );


        jumpSelect.appendChild(
            allGroup
        );


        if(
            old &&
            regions.some(
                function(name){

                    return (
                        normalise(name) ===
                        normalise(old)
                    );

                }
            )
        ){

            jumpSelect.value =
                old;

        }
        else{

            jumpSelect.value =
                "";

        }

    }


    /* ========================================================
       CENTER ON ME
       ======================================================== */

    async function centerOnMe(
        button
    ){

        const previous =
            button.textContent;


        button.disabled =
            true;


        button.textContent =
            "LOCATING...";


        try{

            const response =
                await fetch(
                    LOCATION_URL +
                    "?nocache=" +
                    Date.now(),
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
                !data ||
                data.ok === false
            ){

                throw new Error(
                    data &&
                    data.error
                        ? data.error
                        : "Location unavailable"
                );

            }


            const region =
                String(

                    data.region ||
                    data.RegionName ||
                    data.regionName ||
                    data.region_name ||
                    data.name ||
                    ""

                ).trim();


            if(!region){

                throw new Error(
                    "Your avatar is not currently on the grid."
                );

            }


            jumpToRegion(
                region
            );

        }
        catch(error){

            showStatus(
                error &&
                error.message
                    ? error.message
                    : "Unable to locate avatar."
            );

        }
        finally{

            button.disabled =
                false;


            button.textContent =
                previous;

        }

    }


    /* ========================================================
       FULLSCREEN MOVEMENT
       ======================================================== */

    function restoreNormalPosition(){

        if(
            nav &&
            normalHome &&
            nav.parentNode !== normalHome
        ){

            normalHome.appendChild(
                nav
            );

        }

    }


    function moveForFullscreen(){

        const viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        if(
            !viewport ||
            !nav
        ){

            return;

        }


        const isFullscreen =
            document.fullscreenElement ===
            viewport;


        if(!isFullscreen){

            restoreNormalPosition();

            return;

        }


        /*
         * Use the EXISTING fullscreen control dock.
         * Do not attach Quick Nav directly to the pan surface.
         */

        let attempts =
            0;


        const timer =
            setInterval(
                function(){

                    attempts++;


                    const dock =
                        document.getElementById(
                            "gridmap-fullscreen-dock"
                        );


                    if(dock){

                        clearInterval(
                            timer
                        );


                        dock.appendChild(
                            nav
                        );


                        return;

                    }


                    if(
                        attempts >= 15
                    ){

                        clearInterval(
                            timer
                        );

                    }

                },
                60
            );

    }


    /* ========================================================
       INSTALL
       ======================================================== */

    function installQuickNavV2(){

        const toolbar =
            document.querySelector(
                ".gridmap-toolbar-left"
            );


        const viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        const map =
            document.getElementById(
                "grid-map"
            );


        if(
            !toolbar ||
            !viewport ||
            !map
        ){

            return false;

        }


        if(
            document.getElementById(
                "gridmap-user-quicknav-v2"
            )
        ){

            return true;

        }


        normalHome =
            toolbar;


        loadFavorites();


        nav =
            document.createElement(
                "div"
            );


        nav.id =
            "gridmap-user-quicknav-v2";


        nav.innerHTML = `

            <button
                type="button"
                id="gridmap-user-center-me-v2"
                class="gridmap-user-nav-btn-v2"
                title="Centre on your current region">

                â—Ž CENTER ON ME

            </button>


            <button
                type="button"
                id="gridmap-user-favorite-v2"
                class="gridmap-user-nav-btn-v2"
                title="Favorite the selected region"
                disabled>

                â˜† FAVORITE

            </button>


            <select
                id="gridmap-user-jump-v2"
                title="Quick Jump to a region">

                <option value="">
                    â˜… QUICK JUMP
                </option>

            </select>


            <span
                id="gridmap-user-nav-status-v2">
            </span>

        `;


        /*
         * IMPORTANT:
         * Normal location is outside .gridmap-scroll.
         */

        toolbar.appendChild(
            nav
        );


        const centerButton =
            document.getElementById(
                "gridmap-user-center-me-v2"
            );


        favoriteButton =
            document.getElementById(
                "gridmap-user-favorite-v2"
            );


        jumpSelect =
            document.getElementById(
                "gridmap-user-jump-v2"
            );


        statusHolder =
            document.getElementById(
                "gridmap-user-nav-status-v2"
            );


        centerButton.addEventListener(
            "click",
            function(){

                centerOnMe(
                    centerButton
                );

            }
        );


        favoriteButton.addEventListener(
            "click",
            toggleFavorite
        );


        jumpSelect.addEventListener(
            "change",
            function(){

                const name =
                    jumpSelect.value;


                if(!name){

                    return;

                }


                /*
                 * Reset immediately so the same region can
                 * be selected again later.
                 */

                jumpSelect.value =
                    "";


                jumpToRegion(
                    name
                );

            }
        );


        /*
         * Fullscreen dock already handles map-safe controls,
         * but stop bubbling here as an extra guard.
         */

        [
            "pointerdown",
            "pointerup",
            "mousedown",
            "mouseup",
            "click",
            "touchstart",
            "touchend",
            "wheel"
        ]
        .forEach(
            function(eventName){

                nav.addEventListener(
                    eventName,
                    function(event){

                        event.stopPropagation();

                    }
                );

            }
        );


        /*
         * Watch the existing region selection state only.
         */

        selectionObserver =
            new MutationObserver(
                function(){

                    updateSelectedRegion();

                }
            );


        selectionObserver.observe(
            map,
            {
                subtree:true,
                attributes:true,
                attributeFilter:[
                    "class"
                ]
            }
        );


        map.addEventListener(
            "click",
            function(){

                setTimeout(
                    updateSelectedRegion,
                    70
                );

            }
        );


        document.addEventListener(
            "fullscreenchange",
            function(){

                setTimeout(
                    moveForFullscreen,
                    100
                );

            }
        );


        updateFavoriteButton();

        loadRegions();

        updateSelectedRegion();

        return true;

    }


    function installWhenReady(){

        let attempts =
            0;


        const timer =
            setInterval(
                function(){

                    attempts++;


                    if(
                        installQuickNavV2() ||
                        attempts >= 40
                    ){

                        clearInterval(
                            timer
                        );

                    }

                },
                250
            );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            installWhenReady
        );

    }
    else{

        installWhenReady();

    }

})();

/* END Grid MAP - USER QUICK NAV CLIENT V2 */

</script>


<script>

/* ============================================================
   AUSTRALIA USER MAP - AUTO CENTER REGIONS V1

   Centres the REAL region footprint when the USER map opens.

   Uses original OpenSim card geometry where available.

   Does NOT change:
       Region coordinates
       Islands
       Water
       Pan engine
       Wheel zoom
       Quick Jump
       Admin map
   ============================================================ */

(function(){

    let finished =
        false;


    function numberValue(
        value,
        fallback
    ){

        const number =
            Number(
                value
            );


        return Number.isFinite(
            number
        )
            ? number
            : fallback;

    }


    function originalGeometry(
        tile
    ){

        const left =
            numberValue(
                tile.dataset
                    .australiaOriginalLeft,
                tile.offsetLeft
            );


        const top =
            numberValue(
                tile.dataset
                    .australiaOriginalTop,
                tile.offsetTop
            );


        const width =
            Math.max(
                1,
                numberValue(
                    tile.dataset
                        .australiaOriginalWidth,
                    tile.offsetWidth
                )
            );


        const height =
            Math.max(
                1,
                numberValue(
                    tile.dataset
                        .australiaOriginalHeight,
                    tile.offsetHeight
                )
            );


        return {
            left:left,
            top:top,
            width:width,
            height:height
        };

    }


    function centerRegions(){

        if(finished){

            return true;

        }


        const viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        const map =
            document.getElementById(
                "grid-map"
            );


        if(
            !viewport ||
            !map ||
            !window.AustraliaGridMapView ||
            typeof
                window.AustraliaGridMapView
                .setView !==
                "function"
        ){

            return false;

        }


        const tiles =
            Array.from(
                map.querySelectorAll(
                    ".map-region"
                )
            );


        if(!tiles.length){

            return false;

        }


        let minLeft =
            Infinity;


        let minTop =
            Infinity;


        let maxRight =
            -Infinity;


        let maxBottom =
            -Infinity;


        for(
            const tile
            of tiles
        ){

            const g =
                originalGeometry(
                    tile
                );


            minLeft =
                Math.min(
                    minLeft,
                    g.left
                );


            minTop =
                Math.min(
                    minTop,
                    g.top
                );


            maxRight =
                Math.max(
                    maxRight,
                    g.left +
                    g.width
                );


            maxBottom =
                Math.max(
                    maxBottom,
                    g.top +
                    g.height
                );

        }


        if(
            !Number.isFinite(minLeft) ||
            !Number.isFinite(minTop) ||
            !Number.isFinite(maxRight) ||
            !Number.isFinite(maxBottom)
        ){

            return false;

        }


        /*
         * Extra world-space margin leaves room for the
         * island artwork around the region cards.
         */

        const worldPaddingX =
            150;


        const worldPaddingY =
            110;


        minLeft -=
            worldPaddingX;


        maxRight +=
            worldPaddingX;


        minTop -=
            worldPaddingY;


        maxBottom +=
            worldPaddingY;


        const boundsWidth =
            Math.max(
                1,
                maxRight -
                minLeft
            );


        const boundsHeight =
            Math.max(
                1,
                maxBottom -
                minTop
            );


        /*
         * Keep a screen-space border around the full region group.
         */

        const usableWidth =
            Math.max(
                200,
                viewport.clientWidth -
                120
            );


        const usableHeight =
            Math.max(
                200,
                viewport.clientHeight -
                90
            );


        /*
         * Match the Firestorm map engine's normal minimum.
         *
         * This should naturally settle around 12% on the
         * current long north/south Grid layout.
         */

        let zoom =
            Math.min(
                usableWidth /
                    boundsWidth,

                usableHeight /
                    boundsHeight
            );


        zoom =
            Math.max(
                0.12,
                Math.min(
                    1.00,
                    zoom
                )
            );


        const centreX =
            (
                minLeft +
                maxRight
            ) /
            2;


        const centreY =
            (
                minTop +
                maxBottom
            ) /
            2;


        const panX =
            viewport.clientWidth /
            2 -
            centreX *
            zoom;


        const panY =
            viewport.clientHeight /
            2 -
            centreY *
            zoom;


        const applied =
            window
                .AustraliaGridMapView
                .setView(
                    zoom,
                    panX,
                    panY
                );


        if(!applied){

            return false;

        }


        finished =
            true;


        return true;

    }


    function start(){

        let attempts =
            0;


        const timer =
            setInterval(
                function(){

                    attempts++;


                    /*
                     * Give the normal renderer and small-region
                     * geometry correction time to finish first.
                     */

                    if(
                        attempts >= 3 &&
                        centerRegions()
                    ){

                        clearInterval(
                            timer
                        );

                        return;

                    }


                    if(
                        attempts >= 40
                    ){

                        clearInterval(
                            timer
                        );

                    }

                },
                250
            );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            start
        );

    }
    else{

        start();

    }

})();

/* END AUSTRALIA USER MAP - AUTO CENTER REGIONS V1 */

</script>








<script>

/* ============================================================
   AUSTRALIA USER MAP - REGION DETAIL ACTION BAR CLIENT V1

   Adds to the existing Region Details action row:

       CENTER MAP
       FAVORITE
       existing HOP TO REGION

   ============================================================ */

(function(){

    let centerButton =
        null;


    let favoriteButton =
        null;


    let topFavoriteButton =
        null;


    let installed =
        false;


    /* ========================================================
       SELECTED REGION TILE
       ======================================================== */

    function selectedTile(){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){

            return null;

        }


        return (

            map.querySelector(
                ".map-region.gridmap-selected-region"
            ) ||

            map.querySelector(
                ".map-region.selected"
            )

        );

    }


    function selectedRegionName(){

        const tile =
            selectedTile();


        if(!tile){

            return "";

        }


        return String(

            tile.dataset.region ||
            tile.dataset.regionName ||
            tile.dataset.name ||
            tile.getAttribute(
                "data-region"
            ) ||
            tile.getAttribute(
                "data-region-name"
            ) ||
            ""

        ).trim();

    }


    /* ========================================================
       CENTER SELECTED REGION
       ======================================================== */

    function centerSelectedRegion(){

        const tile =
            selectedTile();


        if(!tile){

            return;

        }


        try{

            if(
                window.AustraliaGridMapView &&
                typeof
                    window.AustraliaGridMapView
                    .centreRegion ===
                    "function"
            ){

                /*
                 * IMPORTANT:
                 * Pass the REAL REGION TILE element.
                 *
                 * This is the same method used by the fixed
                 * Quick Jump system.
                 */

                window
                    .AustraliaGridMapView
                    .centreRegion(
                        tile
                    );

            }

        }
        catch(error){

            console.debug(
                "Region Details CENTER MAP:",
                error
            );

        }

    }


    /* ========================================================
       FAVORITE STATE
       ======================================================== */

    function syncFavoriteState(){

        if(!favoriteButton){

            return;

        }


        const regionName =
            selectedRegionName();


        topFavoriteButton =
            document.getElementById(
                "gridmap-user-favorite-v2"
            );


        if(
            !regionName ||
            !topFavoriteButton
        ){

            favoriteButton.disabled =
                true;


            favoriteButton.textContent =
                "â˜† FAVORITE";


            favoriteButton.classList.remove(
                "is-favorite"
            );


            return;

        }


        favoriteButton.disabled =
            false;


        const isFavorite =
            topFavoriteButton
                .classList
                .contains(
                    "is-favorite"
                ) ||

            String(
                topFavoriteButton.textContent ||
                ""
            )
            .toUpperCase()
            .includes(
                "FAVORITED"
            );


        if(isFavorite){

            favoriteButton.textContent =
                "â˜… FAVORITED";


            favoriteButton.classList.add(
                "is-favorite"
            );

        }
        else{

            favoriteButton.textContent =
                "â˜† FAVORITE";


            favoriteButton.classList.remove(
                "is-favorite"
            );

        }

    }


    /* ========================================================
       FAVORITE SELECTED REGION
       ======================================================== */

    function toggleFavorite(){

        const tile =
            selectedTile();


        if(!tile){

            return;

        }


        /*
         * Use the EXISTING working Favorites button.
         *
         * That means:
         * - same localStorage
         * - same avatar-specific Favorites
         * - same Quick Jump Favorites list
         * - no second Favorite database
         */

        topFavoriteButton =
            document.getElementById(
                "gridmap-user-favorite-v2"
            );


        if(
            topFavoriteButton &&
            !topFavoriteButton.disabled
        ){

            topFavoriteButton.click();


            setTimeout(
                syncFavoriteState,
                80
            );


            setTimeout(
                syncFavoriteState,
                220
            );

            return;

        }


        /*
         * If selection state has not reached the top toolbar
         * yet, refresh selection once and try again.
         */

        try{

            tile.click();

        }
        catch(error){
        }


        setTimeout(
            function(){

                topFavoriteButton =
                    document.getElementById(
                        "gridmap-user-favorite-v2"
                    );


                if(
                    topFavoriteButton &&
                    !topFavoriteButton.disabled
                ){

                    topFavoriteButton.click();

                }


                setTimeout(
                    syncFavoriteState,
                    100
                );

            },
            120
        );

    }


    /* ========================================================
       BUTTON STATES
       ======================================================== */

    function updateButtons(){

        const tile =
            selectedTile();


        if(centerButton){

            centerButton.disabled =
                !tile;

        }


        syncFavoriteState();

    }


    /* ========================================================
       INSTALL INTO EXISTING HOP ROW
       ======================================================== */

    function installActionButtons(){

        const actions =
            document.querySelector(
                ".gridmap-hop-actions"
            );


        const hopButton =
            document.getElementById(
                "gridmap-hop-button"
            );


        if(
            !actions ||
            !hopButton
        ){

            return false;

        }


        if(
            document.getElementById(
                "australia-user-detail-center-v1"
            )
        ){

            centerButton =
                document.getElementById(
                    "australia-user-detail-center-v1"
                );


            favoriteButton =
                document.getElementById(
                    "australia-user-detail-favorite-v1"
                );


            updateButtons();

            installed =
                true;


            return true;

        }


        centerButton =
            document.createElement(
                "button"
            );


        centerButton.type =
            "button";


        centerButton.id =
            "australia-user-detail-center-v1";


        centerButton.textContent =
            "â—Ž CENTER MAP";


        centerButton.disabled =
            true;


        favoriteButton =
            document.createElement(
                "button"
            );


        favoriteButton.type =
            "button";


        favoriteButton.id =
            "australia-user-detail-favorite-v1";


        favoriteButton.textContent =
            "â˜† FAVORITE";


        favoriteButton.disabled =
            true;


        /*
         * Insert BEFORE existing HOP button:
         *
         * CENTER MAP | FAVORITE | HOP TO REGION
         */

        actions.insertBefore(
            favoriteButton,
            hopButton
        );


        actions.insertBefore(
            centerButton,
            favoriteButton
        );


        centerButton.addEventListener(
            "click",
            function(event){

                event.preventDefault();

                centerSelectedRegion();

            }
        );


        favoriteButton.addEventListener(
            "click",
            function(event){

                event.preventDefault();

                toggleFavorite();

            }
        );


        installed =
            true;


        updateButtons();

        return true;

    }


    /* ========================================================
       WATCH REGION SELECTION
       ======================================================== */

    function watchSelection(){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){

            return;

        }


        map.addEventListener(
            "click",
            function(){

                setTimeout(
                    updateButtons,
                    80
                );


                setTimeout(
                    updateButtons,
                    220
                );

            }
        );


        const observer =
            new MutationObserver(
                function(){

                    updateButtons();

                }
            );


        observer.observe(
            map,
            {
                subtree:true,
                attributes:true,
                attributeFilter:[
                    "class"
                ]
            }
        );

    }


    /* ========================================================
       WATCH EXISTING FAVORITE BUTTON
       ======================================================== */

    function watchFavoriteButton(){

        let attempts =
            0;


        const timer =
            setInterval(
                function(){

                    attempts++;


                    topFavoriteButton =
                        document.getElementById(
                            "gridmap-user-favorite-v2"
                        );


                    if(topFavoriteButton){

                        clearInterval(
                            timer
                        );


                        const observer =
                            new MutationObserver(
                                function(){

                                    syncFavoriteState();

                                }
                            );


                        observer.observe(
                            topFavoriteButton,
                            {
                                attributes:true,
                                childList:true,
                                subtree:true
                            }
                        );


                        syncFavoriteState();

                    }


                    if(
                        attempts >= 60
                    ){

                        clearInterval(
                            timer
                        );

                    }

                },
                250
            );

    }


    /* ========================================================
       START
       ======================================================== */

    function start(){

        let attempts =
            0;


        const timer =
            setInterval(
                function(){

                    attempts++;


                    if(
                        installActionButtons()
                    ){

                        clearInterval(
                            timer
                        );

                    }


                    if(
                        attempts >= 80
                    ){

                        clearInterval(
                            timer
                        );

                    }

                },
                250
            );


        /*
         * Existing HOP system may rebuild its row.
         * If that happens, automatically restore our buttons.
         */

        const details =
            document.getElementById(
                "gridmap-details-body"
            );


        if(details){

            const observer =
                new MutationObserver(
                    function(){

                        if(
                            !document.getElementById(
                                "australia-user-detail-center-v1"
                            )
                        ){

                            setTimeout(
                                installActionButtons,
                                20
                            );

                        }

                    }
                );


            observer.observe(
                details,
                {
                    childList:true,
                    subtree:true
                }
            );

        }


        watchSelection();

        watchFavoriteButton();

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            start
        );

    }
    else{

        start();

    }

})();

/* END AUSTRALIA USER MAP - REGION DETAIL ACTION BAR CLIENT V1 */

</script>


<script>

/* ============================================================
   AUSTRALIA USER MAP - RECENT REGIONS V1

   Adds RECENT REGIONS to the existing QUICK JUMP dropdown.

   Stores the last 8 MAP selections for the signed-in avatar.

   Does NOT:
       change Quick Jump
       change Favorites
       change map pan
       change map zoom
       change water
       call RemoteAdmin
       create a server database
   ============================================================ */

(function(){

    const MAX_RECENT =
        8;


    const RECENT_GROUP_ID =
        "australia-user-recent-regions-v1";


    let jumpSelect =
        null;


    let selectObserver =
        null;


    let rebuilding =
        false;


    /* ========================================================
       AVATAR
       ======================================================== */

    function avatarName(){

        try{

            if(
                window.DG &&
                window.DG.avatar
            ){

                return String(
                    window.DG.avatar
                ).trim();

            }

        }
        catch(error){
        }


        try{

            if(
                window.DG_GRID_MAP &&
                window.DG_GRID_MAP.avatar
            ){

                return String(
                    window.DG_GRID_MAP.avatar
                ).trim();

            }

        }
        catch(error){
        }


        return "AustraliaGridUser";

    }


    function normalise(
        value
    ){

        return String(
            value || ""
        )
        .trim()
        .replace(
            /\s+/g,
            " "
        )
        .toLowerCase();

    }


    function storageKey(){

        return (
            "AustraliaGridUserRecentRegionsV1::" +
            normalise(
                avatarName()
            )
        );

    }


    /* ========================================================
       STORAGE
       ======================================================== */

    function loadRecent(){

        try{

            const raw =
                localStorage.getItem(
                    storageKey()
                );


            if(!raw){

                return [];
            }


            const list =
                JSON.parse(
                    raw
                );


            if(
                !Array.isArray(
                    list
                )
            ){

                return [];
            }


            return list
                .map(
                    value =>
                        String(
                            value || ""
                        ).trim()
                )
                .filter(Boolean)
                .slice(
                    0,
                    MAX_RECENT
                );

        }
        catch(error){

            return [];

        }

    }


    function saveRecent(
        list
    ){

        try{

            localStorage.setItem(
                storageKey(),
                JSON.stringify(
                    list.slice(
                        0,
                        MAX_RECENT
                    )
                )
            );

        }
        catch(error){
        }

    }


    /* ========================================================
       ADD RECENT REGION
       ======================================================== */

    function rememberRegion(
        name
    ){

        name =
            String(
                name || ""
            ).trim();


        if(!name){

            return;

        }


        const wanted =
            normalise(
                name
            );


        let list =
            loadRecent()
                .filter(
                    item =>
                        normalise(
                            item
                        ) !== wanted
                );


        list.unshift(
            name
        );


        if(
            list.length >
            MAX_RECENT
        ){

            list =
                list.slice(
                    0,
                    MAX_RECENT
                );

        }


        saveRecent(
            list
        );


        rebuildRecentGroup();

    }


    /* ========================================================
       REGION NAME FROM TILE
       ======================================================== */

    function tileRegionName(
        tile
    ){

        if(!tile){

            return "";
        }


        const direct = [

            tile.dataset.region,
            tile.dataset.regionName,
            tile.dataset.name,

            tile.getAttribute(
                "data-region"
            ),

            tile.getAttribute(
                "data-region-name"
            )

        ];


        for(
            const value
            of direct
        ){

            if(
                value &&
                String(value).trim()
            ){

                return String(
                    value
                ).trim();

            }

        }


        return "";

    }


    /* ========================================================
       VALID REGION NAMES FROM CURRENT QUICK JUMP
       ======================================================== */

    function availableRegions(){

        const output =
            new Map();


        if(!jumpSelect){

            return output;

        }


        jumpSelect
            .querySelectorAll(
                "option[value]"
            )
            .forEach(
                function(option){

                    const value =
                        String(
                            option.value ||
                            ""
                        ).trim();


                    if(!value){

                        return;

                    }


                    /*
                     * Ignore our own RECENT options when
                     * constructing the master region list.
                     */

                    if(
                        option.parentElement &&
                        option.parentElement.id ===
                        RECENT_GROUP_ID
                    ){

                        return;

                    }


                    const key =
                        normalise(
                            value
                        );


                    if(
                        !output.has(
                            key
                        )
                    ){

                        output.set(
                            key,
                            value
                        );

                    }

                }
            );


        return output;

    }


    /* ========================================================
       RECENT OPTGROUP
       ======================================================== */

    function rebuildRecentGroup(){

        if(
            !jumpSelect ||
            rebuilding
        ){

            return;

        }


        rebuilding =
            true;


        if(selectObserver){

            selectObserver.disconnect();

        }


        try{

            const old =
                document.getElementById(
                    RECENT_GROUP_ID
                );


            if(old){

                old.remove();

            }


            const available =
                availableRegions();


            let recent =
                loadRecent();


            /*
             * Remove regions that no longer exist.
             */

            recent =
                recent
                    .map(
                        function(name){

                            const realName =
                                available.get(
                                    normalise(
                                        name
                                    )
                                );


                            return realName || "";

                        }
                    )
                    .filter(Boolean);


            /*
             * Remove duplicates while preserving order.
             */

            const seen =
                new Set();


            recent =
                recent.filter(
                    function(name){

                        const key =
                            normalise(
                                name
                            );


                        if(
                            seen.has(
                                key
                            )
                        ){

                            return false;

                        }


                        seen.add(
                            key
                        );


                        return true;

                    }
                )
                .slice(
                    0,
                    MAX_RECENT
                );


            saveRecent(
                recent
            );


            if(
                recent.length
            ){

                const group =
                    document.createElement(
                        "optgroup"
                    );


                group.id =
                    RECENT_GROUP_ID;


                group.label =
                    "â†¶ RECENT REGIONS";


                for(
                    const name
                    of recent
                ){

                    const option =
                        document.createElement(
                            "option"
                        );


                    option.value =
                        name;


                    option.textContent =
                        "â†¶ " +
                        name;


                    group.appendChild(
                        option
                    );

                }


                /*
                 * Insert immediately after the normal
                 * â˜… QUICK JUMP placeholder.
                 */

                const firstChild =
                    jumpSelect.children[0];


                if(
                    firstChild &&
                    firstChild.nextSibling
                ){

                    jumpSelect.insertBefore(
                        group,
                        firstChild.nextSibling
                    );

                }
                else{

                    jumpSelect.appendChild(
                        group
                    );

                }

            }

        }
        catch(error){

            console.debug(
                "Recent Regions rebuild:",
                error
            );

        }
        finally{

            rebuilding =
                false;


            if(selectObserver){

                selectObserver.observe(
                    jumpSelect,
                    {
                        childList:true,
                        subtree:true
                    }
                );

            }

        }

    }


    /* ========================================================
       WATCH REGION CLICKS
       ======================================================== */

    function watchMap(){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){

            return false;

        }


        map.addEventListener(
            "click",
            function(event){

                const tile =
                    event.target.closest(
                        ".map-region"
                    );


                if(!tile){

                    return;

                }


                const name =
                    tileRegionName(
                        tile
                    );


                if(name){

                    /*
                     * Delay slightly so normal Region Details,
                     * Favorite state and selection finish first.
                     */

                    setTimeout(
                        function(){

                            rememberRegion(
                                name
                            );

                        },
                        80
                    );

                }

            }
        );


        return true;

    }


    /* ========================================================
       WATCH QUICK JUMP REBUILDS
       ======================================================== */

    function watchJumpDropdown(){

        jumpSelect =
            document.getElementById(
                "gridmap-user-jump-v2"
            );


        if(!jumpSelect){

            return false;

        }


        selectObserver =
            new MutationObserver(
                function(){

                    if(rebuilding){

                        return;

                    }


                    setTimeout(
                        rebuildRecentGroup,
                        20
                    );

                }
            );


        selectObserver.observe(
            jumpSelect,
            {
                childList:true,
                subtree:true
            }
        );


        rebuildRecentGroup();


        return true;

    }


    /* ========================================================
       START
       ======================================================== */

    function start(){

        let attempts =
            0;


        let mapReady =
            false;


        let jumpReady =
            false;


        const timer =
            setInterval(
                function(){

                    attempts++;


                    if(!mapReady){

                        mapReady =
                            watchMap();

                    }


                    if(!jumpReady){

                        jumpReady =
                            watchJumpDropdown();

                    }


                    if(
                        mapReady &&
                        jumpReady
                    ){

                        clearInterval(
                            timer
                        );

                    }


                    if(
                        attempts >= 80
                    ){

                        clearInterval(
                            timer
                        );

                    }

                },
                250
            );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            start
        );

    }
    else{

        start();

    }

})();

/* END AUSTRALIA USER MAP - RECENT REGIONS V1 */

</script>


<script>

/* ============================================================
   AUSTRALIA USER MAP - DISPLAY CONTROLS CLIENT V1

   USER SAFE VISUAL LAYERS ONLY:

       REGION NAMES
       ISLANDS
       COORDINATES
       COMPASS
       YOU ARE HERE

   ============================================================ */

(function(){

    const DEFAULTS = {

        names:true,
        islands:true,
        coordinates:true,
        compass:true,
        you:true

    };


    let settings =
        Object.assign(
            {},
            DEFAULTS
        );


    let host =
        null;


    let panel =
        null;


    let button =
        null;


    let normalHome =
        null;


    /* ========================================================
       AVATAR-SPECIFIC STORAGE
       ======================================================== */

    function normalise(
        value
    ){

        return String(
            value || ""
        )
        .trim()
        .replace(
            /\s+/g,
            " "
        )
        .toLowerCase();

    }


    function avatarName(){

        try{

            if(
                window.DG &&
                window.DG.avatar
            ){

                return String(
                    window.DG.avatar
                ).trim();

            }

        }
        catch(error){
        }


        try{

            if(
                window.DG_GRID_MAP &&
                window.DG_GRID_MAP.avatar
            ){

                return String(
                    window.DG_GRID_MAP.avatar
                ).trim();

            }

        }
        catch(error){
        }


        return "AustraliaGridUser";

    }


    function storageKey(){

        return (
            "AustraliaUserMapDisplayV1::" +
            normalise(
                avatarName()
            )
        );

    }


    function loadSettings(){

        try{

            const raw =
                localStorage.getItem(
                    storageKey()
                );


            if(!raw){

                return;
            }


            const saved =
                JSON.parse(
                    raw
                );


            if(
                saved &&
                typeof saved ===
                "object"
            ){

                for(
                    const key
                    of Object.keys(
                        DEFAULTS
                    )
                ){

                    if(
                        typeof saved[key] ===
                        "boolean"
                    ){

                        settings[key] =
                            saved[key];

                    }

                }

            }

        }
        catch(error){
        }

    }


    function saveSettings(){

        try{

            localStorage.setItem(
                storageKey(),
                JSON.stringify(
                    settings
                )
            );

        }
        catch(error){
        }

    }


    /* ========================================================
       DISCOVER SAFE EXISTING VISUALS
       ======================================================== */

    function markIslandLayers(){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){

            return;
        }


        map
            .querySelectorAll(
                "*"
            )
            .forEach(
                function(el){

                    if(
                        el.classList.contains(
                            "map-region"
                        ) ||
                        el.closest(
                            ".map-region"
                        )
                    ){

                        return;

                    }


                    const signature = (

                        String(
                            el.id || ""
                        ) +

                        " " +

                        String(
                            el.className || ""
                        ) +

                        " " +

                        String(
                            el.getAttribute("src") || ""
                        ) +

                        " " +

                        String(
                            el.style.backgroundImage || ""
                        )

                    ).toLowerCase();


                    if(
                        signature.includes(
                            "island"
                        )
                    ){

                        el.classList.add(
                            "australia-user-island-layer-v1"
                        );

                    }

                }
            );

    }


    function markCompass(){

        const viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        if(!viewport){

            return;
        }


        /*
         * Prefer known/likely compass IDs or classes.
         */

        const candidates =
            Array.from(
                viewport.querySelectorAll(
                    "*"
                )
            );


        for(
            const el
            of candidates
        ){

            const signature = (

                String(
                    el.id || ""
                ) +

                " " +

                String(
                    el.className || ""
                )

            ).toLowerCase();


            if(
                signature.includes(
                    "compass"
                )
            ){

                el.classList.add(
                    "australia-user-compass-layer-v1"
                );

                continue;

            }


            const text =
                String(
                    el.textContent || ""
                )
                .replace(
                    /\s+/g,
                    " "
                )
                .trim()
                .toUpperCase();


            if(
                text ===
                "GRID NORTH" ||
                text.includes(
                    "GRID NORTH"
                )
            ){

                let target =
                    el;


                for(
                    let i = 0;
                    i < 4 &&
                    target.parentElement &&
                    target.parentElement !==
                    viewport;
                    i++
                ){

                    const rect =
                        target.getBoundingClientRect();


                    if(
                        rect.width >= 70 &&
                        rect.width <= 180 &&
                        rect.height >= 70 &&
                        rect.height <= 180
                    ){

                        break;

                    }


                    target =
                        target.parentElement;

                }


                target.classList.add(
                    "australia-user-compass-layer-v1"
                );

            }

        }

    }


    function markYouAreHere(){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){

            return;
        }


        map
            .querySelectorAll(
                "*"
            )
            .forEach(
                function(el){

                    const signature = (

                        String(
                            el.id || ""
                        ) +

                        " " +

                        String(
                            el.className || ""
                        )

                    ).toLowerCase();


                    const text =
                        String(
                            el.textContent || ""
                        )
                        .replace(
                            /\s+/g,
                            " "
                        )
                        .trim()
                        .toUpperCase();


                    if(
                        signature.includes(
                            "you-are-here"
                        ) ||
                        signature.includes(
                            "youarehere"
                        ) ||
                        text ===
                        "YOU ARE HERE" ||
                        text.startsWith(
                            "YOU ARE HERE "
                        )
                    ){

                        let target =
                            el;


                        for(
                            let i = 0;
                            i < 4 &&
                            target.parentElement &&
                            target.parentElement !==
                            map;
                            i++
                        ){

                            const parentText =
                                String(
                                    target.parentElement.textContent ||
                                    ""
                                ).toUpperCase();


                            if(
                                parentText.includes(
                                    "YOU ARE HERE"
                                )
                            ){

                                target =
                                    target.parentElement;

                            }
                            else{

                                break;

                            }

                        }


                        target.classList.add(
                            "australia-user-you-layer-v1"
                        );

                    }

                }
            );

    }


    function markCoordinateLayers(){

        const viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        const map =
            document.getElementById(
                "grid-map"
            );


        if(
            !viewport ||
            !map
        ){

            return;
        }


        viewport
            .querySelectorAll(
                "*"
            )
            .forEach(
                function(el){

                    if(
                        el === map ||
                        map.contains(el) ||
                        el.closest(
                            "#australia-user-display-v1"
                        )
                    ){

                        return;

                    }


                    const signature = (

                        String(
                            el.id || ""
                        ) +

                        " " +

                        String(
                            el.className || ""
                        )

                    ).toLowerCase();


                    if(
                        signature.includes(
                            "coordinate"
                        ) ||
                        signature.includes(
                            "coord-"
                        ) ||
                        signature.includes(
                            "coord_"
                        ) ||
                        signature.includes(
                            "axis"
                        )
                    ){

                        el.classList.add(
                            "australia-user-coordinate-layer-v1"
                        );

                    }

                }
            );


        /*
         * Some coordinate-scale implementations use simple
         * absolutely-positioned numeric labels with generic
         * class names. Mark only small, pointer-free numeric
         * overlays sitting directly on the map viewport.
         */

        viewport
            .querySelectorAll(
                ":scope > div, :scope > span"
            )
            .forEach(
                function(el){

                    if(
                        el === map ||
                        el.id ===
                        "gridmap-fullscreen-dock" ||
                        el.id ===
                        "gridmap-compass" ||
                        el.id ===
                        "australia-user-display-v1"
                    ){

                        return;

                    }


                    const style =
                        getComputedStyle(
                            el
                        );


                    const text =
                        String(
                            el.textContent || ""
                        ).trim();


                    if(
                        (
                            style.position ===
                            "absolute" ||
                            style.position ===
                            "fixed"
                        ) &&
                        style.pointerEvents ===
                        "none" &&
                        /^[-+]?\d+(?:\s+[-+]?\d+)*$/.test(
                            text
                        )
                    ){

                        el.classList.add(
                            "australia-user-coordinate-layer-v1"
                        );

                    }

                }
            );

    }


    function discoverLayers(){

        markIslandLayers();

        markCompass();

        markYouAreHere();

        markCoordinateLayers();

    }


    /* ========================================================
       APPLY SETTINGS
       ======================================================== */

    function setBodyClass(
        className,
        enabled
    ){

        document.body
            .classList
            .toggle(
                className,
                !enabled
            );

    }


    function applySettings(){

        setBodyClass(
            "australia-user-hide-region-names-v1",
            settings.names
        );


        setBodyClass(
            "australia-user-hide-islands-v1",
            settings.islands
        );


        setBodyClass(
            "australia-user-hide-coordinates-v1",
            settings.coordinates
        );


        setBodyClass(
            "australia-user-hide-compass-v1",
            settings.compass
        );


        setBodyClass(
            "australia-user-hide-you-v1",
            settings.you
        );


        updateRows();

    }


    /* ========================================================
       PANEL
       ======================================================== */

    function rowMarkup(
        key,
        label
    ){

        return `

            <div
                class="australia-user-display-row-v1"
                data-display-key="${key}">

                <span>
                    ${label}
                </span>

                <span
                    class="australia-user-display-switch-v1">
                </span>

            </div>

        `;

    }


    function updateRows(){

        if(!panel){

            return;
        }


        panel
            .querySelectorAll(
                "[data-display-key]"
            )
            .forEach(
                function(row){

                    const key =
                        row.getAttribute(
                            "data-display-key"
                        );


                    row.classList.toggle(
                        "on",
                        !!settings[key]
                    );

                }
            );

    }


    function toggle(
        key
    ){

        if(
            !Object.prototype.hasOwnProperty.call(
                settings,
                key
            )
        ){

            return;
        }


        settings[key] =
            !settings[key];


        saveSettings();

        discoverLayers();

        applySettings();

    }


    function closePanel(){

        if(!panel){

            return;
        }


        panel.classList.remove(
            "open"
        );


        if(button){

            button.classList.remove(
                "active"
            );

        }

    }


    function togglePanel(){

        if(!panel){

            return;
        }


        const open =
            !panel.classList.contains(
                "open"
            );


        panel.classList.toggle(
            "open",
            open
        );


        button.classList.toggle(
            "active",
            open
        );


        if(open){

            discoverLayers();

            applySettings();

        }

    }


    /* ========================================================
       FULLSCREEN
       ======================================================== */

    function restoreNormal(){

        if(
            host &&
            normalHome &&
            host.parentElement !==
            normalHome
        ){

            normalHome.appendChild(
                host
            );

        }

    }


    function fullscreenChanged(){

        const viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        if(
            !viewport ||
            !host
        ){

            return;
        }


        if(
            document.fullscreenElement !==
            viewport
        ){

            restoreNormal();

            closePanel();

            return;

        }


        let attempts =
            0;


        const timer =
            setInterval(
                function(){

                    attempts++;


                    const dock =
                        document.getElementById(
                            "gridmap-fullscreen-dock"
                        );


                    if(dock){

                        clearInterval(
                            timer
                        );


                        dock.appendChild(
                            host
                        );


                        return;

                    }


                    if(
                        attempts >= 20
                    ){

                        clearInterval(
                            timer
                        );

                    }

                },
                60
            );

    }


    /* ========================================================
       INSTALL
       ======================================================== */

    function install(){

        const toolbar =
            document.querySelector(
                ".gridmap-toolbar-left"
            );


        const viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        if(
            !toolbar ||
            !viewport
        ){

            return false;
        }


        if(
            document.getElementById(
                "australia-user-display-v1"
            )
        ){

            return true;
        }


        normalHome =
            toolbar;


        loadSettings();


        host =
            document.createElement(
                "div"
            );


        host.id =
            "australia-user-display-v1";


        host.innerHTML = `

            <button
                type="button"
                id="australia-user-display-button-v1">

                â—« DISPLAY

            </button>


            <div
                id="australia-user-display-panel-v1">

                <div
                    class="australia-user-display-title-v1">

                    MAP DISPLAY

                </div>


                ${rowMarkup(
                    "names",
                    "REGION NAMES"
                )}


                ${rowMarkup(
                    "islands",
                    "ISLANDS"
                )}


                ${rowMarkup(
                    "coordinates",
                    "COORDINATES"
                )}


                ${rowMarkup(
                    "compass",
                    "COMPASS"
                )}


                ${rowMarkup(
                    "you",
                    "YOU ARE HERE"
                )}

            </div>

        `;


        toolbar.appendChild(
            host
        );


        panel =
            document.getElementById(
                "australia-user-display-panel-v1"
            );


        button =
            document.getElementById(
                "australia-user-display-button-v1"
            );


        button.addEventListener(
            "click",
            function(event){

                event.preventDefault();

                event.stopPropagation();

                togglePanel();

            }
        );


        panel
            .querySelectorAll(
                "[data-display-key]"
            )
            .forEach(
                function(row){

                    row.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();

                            event.stopPropagation();


                            toggle(
                                row.getAttribute(
                                    "data-display-key"
                                )
                            );

                        }
                    );

                }
            );


        /*
         * Keep DISPLAY UI completely out of map
         * pointer/pan handling.
         */

        [
            "pointerdown",
            "pointerup",
            "mousedown",
            "mouseup",
            "touchstart",
            "touchend",
            "wheel"
        ]
        .forEach(
            function(eventName){

                host.addEventListener(
                    eventName,
                    function(event){

                        event.stopPropagation();

                    }
                );

            }
        );


        document.addEventListener(
            "pointerdown",
            function(event){

                if(
                    panel &&
                    panel.classList.contains(
                        "open"
                    ) &&
                    !host.contains(
                        event.target
                    )
                ){

                    closePanel();

                }

            }
        );


        document.addEventListener(
            "keydown",
            function(event){

                if(
                    event.key ===
                    "Escape"
                ){

                    closePanel();

                }

            }
        );


        document.addEventListener(
            "fullscreenchange",
            function(){

                setTimeout(
                    fullscreenChanged,
                    100
                );

            }
        );


        /*
         * New map elements may be rebuilt after refresh or
         * region changes. Re-discover only visual layers.
         */

        const map =
            document.getElementById(
                "grid-map"
            );


        if(map){

            const observer =
                new MutationObserver(
                    function(){

                        discoverLayers();

                        applySettings();

                    }
                );


            observer.observe(
                map,
                {
                    childList:true,
                    subtree:true
                }
            );

        }


        discoverLayers();

        applySettings();


        return true;

    }


    function start(){

        let attempts =
            0;


        const timer =
            setInterval(
                function(){

                    attempts++;


                    if(
                        install()
                    ){

                        clearInterval(
                            timer
                        );

                    }


                    if(
                        attempts >= 80
                    ){

                        clearInterval(
                            timer
                        );

                    }

                },
                250
            );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            start
        );

    }
    else{

        start();

    }

})();

/* END AUSTRALIA USER MAP - DISPLAY CONTROLS CLIENT V1 */

</script>








<style>

/* ============================================================
   AUSTRALIA USER MAP - ALL REGION COMPOSITES V1.1

   Builds complete OpenSim map images from individual
   256x256 map cells.

   Existing region cards stay exactly where they are.
   ============================================================ */


#grid-map
.map-region.australia-region-composite-host-v11{

    isolation:isolate;

}


/* COMPLETE REGION IMAGE */

#grid-map
.australia-region-composite-v11{

    position:absolute;

    left:50%;
    top:50%;

    transform:
        translate(-50%,-50%);

    display:grid;

    overflow:hidden;

    z-index:0;

    pointer-events:none;

    user-select:none;

    background:#03080c;

    opacity:.88;

}


/* INDIVIDUAL OPENSIM MAP CELLS */

#grid-map
.australia-region-composite-v11
img{

    display:block;

    width:100%;
    height:100%;

    object-fit:fill;

    margin:0;
    padding:0;
    border:0;

    pointer-events:none;

    user-select:none;

    -webkit-user-drag:none;

}


/* KEEP EXISTING REGION INFORMATION ABOVE IMAGE */

#grid-map
.map-region.australia-region-composite-host-v11
> .map-region-header,

#grid-map
.map-region.australia-region-composite-host-v11
> .map-region-body{

    position:relative;

    z-index:2;

}


/* SAME READABILITY VEIL AS WORKING NO ENTRY TEST */

#grid-map
.map-region.australia-region-composite-host-v11::after{

    content:"";

    position:absolute;

    inset:0;

    z-index:1;

    pointer-events:none;

    background:
        linear-gradient(
            to bottom,
            rgba(2,7,11,.14),
            rgba(2,7,11,.28)
        );

}


/* END AUSTRALIA USER MAP - ALL REGION COMPOSITES V1.1 */


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


<script>

/* ============================================================
   AUSTRALIA USER MAP - ALL REGION COMPOSITES CLIENT V1.1
   ============================================================ */

(function(){

    "use strict";


    const DATA_URL =
        "/Other/grid-map-data-user.php";


    const TILE_BASE =
        <?php echo json_encode(rtrim(ag_dg_login_url(), '/'), JSON_UNESCAPED_SLASHES); ?>;


    const HOST_CLASS =
        "australia-region-composite-host-v11";


    const COMPOSITE_CLASS =
        "australia-region-composite-v11";


    let regionLookup =
        new Map();


    let buildTimer =
        0;


    let reloadTimer =
        0;



    function normalise(
        value
    ){

        return String(
            value || ""
        )
        .trim()
        .replace(
            /\s+/g,
            " "
        )
        .toLowerCase();

    }



    function mapTileUrl(
        x,
        y
    ){

        return (
            TILE_BASE +
            "/map-1-" +
            x +
            "-" +
            y +
            "-objects.jpg"
        );

    }



    function findRegion(
        card
    ){

        return regionLookup.get(
            normalise(
                card.dataset.region
            )
        ) || null;

    }



    /* ========================================================
       FIT COMPLETE REGION IMAGE INSIDE EXISTING CARD
       ======================================================== */

    function fitComposite(
        composite,
        card,
        cellsX,
        cellsY
    ){

        const cardWidth =
            card.clientWidth;


        const cardHeight =
            card.clientHeight;


        if(
            cardWidth <= 0 ||
            cardHeight <= 0
        ){

            return;

        }


        const regionAspect =
            cellsX /
            cellsY;


        const cardAspect =
            cardWidth /
            cardHeight;


        let width =
            0;


        let height =
            0;


        if(
            cardAspect >
            regionAspect
        ){

            height =
                cardHeight;


            width =
                height *
                regionAspect;

        }
        else{

            width =
                cardWidth;


            height =
                width /
                regionAspect;

        }


        composite.style.width =
            width +
            "px";


        composite.style.height =
            height +
            "px";

    }



    function clearComposites(){

        document
            .querySelectorAll(
                "#grid-map ." +
                COMPOSITE_CLASS
            )
            .forEach(
                function(element){

                    element.remove();

                }
            );

    }



    /* ========================================================
       BUILD ONE COMPLETE REGION IMAGE
       ======================================================== */

    function buildComposite(
        card
    ){

        const region =
            findRegion(
                card
            );


        if(
            !region
        ){

            return;

        }


        const baseX =
            Math.trunc(
                Number(
                    region.X
                )
            );


        const baseY =
            Math.trunc(
                Number(
                    region.Y
                )
            );


        const cellsX =
            Math.trunc(
                Number(
                    region.CellsX
                )
            );


        const cellsY =
            Math.trunc(
                Number(
                    region.CellsY
                )
            );


        if(
            !Number.isFinite(baseX) ||
            !Number.isFinite(baseY) ||
            !Number.isFinite(cellsX) ||
            !Number.isFinite(cellsY) ||
            cellsX < 1 ||
            cellsY < 1 ||
            cellsX > 16 ||
            cellsY > 16
        ){

            return;

        }


        card.classList.add(
            HOST_CLASS
        );


        const composite =
            document.createElement(
                "div"
            );


        composite.className =
            COMPOSITE_CLASS;


        composite.dataset.region =
            String(
                region.RegionName ||
                ""
            );


        composite.style.gridTemplateColumns =
            "repeat(" +
            cellsX +
            ",1fr)";


        composite.style.gridTemplateRows =
            "repeat(" +
            cellsY +
            ",1fr)";


        /*
           OpenSim Y increases north.

           Highest Y goes in the TOP HTML row.
        */

        for(
            let y =
                baseY +
                cellsY -
                1;

            y >=
                baseY;

            y--
        ){

            for(
                let x =
                    baseX;

                x <
                    baseX +
                    cellsX;

                x++
            ){

                const image =
                    document.createElement(
                        "img"
                    );


                image.alt =
                    "";


                image.draggable =
                    false;


                image.setAttribute(
                    "aria-hidden",
                    "true"
                );


                image.src =
                    mapTileUrl(
                        x,
                        y
                    );


                composite.appendChild(
                    image
                );

            }

        }


        /*
           Put map picture behind the existing region text.
        */

        card.insertBefore(
            composite,
            card.firstChild
        );


        fitComposite(
            composite,
            card,
            cellsX,
            cellsY
        );

    }



    function buildAll(){

        if(
            regionLookup.size === 0
        ){

            return;

        }


        clearComposites();


        document
            .querySelectorAll(
                "#grid-map .map-region"
            )
            .forEach(
                buildComposite
            );

    }



    function scheduleBuild(){

        window.clearTimeout(
            buildTimer
        );


        buildTimer =
            window.setTimeout(
                buildAll,
                100
            );

    }



    /* ========================================================
       LOAD SAFE REGION GEOMETRY
       ======================================================== */

    async function loadRegionData(){

        try{

            const response =
                await fetch(
                    DATA_URL +
                    "?allRegionComposites=11&_=" +
                    Date.now(),
                    {
                        cache:
                            "no-store",

                        credentials:
                            "same-origin"
                    }
                );


            if(
                !response.ok
            ){

                return;

            }


            const data =
                await response.json();


            const regions =
                Array.isArray(
                    data.regions
                )
                    ? data.regions
                    : [];


            regionLookup =
                new Map();


            regions.forEach(
                function(region){

                    const key =
                        normalise(
                            region.RegionName
                        );


                    if(
                        key
                    ){

                        regionLookup.set(
                            key,
                            region
                        );

                    }

                }
            );


            scheduleBuild();

        }
        catch(error){

            console.warn(
                "Australia region composite system:",
                error
            );

        }

    }



    function scheduleReload(){

        window.clearTimeout(
            reloadTimer
        );


        reloadTimer =
            window.setTimeout(
                loadRegionData,
                120
            );

    }



    /* ========================================================
       REBUILD AFTER NORMAL MAP REFRESH
       ======================================================== */

    const map =
        document.getElementById(
            "grid-map"
        );


    if(
        map
    ){

        const observer =
            new MutationObserver(
                function(mutations){

                    let changed =
                        false;


                    mutations.forEach(
                        function(mutation){

                            mutation.addedNodes.forEach(
                                function(node){

                                    if(
                                        node.nodeType === 1 &&
                                        node.classList &&
                                        node.classList.contains(
                                            "map-region"
                                        )
                                    ){

                                        changed =
                                            true;

                                    }

                                }
                            );


                            mutation.removedNodes.forEach(
                                function(node){

                                    if(
                                        node.nodeType === 1 &&
                                        node.classList &&
                                        node.classList.contains(
                                            "map-region"
                                        )
                                    ){

                                        changed =
                                            true;

                                    }

                                }
                            );

                        }
                    );


                    if(
                        changed
                    ){

                        scheduleReload();

                    }

                }
            );


        observer.observe(
            map,
            {
                childList:true
            }
        );

    }



    window.addEventListener(
        "resize",
        scheduleBuild
    );


    loadRegionData();


})();

/* END AUSTRALIA USER MAP - ALL REGION COMPOSITES CLIENT V1.1 */

</script>


<style>

/* ============================================================
   AUSTRALIA USER MAP - CUSTOM REGION ARTWORK V1

   PRIORITY:

       Custom PNG
           â†“
       Live OpenSim composite

   The working composite engine is NOT modified.
   ============================================================ */


#grid-map
.australia-custom-region-image-v1{

    position:absolute;

    left:50%;
    top:50%;

    transform:
        translate(-50%,-50%);

    display:block;

    z-index:0;

    margin:0;
    padding:0;
    border:0;

    object-fit:fill;

    pointer-events:none;

    user-select:none;

    -webkit-user-drag:none;

    opacity:.88;

}


/*
   Existing card information remains above custom artwork.
*/

#grid-map
.map-region.australia-custom-region-host-v1
> .map-region-header,

#grid-map
.map-region.australia-custom-region-host-v1
> .map-region-body{

    position:relative;

    z-index:2;

}


/* END AUSTRALIA USER MAP - CUSTOM REGION ARTWORK V1 */


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


<script>

/* ============================================================
   AUSTRALIA USER MAP - CUSTOM REGION ARTWORK CLIENT V1

   Custom image folder:

       /Other/images/region-tiles/

   Naming examples:

       Welcome         -> welcome.png
       FARM SHOP       -> farm-shop.png
       NORTI FARM      -> norti-farm.png
       Norti Farm 2    -> norti-farm-2.png
       Official Region -> official-region.png

   If PNG does not exist:
       live OpenSim composite remains visible.

   ============================================================ */

(function(){

    "use strict";


    const CUSTOM_BASE =
        "/Other/images/region-tiles";


    const COMPOSITE_CLASS =
        "australia-region-composite-v11";


    const CUSTOM_CLASS =
        "australia-custom-region-image-v1";


    const HOST_CLASS =
        "australia-custom-region-host-v1";


    let scanTimer =
        0;



    /* ========================================================
       REGION FILE NAME
       ======================================================== */

    function slug(
        value
    ){

        let text =
            String(
                value || ""
            )
            .trim()
            .toLowerCase();


        try{

            text =
                text.normalize(
                    "NFKD"
                );

        }
        catch(error){
        }


        return text
            .replace(
                /[\u0300-\u036f]/g,
                ""
            )
            .replace(
                /[^a-z0-9]+/g,
                "-"
            )
            .replace(
                /^-+|-+$/g,
                ""
            );

    }



    /* ========================================================
       FIT CUSTOM IMAGE EXACTLY TO WORKING COMPOSITE
       ======================================================== */

    function copyCompositeSize(
        image,
        composite
    ){

        let width =
            composite.style.width;


        let height =
            composite.style.height;


        if(
            !width
        ){

            width =
                composite.offsetWidth +
                "px";

        }


        if(
            !height
        ){

            height =
                composite.offsetHeight +
                "px";

        }


        image.style.width =
            width;


        image.style.height =
            height;

    }



    /* ========================================================
       CHECK ONE REGION
       ======================================================== */

    function probeCustomImage(
        composite
    ){

        if(
            !composite ||
            composite.dataset
                .australiaCustomCheckedV1 ===
                "1"
        ){

            return;

        }


        composite.dataset
            .australiaCustomCheckedV1 =
                "1";


        const card =
            composite.closest(
                ".map-region"
            );


        if(
            !card
        ){

            return;

        }


        const regionName =
            String(
                composite.dataset.region ||
                card.dataset.region ||
                ""
            );


        const filename =
            slug(
                regionName
            );


        if(
            !filename
        ){

            return;

        }


        /*
           Remove stale custom image left from a previous
           normal map refresh.
        */

        card
            .querySelectorAll(
                "." + CUSTOM_CLASS
            )
            .forEach(
                function(oldImage){

                    oldImage.remove();

                }
            );


        /*
           Always begin with the live composite visible.
        */

        composite.style.visibility =
            "visible";


        const image =
            document.createElement(
                "img"
            );


        image.className =
            CUSTOM_CLASS;


        image.alt =
            "";


        image.draggable =
            false;


        image.setAttribute(
            "aria-hidden",
            "true"
        );


        image.style.visibility =
            "hidden";


        copyCompositeSize(
            image,
            composite
        );


        /*
           Test custom PNG.

           Date value prevents a previously cached 404 from
           stopping a newly-added image appearing on refresh.
        */

        image.src =
            CUSTOM_BASE +
            "/" +
            encodeURIComponent(
                filename
            ) +
            ".png" +
            "?customRegionArtwork=1&_=" +
            Date.now();


        image.addEventListener(
            "load",
            function(){

                if(
                    !document.body.contains(
                        composite
                    ) ||
                    !document.body.contains(
                        card
                    )
                ){

                    return;

                }


                /*
                   CUSTOM IMAGE EXISTS.

                   Hide ONLY the live composite picture.
                   Existing card, text, click, HOP etc remain.
                */

                composite.style.visibility =
                    "hidden";


                image.style.visibility =
                    "visible";


                card.classList.add(
                    HOST_CLASS
                );

            }
        );


        image.addEventListener(
            "error",
            function(){

                /*
                   NO CUSTOM IMAGE.

                   Remove failed custom request and keep
                   working OpenSim composite visible.
                */

                composite.style.visibility =
                    "visible";


                image.remove();


                card.classList.remove(
                    HOST_CLASS
                );

            }
        );


        /*
           Insert beside the working composite.
           Existing text remains above both.
        */

        card.insertBefore(
            image,
            composite.nextSibling
        );

    }



    /* ========================================================
       SCAN ALL CURRENT REGIONS
       ======================================================== */

    function scan(){

        document
            .querySelectorAll(
                "#grid-map ." +
                COMPOSITE_CLASS
            )
            .forEach(
                probeCustomImage
            );

    }



    function scheduleScan(){

        window.clearTimeout(
            scanTimer
        );


        scanTimer =
            window.setTimeout(
                scan,
                80
            );

    }



    /* ========================================================
       WATCH WORKING COMPOSITE ENGINE

       When Refresh Map recreates a composite, test that
       region's custom PNG again.

       ======================================================== */

    const map =
        document.getElementById(
            "grid-map"
        );


    if(
        map
    ){

        const observer =
            new MutationObserver(
                function(){

                    scheduleScan();

                }
            );


        observer.observe(
            map,
            {
                childList:true,
                subtree:true
            }
        );

    }



    /*
       Composite system loads asynchronously, so scan once
       immediately and once again shortly afterwards.
    */

    scan();


    window.setTimeout(
        scan,
        500
    );


})();

/* END AUSTRALIA USER MAP - CUSTOM REGION ARTWORK CLIENT V1 */

</script>


<style>

/* ============================================================
   AUSTRALIA USER MAP - REGION CARD TEXTURE LAYOUT V1

   KEEP THE EXISTING BOXES.

   Layout:

       REGION NAME / STATUS
       FULL REGION TEXTURE
       SIZE / AVATAR INFORMATION

   ============================================================ */


/* ------------------------------------------------------------
   CARD
   ------------------------------------------------------------ */

#grid-map
.map-region.australia-region-composite-host-v11{

    overflow:
        hidden !important;

    padding:
        0 !important;

}


/* ------------------------------------------------------------
   REGION NAME BAR
   ------------------------------------------------------------ */

#grid-map
.map-region.australia-region-composite-host-v11
> .map-region-header{

    position:absolute !important;

    left:0 !important;
    top:0 !important;
    right:0 !important;

    height:25px !important;

    box-sizing:border-box;

    z-index:20 !important;

    display:flex !important;

    align-items:center !important;

    padding:
        4px 7px !important;

    background:
        rgba(4,11,17,.94) !important;

    border-bottom:
        1px solid rgba(65,178,218,.35);

}


/* NAME ALWAYS ABOVE TEXTURE */

#grid-map
.map-region.australia-region-composite-host-v11
.map-region-name{

    position:relative;

    z-index:21;

    overflow:hidden;

    white-space:nowrap;

    text-overflow:ellipsis;

    text-shadow:
        0 1px 2px #000;

}


/* ------------------------------------------------------------
   BOTTOM INFORMATION BAR
   ------------------------------------------------------------ */

#grid-map
.map-region.australia-region-composite-host-v11
> .map-region-body{

    position:absolute !important;

    left:0 !important;
    right:0 !important;
    bottom:0 !important;

    min-height:27px !important;

    box-sizing:border-box;

    z-index:20 !important;

    display:flex !important;

    align-items:center !important;

    justify-content:center !important;

    gap:8px !important;

    padding:
        3px 6px !important;

    background:
        rgba(4,11,17,.92) !important;

    border-top:
        1px solid rgba(65,178,218,.30);

    font-size:
        10px !important;

}


/*
   Put size and avatar count side by side.
*/

#grid-map
.map-region.australia-region-composite-host-v11
> .map-region-body
> div{

    margin:
        0 !important;

    padding:
        0 !important;

    white-space:
        nowrap;

}


/* ------------------------------------------------------------
   LIVE OPENSIM COMPOSITE
   ------------------------------------------------------------ */

#grid-map
.australia-region-composite-v11{

    /*
       JavaScript below calculates exact dimensions.

       These rules make sure no older centering rule fights it.
    */

    transform:
        none !important;

    margin:
        0 !important;

    z-index:
        5 !important;

    border-radius:
        4px;

}


/* ------------------------------------------------------------
   CUSTOM PNG
   ------------------------------------------------------------ */

#grid-map
.australia-custom-region-image-v1{

    transform:
        none !important;

    margin:
        0 !important;

    z-index:
        6 !important;

    border-radius:
        4px;

}


/* END AUSTRALIA USER MAP - REGION CARD TEXTURE LAYOUT V1 */


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


<script>

/* ============================================================
   AUSTRALIA USER MAP - REGION CARD TEXTURE LAYOUT CLIENT V1

   Fits the complete square simulator/custom texture BETWEEN
   the region-name bar and the information bar.

   It NEVER resizes or moves the actual .map-region card.
   ============================================================ */

(function(){

    "use strict";


    const COMPOSITE_CLASS =
        "australia-region-composite-v11";


    const CUSTOM_CLASS =
        "australia-custom-region-image-v1";


    const HEADER_HEIGHT =
        25;


    const FOOTER_HEIGHT =
        27;


    const INNER_GAP =
        3;


    let timer =
        0;



    function fitOneCard(
        card
    ){

        if(
            !card
        ){

            return;

        }


        const composite =
            card.querySelector(
                "." + COMPOSITE_CLASS
            );


        if(
            !composite
        ){

            return;

        }


        const custom =
            card.querySelector(
                "." + CUSTOM_CLASS
            );


        const cardWidth =
            card.clientWidth;


        const cardHeight =
            card.clientHeight;


        if(
            cardWidth <= 0 ||
            cardHeight <= 0
        ){

            return;

        }


        /*
           Available image area between the top title bar
           and bottom info bar.
        */

        const availableWidth =
            Math.max(
                1,
                cardWidth -
                (
                    INNER_GAP *
                    2
                )
            );


        const availableHeight =
            Math.max(
                1,
                cardHeight -
                HEADER_HEIGHT -
                FOOTER_HEIGHT -
                (
                    INNER_GAP *
                    2
                )
            );


        /*
           All current Australia regions are square varregions,
           so preserve the map texture as a square.

           Never crop it.
        */

        const size =
            Math.max(
                1,
                Math.min(
                    availableWidth,
                    availableHeight
                )
            );


        const left =
            Math.round(
                (
                    cardWidth -
                    size
                ) /
                2
            );


        const top =
            Math.round(
                HEADER_HEIGHT +
                INNER_GAP +
                (
                    (
                        availableHeight -
                        size
                    ) /
                    2
                )
            );


        composite.style.setProperty(
            "left",
            left + "px",
            "important"
        );


        composite.style.setProperty(
            "top",
            top + "px",
            "important"
        );


        composite.style.setProperty(
            "width",
            size + "px",
            "important"
        );


        composite.style.setProperty(
            "height",
            size + "px",
            "important"
        );


        if(
            custom
        ){

            custom.style.setProperty(
                "left",
                left + "px",
                "important"
            );


            custom.style.setProperty(
                "top",
                top + "px",
                "important"
            );


            custom.style.setProperty(
                "width",
                size + "px",
                "important"
            );


            custom.style.setProperty(
                "height",
                size + "px",
                "important"
            );

        }

    }



    function fitAll(){

        document
            .querySelectorAll(
                "#grid-map .map-region"
            )
            .forEach(
                fitOneCard
            );

    }



    function scheduleFit(){

        window.clearTimeout(
            timer
        );


        timer =
            window.setTimeout(
                fitAll,
                80
            );

    }



    const map =
        document.getElementById(
            "grid-map"
        );


    if(
        map
    ){

        const observer =
            new MutationObserver(
                function(){

                    scheduleFit();

                }
            );


        observer.observe(
            map,
            {
                childList:true,
                subtree:true
            }
        );

    }



    window.addEventListener(
        "resize",
        scheduleFit
    );


    /*
       Allow the existing composite engine and custom-image
       system to finish creating their elements first.
    */

    window.setTimeout(
        fitAll,
        250
    );


    window.setTimeout(
        fitAll,
        700
    );


})();

/* END AUSTRALIA USER MAP - REGION CARD TEXTURE LAYOUT CLIENT V1 */

</script>







<script>

/* ============================================================
   Grid MAP - PICK DEEP LINK V1

   Accepts:

       ?region=Welcome&x=128&y=128&z=22

   Opens the existing Grid web map,
   selects the requested region,
   opens Region Details,
   and centres the map on that region.
   ============================================================ */

(function(){

    "use strict";


    const params =
        new URLSearchParams(
            window.location.search
        );


    const requestedRegion =
        String(
            params.get("region") || ""
        ).trim();


    const requestedX =
        String(
            params.get("x") || ""
        ).trim();


    const requestedY =
        String(
            params.get("y") || ""
        ).trim();


    const requestedZ =
        String(
            params.get("z") || ""
        ).trim();


    if(!requestedRegion){
        return;
    }


    function normalise(value){

        return String(
            value || ""
        )
        .trim()
        .replace(
            /\s+/g,
            " "
        )
        .toLowerCase();

    }


    /*
     * Feed the existing Search/Selection Memory system.
     */

    try{

        localStorage.setItem(
            "AustraliaGridMapSelectedRegionV2",
            requestedRegion
        );

    }
    catch(error){
    }


    let attempts = 0;


    function openRequestedRegion(){

        attempts++;


        const tiles =
            Array.from(
                document.querySelectorAll(
                    "#grid-map .map-region"
                )
            );


        const tile =
            tiles.find(
                function(item){

                    return normalise(
                        item.dataset.region ||
                        item.getAttribute(
                            "data-region"
                        ) ||
                        ""
                    ) ===
                    normalise(
                        requestedRegion
                    );

                }
            );


        if(!tile){

            if(attempts < 100){

                setTimeout(
                    openRequestedRegion,
                    150
                );

            }

            return;
        }


        /*
         * Use the map's existing click handler.
         * This opens Region Details and applies
         * the normal gold selected-region state.
         */

        try{

            tile.click();

        }
        catch(error){
        }


        /*
         * Centre the selected region using the
         * map's existing pan/zoom engine.
         */

        setTimeout(
            function(){

                try{

                    if(
                        window.AustraliaGridMapView &&
                        typeof
                            window
                                .AustraliaGridMapView
                                .centreRegion ===
                            "function"
                    ){

                        window
                            .AustraliaGridMapView
                            .centreRegion(
                                tile
                            );

                    }

                }
                catch(error){
                }

            },
            100
        );


        /*
         * Store the Pick coordinates so future
         * map enhancements can place an exact pin.
         */

        window.AustraliaGridPickLocation = {

            region:
                requestedRegion,

            x:
                requestedX,

            y:
                requestedY,

            z:
                requestedZ

        };

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            function(){

                setTimeout(
                    openRequestedRegion,
                    250
                );

            }
        );

    }
    else{

        setTimeout(
            openRequestedRegion,
            250
        );

    }


})();

/* END Grid MAP - PICK DEEP LINK V1 */

</script>
<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>
</html>





