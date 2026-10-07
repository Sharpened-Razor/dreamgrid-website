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
    $url = '/Other/admin-accounts.php';

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
            'ORDER BY FirstName, LastName ' .
            'LIMIT 200';

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
            'ORDER BY FirstName, LastName ' .
            'LIMIT 200';

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
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Accounts</title>
<link rel="stylesheet" href="/Other/core/site-shell.css?v=1">

<style>
.accounts-summary{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:14px;
    margin:0 0 20px;
}
.summary-box{
    padding:18px;
    border:1px solid rgba(255,255,255,.13);
    border-radius:14px;
    background:rgba(14,20,24,.94);
}
.summary-box strong{
    display:block;
    margin-top:5px;
    color:#f4b323;
    font-size:28px;
}
.searchbar{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin:0 0 18px;
}
.searchbar input{
    flex:1 1 300px;
    min-height:44px;
    padding:0 14px;
    border:1px solid rgba(255,255,255,.17);
    border-radius:10px;
    color:#fff;
    background:#090e11;
}
.table-wrap{
    overflow:auto;
    border:1px solid rgba(255,255,255,.13);
    border-radius:14px;
    background:rgba(14,20,24,.94);
}
.accounts-table{
    width:100%;
    min-width:900px;
    border-collapse:collapse;
}
.accounts-table th,
.accounts-table td{
    padding:13px 14px;
    text-align:left;
    border-bottom:1px solid rgba(255,255,255,.08);
}
.accounts-table th{
    color:#f4b323;
    font-size:12px;
    letter-spacing:.08em;
}
.accounts-table tr:last-child td{
    border-bottom:0;
}
.account-name{
    font-weight:900;
}
.status{
    display:inline-flex;
    padding:5px 9px;
    border-radius:999px;
    font-size:11px;
    font-weight:900;
    border:1px solid rgba(255,255,255,.15);
}
.status.admin{color:#ffd47a;background:rgba(244,179,35,.10)}
.status.member{color:#bfe7ff;background:rgba(43,148,214,.10)}
.status.user{color:#caf4da;background:rgba(47,130,76,.13)}
.status.disabled{color:#ffc6c6;background:rgba(143,40,40,.16)}
.detail{
    margin:20px 0;
    padding:22px;
    border:1px solid rgba(244,179,35,.30);
    border-radius:16px;
    background:rgba(244,179,35,.06);
}
.detail-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:12px;
}
.detail-item{
    padding:13px;
    border:1px solid rgba(255,255,255,.09);
    border-radius:10px;
    background:rgba(5,9,11,.55);
}
.detail-item span.label{
    display:block;
    margin-bottom:5px;
    color:#8fa0aa;
    font-size:11px;
    font-weight:900;
    letter-spacing:.08em;
}
.form-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:14px;
    margin-top:18px;
}
.form-field label{
    display:block;
    margin-bottom:6px;
    color:#aebbc3;
    font-size:12px;
    font-weight:900;
}
.form-field input{
    width:100%;
    min-height:42px;
    padding:0 12px;
    border:1px solid rgba(255,255,255,.16);
    border-radius:9px;
    color:#fff;
    background:#090e11;
}
.actions-line{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:16px;
}
.danger-button{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:42px;
    padding:0 15px;
    border:1px solid rgba(255,96,96,.35);
    border-radius:10px;
    color:#ffd4d4;
    background:rgba(126,31,31,.28);
    font-weight:900;
    cursor:pointer;
}
.small-button{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:34px;
    padding:0 10px;
    border:1px solid rgba(255,255,255,.15);
    border-radius:8px;
    color:#edf2f4;
    background:#172128;
    font-size:12px;
    font-weight:900;
    text-decoration:none;
}
.account-tools{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
    margin-top:18px;
}
.tool-box{
    padding:17px;
    border:1px solid rgba(255,255,255,.11);
    border-radius:12px;
    background:rgba(5,9,11,.48);
}
.tool-box h3{
    margin:0 0 12px;
}
@media(max-width:800px){
    .account-tools{grid-template-columns:1fr}
}
</style>

<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">

<style id="manage-accounts-stage1-design">

/* ==========================================================
   MANAGE ACCOUNTS - DESIGN STAGE 1
   Toolbar + Summary + Table
   ========================================================== */


/* ----------------------------------------------------------
   SUMMARY CARDS
   ---------------------------------------------------------- */

.accounts-summary{

    display:grid !important;

    grid-template-columns:
        repeat(4,minmax(0,1fr)) !important;

    gap:12px !important;

    margin:
        0 0 16px !important;
}


.accounts-summary .summary-box{

    min-height:
        76px !important;

    padding:
        14px 17px !important;

    display:flex !important;

    flex-direction:
        column !important;

    justify-content:
        center !important;

    box-sizing:
        border-box !important;

    border:
        1px solid rgba(242,182,50,.24) !important;

    border-radius:
        12px !important;

    background:
        linear-gradient(
            180deg,
            rgba(13,20,24,.98),
            rgba(4,9,12,.98)
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.05),
        0 8px 20px rgba(0,0,0,.22) !important;

    font-size:
        12px !important;

    font-weight:
        800 !important;

    letter-spacing:
        .025em !important;
}


.accounts-summary .summary-box strong{

    display:block !important;

    margin-top:
        5px !important;

    color:
        #f4b323 !important;

    font-size:
        25px !important;

    line-height:
        1 !important;
}


/* ----------------------------------------------------------
   SEARCH + CREATE TOOLBAR
   ---------------------------------------------------------- */

.accounts-toolbar{

    display:grid !important;

    grid-template-columns:
        minmax(0,1fr) auto !important;

    align-items:
        stretch !important;

    gap:
        12px !important;

    margin:
        0 0 16px !important;
}


.accounts-toolbar .searchbar{

    display:flex !important;

    align-items:
        center !important;

    flex-wrap:
        nowrap !important;

    gap:
        9px !important;

    margin:
        0 !important;

    padding:
        9px !important;

    box-sizing:
        border-box !important;

    border:
        1px solid rgba(255,255,255,.12) !important;

    border-radius:
        12px !important;

    background:
        rgba(3,8,11,.96) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.04) !important;
}


.accounts-toolbar .searchbar input{

    flex:
        1 1 auto !important;

    width:
        100% !important;

    min-height:
        42px !important;

    padding:
        0 14px !important;

    box-sizing:
        border-box !important;

    border:
        1px solid rgba(255,255,255,.18) !important;

    border-radius:
        8px !important;

    background:
        #070c0f !important;

    color:
        #f4f6f7 !important;

    font-size:
        14px !important;
}


.accounts-toolbar .searchbar input:focus{

    outline:
        none !important;

    border-color:
        rgba(242,182,50,.78) !important;

    box-shadow:
        0 0 0 2px rgba(242,182,50,.10) !important;
}


.accounts-toolbar .create-account-action{

    display:flex !important;

    align-items:
        stretch !important;

    justify-content:
        flex-end !important;

    margin:
        0 !important;
}


.accounts-toolbar .create-account-action .ag-button{

    min-height:
        60px !important;

    min-width:
        160px !important;

    display:inline-flex !important;

    align-items:center !important;

    justify-content:center !important;

    padding:
        0 20px !important;

    box-sizing:
        border-box !important;

    border-radius:
        11px !important;

    font-weight:
        900 !important;

    white-space:
        nowrap !important;
}


/* ----------------------------------------------------------
   TABLE CONTAINER
   ---------------------------------------------------------- */

.table-wrap{

    overflow:
        auto !important;

    border:
        1px solid rgba(242,182,50,.22) !important;

    border-radius:
        14px !important;

    background:
        rgba(2,7,10,.975) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.04),
        0 12px 30px rgba(0,0,0,.30) !important;
}


/* ----------------------------------------------------------
   ACCOUNT TABLE
   ---------------------------------------------------------- */

.accounts-table{

    width:
        100% !important;

    min-width:
        900px !important;

    border-collapse:
        collapse !important;

    font-size:
        13px !important;
}


.accounts-table thead{

    background:
        rgba(1,5,7,.99) !important;
}


.accounts-table th{

    padding:
        14px 16px !important;

    color:
        #f4b323 !important;

    font-size:
        12px !important;

    font-weight:
        900 !important;

    letter-spacing:
        .075em !important;

    border-bottom:
        1px solid rgba(242,182,50,.26) !important;
}


.accounts-table td{

    padding:
        15px 16px !important;

    color:
        #e7ecef !important;

    line-height:
        1.3 !important;

    border-bottom:
        1px solid rgba(255,255,255,.075) !important;
}


.accounts-table tbody tr{

    background:
        rgba(10,16,20,.72) !important;

    transition:
        background .14s ease !important;
}


.accounts-table tbody tr:nth-child(even){

    background:
        rgba(15,22,26,.76) !important;
}


.accounts-table tbody tr:hover{

    background:
        rgba(242,182,50,.075) !important;
}


.accounts-table tbody tr:last-child td{

    border-bottom:
        0 !important;
}


/* ----------------------------------------------------------
   AVATAR
   ---------------------------------------------------------- */

.accounts-table .account-name{

    font-size:
        13px !important;

    font-weight:
        900 !important;

    color:
        #ffffff !important;
}


/* ----------------------------------------------------------
   ROLE / STATUS BADGES
   ---------------------------------------------------------- */

.accounts-table .status{

    min-width:
        66px !important;

    min-height:
        27px !important;

    display:inline-flex !important;

    align-items:center !important;

    justify-content:center !important;

    padding:
        0 10px !important;

    box-sizing:
        border-box !important;

    font-size:
        11px !important;

    font-weight:
        900 !important;

    text-align:
        center !important;
}


/* ----------------------------------------------------------
   OPEN BUTTON
   ---------------------------------------------------------- */

.accounts-table .small-button{

    min-width:
        62px !important;

    min-height:
        34px !important;

    padding:
        0 12px !important;

    display:inline-flex !important;

    align-items:center !important;

    justify-content:center !important;

    font-size:
        12px !important;

    font-weight:
        900 !important;
}


/* ----------------------------------------------------------
   RESPONSIVE
   ---------------------------------------------------------- */

@media (max-width:1050px){

    .accounts-summary{

        grid-template-columns:
            repeat(2,minmax(0,1fr)) !important;
    }
}


@media (max-width:760px){

    .accounts-toolbar{

        grid-template-columns:
            1fr !important;
    }


    .accounts-toolbar .create-account-action{

        justify-content:
            stretch !important;
    }


    .accounts-toolbar .create-account-action .ag-button{

        width:
            100% !important;

        min-height:
            46px !important;
    }
}


@media (max-width:560px){

    .accounts-summary{

        grid-template-columns:
            1fr !important;
    }


    .accounts-toolbar .searchbar{

        flex-wrap:
            wrap !important;
    }
}

</style>




<style id="manage-accounts-detail-design">

/* ==========================================================
   MANAGE ACCOUNTS
   SELECTED ACCOUNT DETAIL DESIGN V1
   ========================================================== */


/* ----------------------------------------------------------
   MAIN ACCOUNT DETAILS CARD
   ---------------------------------------------------------- */

.detail{

    margin:
        18px 0 22px !important;

    padding:
        22px !important;

    box-sizing:
        border-box !important;

    border:
        1px solid rgba(244,179,35,.48) !important;

    border-radius:
        16px !important;

    background:
        linear-gradient(
            145deg,
            rgba(22,30,35,.985) 0%,
            rgba(7,12,15,.99) 52%,
            rgba(2,6,8,.995) 100%
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.07),
        0 12px 30px rgba(0,0,0,.40) !important;

    backdrop-filter:
        blur(8px) !important;
}


/* ----------------------------------------------------------
   ACCOUNT DETAILS HEADING
   ---------------------------------------------------------- */

.detail > .ag-eyebrow{

    margin:
        0 0 5px !important;

    color:
        #f4b323 !important;

    font-size:
        12px !important;

    font-weight:
        900 !important;

    letter-spacing:
        .12em !important;

    text-transform:
        uppercase !important;
}


.detail > h2{

    margin:
        0 0 18px !important;

    color:
        #ffffff !important;

    font-size:
        26px !important;

    font-weight:
        900 !important;

    line-height:
        1.1 !important;
}


/* ----------------------------------------------------------
   ACCOUNT SUMMARY GRID
   ---------------------------------------------------------- */

.detail .detail-grid{

    display:grid !important;

    grid-template-columns:
        repeat(4,minmax(0,1fr)) !important;

    gap:
        12px !important;

    margin:
        0 !important;
}


/* ----------------------------------------------------------
   SUMMARY MINI CARDS
   ---------------------------------------------------------- */

.detail .detail-item{

    min-height:
        82px !important;

    padding:
        14px 16px !important;

    display:flex !important;

    flex-direction:
        column !important;

    justify-content:
        center !important;

    box-sizing:
        border-box !important;

    border:
        1px solid rgba(255,255,255,.13) !important;

    border-radius:
        11px !important;

    background:
        linear-gradient(
            180deg,
            rgba(26,35,40,.98),
            rgba(9,14,17,.99)
        ) !important;

    color:
        #f0f4f5 !important;

    font-size:
        14px !important;

    font-weight:
        700 !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.05) !important;
}


.detail .detail-item .label{

    display:block !important;

    margin:
        0 0 7px !important;

    color:
        #9eafb8 !important;

    font-size:
        11px !important;

    font-weight:
        900 !important;

    letter-spacing:
        .09em !important;
}


/* Principal ID */

.detail .detail-item[style*="grid-column"]{

    min-height:
        68px !important;

    margin-top:
        0 !important;

    word-break:
        break-all !important;
}


/* ----------------------------------------------------------
   EDIT ACCOUNT FORM CARD
   ---------------------------------------------------------- */

.detail > form{

    margin:
        16px 0 0 !important;

    padding:
        18px !important;

    box-sizing:
        border-box !important;

    border:
        1px solid rgba(255,255,255,.12) !important;

    border-radius:
        12px !important;

    background:
        rgba(4,9,12,.97) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.035) !important;
}


.detail > form .form-grid{

    display:grid !important;

    grid-template-columns:
        repeat(2,minmax(0,1fr)) !important;

    gap:
        14px !important;

    margin:
        0 !important;
}


/* ----------------------------------------------------------
   FORM LABELS
   ---------------------------------------------------------- */

.detail .form-field label{

    display:block !important;

    margin:
        0 0 7px !important;

    color:
        #c5d1d7 !important;

    font-size:
        12px !important;

    font-weight:
        900 !important;

    letter-spacing:
        .035em !important;
}


/* ----------------------------------------------------------
   FORM INPUTS
   ---------------------------------------------------------- */

.detail .form-field input{

    width:
        100% !important;

    min-height:
        44px !important;

    padding:
        0 13px !important;

    box-sizing:
        border-box !important;

    border:
        1px solid rgba(255,255,255,.19) !important;

    border-radius:
        9px !important;

    background:
        #050a0d !important;

    color:
        #ffffff !important;

    font-size:
        14px !important;

    box-shadow:
        inset 0 2px 5px rgba(0,0,0,.45) !important;
}


.detail .form-field input:focus{

    outline:
        none !important;

    border-color:
        rgba(244,179,35,.82) !important;

    box-shadow:
        0 0 0 2px rgba(244,179,35,.10),
        inset 0 2px 5px rgba(0,0,0,.45) !important;
}


/* ----------------------------------------------------------
   ACTION ROWS
   ---------------------------------------------------------- */

.detail .actions-line{

    display:flex !important;

    align-items:center !important;

    gap:
        10px !important;

    margin-top:
        14px !important;
}


/* ----------------------------------------------------------
   RESET / DISABLE TOOL GRID
   ---------------------------------------------------------- */

.detail .account-tools{

    display:grid !important;

    grid-template-columns:
        repeat(2,minmax(0,1fr)) !important;

    gap:
        16px !important;

    margin-top:
        16px !important;
}


/* ----------------------------------------------------------
   TOOL CARDS
   ---------------------------------------------------------- */

.detail .tool-box{

    padding:
        18px !important;

    box-sizing:
        border-box !important;

    border:
        1px solid rgba(255,255,255,.13) !important;

    border-radius:
        12px !important;

    background:
        linear-gradient(
            145deg,
            rgba(20,28,33,.985),
            rgba(5,10,13,.99)
        ) !important;

    color:
        #e4ebee !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.045) !important;
}


.detail .tool-box h3{

    margin:
        0 0 14px !important;

    color:
        #ffffff !important;

    font-size:
        18px !important;

    font-weight:
        900 !important;

    line-height:
        1.1 !important;
}


.detail .tool-box .ag-muted{

    color:
        #b9c6cc !important;

    font-size:
        13px !important;

    line-height:
        1.45 !important;
}


/* ----------------------------------------------------------
   RESET PASSWORD INPUT SPACING
   ---------------------------------------------------------- */

.detail .account-tools .form-field + .form-field{

    margin-top:
        13px !important;
}


/* ----------------------------------------------------------
   DELETE ACCOUNT SAFETY CARD
   This is the direct tool-box underneath account-tools.
   ---------------------------------------------------------- */

.detail > .tool-box{

    margin-top:
        16px !important;

    padding:
        18px !important;

    border:
        1px solid rgba(220,75,75,.48) !important;

    background:
        linear-gradient(
            145deg,
            rgba(43,16,18,.97),
            rgba(16,7,9,.99)
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.035),
        0 8px 20px rgba(0,0,0,.25) !important;
}


.detail > .tool-box h3{

    color:
        #ffdddd !important;
}


/* ----------------------------------------------------------
   BUTTONS INSIDE ACCOUNT DETAILS
   ---------------------------------------------------------- */

.detail .ag-button,
.detail .danger-button{

    min-height:
        40px !important;

    padding:
        0 16px !important;

    display:inline-flex !important;

    align-items:center !important;

    justify-content:center !important;

    box-sizing:
        border-box !important;

    font-size:
        12px !important;

    font-weight:
        900 !important;

    text-decoration:
        none !important;
}


/* Save Account */

.detail > form .ag-button.primary{

    border-color:
        rgba(255,208,91,.85) !important;

    color:
        #151006 !important;

    background:
        linear-gradient(
            180deg,
            #f5ca54,
            #b97910
        ) !important;
}


/* Dangerous actions */

.detail .danger-button{

    border:
        1px solid rgba(235,92,92,.58) !important;

    color:
        #ffdede !important;

    background:
        linear-gradient(
            180deg,
            rgba(126,35,40,.78),
            rgba(60,17,21,.92)
        ) !important;

    border-radius:
        9px !important;
}


.detail .danger-button:hover{

    filter:
        brightness(1.12) !important;
}


/* ----------------------------------------------------------
   RESPONSIVE
   ---------------------------------------------------------- */

@media(max-width:1100px){

    .detail .detail-grid{

        grid-template-columns:
            repeat(2,minmax(0,1fr)) !important;
    }
}


@media(max-width:800px){

    .detail .account-tools,
    .detail > form .form-grid{

        grid-template-columns:
            1fr !important;
    }
}


@media(max-width:600px){

    .detail{

        padding:
            15px !important;
    }


    .detail .detail-grid{

        grid-template-columns:
            1fr !important;
    }


    .detail > h2{

        font-size:
            23px !important;
    }
}

</style>

<style id="manage-accounts-global-glow-icons">

.ag-button,
.danger-button,
.small-button,


.ag-page-button-icon{
    width:24px !important;
    min-width:24px !important;
    height:24px !important;
    flex:0 0 24px !important;
}

.ag-page-button-icon svg{
    width:20px !important;
    height:20px !important;
}

.small-button .ag-page-button-icon{
    width:21px !important;
    min-width:21px !important;
    height:21px !important;
    flex-basis:21px !important;
}

.small-button .ag-page-button-icon svg{
    width:18px !important;
    height:18px !important;
}

</style>

<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=<?= filemtime(__DIR__ . '/assets/css/ag-uniform-site-v12.css') ?>">
<style id="manage-accounts-text-button-size-fix-v1">

/*
============================================================
 MANAGE ACCOUNTS - TEXT ONLY ACTION BUTTONS
============================================================
*/


/*
   SAVE ACCOUNT
   RESET PASSWORD
   DISABLE ACCOUNT
   DELETE ACCOUNT
*/

.detail .actions-line > .ag-button,
.detail .actions-line > .danger-button,
.detail .actions-line > button.ag-button,
.detail .actions-line > button.danger-button,
.detail .actions-line > a.ag-button,
.detail .actions-line > a.danger-button{

    box-sizing:border-box !important;

    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;

    width:auto !important;

    min-width:max-content !important;
    max-width:none !important;

    min-height:40px !important;
    height:40px !important;
    max-height:40px !important;

    padding:0 18px !important;

    margin:0 !important;

    flex:0 0 auto !important;

    position:static !important;

    inset:auto !important;

    transform:none !important;

    white-space:nowrap !important;

    overflow:visible !important;

    line-height:1 !important;

    text-align:center !important;
}


/*
   Make sure the action row itself does not squash
   its buttons down to icon width.
*/

.detail .actions-line{

    display:flex !important;

    align-items:center !important;
    justify-content:flex-start !important;

    flex-wrap:wrap !important;

    gap:10px !important;

    width:100% !important;

    overflow:visible !important;
}


/*
   Prevent any shared square-button rule from
   forcing these text buttons to icon dimensions.
*/

.detail button.ag-button,
.detail a.ag-button,
.detail button.danger-button,
.detail a.danger-button{

    aspect-ratio:auto !important;

    min-inline-size:max-content !important;

    inline-size:auto !important;

    max-inline-size:none !important;
}


/*
   DELETE stays red, but must remain a normal
   horizontal text button.
*/

.detail > .tool-box .danger-button{

    width:auto !important;

    min-width:max-content !important;
    max-width:none !important;

    min-height:40px !important;
    height:40px !important;

    padding:0 18px !important;

    white-space:nowrap !important;
}

</style>

<style id="manage-accounts-search-button-fix-v1">

/*
============================================================
 MANAGE ACCOUNTS - SEARCH BUTTON
 Text-only horizontal button
============================================================
*/

.accounts-toolbar .searchbar > button.ag-button.primary{

    box-sizing:border-box !important;

    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;

    flex:
        0 0 auto !important;

    width:auto !important;

    min-width:
        96px !important;

    max-width:none !important;

    min-height:
        44px !important;

    height:
        44px !important;

    max-height:
        44px !important;

    padding:
        0 20px !important;

    margin:
        0 !important;

    aspect-ratio:
        auto !important;

    inline-size:
        auto !important;

    min-inline-size:
        96px !important;

    max-inline-size:
        none !important;

    position:
        static !important;

    inset:
        auto !important;

    transform:
        none !important;

    white-space:
        nowrap !important;

    overflow:
        visible !important;

    line-height:
        1 !important;

    text-align:
        center !important;
}


/* Keep input and SEARCH aligned */

.accounts-toolbar .searchbar{

    display:flex !important;

    align-items:center !important;

    gap:
        9px !important;
}

</style>

<style id="australia-sentinel-sitewide-v3">


/* ============================================================
   AUSTRALIA SENTINEL 3D CARD SYSTEM V3
   SITE WIDE
   ============================================================ */


.dashboard-card,
.control-hub-card,
.stat-card,
.summary-card,
.inventory-card,
.profile-card,
.region-panel,
.section-card,
.manage-card{


position:relative !important;

overflow:hidden !important;


border-radius:15px !important;


border:

2px solid
rgba(210,218,220,.55) !important;


background:

linear-gradient(
145deg,
#4d5559,
#090b0c
) !important;


box-shadow:

inset 0 2px 0
rgba(255,255,255,.25),

inset 0 -22px 35px
rgba(0,0,0,.8),

0 18px 40px
rgba(0,0,0,.75) !important;


}



.dashboard-card:after,
.control-hub-card:after,
.stat-card:after,
.summary-card:after,
.inventory-card:after,
.profile-card:after,
.region-panel:after,
.section-card:after,
.manage-card:after{


content:"";

position:absolute;

inset:7px;

border-radius:10px;

pointer-events:none;


border:

1px solid
rgba(255,193,58,.45);

}



.card-title,
.card-heading,
.panel-title,
.summary-title{


color:#ffd167 !important;

font-weight:900 !important;

text-shadow:

0 2px 5px
rgba(0,0,0,.8);

}



.card-icon,
.card-icon.svg-badge{


background:

linear-gradient(
145deg,
#70777a,
#111415
) !important;


border:

1px solid
rgba(255,255,255,.3) !important;


box-shadow:

inset 0 2px 4px
rgba(255,255,255,.25),

0 8px 18px
rgba(0,0,0,.6);


}



.dashboard-card:hover,
.control-hub-card:hover,
.stat-card:hover,
.summary-card:hover{


transform:
translateY(-3px);


}


</style>

</head>
<body>

<div class="ag-shell">

<?php
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "Manage Accounts";
$siteHeaderRole = "GRID OWNER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "ADMIN HOME";
$siteHeaderLink = "/Other/admin-home.php";
require_once __DIR__ . "/includes/site-header.php";
?>

<?php if ($dbError !== ''): ?>
    <div class="ag-error"><?=ag_h($dbError)?></div>
<?php endif; ?>

<?php if ($errorCode !== '' && isset($errorMessages[$errorCode])): ?>
    <div class="ag-error"><?=ag_h($errorMessages[$errorCode])?></div>
<?php endif; ?>

<?php if (isset($_GET['saved'])): ?>
    <div class="ag-success">Account email and UserLevel saved.</div>
<?php endif; ?>

<?php if (isset($_GET['disabled'])): ?>
    <div class="ag-success">
        Account disabled. Its current website session is no longer accepted.
    </div>
<?php endif; ?>

<?php if (isset($_GET['password'])): ?>
    <div class="ag-success">Password reset completed.</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="ag-success">
        Account permanently deleted:
        <strong><?=ag_h((string)($_GET['deleted_name'] ?? ''))?></strong>
    </div>
<?php endif; ?>

<div class="accounts-summary">

    <div class="summary-box">
        REGISTERED ACCOUNTS
        <strong><?=ag_h($totalUsers)?></strong>
    </div>

    <div class="summary-box">
        ADMIN ACCOUNTS
        <strong><?=ag_h($totalAdmins)?></strong>
    </div>

    <div class="summary-box">
        DISABLED ACCOUNTS
        <strong><?=ag_h($totalDisabled)?></strong>
    </div>

    <div class="summary-box">
        RESULTS SHOWN
        <strong><?=ag_h(count($accounts))?></strong>
    </div>

</div>

<div class="accounts-toolbar">

<form class="searchbar" method="get" action="/Other/admin-accounts.php">

    <input
        type="text"
        name="q"
        value="<?=ag_h($q)?>"
        placeholder="Search avatar name, email or UUID">

    <button class="ag-button primary" type="submit">
        SEARCH
    </button>

    <?php if ($q !== ''): ?>
        <a class="ag-button" href="/Other/admin-accounts.php">
            CLEAR
        </a>
    <?php endif; ?>

</form>

<div class="create-account-action">
    <a class="ag-button primary" href="<?=ag_h(ag_route('admin_account_create'))?>">
        CREATE ACCOUNT
    </a>
</div>

</div>

<?php if ($selected): ?>

<?php
    [$selectedStatus, $selectedClass] =
        accountStatus((int)$selected['UserLevel']);

    $selectedIsSelf =
        hash_equals(
            $adminPrincipalId,
            (string)$selected['PrincipalID']
        );
?>

<section class="detail">

    <div class="ag-eyebrow">Account Details</div>

    <h2 style="margin:7px 0 15px">
        <?=ag_h(trim($selected['FirstName'] . ' ' . $selected['LastName']))?>
    </h2>

    <div class="detail-grid">

        <div class="detail-item">
            <span class="label">STATUS</span>
            <span class="status <?=ag_h($selectedClass)?>">
                <?=ag_h($selectedStatus)?>
            </span>
        </div>

        <div class="detail-item">
            <span class="label">USER LEVEL</span>
            <?=ag_h($selected['UserLevel'])?>
        </div>

        <div class="detail-item">
            <span class="label">EMAIL</span>
            <?=ag_h($selected['Email'] !== '' ? $selected['Email'] : '&mdash;')?>
        </div>

        <div class="detail-item">
            <span class="label">CREATED</span>
            <?=ag_h(createdDisplay($selected['Created']))?>
        </div>

        <div class="detail-item" style="grid-column:1/-1">
            <span class="label">PRINCIPAL ID</span>
            <?=ag_h($selected['PrincipalID'])?>
        </div>

    </div>

    <form method="post" action="/Other/admin-accounts.php?id=<?=rawurlencode((string)$selected['PrincipalID'])?>">

        <input type="hidden" name="csrf_token" value="<?=ag_h($csrfToken)?>">
        <input type="hidden" name="action" value="save_account">
        <input type="hidden" name="principal_id" value="<?=ag_h($selected['PrincipalID'])?>">
        <input type="hidden" name="q" value="<?=ag_h($q)?>">

        <div class="form-grid">

            <div class="form-field">
                <label for="edit-email">EMAIL</label>
                <input
                    id="edit-email"
                    type="email"
                    name="email"
                    maxlength="254"
                    value="<?=ag_h($selected['Email'])?>">
            </div>

            <div class="form-field">
                <label for="edit-level">USER LEVEL (-1 TO 255)</label>
                <input
                    id="edit-level"
                    type="number"
                    name="user_level"
                    min="-1"
                    max="255"
                    step="1"
                    value="<?=ag_h($selected['UserLevel'])?>"
                    required>
            </div>

        </div>

        <div class="actions-line">

            <button class="ag-button primary" type="submit">
                SAVE ACCOUNT
            </button>

        </div>

    </form>

    <div class="account-tools">

        <div class="tool-box">

            <h3>RESET PASSWORD</h3>

            <form method="post" action="/Other/admin-accounts.php?id=<?=rawurlencode((string)$selected['PrincipalID'])?>">

                <input type="hidden" name="csrf_token" value="<?=ag_h($csrfToken)?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="principal_id" value="<?=ag_h($selected['PrincipalID'])?>">
                <input type="hidden" name="q" value="<?=ag_h($q)?>">

                <div class="form-field">
                    <label for="new-password">NEW PASSWORD</label>
                    <input
                        id="new-password"
                        type="password"
                        name="new_password"
                        minlength="8"
                        maxlength="128"
                        required>
                </div>

                <div class="form-field" style="margin-top:10px">
                    <label for="confirm-password">CONFIRM PASSWORD</label>
                    <input
                        id="confirm-password"
                        type="password"
                        name="confirm_password"
                        minlength="8"
                        maxlength="128"
                        required>
                </div>

                <div class="actions-line">
                    <button
                        class="ag-button"
                        type="submit"
                        onclick="return confirm('Reset this avatar password?');"
                    >
                        RESET PASSWORD
                    </button>
                </div>

            </form>

        </div>

        <div class="tool-box">

            <h3>DISABLE ACCOUNT</h3>

            <?php if ($selectedIsSelf): ?>

                <p class="ag-muted">
                    You cannot disable the account you are currently signed in with.
                </p>

            <?php elseif ((int)$selected['UserLevel'] < 0): ?>

                <p class="ag-muted">
                    This account is already disabled. Set UserLevel to 0, 50,
                    or another required level above to re-enable it.
                </p>

            <?php else: ?>

                <p class="ag-muted">
                    Disabling sets UserLevel to -1. The website will
                    reject the account immediately.
                </p>

                <form method="post" action="/Other/admin-accounts.php?id=<?=rawurlencode((string)$selected['PrincipalID'])?>">

                    <input type="hidden" name="csrf_token" value="<?=ag_h($csrfToken)?>">
                    <input type="hidden" name="action" value="disable_account">
                    <input type="hidden" name="principal_id" value="<?=ag_h($selected['PrincipalID'])?>">
                    <input type="hidden" name="q" value="<?=ag_h($q)?>">

                    <button
                        class="danger-button"
                        type="submit"
                        onclick="return confirm('Disable this account?');"
                    >
                        DISABLE ACCOUNT
                    </button>

                </form>

            <?php endif; ?>

        </div>

    </div>

    <div class="tool-box" style="margin-top:16px;border-color:rgba(255,96,96,.28)">

        <h3>DELETE ACCOUNT</h3>

        <p class="ag-muted">
            Permanent deletion is handled on a separate safety page.
            The account must be disabled first and cannot own a region,
            estate or other protected grid resource.
        </p>

        <div class="actions-line">
            <a
                class="danger-button"
                href="<?=ag_h(ag_route('admin_account_delete'))?>?id=<?=rawurlencode((string)$selected['PrincipalID'])?>"
            >
                DELETE ACCOUNT
            </a>
        </div>

    </div>

</section>

<?php endif; ?>

<div class="table-wrap">

<table class="accounts-table">

<thead>
<tr>
    <th>AVATAR</th>
    <th>EMAIL</th>
    <th>LEVEL</th>
    <th>STATUS</th>
    <th>CREATED</th>
    <th></th>
</tr>
</thead>

<tbody>

<?php if (!$accounts): ?>

<tr>
    <td colspan="6">No accounts matched this search.</td>
</tr>

<?php else: ?>

<?php foreach ($accounts as $account): ?>

<?php
    $accountLevel = (int)$account['UserLevel'];
    [$statusLabel, $statusClass] = accountStatus($accountLevel);
    $accountName = trim($account['FirstName'] . ' ' . $account['LastName']);
?>

<tr>

    <td>
        <div class="account-name"><?=ag_h($accountName)?></div>
        <div class="ag-muted" style="font-size:11px">
            <?=ag_h($account['PrincipalID'])?>
        </div>
    </td>

    <td>
        <?= $account['Email'] !== '' ? ag_h($account['Email']) : '&mdash;' ?>
    </td>

    <td>
        <?=ag_h($accountLevel)?>
    </td>

    <td>
        <span class="status <?=ag_h($statusClass)?>">
            <?=ag_h($statusLabel)?>
        </span>
    </td>

    <td>
        <?=ag_h(createdDisplay($account['Created']))?>
    </td>

    <td>
        <a
            class="small-button"
            href="/Other/admin-accounts.php?id=<?=rawurlencode((string)$account['PrincipalID'])?><?= $q !== '' ? '&amp;q=' . rawurlencode($q) : '' ?>"
        >
            OPEN
        </a>
    </td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<script defer src="/Other/core/ag-confirm-modal.js?v=1"></script>

<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>
</body>
</html>







