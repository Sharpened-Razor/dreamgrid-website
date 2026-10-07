<?php

require_once __DIR__ . '/core/bootstrap.php';

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
 * DEFAULT VALUES
 * ------------------------------------------------------------
 */

$firstName =
    '';

$lastName =
    '';

$email =
    '';

$createdDisplay =
    'Unavailable';

$statusDisplay =
    'ACTIVE';

$dbWarning =
    '';


/*
 * ------------------------------------------------------------
 * READ THIS AVATAR'S OWN ROBUST ACCOUNT
 * ------------------------------------------------------------
 */

try {

    require __DIR__ . '/../../MetroMap/includes/config.php';


    $con =
        @mysqli_connect(
            $CONF_db_server,
            $CONF_db_user,
            $CONF_db_pass,
            $CONF_db_database,
            (int)$CONF_db_port
        );


    if ($con) {

        @mysqli_set_charset(
            $con,
            'utf8mb4'
        );


        $sql =
            'SELECT ' .
            'PrincipalID, FirstName, LastName, Email, Created, UserLevel ' .
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


            mysqli_stmt_bind_result(
                $stmt,
                $dbPrincipalId,
                $dbFirstName,
                $dbLastName,
                $dbEmail,
                $dbCreated,
                $dbUserLevel
            );


            if (
                mysqli_stmt_fetch(
                    $stmt
                )
            ) {

                $firstName =
                    trim(
                        (string)$dbFirstName
                    );


                $lastName =
                    trim(
                        (string)$dbLastName
                    );


                $email =
                    trim(
                        (string)$dbEmail
                    );


                $createdNumber =
                    (int)$dbCreated;


                if ($createdNumber > 0) {

                    $createdDisplay =
                        date(
                            'j M Y',
                            $createdNumber
                        );

                }


                if (
                    (int)$dbUserLevel < 0
                ) {

                    $statusDisplay =
                        'DISABLED';

                }

            }
            else {

                $dbWarning =
                    'Your account record could not be found.';

            }


            mysqli_stmt_close(
                $stmt
            );

        }
        else {

            $dbWarning =
                'Account details are temporarily unavailable.';

        }


        mysqli_close(
            $con
        );

    }
    else {

        $dbWarning =
            'Account details are temporarily unavailable.';

    }

}
catch (Throwable $e) {

    $dbWarning =
        'Account details are temporarily unavailable.';

}


/*
 * ------------------------------------------------------------
 * FALLBACK AVATAR NAME
 * ------------------------------------------------------------
 */

$avatar =
    trim(
        $firstName .
        ' ' .
        $lastName
    );


if ($avatar === '') {

    $avatar =
        $sessionAvatar;

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
Grid - My Account
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
            rgba(0,0,0,.45),
            rgba(0,0,0,.64)
        ),

        url(
            "/Other/custom/Branding/AUSTRALIA-BACKGROUND.png"
        )

        center center /
        cover
        fixed
        no-repeat;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    color:#edf2f4;

}


.account-shell{

    width:
        min(
            1050px,
            calc(100% - 40px)
        );

    margin:
        45px auto 80px;

    padding:28px;

}


.account-header{

    padding:
        28px 31px;

    margin-bottom:20px;

}


.account-kicker{

    color:#efb83d;

    font-size:10px;

    font-weight:900;

    letter-spacing:.20em;

}


.account-header h1{

    margin:
        8px 0 7px;

    font-size:
        38px;

}


.account-header p{

    margin:0;

    color:#b8c2c7;

    font-size:14px;

}


.account-card{

    padding:
        27px;

}


.account-grid{

    display:grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap:15px;

}


.account-detail{

    padding:
        18px 20px;

    border:
        1px solid
        rgba(231,172,47,.30);

    border-radius:
        10px;

    background:

        linear-gradient(
            145deg,
            rgba(30,38,42,.96),
            rgba(7,11,13,.98)
        );

    box-shadow:

        inset 0 1px 0
        rgba(255,255,255,.10),

        inset 0 -3px 6px
        rgba(0,0,0,.70),

        0 5px 12px
        rgba(0,0,0,.34);

}


.account-label{

    color:#eeb43a;

    font-size:10px;

    font-weight:900;

    letter-spacing:.08em;

    margin-bottom:7px;

}


.account-value{

    color:#f2f4f5;

    font-size:16px;

    font-weight:700;

    overflow-wrap:anywhere;

}


.account-status{

    color:#7fe6a2;

}


.account-warning{

    margin-top:18px;

    padding:
        14px 16px;

    border:
        1px solid
        rgba(238,175,48,.27);

    border-radius:
        9px;

    background:
        rgba(238,175,48,.07);

    color:#d8c493;

    font-size:12px;

}


.account-actions{

    display:flex;

    justify-content:center;

    gap:12px;

    margin-top:25px;

    padding-top:23px;

    border-top:
        1px solid
        rgba(255,255,255,.09);

}


.account-action{

    min-width:
        170px;

    min-height:
        46px;

    display:inline-flex;

    justify-content:center;

    align-items:center;

    padding:
        10px 18px;

    text-decoration:none;

}


.account-action.disabled{

    opacity:.43;

    cursor:default;

    pointer-events:none;

}


@media(
    max-width:700px
){

    .account-grid{

        grid-template-columns:
            1fr;

    }


    .account-shell{

        width:
            calc(100% - 24px);

        margin:
            20px auto 50px;

        padding:16px;

    }


    .account-header h1{

        font-size:30px;

    }


    .account-actions{

        flex-direction:column;

    }


    .account-action{

        width:100%;

    }

}


/* ==========================================================
   AUSTRALIA USER BUTTON NO TEXT SHADOW V1
   ========================================================== */

button,
input[type="button"],
input[type="submit"],
input[type="reset"],
a[class*="button"],
a[class*="btn"],
a[class*="back"],
a[class*="logout"] {
    text-shadow: none !important;
}

/* END AUSTRALIA USER BUTTON NO TEXT SHADOW V1 */

</style>


<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">

<style>
/* MY ACCOUNT HEADER BACK BUTTON V1 */

.account-header{
    position:relative !important;
    padding-right:290px !important;
}

.account-header .account-header-back{
    position:absolute !important;
    top:28px !important;
    right:31px !important;
    z-index:20 !important;
    margin:0 !important;
}

/*
 * Bottom row now contains EDIT ACCOUNT only,
 * so keep that button centred.
 */
.account-actions{
    justify-content:center !important;
}

@media (max-width:760px){

    .account-header{
        padding-right:31px !important;
        padding-top:82px !important;
    }

    .account-header .account-header-back{
        top:22px !important;
        right:31px !important;
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


<main class="account-shell panel">



<section class="account-header header">

<!-- MY ACCOUNT HEADER BACK BUTTON V1 -->
<a
    class="button australia-back-dashboard ag-return-button account-header-back"
    href="/Other/dashboard-return.php<?= !empty($_GET['sid']) ? '?sid=' . rawurlencode((string)$_GET['sid']) : '' ?>"
>
    BACK TO DASHBOARD
</a>


<div class="account-kicker">
    <?=htmlspecialchars($agRoleLabel, ENT_QUOTES, 'UTF-8')?> AREA
</div>


<h1>
    MY ACCOUNT
</h1>
<div class="ag-account-role-box">
    <strong><?=htmlspecialchars($agRoleLabel, ENT_QUOTES, 'UTF-8')?></strong>
    <span>USER LEVEL <?=htmlspecialchars((string)$agRoleLevel, ENT_QUOTES, 'UTF-8')?></span>
</div>



<p>
    Your Grid account information.
</p>


</section>



<section class="account-card">


<div class="account-grid">


<div class="account-detail">

    <div class="account-label">
        AVATAR NAME
    </div>

    <div class="account-value">
        <?=htmlspecialchars(
            $avatar,
            ENT_QUOTES,
            'UTF-8'
        )?>
    </div>

</div>



<div class="account-detail">

    <div class="account-label">
        ACCOUNT STATUS
    </div>

    <div class="account-value account-status">
        <?=htmlspecialchars(
            $statusDisplay,
            ENT_QUOTES,
            'UTF-8'
        )?>
    </div>

</div>



<div class="account-detail">

    <div class="account-label">
        EMAIL
    </div>

    <div class="account-value">

        <?php if ($email !== ''): ?>

            <?=htmlspecialchars(
                $email,
                ENT_QUOTES,
                'UTF-8'
            )?>

        <?php else: ?>

            Not supplied

        <?php endif; ?>

    </div>

</div>



<div class="account-detail">

    <div class="account-label">
        MEMBER SINCE
    </div>

    <div class="account-value">
        <?=htmlspecialchars(
            $createdDisplay,
            ENT_QUOTES,
            'UTF-8'
        )?>
    </div>

</div>


</div>


<?php if ($dbWarning !== ''): ?>

<div class="account-warning">

    <?=htmlspecialchars(
        $dbWarning,
        ENT_QUOTES,
        'UTF-8'
    )?>

</div>

<?php endif; ?>


<!-- AUSTRALIA MY ACCOUNT UPDATE NOTICE V1 -->

<?php if (($_GET['updated'] ?? '') === '1'): ?>

<div
    class="account-warning"
    style="
        border-color:rgba(65,205,112,.40);
        background:rgba(28,120,63,.16);
        color:#9ff0b6;
    ">

    ACCOUNT UPDATED SUCCESSFULLY

</div>

<?php endif; ?>


<div class="account-actions">





<a
    class="button account-action button-primary"
    href="/Other/user-account-edit.php">

    EDIT ACCOUNT

</a>


</div>


</section>


</main>


<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>



