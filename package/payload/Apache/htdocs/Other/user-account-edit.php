<?php

require_once __DIR__ . '/core/bootstrap.php';


/*
 * ============================================================
 * Grid - EDIT ACCOUNT V1
 * ============================================================
 */


$session =
    ag_current_session();


if (!$session) {

    header(
        'Location: /Other/login.php'
    );

    exit;

}


$agRoleLevel = function_exists('ag_user_level')
    ? (int) ag_user_level($session)
    : (int)($session['level'] ?? 0);

$agRoleIsAdmin = function_exists('ag_is_admin')
    ? (bool) ag_is_admin($session)
    : ($agRoleLevel >= 200);

$agRoleLabel =
    ($agRoleLevel >= 250)
        ? "GRID OWNER"
        : ($agRoleIsAdmin ? "ADMIN" : "USER");

$principalId =
    trim(
        (string)(
            $session['principalId'] ??
            ''
        )
    );


$sessionAvatar =
    trim(
        (string)(
            $session['avatar'] ??
            ''
        )
    );


if ($principalId === '') {

    http_response_code(403);

    echo 'Signed account ID is unavailable.';

    exit;

}


/*
 * ------------------------------------------------------------
 * CSRF SESSION
 * ------------------------------------------------------------
 */


if (
    session_status() !==
    PHP_SESSION_ACTIVE
) {

    session_start();

}


if (
    empty(
        $_SESSION['australia_account_csrf']
    )
) {

    $_SESSION['australia_account_csrf'] =
        bin2hex(
            random_bytes(32)
        );

}


$csrfToken =
    (string)
    $_SESSION['australia_account_csrf'];


/*
 * ------------------------------------------------------------
 * PAGE VALUES
 * ------------------------------------------------------------
 */


$avatar =
    $sessionAvatar;


$currentEmail =
    '';


$formEmail =
    '';


$errors =
    [];


$dbError =
    '';


/*
 * ------------------------------------------------------------
 * CONNECT TO ROBUST DATABASE
 * ------------------------------------------------------------
 */


require __DIR__ . '/../../MetroMap/includes/config.php';


$con =
    @mysqli_connect(
        $CONF_db_server,
        $CONF_db_user,
        $CONF_db_pass,
        $CONF_db_database,
        (int)$CONF_db_port
    );


if (!$con) {

    http_response_code(500);

    $dbError =
        'Grid account services are temporarily unavailable.';

}
else {

    @mysqli_set_charset(
        $con,
        'utf8mb4'
    );

}


/*
 * ------------------------------------------------------------
 * LOAD SIGNED-IN USER ACCOUNT
 * ------------------------------------------------------------
 */


if (
    $con &&
    $dbError === ''
) {

    $accountSql =
        'SELECT FirstName, LastName, Email ' .
        'FROM UserAccounts ' .
        'WHERE PrincipalID = ? ' .
        'LIMIT 1';


    $accountStmt =
        @mysqli_prepare(
            $con,
            $accountSql
        );


    if (!$accountStmt) {

        $dbError =
            'Grid could not read your account information.';

    }
    else {

        mysqli_stmt_bind_param(
            $accountStmt,
            's',
            $principalId
        );


        mysqli_stmt_execute(
            $accountStmt
        );


        mysqli_stmt_bind_result(
            $accountStmt,
            $dbFirstName,
            $dbLastName,
            $dbEmail
        );


        if (
            mysqli_stmt_fetch(
                $accountStmt
            )
        ) {

            $dbAvatar =
                trim(
                    (string)$dbFirstName .
                    ' ' .
                    (string)$dbLastName
                );


            if ($dbAvatar !== '') {

                $avatar =
                    $dbAvatar;

            }


            $currentEmail =
                trim(
                    (string)$dbEmail
                );


            $formEmail =
                $currentEmail;

        }
        else {

            $dbError =
                'Your Grid account record could not be found.';

        }


        mysqli_stmt_close(
            $accountStmt
        );

    }

}


/*
 * ------------------------------------------------------------
 * HANDLE SAVE
 * ------------------------------------------------------------
 */


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $dbError === ''
) {

    $postedCsrf =
        (string)(
            $_POST['csrf_token'] ??
            ''
        );


    $formEmail =
        trim(
            (string)(
                $_POST['email'] ??
                ''
            )
        );


    $currentPassword =
        (string)(
            $_POST['current_password'] ??
            ''
        );


    $newPassword =
        (string)(
            $_POST['new_password'] ??
            ''
        );


    $confirmPassword =
        (string)(
            $_POST['confirm_password'] ??
            ''
        );


    /*
     * --------------------------------------------------------
     * CSRF
     * --------------------------------------------------------
     */


    if (
        $postedCsrf === '' ||
        !hash_equals(
            $csrfToken,
            $postedCsrf
        )
    ) {

        $errors[] =
            'Your security token expired. Please reload the page and try again.';

    }


    /*
     * --------------------------------------------------------
     * EMAIL VALIDATION
     * --------------------------------------------------------
     */


    if (
        $formEmail !== '' &&
        !filter_var(
            $formEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors[] =
            'Please enter a valid email address.';

    }


    if (
        strlen(
            $formEmail
        ) > 254
    ) {

        $errors[] =
            'The email address is too long.';

    }


    /*
     * --------------------------------------------------------
     * CURRENT PASSWORD IS ALWAYS REQUIRED
     * --------------------------------------------------------
     */


    if (
        $currentPassword === ''
    ) {

        $errors[] =
            'Enter your current password to save account changes.';

    }


    /*
     * --------------------------------------------------------
     * NEW PASSWORD VALIDATION
     * --------------------------------------------------------
     */


    if (
        $newPassword !== ''
    ) {

        if (
            strlen(
                $newPassword
            ) < 8
        ) {

            $errors[] =
                'Your new password must contain at least 8 characters.';

        }


        if (
            strlen(
                $newPassword
            ) > 128
        ) {

            $errors[] =
                'Your new password is too long.';

        }


        if (
            $newPassword !==
            $confirmPassword
        ) {

            $errors[] =
                'The new passwords do not match.';

        }

    }


    /*
     * --------------------------------------------------------
     * SOMETHING MUST ACTUALLY CHANGE
     * --------------------------------------------------------
     */


    $emailChanged =
        (
            $formEmail !==
            $currentEmail
        );


    $passwordChanged =
        (
            $newPassword !==
            ''
        );


    if (
        !$emailChanged &&
        !$passwordChanged
    ) {

        $errors[] =
            'There are no account changes to save.';

    }


    /*
     * --------------------------------------------------------
     * SIMPLE PASSWORD ATTEMPT THROTTLE
     * --------------------------------------------------------
     */


    $now =
        time();


    $lockUntil =
        (int)(
            $_SESSION['australia_account_lock_until'] ??
            0
        );


    if (
        $lockUntil >
        $now
    ) {

        $errors[] =
            'Too many incorrect password attempts. Please wait before trying again.';

    }


    /*
     * --------------------------------------------------------
     * VERIFY CURRENT PASSWORD AGAINST OPENSIM AUTH TABLE
     * --------------------------------------------------------
     */


    if (
        count(
            $errors
        ) === 0
    ) {

        $authSql =
            'SELECT passwordHash, passwordSalt ' .
            'FROM auth ' .
            'WHERE UUID = ? ' .
            'LIMIT 1';


        $authStmt =
            @mysqli_prepare(
                $con,
                $authSql
            );


        if (!$authStmt) {

            $errors[] =
                'Grid could not verify your current password.';

        }
        else {

            mysqli_stmt_bind_param(
                $authStmt,
                's',
                $principalId
            );


            mysqli_stmt_execute(
                $authStmt
            );


            mysqli_stmt_bind_result(
                $authStmt,
                $storedPasswordHash,
                $storedPasswordSalt
            );


            if (
                mysqli_stmt_fetch(
                    $authStmt
                )
            ) {

                /*
                 * OpenSimulator SetPassword:
                 *
                 * MD5(
                 *     MD5(plain password)
                 *     + ":"
                 *     + password salt
                 * )
                 */

                $currentPasswordHash =
                    md5(
                        md5(
                            $currentPassword
                        ) .
                        ':' .
                        (string)$storedPasswordSalt
                    );


                $passwordCorrect =
                    hash_equals(
                        strtolower(
                            (string)$storedPasswordHash
                        ),
                        strtolower(
                            $currentPasswordHash
                        )
                    );


                if (!$passwordCorrect) {

                    $failedAttempts =
                        (int)(
                            $_SESSION['australia_account_failed_passwords'] ??
                            0
                        );


                    $failedAttempts++;


                    $_SESSION['australia_account_failed_passwords'] =
                        $failedAttempts;


                    if (
                        $failedAttempts >= 5
                    ) {

                        $_SESSION['australia_account_lock_until'] =
                            time() + 60;


                        $_SESSION['australia_account_failed_passwords'] =
                            0;

                    }


                    $errors[] =
                        'Your current password is incorrect.';

                }
                else {

                    $_SESSION['australia_account_failed_passwords'] =
                        0;


                    $_SESSION['australia_account_lock_until'] =
                        0;

                }

            }
            else {

                $errors[] =
                    'Your Grid authentication record could not be found.';

            }


            mysqli_stmt_close(
                $authStmt
            );

        }

    }


    /*
     * --------------------------------------------------------
     * SAVE CHANGES IN A DATABASE TRANSACTION
     * --------------------------------------------------------
     */


    if (
        count(
            $errors
        ) === 0
    ) {

        mysqli_begin_transaction(
            $con
        );


        $transactionOk =
            true;


        try {


            /*
             * EMAIL
             */


            if ($emailChanged) {

                $emailSql =
                    'UPDATE UserAccounts ' .
                    'SET Email = ? ' .
                    'WHERE PrincipalID = ? ' .
                    'LIMIT 1';


                $emailStmt =
                    mysqli_prepare(
                        $con,
                        $emailSql
                    );


                if (!$emailStmt) {

                    throw new Exception(
                        'Email update could not be prepared.'
                    );

                }


                mysqli_stmt_bind_param(
                    $emailStmt,
                    'ss',
                    $formEmail,
                    $principalId
                );


                if (
                    !mysqli_stmt_execute(
                        $emailStmt
                    )
                ) {

                    mysqli_stmt_close(
                        $emailStmt
                    );


                    throw new Exception(
                        'Email update failed.'
                    );

                }


                mysqli_stmt_close(
                    $emailStmt
                );

            }


            /*
             * PASSWORD
             */


            if ($passwordChanged) {

                /*
                 * OpenSimulator-compatible salt/hash.
                 */


                $newSalt =
                    md5(
                        bin2hex(
                            random_bytes(32)
                        ) .
                        microtime(
                            true
                        )
                    );


                $newHash =
                    md5(
                        md5(
                            $newPassword
                        ) .
                        ':' .
                        $newSalt
                    );


                $passwordSql =
                    'UPDATE auth ' .
                    'SET passwordHash = ?, passwordSalt = ? ' .
                    'WHERE UUID = ? ' .
                    'LIMIT 1';


                $passwordStmt =
                    mysqli_prepare(
                        $con,
                        $passwordSql
                    );


                if (!$passwordStmt) {

                    throw new Exception(
                        'Password update could not be prepared.'
                    );

                }


                mysqli_stmt_bind_param(
                    $passwordStmt,
                    'sss',
                    $newHash,
                    $newSalt,
                    $principalId
                );


                if (
                    !mysqli_stmt_execute(
                        $passwordStmt
                    )
                ) {

                    mysqli_stmt_close(
                        $passwordStmt
                    );


                    throw new Exception(
                        'Password update failed.'
                    );

                }


                mysqli_stmt_close(
                    $passwordStmt
                );

            }


            mysqli_commit(
                $con
            );


            /*
             * Rotate CSRF token after a successful save.
             */


            $_SESSION['australia_account_csrf'] =
                bin2hex(
                    random_bytes(32)
                );


            header(
                'Location: /Other/user-account.php?updated=1'
            );


            exit;


        }
        catch (Throwable $saveError) {

            $transactionOk =
                false;


            mysqli_rollback(
                $con
            );


            $errors[] =
                'Grid could not save your account changes. Nothing was changed.';

        }

    }

}


if ($con) {

    mysqli_close(
        $con
    );

}


header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>
Grid - Edit Account
</title>


<link
    rel="stylesheet"
    href="/Other/australia-3d-theme.css?v=31">


<style>

*{
    box-sizing:border-box;
}


html,
body{

    min-height:100%;

}


body{

    margin:0;

    background:

        linear-gradient(
            rgba(0,0,0,.46),
            rgba(0,0,0,.67)
        ),

        url(
            "/Other/assets/images/control-center-teal-bg.png"
        )

        center center /
        cover
        fixed
        no-repeat;

    color:#edf2f4;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}


.edit-shell{

    width:
        min(
            1000px,
            calc(100% - 40px)
        );

    margin:
        42px auto 75px;

    padding:
        28px;

}


.edit-header{

    padding:
        28px 31px;

    margin-bottom:
        20px;

}


.edit-kicker{

    color:#efb83d;

    font-size:10px;

    font-weight:900;

    letter-spacing:.20em;

}


.edit-header h1{

    margin:
        8px 0 7px;

    font-size:
        38px;

}


.edit-header p{

    margin:0;

    color:#bcc6ca;

    font-size:14px;

}


.edit-card{

    padding:
        27px;

}


.avatar-readonly{

    margin-bottom:
        23px;

    padding:
        17px 19px;

    border:
        1px solid
        rgba(233,174,48,.33);

    border-radius:
        10px;

    background:

        linear-gradient(
            145deg,
            rgba(36,44,48,.97),
            rgba(7,11,13,.98)
        );

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.12),

        inset 0 -3px 6px
        rgba(0,0,0,.72);

}


.readonly-label{

    margin-bottom:7px;

    color:#f0b63d;

    font-size:10px;

    font-weight:900;

    letter-spacing:.08em;

}


.readonly-value{

    color:#fff;

    font-size:17px;

    font-weight:900;

}


.readonly-note{

    margin-top:7px;

    color:#9ba8ae;

    font-size:11px;

}


.form-section{

    margin-top:
        20px;

    padding-top:
        20px;

    border-top:
        1px solid
        rgba(255,255,255,.09);

}


.form-section:first-of-type{

    margin-top:0;

    padding-top:0;

    border-top:0;

}


.form-section h2{

    margin:
        0 0 6px;

    font-size:17px;

}


.form-section-description{

    margin:
        0 0 16px;

    color:#aeb9be;

    font-size:12px;

    line-height:1.55;

}


.form-group{

    margin-bottom:
        17px;

}


.form-group label{

    display:block;

    margin-bottom:
        7px;

    color:#f0b63c;

    font-size:10px;

    font-weight:900;

    letter-spacing:.065em;

}


.form-group input{

    width:100%;

    min-height:
        47px;

    padding:
        0 14px;

    font-size:
        14px;

}


.password-grid{

    display:grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap:
        14px;

}


.edit-message{

    margin-bottom:
        20px;

    padding:
        14px 16px;

    border:
        1px solid
        rgba(226,76,84,.43);

    border-radius:
        9px;

    background:
        rgba(115,25,30,.30);

    color:#ffd1d4;

}


.edit-message ul{

    margin:
        0;

    padding-left:
        20px;

}


.edit-message li{

    margin:
        4px 0;

}


.security-note{

    margin-top:
        18px;

    padding:
        14px 16px;

    border-left:
        3px solid
        #e4a82f;

    border-radius:
        7px;

    background:
        rgba(229,169,47,.065);

    color:#bfc8cc;

    font-size:
        11px;

    line-height:
        1.6;

}


.edit-actions{

    display:flex;

    flex-wrap:wrap;

    justify-content:center;

    gap:
        11px;

    margin-top:
        25px;

    padding-top:
        23px;

    border-top:
        1px solid
        rgba(255,255,255,.09);

}


.edit-action{

    min-width:
        160px;

    min-height:
        47px;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    padding:
        10px 17px;

    text-decoration:none;

    cursor:pointer;

}


@media(
    max-width:700px
){

    .password-grid{

        grid-template-columns:
            1fr;

        gap:0;

    }


    .edit-shell{

        width:
            calc(100% - 24px);

        margin:
            20px auto 50px;

        padding:
            16px;

    }


    .edit-header h1{

        font-size:
            30px;

    }


    .edit-actions{

        flex-direction:column;

    }


    .edit-action{

        width:100%;

    }

}

</style>


<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">
<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=20260830-phase3c">

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


<style id="ag-custom-role-box-v1">

.ag-account-role-box{
    display:inline-flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    min-width:138px;
    margin-top:14px;
    padding:8px 14px;
    box-sizing:border-box;
    border:1px solid rgba(68,153,220,.48);
    border-radius:8px;
    background:rgba(12,62,98,.30);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.06);
    line-height:1.2;
}

.ag-account-role-box strong{
    display:block;
    color:#77c8ff;
    font-size:11px;
    font-weight:900;
    letter-spacing:.05em;
}

.ag-account-role-box span{
    display:block;
    margin-top:3px;
    color:#f2f4f5;
    font-size:10px;
    font-weight:800;
    white-space:nowrap;
}

</style>


<style id="ag-role-display-standard-v4">

/* ============================================================
   STANDARD ROLE DISPLAY V4

   250+        GRID OWNER
   other admin ADMIN
   normal user USER
   ============================================================ */

.php-level,
.ag-account-role-box{
    display:inline-flex !important;
    flex-direction:column !important;
    align-items:center !important;
    justify-content:center !important;
    min-width:145px !important;
    min-height:54px !important;
    padding:7px 14px !important;
    box-sizing:border-box !important;
    border:1px solid rgba(55,150,215,.70) !important;
    border-radius:8px !important;
    background:rgba(5,28,43,.88) !important;
    box-shadow:none !important;
    text-align:center !important;
    line-height:1.15 !important;
    color:#ffffff !important;
}

.php-level strong,
.ag-account-role-box strong{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    width:auto !important;
    height:auto !important;
    margin:0 0 4px 0 !important;
    padding:0 !important;
    color:#75c9ff !important;
    background:none !important;
    font-size:12px !important;
    font-weight:900 !important;
    line-height:1.1 !important;
    letter-spacing:.03em !important;
    text-indent:0 !important;
    clip:auto !important;
    overflow:visible !important;
}

.php-level span,
.ag-account-role-box span{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    margin:0 !important;
    padding:0 !important;
    color:#ffffff !important;
    background:none !important;
    font-size:11px !important;
    font-weight:800 !important;
    line-height:1.1 !important;
    white-space:nowrap !important;
}

</style>

</head>


<body>


<main class="edit-shell panel">


<section class="edit-header header">


<div class="edit-kicker">
    <?=htmlspecialchars($agRoleLabel, ENT_QUOTES, 'UTF-8')?> AREA
</div>


<h1>
    EDIT ACCOUNT
</h1>
<div class="ag-account-role-box">
    <strong><?=htmlspecialchars($agRoleLabel, ENT_QUOTES, 'UTF-8')?></strong>
    <span>USER LEVEL <?=htmlspecialchars((string)$agRoleLevel, ENT_QUOTES, 'UTF-8')?></span>
</div>



<p>
    Update your Grid email address or password.
</p>


</section>



<section class="edit-card card">


<?php if ($dbError !== ''): ?>

<div class="edit-message">

    <?=htmlspecialchars(
        $dbError,
        ENT_QUOTES,
        'UTF-8'
    )?>

</div>

<?php endif; ?>


<?php if (count($errors) > 0): ?>

<div class="edit-message">

<ul>

<?php foreach ($errors as $error): ?>

<li>
    <?=htmlspecialchars(
        $error,
        ENT_QUOTES,
        'UTF-8'
    )?>
</li>

<?php endforeach; ?>

</ul>

</div>

<?php endif; ?>



<div class="avatar-readonly">

    <div class="readonly-label">
        AVATAR NAME
    </div>

    <div class="readonly-value">

        <?=htmlspecialchars(
            $avatar,
            ENT_QUOTES,
            'UTF-8'
        )?>

    </div>

    <div class="readonly-note">
        Avatar names cannot be changed from this page.
    </div>

</div>



<form
    method="post"
    action="">


<input
    type="hidden"
    name="csrf_token"
    value="<?=htmlspecialchars(
        $csrfToken,
        ENT_QUOTES,
        'UTF-8'
    )?>">



<section class="form-section">


<h2>
    EMAIL ADDRESS
</h2>


<p class="form-section-description">
    Change the email address attached to your Grid account.
</p>


<div class="form-group">

<label for="email">
    EMAIL
</label>

<input
    id="email"
    name="email"
    type="email"
    maxlength="254"
    autocomplete="email"
    value="<?=htmlspecialchars(
        $formEmail,
        ENT_QUOTES,
        'UTF-8'
    )?>">

</div>


</section>



<section class="form-section">


<h2>
    CHANGE PASSWORD
</h2>


<p class="form-section-description">
    Leave both new-password boxes empty if you only want to change your email.
</p>


<div class="password-grid">


<div class="form-group">

<label for="new_password">
    NEW PASSWORD
</label>

<input
    id="new_password"
    name="new_password"
    type="password"
    minlength="8"
    maxlength="128"
    autocomplete="new-password">

</div>



<div class="form-group">

<label for="confirm_password">
    CONFIRM NEW PASSWORD
</label>

<input
    id="confirm_password"
    name="confirm_password"
    type="password"
    minlength="8"
    maxlength="128"
    autocomplete="new-password">

</div>


</div>


</section>



<section class="form-section">


<h2>
    CONFIRM YOUR CHANGES
</h2>


<p class="form-section-description">
    For security, enter your current Grid password before saving.
</p>


<div class="form-group">

<label for="current_password">
    CURRENT PASSWORD
</label>

<input
    id="current_password"
    name="current_password"
    type="password"
    maxlength="128"
    autocomplete="current-password"
    required>

</div>


</section>



<div class="security-note">

    Only your signed-in avatar can update this account.
    Your current password is checked before any email or
    password change is written to the Grid database.

</div>



<div class="edit-actions">


<button
    class="edit-action button-primary"
    type="submit">

    SAVE CHANGES

</button>


<a
    class="edit-action button ag-return-button"
    href="javascript:history.back()">

    BACK

</a>


<a
    class="edit-action button"
    href="/Other/user-help.php#edit-account">

    HELP CENTRE

</a>


</div>


</form>


</section>


</main>


<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>



