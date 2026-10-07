<?php
/*
 * ============================================================
 * GRID - PORTABLE CENTRAL DATABASE ACCESS
 * ============================================================
 *
 * DreamGrid generates:
 *
 * Apache\htdocs\MetroMap\includes\config.php
 *
 * from its current Robust MySQL configuration.
 *
 * The DreamGrid root is discovered dynamically through
 * dreamgrid-env.php. No drive letter or installation folder
 * is hard-coded here.
 * ============================================================
 */

require_once __DIR__ . '/dreamgrid-env.php';


if (!function_exists('ag_db_config_path')) {

    function ag_db_config_path(): ?string
    {
        return ag_dg_path(
            'Apache',
            'htdocs',
            'MetroMap',
            'includes',
            'config.php'
        );
    }
}


$configPath =
    ag_db_config_path();

if (
    $configPath === null ||
    !is_file($configPath)
) {
    throw new RuntimeException(
        'Grid database configuration was not found.'
    );
}

require_once $configPath;


if (!function_exists('ag_db_connect')) {

    function ag_db_connect(): ?mysqli
    {
        global
            $CONF_db_server,
            $CONF_db_user,
            $CONF_db_pass,
            $CONF_db_database,
            $CONF_db_port;

        $con = null;
        try {
            $con = @mysqli_connect(
                $CONF_db_server, $CONF_db_user, $CONF_db_pass,
                $CONF_db_database, (int)$CONF_db_port
            );
            if (!$con) return null;
            if (!@mysqli_set_charset($con, 'utf8mb4')) {
                mysqli_close($con);
                return null;
            }
        } catch (mysqli_sql_exception $error) {
            if ($con instanceof mysqli) mysqli_close($con);
            return null;
        }

        return $con;
    }
}