<?php

require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/login/session.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$session = dreamGridCurrentSession();


if (!$session) {
    header('Location: /Other/login.php');
    exit;
}

$avatar = $session['avatar'];
$level  = (int)$session['level'];

if ($level < 200) {
    http_response_code(403);
    echo 'Grid Owner access required';
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Grid Map</title>

<?php if (!isset($_GET["from"]) || $_GET["from"] !== "admin"): ?>
<link rel="stylesheet" href="/Other/australia-panel.css?v=69">
<?php endif; ?>

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
          DOWN
   Real transparent island PNG
          DOWN
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
            440px,
            calc(100vh - 240px)
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

</style>

<style id="australia-admin-map-gold-back">

/* AUSTRALIA ADMIN MAP GOLD BACK BUTTON */

a[data-admin-map-back-gold="1"]{

    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;

    min-width:170px !important;
    min-height:44px !important;

    padding:10px 18px !important;
    box-sizing:border-box !important;

    border:1px solid #ffe077 !important;
    border-radius:8px !important;

    background:
        linear-gradient(
            180deg,
            #ffe48b 0%,
            #e9ad2b 45%,
            #b46f08 100%
        ) !important;

    color:#111111 !important;

    font-family:Arial,Helvetica,sans-serif !important;
    font-size:12px !important;
    font-weight:900 !important;
    letter-spacing:.045em !important;

    text-decoration:none !important;
    text-align:center !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.55),
        0 4px 10px rgba(0,0,0,.45) !important;

    cursor:pointer !important;
}

a[data-admin-map-back-gold="1"]:hover{

    filter:brightness(1.08) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.70),
        0 5px 13px rgba(0,0,0,.55) !important;
}

</style>

<style id="australia-online-now-colour-symbol">

/* AUSTRALIA ONLINE NOW COLOURED TRIANGLE */

.gridmap-online-v2-collapse{

    display:flex !important;
    align-items:center !important;
    justify-content:center !important;

    width:40px !important;
    height:40px !important;

    min-width:40px !important;
    min-height:40px !important;

    max-width:40px !important;
    max-height:40px !important;

    padding:0 !important;
    margin:0 !important;

    box-sizing:border-box !important;
    overflow:hidden !important;

    font-family:
        "Segoe UI Symbol",
        Arial,
        Helvetica,
        sans-serif !important;

    font-size:18px !important;
    font-style:normal !important;
    font-weight:900 !important;

    line-height:40px !important;

    text-align:center !important;
    text-indent:0 !important;
    letter-spacing:0 !important;

    color:#45caff !important;

    text-shadow:
        0 0 4px rgba(69,202,255,.95),
        0 0 8px rgba(69,202,255,.70) !important;

    white-space:nowrap !important;

    cursor:pointer !important;
}

.gridmap-online-v2-collapse:hover{

    color:#8ee4ff !important;

    text-shadow:
        0 0 5px rgba(142,228,255,1),
        0 0 10px rgba(69,202,255,.90) !important;
}

</style>

<style id="australia-stats-panel-height-fix">

/* Grid - STATS PANEL HEIGHT FIX */

#gridmap-stats-panel-v1{

    max-height:440px !important;
    height:auto !important;

    box-sizing:border-box !important;

    overflow:hidden !important;
}


/* Keep Stats content scrolling INSIDE the shorter panel */

#gridmap-stats-panel-v1 .gridmap-stats-body-v1,
#gridmap-stats-panel-v1 .gridmap-stats-content-v1,
#gridmap-stats-panel-v1 .gridmap-stats-scroll-v1{

    min-height:0 !important;

    max-height:100% !important;

    overflow-y:auto !important;
    overflow-x:hidden !important;
}

</style>

<style id="australia-grid-compass-v3">

/* ============================================================
   Grid MAP - COMPASS V3
   Visual override only.
   ============================================================ */


/* ------------------------------------------------------------
   MAIN COMPASS
   ------------------------------------------------------------ */

#gridmap-compass.gridmap-compass,
.gridmap-compass{

    position:absolute !important;

    right:26px !important;
    bottom:38px !important;

    width:116px !important;
    height:116px !important;

    /*
       Put compass above map grid / coordinate layers.
       Keep one value below browser maximum.
    */

    z-index:2147483600 !important;

    isolation:isolate !important;

    pointer-events:none !important;
    user-select:none !important;

    transform:translateZ(0) !important;

    filter:
        drop-shadow(0 8px 12px rgba(0,0,0,.82))
        drop-shadow(0 0 8px rgba(255,190,45,.22)) !important;
}


/* ------------------------------------------------------------
   SOLID OUTER FACE

   Fully opaque background prevents map grid lines from
   showing through the compass.
   ------------------------------------------------------------ */

#gridmap-compass .gridmap-compass-ring{

    position:absolute !important;

    inset:0 !important;

    border:
        3px solid #d89a1b !important;

    border-radius:50% !important;

    background:
        radial-gradient(
            circle at 50% 42%,
            #16252d 0%,
            #0a151b 34%,
            #050c10 68%,
            #010406 100%
        ) !important;

    box-shadow:

        inset 0 0 0 2px
        rgba(255,220,120,.20),

        inset 0 0 0 7px
        rgba(0,0,0,.70),

        inset 0 0 28px
        rgba(0,0,0,.95),

        0 0 0 1px
        rgba(0,0,0,.95),

        0 0 16px
        rgba(255,183,28,.38) !important;

    overflow:hidden !important;

    z-index:1 !important;
}


/* ------------------------------------------------------------
   INNER GOLD DIAL RING
   ------------------------------------------------------------ */

#gridmap-compass .gridmap-compass-ring::before{

    content:"" !important;

    position:absolute !important;

    inset:10px !important;

    border:
        1px solid rgba(255,195,58,.48) !important;

    border-radius:50% !important;

    box-shadow:

        inset 0 0 10px
        rgba(255,190,45,.08),

        0 0 5px
        rgba(255,190,45,.10) !important;

    background:transparent !important;
}


/* Remove old full-height north/south line.
   This avoids the compass looking like map grid lines. */

#gridmap-compass .gridmap-compass-ring::after{

    display:none !important;
}


/* ------------------------------------------------------------
   EAST / WEST DIAL LINE
   Short and subtle \u2014 clearly part of compass, not map grid.
   ------------------------------------------------------------ */

#gridmap-compass .gridmap-compass-cross{

    position:absolute !important;

    left:23px !important;
    top:57px !important;

    width:70px !important;
    height:1px !important;

    background:
        linear-gradient(
            90deg,
            transparent 0%,
            rgba(255,190,45,.30) 20%,
            rgba(255,190,45,.50) 50%,
            rgba(255,190,45,.30) 80%,
            transparent 100%
        ) !important;

    z-index:2 !important;
}


/* ------------------------------------------------------------
   NORTH NEEDLE
   ------------------------------------------------------------ */

#gridmap-compass .gridmap-compass-needle-north{

    position:absolute !important;

    left:50% !important;
    top:28px !important;

    width:0 !important;
    height:0 !important;

    transform:
        translateX(-50%) !important;

    border-left:
        8px solid transparent !important;

    border-right:
        8px solid transparent !important;

    border-bottom:
        31px solid #ffc43d !important;

    filter:
        drop-shadow(
            0 0 5px
            rgba(255,196,61,.85)
        ) !important;

    z-index:5 !important;
}


/* ------------------------------------------------------------
   SOUTH NEEDLE
   ------------------------------------------------------------ */

#gridmap-compass .gridmap-compass-needle-south{

    position:absolute !important;

    left:50% !important;
    top:57px !important;

    width:0 !important;
    height:0 !important;

    transform:
        translateX(-50%) !important;

    border-left:
        7px solid transparent !important;

    border-right:
        7px solid transparent !important;

    border-top:
        27px solid #a9b8c0 !important;

    filter:
        drop-shadow(
            0 1px 2px
            rgba(0,0,0,.85)
        ) !important;

    z-index:4 !important;
}


/* ------------------------------------------------------------
   CENTRE PIN
   ------------------------------------------------------------ */

#gridmap-compass .gridmap-compass-centre{

    position:absolute !important;

    left:50% !important;
    top:50% !important;

    width:14px !important;
    height:14px !important;

    transform:
        translate(-50%,-50%) !important;

    border:
        2px solid #ffd669 !important;

    border-radius:50% !important;

    background:
        radial-gradient(
            circle,
            #7ee8ff 0%,
            #169ac4 30%,
            #101a20 33%,
            #05090b 100%
        ) !important;

    box-shadow:

        0 0 0 3px
        rgba(0,0,0,.65),

        0 0 9px
        rgba(80,215,255,.80),

        0 0 14px
        rgba(255,193,58,.55) !important;

    z-index:8 !important;
}


/* ------------------------------------------------------------
   N / E / S / W LETTERS
   ------------------------------------------------------------ */

#gridmap-compass .gridmap-compass-direction{

    position:absolute !important;

    font-family:
        Arial,
        Helvetica,
        sans-serif !important;

    font-size:13px !important;
    font-weight:900 !important;

    line-height:1 !important;

    color:#c7d1d6 !important;

    text-shadow:
        0 2px 3px #000,
        0 0 5px rgba(0,0,0,.95) !important;

    z-index:10 !important;
}


/* NORTH */

#gridmap-compass .gridmap-compass-north{

    left:50% !important;
    top:8px !important;

    transform:
        translateX(-50%) !important;

    color:#ffc43d !important;

    font-size:18px !important;

    text-shadow:
        0 2px 3px #000,
        0 0 8px rgba(255,196,61,.60) !important;
}


/* SOUTH */

#gridmap-compass .gridmap-compass-south{

    left:50% !important;
    bottom:9px !important;

    transform:
        translateX(-50%) !important;
}


/* EAST */

#gridmap-compass .gridmap-compass-east{

    right:9px !important;
    top:50% !important;

    transform:
        translateY(-50%) !important;
}


/* WEST */

#gridmap-compass .gridmap-compass-west{

    left:9px !important;
    top:50% !important;

    transform:
        translateY(-50%) !important;
}


/* ------------------------------------------------------------
   GRID NORTH BADGE
   ------------------------------------------------------------ */

#gridmap-compass .gridmap-compass-caption{

    position:absolute !important;

    left:50% !important;
    bottom:-26px !important;

    transform:
        translateX(-50%) !important;

    white-space:nowrap !important;

    padding:
        3px 8px !important;

    border:
        1px solid rgba(216,154,27,.58) !important;

    border-radius:
        10px !important;

    background:
        rgba(3,9,12,.96) !important;

    color:
        #e2bd59 !important;

    font-family:
        Arial,
        Helvetica,
        sans-serif !important;

    font-size:
        8px !important;

    font-weight:
        900 !important;

    letter-spacing:
        .13em !important;

    text-shadow:
        0 1px 2px #000 !important;

    box-shadow:
        0 3px 7px
        rgba(0,0,0,.68) !important;

    z-index:
        11 !important;
}


/* ------------------------------------------------------------
   FULL SCREEN
   ------------------------------------------------------------ */

.gridmap-scroll:fullscreen
#gridmap-compass,

.gridmap-scroll:-webkit-full-screen
#gridmap-compass{

    right:34px !important;
    bottom:48px !important;

    transform:
        scale(1.12)
        translateZ(0) !important;

    transform-origin:
        bottom right !important;

    z-index:
        2147483600 !important;
}

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

<style id="ag-map-control-center-v1">/* AG MAP CONTROL CENTER V1 START */html,body{width:100%!important;min-height:100%!important;height:auto!important;margin:0!important;padding:0!important;overflow-x:hidden!important;overflow-y:auto!important}body>.shell,.shell{width:100%!important;max-width:none!important;height:auto!important;min-height:100%!important;margin:0!important;padding:0!important}.ag-map-v1{width:100%!important;max-width:none!important;height:auto!important;min-height:100vh!important;margin:0!important;padding:8px!important;display:block!important;overflow:visible!important;box-sizing:border-box!important;color:#d7dde0}.ag-map-v1 *{box-sizing:border-box}.ag-map-v1-intro{min-height:56px;margin:0 0 8px!important;padding:9px 12px;display:flex;align-items:center;justify-content:space-between;gap:15px;border:1px solid rgba(214,164,59,.34);border-radius:6px;background:linear-gradient(180deg,#151918 0%,#0b0f0e 100%);box-shadow:0 6px 16px rgba(0,0,0,.30)}.ag-map-v1-identity{display:flex;align-items:center;gap:10px;min-width:0}.ag-map-v1-icon{width:30px;height:30px;display:block;object-fit:contain}.ag-map-v1-title{color:#eef2f3;font-size:12px;font-weight:900}.ag-map-v1-subtitle{margin-top:4px;color:#747f83;font-size:7px;font-weight:800;letter-spacing:.08em}.ag-map-v1-live,.ag-map-v1-ready{position:relative;padding-left:13px;color:#72dc96;font-size:7px;font-weight:900;letter-spacing:.08em}.ag-map-v1-live:before,.ag-map-v1-ready:before{content:"";position:absolute;left:0;top:50%;width:7px;height:7px;margin-top:-4px;border-radius:50%;background:#5ce28e;box-shadow:0 0 8px rgba(92,226,142,.9)}.ag-map-v1 .gridmap-toolbar{width:100%!important;margin:0 0 8px!important;padding:8px 10px!important;border:1px solid rgba(214,164,59,.28)!important;border-radius:6px!important;background:linear-gradient(180deg,#131817 0%,#090d0c 100%)!important;background-image:none!important;box-shadow:0 5px 12px rgba(0,0,0,.26)!important}.ag-map-v1 .gridmap-toolbar button{min-height:30px!important;padding:0 14px!important;border:1px solid rgba(214,164,59,.52)!important;border-radius:4px!important;background:linear-gradient(180deg,#252b2a 0%,#101413 55%,#080b0a 100%)!important;color:#eef2f3!important;font-size:7px!important;font-weight:900!important;letter-spacing:.04em!important}.ag-map-v1 #gridmap-updated{color:#778488!important;font-size:7px!important}.ag-map-v1 .gridmap-legend{color:#aab4b7!important;font-size:7px!important}.ag-map-v1-region{width:100%!important;max-width:none!important;height:auto!important;margin:0 0 8px!important;border:1px solid rgba(214,164,59,.34);border-radius:6px;overflow:hidden;background:linear-gradient(180deg,#151918 0%,#0b0f0e 100%);box-shadow:0 6px 16px rgba(0,0,0,.30)}.ag-map-v1-region-head{min-height:48px;padding:8px 12px;display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid rgba(255,255,255,.075);background:linear-gradient(180deg,rgba(24,29,28,.94),rgba(14,18,17,.94))}.ag-map-v1-region-title{color:#eef2f3;font-size:10px;font-weight:900}.ag-map-v1-region-subtitle{margin-top:3px;color:#747f83;font-size:7px;font-weight:800;letter-spacing:.07em}.ag-map-v1-region-body{width:100%!important;height:auto!important;min-height:0!important;padding:8px!important;display:block!important}.ag-map-v1 .gridmap-shell{width:100%!important;max-width:none!important;height:auto!important;min-height:0!important;margin:0!important;padding:6px!important;border:1px solid rgba(214,164,59,.20)!important;border-radius:5px!important;background:#070b0a!important;background-image:none!important;box-shadow:none!important;display:block!important}.ag-map-v1 .gridmap-scroll{width:100%!important;height:calc(100vh - 110px)!important;min-height:650px!important;max-height:none!important;margin:0!important;border:1px solid rgba(214,164,59,.20)!important;border-radius:4px!important;overflow:hidden!important}.ag-map-v1-details{width:100%!important;max-width:none!important;height:auto!important;min-height:104px!important;margin:0!important}.ag-map-v1-details .ag-map-v1-region-head{min-height:40px!important}.ag-map-v1-details h2{display:none!important}.ag-map-v1 #gridmap-details-body{min-height:55px!important;margin:6px 8px!important;padding:9px 10px;border:1px solid rgba(255,255,255,.065);border-radius:4px;background:#080c0b;color:#8d999d;font-size:8px;line-height:1.35}/* AG MAP PAGE NUDGE V1 */body.ag-map-page main.ag-map-v1{position:relative!important;left:-18px!important;width:calc(100% + 18px)!important;}/* AG MAP CONTROL CENTER V1 END */</style>

</head>

<body class="ag-map-page">

<div class="shell">

<?php
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "GRID MAP";
$siteHeaderButton = "BACK TO DASHBOARD";
$siteHeaderLink = "/Other/admin-dashboard.php";
require_once __DIR__ . "/includes/site-header.php";
?>


<main class="ag-map-v1"><section class="ag-map-v1-intro"><div class="ag-map-v1-identity"><img src="/Other/assets/icons/sentinel/map.png" alt="" class="ag-map-v1-icon"><div><div class="ag-map-v1-title">Live Grid Map</div><div class="ag-map-v1-subtitle">REAL-TIME OPENSIM REGION OVERVIEW</div></div></div><div class="ag-map-v1-live">LIVE</div></section><section class="ag-map-v1-toolbar gridmap-toolbar"><div class="gridmap-toolbar-left"><button type="button" id="gridmap-refresh">REFRESH MAP</button><span id="gridmap-updated">Waiting for first refresh...</span></div><div class="gridmap-legend"><span class="map-legend-item"><span class="map-dot online"></span>ONLINE</span><span class="map-legend-item"><span class="map-dot warning"></span>WARNING</span><span class="map-legend-item"><span class="map-dot offline"></span>OFFLINE</span></div></section><section class="ag-map-v1-region"><header class="ag-map-v1-region-head"><div><div class="ag-map-v1-region-title">Grid Region Map</div><div class="ag-map-v1-region-subtitle">LIVE REGION POSITIONS, STATUS AND MAP DATA</div></div><div class="ag-map-v1-ready">READY</div></header><div class="ag-map-v1-region-body"><section class="gridmap-shell"><div class="gridmap-scroll"><div id="grid-map"><div class="gridmap-message">Loading grid map...</div></div></div></section></div></section><section class="ag-map-v1-region ag-map-v1-details gridmap-details"><header class="ag-map-v1-region-head"><div><div class="ag-map-v1-region-title">Region Details</div><div class="ag-map-v1-region-subtitle">SELECT A REGION ON THE MAP</div></div></header><div class="ag-map-v1-region-body"><div id="gridmap-details-body">Select a region on the map to view its details.</div></div></section></main>

</div>


<script>
window.DG_GRID_MAP = {
    level: <?=json_encode($level)?>,
    avatar: <?=json_encode($avatar)?>
};
</script>

<script src="/Other/grid-map.js?v=1"></script>








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
                title="Zoom out"><span class="ag-map-arrow-v4" aria-hidden="true">&#8592;</span></button>

            <span
                id="firestorm-map-zoom-value"
                class="firestorm-map-zoom-value">
                100%
            </span>

            <button
                type="button"
                id="firestorm-map-plus"
                class="firestorm-map-button"
                title="Zoom in"><span class="ag-map-arrow-v4" aria-hidden="true">&#8594;</span></button>

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
                "/Other/grid-map-data.php",
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
         * V7:
         *
         * DO NOT invoke hop:// here.
         *
         * Show the Australia Control Center confirmation box.
         * The external protocol is only invoked if the user
         * explicitly presses OPEN FIRESTORM.
         */

        if(
            window.AustraliaHopModalV7 &&
            typeof
                window.AustraliaHopModalV7.open ===
                "function"
        ){

            window.AustraliaHopModalV7.open({

                hop:
                    hop,

                regionName:
                    String(
                        region.RegionName ||
                        "REGION"
                    )

            });


            return;

        }


        console.error(
            "HOP modal is not available."
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
                disabled>HOP TO REGION</button>

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
                    "/Other/grid-map-data.php",
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
                    "/Other/grid-map-data.php",
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
                    ? "\u2014"
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
            return "\u2014";
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
                    "/Other/grid-map-data.php",
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
                    "/Other/regions.php?hover=1&nocache=" +
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
            "\u2014";


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
                        cellsX + " \u00D7 " + cellsY
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
                            ? "\u2014"
                            : avatars
                    )}
                </span>


                <span class="gridmap-hover-label">
                    PRIMS
                </span>

                <span class="gridmap-hover-value">
                    ${safe(
                        prims === ""
                            ? "\u2014"
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
    CLICK FOR DETAILS &nbsp; &#8226; &nbsp; DOUBLE CLICK TO HOP
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
        <?=json_encode(ag_web_map_token())?>;


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
                    /\s+(GRID OWNER|USER LEVEL|HOME|LOG OUT).*$/i,
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
                    "/Other/avatar-location.php?" +
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
   Grid MAP - LIVE AVATAR DOTS CLIENT V1

   Displays every live USER avatar returned by OpenSim.

   The signed-in avatar is excluded because the existing
   gold YOU ARE HERE marker already represents that avatar.
   ============================================================ */

(function(){

    const API_TOKEN =
        "56ec74dce329f0cb8ea455190dd31444d9bb197010b0c5586f23c0834a3b8bbb";


    const POLL_MS =
        5000;


    const markers =
        new Map();


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
       CURRENT SIGNED-IN AVATAR
       ======================================================== */

    function signedInAvatar(){

        try{

            if(
                window.DG &&
                typeof window.DG ===
                "object"
            ){

                const candidate =
                    window.DG.avatar ||
                    window.DG.username ||
                    window.DG.user ||
                    window.DG.name;


                if(
                    candidate &&
                    String(
                        candidate
                    ).trim()
                ){

                    return String(
                        candidate
                    ).trim();

                }

            }

        }
        catch(error){
        }


        const bodyText =
            String(
                document.body
                    ? document.body.innerText
                    : ""
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
                candidate.replace(
                    /\s+(GRID OWNER|USER LEVEL|HOME|LOG OUT).*$/i,
                    ""
                ).trim();


            if(candidate){
                return candidate;
            }

        }


        return "";

    }


    /* ========================================================
       FIND REAL REGION TILE
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
       NUMBER HELPERS
       ======================================================== */

    function numberValue(
        value
    ){

        const number =
            parseFloat(
                value
            );


        return Number.isFinite(
            number
        )
            ? number
            : null;

    }


    function firstNumber(){

        for(
            let i = 0;
            i < arguments.length;
            i++
        ){

            const number =
                numberValue(
                    arguments[i]
                );


            if(
                number !== null
            ){

                return number;

            }

        }


        return null;

    }


    /* ========================================================
       TRUE REGION FOOTPRINT

       Important for FARM SHOP / Welcome / Builder Box because
       their visual cards may be larger than the real region.
       ======================================================== */

    function regionGeometry(
        tile
    ){

        if(!tile){
            return null;
        }


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

    function createMarker(
        key
    ){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){
            return null;
        }


        const element =
            document.createElement(
                "div"
            );


        element.className =
            "australia-grid-agent";


        element.dataset.agentKey =
            key;


        element.innerHTML = `

            <div
                class="australia-grid-agent-pulse">
            </div>

            <div
                class="australia-grid-agent-dot">
            </div>

            <div
                class="australia-grid-agent-label">

                <span
                    class="australia-grid-agent-name">
                </span>

                <span
                    class="australia-grid-agent-region">
                </span>

                <span
                    class="australia-grid-agent-position">
                </span>

            </div>

        `;


        map.appendChild(
            element
        );


        const state = {

            element:
                element,

            misses:
                0
        };


        markers.set(
            key,
            state
        );


        return state;

    }


    /* ========================================================
       POSITION MARKER
       ======================================================== */

    function positionAgent(
        state,
        agent
    ){

        const tile =
            findRegionTile(
                agent.region
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
                    agent.size_x ||
                    256
                )
            );


        const sizeY =
            Math.max(
                1,
                Number(
                    agent.size_y ||
                    256
                )
            );


        let localX =
            Number(
                agent.pos_x
            );


        let localY =
            Number(
                agent.pos_y
            );


        if(
            !Number.isFinite(localX) ||
            !Number.isFinite(localY)
        ){

            return false;

        }


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
           OpenSim Y increases NORTH.

           Browser Y increases DOWN.

           Flip Y exactly as the YOU ARE HERE marker does.
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


        const element =
            state.element;


        element.style.left =
            mapX + "px";


        element.style.top =
            mapY + "px";


        element.classList.toggle(
            "is-flying",
            Boolean(
                agent.is_flying
            )
        );


        const name =
            element.querySelector(
                ".australia-grid-agent-name"
            );


        const region =
            element.querySelector(
                ".australia-grid-agent-region"
            );


        const position =
            element.querySelector(
                ".australia-grid-agent-position"
            );


        if(name){

            name.textContent =
                String(
                    agent.name ||
                    "Unknown Avatar"
                );

        }


        if(region){

            region.textContent =
                String(
                    agent.region ||
                    ""
                );

        }


        if(position){

            position.textContent =
                "X " +
                Math.round(
                    Number(
                        agent.pos_x
                    )
                ) +
                "  Y " +
                Math.round(
                    Number(
                        agent.pos_y
                    )
                ) +
                "  Z " +
                Math.round(
                    Number(
                        agent.pos_z
                    )
                );

        }


        state.misses =
            0;


        return true;

    }


    /* ========================================================
       RENDER LIVE AGENTS
       ======================================================== */

    function renderAgents(
        agents
    ){

        const ownAvatar =
            normalise(
                signedInAvatar()
            );


        const seen =
            new Set();


        for(
            const agent of agents
        ){

            if(
                !agent ||
                !agent.name ||
                !agent.region
            ){

                continue;

            }


            /*
               Gold YOU ARE HERE already displays the
               signed-in avatar, so avoid drawing a duplicate
               green marker over it.
            */

            if(
                ownAvatar &&
                normalise(
                    agent.name
                ) === ownAvatar
            ){

                continue;

            }


            const key =
                String(
                    agent.id ||
                    (
                        agent.region +
                        "|" +
                        agent.name
                    )
                ).toLowerCase();


            seen.add(
                key
            );


            let state =
                markers.get(
                    key
                );


            if(
                !state ||
                !state.element ||
                !state.element.isConnected
            ){

                state =
                    createMarker(
                        key
                    );

            }


            if(!state){
                continue;
            }


            positionAgent(
                state,
                agent
            );

        }


        /*
           Keep a marker through one missed poll so region
           crossings do not cause distracting blinking.
        */

        for(
            const [
                key,
                state
            ]
            of markers
        ){

            if(
                seen.has(
                    key
                )
            ){

                continue;

            }


            state.misses++;


            if(
                state.misses >= 2
            ){

                if(
                    state.element
                ){

                    state.element.remove();

                }


                markers.delete(
                    key
                );

            }

        }

    }


    /* ========================================================
       POLL SERVER
       ======================================================== */

    async function updateAgents(){

        try{

            const params =
                new URLSearchParams();


            params.set(
                "token",
                API_TOKEN
            );


            params.set(
                "nocache",
                String(
                    Date.now()
                )
            );


            const response =
                await fetch(
                    "/Other/grid-agents.php?" +
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
                !data.ok ||
                !Array.isArray(
                    data.agents
                )
            ){

                return;

            }


            
/* Grid MAP - ONLINE NOW SHARED FEED V2 */

window.AustraliaGridLiveAgentsV2 = data;

window.dispatchEvent(
    new CustomEvent(
        "australia-grid-live-agents-v2",
        {
            detail:data
        }
    )
);

/* END Grid MAP - ONLINE NOW SHARED FEED V2 */

renderAgents(
    data.agents
);


        }
        catch(error){

            console.debug(
                "Live Grid Agents:",
                error
            );

        }

    }


    /* ========================================================
       LOOP
       ======================================================== */

    async function poll(){

        await updateAgents();


        setTimeout(
            poll,
            POLL_MS
        );

    }


    function start(){

        setTimeout(
            poll,
            1200
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

/* END Grid MAP - LIVE AVATAR DOTS CLIENT V1 */

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
        "/Other/grid-map-data.php";


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
   Grid MAP - ONLINE NOW PANEL CLIENT V2
   ============================================================ */

(function(){

    const STORAGE_KEY =
        "AustraliaGridOnlineNowCollapsedV2";


    let panel = null;


    function esc(value){

        return String(
            value == null ? "" : value
        )
        .replace(/&/g,"&amp;")
        .replace(/</g,"&lt;")
        .replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;")
        .replace(/'/g,"&#039;");

    }


    function normalise(value){

        return String(
            value || ""
        )
        .trim()
        .replace(/\s+/g," ")
        .toLowerCase();

    }


    function signedInAvatar(){

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


        const body =
            String(
                document.body
                    ? document.body.innerText
                    : ""
            );


        const match =
            body.match(
                /Signed\s+in\s+as\s+([^\r\n]{1,100})/i
            );


        if(match){

            return String(
                match[1] || ""
            )
            .replace(
                /\s+(GRID OWNER|USER LEVEL|HOME|LOG OUT).*$/i,
                ""
            )
            .trim();

        }


        return "";

    }


    function findRegionTile(regionName){

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
            map.querySelectorAll(
                ".map-region"
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
                normalise(stored) === wanted
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


    function centreRegion(regionName){

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
                        regionName
                    );

            }

        }
        catch(error){

            console.debug(
                "Online Now centreRegion:",
                error
            );

        }


        setTimeout(
            function(){

                const tile =
                    findRegionTile(
                        regionName
                    );


                if(tile){

                    tile.click();

                }

            },
            120
        );

    }


    function updateCollapseButton(){

        if(!panel){
            return;
        }


        const button =
            panel.querySelector(
                ".gridmap-online-v2-collapse"
            );


        if(!button){
            return;
        }


        const collapsed =
            panel.classList.contains(
                "is-collapsed"
            );


        button.textContent =
            collapsed
                ? "\u25BC" : "\u25B2";


        button.title =
            collapsed
                ? "Expand Online Now"
                : "Collapse Online Now";

    }


    function ensurePanel(){

        const viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        if(!viewport){
            return null;
        }


        panel =
            document.getElementById(
                "gridmap-online-now-v2"
            );


        if(
            panel &&
            panel.isConnected
        ){

            return panel;

        }


        panel =
            document.createElement(
                "section"
            );


        panel.id =
            "gridmap-online-now-v2";


        panel.innerHTML = `

            <div class="gridmap-online-v2-header">

                <span
                    class="gridmap-online-v2-live">
                </span>

                <div
                    class="gridmap-online-v2-heading">

                    <span
                        class="gridmap-online-v2-title">
                        ONLINE NOW
                    </span>

                    <span
                        class="gridmap-online-v2-subtitle">
                        WAITING FOR LIVE DATA
                    </span>

                </div>

                <span
                    class="gridmap-online-v2-count">
                    0
                </span>

                <button
                    type="button"
                    class="gridmap-online-v2-collapse"
                    title="Collapse Online Now"><img class="ag-sentinel-direct-icon ag-sentinel-xs" src="/Other/assets/icons/sentinel/view.png" alt="" aria-hidden="true" draggable="false" decoding="async"></button>

            </div>

            <div
                class="gridmap-online-v2-body">

                <div
                    class="gridmap-online-v2-empty">
                    Waiting for live avatar data...
                </div>

            </div>

            <div
                class="gridmap-online-v2-footer">

                <span>
                    <strong>LIVE</strong>
                    &nbsp;OpenSim
                </span>

                <span
                    class="gridmap-online-v2-updated">
                    Waiting...
                </span>

            </div>

        `;


        viewport.appendChild(
            panel
        );


        const collapsed =
            localStorage.getItem(
                STORAGE_KEY
            ) === "1";


        panel.classList.toggle(
            "is-collapsed",
            collapsed
        );


        updateCollapseButton();


        const button =
            panel.querySelector(
                ".gridmap-online-v2-collapse"
            );


        if(button){

            button.addEventListener(
                "click",
                function(event){

                    event.preventDefault();
                    event.stopPropagation();


                    panel.classList.toggle(
                        "is-collapsed"
                    );


                    localStorage.setItem(
                        STORAGE_KEY,
                        panel.classList.contains(
                            "is-collapsed"
                        )
                            ? "1"
                            : "0"
                    );


                    updateCollapseButton();

                }
            );

        }


        /*
         * Prevent map pan/zoom from firing while interacting
         * with the ONLINE NOW panel.
         */

        panel.addEventListener(
            "pointerdown",
            function(event){

                event.stopPropagation();

            }
        );


        panel.addEventListener(
            "wheel",
            function(event){

                event.stopPropagation();

            },
            {
                passive:true
            }
        );


        return panel;

    }


    function currentTime(){

        return new Date()
            .toLocaleTimeString(
                "en-AU",
                {
                    hour:"numeric",
                    minute:"2-digit",
                    second:"2-digit"
                }
            );

    }


    function renderOnlineNow(data){

        const target =
            ensurePanel();


        if(
            !target ||
            !data ||
            !Array.isArray(data.agents)
        ){

            return;
        }


        const agents =
            data.agents.filter(
                agent =>
                    agent &&
                    agent.name &&
                    agent.region
            );


        const ownAvatar =
            normalise(
                signedInAvatar()
            );


        const groups =
            new Map();


        for(
            const agent of agents
        ){

            const region =
                String(
                    agent.region
                ).trim();


            if(
                !groups.has(region)
            ){

                groups.set(
                    region,
                    []
                );

            }


            groups
                .get(region)
                .push(agent);

        }


        const regions =
            Array.from(
                groups.keys()
            )
            .sort(
                (a,b) =>
                    a.localeCompare(
                        b,
                        undefined,
                        {
                            sensitivity:"base"
                        }
                    )
            );


        for(
            const region of regions
        ){

            groups
                .get(region)
                .sort(
                    (a,b) =>
                        String(a.name)
                        .localeCompare(
                            String(b.name),
                            undefined,
                            {
                                sensitivity:"base"
                            }
                        )
                );

        }


        const count =
            target.querySelector(
                ".gridmap-online-v2-count"
            );


        const subtitle =
            target.querySelector(
                ".gridmap-online-v2-subtitle"
            );


        const body =
            target.querySelector(
                ".gridmap-online-v2-body"
            );


        const updated =
            target.querySelector(
                ".gridmap-online-v2-updated"
            );


        if(count){

            count.textContent =
                String(
                    agents.length
                );

        }


        if(subtitle){

            if(
                regions.length === 0
            ){

                subtitle.textContent =
                    "NO ACTIVE REGIONS";

            }
            else if(
                regions.length === 1
            ){

                subtitle.textContent =
                    "1 ACTIVE REGION";

            }
            else{

                subtitle.textContent =
                    regions.length +
                    " ACTIVE REGIONS";

            }

        }


        if(updated){

            updated.textContent =
                "Updated " +
                currentTime();

        }


        if(!body){
            return;
        }


        if(
            agents.length === 0
        ){

            body.innerHTML = `

                <div
                    class="gridmap-online-v2-empty">

                    No avatars are currently
                    online on the grid.

                </div>

            `;


            return;

        }


        let html =
            "";


        for(
            const region of regions
        ){

            const regionAgents =
                groups.get(
                    region
                );


            html += `

                <div
                    class="gridmap-online-v2-region">

                    <button
                        type="button"
                        class="gridmap-online-v2-region-button"
                        data-online-region="${esc(region)}"
                        title="Centre map on ${esc(region)}">

                        <span
                            class="gridmap-online-v2-region-dot">
                        </span>

                        <span
                            class="gridmap-online-v2-region-name">
                            ${esc(region)}
                        </span>

                        <span
                            class="gridmap-online-v2-region-count">
                            ${regionAgents.length}
                        </span>

                    </button>

            `;


            for(
                const agent of regionAgents
            ){

                const isYou =
                    ownAvatar !== "" &&
                    normalise(
                        agent.name
                    ) === ownAvatar;


                html += `

                    <div
                        class="gridmap-online-v2-avatar">

                        <span
                            class="gridmap-online-v2-avatar-dot">
                        </span>

                        <span
                            class="gridmap-online-v2-avatar-name"
                            title="${esc(agent.name)}">

                            ${esc(agent.name)}

                        </span>

                        ${
                            isYou
                                ? `
                                    <span
                                        class="gridmap-online-v2-you">
                                        YOU
                                    </span>
                                  `
                                : ""
                        }

                    </div>

                `;

            }


            html += `

                </div>

            `;

        }


        body.innerHTML =
            html;


        body
            .querySelectorAll(
                ".gridmap-online-v2-region-button"
            )
            .forEach(
                button => {

                    button.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();
                            event.stopPropagation();


                            const region =
                                button.getAttribute(
                                    "data-online-region"
                                );


                            if(region){

                                centreRegion(
                                    region
                                );

                            }

                        }
                    );

                }
            );

    }


    function installOnlineNowV2(){

        ensurePanel();


        window.addEventListener(
            "australia-grid-live-agents-v2",
            function(event){

                if(
                    event &&
                    event.detail
                ){

                    renderOnlineNow(
                        event.detail
                    );

                }

            }
        );


        if(
            window.AustraliaGridLiveAgentsV2
        ){

            renderOnlineNow(
                window.AustraliaGridLiveAgentsV2
            );

        }

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            installOnlineNowV2
        );

    }
    else{

        installOnlineNowV2();

    }

})();

/* END Grid MAP - ONLINE NOW PANEL CLIENT V2 */

</script>


<script>

/* ============================================================
   Grid MAP - MAP LAYERS CLIENT V1
   ============================================================ */

(function(){

    const STORAGE_KEY =
        "AustraliaGridMapLayersV1";


    const defaultState = {

        regionCards:true,
        islands:true,
        you:true,
        avatars:true,
        coordinates:true,
        compass:true,
        online:true,
        gridLines:true,
        regionNames:true

    };


    let state =
        Object.assign(
            {},
            defaultState
        );


    let viewport =
        null;

    let control =
        null;


    /* ========================================================
       LOAD STORED SETTINGS
       ======================================================== */

    function loadState(){

        try{

            const saved =
                JSON.parse(
                    localStorage.getItem(
                        STORAGE_KEY
                    ) || "{}"
                );


            if(
                saved &&
                typeof saved === "object"
            ){

                state =
                    Object.assign(
                        {},
                        defaultState,
                        saved
                    );

            }

        }
        catch(error){

            state =
                Object.assign(
                    {},
                    defaultState
                );

        }

    }


    function saveState(){

        try{

            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(
                    state
                )
            );

        }
        catch(error){
        }

    }


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
       FIND / TAG ISLAND ELEMENTS
       ======================================================== */

    function tagIslandTargets(){

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
                element => {

                    if(
                        element.classList.contains(
                            "map-region"
                        ) ||
                        element.closest(
                            ".map-region"
                        )
                    ){

                        return;

                    }


                    const classText =
                        typeof element.className ===
                        "string"
                            ? element.className
                            : "";


                    const idText =
                        element.id || "";


                    const src =
                        element.getAttribute(
                            "src"
                        ) || "";


                    let background =
                        "";


                    try{

                        background =
                            getComputedStyle(
                                element
                            ).backgroundImage || "";

                    }
                    catch(error){
                    }


                    const haystack =
                        (
                            classText +
                            " " +
                            idText +
                            " " +
                            src +
                            " " +
                            background
                        )
                        .toLowerCase();


                    if(
                        haystack.includes(
                            "island-small"
                        ) ||
                        haystack.includes(
                            "island-medium"
                        ) ||
                        haystack.includes(
                            "island-large"
                        ) ||
                        /(^|[\s_-])region[\s_-]*island($|[\s_-])/.test(
                            haystack
                        ) ||
                        /(^|[\s_-])map[\s_-]*island($|[\s_-])/.test(
                            haystack
                        )
                    ){

                        element.classList.add(
                            "australia-layer-island-target"
                        );

                    }

                }
            );

    }


    /* ========================================================
       TAG MAP GRID LINE ELEMENTS

       Does not touch coordinate-scale lines because those
       already have their own separate layer switch.
       ======================================================== */

    function tagGridLineTargets(){

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
                element => {

                    if(
                        element.closest(
                            "#gridmap-coordinate-scale"
                        ) ||
                        element.closest(
                            ".map-region"
                        )
                    ){

                        return;

                    }


                    const classText =
                        typeof element.className ===
                        "string"
                            ? element.className
                            : "";


                    const idText =
                        element.id || "";


                    const token =
                        (
                            classText +
                            " " +
                            idText
                        )
                        .toLowerCase();


                    if(
                        token.includes(
                            "grid-line"
                        ) ||
                        token.includes(
                            "grid_line"
                        ) ||
                        token.includes(
                            "gridline"
                        ) ||
                        token.includes(
                            "map-grid-line"
                        ) ||
                        token.includes(
                            "grid-overlay"
                        )
                    ){

                        element.classList.add(
                            "australia-layer-grid-line-target"
                        );

                    }

                }
            );

    }


    /* ========================================================
       TAG REGION NAME ELEMENTS
       ======================================================== */

    function tagRegionNameTargets(){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){
            return;
        }


        map
            .querySelectorAll(
                ".map-region"
            )
            .forEach(
                tile => {

                    /*
                     * First try common title/name classes.
                     */

                    const common =
                        tile.querySelectorAll(
                            ".region-name," +
                            ".map-region-name," +
                            ".region-title," +
                            ".map-region-title"
                        );


                    if(
                        common.length
                    ){

                        common.forEach(
                            element =>
                                element.classList.add(
                                    "australia-layer-region-name-target"
                                )
                        );

                        return;

                    }


                    let regionName =
                        tile.dataset.regionName ||
                        tile.dataset.region ||
                        tile.dataset.name ||
                        tile.getAttribute(
                            "data-region-name"
                        ) ||
                        "";


                    /*
                     * Fallback:
                     * first visible line of the card is normally
                     * the region name.
                     */

                    if(
                        !regionName
                    ){

                        const lines =
                            String(
                                tile.innerText ||
                                tile.textContent ||
                                ""
                            )
                            .split(
                                /\r?\n/
                            )
                            .map(
                                line =>
                                    line.trim()
                            )
                            .filter(
                                Boolean
                            );


                        if(
                            lines.length
                        ){

                            regionName =
                                lines[0];

                        }

                    }


                    if(
                        !regionName
                    ){

                        return;

                    }


                    const wanted =
                        normalise(
                            regionName
                        );


                    const descendants =
                        Array.from(
                            tile.querySelectorAll(
                                "*"
                            )
                        );


                    for(
                        const element
                        of descendants
                    ){

                        /*
                         * Prefer leaf nodes to avoid hiding the
                         * whole card accidentally.
                         */

                        if(
                            element.children.length >
                            0
                        ){

                            continue;

                        }


                        if(
                            normalise(
                                element.textContent
                            ) === wanted
                        ){

                            element.classList.add(
                                "australia-layer-region-name-target"
                            );

                            break;

                        }

                    }

                }
            );

    }


    /* ========================================================
       TAG ALL OPTIONAL TARGETS
       ======================================================== */

    function discoverTargets(){

        tagIslandTargets();

        tagGridLineTargets();

        tagRegionNameTargets();

        updateAvailability();

    }


    /* ========================================================
       APPLY VISIBILITY
       ======================================================== */

    function applyState(){

        if(!viewport){
            return;
        }


        viewport.classList.toggle(
            "australia-layer-hide-region-cards",
            !state.regionCards
        );


        viewport.classList.toggle(
            "australia-layer-hide-islands",
            !state.islands
        );


        viewport.classList.toggle(
            "australia-layer-hide-you",
            !state.you
        );


        viewport.classList.toggle(
            "australia-layer-hide-avatars",
            !state.avatars
        );


        viewport.classList.toggle(
            "australia-layer-hide-coordinates",
            !state.coordinates
        );


        viewport.classList.toggle(
            "australia-layer-hide-compass",
            !state.compass
        );


        viewport.classList.toggle(
            "australia-layer-hide-online",
            !state.online
        );


        viewport.classList.toggle(
            "australia-layer-hide-grid-lines",
            !state.gridLines
        );


        viewport.classList.toggle(
            "australia-layer-hide-region-names",
            !state.regionNames
        );


        updateRows();

    }


    /* ========================================================
       SWITCH ROWS
       ======================================================== */

    function updateRows(){

        if(!control){
            return;
        }


        control
            .querySelectorAll(
                "[data-layer-key]"
            )
            .forEach(
                row => {

                    const key =
                        row.getAttribute(
                            "data-layer-key"
                        );


                    row.classList.toggle(
                        "is-on",
                        Boolean(
                            state[key]
                        )
                    );

                }
            );

    }


    /* ========================================================
       AVAILABILITY

       Grid lines / island / region-name internals can differ
       between map revisions. The switch is dimmed only if
       there is no safe dedicated DOM target to control.
       ======================================================== */

    function setAvailability(
        key,
        available
    ){

        if(!control){
            return;
        }


        const row =
            control.querySelector(
                '[data-layer-key="' +
                key +
                '"]'
            );


        if(!row){
            return;
        }


        row.classList.toggle(
            "is-unavailable",
            !available
        );


        row.dataset.available =
            available
                ? "1"
                : "0";

    }


    function updateAvailability(){

        if(!control){
            return;
        }


        setAvailability(
            "regionCards",
            document.querySelector(
                "#grid-map .map-region"
            ) !== null
        );


        setAvailability(
            "islands",
            document.querySelector(
                ".australia-layer-island-target"
            ) !== null
        );


        setAvailability(
            "you",
            document.getElementById(
                "australia-you-are-here"
            ) !== null
        );


        /*
         * Avatar markers may legitimately be absent when
         * nobody else is online, so keep this switch enabled.
         */

        setAvailability(
            "avatars",
            true
        );


        setAvailability(
            "coordinates",
            document.getElementById(
                "gridmap-coordinate-scale"
            ) !== null
        );


        setAvailability(
            "compass",
            document.getElementById(
                "gridmap-compass"
            ) !== null
        );


        setAvailability(
            "online",
            document.getElementById(
                "gridmap-online-now-v2"
            ) !== null ||
            document.getElementById(
                "gridmap-online-now"
            ) !== null
        );


        setAvailability(
            "gridLines",
            document.querySelector(
                ".australia-layer-grid-line-target"
            ) !== null
        );


        setAvailability(
            "regionNames",
            document.querySelector(
                ".australia-layer-region-name-target"
            ) !== null
        );

    }


    /* ========================================================
       CREATE CONTROL
       ======================================================== */

    function ensureControl(){

        if(!viewport){
            return null;
        }


        control =
            document.getElementById(
                "gridmap-layers-v1"
            );


        if(
            control &&
            control.isConnected
        ){

            return control;

        }


        control =
            document.createElement(
                "div"
            );


        control.id =
            "gridmap-layers-v1";


        control.innerHTML = `

            <div
                id="gridmap-layers-menu-v1">

                <div
                    class="gridmap-layers-header-v1"><div
                        class="gridmap-layers-header-copy-v1">

                        <span
                            class="gridmap-layers-title-v1">
                            MAP LAYERS
                        </span>

                        <span
                            class="gridmap-layers-subtitle-v1">
                            SHOW OR HIDE MAP OVERLAYS
                        </span>

                    </div>

                </div>


                <div
                    class="gridmap-layer-row-v1"
                    data-layer-key="regionCards">

                    <div
                        class="gridmap-layer-copy-v1">

                        <span
                            class="gridmap-layer-name-v1">
                            Region Cards
                        </span>

                        <span
                            class="gridmap-layer-note-v1">
                            Region information boxes
                        </span>

                    </div>

                    <span
                        class="gridmap-layer-switch-v1">
                    </span>

                </div>


                <div
                    class="gridmap-layer-row-v1"
                    data-layer-key="islands">

                    <div
                        class="gridmap-layer-copy-v1">

                        <span
                            class="gridmap-layer-name-v1">
                            Island Images
                        </span>

                        <span
                            class="gridmap-layer-note-v1">
                            Sand and island graphics
                        </span>

                    </div>

                    <span
                        class="gridmap-layer-switch-v1">
                    </span>

                </div>


                <div
                    class="gridmap-layer-row-v1"
                    data-layer-key="you">

                    <div
                        class="gridmap-layer-copy-v1">

                        <span
                            class="gridmap-layer-name-v1">
                            YOU ARE HERE
                        </span>

                        <span
                            class="gridmap-layer-note-v1">
                            Your live gold marker
                        </span>

                    </div>

                    <span
                        class="gridmap-layer-switch-v1">
                    </span>

                </div>


                <div
                    class="gridmap-layer-row-v1"
                    data-layer-key="avatars">

                    <div
                        class="gridmap-layer-copy-v1">

                        <span
                            class="gridmap-layer-name-v1">
                            Avatar Dots
                        </span>

                        <span
                            class="gridmap-layer-note-v1">
                            Other live avatars
                        </span>

                    </div>

                    <span
                        class="gridmap-layer-switch-v1">
                    </span>

                </div>


                <div
                    class="gridmap-layer-row-v1"
                    data-layer-key="coordinates">

                    <div
                        class="gridmap-layer-copy-v1">

                        <span
                            class="gridmap-layer-name-v1">
                            Coordinate Scale
                        </span>

                        <span
                            class="gridmap-layer-note-v1">
                            OpenSim X / Y edge scale
                        </span>

                    </div>

                    <span
                        class="gridmap-layer-switch-v1">
                    </span>

                </div>


                <div
                    class="gridmap-layer-row-v1"
                    data-layer-key="compass">

                    <div
                        class="gridmap-layer-copy-v1">

                        <span
                            class="gridmap-layer-name-v1">
                            Compass
                        </span>

                        <span
                            class="gridmap-layer-note-v1">
                            Fixed grid north compass
                        </span>

                    </div>

                    <span
                        class="gridmap-layer-switch-v1">
                    </span>

                </div>


                <div
                    class="gridmap-layer-row-v1"
                    data-layer-key="online">

                    <div
                        class="gridmap-layer-copy-v1">

                        <span
                            class="gridmap-layer-name-v1">
                            ONLINE NOW
                        </span>

                        <span
                            class="gridmap-layer-note-v1">
                            Live avatar list
                        </span>

                    </div>

                    <span
                        class="gridmap-layer-switch-v1">
                    </span>

                </div>


                <div
                    class="gridmap-layer-row-v1"
                    data-layer-key="gridLines">

                    <div
                        class="gridmap-layer-copy-v1">

                        <span
                            class="gridmap-layer-name-v1">
                            Grid Lines
                        </span>

                        <span
                            class="gridmap-layer-note-v1">
                            Map grid overlay
                        </span>

                    </div>

                    <span
                        class="gridmap-layer-switch-v1">
                    </span>

                </div>


                <div
                    class="gridmap-layer-row-v1"
                    data-layer-key="regionNames">

                    <div
                        class="gridmap-layer-copy-v1">

                        <span
                            class="gridmap-layer-name-v1">
                            Region Names
                        </span>

                        <span
                            class="gridmap-layer-note-v1">
                            Names inside region cards
                        </span>

                    </div>

                    <span
                        class="gridmap-layer-switch-v1">
                    </span>

                </div>


                <div
                    class="gridmap-layers-footer-v1">

                    <button
                        type="button"
                        class="gridmap-layers-reset-v1">

                        SHOW ALL LAYERS

                    </button>

                </div>

            </div>


            <button
                type="button"
                id="gridmap-layers-button-v1">

                <span
                    class="gridmap-layers-icon-v1"><img class="ag-sentinel-direct-icon ag-sentinel-xs" src="/Other/assets/icons/sentinel/map.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

                <span>
                    LAYERS
                </span>

            </button>

        `;


        viewport.appendChild(
            control
        );


        /* ====================================================
           OPEN / CLOSE
           ==================================================== */

        const button =
            control.querySelector(
                "#gridmap-layers-button-v1"
            );


        if(button){

            button.addEventListener(
                "click",
                function(event){

                    event.preventDefault();

                    event.stopPropagation();


                    control.classList.toggle(
                        "is-open"
                    );

                }
            );

        }


        /* ====================================================
           LAYER SWITCHES
           ==================================================== */

        control
            .querySelectorAll(
                "[data-layer-key]"
            )
            .forEach(
                row => {

                    row.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();

                            event.stopPropagation();


                            if(
                                row.dataset.available ===
                                "0"
                            ){

                                return;

                            }


                            const key =
                                row.getAttribute(
                                    "data-layer-key"
                                );


                            if(
                                !Object.prototype
                                    .hasOwnProperty
                                    .call(
                                        state,
                                        key
                                    )
                            ){

                                return;

                            }


                            state[key] =
                                !state[key];


                            saveState();

                            applyState();

                        }
                    );

                }
            );


        /* ====================================================
           SHOW ALL
           ==================================================== */

        const reset =
            control.querySelector(
                ".gridmap-layers-reset-v1"
            );


        if(reset){

            reset.addEventListener(
                "click",
                function(event){

                    event.preventDefault();

                    event.stopPropagation();


                    state =
                        Object.assign(
                            {},
                            defaultState
                        );


                    saveState();

                    applyState();

                }
            );

        }


        /*
         * Do not allow the map pan engine to grab pointer
         * movement while operating the menu.
         */

        control.addEventListener(
            "pointerdown",
            function(event){

                event.stopPropagation();

            }
        );


        control.addEventListener(
            "pointermove",
            function(event){

                event.stopPropagation();

            }
        );


        control.addEventListener(
            "wheel",
            function(event){

                event.stopPropagation();

            },
            {
                passive:true
            }
        );


        return control;

    }


    /* ========================================================
       CLOSE MENU WHEN CLICKING ELSEWHERE
       ======================================================== */

    function installOutsideClose(){

        document.addEventListener(
            "pointerdown",
            function(event){

                if(
                    !control ||
                    !control.classList.contains(
                        "is-open"
                    )
                ){

                    return;

                }


                if(
                    control.contains(
                        event.target
                    )
                ){

                    return;

                }


                control.classList.remove(
                    "is-open"
                );

            }
        );

    }


    /* ========================================================
       WATCH MAP REBUILDS

       Region refreshes may recreate islands/cards.
       Re-discover them automatically.
       ======================================================== */

    function installObserver(){

        const map =
            document.getElementById(
                "grid-map"
            );


        if(!map){
            return;
        }


        let timer =
            null;


        const observer =
            new MutationObserver(
                function(){

                    clearTimeout(
                        timer
                    );


                    timer =
                        setTimeout(
                            function(){

                                discoverTargets();

                                applyState();

                            },
                            120
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

    }


    /* ========================================================
       INSTALL
       ======================================================== */

    function installMapLayersV1(){

        viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        if(!viewport){
            return;
        }


        loadState();

        ensureControl();

        discoverTargets();

        applyState();

        installObserver();

        installOutsideClose();


        /*
         * Some overlays are created shortly after DOM ready.
         */

        setTimeout(
            function(){

                discoverTargets();

                applyState();

            },
            750
        );


        setTimeout(
            function(){

                discoverTargets();

                applyState();

            },
            1800
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            installMapLayersV1
        );

    }
    else{

        installMapLayersV1();

    }

})();

/* END Grid MAP - MAP LAYERS CLIENT V1 */

</script>


<script>

/* ============================================================
   Grid MAP - REGION TRAFFIC HEATMAP CLIENT V1
   ============================================================ */

(function(){

    const TOKEN =
        "ea923d827cf9a1d00fd15eb7c38e93751148da69ac744d27f61b99b430f7ac70";


    const API =
        "/Other/grid-traffic.php";


    const STORAGE_WINDOW =
        "AustraliaGridTrafficWindowV1";


    const STORAGE_ENABLED =
        "AustraliaGridTrafficEnabledV1";


    const SAMPLE_MS =
        60000;


    const SUMMARY_MS =
        60000;


    const WINDOWS = {

        3600:
            "1H",

        86400:
            "24H",

        604800:
            "7D",

        2592000:
            "30D"

    };


    let viewport =
        null;

    let map =
        null;

    let control =
        null;

    let heatLayer =
        null;


    let selectedWindow =
        Number(
            localStorage.getItem(
                STORAGE_WINDOW
            ) || 86400
        );


    if(
        !WINDOWS[
            selectedWindow
        ]
    ){

        selectedWindow =
            86400;
    }


    let enabled =
        localStorage.getItem(
            STORAGE_ENABLED
        ) !== "0";


    let lastPost =
        0;


    let posting =
        false;


    let latestSummary =
        null;


    /* ========================================================
       ESCAPE
       ======================================================== */

    function esc(value){

        return String(
            value == null
                ? ""
                : value
        )
        .replace(/&/g,"&amp;")
        .replace(/</g,"&lt;")
        .replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;")
        .replace(/'/g,"&#039;");

    }


    function normalise(value){

        return String(
            value || ""
        )
        .trim()
        .replace(/\s+/g," ")
        .toLowerCase();

    }


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


            if(
                n !== null
            ){

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
            map.querySelectorAll(
                ".map-region"
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
       TRUE REGION GEOMETRY
       ======================================================== */

    function regionGeometry(
        tile
    ){

        if(!tile){
            return null;
        }


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
            height === null
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
       HEAT LAYER
       ======================================================== */

    function ensureHeatLayer(){

        if(!map){
            return null;
        }


        heatLayer =
            document.getElementById(
                "gridmap-traffic-heat-layer-v1"
            );


        if(
            heatLayer &&
            heatLayer.isConnected
        ){

            return heatLayer;
        }


        heatLayer =
            document.createElement(
                "div"
            );


        heatLayer.id =
            "gridmap-traffic-heat-layer-v1";


        map.appendChild(
            heatLayer
        );


        return heatLayer;

    }


    function clearHeat(){

        const layer =
            ensureHeatLayer();


        if(layer){

            layer.innerHTML =
                "";

        }

    }


    /* ========================================================
       HEAT LEVEL
       ======================================================== */

    function heatLevel(
        score,
        maximum
    ){

        if(
            maximum <= 0 ||
            score <= 0
        ){

            return 0;
        }


        const ratio =
            score /
            maximum;


        if(
            ratio <= .20
        ){
            return 1;
        }


        if(
            ratio <= .40
        ){
            return 2;
        }


        if(
            ratio <= .60
        ){
            return 3;
        }


        if(
            ratio <= .80
        ){
            return 4;
        }


        return 5;
    }


    /* ========================================================
       RENDER HEATMAP
       ======================================================== */

    function renderHeatmap(
        summary
    ){

        clearHeat();


        if(
            !enabled ||
            !summary ||
            !Array.isArray(
                summary.regions
            )
        ){

            return;
        }


        const active =
            summary.regions.filter(
                region =>
                    Number(
                        region.avg_online || 0
                    ) > 0
            );


        if(
            !active.length
        ){

            return;
        }


        let maximum =
            0;


        for(
            const region of active
        ){

            maximum =
                Math.max(
                    maximum,
                    Number(
                        region.avg_online || 0
                    )
                );

        }


        const layer =
            ensureHeatLayer();


        if(!layer){
            return;
        }


        for(
            const region of active
        ){

            const tile =
                findRegionTile(
                    region.region
                );


            const geometry =
                regionGeometry(
                    tile
                );


            if(!geometry){
                continue;
            }


            const level =
                heatLevel(
                    Number(
                        region.avg_online || 0
                    ),
                    maximum
                );


            if(
                level <= 0
            ){

                continue;
            }


            const heat =
                document.createElement(
                    "div"
                );


            heat.className =
                "australia-grid-traffic-heat-v1 level-" +
                level;


            heat.style.left =
                geometry.left +
                "px";


            heat.style.top =
                geometry.top +
                "px";


            heat.style.width =
                geometry.width +
                "px";


            heat.style.height =
                geometry.height +
                "px";


            layer.appendChild(
                heat
            );

        }

    }


    /* ========================================================
       CENTRE REGION
       ======================================================== */

    function centreRegion(
        regionName
    ){

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
                        regionName
                    );

            }

        }
        catch(error){
        }


        setTimeout(
            function(){

                const tile =
                    findRegionTile(
                        regionName
                    );


                if(tile){

                    tile.click();

                }

            },
            120
        );

    }


    /* ========================================================
       PANEL
       ======================================================== */

    function ensureControl(){

        if(!viewport){
            return null;
        }


        control =
            document.getElementById(
                "gridmap-traffic-control-v1"
            );


        if(
            control &&
            control.isConnected
        ){

            return control;
        }


        control =
            document.createElement(
                "div"
            );


        control.id =
            "gridmap-traffic-control-v1";


        control.innerHTML = `

            <div
                id="gridmap-traffic-panel-v1">

                <div
                    class="gridmap-traffic-header-v1">

                    <span
                        class="gridmap-traffic-title-v1">
                        REGION TRAFFIC
                    </span>

                    <span
                        class="gridmap-traffic-subtitle-v1">
                        HISTORICAL AVATAR HEATMAP
                    </span>

                </div>


                <div
                    class="gridmap-traffic-windows-v1">

                    <button
                        type="button"
                        class="gridmap-traffic-window-v1"
                        data-window="3600">
                        1H
                    </button>

                    <button
                        type="button"
                        class="gridmap-traffic-window-v1"
                        data-window="86400">
                        24H
                    </button>

                    <button
                        type="button"
                        class="gridmap-traffic-window-v1"
                        data-window="604800">
                        7D
                    </button>

                    <button
                        type="button"
                        class="gridmap-traffic-window-v1"
                        data-window="2592000">
                        30D
                    </button>

                    <button
                        type="button"
                        class="gridmap-traffic-window-v1 is-off"
                        data-window="off">
                        OFF
                    </button>

                </div>


                <div
                    class="gridmap-traffic-legend-v1">

                    <span>
                        LOW
                    </span>

                    <span
                        class="gridmap-traffic-legend-block-v1 l1">
                    </span>

                    <span
                        class="gridmap-traffic-legend-block-v1 l2">
                    </span>

                    <span
                        class="gridmap-traffic-legend-block-v1 l3">
                    </span>

                    <span
                        class="gridmap-traffic-legend-block-v1 l4">
                    </span>

                    <span
                        class="gridmap-traffic-legend-block-v1 l5">
                    </span>

                    <span>
                        BUSIEST
                    </span>

                </div>


                <div
                    class="gridmap-traffic-summary-v1">

                    <span
                        id="gridmap-traffic-samples-v1">
                        0 samples
                    </span>

                    <span
                        id="gridmap-traffic-range-v1">
                        BUILDING HISTORY
                    </span>

                </div>


                <div
                    id="gridmap-traffic-ranking-v1"
                    class="gridmap-traffic-ranking-v1">

                    <div
                        class="gridmap-traffic-empty-v1">

                        Traffic history will begin
                        building automatically.

                    </div>

                </div>


                <div
                    class="gridmap-traffic-footer-v1">

                    AVG = average avatars online.
                    PEAK = highest number seen at once.
                    UNIQUE = anonymous unique visitors.

                </div>

            </div>


            <button
                type="button"
                id="gridmap-traffic-button-v1">

                <span
                    class="gridmap-traffic-flame-v1"><img class="ag-sentinel-direct-icon ag-sentinel-xs" src="/Other/assets/icons/sentinel/statistics.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

                <span>
                    TRAFFIC
                </span>

                <span
                    class="gridmap-traffic-mode-v1">
                    24H
                </span>

            </button>

        `;


        viewport.appendChild(
            control
        );


        const button =
            control.querySelector(
                "#gridmap-traffic-button-v1"
            );


        if(button){

            button.addEventListener(
                "click",
                function(event){

                    event.preventDefault();

                    event.stopPropagation();


                    const layers =
                        document.getElementById(
                            "gridmap-layers-v1"
                        );


                    if(layers){

                        layers.classList.remove(
                            "is-open"
                        );

                    }


                    control.classList.toggle(
                        "is-open"
                    );

                }
            );

        }


        control
            .querySelectorAll(
                "[data-window]"
            )
            .forEach(
                button => {

                    button.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();

                            event.stopPropagation();


                            const value =
                                button.getAttribute(
                                    "data-window"
                                );


                            if(
                                value ===
                                "off"
                            ){

                                enabled =
                                    false;


                                localStorage.setItem(
                                    STORAGE_ENABLED,
                                    "0"
                                );


                                clearHeat();

                                updateControls();

                                return;

                            }


                            const seconds =
                                Number(
                                    value
                                );


                            if(
                                !WINDOWS[
                                    seconds
                                ]
                            ){

                                return;

                            }


                            selectedWindow =
                                seconds;


                            enabled =
                                true;


                            localStorage.setItem(
                                STORAGE_WINDOW,
                                String(
                                    selectedWindow
                                )
                            );


                            localStorage.setItem(
                                STORAGE_ENABLED,
                                "1"
                            );


                            updateControls();

                            fetchSummary();

                        }
                    );

                }
            );


        control.addEventListener(
            "pointerdown",
            function(event){

                event.stopPropagation();

            }
        );


        control.addEventListener(
            "pointermove",
            function(event){

                event.stopPropagation();

            }
        );


        control.addEventListener(
            "wheel",
            function(event){

                event.stopPropagation();

            },
            {
                passive:true
            }
        );


        updateControls();


        return control;
    }


    /* ========================================================
       CONTROL STATE
       ======================================================== */

    function updateControls(){

        if(!control){
            return;
        }


        control
            .querySelectorAll(
                "[data-window]"
            )
            .forEach(
                button => {

                    const value =
                        button.getAttribute(
                            "data-window"
                        );


                    let active =
                        false;


                    if(
                        value === "off"
                    ){

                        active =
                            !enabled;

                    }
                    else{

                        active =
                            enabled &&
                            Number(value) ===
                            selectedWindow;

                    }


                    button.classList.toggle(
                        "is-active",
                        active
                    );

                }
            );


        const mode =
            control.querySelector(
                ".gridmap-traffic-mode-v1"
            );


        if(mode){

            mode.textContent =
                enabled
                    ? WINDOWS[
                        selectedWindow
                      ]
                    : "OFF";

        }

    }


    /* ========================================================
       RENDER RANKING
       ======================================================== */

    function renderRanking(
        summary
    ){

        latestSummary =
            summary;


        renderHeatmap(
            summary
        );


        if(!control){
            return;
        }


        const ranking =
            control.querySelector(
                "#gridmap-traffic-ranking-v1"
            );


        const samples =
            control.querySelector(
                "#gridmap-traffic-samples-v1"
            );


        const range =
            control.querySelector(
                "#gridmap-traffic-range-v1"
            );


        if(samples){

            samples.textContent =
                String(
                    Number(
                        summary.samples || 0
                    )
                ) +
                " samples";

        }


        if(range){

            range.textContent =
                enabled
                    ? WINDOWS[
                        selectedWindow
                      ] +
                      " WINDOW"
                    : "HEATMAP OFF";

        }


        if(!ranking){
            return;
        }


        const regions =
            Array.isArray(
                summary.regions
            )
                ? summary.regions
                : [];


        if(
            !regions.length
        ){

            ranking.innerHTML = `

                <div
                    class="gridmap-traffic-empty-v1">

                    BUILDING TRAFFIC HISTORY<br><br>

                    The first region samples
                    are being recorded now.

                </div>

            `;


            return;
        }


        let html =
            "";


        regions
            .slice(
                0,
                20
            )
            .forEach(
                function(
                    region,
                    index
                ){

                    const average =
                        Number(
                            region.avg_online || 0
                        );


                    const peak =
                        Number(
                            region.max_online || 0
                        );


                    const unique =
                        Number(
                            region.unique_count || 0
                        );


                    const active =
                        Number(
                            region.active_percent || 0
                        );


                    html += `

                        <button
                            type="button"
                            class="gridmap-traffic-row-v1"
                            data-traffic-region="${esc(region.region)}">

                            <span
                                class="gridmap-traffic-rank-v1">

                                #${index + 1}

                            </span>

                            <span
                                class="gridmap-traffic-row-copy-v1">

                                <span
                                    class="gridmap-traffic-region-v1">

                                    ${esc(region.region)}

                                </span>

                                <span
                                    class="gridmap-traffic-meta-v1">
                            PEAK ${peak} &#8226; UNIQUE ${unique} &#8226; ACTIVE ${active.toFixed(0)}%
                        </span>

                            </span>

                            <span
                                class="gridmap-traffic-avg-v1">

                                ${average.toFixed(2)}
                                AVG

                            </span>

                        </button>

                    `;

                }
            );


        ranking.innerHTML =
            html;


        ranking
            .querySelectorAll(
                "[data-traffic-region]"
            )
            .forEach(
                button => {

                    button.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();

                            event.stopPropagation();


                            const region =
                                button.getAttribute(
                                    "data-traffic-region"
                                );


                            if(region){

                                centreRegion(
                                    region
                                );

                            }

                        }
                    );

                }
            );

    }


    /* ========================================================
       POST EXISTING LIVE FEED
       ======================================================== */

    async function postSample(
        data
    ){

        if(
            posting ||
            !data ||
            !Array.isArray(
                data.agents
            )
        ){

            return;
        }


        const now =
            Date.now();


        if(
            now -
            lastPost <
            SAMPLE_MS
        ){

            return;
        }


        lastPost =
            now;


        posting =
            true;


        try{

            const params =
                new URLSearchParams();


            params.set(
                "token",
                TOKEN
            );


            params.set(
                "nocache",
                String(
                    Date.now()
                )
            );


            const response =
                await fetch(
                    API +
                    "?" +
                    params.toString(),
                    {
                        method:
                            "POST",

                        credentials:
                            "same-origin",

                        cache:
                            "no-store",

                        headers:{
                            "Content-Type":
                                "application/json"
                        },

                        body:
                            JSON.stringify(
                                {
                                    agents:
                                        data.agents
                                }
                            )
                    }
                );


            const result =
                await response.json();


            if(
                result &&
                result.ok &&
                enabled
            ){

                setTimeout(
                    fetchSummary,
                    250
                );

            }

        }
        catch(error){

            console.debug(
                "Traffic sample:",
                error
            );

        }
        finally{

            posting =
                false;

        }

    }


    /* ========================================================
       FETCH SUMMARY
       ======================================================== */

    async function fetchSummary(){

        if(
            !enabled
        ){

            return;
        }


        try{

            const params =
                new URLSearchParams();


            params.set(
                "token",
                TOKEN
            );


            params.set(
                "window",
                String(
                    selectedWindow
                )
            );


            params.set(
                "nocache",
                String(
                    Date.now()
                )
            );


            const response =
                await fetch(
                    API +
                    "?" +
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


            renderRanking(
                data
            );

        }
        catch(error){

            console.debug(
                "Traffic summary:",
                error
            );

        }

    }


    /* ========================================================
       LIVE FEED EVENT
       ======================================================== */

    function liveAgentUpdate(
        event
    ){

        if(
            !event ||
            !event.detail
        ){

            return;
        }


        postSample(
            event.detail
        );

    }


    /* ========================================================
       MAP REBUILD
       ======================================================== */

    function watchMap(){

        if(!map){
            return;
        }


        let timer =
            null;


        const observer =
            new MutationObserver(
                function(){

                    clearTimeout(
                        timer
                    );


                    timer =
                        setTimeout(
                            function(){

                                if(
                                    latestSummary
                                ){

                                    renderHeatmap(
                                        latestSummary
                                    );

                                }

                            },
                            160
                        );

                }
            );


        observer.observe(
            map,
            {
                childList:true,
                subtree:false
            }
        );

    }


    /* ========================================================
       OUTSIDE CLOSE
       ======================================================== */

    function outsideClose(){

        document.addEventListener(
            "pointerdown",
            function(event){

                if(
                    !control ||
                    !control.classList.contains(
                        "is-open"
                    )
                ){

                    return;
                }


                if(
                    control.contains(
                        event.target
                    )
                ){

                    return;
                }


                control.classList.remove(
                    "is-open"
                );

            }
        );

    }


    /* ========================================================
       INSTALL
       ======================================================== */

    function installTrafficHeatmapV1(){

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


        ensureHeatLayer();

        ensureControl();

        watchMap();

        outsideClose();


        window.addEventListener(
            "australia-grid-live-agents-v2",
            liveAgentUpdate
        );


        /*
         * The existing feed may already have completed before
         * this script started.
         */

        if(
            window.AustraliaGridLiveAgentsV2
        ){

            postSample(
                window.AustraliaGridLiveAgentsV2
            );

        }


        if(enabled){

            fetchSummary();

        }


        setInterval(
            function(){

                if(enabled){

                    fetchSummary();

                }

            },
            SUMMARY_MS
        );


        document.addEventListener(
            "fullscreenchange",
            function(){

                if(
                    latestSummary
                ){

                    setTimeout(
                        function(){

                            renderHeatmap(
                                latestSummary
                            );

                        },
                        100
                    );

                }

            }
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            installTrafficHeatmapV1
        );

    }
    else{

        installTrafficHeatmapV1();

    }

})();

/* END Grid MAP - REGION TRAFFIC HEATMAP CLIENT V1 */

</script>


<script>

/* ============================================================
   Grid MAP - REGION UPTIME CLIENT V1
   ============================================================ */

(function(){

    const TOKEN =
        "60d9cfcdfd4cc73ed97f34f57061bb2a1a1bf14be33b0b4885051014f0e15b4d";


    const API =
        "/Other/grid-uptime.php";


    const REGIONS_API =
        "/Other/regions.php";


    const STORAGE_WINDOW =
        "AustraliaGridUptimeWindowV1";


    const SAMPLE_MS =
        60000;


    const SUMMARY_MS =
        60000;


    const WINDOWS = {

        3600:"1H",
        86400:"24H",
        604800:"7D",
        2592000:"30D"

    };


    let selectedWindow =
        Number(
            localStorage.getItem(
                STORAGE_WINDOW
            ) || 86400
        );


    if(
        !WINDOWS[
            selectedWindow
        ]
    ){

        selectedWindow =
            86400;
    }


    let viewport =
        null;

    let control =
        null;

    let posting =
        false;


    function esc(value){

        return String(
            value == null
                ? ""
                : value
        )
        .replace(/&/g,"&amp;")
        .replace(/</g,"&lt;")
        .replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;")
        .replace(/'/g,"&#039;");

    }


    function normalise(value){

        return String(
            value || ""
        )
        .trim()
        .replace(/\s+/g," ")
        .toLowerCase();

    }


    /* ========================================================
       REGION ARRAY FROM regions.php
       ======================================================== */

    function extractRegions(data){

        if(
            Array.isArray(
                data
            )
        ){

            return data;
        }


        if(
            data &&
            Array.isArray(
                data.regions
            )
        ){

            return data.regions;
        }


        if(
            data &&
            Array.isArray(
                data.data
            )
        ){

            return data.data;
        }


        return [];

    }


    function regionName(region){

        return String(
            region.RegionName ||
            region.regionName ||
            region.region_name ||
            region.Name ||
            region.name ||
            ""
        ).trim();

    }


    function regionStatus(region){

        return String(
            region.EffectiveStatus ||
            region.effectiveStatus ||
            region.effective_status ||
            region.Status ||
            region.status ||
            region.State ||
            region.state ||
            "Unknown"
        ).trim();

    }


    /* ========================================================
       RECORD STATUS SNAPSHOT
       ======================================================== */

    async function recordStatus(){

        if(posting){
            return;
        }


        posting =
            true;


        try{

            const response =
                await fetch(
                    REGIONS_API +
                    "?uptime=1&nocache=" +
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


            const rawRegions =
                extractRegions(
                    data
                );


            const regions =
                [];


            for(
                const region of rawRegions
            ){

                const name =
                    regionName(
                        region
                    );


                if(!name){
                    continue;
                }


                regions.push(
                    {
                        name:name,
                        status:
                            regionStatus(
                                region
                            )
                    }
                );

            }


            if(
                !regions.length
            ){

                console.debug(
                    "Region Uptime: regions.php returned no usable regions."
                );

                return;
            }


            const params =
                new URLSearchParams();


            params.set(
                "token",
                TOKEN
            );


            params.set(
                "nocache",
                String(
                    Date.now()
                )
            );


            const saveResponse =
                await fetch(
                    API +
                    "?" +
                    params.toString(),
                    {
                        method:
                            "POST",

                        credentials:
                            "same-origin",

                        cache:
                            "no-store",

                        headers:{
                            "Content-Type":
                                "application/json"
                        },

                        body:
                            JSON.stringify(
                                {
                                    regions:
                                        regions
                                }
                            )
                    }
                );


            const result =
                await saveResponse.json();


            if(
                result &&
                result.ok
            ){

                setTimeout(
                    fetchSummary,
                    200
                );

            }

        }
        catch(error){

            console.debug(
                "Region Uptime sample:",
                error
            );

        }
        finally{

            posting =
                false;

        }

    }


    /* ========================================================
       FIND REGION
       ======================================================== */

    function findRegionTile(
        regionNameValue
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
                regionNameValue
            );


        const tiles =
            map.querySelectorAll(
                ".map-region"
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


    function centreRegion(
        regionNameValue
    ){

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
                        regionNameValue
                    );

            }

        }
        catch(error){
        }


        setTimeout(
            function(){

                const tile =
                    findRegionTile(
                        regionNameValue
                    );


                if(tile){

                    tile.click();

                }

            },
            120
        );

    }


    /* ========================================================
       TIME FORMAT
       ======================================================== */

    function ageText(timestamp){

        const value =
            Number(
                timestamp || 0
            );


        if(
            !value
        ){

            return "No data";
        }


        let seconds =
            Math.max(
                0,
                Math.floor(
                    Date.now() / 1000 -
                    value
                )
            );


        const days =
            Math.floor(
                seconds / 86400
            );


        if(days > 0){

            return (
                days +
                (
                    days === 1
                        ? " day"
                        : " days"
                )
            );
        }


        const hours =
            Math.floor(
                seconds / 3600
            );


        if(hours > 0){

            return (
                hours +
                (
                    hours === 1
                        ? " hour"
                        : " hours"
                )
            );
        }


        const minutes =
            Math.floor(
                seconds / 60
            );


        if(minutes > 0){

            return (
                minutes +
                (
                    minutes === 1
                        ? " min"
                        : " mins"
                )
            );
        }


        return "just now";

    }


    function dateText(timestamp){

        const value =
            Number(
                timestamp || 0
            );


        if(!value){

            return "None recorded";
        }


        return new Date(
            value *
            1000
        )
        .toLocaleString(
            "en-AU"
        );

    }


    /* ========================================================
       CONTROL
       ======================================================== */

    function ensureControl(){

        if(!viewport){
            return null;
        }


        control =
            document.getElementById(
                "gridmap-uptime-control-v1"
            );


        if(
            control &&
            control.isConnected
        ){

            return control;
        }


        control =
            document.createElement(
                "div"
            );


        control.id =
            "gridmap-uptime-control-v1";


        control.innerHTML = `

            <div
                id="gridmap-uptime-panel-v1">

                <div
                    class="gridmap-uptime-header-v1">

                    <span
                        class="gridmap-uptime-title-v1">
                        REGION UPTIME
                    </span>

                    <span
                        class="gridmap-uptime-subtitle-v1">
                        STATUS & OUTAGE HISTORY
                    </span>

                </div>


                <div
                    class="gridmap-uptime-windows-v1">

                    <button
                        type="button"
                        class="gridmap-uptime-window-v1"
                        data-uptime-window="3600">
                        1H
                    </button>

                    <button
                        type="button"
                        class="gridmap-uptime-window-v1"
                        data-uptime-window="86400">
                        24H
                    </button>

                    <button
                        type="button"
                        class="gridmap-uptime-window-v1"
                        data-uptime-window="604800">
                        7D
                    </button>

                    <button
                        type="button"
                        class="gridmap-uptime-window-v1"
                        data-uptime-window="2592000">
                        30D
                    </button>

                </div>


                <div
                    class="gridmap-uptime-legend-v1">

                    <span
                        class="gridmap-uptime-legend-item-v1">

                        <span
                            class="gridmap-uptime-dot-v1 online">
                        </span>

                        ONLINE

                    </span>

                    <span
                        class="gridmap-uptime-legend-item-v1">

                        <span
                            class="gridmap-uptime-dot-v1 warning">
                        </span>

                        WARNING

                    </span>

                    <span
                        class="gridmap-uptime-legend-item-v1">

                        <span
                            class="gridmap-uptime-dot-v1 offline">
                        </span>

                        OFFLINE

                    </span>

                    <span
                        class="gridmap-uptime-legend-item-v1">

                        <span
                            class="gridmap-uptime-dot-v1 unknown">
                        </span>

                        NO DATA

                    </span>

                </div>


                <div
                    id="gridmap-uptime-list-v1"
                    class="gridmap-uptime-list-v1">

                    <div
                        class="gridmap-uptime-empty-v1">

                        Building region uptime history...

                    </div>

                </div>


                <div
                    class="gridmap-uptime-footer-v1">

                    UPTIME = confirmed ONLINE samples.
                    WARNING is shown separately.
                    History begins from installation.

                </div>

            </div>


            <button
                type="button"
                id="gridmap-uptime-button-v1">

                <span
                    class="gridmap-uptime-heart-v1"><img class="ag-sentinel-direct-icon ag-sentinel-xs" src="/Other/assets/icons/sentinel/report.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

                <span>
                    UPTIME
                </span>

                <span
                    class="gridmap-uptime-mode-v1">
                    24H
                </span>

            </button>

        `;


        viewport.appendChild(
            control
        );


        const mainButton =
            control.querySelector(
                "#gridmap-uptime-button-v1"
            );


        if(mainButton){

            mainButton.addEventListener(
                "click",
                function(event){

                    event.preventDefault();

                    event.stopPropagation();


                    const traffic =
                        document.getElementById(
                            "gridmap-traffic-control-v1"
                        );


                    if(traffic){

                        traffic.classList.remove(
                            "is-open"
                        );

                    }


                    const layers =
                        document.getElementById(
                            "gridmap-layers-v1"
                        );


                    if(layers){

                        layers.classList.remove(
                            "is-open"
                        );

                    }


                    control.classList.toggle(
                        "is-open"
                    );

                }
            );

        }


        control
            .querySelectorAll(
                "[data-uptime-window]"
            )
            .forEach(
                button => {

                    button.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();

                            event.stopPropagation();


                            const seconds =
                                Number(
                                    button.getAttribute(
                                        "data-uptime-window"
                                    )
                                );


                            if(
                                !WINDOWS[
                                    seconds
                                ]
                            ){

                                return;
                            }


                            selectedWindow =
                                seconds;


                            localStorage.setItem(
                                STORAGE_WINDOW,
                                String(
                                    selectedWindow
                                )
                            );


                            updateWindowButtons();

                            fetchSummary();

                        }
                    );

                }
            );


        control.addEventListener(
            "pointerdown",
            function(event){

                event.stopPropagation();

            }
        );


        control.addEventListener(
            "pointermove",
            function(event){

                event.stopPropagation();

            }
        );


        control.addEventListener(
            "wheel",
            function(event){

                event.stopPropagation();

            },
            {
                passive:true
            }
        );


        updateWindowButtons();


        return control;

    }


    function updateWindowButtons(){

        if(!control){
            return;
        }


        control
            .querySelectorAll(
                "[data-uptime-window]"
            )
            .forEach(
                button => {

                    button.classList.toggle(
                        "is-active",
                        Number(
                            button.getAttribute(
                                "data-uptime-window"
                            )
                        ) ===
                        selectedWindow
                    );

                }
            );


        const mode =
            control.querySelector(
                ".gridmap-uptime-mode-v1"
            );


        if(mode){

            mode.textContent =
                WINDOWS[
                    selectedWindow
                ];

        }

    }


    /* ========================================================
       TIMELINE HTML
       ======================================================== */

    function timelineHtml(timeline){

        if(
            !Array.isArray(
                timeline
            )
        ){

            return "";
        }


        return timeline
            .map(
                status => {

                    const valid =
                        [
                            "online",
                            "warning",
                            "offline",
                            "unknown"
                        ]
                        .includes(
                            status
                        )
                            ? status
                            : "unknown";


                    return (
                        '<span class="gridmap-uptime-segment-v1 ' +
                        valid +
                        '"></span>'
                    );

                }
            )
            .join("");

    }


    /* ========================================================
       RENDER
       ======================================================== */

    function renderSummary(data){

        if(
            !control ||
            !data ||
            !Array.isArray(
                data.regions
            )
        ){

            return;
        }


        const list =
            control.querySelector(
                "#gridmap-uptime-list-v1"
            );


        if(!list){
            return;
        }


        if(
            !data.regions.length
        ){

            list.innerHTML = `

                <div
                    class="gridmap-uptime-empty-v1">

                    BUILDING UPTIME HISTORY<br><br>

                    The first status sample is
                    being recorded now.

                </div>

            `;


            return;
        }


        let html =
            "";


        for(
            const region
            of data.regions
        ){

            const currentClass =
                [
                    "online",
                    "warning",
                    "offline",
                    "unknown"
                ]
                .includes(
                    region.current_class
                )
                    ? region.current_class
                    : "unknown";


            const currentStatus =
                String(
                    region.current_status ||
                    currentClass
                )
                .toUpperCase();


            const uptime =
                region.uptime_percent ===
                null
                    ? "\u2014"
                    : (
                        Number(
                            region.uptime_percent
                        )
                        .toFixed(1) +
                        "%"
                    );


            html += `

                <div
                    class="gridmap-uptime-region-v1">

                    <div
                        class="gridmap-uptime-region-top-v1">

                        <span
                            class="gridmap-uptime-dot-v1 ${currentClass}">
                        </span>

                        <button
                            type="button"
                            class="gridmap-uptime-region-button-v1"
                            data-uptime-region="${esc(region.region)}">

                            ${esc(region.region)}

                        </button>

                        <span
                            class="gridmap-uptime-status-v1 ${currentClass}">

                            ${esc(currentStatus)}

                        </span>

                        <span
                            class="gridmap-uptime-percent-v1">

                            ${uptime}

                        </span>

                    </div>


                    <div
                        class="gridmap-uptime-timeline-v1"
                        title="Oldest on left \u2014 newest on right">

                        ${timelineHtml(region.timeline)}

                    </div>


                    <div
                        class="gridmap-uptime-meta-v1">

                        <span>

                            Current:
                            <strong>
                                ${esc(ageText(region.current_since))}
                            </strong>

                        </span>

                        <span>

                            Last outage:
                            <strong>
                                ${esc(dateText(region.last_outage))}
                            </strong>

                        </span>

                        <span>

                            Online:
                            <strong>
                                ${Number(region.online_samples || 0)}
                            </strong>

                        </span>

                        <span>

                            Warning:
                            <strong>
                                ${Number(region.warning_samples || 0)}
                            </strong>

                        </span>

                        <span>

                            Offline:
                            <strong>
                                ${Number(region.offline_samples || 0)}
                            </strong>

                        </span>

                    </div>

                </div>

            `;

        }


        list.innerHTML =
            html;


        list
            .querySelectorAll(
                "[data-uptime-region]"
            )
            .forEach(
                button => {

                    button.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();

                            event.stopPropagation();


                            const region =
                                button.getAttribute(
                                    "data-uptime-region"
                                );


                            if(region){

                                centreRegion(
                                    region
                                );

                            }

                        }
                    );

                }
            );

    }


    /* ========================================================
       GET SUMMARY
       ======================================================== */

    async function fetchSummary(){

        try{

            const params =
                new URLSearchParams();


            params.set(
                "token",
                TOKEN
            );


            params.set(
                "window",
                String(
                    selectedWindow
                )
            );


            params.set(
                "nocache",
                String(
                    Date.now()
                )
            );


            const response =
                await fetch(
                    API +
                    "?" +
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
                data &&
                data.ok
            ){

                renderSummary(
                    data
                );

            }

        }
        catch(error){

            console.debug(
                "Region Uptime summary:",
                error
            );

        }

    }


    /* ========================================================
       CLOSE WHEN CLICKING ELSEWHERE
       ======================================================== */

    function installOutsideClose(){

        document.addEventListener(
            "pointerdown",
            function(event){

                if(
                    !control ||
                    !control.classList.contains(
                        "is-open"
                    )
                ){

                    return;
                }


                if(
                    control.contains(
                        event.target
                    )
                ){

                    return;
                }


                control.classList.remove(
                    "is-open"
                );

            }
        );

    }


    /* ========================================================
       INSTALL
       ======================================================== */

    function installRegionUptimeV1(){

        viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        if(!viewport){
            return;
        }


        ensureControl();

        installOutsideClose();


        /*
         * Record immediately, then every minute.
         */

        setTimeout(
            recordStatus,
            1000
        );


        setInterval(
            recordStatus,
            SAMPLE_MS
        );


        /*
         * Load and refresh summary.
         */

        setTimeout(
            fetchSummary,
            1500
        );


        setInterval(
            fetchSummary,
            SUMMARY_MS
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            installRegionUptimeV1
        );

    }
    else{

        installRegionUptimeV1();

    }

})();

/* END Grid MAP - REGION UPTIME CLIENT V1 */

</script>


<script>

/* ============================================================
   Grid MAP - ALERTS INCIDENTS CLIENT V1
   ============================================================ */

(function(){

    const TOKEN =
        "9a303d9cd29f90c12eb91a1879f76ce1ec4e000a82d0170844b5cd662841877f";


    const API =
        "/Other/grid-incidents.php";


    const STORAGE_WINDOW =
        "AustraliaGridIncidentWindowV1";


    const WINDOWS = {

        3600:"1H",
        86400:"24H",
        604800:"7D",
        2592000:"30D"

    };


    let selectedWindow =
        Number(
            localStorage.getItem(
                STORAGE_WINDOW
            ) || 86400
        );


    if(
        !WINDOWS[
            selectedWindow
        ]
    ){

        selectedWindow =
            86400;
    }


    let viewport =
        null;

    let control =
        null;


    /* ========================================================
       HELPERS
       ======================================================== */

    function esc(value){

        return String(
            value == null
                ? ""
                : value
        )
        .replace(/&/g,"&amp;")
        .replace(/</g,"&lt;")
        .replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;")
        .replace(/'/g,"&#039;");

    }


    function normalise(value){

        return String(
            value || ""
        )
        .trim()
        .replace(/\s+/g," ")
        .toLowerCase();

    }


    function durationText(seconds){

        seconds =
            Math.max(
                0,
                Number(
                    seconds || 0
                )
            );


        const days =
            Math.floor(
                seconds /
                86400
            );


        if(days > 0){

            const hours =
                Math.floor(
                    (
                        seconds %
                        86400
                    ) /
                    3600
                );


            return (
                days +
                "d " +
                hours +
                "h"
            );
        }


        const hours =
            Math.floor(
                seconds /
                3600
            );


        if(hours > 0){

            const minutes =
                Math.floor(
                    (
                        seconds %
                        3600
                    ) /
                    60
                );


            return (
                hours +
                "h " +
                minutes +
                "m"
            );
        }


        const minutes =
            Math.floor(
                seconds /
                60
            );


        if(minutes > 0){

            return (
                minutes +
                "m"
            );
        }


        return "<1m";

    }


    function dateText(timestamp){

        const value =
            Number(
                timestamp || 0
            );


        if(!value){

            return "Unknown";
        }


        return new Date(
            value *
            1000
        )
        .toLocaleString(
            "en-AU",
            {
                day:"2-digit",
                month:"2-digit",
                year:"numeric",
                hour:"numeric",
                minute:"2-digit"
            }
        );

    }


    /* ========================================================
       FIND REGION
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
            map.querySelectorAll(
                ".map-region"
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
                normalise(
                    stored
                ) ===
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


    function centreRegion(
        regionName
    ){

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
                        regionName
                    );

            }

        }
        catch(error){
        }


        setTimeout(
            function(){

                const tile =
                    findRegionTile(
                        regionName
                    );


                if(tile){

                    tile.click();

                }

            },
            120
        );

    }


    /* ========================================================
       CREATE CONTROL
       ======================================================== */

    function ensureControl(){

        if(!viewport){
            return null;
        }


        control =
            document.getElementById(
                "gridmap-incidents-control-v1"
            );


        if(
            control &&
            control.isConnected
        ){

            return control;
        }


        control =
            document.createElement(
                "div"
            );


        control.id =
            "gridmap-incidents-control-v1";


        control.innerHTML = `

            <div
                id="gridmap-incidents-panel-v1">

                <div
                    class="gridmap-incidents-header-v1">

                    <span
                        class="gridmap-incidents-header-dot-v1">
                    </span>

                    <div
                        class="gridmap-incidents-heading-v1">

                        <span
                            class="gridmap-incidents-title-v1">
                            GRID ALERTS & INCIDENTS
                        </span>

                        <span
                            class="gridmap-incidents-subtitle-v1">
                            REGION WARNING / OFFLINE HISTORY
                        </span>

                    </div>

                </div>


                <div
                    class="gridmap-incidents-windows-v1">

                    <button
                        type="button"
                        class="gridmap-incidents-window-v1"
                        data-incident-window="3600">
                        1H
                    </button>

                    <button
                        type="button"
                        class="gridmap-incidents-window-v1"
                        data-incident-window="86400">
                        24H
                    </button>

                    <button
                        type="button"
                        class="gridmap-incidents-window-v1"
                        data-incident-window="604800">
                        7D
                    </button>

                    <button
                        type="button"
                        class="gridmap-incidents-window-v1"
                        data-incident-window="2592000">
                        30D
                    </button>

                </div>


                <div
                    id="gridmap-incidents-current-v1"
                    class="gridmap-incidents-current-v1">

                    <div
                        class="gridmap-incidents-current-title-v1">
                        CURRENT GRID STATUS
                    </div>

                    <div
                        class="gridmap-incidents-clear-v1">

                        <span
                            class="gridmap-incidents-clear-dot-v1">
                        </span>

                        ALL REGIONS CLEAR

                    </div>

                </div>


                <div
                    class="gridmap-incidents-history-title-v1">
                    RECENT INCIDENTS
                </div>


                <div
                    id="gridmap-incidents-list-v1"
                    class="gridmap-incidents-list-v1">

                    <div
                        class="gridmap-incidents-empty-v1">
                        No incidents recorded yet.
                    </div>

                </div>


                <div
                    class="gridmap-incidents-footer-v1">

                    <span>
                        Derived from UPTIME history
                    </span>

                    <span
                        id="gridmap-incidents-updated-v1">
                        Waiting...
                    </span>

                </div>

            </div>


            <button
                type="button"
                id="gridmap-incidents-button-v1">

                <span
                    class="gridmap-incidents-icon-v1"><img class="ag-sentinel-direct-icon ag-sentinel-xs" src="/Other/assets/icons/sentinel/alert.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

                <span
                    class="gridmap-incidents-button-label-v1">
                    ALL CLEAR
                </span>

                <span
                    class="gridmap-incidents-count-v1">
                    0
                </span>

            </button>

        `;


        viewport.appendChild(
            control
        );


        /* MAIN BUTTON */

        const mainButton =
            control.querySelector(
                "#gridmap-incidents-button-v1"
            );


        if(mainButton){

            mainButton.addEventListener(
                "click",
                function(event){

                    event.preventDefault();
                    event.stopPropagation();


                    const others =
                        [
                            "gridmap-layers-v1",
                            "gridmap-traffic-control-v1",
                            "gridmap-uptime-control-v1"
                        ];


                    for(
                        const id
                        of others
                    ){

                        const other =
                            document.getElementById(
                                id
                            );


                        if(other){

                            other.classList.remove(
                                "is-open"
                            );

                        }

                    }


                    control.classList.toggle(
                        "is-open"
                    );

                }
            );

        }


        /* WINDOW BUTTONS */

        control
            .querySelectorAll(
                "[data-incident-window]"
            )
            .forEach(
                button => {

                    button.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();
                            event.stopPropagation();


                            const seconds =
                                Number(
                                    button.getAttribute(
                                        "data-incident-window"
                                    )
                                );


                            if(
                                !WINDOWS[
                                    seconds
                                ]
                            ){

                                return;
                            }


                            selectedWindow =
                                seconds;


                            localStorage.setItem(
                                STORAGE_WINDOW,
                                String(
                                    selectedWindow
                                )
                            );


                            updateWindowButtons();

                            fetchIncidents();

                        }
                    );

                }
            );


        control.addEventListener(
            "pointerdown",
            function(event){

                event.stopPropagation();

            }
        );


        control.addEventListener(
            "pointermove",
            function(event){

                event.stopPropagation();

            }
        );


        control.addEventListener(
            "wheel",
            function(event){

                event.stopPropagation();

            },
            {
                passive:true
            }
        );


        updateWindowButtons();


        return control;

    }


    /* ========================================================
       TIME WINDOW
       ======================================================== */

    function updateWindowButtons(){

        if(!control){
            return;
        }


        control
            .querySelectorAll(
                "[data-incident-window]"
            )
            .forEach(
                button => {

                    button.classList.toggle(
                        "is-active",
                        Number(
                            button.getAttribute(
                                "data-incident-window"
                            )
                        ) ===
                        selectedWindow
                    );

                }
            );

    }


    /* ========================================================
       CURRENT ALERTS
       ======================================================== */

    function renderCurrent(
        data
    ){

        const holder =
            control.querySelector(
                "#gridmap-incidents-current-v1"
            );


        if(!holder){
            return;
        }


        const active =
            Array.isArray(
                data.active
            )
                ? data.active
                : [];


        let html = `

            <div
                class="gridmap-incidents-current-title-v1">
                CURRENT GRID STATUS
            </div>

        `;


        if(
            active.length === 0
        ){

            html += `

                <div
                    class="gridmap-incidents-clear-v1">

                    <span
                        class="gridmap-incidents-clear-dot-v1">
                    </span>

                    ALL REGIONS CLEAR

                </div>

            `;


            holder.innerHTML =
                html;


            return;
        }


        for(
            const alert
            of active
        ){

            const severity =
                alert.severity ===
                "offline"
                    ? "offline"
                    : "warning";


            const status =
                String(
                    alert.status ||
                    severity
                ).toUpperCase();


            html += `

                <div
                    class="gridmap-current-alert-v1 ${severity}">

                    <span
                        class="gridmap-current-alert-dot-v1 ${severity}">
                    </span>

                    <button
                        type="button"
                        class="gridmap-current-alert-button-v1"
                        data-alert-region="${esc(alert.region)}">

                        ${esc(alert.region)}

                    </button>

                    <span
                        class="gridmap-current-alert-status-v1">

                        ${esc(status)}

                    </span>

                    <span
                        class="gridmap-current-alert-duration-v1">

                        ${esc(durationText(alert.duration))}

                    </span>

                </div>

            `;

        }


        holder.innerHTML =
            html;


        holder
            .querySelectorAll(
                "[data-alert-region]"
            )
            .forEach(
                button => {

                    button.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();
                            event.stopPropagation();


                            centreRegion(
                                button.getAttribute(
                                    "data-alert-region"
                                )
                            );

                        }
                    );

                }
            );

    }


    /* ========================================================
       INCIDENT HISTORY
       ======================================================== */

    function renderHistory(
        data
    ){

        const holder =
            control.querySelector(
                "#gridmap-incidents-list-v1"
            );


        if(!holder){
            return;
        }


        const incidents =
            Array.isArray(
                data.incidents
            )
                ? data.incidents
                : [];


        if(
            incidents.length === 0
        ){

            holder.innerHTML = `

                <div
                    class="gridmap-incidents-empty-v1">

                    No warning or offline incidents
                    recorded in this period.

                </div>

            `;


            return;
        }


        let html =
            "";


        for(
            const incident
            of incidents
        ){

            const severity =
                incident.severity ===
                "offline"
                    ? "offline"
                    : "warning";


            const recovered =
                Boolean(
                    incident.recovered
                );


            html += `

                <div
                    class="gridmap-incident-row-v1">

                    <span
                        class="gridmap-incident-severity-v1 ${severity}">
                    </span>

                    <div
                        class="gridmap-incident-copy-v1">

                        <button
                            type="button"
                            class="gridmap-incident-region-v1"
                            data-incident-region="${esc(incident.region)}">

                            ${esc(incident.region)}

                        </button>

                        <span
                            class="gridmap-incident-meta-v1">

                            ${severity.toUpperCase()}
                            \u2022 Started ${esc(dateText(incident.started))}
                            \u2022 ${esc(durationText(incident.duration))}

                        </span>

                    </div>

                    <span
                        class="gridmap-incident-result-v1 ${
                            recovered
                                ? "recovered"
                                : "active"
                        }">

                        ${
                            recovered
                                ? "RECOVERED"
                                : "ACTIVE"
                        }

                    </span>

                </div>

            `;

        }


        holder.innerHTML =
            html;


        holder
            .querySelectorAll(
                "[data-incident-region]"
            )
            .forEach(
                button => {

                    button.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();
                            event.stopPropagation();


                            centreRegion(
                                button.getAttribute(
                                    "data-incident-region"
                                )
                            );

                        }
                    );

                }
            );

    }


    /* ========================================================
       BUTTON STATE
       ======================================================== */

    function renderButtonState(
        data
    ){

        if(!control){
            return;
        }


        const activeCount =
            Number(
                data.active_count || 0
            );


        const offlineCount =
            Number(
                data.offline_count || 0
            );


        const warningCount =
            Number(
                data.warning_count || 0
            );


        control.classList.toggle(
            "has-offline",
            offlineCount > 0
        );


        control.classList.toggle(
            "has-warning",
            offlineCount === 0 &&
            warningCount > 0
        );


        const label =
            control.querySelector(
                ".gridmap-incidents-button-label-v1"
            );


        const count =
            control.querySelector(
                ".gridmap-incidents-count-v1"
            );


        const headerDot =
            control.querySelector(
                ".gridmap-incidents-header-dot-v1"
            );


        if(label){

            label.textContent =
                activeCount === 0
                    ? "ALL CLEAR"
                    : "ALERTS";

        }


        if(count){

            count.textContent =
                String(
                    activeCount
                );

        }


        if(headerDot){

            headerDot.classList.remove(
                "offline",
                "warning"
            );


            if(
                offlineCount > 0
            ){

                headerDot.classList.add(
                    "offline"
                );

            }
            else if(
                warningCount > 0
            ){

                headerDot.classList.add(
                    "warning"
                );

            }

        }

    }


    /* ========================================================
       RENDER
       ======================================================== */

    function render(
        data
    ){

        renderButtonState(
            data
        );


        renderCurrent(
            data
        );


        renderHistory(
            data
        );


        const updated =
            control.querySelector(
                "#gridmap-incidents-updated-v1"
            );


        if(updated){

            updated.textContent =
                "Updated " +
                new Date()
                    .toLocaleTimeString(
                        "en-AU",
                        {
                            hour:"numeric",
                            minute:"2-digit",
                            second:"2-digit"
                        }
                    );

        }

    }


    /* ========================================================
       FETCH
       ======================================================== */

    async function fetchIncidents(){

        try{

            const params =
                new URLSearchParams();


            params.set(
                "token",
                TOKEN
            );


            params.set(
                "window",
                String(
                    selectedWindow
                )
            );


            params.set(
                "nocache",
                String(
                    Date.now()
                )
            );


            const response =
                await fetch(
                    API +
                    "?" +
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
                data &&
                data.ok
            ){

                render(
                    data
                );

            }

        }
        catch(error){

            console.debug(
                "Grid incidents:",
                error
            );

        }

    }


    /* ========================================================
       OUTSIDE CLOSE
       ======================================================== */

    function installOutsideClose(){

        document.addEventListener(
            "pointerdown",
            function(event){

                if(
                    !control ||
                    !control.classList.contains(
                        "is-open"
                    )
                ){

                    return;
                }


                if(
                    control.contains(
                        event.target
                    )
                ){

                    return;
                }


                control.classList.remove(
                    "is-open"
                );

            }
        );

    }


    /* ========================================================
       INSTALL
       ======================================================== */

    function installGridIncidentsV1(){

        viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        if(!viewport){
            return;
        }


        ensureControl();

        installOutsideClose();


        /*
         * Initial read happens after uptime has had a moment
         * to record its first sample.
         */

        setTimeout(
            fetchIncidents,
            2200
        );


        /*
         * Refresh every 30 seconds.
         *
         * This is only reading a small JSON history file.
         * It is NOT polling OpenSim.
         */

        setInterval(
            fetchIncidents,
            30000
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            installGridIncidentsV1
        );

    }
    else{

        installGridIncidentsV1();

    }

})();

/* END Grid MAP - ALERTS INCIDENTS CLIENT V1 */

</script>


<script>

/* ============================================================
   Grid MAP - STATISTICS DASHBOARD CLIENT V1
   ============================================================ */

(function(){

    const TOKEN =
        "4e6c8e72233ca143c3df3815c681f7daa07d2165d4c1fee5ccd187b5fa5d440b";


    const API =
        "/Other/grid-statistics.php";


    const WINDOW_KEY =
        "AustraliaGridStatisticsWindowV1";


    const TAB_KEY =
        "AustraliaGridStatisticsTabV1";


    const WINDOWS = {

        3600:"1H",
        86400:"24H",
        604800:"7D",
        2592000:"30D"

    };


    let selectedWindow =
        Number(
            localStorage.getItem(
                WINDOW_KEY
            ) || 86400
        );


    if(
        !WINDOWS[
            selectedWindow
        ]
    ){

        selectedWindow =
            86400;
    }


    let selectedTab =
        localStorage.getItem(
            TAB_KEY
        ) || "overview";


    if(
        ![
            "overview",
            "traffic",
            "uptime",
            "incidents"
        ].includes(
            selectedTab
        )
    ){

        selectedTab =
            "overview";
    }


    let viewport =
        null;

    let control =
        null;

    let latestStats =
        null;

    let latestLive =
        window.AustraliaGridLiveAgentsV2 ||
        null;


    /* ========================================================
       HELPERS
       ======================================================== */

    function esc(value){

        return String(
            value == null
                ? ""
                : value
        )
        .replace(/&/g,"&amp;")
        .replace(/</g,"&lt;")
        .replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;")
        .replace(/'/g,"&#039;");

    }


    function normalise(value){

        return String(
            value || ""
        )
        .trim()
        .replace(/\s+/g," ")
        .toLowerCase();

    }


    function percent(value){

        if(
            value === null ||
            value === undefined ||
            !Number.isFinite(
                Number(
                    value
                )
            )
        ){

            return "\u2014";
        }


        return (
            Number(value)
                .toFixed(1) +
            "%"
        );
    }


    function durationText(seconds){

        seconds =
            Math.max(
                0,
                Number(
                    seconds || 0
                )
            );


        const days =
            Math.floor(
                seconds /
                86400
            );


        if(days > 0){

            return (
                days +
                "d " +
                Math.floor(
                    (
                        seconds %
                        86400
                    ) /
                    3600
                ) +
                "h"
            );
        }


        const hours =
            Math.floor(
                seconds /
                3600
            );


        if(hours > 0){

            return (
                hours +
                "h " +
                Math.floor(
                    (
                        seconds %
                        3600
                    ) /
                    60
                ) +
                "m"
            );
        }


        const minutes =
            Math.floor(
                seconds /
                60
            );


        if(minutes > 0){

            return (
                minutes +
                "m"
            );
        }


        return "<1m";
    }


    function dateText(timestamp){

        const value =
            Number(
                timestamp || 0
            );


        if(!value){

            return "Unknown";
        }


        return new Date(
            value *
            1000
        )
        .toLocaleString(
            "en-AU",
            {
                day:"2-digit",
                month:"2-digit",
                hour:"numeric",
                minute:"2-digit"
            }
        );

    }


    /* ========================================================
       LIVE AGENTS
       ======================================================== */

    function liveAgents(){

        if(
            latestLive &&
            Array.isArray(
                latestLive.agents
            )
        ){

            return latestLive.agents;
        }


        if(
            window.AustraliaGridLiveAgentsV2 &&
            Array.isArray(
                window.AustraliaGridLiveAgentsV2.agents
            )
        ){

            return window
                .AustraliaGridLiveAgentsV2
                .agents;
        }


        return [];
    }


    function activeAvatarRegions(){

        const regions =
            new Set();


        for(
            const agent
            of liveAgents()
        ){

            if(
                agent &&
                agent.region
            ){

                regions.add(
                    String(
                        agent.region
                    )
                );

            }

        }


        return regions.size;
    }


    /* ========================================================
       REGION NAVIGATION
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
            map.querySelectorAll(
                ".map-region"
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
                normalise(
                    stored
                ) ===
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


    function centreRegion(
        regionName
    ){

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
                        regionName
                    );

            }

        }
        catch(error){
        }


        setTimeout(
            function(){

                const tile =
                    findRegionTile(
                        regionName
                    );


                if(tile){

                    tile.click();

                }

            },
            120
        );

    }


    /* ========================================================
       CREATE CONTROL
       ======================================================== */

    function ensureControl(){

        if(!viewport){

            return null;
        }


        control =
            document.getElementById(
                "gridmap-stats-control-v1"
            );


        if(
            control &&
            control.isConnected
        ){

            return control;
        }


        control =
            document.createElement(
                "div"
            );


        control.id =
            "gridmap-stats-control-v1";


        control.innerHTML = `

            <div
                id="gridmap-stats-panel-v1">

                <div
                    class="gridmap-stats-header-v1"><div
                        class="gridmap-stats-heading-v1">

                        <span
                            class="gridmap-stats-title-v1">
                            GRID STATISTICS
                        </span>

                        <span
                            class="gridmap-stats-subtitle-v1">
                            LIVE & HISTORICAL GRID OVERVIEW
                        </span>

                    </div>

                    <span
                        class="gridmap-stats-updated-v1">
                        Waiting...
                    </span>

                </div>


                <div
                    class="gridmap-stats-windows-v1">

                    <button
                        type="button"
                        class="gridmap-stats-window-v1"
                        data-stats-window="3600">
                        1H
                    </button>

                    <button
                        type="button"
                        class="gridmap-stats-window-v1"
                        data-stats-window="86400">
                        24H
                    </button>

                    <button
                        type="button"
                        class="gridmap-stats-window-v1"
                        data-stats-window="604800">
                        7D
                    </button>

                    <button
                        type="button"
                        class="gridmap-stats-window-v1"
                        data-stats-window="2592000">
                        30D
                    </button>

                </div>


                <div
                    class="gridmap-stats-tabs-v1">

                    <button
                        type="button"
                        class="gridmap-stats-tab-v1"
                        data-stats-tab="overview">
                        OVERVIEW
                    </button>

                    <button
                        type="button"
                        class="gridmap-stats-tab-v1"
                        data-stats-tab="traffic">
                        TRAFFIC
                    </button>

                    <button
                        type="button"
                        class="gridmap-stats-tab-v1"
                        data-stats-tab="uptime">
                        UPTIME
                    </button>

                    <button
                        type="button"
                        class="gridmap-stats-tab-v1"
                        data-stats-tab="incidents">
                        INCIDENTS
                    </button>

                </div>


                <div
                    id="gridmap-stats-body-v1">

                    <div
                        class="gridmap-stats-empty-v1">
                        Loading Grid Statistics...
                    </div>

                </div>


                <div
                    class="gridmap-stats-footer-v1">

                    <span>
                        Existing grid history only
                    </span>

                    <span>
                        Read-only dashboard
                    </span>

                </div>

            </div>


            <button
                type="button"
                id="gridmap-stats-button-v1">

                <span
                    class="gridmap-stats-icon-v1"><img class="ag-sentinel-direct-icon ag-sentinel-xs" src="/Other/assets/icons/sentinel/statistics.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span>

                <span>
                    STATS
                </span>

                <span
                    class="gridmap-stats-mode-v1">
                    24H
                </span>

            </button>

        `;


        viewport.appendChild(
            control
        );


        /* ====================================================
           OPEN
           ==================================================== */

        const mainButton =
            control.querySelector(
                "#gridmap-stats-button-v1"
            );


        if(mainButton){

            mainButton.addEventListener(
                "click",
                function(event){

                    event.preventDefault();

                    event.stopPropagation();


                    const others =
                        [
                            "gridmap-layers-v1",
                            "gridmap-traffic-control-v1",
                            "gridmap-uptime-control-v1",
                            "gridmap-incidents-control-v1"
                        ];


                    for(
                        const id
                        of others
                    ){

                        const element =
                            document.getElementById(
                                id
                            );


                        if(element){

                            element.classList.remove(
                                "is-open"
                            );

                        }

                    }


                    control.classList.toggle(
                        "is-open"
                    );


                    if(
                        control.classList.contains(
                            "is-open"
                        )
                    ){

                        fetchStats();

                    }

                }
            );

        }


        /* ====================================================
           WINDOWS
           ==================================================== */

        control
            .querySelectorAll(
                "[data-stats-window]"
            )
            .forEach(
                button => {

                    button.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();

                            event.stopPropagation();


                            const value =
                                Number(
                                    button.getAttribute(
                                        "data-stats-window"
                                    )
                                );


                            if(
                                !WINDOWS[
                                    value
                                ]
                            ){

                                return;
                            }


                            selectedWindow =
                                value;


                            localStorage.setItem(
                                WINDOW_KEY,
                                String(
                                    selectedWindow
                                )
                            );


                            updateControls();

                            fetchStats();

                        }
                    );

                }
            );


        /* ====================================================
           TABS
           ==================================================== */

        control
            .querySelectorAll(
                "[data-stats-tab]"
            )
            .forEach(
                button => {

                    button.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();

                            event.stopPropagation();


                            selectedTab =
                                button.getAttribute(
                                    "data-stats-tab"
                                );


                            localStorage.setItem(
                                TAB_KEY,
                                selectedTab
                            );


                            updateControls();

                            render();

                        }
                    );

                }
            );


        /*
         * Do not trigger map drag/zoom when operating stats.
         */

        control.addEventListener(
            "pointerdown",
            function(event){

                event.stopPropagation();

            }
        );


        control.addEventListener(
            "pointermove",
            function(event){

                event.stopPropagation();

            }
        );


        control.addEventListener(
            "wheel",
            function(event){

                event.stopPropagation();

            },
            {
                passive:true
            }
        );


        updateControls();


        return control;

    }


    /* ========================================================
       CONTROL STATE
       ======================================================== */

    function updateControls(){

        if(!control){

            return;
        }


        control
            .querySelectorAll(
                "[data-stats-window]"
            )
            .forEach(
                button => {

                    button.classList.toggle(
                        "is-active",
                        Number(
                            button.getAttribute(
                                "data-stats-window"
                            )
                        ) ===
                        selectedWindow
                    );

                }
            );


        control
            .querySelectorAll(
                "[data-stats-tab]"
            )
            .forEach(
                button => {

                    button.classList.toggle(
                        "is-active",
                        button.getAttribute(
                            "data-stats-tab"
                        ) ===
                        selectedTab
                    );

                }
            );


        const mode =
            control.querySelector(
                ".gridmap-stats-mode-v1"
            );


        if(mode){

            mode.textContent =
                WINDOWS[
                    selectedWindow
                ];

        }

    }


    /* ========================================================
       REGION BUTTON
       ======================================================== */

    function regionButton(name){

        if(!name){

            return "\u2014";
        }


        return `

            <button
                type="button"
                class="gridmap-stats-region-link-v1"
                data-stats-region="${esc(name)}">

                ${esc(name)}

            </button>

        `;

    }


    /* ========================================================
       OVERVIEW
       ======================================================== */

    function renderOverview(){

        const data =
            latestStats;


        const traffic =
            data.traffic || {};


        const uptime =
            data.uptime || {};


        const incidents =
            data.incidents || {};


        const agents =
            liveAgents();


        const busiest =
            traffic.busiest || null;


        const alerts =
            Number(
                incidents.active_count || 0
            );


        const uptimeValue =
            uptime.grid_uptime_percent;


        const alertClass =
            alerts > 0
                ? "red"
                : "green";


        const body =
            control.querySelector(
                "#gridmap-stats-body-v1"
            );


        body.innerHTML = `

            <div
                class="gridmap-stats-cards-v1">


                <div
                    class="gridmap-stats-card-v1">

                    <span
                        class="gridmap-stats-card-label-v1">
                        AVATARS ONLINE NOW
                    </span>

                    <span
                        class="gridmap-stats-card-value-v1 green">
                        ${agents.length}
                    </span>

                    <span
                        class="gridmap-stats-card-note-v1">
                            ${ Number( uptime.regions_offline || 0 ) } offline &#8226; ${ Number( uptime.regions_warning || 0 ) } warning
                        </span>

                </div>


                <div
                    class="gridmap-stats-card-v1">

                    <span
                        class="gridmap-stats-card-label-v1">
                        INCIDENTS
                    </span>

                    <span
                        class="gridmap-stats-card-value-v1">
                        ${Number(incidents.count || 0)}
                    </span>

                    <span
                        class="gridmap-stats-card-note-v1">
                        ${WINDOWS[selectedWindow]} recorded history
                    </span>

                </div>

            </div>


            <div
                class="gridmap-stats-section-v1">

                <div
                    class="gridmap-stats-section-title-v1">
                    REGION PERFORMANCE
                </div>

                <table
                    class="gridmap-stats-table-v1">

                    <thead>

                        <tr>
                            <th>METRIC</th>
                            <th>REGION</th>
                            <th>VALUE</th>
                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td>
                                Busiest
                            </td>

                            <td>
                                ${
                                    busiest
                                        ? regionButton(
                                            busiest.region
                                          )
                                        : "\u2014"
                                }
                            </td>

                            <td>
                                ${
                                    busiest
                                        ? Number(
                                            busiest.avg_online || 0
                                          ).toFixed(2) +
                                          " AVG"
                                        : "No data"
                                }
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Best Uptime
                            </td>

                            <td>
                                ${
                                    uptime.best
                                        ? regionButton(
                                            uptime.best.region
                                          )
                                        : "\u2014"
                                }
                            </td>

                            <td>
                                ${
                                    uptime.best
                                        ? percent(
                                            uptime.best
                                                .uptime_percent
                                          )
                                        : "No data"
                                }
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Lowest Uptime
                            </td>

                            <td>
                                ${
                                    uptime.worst
                                        ? regionButton(
                                            uptime.worst.region
                                          )
                                        : "\u2014"
                                }
                            </td>

                            <td>
                                ${
                                    uptime.worst
                                        ? percent(
                                            uptime.worst
                                                .uptime_percent
                                          )
                                        : "No data"
                                }
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Traffic Samples
                            </td>

                            <td>
                                Entire Grid
                            </td>

                            <td>
                                ${Number(traffic.samples || 0)}
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        `;


        bindRegionButtons();

    }


    /* ========================================================
       TRAFFIC TAB
       ======================================================== */

    function renderTraffic(){

        const traffic =
            latestStats.traffic || {};


        const regions =
            Array.isArray(
                traffic.regions
            )
                ? traffic.regions
                : [];


        const body =
            control.querySelector(
                "#gridmap-stats-body-v1"
            );


        if(
            !regions.length
        ){

            body.innerHTML = `

                <div
                    class="gridmap-stats-empty-v1">

                    No traffic history has been
                    recorded for this period yet.

                </div>

            `;

            return;
        }


        let rows =
            "";


        regions.forEach(
            function(
                region,
                index
            ){

                rows += `

                    <tr>

                        <td>
                            #${index + 1}
                        </td>

                        <td>
                            ${regionButton(region.region)}
                        </td>

                        <td>
                            ${Number(region.avg_online || 0).toFixed(2)}
                        </td>

                        <td>
                            ${Number(region.max_online || 0)}
                        </td>

                        <td>
                            ${Number(region.unique_count || 0)}
                        </td>

                        <td>
                            ${Number(region.active_percent || 0).toFixed(0)}%
                        </td>

                    </tr>

                `;

            }
        );


        body.innerHTML = `

            <div
                class="gridmap-stats-section-v1">

                <div
                    class="gridmap-stats-section-title-v1">

                    REGION TRAFFIC &#8226; ${WINDOWS[selectedWindow]}

                </div>

                <table
                    class="gridmap-stats-table-v1">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>REGION</th>
                            <th>AVG</th>
                            <th>PEAK</th>
                            <th>UNIQUE</th>
                            <th>ACTIVE</th>
                        </tr>

                    </thead>

                    <tbody>
                        ${rows}
                    </tbody>

                </table>

            </div>

        `;


        bindRegionButtons();

    }


    /* ========================================================
       UPTIME TAB
       ======================================================== */

    function renderUptime(){

        const uptime =
            latestStats.uptime || {};


        let regions =
            Array.isArray(
                uptime.regions
            )
                ? uptime.regions.slice()
                : [];


        if(
            !regions.length
        ){

            control.querySelector(
                "#gridmap-stats-body-v1"
            ).innerHTML = `

                <div
                    class="gridmap-stats-empty-v1">

                    No uptime history has been
                    recorded for this period yet.

                </div>

            `;

            return;
        }


        const rank = {

            offline:0,
            warning:1,
            unknown:2,
            online:3

        };


        regions.sort(
            function(a,b){

                const ar =
                    rank[
                        a.current_class
                    ] ?? 2;


                const br =
                    rank[
                        b.current_class
                    ] ?? 2;


                if(
                    ar !== br
                ){

                    return ar - br;
                }


                return String(
                    a.region
                ).localeCompare(
                    String(
                        b.region
                    )
                );

            }
        );


        let rows =
            "";


        for(
            const region
            of regions
        ){

            const statusClass =
                [
                    "online",
                    "warning",
                    "offline",
                    "unknown"
                ].includes(
                    region.current_class
                )
                    ? region.current_class
                    : "unknown";


            rows += `

                <tr>

                    <td>
                        ${regionButton(region.region)}
                    </td>

                    <td>

                        <span
                            class="gridmap-stats-status-v1 ${statusClass}">

                            ${esc(
                                String(
                                    region.current_status ||
                                    statusClass
                                ).toUpperCase()
                            )}

                        </span>

                    </td>

                    <td>
                        ${percent(region.uptime_percent)}
                    </td>

                    <td>
                        ${Number(region.online_samples || 0)}
                    </td>

                    <td>
                        ${Number(region.warning_samples || 0)}
                    </td>

                    <td>
                        ${Number(region.offline_samples || 0)}
                    </td>

                </tr>

            `;

        }


        control.querySelector(
            "#gridmap-stats-body-v1"
        ).innerHTML = `

            <div
                class="gridmap-stats-section-v1">

                <div
                    class="gridmap-stats-section-title-v1">

                    REGION UPTIME &#8226; ${WINDOWS[selectedWindow]}

                </div>

                <table
                    class="gridmap-stats-table-v1">

                    <thead>

                        <tr>
                            <th>REGION</th>
                            <th>CURRENT</th>
                            <th>UPTIME</th>
                            <th>ONLINE</th>
                            <th>WARNING</th>
                            <th>OFFLINE</th>
                        </tr>

                    </thead>

                    <tbody>
                        ${rows}
                    </tbody>

                </table>

            </div>

        `;


        bindRegionButtons();

    }


    /* ========================================================
       INCIDENT TAB
       ======================================================== */

    function renderIncidents(){

        const incidents =
            latestStats.incidents || {};


        const recent =
            Array.isArray(
                incidents.recent
            )
                ? incidents.recent
                : [];


        const body =
            control.querySelector(
                "#gridmap-stats-body-v1"
            );


        if(
            !recent.length
        ){

            body.innerHTML = `

                <div
                    class="gridmap-stats-empty-v1">

                    No warning or offline incidents
                    recorded during this period.

                </div>

            `;

            return;
        }


        let html = `

            <div
                class="gridmap-stats-section-v1">

                <div
                    class="gridmap-stats-section-title-v1">

                    INCIDENT HISTORY \u2014
                    ${WINDOWS[selectedWindow]}

                </div>

        `;


        for(
            const incident
            of recent
        ){

            const severity =
                incident.severity ===
                "offline"
                    ? "offline"
                    : "warning";


            const recovered =
                Boolean(
                    incident.recovered
                );


            html += `

                <div
                    class="gridmap-stats-incident-v1">

                    <span
                        class="gridmap-stats-incident-bar-v1 ${severity}">
                    </span>

                    <div
                        class="gridmap-stats-incident-copy-v1">

                        ${regionButton(incident.region)}

                        <span
                            class="gridmap-stats-incident-meta-v1">

                            ${severity.toUpperCase()}
                            \u2022 ${dateText(incident.started)}
                            \u2022 ${durationText(incident.duration)}

                        </span>

                    </div>

                    <span
                        class="gridmap-stats-incident-result-v1 ${
                            recovered
                                ? "recovered"
                                : "active"
                        }">

                        ${
                            recovered
                                ? "RECOVERED"
                                : "ACTIVE"
                        }

                    </span>

                </div>

            `;

        }


        html +=
            "</div>";


        body.innerHTML =
            html;


        bindRegionButtons();

    }


    /* ========================================================
       BIND REGION BUTTONS
       ======================================================== */

    function bindRegionButtons(){

        if(!control){

            return;
        }


        control
            .querySelectorAll(
                "[data-stats-region]"
            )
            .forEach(
                button => {

                    button.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();

                            event.stopPropagation();


                            const region =
                                button.getAttribute(
                                    "data-stats-region"
                                );


                            if(region){

                                centreRegion(
                                    region
                                );

                            }

                        }
                    );

                }
            );

    }


    /* ========================================================
       RENDER ACTIVE TAB
       ======================================================== */

    function render(){

        if(
            !control ||
            !latestStats
        ){

            return;
        }


        if(
            selectedTab ===
            "traffic"
        ){

            renderTraffic();

        }
        else if(
            selectedTab ===
            "uptime"
        ){

            renderUptime();

        }
        else if(
            selectedTab ===
            "incidents"
        ){

            renderIncidents();

        }
        else{

            renderOverview();

        }

    }


    /* ========================================================
       FETCH STATISTICS
       ======================================================== */

    async function fetchStats(){

        try{

            const params =
                new URLSearchParams();


            params.set(
                "token",
                TOKEN
            );


            params.set(
                "window",
                String(
                    selectedWindow
                )
            );


            params.set(
                "nocache",
                String(
                    Date.now()
                )
            );


            const response =
                await fetch(
                    API +
                    "?" +
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


            latestStats =
                data;


            const updated =
                control.querySelector(
                    ".gridmap-stats-updated-v1"
                );


            if(updated){

                updated.textContent =
                    "Updated " +
                    new Date()
                        .toLocaleTimeString(
                            "en-AU",
                            {
                                hour:"numeric",
                                minute:"2-digit",
                                second:"2-digit"
                            }
                        );

            }


            render();

        }
        catch(error){

            console.debug(
                "Grid Statistics:",
                error
            );

        }

    }


    /* ========================================================
       LIVE AGENT UPDATE
       ======================================================== */

    function liveUpdate(event){

        if(
            event &&
            event.detail
        ){

            latestLive =
                event.detail;


            if(
                latestStats &&
                selectedTab ===
                "overview"
            ){

                renderOverview();

            }

        }

    }


    /* ========================================================
       OUTSIDE CLOSE
       ======================================================== */

    function installOutsideClose(){

        document.addEventListener(
            "pointerdown",
            function(event){

                if(
                    !control ||
                    !control.classList.contains(
                        "is-open"
                    )
                ){

                    return;
                }


                if(
                    control.contains(
                        event.target
                    )
                ){

                    return;
                }


                control.classList.remove(
                    "is-open"
                );

            }
        );

    }


    /* ========================================================
       INSTALL
       ======================================================== */

    function installGridStatisticsV1(){

        viewport =
            document.querySelector(
                ".gridmap-scroll"
            );


        if(!viewport){

            return;
        }


        ensureControl();

        installOutsideClose();


        window.addEventListener(
            "australia-grid-live-agents-v2",
            liveUpdate
        );


        setTimeout(
            fetchStats,
            2500
        );


        /*
         * Statistics API reads local history files only.
         */

        setInterval(
            fetchStats,
            30000
        );

    }


    if(
        document.readyState ===
        "loading"
    ){

        document.addEventListener(
            "DOMContentLoaded",
            installGridStatisticsV1
        );

    }
    else{

        installGridStatisticsV1();

    }

})();

/* END Grid MAP - STATISTICS DASHBOARD CLIENT V1 */

</script>


<script>

/* ============================================================
   Grid MAP - REGION PERFORMANCE CLIENT V2
   ============================================================ */

(function(){

    const TOKEN =
        "829b04b888f948b19dd27b6073c1feecb1bd48cf672448876c630ba3f1f85bbd";

    const API =
        "/Other/grid-performance.php";

    const REGIONS_API =
        "/Other/regions.php";

    const WINDOW_KEY =
        "AustraliaGridStatisticsWindowV1";

    const SAMPLE_MS =
        60000;

    let installed =
        false;

    let posting =
        false;

    let rendering =
        false;

    let cache =
        new Map();


    function esc(value){

        return String(
            value == null
                ? ""
                : value
        )
        .replace(/&/g,"&amp;")
        .replace(/</g,"&lt;")
        .replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;")
        .replace(/'/g,"&#039;");

    }


    function formatNumber(value){

        const n =
            Number(value);

        if(!Number.isFinite(n)){

            return "\u2014";
        }

        return n.toLocaleString(
            "en-AU"
        );

    }


    function formatChange(value){

        const n =
            Number(value || 0);

        if(n > 0){

            return (
                "+" +
                n.toLocaleString(
                    "en-AU"
                )
            );
        }

        return n.toLocaleString(
            "en-AU"
        );

    }


    function changeClass(value){

        const n =
            Number(value || 0);

        if(n > 0){
            return "positive";
        }

        if(n < 0){
            return "negative";
        }

        return "neutral";
    }


    function currentWindow(){

        const value =
            Number(
                localStorage.getItem(
                    WINDOW_KEY
                ) || 86400
            );

        if(
            [
                3600,
                86400,
                604800,
                2592000
            ].includes(value)
        ){

            return value;
        }

        return 86400;
    }


    function windowLabel(value){

        if(value === 3600){
            return "1H";
        }

        if(value === 604800){
            return "7D";
        }

        if(value === 2592000){
            return "30D";
        }

        return "24H";
    }


    function extractRegions(data){

        if(Array.isArray(data)){

            return data;
        }

        if(
            data &&
            Array.isArray(
                data.regions
            )
        ){

            return data.regions;
        }

        return [];
    }


    function regionName(region){

        return String(
            region.RegionName ||
            region.regionName ||
            region.name ||
            ""
        ).trim();
    }


    function primCount(region){

        const values = [

            region.PrimCount,
            region.Prims,
            region.PrimitiveCount,
            region.TotalPrims,
            region.primCount,
            region.prims

        ];

        for(const value of values){

            const n =
                Number(value);

            if(
                Number.isFinite(n) &&
                n >= 0
            ){

                return Math.round(n);
            }
        }

        return null;
    }


    function regionStatus(region){

        return String(
            region.EffectiveStatus ||
            region.effectiveStatus ||
            region.Status ||
            region.status ||
            ""
        )
        .trim()
        .toLowerCase();
    }


    function safeSample(region){

        const status =
            regionStatus(region);

        if(!status){

            return true;
        }

        const bad = [

            "stopped",
            "offline",
            "shutdown",
            "crashed",
            "failed",
            "failure",
            "error",
            "booting",
            "starting",
            "restart",
            "restarting",
            "stopping",
            "recyclingdown"

        ];

        return !bad.some(
            word =>
                status.includes(word)
        );
    }


    async function recordPerformance(){

        if(posting){

            return;
        }

        posting =
            true;

        try{

            const response =
                await fetch(
                    REGIONS_API +
                    "?performance=2&nocache=" +
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

            const regions =
                [];

            for(
                const region
                of extractRegions(data)
            ){

                const name =
                    regionName(region);

                const prims =
                    primCount(region);

                if(
                    !name ||
                    prims === null ||
                    !safeSample(region)
                ){

                    continue;
                }

                regions.push(
                    {
                        name:name,
                        prims:prims
                    }
                );
            }

            if(!regions.length){

                console.debug(
                    "Region Performance: no valid region samples."
                );

                return;
            }

            const params =
                new URLSearchParams();

            params.set(
                "token",
                TOKEN
            );

            params.set(
                "nocache",
                String(Date.now())
            );

            const saveResponse =
                await fetch(
                    API +
                    "?" +
                    params.toString(),
                    {
                        method:
                            "POST",

                        credentials:
                            "same-origin",

                        cache:
                            "no-store",

                        headers:{
                            "Content-Type":
                                "application/json"
                        },

                        body:
                            JSON.stringify(
                                {
                                    regions:regions
                                }
                            )
                    }
                );

            const result =
                await saveResponse.json();

            if(
                result &&
                result.ok
            ){

                cache.clear();

                refreshVisible();
            }

        }
        catch(error){

            console.debug(
                "Region Performance record:",
                error
            );

        }
        finally{

            posting =
                false;
        }
    }


    async function getPerformance(
        windowSeconds,
        force
    ){

        if(
            !force &&
            cache.has(windowSeconds)
        ){

            return cache.get(
                windowSeconds
            );
        }

        const params =
            new URLSearchParams();

        params.set(
            "token",
            TOKEN
        );

        params.set(
            "window",
            String(windowSeconds)
        );

        params.set(
            "nocache",
            String(Date.now())
        );

        const response =
            await fetch(
                API +
                "?" +
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

            throw new Error(
                data &&
                data.error
                    ? data.error
                    : "Performance data unavailable"
            );
        }

        cache.set(
            windowSeconds,
            data
        );

        return data;
    }


    function normalise(value){

        return String(
            value || ""
        )
        .trim()
        .replace(/\s+/g," ")
        .toLowerCase();
    }


    function findRegionTile(name){

        const map =
            document.getElementById(
                "grid-map"
            );

        if(!map){

            return null;
        }

        const wanted =
            normalise(name);

        for(
            const tile
            of map.querySelectorAll(
                ".map-region"
            )
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


    function centreRegion(name){

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
                    .centreRegion(name);
            }

        }
        catch(error){
        }

        setTimeout(
            function(){

                const tile =
                    findRegionTile(name);

                if(tile){

                    tile.click();
                }

            },
            120
        );
    }


    function regionButton(name){

        return `

            <button
                type="button"
                class="gridmap-stats-region-link-v1"
                data-performance-region="${esc(name)}">

                ${esc(name)}

            </button>

        `;
    }


    function bindRegionButtons(holder){

        holder
            .querySelectorAll(
                "[data-performance-region]"
            )
            .forEach(
                button => {

                    button.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();

                            event.stopPropagation();

                            const region =
                                button.getAttribute(
                                    "data-performance-region"
                                );

                            if(region){

                                centreRegion(region);
                            }
                        }
                    );
                }
            );
    }


    async function renderPerformance(
        windowSeconds
    ){

        if(rendering){

            return;
        }

        const body =
            document.getElementById(
                "gridmap-stats-body-v1"
            );

        const button =
            document.querySelector(
                '[data-stats-tab="performance"]'
            );

        if(
            !body ||
            !button ||
            !button.classList.contains(
                "is-active"
            )
        ){

            return;
        }

        rendering =
            true;

        try{

            const data =
                await getPerformance(
                    windowSeconds,
                    true
                );

            const regions =
                Array.isArray(
                    data.regions
                )
                    ? data.regions
                    : [];

            if(!regions.length){

                body.innerHTML = `

                    <div
                        class="gridmap-performance-root-v2">

                        <div
                            class="gridmap-stats-empty-v1">

                            BUILDING REGION PERFORMANCE HISTORY
                            <br><br>

                            The first prim-count sample
                            is being recorded now.

                        </div>

                    </div>

                `;

                return;
            }

            let rows =
                "";

            for(const region of regions){

                const hasHistory =
                    Number(
                        region.sample_count || 0
                    ) >= 2;

                const change =
                    Number(
                        region.change || 0
                    );

                const changeText =
                    hasHistory
                        ? formatChange(change)
                        : "BUILDING";

                const changePercent =
                    hasHistory
                        ? (
                            " (" +
                            Number(
                                region.change_percent || 0
                            ).toFixed(1) +
                            "%)"
                          )
                        : "";

                const activity =
                    String(
                        region.activity ||
                        "LOW"
                    ).toUpperCase();

                rows += `

                    <tr>

                        <td>
                            ${regionButton(region.region)}
                        </td>

                        <td>
                            ${formatNumber(region.current_prims)}
                        </td>

                        <td>

                            <span
                                class="gridmap-performance-change-v2 ${
                                    hasHistory
                                        ? changeClass(change)
                                        : "neutral"
                                }">

                                ${esc(changeText)}
                                ${esc(changePercent)}

                            </span>

                            ${
                                region.large_change
                                    ? `
                                        <span
                                            class="gridmap-performance-warning-v2">
                                            LARGE CHANGE
                                        </span>
                                      `
                                    : ""
                            }

                        </td>

                        <td>
                            ${formatNumber(region.minimum_prims)}
                        </td>

                        <td>
                            ${formatNumber(region.maximum_prims)}
                        </td>

                        <td>
                            ${Number(region.avg_online || 0).toFixed(2)}
                        </td>

                        <td>
                            ${Number(region.peak_online || 0)}
                        </td>

                        <td>

                            <span
                                class="gridmap-performance-activity-v2 ${
                                    activity.toLowerCase()
                                }">

                                ${esc(activity)}

                            </span>

                        </td>

                    </tr>

                `;
            }

            body.innerHTML = `

                <div
                    class="gridmap-performance-root-v2">


                    <div
                        class="gridmap-performance-summary-v2">

                        <div
                            class="gridmap-performance-card-v2">

                            <span
                                class="gridmap-performance-label-v2">
                                HIGHEST PRIM LOAD
                            </span>

                            <span
                                class="gridmap-performance-value-v2 gold">

                                ${
                                    data.highest
                                        ? esc(
                                            data.highest.region
                                          )
                                        : "\u2014"
                                }

                            </span>

                            <span
                                class="gridmap-performance-note-v2">

                                ${
                                    data.highest
                                        ? formatNumber(
                                            data.highest
                                                .current_prims
                                          ) +
                                          " prims"
                                        : "Building history"
                                }

                            </span>

                        </div>


                        <div
                            class="gridmap-performance-card-v2">

                            <span
                                class="gridmap-performance-label-v2">
                                BIGGEST PRIM CHANGE
                            </span>

                            <span
                                class="gridmap-performance-value-v2">

                                ${
                                    data.biggest_change
                                        ? esc(
                                            data.biggest_change
                                                .region
                                          )
                                        : "\u2014"
                                }

                            </span>

                            <span
                                class="gridmap-performance-note-v2">

                                ${
                                    data.biggest_change
                                        ? formatChange(
                                            data.biggest_change
                                                .change
                                          ) +
                                          " prims"
                                        : "Need at least 2 samples"
                                }

                            </span>

                        </div>


                        <div
                            class="gridmap-performance-card-v2">

                            <span
                                class="gridmap-performance-label-v2">
                                HISTORY WINDOW
                            </span>

                            <span
                                class="gridmap-performance-value-v2">

                                ${windowLabel(windowSeconds)}

                            </span>

                            <span
                                class="gridmap-performance-note-v2">
                                5-minute historical buckets
                            </span>

                        </div>

                    </div>


                    <div
                        class="gridmap-stats-section-v1">

                        <div
                            class="gridmap-stats-section-title-v1">

                            REGION PERFORMANCE &#8226; ${windowLabel(windowSeconds)}

                        </div>


                        <table
                            class="gridmap-stats-table-v1">

                            <thead>

                                <tr>
                                    <th>REGION</th>
                                    <th>PRIMS</th>
                                    <th>CHANGE</th>
                                    <th>MIN</th>
                                    <th>MAX</th>
                                    <th>AVG AV</th>
                                    <th>PEAK AV</th>
                                    <th>ACTIVITY</th>
                                </tr>

                            </thead>

                            <tbody>
                                ${rows}
                            </tbody>

                        </table>

                    </div>

                </div>

            `;

            bindRegionButtons(body);

        }
        catch(error){

            body.innerHTML = `

                <div
                    class="gridmap-performance-root-v2">

                    <div
                        class="gridmap-stats-empty-v1">

                        Performance data could not be loaded.
                        <br><br>

                        ${esc(error.message)}

                    </div>

                </div>

            `;

        }
        finally{

            rendering =
                false;
        }
    }


    async function decorateOverview(){

        const overview =
            document.querySelector(
                '.gridmap-stats-tab-v1.is-active[data-stats-tab="overview"]'
            );

        if(!overview){

            return;
        }

        const body =
            document.getElementById(
                "gridmap-stats-body-v1"
            );

        if(!body){

            return;
        }

        const cards =
            body.querySelector(
                ".gridmap-stats-cards-v1"
            );

        if(
            !cards ||
            cards.querySelector(
                ".gridmap-performance-overview-v2"
            )
        ){

            return;
        }

        try{

            const data =
                await getPerformance(
                    currentWindow(),
                    false
                );

            const stillOverview =
                document.querySelector(
                    '.gridmap-stats-tab-v1.is-active[data-stats-tab="overview"]'
                );

            const currentCards =
                body.querySelector(
                    ".gridmap-stats-cards-v1"
                );

            if(
                !stillOverview ||
                !currentCards
            ){

                return;
            }

            const highest =
                document.createElement(
                    "div"
                );

            highest.className =
                "gridmap-stats-card-v1 gridmap-performance-overview-v2";

            highest.innerHTML = `

                <span
                    class="gridmap-stats-card-label-v1">
                    HIGHEST PRIM LOAD
                </span>

                <span
                    class="gridmap-stats-card-value-v1 gold">

                    ${
                        data.highest
                            ? esc(data.highest.region)
                            : "\u2014"
                    }

                </span>

                <span
                    class="gridmap-stats-card-note-v1">

                    ${
                        data.highest
                            ? formatNumber(
                                data.highest.current_prims
                              ) +
                              " prims"
                            : "Building history"
                    }

                </span>

            `;

            const biggest =
                document.createElement(
                    "div"
                );

            biggest.className =
                "gridmap-stats-card-v1 gridmap-performance-overview-v2";

            biggest.innerHTML = `

                <span
                    class="gridmap-stats-card-label-v1">
                    BIGGEST PRIM CHANGE
                </span>

                <span
                    class="gridmap-stats-card-value-v1">

                    ${
                        data.biggest_change
                            ? esc(
                                data.biggest_change.region
                              )
                            : "\u2014"
                    }

                </span>

                <span
                    class="gridmap-stats-card-note-v1">

                    ${
                        data.biggest_change
                            ? formatChange(
                                data.biggest_change.change
                              ) +
                              " prims"
                            : "Need at least 2 samples"
                    }

                </span>

            `;

            currentCards.appendChild(
                highest
            );

            currentCards.appendChild(
                biggest
            );

            if(data.highest){

                highest.style.cursor =
                    "pointer";

                highest.addEventListener(
                    "click",
                    function(){

                        centreRegion(
                            data.highest.region
                        );
                    }
                );
            }

            if(data.biggest_change){

                biggest.style.cursor =
                    "pointer";

                biggest.addEventListener(
                    "click",
                    function(){

                        centreRegion(
                            data.biggest_change.region
                        );
                    }
                );
            }

        }
        catch(error){

            console.debug(
                "Performance overview:",
                error
            );
        }
    }


    function refreshVisible(){

        const performance =
            document.querySelector(
                '.gridmap-stats-tab-v1.is-active[data-stats-tab="performance"]'
            );

        if(performance){

            renderPerformance(
                currentWindow()
            );

            return;
        }

        decorateOverview();
    }


    function installPerformance(){

        if(installed){

            return true;
        }

        const control =
            document.getElementById(
                "gridmap-stats-control-v1"
            );

        const tabs =
            control
                ? control.querySelector(
                    ".gridmap-stats-tabs-v1"
                  )
                : null;

        const body =
            document.getElementById(
                "gridmap-stats-body-v1"
            );

        const traffic =
            tabs
                ? tabs.querySelector(
                    '[data-stats-tab="traffic"]'
                  )
                : null;

        if(
            !control ||
            !tabs ||
            !body ||
            !traffic
        ){

            return false;
        }


        let performance =
            tabs.querySelector(
                '[data-stats-tab="performance"]'
            );


        if(!performance){

            performance =
                document.createElement(
                    "button"
                );

            performance.type =
                "button";

            performance.className =
                "gridmap-stats-tab-v1";

            performance.setAttribute(
                "data-stats-tab",
                "performance"
            );

            performance.textContent =
                "PERFORMANCE";

            traffic.insertAdjacentElement(
                "afterend",
                performance
            );
        }


        performance.addEventListener(
            "click",
            function(event){

                event.preventDefault();

                event.stopPropagation();

                tabs
                    .querySelectorAll(
                        "[data-stats-tab]"
                    )
                    .forEach(
                        button =>
                            button.classList.remove(
                                "is-active"
                            )
                    );

                performance.classList.add(
                    "is-active"
                );

                renderPerformance(
                    currentWindow()
                );
            }
        );


        /*
         * If the normal STATS time buttons are used while
         * PERFORMANCE is selected, restore PERFORMANCE after
         * the original STATS handler changes the window.
         */

        control
            .querySelectorAll(
                "[data-stats-window]"
            )
            .forEach(
                button => {

                    button.addEventListener(
                        "click",
                        function(){

                            const wasPerformance =
                                performance.classList.contains(
                                    "is-active"
                                );

                            if(!wasPerformance){

                                return;
                            }

                            setTimeout(
                                function(){

                                    tabs
                                        .querySelectorAll(
                                            "[data-stats-tab]"
                                        )
                                        .forEach(
                                            item =>
                                                item.classList.remove(
                                                    "is-active"
                                                )
                                        );

                                    performance.classList.add(
                                        "is-active"
                                    );

                                    renderPerformance(
                                        currentWindow()
                                    );

                                },
                                100
                            );
                        }
                    );
                }
            );


        /*
         * Existing STATS refreshes can redraw the body.
         * If PERFORMANCE is selected, put its contents back.
         */

        let bodyTimer =
            null;

        const observer =
            new MutationObserver(
                function(){

                    clearTimeout(
                        bodyTimer
                    );

                    bodyTimer =
                        setTimeout(
                            function(){

                                if(
                                    performance.classList.contains(
                                        "is-active"
                                    )
                                ){

                                    if(
                                        !body.querySelector(
                                            ".gridmap-performance-root-v2"
                                        )
                                    ){

                                        renderPerformance(
                                            currentWindow()
                                        );
                                    }

                                    return;
                                }

                                decorateOverview();

                            },
                            80
                        );
                }
            );

        observer.observe(
            body,
            {
                childList:true,
                subtree:false
            }
        );


        installed =
            true;

        setTimeout(
            decorateOverview,
            500
        );

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
                        installPerformance() ||
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


    /*
     * Record immediately and then once per minute.
     */

    setTimeout(
        recordPerformance,
        1800
    );

    setInterval(
        recordPerformance,
        SAMPLE_MS
    );

})();

/* END Grid MAP - REGION PERFORMANCE CLIENT V2 */

</script>





































<style>

/* ============================================================
   AUSTRALIA ADMIN MAP - CLEAN TEXTURE ENGINE V3

   ONE layout system.

   NO wheel listeners.
   NO zoom listeners.
   NO repeated texture repositioning.

   Texture lives inside a fixed stage inside its own card.
   ============================================================ */


#grid-map
.map-region.australia-admin-card-v3{

    box-sizing:
        border-box !important;

    overflow:
        hidden !important;

    padding:
        0 !important;

    isolation:
        isolate;

}


/* ============================================================
   TITLE BAR
   ============================================================ */

#grid-map
.map-region.australia-admin-card-v3
> .map-region-header{

    position:
        absolute !important;

    left:
        0 !important;

    right:
        0 !important;

    top:
        0 !important;

    height:
        25px !important;

    box-sizing:
        border-box !important;

    display:
        flex !important;

    align-items:
        center !important;

    margin:
        0 !important;

    padding:
        4px 7px !important;

    z-index:
        30 !important;

    background:
        rgba(4,11,17,.95) !important;

    border-bottom:
        1px solid rgba(65,178,218,.35);

}


/* ============================================================
   STATS BAR
   ============================================================ */

#grid-map
.map-region.australia-admin-card-v3
> .map-region-body{

    position:
        absolute !important;

    left:
        0 !important;

    right:
        0 !important;

    bottom:
        0 !important;

    height:
        29px !important;

    min-height:
        29px !important;

    box-sizing:
        border-box !important;

    display:
        flex !important;

    align-items:
        center !important;

    justify-content:
        center !important;

    gap:
        5px !important;

    margin:
        0 !important;

    padding:
        3px 5px !important;

    z-index:
        30 !important;

    background:
        rgba(4,11,17,.94) !important;

    border-top:
        1px solid rgba(65,178,218,.30);

}


/* ============================================================
   FIXED TEXTURE STAGE

   THIS is the important change.

   The OpenSim composite never positions itself independently
   against the card anymore.
   ============================================================ */

#grid-map
.australia-admin-texture-stage-v3{

    position:
        absolute;

    overflow:
        hidden;

    box-sizing:
        border-box;

    z-index:
        10;

    margin:
        0;

    padding:
        0;

    border:
        1px solid rgba(65,178,218,.38);

    border-radius:
        4px;

    background:
        #03080c;

    box-shadow:
        0 2px 7px rgba(0,0,0,.34);

    pointer-events:
        none;

    user-select:
        none;

}


/* ============================================================
   LIVE COMPOSITE

   It fills the STAGE.
   It has NO left/top coordinates of its own.
   ============================================================ */

#grid-map
.australia-admin-composite-v3{

    position:
        absolute;

    inset:
        0;

    width:
        100%;

    height:
        100%;

    display:
        grid;

    overflow:
        hidden;

    margin:
        0;

    padding:
        0;

    border:
        0;

    transform:
        none;

    pointer-events:
        none;

}


/* Individual OpenSim cells */

#grid-map
.australia-admin-composite-v3
> img{

    display:
        block;

    width:
        100%;

    height:
        100%;

    min-width:
        0;

    min-height:
        0;

    margin:
        0;

    padding:
        0;

    border:
        0;

    object-fit:
        fill;

    pointer-events:
        none;

    user-select:
        none;

    -webkit-user-drag:
        none;

}


/* ============================================================
   OPTIONAL CUSTOM PNG

   Same stage.
   Same dimensions.
   Cannot move separately.
   ============================================================ */

#grid-map
.australia-admin-custom-v3{

    position:
        absolute;

    inset:
        0;

    display:
        block;

    width:
        100%;

    height:
        100%;

    margin:
        0;

    padding:
        0;

    border:
        0;

    object-fit:
        fill;

    transform:
        none;

    pointer-events:
        none;

    user-select:
        none;

    -webkit-user-drag:
        none;

}


/* END AUSTRALIA ADMIN MAP - CLEAN TEXTURE ENGINE V3 */
</style>


<script>

/* ============================================================
   AUSTRALIA ADMIN MAP - CLEAN TEXTURE ENGINE CLIENT V3

   IMPORTANT:

   The map's own Firestorm pan/zoom transforms #grid-map.

   This code DOES NOT listen to zoom.

   Card + stage + texture therefore transform together as a
   single DOM hierarchy.

   ============================================================ */

(function(){

    "use strict";


    const DATA_URL =
        "/Other/grid-map-data.php";


    const TILE_BASE =
        <?php echo json_encode(rtrim(ag_dg_login_url(), '/'), JSON_UNESCAPED_SLASHES); ?>;


    const CUSTOM_BASE =
        "/Other/images/region-tiles";


    const CELL =
        42;


    const HEADER =
        25;


    const FOOTER =
        29;


    const GAP_TOP =
        3;


    const GAP_BOTTOM =
        3;


    const EXTRA_WIDTH =
        90;


    const CARD_CLASS =
        "australia-admin-card-v3";


    const STAGE_CLASS =
        "australia-admin-texture-stage-v3";


    const COMPOSITE_CLASS =
        "australia-admin-composite-v3";


    const CUSTOM_CLASS =
        "australia-admin-custom-v3";


    let regionLookup =
        new Map();


    let refreshTimer =
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



    function slug(
        value
    ){

        let text =
            normalise(
                value
            );


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



    function tileUrl(
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



    /* ========================================================
       READ CURRENT CARD CENTRE BEFORE RESIZING.

       Only done once for each freshly-rendered card.
       ======================================================== */

    function getOriginalCentre(
        card
    ){

        if(
            card.dataset.adminV3CentreX &&
            card.dataset.adminV3CentreY
        ){

            return {

                x:
                    Number(
                        card.dataset.adminV3CentreX
                    ),

                y:
                    Number(
                        card.dataset.adminV3CentreY
                    )

            };

        }


        const computed =
            window.getComputedStyle(
                card
            );


        const left =
            parseFloat(
                card.style.left ||
                computed.left
            );


        const top =
            parseFloat(
                card.style.top ||
                computed.top
            );


        const width =
            card.offsetWidth;


        const height =
            card.offsetHeight;


        if(
            !Number.isFinite(left) ||
            !Number.isFinite(top) ||
            width <= 0 ||
            height <= 0
        ){

            return null;

        }


        const centreX =
            left +
            (
                width /
                2
            );


        const centreY =
            top +
            (
                height /
                2
            );


        card.dataset.adminV3CentreX =
            String(
                centreX
            );


        card.dataset.adminV3CentreY =
            String(
                centreY
            );


        return {

            x:
                centreX,

            y:
                centreY

        };

    }



    /* ========================================================
       BUILD ONE CARD
       ======================================================== */

    function buildCard(
        card
    ){

        const region =
            regionLookup.get(
                normalise(
                    card.dataset.region
                )
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


        const centre =
            getOriginalCentre(
                card
            );


        if(
            !centre
        ){

            return;

        }


        const textureWidth =
            cellsX *
            CELL;


        const textureHeight =
            cellsY *
            CELL;


        const cardWidth =
            textureWidth +
            EXTRA_WIDTH;


        const cardHeight =
            HEADER +
            GAP_TOP +
            textureHeight +
            GAP_BOTTOM +
            FOOTER;


        const left =
            centre.x -
            (
                cardWidth /
                2
            );


        const top =
            centre.y -
            (
                cardHeight /
                2
            );


        card.classList.add(
            CARD_CLASS
        );


        /*
           Card position and size are established ONCE.

           Zoom does not touch them.
        */

        card.style.setProperty(
            "left",
            left + "px",
            "important"
        );


        card.style.setProperty(
            "top",
            top + "px",
            "important"
        );


        card.style.setProperty(
            "width",
            cardWidth + "px",
            "important"
        );


        card.style.setProperty(
            "height",
            cardHeight + "px",
            "important"
        );


        /* ====================================================
           REMOVE ONLY OUR V3 STAGE IF REBUILDING
           ==================================================== */

        const oldStage =
            card.querySelector(
                ":scope > ." +
                STAGE_CLASS
            );


        if(
            oldStage
        ){

            oldStage.remove();

        }


        /* ====================================================
           CREATE FIXED STAGE
           ==================================================== */

        const stage =
            document.createElement(
                "div"
            );


        stage.className =
            STAGE_CLASS;


        /*
           Stage is permanently attached to card coordinates.

           It never uses translate().
           It never responds to zoom.
        */

        stage.style.left =
            Math.round(
                (
                    cardWidth -
                    textureWidth
                ) /
                2
            ) +
            "px";


        stage.style.top =
            (
                HEADER +
                GAP_TOP
            ) +
            "px";


        stage.style.width =
            textureWidth +
            "px";


        stage.style.height =
            textureHeight +
            "px";


        /* ====================================================
           CREATE LIVE COMPOSITE
           ==================================================== */

        const composite =
            document.createElement(
                "div"
            );


        composite.className =
            COMPOSITE_CLASS;


        composite.style.gridTemplateColumns =
            "repeat(" +
            cellsX +
            ",1fr)";


        composite.style.gridTemplateRows =
            "repeat(" +
            cellsY +
            ",1fr)";


        /*
           OpenSim north = higher Y.

           Highest Y must be top HTML row.
        */

        for(
            let y =
                baseY +
                cellsY -
                1;

            y >= baseY;

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
                    tileUrl(
                        x,
                        y
                    );


                composite.appendChild(
                    image
                );

            }

        }


        stage.appendChild(
            composite
        );


        /* ====================================================
           OPTIONAL CUSTOM PNG
           ==================================================== */

        const filename =
            slug(
                region.RegionName
            );


        if(
            filename
        ){

            const custom =
                document.createElement(
                    "img"
                );


            custom.className =
                CUSTOM_CLASS;


            custom.alt =
                "";


            custom.draggable =
                false;


            custom.style.visibility =
                "hidden";


            custom.setAttribute(
                "aria-hidden",
                "true"
            );


            custom.addEventListener(
                "load",
                function(){

                    if(
                        !document.body.contains(
                            stage
                        )
                    ){

                        return;

                    }


                    composite.style.visibility =
                        "hidden";


                    custom.style.visibility =
                        "visible";

                }
            );


            custom.addEventListener(
                "error",
                function(){

                    composite.style.visibility =
                        "visible";


                    custom.remove();

                }
            );


            custom.src =
                "/Other/region-map-custom-image.php?region=" +
                encodeURIComponent(
                    region.RegionName
                ) +
                "&adminV3=1&_=" +
                Date.now();


            stage.appendChild(
                custom
            );

        }


        /*
           Stage becomes part of card.

           From this point:
               card -> stage -> texture

           All three transform together under map zoom.
        */

        card.insertBefore(
            stage,
            card.firstChild
        );

    }



    /* ========================================================
       APPLY TO ALL CURRENT CARDS
       ======================================================== */

    function buildAll(){

        if(
            regionLookup.size === 0
        ){

            return;

        }


        document
            .querySelectorAll(
                "#grid-map .map-region"
            )
            .forEach(
                buildCard
            );

    }



    /* ========================================================
       LOAD GEOMETRY
       ======================================================== */

    async function loadRegions(){

        try{

            const response =
                await fetch(
                    DATA_URL +
                    "?adminTextureEngineV3=1&_=" +
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

                console.warn(
                    "Admin Texture V3: data request failed."
                );

                return;

            }


            const data =
                await response.json();


            const list =
                Array.isArray(
                    data.regions
                )
                    ? data.regions
                    : [];


            regionLookup =
                new Map();


            list.forEach(
                function(region){

                    regionLookup.set(
                        normalise(
                            region.RegionName
                        ),
                        region
                    );

                }
            );


            window.setTimeout(
                buildAll,
                120
            );

        }
        catch(error){

            console.warn(
                "Admin Texture V3:",
                error
            );

        }

    }



    function scheduleRefresh(){

        window.clearTimeout(
            refreshTimer
        );


        refreshTimer =
            window.setTimeout(
                loadRegions,
                150
            );

    }



    /* ========================================================
       ONLY WATCH FOR ACTUAL REGION CARD REDRAWS.

       NOT subtree.
       NOT style changes.
       NOT wheel.
       NOT zoom.
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

                    let regionCardsChanged =
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

                                        regionCardsChanged =
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

                                        regionCardsChanged =
                                            true;

                                    }

                                }
                            );

                        }
                    );


                    if(
                        regionCardsChanged
                    ){

                        scheduleRefresh();

                    }

                }
            );


        observer.observe(
            map,
            {
                childList:
                    true
            }
        );

    }



    /*
       Initial build only.

       NO zoom handling here on purpose.
    */

    /*
     * ========================================================
     * AUSTRALIA ADMIN 2D MAP LIVE TEXTURE REFRESH V1
     * ========================================================
     *
     * Region Map Textures sends a change event after a
     * successful upload / replace / restore.
     *
     * Rebuild ONLY that region card.
     *
     * No full map refresh.
     * No loadRegions().
     * No rebuild of unrelated regions.
     * No involvement with the 3D Map.
     * ========================================================
     */

    const RMT_LIVE_CHANNEL =
        "australia-rmt-map-change-v1";


    const RMT_LIVE_STORAGE_KEY =
        "australia-rmt-map-change-v1";


    function refreshOneRmtRegion(
        regionName
    ){

        const key =
            normalise(
                regionName
            );


        if(
            !key ||
            !regionLookup.has(
                key
            )
        ){

            return false;
        }


        let rebuilt =
            false;


        document
            .querySelectorAll(
                "#grid-map .map-region"
            )
            .forEach(
                function(card){

                    if(
                        normalise(
                            card.dataset.region
                        ) !== key
                    ){

                        return;
                    }


                    buildCard(
                        card
                    );


                    rebuilt =
                        true;
                }
            );


        return rebuilt;
    }


    function consumeRmtTextureChange(
        message
    ){

        if(
            !message ||
            typeof message !==
                "object"
        ){

            return;
        }


        const name =
            String(
                message.region_name ||
                ""
            );


        if(
            !name
        ){

            return;
        }


        /*
         * Usually the map is already fully built.
         *
         * A few short retries also cover a change arriving
         * while the map is still completing its initial load.
         */
        let attempts =
            0;


        function attempt(){

            attempts++;


            if(
                refreshOneRmtRegion(
                    name
                )
            ){

                return;
            }


            if(
                attempts < 4
            ){

                window.setTimeout(
                    attempt,
                    attempts * 200
                );
            }
        }


        attempt();
    }


    if(
        "BroadcastChannel"
        in window
    ){

        try{

            const rmtLiveChannel =
                new BroadcastChannel(
                    RMT_LIVE_CHANNEL
                );


            rmtLiveChannel.addEventListener(
                "message",
                function(event){

                    consumeRmtTextureChange(
                        event.data
                    );
                }
            );

        }
        catch(error){
        }
    }


    window.addEventListener(
        "storage",
        function(event){

            if(
                event.key !==
                    RMT_LIVE_STORAGE_KEY ||
                !event.newValue
            ){

                return;
            }


            try{

                consumeRmtTextureChange(
                    JSON.parse(
                        event.newValue
                    )
                );

            }
            catch(error){
            }
        }
    );

    loadRegions();


    window.setTimeout(
        buildAll,
        500
    );


    window.setTimeout(
        buildAll,
        1000
    );


})();

/* END AUSTRALIA ADMIN MAP - CLEAN TEXTURE ENGINE CLIENT V3 */

</script>

<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>


<!-- AUSTRALIA MAP SHARED CONTROLS V4 START -->

<style id="australia-map-shared-controls-v4">

.firestorm-map-button
.ag-map-arrow-v4 {

    display:inline-flex;

    align-items:center;
    justify-content:center;

    width:22px;
    height:22px;

    color:#f0c653;

    font-family:Arial,sans-serif;

    font-size:23px;
    font-weight:900;

    line-height:1;

    text-shadow:
        0 1px 3px rgba(0,0,0,.95);

    pointer-events:none;
}


#australia-detail-center-v4 {
    white-space:nowrap;
}


.ag-map-v1-toolbar
#gridmap-fullscreen-enter {

    margin-left:4px !important;
    margin-right:0 !important;

    min-height:30px !important;
    height:30px !important;

    padding:0 14px !important;
}


.ag-map-v1-toolbar
.gridmap-toolbar-left {

    display:flex !important;
    align-items:center !important;
    flex-wrap:wrap !important;

    gap:10px !important;
}

</style>


<script id="australia-map-shared-controls-v4-script">

(function(){

    let centerButton = null;


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

                window
                    .AustraliaGridMapView
                    .centreRegion(
                        tile
                    );
            }
        }
        catch(error){

            console.debug(
                "CENTER MAP:",
                error
            );
        }
    }


    function updateCenterButton(){

        if(!centerButton){
            return;
        }

        centerButton.disabled =
            !selectedTile();
    }


    function installCenterButton(){

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


        const existing =
            document.getElementById(
                "australia-detail-center-v4"
            );

        if(existing){

            centerButton =
                existing;

            updateCenterButton();

            return true;
        }


        centerButton =
            document.createElement(
                "button"
            );

        centerButton.type =
            "button";

        centerButton.id =
            "australia-detail-center-v4";

        centerButton.className =
            "gridmap-hop-button";

        centerButton.textContent =
            "CENTER MAP";

        centerButton.disabled =
            true;


        actions.insertBefore(
            centerButton,
            hopButton
        );


        centerButton.addEventListener(
            "click",
            function(event){

                event.preventDefault();
                event.stopPropagation();

                centerSelectedRegion();
            }
        );


        updateCenterButton();

        return true;
    }


    function moveFullscreen(){

        const button =
            document.getElementById(
                "gridmap-fullscreen-enter"
            );

        const toolbarLeft =
            document.querySelector(
                ".ag-map-v1-toolbar .gridmap-toolbar-left"
            );

        if(
            !button ||
            !toolbarLeft
        ){
            return false;
        }


        if(
            button.parentElement !==
            toolbarLeft
        ){

            toolbarLeft.appendChild(
                button
            );
        }


        return true;
    }


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
                    updateCenterButton,
                    60
                );

                setTimeout(
                    updateCenterButton,
                    180
                );
            }
        );


        const observer =
            new MutationObserver(
                function(){

                    updateCenterButton();
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


    function start(){

        installCenterButton();
        moveFullscreen();


        let attempts = 0;


        const timer =
            setInterval(
                function(){

                    attempts++;


                    const centerReady =
                        installCenterButton();

                    const fullReady =
                        moveFullscreen();


                    if(
                        (
                            centerReady &&
                            fullReady
                        ) ||
                        attempts >= 50
                    ){

                        clearInterval(
                            timer
                        );
                    }

                },
                100
            );


        watchSelection();
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

</script>

<!-- AUSTRALIA MAP SHARED CONTROLS V4 END -->



<!-- AUSTRALIA MAP TOP BUTTONS V5 START -->

<style id="australia-map-top-buttons-v5-style">

/* ==========================================================
   ADMIN + USER MAP
   TOP TOOLBAR BUTTONS V5
   ========================================================== */

#australia-map-top-actions-v5 {

    display:flex;

    align-items:center;

    gap:7px;

    flex-wrap:wrap;

    margin-left:4px;
}


/*
 * CENTER MAP + HOP TO REGION now live in the top toolbar.
 */

#australia-map-top-actions-v5
.gridmap-hop-button {

    min-height:30px !important;
    height:30px !important;

    padding:
        0 13px !important;

    margin:
        0 !important;

    display:inline-flex !important;

    align-items:center !important;
    justify-content:center !important;

    white-space:nowrap;
}


/*
 * FULL SCREEN stays in top toolbar.
 */

.ag-map-v1-toolbar
#gridmap-fullscreen-enter {

    min-height:30px !important;
    height:30px !important;

    margin:
        0 !important;

    padding:
        0 13px !important;
}


.ag-map-v1-toolbar
.gridmap-toolbar-left {

    display:flex !important;

    align-items:center !important;

    flex-wrap:wrap !important;

    gap:9px !important;
}


/*
 * Once the buttons have moved out, don't leave a large
 * empty action area at the bottom of Region Details.
 */

.gridmap-hop-actions {

    min-height:0 !important;
}


/* END ADMIN + USER MAP TOP TOOLBAR BUTTONS V5 */

</style>


<script id="australia-map-top-buttons-v5-script">

(function(){

    let centerButton = null;


    /* ========================================================
       CURRENT SELECTED REGION
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

                window
                    .AustraliaGridMapView
                    .centreRegion(
                        tile
                    );

            }

        }
        catch(error){

            console.debug(
                "CENTER MAP:",
                error
            );

        }

    }


    /* ========================================================
       FIND OR CREATE CENTER MAP BUTTON
       ======================================================== */

    function ensureCenterButton(){

        centerButton =

            document.getElementById(
                "australia-detail-center-v4"
            ) ||

            document.getElementById(
                "australia-user-detail-center-v3"
            ) ||

            document.getElementById(
                "australia-detail-center-v5"
            );


        if(centerButton){

            centerButton.disabled =
                !selectedTile();

            return centerButton;
        }


        centerButton =
            document.createElement(
                "button"
            );


        centerButton.type =
            "button";


        centerButton.id =
            "australia-detail-center-v5";


        centerButton.className =
            "gridmap-hop-button";


        centerButton.textContent =
            "CENTER MAP";


        centerButton.disabled =
            true;


        centerButton.addEventListener(
            "click",
            function(event){

                event.preventDefault();
                event.stopPropagation();

                centerSelectedRegion();

            }
        );


        return centerButton;

    }


    /* ========================================================
       TOP TOOLBAR CONTAINER
       ======================================================== */

    function ensureTopActions(){

        const toolbarLeft =
            document.querySelector(
                ".ag-map-v1-toolbar .gridmap-toolbar-left"
            );


        if(!toolbarLeft){
            return null;
        }


        let actions =
            document.getElementById(
                "australia-map-top-actions-v5"
            );


        if(!actions){

            actions =
                document.createElement(
                    "div"
                );


            actions.id =
                "australia-map-top-actions-v5";


            toolbarLeft.appendChild(
                actions
            );

        }


        return actions;

    }


    /* ========================================================
       MOVE ALL ACTION BUTTONS UP
       ======================================================== */

    function moveButtonsToTop(){

        const toolbarLeft =
            document.querySelector(
                ".ag-map-v1-toolbar .gridmap-toolbar-left"
            );


        if(!toolbarLeft){
            return false;
        }


        const topActions =
            ensureTopActions();


        if(!topActions){
            return false;
        }


        const center =
            ensureCenterButton();


        const hop =
            document.getElementById(
                "gridmap-hop-button"
            );


        const fullscreen =
            document.getElementById(
                "gridmap-fullscreen-enter"
            );


        /*
         * FULL SCREEN stays on the upper toolbar.
         */

        if(
            fullscreen &&
            fullscreen.parentElement !==
            toolbarLeft
        ){

            toolbarLeft.appendChild(
                fullscreen
            );

        }


        /*
         * CENTER MAP and HOP TO REGION move together
         * into the upper toolbar.
         */

        if(
            center &&
            center.parentElement !==
            topActions
        ){

            topActions.appendChild(
                center
            );

        }


        if(
            hop &&
            hop.parentElement !==
            topActions
        ){

            topActions.appendChild(
                hop
            );

        }


        return !!(
            center &&
            hop
        );

    }


    /* ========================================================
       UPDATE CENTER BUTTON
       ======================================================== */

    function updateCenterButton(){

        const center =
            ensureCenterButton();


        if(center){

            center.disabled =
                !selectedTile();

        }

    }


    /* ========================================================
       WATCH REGION SELECTION
       ======================================================== */

    function watchMap(){

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
                    updateCenterButton,
                    50
                );


                setTimeout(
                    updateCenterButton,
                    160
                );

            }
        );


        const observer =
            new MutationObserver(
                function(){

                    updateCenterButton();

                    moveButtonsToTop();

                }
            );


        observer.observe(
            map,
            {
                subtree:true,
                childList:true,
                attributes:true,
                attributeFilter:[
                    "class"
                ]
            }
        );

    }


    /* ========================================================
       START
       ======================================================== */

    function start(){

        moveButtonsToTop();

        watchMap();


        /*
         * Existing map scripts create some controls slightly
         * later, so retry until everything is in position.
         */

        let attempts =
            0;


        const timer =
            setInterval(
                function(){

                    attempts++;


                    const ready =
                        moveButtonsToTop();


                    updateCenterButton();


                    if(
                        ready ||
                        attempts >= 60
                    ){

                        clearInterval(
                            timer
                        );

                    }

                },
                100
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

</script>

<!-- AUSTRALIA MAP TOP BUTTONS V5 END -->



<!-- AUSTRALIA HOP MODAL V7 START -->

<style id="australia-hop-modal-v7-style">

#australia-hop-modal-v7 {

    position:fixed;

    inset:0;

    z-index:2147483646;

    display:none;

    align-items:center;
    justify-content:center;

    padding:28px;

    background:
        rgba(0,0,0,.72);

    backdrop-filter:
        blur(5px);

}


#australia-hop-modal-v7.is-open {

    display:flex;

}


#australia-hop-box-v7 {

    width:min(
        520px,
        calc(100vw - 40px)
    );

    overflow:hidden;

    border:
        1px solid rgba(213,163,41,.94);

    border-radius:
        13px;

    background:
        linear-gradient(
            180deg,
            rgba(10,20,23,.99),
            rgba(4,11,14,.99)
        );

    box-shadow:
        0 26px 70px rgba(0,0,0,.78),
        inset 0 0 0 1px rgba(0,150,170,.16);

    color:#dce8eb;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}


.australia-hop-head-v7 {

    display:flex;

    align-items:center;
    justify-content:space-between;

    gap:15px;

    padding:
        16px 18px;

    border-bottom:
        1px solid rgba(213,163,41,.25);

    background:
        linear-gradient(
            180deg,
            rgba(24,32,31,.98),
            rgba(11,18,18,.98)
        );

}


.australia-hop-title-wrap-v7 {

    display:flex;

    align-items:center;

    gap:12px;

}


.australia-hop-icon-v7 {

    width:34px;
    height:34px;

    object-fit:contain;

}


.australia-hop-title-v7 {

    color:#f2c653;

    font-size:15px;

    font-weight:900;

    letter-spacing:.08em;

}


.australia-hop-subtitle-v7 {

    margin-top:3px;

    color:#748991;

    font-size:9px;

    font-weight:800;

    letter-spacing:.12em;

}


#australia-hop-close-v7 {

    width:34px;
    height:34px;

    border:
        1px solid rgba(255,255,255,.13);

    border-radius:7px;

    background:
        linear-gradient(
            180deg,
            #263138,
            #12191d
        );

    color:#d9e1e4;

    font-size:18px;

    font-weight:900;

    cursor:pointer;

}


#australia-hop-close-v7:hover {

    border-color:
        #d5a329;

    color:
        #ffd66d;

}


.australia-hop-body-v7 {

    padding:
        20px 20px 18px;

}


.australia-hop-label-v7 {

    color:#667a83;

    font-size:9px;

    font-weight:900;

    letter-spacing:.13em;

}


#australia-hop-region-v7 {

    margin-top:7px;

    color:#ffffff;

    font-size:23px;

    font-weight:900;

    letter-spacing:.025em;

}


.australia-hop-destination-v7 {

    margin-top:17px;

    padding:
        12px 14px;

    border:
        1px solid rgba(20,139,158,.32);

    border-radius:9px;

    background:
        rgba(0,0,0,.25);

}


.australia-hop-destination-v7 strong {

    display:block;

    margin-bottom:5px;

    color:#e5bd52;

    font-size:10px;

    letter-spacing:.09em;

}


#australia-hop-link-v7 {

    display:block;

    overflow:hidden;

    color:#a9bdc5;

    font-size:10px;

    line-height:1.45;

    text-overflow:ellipsis;

    white-space:nowrap;

}


.australia-hop-notice-v7 {

    display:flex;

    gap:10px;

    margin-top:16px;

    padding:
        11px 13px;

    border:
        1px solid rgba(213,163,41,.22);

    border-radius:8px;

    background:
        rgba(213,163,41,.055);

    color:#aebdc2;

    font-size:10px;

    line-height:1.5;

}


.australia-hop-notice-icon-v7 {

    color:#f0c653;

    font-size:17px;

    font-weight:900;

}


.australia-hop-actions-v7 {

    display:flex;

    align-items:center;
    justify-content:flex-end;

    gap:9px;

    flex-wrap:wrap;

    padding:
        13px 18px;

    border-top:
        1px solid rgba(255,255,255,.075);

    background:
        rgba(0,0,0,.20);

}


.australia-hop-button-v7 {

    min-height:36px;

    padding:
        0 17px;

    border:
        1px solid rgba(255,255,255,.14);

    border-radius:7px;

    background:
        linear-gradient(
            180deg,
            #28343b,
            #141c20
        );

    color:#e1e8ea;

    font-size:10px;

    font-weight:900;

    letter-spacing:.055em;

    cursor:pointer;

}


.australia-hop-button-v7:hover {

    border-color:#d5a329;

    color:#ffe397;

}


#australia-hop-open-v7 {

    border-color:
        rgba(213,163,41,.64);

    background:
        linear-gradient(
            180deg,
            rgba(76,57,20,.98),
            rgba(30,23,12,.98)
        );

    color:#f4d36c;

}


#australia-hop-copy-result-v7 {

    min-height:16px;

    margin-right:auto;

    color:#69e090;

    font-size:9px;

    font-weight:800;

    letter-spacing:.05em;

}


@media(max-width:600px){

    .australia-hop-actions-v7 {

        justify-content:stretch;

    }

    .australia-hop-button-v7 {

        flex:1 1 auto;

    }

}

</style>


<div
    id="australia-hop-modal-v7"
    role="dialog"
    aria-modal="true"
    aria-labelledby="australia-hop-title-v7">

    <div id="australia-hop-box-v7">

        <div class="australia-hop-head-v7">

            <div class="australia-hop-title-wrap-v7">

                <img
                    class="australia-hop-icon-v7"
                    src="/Other/assets/icons/sentinel/map.png"
                    alt="">

                <div>

                    <div
                        id="australia-hop-title-v7"
                        class="australia-hop-title-v7">
                        HOP TO REGION
                    </div>

                    <div
                        class="australia-hop-subtitle-v7">
                        <?=htmlspecialchars(ag_grid_name(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?> &#183; FIRESTORM TELEPORT
                    </div>

                </div>

            </div>

            <button
                type="button"
                id="australia-hop-close-v7"
                title="Close">
                ×
            </button>

        </div>


        <div class="australia-hop-body-v7">

            <div class="australia-hop-label-v7">
                SELECTED REGION
            </div>

            <div id="australia-hop-region-v7">
                REGION
            </div>


            <div class="australia-hop-destination-v7">

                <strong>
                    HOP ADDRESS
                </strong>

                <span id="australia-hop-link-v7"></span>

            </div>


            <div class="australia-hop-notice-v7">

                <div class="australia-hop-notice-icon-v7">
                    !
                </div>

                <div>
                    OPEN FIRESTORM sends this destination to your
                    installed viewer. Your browser may ask permission
                    the first time. Tick
                    <strong>Always allow localhost</strong>
                    in that browser message to stop it asking again.
                </div>

            </div>

        </div>


        <div class="australia-hop-actions-v7">

            <span id="australia-hop-copy-result-v7"></span>

            <button
                type="button"
                id="australia-hop-cancel-v7"
                class="australia-hop-button-v7">
                CANCEL
            </button>

            <button
                type="button"
                id="australia-hop-copy-v7"
                class="australia-hop-button-v7">
                COPY HOP LINK
            </button>

            <button
                type="button"
                id="australia-hop-open-v7"
                class="australia-hop-button-v7">
                OPEN FIRESTORM
            </button>

        </div>

    </div>

</div>


<script id="australia-hop-modal-v7-script">

(function(){

    let currentHop = "";
    let currentRegion = "";


    const modal =
        document.getElementById(
            "australia-hop-modal-v7"
        );

    const regionText =
        document.getElementById(
            "australia-hop-region-v7"
        );

    const hopText =
        document.getElementById(
            "australia-hop-link-v7"
        );

    const copyResult =
        document.getElementById(
            "australia-hop-copy-result-v7"
        );


    function closeModal(){

        modal.classList.remove(
            "is-open"
        );

        copyResult.textContent = "";

    }


    function openModal(data){

        currentHop =
            String(
                data && data.hop || ""
            );

        currentRegion =
            String(
                data && data.regionName || "REGION"
            );


        if(!currentHop){
            return;
        }


        regionText.textContent =
            currentRegion;


        hopText.textContent =
            currentHop;


        copyResult.textContent =
            "";


        modal.classList.add(
            "is-open"
        );


        setTimeout(
            function(){

                document
                    .getElementById(
                        "australia-hop-open-v7"
                    )
                    .focus();

            },
            30
        );

    }


    async function copyHop(){

        if(!currentHop){
            return;
        }


        try{

            await navigator.clipboard.writeText(
                currentHop
            );

            copyResult.textContent =
                "HOP LINK COPIED";

        }
        catch(error){

            const area =
                document.createElement(
                    "textarea"
                );

            area.value =
                currentHop;

            area.style.position =
                "fixed";

            area.style.left =
                "-9999px";

            document.body.appendChild(
                area
            );

            area.select();

            document.execCommand(
                "copy"
            );

            area.remove();

            copyResult.textContent =
                "HOP LINK COPIED";

        }

    }


    function openFirestorm(){

        if(!currentHop){
            return;
        }


        /*
         * This is the ONLY place V7 invokes the external
         * hop:// protocol.
         */

        const link =
            document.createElement(
                "a"
            );


        link.href =
            currentHop;


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


    document
        .getElementById(
            "australia-hop-close-v7"
        )
        .addEventListener(
            "click",
            closeModal
        );


    document
        .getElementById(
            "australia-hop-cancel-v7"
        )
        .addEventListener(
            "click",
            closeModal
        );


    document
        .getElementById(
            "australia-hop-copy-v7"
        )
        .addEventListener(
            "click",
            copyHop
        );


    document
        .getElementById(
            "australia-hop-open-v7"
        )
        .addEventListener(
            "click",
            openFirestorm
        );


    modal.addEventListener(
        "click",
        function(event){

            if(event.target === modal){

                closeModal();

            }

        }
    );


    document.addEventListener(
        "keydown",
        function(event){

            if(
                event.key === "Escape" &&
                modal.classList.contains(
                    "is-open"
                )
            ){

                closeModal();

            }

        }
    );


    window.AustraliaHopModalV7 = {

        open:
            openModal,

        close:
            closeModal

    };

})();

</script>

<!-- AUSTRALIA HOP MODAL V7 END -->

</body>
</html>





