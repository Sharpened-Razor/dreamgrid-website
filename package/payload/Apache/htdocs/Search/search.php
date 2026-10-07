<?php
//include the source file
require( "flog.php" );


header("content-type: text/html; charset=UTF-8");

include("database.php");    
?>


<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <script src="/assets/vendor/jquery-1.4.2.min.js" type="text/javascript"></script>
        <style type="text/css">
            tr.header
            {
                font-weight:bold;
            }
            tr.alt
            {
                background-color: #cccccc;
            }
        </style>
        <script type="text/javascript">
            $(document).ready(function(){
               $('.striped tr:even').addClass('alt');
            });
        </script>
        <title></title>
    </head>
    <body>

        <table class="striped">
            <tr class="header">
                <td>Name</td>
                <td>Description</td>
                <td>Region</td>
                <td>Location</td>
            </tr>
            <?php
            
            $text = $_POST['SearchTerm'];
            if (empty($_GET['searchTerm'])) {
                $text = "%";
            } else {
                $text = "%$text%";
            }
            $sqldata['text1'] = $text;
            $sqldata['text2'] = $text;
             
            $q = "SELECT Name, Description, location, Regions.regionname
                FROM Objects INNER JOIN Regions ON Objects.regionuuid = Regions.regionuuid
                where Name like :text1 or Description like :text2
                order by Name, Description";
            
            $query = $db->prepare($q);
            $result = $query->execute($sqldata);
            
            $counter = 0;
            while ($row = $query->fetch(PDO::FETCH_ASSOC))
            {
                $counter++;
                $location = $row["location"];
                $location = str_replace("/"," ", $location );
                echo "<tr valign=\"top\" >";
                echo "<td>" . $row["Name"] . "</td>";
                echo "<td>" . $row["Description"] . "</td>";
                echo "<td>" . $row["regionname"] . "</td>";
                echo "<td>" . $location . "</td>";
                echo "</tr>";
            }
            if ($counter == 0) {
                echo "<tr valign=\"top\">";
                echo "<td> </td>";
                echo "<td>Nothing found</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<input type=\"button\" value=\"Go Back\" onclick=\"history.back(-1)\" />"; 
            
            ?>
        </table>
    </body>
</html>