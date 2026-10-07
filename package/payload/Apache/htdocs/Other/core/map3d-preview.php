<?php
declare(strict_types=1);
require_once __DIR__ . '/map3d-cache.php';

function map3d_texture_preview(string $source, string $uuid, int $size): string {
    if ($size !== 256 || !is_file($source)) return $source;
    $helper = dirname(__DIR__) . '/private/map3d-tools/TexturePreview.exe';
    if (!is_file($helper)) throw new RuntimeException('Preview helper unavailable.');
    $path = map3d_cache_path('texture-preview', 'png-bicubic-v1:256:' . $uuid . ':' . filemtime($source) . ':' . filesize($source));
    if (map3d_cache_read($path) !== null) { header('X-Map3D-Preview: HIT'); return $path; }
    $lock = @fopen(dirname($path) . '/preview-' . (hexdec(substr(hash('sha256', $uuid), 0, 2)) % 16) . '.lock', 'c');
    if (!$lock) throw new RuntimeException('Preview cache unavailable.');
    $lockDeadline=microtime(true)+5;
    while(!flock($lock,LOCK_EX|LOCK_NB)){
        if(microtime(true)>=$lockDeadline){fclose($lock);throw new RuntimeException('Preview busy.');}
        usleep(20000);
    }
    $temp = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
    try {
        if (map3d_cache_read($path) !== null) return $path;
        $pipes = [];
        $process = @proc_open([$helper, $source, $temp, '256'], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, null, null, ['bypass_shell'=>true]);
        if (!is_resource($process)) throw new RuntimeException('Preview could not start.');
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);
        $deadline = microtime(true) + 5;
        do {
            stream_get_contents($pipes[1]); stream_get_contents($pipes[2]);
            $status = proc_get_status($process);
            if (!$status['running']) break;
            if (microtime(true) >= $deadline) { proc_terminate($process); break; }
            usleep(10000);
        } while (true);
        fclose($pipes[1]); fclose($pipes[2]); proc_close($process);
        if (($status['exitcode'] ?? -1) !== 0 || !is_file($temp)) throw new RuntimeException('Preview failed.');
        $body = file_get_contents($temp);
        if (!is_string($body) || !str_starts_with($body, "\x89PNG\r\n\x1a\n")) throw new RuntimeException('Invalid preview.');
        map3d_cache_write($path, $body);
        header('X-Map3D-Preview: MISS');
        if(!is_file($path))throw new RuntimeException('Preview cache publish failed.');
        return $path;
    } finally { @unlink($temp); flock($lock, LOCK_UN); fclose($lock); }
}
