<?php

require( "flog.php" );
include("database.php");

$now = time();

include("./parserscanner.php");

flog("\n\nParser Startup");

$sql = "SELECT gateway, host, port FROM hostsregister
            where
            online = 1 and 
            nextcheck < $now AND failcounter =0
            order by host asc
            LIMIT 0,500";
  
$jobsearch = $db->query($sql);

//
// If the sql query returns no rows, all entries in the hostsregister
// table have been checked. Re-run the
// query to select the next set of hosts to be checked.
//
if ($jobsearch->rowCount() == 0)
{      
    echo "Nothing to do\n";
}

while ($jobs = $jobsearch->fetch(PDO::FETCH_NUM))
{    
    echo "Checking " . $jobs[0] . " @ " . $jobs[1] . ":" . $jobs[2] . "\n";
    flog("Checking " . $jobs[0] . " @ " . $jobs[1] . ":" . $jobs[2] );
    $jobs[0] = str_replace('http://','',$jobs[0]);
  
    CheckHost($jobs[0], $jobs[1], $jobs[2]);
}
flog("Scan Complete....");



?>
