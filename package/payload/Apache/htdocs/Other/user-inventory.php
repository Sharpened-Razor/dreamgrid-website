<?php

require_once __DIR__ . '/core/bootstrap.php';


$session =
    ag_current_session();


if (!$session) {

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


$principalId =
    trim(
        (string)(
            $session['principalId'] ??
            ''
        )
    );


if ($principalId === '') {

    http_response_code(403);

    echo 'Signed account ID is unavailable.';

    exit;

}


$level = ag_user_level($session);

$fromAdmin =
    strtolower(trim((string)($_GET['from'] ?? ''))) === 'admin'
    && ag_is_admin($session);

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
Grid - My Inventory
</title>

<link
    rel="stylesheet"
    href="/Other/australia-3d-theme.css?v=31">


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

        linear-gradient(
            rgba(0,0,0,.45),
            rgba(0,0,0,.67)
        ),

        url(
            "/Other/assets/images/control-center-teal-bg.png"
        )

        center center /
        cover
        fixed
        no-repeat;

    color:#edf2f4;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}


.inventory-shell{

    width:
        min(
            1250px,
            calc(100% - 40px)
        );

    margin:
        40px auto 75px;

    padding:
        28px;

}





.inventory-kicker{

    color:#efb83d;

    font-size:10px;

    font-weight:900;

    letter-spacing:.20em;

}


.inventory-avatar{

    margin-top:8px;

    color:#9eabb1;

    font-size:12px;

}


.inventory-avatar strong{

    color:#fff;

}


.inventory-grid{

    display:grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0,1fr)
        );

    gap:
        18px;

}


.inventory-card{

    min-height:
        270px;

    display:flex;

    flex-direction:column;

    padding:
        24px;

}


.inventory-card-heading{

    display:flex;

    align-items:center;

    gap:
        14px;

    margin-bottom:
        17px;

}


.inventory-icon{

    width:
        55px;

    height:
        55px;

    flex:
        0 0 55px;

    display:flex;

    align-items:center;

    justify-content:center;

    border:
        2px solid
        #ae8128;

    border-radius:
        11px;

    background:

        linear-gradient(
            145deg,
            #414748,
            #1b2022 42%,
            #080b0c 70%,
            #030404
        );

    color:#ffc647;

    font-size:11px;

    font-weight:900;

    box-shadow:

        inset 0 2px 0
        rgba(255,255,255,.30),

        inset 0 -4px 7px
        rgba(0,0,0,.87),

        0 2px 0
        #44310b,

        0 6px 10px
        rgba(0,0,0,.68);

}


.inventory-card h2{

    margin:0;

    font-size:20px;

}


.inventory-status{

    display:inline-block;

    margin-top:
        7px;

    padding:
        4px 8px;

    border:
        1px solid
        rgba(236,176,53,.34);

    border-radius:
        999px;

    background:
        rgba(224,162,40,.08);

    color:#f0c569;

    font-size:9px;

    font-weight:900;

}


.inventory-card p{

    flex:1;

    margin:
        0 0 20px;

    color:#bbc5ca;

    font-size:14px;

    line-height:1.65;

}


.inventory-button{

    min-height:
        47px;

    display:flex;

    align-items:center;

    justify-content:center;

    width:100%;

    padding:
        10px 15px;

    text-decoration:none;

}


.inventory-button.disabled{

    opacity:.46;

    pointer-events:none;

}


.inventory-actions{

    display:flex;

    justify-content:center;

    gap:12px;

    margin-top:
        22px;

    padding-top:
        22px;

    border-top:
        1px solid
        rgba(255,255,255,.09);

}


.inventory-actions a{

    min-width:
        160px;

    min-height:
        46px;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    padding:
        10px 18px;

    text-decoration:none;

}


.inventory-note{

    margin-top:
        20px;

    padding:
        16px 18px;

    border-left:
        4px solid
        #e6aa30;

    color:#bbc4c8;

    font-size:12px;

    line-height:1.65;

}


@media(
    max-width:950px
){

    .inventory-grid{

        grid-template-columns:
            1fr;

    }

}


@media(
    max-width:650px
){

    .inventory-shell{

        width:
            calc(100% - 24px);

        margin:
            20px auto 50px;

        padding:
            16px;

    }


    .inventory-actions{

        flex-direction:column;

    }


    .inventory-actions a{

        width:100%;

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


<style id="inventory-card-gold-frame-fix-v1">

/* ============================================================
   INVENTORY CARD GOLD FRAME FIX V1
   Keep heading + description inside gold trim.
   Keep action button below the gold trim.
   ============================================================ */

.inventory-card{
    position:relative !important;
}

.inventory-card::after{
    inset:auto !important;
    top:10px !important;
    left:10px !important;
    right:10px !important;
    bottom:88px !important;
    width:auto !important;
    height:auto !important;
    min-height:0 !important;
    max-height:none !important;
    border:1px solid rgba(255,193,58,.55) !important;
    border-radius:10px !important;
    pointer-events:none !important;
    z-index:1 !important;
}

.inventory-card > *{
    position:relative !important;
    z-index:2 !important;
}

.inventory-card p{
    margin-bottom:20px !important;
    padding:0 2px !important;
    line-height:1.55 !important;
}

.inventory-button{
    margin-top:auto !important;
    position:relative !important;
    z-index:3 !important;
}

/* END INVENTORY CARD GOLD FRAME FIX V1 */

</style>


<style id="ag-role-display-standard-v4">

/* ============================================================
   STANDARD ROLE DISPLAY V4

   250+        GRID OWNER
   other admin ADMIN
   normal user USER
   ============================================================ */

.php-level,
.ag-account-role-box{
    display:inline-flex !important;
    flex-direction:column !important;
    align-items:center !important;
    justify-content:center !important;
    min-width:145px !important;
    min-height:54px !important;
    padding:7px 14px !important;
    box-sizing:border-box !important;
    border:1px solid rgba(55,150,215,.70) !important;
    border-radius:8px !important;
    background:rgba(5,28,43,.88) !important;
    box-shadow:none !important;
    text-align:center !important;
    line-height:1.15 !important;
    color:#ffffff !important;
}

.php-level strong,
.ag-account-role-box strong{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    width:auto !important;
    height:auto !important;
    margin:0 0 4px 0 !important;
    padding:0 !important;
    color:#75c9ff !important;
    background:none !important;
    font-size:12px !important;
    font-weight:900 !important;
    line-height:1.1 !important;
    letter-spacing:.03em !important;
    text-indent:0 !important;
    clip:auto !important;
    overflow:visible !important;
}

.php-level span,
.ag-account-role-box span{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    margin:0 !important;
    padding:0 !important;
    color:#ffffff !important;
    background:none !important;
    font-size:11px !important;
    font-weight:800 !important;
    line-height:1.1 !important;
    white-space:nowrap !important;
}

</style>

</head>


<body>


<?php
$siteHeaderKicker = $fromAdmin ? "ADMINISTRATION" : "ACCOUNT";
$siteHeaderTitle = $fromAdmin ? "MY INVENTORY" : "USER INVENTORY";
$siteHeaderRole = ((int)$level >= 250) ? "GRID OWNER" : ((function_exists("ag_is_admin") && ag_is_admin($session)) ? "ADMIN" : "USER");
$siteHeaderLevel = $level;
$siteHeaderButton = $fromAdmin ? "BACK TO DASHBOARD" : "ACCOUNT HOME";
$siteHeaderLink = $fromAdmin
    ? "/Other/admin-dashboard.php"
    : "/Other/FreshUserDashboardExact/user-dashboard.php";
require_once __DIR__ . "/includes/site-header.php";
?>
<main class="inventory-shell panel">


<section class="inventory-grid">


<!-- =========================================================
     INVENTORY BROWSER
     ========================================================= -->

<article class="inventory-card card">


<div class="inventory-card-heading">

    <?=ag_icon(
    'inventory',
    null,
    'inventory-v2-icon ag-icon-badge ag-icon-large ag-badge-gold'
)?>

    <div>

        <h2>
            INVENTORY BROWSER
        </h2>

        <span class="inventory-status" style="color:#91e5a9;border-color:rgba(70,190,105,.36);background:rgba(70,190,105,.09);">LIVE</span>

    </div>

</div>


<p>

    Browse your inventory folders and items.
Search, sort and inspect item details in the
live graphical Inventory Browser.

</p>


<a
    class="button-primary inventory-button"
    href="<?= $fromAdmin ? '/Other/user-inventory-browser.php' : '/Other/FreshUserDashboardExact/UserPages/inventory-browser.php' ?>">

    OPEN INVENTORY BROWSER

</a>


</article>



<!-- =========================================================
     CLEAN INVENTORY
     ========================================================= -->

<article class="inventory-card card">


<div class="inventory-card-heading">

    <?=ag_icon(
    'refresh',
    null,
    'inventory-v2-icon ag-icon-badge ag-icon-large ag-badge-cyan'
)?>

    <div>

        <h2>
            CLEAN INVENTORY
        </h2>

        <span class="inventory-status" style="color:#91e5a9;border-color:rgba(70,190,105,.36);background:rgba(70,190,105,.09);">LIVE</span>

    </div>

</div>


<p>

    Prepare a clean inventory using the MAIN
INVENTORY and CLEAN INVENTORY workspaces.
Move only the folders and items you want to keep.

</p>


<a
    class="button-primary inventory-button"
    href="/Other/user-clean-inventory.php">

    OPEN CLEAN INVENTORY

</a>


</article>



<!-- =========================================================
     IAR BACKUPS
     ========================================================= -->

<article class="inventory-card card">


<div class="inventory-card-heading">

    <?=ag_icon(
    'backup',
    null,
    'inventory-v2-icon ag-icon-badge ag-icon-large ag-badge-green'
)?>

    <div>

        <h2>
            IAR BACKUPS
        </h2>

        <span class="inventory-status" style="color:#91e5a9;border-color:rgba(70,190,105,.36);background:rgba(70,190,105,.09);">LIVE</span>

    </div>

</div>


<p>

    Create, download, upload and restore your
personal IAR backups using the two-slot
rotation and built-in safety checks.

</p>


<a
    class="button-primary inventory-button"
    href="/Other/user-iar-backups.php">

    OPEN IAR BACKUPS

</a>


</article>


</section>



<section class="inventory-note panel">

<?php if ($fromAdmin): ?>

    These tools manage the signed Grid Owner's own inventory.
    To create an IAR backup for another local avatar, use
    Control Panel &gt; USER IAR BACKUP.

<?php else: ?>

    Use these tools to browse, clean and back up the inventory
    belonging to your signed-in Grid account.

<?php endif; ?>

</section>



<div class="inventory-actions">


<a
    class="button australia-back-dashboard"
    href="<?= $fromAdmin ? '/Other/admin-dashboard.php' : '/Other/FreshUserDashboardExact/user-dashboard.php' ?>"
    style="
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
    "><?= $fromAdmin ? "BACK TO DASHBOARD" : "BACK TO DASHBOARD" ?></a>


<a
    class="button"
    href="<?= $fromAdmin ? '/Other/user-help.php#inventory' : '/Other/FreshUserDashboardExact/UserPages/help-centre.php' ?>">

    HELP CENTRE

</a>


</div>


</main>


<!-- AUSTRALIA-INVENTORY-COUNT-COLOURS-STYLE-V1 START -->
<style id="ag-inventory-count-colours-v1">
.ag-folder-count{
    margin-left:auto !important;
    display:inline-flex !important;
    align-items:center !important;
    justify-content:flex-end !important;
    min-width:74px !important;
    padding-left:10px !important;

    font-family:Arial,Helvetica,sans-serif !important;
    font-size:12px !important;
    font-weight:900 !important;
    letter-spacing:.04em !important;
    text-transform:uppercase !important;
    white-space:nowrap !important;

    color:#ffc94d !important;
    text-shadow:
        0 1px 0 rgba(0,0,0,.65),
        0 0 8px rgba(255,201,77,.18) !important;
}

.ag-folder-count.zero{
    color:#ff5a5a !important;
    text-shadow:
        0 1px 0 rgba(0,0,0,.65),
        0 0 8px rgba(255,90,90,.18) !important;
}

.folder-button,
.folder-node > .folder-button,
#folderTree .folder-button,
#mainFolders .folder-button,
#cleanFolderTree .folder-button{
    gap:10px !important;
}

.folder-name,
.folder-label,
.folder-title,
.tree-label{
    flex:1 1 auto !important;
    min-width:0 !important;
}
</style>
<!-- AUSTRALIA-INVENTORY-COUNT-COLOURS-STYLE-V1 END -->
<!-- AUSTRALIA-INVENTORY-COUNT-COLOURS-SCRIPT-V1 START -->
<script id="ag-inventory-count-colours-js-v1">
(function(){
    function parseCountText(text){
        if(!text){ return null; }

        text = String(text).replace(/\s+/g, " ").trim();

        let m = text.match(/^(.*?)[ ]*\(([0-9]+)\)$/);
        if(m){
            return {
                name: m[1].trim(),
                count: parseInt(m[2],10),
                empty: parseInt(m[2],10) === 0
            };
        }

        m = text.match(/^(.*?)[ ]+([0-9]+)[ ]+EMPTY$/i);
        if(m){
            return {
                name: m[1].trim(),
                count: parseInt(m[2],10),
                empty: true
            };
        }

        return null;
    }

    function normaliseButton(button){
        if(!button){ return; }

        const nameEl =
            button.querySelector('.folder-name') ||
            button.querySelector('.folder-label') ||
            button.querySelector('.folder-title') ||
            button.querySelector('.tree-label');

        if(!nameEl){ return; }

        let parsed = parseCountText(nameEl.textContent || '');

        if(!parsed){
            const existing =
                button.querySelector('.folder-count') ||
                button.querySelector('.count') ||
                button.querySelector('.item-count') ||
                button.querySelector('.ag-folder-count');

            if(existing){
                const n = (existing.textContent || '').match(/[0-9]+/);
                if(n){
                    parsed = {
                        name: (nameEl.textContent || '').trim(),
                        count: parseInt(n[0],10),
                        empty: parseInt(n[0],10) === 0 || /empty/i.test(existing.textContent || '')
                    };

                    if(!existing.classList.contains('ag-folder-count')){
                        existing.style.display = 'none';
                    }
                }
            }
        }

        if(!parsed){ return; }

        nameEl.textContent = parsed.name;

        let countEl = button.querySelector('.ag-folder-count');
        if(!countEl){
            countEl = document.createElement('span');
            countEl.className = 'ag-folder-count';
            button.appendChild(countEl);
        }

        const value = Number.isFinite(parsed.count) ? parsed.count : 0;
        const empty = parsed.empty || value === 0;

        countEl.textContent = empty ? '0 EMPTY' : String(value);
        countEl.classList.toggle('zero', empty);
    }

    function runAll(){
        document.querySelectorAll(
            '#folderTree .folder-button,' +
            '#mainFolders .folder-button,' +
            '#cleanFolderTree .folder-button,' +
            '.folder-tree .folder-button'
        ).forEach(normaliseButton);
    }

    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', runAll);
    } else {
        runAll();
    }

    const obs = new MutationObserver(function(){
        window.requestAnimationFrame(runAll);
    });

    obs.observe(document.documentElement, {
        childList: true,
        subtree: true
    });

    window.addEventListener('load', runAll);
})();
</script>
<!-- AUSTRALIA-INVENTORY-COUNT-COLOURS-SCRIPT-V1 END -->
<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>






