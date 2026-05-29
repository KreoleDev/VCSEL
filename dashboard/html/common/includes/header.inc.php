<?php
//Developer:    Charles Palmer
//Created:      2014.04.15
//Revision:     2014.05.20

require_once('common/includes/std_lib.inc.php');
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9; IE=8" />
    <meta name="viewport" content="width=device-width; initial-scale=1.0" />
    <!--[if lt IE 9]>
    	<script src="common/js/html5shiv.min.js"></script>
    	<script src="common/js/respond.min.js"></script>
    <![endif]-->
    <title><?=CFG_CMS_NAME; ?><?=isset($page_title)?': ' . $page_title:''; ?></title>
    <link rel="icon" type="image/png" href="common/images/favicon.png" />
    <link rel="stylesheet" href="common/css/reset.css" type="text/css" media="screen, projection" />
    <link rel="stylesheet" href="common/css/frm.css" type="text/css" media="screen, projection" />
    <link rel="stylesheet" href="common/css/screen.css" type="text/css" media="screen, projection" />
    <link rel="stylesheet" href="<?=CFG_CMS_BASE_URL; ?>common/js/datatables/css/jquery.dataTables.min.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" href="<?=CFG_CMS_BASE_URL; ?>common/js/jquery-ui-1.11.4.custom/jquery-ui.min.css" type="text/css" media="screen, projection" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="format-detection" content="telephone=no">
    <link rel="apple-touch-icon" href="common/images/favicon_large.png"/>
    <script>(function(a,b,c){if(c in b&&b[c]){var d,e=a.location,f=/^(a|html)$/i;a.addEventListener("click",function(a){d=a.target;while(!f.test(d.nodeName))d=d.parentNode;"href"in d&&(d.href.indexOf("http")||~d.href.indexOf(e.host))&&(a.preventDefault(),e.href=d.href)},!1)}})(document,window.navigator,"standalone")</script>
    <script src="<?=CFG_CMS_BASE_URL; ?>common/js/jquery-2.1.1.min.js"></script>
    <script src="<?=CFG_CMS_BASE_URL; ?>common/js/jquery-ui-1.11.4.custom/jquery-ui.min.js"></script>
    <script src="<?=CFG_CMS_BASE_URL; ?>common/js/datatables/jquery.dataTables.min.js"></script>
    <script type="text/javascript">
	$(document).ready(function(){
	    // Fix issue with ipad on footer moving on keyboard display
	    //---------------------------------------------------------------------------//
	    if (navigator.platform == 'iPad') {
		$("footer").css("top", $(window).height() - 24 + "px");
	    }
	    
	    $(window).bind('orientationchange', function(event) {
		if (navigator.platform == 'iPad') {
		    $("footer").css("top", $(window).height() - 24 + "px");
		}
	    });
	    //---------------------------------------------------------------------------//
	});
    </script>
    <?php require('common/includes/js.inc.php'); ?>
</head>
<body<?=isset($body_class) && $body_class!==''?' class="' . $body_class . '"':''; ?>>
