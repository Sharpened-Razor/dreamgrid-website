<?php
  
	header("content-type: text/html; charset=UTF-8");
	require( "flog.php" );
	include("database.php");
    
	$text = '';
	if (isset($_GET['query']))     $text = $_GET['query'];	
	$sqldata['text1'] = $text;

	$rc = '';
	if (isset($_GET['rp']))    $rc = intval($_GET['rp'] )  ;
    
	if ($rc == "") {
	  $rc = 100;
	}

	$sort = '';
	if (isset($_GET['sort']))     $sort = $_GET['sortname'] ;
	$sort = 'host';

	$ord = '';
    if (isset($_GET['sortorder']))    $ord = $_GET['sortorder']   ;
    if ($ord == 'asc') {
        $ord = 'asc';
    } else {
        $ord = 'desc';
    }

	$qtype = '';
    if (isset($_GET['qtype']))    $qtype = $_GET['qtype'] ;
    $qtype = 'gateway';

    $total = 0;
	
	$page = '';

    if (isset($_GET['page']))    $page =  intval($_GET['page']);
    if ($page == "" ) {
        $page = 1;
    }

    $stack = array();

    class OUT { public $domain; public $port; public $page; public $total; public $rows; }
    class Row { public $cell; }

    $out = new OUT();

  
    $counter = 0;

    $sql = "SELECT distinct host as host FROM hostsregister  where
            failcounter = 0
	        and online = 1
                and " . $qtype . "  like CONCAT('%', :text1, '%')
            order by " . $sort . ' ' .  $ord ;


    $query = $db->prepare($sql);

#    if (!$query) {
#        echo "\nPDO::errorInfo():\n";
#        print_r($db->errorInfo());
#    }
    
    $result = $query->execute($sqldata);
         
#    if (!$result) {
#        echo "\nPDO::errorInfo():\n";
#        print_r($db->errorInfo());
#    }

    $host = '';

    while ($row = $query->fetch(PDO::FETCH_ASSOC))
    {
        $host = $row["host"];
        
        // get the port
        $sql1 = "SELECT gateway FROM hostsregister  where host = :text1";

        $query1 = $db->prepare($sql1);
        $result1 = $query1->execute(array('text1' => $host));

        $gateway = '';
        while ($row1 = $query1->fetch(PDO::FETCH_ASSOC))
        {
            $gateway = $row1["gateway"];
        }

        $gateway = str_replace (':', '|', $gateway );
        #$regionname = str_replace(' ','+',$row["regionname"]);
        
        $hop = "hop://" . $host;
        $v3 = "secondlife://http|!!" . $gateway;
        $hg = "secondlife://" . $host;
            
        
        $link = "<a href=\"$hop\"><img src=\"hop.png\" height=\"24\"></a>";
        #$link = "<a href=\"$v3\"><img src=\"v3hg2.png\" height=\"24\"></a>";
        #$link .= "<br><a href=\"$hg\"><img src=\"hg.png\" height=\"24\"></a>";

        // get the hours of runtime
        $sql1 = "SELECT sum(checked) as minutes FROM hostsregister where host = :text1";
        $query1 = $db->prepare($sql1);
        $result1 = $query1->execute(array('text1'=>$host));

        $minutes = 0;
        while ($row1 = $query1->fetch(PDO::FETCH_ASSOC))
        {
            $minutes = $row1["minutes"] * 10;
        }

        if ($minutes > 0 ) {
            $minutes = round($minutes /60,1) ;
        } else {
            $minutes = 0;
        }

        $row = array(
                   "hop"=>$link,
                   "Grid"=>$host,
                   "Hours"=> $minutes
                   );

        $rowobj = new Row();
        $rowobj->cell = $row;

        if ($total >= (($page-1) *$rc) && $total < ($page) *$rc) {
        array_push($stack, $rowobj);
        }

        $total++;
    }

    if ($total == 0) {
        $total = 1;
        flog("Nothing found");
        $row = array("hop"=>"","Hours"=>"","Grid"=>"No records");
        $rowobj = new Row();
        $rowobj->cell = $row;
        array_push($stack, $rowobj);
    }

    $out->domain = $CONF_domain;
    $out->port = $CONF_port;
    $out->page  = $page;
    $out->total = $total;
    $out->rows  = $stack;

    $myJSON = json_encode($out);

    echo $myJSON;

  ?>
