<?php
/*
 * Grid - CENTRAL NAVIGATION DEFINITIONS
 *
 * These menus intentionally contain only Apache/PHP Australia routes.
 */

require_once __DIR__ . '/routes.php';

function ag_admin_navigation(): array
{
    return [
        [
            'label' => 'ADMIN DASHBOARD',
            'url'   => '/Other/admin-dashboard.php',
        ],
        [
            'label' => 'MANAGE ACCOUNTS',
            'url'   => '/Other/admin-accounts.php',
        ],
        [
            'label' => 'MANAGE GROUPS',
            'url'   => '/Other/admin-groups.php',
        ],
        [
            'label' => 'CONSOLE',
            'url'   => '/Other/admin-console.php',
        ],
        [
            'label' => 'ADMIN HOME',
            'url'   => '/Other/admin-home.php',
        ],
    ];
}

function ag_user_navigation(): array
{
    return [

        [
            'label' => 'MY DASHBOARD',
            'url'   => ag_route('user_dashboard'),
        ],

        [
            'label' => 'HELP CENTRE',
            'url'   => ag_route('user_help'),
        ],

        [
            'label' => 'HOME',
            'url'   => ag_route('user_home'),
        ],
    ];
}
