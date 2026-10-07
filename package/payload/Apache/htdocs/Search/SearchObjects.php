<?php
// AGPL 3.0 by Fred Beckhusen
  require( "flog.php" );

  
  header("content-type: text/html; charset=UTF-8");
   
  include("database.php");
     
  $text = ($_GET['query'] ?? '');
  $text = "%$text%";
  $sqldata['text1'] = $text;
  

  $rc = intval(($_GET['rp'] ?? ''));
   
  if ($rc == "") {
      $rc = 100;
  }
  

  $sort = ($_GET['sortname'] ?? '');
  flog("************** sort = $sort");
  if ($sort == 'Name') {
    $sort = 'Name';
  } else if ($sort == 'Description') {
    $sort = 'Description';
  } else if ($sort == 'Regionname') {
    $sort = 'Regions.Regionname';
  } else {
    $sort = 'Description';
  }
  
  
  flog("************** sort = $sort");
  
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
  
  flog("text= $text");
  flog("qtype= $qtype");
  flog("ord= $ord");
  flog("sort= $sort");
  
  $total = 0;
   
  $page =  intval(($_GET['page'] ?? ''));
  if ($page == "" ) {
    $page = 1;
  }
  
  $stack = array();

  $q = "SELECT Regions.gateway as AGateway, Name, Description, Location, Regions.Regionname as Regioname, Regions.gateway as gateway, Objects.Location as landingpoint FROM Objects
    left  JOIN Regions ON Objects.regionuuid = Regions.regionuuid    
            where  Name <> ''           
            and " . $qtype . "  like CONCAT('%', :text1, '%')
            order by " . $sort . ' ' .  $ord ;
    

    flog($q );  
    $query = $db->prepare($q);
    $result = $query->execute($sqldata);
    flog($sqldata );  
    class OUT { public $domain; public $port; public $page; public $total; public $rows; }
    class Row { public $cell; }
    
    $out = new OUT();
    $counter= 0;
    while ($row = $query->fetch(PDO::FETCH_ASSOC))
    {
      
        $location = $row["Location"];
        
        
        $gateway = str_replace (':', '|', $row["AGateway"] );
        $regionname = str_replace(' ','+',$row["Regioname"]);
        
        
        $hop    = "hop://" . $row["gateway"]  . '/' . $row["landingpoint"];
        $v3     = "secondlife://http|!!" . $gateway  .  '+' . $regionname. '/' . $row["landingpoint"];
        $hg     = "secondlife://" . $row["gateway"]  . '/' . $row["landingpoint"];
            
        
        $link = "<a href=\"$hop\"><img src=\"hop.png\" height=\"24\"></a>";
        #$link = "<a href=\"$v3\"><img src=\"v3hg2.png\" height=\"24\"></a>";
        #$link .= "<br><a href=\"$hg\"><img src=\"hg.png\" height=\"24\"></a>";

        
        $name = wordwrap($row["Name"],35, "<br>\n", false);
        
        $row = array("hop"=>$link ,
                     "Name"=>$name,
                     "Description"=>$row["Description"],
                     "Regionname"=>$row["Regioname"]. "<br>Link: <br><a href=\"$v3\">" . $row["AGateway"] . '/' . $location . '</a>',
                     "Location"=>$location);
        
        $rowobj = new Row();
        $rowobj->cell = $row;
        
        #$myJSON = json_encode($rowobj);
        #echo $myJSON . "<br>";
        if ($total >= (($page-1) *$rc) && $total < ($page) *$rc) {
          array_push($stack, $rowobj);
        }
        $total++;
        
    }
    if ($total == 0) {
       $total = 1;
	  flog("Nothing found");
      $row = array("hop"=>"", "Name"=>"No records","Description"=>"","Regionname"=>"","Location"=>"");
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
