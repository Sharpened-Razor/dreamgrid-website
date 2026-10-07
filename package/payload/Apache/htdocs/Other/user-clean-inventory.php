<?php

require_once __DIR__ . '/core/bootstrap.php';


/*
 * ============================================================
 * Grid
 * CLEAN INVENTORY WORKSPACE V1
 *
 * SAFETY:
 *
 * This page NEVER deletes or modifies live OpenSim inventory.
 * CLEAN INVENTORY is staging data only.
 * ============================================================
 */


$session =
    ag_current_session();


if (!$session) {

    header(
        'Location: /Other/login.php'
    );

    exit;

}


$principalId =
    trim(
        (string)(
            $session['principalId'] ??
            ''
        )
    );


$avatar =
    trim(
        (string)(
            $session['avatar'] ??
            ''
        )
    );


if ($principalId === '') {

    http_response_code(403);

    echo 'Signed account ID is unavailable.';

    exit;

}


/*
 * ============================================================
 * CSRF
 * ============================================================
 */


if (
    session_status() !==
    PHP_SESSION_ACTIVE
) {

    session_start();

}


if (
    empty(
        $_SESSION['australia_clean_inventory_csrf']
    )
) {

    $_SESSION['australia_clean_inventory_csrf'] =
        bin2hex(
            random_bytes(32)
        );

}


$csrfToken =
    (string)
    $_SESSION['australia_clean_inventory_csrf'];


/*
 * ============================================================
 * PRIVATE STAGING STORAGE
 *
 * This folder is OUTSIDE Apache htdocs.
 * ============================================================
 */


$storageRoot =
    dirname(
        __DIR__,
        3
    ) .
    DIRECTORY_SEPARATOR .
    'AustraliaData' .
    DIRECTORY_SEPARATOR .
    'clean-inventory';


if (
    !is_dir(
        $storageRoot
    )
) {

    @mkdir(
        $storageRoot,
        0750,
        true
    );

}


if (
    !is_dir(
        $storageRoot
    )
) {

    http_response_code(500);

    echo 'Clean Inventory storage is unavailable.';

    exit;

}


$storageFile =
    $storageRoot .
    DIRECTORY_SEPARATOR .
    hash(
        'sha256',
        $principalId
    ) .
    '.json';


/*
 * ============================================================
 * GENERIC HELPERS
 * ============================================================
 */


function cleanJson(
    array $data,
    int $status = 200
): void {

    http_response_code(
        $status
    );


    header(
        'Content-Type: application/json; charset=utf-8'
    );


    header(
        'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
    );


    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES
    );


    exit;

}


function cleanIdentifier(
    string $name
): string {

    return
        '`' .
        str_replace(
            '`',
            '``',
            $name
        ) .
        '`';

}


function cleanDefaultState(
    string $principalId
): array {

    $now =
        gmdate(
            'c'
        );


    return [
        'version' =>
            1,

        'principalId' =>
            $principalId,

        'createdAt' =>
            $now,

        'updatedAt' =>
            $now,

        'folders' =>
            [],

        'items' =>
            []
    ];

}


function cleanLoadState(
    string $file,
    string $principalId
): array {

    if (
        !is_file(
            $file
        )
    ) {

        return
            cleanDefaultState(
                $principalId
            );

    }


    $raw =
        @file_get_contents(
            $file
        );


    if (
        !is_string(
            $raw
        ) ||
        trim(
            $raw
        ) === ''
    ) {

        return
            cleanDefaultState(
                $principalId
            );

    }


    $state =
        json_decode(
            $raw,
            true
        );


    if (
        !is_array(
            $state
        ) ||
        (
            (string)(
                $state['principalId'] ??
                ''
            )
            !==
            $principalId
        )
    ) {

        throw new Exception(
            'Clean Inventory staging file is invalid.'
        );

    }


    if (
        !isset(
            $state['folders']
        ) ||
        !is_array(
            $state['folders']
        )
    ) {

        $state['folders'] =
            [];

    }


    if (
        !isset(
            $state['items']
        ) ||
        !is_array(
            $state['items']
        )
    ) {

        $state['items'] =
            [];

    }


    return $state;

}


function cleanSaveState(
    string $file,
    array $state
): void {

    $state['updatedAt'] =
        gmdate(
            'c'
        );


    $encoded =
        json_encode(
            $state,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        );


    if (
        !is_string(
            $encoded
        )
    ) {

        throw new Exception(
            'Clean Inventory staging data could not be encoded.'
        );

    }


    $backup =
        $file .
        '.bak';


    if (
        is_file(
            $file
        )
    ) {

        @copy(
            $file,
            $backup
        );

    }


    $written =
        @file_put_contents(
            $file,
            $encoded,
            LOCK_EX
        );


    if (
        $written ===
        false
    ) {

        if (
            is_file(
                $backup
            )
        ) {

            @copy(
                $backup,
                $file
            );

        }


        throw new Exception(
            'Clean Inventory staging data could not be saved.'
        );

    }

}


function cleanPostData(): array {

    $raw =
        file_get_contents(
            'php://input'
        );


    $data =
        json_decode(
            (string)$raw,
            true
        );


    return
        is_array(
            $data
        )
        ?
        $data
        :
        [];

}


function cleanValidUuid(
    string $value
): bool {

    return (
        preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
            $value
        ) === 1
    );

}


function cleanSafeFolderName(
    string $name
): string {

    $name =
        trim(
            preg_replace(
                '/[\x00-\x1F\x7F]/u',
                '',
                $name
            )
        );


    if (
        $name === ''
    ) {

        throw new Exception(
            'Enter a folder name.'
        );

    }


    if (
        strlen(
            $name
        ) > 64
    ) {

        throw new Exception(
            'Folder names cannot exceed 64 characters.'
        );

    }


    return $name;

}


/*
 * ============================================================
 * DATABASE DISCOVERY
 * ============================================================
 */


function cleanFindInventoryTables(
    mysqli $con,
    string $preferredSchema
): array {

    $sql =
        "SELECT TABLE_SCHEMA, TABLE_NAME
         FROM information_schema.TABLES
         WHERE LOWER(TABLE_NAME)
         IN ('inventoryfolders','inventoryitems')";


    $result =
        mysqli_query(
            $con,
            $sql
        );


    if (!$result) {

        throw new Exception(
            'Could not locate OpenSim inventory tables.'
        );

    }


    $schemas =
        [];


    while (
        $row =
            mysqli_fetch_assoc(
                $result
            )
    ) {

        $schema =
            (string)
            $row['TABLE_SCHEMA'];


        $table =
            (string)
            $row['TABLE_NAME'];


        if (
            !isset(
                $schemas[$schema]
            )
        ) {

            $schemas[$schema] =
                [];

        }


        $schemas[$schema][
            strtolower(
                $table
            )
        ] =
            $table;

    }


    mysqli_free_result(
        $result
    );


    if (
        isset(
            $schemas[$preferredSchema]['inventoryfolders']
        ) &&
        isset(
            $schemas[$preferredSchema]['inventoryitems']
        )
    ) {

        return [
            'schema' =>
                $preferredSchema,

            'folders' =>
                $schemas[$preferredSchema]['inventoryfolders'],

            'items' =>
                $schemas[$preferredSchema]['inventoryitems']
        ];

    }


    foreach(
        $schemas
        as
        $schema =>
        $tables
    ) {

        if (
            isset(
                $tables['inventoryfolders']
            ) &&
            isset(
                $tables['inventoryitems']
            )
        ) {

            return [
                'schema' =>
                    $schema,

                'folders' =>
                    $tables['inventoryfolders'],

                'items' =>
                    $tables['inventoryitems']
            ];

        }

    }


    throw new Exception(
        'OpenSim inventory tables were not found.'
    );

}


function cleanColumns(
    mysqli $con,
    string $schema,
    string $table
): array {

    $sql =
        "SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ?
         AND TABLE_NAME = ?";


    $stmt =
        mysqli_prepare(
            $con,
            $sql
        );


    if (!$stmt) {

        throw new Exception(
            'Could not inspect inventory table columns.'
        );

    }


    mysqli_stmt_bind_param(
        $stmt,
        'ss',
        $schema,
        $table
    );


    mysqli_stmt_execute(
        $stmt
    );


    $result =
        mysqli_stmt_get_result(
            $stmt
        );


    $columns =
        [];


    while (
        $row =
            mysqli_fetch_assoc(
                $result
            )
    ) {

        $name =
            (string)
            $row['COLUMN_NAME'];


        $columns[
            strtolower(
                $name
            )
        ] =
            $name;

    }


    mysqli_stmt_close(
        $stmt
    );


    return $columns;

}


function cleanPickColumn(
    array $columns,
    array $names,
    bool $required = true
): ?string {

    foreach(
        $names
        as
        $name
    ) {

        $key =
            strtolower(
                $name
            );


        if (
            isset(
                $columns[$key]
            )
        ) {

            return
                $columns[$key];

        }

    }


    if ($required) {

        throw new Exception(
            'Required inventory column was not found: ' .
            implode(
                ' / ',
                $names
            )
        );

    }


    return null;

}


/*
 * ============================================================
 * LOAD LIVE INVENTORY FOR VALIDATION / COPYING
 * ============================================================
 */


function cleanLoadLiveInventory(
    string $principalId
): array {

    require __DIR__ . '/../../MetroMap/includes/config.php';


    $con =
        @mysqli_connect(
            $CONF_db_server,
            $CONF_db_user,
            $CONF_db_pass,
            $CONF_db_database,
            (int)$CONF_db_port
        );


    if (!$con) {

        throw new Exception(
            'Grid inventory database is unavailable.'
        );

    }


    mysqli_set_charset(
        $con,
        'utf8mb4'
    );


    try {

        $tables =
            cleanFindInventoryTables(
                $con,
                (string)$CONF_db_database
            );


        $schema =
            $tables['schema'];


        $folderTable =
            $tables['folders'];


        $itemTable =
            $tables['items'];


        $fc =
            cleanColumns(
                $con,
                $schema,
                $folderTable
            );


        $ic =
            cleanColumns(
                $con,
                $schema,
                $itemTable
            );


        $folderId =
            cleanPickColumn(
                $fc,
                [
                    'folderID'
                ]
            );


        $folderOwner =
            cleanPickColumn(
                $fc,
                [
                    'agentID',
                    'avatarID'
                ]
            );


        $folderParent =
            cleanPickColumn(
                $fc,
                [
                    'parentFolderID',
                    'parentID'
                ]
            );


        $folderName =
            cleanPickColumn(
                $fc,
                [
                    'folderName',
                    'name'
                ]
            );


        $folderType =
            cleanPickColumn(
                $fc,
                [
                    'type'
                ],
                false
            );


        $itemId =
            cleanPickColumn(
                $ic,
                [
                    'inventoryID'
                ]
            );


        $itemOwner =
            cleanPickColumn(
                $ic,
                [
                    'avatarID',
                    'agentID'
                ]
            );


        $itemParent =
            cleanPickColumn(
                $ic,
                [
                    'parentFolderID',
                    'folderID'
                ]
            );


        $itemName =
            cleanPickColumn(
                $ic,
                [
                    'inventoryName',
                    'name'
                ]
            );


        $itemDescription =
            cleanPickColumn(
                $ic,
                [
                    'inventoryDescription',
                    'description'
                ],
                false
            );


        $assetId =
            cleanPickColumn(
                $ic,
                [
                    'assetID'
                ],
                false
            );


        $assetType =
            cleanPickColumn(
                $ic,
                [
                    'assetType'
                ],
                false
            );


        $inventoryType =
            cleanPickColumn(
                $ic,
                [
                    'invType',
                    'inventoryType'
                ],
                false
            );


        $creatorId =
            cleanPickColumn(
                $ic,
                [
                    'creatorID'
                ],
                false
            );


        $creationDate =
            cleanPickColumn(
                $ic,
                [
                    'creationDate'
                ],
                false
            );


        $folderTypeExpr =
            $folderType
            ?
            cleanIdentifier(
                $folderType
            )
            :
            'NULL';


        $folderSql =
            'SELECT ' .

            cleanIdentifier(
                $folderId
            ) .
            ' folder_id, ' .

            cleanIdentifier(
                $folderParent
            ) .
            ' parent_id, ' .

            cleanIdentifier(
                $folderName
            ) .
            ' folder_name, ' .

            $folderTypeExpr .
            ' folder_type ' .

            'FROM ' .

            cleanIdentifier(
                $schema
            ) .
            '.' .
            cleanIdentifier(
                $folderTable
            ) .

            ' WHERE ' .

            cleanIdentifier(
                $folderOwner
            ) .
            ' = ?';


        $folderStmt =
            mysqli_prepare(
                $con,
                $folderSql
            );


        mysqli_stmt_bind_param(
            $folderStmt,
            's',
            $principalId
        );


        mysqli_stmt_execute(
            $folderStmt
        );


        $folderResult =
            mysqli_stmt_get_result(
                $folderStmt
            );


        $folders =
            [];


        while (
            $row =
                mysqli_fetch_assoc(
                    $folderResult
                )
        ) {

            $id =
                (string)
                $row['folder_id'];


            $folders[$id] = [
                'id' =>
                    $id,

                'parentId' =>
                    (string)
                    $row['parent_id'],

                'name' =>
                    (string)
                    $row['folder_name'],

                'type' =>
                    $row['folder_type']
            ];

        }


        mysqli_stmt_close(
            $folderStmt
        );


        $descExpr =
            $itemDescription
            ?
            cleanIdentifier(
                $itemDescription
            )
            :
            "''";


        $assetExpr =
            $assetId
            ?
            cleanIdentifier(
                $assetId
            )
            :
            "''";


        $assetTypeExpr =
            $assetType
            ?
            cleanIdentifier(
                $assetType
            )
            :
            'NULL';


        $invTypeExpr =
            $inventoryType
            ?
            cleanIdentifier(
                $inventoryType
            )
            :
            'NULL';


        $creatorExpr =
            $creatorId
            ?
            cleanIdentifier(
                $creatorId
            )
            :
            "''";


        $createdExpr =
            $creationDate
            ?
            cleanIdentifier(
                $creationDate
            )
            :
            'NULL';


        $itemSql =
            'SELECT ' .

            cleanIdentifier(
                $itemId
            ) .
            ' inventory_id, ' .

            cleanIdentifier(
                $itemParent
            ) .
            ' parent_folder_id, ' .

            cleanIdentifier(
                $itemName
            ) .
            ' inventory_name, ' .

            $descExpr .
            ' inventory_description, ' .

            $assetExpr .
            ' asset_id, ' .

            $assetTypeExpr .
            ' asset_type, ' .

            $invTypeExpr .
            ' inventory_type, ' .

            $creatorExpr .
            ' creator_id, ' .

            $createdExpr .
            ' creation_date ' .

            'FROM ' .

            cleanIdentifier(
                $schema
            ) .
            '.' .
            cleanIdentifier(
                $itemTable
            ) .

            ' WHERE ' .

            cleanIdentifier(
                $itemOwner
            ) .
            ' = ?';


        $itemStmt =
            mysqli_prepare(
                $con,
                $itemSql
            );


        mysqli_stmt_bind_param(
            $itemStmt,
            's',
            $principalId
        );


        mysqli_stmt_execute(
            $itemStmt
        );


        $itemResult =
            mysqli_stmt_get_result(
                $itemStmt
            );


        $items =
            [];


        while (
            $row =
                mysqli_fetch_assoc(
                    $itemResult
                )
        ) {

            $id =
                (string)
                $row['inventory_id'];


            $items[$id] = [
                'id' =>
                    $id,

                'sourceId' =>
                    $id,

                'folderId' =>
                    (string)
                    $row['parent_folder_id'],

                'name' =>
                    (string)
                    $row['inventory_name'],

                'description' =>
                    (string)
                    $row['inventory_description'],

                'assetId' =>
                    (string)
                    $row['asset_id'],

                'assetType' =>
                    $row['asset_type'],

                'inventoryType' =>
                    $row['inventory_type'],

                'creatorId' =>
                    (string)
                    $row['creator_id'],

                'creationDate' =>
                    $row['creation_date']
            ];

        }


        mysqli_stmt_close(
            $itemStmt
        );


        mysqli_close(
            $con
        );


        return [
            'folders' =>
                $folders,

            'items' =>
                $items
        ];

    }
    catch (Throwable $e) {

        mysqli_close(
            $con
        );


        throw $e;

    }

}


/*
 * ============================================================
 * CLEAN TREE HELPERS
 * ============================================================
 */


function cleanTargetExists(
    array $state,
    string $target
): bool {

    return (
        $target ===
        'clean-root'
        ||
        isset(
            $state['folders'][$target]
        )
    );

}


function cleanFolderDescendants(
    array $folders,
    string $startId
): array {

    $result =
        [];


    $queue =
        [
            $startId
        ];


    while (
        count(
            $queue
        ) > 0
    ) {

        $current =
            array_shift(
                $queue
            );


        if (
            isset(
                $result[$current]
            )
        ) {

            continue;

        }


        $result[$current] =
            true;


        foreach(
            $folders
            as
            $id =>
            $folder
        ) {

            if (
                (string)(
                    $folder['parentId'] ??
                    ''
                )
                ===
                $current
            ) {

                $queue[] =
                    $id;

            }

        }

    }


    return
        array_keys(
            $result
        );

}


/*
 * ============================================================
 * API
 * ============================================================
 */


$apiAction =
    trim(
        (string)(
            $_GET['api'] ??
            ''
        )
    );


if (
    $apiAction !==
    ''
) {

    try {

        $state =
            cleanLoadState(
                $storageFile,
                $principalId
            );


        /*
         * --------------------------------------------------------
         * READ STATE
         * --------------------------------------------------------
         */


        if (
            $apiAction ===
            'state'
        ) {

            cleanJson([
                'ok' =>
                    true,

                'state' =>
                    $state,

                'mainFolders' =>
                    0,

                'mainItems' =>
                    0,

                'cleanFolders' =>
                    count(
                        $state['folders']
                    ),

                'cleanItems' =>
                    count(
                        $state['items']
                    ),

                'leftOut' =>
                    0
            ]);

        }


        /*
         * --------------------------------------------------------
         * ALL REMAINING ACTIONS ARE POST
         * --------------------------------------------------------
         */


        if (
            $_SERVER['REQUEST_METHOD']
            !==
            'POST'
        ) {

            cleanJson(
                [
                    'ok' =>
                        false,

                    'error' =>
                        'POST request required.'
                ],
                405
            );

        }


        $postedCsrf =
            (string)(
                $_SERVER['HTTP_X_CSRF_TOKEN'] ??
                ''
            );


        if (
            $postedCsrf ===
            ''
            ||
            !hash_equals(
                $csrfToken,
                $postedCsrf
            )
        ) {

            cleanJson(
                [
                    'ok' =>
                        false,

                    'error' =>
                        'Security token expired. Reload the page.'
                ],
                403
            );

        }


        $post =
            cleanPostData();


        /*
         * --------------------------------------------------------
         * ADD LIVE ITEM TO CLEAN
         * --------------------------------------------------------
         */


        if (
            $apiAction ===
            'add-item'
        ) {

            $sourceId =
                trim(
                    (string)(
                        $post['sourceId'] ??
                        ''
                    )
                );


            $target =
                trim(
                    (string)(
                        $post['targetFolder'] ??
                        'clean-root'
                    )
                );


            if (
                !cleanValidUuid(
                    $sourceId
                )
            ) {

                throw new Exception(
                    'Invalid inventory item.'
                );

            }


            if (
                !cleanTargetExists(
                    $state,
                    $target
                )
            ) {

                throw new Exception(
                    'Clean Inventory destination does not exist.'
                );

            }


            $main =
                cleanLoadLiveInventory(
                    $principalId
                );


            if (
                !isset(
                    $main['items'][$sourceId]
                )
            ) {

                throw new Exception(
                    'That inventory item does not belong to the signed-in avatar.'
                );

            }


            $item =
                $main['items'][$sourceId];


            $item['folderId'] =
                $target;


            $state['items'][$sourceId] =
                $item;


            cleanSaveState(
                $storageFile,
                $state
            );


            cleanJson([
                'ok' =>
                    true
            ]);

        }


        /*
         * --------------------------------------------------------
         * ADD LIVE FOLDER + COMPLETE SUBTREE TO CLEAN
         * --------------------------------------------------------
         */


        if (
            $apiAction ===
            'add-folder'
        ) {

            $sourceId =
                trim(
                    (string)(
                        $post['sourceId'] ??
                        ''
                    )
                );


            $target =
                trim(
                    (string)(
                        $post['targetFolder'] ??
                        'clean-root'
                    )
                );


            if (
                !cleanValidUuid(
                    $sourceId
                )
            ) {

                throw new Exception(
                    'Invalid inventory folder.'
                );

            }


            if (
                !cleanTargetExists(
                    $state,
                    $target
                )
            ) {

                throw new Exception(
                    'Clean Inventory destination does not exist.'
                );

            }


            $main =
                cleanLoadLiveInventory(
                    $principalId
                );


            if (
                !isset(
                    $main['folders'][$sourceId]
                )
            ) {

                throw new Exception(
                    'That folder does not belong to the signed-in avatar.'
                );

            }


            $subtree =
                cleanFolderDescendants(
                    $main['folders'],
                    $sourceId
                );


            $subtreeMap =
                array_fill_keys(
                    $subtree,
                    true
                );


            foreach(
                $subtree
                as
                $folderId
            ) {

                $folder =
                    $main['folders'][$folderId];


                $parent =
                    (string)
                    $folder['parentId'];


                if (
                    $folderId ===
                    $sourceId
                ) {

                    $parent =
                        $target;

                }
                elseif (
                    !isset(
                        $subtreeMap[$parent]
                    )
                ) {

                    $parent =
                        $target;

                }


                $state['folders'][$folderId] = [
                    'id' =>
                        $folderId,

                    'sourceId' =>
                        $folderId,

                    'parentId' =>
                        $parent,

                    'name' =>
                        (string)
                        $folder['name'],

                    'type' =>
                        $folder['type'],

                    'custom' =>
                        false
                ];

            }


            foreach(
                $main['items']
                as
                $itemId =>
                $item
            ) {

                $folderId =
                    (string)
                    $item['folderId'];


                if (
                    isset(
                        $subtreeMap[$folderId]
                    )
                ) {

                    $state['items'][$itemId] =
                        $item;

                }

            }


            cleanSaveState(
                $storageFile,
                $state
            );


            cleanJson([
                'ok' =>
                    true
            ]);

        }


        /*
         * --------------------------------------------------------
         * CREATE CLEAN FOLDER
         * --------------------------------------------------------
         */


        if (
            $apiAction ===
            'create-folder'
        ) {

            $name =
                cleanSafeFolderName(
                    (string)(
                        $post['name'] ??
                        ''
                    )
                );


            $parent =
                trim(
                    (string)(
                        $post['parentId'] ??
                        'clean-root'
                    )
                );


            if (
                !cleanTargetExists(
                    $state,
                    $parent
                )
            ) {

                throw new Exception(
                    'Selected parent folder does not exist.'
                );

            }


            $id =
                'custom-' .
                bin2hex(
                    random_bytes(16)
                );


            $state['folders'][$id] = [
                'id' =>
                    $id,

                'sourceId' =>
                    '',

                'parentId' =>
                    $parent,

                'name' =>
                    $name,

                'type' =>
                    null,

                'custom' =>
                    true
            ];


            cleanSaveState(
                $storageFile,
                $state
            );


            cleanJson([
                'ok' =>
                    true,

                'id' =>
                    $id
            ]);

        }


        /*
         * --------------------------------------------------------
         * RENAME CLEAN FOLDER
         * --------------------------------------------------------
         */


        if (
            $apiAction ===
            'rename-folder'
        ) {

            $id =
                trim(
                    (string)(
                        $post['id'] ??
                        ''
                    )
                );


            $name =
                cleanSafeFolderName(
                    (string)(
                        $post['name'] ??
                        ''
                    )
                );


            if (
                !isset(
                    $state['folders'][$id]
                )
            ) {

                throw new Exception(
                    'Select a Clean Inventory folder first.'
                );

            }


            $state['folders'][$id]['name'] =
                $name;


            cleanSaveState(
                $storageFile,
                $state
            );


            cleanJson([
                'ok' =>
                    true
            ]);

        }


        /*
         * --------------------------------------------------------
         * REMOVE CLEAN ITEM
         * --------------------------------------------------------
         */


        if (
            $apiAction ===
            'remove-item'
        ) {

            $id =
                trim(
                    (string)(
                        $post['id'] ??
                        ''
                    )
                );


            if (
                isset(
                    $state['items'][$id]
                )
            ) {

                unset(
                    $state['items'][$id]
                );

            }


            cleanSaveState(
                $storageFile,
                $state
            );


            cleanJson([
                'ok' =>
                    true
            ]);

        }


        /*
         * --------------------------------------------------------
         * REMOVE CLEAN FOLDER + ITS CLEAN CONTENTS
         * --------------------------------------------------------
         */


        if (
            $apiAction ===
            'remove-folder'
        ) {

            $id =
                trim(
                    (string)(
                        $post['id'] ??
                        ''
                    )
                );


            if (
                !isset(
                    $state['folders'][$id]
                )
            ) {

                throw new Exception(
                    'Select a Clean Inventory folder first.'
                );

            }


            $subtree =
                cleanFolderDescendants(
                    $state['folders'],
                    $id
                );


            $subtreeMap =
                array_fill_keys(
                    $subtree,
                    true
                );


            foreach(
                $subtree
                as
                $folderId
            ) {

                unset(
                    $state['folders'][$folderId]
                );

            }


            foreach(
                $state['items']
                as
                $itemId =>
                $item
            ) {

                if (
                    isset(
                        $subtreeMap[
                            (string)(
                                $item['folderId'] ??
                                ''
                            )
                        ]
                    )
                ) {

                    unset(
                        $state['items'][$itemId]
                    );

                }

            }


            cleanSaveState(
                $storageFile,
                $state
            );


            cleanJson([
                'ok' =>
                    true
            ]);

        }


        /*
         * --------------------------------------------------------
         * CLEAR CLEAN WORKSPACE
         * --------------------------------------------------------
         */


        if (
            $apiAction ===
            'clear'
        ) {

            $state =
                cleanDefaultState(
                    $principalId
                );


            cleanSaveState(
                $storageFile,
                $state
            );


            cleanJson([
                'ok' =>
                    true
            ]);

        }


        cleanJson(
            [
                'ok' =>
                    false,

                'error' =>
                    'Unknown Clean Inventory request.'
            ],
            400
        );

    }
    catch (Throwable $e) {

        cleanJson(
            [
                'ok' =>
                    false,

                'error' =>
                    $e->getMessage()
            ],
            500
        );

    }

}


header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>
Grid - Clean Inventory
</title>
<!-- AUSTRALIA SAFETY IAR STYLE V1.1 START -->



<!-- AUSTRALIA SAFETY IAR STYLE V1.1 END -->
<link
    rel="stylesheet"
    href="/Other/australia-modal.css?v=1">
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-sentinel-icons-v1.css?v=20260902-sentinel-v1">












<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-panel-v1.css?v=8">

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-regions-v5.css?v=6">

<style id="australia-clean-picture2-v6-css">

html,
body{
    min-height:100%;
    background:transparent !important;
}

body{
    margin:0;
}

.clean-shell{
    width:100%;
    max-width:none;
    min-width:0;
    margin:0;
    padding:0;
    background:transparent !important;
}

.summary-grid{
    margin-bottom:12px;
}

.summary-number{
    display:block;
}

/* AUSTRALIA CLEAN PANEL ALIGNMENT V6.1 */

.workspace{
    display:grid;
    grid-template-columns:
        minmax(0,1fr)
        minmax(0,1fr);
    gap:12px;
    align-items:stretch;
}

.inventory-side{
    min-width:0;
    display:flex;
    flex-direction:column;
    overflow:hidden;
}

.side-heading{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
}

.clean-section-title,
.clean-title-with-icon{
    display:inline-flex;
    align-items:center;
    gap:8px;
    margin:0;
}

.clean-section-icon,
.clean-title-icon{
    width:18px;
    height:18px;
    object-fit:contain;
}

.side-heading h2{
    margin:0;
    font-size:11px;
    font-weight:900;
    letter-spacing:.05em;
}

.side-subtitle{
    margin-top:2px;
    opacity:.60;
    font-size:8px;
    font-weight:700;
}

.selected-target{
    min-height:30px;
    margin:8px 9px 0;
    padding:7px 9px;
    border:
        1px solid
        rgba(214,164,59,.22);
    border-radius:4px;
    background:
        rgba(1,5,7,.44);
    color:
        rgba(216,224,226,.62);
    font-size:9px;
}

.selected-target strong{
    color:#f1cf79;
}

.php-level{
    width:auto;
    min-width:104px;
    min-height:24px;
    margin:8px 9px 0;
    padding:3px 9px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:5px;
    border:
        1px solid
        rgba(214,164,59,.24);
    border-radius:4px;
    background:#0f110e;
    color:#d8dfe1;
    box-shadow:none;
    font-size:8px;
    font-weight:800;
}

.php-level strong{
    margin:0;
    color:#f1cf79;
    font-size:8px;
}

.side-tools,
.safety-actions{
    min-height:34px;
    padding:5px 8px;
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:6px;
}

.side-tools{
    border-bottom:
        1px solid
        rgba(255,255,255,.05);
}

.fs2-tool{
    min-height:24px !important;
    padding:3px 9px !important;
    font-size:8px !important;
}

.side-columns{
    height:auto;
    min-height:560px;
    flex:1 1 auto;
    display:grid;
    grid-template-columns:
        260px
        minmax(0,1fr);
    gap:8px;
    padding:8px;
}

.folder-panel,
.item-panel{
    min-width:0;
    min-height:0;
    display:flex;
    flex-direction:column;
    overflow:hidden;
    border:
        1px solid
        rgba(255,255,255,.05);
    border-radius:3px;
    background:
        rgba(255,255,255,.012);
}

.inner-heading{
    min-height:28px;
    padding:0 8px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    border-bottom:
        1px solid
        rgba(255,255,255,.05);
    color:#d9ae46;
    font-size:8px;
    font-weight:900;
}

.folder-tree,
.item-list{
    flex:1;
    min-height:0;
    overflow:auto;
    padding:4px;
    scrollbar-width:thin;
    scrollbar-color:
        #8d6b2b
        #071015;
}

.folder-node{
    margin:1px 0;
}

.folder-node.ag-collapsed >
.folder-children{
    display:none !important;
}

.folder-node.ag-expanded >
.folder-children,
.folder-node.ag-search-open >
.folder-children{
    display:block !important;
}

.folder-button{
    width:100%;
    min-height:27px;
    display:flex;
    align-items:center;
    gap:6px;
    padding:3px 5px;
    border:
        1px solid
        transparent !important;
    border-radius:2px;
    background:
        transparent !important;
    box-shadow:none !important;
    color:
        rgba(220,227,230,.72) !important;
    text-align:left;
    cursor:pointer;
}

.folder-button:hover,
.item-row:hover{
    background:
        rgba(214,164,59,.055) !important;
    border-color:
        rgba(214,164,59,.14) !important;
}

.folder-button.active,
.item-row.active{
    background:
        rgba(214,164,59,.13) !important;
    border-color:
        rgba(214,164,59,.22) !important;
    color:#f1cf79 !important;
}

.folder-button.ag-has-children::after{
    content:"\25B8";
    margin-left:auto;
    color:
        rgba(220,227,230,.48);
    font-size:10px;
}

.folder-node.ag-expanded >
.folder-button.ag-has-children::after,
.folder-node.ag-search-open >
.folder-button.ag-has-children::after{
    content:"\25BE";
    color:#f1cf79;
}

#cleanFolderTree.ag-root-collapsed >
.folder-children{
    display:none !important;
}

#cleanFolderTree.ag-root-search-open >
.folder-children{
    display:block !important;
}

#cleanFolderTree >
.folder-button.ag-root-has-children::after{
    content:"\25BE";
    margin-left:auto;
    color:#f1cf79;
}

#cleanFolderTree.ag-root-collapsed >
.folder-button.ag-root-has-children::after{
    content:"\25B8";
    color:
        rgba(220,227,230,.48);
}

.folder-children{
    margin-left:13px;
    padding-left:4px;
    border-left:
        1px solid
        rgba(255,255,255,.05);
}

.folder-icon{
    width:22px !important;
    height:22px !important;
    flex:0 0 22px !important;
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
    border:0 !important;
    background:transparent !important;
    box-shadow:none !important;
}

.folder-name{
    min-width:0 !important;
    flex:1 !important;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    font-size:9px;
    font-weight:700;
}

.folder-count,
.ag-folder-count{
    margin-left:auto !important;
    border:0 !important;
    background:transparent !important;
    color:#f1cf79 !important;
    font-size:8px !important;
    font-weight:900 !important;
    text-shadow:none !important;
}

.folder-count.ag-folder-empty-count,
.ag-folder-count.zero{
    color:
        rgba(220,227,230,.42) !important;
}

.item-row{
    width:100%;
    min-height:38px;
    display:grid;
    grid-template-columns:
        36px
        minmax(0,1fr);
    gap:7px;
    align-items:center;
    margin:1px 0;
    padding:4px 6px;
    border:
        1px solid
        transparent !important;
    border-radius:2px;
    background:transparent !important;
    box-shadow:none !important;
    color:#dce3e6 !important;
    text-align:left;
    cursor:pointer;
}

.item-icon{
    width:34px !important;
    height:34px !important;
    min-width:34px !important;
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
    border:0 !important;
    background:transparent !important;
    box-shadow:none !important;
}

.item-name{
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    font-size:9px;
    font-weight:900;
}

.item-desc{
    margin-top:2px;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    color:
        rgba(220,227,230,.48);
    font-size:8px;
}

.empty-state{
    min-height:120px;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:14px;
    color:
        rgba(220,227,230,.46);
    font-size:9px;
    text-align:center;
}

.drop-hint{
    margin:0 8px 8px;
    padding:8px 10px;
    border:
        1px dashed
        rgba(214,164,59,.22);
    border-radius:3px;
    background:
        rgba(214,164,59,.025);
    color:
        rgba(220,227,230,.48);
    font-size:8px;
    text-align:center;
}

.drop-hint.drag-over{
    border-color:
        rgba(240,186,61,.72);
    background:
        rgba(214,164,59,.08);
    color:#f1cf79;
}

.clean-warning{
    margin:10px 0 12px;
    padding:9px 11px;

    border:
        1px solid
        rgba(214,164,59,.22);

    border-left:
        3px solid
        #d9ae46;

    border-radius:4px;

    background:
        #0f110e !important;

    color:
        rgba(220,227,230,.65);

    box-shadow:none !important;

    font-size:8px;
    line-height:1.45;
}

.clean-warning strong{
    display:block;
    margin-bottom:2px;

    color:#f1cf79;

    font-size:9px;
    font-weight:900;
}

.status-bar{
    margin-top:12px;
    padding:8px 10px;

    color:
        rgba(220,227,230,.65);

    font-size:8px;
}

.status-bar.error{
    color:#e58b91;
}

.ag-inventory-search{
    display:grid;
    grid-template-columns:
        minmax(0,1fr)
        auto;
    gap:6px;
    align-items:center;
    padding:8px 9px 0;
}

.fs2-search{
    width:100%;
    height:31px;
    padding:0 12px;
    border:
        1px solid
        rgba(214,164,59,.32);
    border-radius:15px;
    outline:none;
    background:
        rgba(1,5,7,.72);
    color:#e5eaec;
    font-size:10px;
}

.fs2-search:focus{
    border-color:
        rgba(240,186,61,.72);
}

.ag-search-result,
.ag-manual-refresh-note{
    color:
        rgba(220,227,230,.44);
    font-size:8px;
}

.ag-search-result{
    grid-column:1/-1;
}

.ag-breadcrumb{
    margin:6px 9px 8px;
    padding:6px 9px;
    border:
        1px solid
        rgba(214,164,59,.16);
    border-radius:3px;
    background:
        rgba(1,5,7,.36);
    color:
        rgba(220,227,230,.55);
    font-size:8px;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.ag-breadcrumb strong{
    color:#f1cf79;
}

.ag-type-totals-split,
.ag-preview-columns,
.ag-dup-split{
    display:grid;
    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );
    gap:8px;
}

.ag-type-totals-split{
    margin:0 0 12px;
}

.ag-type-panel,
.ag-preview-side,
.ag-preview-stat{
    min-width:0;
    padding:9px;
}

.ag-type-heading,
.ag-final-preview-head,
.ag-dup-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
}

.ag-type-title,
.ag-preview-side-title,
.ag-preview-change-title,
.ag-dup-side-title,
.ag-dup-section-title{
    color:#d9ae46;
    font-size:8px;
    font-weight:900;
}

.ag-type-note,
.ag-dup-note,
.ag-final-preview-head p{
    opacity:.60;
    font-size:8px;
}

.ag-type-list{
    display:grid;
    grid-template-columns:
        repeat(
            auto-fit,
            minmax(125px,1fr)
        );
    gap:5px;
}

.ag-type-chip,
.ag-dup-side,
.ag-dup-metric,
.ag-dup-group,
.ag-preview-type-chip,
.safety-file-info > div,
.ag-preview-message{
    border:
        1px solid
        rgba(255,255,255,.05);
    border-radius:3px;
    background:
        rgba(255,255,255,.012);
}

.ag-type-chip{
    display:grid;
    grid-template-columns:
        20px
        minmax(0,1fr)
        auto;
    align-items:center;
    gap:5px;
    padding:4px 6px;
}

.ag-type-label{
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    font-size:8px;
}

.ag-type-number,
.ag-preview-type-chip strong,
.ag-preview-secondary strong,
.ag-preview-stat strong{
    color:#f1cf79;
    font-weight:900;
}

.ag-clean-sentinel-img{
    display:block !important;
    object-fit:contain !important;
    pointer-events:none !important;
}

.ag-clean-icon-folder{
    width:18px !important;
    height:18px !important;
}

.ag-clean-icon-item{
    width:30px !important;
    height:30px !important;
}

.ag-clean-icon-type,
.ag-clean-icon-preview,
.ag-clean-icon-duplicate{
    width:16px !important;
    height:16px !important;
}

.ag-duplicate-panel,
.ag-final-preview,
.final-zone{
    margin:12px 0;
}

.ag-dup-main-title{
    font-size:11px;
    font-weight:900;
}

.ag-dup-split{
    padding:10px;
}

.ag-dup-side{
    padding:8px;
}

.ag-dup-results{
    max-height:720px;
    overflow:auto;
}

.ag-dup-placeholder,
.ag-dup-none,
.ag-dup-error{
    padding:8px;
    font-size:8px;
}

.ag-dup-error{
    color:#e58b91;
}

.ag-dup-metrics,
.ag-preview-change-grid{
    display:grid;
    grid-template-columns:
        repeat(
            4,
            minmax(0,1fr)
        );
    gap:6px;
}

.ag-dup-metric{
    padding:6px;
    text-align:center;
}

.ag-dup-metric strong{
    display:block;
    color:#f1cf79;
    font-size:13px;
}

.ag-dup-metric span,
.ag-preview-stat span{
    opacity:.50;
    font-size:7px;
}

.ag-dup-group{
    margin:5px 0;
    overflow:hidden;
}

.ag-dup-group > summary{
    display:grid;
    grid-template-columns:
        24px
        minmax(0,1fr)
        auto
        auto;
    gap:6px;
    align-items:center;
    padding:6px 7px;
    cursor:pointer;
}

.ag-dup-item{
    display:grid;
    grid-template-columns:
        minmax(0,1fr)
        auto
        auto;
    gap:8px;
    align-items:center;
    padding:6px 7px;
    border-bottom:
        1px solid
        rgba(255,255,255,.04);
    font-size:8px;
}

/* AUSTRALIA CLEAN CHARCOAL PREVIEW V7 START */


/* ==========================================================
   FINAL CLEAN INVENTORY PREVIEW
   ========================================================== */

#agCleanFinalPreview{
    width:100%;
    max-width:none;

    margin:12px 0;
    padding:12px;

    box-sizing:border-box;

    border:
        1px solid
        rgba(214,164,59,.28) !important;

    border-radius:5px;

    background:
        #0f110e !important;

    box-shadow:
        none !important;
}


.ag-final-preview-head{
    display:flex;

    align-items:center;
    justify-content:space-between;

    gap:12px;

    margin:0 0 10px;

    padding:
        0 0 9px;

    border-bottom:
        1px solid
        rgba(255,255,255,.05);
}


.ag-final-preview-kicker{
    margin:
        0 0 3px;

    color:#d9ae46;

    font-size:8px;
    font-weight:900;

    letter-spacing:.06em;
}


.ag-final-preview h2,
.final-zone > h2{
    margin:0;

    color:#e5eaec;

    font-size:12px;
    font-weight:900;
}


.ag-final-preview-head p{
    margin:
        4px 0 0;

    color:
        rgba(220,227,230,.48);

    opacity:1;

    font-size:8px;
}


.ag-preview-only-badge{
    flex:0 0 auto;

    padding:
        4px 7px;

    border:
        1px solid
        rgba(214,164,59,.30);

    border-radius:3px;

    background:
        #0f110e;

    color:#f1cf79;

    font-size:7px;
    font-weight:900;
}


/* ==========================================================
   LIVE + CLEAN INVENTORY CARDS
   ========================================================== */

.ag-preview-columns{
    display:grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap:10px;

    align-items:stretch;
}


.ag-preview-side{
    min-width:0;
    min-height:102px;

    padding:
        10px 12px !important;

    display:grid !important;

    grid-template-columns:
        minmax(0,1fr)
        auto;

    grid-template-areas:
        "title value"
        "label value"
        "secondary secondary"
        "types types";

    column-gap:12px;
    row-gap:2px;

    align-content:start;

    border:
        1px solid
        rgba(214,164,59,.16) !important;

    border-left:
        2px solid
        rgba(214,164,59,.55) !important;

    border-radius:4px;

    background:
        rgba(255,255,255,.012) !important;

    box-shadow:
        none !important;
}


.ag-preview-side.clean{
    border-color:
        rgba(214,164,59,.24) !important;
}


.ag-preview-side-title{
    grid-area:title;

    color:#d9ae46;

    font-size:8px;
    font-weight:900;
}


.ag-preview-big-number{
    grid-area:value;

    margin:0;

    align-self:center;

    color:#f1cf79;

    font-size:24px;
    font-weight:900;

    line-height:1;
}


.ag-preview-big-label{
    grid-area:label;

    color:
        rgba(220,227,230,.62);

    font-size:8px;

    line-height:1.2;
}


.ag-preview-secondary{
    grid-area:secondary;

    display:flex;

    align-items:center;
    justify-content:space-between;

    gap:10px;

    margin-top:7px;

    padding-top:7px;

    border-top:
        1px solid
        rgba(255,255,255,.05);

    color:
        rgba(220,227,230,.62);

    font-size:8px;
}


.ag-preview-secondary strong{
    color:#f1cf79;

    font-size:10px;
    font-weight:900;
}


.ag-preview-types{
    grid-area:types;

    display:flex;

    flex-wrap:wrap;

    gap:4px;

    margin-top:5px;
}


.ag-preview-types:empty{
    display:none !important;
}


.ag-preview-type-chip{
    display:inline-grid;

    grid-template-columns:
        auto
        auto
        auto;

    gap:4px;

    padding:
        3px 5px;

    border:
        1px solid
        rgba(255,255,255,.05);

    border-radius:3px;

    background:
        rgba(255,255,255,.012);
}


/* ==========================================================
   CHANGE SUMMARY
   ========================================================== */

.ag-preview-change-title{
    margin:
        10px 0 6px;

    color:#d9ae46;

    font-size:8px;
    font-weight:900;
}


.ag-preview-change-grid{
    display:grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0,1fr)
        );

    gap:8px;
}


.ag-preview-stat{
    min-width:0;
    min-height:52px;

    padding:
        8px 10px !important;

    display:flex !important;

    flex-direction:column;

    align-items:center;
    justify-content:center;

    gap:3px;

    border:
        1px solid
        rgba(214,164,59,.12) !important;

    border-radius:4px;

    background:
        rgba(255,255,255,.012) !important;

    box-shadow:
        none !important;

    text-align:center;
}


.ag-preview-stat strong{
    display:block;

    color:#f1cf79;

    font-size:16px;
    font-weight:900;

    line-height:1;
}


.ag-preview-stat.leftout strong,
.ag-preview-stat.removed strong{
    color:#e58b91;
}


.ag-preview-stat span{
    color:
        rgba(220,227,230,.46);

    opacity:1;

    font-size:7px;

    line-height:1.1;
}


/* ==========================================================
   RETAINED BAR + STATUS
   ========================================================== */

.ag-preview-bar{
    height:6px;

    margin-top:8px;

    overflow:hidden;

    border-radius:999px;

    background:
        rgba(205,90,90,.10);
}


.ag-preview-bar-kept{
    width:0;
    height:100%;

    background:#d9ae46;
}


.ag-preview-bar-labels{
    display:flex;

    justify-content:space-between;

    margin-top:3px;

    color:
        rgba(220,227,230,.42);

    opacity:1;

    font-size:7px;
}


.ag-preview-message{
    min-height:28px;

    margin-top:7px;

    padding:
        6px 8px;

    display:flex;

    align-items:center;

    border:
        1px solid
        rgba(255,255,255,.05);

    border-radius:3px;

    background:
        rgba(255,255,255,.012);

    color:
        rgba(220,227,230,.55);

    font-size:8px;
}


/* ==========================================================
   LIVE INVENTORY PROTECTION
   ========================================================== */

.ag-preview-safety{
    min-height:0 !important;

    margin-top:8px;

    padding:
        9px 10px !important;

    display:grid !important;

    grid-template-columns:
        28px
        minmax(0,1fr);

    gap:9px;

    align-items:center;

    border:
        1px solid
        rgba(214,164,59,.18) !important;

    border-left:
        3px solid
        rgba(214,164,59,.55) !important;

    border-radius:4px;

    background:
        #0f110e !important;

    box-shadow:
        none !important;
}


.ag-preview-lock{
    display:flex;

    align-items:center;
    justify-content:center;
}


.ag-preview-safety strong{
    color:#e5eaec;

    font-size:9px;
    font-weight:900;
}


.ag-preview-safety p{
    margin:
        2px 0 0;

    color:
        rgba(220,227,230,.50);

    font-size:8px;

    line-height:1.35;
}


/* ==========================================================
   SAFETY INVENTORY IAR
   ========================================================== */

.final-zone{
    margin:12px 0;

    padding:12px;

    border:
        1px solid
        rgba(214,164,59,.28) !important;

    border-radius:5px;

    background:
        #0f110e !important;

    box-shadow:
        none !important;

    text-align:left;
}


.final-zone > h2{
    display:flex;

    align-items:center;
    justify-content:flex-start;

    gap:7px;
}


.final-zone > p{
    max-width:none;

    margin:
        4px 0 10px;

    color:
        rgba(220,227,230,.48);

    opacity:1;

    font-size:8px;
}


.safety-iar-panel{
    width:100%;
    max-width:none;

    margin:
        10px 0;

    overflow:hidden;

    border:
        1px solid
        rgba(214,164,59,.18) !important;

    border-radius:4px;

    background:
        rgba(255,255,255,.012) !important;

    box-shadow:
        none !important;
}


.safety-top-row{
    padding:
        8px 10px;

    display:flex;

    justify-content:space-between;
    align-items:center;

    border-bottom:
        1px solid
        rgba(255,255,255,.05);
}


.safety-small-label{
    color:#d9ae46;

    font-size:8px;
    font-weight:900;
}


.safety-status-badge{
    display:inline-flex;

    padding:
        3px 7px;

    border:
        1px solid;

    border-radius:999px;

    font-size:8px;
    font-weight:900;
}


.safety-waiting{
    color:#d9ae46;

    border-color:
        rgba(214,164,59,.24);

    background:
        rgba(214,164,59,.055);
}


.safety-processing{
    color:#8fc0df;
}


.safety-good{
    color:#91caa0;
}


.safety-bad{
    color:#e58b91;
}


.safety-message{
    margin:
        8px 10px 0;

    padding:
        7px 9px;

    border:
        1px solid
        rgba(255,255,255,.05);

    border-left:
        2px solid
        rgba(214,164,59,.50);

    border-radius:3px;

    background:
        #0f110e;

    color:
        rgba(220,227,230,.62);

    font-size:8px;

    text-align:left;
}


.safety-file-info{
    display:grid;

    grid-template-columns:
        1fr
        1fr;

    gap:6px;

    margin:
        8px 10px 0;
}


.safety-file-info > div{
    padding:
        7px 9px;

    border:
        1px solid
        rgba(255,255,255,.05);

    border-radius:3px;

    background:
        #0f110e;

    text-align:left;
}


.safety-file-info span{
    display:block;

    color:#d9ae46;

    font-size:7px;
    font-weight:900;
}


.safety-actions{
    padding:
        8px 10px;
}


.safety-verified{
    width:100%;
    max-width:none;

    margin:
        8px 0;

    padding:
        9px 10px;

    display:flex;

    align-items:center;

    gap:10px;

    border:
        1px solid
        rgba(214,164,59,.18);

    border-radius:4px;

    background:
        rgba(255,255,255,.012);

    box-shadow:none;

    text-align:left;
}


.safety-check{
    width:30px;
    height:30px;

    display:flex;

    align-items:center;
    justify-content:center;
}


.safety-check-icon{
    width:22px;
    height:22px;
}


.safety-divider{
    height:1px;

    width:100%;
    max-width:none;

    margin:
        12px 0;

    background:
        rgba(255,255,255,.05);
}


.safety-spinner{
    width:20px;
    height:20px;

    border:
        2px solid
        rgba(255,255,255,.10);

    border-top-color:#d9ae46;

    border-radius:50%;

    animation:
        safetySpin
        1s
        linear
        infinite;
}


@keyframes safetySpin{

    to{
        transform:
            rotate(360deg);
    }
}


/*
   Respect the existing hidden state.
   Prevent CSS display rules from exposing these early.
*/

#safetySpinner[hidden],
#safetyFileInfo[hidden],
#downloadSafetyIar[hidden],
#safetyVerifiedBox[hidden]{
    display:none !important;
}


.final-button{
    min-width:330px;
}


.final-button:disabled{
    opacity:.45;

    pointer-events:none;
}


/* AUSTRALIA CLEAN CHARCOAL PREVIEW V7 END */

@media(max-width:1250px){

    .workspace{
        grid-template-columns:1fr;
    }
}

@media(max-width:900px){

    .ag-type-totals-split,
    .ag-preview-columns,
    .ag-dup-split{
        grid-template-columns:1fr;
    }

    .ag-preview-change-grid{
        grid-template-columns:
            repeat(
                2,
                minmax(0,1fr)
            );
    }
}

@media(max-width:760px){

    .side-columns{
        height:auto;
        grid-template-columns:1fr;
    }

    .folder-tree,
    .item-list{
        max-height:500px;
    }

    .ag-inventory-search,
    .safety-file-info{
        grid-template-columns:1fr;
    }

    .ag-search-clear,
    .final-button{
        width:100%;
        min-width:0;
    }
}



</style>

</head>


<body>


<main class="cp-page rp-page clean-shell">


<?php
$level = ag_user_level($session);
$isAdmin = ag_is_admin($session);

$siteHeaderKicker = $isAdmin ? "ADMINISTRATION" : "ACCOUNT";
$siteHeaderTitle = "
CLEAN INVENTORY
";
$siteHeaderRole = ((int)$level >= 250) ? "GRID OWNER" : ($isAdmin ? "ADMIN" : "USER");
$siteHeaderLevel = $level;
$siteHeaderButton = "BACK";
$siteHeaderLink = "/Other/user-inventory.php";
require_once __DIR__ . "/includes/site-header.php";
?>



<section class="rp-summary summary-grid">


<div class="rp-summary-item summary-card">

<div class="summary-label">
    MAIN ITEMS
</div>

<div
    id="mainItems"
    class="summary-number">

    0

</div>

</div>


<div class="rp-summary-item summary-card">

<div class="summary-label">
    CLEAN ITEMS KEPT
</div>

<div
    id="cleanItems"
    class="summary-number">

    0

</div>

</div>


<div class="rp-summary-item summary-card">

<div class="summary-label">
    CLEAN FOLDERS
</div>

<div
    id="cleanFolders"
    class="summary-number">

    0

</div>

</div>


<div class="rp-summary-item summary-card left-out">

<div class="summary-label">
    ITEMS LEFT OUT
</div>

<div
    id="leftOut"
    class="summary-number">

    0

</div>

</div>


</section>



<section class="workspace">


<!-- ========================================================
     MAIN INVENTORY
     ======================================================== -->


<section
    id="mainSide"
    class="rp-region inventory-side">


<div class="rp-region-header side-heading">

<div>

<h2 class="clean-section-title"><img class="ag-sentinel-direct-icon ag-sentinel-sm clean-section-icon" src="/Other/assets/icons/sentinel/inventory.png" alt="" aria-hidden="true" draggable="false" decoding="async"><span>MAIN INVENTORY</span></h2>

<div class="side-subtitle">
    CURRENT LIVE INVENTORY — READ ONLY
</div>

</div>

</div>


<div class="selected-target">

Selected Main Folder:

<strong id="mainSelectedName">
    None
</strong>

</div>

<div class="php-level">

    <strong>
        <?=((int)$level >= 250 ? 'GRID OWNER' : ($isAdmin ? 'ADMIN' : 'USER'))?>
    </strong>

    USER LEVEL
    <?=ag_h($level)?>

</div>



<div class="side-tools">

<button
    id="addFolderButton"
    type="button" class="cp-button fs2-tool">

    ADD SELECTED FOLDER TO CLEAN

</button>


<button
    id="addItemButton"
    type="button" class="cp-button fs2-tool">

    ADD SELECTED ITEM TO CLEAN

</button>

</div>


<div class="side-columns">


<div class="folder-panel">

<div class="inner-heading">

<span>
    FOLDERS
</span>

<span id="mainFolderCount">
    0
</span>

</div>


<div
    id="mainFolders"
    class="folder-tree">

    Loading Main Inventory...

</div>

</div>



<div class="item-panel">

<div class="inner-heading">

<span id="mainCurrentFolder">
    ITEMS
</span>

<span id="mainVisibleItems">
    0
</span>

</div>


<div
    id="mainItemList"
    class="item-list">

<div class="empty-state">
    Select a Main Inventory folder.
</div>

</div>

</div>


</div>


<div
    id="mainDropZone"
    class="drop-hint">

    Drag an item or folder FROM CLEAN INVENTORY back here
    to remove it from the Clean staging workspace.

</div>


</section>



<!-- ========================================================
     CLEAN INVENTORY
     ======================================================== -->


<section
    id="cleanSide"
    class="rp-region inventory-side">


<div class="rp-region-header side-heading">

<div>

<h2 class="clean-section-title"><img class="ag-sentinel-direct-icon ag-sentinel-sm clean-section-icon" src="/Other/assets/icons/sentinel/inventory.png" alt="" aria-hidden="true" draggable="false" decoding="async"><span>CLEAN INVENTORY</span></h2>

<div class="side-subtitle">
    SAFE STAGING WORKSPACE
</div>

</div>

</div>


<div class="selected-target">

Clean Destination:

<strong id="cleanSelectedName">
    CLEAN INVENTORY ROOT
</strong>

</div>


<div class="side-tools">

<button
    id="createFolderButton"
    type="button" class="cp-button fs2-tool">

    + NEW FOLDER

</button>


<button
    id="renameFolderButton"
    type="button" class="cp-button fs2-tool">

    RENAME FOLDER

</button>


<button
    id="removeFolderButton"
    type="button" class="cp-button fs2-tool">

    REMOVE FOLDER

</button>


<button
    id="removeItemButton"
    type="button" class="cp-button fs2-tool">

    REMOVE ITEM

</button>


<button
    id="clearCleanButton"
    type="button" class="cp-button fs2-tool">

    CLEAR CLEAN

</button>

</div>


<div class="side-columns">


<div class="folder-panel">

<div class="inner-heading">

<span>
    CLEAN FOLDERS
</span>

<span id="cleanFolderCount">
    0
</span>

</div>


<div
    id="cleanFolderTree"
    class="folder-tree">
</div>

</div>



<div class="item-panel">

<div class="inner-heading">

<span id="cleanCurrentFolder">
    CLEAN INVENTORY ROOT
</span>

<span id="cleanVisibleItems">
    0
</span>

</div>


<div
    id="cleanItemList"
    class="item-list">

<div class="empty-state">
    Your Clean Inventory is empty.
</div>

</div>

</div>


</div>


<div
    id="cleanDropZone"
    class="drop-hint">

    Drag folders or items FROM MAIN INVENTORY here.
    They will be COPIED into Clean Inventory.
    Main Inventory remains untouched.

</div>


</section>


</section>



<section class="rp-performance-item clean-warning">

<strong>
    SAFETY MODE:
</strong>

Everything on this page is staging only.
Moving or removing something from CLEAN INVENTORY does not delete it
from your real OpenSim inventory.

</section>




<!-- ========================================================
     Grid - CLEAN INVENTORY FINAL PREVIEW V1
     PREVIEW ONLY
     ======================================================== -->

<section
    id="agCleanFinalPreview"
    class="rp-overview ag-final-preview">

    <div class="ag-final-preview-head">

        <div>

            <div class="ag-final-preview-kicker">
                FINAL CLEAN INVENTORY PREVIEW
            </div>

            <h2>
                CURRENT LIVE vs CLEAN VERSION
            </h2>

            <p>
                Review exactly what is currently staged before any future inventory replacement is allowed.
            </p>

        </div>

        <div class="ag-preview-only-badge">
            PREVIEW ONLY
        </div>

    </div>


    <div class="ag-preview-columns">

        <section class="rp-performance-item ag-preview-side live">

            <div class="ag-preview-side-title">
                CURRENT LIVE INVENTORY
            </div>

            <div class="ag-preview-big-number"
                 id="agPreviewMainItems">
                0
            </div>

            <div class="ag-preview-big-label">
                TOTAL ITEMS
            </div>

            <div class="ag-preview-secondary">

                <span>
                    FOLDERS
                </span>

                <strong id="agPreviewMainFolders">
                    0
                </strong>

            </div>

            <div
                id="agPreviewMainTypes"
                class="ag-preview-types">
            </div>

        </section>


        <section class="rp-performance-item ag-preview-side clean">

            <div class="ag-preview-side-title">
                CLEAN STAGING VERSION
            </div>

            <div class="ag-preview-big-number"
                 id="agPreviewCleanItems">
                0
            </div>

            <div class="ag-preview-big-label">
                ITEMS BEING KEPT
            </div>

            <div class="ag-preview-secondary">

                <span>
                    CLEAN FOLDERS
                </span>

                <strong id="agPreviewCleanFolders">
                    0
                </strong>

            </div>

            <div
                id="agPreviewCleanTypes"
                class="ag-preview-types">
            </div>

        </section>

    </div>


    <div class="ag-preview-change-title">
        CHANGE SUMMARY
    </div>

    <div class="ag-preview-change-grid">

        <div class="rp-performance-item ag-preview-stat kept">

            <strong id="agPreviewKept">
                0
            </strong>

            <span>
                ITEMS KEPT
            </span>

        </div>

        <div class="rp-performance-item ag-preview-stat leftout">

            <strong id="agPreviewLeftOut">
                0
            </strong>

            <span>
                ITEMS LEFT OUT
            </span>

        </div>

        <div class="rp-performance-item ag-preview-stat retained">

            <strong id="agPreviewRetainedPercent">
                0%
            </strong>

            <span>
                RETAINED
            </span>

        </div>

        <div class="rp-performance-item ag-preview-stat removed">

            <strong id="agPreviewLeftOutPercent">
                0%
            </strong>

            <span>
                LEFT OUT
            </span>

        </div>

    </div>


    <div class="ag-preview-bar">

        <div
            id="agPreviewKeptBar"
            class="ag-preview-bar-kept">
        </div>

    </div>

    <div class="ag-preview-bar-labels">

        <span>
            CLEAN INVENTORY KEPT
        </span>

        <span>
            ITEMS LEFT OUT
        </span>

    </div>


    <div
        id="agPreviewMessage"
        class="ag-preview-message">

        Loading preview...

    </div>


    <div class="rp-performance-item ag-preview-safety">

        <div class="ag-preview-lock">
            <img class="ag-sentinel-direct-icon ag-sentinel-sm" src="/Other/assets/icons/sentinel/security.png" alt="" aria-hidden="true" draggable="false" decoding="async">
        </div>

        <div>

            <strong>
                LIVE INVENTORY IS STILL PROTECTED
            </strong>

            <p>
                This is a comparison preview only. Nothing displayed here deletes, replaces or modifies your current OpenSim inventory.
            </p>

            <p>
                Final replacement remains controlled separately by the Safety Inventory IAR verification gate below.
            </p>

        </div>

    </div>

</section>
<!-- AUSTRALIA SAFETY IAR PANEL V1.1 START -->

<section class="rp-overview final-zone">

<h2 class="clean-title-with-icon"><img class="ag-sentinel-direct-icon ag-sentinel-sm clean-title-icon" src="/Other/assets/icons/sentinel/backup.png" alt="" aria-hidden="true" draggable="false" decoding="async"><span>SAFETY INVENTORY IAR</span></h2>

<p>
    Before Grid can replace your Main Inventory,
    a complete emergency IAR of your CURRENT live inventory
    must first be created and VERIFIED.
</p>


<div class="rp-region safety-iar-panel">

<div class="safety-top-row">

<div>

<div class="safety-small-label">
    SAFETY BACKUP STATUS
</div>

<div
    id="safetyIarBadge"
    class="safety-status-badge safety-waiting">

    NOT CREATED

</div>

</div>


<div
    id="safetySpinner"
    class="safety-spinner"
    hidden>
</div>

</div>


<div
    id="safetyIarMessage"
    class="safety-message">

    No Safety Inventory IAR has been created yet.

</div>


<div
    id="safetyFileInfo"
    class="safety-file-info"
    hidden>


<div>

<span>
    FILE
</span>

<strong id="safetyFileName">
</strong>

</div>


<div>

<span>
    SIZE
</span>

<strong id="safetyFileSize">
</strong>

</div>


</div>


<div class="safety-actions">


<button
    id="createSafetyIar"
    type="button" class="cp-button fs2-tool">

    CREATE SAFETY IAR

</button>


<a
    id="downloadSafetyIar"
    href="#"
    hidden class="cp-button fs2-tool">

    DOWNLOAD SAFETY IAR

</a>


</div>


</div>


<div
    id="safetyVerifiedBox"
    class="rp-performance-item safety-verified"
    hidden>

<div class="safety-check">
    <img class="ag-sentinel-direct-icon ag-sentinel-sm safety-check-icon" src="/Other/assets/icons/sentinel/backup.png" alt="" aria-hidden="true" draggable="false" decoding="async">
</div>

<div>

<strong>
    SAFETY IAR VERIFIED
</strong>

<p>
    Your emergency inventory backup has passed the
    Grid Safety IAR verification checks.
</p>

</div>

</div>


<div class="safety-divider">
</div>


<h2>
    SAVE CLEAN INVENTORY AS MY NEW MAIN INVENTORY
</h2>


<p id="replacementSafetyText">

This remains locked until a Safety IAR has successfully
completed and been verified.

</p>


<button
    id="finalReplacementButton"
    type="button"
    disabled class="cp-button fs2-tool">

    SAFETY IAR REQUIRED BEFORE INVENTORY REPLACEMENT

</button>


</section>

<!-- AUSTRALIA SAFETY IAR PANEL V1.1 END -->



<section
    id="statusBar"
    class="rp-performance-item status-bar">

    Loading Clean Inventory workspace...

</section>


</main>



<script>

(function () {


const CSRF =
    <?=json_encode(
        $csrfToken,
        JSON_UNESCAPED_SLASHES
    )?>;


const BROWSER =
    "/Other/inventory-browser-api.php";


const CLEAN =
    "/Other/user-clean-inventory.php";


const ZERO =
    "00000000-0000-0000-0000-000000000000";


let mainFolders =
    [];


let mainFolderMap =
    new Map();


let selectedMainFolder =
    null;


let selectedMainItem =
    null;


let selectedCleanFolder =
    "clean-root";


let selectedCleanItem =
    null;


let cleanState =
    {
        folders:{},
        items:{}
    };


function status(
    text,
    error
){

    const box =
        document.getElementById(
            "statusBar"
        );


    box.textContent =
        text;


    box.classList.toggle(
        "error",
        !!error
    );

}


async function browserApi(
    action,
    params
){

    const url =
        new URL(
            BROWSER,
            window.location.origin
        );


    url.searchParams.set(
        "api",
        action
    );


    if(params){

        Object.keys(
            params
        ).forEach(
            function(key){

                url.searchParams.set(
                    key,
                    params[key]
                );

            }
        );

    }


    const response =
        await fetch(
            url.toString(),
            {
                cache:"no-store",
                credentials:"same-origin"
            }
        );


    const data =
        await response.json();


    if(
        !response.ok ||
        !data.ok
    ){

        throw new Error(
            data.error ||
            "Inventory request failed."
        );

    }


    return data;

}


async function cleanApi(
    action,
    data
){

    const url =
        new URL(
            CLEAN,
            window.location.origin
        );


    url.searchParams.set(
        "api",
        action
    );


    const options = {

        cache:
            "no-store",

        credentials:
            "same-origin"

    };


    if(data){

        options.method =
            "POST";


        options.headers = {

            "Content-Type":
                "application/json",

            "X-CSRF-Token":
                CSRF

        };


        options.body =
            JSON.stringify(
                data
            );

    }


    const response =
        await fetch(
            url.toString(),
            options
        );


    const result =
        await response.json();


    if(
        !response.ok ||
        !result.ok
    ){

        throw new Error(
            result.error ||
            "Clean Inventory request failed."
        );

    }


    return result;

}


/* AUSTRALIA CLEAN INVENTORY SENTINEL ICONS V1 */

function makeCleanSentinelIcon(
    fileName,
    sizeClass
){
    const img =
        document.createElement("img");

    img.className =
        "ag-clean-sentinel-img " +
        String(sizeClass || "");

    img.src =
        "/Other/assets/icons/sentinel/" +
        String(fileName || "files.png");

    img.alt = "";
    img.draggable = false;
    img.decoding = "async";

    img.setAttribute(
        "aria-hidden",
        "true"
    );

    return img;
}
function typeInfo(
    value
){

    const type =
        Number(
            value
        );


    const map = {

        0:["image.png","Texture"],
        1:["notification.png","Sound"],
        2:["account.png","Calling Card"],
        3:["map.png","Landmark"],
        5:["avatar.png","Clothing"],
        6:["inventory.png","Object"],
        7:["report.png","Notecard"],
        10:["tools.png","Script"],
        13:["avatar.png","Body Part"],
        20:["refresh.png","Animation"],
        21:["chat.png","Gesture"],
        24:["link.png","Inventory Link"],
        25:["link.png","Folder Link"]

    };


    return (
        map[type] ||
        ["files.png","Inventory Item"]
    );

}


function makeItemRow(
    item,
    side
){

    const info =
        typeInfo(
            item.assetType
        );


    const row =
        document.createElement(
            "button"
        );


    row.type =
        "button";


    row.className =
        "item-row";


    row.draggable =
        true;


    const icon =
        document.createElement(
            "span"
        );


    icon.className =
        "item-icon";

    icon.appendChild(
        makeCleanSentinelIcon(
            info[0],
            "ag-clean-icon-item"
        )
    );


    const copy =
        document.createElement(
            "span"
        );


    const name =
        document.createElement(
            "div"
        );


    name.className =
        "item-name";


    name.textContent =
        item.name ||
        "(Unnamed item)";


    const description =
        document.createElement(
            "div"
        );


    description.className =
        "item-desc";


    description.textContent =
        item.description ||
        info[1];


    copy.appendChild(
        name
    );


    copy.appendChild(
        description
    );


    row.appendChild(
        icon
    );


    row.appendChild(
        copy
    );


    row.addEventListener(
        "click",
        function(){

            if(side === "main"){

                selectedMainItem =
                    item;


                document.querySelectorAll(
                    "#mainItemList .item-row"
                ).forEach(
                    function(element){

                        element.classList.remove(
                            "active"
                        );

                    }
                );

            }
            else{

                selectedCleanItem =
                    item;


                document.querySelectorAll(
                    "#cleanItemList .item-row"
                ).forEach(
                    function(element){

                        element.classList.remove(
                            "active"
                        );

                    }
                );

            }


            row.classList.add(
                "active"
            );

        }
    );


    row.addEventListener(
        "dragstart",
        function(event){

            event.dataTransfer.setData(
                "text/plain",
                JSON.stringify({
                    side:
                        side,

                    kind:
                        "item",

                    id:
                        item.id
                })
            );

        }
    );


    return row;

}


function mainSubtreeStats(
    folderId
){

    const rootId =
        String(
            folderId ||
            ""
        ).toLowerCase();

    const seen =
        new Set();

    const queue =
        [
            rootId
        ];

    let totalItems =
        0;

    let descendantFolders =
        0;


    while(
        queue.length >
        0
    ){

        const currentId =
            queue.shift();

        if(
            !currentId ||
            seen.has(
                currentId
            )
        ){
            continue;
        }

        seen.add(
            currentId
        );


        const currentFolder =
            mainFolders.find(
                function(candidate){

                    return (
                        String(
                            candidate.id ||
                            ""
                        ).toLowerCase()
                        ===
                        currentId
                    );

                }
            );


        if(currentFolder){

            const rawCount =
                Number(
                    currentFolder.itemCount ||
                    0
                );

            if(
                Number.isFinite(
                    rawCount
                )
            ){

                totalItems +=
                    Math.max(
                        0,
                        rawCount
                    );

            }

        }


        mainFolders.forEach(
            function(candidate){

                const candidateId =
                    String(
                        candidate.id ||
                        ""
                    ).toLowerCase();

                const parentId =
                    String(
                        candidate.parent ||
                        ZERO
                    ).toLowerCase();


                if(
                    parentId ===
                    currentId
                    &&
                    candidateId !==
                    rootId
                    &&
                    !seen.has(
                        candidateId
                    )
                ){

                    descendantFolders++;

                    queue.push(
                        candidateId
                    );

                }

            }
        );

    }


    return {
        totalItems:
            totalItems,

        descendantFolders:
            descendantFolders
    };

}

/* CLEAN OPENSIM LIBRARY ROOT */ async function cleanLibraryApi(action,params){const url=new URL("/Other/inventory-library-api.php",window.location.origin);url.searchParams.set("api",action);if(params){Object.keys(params).forEach(function(key){url.searchParams.set(key,params[key]);});}const response=await fetch(url.toString(),{cache:"no-store",credentials:"same-origin"});const data=await response.json();if(!response.ok||!data||data.ok!==true){throw new Error(data&&data.error?data.error:"OpenSim Library request failed.");}return data;} function makeCleanLibraryItemRow(item){const info=typeInfo(item.assetType);const row=document.createElement("button");row.type="button";row.className="item-row";row.draggable=false;row.title="OpenSim Library - read only";const icon=document.createElement("span");icon.className="item-icon";icon.appendChild(makeCleanSentinelIcon(info[0],"ag-clean-icon-item"));const copy=document.createElement("span");const name=document.createElement("div");name.className="item-name";name.textContent=item.name||"(Unnamed item)";const description=document.createElement("div");description.className="item-desc";description.textContent=item.description||info[1];copy.appendChild(name);copy.appendChild(description);row.appendChild(icon);row.appendChild(copy);row.addEventListener("click",function(){document.querySelectorAll("#mainItemList .item-row").forEach(function(element){element.classList.remove("active");});selectedMainItem=null;selectedMainFolder=null;row.classList.add("active");});return row;} async function loadCleanLibraryItems(folder,button){const itemList=document.getElementById("mainItemList");itemList.innerHTML="<div class=\"empty-state\">Loading OpenSim Library...</div>";selectedMainFolder=null;selectedMainItem=null;document.getElementById("mainSelectedName").textContent=folder.name||"OpenSim Library";document.querySelectorAll("#mainFolders .folder-button").forEach(function(element){element.classList.remove("active");});button.classList.add("active");try{const result=await cleanLibraryApi("items",{folder:folder.id});const items=Array.isArray(result.items)?result.items:[];items.sort(function(a,b){return String(a.name||"").localeCompare(String(b.name||""),undefined,{numeric:true,sensitivity:"base"});});itemList.innerHTML="";document.getElementById("mainVisibleItems").textContent=items.length.toLocaleString();if(items.length===0){itemList.innerHTML="<div class=\"empty-state\">This OpenSim Library folder contains no items.</div>";return;}items.forEach(function(item){itemList.appendChild(makeCleanLibraryItemRow(item));});status("OpenSim Library is read only.",false);}catch(error){const errorState=document.createElement("div");errorState.className="empty-state";errorState.textContent=String(error.message||"OpenSim Library could not be loaded.");itemList.replaceChildren(errorState);document.getElementById("mainVisibleItems").textContent="0";status(error.message,true);}} async function appendOpenSimLibrary(container){const token=String(Date.now())+"-"+String(Math.random());container.dataset.libraryToken=token;try{const responses=await Promise.all([cleanLibraryApi("summary"),cleanLibraryApi("folders")]);if(container.dataset.libraryToken!==token){return;}const summary=responses[0]||{};const folders=Array.isArray(responses[1].folders)?responses[1].folders:[];const map=new Map();folders.forEach(function(raw){const folder={id:String(raw.id||""),parent:String(raw.parent||""),name:String(raw.name||"Unnamed Library Folder"),type:raw.type,itemCount:Number(raw.itemCount||0),children:[]};map.set(folder.id.toLowerCase(),folder);});const roots=[];map.forEach(function(folder){const parent=map.get(folder.parent.toLowerCase());if(parent&&parent.id!==folder.id){parent.children.push(folder);}else{roots.push(folder);}});function sortLibrary(list){list.sort(function(a,b){return a.name.localeCompare(b.name,undefined,{numeric:true,sensitivity:"base"});});list.forEach(function(folder){sortLibrary(folder.children);});}sortLibrary(roots);function total(folder,seen){const id=folder.id.toLowerCase();if(seen.has(id)){return 0;}seen.add(id);let count=Math.max(0,Number(folder.itemCount||0));folder.children.forEach(function(child){count+=total(child,seen);});return count;}function addLibraryFolder(folder,parentElement){const node=document.createElement("div");node.className="folder-node";const button=document.createElement("button");button.type="button";button.className="folder-button";button.dataset.libraryFolderId=folder.id;button.dataset.folderId="library-"+String(folder.id).toLowerCase();button.title="OpenSim Library - read only";const icon=document.createElement("span");icon.className="folder-icon";icon.appendChild(makeCleanSentinelIcon("files.png","ag-clean-icon-folder"));const name=document.createElement("span");name.className="folder-name";name.textContent=folder.name;const count=document.createElement("span");count.className="folder-count";const itemTotal=total(folder,new Set());count.textContent="("+itemTotal.toLocaleString()+")";if(itemTotal===0&&folder.children.length===0){count.classList.add("ag-folder-empty-count");count.textContent="(0) EMPTY";button.classList.add("ag-empty-folder");}button.appendChild(icon);button.appendChild(name);button.appendChild(count);button.addEventListener("click",function(){loadCleanLibraryItems(folder,button);});node.appendChild(button);if(folder.children.length>0){const childBox=document.createElement("div");childBox.className="folder-children";folder.children.forEach(function(child){addLibraryFolder(child,childBox);});node.appendChild(childBox);}parentElement.appendChild(node);}const libraryNode=document.createElement("div");libraryNode.className="folder-node";const libraryButton=document.createElement("button");libraryButton.type="button";libraryButton.className="folder-button";libraryButton.dataset.folderId="opensim-library-root";libraryButton.title="OpenSim Library - read only";const libraryIcon=document.createElement("span");libraryIcon.className="folder-icon";libraryIcon.appendChild(makeCleanSentinelIcon("inventory.png","ag-clean-icon-folder"));const libraryName=document.createElement("span");libraryName.className="folder-name";libraryName.textContent=String(summary.libraryName||responses[1].libraryName||"OpenSim Library");const libraryCount=document.createElement("span");libraryCount.className="folder-count";let libraryItems=Number(summary.items||0);if(!libraryItems){roots.forEach(function(folder){libraryItems+=total(folder,new Set());});}libraryCount.textContent="("+libraryItems.toLocaleString()+")";libraryButton.appendChild(libraryIcon);libraryButton.appendChild(libraryName);libraryButton.appendChild(libraryCount);libraryButton.addEventListener("click",function(){selectedMainFolder=null;selectedMainItem=null;document.querySelectorAll("#mainFolders .folder-button").forEach(function(element){element.classList.remove("active");});libraryButton.classList.add("active");document.getElementById("mainSelectedName").textContent=libraryName.textContent;document.getElementById("mainVisibleItems").textContent="0";document.getElementById("mainItemList").innerHTML="<div class=\"empty-state\">Select an OpenSim Library folder to view its items. Library inventory is read only.</div>";});const children=document.createElement("div");children.className="folder-children";roots.forEach(function(folder){addLibraryFolder(folder,children);});libraryNode.appendChild(libraryButton);libraryNode.appendChild(children);if(container.dataset.libraryToken===token){container.appendChild(libraryNode);}}catch(error){if(container.dataset.libraryToken!==token){return;}const node=document.createElement("div");node.className="folder-node";const button=document.createElement("button");button.type="button";button.className="folder-button ag-empty-folder";const icon=document.createElement("span");icon.className="folder-icon";icon.appendChild(makeCleanSentinelIcon("inventory.png","ag-clean-icon-folder"));const name=document.createElement("span");name.className="folder-name";name.textContent="OpenSim Library";const count=document.createElement("span");count.className="folder-count ag-folder-empty-count";count.textContent="UNAVAILABLE";button.appendChild(icon);button.appendChild(name);button.appendChild(count);button.title=String(error.message||"OpenSim Library unavailable");node.appendChild(button);container.appendChild(node);}}
function buildMainTree(){

    const container =
        document.getElementById(
            "mainFolders"
        );


    container.innerHTML =
        "";


    mainFolderMap =
        new Map();


    const children =
        new Map();


    mainFolders.forEach(
        function(folder){

            mainFolderMap.set(
                String(
                    folder.id
                ).toLowerCase(),
                folder
            );

        }
    );


    mainFolders.forEach(
        function(folder){

            const parent =
                String(
                    folder.parent ||
                    ZERO
                ).toLowerCase();


            if(
                !children.has(
                    parent
                )
            ){

                children.set(
                    parent,
                    []
                );

            }


            children.get(
                parent
            ).push(
                folder
            );

        }
    );


    /*
     * Firestorm-style Main Inventory ordering.
     * Standard system folders first.
     * User/custom folders follow alphabetically.
     */

    const firestormFolderOrder = [
        "my inventory",
        "inventory",
        "#firestorm",
        "animations",
        "body parts",
        "calling cards",
        "clothing",
        "current outfit",
        "favorites",
        "gestures",
        "landmarks",
        "lost and found",
        "materials",
        "my suitcase",
        "notecards",
        "objects",
        "outfits",
        "photo album",
        "scripts",
        "settings",
        "sounds",
        "textures",
        "trash"
    ];

    const firestormFolderRank =
        new Map();

    firestormFolderOrder.forEach(
        function(folderName,index){

            firestormFolderRank.set(
                folderName,
                index
            );
        }
    );


    /* INVENTORY BROWSER FOLDER ORDER */ function folderRank(folder){const name=String(folder.name||"").trim();const lower=name.toLowerCase();if(lower==="#firestorm")return {group:0,rank:0,name:lower};if(name.startsWith("#"))return {group:1,rank:0,name:lower};const i=firestormFolderOrder.indexOf(lower);if(i>=0)return {group:2,rank:i,name:lower};if(name.startsWith("!"))return {group:3,rank:0,name:lower};return {group:4,rank:0,name:lower};} function sortFolders(list){list.sort(function(a,b){const aa=folderRank(a),bb=folderRank(b);if(aa.group!==bb.group)return aa.group-bb.group;if(aa.rank!==bb.rank)return aa.rank-bb.rank;return String(a.name||"").localeCompare(String(b.name||""),undefined,{numeric:true,sensitivity:"base"});});} children.forEach(sortFolders);


    const roots =
        mainFolders.filter(
            function(folder){

                const parent =
                    String(
                        folder.parent ||
                        ZERO
                    ).toLowerCase();


                const id =
                    String(
                        folder.id
                    ).toLowerCase();


                return (
                    parent === ZERO ||
                    parent === "" ||
                    parent === id ||
                    !mainFolderMap.has(
                        parent
                    )
                );

            }
        );


    sortFolders(
        roots
    );


    const rendered =
        new Set();


    function add(
        folder,
        parentElement,
        depth
    ){

        if(
            depth > 80
        ){
            return;
        }


        const id =
            String(
                folder.id
            ).toLowerCase();


        if(
            rendered.has(
                id
            )
        ){
            return;
        }


        rendered.add(
            id
        );


        const node =
            document.createElement(
                "div"
            );


        node.className =
            "folder-node";


        const button =
            document.createElement(
                "button"
            );


        button.type =
            "button";


        button.className =
            "folder-button";

        button.dataset.folderId =
            String(
                folder.id || ""
            );


        button.draggable =
            true;


        const icon =
            document.createElement(
                "span"
            );


        icon.className =
            "folder-icon";

        icon.appendChild(
            makeCleanSentinelIcon(
                "files.png",
                "ag-clean-icon-folder"
            )
        );


        const name =
            document.createElement(
                "span"
            );


        name.className =
            "folder-name";


        const rawFolderName =
            folder.name ||
            "Unnamed Folder";

        name.textContent =
            (
                depth === 0 &&
                String(rawFolderName)
                    .trim()
                    .toLowerCase() === "my inventory"
            )
            ? "Inventory"
            : rawFolderName;


        button.appendChild(
            icon
        );


        button.appendChild(
            name
        );

        const rawItemCount =
            Number(
                folder.itemCount ||
                0
            );

        const directItemCount =
            Number.isFinite(rawItemCount)
            ? Math.max(0,rawItemCount)
            : 0;

        const folderStats =
            mainSubtreeStats(
                id
            );

        const totalItemCount =
            Number(
                folderStats.totalItems ||
                0
            );


        const countBadge =
            document.createElement(
                "span"
            );

        countBadge.className =
            "folder-count";

        countBadge.textContent =
            "(" +
            totalItemCount.toLocaleString() +
            ")";

        if(
            totalItemCount !==
            directItemCount
        ){

            countBadge.title =
                directItemCount.toLocaleString() +
                " direct items; " +
                totalItemCount.toLocaleString() +
                " including subfolders";

        }

        button.appendChild(
            countBadge
        );


        button.addEventListener(
            "click",
            function(){

                selectedMainFolder =
                    folder;


                selectedMainItem =
                    null;


                document.getElementById(
                    "mainSelectedName"
                ).textContent =
                    folder.name ||
                    "Unnamed Folder";


                document.querySelectorAll(
                    "#mainFolders .folder-button"
                ).forEach(
                    function(element){

                        element.classList.remove(
                            "active"
                        );

                    }
                );


                button.classList.add(
                    "active"
                );


                loadMainItems(
                    folder
                );

            }
        );


        button.addEventListener(
            "dragstart",
            function(event){

                event.dataTransfer.setData(
                    "text/plain",
                    JSON.stringify({
                        side:
                            "main",

                        kind:
                            "folder",

                        id:
                            folder.id
                    })
                );

            }
        );


        node.appendChild(
            button
        );


        const childList =
            children.get(
                id
            ) ||
            [];

        if(
            directItemCount === 0 &&
            childList.length === 0
        ){

            countBadge.classList.add(
                "ag-folder-empty-count"
            );

            countBadge.textContent =
                "(0) EMPTY";

            button.classList.add(
                "ag-empty-folder"
            );
        }


        if(
            childList.length > 0
        ){

            const childBox =
                document.createElement(
                    "div"
                );


            childBox.className =
                "folder-children";


            childList.forEach(
                function(child){

                    add(
                        child,
                        childBox,
                        depth + 1
                    );

                }
            );


            node.appendChild(
                childBox
            );

        }


        parentElement.appendChild(
            node
        );

    }


    /* INVENTORY BROWSER PERSONAL ROOT MODEL */ const personalRoot=roots.find(function(folder){return /^(my\s+)?inventory$/i.test(String(folder.name||"").trim());})||null;let personalFolders=personalRoot?(children.get(String(personalRoot.id).toLowerCase())||[]):roots.slice();personalFolders=personalFolders.slice();sortFolders(personalFolders);const inventoryNode=document.createElement("div");inventoryNode.className="folder-node";const inventoryButton=document.createElement("button");inventoryButton.type="button";inventoryButton.className="folder-button";inventoryButton.dataset.folderId=personalRoot?String(personalRoot.id):"inventory-root";const inventoryIcon=document.createElement("span");inventoryIcon.className="folder-icon";inventoryIcon.appendChild(makeCleanSentinelIcon("inventory.png","ag-clean-icon-folder"));const inventoryName=document.createElement("span");inventoryName.className="folder-name";inventoryName.textContent="Inventory";inventoryButton.appendChild(inventoryIcon);inventoryButton.appendChild(inventoryName);let inventoryTotal=0;if(personalRoot){inventoryTotal=Number(mainSubtreeStats(String(personalRoot.id).toLowerCase()).totalItems||0);}else{personalFolders.forEach(function(folder){inventoryTotal+=Number(mainSubtreeStats(String(folder.id||"").toLowerCase()).totalItems||0);});}const inventoryCount=document.createElement("span");inventoryCount.className="folder-count";inventoryCount.textContent="("+inventoryTotal.toLocaleString()+")";inventoryButton.appendChild(inventoryCount);if(personalRoot){inventoryButton.addEventListener("click",function(){selectedMainFolder=personalRoot;selectedMainItem=null;document.getElementById("mainSelectedName").textContent="Inventory";document.querySelectorAll("#mainFolders .folder-button").forEach(function(element){element.classList.remove("active");});inventoryButton.classList.add("active");loadMainItems(personalRoot);});}const inventoryChildren=document.createElement("div");inventoryChildren.className="folder-children";personalFolders.forEach(function(folder){add(folder,inventoryChildren,0);});inventoryNode.appendChild(inventoryButton);inventoryNode.appendChild(inventoryChildren);container.appendChild(inventoryNode);appendOpenSimLibrary(container);document.getElementById("mainFolderCount").textContent=mainFolders.length.toLocaleString();

}


async function loadMainItems(
    folder
){

    const container =
        document.getElementById(
            "mainItemList"
        );


    container.innerHTML =
        '<div class="empty-state">Loading items...</div>';


    document.getElementById(
        "mainCurrentFolder"
    ).textContent =
        folder.name ||
        "ITEMS";


    try{

        const result =
            await browserApi(
                "items",
                {
                    folder:
                        folder.id
                }
            );


        const items =
            result.items ||
            [];


        container.innerHTML =
            "";


        document.getElementById(
            "mainVisibleItems"
        ).textContent =
            items.length.toLocaleString();


        if(
            items.length === 0
        ){

            const stats =
                mainSubtreeStats(
                    folder.id
                );

            const folderName =
                String(
                    folder.name ||
                    "This folder"
                );

            if(
                Number(
                    stats.descendantFolders ||
                    0
                ) > 0
            ){

                container.innerHTML =
                    '<div class="empty-state">' +
                    'No items are stored directly in ' +
                    folderName +
                    '. It contains ' +
                    Number(
                        stats.descendantFolders
                    ).toLocaleString() +
                    ' subfolders with ' +
                    Number(
                        stats.totalItems ||
                        0
                    ).toLocaleString() +
                    ' items inside them. ' +
                    'Select a subfolder to view those items.' +
                    '</div>';

            }
            else{

                container.innerHTML =
                    '<div class="empty-state">' +
                    'This folder contains no inventory items.' +
                    '</div>';

            }

            return;

        }


        items.forEach(
            function(item){

                container.appendChild(
                    makeItemRow(
                        item,
                        "main"
                    )
                );

            }
        );

    }
    catch(error){

        status(
            error.message,
            true
        );

    }

}


function buildCleanTree(){

    const container =
        document.getElementById(
            "cleanFolderTree"
        );


    container.innerHTML =
        "";

    const cleanItemCounts =
        new Map();

    Object.values(
        cleanState.items ||
        {}
    ).forEach(
        function(item){

            const parent =
                String(
                    item.folderId ||
                    "clean-root"
                );

            cleanItemCounts.set(
                parent,
                (
                    cleanItemCounts.get(parent) ||
                    0
                ) + 1
            );
        }
    );


    const rootButton =
        document.createElement(
            "button"
        );


    rootButton.type =
        "button";


    rootButton.className =
        "folder-button";

    rootButton.dataset.folderId =
        "clean-root";


    if(
        selectedCleanFolder ===
        "clean-root"
    ){

        rootButton.classList.add(
            "active"
        );

    }


    rootButton.innerHTML = '<span class="folder-icon"><img class="ag-sentinel-direct-icon ag-sentinel-sm" src="/Other/assets/icons/sentinel/files.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span><span class="folder-name">CLEAN INVENTORY ROOT</span>';


    
    const cleanRootItemCount =
        cleanItemCounts.get(
            "clean-root"
        ) ||
        0;

    const rootCountBadge =
        document.createElement(
            "span"
        );

    rootCountBadge.className =
        "folder-count";

    rootCountBadge.textContent =
        "(" +
        cleanRootItemCount.toLocaleString() +
        ")";

    rootButton.appendChild(
        rootCountBadge
    );
rootButton.addEventListener(
        "click",
        function(){

            selectedCleanFolder =
                "clean-root";


            selectedCleanItem =
                null;


            document.getElementById(
                "cleanSelectedName"
            ).textContent =
                "CLEAN INVENTORY ROOT";


            buildCleanTree();


            renderCleanItems();

        }
    );


    container.appendChild(
        rootButton
    );


    const folders =
        Object.values(
            cleanState.folders ||
            {}
        );


    const byParent =
        new Map();


    folders.forEach(
        function(folder){

            const parent =
                String(
                    folder.parentId ||
                    "clean-root"
                );


            if(
                !byParent.has(
                    parent
                )
            ){

                byParent.set(
                    parent,
                    []
                );

            }


            byParent.get(
                parent
            ).push(
                folder
            );

        }
    );


    byParent.forEach(
        function(list){

            list.sort(
                function(a,b){

                    return String(
                        a.name ||
                        ""
                    ).localeCompare(
                        String(
                            b.name ||
                            ""
                        )
                    );

                }
            );

        }
    );


    function renderChildren(
        parentId,
        parentElement,
        depth
    ){

        if(
            depth > 80
        ){
            return;
        }


        const list =
            byParent.get(
                parentId
            ) ||
            [];


        list.forEach(
            function(folder){

                const node =
                    document.createElement(
                        "div"
                    );


                node.className =
                    "folder-node";


                const button =
                    document.createElement(
                        "button"
                    );


                button.type =
                    "button";


                button.className =
                    "folder-button";

                button.dataset.folderId =
                    String(
                        folder.id || ""
                    );


                button.draggable =
                    true;


                if(
                    selectedCleanFolder ===
                    folder.id
                ){

                    button.classList.add(
                        "active"
                    );

                }


                const icon =
                    document.createElement(
                        "span"
                    );


                icon.className =
            "folder-icon";

        icon.appendChild(
            makeCleanSentinelIcon(
                "files.png",
                "ag-clean-icon-folder"
            )
        );


                const name =
                    document.createElement(
                        "span"
                    );


                name.className =
                    "folder-name";


                name.textContent =
                    folder.name ||
                    "Unnamed Folder";


                button.appendChild(
                    icon
                );


                button.appendChild(
                    name
                );

                const directItemCount =
                    cleanItemCounts.get(
                        String(folder.id)
                    ) ||
                    0;

                const countBadge =
                    document.createElement(
                        "span"
                    );

                countBadge.className =
                    "folder-count";

                countBadge.textContent =
                    "(" +
                    directItemCount.toLocaleString() +
                    ")";

                button.appendChild(
                    countBadge
                );


                button.addEventListener(
                    "click",
                    function(){

                        selectedCleanFolder =
                            folder.id;


                        selectedCleanItem =
                            null;


                        document.getElementById(
                            "cleanSelectedName"
                        ).textContent =
                            folder.name ||
                            "Unnamed Folder";


                        buildCleanTree();


                        renderCleanItems();

                    }
                );


                button.addEventListener(
                    "dragstart",
                    function(event){

                        event.dataTransfer.setData(
                            "text/plain",
                            JSON.stringify({
                                side:
                                    "clean",

                                kind:
                                    "folder",

                                id:
                                    folder.id
                            })
                        );

                    }
                );


                node.appendChild(
                    button
                );


                const childBox =
                    document.createElement(
                        "div"
                    );


                childBox.className =
                    "folder-children";


                renderChildren(
                    folder.id,
                    childBox,
                    depth + 1
                );


                

                if(
                    directItemCount === 0 &&
                    childBox.children.length === 0
                ){

                    countBadge.classList.add(
                        "ag-folder-empty-count"
                    );

                    countBadge.textContent =
                        "(0) EMPTY";

                    button.classList.add(
                        "ag-empty-folder"
                    );
                }
if(
                    childBox.children.length > 0
                ){

                    node.appendChild(
                        childBox
                    );

                }


                parentElement.appendChild(
                    node
                );

            }
        );

    }


    const rootChildren =
        document.createElement(
            "div"
        );


    rootChildren.className =
        "folder-children";


    renderChildren(
        "clean-root",
        rootChildren,
        0
    );


    container.appendChild(
        rootChildren
    );


    document.getElementById(
        "cleanFolderCount"
    ).textContent =
        folders.length.toLocaleString();

}


function renderCleanItems(){

    const container =
        document.getElementById(
            "cleanItemList"
        );


    container.innerHTML =
        "";


    selectedCleanItem =
        null;


    const items =
        Object.values(
            cleanState.items ||
            {}
        ).filter(
            function(item){

                return (
                    String(
                        item.folderId ||
                        "clean-root"
                    )
                    ===
                    selectedCleanFolder
                );

            }
        );


    items.sort(
        function(a,b){

            return String(
                a.name ||
                ""
            ).localeCompare(
                String(
                    b.name ||
                    ""
                )
            );

        }
    );


    document.getElementById(
        "cleanVisibleItems"
    ).textContent =
        items.length.toLocaleString();


    let folderName =
        "CLEAN INVENTORY ROOT";


    if(
        selectedCleanFolder !==
        "clean-root"
        &&
        cleanState.folders[
            selectedCleanFolder
        ]
    ){

        folderName =
            cleanState.folders[
                selectedCleanFolder
            ].name;

    }


    document.getElementById(
        "cleanCurrentFolder"
    ).textContent =
        folderName;


    if(
        items.length === 0
    ){

        container.innerHTML =
            '<div class="empty-state">No Clean Inventory items in this folder.</div>';


        return;

    }


    items.forEach(
        function(item){

            container.appendChild(
                makeItemRow(
                    item,
                    "clean"
                )
            );

        }
    );

}




function agCleanTypeOrder(
    totals
){

    const preferred = [
        "6",
        "0",
        "10",
        "7",
        "1",
        "3",
        "20",
        "21",
        "5",
        "13",
        "2",
        "24",
        "25"
    ];

    const rank =
        new Map();

    preferred.forEach(
        function(key,index){
            rank.set(key,index);
        }
    );

    return Object.keys(
        totals || {}
    )
    .filter(
        function(key){
            return Number(
                totals[key] || 0
            ) > 0;
        }
    )
    .sort(
        function(a,b){

            const ar =
                rank.has(a)
                ? rank.get(a)
                : 1000;

            const br =
                rank.has(b)
                ? rank.get(b)
                : 1000;

            if(ar !== br){
                return ar - br;
            }

            return String(
                typeInfo(a)[1]
            ).localeCompare(
                String(
                    typeInfo(b)[1]
                )
            );
        }
    );
}


function agCleanStageTypeTotals(
    items
){

    const totals = {};

    Object.values(
        items || {}
    ).forEach(
        function(item){

            const number =
                Number(
                    item.assetType
                );

            const key =
                Number.isFinite(number)
                ? String(number)
                : "unknown";

            totals[key] =
                (totals[key] || 0) + 1;
        }
    );

    return totals;
}


function agFillTypePanel(
    panel,
    totals
){

    const list =
        panel.querySelector(
            ".ag-type-list"
        );

    list.innerHTML = "";

    const keys =
        agCleanTypeOrder(
            totals
        );

    if(keys.length === 0){

        const empty =
            document.createElement(
                "div"
            );

        empty.className =
            "ag-type-empty";

        empty.textContent =
            "No items in this inventory.";

        list.appendChild(empty);
        return;
    }

    keys.forEach(
        function(key){

            const info =
                typeInfo(key);

            const chip =
                document.createElement(
                    "div"
                );

            chip.className =
                "ag-type-chip";

            const icon =
                document.createElement(
                    "span"
                );

            icon.className =
                "ag-type-icon";

            icon.appendChild(
                makeCleanSentinelIcon(
                    info[0],
                    "ag-clean-icon-type"
                )
            );

            const label =
                document.createElement(
                    "span"
                );

            label.className =
                "ag-type-label";

            label.textContent =
                info[1];

            const number =
                document.createElement(
                    "span"
                );

            number.className =
                "ag-type-number";

            number.textContent =
                Number(
                    totals[key] || 0
                ).toLocaleString();

            chip.appendChild(icon);
            chip.appendChild(label);
            chip.appendChild(number);

            list.appendChild(chip);
        }
    );
}


function renderCleanInventoryTypeTotals(
    mainTotals,
    cleanItems
){

    let wrapper =
        document.getElementById(
            "agCleanInventoryTypeTotals"
        );

    if(!wrapper){

        const summaryGrid =
            document.querySelector(
                ".summary-grid"
            );

        if(!summaryGrid){
            return;
        }

        wrapper =
            document.createElement(
                "section"
            );

        wrapper.id =
            "agCleanInventoryTypeTotals";

        wrapper.className =
            "ag-type-totals-split";


        function makePanel(
            id,
            titleText,
            noteText
        ){

            const panel =
                document.createElement(
                    "div"
                );

            panel.id = id;

            panel.className =
                "rp-region ag-type-panel";

            const heading =
                document.createElement(
                    "div"
                );

            heading.className =
                "ag-type-heading";

            const title =
                document.createElement(
                    "div"
                );

            title.className =
                "ag-type-title";

            title.textContent =
                titleText;

            const note =
                document.createElement(
                    "div"
                );

            note.className =
                "ag-type-note";

            note.textContent =
                noteText;

            const list =
                document.createElement(
                    "div"
                );

            list.className =
                "ag-type-list";

            heading.appendChild(title);
            heading.appendChild(note);

            panel.appendChild(heading);
            panel.appendChild(list);

            return panel;
        }


        wrapper.appendChild(
            makePanel(
                "agMainInventoryTypePanel",
                "MAIN INVENTORY BY TYPE",
                "CURRENT LIVE INVENTORY"
            )
        );

        wrapper.appendChild(
            makePanel(
                "agCleanInventoryTypePanel",
                "CLEAN INVENTORY BY TYPE",
                "CURRENT STAGING INVENTORY"
            )
        );

        summaryGrid.insertAdjacentElement(
            "afterend",
            wrapper
        );
    }

    agFillTypePanel(
        document.getElementById(
            "agMainInventoryTypePanel"
        ),
        mainTotals || {}
    );

    agFillTypePanel(
        document.getElementById(
            "agCleanInventoryTypePanel"
        ),
        agCleanStageTypeTotals(
            cleanItems || {}
        )
    );
}


/* ==========================================================
 * Grid - CLEAN DUPLICATE FINDER SCOPE BRIDGE V1
 * ========================================================== */

window.agCleanDuplicateBrowserApi =
    browserApi;

window.agCleanDuplicateTypeInfo =
    typeInfo;

Object.defineProperty(
    window,
    "agCleanDuplicateMainFolders",
    {
        configurable:true,
        get:function(){
            return mainFolders;
        }
    }
);

Object.defineProperty(
    window,
    "agCleanDuplicateState",
    {
        configurable:true,
        get:function(){
            return cleanState;
        }
    }
);



/* ==========================================================
 * Grid - CLEAN DUPLICATE STAGING BRIDGE V2
 * ========================================================== */

window.agCleanDuplicateCleanApi =
    cleanApi;

window.agCleanDuplicateLoadWorkspace =
    loadWorkspace;

window.agCleanDuplicateStatus =
    status;

Object.defineProperty(
    window,
    "agCleanDuplicateSelectedFolder",
    {
        configurable:true,
        get:function(){
            return selectedCleanFolder;
        }
    }
);



/* ==========================================================
 * Grid - CLEAN INVENTORY FINAL PREVIEW V1
 * PREVIEW ONLY
 * ========================================================== */

function agPreviewNumber(value){

    const number =
        Number(value || 0);

    return Number.isFinite(number)
        ? Math.max(0,number)
        : 0;
}


function agPreviewSetText(id,value){

    const element =
        document.getElementById(id);

    if(element){
        element.textContent = value;
    }
}


function agPreviewRenderTypes(
    id,
    totals
){

    const container =
        document.getElementById(id);

    if(!container){
        return;
    }

    container.innerHTML = "";

    const entries =
        Object.keys(totals || {})
        .map(
            function(key){
                return {
                    key:key,
                    total:agPreviewNumber(
                        totals[key]
                    )
                };
            }
        )
        .filter(
            function(entry){
                return entry.total > 0;
            }
        )
        .sort(
            function(a,b){
                return b.total - a.total;
            }
        );

    if(entries.length === 0){

        const empty =
            document.createElement(
                "div"
            );

        empty.className =
            "ag-preview-types-empty";

        empty.textContent =
            "No items in this version.";

        container.appendChild(empty);

        return;
    }

    entries.forEach(
        function(entry){

            const info =
                typeInfo(entry.key);

            const chip =
                document.createElement(
                    "div"
                );

            chip.className =
                "ag-preview-type-chip";

            const icon =
                document.createElement(
                    "span"
                );

            icon.className =
                "ag-preview-type-icon";

            icon.appendChild(
                makeCleanSentinelIcon(
                    info[0],
                    "ag-clean-icon-preview"
                )
            );

            const label =
                document.createElement(
                    "span"
                );

            label.className =
                "ag-preview-type-label";

            label.textContent =
                info[1];

            const number =
                document.createElement(
                    "strong"
                );

            number.textContent =
                entry.total.toLocaleString();

            chip.appendChild(icon);
            chip.appendChild(label);
            chip.appendChild(number);

            container.appendChild(chip);
        }
    );
}


function renderFinalCleanPreview(
    mainSummary,
    clean
){

    const mainItems =
        agPreviewNumber(
            clean.mainItems
        );

    const cleanItems =
        agPreviewNumber(
            clean.cleanItems
        );

    const cleanFolders =
        agPreviewNumber(
            clean.cleanFolders
        );

    const leftOut =
        agPreviewNumber(
            clean.leftOut
        );

    const mainFolders =
        agPreviewNumber(
            mainSummary.folders
        );


    let retainedPercent = 0;
    let leftOutPercent = 0;

    if(mainItems > 0){

        retainedPercent =
            Math.min(
                100,
                Math.max(
                    0,
                    (cleanItems / mainItems) * 100
                )
            );

        leftOutPercent =
            Math.min(
                100,
                Math.max(
                    0,
                    (leftOut / mainItems) * 100
                )
            );
    }


    agPreviewSetText(
        "agPreviewMainItems",
        mainItems.toLocaleString()
    );

    agPreviewSetText(
        "agPreviewMainFolders",
        mainFolders.toLocaleString()
    );

    agPreviewSetText(
        "agPreviewCleanItems",
        cleanItems.toLocaleString()
    );

    agPreviewSetText(
        "agPreviewCleanFolders",
        cleanFolders.toLocaleString()
    );

    agPreviewSetText(
        "agPreviewKept",
        cleanItems.toLocaleString()
    );

    agPreviewSetText(
        "agPreviewLeftOut",
        leftOut.toLocaleString()
    );

    agPreviewSetText(
        "agPreviewRetainedPercent",
        retainedPercent.toFixed(1) + "%"
    );

    agPreviewSetText(
        "agPreviewLeftOutPercent",
        leftOutPercent.toFixed(1) + "%"
    );


    const bar =
        document.getElementById(
            "agPreviewKeptBar"
        );

    if(bar){
        bar.style.width =
            retainedPercent.toFixed(3) + "%";
    }


    const mainTypes =
        mainSummary.typeTotals || {};

    const cleanTypes =
        agCleanStageTypeTotals(
            cleanState.items || {}
        );

    agPreviewRenderTypes(
        "agPreviewMainTypes",
        mainTypes
    );

    agPreviewRenderTypes(
        "agPreviewCleanTypes",
        cleanTypes
    );


    const message =
        document.getElementById(
            "agPreviewMessage"
        );

    if(message){

        if(mainItems === 0){

            message.textContent =
                "No live inventory items were reported, so no comparison can be calculated.";

            message.className =
                "ag-preview-message warning";
        }
        else if(cleanItems === 0){

            message.textContent =
                "Nothing is currently staged in Clean Inventory. A replacement using the current staging state would keep 0 items.";

            message.className =
                "ag-preview-message danger";
        }
        else{

            message.textContent =
                "Current preview: keep " +
                cleanItems.toLocaleString() +
                " items and leave out " +
                leftOut.toLocaleString() +
                " items. No live inventory changes have been made.";

            message.className =
                "ag-preview-message safe";
        }
    }
}

async function loadWorkspace(){

    try{

        status(
            "Loading Main Inventory and Clean Inventory...",
            false
        );


        const responses =
            await Promise.all([
                browserApi(
                    "folders"
                ),
                cleanApi(
                    "state"
                )
            ]);


        mainFolders =
            responses[0].folders ||
            [];


        const clean =
            responses[1];

        const mainSummary =
            await browserApi(
                "summary"
            );


        cleanState =
            clean.state ||
            {
                folders:{},
                items:{}
            };

        /*
         * Main Inventory totals come from the current
         * inventory-browser-api summary.
         *
         * Do NOT full-scan every live inventory item
         * merely to open Clean Inventory.
         */

        clean.mainFolders =
            Number(
                mainSummary.folders ||
                0
            );

        clean.mainItems =
            Number(
                mainSummary.items ||
                0
            );

        clean.leftOut =
            Math.max(
                0,
                clean.mainItems -
                Number(
                    clean.cleanItems ||
                    0
                )
            );


        
        renderCleanInventoryTypeTotals(
            mainSummary.typeTotals || {},
            cleanState.items || {}
        );

        renderFinalCleanPreview(
            mainSummary,
            clean
        );

document.getElementById(
            "mainItems"
        ).textContent =
            Number(
                clean.mainItems ||
                0
            ).toLocaleString();


        document.getElementById(
            "cleanItems"
        ).textContent =
            Number(
                clean.cleanItems ||
                0
            ).toLocaleString();


        document.getElementById(
            "cleanFolders"
        ).textContent =
            Number(
                clean.cleanFolders ||
                0
            ).toLocaleString();


        document.getElementById(
            "leftOut"
        ).textContent =
            Number(
                clean.leftOut ||
                0
            ).toLocaleString();


        if(
            selectedCleanFolder !==
            "clean-root"
            &&
            !cleanState.folders[
                selectedCleanFolder
            ]
        ){

            selectedCleanFolder =
                "clean-root";


            document.getElementById(
                "cleanSelectedName"
            ).textContent =
                "CLEAN INVENTORY ROOT";

        }


        buildMainTree();


        buildCleanTree();


        renderCleanItems();


        status(
            "Clean Inventory workspace loaded. Your live Main Inventory has not been changed.",
            false
        );

    }
    catch(error){

        status(
            error.message,
            true
        );

    }

}


async function addSelectedFolder(){

    if(
        !selectedMainFolder
    ){

        status(
            "Select a Main Inventory folder first.",
            true
        );


        return;

    }


    try{

        status(
            "Copying selected folder and its contents into Clean Inventory...",
            false
        );


        await cleanApi(
            "add-folder",
            {
                sourceId:
                    selectedMainFolder.id,

                targetFolder:
                    selectedCleanFolder
            }
        );


        await loadWorkspace();


        status(
            "Folder copied into Clean Inventory. Main Inventory remains untouched.",
            false
        );

    }
    catch(error){

        status(
            error.message,
            true
        );

    }

}


async function addSelectedItem(){

    if(
        !selectedMainItem
    ){

        status(
            "Select a Main Inventory item first.",
            true
        );


        return;

    }


    try{

        await cleanApi(
            "add-item",
            {
                sourceId:
                    selectedMainItem.id,

                targetFolder:
                    selectedCleanFolder
            }
        );


        await loadWorkspace();


        status(
            "Item copied into Clean Inventory. Main Inventory remains untouched.",
            false
        );

    }
    catch(error){

        status(
            error.message,
            true
        );

    }

}


async function createFolder(){

    const name =
        await auPrompt({

            title:
                "NEW CLEAN INVENTORY FOLDER",

            message:
                "Enter a name for the new folder.",

            placeholder:
                "Folder name",

            confirmText:
                "CREATE FOLDER",

            cancelText:
                "CANCEL"
        });


    if(
        name === false
    ){
        return;
    }


    try{

        await cleanApi(
            "create-folder",
            {
                name:
                    name,

                parentId:
                    selectedCleanFolder
            }
        );


        await loadWorkspace();


        status(
            "New Clean Inventory folder created.",
            false
        );

    }
    catch(error){

        status(
            error.message,
            true
        );

    }

}


async function renameFolder(){

    if(
        selectedCleanFolder ===
        "clean-root"
    ){

        status(
            "Select a Clean Inventory folder to rename.",
            true
        );


        return;

    }


    const folder =
        cleanState.folders[
            selectedCleanFolder
        ];


    if(
        !folder
    ){
        return;
    }


    const name =
        await auPrompt({

            title:
                "RENAME CLEAN INVENTORY FOLDER",

            message:
                "Enter the new folder name.",

            defaultValue:
                folder.name ||
                "",

            confirmText:
                "RENAME FOLDER",

            cancelText:
                "CANCEL"
        });


    if(
        name === false
    ){
        return;
    }


    try{

        await cleanApi(
            "rename-folder",
            {
                id:
                    selectedCleanFolder,

                name:
                    name
            }
        );


        await loadWorkspace();


        status(
            "Clean Inventory folder renamed.",
            false
        );

    }
    catch(error){

        status(
            error.message,
            true
        );

    }

}


async function removeSelectedFolder(){

    if(
        selectedCleanFolder ===
        "clean-root"
    ){

        status(
            "Select a Clean Inventory folder to remove.",
            true
        );


        return;

    }


    const folder =
        cleanState.folders[
            selectedCleanFolder
        ];


    if(
        !folder
    ){
        return;
    }


    const confirmed =
        await auConfirm({

            title:
                "REMOVE CLEAN INVENTORY FOLDER",

            message:
                "Remove this folder and its contents from your Clean Inventory workspace?",

            highlight:
                folder.name,

            warning:
                "This only removes the folder from CLEAN INVENTORY staging. Your live Main Inventory will NOT be changed.",

            confirmText:
                "REMOVE FROM CLEAN",

            cancelText:
                "CANCEL",

            danger:
                true
        });


    if(
        !confirmed
    ){
        return;
    }


    try{

        await cleanApi(
            "remove-folder",
            {
                id:
                    selectedCleanFolder
            }
        );


        selectedCleanFolder =
            "clean-root";


        await loadWorkspace();


        status(
            "Folder removed from Clean staging only. Main Inventory is untouched.",
            false
        );

    }
    catch(error){

        status(
            error.message,
            true
        );

    }

}


async function removeSelectedItem(){

    if(
        !selectedCleanItem
    ){

        status(
            "Select a Clean Inventory item to remove.",
            true
        );


        return;

    }


    try{

        await cleanApi(
            "remove-item",
            {
                id:
                    selectedCleanItem.id
            }
        );


        await loadWorkspace();


        status(
            "Item removed from Clean staging only. Main Inventory is untouched.",
            false
        );

    }
    catch(error){

        status(
            error.message,
            true
        );

    }

}


async function clearClean(){

    const confirmed =
        await auConfirm({

            title:
                "CLEAR CLEAN INVENTORY",

            message:
                "Clear the entire Clean Inventory staging workspace?",

            warning:
                "Everything currently staged in CLEAN INVENTORY will be removed from the workspace. Your live Main Inventory will NOT be deleted or changed.",

            confirmText:
                "CLEAR CLEAN INVENTORY",

            cancelText:
                "CANCEL",

            danger:
                true
        });


    if(
        !confirmed
    ){
        return;
    }


    try{

        await cleanApi(
            "clear",
            {}
        );


        selectedCleanFolder =
            "clean-root";


        selectedCleanItem =
            null;


        await loadWorkspace();


        status(
            "Clean Inventory staging workspace cleared. Main Inventory is untouched.",
            false
        );

    }
    catch(error){

        status(
            error.message,
            true
        );

    }

}


function enableDropZone(
    element,
    destination
){

    element.addEventListener(
        "dragover",
        function(event){

            event.preventDefault();


            element.classList.add(
                "drag-over"
            );

        }
    );


    element.addEventListener(
        "dragleave",
        function(){

            element.classList.remove(
                "drag-over"
            );

        }
    );


    element.addEventListener(
        "drop",
        async function(event){

            event.preventDefault();


            element.classList.remove(
                "drag-over"
            );


            let data;


            try{

                data =
                    JSON.parse(
                        event.dataTransfer.getData(
                            "text/plain"
                        )
                    );

            }
            catch(error){

                return;

            }


            try{

                if(
                    destination ===
                    "clean"
                    &&
                    data.side ===
                    "main"
                ){

                    if(
                        data.kind ===
                        "folder"
                    ){

                        await cleanApi(
                            "add-folder",
                            {
                                sourceId:
                                    data.id,

                                targetFolder:
                                    selectedCleanFolder
                            }
                        );

                    }
                    else{

                        await cleanApi(
                            "add-item",
                            {
                                sourceId:
                                    data.id,

                                targetFolder:
                                    selectedCleanFolder
                            }
                        );

                    }


                    await loadWorkspace();


                    status(
                        "Copied into Clean Inventory. Main Inventory remains untouched.",
                        false
                    );

                }


                if(
                    destination ===
                    "main"
                    &&
                    data.side ===
                    "clean"
                ){

                    if(
                        data.kind ===
                        "folder"
                    ){

                        await cleanApi(
                            "remove-folder",
                            {
                                id:
                                    data.id
                            }
                        );


                        selectedCleanFolder =
                            "clean-root";

                    }
                    else{

                        await cleanApi(
                            "remove-item",
                            {
                                id:
                                    data.id
                            }
                        );

                    }


                    await loadWorkspace();


                    status(
                        "Removed from Clean staging. Main Inventory remains untouched.",
                        false
                    );

                }

            }
            catch(error){

                status(
                    error.message,
                    true
                );

            }

        }
    );

}


document.getElementById(
    "addFolderButton"
).addEventListener(
    "click",
    addSelectedFolder
);


document.getElementById(
    "addItemButton"
).addEventListener(
    "click",
    addSelectedItem
);


document.getElementById(
    "createFolderButton"
).addEventListener(
    "click",
    createFolder
);


document.getElementById(
    "renameFolderButton"
).addEventListener(
    "click",
    renameFolder
);


document.getElementById(
    "removeFolderButton"
).addEventListener(
    "click",
    removeSelectedFolder
);


document.getElementById(
    "removeItemButton"
).addEventListener(
    "click",
    removeSelectedItem
);


document.getElementById(
    "clearCleanButton"
).addEventListener(
    "click",
    clearClean
);


const refreshButton =
    document.getElementById(
        "refreshButton"
    );

if(refreshButton){

    refreshButton.addEventListener(
        "click",
        loadWorkspace
    );

}


enableDropZone(
    document.getElementById(
        "cleanDropZone"
    ),
    "clean"
);


enableDropZone(
    document.getElementById(
        "mainDropZone"
    ),
    "main"
);


loadWorkspace();


})();

</script>



<!-- AUSTRALIA SAFETY IAR SCRIPT V1.1 START -->

<script>

(function(){


const API =
    "/Other/user-safety-iar.php";


const CSRF =
    <?=json_encode(
        $csrfToken,
        JSON_UNESCAPED_SLASHES
    )?>;


const createButton =
    document.getElementById(
        "createSafetyIar"
    );


const downloadButton =
    document.getElementById(
        "downloadSafetyIar"
    );


const badge =
    document.getElementById(
        "safetyIarBadge"
    );


const message =
    document.getElementById(
        "safetyIarMessage"
    );


const spinner =
    document.getElementById(
        "safetySpinner"
    );


const info =
    document.getElementById(
        "safetyFileInfo"
    );


const fileName =
    document.getElementById(
        "safetyFileName"
    );


const fileSize =
    document.getElementById(
        "safetyFileSize"
    );


const verifiedBox =
    document.getElementById(
        "safetyVerifiedBox"
    );


const replacement =
    document.getElementById(
        "finalReplacementButton"
    );


const replacementText =
    document.getElementById(
        "replacementSafetyText"
    );


let job =
    "";


let timer =
    null;


function formatBytes(
    value
){

    const n =
        Number(
            value ||
            0
        );


    if(n < 1024){
        return n + " bytes";
    }


    if(
        n <
        1024 * 1024
    ){

        return (
            n /
            1024
        ).toFixed(1) +
        " KB";
    }


    if(
        n <
        1024 * 1024 * 1024
    ){

        return (
            n /
            1024 /
            1024
        ).toFixed(2) +
        " MB";
    }


    return (
        n /
        1024 /
        1024 /
        1024
    ).toFixed(2) +
    " GB";
}


function setBadge(
    text,
    style
){

    badge.textContent =
        text;


    badge.className =
        "safety-status-badge " +
        style;
}


async function callApi(
    action,
    options
){

    const url =
        new URL(
            API,
            window.location.origin
        );


    url.searchParams.set(
        "api",
        action
    );


    if(
        options &&
        options.job
    ){

        url.searchParams.set(
            "job",
            options.job
        );
    }


    const fetchOptions = {

        cache:
            "no-store",

        credentials:
            "same-origin"
    };


    if(
        options &&
        options.post
    ){

        fetchOptions.method =
            "POST";


        fetchOptions.headers = {

            "Content-Type":
                "application/json",

            "X-CSRF-Token":
                CSRF
        };


        fetchOptions.body =
            "{}";
    }


    const response =
        await fetch(
            url.toString(),
            fetchOptions
        );


    const data =
        await response.json();


    if(
        !response.ok ||
        !data.ok
    ){

        const error =
            new Error(
                data.error ||
                "Safety IAR request failed."
            );


        error.data =
            data;


        throw error;
    }


    return data;
}


function render(
    data
){

    if(data.jobId){
        job = data.jobId;
    }


    message.textContent =
        data.message ||
        "";


    if(data.name){

        info.hidden =
            false;


        fileName.textContent =
            data.name;


        fileSize.textContent =
            formatBytes(
                data.size
            );
    }
    else{

        info.hidden =
            true;
    }


    if(data.verified){

        setBadge(
            "VERIFIED",
            "safety-good"
        );


        spinner.hidden =
            true;


        verifiedBox.hidden =
            false;


        downloadButton.hidden =
            false;


        downloadButton.href =
            data.downloadUrl;


        createButton.disabled =
            false;


        createButton.textContent =
            "CREATE NEW SAFETY IAR";


        replacementText.textContent =
            "Your Safety IAR is VERIFIED. The emergency backup requirement is complete. The Clean-to-Main replacement engine remains locked until we connect the next stage.";


        replacement.textContent =
            "SAFETY IAR VERIFIED - REPLACEMENT ENGINE NEXT";


        replacement.disabled =
            true;


        if(timer){

            clearTimeout(
                timer
            );

            timer =
                null;
        }


        return;
    }


    verifiedBox.hidden =
        true;


    downloadButton.hidden =
        true;


    if(data.failed){

        setBadge(
            "FAILED",
            "safety-bad"
        );


        spinner.hidden =
            true;


        createButton.disabled =
            false;


        createButton.textContent =
            "TRY SAFETY IAR AGAIN";


        replacement.textContent =
            "SAFETY IAR REQUIRED BEFORE INVENTORY REPLACEMENT";


        return;
    }


    setBadge(
        "PROCESSING",
        "safety-processing"
    );


    spinner.hidden =
        false;


    createButton.disabled =
        true;


    createButton.textContent =
        "SAFETY IAR PROCESSING...";


    replacement.textContent =
        "SAFETY IAR PROCESSING - INVENTORY REPLACEMENT LOCKED";
}


async function poll(){

    if(!job){
        return;
    }


    try{

        const data =
            await callApi(
                "status",
                {
                    job:
                        job
                }
            );


        render(
            data
        );


        if(
            !data.verified &&
            !data.failed
        ){

            timer =
                setTimeout(
                    poll,
                    4000
                );
        }

    }
    catch(error){

        message.textContent =
            error.message;


        setBadge(
            "STATUS CHECK",
            "safety-bad"
        );


        spinner.hidden =
            true;


        timer =
            setTimeout(
                poll,
                7000
            );
    }
}


async function startBackup(){

    const safetyConfirmed =
        await auConfirm({

            title:
                "CREATE SAFETY INVENTORY IAR",

            message:
                "Create a full emergency Safety IAR of your CURRENT Main Inventory?",

            warning:
                "This is a backup only. It will NOT delete, move or alter your live inventory.",

            confirmText:
                "CREATE SAFETY IAR",

            cancelText:
                "CANCEL"
        });


    if(
        !safetyConfirmed
    ){
        return;
    }


    createButton.disabled =
        true;


    spinner.hidden =
        false;


    setBadge(
        "STARTING",
        "safety-processing"
    );


    message.textContent =
        "Starting full Safety Inventory IAR...";


    verifiedBox.hidden =
        true;


    downloadButton.hidden =
        true;


    try{

        const data =
            await callApi(
                "start",
                {
                    post:
                        true
                }
            );


        job =
            data.jobId;


        render(
            data
        );


        timer =
            setTimeout(
                poll,
                2500
            );

    }
    catch(error){

        if(
            error.data &&
            error.data.jobId
        ){

            job =
                error.data.jobId;


            render(
                error.data
            );


            timer =
                setTimeout(
                    poll,
                    3000
                );


            return;
        }


        setBadge(
            "FAILED TO START",
            "safety-bad"
        );


        spinner.hidden =
            true;


        message.textContent =
            error.message;


        createButton.disabled =
            false;


        createButton.textContent =
            "TRY SAFETY IAR AGAIN";
    }
}


async function resume(){

    try{

        const data =
            await callApi(
                "latest"
            );


        if(!data.found){

            setBadge(
                "NOT CREATED",
                "safety-waiting"
            );


            message.textContent =
                "No Safety Inventory IAR has been created yet.";


            createButton.disabled =
                false;


            return;
        }


        render(
            data
        );


        if(
            !data.verified &&
            !data.failed
        ){

            timer =
                setTimeout(
                    poll,
                    3000
                );
        }

    }
    catch(error){

        setBadge(
            "STATUS UNAVAILABLE",
            "safety-bad"
        );


        message.textContent =
            error.message;


        createButton.disabled =
            false;
    }
}


createButton.addEventListener(
    "click",
    startBackup
);


window.addEventListener(
    "pagehide",
    function(){

        if(timer){

            clearTimeout(
                timer
            );
        }
    }
);


resume();


})();

</script>

<!-- AUSTRALIA SAFETY IAR SCRIPT V1.1 END -->
<script src="/Other/australia-modal.js?v=1"></script>
<script defer src="/Other/core/ag-confirm-modal.js?v=1"></script>

<script id="ag-clean-inventory-ux-v2-js">

(function(){

    const mainTree =
        document.getElementById("mainFolders");

    const mainItems =
        document.getElementById("mainItemList");

    const cleanTree =
        document.getElementById("cleanFolderTree");

    const cleanItems =
        document.getElementById("cleanItemList");

    if(
        !mainTree ||
        !mainItems ||
        !cleanTree ||
        !cleanItems
    ){
        return;
    }

    if(
        document.body.dataset.agCleanInventoryUxV2 ===
        "1"
    ){
        return;
    }

    document.body.dataset.agCleanInventoryUxV2 =
        "1";


    /* ======================================================
       REAL UUID-BASED OPEN/CLOSE MEMORY
       ====================================================== */

    const mainExpanded = new Set();
    const cleanExpanded = new Set();

    let mainInitialised = false;
    let cleanInitialised = false;
    let cleanRootExpanded = true;

    let mainTimer = null;
    let cleanTimer = null;


    function directButton(node){

        if(!node){
            return null;
        }

        return Array.from(node.children).find(
            function(child){

                return child.classList &&
                    child.classList.contains(
                        "folder-button"
                    );
            }
        ) || null;
    }


    function directChildren(node){

        if(!node){
            return null;
        }

        return Array.from(node.children).find(
            function(child){

                return child.classList &&
                    child.classList.contains(
                        "folder-children"
                    );
            }
        ) || null;
    }


    function directFolderNodes(box){

        if(!box){
            return [];
        }

        return Array.from(box.children).filter(
            function(child){

                return child.classList &&
                    child.classList.contains(
                        "folder-node"
                    );
            }
        );
    }


    function folderNameFromButton(button){

        if(!button){
            return "";
        }

        const name =
            button.querySelector(".folder-name");

        return name
            ? name.textContent.trim()
            : "";
    }


    function folderIdFromButton(button){

        if(!button){
            return "";
        }

        return String(
            button.dataset.folderId || ""
        ).trim().toLowerCase();
    }


    function setFolderIcon(button,isOpen){

        if(!button){
            return;
        }

        const icon =
            button.querySelector(".folder-icon");

        if(!icon){
            return;
        }

        let img =
            icon.querySelector(
                "img.ag-clean-sentinel-img"
            );

        if(!img){

            icon.textContent = "";

            img =
                makeCleanSentinelIcon(
                    "files.png",
                    "ag-clean-icon-folder"
                );

            icon.appendChild(img);
        }

        icon.classList.toggle(
            "ag-folder-open",
            Boolean(isOpen)
        );
    }

    function applyTree(
        tree,
        expanded,
        isMain
    ){

        const nodes =
            Array.from(
                tree.querySelectorAll(
                    ".folder-node"
                )
            );

        if(nodes.length === 0){
            return false;
        }

        if(
            isMain &&
            !mainInitialised
        ){

            let openedRoot = false;

            nodes.forEach(
                function(node){

                    if(node.parentElement !== tree){
                        return;
                    }

                    const button =
                        directButton(node);

                    const children =
                        directChildren(node);

                    if(!button || !children){
                        return;
                    }

                    const label =
                        folderNameFromButton(button)
                            .toLowerCase();

                    if(
                        label === "inventory" ||
                        label === "my inventory"
                    ){

                        const id =
                            folderIdFromButton(button);

                        if(id){
                            expanded.add(id);
                            openedRoot = true;
                        }
                    }
                }
            );

            if(!openedRoot){

                const firstRoot =
                    nodes.find(
                        function(node){

                            return
                                node.parentElement === tree &&
                                !!directChildren(node);
                        }
                    );

                if(firstRoot){

                    const firstButton =
                        directButton(firstRoot);

                    const firstId =
                        folderIdFromButton(firstButton);

                    if(firstId){
                        expanded.add(firstId);
                    }
                }
            }
        }


        nodes.forEach(
            function(node){

                const button =
                    directButton(node);

                const children =
                    directChildren(node);

                if(!button){
                    return;
                }

                const id =
                    folderIdFromButton(button);

                if(children){

                    button.classList.add(
                        "ag-has-children"
                    );

                    const open =
                        !!id &&
                        expanded.has(id);

                    node.classList.toggle(
                        "ag-expanded",
                        open
                    );

                    node.classList.toggle(
                        "ag-collapsed",
                        !open
                    );

                    setFolderIcon(
                        button,
                        open
                    );

                }
                else{

                    button.classList.remove(
                        "ag-has-children"
                    );

                    node.classList.remove(
                        "ag-expanded"
                    );

                    node.classList.remove(
                        "ag-collapsed"
                    );

                    setFolderIcon(
                        button,
                        false
                    );
                }
            }
        );

        if(isMain){
            mainInitialised = true;
        }
        else{
            cleanInitialised = true;
        }

        return true;
    }


    function applyCleanRoot(){

        const rootButton =
            Array.from(cleanTree.children).find(
                function(child){

                    return child.classList &&
                        child.classList.contains(
                            "folder-button"
                        );
                }
            );

        const rootChildren =
            Array.from(cleanTree.children).find(
                function(child){

                    return child.classList &&
                        child.classList.contains(
                            "folder-children"
                        );
                }
            );

        if(!rootButton){
            return;
        }

        if(rootChildren){

            rootButton.classList.add(
                "ag-root-has-children"
            );

            cleanTree.classList.toggle(
                "ag-root-collapsed",
                !cleanRootExpanded
            );

            setFolderIcon(
                rootButton,
                cleanRootExpanded
            );

        }
        else{

            rootButton.classList.remove(
                "ag-root-has-children"
            );

            cleanTree.classList.remove(
                "ag-root-collapsed"
            );

            setFolderIcon(
                rootButton,
                false
            );
        }
    }


    function applyMainTree(){

        if(
            applyTree(
                mainTree,
                mainExpanded,
                true
            )
        ){
            runSearch("main");
        }
    }


    function applyCleanTree(){

        applyCleanRoot();

        if(
            applyTree(
                cleanTree,
                cleanExpanded,
                false
            )
        ){
            runSearch("clean");
        }
    }


    function scheduleMain(){

        if(mainTimer){
            clearTimeout(mainTimer);
        }

        mainTimer =
            setTimeout(
                applyMainTree,
                60
            );
    }


    function scheduleClean(){

        if(cleanTimer){
            clearTimeout(cleanTimer);
        }

        cleanTimer =
            setTimeout(
                applyCleanTree,
                60
            );
    }


    /* ======================================================
       BREADCRUMBS
       ====================================================== */

    function normalFolderName(name){

        const raw =
            String(name || "").trim();

        if(
            raw.toLowerCase() ===
            "my inventory"
        ){
            return "Inventory";
        }

        return raw;
    }


    function pathFromButton(
        button,
        tree,
        cleanSide
    ){

        if(!button){
            return [];
        }

        if(
            cleanSide &&
            button.parentElement === tree
        ){
            return ["Clean Inventory"];
        }

        const parts = [];
        let node =
            button.closest(".folder-node");

        while(node){

            const currentButton =
                directButton(node);

            if(currentButton){

                const label =
                    normalFolderName(
                        folderNameFromButton(
                            currentButton
                        )
                    );

                if(label){
                    parts.unshift(label);
                }
            }

            const parentBox =
                node.parentElement;

            if(
                !parentBox ||
                parentBox === tree ||
                !parentBox.classList.contains(
                    "folder-children"
                )
            ){
                break;
            }

            const parentNode =
                parentBox.parentElement;

            if(
                !parentNode ||
                !parentNode.classList.contains(
                    "folder-node"
                )
            ){
                break;
            }

            node = parentNode;
        }

        if(cleanSide){
            parts.unshift("Clean Inventory");
        }

        return parts;
    }


    function setBreadcrumb(
        target,
        parts
    ){

        if(!target){
            return;
        }

        const safe =
            parts.filter(Boolean);

        target.textContent =
            safe.length
            ? safe.join(" \u203A ")
            : "No folder selected";
    }


    const mainSelected =
        document.getElementById(
            "mainSelectedName"
        );

    const cleanSelected =
        document.getElementById(
            "cleanSelectedName"
        );

    const mainBreadcrumb =
        document.createElement("div");

    mainBreadcrumb.id =
        "agMainBreadcrumb";

    mainBreadcrumb.className =
        "ag-breadcrumb";

    mainBreadcrumb.textContent =
        "Inventory";

    const cleanBreadcrumb =
        document.createElement("div");

    cleanBreadcrumb.id =
        "agCleanBreadcrumb";

    cleanBreadcrumb.className =
        "ag-breadcrumb";

    cleanBreadcrumb.textContent =
        "Clean Inventory";

    if(
        mainSelected &&
        mainSelected.closest(
            ".selected-target"
        )
    ){

        mainSelected.closest(
            ".selected-target"
        ).insertAdjacentElement(
            "afterend",
            mainBreadcrumb
        );
    }

    if(
        cleanSelected &&
        cleanSelected.closest(
            ".selected-target"
        )
    ){

        cleanSelected.closest(
            ".selected-target"
        ).insertAdjacentElement(
            "afterend",
            cleanBreadcrumb
        );
    }


    /* ======================================================
       SEARCH
       ====================================================== */

    let mainSearchInput = null;
    let cleanSearchInput = null;
    let mainSearchResult = null;
    let cleanSearchResult = null;


    function buildSearch(
        tree,
        inputId,
        placeholder
    ){

        const side =
            tree.closest(".inventory-side");

        if(!side){
            return null;
        }

        const columns =
            side.querySelector(".side-columns");

        if(!columns){
            return null;
        }

        const box =
            document.createElement("div");

        box.className =
            "ag-inventory-search";

        const input =
            document.createElement("input");

        input.id = inputId;
        input.type = "search";
        input.className = "fs2-search";
        input.autocomplete = "off";
        input.spellcheck = false;
        input.placeholder = placeholder;

        const clear =
            document.createElement("button");

        clear.type = "button";
        clear.className =
            "cp-button fs2-tool ag-search-clear";
        clear.textContent =
            "CLEAR";

        const result =
            document.createElement("div");

        result.className =
            "ag-search-result";

        result.textContent =
            "Search folders or items in the current folder.";

        clear.addEventListener(
            "click",
            function(){

                input.value = "";

                input.dispatchEvent(
                    new Event(
                        "input",
                        {bubbles:true}
                    )
                );

                input.focus();
            }
        );

        box.appendChild(input);
        box.appendChild(clear);
        box.appendChild(result);

        columns.parentElement.insertBefore(
            box,
            columns
        );

        return {
            input:input,
            result:result
        };
    }


    const mainSearch =
        buildSearch(
            mainTree,
            "agMainInventorySearch",
            "Search Main Inventory folders / current items..."
        );

    const cleanSearch =
        buildSearch(
            cleanTree,
            "agCleanInventorySearch",
            "Search Clean Inventory folders / current items..."
        );

    if(mainSearch){
        mainSearchInput = mainSearch.input;
        mainSearchResult = mainSearch.result;
    }

    if(cleanSearch){
        cleanSearchInput = cleanSearch.input;
        cleanSearchResult = cleanSearch.result;
    }


    function normalizeSearch(value){

        return String(value || "")
            .trim()
            .toLowerCase();
    }


    function filterFolderTree(
        tree,
        query
    ){

        const q =
            normalizeSearch(query);

        const nodes =
            Array.from(
                tree.querySelectorAll(
                    ".folder-node"
                )
            );

        if(!q){

            nodes.forEach(
                function(node){

                    node.hidden = false;

                    node.classList.remove(
                        "ag-search-open"
                    );

                    node.classList.remove(
                        "ag-search-match"
                    );
                }
            );

            tree.classList.remove(
                "ag-root-search-open"
            );

            return nodes.length;
        }

        const keep =
            new Map();

        nodes.slice().reverse().forEach(
            function(node){

                const button =
                    directButton(node);

                const label =
                    normalizeSearch(
                        folderNameFromButton(button)
                    );

                const ownMatch =
                    label.includes(q);

                let childMatch = false;

                const childBox =
                    directChildren(node);

                directFolderNodes(
                    childBox
                ).forEach(
                    function(child){

                        if(keep.get(child)){
                            childMatch = true;
                        }
                    }
                );

                const show =
                    ownMatch ||
                    childMatch;

                keep.set(
                    node,
                    show
                );

                node.hidden =
                    !show;

                node.classList.toggle(
                    "ag-search-match",
                    ownMatch
                );

                node.classList.toggle(
                    "ag-search-open",
                    childMatch
                );
            }
        );

        if(tree === cleanTree){

            tree.classList.toggle(
                "ag-root-search-open",
                true
            );
        }

        return nodes.filter(
            function(node){
                return !node.hidden;
            }
        ).length;
    }


    function filterItemList(
        list,
        query
    ){

        const q =
            normalizeSearch(query);

        const rows =
            Array.from(
                list.querySelectorAll(
                    ".item-row"
                )
            );

        let visible = 0;

        rows.forEach(
            function(row){

                const match =
                    !q ||
                    normalizeSearch(
                        row.textContent
                    ).includes(q);

                row.hidden =
                    !match;

                if(match){
                    visible++;
                }
            }
        );

        return {
            total:rows.length,
            visible:visible
        };
    }


    function runSearch(side){

        const main =
            side === "main";

        const input =
            main
            ? mainSearchInput
            : cleanSearchInput;

        const result =
            main
            ? mainSearchResult
            : cleanSearchResult;

        const tree =
            main
            ? mainTree
            : cleanTree;

        const list =
            main
            ? mainItems
            : cleanItems;

        if(!input){
            return;
        }

        const query =
            input.value || "";

        const folderCount =
            filterFolderTree(
                tree,
                query
            );

        const itemCount =
            filterItemList(
                list,
                query
            );

        if(!result){
            return;
        }

        if(!normalizeSearch(query)){

            result.textContent =
                "Search folders or items in the current folder.";

            return;
        }

        result.textContent =
            folderCount.toLocaleString() +
            " matching folders | " +
            itemCount.visible.toLocaleString() +
            " matching current-folder items";
    }


    if(mainSearchInput){

        mainSearchInput.addEventListener(
            "input",
            function(){
                runSearch("main");
            }
        );
    }

    if(cleanSearchInput){

        cleanSearchInput.addEventListener(
            "input",
            function(){
                runSearch("clean");
            }
        );
    }


    /* ======================================================
       CLICK = SELECT + OPEN/CLOSE

       CAPTURE PHASE IS INTENTIONAL.
       Clean tree rebuilds itself during its existing click
       handler, so we remember the UUID BEFORE that rebuild.
       ====================================================== */

    mainTree.addEventListener(
        "click",
        function(event){

            const button =
                event.target.closest(
                    ".folder-button"
                );

            if(
                !button ||
                !mainTree.contains(button)
            ){
                return;
            }

            const node =
                button.closest(".folder-node");

            if(!node){
                return;
            }

            const children =
                directChildren(node);

            const id =
                folderIdFromButton(button);

            if(children && id){

                if(mainExpanded.has(id)){
                    mainExpanded.delete(id);
                }
                else{
                    mainExpanded.add(id);
                }
            }

            setBreadcrumb(
                mainBreadcrumb,
                pathFromButton(
                    button,
                    mainTree,
                    false
                )
            );

            setTimeout(
                applyMainTree,
                0
            );
        },
        true
    );


    cleanTree.addEventListener(
        "click",
        function(event){

            const button =
                event.target.closest(
                    ".folder-button"
                );

            if(
                !button ||
                !cleanTree.contains(button)
            ){
                return;
            }

            if(button.parentElement === cleanTree){

                const rootChildren =
                    Array.from(
                        cleanTree.children
                    ).find(
                        function(child){

                            return
                                child.classList &&
                                child.classList.contains(
                                    "folder-children"
                                );
                        }
                    );

                if(rootChildren){
                    cleanRootExpanded =
                        !cleanRootExpanded;
                }

                setBreadcrumb(
                    cleanBreadcrumb,
                    ["Clean Inventory"]
                );

                setTimeout(
                    applyCleanTree,
                    0
                );

                return;
            }

            const node =
                button.closest(".folder-node");

            if(!node){
                return;
            }

            const children =
                directChildren(node);

            const id =
                folderIdFromButton(button);

            if(children && id){

                if(cleanExpanded.has(id)){
                    cleanExpanded.delete(id);
                }
                else{
                    cleanExpanded.add(id);
                }
            }

            setBreadcrumb(
                cleanBreadcrumb,
                pathFromButton(
                    button,
                    cleanTree,
                    true
                )
            );

            setTimeout(
                applyCleanTree,
                0
            );
        },
        true
    );


    /* ======================================================
       EXISTING MANUAL REFRESH
       ====================================================== */

    const refreshButton =
        document.getElementById(
            "refreshButton"
        );

    if(refreshButton){

        refreshButton.title =
            "Manually reload Main Inventory and Clean Inventory";

        refreshButton.setAttribute(
            "data-ag-manual-refresh",
            "1"
        );
    }


    /* ======================================================
       REBUILD WATCHERS
       ====================================================== */

    const mainObserver =
        new MutationObserver(
            scheduleMain
        );

    mainObserver.observe(
        mainTree,
        {
            childList:true,
            subtree:true
        }
    );

    const cleanObserver =
        new MutationObserver(
            scheduleClean
        );

    cleanObserver.observe(
        cleanTree,
        {
            childList:true,
            subtree:true
        }
    );

    const mainItemsObserver =
        new MutationObserver(
            function(){
                runSearch("main");
            }
        );

    mainItemsObserver.observe(
        mainItems,
        {
            childList:true,
            subtree:true
        }
    );

    const cleanItemsObserver =
        new MutationObserver(
            function(){
                runSearch("clean");
            }
        );

    cleanItemsObserver.observe(
        cleanItems,
        {
            childList:true,
            subtree:true
        }
    );


    applyMainTree();
    applyCleanTree();
    runSearch("main");
    runSearch("clean");

})();

</script>
<!-- AUSTRALIA-INVENTORY-COUNT-COLOURS-STYLE-V1 START -->

<!-- AUSTRALIA-INVENTORY-COUNT-COLOURS-STYLE-V1 END -->
<!-- AUSTRALIA-INVENTORY-COUNT-COLOURS-SCRIPT-V1 START -->
<script id="ag-inventory-count-colours-js-v1">
(function(){
    function parseCountText(text){
        if(!text){ return null; }

        text = String(text).replace(/\s+/g, " ").trim();

        let m = text.match(/^(.*?)[ ]*\(([0-9,]+)\)$/);
        if(m){
            return {
                name: m[1].trim(),
                count: parseInt(m[2].replace(/,/g,''),10),
                empty: parseInt(m[2].replace(/,/g,''),10) === 0
            };
        }

        m = text.match(/^(.*?)[ ]+([0-9,]+)[ ]+EMPTY$/i);
        if(m){
            return {
                name: m[1].trim(),
                count: parseInt(m[2].replace(/,/g,''),10),
                empty: true
            };
        }

        return null;
    }

    function normaliseButton(button){
        if(!button){ return; }

        const nameEl =
            button.querySelector('.folder-name') ||
            button.querySelector('.folder-label') ||
            button.querySelector('.folder-title') ||
            button.querySelector('.tree-label');

        if(!nameEl){ return; }

        let parsed = parseCountText(nameEl.textContent || '');

        if(!parsed){
            const existing =
                button.querySelector('.folder-count') ||
                button.querySelector('.count') ||
                button.querySelector('.item-count') ||
                button.querySelector('.ag-folder-count');

            if(existing){
                const n = (existing.textContent || '').match(/[0-9][0-9,]*/);
                if(n){
                    parsed = {
                        name: (nameEl.textContent || '').trim(),
                        count: parseInt(n[0].replace(/,/g,''),10),
                        empty: parseInt(n[0].replace(/,/g,''),10) === 0 || /empty/i.test(existing.textContent || '')
                    };

                    if(!existing.classList.contains('ag-folder-count')){
                        existing.style.display = 'none';
                    }
                }
            }
        }

        if(!parsed){ return; }

        nameEl.textContent = parsed.name;

        let countEl = button.querySelector('.ag-folder-count');
        if(!countEl){
            countEl = document.createElement('span');
            countEl.className = 'ag-folder-count';
            button.appendChild(countEl);
        }

        const value = Number.isFinite(parsed.count) ? parsed.count : 0;
        const empty = parsed.empty || value === 0;

        countEl.textContent = empty ? '0 EMPTY' : String(value);
        countEl.classList.toggle('zero', empty);
    }

    function runAll(){
        document.querySelectorAll(
            '#folderTree .folder-button,' +
            '#mainFolders .folder-button,' +
            '#cleanFolderTree .folder-button,' +
            '.folder-tree .folder-button'
        ).forEach(normaliseButton);
    }

    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', runAll);
    } else {
        runAll();
    }

    const obs = new MutationObserver(function(){
        window.requestAnimationFrame(runAll);
    });

    obs.observe(document.documentElement, {
        childList: true,
        subtree: true
    });

    window.addEventListener('load', runAll);
})();
</script>
<!-- AUSTRALIA-INVENTORY-COUNT-COLOURS-SCRIPT-V1 END -->

<script id="ag-inventory-duplicate-cleaner-v1-js">

/* Grid - CLEAN INVENTORY DUPLICATE FINDER V1 */

function agDupNormal(value){
    return String(value || "").trim().toLowerCase();
}

function agDupValidAsset(value){
    const id = agDupNormal(value);
    return (
        id !== "" &&
        id !== "00000000-0000-0000-0000-000000000000"
    );
}

function agDupShort(value){
    const text = String(value || "");
    if(text.length <= 16){
        return text || "No Asset UUID";
    }
    return text.substring(0,8) + "\u2026" + text.substring(text.length - 6);
}

function agDupBuild(
    items,
    folders,
    rootLabel
){

    const folderMap = new Map();

    (folders || []).forEach(
        function(folder){
            const id = agDupNormal(folder.id);
            if(id){
                folderMap.set(id,folder);
            }
        }
    );

    function folderPath(rawId){

        let current = agDupNormal(rawId);

        if(current === "clean-root"){
            return rootLabel;
        }

        const parts = [];
        const seen = new Set();

        for(let depth=0;depth<64;depth++){

            if(!current || seen.has(current)){
                break;
            }

            if(current === "clean-root"){
                if(rootLabel){
                    parts.unshift(rootLabel);
                }
                break;
            }

            seen.add(current);

            const folder = folderMap.get(current);

            if(!folder){
                break;
            }

            const name = String(folder.name || "").trim();

            if(name){
                parts.unshift(
                    name.toLowerCase() === "my inventory"
                    ? "Inventory"
                    : name
                );
            }

            current = agDupNormal(
                folder.parentId ||
                folder.parent ||
                ""
            );
        }

        return parts.length
            ? parts.join(" \u203A ")
            : (rootLabel || "Inventory");
    }

    const prepared =
        (items || []).map(
            function(item){
                return {
                    id:String(item.id || item.sourceId || ""),
                    name:String(item.name || "(Unnamed item)"),
                    assetId:String(item.assetId || ""),
                    assetType:item.assetType,
                    folderId:String(item.folderId || "clean-root"),
                    folderPath:folderPath(
                        item.folderId ||
                        "clean-root"
                    )
                };
            }
        );

    const byAsset = new Map();
    const byName = new Map();

    prepared.forEach(
        function(item){

            const asset = agDupNormal(item.assetId);
            const name = agDupNormal(item.name);

            if(agDupValidAsset(asset)){
                if(!byAsset.has(asset)){
                    byAsset.set(asset,[]);
                }
                byAsset.get(asset).push(item);
            }

            if(name){
                if(!byName.has(name)){
                    byName.set(name,[]);
                }
                byName.get(name).push(item);
            }
        }
    );

    const exact = [];

    byAsset.forEach(
        function(list,asset){

            if(list.length < 2){
                return;
            }

            const names = new Set(
                list.map(
                    function(item){
                        return agDupNormal(item.name);
                    }
                )
            );

            const folderIds = new Set(
                list.map(
                    function(item){
                        return agDupNormal(item.folderId);
                    }
                )
            );

            exact.push({
                title:list[0].name,
                assetId:asset,
                assetType:list[0].assetType,
                items:list,
                folders:folderIds.size,
                sameName:names.size === 1
            });
        }
    );

    const review = [];

    byName.forEach(
        function(list,nameKey){

            if(list.length < 2){
                return;
            }

            const assets = new Set();

            list.forEach(
                function(item){
                    const asset = agDupNormal(item.assetId);
                    if(agDupValidAsset(asset)){
                        assets.add(asset);
                    }
                }
            );

            if(assets.size < 2){
                return;
            }

            const folderIds = new Set(
                list.map(
                    function(item){
                        return agDupNormal(item.folderId);
                    }
                )
            );

            review.push({
                title:list[0].name,
                assetId:"",
                assetType:list[0].assetType,
                items:list,
                folders:folderIds.size,
                sameName:true
            });
        }
    );

    function sortGroups(a,b){
        if(b.items.length !== a.items.length){
            return b.items.length - a.items.length;
        }
        return String(a.title).localeCompare(String(b.title));
    }

    exact.sort(sortGroups);
    review.sort(sortGroups);

    return {
        exact:exact,
        review:review,
        exactGroups:exact.length,
        reviewGroups:review.length,
        extraCopies:exact.reduce(
            function(total,group){
                return total + Math.max(0,group.items.length - 1);
            },
            0
        ),
        crossFolder:exact.filter(
            function(group){
                return group.folders > 1;
            }
        ).length
    };
}

function agDupRender(target,data,mode){

    target.innerHTML = "";

    const metrics = document.createElement("div");
    metrics.className = "ag-dup-metrics";

    [
        ["EXACT GROUPS",data.exactGroups],
        ["EXTRA COPIES",data.extraCopies],
        ["CROSS-FOLDER",data.crossFolder],
        ["NAME REVIEW",data.reviewGroups]
    ].forEach(
        function(metric){
            const box = document.createElement("div");
            box.className = "ag-dup-metric";

            const number = document.createElement("strong");
            number.textContent = Number(metric[1] || 0).toLocaleString();

            const label = document.createElement("span");
            label.textContent = metric[0];

            box.appendChild(number);
            box.appendChild(label);
            metrics.appendChild(box);
        }
    );

    target.appendChild(metrics);

    function section(titleText,groups,reviewMode){

        const section = document.createElement("div");
        section.className = "ag-dup-section";

        const heading = document.createElement("div");
        heading.className = "ag-dup-section-title";
        heading.textContent = titleText + "  " + groups.length.toLocaleString();
        section.appendChild(heading);

        if(groups.length === 0){
            const none = document.createElement("div");
            none.className = "ag-dup-none";
            none.textContent = reviewMode
                ? "No same-name / different-asset groups found."
                : "No duplicate Asset UUID groups found.";
            section.appendChild(none);
            return section;
        }

        const maximum = Math.min(groups.length,300);

        for(let i=0;i<maximum;i++){

            const group = groups[i];
            const details = document.createElement("details");
            details.className = "ag-dup-group";

            const summary = document.createElement("summary");

            const info = window.agCleanDuplicateTypeInfo(group.assetType);

            const icon = document.createElement("span");
            icon.className = "ag-dup-icon";

            icon.appendChild(
                makeCleanSentinelIcon(
                    info[0],
                    "ag-clean-icon-duplicate"
                )
            );

            const title = document.createElement("span");
            title.className = "ag-dup-title";
            title.textContent = group.title || "(Unnamed item)";

            const tag = document.createElement("span");
            tag.className = reviewMode
                ? "ag-dup-tag review"
                : "ag-dup-tag exact";

            tag.textContent = reviewMode
                ? "REVIEW"
                : (group.sameName ? "EXACT COPY" : "SAME ASSET");

            const copies = document.createElement("span");
            copies.className = "ag-dup-copies";
            copies.textContent =
                group.items.length.toLocaleString() +
                " copies / " +
                group.folders.toLocaleString() +
                " folders";

            summary.appendChild(icon);
            summary.appendChild(title);
            summary.appendChild(tag);
            summary.appendChild(copies);

            details.appendChild(summary);

            const body = document.createElement("div");
            body.className = "ag-dup-body";

            const itemMaximum = Math.min(group.items.length,50);

            for(let x=0;x<itemMaximum;x++){

                const item = group.items[x];
                const row = document.createElement("div");
                row.className = "ag-dup-item";

                const copy = document.createElement("div");

                const itemName = document.createElement("strong");
                itemName.textContent = item.name;

                const folder = document.createElement("span");
                folder.textContent = item.folderPath;

                copy.appendChild(itemName);
                copy.appendChild(folder);

                const asset = document.createElement("code");
                asset.textContent = agDupShort(item.assetId);
                asset.title = item.assetId || "";

                row.appendChild(copy);
                row.appendChild(asset);

                /* CLEAN DUPLICATE STAGING CONTROLS V2 */
                if(
                    !reviewMode &&
                    (
                        mode === "main" ||
                        mode === "clean"
                    )
                ){

                    const action =
                        document.createElement(
                            "button"
                        );

                    action.type =
                        "button";

                    action.className =
                        mode === "main"
                        ? "cp-button fs2-tool ag-dup-stage-action keep"
                        : "cp-button fs2-tool ag-dup-stage-action remove";


                    if(mode === "main"){

                        const stagedItems =
                            (
                                window.agCleanDuplicateState &&
                                window.agCleanDuplicateState.items
                            )
                            ? window.agCleanDuplicateState.items
                            : {};

                        const alreadyStaged =
                            Object.prototype.hasOwnProperty.call(
                                stagedItems,
                                item.id
                            );

                        if(alreadyStaged){

                            action.textContent =
                                "IN CLEAN";

                            action.disabled =
                                true;
                        }
                        else{

                            action.textContent =
                                "KEEP THIS COPY";
                        }

                        action.title =
                            "Copy this exact inventory item into the currently selected Clean Inventory folder.";
                    }
                    else{

                        action.textContent =
                            "REMOVE FROM CLEAN";

                        action.title =
                            "Remove this item from Clean Inventory staging only.";
                    }


                    action.addEventListener(
                        "click",
                        async function(event){

                            event.preventDefault();
                            event.stopPropagation();

                            if(action.disabled){
                                return;
                            }

                            action.disabled =
                                true;

                            const oldText =
                                action.textContent;

                            action.textContent =
                                mode === "main"
                                ? "ADDING..."
                                : "REMOVING...";

                            try{

                                if(mode === "main"){

                                    let targetFolder =
                                        window.agCleanDuplicateSelectedFolder ||
                                        "clean-root";

                                    const currentState =
                                        window.agCleanDuplicateState ||
                                        {folders:{},items:{}};

                                    if(
                                        targetFolder !== "clean-root" &&
                                        !(
                                            currentState.folders &&
                                            currentState.folders[targetFolder]
                                        )
                                    ){

                                        targetFolder =
                                            "clean-root";
                                    }

                                    await window.agCleanDuplicateCleanApi(
                                        "add-item",
                                        {
                                            sourceId:item.id,
                                            targetFolder:targetFolder
                                        }
                                    );

                                    window.agCleanDuplicateStatus(
                                        "Item copied into Clean Inventory staging. Main Inventory remains untouched.",
                                        false
                                    );
                                }
                                else{

                                    await window.agCleanDuplicateCleanApi(
                                        "remove-item",
                                        {
                                            id:item.id
                                        }
                                    );

                                    window.agCleanDuplicateStatus(
                                        "Item removed from Clean staging only. Main Inventory remains untouched.",
                                        false
                                    );
                                }


                                await window.agCleanDuplicateLoadWorkspace();

                                const duplicatePanel =
                                    document.getElementById(
                                        "agCleanDuplicateFinder"
                                    );

                                const scanButton =
                                    duplicatePanel
                                    ? duplicatePanel.querySelector(
                                        ".ag-dup-scan"
                                      )
                                    : null;

                                if(
                                    scanButton &&
                                    !scanButton.disabled
                                ){

                                    scanButton.click();
                                }
                            }
                            catch(error){

                                action.textContent =
                                    oldText;

                                action.disabled =
                                    false;

                                window.agCleanDuplicateStatus(
                                    error.message,
                                    true
                                );
                            }
                        }
                    );

                    row.appendChild(action);
                }

                body.appendChild(row);
            }

            if(group.items.length > itemMaximum){
                const more = document.createElement("div");
                more.className = "ag-dup-more";
                more.textContent =
                    "+" +
                    (group.items.length - itemMaximum).toLocaleString() +
                    " more copies";
                body.appendChild(more);
            }

            details.appendChild(body);
            section.appendChild(details);
        }

        if(groups.length > maximum){
            const more = document.createElement("div");
            more.className = "ag-dup-more";
            more.textContent =
                "Showing first " +
                maximum.toLocaleString() +
                " of " +
                groups.length.toLocaleString() +
                " groups.";
            section.appendChild(more);
        }

        return section;
    }

    target.appendChild(
        section(
            "SAME ASSET UUID",
            data.exact,
            false
        )
    );

    target.appendChild(
        section(
            "SAME NAME / DIFFERENT ASSET",
            data.review,
            true
        )
    );
}


(function(){

    function install(){

        if(document.getElementById("agCleanDuplicateFinder")){
            return;
        }

        const anchor =
            document.getElementById("agCleanInventoryTypeTotals") ||
            document.querySelector(".summary-grid");

        if(!anchor){
            return;
        }

        const panel = document.createElement("section");
        panel.id = "agCleanDuplicateFinder";
        panel.className = "rp-overview ag-duplicate-panel";

        const head = document.createElement("div");
        head.className = "rp-overview-heading ag-dup-head";

        const text = document.createElement("div");

        const title = document.createElement("div");
        title.className = "ag-dup-main-title";
        title.textContent = "DUPLICATE FINDER";

        const note = document.createElement("div");
        note.className = "ag-dup-note";
        note.textContent =
            "MAIN INVENTORY IS READ ONLY \u2014 CLEAN INVENTORY IS STAGING";

        text.appendChild(title);
        text.appendChild(note);

        const button = document.createElement("button");
        button.type = "button";
        button.className = "cp-button fs2-tool ag-dup-scan";
        button.textContent = "SCAN DUPLICATES";

        head.appendChild(text);
        head.appendChild(button);

        const split = document.createElement("div");
        split.className = "ag-dup-split";

        function box(titleText){

            const holder = document.createElement("div");
            holder.className = "ag-dup-side";

            const h = document.createElement("div");
            h.className = "ag-dup-side-title";
            h.textContent = titleText;

            const results = document.createElement("div");
            results.className = "ag-dup-results";
            results.innerHTML =
                '<div class="ag-dup-placeholder">Waiting for scan.</div>';

            holder.appendChild(h);
            holder.appendChild(results);

            return holder;
        }

        const mainBox = box("MAIN INVENTORY DUPLICATES");
        const cleanBox = box("CLEAN STAGING DUPLICATES");

        split.appendChild(mainBox);
        split.appendChild(cleanBox);

        panel.appendChild(head);
        panel.appendChild(split);

        anchor.insertAdjacentElement("afterend",panel);

        button.addEventListener("click",async function(){

            const mainResults =
                mainBox.querySelector(".ag-dup-results");

            const cleanResults =
                cleanBox.querySelector(".ag-dup-results");

            button.disabled = true;
            button.textContent = "SCANNING...";

            mainResults.innerHTML =
                '<div class="ag-dup-placeholder">Scanning Main Inventory...</div>';

            cleanResults.innerHTML =
                '<div class="ag-dup-placeholder">Scanning Clean staging...</div>';

            try{

                const response =
                    await window.agCleanDuplicateBrowserApi("duplicates");

                const mainReport =
                    agDupBuild(
                        response.items || [],
                        window.agCleanDuplicateMainFolders || [],
                        "Inventory"
                    );

                const cleanReport =
                    agDupBuild(
                        Object.values(
                            window.agCleanDuplicateState.items || {}
                        ),
                        Object.values(
                            window.agCleanDuplicateState.folders || {}
                        ),
                        "CLEAN INVENTORY ROOT"
                    );

                agDupRender(mainResults,mainReport,"main");
                agDupRender(cleanResults,cleanReport,"clean");

                button.textContent = "REFRESH SCAN";
            }
            catch(error){

                mainResults.innerHTML = "";
                cleanResults.innerHTML = "";

                const failed = document.createElement("div");
                failed.className = "ag-dup-error";
                failed.textContent = error.message;

                mainResults.appendChild(failed);

                button.textContent = "SCAN DUPLICATES";
            }
            finally{
                button.disabled = false;
            }
        });
    }

    if(document.readyState === "loading"){
        document.addEventListener("DOMContentLoaded",install);
    }
    else{
        install();
    }

    setTimeout(install,500);

})();

</script>

<script id="ag-clean-duplicate-refresh-clear-v1">
(function(){

    function clearDuplicateScan(){

        const panel =
            document.getElementById(
                "agCleanDuplicateFinder"
            );

        if(!panel){
            return;
        }

        const results =
            panel.querySelectorAll(
                ".ag-dup-results"
            );

        if(results[0]){
            results[0].innerHTML =
                '<div class="ag-dup-placeholder">Waiting for scan.</div>';
        }

        if(results[1]){
            results[1].innerHTML =
                '<div class="ag-dup-placeholder">Waiting for scan.</div>';
        }

        const scanButton =
            panel.querySelector(
                ".ag-dup-scan"
            );

        if(scanButton){
            scanButton.disabled = false;
            scanButton.textContent =
                "SCAN DUPLICATES";
        }
    }

    function install(){

        const refresh =
            document.getElementById(
                "refreshButton"
            );

        if(!refresh){
            return;
        }

        if(refresh.dataset.agDuplicateClearInstalled === "1"){
            return;
        }

        refresh.dataset.agDuplicateClearInstalled = "1";

        refresh.addEventListener(
            "click",
            clearDuplicateScan
        );
    }

    if(document.readyState === "loading"){
        document.addEventListener(
            "DOMContentLoaded",
            install
        );
    }
    else{
        install();
    }

    setTimeout(install,500);

})();
</script>
</body>

</html>





