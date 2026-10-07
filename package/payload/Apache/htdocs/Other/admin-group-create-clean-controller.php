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

if (empty($_SESSION['ag_group_create_csrf'])) {
    $_SESSION['ag_group_create_csrf'] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    (string)$_SESSION['ag_group_create_csrf'];

$error = '';

$zeroUuid =
    '00000000-0000-0000-0000-000000000000';

$everyonePowers =
    '62672565501952';

$officerPowers =
    '560750921775310';

$ownerPowers =
    '4503599627370494';

$name =
    trim(
        (string)($_POST['name'] ?? '')
    );

$charter =
    trim(
        (string)($_POST['charter'] ?? '')
    );

$insigniaId =
    trim(
        (string)($_POST['insignia_id'] ?? $zeroUuid)
    );

$membershipFee =
    trim(
        (string)($_POST['membership_fee'] ?? '0')
    );

$founderId =
    trim(
        (string)($_POST['founder_id'] ?? '')
    );

function validUuid(string $value): bool
{
    return (bool)preg_match(
        '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
        $value
    );
}

function uuidV4(): string
{
    $bytes = random_bytes(16);

    $bytes[6] =
        chr(
            (ord($bytes[6]) & 0x0f) |
            0x40
        );

    $bytes[8] =
        chr(
            (ord($bytes[8]) & 0x3f) |
            0x80
        );

    $hex = bin2hex($bytes);

    return sprintf(
        '%s-%s-%s-%s-%s',
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20, 12)
    );
}

function fetchFounder(
    mysqli $con,
    string $principalId
): ?array {
    $stmt =
        mysqli_prepare(
            $con,
            'SELECT PrincipalID, FirstName, LastName, UserLevel ' .
            'FROM UserAccounts ' .
            'WHERE PrincipalID = ? ' .
            'LIMIT 1'
        );

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param(
        $stmt,
        's',
        $principalId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $row =
        $result
            ? mysqli_fetch_assoc($result)
            : null;

    if ($result) {
        mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function groupNameExists(
    mysqli $con,
    string $name
): bool {
    $stmt =
        mysqli_prepare(
            $con,
            'SELECT 1 FROM os_groups_groups ' .
            'WHERE Name = ? ' .
            'LIMIT 1'
        );

    if (!$stmt) {
        return true;
    }

    mysqli_stmt_bind_param(
        $stmt,
        's',
        $name
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    $exists =
        mysqli_stmt_num_rows($stmt) > 0;

    mysqli_stmt_close($stmt);

    return $exists;
}

function writeCreateBackup(
    mysqli $con,
    array $founder,
    array $intent
): string {
    $root =
        rtrim(ag_dg_root(), '/\\') . DIRECTORY_SEPARATOR . '_GROUP_CHANGE_BACKUPS';

    if (
        !is_dir($root) &&
        !@mkdir($root, 0770, true)
    ) {
        throw new RuntimeException(
            'Could not create group backup directory.'
        );
    }

    $safeName =
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            (string)$intent['Name']
        ) ?? 'group';

    $folder =
        $root .
        '/' .
        date('Ymd-His') .
        '-CREATE-' .
        $safeName .
        '-' .
        (string)$intent['GroupID'];

    if (!@mkdir($folder, 0770, true)) {
        throw new RuntimeException(
            'Could not create this Create Group backup folder.'
        );
    }

    $principalRows = [];

    $stmt =
        mysqli_prepare(
            $con,
            'SELECT * FROM os_groups_principals ' .
            'WHERE PrincipalID = ?'
        );

    if (!$stmt) {
        throw new RuntimeException(
            'Could not prepare founder-principal backup.'
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        's',
        $founder['PrincipalID']
    );

    mysqli_stmt_execute($stmt);
    $result =
        mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $principalRows[] = $row;
        }

        mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);

    $snapshot = [
        'format' =>
            'AUSTRALIA-GRID-CREATE-GROUP-BACKUP-V1',
        'created_utc' => gmdate('c'),
        'founder' => $founder,
        'previous_principal_rows' =>
            $principalRows,
        'intended_group' => $intent,
    ];

    $json =
        json_encode(
            $snapshot,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES |
            JSON_INVALID_UTF8_SUBSTITUTE
        );

    if ($json === false) {
        throw new RuntimeException(
            'Could not encode Create Group backup.'
        );
    }

    $path =
        $folder .
        '/GROUP-CREATE-BACKUP.json';

    if (
        @file_put_contents(
            $path,
            $json,
            LOCK_EX
        ) === false
    ) {
        throw new RuntimeException(
            'Could not write Create Group backup. Nothing was created.'
        );
    }

    return $path;
}

$con = ag_db_connect();

if (!$con) {
    http_response_code(500);
    exit('Grid database is unavailable.');
}

mysqli_set_charset($con, 'utf8mb4');

$founders = [];

$result =
    mysqli_query(
        $con,
        'SELECT PrincipalID, FirstName, LastName, UserLevel ' .
        'FROM UserAccounts ' .
        'WHERE UserLevel >= 0 ' .
        'ORDER BY FirstName, LastName'
    );

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $founders[] = $row;
    }

    mysqli_free_result($result);
}

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
) {
    $postedCsrf =
        (string)($_POST['csrf_token'] ?? '');

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
        $name === '' ||
        strlen($name) > 255
    ) {
        $error =
            'Group name must contain 1 to 255 characters.';
    }
    elseif (
        preg_match(
            '/[\x00-\x1F\x7F]/',
            $name
        )
    ) {
        $error =
            'Group name contains invalid control characters.';
    }
    elseif (strlen($charter) > 4096) {
        $error =
            'Charter is too long.';
    }
    elseif (!validUuid($founderId)) {
        $error =
            'Select a valid local founder.';
    }
    elseif (
        $insigniaId !== '' &&
        !validUuid($insigniaId)
    ) {
        $error =
            'Insignia ID must be a valid UUID.';
    }
    elseif (
        !preg_match(
            '/^\d+$/',
            $membershipFee
        )
    ) {
        $error =
            'Membership fee must be a whole number.';
    }
    elseif (
        (int)$membershipFee < 0 ||
        (int)$membershipFee > 1000000
    ) {
        $error =
            'Membership fee must be between 0 and 1,000,000.';
    }
    else {
        if ($insigniaId === '') {
            $insigniaId =
                $zeroUuid;
        }

        $founder =
            fetchFounder(
                $con,
                $founderId
            );

        if (!$founder) {
            $error =
                'The selected founder no longer exists.';
        }
        elseif ((int)$founder['UserLevel'] < 0) {
            $error =
                'A disabled account cannot found a group.';
        }
        elseif (
            groupNameExists(
                $con,
                $name
            )
        ) {
            $error =
                'A group with that name already exists.';
        }
        else {
            $groupId =
                uuidV4();

            $ownerRoleId =
                uuidV4();

            $officerRoleId =
                uuidV4();

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

            $intent = [
                'GroupID' => $groupId,
                'OwnerRoleID' => $ownerRoleId,
                'OfficerRoleID' => $officerRoleId,
                'Name' => $name,
                'Charter' => $charter,
                'InsigniaID' => $insigniaId,
                'FounderID' => $founderId,
                'MembershipFee' =>
                    (int)$membershipFee,
                'OpenEnrollment' =>
                    $openEnrollment,
                'ShowInList' => $showInList,
                'AllowPublish' => $allowPublish,
                'MaturePublish' => $maturePublish,
                'EveryonePowers' =>
                    $everyonePowers,
                'OfficerPowers' =>
                    $officerPowers,
                'OwnerPowers' =>
                    $ownerPowers,
            ];

            try {
                mysqli_begin_transaction($con);

                /*
                 * Re-check duplicate name under transaction.
                 */
                if (
                    groupNameExists(
                        $con,
                        $name
                    )
                ) {
                    throw new RuntimeException(
                        'A group with that name was created before this transaction started.'
                    );
                }

                /*
                 * Lock founder.
                 */
                $founderLock =
                    mysqli_prepare(
                        $con,
                        'SELECT PrincipalID, UserLevel ' .
                        'FROM UserAccounts ' .
                        'WHERE PrincipalID = ? ' .
                        'FOR UPDATE'
                    );

                if (!$founderLock) {
                    throw new RuntimeException(
                        'Could not lock founder account.'
                    );
                }

                mysqli_stmt_bind_param(
                    $founderLock,
                    's',
                    $founderId
                );

                mysqli_stmt_execute($founderLock);
                $founderResult =
                    mysqli_stmt_get_result($founderLock);

                $founderLocked =
                    $founderResult
                        ? mysqli_fetch_assoc($founderResult)
                        : null;

                if ($founderResult) {
                    mysqli_free_result($founderResult);
                }

                mysqli_stmt_close($founderLock);

                if (
                    !$founderLocked ||
                    (int)$founderLocked['UserLevel'] < 0
                ) {
                    throw new RuntimeException(
                        'Founder account is no longer eligible.'
                    );
                }

                /*
                 * Backup founder principal state + intended creation
                 * before first database write.
                 */
                writeCreateBackup(
                    $con,
                    $founder,
                    $intent
                );

                $groupStmt =
                    mysqli_prepare(
                        $con,
                        'INSERT INTO os_groups_groups ' .
                        '(GroupID, Location, Name, Charter, InsigniaID, FounderID, ' .
                        'MembershipFee, OpenEnrollment, ShowInList, AllowPublish, MaturePublish, OwnerRoleID) ' .
                        'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );

                if (!$groupStmt) {
                    throw new RuntimeException(
                        'Could not prepare group record creation.'
                    );
                }

                $location = '';
                $fee = (int)$membershipFee;

                mysqli_stmt_bind_param(
                    $groupStmt,
                    'ssssssisiiis',
                    $groupId,
                    $location,
                    $name,
                    $charter,
                    $insigniaId,
                    $founderId,
                    $fee,
                    $openEnrollment,
                    $showInList,
                    $allowPublish,
                    $maturePublish,
                    $ownerRoleId
                );

                if (!mysqli_stmt_execute($groupStmt)) {
                    $message =
                        mysqli_stmt_error($groupStmt);

                    mysqli_stmt_close($groupStmt);

                    throw new RuntimeException(
                        'Could not create group record: ' .
                        $message
                    );
                }

                mysqli_stmt_close($groupStmt);

                $roleStmt =
                    mysqli_prepare(
                        $con,
                        'INSERT INTO os_groups_roles ' .
                        '(GroupID, RoleID, Name, Description, Title, Powers) ' .
                        'VALUES (?, ?, ?, ?, ?, ?)'
                    );

                if (!$roleStmt) {
                    throw new RuntimeException(
                        'Could not prepare default roles.'
                    );
                }

                $roleId = $zeroUuid;
                $roleName = 'Everyone';
                $roleDescription =
                    'Everyone in the group is in the everyone role.';
                $roleTitle =
                    'Member of ' . $name;
                $rolePowers =
                    $everyonePowers;

                mysqli_stmt_bind_param(
                    $roleStmt,
                    'ssssss',
                    $groupId,
                    $roleId,
                    $roleName,
                    $roleDescription,
                    $roleTitle,
                    $rolePowers
                );

                if (!mysqli_stmt_execute($roleStmt)) {
                    throw new RuntimeException(
                        'Could not create Everyone role.'
                    );
                }

                $roleId =
                    $officerRoleId;
                $roleName =
                    'Officers';
                $roleDescription =
                    'The officers of the group, with more powers than regular members.';
                $roleTitle =
                    'Officer of ' . $name;
                $rolePowers =
                    $officerPowers;

                if (!mysqli_stmt_execute($roleStmt)) {
                    throw new RuntimeException(
                        'Could not create Officers role.'
                    );
                }

                $roleId =
                    $ownerRoleId;
                $roleName =
                    'Owners';
                $roleDescription =
                    'Owners of the group';
                $roleTitle =
                    'Owner of ' . $name;
                $rolePowers =
                    $ownerPowers;

                if (!mysqli_stmt_execute($roleStmt)) {
                    throw new RuntimeException(
                        'Could not create Owners role.'
                    );
                }

                mysqli_stmt_close($roleStmt);

                $accessToken = '';

                $membershipStmt =
                    mysqli_prepare(
                        $con,
                        'INSERT INTO os_groups_membership ' .
                        '(GroupID, PrincipalID, SelectedRoleID, Contribution, ListInProfile, AcceptNotices, AccessToken) ' .
                        'VALUES (?, ?, ?, 0, 1, 1, ?)'
                    );

                if (!$membershipStmt) {
                    throw new RuntimeException(
                        'Could not prepare founder membership.'
                    );
                }

                mysqli_stmt_bind_param(
                    $membershipStmt,
                    'ssss',
                    $groupId,
                    $founderId,
                    $ownerRoleId,
                    $accessToken
                );

                if (!mysqli_stmt_execute($membershipStmt)) {
                    throw new RuntimeException(
                        'Could not create founder membership.'
                    );
                }

                mysqli_stmt_close($membershipStmt);

                $roleMembershipStmt =
                    mysqli_prepare(
                        $con,
                        'INSERT INTO os_groups_rolemembership ' .
                        '(GroupID, RoleID, PrincipalID) ' .
                        'VALUES (?, ?, ?)'
                    );

                if (!$roleMembershipStmt) {
                    throw new RuntimeException(
                        'Could not prepare founder role memberships.'
                    );
                }

                $founderRoleId =
                    $zeroUuid;

                mysqli_stmt_bind_param(
                    $roleMembershipStmt,
                    'sss',
                    $groupId,
                    $founderRoleId,
                    $founderId
                );

                if (!mysqli_stmt_execute($roleMembershipStmt)) {
                    throw new RuntimeException(
                        'Could not add founder to Everyone role.'
                    );
                }

                $founderRoleId =
                    $ownerRoleId;

                if (!mysqli_stmt_execute($roleMembershipStmt)) {
                    throw new RuntimeException(
                        'Could not add founder to Owners role.'
                    );
                }

                mysqli_stmt_close($roleMembershipStmt);

                $principalStmt =
                    mysqli_prepare(
                        $con,
                        'INSERT INTO os_groups_principals ' .
                        '(PrincipalID, ActiveGroupID) ' .
                        'VALUES (?, ?) ' .
                        'ON DUPLICATE KEY UPDATE ActiveGroupID = VALUES(ActiveGroupID)'
                    );

                if (!$principalStmt) {
                    throw new RuntimeException(
                        'Could not prepare founder active-group update.'
                    );
                }

                mysqli_stmt_bind_param(
                    $principalStmt,
                    'ss',
                    $founderId,
                    $groupId
                );

                if (!mysqli_stmt_execute($principalStmt)) {
                    throw new RuntimeException(
                        'Could not make the new group active for the founder.'
                    );
                }

                mysqli_stmt_close($principalStmt);

                /*
                 * Exact verification before commit.
                 */
                $checks = [
                    [
                        'sql' =>
                            'SELECT COUNT(*) AS c FROM os_groups_groups WHERE GroupID = ?',
                        'expected' => 1,
                    ],
                    [
                        'sql' =>
                            'SELECT COUNT(*) AS c FROM os_groups_roles WHERE GroupID = ?',
                        'expected' => 3,
                    ],
                    [
                        'sql' =>
                            'SELECT COUNT(*) AS c FROM os_groups_membership WHERE GroupID = ? AND PrincipalID = ?',
                        'expected' => 1,
                        'founder' => true,
                    ],
                    [
                        'sql' =>
                            'SELECT COUNT(*) AS c FROM os_groups_rolemembership WHERE GroupID = ? AND PrincipalID = ?',
                        'expected' => 2,
                        'founder' => true,
                    ],
                    [
                        'sql' =>
                            'SELECT COUNT(*) AS c FROM os_groups_principals WHERE PrincipalID = ? AND ActiveGroupID = ?',
                        'expected' => 1,
                        'principal' => true,
                    ],
                ];

                foreach ($checks as $check) {
                    $verify =
                        mysqli_prepare(
                            $con,
                            $check['sql']
                        );

                    if (!$verify) {
                        throw new RuntimeException(
                            'Could not prepare creation verification.'
                        );
                    }

                    if (!empty($check['principal'])) {
                        mysqli_stmt_bind_param(
                            $verify,
                            'ss',
                            $founderId,
                            $groupId
                        );
                    }
                    elseif (!empty($check['founder'])) {
                        mysqli_stmt_bind_param(
                            $verify,
                            'ss',
                            $groupId,
                            $founderId
                        );
                    }
                    else {
                        mysqli_stmt_bind_param(
                            $verify,
                            's',
                            $groupId
                        );
                    }

                    mysqli_stmt_execute($verify);
                    $verifyResult =
                        mysqli_stmt_get_result($verify);

                    $verifyRow =
                        $verifyResult
                            ? mysqli_fetch_assoc($verifyResult)
                            : null;

                    if ($verifyResult) {
                        mysqli_free_result($verifyResult);
                    }

                    mysqli_stmt_close($verify);

                    if (
                        !$verifyRow ||
                        (int)$verifyRow['c'] !==
                            (int)$check['expected']
                    ) {
                        throw new RuntimeException(
                            'Create Group verification failed. Transaction rolled back.'
                        );
                    }
                }

                mysqli_commit($con);

                $_SESSION['ag_group_create_csrf'] =
                    bin2hex(random_bytes(32));

                mysqli_close($con);

                header(
                    'Location: ' .
                    '/Other/admin-groups-clean.php' .
                    '?' .
                    http_build_query(
                        [
                            'id' => $groupId,
                            'created' => '1',
                        ]
                    ),
                    true,
                    303
                );

                exit;
            }
            catch (Throwable $e) {
                @mysqli_rollback($con);

                $error =
                    'Group creation stopped and rolled back: ' .
                    $e->getMessage();
            }
        }
    }
}

mysqli_close($con);

?>
