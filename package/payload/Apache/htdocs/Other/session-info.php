<?php

require_once __DIR__ . '/core/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$session = ag_current_session();

if (!$session) {

    http_response_code(401);

    echo json_encode([
        'ok' => false
    ]);

    exit;
}

echo json_encode([
    'ok'     => true,
    'avatar' => ag_avatar_name($session),
    'level'  => ag_user_level($session)
]);