<?php

require_once __DIR__ . '/core/bootstrap.php';

$session = ag_current_session();

if (!$session) {
    header('Location: /Other/login.php');
    exit;
}

$principalId = trim((string)($session['principalId'] ?? ''));
$avatar      = trim((string)($session['avatar'] ?? ''));

if ($principalId === '') {
    http_response_code(403);
    echo 'Signed account ID is unavailable.';
    exit;
}


/*
 * ============================================================
 * Grid INVENTORY BROWSER V1.1
 * READ ONLY
 * ============================================================
 */


function invJson(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


function qi(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}


function findInventoryTables(
    mysqli $con,
    string $preferredSchema
): array
{
    $sql =
        "SELECT TABLE_SCHEMA, TABLE_NAME
         FROM information_schema.TABLES
         WHERE LOWER(TABLE_NAME)
         IN ('inventoryfolders','inventoryitems')";

    $result = mysqli_query($con, $sql);

    if (!$result) {
        throw new Exception('Could not inspect inventory tables.');
    }

    $schemas = [];

    while ($row = mysqli_fetch_assoc($result)) {

        $schema = (string)$row['TABLE_SCHEMA'];
        $table  = (string)$row['TABLE_NAME'];

        if (!isset($schemas[$schema])) {
            $schemas[$schema] = [];
        }

        $schemas[$schema][strtolower($table)] = $table;
    }

    mysqli_free_result($result);

    if (
        isset($schemas[$preferredSchema]['inventoryfolders']) &&
        isset($schemas[$preferredSchema]['inventoryitems'])
    ) {
        return [
            'schema'  => $preferredSchema,
            'folders' => $schemas[$preferredSchema]['inventoryfolders'],
            'items'   => $schemas[$preferredSchema]['inventoryitems']
        ];
    }

    foreach ($schemas as $schema => $tables) {

        if (
            isset($tables['inventoryfolders']) &&
            isset($tables['inventoryitems'])
        ) {
            return [
                'schema'  => $schema,
                'folders' => $tables['inventoryfolders'],
                'items'   => $tables['inventoryitems']
            ];
        }
    }

    throw new Exception(
        'OpenSim inventoryfolders and inventoryitems tables were not found.'
    );
}


function tableColumns(
    mysqli $con,
    string $schema,
    string $table
): array
{
    $sql =
        "SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ?
         AND TABLE_NAME = ?";

    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        throw new Exception('Could not inspect inventory columns.');
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ss',
        $schema,
        $table
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $columns = [];

    while ($row = mysqli_fetch_assoc($result)) {

        $name = (string)$row['COLUMN_NAME'];

        $columns[strtolower($name)] = $name;
    }

    mysqli_stmt_close($stmt);

    return $columns;
}


function pickColumn(
    array $columns,
    array $names,
    bool $required = true
): ?string
{
    foreach ($names as $name) {

        $key = strtolower($name);

        if (isset($columns[$key])) {
            return $columns[$key];
        }
    }

    if ($required) {
        throw new Exception(
            'Required inventory column was not found: ' .
            implode(' / ', $names)
        );
    }

    return null;
}


/*
 * ============================================================
 * READ-ONLY INVENTORY API
 * ============================================================
 */


$apiAction = trim((string)($_GET['api'] ?? ''));


if ($apiAction !== '') {

    try {

        require __DIR__ . '/../MetroMap/includes/config.php';

        $con = @mysqli_connect(
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

        mysqli_set_charset($con, 'utf8mb4');


        $tables = findInventoryTables(
            $con,
            (string)$CONF_db_database
        );

        $schema      = $tables['schema'];
        $folderTable = $tables['folders'];
        $itemTable   = $tables['items'];


        $fc = tableColumns(
            $con,
            $schema,
            $folderTable
        );

        $ic = tableColumns(
            $con,
            $schema,
            $itemTable
        );


        /*
         * Folder columns
         */

        $folderId = pickColumn(
            $fc,
            ['folderID']
        );

        $folderOwner = pickColumn(
            $fc,
            ['agentID','avatarID']
        );

        $folderParent = pickColumn(
            $fc,
            ['parentFolderID','parentID']
        );

        $folderName = pickColumn(
            $fc,
            ['folderName','name']
        );

        $folderType = pickColumn(
            $fc,
            ['type'],
            false
        );


        /*
         * Item columns
         */

        $itemId = pickColumn(
            $ic,
            ['inventoryID']
        );

        $itemOwner = pickColumn(
            $ic,
            ['avatarID','agentID']
        );

        $itemParent = pickColumn(
            $ic,
            ['parentFolderID','folderID']
        );

        $itemName = pickColumn(
            $ic,
            ['inventoryName','name']
        );

        $itemDescription = pickColumn(
            $ic,
            ['inventoryDescription','description'],
            false
        );

        $assetId = pickColumn(
            $ic,
            ['assetID'],
            false
        );

        $assetType = pickColumn(
            $ic,
            ['assetType'],
            false
        );

        $inventoryType = pickColumn(
            $ic,
            ['invType','inventoryType'],
            false
        );

        $creatorId = pickColumn(
            $ic,
            ['creatorID'],
            false
        );

        $creationDate = pickColumn(
            $ic,
            ['creationDate'],
            false
        );


        /*
         * --------------------------------------------------------
         * SUMMARY
         * --------------------------------------------------------
         */

        /* AUSTRALIA INVENTORY ITEMMETA V4 START */

        /*
         * --------------------------------------------------------
         * ITEM METADATA / PERMISSIONS
         * READ ONLY
         * --------------------------------------------------------
         */

        if ($apiAction === 'itemmeta') {

            $requestedItemId =
                trim(
                    (string)(
                        $_GET['id']
                        ??
                        ''
                    )
                );


            if (
                !preg_match(
                    '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
                    $requestedItemId
                )
            ) {

                mysqli_close($con);

                invJson(
                    [
                        'ok'    => false,
                        'error' => 'Invalid inventory item UUID.'
                    ],
                    400
                );
            }


            /*
             * Permission columns differ slightly between
             * OpenSim database versions, so discover them.
             */

            $basePermissions =
                pickColumn(
                    $ic,
                    [
                        'inventoryBasePermissions',
                        'BasePermissions',
                        'basePermissions'
                    ],
                    false
                );


            $currentPermissions =
                pickColumn(
                    $ic,
                    [
                        'inventoryCurrentPermissions',
                        'CurrentPermissions',
                        'currentPermissions'
                    ],
                    false
                );


            $nextPermissions =
                pickColumn(
                    $ic,
                    [
                        'inventoryNextPermissions',
                        'NextPermissions',
                        'nextPermissions'
                    ],
                    false
                );


            $everyonePermissions =
                pickColumn(
                    $ic,
                    [
                        'inventoryEveryOnePermissions',
                        'inventoryEveryonePermissions',
                        'EveryOnePermissions',
                        'EveryonePermissions',
                        'everyonePermissions'
                    ],
                    false
                );


            $groupPermissions =
                pickColumn(
                    $ic,
                    [
                        'inventoryGroupPermissions',
                        'GroupPermissions',
                        'groupPermissions'
                    ],
                    false
                );


            $groupId =
                pickColumn(
                    $ic,
                    [
                        'groupID',
                        'groupId'
                    ],
                    false
                );


            $groupOwned =
                pickColumn(
                    $ic,
                    [
                        'groupOwned'
                    ],
                    false
                );


            $flags =
                pickColumn(
                    $ic,
                    [
                        'flags',
                        'Flags'
                    ],
                    false
                );


            $salePrice =
                pickColumn(
                    $ic,
                    [
                        'salePrice',
                        'SalePrice'
                    ],
                    false
                );


            $saleType =
                pickColumn(
                    $ic,
                    [
                        'saleType',
                        'SaleType'
                    ],
                    false
                );


            /*
             * Build a safe SELECT list using only actual
             * columns discovered from the current table.
             */

            $fields = [];


            $fields[] =
                qi($itemId) .
                ' inventory_id';


            $fields[] =
                qi($itemName) .
                ' inventory_name';


            $fields[] =
                $itemDescription
                    ? qi($itemDescription) . ' inventory_description'
                    : "'' inventory_description";


            $fields[] =
                $assetId
                    ? qi($assetId) . ' asset_id'
                    : "'' asset_id";


            $fields[] =
                $assetType
                    ? qi($assetType) . ' asset_type'
                    : 'NULL asset_type';


            $fields[] =
                $inventoryType
                    ? qi($inventoryType) . ' inventory_type'
                    : 'NULL inventory_type';


            $fields[] =
                $creatorId
                    ? qi($creatorId) . ' creator_id'
                    : "'' creator_id";


            $fields[] =
                $creationDate
                    ? qi($creationDate) . ' creation_date'
                    : 'NULL creation_date';


            $fields[] =
                qi($itemParent) .
                ' parent_folder_id';


            $fields[] =
                $basePermissions
                    ? qi($basePermissions) . ' base_permissions'
                    : 'NULL base_permissions';


            $fields[] =
                $currentPermissions
                    ? qi($currentPermissions) . ' current_permissions'
                    : 'NULL current_permissions';


            $fields[] =
                $nextPermissions
                    ? qi($nextPermissions) . ' next_permissions'
                    : 'NULL next_permissions';


            $fields[] =
                $everyonePermissions
                    ? qi($everyonePermissions) . ' everyone_permissions'
                    : 'NULL everyone_permissions';


            $fields[] =
                $groupPermissions
                    ? qi($groupPermissions) . ' group_permissions'
                    : 'NULL group_permissions';


            $fields[] =
                $groupId
                    ? qi($groupId) . ' group_id'
                    : "'' group_id";


            $fields[] =
                $groupOwned
                    ? qi($groupOwned) . ' group_owned'
                    : 'NULL group_owned';


            $fields[] =
                $flags
                    ? qi($flags) . ' item_flags'
                    : 'NULL item_flags';


            $fields[] =
                $salePrice
                    ? qi($salePrice) . ' sale_price'
                    : 'NULL sale_price';


            $fields[] =
                $saleType
                    ? qi($saleType) . ' sale_type'
                    : 'NULL sale_type';


            $sql =
                'SELECT ' .
                implode(
                    ', ',
                    $fields
                ) .
                ' FROM ' .
                qi($schema) .
                '.' .
                qi($itemTable) .
                ' WHERE ' .
                qi($itemOwner) .
                ' = ? AND ' .
                qi($itemId) .
                ' = ? LIMIT 1';


            $stmt =
                mysqli_prepare(
                    $con,
                    $sql
                );


            if (!$stmt) {

                mysqli_close($con);

                invJson(
                    [
                        'ok'    => false,
                        'error' => 'Could not prepare item metadata query.'
                    ],
                    500
                );
            }


            mysqli_stmt_bind_param(
                $stmt,
                'ss',
                $principalId,
                $requestedItemId
            );


            mysqli_stmt_execute(
                $stmt
            );


            $result =
                mysqli_stmt_get_result(
                    $stmt
                );


            $row =
                $result
                    ? mysqli_fetch_assoc(
                        $result
                    )
                    : null;


            mysqli_stmt_close(
                $stmt
            );


            mysqli_close(
                $con
            );


            if (!$row) {

                invJson(
                    [
                        'ok'    => false,
                        'error' => 'Inventory item was not found.'
                    ],
                    404
                );
            }


            invJson([
                'ok' => true,

                'item' => [

                    'id' =>
                        (string)$row['inventory_id'],

                    'name' =>
                        (string)($row['inventory_name'] ?? ''),

                    'description' =>
                        (string)($row['inventory_description'] ?? ''),

                    'assetId' =>
                        (string)($row['asset_id'] ?? ''),

                    'assetType' =>
                        $row['asset_type'],

                    'inventoryType' =>
                        $row['inventory_type'],

                    'creatorId' =>
                        (string)($row['creator_id'] ?? ''),

                    'creationDate' =>
                        $row['creation_date'],

                    'folderId' =>
                        (string)($row['parent_folder_id'] ?? ''),

                    'ownerId' =>
                        (string)$principalId,

                    'basePermissions' =>
                        $row['base_permissions'],

                    'currentPermissions' =>
                        $row['current_permissions'],

                    'nextPermissions' =>
                        $row['next_permissions'],

                    'everyonePermissions' =>
                        $row['everyone_permissions'],

                    'groupPermissions' =>
                        $row['group_permissions'],

                    'groupId' =>
                        (string)($row['group_id'] ?? ''),

                    'groupOwned' =>
                        $row['group_owned'] === null
                            ? null
                            : (
                                (int)$row['group_owned'] !== 0
                            ),

                    'flags' =>
                        $row['item_flags'],

                    'salePrice' =>
                        $row['sale_price'],

                    'saleType' =>
                        $row['sale_type']
                ]
            ]);
        }

        /* AUSTRALIA INVENTORY ITEMMETA V4 END */

        /* AUSTRALIA INVENTORY SEARCH V7 START */

        /*
         * --------------------------------------------------------
         * FIRESTORM INVENTORY SEARCH V7
         *
         * READ ONLY
         *
         * Searches only the current avatar's own Inventory.
         * --------------------------------------------------------
         */

        if ($apiAction === 'searchv7') {

            $searchText =
                trim(
                    (string)(
                        $_GET['q']
                        ??
                        ''
                    )
                );


            if (
                mb_strlen(
                    $searchText,
                    'UTF-8'
                ) < 2
            ) {

                mysqli_close($con);

                invJson(
                    [
                        'ok'      => true,
                        'query'   => $searchText,
                        'folders' => [],
                        'items'   => []
                    ]
                );
            }


            if (
                mb_strlen(
                    $searchText,
                    'UTF-8'
                ) > 100
            ) {

                $searchText =
                    mb_substr(
                        $searchText,
                        0,
                        100,
                        'UTF-8'
                    );
            }


            $like =
                '%' .
                $searchText .
                '%';


            /*
             * ----------------------------------------------------
             * FOLDER SEARCH
             * ----------------------------------------------------
             */

            $folderTypeSelect =
                $folderType
                    ? qi($folderType)
                    : 'NULL';


            $sql =
                'SELECT ' .

                qi($folderId) .
                ' folder_id, ' .

                qi($folderParent) .
                ' parent_folder_id, ' .

                qi($folderName) .
                ' folder_name, ' .

                $folderTypeSelect .
                ' folder_type ' .

                'FROM ' .
                qi($schema) .
                '.' .
                qi($folderTable) .

                ' WHERE ' .
                qi($folderOwner) .
                ' = ? ' .

                'AND ' .
                qi($folderName) .
                ' LIKE ? ' .

                'ORDER BY ' .
                qi($folderName) .

                ' LIMIT 150';


            $stmt =
                mysqli_prepare(
                    $con,
                    $sql
                );


            if (!$stmt) {

                mysqli_close($con);

                invJson(
                    [
                        'ok'    => false,
                        'error' => 'Could not prepare Inventory folder search.'
                    ],
                    500
                );
            }


            mysqli_stmt_bind_param(
                $stmt,
                'ss',
                $principalId,
                $like
            );


            mysqli_stmt_execute(
                $stmt
            );


            $result =
                mysqli_stmt_get_result(
                    $stmt
                );


            $foldersFound = [];


            if ($result) {

                while (
                    $row =
                        mysqli_fetch_assoc(
                            $result
                        )
                ) {

                    $foldersFound[] = [

                        'id' =>
                            (string)(
                                $row['folder_id']
                                ??
                                ''
                            ),

                        'parent' =>
                            (string)(
                                $row['parent_folder_id']
                                ??
                                ''
                            ),

                        'name' =>
                            (string)(
                                $row['folder_name']
                                ??
                                ''
                            ),

                        'type' =>
                            $row['folder_type']
                    ];
                }
            }


            mysqli_stmt_close(
                $stmt
            );


            /*
             * ----------------------------------------------------
             * ITEM SEARCH
             * ----------------------------------------------------
             */

            $descriptionSelect =
                $itemDescription
                    ? qi($itemDescription)
                    : "''";


            $assetIdSelect =
                $assetId
                    ? qi($assetId)
                    : "''";


            $assetTypeSelect =
                $assetType
                    ? qi($assetType)
                    : 'NULL';


            $inventoryTypeSelect =
                $inventoryType
                    ? qi($inventoryType)
                    : 'NULL';


            $creatorSelect =
                $creatorId
                    ? qi($creatorId)
                    : "''";


            $creationSelect =
                $creationDate
                    ? qi($creationDate)
                    : 'NULL';


            $sql =
                'SELECT ' .

                qi($itemId) .
                ' inventory_id, ' .

                qi($itemName) .
                ' inventory_name, ' .

                $descriptionSelect .
                ' inventory_description, ' .

                $assetIdSelect .
                ' asset_id, ' .

                $assetTypeSelect .
                ' asset_type, ' .

                $inventoryTypeSelect .
                ' inventory_type, ' .

                $creatorSelect .
                ' creator_id, ' .

                $creationSelect .
                ' creation_date, ' .

                qi($itemParent) .
                ' parent_folder_id ' .

                'FROM ' .
                qi($schema) .
                '.' .
                qi($itemTable) .

                ' WHERE ' .
                qi($itemOwner) .
                ' = ? ';


            if ($itemDescription) {

                $sql .=
                    'AND (' .
                    qi($itemName) .
                    ' LIKE ? OR ' .
                    qi($itemDescription) .
                    ' LIKE ?) ';
            }
            else {

                $sql .=
                    'AND ' .
                    qi($itemName) .
                    ' LIKE ? ';
            }


            $sql .=
                'ORDER BY ' .
                qi($itemName) .
                ' LIMIT 300';


            $stmt =
                mysqli_prepare(
                    $con,
                    $sql
                );


            if (!$stmt) {

                mysqli_close($con);

                invJson(
                    [
                        'ok'    => false,
                        'error' => 'Could not prepare Inventory item search.'
                    ],
                    500
                );
            }


            if ($itemDescription) {

                mysqli_stmt_bind_param(
                    $stmt,
                    'sss',
                    $principalId,
                    $like,
                    $like
                );
            }
            else {

                mysqli_stmt_bind_param(
                    $stmt,
                    'ss',
                    $principalId,
                    $like
                );
            }


            mysqli_stmt_execute(
                $stmt
            );


            $result =
                mysqli_stmt_get_result(
                    $stmt
                );


            $itemsFound = [];


            if ($result) {

                while (
                    $row =
                        mysqli_fetch_assoc(
                            $result
                        )
                ) {

                    $itemsFound[] = [

                        'id' =>
                            (string)(
                                $row['inventory_id']
                                ??
                                ''
                            ),

                        'name' =>
                            (string)(
                                $row['inventory_name']
                                ??
                                ''
                            ),

                        'description' =>
                            (string)(
                                $row['inventory_description']
                                ??
                                ''
                            ),

                        'assetId' =>
                            (string)(
                                $row['asset_id']
                                ??
                                ''
                            ),

                        'assetType' =>
                            $row['asset_type'],

                        'inventoryType' =>
                            $row['inventory_type'],

                        'creatorId' =>
                            (string)(
                                $row['creator_id']
                                ??
                                ''
                            ),

                        'creationDate' =>
                            $row['creation_date'],

                        'folderId' =>
                            (string)(
                                $row['parent_folder_id']
                                ??
                                ''
                            )
                    ];
                }
            }


            mysqli_stmt_close(
                $stmt
            );


            mysqli_close(
                $con
            );


            invJson(
                [
                    'ok'      => true,
                    'query'   => $searchText,
                    'folders' => $foldersFound,
                    'items'   => $itemsFound
                ]
            );
        }

        /* AUSTRALIA INVENTORY SEARCH V7 END */



        if ($apiAction === 'summary') {

            $sql =
                'SELECT COUNT(*) total FROM ' .
                qi($schema) . '.' . qi($folderTable) .
                ' WHERE ' . qi($folderOwner) . ' = ?';

            $stmt = mysqli_prepare($con, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                's',
                $principalId
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);

            $folderCount = (int)($row['total'] ?? 0);

            mysqli_stmt_close($stmt);


            $sql =
                'SELECT COUNT(*) total FROM ' .
                qi($schema) . '.' . qi($itemTable) .
                ' WHERE ' . qi($itemOwner) . ' = ?';

            $stmt = mysqli_prepare($con, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                's',
                $principalId
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);

            $itemCount = (int)($row['total'] ?? 0);

            mysqli_stmt_close($stmt);
            

            /* Grid INVENTORY TYPE TOTALS V1 */

            $typeTotals = [];

            if ($assetType) {

                $typeSql =
                    'SELECT ' .
                    qi($assetType) .
                    ' asset_type, COUNT(*) total FROM ' .
                    qi($schema) .
                    '.' .
                    qi($itemTable) .
                    ' WHERE ' .
                    qi($itemOwner) .
                    ' = ? GROUP BY ' .
                    qi($assetType);

                $typeStmt =
                    mysqli_prepare(
                        $con,
                        $typeSql
                    );

                if (!$typeStmt) {

                    throw new Exception(
                        'Could not prepare inventory type totals.'
                    );
                }

                mysqli_stmt_bind_param(
                    $typeStmt,
                    's',
                    $principalId
                );

                mysqli_stmt_execute(
                    $typeStmt
                );

                $typeResult =
                    mysqli_stmt_get_result(
                        $typeStmt
                    );

                while (
                    $typeRow =
                        mysqli_fetch_assoc(
                            $typeResult
                        )
                ) {

                    $rawType =
                        $typeRow['asset_type'];

                    if (
                        $rawType === null ||
                        $rawType === ''
                    ) {

                        $typeKey =
                            'unknown';
                    }
                    else {

                        $typeKey =
                            (string)
                            ((int)$rawType);
                    }

                    $typeTotals[$typeKey] =
                        (int)(
                            $typeRow['total'] ??
                            0
                        );
                }

                mysqli_stmt_close(
                    $typeStmt
                );

                ksort(
                    $typeTotals
                );

            }
            else {

                $typeTotals['unknown'] =
                    $itemCount;
            }
mysqli_close($con);


            invJson([
                'ok'      => true,
                'folders' => $folderCount,
                'items'   => $itemCount,
                'typeTotals' => $typeTotals
            ]);
        }


        /*
         * --------------------------------------------------------
         * FOLDERS
         * --------------------------------------------------------
         */

        if ($apiAction === 'folders') {

            $typeExpr =
                $folderType
                ? qi($folderType)
                : 'NULL';

            $sql =
                'SELECT ' .
                qi($folderId) .
                ' folder_id, ' .

                qi($folderParent) .
                ' parent_id, ' .

                qi($folderName) .
                ' folder_name, ' .

                $typeExpr .
                ' folder_type ' .

                'FROM ' .
                qi($schema) . '.' . qi($folderTable) .

                ' WHERE ' .
                qi($folderOwner) .
                ' = ? ' .

                'ORDER BY ' .
                qi($folderName);

            $stmt = mysqli_prepare($con, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                's',
                $principalId
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $folders = [];

            while ($row = mysqli_fetch_assoc($result)) {

                $folders[] = [
                    'id'     => (string)$row['folder_id'],
                    'parent' => (string)$row['parent_id'],
                    'name'   => (string)$row['folder_name'],
                    'type'   => $row['folder_type']
                ];
            }

            mysqli_stmt_close($stmt);
            
            /* AUSTRALIA FOLDER ITEM COUNTS V1 */

            $countSql =
                'SELECT ' .
                qi($itemParent) .
                ' parent_folder_id, COUNT(*) item_count FROM ' .
                qi($schema) .
                '.' .
                qi($itemTable) .
                ' WHERE ' .
                qi($itemOwner) .
                ' = ? GROUP BY ' .
                qi($itemParent);

            $countStmt =
                mysqli_prepare(
                    $con,
                    $countSql
                );

            if (!$countStmt) {
                throw new Exception(
                    'Could not prepare inventory folder item counts.'
                );
            }

            mysqli_stmt_bind_param(
                $countStmt,
                's',
                $principalId
            );

            mysqli_stmt_execute(
                $countStmt
            );

            $countResult =
                mysqli_stmt_get_result(
                    $countStmt
                );

            $itemCounts = [];

            while (
                $countRow =
                    mysqli_fetch_assoc(
                        $countResult
                    )
            ) {

                $countFolder =
                    strtolower(
                        (string)$countRow['parent_folder_id']
                    );

                $itemCounts[$countFolder] =
                    (int)(
                        $countRow['item_count'] ??
                        0
                    );
            }

            mysqli_stmt_close(
                $countStmt
            );

            foreach ($folders as &$folderRow) {

                $countKey =
                    strtolower(
                        (string)($folderRow['id'] ?? '')
                    );

                $folderRow['itemCount'] =
                    $itemCounts[$countKey] ??
                    0;
            }

            unset($folderRow);
mysqli_close($con);

            invJson([
                'ok'      => true,
                'folders' => $folders
            ]);
        }


        /*
         * Common item expressions
         */

        $descExpr =
            $itemDescription
            ? qi($itemDescription)
            : "''";

        $assetExpr =
            $assetId
            ? qi($assetId)
            : "''";

        $assetTypeExpr =
            $assetType
            ? qi($assetType)
            : 'NULL';

        $invTypeExpr =
            $inventoryType
            ? qi($inventoryType)
            : 'NULL';

        $creatorExpr =
            $creatorId
            ? qi($creatorId)
            : "''";

        $createdExpr =
            $creationDate
            ? qi($creationDate)
            : 'NULL';


        /*
         * --------------------------------------------------------
         * FOLDER ITEMS
         * --------------------------------------------------------
         */

        if ($apiAction === 'items') {

            $folder =
                trim(
                    (string)(
                        $_GET['folder'] ??
                        ''
                    )
                );

            if (
                !preg_match(
                    '/^[0-9a-fA-F-]{36}$/',
                    $folder
                )
            ) {

                mysqli_close($con);

                invJson(
                    [
                        'ok'    => false,
                        'error' => 'Invalid inventory folder.'
                    ],
                    400
                );
            }


            $sql =
                'SELECT ' .

                qi($itemId) .
                ' inventory_id, ' .

                qi($itemName) .
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
                ' creation_date, ' .

                qi($itemParent) .
                ' parent_folder_id ' .

                'FROM ' .
                qi($schema) . '.' . qi($itemTable) .

                ' WHERE ' .
                qi($itemOwner) .
                ' = ? ' .

                'AND ' .
                qi($itemParent) .
                ' = ? ' .

                'ORDER BY ' .
                qi($itemName) .

                ' LIMIT 2000';


            $stmt = mysqli_prepare($con, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                'ss',
                $principalId,
                $folder
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $items = [];

            while ($row = mysqli_fetch_assoc($result)) {

                $items[] = [
                    'id'            => (string)$row['inventory_id'],
                    'name'          => (string)$row['inventory_name'],
                    'description'   => (string)$row['inventory_description'],
                    'assetId'       => (string)$row['asset_id'],
                    'assetType'     => $row['asset_type'],
                    'inventoryType' => $row['inventory_type'],
                    'creatorId'     => (string)$row['creator_id'],
                    'creationDate'  => $row['creation_date'],
                    'folderId'      => (string)$row['parent_folder_id']
                ];
            }

            mysqli_stmt_close($stmt);
            mysqli_close($con);

            invJson([
                'ok'    => true,
                'items' => $items
            ]);
        }


        /*
         * --------------------------------------------------------
         * SEARCH
         * --------------------------------------------------------
         */

        if ($apiAction === 'search') {

            $query =
                trim(
                    (string)(
                        $_GET['q'] ??
                        ''
                    )
                );

            if (strlen($query) < 2) {

                mysqli_close($con);

                invJson([
                    'ok'    => true,
                    'items' => []
                ]);
            }


            if (strlen($query) > 100) {
                $query = substr($query, 0, 100);
            }


            $search =
                '%' .
                $query .
                '%';


            $sql =
                'SELECT ' .

                qi($itemId) .
                ' inventory_id, ' .

                qi($itemName) .
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
                ' creation_date, ' .

                qi($itemParent) .
                ' parent_folder_id ' .

                'FROM ' .
                qi($schema) . '.' . qi($itemTable) .

                ' WHERE ' .
                qi($itemOwner) .
                ' = ? ' .

                'AND ' .
                qi($itemName) .
                ' LIKE ? ' .

                'ORDER BY ' .
                qi($itemName) .

                ' LIMIT 300';


            $stmt = mysqli_prepare($con, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                'ss',
                $principalId,
                $search
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $items = [];

            while ($row = mysqli_fetch_assoc($result)) {

                $items[] = [
                    'id'            => (string)$row['inventory_id'],
                    'name'          => (string)$row['inventory_name'],
                    'description'   => (string)$row['inventory_description'],
                    'assetId'       => (string)$row['asset_id'],
                    'assetType'     => $row['asset_type'],
                    'inventoryType' => $row['inventory_type'],
                    'creatorId'     => (string)$row['creator_id'],
                    'creationDate'  => $row['creation_date'],
                    'folderId'      => (string)$row['parent_folder_id']
                ];
            }

            mysqli_stmt_close($stmt);
            mysqli_close($con);

            invJson([
                'ok'    => true,
                'items' => $items
            ]);
        }


        
        /* =====================================================
         * Grid - DUPLICATE FINDER API V1
         * READ ONLY
         * ===================================================== */

        if ($apiAction === 'duplicates') {

            $dupSql =
                'SELECT ' .
                qi($itemId) .
                ' inventory_id, ' .
                qi($itemName) .
                ' inventory_name, ' .
                $assetExpr .
                ' asset_id, ' .
                $assetTypeExpr .
                ' asset_type, ' .
                qi($itemParent) .
                ' parent_folder_id FROM ' .
                qi($schema) .
                '.' .
                qi($itemTable) .
                ' WHERE ' .
                qi($itemOwner) .
                ' = ? ORDER BY ' .
                qi($itemName);

            $dupStmt =
                mysqli_prepare(
                    $con,
                    $dupSql
                );

            if (!$dupStmt) {
                throw new Exception(
                    'Could not prepare duplicate inventory scan.'
                );
            }

            mysqli_stmt_bind_param(
                $dupStmt,
                's',
                $principalId
            );

            mysqli_stmt_execute(
                $dupStmt
            );

            $dupResult =
                mysqli_stmt_get_result(
                    $dupStmt
                );

            $dupItems = [];

            while (
                $dupRow =
                    mysqli_fetch_assoc(
                        $dupResult
                    )
            ) {

                $dupItems[] = [
                    'id' =>
                        (string)$dupRow['inventory_id'],

                    'name' =>
                        (string)$dupRow['inventory_name'],

                    'assetId' =>
                        (string)$dupRow['asset_id'],

                    'assetType' =>
                        $dupRow['asset_type'],

                    'folderId' =>
                        (string)$dupRow['parent_folder_id']
                ];
            }

            mysqli_stmt_close(
                $dupStmt
            );

            mysqli_close($con);

            invJson([
                'ok' => true,
                'items' => $dupItems
            ]);
        }
mysqli_close($con);

        invJson(
            [
                'ok'    => false,
                'error' => 'Unknown inventory request.'
            ],
            400
        );

    }
    catch (Throwable $e) {

        if (
            isset($con) &&
            $con instanceof mysqli
        ) {
            @mysqli_close($con);
        }

        invJson(
            [
                'ok'    => false,
                'error' => $e->getMessage()
            ],
            500
        );
    }
}


header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header('Pragma: no-cache');


/*
 * ============================================================
 * AUSTRALIA CONTROL CENTER
 * INVENTORY BROWSER API
 *
 * Backend only.
 * No legacy frontend is loaded from this file.
 * ============================================================
 */

if (function_exists('invJson')) {

    invJson(
        [
            'ok' =>
                false,

            'error' =>
                'Unknown inventory request.'
        ],
        400
    );
}

http_response_code(400);

header(
    'Content-Type: application/json; charset=utf-8'
);

echo json_encode(
    [
        'ok' =>
            false,

        'error' =>
            'Inventory API unavailable.'
    ]
);

exit;
