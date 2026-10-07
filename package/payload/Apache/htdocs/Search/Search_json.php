<?php
// AGPL 3.0 by Fred Beckhusen
require( "flog.php" );

include("database.php");

header("content-type: text/html; charset=UTF-8");
  
 
     
  $text = ($_GET['query'] ?? '');
  $text = "%$text%";
  $sqldata['text1'] = $text;
  
  

  $rc = intval(($_GET['rp'] ?? ''));
   
  if ($rc == "") {
      $rc = 100;
  }
  
 
  $sort = ($_GET['sortname'] ?? '');
  if ($sort == 'Name') {
    $sort = 'Name';
  }else{
    $sort = 'Description';
  }
  
  $ord = ($_GET['sortorder'] ?? '');
  if ($ord == 'asc') {
    $ord = 'asc';
  } else {
    $ord = 'desc';
  }
  
  $qtype = ($_GET['qtype'] ?? '');
  if ($qtype == 'Name') {
    $qtype = 'Name';
  } else {
    $qtype = 'Description';
  }
  
  $total = 0;
   
  $page =  intval(($_GET['page'] ?? '')) || 1;
  if ($page == "" ) {
    $page = 1;
  }
  
  $stack = array();

  $q = "SELECT Name, Description, Location, Regions.Regionname as  Regioname FROM Objects INNER JOIN Regions ON Objects.regionuuid = Regions.regionuuid 
    where " . $qtype . "  like :text1 
    order by " . $sort . ' ' .  $ord ;
    //. " limit " . $lim1 . "," . $lim2;     


    $query = $db->prepare($q);
    $result = $query->execute($sqldata);
    
    class OUT { public $domain; public $port; public $page; public $total; public $rows; }
    class Row { public $cell; }
    
    $out = new OUT();
    
    while ($row = $query->fetch(PDO::FETCH_ASSOC))
    {
        
        $location = $row["Location"];
        #$location = str_replace("/","+", $location );
        
        # need grid name and region name in a url
                
        
        $hop = "<a href=\"hop://" . $DB_GRIDNAME . "+" . $row["Regioname"] . "/" . $location .  "\"  class=\"hop\"><img src=\"images/Hop.png\" height=\"25\"></a>";
        #secondlife://http|!!breath-grid.info|8002+IT+IS+ALL+FREE
        $row = array("hop"=>$hop, "Name"=>$row["Name"],"Description"=>$row["Description"],"Regionname"=>$row["Regioname"],"Location"=>$location);
        
        $rowobj = new Row();
        $rowobj->cell = $row;
        
       # foreach($rowobj->cell as $x=>$x_value)
       # {
       #   echo "Key=" . $x . ", Value=" . $x_value;
       #   echo "<br>";
       # }
  
        
        #$myJSON = json_encode($rowobj);
        #echo $myJSON . "<br>";
        if ($total >= (($page-1) *$rc) && $total < ($page) *$rc) {
          array_push($stack, $rowobj);
        }
        $total++;
        
    }
    if ($total == 0) {
	  flog("Nothing found");
      $row = array("hop"=>"", "Name"=>"No records","Description"=>"No records","Regionname"=>"No records","Location"=>"No records");
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
