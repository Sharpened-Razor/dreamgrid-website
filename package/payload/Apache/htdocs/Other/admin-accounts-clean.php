<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/admin-accounts-clean-controller.php';

if (!function_exists('ma_h')) {

    function ma_h($value): string
    {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

}


$search = $q;
$totalAccounts = $totalUsers;
$adminAccounts = $totalAdmins;
$disabledAccounts = $totalDisabled;

function ma_account_status(
    int $level
): array {

    if ($level < 0) {

        return [
            'DISABLED',
            'disabled'
        ];
    }


    if ($level >= 200) {

        return [
            'ADMIN',
            'admin'
        ];
    }


    if ($level > 0) {

        return [
            'MEMBER',
            'member'
        ];
    }


    return [
        'USER',
        'user'
    ];
}


function ma_created_date(
    $created
): string {

    $timestamp =
        (int)$created;


    if ($timestamp <= 0) {

        return '—';
    }


    return date(
        'd M Y h:i A',
        $timestamp
    );
}


$resultsShown =
    count(
        $accounts
    );

?><!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>
    Manage Accounts
</title>

<link
    rel="stylesheet"
    href="/Other/assets/css/admin-accounts-clean-v1.css?v=20260920-charcoal-gold-clean">

<link
    rel="stylesheet"
    href="/Other/australia-modal.css?v=20261003-charcoal-v2">
</head>


<body>

<main class="ma-shell">


    <!-- =====================================================
         CLEAN PAGE HEADER
         ===================================================== -->

    <section class="ma-hero">

        <div class="ma-hero-icon">

            <img
                src="/Other/assets/icons/sentinel/users.png"
                alt="">

        </div>


        <div class="ma-hero-copy">

            <div class="ma-kicker">
                ADMIN / ACCOUNT MANAGEMENT
            </div>

            <h1>
                MANAGE ACCOUNTS
            </h1>

            <p>
                Search, review and manage registered grid accounts.
            </p>

        </div>


        <div class="ma-hero-status">

            <span>
                ACCOUNT SYSTEM
            </span>

            <strong>
                READY
            </strong>

        </div>

    </section>



    <!-- =====================================================
         REAL COUNTS
         ===================================================== -->

    <section class="ma-summary">


        <article>

            <img
                src="/Other/assets/icons/sentinel/users.png"
                alt="">

            <div>

                <span>
                    REGISTERED ACCOUNTS
                </span>

                <strong>
                    <?=$totalAccounts?>
                </strong>

            </div>

        </article>



        <article>

            <img
                src="/Other/assets/icons/sentinel/security.png"
                alt="">

            <div>

                <span>
                    ADMIN ACCOUNTS
                </span>

                <strong>
                    <?=$adminAccounts?>
                </strong>

            </div>

        </article>



        <article>

            <img
                src="/Other/assets/icons/sentinel/alert.png"
                alt="">

            <div>

                <span>
                    DISABLED ACCOUNTS
                </span>

                <strong>
                    <?=$disabledAccounts?>
                </strong>

            </div>

        </article>



        <article>

            <img
                src="/Other/assets/icons/sentinel/view.png"
                alt="">

            <div>

                <span>
                    RESULTS SHOWN
                </span>

                <strong>
                    <?=$resultsShown?>
                </strong>

            </div>

        </article>


    </section>



    <!-- =====================================================
         REAL SEARCH
         ===================================================== -->

    <form
        class="ma-toolbar"
        method="get"
        action="/Other/admin-accounts-clean.php"
    >

        <div class="ma-search">

            <img
                src="/Other/assets/icons/sentinel/search.png"
                alt="">

            <input
                type="search"
                name="q"
                value="<?=ma_h($search)?>"
                placeholder="Search avatar name, email or UUID">

        </div>


        <button
            type="submit"
            class="ma-search-button">

            <img
                src="/Other/assets/icons/sentinel/search.png"
                alt="">

            SEARCH

        </button>


        <!--
             CREATE ACCOUNT functionality will be transplanted
             from the frozen working page after this clean page
             is approved.
        -->

        <a
            class="ma-create-button"
            href="/Other/admin-account-create.php">

            <img
                src="/Other/assets/icons/sentinel/account.png"
                alt="">

            CREATE ACCOUNT

        </a>

    </form>



    <!-- =====================================================
         REAL ACCOUNT DATA
         ===================================================== -->

    
<?php if (
    isset($selected) &&
    is_array($selected) &&
    !empty($selected['PrincipalID'])
): ?>

<?php
    $maSelectedName =
        trim(
            (string)($selected['FirstName'] ?? '') .
            ' ' .
            (string)($selected['LastName'] ?? '')
        );

    $maSelectedLevel =
        (int)($selected['UserLevel'] ?? 0);

    [
        $maSelectedStatusLabel,
        $maSelectedStatusClass
    ] =
        ma_account_status(
            $maSelectedLevel
        );
?>

<section class="ma-selected-panel">

    <div class="ma-selected-heading">

        <div class="ma-selected-title">

            <div class="ma-selected-icon">

                <img
                    src="/Other/assets/icons/sentinel/account.png"
                    alt="">

            </div>

            <div>

                <div class="ma-kicker">
                    SELECTED ACCOUNT
                </div>

                <h2>
                    <?=ma_h($maSelectedName)?>
                </h2>

                <div class="ma-selected-id">
                    <?=ma_h($selected['PrincipalID'])?>
                </div>

            </div>

        </div>


        <a
            class="ma-close-account"
            href="/Other/admin-accounts-clean.php<?= $search !== '' ? '?q=' . rawurlencode($search) : '' ?>">

            CLOSE ACCOUNT

        </a>

    </div>


    <div class="ma-selected-details">

        <div>
            <span>EMAIL</span>
            <strong>
                <?=
                    trim((string)($selected['Email'] ?? '')) !== ''
                        ? ma_h($selected['Email'])
                        : '—'
                ?>
            </strong>
        </div>

        <div>
            <span>LEVEL</span>
            <strong>
                <?=$maSelectedLevel?>
            </strong>
        </div>

        <div>
            <span>STATUS</span>

            <strong>
                <span class="ma-status <?=ma_h($maSelectedStatusClass)?>">
                    <?=ma_h($maSelectedStatusLabel)?>
                </span>
            </strong>
        </div>

        <div>
            <span>CREATED</span>
            <strong>
                <?=ma_h(
                    ma_created_date(
                        $selected['Created'] ?? 0
                    )
                )?>
            </strong>
        </div>

    </div>


    <div class="ma-selected-actions">

    <form method="post" action="/Other/admin-accounts-clean.php?id=<?=rawurlencode((string)$selected['PrincipalID'])?>">

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

            <form method="post" action="/Other/admin-accounts-clean.php?id=<?=rawurlencode((string)$selected['PrincipalID'])?>">

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
                        
                    >
                        RESET PASSWORD
                    </button>
                </div>

            </form>

                <form method="post" action="/Other/admin-accounts-clean.php?id=<?=rawurlencode((string)$selected['PrincipalID'])?>">

                    <input type="hidden" name="csrf_token" value="<?=ag_h($csrfToken)?>">
                    <input type="hidden" name="action" value="disable_account">
                    <input type="hidden" name="principal_id" value="<?=ag_h($selected['PrincipalID'])?>">
                    <input type="hidden" name="q" value="<?=ag_h($q)?>">

                    <button
                        class="danger-button"
                        type="submit"
                        
                    >
                        DISABLE ACCOUNT
                    </button>

                </form>

    </div>

</section>

<?php endif; ?>

<section class="ma-account-panel">

        <header class="ma-table-head">

            <div>
                AVATAR
            </div>

            <div>
                EMAIL
            </div>

            <div>
                LEVEL
            </div>

            <div>
                STATUS
            </div>

            <div>
                CREATED
            </div>

            <div></div>

        </header>


        <?php if ($dbError !== ''): ?>

            <div class="ma-empty">

                <img
                    src="/Other/assets/icons/sentinel/alert.png"
                    alt="">

                <strong>
                    <?=ma_h($dbError)?>
                </strong>

            </div>


        <?php elseif (!$accounts): ?>

            <div class="ma-empty">

                <img
                    src="/Other/assets/icons/sentinel/search.png"
                    alt="">

                <strong>
                    NO ACCOUNTS FOUND
                </strong>

            </div>


        <?php else: ?>


            <?php foreach ($accounts as $account): ?>

                <?php

                $principalId =
                    trim(
                        (string)(
                            $account['PrincipalID'] ??
                            ''
                        )
                    );


                $firstName =
                    trim(
                        (string)(
                            $account['FirstName'] ??
                            ''
                        )
                    );


                $lastName =
                    trim(
                        (string)(
                            $account['LastName'] ??
                            ''
                        )
                    );


                $avatarName =
                    trim(
                        $firstName .
                        ' ' .
                        $lastName
                    );


                $email =
                    trim(
                        (string)(
                            $account['Email'] ??
                            ''
                        )
                    );


                $level =
                    (int)(
                        $account['UserLevel'] ??
                        0
                    );


                [
                    $statusLabel,
                    $statusClass
                ] =
                    ma_account_status(
                        $level
                    );

                ?>


                <article class="ma-demo-row">


                    <div class="ma-avatar-cell">

                        <strong>
                            <?=ma_h($avatarName)?>
                        </strong>

                        <span>
                            <?=ma_h($principalId)?>
                        </span>

                    </div>


                    <div class="ma-email-cell">

                        <?=
                            $email !== ''
                                ? ma_h($email)
                                : '—'
                        ?>

                    </div>


                    <div>
                        <?=$level?>
                    </div>


                    <div>

                        <span
                            class="ma-status <?=ma_h($statusClass)?>"
                        >
                            <?=ma_h($statusLabel)?>
                        </span>

                    </div>


                    <div>

                        <?=
                            ma_h(
                                ma_created_date(
                                    $account['Created'] ??
                                    0
                                )
                            )
                        ?>

                    </div>


                    <div>

                        <!--
                             OPEN functionality comes from the
                             frozen working Manage Accounts source.
                             We reconnect it after visual approval.
                        -->

                        <a
                            class="ma-open-button"
                            href="/Other/admin-accounts-clean.php?id=<?=rawurlencode($principalId)?><?= $search !== '' ? '&amp;q=' . rawurlencode($search) : '' ?>">

                            <img
                                src="/Other/assets/icons/sentinel/view.png"
                                alt="">

                            OPEN

                        </a>

                    </div>


                </article>


            <?php endforeach; ?>


        <?php endif; ?>


    </section>


</main>


<script src="/Other/australia-modal.js?v=20261003-charcoal-v2"></script>

<script id="manage-accounts-charcoal-actions-v1">

(function(){

"use strict";


function findActionForm(
    actionName
){

    const forms =
        Array.from(
            document.querySelectorAll(
                "form"
            )
        );


    return (
        forms.find(
            function(form){

                const action =
                    form.querySelector(
                        'input[name="action"]'
                    );


                return (
                    action &&
                    action.value ===
                        actionName
                );
            }
        ) ||
        null
    );
}


function wireConfirmation(
    actionName,
    options
){

    const form =
        findActionForm(
            actionName
        );


    if(!form){
        return;
    }


    form.addEventListener(
        "submit",
        async function(event){

            if(
                form.dataset
                    .australiaConfirmed ===
                "1"
            ){

                delete form.dataset
                    .australiaConfirmed;

                return;
            }


            event.preventDefault();


            if(
                typeof window.auConfirm !==
                "function"
            ){

                console.error(
                    "Website confirmation modal system is unavailable."
                );

                return;
            }


            const accepted =
                await window.auConfirm(
                    options
                );


            if(!accepted){
                return;
            }


            form.dataset
                .australiaConfirmed =
                "1";


            if(
                typeof form.requestSubmit ===
                "function"
            ){

                form.requestSubmit();

                return;
            }


            form.submit();
        }
    );
}


wireConfirmation(
    "reset_password",
    {
        title:
            "RESET PASSWORD",

        message:
            "Reset this avatar password?",

        highlight:
            "The new password entered above will immediately replace the avatar's current OpenSim password.",

        confirmText:
            "RESET PASSWORD",

        cancelText:
            "CANCEL"
    }
);


wireConfirmation(
    "disable_account",
    {
        title:
            "DISABLE ACCOUNT",

        message:
            "Disable this account?",

        warning:
            "The account will no longer be able to log in until its UserLevel is restored.",

        confirmText:
            "DISABLE ACCOUNT",

        cancelText:
            "CANCEL",

        danger:
            true
    }
);


})();

</script>
</body>

</html>
