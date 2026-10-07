<?php
require_once __DIR__ . '/login/session.php';
header('Content-Type: application/json');
$s = dreamGridCurrentSession();
if (!$s) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }
echo json_encode(['ok'=>true,'avatar'=>$s['avatar'],'principalId'=>$s['principalId'],'level'=>(int)$s['level']]);
