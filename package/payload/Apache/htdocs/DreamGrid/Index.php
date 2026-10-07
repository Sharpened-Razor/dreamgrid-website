<html>
 <head>
  <title>Welcome to DreamGrid</title>
  <link rel="shortcut icon" href="/favicon.ico">
</head>
<body>
<?php
  include("../Metromap/includes/config.php");
?>
Log in to <a href="<?php echo $CONF_sim_domain.":".$CONF_port ?>">DreamGrid</a>
| <a href="/API">API HOME</a>
| <a href="/VR">360 View</a>
| <a href="/Video">Video</a>
| <a href="/Audio">Audio</a>
| <a href="/PerlExample">Perl Examples</a>
| <a href="/Stats">Stats</a>
| <a href="http://outworldz.com/Search" >Search</a>
| <a href="http://outworldz.com/DestinationGuide" >Destinations</a> |</p>

<p>
<?php include(__DIR__ . "/../Metromap/map.php"); ?>
</p>
<p>
 DreamGrid is a <a href="https://www.outworldz.com/Outworldz_installer/">free Opensimulator server</a> powered by <a href="https://www.Outworldz.com">Outworldz.com</a>
</p>

 </body>
</html>
