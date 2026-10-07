<?php
declare(strict_types=1);
require_once __DIR__ . '/core/bootstrap.php';
ag_require_login();
require_once __DIR__ . '/core/map3d-sculpt.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function agt_fail(string $message, int $status = 500): never {
    http_response_code($status); header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>$message]); exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') { header('Allow: GET'); agt_fail('Read requests only.',405); }
$raw = $_GET['shape'] ?? null;
if (!is_string($raw) || strlen($raw)>240 || !preg_match('/^-?\d+(?:,-?\d+){19}$/D',$raw)) agt_fail('Invalid prim parameters.',400);
$shape = array_map('intval',explode(',',$raw));
if ($shape[0]!==9) agt_fail('Unsupported object type.',422);
foreach ($shape as $i=>$value) {
    $min = in_array($i,[11,12,13,14,15,16,17,19],true) ? -128 : 0;
    $max = in_array($i,[4,5,6,7,8],true) ? 50000 : 255;
    if ($value<$min || $value>$max) agt_fail('Prim parameter out of range.',400);
}
if (($shape[2]&7)>5 || !in_array($shape[3],[16,32,33,48,64,128],true)) agt_fail('Unsupported prim profile/path.',422);
$canonical = implode(',',$shape);
map3d_sculpt_generate($canonical,'prim:' . implode('_',$shape),0);
