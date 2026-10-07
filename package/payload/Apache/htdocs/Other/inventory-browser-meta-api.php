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

        /* AUSTRALIA ACTIVE INVENTORY META V3 START */


        /*
         * =====================================================
         * CURRENT INVENTORY OWNER PROFILE
         * =====================================================
         */

        if ($apiAction === 'profile') {

            $profileSession =
                (
                    isset($session) &&
                    is_array($session)
                )
                    ? $session
                    : [];


            $avatarName = '';


            foreach (
                [
                    'avatarName',
                    'avatar',
                    'username',
                    'userName',
                    'name',
                    'user'
                ]
                as $key
            ) {

                if (
                    isset($profileSession[$key]) &&
                    trim((string)$profileSession[$key]) !== ''
                ) {

                    $avatarName =
                        trim(
                            (string)$profileSession[$key]
                        );

                    break;
                }
            }


            if ($avatarName === '') {

                $first =
                    trim(
                        (string)(
                            $profileSession['firstName']
                            ??
                            $profileSession['firstname']
                            ??
                            $profileSession['FirstName']
                            ??
                            ''
                        )
                    );


                $last =
                    trim(
                        (string)(
                            $profileSession['lastName']
                            ??
                            $profileSession['lastname']
                            ??
                            $profileSession['LastName']
                            ??
                            ''
                        )
                    );


                $avatarName =
                    trim(
                        $first . ' ' . $last
                    );
            }


            $userLevel = null;


            foreach (
                [
                    'level',
                    'userLevel',
                    'UserLevel',
                    'accountLevel',
                    'AccountLevel'
                ]
                as $levelKey
            ) {

                if (
                    isset($profileSession[$levelKey]) &&
                    is_numeric($profileSession[$levelKey])
                ) {

                    $userLevel =
                        (int)$profileSession[$levelKey];

                    break;
                }
            }


            mysqli_close($con);


            invJson([
                'ok'          => true,
                'avatar'      => $avatarName,
                'principalId' => (string)$principalId,
                'level'       => $userLevel,
                'role'        => 'ADMIN'
            ]);
        }


        /*
         * =====================================================
         * ONE INVENTORY ITEM - FULL METADATA
         * =====================================================
         */

        if ($apiAction === 'itemmeta') {

            $requestedItem =
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
                    $requestedItem
                )
            ) {

                mysqli_close($con);


                invJson([
                    'ok'    => false,
                    'error' => 'Invalid inventory item UUID.'
                ]);
            }


            /*
             * OPTIONAL COLUMNS
             */

            $cName =
                pickColumn(
                    $ic,
                    [
                        'inventoryName',
                        'name'
                    ],
                    false
                );


            $cDescription =
                pickColumn(
                    $ic,
                    [
                        'inventoryDescription',
                        'description'
                    ],
                    false
                );


            $cAssetId =
                pickColumn(
                    $ic,
                    [
                        'assetID',
                        'assetId'
                    ],
                    false
                );


            $cAssetType =
                pickColumn(
                    $ic,
                    [
                        'assetType'
                    ],
                    false
                );


            $cInvType =
                pickColumn(
                    $ic,
                    [
                        'invType',
                        'inventoryType'
                    ],
                    false
                );


            $cCreator =
                pickColumn(
                    $ic,
                    [
                        'creatorID',
                        'creatorId'
                    ],
                    false
                );


            $cCreationDate =
                pickColumn(
                    $ic,
                    [
                        'creationDate',
                        'CreationDate'
                    ],
                    false
                );


            $cFolder =
                pickColumn(
                    $ic,
                    [
                        'parentFolderID',
                        'folderID'
                    ],
                    false
                );


            $cBase =
                pickColumn(
                    $ic,
                    [
                        'inventoryBasePermissions',
                        'BasePermissions',
                        'basePermissions'
                    ],
                    false
                );


            $cCurrent =
                pickColumn(
                    $ic,
                    [
                        'inventoryCurrentPermissions',
                        'CurrentPermissions',
                        'currentPermissions'
                    ],
                    false
                );


            $cNext =
                pickColumn(
                    $ic,
                    [
                        'inventoryNextPermissions',
                        'NextPermissions',
                        'nextPermissions'
                    ],
                    false
                );


            $cEveryone =
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


            $cGroup =
                pickColumn(
                    $ic,
                    [
                        'inventoryGroupPermissions',
                        'GroupPermissions',
                        'groupPermissions'
                    ],
                    false
                );


            $cGroupId =
                pickColumn(
                    $ic,
                    [
                        'groupID',
                        'groupId'
                    ],
                    false
                );


            $cGroupOwned =
                pickColumn(
                    $ic,
                    [
                        'groupOwned'
                    ],
                    false
                );


            $cFlags =
                pickColumn(
                    $ic,
                    [
                        'flags',
                        'Flags'
                    ],
                    false
                );


            $cSalePrice =
                pickColumn(
                    $ic,
                    [
                        'salePrice',
                        'SalePrice'
                    ],
                    false
                );


            $cSaleType =
                pickColumn(
                    $ic,
                    [
                        'saleType',
                        'SaleType'
                    ],
                    false
                );


            /*
             * SAFE SELECT LIST
             */

            $fields = [];


            $fields[] =
                qi($itemId) .
                ' item_id';


            $fields[] =
                $cName
                    ? qi($cName) . ' item_name'
                    : "'' item_name";


            $fields[] =
                $cDescription
                    ? qi($cDescription) . ' item_description'
                    : "'' item_description";


            $fields[] =
                $cAssetId
                    ? qi($cAssetId) . ' asset_id'
                    : "'' asset_id";


            $fields[] =
                $cAssetType
                    ? qi($cAssetType) . ' asset_type'
                    : 'NULL asset_type';


            $fields[] =
                $cInvType
                    ? qi($cInvType) . ' inventory_type'
                    : 'NULL inventory_type';


            $fields[] =
                $cCreator
                    ? qi($cCreator) . ' creator_id'
                    : "'' creator_id";


            $fields[] =
                $cCreationDate
                    ? qi($cCreationDate) . ' creation_date'
                    : 'NULL creation_date';


            $fields[] =
                $cFolder
                    ? qi($cFolder) . ' folder_id'
                    : "'' folder_id";


            $fields[] =
                $cBase
                    ? qi($cBase) . ' base_permissions'
                    : 'NULL base_permissions';


            $fields[] =
                $cCurrent
                    ? qi($cCurrent) . ' current_permissions'
                    : 'NULL current_permissions';


            $fields[] =
                $cNext
                    ? qi($cNext) . ' next_permissions'
                    : 'NULL next_permissions';


            $fields[] =
                $cEveryone
                    ? qi($cEveryone) . ' everyone_permissions'
                    : 'NULL everyone_permissions';


            $fields[] =
                $cGroup
                    ? qi($cGroup) . ' group_permissions'
                    : 'NULL group_permissions';


            $fields[] =
                $cGroupId
                    ? qi($cGroupId) . ' group_id'
                    : "'' group_id";


            $fields[] =
                $cGroupOwned
                    ? qi($cGroupOwned) . ' group_owned'
                    : 'NULL group_owned';


            $fields[] =
                $cFlags
                    ? qi($cFlags) . ' item_flags'
                    : 'NULL item_flags';


            $fields[] =
                $cSalePrice
                    ? qi($cSalePrice) . ' sale_price'
                    : 'NULL sale_price';


            $fields[] =
                $cSaleType
                    ? qi($cSaleType) . ' sale_type'
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


                invJson([
                    'ok'    => false,
                    'error' => 'Metadata query could not be prepared.'
                ]);
            }


            mysqli_stmt_bind_param(
                $stmt,
                'ss',
                $principalId,
                $requestedItem
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

                invJson([
                    'ok'    => false,
                    'error' => 'Inventory item not found.'
                ]);
            }


            invJson([
                'ok' => true,

                'item' => [

                    'id' =>
                        (string)$row['item_id'],

                    'name' =>
                        (string)($row['item_name'] ?? ''),

                    'description' =>
                        (string)($row['item_description'] ?? ''),

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
                        (string)($row['folder_id'] ?? ''),

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


        /* AUSTRALIA ACTIVE INVENTORY META V3 END */


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
