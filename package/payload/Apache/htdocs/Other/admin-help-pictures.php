<?php

require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/navigation.php';
require_once __DIR__ . '/core/help-pictures.php';

ag_no_cache();

$session =
    ag_require_admin();


if (
    session_status() !==
    PHP_SESSION_ACTIVE
) {

    session_start();
}


if (
    empty(
        $_SESSION[
            'ag_help_picture_csrf'
        ]
    )
) {

    $_SESSION[
        'ag_help_picture_csrf'
    ] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    (string)
    $_SESSION[
        'ag_help_picture_csrf'
    ];


$backupRoot =
    ag_dg_path(
        '_HELP_PICTURE_BACKUPS'
    );

if (
    !is_string($backupRoot) ||
    trim($backupRoot) === ''
) {
    throw new RuntimeException(
        'Could not resolve the Help-picture backup directory.'
    );
}


$error = '';
$success = '';


function ag_help_picture_human_bytes(
    int $bytes
): string {

    if ($bytes >= 1048576) {

        return
            number_format(
                $bytes / 1048576,
                2
            ) .
            ' MB';
    }


    if ($bytes >= 1024) {

        return
            number_format(
                $bytes / 1024,
                1
            ) .
            ' KB';
    }


    return
        $bytes .
        ' B';
}


function ag_help_picture_safe_backup_name(
    string $value
): string {

    $clean =
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            $value
        );


    if (!is_string($clean)) {

        $clean =
            'picture';
    }


    $clean =
        trim(
            $clean,
            '-_'
        );


    return
        $clean !== ''
            ? $clean
            : 'picture';
}


function ag_help_picture_backup_existing(
    string $slotKey,
    string $existing,
    string $backupRoot,
    string $reason
): void {

    if (!is_file($existing)) {

        return;
    }


    $folder =
        rtrim(
            $backupRoot,
            '/\\'
        ) .
        '/' .
        date('Y-m-d') .
        '/' .
        ag_help_picture_safe_backup_name(
            $slotKey
        );


    if (
        !is_dir($folder) &&
        !@mkdir(
            $folder,
            0770,
            true
        )
    ) {

        throw new RuntimeException(
            'Could not create the Help picture backup folder.'
        );
    }


    $destination =
        $folder .
        '/' .
        date('Ymd-His') .
        '-' .
        ag_help_picture_safe_backup_name(
            $reason
        ) .
        '-' .
        bin2hex(
            random_bytes(2)
        ) .
        '-' .
        basename($existing);


    if (
        !@copy(
            $existing,
            $destination
        )
    ) {

        throw new RuntimeException(
            'Could not back up the existing Help picture.'
        );
    }
}


function ag_help_picture_remove_custom_slot(
    string $slotKey,
    string $backupRoot,
    string $reason
): int {

    $slot =
        ag_help_picture_slot(
            $slotKey
        );


    if (!$slot) {

        return 0;
    }


    $files =
        ag_help_picture_all_custom_files(
            $slot
        );


    if (!$files) {

        return 0;
    }


    foreach ($files as $existing) {

        ag_help_picture_backup_existing(
            $slotKey,
            $existing,
            $backupRoot,
            $reason
        );
    }


    $removed = 0;


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


        $removed++;
    }


    return $removed;
}


function ag_help_picture_aspect_label(
    int $width,
    int $height
): string {

    if (
        $width < 1 ||
        $height < 1
    ) {

        return 'N/A';
    }


    $ratio =
        $width /
        $height;


    if (
        abs(
            $ratio -
            (16 / 9)
        ) <= 0.06
    ) {

        return '16:9';
    }


    return
        number_format(
            $ratio,
            2
        ) .
        ':1';
}


function ag_help_picture_suitability(
    int $width,
    int $height
): array {

    if (
        $width < 1 ||
        $height < 1
    ) {

        return [
            'class' =>
                'neutral',
            'label' =>
                'PICTURE NOT ADDED',
        ];
    }


    $ratio =
        $width /
        $height;


    if (
        $width < 1280 ||
        $height < 720
    ) {

        return [
            'class' =>
                'bad',
            'label' =>
                'LOW RESOLUTION',
        ];
    }


    if (
        $ratio < 1.30 ||
        $ratio > 2.20
    ) {

        return [
            'class' =>
                'warning',
            'label' =>
                'UNUSUAL ASPECT',
        ];
    }


    if (
        $width >= 1920 &&
        $height >= 1080
    ) {

        return [
            'class' =>
                'good',
            'label' =>
                'GOOD FOR HELP',
        ];
    }


    return [
        'class' =>
            'neutral',
        'label' =>
            'GOOD SIZE',
    ];
}


if (
    ($_SERVER['REQUEST_METHOD'] ?? '') ===
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


    $topicId =
        trim(
            (string)
            ($_POST['topic'] ?? '')
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


    elseif ($action === 'remove-topic') {

        try {

            $topics =
                ag_help_picture_topics();


            if (
                !isset(
                    $topics[$topicId]
                )
            ) {

                throw new RuntimeException(
                    'Unknown Help topic.'
                );
            }


            $removed =
                0;


            foreach (
                ag_help_picture_topic_slots(
                    $topicId
                )
                as $key => $slot
            ) {

                $removed +=
                    ag_help_picture_remove_custom_slot(
                        $key,
                        $backupRoot,
                        'restore-topic'
                    );
            }


            if ($removed > 0) {

                $success =
                    (string)
                    $topics[$topicId]['label'] .
                    ' custom pictures restored.';
            }
            else {

                $success =
                    'There are no custom pictures to restore for this Help topic.';
            }
        }
        catch (Throwable $e) {

            $error =
                'Topic pictures were not restored: ' .
                $e->getMessage();
        }
    }


    else {

        $slot =
            ag_help_picture_slot(
                $slotKey
            );


        if (!$slot) {

            $error =
                'Unknown Help picture slot.';
        }


        elseif ($action === 'remove') {

            try {

                $removed =
                    ag_help_picture_remove_custom_slot(
                        $slotKey,
                        $backupRoot,
                        'restore'
                    );


                if ($removed < 1) {

                    $success =
                        'There is no custom picture to restore for this slot.';
                }
                elseif (
                    is_file(
                        (string)
                        ($slot['default_file'] ?? '')
                    )
                ) {

                    $success =
                        'Custom picture removed. Supplied default is active.';
                }
                else {

                    $success =
                        'Custom picture removed. Placeholder is active.';
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
                        'Choose an image first.'
                    );
                }


                $upload =
                    $_FILES['picture'];


                $uploadError =
                    (int)
                    (
                        $upload['error'] ??
                        UPLOAD_ERR_NO_FILE
                    );


                if (
                    $uploadError !==
                    UPLOAD_ERR_OK
                ) {

                    throw new RuntimeException(
                        $uploadError ===
                        UPLOAD_ERR_NO_FILE
                            ? 'Choose an image first.'
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
                        'Only JPG/JPEG, PNG and WEBP images are allowed.'
                    );
                }


                $extension =
                    $mimeToExt[$mime];


                $target =
                    ag_help_picture_target(
                        $slot,
                        $extension
                    );


                $targetDir =
                    dirname(
                        $target
                    );


                if (
                    !is_dir($targetDir) &&
                    !@mkdir(
                        $targetDir,
                        0770,
                        true
                    )
                ) {

                    throw new RuntimeException(
                        'Could not create the custom Help picture folder.'
                    );
                }


                $existingFiles =
                    ag_help_picture_all_custom_files(
                        $slot
                    );


                foreach (
                    $existingFiles
                    as $existing
                ) {

                    ag_help_picture_backup_existing(
                        $slotKey,
                        $existing,
                        $backupRoot,
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
                        'Could not stage the uploaded Help picture.'
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


                $verifyMime =
                    strtolower(
                        (string)
                        ($verify['mime'] ?? '')
                    );


                if (
                    !isset(
                        $mimeToExt[
                            $verifyMime
                        ]
                    )
                ) {

                    @unlink(
                        $temporaryTarget
                    );

                    throw new RuntimeException(
                        'The staged image type is not allowed.'
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
                        'Could not replace the current Help picture.'
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
                        'Could not commit the uploaded Help picture.'
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


        else {

            $error =
                'Unknown Help picture action.';
        }
    }


    $_SESSION[
        'ag_help_picture_csrf'
    ] =
        bin2hex(
            random_bytes(32)
        );


    $csrfToken =
        (string)
        $_SESSION[
            'ag_help_picture_csrf'
        ];
}


$topics =
    ag_help_picture_topics();


$tutorialTopicIds = [
    'firestorm-grid',
    'login',
    'teleport',
    'edit-account',
    'my-profile',
    'inventory-browser',
    'clean-inventory',
    'safety-iar',
    'iar-backups',
    'grid-map',
];


$tutorialTopics = [];


foreach ($tutorialTopicIds as $tutorialTopicId) {

    if (isset($topics[$tutorialTopicId])) {

        $tutorialTopics[$tutorialTopicId] =
            $topics[$tutorialTopicId];
    }
}


$slots =
    ag_help_picture_slots();


$totalSlots =
    count($slots);


$customTotal = 0;
$defaultTotal = 0;
$placeholderTotal = 0;


foreach (
    $slots
    as $slotKey => $slot
) {

    $info =
        ag_help_picture_info(
            $slotKey
        );


    if (
        $info['status'] ===
        'CUSTOM'
    ) {

        $customTotal++;
    }
    elseif (
        $info['status'] ===
        'DEFAULT'
    ) {

        $defaultTotal++;
    }
    else {

        $placeholderTotal++;
    }
}

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Help Tutorial Pictures</title>

<link
    rel="stylesheet"
    href="/Other/core/site-shell.css?v=1">

<style>

*{
    box-sizing:border-box;
}

body{
    margin:0;
    color:#dce3df;
    background:#0a0e10;
    font-family:Arial,Helvetica,sans-serif;
}

.hp-page{
    max-width:1540px;
    margin:0 auto;
    padding:22px;
}

.hp-header{
    margin-bottom:16px;
    padding:19px 21px;
    border:1px solid rgba(255,255,255,.10);
    border-radius:10px;
    background:rgba(255,255,255,.035);
}

.hp-kicker{
    margin-bottom:6px;
    color:#d6b54f;
    font-size:10px;
    font-weight:900;
    letter-spacing:.12em;
}

.hp-header h1{
    margin:0 0 8px;
    color:#f1f4f2;
    font-size:25px;
}

.hp-header p{
    max-width:1050px;
    margin:0;
    color:#aeb8b2;
    font-size:12px;
    line-height:1.6;
}

.hp-summary{
    display:grid;
    grid-template-columns:repeat(5,minmax(0,1fr));
    gap:10px;
    margin-bottom:18px;
}

.hp-summary-card{
    padding:13px;
    border:1px solid rgba(255,255,255,.10);
    border-radius:8px;
    background:#101619;
}

.hp-summary-name{
    color:#89958e;
    font-size:9px;
    font-weight:900;
    letter-spacing:.07em;
}

.hp-summary-value{
    margin-top:5px;
    color:#f0c85c;
    font-size:21px;
    font-weight:900;
}

.hp-message{
    margin:0 0 16px;
    padding:11px 13px;
    border-radius:8px;
    font-size:12px;
    font-weight:700;
}

.hp-message.success{
    color:#c9f3d1;
    border:1px solid rgba(82,177,102,.35);
    background:rgba(38,102,52,.22);
}

.hp-message.error{
    color:#ffd0d0;
    border:1px solid rgba(211,79,79,.42);
    background:rgba(111,31,31,.25);
}

.hp-topic{
    margin-bottom:20px;
    padding:16px;
    border:1px solid rgba(255,255,255,.10);
    border-radius:10px;
    background:rgba(255,255,255,.025);
}

.hp-topic-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:14px;
    margin-bottom:14px;
}

.hp-topic-title{
    color:#e8c45c;
    font-size:16px;
    font-weight:900;
}

.hp-topic-count{
    margin-top:4px;
    color:#919c96;
    font-size:10px;
}

.hp-topic-actions,
.hp-actions{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
}

.hp-topic-actions{
    justify-content:flex-end;
}

.hp-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:14px;
}

.hp-card{
    overflow:hidden;
    border:1px solid rgba(255,255,255,.12);
    border-radius:9px;
    background:#101619;
}

.hp-preview{
    position:relative;
    min-height:220px;
    padding:9px;
    background:#050809;
}

.hp-preview img{
    display:block;
    width:100%;
    height:230px;
    object-fit:contain;
    border-radius:5px;
    background:#000;
}

.hp-placeholder{
    display:flex;
    align-items:center;
    justify-content:center;
    width:100%;
    height:230px;
    padding:24px;
    border:2px dashed rgba(255,255,255,.16);
    border-radius:6px;
    color:#89938e;
    text-align:center;
    font-size:11px;
    font-weight:900;
    line-height:1.6;
}

.hp-status{
    position:absolute;
    z-index:2;
    top:16px;
    right:16px;
    padding:5px 8px;
    border-radius:999px;
    font-size:9px;
    font-weight:900;
}

.hp-status.default{
    color:#f4dc95;
    background:rgba(116,84,22,.92);
}

.hp-status.custom{
    color:#c9f3d1;
    background:rgba(34,105,53,.94);
}

.hp-status.placeholder{
    color:#d1d7d4;
    background:rgba(67,75,72,.94);
}

.hp-body{
    display:grid;
    gap:10px;
    padding:14px;
}

.hp-title{
    color:#edf1ef;
    font-size:13px;
    font-weight:900;
}

.hp-description{
    min-height:32px;
    color:#aab4ae;
    font-size:11px;
    line-height:1.5;
}

.hp-badges{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
}

.hp-badge{
    display:inline-flex;
    align-items:center;
    min-height:25px;
    padding:0 8px;
    border:1px solid rgba(255,255,255,.16);
    border-radius:999px;
    font-size:9px;
    font-weight:900;
}

.hp-badge.good{
    color:#bff0cb;
    border-color:rgba(87,184,109,.45);
    background:rgba(42,112,60,.24);
}

.hp-badge.warning{
    color:#ffe0a0;
    border-color:rgba(218,164,61,.48);
    background:rgba(116,79,20,.28);
}

.hp-badge.bad{
    color:#ffd0d0;
    border-color:rgba(218,78,78,.46);
    background:rgba(118,31,31,.28);
}

.hp-badge.neutral{
    color:#d4dcda;
    background:rgba(255,255,255,.055);
}

.hp-meta{
    padding:8px 9px;
    border-radius:6px;
    color:#99a49e;
    background:rgba(255,255,255,.035);
    font-size:10px;
    line-height:1.65;
}

.hp-meta strong{
    color:#e7ece9;
}

.hp-default-compare{
    padding:9px;
    border:1px solid rgba(240,200,92,.17);
    border-radius:7px;
    background:rgba(240,200,92,.035);
}

.hp-default-label{
    margin-bottom:7px;
    color:#d9bb62;
    font-size:9px;
    font-weight:900;
}

.hp-default-compare img{
    display:block;
    width:100%;
    max-height:150px;
    object-fit:contain;
    background:#000;
}

.hp-form{
    display:grid;
    gap:8px;
}

.hp-form input[type=file]{
    width:100%;
    padding:8px;
    color:#d8dedb;
    background:#080d10;
    border:1px solid rgba(255,255,255,.17);
    border-radius:6px;
    font-size:10px;
}

.hp-button{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:36px;
    padding:0 12px;
    cursor:pointer;
    border:1px solid rgba(255,255,255,.18);
    border-radius:6px;
    color:#e6ebe8;
    background:linear-gradient(180deg,#30383a,#14191a);
    text-decoration:none;
    font-size:10px;
    font-weight:900;
}

.hp-button.primary{
    color:#1d1608;
    border-color:#e8c65e;
    background:linear-gradient(180deg,#f0cb64,#ae7c17);
}

.hp-button.restore{
    color:#ffd8d8;
    border-color:rgba(213,86,86,.40);
    background:rgba(117,31,31,.27);
}

.hp-button.view{
    color:#f0c85c;
    border-color:rgba(221,187,91,.35);
}

.hp-note{
    margin-top:8px;
    color:#7f8a84;
    font-size:10px;
    line-height:1.5;
}

@media(max-width:1080px){

    .hp-summary{
        grid-template-columns:repeat(3,minmax(0,1fr));
    }

    .hp-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}

@media(max-width:680px){

    .hp-page{
        padding:12px;
    }

    .hp-summary,
    .hp-grid{
        grid-template-columns:1fr;
    }

    .hp-topic-head{
        display:block;
    }

    .hp-topic-actions{
        justify-content:flex-start;
        margin-top:10px;
    }
}

</style>

</head>

<body>

<main class="hp-page">


<header class="hp-header">

    <div class="hp-kicker">
        HELP CENTRE / ADMIN
    </div>

    <h1>
        Help Centre Pictures
    </h1>

    <p>
        Manage only pictures used by Help Centre picture tutorials.
        Supplied defaults are protected and custom uploads are
        stored separately. Slots without a supplied picture show
        a placeholder until a picture is uploaded.
    </p>

</header>


<div class="hp-summary">

    <div class="hp-summary-card">
        <div class="hp-summary-name">TUTORIAL SETS</div>
        <div class="hp-summary-value"><?=count($tutorialTopics)?></div>
    </div>

    <div class="hp-summary-card">
        <div class="hp-summary-name">TOTAL PICTURE SLOTS</div>
        <div class="hp-summary-value"><?=$totalSlots?></div>
    </div>

    <div class="hp-summary-card">
        <div class="hp-summary-name">SUPPLIED DEFAULT</div>
        <div class="hp-summary-value"><?=$defaultTotal?></div>
    </div>

    <div class="hp-summary-card">
        <div class="hp-summary-name">CUSTOM</div>
        <div class="hp-summary-value"><?=$customTotal?></div>
    </div>

    <div class="hp-summary-card">
        <div class="hp-summary-name">PLACEHOLDER</div>
        <div class="hp-summary-value"><?=$placeholderTotal?></div>
    </div>

</div>


<?php if ($success !== ''): ?>

<div class="hp-message success">
    <?=ag_h($success)?>
</div>

<?php endif; ?>


<?php if ($error !== ''): ?>

<div class="hp-message error">
    <?=ag_h($error)?>
</div>

<?php endif; ?>


<?php foreach ($tutorialTopics as $topicId => $topic): ?>


<?php

$topicSlots =
    ag_help_picture_topic_slots(
        $topicId
    );


$topicCustom =
    0;


foreach ($topicSlots as $key => $slot) {

    $topicInfo =
        ag_help_picture_info(
            $key
        );


    if ($topicInfo['is_custom']) {

        $topicCustom++;
    }
}

?>


<section class="hp-topic">


<div class="hp-topic-head">


<div>

    <div class="hp-topic-title">
        <?=ag_h((string)$topic['label'])?>
    </div>

    <div class="hp-topic-count">

        <?=count($topicSlots)?>
        picture slot<?=count($topicSlots) === 1 ? '' : 's'?>

        &nbsp; • &nbsp;

        <?=$topicCustom?> custom

    </div>

</div>


<div class="hp-topic-actions">

    <a
        class="hp-button view"
        href="/Other/panel-help-centre.php?from=admin&amp;topic=<?=rawurlencode($topicId)?>">

        VIEW TUTORIAL

    </a>


    <?php if ($topicCustom > 0): ?>

    <form method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?=ag_h($csrfToken)?>">

        <input
            type="hidden"
            name="action"
            value="remove-topic">

        <input
            type="hidden"
            name="topic"
            value="<?=ag_h($topicId)?>">

        <button
            type="submit"
            class="hp-button restore"
            onclick="return confirm('Restore every custom picture in this Help topic?');">

            RESTORE ALL

        </button>

    </form>

    <?php endif; ?>

</div>


</div>


<div class="hp-grid">


<?php foreach ($topicSlots as $slotKey => $slot): ?>


<?php

$info =
    ag_help_picture_info(
        $slotKey
    );


$defaultInfo =
    ag_help_picture_default_info(
        $slotKey
    );


$aspect =
    ag_help_picture_aspect_label(
        (int)$info['width'],
        (int)$info['height']
    );


$suitability =
    ag_help_picture_suitability(
        (int)$info['width'],
        (int)$info['height']
    );


$statusClass =
    strtolower(
        (string)$info['status']
    );

?>


<article class="hp-card">


<div class="hp-preview">

    <span class="hp-status <?=ag_h($statusClass)?>">
        <?=ag_h((string)$info['status'])?>
    </span>


    <?php if ((string)$info['url'] !== ''): ?>

        <img
            src="<?=ag_h((string)$info['url'])?>"
            alt="<?=ag_h((string)$slot['label'])?>">

    <?php else: ?>

        <div class="hp-placeholder">
            HELP PICTURE<br>
            NOT ADDED YET
        </div>

    <?php endif; ?>

</div>


<div class="hp-body">


<div class="hp-title">
    <?=ag_h((string)$slot['label'])?>
</div>


<div class="hp-description">
    <?=ag_h((string)$slot['description'])?>
</div>


<div class="hp-badges">

    <span
        class="hp-badge <?=ag_h((string)$suitability['class'])?>">

        <?=ag_h((string)$suitability['label'])?>

    </span>

    <span class="hp-badge neutral">
        ASPECT: <?=ag_h($aspect)?>
    </span>

</div>


<div class="hp-meta">

    ACTIVE:
    <strong>
        <?=ag_h((string)$info['status'])?>
    </strong>


    <?php if ($info['exists']): ?>

        <br>

        SIZE:
        <strong>
            <?=ag_h(
                (string)$info['width'] .
                ' × ' .
                (string)$info['height']
            )?>
        </strong>

        &nbsp; • &nbsp;

        <?=ag_h(
            ag_help_picture_human_bytes(
                (int)$info['bytes']
            )
        )?>

        <br>

        FILE:
        <?=ag_h((string)$info['filename'])?>


        <?php if ($info['modified'] !== null): ?>

            <br>

            LAST CHANGED:
            <?=ag_h(
                date(
                    'd M Y H:i',
                    (int)$info['modified']
                )
            )?>

        <?php endif; ?>


    <?php else: ?>

        <br>

        No supplied picture yet.

    <?php endif; ?>


    <br>

    RECOMMENDED:
    1280 × 720 or larger / 16:9

</div>


<?php if (
    $info['is_custom'] &&
    $defaultInfo['exists']
): ?>

<div class="hp-default-compare">

    <div class="hp-default-label">
        SUPPLIED DEFAULT
    </div>

    <img
        src="<?=ag_h((string)$defaultInfo['url'])?>"
        alt="Supplied default">

</div>

<?php endif; ?>


<form
    class="hp-form"
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

    <div class="hp-actions">

        <button
            type="submit"
            class="hp-button primary">

            <?=$info['is_custom']
                ? 'REPLACE CUSTOM PICTURE'
                : 'UPLOAD CUSTOM PICTURE'?>

        </button>

    </div>

</form>


<?php if ($info['is_custom']): ?>

<form
    class="hp-form"
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

    <button
        type="submit"
        class="hp-button restore"
        onclick="return confirm('Remove this custom Help picture and restore its default or placeholder?');">

        <?=$info['has_default']
            ? 'RESTORE DEFAULT'
            : 'RESTORE PLACEHOLDER'?>

    </button>

</form>

<?php endif; ?>


</div>

</article>


<?php endforeach; ?>


</div>

</section>


<?php endforeach; ?>


<div class="hp-note">

    Supplied pictures are never overwritten.
    Custom pictures are stored under
    assets\images\help\custom.
    Replaced custom pictures are backed up under
    <?=ag_h($backupRoot)?>.

</div>


</main>

</body>

</html>

