<?php
declare(strict_types=1);

/* Immutable map representations only. Never cache authentication or region permissions. */
function map3d_cache_path(string $kind, string $identity): string {
    if (!in_array($kind, ['mesh', 'texture-preview', 'material'], true)) throw new InvalidArgumentException('Invalid cache kind.');
    $key = hash('sha256', $identity);
    $directory = dirname(__DIR__) . '/private/map3d-cache/v1/' . $kind . '/' . $key[0];
    if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) throw new RuntimeException('Map cache unavailable.');
    return $directory . '/' . $key . ($kind === 'texture-preview' ? '.png' : '.json');
}

function map3d_cache_read(string $path, string $uuid = ''): ?string {
    if (!is_file($path) || filesize($path) > 16777216) return null;
    $body = @file_get_contents($path);
    if (!is_string($body) || $body === '') return null;
    if (pathinfo($path, PATHINFO_EXTENSION) === 'json') {
        $decoded = json_decode($body, true);
        if (basename(dirname($path,2)) === 'material') {
            return is_array($decoded) && ($decoded['ok'] ?? false) === true && is_string($decoded['MaterialUuid'] ?? null) &&
                (!isset($decoded['Expires']) || $decoded['Expires'] > time()) ? $body : null;
        }
        if (!is_array($decoded) || ($decoded['ok'] ?? false) !== true ||
            !is_array($decoded['Positions'] ?? null) || !is_array($decoded['Indices'] ?? null) ||
            ($uuid !== '' && strcasecmp((string)($decoded['MeshUuid'] ?? ''), $uuid) !== 0)) {
            return null;
        }
    } elseif (!map3d_png_valid($body)) return null;
    return $body;
}

function map3d_png_valid(string $body): bool {
    if (!str_starts_with($body, "\x89PNG\r\n\x1a\n")) return false;
    $offset = 8; $length = strlen($body);
    while ($offset + 12 <= $length) {
        $size = unpack('N', substr($body, $offset, 4))[1];
        if ($size > $length - $offset - 12) return false;
        $chunk = substr($body, $offset + 4, $size + 4);
        if (hash('crc32b', $chunk, true) !== substr($body, $offset + 8 + $size, 4)) return false;
        $offset += $size + 12;
        if (substr($chunk, 0, 4) === 'IEND') return $offset === $length;
    }
    return false;
}

function map3d_cache_write(string $path, string $body): void {
    if (strlen($body) > 16777216) return;
    $directory = dirname($path);
    $lock = @fopen($directory . '/publish.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { if ($lock) fclose($lock); return; }
    try {
        // Sixteen shards, each capped at 64 MiB and 128 entries: <= 1 GiB total.
        $files = glob($directory . '/*.' . pathinfo($path, PATHINFO_EXTENSION)) ?: [];
        usort($files, static fn($a, $b) => filemtime($a) <=> filemtime($b));
        $size = 0;
        foreach ($files as $file) $size += (int)filesize($file);
        $limit = basename(dirname($directory)) === 'material' ? 4194304 : 67108864;
        while ($files && ($size + strlen($body) > $limit || count($files) >= 128)) {
            $old = array_shift($files);
            $size -= (int)filesize($old);
            @unlink($old);
        }
        $temp = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($temp, $body, LOCK_EX) !== strlen($body)) { @unlink($temp); return; }
        if (!@rename($temp, $path)) @unlink($temp);
    } finally { flock($lock, LOCK_UN); fclose($lock); }
}
