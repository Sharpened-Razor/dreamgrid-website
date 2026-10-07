<?php

require_once __DIR__ . '/core/auth.php';

header(
    'Content-Type: application/json; charset=utf-8'
);

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
                : null,

        'session_secret_exists' =>
            is_file(
                __DIR__ .
                '/login/session_secret.php'
            )
    ],
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_SLASHES
);