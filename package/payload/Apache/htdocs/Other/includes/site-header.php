<?php

if(!isset($siteHeaderKicker)){
    $siteHeaderKicker = "ADMINISTRATION";
}

if(!isset($siteHeaderTitle)){
    $siteHeaderTitle = "ADMIN DASHBOARD";
}

if(!isset($siteHeaderRole)){
    $siteHeaderRole =
        (($level ?? 250) >= 200)
            ? "GRID OWNER"
            : "MEMBER";
}

if(!isset($siteHeaderLevel)){
    $siteHeaderLevel = $level ?? 250;
}

if(!isset($siteHeaderButton)){
    $siteHeaderButton = "ADMIN HOME";
}

if(!isset($siteHeaderLink)){
    $siteHeaderLink = "/Other/admin-home.php";
}

$siteHeaderAvatar =
    $avatar ??
    ($_SESSION['avatar'] ?? "Avatar");



/* ============================================================
   GLOBAL ACCOUNT ROLE HIERARCHY V2

   UserLevel 250+ : GRID OWNER
   Other admins   : ADMIN
   Normal account : USER
   ============================================================ */

if (isset($session)) {

    if (function_exists('ag_user_level')) {
        $siteHeaderLevel = (int) ag_user_level($session);
    }

    if ((int)($siteHeaderLevel ?? 0) >= 250) {

        $siteHeaderRole = "GRID OWNER";

    } elseif (
        function_exists('ag_is_admin')
        && ag_is_admin($session)
    ) {

        $siteHeaderRole = "ADMIN";

    } else {

        $siteHeaderRole = "USER";
    }
}

/* END GLOBAL ACCOUNT ROLE HIERARCHY V2 */
?>

<style id="australia-master-dashboard-header">

.dashboard-header{

    position:relative;

    overflow:hidden;

    display:flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:20px;

    padding:
        25px 27px;

    border:
        1px solid
        rgba(222,167,52,.40);

    border-radius:
        15px;

    background:
        linear-gradient(
            145deg,
            rgba(18,26,31,.98),
            rgba(6,10,13,.98)
        );

    box-shadow:
        0 22px 60px
        rgba(0,0,0,.45);

}


.dashboard-header:before{

    content:"";

    position:absolute;

    inset:0;

    pointer-events:none;

    background:
        radial-gradient(
            circle at 20% 0,
            rgba(224,169,53,.12),
            transparent 45%
        );

}


.dashboard-heading,
.dashboard-actions{

    position:relative;

    z-index:1;

}


.dashboard-kicker{

    color:#dda735;

    font-size:9px;

    font-weight:900;

    letter-spacing:.22em;

}


.dashboard-heading h1{

    margin:
        7px 0 5px;

    color:#fff;

    font-size:
        31px;

    line-height:1;

}


.dashboard-avatar{

    color:#9faab0;

    font-size:12px;

}


.dashboard-avatar strong{

    color:#fff;

}


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


.dashboard-actions{

    position:absolute !important;

    right:27px !important;

    top:50% !important;

    transform:translateY(-50%) !important;

    display:flex;

    gap:9px;

    flex-wrap:wrap;

    align-items:center;

}


.top-button{

    min-height:41px;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    padding:
        9px 15px;

    border-radius:
        8px;

    text-decoration:none;

    font-size:10px;

    font-weight:900;

    letter-spacing:.055em;

}


.top-button.home{

    border:
        1px solid
        #e0aa36;

    background:
        linear-gradient(
            #ffc94c,
            #d78e10
        );

    color:#171109;

}


.top-button.logout{

    border:
        1px solid
        rgba(210,86,93,.42);

    background:
        rgba(128,29,35,.22);

    color:#ffc7ca;

}


@media(max-width:650px){

    .dashboard-header{
        display:block;
    }

    .dashboard-actions{
        margin-top:16px;
    }

}
</style>

<header class="dashboard-header">

<div class="dashboard-heading">

    <div class="dashboard-kicker">
        <?=htmlspecialchars(
            $siteHeaderKicker,
            ENT_QUOTES,
            'UTF-8'
        )?>
    </div>

    <h1>
        <?=htmlspecialchars(
            $siteHeaderTitle,
            ENT_QUOTES,
            'UTF-8'
        )?>
    </h1>

    <div class="dashboard-avatar">

        Welcome,

        <strong>
            <?=htmlspecialchars(
                $siteHeaderAvatar,
                ENT_QUOTES,
                'UTF-8'
            )?>
        </strong>

    </div>

</div>


<div class="php-level">

    <strong>
        <?=htmlspecialchars(
            $siteHeaderRole,
            ENT_QUOTES,
            'UTF-8'
        )?>
    </strong>

    USER LEVEL
    <?=htmlspecialchars(
        (string)$siteHeaderLevel,
        ENT_QUOTES,
        'UTF-8'
    )?>

</div>


<div class="dashboard-actions">

    <a
        data-ag-native-nav="1"
        data-ag-header-button="1"
        href="<?=htmlspecialchars(
            $siteHeaderLink,
            ENT_QUOTES,
            'UTF-8'
        )?>"
        class="top-button home"
    >
        <?=htmlspecialchars(
            $siteHeaderButton,
            ENT_QUOTES,
            'UTF-8'
        )?>
    </a>

</div>

</header>



