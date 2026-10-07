<?php

/*
 * ============================================================
 * Grid - PUBLIC CREATE ACCOUNT
 *
 * Public account creation using the existing private OpenSim
 * Robust UserAccountService.
 *
 * Accounts are NOT manually inserted into the database.
 * ============================================================
 */

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/robust.php';

ag_no_cache();

if (session_status() !== PHP_SESSION_ACTIVE) {

    session_start();
}


/*
 * ------------------------------------------------------------
 * CSRF
 * ------------------------------------------------------------
 */

if (empty($_SESSION['ag_public_create_csrf'])) {

    $_SESSION['ag_public_create_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    (string)
    $_SESSION['ag_public_create_csrf'];


$error = '';

$success = false;

$firstName = '';

$lastName = '';

$email = '';


/*
 * ------------------------------------------------------------
 * VALIDATION
 * ------------------------------------------------------------
 */

function ag_public_valid_avatar_part(
    string $value
): bool {

    if (
        $value === '' ||
        strlen($value) > 64
    ) {

        return false;
    }


    if (
        preg_match(
            '/[\x00-\x1F\x7F]/',
            $value
        )
    ) {

        return false;
    }


    /*
     * OpenSim account name restrictions.
     *
     * No:
     *   spaces
     *   @
     *   periods
     *   colons
     */

    return !preg_match(
        '/[\s@\.:]/',
        $value
    );
}


function ag_public_account_exists(
    mysqli $con,
    string $firstName,
    string $lastName
): bool {

    $sql =
        'SELECT PrincipalID ' .
        'FROM UserAccounts ' .
        'WHERE FirstName = ? ' .
        'AND LastName = ? ' .
        'LIMIT 1';


    $stmt =
        @mysqli_prepare(
            $con,
            $sql
        );


    /*
     * Fail closed:
     * if duplicate checking cannot be performed,
     * don't attempt to create an account.
     */

    if (!$stmt) {

        return true;
    }


    mysqli_stmt_bind_param(
        $stmt,
        'ss',
        $firstName,
        $lastName
    );


    mysqli_stmt_execute(
        $stmt
    );


    mysqli_stmt_store_result(
        $stmt
    );


    $exists =
        mysqli_stmt_num_rows(
            $stmt
        ) > 0;


    mysqli_stmt_close(
        $stmt
    );


    return $exists;
}


function ag_public_verify_account(
    mysqli $con,
    string $principalId
): array {

    $result = [
        'account' => false,
        'auth' => false,
        'row' => null,
    ];


    /*
     * Verify UserAccounts.
     */

    $sql =
        'SELECT ' .
        'PrincipalID, ' .
        'FirstName, ' .
        'LastName, ' .
        'Email, ' .
        'Created, ' .
        'UserLevel ' .
        'FROM UserAccounts ' .
        'WHERE PrincipalID = ? ' .
        'LIMIT 1';


    $stmt =
        @mysqli_prepare(
            $con,
            $sql
        );


    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            's',
            $principalId
        );


        mysqli_stmt_execute(
            $stmt
        );


        $queryResult =
            mysqli_stmt_get_result(
                $stmt
            );


        if ($queryResult) {

            $row =
                mysqli_fetch_assoc(
                    $queryResult
                );


            if ($row) {

                $result['account'] =
                    true;

                $result['row'] =
                    $row;
            }


            mysqli_free_result(
                $queryResult
            );
        }


        mysqli_stmt_close(
            $stmt
        );
    }


    /*
     * Verify authentication record.
     */

    $authSql =
        'SELECT UUID ' .
        'FROM auth ' .
        'WHERE UUID = ? ' .
        'LIMIT 1';


    $authStmt =
        @mysqli_prepare(
            $con,
            $authSql
        );


    if ($authStmt) {

        mysqli_stmt_bind_param(
            $authStmt,
            's',
            $principalId
        );


        mysqli_stmt_execute(
            $authStmt
        );


        mysqli_stmt_store_result(
            $authStmt
        );


        $result['auth'] =
            mysqli_stmt_num_rows(
                $authStmt
            ) === 1;


        mysqli_stmt_close(
            $authStmt
        );
    }


    return $result;
}


/*
 * ------------------------------------------------------------
 * CREATE ACCOUNT
 * ------------------------------------------------------------
 */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    ===
    'POST'
) {

    $postedCsrf =
        (string)(
            $_POST['csrf_token'] ??
            ''
        );


    if (
        $postedCsrf === '' ||
        !hash_equals(
            $csrfToken,
            $postedCsrf
        )
    ) {

        $error =
            'Your security token expired. ' .
            'Reload the page and try again.';
    }
    else {

        $firstName =
            trim(
                (string)(
                    $_POST['first_name'] ??
                    ''
                )
            );


        $lastName =
            trim(
                (string)(
                    $_POST['last_name'] ??
                    ''
                )
            );


        $email =
            trim(
                (string)(
                    $_POST['email'] ??
                    ''
                )
            );


        $password =
            (string)(
                $_POST['password'] ??
                ''
            );


        $confirmPassword =
            (string)(
                $_POST['confirm_password'] ??
                ''
            );


        $acceptedTerms =
            !empty(
                $_POST['accept_terms']
            );


        if (
            !ag_public_valid_avatar_part(
                $firstName
            )
        ) {

            $error =
                'Enter a valid first name. ' .
                'Do not use spaces, @, periods or colons.';
        }
        elseif (
            !ag_public_valid_avatar_part(
                $lastName
            )
        ) {

            $error =
                'Enter a valid last name. ' .
                'Do not use spaces, @, periods or colons.';
        }
        elseif (
            $email !== '' &&
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error =
                'Enter a valid email address.';
        }
        elseif (
            strlen($email) > 254
        ) {

            $error =
                'The email address is too long.';
        }
        elseif (
            strlen($password) < 8 ||
            strlen($password) > 128
        ) {

            $error =
                'Password must contain between ' .
                '8 and 128 characters.';
        }
        elseif (
            $password !==
            $confirmPassword
        ) {

            $error =
                'The password confirmation does not match.';
        }
        elseif (!$acceptedTerms) {

            $error =
                'You must accept the Terms of Service.';
        }
        elseif (
            !ag_robust_create_user_enabled()
        ) {

            $error =
                'New account registration is currently unavailable.';
        }
        else {

            $con =
                ag_db_connect();


            if (!$con) {

                $error =
                    'Grid account service is unavailable.';
            }
            elseif (
                ag_public_account_exists(
                    $con,
                    $firstName,
                    $lastName
                )
            ) {

                $error =
                    'An avatar with that first and last name already exists.';
            }
            else {

                $principalId =
                    ag_uuid_v4();


                $robustResult =
                    ag_robust_create_user(
                        $principalId,
                        $firstName,
                        $lastName,
                        $password,
                        $email
                    );


                /*
                 * Do not retain the plain password after
                 * the Robust request.
                 */

                $password = '';

                $confirmPassword = '';


                if (
                    !$robustResult['ok']
                ) {

                    $error =
                        'The Grid account service ' .
                        'could not complete the request.';
                }
                elseif (
                    preg_match(
                        '/<result>\s*Failure\s*<\/result>/i',
                        (string)
                        $robustResult['body']
                    )
                ) {

                    $error =
                        'The account creation request was refused.';
                }
                else {

                    $verified =
                        ag_public_verify_account(
                            $con,
                            $principalId
                        );


                    if (
                        !$verified['account']
                    ) {

                        $error =
                            'The account request returned, but ' .
                            'the new account could not be verified.';
                    }
                    elseif (
                        !$verified['auth']
                    ) {

                        $error =
                            'The avatar was created, but its ' .
                            'login record could not be verified. ' .
                            'Do not create the same avatar again.';
                    }
                    else {

                        $success =
                            true;


                        /*
                         * Rotate CSRF token after successful use.
                         */

                        $_SESSION['ag_public_create_csrf'] =
                            bin2hex(
                                random_bytes(32)
                            );


                        $csrfToken =
                            (string)
                            $_SESSION['ag_public_create_csrf'];


                        $firstName = '';

                        $lastName = '';

                        $email = '';
                    }
                }
            }


            if (
                isset($con) &&
                $con instanceof mysqli
            ) {

                @mysqli_close(
                    $con
                );
            }
        }


        /*
         * Clear password variables regardless of result.
         */

        $password = '';

        $confirmPassword = '';
    }
}


/*
 * ============================================================
 * AUSTRALIA CREATE ACCOUNT CONTROLLER BRIDGE V1
 * ============================================================
 *
 * The normal direct page continues into the old HTML below.
 * The front Login shell defines this constant so only the
 * existing backend processing runs.
 */

if (
    defined('AUSTRALIA_CREATE_ACCOUNT_CONTROLLER_ONLY') &&
    AUSTRALIA_CREATE_ACCOUNT_CONTROLLER_ONLY
) {
    return;
}

?><!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>
    Create Account
</title>


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

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    color:#e9eef1;

    background:
        linear-gradient(
            rgba(3,8,10,.43),
            rgba(3,8,10,.62)
        ),
        url(
            "/Other/custom/Branding/AUSTRALIA-BACKGROUND.png"
        )
        center center /
        cover fixed
        no-repeat;

}


.account-shell{

    width:
        min(
            980px,
            calc(100% - 32px)
        );

    margin:
        42px auto;

}


.account-top{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

    padding:
        20px 24px;

    border:
        1px solid
        rgba(218,160,39,.38);

    border-radius:
        14px;

    background:
        rgba(7,16,21,.92);

    box-shadow:
        0 18px 50px
        rgba(0,0,0,.46);

}


.eyebrow{

    color:#e9ad34;

    font-size:10px;

    font-weight:900;

    letter-spacing:.16em;

}


.account-top h1{

    margin:
        5px 0 0;

    color:#fff;

    font-size:
        clamp(
            25px,
            4vw,
            38px
        );

}


.home-link{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    min-height:39px;

    padding:
        0 16px;

    border:
        1px solid
        rgba(224,169,51,.45);

    border-radius:8px;

    color:#ffd66d;

    background:
        rgba(223,164,42,.08);

    text-decoration:none;

    font-size:10px;

    font-weight:900;

}


.account-card{

    max-width:700px;

    margin:
        20px auto 0;

    padding:
        26px;

    border:
        1px solid
        rgba(221,165,48,.48);

    border-radius:
        14px;

    background:
        linear-gradient(
            180deg,
            rgba(19,30,35,.96),
            rgba(5,11,14,.97)
        );

    box-shadow:
        0 18px 55px
        rgba(0,0,0,.48);

}


.account-card h2{

    margin:
        0 0 8px;

    color:#f3b83f;

    font-size:24px;

}


.intro{

    margin:
        0 0 22px;

    color:#aebbc1;

    font-size:13px;

    line-height:1.6;

}


.form-grid{

    display:grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap:15px;

}


.field{

    display:flex;

    flex-direction:column;

    gap:7px;

}


.field.full{

    grid-column:
        1 / -1;

}


.field label{

    color:#dce5e9;

    font-size:10px;

    font-weight:900;

    letter-spacing:.07em;

}


.field input{

    width:100%;

    height:43px;

    padding:
        0 12px;

    border:
        1px solid
        rgba(166,187,196,.27);

    border-radius:7px;

    outline:none;

    color:#fff;

    background:
        rgba(0,0,0,.38);

    font-size:13px;

}


.field input:focus{

    border-color:
        #dc9f2c;

    box-shadow:
        0 0 0 2px
        rgba(220,159,44,.12);

}


.help{

    margin-top:3px;

    color:#78888f;

    font-size:10px;

    line-height:1.45;

}


.terms{

    display:flex;

    align-items:flex-start;

    gap:9px;

    margin-top:18px;

    color:#aebbc1;

    font-size:11px;

    line-height:1.45;

}


.terms input{

    margin-top:2px;

}


.terms a{

    color:#f4b83d;
}


.actions{

    display:flex;

    align-items:center;

    gap:10px;

    margin-top:22px;

}


.create-button{

    min-width:170px;

    height:42px;

    border:
        1px solid
        #e3aa34;

    border-radius:7px;

    cursor:pointer;

    color:#171006;

    background:
        linear-gradient(
            #f7ca59,
            #d59017
        );

    font-size:10px;

    font-weight:900;

    letter-spacing:.06em;

}


.cancel-button{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    height:42px;

    padding:
        0 16px;

    border:
        1px solid
        rgba(255,255,255,.13);

    border-radius:7px;

    color:#d3dbdf;

    background:
        rgba(255,255,255,.04);

    text-decoration:none;

    font-size:10px;

    font-weight:900;

}


.message{

    margin:
        0 0 20px;

    padding:
        13px 15px;

    border-radius:8px;

    font-size:12px;

    line-height:1.5;

}


.message.error{

    border:
        1px solid
        rgba(235,88,88,.42);

    color:#ffd0d0;

    background:
        rgba(177,46,46,.13);

}


.message.success{

    border:
        1px solid
        rgba(71,197,112,.40);

    color:#c9f6d6;

    background:
        rgba(42,147,78,.13);

}


.success-actions{

    display:flex;

    gap:10px;

    flex-wrap:wrap;

    margin-top:16px;

}


.success-actions a{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    min-height:38px;

    padding:
        0 14px;

    border-radius:7px;

    text-decoration:none;

    font-size:10px;

    font-weight:900;

}


.login-now{

    color:#171006;

    background:
        linear-gradient(
            #f6c653,
            #d38d17
        );

}


.back-home{

    border:
        1px solid
        rgba(255,255,255,.13);

    color:#dce4e8;

    background:
        rgba(255,255,255,.04);

}


.footer{

    padding:
        17px 0 4px;

    color:#7f8d93;

    text-align:center;

    font-size:10px;

}


@media(max-width:650px){

    .account-shell{

        margin-top:16px;

    }


    .account-top{

        align-items:flex-start;

        flex-direction:column;

    }


    .form-grid{

        grid-template-columns:1fr;

    }


    .field.full{

        grid-column:auto;

    }

}

</style>

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

</head>


<body>

<div class="account-shell">


    <header class="account-top">

        <div>

            <div class="eyebrow">
                GRID SERVICES
            </div>

            <h1>
                Create Account
            </h1>

        </div>


        <a
            class="home-link"
            href="/Other/index.php"
        >
            BACK TO HOME
        </a>

    </header>


    <main class="account-card">

        <?php if ($success): ?>

            <div class="message success">

                <strong>
                    Your account has been created.
                </strong>

                <br><br>

                You can now sign in using your new
                avatar first name, last name and password.

                <div class="success-actions">

                    <a
                        class="login-now"
                        href="/Other/index.php"
                    >
                        GO TO LOGIN
                    </a>

                    <a
                        class="back-home"
                        href="/Other/create-account.php"
                    >
                        CREATE ANOTHER ACCOUNT
                    </a>

                </div>

            </div>

        <?php else: ?>


            <h2>
                Create Your Account
            </h2>


            <p class="intro">

                Create your OpenSimulator avatar.
                Your first and last name become your
                OpenSimulator avatar name.

            </p>


            <?php if ($error !== ''): ?>

                <div class="message error">
                    <?=ag_h($error)?>
                </div>

            <?php endif; ?>


            <form
                method="post"
                action="/Other/create-account.php"
                autocomplete="off"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?=ag_h($csrfToken)?>"
                >


                <div class="form-grid">


                    <div class="field">

                        <label for="first_name">
                            FIRST NAME
                        </label>

                        <input
                            id="first_name"
                            name="first_name"
                            type="text"
                            maxlength="64"
                            value="<?=ag_h($firstName)?>"
                            required
                            autocomplete="off"
                        >

                        <div class="help">
                            No spaces, @, periods or colons.
                        </div>

                    </div>


                    <div class="field">

                        <label for="last_name">
                            LAST NAME
                        </label>

                        <input
                            id="last_name"
                            name="last_name"
                            type="text"
                            maxlength="64"
                            value="<?=ag_h($lastName)?>"
                            required
                            autocomplete="off"
                        >

                        <div class="help">
                            No spaces, @, periods or colons.
                        </div>

                    </div>


                    <div class="field full">

                        <label for="email">
                            EMAIL
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            maxlength="254"
                            value="<?=ag_h($email)?>"
                            required
                            autocomplete="email"
                        >

                    </div>


                    <div class="field">

                        <label for="password">
                            PASSWORD
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            minlength="8"
                            maxlength="128"
                            required
                            autocomplete="new-password"
                        >

                    </div>


                    <div class="field">

                        <label for="confirm_password">
                            CONFIRM PASSWORD
                        </label>

                        <input
                            id="confirm_password"
                            name="confirm_password"
                            type="password"
                            minlength="8"
                            maxlength="128"
                            required
                            autocomplete="new-password"
                        >

                    </div>


                </div>


                <label class="terms">

                    <input
                        type="checkbox"
                        name="accept_terms"
                        value="1"
                        required
                    >

                    <span>

                        I agree to the

                        <a
                            href="/Other/terms.php"
                            target="_blank"
                            rel="noopener"
                        >
                            Terms of Service
                        </a>.

                    </span>

                </label>


                <div class="actions">

                    <button
                        class="create-button"
                        type="submit"
                    >
                        CREATE ACCOUNT
                    </button>


                    <a
                        class="cancel-button"
                        href="/Other/index.php"
                    >
                        CANCEL
                    </a>

                </div>


            </form>

        <?php endif; ?>

    </main>


    <div class="footer">

        GRID SERVICES
        &nbsp; â€¢ &nbsp;
        Powered by OpenSimÂ®

    </div>


</div>

<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>



