<?php

require_once __DIR__ . '/dreamgrid-env.php';

/*
 ============================================================
 AUSTRALIA CONTROL CENTER
 DYNAMIC REGION MAP TEXTURES
 ============================================================

 Custom region textures are keyed by RegionUUID.

 Adding, removing, renaming or moving regions does not require
 changing texture slot definitions.

 PHP 7.4 compatible.
 ============================================================
*/

function ag_rmt_regions_root(): string
{
    $root =
        ag_dg_regions_root();

    if (
        !is_string($root) ||
        trim($root) === ''
    ) {
        throw new RuntimeException(
            'DreamGrid Regions directory could not be resolved.'
        );
    }

    return $root;
}


function ag_rmt_custom_root(): string
{
    return
        dirname(__DIR__) .
        '/custom-textures/map-regions';
}


function ag_rmt_backup_root(): string
{
    $root =
        ag_dg_path(
            '_TEXTURE_BACKUPS' .
            DIRECTORY_SEPARATOR .
            'region-map-textures'
        );

    if (
        !is_string($root) ||
        trim($root) === ''
    ) {
        throw new RuntimeException(
            'Region-map texture backup directory could not be resolved.'
        );
    }

    return $root;
}


function ag_rmt_valid_uuid(string $value): bool
{
    return
        preg_match(
            '/^[0-9a-fA-F]{8}-' .
            '[0-9a-fA-F]{4}-' .
            '[0-9a-fA-F]{4}-' .
            '[0-9a-fA-F]{4}-' .
            '[0-9a-fA-F]{12}$/',
            trim($value)
        ) === 1;
}


function ag_rmt_ini_scalar(
    array $section,
    array $keys
): ?string {

    foreach ($keys as $key) {

        if (!array_key_exists($key, $section)) {
            continue;
        }

        $value =
            $section[$key];

        if (
            is_scalar($value) ||
            $value === null
        ) {

            $text =
                trim(
                    (string)$value
                );

            return
                $text !== ''
                    ? $text
                    : null;
        }
    }

    return null;
}


function ag_rmt_region_ini_files(): array
{
    $root =
        ag_rmt_regions_root();

    if (!is_dir($root)) {
        return [];
    }

    $files = [];

    try {

        $iterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $root,
                    FilesystemIterator::SKIP_DOTS
                )
            );

        foreach ($iterator as $entry) {

            if (!$entry->isFile()) {
                continue;
            }

            $path =
                $entry->getPathname();

            if (
                strtolower(
                    pathinfo(
                        $path,
                        PATHINFO_EXTENSION
                    )
                ) !== 'ini'
            ) {
                continue;
            }

            if (
                preg_match(
                    '/(?:^|[.])bak(?:[.]|$)/i',
                    basename($path)
                )
            ) {
                continue;
            }

            $files[] =
                $path;
        }
    }
    catch (Throwable $e) {

        return [];
    }

    sort(
        $files,
        SORT_NATURAL |
        SORT_FLAG_CASE
    );

    return $files;
}


function ag_rmt_regions(): array
{
    $indexed = [];

    foreach (
        ag_rmt_region_ini_files()
        as $path
    ) {

        $parsed =
            @parse_ini_file(
                $path,
                true,
                INI_SCANNER_RAW
            );

        if (
            !is_array($parsed) ||
            !$parsed
        ) {
            continue;
        }

        foreach (
            $parsed
            as $sectionName => $section
        ) {

            if (!is_array($section)) {
                continue;
            }

            $uuid =
                ag_rmt_ini_scalar(
                    $section,
                    ['RegionUUID']
                );

            if (
                $uuid === null ||
                !ag_rmt_valid_uuid($uuid)
            ) {
                continue;
            }

            $location =
                ag_rmt_ini_scalar(
                    $section,
                    ['Location']
                );

            if ($location === null) {
                continue;
            }

            $parts =
                array_map(
                    'trim',
                    explode(
                        ',',
                        $location
                    )
                );

            if (count($parts) !== 2) {
                continue;
            }

            if (
                !preg_match(
                    '/^-?\d+$/',
                    $parts[0]
                ) ||
                !preg_match(
                    '/^-?\d+$/',
                    $parts[1]
                )
            ) {
                continue;
            }

            $x =
                (int)$parts[0];

            $y =
                (int)$parts[1];

            $sizeX =
                (int)(
                    ag_rmt_ini_scalar(
                        $section,
                        [
                            'SizeX',
                            'RegionSizeX'
                        ]
                    ) ?? '256'
                );

            $sizeY =
                (int)(
                    ag_rmt_ini_scalar(
                        $section,
                        [
                            'SizeY',
                            'RegionSizeY'
                        ]
                    ) ?? '256'
                );

            if ($sizeX <= 0) {
                $sizeX = 256;
            }

            if ($sizeY <= 0) {
                $sizeY = 256;
            }

            $uuid =
                strtolower(
                    trim($uuid)
                );

            $indexed[$uuid] = [
                'uuid' =>
                    $uuid,

                'name' =>
                    trim(
                        (string)$sectionName
                    ),

                'x' =>
                    $x,

                'y' =>
                    $y,

                'size_x' =>
                    $sizeX,

                'size_y' =>
                    $sizeY,

                'cells_x' =>
                    max(
                        1,
                        (int)round(
                            $sizeX /
                            256
                        )
                    ),

                'cells_y' =>
                    max(
                        1,
                        (int)round(
                            $sizeY /
                            256
                        )
                    ),

                'ini_path' =>
                    $path
            ];

            break;
        }
    }

    $regions =
        array_values(
            $indexed
        );

    usort(
        $regions,
        function (
            array $a,
            array $b
        ): int {

            return
                strnatcasecmp(
                    (string)$a['name'],
                    (string)$b['name']
                );
        }
    );

    return $regions;
}


function ag_rmt_allowed_extensions(): array
{
    return [
        'jpg',
        'jpeg',
        'png',
        'webp'
    ];
}


function ag_rmt_custom_files(
    string $uuid
): array {

    if (!ag_rmt_valid_uuid($uuid)) {
        return [];
    }

    $root =
        ag_rmt_custom_root();

    $files = [];

    foreach (
        ag_rmt_allowed_extensions()
        as $extension
    ) {

        $path =
            $root .
            '/' .
            strtolower($uuid) .
            '.' .
            $extension;

        if (is_file($path)) {
            $files[] = $path;
        }
    }

    return $files;
}


function ag_rmt_custom_file(
    string $uuid
): ?string {

    $files =
        ag_rmt_custom_files(
            $uuid
        );

    return
        $files
            ? $files[0]
            : null;
}


function ag_rmt_custom_url(
    string $uuid
): string {

    $path =
        ag_rmt_custom_file(
            $uuid
        );

    if ($path === null) {
        return '';
    }

    $url =
        '/Other/custom-textures/map-regions/' .
        rawurlencode(
            basename($path)
        );

    $mtime =
        @filemtime($path);

    if ($mtime !== false) {
        $url .=
            '?v=' .
            (string)$mtime;
    }

    return $url;
}


function ag_rmt_custom_info(
    string $uuid
): array {

    $path =
        ag_rmt_custom_file(
            $uuid
        );

    $result = [
        'exists' =>
            false,

        'path' =>
            null,

        'url' =>
            '',

        'filename' =>
            '',

        'bytes' =>
            0,

        'width' =>
            null,

        'height' =>
            null,

        'mime' =>
            '',

        'modified' =>
            null
    ];

    if ($path === null) {
        return $result;
    }

    $result['exists'] =
        true;

    $result['path'] =
        $path;

    $result['url'] =
        ag_rmt_custom_url(
            $uuid
        );

    $result['filename'] =
        basename($path);

    $result['bytes'] =
        (int)(
            @filesize($path) ?:
            0
        );

    $mtime =
        @filemtime($path);

    $result['modified'] =
        $mtime !== false
            ? $mtime
            : null;

    $image =
        @getimagesize($path);

    if (is_array($image)) {

        $result['width'] =
            (int)(
                $image[0] ??
                0
            );

        $result['height'] =
            (int)(
                $image[1] ??
                0
            );

        $result['mime'] =
            (string)(
                $image['mime'] ??
                ''
            );
    }

    return $result;
}


function ag_rmt_format_bytes(
    int $bytes
): string {

    if ($bytes < 1024) {
        return $bytes . ' B';
    }

    if ($bytes < 1048576) {
        return
            number_format(
                $bytes /
                1024,
                1
            ) .
            ' KB';
    }

    return
        number_format(
            $bytes /
            1048576,
            2
        ) .
        ' MB';
}


function ag_rmt_backup_existing(
    string $uuid,
    string $path,
    string $reason
): ?string {

    if (
        !ag_rmt_valid_uuid($uuid) ||
        !is_file($path)
    ) {
        return null;
    }

    $root =
        ag_rmt_backup_root();

    if (
        !is_dir($root) &&
        !@mkdir(
            $root,
            0770,
            true
        )
    ) {
        throw new RuntimeException(
            'Could not create region texture backup root.'
        );
    }

    $stamp =
        date(
            'Y-m-d_H-i-s'
        );

    $folder =
        $root .
        '/' .
        $stamp .
        '-' .
        strtolower($uuid) .
        '-' .
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            $reason
        );

    if (is_dir($folder)) {

        $folder .=
            '-' .
            bin2hex(
                random_bytes(2)
            );
    }

    if (
        !@mkdir(
            $folder,
            0770,
            true
        )
    ) {
        throw new RuntimeException(
            'Could not create region texture backup folder.'
        );
    }

    $destination =
        $folder .
        '/' .
        basename($path);

    if (!@copy($path, $destination)) {

        throw new RuntimeException(
            'Could not back up the existing region texture.'
        );
    }

    $meta = [
        'format' =>
            'AUSTRALIA-REGION-MAP-TEXTURE-BACKUP-V1',

        'region_uuid' =>
            strtolower($uuid),

        'reason' =>
            $reason,

        'source_path' =>
            $path,

        'backup_path' =>
            $destination,

        'created' =>
            date(DATE_ATOM)
    ];

    @file_put_contents(
        $folder .
        '/TEXTURE-BACKUP.json',
        json_encode(
            $meta,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        )
    );

    return $destination;
}


function ag_rmt_orphan_files(
    array $activeUuids
): array {

    $active = [];

    foreach ($activeUuids as $uuid) {

        if (ag_rmt_valid_uuid((string)$uuid)) {

            $active[
                strtolower(
                    (string)$uuid
                )
            ] = true;
        }
    }

    $root =
        ag_rmt_custom_root();

    if (!is_dir($root)) {
        return [];
    }

    $result = [];

    foreach (
        ag_rmt_allowed_extensions()
        as $extension
    ) {

        $files =
            glob(
                $root .
                '/*.' .
                $extension
            ) ?: [];

        foreach ($files as $path) {

            $base =
                strtolower(
                    pathinfo(
                        $path,
                        PATHINFO_FILENAME
                    )
                );

            if (
                isset(
                    $active[$base]
                )
            ) {
                continue;
            }

            $result[] = [
                'path' =>
                    $path,

                'filename' =>
                    basename($path),

                'bytes' =>
                    (int)(
                        @filesize($path) ?:
                        0
                    ),

                'modified' =>
                    @filemtime($path) ?:
                    null
            ];
        }
    }

    usort(
        $result,
        function (
            array $a,
            array $b
        ): int {

            return
                strnatcasecmp(
                    (string)$a['filename'],
                    (string)$b['filename']
                );
        }
    );

    return $result;
}
