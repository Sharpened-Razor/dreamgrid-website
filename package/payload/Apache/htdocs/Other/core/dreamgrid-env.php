<?php
require_once __DIR__.'/php-compatibility.php';

/*
 * ============================================================
 * DREAMGRID PORTABLE ENVIRONMENT HELPER
 * ============================================================
 *
 * No machine-specific DreamGrid path is stored here.
 *
 * The DreamGrid root is discovered by walking upward until
 * Settings.ini is found.
 *
 * Designed for:
 *     Apache\htdocs\Other
 *
 * and portable DreamGrid installations on other drives/folders.
 * ============================================================
 */

require_once __DIR__ . '/grid-branding.php';
require_once __DIR__ . '/website-runtime.php';


if (!function_exists('ag_dg_root')) {

    function ag_dg_root(): ?string
    {
        return ag_find_dreamgrid_root();
    }
}


if (!function_exists('ag_dg_join')) {

    function ag_dg_join(
        ?string $root,
        string ...$parts
    ): ?string {

        if ($root === null || $root === '') {
            return null;
        }

        $path = rtrim(
            $root,
            "\\/"
        );

        foreach ($parts as $part) {

            $part = trim(
                $part,
                "\\/"
            );

            if ($part === '') {
                continue;
            }

            $path .=
                DIRECTORY_SEPARATOR .
                $part;
        }

        return $path;
    }
}


if (!function_exists('ag_dg_path')) {

    function ag_dg_path(
        string ...$parts
    ): ?string {

        return ag_dg_join(
            ag_dg_root(),
            ...$parts
        );
    }
}


if (!function_exists('ag_dg_settings_file')) {

    function ag_dg_settings_file(): ?string
    {
        return ag_dg_path(
            'Settings.ini'
        );
    }
}


if (!function_exists('ag_dg_setting')) {

    function ag_dg_setting(
        string $key
    ): ?string {

        $settings =
            ag_dg_settings_file();

        if (
            $settings === null ||
            !is_file($settings)
        ) {
            return null;
        }

        return ag_read_grid_setting(
            $settings,
            $key
        );
    }
}


if (!function_exists('ag_dg_first_setting')) {

    function ag_dg_first_setting(
        array $keys
    ): ?string {

        foreach ($keys as $key) {

            $value =
                ag_dg_setting(
                    (string)$key
                );

            if (
                $value !== null &&
                trim($value) !== ''
            ) {
                return trim($value);
            }
        }

        return null;
    }
}


if (!function_exists('ag_dg_hostname')) {

    function ag_dg_hostname(): string
    {
        $host=ag_web_setting(array('ExternalHostName','BaseHostName','DnsName'));
        if(!$host){$base=ag_web_ini_value(ag_web_robust_ini(),'Const','BaseURL');$host=$base ? parse_url($base,PHP_URL_HOST) : null;}
        if(!$host){$request=$_SERVER['HTTP_HOST'] ?? ''; $host=$request ? parse_url('http://'.$request,PHP_URL_HOST) : gethostname();}
        if(!is_string($host)||$host===''||preg_match('/[\s\/\\\\?#@]/',$host))throw new RuntimeException('Grid hostname is not configured.');
        return trim($host,'[]');
}
}


if (!function_exists('ag_dg_robust_port')) {

    function ag_dg_robust_port(): int
    {
        return ag_web_port(array('RobustPort','RobustPortWas'),array('PublicPort','http_listener_port'));
}
}


if (!function_exists('ag_dg_apache_port')) {

    function ag_dg_apache_port(): int
    {
        try{return ag_web_port(array('ApachePort','ApachePortWas'),array('ApachePort'));}catch(RuntimeException $e){$port=(int)($_SERVER['SERVER_PORT'] ?? 0);if($port>=1&&$port<=65535)return $port;throw $e;}
}
}


if (!function_exists('ag_dg_login_url')) {

    function ag_dg_login_url(): string
    {
        $base=ag_web_ini_value(ag_web_robust_ini(),'Const','BaseURL');$scheme=$base ? parse_url($base,PHP_URL_SCHEME) : 'http';
        return $scheme.'://'.ag_web_authority(ag_dg_hostname(),ag_dg_robust_port()).'/';
}
}


if (!function_exists('ag_dg_hop_host')) {

    function ag_dg_hop_host(): string
    {
        return ag_web_authority(ag_dg_hostname(),ag_dg_robust_port());
}
}


if (!function_exists('ag_dg_opensim_bin')) {

    function ag_dg_opensim_bin(): ?string
    {
        return ag_dg_path(
            'Opensim',
            'bin'
        );
    }
}


if (!function_exists('ag_dg_regions_root')) {

    function ag_dg_regions_root(): ?string
    {
        return ag_dg_path(
            'Opensim',
            'bin',
            'Regions'
        );
    }
}


if (!function_exists('ag_dg_robust_ini')) {

    function ag_dg_robust_ini(): ?string
    {
        return ag_dg_path(
            'Opensim',
            'bin',
            'Robust.HG.ini'
        );
    }
}


if (!function_exists('ag_dg_private_robust_port')) {

    function ag_dg_private_robust_port(): int
    {
        return ag_web_port(array('PrivateRobustPort'),array('PrivatePort'));
}
}


if (!function_exists('ag_dg_private_robust_base')) {

    function ag_dg_private_robust_base(): string
    {
        return ag_web_local_base(ag_dg_private_robust_port());
}
}

if (!function_exists('ag_dg_autobackup_root')) {

    function ag_dg_autobackup_root(): ?string
    {
        return ag_dg_path(
            'Autobackup'
        );
    }
}


if (!function_exists('ag_dg_apache_htdocs')) {

    function ag_dg_apache_htdocs(): ?string
    {
        return ag_dg_path(
            'Apache',
            'htdocs'
        );
    }
}


/*
 * ============================================================
 * DREAMGRID LOCAL DIAGNOSTICS / CONTROL SERVICE
 * ============================================================
 *
 * DreamGrid normally exposes its local control/diagnostic
 * local diagnostics service.
 *
 * If a future DreamGrid Settings.ini exposes a diagnostics
 * port value, use it. Otherwise retain DreamGrid's established
 * local port 8001.
 * ============================================================
 */

if (!function_exists('ag_dg_diagnostics_port')) {

    function ag_dg_diagnostics_port(): int
    {
        return ag_web_port(array('DiagnosticPort','DiagnosticsPort','DiagnosticsPortWas'),array('DiagnosticsPort'));
}
}


if (!function_exists('ag_dg_diagnostics_base')) {

    function ag_dg_diagnostics_base(): string
    {
        return ag_web_local_base(ag_dg_diagnostics_port());
}
}
/*
 * ============================================================
 * PORTABLE WINDOWS CONTROL HELPERS
 * ============================================================
 */

if (!function_exists('ag_dg_web_control_root')) {

    function ag_dg_web_control_root(): ?string
    {
        return ag_dg_path(
            '_WEB_CONTROL'
        );
    }
}


if (!function_exists('ag_dg_windows_root')) {

    function ag_dg_windows_root(): ?string
    {
        foreach (
            [
                'SystemRoot',
                'WINDIR'
            ]
            as $key
        ) {

            $value =
                getenv($key);

            if (
                is_string($value) &&
                trim($value) !== '' &&
                is_dir($value)
            ) {

                return rtrim(
                    $value,
                    "\\/"
                );
            }
        }

        return null;
    }
}


if (!function_exists('ag_dg_windows_system_executable')) {

    function ag_dg_windows_system_executable(
        string $name
    ): string {

        $root =
            ag_dg_windows_root();

        if ($root !== null) {

            $candidate =
                $root .
                DIRECTORY_SEPARATOR .
                'System32' .
                DIRECTORY_SEPARATOR .
                $name;

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $name;
    }
}


if (!function_exists('ag_dg_schtasks_exe')) {

    function ag_dg_schtasks_exe(): string
    {
        return ag_dg_windows_system_executable(
            'schtasks.exe'
        );
    }
}

if (!function_exists('ag_dg_find_executable')) {

    function ag_dg_find_executable(
        string $name
    ): ?string {

        $name =
            trim($name);

        if ($name === '') {
            return null;
        }


        $path =
            (string)getenv('PATH');

        if ($path !== '') {

            foreach (
                explode(
                    PATH_SEPARATOR,
                    $path
                )
                as
                $directory
            ) {

                $directory =
                    trim(
                        $directory,
                        " \t\n\r\0\x0B\""
                    );

                if ($directory === '') {
                    continue;
                }

                $candidate =
                    rtrim(
                        $directory,
                        "\\/"
                    ) .
                    DIRECTORY_SEPARATOR .
                    $name;

                if (!is_file($candidate)) {
                    continue;
                }

                $real =
                    realpath($candidate);

                return
                    $real !== false
                        ? $real
                        : $candidate;
            }
        }


        $programRoots = [];

        foreach (
            [
                'ProgramFiles',
                'ProgramW6432',
                'ProgramFiles(x86)',
            ]
            as
            $environmentName
        ) {

            $root =
                trim(
                    (string)getenv(
                        $environmentName
                    )
                );

            if (
                $root !== '' &&
                !in_array(
                    $root,
                    $programRoots,
                    true
                )
            ) {
                $programRoots[] =
                    $root;
            }
        }


        $lowerName =
            strtolower($name);


        if ($lowerName === 'dotnet.exe') {

            foreach (
                $programRoots
                as
                $root
            ) {

                $candidate =
                    $root .
                    DIRECTORY_SEPARATOR .
                    'dotnet' .
                    DIRECTORY_SEPARATOR .
                    'dotnet.exe';

                if (!is_file($candidate)) {
                    continue;
                }

                $real =
                    realpath($candidate);

                return
                    $real !== false
                        ? $real
                        : $candidate;
            }
        }


        if ($lowerName === 'pwsh.exe') {

            foreach (
                $programRoots
                as
                $root
            ) {

                $pattern =
                    $root .
                    DIRECTORY_SEPARATOR .
                    'PowerShell' .
                    DIRECTORY_SEPARATOR .
                    '*' .
                    DIRECTORY_SEPARATOR .
                    'pwsh.exe';

                $matches =
                    glob($pattern);

                if (!is_array($matches)) {
                    continue;
                }

                rsort(
                    $matches,
                    SORT_NATURAL
                );

                foreach (
                    $matches
                    as
                    $candidate
                ) {

                    if (!is_file($candidate)) {
                        continue;
                    }

                    $real =
                        realpath($candidate);

                    return
                        $real !== false
                            ? $real
                            : $candidate;
                }
            }
        }


        return null;
    }
}


if (!function_exists('ag_dg_dotnet_exe')) {

    function ag_dg_dotnet_exe(): string
    {
        return
            ag_dg_find_executable(
                'dotnet.exe'
            ) ??
            '';
    }
}


if (!function_exists('ag_dg_pwsh_exe')) {

    function ag_dg_pwsh_exe(): string
    {
        return
            ag_dg_find_executable(
                'pwsh.exe'
            ) ??
            '';
    }
}


/*
 * Portable website timezone.
 *
 * DreamGrid runs on Windows, while PHP date_default_timezone_set()
 * requires an IANA timezone name.  Windows exposes a Windows timezone
 * identifier through tzutil /g, so translate that identifier here.
 *
 * An administrator may explicitly override detection by defining the
 * AG_TIMEZONE environment variable to a valid PHP/IANA timezone.
 */
if (!function_exists('ag_dg_timezone')) {

    function ag_dg_timezone(): string
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }


        /*
         * Optional explicit override.
         */
        $override = getenv('AG_TIMEZONE');

        if (is_string($override)) {

            $override = trim($override);

            if (
                $override !== '' &&
                in_array(
                    $override,
                    timezone_identifiers_list(),
                    true
                )
            ) {
                $cached = $override;

                return $cached;
            }
        }


        /*
         * Windows timezone ID -> IANA timezone.
         *
         * These mappings use representative IANA zones for the
         * corresponding Windows timezone rules.
         */
        $windowsMap = [

            'Dateline Standard Time'
                => 'Etc/GMT+12',

            'UTC-11'
                => 'Etc/GMT+11',

            'Aleutian Standard Time'
                => 'America/Adak',

            'Hawaiian Standard Time'
                => 'Pacific/Honolulu',

            'Marquesas Standard Time'
                => 'Pacific/Marquesas',

            'Alaskan Standard Time'
                => 'America/Anchorage',

            'UTC-09'
                => 'Etc/GMT+9',

            'Pacific Standard Time (Mexico)'
                => 'America/Tijuana',

            'UTC-08'
                => 'Etc/GMT+8',

            'Pacific Standard Time'
                => 'America/Los_Angeles',

            'US Mountain Standard Time'
                => 'America/Phoenix',

            'Mountain Standard Time (Mexico)'
                => 'America/Chihuahua',

            'Mountain Standard Time'
                => 'America/Denver',

            'Yukon Standard Time'
                => 'America/Whitehorse',

            'Central America Standard Time'
                => 'America/Guatemala',

            'Central Standard Time'
                => 'America/Chicago',

            'Easter Island Standard Time'
                => 'Pacific/Easter',

            'Central Standard Time (Mexico)'
                => 'America/Mexico_City',

            'Canada Central Standard Time'
                => 'America/Regina',

            'SA Pacific Standard Time'
                => 'America/Bogota',

            'Eastern Standard Time (Mexico)'
                => 'America/Cancun',

            'Eastern Standard Time'
                => 'America/New_York',

            'Haiti Standard Time'
                => 'America/Port-au-Prince',

            'Cuba Standard Time'
                => 'America/Havana',

            'US Eastern Standard Time'
                => 'America/Indianapolis',

            'Turks And Caicos Standard Time'
                => 'America/Grand_Turk',

            'Paraguay Standard Time'
                => 'America/Asuncion',

            'Atlantic Standard Time'
                => 'America/Halifax',

            'Venezuela Standard Time'
                => 'America/Caracas',

            'Central Brazilian Standard Time'
                => 'America/Cuiaba',

            'SA Western Standard Time'
                => 'America/La_Paz',

            'Pacific SA Standard Time'
                => 'America/Santiago',

            'Newfoundland Standard Time'
                => 'America/St_Johns',

            'Tocantins Standard Time'
                => 'America/Araguaina',

            'E. South America Standard Time'
                => 'America/Sao_Paulo',

            'SA Eastern Standard Time'
                => 'America/Cayenne',

            'Argentina Standard Time'
                => 'America/Buenos_Aires',

            'Greenland Standard Time'
                => 'America/Nuuk',

            'Montevideo Standard Time'
                => 'America/Montevideo',

            'Magallanes Standard Time'
                => 'America/Punta_Arenas',

            'Saint Pierre Standard Time'
                => 'America/Miquelon',

            'Bahia Standard Time'
                => 'America/Bahia',

            'UTC-02'
                => 'Etc/GMT+2',

            'Azores Standard Time'
                => 'Atlantic/Azores',

            'Cape Verde Standard Time'
                => 'Atlantic/Cape_Verde',

            'UTC'
                => 'UTC',

            'GMT Standard Time'
                => 'Europe/London',

            'Greenwich Standard Time'
                => 'Atlantic/Reykjavik',

            'Sao Tome Standard Time'
                => 'Africa/Sao_Tome',

            'Morocco Standard Time'
                => 'Africa/Casablanca',

            'W. Europe Standard Time'
                => 'Europe/Berlin',

            'Central Europe Standard Time'
                => 'Europe/Budapest',

            'Romance Standard Time'
                => 'Europe/Paris',

            'Central European Standard Time'
                => 'Europe/Warsaw',

            'W. Central Africa Standard Time'
                => 'Africa/Lagos',

            'Jordan Standard Time'
                => 'Asia/Amman',

            'GTB Standard Time'
                => 'Europe/Bucharest',

            'Middle East Standard Time'
                => 'Asia/Beirut',

            'Egypt Standard Time'
                => 'Africa/Cairo',

            'E. Europe Standard Time'
                => 'Europe/Chisinau',

            'Syria Standard Time'
                => 'Asia/Damascus',

            'West Bank Standard Time'
                => 'Asia/Hebron',

            'South Africa Standard Time'
                => 'Africa/Johannesburg',

            'FLE Standard Time'
                => 'Europe/Kyiv',

            'Israel Standard Time'
                => 'Asia/Jerusalem',

            'South Sudan Standard Time'
                => 'Africa/Juba',

            'Kaliningrad Standard Time'
                => 'Europe/Kaliningrad',

            'Sudan Standard Time'
                => 'Africa/Khartoum',

            'Libya Standard Time'
                => 'Africa/Tripoli',

            'Namibia Standard Time'
                => 'Africa/Windhoek',

            'Arabic Standard Time'
                => 'Asia/Baghdad',

            'Turkey Standard Time'
                => 'Europe/Istanbul',

            'Arab Standard Time'
                => 'Asia/Riyadh',

            'Belarus Standard Time'
                => 'Europe/Minsk',

            'Russian Standard Time'
                => 'Europe/Moscow',

            'E. Africa Standard Time'
                => 'Africa/Nairobi',

            'Volgograd Standard Time'
                => 'Europe/Volgograd',

            'Iran Standard Time'
                => 'Asia/Tehran',

            'Arabian Standard Time'
                => 'Asia/Dubai',

            'Astrakhan Standard Time'
                => 'Europe/Astrakhan',

            'Azerbaijan Standard Time'
                => 'Asia/Baku',

            'Russia Time Zone 3'
                => 'Europe/Samara',

            'Mauritius Standard Time'
                => 'Indian/Mauritius',

            'Saratov Standard Time'
                => 'Europe/Saratov',

            'Georgian Standard Time'
                => 'Asia/Tbilisi',

            'Caucasus Standard Time'
                => 'Asia/Yerevan',

            'Afghanistan Standard Time'
                => 'Asia/Kabul',

            'West Asia Standard Time'
                => 'Asia/Tashkent',

            'Ekaterinburg Standard Time'
                => 'Asia/Yekaterinburg',

            'Pakistan Standard Time'
                => 'Asia/Karachi',

            'Qyzylorda Standard Time'
                => 'Asia/Qyzylorda',

            'India Standard Time'
                => 'Asia/Kolkata',

            'Sri Lanka Standard Time'
                => 'Asia/Colombo',

            'Nepal Standard Time'
                => 'Asia/Kathmandu',

            'Central Asia Standard Time'
                => 'Asia/Almaty',

            'Bangladesh Standard Time'
                => 'Asia/Dhaka',

            'Omsk Standard Time'
                => 'Asia/Omsk',

            'Myanmar Standard Time'
                => 'Asia/Yangon',

            'SE Asia Standard Time'
                => 'Asia/Bangkok',

            'Altai Standard Time'
                => 'Asia/Barnaul',

            'W. Mongolia Standard Time'
                => 'Asia/Hovd',

            'North Asia Standard Time'
                => 'Asia/Krasnoyarsk',

            'N. Central Asia Standard Time'
                => 'Asia/Novosibirsk',

            'Tomsk Standard Time'
                => 'Asia/Tomsk',

            'China Standard Time'
                => 'Asia/Shanghai',

            'North Asia East Standard Time'
                => 'Asia/Irkutsk',

            'Singapore Standard Time'
                => 'Asia/Singapore',

            'W. Australia Standard Time'
                => 'Australia/Perth',

            'Taipei Standard Time'
                => 'Asia/Taipei',

            'Ulaanbaatar Standard Time'
                => 'Asia/Ulaanbaatar',

            'Aus Central W. Standard Time'
                => 'Australia/Eucla',

            'Transbaikal Standard Time'
                => 'Asia/Chita',

            'Tokyo Standard Time'
                => 'Asia/Tokyo',

            'North Korea Standard Time'
                => 'Asia/Pyongyang',

            'Korea Standard Time'
                => 'Asia/Seoul',

            'Yakutsk Standard Time'
                => 'Asia/Yakutsk',

            'Cen. Australia Standard Time'
                => 'Australia/Adelaide',

            'AUS Central Standard Time'
                => 'Australia/Darwin',

            'E. Australia Standard Time'
                => 'Australia/Brisbane',

            'AUS Eastern Standard Time'
                => 'Australia/Sydney',

            'West Pacific Standard Time'
                => 'Pacific/Port_Moresby',

            'Tasmania Standard Time'
                => 'Australia/Hobart',

            'Vladivostok Standard Time'
                => 'Asia/Vladivostok',

            'Lord Howe Standard Time'
                => 'Australia/Lord_Howe',

            'Bougainville Standard Time'
                => 'Pacific/Bougainville',

            'Russia Time Zone 10'
                => 'Asia/Srednekolymsk',

            'Magadan Standard Time'
                => 'Asia/Magadan',

            'Norfolk Standard Time'
                => 'Pacific/Norfolk',

            'Sakhalin Standard Time'
                => 'Asia/Sakhalin',

            'Central Pacific Standard Time'
                => 'Pacific/Guadalcanal',

            'Russia Time Zone 11'
                => 'Asia/Kamchatka',

            'New Zealand Standard Time'
                => 'Pacific/Auckland',

            'UTC+12'
                => 'Etc/GMT-12',

            'Fiji Standard Time'
                => 'Pacific/Fiji',

            'Chatham Islands Standard Time'
                => 'Pacific/Chatham',

            'UTC+13'
                => 'Etc/GMT-13',

            'Tonga Standard Time'
                => 'Pacific/Tongatapu',

            'Samoa Standard Time'
                => 'Pacific/Apia',

            'Line Islands Standard Time'
                => 'Pacific/Kiritimati',
        ];


        /*
         * Ask Windows directly.
         */
        $windowsId = '';

        if (
            PHP_OS_FAMILY === 'Windows' &&
            function_exists('shell_exec')
        ) {

            $disabled =
                array_map(
                    'trim',
                    explode(
                        ',',
                        (string)ini_get(
                            'disable_functions'
                        )
                    )
                );

            if (
                !in_array(
                    'shell_exec',
                    $disabled,
                    true
                )
            ) {

                $result =
                    @shell_exec(
                        'tzutil /g 2>NUL'
                    );

                if (is_string($result)) {

                    $windowsId =
                        trim(
                            preg_replace(
                                '/[\r\n]+/',
                                '',
                                $result
                            )
                        );
                }
            }
        }


        if (
            $windowsId !== '' &&
            isset($windowsMap[$windowsId])
        ) {

            $candidate =
                $windowsMap[$windowsId];

            if (
                in_array(
                    $candidate,
                    timezone_identifiers_list(),
                    true
                )
            ) {

                $cached = $candidate;

                return $cached;
            }
        }


        /*
         * If PHP itself has been configured with a valid timezone,
         * respect it.
         */
        $configured =
            trim(
                (string)ini_get(
                    'date.timezone'
                )
            );

        if (
            $configured !== '' &&
            in_array(
                $configured,
                timezone_identifiers_list(),
                true
            )
        ) {

            $cached = $configured;

            return $cached;
        }


        /*
         * Last-resort safe fallback.
         */
        $cached = 'UTC';

        return $cached;
    }
}
