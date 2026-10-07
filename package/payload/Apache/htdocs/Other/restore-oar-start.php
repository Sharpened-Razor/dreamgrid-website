<?php
require_once __DIR__ . '/login/session.php';
header('Content-Type: application/json');
echo json_encode(['ok'=>false,'error'=>'V48 does not use a separate start endpoint.']);
