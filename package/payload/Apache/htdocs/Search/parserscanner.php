<?php


function startsWith ($string, $startString) 
{ 
    $len = strlen($startString); 
    return (substr($string, 0, $len) === $startString); 
}

function GetURL($host, $port, $url, $header)
{    
        
    $url = "http://$host:$port/$url";
    flog("Checking $url\n");
    echo "Checking $url\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    if ($header) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml+rpc'));
    }
    

    $data = curl_exec($ch);
    if (curl_errno($ch) == 0)
    {
        curl_close($ch);
        echo "Got Data\n";
        return $data;
    }

    curl_close($ch);
    echo "No Data\n";
    return "";
}
function Delete ($gateway, $hostname)  {
  
  return;

    flog ("Deleting $gateway");
    echo "** Deleting $hostname\n\n\n";
    
    //$query = $GLOBALS['db']->prepare("UPDATE hostsregister set online = 0 WHERE host = ?");
    //$query->execute( array($hostname) );
    //echo "** Offline $hostname\n\n\n";
    //    
    $query = $GLOBALS['db1']->prepare("DELETE FROM objects  WHERE gateway = ?");
    $query->execute( array($gateway) );
    flog ("Deleted objects");

    $query = $GLOBALS['db1']->prepare("DELETE FROM allparcels  WHERE gateway = ?");
    $query->execute( array($gateway) );
    flog ("Deleted allparcels");
    $query = $GLOBALS['db1']->prepare("DELETE FROM parcels  WHERE gateway = ?");
    $query->execute( array($gateway) );
    flog ("Deleted parcels");
    $query = $GLOBALS['db1']->prepare("DELETE FROM parcelsales  WHERE gateway = ?");
    $query->execute( array($gateway) );
    flog ("Deleted Parcelsaves");
    $query = $GLOBALS['db1']->prepare("DELETE FROM popularplaces  WHERE gateway = ?");
    $query->execute( array($gateway) );
    flog ("Deleted Popularplaces");
    $query = $GLOBALS['db1']->prepare("DELETE FROM regions  WHERE gateway = ?");
    $query->execute( array($gateway) );
    flog ("Deleted regions");
}

function CheckHost($gateway, $hostname, $port)
{
    global $db, $now;
    
    $xml = GetURL($hostname, $port, "?method=collector",0);
    flog($xml);
    $online = 0;
    if ($xml == "") {          
        echo "$hostname failed - set to offline\n";
        flog("failed");
        $failcounter = 'failcounter + 1';
        Delete($gateway,$hostname);
        
    } else {
        flog("$hostname success");
        echo "$hostname success\n";
        $failcounter = "0";
        $online = 1;
    }

    //Update nextcheck to be 10 minutes from now. The current OS instance
    //won't be checked again until at least this much time has gone by.
    $next = $now + 600;

    $query = $db->prepare("UPDATE hostsregister SET nextcheck = ?," .
                          " online = ?," . 
                          " checked = checked + 1, failcounter = $failcounter" .
                          " WHERE host = ? AND port = ?");
    $query->execute( array($next, $online, $hostname, $port) );

    if ($xml != "") {        
        parse($gateway, $hostname, $port, $xml);
    }
}

function HandleXmlError($errno, $errstr, $errfile, $errline)
{
    if ($errno==E_WARNING && (substr_count($errstr,"DOMDocument::loadXML()")>0))
    {
        //echo "XML Error\n";
    }
    else
        return false;
}

function parse($gateway,$hostname, $port, $xml)
{
    global $db, $now;
       
    $objDOM = new DOMDocument();
    $objDOM->resolveExternals = false;

    set_error_handler('HandleXmlError');
    
    //Don't try and parse if XML is invalid or we got an HTML 404 error.
    if ($objDOM->loadXML($xml,LIBXML_PARSEHUGE) == False) {
        echo "No XML\n";
        restore_error_handler();
        Delete($gateway,$hostname);    
        return;
    }
    
    restore_error_handler();
    //
    // Get the region data to update
    //
    $regiondata = $objDOM->getElementsByTagName("regiondata");

    //If returned length is 0, collector method may have returned an error
    if ($regiondata->length == 0) {
        echo "No regiondata in XML\n";
        Delete($gateway,$hostname);        
        return;
    }

    $regiondata = $regiondata->item(0);
    
    //
    // Update nextcheck so this host entry won't be checked again until after
    // the DataSnapshot module has generated a new set of data to be parsed.
    //
    $expire = $regiondata->getElementsByTagName("expire")->item(0)->nodeValue;
    $next = $now + $expire;

    $query = $db->prepare("UPDATE hostsregister SET nextcheck = ? WHERE host = ? AND port = ?");
    $query->execute( array($next, $hostname, $port) );

    //
    // Get the region data to be saved in the database
    //
    $regionlist = $regiondata->getElementsByTagName("region");

    foreach ($regionlist as $region)
    {
        $regioncategory = $region->getAttributeNode("category")->nodeValue;

        //
        // Start reading the Region info
        //
        $info = $region->getElementsByTagName("info")->item(0);
        $regionuuid = $info->getElementsByTagName("uuid")->item(0)->nodeValue ;
        $regionname = $info->getElementsByTagName("name")->item(0)->nodeValue;
        $regionhandle = $info->getElementsByTagName("handle")->item(0)->nodeValue;
        $url = $info->getElementsByTagName("url")->item(0)->nodeValue;
        $data = $region->getElementsByTagName("data")->item(0);
        $estate = $data->getElementsByTagName("estate")->item(0);
        $username = $estate->getElementsByTagName("name")->item(0)->nodeValue;        
        $useruuid = $estate->getElementsByTagName("uuid")->item(0)->nodeValue|| "00000000-00000000-00000000-00000000";
        $estateid = $estate->getElementsByTagName("id")->item(0)->nodeValue;        
        
        
        $query = $db->prepare("DELETE FROM regions WHERE regionUUID = ?");
        $query->execute( array($regionuuid) );
            
        echo "Insert region: $regionname\n";
        $query = $db->prepare("INSERT INTO regions (regionname, regionUUID, regionhandle, url, owner, owneruuid, gateway )
                              VALUES(:r_name, :r_uuid, :r_handle, :url, :u_name, :u_uuid, :r_gateway)");
        
        $query->execute( array( "r_name" => $regionname,
                                "r_uuid" => $regionuuid,
                                "r_handle" => $regionhandle,
                                "url" => $url,
                                "u_name" => $username,
                                "u_uuid" => $useruuid,
                                "r_gateway" => $gateway
                              ));

        //
        // Start reading the parcel info
        //
        $parcel = $data->getElementsByTagName("parcel");

        foreach ($parcel as $value)
        {
                
            $parcelname = $value->getElementsByTagName("name")->item(0)->nodeValue;            
            echo "Insert parcel: $parcelname\n";

            $parceluuid = $value->getElementsByTagName("uuid")->item(0)->nodeValue;
            $infouuid = $value->getElementsByTagName("infouuid")->item(0)->nodeValue;
            $parcellanding = $value->getElementsByTagName("location")->item(0)->nodeValue;
            $parceldescription = $value->getElementsByTagName("description")->item(0)->nodeValue;
            $parcelarea = $value->getElementsByTagName("area")->item(0)->nodeValue;
            $parcelcategory = $value->getAttributeNode("category")->nodeValue;
            $parcelsaleprice = $value->getAttributeNode("salesprice")->nodeValue;
            $dwell = $value->getElementsByTagName("dwell")->item(0)->nodeValue;

            //The image tag will only exist if the parcel has a snapshot image
            $has_pic = 0;
            $image_node = $value->getElementsByTagName("image");

            if ($image_node->length > 0)
            {
                $image = $image_node->item(0)->nodeValue;

                if ($image != "00000000-0000-0000-0000-000000000000")
                    $has_pic = 1;
            }

            $owner = $value->getElementsByTagName("owner")->item(0);
            $owneruuid = $owner->getElementsByTagName("uuid")->item(0)->nodeValue;

            // Adding support for groups
            $group = $value->getElementsByTagName("group")->item(0);

            if ($group != "")
            {
                $groupuuid = $group->getElementsByTagName("groupuuid")->item(0)->nodeValue;
            }
            else
            {
                $groupuuid = "00000000-0000-0000-0000-000000000000";
            }

            //
            // Check bits on Public, Build, Script
            //
            $parcelforsale = $value->getAttributeNode("forsale")->nodeValue;
            $parceldirectory = $value->getAttributeNode("showinsearch")->nodeValue;
            $parcelbuild = $value->getAttributeNode("build")->nodeValue;
            $parcelscript = $value->getAttributeNode("scripts")->nodeValue;
            $parcelpublic = $value->getAttributeNode("public")->nodeValue;
         
            $query = $db->prepare("DELETE FROM allparcels WHERE regionUUID = ?");
            $query->execute( array($regionuuid) );
            $query = $db->prepare("DELETE FROM allparcels WHERE parceluuid = ?");
            $query->execute( array($parceluuid) );
            
            echo "Insert into all parcels: $parcelname\n";                    
            
            $query = $db->prepare("INSERT INTO allparcels (regionUUID, parcelname, ownerUUID, groupUUID, landingpoint, parcelUUID, infoUUID, parcelarea, gateway )  VALUES(" .
                                    ":r_uuid, :p_name, :o_uuid, :g_uuid, " .
                                    ":landing, :p_uuid, :i_uuid, :area, :r_gateway )");
            $query->execute( array(
                                   "r_uuid"  => $regionuuid,
                                   "p_name"  => $parcelname,
                                   "o_uuid"  => $owneruuid,
                                   "g_uuid"  => $groupuuid,
                                   "landing" => $parcellanding,
                                   "p_uuid"  => $parceluuid,
                                   "i_uuid"  => $infouuid,
                                   "area"    => $parcelarea,
                                   "r_gateway" => $gateway
                                  ) );
            
            
            if ($parceldirectory == "true")
            {
                
                $query = $db->prepare("DELETE FROM parcels WHERE regionUUID = ?");
                $query->execute( array($regionuuid) );
                
                echo "Insert parcels: $parcelname\n";
                $query = $db->prepare("INSERT INTO parcels (regionuuid, parcelname, parceluuid, landingpoint,
                                      description, searchcategory, build, script, public, dwell, infouuid, mature, gateway) VALUES(" .
                                       ":r_uuid, :p_name, :p_uuid, :landing, " .
                                       ":desc, :cat, :build, :script, :public, ".
                                       ":dwell, :i_uuid, :r_cat, :r_gateway )");
                
                                 
                $query->execute( array(
                                       "r_uuid"  => $regionuuid,
                                       "p_name"  => $parcelname,
                                       "p_uuid"  => $parceluuid,
                                       "landing" => $parcellanding,
                                       "desc"    => $parceldescription,
                                       "cat"     => $parcelcategory,
                                       "build"   => $parcelbuild,
                                       "script"  => $parcelscript,
                                       "public"  => $parcelpublic,
                                       "dwell"   => $dwell,
                                       "i_uuid"  => $infouuid,
                                       "r_cat"   => $regioncategory,
                                       "r_gateway" => $gateway
                                      ) );

                $query = $db->prepare("DELETE FROM popularplaces WHERE parceluuid = ?");
                $query->execute( array($parceluuid) );                     
                

                $query = $db->prepare("INSERT INTO popularplaces(parceluuid, name, dwell, infouuid,
                                      has_picture, mature, gateway) VALUES(" .
                                       ":p_uuid, :p_name, :dwell, " .
                                       ":i_uuid, :has_pic, :r_cat, :r_gateway )");
                $query->execute( array(
                                       "p_uuid"  => $parceluuid,
                                       "p_name"  => $parcelname,
                                       "dwell"   => $dwell,
                                       "i_uuid"  => $infouuid,
                                       "has_pic" => $has_pic,
                                       "r_cat"   => $regioncategory,
                                       "r_gateway" => $gateway
                                    ) );
                
            }
            

            if ($parcelforsale == "true")
            {
                $query = $db->prepare("DELETE FROM parcelsales WHERE parceluuid = ?");
                $query->execute( array($parceluuid) );
                
                     
                echo "Insert parcelsales: $parcelname\n";
                
                $query = $db->prepare("INSERT INTO parcelsales (regionuuid, parcelname, parceluuid, area,
                                      saleprice, landingpoint, infouuid, dwell, parentestate, mature, gateway)
                                      VALUES(" .
                                       ":r_uuid, :p_name, :p_uuid, :area, " .
                                       ":price, :landing, :i_uuid, :dwell, " .
                                       ":e_id, :r_cat, :r_gateway)");
                $query->execute( array(
                                       "r_uuid"  => $regionuuid,
                                       "p_name"  => $parcelname,
                                       "p_uuid"  => $parceluuid,
                                       "area"    => $parcelarea,
                                       "price"   => $parcelsaleprice,
                                       "landing" => $parcellanding,
                                       "i_uuid"  => $infouuid,
                                       "dwell"   => $dwell,
                                       "e_id"    => $estateid,
                                       "r_cat"   => $regioncategory,
                                       "r_gateway" => $gateway
                                    ) );
            }
        }

        //
        // Handle objects
        //
        echo "Search objects\n";            
        $objects = $data->getElementsByTagName("object");

        $query = $db->prepare("DELETE FROM objects WHERE regionuuid = ?");
        $query->execute( array($regionuuid) );

        
        foreach ($objects as $value)
        {
            $uuid = $value->getElementsByTagName("uuid")->item(0)->nodeValue;
            $regionuuid = $value->getElementsByTagName("regionuuid")->item(0)->nodeValue;
            $parceluuid = $value->getElementsByTagName("parceluuid")->item(0)->nodeValue;
            $location = $value->getElementsByTagName("location")->item(0)->nodeValue;
            $title = $value->getElementsByTagName("title")->item(0)->nodeValue;
            echo "Insert object: $title\n";
            $description = $value->getElementsByTagName("description")->item(0)->nodeValue;

            $flags = $value->getElementsByTagName("flags")->item(0)->nodeValue;

            
            $query = $db->prepare("INSERT INTO objects (objectuuid,parceluuid, location, name, description,
                                  regionuuid, gateway, flags) VALUES(" .
                                   ":uuid, :p_uuid, :location, " .
                                   ":title, :desc, :r_uuid, :r_gateway, :flags)");
                

            try {
                $query->execute( array(
                                       "uuid"     => $uuid,
                                       "p_uuid"   => $parceluuid,
                                       "location" => $location,
                                       "title"    => $title,
                                       "desc"     => $description,
                                       "r_uuid"   => $regionuuid,
                                       "r_gateway" => $gateway,
                                       "flags"      => 0                                                                      
                                      ) );                
            
                
                echo "Inserted uuid=$uuid, p_uui = $parceluuid, location = $location, title = $title, desc = $description, r_uuid = $regionuuid, r_gateway = $gateway\n";
                
            } catch (Exception $e) {
                error_log($e->getMessage());
                echo "Error:" . $e->getMessage() . "\n";
            }
        }
       
    }
}
?>