<?php
/*
 * Grid - CENTRAL BOOTSTRAP
 *
 * Include this from Australia Apache/PHP pages.
 */

require_once __DIR__ . '/routes.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/request-security.php';
require_once __DIR__ . '/icons.php';

function ag_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ag_no_cache(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

function ag_redirect(string $url, int $status = 302): void
{
    header('Location: ' . $url, true, $status);
    exit;
}

function ag_avatar_name(array $session): string
{
    return trim((string)($session['avatar'] ?? ''));
}

function ag_user_level(array $session): int
{
    return (int)($session['level'] ?? 0);
}