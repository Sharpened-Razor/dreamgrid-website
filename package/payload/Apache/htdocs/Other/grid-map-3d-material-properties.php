<?php
declare(strict_types=1);
require_once __DIR__.'/core/bootstrap.php';
ag_require_login();
ag_require_same_origin_post();
require_once __DIR__.'/core/map3d-material-properties.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$raw=file_get_contents('php://input',false,null,0,16385);
$input=is_string($raw)&&strlen($raw)<=16384?json_decode($raw,true):null;
$ids=$input['ids']??null;
if(!is_array($ids)||!array_is_list($ids)||count($ids)<1||count($ids)>16){http_response_code(400);echo '{"ok":false,"error":"Invalid material batch"}';exit;}
foreach($ids as $id){if(!is_string($id)||!preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/D',$id)){
    http_response_code(400);echo '{"ok":false,"error":"Invalid material UUID"}';exit;
}}
echo json_encode(['ok'=>true,'Results'=>map3d_material_properties($ids)],JSON_UNESCAPED_SLASHES);
