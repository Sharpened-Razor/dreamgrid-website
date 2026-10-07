<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/navigation.php';

ag_no_cache();

$session = ag_require_admin();
$avatar = ag_avatar_name($session);
$level = ag_user_level($session);
$nav = ag_admin_navigation();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['ag_group_manage_csrf'])) {
    $_SESSION['ag_group_manage_csrf'] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    (string)$_SESSION['ag_group_manage_csrf'];

$groupId =
    trim(
        (string)(
            $_POST['group_id'] ??
            $_GET['id'] ??
            ''
        )
    );

$error = '';
$success = '';

$zeroUuid =
    '00000000-0000-0000-0000-000000000000';

function validUuid(string $value): bool
{
    return (bool)preg_match(
        '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
        $value
    );
}

function qid(string $value): string
{
    return '`' .
        str_replace('`', '``', $value) .
        '`';
}

function loadGroup(
    mysqli $con,
    string $groupId,
    bool $forUpdate = false
): ?array {
    $sql =
        'SELECT g.*, ' .
        'ua.FirstName AS FounderFirstName, ' .
        'ua.LastName AS FounderLastName ' .
        'FROM os_groups_groups g ' .
        'LEFT JOIN UserAccounts ua ' .
        'ON ua.PrincipalID = g.FounderID ' .
        'WHERE g.GroupID = ? ' .
        'LIMIT 1';

    if ($forUpdate) {
        /*
         * MySQL does not allow FOR UPDATE cleanly after every LEFT JOIN
         * configuration, so lock the group row separately below.
         */
    }

    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $groupId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    if ($result) {
        mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);

    if ($forUpdate && $row) {
        $lock =
            mysqli_prepare(
                $con,
                'SELECT GroupID FROM os_groups_groups ' .
                'WHERE GroupID = ? FOR UPDATE'
            );

        if ($lock) {
            mysqli_stmt_bind_param(
                $lock,
                's',
                $groupId
            );

            mysqli_stmt_execute($lock);
            mysqli_stmt_store_result($lock);
            mysqli_stmt_close($lock);
        }
    }

    return $row ?: null;
}

function isLocalGroup(array $group): bool
{
    $founder =
        strtolower(
            trim(
                (string)$group['FounderID']
            )
        );

    return
        $founder !== '' &&
        $founder !==
            '00000000-0000-0000-0000-000000000000';
}

function writeGroupBackup(
    mysqli $con,
    array $group,
    string $reason
): string {
    $groupId =
        (string)$group['GroupID'];

    $safeName =
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            (string)$group['Name']
        ) ?? 'group';

    $root =
        rtrim(ag_dg_root(), '/\\') . DIRECTORY_SEPARATOR . '_GROUP_CHANGE_BACKUPS';

    if (
        !is_dir($root) &&
        !@mkdir($root, 0770, true)
    ) {
        throw new RuntimeException(
            'Could not create group-change backup directory.'
        );
    }

    $folder =
        $root .
        '/' .
        date('Ymd-His') .
        '-' .
        $safeName .
        '-' .
        $groupId;

    if (!@mkdir($folder, 0770, true)) {
        throw new RuntimeException(
            'Could not create this group backup folder.'
        );
    }

    $snapshot = [
        'format' =>
            'AUSTRALIA-GRID-GROUP-CHANGE-BACKUP-V1',
        'created_utc' => gmdate('c'),
        'reason' => $reason,
        'group_id' => $groupId,
        'rows' => [],
    ];

    $queries = [
        'os_groups_groups' =>
            'SELECT * FROM os_groups_groups WHERE GroupID = ?',
        'os_groups_membership' =>
            'SELECT * FROM os_groups_membership WHERE GroupID = ?',
        'os_groups_roles' =>
            'SELECT * FROM os_groups_roles WHERE GroupID = ?',
        'os_groups_rolemembership' =>
            'SELECT * FROM os_groups_rolemembership WHERE GroupID = ?',
        'os_groups_invites' =>
            'SELECT * FROM os_groups_invites WHERE GroupID = ?',
        'os_groups_notices' =>
            'SELECT * FROM os_groups_notices WHERE GroupID = ?',
        'os_groups_principals' =>
            'SELECT p.* FROM os_groups_principals p ' .
            'WHERE p.ActiveGroupID = ? ' .
            'OR p.PrincipalID IN (' .
                'SELECT m.PrincipalID ' .
                'FROM os_groups_membership m ' .
                'WHERE m.GroupID = ?' .
            ')',
    ];

    foreach ($queries as $name => $sql) {

        $stmt = mysqli_prepare($con, $sql);

        if (!$stmt) {
            throw new RuntimeException(
                'Could not prepare backup for ' . $name
            );
        }

        if ($name === 'os_groups_principals') {
            mysqli_stmt_bind_param(
                $stmt,
                'ss',
                $groupId,
                $groupId
            );
        }
        else {
            mysqli_stmt_bind_param(
                $stmt,
                's',
                $groupId
            );
        }

        if (!mysqli_stmt_execute($stmt)) {
            $message =
                mysqli_stmt_error($stmt);

            mysqli_stmt_close($stmt);

            throw new RuntimeException(
                'Could not read backup rows for ' .
                $name .
                ': ' .
                $message
            );
        }

        $result =
            mysqli_stmt_get_result($stmt);

        $rows = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }

            mysqli_free_result($result);
        }

        mysqli_stmt_close($stmt);

        $snapshot['rows'][$name] = $rows;
    }

    $json =
        json_encode(
            $snapshot,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES |
            JSON_INVALID_UTF8_SUBSTITUTE
        );

    if ($json === false) {
        throw new RuntimeException(
            'Could not encode the group backup.'
        );
    }

    $path =
        $folder .
        '/GROUP-BACKUP.json';

    if (
        @file_put_contents(
            $path,
            $json,
            LOCK_EX
        ) === false
    ) {
        throw new RuntimeException(
            'Could not write GROUP-BACKUP.json. Nothing was changed.'
        );
    }

    return $path;
}

function memberExists(
    mysqli $con,
    string $groupId,
    string $principalId
): bool {
    $stmt =
        mysqli_prepare(
            $con,
            'SELECT 1 FROM os_groups_membership ' .
            'WHERE GroupID = ? AND PrincipalID = ? ' .
            'LIMIT 1'
        );

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ss',
        $groupId,
        $principalId
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    $exists =
        mysqli_stmt_num_rows($stmt) > 0;

    mysqli_stmt_close($stmt);

    return $exists;
}

function ownerRoleMember(
    mysqli $con,
    array $group,
    string $principalId
): bool {
    $ownerRole =
        trim(
            (string)$group['OwnerRoleID']
        );

    if (
        $ownerRole === '' ||
        $ownerRole ===
            '00000000-0000-0000-0000-000000000000'
    ) {
        return false;
    }

    $stmt =
        mysqli_prepare(
            $con,
            'SELECT 1 FROM os_groups_rolemembership ' .
            'WHERE GroupID = ? AND RoleID = ? AND PrincipalID = ? ' .
            'LIMIT 1'
        );

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sss',
        $group['GroupID'],
        $ownerRole,
        $principalId
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    $isOwner =
        mysqli_stmt_num_rows($stmt) > 0;

    mysqli_stmt_close($stmt);

    return $isOwner;
}

if (!validUuid($groupId)) {
    $error =
        'Select a valid local group from Manage Groups.';
    $con = null;
    $group = null;
}
else {
    $con = ag_db_connect();

    if (!$con) {
        $error =
            'Grid database is unavailable.';
        $group = null;
    }
    else {
        mysqli_set_charset($con, 'utf8mb4');

        $group =
            loadGroup(
                $con,
                $groupId
            );

        if (!$group) {
            $error =
                'That group no longer exists.';
        }
        elseif (!isLocalGroup($group)) {
            $error =
                'HG / cached groups are read-only. Only proper local Grid groups can be managed here.';
        }
    }
}

if (
    $con &&
    $group &&
    $error === '' &&
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
) {
    $postedCsrf =
        (string)($_POST['csrf_token'] ?? '');

    $action =
        trim(
            (string)($_POST['action'] ?? '')
        );

    if (
        $postedCsrf === '' ||
        !hash_equals(
            $csrfToken,
            $postedCsrf
        )
    ) {
        $error =
            'Your security token expired. Reload the page and try again.';
    }
    elseif (
        !in_array(
            $action,
            [
                'save_settings',
                'add_member',
                'remove_member',
            ],
            true
        )
    ) {
        $error =
            'Unknown group-management action.';
    }
    else {
        try {
            mysqli_begin_transaction($con);

            $locked =
                loadGroup(
                    $con,
                    $groupId,
                    true
                );

            if (!$locked || !isLocalGroup($locked)) {
                throw new RuntimeException(
                    'The group changed before the update began.'
                );
            }

            if ($action === 'save_settings') {

                $charter =
                    trim(
                        (string)($_POST['charter'] ?? '')
                    );

                $insignia =
                    trim(
                        (string)($_POST['insignia_id'] ?? '')
                    );

                $feeText =
                    trim(
                        (string)($_POST['membership_fee'] ?? '0')
                    );

                if (strlen($charter) > 4096) {
                    throw new RuntimeException(
                        'Charter is too long.'
                    );
                }

                if ($insignia === '') {
                    $insignia = $zeroUuid;
                }

                if (!validUuid($insignia)) {
                    throw new RuntimeException(
                        'Insignia ID must be a valid UUID.'
                    );
                }

                if (
                    !preg_match(
                        '/^\d+$/',
                        $feeText
                    )
                ) {
                    throw new RuntimeException(
                        'Membership fee must be a whole number.'
                    );
                }

                $fee = (int)$feeText;

                if ($fee < 0 || $fee > 1000000) {
                    throw new RuntimeException(
                        'Membership fee must be between 0 and 1,000,000.'
                    );
                }

                $openEnrollment =
                    isset($_POST['open_enrollment'])
                        ? '1'
                        : '0';

                $showInList =
                    isset($_POST['show_in_list'])
                        ? 1
                        : 0;

                $allowPublish =
                    isset($_POST['allow_publish'])
                        ? 1
                        : 0;

                $maturePublish =
                    isset($_POST['mature_publish'])
                        ? 1
                        : 0;

                writeGroupBackup(
                    $con,
                    $locked,
                    'save_settings'
                );

                $stmt =
                    mysqli_prepare(
                        $con,
                        'UPDATE os_groups_groups SET ' .
                        'Charter = ?, ' .
                        'InsigniaID = ?, ' .
                        'MembershipFee = ?, ' .
                        'OpenEnrollment = ?, ' .
                        'ShowInList = ?, ' .
                        'AllowPublish = ?, ' .
                        'MaturePublish = ? ' .
                        'WHERE GroupID = ?'
                    );

                if (!$stmt) {
                    throw new RuntimeException(
                        'Could not prepare group settings update.'
                    );
                }

                mysqli_stmt_bind_param(
                    $stmt,
                    'ssisiiis',
                    $charter,
                    $insignia,
                    $fee,
                    $openEnrollment,
                    $showInList,
                    $allowPublish,
                    $maturePublish,
                    $groupId
                );

                if (!mysqli_stmt_execute($stmt)) {
                    $message =
                        mysqli_stmt_error($stmt);

                    mysqli_stmt_close($stmt);

                    throw new RuntimeException(
                        'Group settings update failed: ' .
                        $message
                    );
                }

                mysqli_stmt_close($stmt);

                mysqli_commit($con);

                $success =
                    'Group settings saved.';

            }
            elseif ($action === 'add_member') {

                $principalId =
                    trim(
                        (string)($_POST['principal_id'] ?? '')
                    );

                if (!validUuid($principalId)) {
                    throw new RuntimeException(
                        'Select a valid local account.'
                    );
                }

                $accountStmt =
                    mysqli_prepare(
                        $con,
                        'SELECT FirstName, LastName, UserLevel ' .
                        'FROM UserAccounts ' .
                        'WHERE PrincipalID = ? ' .
                        'LIMIT 1'
                    );

                if (!$accountStmt) {
                    throw new RuntimeException(
                        'Could not verify the local account.'
                    );
                }

                mysqli_stmt_bind_param(
                    $accountStmt,
                    's',
                    $principalId
                );

                mysqli_stmt_execute($accountStmt);

                $accountResult =
                    mysqli_stmt_get_result($accountStmt);

                $account =
                    $accountResult
                        ? mysqli_fetch_assoc($accountResult)
                        : null;

                if ($accountResult) {
                    mysqli_free_result($accountResult);
                }

                mysqli_stmt_close($accountStmt);

                if (!$account) {
                    throw new RuntimeException(
                        'That local account no longer exists.'
                    );
                }

                if ((int)$account['UserLevel'] < 0) {
                    throw new RuntimeException(
                        'Disabled accounts cannot be added to a group.'
                    );
                }

                if (
                    memberExists(
                        $con,
                        $groupId,
                        $principalId
                    )
                ) {
                    throw new RuntimeException(
                        'That avatar is already a member of this group.'
                    );
                }

                /*
                 * The Everyone role must exist for a proper local group.
                 */
                $everyoneStmt =
                    mysqli_prepare(
                        $con,
                        'SELECT 1 FROM os_groups_roles ' .
                        'WHERE GroupID = ? AND RoleID = ? ' .
                        'LIMIT 1'
                    );

                if (!$everyoneStmt) {
                    throw new RuntimeException(
                        'Could not verify the Everyone role.'
                    );
                }

                mysqli_stmt_bind_param(
                    $everyoneStmt,
                    'ss',
                    $groupId,
                    $zeroUuid
                );

                mysqli_stmt_execute($everyoneStmt);
                mysqli_stmt_store_result($everyoneStmt);

                $hasEveryone =
                    mysqli_stmt_num_rows($everyoneStmt) === 1;

                mysqli_stmt_close($everyoneStmt);

                if (!$hasEveryone) {
                    throw new RuntimeException(
                        'This local group is missing its Everyone role. No member was added.'
                    );
                }

                writeGroupBackup(
                    $con,
                    $locked,
                    'add_member:' . $principalId
                );

                $membershipStmt =
                    mysqli_prepare(
                        $con,
                        'INSERT INTO os_groups_membership ' .
                        '(GroupID, PrincipalID, SelectedRoleID, Contribution, ListInProfile, AcceptNotices, AccessToken) ' .
                        'VALUES (?, ?, ?, 0, 1, 1, ?)'
                    );

                if (!$membershipStmt) {
                    throw new RuntimeException(
                        'Could not prepare the membership insert.'
                    );
                }

                $accessToken = '';

                mysqli_stmt_bind_param(
                    $membershipStmt,
                    'ssss',
                    $groupId,
                    $principalId,
                    $zeroUuid,
                    $accessToken
                );

                if (!mysqli_stmt_execute($membershipStmt)) {
                    $message =
                        mysqli_stmt_error($membershipStmt);

                    mysqli_stmt_close($membershipStmt);

                    throw new RuntimeException(
                        'Could not add the group membership: ' .
                        $message
                    );
                }

                mysqli_stmt_close($membershipStmt);

                $roleStmt =
                    mysqli_prepare(
                        $con,
                        'INSERT INTO os_groups_rolemembership ' .
                        '(GroupID, RoleID, PrincipalID) ' .
                        'VALUES (?, ?, ?)'
                    );

                if (!$roleStmt) {
                    throw new RuntimeException(
                        'Could not prepare the Everyone role membership.'
                    );
                }

                mysqli_stmt_bind_param(
                    $roleStmt,
                    'sss',
                    $groupId,
                    $zeroUuid,
                    $principalId
                );

                if (!mysqli_stmt_execute($roleStmt)) {
                    $message =
                        mysqli_stmt_error($roleStmt);

                    mysqli_stmt_close($roleStmt);

                    throw new RuntimeException(
                        'Could not add the Everyone role: ' .
                        $message
                    );
                }

                mysqli_stmt_close($roleStmt);

                mysqli_commit($con);

                $memberName =
                    trim(
                        (string)$account['FirstName'] .
                        ' ' .
                        (string)$account['LastName']
                    );

                $success =
                    'Added ' .
                    $memberName .
                    ' to the group.';

            }
            elseif ($action === 'remove_member') {

                $principalId =
                    trim(
                        (string)($_POST['principal_id'] ?? '')
                    );

                if (!validUuid($principalId)) {
                    throw new RuntimeException(
                        'Invalid member UUID.'
                    );
                }

                if (
                    hash_equals(
                        strtolower(
                            (string)$locked['FounderID']
                        ),
                        strtolower($principalId)
                    )
                ) {
                    throw new RuntimeException(
                        'The group founder cannot be removed.'
                    );
                }

                if (
                    !memberExists(
                        $con,
                        $groupId,
                        $principalId
                    )
                ) {
                    throw new RuntimeException(
                        'That avatar is no longer a member.'
                    );
                }

                if (
                    ownerRoleMember(
                        $con,
                        $locked,
                        $principalId
                    )
                ) {
                    throw new RuntimeException(
                        'This member currently holds the Owners role. Remove that owner-role assignment in a later Roles phase before removing the member.'
                    );
                }

                writeGroupBackup(
                    $con,
                    $locked,
                    'remove_member:' . $principalId
                );

                $roleDelete =
                    mysqli_prepare(
                        $con,
                        'DELETE FROM os_groups_rolemembership ' .
                        'WHERE GroupID = ? AND PrincipalID = ?'
                    );

                if (!$roleDelete) {
                    throw new RuntimeException(
                        'Could not prepare role-membership cleanup.'
                    );
                }

                mysqli_stmt_bind_param(
                    $roleDelete,
                    'ss',
                    $groupId,
                    $principalId
                );

                if (!mysqli_stmt_execute($roleDelete)) {
                    $message =
                        mysqli_stmt_error($roleDelete);

                    mysqli_stmt_close($roleDelete);

                    throw new RuntimeException(
                        'Could not remove role memberships: ' .
                        $message
                    );
                }

                mysqli_stmt_close($roleDelete);

                $membershipDelete =
                    mysqli_prepare(
                        $con,
                        'DELETE FROM os_groups_membership ' .
                        'WHERE GroupID = ? AND PrincipalID = ?'
                    );

                if (!$membershipDelete) {
                    throw new RuntimeException(
                        'Could not prepare membership removal.'
                    );
                }

                mysqli_stmt_bind_param(
                    $membershipDelete,
                    'ss',
                    $groupId,
                    $principalId
                );

                if (!mysqli_stmt_execute($membershipDelete)) {
                    $message =
                        mysqli_stmt_error($membershipDelete);

                    mysqli_stmt_close($membershipDelete);

                    throw new RuntimeException(
                        'Could not remove the membership: ' .
                        $message
                    );
                }

                $removed =
                    mysqli_stmt_affected_rows($membershipDelete);

                mysqli_stmt_close($membershipDelete);

                if ($removed !== 1) {
                    throw new RuntimeException(
                        'Membership removal verification failed.'
                    );
                }

                /*
                 * If this was the avatar's active group, clear it.
                 * Do not delete the principals row.
                 */
                $principalStmt =
                    mysqli_prepare(
                        $con,
                        'UPDATE os_groups_principals ' .
                        'SET ActiveGroupID = ? ' .
                        'WHERE PrincipalID = ? AND ActiveGroupID = ?'
                    );

                if (!$principalStmt) {
                    throw new RuntimeException(
                        'Could not prepare active-group cleanup.'
                    );
                }

                mysqli_stmt_bind_param(
                    $principalStmt,
                    'sss',
                    $zeroUuid,
                    $principalId,
                    $groupId
                );

                if (!mysqli_stmt_execute($principalStmt)) {
                    $message =
                        mysqli_stmt_error($principalStmt);

                    mysqli_stmt_close($principalStmt);

                    throw new RuntimeException(
                        'Could not clear the removed member active group: ' .
                        $message
                    );
                }

                mysqli_stmt_close($principalStmt);

                mysqli_commit($con);

                $success =
                    'Member removed from the group.';
            }

            $_SESSION['ag_group_manage_csrf'] =
                bin2hex(random_bytes(32));

            $csrfToken =
                (string)$_SESSION['ag_group_manage_csrf'];

            $group =
                loadGroup(
                    $con,
                    $groupId
                );
        }
        catch (Throwable $e) {
            @mysqli_rollback($con);

            $error =
                'Group change stopped and rolled back: ' .
                $e->getMessage();
        }
    }
}

$members = [];
$availableAccounts = [];

if (
    $con &&
    $group &&
    isLocalGroup($group)
) {
    $memberSql =
        'SELECT ' .
        'm.PrincipalID, m.SelectedRoleID, m.ListInProfile, m.AcceptNotices, ' .
        'ua.FirstName, ua.LastName, ua.UserLevel, ' .
        'EXISTS(' .
            'SELECT 1 FROM os_groups_rolemembership rm ' .
            'WHERE rm.GroupID = m.GroupID ' .
            'AND rm.PrincipalID = m.PrincipalID ' .
            'AND rm.RoleID = ?' .
        ') AS IsOwner ' .
        'FROM os_groups_membership m ' .
        'LEFT JOIN UserAccounts ua ' .
        'ON ua.PrincipalID = m.PrincipalID ' .
        'WHERE m.GroupID = ? ' .
        "ORDER BY COALESCE(ua.FirstName,''), COALESCE(ua.LastName,''), m.PrincipalID";

    $stmt =
        mysqli_prepare(
            $con,
            $memberSql
        );

    if ($stmt) {
        $ownerRoleId =
            (string)$group['OwnerRoleID'];

        mysqli_stmt_bind_param(
            $stmt,
            'ss',
            $ownerRoleId,
            $groupId
        );

        mysqli_stmt_execute($stmt);
        $result =
            mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $members[] = $row;
            }

            mysqli_free_result($result);
        }

        mysqli_stmt_close($stmt);
    }

    $availableSql =
        'SELECT PrincipalID, FirstName, LastName, UserLevel ' .
        'FROM UserAccounts ua ' .
        'WHERE ua.UserLevel >= 0 ' .
        'AND NOT EXISTS (' .
            'SELECT 1 FROM os_groups_membership m ' .
            'WHERE m.GroupID = ? ' .
            'AND m.PrincipalID = ua.PrincipalID' .
        ') ' .
        'ORDER BY FirstName, LastName';

    $stmt =
        mysqli_prepare(
            $con,
            $availableSql
        );

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            's',
            $groupId
        );

        mysqli_stmt_execute($stmt);
        $result =
            mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $availableAccounts[] = $row;
            }

            mysqli_free_result($result);
        }

        mysqli_stmt_close($stmt);
    }
}

if ($con) {
    mysqli_close($con);
}

$groupName =
    $group
        ? (string)$group['Name']
        : '';

$founderName =
    $group
        ? trim(
            (string)($group['FounderFirstName'] ?? '') .
            ' ' .
            (string)($group['FounderLastName'] ?? '')
        )
        : '';

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Group</title>
<link rel="stylesheet" href="/Other/core/site-shell.css?v=1">

<style>
.manage-card{
    max-width:1050px;
    margin:0 auto 16px;
    padding:20px;
    border:1px solid rgba(255,255,255,.11);
    border-radius:14px;
    background:rgba(8,13,16,.82);
}
.manage-card h2{
    margin-top:0;
}
.form-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:14px;
}
.field.full{
    grid-column:1/-1;
}
.field label{
    display:block;
    margin-bottom:6px;
    color:#96a9b3;
    font-size:11px;
    font-weight:900;
    letter-spacing:.05em;
}
.field input[type=text],
.field input[type=number],
.field select,
.field textarea{
    width:100%;
    box-sizing:border-box;
    padding:10px 12px;
    border:1px solid rgba(255,255,255,.15);
    border-radius:9px;
    color:#fff;
    background:#080d10;
}
.field textarea{
    min-height:120px;
    resize:vertical;
}
.check-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:10px;
    margin:15px 0;
}
.check{
    padding:11px;
    border:1px solid rgba(255,255,255,.11);
    border-radius:9px;
}
.member-table{
    width:100%;
    border-collapse:collapse;
}
.member-table th,
.member-table td{
    padding:10px;
    border-bottom:1px solid rgba(255,255,255,.09);
    text-align:left;
    vertical-align:middle;
}
.member-table th{
    color:#96a9b3;
    font-size:11px;
}
.uuid{
    font-family:Consolas,monospace;
    font-size:11px;
    overflow-wrap:anywhere;
}
.danger-button{
    min-height:38px;
    padding:0 12px;
    border:1px solid #a64b4b;
    border-radius:8px;
    color:#ffdcdc;
    background:#421919;
    font-weight:900;
    cursor:pointer;
}
.actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:14px;
}
.info{
    margin-bottom:16px;
    padding:13px 15px;
    border:1px solid rgba(244,179,35,.25);
    border-radius:10px;
    color:#f7dda0;
    background:rgba(244,179,35,.06);
}
@media(max-width:700px){
    .form-grid,
    .check-grid{
        grid-template-columns:1fr;
    }
    .field.full{
        grid-column:auto;
    }
}
</style>

<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">
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


<style id="australia-user-level-badge-v1">

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


</style>

</head>
<body>

<div class="ag-shell">

<?php
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "Manage Group";
$siteHeaderRole = "GRID OWNER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "ADMIN HOME";
$siteHeaderLink = "/Other/admin-home.php";
require_once __DIR__ . "/includes/site-header.php";
?>

<nav class="ag-nav">
<?php foreach ($nav as $item): ?>
    <a href="<?=ag_h($item['url'])?>"><?=ag_h($item['label'])?></a>
<?php endforeach; ?>
</nav>

<?php if ($error !== ''): ?>
    <div class="ag-error" style="max-width:1050px;margin:0 auto 16px">
        <?=ag_h($error)?>
    </div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="ag-success" style="max-width:1050px;margin:0 auto 16px">
        <?=ag_h($success)?>
    </div>
<?php endif; ?>

<?php if (!$group): ?>

    <div class="manage-card">
        <a class="ag-button" href="<?=ag_h(ag_route('admin_groups'))?>">
            BACK TO MANAGE GROUPS
        </a>
    </div>

<?php elseif (!isLocalGroup($group)): ?>

    <div class="manage-card">

        <div class="info">
            <strong><?=ag_h($groupName)?></strong> is an HG / cached group.
            It remains read-only so the local grid does not overwrite data
            belonging to another grid.
        </div>

        <a
            class="ag-button"
            href="<?=ag_h(ag_route('admin_groups'))?>?id=<?=rawurlencode($groupId)?>"
        >
            BACK TO GROUP
        </a>

    </div>

<?php else: ?>

    <div class="manage-card">

        <h2><?=ag_h($groupName)?></h2>

        <div class="info">
            Founder:
            <strong><?= $founderName !== '' ? ag_h($founderName) : ag_h($group['FounderID']) ?></strong>.
            Group name, founder and role powers are intentionally locked in Phase 4B.
        </div>

        <form method="post" action="<?=ag_h(ag_route('admin_group_manage'))?>">

            <input type="hidden" name="csrf_token" value="<?=ag_h($csrfToken)?>">
            <input type="hidden" name="group_id" value="<?=ag_h($groupId)?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="form-grid">

                <div class="field full">
                    <label>CHARTER</label>
                    <textarea name="charter" maxlength="4096"><?=ag_h($group['Charter'])?></textarea>
                </div>

                <div class="field">
                    <label>INSIGNIA UUID</label>
                    <input
                        type="text"
                        name="insignia_id"
                        maxlength="36"
                        value="<?=ag_h($group['InsigniaID'])?>">
                </div>

                <div class="field">
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

            <div class="check-grid">

                <label class="check">
                    <input
                        type="checkbox"
                        name="open_enrollment"
                        value="1"
                        <?=((string)$group['OpenEnrollment'] === '1') ? 'checked' : ''?>>
                    Open Enrollment
                </label>

                <label class="check">
                    <input
                        type="checkbox"
                        name="show_in_list"
                        value="1"
                        <?=((int)$group['ShowInList']) ? 'checked' : ''?>>
                    Show In List
                </label>

                <label class="check">
                    <input
                        type="checkbox"
                        name="allow_publish"
                        value="1"
                        <?=((int)$group['AllowPublish']) ? 'checked' : ''?>>
                    Allow Publish
                </label>

                <label class="check">
                    <input
                        type="checkbox"
                        name="mature_publish"
                        value="1"
                        <?=((int)$group['MaturePublish']) ? 'checked' : ''?>>
                    Mature Publish
                </label>

            </div>

            <div class="actions">
                <button class="ag-button primary" type="submit">
                    SAVE GROUP SETTINGS
                </button>

                <a
                    class="ag-button"
                    href="<?=ag_h(ag_route('admin_groups'))?>?id=<?=rawurlencode($groupId)?>"
                >
                    BACK TO GROUP
                </a>
            </div>

        </form>

    </div>

    <div class="manage-card">

        <h2>Add Local Member</h2>

        <?php if (!$availableAccounts): ?>

            <div class="ag-muted">
                Every enabled local account is already a member, or no eligible
                local accounts are available.
            </div>

        <?php else: ?>

            <form method="post" action="<?=ag_h(ag_route('admin_group_manage'))?>">

                <input type="hidden" name="csrf_token" value="<?=ag_h($csrfToken)?>">
                <input type="hidden" name="group_id" value="<?=ag_h($groupId)?>">
                <input type="hidden" name="action" value="add_member">

                <div class="field">
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

                <div class="actions">
                    <button
                        class="ag-button primary"
                        type="submit"
                        onclick="return confirm('Add this avatar to <?=ag_h(addslashes($groupName))?>?');"
                    >
                        ADD MEMBER
                    </button>
                </div>

            </form>

        <?php endif; ?>

    </div>

    <div class="manage-card">

        <h2>Current Members (<?=count($members)?>)</h2>

        <div style="overflow:auto">

            <table class="member-table">

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

                        <td class="uuid">
                            <?=ag_h($member['PrincipalID'])?>
                        </td>

                        <td>

                            <?php if ($canRemove): ?>

                                <form
                                    method="post"
                                    action="<?=ag_h(ag_route('admin_group_manage'))?>"
                                    style="margin:0">

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
                                        class="danger-button"
                                        type="submit"
                                        onclick="return confirm('Remove this member from <?=ag_h(addslashes($groupName))?>?');"
                                    >
                                        REMOVE
                                    </button>

                                </form>

                            <?php else: ?>

                                <span class="ag-muted">
                                    Protected
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

    <div class="manage-card" style="border-color:rgba(255,96,96,.30)">

        <h2>Delete Group</h2>

        <div class="info" style="border-color:rgba(255,96,96,.30);color:#ffd1d1;background:rgba(126,31,31,.12)">
            Permanent group deletion is handled on a separate safety page.
            Check land and in-world objects before deleting a group.
        </div>

        <a
            class="danger-button"
            style="display:inline-flex;align-items:center;text-decoration:none"
            href="<?=ag_h(ag_route('admin_group_delete'))?>?id=<?=rawurlencode($groupId)?>"
        >
            DELETE GROUP
        </a>

    </div>

<?php endif; ?>

</div>

<script defer src="/Other/core/ag-confirm-modal.js?v=1"></script>

<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>
</body>
</html>





