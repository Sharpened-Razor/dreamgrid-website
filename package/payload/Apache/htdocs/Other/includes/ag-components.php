<?php
/*
============================================================
 AUSTRALIA COMPONENT SYSTEM V1
============================================================

Central reusable website components.

Cards
Buttons
Badges
Sections

============================================================
*/


/*
============================================================
 USER LEVEL BADGE
============================================================
*/

function ag_user_level()
{

    global $level;


    $levelValue = $level ?? 0;


    $title =
        ($levelValue >= 200)
        ?
        "GRID OWNER"
        :
        "MEMBER";


    echo '

    <div class="ag-user-level">

        <strong>'
        .
        htmlspecialchars(
            $title,
            ENT_QUOTES,
            "UTF-8"
        )
        .
        '</strong>


        USER LEVEL

        '
        .
        htmlspecialchars(
            (string)$levelValue,
            ENT_QUOTES,
            "UTF-8"
        )
        .
        '


    </div>

    ';

}



/*
============================================================
 BUTTON
============================================================
*/

function ag_button($text,$url="#",$class="")
{

echo '

<a

class="ag-button '.$class.'"

href="'
.
htmlspecialchars(
$url,
ENT_QUOTES,
"UTF-8"
)
.
'"

>

'
.
htmlspecialchars(
$text,
ENT_QUOTES,
"UTF-8"
)
.
'

</a>

';

}



/*
============================================================
 CARD START
============================================================
*/

function ag_card_start($title="")
{

echo '

<section class="ag-card">


';


if($title!="")
{

echo '

<h2>
'
.
htmlspecialchars(
$title,
ENT_QUOTES,
"UTF-8"
)
.
'
</h2>

';

}


}



/*
============================================================
 CARD END
============================================================
*/

function ag_card_end()
{

echo '

</section>

';

}



/*
============================================================
 SECTION TITLE
============================================================
*/

function ag_section_title($title)
{

echo '

<div class="ag-section-title">

'
.
htmlspecialchars(
$title,
ENT_QUOTES,
"UTF-8"
)
.
'

</div>

';

}


?>
