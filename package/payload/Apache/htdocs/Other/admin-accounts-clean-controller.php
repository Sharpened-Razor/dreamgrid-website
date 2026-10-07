<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/navigation.php';
require_once __DIR__ . '/core/icons.php';

ag_no_cache();

$session = ag_require_admin();
$adminPrincipalId = trim((string)($session['principalId'] ?? ''));
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['ag_admin_accounts_csrf'])) {
    $_SESSION['ag_admin_accounts_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = (string)$_SESSION['ag_admin_accounts_csrf'];

function accountStatus(int $userLevel): array
{
    if ($userLevel < 0) {
        return ['DISABLED', 'disabled'];
    }

    if ($userLevel >= 200) {
        return ['ADMIN', 'admin'];
    }

    if ($userLevel >= 50) {
        return ['MEMBER', 'member'];
    }

    return ['USER', 'user'];
}

function createdDisplay($created): string
{
    $value = (int)$created;

    if ($value <= 0) {
        return 'Unknown';
    }

    return date('d M Y g:i A', $value);
}

function validPrincipalId(string $value): bool
{
    return (bool)preg_match(
        '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
        $value
    );
}

function redirectAccounts(array $params = []): never
{
    $url = '/Other/admin-accounts-clean.php';

    if ($params) {
        $url .= '?' . http_build_query($params);
    }

    header('Location: ' . $url, true, 303);
    exit;
}

$dbError = '';
$accounts = [];
$selected = null;
$totalUsers = 0;
$totalAdmins = 0;
$totalDisabled = 0;

foreach (['q', 'id', 'error'] as $field) {
    if (isset($_GET[$field]) && !is_string($_GET[$field])) {
        http_response_code(400);
        exit('Invalid account request.');
    }
}
$q = trim((string)($_GET['q'] ?? ''));
$selectedId = trim((string)($_GET['id'] ?? ''));

$con = ag_db_connect();

if (!$con) {
    $dbError = 'Grid account database is temporarily unavailable.';
}

/*
 * ============================================================
 * WRITE ACTIONS
 * ============================================================
 */

if (
    $con &&
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
) {
    $postedCsrf = (string)($_POST['csrf_token'] ?? '');

    if (
        $postedCsrf === '' ||
        !hash_equals($csrfToken, $postedCsrf)
    ) {
        redirectAccounts(['error' => 'csrf']);
    }

    $action = trim((string)($_POST['action'] ?? ''));
    $targetId = trim((string)($_POST['principal_id'] ?? ''));

    if (!validPrincipalId($targetId)) {
        redirectAccounts(['error' => 'invalid']);
    }

    $targetSql =
        'SELECT FirstName, LastName, Email, UserLevel ' .
        'FROM UserAccounts ' .
        'WHERE PrincipalID = ? ' .
        'LIMIT 1';

    $targetStmt = @mysqli_prepare($con, $targetSql);

    if (!$targetStmt) {
        redirectAccounts(['error' => 'database']);
    }

    mysqli_stmt_bind_param($targetStmt, 's', $targetId);
    mysqli_stmt_execute($targetStmt);
    mysqli_stmt_bind_result(
        $targetStmt,
        $targetFirst,
        $targetLast,
        $targetEmail,
        $targetLevel
    );

    $targetFound = mysqli_stmt_fetch($targetStmt);

    mysqli_stmt_close($targetStmt);

    if (!$targetFound) {
        redirectAccounts(['error' => 'missing']);
    }

    $targetLevel = (int)$targetLevel;
    $targetName = trim((string)$targetFirst . ' ' . (string)$targetLast);

    /*
     * --------------------------------------------------------
     * SAVE EMAIL + USER LEVEL
     * --------------------------------------------------------
     */

    if ($action === 'save_account') {
        $newEmail = trim((string)($_POST['email'] ?? ''));
        $newLevelRaw = trim((string)($_POST['user_level'] ?? ''));

        if (
            $newEmail !== '' &&
            !filter_var($newEmail, FILTER_VALIDATE_EMAIL)
        ) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'email',
            ]);
        }

        if (strlen($newEmail) > 254) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'email',
            ]);
        }

        if (
            $newLevelRaw === '' ||
            !preg_match('/^-?\d+$/', $newLevelRaw)
        ) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'level',
            ]);
        }

        $newLevel = (int)$newLevelRaw;

        if ($newLevel < -1 || $newLevel > 255) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'level',
            ]);
        }

        /*
         * Do not let the currently signed-in Grid Owner remove
         * their own administrator access through this page.
         */
        if (
            hash_equals($adminPrincipalId, $targetId) &&
            $newLevel < 200
        ) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'selflevel',
            ]);
        }

        $updateSql =
            'UPDATE UserAccounts ' .
            'SET Email = ?, UserLevel = ? ' .
            'WHERE PrincipalID = ? ' .
            'LIMIT 1';

        $updateStmt = @mysqli_prepare($con, $updateSql);

        if (!$updateStmt) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'database',
            ]);
        }

        mysqli_stmt_bind_param(
            $updateStmt,
            'sis',
            $newEmail,
            $newLevel,
            $targetId
        );

        $ok = mysqli_stmt_execute($updateStmt);

        mysqli_stmt_close($updateStmt);

        if (!$ok) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'database',
            ]);
        }

        $_SESSION['ag_admin_accounts_csrf'] = bin2hex(random_bytes(32));

        redirectAccounts([
            'id' => $targetId,
            'q' => $q,
            'saved' => '1',
        ]);
    }

    /*
     * --------------------------------------------------------
     * DISABLE
     * --------------------------------------------------------
     */

    if ($action === 'disable_account') {
        if (hash_equals($adminPrincipalId, $targetId)) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'selfdisable',
            ]);
        }

        $disabledLevel = -1;

        $disableSql =
            'UPDATE UserAccounts ' .
            'SET UserLevel = ? ' .
            'WHERE PrincipalID = ? ' .
            'LIMIT 1';

        $disableStmt = @mysqli_prepare($con, $disableSql);

        if (!$disableStmt) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'database',
            ]);
        }

        mysqli_stmt_bind_param(
            $disableStmt,
            'is',
            $disabledLevel,
            $targetId
        );

        $ok = mysqli_stmt_execute($disableStmt);

        mysqli_stmt_close($disableStmt);

        if (!$ok) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'database',
            ]);
        }

        $_SESSION['ag_admin_accounts_csrf'] = bin2hex(random_bytes(32));

        redirectAccounts([
            'id' => $targetId,
            'q' => $q,
            'disabled' => '1',
        ]);
    }

    /*
     * --------------------------------------------------------
     * PASSWORD RESET
     * --------------------------------------------------------
     */

    if ($action === 'reset_password') {
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if (
            strlen($newPassword) < 8 ||
            strlen($newPassword) > 128 ||
            $newPassword !== $confirmPassword
        ) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'password',
            ]);
        }

        $authCheckSql =
            'SELECT UUID ' .
            'FROM auth ' .
            'WHERE UUID = ? ' .
            'LIMIT 1';

        $authCheckStmt = @mysqli_prepare($con, $authCheckSql);

        if (!$authCheckStmt) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'database',
            ]);
        }

        mysqli_stmt_bind_param(
            $authCheckStmt,
            's',
            $targetId
        );

        mysqli_stmt_execute($authCheckStmt);
        mysqli_stmt_store_result($authCheckStmt);

        $authExists =
            mysqli_stmt_num_rows($authCheckStmt) === 1;

        mysqli_stmt_close($authCheckStmt);

        if (!$authExists) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'authmissing',
            ]);
        }

        $newSalt =
            md5(
                bin2hex(random_bytes(32)) .
                microtime(true)
            );

        $newHash =
            md5(
                md5($newPassword) .
                ':' .
                $newSalt
            );

        $passwordSql =
            'UPDATE auth ' .
            'SET passwordHash = ?, passwordSalt = ? ' .
            'WHERE UUID = ? ' .
            'LIMIT 1';

        $passwordStmt = @mysqli_prepare($con, $passwordSql);

        if (!$passwordStmt) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'database',
            ]);
        }

        mysqli_stmt_bind_param(
            $passwordStmt,
            'sss',
            $newHash,
            $newSalt,
            $targetId
        );

        $ok = mysqli_stmt_execute($passwordStmt);

        mysqli_stmt_close($passwordStmt);

        if (!$ok) {
            redirectAccounts([
                'id' => $targetId,
                'q' => $q,
                'error' => 'database',
            ]);
        }

        $_SESSION['ag_admin_accounts_csrf'] = bin2hex(random_bytes(32));

        redirectAccounts([
            'id' => $targetId,
            'q' => $q,
            'password' => '1',
        ]);
    }

    redirectAccounts(['error' => 'invalid']);
}

/*
 * ============================================================
 * READ DATA
 * ============================================================
 */

if ($con) {
    $summarySql =
        'SELECT ' .
        'COUNT(*), ' .
        'COALESCE(SUM(CASE WHEN UserLevel >= 200 THEN 1 ELSE 0 END),0), ' .
        'COALESCE(SUM(CASE WHEN UserLevel < 0 THEN 1 ELSE 0 END),0) ' .
        'FROM UserAccounts';

    $summaryResult = @mysqli_query($con, $summarySql);

    if ($summaryResult) {
        $summary = mysqli_fetch_row($summaryResult);

        if ($summary) {
            $totalUsers = (int)($summary[0] ?? 0);
            $totalAdmins = (int)($summary[1] ?? 0);
            $totalDisabled = (int)($summary[2] ?? 0);
        }

        mysqli_free_result($summaryResult);
    }

    if ($selectedId !== '' && validPrincipalId($selectedId)) {
        $detailSql =
            'SELECT PrincipalID, FirstName, LastName, Email, Created, UserLevel ' .
            'FROM UserAccounts ' .
            'WHERE PrincipalID = ? ' .
            'LIMIT 1';

        $detailStmt = @mysqli_prepare($con, $detailSql);

        if ($detailStmt) {
            mysqli_stmt_bind_param($detailStmt, 's', $selectedId);
            mysqli_stmt_execute($detailStmt);

            $detailResult = mysqli_stmt_get_result($detailStmt);

            if ($detailResult) {
                $row = mysqli_fetch_assoc($detailResult);

                if ($row) {
                    $selected = $row;
                }

                mysqli_free_result($detailResult);
            }

            mysqli_stmt_close($detailStmt);
        }
    }

    if ($q === '') {
        $listSql =
            'SELECT PrincipalID, FirstName, LastName, Email, Created, UserLevel ' .
            'FROM UserAccounts ' .
            'ORDER BY FirstName, LastName';

        $listResult = @mysqli_query($con, $listSql);

        if ($listResult) {
            while ($row = mysqli_fetch_assoc($listResult)) {
                $accounts[] = $row;
            }

            mysqli_free_result($listResult);
        }
        else {
            $dbError = 'Grid could not load the account list.';
        }
    }
    else {
        $like = '%' . $q . '%';

        $listSql =
            'SELECT PrincipalID, FirstName, LastName, Email, Created, UserLevel ' .
            'FROM UserAccounts ' .
            'WHERE FirstName LIKE ? ' .
            'OR LastName LIKE ? ' .
            'OR CONCAT(FirstName, " ", LastName) LIKE ? ' .
            'OR Email LIKE ? ' .
            'OR PrincipalID LIKE ? ' .
            'ORDER BY FirstName, LastName';

        $listStmt = @mysqli_prepare($con, $listSql);

        if ($listStmt) {
            mysqli_stmt_bind_param(
                $listStmt,
                'sssss',
                $like,
                $like,
                $like,
                $like,
                $like
            );

            mysqli_stmt_execute($listStmt);

            $listResult = mysqli_stmt_get_result($listStmt);

            if ($listResult) {
                while ($row = mysqli_fetch_assoc($listResult)) {
                    $accounts[] = $row;
                }

                mysqli_free_result($listResult);
            }
            else {
                $dbError = 'Grid could not load the account search.';
            }

            mysqli_stmt_close($listStmt);
        }
        else {
            $dbError = 'Grid could not prepare the account search.';
        }
    }

    mysqli_close($con);
}

$errorCode = trim((string)($_GET['error'] ?? ''));

$errorMessages = [
    'csrf' => 'Your security token expired. Reload the page and try again.',
    'invalid' => 'The account request was invalid.',
    'missing' => 'The selected account could not be found.',
    'database' => 'Grid could not save that account change. Nothing was changed.',
    'email' => 'Enter a valid email address or leave the email field empty.',
    'level' => 'UserLevel must be a whole number from -1 to 255.',
    'selflevel' => 'You cannot remove your own administrator level while signed in.',
    'selfdisable' => 'You cannot disable the account you are currently signed in with.',
    'password' => 'Passwords must match and contain 8 to 128 characters.',
    'authmissing' => 'The selected avatar has no OpenSim authentication record.',
];

?>
