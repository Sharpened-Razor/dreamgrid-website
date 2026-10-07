<?php

require_once __DIR__ . '/admin-group-delete-clean-controller.php';

$returnGroupId =
    isset($groupId)
        ? (string)$groupId
        : (string)($_GET['id'] ?? '');

?>
<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Delete Group</title>


<style id="australia-delete-group-clean-v1">

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

    color:#d7ddd8;

    background:transparent;
}


.cgd-shell{
    width:100%;
    max-width:none;

    margin:0;

    padding:
        12px
        14px
        18px;
}


/* ==========================================================
   HERO
   ========================================================== */

.cgd-hero{
    width:100%;

    display:grid;

    grid-template-columns:
        minmax(0,1fr)
        auto
        auto;

    align-items:center;

    gap:9px;

    margin:
        0
        0
        10px;

    padding:
        14px
        16px;

    border:
        1px solid
        rgba(211,162,48,.38);

    border-radius:8px;

    background:
        linear-gradient(
            145deg,
            rgba(18,24,21,.98),
            rgba(6,10,8,.98)
        );

    box-shadow:
        0 10px 28px
        rgba(0,0,0,.25);
}


.cgd-kicker{
    margin:
        0
        0
        3px;

    color:#a9873c;

    font-size:8px;
    font-weight:900;

    letter-spacing:1.4px;
}


.cgd-hero h1{
    margin:0;

    color:#efbd4e;

    font-size:22px;
    font-weight:900;

    line-height:1.1;
}


.cgd-hero p{
    margin:
        5px
        0
        0;

    color:#89958e;

    font-size:10px;
}


.cgd-nav{
    display:inline-flex;

    align-items:center;
    justify-content:center;

    min-height:35px;

    padding:
        7px
        12px;

    border:
        1px solid
        rgba(218,168,49,.44);

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

    letter-spacing:.5px;

    text-decoration:none;

    white-space:nowrap;
}


.cgd-nav:hover{
    color:#ffd36a;

    border-color:
        rgba(240,187,63,.74);
}


/* ==========================================================
   MAIN CARD
   ========================================================== */

.cgd-card{
    width:100%;
    max-width:none;

    margin:0;

    padding:
        14px;

    border:
        1px solid
        rgba(204,157,45,.31);

    border-radius:8px;

    background:
        linear-gradient(
            145deg,
            rgba(15,21,18,.985),
            rgba(6,10,8,.985)
        );

    box-shadow:
        0 10px 30px
        rgba(0,0,0,.24);
}


/* ==========================================================
   TRANSPLANTED REAL DELETE CONTENT
   ========================================================== */

.cgd-real-panel{
    width:100% !important;
    max-width:none !important;

    margin:0 !important;
    padding:0 !important;

    display:grid !important;

    grid-template-columns:
        repeat(12,minmax(0,1fr));

    gap:
        10px
        12px;

    color:#d3d9d5 !important;

    background:transparent !important;

    border:0 !important;

    box-shadow:none !important;
}


.cgd-real-panel > *{
    grid-column:
        1 / -1;

    min-width:0;
}


/* ==========================================================
   HEADINGS
   ========================================================== */

.cgd-real-panel h1,
.cgd-real-panel h2,
.cgd-real-panel h3,
.cgd-real-panel h4{
    margin:
        5px
        0
        2px;

    color:#efbd4e !important;

    font-weight:900 !important;
}


.cgd-real-panel h1{
    font-size:19px !important;
}


.cgd-real-panel h2{
    font-size:17px !important;
}


.cgd-real-panel h3{
    font-size:13px !important;
}


/* ==========================================================
   COMMON OLD STRUCTURE -> CLEAN GRID
   ========================================================== */

.cgd-real-panel [class*="grid"],
.cgd-real-panel [class*="summary"],
.cgd-real-panel [class*="stats"],
.cgd-real-panel [class*="details"]{
    width:100% !important;

    display:grid !important;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(175px,1fr)
        ) !important;

    gap:9px !important;

    margin:
        0 !important;
}


/* ==========================================================
   INFORMATION / STAT CARDS
   ========================================================== */

.cgd-info-box,
.cgd-stat-card{
    min-width:0 !important;

    padding:
        10px
        11px !important;

    border:
        1px solid
        rgba(152,134,86,.22) !important;

    border-radius:6px !important;

    background:
        linear-gradient(
            145deg,
            #0c120f,
            #070b09
        ) !important;
}


.cgd-info-box span,
.cgd-info-box label,
.cgd-stat-card span,
.cgd-stat-card label{
    display:block;

    margin-bottom:3px;

    color:#9a8658 !important;

    font-size:7px !important;
    font-weight:900 !important;

    letter-spacing:.55px !important;
}


.cgd-info-box strong,
.cgd-stat-card strong{
    color:#e6e2d8 !important;

    font-size:13px !important;
}


/* ==========================================================
   PERMANENT DELETE WARNINGS
   ========================================================== */

.cgd-danger-banner{
    width:100% !important;

    padding:
        11px
        13px !important;

    border:
        1px solid
        rgba(190,72,62,.48) !important;

    border-radius:6px !important;

    color:#e4c8c3 !important;

    background:
        linear-gradient(
            145deg,
            rgba(57,20,17,.55),
            rgba(20,11,9,.82)
        ) !important;
}


.cgd-danger-banner strong{
    color:#ffb39f !important;
}


.cgd-removal-heading{
    margin-top:
        5px !important;

    color:#efbd4e !important;
}


/* ==========================================================
   REAL DELETE FORM
   ========================================================== */

.cgd-delete-form{
    width:100% !important;

    display:grid !important;

    grid-template-columns:
        repeat(2,minmax(0,1fr));

    gap:
        10px
        14px;

    margin:
        2px
        0
        0 !important;

    padding:
        13px !important;

    border:
        1px solid
        rgba(176,140,61,.23) !important;

    border-radius:7px !important;

    background:
        rgba(7,11,9,.72) !important;
}


.cgd-delete-form > *{
    min-width:0;
}


.cgd-delete-form label{
    display:block;

    color:#aa925b !important;

    font-size:8px !important;
    font-weight:900 !important;

    letter-spacing:.45px !important;
}


.cgd-delete-form input[type="text"],
.cgd-delete-form input[type="number"],
.cgd-delete-form input[type="password"],
.cgd-delete-form textarea,
.cgd-delete-form select{
    width:100% !important;

    min-height:37px;

    padding:
        7px
        9px;

    border:
        1px solid
        rgba(145,132,98,.31) !important;

    border-radius:5px;

    outline:none;

    color:#e0e4e0 !important;

    background:#050907 !important;
}


.cgd-delete-form textarea{
    min-height:90px;

    resize:vertical;
}


.cgd-delete-form input:focus,
.cgd-delete-form textarea:focus,
.cgd-delete-form select:focus{
    border-color:
        rgba(219,168,48,.67) !important;
}


.cgd-delete-form input[type="checkbox"]{
    width:15px;
    height:15px;

    accent-color:#c99a32;
}


/*
 * Destructive confirmation fields and checkbox rows
 * should use the complete width.
 */

.cgd-delete-form > div:has(input[type="checkbox"]),
.cgd-delete-form > label:has(input[type="checkbox"]),
.cgd-delete-form > div:has(button),
.cgd-delete-form > div:has(input[type="submit"]){
    grid-column:
        1 / -1;
}


.cgd-delete-form label:has(input[type="checkbox"]){
    display:flex;

    align-items:center;

    gap:8px;

    min-height:40px;

    margin:0;

    padding:
        9px
        10px;

    border:
        1px solid
        rgba(153,130,76,.20);

    border-radius:6px;

    color:#c7cbc7 !important;

    background:#090e0c;
}


/* ==========================================================
   DELETE BUTTONS
   ========================================================== */

.cgd-delete-form button,
.cgd-delete-form input[type="submit"]{
    display:inline-flex;

    align-items:center;
    justify-content:center;

    min-height:35px;

    padding:
        7px
        13px;

    border:
        1px solid
        rgba(207,79,61,.64) !important;

    border-radius:5px;

    color:#f3a47d !important;

    background:
        linear-gradient(
            180deg,
            #391c16,
            #180c09
        ) !important;

    font-size:8px;
    font-weight:900;

    cursor:pointer;
}


.cgd-delete-form button:hover,
.cgd-delete-form input[type="submit"]:hover{
    color:#ffc09b !important;

    border-color:
        rgba(234,102,76,.88) !important;
}


.cgd-delete-form a{
    display:inline-flex;

    align-items:center;
    justify-content:center;

    min-height:35px;

    padding:
        7px
        13px;

    border:
        1px solid
        rgba(204,157,45,.38);

    border-radius:5px;

    color:#e6b64c !important;

    background:
        linear-gradient(
            180deg,
            #292318,
            #14120d
        );

    font-size:8px;
    font-weight:900;

    text-decoration:none;
}


/* ==========================================================
   TABLE FALLBACK
   ========================================================== */

.cgd-real-panel table{
    width:100% !important;

    border-collapse:collapse !important;

    background:#070b09;
}


.cgd-real-panel th{
    padding:8px;

    color:#9d895c;

    background:#0c110f;

    text-align:left;

    font-size:7px;
}


.cgd-real-panel td{
    padding:8px;

    border-top:
        1px solid
        rgba(255,255,255,.05);

    color:#d2d7d3;

    font-size:8px;
}


/* ==========================================================
   RESPONSIVE
   ========================================================== */

@media(max-width:900px){

    .cgd-hero{
        grid-template-columns:
            1fr
            auto;
    }


    .cgd-hero-copy{
        grid-column:
            1 / -1;
    }


    .cgd-delete-form{
        grid-template-columns:1fr;
    }

}


@media(max-width:650px){

    .cgd-shell{
        padding:8px;
    }


    .cgd-hero{
        grid-template-columns:1fr;
    }


    .cgd-nav{
        width:100%;
    }

}



/* ==========================================================
   AUSTRALIA DELETE GROUP MODAL V1 START
   ========================================================== */

.cgd-modal-overlay{
    position:fixed;
    inset:0;

    z-index:999999;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:22px;

    background:
        rgba(0,0,0,.78);

    backdrop-filter:
        blur(4px);
}


.cgd-modal-overlay[hidden]{
    display:none !important;
}


.cgd-modal{
    width:min(
        560px,
        calc(100vw - 36px)
    );

    overflow:hidden;

    border:
        1px solid
        rgba(213,163,47,.58);

    border-radius:9px;

    background:
        linear-gradient(
            145deg,
            #161b18,
            #070a08
        );

    box-shadow:
        0 28px 80px
        rgba(0,0,0,.76),
        0 0 0 1px
        rgba(239,189,78,.04);
}


.cgd-modal-header{
    display:flex;

    align-items:center;

    gap:12px;

    padding:
        15px
        17px;

    border-bottom:
        1px solid
        rgba(208,159,44,.23);

    background:
        linear-gradient(
            180deg,
            #1d211c,
            #0d110e
        );
}


.cgd-modal-icon{
    width:38px;
    height:38px;

    flex:
        0 0 38px;

    display:flex;

    align-items:center;
    justify-content:center;

    border:
        1px solid
        rgba(207,76,58,.62);

    border-radius:7px;

    color:#ffab86;

    background:
        linear-gradient(
            145deg,
            #351914,
            #160b08
        );

    font-size:20px;
    font-weight:900;
}


.cgd-modal-heading{
    min-width:0;
}


.cgd-modal-kicker{
    margin-bottom:2px;

    color:#a3874c;

    font-size:7px;
    font-weight:900;

    letter-spacing:1.25px;
}


.cgd-modal-title{
    margin:0;

    color:#efbd4e;

    font-size:16px;
    font-weight:900;
}


.cgd-modal-body{
    padding:
        17px;
}


.cgd-modal-warning{
    margin:
        0
        0
        13px;

    padding:
        11px
        12px;

    border:
        1px solid
        rgba(198,76,59,.46);

    border-radius:6px;

    color:#e9c9c0;

    background:
        linear-gradient(
            145deg,
            rgba(58,20,16,.54),
            rgba(21,10,8,.84)
        );

    font-size:10px;
    line-height:1.55;
}


.cgd-modal-warning strong{
    color:#ffad8d;
}


.cgd-modal-note{
    margin:0;

    color:#9ca6a0;

    font-size:9px;
    line-height:1.55;
}


.cgd-modal-actions{
    display:flex;

    justify-content:flex-end;

    gap:8px;

    padding:
        12px
        17px
        16px;

    border-top:
        1px solid
        rgba(194,148,42,.17);
}


.cgd-modal-button{
    min-height:35px;

    display:inline-flex;

    align-items:center;
    justify-content:center;

    padding:
        7px
        14px;

    border-radius:5px;

    font-family:inherit;

    font-size:8px;
    font-weight:900;

    letter-spacing:.45px;

    cursor:pointer;
}


.cgd-modal-button.cancel{
    min-width:105px;

    border:
        1px solid
        rgba(151,145,126,.30);

    color:#bec4bf;

    background:
        linear-gradient(
            180deg,
            #1b1f1c,
            #0d100e
        );
}


.cgd-modal-button.delete{
    min-width:185px;

    border:
        1px solid
        rgba(216,83,62,.70);

    color:#ffb08c;

    background:
        linear-gradient(
            180deg,
            #3a1c15,
            #180c09
        );

    box-shadow:
        inset 0 1px 0
        rgba(255,177,135,.05);
}


.cgd-modal-button:hover{
    filter:
        brightness(1.13);
}


.cgd-modal-button:focus-visible{
    outline:
        2px solid
        rgba(239,189,78,.58);

    outline-offset:2px;
}


@media(max-width:600px){

    .cgd-modal-actions{
        flex-direction:column-reverse;
    }


    .cgd-modal-button{
        width:100%;
    }

}


/* ==========================================================
   AUSTRALIA DELETE GROUP MODAL V1 END
   ========================================================== */

</style>

</head>


<body>

<div class="cgd-shell">


    <section class="cgd-hero">

        <div class="cgd-hero-copy">

            <div class="cgd-kicker">
                <?php echo ag_grid_name_html(); ?> · GROUP MANAGEMENT
            </div>

            <h1>
                DELETE GROUP
            </h1>

            <p>
                Review the group records that will be removed before permanently deleting this local Grid group.
            </p>

        </div>


        <a
            class="cgd-nav"
            href="/Other/admin-group-manage-clean.php?id=<?=rawurlencode($returnGroupId)?>">
            BACK TO GROUP MANAGER
        </a>


        <a
            class="cgd-nav"
            href="/Other/admin-groups-clean.php">
            MANAGE GROUPS
        </a>

    </section>


    <section class="cgd-card">

<div class="cgd-real-panel">

    <div class="warning">
        <strong>PERMANENT GROUP DELETION</strong><br>
        This removes the local group, memberships, roles, invites and notices.
        Any avatar using this as its active group will be reset to no active group.
    </div>

    <?php if ($error !== ''): ?>
        <div class="ag-error"><?=ag_h($error)?></div>
    <?php endif; ?>

    <?php if (!$group): ?>

        <div class="actions">
            <a class="ag-button" href="<?=ag_h('/Other/admin-groups-clean.php')?>">
                BACK TO MANAGE GROUPS
            </a>
        </div>

    <?php elseif (!isLocalGroup($group)): ?>

        <div class="blocker">
            HG / cached groups are read-only and cannot be deleted locally.
        </div>

    <?php else: ?>

        <div class="info-grid">

            <div class="info-item">
                <span>GROUP</span>
                <strong><?=ag_h($groupName)?></strong>
            </div>

            <div class="info-item">
                <span>FOUNDER</span>
                <?= $founderName !== '' ? ag_h($founderName) : ag_h($group['FounderID']) ?>
            </div>

            <div class="info-item">
                <span>GROUP ID</span>
                <div class="uuid"><?=ag_h($groupId)?></div>
            </div>

        </div>

        <?php if ($externalRefs): ?>

            <h2>Deletion Blocked</h2>

            <div class="warning">
                Live records outside the normal group tables currently reference
                this GroupID. Resolve them before deleting this group.
            </div>

            <?php foreach ($externalRefs as $ref): ?>

                <div class="blocker">
                    <?=ag_h($ref['table'])?>.<?=ag_h($ref['column'])?>
                    &mdash; <?=ag_h($ref['count'])?> record(s)
                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <h2>Rows Scheduled for Removal</h2>

            <div class="ref-grid">

                <?php foreach ($counts as $table => $count): ?>

                    <div class="ref-card">
                        <span><?=ag_h($table)?></span>
                        <strong><?=ag_h($count)?></strong>
                    </div>

                <?php endforeach; ?>

                <div class="ref-card">
                    <span>ACTIVE GROUP REFERENCES TO CLEAR</span>
                    <strong><?=ag_h($activePrincipals)?></strong>
                </div>

            </div>

            <div class="warning">
                The database scan found no live external GroupID references.
                However, region land and rezzed objects can exist outside these
                Robust group tables. Check those before continuing.
            </div>

            <form class="cgd-delete-form" method="post" action="<?=ag_h('/Other/admin-group-delete-clean.php')?>">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?=ag_h($csrfToken)?>">

                <input
                    type="hidden"
                    name="group_id"
                    value="<?=ag_h($groupId)?>">

                <div class="field">
                    <label>
                        TYPE THE GROUP NAME EXACTLY:
                        <?=ag_h($groupName)?>
                    </label>

                    <input
                        type="text"
                        name="confirm_name"
                        autocomplete="off"
                        required>
                </div>

                <div class="field">
                    <label>TYPE DELETE GROUP</label>

                    <input
                        type="text"
                        name="confirm_phrase"
                        autocomplete="off"
                        required>
                </div>

                <label class="check">
                    <input
                        type="checkbox"
                        name="confirm_land"
                        value="1"
                        required>
                    I have checked this group does not own land or in-world
                    objects that I need to keep.
                </label>

                <label class="check">
                    <input
                        type="checkbox"
                        name="confirm_permanent"
                        value="1"
                        required>
                    I understand this permanently removes this local group and
                    its memberships, roles, invites and notices.
                </label>

                <div class="actions">

                    <button
                        class="delete-button"
                        type="submit"
                    >
                        PERMANENTLY DELETE GROUP
                    </button>

                    <a
                        class="ag-button"
                        href="<?=ag_h('/Other/admin-group-manage-clean.php')?>?id=<?=rawurlencode($groupId)?>"
                    >
                        CANCEL
                    </a>

                </div>

            </form>

        <?php endif; ?>

    <?php endif; ?>

</div>

    </section>


</div>


<script id="australia-delete-group-clean-layout-v1">

document.addEventListener(
    "DOMContentLoaded",
    function(){

        const panel =
            document.querySelector(
                ".cgd-real-panel"
            );


        if(!panel){
            return;
        }


        function smallestDivContaining(
            phrase
        ){

            const target =
                String(
                    phrase
                ).toUpperCase();


            const nodes =
                Array.from(
                    panel.querySelectorAll(
                        "*"
                    )
                );


            const match =
                nodes.find(
                    node => {

                        return (
                            String(
                                node.textContent ||
                                ""
                            )
                            .trim()
                            .toUpperCase() ===
                            target
                        );
                    }
                );


            if(!match){
                return null;
            }


            let current =
                match;


            while(
                current &&
                current !== panel
            ){

                if(
                    current.tagName ===
                    "DIV"
                ){
                    return current;
                }


                current =
                    current.parentElement;
            }


            return null;
        }


        /*
         * Permanent deletion warning.
         */

        const permanent =
            smallestDivContaining(
                "PERMANENT GROUP DELETION"
            );


        if(permanent){

            const box =
                permanent.parentElement &&
                permanent.parentElement.tagName === "DIV"
                    ?
                    permanent.parentElement
                    :
                    permanent;


            box.classList.add(
                "cgd-danger-banner"
            );
        }


        /*
         * Database/reference warning.
         */

        Array.from(
            panel.querySelectorAll(
                "div"
            )
        )
        .forEach(
            div => {

                const text =
                    String(
                        div.textContent ||
                        ""
                    )
                    .trim()
                    .toLowerCase();


                if(
                    text.startsWith(
                        "the database scan found"
                    )
                ){

                    div.classList.add(
                        "cgd-danger-banner"
                    );
                }
            }
        );


        /*
         * Removal heading.
         */

        Array.from(
            panel.querySelectorAll(
                "h1,h2,h3,h4"
            )
        )
        .forEach(
            heading => {

                if(
                    String(
                        heading.textContent ||
                        ""
                    )
                    .toLowerCase()
                    .includes(
                        "rows scheduled for removal"
                    )
                ){

                    heading.classList.add(
                        "cgd-removal-heading"
                    );
                }
            }
        );


        /*
         * Group identity boxes.
         */

        [
            "GROUP",
            "FOUNDER",
            "GROUP ID"
        ]
        .forEach(
            labelText => {

                const label =
                    Array.from(
                        panel.querySelectorAll(
                            "*"
                        )
                    )
                    .find(
                        node =>
                            String(
                                node.textContent ||
                                ""
                            )
                            .trim()
                            .toUpperCase() ===
                            labelText
                    );


                if(label){

                    const box =
                        label.closest(
                            "div"
                        );


                    if(box){

                        box.classList.add(
                            "cgd-info-box"
                        );
                    }
                }
            }
        );


        /*
         * Scheduled removal statistic cards.
         */

        [
            "os_groups_membership",
            "os_groups_roles",
            "os_groups_rolemembership",
            "os_groups_invites",
            "os_groups_notices",
            "ACTIVE GROUP REFERENCES TO CLEAR"
        ]
        .forEach(
            labelText => {

                const label =
                    Array.from(
                        panel.querySelectorAll(
                            "*"
                        )
                    )
                    .find(
                        node =>
                            String(
                                node.textContent ||
                                ""
                            )
                            .trim()
                            .toUpperCase() ===
                            labelText.toUpperCase()
                    );


                if(label){

                    const box =
                        label.closest(
                            "div"
                        );


                    if(box){

                        box.classList.add(
                            "cgd-stat-card"
                        );
                    }
                }
            }
        );

    }
);

</script>




<!-- AUSTRALIA DELETE GROUP MODAL V1 START -->

<div
    id="cgdDeleteOverlay"
    class="cgd-modal-overlay"
    hidden
    aria-hidden="true">

    <section
        class="cgd-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cgdDeleteModalTitle">

        <div class="cgd-modal-header">

            <div
                class="cgd-modal-icon"
                aria-hidden="true">
                !
            </div>


            <div class="cgd-modal-heading">

                <div class="cgd-modal-kicker">
                    <?php echo ag_grid_name_html(); ?> · GROUP MANAGEMENT
                </div>

                <h2
                    id="cgdDeleteModalTitle"
                    class="cgd-modal-title">
                    FINAL WARNING
                </h2>

            </div>

        </div>


        <div class="cgd-modal-body">

            <div class="cgd-modal-warning">

                <strong>
                    PERMANENT GROUP DELETION
                </strong>

                <br><br>

                This will permanently delete this local group,
                including its memberships, roles, role memberships,
                invites and notices.

                <br><br>

                This action cannot be undone.

            </div>


            <p class="cgd-modal-note">

                Your typed confirmation fields and safety checkboxes
                have already been validated. Choose
                <strong>PERMANENTLY DELETE GROUP</strong>
                only if you want the real deletion backend to continue.

            </p>

        </div>


        <div class="cgd-modal-actions">

            <button
                id="cgdDeleteCancel"
                class="cgd-modal-button cancel"
                type="button">
                CANCEL
            </button>


            <button
                id="cgdDeleteConfirm"
                class="cgd-modal-button delete"
                type="button">
                PERMANENTLY DELETE GROUP
            </button>

        </div>

    </section>

</div>

<!-- AUSTRALIA DELETE GROUP MODAL V1 END -->


<script id="australia-delete-group-modal-v1">

document.addEventListener(
    "DOMContentLoaded",
    function(){

        const form =
            document.querySelector(
                ".cgd-delete-form"
            );


        const overlay =
            document.getElementById(
                "cgdDeleteOverlay"
            );


        const confirmButton =
            document.getElementById(
                "cgdDeleteConfirm"
            );


        const cancelButton =
            document.getElementById(
                "cgdDeleteCancel"
            );


        if(
            !form ||
            !overlay ||
            !confirmButton ||
            !cancelButton
        ){
            return;
        }


        let approved =
            false;


        let pendingSubmitter =
            null;


        let previousFocus =
            null;


        function openModal(
            submitter
        ){

            pendingSubmitter =
                submitter ||
                null;


            previousFocus =
                document.activeElement;


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

                    cancelButton.focus();

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
                 * On the second pass, after our own modal
                 * confirmation, allow the REAL form POST.
                 */

                if(approved){

                    approved =
                        false;

                    return;
                }


                /*
                 * Browser validation has already happened
                 * before the submit event fires, so the modal
                 * only opens after required fields/checkboxes
                 * have passed validation.
                 */

                event.preventDefault();


                openModal(
                    event.submitter ||
                    null
                );

            }
        );


        confirmButton.addEventListener(
            "click",
            function(){

                approved =
                    true;


                closeModal();


                /*
                 * Preserve the original submit button and every
                 * real POST field, including CSRF and Group ID.
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