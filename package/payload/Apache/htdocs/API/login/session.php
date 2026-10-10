<?php
// Include this from any page under /API to read the logged-in avatar set by login.php.
// Returns null if there is no cookie, it's malformed, expired, or its signature doesn't
// match the server secret (i.e. was not issued by login.php).
function dreamGridCurrentSession() {
    if (!is_string($_COOKIE['dg_session'] ?? null) || $_COOKIE['dg_session'] === '' || strlen($_COOKIE['dg_session']) > 8192) {
        return null;
    }

    $dot = strrpos($_COOKIE['dg_session'], '.');
    if ($dot === false) {
        return null;
    }

    $payload = substr($_COOKIE['dg_session'], 0, $dot);
    $signature = substr($_COOKIE['dg_session'], $dot + 1);
    if (!preg_match('/^[a-f0-9]{64}$/D', $signature)) return null;

    $keyFile = __DIR__ . '/session_secret.php';
    if (!file_exists($keyFile)) {
        return null;
    }
    $secret = include $keyFile;
    if (!is_string($secret) || $secret === '') return null;

    $expected = hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expected, $signature)) {
        return null;
    }

    $decoded = base64_decode($payload, true);
    if ($decoded === false) return null;
    $session = json_decode($decoded, true);
    if (!is_array($session) || !is_string($session['avatar'] ?? null) || !is_string($session['principalId'] ?? null) || !is_int($session['level'] ?? null)) {
        return null;
    }
    $now = time();
    if (!is_int($session['issuedAt'] ?? null) || !is_int($session['expiresAt'] ?? null) ||
        $session['issuedAt'] <= 0 || $session['issuedAt'] > $now || $session['expiresAt'] <= $now ||
        $session['expiresAt'] <= $session['issuedAt'] || $session['expiresAt'] - $session['issuedAt'] > 8 * 3600) return null;

    return $session;
}
