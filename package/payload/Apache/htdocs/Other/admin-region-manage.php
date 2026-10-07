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

if (empty($_SESSION['ag_region_manage_csrf'])) {
    $_SESSION['ag_region_manage_csrf'] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    (string)$_SESSION['ag_region_manage_csrf'];

$regionsRoot =
    ag_dg_regions_root();

$backupRoot =
    ag_dg_path('_REGION_CHANGE_BACKUPS');

$regionId =
    trim(
        (string)(
            $_POST['region_id'] ??
            $_GET['id'] ??
            ''
        )
    );

$error = '';
$success = '';

function validUuid(string $value): bool
{
    return (bool)preg_match(
        '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
        $value
    );
}

function iniValueFromRaw(
    string $raw,
    string $key
): ?string {
    $pattern =
        '/^\s*' .
        preg_quote($key, '/') .
        '\s*=\s*(.*?)\s*$/mi';

    if (
        !preg_match(
            $pattern,
            $raw,
            $matches
        )
    ) {
        return null;
    }

    return trim(
        (string)$matches[1],
        " \t\n\r\0\x0B\"'"
    );
}

function exactKeyCount(
    string $raw,
    string $key
): int {
    $count =
        preg_match_all(
            '/^\s*' .
            preg_quote($key, '/') .
            '\s*=.*$/mi',
            $raw
        );

    return $count === false
        ? 0
        : (int)$count;
}

function findRegionIniByUuid(
    string $root,
    string $uuid
): array {
    $matches = [];

    $files =
        @glob(
            $root .
            '/*/Region/*.ini'
        );

    if (!is_array($files)) {
        return [];
    }

    foreach ($files as $path) {

        $raw =
            @file_get_contents($path);

        if ($raw === false) {
            continue;
        }

        $foundUuid =
            iniValueFromRaw(
                $raw,
                'RegionUUID'
            );

        if (
            $foundUuid &&
            strcasecmp(
                $foundUuid,
                $uuid
            ) === 0
        ) {
            $matches[] = [
                'path' => $path,
                'raw' => $raw,
                'dos_box' =>
                    basename(
                        dirname(
                            dirname($path)
                        )
                    ),
                'file_name' =>
                    basename($path),
            ];
        }
    }

    return $matches;
}

function loadRegion(
    mysqli $con,
    string $uuid
): ?array {
    $stmt =
        mysqli_prepare(
            $con,
            'SELECT r.*, ua.FirstName, ua.LastName, ua.UserLevel ' .
            'FROM regions r ' .
            'LEFT JOIN UserAccounts ua ' .
            'ON ua.PrincipalID = r.owner_uuid ' .
            'WHERE r.uuid = ? LIMIT 1'
        );

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param(
        $stmt,
        's',
        $uuid
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

function replaceNumericKey(
    string $raw,
    string $key,
    int $value
): string {
    $pattern =
        '/^(\s*' .
        preg_quote($key, '/') .
        '\s*=\s*)\d+(\s*)$/mi';

    $count = 0;

    $new =
        preg_replace_callback(
            $pattern,
            static function (array $matches) use ($value): string {
                return
                    (string)$matches[1] .
                    (string)$value .
                    (string)$matches[2];
            },
            $raw,
            1,
            $count
        );

    if (
        $new === null ||
        $count !== 1
    ) {
        throw new RuntimeException(
            'Could not safely replace ' .
            $key .
            '.'
        );
    }

    return $new;
}

function writeBackup(
    string $backupRoot,
    array $region,
    string $iniPath,
    string $originalRaw,
    array $before,
    array $after
): array {
    if (
        !is_dir($backupRoot) &&
        !@mkdir($backupRoot, 0770, true)
    ) {
        throw new RuntimeException(
            'Could not create region backup directory.'
        );
    }

    $safeName =
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            (string)$region['regionName']
        ) ?? 'region';

    $folder =
        $backupRoot .
        '/' .
        date('Ymd-His') .
        '-' .
        $safeName .
        '-' .
        (string)$region['uuid'] .
        '-' .
        bin2hex(random_bytes(2));

    if (!@mkdir($folder, 0770, true)) {
        throw new RuntimeException(
            'Could not create this region backup folder.'
        );
    }

    $iniBackup =
        $folder .
        '/ORIGINAL-' .
        basename($iniPath);

    if (
        @file_put_contents(
            $iniBackup,
            $originalRaw,
            LOCK_EX
        ) === false
    ) {
        throw new RuntimeException(
            'Could not write original Region INI backup.'
        );
    }

    $metadata = [
        'format' =>
            'AUSTRALIA-GRID-REGION-CHANGE-BACKUP-V1',
        'created_utc' =>
            gmdate('c'),
        'region' => [
            'uuid' =>
                (string)$region['uuid'],
            'name' =>
                (string)$region['regionName'],
            'owner_uuid' =>
                (string)$region['owner_uuid'],
        ],
        'region_ini' =>
            $iniPath,
        'before' =>
            $before,
        'after' =>
            $after,
    ];

    $json =
        json_encode(
            $metadata,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES |
            JSON_INVALID_UTF8_SUBSTITUTE
        );

    if ($json === false) {
        throw new RuntimeException(
            'Could not encode region change metadata.'
        );
    }

    $metaPath =
        $folder .
        '/REGION-CHANGE.json';

    if (
        @file_put_contents(
            $metaPath,
            $json,
            LOCK_EX
        ) === false
    ) {
        throw new RuntimeException(
            'Could not write region change metadata.'
        );
    }

    return [
        'folder' => $folder,
        'ini_backup' => $iniBackup,
        'metadata' => $metaPath,
    ];
}

$con =
    ag_db_connect();

if (!$con) {
    http_response_code(500);
    exit('Grid database is unavailable.');
}

mysqli_set_charset(
    $con,
    'utf8mb4'
);

$region = null;
$ini = null;

if (!validUuid($regionId)) {
    $error =
        'Select a valid region from Manage Regions.';
}
else {
    $region =
        loadRegion(
            $con,
            $regionId
        );

    if (!$region) {
        $error =
            'That region is not registered in Robust.';
    }
    else {
        $iniMatches =
            findRegionIniByUuid(
                $regionsRoot,
                $regionId
            );

        if (count($iniMatches) !== 1) {
            $error =
                count($iniMatches) === 0
                    ? 'No Region INI matched this RegionUUID.'
                    : 'More than one Region INI matched this RegionUUID. Editing is blocked.';
        }
        else {
            $ini =
                $iniMatches[0];

            foreach (
                ['RegionUUID','MaxAgents','MaxPrims']
                as $requiredKey
            ) {
                if (
                    exactKeyCount(
                        $ini['raw'],
                        $requiredKey
                    ) !== 1
                ) {
                    $error =
                        'Editing is blocked because ' .
                        $requiredKey .
                        ' does not occur exactly once in this Region INI.';

                    break;
                }
            }
        }
    }
}

$currentMaxAgents =
    $ini
        ? iniValueFromRaw(
            $ini['raw'],
            'MaxAgents'
        )
        : null;

$currentMaxPrims =
    $ini
        ? iniValueFromRaw(
            $ini['raw'],
            'MaxPrims'
        )
        : null;

if (
    $error === '' &&
    $region &&
    $ini &&
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
) {
    $postedCsrf =
        (string)($_POST['csrf_token'] ?? '');

    $maxAgentsText =
        trim(
            (string)($_POST['max_agents'] ?? '')
        );

    $maxPrimsText =
        trim(
            (string)($_POST['max_prims'] ?? '')
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
        !preg_match(
            '/^\d+$/',
            $maxAgentsText
        )
    ) {
        $error =
            'Max Agents must be a whole number.';
    }
    elseif (
        !preg_match(
            '/^\d+$/',
            $maxPrimsText
        )
    ) {
        $error =
            'Max Prims must be a whole number.';
    }
    else {
        $maxAgents =
            (int)$maxAgentsText;

        $maxPrims =
            (int)$maxPrimsText;

        if (
            $maxAgents < 1 ||
            $maxAgents > 500
        ) {
            $error =
                'Max Agents must be between 1 and 500.';
        }
        elseif (
            $maxPrims < 1000 ||
            $maxPrims > 10000000
        ) {
            $error =
                'Max Prims must be between 1,000 and 10,000,000.';
        }
        else {
            try {
                /*
                 * Re-read immediately before change.
                 */
                $freshRaw =
                    @file_get_contents(
                        $ini['path']
                    );

                if ($freshRaw === false) {
                    throw new RuntimeException(
                        'Could not re-read the Region INI.'
                    );
                }

                $freshUuid =
                    iniValueFromRaw(
                        $freshRaw,
                        'RegionUUID'
                    );

                if (
                    !$freshUuid ||
                    strcasecmp(
                        $freshUuid,
                        $regionId
                    ) !== 0
                ) {
                    throw new RuntimeException(
                        'RegionUUID changed before save. Nothing was written.'
                    );
                }

                foreach (
                    ['RegionUUID','MaxAgents','MaxPrims']
                    as $requiredKey
                ) {
                    if (
                        exactKeyCount(
                            $freshRaw,
                            $requiredKey
                        ) !== 1
                    ) {
                        throw new RuntimeException(
                            $requiredKey .
                            ' no longer occurs exactly once. Nothing was written.'
                        );
                    }
                }

                $before = [
                    'MaxAgents' =>
                        iniValueFromRaw(
                            $freshRaw,
                            'MaxAgents'
                        ),
                    'MaxPrims' =>
                        iniValueFromRaw(
                            $freshRaw,
                            'MaxPrims'
                        ),
                ];

                $after = [
                    'MaxAgents' =>
                        $maxAgents,
                    'MaxPrims' =>
                        $maxPrims,
                ];

                if (
                    (string)$before['MaxAgents'] ===
                        (string)$maxAgents &&
                    (string)$before['MaxPrims'] ===
                        (string)$maxPrims
                ) {
                    $success =
                        'No change was needed. The Region INI already has those values.';
                }
                else {
                    $backup =
                        writeBackup(
                            $backupRoot,
                            $region,
                            $ini['path'],
                            $freshRaw,
                            $before,
                            $after
                        );

                    $newRaw =
                        replaceNumericKey(
                            $freshRaw,
                            'MaxAgents',
                            $maxAgents
                        );

                    $newRaw =
                        replaceNumericKey(
                            $newRaw,
                            'MaxPrims',
                            $maxPrims
                        );

                    if (
                        @file_put_contents(
                            $ini['path'],
                            $newRaw,
                            LOCK_EX
                        ) === false
                    ) {
                        throw new RuntimeException(
                            'Could not write the Region INI. Original backup is safe.'
                        );
                    }

                    $verifyRaw =
                        @file_get_contents(
                            $ini['path']
                        );

                    $verified =
                        $verifyRaw !== false &&
                        strcasecmp(
                            (string)iniValueFromRaw(
                                $verifyRaw,
                                'RegionUUID'
                            ),
                            $regionId
                        ) === 0 &&
                        (int)iniValueFromRaw(
                            $verifyRaw,
                            'MaxAgents'
                        ) === $maxAgents &&
                        (int)iniValueFromRaw(
                            $verifyRaw,
                            'MaxPrims'
                        ) === $maxPrims;

                    if (!$verified) {
                        @file_put_contents(
                            $ini['path'],
                            $freshRaw,
                            LOCK_EX
                        );

                        throw new RuntimeException(
                            'Post-write verification failed. The original Region INI was restored.'
                        );
                    }

                    $ini['raw'] =
                        $verifyRaw;

                    $currentMaxAgents =
                        (string)$maxAgents;

                    $currentMaxPrims =
                        (string)$maxPrims;

                    $_SESSION['ag_region_manage_csrf'] =
                        bin2hex(random_bytes(32));

                    $csrfToken =
                        (string)$_SESSION['ag_region_manage_csrf'];

                    $success =
                        'Region settings saved and verified. Backup: ' .
                        $backup['folder'];
                }
            }
            catch (Throwable $e) {
                $error =
                    'Region settings were not saved: ' .
                    $e->getMessage();
            }
        }
    }
}

mysqli_close($con);

$ownerName =
    $region
        ? trim(
            (string)($region['FirstName'] ?? '') .
            ' ' .
            (string)($region['LastName'] ?? '')
        )
        : '';

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Manage Region</title>

<link
    rel="stylesheet"
    href="/Other/core/site-shell.css?v=1">

<style>
.manage-card{
    max-width:940px;
    margin:0 auto;
    padding:22px;
    border:1px solid rgba(255,255,255,.11);
    border-radius:14px;
    background:rgba(8,13,16,.86);
}
.info{
    margin-bottom:16px;
    padding:13px 15px;
    border:1px solid rgba(77,177,255,.20);
    border-radius:10px;
    color:#cceaff;
    background:rgba(37,115,169,.08);
}
.locked{
    margin-bottom:16px;
    padding:13px 15px;
    border:1px solid rgba(244,179,35,.25);
    border-radius:10px;
    color:#f7dda0;
    background:rgba(244,179,35,.06);
}
.form-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:14px;
}
.field label{
    display:block;
    margin-bottom:6px;
    color:#96a9b3;
    font-size:11px;
    font-weight:900;
}
.field input{
    width:100%;
    box-sizing:border-box;
    min-height:44px;
    padding:0 12px;
    border:1px solid rgba(255,255,255,.15);
    border-radius:9px;
    color:#fff;
    background:#080d10;
}
.meta-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:10px;
    margin:16px 0;
}
.meta{
    padding:11px 12px;
    border:1px solid rgba(255,255,255,.10);
    border-radius:9px;
    background:rgba(0,0,0,.16);
}
.meta span{
    display:block;
    margin-bottom:4px;
    color:#8fa3ad;
    font-size:10px;
    font-weight:900;
}
.uuid,.path{
    font-family:Consolas,monospace;
    overflow-wrap:anywhere;
}
.actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:18px;
}
@media(max-width:700px){
    .form-grid,
    .meta-grid{
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
$siteHeaderTitle = "Manage Region";
$siteHeaderRole = "GRID OWNER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "ADMIN HOME";
$siteHeaderLink = "/Other/admin-home.php";
require_once __DIR__ . "/includes/site-header.php";
?>

<nav class="ag-nav">

<?php foreach ($nav as $item): ?>

    <a href="<?=ag_h($item['url'])?>">
        <?=ag_h($item['label'])?>
    </a>

<?php endforeach; ?>

</nav>

<div class="manage-card">

    <?php if ($error !== ''): ?>
        <div class="ag-error" style="margin-bottom:16px">
            <?=ag_h($error)?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="ag-success" style="margin-bottom:16px">
            <?=ag_h($success)?>
        </div>
    <?php endif; ?>

    <?php if (!$region || !$ini): ?>

        <div class="actions">

            <a
                class="ag-button"
                href="<?=ag_h(ag_route('admin_regions'))?>"
            >
                BACK TO REGIONS
            </a>

        </div>

    <?php else: ?>

        <div class="ag-eyebrow">
            Safe Region Settings
        </div>

        <h2 style="margin-top:5px">
            <?=ag_h($region['regionName'])?>
        </h2>

        <div class="info">
            Phase 5C-1 changes only <strong>Max Agents</strong> and
            <strong>Max Prims</strong>. The RegionUUID, region name,
            location, ports and owner are not modified.
        </div>

        <div class="locked">
            <strong>Owner is locked in this phase:</strong>
            <?=ag_h($ownerName !== '' ? $ownerName : (string)$region['owner_uuid'])?>.
            Region ownership is tied to the estate/ownership system and will
            be added only after that linkage is audited.
        </div>

        <div class="meta-grid">

            <div class="meta">
                <span>REGION UUID</span>
                <div class="uuid">
                    <?=ag_h($region['uuid'])?>
                </div>
            </div>

            <div class="meta">
                <span>OWNER UUID</span>
                <div class="uuid">
                    <?=ag_h($region['owner_uuid'])?>
                </div>
            </div>

            <div class="meta">
                <span>REGION CONSOLE</span>
                <strong><?=ag_h($ini['dos_box'])?></strong>
            </div>

            <div class="meta">
                <span>REGION INI</span>
                <div class="path">
                    <?=ag_h(str_replace('\\', '/', $ini['path']))?>
                </div>
            </div>

        </div>

        <form method="post" action="<?=ag_h(ag_route('admin_region_manage'))?>">

            <input
                type="hidden"
                name="csrf_token"
                value="<?=ag_h($csrfToken)?>">

            <input
                type="hidden"
                name="region_id"
                value="<?=ag_h($regionId)?>">

            <div class="form-grid">

                <div class="field">
                    <label>MAX AGENTS</label>

                    <input
                        type="number"
                        name="max_agents"
                        min="1"
                        max="500"
                        step="1"
                        value="<?=ag_h((string)$currentMaxAgents)?>"
                        required>
                </div>

                <div class="field">
                    <label>MAX PRIMS</label>

                    <input
                        type="number"
                        name="max_prims"
                        min="1000"
                        max="10000000"
                        step="1"
                        value="<?=ag_h((string)$currentMaxPrims)?>"
                        required>
                </div>

            </div>

            <div class="actions">

                <button
                    class="ag-button primary"
                    type="submit"
                    onclick="return confirm('Save these Region INI settings?');"
                >
                    SAVE REGION SETTINGS
                </button>

                <a
                    class="ag-button"
                    href="<?=ag_h(ag_route('admin_regions'))?>?id=<?=rawurlencode($regionId)?>"
                >
                    BACK TO REGION
                </a>

            </div>

        </form>

    <?php endif; ?>

</div>

</div>

<script defer src="/Other/core/ag-confirm-modal.js?v=1"></script>





<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>
</body>
</html>











