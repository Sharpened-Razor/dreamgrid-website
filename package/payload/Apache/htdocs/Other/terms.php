<?php
require_once __DIR__ . '/core/grid-branding.php';
$gridName = ag_grid_name_html();

/*
 * ============================================================
 * Grid - PUBLIC TERMS OF SERVICE
 *
 * Terms content copied at installation time from the preserved
 * AUSTRALIA website master.
 *
 * No live DreamGrid WifiPages dependency.
 * ============================================================
 */

require_once __DIR__ . '/core/bootstrap.php';

ag_no_cache();

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>
Terms of Service
</title>


<style>

*{
    box-sizing:border-box;
}


html,
body{
    min-height:100%;
}


body{

    margin:0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    color:#e9eef1;

    background:
        linear-gradient(
            rgba(2,7,10,.45),
            rgba(2,7,10,.70)
        ),
        url(
            "/Other/assets/images/control-center-teal-bg.png"
        )
        center center /
        cover fixed
        no-repeat;
}


.terms-shell{

    width:
        min(
            1050px,
            calc(100% - 32px)
        );

    margin:
        38px auto;
}


.terms-header{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

    padding:
        20px 24px;

    border:
        1px solid
        rgba(222,165,43,.43);

    border-radius:14px;

    background:
        rgba(6,14,18,.94);

    box-shadow:
        0 18px 50px
        rgba(0,0,0,.45);
}


.terms-eyebrow{

    color:#e9ad34;

    font-size:10px;

    font-weight:900;

    letter-spacing:.16em;
}


.terms-header h1{

    margin:
        5px 0 0;

    color:#fff;

    font-size:
        clamp(
            26px,
            4vw,
            38px
        );
}


.terms-home{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    min-height:40px;

    padding:
        0 16px;

    border:
        1px solid
        rgba(226,169,48,.46);

    border-radius:8px;

    color:#ffd56b;

    background:
        rgba(220,158,32,.07);

    text-decoration:none;

    font-size:10px;

    font-weight:900;
}


.terms-card{

    margin-top:20px;

    padding:
        30px 34px;

    border:
        1px solid
        rgba(221,164,43,.42);

    border-radius:14px;

    background:
        linear-gradient(
            180deg,
            rgba(18,29,34,.97),
            rgba(5,11,14,.98)
        );

    box-shadow:
        0 18px 55px
        rgba(0,0,0,.48);
}


.terms-content{

    color:#cbd4d8;

    font-size:13px;

    line-height:1.68;
}


.terms-content strong{

    color:#fff;
}


.terms-content p{

    margin:
        10px 0;
}


.terms-content ul{

    margin:
        8px 0 14px 24px;
}


.terms-content li{

    margin:
        5px 0;
}


.terms-content h1,
.terms-content h2,
.terms-content h3,
.terms-content h4{

    color:#efb33b;
}


.terms-actions{

    margin-top:28px;

    padding-top:20px;

    border-top:
        1px solid
        rgba(255,255,255,.10);
}


.terms-button{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    min-height:40px;

    padding:
        0 16px;

    border:
        1px solid
        rgba(227,170,50,.42);

    border-radius:7px;

    color:#ffd56b;

    background:
        rgba(225,164,38,.07);

    text-decoration:none;

    font-size:10px;

    font-weight:900;
}


@media(max-width:650px){

    .terms-shell{
        margin-top:16px;
    }

    .terms-header{
        align-items:flex-start;
        flex-direction:column;
    }

    .terms-card{
        padding:22px;
    }
}

</style>

<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=20260830-phase3c">

<style id="australia-sentinel-sitewide-v3">


/* ============================================================
   AUSTRALIA SENTINEL 3D CARD SYSTEM V3
   SITE WIDE
   ============================================================ */


.dashboard-card,
.control-hub-card,
.stat-card,
.summary-card,
.inventory-card,
.profile-card,
.region-panel,
.section-card,
.manage-card{


position:relative !important;

overflow:hidden !important;


border-radius:15px !important;


border:

2px solid
rgba(210,218,220,.55) !important;


background:

linear-gradient(
145deg,
#4d5559,
#090b0c
) !important;


box-shadow:

inset 0 2px 0
rgba(255,255,255,.25),

inset 0 -22px 35px
rgba(0,0,0,.8),

0 18px 40px
rgba(0,0,0,.75) !important;


}



.dashboard-card:after,
.control-hub-card:after,
.stat-card:after,
.summary-card:after,
.inventory-card:after,
.profile-card:after,
.region-panel:after,
.section-card:after,
.manage-card:after{


content:"";

position:absolute;

inset:7px;

border-radius:10px;

pointer-events:none;


border:

1px solid
rgba(255,193,58,.45);

}



.card-title,
.card-heading,
.panel-title,
.summary-title{


color:#ffd167 !important;

font-weight:900 !important;

text-shadow:

0 2px 5px
rgba(0,0,0,.8);

}



.card-icon,
.card-icon.svg-badge{


background:

linear-gradient(
145deg,
#70777a,
#111415
) !important;


border:

1px solid
rgba(255,255,255,.3) !important;


box-shadow:

inset 0 2px 4px
rgba(255,255,255,.25),

0 8px 18px
rgba(0,0,0,.6);


}



.dashboard-card:hover,
.control-hub-card:hover,
.stat-card:hover,
.summary-card:hover{


transform:
translateY(-3px);


}


</style>

</head>


<body>


<div class="terms-shell">


<header class="terms-header">

<div>

<div class="terms-eyebrow">
GRID SERVICES
</div>

<h1>
Terms of Service
</h1>

</div>


<a
    class="terms-home"
    href="/Other/index.php"
>
HOME
</a>

</header>


<main class="terms-card">


<div class="terms-content">

<H3>Terms of Service</H3><!-- Change the text at your own will. These are suggested terms and do not represent any legal advice.--><STRONG>Welcome to <?=$gridName?> !</STRONG> 
<P></P>
<P>Your access to and use of <?=$gridName?> is based on your acceptance of and compliance with these Terms. These Terms apply to all visitors, users and others who access or use the Service. </STRONG>By accessing or using the Service you agree to be bound by these Terms. If you disagree with any part of the terms then you may not access the Service. </STRONG></P>
<P><STRONG>DRM in Opensimulator</STRONG></P>
<P>You acknowledge the use of Digital Rights Management in Opensimulator that grants certain content licenses to other users through the permissions system. This may include the copy, modify, and transfer settings for indicating how other users may use, reproduce, distribute, prepare derivative works of, display, or perform your Content subject to choices you make. You agree to respect, follow and allow the DRM rights of others that use this system.</P>
<P></P>
<P>You acknowledge that content creators maintain their rights to their content. You accept full responsibility for use of provided content and remain liable for unlawful usage.</P>
<P><STRONG>DMCA Notices</STRONG></P>
<P>If a content provider reports a DMCA violation to <?=$gridName?>, it will be investigated and if warranted, removed from public access.</P>
<P>Offending accounts may be banned from use of the services.</P>
<P><STRONG>Copyrights</STRONG></P>
<P>You agree that you will not copy, transfer, or distribute outside of&nbsp; <?=$gridName?> any content in whole or in part or in modified or unmodified form, that infringes or violates any Intellectual Property Rights of <?=$gridName?> , other Content Providers, or any third parties. </P>
<P><STRONG>Age</STRONG> 
<P>You certify that by clicking ACCEPT that you are over 18 years of age.</P>
<P><STRONG>Data Rights</STRONG></P>
<P>You grant <?=$gridName?> permission to process your data to maintain your account. This includes:</P>
<UL>
<LI>Registration information you provide when you create an account such as: 
<UL>
    <LI>Your Email address</LI>
    <LI>Your Avatar Name</LI>
    <LI>Your IP address</LI></UL></LI>
    <LI>Information necessary to connect to your PC such as session ID's and web cookies</LI>
    <LI>Any other personal information you give us to service your account.</LI>
    <LI>Transaction information you provide when you request information or purchase a product or service from us, whether on our sites or through our applications. </LI>
</UL>
<P><STRONG>Hyperlinks</STRONG></P>
<P><?=$gridName?> may contain links or hyperlinks to third-party services that are not owned or controlled by <?=$gridName?>. Hyperlinks are destinations that you may travel to. Hyperlinks connect to third parties and they will have access to limited data. They will need this info to provide Hypergrid service, and may not follow the GDPR act, but may use this information by means outside of our control.</P>
<P>You agree that your Avatar Name, Session ID, IP address and other technical information can be sent to other opensim grids when you travel the hypergrid. <?=$gridName?> has no control over, and assumes no responsibility for, the content, privacy policies, or practices of any third party web sites or services. You further acknowledge and agree that <?=$gridName?> may contain links to third-party services that are not owned or controlled by <?=$gridName?> .</P>
<P><STRONG>Liability</STRONG></P>
<P><?=$gridName?> shall not be responsible or liable, directly or indirectly, for any damage or loss caused or alleged to be caused by or in connection with use of or reliance on any such content, goods or services available on or through any such web sites or services.</P>
<P><STRONG>Right to be forgotten</STRONG></P>
<P>You have the right to request erasure of personal identifying information, or to the deletion of your account. <?=$gridName?> may retain certain records that <?=$gridName?> is required to retain by law.</P>
<P><STRONG>Termination</STRONG></P>
<P>We may terminate or suspend access to our Service immediately, without prior notice or liability, for any reason whatsoever, without limitation if you breach the Terms.</P>
<P>All provisions of the Terms which by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity and limitations of liability This Agreement may be modified for any reason, without prior notice (unless prior notice is required by law), by posting the revised Agreement here. If at any time you do not agree to those terms and conditions, you must immediately cease your use of the system.</P>
<P><STRONG>Agreement</STRONG></P>
<P>You specifically give <?=$gridName?> permission to collect and use your data as indicated in this Policy and you agree to be bound by this Terms of Service.</P>


</div>


<div class="terms-actions">

<a
    class="terms-button"
    href="/Other/index.php"
>
BACK TO HOME
</a>

</div>


</main>


</div>


<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>

</html>



