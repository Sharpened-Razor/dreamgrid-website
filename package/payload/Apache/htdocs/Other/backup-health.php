<?php
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/bootstrap.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$s = ag_current_session();
if (!$s) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Not logged in']);
    exit;
}
if (!ag_is_admin($s)) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'Grid Owner access required']);
    exit;
}

function readJsonNoBom($path) {
    if (!is_file($path)) return null;
    $raw = (string)@file_get_contents($path);
    if (substr($raw,0,3) === "\xEF\xBB\xBF") $raw = substr($raw,3);
    $d = json_decode($raw,true);
    return is_array($d) ? $d : null;
}

$jobs = __DIR__ . DIRECTORY_SEPARATOR . 'jobs';
$schedule = readJsonNoBom($jobs . DIRECTORY_SEPARATOR . 'backup_schedule.json');
$marker   = readJsonNoBom($jobs . DIRECTORY_SEPARATOR . 'backup_scheduler_installed.json');
$state    = readJsonNoBom($jobs . DIRECTORY_SEPARATOR . 'backup_scheduler_state.json');
$history  = readJsonNoBom($jobs . DIRECTORY_SEPARATOR . 'backup_history.json');
if (!is_array($history)) $history = [];

$checks = [];
$severity = 0; // 0 healthy, 1 warning, 2 error

function addCheck(&$checks,&$severity,$level,$title,$detail) {
    $checks[] = ['level'=>$level,'title'=>$title,'detail'=>$detail];
    if ($level === 'ERROR') $severity = max($severity,2);
    elseif ($level === 'WARNING') $severity = max($severity,1);
}

// Scheduler installation / enablement.
if (!is_array($marker) || empty($marker['installed'])) {
    addCheck($checks,$severity,'ERROR','Background scheduler','Not installed.');
} else {
    addCheck($checks,$severity,'HEALTHY','Background scheduler','Installed and available.');
}

if (!is_array($schedule)) {
    addCheck($checks,$severity,'WARNING','Backup schedule','No saved schedule configuration found.');
} elseif (empty($schedule['enabled'])) {
    addCheck($checks,$severity,'WARNING','Backup schedule','Automatic backups are disabled.');
} else {
    $type = strtoupper((string)($schedule['type'] ?? 'daily'));
    $time = (string)($schedule['time'] ?? '');
    $count = is_array($schedule['regions'] ?? null) ? count($schedule['regions']) : 0;
    addCheck($checks,$severity,'HEALTHY','Backup schedule',"$type at $time — $count region(s) selected.");
}

// Durable last scheduled backup result.
$lastRun = $state['lastBackupRun'] ?? $state['lastRun'] ?? null;
$lastFinished = $state['lastBackupFinished'] ?? null;
$completed = (int)($state['lastBackupCompleted'] ?? $state['completed'] ?? 0);
$skipped = (int)($state['lastBackupSkipped'] ?? $state['skipped'] ?? 0);
$errors = (int)($state['lastBackupErrors'] ?? $state['errors'] ?? 0);

if (!$lastRun) {
    addCheck($checks,$severity,'WARNING','Last scheduled backup','No completed scheduled backup has been recorded yet.');
} elseif ($errors > 0) {
    addCheck($checks,$severity,'ERROR','Last scheduled backup',"$completed completed, $skipped skipped, $errors error(s).");
} elseif ($skipped > 0) {
    addCheck($checks,$severity,'WARNING','Last scheduled backup',"$completed completed, $skipped skipped, 0 errors.");
} else {
    addCheck($checks,$severity,'HEALTHY','Last scheduled backup',"$completed completed, 0 skipped, 0 errors.");
}

// Recent failures/skips in history (last 7 days).
$cutoff = time() - (7 * 86400);
$recentFailed = 0;
$recentSkipped = 0;
foreach ($history as $h) {
    if (!is_array($h)) continue;
    $t = strtotime((string)($h['time'] ?? ''));
    if (!$t || $t < $cutoff) continue;
    $st = strtoupper((string)($h['status'] ?? ''));
    if ($st === 'FAILED') $recentFailed++;
    elseif ($st === 'SKIPPED') $recentSkipped++;
}
if ($recentFailed > 0) {
    addCheck($checks,$severity,'ERROR','Recent backup activity',"$recentFailed failed and $recentSkipped skipped backup(s) in the last 7 days.");
} elseif ($recentSkipped > 0) {
    addCheck($checks,$severity,'WARNING','Recent backup activity',"0 failed and $recentSkipped skipped backup(s) in the last 7 days.");
} else {
    addCheck($checks,$severity,'HEALTHY','Recent backup activity','No failed or skipped backups in the last 7 days.');
}

// Backup storage: use the same Autobackup root as the existing panel.
$root = ag_dg_autobackup_root();
if (is_dir($root)) {
    $free = @disk_free_space($root);
    $total = @disk_total_space($root);
    if ($free !== false && $total !== false && $total > 0) {
        $pct = ($free / $total) * 100.0;
        if ($pct < 8) {
            addCheck($checks,$severity,'ERROR','Backup drive free space',sprintf('%.1f%% free.', $pct));
        } elseif ($pct < 15) {
            addCheck($checks,$severity,'WARNING','Backup drive free space',sprintf('%.1f%% free.', $pct));
        } else {
            addCheck($checks,$severity,'HEALTHY','Backup drive free space',sprintf('%.1f%% free.', $pct));
        }
        $storage = ['free'=>(int)$free,'total'=>(int)$total,'percentFree'=>round($pct,1)];
    } else {
        $storage = null;
        addCheck($checks,$severity,'WARNING','Backup drive free space','Unable to read disk free space.');
    }
} else {
    $storage = null;
    addCheck($checks,$severity,'WARNING','Backup storage','Autobackup folder was not found.');
}

$backupFiles = [
    'oar'=>0,
    'iar'=>0,
    'total'=>0,
    'newest'=>null,
    'oldest'=>null
];

if (is_dir($root)) {

    foreach (glob(
        $root . DIRECTORY_SEPARATOR . 'AutoBackup-*',
        GLOB_ONLYDIR
    ) ?: [] as $dayDir) {

        foreach ([
            'OAR'=>'oar',
            'IAR'=>'iar'
        ] as $folder=>$ext) {

            $dir = $dayDir . DIRECTORY_SEPARATOR . $folder;
            if (!is_dir($dir)) continue;

            foreach (glob(
                $dir . DIRECTORY_SEPARATOR . '*.' . $ext
            ) ?: [] as $path) {

                if (!is_file($path)) continue;

                $backupFiles[$ext]++;
                $backupFiles['total']++;

                $mtime = (int)@filemtime($path);
                if ($mtime <= 0) continue;

                $entry = [
                    'mtime'=>$mtime,
                    'time'=>date(DATE_ATOM,$mtime),
                    'name'=>basename($path),
                    'type'=>strtoupper($ext)
                ];

                if (
                    $backupFiles['newest'] === null ||
                    $mtime > $backupFiles['newest']['mtime']
                ) {
                    $backupFiles['newest'] = $entry;
                }

                if (
                    $backupFiles['oldest'] === null ||
                    $mtime < $backupFiles['oldest']['mtime']
                ) {
                    $backupFiles['oldest'] = $entry;
                }
            }
        }
    }
}

foreach (['newest','oldest'] as $which) {
    if (is_array($backupFiles[$which])) {
        unset($backupFiles[$which]['mtime']);
    }
}

if (
    is_array($storage) &&
    isset($storage['free'],$storage['total'])
) {
    $storage['used'] =
        max(0,(int)$storage['total']-(int)$storage['free']);

    $storage['percentUsed'] =
        $storage['total'] > 0
        ? round(($storage['used']/$storage['total'])*100,1)
        : 0;
}
$overall = $severity >= 2 ? 'ERROR' : ($severity === 1 ? 'WARNING' : 'HEALTHY');

echo json_encode([
    'ok'=>true,
    'overall'=>$overall,
    'checks'=>$checks,
    'lastRun'=>$lastRun,
    'lastFinished'=>$lastFinished,
    'completed'=>$completed,
    'skipped'=>$skipped,
    'errors'=>$errors,
    'lastCheck'=>$state['lastCheck'] ?? null,
    'lastCheckResult'=>$state['lastCheckResult'] ?? null,
    'storage'=>$storage,
    'backupFiles'=>$backupFiles,
    'timezone'=>date_default_timezone_get()
]);
