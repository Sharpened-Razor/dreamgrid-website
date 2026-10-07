<?php

require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/navigation.php';
require_once __DIR__ . '/core/textures.php';

ag_no_cache();

$session = ag_require_admin();
$avatar = ag_avatar_name($session);
$level = ag_user_level($session);
$nav = ag_admin_navigation();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['ag_texture_csrf'])) {
    $_SESSION['ag_texture_csrf'] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    (string)$_SESSION['ag_texture_csrf'];

$backupRoot =
    ag_dg_path(
        '_TEXTURE_BACKUPS'
    );

if (
    !is_string($backupRoot) ||
    trim($backupRoot) === ''
) {
    throw new RuntimeException(
        'Could not resolve the texture backup directory.'
    );
}

$manifest =
    ag_texture_manifest();

$error = '';
$success = '';

function ag_texture_human_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }

    return $bytes . ' B';
}

function ag_texture_safe_backup_name(string $value): string
{
    $clean =
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            $value
        ) ?? 'texture';

    return trim($clean, '-_') ?: 'texture';
}

function ag_texture_backup_existing(
    string $slotKey,
    string $path,
    string $backupRoot,
    string $reason
): string {
    if (!is_file($path)) {
        return '';
    }

    if (
        !is_dir($backupRoot) &&
        !@mkdir($backupRoot, 0770, true)
    ) {
        throw new RuntimeException(
            'Could not create texture backup directory.'
        );
    }

    $folder =
        $backupRoot .
        '/' .
        date('Ymd-His') .
        '-' .
        ag_texture_safe_backup_name($slotKey) .
        '-' .
        bin2hex(random_bytes(2));

    if (!@mkdir($folder, 0770, true)) {
        throw new RuntimeException(
            'Could not create texture backup folder.'
        );
    }

    $destination =
        $folder .
        '/' .
        basename($path);

    if (!@copy($path, $destination)) {
        throw new RuntimeException(
            'Could not back up the previous custom texture.'
        );
    }

    $meta = [
        'format' => 'AUSTRALIA-GRID-TEXTURE-BACKUP-V1',
        'created_utc' => gmdate('c'),
        'slot' => $slotKey,
        'reason' => $reason,
        'original_path' => $path,
        'backup_path' => $destination,
    ];

    @file_put_contents(
        $folder . '/TEXTURE-BACKUP.json',
        json_encode(
            $meta,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        ),
        LOCK_EX
    );

    return $folder;
}

function ag_texture_slot_target(
    array $slot,
    string $extension
): string {
    $folder =
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '',
            (string)($slot['folder'] ?? '')
        );

    $key =
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '',
            (string)($slot['key'] ?? '')
        );

    if ($folder === '' || $key === '') {
        throw new RuntimeException(
            'Invalid texture slot definition.'
        );
    }

    return
        ag_texture_root() .
        '/' .
        $folder .
        '/' .
        $key .
        '.' .
        $extension;
}

function ag_texture_all_slot_files(array $slot): array
{
    $paths = [];

    foreach (ag_texture_allowed_extensions() as $ext) {
        $path =
            ag_texture_slot_target(
                $slot,
                $ext
            );

        if (is_file($path)) {
            $paths[] = $path;
        }
    }

    return $paths;
}

function ag_texture_groups_by_id(array $manifest): array
{
    $indexed = [];

    foreach (($manifest['groups'] ?? []) as $group) {
        $id = (string)($group['id'] ?? '');

        if ($id !== '') {
            $indexed[$id] = $group;
        }
    }

    return $indexed;
}

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
) {
    $postedCsrf =
        (string)($_POST['csrf_token'] ?? '');

    $action =
        trim(
            (string)($_POST['action'] ?? '')
        );

    $slotKey =
        trim(
            (string)($_POST['slot'] ?? '')
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
            ag_texture_slot($slotKey);

        if (!$slot) {
            $error =
                'Unknown texture slot.';
        }
        elseif ($action === 'remove') {
            try {
                $files =
                    ag_texture_all_slot_files(
                        $slot
                    );

                if (!$files) {
                    $success =
                        'There is no custom texture to remove from ' .
                        $slotKey .
                        '.';
                }
                else {
                    foreach ($files as $existing) {
                        ag_texture_backup_existing(
                            $slotKey,
                            $existing,
                            $backupRoot,
                            'remove'
                        );
                    }

                    foreach ($files as $existing) {
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
                        $slotKey .
                        ' custom texture removed. Default/fallback is active.';
                }
            }
            catch (Throwable $e) {
                $error =
                    'Texture was not removed: ' .
                    $e->getMessage();
            }
        }
        elseif ($action === 'upload') {
            try {
                if (
                    !isset($_FILES['texture']) ||
                    !is_array($_FILES['texture'])
                ) {
                    throw new RuntimeException(
                        'Choose an image first.'
                    );
                }

                $upload =
                    $_FILES['texture'];

                $uploadError =
                    (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);

                if ($uploadError !== UPLOAD_ERR_OK) {
                    throw new RuntimeException(
                        $uploadError === UPLOAD_ERR_NO_FILE
                            ? 'Choose an image first.'
                            : 'The upload failed with PHP error code ' .
                              $uploadError .
                              '.'
                    );
                }

                $size =
                    (int)($upload['size'] ?? 0);

                if (
                    $size < 1 ||
                    $size > 15 * 1024 * 1024
                ) {
                    throw new RuntimeException(
                        'Texture must be larger than 0 bytes and no more than 15 MB.'
                    );
                }

                $tmp =
                    (string)($upload['tmp_name'] ?? '');

                if (
                    $tmp === '' ||
                    !is_uploaded_file($tmp)
                ) {
                    throw new RuntimeException(
                        'PHP did not recognise this as a valid uploaded file.'
                    );
                }

                $image =
                    @getimagesize($tmp);

                if (!is_array($image)) {
                    throw new RuntimeException(
                        'The uploaded file is not a readable image.'
                    );
                }

                $mime =
                    strtolower(
                        (string)($image['mime'] ?? '')
                    );

                $mimeToExt = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];

                if (!isset($mimeToExt[$mime])) {
                    throw new RuntimeException(
                        'Only JPG/JPEG, PNG and WEBP images are allowed.'
                    );
                }

                $extension =
                    $mimeToExt[$mime];

                $target =
                    ag_texture_slot_target(
                        $slot,
                        $extension
                    );

                $targetDir =
                    dirname($target);

                if (
                    !is_dir($targetDir) &&
                    !@mkdir($targetDir, 0770, true)
                ) {
                    throw new RuntimeException(
                        'Could not create the custom texture folder.'
                    );
                }

                $existingFiles =
                    ag_texture_all_slot_files(
                        $slot
                    );

                foreach ($existingFiles as $existing) {
                    ag_texture_backup_existing(
                        $slotKey,
                        $existing,
                        $backupRoot,
                        'replace'
                    );
                }

                $temporaryTarget =
                    $target .
                    '.upload-' .
                    bin2hex(random_bytes(3));

                if (
                    !@move_uploaded_file(
                        $tmp,
                        $temporaryTarget
                    )
                ) {
                    throw new RuntimeException(
                        'Could not stage the uploaded texture.'
                    );
                }

                $verify =
                    @getimagesize(
                        $temporaryTarget
                    );

                if (!is_array($verify)) {
                    @unlink($temporaryTarget);

                    throw new RuntimeException(
                        'Staged image verification failed.'
                    );
                }

                if (
                    is_file($target) &&
                    !@unlink($target)
                ) {
                    @unlink($temporaryTarget);

                    throw new RuntimeException(
                        'Could not replace the current texture file.'
                    );
                }

                if (
                    !@rename(
                        $temporaryTarget,
                        $target
                    )
                ) {
                    @unlink($temporaryTarget);

                    throw new RuntimeException(
                        'Could not commit the uploaded texture.'
                    );
                }

                foreach ($existingFiles as $existing) {
                    if (
                        strcasecmp(
                            $existing,
                            $target
                        ) !== 0 &&
                        is_file($existing)
                    ) {
                        @unlink($existing);
                    }
                }

                $success =
                    $slotKey .
                    ' uploaded successfully as ' .
                    basename($target) .
                    '.';
            }
            catch (Throwable $e) {
                $error =
                    'Texture was not uploaded: ' .
                    $e->getMessage();
            }
        }
        else {
            $error =
                'Unknown texture action.';
        }
    }

    $_SESSION['ag_texture_csrf'] =
        bin2hex(random_bytes(32));

    $csrfToken =
        (string)$_SESSION['ag_texture_csrf'];
}

$groups =
    ag_texture_groups_by_id(
        $manifest
    );

$frontSlots =
    $groups['frontpage']['slots'] ?? [];

$pageSlots =
    $groups['pages']['slots'] ?? [];

$mapSlots =
    $groups['map-tiles']['slots'] ?? [];

?>
<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">
<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Custom Textures</title>

<link
    rel="stylesheet"
    href="/Other/core/site-shell.css?v=1">

<style>
.texture-layout{
    display:grid;
    gap:18px;
}
.texture-panel{
    overflow:hidden;
    border:1px solid rgba(255,255,255,.10);
    border-radius:14px;
    background:rgba(8,13,16,.88);
}
.texture-panel-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    padding:16px 18px;
    border-bottom:1px solid rgba(255,255,255,.08);
}
.texture-panel-title{
    color:#fff;
    font-size:16px;
    font-weight:1000;
}
.texture-panel-sub{
    margin-top:3px;
    color:#8799a2;
    font-size:11px;
    line-height:1.45;
}
.texture-badge{
    flex:0 0 auto;
    padding:5px 9px;
    border:1px solid rgba(244,179,35,.28);
    border-radius:999px;
    color:#f4b323;
    background:rgba(244,179,35,.07);
    font-size:10px;
    font-weight:1000;
}
.texture-panel-body{
    padding:16px;
}
.texture-feature{
    display:grid;
    grid-template-columns:minmax(280px,1.15fr) minmax(280px,.85fr);
    gap:16px;
}
.texture-feature-preview{
    min-height:260px;
    overflow:hidden;
    border:1px solid rgba(255,255,255,.09);
    border-radius:12px;
    background:#070c0f;
}
.texture-feature-preview img{
    width:100%;
    height:100%;
    min-height:260px;
    display:block;
    object-fit:cover;
}
.texture-empty-large,
.texture-empty-small{
    display:grid;
    place-items:center;
    color:#728690;
    text-align:center;
    font-weight:1000;
    background:
        linear-gradient(45deg,#11191d 25%,transparent 25%),
        linear-gradient(-45deg,#11191d 25%,transparent 25%),
        linear-gradient(45deg,transparent 75%,#11191d 75%),
        linear-gradient(-45deg,transparent 75%,#11191d 75%);
    background-size:22px 22px;
    background-position:0 0,0 11px,11px -11px,-11px 0;
}
.texture-empty-large{
    min-height:260px;
    padding:25px;
}
.texture-empty-small{
    min-height:130px;
    padding:14px;
    font-size:10px;
}
.texture-editor{
    padding:16px;
    border:1px solid rgba(255,255,255,.09);
    border-radius:12px;
    background:rgba(0,0,0,.14);
}
.texture-slot-name{
    color:#fff;
    font-size:15px;
    font-weight:1000;
}
.texture-file-key{
    margin-top:4px;
    color:#f4b323;
    font-family:Consolas,monospace;
    font-size:11px;
    overflow-wrap:anywhere;
}
.texture-description{
    margin-top:9px;
    color:#a7b6bd;
    font-size:12px;
    line-height:1.5;
}
.texture-meta{
    margin-top:12px;
    padding:9px 10px;
    border:1px solid rgba(255,255,255,.07);
    border-radius:8px;
    color:#8fa1aa;
    background:rgba(255,255,255,.025);
    font-size:10px;
    line-height:1.5;
}
.texture-form{
    display:grid;
    gap:8px;
    margin-top:12px;
}
.texture-form input[type=file]{
    width:100%;
    box-sizing:border-box;
    padding:9px;
    border:1px solid rgba(255,255,255,.11);
    border-radius:8px;
    color:#cbd8de;
    background:#080d10;
    font-size:10px;
}
.texture-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.texture-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:12px;
}
.texture-card{
    overflow:hidden;
    border:1px solid rgba(255,255,255,.09);
    border-radius:11px;
    background:rgba(0,0,0,.13);
}
.texture-card-preview{
    aspect-ratio:16/9;
    overflow:hidden;
    background:#070c0f;
}
.texture-card-preview img{
    width:100%;
    height:100%;
    display:block;
    object-fit:cover;
}
.texture-card-body{
    padding:11px;
}
.texture-card .texture-slot-name{
    font-size:13px;
}
.texture-card .texture-description{
    min-height:34px;
    font-size:10px;
}
.texture-map-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:8px;
}
.texture-map-card{
    overflow:hidden;
    border:1px solid rgba(255,255,255,.10);
    border-radius:10px;
    background:rgba(0,0,0,.16);
}
.texture-map-preview{
    aspect-ratio:1/1;
    overflow:hidden;
    background:#070c0f;
}
.texture-map-preview img{
    width:100%;
    height:100%;
    display:block;
    object-fit:cover;
}
.texture-map-body{
    padding:9px;
}
.texture-map-title{
    display:flex;
    justify-content:space-between;
    gap:7px;
    align-items:center;
}
.texture-map-title strong{
    color:#fff;
    font-size:11px;
}
.texture-map-number{
    display:grid;
    place-items:center;
    width:22px;
    height:22px;
    flex:0 0 22px;
    border-radius:6px;
    color:#091015;
    background:#f4b323;
    font-size:10px;
    font-weight:1000;
}
.texture-map-body .texture-file-key{
    font-size:9px;
}
.texture-map-body .texture-form{
    margin-top:8px;
}
.texture-map-body .texture-form input[type=file]{
    padding:6px;
    font-size:9px;
}
.texture-map-body .ag-button{
    min-height:32px;
    padding:0 9px;
    font-size:9px;
}
.texture-help{
    padding:13px 15px;
    border:1px solid rgba(244,179,35,.19);
    border-radius:10px;
    color:#b9c8cf;
    background:rgba(244,179,35,.045);
    font-size:11px;
    line-height:1.55;
}
@media(max-width:1100px){
    .texture-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .texture-map-grid{
        grid-template-columns:repeat(3,minmax(0,1fr));
    }
}
@media(max-width:760px){
    .texture-feature{
        grid-template-columns:1fr;
    }
    .texture-map-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}
@media(max-width:600px){
    .texture-grid,
    .texture-map-grid{
        grid-template-columns:1fr;
    }
}
</style>


<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">
<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=<?= filemtime(__DIR__ . '/assets/css/ag-uniform-site-v12.css') ?>">

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

</head>

<body>

<div class="ag-shell">

<?php
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "Custom Textures";
$siteHeaderRole = "GRID OWNER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "ADMIN HOME";
$siteHeaderLink = "/Other/admin-home.php";
require_once __DIR__ . "/includes/site-header.php";
?>

<nav class="ag-nav">

<?php foreach ($nav as $item): ?>

    <a
        href="<?=ag_h($item['url'])?>"
        class="<?=($item['url'] === ag_route('admin_textures')) ? 'active' : ''?>"
    >
        <?=ag_h($item['label'])?>
    </a>

<?php endforeach; ?>

</nav>

<div class="texture-layout">

    <?php if ($error !== ''): ?>

        <div class="ag-error">
            <?=ag_h($error)?>
        </div>

    <?php endif; ?>

    <?php if ($success !== ''): ?>

        <div class="ag-success">
            <?=ag_h($success)?>
        </div>

    <?php endif; ?>

    <div class="texture-help">
        Upload a picture into a named slot and the Grid website can
        use that slot everywhere. Replacing or removing an existing custom
        texture backs the old file up first. JPG, PNG and WEBP are supported.
    </div>

    <!-- FRONT PAGE -->
    <section class="texture-panel">

        <div class="texture-panel-head">

            <div>
                <div class="texture-panel-title">
                    Front Page
                </div>

                <div class="texture-panel-sub">
                    Main public/front-page custom image.
                </div>
            </div>

            <div class="texture-badge">
                1 TEXTURE SLOT
            </div>

        </div>

        <div class="texture-panel-body">

        <?php foreach ($frontSlots as $slot): ?>

            <?php
                $slotKey =
                    (string)($slot['key'] ?? '');

                $info =
                    ag_texture_info($slotKey);
            ?>

            <div class="texture-feature">

                <div class="texture-feature-preview">

                    <?php if ($info['exists']): ?>

                        <img
                            src="<?=ag_h((string)$info['url'])?>"
                            alt="<?=ag_h((string)($slot['label'] ?? $slotKey))?>">

                    <?php else: ?>

                        <div class="texture-empty-large">
                            NO CUSTOM FRONT PAGE TEXTURE
                        </div>

                    <?php endif; ?>

                </div>

                <div class="texture-editor">

                    <div class="texture-slot-name">
                        <?=ag_h((string)($slot['label'] ?? $slotKey))?>
                    </div>

                    <div class="texture-file-key">
                        <?=ag_h($slotKey)?>
                    </div>

                    <div class="texture-description">
                        <?=ag_h((string)($slot['description'] ?? ''))?>
                    </div>

                    <?php if ($info['exists']): ?>

                        <div class="texture-meta">
                            <strong><?=ag_h((string)$info['filename'])?></strong><br>
                            <?=ag_h(
                                ((int)$info['width']) .
                                ' x ' .
                                ((int)$info['height'])
                            )?>
                            &nbsp; &bull; &nbsp;
                            <?=ag_h(
                                ag_texture_human_bytes(
                                    (int)$info['bytes']
                                )
                            )?>
                        </div>

                    <?php endif; ?>

                    <form
                        class="texture-form"
                        method="post"
                        enctype="multipart/form-data"
                    >
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
                            name="texture"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required>

                        <div class="texture-actions">

                            <button
                                class="ag-button primary"
                                type="submit"
                            >
                                <?= $info['exists']
                                    ? 'REPLACE TEXTURE'
                                    : 'UPLOAD TEXTURE' ?>
                            </button>

                        </div>

                    </form>

                    <?php if ($info['exists']): ?>

                        <form
                            class="texture-form"
                            method="post"
                        >
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

                            <button
                                class="ag-button"
                                type="submit"
                                onclick="return confirm('Remove this custom texture and return this slot to its default/fallback texture?');"
                            >
                                REMOVE CUSTOM TEXTURE
                            </button>
                        </form>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

        </div>

    </section>

    <!-- PAGE TEXTURES -->
    <section class="texture-panel">

        <div class="texture-panel-head">

            <div>
                <div class="texture-panel-title">
                    Page Textures
                </div>

                <div class="texture-panel-sub">
                    Custom images for Grid admin and user pages.
                </div>
            </div>

            <div class="texture-badge">
                <?=count($pageSlots)?> PAGE SLOTS
            </div>

        </div>

        <div class="texture-panel-body">

            <div class="texture-grid">

            <?php foreach ($pageSlots as $slot): ?>

                <?php
                    $slotKey =
                        (string)($slot['key'] ?? '');

                    $info =
                        ag_texture_info($slotKey);
                ?>

                <article class="texture-card">

                    <div class="texture-card-preview">

                        <?php if ($info['exists']): ?>

                            <img
                                src="<?=ag_h((string)$info['url'])?>"
                                alt="<?=ag_h((string)($slot['label'] ?? $slotKey))?>">

                        <?php else: ?>

                            <div class="texture-empty-small">
                                DEFAULT
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="texture-card-body">

                        <div class="texture-slot-name">
                            <?=ag_h((string)($slot['label'] ?? $slotKey))?>
                        </div>

                        <div class="texture-file-key">
                            <?=ag_h($slotKey)?>
                        </div>

                        <div class="texture-description">
                            <?=ag_h((string)($slot['description'] ?? ''))?>
                        </div>

                        <?php if ($info['exists']): ?>

                            <div class="texture-meta">
                                <?=ag_h((string)$info['filename'])?><br>
                                <?=ag_h(
                                    ((int)$info['width']) .
                                    ' x ' .
                                    ((int)$info['height'])
                                )?>
                                &nbsp; &bull; &nbsp;
                                <?=ag_h(
                                    ag_texture_human_bytes(
                                        (int)$info['bytes']
                                    )
                                )?>
                            </div>

                        <?php endif; ?>

                        <form
                            class="texture-form"
                            method="post"
                            enctype="multipart/form-data"
                        >
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
                                name="texture"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                required>

                            <button
                                class="ag-button primary"
                                type="submit"
                            >
                                <?= $info['exists']
                                    ? 'REPLACE'
                                    : 'UPLOAD' ?>
                            </button>
                        </form>

                        <?php if ($info['exists']): ?>

                            <form
                                class="texture-form"
                                method="post"
                            >
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

                                <button
                                    class="ag-button"
                                    type="submit"
                                    onclick="return confirm('Remove this custom texture and return this slot to its default/fallback texture?');"
                                >
                                    REMOVE
                                </button>
                            </form>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

            </div>

        </div>

    </section>

    <!-- MAP TILE TEXTURES -->
    <section class="texture-panel">

        <div class="texture-panel-head">

            <div>
                <div class="texture-panel-title">
                    Map Tile Textures
                </div>

                <div class="texture-panel-sub">
                    Sixteen custom map-tile slots arranged as a 4 x 4 grid.
                </div>
            </div>

            <div class="texture-badge">
                <?=count($mapSlots)?> MAP SLOTS
            </div>

        </div>

        <div class="texture-panel-body">

            <div class="texture-map-grid">

            <?php foreach ($mapSlots as $slot): ?>

                <?php
                    $slotKey =
                        (string)($slot['key'] ?? '');

                    $info =
                        ag_texture_info($slotKey);

                    preg_match(
                        '/Map(\d+)-texture/i',
                        $slotKey,
                        $mapMatch
                    );

                    $mapNumber =
                        (string)($mapMatch[1] ?? '');
                ?>

                <article class="texture-map-card">

                    <div class="texture-map-preview">

                        <?php if ($info['exists']): ?>

                            <img
                                src="<?=ag_h((string)$info['url'])?>"
                                alt="<?=ag_h($slotKey)?>">

                        <?php else: ?>

                            <div class="texture-empty-small">
                                DEFAULT MAP TILE
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="texture-map-body">

                        <div class="texture-map-title">

                            <strong>
                                Map Tile
                            </strong>

                            <div class="texture-map-number">
                                <?=ag_h($mapNumber)?>
                            </div>

                        </div>

                        <div class="texture-file-key">
                            <?=ag_h($slotKey)?>
                        </div>

                        <form
                            class="texture-form"
                            method="post"
                            enctype="multipart/form-data"
                        >
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
                                name="texture"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                required>

                            <button
                                class="ag-button primary"
                                type="submit"
                            >
                                <?= $info['exists']
                                    ? 'REPLACE'
                                    : 'UPLOAD' ?>
                            </button>
                        </form>

                        <?php if ($info['exists']): ?>

                            <form
                                class="texture-form"
                                method="post"
                            >
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

                                <button
                                    class="ag-button"
                                    type="submit"
                                    onclick="return confirm('Remove this custom map tile texture and return it to its default/fallback texture?');"
                                >
                                    REMOVE
                                </button>
                            </form>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

            </div>

        </div>

    </section>

</div>

</div>

<script
    defer
    src="/Other/core/ag-confirm-modal.js?v=1">
</script>

<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>
</body>
</html>





