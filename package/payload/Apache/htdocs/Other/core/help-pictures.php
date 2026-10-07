<?php

function ag_help_picture_topics(): array
{
    return [

        'home' => [
            'label' => 'Help Centre Home',
        ],

        'getting-started' => [
            'label' => 'Getting Started',
        ],

        'firestorm-grid' => [
            'label' => 'Add This Grid to Firestorm',
        ],

        'login' => [
            'label' => 'Logging In',
        ],

        'teleport' => [
            'label' => 'Teleporting',
        ],

        'edit-account' => [
            'label' => 'Edit My Account',
        ],

        'my-profile' => [
            'label' => 'My Profile',
        ],

        'inventory-browser' => [
            'label' => 'Inventory Browser',
        ],

        'clean-inventory' => [
            'label' => 'Clean Inventory',
        ],

        'safety-iar' => [
            'label' => 'Safety Inventory IAR',
        ],

        'iar-backups' => [
            'label' => 'IAR Backups',
        ],

        'delete-iar' => [
            'label' => 'Delete an IAR Backup',
        ],

        'grid-map' => [
            'label' => 'Grid Map',
        ],

        'my-regions' => [
            'label' => 'My Regions',
        ],

        'offline-messages' => [
            'label' => 'Offline Messages',
        ],

        'linked-regions' => [
            'label' => 'Linked Regions',
        ],
    ];
}


function ag_help_picture_slots(): array
{
    $root =
        dirname(__DIR__);


    return [

        /*
         * HELP CENTRE HOME
         */



        /*
         * GETTING STARTED
         */



        /*
         * FIRESTORM - 6 REAL SUPPLIED DEFAULTS
         */

        'firestorm-step-01' => [
            'topic' =>
                'firestorm-grid',
            'folder' =>
                'firestorm',
            'base' =>
                'step-01-open-viewer',
            'label' =>
                'Step 1 - Click & Open Viewer',
            'description' =>
                'Open the Firestorm OpenSim-compatible viewer.',
            'default_file' =>
                $root .
                '/assets/images/help/firestorm/step-01-open-viewer.png',
            'default_url' =>
                '/Other/assets/images/help/firestorm/step-01-open-viewer.png',
        ],

        'firestorm-step-02' => [
            'topic' =>
                'firestorm-grid',
            'folder' =>
                'firestorm',
            'base' =>
                'step-02-open-preferences',
            'label' =>
                'Step 2 - Click & Open Preferences',
            'description' =>
                'Open Firestorm Preferences.',
            'default_file' =>
                $root .
                '/assets/images/help/firestorm/step-02-open-preferences.png',
            'default_url' =>
                '/Other/assets/images/help/firestorm/step-02-open-preferences.png',
        ],

        'firestorm-step-03' => [
            'topic' =>
                'firestorm-grid',
            'folder' =>
                'firestorm',
            'base' =>
                'step-03-open-opensim',
            'label' =>
                'Step 3 - Click & Open OpenSim',
            'description' =>
                'Open the OpenSim section in Firestorm Preferences.',
            'default_file' =>
                $root .
                '/assets/images/help/firestorm/step-03-open-opensim.png',
            'default_url' =>
                '/Other/assets/images/help/firestorm/step-03-open-opensim.png',
        ],

        'firestorm-step-04' => [
            'topic' =>
                'firestorm-grid',
            'folder' =>
                'firestorm',
            'base' =>
                'step-04-add-new-grid',
            'label' =>
                'Step 4 - Add New Grid',
            'description' =>
                'Show where to add a new OpenSim grid.',
            'default_file' =>
                $root .
                '/assets/images/help/firestorm/step-04-add-new-grid.png',
            'default_url' =>
                '/Other/assets/images/help/firestorm/step-04-add-new-grid.png',
        ],

        'firestorm-step-05' => [
            'topic' =>
                'firestorm-grid',
            'folder' =>
                'firestorm',
            'base' =>
                'step-05-enter-grid-login-address',
            'label' =>
                'Step 5 - Enter Grid Login Address',
            'description' =>
                'Show where the grid login address is entered.',
            'default_file' =>
                $root .
                '/assets/images/help/firestorm/step-05-enter-grid-login-address.png',
            'default_url' =>
                '/Other/assets/images/help/firestorm/step-05-enter-grid-login-address.png',
        ],

        'firestorm-step-06' => [
            'topic' =>
                'firestorm-grid',
            'folder' =>
                'firestorm',
            'base' =>
                'step-06-apply-then-ok',
            'label' =>
                'Step 6 - Apply and OK',
            'description' =>
                'Show Apply and OK to save the grid.',
            'default_file' =>
                $root .
                '/assets/images/help/firestorm/step-06-apply-then-ok.png',
            'default_url' =>
                '/Other/assets/images/help/firestorm/step-06-apply-then-ok.png',
        ],


        /*
         * LOGGING IN - 5 MANAGED PLACEHOLDER SLOTS
         */

        'login-step-01' => [
            'topic' =>
                'login',
            'folder' =>
                'login',
            'base' =>
                'step-01-select-grid',
            'label' =>
                'Step 1 - Select This Grid',
            'description' =>
                'Show the grid selector on the Firestorm login screen.',
            'default_file' =>
                $root .
                '/assets/images/help/login/step-01-select-grid.png',
            'default_url' =>
                '/Other/assets/images/help/login/step-01-select-grid.png',
        ],

        'login-step-02' => [
            'topic' =>
                'login',
            'folder' =>
                'login',
            'base' =>
                'step-02-enter-first-name',
            'label' =>
                'Step 2 - Enter First Name',
            'description' =>
                'Show where the avatar First Name is entered.',
            'default_file' =>
                $root .
                '/assets/images/help/login/step-02-enter-first-name.png',
            'default_url' =>
                '/Other/assets/images/help/login/step-02-enter-first-name.png',
        ],

        'login-step-03' => [
            'topic' =>
                'login',
            'folder' =>
                'login',
            'base' =>
                'step-03-enter-last-name',
            'label' =>
                'Step 3 - Enter Last Name',
            'description' =>
                'Show where the avatar Last Name is entered.',
            'default_file' =>
                $root .
                '/assets/images/help/login/step-03-enter-last-name.png',
            'default_url' =>
                '/Other/assets/images/help/login/step-03-enter-last-name.png',
        ],

        'login-step-04' => [
            'topic' =>
                'login',
            'folder' =>
                'login',
            'base' =>
                'step-04-enter-password',
            'label' =>
                'Step 4 - Enter Password',
            'description' =>
                'Show where the avatar password is entered.',
            'default_file' =>
                $root .
                '/assets/images/help/login/step-04-enter-password.png',
            'default_url' =>
                '/Other/assets/images/help/login/step-04-enter-password.png',
        ],

        'login-step-05' => [
            'topic' =>
                'login',
            'folder' =>
                'login',
            'base' =>
                'step-05-click-log-in',
            'label' =>
                'Step 5 - Click Log In',
            'description' =>
                'Show the Firestorm Log In button.',
            'default_file' =>
                $root .
                '/assets/images/help/login/step-05-click-log-in.png',
            'default_url' =>
                '/Other/assets/images/help/login/step-05-click-log-in.png',
        ],


        /*
         * TELEPORTING
         */

        'topic-teleport' => [
            'topic' =>
                'teleport',
            'folder' =>
                'topics',
            'base' =>
                'firestorm-world-map',
            'label' =>
                'Step 1 - Open World Map',
            'description' =>
                'Open the Firestorm World Map.',
            'default_file' =>
                $root .
                '/assets/images/help/topics/firestorm-world-map.png',
            'default_url' =>
                '/Other/assets/images/help/topics/firestorm-world-map.png',
        ],


        'topic-teleport-step-02' => [
            'topic' =>
                'teleport',
            'folder' =>
                'teleport',
            'base' =>
                'step-02-search-region',
            'label' =>
                'Step 2 - Search For Region',
            'description' =>
                'Show where to enter the destination region name.',
            'default_file' =>
                $root .
                '/assets/images/help/teleport/step-02-search-region.png',
            'default_url' =>
                '/Other/assets/images/help/teleport/step-02-search-region.png',
        ],


        'topic-teleport-step-03' => [
            'topic' =>
                'teleport',
            'folder' =>
                'teleport',
            'base' =>
                'step-03-select-region',
            'label' =>
                'Step 3 - Select Region',
            'description' =>
                'Show how to select the destination region.',
            'default_file' =>
                $root .
                '/assets/images/help/teleport/step-03-select-region.png',
            'default_url' =>
                '/Other/assets/images/help/teleport/step-03-select-region.png',
        ],


        'topic-teleport-step-04' => [
            'topic' =>
                'teleport',
            'folder' =>
                'teleport',
            'base' =>
                'step-04-click-teleport',
            'label' =>
                'Step 4 - Click Teleport',
            'description' =>
                'Show the Firestorm Teleport button.',
            'default_file' =>
                $root .
                '/assets/images/help/teleport/step-04-click-teleport.png',
            'default_url' =>
                '/Other/assets/images/help/teleport/step-04-click-teleport.png',
        ],

        /*
         * EDIT MY ACCOUNT
         */

        'topic-edit-account' => [
            'topic' =>
                'edit-account',
            'folder' =>
                'topics',
            'base' =>
                'my-account',
            'label' =>
                'Edit My Account Picture',
            'description' =>
                'Main picture for the Edit My Account guide.',
            'default_file' =>
                $root .
                '/assets/images/help/topics/my-account.png',
            'default_url' =>
                '/Other/assets/images/help/topics/my-account.png',
        ],


        /*
         * MY PROFILE
         */

        'topic-my-profile' => [
            'topic' =>
                'my-profile',
            'folder' =>
                'topics',
            'base' =>
                'my-profile',
            'label' =>
                'My Profile Picture',
            'description' =>
                'Main picture for the My Profile guide.',
            'default_file' =>
                $root .
                '/assets/images/help/topics/my-profile.png',
            'default_url' =>
                '/Other/assets/images/help/topics/my-profile.png',
        ],


        /*
         * INVENTORY BROWSER
         */

        'topic-inventory-browser' => [
            'topic' =>
                'inventory-browser',
            'folder' =>
                'topics',
            'base' =>
                'inventory-browser',
            'label' =>
                'Inventory Browser Picture',
            'description' =>
                'Main picture for the Inventory Browser guide.',
            'default_file' =>
                $root .
                '/assets/images/help/topics/inventory-browser.png',
            'default_url' =>
                '/Other/assets/images/help/topics/inventory-browser.png',
        ],


        /*
         * CLEAN INVENTORY
         */

        'topic-clean-inventory' => [
            'topic' =>
                'clean-inventory',
            'folder' =>
                'topics',
            'base' =>
                'clean-inventory',
            'label' =>
                'Clean Inventory Picture',
            'description' =>
                'Main picture for the Clean Inventory guide.',
            'default_file' =>
                $root .
                '/assets/images/help/topics/clean-inventory.png',
            'default_url' =>
                '/Other/assets/images/help/topics/clean-inventory.png',
        ],


        /*
         * SAFETY INVENTORY IAR
         */

        'topic-safety-iar' => [
            'topic' =>
                'safety-iar',
            'folder' =>
                'topics',
            'base' =>
                'safety-iar',
            'label' =>
                'Safety Inventory IAR Picture',
            'description' =>
                'Main picture for the Safety Inventory IAR guide.',
            'default_file' =>
                $root .
                '/assets/images/help/topics/safety-iar.png',
            'default_url' =>
                '/Other/assets/images/help/topics/safety-iar.png',
        ],


        /*
         * IAR BACKUPS
         */

        'topic-iar-backups' => [
            'topic' =>
                'iar-backups',
            'folder' =>
                'topics',
            'base' =>
                'iar-backups',
            'label' =>
                'IAR Backups Picture',
            'description' =>
                'Main picture for the IAR Backups guide.',
            'default_file' =>
                $root .
                '/assets/images/help/topics/iar-backups.png',
            'default_url' =>
                '/Other/assets/images/help/topics/iar-backups.png',
        ],


        /*
         * DELETE AN IAR BACKUP
         */



        /*
         * GRID MAP
         */

        'topic-grid-map' => [
            'topic' =>
                'grid-map',
            'folder' =>
                'topics',
            'base' =>
                'grid-map',
            'label' =>
                'Grid Map Picture',
            'description' =>
                'Main picture for the Grid Map guide.',
            'default_file' =>
                $root .
                '/assets/images/help/topics/grid-map.png',
            'default_url' =>
                '/Other/assets/images/help/topics/grid-map.png',
        ],


        /*
         * MY REGIONS
         */



        /*
         * OFFLINE MESSAGES
         */



        /*
         * LINKED REGIONS
         */

    ];
}


function ag_help_picture_topic_slots(
    string $topicId
): array {

    $result = [];


    foreach (
        ag_help_picture_slots()
        as $key => $slot
    ) {

        if (
            (string)
            ($slot['topic'] ?? '') ===
            $topicId
        ) {

            $slot['key'] =
                $key;

            $result[$key] =
                $slot;
        }
    }


    return $result;
}


function ag_help_picture_slot(
    string $key
): ?array {

    $slots =
        ag_help_picture_slots();


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


function ag_help_picture_allowed_extensions(): array
{
    return [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];
}


function ag_help_picture_custom_root(): string
{
    return
        dirname(__DIR__) .
        '/assets/images/help/custom';
}


function ag_help_picture_safe_segment(
    string $value
): string {

    $clean =
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '',
            $value
        );


    return
        is_string($clean)
            ? $clean
            : '';
}


function ag_help_picture_target(
    array $slot,
    string $extension
): string {

    $folder =
        ag_help_picture_safe_segment(
            (string)
            ($slot['folder'] ?? '')
        );


    $base =
        ag_help_picture_safe_segment(
            (string)
            ($slot['base'] ?? '')
        );


    $extension =
        strtolower(
            trim($extension)
        );


    if (
        $folder === '' ||
        $base === '' ||
        !in_array(
            $extension,
            ag_help_picture_allowed_extensions(),
            true
        )
    ) {

        throw new RuntimeException(
            'Invalid Help picture target.'
        );
    }


    return
        ag_help_picture_custom_root() .
        '/' .
        $folder .
        '/' .
        $base .
        '.' .
        $extension;
}


function ag_help_picture_all_custom_files(
    array $slot
): array {

    $result = [];


    foreach (
        ag_help_picture_allowed_extensions()
        as $extension
    ) {

        $path =
            ag_help_picture_target(
                $slot,
                $extension
            );


        if (is_file($path)) {

            $result[] =
                $path;
        }
    }


    return $result;
}


function ag_help_picture_find_custom_path(
    string $key
): ?string {

    $slot =
        ag_help_picture_slot(
            $key
        );


    if (!$slot) {

        return null;
    }


    $files =
        ag_help_picture_all_custom_files(
            $slot
        );


    return
        $files
            ? $files[0]
            : null;
}


function ag_help_picture_file_info(
    string $path,
    string $url
): array {

    $result = [
        'exists' =>
            false,
        'path' =>
            '',
        'url' =>
            '',
        'filename' =>
            '',
        'bytes' =>
            0,
        'width' =>
            0,
        'height' =>
            0,
        'mime' =>
            '',
        'modified' =>
            null,
    ];


    if (
        $path === '' ||
        !is_file($path)
    ) {

        return $result;
    }


    $modified =
        @filemtime($path) ?: null;


    if (
        $url !== '' &&
        $modified !== null
    ) {

        $url .=
            (
                strpos(
                    $url,
                    '?'
                ) !== false
                    ? '&'
                    : '?'
            ) .
            'v=' .
            (string)$modified;
    }


    $result['exists'] =
        true;

    $result['path'] =
        $path;

    $result['url'] =
        $url;

    $result['filename'] =
        basename($path);

    $result['bytes'] =
        (int)
        (@filesize($path) ?: 0);

    $result['modified'] =
        $modified;


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


    return $result;
}


function ag_help_picture_default_info(
    string $key
): array {

    $slot =
        ag_help_picture_slot(
            $key
        );


    if (!$slot) {

        return
            ag_help_picture_file_info(
                '',
                ''
            );
    }


    return
        ag_help_picture_file_info(
            (string)
            ($slot['default_file'] ?? ''),
            (string)
            ($slot['default_url'] ?? '')
        );
}


function ag_help_picture_info(
    string $key
): array {

    $slot =
        ag_help_picture_slot(
            $key
        );


    if (!$slot) {

        return [
            'exists' => false,
            'is_custom' => false,
            'has_default' => false,
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
        ag_help_picture_find_custom_path(
            $key
        );


    $defaultInfo =
        ag_help_picture_default_info(
            $key
        );


    if ($customPath !== null) {

        $folder =
            ag_help_picture_safe_segment(
                (string)
                ($slot['folder'] ?? '')
            );


        $customInfo =
            ag_help_picture_file_info(
                $customPath,
                '/Other/assets/images/help/custom/' .
                $folder .
                '/' .
                basename($customPath)
            );


        return
            array_merge(
                $customInfo,
                [
                    'is_custom' =>
                        true,
                    'has_default' =>
                        (bool)
                        $defaultInfo['exists'],
                    'status' =>
                        'CUSTOM',
                    'slot' =>
                        $slot,
                ]
            );
    }


    if ($defaultInfo['exists']) {

        return
            array_merge(
                $defaultInfo,
                [
                    'is_custom' =>
                        false,
                    'has_default' =>
                        true,
                    'status' =>
                        'DEFAULT',
                    'slot' =>
                        $slot,
                ]
            );
    }


    return [
        'exists' =>
            false,
        'is_custom' =>
            false,
        'has_default' =>
            false,
        'status' =>
            'PLACEHOLDER',
        'path' =>
            '',
        'url' =>
            '',
        'filename' =>
            '',
        'bytes' =>
            0,
        'width' =>
            0,
        'height' =>
            0,
        'mime' =>
            '',
        'modified' =>
            null,
        'slot' =>
            $slot,
    ];
}


function ag_help_picture_url(
    string $key
): string {

    $info =
        ag_help_picture_info(
            $key
        );


    return
        (string)
        ($info['url'] ?? '');
}


function ag_help_picture_display_url(
    string $key
): string {

    $url =
        ag_help_picture_url(
            $key
        );


    if ($url !== '') {

        return $url;
    }


    if (!ag_help_picture_slot($key)) {

        return '';
    }


    return
        '/Other/help-picture-placeholder.php?slot=' .
        rawurlencode($key);
}

