<?php

require_once __DIR__ . '/core/dreamgrid-env.php';

/*
 * ============================================================
 * Grid - PROFILE LIBRARY V1.2
 * ============================================================
 */

function auProfileSession(): array
{
    require_once __DIR__ . '/login/session.php';

    $session = dreamGridCurrentSession();

    if (
        !is_array($session)
        ||
        empty($session['principalId'])
    ) {
        header('Location: /');
        exit;
    }

    $uuid = strtolower(trim((string)$session['principalId']));

    if (
        !preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $uuid
        )
    ) {
        http_response_code(403);
        exit('Invalid signed Grid session.');
    }

    $session['principalId'] = $uuid;

    return $session;
}


function auProfileEsc(?string $value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


function auProfileIsUuid(string $uuid): bool
{
    return (
        preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
            $uuid
        ) === 1
    );
}


function auProfileIniSection(
    string $text,
    string $section
): string
{
    $name = preg_quote($section, '/');

    if (
        !preg_match(
            '/^\s*\[' . $name . '\]\s*(.*?)(?=^\s*\[[^\]]+\]|\z)/msi',
            $text,
            $match
        )
    ) {
        return '';
    }

    return (string)$match[1];
}


function auProfileIniValue(
    string $section,
    string $key
): string
{
    foreach (
        preg_split('/\r\n|\r|\n/', $section)
        as
        $line
    ) {
        $line = trim((string)$line);

        if (
            $line === ''
            ||
            str_starts_with($line, ';')
            ||
            str_starts_with($line, '#')
        ) {
            continue;
        }

        if (
            preg_match(
                '/^' .
                preg_quote($key, '/') .
                '\s*=\s*(.*)$/i',
                $line,
                $match
            )
        ) {
            return trim(
                trim((string)$match[1]),
                "\"'"
            );
        }
    }

    return '';
}


function auProfileDbConfig(): array
{
    static $config = null;

    if (is_array($config)) {
        return $config;
    }

    $ini =
        ag_dg_robust_ini();

    if (
        !is_string($ini) ||
        trim($ini) === ''
    ) {
        throw new RuntimeException(
            'Cannot resolve Robust.HG.ini.'
        );
    }

    $text = @file_get_contents($ini);

    if ($text === false) {
        throw new RuntimeException(
            'Cannot read Robust.HG.ini.'
        );
    }

    $section =
        auProfileIniSection(
            $text,
            'DatabaseService'
        );

    $connection =
        auProfileIniValue(
            $section,
            'ConnectionString'
        );

    if ($connection === '') {
        throw new RuntimeException(
            'Robust database ConnectionString was not found.'
        );
    }

    $parts = [];

    foreach (
        explode(';', $connection)
        as
        $part
    ) {
        if (!str_contains($part, '=')) {
            continue;
        }

        [$key, $value] =
            array_pad(
                explode('=', $part, 2),
                2,
                ''
            );

        $parts[
            strtolower(trim($key))
        ] = trim($value);
    }

    $read =
        static function(array $keys) use ($parts): string {

            foreach ($keys as $key) {

                $key = strtolower($key);

                if (array_key_exists($key, $parts)) {
                    return (string)$parts[$key];
                }
            }

            return '';
        };

    $config = [
        'host' =>
            $read([
                'Data Source',
                'Server',
                'Host'
            ]),

        'database' =>
            $read([
                'Database',
                'Initial Catalog'
            ]),

        'user' =>
            $read([
                'User ID',
                'Uid',
                'User'
            ]),

        'password' =>
            $read([
                'Password',
                'Pwd'
            ]),

        'port' =>
            (int)(
                $read(['Port']) ?: ag_web_database_port('region')
            )
    ];

    if (
        $config['host'] === ''
        ||
        $config['database'] === ''
        ||
        $config['user'] === ''
    ) {
        throw new RuntimeException(
            'Robust database configuration could not be parsed.'
        );
    }

    return $config;
}


function auProfileDb(): mysqli
{
    static $db = null;

    if ($db instanceof mysqli) {
        return $db;
    }

    $cfg = auProfileDbConfig();

    mysqli_report(
        MYSQLI_REPORT_ERROR |
        MYSQLI_REPORT_STRICT
    );

    $db = new mysqli(
        $cfg['host'],
        $cfg['user'],
        $cfg['password'],
        $cfg['database'],
        $cfg['port']
    );

    $db->set_charset('utf8mb4');

    return $db;
}


function auProfileAccount(
    mysqli $db,
    string $uuid
): array
{
    $stmt =
        $db->prepare(
            '
            SELECT
                PrincipalID,
                FirstName,
                LastName,
                UserLevel
            FROM UserAccounts
            WHERE PrincipalID = ?
            LIMIT 1
            '
        );

    $stmt->bind_param('s', $uuid);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    if (!is_array($row)) {
        throw new RuntimeException(
            'Signed avatar was not found in UserAccounts.'
        );
    }

    return $row;
}


function auProfileDefault(
    string $uuid
): array
{
    return [
        'useruuid' =>
            $uuid,

        'profilePartner' =>
            '00000000-0000-0000-0000-000000000000',

        'profileAllowPublish' =>
            "\x00",

        'profileMaturePublish' =>
            "\x00",

        'profileURL' =>
            '',

        'profileWantToMask' =>
            0,

        'profileWantToText' =>
            '',

        'profileSkillsMask' =>
            0,

        'profileSkillsText' =>
            '',

        'profileLanguages' =>
            '',

        'profileImage' =>
            '00000000-0000-0000-0000-000000000000',

        'profileAboutText' =>
            '',

        'profileFirstImage' =>
            '00000000-0000-0000-0000-000000000000',

        'profileFirstText' =>
            ''
    ];
}


function auProfileGet(
    mysqli $db,
    string $uuid
): array
{
    $stmt =
        $db->prepare(
            '
            SELECT
                useruuid,
                profilePartner,
                profileAllowPublish,
                profileMaturePublish,
                profileURL,
                profileWantToMask,
                profileWantToText,
                profileSkillsMask,
                profileSkillsText,
                profileLanguages,
                profileImage,
                profileAboutText,
                profileFirstImage,
                profileFirstText
            FROM userprofile
            WHERE useruuid = ?
            LIMIT 1
            '
        );

    $stmt->bind_param('s', $uuid);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    if (!is_array($row)) {
        return auProfileDefault($uuid);
    }

    return $row;
}


function auProfileBool($value): bool
{
    if (
        is_string($value)
        &&
        strlen($value) > 0
    ) {
        return ord($value[0]) !== 0;
    }

    return (bool)$value;
}


function auProfilePartner(
    mysqli $db,
    string $uuid
): string
{
    if (
        !auProfileIsUuid($uuid)
        ||
        strtolower($uuid) ===
        '00000000-0000-0000-0000-000000000000'
    ) {
        return 'NONE';
    }

    $stmt =
        $db->prepare(
            '
            SELECT
                FirstName,
                LastName
            FROM UserAccounts
            WHERE PrincipalID = ?
            LIMIT 1
            '
        );

    $stmt->bind_param('s', $uuid);
    $stmt->execute();

    $row =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    if (!is_array($row)) {
        return 'IN-WORLD PARTNER';
    }

    return trim(
        (string)$row['FirstName'] .
        ' ' .
        (string)$row['LastName']
    );
}


function auProfilePrivatePort(): int
{
    return ag_dg_private_robust_port();
}

function auProfileFetchAsset(
    string $uuid
): ?string
{
    if (!auProfileIsUuid($uuid)) {
        return null;
    }

    /*
     * Real OpenSim Robust asset endpoint.
     */

    $url =
        ag_web_local_base(auProfilePrivatePort()) .
        '/assets/' .
        rawurlencode($uuid) .
        '/data';


    if (function_exists('curl_init')) {

        $curl = curl_init($url);

        curl_setopt_array(
            $curl,
            [
                CURLOPT_RETURNTRANSFER =>
                    true,

                CURLOPT_CONNECTTIMEOUT =>
                    3,

                CURLOPT_TIMEOUT =>
                    8,

                CURLOPT_FAILONERROR =>
                    true
            ]
        );

        $data = curl_exec($curl);

        $code =
            (int)curl_getinfo(
                $curl,
                CURLINFO_HTTP_CODE
            );

        curl_close($curl);

        if (
            $data !== false
            &&
            $code === 200
        ) {
            return (string)$data;
        }
    }


    $context =
        stream_context_create([
            'http' => [
                'timeout' =>
                    8,

                'ignore_errors' =>
                    false
            ]
        ]);


    $data =
        @file_get_contents(
            $url,
            false,
            $context
        );


    if ($data === false) {
        return null;
    }


    return (string)$data;
}