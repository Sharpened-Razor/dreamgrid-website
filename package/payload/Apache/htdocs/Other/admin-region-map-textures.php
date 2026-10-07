<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/region-map-textures.php';

if (
    session_status() !==
    PHP_SESSION_ACTIVE
) {

    session_start();
}

ag_require_admin();
ag_no_cache();

function rmt_h($value): string
{
    return
        htmlspecialchars(
            (string)$value,
            ENT_QUOTES |
            ENT_SUBSTITUTE,
            'UTF-8'
        );
}


if (
    empty(
        $_SESSION['ag_rmt_csrf']
    )
) {

    $_SESSION['ag_rmt_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    (string)
    $_SESSION['ag_rmt_csrf'];


$regions =
    ag_rmt_regions();


$regionIndex = [];

foreach ($regions as $region) {

    $regionIndex[
        strtolower(
            (string)$region['uuid']
        )
    ] =
        $region;
}


if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') ===
    'POST'
) {

    $postedCsrf =
        (string)(
            $_POST['csrf'] ??
            ''
        );

    $action =
        trim(
            (string)(
                $_POST['action'] ??
                ''
            )
        );

    $uuid =
        strtolower(
            trim(
                (string)(
                    $_POST['region_uuid'] ??
                    ''
                )
            )
        );

    $flashType =
        'error';

    $flashMessage =
        '';

    try {

        if (
            $postedCsrf === '' ||
            !hash_equals(
                $csrfToken,
                $postedCsrf
            )
        ) {

            throw new RuntimeException(
                'Your security token expired. Reload the page and try again.'
            );
        }

        if (
            !ag_rmt_valid_uuid($uuid) ||
            !isset(
                $regionIndex[$uuid]
            )
        ) {

            throw new RuntimeException(
                'Unknown or inactive region.'
            );
        }

        if ($action === 'restore') {

            $existingFiles =
                ag_rmt_custom_files(
                    $uuid
                );

            if (!$existingFiles) {

                $flashType =
                    'success';

                $flashMessage =
                    'This region is already using its native OpenSim map image.';
            }
            else {

                foreach (
                    $existingFiles
                    as $existing
                ) {

                    ag_rmt_backup_existing(
                        $uuid,
                        $existing,
                        'restore-default'
                    );
                }

                foreach (
                    $existingFiles
                    as $existing
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

                $flashType =
                    'success';

                $flashMessage =
                    'Custom region map texture removed. Native OpenSim map image is active.';
            }
        }
        elseif ($action === 'upload') {

            if (
                !isset(
                    $_FILES['texture']
                ) ||
                !is_array(
                    $_FILES['texture']
                )
            ) {

                throw new RuntimeException(
                    'Choose an image first.'
                );
            }

            $upload =
                $_FILES['texture'];

            $uploadError =
                (int)(
                    $upload['error'] ??
                    UPLOAD_ERR_NO_FILE
                );

            if (
                $uploadError !==
                UPLOAD_ERR_OK
            ) {

                throw new RuntimeException(
                    'The image upload did not complete successfully.'
                );
            }

            $tmp =
                (string)(
                    $upload['tmp_name'] ??
                    ''
                );

            $bytes =
                (int)(
                    $upload['size'] ??
                    0
                );

            if (
                $bytes <= 0 ||
                $bytes >
                15 * 1024 * 1024
            ) {

                throw new RuntimeException(
                    'Image must be larger than 0 bytes and no more than 15 MB.'
                );
            }

            if (
                $tmp === '' ||
                !is_uploaded_file(
                    $tmp
                )
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
                    (string)(
                        $image['mime'] ??
                        ''
                    )
                );

            $mimeToExtension = [
                'image/jpeg' =>
                    'jpg',

                'image/png' =>
                    'png',

                'image/webp' =>
                    'webp'
            ];

            if (
                !isset(
                    $mimeToExtension[$mime]
                )
            ) {

                throw new RuntimeException(
                    'Only JPG/JPEG, PNG and WEBP images are allowed.'
                );
            }

            $extension =
                $mimeToExtension[$mime];

            $customRoot =
                ag_rmt_custom_root();

            if (
                !is_dir($customRoot) &&
                !@mkdir(
                    $customRoot,
                    0770,
                    true
                )
            ) {

                throw new RuntimeException(
                    'Could not create the region map texture directory.'
                );
            }

            $existingFiles =
                ag_rmt_custom_files(
                    $uuid
                );

            foreach (
                $existingFiles
                as $existing
            ) {

                ag_rmt_backup_existing(
                    $uuid,
                    $existing,
                    'replace'
                );
            }

            $target =
                $customRoot .
                '/' .
                $uuid .
                '.' .
                $extension;

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
                    'Could not stage the uploaded region texture.'
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
                    'Staged image verification failed.'
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
                    'Could not replace the current region texture.'
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
                    'Could not commit the uploaded region texture.'
                );
            }

            foreach (
                $existingFiles
                as $existing
            ) {

                if (
                    strcasecmp(
                        $existing,
                        $target
                    ) !== 0 &&
                    is_file(
                        $existing
                    )
                ) {

                    @unlink(
                        $existing
                    );
                }
            }

            $flashType =
                'success';

            $flashMessage =
                'Custom map texture uploaded for ' .
                (string)$regionIndex[$uuid]['name'] .
                '.';
        }
        else {

            throw new RuntimeException(
                'Unknown texture action.'
            );
        }
    }
    catch (Throwable $e) {

        $flashType =
            'error';

        $flashMessage =
            $e->getMessage();
    }


    /*
     * AUSTRALIA RMT AJAX RESPONSE V1
     */

    $isAjax =
        (
            (string)(
                $_POST['ajax'] ??
                ''
            ) === '1'
        );


    if ($isAjax) {

        $regionForAjax =
            $regionIndex[$uuid] ??
            null;


        $infoForAjax = [
            'exists' =>
                false,

            'url' =>
                '',

            'filename' =>
                '',

            'bytes' =>
                0,

            'width' =>
                0,

            'height' =>
                0,

            'modified' =>
                0
        ];


        if (
            is_array($regionForAjax) &&
            ag_rmt_valid_uuid($uuid)
        ) {

            $infoForAjax =
                ag_rmt_custom_info(
                    $uuid
                );
        }


        $customCountForAjax =
            0;


        foreach (
            $regions
            as $regionForCount
        ) {

            if (
                ag_rmt_custom_file(
                    (string)$regionForCount['uuid']
                ) !== null
            ) {

                $customCountForAjax++;
            }
        }


        $hasCustomForAjax =
            !empty(
                $infoForAjax['exists']
            );


        $displayImage =
            $hasCustomForAjax
                ? (string)$infoForAjax['filename']
                : 'Native OpenSim map';


        $displayDimensions =
            $hasCustomForAjax
                ? (
                    (string)$infoForAjax['width'] .
                    ' × ' .
                    (string)$infoForAjax['height']
                )
                : 'Live region map';


        $displayBytes =
            $hasCustomForAjax
                ? ag_rmt_format_bytes(
                    (int)$infoForAjax['bytes']
                )
                : 'DEFAULT';


        $displayChanged =
            (
                $hasCustomForAjax &&
                !empty(
                    $infoForAjax['modified']
                )
            )
                ? date(
                    'Y-m-d H:i:s',
                    (int)$infoForAjax['modified']
                )
                : 'Live OpenSim';


        $payload = [
            'ok' =>
                (
                    $flashType ===
                    'success'
                ),

            'type' =>
                $flashType,

            'message' =>
                $flashMessage,

            'uuid' =>
                $uuid,

            'state' =>
                $hasCustomForAjax
                    ? 'CUSTOM'
                    : 'DEFAULT',

            'custom_count' =>
                $customCountForAjax,

            'custom' => [
                'exists' =>
                    $hasCustomForAjax,

                'url' =>
                    (
                        $hasCustomForAjax
                            ? (string)$infoForAjax['url']
                            : ''
                    ),

                'filename' =>
                    (
                        $hasCustomForAjax
                            ? (string)$infoForAjax['filename']
                            : ''
                    ),

                'width' =>
                    (
                        $hasCustomForAjax
                            ? (int)$infoForAjax['width']
                            : 0
                    ),

                'height' =>
                    (
                        $hasCustomForAjax
                            ? (int)$infoForAjax['height']
                            : 0
                    )
            ],

            'display' => [
                'image' =>
                    $displayImage,

                'dimensions' =>
                    $displayDimensions,

                'file_size' =>
                    $displayBytes,

                'last_changed' =>
                    $displayChanged
            ]
        ];


        header(
            'Content-Type: application/json; charset=utf-8'
        );


        http_response_code(
            (
                $flashType ===
                'success'
            )
                ? 200
                : 400
        );


        echo json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES
        );


        exit;
    }

    $_SESSION['ag_rmt_flash'] = [
        'type' =>
            $flashType,

        'message' =>
            $flashMessage
    ];


    header(
        'Location: /Other/admin-region-map-textures.php'
    );

    exit;
}


$flash =
    $_SESSION['ag_rmt_flash'] ??
    null;

unset(
    $_SESSION['ag_rmt_flash']
);


$activeUuids =
    [];

foreach ($regions as $region) {

    $activeUuids[] =
        (string)$region['uuid'];
}


$orphans =
    ag_rmt_orphan_files(
        $activeUuids
    );

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Region Map Textures</title>

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-panel-v1.css">

<style>

/*
 ============================================================
 REGION MAP TEXTURES - CHARCOAL V2
 Presentation only.
 Region discovery / UUID / upload / restore remain untouched.
 ============================================================
*/

*{
    box-sizing:border-box;
}

html,
body{
    margin:0;
    min-height:100%;
    background:transparent;
    color:var(--cp-text,#e2dfd7);
}

body{
    overflow-x:hidden;
}


/* PAGE */

.rmt-page{
    width:100%;
    max-width:none;
    margin:0;
    padding:16px 16px 28px;
}


/* HERO */

.rmt-hero{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:22px;

    padding:20px 22px;

    border:1px solid rgba(218,184,92,.55);
    border-radius:13px;

    background:
        linear-gradient(
            180deg,
            rgba(38,47,50,.96),
            rgba(7,10,11,.98)
        );

    box-shadow:
        inset 0 1px 0 rgba(255,244,185,.10),
        0 10px 26px rgba(0,0,0,.42);
}

.rmt-kicker{
    margin-bottom:6px;
    color:var(--cp-gold3,#edc45d);
    font-size:11px;
    font-weight:900;
    letter-spacing:.16em;
    text-transform:uppercase;
}

.rmt-title{
    margin:0;
    color:#f1f4f2;
    font-size:30px;
    line-height:1.08;
}

.rmt-intro{
    max-width:920px;
    margin:10px 0 0;
    color:#aeb8b2;
    font-size:13px;
    line-height:1.5;
}

.rmt-refresh{
    appearance:none;
    flex:0 0 auto;
    min-height:40px;
    padding:10px 17px;

    border:1px solid #e8c65e;
    border-radius:7px;

    background:
        linear-gradient(
            180deg,
            #f0cb64,
            #ae7c17
        );

    color:#1d1608;
    font-size:11px;
    font-weight:900;

    cursor:pointer;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.25),
        0 3px 9px rgba(0,0,0,.4);
}

.rmt-refresh:hover{
    filter:brightness(1.08);
}


/* SUMMARY */

.rmt-summary{
    display:grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0,1fr)
        );

    gap:11px;
    margin:12px 0;
}

.rmt-summary-card{
    padding:14px 16px;

    border:1px solid rgba(202,171,86,.34);
    border-radius:10px;

    background:
        linear-gradient(
            180deg,
            #151813,
            #090b09
        );

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.035),
        0 6px 15px rgba(0,0,0,.28);
}

.rmt-summary-label{
    color:#8f9993;
    font-size:10px;
    font-weight:900;
    letter-spacing:.12em;
}

.rmt-summary-value{
    margin-top:3px;
    color:#f1f4f2;
    font-size:23px;
    font-weight:900;
}


/* FLASH */

.rmt-flash{
    margin:12px 0;
    padding:12px 14px;
    border-radius:8px;
    font-weight:750;
}

.rmt-flash.success{
    border:1px solid rgba(82,177,102,.35);
    background:rgba(38,102,52,.22);
    color:#c9f3d1;
}

.rmt-flash.error{
    border:1px solid rgba(211,79,79,.42);
    background:rgba(111,31,31,.25);
    color:#ffd0d0;
}


/* REGION GRID */

.rmt-grid{
    display:grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(410px,1fr)
        );

    gap:12px;

    width:100%;
}


/* REGION CARD */

.rmt-card{
    min-width:0;
    overflow:hidden;

    border:1px solid rgba(202,171,86,.42);
    border-radius:12px;

    background:
        linear-gradient(
            180deg,
            #121510,
            #080b09
        );

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.035),
        0 9px 22px rgba(0,0,0,.38);
}


/* PREVIEW */

.rmt-preview{
    position:relative;

    width:100%;
    min-height:0;

    display:flex;
    align-items:stretch;
    justify-content:stretch;

    overflow:hidden;

    background:#020304;

    border-bottom:
        1px solid
        rgba(202,171,86,.34);
}


/*
 CUSTOM IMAGE
 Fill the complete preview instead of leaving empty background.
*/

.rmt-preview > img{
    display:block;

    width:100%;
    height:100%;

    min-width:0;
    min-height:0;

    object-fit:cover;
    object-position:center center;
}


/*
 DEFAULT NATIVE OPENSIM MOSAIC
 Fill the complete preview as well.
*/

.rmt-native-grid{
    width:100%;
    height:100%;

    min-width:0;
    min-height:0;

    display:grid;
    align-self:stretch;

    overflow:hidden;

    background:#020304;
}

.rmt-native-grid img{
    display:block;

    width:100%;
    height:100%;

    min-width:0;
    min-height:0;

    object-fit:cover;
    object-position:center center;
}


/* STATUS */

.rmt-status{
    position:absolute;
    top:9px;
    right:9px;
    z-index:2;

    padding:6px 9px;

    border-radius:999px;

    font-size:9px;
    font-weight:900;
    letter-spacing:.11em;

    backdrop-filter:blur(7px);

    box-shadow:
        0 3px 8px rgba(0,0,0,.45);
}

.rmt-status.default{
    color:#d1d7d4;

    border:
        1px solid
        rgba(255,255,255,.18);

    background:
        rgba(27,31,29,.92);
}

.rmt-status.custom{
    color:#f4dc95;

    border:
        1px solid
        rgba(218,164,61,.48);

    background:
        rgba(116,79,20,.92);
}


/* CARD BODY */

.rmt-body{
    padding:15px;
}

.rmt-region-name{
    margin:0;

    color:#edf1ef;

    font-size:19px;
    font-weight:850;
    line-height:1.2;
}

.rmt-meta{
    display:grid;
    grid-template-columns:1fr 1fr;

    gap:7px;
    margin-top:12px;
}

.rmt-meta-item{
    min-width:0;

    padding:8px 9px;

    border:
        1px solid
        rgba(255,255,255,.10);

    border-radius:7px;

    background:
        rgba(255,255,255,.025);
}

.rmt-meta-label{
    color:#89958e;

    font-size:9px;
    font-weight:900;
    letter-spacing:.09em;

    text-transform:uppercase;
}

.rmt-meta-value{
    margin-top:3px;

    color:#dce3df;

    font-size:12px;

    overflow-wrap:anywhere;
}

.rmt-uuid{
    grid-column:1 / -1;
}


/* ACTIONS */

.rmt-actions{
    margin-top:12px;

    display:flex;
    gap:7px;
    flex-wrap:wrap;
}

.rmt-upload-form{
    display:flex;
    gap:7px;

    flex:1 1 100%;

    flex-wrap:wrap;
}

.rmt-file{
    flex:1 1 210px;
    min-width:0;

    padding:7px;

    border:
        1px solid
        rgba(255,255,255,.17);

    border-radius:7px;

    background:#080d10;

    color:#d8dedb;
}

.rmt-file::file-selector-button{
    margin-right:8px;

    padding:7px 10px;

    border:
        1px solid
        rgba(255,255,255,.18);

    border-radius:6px;

    background:
        linear-gradient(
            180deg,
            #30383a,
            #14191a
        );

    color:#e6ebe8;

    font-weight:800;

    cursor:pointer;
}


.rmt-button{
    appearance:none;

    min-height:37px;

    padding:9px 13px;

    border:1px solid #e8c65e;
    border-radius:7px;

    background:
        linear-gradient(
            180deg,
            #f0cb64,
            #ae7c17
        );

    color:#1d1608;

    font-size:11px;
    font-weight:900;

    cursor:pointer;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.22),
        0 3px 8px rgba(0,0,0,.34);
}

.rmt-button:hover{
    filter:brightness(1.08);
}

.rmt-button.restore{
    border-color:
        rgba(255,255,255,.18);

    background:
        linear-gradient(
            180deg,
            #30383a,
            #14191a
        );

    color:#e6ebe8;
}

.rmt-button.restore:hover{
    border-color:
        rgba(221,187,91,.35);

    color:#f0c85c;
}


/* EMPTY / ORPHANS */

.rmt-empty{
    padding:26px;

    border:
        1px dashed
        rgba(255,255,255,.16);

    border-radius:11px;

    background:#0a0e10;

    color:#aeb8b2;

    text-align:center;
}

.rmt-orphans{
    margin-top:18px;
    padding:17px;

    border:
        1px solid
        rgba(202,171,86,.30);

    border-radius:11px;

    background:
        linear-gradient(
            180deg,
            #10130f,
            #080a08
        );
}

.rmt-orphans h2{
    margin:0 0 7px;

    color:#edf1ef;

    font-size:18px;
}

.rmt-orphans p{
    margin:0 0 12px;

    color:#98a39d;

    font-size:12px;
    line-height:1.5;
}

.rmt-orphan-row{
    display:flex;
    justify-content:space-between;

    gap:15px;

    padding:8px 0;

    border-top:
        1px solid
        rgba(255,255,255,.08);

    color:#d7ded9;

    font-size:12px;
}

.rmt-orphan-row:first-of-type{
    border-top:0;
}


/* RESPONSIVE */

@media(max-width:1450px){

    .rmt-grid{
        grid-template-columns:
            repeat(
                auto-fit,
                minmax(360px,1fr)
            );
    }
}


@media(max-width:760px){

    .rmt-page{
        padding:11px;
    }

    .rmt-hero{
        display:block;
        padding:17px;
    }

    .rmt-refresh{
        width:100%;
        margin-top:14px;
    }

    .rmt-summary{
        grid-template-columns:1fr;
    }

    .rmt-grid{
        grid-template-columns:1fr;
    }

    .rmt-meta{
        grid-template-columns:1fr;
    }

    .rmt-uuid{
        grid-column:auto;
    }

    .rmt-file{
        flex-basis:100%;
    }

    .rmt-button{
        width:100%;
    }
}

</style>

<!-- AUSTRALIA REGION MAP TEXTURES AJAX V1 -->
<link
    rel="stylesheet"
    href="/Other/assets/css/region-map-textures-ajax-v1.css?v=1">

</head>

<body>

<div class="rmt-page">

    <section class="rmt-hero">

        <div>

            <div class="rmt-kicker">
                REGION MAP MANAGEMENT
            </div>

            <h1 class="rmt-title">
                Region Map Textures
            </h1>

            <p class="rmt-intro">
                Regions are discovered automatically from the live
                DreamGrid/OpenSim region configuration. Custom map
                textures are stored against the Region UUID, so adding,
                removing, renaming or moving regions does not require
                creating numbered texture slots.
            </p>

        </div>

        <button
            class="rmt-refresh"
            type="button"
            onclick="location.reload();">

            REFRESH REGIONS

        </button>

    </section>


    <section class="rmt-summary">

        <div class="rmt-summary-card">

            <div class="rmt-summary-label">
                ACTIVE REGIONS
            </div>

            <div class="rmt-summary-value">
                <?=count($regions)?>
            </div>

        </div>


        <div class="rmt-summary-card">

            <div class="rmt-summary-label">
                CUSTOM TEXTURES
            </div>

            <div class="rmt-summary-value">
<?php

$customCount = 0;

foreach ($regions as $region) {

    if (
        ag_rmt_custom_file(
            (string)$region['uuid']
        ) !== null
    ) {

        $customCount++;
    }
}

?>
                <?=$customCount?>
            </div>

        </div>


        <div class="rmt-summary-card">

            <div class="rmt-summary-label">
                UNUSED TEXTURES
            </div>

            <div class="rmt-summary-value">
                <?=count($orphans)?>
            </div>

        </div>

    </section>


<?php if (is_array($flash)): ?>

    <div
        class="rmt-flash <?=rmt_h((string)($flash['type'] ?? 'error'))?>">

        <?=rmt_h((string)($flash['message'] ?? ''))?>

    </div>

<?php endif; ?>


<?php if (!$regions): ?>

    <div class="rmt-empty">

        No valid region INI files with RegionUUID and Location
        were found.

    </div>

<?php else: ?>

    <section class="rmt-grid">

<?php foreach ($regions as $region): ?>

<?php

$uuid =
    (string)$region['uuid'];

$info =
    ag_rmt_custom_info(
        $uuid
    );

$cellsX =
    max(
        1,
        (int)$region['cells_x']
    );

$cellsY =
    max(
        1,
        (int)$region['cells_y']
    );

?>

        <article
            class="rmt-card"
            data-region-uuid="<?=rmt_h($uuid)?>"
            data-region-name="<?=rmt_h((string)$region['name'])?>"
            data-region-x="<?=rmt_h((string)$region['x'])?>"
            data-region-y="<?=rmt_h((string)$region['y'])?>"
            data-cells-x="<?=rmt_h((string)$cellsX)?>"
            data-cells-y="<?=rmt_h((string)$cellsY)?>">

            <div
                class="rmt-preview"
                style="aspect-ratio:<?=$cellsX?> / <?=$cellsY?>;">

<?php if ($info['exists']): ?>

                <img
                    src="<?=rmt_h((string)$info['url'])?>"
                    alt="<?=rmt_h((string)$region['name'])?> custom map texture">

                <span class="rmt-status custom">
                    CUSTOM
                </span>

<?php else: ?>

                <div
                    class="rmt-native-grid"
                    style="
                        grid-template-columns:
                            repeat(<?=$cellsX?>,1fr);
                        grid-template-rows:
                            repeat(<?=$cellsY?>,1fr);
                        aspect-ratio:
                            <?=$cellsX?> / <?=$cellsY?>;
                    ">

<?php

for (
    $dy = $cellsY - 1;
    $dy >= 0;
    $dy--
) {

    for (
        $dx = 0;
        $dx < $cellsX;
        $dx++
    ) {

        $tileX =
            (int)$region['x'] +
            $dx;

        $tileY =
            (int)$region['y'] +
            $dy;

?>

                    <img
                        src="/Other/region-map-native-tile.php?x=<?=$tileX?>&amp;y=<?=$tileY?>"
                        alt=""
                        loading="lazy">

<?php

    }
}

?>

                </div>

                <span class="rmt-status default">
                    DEFAULT
                </span>

<?php endif; ?>

            </div>


            <div class="rmt-body">

                <h2 class="rmt-region-name">
                    <?=rmt_h((string)$region['name'])?>
                </h2>


                <div class="rmt-meta">

                    <div class="rmt-meta-item">

                        <div class="rmt-meta-label">
                            GRID LOCATION
                        </div>

                        <div class="rmt-meta-value">
                            <?=rmt_h((string)$region['x'])?>,
                            <?=rmt_h((string)$region['y'])?>
                        </div>

                    </div>


                    <div class="rmt-meta-item">

                        <div class="rmt-meta-label">
                            REGION SIZE
                        </div>

                        <div class="rmt-meta-value">
                            <?=rmt_h((string)$region['size_x'])?>
                            ×
                            <?=rmt_h((string)$region['size_y'])?>
                        </div>

                    </div>


                    <div class="rmt-meta-item rmt-uuid">

                        <div class="rmt-meta-label">
                            REGION UUID
                        </div>

                        <div class="rmt-meta-value">
                            <?=rmt_h($uuid)?>
                        </div>

                    </div>


                    <div class="rmt-meta-item">

                        <div class="rmt-meta-label">
                            IMAGE
                        </div>

                        <div class="rmt-meta-value">

<?php if ($info['exists']): ?>

                            <?=rmt_h((string)$info['filename'])?>

<?php else: ?>

                            Native OpenSim map

<?php endif; ?>

                        </div>

                    </div>


                    <div class="rmt-meta-item">

                        <div class="rmt-meta-label">
                            DIMENSIONS
                        </div>

                        <div class="rmt-meta-value">

<?php if ($info['exists']): ?>

                            <?=rmt_h((string)$info['width'])?>
                            ×
                            <?=rmt_h((string)$info['height'])?>

<?php else: ?>

                            Live region map

<?php endif; ?>

                        </div>

                    </div>


                    <div class="rmt-meta-item">

                        <div class="rmt-meta-label">
                            FILE SIZE
                        </div>

                        <div class="rmt-meta-value">

<?php if ($info['exists']): ?>

                            <?=rmt_h(
                                ag_rmt_format_bytes(
                                    (int)$info['bytes']
                                )
                            )?>

<?php else: ?>

                            DEFAULT

<?php endif; ?>

                        </div>

                    </div>


                    <div class="rmt-meta-item">

                        <div class="rmt-meta-label">
                            LAST CHANGED
                        </div>

                        <div class="rmt-meta-value">

<?php if ($info['exists'] && $info['modified']): ?>

                            <?=rmt_h(
                                date(
                                    'Y-m-d H:i:s',
                                    (int)$info['modified']
                                )
                            )?>

<?php else: ?>

                            Live OpenSim

<?php endif; ?>

                        </div>

                    </div>

                </div>


                <div class="rmt-actions">

                    <form
                        class="rmt-upload-form"
                        method="post"
                        enctype="multipart/form-data">

                        <input
                            type="hidden"
                            name="csrf"
                            value="<?=rmt_h($csrfToken)?>">

                        <input
                            type="hidden"
                            name="action"
                            value="upload">

                        <input
                            type="hidden"
                            name="region_uuid"
                            value="<?=rmt_h($uuid)?>">

                        <input
                            class="rmt-file"
                            type="file"
                            name="texture"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required>

                        <button
                            class="rmt-button"
                            type="submit">

<?php if ($info['exists']): ?>

                            REPLACE CUSTOM

<?php else: ?>

                            UPLOAD CUSTOM

<?php endif; ?>

                        </button>

                    </form>


<?php if ($info['exists']): ?>

                    <form method="post">

                        <input
                            type="hidden"
                            name="csrf"
                            value="<?=rmt_h($csrfToken)?>">

                        <input
                            type="hidden"
                            name="action"
                            value="restore">

                        <input
                            type="hidden"
                            name="region_uuid"
                            value="<?=rmt_h($uuid)?>">

                        <button
                            class="rmt-button restore"
                            type="submit"
                            onclick="return confirm('Restore this region to its native OpenSim map image?');">

                            RESTORE DEFAULT

                        </button>

                    </form>

<?php endif; ?>

                </div>

            </div>

        </article>

<?php endforeach; ?>

    </section>

<?php endif; ?>


<?php if ($orphans): ?>

    <section class="rmt-orphans">

        <h2>
            Unused Custom Textures
        </h2>

        <p>
            These files belong to Region UUIDs that are not currently
            present. They are kept safely in case the same region is
            restored later. Nothing is deleted automatically.
        </p>

<?php foreach ($orphans as $orphan): ?>

        <div class="rmt-orphan-row">

            <span>
                <?=rmt_h((string)$orphan['filename'])?>
            </span>

            <span>

                <?=rmt_h(
                    ag_rmt_format_bytes(
                        (int)$orphan['bytes']
                    )
                )?>

<?php if ($orphan['modified']): ?>

                ·
                <?=rmt_h(
                    date(
                        'Y-m-d H:i:s',
                        (int)$orphan['modified']
                    )
                )?>

<?php endif; ?>

            </span>

        </div>

<?php endforeach; ?>

    </section>

<?php endif; ?>


</div>


<!-- AUSTRALIA REGION MAP TEXTURES AJAX V1 -->
<script
    src="/Other/assets/js/region-map-textures-ajax-v1.js?v=3"
    defer></script>

</body>
</html>
