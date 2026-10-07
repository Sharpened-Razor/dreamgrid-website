<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/icons.php';

$session =
    ag_require_admin();

ag_no_cache();


$avatar =
    ag_avatar_name(
        $session
    );


$level =
    ag_user_level(
        $session
    );


$principalId =
    trim(
        (string)(
            $session['principalId'] ??
            ''
        )
    );


$email =
    '';

$created =
    0;

$accountFound =
    false;


if (
    $principalId !== ''
) {

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

                'SELECT Email, Created ' .
                'FROM UserAccounts ' .
                'WHERE PrincipalID = ? ' .
                'LIMIT 1'
            );


        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                's',
                $principalId
            );


            if (
                @mysqli_stmt_execute(
                    $stmt
                )
            ) {

                mysqli_stmt_bind_result(
                    $stmt,
                    $dbEmail,
                    $dbCreated
                );


                if (
                    mysqli_stmt_fetch(
                        $stmt
                    )
                ) {

                    $accountFound =
                        true;


                    $email =
                        trim(
                            (string)$dbEmail
                        );


                    $created =
                        (int)$dbCreated;
                }
            }


            mysqli_stmt_close(
                $stmt
            );
        }


        mysqli_close(
            $con
        );
    }
}


$emailDisplay =
    $email !== ''
        ? $email
        : 'Not supplied';


$memberSince =
    $created > 0
        ? date(
            'j F Y',
            $created
        )
        : 'Unavailable';


$accountStatus =
    $level >= 0
        ? 'ACTIVE'
        : 'DISABLED';

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Account</title>

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-panel-v1.css?v=2">

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
                    DASHBOARD
                </div>


                <div class="cp-intro-title">
                    Account Details
                </div>

            </div>


        </div>


        <div class="cp-intro-note">
            Logged-in account information
        </div>


    </section>



    <section class="cp-grid">


        <article class="cp-card">


            <div class="cp-card-icon">

                <?=ag_icon(
                    'avatar',
                    null,
                    'cp-card-svg'
                )?>

            </div>


            <div>

                <div class="cp-label">
                    Avatar Name
                </div>


                <div class="cp-value">

                    <?=htmlspecialchars(
                        $avatar,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>

                </div>

            </div>


        </article>



        <article class="cp-card">


            <div class="cp-card-icon">

                <?=ag_icon(
                    'account',
                    null,
                    'cp-card-svg'
                )?>

            </div>


            <div>

                <div class="cp-label">
                    Account Status
                </div>


                <div class="cp-value green">

                    <?=htmlspecialchars(
                        $accountStatus,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>

                </div>

            </div>


        </article>



        <article class="cp-card">


            <div class="cp-card-icon">

                <?=ag_icon(
                    'email',
                    null,
                    'cp-card-svg'
                )?>

            </div>


            <div>

                <div class="cp-label">
                    Email
                </div>


                <div class="cp-value">

                    <?=htmlspecialchars(
                        $emailDisplay,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>

                </div>

            </div>


        </article>



        <article class="cp-card">


            <div class="cp-card-icon">

                <?=ag_icon(
                    'statistics',
                    null,
                    'cp-card-svg'
                )?>

            </div>


            <div>

                <div class="cp-label">
                    Member Since
                </div>


                <div class="cp-value">

                    <?=htmlspecialchars(
                        $memberSince,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>

                </div>

            </div>


        </article>


    </section>



    <section class="cp-actions">


        <div class="cp-actions-text">

            Account information is read directly
            from your current account.

        </div>


        <!--
            THIS BUTTON IS NEW.

            NO OLD LINK METHOD.
            NO INLINE onclick.
            NO SPECIAL ACCOUNT SCRIPT.

            IT USES THE SAME SHARED
            CONTROL CENTER METHOD ONLY.
        -->

        <button
            type="button"
            class="cp-button"

            data-cc-open
            data-cc-title="Edit Account"
            data-cc-view="account-edit"
            data-cc-src="/Other/panel-account-edit.php?from=admin"
        >


            <span class="cp-button-icon">

                <?=ag_icon(
                    'account',
                    null,
                    'cp-button-svg'
                )?>

            </span>


            <span>
                Edit Account
            </span>


        </button>


    </section>


</main>


<script
    src="/Other/assets/js/control-center-panel-nav-v1.js?v=2">
</script>


</body>

</html>