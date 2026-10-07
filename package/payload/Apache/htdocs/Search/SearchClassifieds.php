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
  
  $page =  ($_GET['page'] ?? '');
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
   
  $query = "SELECT * FROM classifieds
          INNER JOIN hostsregister ON classifieds.gateway = hostsregister.gateway            
          where
          failcounter = 0
          and classifieds.gateway not like '192.168%'
          and classifieds.gateway not like '172.16%'
          and classifieds.gateway not like '172.17%'
          and classifieds.gateway not like '172.18%'
          and classifieds.gateway not like '172.19%'
          and classifieds.gateway not like '172.20%'
          and classifieds.gateway not like '172.21%'
          and classifieds.gateway not like '172.22%'
          and classifieds.gateway not like '172.23%'
          and classifieds.gateway not like '172.24%'
          and classifieds.gateway not like '172.25%'
          and classifieds.gateway not like '172.26%'
          and classifieds.gateway not like '172.27%'
          and classifieds.gateway not like '172.28%'
          and classifieds.gateway not like '172.29%'
          and classifieds.gateway not like '172.30%'
          and classifieds.gateway not like '172.31%'            
          and classifieds.gateway <> 'http://127.0.0.1'
          and classifieds.gateway not like '10.%'
  
  ";
  
  flog($query);
  
  $sqldata = array();
  
  $query = $db->prepare($query);
  $result = $query->execute($sqldata);
  $counter = 0;
    
  while ($row = $query->fetch(PDO::FETCH_ASSOC))
  {
    
     $v3    = "hop://" . $row["gateway"] ;     
    $local = "secondlife:///app//teleport/" . $row["gateway"] ;     
      
    $link = "<a href=\"$v3\"><img src=\"v3hg2.png\" height=\"24\"></a><br>
<a href=\"$local\"><img src=\"local.png\" height=\"24\"></a>";
  
  
      flog("Name:". $row["name"]);
            
      
      if ($row["simname"] == '')
      {
          $simname = '';
      }else{
          $simname = $row["simname"];
      }
      if ($row["expirationdate"] == '')
      {
          $date = '';
      }else{
          $date = $row["expirationdate"];
      }
      
      if ($row["category"] == '' )
      {
          $category = '';
      } else {
          $category = $row["category"];
      }
      
      $row = array("hop"=>$link,
                   "Grid"=>$link,
                    //$row["classifieduuid"],
                     //$row["creatoruuid"],
                     //$row["creationdate"],
                    "ExpirationDate"     => $row["expirationdate"],
                    "Category"           => $category,
                    "Name"               => $name,
                    "Description"        => $description,
                    //$row["parceluuid"],
                    //$row["parentestate"],
                    //$row["snapshotuuid"],
                    "Region"             => $simname,
                    //$row["posglobal"],
                    //$row["parcelname"],
                    //$row["classifiedflags"],
                    //$row["priceforlisting"],
               );
            
      $rowobj = new Row();
      $rowobj->cell = $row;
          
      if ($total >= (($page-1) *$rc) && $total < ($page) *$rc) {
        array_push($stack, $rowobj);
      }
      
      $total++;
  }
  
  if ($total == 0) {
      flog("Nothing found");
      $row = array("Grid"=>"No records");
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
  
                 
