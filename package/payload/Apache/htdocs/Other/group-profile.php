<?php
declare(strict_types=1);

$siteHeaderKicker = "ACCOUNT";
$siteHeaderTitle = "GROUP PROFILE";
$siteHeaderRole = "MEMBER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "BACK";
$siteHeaderLink = "/Other/user-profile.php";
require_once __DIR__ . "/includes/site-header.php";
?>

/*
 * ============================================================
 * Grid - GROUP PROFILE V1
 * ============================================================
 */

require_once __DIR__ . '/user-profile-lib.php';


$session =
    auProfileSession();


$principalId =
    strtolower(
        trim(
            (string)$session['principalId']
        )
    );


$db =
    auProfileDb();


$groupId =
    strtolower(
        trim(
            (string)(
                $_GET['id'] ??
                ''
            )
        )
    );


if (!auProfileIsUuid($groupId)) {

    http_response_code(400);

    exit(
        'Invalid Grid Group ID.'
    );
}


/*
 * ============================================================
 * SIGNED-IN AVATAR
 * ============================================================
 */

$avatarName =
    'SIGNED-IN AVATAR';


$stmt =
    $db->prepare(
        '
        SELECT
            FirstName,
            LastName
        FROM useraccounts
        WHERE PrincipalID = ?
        LIMIT 1
        '
    );


$stmt->bind_param(
    's',
    $principalId
);


$stmt->execute();


$avatarRow =
    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();


if (is_array($avatarRow)) {

    $name =
        trim(
            (string)$avatarRow['FirstName'] .
            ' ' .
            (string)$avatarRow['LastName']
        );


    if ($name !== '') {
        $avatarName = $name;
    }
}


/*
 * ============================================================
 * GROUP
 *
 * The signed-in avatar must actually belong to the group and
 * the group must be one they have elected to show in profile.
 * ============================================================
 */

$stmt =
    $db->prepare(
        '
        SELECT
            g.GroupID,
            g.Name,
            g.Charter,
            g.InsigniaID,
            g.FounderID,
            g.MembershipFee,
            g.OpenEnrollment,
            g.ShowInList,
            g.AllowPublish,
            g.MaturePublish,
            g.OwnerRoleID,

            m.SelectedRoleID,
            m.ListInProfile,

            COALESCE(
                r.Title,
                ""
            ) AS SelectedTitle,

            CASE
                WHEN p.ActiveGroupID = g.GroupID
                THEN 1
                ELSE 0
            END AS ActiveGroup

        FROM os_groups_groups AS g

        INNER JOIN os_groups_membership AS m
            ON m.GroupID = g.GroupID
            AND m.PrincipalID = ?

        LEFT JOIN os_groups_roles AS r
            ON r.GroupID = g.GroupID
            AND r.RoleID = m.SelectedRoleID

        LEFT JOIN os_groups_principals AS p
            ON p.PrincipalID = m.PrincipalID

        WHERE
            g.GroupID = ?
            AND m.ListInProfile <> 0

        LIMIT 1
        '
    );


$stmt->bind_param(
    'ss',
    $principalId,
    $groupId
);


$stmt->execute();


$group =
    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();


if (!is_array($group)) {

    http_response_code(404);

    exit(
        'This group is not available from your Grid profile.'
    );
}


/*
 * ============================================================
 * FOUNDER
 * ============================================================
 */

$founderId =
    strtolower(
        trim(
            (string)$group['FounderID']
        )
    );


$founderName =
    'UNKNOWN';


if (
    auProfileIsUuid($founderId)
    &&
    $founderId !==
        '00000000-0000-0000-0000-000000000000'
) {

    $stmt =
        $db->prepare(
            '
            SELECT
                FirstName,
                LastName
            FROM useraccounts
            WHERE PrincipalID = ?
            LIMIT 1
            '
        );


    $stmt->bind_param(
        's',
        $founderId
    );


    $stmt->execute();


    $founder =
        $stmt
            ->get_result()
            ->fetch_assoc();


    $stmt->close();


    if (is_array($founder)) {

        $founderName =
            trim(
                (string)$founder['FirstName'] .
                ' ' .
                (string)$founder['LastName']
            );


        if ($founderName === '') {
            $founderName = $founderId;
        }

    } else {

        $founderName =
            $founderId;
    }

} else {

    $founderName =
        'SYSTEM / IMPORTED GROUP';
}


/*
 * ============================================================
 * MEMBER COUNT
 * ============================================================
 */

$stmt =
    $db->prepare(
        '
        SELECT
            COUNT(*)
        FROM os_groups_membership
        WHERE GroupID = ?
        '
    );


$stmt->bind_param(
    's',
    $groupId
);


$stmt->execute();


$countRow =
    $stmt
        ->get_result()
        ->fetch_row();


$stmt->close();


$memberCount =
    (int)(
        $countRow[0] ??
        0
    );


/*
 * ============================================================
 * OWNER ROLE
 * ============================================================
 */

$ownerRoleId =
    strtolower(
        trim(
            (string)$group['OwnerRoleID']
        )
    );


$ownerRoleName =
    'Owners';


$ownerRoleTitle =
    'Group Owner';


if (
    auProfileIsUuid($ownerRoleId)
    &&
    $ownerRoleId !==
        '00000000-0000-0000-0000-000000000000'
) {

    $stmt =
        $db->prepare(
            '
            SELECT
                Name,
                Title
            FROM os_groups_roles
            WHERE
                GroupID = ?
                AND RoleID = ?
            LIMIT 1
            '
        );


    $stmt->bind_param(
        'ss',
        $groupId,
        $ownerRoleId
    );


    $stmt->execute();


    $ownerRole =
        $stmt
            ->get_result()
            ->fetch_assoc();


    $stmt->close();


    if (is_array($ownerRole)) {

        if (
            trim(
                (string)$ownerRole['Name']
            ) !== ''
        ) {

            $ownerRoleName =
                trim(
                    (string)$ownerRole['Name']
                );
        }


        if (
            trim(
                (string)$ownerRole['Title']
            ) !== ''
        ) {

            $ownerRoleTitle =
                trim(
                    (string)$ownerRole['Title']
                );
        }
    }
}


/*
 * ============================================================
 * CURRENT OWNER MEMBERS
 * ============================================================
 */

$owners =
    [];


if (
    auProfileIsUuid($ownerRoleId)
    &&
    $ownerRoleId !==
        '00000000-0000-0000-0000-000000000000'
) {

    $stmt =
        $db->prepare(
            '
            SELECT
                rm.PrincipalID,

                TRIM(
                    CONCAT(
                        COALESCE(
                            ua.FirstName,
                            ""
                        ),
                        " ",
                        COALESCE(
                            ua.LastName,
                            ""
                        )
                    )
                ) AS AvatarName

            FROM os_groups_rolemembership AS rm

            LEFT JOIN useraccounts AS ua
                ON ua.PrincipalID =
                    rm.PrincipalID

            WHERE
                rm.GroupID = ?
                AND rm.RoleID = ?

            ORDER BY
                ua.FirstName,
                ua.LastName,
                rm.PrincipalID
            '
        );


    $stmt->bind_param(
        'ss',
        $groupId,
        $ownerRoleId
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    while (
        $row =
            $result->fetch_assoc()
    ) {

        $ownerName =
            trim(
                (string)(
                    $row['AvatarName'] ??
                    ''
                )
            );


        if ($ownerName === '') {

            $ownerName =
                (string)$row['PrincipalID'];
        }


        $owners[] =
            $ownerName;
    }


    $stmt->close();
}


$ownersText =
    $owners
    ?
        implode(
            ', ',
            $owners
        )
    :
        'NOT RECORDED';


/*
 * ============================================================
 * ROLES
 * ============================================================
 */

$roles =
    [];


$stmt =
    $db->prepare(
        '
        SELECT
            RoleID,
            Name,
            Description,
            Title
        FROM os_groups_roles
        WHERE GroupID = ?
        ORDER BY
            Name ASC
        '
    );


$stmt->bind_param(
    's',
    $groupId
);


$stmt->execute();


$result =
    $stmt->get_result();


while (
    $row =
        $result->fetch_assoc()
) {

    $roles[] =
        $row;
}


$stmt->close();


/*
 * ============================================================
 * MEMBERS
 * ============================================================
 */

$members =
    [];


$stmt =
    $db->prepare(
        '
        SELECT
            m.PrincipalID,
            m.SelectedRoleID,

            TRIM(
                CONCAT(
                    COALESCE(
                        ua.FirstName,
                        ""
                    ),
                    " ",
                    COALESCE(
                        ua.LastName,
                        ""
                    )
                )
            ) AS AvatarName,

            COALESCE(
                NULLIF(
                    r.Title,
                    ""
                ),
                "Member"
            ) AS MemberTitle,

            CASE
                WHEN p.ActiveGroupID =
                    m.GroupID
                THEN 1
                ELSE 0
            END AS ActiveGroup

        FROM os_groups_membership AS m

        LEFT JOIN useraccounts AS ua
            ON ua.PrincipalID =
                m.PrincipalID

        LEFT JOIN os_groups_roles AS r
            ON r.GroupID =
                m.GroupID
            AND r.RoleID =
                m.SelectedRoleID

        LEFT JOIN os_groups_principals AS p
            ON p.PrincipalID =
                m.PrincipalID

        WHERE
            m.GroupID = ?

        ORDER BY
            ua.FirstName ASC,
            ua.LastName ASC,
            m.PrincipalID ASC
        '
    );


$stmt->bind_param(
    's',
    $groupId
);


$stmt->execute();


$result =
    $stmt->get_result();


while (
    $row =
        $result->fetch_assoc()
) {

    $memberName =
        trim(
            (string)(
                $row['AvatarName'] ??
                ''
            )
        );


    if ($memberName === '') {

        $memberName =
            (string)$row['PrincipalID'];
    }


    $row['AvatarName'] =
        $memberName;


    $row['ActiveGroup'] =
        (int)(
            $row['ActiveGroup'] ??
            0
        ) === 1;


    $members[] =
        $row;
}


$stmt->close();


/*
 * ============================================================
 * DISPLAY VALUES
 * ============================================================
 */

$insigniaId =
    strtolower(
        trim(
            (string)$group['InsigniaID']
        )
    );


$hasInsignia =
    auProfileIsUuid($insigniaId)
    &&
    $insigniaId !==
        '00000000-0000-0000-0000-000000000000';


$insigniaUrl =
    $hasInsignia
    ?
        '/Other/user-profile-image.php?id=' .
        rawurlencode(
            $insigniaId
        )
    :
        '';


$openRaw =
    strtolower(
        trim(
            (string)$group['OpenEnrollment']
        )
    );


$openEnrollment =
    in_array(
        $openRaw,
        [
            '1',
            'true',
            'yes',
            'on'
        ],
        true
    );


$membershipFee =
    (int)$group['MembershipFee'];


$membershipText =
    $membershipFee > 0
    ?
        'L$' .
        number_format(
            $membershipFee
        )
    :
        'FREE';


$selectedTitle =
    trim(
        (string)$group['SelectedTitle']
    );


if ($selectedTitle === '') {
    $selectedTitle = 'Member';
}


$isActiveGroup =
    (int)(
        $group['ActiveGroup'] ??
        0
    ) === 1;


$charter =
    trim(
        (string)$group['Charter']
    );


if ($charter === '') {

    $charter =
        'No group charter has been entered.';
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
<?= auProfileEsc((string)$group['Name']) ?>
- Grid
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
    color:#e6eaec;
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
            "/Other/assets/images/control-center-teal-bg.png"
        )

        center center /
        cover
        fixed
        no-repeat !important;

}

/* AUSTRALIA DASHBOARD BACKGROUND END */


.group-page{
    width:min(
        1180px,
        calc(100% - 30px)
    );

    margin:
        20px
        auto
        45px;
}


.group-card{
    padding:22px;

    margin-bottom:18px;

    border:
        1px solid
        rgba(194,146,40,.38);

    border-radius:10px;

    background:
        linear-gradient(
            145deg,
            rgba(24,31,34,.95),
            rgba(5,9,11,.94)
        );

    box-shadow:
        0 8px 26px
        rgba(0,0,0,.42);
}


.group-hero{
    display:grid;

    grid-template-columns:
        190px
        minmax(0,1fr);

    gap:24px;

    align-items:start;
}


.group-image{
    width:190px;
    height:190px;

    overflow:hidden;

    border:
        1px solid
        rgba(225,174,54,.68);

    border-radius:9px;

    background:#020405;

    box-shadow:
        inset 0 0 0 3px
        rgba(0,0,0,.58),
        0 5px 15px
        rgba(0,0,0,.45);
}


.group-image img{
    display:block;

    width:100%;
    height:100%;

    object-fit:cover;
}


.no-insignia{
    display:flex;
    align-items:center;
    justify-content:center;

    width:100%;
    height:100%;

    color:#677579;

    font-size:11px;
    font-weight:900;
}


.group-name-main{
    margin:
        3px
        0
        9px;

    color:#edb842;

    font-size:31px;
    font-weight:900;
}


.group-active{
    display:inline-block;

    margin-bottom:13px;

    padding:
        5px
        9px;

    border:
        1px solid
        rgba(233,178,48,.55);

    border-radius:5px;

    color:#edb842;

    background:
        rgba(53,40,9,.65);

    font-size:9px;
    font-weight:900;
}


.group-charter{
    white-space:pre-wrap;

    color:#c7cfd2;

    line-height:1.55;

    font-size:13px;
}


.stat-grid{
    display:grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(180px,1fr)
        );

    gap:12px;

    margin-top:22px;
}


.stat{
    padding:13px;

    border:
        1px solid
        rgba(174,135,43,.28);

    border-radius:7px;

    background:
        rgba(5,8,10,.65);
}


.stat-label{
    margin-bottom:6px;

    color:#8f9da1;

    font-size:9px;
    font-weight:900;
}


.stat-value{
    color:#e8edef;

    font-size:13px;
    font-weight:900;

    overflow-wrap:anywhere;
}


.open{
    color:#77d59a;
}


.closed{
    color:#da8d8d;
}


.section-title{
    margin:
        0
        0
        16px;

    color:#edb842;

    font-size:24px;
    font-weight:900;
}


.role-grid{
    display:grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(250px,1fr)
        );

    gap:12px;
}


.role{
    padding:14px;

    border:
        1px solid
        rgba(190,146,45,.28);

    border-radius:8px;

    background:
        rgba(5,9,11,.72);
}


.role.owner{
    border-color:
        rgba(230,176,49,.68);

    box-shadow:
        inset 3px 0 0
        #dda82d;
}


.role-name{
    color:#f0bd49;

    font-size:13px;
    font-weight:900;
}


.role-title{
    margin-top:5px;

    color:#dce2e4;

    font-size:12px;
    font-weight:800;
}


.role-description{
    margin-top:8px;

    color:#929fa3;

    font-size:11px;
    line-height:1.45;
}


.owner-label{
    display:inline-block;

    margin-top:8px;

    color:#edb842;

    font-size:8px;
    font-weight:900;
}


.member-table-wrap{
    overflow-x:auto;
}


.member-table{
    width:100%;

    border-collapse:collapse;

    font-size:12px;
}


.member-table th{
    padding:
        10px
        12px;

    text-align:left;

    color:#e6b340;

    border-bottom:
        1px solid
        rgba(202,153,43,.42);

    font-size:9px;
}


.member-table td{
    padding:
        11px
        12px;

    border-bottom:
        1px solid
        rgba(125,139,144,.17);

    color:#dce2e4;
}


.member-table tr:last-child td{
    border-bottom:0;
}


.member-active{
    color:#edb842;

    font-size:8px;
    font-weight:900;
}


.member-owner{
    color:#edb842;
    font-weight:900;
}


@media(max-width:700px){

    .group-hero{
        grid-template-columns:1fr;
    }

    .group-image{
        width:150px;
        height:150px;
    }

    .group-name-main{
        font-size:25px;
    }
}


/*
 * ============================================================
 * AUSTRALIA GROUP MEMBER PROFILE LINKS V1
 * ============================================================
 */

.member-profile-link{
    color:inherit;
    text-decoration:none;
    font-weight:inherit;
}

.member-profile-link:hover{
    color:#f0bd48;
    text-decoration:underline;
}


/* END AUSTRALIA GROUP MEMBER PROFILE LINKS V1 */
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

<div class="group-page">





<section class="group-card">

<div class="group-hero">


<div class="group-image">

<?php if ($hasInsignia): ?>

<img
    src="<?= auProfileEsc($insigniaUrl) ?>"
    alt="<?= auProfileEsc((string)$group['Name']) ?> insignia">

<?php else: ?>

<div class="no-insignia">
NO GROUP INSIGNIA
</div>

<?php endif; ?>

</div>


<div>

<h1 class="group-name-main">
<?= auProfileEsc((string)$group['Name']) ?>
</h1>


<?php if ($isActiveGroup): ?>

<div class="group-active">
YOUR ACTIVE GROUP
</div>

<?php endif; ?>


<div class="group-charter"><?= auProfileEsc($charter) ?></div>

</div>

</div>


<div class="stat-grid">


<div class="stat">

<div class="stat-label">
MEMBERS
</div>

<div class="stat-value">
<?= number_format($memberCount) ?>
</div>

</div>


<div class="stat">

<div class="stat-label">
ENROLMENT
</div>

<div class="stat-value <?= $openEnrollment ? 'open' : 'closed' ?>">
<?= $openEnrollment ? 'OPEN' : 'CLOSED' ?>
</div>

</div>


<div class="stat">

<div class="stat-label">
JOINING FEE
</div>

<div class="stat-value">
<?= auProfileEsc($membershipText) ?>
</div>

</div>


<div class="stat">

<div class="stat-label">
YOUR TITLE
</div>

<div class="stat-value">
<?= auProfileEsc($selectedTitle) ?>
</div>

</div>


<div class="stat">

<div class="stat-label">
FOUNDER
</div>

<div class="stat-value">
<?= auProfileEsc($founderName) ?>
</div>

</div>


<div class="stat">

<div class="stat-label">
CURRENT OWNER(S)
</div>

<div class="stat-value">
<?= auProfileEsc($ownersText) ?>
</div>

</div>


</div>

</section>


<section class="group-card">

<h2 class="section-title">
GROUP ROLES
</h2>


<div class="role-grid">

<?php foreach ($roles as $role): ?>

<?php
$isOwnerRole =
    strtolower(
        (string)$role['RoleID']
    ) ===
    $ownerRoleId;
?>

<div class="role<?= $isOwnerRole ? ' owner' : '' ?>">

<div class="role-name">
<?= auProfileEsc((string)$role['Name']) ?>
</div>

<div class="role-title">
<?= auProfileEsc((string)$role['Title']) ?>
</div>


<?php if (
    trim(
        (string)$role['Description']
    ) !== ''
): ?>

<div class="role-description">
<?= auProfileEsc((string)$role['Description']) ?>
</div>

<?php endif; ?>


<?php if ($isOwnerRole): ?>

<div class="owner-label">
OWNER ROLE
</div>

<?php endif; ?>

</div>

<?php endforeach; ?>

</div>

</section>


<section class="group-card">

<h2 class="section-title">
GROUP MEMBERS (<?= number_format($memberCount) ?>)
</h2>


<div class="member-table-wrap">

<table class="member-table">

<thead>

<tr>
<th>MEMBER</th>
<th>GROUP TITLE</th>
<th>STATUS</th>
</tr>

</thead>

<tbody>


<?php foreach ($members as $member): ?>

<?php
$isOwner =
    strtolower(
        (string)$member['SelectedRoleID']
    ) ===
    $ownerRoleId;
?>

<tr>

<td class="<?= $isOwner ? 'member-owner' : '' ?>">

<a
    class="member-profile-link"
    href="/Other/avatar-profile.php?id=<?= rawurlencode(
        (string)$member['PrincipalID']
    ) ?>&amp;group=<?= rawurlencode($groupId) ?>">

<?= auProfileEsc((string)$member['AvatarName']) ?>

</a>

</td>

<td>
<?= auProfileEsc((string)$member['MemberTitle']) ?>
</td>

<td>

<?php if ($member['ActiveGroup']): ?>

<span class="member-active">
ACTIVE GROUP
</span>

<?php else: ?>

-

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>


</tbody>

</table>

</div>

</section>


</div>

<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>






