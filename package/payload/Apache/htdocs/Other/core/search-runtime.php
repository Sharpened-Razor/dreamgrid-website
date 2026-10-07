<?php
require_once __DIR__.'/website-runtime.php';
function ag_web_search_database(): array {
    static $resolved=null;
    if($resolved!==null)return $resolved;
    $config=ag_web_database();
    $configured=ag_web_setting(array('WebsiteSearchDatabase','SearchDatabase'));
    if($configured!==null){
        if(preg_match('/[;\x00-\x1f]/',$configured))throw new RuntimeException('Invalid search database configuration.');
        $config['database']=$configured;
        return $resolved=$config;
    }
    if(!class_exists('mysqli'))throw new RuntimeException('Search database discovery requires MySQL support.');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db=new mysqli($config['host'],$config['user'],$config['password'],null,$config['port']);
    try{
        // Discover the search schema by its tables, rather than a fixed name.
        // These identifiers are the installed search protocol's table contract.
        $rows=$db->query("SELECT TABLE_SCHEMA FROM INFORMATION_SCHEMA.TABLES WHERE LOWER(TABLE_NAME) IN ('hostsregister','regions','objects','parcels') GROUP BY TABLE_SCHEMA HAVING COUNT(DISTINCT LOWER(TABLE_NAME))=4")->fetch_all(MYSQLI_NUM);
    }finally{$db->close();}
    if(count($rows)!==1)throw new RuntimeException('Search database could not be uniquely identified; configure WebsiteSearchDatabase in grid settings.');
    $config['database']=$rows[0][0];
    return $resolved=$config;
}
