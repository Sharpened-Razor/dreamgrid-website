<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/region-ini-import.php';
ag_no_cache();
ag_require_same_origin_post();
ag_require_admin();
header('Content-Type: application/json; charset=utf-8');
try {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $csrf = (string)($_POST['csrf_token'] ?? '');
    $expected = (string)($_SESSION['ag_create_region_csrf'] ?? '');
    if ($expected === '' || !hash_equals($expected, $csrf)) throw new RuntimeException('Reload this page before importing.');
    $file = $_FILES['region_ini'] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 262144 || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Choose a Region INI file up to 256 KB.');
    }
    $raw = file_get_contents($file['tmp_name']);
    if (!is_string($raw)) throw new RuntimeException('The uploaded file could not be read.');
    $import = ag_region_import_parse($raw);
    $token = bin2hex(random_bytes(24));
    $_SESSION['ag_region_ini_import'] = ['token'=>$token, 'raw'=>$raw, 'created'=>time()];
    echo json_encode(['ok'=>true, 'token'=>$token, 'fields'=>$import['fields'], 'settings'=>count($import['values'])], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $error) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>$error->getMessage()], JSON_INVALID_UTF8_SUBSTITUTE);
}