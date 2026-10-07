<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function ag_login_find_dreamgrid_root(): ?string
{
    $directory = __DIR__;

    for ($i = 0; $i < 12; $i++) {

        $settings =
            $directory .
            DIRECTORY_SEPARATOR .
            'Settings.ini';

        if (is_file($settings)) {
            return $directory;
        }

        $parent = dirname($directory);

        if ($parent === $directory) {
            break;
        }

        $directory = $parent;
    }

    return null;
}


function ag_login_read_ini_value(
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

        $first = $trimmed[0];

        if ($first === ';' || $first === '#') {
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


function ag_login_normalize_url(
    string $url
): ?string {

    $url = trim($url);

    if ($url === '') {
        return null;
    }

    if (
        !preg_match(
            '#^https?://#i',
            $url
        )
    ) {
        $url = 'http://' . $url;
    }

    if (
        !filter_var(
            $url,
            FILTER_VALIDATE_URL
        )
    ) {
        return null;
    }

    return rtrim($url, '/') . '/';
}


$root =
    ag_login_find_dreamgrid_root();

$loginUrl =
    null;


if ($root !== null) {

    /*
     * PRIMARY SOURCE:
     * Diva.Wifi / DreamGrid LoginURL.
     */

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

    $value =
        ag_login_read_ini_value(
            $wifi,
            'LoginURL'
        );

    if ($value !== null) {

        $loginUrl =
            ag_login_normalize_url(
                $value
            );
    }


    /*
     * FALLBACK:
     * Build the normal DreamGrid :8002 address from
     * Settings.ini if LoginURL is ever unavailable.
     */

    if ($loginUrl === null) {

        $settings =
            $root .
            DIRECTORY_SEPARATOR .
            'Settings.ini';

        $hostKeys = array(
            'DnsName',
            'BaseHostName',
            'ExternalHostName',
            'PublicIP'
        );

        foreach ($hostKeys as $key) {

            $host =
                ag_login_read_ini_value(
                    $settings,
                    $key
                );

            if ($host === null || trim($host) === '') {
                continue;
            }

            $host = trim($host);

            if (
                preg_match(
                    '#^https?://#i',
                    $host
                )
            ) {

                $parts =
                    parse_url($host);

                if (
                    is_array($parts) &&
                    !empty($parts['host'])
                ) {
                    $host = $parts['host'];
                }
            }

            $candidate =
                'http://' .
                $host .
                ':' . ag_dg_robust_port() . '/';

            $loginUrl =
                ag_login_normalize_url(
                    $candidate
                );

            if ($loginUrl !== null) {
                break;
            }
        }
    }
}


if ($loginUrl === null) {

    http_response_code(500);

    echo json_encode(
        array(
            'ok' => false,
            'login_url' => null
        ),
        JSON_UNESCAPED_SLASHES
    );

    return;
}


echo json_encode(
    array(
        'ok' => true,
        'login_url' => $loginUrl
    ),
    JSON_UNESCAPED_SLASHES
);