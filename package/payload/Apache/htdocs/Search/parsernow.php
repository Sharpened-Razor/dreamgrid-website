<?php
// runs parse right now

require( "flog.php" );
include("database.php");

flog("Booting parsernow.php");

$now = time();

include("./parserscanner.php");

$failcounter = 0;

$sql = "SELECT gateway, host, port FROM hostsregister where online = 1 order by host asc";


// Skip after 10 tries, they need to re-register

$jobsearch = $db->query($sql);

//
// If the sql query returns no rows, all entries in the hostsregister
// table have been checked. Re-run the
// query to select the next set of hosts to be checked.
//
if ($jobsearch->rowCount() == 0)
{
    
    echo "Nothing to do\n";
    $jobsearch = $db->query($sql);
}

while ($jobs = $jobsearch->fetch(PDO::FETCH_NUM))
{    
    echo "Checking " . $jobs[0] . " @ " . $jobs[1] . ":" . $jobs[2] . "\n" ;
    flog("Checking " . $jobs[0] . " @ " . $jobs[1] . ":" . $jobs[2] );
    $jobs[0] = str_replace('http://','',$jobs[0]);
    CheckHost($jobs[0], $jobs[1],$jobs[2]);

}

$db = NULL;

flog("Exit parsernow.php")
?>
