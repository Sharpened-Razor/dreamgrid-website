<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/navigation.php';

ag_no_cache();

$session = ag_require_admin();
$con = ag_db_connect();

if (!$con) {
    http_response_code(500);
    exit('Grid database is unavailable.');
}

mysqli_set_charset($con, 'utf8mb4');

function qid(string $name): string
{
    return '`' .
        str_replace('`', '``', $name) .
        '`';
}

function validUuid(string $value): bool
{
    return (bool)preg_match(
        '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
        $value
    );
}

function localAvatarName(
    mysqli $con,
    string $principalId
): string {
    if (!validUuid($principalId)) {
        return '';
    }

    $sql =
        'SELECT FirstName, LastName ' .
        'FROM UserAccounts ' .
        'WHERE PrincipalID = ? ' .
        'LIMIT 1';

    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        return '';
    }

    mysqli_stmt_bind_param(
        $stmt,
        's',
        $principalId
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $name = '';

    if ($result) {
        $row = mysqli_fetch_assoc($result);

        if ($row) {
            $name =
                trim(
                    (string)$row['FirstName'] .
                    ' ' .
                    (string)$row['LastName']
                );
        }

        mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);

    return $name;
}

function countGroupRows(
    mysqli $con,
    string $table,
    string $groupId
): int {
    $sql =
        'SELECT COUNT(*) AS c ' .
        'FROM ' . qid($table) . ' ' .
        'WHERE GroupID = ?';

    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param(
        $stmt,
        's',
        $groupId
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $count = 0;

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count = (int)($row['c'] ?? 0);
        mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);

    return $count;
}

function groupType(array $group): string
{
    $founder =
        strtolower(
            trim(
                (string)$group['FounderID']
            )
        );

    if (
        $founder === '' ||
        $founder === '00000000-0000-0000-0000-000000000000'
    ) {
        return 'HG / CACHED';
    }

    return 'LOCAL';
}

$q =
    trim(
        (string)($_GET['q'] ?? '')
    );

$selectedId =
    trim(
        (string)($_GET['id'] ?? '')
    );

$listSql =
    'SELECT ' .
    'g.GroupID, g.Location, g.Name, g.Charter, g.InsigniaID, ' .
    'g.FounderID, g.MembershipFee, g.OpenEnrollment, ' .
    'g.ShowInList, g.AllowPublish, g.MaturePublish, g.OwnerRoleID, ' .
    'ua.FirstName AS FounderFirstName, ' .
    'ua.LastName AS FounderLastName, ' .
    '(SELECT COUNT(*) FROM os_groups_membership m WHERE m.GroupID = g.GroupID) AS MemberCount, ' .
    '(SELECT COUNT(*) FROM os_groups_roles r WHERE r.GroupID = g.GroupID) AS RoleCount, ' .
    '(SELECT COUNT(*) FROM os_groups_notices n WHERE n.GroupID = g.GroupID) AS NoticeCount ' .
    'FROM os_groups_groups g ' .
    'LEFT JOIN UserAccounts ua ON ua.PrincipalID = g.FounderID ';

$params = [];
$types = '';

if ($q !== '') {
    $listSql .=
        'WHERE (' .
        'g.Name LIKE ? OR ' .
        'g.GroupID LIKE ? OR ' .
        'g.FounderID LIKE ? OR ' .
        "CONCAT(COALESCE(ua.FirstName,''), ' ', COALESCE(ua.LastName,'')) LIKE ?" .
        ') ';

    $like = '%' . $q . '%';

    $params = [
        $like,
        $like,
        $like,
        $like,
    ];

    $types = 'ssss';
}

$listSql .=
    'ORDER BY ' .
    'CASE ' .
    "WHEN g.FounderID <> '' " .
    "AND g.FounderID <> '00000000-0000-0000-0000-000000000000' " .
    'THEN 0 ELSE 1 END, ' .
    'g.Name';

$stmt = mysqli_prepare($con, $listSql);

if (!$stmt) {
    mysqli_close($con);
    http_response_code(500);
    exit('Could not prepare Manage Groups query.');
}

if ($params) {
    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );
}

mysqli_stmt_execute($stmt);
$listResult = mysqli_stmt_get_result($stmt);

$groups = [];

if ($listResult) {
    while ($row = mysqli_fetch_assoc($listResult)) {
        $groups[] = $row;
    }

    mysqli_free_result($listResult);
}

mysqli_stmt_close($stmt);

$selected = null;
$members = [];
$roles = [];
$roleMemberships = [];
$notices = [];

if (
    $selectedId !== '' &&
    validUuid($selectedId)
) {
    $detailSql =
        'SELECT ' .
        'g.*, ' .
        'ua.FirstName AS FounderFirstName, ' .
        'ua.LastName AS FounderLastName ' .
        'FROM os_groups_groups g ' .
        'LEFT JOIN UserAccounts ua ON ua.PrincipalID = g.FounderID ' .
        'WHERE g.GroupID = ? ' .
        'LIMIT 1';

    $stmt = mysqli_prepare($con, $detailSql);

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            's',
            $selectedId
        );

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            $selected = mysqli_fetch_assoc($result) ?: null;
            mysqli_free_result($result);
        }

        mysqli_stmt_close($stmt);
    }

    if ($selected) {

        $memberSql =
            'SELECT ' .
            'm.GroupID, m.PrincipalID, m.SelectedRoleID, ' .
            'm.Contribution, m.ListInProfile, m.AcceptNotices, m.AccessToken, ' .
            'ua.FirstName, ua.LastName, ua.UserLevel, ' .
            'r.Name AS SelectedRoleName, r.Title AS SelectedRoleTitle ' .
            'FROM os_groups_membership m ' .
            'LEFT JOIN UserAccounts ua ON ua.PrincipalID = m.PrincipalID ' .
            'LEFT JOIN os_groups_roles r ' .
            'ON r.GroupID = m.GroupID AND r.RoleID = m.SelectedRoleID ' .
            'WHERE m.GroupID = ? ' .
            "ORDER BY COALESCE(ua.FirstName,''), COALESCE(ua.LastName,''), m.PrincipalID";

        $stmt = mysqli_prepare($con, $memberSql);

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                's',
                $selectedId
            );

            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $members[] = $row;
                }

                mysqli_free_result($result);
            }

            mysqli_stmt_close($stmt);
        }

        $roleSql =
            'SELECT GroupID, RoleID, Name, Description, Title, Powers ' .
            'FROM os_groups_roles ' .
            'WHERE GroupID = ? ' .
            "ORDER BY CASE WHEN RoleID = '00000000-0000-0000-0000-000000000000' THEN 0 ELSE 1 END, Name";

        $stmt = mysqli_prepare($con, $roleSql);

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                's',
                $selectedId
            );

            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $roles[] = $row;
                }

                mysqli_free_result($result);
            }

            mysqli_stmt_close($stmt);
        }

        $roleMembershipSql =
            'SELECT ' .
            'rm.GroupID, rm.RoleID, rm.PrincipalID, ' .
            'r.Name AS RoleName, r.Title AS RoleTitle, ' .
            'ua.FirstName, ua.LastName ' .
            'FROM os_groups_rolemembership rm ' .
            'LEFT JOIN os_groups_roles r ' .
            'ON r.GroupID = rm.GroupID AND r.RoleID = rm.RoleID ' .
            'LEFT JOIN UserAccounts ua ON ua.PrincipalID = rm.PrincipalID ' .
            'WHERE rm.GroupID = ? ' .
            "ORDER BY COALESCE(r.Name,''), COALESCE(ua.FirstName,''), COALESCE(ua.LastName,''), rm.PrincipalID";

        $stmt = mysqli_prepare(
            $con,
            $roleMembershipSql
        );

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                's',
                $selectedId
            );

            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $roleMemberships[] = $row;
                }

                mysqli_free_result($result);
            }

            mysqli_stmt_close($stmt);
        }

        $noticeSql =
            'SELECT ' .
            'NoticeID, TMStamp, FromName, Subject, Message, ' .
            'HasAttachment, AttachmentType, AttachmentName, AttachmentItemID, AttachmentOwnerID ' .
            'FROM os_groups_notices ' .
            'WHERE GroupID = ? ' .
            'ORDER BY TMStamp DESC ' .
            'LIMIT 20';

        $stmt = mysqli_prepare($con, $noticeSql);

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                's',
                $selectedId
            );

            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $notices[] = $row;
                }

                mysqli_free_result($result);
            }

            mysqli_stmt_close($stmt);
        }
    }
}

$totalGroups =
    (int)(
        mysqli_fetch_row(
            mysqli_query(
                $con,
                'SELECT COUNT(*) FROM os_groups_groups'
            )
        )[0] ?? 0
    );

$localGroups =
    (int)(
        mysqli_fetch_row(
            mysqli_query(
                $con,
                "SELECT COUNT(*) FROM os_groups_groups " .
                "WHERE FounderID <> '' " .
                "AND FounderID <> '00000000-0000-0000-0000-000000000000'"
            )
        )[0] ?? 0
    );

$totalMemberships =
    (int)(
        mysqli_fetch_row(
            mysqli_query(
                $con,
                'SELECT COUNT(*) FROM os_groups_membership'
            )
        )[0] ?? 0
    );

$totalNotices =
    (int)(
        mysqli_fetch_row(
            mysqli_query(
                $con,
                'SELECT COUNT(*) FROM os_groups_notices'
            )
        )[0] ?? 0
    );

mysqli_close($con);

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Groups</title>
<link rel="stylesheet" href="/Other/core/site-shell.css?v=1">

<style>
.summary-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:12px;
    margin-bottom:18px;
}
.summary-card,
.detail-item,
.group-card,
.section-card{
    border:1px solid rgba(255,255,255,.11);
    border-radius:12px;
    background:rgba(8,13,16,.78);
}
.summary-card{
    padding:16px;
}
.summary-card span,
.detail-item span{
    display:block;
    margin-bottom:5px;
    color:#94a6af;
    font-size:11px;
    font-weight:900;
    letter-spacing:.05em;
}
.summary-card strong{
    font-size:27px;
}
.searchbar{
    display:grid;
    grid-template-columns:1fr auto;
    gap:12px;
    margin-bottom:18px;
}
.searchbar input{
    min-height:48px;
    padding:0 15px;
    border:1px solid rgba(255,255,255,.14);
    border-radius:10px;
    color:#fff;
    background:#080d10;
}
.groups-layout{
    display:grid;
    grid-template-columns:minmax(330px,.85fr) minmax(0,1.55fr);
    gap:16px;
    align-items:start;
}
.group-list{
    display:flex;
    flex-direction:column;
    gap:10px;
    max-height:calc(100vh - 250px);
    overflow:auto;
    padding-right:4px;
}
.group-card{
    display:block;
    padding:14px;
    color:inherit;
    text-decoration:none;
}
.group-card:hover,
.group-card.active{
    border-color:rgba(244,179,35,.55);
    background:rgba(244,179,35,.07);
}
.group-topline{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:flex-start;
}
.group-name{
    font-size:17px;
    font-weight:900;
}
.badge{
    display:inline-block;
    padding:4px 8px;
    border:1px solid rgba(255,255,255,.15);
    border-radius:999px;
    font-size:10px;
    font-weight:900;
    white-space:nowrap;
}
.badge.local{
    border-color:rgba(84,204,121,.34);
    color:#a8efba;
}
.badge.hg{
    border-color:rgba(111,164,255,.35);
    color:#b9d1ff;
}
.group-meta{
    margin-top:9px;
    color:#9badb6;
    font-size:12px;
    line-height:1.55;
}
.detail-panel{
    min-width:0;
}
.section-card{
    padding:18px;
    margin-bottom:14px;
}
.section-card h2{
    margin:0 0 14px;
}
.detail-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:10px;
}
.detail-item{
    padding:12px;
    overflow-wrap:anywhere;
}
.detail-item.full{
    grid-column:1/-1;
}
.charter{
    white-space:pre-wrap;
    line-height:1.6;
}
.table-wrap{
    overflow:auto;
}
table{
    width:100%;
    border-collapse:collapse;
}
th,td{
    padding:10px 9px;
    border-bottom:1px solid rgba(255,255,255,.09);
    text-align:left;
    vertical-align:top;
}
th{
    color:#94a6af;
    font-size:11px;
    letter-spacing:.05em;
}
td{
    font-size:13px;
}
.uuid{
    font-family:Consolas,monospace;
    font-size:11px;
    overflow-wrap:anywhere;
}
.empty{
    padding:22px;
    text-align:center;
    color:#91a3ad;
}
.notice-card{
    padding:13px 0;
    border-bottom:1px solid rgba(255,255,255,.09);
}
.notice-card:last-child{
    border-bottom:0;
}
.notice-subject{
    font-weight:900;
}
.notice-meta{
    margin-top:4px;
    color:#90a2ac;
    font-size:11px;
}
.notice-message{
    margin-top:8px;
    white-space:pre-wrap;
    line-height:1.5;
}
@media(max-width:1000px){
    .summary-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .groups-layout{
        grid-template-columns:1fr;
    }
    .group-list{
        max-height:none;
    }
}
@media(max-width:650px){
    .summary-grid,
    .detail-grid{
        grid-template-columns:1fr;
    }
    .detail-item.full{
        grid-column:auto;
    }
    .searchbar{
        grid-template-columns:1fr;
    }
}
</style>

<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">

<style id="manage-groups-standard-design">

/* ==========================================================
   MANAGE GROUPS - STANDARD ADMIN DESIGN V1
   ========================================================== */


/* ----------------------------------------------------------
   HEADER CARD
   ---------------------------------------------------------- */

.manage-groups-standard-header{

    display:flex !important;
    align-items:center !important;
    justify-content:space-between !important;

    gap:24px !important;

    width:100% !important;
    min-height:105px !important;

    margin:0 0 18px !important;
    padding:18px 22px !important;

    box-sizing:border-box !important;

    border:
        1px solid rgba(244,179,35,.55) !important;

    border-radius:
        16px !important;

    background:
        linear-gradient(
            135deg,
            rgba(31,39,44,.97),
            rgba(9,14,17,.98) 55%,
            rgba(3,7,9,.99)
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.08),
        0 10px 28px rgba(0,0,0,.42) !important;

    backdrop-filter:
        blur(7px) !important;
}


.manage-groups-standard-header .ag-eyebrow{

    margin:0 0 7px !important;

    color:#f4b323 !important;

    font-size:13px !important;
    font-weight:900 !important;

    line-height:1 !important;

    letter-spacing:.14em !important;

    text-transform:uppercase !important;
}


.manage-groups-standard-header .ag-title{

    margin:0 !important;

    color:#ffffff !important;

    font-size:34px !important;
    font-weight:900 !important;

    line-height:1.05 !important;

    letter-spacing:-.02em !important;

    text-shadow:
        0 3px 7px rgba(0,0,0,.80) !important;
}


/* ----------------------------------------------------------
   SUMMARY CARDS
   ---------------------------------------------------------- */

.summary-grid{

    display:grid !important;

    grid-template-columns:
        repeat(4,minmax(0,1fr)) !important;

    gap:12px !important;

    margin:
        0 0 16px !important;
}


.summary-grid .summary-card{

    min-height:78px !important;

    padding:14px 17px !important;

    display:flex !important;
    flex-direction:column !important;
    justify-content:center !important;

    box-sizing:border-box !important;

    border:
        1px solid rgba(244,179,35,.24) !important;

    border-radius:
        12px !important;

    background:
        linear-gradient(
            180deg,
            rgba(13,20,24,.985),
            rgba(4,9,12,.99)
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.05),
        0 8px 20px rgba(0,0,0,.24) !important;
}


.summary-grid .summary-card span{

    margin:0 0 6px !important;

    color:#a7b6be !important;

    font-size:11px !important;
    font-weight:900 !important;

    letter-spacing:.055em !important;
}


.summary-grid .summary-card strong{

    color:#f4b323 !important;

    font-size:25px !important;
    font-weight:900 !important;

    line-height:1 !important;
}


/* ----------------------------------------------------------
   SEARCH + CREATE GROUP TOOLBAR
   ---------------------------------------------------------- */

.groups-toolbar{

    display:grid !important;

    grid-template-columns:
        minmax(0,1fr) auto !important;

    align-items:stretch !important;

    gap:12px !important;

    margin:
        0 0 16px !important;
}


.groups-toolbar .searchbar{

    display:flex !important;
    align-items:center !important;

    gap:9px !important;

    margin:0 !important;
    padding:9px !important;

    box-sizing:border-box !important;

    border:
        1px solid rgba(255,255,255,.12) !important;

    border-radius:
        12px !important;

    background:
        rgba(3,8,11,.97) !important;
}


.groups-toolbar .searchbar input{

    flex:1 1 auto !important;

    width:100% !important;
    min-height:42px !important;

    padding:
        0 14px !important;

    box-sizing:border-box !important;

    border:
        1px solid rgba(255,255,255,.18) !important;

    border-radius:
        8px !important;

    background:
        #070c0f !important;

    color:
        #ffffff !important;

    font-size:
        14px !important;
}


.groups-toolbar .searchbar input:focus{

    outline:none !important;

    border-color:
        rgba(244,179,35,.80) !important;

    box-shadow:
        0 0 0 2px rgba(244,179,35,.10) !important;
}


.create-group-action{

    display:flex !important;

    align-items:stretch !important;
    justify-content:flex-end !important;
}


.create-group-action .ag-button{

    min-width:165px !important;
    min-height:60px !important;

    display:inline-flex !important;

    align-items:center !important;
    justify-content:center !important;

    padding:
        0 20px !important;

    border-radius:
        11px !important;

    font-size:
        12px !important;

    font-weight:
        900 !important;
}


/* ----------------------------------------------------------
   MAIN GROUP WORKSPACE
   ---------------------------------------------------------- */

.groups-layout{

    display:grid !important;

    grid-template-columns:
        minmax(350px,.82fr)
        minmax(0,1.55fr) !important;

    gap:
        16px !important;

    align-items:start !important;
}


/* ----------------------------------------------------------
   LEFT GROUP LIST PANEL
   ---------------------------------------------------------- */

.group-list{

    display:flex !important;

    flex-direction:column !important;

    gap:
        9px !important;

    max-height:
        650px !important;

    overflow-y:
        auto !important;

    overflow-x:
        hidden !important;

    padding:
        12px !important;

    box-sizing:
        border-box !important;

    border:
        1px solid rgba(244,179,35,.27) !important;

    border-radius:
        14px !important;

    background:
        linear-gradient(
            145deg,
            rgba(12,19,23,.985),
            rgba(3,8,11,.99)
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.045),
        0 10px 25px rgba(0,0,0,.28) !important;

    scrollbar-width:
        thin !important;

    scrollbar-color:
        #6c7478 #070b0e !important;
}


/* Chrome / Edge scrollbar */

.group-list::-webkit-scrollbar{

    width:
        9px !important;
}


.group-list::-webkit-scrollbar-track{

    background:
        #070b0e !important;

    border-radius:
        8px !important;
}


.group-list::-webkit-scrollbar-thumb{

    background:
        linear-gradient(
            180deg,
            #788186,
            #3e484d
        ) !important;

    border:
        2px solid #070b0e !important;

    border-radius:
        8px !important;
}


.group-list::-webkit-scrollbar-thumb:hover{

    background:
        #a17b25 !important;
}


/* ----------------------------------------------------------
   INDIVIDUAL GROUP CARDS
   ---------------------------------------------------------- */

.group-list .group-card{

    display:block !important;

    padding:
        14px 15px !important;

    box-sizing:
        border-box !important;

    border:
        1px solid rgba(255,255,255,.12) !important;

    border-radius:
        10px !important;

    background:
        linear-gradient(
            180deg,
            rgba(23,32,37,.98),
            rgba(8,13,16,.99)
        ) !important;

    color:
        #ecf2f4 !important;

    text-decoration:
        none !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.035) !important;
}


.group-list .group-card:hover{

    border-color:
        rgba(244,179,35,.55) !important;

    background:
        linear-gradient(
            180deg,
            rgba(31,42,47,.99),
            rgba(11,17,20,.99)
        ) !important;
}


.group-list .group-card.active{

    border-color:
        rgba(244,179,35,.90) !important;

    background:
        linear-gradient(
            180deg,
            rgba(55,43,16,.97),
            rgba(18,15,8,.99)
        ) !important;

    box-shadow:
        inset 3px 0 0 #f4b323,
        inset 0 1px 0 rgba(255,255,255,.06) !important;
}


.group-name{

    color:
        #ffffff !important;

    font-size:
        15px !important;

    font-weight:
        900 !important;
}


.group-meta{

    margin-top:
        7px !important;

    color:
        #b5c2c8 !important;

    font-size:
        12px !important;

    line-height:
        1.45 !important;
}


/* ----------------------------------------------------------
   GROUP TYPE BADGES
   ---------------------------------------------------------- */

.badge{

    min-height:
        24px !important;

    display:inline-flex !important;

    align-items:center !important;
    justify-content:center !important;

    padding:
        0 9px !important;

    box-sizing:
        border-box !important;

    border-radius:
        999px !important;

    font-size:
        10px !important;

    font-weight:
        900 !important;
}


/* ----------------------------------------------------------
   RIGHT DETAIL PANEL
   ---------------------------------------------------------- */

.detail-panel{

    min-width:
        0 !important;
}


.detail-panel .section-card{

    margin:
        0 0 14px !important;

    padding:
        20px !important;

    box-sizing:
        border-box !important;

    border:
        1px solid rgba(244,179,35,.25) !important;

    border-radius:
        14px !important;

    background:
        linear-gradient(
            145deg,
            rgba(18,26,30,.985),
            rgba(4,9,12,.995)
        ) !important;

    color:
        #e8eef0 !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.045),
        0 10px 25px rgba(0,0,0,.25) !important;
}


.detail-panel .section-card.empty{

    min-height:
        150px !important;

    display:flex !important;

    align-items:center !important;
    justify-content:center !important;

    padding:
        25px !important;

    color:
        #bac7cd !important;

    font-size:
        14px !important;

    text-align:
        center !important;
}


.detail-panel .section-card h2{

    margin:
        0 0 14px !important;

    color:
        #ffffff !important;

    font-size:
        23px !important;

    font-weight:
        900 !important;
}


/* ----------------------------------------------------------
   GROUP DETAIL MINI-CARDS
   ---------------------------------------------------------- */

.detail-panel .detail-grid{

    display:grid !important;

    grid-template-columns:
        repeat(2,minmax(0,1fr)) !important;

    gap:
        11px !important;
}


.detail-panel .detail-item{

    padding:
        13px !important;

    box-sizing:
        border-box !important;

    border:
        1px solid rgba(255,255,255,.11) !important;

    border-radius:
        10px !important;

    background:
        rgba(3,8,11,.96) !important;

    overflow-wrap:
        anywhere !important;
}


.detail-panel .detail-item span{

    color:
        #9eafb8 !important;

    font-size:
        11px !important;

    font-weight:
        900 !important;
}


/* ----------------------------------------------------------
   RESPONSIVE
   ---------------------------------------------------------- */

@media(max-width:1000px){

    .summary-grid{

        grid-template-columns:
            repeat(2,minmax(0,1fr)) !important;
    }


    .groups-layout{

        grid-template-columns:
            1fr !important;
    }


    .group-list{

        max-height:
            none !important;
    }
}


@media(max-width:760px){

    .groups-toolbar{

        grid-template-columns:
            1fr !important;
    }


    .create-group-action .ag-button{

        width:
            100% !important;

        min-height:
            46px !important;
    }


    .manage-groups-standard-header{

        align-items:
            flex-start !important;

        flex-direction:
            column !important;
    }
}


@media(max-width:600px){

    .summary-grid,
    .detail-panel .detail-grid{

        grid-template-columns:
            1fr !important;
    }


    .manage-groups-standard-header{

        padding:
            16px !important;
    }


    .manage-groups-standard-header .ag-title{

        font-size:
            28px !important;
    }
}

</style>
<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=<?= filemtime(__DIR__ . '/assets/css/ag-uniform-site-v12.css') ?>">

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

<div class="ag-shell">

<?php
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "Manage Groups";
$siteHeaderRole = "GRID OWNER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "ADMIN HOME";
$siteHeaderLink = "/Other/admin-home.php";
require_once __DIR__ . "/includes/site-header.php";
?>

<?php if (isset($_GET['deleted_group'])): ?>
    <div class="ag-success" style="margin:0 0 16px">
        Group permanently deleted:
        <strong><?=ag_h((string)($_GET['deleted_group_name'] ?? ''))?></strong>
    </div>
<?php endif; ?>

<div class="summary-grid">

    <div class="summary-card">
        <span>GROUP RECORDS</span>
        <strong><?=ag_h($totalGroups)?></strong>
    </div>

    <div class="summary-card">
        <span>LOCAL GROUPS</span>
        <strong><?=ag_h($localGroups)?></strong>
    </div>

    <div class="summary-card">
        <span>MEMBERSHIPS</span>
        <strong><?=ag_h($totalMemberships)?></strong>
    </div>

    <div class="summary-card">
        <span>GROUP NOTICES</span>
        <strong><?=ag_h($totalNotices)?></strong>
    </div>

</div>

<div class="groups-toolbar">

<form class="searchbar" method="get" action="<?=ag_h(ag_route('admin_groups'))?>">

    <input
        type="text"
        name="q"
        value="<?=ag_h($q)?>"
        placeholder="Search group name, GroupID, founder name or UUID">

    <button class="ag-button primary" type="submit">
        SEARCH
    </button>

</form>

<div class="create-group-action">
<a class="ag-button primary" href="<?=ag_h(ag_route('admin_group_create'))?>">
        CREATE GROUP
    </a>
</div>

</div>

<div class="groups-layout">

    <div class="group-list">

        <?php if (!$groups): ?>

            <div class="empty">
                No groups matched your search.
            </div>

        <?php else: ?>

            <?php foreach ($groups as $group): ?>

                <?php
                $gid = (string)$group['GroupID'];

                $founderName =
                    trim(
                        (string)($group['FounderFirstName'] ?? '') .
                        ' ' .
                        (string)($group['FounderLastName'] ?? '')
                    );

                $type = groupType($group);

                $url =
                    ag_route('admin_groups') .
                    '?' .
                    http_build_query(
                        array_filter(
                            [
                                'q' => $q,
                                'id' => $gid,
                            ],
                            fn($v) => $v !== ''
                        )
                    );
                ?>

                <a
                    class="group-card <?=$selectedId === $gid ? 'active' : ''?>"
                    href="<?=ag_h($url)?>"
                >

                    <div class="group-topline">

                        <div class="group-name">
                            <?=ag_h($group['Name'])?>
                        </div>

                        <span class="badge <?=$type === 'LOCAL' ? 'local' : 'hg'?>">
                            <?=ag_h($type)?>
                        </span>

                    </div>

                    <div class="group-meta">

                        Members:
                        <strong><?=ag_h($group['MemberCount'])?></strong>
                        &nbsp; &bull; &nbsp;

                        Roles:
                        <strong><?=ag_h($group['RoleCount'])?></strong>
                        &nbsp; &bull; &nbsp;

                        Notices:
                        <strong><?=ag_h($group['NoticeCount'])?></strong>

                        <br>

                        Founder:
                        <?= $founderName !== '' ? ag_h($founderName) : ag_h($group['FounderID']) ?>

                    </div>

                </a>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <div class="detail-panel">

        <?php if (!$selected): ?>

            <div class="section-card empty">
                Select a group to view its details, members, roles and notices.
            </div>

        <?php else: ?>

            <?php
            $founderName =
                trim(
                    (string)($selected['FounderFirstName'] ?? '') .
                    ' ' .
                    (string)($selected['FounderLastName'] ?? '')
                );

            $selectedType =
                groupType($selected);
            ?>

            <div class="section-card">

                <div class="group-topline">
                    <h2><?=ag_h($selected['Name'])?></h2>

                    <span class="badge <?=$selectedType === 'LOCAL' ? 'local' : 'hg'?>">
                        <?=ag_h($selectedType)?>
                    </span>
                </div>

                <?php if ($selectedType === 'LOCAL'): ?>

                    <div style="margin:0 0 14px">
                        <a
                            class="ag-button primary"
                            href="<?=ag_h(ag_route('admin_group_manage'))?>?id=<?=rawurlencode((string)$selected['GroupID'])?>"
                        >
                            MANAGE GROUP
                        </a>
                    </div>

                <?php endif; ?>

                <div class="detail-grid">

                    <div class="detail-item full">
                        <span>GROUP ID</span>
                        <div class="uuid"><?=ag_h($selected['GroupID'])?></div>
                    </div>

                    <div class="detail-item">
                        <span>FOUNDER</span>
                        <?= $founderName !== '' ? ag_h($founderName) : '&mdash;' ?>
                    </div>

                    <div class="detail-item">
                        <span>FOUNDER UUID</span>
                        <div class="uuid"><?=ag_h($selected['FounderID'])?></div>
                    </div>

                    <div class="detail-item">
                        <span>LOCATION</span>
                        <?= trim((string)$selected['Location']) !== '' ? ag_h($selected['Location']) : '&mdash;' ?>
                    </div>

                    <div class="detail-item">
                        <span>OWNER ROLE ID</span>
                        <div class="uuid"><?=ag_h($selected['OwnerRoleID'])?></div>
                    </div>

                    <div class="detail-item">
                        <span>MEMBERSHIP FEE</span>
                        <?=ag_h($selected['MembershipFee'])?>
                    </div>

                    <div class="detail-item">
                        <span>OPEN ENROLLMENT</span>
                        <?=ag_h($selected['OpenEnrollment'])?>
                    </div>

                    <div class="detail-item">
                        <span>SHOW IN LIST</span>
                        <?=((int)$selected['ShowInList']) ? 'YES' : 'NO'?>
                    </div>

                    <div class="detail-item">
                        <span>ALLOW PUBLISH</span>
                        <?=((int)$selected['AllowPublish']) ? 'YES' : 'NO'?>
                    </div>

                    <div class="detail-item">
                        <span>MATURE PUBLISH</span>
                        <?=((int)$selected['MaturePublish']) ? 'YES' : 'NO'?>
                    </div>

                    <div class="detail-item">
                        <span>INSIGNIA ID</span>
                        <div class="uuid"><?=ag_h($selected['InsigniaID'])?></div>
                    </div>

                    <div class="detail-item full">
                        <span>CHARTER</span>
                        <div class="charter">
                            <?= trim((string)$selected['Charter']) !== '' ? ag_h($selected['Charter']) : '&mdash;' ?>
                        </div>
                    </div>

                </div>

            </div>

            <div class="section-card">

                <h2>Members (<?=count($members)?>)</h2>

                <?php if (!$members): ?>

                    <div class="empty">No membership rows.</div>

                <?php else: ?>

                    <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>MEMBER</th>
                            <th>SELECTED ROLE</th>
                            <th>NOTICES</th>
                            <th>PROFILE</th>
                            <th>PRINCIPAL ID</th>
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
                            ?>

                            <tr>
                                <td>
                                    <strong>
                                        <?= $memberName !== '' ? ag_h($memberName) : 'HG / UNKNOWN MEMBER' ?>
                                    </strong>

                                    <?php if ($memberName !== ''): ?>
                                        <br>
                                        <span class="ag-muted">
                                            UserLevel <?=ag_h($member['UserLevel'])?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= trim((string)($member['SelectedRoleName'] ?? '')) !== ''
                                        ? ag_h($member['SelectedRoleName'])
                                        : 'Everyone / default' ?>
                                </td>

                                <td>
                                    <?=((int)$member['AcceptNotices']) ? 'YES' : 'NO'?>
                                </td>

                                <td>
                                    <?=((int)$member['ListInProfile']) ? 'YES' : 'NO'?>
                                </td>

                                <td class="uuid">
                                    <?=ag_h($member['PrincipalID'])?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                        </tbody>
                    </table>
                    </div>

                <?php endif; ?>

            </div>

            <div class="section-card">

                <h2>Roles (<?=count($roles)?>)</h2>

                <?php if (!$roles): ?>

                    <div class="empty">
                        No local role rows are stored for this group.
                    </div>

                <?php else: ?>

                    <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>ROLE</th>
                            <th>TITLE</th>
                            <th>DESCRIPTION</th>
                            <th>POWERS</th>
                            <th>ROLE ID</th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php foreach ($roles as $role): ?>

                            <tr>
                                <td><strong><?=ag_h($role['Name'])?></strong></td>
                                <td><?=ag_h($role['Title'])?></td>
                                <td><?=ag_h($role['Description'])?></td>
                                <td class="uuid"><?=ag_h($role['Powers'])?></td>
                                <td class="uuid"><?=ag_h($role['RoleID'])?></td>
                            </tr>

                        <?php endforeach; ?>

                        </tbody>
                    </table>
                    </div>

                <?php endif; ?>

            </div>

            <div class="section-card">

                <h2>Role Memberships (<?=count($roleMemberships)?>)</h2>

                <?php if (!$roleMemberships): ?>

                    <div class="empty">
                        No explicit role membership rows.
                    </div>

                <?php else: ?>

                    <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>MEMBER</th>
                            <th>ROLE</th>
                            <th>PRINCIPAL ID</th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php foreach ($roleMemberships as $membership): ?>

                            <?php
                            $memberName =
                                trim(
                                    (string)($membership['FirstName'] ?? '') .
                                    ' ' .
                                    (string)($membership['LastName'] ?? '')
                                );
                            ?>

                            <tr>
                                <td>
                                    <?= $memberName !== '' ? ag_h($memberName) : 'HG / UNKNOWN MEMBER' ?>
                                </td>
                                <td>
                                    <?= trim((string)($membership['RoleName'] ?? '')) !== ''
                                        ? ag_h($membership['RoleName'])
                                        : ag_h($membership['RoleID']) ?>
                                </td>
                                <td class="uuid">
                                    <?=ag_h($membership['PrincipalID'])?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                        </tbody>
                    </table>
                    </div>

                <?php endif; ?>

            </div>

            <div class="section-card">

                <h2>Recent Notices (<?=count($notices)?>)</h2>

                <?php if (!$notices): ?>

                    <div class="empty">No notices stored for this group.</div>

                <?php else: ?>

                    <?php foreach ($notices as $notice): ?>

                        <div class="notice-card">

                            <div class="notice-subject">
                                <?=ag_h($notice['Subject'])?>
                            </div>

                            <div class="notice-meta">
                                From <?=ag_h($notice['FromName'])?>
                                &nbsp; &bull; &nbsp;
                                <?= (int)$notice['TMStamp'] > 0
                                    ? ag_h(date('j M Y g:i A', (int)$notice['TMStamp']))
                                    : 'Unknown time' ?>

                                <?php if ((int)$notice['HasAttachment']): ?>
                                    &nbsp; &bull; &nbsp;
                                    Attachment: <?=ag_h($notice['AttachmentName'])?>
                                <?php endif; ?>
                            </div>

                            <?php if (trim((string)$notice['Message']) !== ''): ?>
                                <div class="notice-message">
                                    <?=ag_h($notice['Message'])?>
                                </div>
                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </div>

</div>

</div>

<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>
</body>
</html>





