<?php
/*
 * ============================================================
 * AUSTRALIA 3D MAP EDGE IE CACHE SHIELD V2
 *
 * Prevent Edge / IE mode / browser cache from storing a
 * temporary login redirect for this exact 3D Map URL.
 * This runs BEFORE the DreamGrid session check.
 * ============================================================
 */

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);

header(
    'Expires: 0'
);

$otherRoot = dirname(__DIR__, 2);
require_once $otherRoot . '/core/dreamgrid-env.php';
require_once $otherRoot . '/core/bootstrap.php';

$session = ag_current_session();
$_GET['from'] = 'user';

if (!$session) {
    header('Location: /Other/login.php');
    exit;
}

$roleLevel = min((int)($session['level'] ?? 0), 199);
$roleLabel =
    ($roleLevel >= 250)
        ? 'GRID OWNER'
        : (($roleLevel >= 200) ? 'ADMIN' : 'USER');

$avatarName =
    trim((string)($session['avatar'] ?? '')) !== ''
        ? (string)$session['avatar']
        : 'Member';

$from = strtolower(trim((string)($_GET['from'] ?? 'user')));

$backHref =
    ($from === 'admin' && $roleLevel >= 200)
        ? '/Other/admin-dashboard.php'
        : '/Other/FreshUserDashboardExact/user-dashboard.php';

$backText = 'BACK TO DASHBOARD';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>3D Map</title>

<script src="/assets/vendor/three-0.128.0/three.min.js"></script>
<script src="/assets/vendor/three-0.128.0/OrbitControls.js"></script>

<style>
:root{
    --bg0:#050b12;
    --bg1:#091523;
    --bg2:#0c1b2b;
    --panel:#0a1623ee;
    --panel2:#0d1d2d;
    --line:#8a6a19;
    --gold:#e5b53c;
    --gold2:#c99413;
    --text:#f2f4f7;
    --muted:#aab6c4;
    --blue:#79bfff;
    --blue2:#0b72b8;
    --green:#57d687;
    --red:#ff6d6d;
    --warn:#f3c65d;
}

*{
    box-sizing:border-box;
}

html,
body{
    margin:0;
    min-height:100%;
    background:
        radial-gradient(circle at top left, rgba(255,255,255,0.04), transparent 28%),
        linear-gradient(180deg, #09131d 0%, #07111a 100%);
    color:var(--text);
    font-family:Arial, Helvetica, sans-serif;
}

body{
    padding:18px;
}

a{
    color:inherit;
    text-decoration:none;
}

.page-shell{
    max-width:1880px;
    margin:0 auto;
}

.hero{
    background:
        linear-gradient(135deg, rgba(255,255,255,0.16) 0%, rgba(255,255,255,0.05) 18%, rgba(0,0,0,0.10) 19%, rgba(0,0,0,0.18) 100%),
        linear-gradient(180deg, rgba(10,22,34,0.96), rgba(4,10,16,0.98));
    border:2px solid var(--line);
    border-radius:24px;
    box-shadow:
        0 0 0 2px rgba(255,196,52,0.12) inset,
        0 18px 38px rgba(0,0,0,0.35);
    padding:22px 26px;
    display:flex;
    gap:20px;
    align-items:center;
    justify-content:space-between;
    margin-bottom:18px;
}

.hero-copy{
    min-width:0;
}

.hero-kicker{
    color:var(--gold);
    font-size:14px;
    font-weight:800;
    letter-spacing:1px;
    text-transform:uppercase;
    margin-bottom:6px;
}

.hero h1{
    margin:0 0 8px;
    font-size:56px;
    line-height:1;
    font-weight:900;
    letter-spacing:0.5px;
}

.hero-signed{
    margin:0 0 8px;
    font-size:17px;
    color:#9fd0ff;
}

.hero-signed strong{
    color:#ffbe29;
}

.hero-sub{
    margin:0;
    font-size:15px;
    color:var(--muted);
}

.hero-actions{
    display:flex;
    gap:16px;
    align-items:center;
    justify-content:flex-end;
    flex-wrap:wrap;
}

.role-box{
    min-width:176px;
    padding:14px 18px;
    border-radius:14px;
    background:rgba(6,20,34,0.96);
    border:1px solid #1f6aa7;
    text-align:center;
    box-shadow:0 0 0 1px rgba(127,196,255,0.10) inset;
}

.role-box strong{
    display:block;
    color:#82c8ff;
    font-size:14px;
    font-weight:900;
    letter-spacing:0.7px;
    margin-bottom:4px;
    text-transform:uppercase;
}

.role-box span{
    display:block;
    color:#ffffff;
    font-size:14px;
    font-weight:900;
    text-transform:uppercase;
}

.back-button,
.toolbar-button,
.sidebar-button,
.result-button{
    border:none;
    cursor:pointer;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:0.4px;
}

.back-button{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:58px;
    min-width:250px;
    padding:0 24px;
    border-radius:16px;
    background:linear-gradient(180deg, #f7ca58 0%, #e1aa21 54%, #c78600 100%);
    color:#111;
    box-shadow:
        0 0 0 2px rgba(255,228,156,0.35) inset,
        0 10px 24px rgba(0,0,0,0.22);
    font-size:16px;
}

.layout{
    display:grid;
    grid-template-columns:320px 1fr;
    gap:18px;
    min-height:76vh;
}

.panel{
    background:
        linear-gradient(135deg, rgba(255,255,255,0.10) 0%, rgba(255,255,255,0.02) 22%, rgba(0,0,0,0.06) 23%, rgba(0,0,0,0.18) 100%),
        linear-gradient(180deg, rgba(8,16,26,0.96), rgba(5,10,16,0.98));
    border:2px solid var(--line);
    border-radius:22px;
    box-shadow:
        0 0 0 1px rgba(255,210,97,0.18) inset,
        0 14px 30px rgba(0,0,0,0.25);
}

.sidebar{
    padding:16px;
    display:flex;
    flex-direction:column;
    gap:16px;
    min-height:76vh;
}

.section-title{
    font-size:14px;
    font-weight:900;
    color:var(--gold);
    text-transform:uppercase;
    letter-spacing:0.8px;
    margin-bottom:10px;
}

.search-box{
    display:flex;
    gap:10px;
}

.search-box input{
    flex:1;
    min-width:0;
    min-height:44px;
    border-radius:12px;
    border:1px solid rgba(255,197,72,0.35);
    background:#050b11;
    color:#fff;
    padding:0 14px;
    font-size:15px;
    outline:none;
}

.sidebar-button{
    min-height:44px;
    border-radius:12px;
    padding:0 14px;
    background:linear-gradient(180deg, #f5ca59 0%, #e0aa21 55%, #c58400 100%);
    color:#111;
}

.sidebar-button.secondary,
.toolbar-button.secondary{
    background:linear-gradient(180deg, #31404f 0%, #1b2632 55%, #0a1219 100%);
    color:#fff;
    border:1px solid rgba(255,255,255,0.08);
}

.results-box{
    flex:1;
    min-height:240px;
    max-height:calc(76vh - 310px);
    overflow:auto;
    border-radius:14px;
    background:rgba(2,8,14,0.76);
    border:1px solid rgba(255,197,72,0.18);
    padding:10px;
}

.result-button{
    display:block;
    width:100%;
    text-align:left;
    padding:10px 12px;
    margin:0 0 8px;
    border-radius:12px;
    background:#0a1621;
    color:#fff;
    border:1px solid rgba(255,255,255,0.08);
    text-transform:none;
    letter-spacing:0;
    font-weight:700;
}

.result-button:hover,
.result-button.active{
    border-color:rgba(255,197,72,0.65);
    box-shadow:0 0 0 1px rgba(255,197,72,0.15) inset;
    background:#102234;
}

.result-button small{
    display:block;
    margin-top:4px;
    color:#92a8ba;
    font-size:12px;
    font-weight:600;
}

.info-card{
    border-radius:14px;
    background:rgba(2,8,14,0.76);
    border:1px solid rgba(255,197,72,0.18);
    padding:12px;
}

.info-grid{
    display:grid;
    grid-template-columns:1fr;
    gap:10px;
}

.info-item{
    border-radius:12px;
    background:rgba(255,255,255,0.03);
    border:1px solid rgba(255,255,255,0.05);
    padding:10px 12px;
}

.info-item span{
    display:block;
    color:#8fb4d7;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:0.8px;
    margin-bottom:4px;
}

.info-item strong{
    display:block;
    color:#fff;
    font-size:14px;
    line-height:1.4;
    word-break:break-word;
}

.viewer{
    padding:16px;
    display:flex;
    flex-direction:column;
    min-height:76vh;
}

.viewer-toolbar{
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
    margin-bottom:12px;
}

.toolbar-button{
    min-height:42px;
    border-radius:12px;
    padding:0 15px;
    background:linear-gradient(180deg, #f5ca59 0%, #e0aa21 55%, #c58400 100%);
    color:#111;
}

.status-pill{
    margin-left:auto;
    min-height:42px;
    display:flex;
    align-items:center;
    padding:0 14px;
    border-radius:999px;
    background:rgba(4,14,23,0.88);
    border:1px solid rgba(255,197,72,0.22);
    color:#a9bfd3;
    font-size:13px;
    font-weight:700;
}

.canvas-shell{
    position:relative;
    flex:1;
    min-height:620px;
    border-radius:18px;
    overflow:hidden;
    border:1px solid rgba(255,197,72,0.25);
    background:
        radial-gradient(circle at 50% 20%, rgba(39,93,148,0.28), transparent 34%),
        linear-gradient(180deg, #08121b 0%, #050b12 100%);
}

#map3d-canvas{
    position:absolute;
    inset:0;
}

.canvas-note{
    margin-top:10px;
    color:#9bb1c4;
    font-size:13px;
}

.legend{
    position:absolute;
    right:14px;
    bottom:14px;
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    padding:10px 12px;
    border-radius:12px;
    background:rgba(4,11,18,0.80);
    border:1px solid rgba(255,197,72,0.14);
    font-size:12px;
    color:#d8e2ed;
}

.legend-item{
    display:flex;
    align-items:center;
    gap:6px;
    white-space:nowrap;
}

.legend-swatch{
    width:12px;
    height:12px;
    border-radius:999px;
    border:1px solid rgba(255,255,255,0.16);
}

.legend-online{ background:#45c776; }
.legend-warning{ background:#e2b84f; }
.legend-offline{ background:#6b7785; }

.empty-message{
    color:#9db0c3;
    font-size:13px;
    line-height:1.5;
}

@media (max-width:1180px){
    .layout{
        grid-template-columns:1fr;
    }

    .sidebar{
        min-height:auto;
    }

    .results-box{
        max-height:300px;
    }
}

@media (max-width:760px){
    .hero{
        padding:18px;
    }

    .hero h1{
        font-size:40px;
    }

    .hero,
    .hero-actions{
        align-items:flex-start;
        flex-direction:column;
    }

    .back-button{
        width:100%;
        min-width:0;
    }

    .status-pill{
        margin-left:0;
    }
}
</style>

<style id="ag-map3d-control-center-v1">/* AG 3D CONTROL CENTER V1 START */html,body{width:100%!important;min-height:100%!important;height:auto!important;margin:0!important;padding:0!important;overflow-x:hidden!important;overflow-y:auto!important}body.ag-map3d-page{background:transparent!important}.ag3d-v1-shell{box-sizing:border-box!important;position:relative!important;left:-18px!important;width:calc(100% + 18px)!important;max-width:none!important;min-height:100%!important;margin:0!important;padding:0!important}.ag3d-v1{width:100%!important;max-width:none!important;margin:0!important;padding:8px!important;box-sizing:border-box!important;color:#d7dde0}.ag3d-v1 *{box-sizing:border-box}.ag3d-v1-intro{min-height:56px;margin:0 0 8px;padding:9px 12px;display:flex;align-items:center;justify-content:space-between;gap:14px;border:1px solid rgba(214,164,59,.34);border-radius:6px;background:linear-gradient(180deg,#151918,#0b0f0e);box-shadow:0 6px 16px rgba(0,0,0,.30)}.ag3d-v1-identity{display:flex;align-items:center;gap:10px;min-width:0}.ag3d-v1-icon{width:30px;height:30px;object-fit:contain}.ag3d-v1-title{color:#eef2f3;font-size:12px;font-weight:900}.ag3d-v1-subtitle{margin-top:4px;color:#747f83;font-size:7px;font-weight:800;letter-spacing:.08em}.ag3d-v1-live{position:relative;padding-left:13px;color:#72dc96;font-size:7px;font-weight:900;letter-spacing:.08em}.ag3d-v1-live:before{content:"";position:absolute;left:0;top:50%;width:7px;height:7px;margin-top:-4px;border-radius:50%;background:#5ce28e;box-shadow:0 0 8px rgba(92,226,142,.9)}.ag3d-v1-grid{display:grid!important;grid-template-columns:300px minmax(0,1fr)!important;gap:8px!important;width:100%!important;max-width:none!important;min-height:0!important;margin:0!important}.ag3d-v1-side{display:flex;flex-direction:column;gap:8px;min-width:0}.ag3d-v1-card,.ag3d-v1-viewer{min-width:0;border:1px solid rgba(214,164,59,.34);border-radius:6px;background:linear-gradient(180deg,#151918,#0b0f0e);box-shadow:0 6px 16px rgba(0,0,0,.30);overflow:hidden}.ag3d-v1-card-head{min-height:44px;padding:8px 11px;display:flex;align-items:center;border-bottom:1px solid rgba(255,255,255,.075);background:linear-gradient(180deg,rgba(24,29,28,.96),rgba(14,18,17,.96))}.ag3d-v1-card-title{color:#eef2f3;font-size:9px;font-weight:900}.ag3d-v1-card-subtitle{margin-top:3px;color:#747f83;font-size:7px;font-weight:800;letter-spacing:.07em}.ag3d-v1-card-body{padding:8px}.ag3d-v1-search{display:grid!important;grid-template-columns:minmax(0,1fr) 58px!important;gap:6px!important}.ag3d-v1 input{width:100%!important;height:32px!important;padding:0 9px!important;border:1px solid rgba(214,164,59,.28)!important;border-radius:4px!important;background:#070b0a!important;color:#e5e8e8!important;font-size:8px!important}.ag3d-v1 button{min-height:32px!important;border:1px solid rgba(214,164,59,.48)!important;border-radius:4px!important;background:linear-gradient(180deg,#252b2a,#101413 55%,#080b0a)!important;color:#eef2f3!important;font-size:7px!important;font-weight:900!important;letter-spacing:.03em!important;box-shadow:inset 0 1px 0 rgba(255,255,255,.08),0 2px 5px rgba(0,0,0,.55)!important}.ag3d-v1 button:hover{border-color:#d6a43b!important;color:#fff!important}.ag3d-v1 button:disabled{opacity:.45!important}.ag3d-v1-results-body{padding:6px!important}.ag3d-v1 .results-box{height:300px!important;min-height:300px!important;max-height:300px!important;overflow:auto!important;margin:0!important;padding:5px!important;border:1px solid rgba(255,255,255,.06)!important;border-radius:4px!important;background:#070b0a!important}.ag3d-v1 .result-button{border:1px solid rgba(255,255,255,.07)!important;background:#0d1211!important;color:#cbd3d4!important;border-radius:4px!important}.ag3d-v1 .info-card{padding:0!important;border:0!important;background:transparent!important;box-shadow:none!important}.ag3d-v1 .info-grid{display:grid!important;grid-template-columns:1fr!important;gap:5px!important}.ag3d-v1 .info-item{min-height:45px!important;padding:7px 9px!important;border:1px solid rgba(255,255,255,.065)!important;border-radius:4px!important;background:#080c0b!important}.ag3d-v1 .info-item span{display:block;color:#d9ae48!important;font-size:7px!important;font-weight:900!important;text-transform:uppercase!important}.ag3d-v1 .info-item strong{display:block;margin-top:4px;color:#e2e6e6!important;font-size:8px!important;line-height:1.3!important;word-break:break-word}.ag3d-v1-detail-actions{margin-top:7px;display:grid;grid-template-columns:1fr 1fr;gap:6px}.ag3d-v1-viewer{display:flex!important;flex-direction:column!important;height:auto!important;min-height:0!important}.ag3d-v1-toolbar{min-height:48px!important;padding:7px!important;display:flex!important;align-items:center!important;gap:6px!important;flex-wrap:wrap!important;border-bottom:1px solid rgba(255,255,255,.075)!important;background:linear-gradient(180deg,#131817,#090d0c)!important}.ag3d-v1 .status-pill{margin-left:auto!important;min-height:30px!important;padding:0 10px!important;display:flex!important;align-items:center!important;border:1px solid rgba(92,226,142,.25)!important;border-radius:4px!important;background:#07100c!important;color:#72dc96!important;font-size:7px!important;font-weight:900!important}.ag3d-v1-canvas-shell{position:relative!important;width:100%!important;height:calc(100vh - 62px)!important;min-height:650px!important;max-height:none!important;margin:0!important;padding:6px!important;overflow:hidden!important;border:0!important;border-radius:0!important;background:#050908!important}.ag3d-v1 #map3d-canvas{width:100%!important;height:100%!important;min-width:0!important;min-height:0!important;border:1px solid rgba(214,164,59,.20)!important;border-radius:4px!important;overflow:hidden!important;background:#050908!important}.ag3d-v1-legend{position:absolute!important;left:16px!important;bottom:16px!important;display:flex!important;gap:8px!important;flex-wrap:wrap!important;padding:6px 8px!important;border:1px solid rgba(214,164,59,.30)!important;border-radius:4px!important;background:rgba(6,10,9,.88)!important;color:#aab4b7!important;font-size:7px!important}.ag3d-v1-note{margin:0!important;padding:7px 10px!important;border-top:1px solid rgba(255,255,255,.06)!important;background:#080c0b!important;color:#778488!important;font-size:7px!important}.ag3d-v1 ::-webkit-scrollbar{width:8px;height:8px}.ag3d-v1 ::-webkit-scrollbar-track{background:#070a09}.ag3d-v1 ::-webkit-scrollbar-thumb{background:#4f4325;border-radius:6px}.ag3d-v1 #ag3d-reset-panel-h21r2{background:linear-gradient(180deg,#151918,#090d0c)!important;border-color:rgba(214,164,59,.48)!important;color:#e5e8e8!important}@media(max-width:1050px){.ag3d-v1-grid{grid-template-columns:1fr!important}.ag3d-v1 .results-box{height:220px!important;min-height:220px!important;max-height:220px!important}}/* AG 3D CONTROL CENTER V1 END */</style>
<style id="ag3d-fullscreen-style-v1">/* AG 3D FULLSCREEN V2 */.ag3d-v1:fullscreen{box-sizing:border-box!important;width:100vw!important;height:100vh!important;max-width:none!important;max-height:none!important;margin:0!important;padding:8px!important;display:flex!important;flex-direction:column!important;gap:8px!important;overflow:hidden!important;background:#050908!important}.ag3d-v1:fullscreen .ag3d-v1-intro{flex:0 0 auto!important;margin:0!important}.ag3d-v1:fullscreen .ag3d-v1-grid{flex:1 1 auto!important;min-height:0!important;display:block!important}.ag3d-v1:fullscreen .ag3d-v1-side{display:none!important}.ag3d-v1:fullscreen .ag3d-v1-viewer{width:100%!important;height:100%!important;min-height:0!important;display:flex!important;flex-direction:column!important}.ag3d-v1:fullscreen .ag3d-v1-canvas-shell{flex:1 1 auto!important;width:100%!important;height:auto!important;min-height:0!important;max-height:none!important}.ag3d-v1:fullscreen #map3d-canvas{width:100%!important;height:100%!important;min-height:0!important}.ag3d-v1:fullscreen .ag3d-v1-note{display:none!important}#map3d-fullscreen img{width:14px!important;height:14px!important;object-fit:contain!important}</style>
<style id="ag3d-top-controls-style-v1">/* AG 3D TOP CONTROLS V2 */body.ag-map3d-page .ag3d-v1-intro{min-height:72px!important;padding:12px 14px!important;align-items:center!important}.ag3d-v1-intro-right{margin-left:auto!important;display:flex!important;align-items:center!important;justify-content:flex-end!important;gap:10px!important;min-width:0!important}.ag3d-v1-intro .ag3d-v1-toolbar{min-height:36px!important;padding:0!important;margin:0!important;display:flex!important;align-items:center!important;gap:7px!important;flex-wrap:nowrap!important;border:0!important;background:transparent!important;box-shadow:none!important}.ag3d-v1-intro .ag3d-v1-toolbar button{box-sizing:border-box!important;min-height:36px!important;height:36px!important;padding:0 15px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;line-height:1!important;white-space:nowrap!important}.ag3d-v1-intro #map3d-fullscreen{display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:7px!important;line-height:1!important;padding-top:0!important;padding-bottom:0!important}.ag3d-v1-intro #map3d-fullscreen img{display:block!important;width:14px!important;height:14px!important;min-width:14px!important;max-width:14px!important;margin:0!important;flex:0 0 14px!important;object-fit:contain!important;vertical-align:middle!important}.ag3d-v1-intro .status-pill{box-sizing:border-box!important;min-height:36px!important;height:36px!important;margin:0!important;padding:0 12px!important;display:flex!important;align-items:center!important;justify-content:center!important;line-height:1!important;white-space:nowrap!important}.ag3d-v1-intro .ag3d-v1-live{min-height:36px!important;display:flex!important;align-items:center!important;white-space:nowrap!important}@media(max-width:1350px){body.ag-map3d-page .ag3d-v1-intro{align-items:flex-start!important;flex-direction:column!important}.ag3d-v1-intro-right{width:100%!important;margin-left:0!important;justify-content:flex-start!important;flex-wrap:wrap!important}.ag3d-v1-intro .ag3d-v1-toolbar{flex-wrap:wrap!important}}</style>
</head>
<body class="ag-map3d-page">
<div class="page-shell ag3d-v1-shell">
<main class="ag3d-v1">
<section class="ag3d-v1-intro">
<div class="ag3d-v1-identity"><img class="ag3d-v1-icon" src="/Other/assets/icons/sentinel/view.png" alt="" aria-hidden="true"><div><div class="ag3d-v1-title">Interactive 3D Region Map</div><div class="ag3d-v1-subtitle">LIVE REGION SELECTION, STATUS AND INTERACTIVE 3D VIEW</div></div></div>
<div class="ag3d-v1-intro-right"><div class="ag3d-v1-live">READY</div><div class="ag3d-v1-toolbar viewer-toolbar"><button id="map3d-refresh" type="button" class="toolbar-button">REFRESH</button><button id="map3d-home" type="button" class="toolbar-button secondary">RESET VIEW</button><button id="map3d-top" type="button" class="toolbar-button secondary">TOP VIEW</button><button id="map3d-angle" type="button" class="toolbar-button secondary">ANGLE VIEW</button><button id="map3d-fullscreen" type="button" class="toolbar-button secondary"><img class="ag-sentinel-direct-icon ag-sentinel-xs" src="/Other/assets/icons/sentinel/view.png" alt="" aria-hidden="true" draggable="false" decoding="async"> FULL SCREEN MAP</button><div id="map3d-status" class="status-pill">Loading 3D map...</div></div></div>
</section>
<section class="ag3d-v1-grid layout">
<aside class="ag3d-v1-side">
<section class="ag3d-v1-card">
<div class="ag3d-v1-card-head"><div><div class="ag3d-v1-card-title">Find Region</div><div class="ag3d-v1-card-subtitle">SEARCH THE GRID</div></div></div>
<div class="ag3d-v1-card-body"><div class="search-box ag3d-v1-search"><input id="map3d-search" type="text" placeholder="Region name"><button id="map3d-search-button" type="button" class="sidebar-button">GO</button></div></div>
</section>
<section class="ag3d-v1-card ag3d-v1-regions">
<div class="ag3d-v1-card-head"><div><div class="ag3d-v1-card-title">Regions</div><div class="ag3d-v1-card-subtitle">AVAILABLE GRID REGIONS</div></div></div>
<div class="ag3d-v1-card-body ag3d-v1-results-body"><div id="map3d-results" class="results-box"><div class="empty-message">Loading regions...</div></div></div>
</section>
<section class="ag3d-v1-card">
<div class="ag3d-v1-card-head"><div><div class="ag3d-v1-card-title">Region Details</div><div class="ag3d-v1-card-subtitle">SELECTED REGION INFORMATION</div></div></div>
<div class="ag3d-v1-card-body"><div class="info-card"><div class="info-grid">
<div class="info-item"><span>Region</span><strong id="map3d-info-name">Nothing selected</strong></div>
<div class="info-item"><span>Status</span><strong id="map3d-info-status">-</strong></div>
<div class="info-item"><span>Grid Location</span><strong id="map3d-info-location">-</strong></div>
<div class="info-item"><span>Region Size</span><strong id="map3d-info-size">-</strong></div>
<div class="info-item"><span>Estate</span><strong id="map3d-info-estate">-</strong></div>
<div class="info-item"><span>Avatars</span><strong id="map3d-info-avatars">-</strong></div>
<div class="info-item"><span>Hop URL</span><strong id="map3d-info-hop">-</strong></div>
</div><div class="ag3d-v1-detail-actions"><button id="map3d-copy-hop" type="button" class="sidebar-button" disabled>COPY HOP</button><button id="map3d-focus" type="button" class="sidebar-button secondary" disabled>FOCUS REGION</button></div></div></div>
</section>
</aside>
<section class="ag3d-v1-viewer viewer">

<div class="ag3d-v1-canvas-shell canvas-shell"><div id="map3d-canvas"></div><div class="ag3d-v1-legend legend"><div class="legend-item"><span class="legend-swatch legend-online"></span>ONLINE</div><div class="legend-item"><span class="legend-swatch legend-warning"></span>BUSY</div><div class="legend-item"><span class="legend-swatch legend-offline"></span>OFFLINE / UNKNOWN</div></div></div>
<div class="ag3d-v1-note canvas-note">Select one region at a time. Drag to rotate, right-drag to pan and use the mouse wheel to zoom.</div>
</section>
</section>
</main>
</div>
<script>
window.AG_3D_MAP = {
    userMode: true,
    dataUrl: "/Other/grid-map-3d-data-user.php",
    roleLabel: <?=json_encode($roleLabel, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)?>,
    roleLevel: <?=json_encode($roleLevel, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)?>,
    avatarName: <?=json_encode($avatarName, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)?>,
    from: <?=json_encode($from, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)?>
};
</script>
<script src="/Other/assets/js/dg-3d-metrics.js?v=<?=filemtime($otherRoot . '/assets/js/dg-3d-metrics.js')?>"></script>
<script src="/Other/assets/js/dg-3d-pipeline.js?v=<?=filemtime($otherRoot . '/assets/js/dg-3d-pipeline.js')?>"></script>
<script src="/Other/assets/js/grid-map-3d.js?v=<?=filemtime($otherRoot . '/assets/js/grid-map-3d.js')?>"></script>

<!-- ========================================================
     SAFE REGION RESET BUTTONS V6C-H21-R2
     ======================================================== -->

<style>
#ag3d-reset-panel-h21r2{
    position:fixed;
    top:112px;
    right:18px;
    z-index:99990;

    display:none;

    width:205px;
    padding:11px;

    box-sizing:border-box;

    background:
        rgba(7,11,16,.96);

    border:
        1px solid rgba(214,170,63,.72);

    border-radius:
        9px;

    box-shadow:
        0 10px 30px rgba(0,0,0,.5);

    font-family:
        Arial,
        sans-serif;
}

#ag3d-reset-panel-h21r2 .ag3d-title{
    color:#d6aa3f;

    text-align:center;

    font-size:11px;
    font-weight:900;
    letter-spacing:.8px;

    margin-bottom:7px;
}

#ag3d-reset-panel-h21r2 button{
    display:block;

    width:100%;
    min-height:35px;

    margin-top:7px;

    border-radius:5px;

    font-size:11px;
    font-weight:900;
    letter-spacing:.35px;

    cursor:pointer;
}

#ag3d-texture-reset-h21r2{
    color:#111;

    background:#dfb53f;

    border:
        1px solid #f2d16c;
}

#ag3d-mesh-reset-h21r2{
    color:#fff;

    background:#8c2d2d;

    border:
        1px solid #c25252;
}

#ag3d-reset-panel-h21r2 button:disabled{
    opacity:.45;
    cursor:not-allowed;
}

#ag3d-reset-region-h21r2{
    margin-top:8px;

    color:#d8e1e9;

    text-align:center;

    font-size:10px;
    font-weight:800;

    overflow-wrap:anywhere;
}

#ag3d-reset-status-h21r2{
    min-height:30px;

    margin-top:7px;

    color:#91a4b5;

    text-align:center;

    font-size:9px;
    line-height:1.35;
}

#ag3d-reset-note-h21r2{
    margin-top:6px;

    color:#6f7f8c;

    text-align:center;

    font-size:8px;
    line-height:1.3;
}
</style>







<!-- =========================================================
     CUSTOM COPY HOP DIALOG V6C-H29
     ========================================================= -->

<style id="ag-copy-hop-style-h29">

#ag-copy-hop-overlay-h29{
    position:fixed;
    inset:0;
    z-index:2147483000;
    display:none;
    align-items:center;
    justify-content:center;
    padding:24px;
    background:rgba(0,8,15,.76);
    backdrop-filter:blur(4px);
}

#ag-copy-hop-overlay-h29.ag-open{
    display:flex;
}

#ag-copy-hop-dialog-h29{
    width:min(560px,calc(100vw - 40px));
    background:
        linear-gradient(
            145deg,
            #101b25 0%,
            #07111a 62%,
            #040b11 100%
        );
    border:1px solid #d4a425;
    border-radius:17px;
    box-shadow:
        0 22px 70px rgba(0,0,0,.72),
        0 0 0 1px rgba(226,182,66,.10) inset;
    overflow:hidden;
    color:#fff;
    font-family:inherit;
}

#ag-copy-hop-head-h29{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    padding:18px 21px;
    border-bottom:1px solid rgba(226,182,66,.28);
    background:
        linear-gradient(
            180deg,
            rgba(226,182,66,.08),
            rgba(226,182,66,.015)
        );
}

#ag-copy-hop-title-wrap-h29{
    min-width:0;
}

#ag-copy-hop-kicker-h29{
    margin:0 0 4px;
    color:#e2b642;
    font-size:11px;
    line-height:1;
    font-weight:900;
    letter-spacing:1.35px;
}

#ag-copy-hop-title-h29{
    margin:0;
    color:#fff;
    font-size:21px;
    line-height:1.1;
    font-weight:900;
    letter-spacing:.3px;
}

#ag-copy-hop-x-h29{
    flex:0 0 auto;
    width:36px;
    height:36px;
    display:grid;
    place-items:center;
    border:1px solid #344552;
    border-radius:9px;
    background:#111d27;
    color:#d7e0e7;
    font-size:22px;
    line-height:1;
    font-weight:700;
    cursor:pointer;
}

#ag-copy-hop-x-h29:hover{
    border-color:#e2b642;
    color:#e2b642;
}

#ag-copy-hop-body-h29{
    padding:21px;
}

#ag-copy-hop-label-h29{
    display:block;
    margin:0 0 8px;
    color:#91a7b8;
    font-size:11px;
    font-weight:900;
    letter-spacing:.9px;
}

#ag-copy-hop-url-h29{
    box-sizing:border-box;
    width:100%;
    min-height:76px;
    resize:none;
    padding:13px 14px;
    border:1px solid #344552;
    border-radius:10px;
    outline:none;
    background:#03090e;
    color:#f5f7f8;
    font:700 13px/1.45 Consolas, "Courier New", monospace;
    overflow-wrap:anywhere;
    box-shadow:
        0 0 0 1px rgba(0,0,0,.25) inset;
}

#ag-copy-hop-url-h29:focus{
    border-color:#e2b642;
    box-shadow:
        0 0 0 2px rgba(226,182,66,.12);
}

#ag-copy-hop-status-h29{
    min-height:20px;
    margin:10px 0 0;
    color:#91a7b8;
    font-size:12px;
    font-weight:700;
}

#ag-copy-hop-status-h29.ag-success{
    color:#57da8b;
}

#ag-copy-hop-status-h29.ag-error{
    color:#ff8a8a;
}

#ag-copy-hop-actions-h29{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    padding:0 21px 21px;
}

.ag-copy-hop-button-h29{
    min-width:120px;
    height:42px;
    padding:0 18px;
    border-radius:9px;
    font-family:inherit;
    font-size:12px;
    font-weight:900;
    letter-spacing:.35px;
    cursor:pointer;
}

#ag-copy-hop-close-h29{
    border:1px solid #344552;
    background:
        linear-gradient(
            180deg,
            #223241,
            #111c26
        );
    color:#fff;
}

#ag-copy-hop-close-h29:hover{
    border-color:#6a8193;
}

#ag-copy-hop-copy-h29{
    border:1px solid #f0c34b;
    background:
        linear-gradient(
            180deg,
            #f1c84c,
            #dda519
        );
    color:#071019;
    box-shadow:
        0 2px 0 rgba(0,0,0,.35);
}

#ag-copy-hop-copy-h29:hover{
    filter:brightness(1.06);
}

#ag-copy-hop-copy-h29:active{
    transform:translateY(1px);
}

@media(max-width:560px){

    #ag-copy-hop-actions-h29{
        flex-direction:column-reverse;
    }

    .ag-copy-hop-button-h29{
        width:100%;
    }
}

</style>


<div
    id="ag-copy-hop-overlay-h29"
    aria-hidden="true"
>
    <div
        id="ag-copy-hop-dialog-h29"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ag-copy-hop-title-h29"
    >
        <div id="ag-copy-hop-head-h29">

            <div id="ag-copy-hop-title-wrap-h29">

                <div id="ag-copy-hop-kicker-h29">
                    <?=htmlspecialchars(ag_grid_name(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?> 3D MAP
                </div>

                <h2 id="ag-copy-hop-title-h29">
                    COPY HOP URL
                </h2>

            </div>

            <button
                type="button"
                id="ag-copy-hop-x-h29"
                aria-label="Close"
            >Ã—</button>

        </div>


        <div id="ag-copy-hop-body-h29">

            <label
                id="ag-copy-hop-label-h29"
                for="ag-copy-hop-url-h29"
            >
                REGION HOP ADDRESS
            </label>

            <textarea
                id="ag-copy-hop-url-h29"
                readonly
                spellcheck="false"
            ></textarea>

            <div
                id="ag-copy-hop-status-h29"
                aria-live="polite"
            >
                Select COPY URL to place this address on the clipboard.
            </div>

        </div>


        <div id="ag-copy-hop-actions-h29">

            <button
                type="button"
                class="ag-copy-hop-button-h29"
                id="ag-copy-hop-close-h29"
            >
                CLOSE
            </button>

            <button
                type="button"
                class="ag-copy-hop-button-h29"
                id="ag-copy-hop-copy-h29"
            >
                COPY URL
            </button>

        </div>

    </div>
</div>


<script>
/*
 * ============================================================
 * CUSTOM COPY HOP DIALOG V6C-H29
 *
 * Replace only the browser-native:
 *
 *     prompt("Copy Hop URL", hopUrl)
 *
 * The existing COPY HOP function and region data stay intact.
 * ============================================================
 */

(function(){

    "use strict";

    const overlay =
        document.getElementById(
            "ag-copy-hop-overlay-h29"
        );

    const dialog =
        document.getElementById(
            "ag-copy-hop-dialog-h29"
        );

    const urlField =
        document.getElementById(
            "ag-copy-hop-url-h29"
        );

    const status =
        document.getElementById(
            "ag-copy-hop-status-h29"
        );

    const copyButton =
        document.getElementById(
            "ag-copy-hop-copy-h29"
        );

    const closeButton =
        document.getElementById(
            "ag-copy-hop-close-h29"
        );

    const xButton =
        document.getElementById(
            "ag-copy-hop-x-h29"
        );


    if(
        !overlay ||
        !dialog ||
        !urlField ||
        !status ||
        !copyButton ||
        !closeButton ||
        !xButton
    ){

        return;
    }


    const originalPrompt =
        window.prompt
            ? window.prompt.bind(
                window
            )
            : null;


    function agCloseCopyHopH29(){

        overlay.classList.remove(
            "ag-open"
        );

        overlay.setAttribute(
            "aria-hidden",
            "true"
        );

        status.className =
            "";

        status.textContent =
            "Select COPY URL to place this address on the clipboard.";
    }


    function agOpenCopyHopH29(
        url
    ){

        urlField.value =
            String(
                url ||
                ""
            );

        overlay.classList.add(
            "ag-open"
        );

        overlay.setAttribute(
            "aria-hidden",
            "false"
        );

        status.className =
            "";

        status.textContent =
            "Select COPY URL to place this address on the clipboard.";


        window.setTimeout(
            function(){

                try{

                    urlField.focus();
                    urlField.select();
                }
                catch(error){
                }

            },
            30
        );
    }


    async function agCopyHopToClipboardH29(){

        const value =
            String(
                urlField.value ||
                ""
            );


        if(!value){

            status.className =
                "ag-error";

            status.textContent =
                "No Hop URL is available.";

            return;
        }


        let copied =
            false;


        /*
         * Clipboard API first.
         *
         * This can be restricted on plain HTTP, so the classic
         * selection/copy method remains as a fallback.
         */

        try{

            if(
                navigator.clipboard &&
                typeof navigator.clipboard.writeText ===
                    "function"
            ){

                await navigator.clipboard.writeText(
                    value
                );

                copied =
                    true;
            }
        }
        catch(error){
        }


        if(!copied){

            try{

                urlField.focus();
                urlField.select();

                copied =
                    document.execCommand(
                        "copy"
                    ) === true;
            }
            catch(error){

                copied =
                    false;
            }
        }


        if(copied){

            status.className =
                "ag-success";

            status.textContent =
                "HOP URL COPIED.";

            copyButton.textContent =
                "COPIED";


            window.setTimeout(
                function(){

                    copyButton.textContent =
                        "COPY URL";

                },
                1200
            );
        }
        else{

            status.className =
                "ag-error";

            status.textContent =
                "COPY FAILED â€” the URL is selected so you can press Ctrl+C.";
        }
    }


    copyButton.addEventListener(
        "click",
        agCopyHopToClipboardH29
    );


    closeButton.addEventListener(
        "click",
        agCloseCopyHopH29
    );


    xButton.addEventListener(
        "click",
        agCloseCopyHopH29
    );


    overlay.addEventListener(
        "mousedown",
        function(
            event
        ){

            if(event.target === overlay){

                agCloseCopyHopH29();
            }
        }
    );


    document.addEventListener(
        "keydown",
        function(
            event
        ){

            if(
                event.key ===
                    "Escape" &&
                overlay.classList.contains(
                    "ag-open"
                )
            ){

                agCloseCopyHopH29();
            }
        }
    );


    /*
     * ---------------------------------------------------------
     * PROMPT INTERCEPT
     *
     * ONLY intercept Copy Hop URL.
     *
     * All other prompt() calls retain normal browser behaviour.
     * ---------------------------------------------------------
     */

    window.prompt =
        function(
            message,
            defaultValue
        ){

            const title =
                String(
                    message ||
                    ""
                )
                .trim()
                .toLowerCase();


            if(
                title ===
                    "copy hop url" ||
                title ===
                    "copy hop url:"
            ){

                agOpenCopyHopH29(
                    defaultValue
                );

                /*
                 * Existing copyHop() does not need a user-entered
                 * replacement value. Return the original URL so
                 * synchronous prompt semantics remain harmless.
                 */

                return String(
                    defaultValue ||
                    ""
                );
            }


            if(originalPrompt){

                return originalPrompt(
                    message,
                    defaultValue
                );
            }


            return null;
        };


    console.info(
        "CUSTOM COPY HOP DIALOG V6C-H29 - ACTIVE"
    );

})();
</script>

<!-- END CUSTOM COPY HOP DIALOG V6C-H29 -->

<script id="ag3d-fullscreen-script-v1">/* AG 3D FULLSCREEN V2 */(function(){"use strict";const button=document.getElementById("map3d-fullscreen");const target=document.querySelector(".ag3d-v1");if(!button||!target){return;}function label(active){button.innerHTML="<img class=\"ag-sentinel-direct-icon ag-sentinel-xs\" src=\"/Other/assets/icons/sentinel/view.png\" alt=\"\" aria-hidden=\"true\"> "+(active?"EXIT FULL SCREEN":"FULL SCREEN MAP");}button.addEventListener("click",async function(){try{if(document.fullscreenElement===target){await document.exitFullscreen();}else{if(document.fullscreenElement){await document.exitFullscreen();}await target.requestFullscreen();}}catch(error){console.error("3D fullscreen failed",error);}});document.addEventListener("fullscreenchange",function(){label(document.fullscreenElement===target);setTimeout(function(){window.dispatchEvent(new Event("resize"));},80);});label(false);})();</script>


</body>
</html>
