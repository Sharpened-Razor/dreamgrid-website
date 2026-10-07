<?php
require_once __DIR__ . '/core/dreamgrid-env.php';


require_once __DIR__ . '/core/bootstrap.php';
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
