<?php

require_once __DIR__ . "/core/bootstrap.php";

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

$session = ag_current_session();

if (!$session) {
    http_response_code(401);
    echo json_encode([
        "ok" => false,
        "signedIn" => false
    ]);
    exit;
}

$level = function_exists("ag_user_level")
    ? (int) ag_user_level($session)
    : (int)($session["level"] ?? 0);

$isAdmin = function_exists("ag_is_admin")
    ? (bool) ag_is_admin($session)
    : ($level >= 200);

if ($level >= 250) {
    $role = "GRID OWNER";
} elseif ($isAdmin) {
    $role = "ADMIN";
} else {
    $role = "USER";
}

echo json_encode([
    "ok" => true,
    "signedIn" => true,
    "role" => $role,
    "level" => $level
]);