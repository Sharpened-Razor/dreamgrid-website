<?php

require_once dirname(__DIR__, 2) . '/core/help-pictures.php';

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';
require_once dirname(__DIR__, 2) . '/core/icons.php';

$session = ag_require_login();

ag_no_cache();

$avatar =
    ag_avatar_name(
        $session
    );

$level =
    ag_user_level(
        $session
    );

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Help Centre</title>

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-panel-v1.css?v=8">

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-help-v1.css?v=13">



<style id="user-help-charcoal-theme-v1">

/*
 * ============================================================
 * AUSTRALIA HELP CENTRE
 * CHARCOAL / GOLD CONTROL CENTER THEME V1
 * ============================================================
 */

:root{
    --hc-black:#050807;
    --hc-black-2:#080c0b;
    --hc-charcoal:#0b0f0e;
    --hc-charcoal-2:#111615;
    --hc-charcoal-3:#171c1a;
    --hc-button:#242a29;

    --hc-gold:#d6a43b;
    --hc-gold-bright:#e0ae40;
    --hc-gold-border:rgba(214,164,59,.34);
    --hc-gold-soft:rgba(214,164,59,.16);

    --hc-white:#eef2f3;
    --hc-text:#cbd2d5;
    --hc-muted:#747f83;
    --hc-green:#72dc96;
}


/* ============================================================
   PAGE CANVAS
   ============================================================ */

html,
body{
    background:#050807 !important;
    background-image:
        linear-gradient(
            180deg,
            #0b0f0e 0%,
            #050807 100%
        ) !important;

    color:var(--hc-text) !important;
}


/* remove previous coloured/background artwork */

body::before,
body::after{
    background:none !important;
    background-image:none !important;
    filter:none !important;
}


/* ============================================================
   HELP CENTRE MAIN WORKSPACE
   ============================================================ */

.hc-shell,
.hc-layout,
.hc-workspace,
.hc-main,
.hc-content,
.hc-page,
.hc-wrap{
    background:transparent !important;
    background-image:none !important;
}


/* ============================================================
   LEFT HELP TOPICS PANEL
   ============================================================ */

.hc-sidebar,
.hc-topic-panel,
.hc-nav-panel,
.hc-topics{
    border:1px solid var(--hc-gold-border) !important;
    border-radius:6px !important;

    background:
        linear-gradient(
            180deg,
            #151918 0%,
            #0b0f0e 100%
        ) !important;

    background-image:
        linear-gradient(
            180deg,
            #151918 0%,
            #0b0f0e 100%
        ) !important;

    box-shadow:
        0 6px 16px rgba(0,0,0,.30) !important;
}


/* ============================================================
   MAIN HELP PANEL / ARTICLES / CARDS
   ============================================================ */

.hc-panel,
.hc-card,
.hc-home-card,
.hc-guide-card,
.hc-section,
.hc-article,
.hc-hero,
.hc-content-card,
.hc-help-card{
    border-color:var(--hc-gold-border) !important;

    background:
        linear-gradient(
            180deg,
            #111615 0%,
            #090d0c 100%
        ) !important;

    background-image:
        linear-gradient(
            180deg,
            #111615 0%,
            #090d0c 100%
        ) !important;

    box-shadow:
        0 5px 14px rgba(0,0,0,.28) !important;
}


/* broad Help Centre card fallback */

[class^="hc-"][class*="card"],
[class*=" hc-"][class*="card"],
[class^="hc-"][class*="panel"],
[class*=" hc-"][class*="panel"],
[class^="hc-"][class*="article"],
[class*=" hc-"][class*="article"]{
    border-color:var(--hc-gold-border) !important;

    background-color:#0b0f0e !important;
    background-image:
        linear-gradient(
            180deg,
            #111615 0%,
            #090d0c 100%
        ) !important;
}


/* ============================================================
   HEADERS
   ============================================================ */

.hc-header,
.hc-panel-header,
.hc-card-header,
.hc-section-header,
.hc-topic-header{
    border-color:rgba(255,255,255,.075) !important;

    background:
        linear-gradient(
            180deg,
            rgba(24,29,28,.96) 0%,
            rgba(14,18,17,.96) 100%
        ) !important;

    background-image:
        linear-gradient(
            180deg,
            rgba(24,29,28,.96) 0%,
            rgba(14,18,17,.96) 100%
        ) !important;
}


/* ============================================================
   TITLES / TEXT
   ============================================================ */

.hc-title,
.hc-card-title,
.hc-section-title,
.hc-topic-title,
.hc-heading,
[class^="hc-"] h1,
[class^="hc-"] h2,
[class^="hc-"] h3,
[class*=" hc-"] h1,
[class*=" hc-"] h2,
[class*=" hc-"] h3{
    color:var(--hc-white) !important;
}

.hc-label,
.hc-eyebrow,
.hc-kicker,
.hc-category,
.hc-group-title,
.hc-topic-label{
    color:var(--hc-gold) !important;
}

.hc-subtitle,
.hc-description,
.hc-copy,
.hc-meta,
.hc-card p,
.hc-section p,
.hc-article p,
[class^="hc-"] p,
[class*=" hc-"] p,
[class^="hc-"] li,
[class*=" hc-"] li{
    color:#9ba7aa !important;
}

[class^="hc-"] strong,
[class*=" hc-"] strong{
    color:#e2e6e6 !important;
}


/* ============================================================
   LEFT TOPIC BUTTONS
   ============================================================ */

.hc-topic,
.hc-topic-link,
.hc-nav-item,
.hc-nav-link{
    border-color:rgba(255,255,255,.08) !important;

    background:
        linear-gradient(
            180deg,
            #1d2321 0%,
            #0c100f 100%
        ) !important;

    background-image:
        linear-gradient(
            180deg,
            #1d2321 0%,
            #0c100f 100%
        ) !important;

    color:#e0e5e6 !important;
}

.hc-topic:hover,
.hc-topic-link:hover,
.hc-nav-item:hover,
.hc-nav-link:hover{
    border-color:rgba(214,164,59,.48) !important;

    background:
        linear-gradient(
            180deg,
            #282e2c 0%,
            #111614 100%
        ) !important;
}


/* selected topic */

.hc-topic.active,
.hc-topic-link.active,
.hc-nav-item.active,
.hc-nav-link.active,
.hc-topic[aria-current="page"],
.hc-topic-link[aria-current="page"]{
    border-color:rgba(214,164,59,.72) !important;

    background:
        linear-gradient(
            180deg,
            #342d1c 0%,
            #17140c 100%
        ) !important;

    background-image:
        linear-gradient(
            180deg,
            #342d1c 0%,
            #17140c 100%
        ) !important;

    color:#fff !important;

    box-shadow:
        inset 3px 0 0 var(--hc-gold-bright),
        0 3px 8px rgba(0,0,0,.34) !important;
}


/* ============================================================
   HELP ACTION BUTTONS
   ============================================================ */

.hc-btn,
a.hc-btn,
button.hc-btn{
    border:1px solid rgba(214,164,59,.52) !important;
    border-radius:4px !important;

    background:
        linear-gradient(
            180deg,
            #242a29 0%,
            #101413 55%,
            #080b0a 100%
        ) !important;

    background-image:
        linear-gradient(
            180deg,
            #242a29 0%,
            #101413 55%,
            #080b0a 100%
        ) !important;

    color:#eef2f3 !important;

    text-shadow:
        0 1px 1px #000 !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.08),
        0 3px 7px rgba(0,0,0,.30) !important;
}

.hc-btn:hover,
a.hc-btn:hover,
button.hc-btn:hover{
    border-color:var(--hc-gold-bright) !important;

    background:
        linear-gradient(
            180deg,
            #303735 0%,
            #151a18 55%,
            #0a0e0c 100%
        ) !important;

    color:#fff !important;
}


/* ============================================================
   HELP PICTURE AREAS
   ============================================================ */

.hc-picture,
.hc-image,
.hc-picture-frame,
.hc-screenshot{
    border-color:rgba(214,164,59,.22) !important;
    background:#070b0a !important;
}


/* ============================================================
   LINKS
   ============================================================ */

[class^="hc-"] a:not(.hc-btn),
[class*=" hc-"] a:not(.hc-btn){
    color:#d7aa45;
}

[class^="hc-"] a:not(.hc-btn):hover,
[class*=" hc-"] a:not(.hc-btn):hover{
    color:#f0c866;
}


/* ============================================================
   READY / STATUS
   ============================================================ */

.hc-ready,
.hc-status-ready{
    color:var(--hc-green) !important;
}


/* ============================================================
   SCROLLBARS
   ============================================================ */

::-webkit-scrollbar{
    width:8px;
    height:8px;
}

::-webkit-scrollbar-track{
    background:#070a09;
}

::-webkit-scrollbar-thumb{
    background:#4f4325;
    border-radius:6px;
}

::-webkit-scrollbar-thumb:hover{
    background:#6b582c;
}

</style>

</head>

<body>

<main class="cp-page hcp-page">


    <section class="hcp-intro">

        <div class="hcp-intro-left">

            <div class="hcp-intro-icon">

                <img
                    src="/Other/assets/icons/sentinel/help.png"
                    alt=""
                    aria-hidden="true">

            </div>

            <div>

                <div class="hcp-intro-kicker">
                    CONTROL CENTER
                </div>

                <div class="hcp-intro-title">
                    Help Centre
                </div>

            </div>

        </div>

        <div class="hcp-intro-note">
            GUIDES &amp; RESIDENT SUPPORT
        </div>

    </section>


    <section class="hcp-workspace">


        <aside class="hcp-nav">

            <div class="hcp-nav-head">

                <div class="hcp-nav-title">
                    Help Topics
                </div>

                <div class="hcp-nav-subtitle">
                    Choose a guide
                </div>

            </div>


            <div class="hcp-nav-body">


                <div class="hcp-group">

                    <div class="hcp-group-label">
                        START HERE
                    </div>

                    <button
                        class="hcp-topic active"
                        type="button"
                        data-help-topic="home">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/home.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            Help Centre Home
                        </span>

                    </button>


                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="getting-started">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/help.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            Getting Started
                        </span>

                    </button>


                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="firestorm-grid">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/add-region.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            Add This Grid to Firestorm
                        </span>

                    </button>


                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="login">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/key.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            Logging In
                        </span>

                    </button>


                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="teleport">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/map.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            Teleporting
                        </span>

                    </button>

                </div>


                <div class="hcp-group">

                    <div class="hcp-group-label">
                        ACCOUNT &amp; PROFILE
                    </div>

                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="edit-account">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/edit.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            Edit My Account
                        </span>

                    </button>


                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="my-profile">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/account.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            My Profile
                        </span>

                    </button>

                </div>


                <div class="hcp-group">

                    <div class="hcp-group-label">
                        INVENTORY &amp; BACKUPS
                    </div>

                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="inventory-browser">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/inventory.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            Inventory Browser
                        </span>

                    </button>


                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="clean-inventory">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/delete.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            Clean Inventory
                        </span>

                    </button>


                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="safety-iar">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/security.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            Safety Inventory IAR
                        </span>

                    </button>


                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="iar-backups">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/backup.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            IAR Backups
                        </span>

                    </button>


                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="delete-iar">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/delete.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            Delete an IAR Backup
                        </span>

                    </button>

                </div>


                <div class="hcp-group">

                    <div class="hcp-group-label">
                        GRID TOOLS
                    </div>

                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="grid-map">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/map.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            Grid Map
                        </span>

                    </button>

                </div>


                <div class="hcp-group">

                    <div class="hcp-group-label">
                        DASHBOARD FEATURES
                    </div>

                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="my-regions">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/region.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            My Regions
                        </span>

                    </button>


                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="offline-messages">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/email.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            Offline Messages
                        </span>

                    </button>


                    <button
                        class="hcp-topic"
                        type="button"
                        data-help-topic="linked-regions">

                        <span class="hcp-topic-icon">
                            <img
                                src="/Other/assets/icons/sentinel/link.png"
                                alt="">
                        </span>

                        <span class="hcp-topic-label">
                            Linked Regions
                        </span>

                    </button>

                </div>


            </div>

        </aside>


        <section class="hcp-content">

            <header class="hcp-content-head">

                <div>

                    <div class="hcp-content-kicker">
                        HELP CENTRE
                    </div>

                    <div class="hcp-content-title">
                        What do you need help with?
                    </div>

                </div>

                <div class="hcp-ready">
                    READY
                </div>

            </header>


            <div class="hcp-content-body">


                <section class="hcp-topic-panel active" data-help-panel="home">
<div class="hcp-welcome">

                    <h2>
                        <?=ag_grid_name_html()?> Control Center Help
                    </h2>

                    <p>
                        Find step-by-step guides for getting started,
                        setting up your viewer, logging in, managing
                        your account, inventory, regions, backups
                        and other grid features.
                    </p>

                </div>


                <div class="hcp-card-grid">


                    <article class="hcp-card">

                        <div class="hcp-card-top">

                            <div class="hcp-card-icon">
                                <img
                                    src="/Other/assets/icons/sentinel/help.png"
                                    alt="">
                            </div>

                            <div class="hcp-card-title">
                                Getting Started
                            </div>

                        </div>

                        <p>
                            Grid setup, Firestorm, login and first steps.
                        </p>

                    </article>


                    <article class="hcp-card">

                        <div class="hcp-card-top">

                            <div class="hcp-card-icon">
                                <img
                                    src="/Other/assets/icons/sentinel/account.png"
                                    alt="">
                            </div>

                            <div class="hcp-card-title">
                                Account &amp; Profile
                            </div>

                        </div>

                        <p>
                            Account settings and resident profile help.
                        </p>

                    </article>


                    <article class="hcp-card">

                        <div class="hcp-card-top">

                            <div class="hcp-card-icon">
                                <img
                                    src="/Other/assets/icons/sentinel/inventory.png"
                                    alt="">
                            </div>

                            <div class="hcp-card-title">
                                Inventory &amp; Backups
                            </div>

                        </div>

                        <p>
                            Inventory Browser, Clean Inventory and IAR tools.
                        </p>

                    </article>


                    <article class="hcp-card">

                        <div class="hcp-card-top">

                            <div class="hcp-card-icon">
                                <img
                                    src="/Other/assets/icons/sentinel/region.png"
                                    alt="">
                            </div>

                            <div class="hcp-card-title">
                                Grid Tools
                            </div>

                        </div>

                        <p>
                            Regions, maps, messages and linked destinations.
                        </p>

                    </article>


                </div>
</section>

<section
    class="hcp-topic-panel"
    data-help-panel="getting-started">
<div class="hc-kicker">
        START HERE
    </div>

    <h2>
        Getting Started
    </h2>

    <p>
        Follow the basic setup steps before logging into this grid
        for the first time.
    </p>

    <ol>
        <li>
            Download and install an OpenSim-compatible viewer such as Firestorm.
        </li>

        <li>
            Add <?=ag_grid_name_html()?> to the viewer's Grid Manager.
        </li>

        <li>
            Enter the First Name and Last Name of your avatar.
        </li>

        <li>
            Log in and begin at the grid welcome area.
        </li>
    </ol>


    <div class="hcp-gs-step-one">

        <div class="hcp-gs-step-head">

            

            <div>

                <div class="hcp-gs-step-label"><span class="hcp-step-kicker-large">STEP 1</span></div>

                <h3>
                    Download an OpenSim-Compatible Viewer
                </h3>

            </div>

        </div>


        <p>
            <?=ag_grid_name_html()?> runs on OpenSimulator. You need a viewer
            that supports OpenSim grids before you can connect.
        </p>

        <p>
            We recommend Firestorm. Download and install the
            Firestorm OpenSim Viewer before continuing to Step 2.
        </p>


        <div class="hcp-gs-actions">

            <a
                class="hc-btn hcp-primary-action"
                href="https://www.firestormviewer.org/downloads/"
                target="_blank"
                rel="noopener noreferrer">

                DOWNLOAD FIRESTORM OPENSIM VIEWER

            </a>


            <button
                type="button"
                class="hc-btn hc-btn-secondary"
                data-open-topic="firestorm-grid">

                NEXT: ADD GRID TO FIRESTORM

            </button>

        </div>

    </div>


    

</section>
<section
    class="hcp-topic-panel"
    data-help-panel="firestorm-grid">


    <!-- =====================================================
         NORMAL FIRESTORM GUIDE SCREEN
         ===================================================== -->

    <div
        class="hcp-firestorm-guide-main"
        data-firestorm-guide-main>


        <div class="hc-kicker">
            FIRESTORM / OPENSIM
        </div>

        <h2>
            Add This Grid to Firestorm
        </h2>


        <div class="hcp-firestorm-important">

            <p>
                This grid must be added to Firestorm's OpenSim
                Grid Manager before you can select it from the
                Firestorm login screen.
            </p>

        </div>


        <div class="hcp-grid-address-card">

            <div class="hcp-grid-address-label">
                THIS GRID'S LOGIN ADDRESS
            </div>


            <div class="hcp-grid-copy-row">

                <div
                    class="hcp-grid-copy-value ag-grid-login-url"
                    tabindex="0">
                    Loading grid login address...
                </div>

                <button
                    type="button"
                    class="hc-btn hcp-copy-grid-url"
                    data-copy-grid-url>
                    COPY TO CLIPBOARD
                </button>

            </div>


            <div class="hcp-grid-address-note">
                This address is loaded automatically from this grid.
            </div>

        </div>


        <div class="hcp-firestorm-steps">


            <div class="hcp-firestorm-step">

                <div class="hcp-firestorm-step-number">
                    1
                </div>

                <div class="hcp-firestorm-step-text">

                    <strong>Open Firestorm.</strong>

                    In the top-left corner of the Firestorm Viewer,
                    open the <strong>Viewer</strong> menu and choose
                    <strong>Preferences</strong>.

                    Scroll down the Preferences menu and open
                    <strong>OpenSim</strong>.

                </div>

            </div>


            <div class="hcp-firestorm-step">

                <div class="hcp-firestorm-step-number">
                    2
                </div>

                <div class="hcp-firestorm-step-text">

                    At the top of the OpenSim settings,
                    find the section for adding a grid.

                    Choose the option to
                    <strong>Add a new grid</strong>.

                </div>

            </div>


            <div class="hcp-firestorm-step">

                <div class="hcp-firestorm-step-number">
                    3
                </div>

                <div class="hcp-firestorm-step-text">

                    Enter this grid login address into the
                    <strong>Add new grid</strong> box:


                    <div class="hcp-firestorm-inline-address">

                        <div class="hcp-grid-copy-row">

                            <div
                                class="hcp-grid-copy-value ag-grid-login-url"
                                tabindex="0">
                                Loading grid login address...
                            </div>

                            <button
                                type="button"
                                class="hc-btn hcp-copy-grid-url"
                                data-copy-grid-url>
                                COPY TO CLIPBOARD
                            </button>

                        </div>

                    </div>


                    Then click <strong>Apply</strong>.

                </div>

            </div>


            <div class="hcp-firestorm-step">

                <div class="hcp-firestorm-step-number">
                    4
                </div>

                <div class="hcp-firestorm-step-text">

                    Firestorm should load the grid information.

                    Check that the grid has been added, then
                    click <strong>Apply</strong> or
                    <strong>OK</strong> to save the settings
                    and close Preferences.

                </div>

            </div>


            <div class="hcp-firestorm-step">

                <div class="hcp-firestorm-step-number">
                    5
                </div>

                <div class="hcp-firestorm-step-text">

                    Return to the Firestorm login screen and
                    select this grid from the grid list.

                    Enter your avatar's
                    <strong>First Name</strong>,
                    <strong>Last Name</strong>
                    and <strong>password</strong> that you created
                    when you registered your account on this website.

                </div>

            </div>


        </div>


        <!-- =================================================
             MAIN PAGE BUTTON ORDER
             ================================================= -->

        <div class="hcp-firestorm-actions">


            <button
                type="button"
                class="hc-btn hcp-picture-action"
                data-open-picture-screen="firestorm-picture-screen">

                VIEW PICTURE TUTORIAL

            </button>


            <button
                type="button"
                class="hc-btn hcp-primary-action"
                data-open-topic="login">

                NEXT: LOGGING IN

            </button>


            <button
                type="button"
                class="hc-btn hc-btn-secondary"
                data-open-topic="getting-started">

                BACK TO: GETTING STARTED

            </button>


        </div>


    </div>


    <!-- =====================================================
         SEPARATE FIRESTORM PICTURE TUTORIAL SCREEN
         ===================================================== -->

    <section
        class="hcp-picture-screen"
        id="firestorm-picture-screen"
        hidden>


        <div class="hc-kicker">
            PICTURE TUTORIAL
        </div>

        <h2>
            Add This Grid to Firestorm
        </h2>

        <p class="hcp-picture-intro">
            Follow the pictures below in order.
            Each picture will show one part of adding this
            grid to Firestorm.
        </p>


        <div class="hcp-picture-guide">


            <article class="hcp-picture-step">

                <div class="hcp-picture-step-title">
                    <span>1</span>
                    Open the Viewer Menu
                </div>

                <p>
                    Open Firestorm and click
                    <strong>Viewer</strong> in the top-left corner.
                </p>

                <img class="hcp-picture-image" src="<?=ag_h(ag_help_picture_url('firestorm-step-01'))?>" alt="Step 1 - Click and open Viewer in Firestorm" loading="lazy">

            </article>


            <article class="hcp-picture-step">

                <div class="hcp-picture-step-title">
                    <span>2</span>
                    Open Preferences
                </div>

                <p>
                    Choose <strong>Preferences</strong>
                    from the Viewer menu.
                </p>

                <img class="hcp-picture-image" src="<?=ag_h(ag_help_picture_url('firestorm-step-02'))?>" alt="Step 2 - Click and open Preferences in Firestorm" loading="lazy">

            </article>


            <article class="hcp-picture-step">

                <div class="hcp-picture-step-title">
                    <span>3</span>
                    Open OpenSim Settings
                </div>

                <p>
                    Scroll down the Preferences menu and
                    select <strong>OpenSim</strong>.
                </p>

                <img class="hcp-picture-image" src="<?=ag_h(ag_help_picture_url('firestorm-step-03'))?>" alt="Step 3 - Click and open OpenSim settings" loading="lazy">

            </article>


            <article class="hcp-picture-step">

                <div class="hcp-picture-step-title">
                    <span>4</span>
                    Find Add New Grid
                </div>

                <p>
                    At the top of the OpenSim settings,
                    locate the <strong>Add new grid</strong> box.
                </p>

                <img class="hcp-picture-image" src="<?=ag_h(ag_help_picture_url('firestorm-step-04'))?>" alt="Step 4 - Add a new grid" loading="lazy">

            </article>


            <article class="hcp-picture-step">

                <div class="hcp-picture-step-title">
                    <span>5</span>
                    Enter This Grid's Login Address
                </div>

                <p>
                    Paste the dynamic login address shown in
                    the Firestorm setup guide into
                    <strong>Add new grid</strong>.
                </p>

                <img class="hcp-picture-image" src="<?=ag_h(ag_help_picture_url('firestorm-step-05'))?>" alt="Step 5 - Enter the grid login address" loading="lazy">

            </article>


            <article class="hcp-picture-step">

                <div class="hcp-picture-step-title">
                    <span>6</span>
                    Apply and OK to Save the Grid
                </div>

                <p>
                    Click <strong>Apply</strong>, confirm the grid
                    information has loaded, then click <strong>OK</strong>
                    to save the grid settings.
                </p>

                <img class="hcp-picture-image" src="<?=ag_h(ag_help_picture_url('firestorm-step-06'))?>" alt="Step 6 - Click Apply and then OK to save the grid" loading="lazy">

            </article>


            


        </div>


        <div class="hcp-picture-screen-actions">

            <button
                type="button"
                class="hc-btn hcp-primary-action"
                data-close-picture-screen>

                BACK TO: FIRESTORM SETUP

            </button>

        </div>


    </section>


</section>
<section
    class="hcp-topic-panel"
    data-help-panel="login">


    <!-- =====================================================
         NORMAL LOGGING IN GUIDE
         ===================================================== -->

    <div
        class="hcp-login-guide-main"
        data-firestorm-guide-main>


        <div class="hc-kicker">
            ACCOUNT ACCESS
        </div>

        <h2>
            Logging In
        </h2>


        <div class="hcp-firestorm-important">

            <p>
                Use the avatar account you created on
                <?=ag_grid_name_html()?> to log in through
                your OpenSim-compatible viewer.
            </p>

        </div>


        <div class="hcp-grid-address-card">

            <div class="hcp-grid-address-label">
                THIS GRID'S LOGIN ADDRESS
            </div>

            <div class="hcp-grid-copy-row">

                <div
                    class="hcp-grid-copy-value ag-grid-login-url"
                    tabindex="0">
                    Loading grid login address...
                </div>

                <button
                    type="button"
                    class="hc-btn hcp-copy-grid-url"
                    data-copy-grid-url>
                    COPY TO CLIPBOARD
                </button>

            </div>

            <div class="hcp-grid-address-note">
                This address is loaded automatically from this grid.
            </div>

        </div>


        <div class="hcp-firestorm-steps">


            <div class="hcp-firestorm-step">

                <div class="hcp-firestorm-step-number">
                    1
                </div>

                <div class="hcp-firestorm-step-text">

                    On the Firestorm login screen,
                    select <strong><?=ag_grid_name_html()?></strong>
                    from the grid list.

                </div>

            </div>


            <div class="hcp-firestorm-step">

                <div class="hcp-firestorm-step-number">
                    2
                </div>

                <div class="hcp-firestorm-step-text">

                    Enter your avatar's
                    <strong>First Name</strong>
                    exactly as you entered it when creating
                    your account on this website.

                </div>

            </div>


            <div class="hcp-firestorm-step">

                <div class="hcp-firestorm-step-number">
                    3
                </div>

                <div class="hcp-firestorm-step-text">

                    Enter your avatar's
                    <strong>Last Name</strong>
                    exactly as it appears on your grid account.

                </div>

            </div>


            <div class="hcp-firestorm-step">

                <div class="hcp-firestorm-step-number">
                    4
                </div>

                <div class="hcp-firestorm-step-text">

                    Enter the <strong>password</strong>
                    you created when you registered your
                    avatar account on this website.

                </div>

            </div>


            <div class="hcp-firestorm-step">

                <div class="hcp-firestorm-step-number">
                    5
                </div>

                <div class="hcp-firestorm-step-text">

                    Check that
                    <strong><?=ag_grid_name_html()?></strong>
                    is selected, then click
                    <strong>Log In</strong>.

                </div>

            </div>


        </div>


        <!-- =================================================
             BUTTON ORDER
             ================================================= -->

        <div class="hcp-firestorm-actions">


            <button
                type="button"
                class="hc-btn hcp-picture-action"
                data-open-picture-screen="login-picture-screen">

                VIEW PICTURE TUTORIAL

            </button>


            <button
                type="button"
                class="hc-btn hcp-primary-action"
                data-open-topic="teleport">

                NEXT: TELEPORTING

            </button>


            <button
                type="button"
                class="hc-btn hc-btn-secondary"
                data-open-topic="firestorm-grid">

                BACK TO: ADD THIS GRID TO FIRESTORM

            </button>


        </div>


    </div>


    <!-- =====================================================
         SEPARATE LOGGING IN PICTURE TUTORIAL
         ===================================================== -->

    <section
        class="hcp-picture-screen"
        id="login-picture-screen"
        hidden>


        <div class="hc-kicker">
            PICTURE TUTORIAL
        </div>

        <h2>
            Logging In
        </h2>

        <p class="hcp-picture-intro">
            Follow these five pictures in order to log in to
            <?=ag_grid_name_html()?> using Firestorm.
        </p>


        <div class="hcp-picture-guide">


            <!-- STEP 1 -->

            <article class="hcp-picture-step">

                <div class="hcp-picture-step-title">

                    <span>1</span>

                    Select This Grid

                </div>

                <p>
                    On the Firestorm login screen, select
                    <strong><?=ag_grid_name_html()?></strong>
                    from the grid list.
                </p>

                                <?php
                $loginPicture01 =
                    ag_help_picture_url(
                        'login-step-01'
                    );
                ?>

                <?php if ($loginPicture01 !== ''): ?>

                    <img
                        class="hcp-picture-image"
                        src="<?=ag_h($loginPicture01)?>"
                        alt="Step 1 - Select this grid in Firestorm"
                        loading="lazy">

                <?php else: ?>

                    <div class="hcp-picture-placeholder">

                        PICTURE 1<br>
                        SELECT THIS GRID

                    </div>

                <?php endif; ?>

            </article>


            <!-- STEP 2 -->

            <article class="hcp-picture-step">

                <div class="hcp-picture-step-title">

                    <span>2</span>

                    Enter Your First Name

                </div>

                <p>
                    Enter the <strong>First Name</strong>
                    of the avatar account you created on
                    this website.
                </p>

                                <?php
                $loginPicture02 =
                    ag_help_picture_url(
                        'login-step-02'
                    );
                ?>

                <?php if ($loginPicture02 !== ''): ?>

                    <img
                        class="hcp-picture-image"
                        src="<?=ag_h($loginPicture02)?>"
                        alt="Step 2 - Enter your First Name in Firestorm"
                        loading="lazy">

                <?php else: ?>

                    <div class="hcp-picture-placeholder">

                        PICTURE 2<br>
                        ENTER FIRST NAME

                    </div>

                <?php endif; ?>

            </article>


            <!-- STEP 3 -->

            <article class="hcp-picture-step">

                <div class="hcp-picture-step-title">

                    <span>3</span>

                    Enter Your Last Name

                </div>

                <p>
                    Enter the <strong>Last Name</strong>
                    of your avatar account.
                </p>

                                <?php
                $loginPicture03 =
                    ag_help_picture_url(
                        'login-step-03'
                    );
                ?>

                <?php if ($loginPicture03 !== ''): ?>

                    <img
                        class="hcp-picture-image"
                        src="<?=ag_h($loginPicture03)?>"
                        alt="Step 3 - Enter your Last Name in Firestorm"
                        loading="lazy">

                <?php else: ?>

                    <div class="hcp-picture-placeholder">

                        PICTURE 3<br>
                        ENTER LAST NAME

                    </div>

                <?php endif; ?>

            </article>


            <!-- STEP 4 -->

            <article class="hcp-picture-step">

                <div class="hcp-picture-step-title">

                    <span>4</span>

                    Enter Your Password

                </div>

                <p>
                    Enter the <strong>password</strong>
                    you created for your avatar account
                    on this website.
                </p>

                                <?php
                $loginPicture04 =
                    ag_help_picture_url(
                        'login-step-04'
                    );
                ?>

                <?php if ($loginPicture04 !== ''): ?>

                    <img
                        class="hcp-picture-image"
                        src="<?=ag_h($loginPicture04)?>"
                        alt="Step 4 - Enter your password in Firestorm"
                        loading="lazy">

                <?php else: ?>

                    <div class="hcp-picture-placeholder">

                        PICTURE 4<br>
                        ENTER PASSWORD

                    </div>

                <?php endif; ?>

            </article>


            <!-- STEP 5 -->

            <article class="hcp-picture-step">

                <div class="hcp-picture-step-title">

                    <span>5</span>

                    Click Log In

                </div>

                <p>
                    Check that
                    <strong><?=ag_grid_name_html()?></strong>
                    is selected and then click
                    <strong>Log In</strong>.
                </p>

                                <?php
                $loginPicture05 =
                    ag_help_picture_url(
                        'login-step-05'
                    );
                ?>

                <?php if ($loginPicture05 !== ''): ?>

                    <img
                        class="hcp-picture-image"
                        src="<?=ag_h($loginPicture05)?>"
                        alt="Step 5 - Click Log In in Firestorm"
                        loading="lazy">

                <?php else: ?>

                    <div class="hcp-picture-placeholder">

                        PICTURE 5<br>
                        CLICK LOG IN

                    </div>

                <?php endif; ?>

            </article>


        </div>


        <div class="hcp-picture-screen-actions">

            <button
                type="button"
                class="hc-btn hcp-primary-action"
                data-close-picture-screen>

                BACK TO: LOGGING IN

            </button>

        </div>


    </section>


</section>
<section class="hcp-topic-panel" data-help-panel="teleport">
<div class="hc-kicker">
        FIRESTORM
    </div>

    <h2>
        Teleport to Another Region
    </h2>

    <p class="hc-lead">
        Use the World Map in your viewer to find
        another region on this grid.
    </p>


    <ol class="hc-steps">

        <li>
            Open the World Map in Firestorm.
        </li>

        <li>
            Search for the region name.
        </li>

        <li>
            Select the region from the map or search result.
        </li>

        <li>
            Choose Teleport.
        </li>

    </ol>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-teleport">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-teleport">

        <img
            src="<?=ag_h(ag_help_picture_display_url('topic-teleport'))?>"
            alt="Firestorm World Map teleport tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>WORLD MAP PICTURE TUTORIAL</strong><br><br>
                Picture managed in Admin → Help Centre Pictures
            </div>
        </div>

        <div class="hc-picture-caption">
            Firestorm World Map and teleport guide.
        </div>

    </div>
</section>

<section class="hcp-topic-panel" data-help-panel="edit-account">
<div class="hc-kicker">
        MY ACCOUNT
    </div>

    <h2>
        Edit My Account
    </h2>

    <p class="hc-lead">
        Change your account email address
        or password from My Account.
    </p>


    <ol class="hc-steps">

        <li>
            Sign in to this grid.
        </li>

        <li>
            Open <strong>MY ACCOUNT</strong>.
        </li>

        <li>
            Choose <strong>EDIT ACCOUNT</strong>.
        </li>

        <li>
            Change your email address or enter a new password.
        </li>

        <li>
            Enter your current grid password
            before selecting <strong>SAVE CHANGES</strong>.
        </li>

    </ol>


    <div class="hc-note">
        Your avatar name is read-only and cannot be changed
        from this page. If you are only changing your email,
        leave the New Password and Confirm New Password fields empty.
    </div>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-account">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-account">

        <img
            src="<?=ag_h(ag_help_picture_display_url('topic-edit-account'))?>"
            alt="My Account tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>MY ACCOUNT PICTURE TUTORIAL</strong><br><br>
                Picture managed in Admin → Help Centre Pictures
            </div>
        </div>

        <div class="hc-picture-caption">
            My Account and Edit Account picture guide.
        </div>

    </div>


    <div class="hc-actions">

        <a class="hc-btn" href="/Other/FreshUserDashboardExact/UserPages/account.php">

            OPEN MY ACCOUNT

        </a>

    </div>
</section>

<section class="hcp-topic-panel" data-help-panel="my-profile">
<div class="hc-kicker">
        FIRESTORM / OPENSIM PROFILE
    </div>

    <h2>
        My Profile
    </h2>

    <p class="hc-lead">
        My Profile displays the signed-in avatar's
        OpenSim profile.
    </p>


    <div class="hc-info-grid">

        <div class="hc-mini-card">
            <h3>Profile</h3>
            <p>
                About, Profile URL, Languages,
                Interests and supported publishing settings.
            </p>
        </div>

        <div class="hc-mini-card">
            <h3>First Life</h3>
            <p>
                Displays the avatar's First Life image
                and First Life text.
            </p>
        </div>

        <div class="hc-mini-card">
            <h3>Picks</h3>
            <p>
                Displays the avatar's actual
                OpenSim profile Picks.
            </p>
        </div>

        <div class="hc-mini-card">
            <h3>Classifieds</h3>
            <p>
                Displays the avatar's actual
                OpenSim Classifieds.
            </p>
        </div>

    </div>


    <div class="hc-note">
        Profile pictures remain controlled in-world through Firestorm.
        Use <strong>REFRESH FROM IN-WORLD</strong>
        after changing a profile picture.
    </div>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-profile">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-profile">

        <img
            src="<?=ag_h(ag_help_picture_display_url('topic-my-profile'))?>"
            alt="My Profile tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>MY PROFILE PICTURE TUTORIAL</strong><br><br>
                Picture managed in Admin → Help Centre Pictures
            </div>
        </div>

        <div class="hc-picture-caption">
            My Profile picture tutorial.
        </div>

    </div>


    <div class="hc-actions">

        <a class="hc-btn" href="/Other/FreshUserDashboardExact/UserPages/profile.php">

            OPEN MY PROFILE

        </a>

    </div>
</section>

<section class="hcp-topic-panel" data-help-panel="inventory-browser">
<div class="hc-kicker">
        MY INVENTORY
    </div>

    <h2>
        Inventory Browser
    </h2>

    <p class="hc-lead">
        Browse and search your inventory
        from the website.
    </p>


    <ol class="hc-steps">

        <li>
            Open <strong>MY INVENTORY</strong>
            from your User Dashboard.
        </li>

        <li>
            Choose
            <strong>OPEN INVENTORY BROWSER</strong>.
        </li>

        <li>
            Select a folder on the left.
        </li>

        <li>
            Inventory items from that folder
            appear in the centre.
        </li>

        <li>
            Select an item to view its details on the right.
        </li>

    </ol>


    <div class="hc-note">
        Inventory Browser is
        <strong>READ ONLY</strong>.
        It cannot delete, rename, move or modify
        your inventory.
    </div>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-inventory-browser">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-inventory-browser">

        <img
            src="<?=ag_h(ag_help_picture_display_url('topic-inventory-browser'))?>"
            alt="Inventory Browser tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>INVENTORY BROWSER PICTURE TUTORIAL</strong>
                <br><br>
                Picture managed in Admin → Help Centre Pictures
            </div>
        </div>

        <div class="hc-picture-caption">
            Inventory Browser picture guide.
        </div>

    </div>


    <div class="hc-actions">

        <a class="hc-btn" href="/Other/FreshUserDashboardExact/UserPages/inventory-browser.php">

            OPEN MY INVENTORY

        </a>

    </div>
</section>

<section class="hcp-topic-panel" data-help-panel="clean-inventory">
<div class="hc-kicker">
        INVENTORY TOOLS
    </div>

    <h2>
        Clean Inventory
    </h2>

    <p class="hc-lead">
        Clean Inventory is a separate staging workspace
        used to prepare a cleaner inventory structure.
    </p>


    <ol class="hc-steps">

        <li>
            Open your User Dashboard and choose
            <strong>MY INVENTORY</strong>.
        </li>

        <li>
            Choose
            <strong>OPEN CLEAN INVENTORY</strong>.
        </li>

        <li>
            Select Main Inventory folders or individual items
            and add them to Clean Inventory.
        </li>

        <li>
            Use <strong>+ NEW FOLDER</strong>
            to create folders inside the Clean workspace.
        </li>

        <li>
            Review the
            <strong>ITEMS LEFT OUT</strong>
            counter before continuing.
        </li>

    </ol>


    <div class="hc-note">
        Building or clearing Clean Inventory does not delete
        anything from your live Main Inventory.
    </div>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-clean-inventory">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-clean-inventory">

        <img
            src="<?=ag_h(ag_help_picture_display_url('topic-clean-inventory'))?>"
            alt="Clean Inventory tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>CLEAN INVENTORY PICTURE TUTORIAL</strong>
                <br><br>
                Picture managed in Admin → Help Centre Pictures
            </div>
        </div>

        <div class="hc-picture-caption">
            Clean Inventory picture guide.
        </div>

    </div>
</section>

<section class="hcp-topic-panel" data-help-panel="safety-iar">
<div class="hc-kicker">
        INVENTORY SAFETY
    </div>

    <h2>
        Safety Inventory IAR
    </h2>

    <p class="hc-lead">
        A verified Safety IAR is required
        before Clean Inventory can replace your current
        Main Inventory.
    </p>


    <ol class="hc-steps">

        <li>
            Open
            <strong>MY INVENTORY</strong>.
        </li>

        <li>
            Open
            <strong>CLEAN INVENTORY</strong>.
        </li>

        <li>
            Choose
            <strong>CREATE SAFETY IAR</strong>.
        </li>

        <li>
            Wait for
            <strong>SAFETY IAR VERIFIED</strong>.
        </li>

        <li>
            Download a copy to your own computer
            if you want an additional emergency backup.
        </li>

    </ol>


    <div class="hc-note">

        <span class="hc-warning">
            NO VERIFIED SAFETY IAR =
            NO INVENTORY REPLACEMENT.
        </span>

    </div>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-safety-iar">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-safety-iar">

        <img
            src="<?=ag_h(ag_help_picture_display_url('topic-safety-iar'))?>"
            alt="Safety IAR tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>SAFETY IAR PICTURE TUTORIAL</strong>
                <br><br>
                Picture managed in Admin → Help Centre Pictures
            </div>
        </div>

        <div class="hc-picture-caption">
            Safety Inventory IAR picture guide.
        </div>

    </div>
</section>

<section class="hcp-topic-panel" data-help-panel="iar-backups">
<div class="hc-kicker">
        INVENTORY BACKUPS
    </div>

    <h2>
        IAR Backups
    </h2>

    <p class="hc-lead">
        Create, download and manage normal inventory archives
        for your signed-in avatar.
    </p>


    <ol class="hc-steps">

        <li>
            Open the IAR Backups page from your User Dashboard.
        </li>

        <li>
            Select
            <strong>SAVE NEW IAR</strong>.
        </li>

        <li>
            Wait for the grid to create
            and verify the new archive.
        </li>

        <li>
            Use
            <strong>DOWNLOAD IAR</strong>
            to save an archive to your own computer.
        </li>

    </ol>


    <div class="hc-note">
        Each avatar can keep a maximum of
        <strong>2 normal IAR backups</strong>
        on this grid. If both slots are full,
        the new archive is created and verified
        before the oldest retained backup is replaced.
    </div>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-iar-backups">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-iar-backups">

        <img
            src="<?=ag_h(ag_help_picture_display_url('topic-iar-backups'))?>"
            alt="IAR Backups tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>IAR BACKUPS PICTURE TUTORIAL</strong>
                <br><br>
                Picture managed in Admin → Help Centre Pictures
            </div>
        </div>

        <div class="hc-picture-caption">
            IAR Backups picture guide.
        </div>

    </div>


    <div class="hc-actions">

        <a class="hc-btn" href="/Other/FreshUserDashboardExact/UserPages/iar-backups.php">

            OPEN IAR BACKUPS

        </a>

    </div>
</section>

<section class="hcp-topic-panel" data-help-panel="delete-iar">
<div class="hc-kicker">
        IAR BACKUPS
    </div>

    <h2>
        Delete an IAR Backup
    </h2>

    <p class="hc-lead">
        Remove one of your retained normal IAR backups
        from the grid server.
    </p>


    <ol class="hc-steps">

        <li>
            Open
            <strong>IAR BACKUPS</strong>.
        </li>

        <li>
            Find the saved archive you want to remove.
        </li>

        <li>
            Select
            <strong>DELETE IAR</strong>.
        </li>

        <li>
            Review the filename, date and size.
        </li>

        <li>
            Confirm the deletion.
        </li>

    </ol>


    <div class="hc-note">
        A copy that you already downloaded to your own computer
        is not affected. The separate Clean Inventory Safety IAR
        is also not affected.
    </div>
</section>

<section class="hcp-topic-panel" data-help-panel="grid-map">
<div class="hc-kicker">
        GRID TOOLS
    </div>

    <h2>
        Using the Grid Map
    </h2>

    <p class="hc-lead">
        The grid map lets residents view
        regions from the website.
    </p>


    <ol class="hc-steps">

        <li>
            Sign in to this grid.
        </li>

        <li>
            Open your User Dashboard.
        </li>

        <li>
            Choose
            <strong>GRID MAP</strong>.
        </li>

        <li>
            Use the map to view grid regions.
        </li>

    </ol>


    <button
        class="hc-btn hc-btn-secondary hc-picture-button"
        type="button"
        data-picture-toggle="pic-grid-map">

        VIEW PICTURE TUTORIAL

    </button>


    <div
        class="hc-picture"
        id="pic-grid-map">

        <img
            src="<?=ag_h(ag_help_picture_display_url('topic-grid-map'))?>"
            alt="Grid Map tutorial"
            onerror="
                this.style.display='none';
                this.nextElementSibling.style.display='flex';
            ">

        <div class="hc-picture-missing">
            <div>
                <strong>GRID MAP PICTURE TUTORIAL</strong>
                <br><br>
                Picture managed in Admin → Help Centre Pictures
            </div>
        </div>

        <div class="hc-picture-caption">
            Grid map picture tutorial.
        </div>

    </div>


    <div class="hc-actions">

        <a class="hc-btn" href="/Other/FreshUserDashboardExact/UserPages/map.php?ccfit=2">

            OPEN GRID MAP

        </a>

    </div>
</section>

<section class="hcp-topic-panel" data-help-panel="my-regions">
<div class="hc-kicker">
        MY GRID
    </div>

    <h2>
        My Regions
    </h2>

    <p class="hc-lead">
        View the Grid regions assigned to your avatar
        and open the controls available for each region.
    </p>

    <ol class="hc-steps">
        <li>Open <strong>My Regions</strong> from your Dashboard.</li>
        <li>Select the region you want to work with.</li>
        <li>Check its current status and region information.</li>
        <li>Use the available controls for that region.</li>
    </ol>

    <div class="hc-actions">

        <a class="hc-btn" href="/Other/FreshUserDashboardExact/UserPages/regions.php">

            OPEN MY REGIONS

        </a>

    </div>
</section>

<section class="hcp-topic-panel" data-help-panel="offline-messages">
<div class="hc-kicker">
        MESSAGES
    </div>

    <h2>
        Offline Messages
    </h2>

    <p class="hc-lead">
        Review messages currently waiting in OpenSim's
        offline message system for your avatar.
    </p>

    <ol class="hc-steps">
        <li>Open <strong>Offline Messages</strong> from your Dashboard.</li>
        <li>Review the messages waiting for your avatar.</li>
        <li>Use the available message controls when required.</li>
    </ol>

    <div class="hc-actions">

        <a class="hc-btn" href="/Other/FreshUserDashboardExact/UserPages/offline-messages.php">

            OPEN OFFLINE MESSAGES

        </a>

    </div>
</section>

<section class="hcp-topic-panel" data-help-panel="linked-regions">
<div class="hc-kicker">
        GRID TOOLS
    </div>

    <h2>
        Linked Regions
    </h2>

    <p class="hc-lead">
        Open the linked-region tools available to your
        Grid account.
    </p>

    <ol class="hc-steps">
        <li>Open <strong>Linked Regions</strong> from your Dashboard.</li>
        <li>Review the linked-region information shown for your account.</li>
        <li>Use the available tools as they become available.</li>
    </ol>

    <div class="hc-actions">

        <a class="hc-btn" href="/Other/user-linked-regions.php">

            OPEN LINKED REGIONS

        </a>

    </div>
</section>



                </div>
        </section>
    </section>
</main>

<script
    src="/Other/assets/js/control-center-help-v1.js?v=4">
</script>
    <script src="/Other/assets/js/control-center-help-copy-v1.js?v=1"></script>
    <script src="/Other/assets/js/control-center-help-pictures-v1.js?v=1"></script>
</body>

</html>


























