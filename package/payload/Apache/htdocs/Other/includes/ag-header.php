<?php
/*
============================================================
 AUSTRALIA MASTER HEADER V1
 Uses existing Sentinel V3 design system
============================================================
*/

if(!isset($pageSection)){
    $pageSection = "";
}

if(!isset($pageTitle)){
    $pageTitle = "";
}

if(!isset($headerButtons)){
    $headerButtons = "";
}

?>


<link rel="stylesheet" href="/Other/assets/css/australia-design-system.css">


<header class="dashboard-header">


<div class="dashboard-heading">


<div class="dashboard-kicker">

<?=htmlspecialchars(
$pageSection,
ENT_QUOTES,
"UTF-8"
)?>

</div>


<h1>

<?=htmlspecialchars(
$pageTitle,
ENT_QUOTES,
"UTF-8"
)?>

</h1>


<div class="dashboard-avatar">

Signed in as

<strong>

<?=htmlspecialchars(
$avatar ?? "Unknown",
ENT_QUOTES,
"UTF-8"
)?>

</strong>

</div>


</div>



<div class="php-level">

<strong>

<?=(
($level ?? 0) >= 200
?
"GRID OWNER"
:
"MEMBER"
)?>

</strong>

USER LEVEL

<?=htmlspecialchars(
(string)($level ?? 0),
ENT_QUOTES,
"UTF-8"
)?>

</div>



<div class="dashboard-actions">

<?= $headerButtons ?>

</div>



</header>

