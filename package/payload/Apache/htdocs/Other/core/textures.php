<?php
declare(strict_types=1);

/*
 * Grid - CUSTOM TEXTURE HELPER
 *
 * Portable Apache/PHP layer.
 * No DreamGrid dependency.
 */

function ag_texture_root(): string
{
    return dirname(__DIR__) . '/custom-textures';
}

function ag_texture_manifest_path(): string
{
    return ag_texture_root() . '/texture-slots.json';
}

function ag_texture_manifest(): array
{
    static $manifest = null;

    if (is_array($manifest)) {
        return $manifest;
    }

    $path = ag_texture_manifest_path();
    $raw = @file_get_contents($path);

    if ($raw === false) {
        return $manifest = [
            'version' => 1,
            'groups' => [],
        ];
    }

    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
        return $manifest = [
            'version' => 1,
            'groups' => [],
        ];
    }

    return $manifest = $decoded;
}

function ag_texture_slot(string $key): ?array
{
    foreach (ag_texture_manifest()['groups'] ?? [] as $group) {
        foreach ($group['slots'] ?? [] as $slot) {
            if (($slot['key'] ?? '') === $key) {
                $slot['group_id'] = (string)($group['id'] ?? '');
                $slot['group_label'] = (string)($group['label'] ?? '');
                $slot['folder'] = (string)($group['folder'] ?? '');
                return $slot;
            }
        }
    }

    return null;
}

function ag_texture_allowed_extensions(): array
{
    return ['jpg', 'jpeg', 'png', 'webp'];
}

function ag_texture_find_path(string $key): ?string
{
    $slot = ag_texture_slot($key);

    if (!$slot) {
        return null;
    }

    $folder = preg_replace('/[^A-Za-z0-9_-]+/', '', (string)$slot['folder']);
    $base = preg_replace('/[^A-Za-z0-9_-]+/', '', $key);

    if ($folder === '' || $base === '') {
        return null;
    }

    $root = ag_texture_root() . '/' . $folder;

    foreach (ag_texture_allowed_extensions() as $ext) {
        $path = $root . '/' . $base . '.' . $ext;

        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

function ag_texture_web_path_from_file(string $path): string
{
    $htdocs = str_replace(
        '\\',
        '/',
        realpath(dirname(dirname(__DIR__))) ?: ''
    );

    $real = str_replace(
        '\\',
        '/',
        realpath($path) ?: $path
    );

    $realLower =
        strtolower($real);

    $htdocsLower =
        strtolower($htdocs);

    if (
        $htdocs !== '' &&
        substr(
            $realLower,
            0,
            strlen($htdocsLower)
        ) === $htdocsLower
    ) {
        $relative = substr($real, strlen($htdocs));

        if ($relative === '' || $relative[0] !== '/') {
            $relative = '/' . $relative;
        }

        return $relative;
    }

    /*
     * Known Australia API fallback.
     */
    $needle = '/Other/custom-textures/';
    $normal = str_replace('\\', '/', $path);
    $pos = stripos($normal, $needle);

    if ($pos !== false) {
        return substr($normal, $pos);
    }

    return '';
}

function ag_texture_url(
    string $key,
    string $fallback = ''
): string {
    $path = ag_texture_find_path($key);

    if (!$path) {
        return $fallback;
    }

    $url = ag_texture_web_path_from_file($path);

    if ($url === '') {
        return $fallback;
    }

    $mtime = @filemtime($path);

    if ($mtime !== false) {
        $url .= '?v=' . (string)$mtime;
    }

    return $url;
}

function ag_texture_info(string $key): array
{
    $slot = ag_texture_slot($key);
    $path = ag_texture_find_path($key);

    $result = [
        'slot' => $slot,
        'exists' => false,
        'path' => null,
        'url' => '',
        'filename' => '',
        'bytes' => 0,
        'width' => null,
        'height' => null,
        'mime' => '',
        'modified' => null,
    ];

    if (!$path) {
        return $result;
    }

    $result['exists'] = true;
    $result['path'] = $path;
    $result['url'] = ag_texture_url($key);
    $result['filename'] = basename($path);
    $result['bytes'] = (int)(@filesize($path) ?: 0);
    $result['modified'] = @filemtime($path) ?: null;

    $image = @getimagesize($path);

    if (is_array($image)) {
        $result['width'] = (int)($image[0] ?? 0);
        $result['height'] = (int)($image[1] ?? 0);
        $result['mime'] = (string)($image['mime'] ?? '');
    }

    return $result;
}

