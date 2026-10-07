<?php
/*
 * Grid - CENTRAL ROUTES
 *
 * All permanent Australia website navigation belongs here.
 * No DreamGrid /wifi routes are defined in this file.
 */

function ag_routes(): array
{
    static $routes = [
        'login'            => '/Other/index.php',
        'logout'           => '/Other/logout.php',

        'user_home'        => '/Other/FreshUserDashboardExact/user-dashboard.php',
        'user_dashboard'   => '/Other/FreshUserDashboardExact/user-dashboard.php',
        'user_account'     => '/Other/FreshUserDashboardExact/UserPages/account.php',
        'user_profile'     => '/Other/FreshUserDashboardExact/UserPages/profile.php',
        'user_regions'     => '/Other/FreshUserDashboardExact/UserPages/regions.php',
        'user_inventory'   => '/Other/FreshUserDashboardExact/UserPages/inventory.php',
        'user_iar'         => '/Other/FreshUserDashboardExact/UserPages/iar-backups.php',
        'user_map'         => '/Other/FreshUserDashboardExact/UserPages/map.php?ccfit=2',
        'user_linked'      => '/Other/user-linked-regions.php',
        'user_help'        => '/Other/FreshUserDashboardExact/UserPages/help-centre.php',

        'admin_home'       => '/Other/admin-home.php',
        'admin_dashboard'      => '/Other/admin-dashboard.php',
        'admin_tools' => '/Other/admin-tools.php',
        'admin_accounts'       => '/Other/admin-accounts.php',
        'admin_groups'         => '/Other/admin-groups.php',
        'admin_group_create'   => '/Other/admin-group-create.php',
        'admin_group_manage'   => '/Other/admin-group-manage.php',
        'admin_group_delete'   => '/Other/admin-group-delete.php',
        'admin_account_create' => '/Other/admin-account-create.php',
        'admin_account_delete' => '/Other/admin-account-delete.php',
        'admin_regions'        => '/Other/admin-regions.php',
        'admin_textures'       => '/Other/admin-textures.php',
        'admin_region_manage'  => '/Other/admin-region-manage.php',
        'admin_messages'   => '/Other/admin-offline-messages.php',
        'admin_map'        => '/Other/grid-map.php',
        'admin_panel'      => '/Other/australia-panel.php',
        'admin_health'     => '/Other/health-diagnostics.php',
    ];

    return $routes;
}

function ag_route(string $name): string
{
    $routes = ag_routes();

    if (!array_key_exists($name, $routes)) {
        throw new RuntimeException('Unknown website route: ' . $name);
    }

    return $routes[$name];
}
