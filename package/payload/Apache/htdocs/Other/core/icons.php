<?php

if (!function_exists('ag_icon')) {
    function ag_icon(
        string $name,
        ?string $tone = null,
        string $extraClass = ''
    ): string {

        $name = strtolower(trim($name));

        $aliases = [
            'avatars' => 'avatar'
        ];

        if (isset($aliases[$name])) {
            $name = $aliases[$name];
        }

        $allowedIcons = [
            'console','statistics','start','stop','restart','pause',
            'load','save','log','teleport','map','edit','alert',
            'freeze','thaw','add','refresh','import','export',
            'users','avatar','email','details','home','settings',
            'groups','tools','backup','restore','account',
            'dashboard','search','lock','unlock','delete',
            'region','power','inventory','link','help',
            'notification','files','calendar','database','image',
            'view','key','filter','report','checklist','bookmark',
            'admin-home','chat','logout','control-panel'
        ];

        if (!in_array($name, $allowedIcons, true)) {
            $name = 'details';
        }

        $defaultTone = [
            'console'       => 'console',
            'statistics'    => 'statistics',
            'start'         => 'start',
            'stop'          => 'stop',
            'restart'       => 'restart',
            'pause'         => 'pause',
            'load'          => 'load',
            'save'          => 'save',
            'log'           => 'log',
            'teleport'      => 'teleport',
            'map'           => 'map',
            'edit'          => 'edit',
            'alert'         => 'alert',
            'freeze'        => 'freeze',
            'thaw'          => 'thaw',
            'add'           => 'add',
            'refresh'       => 'refresh',
            'import'        => 'import',
            'export'        => 'export',
            'users'         => 'users',
            'avatar'        => 'avatars',
            'email'         => 'email',
            'details'       => 'details',
            'home'          => 'home',
            'settings'      => 'settings',
            'groups'        => 'groups',
            'tools'         => 'tools',
            'backup'        => 'backup',
            'restore'       => 'restore',
            'account'       => 'users',
            'dashboard'     => 'statistics',
            'search'        => 'details',
            'lock'          => 'stop',
            'unlock'        => 'start',
            'delete'        => 'delete',
            'region'        => 'map',
            'power'         => 'start',
            'inventory'     => 'load',
            'link'          => 'teleport',
            'help'          => 'details',
            'notification'  => 'alert',
            'files'         => 'details',
            'calendar'      => 'details',
            'database'      => 'details',
            'image'         => 'details',
            'view'          => 'details',
            'key'           => 'details',
            'filter'        => 'details',
            'report'        => 'statistics',
            'checklist'     => 'details',
            'bookmark'      => 'details',
            'admin-home'    => 'home',
            'chat'          => 'email',
            'logout'        => 'stop',
            'control-panel' => 'settings'
        ];

        if ($tone === null || trim($tone) === '') {
            $tone = $defaultTone[$name] ?? 'details';
        }

        $tone = preg_replace('/[^a-z0-9_-]/i', '', strtolower($tone));
        $extraClass = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $extraClass);

        $extraClass = preg_replace(
            '/\bag-icon-badge\b|\bag-badge-[a-z0-9_-]+\b|\bag-dashboard-glow\b/i',
            '',
            $extraClass
        );
        $extraClass = trim(
            preg_replace('/\s+/', ' ', $extraClass)
        );

        $classes = trim(
            'ag-sentinel-icon ' . $extraClass
        );

        /*
         * AUSTRALIA SENTINEL PICTURE ICONS V1
         *
         * These PNGs are the new website-wide icon theme.
         * Any icon without a matching PNG falls back to the existing SVG
         * sprite so controls remain functional while the picture pack grows.
         */
        $pictureFiles = [
            'console'       => 'console.png',
            'statistics'    => 'statistics.png',
            'restart'       => 'refresh.png',
            'load'          => 'import.png',
            'save'          => 'save.png',
            'log'           => 'report.png',
            'teleport'      => 'map.png',
            'map'           => 'map.png',
            'edit'          => 'edit.png',
            'alert'         => 'alert.png',
            'add'           => 'add-region.png',
            'refresh'       => 'refresh.png',
            'import'        => 'import.png',
            'export'        => 'export.png',
            'users'         => 'users.png',
            'avatar'        => 'avatar.png',
            'email'         => 'email.png',
            'details'       => 'help.png',
            'home'          => 'home.png',
            'settings'      => 'settings.png',
            'groups'        => 'groups.png',
            'tools'         => 'tools.png',
            'backup'        => 'backup.png',
            'restore'       => 'refresh.png',
            'account'       => 'account.png',
            'dashboard'     => 'dashboard.png',
            'search'        => 'search.png',
            'lock'          => 'security.png',
            'delete'        => 'delete.png',
            'region'        => 'region.png',
            'inventory'     => 'inventory.png',
            'link'          => 'link.png',
            'help'          => 'help.png',
            'notification'  => 'notification.png',
            'files'         => 'files.png',
            'calendar'      => 'calendar.png',
            'database'      => 'database.png',
            'image'         => 'image.png',
            'view'          => 'view.png',
            'key'           => 'key.png',
            'filter'        => 'filter.png',
            'report'        => 'report.png',
            'checklist'     => 'checklist.png',
            'bookmark'      => 'bookmark.png',
            'admin-home'    => 'admin-home.png',
            'chat'          => 'chat.png',
            'logout'        => 'logout.png',
            'control-panel' => 'control-panel.png',
            'start'         => 'control-panel.png',
            'stop'          => 'logout.png',
            'pause'         => 'calendar.png',
            'freeze'        => 'security.png',
            'thaw'          => 'key.png',
            'unlock'        => 'key.png',
            'power'         => 'control-panel.png'
        ];

        if (isset($pictureFiles[$name])) {
            $fileName = $pictureFiles[$name];
            $diskPath = dirname(__DIR__) . '/assets/icons/sentinel/' . $fileName;

            if (is_file($diskPath)) {
                $src = '/Other/assets/icons/sentinel/' . $fileName;

                return
                    '<span class="' .
                    htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') .
                    '" aria-hidden="true">' .
                    '<img src="' .
                    htmlspecialchars($src, ENT_QUOTES, 'UTF-8') .
                    '" alt="" draggable="false" decoding="async" ' .
                    'style="width:100%;height:100%;object-fit:contain;display:block;">' .
                    '</span>';
            }
        }

        /* Picture-only fallback. No SVG artwork remains in the live helper. */
        $src = '/Other/assets/icons/sentinel/help.png';

        return
            '<span class="' .
            htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') .
            '" aria-hidden="true">' .
            '<img src="' .
            htmlspecialchars($src, ENT_QUOTES, 'UTF-8') .
            '" alt="" draggable="false" decoding="async" ' .
            'style="width:100%;height:100%;object-fit:contain;display:block;">' .
            '</span>';
    }
}

