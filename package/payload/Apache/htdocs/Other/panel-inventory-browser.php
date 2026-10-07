<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/icons.php';

$session =
    ag_require_admin();

ag_no_cache();

$avatar =
    function_exists('ag_avatar_name')
        ? ag_avatar_name($session)
        : '';

$level =
    function_exists('ag_user_level')
        ? (int)ag_user_level($session)
        : 0;

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Inventory Browser</title>


<!-- ========================================================
     EXACT SAME NEW CONTROL CENTER THEME AS PRIVATE REGIONS
     ======================================================== -->

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-panel-v1.css?v=8">

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-regions-v5.css?v=6">


<style>

/*
 * ============================================================
 * INVENTORY BROWSER LAYOUT ONLY
 *
 * NO theme colours.
 * NO gradients.
 * NO old Inventory card styles.
 * NO old Inventory theme.
 *
 * Visual appearance comes from the Control Center stylesheets.
 * ============================================================
 */


.ib-workspace{

    display:grid;

    grid-template-columns:
        minmax(230px,280px)
        minmax(420px,1fr)
        minmax(270px,320px);

    gap:14px;

    margin-top:18px;
}


.ib-card{

    min-width:0;

    min-height:590px;

    display:flex;

    flex-direction:column;
}


.ib-card-body{

    min-height:0;

    flex:1 1 auto;

    overflow:auto;

    padding:12px;
}


.ib-folder-row,
.ib-item-row{

    width:100%;

    display:block;

    text-align:left;

    cursor:pointer;
}


.ib-folder-row{

    margin-bottom:5px;
}


.ib-folder-row.ib-selected,
.ib-item-row.ib-selected{

    font-weight:900;
}


.ib-folder-line{

    display:grid;

    grid-template-columns:
        18px
        minmax(0,1fr)
        auto;

    gap:8px;

    align-items:center;
}


.ib-folder-name,
.ib-item-name{

    min-width:0;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;
}


.ib-folder-children{

    padding-left:18px;
}


.ib-folder-children.ib-closed{

    display:none;
}


.ib-item-meta{

    display:block;

    margin-top:4px;
}


.ib-current-folder{

    margin:12px;

    margin-bottom:0;
}


.ib-details{

    display:grid;

    gap:10px;
}


.ib-detail-value{

    overflow-wrap:anywhere;
}


.ib-empty{

    min-height:150px;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:20px;

    text-align:center;
}


.ib-duplicates{

    display:none;

    margin-top:18px;
}


.ib-duplicates.ib-open{

    display:block;
}


.ib-duplicate-list{

    display:grid;

    gap:10px;

    margin-top:12px;
}


.ib-status{

    margin-top:18px;
}


@media(max-width:1200px){

    .ib-workspace{

        grid-template-columns:
            220px
            minmax(340px,1fr)
            250px;
    }
}


@media(max-width:900px){

    .ib-workspace{

        grid-template-columns:1fr;
    }


    .ib-card{

        min-height:400px;
    }
}

</style>
<link
    rel="stylesheet"
    href="/Other/assets/css/ag-sentinel-icons-v1.css?v=20260902-sentinel-v1">

<!-- AUSTRALIA INVENTORY SENTINEL ICONS START -->

<style id="australia-inventory-sentinel-icons">

.ib-tree-sentinel{
    display:block;
    width:18px;
    height:18px;
    object-fit:contain;
}

.ib-item-sentinel{
    display:block;
    width:20px;
    height:20px;
    object-fit:contain;
}

.ib-folder-icon{
    display:grid;
    place-items:center;
}

.ib-item-icon{
    display:grid;
    place-items:center;
}

</style>

<!-- AUSTRALIA INVENTORY SENTINEL ICONS END -->

<!-- AUSTRALIA INVENTORY FOLDER MENU V3 START -->

<style id="australia-inventory-folder-menu-v3">

/* ==========================================================
   FOLDER NAVIGATOR CONTAINER
   ========================================================== */

.ib-card:first-child .ib-card-body{
    padding:8px;
}


/* ==========================================================
   MENU ROW
   ========================================================== */

.ib-folder-row{

    width:100%;

    min-height:38px;

    margin:
        0
        0
        4px
        0;

    padding:
        4px
        8px
        4px
        6px;

    display:block;

    box-sizing:border-box;

    border:
        1px solid
        rgba(255,255,255,.045);

    border-left:
        3px solid
        transparent;

    border-radius:
        4px;

    background:
        rgba(255,255,255,.018);

    color:
        inherit;

    text-align:left;

    cursor:pointer;

    user-select:none;

    transition:
        background .12s ease,
        border-color .12s ease,
        transform .12s ease;
}


/* ==========================================================
   HOVER
   ========================================================== */

.ib-folder-row:hover{

    background:
        rgba(214,164,59,.055);

    border-color:
        rgba(214,164,59,.18);

    border-left-color:
        rgba(214,164,59,.50);
}


/* ==========================================================
   SELECTED FOLDER
   ========================================================== */

.ib-folder-row.ib-selected{

    background:
        rgba(214,164,59,.10);

    border-color:
        rgba(214,164,59,.32);

    border-left-color:
        rgba(240,186,61,.95);
}


/* ==========================================================
   ROW GRID
   ========================================================== */

.ib-folder-line{

    width:100%;

    min-height:28px;

    display:grid;

    grid-template-columns:
        15px
        24px
        minmax(0,1fr)
        auto;

    align-items:center;

    gap:7px;
}


/* ==========================================================
   EXPAND ARROW
   ========================================================== */

.ib-folder-toggle{

    width:15px;

    height:20px;

    display:flex;

    align-items:center;

    justify-content:center;

    color:
        rgba(214,164,59,.74);

    font-size:15px;

    font-weight:900;

    line-height:1;
}


/* ==========================================================
   OUR SENTINEL FOLDER ICON
   ========================================================== */

.ib-folder-icon{

    width:24px;

    height:24px;

    display:flex;

    align-items:center;

    justify-content:center;
}


.ib-folder-icon
.ib-tree-sentinel{

    width:22px;

    height:22px;

    display:block;

    object-fit:contain;
}


/* ==========================================================
   FOLDER NAME
   ========================================================== */

.ib-folder-name{

    min-width:0;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    font-size:11px;

    font-weight:700;

    letter-spacing:.01em;
}


.ib-folder-row.ib-selected
.ib-folder-name{

    font-weight:900;
}


/* ==========================================================
   ITEM COUNT BADGE
   ========================================================== */

.ib-folder-count{

    min-width:24px;

    height:20px;

    padding:
        0
        6px;

    display:flex;

    align-items:center;

    justify-content:center;

    box-sizing:border-box;

    border:
        1px solid
        rgba(255,255,255,.045);

    border-radius:10px;

    background:
        rgba(255,255,255,.035);

    font-size:8px;

    font-weight:800;

    line-height:1;
}


.ib-folder-row.ib-selected
.ib-folder-count{

    border-color:
        rgba(214,164,59,.22);

    background:
        rgba(214,164,59,.08);
}


/* ==========================================================
   CHILD FOLDER TREE
   ========================================================== */

.ib-folder-children{

    position:relative;

    margin-left:14px;

    padding-left:12px;

    border-left:
        1px solid
        rgba(214,164,59,.12);
}


.ib-folder-children.ib-closed{
    display:none;
}


/* ==========================================================
   MAKE DEEP FOLDERS EASIER TO FOLLOW
   ========================================================== */

.ib-folder-children
.ib-folder-row{

    min-height:34px;
}


.ib-folder-children
.ib-folder-line{

    min-height:26px;
}


/* ==========================================================
   KEYBOARD FOCUS
   ========================================================== */

.ib-folder-row:focus-visible{

    outline:
        1px solid
        rgba(240,186,61,.65);

    outline-offset:
        1px;
}

</style>

<!-- AUSTRALIA INVENTORY FOLDER MENU V3 END -->

<!-- AUSTRALIA INVENTORY ITEM MENU V4 START -->

<style id="australia-inventory-item-menu-v4">

/* ==========================================================
   ITEMS PANEL
   Same compact list layout as folder tree.
   ========================================================== */

#ib-items{
    padding:8px;
}


.ib-item-row{

    width:100%;

    min-height:38px;

    margin:
        0
        0
        4px
        0;

    padding:
        4px
        8px
        4px
        6px;

    display:block;

    box-sizing:border-box;

    border:
        1px solid
        rgba(255,255,255,.045);

    border-left:
        3px solid
        transparent;

    border-radius:
        4px;

    background:
        rgba(255,255,255,.018);

    color:
        inherit;

    text-align:left;

    cursor:pointer;

    user-select:none;

    transition:
        background .12s ease,
        border-color .12s ease;
}


.ib-item-row:hover{

    background:
        rgba(214,164,59,.055);

    border-color:
        rgba(214,164,59,.18);

    border-left-color:
        rgba(214,164,59,.50);
}


.ib-item-row.ib-selected{

    background:
        rgba(214,164,59,.10);

    border-color:
        rgba(214,164,59,.32);

    border-left-color:
        rgba(240,186,61,.95);
}


.ib-item-line{

    width:100%;

    min-height:28px;

    display:grid;

    grid-template-columns:
        15px
        24px
        minmax(0,1fr)
        auto;

    align-items:center;

    gap:7px;
}


.ib-item-toggle{

    width:15px;

    height:20px;

    display:block;
}


.ib-item-icon{

    width:24px;

    height:24px;

    display:flex;

    align-items:center;

    justify-content:center;

    border:0;

    background:transparent;
}


.ib-item-type-icon{

    width:22px;

    height:22px;

    display:block;

    object-fit:contain;
}


.ib-item-name{

    min-width:0;

    display:block;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    font-size:11px;

    font-weight:700;
}


.ib-item-row.ib-selected
.ib-item-name{

    font-weight:900;
}


.ib-item-type{

    min-width:50px;

    height:20px;

    padding:
        0
        7px;

    display:flex;

    align-items:center;

    justify-content:center;

    box-sizing:border-box;

    border:
        1px solid
        rgba(255,255,255,.045);

    border-radius:10px;

    background:
        rgba(255,255,255,.035);

    font-size:8px;

    font-weight:800;

    white-space:nowrap;
}


.ib-item-row.ib-selected
.ib-item-type{

    border-color:
        rgba(214,164,59,.22);

    background:
        rgba(214,164,59,.08);
}


/* ==========================================================
   ITEM DETAILS
   Keep its fields in the same compact list family.
   ========================================================== */

#ib-details
.rp-performance-item{

    margin:
        0
        0
        4px
        0;

    padding:
        8px
        9px;

    border-left:
        3px solid
        transparent;

    border-radius:
        4px;
}


/* ==========================================================
   DUPLICATE LIST
   Same spacing as the inventory tree.
   ========================================================== */

.ib-duplicate-list{

    gap:4px;
}


.ib-duplicate-list
.rp-performance-item{

    margin:0;

    padding:
        8px
        9px;

    border-left:
        3px solid
        transparent;

    border-radius:
        4px;
}


.ib-item-row:focus-visible{

    outline:
        1px solid
        rgba(240,186,61,.65);

    outline-offset:
        1px;
}

</style>

<!-- AUSTRALIA INVENTORY ITEM MENU V4 END -->
<!-- AUSTRALIA FIRESTORM TYPE FILTER V8 CSS START -->

<style id="australia-firestorm-type-filter-v8-css">

.fs8-type-filter{
    min-width:126px;
    max-width:160px;
    cursor:pointer;
    color-scheme:dark;
}


/*
 * Keep the TYPE control in the same normal state as
 * COLLAPSE ALL and REFRESH even while it has focus/open.
 */

#fs8-type-filter:focus,
#fs8-type-filter:focus-visible,
#fs8-type-filter:active{
    outline:none !important;
}


/*
 * OPEN DROPDOWN LIST
 *
 * Exact dark button-face colour from the existing UI.
 */

#fs8-type-filter option{
    background-color:#0f110e !important;
    color:#f4f5f4 !important;
    font-weight:800 !important;
}


/*
 * Current option stays dark as well.
 */

#fs8-type-filter option:checked{
    background-color:#2a2e28 !important;
    color:#ffffff !important;
}


.fs8-type-hidden{
    display:none !important;
}

</style>

<!-- AUSTRALIA FIRESTORM TYPE FILTER V8 CSS END -->
</head>


<body>


<main class="cp-page rp-page">


<!-- ========================================================
     SAME PAGE INTRO AS PRIVATE REGIONS
     ======================================================== -->

<section class="cp-intro">


    <div class="cp-intro-left">


        <div class="cp-intro-icon">

            <?=ag_icon(
                'inventory',
                null,
                'cp-intro-svg'
            )?>

        </div>


        <div>

            <div class="cp-intro-kicker">
                DASHBOARD
            </div>

            <div class="cp-intro-title">
                Inventory Browser
            </div>

        </div>


    </div>


    <div
        id="fs9-avatar-context"
        class="cp-intro-note"
        data-avatar="<?=htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8')?>"
        data-level="<?=htmlspecialchars((string)$level, ENT_QUOTES, 'UTF-8')?>"
    >

        <?=htmlspecialchars(
            $avatar,
            ENT_QUOTES,
            'UTF-8'
        )?>

        · LEVEL

        <?=htmlspecialchars(
            (string)$level,
            ENT_QUOTES,
            'UTF-8'
        )?>

    </div>


</section>



<!-- ========================================================
     SAME OVERVIEW COMPONENT AS PRIVATE REGIONS
     ======================================================== -->

<section class="rp-overview">


    <div class="rp-overview-heading">


        <div>

            <span class="rp-eyebrow">
                INVENTORY OPERATIONS
            </span>

            <h2>
                Live Inventory Browser
            </h2>

            <p>
                Browse and inspect the current OpenSim inventory.
            </p>

        </div>


        <div class="rp-overview-tools">


            <button
                id="ib-duplicates-button"
                type="button"
                class="cp-button">
                DUPLICATES
            </button>


        </div>


    </div>



    <!-- SAME SUMMARY COMPONENT AS PRIVATE REGIONS -->

    <div class="rp-summary">


        <div class="rp-summary-item">

            <span>
                FOLDERS
            </span>

            <strong id="ib-folder-total">
                —
            </strong>

            <small>
                Inventory folders
            </small>

        </div>


        <div class="rp-summary-item">

            <span>
                ITEMS
            </span>

            <strong id="ib-item-total">
                —
            </strong>

            <small>
                Inventory items
            </small>

        </div>


        <div class="rp-summary-item">

            <span>
                CURRENT VIEW
            </span>

            <strong id="ib-current-view">
                FOLDERS
            </strong>

            <small id="ib-current-note">
                Select a folder
            </small>

        </div>


        <div class="rp-summary-item">

            <span>
                MODE
            </span>

            <strong class="online">
                READ ONLY
            </strong>

            <small>
                Nothing is changed
            </small>

        </div>


    </div>


</section>



<!-- ========================================================
     DUPLICATE FINDER
     ALSO BUILT FROM CURRENT CONTROL CENTER COMPONENTS
     ======================================================== -->

<section
    id="ib-duplicates"
    class="rp-overview ib-duplicates">


    <div class="rp-overview-heading">


        <div>

            <span class="rp-eyebrow">
                INVENTORY ANALYSIS
            </span>

            <h2>
                Duplicate Finder
            </h2>

            <p>
                Read only — nothing will be deleted.
            </p>

        </div>


        <div class="rp-overview-tools">

            <button
                id="ib-close-duplicates"
                type="button"
                class="cp-button">
                CLOSE
            </button>

        </div>


    </div>


    <div
        id="ib-duplicate-list"
        class="ib-duplicate-list">

        <div class="rp-performance-item">
            Select DUPLICATES to analyse the inventory.
        </div>

    </div>


</section>



<!-- ========================================================
     INVENTORY WORKSPACE
     THE SPLIT BROWSER IS THE ONLY IMPLEMENTATION
     ======================================================== -->

<section class="ib-workspace fs2-host"></section>



<section class="rp-overview ib-status">


    <div class="rp-overview-heading">


        <div>

            <span class="rp-eyebrow">
                STATUS
            </span>

            <p id="ib-status">
                Loading inventory...
            </p>

        </div>


    </div>


</section>


</main>



<script>
(() => {
'use strict';
const API='/Other/inventory-browser-api.php';
const ZERO='00000000-0000-0000-0000-000000000000';
const $=id=>document.getElementById(id);
const ui={duplicatesButton:$('ib-duplicates-button'),duplicates:$('ib-duplicates'),closeDuplicates:$('ib-close-duplicates'),duplicateList:$('ib-duplicate-list'),status:$('ib-status')};
function escapeHtml(
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


function setStatus(
    text
){

    ui.status.textContent =
        text;
}


async function request(
    action,
    parameters = {}
){

    const url =
        new URL(
            API,
            window.location.origin
        );


    url.searchParams.set(
        "api",
        action
    );


    Object.entries(
        parameters
    )
    .forEach(
        ([key,value]) => {

            url.searchParams.set(
                key,
                value
            );
        }
    );


    const response =
        await fetch(
            url.toString(),
            {
                cache:
                    "no-store",

                credentials:
                    "same-origin"
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
            raw.trim() ||
            "Inventory API returned invalid data."
        );
    }


    if(
        !response.ok ||
        !data ||
        data.ok === false
    ){

        throw new Error(
            data?.error ||
            "Inventory request failed."
        );
    }


    return data;
}



async function duplicates(){
    if(ui.duplicatesButton.disabled) return;

    ui.duplicates.classList.add(
        "ib-open"
    );


    ui.duplicateList.innerHTML =
        `
        <div class="rp-performance-item">
            Scanning inventory...
        </div>
        `;


    ui.duplicatesButton.disabled =
        true;


    try{

        setStatus(
            "Scanning duplicates..."
        );


        const data =
            await request(
                "duplicates"
            );


        const items =
            Array.isArray(
                data.items
            )
            ? data.items
            : [];


        const groups =
            new Map();


        items.forEach(
            item => {

                const asset =
                    String(
                        item.assetId ||
                        ""
                    );


                const key =
                    asset &&
                    asset !== ZERO
                    ? `asset:${asset}`
                    : `name:${String(item.name || "").toLowerCase()}`;


                if(
                    !groups.has(
                        key
                    )
                ){

                    groups.set(
                        key,
                        []
                    );
                }


                groups.get(
                    key
                ).push(
                    item
                );
            }
        );


        const duplicateGroups =
            Array.from(
                groups.values()
            )
            .filter(
                group =>
                    group.length > 1
            );


        if(
            duplicateGroups.length === 0
        ){

            ui.duplicateList.innerHTML =
                `
                <div class="rp-performance-item">
                    No duplicate groups were found.
                </div>
                `;


            setStatus(
                "Duplicate scan complete."
            );

            return;
        }


        ui.duplicateList.innerHTML =
            duplicateGroups
            .map(
                group => {

                    const item =
                        group[0];


                    return `
                        <div class="rp-performance-item">

                            <strong>
                                ${escapeHtml(item.name || "Unnamed Item")}
                            </strong>

                            <small>
                                ${group.length} copies
                            </small>

                        </div>
                    `;
                }
            )
            .join(
                ""
            );


        setStatus(
            `${duplicateGroups.length} duplicate group(s) found.`
        );
    }
    catch(error){

        ui.duplicateList.innerHTML =
            `
            <div class="rp-performance-item">
                ${escapeHtml(error.message)}
            </div>
            `;


        setStatus(
            error.message
        );
    }
    finally{

        ui.duplicatesButton.disabled =
            false;
    }
}



ui.duplicatesButton.addEventListener('click',duplicates);
ui.closeDuplicates.addEventListener('click',()=>ui.duplicates.classList.remove('ib-open'));
})();
</script>

<!-- AUSTRALIA FIRESTORM SPLIT INVENTORY V2 START -->

<style id="australia-firestorm-split-v2-css">

/* ==========================================================
   SPLIT INVENTORY WORKSPACE
   ========================================================== */


.fs2-host{
    display:block !important;
}


/* ==========================================================
   LEFT INVENTORY + RIGHT INSPECTOR
   ========================================================== */

.fs2-shell{

    width:100%;

    display:grid;

    grid-template-columns:
        minmax(0,1fr) minmax(0,1fr);

    gap:12px;

    align-items:start;
}


.fs2-card{

    min-width:0;

    overflow:hidden;
}


.fs2-inventory-card,
.fs2-details-card{

    min-height:
        clamp(
            620px,
            72vh,
            980px
        );
}


.fs2-details-card{

    position:sticky;

    top:0;
}


/* ==========================================================
   HEADERS
   ========================================================== */

.fs2-header{

    display:flex;

    align-items:center;

    gap:9px;
}


.fs2-header-icon{

    width:26px;

    height:26px;

    display:block;

    object-fit:contain;
}


.fs2-header-text{

    display:flex;

    flex-direction:column;

    gap:2px;
}


.fs2-header-text strong{

    font-size:11px;

    font-weight:900;

    letter-spacing:.05em;
}


.fs2-header-text small{

    opacity:.60;

    font-size:8px;

    font-weight:700;
}


/* ==========================================================
   FIRESTORM SEARCH BAR
   ========================================================== */

.fs2-search-bar{

    padding:
        8px
        9px;

    border-bottom:
        1px solid
        rgba(255,255,255,.05);
}


.fs2-search{

    width:100%;

    height:31px;

    padding:
        0
        12px;

    box-sizing:border-box;

    border:
        1px solid
        rgba(214,164,59,.32);

    border-radius:15px;

    outline:none;

    background:
        rgba(1,5,7,.72);

    color:#e5eaec;

    font-size:10px;
}


.fs2-search:focus{

    border-color:
        rgba(240,186,61,.72);
}


/* ==========================================================
   FIRESTORM TABS
   ========================================================== */

.fs2-tabs{

    height:29px;

    padding:
        0
        8px;

    display:flex;

    align-items:flex-end;

    border-bottom:
        1px solid
        rgba(255,255,255,.06);
}


.fs2-tab{

    height:27px;

    min-width:72px;

    padding:
        0
        11px;

    border:0;

    border-radius:
        3px
        3px
        0
        0;

    background:
        rgba(255,255,255,.025);

    color:
        rgba(220,227,230,.58);

    font-size:8px;
}


.fs2-tab-active{

    background:
        rgba(214,164,59,.13);

    color:#f1cf79;

    font-weight:900;
}


/* ==========================================================
   SMALL TOOL BAR
   ========================================================== */

.fs2-tools{

    min-height:34px;

    padding:
        5px
        8px;

    display:flex;

    align-items:center;

    gap:6px;

    box-sizing:border-box;

    border-bottom:
        1px solid
        rgba(255,255,255,.05);
}


.fs2-tool{

    min-height:24px !important;

    padding:
        3px
        9px !important;

    font-size:8px !important;
}


.fs2-readonly{

    margin-left:auto;

    opacity:.58;

    font-size:8px;

    font-weight:800;
}


/* ==========================================================
   INVENTORY TREE
   ========================================================== */

.fs2-tree-scroll{

    height:
        clamp(
            520px,
            64vh,
            850px
        );

    overflow:auto;

    padding:
        6px
        6px
        10px;

    box-sizing:border-box;

    background:
        rgba(0,0,0,.10);

    scrollbar-width:thin;
}


.fs2-tree{

    min-width:360px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size:10px;
}


.fs2-node{

    display:block;
}


.fs2-children{

    padding-left:17px;
}


.fs2-children.fs2-closed{

    display:none;
}


/* ==========================================================
   FIRESTORM ROW
   ========================================================== */

.fs2-row{

    width:100%;

    height:23px;

    padding:
        0
        4px;

    display:grid;

    grid-template-columns:
        12px
        18px
        minmax(0,1fr);

    align-items:center;

    gap:4px;

    box-sizing:border-box;

    border:0;

    border-radius:2px;

    background:transparent;

    color:#d6dddf;

    cursor:pointer;

    user-select:none;
}


.fs2-row:hover{

    background:
        rgba(255,255,255,.04);
}


.fs2-row.fs2-selected{

    background:
        rgba(190,128,56,.27);

    color:#ffffff;
}


.fs2-root-row{

    height:25px;

    color:#eee1bc;

    font-weight:900;
}


.fs2-toggle{

    width:12px;

    height:18px;

    display:flex;

    align-items:center;

    justify-content:center;

    color:#ddb13d;

    font-size:13px;

    line-height:1;
}


.fs2-icon{

    width:17px;

    height:17px;

    display:block;

    object-fit:contain;
}


.fs2-name{

    min-width:0;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;
}


.fs2-item-row .fs2-name{

    color:#cbd3d6;
}


/* ==========================================================
   DETAILS PANE
   ========================================================== */

.fs2-details-body{

    height:
        clamp(
            555px,
            66vh,
            880px
        );

    overflow:auto;

    padding:12px;

    box-sizing:border-box;

    scrollbar-width:thin;
}


.fs2-details-empty{

    min-height:260px;

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

    gap:10px;

    padding:30px;

    box-sizing:border-box;

    text-align:center;

    color:
        rgba(214,222,225,.52);
}


.fs2-details-empty img{

    width:54px;

    height:54px;

    object-fit:contain;

    opacity:.70;
}


.fs2-detail-hero{

    padding:
        4px
        2px
        15px;

    display:grid;

    grid-template-columns:
        54px
        minmax(0,1fr);

    align-items:center;

    gap:12px;

    border-bottom:
        1px solid
        rgba(255,255,255,.06);
}


.fs2-detail-hero-icon{

    width:52px;

    height:52px;

    object-fit:contain;
}


.fs2-detail-name{

    margin:0;

    color:#f0f3f4;

    font-size:15px;

    line-height:1.25;

    overflow-wrap:anywhere;
}


.fs2-detail-type{

    margin-top:5px;

    color:#d9ad43;

    font-size:9px;

    font-weight:900;

    letter-spacing:.08em;
}


.fs2-avatar-badge{

    display:none;

    margin-top:6px;

    width:max-content;

    padding:
        3px
        6px;

    border:
        1px solid
        rgba(214,164,59,.24);

    border-radius:3px;

    background:
        rgba(214,164,59,.08);

    color:#e1bd65;

    font-size:7px;

    font-weight:900;
}


.fs2-avatar-badge.fs2-show{
    display:block;
}


/* ==========================================================
   DESCRIPTION
   ========================================================== */

.fs2-description{

    margin-top:12px;

    padding:10px;

    border:
        1px solid
        rgba(255,255,255,.05);

    border-radius:4px;

    background:
        rgba(255,255,255,.018);
}


.fs2-section-title{

    margin-bottom:6px;

    color:#d6aa40;

    font-size:8px;

    font-weight:900;

    letter-spacing:.08em;
}


.fs2-description-text{

    min-height:26px;

    color:
        rgba(222,229,231,.78);

    font-size:9px;

    line-height:1.55;

    white-space:pre-wrap;

    overflow-wrap:anywhere;
}


/* ==========================================================
   DETAIL DATA ROWS
   ========================================================== */

.fs2-detail-grid{

    margin-top:12px;

    display:grid;

    grid-template-columns:
        minmax(0,1fr);

    gap:5px;
}


.fs2-detail-row{

    padding:
        7px
        9px;

    display:grid;

    grid-template-columns:
        105px
        minmax(0,1fr);

    gap:8px;

    align-items:start;

    border:
        1px solid
        rgba(255,255,255,.045);

    border-radius:3px;

    background:
        rgba(255,255,255,.014);
}


.fs2-detail-label{

    color:
        rgba(210,219,222,.48);

    font-size:7px;

    font-weight:900;

    letter-spacing:.05em;
}


.fs2-detail-value{

    color:#dce3e5;

    font-size:8px;

    line-height:1.45;

    overflow-wrap:anywhere;

    user-select:text;
}


.fs2-status{

    min-height:29px;

    padding:
        5px
        9px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:10px;

    box-sizing:border-box;

    border-top:
        1px solid
        rgba(255,255,255,.05);

    color:
        rgba(211,220,223,.58);

    font-size:8px;
}


/* ==========================================================
   SEARCH RESULTS
   ========================================================== */

.fs2-message{

    padding:14px 7px;

    color:
        rgba(216,224,226,.55);

    font-size:9px;
}


/* ==========================================================
   RESPONSIVE
   ========================================================== */

@media(max-width:1000px){

    .fs2-shell{

        grid-template-columns:
            minmax(0,1fr) minmax(0,1fr);
    }

}


@media(max-width:760px){

    .fs2-shell{

        grid-template-columns:
            1fr;
    }


    .fs2-details-card{

        position:static;
    }
}

/* AUSTRALIA EQUAL INVENTORY DETAILS V1 START */ .fs2-shell{grid-template-columns:calc(50% - 6px) calc(50% - 6px)!important}.fs2-shell>.fs2-inventory-card,.fs2-shell>.fs2-details-card{width:100%!important;max-width:none!important;min-width:0!important;box-sizing:border-box!important;grid-column:auto!important}@media(max-width:760px){.fs2-shell{grid-template-columns:1fr!important}} /* AUSTRALIA EQUAL INVENTORY DETAILS V1 END *//* AUSTRALIA EQUAL CARD HEIGHT V1 START */ @media(min-width:761px){.fs2-shell{align-items:stretch!important}.fs2-shell>.fs2-inventory-card,.fs2-shell>.fs2-details-card{align-self:stretch!important}.fs2-shell>.fs2-details-card{display:flex!important;flex-direction:column!important}.fs2-shell>.fs2-details-card>.fs2-details-body{flex:1 1 auto!important;height:auto!important;min-height:0!important}} /* AUSTRALIA EQUAL CARD HEIGHT V1 END */</style>



<!-- AUSTRALIA FIRESTORM DETAILS PERMISSIONS V4 CSS START -->

<style id="australia-firestorm-details-permissions-v4">

.fs4-owner-card{
    display:grid;
    grid-template-columns:34px minmax(0,1fr);
    gap:9px;
    align-items:center;
    margin:0 0 12px 0;
    padding:9px;
    border:1px solid rgba(214,164,59,.16);
    border-radius:4px;
    background:rgba(214,164,59,.035);
}

.fs4-owner-icon{
    width:32px;
    height:32px;
    object-fit:contain;
}

.fs4-owner-label{
    color:#d8ad46;
    font-size:7px;
    font-weight:900;
    letter-spacing:.08em;
}

.fs4-owner-title{
    margin-top:2px;
    color:#eef2f3;
    font-size:11px;
    font-weight:900;
}

.fs4-owner-id{
    margin-top:3px;
    color:rgba(217,225,228,.58);
    font-size:7px;
    overflow-wrap:anywhere;
    user-select:text;
}

.fs4-owner-totals{
    margin-top:4px;
    color:rgba(217,225,228,.40);
    font-size:7px;
}

.fs4-section{
    margin-top:12px;
    padding-top:10px;
    border-top:1px solid rgba(255,255,255,.055);
}

.fs4-title{
    margin-bottom:7px;
    color:#d8ad46;
    font-size:8px;
    font-weight:900;
    letter-spacing:.08em;
}

.fs4-permission-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:6px;
}

.fs4-permission-card{
    min-width:0;
    padding:7px;
    border:1px solid rgba(255,255,255,.055);
    border-radius:4px;
    background:rgba(255,255,255,.016);
}

.fs4-permission-name{
    margin-bottom:5px;
    color:rgba(225,232,234,.68);
    font-size:7px;
    font-weight:900;
}

.fs4-chips{
    display:flex;
    flex-wrap:wrap;
    gap:3px;
}

.fs4-chip{
    min-width:50px;
    padding:3px 4px;
    box-sizing:border-box;
    border-radius:3px;
    text-align:center;
    font-size:7px;
    font-weight:900;
}

.fs4-yes{
    border:1px solid rgba(91,192,118,.34);
    background:rgba(91,192,118,.11);
    color:#91d4a1;
}

.fs4-no{
    border:1px solid rgba(205,90,90,.27);
    background:rgba(205,90,90,.08);
    color:#dc9191;
}

.fs4-na{
    border:1px solid rgba(255,255,255,.06);
    background:rgba(255,255,255,.025);
    color:rgba(215,223,225,.34);
}

.fs4-mask{
    margin-top:5px;
    color:rgba(215,223,225,.30);
    font-size:6px;
    overflow-wrap:anywhere;
}

.fs4-note{
    margin-top:6px;
    color:rgba(215,223,225,.47);
    font-size:7px;
    line-height:1.45;
}

.fs4-info{
    display:grid;
}

.fs4-info-row{
    display:grid;
    grid-template-columns:110px minmax(0,1fr);
    gap:8px;
    padding:5px 0;
    border-bottom:1px solid rgba(255,255,255,.035);
}

.fs4-info-label{
    color:rgba(215,223,225,.43);
    font-size:7px;
    font-weight:900;
}

.fs4-info-value{
    min-width:0;
    color:#dce3e5;
    font-size:8px;
    overflow-wrap:anywhere;
    user-select:text;
}

.fs4-source{
    display:inline-block;
    margin-top:5px;
    padding:3px 6px;
    border:1px solid rgba(214,164,59,.18);
    border-radius:3px;
    background:rgba(214,164,59,.055);
    color:#d7b15b;
    font-size:7px;
    font-weight:900;
}

.fs4-error{
    margin-top:7px;
    padding:6px;
    border:1px solid rgba(205,90,90,.25);
    border-radius:3px;
    background:rgba(205,90,90,.07);
    color:#dc9191;
    font-size:7px;
}

@media(max-width:900px){

    .fs4-permission-grid{
        grid-template-columns:1fr;
    }
}

</style>

<!-- AUSTRALIA FIRESTORM DETAILS PERMISSIONS V4 CSS END -->


<!-- AUSTRALIA FIRESTORM CONTEXT MENU V5 CSS START -->

<style id="australia-firestorm-context-menu-v5">

.fs5-context-menu{

    position:fixed;

    z-index:2147483000;

    min-width:220px;

    padding:5px;

    border:
        1px solid
        rgba(214,164,59,.35);

    border-radius:4px;

    background:
        rgba(12,16,14,.985);

    box-shadow:
        0 12px 34px
        rgba(0,0,0,.58);

    backdrop-filter:
        blur(4px);

    user-select:none;
}


.fs5-context-title{

    padding:
        6px
        9px
        7px
        9px;

    color:#d8ad46;

    font-size:7px;

    font-weight:900;

    letter-spacing:.08em;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    border-bottom:
        1px solid
        rgba(255,255,255,.055);

    margin-bottom:4px;
}


.fs5-context-item{

    width:100%;

    display:flex;

    align-items:center;

    gap:8px;

    padding:
        7px
        9px;

    border:0;

    border-radius:3px;

    background:transparent;

    color:#dbe2e5;

    font:inherit;

    font-size:8px;

    text-align:left;

    cursor:pointer;
}


.fs5-context-item:hover,
.fs5-context-item:focus{

    outline:none;

    background:
        rgba(214,164,59,.12);

    color:#ffffff;
}


.fs5-context-item[disabled]{

    opacity:.35;

    cursor:default;
}


.fs5-context-item[disabled]:hover{

    background:transparent;

    color:#dbe2e5;
}


.fs5-context-icon{

    width:15px;

    height:15px;

    flex:
        0
        0
        15px;

    object-fit:contain;
}


.fs5-context-separator{

    height:1px;

    margin:
        4px
        6px;

    background:
        rgba(255,255,255,.055);
}

</style>

<!-- AUSTRALIA FIRESTORM CONTEXT MENU V5 CSS END -->


<!-- AUSTRALIA FIRESTORM SEARCH V7 CSS START -->

<style id="australia-firestorm-search-v7">

.fs7-hidden{
    display:none !important;
}


.fs7-mark{

    padding:
        0
        1px;

    border-radius:
        2px;

    background:
        rgba(214,164,59,.34);

    color:
        #ffffff;

    font-weight:
        900;
}


.fs7-folder-match > .fs2-name,
.fs7-item-match > .fs2-name{

    text-shadow:
        0 0 8px
        rgba(214,164,59,.30);
}


.fs7-description-match > .fs2-name{

    border-bottom:
        1px dotted
        rgba(214,164,59,.65);
}


.fs7-empty{

    margin:
        8px;

    padding:
        12px;

    border:
        1px solid
        rgba(214,164,59,.14);

    border-radius:
        4px;

    background:
        rgba(214,164,59,.035);

    color:
        rgba(225,232,234,.62);

    font-size:
        8px;

    text-align:
        center;
}


#fs2-search.fs7-searching{

    border-color:
        rgba(214,164,59,.55);

    box-shadow:
        0 0 0 1px
        rgba(214,164,59,.10);
}

</style>

<!-- AUSTRALIA FIRESTORM SEARCH V7 CSS END -->

<script id="australia-firestorm-split-v2-js">

(function(){

    "use strict";


    const API =
        "/Other/inventory-browser-api.php";


    const LIBRARY_API =
        "/Other/inventory-library-api.php";


    const ICONS =
        {"inventory":"/Other/assets/icons/sentinel/inventory.png","folder":"/Other/assets/icons/sentinel/files.png","details":"/Other/assets/icons/sentinel/help.png","texture":"/Other/assets/icons/sentinel/image.png","sound":"/Other/assets/icons/sentinel/inventory.png","callingcard":"/Other/assets/icons/sentinel/users.png","landmark":"/Other/assets/icons/sentinel/map.png","clothing":"/Other/assets/icons/sentinel/inventory.png","object":"/Other/assets/icons/sentinel/inventory.png","notecard":"/Other/assets/icons/sentinel/inventory.png","script":"/Other/assets/icons/sentinel/inventory.png","bodypart":"/Other/assets/icons/sentinel/avatar.png","animation":"/Other/assets/icons/sentinel/inventory.png","gesture":"/Other/assets/icons/sentinel/inventory.png","link":"/Other/assets/icons/sentinel/link.png","mesh":"/Other/assets/icons/sentinel/inventory.png","settings":"/Other/assets/icons/sentinel/settings.png","material":"/Other/assets/icons/sentinel/inventory.png"};


    const SYSTEM_ORDER = [

        "animations",

        "body parts",

        "calling cards",

        "clothing",

        "current outfit",

        "favorites",

        "gestures",

        "landmarks",

        "lost and found",

        "materials",

        "my suitcase",

        "notecards",

        "objects",

        "outfits",

        "photo album",

        "scripts",

        "settings",

        "sounds",

        "textures",

        "trash"

    ];


    let summary =
        null;


    let folders =
        [];


    let roots =
        [];


    let folderMap =
        new Map();


    const itemCache =
        new Map();


    const libraryItemCache =
        new Map();


    let librarySummary =
        null;


    let libraryFolders =
        [];


    let libraryRoots =
        [];


    let libraryFolderMap =
        new Map();


    let libraryName =
        "OpenSim Library";


    let libraryError =
        null;


    const expanded =
        new Set();


    let selectedItemRow =
        null;


    let host =
        null;


    let tree =
        null;


    let detailBody =
        null;


    let statusLeft =
        null;


    let statusRight =
        null;


    let search =
        null;



    /* ======================================================
       API
       ====================================================== */

    async function api(
        action,
        parameters
    ){

        const url =
            new URL(
                API,
                window.location.origin
            );


        url.searchParams.set(
            "api",
            action
        );


        if(
            parameters
        ){

            Object.keys(
                parameters
            ).forEach(
                key => {

                    url.searchParams.set(
                        key,
                        parameters[key]
                    );
                }
            );
        }


        const response =
            await fetch(
                url.toString(),
                {
                    cache:
                        "no-store",

                    credentials:
                        "same-origin"
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
                "Inventory API returned invalid JSON."
            );
        }


        if(
            !response.ok ||
            !data ||
            data.ok !== true
        ){

            throw new Error(
                data &&
                data.error
                    ? data.error
                    : "Inventory request failed."
            );
        }


        return data;
    }




    /* ======================================================
       REAL OPENSIM LIBRARY API
       ====================================================== */

    async function libraryApi(
        action,
        parameters
    ){

        const url =
            new URL(
                LIBRARY_API,
                window.location.origin
            );


        url.searchParams.set(
            "api",
            action
        );


        if(
            parameters
        ){

            Object.keys(
                parameters
            ).forEach(
                key => {

                    url.searchParams.set(
                        key,
                        parameters[key]
                    );
                }
            );
        }


        const response =
            await fetch(
                url.toString(),
                {
                    cache:
                        "no-store",

                    credentials:
                        "same-origin"
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
                "OpenSim Library API returned invalid JSON."
            );
        }


        if(
            !response.ok ||
            !data ||
            data.ok !== true
        ){

            throw new Error(
                data &&
                data.error
                    ? data.error
                    : "OpenSim Library request failed."
            );
        }


        return data;
    }


    /* ======================================================
       TYPE / ICON
       ====================================================== */

    function typeInfo(
        item
    ){

        const asset =
            Number(
                item &&
                item.assetType
            );


        const inventory =
            Number(
                item &&
                item.inventoryType
            );


        const assetTypes = {

            0:["texture","TEXTURE"],

            1:["sound","SOUND"],

            2:["callingcard","CALLING CARD"],

            3:["landmark","LANDMARK"],

            5:["clothing","CLOTHING"],

            6:["object","OBJECT"],

            7:["notecard","NOTECARD"],

            10:["script","SCRIPT"],

            11:["script","SCRIPT"],

            13:["bodypart","BODY PART"],

            20:["animation","ANIMATION"],

            21:["gesture","GESTURE"],

            24:["link","INVENTORY LINK"],

            49:["mesh","MESH"],

            56:["settings","SETTINGS"],

            57:["material","MATERIAL"]

        };


        const inventoryTypes = {

            0:["texture","TEXTURE"],

            1:["sound","SOUND"],

            2:["callingcard","CALLING CARD"],

            3:["landmark","LANDMARK"],

            5:["object","OBJECT"],

            6:["notecard","NOTECARD"],

            9:["script","SCRIPT"],

            10:["texture","SNAPSHOT"],

            11:["object","ATTACHMENT"],

            12:["clothing","CLOTHING / WEARABLE"],

            13:["animation","ANIMATION"],

            14:["gesture","GESTURE"],

            15:["mesh","MESH"]

        };


        let value =
            assetTypes[
                asset
            ];


        if(
            !value
        ){

            value =
                inventoryTypes[
                    inventory
                ];
        }


        if(
            !value
        ){

            value =
                [
                    "inventory",
                    "ITEM"
                ];
        }


        return {

            key:
                value[0],

            label:
                value[1],

            icon:
                ICONS[
                    value[0]
                ] ||
                ICONS.inventory

        };
    }



    /* ======================================================
       SORT LIKE FIRESTORM
       SYSTEM FOLDERS
       ! FOLDERS
       OTHER USER FOLDERS
       ====================================================== */

    function folderRank(
        folder
    ){

        const name =
            String(
                folder.name ||
                ""
            )
            .trim();


        const lower =
            name.toLowerCase();


        /*
         * FIRESTORM'S OWN ROOT FOLDER
         */

        if(
            lower ===
            "#firestorm"
        ){

            return {
                group:0,
                rank:0,
                name:lower
            };
        }


        /*
         * OTHER REAL VIEWER-PROTECTED # FOLDERS
         *
         * If these are children of #Firestorm in the
         * real inventory, they stay children.
         *
         * This only affects ordering among siblings.
         */

        if(
            name.startsWith(
                "#"
            )
        ){

            return {
                group:1,
                rank:0,
                name:lower
            };
        }


        /*
         * STANDARD SYSTEM / VIEWER FOLDERS
         */

        const systemIndex =
            SYSTEM_ORDER.indexOf(
                lower
            );


        if(
            systemIndex >=
            0
        ){

            return {
                group:2,
                rank:systemIndex,
                name:lower
            };
        }


        /*
         * AUSTRALIA CUSTOM ! FOLDERS
         */

        if(
            name.startsWith(
                "!"
            )
        ){

            return {
                group:3,
                rank:0,
                name:lower
            };
        }


        /*
         * NORMAL PERSONAL FOLDERS
         */

        return {
            group:4,
            rank:0,
            name:lower
        };
    }


    function folderSorter(
        a,
        b
    ){

        const aa =
            folderRank(
                a
            );


        const bb =
            folderRank(
                b
            );


        if(
            aa.group !==
            bb.group
        ){

            return (
                aa.group -
                bb.group
            );
        }


        if(
            aa.rank !==
            bb.rank
        ){

            return (
                aa.rank -
                bb.rank
            );
        }


        return a.name.localeCompare(
            b.name,
            undefined,
            {
                sensitivity:"base"
            }
        );
    }



    /* ======================================================
       FOLDER MODEL
       ====================================================== */

    function buildModel(){

        folderMap =
            new Map();


        roots =
            [];


        folders.forEach(
            raw => {

                const folder = {

                    id:
                        String(
                            raw.id ||
                            ""
                        ),

                    parent:
                        String(
                            raw.parent ||
                            ""
                        ),

                    name:
                        String(
                            raw.name ||
                            "Unnamed Folder"
                        ),

                    type:
                        raw.type,

                    itemCount:
                        Number(
                            raw.itemCount ||
                            0
                        ),

                    children:
                        []

                };


                folderMap.set(
                    folder.id.toLowerCase(),
                    folder
                );
            }
        );


        folderMap.forEach(
            folder => {

                const parent =
                    folderMap.get(
                        folder.parent.toLowerCase()
                    );


                if(
                    parent &&
                    parent.id !==
                    folder.id
                ){

                    parent.children.push(
                        folder
                    );
                }
                else{

                    roots.push(
                        folder
                    );
                }
            }
        );


        function sortRecursive(
            list
        ){

            list.sort(
                folderSorter
            );


            list.forEach(
                folder =>
                    sortRecursive(
                        folder.children
                    )
            );
        }


        sortRecursive(
            roots
        );
    }



    /* ======================================================
       PATH
       ====================================================== */

    function folderPath(
        folderId
    ){

        let current =
            folderMap.get(
                String(
                    folderId ||
                    ""
                ).toLowerCase()
            );


        const parts =
            [];


        const seen =
            new Set();


        while(
            current &&
            !seen.has(
                current.id
            )
        ){

            seen.add(
                current.id
            );


            parts.unshift(
                current.name
            );


            current =
                folderMap.get(
                    String(
                        current.parent ||
                        ""
                    ).toLowerCase()
                ) ||
                null;
        }


        if(
            parts.length ===
            0
        ){

            return "Inventory";
        }


        if(
            !/^(my\s+)?inventory$/i.test(
                parts[0]
            )
        ){

            parts.unshift(
                "Inventory"
            );
        }


        return parts.join(
            " / "
        );
    }



    /* ======================================================
       DATE
       ====================================================== */

    function formatDate(
        raw
    ){

        if(
            raw ===
            null ||
            raw ===
            undefined ||
            raw ===
            ""
        ){

            return "—";
        }


        const numeric =
            Number(
                raw
            );


        let date;


        if(
            Number.isFinite(
                numeric
            ) &&
            numeric > 0
        ){

            date =
                new Date(
                    numeric *
                    1000
                );
        }
        else{

            date =
                new Date(
                    raw
                );
        }


        if(
            Number.isNaN(
                date.getTime()
            )
        ){

            return String(
                raw
            );
        }


        return date.toLocaleString();
    }



    /* ======================================================
       INSTALL SPLIT SCREEN
       ====================================================== */

    function installLayout(){
        host = document.querySelector('.ib-workspace');
        if(!host) throw new Error('Inventory workspace was not found.');

        const shell =
            document.createElement(
                "div"
            );


        shell.className =
            "fs2-shell";


        shell.innerHTML =
            `
            <article
                class="rp-region ib-card fs2-card fs2-inventory-card">

                <header class="rp-region-header">

                    <div class="fs2-header">

                        <img
                            class="fs2-header-icon"
                            src="${ICONS.inventory}"
                            alt=""
                            aria-hidden="true"
                            draggable="false">

                        <div class="fs2-header-text">

                            <strong>
                                INVENTORY
                            </strong>

                            <small>
                                INVENTORY TREE
                            </small>

                        </div>

                    </div>

                </header>


                <div class="fs2-search-bar">

                    <input
                        id="fs2-search"
                        class="fs2-search"
                        type="search"
                        maxlength="100"
                        autocomplete="off"
                        placeholder="Filter Inventory">

                </div>


                <div class="fs2-tools">

                    <button
                        id="fs2-collapse"
                        type="button"
                        class="cp-button fs2-tool">
                        COLLAPSE ALL
                    </button>

                    <button
                        id="fs2-refresh"
                        type="button"
                        class="cp-button fs2-tool">
                        REFRESH
                    </button>

                    <select
                        id="fs8-type-filter"
                        class="cp-button fs2-tool fs8-type-filter"
                        aria-label="Inventory type filter">
                        <option value="all">ALL TYPES</option>
                        <option value="texture">TEXTURES</option>
                        <option value="sound">SOUNDS</option>
                        <option value="callingcard">CALLING CARDS</option>
                        <option value="landmark">LANDMARKS</option>
                        <option value="clothing">CLOTHING</option>
                        <option value="object">OBJECTS</option>
                        <option value="notecard">NOTECARDS</option>
                        <option value="script">SCRIPTS</option>
                        <option value="bodypart">BODY PARTS</option>
                        <option value="animation">ANIMATIONS</option>
                        <option value="gesture">GESTURES</option>
                        <option value="link">INVENTORY LINKS</option>
                        <option value="mesh">MESH</option>
                        <option value="settings">SETTINGS</option>
                        <option value="material">MATERIALS</option>
                        <option value="inventory">OTHER ITEMS</option>
                    </select>

                    <span class="fs2-readonly">
                        READ ONLY
                    </span>

                </div>


                <div class="fs2-tabs">

                    <button
                        type="button"
                        class="fs2-tab fs2-tab-active">
                        Inventory
                    </button>

                </div>


                <div class="fs2-tree-scroll">

                    <div
                        id="fs2-tree"
                        class="fs2-tree">

                        <div class="fs2-message">
                            Loading inventory...
                        </div>

                    </div>

                </div>


                <div class="fs2-status">

                    <span id="fs2-status-left">
                        Loading...
                    </span>

                    <span id="fs2-status-right">
                        READ ONLY
                    </span>

                </div>

            </article>


            <article
                class="rp-region ib-card fs2-card fs2-details-card">

                <header class="rp-region-header">

                    <div class="fs2-header">

                        <img
                            class="fs2-header-icon"
                            src="${ICONS.details}"
                            alt=""
                            aria-hidden="true"
                            draggable="false">

                        <div class="fs2-header-text">

                            <strong>
                                ITEM DETAILS
                            </strong>

                            <small id="fs2-detail-heading">
                                SELECT AN INVENTORY ITEM
                            </small>

                        </div>

                    </div>

                </header>


                <div
                    id="fs2-details-body"
                    class="fs2-details-body">
                </div>

            </article>
            `;


        host.appendChild(shell);


        tree =
            document.getElementById(
                "fs2-tree"
            );


        detailBody =
            document.getElementById(
                "fs2-details-body"
            );


        statusLeft =
            document.getElementById(
                "fs2-status-left"
            );


        statusRight =
            document.getElementById(
                "fs2-status-right"
            );


        search =
            document.getElementById(
                "fs2-search"
            );


        clearDetails();
    }



    /* ======================================================
       DETAILS EMPTY
       ====================================================== */

    function clearDetails(){

        detailBody.innerHTML =
            `
            <div class="fs2-details-empty">

                <img
                    src="${ICONS.details}"
                    alt=""
                    aria-hidden="true"
                    draggable="false">

                <strong>
                    SELECT AN INVENTORY ITEM
                </strong>

                <span>
                    Open a folder on the left, then select the
                    Object, Script, Texture, Notecard, Clothing,
                    Body Part or other item you want to inspect.
                </span>

            </div>
            `;
    }

    /* ======================================================
       RIGHT DETAILS PANE
       AUSTRALIA REAL PERMISSIONS V4
       ====================================================== */


    function fs4IsLibraryItem(
        item
    ){

        if(
            item &&
            item._library ===
            true
        ){

            return true;
        }


        if(
            String(
                item &&
                item.source ||
                ""
            ).toLowerCase() ===
            "library"
        ){

            return true;
        }


        try{

            if(
                typeof libraryFolderMap !==
                "undefined"
                &&
                libraryFolderMap
                &&
                typeof libraryFolderMap.has ===
                "function"
            ){

                return libraryFolderMap.has(
                    String(
                        item &&
                        item.folderId ||
                        ""
                    ).toLowerCase()
                );
            }
        }
        catch(error){

        }


        return false;
    }



    function fs4PersonalFolderPath(
        folderId
    ){

        const wanted =
            String(
                folderId ||
                ""
            ).toLowerCase();


        if(!wanted){

            return "Inventory";
        }


        let targetRow =
            null;


        document
        .querySelectorAll(
            ".fs2-folder-row[data-folder-id]"
        )
        .forEach(
            row => {

                if(
                    targetRow
                ){

                    return;
                }


                if(
                    String(
                        row.dataset.folderId ||
                        ""
                    ).toLowerCase() ===
                    wanted
                ){

                    targetRow =
                        row;
                }
            }
        );


        if(
            !targetRow
        ){

            return "Inventory";
        }


        const parts =
            [];


        let currentNode =
            targetRow.closest(
                ".fs2-node"
            );


        const seen =
            new Set();


        while(
            currentNode &&
            !seen.has(
                currentNode
            )
        ){

            seen.add(
                currentNode
            );


            const directRow =
                currentNode.querySelector(
                    ":scope > .fs2-row"
                );


            if(
                directRow
            ){

                const name =
                    directRow.querySelector(
                        ":scope > .fs2-name"
                    );


                if(
                    name &&
                    name.textContent.trim() !==
                    ""
                ){

                    parts.unshift(
                        name.textContent.trim()
                    );
                }
            }


            const parentChildren =
                currentNode.parentElement;


            if(
                !parentChildren
                ||
                !parentChildren.classList.contains(
                    "fs2-children"
                )
            ){

                break;
            }


            currentNode =
                parentChildren.closest(
                    ".fs2-node"
                );
        }


        if(
            parts.length ===
            0
        ){

            return "Inventory";
        }


        if(
            parts[0].toLowerCase() !==
            "inventory"
        ){

            parts.unshift(
                "Inventory"
            );
        }


        return parts.join(
            " / "
        );
    }



    function fs4FolderPath(
        item,
        isLibrary
    ){

        if(
            isLibrary
        ){

            try{

                if(
                    typeof libraryFolderPath ===
                    "function"
                ){

                    return libraryFolderPath(
                        item.folderId
                    );
                }
            }
            catch(error){

            }


            return "OpenSim Library";
        }


        return fs4PersonalFolderPath(
            item.folderId
        );
    }



    function fs4FormatDate(
        value
    ){

        if(
            value ===
            null
            ||
            value ===
            undefined
            ||
            value ===
            ""
        ){

            return "—";
        }


        const numeric =
            Number(
                value
            );


        let date;


        if(
            Number.isFinite(
                numeric
            )
            &&
            numeric >
            0
        ){

            date =
                new Date(
                    numeric <
                    100000000000
                        ? numeric * 1000
                        : numeric
                );
        }
        else{

            date =
                new Date(
                    String(
                        value
                    )
                );
        }


        if(
            Number.isNaN(
                date.getTime()
            )
        ){

            return String(
                value
            );
        }


        return date.toLocaleString();
    }



    function fs4PermissionState(
        mask,
        bit
    ){

        if(
            mask ===
            null
            ||
            mask ===
            undefined
            ||
            mask ===
            ""
        ){

            return null;
        }


        const value =
            Number(
                mask
            );


        if(
            !Number.isFinite(
                value
            )
        ){

            return null;
        }


        return (
            (
                value &
                bit
            ) ===
            bit
        );
    }



    function fs4PermissionCard(
        title,
        mask
    ){

        const card =
            document.createElement(
                "div"
            );


        card.className =
            "fs4-permission-card";


        const heading =
            document.createElement(
                "div"
            );


        heading.className =
            "fs4-permission-name";


        heading.textContent =
            title;


        const chips =
            document.createElement(
                "div"
            );


        chips.className =
            "fs4-chips";


        const definitions = [

            [
                "COPY",
                32768
            ],

            [
                "MODIFY",
                16384
            ],

            [
                "TRANSFER",
                8192
            ]

        ];


        definitions.forEach(
            definition => {

                const state =
                    fs4PermissionState(
                        mask,
                        definition[1]
                    );


                const chip =
                    document.createElement(
                        "span"
                    );


                chip.className =
                    "fs4-chip " +
                    (
                        state ===
                        null
                            ? "fs4-na"
                            : (
                                state
                                    ? "fs4-yes"
                                    : "fs4-no"
                            )
                    );


                chip.textContent =
                    definition[0] +
                    (
                        state ===
                        null
                            ? " —"
                            : (
                                state
                                    ? " YES"
                                    : " NO"
                            )
                    );


                chips.appendChild(
                    chip
                );
            }
        );


        const raw =
            document.createElement(
                "div"
            );


        raw.className =
            "fs4-mask";


        raw.textContent =
            (
                mask ===
                null
                ||
                mask ===
                undefined
                ||
                mask ===
                ""
            )
                ? "MASK NOT AVAILABLE"
                : (
                    "MASK " +
                    String(
                        mask
                    )
                );


        card.appendChild(
            heading
        );


        card.appendChild(
            chips
        );


        card.appendChild(
            raw
        );


        return card;
    }



    function fs4InfoRow(
        container,
        label,
        value
    ){

        const row =
            document.createElement(
                "div"
            );


        row.className =
            "fs4-info-row";


        const left =
            document.createElement(
                "div"
            );


        left.className =
            "fs4-info-label";


        left.textContent =
            label;


        const right =
            document.createElement(
                "div"
            );


        right.className =
            "fs4-info-value";


        right.textContent =
            (
                value ===
                null
                ||
                value ===
                undefined
                ||
                value ===
                ""
            )
                ? "—"
                : String(
                    value
                );


        row.appendChild(
            left
        );


        row.appendChild(
            right
        );


        container.appendChild(
            row
        );
    }



    function fs4SaleText(
        type,
        price
    ){

        if(
            type ===
            null
            ||
            type ===
            undefined
            ||
            type ===
            ""
        ){

            return "—";
        }


        const saleTypes = {

            0:
                "NOT FOR SALE",

            1:
                "ORIGINAL",

            2:
                "COPY",

            3:
                "CONTENTS"

        };


        const numeric =
            Number(
                type
            );


        let text =
            saleTypes[numeric]
            ||
            (
                "TYPE " +
                String(
                    type
                )
            );


        if(
            numeric !==
            0
            &&
            price !==
            null
            &&
            price !==
            undefined
            &&
            price !==
            ""
        ){

            text +=
                " — L$" +
                String(
                    price
                );
        }


        return text;
    }



    /* AUSTRALIA FIRESTORM AVATAR INFORMATION V9 START */

    function fs9AvatarContext(){

        const element =
            document.getElementById(
                "fs9-avatar-context"
            );


        return {

            name:
                String(
                    element &&
                    element.dataset.avatar ||
                    ""
                ).trim(),

            level:
                String(
                    element &&
                    element.dataset.level ||
                    ""
                ).trim()
        };
    }

    /* AUSTRALIA FIRESTORM AVATAR INFORMATION V9 END */

    async function showDetails(
        item
    ){

        const meta =
            typeInfo(
                item
            );


        const avatarItem =
            (
                meta.key ===
                "clothing"
                ||
                meta.key ===
                "bodypart"
            );


        const isLibrary =
            fs4IsLibraryItem(
                item
            );


        const token =
            String(
                item.id ||
                ""
            )
            +
            "-"
            +
            String(
                Date.now()
            )
            +
            "-"
            +
            String(
                Math.random()
            );


        detailBody.dataset.fs4Token =
            token;


        detailBody.innerHTML =
            `
            <div class="fs4-owner-card">

                <img
                    class="fs4-owner-icon"
                    src="/Other/assets/icons/sentinel/avatar.png"
                    alt=""
                    aria-hidden="true"
                    draggable="false">

                <div>

                    <div class="fs4-owner-label">
                        INVENTORY OWNER
                    </div>

                    <div
                        id="fs4-owner-title"
                        class="fs4-owner-title">
                    </div>

                    <div
                        id="fs4-owner-id"
                        class="fs4-owner-id">
                    </div>

                    <div
                        id="fs4-owner-totals"
                        class="fs4-owner-totals">
                    </div>

                </div>

            </div>


            <div class="fs2-detail-hero">

                <img
                    id="fs2-detail-icon"
                    class="fs2-detail-hero-icon"
                    alt=""
                    aria-hidden="true"
                    draggable="false">

                <div>

                    <h3
                        id="fs2-detail-name"
                        class="fs2-detail-name">
                    </h3>

                    <div
                        id="fs2-detail-type"
                        class="fs2-detail-type">
                    </div>

                    <div
                        id="fs2-avatar-badge"
                        class="fs2-avatar-badge">
                        AVATAR / WEARABLE INVENTORY
                    </div>

                    <div
                        id="fs4-source"
                        class="fs4-source">
                    </div>

                </div>

            </div>


            <div class="fs2-description">

                <div class="fs2-section-title">
                    DESCRIPTION
                </div>

                <div
                    id="fs2-description"
                    class="fs2-description-text">
                </div>

            </div>


            <div class="fs4-section">

                <div class="fs4-title">
                    PERMISSIONS
                </div>

                <div
                    id="fs4-permissions"
                    class="fs4-permission-grid">
                </div>

                <div
                    id="fs4-permission-note"
                    class="fs4-note">
                    Loading permission information...
                </div>

            </div>


            <div class="fs4-section">

                <div class="fs4-title">
                    ITEM INFORMATION
                </div>

                <div
                    id="fs4-info"
                    class="fs4-info">
                </div>

            </div>
            `;


        const heading =
            document.getElementById(
                "fs2-detail-heading"
            );


        if(
            heading
        ){

            heading.textContent =
                "ITEM DETAILS";
        }


        document
        .getElementById(
            "fs2-detail-icon"
        ).src =
            meta.icon;


        document
        .getElementById(
            "fs2-detail-name"
        ).textContent =
            String(
                item.name ||
                "Unnamed Item"
            );


        document
        .getElementById(
            "fs2-detail-type"
        ).textContent =
            (
                meta.label +
                (
                    item.assetType ===
                    null
                    ||
                    item.assetType ===
                    undefined
                    ||
                    item.assetType ===
                    ""
                        ? ""
                        : (
                            " — ASSET TYPE " +
                            String(
                                item.assetType
                            )
                        )
                )
            );


        if(
            avatarItem
        ){

            document
            .getElementById(
                "fs2-avatar-badge"
            )
            .classList.add(
                "fs2-show"
            );
        }


        document
        .getElementById(
            "fs2-description"
        ).textContent =
            String(
                item.description ||
                "No description."
            );


        document
        .getElementById(
            "fs4-source"
        ).textContent =
            isLibrary
                ? "OPENSIM LIBRARY"
                : "AVATAR INVENTORY";


        const ownerTitle =
            document.getElementById(
                "fs4-owner-title"
            );


        const ownerId =
            document.getElementById(
                "fs4-owner-id"
            );


        const ownerTotals =
            document.getElementById(
                "fs4-owner-totals"
            );


        const fs9Avatar =
            fs9AvatarContext();


        ownerTitle.textContent =
            isLibrary
                ? "OpenSim Library"
                : (
                    fs9Avatar.name ||
                    "Current Admin Inventory"
                );


        ownerId.textContent =
            isLibrary
                ? "READ ONLY LIBRARY"
                : (
                    fs9Avatar.level
                        ? (
                            "ADMIN LEVEL • " +
                            fs9Avatar.level +
                            " • Loading avatar UUID..."
                        )
                        : "Loading avatar UUID..."
                );


        const personalFolders =
            Number(
                summary &&
                summary.folders ||
                0
            );


        const personalItems =
            Number(
                summary &&
                summary.items ||
                0
            );


        let totalsText =
            "PERSONAL: "
            +
            personalFolders.toLocaleString()
            +
            " FOLDERS • "
            +
            personalItems.toLocaleString()
            +
            " ITEMS";


        if(
            typeof librarySummary !==
            "undefined"
            &&
            librarySummary
        ){

            totalsText +=
                " | LIBRARY: "
                +
                Number(
                    librarySummary.folders ||
                    0
                ).toLocaleString()
                +
                " FOLDERS • "
                +
                Number(
                    librarySummary.items ||
                    0
                ).toLocaleString()
                +
                " ITEMS";
        }


        ownerTotals.textContent =
            totalsText;


        const permissionGrid =
            document.getElementById(
                "fs4-permissions"
            );


        const permissionNote =
            document.getElementById(
                "fs4-permission-note"
            );


        const info =
            document.getElementById(
                "fs4-info"
            );


        /*
         * INFORMATION AVAILABLE IMMEDIATELY
         */

        if(!isLibrary){

            fs4InfoRow(
                info,
                "AVATAR NAME",
                fs9Avatar.name
            );


            fs4InfoRow(
                info,
                "ADMIN LEVEL",
                fs9Avatar.level
            );
        }


        fs4InfoRow(
            info,
            "ITEM CLASS",
            avatarItem
                ? (
                    meta.key ===
                    "clothing"
                        ? "CLOTHING / AVATAR WEARABLE"
                        : "BODY PART / AVATAR"
                )
                : meta.label
        );


        fs4InfoRow(
            info,
            "FOLDER",
            fs4FolderPath(
                item,
                isLibrary
            )
        );


        fs4InfoRow(
            info,
            "ITEM UUID",
            item.id
        );


        fs4InfoRow(
            info,
            "ASSET UUID",
            item.assetId
        );


        fs4InfoRow(
            info,
            "CREATOR UUID",
            item.creatorId
        );


        fs4InfoRow(
            info,
            "ASSET TYPE",
            (
                item.assetType ===
                null
                ||
                item.assetType ===
                undefined
                    ? "—"
                    : (
                        meta.label +
                        " — " +
                        String(
                            item.assetType
                        )
                    )
            )
        );


        fs4InfoRow(
            info,
            "INVENTORY TYPE",
            item.inventoryType
        );


        fs4InfoRow(
            info,
            "CREATED",
            fs4FormatDate(
                item.creationDate
            )
        );


        fs4InfoRow(
            info,
            "SOURCE",
            isLibrary
                ? "OpenSim Library"
                : "OpenSim inventoryitems database"
        );


        /*
         * OPENSIM LIBRARY:
         * do not query avatar inventory metadata.
         */

        if(
            isLibrary
        ){

            permissionGrid.appendChild(
                fs4PermissionCard(
                    "BASE / OWNER",
                    item.basePermissions
                )
            );


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "CURRENT",
                    item.currentPermissions
                )
            );


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "NEXT OWNER",
                    item.nextPermissions
                )
            );


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "EVERYONE",
                    item.everyonePermissions
                )
            );


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "GROUP",
                    item.groupPermissions
                )
            );


            permissionNote.textContent =
                "Library permission masks are displayed only when the Library XML provides them.";


            return;
        }


        /*
         * PERSONAL INVENTORY:
         * retrieve real permission masks from inventoryitems.
         */

        try{

            const response =
                await api(
                    "itemmeta",
                    {
                        id:
                            String(
                                item.id ||
                                ""
                            )
                    }
                );


            if(
                detailBody.dataset.fs4Token !==
                token
            ){

                return;
            }


            const full =
                response &&
                response.item
                    ? response.item
                    : {};


            ownerId.textContent =
                full.ownerId
                    ? (
                        "AVATAR UUID • " +
                        String(
                            full.ownerId
                        )
                        +
                        (
                            fs9Avatar.level
                                ? (
                                    " • LEVEL " +
                                    fs9Avatar.level
                                )
                                : ""
                        )
                    )
                    : (
                        fs9Avatar.level
                            ? (
                                "AVATAR UUID NOT AVAILABLE • LEVEL " +
                                fs9Avatar.level
                            )
                            : "AVATAR UUID NOT AVAILABLE"
                    );


            if(
                full.description
            ){

                document
                .getElementById(
                    "fs2-description"
                ).textContent =
                    String(
                        full.description
                    );
            }


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "BASE / OWNER",
                    full.basePermissions
                )
            );


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "CURRENT",
                    full.currentPermissions
                )
            );


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "NEXT OWNER",
                    full.nextPermissions
                )
            );


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "EVERYONE",
                    full.everyonePermissions
                )
            );


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "GROUP",
                    full.groupPermissions
                )
            );


            permissionNote.textContent =
                "Green = allowed. Red = not allowed. NEXT OWNER is what the recipient receives.";


            fs4InfoRow(
                info,
                "OWNER UUID",
                full.ownerId
            );


            fs4InfoRow(
                info,
                "GROUP UUID",
                full.groupId
            );


            fs4InfoRow(
                info,
                "GROUP OWNED",
                full.groupOwned ===
                null
                ||
                full.groupOwned ===
                undefined
                    ? "—"
                    : (
                        full.groupOwned
                            ? "YES"
                            : "NO"
                    )
            );


            fs4InfoRow(
                info,
                "SALE",
                fs4SaleText(
                    full.saleType,
                    full.salePrice
                )
            );


            fs4InfoRow(
                info,
                "FLAGS",
                full.flags
            );
        }
        catch(error){

            if(
                detailBody.dataset.fs4Token !==
                token
            ){

                return;
            }


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "BASE / OWNER",
                    null
                )
            );


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "CURRENT",
                    null
                )
            );


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "NEXT OWNER",
                    null
                )
            );


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "EVERYONE",
                    null
                )
            );


            permissionGrid.appendChild(
                fs4PermissionCard(
                    "GROUP",
                    null
                )
            );


            permissionNote.textContent =
                "Permission metadata could not be loaded.";


            ownerId.textContent =
                "OWNER UUID UNAVAILABLE";


            const errorBox =
                document.createElement(
                    "div"
                );


            errorBox.className =
                "fs4-error";


            errorBox.textContent =
                String(
                    error &&
                    error.message
                    ||
                    "Unknown metadata error."
                );


            detailBody.appendChild(
                errorBox
            );
        }
    }





    /* ======================================================
       REAL ITEMS FROM FOLDER
       ====================================================== */

    async function loadFolderItems(
        folder
    ){

        const key =
            folder.id.toLowerCase();


        if(
            itemCache.has(
                key
            )
        ){

            return itemCache.get(
                key
            );
        }


        const response =
            await api(
                "items",
                {
                    folder:
                        folder.id
                }
            );


        const items =
            Array.isArray(
                response.items
            )
                ? response.items
                : [];


        items.sort(
            (
                a,
                b
            ) =>
                String(
                    a.name ||
                    ""
                ).localeCompare(
                    String(
                        b.name ||
                        ""
                    ),
                    undefined,
                    {
                        sensitivity:"base"
                    }
                )
        );


        itemCache.set(
            key,
            items
        );


        return items;
    }

    /* AUSTRALIA FIRESTORM CONTEXT MENU V5 START */


    let fs5ContextMenu =
        null;



    function fs5CloseContextMenu(){

        if(
            fs5ContextMenu
            &&
            fs5ContextMenu.parentNode
        ){

            fs5ContextMenu.remove();
        }


        fs5ContextMenu =
            null;
    }



    async function fs5CopyText(
        value,
        label
    ){

        const text =
            String(
                value ||
                ""
            );


        if(!text){

            statusLeft.textContent =
                "NOTHING TO COPY";


            statusRight.textContent =
                "CLIPBOARD";


            return;
        }


        let copied =
            false;


        try{

            if(
                navigator.clipboard
                &&
                typeof navigator.clipboard.writeText ===
                "function"
            ){

                await navigator.clipboard.writeText(
                    text
                );


                copied =
                    true;
            }
        }
        catch(error){

        }


        if(!copied){

            const textarea =
                document.createElement(
                    "textarea"
                );


            textarea.value =
                text;


            textarea.setAttribute(
                "readonly",
                ""
            );


            textarea.style.position =
                "fixed";


            textarea.style.left =
                "-9999px";


            textarea.style.top =
                "-9999px";


            document.body.appendChild(
                textarea
            );


            textarea.select();


            try{

                copied =
                    document.execCommand(
                        "copy"
                    );
            }
            catch(error){

                copied =
                    false;
            }


            textarea.remove();
        }


        if(copied){

            statusLeft.textContent =
                "COPIED • " +
                String(
                    label ||
                    "VALUE"
                );


            statusRight.textContent =
                "CLIPBOARD";
        }
        else{

            statusLeft.textContent =
                "COPY FAILED";


            statusRight.textContent =
                "CLIPBOARD";
        }
    }



    function fs5CreateMenuButton(
        label,
        icon,
        action,
        enabled
    ){

        const button =
            document.createElement(
                "button"
            );


        button.type =
            "button";


        button.className =
            "fs5-context-item";


        if(
            enabled ===
            false
        ){

            button.disabled =
                true;
        }


        if(icon){

            const image =
                document.createElement(
                    "img"
                );


            image.className =
                "fs5-context-icon";


            image.src =
                icon;


            image.alt =
                "";


            image.draggable =
                false;


            button.appendChild(
                image
            );
        }


        const text =
            document.createElement(
                "span"
            );


        text.textContent =
            label;


        button.appendChild(
            text
        );


        if(
            enabled !==
            false
        ){

            button.addEventListener(
                "click",
                async event => {

                    event.preventDefault();

                    event.stopPropagation();


                    fs5CloseContextMenu();


                    await action();
                }
            );
        }


        return button;
    }



    function fs5AddSeparator(
        menu
    ){

        const separator =
            document.createElement(
                "div"
            );


        separator.className =
            "fs5-context-separator";


        menu.appendChild(
            separator
        );
    }



    function fs5ItemFolderPath(
        item
    ){

        const isLibrary =
            fs4IsLibraryItem(
                item
            );


        return fs4FolderPath(
            item,
            isLibrary
        );
    }



    function fs5PositionMenu(
        menu,
        clientX,
        clientY
    ){

        const padding =
            8;


        let left =
            clientX;


        let top =
            clientY;


        menu.style.left =
            left +
            "px";


        menu.style.top =
            top +
            "px";


        const box =
            menu.getBoundingClientRect();


        if(
            box.right >
            window.innerWidth -
            padding
        ){

            left =
                Math.max(
                    padding,
                    window.innerWidth -
                    box.width -
                    padding
                );
        }


        if(
            box.bottom >
            window.innerHeight -
            padding
        ){

            top =
                Math.max(
                    padding,
                    window.innerHeight -
                    box.height -
                    padding
                );
        }


        menu.style.left =
            left +
            "px";


        menu.style.top =
            top +
            "px";
    }



    function fs5ShowItemMenu(
        event,
        item,
        selectItem
    ){

        fs5CloseContextMenu();


        /*
         * Make the right-clicked item the active item first.
         */

        selectItem();


        const meta =
            typeInfo(
                item
            );


        const menu =
            document.createElement(
                "div"
            );


        menu.className =
            "fs5-context-menu";


        menu.setAttribute(
            "role",
            "menu"
        );


        const title =
            document.createElement(
                "div"
            );


        title.className =
            "fs5-context-title";


        title.textContent =
            String(
                item.name ||
                "Unnamed Item"
            );


        menu.appendChild(
            title
        );


        /*
         * VIEW DETAILS
         */

        menu.appendChild(
            fs5CreateMenuButton(
                "View Details",
                meta.icon,
                async function(){

                    selectItem();
                },
                true
            )
        );


        fs5AddSeparator(
            menu
        );


        /*
         * COPY NAME
         */

        menu.appendChild(
            fs5CreateMenuButton(
                "Copy Name",
                ICONS.details,
                async function(){

                    await fs5CopyText(
                        item.name,
                        "NAME"
                    );
                },
                Boolean(
                    item.name
                )
            )
        );


        /*
         * COPY ITEM UUID
         */

        menu.appendChild(
            fs5CreateMenuButton(
                "Copy Item UUID",
                ICONS.inventory,
                async function(){

                    await fs5CopyText(
                        item.id,
                        "ITEM UUID"
                    );
                },
                Boolean(
                    item.id
                )
            )
        );


        /*
         * COPY ASSET UUID
         */

        menu.appendChild(
            fs5CreateMenuButton(
                "Copy Asset UUID",
                ICONS.object,
                async function(){

                    await fs5CopyText(
                        item.assetId,
                        "ASSET UUID"
                    );
                },
                Boolean(
                    item.assetId
                )
            )
        );


        /*
         * COPY CREATOR UUID
         */

        menu.appendChild(
            fs5CreateMenuButton(
                "Copy Creator UUID",
                ICONS.inventory,
                async function(){

                    await fs5CopyText(
                        item.creatorId,
                        "CREATOR UUID"
                    );
                },
                Boolean(
                    item.creatorId
                )
            )
        );


        /*
         * COPY FULL FOLDER PATH
         */

        menu.appendChild(
            fs5CreateMenuButton(
                "Copy Folder Path",
                ICONS.folder,
                async function(){

                    await fs5CopyText(
                        fs5ItemFolderPath(
                            item
                        ),
                        "FOLDER PATH"
                    );
                },
                true
            )
        );


        document.body.appendChild(
            menu
        );


        fs5ContextMenu =
            menu;


        fs5PositionMenu(
            menu,
            event.clientX,
            event.clientY
        );


        const firstButton =
            menu.querySelector(
                ".fs5-context-item:not([disabled])"
            );


        if(firstButton){

            firstButton.focus(
                {
                    preventScroll:
                        true
                }
            );
        }
    }



    /*
     * Close menu when user clicks somewhere else.
     */

    document.addEventListener(
        "pointerdown",
        event => {

            if(
                fs5ContextMenu
                &&
                !fs5ContextMenu.contains(
                    event.target
                )
            ){

                fs5CloseContextMenu();
            }
        }
    );



    /*
     * ESC closes menu.
     */

    document.addEventListener(
        "keydown",
        event => {

            if(
                event.key ===
                "Escape"
            ){

                fs5CloseContextMenu();
            }
        }
    );



    window.addEventListener(
        "blur",
        fs5CloseContextMenu
    );



    window.addEventListener(
        "resize",
        fs5CloseContextMenu
    );



    document.addEventListener(
        "scroll",
        fs5CloseContextMenu,
        true
    );


    /* AUSTRALIA FIRESTORM CONTEXT MENU V5 END */

    /* AUSTRALIA FIRESTORM REMEMBER STATE V6 START */


    const FS6_STATE_KEY =
        "AUSTRALIA_FIRESTORM_INVENTORY_STATE_V6";


    let fs6Restoring =
        false;


    let fs6SavedState =
        null;


    let fs6RestoreTimer =
        null;


    let fs6ScrollTimer =
        null;


    let fs6Observer =
        null;


    let fs6RestoreAttempt =
        0;



    function fs6ReadState(){

        try{

            const raw =
                localStorage.getItem(
                    FS6_STATE_KEY
                );


            if(!raw){
                return null;
            }


            const state =
                JSON.parse(
                    raw
                );


            if(
                !state
                ||
                typeof state !==
                "object"
            ){

                return null;
            }


            return state;
        }
        catch(error){

            return null;
        }
    }



    function fs6WriteState(
        state
    ){

        try{

            localStorage.setItem(
                FS6_STATE_KEY,
                JSON.stringify(
                    state
                )
            );


            return true;
        }
        catch(error){

            return false;
        }
    }



    function fs6DirectName(
        row
    ){

        if(!row){
            return "";
        }


        const direct =
            Array.from(
                row.children
            )
            .find(
                child =>
                    child.classList
                    &&
                    child.classList.contains(
                        "fs2-name"
                    )
            );


        if(direct){

            return String(
                direct.textContent ||
                ""
            ).trim();
        }


        const fallback =
            row.querySelector(
                ".fs2-name"
            );


        return fallback
            ? String(
                fallback.textContent ||
                ""
            ).trim()
            : "";
    }



    function fs6RowKey(
        row
    ){

        if(!row){
            return "";
        }


        if(
            row.dataset
            &&
            row.dataset.libraryFolderId
        ){

            return (
                "L:" +
                String(
                    row.dataset.libraryFolderId
                ).toLowerCase()
            );
        }


        if(
            row.dataset
            &&
            row.dataset.folderId
        ){

            return (
                "P:" +
                String(
                    row.dataset.folderId
                ).toLowerCase()
            );
        }


        if(
            row.classList.contains(
                "fs2-root-row"
            )
        ){

            const name =
                fs6DirectName(
                    row
                ).toLowerCase();


            if(
                name.includes(
                    "opensim library"
                )
            ){

                return "R:library";
            }


            if(
                name ===
                "inventory"
                ||
                name.includes(
                    "inventory"
                )
            ){

                return "R:inventory";
            }
        }


        return "";
    }



    function fs6FindRow(
        key
    ){

        if(
            !tree
            ||
            !key
        ){

            return null;
        }


        const rows =
            tree.querySelectorAll(
                ".fs2-folder-row, .fs2-root-row"
            );


        for(
            const row of rows
        ){

            if(
                fs6RowKey(
                    row
                ) ===
                key
            ){

                return row;
            }
        }


        return null;
    }



    function fs6Toggle(
        row
    ){

        if(!row){
            return null;
        }


        return row.querySelector(
            ".fs2-toggle"
        );
    }



    function fs6IsExpanded(
        row
    ){

        if(!row){
            return false;
        }


        const aria =
            row.getAttribute(
                "aria-expanded"
            );


        if(
            aria ===
            "true"
        ){

            return true;
        }


        if(
            aria ===
            "false"
        ){

            return false;
        }


        const toggle =
            fs6Toggle(
                row
            );


        if(toggle){

            const arrow =
                String(
                    toggle.textContent ||
                    ""
                ).trim();


            if(
                arrow ===
                ""
            ){

                return false;
            }


            const closed = [

                "›",
                ">",
                "▶",
                "▸",
                "►",
                "+"

            ];


            if(
                closed.includes(
                    arrow
                )
            ){

                return false;
            }


            const opened = [

                "⌄",
                "⌄",
                "▼",
                "▾",
                "∨",
                "v",
                "−",
                "-"

            ];


            if(
                opened.includes(
                    arrow
                )
            ){

                return true;
            }
        }


        /*
         * Fallback for a layout using classes instead of arrows.
         */

        const classText =
            String(
                row.className ||
                ""
            ).toLowerCase();


        if(
            classText.includes(
                "expanded"
            )
            ||
            classText.includes(
                " open"
            )
        ){

            return true;
        }


        return false;
    }



    function fs6ToggleRow(
        row
    ){

        if(!row){
            return false;
        }


        const toggle =
            fs6Toggle(
                row
            );


        if(
            toggle
            &&
            String(
                toggle.textContent ||
                ""
            ).trim() !==
            ""
        ){

            toggle.dispatchEvent(
                new MouseEvent(
                    "click",
                    {
                        bubbles:
                            true,

                        cancelable:
                            true,

                        view:
                            window
                    }
                )
            );


            return true;
        }


        row.dispatchEvent(
            new MouseEvent(
                "click",
                {
                    bubbles:
                        true,

                    cancelable:
                        true,

                    view:
                        window
                }
            )
        );


        return true;
    }



    function fs6SelectedState(){

        if(!tree){
            return null;
        }


        const row =
            tree.querySelector(
                ".fs2-item-row.fs2-selected[data-fs6-item-id]"
            );


        if(!row){
            return null;
        }


        return {

            id:
                String(
                    row.dataset.fs6ItemId ||
                    ""
                ).toLowerCase(),

            source:
                String(
                    row.dataset.fs6Source ||
                    "personal"
                )
        };
    }



    function fs6CaptureState(){

        if(
            fs6Restoring
            ||
            !tree
        ){

            return;
        }


        const expanded =
            [];


        const roots =
            {};


        tree
        .querySelectorAll(
            ".fs2-folder-row"
        )
        .forEach(
            row => {

                const key =
                    fs6RowKey(
                        row
                    );


                if(
                    key
                    &&
                    fs6IsExpanded(
                        row
                    )
                ){

                    expanded.push(
                        key
                    );
                }
            }
        );


        tree
        .querySelectorAll(
            ".fs2-root-row"
        )
        .forEach(
            row => {

                const key =
                    fs6RowKey(
                        row
                    );


                if(key){

                    roots[key] =
                        fs6IsExpanded(
                            row
                        );
                }
            }
        );


        fs6WriteState({

            version:
                6,

            expanded:
                Array.from(
                    new Set(
                        expanded
                    )
                ),

            roots:
                roots,

            selected:
                fs6SelectedState(),

            scrollTop:
                Number(
                    tree.scrollTop ||
                    0
                ),

            savedAt:
                Date.now()
        });
    }



    function fs6FindSelectedItem(
        selected
    ){

        if(
            !tree
            ||
            !selected
            ||
            !selected.id
        ){

            return null;
        }


        const rows =
            tree.querySelectorAll(
                ".fs2-item-row[data-fs6-item-id]"
            );


        for(
            const row of rows
        ){

            const itemId =
                String(
                    row.dataset.fs6ItemId ||
                    ""
                ).toLowerCase();


            const source =
                String(
                    row.dataset.fs6Source ||
                    "personal"
                );


            if(
                itemId ===
                String(
                    selected.id
                ).toLowerCase()
                &&
                source ===
                String(
                    selected.source ||
                    "personal"
                )
            ){

                return row;
            }
        }


        return null;
    }



    function fs6RestoreRoots(){

        if(
            !fs6SavedState
            ||
            !fs6SavedState.roots
        ){

            return false;
        }


        let changed =
            false;


        Object
        .keys(
            fs6SavedState.roots
        )
        .forEach(
            key => {

                const row =
                    fs6FindRow(
                        key
                    );


                if(!row){
                    return;
                }


                const wanted =
                    Boolean(
                        fs6SavedState.roots[key]
                    );


                const current =
                    fs6IsExpanded(
                        row
                    );


                if(
                    current !==
                    wanted
                ){

                    fs6ToggleRow(
                        row
                    );


                    changed =
                        true;
                }
            }
        );


        return changed;
    }



    function fs6FinishRestore(){

        if(
            tree
            &&
            fs6SavedState
        ){

            const position =
                Number(
                    fs6SavedState.scrollTop
                );


            if(
                Number.isFinite(
                    position
                )
            ){

                tree.scrollTop =
                    position;
            }
        }


        fs6Restoring =
            false;


        fs6RestoreAttempt =
            0;


        setTimeout(
            fs6CaptureState,
            300
        );
    }



    function fs6RestoreTick(){

        clearTimeout(
            fs6RestoreTimer
        );


        if(
            !tree
            ||
            !fs6SavedState
        ){

            fs6FinishRestore();

            return;
        }


        fs6RestoreAttempt++;


        let changed =
            fs6RestoreRoots();


        let waiting =
            false;


        const expanded =
            Array.isArray(
                fs6SavedState.expanded
            )
                ? fs6SavedState.expanded
                : [];


        for(
            const key of expanded
        ){

            const row =
                fs6FindRow(
                    key
                );


            if(!row){

                waiting =
                    true;

                continue;
            }


            if(
                !fs6IsExpanded(
                    row
                )
            ){

                fs6ToggleRow(
                    row
                );


                changed =
                    true;


                waiting =
                    true;
            }
        }


        const selected =
            fs6SavedState.selected;


        if(
            selected
            &&
            selected.id
        ){

            const itemRow =
                fs6FindSelectedItem(
                    selected
                );


            if(itemRow){

                if(
                    !itemRow.classList.contains(
                        "fs2-selected"
                    )
                ){

                    itemRow.dispatchEvent(
                        new MouseEvent(
                            "click",
                            {
                                bubbles:
                                    true,

                                cancelable:
                                    true,

                                view:
                                    window
                            }
                        )
                    );


                    changed =
                        true;
                }
            }
            else{

                waiting =
                    true;
            }
        }


        const position =
            Number(
                fs6SavedState.scrollTop
            );


        if(
            Number.isFinite(
                position
            )
        ){

            tree.scrollTop =
                position;
        }


        if(
            (
                waiting
                ||
                changed
            )
            &&
            fs6RestoreAttempt <
            40
        ){

            fs6RestoreTimer =
                setTimeout(
                    fs6RestoreTick,
                    150
                );


            return;
        }


        fs6FinishRestore();
    }



    function fs6ScheduleRestore(){

        if(
            !fs6Restoring
        ){

            return;
        }


        clearTimeout(
            fs6RestoreTimer
        );


        fs6RestoreTimer =
            setTimeout(
                fs6RestoreTick,
                80
            );
    }



    function fs6InstallState(){

        /*
         * Firestorm installLayout() creates the real #fs2-tree.
         */

        if(
            !tree
            ||
            !tree.isConnected
        ){

            setTimeout(
                fs6InstallState,
                100
            );


            return;
        }


        if(
            tree.dataset.fs6StateInstalled ===
            "1"
        ){

            return;
        }


        tree.dataset.fs6StateInstalled =
            "1";


        fs6SavedState =
            fs6ReadState();


        /*
         * Folder/root/item changes.
         */

        tree.addEventListener(
            "click",
            event => {

                if(
                    !event.target.closest(
                        ".fs2-folder-row, .fs2-root-row, .fs2-item-row"
                    )
                ){

                    return;
                }


                setTimeout(
                    fs6CaptureState,
                    100
                );
            }
        );


        /*
         * V5 right-click selects the right-clicked item.
         */

        tree.addEventListener(
            "contextmenu",
            event => {

                if(
                    !event.target.closest(
                        ".fs2-item-row"
                    )
                ){

                    return;
                }


                setTimeout(
                    fs6CaptureState,
                    120
                );
            }
        );


        /*
         * Existing keyboard activation.
         */

        tree.addEventListener(
            "keydown",
            event => {

                if(
                    event.key ===
                    "Enter"
                    ||
                    event.key ===
                    " "
                    ||
                    event.key ===
                    "ArrowLeft"
                    ||
                    event.key ===
                    "ArrowRight"
                ){

                    setTimeout(
                        fs6CaptureState,
                        100
                    );
                }
            }
        );


        /*
         * Save tree scroll position without writing on every
         * single scroll event.
         */

        tree.addEventListener(
            "scroll",
            () => {

                if(
                    fs6Restoring
                ){

                    return;
                }


                clearTimeout(
                    fs6ScrollTimer
                );


                fs6ScrollTimer =
                    setTimeout(
                        fs6CaptureState,
                        150
                    );
            },
            {
                passive:
                    true
            }
        );


        /*
         * Folder items are lazy-loaded. During restore, retry
         * when the real tree receives new child rows.
         */

        fs6Observer =
            new MutationObserver(
                () => {

                    if(
                        fs6Restoring
                    ){

                        fs6ScheduleRestore();
                    }
                }
            );


        fs6Observer.observe(
            tree,
            {
                childList:
                    true,

                subtree:
                    true
            }
        );


        window.addEventListener(
            "beforeunload",
            fs6CaptureState
        );


        if(fs6SavedState){

            fs6Restoring =
                true;


            fs6RestoreAttempt =
                0;


            fs6RestoreTimer =
                setTimeout(
                    fs6RestoreTick,
                    180
                );
        }
    }



    /*
     * Runs after normal Firestorm startup.
     * If installLayout has not finished yet, it retries.
     */

    setTimeout(
        fs6InstallState,
        150
    );


    /* AUSTRALIA FIRESTORM REMEMBER STATE V6 END */

    /* AUSTRALIA FIRESTORM SEARCH V7 START */


    let fs7Timer =
        null;


    let fs7Serial =
        0;


    let fs7Active =
        false;


    let fs7Query =
        "";


    let fs7PreExpanded =
        new Set();


    let fs7OpenedBySearch =
        new Set();


    let fs7PreScroll =
        0;


    let fs7MatchedFolderRows =
        new Set();


    let fs7MatchedItems =
        new Map();


    let fs7Observer =
        null;



    function fs7ItemKey(
        source,
        id
    ){

        return (
            String(
                source ||
                "personal"
            )
            +
            ":"
            +
            String(
                id ||
                ""
            ).toLowerCase()
        );
    }



    function fs7EscapeRegExp(
        value
    ){

        return String(
            value ||
            ""
        ).replace(
            /[.*+?^${}()|[\]\\]/g,
            "\\$&"
        );
    }



    function fs7NameElement(
        row
    ){

        if(!row){
            return null;
        }


        return row.querySelector(
            ":scope > .fs2-name"
        )
        ||
        row.querySelector(
            ".fs2-name"
        );
    }



    function fs7RememberOriginalName(
        element
    ){

        if(!element){
            return;
        }


        if(
            !Object.prototype.hasOwnProperty.call(
                element.dataset,
                "fs7Original"
            )
        ){

            element.dataset.fs7Original =
                String(
                    element.textContent ||
                    ""
                );
        }
    }



    function fs7ResetHighlights(){

        if(!tree){
            return;
        }


        tree
        .querySelectorAll(
            ".fs2-name[data-fs7-original]"
        )
        .forEach(
            element => {

                element.textContent =
                    String(
                        element.dataset.fs7Original ||
                        ""
                    );


                delete element.dataset.fs7Original;
            }
        );


        tree
        .querySelectorAll(
            ".fs7-folder-match, .fs7-item-match, .fs7-description-match"
        )
        .forEach(
            row => {

                row.classList.remove(
                    "fs7-folder-match",
                    "fs7-item-match",
                    "fs7-description-match"
                );
            }
        );
    }



    function fs7HighlightName(
        row,
        query
    ){

        const element =
            fs7NameElement(
                row
            );


        if(!element){
            return false;
        }


        fs7RememberOriginalName(
            element
        );


        const original =
            String(
                element.dataset.fs7Original ||
                ""
            );


        element.textContent =
            original;


        if(
            !query
            ||
            !original.toLowerCase().includes(
                query.toLowerCase()
            )
        ){

            return false;
        }


        const expression =
            new RegExp(
                fs7EscapeRegExp(
                    query
                ),
                "ig"
            );


        const fragment =
            document.createDocumentFragment();


        let lastIndex =
            0;


        let match;


        while(
            (
                match =
                    expression.exec(
                        original
                    )
            ) !==
            null
        ){

            if(
                match.index >
                lastIndex
            ){

                fragment.appendChild(
                    document.createTextNode(
                        original.slice(
                            lastIndex,
                            match.index
                        )
                    )
                );
            }


            const mark =
                document.createElement(
                    "mark"
                );


            mark.className =
                "fs7-mark";


            mark.textContent =
                match[0];


            fragment.appendChild(
                mark
            );


            lastIndex =
                match.index +
                match[0].length;


            if(
                match[0].length ===
                0
            ){

                expression.lastIndex++;
            }
        }


        if(
            lastIndex <
            original.length
        ){

            fragment.appendChild(
                document.createTextNode(
                    original.slice(
                        lastIndex
                    )
                )
            );
        }


        element.textContent =
            "";


        element.appendChild(
            fragment
        );


        return true;
    }



    function fs7RowDepth(
        row
    ){

        let depth =
            0;


        let node =
            row
                ? row.closest(
                    ".fs2-node"
                )
                : null;


        while(node){

            depth++;


            const parent =
                node.parentElement;


            node =
                parent
                    ? parent.closest(
                        ".fs2-node"
                    )
                    : null;
        }


        return depth;
    }



    function fs7FolderRow(
        source,
        folderId
    ){

        if(
            !tree
            ||
            !folderId
        ){

            return null;
        }


        const wanted =
            String(
                folderId
            ).toLowerCase();


        const selector =
            source ===
            "library"
                ? ".fs2-folder-row[data-library-folder-id]"
                : ".fs2-folder-row[data-folder-id]";


        const rows =
            tree.querySelectorAll(
                selector
            );


        for(
            const row of rows
        ){

            const value =
                source ===
                "library"
                    ? row.dataset.libraryFolderId
                    : row.dataset.folderId;


            if(
                String(
                    value ||
                    ""
                ).toLowerCase() ===
                wanted
            ){

                return row;
            }
        }


        return null;
    }



    function fs7AncestorRows(
        row
    ){

        const result =
            [];


        if(!row){
            return result;
        }


        let node =
            row.closest(
                ".fs2-node"
            );


        const seen =
            new Set();


        while(
            node
            &&
            !seen.has(
                node
            )
        ){

            seen.add(
                node
            );


            const directRow =
                Array.from(
                    node.children
                )
                .find(
                    child =>
                        child.classList
                        &&
                        (
                            child.classList.contains(
                                "fs2-folder-row"
                            )
                            ||
                            child.classList.contains(
                                "fs2-root-row"
                            )
                        )
                );


            if(directRow){

                result.unshift(
                    directRow
                );
            }


            const parent =
                node.parentElement;


            node =
                parent
                    ? parent.closest(
                        ".fs2-node"
                    )
                    : null;
        }


        return result;
    }



    function fs7EnsureOpen(
        row
    ){

        if(!row){
            return;
        }


        const key =
            fs6RowKey(
                row
            );


        if(
            fs6IsExpanded(
                row
            )
        ){

            return;
        }


        if(
            key
        ){

            fs7OpenedBySearch.add(
                key
            );
        }


        fs6ToggleRow(
            row
        );
    }



    function fs7OpenParents(
        row,
        openTarget
    ){

        const rows =
            fs7AncestorRows(
                row
            );


        rows.forEach(
            current => {

                if(
                    current ===
                    row
                    &&
                    !openTarget
                ){

                    return;
                }


                fs7EnsureOpen(
                    current
                );
            }
        );
    }



    function fs7CapturePreSearchState(){

        fs7PreExpanded =
            new Set();


        fs7OpenedBySearch =
            new Set();


        fs7PreScroll =
            Number(
                tree &&
                tree.scrollTop ||
                0
            );


        if(!tree){
            return;
        }


        tree
        .querySelectorAll(
            ".fs2-folder-row, .fs2-root-row"
        )
        .forEach(
            row => {

                const key =
                    fs6RowKey(
                        row
                    );


                if(
                    key
                    &&
                    fs6IsExpanded(
                        row
                    )
                ){

                    fs7PreExpanded.add(
                        key
                    );
                }
            }
        );


        /*
         * Save the proper V6 state BEFORE search opens
         * extra folders.
         */

        fs6CaptureState();
    }



    function fs7RestorePreSearchState(){

        if(!tree){
            return;
        }


        const rowsToClose =
            [];


        fs7OpenedBySearch.forEach(
            key => {

                if(
                    fs7PreExpanded.has(
                        key
                    )
                ){

                    return;
                }


                const row =
                    fs6FindRow(
                        key
                    );


                if(
                    row
                    &&
                    fs6IsExpanded(
                        row
                    )
                ){

                    rowsToClose.push(
                        row
                    );
                }
            }
        );


        /*
         * Close deepest branches first.
         */

        rowsToClose.sort(
            (
                a,
                b
            ) =>
                fs7RowDepth(
                    b
                )
                -
                fs7RowDepth(
                    a
                )
        );


        rowsToClose.forEach(
            row => {

                if(
                    fs6IsExpanded(
                        row
                    )
                ){

                    fs6ToggleRow(
                        row
                    );
                }
            }
        );


        setTimeout(
            () => {

                if(tree){

                    tree.scrollTop =
                        fs7PreScroll;
                }


                fs6Restoring =
                    false;


                setTimeout(
                    fs6CaptureState,
                    120
                );
            },
            120
        );
    }



    function fs7ClearVisualFilter(){

        if(!tree){
            return;
        }


        tree
        .querySelectorAll(
            ".fs7-hidden"
        )
        .forEach(
            element => {

                element.classList.remove(
                    "fs7-hidden"
                );
            }
        );


        const empty =
            tree.querySelector(
                ".fs7-empty"
            );


        if(empty){

            empty.remove();
        }


        fs7ResetHighlights();
    }



    function fs7Deactivate(){

        fs7Serial++;


        clearTimeout(
            fs7Timer
        );


        fs7ClearVisualFilter();


        if(
            fs7Active
        ){

            fs7RestorePreSearchState();
        }
        else{

            fs6Restoring =
                false;
        }


        fs7Active =
            false;


        fs7Query =
            "";


        fs7MatchedFolderRows =
            new Set();


        fs7MatchedItems =
            new Map();


        if(search){

            search.classList.remove(
                "fs7-searching"
            );
        }
    }



    function fs7AddRelevantNode(
        set,
        row
    ){

        if(!row){
            return;
        }


        let node =
            row.closest(
                ".fs2-node"
            );


        const seen =
            new Set();


        while(
            node
            &&
            !seen.has(
                node
            )
        ){

            seen.add(
                node
            );


            set.add(
                node
            );


            const parent =
                node.parentElement;


            node =
                parent
                    ? parent.closest(
                        ".fs2-node"
                    )
                    : null;
        }
    }



    function fs7RefreshVisuals(){

        if(
            !fs7Active
            ||
            !tree
        ){

            return;
        }


        fs7ClearVisualFilter();


        const keepNodes =
            new Set();


        fs7MatchedFolderRows.forEach(
            row => {

                if(
                    !row.isConnected
                ){

                    return;
                }


                fs7AddRelevantNode(
                    keepNodes,
                    row
                );


                if(
                    fs7HighlightName(
                        row,
                        fs7Query
                    )
                ){

                    row.classList.add(
                        "fs7-folder-match"
                    );
                }
            }
        );


        /*
         * Item search results also keep their real folder
         * and all real ancestors visible.
         */

        fs7MatchedItems.forEach(
            result => {

                const folderRow =
                    fs7FolderRow(
                        result.source,
                        result.folderId
                    );


                if(folderRow){

                    fs7AddRelevantNode(
                        keepNodes,
                        folderRow
                    );
                }
            }
        );


        /*
         * Hide unrelated folder/root nodes.
         */

        tree
        .querySelectorAll(
            ".fs2-node"
        )
        .forEach(
            node => {

                if(
                    !keepNodes.has(
                        node
                    )
                ){

                    node.classList.add(
                        "fs7-hidden"
                    );
                }
            }
        );


        /*
         * Hide every loaded item unless it is an actual result.
         */

        tree
        .querySelectorAll(
            ".fs2-item-row"
        )
        .forEach(
            row => {

                const key =
                    fs7ItemKey(
                        row.dataset.fs6Source,
                        row.dataset.fs6ItemId
                    );


                const result =
                    fs7MatchedItems.get(
                        key
                    );


                if(!result){

                    row.classList.add(
                        "fs7-hidden"
                    );

                    return;
                }


                row.classList.remove(
                    "fs7-hidden"
                );


                const nameMatched =
                    fs7HighlightName(
                        row,
                        fs7Query
                    );


                if(nameMatched){

                    row.classList.add(
                        "fs7-item-match"
                    );
                }
                else{

                    row.classList.add(
                        "fs7-description-match"
                    );
                }
            }
        );


        const visibleResultCount =
            fs7MatchedFolderRows.size +
            fs7MatchedItems.size;


        if(
            visibleResultCount ===
            0
        ){

            const empty =
                document.createElement(
                    "div"
                );


            empty.className =
                "fs7-empty";


            empty.textContent =
                `No Inventory matches for "${fs7Query}".`;


            tree.appendChild(
                empty
            );
        }
    }



    function fs7NormaliseItem(
        raw,
        source
    ){

        if(!raw){
            return null;
        }


        if(
            raw.item
            &&
            typeof raw.item ===
            "object"
        ){

            raw =
                raw.item;
        }


        const kind =
            String(
                raw.kind
                ||
                raw.resultType
                ||
                raw.typeName
                ||
                ""
            ).toLowerCase();


        if(
            kind ===
            "folder"
        ){

            return null;
        }


        const id =
            raw.id
            ||
            raw.inventoryID
            ||
            raw.inventoryId
            ||
            raw.inventory_id
            ||
            raw.itemId
            ||
            raw.itemID
            ||
            "";


        const folderId =
            raw.folderId
            ||
            raw.folderID
            ||
            raw.parentFolderID
            ||
            raw.parentFolderId
            ||
            raw.parent_folder_id
            ||
            raw.parent
            ||
            "";


        if(
            !id
            ||
            !folderId
        ){

            return null;
        }


        return {

            source:
                source,

            id:
                String(
                    id
                ),

            folderId:
                String(
                    folderId
                ),

            name:
                String(
                    raw.name
                    ||
                    raw.inventoryName
                    ||
                    raw.inventory_name
                    ||
                    ""
                ),

            description:
                String(
                    raw.description
                    ||
                    raw.inventoryDescription
                    ||
                    raw.inventory_description
                    ||
                    ""
                ),

            raw:
                raw
        };
    }



    function fs7ItemsFromResponse(
        response,
        source
    ){

        const result =
            [];


        if(!response){
            return result;
        }


        const arrays =
            [];


        if(
            Array.isArray(
                response.items
            )
        ){

            arrays.push(
                response.items
            );
        }


        if(
            Array.isArray(
                response.results
            )
        ){

            arrays.push(
                response.results
            );
        }


        if(
            Array.isArray(
                response.matches
            )
        ){

            arrays.push(
                response.matches
            );
        }


        arrays.forEach(
            collection => {

                collection.forEach(
                    raw => {

                        const item =
                            fs7NormaliseItem(
                                raw,
                                source
                            );


                        if(item){

                            result.push(
                                item
                            );
                        }
                    }
                );
            }
        );


        return result;
    }



    function fs7ResponseHasSearchArrays(
        response
    ){

        if(!response){
            return false;
        }


        return (
            Array.isArray(
                response.items
            )
            ||
            Array.isArray(
                response.results
            )
            ||
            Array.isArray(
                response.matches
            )
            ||
            Array.isArray(
                response.folders
            )
        );
    }



    async function fs7SearchPersonal(
        query
    ){

        const response =
            await api(
                "searchv7",
                {
                    q:
                        query
                }
            );


        return fs7ItemsFromResponse(
            response,
            "personal"
        );
    }



    async function fs7SearchLibraryApi(
        query
    ){

        const attempts = [

            {
                q:
                    query
            },

            {
                query:
                    query
            },

            {
                search:
                    query
            }

        ];


        let recognised =
            false;


        const found =
            new Map();


        for(
            const params of attempts
        ){

            try{

                const response =
                    await libraryApi(
                        "search",
                        params
                    );


                if(
                    fs7ResponseHasSearchArrays(
                        response
                    )
                ){

                    recognised =
                        true;


                    fs7ItemsFromResponse(
                        response,
                        "library"
                    )
                    .forEach(
                        item => {

                            found.set(
                                fs7ItemKey(
                                    "library",
                                    item.id
                                ),
                                item
                            );
                        }
                    );
                }
            }
            catch(error){

            }
        }


        return {

            recognised:
                recognised,

            items:
                Array.from(
                    found.values()
                )
        };
    }



    async function fs7SearchLibraryFallback(
        query
    ){

        const found =
            [];


        if(
            typeof libraryFolderMap ===
            "undefined"
            ||
            !libraryFolderMap
        ){

            return found;
        }


        const folders =
            Array.from(
                libraryFolderMap.values()
            );


        /*
         * Process in modest batches so a Library with many
         * folders does not hammer the browser all at once.
         */

        const batchSize =
            10;


        for(
            let offset = 0;
            offset < folders.length;
            offset += batchSize
        ){

            const batch =
                folders.slice(
                    offset,
                    offset + batchSize
                );


            const collections =
                await Promise.all(
                    batch.map(
                        async folder => {

                            try{

                                const items =
                                    await loadLibraryFolderItems(
                                        folder
                                    );


                                return Array.isArray(
                                    items
                                )
                                    ? items
                                    : [];
                            }
                            catch(error){

                                return [];
                            }
                        }
                    )
                );


            collections.forEach(
                items => {

                    items.forEach(
                        raw => {

                            const name =
                                String(
                                    raw.name ||
                                    ""
                                );


                            const description =
                                String(
                                    raw.description ||
                                    ""
                                );


                            if(
                                !name.toLowerCase().includes(
                                    query.toLowerCase()
                                )
                                &&
                                !description.toLowerCase().includes(
                                    query.toLowerCase()
                                )
                            ){

                                return;
                            }


                            const item =
                                fs7NormaliseItem(
                                    raw,
                                    "library"
                                );


                            if(item){

                                found.push(
                                    item
                                );
                            }
                        }
                    );
                }
            );
        }


        return found;
    }



    async function fs7SearchLibrary(
        query
    ){

        const apiResult =
            await fs7SearchLibraryApi(
                query
            );


        if(
            apiResult.recognised
        ){

            return apiResult.items;
        }


        return await fs7SearchLibraryFallback(
            query
        );
    }



    function fs7FindFolderNameMatches(
        query
    ){

        const found =
            new Set();


        if(!tree){
            return found;
        }


        tree
        .querySelectorAll(
            ".fs2-folder-row"
        )
        .forEach(
            row => {

                const element =
                    fs7NameElement(
                        row
                    );


                const name =
                    element
                        ? String(
                            element.dataset.fs7Original
                            ||
                            element.textContent
                            ||
                            ""
                        )
                        : "";


                if(
                    name.toLowerCase().includes(
                        query.toLowerCase()
                    )
                ){

                    found.add(
                        row
                    );
                }
            }
        );


        return found;
    }



    function fs7PrepareMatchingFolders(
        folderRows
    ){

        folderRows.forEach(
            row => {

                /*
                 * Folder-name result:
                 * open parents but do not force the matching
                 * folder itself open.
                 */

                fs7OpenParents(
                    row,
                    false
                );
            }
        );
    }



    function fs7PrepareMatchingItems(
        items
    ){

        items.forEach(
            result => {

                const folderRow =
                    fs7FolderRow(
                        result.source,
                        result.folderId
                    );


                if(!folderRow){
                    return;
                }


                /*
                 * Item result:
                 * open parents AND its folder so the real item
                 * row is lazy-loaded into the real hierarchy.
                 */

                fs7OpenParents(
                    folderRow,
                    true
                );
            }
        );
    }



    async function fs7RunSearch(
        rawQuery
    ){

        const query =
            String(
                rawQuery ||
                ""
            ).trim();


        if(
            query.length ===
            0
        ){

            fs7Deactivate();


            statusLeft.textContent =
                "INVENTORY READY";


            statusRight.textContent =
                "READY";


            return;
        }


        if(
            query.length <
            2
        ){

            fs7Deactivate();


            statusLeft.textContent =
                "TYPE AT LEAST 2 CHARACTERS";


            statusRight.textContent =
                "FILTER";


            return;
        }


        const mySerial =
            ++fs7Serial;


        if(
            !fs7Active
        ){

            fs7CapturePreSearchState();


            fs7Active =
                true;


            /*
             * Stop V6 from saving folders that V7 temporarily
             * opens while filtering.
             */

            fs6Restoring =
                true;
        }


        fs7Query =
            query;


        if(search){

            search.classList.add(
                "fs7-searching"
            );
        }


        statusLeft.textContent =
            `SEARCHING • ${query}`;


        statusRight.textContent =
            "FILTER";


        /*
         * Folder names are already represented by the real
         * hierarchy, so search them directly.
         */

        const folderMatches =
            fs7FindFolderNameMatches(
                query
            );


        let personalItems =
            [];


        let libraryItems =
            [];


        try{

            const results =
                await Promise.all([

                    fs7SearchPersonal(
                        query
                    ),

                    fs7SearchLibrary(
                        query
                    )

                ]);


            personalItems =
                results[0];


            libraryItems =
                results[1];
        }
        catch(error){

            if(
                mySerial !==
                fs7Serial
            ){

                return;
            }


            statusLeft.textContent =
                "SEARCH ERROR";


            statusRight.textContent =
                "ERROR";


            console.error(
                "Inventory V7 search failed:",
                error
            );


            return;
        }


        if(
            mySerial !==
            fs7Serial
        ){

            return;
        }


        const allItems = [

            ...personalItems,
            ...libraryItems

        ];


        const itemMap =
            new Map();


        allItems.forEach(
            result => {

                itemMap.set(
                    fs7ItemKey(
                        result.source,
                        result.id
                    ),
                    result
                );
            }
        );


        fs7MatchedFolderRows =
            folderMatches;


        fs7MatchedItems =
            itemMap;


        fs7PrepareMatchingFolders(
            folderMatches
        );


        fs7PrepareMatchingItems(
            itemMap.values()
        );


        /*
         * First pass immediately.
         */

        fs7RefreshVisuals();


        /*
         * Extra passes catch lazy-loaded item rows.
         */

        setTimeout(
            () => {

                if(
                    mySerial ===
                    fs7Serial
                    &&
                    fs7Active
                ){

                    fs7RefreshVisuals();
                }
            },
            250
        );


        setTimeout(
            () => {

                if(
                    mySerial ===
                    fs7Serial
                    &&
                    fs7Active
                ){

                    fs7RefreshVisuals();
                }
            },
            700
        );


        setTimeout(
            () => {

                if(
                    mySerial ===
                    fs7Serial
                    &&
                    fs7Active
                ){

                    fs7RefreshVisuals();
                }
            },
            1300
        );


        const total =
            folderMatches.size +
            itemMap.size;


        statusLeft.textContent =
            `${total.toLocaleString()} MATCH${total === 1 ? "" : "ES"} • ${query}`;


        statusRight.textContent =
            "FILTERED";
    }



    function fs7InstallSearch(){

        if(
            !search
            ||
            !tree
            ||
            !search.isConnected
            ||
            !tree.isConnected
        ){

            setTimeout(
                fs7InstallSearch,
                100
            );


            return;
        }


        if(
            search.dataset.fs7Installed ===
            "1"
        ){

            return;
        }


        search.dataset.fs7Installed =
            "1";


        search.placeholder =
            "Filter Inventory";


        search.autocomplete =
            "off";


        search.spellcheck =
            false;


        search.addEventListener(
            "input",
            () => {

                clearTimeout(
                    fs7Timer
                );


                const value =
                    search.value;


                fs7Timer =
                    setTimeout(
                        () => {

                            fs7RunSearch(
                                value
                            );
                        },
                        280
                    );
            }
        );


        search.addEventListener(
            "keydown",
            event => {

                if(
                    event.key ===
                    "Escape"
                ){

                    event.preventDefault();


                    search.value =
                        "";


                    fs7Deactivate();


                    statusLeft.textContent =
                        "INVENTORY READY";


                    statusRight.textContent =
                        "READY";
                }
            }
        );


        /*
         * Reapply the active filter when lazy-loaded rows
         * arrive.
         */

        fs7Observer =
            new MutationObserver(
                () => {

                    if(
                        !fs7Active
                    ){

                        return;
                    }


                    clearTimeout(
                        fs7Observer.refreshTimer
                    );


                    fs7Observer.refreshTimer =
                        setTimeout(
                            fs7RefreshVisuals,
                            80
                        );
                }
            );


        fs7Observer.observe(
            tree,
            {
                childList:
                    true,

                subtree:
                    true
            }
        );
    }



    setTimeout(
        fs7InstallSearch,
        200
    );


    
    /* AUSTRALIA SEARCH REFRESH CLEAR V7.1 START */


    function fs71ClearSearchOnReload(){

        if(
            !search
            ||
            !search.isConnected
        ){

            setTimeout(
                fs71ClearSearchOnReload,
                80
            );


            return;
        }


        /*
         * Chromium / Edge may restore form-control values
         * during refresh. Force Filter Inventory back to an
         * empty value for a fresh page load.
         */

        search.value =
            "";


        search.defaultValue =
            "";


        search.classList.remove(
            "fs7-searching"
        );


        /*
         * Clear any V7 visual filtering that may have been
         * restored with the page DOM.
         *
         * Do NOT wipe V6 localStorage. V6 can still restore
         * normal folders, selection and scroll position.
         */

        fs7Serial++;


        clearTimeout(
            fs7Timer
        );


        fs7Query =
            "";


        fs7Active =
            false;


        fs7MatchedFolderRows =
            new Set();


        fs7MatchedItems =
            new Map();


        fs7OpenedBySearch =
            new Set();


        fs7ClearVisualFilter();


        if(
            typeof fs5CloseContextMenu ===
            "function"
        ){

            fs5CloseContextMenu();
        }


        statusRight.textContent =
            "READY";
    }



    /*
     * Normal fresh load.
     */

    setTimeout(
        fs71ClearSearchOnReload,
        260
    );



    /*
     * Handles browser history cache / Chromium form restore.
     */

    window.addEventListener(
        "pageshow",
        () => {

            setTimeout(
                fs71ClearSearchOnReload,
                40
            );


            setTimeout(
                fs71ClearSearchOnReload,
                300
            );
        }
    );


    /* AUSTRALIA SEARCH REFRESH CLEAR V7.1 END */


/* AUSTRALIA FIRESTORM SEARCH V7 END */

    /* AUSTRALIA FIRESTORM TYPE FILTER V8 START */
/* AUSTRALIA FIRESTORM TYPE FILTER FUNCTION V8.6 */

    let fs8TypeFilter =
        null;

    let fs8Observer =
        null;

    let fs8Serial =
        0;

    let fs8Active =
        false;

    let fs8Applying =
        false;

    let fs8SavedState =
        null;

    let fs8TypeIndexReady =
        false;

    let fs8TypeIndex =
        new Map();

    let fs8Matches =
        new Map();


    const FS8_TYPE_LABELS = {

        all:
            "ALL TYPES",

        texture:
            "TEXTURES",

        sound:
            "SOUNDS",

        callingcard:
            "CALLING CARDS",

        landmark:
            "LANDMARKS",

        clothing:
            "CLOTHING",

        object:
            "OBJECTS",

        notecard:
            "NOTECARDS",

        script:
            "SCRIPTS",

        bodypart:
            "BODY PARTS",

        animation:
            "ANIMATIONS",

        gesture:
            "GESTURES",

        link:
            "INVENTORY LINKS",

        mesh:
            "MESH",

        settings:
            "SETTINGS",

        material:
            "MATERIALS",

        inventory:
            "OTHER ITEMS"
    };


    function fs8ItemKey(
        source,
        id
    ){

        return (
            String(
                source ||
                "personal"
            )
            +
            ":"
            +
            String(
                id ||
                ""
            ).toLowerCase()
        );
    }


    function fs8ResetTypeIndex(){

        fs8TypeIndexReady =
            false;

        fs8TypeIndex =
            new Map();

        fs8Matches =
            new Map();

        fs8Active =
            false;

        fs8Applying =
            false;

        fs8Serial++;


        if(fs8TypeFilter){

            fs8TypeFilter.value =
                "all";
        }
    }


    function fs8ClearVisualFilter(){

        if(!tree){
            return;
        }


        tree
        .querySelectorAll(
            ".fs8-type-hidden"
        )
        .forEach(
            element => {

                element.classList.remove(
                    "fs8-type-hidden"
                );
            }
        );


        tree
        .querySelectorAll(
            ".fs8-empty"
        )
        .forEach(
            element => {

                element.remove();
            }
        );
    }


    function fs8KeepNode(
        keepNodes,
        row
    ){

        if(!row){
            return;
        }


        let node =
            row.closest(
                ".fs2-node"
            );


        const seen =
            new Set();


        while(
            node
            &&
            !seen.has(
                node
            )
        ){

            seen.add(
                node
            );


            keepNodes.add(
                node
            );


            const parent =
                node.parentElement;


            node =
                parent
                    ? parent.closest(
                        ".fs2-node"
                    )
                    : null;
        }
    }


    function fs8RefreshVisuals(){

        if(
            !fs8Active
            ||
            fs8Applying
            ||
            !tree
        ){
            return;
        }


        fs8ClearVisualFilter();


        const keepNodes =
            new Set();


        fs8Matches.forEach(
            result => {

                const folderRow =
                    fs7FolderRow(
                        result.source,
                        result.folderId
                    );


                if(folderRow){

                    fs8KeepNode(
                        keepNodes,
                        folderRow
                    );
                }
            }
        );


        tree
        .querySelectorAll(
            ".fs2-node"
        )
        .forEach(
            node => {

                if(
                    !keepNodes.has(
                        node
                    )
                ){

                    node.classList.add(
                        "fs8-type-hidden"
                    );
                }
            }
        );


        tree
        .querySelectorAll(
            ".fs2-item-row"
        )
        .forEach(
            row => {

                const key =
                    fs8ItemKey(
                        row.dataset.fs6Source,
                        row.dataset.fs6ItemId
                    );


                if(
                    !fs8Matches.has(
                        key
                    )
                ){

                    row.classList.add(
                        "fs8-type-hidden"
                    );
                }
            }
        );


        if(
            fs8Matches.size ===
            0
            &&
            !tree.querySelector(
                ".fs8-empty"
            )
        ){

            const empty =
                document.createElement(
                    "div"
                );


            empty.className =
                "fs8-empty fs2-message";


            empty.textContent =
                "No items of this type.";


            tree.appendChild(
                empty
            );
        }
    }


    function fs8OpenMatchingFolders(){

        const opened =
            new Set();


        fs8Matches.forEach(
            result => {

                const folderKey =
                    String(
                        result.source
                    )
                    +
                    ":"
                    +
                    String(
                        result.folderId ||
                        ""
                    ).toLowerCase();


                if(
                    opened.has(
                        folderKey
                    )
                ){
                    return;
                }


                opened.add(
                    folderKey
                );


                const folderRow =
                    fs7FolderRow(
                        result.source,
                        result.folderId
                    );


                if(!folderRow){
                    return;
                }


                const rows =
                    fs7AncestorRows(
                        folderRow
                    );


                rows.forEach(
                    current => {

                        if(
                            !fs6IsExpanded(
                                current
                            )
                        ){

                            fs6ToggleRow(
                                current
                            );
                        }
                    }
                );
            }
        );
    }


    async function fs8BuildTypeIndex(
        mySerial
    ){

        if(fs8TypeIndexReady){
            return true;
        }


        const targets =
            [];


        folders.forEach(
            folder => {

                if(
                    Number(
                        folder &&
                        folder.itemCount ||
                        0
                    ) >
                    0
                ){

                    targets.push({

                        source:
                            "personal",

                        folder:
                            folder
                    });
                }
            }
        );


        libraryFolders.forEach(
            folder => {

                if(
                    Number(
                        folder &&
                        folder.itemCount ||
                        0
                    ) >
                    0
                ){

                    targets.push({

                        source:
                            "library",

                        folder:
                            folder
                    });
                }
            }
        );


        const built =
            new Map();


        let nextIndex =
            0;


        async function worker(){

            while(true){

                const index =
                    nextIndex++;


                if(
                    index >=
                    targets.length
                ){
                    return;
                }


                if(
                    mySerial !==
                    fs8Serial
                ){
                    return;
                }


                const target =
                    targets[
                        index
                    ];


                let items =
                    [];


                try{

                    if(
                        target.source ===
                        "library"
                    ){

                        items =
                            await loadLibraryFolderItems(
                                target.folder
                            );
                    }
                    else{

                        items =
                            await loadFolderItems(
                                target.folder
                            );
                    }
                }
                catch(error){

                    console.error(
                        "Inventory V8 type index folder failed:",
                        error
                    );

                    continue;
                }


                items.forEach(
                    item => {

                        const id =
                            String(
                                item &&
                                item.id ||
                                ""
                            );


                        if(!id){
                            return;
                        }


                        const meta =
                            typeInfo(
                                item
                            );


                        const itemType =
                            String(
                                meta.key ||
                                "inventory"
                            );


                        if(
                            !built.has(
                                itemType
                            )
                        ){

                            built.set(
                                itemType,
                                new Map()
                            );
                        }


                        built
                        .get(
                            itemType
                        )
                        .set(
                            fs8ItemKey(
                                target.source,
                                id
                            ),
                            {
                                source:
                                    target.source,

                                id:
                                    id,

                                folderId:
                                    String(
                                        item.folderId ||
                                        target.folder.id ||
                                        ""
                                    )
                            }
                        );
                    }
                );
            }
        }


        const workerCount =
            Math.min(
                6,
                Math.max(
                    1,
                    targets.length
                )
            );


        const workers =
            [];


        for(
            let index = 0;
            index < workerCount;
            index++
        ){

            workers.push(
                worker()
            );
        }


        await Promise.all(
            workers
        );


        if(
            mySerial !==
            fs8Serial
        ){
            return false;
        }


        fs8TypeIndex =
            built;


        fs8TypeIndexReady =
            true;


        return true;
    }


    async function fs8RestoreNormalTree(){

        fs8Active =
            false;

        fs8Applying =
            true;

        fs8Matches =
            new Map();


        fs8ClearVisualFilter();


        await renderTree();


        fs8Applying =
            false;


        if(fs8SavedState){

            fs6SavedState =
                fs8SavedState;


            fs6Restoring =
                true;


            fs6RestoreAttempt =
                0;


            fs6ScheduleRestore();
        }
        else{

            fs6Restoring =
                false;
        }
    }


    async function fs8ApplyTypeFilter(
        updateStatus = true
    ){

        if(
            !tree
            ||
            !fs8TypeFilter
        ){
            return;
        }


        const selected =
            String(
                fs8TypeFilter.value ||
                "all"
            );


        const mySerial =
            ++fs8Serial;


        if(
            selected ===
            "all"
        ){

            await fs8RestoreNormalTree();

            return;
        }


        if(!fs8Active){

            fs6CaptureState();


            fs8SavedState =
                fs6ReadState();
        }


        fs8Active =
            true;

        fs8Applying =
            true;


        clearTimeout(
            fs6RestoreTimer
        );


        fs6Restoring =
            true;


        if(
            search
            &&
            String(
                search.value ||
                ""
            ).trim() !==
            ""
        ){

            search.value =
                "";


            if(
                typeof fs7Deactivate ===
                "function"
            ){

                fs7Deactivate();
            }
        }


        await renderTree();


        if(
            mySerial !==
            fs8Serial
        ){
            return;
        }


        const label =
            FS8_TYPE_LABELS[
                selected
            ]
            ||
            selected.toUpperCase();


        if(updateStatus){

            statusLeft.textContent =
                `FILTERING • ${label}`;


            statusRight.textContent =
                "TYPE FILTER";
        }


        const ready =
            await fs8BuildTypeIndex(
                mySerial
            );


        if(
            !ready
            ||
            mySerial !==
            fs8Serial
        ){
            return;
        }


        fs8Matches =
            new Map(
                fs8TypeIndex.get(
                    selected
                )
                ||
                []
            );


        fs8Applying =
            false;


        fs8OpenMatchingFolders();


        fs8RefreshVisuals();


        [
            80,
            220,
            500,
            1000
        ]
        .forEach(
            delay => {

                setTimeout(
                    () => {

                        if(
                            mySerial ===
                            fs8Serial
                            &&
                            fs8Active
                        ){

                            fs8RefreshVisuals();
                        }
                    },
                    delay
                );
            }
        );


        statusLeft.textContent =
            `${fs8Matches.size.toLocaleString()} ${label}`;


        statusRight.textContent =
            "TYPE FILTER";
    }


    function fs8InstallTypeFilter(){

        fs8TypeFilter =
            document.getElementById(
                "fs8-type-filter"
            );


        if(!fs8TypeFilter){
            return;
        }


        if(
            fs8TypeFilter.dataset.fs8Installed ===
            "1"
        ){
            return;
        }


        fs8TypeFilter.dataset.fs8Installed =
            "1";


        fs8TypeFilter.addEventListener(
            "change",
            () => {

                fs8ApplyTypeFilter(
                    true
                )
                .catch(
                    error => {

                        fs8Applying =
                            false;


                        statusLeft.textContent =
                            "TYPE FILTER ERROR";


                        statusRight.textContent =
                            "ERROR";


                        console.error(
                            "Inventory V8 type filter failed:",
                            error
                        );
                    }
                );
            }
        );


        if(fs8Observer){

            fs8Observer.disconnect();
        }


        fs8Observer =
            new MutationObserver(
                () => {

                    if(
                        fs8Active
                        &&
                        !fs8Applying
                    ){

                        fs8RefreshVisuals();
                    }
                }
            );


        fs8Observer.observe(
            tree,
            {
                childList:
                    true,

                subtree:
                    true
            }
        );
    }

/* AUSTRALIA FIRESTORM TYPE FILTER V8 END */

    /* AUSTRALIA FIRESTORM DROPDOWN THEME V8.4 START */

    function fs84SyncDropdownTheme(){

        const select =
            document.getElementById(
                "fs8-type-filter"
            );

        const referenceButton =
            document.getElementById(
                "fs2-refresh"
            );

        if(
            !select ||
            !referenceButton
        ){
            return;
        }


        const style =
            window.getComputedStyle(
                referenceButton
            );


        const properties = [

            "background-color",
            "background-image",
            "background-position",
            "background-size",
            "background-repeat",

            "border-top-color",
            "border-right-color",
            "border-bottom-color",
            "border-left-color",

            "border-top-style",
            "border-right-style",
            "border-bottom-style",
            "border-left-style",

            "border-top-width",
            "border-right-width",
            "border-bottom-width",
            "border-left-width",

            "border-top-left-radius",
            "border-top-right-radius",
            "border-bottom-left-radius",
            "border-bottom-right-radius",

            "box-shadow",

            "color",

            "font-family",
            "font-size",
            "font-weight",
            "letter-spacing",

            "text-shadow",

            "min-height"
        ];


        properties.forEach(
            property => {

                const value =
                    style.getPropertyValue(
                        property
                    );

                if(value){

                    select.style.setProperty(
                        property,
                        value,
                        "important"
                    );
                }
            }
        );


        /*
         * Do not allow the native focused select
         * to replace the Control Center button face.
         */

        select.style.setProperty(
            "outline",
            "none",
            "important"
        );


        /*
         * Windows Chromium native OPTION lists need
         * an explicit dark background.
         */

        select
        .querySelectorAll(
            "option"
        )
        .forEach(
            option => {

                option.style.setProperty(
                    "background-color",
                    "#0f110e",
                    "important"
                );

                option.style.setProperty(
                    "color",
                    "#f4f5f4",
                    "important"
                );
            }
        );
    }

/* AUSTRALIA FIRESTORM DROPDOWN THEME V8.4 END */

    setTimeout(
        fs84SyncDropdownTheme,
        100
    );


    window.addEventListener(
        "pageshow",
        () => {
            setTimeout(
                fs84SyncDropdownTheme,
                100
            );
        }
    );









    /* AUSTRALIA FIRESTORM FOLDER DETAILS V10 START */

    function showFolderDetails(
        folder,
        isLibrary
    ){

        if(
            !detailBody
            ||
            !folder
        ){
            return;
        }


        /*
         * Cancel any old asynchronous item-details response.
         */
        detailBody.dataset.fs4Token =
            "";


        /*
         * Remove the previous item highlight because
         * folder information is now being displayed.
         */
        if(selectedItemRow){

            selectedItemRow.classList.remove(
                "fs2-selected"
            );

            selectedItemRow =
                null;
        }


        const heading =
            document.getElementById(
                "fs2-detail-heading"
            );


        if(heading){

            heading.textContent =
                "FOLDER DETAILS";
        }


        const folderTypes = {

            "-1":"USER FOLDER",
            "0":"TEXTURES",
            "1":"SOUNDS",
            "2":"CALLING CARDS",
            "3":"LANDMARKS",
            "5":"CLOTHING",
            "6":"OBJECTS",
            "7":"NOTECARDS",
            "8":"INVENTORY ROOT",
            "10":"SCRIPTS",
            "13":"BODY PARTS",
            "14":"TRASH",
            "15":"PHOTO ALBUM",
            "16":"LOST AND FOUND",
            "20":"ANIMATIONS",
            "21":"GESTURES",
            "23":"FAVORITES",
            "46":"CURRENT OUTFIT",
            "47":"OUTFIT",
            "48":"MY OUTFITS",
            "50":"RECEIVED ITEMS",
            "56":"SETTINGS",
            "57":"MATERIALS",
            "100":"MY SUITCASE"
        };


        const rawType =
            folder.type;


        let typeText =
            "—";


        if(
            rawType !==
            null
            &&
            rawType !==
            undefined
            &&
            rawType !==
            ""
        ){

            const numericType =
                String(
                    Number(
                        rawType
                    )
                );


            typeText =
                (
                    folderTypes[numericType]
                    ||
                    "TYPE"
                )
                +
                " — "
                +
                String(
                    rawType
                );
        }


        const directItems =
            Number(
                folder.itemCount
                ||
                0
            );


        const subfolders =
            Array.isArray(
                folder.children
            )
                ? folder.children.length
                : 0;


        const fullPath =
            isLibrary
                ? libraryFolderPath(
                    folder.id
                )
                : folderPath(
                    folder.id
                );


        detailBody.innerHTML =
            `
            <div class="fs2-detail-hero">

                <img
                    class="fs2-detail-hero-icon"
                    src="${ICONS.folder}"
                    alt=""
                    aria-hidden="true"
                    draggable="false">

                <div>

                    <h3
                        class="fs2-detail-name">
                    </h3>

                    <div class="fs2-detail-type">
                        FOLDER
                    </div>

                    <div class="fs4-source">
                    </div>

                </div>

            </div>


            <div class="fs4-section">

                <div class="fs4-title">
                    FOLDER INFORMATION
                </div>

                <div
                    id="fs10-folder-info"
                    class="fs4-info">
                </div>

            </div>
            `;


        detailBody
        .querySelector(
            ".fs2-detail-name"
        ).textContent =
            String(
                folder.name
                ||
                "Unnamed Folder"
            );


        detailBody
        .querySelector(
            ".fs4-source"
        ).textContent =
            isLibrary
                ? "OPENSIM LIBRARY"
                : "AVATAR INVENTORY";


        const info =
            document.getElementById(
                "fs10-folder-info"
            );


        fs4InfoRow(
            info,
            "FULL PATH",
            fullPath
        );


        fs4InfoRow(
            info,
            "FOLDER UUID",
            folder.id
        );


        fs4InfoRow(
            info,
            "PARENT UUID",
            folder.parent
        );


        fs4InfoRow(
            info,
            "FOLDER TYPE",
            typeText
        );


        fs4InfoRow(
            info,
            "DIRECT ITEMS",
            directItems.toLocaleString()
        );


        fs4InfoRow(
            info,
            "SUBFOLDERS",
            subfolders.toLocaleString()
        );


        fs4InfoRow(
            info,
            "SOURCE",
            isLibrary
                ? "OpenSim Library"
                : "Avatar Inventory"
        );


        fs4InfoRow(
            info,
            "ACCESS",
            isLibrary
                ? "READ ONLY"
                : "ADMIN BROWSER — READ ONLY"
        );


        statusLeft.textContent =
            (
                "FOLDER • "
                +
                String(
                    folder.name
                    ||
                    "Unnamed Folder"
                )
            );


        statusRight.textContent =
            isLibrary
                ? "LIBRARY"
                : "SELECTED";
    }

    /* AUSTRALIA FIRESTORM FOLDER DETAILS V10 END */

    /* AUSTRALIA FIRESTORM FOLDER CONTEXT MENU V11 START */


    function fs11FolderPath(
        folder,
        isLibrary
    ){

        if(
            !folder
            ||
            !folder.id
        ){

            return isLibrary
                ? String(
                    libraryName ||
                    "OpenSim Library"
                )
                : "Inventory";
        }


        return isLibrary
            ? libraryFolderPath(
                folder.id
            )
            : folderPath(
                folder.id
            );
    }


    function fs11ShowFolderMenu(
        event,
        folder,
        isLibrary
    ){

        fs5CloseContextMenu();


        /*
         * Make the right-clicked folder the active details view.
         */
        showFolderDetails(
            folder,
            isLibrary
        );


        const menu =
            document.createElement(
                "div"
            );


        menu.className =
            "fs5-context-menu";


        menu.setAttribute(
            "role",
            "menu"
        );


        const title =
            document.createElement(
                "div"
            );


        title.className =
            "fs5-context-title";


        title.textContent =
            String(
                folder.name ||
                "Unnamed Folder"
            );


        menu.appendChild(
            title
        );


        /*
         * VIEW FOLDER DETAILS
         */
        menu.appendChild(
            fs5CreateMenuButton(
                "View Folder Details",
                ICONS.folder,
                async function(){

                    showFolderDetails(
                        folder,
                        isLibrary
                    );
                },
                true
            )
        );


        fs5AddSeparator(
            menu
        );


        /*
         * COPY FOLDER NAME
         */
        menu.appendChild(
            fs5CreateMenuButton(
                "Copy Folder Name",
                ICONS.details,
                async function(){

                    await fs5CopyText(
                        folder.name,
                        "FOLDER NAME"
                    );
                },
                Boolean(
                    folder.name
                )
            )
        );


        /*
         * COPY FOLDER UUID
         */
        menu.appendChild(
            fs5CreateMenuButton(
                "Copy Folder UUID",
                ICONS.folder,
                async function(){

                    await fs5CopyText(
                        folder.id,
                        "FOLDER UUID"
                    );
                },
                Boolean(
                    folder.id
                )
            )
        );


        /*
         * COPY PARENT UUID
         */
        menu.appendChild(
            fs5CreateMenuButton(
                "Copy Parent UUID",
                ICONS.link,
                async function(){

                    await fs5CopyText(
                        folder.parent,
                        "PARENT UUID"
                    );
                },
                Boolean(
                    folder.parent
                )
            )
        );


        /*
         * COPY FULL FOLDER PATH
         */
        menu.appendChild(
            fs5CreateMenuButton(
                "Copy Folder Path",
                ICONS.folder,
                async function(){

                    await fs5CopyText(
                        fs11FolderPath(
                            folder,
                            isLibrary
                        ),
                        "FOLDER PATH"
                    );
                },
                true
            )
        );


        document.body.appendChild(
            menu
        );


        fs5ContextMenu =
            menu;


        fs5PositionMenu(
            menu,
            event.clientX,
            event.clientY
        );


        const firstButton =
            menu.querySelector(
                ".fs5-context-item:not([disabled])"
            );


        if(firstButton){

            firstButton.focus(
                {
                    preventScroll:
                        true
                }
            );
        }
    }


    /* AUSTRALIA FIRESTORM FOLDER CONTEXT MENU V11 END */

    /* AUSTRALIA FIRESTORM FOLDER STATE V12 START */


    const FS12_FOLDER_STATE_KEY =
        "AUSTRALIA_FIRESTORM_SELECTED_FOLDER_V12";


    let fs12RestoreAttempt =
        0;


    let fs12RestoreTimer =
        null;


    function fs12ReadFolderState(){

        try{

            const raw =
                localStorage.getItem(
                    FS12_FOLDER_STATE_KEY
                );


            if(!raw){

                return null;
            }


            const state =
                JSON.parse(
                    raw
                );


            if(
                !state
                ||
                !state.id
                ||
                (
                    state.source !==
                    "personal"
                    &&
                    state.source !==
                    "library"
                )
            ){

                return null;
            }


            return state;
        }
        catch(error){

            return null;
        }
    }


    function fs12WriteFolderState(
        source,
        id
    ){

        try{

            localStorage.setItem(
                FS12_FOLDER_STATE_KEY,
                JSON.stringify({
                    source:
                        String(
                            source
                        ),

                    id:
                        String(
                            id
                        ).toLowerCase(),

                    savedAt:
                        Date.now()
                })
            );
        }
        catch(error){

        }
    }


    function fs12ClearFolderState(){

        try{

            localStorage.removeItem(
                FS12_FOLDER_STATE_KEY
            );
        }
        catch(error){

        }
    }


    function fs12ClearFolderHighlights(){

        if(!tree){

            return;
        }


        tree
        .querySelectorAll(
            ".fs2-folder-row.fs2-selected"
        )
        .forEach(
            row => {

                row.classList.remove(
                    "fs2-selected"
                );
            }
        );
    }


    function fs12HighlightFolder(
        row
    ){

        fs12ClearFolderHighlights();


        if(row){

            row.classList.add(
                "fs2-selected"
            );
        }
    }


    function fs12FolderSelectionFromRow(
        row
    ){

        if(!row){

            return null;
        }


        if(
            row.dataset
            &&
            row.dataset.libraryFolderId
        ){

            return {

                source:
                    "library",

                id:
                    String(
                        row.dataset.libraryFolderId
                    ).toLowerCase()
            };
        }


        if(
            row.dataset
            &&
            row.dataset.folderId
        ){

            return {

                source:
                    "personal",

                id:
                    String(
                        row.dataset.folderId
                    ).toLowerCase()
            };
        }


        return null;
    }


    function fs12FindFolderRow(
        state
    ){

        if(
            !tree
            ||
            !state
        ){

            return null;
        }


        const selector =
            state.source ===
            "library"
                ? ".fs2-folder-row[data-library-folder-id]"
                : ".fs2-folder-row[data-folder-id]";


        const rows =
            tree.querySelectorAll(
                selector
            );


        for(const row of rows){

            const value =
                state.source ===
                "library"
                    ? row.dataset.libraryFolderId
                    : row.dataset.folderId;


            if(
                String(
                    value ||
                    ""
                ).toLowerCase() ===
                String(
                    state.id ||
                    ""
                ).toLowerCase()
            ){

                return row;
            }
        }


        return null;
    }


    function fs12FolderObject(
        state
    ){

        if(
            !state
            ||
            !state.id
        ){

            return null;
        }


        const key =
            String(
                state.id
            ).toLowerCase();


        if(
            state.source ===
            "library"
        ){

            return (
                libraryFolderMap.get(
                    key
                )
                ||
                null
            );
        }


        return (
            folderMap.get(
                key
            )
            ||
            null
        );
    }


    function fs12RestoreTick(){

        clearTimeout(
            fs12RestoreTimer
        );


        const state =
            fs12ReadFolderState();


        if(!state){

            return;
        }


        fs12RestoreAttempt++;


        const row =
            fs12FindFolderRow(
                state
            );


        const folder =
            fs12FolderObject(
                state
            );


        if(
            row
            &&
            folder
        ){

            fs12HighlightFolder(
                row
            );


            showFolderDetails(
                folder,
                state.source ===
                "library"
            );


            fs12RestoreAttempt =
                0;


            return;
        }


        if(
            fs12RestoreAttempt <
            50
        ){

            fs12RestoreTimer =
                setTimeout(
                    fs12RestoreTick,
                    150
                );


            return;
        }


        fs12ClearFolderState();


        fs12RestoreAttempt =
            0;
    }


    function fs12InstallFolderState(){

        if(
            !tree
            ||
            !tree.isConnected
        ){

            setTimeout(
                fs12InstallFolderState,
                100
            );


            return;
        }


        if(
            tree.dataset.fs12FolderStateInstalled ===
            "1"
        ){

            return;
        }


        tree.dataset.fs12FolderStateInstalled =
            "1";


        function captureSelection(
            event
        ){

            /*
             * Capture is used because the existing row handlers
             * stop propagation.
             */

            const itemRow =
                event.target.closest(
                    ".fs2-item-row"
                );


            if(itemRow){

                fs12ClearFolderState();


                fs12ClearFolderHighlights();


                setTimeout(
                    fs6CaptureState,
                    120
                );


                return;
            }


            const folderRow =
                event.target.closest(
                    ".fs2-folder-row"
                );


            if(!folderRow){

                return;
            }


            const selected =
                fs12FolderSelectionFromRow(
                    folderRow
                );


            if(!selected){

                return;
            }


            fs12WriteFolderState(
                selected.source,
                selected.id
            );


            /*
             * Wait until V10 has cleared any previously
             * selected item and V6 can save selected=null.
             */
            setTimeout(
                () => {

                    fs12HighlightFolder(
                        folderRow
                    );


                    fs6CaptureState();
                },
                120
            );
        }


        tree.addEventListener(
            "click",
            captureSelection,
            true
        );


        tree.addEventListener(
            "contextmenu",
            captureSelection,
            true
        );


        fs12RestoreAttempt =
            0;


        fs12RestoreTimer =
            setTimeout(
                fs12RestoreTick,
                350
            );
    }


    setTimeout(
        fs12InstallFolderState,
        250
    );


    /* AUSTRALIA FIRESTORM FOLDER STATE V12 END */

    /* AUSTRALIA FIRESTORM ROOT DETAILS STATE V13 START */


    const FS13_ROOT_STATE_KEY =
        "AUSTRALIA_FIRESTORM_SELECTED_ROOT_V13";


    let fs13RestoreTimer =
        null;


    let fs13RestoreAttempt =
        0;


    function fs13RootKey(
        row
    ){

        if(!row){

            return "";
        }


        if(
            typeof fs6RowKey ===
            "function"
        ){

            const key =
                fs6RowKey(
                    row
                );


            if(
                key ===
                "R:inventory"
                ||
                key ===
                "R:library"
            ){

                return key;
            }
        }


        return "";
    }


    function fs13WriteRootState(
        key
    ){

        try{

            localStorage.setItem(
                FS13_ROOT_STATE_KEY,
                JSON.stringify({

                    key:
                        String(
                            key
                        ),

                    savedAt:
                        Date.now()
                })
            );
        }
        catch(error){

        }
    }


    function fs13ReadRootState(){

        try{

            const raw =
                localStorage.getItem(
                    FS13_ROOT_STATE_KEY
                );


            if(!raw){

                return null;
            }


            const state =
                JSON.parse(
                    raw
                );


            if(
                !state
                ||
                (
                    state.key !==
                    "R:inventory"
                    &&
                    state.key !==
                    "R:library"
                )
            ){

                return null;
            }


            return state;
        }
        catch(error){

            return null;
        }
    }


    function fs13ClearRootState(){

        try{

            localStorage.removeItem(
                FS13_ROOT_STATE_KEY
            );
        }
        catch(error){

        }
    }


    function fs13ClearRootHighlights(){

        if(!tree){

            return;
        }


        tree
        .querySelectorAll(
            ".fs2-root-row.fs2-selected"
        )
        .forEach(
            row => {

                row.classList.remove(
                    "fs2-selected"
                );
            }
        );
    }


    function fs13ClearV6SelectedItem(){

        try{

            if(
                fs6SavedState
                &&
                typeof fs6SavedState ===
                "object"
            ){

                fs6SavedState.selected =
                    null;
            }


            const state =
                fs6ReadState();


            if(
                !state
                ||
                typeof state !==
                "object"
            ){

                return;
            }


            state.selected =
                null;


            state.savedAt =
                Date.now();


            fs6WriteState(
                state
            );
        }
        catch(error){

        }
    }


    function fs13FindRootRow(
        key
    ){

        if(!tree){

            return null;
        }


        const rows =
            tree.querySelectorAll(
                ".fs2-root-row"
            );


        for(
            const row of rows
        ){

            if(
                fs13RootKey(
                    row
                ) ===
                key
            ){

                return row;
            }
        }


        return null;
    }


    function fs13ShowRootDetails(
        key,
        row
    ){

        if(!detailBody){

            return;
        }


        if(selectedItemRow){

            selectedItemRow.classList.remove(
                "fs2-selected"
            );


            selectedItemRow =
                null;
        }


        fs12ClearFolderHighlights();


        fs13ClearRootHighlights();


        if(row){

            row.classList.add(
                "fs2-selected"
            );
        }


        detailBody.dataset.fs4Token =
            "";


        const isLibrary =
            key ===
            "R:library";


        const avatarContext =
            fs9AvatarContext();


        const foldersTotal =
            Number(
                isLibrary
                    ? (
                        librarySummary
                        &&
                        librarySummary.folders
                        ||
                        0
                    )
                    : (
                        summary
                        &&
                        summary.folders
                        ||
                        0
                    )
            );


        const itemsTotal =
            Number(
                isLibrary
                    ? (
                        librarySummary
                        &&
                        librarySummary.items
                        ||
                        0
                    )
                    : (
                        summary
                        &&
                        summary.items
                        ||
                        0
                    )
            );


        const rootName =
            isLibrary
                ? String(
                    libraryName ||
                    "OpenSim Library"
                )
                : "Inventory";


        const heading =
            document.getElementById(
                "fs2-detail-heading"
            );


        if(heading){

            heading.textContent =
                isLibrary
                    ? "OPENSIM LIBRARY ROOT"
                    : "INVENTORY ROOT";
        }


        detailBody.innerHTML =
            `
            <div class="fs4-owner-card">

                <img
                    class="fs4-owner-icon"
                    src="${isLibrary ? ICONS.inventory : "/Other/assets/icons/sentinel/avatar.png"}"
                    alt=""
                    aria-hidden="true"
                    draggable="false">

                <div>

                    <div
                        class="fs4-owner-label">
                        ${isLibrary ? "LIBRARY SOURCE" : "INVENTORY OWNER"}
                    </div>

                    <div
                        id="fs13-owner-title"
                        class="fs4-owner-title">
                    </div>

                    <div
                        id="fs13-owner-id"
                        class="fs4-owner-id">
                    </div>

                    <div
                        id="fs13-owner-totals"
                        class="fs4-owner-totals">
                    </div>

                </div>

            </div>


            <div class="fs2-detail-hero">

                <img
                    class="fs2-detail-hero-icon"
                    src="${ICONS.inventory}"
                    alt=""
                    aria-hidden="true"
                    draggable="false">

                <div>

                    <h3
                        id="fs13-root-name"
                        class="fs2-detail-name">
                    </h3>

                    <div class="fs2-detail-type">
                        INVENTORY ROOT
                    </div>

                    <div
                        id="fs13-root-source"
                        class="fs4-source">
                    </div>

                </div>

            </div>


            <div class="fs4-section">

                <div class="fs4-title">
                    ROOT INFORMATION
                </div>

                <div
                    id="fs13-root-info"
                    class="fs4-info">
                </div>

            </div>
            `;


        document
        .getElementById(
            "fs13-owner-title"
        ).textContent =
            isLibrary
                ? rootName
                : (
                    avatarContext.name ||
                    "Current Admin Inventory"
                );


        document
        .getElementById(
            "fs13-owner-id"
        ).textContent =
            isLibrary
                ? "READ ONLY LIBRARY"
                : (
                    avatarContext.level
                        ? (
                            "ADMIN LEVEL • " +
                            avatarContext.level
                        )
                        : "PERSONAL INVENTORY"
                );


        document
        .getElementById(
            "fs13-owner-totals"
        ).textContent =
            foldersTotal.toLocaleString()
            +
            " FOLDERS • "
            +
            itemsTotal.toLocaleString()
            +
            " ITEMS";


        document
        .getElementById(
            "fs13-root-name"
        ).textContent =
            rootName;


        document
        .getElementById(
            "fs13-root-source"
        ).textContent =
            isLibrary
                ? "OPENSIM LIBRARY"
                : "AVATAR INVENTORY";


        const info =
            document.getElementById(
                "fs13-root-info"
            );


        fs4InfoRow(
            info,
            "ROOT NAME",
            rootName
        );


        fs4InfoRow(
            info,
            "FOLDERS",
            foldersTotal.toLocaleString()
        );


        fs4InfoRow(
            info,
            "ITEMS",
            itemsTotal.toLocaleString()
        );


        fs4InfoRow(
            info,
            "SOURCE",
            isLibrary
                ? "OpenSim Library"
                : "Avatar Inventory"
        );


        fs4InfoRow(
            info,
            "ACCESS",
            isLibrary
                ? "READ ONLY"
                : "ADMIN BROWSER — READ ONLY"
        );


        statusLeft.textContent =
            rootName
            +
            " • "
            +
            foldersTotal.toLocaleString()
            +
            " FOLDERS • "
            +
            itemsTotal.toLocaleString()
            +
            " ITEMS";


        statusRight.textContent =
            isLibrary
                ? "LIBRARY ROOT"
                : "INVENTORY ROOT";
    }


    function fs13SelectRoot(
        key,
        row
    ){

        fs12ClearFolderState();


        fs13ClearV6SelectedItem();


        fs13WriteRootState(
            key
        );


        fs13ShowRootDetails(
            key,
            row
        );


        setTimeout(
            () => {

                fs6CaptureState();
            },
            120
        );
    }


    function fs13ShowRootMenu(
        event,
        key,
        row
    ){

        fs5CloseContextMenu();


        fs13SelectRoot(
            key,
            row
        );


        const isLibrary =
            key ===
            "R:library";


        const rootName =
            isLibrary
                ? String(
                    libraryName ||
                    "OpenSim Library"
                )
                : "Inventory";


        const sourceText =
            isLibrary
                ? "OpenSim Library"
                : "Avatar Inventory";


        const menu =
            document.createElement(
                "div"
            );


        menu.className =
            "fs5-context-menu";


        menu.setAttribute(
            "role",
            "menu"
        );


        const title =
            document.createElement(
                "div"
            );


        title.className =
            "fs5-context-title";


        title.textContent =
            rootName;


        menu.appendChild(
            title
        );


        menu.appendChild(
            fs5CreateMenuButton(
                "View Root Details",
                ICONS.inventory,
                async function(){

                    fs13SelectRoot(
                        key,
                        row
                    );
                },
                true
            )
        );


        fs5AddSeparator(
            menu
        );


        menu.appendChild(
            fs5CreateMenuButton(
                "Copy Root Name",
                ICONS.details,
                async function(){

                    await fs5CopyText(
                        rootName,
                        "ROOT NAME"
                    );
                },
                true
            )
        );


        menu.appendChild(
            fs5CreateMenuButton(
                "Copy Source",
                ICONS.inventory,
                async function(){

                    await fs5CopyText(
                        sourceText,
                        "SOURCE"
                    );
                },
                true
            )
        );


        document.body.appendChild(
            menu
        );


        fs5ContextMenu =
            menu;


        fs5PositionMenu(
            menu,
            event.clientX,
            event.clientY
        );


        const firstButton =
            menu.querySelector(
                ".fs5-context-item:not([disabled])"
            );


        if(firstButton){

            firstButton.focus(
                {
                    preventScroll:
                        true
                }
            );
        }
    }


    function fs13RestoreTick(){

        clearTimeout(
            fs13RestoreTimer
        );


        const state =
            fs13ReadRootState();


        if(!state){

            return;
        }


        fs13RestoreAttempt++;


        const row =
            fs13FindRootRow(
                state.key
            );


        if(row){

            fs13ShowRootDetails(
                state.key,
                row
            );


            fs13RestoreAttempt =
                0;


            return;
        }


        if(
            fs13RestoreAttempt <
            50
        ){

            fs13RestoreTimer =
                setTimeout(
                    fs13RestoreTick,
                    150
                );


            return;
        }


        fs13ClearRootState();


        fs13RestoreAttempt =
            0;
    }


    function fs13InstallRootDetails(){

        if(
            !tree
            ||
            !tree.isConnected
        ){

            setTimeout(
                fs13InstallRootDetails,
                100
            );


            return;
        }


        if(
            tree.dataset.fs13RootDetailsInstalled ===
            "1"
        ){

            return;
        }


        tree.dataset.fs13RootDetailsInstalled =
            "1";


        tree.addEventListener(
            "click",
            event => {

                const itemOrFolder =
                    event.target.closest(
                        ".fs2-item-row, .fs2-folder-row"
                    );


                if(itemOrFolder){

                    fs13ClearRootState();


                    fs13ClearRootHighlights();


                    return;
                }


                const row =
                    event.target.closest(
                        ".fs2-root-row"
                    );


                if(!row){

                    return;
                }


                const key =
                    fs13RootKey(
                        row
                    );


                if(!key){

                    return;
                }


                fs13SelectRoot(
                    key,
                    row
                );
            },
            true
        );


        tree.addEventListener(
            "contextmenu",
            event => {

                const row =
                    event.target.closest(
                        ".fs2-root-row"
                    );


                if(!row){

                    return;
                }


                const key =
                    fs13RootKey(
                        row
                    );


                if(!key){

                    return;
                }


                event.preventDefault();


                event.stopPropagation();


                fs13ShowRootMenu(
                    event,
                    key,
                    row
                );
            },
            true
        );


        fs13RestoreAttempt =
            0;


        fs13RestoreTimer =
            setTimeout(
                fs13RestoreTick,
                450
            );
    }


    setTimeout(
        fs13InstallRootDetails,
        300
    );


    /* AUSTRALIA FIRESTORM ROOT DETAILS STATE V13 END */

    

    /* ======================================================
       ITEM ROW
       ====================================================== */

    function createItemRow(
        item
    ){

        const meta =
            typeInfo(
                item
            );


        const row =
            document.createElement(
                "div"
            );


        row.className =
            "fs2-row fs2-item-row";

        /* AUSTRALIA FIRESTORM STATE ITEM TAG V6 START */

        row.dataset.fs6ItemId =
            String(
                item.id ||
                ""
            ).toLowerCase();


        row.dataset.fs6Source =
            fs4IsLibraryItem(
                item
            )
                ? "library"
                : "personal";

        /* AUSTRALIA FIRESTORM STATE ITEM TAG V6 END */
        /* AUSTRALIA FIRESTORM TYPE ITEM TAG V8 START */

        row.dataset.fs8Type =
            String(
                meta.key ||
                "inventory"
            );

        /* AUSTRALIA FIRESTORM TYPE ITEM TAG V8 END */



        row.tabIndex =
            0;


        const toggle =
            document.createElement(
                "span"
            );


        toggle.className =
            "fs2-toggle";


        const icon =
            document.createElement(
                "img"
            );


        icon.className =
            "fs2-icon";


        icon.src =
            meta.icon;


        icon.alt =
            "";


        icon.draggable =
            false;


        const name =
            document.createElement(
                "span"
            );


        name.className =
            "fs2-name";


        name.textContent =
            String(
                item.name ||
                "Unnamed Item"
            );


        row.appendChild(
            toggle
        );


        row.appendChild(
            icon
        );


        row.appendChild(
            name
        );


        function selectItem(){

            if(
                selectedItemRow &&
                selectedItemRow !==
                row
            ){

                selectedItemRow.classList.remove(
                    "fs2-selected"
                );
            }


            selectedItemRow =
                row;


            row.classList.add(
                "fs2-selected"
            );


            showDetails(
                item
            );


            statusLeft.textContent =
                `${meta.label} • ${String(item.name || "Unnamed Item")}`;


            statusRight.textContent =
                "SELECTED";
        }


        row.addEventListener(
            "click",
            event => {

                event.stopPropagation();

                selectItem();
            }
        );

        /* AUSTRALIA FIRESTORM CONTEXT LISTENER V5 START */

        row.addEventListener(
            "contextmenu",
            event => {

                event.preventDefault();

                event.stopPropagation();


                fs5ShowItemMenu(
                    event,
                    item,
                    selectItem
                );
            }
        );

        /* AUSTRALIA FIRESTORM CONTEXT LISTENER V5 END */



        row.addEventListener(
            "keydown",
            event => {

                if(
                    event.key ===
                    "Enter" ||
                    event.key ===
                    " "
                ){

                    event.preventDefault();

                    selectItem();
                }
            }
        );


        return row;
    }



    /* ======================================================
       FOLDER NODE
       ====================================================== */

    function fsCleanTreeTotal(folder,seen){seen=seen||new Set();const id=String(folder&&folder.id||"").toLowerCase();if(id&&seen.has(id)){return 0;}if(id){seen.add(id);}let total=Math.max(0,Number(folder&&folder.itemCount||0));const children=Array.isArray(folder&&folder.children)?folder.children:[];children.forEach(function(child){total+=fsCleanTreeTotal(child,seen);});return total;} function createFolderNode(
        folder
    ){

        const node =
            document.createElement(
                "div"
            );


        node.className =
            "fs2-node";


        const row =
            document.createElement(
                "div"
            );


        row.className =
            "fs2-row fs2-folder-row";


        row.tabIndex =
            0;


        row.dataset.folderId =
            folder.id;


        const canOpen =
            (
                folder.children.length >
                0
                ||
                folder.itemCount >
                0
            );


        const toggle =
            document.createElement(
                "span"
            );


        toggle.className =
            "fs2-toggle";


        toggle.textContent =
            canOpen
                ? "›"
                : "";


        const icon =
            document.createElement(
                "img"
            );


        icon.className =
            "fs2-icon";


        icon.src =
            ICONS.folder;


        icon.alt =
            "";


        icon.draggable =
            false;


        const name =
            document.createElement(
                "span"
            );


        name.className =
            "fs2-name";


        name.textContent=folder.name;const cleanCount=document.createElement("span");cleanCount.className="fs2-clean-count";const cleanTotal=fsCleanTreeTotal(folder,new Set());cleanCount.textContent=cleanTotal===0&&folder.children.length===0?"(0) EMPTY":"("+cleanTotal.toLocaleString()+")";if(cleanTotal===0&&folder.children.length===0){row.classList.add("fs2-clean-empty");}


        row.appendChild(
            toggle
        );


        row.appendChild(
            icon
        );


        row.appendChild(name);row.appendChild(cleanCount);


        const children =
            document.createElement(
                "div"
            );


        children.className =
            "fs2-children fs2-closed";


        folder.children.forEach(
            child => {

                children.appendChild(
                    createFolderNode(
                        child
                    )
                );
            }
        );


        node.appendChild(
            row
        );


        node.appendChild(
            children
        );


        async function setOpen(
            open
        ){

            if(
                !canOpen
            ){

                return;
            }


            if(
                open
            ){

                children.classList.remove(
                    "fs2-closed"
                );


                toggle.textContent =
                    "⌄";


                expanded.add(
                    folder.id
                );


                if(
                    folder.itemCount >
                    0
                    &&
                    children.dataset.itemsLoaded !==
                    "1"
                ){

                    const loading =
                        document.createElement(
                            "div"
                        );


                    loading.className =
                        "fs2-message";


                    loading.textContent =
                        "Loading folder contents...";


                    children.appendChild(
                        loading
                    );


                    try{

                        const items =
                            await loadFolderItems(
                                folder
                            );


                        loading.remove();


                        items.forEach(
                            item => {

                                children.appendChild(
                                    createItemRow(
                                        item
                                    )
                                );
                            }
                        );


                        children.dataset.itemsLoaded =
                            "1";
                    }
                    catch(error){

                        loading.textContent =
                            error.message;
                    }
                }
            }
            else{

                children.classList.add(
                    "fs2-closed"
                );


                toggle.textContent =
                    "›";


                expanded.delete(
                    folder.id
                );
            }
        }


        row._fs2SetOpen =
            setOpen;


        row.addEventListener(
            "click",
            async event => {

                event.stopPropagation();


                showFolderDetails(
                    folder,
                    false
                );

                if(
                    !canOpen
                ){

                    return;
                }


                await setOpen(
                    children.classList.contains(
                        "fs2-closed"
                    )
                );
            }
        );


        /* AUSTRALIA FIRESTORM FOLDER CONTEXT LISTENER V11 PERSONAL START */

        row.addEventListener(
            "contextmenu",
            event => {

                event.preventDefault();
                event.stopPropagation();


                fs11ShowFolderMenu(
                    event,
                    folder,
                    false
                );
            }
        );

        /* AUSTRALIA FIRESTORM FOLDER CONTEXT LISTENER V11 PERSONAL END */

        row.addEventListener(
            "keydown",
            async event => {

                if(
                    event.key ===
                    "Enter" ||
                    event.key ===
                    " "
                ){

                    event.preventDefault();

                    row.click();
                }


                if(
                    event.key ===
                    "ArrowRight"
                ){

                    event.preventDefault();

                    await setOpen(
                        true
                    );
                }


                if(
                    event.key ===
                    "ArrowLeft"
                ){

                    event.preventDefault();

                    await setOpen(
                        false
                    );
                }
            }
        );


        return node;
    }

    /* ======================================================
       REAL OPENSIM LIBRARY MODEL
       ====================================================== */

    function buildLibraryModel(
        source
    ){

        libraryFolderMap =
            new Map();


        libraryRoots =
            [];


        source.forEach(
            raw => {

                const folder = {

                    id:
                        String(
                            raw.id ||
                            ""
                        ),

                    parent:
                        String(
                            raw.parent ||
                            ""
                        ),

                    name:
                        String(
                            raw.name ||
                            "Unnamed Library Folder"
                        ),

                    type:
                        raw.type,

                    itemCount:
                        Number(
                            raw.itemCount ||
                            0
                        ),

                    children:
                        []

                };


                libraryFolderMap.set(
                    folder.id.toLowerCase(),
                    folder
                );
            }
        );


        libraryFolderMap.forEach(
            folder => {

                const parent =
                    libraryFolderMap.get(
                        folder.parent.toLowerCase()
                    );


                if(
                    parent &&
                    parent.id !==
                    folder.id
                ){

                    parent.children.push(
                        folder
                    );
                }
                else{

                    libraryRoots.push(
                        folder
                    );
                }
            }
        );


        function sortLibrary(
            list
        ){

            list.sort(
                (
                    a,
                    b
                ) =>
                    a.name.localeCompare(
                        b.name,
                        undefined,
                        {
                            sensitivity:
                                "base"
                        }
                    )
            );


            list.forEach(
                folder =>
                    sortLibrary(
                        folder.children
                    )
            );
        }


        sortLibrary(
            libraryRoots
        );
    }



    function libraryFolderPath(
        folderId
    ){

        let current =
            libraryFolderMap.get(
                String(
                    folderId ||
                    ""
                ).toLowerCase()
            );


        const parts =
            [];


        const seen =
            new Set();


        while(
            current &&
            !seen.has(
                current.id
            )
        ){

            seen.add(
                current.id
            );


            parts.unshift(
                current.name
            );


            current =
                libraryFolderMap.get(
                    String(
                        current.parent ||
                        ""
                    ).toLowerCase()
                ) ||
                null;
        }


        if(
            parts.length ===
            0
        ){

            return libraryName;
        }


        if(
            parts[0].toLowerCase() !==
            libraryName.toLowerCase()
        ){

            parts.unshift(
                libraryName
            );
        }


        return parts.join(
            " / "
        );
    }



    async function loadLibraryFolderItems(
        folder
    ){

        const key =
            folder.id.toLowerCase();


        if(
            libraryItemCache.has(
                key
            )
        ){

            return libraryItemCache.get(
                key
            );
        }


        const response =
            await libraryApi(
                "items",
                {
                    folder:
                        folder.id
                }
            );


        const items =
            Array.isArray(
                response.items
            )
                ? response.items
                : [];


        items.sort(
            (
                a,
                b
            ) =>
                String(
                    a.name ||
                    ""
                ).localeCompare(
                    String(
                        b.name ||
                        ""
                    ),
                    undefined,
                    {
                        sensitivity:
                            "base"
                    }
                )
        );


        libraryItemCache.set(
            key,
            items
        );


        return items;
    }



    function createLibraryFolderNode(
        folder
    ){

        const node =
            document.createElement(
                "div"
            );


        node.className =
            "fs2-node";


        const row =
            document.createElement(
                "div"
            );


        row.className =
            "fs2-row fs2-folder-row";


        row.tabIndex =
            0;


        row.dataset.libraryFolderId =
            folder.id;


        const canOpen =
            (
                folder.children.length >
                0
                ||
                folder.itemCount >
                0
            );


        const toggle =
            document.createElement(
                "span"
            );


        toggle.className =
            "fs2-toggle";


        toggle.textContent =
            canOpen
                ? "›"
                : "";


        const icon =
            document.createElement(
                "img"
            );


        icon.className =
            "fs2-icon";


        icon.src =
            ICONS.folder;


        icon.alt =
            "";


        icon.draggable =
            false;


        const name =
            document.createElement(
                "span"
            );


        name.className =
            "fs2-name";


        name.textContent=folder.name;const cleanCount=document.createElement("span");cleanCount.className="fs2-clean-count";const cleanTotal=fsCleanTreeTotal(folder,new Set());cleanCount.textContent=cleanTotal===0&&folder.children.length===0?"(0) EMPTY":"("+cleanTotal.toLocaleString()+")";if(cleanTotal===0&&folder.children.length===0){row.classList.add("fs2-clean-empty");}


        row.appendChild(
            toggle
        );


        row.appendChild(
            icon
        );


        row.appendChild(name);row.appendChild(cleanCount);


        const children =
            document.createElement(
                "div"
            );


        children.className =
            "fs2-children fs2-closed";


        folder.children.forEach(
            child => {

                children.appendChild(
                    createLibraryFolderNode(
                        child
                    )
                );
            }
        );


        node.appendChild(
            row
        );


        node.appendChild(
            children
        );


        async function setOpen(
            open
        ){

            if(
                !canOpen
            ){

                return;
            }


            if(
                open
            ){

                children.classList.remove(
                    "fs2-closed"
                );


                toggle.textContent =
                    "⌄";


                if(
                    folder.itemCount >
                    0
                    &&
                    children.dataset.itemsLoaded !==
                    "1"
                ){

                    const loading =
                        document.createElement(
                            "div"
                        );


                    loading.className =
                        "fs2-message";


                    loading.textContent =
                        "Loading Library contents...";


                    children.appendChild(
                        loading
                    );


                    try{

                        const items =
                            await loadLibraryFolderItems(
                                folder
                            );


                        loading.remove();


                        items.forEach(
                            item => {

                                const libraryItem =
                                    Object.assign(
                                        {},
                                        item,
                                        {
                                            _library:
                                                true
                                        }
                                    );


                                children.appendChild(
                                    createItemRow(
                                        libraryItem
                                    )
                                );
                            }
                        );


                        children.dataset.itemsLoaded =
                            "1";
                    }
                    catch(error){

                        loading.textContent =
                            error.message;
                    }
                }
            }
            else{

                children.classList.add(
                    "fs2-closed"
                );


                toggle.textContent =
                    "›";
            }
        }


        row._fs2LibrarySetOpen =
            setOpen;


        row.addEventListener(
            "click",
            async event => {

                event.stopPropagation();


                showFolderDetails(
                    folder,
                    true
                );

                if(
                    !canOpen
                ){

                    return;
                }


                await setOpen(
                    children.classList.contains(
                        "fs2-closed"
                    )
                );
            }
        );


        /* AUSTRALIA FIRESTORM FOLDER CONTEXT LISTENER V11 LIBRARY START */

        row.addEventListener(
            "contextmenu",
            event => {

                event.preventDefault();
                event.stopPropagation();


                fs11ShowFolderMenu(
                    event,
                    folder,
                    true
                );
            }
        );

        /* AUSTRALIA FIRESTORM FOLDER CONTEXT LISTENER V11 LIBRARY END */

        row.addEventListener(
            "keydown",
            async event => {

                if(
                    event.key ===
                    "Enter" ||
                    event.key ===
                    " "
                ){

                    event.preventDefault();

                    row.click();
                }


                if(
                    event.key ===
                    "ArrowRight"
                ){

                    event.preventDefault();

                    await setOpen(
                        true
                    );
                }


                if(
                    event.key ===
                    "ArrowLeft"
                ){

                    event.preventDefault();

                    await setOpen(
                        false
                    );
                }
            }
        );


        return node;
    }



    function renderLibraryRoot(){

        const node =
            document.createElement(
                "div"
            );


        node.className =
            "fs2-node";


        const row =
            document.createElement(
                "div"
            );


        row.className =
            "fs2-row fs2-root-row";


        row.tabIndex =
            0;


        const toggle =
            document.createElement(
                "span"
            );


        toggle.className =
            "fs2-toggle";


        toggle.textContent =
            (
                libraryRoots.length >
                0
                ||
                libraryError
            )
                ? "›"
                : "";


        const icon =
            document.createElement(
                "img"
            );


        icon.className =
            "fs2-icon";


        icon.src =
            ICONS.inventory;


        icon.alt =
            "";


        icon.draggable =
            false;


        const name =
            document.createElement(
                "span"
            );


        name.className =
            "fs2-name";


        name.textContent =
            libraryName;


        row.appendChild(
            toggle
        );


        row.appendChild(
            icon
        );


        row.appendChild(name);const cleanRootCount=document.createElement("span");cleanRootCount.className="fs2-clean-count";cleanRootCount.textContent="("+Number(librarySummary&&librarySummary.items||0).toLocaleString()+")";row.appendChild(cleanRootCount);


        const children =
            document.createElement(
                "div"
            );


        children.className =
            "fs2-children fs2-closed";


        if(
            libraryError
        ){

            const failed =
                document.createElement(
                    "div"
                );


            failed.className =
                "fs2-message";


            failed.textContent =
                libraryError;


            children.appendChild(
                failed
            );
        }
        else{

            libraryRoots.forEach(
                folder => {

                    children.appendChild(
                        createLibraryFolderNode(
                            folder
                        )
                    );
                }
            );


            if(
                libraryRoots.length ===
                0
            ){

                const empty =
                    document.createElement(
                        "div"
                    );


                empty.className =
                    "fs2-message";


                empty.textContent =
                    "No OpenSim Library folders were returned.";


                children.appendChild(
                    empty
                );
            }
        }


        node.appendChild(
            row
        );


        node.appendChild(
            children
        );


        row.addEventListener(
            "click",
            event => {

                event.stopPropagation();


                const closed =
                    children.classList.contains(
                        "fs2-closed"
                    );


                if(
                    closed
                ){

                    children.classList.remove(
                        "fs2-closed"
                    );


                    toggle.textContent =
                        "⌄";
                }
                else{

                    children.classList.add(
                        "fs2-closed"
                    );


                    toggle.textContent =
                        "›";
                }
            }
        );


        row.addEventListener(
            "keydown",
            event => {

                if(
                    event.key ===
                    "Enter" ||
                    event.key ===
                    " "
                ){

                    event.preventDefault();

                    row.click();
                }
            }
        );


        return node;
    }



    /* ======================================================
       MAIN INVENTORY ROOT
       ====================================================== */

    async function renderTree(){

        tree.innerHTML =
            "";


        const inventoryNode =
            document.createElement(
                "div"
            );


        inventoryNode.className =
            "fs2-node";


        const inventoryRow =
            document.createElement(
                "div"
            );


        inventoryRow.className =
            "fs2-row fs2-root-row";


        inventoryRow.tabIndex =
            0;


        inventoryRow.innerHTML =
            `
            <span class="fs2-toggle">
                ⌄
            </span>

            <img
                class="fs2-icon"
                src="${ICONS.inventory}"
                alt=""
                aria-hidden="true"
                draggable="false">

            <span class="fs2-name">
                Inventory
            </span>
            `;


        const cleanInventoryCount=document.createElement("span");cleanInventoryCount.className="fs2-clean-count";cleanInventoryCount.textContent="("+Number(summary&&summary.items||0).toLocaleString()+")";inventoryRow.appendChild(cleanInventoryCount);const inventoryChildren =
            document.createElement(
                "div"
            );


        inventoryChildren.className =
            "fs2-children";


        let personalRoot =
            roots.find(
                folder =>
                    /^(my\s+)?inventory$/i.test(
                        folder.name.trim()
                    )
            ) ||
            null;


        let personalFolders =
            personalRoot
                ? personalRoot.children
                : roots;


        personalFolders =
            personalFolders
            .slice()
            .sort(
                folderSorter
            );


        personalFolders.forEach(
            folder => {

                inventoryChildren.appendChild(
                    createFolderNode(
                        folder
                    )
                );
            }
        );


        inventoryNode.appendChild(
            inventoryRow
        );


        inventoryNode.appendChild(inventoryChildren); /* ag-inventory-root-toggle-v1 */ const inventoryToggle=inventoryRow.querySelector(":scope > .fs2-toggle"); inventoryRow.addEventListener("click",event=>{event.stopPropagation();const closed=inventoryChildren.classList.contains("fs2-closed");if(closed){inventoryChildren.classList.remove("fs2-closed");if(inventoryToggle){inventoryToggle.textContent="⌄";}}else{inventoryChildren.classList.add("fs2-closed");if(inventoryToggle){inventoryToggle.textContent="›";}}}); inventoryRow.addEventListener("keydown",event=>{if(event.key==="Enter"||event.key===" "){event.preventDefault();inventoryRow.click();}}); tree.appendChild(inventoryNode);


        if(
            personalRoot &&
            personalRoot.itemCount >
            0
        ){

            try{

                const rootItems =
                    await loadFolderItems(
                        personalRoot
                    );


                rootItems.forEach(
                    item => {

                        inventoryChildren.appendChild(
                            createItemRow(
                                item
                            )
                        );
                    }
                );
            }
            catch(error){

            }
        }


        /*
         * REAL OPENSIM LIBRARY
         *
         * Separate Firestorm-style root.
         * Never mixed into avatar inventory.
         */

        tree.appendChild(
            renderLibraryRoot()
        );


        const personalFoldersTotal =
            Number(
                summary &&
                summary.folders ||
                0
            );


        const personalItemsTotal =
            Number(
                summary &&
                summary.items ||
                0
            );


        const libraryFoldersTotal =
            Number(
                librarySummary &&
                librarySummary.folders ||
                0
            );


        const libraryItemsTotal =
            Number(
                librarySummary &&
                librarySummary.items ||
                0
            );


        statusLeft.textContent =
            `${(
                personalFoldersTotal +
                libraryFoldersTotal
            ).toLocaleString()} FOLDERS • ${(
                personalItemsTotal +
                libraryItemsTotal
            ).toLocaleString()} ITEMS`;


        statusRight.textContent =
            "READ ONLY";
    }





    /* ======================================================
       COLLAPSE
       ====================================================== */

    async function collapseAll(){

        expanded.clear();


        const rows =
            Array.from(
                tree.querySelectorAll(
                    ".fs2-folder-row"
                )
            );


        for(
            const row of rows
        ){

            if(
                typeof row._fs2SetOpen ===
                "function"
            ){

                await row._fs2SetOpen(
                    false
                );
            }
        }


        statusRight.textContent =
            "COLLAPSED";
    }



    /* ======================================================
       LOAD PERSONAL INVENTORY + REAL OPENSIM LIBRARY
       ====================================================== */

    function renderBrowserTypeTotals(totals){const shell=document.querySelector(".fs2-shell");if(!shell||!host){return;}let panel=document.getElementById("agBrowserInventoryTypePanel");if(!panel){panel=document.createElement("section");panel.id="agBrowserInventoryTypePanel";panel.className="rp-region ib-card ag-browser-type-panel";panel.innerHTML="<div class=\"ag-browser-type-heading\"><div class=\"ag-browser-type-title\">MAIN INVENTORY BY TYPE</div><div class=\"ag-browser-type-note\">CURRENT LIVE INVENTORY</div></div><div class=\"ag-browser-type-list\"></div>";host.insertBefore(panel,shell);}const list=panel.querySelector(".ag-browser-type-list");list.innerHTML="";const map={"6":["inventory.png","Object"],"0":["image.png","Texture"],"10":["tools.png","Script"],"7":["report.png","Notecard"],"1":["notification.png","Sound"],"3":["map.png","Landmark"],"20":["refresh.png","Animation"],"21":["chat.png","Gesture"],"5":["avatar.png","Clothing"],"13":["avatar.png","Body Part"],"2":["account.png","Calling Card"],"24":["link.png","Inventory Link"],"25":["link.png","Folder Link"]};const preferred=["6","0","10","7","1","3","20","21","5","13","2","24","25"];const raw=totals||{};let unknown=0;const rows=[];Object.keys(raw).forEach(function(key){const count=Number(raw[key]||0);if(count<=0){return;}if(map[key]){rows.push([key,count]);}else{unknown+=count;}});rows.sort(function(a,b){const ai=preferred.indexOf(a[0]);const bi=preferred.indexOf(b[0]);return (ai<0?999:ai)-(bi<0?999:bi);});if(unknown>0){rows.push(["unknown",unknown]);}if(rows.length===0){const empty=document.createElement("div");empty.className="ag-browser-type-empty";empty.textContent="No inventory items were returned.";list.appendChild(empty);return;}rows.forEach(function(row){const info=row[0]==="unknown"?["files.png","Inventory Item"]:map[row[0]];const chip=document.createElement("div");chip.className="ag-browser-type-chip";const icon=document.createElement("img");icon.className="ag-browser-type-icon";icon.src="/Other/assets/icons/sentinel/"+info[0];icon.alt="";icon.draggable=false;const label=document.createElement("span");label.className="ag-browser-type-label";label.textContent=info[1];const number=document.createElement("span");number.className="ag-browser-type-number";number.textContent=Number(row[1]).toLocaleString();chip.appendChild(icon);chip.appendChild(label);chip.appendChild(number);list.appendChild(chip);});}
    let inventoryLoading = false;
    async function loadInventory(){
        if(inventoryLoading) return;
        inventoryLoading = true;
        document.getElementById('ib-status').textContent = 'Loading inventory...';

        tree.innerHTML =
            `
            <div class="fs2-message">
                Loading inventory...
            </div>
            `;


        clearDetails();


        itemCache.clear();


        libraryItemCache.clear();

        /* AUSTRALIA FIRESTORM TYPE INDEX RESET V8.6 */

        if(
            typeof fs8ResetTypeIndex ===
            "function"
        ){
            fs8ResetTypeIndex();
        }


        librarySummary =
            null;


        libraryFolders =
            [];


        libraryRoots =
            [];


        libraryFolderMap =
            new Map();


        libraryName =
            "OpenSim Library";


        libraryError =
            null;


        try{

            const responses =
                await Promise.all([

                    api(
                        "summary"
                    ),

                    api(
                        "folders"
                    )

                ]);


            summary = responses[0]; renderBrowserTypeTotals(summary&&summary.typeTotals?summary.typeTotals:{});
            document.getElementById('ib-folder-total').textContent = Number(summary.folders || 0).toLocaleString();
            document.getElementById('ib-item-total').textContent = Number(summary.items || 0).toLocaleString();


            folders =
                Array.isArray(
                    responses[1].folders
                )
                    ? responses[1].folders
                    : [];


            buildModel();


            try{

                const libraryResponses =
                    await Promise.all([

                        libraryApi(
                            "summary"
                        ),

                        libraryApi(
                            "folders"
                        )

                    ]);


                librarySummary =
                    libraryResponses[0];


                libraryName =
                    String(
                        libraryResponses[0].libraryName ||
                        libraryResponses[1].libraryName ||
                        "OpenSim Library"
                    );


                libraryFolders =
                    Array.isArray(
                        libraryResponses[1].folders
                    )
                        ? libraryResponses[1].folders
                        : [];


                buildLibraryModel(
                    libraryFolders
                );
            }
            catch(error){

                libraryError =
                    error.message;


                console.error(
                    "OpenSim Library:",
                    error
                );
            }


            await renderTree();
            document.getElementById('ib-status').textContent = 'Inventory loaded. Select a folder or search for an item.';
        }
        catch(error){

            tree.innerHTML =
                `
                <div class="fs2-message">
                    Inventory could not be loaded.
                </div>
                `;


            document.getElementById('ib-status').textContent = error.message;
            statusLeft.textContent =
                error.message;


            statusRight.textContent =
                "ERROR";
        } finally {
            inventoryLoading = false;
        }
    }





    /* ======================================================
       START
       ====================================================== */

    async function start(){

        try{

            installLayout();

            fs8InstallTypeFilter();

            fs84SyncDropdownTheme();


            document
            .getElementById(
                "fs2-collapse"
            )
            .addEventListener(
                "click",
                collapseAll
            );


            document
            .getElementById(
                "fs2-refresh"
            )
            .addEventListener(
                "click",
                async () => {

                    fs71ClearSearchOnReload();


                    await loadInventory();
                }
            );




            tree.addEventListener(
                "keydown",
                event => {

                    if(
                        event.key !==
                        "ArrowDown"
                        &&
                        event.key !==
                        "ArrowUp"
                    ){

                        return;
                    }


                    const rows =
                        Array.from(
                            tree.querySelectorAll(
                                ".fs2-row"
                            )
                        ).filter(
                            row =>
                                row.offsetParent !==
                                null
                        );


                    const index =
                        rows.indexOf(
                            document.activeElement
                        );


                    if(
                        index <
                        0
                    ){

                        return;
                    }


                    event.preventDefault();


                    const next =
                        event.key ===
                        "ArrowDown"
                            ? Math.min(
                                rows.length - 1,
                                index + 1
                            )
                            : Math.max(
                                0,
                                index - 1
                            );


                    rows[
                        next
                    ].focus();
                }
            );


            await loadInventory();
        }
        catch(error){

            console.error(
                error
            );
        }
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

<!-- AUSTRALIA FIRESTORM SPLIT INVENTORY V2 END -->


<style id="ag-inventory-browser-real-clean-tree-v1">#fs2-tree{min-width:0!important;font-family:Arial,Helvetica,sans-serif!important;font-size:10px!important}.fs2-node{margin:2px 0!important}.fs2-folder-row,.fs2-root-row{width:100%!important;min-height:34px!important;height:auto!important;display:flex!important;align-items:center!important;gap:10px!important;padding:6px 8px!important;box-sizing:border-box!important;border:1px solid transparent!important;border-radius:0!important;background:transparent!important;box-shadow:none!important;color:#c8d1d5!important}.fs2-folder-row:hover,.fs2-root-row:hover,.fs2-folder-row.fs2-selected,.fs2-root-row.fs2-selected{border-color:rgba(231,172,49,.36)!important;background:rgba(224,164,39,.08)!important}.fs2-folder-row.fs2-selected,.fs2-root-row.fs2-selected{color:#ffc54a!important}.fs2-folder-row>.fs2-icon,.fs2-root-row>.fs2-icon{order:1!important;width:31px!important;height:29px!important;flex:0 0 31px!important;box-sizing:border-box!important;padding:3px!important;border:1px solid rgba(230,171,46,.40)!important;border-radius:5px!important;object-fit:contain!important}.fs2-folder-row>.fs2-name,.fs2-root-row>.fs2-name{order:2!important;min-width:0!important;flex:1 1 auto!important;overflow:hidden!important;text-overflow:ellipsis!important;white-space:nowrap!important;font-size:10px!important;font-weight:700!important;color:inherit!important}.fs2-root-row>.fs2-name{font-weight:900!important}.fs2-clean-count{order:3!important;flex:0 0 auto!important;margin-left:6px!important;padding:2px 6px!important;border:1px solid rgba(224,173,58,.22)!important;border-radius:999px!important;background:rgba(1,5,7,.48)!important;color:#a8b2b6!important;font-size:9px!important;font-weight:900!important;line-height:1.2!important;white-space:nowrap!important}.fs2-folder-row>.fs2-toggle,.fs2-root-row>.fs2-toggle{order:4!important;width:auto!important;height:auto!important;flex:0 0 auto!important;margin-left:0!important;padding-left:7px!important;color:#7f8d93!important;font-size:11px!important;line-height:1!important}.fs2-folder-row:hover>.fs2-toggle,.fs2-root-row:hover>.fs2-toggle{color:#ffc64a!important}.fs2-children{margin-left:14px!important;padding-left:5px!important;border-left:1px solid rgba(255,255,255,.065)!important}.fs2-clean-empty>.fs2-name{color:#7d898e!important}.fs2-clean-empty>.fs2-icon{opacity:.60!important}</style>
<style id="ag-inventory-browser-type-totals-v1">.ag-browser-type-panel{margin:0 0 12px 0!important;padding:12px 14px!important;box-sizing:border-box!important;min-height:0!important;height:auto!important;max-height:none!important;align-self:start!important}.ag-browser-type-heading{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}.ag-browser-type-title{color:#eeb23b;font-size:10px;font-weight:900;letter-spacing:.07em}.ag-browser-type-note{color:#758288;font-size:8px;font-weight:800;letter-spacing:.04em;text-align:right}.ag-browser-type-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(125px,1fr));gap:7px}.ag-browser-type-chip{display:grid;grid-template-columns:24px minmax(0,1fr) auto;align-items:center;gap:6px;min-height:35px;padding:6px 8px;box-sizing:border-box;border:1px solid rgba(225,176,62,.16);border-radius:8px;background:rgba(3,8,10,.58)}.ag-browser-type-icon{width:18px;height:18px;display:block;object-fit:contain}.ag-browser-type-label{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#cbd3d6;font-size:9px;font-weight:800}.ag-browser-type-number{color:#ffc64a;font-size:11px;font-weight:900;white-space:nowrap;text-shadow:0 1px 2px rgba(0,0,0,.95),0 0 6px rgba(255,198,74,.25)}.ag-browser-type-empty{grid-column:1/-1;padding:8px;color:#7d898e;font-size:9px}@media(max-width:700px){.ag-browser-type-heading{align-items:flex-start;flex-direction:column}}</style>
</body>

</html>
