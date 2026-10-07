<?php
declare(strict_types=1);
require_once __DIR__ . '/map3d-cache.php';
require_once __DIR__ . '/dreamgrid-env.php';

function map3d_sculpt_cache(string $uuid, int $type): string {
    $bin = ag_dg_opensim_bin();
    $library = $bin ? $bin . '/PrimMesher.dll' : '';
    $version = is_file($library) ? filemtime($library) . ':' . filesize($library) : 'missing';
    return map3d_cache_path('mesh', ($type === 0 ? 'prim-v1:high:' : 'sculpt-v1:32:') . 'x-z-minusy:' . $version . ':' . $type . ':' . $uuid);
}

function map3d_sculpt_send(string $body, string $cache): never {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Map3D-Cache: ' . $cache);
    echo $body;
    exit;
}

function map3d_sculpt_generate(string $source, string $uuid, int $type): never {
    $path = map3d_sculpt_cache($uuid, $type);
    if (($body = map3d_cache_read($path, $uuid)) !== null) map3d_sculpt_send($body, 'HIT');
    $helper = dirname(__DIR__) . '/private/map3d-tools/sculpt/SculptGeometry.exe';
    $bin = ag_dg_opensim_bin();
    if (!$bin || !is_file($helper) || !is_file($bin . '/PrimMesher.dll')) agt_fail('Sculpt converter unavailable.', 503);
    $lock = @fopen(dirname($path) . '/sculpt.lock', 'c');
    if (!$lock) agt_fail('Geometry cache unavailable.', 503);
    $lockDeadline = microtime(true) + 10;
    while (!flock($lock, LOCK_EX | LOCK_NB)) {
        if (microtime(true) >= $lockDeadline) { fclose($lock); agt_fail('Geometry conversion busy; retry later.', 503); }
        usleep(20000);
    }
    if (($body = map3d_cache_read($path, $uuid)) !== null) {
        flock($lock, LOCK_UN); fclose($lock); map3d_sculpt_send($body, 'HIT');
    }
    $temp = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
    $started = microtime(true);
    try {
        $pipes = [];
        $process = @proc_open([$helper, $bin, $source, $temp, $uuid, (string)$type],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, null, null, ['bypass_shell'=>true]);
        if (!is_resource($process)) throw new RuntimeException('Sculpt converter could not start.');
        fclose($pipes[0]); stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);
        do {
            stream_get_contents($pipes[1]); stream_get_contents($pipes[2]);
            $status = proc_get_status($process);
            if (!$status['running']) break;
            if (microtime(true) - $started > 5) { proc_terminate($process); break; }
            usleep(10000);
        } while (true);
        fclose($pipes[1]); fclose($pipes[2]); proc_close($process);
        if (($status['exitcode'] ?? -1) !== 0 || !is_file($temp)) throw new RuntimeException('Sculpt conversion failed.');
        $body = file_get_contents($temp);
        $data = json_decode($body, true);
        if (!is_array($data) || ($data['ok'] ?? false) !== true || ($data['MeshUuid'] ?? '') !== $uuid ||
            count($data['Positions'] ?? []) < 9 || count($data['Indices'] ?? []) < 3) throw new RuntimeException('Invalid sculpt geometry.');
        map3d_cache_write($path, $body);
    } catch (Throwable $error) {
        $body = null;
    } finally { @unlink($temp); flock($lock, LOCK_UN); fclose($lock); }
    if ($body === null) agt_fail('Sculpt conversion failed.', 502);
    header('Server-Timing: sculpt;dur=' . round((microtime(true)-$started)*1000,3));
    map3d_sculpt_send($body, 'MISS');
}
