<?php

require_once __DIR__ . '/dreamgrid-env.php';
/*
 * Grid - PRIVATE ROBUST SERVICE HELPER
 *
 * Reads the current DreamGrid Robust.HG.ini so the Apache/PHP site
 * follows DreamGrid's private Robust address instead of hard-coding it.
 */

function ag_robust_ini_path(): string
{
    return
        ag_dg_robust_ini()
        ?? '';
}

function ag_robust_ini_value(
    string $section,
    string $key
): ?string {
    $path = ag_robust_ini_path();

    $lines = @file(
        $path,
        FILE_IGNORE_NEW_LINES
    );

    if ($lines === false) {
        return null;
    }

    $inSection = false;

    foreach ($lines as $rawLine) {
        $line = trim((string)$rawLine);

        if ($line === '') {
            continue;
        }

        if (
            str_starts_with($line, ';') ||
            str_starts_with($line, '#')
        ) {
            continue;
        }

        if (
            preg_match(
                '/^\[(.+)\]$/',
                $line,
                $sectionMatch
            )
        ) {
            $inSection =
                strcasecmp(
                    trim((string)$sectionMatch[1]),
                    $section
                ) === 0;

            continue;
        }

        if (!$inSection) {
            continue;
        }

        if (
            !preg_match(
                '/^\s*' .
                preg_quote($key, '/') .
                '\s*=\s*(.*?)\s*$/i',
                (string)$rawLine,
                $valueMatch
            )
        ) {
            continue;
        }

        $value = trim((string)$valueMatch[1]);

        /*
         * Strip only whitespace-separated inline INI comments.
         * This leaves URLs such as http://192.168.0.21 untouched.
         */
        $value =
            preg_replace(
                '/\s+[;#].*$/',
                '',
                $value
            ) ?? $value;

        $value = trim($value);

        $length = strlen($value);

        if ($length >= 2) {
            $first = $value[0];
            $last = $value[$length - 1];

            if (
                ($first === '"' && $last === '"') ||
                ($first === "'" && $last === "'")
            ) {
                $value =
                    substr(
                        $value,
                        1,
                        $length - 2
                    );
            }
        }

        return trim($value);
    }

    return null;
}

function ag_robust_module_ini_path(): string
{
    $folder =
        ag_dg_path(
            'Opensim',
            'bin',
            'config-addon-robust'
        );

    if (
        $folder === null ||
        !is_dir($folder)
    ) {
        return '';
    }

    $matches =
        glob(
            $folder .
            DIRECTORY_SEPARATOR .
            '*UserAccountService*.ini'
        );

    if (
        !is_array($matches) ||
        count($matches) === 0
    ) {
        return '';
    }

    natcasesort($matches);

    $matches =
        array_values($matches);

    return
        (string)$matches[0];
}

function ag_ini_value_from_path(
    string $path,
    string $section,
    string $key
): ?string {
    $lines = @file(
        $path,
        FILE_IGNORE_NEW_LINES
    );

    if ($lines === false) {
        return null;
    }

    $inSection = false;

    foreach ($lines as $rawLine) {
        $line = trim((string)$rawLine);

        if ($line === '') {
            continue;
        }

        if (
            str_starts_with($line, ';') ||
            str_starts_with($line, '#')
        ) {
            continue;
        }

        if (
            preg_match(
                '/^\[(.+)\]$/',
                $line,
                $sectionMatch
            )
        ) {
            $inSection =
                strcasecmp(
                    trim((string)$sectionMatch[1]),
                    $section
                ) === 0;

            continue;
        }

        if (!$inSection) {
            continue;
        }

        if (
            !preg_match(
                '/^\s*' .
                preg_quote($key, '/') .
                '\s*=\s*(.*?)\s*$/i',
                (string)$rawLine,
                $valueMatch
            )
        ) {
            continue;
        }

        $value = trim((string)$valueMatch[1]);

        $value =
            preg_replace(
                '/\s+[;#].*$/',
                '',
                $value
            ) ?? $value;

        $value = trim($value);

        $length = strlen($value);

        if ($length >= 2) {
            $first = $value[0];
            $last = $value[$length - 1];

            if (
                ($first === '"' && $last === '"') ||
                ($first === "'" && $last === "'")
            ) {
                $value =
                    substr(
                        $value,
                        1,
                        $length - 2
                    );
            }
        }

        return trim($value);
    }

    return null;
}

function ag_robust_create_user_enabled(): bool
{
    $main =
        ag_robust_ini_value(
            'UserAccountService',
            'AllowCreateUser'
        );

    if (strtolower((string)$main) === 'true') {
        return true;
    }

    $module =
        ag_ini_value_from_path(
            ag_robust_module_ini_path(),
            'UserAccountService',
            'AllowCreateUser'
        );

    return strtolower((string)$module) === 'true';
}

function ag_robust_private_base(): ?string
{
    $url = ag_robust_ini_value('Const', 'PrivURL');
    $port = ag_robust_ini_value('Const', 'PrivatePort');

    if ($url === null || $port === null) {
        return null;
    }

    if (!preg_match('/^\d{1,5}$/', $port)) {
        return null;
    }

    $portNumber = (int)$port;

    if ($portNumber < 1 || $portNumber > 65535) {
        return null;
    }

    return rtrim($url, '/') . ':' . $portNumber;
}

function ag_robust_accounts_post(array $fields): array
{
    $base = ag_robust_private_base();

    if ($base === null) {
        return [
            'ok' => false,
            'http_code' => 0,
            'body' => '',
            'error' => 'Private Robust address could not be read.',
        ];
    }

    $url = $base . '/accounts';

    $payload = http_build_query(
        $fields,
        '',
        '&',
        PHP_QUERY_RFC3986
    );

    if (function_exists('curl_init')) {
        $ch = curl_init($url);

        curl_setopt_array(
            $ch,
            [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Expect:',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_FOLLOWLOCATION => false,
            ]
        );

        $body = curl_exec($ch);

        $error =
            $body === false
                ? (string)curl_error($ch)
                : '';

        $httpCode = (int)curl_getinfo(
            $ch,
            CURLINFO_RESPONSE_CODE
        );

        curl_close($ch);

        return [
            'ok' =>
                $body !== false &&
                $httpCode >= 200 &&
                $httpCode < 300,
            'http_code' => $httpCode,
            'body' => $body === false ? '' : (string)$body,
            'error' => $error,
        ];
    }

    $context = stream_context_create(
        [
            'http' => [
                'method' => 'POST',
                'header' =>
                    "Content-Type: application/x-www-form-urlencoded\r\n" .
                    "Connection: close\r\n",
                'content' => $payload,
                'timeout' => 12,
                'ignore_errors' => true,
            ],
        ]
    );

    $body = @file_get_contents(
        $url,
        false,
        $context
    );

    $httpCode = 0;

    if (isset($http_response_header[0])) {
        if (
            preg_match(
                '/\s(\d{3})\s/',
                (string)$http_response_header[0],
                $match
            )
        ) {
            $httpCode = (int)$match[1];
        }
    }

    return [
        'ok' =>
            $body !== false &&
            $httpCode >= 200 &&
            $httpCode < 300,
        'http_code' => $httpCode,
        'body' => $body === false ? '' : (string)$body,
        'error' =>
            $body === false
                ? 'Private Robust HTTP request failed.'
                : '',
    ];
}

function ag_uuid_v4(): string
{
    $bytes = random_bytes(16);

    $bytes[6] =
        chr(
            (ord($bytes[6]) & 0x0f) | 0x40
        );

    $bytes[8] =
        chr(
            (ord($bytes[8]) & 0x3f) | 0x80
        );

    $hex = bin2hex($bytes);

    return sprintf(
        '%s-%s-%s-%s-%s',
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20, 12)
    );
}

function ag_robust_create_user(
    string $principalId,
    string $firstName,
    string $lastName,
    string $password,
    string $email
): array {
    if (!ag_robust_create_user_enabled()) {
        return [
            'ok' => false,
            'http_code' => 0,
            'body' => '',
            'error' => 'Robust AllowCreateUser is not enabled.',
        ];
    }

    return ag_robust_accounts_post(
        [
            'METHOD' => 'createuser',
            'ScopeID' =>
                '00000000-0000-0000-0000-000000000000',
            'PrincipalID' => $principalId,
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'Password' => $password,
            'Email' => $email,
        ]
    );
}