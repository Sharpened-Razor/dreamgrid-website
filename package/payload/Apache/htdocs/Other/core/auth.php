<?php
/*
 * Grid - CENTRAL AUTHENTICATION HELPERS
 *
 * Validates the signed dg_session used by the /Other Control Center.
 * This does NOT create a second authentication system.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/routes.php';
require_once __DIR__ . '/../login/session.php';

function ag_current_session(): ?array
{
    static $resolved = false;
    static $resolvedSession = null;

    if ($resolved) {
        return $resolvedSession;
    }

    $resolved = true;

    $cookieSession = dreamGridCurrentSession();

    if (!$cookieSession) {
        return null;
    }

    $principalId = trim((string)($cookieSession['principalId'] ?? ''));

    if ($principalId === '') {
        return null;
    }

    $con = ag_db_connect();

    if (!$con) {
        return null;
    }

    $sql =
        'SELECT FirstName, LastName, UserLevel ' .
        'FROM UserAccounts ' .
        'WHERE PrincipalID = ? ' .
        'LIMIT 1';

    $stmt = null;
    try {
        $stmt = @mysqli_prepare($con, $sql);
        if (!$stmt) return null;
        if (!@mysqli_stmt_bind_param($stmt, 's', $principalId) || !@mysqli_stmt_execute($stmt)) return null;
        if (!@mysqli_stmt_bind_result($stmt, $firstName, $lastName, $userLevel)) return null;
        $found = @mysqli_stmt_fetch($stmt);
    } catch (mysqli_sql_exception $error) {
        // A failed account lookup must never grant access or expose database details.
        return null;
    } finally {
        if ($stmt) mysqli_stmt_close($stmt);
        mysqli_close($con);
    }

    if (!$found) {
        return null;
    }

    $userLevel = (int)$userLevel;

    if ($userLevel < 0) {
        return null;
    }

    $resolvedSession = [
        'avatar' => trim((string)$firstName . ' ' . (string)$lastName),
        'principalId' => $principalId,
        'level' => $userLevel,
    ];

    return $resolvedSession;
}

function ag_is_logged_in(): bool
{
    return ag_current_session() !== null;
}

function ag_is_admin(?array $session = null): bool
{
    $session = $session ?? ag_current_session();

    if (!$session) {
        return false;
    }

    return (int)($session['level'] ?? 0) >= 200;
}

function ag_require_login(): array
{
    /*
     * ============================================================
     * AUSTRALIA AUTH NO-CACHE V1
     *
     * Protected pages and authentication redirects must never be
     * stored by the browser. This runs BEFORE session validation,
     * so a temporary authentication failure cannot leave a cached
     * redirect back to the public login page.
     * ============================================================
     */

    header(
        'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
    );

    header(
        'Pragma: no-cache'
    );

    header(
        'Expires: 0'
    );

    $session = ag_current_session();

    if (!$session) {
        header('Location: ' . ag_route('login'));
        exit;
    }

    return $session;
}

/*
 * ============================================================
 * Grid - USER LEVEL POLICY
 * ============================================================
 *
 * Level 0+   = active signed-in resident
 * Level 50+  = privileged resident operations
 * Level 200+ = grid administrator
 *
 * NOTE:
 * UserLevel grants permission to perform a TYPE of operation.
 * It does NOT grant ownership of another avatar's resources.
 * Account, inventory, regions, IARs and messages must still
 * be checked against the signed-in PrincipalID.
 */

function ag_has_user_level(
    int $minimumLevel,
    ?array $session = null
): bool
{
    $session =
        $session ??
        ag_current_session();

    if (!$session) {
        return false;
    }

    return
        (int)($session['level'] ?? -1) >=
        $minimumLevel;
}


function ag_can_use_privileged_user_tools(
    ?array $session = null
): bool
{
    return
        ag_has_user_level(
            50,
            $session
        );
}


function ag_require_user_level(
    int $minimumLevel
): array
{
    $session =
        ag_require_login();

    if (
        !ag_has_user_level(
            $minimumLevel,
            $session
        )
    ) {
        http_response_code(403);

        echo 'Permission denied.';

        exit;
    }

    return $session;
}

function ag_require_admin(): array
{
    $session = ag_require_login();

    if ((int)($session['level'] ?? 0) < 200) {
        header('Location: ' . ag_route('user_home'));
        exit;
    }

    return $session;
}

function ag_clear_session_cookie(): void
{
    setcookie('dg_session', '', [
        'expires' => time() - 3600,
        'path' => '/Other/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    unset($_COOKIE['dg_session']);
}