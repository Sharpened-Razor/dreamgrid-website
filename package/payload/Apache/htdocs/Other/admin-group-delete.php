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

if (empty($_SESSION['ag_group_delete_csrf'])) {
    $_SESSION['ag_group_delete_csrf'] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    (string)$_SESSION['ag_group_delete_csrf'];

$zeroUuid =
    '00000000-0000-0000-0000-000000000000';

$groupId =
    trim(
        (string)(
            $_POST['group_id'] ??
            $_GET['id'] ??
            ''
        )
    );

$error = '';
$group = null;
$counts = [];
$activePrincipals = 0;
$externalRefs = [];

function validUuid(string $value): bool
{
    return (bool)preg_match(
        '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
        $value
    );
}

function qid(string $name): string
{
    return '`' .
        str_replace('`', '``', $name) .
        '`';
}

function loadGroup(
    mysqli $con,
    string $groupId,
    bool $forUpdate = false
): ?array {
    $sql =
        'SELECT g.*, ua.FirstName, ua.LastName ' .
        'FROM os_groups_groups g ' .
        'LEFT JOIN UserAccounts ua ' .
        'ON ua.PrincipalID = g.FounderID ' .
        'WHERE g.GroupID = ? ' .
        'LIMIT 1';

    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param(
        $stmt,
        's',
        $groupId
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $row =
        $result
            ? mysqli_fetch_assoc($result)
            : null;

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

        if (!$lock) {
            return null;
        }

        mysqli_stmt_bind_param(
            $lock,
            's',
            $groupId
        );

        mysqli_stmt_execute($lock);
        mysqli_stmt_store_result($lock);

        $locked =
            mysqli_stmt_num_rows($lock) === 1;

        mysqli_stmt_close($lock);

        if (!$locked) {
            return null;
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

function countGroupRows(
    mysqli $con,
    string $table,
    string $groupId
): int {
    $stmt =
        mysqli_prepare(
            $con,
            'SELECT COUNT(*) AS c FROM ' .
            qid($table) .
            ' WHERE GroupID = ?'
        );

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param(
        $stmt,
        's',
        $groupId
    );

    mysqli_stmt_execute($stmt);
    $result =
        mysqli_stmt_get_result($stmt);

    $count = 0;

    if ($result) {
        $row =
            mysqli_fetch_assoc($result);

        $count =
            (int)($row['c'] ?? 0);

        mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);

    return $count;
}

function countActivePrincipals(
    mysqli $con,
    string $groupId
): int {
    $stmt =
        mysqli_prepare(
            $con,
            'SELECT COUNT(*) AS c ' .
            'FROM os_groups_principals ' .
            'WHERE ActiveGroupID = ?'
        );

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param(
        $stmt,
        's',
        $groupId
    );

    mysqli_stmt_execute($stmt);
    $result =
        mysqli_stmt_get_result($stmt);

    $count = 0;

    if ($result) {
        $row =
            mysqli_fetch_assoc($result);

        $count =
            (int)($row['c'] ?? 0);

        mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);

    return $count;
}

function externalGroupReferences(
    mysqli $con,
    string $groupId
): array {
    /*
     * Find live, non-group, non-backup string columns whose names indicate
     * group references. If anything uses this GroupID, block deletion.
     */
    $sql =
        'SELECT TABLE_NAME, COLUMN_NAME ' .
        'FROM information_schema.COLUMNS ' .
        'WHERE TABLE_SCHEMA = DATABASE() ' .
        "AND DATA_TYPE IN ('char','varchar','text','tinytext','mediumtext','longtext') " .
        "AND (LOWER(COLUMN_NAME) LIKE '%group%') " .
        "AND TABLE_NAME NOT LIKE 'os_groups_%' " .
        "AND LOWER(TABLE_NAME) NOT LIKE '%backup%' " .
        'ORDER BY TABLE_NAME, ORDINAL_POSITION';

    $result =
        mysqli_query(
            $con,
            $sql
        );

    if (!$result) {
        throw new RuntimeException(
            'Could not inspect external group references.'
        );
    }

    $refs = [];

    while ($column = mysqli_fetch_assoc($result)) {

        $table =
            (string)$column['TABLE_NAME'];

        $name =
            (string)$column['COLUMN_NAME'];

        $stmt =
            @mysqli_prepare(
                $con,
                'SELECT COUNT(*) AS c FROM ' .
                qid($table) .
                ' WHERE ' .
                qid($name) .
                ' = ?'
            );

        if (!$stmt) {
            continue;
        }

        mysqli_stmt_bind_param(
            $stmt,
            's',
            $groupId
        );

        mysqli_stmt_execute($stmt);
        $countResult =
            mysqli_stmt_get_result($stmt);

        $count = 0;

        if ($countResult) {
            $row =
                mysqli_fetch_assoc($countResult);

            $count =
                (int)($row['c'] ?? 0);

            mysqli_free_result($countResult);
        }

        mysqli_stmt_close($stmt);

        if ($count > 0) {
            $refs[] = [
                'table' => $table,
                'column' => $name,
                'count' => $count,
            ];
        }
    }

    mysqli_free_result($result);

    return $refs;
}

function fetchRows(
    mysqli $con,
    string $sql,
    array $params
): array {
    $stmt =
        mysqli_prepare(
            $con,
            $sql
        );

    if (!$stmt) {
        throw new RuntimeException(
            'Could not prepare group backup read.'
        );
    }

    if ($params) {
        $types =
            str_repeat(
                's',
                count($params)
            );

        mysqli_stmt_bind_param(
            $stmt,
            $types,
            ...$params
        );
    }

    if (!mysqli_stmt_execute($stmt)) {
        $message =
            mysqli_stmt_error($stmt);

        mysqli_stmt_close($stmt);

        throw new RuntimeException(
            'Could not read group backup rows: ' .
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

    return $rows;
}

function writeDeleteBackup(
    mysqli $con,
    array $group
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
        rtrim(ag_dg_root(), '/\\') . DIRECTORY_SEPARATOR . '_GROUP_DELETE_BACKUPS';

    if (
        !is_dir($root) &&
        !@mkdir($root, 0770, true)
    ) {
        throw new RuntimeException(
            'Could not create group-delete backup directory.'
        );
    }

    $folder =
        $root .
        '/' .
        date('Ymd-His') .
        '-' .
        $safeName .
        '-' .
        $groupId .
        '-' .
        bin2hex(random_bytes(2));

    if (!@mkdir($folder, 0770, true)) {
        throw new RuntimeException(
            'Could not create this group-delete backup folder.'
        );
    }

    $queries = [
        'os_groups_groups' => [
            'sql' =>
                'SELECT * FROM os_groups_groups WHERE GroupID = ?',
            'params' => [$groupId],
        ],
        'os_groups_membership' => [
            'sql' =>
                'SELECT * FROM os_groups_membership WHERE GroupID = ?',
            'params' => [$groupId],
        ],
        'os_groups_roles' => [
            'sql' =>
                'SELECT * FROM os_groups_roles WHERE GroupID = ?',
            'params' => [$groupId],
        ],
        'os_groups_rolemembership' => [
            'sql' =>
                'SELECT * FROM os_groups_rolemembership WHERE GroupID = ?',
            'params' => [$groupId],
        ],
        'os_groups_invites' => [
            'sql' =>
                'SELECT * FROM os_groups_invites WHERE GroupID = ?',
            'params' => [$groupId],
        ],
        'os_groups_notices' => [
            'sql' =>
                'SELECT * FROM os_groups_notices WHERE GroupID = ?',
            'params' => [$groupId],
        ],
        'os_groups_principals_active' => [
            'sql' =>
                'SELECT * FROM os_groups_principals WHERE ActiveGroupID = ?',
            'params' => [$groupId],
        ],
    ];

    $snapshot = [
        'format' =>
            'AUSTRALIA-GRID-GROUP-DELETE-BACKUP-V1',
        'created_utc' =>
            gmdate('c'),
        'group' =>
            $group,
        'rows' =>
            [],
    ];

    foreach ($queries as $name => $spec) {
        $snapshot['rows'][$name] =
            fetchRows(
                $con,
                $spec['sql'],
                $spec['params']
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
            'Could not encode group-delete backup.'
        );
    }

    $path =
        $folder .
        '/GROUP-DELETE-BACKUP.json';

    if (
        @file_put_contents(
            $path,
            $json,
            LOCK_EX
        ) === false
    ) {
        throw new RuntimeException(
            'Could not write GROUP-DELETE-BACKUP.json. Nothing was deleted.'
        );
    }

    $readme =
        "Grid GROUP DELETE BACKUP\r\n" .
        "Group: " .
        (string)$group['Name'] .
        "\r\n" .
        "GroupID: " .
        $groupId .
        "\r\n" .
        "Created UTC: " .
        gmdate('c') .
        "\r\n";

    @file_put_contents(
        $folder . '/README.txt',
        $readme,
        LOCK_EX
    );

    return $path;
}

function deleteGroupRows(
    mysqli $con,
    string $table,
    string $groupId
): int {
    $stmt =
        mysqli_prepare(
            $con,
            'DELETE FROM ' .
            qid($table) .
            ' WHERE GroupID = ?'
        );

    if (!$stmt) {
        throw new RuntimeException(
            'Could not prepare delete for ' .
            $table
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        's',
        $groupId
    );

    if (!mysqli_stmt_execute($stmt)) {
        $message =
            mysqli_stmt_error($stmt);

        mysqli_stmt_close($stmt);

        throw new RuntimeException(
            'Delete failed for ' .
            $table .
            ': ' .
            $message
        );
    }

    $affected =
        mysqli_stmt_affected_rows($stmt);

    mysqli_stmt_close($stmt);

    return (int)$affected;
}

$con = null;

if (!validUuid($groupId)) {
    $error =
        'Select a valid local group from Manage Groups.';
}
else {
    $con =
        ag_db_connect();

    if (!$con) {
        $error =
            'Grid database is unavailable.';
    }
    else {
        mysqli_set_charset(
            $con,
            'utf8mb4'
        );

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
                'HG / cached groups cannot be deleted from the local grid.';
        }
        else {
            try {
                $externalRefs =
                    externalGroupReferences(
                        $con,
                        $groupId
                    );
            }
            catch (Throwable $e) {
                $error =
                    'Could not complete the delete safety scan: ' .
                    $e->getMessage();
            }

            $tables = [
                'os_groups_membership',
                'os_groups_roles',
                'os_groups_rolemembership',
                'os_groups_invites',
                'os_groups_notices',
            ];

            foreach ($tables as $table) {
                $counts[$table] =
                    countGroupRows(
                        $con,
                        $table,
                        $groupId
                    );
            }

            $activePrincipals =
                countActivePrincipals(
                    $con,
                    $groupId
                );
        }
    }
}

if (
    $con &&
    $group &&
    isLocalGroup($group) &&
    $error === '' &&
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
) {
    $postedCsrf =
        (string)($_POST['csrf_token'] ?? '');

    $confirmName =
        trim(
            (string)($_POST['confirm_name'] ?? '')
        );

    $confirmPhrase =
        strtoupper(
            trim(
                (string)($_POST['confirm_phrase'] ?? '')
            )
        );

    $confirmLand =
        (string)($_POST['confirm_land'] ?? '');

    $confirmPermanent =
        (string)($_POST['confirm_permanent'] ?? '');

    $groupName =
        (string)$group['Name'];

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
    elseif (!hash_equals($groupName, $confirmName)) {
        $error =
            'The group-name confirmation does not match exactly.';
    }
    elseif ($confirmPhrase !== 'DELETE GROUP') {
        $error =
            'Type DELETE GROUP in the confirmation box.';
    }
    elseif ($confirmLand !== '1') {
        $error =
            'Confirm that you checked land and in-world objects first.';
    }
    elseif ($confirmPermanent !== '1') {
        $error =
            'Confirm that you understand this deletion is permanent.';
    }
    elseif ($externalRefs) {
        $error =
            'Deletion is blocked because live non-group records reference this GroupID.';
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

            if (!$locked) {
                throw new RuntimeException(
                    'The group disappeared before deletion began.'
                );
            }

            if (!isLocalGroup($locked)) {
                throw new RuntimeException(
                    'The group is no longer a proper local group.'
                );
            }

            /*
             * Re-run external reference safety scan INSIDE transaction
             * before the first mutation.
             */
            $freshExternal =
                externalGroupReferences(
                    $con,
                    $groupId
                );

            if ($freshExternal) {
                throw new RuntimeException(
                    'A live external GroupID reference appeared. Nothing was deleted.'
                );
            }

            /*
             * Backup MUST succeed before any database mutation.
             */
            writeDeleteBackup(
                $con,
                $locked
            );

            /*
             * Clear ActiveGroupID instead of deleting principal rows.
             */
            $principalStmt =
                mysqli_prepare(
                    $con,
                    'UPDATE os_groups_principals ' .
                    'SET ActiveGroupID = ? ' .
                    'WHERE ActiveGroupID = ?'
                );

            if (!$principalStmt) {
                throw new RuntimeException(
                    'Could not prepare ActiveGroupID cleanup.'
                );
            }

            mysqli_stmt_bind_param(
                $principalStmt,
                'ss',
                $zeroUuid,
                $groupId
            );

            if (!mysqli_stmt_execute($principalStmt)) {
                $message =
                    mysqli_stmt_error($principalStmt);

                mysqli_stmt_close($principalStmt);

                throw new RuntimeException(
                    'Could not clear ActiveGroupID: ' .
                    $message
                );
            }

            mysqli_stmt_close($principalStmt);

            /*
             * Dependency order:
             * invitations / notices
             * role memberships
             * memberships
             * roles
             * group record LAST
             */
            deleteGroupRows(
                $con,
                'os_groups_invites',
                $groupId
            );

            deleteGroupRows(
                $con,
                'os_groups_notices',
                $groupId
            );

            deleteGroupRows(
                $con,
                'os_groups_rolemembership',
                $groupId
            );

            deleteGroupRows(
                $con,
                'os_groups_membership',
                $groupId
            );

            deleteGroupRows(
                $con,
                'os_groups_roles',
                $groupId
            );

            $deletedGroup =
                deleteGroupRows(
                    $con,
                    'os_groups_groups',
                    $groupId
                );

            if ($deletedGroup !== 1) {
                throw new RuntimeException(
                    'Main group-record deletion verification failed.'
                );
            }

            /*
             * Exact post-delete verification.
             */
            $verifyTables = [
                'os_groups_groups',
                'os_groups_membership',
                'os_groups_roles',
                'os_groups_rolemembership',
                'os_groups_invites',
                'os_groups_notices',
            ];

            foreach ($verifyTables as $table) {

                $remaining =
                    countGroupRows(
                        $con,
                        $table,
                        $groupId
                    );

                if ($remaining !== 0) {
                    throw new RuntimeException(
                        'Post-delete verification failed for ' .
                        $table .
                        '. Transaction rolled back.'
                    );
                }
            }

            if (
                countActivePrincipals(
                    $con,
                    $groupId
                ) !== 0
            ) {
                throw new RuntimeException(
                    'ActiveGroupID verification failed. Transaction rolled back.'
                );
            }

            mysqli_commit($con);

            $_SESSION['ag_group_delete_csrf'] =
                bin2hex(random_bytes(32));

            mysqli_close($con);

            header(
                'Location: ' .
                ag_route('admin_groups') .
                '?' .
                http_build_query(
                    [
                        'deleted_group' =>
                            '1',
                        'deleted_group_name' =>
                            $groupName,
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
                'Group deletion stopped and rolled back: ' .
                $e->getMessage();
        }
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
            (string)($group['FirstName'] ?? '') .
            ' ' .
            (string)($group['LastName'] ?? '')
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
<title>Delete Group</title>
<link rel="stylesheet" href="/Other/core/site-shell.css?v=1">

<style>
.delete-card{
    max-width:950px;
    margin:0 auto;
    padding:22px;
    border:1px solid rgba(255,96,96,.30);
    border-radius:14px;
    background:rgba(8,13,16,.88);
}
.warning{
    margin-bottom:16px;
    padding:14px 16px;
    border:1px solid rgba(255,96,96,.32);
    border-radius:10px;
    color:#ffd1d1;
    background:rgba(126,31,31,.14);
}
.info-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:10px;
    margin-bottom:16px;
}
.info-item{
    padding:12px;
    border:1px solid rgba(255,255,255,.11);
    border-radius:9px;
    background:rgba(0,0,0,.18);
}
.info-item span,
.ref-card span{
    display:block;
    margin-bottom:4px;
    color:#96a9b3;
    font-size:11px;
    font-weight:900;
}
.ref-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(190px,1fr));
    gap:10px;
    margin:16px 0;
}
.ref-card{
    padding:12px;
    border:1px solid rgba(255,255,255,.11);
    border-radius:9px;
    background:rgba(0,0,0,.18);
}
.ref-card strong{
    font-size:20px;
}
.blocker{
    padding:12px 14px;
    margin:8px 0;
    border-left:4px solid #ff7070;
    background:rgba(126,31,31,.14);
}
.field{
    margin-top:14px;
}
.field label{
    display:block;
    margin-bottom:6px;
    color:#96a9b3;
    font-size:11px;
    font-weight:900;
}
.field input[type=text]{
    width:100%;
    min-height:44px;
    box-sizing:border-box;
    padding:0 12px;
    border:1px solid rgba(255,255,255,.16);
    border-radius:9px;
    color:#fff;
    background:#080d10;
}
.check{
    display:block;
    margin-top:14px;
    padding:12px;
    border:1px solid rgba(255,255,255,.11);
    border-radius:9px;
}
.actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:18px;
}
.delete-button{
    min-height:44px;
    padding:0 18px;
    border:1px solid #ff7474;
    border-radius:9px;
    color:#fff;
    background:#8f2323;
    font-weight:900;
    cursor:pointer;
}
.uuid{
    font-family:Consolas,monospace;
    overflow-wrap:anywhere;
}
@media(max-width:700px){
    .info-grid{
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
$siteHeaderTitle = "Delete Group";
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
        <strong>PERMANENT GROUP DELETION</strong><br>
        This removes the local group, memberships, roles, invites and notices.
        Any avatar using this as its active group will be reset to no active group.
    </div>

    <?php if ($error !== ''): ?>
        <div class="ag-error"><?=ag_h($error)?></div>
    <?php endif; ?>

    <?php if (!$group): ?>

        <div class="actions">
            <a class="ag-button" href="<?=ag_h(ag_route('admin_groups'))?>">
                BACK TO MANAGE GROUPS
            </a>
        </div>

    <?php elseif (!isLocalGroup($group)): ?>

        <div class="blocker">
            HG / cached groups are read-only and cannot be deleted locally.
        </div>

    <?php else: ?>

        <div class="info-grid">

            <div class="info-item">
                <span>GROUP</span>
                <strong><?=ag_h($groupName)?></strong>
            </div>

            <div class="info-item">
                <span>FOUNDER</span>
                <?= $founderName !== '' ? ag_h($founderName) : ag_h($group['FounderID']) ?>
            </div>

            <div class="info-item" style="grid-column:1/-1">
                <span>GROUP ID</span>
                <div class="uuid"><?=ag_h($groupId)?></div>
            </div>

        </div>

        <?php if ($externalRefs): ?>

            <h2>Deletion Blocked</h2>

            <div class="warning">
                Live records outside the normal group tables currently reference
                this GroupID. Resolve them before deleting this group.
            </div>

            <?php foreach ($externalRefs as $ref): ?>

                <div class="blocker">
                    <?=ag_h($ref['table'])?>.<?=ag_h($ref['column'])?>
                    &mdash; <?=ag_h($ref['count'])?> record(s)
                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <h2>Rows Scheduled for Removal</h2>

            <div class="ref-grid">

                <?php foreach ($counts as $table => $count): ?>

                    <div class="ref-card">
                        <span><?=ag_h($table)?></span>
                        <strong><?=ag_h($count)?></strong>
                    </div>

                <?php endforeach; ?>

                <div class="ref-card">
                    <span>ACTIVE GROUP REFERENCES TO CLEAR</span>
                    <strong><?=ag_h($activePrincipals)?></strong>
                </div>

            </div>

            <div class="warning">
                The database scan found no live external GroupID references.
                However, region land and rezzed objects can exist outside these
                Robust group tables. Check those before continuing.
            </div>

            <form method="post" action="<?=ag_h(ag_route('admin_group_delete'))?>">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?=ag_h($csrfToken)?>">

                <input
                    type="hidden"
                    name="group_id"
                    value="<?=ag_h($groupId)?>">

                <div class="field">
                    <label>
                        TYPE THE GROUP NAME EXACTLY:
                        <?=ag_h($groupName)?>
                    </label>

                    <input
                        type="text"
                        name="confirm_name"
                        autocomplete="off"
                        required>
                </div>

                <div class="field">
                    <label>TYPE DELETE GROUP</label>

                    <input
                        type="text"
                        name="confirm_phrase"
                        autocomplete="off"
                        required>
                </div>

                <label class="check">
                    <input
                        type="checkbox"
                        name="confirm_land"
                        value="1"
                        required>
                    I have checked this group does not own land or in-world
                    objects that I need to keep.
                </label>

                <label class="check">
                    <input
                        type="checkbox"
                        name="confirm_permanent"
                        value="1"
                        required>
                    I understand this permanently removes this local group and
                    its memberships, roles, invites and notices.
                </label>

                <div class="actions">

                    <button
                        class="delete-button"
                        type="submit"
                        onclick="return confirm('FINAL WARNING: permanently delete this group?');"
                    >
                        PERMANENTLY DELETE GROUP
                    </button>

                    <a
                        class="ag-button"
                        href="<?=ag_h(ag_route('admin_group_manage'))?>?id=<?=rawurlencode($groupId)?>"
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





