<?php
try {
require_once __DIR__.'/databaseinfo.php';
$dsn="mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8";
$options=[PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];
$db=new PDO($dsn,$DB_USER,$DB_PASSWORD,$options);$db1=new PDO($dsn,$DB_USER,$DB_PASSWORD,$options);}
catch(Throwable $e){error_log('Search database connection failed.');http_response_code(503);exit('Search database is unavailable.');}
