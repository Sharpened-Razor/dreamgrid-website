<?php
// Shared verifier for the existing signed dg_session cookie.
function dreamGridDecodeSession($cookie, string $secret, int $now): ?array
{
    if (!is_string($cookie) || $cookie === '' || strlen($cookie) > 8192 || $secret === '') return null;
    $dot = strrpos($cookie, '.');
    if ($dot === false) return null;
    $payload = substr($cookie, 0, $dot);
    $signature = substr($cookie, $dot + 1);
    if (!preg_match('/^[a-f0-9]{64}$/D', $signature) ||
        !hash_equals(hash_hmac('sha256', $payload, $secret), $signature)) return null;
    $decoded = base64_decode($payload, true);
    if ($decoded === false) return null;
    $session = json_decode($decoded, true);
    if (!is_array($session) || !is_string($session['avatar'] ?? null) ||
        !is_string($session['principalId'] ?? null) || !is_int($session['level'] ?? null) ||
        !preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/iD', $session['principalId'])) return null;
    $issued = $session['issuedAt'] ?? null;
    $expires = $session['expiresAt'] ?? null;
    if (!is_int($issued) || !is_int($expires) || $issued <= 0 || $issued > $now ||
        $expires <= $now || $expires <= $issued || $expires - $issued > 8 * 3600) return null;
    return $session;
}

function dreamGridCurrentSession(): ?array
{
    $cookie = $_COOKIE['dg_session'] ?? null;
    if (!is_string($cookie) || $cookie === '' || strlen($cookie) > 8192) return null;
    $keyFile = __DIR__ . '/session_secret.php';
    if (!is_file($keyFile)) return null;
    $secret = include $keyFile;
    if (!is_string($secret) || $secret === '') return null;
    return dreamGridDecodeSession($cookie, $secret, time());
}