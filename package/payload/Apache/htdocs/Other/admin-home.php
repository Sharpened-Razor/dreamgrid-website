<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/icons.php';
require_once __DIR__ . '/core/grid-branding.php';

$session = ag_require_admin();

$avatar = ag_avatar_name($session);
$level  = ag_user_level($session);

ag_no_cache();

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>
    <?=ag_grid_name_html()?> Control Center
</title>

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-shell-v1.css?v=1">


<link
    rel="stylesheet"
    href="/Other/site-design-display.php?slot=admin-control-center-background">
</head>


<body>


<div class="cc-shell">


<!-- ======================================================
     PERMANENT LEFT MENU
     ====================================================== -->

<aside class="cc-sidebar">


    <div class="cc-brand">

        <div class="cc-brand-icon">

            <?=ag_icon(
                'dashboard',
                null,
                'cc-brand-svg'
            )?>

        </div>


        <div>

            <div class="cc-grid-name">

                <?=ag_grid_name_html()?>

            </div>

            <div class="cc-brand-sub">
                CONTROL CENTER
            </div>

        </div>

    </div>



    <!-- ==================================================
         DASHBOARD
         ================================================== -->

    <section class="cc-menu-section">

        <div class="cc-menu-heading">
            DASHBOARD
        </div>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Account"
            data-view="account"
            data-src="/Other/panel-account.php">

            <span class="cc-nav-icon">
                <?=ag_icon('account',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                ACCOUNT
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Profile"
            data-view="profile"
            data-src="/Other/panel-profile.php">

            <span class="cc-nav-icon">
                <?=ag_icon('avatar',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                PROFILE
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Regions"
            data-view="regions"
            data-src="/Other/panel-regions.php?from=admin">

            <span class="cc-nav-icon">
                <?=ag_icon('region',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                REGIONS
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Inventory"
            data-view="inventory"
            data-src="/Other/panel-inventory.php?from=admin">

            <span class="cc-nav-icon">
                <?=ag_icon('inventory',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                INVENTORY
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="IAR Backups"
            data-view="iar-backups"
            data-src="/Other/user-iar-backups.php?from=admin">

            <span class="cc-nav-icon">
                <?=ag_icon('backup',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                IAR BACKUPS
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Offline Messages"
            data-view="offline-messages"
            data-src="/Other/admin-offline-messages.php?v=20260918-emotes-filtered-v4">

            <span class="cc-nav-icon">
                <?=ag_icon('email',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                OFFLINE MESSAGES
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Map"
            data-view="map"
            data-src="/Other/grid-map.php?from=admin&ccfit=2">

            <span class="cc-nav-icon">
                <?=ag_icon('map',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                MAP
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="3D Map"
            data-view="3d-map"
            data-src="/Other/grid-map-3d.php?from=admin">

            <span class="cc-nav-icon">
                <?=ag_icon('map',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                3D MAP
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Linked Regions"
            data-view="linked-regions"
            data-src="/Other/user-linked-regions.php?from=admin">

            <span class="cc-nav-icon">
                <?=ag_icon('link',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                LINKED REGIONS
            </span>

        </button>


        <!-- HELP CENTRE - CONTROL CENTER PANEL -->

        <button
            class="cc-nav-button"
            type="button"
            data-src="/Other/panel-help-centre.php?from=admin">


            <span class="cc-nav-icon">
                <?=ag_icon('help',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                HELP CENTRE
            </span>

        
        </button>

    </section>



    <!-- ==================================================
         ADMIN MENU
         ================================================== -->

    <section class="cc-menu-section">

        <div class="cc-menu-heading">
            ADMIN MENU
        </div>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Manage Accounts"
            data-view="manage-accounts"
            data-src="/Other/admin-accounts-clean.php">

            <span class="cc-nav-icon">
                <?=ag_icon('account',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                MANAGE ACCOUNTS
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Manage Groups"
            data-view="manage-groups"
            data-src="/Other/admin-groups-clean.php">

            <span class="cc-nav-icon">
                <?=ag_icon('groups',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                MANAGE GROUPS
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Console"
            data-view="console"
            data-src="/Other/admin-console.php">

            <span class="cc-nav-icon">
                <?=ag_icon('console',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                CONSOLE
            </span>

        </button>

    </section>



    <!-- ==================================================
         ADMIN TOOLS
         ================================================== -->

    <section class="cc-menu-section">

        <div class="cc-menu-heading">
            ADMIN TOOLS
        </div>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Control Panel"
            data-view="control-panel"
            data-src="/Other/control-panel-home.php">

            <span class="cc-nav-icon">
                <?=ag_icon('settings',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                CONTROL PANEL
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Stats"
            data-view="stats"
            data-src="/Other/stats.php">

            <span class="cc-nav-icon">
                <?=ag_icon('statistics',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                STATS
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="All Regions"
            data-view="all-regions"
            data-src="/Other/admin-all-regions.php">

            <span class="cc-nav-icon">
                <?=ag_icon('region',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                ALL REGIONS
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Region Manager"
            data-view="region-manager"
            data-src="/Other/admin-regions.php">

            <span class="cc-nav-icon">
                <?=ag_icon('region',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                REGION MANAGER
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Create Region"
            data-view="create-region"
            data-src="/Other/admin-create-region.php">

            <span class="cc-nav-icon">
                <?=ag_icon('add',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                CREATE REGION
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Region Map Textures"
            data-view="region-map-textures"
            data-src="/Other/admin-region-map-textures.php">

            <span class="cc-nav-icon">
                <?=ag_icon('map',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                REGION MAP TEXTURES
            </span>

        </button>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Page Designer"
            data-view="page-designer"
            data-src="/Other/admin-page-designer.php">

            <span class="cc-nav-icon">
                <?=ag_icon('settings',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                PAGE DESIGNER
            </span>

        </button>
<?php
$ccPdMenuPages = array();
$ccPdCore = __DIR__ . '/core/page-designer.php';

try {
    if (is_file($ccPdCore)) {
        require_once $ccPdCore;

        if (
            function_exists('ag_pd_list_pages') &&
            function_exists('ag_pd_menu_settings')
        ) {
            foreach (ag_pd_list_pages() as $ccPdPage) {
                if (
                    empty($ccPdPage['published']) ||
                    empty($ccPdPage['showInMenu'])
                ) {
                    continue;
                }

                $ccPdId =
                    strtolower(
                        trim(
                            (string)(
                                $ccPdPage['id'] ??
                                ''
                            )
                        )
                    );

                $ccPdSlug =
                    ag_pd_slug(
                        $ccPdPage['slug'] ??
                        ''
                    );

                $ccPdTitle =
                    ag_pd_text(
                        $ccPdPage['title'] ??
                        '',
                        120
                    );

                $ccPdMenuSettings =
                    ag_pd_menu_settings(
                        $ccPdPage
                    );

                $ccPdMenuLabel =
                    (string)(
                        $ccPdMenuSettings['label'] ??
                        ''
                    );

                $ccPdMenuIcon =
                    (string)(
                        $ccPdMenuSettings['icon'] ??
                        'link'
                    );

                $ccPdMenuOrder =
                    (int)(
                        $ccPdMenuSettings['order'] ??
                        100
                    );

                $ccPdMenuLocation =
                    (string)(
                        $ccPdMenuSettings['location'] ??
                        'admin-tools'
                    );

                if (
                    !preg_match(
                        '/^[a-f0-9]{16}$/',
                        $ccPdId
                    ) ||
                    $ccPdSlug === '' ||
                    $ccPdTitle === '' ||
                    $ccPdMenuLabel === ''
                ) {
                    continue;
                }

                $ccPdMenuPages[] = array(
                    'id' => $ccPdId,
                    'title' => $ccPdTitle,
                    'label' => $ccPdMenuLabel,
                    'icon' => $ccPdMenuIcon,
                    'order' => $ccPdMenuOrder,
                    'location' => $ccPdMenuLocation,
                    'slug' => $ccPdSlug
                );
            }

            usort(
                $ccPdMenuPages,
                static function ($left, $right) {
                    $orderCompare =
                        ((int)($left['order'] ?? 100)) <=>
                        ((int)($right['order'] ?? 100));

                    if ($orderCompare !== 0) {
                        return $orderCompare;
                    }

                    $labelCompare =
                        strcasecmp(
                            (string)($left['label'] ?? ''),
                            (string)($right['label'] ?? '')
                        );

                    if ($labelCompare !== 0) {
                        return $labelCompare;
                    }

                    return strcmp(
                        (string)($left['id'] ?? ''),
                        (string)($right['id'] ?? '')
                    );
                }
            );
        }
    }
}
catch (Throwable $ccPdError) {
    $ccPdMenuPages = array();
}
?>

<?php foreach ($ccPdMenuPages as $ccPdMenuPage): ?>

        <button
            type="button"
            class="cc-nav-button"
            data-title="<?=htmlspecialchars((string)($ccPdMenuPage['title'] ?? 'Custom Page'), ENT_QUOTES, 'UTF-8')?>"
            data-view="custom-page-<?=htmlspecialchars((string)($ccPdMenuPage['id'] ?? ''), ENT_QUOTES, 'UTF-8')?>"
            data-src="/Other/custom-page.php?page=<?=rawurlencode((string)($ccPdMenuPage['slug'] ?? ''))?>"
            data-page-designer-custom="1"
            data-page-designer-menu-icon="<?=htmlspecialchars((string)($ccPdMenuPage['icon'] ?? 'link'), ENT_QUOTES, 'UTF-8')?>"
            data-page-designer-menu-order="<?=htmlspecialchars((string)($ccPdMenuPage['order'] ?? 100), ENT_QUOTES, 'UTF-8')?>"
            data-page-designer-menu-location="<?=htmlspecialchars((string)($ccPdMenuPage['location'] ?? 'admin-tools'), ENT_QUOTES, 'UTF-8')?>">

            <span class="cc-nav-icon">
                <?=ag_icon((string)($ccPdMenuPage['icon'] ?? 'link'),null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                <?=htmlspecialchars(strtoupper((string)($ccPdMenuPage['label'] ?? 'CUSTOM PAGE')), ENT_QUOTES, 'UTF-8')?>
            </span>

        </button>

<?php endforeach; ?>


        <button
            type="button"
            class="cc-nav-button"
            data-title="Site & Page Design"
            data-view="site-page-design"
            data-src="/Other/admin-site-design.php">

            <span class="cc-nav-icon">
                <?=ag_icon('settings',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                SITE &amp; PAGE DESIGN
            </span>

        </button>

        <button
            type="button"
            class="cc-nav-button"
            data-title="Help Tutorial Pictures"
            data-view="help-custom-pictures"
            data-src="/Other/admin-help-pictures.php">

            <span class="cc-nav-icon">
                <?=ag_icon('settings',null,'cc-nav-svg')?>
            </span>

            <span class="cc-nav-label">
                HELP TUTORIAL PICTURES
            </span>

        </button>

    </section>



    <!-- ==================================================
         BOTTOM USER / LOGOUT
         ================================================== -->

    <div class="cc-sidebar-bottom">


        <div class="cc-user-card">

            <div class="cc-user-role">
                ADMIN
            </div>


            <div class="cc-user-name">

                <?=htmlspecialchars(
                    $avatar,
                    ENT_QUOTES,
                    'UTF-8'
                )?>

            </div>


            <div class="cc-user-level">

                LEVEL
                <?=htmlspecialchars(
                    (string)$level,
                    ENT_QUOTES,
                    'UTF-8'
                )?>

            </div>

        </div>


        <a
            class="cc-logout"
            href="/Other/logout.php">

            LOGOUT

        </a>

    </div>


</aside>



<!-- ======================================================
     RIGHT WORKSPACE
     ====================================================== -->

<section class="cc-workspace">


    <header class="cc-workspace-header">


        <div>

            <div class="cc-header-kicker">

                <?=ag_grid_name_html()?>

            </div>


            <div
                class="cc-workspace-title"
                id="cc-workspace-title">

                Admin Control Center

            </div>

        </div>


        <div class="cc-header-state">

            <strong>
                READY
            </strong>

            <span>
                ADMIN SESSION
            </span>

        </div>


    </header>



    <div class="cc-content">


        <!-- DEFAULT ADMIN FRONT PAGE -->

        <div
            class="cc-home"
            id="cc-home">


            <section class="cc-welcome">

                <div class="cc-welcome-label">
                    ADMINISTRATION
                </div>


                <h1>
                    <?=ag_grid_name_html()?>
                </h1>


                <p>
                    Select a function from the control menu.
                </p>

            </section>



            <section class="cc-stat-grid">


                <div class="cc-stat">

                    <div class="cc-stat-icon">
                        <?=ag_icon('account',null,'cc-stat-svg')?>
                    </div>

                    <div class="cc-stat-label">
                        ADMINISTRATOR
                    </div>

                    <div class="cc-stat-value">
                        <?=htmlspecialchars(
                            $avatar,
                            ENT_QUOTES,
                            'UTF-8'
                        )?>
                    </div>

                </div>


                <div class="cc-stat">

                    <div class="cc-stat-icon">
                        <?=ag_icon('region',null,'cc-stat-svg')?>
                    </div>

                    <div class="cc-stat-label">
                        GRID
                    </div>

                    <div class="cc-stat-value">
                        <?=ag_grid_name_html()?>
                    </div>

                </div>


                <div class="cc-stat">

                    <div class="cc-stat-icon">
                        <?=ag_icon('settings',null,'cc-stat-svg')?>
                    </div>

                    <div class="cc-stat-label">
                        CONTROL
                    </div>

                    <div class="cc-stat-value">
                        Admin
                    </div>

                </div>


                <div class="cc-stat">

                    <div class="cc-stat-icon">
                        <?=ag_icon('statistics',null,'cc-stat-svg')?>
                    </div>

                    <div class="cc-stat-label">
                        USER LEVEL
                    </div>

                    <div class="cc-stat-value">
                        <?=htmlspecialchars(
                            (string)$level,
                            ENT_QUOTES,
                            'UTF-8'
                        )?>
                    </div>

                </div>


                <div class="cc-stat">

                    <div class="cc-stat-icon">
                        <?=ag_icon('console',null,'cc-stat-svg')?>
                    </div>

                    <div class="cc-stat-label">
                        STATUS
                    </div>

                    <div class="cc-stat-value">
                        Ready
                    </div>

                </div>


            </section>



            <section class="cc-bars">

                <div class="cc-bar">
                    ADMIN SESSION ACTIVE
                </div>

                <div class="cc-bar">
                    CONTROL CENTER READY
                </div>

                <div class="cc-bar">
                    SECURE ACCESS
                </div>

            </section>



            <section class="cc-lower-grid">


                <article class="cc-panel">

                    <div class="cc-panel-title">
                        Control Center
                    </div>


                    <div class="cc-console">

                        <div class="cc-console-line">
                            &gt; Administrative control centre ready.
                        </div>

                        <div class="cc-console-line">
                            &gt; Select a command from the left menu.
                        </div>

                        <div class="cc-console-line">
                            &gt; The selected tool will open in this workspace.
                        </div>

                        <div class="cc-console-line">
                            &gt; Left navigation remains available at all times.
                        </div>

                    </div>

                </article>



                <article class="cc-panel">

                    <div class="cc-panel-title">
                        Session
                    </div>


                    <div class="cc-info-row">

                        <div class="cc-info-key">
                            USER
                        </div>

                        <div class="cc-info-value">
                            <?=htmlspecialchars(
                                $avatar,
                                ENT_QUOTES,
                                'UTF-8'
                            )?>
                        </div>

                    </div>


                    <div class="cc-info-row">

                        <div class="cc-info-key">
                            ACCESS
                        </div>

                        <div class="cc-info-value">

                            <?=($level >= 250)
                                ? 'Grid Owner'
                                : 'Administrator'?>

                        </div>

                    </div>


                    <div class="cc-info-row">

                        <div class="cc-info-key">
                            GRID
                        </div>

                        <div class="cc-info-value">
                            <?=ag_grid_name_html()?>
                        </div>

                    </div>


                    <div class="cc-info-row">

                        <div class="cc-info-key">
                            STATUS
                        </div>

                        <div class="cc-info-value">
                            Ready
                        </div>

                    </div>

                </article>


            </section>


        </div>



        <!-- EXISTING PAGES OPEN HERE -->

        <iframe
            class="cc-frame"
            id="cc-frame"
            name="cc-frame"
            title="Control Center Workspace">

        </iframe>


    </div>



    <footer class="cc-status">


        <div class="cc-status-left">

            <span class="cc-status-dot"></span>

            <span id="cc-status-text">
                Control Center Ready
            </span>

        </div>


        <div id="cc-clock">
            --:--
        </div>


    </footer>


</section>


</div>



<script>

(function(){

    "use strict";


    const buttons =
        Array.from(
            document.querySelectorAll(
                ".cc-nav-button[data-src]"
            )
        );


    const frame =
        document.getElementById(
            "cc-frame"
        );


    const home =
        document.getElementById(
            "cc-home"
        );


    const title =
        document.getElementById(
            "cc-workspace-title"
        );


    const status =
        document.getElementById(
            "cc-status-text"
        );


    function clearActive(){

        buttons.forEach(
            function(button){

                button.classList.remove(
                    "active"
                );
            }
        );
    }


    function withSid(url){

        const current =
            new URLSearchParams(
                window.location.search
            );


        const sid =
            current.get(
                "sid"
            );


        if(!sid){
            return url;
        }


        const target =
            new URL(
                url,
                window.location.origin
            );


        if(
            !target.searchParams.has(
                "sid"
            )
        ){
            target.searchParams.set(
                "sid",
                sid
            );
        }


        return (
            target.pathname +
            target.search
        );
    }


    function openView(button,pushState){

        if(!button){
            return;
        }


        clearActive();

        button.classList.add(
            "active"
        );


        const pageTitle =
            button.dataset.title ||
            "Control Center";


        const view =
            button.dataset.view ||
            "";


        const src =
            withSid(
                button.dataset.src
            );


        title.textContent =
            pageTitle;


        status.textContent =
            pageTitle + " Open";


        home.classList.add(
            "hidden"
        );


        frame.classList.add(
            "visible"
        );


        frame.src =
            src;


        if(pushState !== false){

            const next =
                new URL(
                    window.location.href
                );


            next.searchParams.set(
                "view",
                view
            );


            history.pushState(
                {
                    view:view
                },
                "",
                next
            );
        }
    }


    function showHome(pushState){

        clearActive();


        frame.classList.remove(
            "visible"
        );


        frame.src =
            "about:blank";


        home.classList.remove(
            "hidden"
        );


        title.textContent =
            "Admin Control Center";


        status.textContent =
            "Control Center Ready";


        if(pushState !== false){

            const next =
                new URL(
                    window.location.href
                );


            next.searchParams.delete(
                "view"
            );


            history.pushState(
                {},
                "",
                next
            );
        }
    }


    buttons.forEach(
        function(button){

            button.addEventListener(
                "click",
                function(){

                    openView(
                        button,
                        true
                    );
                }
            );
        }
    );


    const pageDesignerMenuIcons = {
        "account": <?=json_encode(ag_icon('account',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>,
        "add": <?=json_encode(ag_icon('add',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>,
        "avatar": <?=json_encode(ag_icon('avatar',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>,
        "backup": <?=json_encode(ag_icon('backup',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>,
        "console": <?=json_encode(ag_icon('console',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>,
        "email": <?=json_encode(ag_icon('email',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>,
        "groups": <?=json_encode(ag_icon('groups',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>,
        "help": <?=json_encode(ag_icon('help',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>,
        "inventory": <?=json_encode(ag_icon('inventory',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>,
        "link": <?=json_encode(ag_icon('link',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>,
        "map": <?=json_encode(ag_icon('map',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>,
        "region": <?=json_encode(ag_icon('region',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>,
        "settings": <?=json_encode(ag_icon('settings',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>,
        "statistics": <?=json_encode(ag_icon('statistics',null,'cc-nav-svg'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)?>
    };


    const pageDesignerMenuLocations = {
        "dashboard":"DASHBOARD",
        "admin-menu":"ADMIN MENU",
        "admin-tools":"ADMIN TOOLS"
    };


    function pageDesignerMenuLocation(value){

        const normalized =
            String(
                value ||
                ""
            )
            .trim()
            .toLowerCase();

        return Object.prototype.hasOwnProperty.call(
            pageDesignerMenuLocations,
            normalized
        )
            ? normalized
            : "admin-tools";
    }


    function pageDesignerMenuSection(location){

        const normalized =
            pageDesignerMenuLocation(
                location
            );

        const expectedHeading =
            pageDesignerMenuLocations[
                normalized
            ];

        const sections =
            Array.from(
                document.querySelectorAll(
                    ".cc-menu-section"
                )
            );

        return (
            sections.find(
                function(section){

                    const heading =
                        section.querySelector(
                            ".cc-menu-heading"
                        );

                    return (
                        heading &&
                        heading.textContent
                            .trim()
                            .toUpperCase() ===
                            expectedHeading
                    );
                }
            ) ||
            null
        );
    }


    function placePageDesignerServerButtons(){

        buttons
            .filter(
                function(button){

                    return (
                        button.dataset.pageDesignerCustom ===
                        "1"
                    );
                }
            )
            .forEach(
                function(button){

                    const location =
                        pageDesignerMenuLocation(
                            button.dataset.pageDesignerMenuLocation
                        );

                    button.dataset.pageDesignerMenuLocation =
                        location;

                    if(
                        location ===
                        "admin-tools"
                    ){
                        return;
                    }

                    const section =
                        pageDesignerMenuSection(
                            location
                        );

                    if(section){
                        section.appendChild(
                            button
                        );
                    }
                    else{
                        button.dataset.pageDesignerMenuLocation =
                            "admin-tools";
                    }
                }
            );
    }


    window.addEventListener(
        "message",
        function(event){

            if(
                event.origin !==
                window.location.origin
            ){
                return;
            }

            const data =
                event.data;

            if(
                !data ||
                data.type !==
                    "cc-page-designer-menu-sync" ||
                !Array.isArray(
                    data.pages
                )
            ){
                return;
            }

            for(
                let index =
                    buttons.length - 1;
                index >= 0;
                index--
            ){

                const item =
                    buttons[index];

                if(
                    item.dataset.pageDesignerCustom ===
                    "1"
                ){

                    item.remove();

                    buttons.splice(
                        index,
                        1
                    );
                }
            }

            const pageDesignerButton =
                buttons.find(
                    function(item){

                        return (
                            item.dataset.view ===
                            "page-designer"
                        );
                    }
                );

            if(
                !pageDesignerButton ||
                !pageDesignerButton.parentNode
            ){
                return;
            }

            const adminToolsParent =
                pageDesignerButton.parentNode;

            const adminToolsBeforeNode =
                pageDesignerButton.nextSibling;

            const activeView =
                new URLSearchParams(
                    window.location.search
                )
                .get(
                    "view"
                ) ||
                "";

            const menuPages =
                data.pages
                .map(
                    function(page){

                        const id =
                            String(
                                page &&
                                page.id ||
                                ""
                            )
                            .toLowerCase();

                        const titleText =
                            String(
                                page &&
                                page.title ||
                                ""
                            )
                            .trim();

                        const menuLabel =
                            String(
                                page &&
                                page.label ||
                                titleText
                            )
                            .trim()
                            .slice(
                                0,
                                40
                            );

                        const slug =
                            String(
                                page &&
                                page.slug ||
                                ""
                            )
                            .trim()
                            .toLowerCase();

                        let iconName =
                            String(
                                page &&
                                page.icon ||
                                "link"
                            )
                            .trim()
                            .toLowerCase();

                        if(
                            !Object.prototype.hasOwnProperty.call(
                                pageDesignerMenuIcons,
                                iconName
                            )
                        ){
                            iconName =
                                "link";
                        }

                        let menuOrder =
                            Number.parseInt(
                                page &&
                                page.order,
                                10
                            );

                        if(
                            !Number.isFinite(
                                menuOrder
                            )
                        ){
                            menuOrder =
                                100;
                        }

                        menuOrder =
                            Math.max(
                                0,
                                Math.min(
                                    999,
                                    menuOrder
                                )
                            );

                        const menuLocation =
                            pageDesignerMenuLocation(
                                page &&
                                page.location
                            );

                        if(
                            !/^[a-f0-9]{16}$/.test(
                                id
                            ) ||
                            !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(
                                slug
                            ) ||
                            !titleText ||
                            !menuLabel
                        ){
                            return null;
                        }

                        return {
                            id:id,
                            title:titleText,
                            label:menuLabel,
                            icon:iconName,
                            order:menuOrder,
                            location:menuLocation,
                            slug:slug
                        };
                    }
                )
                .filter(
                    function(page){
                        return page !== null;
                    }
                )
                .sort(
                    function(left,right){

                        if(
                            left.order !==
                            right.order
                        ){
                            return (
                                left.order -
                                right.order
                            );
                        }

                        const labelCompare =
                            left.label.localeCompare(
                                right.label,
                                undefined,
                                {
                                    sensitivity:"base"
                                }
                            );

                        if(labelCompare !== 0){
                            return labelCompare;
                        }

                        return left.id.localeCompare(
                            right.id
                        );
                    }
                );

            menuPages.forEach(
                function(page){

                    const button =
                        document.createElement(
                            "button"
                        );

                    button.type =
                        "button";

                    button.className =
                        "cc-nav-button";

                    button.dataset.title =
                        page.title;

                    button.dataset.view =
                        "custom-page-" +
                        page.id;

                    button.dataset.src =
                        "/Other/custom-page.php?page=" +
                        encodeURIComponent(
                            page.slug
                        );

                    button.dataset.pageDesignerCustom =
                        "1";

                    button.dataset.pageDesignerMenuIcon =
                        page.icon;

                    button.dataset.pageDesignerMenuOrder =
                        String(
                            page.order
                        );

                    button.dataset.pageDesignerMenuLocation =
                        page.location;

                    if(
                        button.dataset.view ===
                        activeView
                    ){
                        button.classList.add(
                            "active"
                        );
                    }

                    const icon =
                        document.createElement(
                            "span"
                        );

                    icon.className =
                        "cc-nav-icon";

                    icon.innerHTML =
                        pageDesignerMenuIcons[
                            page.icon
                        ] ||
                        pageDesignerMenuIcons.link ||
                        "";

                    const label =
                        document.createElement(
                            "span"
                        );

                    label.className =
                        "cc-nav-label";

                    label.textContent =
                        page.label.toUpperCase();

                    button.appendChild(
                        icon
                    );

                    button.appendChild(
                        label
                    );

                    button.addEventListener(
                        "click",
                        function(){

                            openView(
                                button,
                                true
                            );
                        }
                    );

                    if(
                        page.location ===
                        "admin-tools"
                    ){
                        adminToolsParent.insertBefore(
                            button,
                            adminToolsBeforeNode
                        );
                    }
                    else{
                        const section =
                            pageDesignerMenuSection(
                                page.location
                            );

                        if(section){
                            section.appendChild(
                                button
                            );
                        }
                        else{
                            button.dataset.pageDesignerMenuLocation =
                                "admin-tools";

                            adminToolsParent.insertBefore(
                                button,
                                adminToolsBeforeNode
                            );
                        }
                    }

                    buttons.push(
                        button
                    );
                }
            );
        }
    );


    placePageDesignerServerButtons();

    frame.addEventListener(
        "load",
        function(){

            if(
                !frame.src ||
                frame.src ===
                "about:blank"
            ){
                return;
            }


            try{

                const doc =
                    frame.contentDocument;


                if(!doc){
                    return;
                }


                                                /*
                 * Apply shared embedded theme.
                 */

                if(
                    !doc.getElementById(
                        "cc-embedded-theme"
                    )
                ){

                    const link =
                        doc.createElement(
                            "link"
                        );


                    link.id =
                        "cc-embedded-theme";


                    link.rel =
                        "stylesheet";


                    link.href =
                        "/Other/assets/css/control-center-embedded-v1.css?v=1";


                    doc.head.appendChild(
                        link
                    );
                }


                /*
                 * Remove old Back / Home controls
                 * from pages displayed inside
                 * the permanent shell.
                 */

                Array.from(
                    doc.querySelectorAll(
                        "a,button"
                    )
                ).forEach(
                    function(element){

                        const text =
                            (
                                element.textContent ||
                                ""
                            )
                            .trim()
                            .toUpperCase();


                        if(
                            text === "BACK" ||
                            text.indexOf(
                                "BACK TO "
                            ) === 0 ||
                            text ===
                                "ADMIN HOME" ||
                            text ===
                                "BACK TO DASHBOARD" ||
                            text ===
                                "ADMIN DASHBOARD"
                        ){

                            element.style.display =
                                "none";
                        }
                    }
                );


                /*
                 * Keep links inside this right
                 * workspace unless they explicitly
                 * open elsewhere.
                 */

                Array.from(
                    doc.querySelectorAll(
                        "a[href]"
                    )
                ).forEach(
                    function(link){

                        const href =
                            link.getAttribute(
                                "href"
                            );


                        if(
                            !href ||
                            href.startsWith("#") ||
                            href.startsWith("javascript:")
                        ){
                            return;
                        }


                        if(
                            link.target === "_blank"
                        ){
                            return;
                        }


                        link.target =
                            "_self";
                    }
                );


            }
            catch(error){

                console.log(
                    "Embedded style skipped:",
                    error
                );
            }
        }
    );


    window.addEventListener(
        "popstate",
        function(){

            restoreFromUrl();
        }
    );


    function restoreFromUrl(){

        const params =
            new URLSearchParams(
                window.location.search
            );


        const view =
            params.get(
                "view"
            );


        if(!view){

            showHome(
                false
            );

            return;
        }


        const button =
            buttons.find(
                function(item){

                    return (
                        item.dataset.view ===
                        view
                    );
                }
            );


        if(button){

            openView(
                button,
                false
            );

        }
        else{

            showHome(
                false
            );
        }
    }


    function updateClock(){

        const clock =
            document.getElementById(
                "cc-clock"
            );


        if(!clock){
            return;
        }


        clock.textContent =
            new Date().toLocaleTimeString(
                [],
                {
                    hour:"2-digit",
                    minute:"2-digit"
                }
            );
    }


    updateClock();

    setInterval(
        updateClock,
        10000
    );


    restoreFromUrl();

})();

</script>



<script id="cc-account-view-dom-v1">

/*
 * ==========================================================
 * CONTROL CENTER ACCOUNT VIEW DOM V1
 * ==========================================================
 */

(function(){

    "use strict";


    const frame =
        document.getElementById(
            "cc-frame"
        );


    if(!frame){
        return;
    }


    function cleanText(value){

        return String(
            value || ""
        )
        .replace(
            /\s+/g,
            " "
        )
        .trim();
    }


    function findExactText(
        doc,
        wanted
    ){

        const target =
            wanted.toUpperCase();


        const elements =
            Array.from(
                doc.querySelectorAll(
                    "div,span,strong,label,p,h1,h2,h3,h4"
                )
            );


        return elements.find(
            function(element){

                return (
                    cleanText(
                        element.textContent
                    ).toUpperCase()
                    ===
                    target
                );
            }
        ) || null;
    }


    function findFieldBox(
        labelElement
    ){

        if(!labelElement){
            return null;
        }


        let node =
            labelElement;


        for(
            let i = 0;
            i < 5 && node;
            i++
        ){

            const parent =
                node.parentElement;


            if(!parent){
                break;
            }


            const text =
                cleanText(
                    parent.textContent
                );


            if(
                text.length >
                    cleanText(
                        labelElement.textContent
                    ).length
                &&
                text.length < 220
            ){

                return parent;
            }


            node =
                parent;
        }


        return (
            labelElement.parentElement ||
            null
        );
    }


    function styleField(
        doc,
        labelText
    ){

        const label =
            findExactText(
                doc,
                labelText
            );


        if(!label){
            return null;
        }


        label.classList.add(
            "cc-account-field-label"
        );


        const box =
            findFieldBox(
                label
            );


        if(!box){
            return null;
        }


        box.classList.add(
            "cc-account-field"
        );


        Array.from(
            box.querySelectorAll(
                "div,span,strong,p"
            )
        ).forEach(
            function(element){

                const text =
                    cleanText(
                        element.textContent
                    );


                if(
                    !text ||
                    element === label
                ){
                    return;
                }


                if(
                    text.toUpperCase()
                    !==
                    labelText.toUpperCase()
                    &&
                    text.length < 120
                ){

                    element.classList.add(
                        "cc-account-field-value"
                    );


                    if(
                        text.toUpperCase()
                        ===
                        "ACTIVE"
                    ){

                        element.classList.add(
                            "cc-account-active-value"
                        );
                    }
                }
            }
        );


        return box;
    }


    function arrangeFields(
        doc,
        boxes
    ){

        const usable =
            boxes.filter(
                Boolean
            );


        if(
            usable.length !== 4
        ){
            return;
        }


        /*
         * Find common parent.
         */

        let parent =
            usable[0].parentElement;


        while(
            parent &&
            !usable.every(
                function(box){

                    return (
                        box.parentElement ===
                        parent
                    );
                }
            )
        ){

            parent =
                parent.parentElement;
        }


        if(parent){

            parent.classList.add(
                "cc-account-grid-v1"
            );
        }
    }


    function styleNotice(
        doc
    ){

        const elements =
            Array.from(
                doc.querySelectorAll(
                    "div,p,section"
                )
            );


        const notice =
            elements.find(
                function(element){

                    const text =
                        cleanText(
                            element.textContent
                        ).toLowerCase();


                    return (
                        text ===
                        "account details are temporarily unavailable."
                        ||
                        text.indexOf(
                            "account details are temporarily unavailable"
                        ) === 0
                    );
                }
            );


        if(notice){

            notice.classList.add(
                "cc-account-notice"
            );
        }
    }


    function styleEditButton(
        doc
    ){

        const controls =
            Array.from(
                doc.querySelectorAll(
                    "a,button"
                )
            );


        const edit =
            controls.find(
                function(element){

                    return (
                        cleanText(
                            element.textContent
                        ).toUpperCase()
                        ===
                        "EDIT ACCOUNT"
                    );
                }
            );


        if(!edit){
            return;
        }


        edit.classList.add(
            "cc-account-edit-button"
        );


        if(edit.parentElement){

            edit.parentElement.classList.add(
                "cc-account-edit-wrap"
            );
        }
    }


    function applyAccountStyle(){

        try{

            const path =
                frame.contentWindow
                    .location
                    .pathname
                    .toLowerCase();


            if(
                !path.endsWith(
                    "/user-account.php"
                )
            ){
                return;
            }


            const doc =
                frame.contentDocument;


            if(
                !doc ||
                !doc.body
            ){
                return;
            }


            doc.body.classList.add(
                "cc-account-view"
            );


            const boxes = [

                styleField(
                    doc,
                    "AVATAR NAME"
                ),

                styleField(
                    doc,
                    "ACCOUNT STATUS"
                ),

                styleField(
                    doc,
                    "EMAIL"
                ),

                styleField(
                    doc,
                    "MEMBER SINCE"
                )
            ];


            arrangeFields(
                doc,
                boxes
            );


            styleNotice(
                doc
            );


            styleEditButton(
                doc
            );

        }
        catch(error){

            console.log(
                "Account view styling:",
                error
            );
        }
    }


    frame.addEventListener(
        "load",
        function(){

            /*
             * Give the original page a moment
             * to finish its own rendering.
             */

            setTimeout(
                applyAccountStyle,
                60
            );
        }
    );

})();

</script>


<script id="cc-standard-navigation-v1">

/*
 * ==========================================================
 * STANDARD CONTROL CENTER NAVIGATION V2
 *
 * TOP-LEVEL PAGE OWNER:
 *     Existing sidebar openView()
 *
 * ccOpenPanel is now a bridge for nested panel navigation.
 * ==========================================================
 */

(function(){

    "use strict";


    function ccUrl(src){

        try{

            return new URL(
                src,
                window.location.href
            );
        }
        catch(error){

            return null;
        }
    }


    function ccShellView(){

        const params =
            new URLSearchParams(
                window.location.search
            );


        return (
            params.get(
                "view"
            ) ||
            ""
        );
    }


    function ccFindSidebarButton(src,view){

        const target =
            ccUrl(
                src
            );


        const buttons =
            Array.from(
                document.querySelectorAll(
                    ".cc-nav-button[data-src]"
                )
            );


        for(
            let i = 0;
            i < buttons.length;
            i++
        ){

            const button =
                buttons[i];


            const buttonView =
                button.dataset.view ||
                "";


            if(
                view &&
                buttonView === view
            ){

                return button;
            }


            const buttonUrl =
                ccUrl(
                    button.dataset.src ||
                    ""
                );


            if(
                target &&
                buttonUrl &&
                target.pathname ===
                buttonUrl.pathname
            ){

                return button;
            }
        }


        return null;
    }


    function ccWithSid(src){

        const target =
            ccUrl(
                src
            );


        if(!target){

            return src;
        }


        const shell =
            new URL(
                window.location.href
            );


        const sid =
            shell.searchParams.get(
                "sid"
            );


        if(
            sid &&
            !target.searchParams.has(
                "sid"
            )
        ){

            target.searchParams.set(
                "sid",
                sid
            );
        }


        return (
            target.pathname +
            target.search +
            target.hash
        );
    }


    window.ccOpenPanel =
        function(
            pageTitle,
            src,
            view,
            pushState
        ){

            if(!src){

                return false;
            }


            const sidebarButton =
                ccFindSidebarButton(
                    src,
                    view
                );


            /*
             * TOP-LEVEL DESTINATION
             *
             * Do not directly assign frame.src here.
             *
             * The existing sidebar/openView system remains
             * the one owner of top-level Control Center pages.
             */

            if(sidebarButton){

                const requestedView =
                    sidebarButton.dataset.view ||
                    view ||
                    "";


                const shellView =
                    ccShellView();


                /*
                 * A background/non-history request may not
                 * replace a different active top-level page.
                 *
                 * This is what prevents CREATE REGION from
                 * silently being replaced by REGION MANAGER.
                 */

                if(
                    pushState === false &&
                    shellView &&
                    requestedView &&
                    shellView !== requestedView
                ){

                    return false;
                }


                /*
                 * Delegate to the existing sidebar handler.
                 * That handler owns openView().
                 */

                sidebarButton.click();

                return false;
            }


            /*
             * NESTED PANEL DESTINATION
             *
             * No matching sidebar page exists, so this may
             * remain inside the current Control Center iframe.
             */

            const frame =
                document.getElementById(
                    "cc-frame"
                );


            if(!frame){

                return false;
            }


            const home =
                document.getElementById(
                    "cc-home"
                );


            const title =
                document.getElementById(
                    "cc-workspace-title"
                );


            const status =
                document.getElementById(
                    "cc-status-text"
                );


            if(home){

                home.classList.add(
                    "hidden"
                );
            }


            frame.classList.add(
                "visible"
            );


            if(title){

                title.textContent =
                    pageTitle ||
                    "Control Center";
            }


            if(status){

                status.textContent =
                    (
                        pageTitle ||
                        "Control Center"
                    ) +
                    " Open";
            }


            frame.src =
                ccWithSid(
                    src
                );


            if(
                pushState !== false &&
                view
            ){

                const next =
                    new URL(
                        window.location.href
                    );


                next.searchParams.set(
                    "view",
                    view
                );


                history.pushState(
                    {
                        view:view
                    },
                    "",
                    next
                );
            }


            return false;
        };

})();

</script>

</body>

</html>







