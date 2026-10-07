<?php
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/site-design-assets.php';

ag_no_cache();

$session =
    ag_require_admin();

$avatar =
    ag_avatar_name($session);

$level =
    ag_user_level($session);


if (
    session_status() !==
    PHP_SESSION_ACTIVE
) {
    session_start();
}


if (
    empty(
        $_SESSION['ag_site_design_csrf']
    )
) {

    $_SESSION['ag_site_design_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    (string)
    $_SESSION['ag_site_design_csrf'];

$error = '';
$success = '';


if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    ===
    'POST'
) {

    $postedCsrf =
        (string)
        ($_POST['csrf_token'] ?? '');

    $action =
        trim(
            (string)
            ($_POST['action'] ?? '')
        );

    $slotKey =
        trim(
            (string)
            ($_POST['slot'] ?? '')
        );


    if (
        $postedCsrf === '' ||
        !hash_equals(
            $csrfToken,
            $postedCsrf
        )
    ) {

        $error =
            'Your security token expired. Reload the page and try again.';
    }
    else {

        $slot =
            ag_site_design_slot(
                $slotKey
            );


        if (!$slot) {

            $error =
                'Unknown Site & Page Design picture slot.';
        }


        elseif ($action === 'remove') {

            try {

                $files =
                    ag_site_design_custom_files(
                        $slotKey
                    );


                if (!$files) {

                    $success =
                        'This picture is already using its supplied default.';
                }
                else {

                    foreach (
                        $files
                        as
                        $existing
                    ) {

                        ag_site_design_backup_existing(
                            $slotKey,
                            $existing,
                            'restore-default'
                        );
                    }


                    foreach (
                        $files
                        as
                        $existing
                    ) {

                        if (
                            is_file($existing) &&
                            !@unlink($existing)
                        ) {

                            throw new RuntimeException(
                                'Could not remove ' .
                                basename($existing) .
                                '.'
                            );
                        }
                    }


                    $success =
                        (string)
                        ($slot['label'] ?? $slotKey) .
                        ' restored to the supplied default.';
                }
            }
            catch (Throwable $e) {

                $error =
                    'Picture was not restored: ' .
                    $e->getMessage();
            }
        }


        elseif ($action === 'upload') {

            try {

                if (
                    !isset($_FILES['picture']) ||
                    !is_array(
                        $_FILES['picture']
                    )
                ) {

                    throw new RuntimeException(
                        'Choose a picture first.'
                    );
                }


                $upload =
                    $_FILES['picture'];


                $uploadError =
                    (int)
                    ($upload['error']
                        ?? UPLOAD_ERR_NO_FILE);


                if (
                    $uploadError !==
                    UPLOAD_ERR_OK
                ) {

                    throw new RuntimeException(

                        $uploadError ===
                        UPLOAD_ERR_NO_FILE

                            ? 'Choose a picture first.'

                            : 'The upload failed with PHP error code ' .
                              $uploadError .
                              '.'
                    );
                }


                $size =
                    (int)
                    ($upload['size'] ?? 0);


                if (
                    $size < 1 ||
                    $size >
                    15 * 1024 * 1024
                ) {

                    throw new RuntimeException(
                        'Picture must be larger than 0 bytes and no more than 15 MB.'
                    );
                }


                $tmp =
                    (string)
                    ($upload['tmp_name'] ?? '');


                if (
                    $tmp === '' ||
                    !is_uploaded_file($tmp)
                ) {

                    throw new RuntimeException(
                        'PHP did not recognise this as a valid uploaded file.'
                    );
                }


                $image =
                    @getimagesize(
                        $tmp
                    );


                if (!is_array($image)) {

                    throw new RuntimeException(
                        'The uploaded file is not a readable image.'
                    );
                }


                $mime =
                    strtolower(
                        (string)
                        ($image['mime'] ?? '')
                    );


                $mimeToExt = [

                    'image/jpeg' =>
                        'jpg',

                    'image/png' =>
                        'png',

                    'image/webp' =>
                        'webp',
                ];


                if (
                    !isset(
                        $mimeToExt[$mime]
                    )
                ) {

                    throw new RuntimeException(
                        'Only JPG/JPEG, PNG and WEBP pictures are allowed.'
                    );
                }


                $extension =
                    $mimeToExt[$mime];


                $target =
                    ag_site_design_target(
                        $slot,
                        $extension
                    );


                $targetDir =
                    dirname($target);


                if (
                    !is_dir($targetDir) &&
                    !@mkdir(
                        $targetDir,
                        0770,
                        true
                    )
                ) {

                    throw new RuntimeException(
                        'Could not create the custom Site Design picture folder.'
                    );
                }


                $existingFiles =
                    ag_site_design_custom_files(
                        $slotKey
                    );


                foreach (
                    $existingFiles
                    as
                    $existing
                ) {

                    ag_site_design_backup_existing(
                        $slotKey,
                        $existing,
                        'replace'
                    );
                }


                $temporaryTarget =
                    $target .
                    '.upload-' .
                    bin2hex(
                        random_bytes(3)
                    );


                if (
                    !@move_uploaded_file(
                        $tmp,
                        $temporaryTarget
                    )
                ) {

                    throw new RuntimeException(
                        'Could not stage the uploaded picture.'
                    );
                }


                $verify =
                    @getimagesize(
                        $temporaryTarget
                    );


                if (!is_array($verify)) {

                    @unlink(
                        $temporaryTarget
                    );

                    throw new RuntimeException(
                        'Staged picture verification failed.'
                    );
                }


                if (
                    is_file($target) &&
                    !@unlink($target)
                ) {

                    @unlink(
                        $temporaryTarget
                    );

                    throw new RuntimeException(
                        'Could not replace the current custom picture.'
                    );
                }


                if (
                    !@rename(
                        $temporaryTarget,
                        $target
                    )
                ) {

                    @unlink(
                        $temporaryTarget
                    );

                    throw new RuntimeException(
                        'Could not commit the uploaded picture.'
                    );
                }


                foreach (
                    $existingFiles
                    as
                    $existing
                ) {

                    if (
                        strcasecmp(
                            $existing,
                            $target
                        ) !== 0 &&
                        is_file($existing)
                    ) {

                        @unlink(
                            $existing
                        );
                    }
                }


                $success =
                    (string)
                    ($slot['label'] ?? $slotKey) .
                    ' uploaded successfully.';
            }
            catch (Throwable $e) {

                $error =
                    'Picture was not uploaded: ' .
                    $e->getMessage();
            }
        }


        elseif ($action === 'display') {

            try {

                $fit =
                    trim(
                        (string)
                        (
                            isset($_POST['fit'])
                                ? $_POST['fit']
                                : ''
                        )
                    );


                $position =
                    trim(
                        (string)
                        (
                            isset($_POST['position'])
                                ? $_POST['position']
                                : ''
                        )
                    );


                ag_site_design_save_display_settings(
                    $slotKey,
                    $fit,
                    $position
                );


                $success =
                    (string)
                    ($slot['label'] ?? $slotKey) .
                    ' display settings saved.';
            }
            catch (Throwable $e) {

                $error =
                    'Display settings were not saved: ' .
                    $e->getMessage();
            }
        }


        elseif ($action === 'reset-display') {

            try {

                $defaults =
                    ag_site_design_default_display_settings(
                        $slotKey
                    );


                ag_site_design_save_display_settings(
                    $slotKey,
                    (string)
                    $defaults['fit'],
                    (string)
                    $defaults['position']
                );


                $success =
                    (string)
                    ($slot['label'] ?? $slotKey) .
                    ' display settings reset to default.';
            }
            catch (Throwable $e) {

                $error =
                    'Display settings were not reset: ' .
                    $e->getMessage();
            }
        }


        else {

            $error =
                'Unknown Site Design action.';
        }
    }


    $_SESSION['ag_site_design_csrf'] =
        bin2hex(
            random_bytes(32)
        );

    $csrfToken =
        (string)
        $_SESSION['ag_site_design_csrf'];
}


$sections = [

    'LOGIN FRONT PAGE' => [
        'frontpage-background',
    ],

    'CONTROL CENTER BACKGROUNDS' => [
        'admin-control-center-background',
        'user-control-center-background',
    ],

];

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>
    Site &amp; Page Design
</title>

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-embedded-v1.css">

<style>

.design-shell{
    display:grid;
    gap:18px;
}

.design-title{
    padding:18px;
    background:
        linear-gradient(
            145deg,
            rgba(38,47,50,.96),
            rgba(7,10,11,.98)
        );
    border:
        1px solid
        rgba(218,184,92,.55);
    border-radius:12px;
}

.design-title h1{
    margin:0;
    color:#f0c85c;
    font-size:24px;
}

.design-title p{
    margin:8px 0 0;
    color:#c8ceca;
    font-size:13px;
    line-height:1.6;
}

.design-message{
    padding:13px 15px;
    border-radius:9px;
    font-size:13px;
    font-weight:700;
}

.design-message.success{
    color:#b8ffad;
    border:
        1px solid
        rgba(112,219,94,.45);
    background:
        rgba(58,139,46,.14);
}

.design-message.error{
    color:#ffb8b8;
    border:
        1px solid
        rgba(230,90,90,.48);
    background:
        rgba(165,42,42,.15);
}

.design-note{
    padding:13px 15px;
    color:#b8c0bb;
    border:
        1px solid
        rgba(255,255,255,.12);
    border-radius:9px;
    background:
        rgba(0,0,0,.22);
    font-size:12px;
    line-height:1.6;
}

.design-section{
    overflow:hidden;
    border:
        1px solid
        rgba(202,171,86,.42);
    border-radius:12px;
    background:
        rgba(7,10,11,.82);
}

.design-section-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    padding:15px 17px;
    border-bottom:
        1px solid
        rgba(202,171,86,.27);
}

.design-section-head h2{
    margin:0;
    color:#e4bc55;
    font-size:17px;
}

.design-section-count{
    color:#d9deda;
    font-size:11px;
    font-weight:800;
}

.design-grid{
    display:grid;
    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );
    gap:15px;
    padding:15px;
}

.design-section:first-of-type
.design-grid{
    grid-template-columns:1fr;
}

.design-card{
    min-width:0;
    overflow:hidden;
    border:
        1px solid
        rgba(255,255,255,.14);
    border-radius:11px;
    background:
        linear-gradient(
            145deg,
            rgba(41,48,50,.8),
            rgba(5,8,9,.95)
        );
}

/* AUSTRALIA SITE DESIGN 16X9 PREVIEW V1 */
.design-preview{
    position:relative;
    width:min(100%, 1100px);
    height:auto;
    aspect-ratio:16 / 9;
    margin:14px auto;
    overflow:hidden;

    border:
        1px solid
        rgba(224,189,93,.38);

    border-radius:10px;

    background:#020304;

    box-shadow:
        0 10px 30px rgba(0,0,0,.42);
}

.design-preview img{
    display:block;
    width:100%;
    height:100%;
    object-fit:cover;
}

.design-status{
    position:absolute;
    top:10px;
    right:10px;
    padding:6px 11px;
    border-radius:999px;
    font-size:10px;
    font-weight:900;
    letter-spacing:.08em;
}

.design-status.default{
    color:#17130a;
    background:#e0bd5d;
    border:1px solid #ffe79b;
}

.design-status.custom{
    color:#071008;
    background:#78d069;
    border:1px solid #b6f1a8;
}

.design-card-head{
    padding:15px 15px 8px;
}

.design-card-head h3{
    margin:0;
    color:#f0c85c;
    font-size:15px;
}

.design-key{
    margin-top:5px;
    color:#82908a;
    font:
        10px
        Consolas,
        monospace;
}

.design-card-body{
    display:grid;
    gap:11px;
    padding:0 15px 15px;
}

.design-description{
    color:#b7bfba;
    font-size:12px;
    line-height:1.5;
}

.design-meta{
    padding:9px 10px;
    border-radius:7px;
    color:#aeb8b2;
    background:
        rgba(255,255,255,.035);
    font-size:11px;
    line-height:1.55;
}

.design-meta strong{
    color:#eef2ef;
}

.design-form{
    display:grid;
    gap:8px;
}

.design-form input[type=file]{
    width:100%;
    padding:8px;
    color:#d8dedb;
    background:#080d10;
    border:
        1px solid
        rgba(255,255,255,.18);
    border-radius:7px;
}

.design-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.design-button{
    min-height:38px;
    padding:0 14px;
    cursor:pointer;
    border-radius:7px;
    border:
        1px solid
        rgba(255,255,255,.2);
    color:#e8ece9;
    background:
        linear-gradient(
            180deg,
            #30383a,
            #14191a
        );
    font-size:11px;
    font-weight:900;
}

.design-button.primary{
    color:#191307;
    border-color:#f0cf79;
    background:
        linear-gradient(
            180deg,
            #f2cb66,
            #b98519
        );
}

.design-button.restore{
    color:#ffd8d8;
    border-color:
        rgba(223,91,91,.45);
    background:
        rgba(125,32,32,.28);
}

.design-picture-summary{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:8px 10px;
}

.design-suitability{
    display:inline-flex;
    align-items:center;
    min-height:27px;
    padding:0 9px;
    border-radius:999px;
    border:
        1px solid
        rgba(255,255,255,.18);
    font-size:9px;
    font-weight:900;
    letter-spacing:.055em;
}

.design-suitability.good{
    color:#bff0cb;
    border-color:
        rgba(87,184,109,.45);
    background:
        rgba(42,112,60,.24);
}

.design-suitability.warning{
    color:#ffe0a0;
    border-color:
        rgba(218,164,61,.48);
    background:
        rgba(116,79,20,.28);
}

.design-suitability.bad{
    color:#ffd0d0;
    border-color:
        rgba(218,78,78,.46);
    background:
        rgba(118,31,31,.28);
}

.design-suitability.neutral{
    color:#d4dcda;
    border-color:
        rgba(255,255,255,.20);
    background:
        rgba(255,255,255,.055);
}

.design-current-display{
    color:#98a39d;
    font-size:10px;
    font-weight:800;
    letter-spacing:.035em;
}

.design-current-display strong{
    color:#e7ece9;
}

.design-secondary-form{
    margin:0;
}

.design-button.reset-display{
    color:#e6e9e7;
}

.design-button.view-page{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
    color:#f0c85c;
    border-color:
        rgba(221,187,91,.35);
}

.design-button.view-page:hover{
    text-decoration:none;
}


.design-recommendation{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:5px 8px;
    padding:10px 12px;
    border:
        1px solid
        rgba(221,187,91,.26);
    border-radius:8px;
    background:
        rgba(212,172,65,.055);
    color:#b9c1bc;
    font-size:11px;
    line-height:1.45;
}

.design-recommendation strong{
    color:#f0c85c;
}

.design-recommendation span{
    color:#8f9993;
}

.design-display-form{
    display:grid;
    gap:11px;
    padding:12px;
    border:
        1px solid
        rgba(255,255,255,.10);
    border-radius:8px;
    background:
        rgba(0,0,0,.18);
}

.design-display-grid{
    display:grid;
    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );
    gap:12px;
}

.design-display-grid label{
    display:grid;
    align-content:start;
    gap:7px;
}

.design-field-label{
    color:#dce2de;
    font-size:10px;
    font-weight:900;
    letter-spacing:.075em;
}

.design-display-grid select{
    width:100%;
    min-height:39px;
    padding:0 10px;
    color:#edf1ef;
    background:#080d10;
    border:
        1px solid
        rgba(255,255,255,.20);
    border-radius:7px;
    font-family:inherit;
    font-size:11px;
    font-weight:800;
    cursor:pointer;
}

.design-display-grid select:focus{
    outline:
        1px solid
        #e1bd59;
    outline-offset:1px;
}

.design-display-grid small{
    color:#7f8b85;
    font-size:10px;
    line-height:1.45;
}

@media(max-width:900px){

    .design-grid{
        grid-template-columns:1fr;
    }

    .design-display-grid{
        grid-template-columns:1fr;
    }

    .design-preview{
        width:100%;
        height:auto;
        aspect-ratio:16 / 9;
        margin:10px auto;
    }
}

</style>


<!-- AUSTRALIA SITE DESIGN RESTORE WARNING V1 -->
<style id="australia-site-design-restore-warning-v1">

.design-restore-overlay[hidden]{
    display:none !important;
}

.design-restore-overlay{
    position:fixed;
    inset:0;
    z-index:2147483000;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
    background:rgba(0,0,0,.78);
    backdrop-filter:blur(3px);
}

.design-restore-dialog{
    width:min(560px, calc(100vw - 40px));
    overflow:hidden;
    border:1px solid #9d7525;
    border-radius:12px;
    background:
        linear-gradient(
            180deg,
            #151813 0%,
            #080d10 38%,
            #020304 100%
        );
    box-shadow:
        0 24px 80px rgba(0,0,0,.78),
        0 0 0 1px rgba(237,196,93,.08) inset;
}

.design-restore-head{
    padding:18px 20px 15px;
    border-bottom:1px solid #625021;
    background:
        linear-gradient(
            180deg,
            rgba(44,47,39,.97),
            rgba(15,18,15,.97)
        );
}

.design-restore-kicker{
    margin:0 0 5px;
    color:#d5a83e;
    font-size:11px;
    font-weight:900;
    letter-spacing:.16em;
    text-transform:uppercase;
}

.design-restore-title{
    margin:0;
    color:#edc45d;
    font-size:20px;
    font-weight:900;
}

.design-restore-body{
    padding:20px;
    color:#e2dfd7;
    font-size:14px;
    line-height:1.55;
}

.design-restore-slot{
    margin:0 0 15px;
    padding:12px 14px;
    border:1px solid rgba(213,168,62,.34);
    border-radius:8px;
    background:rgba(157,117,37,.10);
}

.design-restore-slot strong{
    color:#edc45d;
}

.design-restore-text{
    margin:0 0 10px;
}

.design-restore-note{
    margin:0;
    color:#aeb3ad;
    font-size:13px;
}

.design-restore-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    padding:15px 20px 19px;
    border-top:1px solid rgba(98,80,33,.75);
}

.design-restore-button{
    min-width:130px;
    padding:10px 16px;
    border-radius:7px;
    font:inherit;
    font-size:12px;
    font-weight:900;
    cursor:pointer;
}

.design-restore-cancel{
    border:1px solid #566063;
    color:#e2dfd7;
    background:
        linear-gradient(
            180deg,
            #30383a,
            #14191a
        );
}

.design-restore-confirm{
    border:1px solid #edc45d;
    color:#171006;
    background:
        linear-gradient(
            180deg,
            #f0cb64,
            #ae7c17
        );
}

.design-restore-cancel:hover,
.design-restore-confirm:hover{
    filter:brightness(1.08);
}

</style>

</head>


<body>

<div class="design-shell">


    <div class="design-title">

        <h1>
            Site &amp; Page Design
        </h1>

        <p>
            Manage the Login Front Page, Admin Control Center
            and User Control Center background pictures.
        </p>

    </div>


    <?php if ($error !== ''): ?>

        <div class="design-message error">
            <?=ag_h($error)?>
        </div>

    <?php endif; ?>


    <?php if ($success !== ''): ?>

        <div class="design-message success">
            <?=ag_h($success)?>
        </div>

    <?php endif; ?>


    <div class="design-note">

        The supplied pictures remain untouched.
        Uploading a custom picture automatically overrides its default.
        RESTORE DEFAULT removes only the custom picture.
        JPG, PNG and WEBP are supported up to 15 MB.
        No Grid restart is required.

    </div>


    <?php foreach ($sections as $sectionName => $slotKeys): ?>

        <section class="design-section">

            <div class="design-section-head">

                <h2>
                    <?=ag_h($sectionName)?>
                </h2>

                <div class="design-section-count">

                    <?=count($slotKeys)?>

                    <?=count($slotKeys) === 1
                        ? 'PICTURE SLOT'
                        : 'PICTURE SLOTS'?>

                </div>

            </div>


            <div class="design-grid">


            <?php foreach ($slotKeys as $slotKey): ?>


                <?php

                    $slot =
                        ag_site_design_slot(
                            $slotKey
                        );

                    $info =
                        ag_site_design_asset_info(
                            $slotKey
                        );


                    $display =
                        ag_site_design_display_settings(
                            $slotKey
                        );


                    // AUSTRALIA SITE DESIGN SAFE PREVIEW FIT V1
                    //
                    // The real page may intentionally use "stretch",
                    // but the admin preview must never distort the image.
                    // Only the preview is changed here.
                    $previewFit =
                        (
                            (string)
                            $display['fit'] ===
                            'stretch'
                        )
                            ? 'contain'
                            : ag_site_design_preview_object_fit(
                                (string)
                                $display['fit']
                            );


                    $previewPosition =
                        ag_site_design_position_css(
                            (string)
                            $display['position']
                        );


                    $fitLabels = [
                        'fill' =>
                            'FILL SCREEN',

                        'whole' =>
                            'SHOW WHOLE PICTURE',

                        'stretch' =>
                            'STRETCH TO SCREEN',
                    ];


                    $positionLabels = [
                        'center' =>
                            'CENTER',

                        'top' =>
                            'TOP',

                        'bottom' =>
                            'BOTTOM',

                        'left' =>
                            'LEFT',

                        'right' =>
                            'RIGHT',
                    ];


                    $displayFitLabel =
                        isset(
                            $fitLabels[
                                (string)
                                $display['fit']
                            ]
                        )
                            ? $fitLabels[
                                (string)
                                $display['fit']
                            ]
                            : strtoupper(
                                (string)
                                $display['fit']
                            );


                    $displayPositionLabel =
                        isset(
                            $positionLabels[
                                (string)
                                $display['position']
                            ]
                        )
                            ? $positionLabels[
                                (string)
                                $display['position']
                            ]
                            : strtoupper(
                                (string)
                                $display['position']
                            );


                    $pictureWidth =
                        (int)
                        $info['width'];


                    $pictureHeight =
                        (int)
                        $info['height'];


                    $pictureRatio =
                        $pictureHeight > 0
                            ? (
                                $pictureWidth /
                                $pictureHeight
                            )
                            : 0.0;


                    if (
                        $pictureRatio > 0 &&
                        abs(
                            $pictureRatio -
                            (16 / 9)
                        ) <= 0.06
                    ) {

                        $aspectLabel =
                            '16:9';
                    }
                    elseif ($pictureRatio > 0) {

                        $aspectLabel =
                            number_format(
                                $pictureRatio,
                                2
                            ) .
                            ':1';
                    }
                    else {

                        $aspectLabel =
                            'UNKNOWN';
                    }


                    $suitabilityClass =
                        'neutral';


                    $suitabilityLabel =
                        'SIZE UNKNOWN';


                    if (
                        $pictureWidth > 0 &&
                        $pictureHeight > 0
                    ) {

                        if (
                            $pictureWidth < 1280 ||
                            $pictureHeight < 720
                        ) {

                            $suitabilityClass =
                                'bad';

                            $suitabilityLabel =
                                'LOW RESOLUTION';
                        }
                        elseif (
                            $pictureRatio < 1.40 ||
                            $pictureRatio > 2.10
                        ) {

                            $suitabilityClass =
                                'warning';

                            $suitabilityLabel =
                                'UNUSUAL ASPECT RATIO';
                        }
                        elseif (
                            $pictureWidth >= 1920 &&
                            $pictureHeight >= 1080 &&
                            abs(
                                $pictureRatio -
                                (16 / 9)
                            ) <= 0.10
                        ) {

                            $suitabilityClass =
                                'good';

                            $suitabilityLabel =
                                'GOOD FOR FULL SCREEN';
                        }
                        elseif (
                            $pictureWidth >= 1920 &&
                            $pictureHeight >= 1080
                        ) {

                            $suitabilityClass =
                                'good';

                            $suitabilityLabel =
                                'GOOD RESOLUTION';
                        }
                        else {

                            $suitabilityClass =
                                'neutral';

                            $suitabilityLabel =
                                'USABLE SIZE';
                        }
                    }


                    $viewUrl =
                        '/Other/';


                    $viewLabel =
                        'VIEW PAGE';


                    if (
                        $slotKey ===
                        'frontpage-background'
                    ) {

                        $viewUrl =
                            '/Other/index.php';

                        $viewLabel =
                            'VIEW LOGIN PAGE';
                    }
                    elseif (
                        $slotKey ===
                        'admin-control-center-background'
                    ) {

                        $viewUrl =
                            '/Other/admin-home.php';

                        $viewLabel =
                            'VIEW ADMIN CENTER';
                    }
                    elseif (
                        $slotKey ===
                        'user-control-center-background'
                    ) {

                        $viewUrl =
                            '/Other/FreshUserDashboardExact/user-dashboard.php';

                        $viewLabel =
                            'VIEW USER CENTER';
                    }

                ?>


                <article class="design-card">


                    <div class="design-preview">


                        <?php if ($info['exists']): ?>

                            <img
                                src="<?=ag_h(
                                    (string)
                                    $info['url']
                                )?>"
                                alt="<?=ag_h(
                                    (string)
                                    $slot['label']
                                )?>"

                                style="
                                    object-fit:<?=ag_h(
                                        $previewFit
                                    )?>;
                                    object-position:<?=ag_h(
                                        $previewPosition
                                    )?>;
                                ">

                        <?php endif; ?>


                        <div
                            class="design-status <?= $info['is_custom'] ? 'custom' : 'default' ?>">

                            <?=ag_h(
                                (string)
                                $info['status']
                            )?>

                        </div>


                    </div>


                    <div class="design-card-head">

                        <h3>
                            <?=ag_h(
                                (string)
                                $slot['label']
                            )?>
                        </h3>

                        <div class="design-key">
                            <?=ag_h($slotKey)?>
                        </div>

                    </div>


                    <div class="design-card-body">


                        <div class="design-description">

                            <?=ag_h(
                                (string)
                                $slot['description']
                            )?>

                        </div>


                        <div class="design-meta">

                            <strong>
                                <?=ag_h(
                                    (string)
                                    $info['filename']
                                )?>
                            </strong>

                            <br>

                            <?=ag_h(
                                (string)
                                $info['width'] .
                                ' x ' .
                                (string)
                                $info['height']
                            )?>

                            &nbsp; &bull; &nbsp;

                            <?=ag_h(
                                ag_site_design_human_bytes(
                                    (int)
                                    $info['bytes']
                                )
                            )?>

                            &nbsp; &bull; &nbsp;

                            ASPECT:
                            <strong>
                                <?=ag_h(
                                    $aspectLabel
                                )?>
                            </strong>

                            <br>

                            ACTIVE:
                            <strong>
                                <?=ag_h(
                                    (string)
                                    $info['status']
                                )?>
                            </strong>

                        </div>


                        <div class="design-picture-summary">

                            <span
                                class="design-suitability <?=ag_h(
                                    $suitabilityClass
                                )?>">

                                <?=ag_h(
                                    $suitabilityLabel
                                )?>

                            </span>


                            <span class="design-current-display">

                                DISPLAY:

                                <strong>
                                    <?=ag_h(
                                        $displayFitLabel
                                    )?>

                                    &nbsp; &bull; &nbsp;

                                    <?=ag_h(
                                        $displayPositionLabel
                                    )?>
                                </strong>

                            </span>

                        </div>


                        <div class="design-recommendation">

                            Recommended picture size:
                            <strong>
                                1920 × 1080 or larger
                            </strong>

                            <span>
                                16:9 works best.
                            </span>

                        </div>


                        <form
                            class="design-display-form"
                            method="post">


                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?=ag_h($csrfToken)?>">


                            <input
                                type="hidden"
                                name="action"
                                value="display">


                            <input
                                type="hidden"
                                name="slot"
                                value="<?=ag_h($slotKey)?>">


                            <div class="design-display-grid">


                                <label>

                                    <span class="design-field-label">
                                        PICTURE FIT
                                    </span>


                                    <select name="fit">


                                        <option
                                            value="fill"
                                            <?=(
                                                $display['fit'] ===
                                                'fill'
                                            )
                                                ? 'selected'
                                                : ''?>>

                                            FILL SCREEN

                                        </option>


                                        <option
                                            value="whole"
                                            <?=(
                                                $display['fit'] ===
                                                'whole'
                                            )
                                                ? 'selected'
                                                : ''?>>

                                            SHOW WHOLE PICTURE

                                        </option>


                                        <option
                                            value="stretch"
                                            <?=(
                                                $display['fit'] ===
                                                'stretch'
                                            )
                                                ? 'selected'
                                                : ''?>>

                                            STRETCH TO SCREEN

                                        </option>


                                    </select>


                                    <small>

                                        Fill keeps proportions but may crop.
                                        Whole shows every part of the picture.
                                        Stretch forces it to the screen size.

                                    </small>

                                </label>


                                <label>

                                    <span class="design-field-label">
                                        PICTURE POSITION
                                    </span>


                                    <select name="position">


                                        <option
                                            value="center"
                                            <?=(
                                                $display['position'] ===
                                                'center'
                                            )
                                                ? 'selected'
                                                : ''?>>

                                            CENTER

                                        </option>


                                        <option
                                            value="top"
                                            <?=(
                                                $display['position'] ===
                                                'top'
                                            )
                                                ? 'selected'
                                                : ''?>>

                                            TOP

                                        </option>


                                        <option
                                            value="bottom"
                                            <?=(
                                                $display['position'] ===
                                                'bottom'
                                            )
                                                ? 'selected'
                                                : ''?>>

                                            BOTTOM

                                        </option>


                                        <option
                                            value="left"
                                            <?=(
                                                $display['position'] ===
                                                'left'
                                            )
                                                ? 'selected'
                                                : ''?>>

                                            LEFT

                                        </option>


                                        <option
                                            value="right"
                                            <?=(
                                                $display['position'] ===
                                                'right'
                                            )
                                                ? 'selected'
                                                : ''?>>

                                            RIGHT

                                        </option>


                                    </select>


                                    <small>

                                        Controls which part of the picture
                                        stays visible when cropping occurs.

                                    </small>

                                </label>


                            </div>


                            <div class="design-actions">

                                <button
                                    type="submit"
                                    class="design-button">

                                    APPLY DISPLAY SETTINGS

                                </button>

                            </div>


                        </form>

                        <form
                            class="design-secondary-form"
                            method="post">


                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?=ag_h($csrfToken)?>">


                            <input
                                type="hidden"
                                name="action"
                                value="reset-display">


                            <input
                                type="hidden"
                                name="slot"
                                value="<?=ag_h($slotKey)?>">


                            <div class="design-actions">

                                <button
                                    type="submit"
                                    class="design-button reset-display">

                                    RESET DISPLAY SETTINGS

                                </button>


                                <a
                                    class="design-button view-page"
                                    href="<?=ag_h(
                                        $viewUrl
                                    )?>"
                                    target="_blank"
                                    rel="noopener noreferrer">

                                    <?=ag_h(
                                        $viewLabel
                                    )?>

                                </a>

                            </div>


                        </form>

                        <form
                            class="design-form"
                            method="post"
                            enctype="multipart/form-data">


                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?=ag_h($csrfToken)?>">


                            <input
                                type="hidden"
                                name="action"
                                value="upload">


                            <input
                                type="hidden"
                                name="slot"
                                value="<?=ag_h($slotKey)?>">


                            <input
                                type="file"
                                name="picture"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                required>


                            <div class="design-actions">

                                <button
                                    type="submit"
                                    class="design-button primary">

                                    <?= $info['is_custom']
                                        ? 'REPLACE PICTURE'
                                        : 'UPLOAD CUSTOM PICTURE' ?>

                                </button>

                            </div>


                        </form>


                        <?php if ($info['is_custom']): ?>


                            <form
                                class="design-form"
                                method="post">


                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?=ag_h($csrfToken)?>">


                                <input
                                    type="hidden"
                                    name="action"
                                    value="remove">


                                <input
                                    type="hidden"
                                    name="slot"
                                    value="<?=ag_h($slotKey)?>">


                                <div class="design-actions">

                                    <button
                                        type="submit"
                                        class="design-button restore"
                                        data-site-design-restore="1">

                                        RESTORE DEFAULT

                                    </button>

                                </div>


                            </form>


                        <?php endif; ?>


                    </div>


                </article>


            <?php endforeach; ?>


            </div>


        </section>


    <?php endforeach; ?>


</div>


<!-- AUSTRALIA SITE DESIGN RESTORE WARNING V1 BODY -->

<div
    class="design-restore-overlay"
    id="design-restore-warning"
    hidden>

    <div
        class="design-restore-dialog"
        role="dialog"
        aria-modal="true">

        <div class="design-restore-head">

            <div class="design-restore-kicker">
                SECURITY WARNING
            </div>

            <h2 class="design-restore-title">
                RESTORE DEFAULT PICTURE
            </h2>

        </div>

        <div class="design-restore-body">

            <p class="design-restore-slot">
                Picture:
                <strong id="design-restore-name">
                    Site Design Picture
                </strong>
            </p>

            <p class="design-restore-text">
                This will remove the custom picture and restore
                the supplied default picture.
            </p>

            <p class="design-restore-note">
                The current custom picture will be backed up
                before it is removed.
            </p>

        </div>

        <div class="design-restore-actions">

            <button
                type="button"
                class="design-restore-button design-restore-cancel"
                id="design-restore-cancel">
                CANCEL
            </button>

            <button
                type="button"
                class="design-restore-button design-restore-confirm"
                id="design-restore-confirm">
                RESTORE DEFAULT
            </button>

        </div>

    </div>

</div>


<script>
(function () {
    'use strict';


    var overlay =
        document.getElementById(
            'design-restore-warning'
        );


    var nameBox =
        document.getElementById(
            'design-restore-name'
        );


    var cancelButton =
        document.getElementById(
            'design-restore-cancel'
        );


    var confirmButton =
        document.getElementById(
            'design-restore-confirm'
        );


    var pendingForm =
        null;


    function closeWarning()
    {
        overlay.hidden =
            true;

        pendingForm =
            null;
    }


    function slotLabel(
        form
    ) {

        var card =
            form.closest(
                'article'
            );


        if (card) {

            var heading =
                card.querySelector(
                    'h2, h3, .design-title, .design-card-title'
                );


            if (
                heading &&
                heading.textContent.trim()
            ) {

                return heading.textContent.trim();
            }
        }


        var slot =
            form.querySelector(
                'input[name="slot"]'
            );


        var key =
            slot
                ? String(slot.value || '')
                : '';


        var labels = {
            'frontpage-background':
                'Login Front Page Picture',

            'admin-control-center-background':
                'Admin Control Center Background',

            'user-control-center-background':
                'User Control Center Background'
        };


        return (
            labels[key] ||
            'Site Design Picture'
        );
    }


    document.addEventListener(
        'click',
        function (event) {

            var button =
                event.target.closest(
                    '[data-site-design-restore]'
                );


            if (!button) {
                return;
            }


            var form =
                button.closest(
                    'form'
                );


            if (!form) {
                return;
            }


            event.preventDefault();


            pendingForm =
                form;


            nameBox.textContent =
                slotLabel(
                    form
                );


            overlay.hidden =
                false;


            cancelButton.focus();
        }
    );


    cancelButton.addEventListener(
        'click',
        closeWarning
    );


    confirmButton.addEventListener(
        'click',
        function () {

            var form =
                pendingForm;


            closeWarning();


            if (form) {

                HTMLFormElement.prototype.submit.call(
                    form
                );
            }
        }
    );


    overlay.addEventListener(
        'click',
        function (event) {

            if (
                event.target ===
                overlay
            ) {

                closeWarning();
            }
        }
    );


    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                !overlay.hidden
            ) {

                event.preventDefault();

                closeWarning();
            }
        }
    );

})();
</script>

</body>

</html>


