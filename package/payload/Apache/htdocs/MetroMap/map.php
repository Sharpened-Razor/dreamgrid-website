<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<base href="/Metromap/">
<link id="main" rel="stylesheet" href="w3.css" type="text/css" media="screen"/>
</head>
    <title>Region Map</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="w3-theme-blue-grey.css">
      <link rel="shortcut icon" href="/favicon.ico">
</head>

<body>
<div class="w3-card-4">
<div class="w3-container w3-theme w3-card-2">
  <h1>Regions Map</h1>
</div>

<!--
 * Copyright (c) Metropolis Metaversum [ http://hypergrid.org ]
 *
 * The MetroTools are BSD-licensed. For more informations about BSD-licensed
 * Software use this link: http://www.wikipedia.org/BSD-License
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Metropolis Project nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
-->

<?php
// ------------------------------------------------------------------------------------
// Change this items to your Language
// ------------------------------------------------------------------------------------

  $CONF_txt_north =      "North";
  $CONF_txt_south =      "South";
  $CONF_txt_west =       "West";
  $CONF_txt_east =       "East";
  $CONF_txt_center =     "Center";
  $CONF_txt_refresh =    "Refresh";
  $CONF_txt_occupied =   "Occupied";
  $CONF_txt_free =       "Unused";
  $CONF_txt_coords =     "Coordinate";
// Konfiguration einbinden
  include(__DIR__ . "/includes/runtime-config.php");

// Daten aus der Konfiguration
  $dbort = $CONF_db_server;
  $dbuser = $CONF_db_user;
  $dbpw = $CONF_db_pass;
  $dbdb = $CONF_db_database;
  $dbport = $CONF_db_port;

  $grid_x = 0;
  $grid_y = 0;

  if (isset($_POST['x']) && ($_POST['y']))
  {
	$grid_x = $_POST['x'];
	$grid_y = $_POST['y'];
  }
  else
  {
	if (isset($_GET['x']) && ($_GET['y']))
	{
	  $grid_x = $_GET['x'];
	  $grid_y = $_GET['y'];
	}
  }

  if ($grid_x == 0) {$grid_x = $CONF_center_coord_x;}
  if ($grid_y == 0) {$grid_y = $CONF_center_coord_y;}
  if ($grid_y <= 30) {$grid_y = $CONF_center_coord_y;}
  if ($grid_x <= 30) {$grid_x = $CONF_center_coord_x;}
  if ($grid_x >=99999) {$grid_x = $CONF_center_coord_x;}
  if ($grid_y >=99999) {$grid_y = $CONF_center_coord_y;}

  // Zoom level: the grid is always square (same number of columns and rows) and
  // the grid's total width stays fixed; zooming in shows fewer coordinates so
  // each cell gets bigger, zooming out shows more coordinates so each cell shrinks
  // to still fit that width. [left, right, up, down] offsets from the center.
  $zoom_levels = array(
      1 => array(15, 15, 15, 15),
      2 => array(11, 11, 11, 11),
      3 => array(8,  7,  8,  7),
      4 => array(5,  5,  5,  5),
      5 => array(4,  3,  4,  3),
  );

  $grid_total_width = 1536;

  $zoom = 3;
  if (isset($_POST['zoom'])) { $zoom = (int)$_POST['zoom']; }
  else if (isset($_GET['zoom'])) { $zoom = (int)$_GET['zoom']; }
  if (!array_key_exists($zoom, $zoom_levels)) { $zoom = 3; }

  list($zoom_left, $zoom_right, $zoom_up, $zoom_down) = $zoom_levels[$zoom];

  $zoom_columns = $zoom_left + $zoom_right + 1;
  $tile_px = intdiv($grid_total_width, $zoom_columns);

  $start_x = $grid_x - $zoom_left;
  $start_y = $grid_y + $zoom_up;

  $end_x = $grid_x + $zoom_right;
  $end_y = $grid_y - $zoom_down;

// Datenbank anzapfen
$con = mysqli_connect($dbort,$dbuser,$dbpw,$dbdb,$dbport);

   // Datenbank abfragen
$z=mysqli_query($con,"SELECT uuid,regionName,locX,locY,serverURI,sizeX,sizeY,owner_uuid FROM regions") or die("Error: " . mysqli_error($con));

/* Datenbank prüfen */
if (mysqli_connect_errno())
{
    printf("Database Fail: %s\n", mysqli_connect_error());
    exit();
}

 $xx = 0; # otherwise $region[$xx] will go to "" as null is a string

  while($region=mysqli_fetch_array($z))
  {

	if ($region['sizeX'] == 0) {$region['sizeX'] = 256; }
	if ($region['sizeY'] == 0) {$region['sizeY'] = 256; }

   if ((($region['sizeX'] == 256) && ($region['sizeY'] == 256)) || (($region['sizeX'] == 256) && ($region['sizeY'] == 0)))
      {
		$work_reg = $region['uuid'].";".$region['regionName'].";".$region['locX'].";".$region['locY'].";".$region['serverURI'].";".$region['sizeX'].";".$region['sizeY'].";".$region['owner_uuid'].";SingleRegion";
		$region_sg[$xx] = $work_reg;
		$xx++;
      }
      else

// **********************************************************

      {
         $varreg_locx = ($region['locX'] / 256);
         $varreg_locy = ($region['locY'] / 256);
         $varreg_start_x = $varreg_locx;
         $varreg_start_y = $varreg_locy;
         $varreg_end_x = $varreg_locx + (($region['sizeX'] / 256) - 1);
         $varreg_end_y = $varreg_locy + (($region['sizeY'] / 256) - 1);

         $varreg_work_x = $varreg_start_x;
         $varreg_work_y = $varreg_start_y;

         while (($varreg_work_y <= $varreg_end_y)&& ($varreg_work_x <= $varreg_end_x))
         {
           $varreg_key = $varreg_work_x."-".$varreg_work_y;

			$work_reg = $region['uuid'].";".$region['regionName'].";".$varreg_work_x.";".$varreg_work_y.";".$region['serverURI'].";".$region['sizeX'].";".$region['sizeY'].";".$region['owner_uuid'].";VarRegion";

	      $region_sg[$xx] = $work_reg;
	      $xx++;

         if (($varreg_work_y == $varreg_end_y)&& ($varreg_work_x == $varreg_end_x))
            {}

         if ($varreg_work_y == $varreg_end_y)
             {
               $varreg_work_y = $varreg_start_y;
               $varreg_work_x++;
             }
               else
             {
               $varreg_work_y++;
             }
         }
       }

// ********************************************************
  }
?>

<!-- Koordinaten Pfeile -->

 <table border=0 cellpadding=0 cellspacing=0>
 <tr>
   <td valign=top align=center>
     <br>
     <img src ="./img/spacer.gif" width="15">

   </td>
   <td valign=top align=center>
     <br>
     <table width=100 height=100 cellspacing=0 cellpadding=0 border=0>
        <tr>
          <td align=center>
            <center>
             <table border=0 cellpadding=0 cellspacing=0>
                <tr align=center>
                  <td><img src = ./img/spacer.gif></td>
                  <td><a href="map.php?x=<?php  echo $grid_x;?>&y=<?php  echo $grid_y + 1; ?>&zoom=<?php print $zoom; ?>" target=_self><img src=./img/oben.png width="50" height="50" border=0 alt="<?php  echo $CONF_txt_north;?>" title="<?php  echo $CONF_txt_north;?>"></a></td>
                  <td><img src = ./img/spacer.gif></td>
               </tr>
               <tr>
                  <td><a href="map.php?x=<?php  print $grid_x - 1; ?>&y=<?php  print $grid_y; ?>&zoom=<?php print $zoom; ?>" target=_self><img src=./img/links.png width="50" height="50" border=0 alt="<?php  echo $CONF_txt_west;?>" title="<?php  echo $CONF_txt_west;?>"></a></td>
                  <td><a href="map.php?x=<?php  echo $CONF_center_coord_x;?>&y=<?php  echo $CONF_center_coord_y;?>&zoom=<?php print $zoom; ?>" target=_self><img src=./img/home.png width="50" height="50" border=0 alt="<?php  echo $CONF_txt_center;?>" title="<?php  echo $CONF_txt_center;?>"></a></td>
                  <td><a href="map.php?x=<?php  print $grid_x + 1; ?>&y=<?php  print $grid_y; ?>&zoom=<?php print $zoom; ?>" target=_self><img src=./img/rechts.png width="50" height="50" border=0 alt="<?php  echo $CONF_txt_east;?>" title="<?php  echo $CONF_txt_east;?>"></a></td>

			   </tr>
               <tr>
                  <td><img src = ./img/spacer.gif></td>
                  <td><a href="map.php?x=<?php  print $grid_x; ?>&y=<?php  print $grid_y - 1; ?>&zoom=<?php print $zoom; ?>" target=_self><img src=./img/unten.png width="50" height="50" border=0 alt="<?php  echo $CONF_txt_south;?>" title="<?php  echo $CONF_txt_south;?>"></a></td>
                  <td><img src = ./img/spacer.gif></td>
              </tr>
          </table>
        </td>
     </tr>
  </table>

  <!-- Koordinaten eingabe -->

  <br><br>
  <table class="w3-table">
    <tr>
       <td align=center valign=middle>
          <center><b><br><font color=w3-dark-grey><?php  echo $CONF_txt_coords;?></b><br><hr width=40%>
          <table class="w3-table">
             <tr>
               <td align=right valign=middle border=0 width=40%>
                   <form name="submit" action="map.php" method="post">
                   <font color=w3-dark-grey><b>X:</b></font>
                   <img src= ./img/spacer.gif></td>
               <td width=60% valign=middle align=left border=1 >
                   <input type="hidden" name="zoom" value="<?php print $zoom; ?>">
                   <input type="text" value="<?php print $grid_x;?>" name="x" size=4></td>
             </tr>
             <tr>
               <td align=right border=0 valign=middle width=40%>
                   <font color=w3-dark-grey><b>Y:</b></font>
                   <img src= ./img/spacer.gif></td><td width=60% valign=middle border=1 align=left>
                   <input type="text" name="y" size=4 value="<?php print $grid_y;?>"></td></font>
             </tr>
             <tr>
               <td colspan=2 align=center>
                   <br>

				   <button class="w3-btn-block w3-blue-grey" type="submit" name="submit" value="Installer">Search</button>

                   <br><br>
                   </form>

                   <a href="map.php?x=<?php print $grid_x; ?>&y=<?php print $grid_y; ?>&zoom=<?php print min(5, $zoom + 1); ?>"><button class="w3-btn-block w3-blue-grey" type="button">Zoom In</button></a>
                   <br><br>
                   <a href="map.php?x=<?php print $grid_x; ?>&y=<?php print $grid_y; ?>&zoom=<?php print max(1, $zoom - 1); ?>"><button class="w3-btn-block w3-blue-grey" type="button">Zoom Out</button></a>

                   <br><br>
                   </b></font></td>
             </tr>
          </table>
        </td>
      </tr>
    </table></td>

   <td><img src=./img/spacer.gif width=10 border=0></td>
   <td valign=top>
   <br>
     <table bordercolor="#333333" cellpadding=0 cellspacing=0 border=1 style="border-collapse:collapse;">

<?php
  $y = $start_y;
  $x = $start_x;

  while ($y >= $end_y)
    {
       $x = $start_x;
       ?>
       </td></tr><tr valign=middle><td valign=middle>
       <div class="w3-opacity">
       <?php
       if ($y <> $start_y)
           {
            echo $y;
           }
?>

       </div>

<?php
         while ($x <= $end_x)
         {
         if ($y == $start_y)
          {
          ?>
          </td><td align=center><div class="w3-opacity">
          <?php
            $xs="a";
            $xs=$x;
            $z1=""; $z2=""; $z3=""; $z4=""; $z5=""; $z6="";
            $z1= substr($xs,'0','1');
            $z2= substr($xs,'1','1');
            $z3= substr($xs,'2','1');
            $z4= substr($xs,'3','1');
            $z5= substr($xs,'4','1');
            $z6= substr($xs,'5','1');

            if ($z1) {print $z1;} else {print "<br>0";}
            if ($z2) {print $z2;} else {if ($x >= 10) {print "0";}}
            if ($z3) {print "<br>".$z3;} else {if ($x >= 100) {print "<br>0";}}
            if ($z4) {print $z4;} else {if ($x >= 1000) {print "0";}}
            if ($z5) {print "<br>".$z5;} else {if ($x >= 10000) {print "<br>0";}}
            if ($z6) {print $z6;} else {if ($x >= 100000) {print "0";}}

?>

          </div>

<?php
          $x++;
          }
          else
          {
              $count = count($region_sg);
              for ($q = 0; $q < $count; $q++)
                 {
                    $region_value = $region_sg[$q];
                    $sim_new = 0;
                    @list($region_uuid, $region_name, $region_locx, $region_locy, $region_serverip, $region_sizex, $region_sizey, $region_owner, $region_type) = @explode(";",$region_value);

                      if ($region_sizey == 0) { $region_sizey = 256; }

                      if ($region_locx >= 100000)
                      {
                         $region_locx = $region_locx / 256;
                         $region_locy = $region_locy / 256;
                      }

                      if (($region_locx == $x) && ($region_locy == $y))
                      { $sim_new = 1; break;}
                 }

              $owner_stmt = mysqli_prepare($con, "SELECT FirstName, LastName FROM UserAccounts where PrincipalID=?") or die("Error: " . mysqli_error($con));
              mysqli_stmt_bind_param($owner_stmt, "s", $region_owner);
              mysqli_stmt_execute($owner_stmt);
              $zq = mysqli_stmt_get_result($owner_stmt);

              $owner=mysqli_fetch_array($zq);
              $firstname = $owner['FirstName'];
              $lastname = $owner['LastName'];
              mysqli_stmt_close($owner_stmt);

              if ($sim_new == 1)
              {

              if (($x == $CONF_center_coord_x) && ($y == $CONF_center_coord_y))
              {
              $reg_colour = "./img/grid_mainland.png";

              $maptile_url = "./maptiles/00000000-0000-0000-0000-000000000000/map-1-$x-$y-objects.jpg";
              $maptile_path = __DIR__ . "/../../../Opensim/bin/maptiles/00000000-0000-0000-0000-000000000000/map-1-$x-$y-objects.jpg";
              if (file_exists($maptile_path)) { $reg_colour = $maptile_url; }

              $region_dimension = ($region_sizex / 256)." x ".($region_sizey / 256)." Regions";
              $region_total_size = $region_sizex * $region_sizey;
              $region_total_size = number_format($region_total_size, 0, ",", ".")." sqm";

?>

</td><td><A href="hop://<?php echo $CONF_domain.":".$CONF_port."/".$region_name; ?>"   style="cursor:pointer"><img src="<?php print $reg_colour?>" width="<?php print $tile_px; ?>" height="<?php print $tile_px; ?>" style="object-fit:cover;" alt= " RegionName: <?php  print $region_name; ?> &#10 RegionType: <?php print $region_type; ?> &#10&#10 Dimension: <?php print $region_dimension ?> &#10 Total size: <?php print $region_totalsize ?> &#10&#10 X-Coordinate: <?php print $x; ?> &#10 Y-Coordinate: <?php print $y; ?> &#10&#10 Status: <?php  echo $CONF_txt_occupied;?> &#10 Owner: <?php echo $firstname.' '.$lastname;?>" title= " RegionName: <?php  print $region_name; ?> &#10 RegionType: <?php print $region_type; ?> &#10&#10 Dimension: <?php print $region_dimension ?> &#10 Total size: <?php print $region_totalsize ?> &#10&#10 X-Coordinate: <?php print $x; ?> &#10 Y-Coordinate: <?php print $y; ?> &#10 Owner: <?php  echo $CONF_txt_occupied;?> &#10 Owner: <?php  echo $firstname.' '.$lastname;?>"></A><?php
                 $x++;

              }
              else
              {

              if ($region_type == "SingleRegion")
                 {$reg_colour = "./img/grid_besetzt.png";}
              if ($region_type == "VarRegion")
                 {$reg_colour = "./img/grid_varregion.png";}

              $maptile_url = "./maptiles/00000000-0000-0000-0000-000000000000/map-1-$x-$y-objects.jpg";
              $maptile_path = __DIR__ . "/../../../Opensim/bin/maptiles/00000000-0000-0000-0000-000000000000/map-1-$x-$y-objects.jpg";
              if (file_exists($maptile_path)) { $reg_colour = $maptile_url; }

              $region_dimension = ($region_sizex / 256)." x ".($region_sizey / 256)." Regions";
              $region_totalsize = $region_sizex * $region_sizey;
              $region_totalsize = number_format($region_totalsize, 0, ",", ".")." sqm";

?>

</td><td><A href="hop://<?php echo $CONF_domain.":".$CONF_port."/".$region_name; ?>"  style="cursor:pointer" "><img src="<?php print $reg_colour?>" width="<?php print $tile_px; ?>" height="<?php print $tile_px; ?>" style="object-fit:cover;" alt= " RegionName: <?php  print $region_name; ?> &#10 RegionType: <?php print $region_type; ?> &#10 Dimension: <?php print $region_dimension ?> &#10 Total size: <?php print $region_totalsize ?> &#10 X-Coordinate: <?php print $x; ?> &#10 Y-Coordinate: <?php print $y; ?> &#10 Status: OCCUPIED  &#10 Owner: <?php echo $firstname.' '.$lastname;?>" title = " RegionName: <?php  print $region_name; ?> &#10 RegionType: <?php print $region_type;?> &#10 Dimension: <?php print $region_dimension ?> &#10 Total size: <?php print $region_totalsize ?> &#10 X-Coordinate: <?php print $x; ?> &#10 Y-Coordinate: <?php print $y; ?> &#10 Status: OCCUPIED &#10 Owner: <?php  echo $firstname.' '.$lastname;?>"</A>

<?php
                         $x++;
                      }}

                else

                      {
?>

                         </td><td style="background-color:#1E465F; width:<?php print $tile_px; ?>px; height:<?php print $tile_px; ?>px; padding:0; margin:0;"
                         title=" X-Coordinate: <?php print $x; ?> &#10 Y-Coordinate: <?php print $y; ?> &#10 Status: <?php  echo $CONF_txt_free;?>">
                         <?php
                         $x++;

                   }
                }
             }

   $y--;

 }

?>

         </td>
        </tr>
      </table>
     </td>
     <td> <img src ="./img/spacer.gif" width="15"></td>
    </tr>
   </table>

<img src = "./img/spacer.gif" width="350" height="1">
<img src="./img/grid_frei.png"> = Free Coordinate&nbsp;&nbsp;&nbsp;
<img src="./img/grid_besetzt.png"> = Occupied(Single Region) &nbsp;&nbsp;&nbsp;
<img src="./img/grid_varregion.png"> = Occupied (Var Region) &nbsp;&nbsp;&nbsp;
<img src="./img/grid_mainland.png"> = Center of the grid&nbsp;&nbsp;&nbsp;

</div>
</body>
</html>
