<?php

/*
 * Grid
 * BACKUP HISTORY CLEAR CUT-OFF V2
 *
 * Does NOT delete OAR or IAR files.
 *
 * Stores the time when the Grid Owner last cleared
 * the displayed backup history.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/core/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    ag_require_same_origin_post();
}

$session = ag_current_session();

if (!$session) {

    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'error' => 'Not signed in.'
    ]);

    exit;
}

$level =
    (int)(
        $session['level']
        ??
        0
    );

if (!ag_is_admin($session)) {

    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'error' => 'Grid Owner access required.'
    ]);

    exit;
}

$jobsDir =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'jobs';

if (!is_dir($jobsDir)) {

    if (!@mkdir($jobsDir,0775,true) && !is_dir($jobsDir)) {

        http_response_code(500);

        echo json_encode([
            'ok' => false,
            'error' => 'Could not create jobs directory.'
        ]);

        exit;
    }
}

$marker =
    $jobsDir .
    DIRECTORY_SEPARATOR .
    'backup_history_clear_before.txt';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action =
        trim(
            (string)(
                $_POST['action']
                ??
                ''
            )
        );

    if ($action !== 'set') {

        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'error' => 'Invalid action.'
        ]);

        exit;
    }


    $now =
        time();


    $written =
        @file_put_contents(
            $marker,
            (string)$now,
            LOCK_EX
        );


    if ($written === false) {

        http_response_code(500);

        echo json_encode([
            'ok' => false,
            'error' => 'Could not save history clear point.'
        ]);

        exit;
    }


    echo json_encode([
        'ok' => true,
        'cutoff' => $now * 1000
    ]);

    exit;
}


$cutoff = 0;


if (is_file($marker)) {

    $raw =
        trim(
            (string)
            @file_get_contents(
                $marker
            )
        );


    if (
        $raw !== ''
        &&
        ctype_digit($raw)
    ) {

        $cutoff =
            ((int)$raw)
            *
            1000;
    }
}


echo json_encode([
    'ok' => true,
    'cutoff' => $cutoff
]);