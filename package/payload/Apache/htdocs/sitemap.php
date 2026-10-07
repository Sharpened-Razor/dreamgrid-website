<?php
require_once __DIR__.'/Other/core/dreamgrid-env.php';
header('Content-Type: application/xml; charset=UTF-8');
$scheme=ag_web_secure_request()?'https':'http';
$port=(int)($_SERVER['SERVER_PORT']??ag_dg_apache_port());
$base=$scheme.'://'.ag_web_authority(ag_dg_hostname(),$port);
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
foreach(array('/','/Other/') as $route){
    echo '<url><loc>'.htmlspecialchars($base.$route,ENT_XML1|ENT_QUOTES,'UTF-8').'</loc><changefreq>daily</changefreq></url>'."\n";
}
echo '</urlset>';
