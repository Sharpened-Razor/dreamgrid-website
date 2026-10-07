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


function validUuid(string $value): bool
{
    return (bool)preg_match(
        '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
        $value
    );
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