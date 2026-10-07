<?php
declare(strict_types=1);

require_once __DIR__ . '/dreamgrid-env.php';


/*
 * Grid - SITE & PAGE DESIGN
 *
 * THREE PICTURE SLOTS:
 *
 * 1. Main Login Front Page Picture
 * 2. Admin Control Center Background
 * 3. User Control Center Background
 *
 * Supplied/default pictures are NEVER overwritten.
 *
 * Custom picture exists:
 *     use CUSTOM.
 *
 * Custom picture does not exist:
 *     automatically use DEFAULT.
 */


function ag_site_design_custom_root(): string
{
    return
        dirname(__DIR__) .
        '/custom-site-design';
}


function ag_site_design_backup_root(): string
{
    return
        ag_dg_root() .
        '/_SITE_DESIGN_BACKUPS';
}


function ag_site_design_slots(): array
{
    $root =
        dirname(__DIR__);


    $userDefaultFile=$root.'/assets/images/control-center-teal-bg.png';
    $userDefaultUrl='/Other/assets/images/control-center-teal-bg.png';
    $pictures=glob($root.'/custom/Branding/*') ?: array();
    $backgrounds=array_values(array_filter($pictures,function($p){return is_file($p)&&preg_match('/background\.(png|jpe?g|webp)$/i',basename($p));}));
    if(count($backgrounds)===1){$userDefaultFile=$backgrounds[0];$userDefaultUrl='/Other/custom/Branding/'.rawurlencode(basename($userDefaultFile));}

    return [


        'frontpage-background' => [

            'folder' =>
                'login',

            'label' =>
                'Main Login Front Page Picture',

            'description' =>
                'The main public Grid Login / Home picture everyone sees before signing in.',

            'default_file' =>
                $root .
                '/assets/images/Frontpage-background.png',

            'default_url' =>
                '/Other/assets/images/Frontpage-background.png',
        ],


        'admin-control-center-background' => [

            'folder' =>
                'admin',

            'label' =>
                'Admin Control Center Background',

            'description' =>
                'Background behind the administrator Control Center and its left-side navigation.',

            'default_file' =>
                $root .
                '/assets/images/control-center-teal-bg.png',

            'default_url' =>
                '/Other/assets/images/control-center-teal-bg.png',
        ],


        'user-control-center-background' => [

            'folder' =>
                'user',

            'label' =>
                'User Control Center Background',

            'description' =>
                'Independent background for the User Dashboard and future User Control Center.',

            'default_file' =>
                $userDefaultFile,

            'default_url' =>
                $userDefaultUrl,
        ],


    ];
}


function ag_site_design_slot(
    string $key
): ?array {

    $slots =
        ag_site_design_slots();

    if (
        !isset($slots[$key]) ||
        !is_array($slots[$key])
    ) {
        return null;
    }

    $slot =
        $slots[$key];

    $slot['key'] =
        $key;

    return $slot;
}


function ag_site_design_allowed_extensions(): array
{
    return [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];
}


function ag_site_design_target(
    array $slot,
    string $extension
): string {

    $folder =
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '',
            (string)($slot['folder'] ?? '')
        );

    $key =
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '',
            (string)($slot['key'] ?? '')
        );


    if (
        $folder === '' ||
        $key === ''
    ) {

        throw new RuntimeException(
            'Invalid Site & Page Design picture slot.'
        );
    }


    if (
        !in_array(
            $extension,
            ag_site_design_allowed_extensions(),
            true
        )
    ) {

        throw new RuntimeException(
            'Invalid Site & Page Design image extension.'
        );
    }


    return
        ag_site_design_custom_root() .
        '/' .
        $folder .
        '/' .
        $key .
        '.' .
        $extension;
}


function ag_site_design_custom_files(
    string $key
): array {

    $slot =
        ag_site_design_slot($key);

    if (!$slot) {
        return [];
    }


    $files = [];


    foreach (
        ag_site_design_allowed_extensions()
        as
        $extension
    ) {

        $path =
            ag_site_design_target(
                $slot,
                $extension
            );


        if (is_file($path)) {
            $files[] = $path;
        }
    }


    return $files;
}


function ag_site_design_find_custom_path(
    string $key
): ?string {

    $files =
        ag_site_design_custom_files(
            $key
        );

    return
        $files[0]
        ?? null;
}


function ag_site_design_web_path_from_file(
    string $path
): string {

    $root =
        str_replace(
            '\\',
            '/',
            realpath(dirname(__DIR__))
                ?: dirname(__DIR__)
        );


    $real =
        str_replace(
            '\\',
            '/',
            realpath($path)
                ?: $path
        );


    if (
        $root !== '' &&
        substr(strtolower($real), 0, strlen($root)) === strtolower($root)
    ) {

        $relative =
            substr(
                $real,
                strlen($root)
            );


        if (
            $relative === '' ||
            $relative[0] !== '/'
        ) {

            $relative =
                '/' .
                $relative;
        }


        return
            '/Other' .
            $relative;
    }


    return '';
}


function ag_site_design_asset_info(
    string $key
): array {

    $slot =
        ag_site_design_slot(
            $key
        );


    if (!$slot) {

        return [
            'exists' => false,
            'is_custom' => false,
            'status' => 'UNKNOWN',
            'path' => '',
            'url' => '',
            'filename' => '',
            'bytes' => 0,
            'width' => 0,
            'height' => 0,
            'mime' => '',
            'modified' => null,
            'slot' => null,
        ];
    }


    $customPath =
        ag_site_design_find_custom_path(
            $key
        );


    $isCustom =
        $customPath !== null;


    $path =
        $isCustom
            ? $customPath
            : (string)$slot['default_file'];


    $url =
        $isCustom
            ? ag_site_design_web_path_from_file(
                $path
            )
            : (string)$slot['default_url'];


    $mtime =
        is_file($path)
            ? (@filemtime($path) ?: null)
            : null;


    if (
        $url !== '' &&
        $mtime !== null
    ) {

        $url .=
            (
                strpos($url, '?') !== false
                    ? '&'
                    : '?'
            ) .
            'v=' .
            (string)$mtime;
    }


    $result = [

        'exists' =>
            is_file($path),

        'is_custom' =>
            $isCustom,

        'status' =>
            $isCustom
                ? 'CUSTOM'
                : 'DEFAULT',

        'path' =>
            $path,

        'url' =>
            $url,

        'filename' =>
            basename($path),

        'bytes' =>
            is_file($path)
                ? (int)(@filesize($path) ?: 0)
                : 0,

        'width' =>
            0,

        'height' =>
            0,

        'mime' =>
            '',

        'modified' =>
            $mtime,

        'slot' =>
            $slot,
    ];


    if (is_file($path)) {

        $image =
            @getimagesize(
                $path
            );


        if (is_array($image)) {

            $result['width'] =
                (int)
                ($image[0] ?? 0);

            $result['height'] =
                (int)
                ($image[1] ?? 0);

            $result['mime'] =
                (string)
                ($image['mime'] ?? '');
        }
    }


    return $result;
}


function ag_site_design_active_path(
    string $key
): ?string {

    $info =
        ag_site_design_asset_info(
            $key
        );


    $path =
        (string)
        ($info['path'] ?? '');


    if (
        $path === '' ||
        !is_file($path)
    ) {
        return null;
    }


    return $path;
}


function ag_site_design_safe_backup_name(
    string $value
): string {

    $clean =
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            $value
        )
        ??
        'site-design';


    return
        trim(
            $clean,
            '-_'
        )
        ?:
        'site-design';
}


function ag_site_design_backup_existing(
    string $slotKey,
    string $path,
    string $reason
): string {

    if (!is_file($path)) {
        return '';
    }


    $backupRoot =
        ag_site_design_backup_root();


    if (
        !is_dir($backupRoot) &&
        !@mkdir(
            $backupRoot,
            0770,
            true
        )
    ) {

        throw new RuntimeException(
            'Could not create Site Design backup directory.'
        );
    }


    $folder =
        $backupRoot .
        '/' .
        date('Ymd-His') .
        '-' .
        ag_site_design_safe_backup_name(
            $slotKey
        ) .
        '-' .
        bin2hex(
            random_bytes(2)
        );


    if (
        !@mkdir(
            $folder,
            0770,
            true
        )
    ) {

        throw new RuntimeException(
            'Could not create Site Design backup folder.'
        );
    }


    $destination =
        $folder .
        '/' .
        basename($path);


    if (
        !@copy(
            $path,
            $destination
        )
    ) {

        throw new RuntimeException(
            'Could not back up the previous custom picture.'
        );
    }


    $meta = [

        'format' =>
            'GRID-SITE-DESIGN-BACKUP-V1',

        'created_utc' =>
            gmdate('c'),

        'slot' =>
            $slotKey,

        'reason' =>
            $reason,

        'original_path' =>
            $path,

        'backup_path' =>
            $destination,
    ];


    @file_put_contents(

        $folder .
        '/SITE-DESIGN-BACKUP.json',

        json_encode(
            $meta,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        ),

        LOCK_EX
    );


    return $folder;
}


function ag_site_design_human_bytes(
    int $bytes
): string {

    if ($bytes >= 1048576) {

        return
            number_format(
                $bytes / 1048576,
                2
            ) .
            ' MB';
    }


    if ($bytes >= 1024) {

        return
            number_format(
                $bytes / 1024,
                1
            ) .
            ' KB';
    }


    return
        $bytes .
        ' B';
}


/*
 * ============================================================
 * SITE DESIGN DISPLAY SETTINGS
 * PHP 7.4 COMPATIBLE
 * ============================================================
 *
 * FIT VALUES:
 *
 * fill
 *     Preserve proportions and fill the available screen.
 *     Some edges can be cropped.
 *
 * whole
 *     Preserve proportions and show the entire picture.
 *
 * stretch
 *     Force the picture to the viewport dimensions.
 *
 * POSITION VALUES:
 *
 * center
 * top
 * bottom
 * left
 * right
 */


function ag_site_design_display_settings_path(): string
{
    return
        ag_site_design_custom_root() .
        '/display-settings.json';
}


function ag_site_design_default_display_settings(
    string $key
): array {

    /*
     * Preserve the CURRENT appearance of each live page.
     */

    if (
        $key ===
        'frontpage-background'
    ) {

        return [
            'fit' =>
                'stretch',

            'position' =>
                'center',
        ];
    }


    if (
        $key ===
        'admin-control-center-background'
    ) {

        return [
            'fit' =>
                'fill',

            'position' =>
                'top',
        ];
    }


    if (
        $key ===
        'user-control-center-background'
    ) {

        return [
            'fit' =>
                'fill',

            'position' =>
                'center',
        ];
    }


    return [
        'fit' =>
            'fill',

        'position' =>
            'center',
    ];
}


function ag_site_design_allowed_fits(): array
{
    return [
        'fill',
        'whole',
        'stretch',
    ];
}


function ag_site_design_allowed_positions(): array
{
    return [
        'center',
        'top',
        'bottom',
        'left',
        'right',
    ];
}


function ag_site_design_saved_display_settings(): array
{
    $path =
        ag_site_design_display_settings_path();


    if (!is_file($path)) {
        return [];
    }


    $raw =
        @file_get_contents(
            $path
        );


    if ($raw === false) {
        return [];
    }


    $decoded =
        json_decode(
            $raw,
            true
        );


    if (!is_array($decoded)) {
        return [];
    }


    if (
        !isset($decoded['slots']) ||
        !is_array($decoded['slots'])
    ) {
        return [];
    }


    return
        $decoded['slots'];
}


function ag_site_design_display_settings(
    string $key
): array {

    $defaults =
        ag_site_design_default_display_settings(
            $key
        );


    $saved =
        ag_site_design_saved_display_settings();


    $slot =
        isset($saved[$key]) &&
        is_array($saved[$key])
            ? $saved[$key]
            : [];


    $fit =
        isset($slot['fit'])
            ? (string)$slot['fit']
            : (string)$defaults['fit'];


    $position =
        isset($slot['position'])
            ? (string)$slot['position']
            : (string)$defaults['position'];


    if (
        !in_array(
            $fit,
            ag_site_design_allowed_fits(),
            true
        )
    ) {

        $fit =
            (string)$defaults['fit'];
    }


    if (
        !in_array(
            $position,
            ag_site_design_allowed_positions(),
            true
        )
    ) {

        $position =
            (string)$defaults['position'];
    }


    return [
        'fit' =>
            $fit,

        'position' =>
            $position,
    ];
}


function ag_site_design_save_display_settings(
    string $key,
    string $fit,
    string $position
): void {

    if (!ag_site_design_slot($key)) {

        throw new RuntimeException(
            'Unknown Site Design picture slot.'
        );
    }


    if (
        !in_array(
            $fit,
            ag_site_design_allowed_fits(),
            true
        )
    ) {

        throw new RuntimeException(
            'Invalid picture fit setting.'
        );
    }


    if (
        !in_array(
            $position,
            ag_site_design_allowed_positions(),
            true
        )
    ) {

        throw new RuntimeException(
            'Invalid picture position setting.'
        );
    }


    $root =
        ag_site_design_custom_root();


    if (
        !is_dir($root) &&
        !@mkdir(
            $root,
            0770,
            true
        )
    ) {

        throw new RuntimeException(
            'Could not create the Site Design settings folder.'
        );
    }


    $slots =
        ag_site_design_saved_display_settings();


    $slots[$key] = [
        'fit' =>
            $fit,

        'position' =>
            $position,
    ];


    $document = [
        'version' =>
            1,

        'slots' =>
            $slots,
    ];


    $json =
        json_encode(
            $document,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        );


    if (!is_string($json)) {

        throw new RuntimeException(
            'Could not encode Site Design display settings.'
        );
    }


    $path =
        ag_site_design_display_settings_path();


    $temporary =
        $path .
        '.tmp-' .
        bin2hex(
            random_bytes(4)
        );


    if (
        @file_put_contents(
            $temporary,
            $json,
            LOCK_EX
        )
        ===
        false
    ) {

        throw new RuntimeException(
            'Could not write Site Design display settings.'
        );
    }


    if (
        is_file($path) &&
        !@unlink($path)
    ) {

        @unlink($temporary);

        throw new RuntimeException(
            'Could not replace the previous display settings.'
        );
    }


    if (
        !@rename(
            $temporary,
            $path
        )
    ) {

        @unlink($temporary);

        throw new RuntimeException(
            'Could not commit Site Design display settings.'
        );
    }
}


function ag_site_design_fit_css(
    string $fit,
    bool $dynamicViewport = false
): string {

    if ($fit === 'whole') {

        return
            'contain';
    }


    if ($fit === 'stretch') {

        if ($dynamicViewport) {

            return
                '100vw 100dvh';
        }


        return
            '100vw 100vh';
    }


    return
        'cover';
}


function ag_site_design_position_css(
    string $position
): string {

    if ($position === 'top') {

        return
            'center top';
    }


    if ($position === 'bottom') {

        return
            'center bottom';
    }


    if ($position === 'left') {

        return
            'left center';
    }


    if ($position === 'right') {

        return
            'right center';
    }


    return
        'center center';
}


function ag_site_design_preview_object_fit(
    string $fit
): string {

    if ($fit === 'whole') {

        return
            'contain';
    }


    if ($fit === 'stretch') {

        return
            'fill';
    }


    return
        'cover';
}

