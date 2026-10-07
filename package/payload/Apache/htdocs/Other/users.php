<?php
require_once __DIR__ . '/core/bootstrap.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$s = ag_current_session();
if (!$s) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Not logged in']);
    exit;
}

if (!ag_is_admin($s)) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'Grid Owner access required']);
    exit;
}

$con = ag_db_connect();

if (!$con) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Could not connect to Robust database']);
    exit;
}

$sql = "SELECT FirstName, LastName, UserLevel, PrincipalID
        FROM UserAccounts
        WHERE UserLevel >= 0
          AND FirstName <> ''
          AND LastName <> ''
        ORDER BY FirstName ASC, LastName ASC";

$result = mysqli_query($con, $sql);
if (!$result) {
    mysqli_close($con);
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Could not read local users']);
    exit;
}

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $name = trim($row['FirstName'] . ' ' . $row['LastName']);
    if ($name === '') continue;

    $users[] = [
        'avatar' => $name,
        'level' => (int)$row['UserLevel'],
        'principalId' => (string)$row['PrincipalID']
    ];
}

mysqli_free_result($result);
mysqli_close($con);

echo json_encode(['ok'=>true,'users'=>$users]);
