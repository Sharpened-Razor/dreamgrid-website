<?php
// V51 shared restore safety helpers. No output is emitted from this file.

function dgRestoreJobsDir() {
    $dir = __DIR__ . DIRECTORY_SEPARATOR . 'jobs';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    return $dir;
}

function dgRestoreLockPath() {
    return dgRestoreJobsDir() . DIRECTORY_SEPARATOR . 'restore-active.lock';
}

function dgRestoreLockRead() {
    $p = dgRestoreLockPath();
    if (!is_file($p)) return null;
    $d = json_decode((string)@file_get_contents($p), true);
    return is_array($d) ? $d : ['unknown'=>true];
}

function dgRestoreLockIsStale($lock) {
    $p = dgRestoreLockPath();
    if (!is_file($p)) return true;

    // If the recorded job already has a completed/error log, the lock is stale.
    if (is_array($lock)) {
        $job = (string)($lock['jobId'] ?? '');
        $type = strtolower((string)($lock['type'] ?? ''));
        if (preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/', $job) && in_array($type, ['oar','iar'], true)) {
            $prefix = $type === 'oar' ? 'oar_restore_' : 'iar_restore_';
            $log = dgRestoreJobsDir() . DIRECTORY_SEPARATOR . $prefix . $job . '.log';
            if (is_file($log)) {
                $txt = (string)@file_get_contents($log);
                if (preg_match('/(?:^|\R)END\s+/m', $txt) || preg_match('/(?:^|\R)ERROR\s+/mi', $txt)) {
                    return true;
                }
            }
        }
    }

    // Last-resort stale protection. Very large restores can take hours, so use 24h.
    $mtime = @filemtime($p) ?: time();
    return (time() - $mtime) > 86400;
}

function dgRestoreAcquireLock($jobId, $type, $target, $source, $requestedBy) {
    $path = dgRestoreLockPath();

    if (is_file($path)) {
        $existing = dgRestoreLockRead();
        if (dgRestoreLockIsStale($existing)) {
            @unlink($path);
        }
    }

    $fh = @fopen($path, 'x');
    if (!$fh) {
        $existing = dgRestoreLockRead();
        $desc = '';
        if (is_array($existing)) {
            $desc = trim((string)($existing['type'] ?? '') . ' restore to ' . (string)($existing['target'] ?? ''));
        }
        return [false, $desc !== '' ? $desc : 'another restore'];
    }

    $record = [
        'jobId'=>$jobId,
        'type'=>strtolower($type),
        'target'=>$target,
        'source'=>$source,
        'requestedBy'=>$requestedBy,
        'started'=>date('c')
    ];
    fwrite($fh, json_encode($record, JSON_UNESCAPED_SLASHES));
    fclose($fh);
    return [true, ''];
}

function dgRestoreReleaseLock($jobId) {
    $path = dgRestoreLockPath();
    if (!is_file($path)) return;
    $d = dgRestoreLockRead();
    if (is_array($d) && (string)($d['jobId'] ?? '') === (string)$jobId) {
        @unlink($path);
    }
}

function dgRestoreActiveInfo() {
    $lock = dgRestoreLockRead();
    if ($lock === null) return null;
    if (dgRestoreLockIsStale($lock)) {
        @unlink(dgRestoreLockPath());
        return null;
    }
    return $lock;
}
