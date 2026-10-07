<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/core/bootstrap.php';

ag_require_admin();


function v85b_output(array $data, int $status = 200): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


function v85b_key(string $value): string
{
    $value =
        strtolower(
            trim($value)
        );


    $value =
        preg_replace(
            '/[^a-z0-9]+/',
            '',
            $value
        )
        ?? '';


    /*
     * Existing DreamGrid typo compatibility.
     */

    if ($value === 'officalregion') {
        return 'officialregion';
    }


    return $value;
}


/*
 * Locate Autobackup by walking upward from:
 *
 * Apache\htdocs\Other
 *
 * rather than assuming a hard-coded number of parent levels.
 */

function v85b_find_backup_root(): ?string
{
    $directory =
        __DIR__;


    for ($i = 0; $i < 8; $i++) {

        $candidate =
            $directory .
            DIRECTORY_SEPARATOR .
            'Autobackup';


        if (is_dir($candidate)) {

            $resolved =
                realpath(
                    $candidate
                );


            return $resolved !== false
                ?
                $resolved
                :
                $candidate;
        }


        $parent =
            dirname(
                $directory
            );


        if ($parent === $directory) {
            break;
        }


        $directory =
            $parent;
    }


    return null;
}


/*
 * DreamGrid currently creates files like:
 *
 * Autobackup\
 *   AutoBackup-2026-09-11\
 *     OAR\
 *       Welcome\
 *         _2026-09-11_19_56_02(1X1).oar
 *
 * Therefore the region name is normally the DIRECTORY
 * immediately below OAR, not part of the filename.
 *
 * Older layouts that put the region name in the filename
 * are supported as well.
 */

function v85b_region_for_file(
    SplFileInfo $file
): ?string
{
    $filename =
        $file->getFilename();


    /*
     * Older form:
     *
     * Welcome_2026-09-11_19_56_02(1X1).oar
     */

    if (
        preg_match(
            '~^(.+?)_' .
            '\d{4}-\d{2}-\d{2}_' .
            '\d{2}_\d{2}_\d{2}' .
            '(?:\([^)]*\))?' .
            '\.oar$~i',
            $filename,
            $match
        )
    ) {

        $region =
            trim(
                (string)(
                    $match[1] ??
                    ''
                )
            );


        if ($region !== '') {
            return $region;
        }
    }


    /*
     * Current DreamGrid form:
     *
     * ...\OAR\Welcome\_2026...
     */

    $filePath =
        str_replace(
            '\\',
            '/',
            $file->getPathname()
        );


    $parts =
        array_values(
            array_filter(
                explode(
                    '/',
                    $filePath
                ),
                static fn($value) =>
                    $value !== ''
            )
        );


    $count =
        count(
            $parts
        );


    for ($i = 0; $i < $count - 1; $i++) {

        if (
            strcasecmp(
                $parts[$i],
                'OAR'
            ) !== 0
        ) {
            continue;
        }


        if (!isset($parts[$i + 1])) {
            continue;
        }


        $region =
            trim(
                (string)$parts[$i + 1]
            );


        if ($region !== '') {
            return $region;
        }
    }


    return null;
}


/*
 * Request contains the region names currently displayed
 * by the Admin Regions page.
 */

$requested =
    json_decode(
        (string)(
            $_GET['regions'] ??
            ''
        ),
        true
    );


if (!is_array($requested)) {
    $requested = [];
}


$wanted = [];


foreach ($requested as $region) {

    if (!is_string($region)) {
        continue;
    }


    $region =
        trim(
            $region
        );


    if ($region === '') {
        continue;
    }


    $key =
        v85b_key(
            $region
        );


    if ($key === '') {
        continue;
    }


    $wanted[$key] =
        $region;


    if (count($wanted) >= 100) {
        break;
    }
}


if (!$wanted) {

    v85b_output([
        'ok' =>
            true,

        'rows' =>
            [],

        'generatedAt' =>
            gmdate('c'),
    ]);
}


$root =
    v85b_find_backup_root();


if ($root === null) {

    $rows =
        [];


    foreach ($wanted as $region) {

        $rows[] = [
            'RegionName' =>
                $region,

            'Available' =>
                false,

            'FileName' =>
                '',

            'ModifiedUnix' =>
                0,

            'ModifiedIso' =>
                '',

            'SizeBytes' =>
                0,

            'AgeSeconds' =>
                0,

            'Status' =>
                'NO BACKUP ROOT',
        ];
    }


    v85b_output([
        'ok' =>
            true,

        'rootAvailable' =>
            false,

        'rows' =>
            $rows,

        'generatedAt' =>
            gmdate('c'),
    ]);
}


/*
 * Newest AutoBackup folders first.
 */

$backupFolders =
    glob(
        $root .
        DIRECTORY_SEPARATOR .
        'AutoBackup-*',
        GLOB_ONLYDIR
    );


if (!is_array($backupFolders)) {
    $backupFolders = [];
}


usort(
    $backupFolders,
    static function(
        string $left,
        string $right
    ): int {

        return strnatcasecmp(
            basename($right),
            basename($left)
        );
    }
);


$found =
    [];


/*
 * Search newest dated backup folders first.
 *
 * As soon as every requested region has a backup,
 * older folders do not need to be scanned.
 */

foreach ($backupFolders as $backupFolder) {

    $scanRoot =
        $backupFolder .
        DIRECTORY_SEPARATOR .
        'OAR';


    if (!is_dir($scanRoot)) {

        $scanRoot =
            $backupFolder;
    }


    try {

        $iterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $scanRoot,
                    FilesystemIterator::SKIP_DOTS
                )
            );


        foreach ($iterator as $file) {

            if (
                !$file instanceof SplFileInfo
                ||
                !$file->isFile()
            ) {
                continue;
            }


            if (
                strtolower(
                    $file->getExtension()
                )
                !==
                'oar'
            ) {
                continue;
            }


            $sourceRegion =
                v85b_region_for_file(
                    $file
                );


            if ($sourceRegion === null) {
                continue;
            }


            $sourceKey =
                v85b_key(
                    $sourceRegion
                );


            if (
                $sourceKey === ''
                ||
                !isset(
                    $wanted[$sourceKey]
                )
            ) {
                continue;
            }


            $modified =
                (int)$file->getMTime();


            $size =
                (int)$file->getSize();


            /*
             * Keep newest file for that region.
             */

            if (
                isset(
                    $found[$sourceKey]
                )
                &&
                (int)$found[$sourceKey]['ModifiedUnix']
                >=
                $modified
            ) {
                continue;
            }


            $found[$sourceKey] = [
                'RegionName' =>
                    $wanted[$sourceKey],

                'SourceRegionName' =>
                    $sourceRegion,

                'Available' =>
                    true,

                'FileName' =>
                    $file->getFilename(),

                'ModifiedUnix' =>
                    $modified,

                'ModifiedIso' =>
                    $modified > 0
                        ?
                        gmdate(
                            'c',
                            $modified
                        )
                        :
                        '',

                'SizeBytes' =>
                    $size,

                'AgeSeconds' =>
                    $modified > 0
                        ?
                        max(
                            0,
                            time() - $modified
                        )
                        :
                        0,

                'Status' =>
                    $size > 0
                        ?
                        'OK'
                        :
                        'EMPTY',
            ];
        }
    }
    catch (Throwable $error) {

        /*
         * Skip an inaccessible historical folder.
         */

    }


    if (
        count($found)
        >=
        count($wanted)
    ) {

        break;
    }
}


/*
 * Also support old installations that placed OAR files
 * directly in Autobackup rather than dated folders.
 */

if (
    count($found)
    <
    count($wanted)
) {

    try {

        $rootIterator =
            new DirectoryIterator(
                $root
            );


        foreach ($rootIterator as $file) {

            if (
                !$file->isFile()
                ||
                strtolower(
                    $file->getExtension()
                )
                !==
                'oar'
            ) {
                continue;
            }


            $sourceRegion =
                v85b_region_for_file(
                    $file
                );


            if ($sourceRegion === null) {
                continue;
            }


            $sourceKey =
                v85b_key(
                    $sourceRegion
                );


            if (
                $sourceKey === ''
                ||
                !isset(
                    $wanted[$sourceKey]
                )
            ) {
                continue;
            }


            $modified =
                (int)$file->getMTime();


            $size =
                (int)$file->getSize();


            if (
                isset(
                    $found[$sourceKey]
                )
                &&
                (int)$found[$sourceKey]['ModifiedUnix']
                >=
                $modified
            ) {
                continue;
            }


            $found[$sourceKey] = [
                'RegionName' =>
                    $wanted[$sourceKey],

                'SourceRegionName' =>
                    $sourceRegion,

                'Available' =>
                    true,

                'FileName' =>
                    $file->getFilename(),

                'ModifiedUnix' =>
                    $modified,

                'ModifiedIso' =>
                    $modified > 0
                        ?
                        gmdate(
                            'c',
                            $modified
                        )
                        :
                        '',

                'SizeBytes' =>
                    $size,

                'AgeSeconds' =>
                    $modified > 0
                        ?
                        max(
                            0,
                            time() - $modified
                        )
                        :
                        0,

                'Status' =>
                    $size > 0
                        ?
                        'OK'
                        :
                        'EMPTY',
            ];
        }
    }
    catch (Throwable $error) {

    }
}


$rows =
    [];


foreach ($wanted as $key => $region) {

    if (isset($found[$key])) {

        $rows[] =
            $found[$key];

        continue;
    }


    $rows[] = [
        'RegionName' =>
            $region,

        'SourceRegionName' =>
            '',

        'Available' =>
            false,

        'FileName' =>
            '',

        'ModifiedUnix' =>
            0,

        'ModifiedIso' =>
            '',

        'SizeBytes' =>
            0,

        'AgeSeconds' =>
            0,

        'Status' =>
            'NO OAR',
    ];
}


v85b_output([
    'ok' =>
        true,

    'rootAvailable' =>
        true,

    'backupRoot' =>
        $root,

    'rows' =>
        $rows,

    'generatedAt' =>
        gmdate('c'),
]);