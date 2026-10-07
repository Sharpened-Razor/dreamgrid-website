<?php
require_once __DIR__.'/php-compatibility.php';

if (!function_exists('ag_find_dreamgrid_root')) {

    function ag_find_dreamgrid_root(): ?string
    {
        // Installation location is stable for the lifetime of one PHP request.
        static $resolved = false;
        static $root = null;
        if ($resolved) return $root;
        $resolved = true;
        $dir = __DIR__;

        for ($i = 0; $i < 12; $i++) {

            $settings =
                $dir .
                DIRECTORY_SEPARATOR .
                'Settings.ini';

            if (is_file($settings)) {
                return $root = $dir;
            }

            $parent = dirname($dir);

            if ($parent === $dir) {
                break;
            }

            $dir = $parent;
        }

        return null;
    }
}


if (!function_exists('ag_read_grid_setting')) {

    function ag_read_grid_setting(
        string $file,
        string $key
    ): ?string {

        if (!is_readable($file)) {
            return null;
        }

        $handle = @fopen($file, 'rb');

        if ($handle === false) {
            return null;
        }

        while (($line = fgets($handle)) !== false) {

            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            if (
                $trimmed[0] === ';' ||
                $trimmed[0] === '#'
            ) {
                continue;
            }

            $pattern =
                '/^\s*' .
                preg_quote($key, '/') .
                '\s*=\s*(.*?)\s*$/i';

            if (!preg_match($pattern, $line, $match)) {
                continue;
            }

            $value = trim($match[1]);

            if (
                strlen($value) >= 2 &&
                (
                    (
                        $value[0] === '"' &&
                        substr($value, -1) === '"'
                    ) ||
                    (
                        $value[0] === "'" &&
                        substr($value, -1) === "'"
                    )
                )
            ) {
                $value = substr($value, 1, -1);
            }

            $value = trim($value);

            fclose($handle);

            return $value !== ''
                ? $value
                : null;
        }

        fclose($handle);

        return null;
    }
}


if (!function_exists('ag_grid_name')) {

    function ag_grid_name(): string
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $root = ag_find_dreamgrid_root();

        if ($root !== null) {

            $settings =
                $root .
                DIRECTORY_SEPARATOR .
                'Settings.ini';

            $name =
                ag_read_grid_setting(
                    $settings,
                    'SimName'
                );

            if ($name !== null) {

                $cached = $name;

                return $cached;
            }


            $wifi =
                $root .
                DIRECTORY_SEPARATOR .
                'Opensim' .
                DIRECTORY_SEPARATOR .
                'bin' .
                DIRECTORY_SEPARATOR .
                'config-addon-robust' .
                DIRECTORY_SEPARATOR .
                'Wifi.ini';

            $name =
                ag_read_grid_setting(
                    $wifi,
                    'GridName'
                );

            if ($name !== null) {

                $cached = $name;

                return $cached;
            }
        }

        $cached = 'GRID';

        return $cached;
    }
}


if (!function_exists('ag_grid_name_html')) {

    function ag_grid_name_html(): string
    {
        return htmlspecialchars(
            ag_grid_name(),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}
