<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/help-pictures.php';
require_once __DIR__ . '/core/icons.php';

ag_require_admin();
ag_no_cache();

?>
<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1">

    <title>Teleporting Rebuild Preview</title>

    <link
        rel="stylesheet"
        href="/Other/assets/css/help-teleporting-v1.css?v=2">

    <script
        src="/Other/assets/js/help-teleporting-v1.js?v=1"
        defer>
    </script>

</head>

<body>

<div class="tp-shell">


    <!-- =====================================================
         FRESH TELEPORTING HEADER
         ===================================================== -->

    <header class="tp-hero">

        <div class="tp-hero-icon">

            <?=ag_icon(
                'map',
                null,
                'tp-icon-svg'
            )?>

        </div>


        <div class="tp-hero-copy">

            <div class="tp-kicker">
                FIRESTORM
            </div>

            <h1>
                TELEPORT TO ANOTHER REGION
            </h1>

            <p>
                Find another region on this grid with the
                Firestorm World Map and teleport directly
                to your destination.
            </p>

        </div>


        <div class="tp-status">

            <span class="tp-status-light"></span>

            <div>
                <strong>
                    TELEPORT GUIDE
                </strong>

                <span>
                    READY
                </span>
            </div>

        </div>

    </header>



    <!-- =====================================================
         INTRODUCTION
         ===================================================== -->

    <section class="tp-intro-card">

        <div class="tp-section-heading">

            <div class="tp-heading-number">
                4
            </div>

            <div>

                <div class="tp-small-label">
                    QUICK GUIDE
                </div>

                <h2>
                    TELEPORT IN FOUR EASY STEPS
                </h2>

            </div>

        </div>


        <p class="tp-intro-text">

            Use the World Map in your
            OpenSim-compatible viewer to search for a region,
            select the destination and teleport there.

        </p>

    </section>



    <!-- =====================================================
         FOUR FRESH STEP CARDS
         ===================================================== -->

    <section class="tp-step-grid">


        <article class="tp-step-card">

            <div class="tp-step-top">

                <span class="tp-step-number">
                    01
                </span>

                <span class="tp-step-tag">
                    WORLD MAP
                </span>

            </div>

            <div class="tp-step-icon">
                <?=ag_icon(
                    'map',
                    null,
                    'tp-step-svg'
                )?>
            </div>

            <h3>
                OPEN THE WORLD MAP
            </h3>

            <p>
                Open the World Map from Firestorm so you can
                see and search the regions available on the grid.
            </p>

        </article>



        <article class="tp-step-card">

            <div class="tp-step-top">

                <span class="tp-step-number">
                    02
                </span>

                <span class="tp-step-tag">
                    SEARCH
                </span>

            </div>

            <div class="tp-step-icon">

                <?=ag_icon(
                    'search',
                    null,
                    'tp-step-svg'
                )?>

            </div>

            <h3>
                SEARCH FOR THE REGION
            </h3>

            <p>
                Enter the destination region name into the
                World Map search and locate the region you
                want to visit.
            </p>

        </article>



        <article class="tp-step-card">

            <div class="tp-step-top">

                <span class="tp-step-number">
                    03
                </span>

                <span class="tp-step-tag">
                    DESTINATION
                </span>

            </div>

            <div class="tp-step-icon">

                <?=ag_icon(
                    'region',
                    null,
                    'tp-step-svg'
                )?>

            </div>

            <h3>
                SELECT THE REGION
            </h3>

            <p>
                Choose the correct region from the map or the
                search results so Firestorm knows your
                destination.
            </p>

        </article>



        <article class="tp-step-card">

            <div class="tp-step-top">

                <span class="tp-step-number">
                    04
                </span>

                <span class="tp-step-tag">
                    TELEPORT
                </span>

            </div>

            <div class="tp-step-icon">

                <?=ag_icon(
                    'teleport',
                    null,
                    'tp-step-svg'
                )?>

            </div>

            <h3>
                CHOOSE TELEPORT
            </h3>

            <p>
                Press the Teleport button in Firestorm to
                travel to the selected region.
            </p>

        </article>


    </section>



    <!-- =====================================================
         FOUR PICTURE TELEPORT TUTORIAL
         VISUAL DESIGN ONLY - NOT CONNECTED YET
         ===================================================== -->

    <section class="tp-picture-card">

        <div class="tp-picture-head">

            <div>

                <div class="tp-small-label">
                    VISUAL WALKTHROUGH
                </div>

                <h2>
                    TELEPORTING PICTURE TUTORIAL
                </h2>

                <p>
                    Follow the four picture steps below to open
                    the World Map, find a region and teleport.
                </p>

            </div>


            <div class="tp-picture-badge">

                <span>
                    4 PICTURE
                </span>

                <strong>
                    GUIDE
                </strong>

            </div>

        </div>


        <div class="tp-picture-controls">

            <button
                type="button"
                class="tp-button tp-button-primary"
                id="tpPictureTutorialToggle"
                aria-controls="tp-picture-tutorial"
                aria-expanded="false">

                <span class="tp-button-icon">

                    <?=ag_icon(
                        'image',
                        null,
                        'tp-button-svg'
                    )?>

                </span>

                <span id="tpPictureTutorialToggleText">
                    VIEW PICTURE TUTORIAL
                </span>

            </button>

        </div>

        <div class="tp-picture-grid" id="tp-picture-tutorial" hidden>


            <!-- STEP 1 -->

            <article class="tp-picture-step">

                <div class="tp-picture-step-head">

                    <div class="tp-picture-step-number">
                        STEP 1
                    </div>

                    <div class="tp-picture-step-icon">

                        <?=ag_icon(
                            'image',
                            null,
                            'tp-picture-step-svg'
                        )?>

                    </div>

                </div>

                <h3>
                    OPEN THE WORLD MAP
                </h3>

                <p>
                    Open the World Map in Firestorm.
                </p>

                <div class="tp-picture-frame">

                    <div class="tp-picture-frame-inner tp-picture-frame-live">

                        <img
                            class="tp-picture-image"
                            src="<?=ag_h(ag_help_picture_display_url('topic-teleport'))?>"
                            alt="Step 1 - Open the Firestorm World Map"
                            onerror="
                                this.style.display='none';
                                this.nextElementSibling.style.display='flex';
                            ">

                        <div class="tp-picture-fallback">

                            <?=ag_icon(
                                'image',
                                null,
                                'tp-picture-placeholder-svg'
                            )?>

                            <strong>
                                STEP 1 PICTURE
                            </strong>

                            <span>
                                Upload in Admin → Help Centre Pictures
                            </span>

                        </div>

                    </div>

                </div>

            </article>



            <!-- STEP 2 -->

            <article class="tp-picture-step">

                <div class="tp-picture-step-head">

                    <div class="tp-picture-step-number">
                        STEP 2
                    </div>

                    <div class="tp-picture-step-icon">

                        <?=ag_icon(
                            'image',
                            null,
                            'tp-picture-step-svg'
                        )?>

                    </div>

                </div>

                <h3>
                    SEARCH FOR THE REGION
                </h3>

                <p>
                    Enter the destination region name.
                </p>

                <div class="tp-picture-frame">

                    <div class="tp-picture-frame-inner tp-picture-frame-live">

                        <img
                            class="tp-picture-image"
                            src="<?=ag_h(ag_help_picture_display_url('topic-teleport-step-02'))?>"
                            alt="Step 2 - Search for the destination region"
                            onerror="
                                this.style.display='none';
                                this.nextElementSibling.style.display='flex';
                            ">

                        <div class="tp-picture-fallback">

                            <?=ag_icon(
                                'image',
                                null,
                                'tp-picture-placeholder-svg'
                            )?>

                            <strong>
                                STEP 2 PICTURE
                            </strong>

                            <span>
                                Upload in Admin → Help Centre Pictures
                            </span>

                        </div>

                    </div>

                </div>

            </article>



            <!-- STEP 3 -->

            <article class="tp-picture-step">

                <div class="tp-picture-step-head">

                    <div class="tp-picture-step-number">
                        STEP 3
                    </div>

                    <div class="tp-picture-step-icon">

                        <?=ag_icon(
                            'image',
                            null,
                            'tp-picture-step-svg'
                        )?>

                    </div>

                </div>

                <h3>
                    SELECT THE REGION
                </h3>

                <p>
                    Choose the correct region from the results.
                </p>

                <div class="tp-picture-frame">

                    <div class="tp-picture-frame-inner tp-picture-frame-live">

                        <img
                            class="tp-picture-image"
                            src="<?=ag_h(ag_help_picture_display_url('topic-teleport-step-03'))?>"
                            alt="Step 3 - Select the destination region"
                            onerror="
                                this.style.display='none';
                                this.nextElementSibling.style.display='flex';
                            ">

                        <div class="tp-picture-fallback">

                            <?=ag_icon(
                                'image',
                                null,
                                'tp-picture-placeholder-svg'
                            )?>

                            <strong>
                                STEP 3 PICTURE
                            </strong>

                            <span>
                                Upload in Admin → Help Centre Pictures
                            </span>

                        </div>

                    </div>

                </div>

            </article>



            <!-- STEP 4 -->

            <article class="tp-picture-step">

                <div class="tp-picture-step-head">

                    <div class="tp-picture-step-number">
                        STEP 4
                    </div>

                    <div class="tp-picture-step-icon">

                        <?=ag_icon(
                            'image',
                            null,
                            'tp-picture-step-svg'
                        )?>

                    </div>

                </div>

                <h3>
                    CLICK TELEPORT
                </h3>

                <p>
                    Press Teleport to travel to the region.
                </p>

                <div class="tp-picture-frame">

                    <div class="tp-picture-frame-inner tp-picture-frame-live">

                        <img
                            class="tp-picture-image"
                            src="<?=ag_h(ag_help_picture_display_url('topic-teleport-step-04'))?>"
                            alt="Step 4 - Click Teleport"
                            onerror="
                                this.style.display='none';
                                this.nextElementSibling.style.display='flex';
                            ">

                        <div class="tp-picture-fallback">

                            <?=ag_icon(
                                'image',
                                null,
                                'tp-picture-placeholder-svg'
                            )?>

                            <strong>
                                STEP 4 PICTURE
                            </strong>

                            <span>
                                Upload in Admin → Help Centre Pictures
                            </span>

                        </div>

                    </div>

                </div>

            </article>


        </div>


    </section>


    <!-- =====================================================
         INFORMATION STRIP
         ===================================================== -->

    <section class="tp-info-strip">

        <div class="tp-info-icon">

            <?=ag_icon(
                'help',
                null,
                'tp-info-svg'
            )?>

        </div>

        <div>

            <strong>
                TELEPORT TIP
            </strong>

            <p>
                If a region does not appear immediately,
                check the spelling of the region name and
                try the World Map search again.
            </p>

        </div>

    </section>


</div>

</body>
</html>