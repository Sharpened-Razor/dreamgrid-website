<?php
// Include this from any page under /API to read the logged-in avatar set by login.php.
// Returns null if there is no cookie, it's malformed, expired, or its signature doesn't
// match the server secret (i.e. was not issued by login.php).
function dreamGridCurrentSession() {
    if (empty($_COOKIE['dg_session'])) {
        return null;
    }

    $dot = strrpos($_COOKIE['dg_session'], '.');
    if ($dot === false) {
        return null;
    }

    $payload = substr($_COOKIE['dg_session'], 0, $dot);
    $signature = substr($_COOKIE['dg_session'], $dot + 1);

    $keyFile = __DIR__ . '/session_secret.php';
    if (!file_exists($keyFile)) {
        return null;
    }
    $secret = include $keyFile;

    $expected = hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expected, $signature)) {
        return null;
    }

    $session = json_decode(base64_decode($payload), true);
    if (!is_array($session) || !isset($session['avatar'], $session['principalId'], $session['level'])) {
        return null;
    }

    return $session;
}
