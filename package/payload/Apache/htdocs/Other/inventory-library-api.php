<?php

declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

$session = ag_current_session();

ag_no_cache();

header('Content-Type: application/json; charset=utf-8');


function libJson(array $data, int $status = 200): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


function libFail(string $message, int $status = 500): void
{
    libJson(
        [
            'ok'    => false,
            'error' => $message
        ],
        $status
    );
}


function libAttrCI(
    SimpleXMLElement $node,
    string $wanted
): string {

    foreach ($node->attributes() as $name => $value) {

        if (strcasecmp((string)$name, $wanted) === 0) {
            return trim((string)$value);
        }
    }

    return '';
}


function libReadNini(string $file): array
{
    if (!is_file($file)) {
        throw new RuntimeException(
            'Library XML file was not found: ' .
            basename($file)
        );
    }


    libxml_use_internal_errors(true);


    $xml = simplexml_load_file(
        $file,
        SimpleXMLElement::class,
        LIBXML_NONET | LIBXML_NOCDATA
    );


    if ($xml === false) {

        $errors = libxml_get_errors();

        libxml_clear_errors();

        $message =
            'Could not parse library XML: ' .
            basename($file);

        if (!empty($errors)) {

            $message .=
                ' - ' .
                trim((string)$errors[0]->message);
        }

        throw new RuntimeException($message);
    }


    $sections = [];


    $nodes =
        $xml->xpath('//Section');


    if (!is_array($nodes)) {
        return [];
    }


    foreach ($nodes as $section) {

        $sectionName =
            libAttrCI(
                $section,
                'Name'
            );


        $keys = [];


        foreach ($section->children() as $child) {

            if (
                strcasecmp(
                    $child->getName(),
                    'Key'
                ) !== 0
            ) {
                continue;
            }


            $name =
                libAttrCI(
                    $child,
                    'Name'
                );


            $value =
                libAttrCI(
                    $child,
                    'Value'
                );


            if ($name === '') {
                continue;
            }


            $keys[
                strtolower($name)
            ] = $value;
        }


        $sections[] = [
            'name' => $sectionName,
            'keys' => $keys
        ];
    }


    return $sections;
}


function libPick(
    array $keys,
    array $names,
    string $default = ''
): string {

    foreach ($names as $name) {

        $key =
            strtolower($name);


        if (
            array_key_exists(
                $key,
                $keys
            )
        ) {
            return trim(
                (string)$keys[$key]
            );
        }
    }


    return $default;
}


function libOpenSimBin(): string
{
    $outworldz =
        dirname(
            __DIR__,
            3
        );


    $candidates = [

        $outworldz .
        DIRECTORY_SEPARATOR .
        'Opensim' .
        DIRECTORY_SEPARATOR .
        'bin',

        $outworldz .
        DIRECTORY_SEPARATOR .
        'OpenSim' .
        DIRECTORY_SEPARATOR .
        'bin'

    ];


    foreach ($candidates as $candidate) {

        $real =
            realpath(
                $candidate
            );


        if (
            $real !== false &&
            is_dir($real)
        ) {
            return $real;
        }
    }


    throw new RuntimeException(
        'OpenSim bin folder could not be found.'
    );
}


function libIniSectionValue(
    string $file,
    string $section,
    string $key
): string {

    if (!is_file($file)) {
        return '';
    }


    $text =
        (string)@file_get_contents(
            $file
        );


    if ($text === '') {
        return '';
    }


    $sectionPattern =
        '/^\s*\[' .
        preg_quote($section, '/') .
        '\]\s*$(.*?)(?=^\s*\[|\z)/msi';


    if (
        !preg_match(
            $sectionPattern,
            $text,
            $match
        )
    ) {
        return '';
    }


    $body =
        (string)$match[1];


    $keyPattern =
        '/^\s*' .
        preg_quote($key, '/') .
        '\s*=\s*"?([^"\r\n;]+)"?/mi';


    if (
        !preg_match(
            $keyPattern,
            $body,
            $value
        )
    ) {
        return '';
    }


    return trim(
        (string)$value[1]
    );
}


function libResolvePath(
    string $value,
    string $baseFile,
    string $bin
): string {

    $value =
        trim(
            $value,
            " \t\n\r\0\x0B\"'"
        );


    if ($value === '') {
        return '';
    }


    $value =
        str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $value
        );


    $candidates = [];


    if (
        preg_match(
            '/^(?:[A-Za-z]:[\\\\\/]|\\\\\\\\)/',
            $value
        )
    ) {

        $candidates[] =
            $value;
    }
    else {

        $candidates[] =
            dirname($baseFile) .
            DIRECTORY_SEPARATOR .
            $value;


        $candidates[] =
            $bin .
            DIRECTORY_SEPARATOR .
            $value;


        $candidates[] =
            $bin .
            DIRECTORY_SEPARATOR .
            'inventory' .
            DIRECTORY_SEPARATOR .
            $value;
    }


    foreach ($candidates as $candidate) {

        $real =
            realpath(
                $candidate
            );


        if (
            $real !== false &&
            is_file($real)
        ) {
            return $real;
        }
    }


    return '';
}


function libConfig(): array
{
    $bin =
        libOpenSimBin();


    $configFiles = [

        $bin .
        DIRECTORY_SEPARATOR .
        'Robust.HG.ini',

        $bin .
        DIRECTORY_SEPARATOR .
        'Robust.ini',

        $bin .
        DIRECTORY_SEPARATOR .
        'OpenSim.ini'

    ];


    foreach (
        glob(
            $bin .
            DIRECTORY_SEPARATOR .
            'Robust*.ini'
        ) ?: []
        as $file
    ) {

        if (
            !in_array(
                $file,
                $configFiles,
                true
            )
        ) {
            $configFiles[] = $file;
        }
    }


    $includeDir =
        $bin .
        DIRECTORY_SEPARATOR .
        'config-include';


    if (is_dir($includeDir)) {

        foreach (
            glob(
                $includeDir .
                DIRECTORY_SEPARATOR .
                '*.ini'
            ) ?: []
            as $file
        ) {

            if (
                !in_array(
                    $file,
                    $configFiles,
                    true
                )
            ) {
                $configFiles[] = $file;
            }
        }
    }


    $libraryName =
        'OpenSim Library';


    $control =
        '';


    foreach ($configFiles as $file) {

        if (!is_file($file)) {
            continue;
        }


        $name =
            libIniSectionValue(
                $file,
                'LibraryService',
                'LibraryName'
            );


        if ($name !== '') {
            $libraryName = $name;
        }


        $default =
            libIniSectionValue(
                $file,
                'LibraryService',
                'DefaultLibrary'
            );


        if ($default !== '') {

            $resolved =
                libResolvePath(
                    $default,
                    $bin .
                    DIRECTORY_SEPARATOR .
                    'placeholder.ini',
                    $bin
                );


            if ($resolved !== '') {

                $control =
                    $resolved;

                break;
            }
        }
    }


    if ($control === '') {

        $fallback =
            $bin .
            DIRECTORY_SEPARATOR .
            'inventory' .
            DIRECTORY_SEPARATOR .
            'Libraries.xml';


        $real =
            realpath(
                $fallback
            );


        if (
            $real !== false &&
            is_file($real)
        ) {
            $control = $real;
        }
    }


    if ($control === '') {

        throw new RuntimeException(
            'OpenSim Libraries.xml could not be found.'
        );
    }


    return [
        'bin'         => $bin,
        'libraryName' => $libraryName,
        'control'     => $control
    ];
}


function libClassify(array $sections): string
{
    $folderScore = 0;

    $itemScore = 0;


    foreach ($sections as $section) {

        $keys =
            $section['keys'] ?? [];


        if (
            isset($keys['inventoryid'])
            ||
            (
                isset($keys['assetid'])
                &&
                isset($keys['folderid'])
            )
        ) {
            $itemScore++;
        }


        if (
            isset($keys['folderid'])
            &&
            !isset($keys['inventoryid'])
            &&
            !isset($keys['assetid'])
            &&
            (
                isset($keys['parentfolderid'])
                ||
                isset($keys['parentid'])
                ||
                isset($keys['parent'])
                ||
                isset($keys['name'])
            )
        ) {
            $folderScore++;
        }
    }


    if ($itemScore > 0) {
        return 'items';
    }


    if ($folderScore > 0) {
        return 'folders';
    }


    return 'control';
}


function libReferencedXml(
    array $sections,
    string $currentFile,
    string $bin
): array {

    $result = [];


    foreach ($sections as $section) {

        foreach (
            ($section['keys'] ?? [])
            as $value
        ) {

            $value =
                trim(
                    (string)$value
                );


            if (
                !preg_match(
                    '/\.xml\s*$/i',
                    $value
                )
            ) {
                continue;
            }


            $resolved =
                libResolvePath(
                    $value,
                    $currentFile,
                    $bin
                );


            if ($resolved !== '') {

                $result[
                    strtolower($resolved)
                ] = $resolved;
            }
        }
    }


    return array_values(
        $result
    );
}


function libLeafFiles(
    string $control,
    string $bin
): array {

    $queue = [
        [
            'file'  => $control,
            'depth' => 0
        ]
    ];


    $seen = [];

    $leaves = [];


    while (!empty($queue)) {

        $entry =
            array_shift(
                $queue
            );


        $file =
            (string)$entry['file'];


        $depth =
            (int)$entry['depth'];


        $key =
            strtolower($file);


        if (isset($seen[$key])) {
            continue;
        }


        $seen[$key] = true;


        $sections =
            libReadNini(
                $file
            );


        $kind =
            libClassify(
                $sections
            );


        if (
            $file !== $control
            &&
            (
                $kind === 'folders'
                ||
                $kind === 'items'
            )
        ) {

            $leaves[] = [
                'file'     => $file,
                'kind'     => $kind,
                'sections' => $sections
            ];

            continue;
        }


        if ($depth >= 5) {
            continue;
        }


        foreach (
            libReferencedXml(
                $sections,
                $file,
                $bin
            )
            as $child
        ) {

            $queue[] = [
                'file'  => $child,
                'depth' => $depth + 1
            ];
        }
    }


    return $leaves;
}


function libData(): array
{
    $config =
        libConfig();


    $leaves =
        libLeafFiles(
            $config['control'],
            $config['bin']
        );


    $foldersById = [];

    $itemsById = [];


    foreach ($leaves as $leaf) {

        $kind =
            (string)$leaf['kind'];


        foreach (
            $leaf['sections']
            as $section
        ) {

            $keys =
                $section['keys'] ?? [];


            if ($kind === 'folders') {

                $id =
                    libPick(
                        $keys,
                        [
                            'folderID',
                            'folderId',
                            'id'
                        ]
                    );


                if ($id === '') {
                    continue;
                }


                $parent =
                    libPick(
                        $keys,
                        [
                            'parentFolderID',
                            'parentFolderId',
                            'parentID',
                            'parentId',
                            'parent'
                        ]
                    );


                $name =
                    libPick(
                        $keys,
                        ['name'],
                        (string)(
                            $section['name'] ??
                            'Unnamed Folder'
                        )
                    );


                $type =
                    libPick(
                        $keys,
                        [
                            'type',
                            'folderType'
                        ],
                        '-1'
                    );


                $foldersById[
                    strtolower($id)
                ] = [
                    'id'        => $id,
                    'parent'    => $parent,
                    'name'      => $name,
                    'type'      => is_numeric($type)
                        ? (int)$type
                        : $type,
                    'itemCount' => 0,
                    'source'    => 'library'
                ];
            }


            if ($kind === 'items') {

                $id =
                    libPick(
                        $keys,
                        [
                            'inventoryID',
                            'inventoryId',
                            'itemID',
                            'itemId',
                            'id'
                        ]
                    );


                $folderId =
                    libPick(
                        $keys,
                        [
                            'folderID',
                            'folderId',
                            'parentFolderID'
                        ]
                    );


                if (
                    $id === ''
                    ||
                    $folderId === ''
                ) {
                    continue;
                }


                $name =
                    libPick(
                        $keys,
                        ['name'],
                        (string)(
                            $section['name'] ??
                            'Unnamed Item'
                        )
                    );


                $assetType =
                    libPick(
                        $keys,
                        ['assetType'],
                        '-1'
                    );


                $inventoryType =
                    libPick(
                        $keys,
                        ['inventoryType'],
                        '-1'
                    );


                $itemsById[
                    strtolower($id)
                ] = [
                    'id'            => $id,
                    'name'          => $name,
                    'description'   => libPick(
                        $keys,
                        ['description']
                    ),
                    'assetId'       => libPick(
                        $keys,
                        [
                            'assetID',
                            'assetId'
                        ]
                    ),
                    'assetType'     => is_numeric($assetType)
                        ? (int)$assetType
                        : $assetType,
                    'inventoryType' => is_numeric($inventoryType)
                        ? (int)$inventoryType
                        : $inventoryType,
                    'creatorId'     => libPick(
                        $keys,
                        [
                            'creatorID',
                            'creatorId',
                            'creator'
                        ]
                    ),
                    'creationDate'  => libPick(
                        $keys,
                        [
                            'creationDate',
                            'creationTime'
                        ]
                    ),
                    'folderId'      => $folderId,
                    'source'        => 'library'
                ];
            }
        }
    }


    $itemsByFolder = [];


    foreach ($itemsById as $item) {

        $folderKey =
            strtolower(
                (string)$item['folderId']
            );


        if (
            !isset(
                $itemsByFolder[$folderKey]
            )
        ) {
            $itemsByFolder[$folderKey] = [];
        }


        $itemsByFolder[
            $folderKey
        ][] = $item;


        if (
            isset(
                $foldersById[$folderKey]
            )
        ) {

            $foldersById[
                $folderKey
            ]['itemCount']++;
        }
    }


    $folders =
        array_values(
            $foldersById
        );


    usort(
        $folders,
        static function (
            array $a,
            array $b
        ): int {

            return strcasecmp(
                (string)$a['name'],
                (string)$b['name']
            );
        }
    );


    foreach ($itemsByFolder as &$items) {

        usort(
            $items,
            static function (
                array $a,
                array $b
            ): int {

                return strcasecmp(
                    (string)$a['name'],
                    (string)$b['name']
                );
            }
        );
    }

    unset($items);


    return [
        'libraryName'   => $config['libraryName'],
        'folders'       => $folders,
        'items'         => array_values($itemsById),
        'itemsByFolder' => $itemsByFolder,
        'sourceFile'    => basename($config['control'])
    ];
}


// Shared read-only library access is available to authenticated grid users.
if (!$session) {
    libFail('Not logged in.', 401);
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    libFail('GET required.', 405);
}
foreach (['api', 'folder', 'q'] as $field) {
    if (isset($_GET[$field]) && !is_string($_GET[$field])) {
        libFail('Invalid library request.', 400);
    }
}

try {

    $data =
        libData();


    $action =
        strtolower(
            trim(
                (string)(
                    $_GET['api'] ??
                    'summary'
                )
            )
        );


    if ($action === 'summary') {

        libJson([
            'ok'          => true,
            'libraryName' => $data['libraryName'],
            'folders'     => count($data['folders']),
            'items'       => count($data['items']),
            'sourceFile'  => $data['sourceFile']
        ]);
    }


    if ($action === 'folders') {

        libJson([
            'ok'          => true,
            'libraryName' => $data['libraryName'],
            'folders'     => $data['folders']
        ]);
    }


    if ($action === 'items') {

        $folder =
            trim(
                (string)(
                    $_GET['folder'] ??
                    ''
                )
            );


        if ($folder === '') {

            libJson([
                'ok'    => true,
                'items' => []
            ]);
        }


        $key =
            strtolower(
                $folder
            );


        libJson([
            'ok'    => true,
            'items' => $data['itemsByFolder'][$key] ?? []
        ]);
    }


    if ($action === 'search') {

        $query =
            trim(
                (string)(
                    $_GET['q'] ??
                    ''
                )
            );


        if (
            strlen($query) <
            2
        ) {

            libJson([
                'ok'    => true,
                'items' => []
            ]);
        }


        if (
            strlen($query) >
            100
        ) {

            $query =
                substr(
                    $query,
                    0,
                    100
                );
        }


        $matches = [];


        foreach ($data['items'] as $item) {

            if (
                stripos(
                    (string)$item['name'],
                    $query
                ) !== false
                ||
                stripos(
                    (string)$item['description'],
                    $query
                ) !== false
            ) {

                $matches[] =
                    $item;


                if (
                    count($matches) >=
                    300
                ) {
                    break;
                }
            }
        }


        libJson([
            'ok'    => true,
            'items' => $matches
        ]);
    }


    libFail(
        'Unknown OpenSim Library API action.',
        400
    );
}
catch (Throwable $error) {

    libFail(
        $error->getMessage(),
        500
    );
}
