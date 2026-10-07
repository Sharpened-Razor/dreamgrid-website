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

$avatar =
    function_exists('ag_avatar_name')
        ? ag_avatar_name($session)
        : '';

$level =
    function_exists('ag_user_level')
        ? ag_user_level($session)
        : 0;

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Inventory</title>


<!--
    SAME BASE PANEL CSS AS PRIVATE REGIONS.

    DO NOT add the Control Center outer shell here.
    admin-home.php owns that.

    DO NOT add AUSTRALIA-BACKGROUND here.
    admin-home.php owns that.

    control-center-embedded-v1.css is injected
    automatically by admin-home.php.
-->

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-panel-v1.css?v=8">

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-regions-v5.css?v=6">


<style>

/* ==========================================================
   INVENTORY EMBEDDED PANEL
   ========================================================== */

.inventory-workspaces {

    display:grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap:18px;

    margin-top:18px;
}


.inventory-workspace {

    display:flex;

    flex-direction:column;

    min-width:0;

    min-height:315px;
}


.inventory-workspace
.rp-region-header {

    min-height:86px;
}


.inventory-workspace-name {

    font-size:18px;

    font-weight:900;

    line-height:1.1;
}


.inventory-workspace-sub {

    margin-top:5px;

    font-size:11px;

    opacity:.72;

    letter-spacing:.06em;
}


.inventory-live {

    display:flex;

    align-items:center;

    gap:7px;

    font-size:10px;

    font-weight:900;

    letter-spacing:.08em;
}


.inventory-live::before {

    content:"";

    width:8px;
    height:8px;

    border-radius:50%;

    background:#5ce28e;

    box-shadow:
        0 0 9px
        rgba(92,226,142,.9);
}


.inventory-body {

    flex:1;

    padding:20px;
}


.inventory-body p {

    margin:
        0
        0
        18px;

    line-height:1.55;
}


.inventory-details {

    display:grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap:10px;
}


.inventory-detail {

    padding:11px 12px;

    border:
        1px solid
        rgba(108,154,178,.22);

    border-radius:7px;

    background:
        rgba(0,0,0,.28);
}


.inventory-detail span {

    display:block;

    font-size:10px;

    font-weight:900;

    letter-spacing:.08em;
}


.inventory-detail small {

    display:block;

    margin-top:4px;

    opacity:.68;

    line-height:1.35;
}


.inventory-workspace
.rp-actions {

    width:100%;
}


.inventory-workspace
.rp-action {

    cursor:pointer;
}


.inventory-user {

    white-space:nowrap;
}


@media(max-width:1100px) {

    .inventory-workspaces {

        grid-template-columns:1fr;
    }
}


@media(max-width:600px) {

    .inventory-details {

        grid-template-columns:1fr;
    }
}

/* AUSTRALIA INVENTORY SAFETY WORKFLOW V1 START */ .inventory-workflow-panel{grid-column:1/-1;min-width:0;padding:0!important}.inventory-workflow-header{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:16px 18px;border-bottom:1px solid rgba(255,255,255,.10)}.inventory-workflow-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;padding:14px 18px 18px}.inventory-workflow-step{min-width:0;padding:13px 14px;border:1px solid rgba(255,255,255,.10);border-radius:6px;background:rgba(0,0,0,.16)}.inventory-workflow-step span{display:block;font-size:11px;font-weight:900;letter-spacing:.05em}.inventory-workflow-step strong{display:block;margin-top:5px;font-size:13px;color:#e5b83f}.inventory-workflow-step small{display:block;margin-top:6px;line-height:1.45;opacity:.72}@media(max-width:900px){.inventory-workflow-grid{grid-template-columns:1fr}} /* AUSTRALIA INVENTORY SAFETY WORKFLOW V1 END */</style>

</head>


<body>


<!--
     THIS IS THE CONTENT INSIDE ADMIN HOME'S RIGHT PANEL.
     NO OUTER SITE SHELL.
-->

<main class="cp-page inventory-page">


<!-- ========================================================
     CONTROL CENTER PANEL HEADER
     Same structure as Private Regions
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
                Inventory
            </div>

        </div>


    </div>


    <div class="cp-intro-note">
        Inventory Operations
    </div>


</section>



<!-- ========================================================
     INVENTORY OVERVIEW
     ======================================================== -->

<section class="rp-overview">


    <div class="rp-overview-heading">


        <div>

            <span class="rp-eyebrow">
                INVENTORY OPERATIONS
            </span>

            <h2>
                Inventory Management
            </h2>

            <p>
                Browse, maintain and protect your
                OpenSim inventory.
            </p>

        </div>


        <div class="rp-overview-tools">

            <button
                type="button"
                class="cp-button"
                onclick="window.location.reload()"
            >
                REFRESH
            </button>

        </div>


    </div>



    <div class="rp-summary">


        <div class="rp-summary-item">

            <span>
                INVENTORY
            </span>

            <strong class="online">
                LIVE
            </strong>

            <small>
                Grid service
            </small>

        </div>


        <div class="rp-summary-item">

            <span>
                BROWSER
            </span>

            <strong class="online">
                READY
            </strong>

            <small>
                Inventory tree
            </small>

        </div>


        <div class="rp-summary-item">

            <span>
                CLEANUP
            </span>

            <strong class="online">
                READY
            </strong>

            <small>
                Maintenance
            </small>

        </div>


        <div class="rp-summary-item">

            <span>
                IAR
            </span>

            <strong class="online">
                READY
            </strong>

            <small>
                Archive system
            </small>

        </div>


        <div class="rp-summary-item">

            <span>
                ACCOUNT
            </span>

            <strong class="inventory-user">
                <?=htmlspecialchars(
                    $avatar,
                    ENT_QUOTES,
                    'UTF-8'
                )?>
            </strong>

            <small>
                Level
                <?=htmlspecialchars(
                    (string)$level,
                    ENT_QUOTES,
                    'UTF-8'
                )?>
            </small>

        </div>


    </div>


</section>



<!-- ========================================================
     INVENTORY WORKSPACES
     ======================================================== -->

<section class="inventory-workspaces">



<!-- ========================================================
     BROWSE INVENTORY
     ======================================================== -->

<article class="rp-region inventory-workspace">


    <header class="rp-region-header">


        <div class="rp-region-identity">

            <div>

                <div class="inventory-workspace-name">
                    Inventory Browser
                </div>

                <div class="inventory-workspace-sub">
                    LIVE INVENTORY
                </div>

            </div>

        </div>


        <div class="inventory-live">
            AVAILABLE
        </div>


    </header>



    <div class="inventory-body">


        <p>
            Browse the current inventory folders
            and items stored for your avatar.
        </p>


        <div class="inventory-details">


            <div class="inventory-detail">

                <span>
                    FOLDERS
                </span>

                <small>
                    Browse inventory structure
                </small>

            </div>


            <div class="inventory-detail">

                <span>
                    ITEMS
                </span>

                <small>
                    View inventory assets
                </small>

            </div>


            <div class="inventory-detail">

                <span>
                    SEARCH
                </span>

                <small>
                    Find inventory content
                </small>

            </div>


            <div class="inventory-detail">

                <span>
                    LIVE DATA
                </span>

                <small>
                    Current Grid inventory
                </small>

            </div>


        </div>


    </div>



    <footer>


        <div class="rp-actions">


            <button
                type="button"
                class="rp-action start"
                onclick="
                    window.location.href =
                    '/Other/FreshUserDashboardExact/UserPages/inventory-browser.php';
                "
            >
                OPEN INVENTORY
            </button>


        </div>


    </footer>


</article>



<!-- ========================================================
     CLEAN INVENTORY
     ======================================================== -->

<article class="rp-region inventory-workspace">


    <header class="rp-region-header">


        <div class="rp-region-identity">

            <div>

                <div class="inventory-workspace-name">
                    Clean Inventory
                </div>

                <div class="inventory-workspace-sub">
                    INVENTORY MAINTENANCE
                </div>

            </div>

        </div>


        <div class="inventory-live">
            AVAILABLE
        </div>


    </header>



    <div class="inventory-body">


        <p>
            Maintain and organise the avatar
            inventory using the working cleanup
            engine.
        </p>


        <div class="inventory-details">


            <div class="inventory-detail">

                <span>
                    CLEANUP
                </span>

                <small>
                    Inventory maintenance
                </small>

            </div>


            <div class="inventory-detail">

                <span>
                    ORGANISE
                </span>

                <small>
                    Review inventory structure
                </small>

            </div>


            <div class="inventory-detail">

                <span>
                    FOLDERS
                </span>

                <small>
                    Maintain folder structure
                </small>

            </div>


            <div class="inventory-detail">

                <span>
                    SAFETY
                </span>

                <small>
                    Controlled operations
                </small>

            </div>


        </div>


    </div>



    <footer>


        <div class="rp-actions">


            <button
                type="button"
                class="rp-action restart"
                onclick="
                    window.location.href =
                    '/Other/FreshUserDashboardExact/UserPages/clean-inventory.php';
                "
            >
                OPEN CLEANUP
            </button>


        </div>


    </footer>


</article>
<!-- AUSTRALIA INVENTORY SAFETY WORKFLOW V1 START --><article class="rp-region inventory-workflow-panel"><header class="inventory-workflow-header"><div><div class="inventory-workspace-name">Inventory Safety &amp; Workflow</div><div class="inventory-workspace-sub">SAFE INVENTORY OPERATIONS</div></div><div class="inventory-live">GUIDED</div></header><div class="inventory-workflow-grid"><div class="inventory-workflow-step"><span>INVENTORY BROWSER</span><strong>READ ONLY</strong><small>Browse folders, items and inventory details safely. No inventory changes are made from the browser.</small></div><div class="inventory-workflow-step"><span>CLEAN INVENTORY</span><strong>BACKUP REQUIRED</strong><small>Create and verify an IAR safety backup before Clean Inventory changes are applied or saved.</small></div></div></article><!-- AUSTRALIA INVENTORY SAFETY WORKFLOW V1 END --></section>


</main>












</body>

</html>