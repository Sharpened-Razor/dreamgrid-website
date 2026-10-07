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

        require __DIR__ . '/../../MetroMap/includes/config.php';

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

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Grid - Inventory Browser</title>

<link
    rel="stylesheet"
    href="/Other/australia-3d-theme.css?v=31">


<style>

*{
    box-sizing:border-box;
}

html,
body{
    min-height:100%;
}

body{

    margin:0;

    background:
        linear-gradient(
            rgba(0,0,0,.48),
            rgba(0,0,0,.70)
        ),
        url(
            "/Other/custom/Branding/AUSTRALIA-BACKGROUND.png"
        )
        center center /
        cover fixed no-repeat;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    color:#edf2f4;
}


.browser-shell{

    width:min(
        1580px,
        calc(100% - 32px)
    );

    margin:26px auto 60px;

    padding:24px;
}


.browser-kicker{

    color:#f0b83d;

    font-size:10px;

    font-weight:900;

    letter-spacing:.20em;
}


.member-line{

    margin-top:7px;

    color:#9ba8ae;

    font-size:11px;
}


.member-line strong{
    color:#fff;
}


.read-only{

    display:inline-block;

    margin-left:8px;

    padding:4px 8px;

    border:
        1px solid
        rgba(70,190,107,.40);

    border-radius:999px;

    background:
        rgba(70,190,107,.10);

    color:#8de3a8;

    font-size:8px;

    font-weight:900;
}


.header-actions{

    display:flex;

    gap:9px;
}


.header-actions a,
.header-actions button{

    min-width:110px;

    min-height:44px;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    padding:9px 14px;

    text-decoration:none;

    cursor:pointer;
}


.summary-grid{

    display:grid;

    grid-template-columns:
        repeat(3,minmax(0,1fr));

    gap:12px;

    margin-bottom:16px;
}


.summary-card{

    padding:15px 18px;
}


.summary-label{

    color:#eeb23b;

    font-size:9px;

    font-weight:900;

    letter-spacing:.08em;
}


.summary-number{

    margin-top:4px;

    color:#fff;

    font-size:26px;

    font-weight:900;
}


.search-bar{

    display:flex;

    gap:9px;

    padding:14px;

    margin-bottom:16px;
}


.search-bar input{

    flex:1;

    min-width:0;

    height:45px;

    padding:0 14px;
}


.search-bar button{

    min-width:110px;

    cursor:pointer;
}


.workspace{

    display:grid;

    grid-template-columns:
        330px
        minmax(400px,1fr)
        360px;

    gap:15px;
}


.workspace-card{

    min-height:650px;

    padding:18px;

    display:flex;

    flex-direction:column;
}


.workspace-title{

    display:flex;

    justify-content:
        space-between;

    align-items:center;

    gap:10px;

    margin-bottom:13px;

    padding-bottom:12px;

    border-bottom:
        1px solid
        rgba(255,255,255,.09);
}


.workspace-title h2{

    margin:0;

    font-size:17px;
}


.workspace-hint{

    color:#85939a;

    font-size:9px;
}


.folder-tree,
.item-list{

    flex:1;

    overflow:auto;
}


.folder-node{
    margin:2px 0;
}


.folder-button{

    width:100%;

    min-height:36px;

    display:flex;

    align-items:center;

    gap:8px;

    padding:7px 9px;

    border:
        1px solid
        transparent !important;

    background:
        transparent !important;

    box-shadow:
        none !important;

    color:#cbd4d8 !important;

    text-align:left;

    cursor:pointer;
}


.folder-button:hover,
.folder-button.active{

    border-color:
        rgba(231,173,50,.35) !important;

    background:
        rgba(225,166,41,.08) !important;
}


.folder-button.active{

    color:#ffc64a !important;
}


.folder-icon{

    width:27px;

    height:24px;

    flex:0 0 27px;

    display:flex;

    align-items:center;

    justify-content:center;

    border:
        1px solid
        rgba(232,175,54,.40);

    border-radius:5px;

    color:#f2b83e;

    font-size:8px;

    font-weight:900;
}


.folder-name{

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    font-size:11px;

    font-weight:700;
}


.folder-children{

    margin-left:16px;

    padding-left:6px;

    border-left:
        1px solid
        rgba(255,255,255,.07);
}


.item-header{

    display:flex;

    justify-content:
        space-between;

    gap:10px;

    margin-bottom:11px;
}


.current-folder{

    color:#fff;

    font-size:12px;

    font-weight:900;
}


.item-count{

    color:#89979e;

    font-size:10px;
}


.item-row{

    width:100%;

    display:grid;

    grid-template-columns:
        44px minmax(0,1fr);

    gap:11px;

    align-items:center;

    margin-bottom:7px;

    padding:10px;

    border:
        1px solid
        rgba(255,255,255,.09) !important;

    border-radius:8px;

    background:
        linear-gradient(
            145deg,
            rgba(41,49,53,.82),
            rgba(6,10,12,.92)
        ) !important;

    color:#dce3e6 !important;

    text-align:left;

    cursor:pointer;

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.08),
        0 4px 9px
        rgba(0,0,0,.29) !important;
}


.item-row:hover,
.item-row.active{

    border-color:
        rgba(239,181,56,.62) !important;
}


.item-row.active{

    background:
        linear-gradient(
            145deg,
            rgba(73,59,24,.58),
            rgba(7,11,13,.94)
        ) !important;
}


.item-icon{
    width:48px !important;
    height:48px !important;
    min-width:48px !important;
    flex:0 0 48px !important;

    display:flex !important;
    align-items:center !important;
    justify-content:center !important;

    border:1px solid rgba(255,193,58,.85) !important;
    border-radius:11px !important;

    background:
        linear-gradient(
            180deg,
            #0b1116 0%,
            #04080c 100%
        ) !important;

    font-size:22px !important;
    line-height:1 !important;
    color:#ffc94a !important;
    text-shadow:0 2px 8px rgba(0,0,0,.55) !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.06),
        0 4px 12px rgba(0,0,0,.38) !important;
}


.item-name{

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    font-size:12px;

    font-weight:900;
}


.item-desc{

    margin-top:3px;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    color:#87959b;

    font-size:10px;
}


.empty-state{

    flex:1;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:20px;

    color:#839097;

    font-size:12px;

    line-height:1.6;

    text-align:center;
}


.details{
    display:none;
}


.detail-name{

    margin:
        3px 0 16px;

    color:#fff;

    font-size:18px;

    font-weight:900;

    overflow-wrap:anywhere;
}


.detail-row{

    padding:11px 0;

    border-bottom:
        1px solid
        rgba(255,255,255,.075);
}


.detail-label{

    margin-bottom:4px;

    color:#edb13b;

    font-size:8px;

    font-weight:900;

    letter-spacing:.08em;
}


.detail-value{

    color:#cbd4d8;

    font-size:11px;

    line-height:1.5;

    overflow-wrap:anywhere;
}


.status-bar{

    margin-top:15px;

    padding:13px 15px;

    border-left:
        4px solid
        #e6aa31;

    color:#aeb9be;

    font-size:11px;

    line-height:1.55;
}


.status-bar.error{

    border-left-color:#d95059;

    color:#ffc8cb;
}


@media(max-width:1200px){

    .workspace{

        grid-template-columns:
            300px 1fr;
    }

    .details-card{

        grid-column:
            1 / -1;

        min-height:320px;
    }
}


@media(max-width:780px){

    .browser-shell{

        width:
            calc(100% - 18px);

        margin:
            10px auto 40px;

        padding:12px;
    }

    .header-actions{
        margin-top:15px;
    }

    .summary-grid,
    .workspace{

        grid-template-columns:
            1fr;
    }

    .search-bar{
        flex-direction:column;
    }

    .workspace-card{
        min-height:420px;
    }
}

</style>


<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">

<style id="ag-inventory-symbols-v2">

/* Grid - INVENTORY BROWSER SYMBOLS V2 */

.ag-inv-symbol{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    margin-right:7px;
    vertical-align:middle;
    line-height:1;
    font-family:"Segoe UI Emoji","Segoe UI Symbol",Arial,sans-serif;
    font-weight:900;
    text-shadow:
        0 1px 2px rgba(0,0,0,.95),
        0 0 7px rgba(255,190,40,.42);
}

h1 .ag-inv-symbol{
    font-size:.78em;
    margin-right:12px;
    transform:translateY(-1px);
}

button .ag-inv-symbol,
a .ag-inv-symbol{
    margin-right:6px;
}

.summary-label .ag-inv-symbol,
.panel-title .ag-inv-symbol{
    margin-right:6px;
}

</style>

<style id="australia-inventory-folder-icon-v1">

/* Grid - INVENTORY FOLDER ICON */

.folder-icon{
    font-family:
        "Segoe UI Emoji",
        "Segoe UI Symbol",
        sans-serif !important;

    font-size:17px !important;

    line-height:1 !important;

    color:#ffc64a !important;

    text-shadow:
        0 1px 2px rgba(0,0,0,.95),
        0 0 6px rgba(255,198,74,.30) !important;
}

</style>

<style id="ag-firestorm-inventory-tree-v1">

/* ========================================================
   Grid - FIRESTORM STYLE INVENTORY TREE
   ======================================================== */

html{
    min-height:100% !important;
    background:#03070a !important;
}

body{
    min-height:100vh !important;
    background-color:#03070a !important;
    background-image:
        linear-gradient(
            rgba(2,6,9,.92),
            rgba(2,6,9,.96)
        ),
        url(
            "/Other/custom/Branding/AUSTRALIA-BACKGROUND.png"
        ) !important;
    background-position:center top !important;
    background-size:cover !important;
    background-repeat:no-repeat !important;
    background-attachment:fixed !important;
}

/* Keep desktop Inventory panels at a controlled height. */

@media(min-width:781px){

    .workspace > .workspace-card{
        height:650px !important;
        min-height:650px !important;
        max-height:650px !important;
        overflow:hidden !important;
    }

}

/* Folder and item lists scroll inside their own panels. */

.folder-tree,
.item-list{
    flex:1 1 auto !important;
    min-height:0 !important;
    max-height:100% !important;
    overflow-y:auto !important;
    overflow-x:hidden !important;
    overscroll-behavior:contain !important;
    scrollbar-width:thin;
    scrollbar-color:#b78325 #071015;
    padding-right:5px;
}

.folder-tree::-webkit-scrollbar,
.item-list::-webkit-scrollbar{
    width:10px;
}

.folder-tree::-webkit-scrollbar-track,
.item-list::-webkit-scrollbar-track{
    background:#071015;
    border-radius:8px;
}

.folder-tree::-webkit-scrollbar-thumb,
.item-list::-webkit-scrollbar-thumb{
    background:linear-gradient(
        180deg,
        #d6a43b,
        #805b18
    );
    border:2px solid #071015;
    border-radius:8px;
}

.folder-tree::-webkit-scrollbar-thumb:hover,
.item-list::-webkit-scrollbar-thumb:hover{
    background:#e7b84d;
}

/* Firestorm-style collapsed tree. */

.folder-node.ag-collapsed > .folder-children{
    display:none !important;
}

.folder-node.ag-expanded > .folder-children{
    display:block !important;
}

.folder-button{
    position:relative;
}

.folder-button.ag-has-children::after{
    content:"\25B8";
    margin-left:auto;
    padding-left:6px;
    color:#8d9aa0;
    font-size:11px;
    line-height:1;
}

.folder-node.ag-expanded > .folder-button.ag-has-children::after{
    content:"\25BE";
    color:#e9b844;
}

.folder-node.ag-expanded > .folder-button{
    color:#e4c06a !important;
}

.folder-icon{
    font-size:17px !important;
    line-height:1 !important;
}

/* Mobile keeps natural card height. */

@media(max-width:780px){

    .workspace > .workspace-card{
        height:auto !important;
        max-height:none !important;
        min-height:420px !important;
    }

    .folder-tree,
    .item-list{
        max-height:520px !important;
    }

}

</style>

<style id="ag-inventory-browser-large-symbols-v1">

/* Grid - INVENTORY BROWSER LARGER SYMBOLS */

.folder-icon{
    width:31px !important;
    height:29px !important;
    flex:0 0 31px !important;
    font-size:22px !important;
    line-height:1 !important;
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
    font-family:
        "Segoe UI Emoji",
        "Segoe UI Symbol",
        Arial,
        sans-serif !important;
}

.item-icon{
    width:48px !important;
    height:48px !important;
    min-width:48px !important;
    flex:0 0 48px !important;
    font-size:24px !important;
    line-height:1 !important;
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
    font-family:
        "Segoe UI Emoji",
        "Segoe UI Symbol",
        Arial,
        sans-serif !important;
}

.item-row{
    grid-template-columns:50px minmax(0,1fr) !important;
}

</style>

<style id="ag-inventory-browser-uuid-breadcrumb-v1">

/* Grid - INVENTORY BROWSER BREADCRUMB */

.ag-browser-breadcrumb{
    margin:0 0 11px 0;
    padding:8px 11px;
    min-height:32px;
    display:flex;
    align-items:center;
    border:1px solid rgba(230,174,50,.18);
    border-radius:7px;
    background:rgba(4,8,10,.52);
    color:#9eabb0;
    font-size:9px;
    font-weight:700;
    line-height:1.45;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.ag-browser-breadcrumb .ag-breadcrumb-root{
    color:#eab13b;
    font-weight:900;
}

</style>


<style id="ag-inventory-folder-counts-v2">

/* Grid - INVENTORY BROWSER FOLDER COUNTS V2 */

.folder-name{
    min-width:0 !important;
    flex:1 1 auto !important;
}

.folder-count{
    flex:0 0 auto !important;
    margin-left:auto !important;
    padding-left:8px !important;
    color:#ffc64a !important;
    font-family:Arial,Helvetica,sans-serif !important;
    font-size:11px !important;
    font-weight:900 !important;
    line-height:1 !important;
    white-space:nowrap !important;
    text-shadow:
        0 1px 2px rgba(0,0,0,.95),
        0 0 7px rgba(255,198,74,.32) !important;
}

.folder-count.ag-folder-empty-count{
    color:#ff4545 !important;
    text-shadow:
        0 1px 2px rgba(0,0,0,.95),
        0 0 8px rgba(255,50,50,.36) !important;
}

.folder-button.ag-empty-folder > .folder-name{
    color:#a8b0b4 !important;
}

.folder-button.ag-empty-folder > .folder-icon{
    opacity:.72 !important;
}

.folder-button.ag-has-children::after{
    margin-left:7px !important;
}

</style>

<style id="ag-inventory-type-totals-v1">

/* Grid - INVENTORY TYPE TOTALS V1 */

.ag-type-panel{
    padding:15px 18px;
    margin-bottom:16px;
}

.ag-type-heading{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:11px;
}

.ag-type-title{
    color:#eeb23b;
    font-size:10px;
    font-weight:900;
    letter-spacing:.08em;
}

.ag-type-note{
    color:#758288;
    font-size:8px;
    font-weight:800;
    letter-spacing:.05em;
}

.ag-type-list{
    display:grid;
    grid-template-columns:
        repeat(auto-fit,minmax(135px,1fr));
    gap:7px;
}

.ag-type-chip{
    display:grid;
    grid-template-columns:25px minmax(0,1fr) auto;
    align-items:center;
    gap:7px;
    min-height:36px;
    padding:6px 9px;
    border:1px solid rgba(225,176,62,.16);
    border-radius:8px;
    background:rgba(3,8,10,.58);
}

.ag-type-icon{
    font-family:
        "Segoe UI Emoji",
        "Segoe UI Symbol",
        sans-serif;
    font-size:17px;
    line-height:1;
    text-align:center;
}

.ag-type-label{
    min-width:0;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    color:#cbd3d6;
    font-size:9px;
    font-weight:800;
}

.ag-type-number{
    color:#ffc64a;
    font-size:11px;
    font-weight:900;
    white-space:nowrap;
    text-shadow:
        0 1px 2px rgba(0,0,0,.95),
        0 0 6px rgba(255,198,74,.25);
}

.ag-type-empty{
    grid-column:1/-1;
    padding:8px;
    color:#7d898e;
    font-size:9px;
}

@media(max-width:800px){

    .ag-type-heading{
        align-items:flex-start;
        flex-direction:column;
    }

}

</style>

<style id="ag-inventory-duplicate-v1">

/* Grid - DUPLICATE FINDER V1 */

.ag-duplicate-panel{
    padding:16px 18px;
    margin-bottom:16px;
}

.ag-dup-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    margin-bottom:13px;
}

.ag-dup-main-title{
    color:#eeb23b;
    font-size:11px;
    font-weight:900;
    letter-spacing:.08em;
}

.ag-dup-note{
    margin-top:3px;
    color:#7f8b90;
    font-size:8px;
    font-weight:800;
    letter-spacing:.05em;
}

.ag-dup-scan{
    min-width:145px;
    min-height:38px;
    padding:7px 13px;
    cursor:pointer;
    font-weight:900;
}

.ag-dup-scan:disabled{
    opacity:.55;
    cursor:wait;
}

.ag-dup-split{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:12px;
}

.ag-dup-side{
    min-width:0;
    padding:11px;
    border:1px solid rgba(230,175,55,.13);
    border-radius:9px;
    background:rgba(2,6,8,.38);
}

.ag-dup-side-title,
.ag-dup-section-title{
    margin-bottom:8px;
    color:#d8a93f;
    font-size:9px;
    font-weight:900;
    letter-spacing:.06em;
}

.ag-dup-results{
    max-height:720px;
    overflow:auto;
}

.ag-dup-placeholder,
.ag-dup-none{
    padding:10px;
    color:#77858a;
    font-size:9px;
}

.ag-dup-error{
    padding:10px;
    color:#ff5a5a;
    font-size:10px;
    font-weight:900;
}

.ag-dup-metrics{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:7px;
    margin-bottom:12px;
}

.ag-dup-metric{
    padding:8px;
    text-align:center;
    border:1px solid rgba(228,176,58,.13);
    border-radius:7px;
    background:rgba(3,8,10,.56);
}

.ag-dup-metric strong{
    display:block;
    color:#ffc64a;
    font-size:16px;
    font-weight:900;
}

.ag-dup-metric span{
    display:block;
    margin-top:2px;
    color:#77858a;
    font-size:7px;
    font-weight:900;
}

.ag-dup-section{
    margin-top:12px;
}

.ag-dup-group{
    margin-bottom:6px;
    border:1px solid rgba(220,226,230,.09);
    border-radius:7px;
    background:rgba(2,6,8,.48);
    overflow:hidden;
}

.ag-dup-group > summary{
    display:grid;
    grid-template-columns:27px minmax(0,1fr) auto auto;
    align-items:center;
    gap:7px;
    padding:8px 9px;
    cursor:pointer;
    list-style:none;
}

.ag-dup-group > summary::-webkit-details-marker{
    display:none;
}

.ag-dup-icon{
    font-size:17px;
    text-align:center;
}

.ag-dup-title{
    min-width:0;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    color:#d8e0e3;
    font-size:9px;
    font-weight:800;
}

.ag-dup-tag{
    padding:2px 5px;
    border-radius:999px;
    font-size:7px;
    font-weight:900;
    white-space:nowrap;
}

.ag-dup-tag.exact{
    color:#ff6262;
    border:1px solid rgba(255,75,75,.30);
    background:rgba(130,20,20,.18);
}

.ag-dup-tag.review{
    color:#ffc64a;
    border:1px solid rgba(255,198,74,.26);
    background:rgba(115,80,10,.18);
}

.ag-dup-copies{
    color:#8e9a9f;
    font-size:8px;
    font-weight:800;
    white-space:nowrap;
}

.ag-dup-body{
    border-top:1px solid rgba(220,226,230,.07);
}

.ag-dup-item{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    gap:10px;
    align-items:center;
    padding:7px 9px;
    border-bottom:1px solid rgba(220,226,230,.05);
}

.ag-dup-item strong{
    display:block;
    color:#cbd4d7;
    font-size:9px;
}

.ag-dup-item span{
    display:block;
    margin-top:2px;
    color:#718087;
    font-size:8px;
}

.ag-dup-item code{
    color:#d7aa43;
    font-size:8px;
    white-space:nowrap;
}

.ag-dup-more{
    padding:8px;
    color:#7c898e;
    font-size:8px;
    text-align:center;
}

@media(max-width:1050px){
    .ag-dup-split{
        grid-template-columns:1fr;
    }
}

@media(max-width:700px){
    .ag-dup-head{
        align-items:flex-start;
        flex-direction:column;
    }

    .ag-dup-metrics{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .ag-dup-group > summary{
        grid-template-columns:25px minmax(0,1fr);
    }

    .ag-dup-tag,
    .ag-dup-copies{
        grid-column:2;
    }
}

</style>
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">
<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=20260830-phase3c">

<style id="ag-inventory-native-back-approved-v1">

/*
 * Inventory Browser native BACK button.
 *
 * Navigation remains native and is excluded from the global
 * return-button JavaScript.
 *
 * Visual appearance copied from the approved MASTER RETURN
 * button styling.
 */

/* ============================================================
   APPROVED BACK BUTTON
   234.9px x 53.46px
   ============================================================ */

html body #agInventoryNativeBack{

    appearance:none !important;

    box-sizing:border-box !important;

    position:absolute !important;

    top:50% !important;
    right:32px !important;

    transform:
        translateY(-50%)
        !important;

    z-index:500 !important;

    display:inline-flex !important;

    align-items:center !important;
    justify-content:center !important;

    width:234.9px !important;
    min-width:234.9px !important;
    max-width:234.9px !important;

    height:53.46px !important;
    min-height:53.46px !important;
    max-height:53.46px !important;

    margin:0 !important;

    padding:
        0 18px
        !important;

    border:
        1px solid #ffeba5
        !important;

    border-radius:
        10px
        !important;

    background:
        linear-gradient(
            180deg,
            #fff0a8 0%,
            #f4c94c 18%,
            #dda018 62%,
            #a96705 100%
        )
        !important;

    color:#090909 !important;

    text-shadow:none !important;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.82),
        inset 0 -1px 0 rgba(75,35,0,.42),
        0 0 0 1px rgba(117,66,0,.78),
        0 5px 12px rgba(0,0,0,.38)
        !important;

    font-family:
        Arial,
        Helvetica,
        sans-serif
        !important;

    font-size:12.6px !important;

    font-weight:900 !important;

    line-height:1 !important;

    letter-spacing:.045em !important;

    text-align:center !important;

    text-decoration:none !important;

    text-transform:uppercase !important;

    white-space:nowrap !important;

    overflow:hidden !important;

    cursor:pointer !important;
}


html body #agInventoryNativeBack:hover{

    background:
        linear-gradient(
            180deg,
            #fff7cb 0%,
            #f9d965 20%,
            #e7ae28 63%,
            #bd7908 100%
        )
        !important;

    color:#000000 !important;
}


/* No arrow / generated icon */

html body #agInventoryNativeBack::before,
html body #agInventoryNativeBack::after{

    content:none !important;
    display:none !important;
}

</style>
<link rel="stylesheet" href="/Other/assets/css/ag-sentinel-icons-v1.css?v=20260902-sentinel-v1">

<style id="australia-sentinel-sitewide-v3">


/* ============================================================
   AUSTRALIA SENTINEL 3D CARD SYSTEM V3
   SITE WIDE
   ============================================================ */


.dashboard-card,
.control-hub-card,
.stat-card,
.summary-card,
.inventory-card,
.profile-card,
.region-panel,
.section-card,
.manage-card{


position:relative !important;

overflow:hidden !important;


border-radius:15px !important;


border:

2px solid
rgba(210,218,220,.55) !important;


background:

linear-gradient(
145deg,
#4d5559,
#090b0c
) !important;


box-shadow:

inset 0 2px 0
rgba(255,255,255,.25),

inset 0 -22px 35px
rgba(0,0,0,.8),

0 18px 40px
rgba(0,0,0,.75) !important;


}



.dashboard-card:after,
.control-hub-card:after,
.stat-card:after,
.summary-card:after,
.inventory-card:after,
.profile-card:after,
.region-panel:after,
.section-card:after,
.manage-card:after{


content:"";

position:absolute;

inset:7px;

border-radius:10px;

pointer-events:none;


border:

1px solid
rgba(255,193,58,.45);

}



.card-title,
.card-heading,
.panel-title,
.summary-title{


color:#ffd167 !important;

font-weight:900 !important;

text-shadow:

0 2px 5px
rgba(0,0,0,.8);

}



.card-icon,
.card-icon.svg-badge{


background:

linear-gradient(
145deg,
#70777a,
#111415
) !important;


border:

1px solid
rgba(255,255,255,.3) !important;


box-shadow:

inset 0 2px 4px
rgba(255,255,255,.25),

0 8px 18px
rgba(0,0,0,.6);


}



.dashboard-card:hover,
.control-hub-card:hover,
.stat-card:hover,
.summary-card:hover{


transform:
translateY(-3px);


}


</style>


<style id="ag-role-display-standard-v4">

/* ============================================================
   STANDARD ROLE DISPLAY V4

   250+        GRID OWNER
   other admin ADMIN
   normal user USER
   ============================================================ */

.php-level,
.ag-account-role-box{
    display:inline-flex !important;
    flex-direction:column !important;
    align-items:center !important;
    justify-content:center !important;
    min-width:145px !important;
    min-height:54px !important;
    padding:7px 14px !important;
    box-sizing:border-box !important;
    border:1px solid rgba(55,150,215,.70) !important;
    border-radius:8px !important;
    background:rgba(5,28,43,.88) !important;
    box-shadow:none !important;
    text-align:center !important;
    line-height:1.15 !important;
    color:#ffffff !important;
}

.php-level strong,
.ag-account-role-box strong{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    width:auto !important;
    height:auto !important;
    margin:0 0 4px 0 !important;
    padding:0 !important;
    color:#75c9ff !important;
    background:none !important;
    font-size:12px !important;
    font-weight:900 !important;
    line-height:1.1 !important;
    letter-spacing:.03em !important;
    text-indent:0 !important;
    clip:auto !important;
    overflow:visible !important;
}

.php-level span,
.ag-account-role-box span{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    margin:0 !important;
    padding:0 !important;
    color:#ffffff !important;
    background:none !important;
    font-size:11px !important;
    font-weight:800 !important;
    line-height:1.1 !important;
    white-space:nowrap !important;
}

</style>

<!-- AUSTRALIA INVENTORY REAL DARK V2 START -->

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-panel-v1.css?v=8">

<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-regions-v5.css?v=6">


<style id="australia-inventory-real-dark-v2">

/*
 * ============================================================
 * INVENTORY BROWSER
 * CONTROL CENTER DARK ADAPTER
 *
 * The colours/surfaces come from the real Control Center
 * stylesheets above.
 *
 * This block deals primarily with layout and kills the old
 * metallic/steel presentation.
 * ============================================================
 */


/* ------------------------------------------------------------
   CHILD PAGE
   Admin Home owns the outer shell and background.
   ------------------------------------------------------------ */

html,
body {

    margin:0 !important;

    background:
        transparent
        !important;

    background-image:
        none
        !important;
}


.browser-shell {

    width:100% !important;
    max-width:none !important;

    margin:0 !important;

    padding:
        12px
        !important;

    background:
        transparent
        !important;

    background-image:
        none
        !important;

    border:
        0
        !important;

    box-shadow:
        none
        !important;
}


/* ------------------------------------------------------------
   KILL ALL LEGACY SILVER / STEEL OVERLAYS
   ------------------------------------------------------------ */

.browser-shell::before,
.browser-shell::after,

.summary-grid::before,
.summary-grid::after,

.summary-card::before,
.summary-card::after,

.search-bar::before,
.search-bar::after,

.workspace::before,
.workspace::after,

.workspace-card::before,
.workspace-card::after,

.folder-tree::before,
.folder-tree::after,

.item-list::before,
.item-list::after,

.item-header::before,
.item-header::after,

.details-card::before,
.details-card::after,

.ag-duplicate-panel::before,
.ag-duplicate-panel::after {

    content:
        none
        !important;

    display:
        none
        !important;

    background-image:
        none
        !important;
}


/* ------------------------------------------------------------
   SUMMARY
   Now using rp-summary + rp-summary-item.
   ------------------------------------------------------------ */

.summary-grid {

    grid-template-columns:
        repeat(
            3,
            minmax(0,1fr)
        )
        !important;

    gap:
        12px
        !important;

    margin:
        0
        0
        12px
        0
        !important;
}


.summary-card {

    background-image:
        none
        !important;

    min-width:
        0
        !important;
}


.summary-number {

    font-size:
        22px
        !important;

    line-height:
        1.05
        !important;
}


#currentView {

    font-size:
        16px
        !important;
}


/* ------------------------------------------------------------
   DUPLICATE FINDER
   Uses the real rp-overview surface.
   ------------------------------------------------------------ */

.ag-duplicate-panel {

    margin:
        0
        0
        12px
        0
        !important;

    padding:
        14px
        !important;

    background-image:
        none
        !important;
}


.ag-dup-head {

    margin:
        0
        !important;
}


.ag-dup-results {

    margin-top:
        10px
        !important;
}


/* ------------------------------------------------------------
   SEARCH PANEL
   Pure dark surface - NO silver gradient.
   ------------------------------------------------------------ */

.search-bar {

    display:grid !important;

    grid-template-columns:
        minmax(0,1fr)
        auto
        auto
        !important;

    align-items:
        center
        !important;

    gap:
        8px
        !important;

    margin:
        0
        0
        12px
        0
        !important;

    padding:
        10px
        !important;

    border:
        1px solid
        rgba(214,164,59,.38)
        !important;

    border-radius:
        7px
        !important;

    background:
        rgba(4,10,11,.92)
        !important;

    background-image:
        none
        !important;

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.025)
        !important;
}


#searchBox {

    width:
        100%
        !important;

    min-height:
        34px
        !important;

    margin:
        0
        !important;

    border:
        1px solid
        rgba(255,255,255,.11)
        !important;

    border-radius:
        5px
        !important;

    background:
        #030809
        !important;

    background-image:
        none
        !important;

    color:
        #dfe6e7
        !important;

    box-shadow:
        inset 0 1px 5px
        rgba(0,0,0,.72)
        !important;
}


#searchBox:focus {

    border-color:
        rgba(214,164,59,.68)
        !important;

    outline:
        none
        !important;
}


/* ------------------------------------------------------------
   REAL CONTROL CENTER BUTTON DIMENSIONS
   Appearance comes from cp-button.
   ------------------------------------------------------------ */

#searchButton,
#clearButton,
.ag-dup-scan {

    width:
        auto
        !important;

    min-width:
        88px
        !important;

    min-height:
        32px
        !important;

    margin:
        0
        !important;

    cursor:
        pointer
        !important;
}


/* ------------------------------------------------------------
   MAIN INVENTORY WORKSPACE
   ------------------------------------------------------------ */

.workspace {

    display:grid !important;

    grid-template-columns:
        minmax(230px,280px)
        minmax(430px,1fr)
        minmax(260px,315px)
        !important;

    gap:
        12px
        !important;

    align-items:
        stretch
        !important;
}


/*
 * rp-region-card supplies the Control Center card.
 * Remove every old decorative background that may still
 * be coming from Inventory Browser's legacy CSS.
 */

.workspace-card {

    display:flex !important;

    flex-direction:
        column
        !important;

    min-width:
        0
        !important;

    min-height:
        550px
        !important;

    padding:
        0
        !important;

    overflow:
        hidden
        !important;

    background-image:
        none
        !important;
}


/* ------------------------------------------------------------
   WORKSPACE HEADERS
   ------------------------------------------------------------ */

.workspace-title {

    min-height:
        45px
        !important;

    flex:
        0 0 auto
        !important;

    display:flex !important;

    align-items:
        center
        !important;

    justify-content:
        space-between
        !important;

    gap:
        10px
        !important;

    margin:
        0
        !important;

    padding:
        0
        13px
        !important;

    border-bottom:
        1px solid
        rgba(214,164,59,.20)
        !important;

    background:
        rgba(4,10,11,.74)
        !important;

    background-image:
        none
        !important;
}


.workspace-title h2 {

    margin:
        0
        !important;

    color:
        #e5b339
        !important;

    font-size:
        14px
        !important;

    font-weight:
        900
        !important;

    letter-spacing:
        .025em
        !important;
}


.workspace-hint {

    color:
        rgba(215,225,226,.45)
        !important;

    font-size:
        8px
        !important;
}


/* ------------------------------------------------------------
   FOLDERS
   ------------------------------------------------------------ */

.folder-tree {

    flex:
        1 1 auto
        !important;

    min-height:
        0
        !important;

    max-height:
        none
        !important;

    overflow:
        auto
        !important;

    padding:
        8px
        !important;

    background:
        rgba(0,0,0,.12)
        !important;

    background-image:
        none
        !important;
}


.folder-button {

    min-height:
        29px
        !important;

    margin:
        1px
        0
        !important;

    border:
        1px solid
        transparent
        !important;

    border-radius:
        4px
        !important;

    background:
        transparent
        !important;

    background-image:
        none
        !important;

    box-shadow:
        none
        !important;
}


.folder-button:hover {

    border-color:
        rgba(214,164,59,.20)
        !important;

    background:
        rgba(214,164,59,.045)
        !important;
}


.folder-button.active,
.folder-button.selected {

    border-color:
        rgba(214,164,59,.46)
        !important;

    background:
        rgba(214,164,59,.09)
        !important;
}


/* ------------------------------------------------------------
   ITEMS
   ------------------------------------------------------------ */

.item-header {

    flex:
        0 0 auto
        !important;

    margin:
        8px
        !important;

    padding:
        8px
        10px
        !important;

    border:
        1px solid
        rgba(214,164,59,.15)
        !important;

    border-radius:
        5px
        !important;

    background:
        rgba(0,0,0,.23)
        !important;

    background-image:
        none
        !important;
}


.current-folder {

    color:
        #e5b339
        !important;
}


.item-list {

    flex:
        1 1 auto
        !important;

    min-height:
        0
        !important;

    max-height:
        none
        !important;

    overflow:
        auto
        !important;

    padding:
        0
        8px
        8px
        8px
        !important;

    background:
        transparent
        !important;

    background-image:
        none
        !important;
}


.item-row {

    margin:
        0
        0
        5px
        0
        !important;

    border:
        1px solid
        rgba(255,255,255,.07)
        !important;

    border-radius:
        4px
        !important;

    background:
        rgba(255,255,255,.018)
        !important;

    background-image:
        none
        !important;

    box-shadow:
        none
        !important;
}


.item-row:hover {

    border-color:
        rgba(214,164,59,.20)
        !important;

    background:
        rgba(214,164,59,.035)
        !important;
}


.item-row.active {

    border-color:
        rgba(214,164,59,.52)
        !important;

    background:
        rgba(214,164,59,.08)
        !important;

    background-image:
        none
        !important;
}


/* ------------------------------------------------------------
   DETAILS
   ------------------------------------------------------------ */

.details-card {

    background-image:
        none
        !important;
}


.details-card [class*="detail"],
.details-card [id*="detail"] {

    background-image:
        none
        !important;
}


/* ------------------------------------------------------------
   STATUS
   ------------------------------------------------------------ */

.status-bar {

    margin-top:
        12px
        !important;

    border:
        1px solid
        rgba(214,164,59,.18)
        !important;

    border-radius:
        5px
        !important;

    background:
        rgba(0,0,0,.20)
        !important;

    background-image:
        none
        !important;

    box-shadow:
        none
        !important;
}


/* ------------------------------------------------------------
   RESPONSIVE
   ------------------------------------------------------------ */

@media(max-width:1200px) {

    .workspace {

        grid-template-columns:
            230px
            minmax(350px,1fr)
            260px
            !important;
    }

}


@media(max-width:900px) {

    .summary-grid {

        grid-template-columns:
            1fr
            !important;
    }


    .workspace {

        grid-template-columns:
            1fr
            !important;
    }


    .workspace-card {

        min-height:
            360px
            !important;
    }

}


@media(max-width:650px) {

    .search-bar {

        grid-template-columns:
            1fr
            !important;
    }


    #searchButton,
    #clearButton,
    .ag-dup-scan {

        width:
            100%
            !important;
    }

}

</style>

<!-- AUSTRALIA INVENTORY REAL DARK V2 END -->
</head>


<body>


<main class="cp-page rp-page browser-shell">


<?php
$level = ag_user_level($session);
$isAdmin = ag_is_admin($session);

$siteHeaderKicker = $isAdmin ? "ADMINISTRATION" : "ACCOUNT";
$siteHeaderTitle = "
INVENTORY BROWSER
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
        <span class="ag-inv-symbol"><img class="ag-sentinel-direct-icon ag-sentinel-sm" src="/Other/assets/icons/sentinel/files.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span> TOTAL FOLDERS
    </div>

    <div
        class="summary-number"
        id="folderTotal">

        &mdash;

    </div>

</div>


<div class="rp-summary-item summary-card">

    <div class="summary-label">
        <span class="ag-inv-symbol"><img class="ag-sentinel-direct-icon ag-sentinel-sm" src="/Other/assets/icons/sentinel/inventory.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span> TOTAL ITEMS
    </div>

    <div
        class="summary-number"
        id="itemTotal">

        &mdash;

    </div>

</div>


<div class="rp-summary-item summary-card">

    <div class="summary-label">
        <span class="ag-inv-symbol"><img class="ag-sentinel-direct-icon ag-sentinel-sm" src="/Other/assets/icons/sentinel/view.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span> CURRENT VIEW
    </div>

    <div
        class="summary-number"
        id="currentView">

        <span class="ag-inv-symbol"><img class="ag-sentinel-direct-icon ag-sentinel-sm" src="/Other/assets/icons/sentinel/files.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span> FOLDERS

    </div>

</div>

</section>



<section class="search-bar">

<input
    id="searchBox"
    type="search"
    maxlength="100"
    placeholder="Search your inventory by item name...">

<button
    id="searchButton"
    class="cp-button"
    type="button">

    <span class="ag-inv-symbol"><img class="ag-sentinel-direct-icon ag-sentinel-sm" src="/Other/assets/icons/sentinel/search.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span> SEARCH

</button>

<button
    id="clearButton"
    class="cp-button"
    type="button">

    <span class="ag-inv-symbol"><img class="ag-sentinel-direct-icon ag-sentinel-sm" src="/Other/assets/icons/sentinel/delete.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span> CLEAR

</button>

</section>



<section class="workspace">


<section class="rp-region-card workspace-card">

<div class="workspace-title">

    <h2>
        <span class="ag-inv-symbol"><img class="ag-sentinel-direct-icon ag-sentinel-sm" src="/Other/assets/icons/sentinel/files.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span> FOLDERS
    </h2>

    <span class="workspace-hint">
        INVENTORY
    </span>

</div>

<div
    id="folderTree"
    class="folder-tree">

    Loading folders...

</div>

</section>



<section class="rp-region-card workspace-card">

<div class="workspace-title">

    <h2>
        <span class="ag-inv-symbol"><img class="ag-sentinel-direct-icon ag-sentinel-sm" src="/Other/assets/icons/sentinel/inventory.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span> ITEMS
    </h2>

    <span
        id="itemMode"
        class="workspace-hint">

        SELECT A FOLDER

    </span>

</div>


<div class="item-header">

    <div
        id="currentFolder"
        class="current-folder">

        Select a folder

    </div>

    <div
        id="itemCount"
        class="item-count">

        0 items

    </div>

</div>


<div
    id="itemList"
    class="item-list">

    <div class="empty-state">
        Choose a folder on the left to view its contents.
    </div>

</div>

</section>



<section class="rp-region-card workspace-card details-card">

<div class="workspace-title">

    <h2>
        <span class="ag-inv-symbol"><img class="ag-sentinel-direct-icon ag-sentinel-sm" src="/Other/assets/icons/sentinel/help.png" alt="" aria-hidden="true" draggable="false" decoding="async"></span> ITEM DETAILS
    </h2>

    <span class="workspace-hint">
        SELECTED ITEM
    </span>

</div>


<div
    id="detailEmpty"
    class="empty-state">

    Select an inventory item to view its details.

</div>


<div
    id="details"
    class="details">

<div
    id="detailName"
    class="detail-name">
</div>


<div class="detail-row">

    <div class="detail-label">
        ITEM TYPE
    </div>

    <div
        id="detailType"
        class="detail-value">
    </div>

</div>


<div class="detail-row">

    <div class="detail-label">
        DESCRIPTION
    </div>

    <div
        id="detailDescription"
        class="detail-value">
    </div>

</div>


<div class="detail-row">

    <div class="detail-label">
        INVENTORY UUID
    </div>

    <div
        id="detailInventory"
        class="detail-value">
    </div>

</div>


<div class="detail-row">

    <div class="detail-label">
        ASSET UUID
    </div>

    <div
        id="detailAsset"
        class="detail-value">
    </div>

</div>


<div class="detail-row">

    <div class="detail-label">
        CREATOR UUID
    </div>

    <div
        id="detailCreator"
        class="detail-value">
    </div>

</div>


<div class="detail-row">

    <div class="detail-label">
        CREATED
    </div>

    <div
        id="detailCreated"
        class="detail-value">
    </div>

</div>

</div>

</section>


</section>


<section
    id="statusBar"
    class="status-bar">

    Loading your Grid inventory...

</section>


</main>



<script>

(function () {


const ZERO =
    "00000000-0000-0000-0000-000000000000";


const tree =
    document.getElementById(
        "folderTree"
    );


const list =
    document.getElementById(
        "itemList"
    );


const statusBar =
    document.getElementById(
        "statusBar"
    );


const currentFolder =
    document.getElementById(
        "currentFolder"
    );


const itemMode =
    document.getElementById(
        "itemMode"
    );


const itemCount =
    document.getElementById(
        "itemCount"
    );


const currentView =
    document.getElementById(
        "currentView"
    );


const searchBox =
    document.getElementById(
        "searchBox"
    );


let folders =
    [];


let byId =
    new Map();


let activeFolder =
    null;


let activeItem =
    null;


function status(
    text,
    isError
){

    statusBar.textContent =
        text;

    statusBar.classList.toggle(
        "error",
        !!isError
    );
}


async function api(
    action,
    parameters
){

    const url =
        new URL(
            window.location.href
        );

    url.search =
        "";

    url.searchParams.set(
        "api",
        action
    );


    if(parameters){

        Object.keys(
            parameters
        ).forEach(
            function(key){

                url.searchParams.set(
                    key,
                    parameters[key]
                );

            }
        );
    }


    const response =
        await fetch(
            url.toString(),
            {
                cache:
                    "no-store",

                credentials:
                    "same-origin"
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


function typeInfo(
    raw
){

    const type =
        Number(
            raw
        );


    const map = {

        0:["\uD83D\uDDBC\uFE0F","Texture"],
        1:["\uD83D\uDD0A","Sound"],
        2:["\uD83D\uDC64","Calling Card"],
        3:["\uD83D\uDCCD","Landmark"],
        5:["\uD83D\uDC55","Clothing"],
        6:["\uD83D\uDCE6","Object"],
        7:["\uD83D\uDCDD","Notecard"],
        10:["\uD83D\uDCDC","Script"],
        13:["\uD83E\uDDCD","Body Part"],
        20:["\uD83C\uDF9E\uFE0F","Animation"],
        21:["\uD83D\uDC4B","Gesture"],
        24:["\uD83D\uDD17","Inventory Link"],
        25:["\uD83D\uDD17","Folder Link"]

    };


    return (
        map[type] ||
        ["\uD83D\uDCC4","Inventory Item"]
    );
}


function displayDate(
    raw
){

    if(
        raw === null ||
        raw === "" ||
        typeof raw === "undefined"
    ){
        return "Unavailable";
    }


    const value =
        Number(
            raw
        );


    if(
        !Number.isFinite(value) ||
        value <= 0
    ){
        return String(raw);
    }


    return new Date(
        value * 1000
    ).toLocaleString();
}


function clearDetails(){

    document.getElementById(
        "detailEmpty"
    ).style.display =
        "flex";


    document.getElementById(
        "details"
    ).style.display =
        "none";


    activeItem =
        null;
}


function showDetails(
    item,
    row
){

    if(activeItem){

        activeItem.classList.remove(
            "active"
        );
    }


    activeItem =
        row;


    row.classList.add(
        "active"
    );


    const info =
        typeInfo(
            item.assetType
        );


    document.getElementById(
        "detailEmpty"
    ).style.display =
        "none";


    document.getElementById(
        "details"
    ).style.display =
        "block";


    document.getElementById(
        "detailName"
    ).textContent =
        item.name ||
        "(Unnamed item)";


    document.getElementById(
        "detailType"
    ).textContent =
        info[1] +
        " (" +
        (
            item.assetType ??
            "?"
        ) +
        ")";


    document.getElementById(
        "detailDescription"
    ).textContent =
        item.description ||
        "No description";


    document.getElementById(
        "detailInventory"
    ).textContent =
        item.id ||
        "Unavailable";


    document.getElementById(
        "detailAsset"
    ).textContent =
        item.assetId ||
        "Unavailable";


    document.getElementById(
        "detailCreator"
    ).textContent =
        item.creatorId ||
        "Unavailable";


    document.getElementById(
        "detailCreated"
    ).textContent =
        displayDate(
            item.creationDate
        );
}


function renderItems(
    items
){

    list.innerHTML =
        "";


    clearDetails();


    itemCount.textContent =
        items.length +
        (
            items.length === 1
            ? " item"
            : " items"
        );


    if(items.length === 0){

        const empty =
            document.createElement(
                "div"
            );

        empty.className =
            "empty-state";

        empty.textContent =
            "No inventory items were found here.";

        list.appendChild(
            empty
        );

        return;
    }


    items.forEach(
        function(item){

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


            const icon =
                document.createElement(
                    "div"
                );

            icon.className =
                "item-icon";

            icon.textContent =
                info[0];


            const copy =
                document.createElement(
                    "div"
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


            const desc =
                document.createElement(
                    "div"
                );

            desc.className =
                "item-desc";

            desc.textContent =
                item.description ||
                info[1];


            copy.appendChild(
                name
            );

            copy.appendChild(
                desc
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

                    showDetails(
                        item,
                        row
                    );
                }
            );


            list.appendChild(
                row
            );
        }
    );
}


async function openFolder(
    folder,
    button
){

    try{

        if(activeFolder){

            activeFolder.classList.remove(
                "active"
            );
        }


        activeFolder =
            button;


        button.classList.add(
            "active"
        );


        currentFolder.textContent =
            folder.name ||
            "Inventory Folder";


        itemMode.textContent =
            "FOLDER CONTENTS";


        currentView.textContent =
            "FOLDER";


        list.innerHTML =
            '<div class="empty-state">Loading items...</div>';


        const data =
            await api(
                "items",
                {
                    folder:
                        folder.id
                }
            );


        renderItems(
            data.items ||
            []
        );


        status(
            "Viewing " +
            (
                folder.name ||
                "inventory folder"
            ) +
            ". Inventory Browser is READ ONLY.",
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


function buildTree(){

    tree.innerHTML =
        "";


    byId =
        new Map();


    const childMap =
        new Map();


    folders.forEach(
        function(folder){

            byId.set(
                String(
                    folder.id
                ).toLowerCase(),
                folder
            );
        }
    );


    folders.forEach(
        function(folder){

            const parent =
                String(
                    folder.parent ||
                    ZERO
                ).toLowerCase();


            if(
                !childMap.has(parent)
            ){

                childMap.set(
                    parent,
                    []
                );
            }


            childMap.get(
                parent
            ).push(
                folder
            );
        }
    );


    /*
     * Firestorm-style inventory ordering.
     * Standard inventory folders first, custom folders after.
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


    function sort(list){

        list.sort(
            function(a,b){

                const aName =
                    String(
                        a.name || ""
                    ).trim();

                const bName =
                    String(
                        b.name || ""
                    ).trim();

                const aKey =
                    aName.toLowerCase();

                const bKey =
                    bName.toLowerCase();

                const aRank =
                    firestormFolderRank.has(aKey)
                    ? firestormFolderRank.get(aKey)
                    : 10000;

                const bRank =
                    firestormFolderRank.has(bKey)
                    ? firestormFolderRank.get(bKey)
                    : 10000;

                if(aRank !== bRank){
                    return aRank - bRank;
                }

                return aName.localeCompare(
                    bName,
                    undefined,
                    {
                        numeric:true,
                        sensitivity:"base"
                    }
                );
            }
        );
    }

    childMap.forEach(
        sort
    );


    const roots =
        folders.filter(
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
                    !byId.has(parent)
                );
            }
        );


    sort(
        roots
    );


    const rendered =
        new Set();


    function addFolder(
        folder,
        parentElement,
        depth
    ){

        if(depth > 80){
            return;
        }


        const id =
            String(
                folder.id
            ).toLowerCase();


        if(rendered.has(id)){
            return;
        }


        rendered.add(id);


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

        button.dataset.folderId =
            id;

        button.type =
            "button";

        button.className =
            "folder-button";


        const icon =
            document.createElement(
                "span"
            );

        icon.className =
            "folder-icon";

        icon.textContent =
            "\uD83D\uDCC1";


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


        button.appendChild(icon);
        button.appendChild(name);

        const rawItemCount =
            Number(
                folder.itemCount ||
                0
            );

        const directItemCount =
            Number.isFinite(rawItemCount)
            ? Math.max(0,rawItemCount)
            : 0;

        const countBadge =
            document.createElement(
                "span"
            );

        countBadge.className =
            "folder-count";

        countBadge.textContent = directItemCount.toLocaleString();

        button.appendChild(
            countBadge
        );


        button.addEventListener(
            "click",
            function(){

                openFolder(
                    folder,
                    button
                );
            }
        );


        node.appendChild(
            button
        );


        const children =
            childMap.get(id) ||
            [];

        if(
            directItemCount === 0 &&
            children.length === 0
        ){

            countBadge.classList.add(
                "ag-folder-empty-count"
            );

            countBadge.textContent = "0 EMPTY";

            button.classList.add(
                "ag-empty-folder"
            );
        }


        if(children.length > 0){

            const childBox =
                document.createElement(
                    "div"
                );

            childBox.className =
                "folder-children";


            children.forEach(
                function(child){

                    addFolder(
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


    roots.forEach(
        function(folder){

            addFolder(
                folder,
                tree,
                0
            );
        }
    );


    /*
     * Any orphaned inventory folders are still displayed.
     */

    folders.forEach(
        function(folder){

            const id =
                String(
                    folder.id
                ).toLowerCase();


            if(
                !rendered.has(id)
            ){

                addFolder(
                    folder,
                    tree,
                    0
                );
            }
        }
    );


    if(folders.length === 0){

        tree.textContent =
            "No inventory folders were found.";
    }
}




function agInventoryTypeOrder(
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


function renderInventoryTypeTotals(
    totals
){

    let panel =
        document.getElementById(
            "agInventoryTypeTotals"
        );

    if(!panel){

        const summaryGrid =
            document.querySelector(
                ".summary-grid"
            );

        if(!summaryGrid){
            return;
        }

        panel =
            document.createElement(
                "section"
            );

        panel.id =
            "agInventoryTypeTotals";

        panel.className =
            "ag-type-panel card";

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
            "INVENTORY BY TYPE";

        const note =
            document.createElement(
                "div"
            );

        note.className =
            "ag-type-note";

        note.textContent =
            "ALL ITEMS IN YOUR INVENTORY";

        heading.appendChild(title);
        heading.appendChild(note);

        const list =
            document.createElement(
                "div"
            );

        list.className =
            "ag-type-list";

        panel.appendChild(heading);
        panel.appendChild(list);

        summaryGrid.insertAdjacentElement(
            "afterend",
            panel
        );
    }

    const list =
        panel.querySelector(
            ".ag-type-list"
        );

    list.innerHTML = "";

    const keys =
        agInventoryTypeOrder(
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
            "No inventory type totals available.";

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

            icon.textContent =
                info[0];

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

/* Grid - DUPLICATE FINDER API BRIDGE V1 */
window.agInventoryDuplicateApi = api;

async function loadInventory(){

    try{

        status(
            "Loading your Grid inventory...",
            false
        );


        tree.textContent =
            "Loading folders...";


        const responses =
            await Promise.all([
                api("summary"),
                api("folders")
            ]);


        const summary =
            responses[0];


        const folderData =
            responses[1];

        renderInventoryTypeTotals(
            summary.typeTotals || {}
        );


        document.getElementById(
            "folderTotal"
        ).textContent =
            Number(
                summary.folders || 0
            ).toLocaleString();


        document.getElementById(
            "itemTotal"
        ).textContent =
            Number(
                summary.items || 0
            ).toLocaleString();


        folders =
            folderData.folders ||
            [];


        buildTree();


        currentView.textContent =
            "FOLDERS";


        status(
            "Inventory loaded successfully. Select a folder or search for an item. This browser is READ ONLY.",
            false
        );

    }
    catch(error){

        tree.textContent =
            "Inventory could not be loaded.";


        status(
            error.message,
            true
        );
    }
}


async function runSearch(){

    const query =
        searchBox.value.trim();


    if(query.length < 2){

        status(
            "Enter at least 2 characters to search.",
            true
        );

        searchBox.focus();

        return;
    }


    try{

        currentFolder.textContent =
            'Search: "' +
            query +
            '"';


        itemMode.textContent =
            "SEARCH RESULTS";


        currentView.textContent =
            "SEARCH";


        list.innerHTML =
            '<div class="empty-state">Searching inventory...</div>';


        const data =
            await api(
                "search",
                {
                    q:
                        query
                }
            );


        renderItems(
            data.items ||
            []
        );


        status(
            "Search complete. Up to 300 matching items are displayed.",
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


document.getElementById(
    "searchButton"
).addEventListener(
    "click",
    runSearch
);


searchBox.addEventListener(
    "keydown",
    function(event){

        if(event.key === "Enter"){

            event.preventDefault();

            runSearch();
        }
    }
);


document.getElementById(
    "clearButton"
).addEventListener(
    "click",
    function(){

        searchBox.value =
            "";

        currentFolder.textContent =
            "Select a folder";

        itemMode.textContent =
            "SELECT A FOLDER";

        currentView.textContent =
            "FOLDERS";

        renderItems([]);

        status(
            "Search cleared. Select a folder to continue.",
            false
        );
    }
);


document.getElementById(
    "refreshButton"
).addEventListener(
    "click",
    loadInventory
);


loadInventory();


})();

</script>



<script id="ag-firestorm-inventory-tree-v1-js">

(function(){

    const tree =
        document.getElementById("folderTree");

    const itemList =
        document.getElementById("itemList");

    const refreshButton =
        document.getElementById("refreshButton");

    if(!tree || !refreshButton){
        return;
    }

    if(tree.dataset.agFirestormTree === "1"){
        return;
    }

    tree.dataset.agFirestormTree = "1";

    const expanded = new Set();

    let firstPass = true;
    let selectedKey = "";
    let restoreClick = false;
    let refreshPending = false;
    let savedFolderScroll = 0;
    let savedItemScroll = 0;
    let applyTimer = null;


    function directButton(node){

        if(!node){
            return null;
        }

        for(const child of node.children){

            if(
                child.classList &&
                child.classList.contains("folder-button")
            ){
                return child;
            }
        }

        return null;
    }


    function directChildren(node){

        if(!node){
            return null;
        }

        for(const child of node.children){

            if(
                child.classList &&
                child.classList.contains("folder-children")
            ){
                return child;
            }
        }

        return null;
    }


    function folderName(node){

        const button = directButton(node);

        if(!button){
            return "";
        }

        const name =
            button.querySelector(".folder-name");

        return name
            ? name.textContent.trim()
            : "";
    }


    function nodeKey(node){

        const button =
            directButton(node);

        if(!button){
            return "";
        }

        return String(
            button.dataset.folderId || ""
        )
        .trim()
        .toLowerCase();
    }

    function rootNode(node){

        return (
            node.parentElement === tree
        );
    }


    function findNodeByKey(key){

        if(!key){
            return null;
        }

        const nodes =
            tree.querySelectorAll(".folder-node");

        for(const node of nodes){

            if(nodeKey(node) === key){
                return node;
            }
        }

        return null;
    }


    function applyTreeState(){

        const nodes =
            tree.querySelectorAll(".folder-node");

        nodes.forEach(
            function(node){

                const button =
                    directButton(node);

                const childBox =
                    directChildren(node);

                if(!button){
                    return;
                }

                const icon =
                    button.querySelector(".folder-icon");

                const key =
                    nodeKey(node);

                if(childBox){

                    button.classList.add(
                        "ag-has-children"
                    );

                    if(firstPass && rootNode(node)){
                        expanded.add(key);
                    }

                    const isOpen =
                        expanded.has(key);

                    node.classList.toggle(
                        "ag-expanded",
                        isOpen
                    );

                    node.classList.toggle(
                        "ag-collapsed",
                        !isOpen
                    );

                    if(icon){

                        const wanted =
                            isOpen
                            ? "\uD83D\uDCC2"
                            : "\uD83D\uDCC1";

                        if(icon.textContent !== wanted){
                            icon.textContent = wanted;
                        }
                    }

                }
                else{

                    button.classList.remove(
                        "ag-has-children"
                    );

                    node.classList.remove(
                        "ag-expanded",
                        "ag-collapsed"
                    );

                    if(
                        icon &&
                        icon.textContent !== "\uD83D\uDCC1"
                    ){
                        icon.textContent = "\uD83D\uDCC1";
                    }
                }
            }
        );

        firstPass = false;

        if(refreshPending){

            tree.scrollTop =
                savedFolderScroll;

            if(itemList){
                itemList.scrollTop =
                    savedItemScroll;
            }

            if(
                selectedKey &&
                !tree.querySelector(".folder-button.active")
            ){

                const selectedNode =
                    findNodeByKey(selectedKey);

                const selectedButton =
                    directButton(selectedNode);

                if(selectedButton){

                    restoreClick = true;
                    selectedButton.click();
                    restoreClick = false;
                }
            }

            tree.scrollTop =
                savedFolderScroll;

            if(itemList){
                itemList.scrollTop =
                    savedItemScroll;
            }

            refreshPending = false;
        }
    }


    function scheduleApply(){

        if(applyTimer){
            clearTimeout(applyTimer);
        }

        applyTimer =
            setTimeout(
                applyTreeState,
                100
            );
    }


    tree.addEventListener(
        "click",
        function(event){

            const button =
                event.target.closest(
                    ".folder-button"
                );

            if(
                !button ||
                !tree.contains(button)
            ){
                return;
            }

            const node =
                button.closest(
                    ".folder-node"
                );

            if(!node){
                return;
            }

            const key =
                nodeKey(node);

            selectedKey = key;

            if(restoreClick){
                return;
            }

            const childBox =
                directChildren(node);

            if(!childBox){
                return;
            }

            if(expanded.has(key)){
                expanded.delete(key);
            }
            else{
                expanded.add(key);
            }

            applyTreeState();
        }
    );


    const observer =
        new MutationObserver(
            scheduleApply
        );

    observer.observe(
        tree,
        {
            childList:true,
            subtree:true
        }
    );


    function automaticRefresh(){

        if(document.hidden){
            return;
        }

        const focused =
            document.activeElement;

        if(
            focused &&
            (
                focused.tagName === "INPUT" ||
                focused.tagName === "TEXTAREA"
            )
        ){
            return;
        }

        const activeButton =
            tree.querySelector(
                ".folder-button.active"
            );

        if(activeButton){

            const activeNode =
                activeButton.closest(
                    ".folder-node"
                );

            if(activeNode){
                selectedKey =
                    nodeKey(activeNode);
            }
        }

        savedFolderScroll =
            tree.scrollTop;

        if(itemList){
            savedItemScroll =
                itemList.scrollTop;
        }

        refreshPending = true;

        if(
            refreshButton &&
            !refreshButton.disabled
        ){
            refreshButton.click();
        }
    }


    /*
     * Automatic inventory refresh disabled.
     * Use the REFRESH button when inventory changes.
     */


    applyTreeState();

})();

</script>

<script id="ag-inventory-browser-uuid-breadcrumb-v1-js">

(function(){

    const tree =
        document.getElementById("folderTree");

    const currentFolder =
        document.getElementById("currentFolder");

    if(!tree || !currentFolder){
        return;
    }

    if(
        document.body.dataset.agBrowserBreadcrumb ===
        "1"
    ){
        return;
    }

    document.body.dataset.agBrowserBreadcrumb =
        "1";


    function directButton(node){

        if(!node){
            return null;
        }

        for(const child of node.children){

            if(
                child.classList &&
                child.classList.contains("folder-button")
            ){
                return child;
            }
        }

        return null;
    }


    function cleanName(value){

        const name =
            String(value || "").trim();

        if(
            name.toLowerCase() === "my inventory"
        ){
            return "Inventory";
        }

        return name;
    }


    function nameFromButton(button){

        if(!button){
            return "";
        }

        const name =
            button.querySelector(".folder-name");

        return cleanName(
            name
            ? name.textContent
            : ""
        );
    }


    function pathFromButton(button){

        if(!button){
            return ["Inventory"];
        }

        const parts = [];

        let node =
            button.closest(".folder-node");

        while(node){

            const currentButton =
                directButton(node);

            const label =
                nameFromButton(currentButton);

            if(label){
                parts.unshift(label);
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

        if(
            parts.length === 0 ||
            parts[0].toLowerCase() !== "inventory"
        ){
            parts.unshift("Inventory");
        }

        return parts;
    }


    const breadcrumb =
        document.createElement("div");

    breadcrumb.id =
        "agInventoryBrowserBreadcrumb";

    breadcrumb.className =
        "ag-browser-breadcrumb";


    function render(parts){

        const path =
            Array.isArray(parts)
            ? parts.filter(Boolean)
            : [];

        breadcrumb.innerHTML = "";

        if(path.length === 0){
            path.push("Inventory");
        }

        path.forEach(
            function(part,index){

                if(index > 0){

                    breadcrumb.appendChild(
                        document.createTextNode(
                            " \u203A "
                        )
                    );
                }

                const span =
                    document.createElement("span");

                span.textContent = part;

                if(index === 0){
                    span.className =
                        "ag-breadcrumb-root";
                }

                breadcrumb.appendChild(span);
            }
        );

        breadcrumb.title =
            path.join(" \u203A ");
    }


    const itemHeader =
        currentFolder.closest(".item-header");

    if(itemHeader){

        itemHeader.insertAdjacentElement(
            "afterend",
            breadcrumb
        );

    }
    else{

        currentFolder.insertAdjacentElement(
            "afterend",
            breadcrumb
        );
    }


    function updateFromActive(){

        const active =
            tree.querySelector(
                ".folder-button.active"
            );

        if(active){
            render(pathFromButton(active));
        }
        else{
            render(["Inventory"]);
        }
    }


    tree.addEventListener(
        "click",
        function(event){

            const button =
                event.target.closest(
                    ".folder-button"
                );

            if(
                !button ||
                !tree.contains(button)
            ){
                return;
            }

            render(
                pathFromButton(button)
            );
        }
    );


    const observer =
        new MutationObserver(
            function(mutations){

                let activeChanged = false;

                for(const mutation of mutations){

                    if(
                        mutation.type === "attributes" &&
                        mutation.attributeName === "class"
                    ){
                        activeChanged = true;
                        break;
                    }
                }

                if(activeChanged){
                    updateFromActive();
                }
            }
        );

    observer.observe(
        tree,
        {
            subtree:true,
            attributes:true,
            attributeFilter:["class"]
        }
    );


    updateFromActive();

})();

</script>

<script id="ag-inventory-duplicate-browser-v1-js">

/* Grid - INVENTORY DUPLICATE FINDER V1 */

function agDupTypeInfo(raw){

    const type = Number(raw);

    const map = {
        0:["\uD83D\uDDBC\uFE0F","Texture"],
        1:["\uD83D\uDD0A","Sound"],
        2:["\uD83D\uDC64","Calling Card"],
        3:["\uD83D\uDCCD","Landmark"],
        5:["\uD83D\uDC55","Clothing"],
        6:["\uD83D\uDCE6","Object"],
        7:["\uD83D\uDCDD","Notecard"],
        10:["\uD83D\uDCDC","Script"],
        13:["\uD83E\uDDCD","Body Part"],
        20:["\uD83C\uDF9E\uFE0F","Animation"],
        21:["\uD83D\uDC4B","Gesture"],
        24:["\uD83D\uDD17","Inventory Link"],
        25:["\uD83D\uDD17","Folder Link"]
    };

    return (
        map[type] ||
        ["\uD83D\uDCC4","Inventory Item"]
    );
}

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

function agDupRender(target,data){

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

            const info = agDupTypeInfo(group.assetType);

            const icon = document.createElement("span");
            icon.className = "ag-dup-icon";
            icon.textContent = info[0];

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

        if(document.getElementById("agInventoryDuplicateFinder")){
            return;
        }

        const anchor =
            document.getElementById("agInventoryTypeTotals") ||
            document.querySelector(".summary-grid");

        if(!anchor){
            return;
        }

        const panel = document.createElement("section");
        panel.id = "agInventoryDuplicateFinder";
        panel.className = "ag-duplicate-panel card";

        const head = document.createElement("div");
        head.className = "ag-dup-head";

        const text = document.createElement("div");

        const title = document.createElement("div");
        title.className = "ag-dup-main-title";
        title.textContent = "DUPLICATE FINDER";

        const note = document.createElement("div");
        note.className = "ag-dup-note";
        note.textContent = "READ ONLY \u2014 NOTHING WILL BE DELETED";

        text.appendChild(title);
        text.appendChild(note);

        const button = document.createElement("button");
        button.type = "button";
        button.className = "ag-dup-scan";
        button.textContent = "SCAN DUPLICATES";

        head.appendChild(text);
        head.appendChild(button);

        const results = document.createElement("div");
        results.className = "ag-dup-results";
        results.innerHTML =
            '<div class="ag-dup-placeholder">Click SCAN DUPLICATES to analyse your inventory.</div>';

        panel.appendChild(head);
        panel.appendChild(results);

        anchor.insertAdjacentElement("afterend",panel);

        button.addEventListener("click",async function(){

            button.disabled = true;
            button.textContent = "SCANNING...";

            results.innerHTML =
                '<div class="ag-dup-placeholder">Scanning inventory...</div>';

            try{

                const responses =
                    await Promise.all([
                        window.agInventoryDuplicateApi(
                            "duplicates"
                        ),
                        window.agInventoryDuplicateApi(
                            "folders"
                        )
                    ]);

                const response =
                    responses[0];

                const folderResponse =
                    responses[1];

                const report =
                    agDupBuild(
                        response.items || [],
                        folderResponse.folders || [],
                        "Inventory"
                    );

                agDupRender(results,report);

                button.textContent = "REFRESH SCAN";
            }
            catch(error){

                results.innerHTML = "";

                const failed = document.createElement("div");
                failed.className = "ag-dup-error";
                failed.textContent = error.message;
                results.appendChild(failed);

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
<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>





