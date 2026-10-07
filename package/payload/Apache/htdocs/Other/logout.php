<?php
require_once __DIR__ . '/core/bootstrap.php';
ag_no_cache();
ag_clear_session_cookie();
// Clear PHP session state used for CSRF and staged imports as well.
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600, 'path' => $params['path'],
        'domain' => $params['domain'], 'secure' => $params['secure'],
        'httponly' => true, 'samesite' => 'Lax',
    ]);
}
session_destroy();
ag_redirect('/Other/index.php', 303);