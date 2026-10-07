<?php

require_once __DIR__ . '/admin-group-create-clean-controller.php';

?>
<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Create Group</title>

<style id="australia-create-group-clean-v1">

*{
    box-sizing:border-box;
}

html,
body{
    margin:0;
    padding:0;
    width:100%;
    min-height:100%;
}

body{
    min-height:100vh;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    color:#d5dbd7;

    background:transparent;
}

.cgc-shell{
    width:100%;
    max-width:none;

    margin:0;

    padding:14px;
}


/* ==========================================================
   HERO
   ========================================================== */

.cgc-hero{
    display:grid;

    grid-template-columns:
        minmax(0,1fr)
        auto;

    align-items:center;

    gap:14px;

    width:100%;

    margin-bottom:12px;

    padding:15px 17px;

    border:
        1px solid
        rgba(207,158,45,.36);

    border-radius:8px;

    background:
        linear-gradient(
            145deg,
            rgba(18,24,21,.98),
            rgba(6,10,8,.98)
        );

    box-shadow:
        0 10px 28px
        rgba(0,0,0,.24);
}

.cgc-kicker{
    margin:0 0 4px;

    color:#a9873c;

    font-size:8px;
    font-weight:900;

    letter-spacing:1.4px;
}

.cgc-hero h1{
    margin:0;

    color:#efbd4e;

    font-size:22px;
    font-weight:900;

    line-height:1.1;
}

.cgc-hero p{
    margin:5px 0 0;

    color:#88958e;

    font-size:10px;
}

.cgc-back{
    display:inline-flex;

    align-items:center;
    justify-content:center;

    min-height:36px;

    padding:7px 13px;

    border:
        1px solid
        rgba(218,168,49,.48);

    border-radius:6px;

    color:#efbd4e;

    background:
        linear-gradient(
            180deg,
            #2b2518,
            #14120d
        );

    font-size:8px;
    font-weight:900;

    letter-spacing:.6px;

    text-decoration:none;

    white-space:nowrap;
}

.cgc-back:hover{
    color:#ffd36a;

    border-color:
        rgba(240,187,63,.75);
}


/* ==========================================================
   CREATE CARD
   ========================================================== */

.cgc-card{
    width:100%;
    max-width:1180px;

    margin:
        0
        auto;

    overflow:hidden;

    border:
        1px solid
        rgba(204,157,45,.31);

    border-radius:9px;

    background:
        linear-gradient(
            145deg,
            rgba(15,21,18,.985),
            rgba(6,10,8,.985)
        );

    box-shadow:
        0 10px 30px
        rgba(0,0,0,.26);
}

.cgc-card-header{
    padding:13px 15px;

    border-bottom:
        1px solid
        rgba(189,146,43,.19);

    background:
        linear-gradient(
            180deg,
            #171c19,
            #0b100e
        );
}

.cgc-card-header span{
    display:block;

    margin-bottom:3px;

    color:#8d7c56;

    font-size:7px;
    font-weight:900;

    letter-spacing:1.2px;
}

.cgc-card-header h2{
    margin:0;

    color:#e9e4d9;

    font-size:15px;
    font-weight:900;
}

.cgc-card-body{
    padding:15px;
}


/* ==========================================================
   INFORMATION
   ========================================================== */

.cgc-note{
    margin-bottom:14px;

    padding:11px 13px;

    border:
        1px solid
        rgba(201,154,43,.28);

    border-radius:6px;

    color:#d6c98f;

    background:
        linear-gradient(
            135deg,
            rgba(41,37,20,.64),
            rgba(20,22,15,.76)
        );

    font-size:9px;
    line-height:1.55;
}


/* ==========================================================
   STATUS / ERRORS
   ========================================================== */

.cgc-message{
    margin-bottom:12px;

    padding:10px 12px;

    border-radius:6px;

    font-size:9px;
    font-weight:700;
}

.cgc-message.error{
    border:
        1px solid
        rgba(191,72,72,.42);

    color:#f0a0a0;

    background:#180b0b;
}

.cgc-message.success{
    border:
        1px solid
        rgba(73,166,92,.40);

    color:#98dfa7;

    background:#09150d;
}


/* ==========================================================
   REAL CREATE GROUP FORM
   ========================================================== */

.cgc-form{
    display:grid;

    grid-template-columns:
        repeat(2,minmax(0,1fr));

    gap:
        12px
        16px;

    width:100%;

    margin:0;
}


/*
 * Structural wrappers from the working form do not control
 * the new presentation. This lets their real fields participate
 * in the fresh grid without importing old CSS.
 */

.cgc-form > div:not(:has(> input:not([type="hidden"]))):not(:has(> select)):not(:has(> textarea)):not(:has(> button)):not(:has(> a)):not(:has(> label > input[type="checkbox"])){
    display:contents;
}


.cgc-form div{
    min-width:0;
}


.cgc-form label{
    display:block;

    margin-bottom:5px;

    color:#aa925b;

    font-size:8px;
    font-weight:900;

    letter-spacing:.55px;
}


.cgc-form input[type="text"],
.cgc-form input[type="number"],
.cgc-form input[type="email"],
.cgc-form select,
.cgc-form textarea{
    width:100%;

    min-height:36px;

    padding:
        7px
        9px;

    border:
        1px solid
        rgba(145,132,98,.30);

    border-radius:5px;

    outline:none;

    color:#d8ddd8;

    background:#060a08;

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.015);

    font-family:inherit;
}


.cgc-form textarea{
    min-height:115px;

    resize:vertical;
}


.cgc-form input:focus,
.cgc-form select:focus,
.cgc-form textarea:focus{
    border-color:
        rgba(219,168,48,.68);

    box-shadow:
        0 0 0 2px
        rgba(219,168,48,.06);
}


/* Charter / text-area field gets full row */

.cgc-form div:has(textarea){
    grid-column:
        1 / -1;
}


/* ==========================================================
   CHECKBOXES
   ========================================================== */

.cgc-form label:has(input[type="checkbox"]){
    display:flex;

    align-items:center;

    gap:8px;

    min-height:38px;

    margin:0;

    padding:
        8px
        10px;

    border:
        1px solid
        rgba(150,132,84,.20);

    border-radius:6px;

    color:#c6c9c4;

    background:#090e0c;

    cursor:pointer;
}


.cgc-form input[type="checkbox"]{
    width:15px;
    height:15px;

    margin:0;

    accent-color:#c99a32;
}


/* ==========================================================
   ACTIONS
   ========================================================== */

.cgc-form button,
.cgc-form input[type="submit"],
.cgc-form a{
    display:inline-flex;

    align-items:center;
    justify-content:center;

    min-height:34px;

    padding:
        7px
        12px;

    border:
        1px solid
        rgba(209,160,46,.42);

    border-radius:5px;

    color:#e7b64a;

    background:
        linear-gradient(
            180deg,
            #292318,
            #14120d
        );

    font-size:8px;
    font-weight:900;

    text-decoration:none;

    cursor:pointer;
}


.cgc-form button:hover,
.cgc-form input[type="submit"]:hover,
.cgc-form a:hover{
    color:#ffd16a;

    border-color:
        rgba(237,185,61,.70);
}


/*
 * Submit/action area gets full row.
 */

.cgc-form div:has(> button),
.cgc-form div:has(> input[type="submit"]),
.cgc-form div:has(> a){
    grid-column:
        1 / -1;
}


/* ==========================================================
   HIDE OLD PRESENTATION-ONLY ELEMENTS IF THEY WERE INSIDE FORM
   ========================================================== */

.cgc-form nav,
.cgc-form header,
.cgc-form footer{
    display:none !important;
}


/* ==========================================================
   RESPONSIVE
   ========================================================== */

@media(max-width:780px){

    .cgc-shell{
        padding:8px;
    }

    .cgc-hero{
        grid-template-columns:1fr;
    }

    .cgc-back{
        width:100%;
    }

    .cgc-form{
        grid-template-columns:1fr;
    }

    .cgc-form div:has(textarea),
    .cgc-form div:has(> button),
    .cgc-form div:has(> input[type="submit"]),
    .cgc-form div:has(> a){
        grid-column:auto;
    }
}



/* ==========================================================
   AUSTRALIA CREATE GROUP SCREEN FIT V2 START
   ========================================================== */

.cgc-shell{
    width:100% !important;
    max-width:none !important;

    padding:
        12px
        14px !important;
}


.cgc-hero{
    width:100% !important;

    margin:
        0
        0
        10px
        0 !important;
}


/*
 * Use the entire Control Center workspace instead of
 * restricting the form to the old centered width.
 */

.cgc-card{
    width:100% !important;
    max-width:none !important;

    margin:
        0 !important;
}


.cgc-card-body{
    width:100% !important;

    padding:
        14px
        16px
        16px !important;
}


/* ==========================================================
   BALANCED 12-COLUMN FORM
   ========================================================== */

.cgc-form{
    width:100% !important;

    display:grid !important;

    grid-template-columns:
        repeat(
            12,
            minmax(0,1fr)
        ) !important;

    gap:
        11px
        14px !important;
}


/*
 * Override presentation wrappers inherited from the real form.
 * The underlying names, values and POST fields are unchanged.
 */

.cgc-form > div{
    display:block !important;

    min-width:0 !important;

    grid-column:
        span 6 !important;
}


/*
 * Charter uses the complete row.
 */

.cgc-form > div:has(textarea){
    grid-column:
        1 / -1 !important;
}


/*
 * Four group-option boxes across one row.
 */

.cgc-form > div:has(input[type="checkbox"]){
    grid-column:
        span 3 !important;
}


.cgc-form > div:has(input[type="checkbox"]) label{
    width:100% !important;
    height:100% !important;

    min-height:42px !important;

    display:flex !important;

    align-items:center !important;

    margin:0 !important;

    padding:
        9px
        11px !important;
}


/*
 * Action buttons occupy the final complete row.
 */

.cgc-form > div:has(> button),
.cgc-form > div:has(> input[type="submit"]),
.cgc-form > div:has(> a){
    grid-column:
        1 / -1 !important;
}


/*
 * Some versions of the original form place the checkbox
 * label itself directly inside the form.
 */

.cgc-form > label:has(input[type="checkbox"]){
    grid-column:
        span 3 !important;

    min-height:42px !important;
}


/*
 * Keep inputs comfortably sized across the wider workspace.
 */

.cgc-form input[type="text"],
.cgc-form input[type="number"],
.cgc-form input[type="email"],
.cgc-form select{
    min-height:38px !important;
}


.cgc-form textarea{
    min-height:125px !important;
}


/* ==========================================================
   MEDIUM WIDTH
   ========================================================== */

@media(max-width:1100px){

    .cgc-form > div:has(input[type="checkbox"]),
    .cgc-form > label:has(input[type="checkbox"]){
        grid-column:
            span 6 !important;
    }

}


/* ==========================================================
   SMALL WIDTH
   ========================================================== */

@media(max-width:760px){

    .cgc-shell{
        padding:
            8px !important;
    }


    .cgc-form > div,
    .cgc-form > div:has(textarea),
    .cgc-form > div:has(input[type="checkbox"]),
    .cgc-form > label:has(input[type="checkbox"]),
    .cgc-form > div:has(> button),
    .cgc-form > div:has(> input[type="submit"]),
    .cgc-form > div:has(> a){
        grid-column:
            1 / -1 !important;
    }

}


/* ==========================================================
   AUSTRALIA CREATE GROUP SCREEN FIT V2 END
   ========================================================== */



/* ==========================================================
   AUSTRALIA CREATE GROUP CONFIRM MODAL V1 START
   ========================================================== */

.cgc-modal-overlay{
    position:fixed;
    inset:0;

    z-index:999999;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:24px;

    background:
        rgba(0,0,0,.74);

    backdrop-filter:
        blur(3px);
}

.cgc-modal-overlay[hidden]{
    display:none !important;
}


.cgc-modal{
    width:min(
        520px,
        calc(100vw - 40px)
    );

    overflow:hidden;

    border:
        1px solid
        rgba(220,169,48,.62);

    border-radius:9px;

    background:
        linear-gradient(
            145deg,
            #151b18,
            #070b09
        );

    box-shadow:
        0 24px 70px
        rgba(0,0,0,.72),
        0 0 0 1px
        rgba(255,196,66,.04);
}


.cgc-modal-header{
    display:flex;
    align-items:center;

    gap:11px;

    padding:
        14px
        16px;

    border-bottom:
        1px solid
        rgba(208,159,44,.24);

    background:
        linear-gradient(
            180deg,
            #1b201b,
            #0e120f
        );
}


.cgc-modal-icon{
    width:34px;
    height:34px;

    flex:
        0 0 34px;

    display:flex;
    align-items:center;
    justify-content:center;

    border:
        1px solid
        rgba(218,167,47,.40);

    border-radius:6px;

    color:#efbd4e;

    background:#090d0b;

    font-size:17px;
    font-weight:900;
}


.cgc-modal-title-wrap{
    min-width:0;
}


.cgc-modal-kicker{
    margin-bottom:2px;

    color:#947b48;

    font-size:7px;
    font-weight:900;

    letter-spacing:1.2px;
}


.cgc-modal-title{
    margin:0;

    color:#efbd4e;

    font-size:15px;
    font-weight:900;
}


.cgc-modal-body{
    padding:
        17px
        17px
        14px;
}


.cgc-modal-body p{
    margin:
        0
        0
        13px;

    color:#c8cfca;

    font-size:10px;
    line-height:1.55;
}


.cgc-modal-group{
    padding:
        10px
        11px;

    border:
        1px solid
        rgba(175,143,69,.24);

    border-radius:6px;

    background:#080d0a;
}


.cgc-modal-group span{
    display:block;

    margin-bottom:4px;

    color:#88774f;

    font-size:7px;
    font-weight:900;

    letter-spacing:.8px;
}


.cgc-modal-group strong{
    display:block;

    overflow-wrap:anywhere;

    color:#e5e1d7;

    font-size:11px;
}


.cgc-modal-actions{
    display:flex;

    justify-content:flex-end;

    gap:8px;

    padding:
        12px
        16px
        15px;

    border-top:
        1px solid
        rgba(194,148,42,.16);
}


.cgc-modal-button{
    min-width:105px;
    min-height:34px;

    display:inline-flex;
    align-items:center;
    justify-content:center;

    padding:
        7px
        13px;

    border-radius:5px;

    font-family:inherit;

    font-size:8px;
    font-weight:900;

    letter-spacing:.4px;

    cursor:pointer;
}


.cgc-modal-button.cancel{
    border:
        1px solid
        rgba(143,143,129,.28);

    color:#b8beb9;

    background:
        linear-gradient(
            180deg,
            #1a1e1b,
            #0d100e
        );
}


.cgc-modal-button.confirm{
    border:
        1px solid
        rgba(227,176,52,.65);

    color:#ffd066;

    background:
        linear-gradient(
            180deg,
            #342b18,
            #17130b
        );

    box-shadow:
        inset 0 1px 0
        rgba(255,211,102,.06);
}


.cgc-modal-button:hover{
    filter:
        brightness(1.12);
}


.cgc-modal-button:focus-visible{
    outline:
        2px solid
        rgba(239,189,78,.55);

    outline-offset:2px;
}


@media(max-width:600px){

    .cgc-modal-actions{
        flex-direction:column-reverse;
    }

    .cgc-modal-button{
        width:100%;
    }
}


/* ==========================================================
   AUSTRALIA CREATE GROUP CONFIRM MODAL V1 END
   ========================================================== */

</style>

</head>

<body>

<div class="cgc-shell">

    <section class="cgc-hero">

        <div>

            <div class="cgc-kicker">
                <?php echo ag_grid_name_html(); ?> · GROUP MANAGEMENT
            </div>

            <h1>
                CREATE GROUP
            </h1>

            <p>
                Create a new local OpenSim group using the Grid's existing group backend.
            </p>

        </div>

        <a
            class="cgc-back"
            href="/Other/admin-groups-clean.php">
            BACK TO MANAGE GROUPS
        </a>

    </section>


    <section class="cgc-card">

        <div class="cgc-card-header">

            <span>
                NEW LOCAL GROUP
            </span>

            <h2>
                Group Details
            </h2>

        </div>


        <div class="cgc-card-body">

            <div class="cgc-note">
                The new group will be created with the standard
                Everyone, Officers and Owners roles. The selected
                founder becomes the first member, receives the
                required founder roles, and the new group becomes
                that avatar's active group.
            </div>


            <?php

            if (
                isset($error) &&
                is_string($error) &&
                trim($error) !== ''
            ):
            ?>

                <div class="cgc-message error">
                    <?=ag_h($error)?>
                </div>

            <?php endif; ?>


            <?php

            if (
                isset($errors) &&
                is_array($errors) &&
                $errors
            ):
            ?>

                <div class="cgc-message error">

                    <?php foreach ($errors as $createError): ?>

                        <div>
                            <?=ag_h((string)$createError)?>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


            <?php

            if (
                isset($message) &&
                is_string($message) &&
                trim($message) !== ''
            ):
            ?>

                <div class="cgc-message success">
                    <?=ag_h($message)?>
                </div>

            <?php endif; ?>


<form class="cgc-form" method="post" action="<?=ag_h('/Other/admin-group-create-clean.php')?>">

        <input
            type="hidden"
            name="csrf_token"
            value="<?=ag_h($csrfToken)?>">

        <div class="form-grid">

            <div class="field">
                <label>GROUP NAME</label>
                <input
                    type="text"
                    name="name"
                    maxlength="255"
                    value="<?=ag_h($name)?>"
                    required>
            </div>

            <div class="field">
                <label>FOUNDER</label>

                <select
                    name="founder_id"
                    required>

                    <option value="">
                        Select founder...
                    </option>

                    <?php foreach ($founders as $founder): ?>

                        <option
                            value="<?=ag_h($founder['PrincipalID'])?>"
                            <?=$founderId === (string)$founder['PrincipalID'] ? 'selected' : ''?>
                        >
                            <?=ag_h(
                                trim(
                                    (string)$founder['FirstName'] .
                                    ' ' .
                                    (string)$founder['LastName']
                                )
                            )?>
                            â€” Level <?=ag_h($founder['UserLevel'])?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <div class="field full">
                <label>CHARTER</label>
                <textarea
                    name="charter"
                    maxlength="4096"><?=ag_h($charter)?></textarea>
            </div>

            <div class="field">
                <label>INSIGNIA UUID</label>
                <input
                    type="text"
                    name="insignia_id"
                    maxlength="36"
                    value="<?=ag_h($insigniaId !== '' ? $insigniaId : $zeroUuid)?>">
            </div>

            <div class="field">
                <label>MEMBERSHIP FEE</label>
                <input
                    type="number"
                    name="membership_fee"
                    min="0"
                    max="1000000"
                    step="1"
                    value="<?=ag_h($membershipFee)?>">
            </div>

        </div>

        <div class="check-grid">

            <label class="check">
                <input
                    type="checkbox"
                    name="open_enrollment"
                    value="1"
                    <?=isset($_POST['open_enrollment']) ? 'checked' : ''?>>
                Open Enrollment
            </label>

            <label class="check">
                <input
                    type="checkbox"
                    name="show_in_list"
                    value="1"
                    <?=($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || isset($_POST['show_in_list']) ? 'checked' : ''?>>
                Show In List
            </label>

            <label class="check">
                <input
                    type="checkbox"
                    name="allow_publish"
                    value="1"
                    <?=isset($_POST['allow_publish']) ? 'checked' : ''?>>
                Allow Publish
            </label>

            <label class="check">
                <input
                    type="checkbox"
                    name="mature_publish"
                    value="1"
                    <?=isset($_POST['mature_publish']) ? 'checked' : ''?>>
                Mature Publish
            </label>

        </div>

        <div class="actions">

            <button
                class="ag-button primary"
                type="submit"
            >
                CREATE GROUP
            </button>

            <a
                class="ag-button"
                href="<?=ag_h('/Other/admin-groups-clean.php')?>"
            >
                CANCEL
            </a>

        </div>

    </form>


        </div>

    </section>

</div>


<script id="australia-create-group-founder-clean-v1">

document.addEventListener(
    "DOMContentLoaded",
    function(){

        const form =
            document.querySelector(
                ".cgc-form"
            );


        if(!form){
            return;
        }


        /*
         * Clean display corruption in the Founder dropdown.
         * OPTION VALUES / UUIDs ARE NEVER ALTERED.
         */

        Array.from(
            form.querySelectorAll(
                "select"
            )
        )
        .forEach(
            select => {

                Array.from(
                    select.options
                )
                .forEach(
                    option => {

                        let text =
                            String(
                                option.textContent ||
                                ""
                            );


                        const replacements = [

                            ["â€”", " - "],
                            ["â€“", " - "],
                            ["â€¢", " - "],
                            ["â€˜", "'"],
                            ["â€™", "'"],
                            ["â€œ", '"'],
                            ["â€", '"'],
                            ["Â·", " - "],
                            ["Â", ""],
                            ["ï¿½", ""],
                            ["�", ""]

                        ];


                        replacements.forEach(
                            pair => {

                                text =
                                    text
                                    .split(
                                        pair[0]
                                    )
                                    .join(
                                        pair[1]
                                    );
                            }
                        );


                        text =
                            text
                            .replace(
                                /[\u0000-\u001F\u007F-\u009F]/g,
                                ""
                            )
                            .replace(
                                /\s{2,}/g,
                                " "
                            )
                            .trim();


                        option.textContent =
                            text;

                    }
                );

            }
        );

    }
);

</script>



<!-- AUSTRALIA CREATE GROUP CONFIRM MODAL V1 START -->

<div
    id="cgcConfirmOverlay"
    class="cgc-modal-overlay"
    hidden
    aria-hidden="true">

    <section
        class="cgc-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cgcConfirmTitle">

        <div class="cgc-modal-header">

            <div class="cgc-modal-icon">
                +
            </div>

            <div class="cgc-modal-title-wrap">

                <div class="cgc-modal-kicker">
                    <?php echo ag_grid_name_html(); ?> · GROUP MANAGEMENT
                </div>

                <h2
                    id="cgcConfirmTitle"
                    class="cgc-modal-title">
                    CREATE NEW GROUP?
                </h2>

            </div>

        </div>


        <div class="cgc-modal-body">

            <p>
                Confirm that you want to create this new Grid group.
                The selected founder will become the first member and
                the standard group roles will be created automatically.
            </p>


            <div class="cgc-modal-group">

                <span>
                    GROUP NAME
                </span>

                <strong id="cgcConfirmGroupName">
                    New Group
                </strong>

            </div>

        </div>


        <div class="cgc-modal-actions">

            <button
                id="cgcConfirmCancel"
                class="cgc-modal-button cancel"
                type="button">
                CANCEL
            </button>

            <button
                id="cgcConfirmCreate"
                class="cgc-modal-button confirm"
                type="button">
                CREATE GROUP
            </button>

        </div>

    </section>

</div>

<!-- AUSTRALIA CREATE GROUP CONFIRM MODAL V1 END -->


<script id="australia-create-group-confirm-modal-v1">

document.addEventListener(
    "DOMContentLoaded",
    function(){

        const form =
            document.querySelector(
                ".cgc-form"
            );


        const overlay =
            document.getElementById(
                "cgcConfirmOverlay"
            );


        const createButton =
            document.getElementById(
                "cgcConfirmCreate"
            );


        const cancelButton =
            document.getElementById(
                "cgcConfirmCancel"
            );


        const groupNameDisplay =
            document.getElementById(
                "cgcConfirmGroupName"
            );


        if(
            !form ||
            !overlay ||
            !createButton ||
            !cancelButton ||
            !groupNameDisplay
        ){
            return;
        }


        let approved =
            false;


        let pendingSubmitter =
            null;


        let previousFocus =
            null;


        function findGroupName(){

            const candidates =
                Array.from(
                    form.querySelectorAll(
                        'input[type="text"]'
                    )
                );


            const named =
                candidates.find(
                    input => {

                        const name =
                            String(
                                input.name ||
                                ""
                            )
                            .toLowerCase();

                        return (
                            name.includes("group") &&
                            name.includes("name")
                        );
                    }
                );


            const input =
                named ||
                candidates[0] ||
                null;


            const value =
                input
                    ? String(
                        input.value ||
                        ""
                    ).trim()
                    : "";


            return value ||
                "New Group";
        }


        function openModal(
            submitter
        ){

            pendingSubmitter =
                submitter ||
                null;


            previousFocus =
                document.activeElement;


            groupNameDisplay.textContent =
                findGroupName();


            overlay.hidden =
                false;


            overlay.setAttribute(
                "aria-hidden",
                "false"
            );


            document.body.style.overflow =
                "hidden";


            window.setTimeout(
                function(){

                    createButton.focus();

                },
                0
            );
        }


        function closeModal(){

            overlay.hidden =
                true;


            overlay.setAttribute(
                "aria-hidden",
                "true"
            );


            document.body.style.overflow =
                "";


            if(
                previousFocus &&
                typeof previousFocus.focus ===
                "function"
            ){

                previousFocus.focus();
            }
        }


        form.addEventListener(
            "submit",
            function(event){

                /*
                 * Second pass after the user pressed
                 * our CREATE GROUP button.
                 */

                if(approved){

                    approved =
                        false;

                    return;
                }


                event.preventDefault();


                openModal(
                    event.submitter ||
                    null
                );

            }
        );


        createButton.addEventListener(
            "click",
            function(){

                approved =
                    true;


                closeModal();


                /*
                 * requestSubmit preserves the real submit
                 * button and all original POST fields.
                 */

                if(
                    typeof form.requestSubmit ===
                    "function"
                ){

                    form.requestSubmit(
                        pendingSubmitter ||
                        undefined
                    );

                }
                else {

                    HTMLFormElement
                        .prototype
                        .submit
                        .call(
                            form
                        );
                }

            }
        );


        cancelButton.addEventListener(
            "click",
            function(){

                pendingSubmitter =
                    null;


                closeModal();

            }
        );


        overlay.addEventListener(
            "click",
            function(event){

                if(
                    event.target ===
                    overlay
                ){

                    pendingSubmitter =
                        null;


                    closeModal();
                }

            }
        );


        document.addEventListener(
            "keydown",
            function(event){

                if(
                    overlay.hidden
                ){
                    return;
                }


                if(
                    event.key ===
                    "Escape"
                ){

                    event.preventDefault();


                    pendingSubmitter =
                        null;


                    closeModal();
                }

            }
        );

    }
);

</script>


</body>
</html>