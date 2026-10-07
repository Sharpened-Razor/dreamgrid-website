<?php
//////////////////////////////////////////////////////////////////////////////
// register.php                                                             //
// This file contains the registration of a simulator to the database       //
// and checks if the simulator is new in the database or a reconnected one  //
//                                                                          //
// If the simulator is old, check if the nextcheck date > registration      //
// When the date is older, make a request to the Parser to grab new data    //
//////////////////////////////////////////////////////////////////////////////

// mod by fkb for gateway for centralized search across grids

require( "flog.php" );
include("database.php");


$now = time();


$gateway = "";
$hostname = "";
$port = "";
$service = "";
$secret = "";

if (isset($_GET['gatekeeper']))    $gateway = $_GET['gatekeeper'] ;
if (isset($_GET['host']))    $hostname = $_GET['host'] ;
if (isset($_GET['port']))    $port = $_GET['port'] ;
if (isset($_GET['service'])) $service = $_GET['service'] ;
if (isset($_GET['secret'])) $secret = $_GET['secret'];

$debug = 0;
$gateway = str_replace('http://','',$gateway);


flog("hostname:" . $hostname );
flog("port:" . $port);
flog("service:" . $service );

if ($hostname == "" || $port == "")
{
    echo "Missing host name and/or port address\n";
    flog("Missing host name and/or port address");
    exit;
}

if ($gateway == "")
{
    $gateway = $hostname . ':' . (int)$port; 
}

if ($service == "online")
{
    echo "Online\n";
    // Check if there is already a database row for this host
    $query = $db->prepare("SELECT register FROM hostsregister WHERE host = ? AND port = ?");
    $query->execute( array($hostname, $port) );

    // Get the request time as a timestamp for later
    $timestamp = $_SERVER['REQUEST_TIME'];

    // If a database row was returned check the nextcheck date
    if ($query->rowCount() > 0)
    {
        echo "Located $hostname\n";
        $query = $db->prepare("UPDATE hostsregister SET " .
		     "online = 1, " .
                     "port = ?, " .
                     "register = ?, " .
                     "nextcheck = 0, failcounter = 0, gateway = ? " .
                     "WHERE host = ? AND port = ?");
        $query->execute( array($port, $timestamp, $gateway, $hostname, $port) );
        flog("Updated as online:" . $hostname . ":" . $port );
    }
    else
    {
        echo "insert $hostname\n";
        // The SELECT did not return a result. Insert a new record.
        $query = $db->prepare("INSERT INTO hostsregister (host, port, nextcheck, gateway, online) VALUES (?, ?, ?, ?,  1)");
        $query->execute( array($hostname, $port, $timestamp, $gateway) );
        flog("Registered as online:" . $hostname . ":" . $port );
    }
}

if ($service == "offline")
{
    echo ("offline:" .  $hostname . ":" . $port);
    flog("offline:" .  $hostname . ":" . $port);
    $query = $db->prepare("Update hostsregister set online = 0 WHERE host = ? AND port = ?");
    $query->execute( array($hostname, $port) );
    $query = $db->prepare("DELETE FROM objects  WHERE gateway = ?");
    $query->execute( array($gateway) );
    $query = $db->prepare("DELETE FROM allparcels  WHERE gateway = ?");
    $query->execute( array($gateway) );
    $query = $db->prepare("DELETE FROM parcels  WHERE gateway = ?");
    $query->execute( array($gateway) );
    $query = $db->prepare("DELETE FROM parcelsales  WHERE gateway = ?");
    $query->execute( array($gateway) );
    $query = $db->prepare("DELETE FROM popularplaces  WHERE gateway = ?");
    $query->execute( array($gateway) );
    $query = $db->prepare("DELETE FROM regions  WHERE gateway = ?");
    $query->execute( array($gateway) );

    
}

$db = NULL;
?>
