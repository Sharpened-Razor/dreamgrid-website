<?php
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/bootstrap.php';

ag_require_same_origin_post();
ag_no_cache();

header('Content-Type: application/json; charset=utf-8');

$session = ag_require_admin();

function load_oar_reply(bool $ok, string $message, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        [
            'ok' => $ok,
            $ok ? 'message' : 'error' => $message,
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    exit;
}

function load_oar_ini_value(string $raw, string $key): ?string
{
    $pattern =
        '/^[ \t]*' .
        preg_quote($key, '/') .
        '[ \t]*=[ \t]*(.*?)[ \t]*$/mi';

    if (!preg_match($pattern, $raw, $match)) {
        return null;
    }

    $value = trim((string)$match[1]);

    if (
        strlen($value) >= 2 &&
        (
            ($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
            ($value[0] === "'" && $value[strlen($value) - 1] === "'")
        )
    ) {
        $value = substr($value, 1, -1);
    }

    return trim($value);
}

function load_oar_section_value(string $file, string $section, string $key): ?string
{
    if (!is_file($file)) {
        return null;
    }

    $lines = @file($file, FILE_IGNORE_NEW_LINES);

    if (!is_array($lines)) {
        return null;
    }

    $current = '';

    foreach ($lines as $line) {
        $trimmed = trim((string)$line);

        if ($trimmed === '' || $trimmed[0] === ';' || $trimmed[0] === '#') {
            continue;
        }

        if (preg_match('/^\[([^\]]+)\]$/', $trimmed, $match)) {
            $current = trim((string)$match[1]);
            continue;
        }

        if (strcasecmp($current, $section) !== 0) {
            continue;
        }

        if (
            preg_match(
                '/^' . preg_quote($key, '/') . '\s*=\s*(.*)$/i',
                $trimmed,
                $match
            )
        ) {
            $value = trim((string)$match[1]);

            if (
                strlen($value) >= 2 &&
                (
                    ($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
                    ($value[0] === "'" && $value[strlen($value) - 1] === "'")
                )
            ) {
                $value = substr($value, 1, -1);
            }

            return trim($value);
        }
    }

    return null;
}

function load_oar_unique_files(array $files): array
{
    $result = [];
    $seen = [];

    foreach ($files as $file) {
        if (!is_string($file) || $file === '' || !is_file($file)) {
            continue;
        }

        $real = realpath($file);
        $key = strtolower($real !== false ? $real : $file);

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $result[] = $real !== false ? $real : $file;
    }

    return $result;
}

function load_oar_config_files(string $regionFolderPath, string $regionsRoot): array
{
    $binRoot = dirname(rtrim($regionsRoot, '/\\'));
    $patterns = [
        $regionFolderPath . DIRECTORY_SEPARATOR . '*.ini',
        $regionFolderPath . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . '*.ini',
        $regionFolderPath . DIRECTORY_SEPARATOR . 'config-include' . DIRECTORY_SEPARATOR . '*.ini',
        $binRoot . DIRECTORY_SEPARATOR . '*.ini',
        $binRoot . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . '*.ini',
        $binRoot . DIRECTORY_SEPARATOR . 'config-include' . DIRECTORY_SEPARATOR . '*.ini',
    ];

    $files = [];

    foreach ($patterns as $pattern) {
        foreach (glob($pattern) ?: [] as $file) {
            $files[] = $file;
        }
    }

    return load_oar_unique_files($files);
}

function load_oar_resolve_reference(string $value, array $configFiles): string
{
    $value = trim($value);

    if (!preg_match('/^\$\{([^|}]+)\|([^}]+)\}$/', $value, $match)) {
        return $value;
    }

    $section = trim((string)$match[1]);
    $key = trim((string)$match[2]);

    foreach ($configFiles as $referenceFile) {
        $resolved = load_oar_section_value($referenceFile, $section, $key);

        if (is_string($resolved) && $resolved !== '') {
            return trim($resolved);
        }
    }

    return '';
}

function load_oar_resolve_password(array $configFiles): ?string
{
    foreach ($configFiles as $file) {
        $value = load_oar_section_value($file, 'RemoteAdmin', 'access_password');

        if (!is_string($value) || $value === '') {
            continue;
        }

        $value = load_oar_resolve_reference($value, $configFiles);

        if ($value !== '') {
            return $value;
        }
    }

    return null;
}

function load_oar_port_candidates(string $regionRaw, array $configFiles): array
{
    $values = [];

    foreach (['GroupPort', 'InternalPort'] as $key) {
        $value = load_oar_ini_value($regionRaw, $key);

        if (is_string($value) && preg_match('/^\d+$/', $value)) {
            $values[] = (int)$value;
        }
    }

    foreach ($configFiles as $file) {
        foreach (
            [
                ['Network', 'http_listener_port'],
                ['Const', 'PublicPort'],
            ] as $pair
        ) {
            $value = load_oar_section_value($file, $pair[0], $pair[1]);

            if (is_string($value)) {
                $value = load_oar_resolve_reference($value, $configFiles);
            }

            if (is_string($value) && preg_match('/^\d+$/', $value)) {
                $values[] = (int)$value;
            }
        }
    }

    $result = [];

    foreach ($values as $port) {
        if ($port < 1 || $port > 65535 || in_array($port, $result, true)) {
            continue;
        }

        $result[] = $port;
    }

    return $result;
}

function load_oar_xml_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function load_oar_xmlrpc_request(string $password, string $command): string
{
    return
        '<?xml version="1.0"?>' .
        '<methodCall>' .
        '<methodName>admin_console_command</methodName>' .
        '<params><param><value><struct>' .
        '<member><name>password</name><value><string>' .
        load_oar_xml_escape($password) .
        '</string></value></member>' .
        '<member><name>command</name><value><string>' .
        load_oar_xml_escape($command) .
        '</string></value></member>' .
        '</struct></value></param></params>' .
        '</methodCall>';
}

function load_oar_post_xml(string $url, string $payload): array
{
    if (function_exists('curl_init')) {
        $curl = curl_init($url);

        if ($curl !== false) {
            curl_setopt_array(
                $curl,
                [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 2,
                    CURLOPT_TIMEOUT => 20,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: text/xml',
                        'Content-Length: ' . strlen($payload),
                    ],
                ]
            );

            $body = curl_exec($curl);
            $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            return [
                is_string($body) ? $body : '',
                $status,
                $error,
            ];
        }
    }

    $context = stream_context_create(
        [
            'http' => [
                'method' => 'POST',
                'header' =>
                    "Content-Type: text/xml\r\n" .
                    'Content-Length: ' . strlen($payload) . "\r\n",
                'content' => $payload,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]
    );

    $body = @file_get_contents($url, false, $context);
    $status = 0;

    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $match)) {
            $status = (int)$match[1];
            break;
        }
    }

    return [
        is_string($body) ? $body : '',
        $status,
        $body === false ? 'HTTP request failed.' : '',
    ];
}

function load_oar_response_ok(int $status, string $body): bool
{
    if ($status < 200 || $status >= 300) {
        return false;
    }

    if ($body === '' || stripos($body, '<methodResponse') === false) {
        return false;
    }

    if (stripos($body, '<fault>') !== false) {
        return false;
    }

    if (
        preg_match(
            '/<name>success<\/name>.*?<boolean>0<\/boolean>/is',
            $body
        )
    ) {
        return false;
    }

    return true;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    load_oar_reply(false, 'POST required.', 405);
}

foreach (['region', 'uuid', 'group', 'oar', 'url'] as $field) {
    if (isset($_POST[$field]) && !is_string($_POST[$field])) load_oar_reply(false, 'Invalid OAR request field.', 400);
}

$regionName = trim((string)($_POST['region'] ?? ''));
$regionUuid = trim((string)($_POST['uuid'] ?? ''));
$regionGroup = trim((string)($_POST['group'] ?? ''));
$oarName = trim((string)($_POST['oar'] ?? ''));
$oarUrl = trim((string)($_POST['url'] ?? ''));

if (
    $regionName === '' &&
    $regionUuid === ''
) {
    load_oar_reply(
        false,
        'Region name or UUID is required.',
        400
    );
}

if (
    $regionUuid !== '' &&
    preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
        $regionUuid
    ) !== 1
) {
    load_oar_reply(
        false,
        'Region UUID is invalid.',
        400
    );
}

if (
    $regionGroup !== '' &&
    (
        strpos($regionGroup, '/') !== false ||
        strpos($regionGroup, '\\') !== false ||
        strpos($regionGroup, '..') !== false
    )
) {
    load_oar_reply(
        false,
        'DreamGrid region group is invalid.',
        400
    );
}

if ($oarUrl === '') {
    load_oar_reply(false, 'OAR URL is required.', 400);
}

if (
    preg_match('/[\r\n"]/', $oarUrl) ||
    filter_var($oarUrl, FILTER_VALIDATE_URL) === false
) {
    load_oar_reply(false, 'The selected OAR URL is invalid.', 400);
}

$parts = parse_url($oarUrl);

if (
    !is_array($parts) ||
    strcasecmp((string)($parts['scheme'] ?? ''), 'https') !== 0 ||
    strcasecmp((string)($parts['host'] ?? ''), 'outworldz.com') !== 0
) {
    load_oar_reply(false, 'Only DreamGrid Outworldz OAR catalogue URLs are allowed.', 400);
}

$regionsRoot = ag_dg_regions_root();

if (!is_string($regionsRoot) || $regionsRoot === '' || !is_dir($regionsRoot)) {
    load_oar_reply(false, 'DreamGrid regions folder is unavailable.', 500);
}

$files = [];

if ($regionGroup !== '') {

    $groupRegionPath =
        rtrim(
            $regionsRoot,
            '/\\'
        ) .
        DIRECTORY_SEPARATOR .
        $regionGroup .
        DIRECTORY_SEPARATOR .
        'Region';

    if (is_dir($groupRegionPath)) {

        $files =
            glob(
                $groupRegionPath .
                DIRECTORY_SEPARATOR .
                '*.ini'
            ) ?: [];
    }
}

if ($files === []) {

    $files =
        glob(
            rtrim(
                $regionsRoot,
                '/\\'
            ) .
            DIRECTORY_SEPARATOR .
            '*' .
            DIRECTORY_SEPARATOR .
            'Region' .
            DIRECTORY_SEPARATOR .
            '*.ini'
        ) ?: [];
}

$iniPath = null;
$regionRaw = null;

foreach ($files as $candidate) {

    $raw =
        @file_get_contents(
            $candidate
        );

    if (!is_string($raw)) {
        continue;
    }

    $candidateUuid =
        load_oar_ini_value(
            $raw,
            'RegionUUID'
        ) ?? '';

    if (
        $regionUuid !== '' &&
        $candidateUuid !== '' &&
        strcasecmp(
            $candidateUuid,
            $regionUuid
        ) === 0
    ) {
        $iniPath = $candidate;
        $regionRaw = $raw;
        break;
    }

    if (
        !preg_match(
            '/^[ \t]*\[([^\]]+)\][ \t]*$/m',
            $raw,
            $match
        )
    ) {
        continue;
    }

    $candidateName =
        trim(
            (string)$match[1]
        );

    if (
        $regionUuid === '' &&
        $regionName !== '' &&
        strcasecmp(
            $candidateName,
            $regionName
        ) === 0
    ) {
        $iniPath = $candidate;
        $regionRaw = $raw;
        break;
    }
}

if (
    $iniPath === null ||
    !is_string($regionRaw)
) {

    load_oar_reply(
        false,
        'Region INI was not found for "' .
        $regionName .
        '"' .
        (
            $regionUuid !== ''
                ? ' UUID ' . $regionUuid
                : ''
        ) .
        (
            $regionGroup !== ''
                ? ' group "' . $regionGroup . '"'
                : ''
        ) .
        '. Scanned ' .
        count($files) .
        ' INI file(s).',
        404
    );
}

$regionFolderPath = dirname(dirname($iniPath));
$configFiles = load_oar_config_files($regionFolderPath, $regionsRoot);
$password = load_oar_resolve_password($configFiles);

if (!is_string($password) || $password === '') {
    load_oar_reply(false, 'DreamGrid RemoteAdmin access_password could not be resolved.', 500);
}

$ports = load_oar_port_candidates($regionRaw, $configFiles);

if ($ports === []) {
    load_oar_reply(false, 'No usable DreamGrid/OpenSim region command port was found.', 500);
}

$command = 'load oar --force-terrain "' . $oarUrl . '"';
$payload = load_oar_xmlrpc_request($password, $command);
$errors = [];

foreach ($ports as $port) {
    $url = ag_web_local_base($port) . '/';
    [$body, $status, $error] = load_oar_post_xml($url, $payload);

    if (load_oar_response_ok($status, $body)) {
        load_oar_reply(
            true,
            'DreamGrid started loading ' .
            ($oarName !== '' ? $oarName : 'the selected OAR') .
            ' onto ' .
            $regionName .
            '.'
        );
    }

    if ($status === 0) {
        load_oar_reply(false, 'Load OAR outcome is unconfirmed. Check the selected region before retrying; no further port was attempted.', 502);
    }
    $errors[] =
        'port ' . $port .
        ' HTTP ' . $status .
        ($error !== '' ? ' ' . $error : '');
}

load_oar_reply(
    false,
    'DreamGrid/OpenSim did not accept the Load OAR command (' .
    implode('; ', $errors) .
    ').',
    502
);
