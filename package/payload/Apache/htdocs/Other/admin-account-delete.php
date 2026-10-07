<?php

require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/navigation.php';

ag_no_cache();

$session = ag_require_admin();
$avatar = ag_avatar_name($session);
$level = ag_user_level($session);
$adminPrincipalId = trim((string)($session['principalId'] ?? ''));
$nav = ag_admin_navigation();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['ag_delete_account_csrf'])) {
    $_SESSION['ag_delete_account_csrf'] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    (string)$_SESSION['ag_delete_account_csrf'];

$targetId =
    trim(
        (string)(
            $_POST['principal_id'] ??
            $_GET['id'] ??
            ''
        )
    );

$error = '';
$target = null;
$blockers = [];
$counts = [];
$backupPath = '';

function validPrincipalId(string $value): bool
{
    return (bool)preg_match(
        '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}[0-9a-fA-F]{8}$/',
        $value
    );
}

function tableExists(
    mysqli $con,
    string $table
): bool {
    $sql =
        'SELECT COUNT(*) ' .
        'FROM information_schema.TABLES ' .
        'WHERE TABLE_SCHEMA = DATABASE() ' .
        'AND TABLE_NAME = ?';

    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 's', $table);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return (int)$count > 0;
}

function columnExists(
    mysqli $con,
    string $table,
    string $column
): bool {
    $sql =
        'SELECT COUNT(*) ' .
        'FROM information_schema.COLUMNS ' .
        'WHERE TABLE_SCHEMA = DATABASE() ' .
        'AND TABLE_NAME = ? ' .
        'AND COLUMN_NAME = ?';

    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ss',
        $table,
        $column
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return (int)$count > 0;
}

function quoteIdentifier(string $name): string
{
    return '`' .
        str_replace('`', '``', $name) .
        '`';
}

function countWhere(
    mysqli $con,
    string $table,
    string $where,
    array $params
): int {
    if (!tableExists($con, $table)) {
        return 0;
    }

    $sql =
        'SELECT COUNT(*) AS c FROM ' .
        quoteIdentifier($table) .
        ' WHERE ' .
        $where;

    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        return 0;
    }

    if ($params) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param(
            $stmt,
            $types,
            ...$params
        );
    }

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

function fetchRows(
    mysqli $con,
    string $table,
    string $where,
    array $params
): array {
    if (!tableExists($con, $table)) {
        return [];
    }

    $sql =
        'SELECT * FROM ' .
        quoteIdentifier($table) .
        ' WHERE ' .
        $where;

    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Could not prepare backup read for ' . $table
        );
    }

    if ($params) {
        $types = str_repeat('s', count($params));

        mysqli_stmt_bind_param(
            $stmt,
            $types,
            ...$params
        );
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException(
            'Could not read backup rows for ' . $table
        );
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

        mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);

    return $rows;
}

function executeDelete(
    mysqli $con,
    string $table,
    string $where,
    array $params
): int {
    if (!tableExists($con, $table)) {
        return 0;
    }

    $sql =
        'DELETE FROM ' .
        quoteIdentifier($table) .
        ' WHERE ' .
        $where;

    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Could not prepare delete for ' . $table
        );
    }

    if ($params) {
        $types = str_repeat('s', count($params));

        mysqli_stmt_bind_param(
            $stmt,
            $types,
            ...$params
        );
    }

    if (!mysqli_stmt_execute($stmt)) {
        $message = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        throw new RuntimeException(
            'Delete failed for ' .
            $table .
            ': ' .
            $message
        );
    }

    $affected = mysqli_stmt_affected_rows($stmt);

    mysqli_stmt_close($stmt);

    return (int)$affected;
}

function buildDeleteSpecs(
    mysqli $con,
    string $uuid
): array {
    $friendPrefix = $uuid . ';%';

    /*
     * creatorID rows in other avatars' inventories are deliberately
     * NOT included. We delete inventory by avatarID only.
     */
    return [
        [
            'table' => 'agentprefs',
            'where' => '`PrincipalID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'presence',
            'where' => '`UserID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'griduser',
            'where' => '`UserID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'hg_traveling_data',
            'where' => '`UserID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'im_offline',
            'where' => '`PrincipalID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'mutelist',
            'where' => '`AgentID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'friends',
            'where' =>
                '`PrincipalID` = ? ' .
                'OR `Friend` = ? ' .
                'OR `Friend` LIKE ?',
            'params' => [$uuid, $uuid, $friendPrefix],
        ],
        [
            'table' => 'os_groups_invites',
            'where' => '`PrincipalID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'os_groups_rolemembership',
            'where' => '`PrincipalID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'os_groups_membership',
            'where' => '`PrincipalID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'os_groups_principals',
            'where' => '`PrincipalID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'classifieds',
            'where' => '`creatoruuid` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'userpicks',
            'where' => '`creatoruuid` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'userprofile',
            'where' => '`useruuid` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'usersettings',
            'where' => '`useruuid` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'userdata',
            'where' => '`UserId` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'usernotes',
            'where' =>
                '`useruuid` = ? OR `targetuuid` = ?',
            'params' => [$uuid, $uuid],
        ],
        [
            'table' => 'tosauth',
            'where' => '`avataruuid` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'tokens',
            'where' => '`UUID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'estate_users',
            'where' => '`uuid` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'estate_managers',
            'where' => '`uuid` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'estateban',
            'where' =>
                '`bannedUUID` = ? OR `banningUUID` = ?',
            'params' => [$uuid, $uuid],
        ],
        [
            'table' => 'avatars',
            'where' => '`PrincipalID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'inventoryitems',
            'where' => '`avatarID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'inventoryfolders',
            'where' => '`agentID` = ?',
            'params' => [$uuid],
        ],
        [
            'table' => 'auth',
            'where' => '`UUID` = ?',
            'params' => [$uuid],
        ],

        /*
         * UserAccounts MUST remain the final delete.
         */
        [
            'table' => 'useraccounts',
            'where' => '`PrincipalID` = ?',
            'params' => [$uuid],
        ],
    ];
}

function loadTarget(
    mysqli $con,
    string $uuid,
    bool $forUpdate = false
): ?array {
    $sql =
        'SELECT PrincipalID, FirstName, LastName, Email, Created, UserLevel ' .
        'FROM UserAccounts ' .
        'WHERE PrincipalID = ? ' .
        'LIMIT 1';

    if ($forUpdate) {
        $sql .= ' FOR UPDATE';
    }

    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $uuid);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    if ($result) {
        mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function buildBlockers(
    mysqli $con,
    array $target,
    string $adminPrincipalId
): array {
    $uuid = (string)$target['PrincipalID'];
    $name =
        trim(
            (string)$target['FirstName'] .
            ' ' .
            (string)$target['LastName']
        );

    $blockers = [];

    if (
        $adminPrincipalId !== '' &&
        hash_equals($adminPrincipalId, $uuid)
    ) {
        $blockers[] =
            'You cannot delete the account you are currently signed in with.';
    }

    $protectedNames = [
        'GRID SERVICES',
        'WIFI ADMIN',
    ];

    if (
        in_array(
            strtoupper($name),
            $protectedNames,
            true
        )
    ) {
        $blockers[] =
            'This is a protected Grid service account.';
    }

    if ((int)$target['UserLevel'] >= 0) {
        $blockers[] =
            'Disable this account first. Permanent deletion is only allowed when UserLevel is below 0.';
    }

    if (
        tableExists($con, 'presence') &&
        columnExists($con, 'presence', 'UserID')
    ) {
        $presence =
            countWhere(
                $con,
                'presence',
                '`UserID` = ?',
                [$uuid]
            );

        if ($presence > 0) {
            $blockers[] =
                'This avatar still has a Presence record. Log it out and clear the session before deleting.';
        }
    }

    if (tableExists($con, 'regions')) {
        $regionClauses = [];
        $regionParams = [];

        if (
            columnExists(
                $con,
                'regions',
                'owner_uuid'
            )
        ) {
            $regionClauses[] = '`owner_uuid` = ?';
            $regionParams[] = $uuid;
        }

        if (
            columnExists(
                $con,
                'regions',
                'PrincipalID'
            )
        ) {
            $regionClauses[] = '`PrincipalID` = ?';
            $regionParams[] = $uuid;
        }

        if ($regionClauses) {
            $regionCount =
                countWhere(
                    $con,
                    'regions',
                    implode(
                        ' OR ',
                        $regionClauses
                    ),
                    $regionParams
                );

            if ($regionCount > 0) {
                $blockers[] =
                    'This avatar owns or is assigned to ' .
                    $regionCount .
                    ' region record(s). Reassign those regions before deleting the account.';
            }
        }
    }

    if (
        tableExists(
            $con,
            'estate_settings'
        ) &&
        columnExists(
            $con,
            'estate_settings',
            'EstateOwner'
        )
    ) {
        $estateCount =
            countWhere(
                $con,
                'estate_settings',
                '`EstateOwner` = ?',
                [$uuid]
            );

        if ($estateCount > 0) {
            $blockers[] =
                'This avatar owns ' .
                $estateCount .
                ' estate(s). Assign another estate owner before deleting the account.';
        }
    }

    /*
     * Group schema can vary. Dynamically look for founder columns.
     */
    if (tableExists($con, 'os_groups_groups')) {

        $sql =
            'SELECT COLUMN_NAME ' .
            'FROM information_schema.COLUMNS ' .
            'WHERE TABLE_SCHEMA = DATABASE() ' .
            "AND TABLE_NAME = 'os_groups_groups' " .
            "AND LOWER(COLUMN_NAME) LIKE '%founder%'";

        $result = mysqli_query($con, $sql);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $column =
                    (string)$row['COLUMN_NAME'];

                $founderCount =
                    countWhere(
                        $con,
                        'os_groups_groups',
                        quoteIdentifier($column) .
                            ' = ?',
                        [$uuid]
                    );

                if ($founderCount > 0) {
                    $blockers[] =
                        'This avatar is the founder of ' .
                        $founderCount .
                        ' group(s). Transfer or resolve those groups before deleting the account.';
                    break;
                }
            }

            mysqli_free_result($result);
        }
    }

    return $blockers;
}

function backupTargetRows(
    mysqli $con,
    array $target,
    array $specs
): string {
    $uuid =
        (string)$target['PrincipalID'];

    $name =
        trim(
            (string)$target['FirstName'] .
            ' ' .
            (string)$target['LastName']
        );

    $safeName =
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            $name
        ) ?? 'account';

    $root =
        ag_dg_path(
            '_ACCOUNT_DELETE_BACKUPS'
        );

    if (
        !is_string($root) ||
        trim($root) === ''
    ) {
        throw new RuntimeException(
            'Could not resolve the account-delete backup directory.'
        );
    }

    if (
        !is_dir($root) &&
        !@mkdir($root, 0770, true)
    ) {
        throw new RuntimeException(
            'Could not create the account-delete backup directory.'
        );
    }

    $folder =
        $root .
        '/' .
        date('Ymd-His') .
        '-' .
        $safeName .
        '-' .
        $uuid;

    if (!@mkdir($folder, 0770, true)) {
        throw new RuntimeException(
            'Could not create this account backup folder.'
        );
    }

    $snapshot = [
        'format' => 'AUSTRALIA-GRID-ACCOUNT-DELETE-BACKUP-V1',
        'created_utc' => gmdate('c'),
        'account' => $target,
        'rows' => [],
    ];

    foreach ($specs as $spec) {
        $table =
            (string)$spec['table'];

        /*
         * UserAccounts is also captured in account above,
         * but keep its full row in the table snapshot too.
         */
        $snapshot['rows'][$table] =
            fetchRows(
                $con,
                $table,
                (string)$spec['where'],
                (array)$spec['params']
            );
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
            'Could not encode the account backup.'
        );
    }

    $path =
        $folder .
        '/ACCOUNT-BACKUP.json';

    $written =
        @file_put_contents(
            $path,
            $json,
            LOCK_EX
        );

    if ($written === false) {
        throw new RuntimeException(
            'Could not write ACCOUNT-BACKUP.json. The database was not deleted.'
        );
    }

    $receipt =
        "Grid ACCOUNT DELETE BACKUP\r\n" .
        "Avatar: " . $name . "\r\n" .
        "UUID: " . $uuid . "\r\n" .
        "Created UTC: " . gmdate('c') . "\r\n" .
        "Backup JSON: " . $path . "\r\n";

    @file_put_contents(
        $folder . '/README.txt',
        $receipt,
        LOCK_EX
    );

    return $path;
}

if (!validPrincipalId($targetId)) {
    $error =
        'Select a valid account from Manage Accounts.';
}
else {
    $con = ag_db_connect();

    if (!$con) {
        $error =
            'Grid account database is unavailable.';
    }
    else {
        $target =
            loadTarget(
                $con,
                $targetId
            );

        if (!$target) {
            $error =
                'That account no longer exists.';
        }
        else {
            $blockers =
                buildBlockers(
                    $con,
                    $target,
                    $adminPrincipalId
                );

            foreach (
                buildDeleteSpecs(
                    $con,
                    $targetId
                ) as $spec
            ) {
                $counts[
                    (string)$spec['table']
                ] =
                    countWhere(
                        $con,
                        (string)$spec['table'],
                        (string)$spec['where'],
                        (array)$spec['params']
                    );
            }
        }

        if (
            $target &&
            !$blockers &&
            ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        ) {
            $postedCsrf =
                (string)($_POST['csrf_token'] ?? '');

            $confirmName =
                trim(
                    (string)(
                        $_POST['confirm_name'] ??
                        ''
                    )
                );

            $confirmWord =
                strtoupper(
                    trim(
                        (string)(
                            $_POST['confirm_word'] ??
                            ''
                        )
                    )
                );

            $confirmInventory =
                (string)(
                    $_POST['confirm_inventory'] ??
                    ''
                );

            $targetName =
                trim(
                    (string)$target['FirstName'] .
                    ' ' .
                    (string)$target['LastName']
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
                strcasecmp(
                    $confirmName,
                    $targetName
                ) !== 0
            ) {
                $error =
                    'The avatar-name confirmation does not match.';
            }
            elseif ($confirmWord !== 'DELETE') {
                $error =
                    'Type DELETE in the confirmation box.';
            }
            elseif ($confirmInventory !== '1') {
                $error =
                    'Confirm that you understand this deletes the avatar inventory.';
            }
            else {
                $specs =
                    buildDeleteSpecs(
                        $con,
                        $targetId
                    );

                try {
                    mysqli_begin_transaction($con);

                    /*
                     * Lock and re-read the account inside the transaction.
                     */
                    $lockedTarget =
                        loadTarget(
                            $con,
                            $targetId,
                            true
                        );

                    if (!$lockedTarget) {
                        throw new RuntimeException(
                            'The account disappeared before deletion began.'
                        );
                    }

                    $freshBlockers =
                        buildBlockers(
                            $con,
                            $lockedTarget,
                            $adminPrincipalId
                        );

                    if ($freshBlockers) {
                        throw new RuntimeException(
                            'A safety blocker appeared. Nothing was deleted.'
                        );
                    }

                    /*
                     * Backup MUST succeed before the first DELETE.
                     */
                    $backupPath =
                        backupTargetRows(
                            $con,
                            $lockedTarget,
                            $specs
                        );

                    $deletedCounts = [];

                    foreach ($specs as $spec) {
                        $deletedCounts[
                            (string)$spec['table']
                        ] =
                            executeDelete(
                                $con,
                                (string)$spec['table'],
                                (string)$spec['where'],
                                (array)$spec['params']
                            );
                    }

                    /*
                     * Final verification: the identity row must be gone.
                     */
                    $stillThere =
                        loadTarget(
                            $con,
                            $targetId
                        );

                    if ($stillThere) {
                        throw new RuntimeException(
                            'UserAccounts verification failed. Transaction rolled back.'
                        );
                    }

                    mysqli_commit($con);

                    $_SESSION['ag_delete_account_csrf'] =
                        bin2hex(random_bytes(32));

                    mysqli_close($con);

                    header(
                        'Location: /Other/admin-accounts.php?' .
                        http_build_query(
                            [
                                'deleted' => '1',
                                'deleted_name' =>
                                    $targetName,
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
                        'Account deletion stopped and rolled back: ' .
                        $e->getMessage();
                }
            }
        }

        mysqli_close($con);
    }
}

$targetName =
    $target
        ? trim(
            (string)$target['FirstName'] .
            ' ' .
            (string)$target['LastName']
        )
        : '';

$totalRows =
    array_sum($counts);

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Delete Account</title>
<link rel="stylesheet" href="/Other/core/site-shell.css?v=1">

<style>
.delete-card{
    max-width:900px;
    margin:0 auto;
    padding:24px;
    border:1px solid rgba(255,96,96,.28);
    border-radius:16px;
    background:rgba(14,20,24,.96);
}
.warning{
    padding:16px;
    margin-bottom:18px;
    border:1px solid rgba(255,96,96,.32);
    border-radius:11px;
    color:#ffd1d1;
    background:rgba(126,31,31,.15);
}
.blocker{
    padding:12px 14px;
    margin:8px 0;
    border-left:4px solid #ff7070;
    background:rgba(126,31,31,.14);
}
.reference-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(190px,1fr));
    gap:10px;
    margin:18px 0;
}
.reference{
    padding:12px;
    border:1px solid rgba(255,255,255,.10);
    border-radius:9px;
    background:rgba(0,0,0,.18);
}
.reference span{
    display:block;
    color:#97a7b0;
    font-size:11px;
    font-weight:900;
}
.reference strong{
    display:block;
    margin-top:4px;
    font-size:20px;
}
.confirm-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
    margin-top:18px;
}
.field label{
    display:block;
    margin-bottom:6px;
    color:#aebbc3;
    font-size:12px;
    font-weight:900;
}
.field input[type=text]{
    width:100%;
    min-height:44px;
    box-sizing:border-box;
    padding:0 12px;
    border:1px solid rgba(255,255,255,.17);
    border-radius:9px;
    color:#fff;
    background:#090e11;
}
.check-line{
    margin:18px 0;
    padding:13px;
    border:1px solid rgba(255,255,255,.11);
    border-radius:9px;
}
.actions{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:16px;
}
.delete-button{
    min-height:44px;
    padding:0 18px;
    border:1px solid #ff7777;
    border-radius:10px;
    color:#fff;
    background:#8f2323;
    font-weight:900;
    cursor:pointer;
}
.delete-button:disabled{
    opacity:.45;
    cursor:not-allowed;
}
@media(max-width:700px){
    .confirm-grid{
        grid-template-columns:1fr;
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
$siteHeaderTitle = "Delete Account";
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

<div class="delete-card">

    <div class="warning">
        <strong>PERMANENT ACCOUNT DELETION</strong><br>
        This removes the avatar identity, login, appearance, target-owned
        inventory and account-specific records. Assets and creator references
        owned by other avatars are deliberately preserved.
    </div>

    <?php if ($error !== ''): ?>
        <div class="ag-error"><?=ag_h($error)?></div>
    <?php endif; ?>

    <?php if (!$target): ?>

        <div class="actions">
            <a class="ag-button" href="<?=ag_h(ag_route('admin_accounts'))?>">
                BACK TO MANAGE ACCOUNTS
            </a>
        </div>

    <?php else: ?>

        <div class="detail-grid">

            <div class="detail-item">
                <span class="label">AVATAR</span>
                <?=ag_h($targetName)?>
            </div>

            <div class="detail-item">
                <span class="label">USER LEVEL</span>
                <?=ag_h($target['UserLevel'])?>
            </div>

            <div class="detail-item" style="grid-column:1/-1">
                <span class="label">PRINCIPAL ID</span>
                <?=ag_h($target['PrincipalID'])?>
            </div>

        </div>

        <?php if ($blockers): ?>

            <h2>Deletion Blocked</h2>

            <?php foreach ($blockers as $blocker): ?>
                <div class="blocker">
                    <?=ag_h($blocker)?>
                </div>
            <?php endforeach; ?>

            <div class="actions">
                <a
                    class="ag-button"
                    href="/Other/admin-accounts.php?id=<?=rawurlencode((string)$target['PrincipalID'])?>"
                >
                    BACK TO ACCOUNT
                </a>
            </div>

        <?php else: ?>

            <h2>Rows Scheduled for Removal</h2>

            <div class="ag-muted">
                Total matching account-specific rows:
                <strong><?=ag_h($totalRows)?></strong>
            </div>

            <div class="reference-grid">

            <?php foreach ($counts as $table => $count): ?>

                <?php if ($count > 0): ?>

                    <div class="reference">
                        <span><?=ag_h($table)?></span>
                        <strong><?=ag_h($count)?></strong>
                    </div>

                <?php endif; ?>

            <?php endforeach; ?>

            </div>

            <div class="warning">
                Before the database transaction deletes anything, the complete
                matching rows are written to
                <strong><?=ag_h((string)(ag_dg_path('_ACCOUNT_DELETE_BACKUPS') ?? '_ACCOUNT_DELETE_BACKUPS'))?></strong>.
                If that backup cannot be written, deletion does not start.
            </div>

            <form method="post" action="<?=ag_h(ag_route('admin_account_delete'))?>">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?=ag_h($csrfToken)?>">

                <input
                    type="hidden"
                    name="principal_id"
                    value="<?=ag_h($target['PrincipalID'])?>">

                <div class="confirm-grid">

                    <div class="field">
                        <label>
                            TYPE THE AVATAR NAME EXACTLY:
                            <?=ag_h($targetName)?>
                        </label>
                        <input
                            type="text"
                            name="confirm_name"
                            autocomplete="off"
                            required>
                    </div>

                    <div class="field">
                        <label>TYPE DELETE</label>
                        <input
                            type="text"
                            name="confirm_word"
                            autocomplete="off"
                            required>
                    </div>

                </div>

                <label class="check-line">
                    <input
                        type="checkbox"
                        name="confirm_inventory"
                        value="1"
                        required>
                    I understand that this permanently removes this avatar's
                    inventory and account-specific records.
                </label>

                <div class="actions">

                    <button
                        class="delete-button"
                        type="submit"
                        onclick="return confirm('FINAL WARNING: permanently delete <?=ag_h(addslashes($targetName))?>?');"
                    >
                        PERMANENTLY DELETE ACCOUNT
                    </button>

                    <a
                        class="ag-button"
                        href="/Other/admin-accounts.php?id=<?=rawurlencode((string)$target['PrincipalID'])?>"
                    >
                        CANCEL
                    </a>

                </div>

            </form>

        <?php endif; ?>

    <?php endif; ?>

</div>

</div>

<script defer src="/Other/core/ag-confirm-modal.js?v=1"></script>

<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>
</body>
</html>





