<?php

require_once __DIR__ . '/admin-group-manage-clean-controller.php';

?>
<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Manage Group</title>

<link
    rel="stylesheet"
    href="/Other/site-design-display.php?slot=admin-control-center-background">

<style id="australia-group-manager-charcoal-gold-v2">
*{
    box-sizing:border-box;
}

html,
body{
    margin:0 !important;
    padding:0 !important;

    width:100% !important;
    min-height:100% !important;
}

body{
    min-height:100vh !important;

    font-family:
        Arial,
        Helvetica,
        sans-serif !important;

    color:#d7ddd8 !important;

    background:transparent !important;
}

.mgm-shell{
    width:100% !important;
    max-width:none !important;

    min-height:100vh !important;

    margin:0 !important;

    padding:
        14px !important;
}


/* ==========================================================
   HERO
   ========================================================== */

.mgm-hero{
    width:100% !important;

    display:grid !important;

    grid-template-columns:
        48px
        minmax(0,1fr)
        auto !important;

    align-items:center !important;

    gap:13px !important;

    margin:0 0 11px !important;

    padding:14px 16px !important;

    border:
        1px solid
        rgba(211,162,48,.38) !important;

    border-radius:8px !important;

    background:
        linear-gradient(
            145deg,
            rgba(18,24,21,.98),
            rgba(6,10,8,.98)
        ) !important;

    box-shadow:
        0 10px 28px
        rgba(0,0,0,.28) !important;
}

.mgm-hero-icon{
    width:48px !important;
    height:48px !important;

    display:flex !important;

    align-items:center !important;
    justify-content:center !important;

    overflow:hidden !important;

    border:
        1px solid
        rgba(213,164,48,.36) !important;

    border-radius:7px !important;

    background:#090d0b !important;
}

.mgm-hero-icon img{
    display:block !important;

    width:26px !important;
    height:26px !important;

    max-width:26px !important;
    max-height:26px !important;

    object-fit:contain !important;
}

.mgm-hero-copy{
    min-width:0 !important;
}

.mgm-kicker{
    margin:0 0 3px !important;

    color:#b88d34 !important;

    font-size:8px !important;
    font-weight:900 !important;

    letter-spacing:1.5px !important;
}

.mgm-hero h1{
    margin:0 !important;

    color:#efbd4e !important;

    font-size:22px !important;
    font-weight:900 !important;

    line-height:1.1 !important;
}

.mgm-hero p{
    margin:4px 0 0 !important;

    color:#89958e !important;

    font-size:10px !important;
}

.mgm-back{
    display:inline-flex !important;

    align-items:center !important;
    justify-content:center !important;

    min-height:36px !important;

    padding:7px 13px !important;

    border:
        1px solid
        rgba(218,168,49,.48) !important;

    border-radius:6px !important;

    color:#efbd4e !important;

    background:
        linear-gradient(
            180deg,
            #2b2518,
            #14120d
        ) !important;

    font-size:8px !important;
    font-weight:900 !important;

    letter-spacing:.6px !important;

    text-decoration:none !important;

    white-space:nowrap !important;
}

.mgm-back:hover{
    border-color:
        rgba(240,187,63,.75) !important;

    color:#ffd36a !important;
}


/* ==========================================================
   CONTENT
   ========================================================== */

.mgm-content{
    width:100% !important;

    display:grid !important;

    grid-template-columns:1fr !important;

    gap:11px !important;

    margin:0 !important;
}


/* ==========================================================
   CARDS
   ========================================================== */

.mgm-card{
    width:100% !important;
    max-width:none !important;

    margin:0 !important;

    overflow:hidden !important;

    border:
        1px solid
        rgba(204,157,45,.31) !important;

    border-radius:8px !important;

    background:
        linear-gradient(
            145deg,
            rgba(15,21,18,.98),
            rgba(6,10,8,.98)
        ) !important;

    box-shadow:
        0 8px 24px
        rgba(0,0,0,.20) !important;
}

.mgm-card-heading{
    width:100% !important;

    padding:10px 13px !important;

    border-bottom:
        1px solid
        rgba(189,146,43,.19) !important;

    background:
        linear-gradient(
            180deg,
            #171c19,
            #0b100e
        ) !important;
}

.mgm-card-heading span{
    display:block !important;

    margin:0 0 2px !important;

    color:#8d7c56 !important;

    font-size:7px !important;
    font-weight:900 !important;

    letter-spacing:1px !important;
}

.mgm-card-heading h1,
.mgm-card-heading h2,
.mgm-card-heading h3,
.mgm-card-heading h4{
    margin:0 !important;

    color:#e8e3d8 !important;

    font-size:14px !important;
    font-weight:900 !important;
}

.mgm-card-body{
    width:100% !important;

    padding:13px !important;

    overflow-x:auto !important;
}


/* ==========================================================
   FORMS
   ========================================================== */

.mgm-form{
    width:100% !important;

    margin:0 !important;

    display:grid !important;

    grid-template-columns:
        repeat(2,minmax(0,1fr)) !important;

    gap:10px 12px !important;
}

.mgm-form > *{
    min-width:0 !important;
}

.mgm-card label{
    display:block !important;

    color:#aa925b !important;

    font-size:8px !important;
    font-weight:900 !important;

    letter-spacing:.5px !important;
}

.mgm-card input[type="text"],
.mgm-card input[type="email"],
.mgm-card input[type="number"],
.mgm-card input[type="password"],
.mgm-card select,
.mgm-card textarea{
    width:100% !important;

    min-height:35px !important;

    padding:7px 9px !important;

    border:
        1px solid
        rgba(145,132,98,.29) !important;

    border-radius:5px !important;

    outline:none !important;

    color:#d8ddd8 !important;

    background:#060a08 !important;

    box-shadow:none !important;
}

.mgm-card textarea{
    min-height:95px !important;

    resize:vertical !important;
}

.mgm-card input:focus,
.mgm-card select:focus,
.mgm-card textarea:focus{
    border-color:
        rgba(219,168,48,.62) !important;
}

.mgm-card input[type="checkbox"]{
    accent-color:#c99a32 !important;
}


/* ==========================================================
   CHECKBOX / FIELD GROUPS
   ========================================================== */

.mgm-card fieldset{
    width:100% !important;

    margin:0 !important;

    padding:10px !important;

    border:
        1px solid
        rgba(157,137,88,.19) !important;

    border-radius:6px !important;

    background:#090e0c !important;
}


/* ==========================================================
   BUTTONS
   ========================================================== */

.mgm-card button,
.mgm-card input[type="submit"],
.mgm-card input[type="button"],
.mgm-card a{
    display:inline-flex !important;

    align-items:center !important;
    justify-content:center !important;

    min-height:32px !important;

    padding:6px 11px !important;

    border:
        1px solid
        rgba(209,160,46,.40) !important;

    border-radius:5px !important;

    color:#e7b64a !important;

    background:
        linear-gradient(
            180deg,
            #292318,
            #14120d
        ) !important;

    font-size:8px !important;
    font-weight:900 !important;

    text-decoration:none !important;

    cursor:pointer !important;
}

.mgm-card button:hover,
.mgm-card input[type="submit"]:hover,
.mgm-card input[type="button"]:hover,
.mgm-card a:hover{
    border-color:
        rgba(237,185,61,.68) !important;

    color:#ffd16a !important;
}


/* ==========================================================
   TABLES
   ========================================================== */

.mgm-table{
    width:100% !important;

    border-collapse:collapse !important;
}

.mgm-table th{
    padding:8px !important;

    border-bottom:
        1px solid
        rgba(194,149,47,.23) !important;

    color:#a28e60 !important;

    background:#0c110f !important;

    text-align:left !important;

    font-size:7px !important;
    font-weight:900 !important;

    letter-spacing:.7px !important;
}

.mgm-table td{
    padding:8px !important;

    border-bottom:
        1px solid
        rgba(255,255,255,.055) !important;

    color:#c9cfca !important;

    background:
        rgba(7,11,9,.70) !important;

    font-size:8px !important;

    vertical-align:middle !important;
}

.mgm-table tbody tr:last-child td{
    border-bottom:0 !important;
}

.mgm-table form{
    margin:0 !important;
}


/* ==========================================================
   MESSAGE
   ========================================================== */

.mgm-message{
    width:100% !important;

    margin:0 0 10px !important;

    padding:10px 12px !important;

    border:
        1px solid
        rgba(206,158,45,.28) !important;

    border-radius:6px !important;

    color:#d7dcd7 !important;

    background:#0a100d !important;

    font-size:9px !important;
}


/* ==========================================================
   RESPONSIVE
   ========================================================== */

@media(max-width:800px){

    .mgm-shell{
        padding:8px !important;
    }

    .mgm-hero{
        grid-template-columns:
            42px
            minmax(0,1fr) !important;
    }

    .mgm-back{
        grid-column:
            1 / -1 !important;

        width:100% !important;
    }

    .mgm-form{
        grid-template-columns:1fr !important;
    }

}


.mgm-save-status{
    grid-column:1 / -1 !important;

    width:100% !important;

    margin-top:4px !important;

    padding:8px 10px !important;

    border-radius:5px !important;

    font-size:8px !important;
    font-weight:900 !important;

    letter-spacing:.6px !important;
}

.mgm-save-status.saving{
    border:
        1px solid
        rgba(206,158,45,.35) !important;

    color:#dcb14d !important;

    background:#17140c !important;
}

.mgm-save-status.saved{
    border:
        1px solid
        rgba(75,170,96,.38) !important;

    color:#91dfa1 !important;

    background:#09150d !important;
}

.mgm-save-status.error{
    border:
        1px solid
        rgba(190,74,74,.42) !important;

    color:#ef9a9a !important;

    background:#180b0b !important;
}


/* ==========================================================
   AUSTRALIA DELETE GROUP BUTTON V2 START
   ========================================================== */

.mgm-hero{
    grid-template-columns:
        48px
        minmax(0,1fr)
        auto
        auto !important;
}


.mgm-delete-group{
    display:inline-flex !important;

    align-items:center !important;
    justify-content:center !important;

    min-height:36px !important;

    padding:
        7px
        13px !important;

    border:
        1px solid
        rgba(204,76,58,.62) !important;

    border-radius:6px !important;

    color:#f0a073 !important;

    background:
        linear-gradient(
            180deg,
            #351b15,
            #170c09
        ) !important;

    font-size:8px !important;
    font-weight:900 !important;

    letter-spacing:.6px !important;

    text-decoration:none !important;

    white-space:nowrap !important;
}


.mgm-delete-group:hover{
    color:#ffc09a !important;

    border-color:
        rgba(234,99,73,.88) !important;

    background:
        linear-gradient(
            180deg,
            #472219,
            #1d0e0a
        ) !important;
}


@media(max-width:800px){

    .mgm-hero{
        grid-template-columns:
            42px
            minmax(0,1fr) !important;
    }


    .mgm-back,
    .mgm-delete-group{
        grid-column:
            1 / -1 !important;

        width:100% !important;
    }

}


/* ==========================================================
   AUSTRALIA DELETE GROUP BUTTON V2 END
   ========================================================== */

</style>
</head>

<body>

<main class="mgm-shell">

    <header class="mgm-hero">

        <div class="mgm-hero-icon">

            <img
                src="/Other/assets/icons/sentinel/users.png"
                alt="">

        </div>

        <div class="mgm-hero-copy">

            <div class="mgm-kicker">
                ADMIN / GROUP MANAGEMENT
            </div>

            <h1>GROUP MANAGER</h1>

            <p>
                Manage group settings, membership and local group records.
            </p>

        </div>

        <a
            class="mgm-back"
            href="/Other/admin-groups-clean.php?id=<?=rawurlencode((string)($_GET['id'] ?? ''))?>">

            BACK TO MANAGE GROUPS

        </a>

<a
    class="mgm-delete-group"
    href="<?=ag_h('/Other/admin-group-delete-clean.php')?>?id=<?=rawurlencode($groupId)?>">
    DELETE GROUP
</a>


    </header>


    
<div class="mgm-message"><?php if ($error !== ''): ?>
    <div>
        <?=ag_h($error)?>
    </div>
<?php endif; ?></div>

<div class="mgm-message"><?php if ($success !== ''): ?>
    <div>
        <?=ag_h($success)?>
    </div>
<?php endif; ?></div>



    <div class="mgm-content">

        
<section class="mgm-card">

<div class="mgm-card-heading">
    <div>
        <span>GROUP CONFIGURATION</span>
        <h2>Group Settings</h2>
    </div>
</div>

<div class="mgm-card-body">

<form class="mgm-form" method="post" action="/Other/admin-group-manage-clean.php">

            <input type="hidden" name="csrf_token" value="<?=ag_h($csrfToken)?>">
            <input type="hidden" name="group_id" value="<?=ag_h($groupId)?>">
            <input type="hidden" name="action" value="save_settings">

            <div>

                <div>
                    <label>CHARTER</label>
                    <textarea name="charter" maxlength="4096"><?=ag_h($group['Charter'])?></textarea>
                </div>

                <div>
                    <label>INSIGNIA UUID</label>
                    <input
                        type="text"
                        name="insignia_id"
                        maxlength="36"
                        value="<?=ag_h($group['InsigniaID'])?>">
                </div>

                <div>
                    <label>MEMBERSHIP FEE</label>
                    <input
                        type="number"
                        name="membership_fee"
                        min="0"
                        max="1000000"
                        step="1"
                        value="<?=ag_h($group['MembershipFee'])?>">
                </div>

            </div>

            <div>

                <label>
                    <input
                        type="checkbox"
                        name="open_enrollment"
                        value="1"
                        <?=((string)$group['OpenEnrollment'] === '1') ? 'checked' : ''?>>
                    Open Enrollment
                </label>

                <label>
                    <input
                        type="checkbox"
                        name="show_in_list"
                        value="1"
                        <?=((int)$group['ShowInList']) ? 'checked' : ''?>>
                    Show In List
                </label>

                <label>
                    <input
                        type="checkbox"
                        name="allow_publish"
                        value="1"
                        <?=((int)$group['AllowPublish']) ? 'checked' : ''?>>
                    Allow Publish
                </label>

                <label>
                    <input
                        type="checkbox"
                        name="mature_publish"
                        value="1"
                        <?=((int)$group['MaturePublish']) ? 'checked' : ''?>>
                    Mature Publish
                </label>

            </div>

            <div>
                <button type="submit">
                    SAVE GROUP SETTINGS
                </button>

                <a
                    href="<?=ag_h(ag_route('admin_groups'))?>?id=<?=rawurlencode($groupId)?>"
                >
                    BACK TO GROUP
                </a>
            </div>

        </form>

</div>

</section>

<section class="mgm-card">

<div class="mgm-card-heading">
    <div>
        <span>MEMBERSHIP</span>
        <h2>Add Local Member</h2>
    </div>
</div>

<div class="mgm-card-body">

<form class="mgm-form" method="post" action="/Other/admin-group-manage-clean.php">

                <input type="hidden" name="csrf_token" value="<?=ag_h($csrfToken)?>">
                <input type="hidden" name="group_id" value="<?=ag_h($groupId)?>">
                <input type="hidden" name="action" value="add_member">

                <div>
                    <label>LOCAL AVATAR</label>

                    <select name="principal_id" required>
                        <option value="">Select an avatar...</option>

                        <?php foreach ($availableAccounts as $account): ?>

                            <option value="<?=ag_h($account['PrincipalID'])?>">
                                <?=ag_h(
                                    trim(
                                        (string)$account['FirstName'] .
                                        ' ' .
                                        (string)$account['LastName']
                                    )
                                )?>
                                â€” Level <?=ag_h($account['UserLevel'])?>
                            </option>

                        <?php endforeach; ?>
                    </select>

                </div>

                <div>
                    <button
                        type="submit"
                        onclick="return confirm('Add this avatar to <?=ag_h(addslashes($groupName))?>?');"
                    >
                        ADD MEMBER
                    </button>
                </div>

            </form>

</div>

</section>

<section class="mgm-card">

<div class="mgm-card-heading"><h2>Current Members (<?=count($members)?>)</h2></div>

<div class="mgm-card-body">

<table class="mgm-table">

                <thead>
                <tr>
                    <th>MEMBER</th>
                    <th>STATUS</th>
                    <th>ROLE STATE</th>
                    <th>PRINCIPAL ID</th>
                    <th>ACTION</th>
                </tr>
                </thead>

                <tbody>

                <?php foreach ($members as $member): ?>

                    <?php
                    $memberName =
                        trim(
                            (string)($member['FirstName'] ?? '') .
                            ' ' .
                            (string)($member['LastName'] ?? '')
                        );

                    $isFounder =
                        strcasecmp(
                            (string)$member['PrincipalID'],
                            (string)$group['FounderID']
                        ) === 0;

                    $isOwner =
                        (int)$member['IsOwner'] === 1;

                    $canRemove =
                        !$isFounder &&
                        !$isOwner;
                    ?>

                    <tr>

                        <td>
                            <strong>
                                <?= $memberName !== '' ? ag_h($memberName) : 'HG / UNKNOWN MEMBER' ?>
                            </strong>
                        </td>

                        <td>
                            <?php if ($isFounder): ?>
                                FOUNDER
                            <?php elseif ($isOwner): ?>
                                OWNER
                            <?php elseif (
                                isset($member['UserLevel']) &&
                                (int)$member['UserLevel'] < 0
                            ): ?>
                                DISABLED ACCOUNT
                            <?php else: ?>
                                MEMBER
                            <?php endif; ?>
                        </td>

                        <td>
                            <?=ag_h($member['SelectedRoleID'])?>
                        </td>

                        <td>
                            <?=ag_h($member['PrincipalID'])?>
                        </td>

                        <td>

                            <?php if ($canRemove): ?>

                                <form
                                    method="post"
                                    action="/Other/admin-group-manage-clean.php">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?=ag_h($csrfToken)?>">

                                    <input
                                        type="hidden"
                                        name="group_id"
                                        value="<?=ag_h($groupId)?>">

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="remove_member">

                                    <input
                                        type="hidden"
                                        name="principal_id"
                                        value="<?=ag_h($member['PrincipalID'])?>">

                                    <button
                                        type="submit"
                                        onclick="return confirm('Remove this member from <?=ag_h(addslashes($groupName))?>?');"
                                    >
                                        REMOVE
                                    </button>

                                </form>

                            <?php else: ?>

                                <span>
                                    Protected
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

</div>

</section>


    </div>

</main>


<script defer src="/Other/core/ag-confirm-modal.js?v=1"></script>
<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>



<script id="australia-group-settings-ajax-v1">

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const buttons =
            Array.from(
                document.querySelectorAll(
                    'button, input[type="submit"]'
                )
            );


        const saveButton =
            buttons.find(
                button => {

                    const text =
                        (
                            button.innerText ||
                            button.value ||
                            ""
                        )
                        .replace(/\s+/g, " ")
                        .trim()
                        .toUpperCase();

                    return text.includes(
                        "SAVE GROUP SETTINGS"
                    );
                }
            );


        if (!saveButton) {
            return;
        }


        const form =
            saveButton.closest(
                "form"
            );


        if (!form) {
            return;
        }


        /*
         * ------------------------------------------------------
         * STATUS MESSAGE
         * ------------------------------------------------------
         */

        let status =
            form.querySelector(
                ".mgm-save-status"
            );


        if (!status) {

            status =
                document.createElement(
                    "div"
                );

            status.className =
                "mgm-save-status";

            status.style.display =
                "none";

            form.appendChild(
                status
            );
        }


        /*
         * ------------------------------------------------------
         * AJAX SAVE
         *
         * The existing PHP controller still receives exactly
         * the same form fields and performs the real save.
         *
         * We simply prevent the iframe from navigating away
         * during the POST.
         * ------------------------------------------------------
         */

        form.addEventListener(
            "submit",
            async function (event) {

                event.preventDefault();


                if (form.dataset.saving === "1") {
                    return;
                }


                form.dataset.saving =
                    "1";


                const originalText =
                    saveButton.tagName === "INPUT"
                        ? saveButton.value
                        : saveButton.textContent;


                if (saveButton.tagName === "INPUT") {

                    saveButton.value =
                        "SAVING...";

                }
                else {

                    saveButton.textContent =
                        "SAVING...";
                }


                saveButton.disabled =
                    true;


                status.style.display =
                    "block";

                status.className =
                    "mgm-save-status saving";

                status.textContent =
                    "Saving group settings...";


                try {

                    const action =
                        form.getAttribute(
                            "action"
                        ) ||
                        window.location.href;


                    const response =
                        await fetch(
                            action,
                            {
                                method:
                                    "POST",

                                body:
                                    new FormData(
                                        form
                                    ),

                                credentials:
                                    "same-origin",

                                cache:
                                    "no-store",

                                redirect:
                                    "follow"
                            }
                        );


                    if (!response.ok) {

                        throw new Error(
                            "HTTP " +
                            response.status
                        );
                    }


                    /*
                     * Consume the response so the request fully
                     * completes, but DO NOT navigate the iframe.
                     */

                    await response.text();


                    status.className =
                        "mgm-save-status saved";

                    status.textContent =
                        "GROUP SETTINGS SAVED";


                    window.setTimeout(
                        function () {

                            status.style.display =
                                "none";
                        },
                        3000
                    );

                }
                catch (error) {

                    console.error(
                        "Group settings save failed:",
                        error
                    );


                    status.className =
                        "mgm-save-status error";

                    status.textContent =
                        "SAVE FAILED - SETTINGS WERE NOT CONFIRMED";
                }
                finally {

                    form.dataset.saving =
                        "0";

                    saveButton.disabled =
                        false;


                    if (saveButton.tagName === "INPUT") {

                        saveButton.value =
                            originalText;

                    }
                    else {

                        saveButton.textContent =
                            originalText;
                    }
                }

            }
        );

    }
);

</script>


<script id="australia-avatar-dropdown-clean-v1">

document.addEventListener(
    "DOMContentLoaded",
    function () {

        /*
         * Find ONLY the Add Local Member form.
         * Nothing else on the page is modified.
         */

        const forms =
            Array.from(
                document.querySelectorAll(
                    "form"
                )
            );


        const addMemberForm =
            forms.find(
                form => {

                    const text =
                        (
                            form.textContent ||
                            ""
                        )
                        .replace(/\s+/g, " ")
                        .trim()
                        .toUpperCase();

                    return text.includes(
                        "ADD MEMBER"
                    );
                }
            );


        if (!addMemberForm) {
            return;
        }


        const avatarSelect =
            addMemberForm.querySelector(
                "select"
            );


        if (!avatarSelect) {
            return;
        }


        function cleanAvatarLabel(
            value
        ){

            let text =
                String(
                    value || ""
                );


            /*
             * Repair common UTF-8 / Windows character
             * corruption without changing real avatar IDs.
             */

            const replacements = [
                ["â€”", " - "],
                ["â€“", " - "],
                ["â€¢", " - "],
                ["â€˜", "'"],
                ["â€™", "'"],
                ["â€œ", '"'],
                ["â€", '"'],
                ["Â·",  " - "],
                ["Â",   ""],
                ["ï¿½", ""],
                ["�",   ""]
            ];


            replacements.forEach(
                pair => {

                    text =
                        text.split(
                            pair[0]
                        ).join(
                            pair[1]
                        );
                }
            );


            /*
             * Remove invisible/control characters that should
             * never appear inside an avatar display name.
             */

            text =
                text.replace(
                    /[\u0000-\u001F\u007F-\u009F]/g,
                    ""
                );


            /*
             * Clean doubled separators / whitespace created
             * by damaged characters.
             */

            text =
                text
                .replace(
                    /\s+-\s+-\s+/g,
                    " - "
                )
                .replace(
                    /\s{2,}/g,
                    " "
                )
                .trim();


            return text;
        }


        Array.from(
            avatarSelect.options
        )
        .forEach(
            option => {

                /*
                 * IMPORTANT:
                 *
                 * option.value is deliberately untouched.
                 * That remains the real PrincipalID / backend
                 * value used when ADD MEMBER is pressed.
                 */

                option.textContent =
                    cleanAvatarLabel(
                        option.textContent
                    );
            }
        );


        avatarSelect.dataset.avatarNamesClean =
            "1";

    }
);

</script>

</body>
</html>