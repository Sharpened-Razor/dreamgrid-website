<?php
  // AGPL 3.0 by Fred Beckhusen
  
    header("content-type: text/html; charset=UTF-8");    

    require( "flog.php" );
    include("database.php");
     

    $text = ($_GET['query'] ?? '');     
    $sqldata['text1'] = $text;

    $rc = intval(($_GET['rp'] ?? '') )  ;
    
    if ($rc == "") {
      $rc = 100;
    }    
    
    $sort = ($_GET['sortname'] ?? '');
    
    
    if ($sort == 'Grid') {
        $sort = 'url';
    } else if ($sort == 'RegionName') {
        $sort = 'regionname';
    } else {
        $sort = 'owner';
    }
        
    $ord = ($_GET['sortorder'] ?? '')   ;
    if ($ord == 'asc') {
        $ord = 'asc';
    } else {
        $ord = 'desc';
    }
    
    $qtype = ($_GET['qtype'] ?? '');
    if ($qtype == 'Grid') {
        $qtype = 'url';
    } else if ($qtype == 'Regionname') {
        $qtype = 'regionname';
    } else if ($qtype == 'Owner') {
        $qtype = 'owner';
    } else {
        $qtype = 'url';
    }
    flog('qtype:' . $qtype);
    
    $total = 0;
    
    $page =  intval(($_GET['page'] ?? ''));
    if ($page == "" ) {
        $page = 1;
    }
    
    flog("text= $text");
    flog("qtype= $qtype");
    flog("ord= $ord");
    flog("sort= $sort");
    
    
    $stack = array();
    
    class OUT { public $domain; public $port; public $page; public $total; public $rows; }
    class Row { public $cell; }
  
    $out = new OUT();

    $counter = 0;
    
    $query = "SELECT * FROM regions  where " .  $qtype  . " like  CONCAT('%', :text1, '%')  
            order by " .  $sort . " " . " $ord";
    
   
    flog ($query);
    
    $query = $db->prepare($query);
    flog($sqldata);

    $result = $query->execute($sqldata);

    while ($row = $query->fetch(PDO::FETCH_ASSOC))
    {
      
        //flog($row["gateway"]);
      
        $gateway = str_replace (':', '|', $row["gateway"] );
        $regionname = str_replace(' ','+',$row["regionname"]);
        
        $hop    = "hop://" . $row["gateway"]  . '/' . $row["landingpoint"];
        $v3     = "secondlife://http|!!" . $gateway  .  '+' . $regionname. '/' . $row["landingpoint"];
        $hg     = "secondlife://" . $row["gateway"]  . '/' . $row["landingpoint"];
            
        
        $link = "<a href=\"$hop\"><img src=\"hop.png\" height=\"24\"></a>";
        #$link = "<a href=\"$v3\"><img src=\"v3hg2.png\" height=\"24\"></a>";
        #$link .= "<br><a href=\"$hg\"><img src=\"hg.png\" height=\"24\"></a>";
        
        $row = array("hop"=>$link,
                     "Grid"         =>$row["gateway"],
                     "RegionName"   => $row["regionname"],                     
                     "Owner"=>$row["owner"]
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
        $row = array("hop"=>"","Grid"=>"","RegionName"=>"No records","Owner"=>"");
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
  
    