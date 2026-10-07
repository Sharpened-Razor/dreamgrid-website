<?php
declare(strict_types=1);

$siteHeaderKicker = "ACCOUNT";
$siteHeaderTitle = "AVATAR PROFILE";
$siteHeaderRole = "MEMBER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "BACK";
$siteHeaderLink = auProfileEsc($backUrl);
require_once __DIR__ . "/includes/site-header.php";
?>";

?>

/*
 * ============================================================
 * Grid - AVATAR PROFILE V1
 * READ ONLY
 * ============================================================
 */

require_once __DIR__ . '/user-profile-lib.php';


/*
 * Signed-in website viewer.
 */

$session =
    auProfileSession();


$viewerId =
    strtolower(
        trim(
            (string)$session['principalId']
        )
    );


$db =
    auProfileDb();


/*
 * Avatar whose profile is being viewed.
 */

$targetId =
    strtolower(
        trim(
            (string)(
                $_GET['id'] ??
                ''
            )
        )
    );


if (!auProfileIsUuid($targetId)) {

    http_response_code(400);

    exit(
        'Invalid Grid avatar ID.'
    );
}


/*
 * Optional originating group.
 * Allows BACK to return to the Group Profile.
 */

$fromGroup =
    strtolower(
        trim(
            (string)(
                $_GET['group'] ??
                ''
            )
        )
    );


if (
    $fromGroup !== ''
    &&
    !auProfileIsUuid($fromGroup)
) {

    $fromGroup = '';
}


$backUrl =
    $fromGroup !== ''
    ?
        '/Other/group-profile.php?id=' .
        rawurlencode(
            $fromGroup
        )
    :
        '/Other/FreshUserDashboardExact/user-dashboard.php';


/*
 * ============================================================
 * VIEWER NAME
 * ============================================================
 */

$viewerAccount =
    auProfileAccount(
        $db,
        $viewerId
    );


$viewerName =
    trim(
        (string)$viewerAccount['FirstName'] .
        ' ' .
        (string)$viewerAccount['LastName']
    );


/*
 * ============================================================
 * TARGET ACCOUNT
 * ============================================================
 */

$stmt =
    $db->prepare(
        '
        SELECT
            PrincipalID,
            FirstName,
            LastName,
            Created,
            UserTitle,
            active

        FROM useraccounts

        WHERE PrincipalID = ?

        LIMIT 1
        '
    );


$stmt->bind_param(
    's',
    $targetId
);


$stmt->execute();


$account =
    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();


if (!is_array($account)) {

    http_response_code(404);

    exit(
        'Grid avatar was not found.'
    );
}


$avatarName =
    trim(
        (string)$account['FirstName'] .
        ' ' .
        (string)$account['LastName']
    );


/*
 * ============================================================
 * REAL OPENSIM PROFILE
 * ============================================================
 */

$profile =
    auProfileGet(
        $db,
        $targetId
    );


$partner =
    auProfilePartner(
        $db,
        (string)$profile['profilePartner']
    );


/*
 * ============================================================
 * ACCOUNT AGE / BORN DATE
 * ============================================================
 */

$created =
    (int)(
        $account['Created'] ??
        0
    );


$bornText =
    $created > 0
    ?
        gmdate(
            'm/d/Y',
            $created
        )
    :
        'UNKNOWN';


if ($created > 0) {

    $ageDays =
        max(
            0,
            (int)floor(
                (time() - $created) /
                86400
            )
        );


    $ageText =
        $ageDays === 1
        ?
            '1 day old'
        :
            number_format($ageDays) .
            ' days old';

}
else {

    $ageText =
        'UNKNOWN';
}


/*
 * ============================================================
 * IMAGES
 * ============================================================
 */

$zeroUuid =
    '00000000-0000-0000-0000-000000000000';


$profileImage =
    strtolower(
        trim(
            (string)$profile['profileImage']
        )
    );


$firstImage =
    strtolower(
        trim(
            (string)$profile['profileFirstImage']
        )
    );


$hasProfileImage =
    auProfileIsUuid($profileImage)
    &&
    $profileImage !== $zeroUuid;


$hasFirstImage =
    auProfileIsUuid($firstImage)
    &&
    $firstImage !== $zeroUuid;


$profileImageUrl =
    $hasProfileImage
    ?
        '/Other/avatar-profile-image.php?' .
        http_build_query(
            [
                'user' =>
                    $targetId,

                'id' =>
                    $profileImage
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        )
    :
        '';


$firstImageUrl =
    $hasFirstImage
    ?
        '/Other/avatar-profile-image.php?' .
        http_build_query(
            [
                'user' =>
                    $targetId,

                'id' =>
                    $firstImage
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        )
    :
        '';


/*
 * ============================================================
 * GROUPS SHOWN IN THIS AVATAR'S PROFILE
 * ============================================================
 */

$groups = [];


$stmt =
    $db->prepare(
        '
        SELECT
            g.GroupID,
            g.Name,
            g.Charter,
            g.InsigniaID,

            m.SelectedRoleID,

            COALESCE(
                r.Title,
                ""
            ) AS SelectedTitle,

            CASE
                WHEN p.ActiveGroupID =
                    m.GroupID
                THEN 1
                ELSE 0
            END AS ActiveGroup

        FROM os_groups_membership AS m

        INNER JOIN os_groups_groups AS g
            ON g.GroupID =
                m.GroupID

        LEFT JOIN os_groups_roles AS r
            ON r.GroupID =
                m.GroupID
            AND r.RoleID =
                m.SelectedRoleID

        LEFT JOIN os_groups_principals AS p
            ON p.PrincipalID =
                m.PrincipalID

        WHERE
            m.PrincipalID = ?
            AND m.ListInProfile <> 0

        ORDER BY
            ActiveGroup DESC,
            g.Name ASC
        '
    );


$stmt->bind_param(
    's',
    $targetId
);


$stmt->execute();


$result =
    $stmt->get_result();


while (
    $row =
        $result->fetch_assoc()
) {

    $row['ActiveGroup'] =
        (int)(
            $row['ActiveGroup'] ??
            0
        ) === 1;


    $groups[] =
        $row;
}


$stmt->close();


/*
 * ============================================================
 * DISPLAY TEXT
 * ============================================================
 */

function auAvatarDisplayText(
    $value,
    string $empty = 'NOT SET'
): string
{
    $text =
        trim(
            (string)$value
        );

    return
        $text !== ''
        ?
            $text
        :
            $empty;
}


$profileUrl =
    trim(
        (string)$profile['profileURL']
    );


$profileUrlValid =
    false;


if (
    $profileUrl !== ''
    &&
    filter_var(
        $profileUrl,
        FILTER_VALIDATE_URL
    )
) {

    $scheme =
        strtolower(
            (string)parse_url(
                $profileUrl,
                PHP_URL_SCHEME
            )
        );


    $profileUrlValid =
        in_array(
            $scheme,
            [
                'http',
                'https'
            ],
            true
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

<title>
<?= auProfileEsc($avatarName) ?> - Grid
</title>

<link
    rel="stylesheet"
    href="/Other/australia-3d-theme.css">

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

    color:#e8edef;

    font-family:
        Arial,
        Helvetica,
        sans-serif;
}


/* AUSTRALIA DASHBOARD BACKGROUND START */

/*
 * EXACT FULL-PAGE BACKGROUND COPIED FROM user-dashboard.php
 */

body{

    min-height:100vh !important;

    background:

        linear-gradient(
            180deg,
            rgba(0,0,0,.42),
            rgba(0,0,0,.62)
        ),

        url(
            "/Other/custom/Branding/AUSTRALIA-BACKGROUND.png"
        )

        center center /
        cover
        fixed
        no-repeat !important;

}

/* AUSTRALIA DASHBOARD BACKGROUND END */


.avatar-wrap{
    width:
        min(
            1160px,
            calc(100% - 30px)
        );

    margin:
        20px
        auto
        50px;
}


.signed-in{
    margin-top:5px;

    color:#89979c;

    font-size:10px;
    font-weight:800;
}


.tabs{
    display:flex;

    gap:8px;

    margin-bottom:15px;
}


.tab{
    padding:
        10px
        18px;

    border:
        1px solid
        rgba(181,139,39,.34);

    border-radius:7px;

    background:
        rgba(7,11,13,.88);

    color:#909da2;

    cursor:pointer;

    font-size:10px;
    font-weight:900;
}


.tab.active{
    color:#edb842;

    border-color:
        rgba(224,171,44,.72);

    background:
        rgba(49,38,12,.82);
}


.tab-panel{
    display:none;
}


.tab-panel.active{
    display:block;
}


.profile-card{
    margin-bottom:17px;

    padding:20px;

    border:
        1px solid
        rgba(193,145,38,.38);

    border-radius:10px;

    background:
        linear-gradient(
            145deg,
            rgba(24,31,34,.95),
            rgba(5,9,11,.94)
        );

    box-shadow:
        0 8px 26px
        rgba(0,0,0,.43);
}


.profile-grid{
    display:grid;

    grid-template-columns:
        320px
        minmax(0,1fr);

    gap:25px;

    align-items:start;
}


.avatar-image{
    width:100%;

    overflow:hidden;

    border:
        1px solid
        rgba(219,168,48,.60);

    border-radius:9px;

    background:#020405;
}


.avatar-image img{
    display:block;

    width:100%;
    height:auto;

    object-fit:contain;
}


.no-image{
    display:flex;

    align-items:center;
    justify-content:center;

    aspect-ratio:1 / 1;

    color:#68767a;

    font-size:11px;
    font-weight:900;
}


.avatar-name{
    margin-top:13px;

    color:#edb842;

    font-size:24px;
    font-weight:900;
}


.partner{
    margin-top:7px;

    color:#9ba7ab;

    font-size:11px;
}


.section-title{
    margin:
        0
        0
        17px;

    color:#edb842;

    font-size:25px;
    font-weight:900;
}


.about{
    min-height:100px;

    padding:13px;

    margin-bottom:14px;

    border:
        1px solid
        rgba(145,159,164,.20);

    border-radius:7px;

    background:
        rgba(1,4,5,.47);

    color:#d2d9dc;

    white-space:pre-wrap;

    line-height:1.55;

    font-size:12px;
}


.label{
    margin-bottom:6px;

    color:#dba939;

    font-size:9px;
    font-weight:900;
}


.value-box{
    padding:
        11px
        12px;

    margin-bottom:12px;

    border:
        1px solid
        rgba(137,151,156,.18);

    border-radius:6px;

    background:
        rgba(3,6,8,.44);

    color:#cbd3d6;

    white-space:pre-wrap;

    line-height:1.5;

    font-size:11px;
}


.value-box a{
    color:#e5b344;

    overflow-wrap:anywhere;
}


.info-grid{
    display:grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(180px,1fr)
        );

    gap:10px;

    margin-top:16px;
}


.info{
    padding:12px;

    border:
        1px solid
        rgba(170,130,39,.24);

    border-radius:7px;

    background:
        rgba(3,6,8,.50);
}


.info-label{
    margin-bottom:6px;

    color:#8b999e;

    font-size:8px;
    font-weight:900;
}


.info-value{
    color:#e0e6e8;

    font-size:11px;
    font-weight:800;
}


.groups-title{
    margin:
        22px
        0
        10px;

    color:#e3af3b;

    font-size:13px;
    font-weight:900;
}


.group-entry{
    position:relative;

    min-height:70px;

    margin-bottom:9px;

    padding:9px;

    overflow:hidden;

    border:
        1px solid
        rgba(189,145,42,.28);

    border-radius:7px;

    background:
        rgba(3,7,9,.58);
}


.group-entry.active{
    border-color:
        rgba(229,174,48,.68);

    box-shadow:
        inset 3px 0 0
        #dca62d;
}


.group-insignia{
    float:left;

    width:54px;
    height:54px;

    margin:
        0
        11px
        3px
        0;

    overflow:hidden;

    border:
        1px solid
        rgba(218,166,47,.57);

    border-radius:6px;

    background:#020405;
}


.group-insignia img{
    width:100%;
    height:100%;

    object-fit:cover;
}


.group-name{
    color:#edf1f2;

    font-size:11px;
    font-weight:900;
}


.group-title{
    margin-top:4px;

    color:#8f9ca0;

    font-size:9px;
}


.active-group{
    margin-top:5px;

    color:#e6b13d;

    font-size:8px;
    font-weight:900;
}


.first-life-grid{
    display:grid;

    grid-template-columns:
        320px
        minmax(0,1fr);

    gap:25px;
}


.first-life-text{
    min-height:180px;

    padding:14px;

    border:
        1px solid
        rgba(142,156,162,.20);

    border-radius:7px;

    background:
        rgba(2,5,7,.52);

    color:#d1d9dc;

    white-space:pre-wrap;

    line-height:1.55;

    font-size:12px;
}


@media(max-width:760px){

    .profile-grid,
    .first-life-grid{
        grid-template-columns:1fr;
    }


    .tabs{
        flex-wrap:wrap;
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

</head>


<body>


<main class="avatar-wrap">





<nav class="tabs">

<button
    type="button"
    class="tab active"
    data-avatar-tab="profile">

PROFILE

</button>


<button
    type="button"
    class="tab"
    data-avatar-tab="firstlife">

FIRST LIFE

</button>

</nav>


<section
    id="avatar-tab-profile"
    class="tab-panel active">


<div class="profile-card">

<div class="profile-grid">


<div>

<div class="avatar-image">

<?php if ($hasProfileImage): ?>

<img
    src="<?= auProfileEsc($profileImageUrl) ?>"
    alt="<?= auProfileEsc($avatarName) ?> profile picture">

<?php else: ?>

<div class="no-image">
NO PROFILE PICTURE
</div>

<?php endif; ?>

</div>


<div class="avatar-name">
<?= auProfileEsc($avatarName) ?>
</div>


<div class="partner">
PARTNER:
<?= auProfileEsc($partner) ?>
</div>


<div class="info-grid">


<div class="info">

<div class="info-label">
BORN
</div>

<div class="info-value">
<?= auProfileEsc($bornText) ?>
</div>

</div>


<div class="info">

<div class="info-label">
ACCOUNT AGE
</div>

<div class="info-value">
<?= auProfileEsc($ageText) ?>
</div>

</div>


<div class="info">

<div class="info-label">
ACCOUNT
</div>

<div class="info-value">
<?= auProfileEsc(
    auAvatarDisplayText(
        $account['UserTitle'],
        'LOCAL USER'
    )
) ?>
</div>

</div>


</div>


<?php if ($groups): ?>

<div class="groups-title">
GROUPS
</div>


<?php foreach ($groups as $group): ?>

<?php

$groupInsignia =
    strtolower(
        trim(
            (string)(
                $group['InsigniaID'] ??
                ''
            )
        )
    );


$hasGroupInsignia =
    auProfileIsUuid(
        $groupInsignia
    )
    &&
    $groupInsignia !==
        $zeroUuid;


$groupImageUrl =
    $hasGroupInsignia
    ?
        '/Other/avatar-profile-image.php?' .
        http_build_query(
            [
                'user' =>
                    $targetId,

                'id' =>
                    $groupInsignia
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        )
    :
        '';


$selectedTitle =
    trim(
        (string)(
            $group['SelectedTitle'] ??
            ''
        )
    );


if ($selectedTitle === '') {
    $selectedTitle = 'Member';
}

?>


<div class="group-entry<?= $group['ActiveGroup'] ? ' active' : '' ?>">


<?php if ($hasGroupInsignia): ?>

<div class="group-insignia">

<img
    src="<?= auProfileEsc($groupImageUrl) ?>"
    alt="Group insignia">

</div>

<?php endif; ?>


<div class="group-name">
<?= auProfileEsc((string)$group['Name']) ?>
</div>


<div class="group-title">
<?= auProfileEsc($selectedTitle) ?>
</div>


<?php if ($group['ActiveGroup']): ?>

<div class="active-group">
ACTIVE GROUP
</div>

<?php endif; ?>


</div>


<?php endforeach; ?>

<?php endif; ?>


</div>


<div>

<h1 class="section-title">
PROFILE
</h1>


<div class="label">
ABOUT
</div>

<div class="about"><?= auProfileEsc(
    auAvatarDisplayText(
        $profile['profileAboutText']
    )
) ?></div>


<div class="label">
PROFILE WEB URL
</div>

<div class="value-box">

<?php if ($profileUrlValid): ?>

<a
    href="<?= auProfileEsc($profileUrl) ?>"
    target="_blank"
    rel="noopener noreferrer">

<?= auProfileEsc($profileUrl) ?>

</a>

<?php else: ?>

<?= auProfileEsc(
    auAvatarDisplayText(
        $profileUrl
    )
) ?>

<?php endif; ?>

</div>


<div class="label">
LANGUAGES
</div>

<div class="value-box"><?= auProfileEsc(
    auAvatarDisplayText(
        $profile['profileLanguages']
    )
) ?></div>


<div class="label">
I WANT TO
</div>

<div class="value-box"><?= auProfileEsc(
    auAvatarDisplayText(
        $profile['profileWantToText']
    )
) ?></div>


<div class="label">
SKILLS
</div>

<div class="value-box"><?= auProfileEsc(
    auAvatarDisplayText(
        $profile['profileSkillsText']
    )
) ?></div>


</div>

</div>

</div>

</section>


<section
    id="avatar-tab-firstlife"
    class="tab-panel">


<div class="profile-card">


<h2 class="section-title">
FIRST LIFE
</h2>


<div class="first-life-grid">


<div class="avatar-image">

<?php if ($hasFirstImage): ?>

<img
    src="<?= auProfileEsc($firstImageUrl) ?>"
    alt="<?= auProfileEsc($avatarName) ?> First Life picture">

<?php else: ?>

<div class="no-image">
NO FIRST LIFE PICTURE
</div>

<?php endif; ?>

</div>


<div>

<div class="label">
FIRST LIFE ABOUT
</div>

<div class="first-life-text"><?= auProfileEsc(
    auAvatarDisplayText(
        $profile['profileFirstText']
    )
) ?></div>

</div>


</div>

</div>

</section>


</main>


<script>

(function(){

    const buttons =
        document.querySelectorAll(
            "[data-avatar-tab]"
        );


    const panels =
        document.querySelectorAll(
            ".tab-panel"
        );


    buttons.forEach(
        function(button){

            button.addEventListener(
                "click",
                function(){

                    const name =
                        button.dataset.avatarTab;


                    buttons.forEach(
                        function(item){

                            item.classList.remove(
                                "active"
                            );
                        }
                    );


                    panels.forEach(
                        function(item){

                            item.classList.remove(
                                "active"
                            );
                        }
                    );


                    button.classList.add(
                        "active"
                    );


                    const target =
                        document.getElementById(
                            "avatar-tab-" +
                            name
                        );


                    if(target){

                        target.classList.add(
                            "active"
                        );
                    }
                }
            );
        }
    );

})();

</script>


<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>






