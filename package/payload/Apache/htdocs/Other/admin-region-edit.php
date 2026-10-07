<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/navigation.php';
require_once __DIR__ . '/core/region-database.php';

ag_no_cache();

$session = ag_require_admin();
$avatar = ag_avatar_name($session);
$level = ag_user_level($session);
$nav = ag_admin_navigation();

/*
 * ============================================================
 * DREAMGRID FORMREGION NATIVE SYNC V2
 *
 * Standalone mode:
 *   Full Administration page.
 *
 * Embedded mode:
 *   Only the actual Region Edit workspace is rendered.
 *
 * This is server-side rendering. Standalone chrome is NOT
 * rendered and then hidden with CSS.
 * ============================================================
 */
foreach ([$_GET, $_POST] as $requestFields) {
    foreach ($requestFields as $key => $value) {
        if ($key === 'cores' && is_array($value) && count(array_filter($value, 'is_string')) === count($value)) continue;
        if (!is_string($value)) {
            http_response_code(400);
            exit('Invalid Region Edit request.');
        }
    }
}

$dreEmbedded =
    (
        (string)(
            $_GET['embedded'] ??
            $_POST['embedded'] ??
            ''
        )
    ) ===
    '1';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['ag_region_edit_csrf'])) {
    $_SESSION['ag_region_edit_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = (string)$_SESSION['ag_region_edit_csrf'];
$regionsRoot = ag_dg_regions_root();
$backupRoot = ag_dg_path('_REGION_CHANGE_BACKUPS');

$regionName = trim((string)($_GET['region'] ?? $_POST['region_original'] ?? ''));

if ($regionName === '') {
    http_response_code(400);
    exit('Region name is required.');
}

$error = '';
$success = '';
$formDraft = null;

function dre_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function dre_region_list(): array
{
    $url = ag_dg_diagnostics_base() . '/?command=regionlist&page=1&rp=500&sortorder=asc';
    $json = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
        ]);
        $json = curl_exec($ch);
        curl_close($ch);
    }

    if (!is_string($json) || trim($json) === '') {
        $json = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 8]]));
    }

    if (!is_string($json) || trim($json) === '') {
        return [];
    }

    $data = json_decode($json, true);

    if (!is_array($data) || !isset($data['rows']) || !is_array($data['rows'])) {
        return [];
    }

    return $data['rows'];
}

function dre_find_live_region(string $regionName, array $rows): ?array
{
    foreach ($rows as $row) {
        $cell = $row['cell'] ?? [];
        if (!is_array($cell)) {
            continue;
        }

        $name = trim((string)($cell['RegionName'] ?? ''));
        if ($name !== '' && strcasecmp($name, $regionName) === 0) {
            return $cell;
        }
    }

    return null;
}

function dre_live_running(?array $cell): bool
{
    if (!is_array($cell)) {
        return false;
    }

    $ram = trim(strip_tags((string)($cell['Ram'] ?? $cell['RAM'] ?? '')));
    return $ram !== '' && $ram !== '-' && (bool)preg_match('/\d/', $ram);
}

function dre_any_running(array $rows): bool
{
    foreach ($rows as $row) {
        $cell = $row['cell'] ?? [];
        if (is_array($cell) && dre_live_running($cell)) {
            return true;
        }
    }
    return false;
}

function dre_find_region_ini(string $root, string $regionName): ?array
{
    $files = @glob($root . '/*/Region/*.ini');
    if (!is_array($files)) {
        return null;
    }

    $sectionPattern = '/^\s*\[' . preg_quote($regionName, '/') . '\]\s*$/mi';

    foreach ($files as $path) {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            continue;
        }

        if (preg_match($sectionPattern, $raw)) {
            return [
                'path' => $path,
                'raw' => $raw,
                'group' => basename(dirname(dirname($path))),
            ];
        }
    }

    return null;
}

function dre_ini_value(string $raw, string $key): ?string
{
    $pattern = '/^\s*' . preg_quote($key, '/') . '\s*=\s*(.*?)\s*$/mi';

    if (!preg_match($pattern, $raw, $m)) {
        return null;
    }

    return trim((string)$m[1], " \t\n\r\0\x0B\"'");
}

function dre_true(?string $value): bool
{
    return strcasecmp(trim((string)$value), 'true') === 0;
}

function dre_selected(string $value, string $expected): string
{
    return strcasecmp($value, $expected) === 0 ? ' selected' : '';
}

function dre_checked(bool $value): string
{
    return $value ? ' checked' : '';
}

function dre_valid_name(string $value): bool
{
    if ($value === '' || $value === '.' || $value === '..') {
        return false;
    }

    if (preg_match('/[<>:"\\\/|?*\x00-\x1F]/', $value)) {
        return false;
    }

    if (preg_match('/[\. ]$/', $value)) {
        return false;
    }

    return true;
}

function dre_valid_uuid(string $value): bool
{
    return (bool)preg_match(
        '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
        $value
    );
}

function dre_digits(string $value): string
{
    return preg_replace('/[^0-9]/', '', $value) ?? '';
}

function dre_set_key(string $raw, string $key, string $value): string
{
    $lines = preg_split('/\r\n|\n|\r/', $raw);
    if (!is_array($lines)) {
        $lines = [];
    }

    $out = [];
    $done = false;
    $pattern = '/^\s*' . preg_quote($key, '/') . '\s*=/i';

    foreach ($lines as $line) {
        if (preg_match($pattern, $line)) {
            if (!$done) {
                $out[] = $key . '=' . $value;
                $done = true;
            }
            continue;
        }
        $out[] = $line;
    }

    if (!$done) {
        while ($out && trim((string)end($out)) === '') {
            array_pop($out);
        }
        $out[] = $key . '=' . $value;
    }

    return rtrim(implode("\r\n", $out), "\r\n") . "\r\n";
}

function dre_rename_section(string $raw, string $oldName, string $newName): string
{
    if (strcasecmp($oldName, $newName) === 0) {
        return $raw;
    }

    $pattern = '/^\s*\[' . preg_quote($oldName, '/') . '\]\s*$/mi';
    $changed = preg_replace($pattern, '[' . $newName . ']', $raw, 1);
    return is_string($changed) ? $changed : $raw;
}

function dre_backup(string $source, string $backupRoot): string
{
    if (!is_dir($backupRoot) && !@mkdir($backupRoot, 0775, true) && !is_dir($backupRoot)) {
        throw new RuntimeException('Could not create the region backup folder.');
    }

    $stamp = date('Ymd-His');
    $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($source)) ?: 'region.ini';
    $dest = rtrim($backupRoot, '/\\') . '/' . $stamp . '-' . bin2hex(random_bytes(3)) . '-' . $safe;

    if (!@copy($source, $dest)) {
        throw new RuntimeException('Could not back up the Region INI before saving.');
    }

    return $dest;
}

function dre_write_verified(string $path, string $raw): void
{
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create the target Region folder.');
    }

    $bytes = @file_put_contents($path, $raw, LOCK_EX);
    if ($bytes === false) {
        throw new RuntimeException('Could not write the Region INI.');
    }

    $verify = @file_get_contents($path);
    if (!is_string($verify) || $verify !== $raw) {
        throw new RuntimeException('Region INI verification failed after write.');
    }
}

function dre_db(): ?mysqli
{
    if (!function_exists('ag_region_db_connect')) {
        return null;
    }

    try {
        $db = ag_region_db_connect();
        return $db instanceof mysqli ? $db : null;
    }
    catch (Throwable $e) {
        return null;
    }
}

function dre_estate_names(?mysqli $db, string $fallback): array
{
    $names = [];

    if ($db) {
        $result = @mysqli_query($db, 'SELECT EstateName FROM estate_settings ORDER BY EstateName');
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $name = trim((string)($row['EstateName'] ?? ''));
                if ($name !== '') {
                    $names[strtolower($name)] = $name;
                }
            }
            mysqli_free_result($result);
        }
    }

    if ($fallback !== '') {
        $names[strtolower($fallback)] = $fallback;
    }

    natcasesort($names);
    return array_values($names);
}

function dre_estate_id(mysqli $db, string $estateName): int
{
    $stmt = mysqli_prepare($db, 'SELECT EstateID FROM estate_settings WHERE EstateName = ? LIMIT 1');
    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 's', $estateName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ? (int)$row['EstateID'] : 0;
}

function dre_set_estate(mysqli $db, string $uuid, int $estateId): void
{
    $delete = mysqli_prepare($db, 'DELETE FROM estate_map WHERE RegionID = ?');
    if (!$delete) {
        throw new RuntimeException('Could not prepare estate reassignment.');
    }
    mysqli_stmt_bind_param($delete, 's', $uuid);
    if (!mysqli_stmt_execute($delete)) {
        mysqli_stmt_close($delete);
        throw new RuntimeException('Could not clear the old estate assignment.');
    }
    mysqli_stmt_close($delete);

    $insert = mysqli_prepare($db, 'INSERT INTO estate_map (RegionID, EstateID) VALUES (?, ?)');
    if (!$insert) {
        throw new RuntimeException('Could not prepare the new estate assignment.');
    }
    mysqli_stmt_bind_param($insert, 'si', $uuid, $estateId);
    if (!mysqli_stmt_execute($insert)) {
        $message = mysqli_stmt_error($insert);
        mysqli_stmt_close($insert);
        throw new RuntimeException('Could not assign the estate: ' . $message);
    }
    mysqli_stmt_close($insert);
}

function dre_enabled_region_uuids(string $root): array
{
    $uuids = [];
    $files = @glob($root . '/*/Region/*.ini');
    if (!is_array($files)) {
        return [];
    }

    foreach ($files as $path) {
        $raw = @file_get_contents($path);
        if (!is_string($raw)) {
            continue;
        }

        $enabled = dre_ini_value($raw, 'Enabled');
        $uuid = trim((string)dre_ini_value($raw, 'RegionUUID'));

        if (dre_true($enabled) && dre_valid_uuid($uuid)) {
            $uuids[strtolower($uuid)] = $uuid;
        }
    }

    return array_values($uuids);
}

function dre_concierge_path(string $root, string $name): string
{
    return rtrim($root, '/\\') . '/' . $name . '/' . $name . '.txt';
}

function dre_all_region_names(string $root): array
{
    $names = [];
    $files = @glob($root . '/*/Region/*.ini');
    if (!is_array($files)) return [];

    foreach ($files as $path) {
        $raw = @file_get_contents($path);
        if (!is_string($raw)) continue;
        if (!preg_match('/^\s*\[([^\]]+)\]\s*$/m', $raw, $m)) continue;
        $name = trim((string)$m[1]);
        if ($name !== '') $names[strtolower($name)] = $name;
    }

    natcasesort($names);
    return array_values($names);
}

$regionChoices = dre_all_region_names($regionsRoot);

$rows = dre_region_list();
$live = dre_find_live_region($regionName, $rows);
$currentRunning = dre_live_running($live);
$anyRunning = dre_any_running($rows);
$ini = dre_find_region_ini($regionsRoot, $regionName);

if (!$ini) {
    http_response_code(404);
    exit('Region INI was not found.');
}

$originalPath = (string)$ini['path'];
$originalRaw = (string)$ini['raw'];
$originalGroup = (string)$ini['group'];
$originalUuid = trim((string)dre_ini_value($originalRaw, 'RegionUUID'));
$originalForeigners = dre_true(dre_ini_value($originalRaw, 'DisallowForeigners'));
$originalResidents = dre_true(dre_ini_value($originalRaw, 'DisallowResidents'));
$originalEstate = trim((string)dre_ini_value($originalRaw, 'Estate'));
$db = dre_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'raw_save') {
    try {
        $postedToken = (string)($_POST['csrf_token'] ?? '');
        if (!hash_equals($csrfToken, $postedToken)) {
            throw new RuntimeException('Security token expired. Reload the page and try again.');
        }

        $rawDraft = (string)($_POST['raw_ini'] ?? '');
        if (trim($rawDraft) === '') {
            throw new RuntimeException('Region INI cannot be empty.');
        }

        if (preg_match_all('/^\s*\[[^\]]+\]\s*$/m', $rawDraft) !== 1) {
            throw new RuntimeException('Region INI must contain exactly one Region section.');
        }

        if (!preg_match('/^\s*\[' . preg_quote($regionName, '/') . '\]\s*$/mi', $rawDraft)) {
            throw new RuntimeException('The Region section name must remain [' . $regionName . '] in the raw editor. Use the Name field for a native rename.');
        }

        $rawUuid = trim((string)dre_ini_value($rawDraft, 'RegionUUID'));
        if (!dre_valid_uuid($rawUuid)) {
            throw new RuntimeException('Raw Region INI contains an invalid RegionUUID.');
        }

        $backupPath = dre_backup($originalPath, $backupRoot);
        $normalizedRaw = rtrim(str_replace(["\r\n", "\r"], "\n", $rawDraft), "\n") . "\r\n";
        $normalizedRaw = str_replace("\n", "\r\n", str_replace("\r\n", "\n", $normalizedRaw));
        dre_write_verified($originalPath, $normalizedRaw);

        $success = 'Raw Region INI saved. Backup: ' . $backupPath;
        $ini = dre_find_region_ini($regionsRoot, $regionName);
        if (!$ini) throw new RuntimeException('Raw Region INI saved but could not be reloaded.');
        $originalPath = (string)$ini['path'];
        $originalRaw = (string)$ini['raw'];
    }
    catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'save') {
    try {
        $postedToken = (string)($_POST['csrf_token'] ?? '');
        if (!hash_equals($csrfToken, $postedToken)) {
            throw new RuntimeException('Security token expired. Reload the page and try again.');
        }

        $formDraft = $_POST;

        $newName = trim((string)($_POST['region_name'] ?? $regionName));
        $newGroup = trim((string)($_POST['group_name'] ?? $originalGroup));
        $enabled = (string)($_POST['enabled'] ?? '0') === '1';
        $locked = (string)($_POST['locked'] ?? '0') === '1';
        $estate = trim((string)($_POST['estate'] ?? $originalEstate));
        $applyEstateAll = (string)($_POST['apply_estate_all'] ?? '0') === '1';
        $smartMode = (string)($_POST['smart_mode'] ?? 'off');
        $simSize = (int)dre_digits((string)($_POST['sim_size'] ?? '1'));

        $coordXText = dre_digits((string)($_POST['coord_x'] ?? '0'));
        $coordYText = dre_digits((string)($_POST['coord_y'] ?? ''));
        $coordX = (int)('0' . $coordXText);
        $coordY = (int)('0' . $coordYText);

        $nonPhysicalPrimMax = dre_digits((string)($_POST['nonphysical_prim_max'] ?? ''));
        $physicalPrimMax = dre_digits((string)($_POST['physical_prim_max'] ?? ''));
        $maxPrims = dre_digits((string)($_POST['max_prims'] ?? ''));
        $maxAgents = dre_digits((string)($_POST['max_agents'] ?? ''));
        $clampPrimSize = (string)($_POST['clamp_prim_size'] ?? '0') === '1';
        $uuid = trim((string)($_POST['uuid'] ?? $originalUuid));

        $mapType = (string)($_POST['map_type'] ?? 'Default');
        $landing = trim((string)($_POST['default_landing'] ?? '<128,128,30>'));
        if ($landing === '') {
            $landing = '<128,128,30>';
        }

        $physics = (string)($_POST['physics'] ?? '3');
        $scriptEngine = (string)($_POST['script_engine'] ?? '');
        $asyncLLRaw = trim((string)($_POST['async_ll'] ?? '100'));
        $timerRateRaw = trim((string)($_POST['timer_rate'] ?? ''));
        $frameRateRaw = trim((string)($_POST['frame_rate'] ?? ''));

        $permissionMode = (string)($_POST['permission_mode'] ?? 'default');
        $publicityMode = (string)($_POST['publicity_mode'] ?? 'default');
        $apiKey = trim((string)($_POST['api_key'] ?? ''));

        $birds = (string)($_POST['birds'] ?? '0') === '1';
        $tides = (string)($_POST['tides'] ?? '0') === '1';
        $teleport = (string)($_POST['teleport'] ?? '0') === '1';
        $disableGloebits = (string)($_POST['disable_gloebits'] ?? '0') === '1';
        $disallowForeigners = (string)($_POST['disallow_foreigners'] ?? '0') === '1';
        $disallowResidents = (string)($_POST['disallow_residents'] ?? '0') === '1';
        $skipAutoBackup = (string)($_POST['skip_auto_backup'] ?? '0') === '1';
        $concierge = (string)($_POST['concierge'] ?? '0') === '1';
        $conciergeText = (string)($_POST['concierge_text'] ?? '');

        $cores = $_POST['cores'] ?? [];
        if (!is_array($cores)) {
            $cores = [];
        }
        $coreMask = 0;
        foreach ($cores as $coreRaw) {
            $core = (int)$coreRaw;
            if ($core >= 1 && $core <= 24) {
                $coreMask |= (1 << ($core - 1));
            }
        }

        if (!dre_valid_name($newName)) {
            throw new RuntimeException('Region Name contains unsupported characters.');
        }
        if (!dre_valid_name($newGroup)) {
            throw new RuntimeException('Group contains unsupported characters.');
        }
        if (!dre_valid_uuid($uuid)) {
            throw new RuntimeException('Region UUID is invalid.');
        }
        if ($coordX < 0 || $coordX > 65536) {
            throw new RuntimeException('X must be between 0 and 65536.');
        }
        if ($coordY < 32 || $coordY > 65536) {
            throw new RuntimeException('Y must be between 32 and 65536.');
        }
        if ($simSize < 1 || $simSize > 16) {
            throw new RuntimeException('Sim Size must be between 1 x 1 and 16 x 16.');
        }
        if (!preg_match('/^<\d*\.?\d*,\d*\.?\d*,\d*\.?\d*>$/', $landing)) {
            throw new RuntimeException('Default Landing Spot must look like <128,128,30>.');
        }

        foreach ([
            'Nonphysical Prim Size' => $nonPhysicalPrimMax,
            'Physical Prim Max Size' => $physicalPrimMax,
            'Max Prims in Parcel' => $maxPrims,
            'Max Avatars + NPCs' => $maxAgents,
        ] as $label => $numberText) {
            if ($numberText === '') {
                $numberText = '0';
            }
            if ((float)$numberText > 2147483647) {
                throw new RuntimeException($label . ' is too large.');
            }
        }

        $allowedSmart = ['off', 'boot', 'suspend'];
        $allowedMap = ['Default', 'None', 'Simple', 'Good', 'Better', 'Best'];
        $allowedPhysics = ['', '2', '3', '4', '5'];
        $allowedScripts = ['', 'Off', 'YEngine'];
        $allowedPermissions = ['default', 'level', 'owner', 'manager'];
        $allowedPublicity = ['default', 'off', 'search'];

        if (!in_array($smartMode, $allowedSmart, true) ||
            !in_array($mapType, $allowedMap, true) ||
            !in_array($physics, $allowedPhysics, true) ||
            !in_array($scriptEngine, $allowedScripts, true) ||
            !in_array($permissionMode, $allowedPermissions, true) ||
            !in_array($publicityMode, $allowedPublicity, true)) {
            throw new RuntimeException('One of the selected options is invalid.');
        }

        $asyncLL = 100.0;
        if ($asyncLLRaw !== '' && is_numeric($asyncLLRaw)) {
            $asyncLL = (float)$asyncLLRaw;
        }

        $nameChanged = strcasecmp($newName, $regionName) !== 0;
        $groupChanged = strcasecmp($newGroup, $originalGroup) !== 0;
        $uuidChanged = strcasecmp($uuid, $originalUuid) !== 0;
        $accessChanged = ($disallowForeigners !== $originalForeigners) || ($disallowResidents !== $originalResidents);
        $estateChanged = strcasecmp($estate, $originalEstate) !== 0;

        if (($nameChanged || $groupChanged) && $currentRunning) {
            throw new RuntimeException('Stop this region before changing its Name or Group. The native grid service restarts the region for these changes; the web editor will not force a live rename.');
        }
        if ($uuidChanged && $anyRunning) {
            throw new RuntimeException('UUID editing is allowed only while OpenSim is stopped. Stop all regions before changing the UUID.');
        }
        if ($accessChanged && $anyRunning) {
            throw new RuntimeException('Disable Foreign Visitors / Disable All Residents normally forces a grid-wide Restart All. Stop all regions first before changing either setting from the web editor.');
        }

        if (($estateChanged || $applyEstateAll) && !$db) {
            throw new RuntimeException('The estate database connection is not available, so the Estate assignment was not changed.');
        }

        if ($db && ($estateChanged || $applyEstateAll)) {
            $estateId = dre_estate_id($db, $estate);
            if ($estateId <= 0) {
                throw new RuntimeException('The selected Estate was not found in the OpenSim estate database.');
            }

            if ($applyEstateAll) {
                foreach (dre_enabled_region_uuids($regionsRoot) as $enabledUuid) {
                    dre_set_estate($db, $enabledUuid, $estateId);
                }
            } else {
                dre_set_estate($db, $originalUuid, $estateId);
            }
        }

        $newRaw = $originalRaw;
        $newRaw = dre_rename_section($newRaw, $regionName, $newName);

        $smartBoot = $smartMode === 'boot' ? 'True' : 'False';
        $smartStart = $smartMode === 'suspend' ? 'True' : 'False';
        $size = (string)($simSize * 256);

        $newRaw = dre_set_key($newRaw, 'AllowAlternatePorts', 'False');
        $newRaw = dre_set_key($newRaw, 'Enabled', $enabled ? 'True' : 'False');
        $newRaw = dre_set_key($newRaw, 'Estate', $estate);
        $newRaw = dre_set_key($newRaw, 'Locked', $locked ? 'True' : 'False');
        $newRaw = dre_set_key($newRaw, 'SmartBoot', $smartBoot);
        $newRaw = dre_set_key($newRaw, 'SmartStart', $smartStart);
        $newRaw = dre_set_key($newRaw, 'SizeX', $size);
        $newRaw = dre_set_key($newRaw, 'SizeY', $size);
        $newRaw = dre_set_key($newRaw, 'CoordX', (string)$coordX);
        $newRaw = dre_set_key($newRaw, 'CoordY', (string)$coordY);
        $newRaw = dre_set_key($newRaw, 'Location', $coordX . ',' . $coordY);
        $newRaw = dre_set_key($newRaw, 'NonPhysicalPrimMax', $nonPhysicalPrimMax === '' ? '0' : $nonPhysicalPrimMax);
        $newRaw = dre_set_key($newRaw, 'PhysicalPrimMax', $physicalPrimMax === '' ? '0' : $physicalPrimMax);
        $newRaw = dre_set_key($newRaw, 'MaxPrims', $maxPrims === '' ? '0' : $maxPrims);
        $newRaw = dre_set_key($newRaw, 'MaxAgents', $maxAgents === '' ? '0' : $maxAgents);
        $newRaw = dre_set_key($newRaw, 'ClampPrimSize', $clampPrimSize ? 'True' : 'False');
        $newRaw = dre_set_key($newRaw, 'RegionUUID', $uuid);
        $newRaw = dre_set_key($newRaw, 'MapType', $mapType);
        $newRaw = dre_set_key($newRaw, 'DefaultLanding', $landing);
        $newRaw = dre_set_key($newRaw, 'Physics', $physics);
        $newRaw = dre_set_key($newRaw, 'ScriptEngine', $scriptEngine);
        $newRaw = dre_set_key($newRaw, 'AsyncScriptLLTimeMs', rtrim(rtrim(sprintf('%.12F', $asyncLL), '0'), '.'));
        $newRaw = dre_set_key($newRaw, 'MinTimerInterval', $timerRateRaw === '' ? '0' : $timerRateRaw);
        $newRaw = dre_set_key($newRaw, 'FrameTime', $frameRateRaw === '' ? '0' : $frameRateRaw);
        $newRaw = dre_set_key($newRaw, 'OpenSimWorldAPIKey', $apiKey);

        if ($permissionMode === 'level') {
            $newRaw = dre_set_key($newRaw, 'AllowGods', 'True');
            $newRaw = dre_set_key($newRaw, 'GodDefault', 'False');
            $newRaw = dre_set_key($newRaw, 'ManagerGod', 'False');
            $newRaw = dre_set_key($newRaw, 'RegionGod', 'False');
        } elseif ($permissionMode === 'owner') {
            $newRaw = dre_set_key($newRaw, 'AllowGods', 'False');
            $newRaw = dre_set_key($newRaw, 'GodDefault', 'False');
            $newRaw = dre_set_key($newRaw, 'ManagerGod', 'False');
            $newRaw = dre_set_key($newRaw, 'RegionGod', 'True');
        } elseif ($permissionMode === 'manager') {
            $newRaw = dre_set_key($newRaw, 'AllowGods', 'False');
            $newRaw = dre_set_key($newRaw, 'GodDefault', 'False');
            $newRaw = dre_set_key($newRaw, 'ManagerGod', 'True');
            $newRaw = dre_set_key($newRaw, 'RegionGod', 'False');
        } else {
            $newRaw = dre_set_key($newRaw, 'AllowGods', '');
            $newRaw = dre_set_key($newRaw, 'GodDefault', 'True');
            $newRaw = dre_set_key($newRaw, 'ManagerGod', '');
            $newRaw = dre_set_key($newRaw, 'RegionGod', '');
        }

        if ($publicityMode === 'off') {
            $newRaw = dre_set_key($newRaw, 'Publicity', '');
            $newRaw = dre_set_key($newRaw, 'RegionSnapShot', 'False');
        } elseif ($publicityMode === 'search') {
            $newRaw = dre_set_key($newRaw, 'Publicity', 'True');
            $newRaw = dre_set_key($newRaw, 'RegionSnapShot', 'True');
        } else {
            $newRaw = dre_set_key($newRaw, 'Publicity', '');
            $newRaw = dre_set_key($newRaw, 'RegionSnapShot', '');
        }

        $newRaw = dre_set_key($newRaw, 'Birds', $birds ? 'True' : '');
        $newRaw = dre_set_key($newRaw, 'Tides', $tides ? 'True' : '');
        $newRaw = dre_set_key($newRaw, 'Teleport', $teleport ? 'True' : 'False');
        $newRaw = dre_set_key($newRaw, 'DisableGloebits', $disableGloebits ? 'True' : '');
        $newRaw = dre_set_key($newRaw, 'DisallowForeigners', $disallowForeigners ? 'True' : '');
        $newRaw = dre_set_key($newRaw, 'DisallowResidents', $disallowResidents ? 'True' : '');
        $newRaw = dre_set_key($newRaw, 'SkipAutoBackup', $skipAutoBackup ? 'True' : '');
        $newRaw = dre_set_key($newRaw, 'Concierge', $concierge ? 'True' : 'False');
        $newRaw = dre_set_key($newRaw, 'Cores', (string)$coreMask);

        $targetDir = rtrim($regionsRoot, '/\\') . '/' . $newGroup . '/Region';
        $targetPath = $targetDir . '/' . $newName . '.ini';
        $samePath = strcasecmp(str_replace('\\', '/', $targetPath), str_replace('\\', '/', $originalPath)) === 0;

        if (!$samePath && file_exists($targetPath)) {
            throw new RuntimeException('The target Region INI already exists: ' . $targetPath);
        }

        $backupPath = dre_backup($originalPath, $backupRoot);
        dre_write_verified($targetPath, $newRaw);

        if (!$samePath && file_exists($originalPath) && !@unlink($originalPath)) {
            @unlink($targetPath);
            @copy($backupPath, $originalPath);
            throw new RuntimeException('The new Region INI was written, but the old INI could not be removed. The change was rolled back.');
        }

        $conciergePath = dre_concierge_path($regionsRoot, $newName);
        if ($conciergeText !== '') {
            $conciergeDir = dirname($conciergePath);
            if (!is_dir($conciergeDir) && !@mkdir($conciergeDir, 0775, true) && !is_dir($conciergeDir)) {
                throw new RuntimeException('Region INI saved, but the Concierge text folder could not be created.');
            }
            if (@file_put_contents($conciergePath, $conciergeText, LOCK_EX) === false) {
                throw new RuntimeException('Region INI saved, but the Concierge text could not be written.');
            }
        } elseif (file_exists($conciergePath)) {
            @unlink($conciergePath);
        }

        $regionName = $newName;
        $success = 'Region settings saved. Backup: ' . $backupPath;
        if ($nameChanged || $groupChanged || $uuidChanged) {
            $success .= ' Name / Group / UUID changes are loaded cleanly after a full grid restart.';
        }

        $rows = dre_region_list();
        $live = dre_find_live_region($regionName, $rows);
        $currentRunning = dre_live_running($live);
        $anyRunning = dre_any_running($rows);
        $ini = dre_find_region_ini($regionsRoot, $regionName);
        if (!$ini) {
            throw new RuntimeException('Saved the Region INI, but could not reload it for display.');
        }
    }
    catch (Throwable $e) {
        $error = $e->getMessage();
        $ini = dre_find_region_ini($regionsRoot, $regionName) ?? $ini;
    }
}

if ($error === '') $formDraft = null;

$raw = (string)$ini['raw'];
$get = static function (string $key, string $default = '') use ($raw): string {
    $value = dre_ini_value($raw, $key);
    return $value === null ? $default : $value;
};

$enabled = dre_true($get('Enabled', 'True'));
$locked = dre_true($get('Locked', 'False'));
$groupName = (string)$ini['group'];
$estate = $get('Estate', '');
$uuid = $get('RegionUUID', '');
$coordX = $get('CoordX', '0');
$coordY = $get('CoordY', '32');
$port = $get('InternalPort', '');
$maxAgents = $get('MaxAgents', '100');
$maxPrims = $get('MaxPrims', '0');
$nonPhysicalPrimMax = $get('NonPhysicalPrimMax', '1024');
$physicalPrimMax = $get('PhysicalPrimMax', '64');
$clampPrimSize = dre_true($get('ClampPrimSize', 'False'));
$mapType = $get('MapType', 'Default');
$landing = $get('DefaultLanding', '<128,128,30>');
$physics = $get('Physics', '3');
$scriptEngine = $get('ScriptEngine', '');
$asyncLL = $get('AsyncScriptLLTimeMs', '100');
$timerRate = $get('MinTimerInterval', '0.2');
$frameRate = $get('FrameTime', '0.09090909090909091');
$apiKey = $get('OpenSimWorldAPIKey', '');

$smartBoot = dre_true($get('SmartBoot', 'False'));
$smartStart = dre_true($get('SmartStart', 'False'));
$smartMode = $smartStart ? 'suspend' : ($smartBoot ? 'boot' : 'off');

$sizeX = (int)$get('SizeX', '256');
$simSize = max(1, min(16, (int)round($sizeX / 256)));

$allowGods = $get('AllowGods', '');
$godDefault = $get('GodDefault', 'True');
$managerGod = $get('ManagerGod', '');
$regionGod = $get('RegionGod', '');

if (strcasecmp($allowGods, 'True') === 0 && strcasecmp($godDefault, 'False') === 0) {
    $permissionMode = 'level';
} elseif (strcasecmp($regionGod, 'True') === 0) {
    $permissionMode = 'owner';
} elseif (strcasecmp($managerGod, 'True') === 0) {
    $permissionMode = 'manager';
} else {
    $permissionMode = 'default';
}

$publicity = $get('Publicity', '');
$regionSnapshot = $get('RegionSnapShot', '');

if (strcasecmp($regionSnapshot, 'False') === 0) {
    $publicityMode = 'off';
} elseif (strcasecmp($publicity, 'True') === 0 && strcasecmp($regionSnapshot, 'True') === 0) {
    $publicityMode = 'search';
} else {
    $publicityMode = 'default';
}

$modules = [
    'birds' => dre_true($get('Birds', '')),
    'tides' => dre_true($get('Tides', '')),
    'teleport' => dre_true($get('Teleport', 'True')),
    'gloebits' => dre_true($get('DisableGloebits', '')),
    'foreigners' => dre_true($get('DisallowForeigners', '')),
    'residents' => dre_true($get('DisallowResidents', '')),
    'skipbackup' => dre_true($get('SkipAutoBackup', '')),
    'concierge' => dre_true($get('Concierge', 'False')),
];

$coreMask = (int)$get('Cores', '0');
$estateNames = dre_estate_names($db, $estate);

$mapUrl = '';
if (is_array($live)) {
    $mapHtml = (string)($live['Map'] ?? '');
    if (preg_match("~src\s*=\s*['\"]([^'\"]+)['\"]~i", $mapHtml, $m)) {
        $mapUrl = trim(html_entity_decode((string)$m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}

$conciergePath = dre_concierge_path($regionsRoot, $regionName);
$conciergeText = file_exists($conciergePath) ? (string)@file_get_contents($conciergePath) : '';

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Region - <?=dre_h($regionName)?></title>
<link rel="stylesheet" href="/Other/core/site-shell.css?v=1">

<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">






<style id="australia-region-edit-clean-v1">

:root{

    --cc-black:#050706;

    --cc-panel:#0a0c0b;
    --cc-panel2:#111310;
    --cc-panel3:#171914;

    --cc-gold:#a87b25;
    --cc-gold-bright:#e2b64f;
    --cc-gold-soft:#74602d;

    --cc-border:#665427;
    --cc-metal:#55564e;

    --cc-text:#dedbd2;
    --cc-muted:#8f918b;

    --cc-green:#75ce72;
    --cc-red:#ba5555;

    --cc-font:
        "Segoe UI",
        Arial,
        Helvetica,
        sans-serif;
}


html{

    background:
        var(--cc-black);

}


body#australia-region-edit-page{

    margin:
        0 !important;

    color:
        var(--cc-text) !important;

    background:
        var(--cc-black) !important;

    background-image:
        none !important;

    font-family:
        var(--cc-font) !important;

}


body#australia-region-edit-page *{

    box-sizing:
        border-box;

}


body#australia-region-edit-page .ag-shell{

    width:
        100% !important;

    max-width:
        none !important;

    margin:
        0 !important;

    padding:
        18px !important;

    color:
        var(--cc-text) !important;

    background:
        var(--cc-black) !important;

    background-image:
        none !important;

}


/* =========================================================
   HEADER
   ========================================================= */

body#australia-region-edit-page .ag-header{

    color:
        var(--cc-text) !important;

    border:
        1px solid var(--cc-border) !important;

    border-radius:
        6px !important;

    background:
        linear-gradient(
            180deg,
            #151713 0%,
            #0a0c0a 100%
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.05),
        0 2px 7px rgba(0,0,0,.55) !important;

}


body#australia-region-edit-page .ag-header h1,
body#australia-region-edit-page .ag-header h2{

    color:
        var(--cc-gold-bright) !important;

}


body#australia-region-edit-page .ag-eyebrow{

    color:
        var(--cc-gold-bright) !important;

    letter-spacing:
        .14em !important;

}


/* =========================================================
   TOP NAV
   ========================================================= */

body#australia-region-edit-page .ag-nav{

    border:
        1px solid #40371f !important;

    border-radius:
        6px !important;

    background:
        #080a08 !important;

    background-image:
        none !important;

}


body#australia-region-edit-page .ag-nav a{

    color:
        #d7d7d1 !important;

    border:
        1px solid #484a44 !important;

    border-radius:
        5px !important;

    background:
        linear-gradient(
            180deg,
            #292a25 0,
            #20211d 7px,
            #111310 8px,
            #0d0f0d 100%
        ) !important;

    box-shadow:
        inset 0 1px 0 #64645d,
        0 1px 2px rgba(0,0,0,.9) !important;

}


body#australia-region-edit-page .ag-nav a:hover{

    color:
        #f4e6bd !important;

    border-color:
        #8c6b29 !important;

    background:
        linear-gradient(
            180deg,
            #34342e 0,
            #29291f 7px,
            #181711 8px,
            #11110e 100%
        ) !important;

}


/* =========================================================
   REGION EDIT CARD
   ========================================================= */

body#australia-region-edit-page .region-edit-card{

    width:
        100% !important;

    max-width:
        none !important;

    margin:
        0 !important;

    padding:
        20px !important;

    color:
        var(--cc-text) !important;

    border:
        1px solid var(--cc-border) !important;

    border-radius:
        8px !important;

    background:
        linear-gradient(
            180deg,
            var(--cc-panel2) 0%,
            var(--cc-panel) 100%
        ) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.035),
        0 4px 14px rgba(0,0,0,.55) !important;

}


body#australia-region-edit-page .ccre-region-title,
body#australia-region-edit-page .region-edit-card > h2{

    margin:
        5px 0 14px !important;

    color:
        var(--cc-gold-bright) !important;

}


body#australia-region-edit-page .dg-status{

    display:
        inline-flex !important;

    align-items:
        center !important;

    margin-left:
        10px !important;

    padding:
        4px 8px !important;

    color:
        #ffe3a0 !important;

    border:
        1px solid #8c6b29 !important;

    border-radius:
        999px !important;

    background:
        #15110b !important;

    font-size:
        11px !important;

    font-weight:
        800 !important;

}


/* =========================================================
   NOTICE BOXES
   ========================================================= */

body#australia-region-edit-page .region-edit-note,
body#australia-region-edit-page .region-edit-warning,
body#australia-region-edit-page .ag-error,
body#australia-region-edit-page .ag-success{

    margin-bottom:
        16px !important;

    padding:
        12px 14px !important;

    border-radius:
        6px !important;

    background-image:
        none !important;

    box-shadow:
        none !important;

}


body#australia-region-edit-page .region-edit-note{

    color:
        var(--cc-text) !important;

    border:
        1px solid #544724 !important;

    background:
        #10120f !important;

}


body#australia-region-edit-page .region-edit-warning{

    color:
        #f0ca62 !important;

    border:
        1px solid #6e5723 !important;

    background:
        #181408 !important;

}


body#australia-region-edit-page .ag-success{

    color:
        #75ce72 !important;

    border:
        1px solid #315d31 !important;

    background:
        #091309 !important;

}


body#australia-region-edit-page .ag-error{

    color:
        #e68181 !important;

    border:
        1px solid #713535 !important;

    background:
        #170909 !important;

}


/* =========================================================
   REGION EDIT BUTTON ROW

   REAL CONTROL CENTER BUTTON COLOURS
   ========================================================= */

body#australia-region-edit-page .ccre-tabs{

    display:
        flex !important;

    align-items:
        center !important;

    flex-wrap:
        wrap !important;

    gap:
        4px !important;

    width:
        100% !important;

    margin:
        0 0 18px !important;

    padding:
        0 0 12px !important;

    border-bottom:
        1px solid var(--cc-border) !important;

}


body#australia-region-edit-page
.ccre-tabs > button.ccre-tab{

    position:
        relative !important;

    width:
        auto !important;

    height:
        37px !important;

    min-height:
        37px !important;

    display:
        inline-flex !important;

    align-items:
        center !important;

    justify-content:
        center !important;

    margin:
        0 !important;

    padding:
        4px 12px !important;

    background:
        linear-gradient(
            180deg,
            #292a25 0,
            #20211d 7px,
            #111310 8px,
            #0d0f0d 100%
        ) !important;

    border:
        1px solid #484a44 !important;

    border-radius:
        5px !important;

    box-shadow:
        inset 0 1px 0 #64645d,
        0 1px 2px rgba(0,0,0,.9) !important;

    color:
        #d7d7d1 !important;

    cursor:
        pointer !important;

    font-family:
        var(--cc-font) !important;

    font-size:
        8.8px !important;

    font-weight:
        600 !important;

    line-height:
        1 !important;

    text-align:
        center !important;

    appearance:
        none !important;

    -webkit-appearance:
        none !important;

    transition:
        border-color .12s ease,
        color .12s ease,
        background .12s ease !important;

}


body#australia-region-edit-page
.ccre-tabs > button.ccre-tab:hover{

    border-color:
        #8c6b29 !important;

    color:
        #f4e6bd !important;

    background:
        linear-gradient(
            180deg,
            #34342e 0,
            #29291f 7px,
            #181711 8px,
            #11110e 100%
        ) !important;

}


body#australia-region-edit-page
.ccre-tabs > button.ccre-tab.active{

    border-color:
        #c18f2c !important;

    color:
        #ffe3a0 !important;

    background:
        linear-gradient(
            180deg,
            #4a402d 0,
            #4a402d 7px,
            #211b10 8px,
            #15110b 100%
        ) !important;

    box-shadow:
        inset 0 0 0 1px #59451a,
        inset 0 1px 0 #81704b !important;

}


/* =========================================================
   REGION PANELS
   ========================================================= */

body#australia-region-edit-page .region-panel{

    display:
        none;

}


body#australia-region-edit-page .region-panel.active{

    display:
        block;

}


body#australia-region-edit-page .dg-group{

    margin-bottom:
        16px !important;

    padding:
        16px !important;

    color:
        var(--cc-text) !important;

    border:
        1px solid #554722 !important;

    border-radius:
        6px !important;

    background:
        #080a08 !important;

    background-image:
        none !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.025) !important;

}


body#australia-region-edit-page .dg-group h3{

    margin:
        0 0 14px !important;

    color:
        var(--cc-gold-bright) !important;

    font-size:
        15px !important;

    font-weight:
        800 !important;

}


/* =========================================================
   FORM GRID
   ========================================================= */

body#australia-region-edit-page .dg-grid{

    display:
        grid !important;

    grid-template-columns:
        repeat(2,minmax(0,1fr)) !important;

    gap:
        14px 18px !important;

}


body#australia-region-edit-page .dg-grid.three{

    grid-template-columns:
        repeat(3,minmax(0,1fr)) !important;

}


body#australia-region-edit-page .dg-field label,
body#australia-region-edit-page .dg-check-title{

    display:
        block !important;

    margin-bottom:
        6px !important;

    color:
        #a9a28e !important;

    font-size:
        11px !important;

    font-weight:
        700 !important;

    text-transform:
        uppercase !important;

    letter-spacing:
        .04em !important;

}


body#australia-region-edit-page .dg-field input,
body#australia-region-edit-page .dg-field select,
body#australia-region-edit-page .dg-field textarea{

    width:
        100% !important;

    min-height:
        40px !important;

    padding:
        0 10px !important;

    color:
        #f1eee5 !important;

    border:
        1px solid #403f39 !important;

    border-radius:
        5px !important;

    outline:
        none !important;

    background:
        #050605 !important;

    background-image:
        none !important;

    box-shadow:
        inset 0 1px 2px rgba(0,0,0,.8) !important;

}


body#australia-region-edit-page .dg-field textarea{

    min-height:
        100px !important;

    padding:
        10px !important;

    resize:
        vertical !important;

}


body#australia-region-edit-page .dg-field input:focus,
body#australia-region-edit-page .dg-field select:focus,
body#australia-region-edit-page .dg-field textarea:focus{

    border-color:
        #a87b25 !important;

    box-shadow:
        inset 0 1px 2px rgba(0,0,0,.8),
        0 0 0 1px #59451a !important;

}


body#australia-region-edit-page .dg-field input[readonly],
body#australia-region-edit-page .dg-field input:disabled,
body#australia-region-edit-page .dg-field select:disabled,
body#australia-region-edit-page .dg-field textarea:disabled{

    color:
        #77776e !important;

    border-color:
        #292b25 !important;

    background:
        #0a0b09 !important;

}


/* =========================================================
   CHECKBOXES
   ========================================================= */

body#australia-region-edit-page .dg-check{

    display:
        flex !important;

    align-items:
        center !important;

    gap:
        9px !important;

    min-height:
        36px !important;

    color:
        var(--cc-text) !important;

}


body#australia-region-edit-page input[type="checkbox"]{

    accent-color:
        #a87b25 !important;

}


/* =========================================================
   ACTION BUTTONS
   ========================================================= */

body#australia-region-edit-page .dg-actions{

    display:
        flex !important;

    gap:
        7px !important;

    flex-wrap:
        wrap !important;

    margin-top:
        14px !important;

}


body#australia-region-edit-page .ag-button,
body#australia-region-edit-page button.ag-button,
body#australia-region-edit-page a.ag-button{

    min-height:
        37px !important;

    padding:
        4px 12px !important;

    display:
        inline-flex !important;

    align-items:
        center !important;

    justify-content:
        center !important;

    color:
        #d7d7d1 !important;

    border:
        1px solid #484a44 !important;

    border-radius:
        5px !important;

    background:
        linear-gradient(
            180deg,
            #292a25 0,
            #20211d 7px,
            #111310 8px,
            #0d0f0d 100%
        ) !important;

    box-shadow:
        inset 0 1px 0 #64645d,
        0 1px 2px rgba(0,0,0,.9) !important;

    text-decoration:
        none !important;

    cursor:
        pointer !important;

    font-family:
        var(--cc-font) !important;

    font-size:
        8.8px !important;

    font-weight:
        600 !important;

}


body#australia-region-edit-page
.ag-button:hover:not(:disabled),

body#australia-region-edit-page
button.ag-button:hover:not(:disabled),

body#australia-region-edit-page
a.ag-button:hover{

    border-color:
        #8c6b29 !important;

    color:
        #f4e6bd !important;

    background:
        linear-gradient(
            180deg,
            #34342e 0,
            #29291f 7px,
            #181711 8px,
            #11110e 100%
        ) !important;

}


body#australia-region-edit-page .ag-button.primary,
body#australia-region-edit-page button.ag-button.primary{

    border-color:
        #c18f2c !important;

    color:
        #ffe3a0 !important;

    background:
        linear-gradient(
            180deg,
            #4a402d 0,
            #4a402d 7px,
            #211b10 8px,
            #15110b 100%
        ) !important;

    box-shadow:
        inset 0 0 0 1px #59451a,
        inset 0 1px 0 #81704b !important;

}


body#australia-region-edit-page .ag-button:disabled,
body#australia-region-edit-page button.ag-button:disabled{

    opacity:
        .45 !important;

    cursor:
        not-allowed !important;

}


/* =========================================================
   NOTES / LINKS
   ========================================================= */

body#australia-region-edit-page .dg-native-note{

    margin-top:
        9px !important;

    color:
        var(--cc-muted) !important;

    font-size:
        11px !important;

    line-height:
        1.45 !important;

}


body#australia-region-edit-page .dg-links{

    display:
        flex !important;

    gap:
        12px !important;

    flex-wrap:
        wrap !important;

    margin-top:
        10px !important;

}


body#australia-region-edit-page .dg-links a{

    color:
        var(--cc-gold-bright) !important;

}


body#australia-region-edit-page .dg-links a:hover{

    color:
        #fff4cf !important;

}


/* =========================================================
   MAP
   ========================================================= */

body#australia-region-edit-page .dg-map-preview{

    display:
        flex !important;

    align-items:
        center !important;

    justify-content:
        center !important;

    min-height:
        260px !important;

    overflow:
        hidden !important;

    border:
        1px solid #554722 !important;

    border-radius:
        6px !important;

    background:
        #050605 !important;

}


body#australia-region-edit-page .dg-map-preview img{

    max-width:
        100% !important;

    max-height:
        420px !important;

    object-fit:
        contain !important;

}


/* =========================================================
   CPU
   ========================================================= */

body#australia-region-edit-page .dg-core-grid{

    display:
        grid !important;

    grid-template-columns:
        repeat(6,minmax(0,1fr)) !important;

    gap:
        8px !important;

}


body#australia-region-edit-page .dg-core{

    display:
        flex !important;

    align-items:
        center !important;

    gap:
        7px !important;

    padding:
        8px !important;

    color:
        var(--cc-text) !important;

    border:
        1px solid #403f39 !important;

    border-radius:
        5px !important;

    background:
        #0d0f0c !important;

}


/* =========================================================
   DIALOG
   ========================================================= */

body#australia-region-edit-page dialog{

    width:
        min(760px,calc(100vw - 40px)) !important;

    padding:
        0 !important;

    color:
        var(--cc-text) !important;

    border:
        1px solid #8c6a26 !important;

    border-radius:
        8px !important;

    background:
        #0b0d0a !important;

    box-shadow:
        0 25px 80px rgba(0,0,0,.65) !important;

}


body#australia-region-edit-page dialog::backdrop{

    background:
        rgba(0,0,0,.78) !important;

}


body#australia-region-edit-page .dg-dialog-head{

    padding:
        16px 18px !important;

    color:
        var(--cc-gold-bright) !important;

    border-bottom:
        1px solid #554722 !important;

    background:
        #10120f !important;

    font-weight:
        800 !important;

}


body#australia-region-edit-page .dg-dialog-body{

    padding:
        18px !important;

}


body#australia-region-edit-page .dg-dialog-body textarea{

    width:
        100% !important;

    min-height:
        330px !important;

    padding:
        12px !important;

    color:
        #f1eee5 !important;

    border:
        1px solid #403f39 !important;

    border-radius:
        5px !important;

    background:
        #050605 !important;

    font-family:
        Consolas,
        monospace !important;

}


body#australia-region-edit-page .dg-dialog-actions{

    display:
        flex !important;

    gap:
        7px !important;

    justify-content:
        flex-end !important;

    padding:
        0 18px 18px !important;

}


@media(max-width:900px){

    body#australia-region-edit-page .dg-grid,
    body#australia-region-edit-page .dg-grid.three{

        grid-template-columns:
            1fr !important;

    }


    body#australia-region-edit-page .dg-core-grid{

        grid-template-columns:
            repeat(3,minmax(0,1fr)) !important;

    }


    body#australia-region-edit-page .ag-shell{

        padding:
            10px !important;

    }


    body#australia-region-edit-page .region-edit-card{

        padding:
            14px !important;

    }

}

</style>

<?php if ($dreEmbedded): ?>
<style id="dreamgrid-clean-embedded-region-edit-v1">

html,
body{
    width:100% !important;
    min-height:100% !important;
    margin:0 !important;
    padding:0 !important;
    background:#080b0d !important;
    background-image:none !important;
}

body{
    overflow:auto !important;
}

.ag-shell{
    width:100% !important;
    max-width:none !important;
    min-height:0 !important;
    margin:0 !important;
    padding:18px !important;
    background:#080b0d !important;
}

.region-edit-card{
    width:100% !important;
    max-width:none !important;
    margin:0 !important;
    padding:0 !important;

    border:0 !important;
    border-radius:0 !important;

    background:transparent !important;
    box-shadow:none !important;
}

/*
 * The charcoal/gold parent popup already supplies the window
 * frame. The editor supplies only its own actual controls.
 */
.region-edit-card > .ag-eyebrow{
    margin-top:0 !important;
}

</style>
<?php endif; ?>
</head>
<body id="australia-region-edit-page">
<div class="ag-shell">
<?php if (!$dreEmbedded): ?><?php
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "Edit Region";
$siteHeaderRole = "GRID OWNER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "ADMIN HOME";
$siteHeaderLink = "/Other/admin-home.php";
require_once __DIR__ . "/includes/site-header.php";
?>
<?php endif; ?>
<div class="region-edit-card" id="regionEditCard">
    <div class="ag-eyebrow">DREAMGRID REGIONS</div>
    <h2 class="ccre-region-title"><?=dre_h($regionName)?><span class="dg-status"><?=$currentRunning?'RUNNING':'STOPPED'?></span></h2>

    <?php if ($error !== ''): ?>
        <div class="ag-error"><?=dre_h($error)?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="ag-success" data-dg-region-changed="true"><?=dre_h($success)?></div>
    <?php endif; ?>

    <div class="region-edit-note">
        This is the web counterpart of Start.dll Outworldz.Forms.FormRegion. It reads and writes the real Region INI and uses DreamGrid native Deregister/Delete actions.
    </div>

    <?php if ($anyRunning): ?>
    <div class="region-edit-warning">
        OpenSim is running. UUID is read-only, and the web editor will block changes that the native grid service would handle with a full Restart All. Name/Group changes are also blocked while this region is running.
    </div>
    <?php endif; ?>

    <div class="region-tabs ccre-tabs" role="tablist" aria-label="Region Edit Sections">

    <button type="button"
            class="region-tab ccre-tab active"
            data-tab="tab0">
        Regions
    </button>

    <button type="button"
            class="region-tab ccre-tab"
            data-tab="tab1">
        Options
    </button>

    <button type="button"
            class="region-tab ccre-tab"
            data-tab="tab2">
        Maps
    </button>

    <button type="button"
            class="region-tab ccre-tab"
            data-tab="tab3">
        Physics
    </button>

    <button type="button"
            class="region-tab ccre-tab"
            data-tab="tab4">
        Scripts
    </button>

    <button type="button"
            class="region-tab ccre-tab"
            data-tab="tab5">
        Permissions
    </button>

    <button type="button"
            class="region-tab ccre-tab"
            data-tab="tab6">
        Publicity
    </button>

    <button type="button"
            class="region-tab ccre-tab"
            data-tab="tab7">
        Modules
    </button>

    <button type="button"
            class="region-tab ccre-tab"
            data-tab="tab8">
        CPU %
    </button>

</div>

    <form id="regionEditForm" method="post" action="?region=<?=rawurlencode($regionName)?><?=$dreEmbedded ? '&amp;embedded=1' : ''?>">
        <input type="hidden" name="action" value="save">
        <?php if ($dreEmbedded): ?>
        <input type="hidden" name="embedded" value="1">
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?=dre_h($csrfToken)?>">
        <input type="hidden" name="region_original" value="<?=dre_h($regionName)?>">

        <section class="region-panel active" id="tab0">
            <div class="dg-group">
                <h3>Regions</h3>
                <div class="dg-grid">
                    <div class="dg-field" style="grid-column:1/-1"><label>Name of Region</label><select id="dreRegionSelector"><option value="">New Region</option><?php foreach($regionChoices as $choice): ?><option value="<?=dre_h($choice)?>"<?=dre_selected($choice,$regionName)?>><?=dre_h($choice)?></option><?php endforeach; ?></select></div>
                    <label class="dg-check"><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1"<?=dre_checked($enabled)?>><span>Enabled</span></label>
                    <label class="dg-check"><input type="hidden" name="locked" value="0"><input type="checkbox" id="lockedBox" name="locked" value="1"<?=dre_checked($locked)?>><span>Locked</span></label>
                    <div class="dg-field"><label>Name</label><input class="dg-lockable" id="regionNameField" name="region_name" type="text" value="<?=dre_h($regionName)?>" required></div>
                    <div class="dg-field"><label>Group</label><input class="dg-lockable" id="groupNameField" name="group_name" type="text" value="<?=dre_h($groupName)?>" required></div>
                    <div class="dg-field"><label>Estate assignment</label><select class="dg-lockable" name="estate"<?=$db?'':' disabled'?>><?php foreach($estateNames as $estateName): ?><option value="<?=dre_h($estateName)?>"<?=dre_selected($estate,$estateName)?>><?=dre_h($estateName)?></option><?php endforeach; ?></select></div>
                    <label class="dg-check dg-lockable"><input type="hidden" name="apply_estate_all" value="0"><input type="checkbox" name="apply_estate_all" value="1"<?=$db?'':' disabled'?>><span>Apply Estate to all Enabled Regions</span></label>
                    <div class="dg-field"><label>Smart Start mode</label><select class="dg-lockable" name="smart_mode"><option value="off"<?=dre_selected($smartMode,'off')?>>Off</option><option value="boot"<?=dre_selected($smartMode,'boot')?>>Smart Boot</option><option value="suspend"<?=dre_selected($smartMode,'suspend')?>>Smart Suspend</option></select></div>
                    <div class="dg-field"><label>Sim Size</label><select class="dg-lockable" name="sim_size"><?php for($s=1;$s<=16;$s++): ?><option value="<?=$s?>"<?=$s===$simSize?' selected':''?>><?=$s?> x <?=$s?></option><?php endfor; ?></select></div>
                </div>
                <?php if (!$db): ?><div class="dg-native-note">Estate controls are disabled because the OpenSim estate database connection is unavailable to this page.</div><?php endif; ?>
                <div class="dg-actions">
                    <button class="ag-button primary" id="saveButton" type="submit">SAVE</button>
                    <button class="ag-button" id="dreDeregisterButton" type="button" disabled title="Checking DreamGrid Native Bridge...">DEREGISTER</button>
                    <button class="ag-button" id="dreDeleteButton" type="button" disabled title="Checking DreamGrid Native Bridge...">DELETE</button>
                    <button class="ag-button" id="dreIniEditButton" type="button" title="Edit the real Region INI, matching DreamGrid Edit">EDIT</button>
                </div>
            </div>
        </section>

        <section class="region-panel" id="tab1">
            <div class="dg-group"><h3>Options</h3><div class="dg-grid three">
                <div class="dg-field"><label>X</label><input class="dg-lockable dg-digits" name="coord_x" type="text" inputmode="numeric" value="<?=dre_h($coordX)?>"></div>
                <div class="dg-field"><label>Y</label><input class="dg-lockable dg-digits" name="coord_y" type="text" inputmode="numeric" value="<?=dre_h($coordY)?>"></div>
                <div class="dg-field"><label>Port</label><input type="text" value="<?=dre_h($port)?>" readonly></div>
                <div class="dg-field"><label>Nonphysical Prim Size</label><input class="dg-lockable dg-digits" name="nonphysical_prim_max" type="text" inputmode="numeric" value="<?=dre_h($nonPhysicalPrimMax)?>"></div>
                <div class="dg-field"><label>Physical Prim Max Size</label><input class="dg-lockable dg-digits" name="physical_prim_max" type="text" inputmode="numeric" value="<?=dre_h($physicalPrimMax)?>"></div>
                <div class="dg-field"><label>Max Prims in Parcel</label><input class="dg-lockable dg-digits" name="max_prims" type="text" inputmode="numeric" value="<?=dre_h($maxPrims)?>"></div>
                <div class="dg-field"><label>Max Avatars + NPCs</label><input class="dg-lockable dg-digits" name="max_agents" type="text" inputmode="numeric" value="<?=dre_h($maxAgents)?>"></div>
                <label class="dg-check dg-lockable"><input type="hidden" name="clamp_prim_size" value="0"><input type="checkbox" name="clamp_prim_size" value="1"<?=dre_checked($clampPrimSize)?>><span>Clamp Prim Size</span></label>
                <div class="dg-field"><label>UUID</label><input class="dg-lockable" id="uuidField" name="uuid" type="text" value="<?=dre_h($uuid)?>"<?=$anyRunning?' readonly':''?>></div>
            </div><div class="dg-native-note">Region validation: X 0-65536, Y 32-65536. Port is read-only. UUID is editable only when OpenSim is stopped.</div></div>
        </section>

        <section class="region-panel" id="tab2">
            <div class="dg-group"><h3>Maps</h3><div class="dg-grid">
                <div class="dg-field"><label>Map renderer</label><select class="dg-lockable" name="map_type"><?php foreach(['Default'=>'Use Default','None'=>'None','Simple'=>'Simple but fast','Good'=>'Good (Warp3D)','Better'=>'Better (Prims, Slow)','Best'=>'Best (Prims + Mesh, Very Slow)'] as $v=>$label): ?><option value="<?=dre_h($v)?>"<?=dre_selected($mapType,$v)?>><?=dre_h($label)?></option><?php endforeach; ?></select></div>
                <div class="dg-field"><label>Default Landing Spot</label><input class="dg-lockable" name="default_landing" type="text" value="<?=dre_h($landing)?>"></div>
            </div><?php if($mapUrl!==''): ?><div class="dg-map-preview" style="margin-top:14px"><img src="<?=dre_h($mapUrl)?>" alt="Region map preview"></div><?php endif; ?></div>
        </section>

        <section class="region-panel" id="tab3">
            <div class="dg-group"><h3>Physics</h3><div class="dg-field"><label>Physics engine</label><select class="dg-lockable" name="physics"><option value=""<?=dre_selected($physics,'')?>>Use Default</option><option value="4"<?=dre_selected($physics,'4')?>>Ubit Open Dynamic Engine</option><option value="5"<?=dre_selected($physics,'5')?>>Ubit ODE/Bullet Hybrid</option><option value="2"<?=dre_selected($physics,'2')?>>Bullet physics</option><option value="3"<?=dre_selected($physics,'3')?>>Bullet physics in separate thread</option></select></div></div>
        </section>

        <section class="region-panel" id="tab4">
            <div class="dg-group"><h3>Scripts</h3><div class="dg-grid">
                <div class="dg-field"><label>Script engine</label><select class="dg-lockable" name="script_engine"><option value=""<?=dre_selected($scriptEngine,'')?>>Use Default</option><option value="Off"<?=dre_selected($scriptEngine,'Off')?>>Off</option><option value="YEngine"<?=dre_selected($scriptEngine,'YEngine')?>>On</option></select></div>
                <div class="dg-field"><label>Async LL Script Time</label><input class="dg-lockable" name="async_ll" type="text" value="<?=dre_h($asyncLL)?>"></div>
                <div class="dg-field"><label>Script Timer Rate</label><input class="dg-lockable" name="timer_rate" type="text" value="<?=dre_h($timerRate)?>"></div>
                <div class="dg-field"><label>Frame Rate</label><input class="dg-lockable" name="frame_rate" type="text" value="<?=dre_h($frameRate)?>"></div>
            </div></div>
        </section>

        <section class="region-panel" id="tab5">
            <div class="dg-group"><h3>Permissions</h3><div class="dg-field"><label>God permissions</label><select class="dg-lockable" name="permission_mode"><option value="default"<?=dre_selected($permissionMode,'default')?>>Use Default</option><option value="level"<?=dre_selected($permissionMode,'level')?>>Allow Level-based gods</option><option value="owner"<?=dre_selected($permissionMode,'owner')?>>Region Owner is God</option><option value="manager"<?=dre_selected($permissionMode,'manager')?>>Estate Manager is god</option></select></div></div>
        </section>

        <section class="region-panel" id="tab6">
            <div class="dg-group"><h3>Publicity</h3><div class="dg-grid">
                <div class="dg-field"><label>Publicity</label><select class="dg-lockable" name="publicity_mode"><option value="default"<?=dre_selected($publicityMode,'default')?>>Use Default</option><option value="off"<?=dre_selected($publicityMode,'off')?>>Do not publish this region</option><option value="search"<?=dre_selected($publicityMode,'search')?>>Publish Items marked for search</option></select></div>
                <div class="dg-field"><label>OpenSimWorld API Key</label><input class="dg-lockable" name="api_key" type="text" value="<?=dre_h($apiKey)?>"></div>
            </div><div class="dg-links"><a href="https://outworldz.com/search" target="_blank" rel="noopener">Outworldz Search</a><a href="https://opensimworld.com" target="_blank" rel="noopener">OpenSimWorld</a></div></div>
        </section>

        <section class="region-panel" id="tab7">
            <div class="dg-group"><h3>Modules</h3><div class="dg-grid">
                <label class="dg-check dg-lockable"><input type="hidden" name="birds" value="0"><input type="checkbox" name="birds" value="1"<?=dre_checked($modules['birds'])?>><span>Bird Module</span></label>
                <label class="dg-check dg-lockable"><input type="hidden" name="tides" value="0"><input type="checkbox" name="tides" value="1"<?=dre_checked($modules['tides'])?>><span>Tides</span></label>
                <label class="dg-check dg-lockable"><input type="hidden" name="teleport" value="0"><input type="checkbox" name="teleport" value="1"<?=dre_checked($modules['teleport'])?>><span>Teleport Sign Enable</span></label>
                <label class="dg-check dg-lockable"><input type="hidden" name="disable_gloebits" value="0"><input type="checkbox" name="disable_gloebits" value="1"<?=dre_checked($modules['gloebits'])?>><span>Disable All Gloebit</span></label>
                <label class="dg-check dg-lockable"><input type="hidden" name="disallow_foreigners" value="0"><input type="checkbox" name="disallow_foreigners" value="1"<?=dre_checked($modules['foreigners'])?>><span>Disable Foreign Visitors</span></label>
                <label class="dg-check dg-lockable"><input type="hidden" name="disallow_residents" value="0"><input type="checkbox" name="disallow_residents" value="1"<?=dre_checked($modules['residents'])?>><span>Disable All Residents</span></label>
                <label class="dg-check dg-lockable"><input type="hidden" name="skip_auto_backup" value="0"><input type="checkbox" name="skip_auto_backup" value="1"<?=dre_checked($modules['skipbackup'])?>><span>Skip Automatic OAR backup</span></label>
                <label class="dg-check dg-lockable"><input type="hidden" name="concierge" value="0"><input type="checkbox" name="concierge" value="1"<?=dre_checked($modules['concierge'])?>><span>Announce Visitors in region chat</span></label>
            </div><div class="dg-actions"><button class="ag-button dg-lockable" id="conciergeEditButton" type="button">EDIT</button></div><div class="dg-native-note">EDIT opens the per-region Concierge text: Regions\&lt;RegionName&gt;\&lt;RegionName&gt;.txt. Empty text deletes the file.</div></div>
        </section>

        <section class="region-panel" id="tab8">
            <div class="dg-group"><h3>CPU %</h3><div class="dg-core-grid"><?php for($core=1;$core<=24;$core++): $bit=1<<($core-1); ?><label class="dg-core dg-lockable"><input type="checkbox" name="cores[]" value="<?=$core?>"<?=dre_checked(($coreMask & $bit)!==0)?>><span>Core <?=$core?></span></label><?php endfor; ?></div></div>
        </section>

        <textarea name="concierge_text" id="conciergeTextHidden" hidden><?=dre_h($conciergeText)?></textarea>
    </form>

    <?php if (!$dreEmbedded): ?><div class="dg-actions">
        <a class="ag-button" href="<?=dre_h(ag_route('admin_regions'))?>">BACK TO REGIONS</a>
    </div><?php endif; ?>
</div>
</div>

<dialog id="conciergeDialog">
    <div class="dg-dialog-head">Concierge Editor â€” <?=dre_h($regionName)?></div>
    <div class="dg-dialog-body"><textarea id="conciergeEditor"><?=dre_h($conciergeText)?></textarea></div>
    <div class="dg-dialog-actions"><button class="ag-button" id="conciergeCancel" type="button">CANCEL</button><button class="ag-button primary" id="conciergeUse" type="button">USE THIS TEXT</button></div>
</dialog>

<dialog id="dreIniDialog">
    <form method="post" action="?region=<?=rawurlencode($regionName)?><?=$dreEmbedded ? '&amp;embedded=1' : ''?>">
        <input type="hidden" name="action" value="raw_save">
        <?php if ($dreEmbedded): ?><input type="hidden" name="embedded" value="1"><?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?=dre_h($csrfToken)?>">
        <input type="hidden" name="region_original" value="<?=dre_h($regionName)?>">
        <div class="dg-dialog-head">Region INI Editor — <?=dre_h($regionName)?></div>
        <div class="dg-dialog-body"><textarea name="raw_ini" id="dreIniEditor"><?=dre_h($originalRaw)?></textarea></div>
        <div class="dg-dialog-actions"><button class="ag-button" id="dreIniCancel" type="button">CANCEL</button><button class="ag-button primary" type="submit">SAVE INI</button></div>
    </form>
</dialog>

<script>
(function(){
    const tabs = Array.from(document.querySelectorAll('.region-tab'));
    const panels = Array.from(document.querySelectorAll('.region-panel'));
    const lockBox = document.getElementById('lockedBox');
    const card = document.getElementById('regionEditCard');
    const form = document.getElementById('regionEditForm');
    const originalName = <?=json_encode($regionName, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
    const originalGroup = <?=json_encode($groupName, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
    const anyRunning = <?=$anyRunning?'true':'false'?>;
    const currentRunning = <?=$currentRunning?'true':'false'?>;

    const dreRegionSelector = document.getElementById('dreRegionSelector');
    if (dreRegionSelector) {
        dreRegionSelector.addEventListener('change', function(){
            const selected = dreRegionSelector.value.trim();
            const embedded = new URLSearchParams(window.location.search).get('embedded') === '1';
            let target;
            if (selected === '') {
                target = '/Other/admin-create-region.php' + (embedded ? '?embedded=1' : '');
            } else {
                target = '/Other/admin-region-edit.php?region=' + encodeURIComponent(selected) + (embedded ? '&embedded=1' : '');
            }
            window.location.href = target;
        });
    }

    const dreIniDialog = document.getElementById('dreIniDialog');
    const dreIniEditButton = document.getElementById('dreIniEditButton');
    const dreIniCancel = document.getElementById('dreIniCancel');

    if (dreIniEditButton) {
        dreIniEditButton.addEventListener('click', function(){
            if (dreIniDialog && dreIniDialog.showModal) dreIniDialog.showModal();
        });
    }

    if (dreIniCancel) {
        dreIniCancel.addEventListener('click', function(){
            if (dreIniDialog) dreIniDialog.close();
        });
    }

    tabs.forEach(function(tab){
        tab.addEventListener('click', function(){
            tabs.forEach(function(t){ t.classList.remove('active'); });
            panels.forEach(function(p){ p.classList.remove('active'); });
            tab.classList.add('active');
            const panel = document.getElementById(tab.dataset.tab || '');
            if (panel) panel.classList.add('active');
        });
    });

    const draft = <?=json_encode($formDraft, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE)?>;
    if (draft) {
        Array.from(form.elements).forEach(function(control){
            if (!control.name || ['action', 'embedded', 'csrf_token', 'region_original'].includes(control.name)) return;
            if (control.type === 'hidden') return;
            const key = control.name === 'cores[]' ? 'cores' : control.name;
            if (control.type === 'checkbox') {
                const values = key === 'cores' ? (draft.cores || []) : [draft[key]];
                control.checked = values.includes(control.value);
            } else if (typeof draft[key] === 'string') {
                control.value = draft[key];
            }
        });
    }

    const lockableControls = Array.from(new Set(Array.from(document.querySelectorAll('.dg-lockable')).flatMap(function(el){
        return el.matches('input,select,textarea,button') ? [el] : Array.from(el.querySelectorAll('input,select,textarea,button'));
    })));
    const unavailableControls = new Set(lockableControls.filter(function(el){ return el.disabled; }));
    function applyLocked(){
        const locked = !!(lockBox && lockBox.checked);
        card.classList.toggle('dg-lock-overlay', locked);
        lockableControls.forEach(function(el){ el.disabled = locked || unavailableControls.has(el); });
    }

    if (lockBox) {
        lockBox.addEventListener('change', applyLocked);
        applyLocked();
    }

    document.querySelectorAll('.dg-digits').forEach(function(input){
        input.addEventListener('input', function(){ input.value = input.value.replace(/[^0-9]/g, ''); });
    });

    const dlg = document.getElementById('conciergeDialog');
    const edit = document.getElementById('conciergeEditButton');
    const editor = document.getElementById('conciergeEditor');
    const hidden = document.getElementById('conciergeTextHidden');
    const cancel = document.getElementById('conciergeCancel');
    const use = document.getElementById('conciergeUse');

    if (edit) edit.addEventListener('click', function(){
        if (editor && hidden) editor.value = hidden.value;
        if (dlg && dlg.showModal) dlg.showModal();
    });
    if (cancel) cancel.addEventListener('click', function(){ if (dlg) dlg.close(); });
    if (use) use.addEventListener('click', function(){ if (hidden && editor) hidden.value = editor.value; if (dlg) dlg.close(); });

    form.addEventListener('submit', function(event){
        const nameField = document.getElementById('regionNameField');
        const groupField = document.getElementById('groupNameField');
        const newName = nameField ? nameField.value.trim() : originalName;
        const newGroup = groupField ? groupField.value.trim() : originalGroup;

        if (currentRunning && (newName.toLowerCase() !== originalName.toLowerCase() || newGroup.toLowerCase() !== originalGroup.toLowerCase())) {
            event.preventDefault();
            alert('Stop this region before changing its Name or Group.');
            return;
        }

        if (!confirm('Save these Region settings? A backup will be made first.')) {
            event.preventDefault();
            return;
        }

        /* Disabled native-style controls still need their current values submitted. */
        lockableControls.forEach(function(el){ el.disabled = unavailableControls.has(el); });

        const uuidField = document.getElementById('uuidField');
        if (uuidField && anyRunning) uuidField.readOnly = true;
    });
})();
</script>
<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>
<script id="dreamgrid-native-region-actions-v11">
(function(){

    "use strict";

    const deregisterButton =
        document.getElementById(
            "dreDeregisterButton"
        );

    const deleteButton =
        document.getElementById(
            "dreDeleteButton"
        );

    if (
        !deregisterButton ||
        !deleteButton
    ) {
        return;
    }

    const regionName =
        <?=json_encode($regionName, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;

    const regionUuid =
        <?=json_encode($uuid, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;

    const csrfToken =
        <?=json_encode($csrfToken, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;


    function refreshParent(){

        /* dreamgrid-region-action-refresh-v2 */

        /*
         * Region Edit is now embedded inside the charcoal/gold
         * Global Map popup.
         *
         * Walk upward through same-origin parent frames:
         *
         *   Region Edit
         *       -> Global Map
         *       -> Region Manager
         *
         * Refresh the map where refreshMap() exists and notify
         * Region Manager to remove the deregistered/deleted row.
         */
        try {

            let frame =
                window;


            for (
                let depth = 0;
                depth < 8;
                depth++
            ) {

                if (
                    !frame.parent ||
                    frame.parent ===
                        frame
                ) {

                    break;
                }


                frame =
                    frame.parent;


                try {

                    if (
                        typeof frame.refreshMap ===
                        "function"
                    ) {

                        frame.refreshMap();
                    }


                    frame.postMessage(
                        {
                            type:
                                "dreamgrid-region-removed-v2",

                            regionName:
                                regionName,

                            regionUuid:
                                regionUuid
                        },
                        window.location.origin
                    );
                }
                catch(error){
                }
            }
        }
        catch(error){
        }


        /*
         * Retain compatibility if Region Edit is ever opened as
         * a real standalone browser window.
         */
        try {

            if (
                window.opener &&
                !window.opener.closed
            ) {

                if (
                    typeof window.opener.refreshMap ===
                    "function"
                ) {

                    window.opener.refreshMap();
                }
                else {

                    window.opener.postMessage(
                        {
                            type:
                                "dreamgrid-region-removed-v2",

                            regionName:
                                regionName,

                            regionUuid:
                                regionUuid
                        },
                        window.location.origin
                    );
                }
            }
        }
        catch(error){
        }
    }


    function disableBoth(){

        deregisterButton.disabled =
            true;

        deleteButton.disabled =
            true;
    }


    async function checkBridge(){

        disableBoth();

        try {

            const response =
                await fetch(
                    "/Other/admin-region-native-action.php?action=health&_=" +
                    Date.now(),
                    {
                        credentials:
                            "same-origin",

                        cache:
                            "no-store"
                    }
                );

            const data =
                await response.json();

            if (
                response.ok &&
                data &&
                data.ok &&
                data.ready
            ) {

                deregisterButton.disabled =
                    false;

                deleteButton.disabled =
                    false;

                deregisterButton.title =
                    "DreamGrid native Deregister";

                deleteButton.title =
                    "DreamGrid native permanent Delete";

                return;
            }

        }
        catch(error){
        }

        deregisterButton.title =
            "DreamGrid Native Bridge is not loaded yet.";

        deleteButton.title =
            "DreamGrid Native Bridge is not loaded yet.";
    }


    async function nativeAction(
        action
    ){

        const body =
            new URLSearchParams();

        body.set(
            "action",
            action
        );

        body.set(
            "region",
            regionName
        );

        body.set(
            "uuid",
            regionUuid
        );

        body.set(
            "csrf_token",
            csrfToken
        );

        const response =
            await fetch(
                "/Other/admin-region-native-action.php",
                {
                    method:
                        "POST",

                    credentials:
                        "same-origin",

                    cache:
                        "no-store",

                    headers:
                        {
                            "Content-Type":
                                "application/x-www-form-urlencoded;charset=UTF-8"
                        },

                    body:
                        body.toString()
                }
            );

        let data = null;

        try {
            data =
                await response.json();
        }
        catch(error){
            throw new Error(
                "DreamGrid native action returned invalid JSON."
            );
        }

        if (
            !response.ok ||
            !data ||
            !data.ok
        ) {
            throw new Error(
                data &&
                (
                    data.error ||
                    data.message
                )
                    ? (
                        data.error ||
                        data.message
                    )
                    : "DreamGrid native action failed."
            );
        }

        return data;
    }


    deregisterButton.addEventListener(
        "click",
        async function(){

            if (
                !confirm(
                    "DEREGISTER " +
                    regionName +
                    "?\n\n" +
                    "DreamGrid will stop THIS REGION ONLY and remove its Robust registration.\n\n" +
                    "It will also disappear from the web Global Map.\n\n" +
                    "The Region files will be kept."
                )
            ) {
                return;
            }

            disableBoth();

            try {

                const data =
                    await nativeAction(
                        "deregister"
                    );

                alert(
                    data.message
                );

                refreshParent();

                /*
                 * Keep this Edit window open.
                 * DELETE must still be available after Deregister.
                 */

                deregisterButton.disabled =
                    true;

                deregisterButton.title =
                    "Region has been deregistered.";

                deleteButton.disabled =
                    false;

            }
            catch(error){

                alert(
                    error &&
                    error.message
                        ? error.message
                        : String(error)
                );

                await checkBridge();
            }
        }
    );


    deleteButton.addEventListener(
        "click",
        async function(){

            if (
                !confirm(
                    "PERMANENTLY DELETE " +
                    regionName +
                    "?\n\n" +
                    "DreamGrid will run its native FileStuff.DeleteAllContents routine.\n\n" +
                    "THIS CANNOT BE UNDONE."
                )
            ) {
                return;
            }

            const typedName =
                prompt(
                    "Type the region name exactly to confirm permanent deletion:",
                    ""
                );

            if (
                typedName !==
                regionName
            ) {

                alert(
                    "Delete cancelled. Region name did not match."
                );

                return;
            }

            disableBoth();

            try {

                const data =
                    await nativeAction(
                        "delete"
                    );

                alert(
                    data.message
                );

                refreshParent();

                window.close();

            }
            catch(error){

                alert(
                    error &&
                    error.message
                        ? error.message
                        : String(error)
                );

                await checkBridge();
            }
        }
    );


    checkBridge();

})();
</script>
<script src="/Other/assets/js/dg-region-action-modal-v1.js?v=20260925-v2"></script>
</body>
</html>






