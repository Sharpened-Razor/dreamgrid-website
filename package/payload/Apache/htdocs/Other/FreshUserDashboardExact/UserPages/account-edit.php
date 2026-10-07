<?php

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';
require_once dirname(__DIR__, 2) . '/core/database.php';
require_once dirname(__DIR__, 2) . '/core/icons.php';

$session =
    ag_current_session();

if (!$session) {

    header(
        'Location: /Other/login.php'
    );

    exit;
}
ag_no_cache();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (
    empty($_SESSION['cc_account_csrf']) ||
    !is_string($_SESSION['cc_account_csrf'])
) {
    $_SESSION['cc_account_csrf'] =
        bin2hex(random_bytes(32));
}

$csrf =
    $_SESSION['cc_account_csrf'];

$principalId =
    trim(
        (string)(
            $session['principalId'] ??
            ''
        )
    );

$avatar =
    ag_avatar_name($session);

$sid =
    trim(
        (string)(
            $_GET['sid'] ??
            ''
        )
    );

$email = '';

if ($principalId !== '') {

    $con =
        ag_db_connect();

    if ($con) {

        mysqli_set_charset(
            $con,
            'utf8mb4'
        );

        $stmt =
            @mysqli_prepare(
                $con,
                'SELECT Email
                 FROM UserAccounts
                 WHERE PrincipalID = ?
                 LIMIT 1'
            );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                's',
                $principalId
            );

            if (@mysqli_stmt_execute($stmt)) {

                mysqli_stmt_bind_result(
                    $stmt,
                    $dbEmail
                );

                if (mysqli_stmt_fetch($stmt)) {
                    $email =
                        trim((string)$dbEmail);
                }
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_close($con);
    }
}

$result =
    trim(
        (string)(
            $_GET['result'] ??
            ''
        )
    );

$message = '';
$messageClass = '';

switch ($result) {

    case 'email-ok':
        $message = 'Email address updated.';
        $messageClass = 'success';
        break;

    case 'password-ok':
        $message = 'Password updated.';
        $messageClass = 'success';
        break;

    case 'bad-email':
        $message = 'Enter a valid email address, or leave it blank.';
        $messageClass = 'error';
        break;

    case 'bad-current-password':
        $message = 'Current password is incorrect.';
        $messageClass = 'error';
        break;

    case 'password-mismatch':
        $message = 'The new passwords do not match.';
        $messageClass = 'error';
        break;

    case 'password-short':
        $message = 'New password must be at least 8 characters.';
        $messageClass = 'error';
        break;

    case 'db-error':
    case 'auth-missing':
        $message = 'The account update could not be completed.';
        $messageClass = 'error';
        break;

    case 'security-error':
        $message = 'Security validation failed. Reload and try again.';
        $messageClass = 'error';
        break;
}

function cae_h(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>
<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Edit Account</title>

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-panel-v1.css?v=4">

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-account-edit-v1.css?v=1">

</head>

<body>

<main class="cp-page">

<section class="cp-intro">

    <div class="cp-intro-left">

        <div class="cp-intro-icon">

            <?=ag_icon(
                'account',
                null,
                'cp-intro-svg'
            )?>

        </div>

        <div>

            <div class="cp-intro-kicker">
                ACCOUNT
            </div>

            <div class="cp-intro-title">
                Account Settings
            </div>

        </div>

    </div>

    <div class="cp-intro-note">
        <?=cae_h($avatar)?>
    </div>

</section>


<?php if ($message !== ''): ?>

<div class="cae-alert <?=cae_h($messageClass)?>">
    <?=cae_h($message)?>
</div>

<?php endif; ?>


<section class="cae-grid">

    <article class="cae-card">

        <div class="cae-card-title">
            Email Address
        </div>

        <div class="cae-card-note">
            Change the email address stored on your account.
        </div>

        <form
            method="post"
            action="/Other/FreshUserDashboardExact/UserPages/account-edit-action.php"
            autocomplete="off"
        >

            <input
                type="hidden"
                name="csrf"
                value="<?=cae_h($csrf)?>">

            <input
                type="hidden"
                name="operation"
                value="email">

            <input
                type="hidden"
                name="sid"
                value="<?=cae_h($sid)?>">

            <label for="cae-email">
                Email Address
            </label>

            <input
                id="cae-email"
                name="email"
                type="email"
                maxlength="254"
                value="<?=cae_h($email)?>"
                autocomplete="email">

            <div class="cae-button-row">

                <button
                    class="cp-button"
                    type="submit"
                >
                    Save Email
                </button>

            </div>

        </form>

    </article>


    <article class="cae-card">

        <div class="cae-card-title">
            Change Password
        </div>

        <div class="cae-card-note">
            Enter your current password, then choose a new password.
        </div>

        <form
            method="post"
            action="/Other/FreshUserDashboardExact/UserPages/account-edit-action.php"
            autocomplete="off"
        >

            <input
                type="hidden"
                name="csrf"
                value="<?=cae_h($csrf)?>">

            <input
                type="hidden"
                name="operation"
                value="password">

            <input
                type="hidden"
                name="sid"
                value="<?=cae_h($sid)?>">

            <label for="cae-current-password">
                Current Password
            </label>

            <input
                id="cae-current-password"
                name="current_password"
                type="password"
                maxlength="128"
                required
                autocomplete="current-password">

            <label for="cae-new-password">
                New Password
            </label>

            <input
                id="cae-new-password"
                name="new_password"
                type="password"
                minlength="8"
                maxlength="128"
                required
                autocomplete="new-password">

            <label for="cae-confirm-password">
                Confirm New Password
            </label>

            <input
                id="cae-confirm-password"
                name="confirm_password"
                type="password"
                minlength="8"
                maxlength="128"
                required
                autocomplete="new-password">

            <div class="cae-button-row">

                <button
                    class="cp-button"
                    type="submit"
                >
                    Change Password
                </button>

            </div>

        </form>

    </article>

</section>


<section class="cae-footer">

    <button
        type="button"
        class="cp-button"
        data-cc-open
        data-cc-title="Account"
        data-cc-view="account"
        data-cc-src="/Other/FreshUserDashboardExact/UserPages/account.php"
    >
        Return to Account
    </button>

</section>

</main>


<script
    src="/Other/assets/js/control-center-panel-nav-v1.js?v=4">
</script>

</body>
</html>