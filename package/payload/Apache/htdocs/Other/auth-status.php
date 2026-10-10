<?php

require_once __DIR__ . '/core/auth.php';

header(
    'Content-Type: application/json; charset=utf-8'
);
header('Cache-Control: no-store');

$session =
    ag_current_session();

echo json_encode(
    [
        'logged_in' =>
            $session !== null,

        'avatar' =>
            $session !== null
                ? ($session['avatar'] ?? '')
                : '',

        'level' =>
            $session !== null
                ? (int)($session['level'] ?? 0)
                : null
    ],
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_SLASHES
);
