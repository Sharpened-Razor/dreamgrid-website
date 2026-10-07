<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/navigation.php';
require_once __DIR__ . '/core/robust.php';

ag_no_cache();

$session = ag_require_admin();
$avatar = ag_avatar_name($session);
$level = ag_user_level($session);
$nav = ag_admin_navigation();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['ag_create_account_csrf'])) {
    $_SESSION['ag_create_account_csrf'] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    (string)$_SESSION['ag_create_account_csrf'];

$error = '';
$success = null;

$firstName = '';
$lastName = '';
$email = '';

function validAvatarPart(string $value): bool
{
    if ($value === '' || strlen($value) > 64) {
        return false;
    }

    if (preg_match('/[\x00-\x1F\x7F]/', $value)) {
        return false;
    }

    /*
     * Match OpenSim console account-name restrictions:
     * no spaces, @, period or colon in FirstName/LastName.
     */
    return !preg_match('/[\s@\.:]/', $value);
}

function accountExistsByName(
    mysqli $con,
    string $firstName,
    string $lastName
): bool {
    $sql =
        'SELECT PrincipalID ' .
        'FROM UserAccounts ' .
        'WHERE FirstName = ? AND LastName = ? ' .
        'LIMIT 1';

    $stmt = @mysqli_prepare($con, $sql);

    if (!$stmt) {
        return true;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ss',
        $firstName,
        $lastName
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    $exists =
        mysqli_stmt_num_rows($stmt) > 0;

    mysqli_stmt_close($stmt);

    return $exists;
}

function verifyCreatedAccount(
    mysqli $con,
    string $principalId
): array {
    $result = [
        'account' => false,
        'auth' => false,
        'row' => null,
    ];

    $sql =
        'SELECT PrincipalID, FirstName, LastName, Email, Created, UserLevel ' .
        'FROM UserAccounts ' .
        'WHERE PrincipalID = ? ' .
        'LIMIT 1';

    $stmt = @mysqli_prepare($con, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            's',
            $principalId
        );

        mysqli_stmt_execute($stmt);

        $queryResult =
            mysqli_stmt_get_result($stmt);

        if ($queryResult) {
            $row = mysqli_fetch_assoc($queryResult);

            if ($row) {
                $result['account'] = true;
                $result['row'] = $row;
            }

            mysqli_free_result($queryResult);
        }

        mysqli_stmt_close($stmt);
    }

    $authSql =
        'SELECT UUID ' .
        'FROM auth ' .
        'WHERE UUID = ? ' .
        'LIMIT 1';

    $authStmt = @mysqli_prepare(
        $con,
        $authSql
    );

    if ($authStmt) {
        mysqli_stmt_bind_param(
            $authStmt,
            's',
            $principalId
        );

        mysqli_stmt_execute($authStmt);
        mysqli_stmt_store_result($authStmt);

        $result['auth'] =
            mysqli_stmt_num_rows($authStmt) === 1;

        mysqli_stmt_close($authStmt);
    }

    return $result;
}

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
) {
    $postedCsrf =
        (string)($_POST['csrf_token'] ?? '');

    if (
        $postedCsrf === '' ||
        !hash_equals($csrfToken, $postedCsrf)
    ) {
        $error =
            'Your security token expired. Reload the page and try again.';
    }
    else {
        $firstName =
            trim((string)($_POST['first_name'] ?? ''));

        $lastName =
            trim((string)($_POST['last_name'] ?? ''));

        $email =
            trim((string)($_POST['email'] ?? ''));

        $password =
            (string)($_POST['password'] ?? '');

        $confirmPassword =
            (string)($_POST['confirm_password'] ?? '');

        if (!validAvatarPart($firstName)) {
            $error =
                'Enter a valid first name with no spaces, @, period or colon.';
        }
        elseif (!validAvatarPart($lastName)) {
            $error =
                'Enter a valid last name with no spaces, @, period or colon.';
        }
        elseif (
            $email !== '' &&
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $error = 'Enter a valid email address or leave it blank.';
        }
        elseif (strlen($email) > 254) {
            $error = 'The email address is too long.';
        }
        elseif (
            strlen($password) < 8 ||
            strlen($password) > 128
        ) {
            $error =
                'Password must contain 8 to 128 characters.';
        }
        elseif ($password !== $confirmPassword) {
            $error =
                'The password confirmation does not match.';
        }
        elseif (!ag_robust_create_user_enabled()) {
            $error =
                'Robust AllowCreateUser is not currently enabled.';
        }
        else {
            $con = ag_db_connect();

            if (!$con) {
                $error =
                    'Grid account database is unavailable.';
            }
            elseif (
                accountExistsByName(
                    $con,
                    $firstName,
                    $lastName
                )
            ) {
                $error =
                    'An avatar with that first and last name already exists.';
            }
            else {
                $principalId = ag_uuid_v4();

                $robustResult =
                    ag_robust_create_user(
                        $principalId,
                        $firstName,
                        $lastName,
                        $password,
                        $email
                    );

                /*
                 * Do not retain the plain password after the Robust call.
                 */
                $password = '';
                $confirmPassword = '';

                if (!$robustResult['ok']) {
                    $error =
                        'The private Robust account service did not complete the request.';
                }
                elseif (
                    preg_match(
                        '/<result>\s*Failure\s*<\/result>/i',
                        (string)$robustResult['body']
                    )
                ) {
                    $error =
                        'Robust refused the account creation request.';
                }
                else {
                    $verified =
                        verifyCreatedAccount(
                            $con,
                            $principalId
                        );

                    if (!$verified['account']) {
                        $error =
                            'Robust returned, but the new UserAccounts record could not be verified.';
                    }
                    elseif (!$verified['auth']) {
                        $error =
                            'The account record exists, but its authentication record could not be verified. Do not recreate the same avatar; check the Robust console.';
                    }
                    else {
                        $row = $verified['row'];

                        $success = [
                            'principal_id' =>
                                (string)$row['PrincipalID'],
                            'name' =>
                                trim(
                                    (string)$row['FirstName'] .
                                    ' ' .
                                    (string)$row['LastName']
                                ),
                            'email' =>
                                (string)$row['Email'],
                            'user_level' =>
                                (int)$row['UserLevel'],
                        ];

                        $_SESSION['ag_create_account_csrf'] =
                            bin2hex(random_bytes(32));

                        $csrfToken =
                            (string)$_SESSION['ag_create_account_csrf'];

                        $firstName = '';
                        $lastName = '';
                        $email = '';
                    }
                }

                mysqli_close($con);
            }

            if (
                isset($con) &&
                $con instanceof mysqli
            ) {
                /*
                 * mysqli_close above is safe; avoid a second close.
                 */
            }
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Account</title>
<link rel="stylesheet" href="/Other/core/site-shell.css?v=1">

<style>
.create-card{
    max-width:780px;
    margin:0 auto;
    padding:24px;
    border:1px solid rgba(255,255,255,.13);
    border-radius:16px;
    background:rgba(14,20,24,.94);
}
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
}
.form-field{
    margin-bottom:14px;
}
.form-field.full{
    grid-column:1/-1;
}
.form-field label{
    display:block;
    margin-bottom:6px;
    color:#aebbc3;
    font-size:12px;
    font-weight:900;
    letter-spacing:.04em;
}
.form-field input{
    width:100%;
    min-height:44px;
    padding:0 12px;
    border:1px solid rgba(255,255,255,.16);
    border-radius:9px;
    color:#fff;
    background:#090e11;
    box-sizing:border-box;
}
.notice{
    margin-bottom:18px;
    padding:14px 16px;
    border:1px solid rgba(244,179,35,.30);
    border-radius:11px;
    color:#ffe2a0;
    background:rgba(244,179,35,.08);
}
.success-box{
    margin-bottom:20px;
    padding:18px;
    border:1px solid rgba(92,203,125,.35);
    border-radius:12px;
    background:rgba(47,130,76,.14);
}
.success-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
    margin-top:12px;
}
.success-item{
    padding:11px;
    border:1px solid rgba(255,255,255,.10);
    border-radius:9px;
    background:rgba(0,0,0,.18);
}
.success-item span{
    display:block;
    margin-bottom:4px;
    color:#99a9b2;
    font-size:11px;
    font-weight:900;
}
.actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:8px;
}
@media(max-width:700px){
    .form-grid,
    .success-grid{
        grid-template-columns:1fr;
    }
    .form-field.full{
        grid-column:auto;
    }
}
</style>

<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">
<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=<?= filemtime(__DIR__ . '/assets/css/ag-uniform-site-v12.css') ?>">

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


<style id="australia-user-level-badge-v1">

.php-level{

    min-width:128px;

    padding:
        8px 12px;

    text-align:center;

    border:
        1px solid
        rgba(67,151,220,.38);

    border-radius:
        8px;

    background:
        rgba(15,68,104,.22);

    font-size:10px;

    font-weight:800;

}


.php-level strong{

    display:block;

    margin-bottom:2px;

    color:#7bc6ff;

}


</style>

</head>
<body>

<div class="ag-shell">

<?php
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "Create Account";
$siteHeaderRole = "GRID OWNER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "ADMIN HOME";
$siteHeaderLink = "/Other/admin-home.php";
require_once __DIR__ . "/includes/site-header.php";
?>

<nav class="ag-nav">
<?php foreach ($nav as $item): ?>
    <a href="<?=ag_h($item['url'])?>"><?=ag_h($item['label'])?></a>
<?php endforeach; ?>
</nav>

<div class="create-card">

    <div class="notice">
        This creates the avatar through OpenSim's private Robust
        UserAccountService. New accounts begin at <strong>UserLevel 0</strong>.
        You can change their level afterward in Manage Accounts.
    </div>

    <?php if ($error !== ''): ?>
        <div class="ag-error"><?=ag_h($error)?></div>
    <?php endif; ?>

    <?php if ($success): ?>

        <div class="success-box">

            <strong>ACCOUNT CREATED SUCCESSFULLY</strong>

            <div class="success-grid">

                <div class="success-item">
                    <span>AVATAR</span>
                    <?=ag_h($success['name'])?>
                </div>

                <div class="success-item">
                    <span>USER LEVEL</span>
                    <?=ag_h($success['user_level'])?>
                </div>

                <div class="success-item">
                    <span>EMAIL</span>
                    <?= $success['email'] !== '' ? ag_h($success['email']) : '&mdash;' ?>
                </div>

                <div class="success-item">
                    <span>PRINCIPAL ID</span>
                    <?=ag_h($success['principal_id'])?>
                </div>

            </div>

            <div class="actions">

                <a
                    class="ag-button primary"
                    href="/Other/admin-accounts.php?id=<?=rawurlencode($success['principal_id'])?>"
                >
                    OPEN ACCOUNT
                </a>

                <a
                    class="ag-button"
                    href="<?=ag_h(ag_route('admin_accounts'))?>"
                >
                    MANAGE ACCOUNTS
                </a>

            </div>

        </div>

    <?php endif; ?>

    <form method="post" action="<?=ag_h(ag_route('admin_account_create'))?>">

        <input
            type="hidden"
            name="csrf_token"
            value="<?=ag_h($csrfToken)?>">

        <div class="form-grid">

            <div class="form-field">

                <label for="first-name">FIRST NAME</label>

                <input
                    id="first-name"
                    type="text"
                    name="first_name"
                    maxlength="64"
                    autocomplete="off"
                    value="<?=ag_h($firstName)?>"
                    required>

            </div>

            <div class="form-field">

                <label for="last-name">LAST NAME</label>

                <input
                    id="last-name"
                    type="text"
                    name="last_name"
                    maxlength="64"
                    autocomplete="off"
                    value="<?=ag_h($lastName)?>"
                    required>

            </div>

            <div class="form-field full">

                <label for="email">EMAIL - OPTIONAL</label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    maxlength="254"
                    autocomplete="off"
                    value="<?=ag_h($email)?>">

            </div>

            <div class="form-field">

                <label for="password">PASSWORD</label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    minlength="8"
                    maxlength="128"
                    autocomplete="new-password"
                    required>

            </div>

            <div class="form-field">

                <label for="confirm-password">CONFIRM PASSWORD</label>

                <input
                    id="confirm-password"
                    type="password"
                    name="confirm_password"
                    minlength="8"
                    maxlength="128"
                    autocomplete="new-password"
                    required>

            </div>

        </div>

        <div class="actions">

            <button
                class="ag-button primary"
                type="submit"
                onclick="return confirm('Create this new Grid avatar account?');"
            >
                CREATE ACCOUNT
            </button>

            <a
                class="ag-button"
                href="<?=ag_h(ag_route('admin_accounts'))?>"
            >
                CANCEL
            </a>

        </div>

    </form>

</div>

</div>

<script defer src="/Other/core/ag-confirm-modal.js?v=1"></script>

<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>
</body>
</html>





