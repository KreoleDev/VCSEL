<?php
//Developer:    Charles Palmer
//Created:      2014.05.02
//Revision:     2015.07.22

require_once('common/includes/std_lib.inc.php');

//Add entry that user loaded page
if(isset($_SESSION['mod_id'])){
    //add new entry
    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, action="Load Page", datetime=NOW(), remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
}

//Determine modification date/time for page loaded
if(isset($server_file_check)){
	$newest_time=0;
	foreach($server_file_check as $key=>$value){
		if($newest_time<filemtime($value)){
			$newest_time=filemtime($value);
		}
	}
	$server_file_last_modified=date ("m/d/Y @ g:iA", $newest_time);
}

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
    <link rel="icon" type="image/png" href="<?=CFG_CMS_BASE_URL; ?>common/images/favicon.png" />
    <link rel="stylesheet" href="<?=CFG_CMS_BASE_URL; ?>common/css/reset.css" type="text/css" media="screen, projection" />
    <link href='http://fonts.googleapis.com/css?family=Roboto+Condensed:700' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="<?=CFG_CMS_BASE_URL; ?>common/css/frm.css" type="text/css" media="screen, projection" />
    <link rel="stylesheet" href="<?=CFG_CMS_BASE_URL; ?>common/js/datatables/css/jquery.dataTables.min.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" href="<?=CFG_CMS_BASE_URL; ?>common/js/jquery-ui-1.11.4.custom/jquery-ui.min.css" type="text/css" media="screen, projection" />
    <link rel="stylesheet" href="<?=CFG_CMS_BASE_URL; ?>common/css/screen.css" type="text/css" media="screen, projection" />
    <link rel="stylesheet" href="<?=CFG_CMS_BASE_URL; ?>common/css/print.css" type="text/css" media="print" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="format-detection" content="telephone=no">
    <link rel="apple-touch-icon" href="<?=CFG_CMS_BASE_URL; ?>common/images/favicon_large.png"/>
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
	    
	    //Expand and collapse windows
	    //---------------------------------------------------------------------------//
	    $('div.win_title span').click(function(){
		$(this).parents('div.window').children('div.win_content').toggle('blind');
		if ($(this).hasClass('collapsed')) {
		    $(this).removeClass('collapsed').addClass('expanded');
		    $(this).parent().removeClass('collapse_rounded');
		}else{
		    $(this).removeClass('expanded').addClass('collapsed');
		    $(this).parent().addClass('collapse_rounded');
		}
	    });
	    //---------------------------------------------------------------------------//
	    
	    //Show or hide menu for mobile
	    //---------------------------------------------------------------------------//
	    $('#main_menu_btn').click(function(){
		$('#main_menu').toggle('blind');
		$('#menu_overlay').toggle('fade');
		
		return false;
	    });
	    //---------------------------------------------------------------------------//
	});
    </script>
    <?php require('common/includes/js.inc.php'); ?>
    <?=isset($additional_head)?$additional_head:''; ?>
</head>
<body<?=isset($body_class) && $body_class!==''?' class="' . $body_class . '"':''; ?>>
    <header>
        <a id="logo" href="<?=CFG_CMS_BASE_URL; ?>index.php" title="Home"><img src="<?=CFG_CMS_BASE_URL; ?>common/images/login_logo.png?v=20260529" alt="<?=CFG_CMS_NAME; ?>" /></a>
	
	<div id="header_options">
	    <a id="main_menu_btn" href="" title="Main Menu"><img src="<?=CFG_CMS_BASE_URL; ?>common/images/header_menu.png" alt="Main Menu" /></a>
	    <a href="<?=CFG_CMS_BASE_URL; ?>index.php?logoff=true" title="Log Out"><img src="<?=CFG_CMS_BASE_URL; ?>common/images/header_logout.png" alt="Log Out" /></a>
	</div>
	
	<div id="login_info">
	    <ul>
		<li><?=$_SESSION['user']; ?></li>
		<li><?=$_SESSION['site_name']; ?></li>
	    </ul>
	</div>
    </header>
    <div id="main_menu">
	<?php
	//Find all panels
	$results=$common['db']->pec('SELECT panel_id, title FROM core_panels ORDER BY sort_order',array(),'',array('panel_id', 'title'));
	foreach($results as $row){
	    if(isset($_SESSION['modules'][$row['panel_id']])){
		//module exist for user in this panel
		echo '<dl><dt>' . $row['title'] . '</dt>';
		    foreach($_SESSION['modules'][$row['panel_id']] as $key=>$value){
			echo '<dd><a href="' . CFG_CMS_BASE_URL . $_SESSION['modules'][$row['panel_id']][$key]['path'] . '?mod_id=' . $_SESSION['modules'][$row['panel_id']][$key]['id'] . '" title="' . $_SESSION['modules'][$row['panel_id']][$key]['title'] . '">' . $_SESSION['modules'][$row['panel_id']][$key]['title'] . '</a></dd>';
		    }
		echo '</dl>';
	    }
	}
    $vcsel_app_active=basename($_SERVER['SCRIPT_NAME'])=='vcsel_app.php'?' class="active"':'';
    echo '<dl><dt>Downloads</dt>';
        echo '<dd><a' . $vcsel_app_active . ' href="' . CFG_CMS_BASE_URL . 'vcsel_app.php" title="VCSEL APP">VCSEL APP</a></dd>';
    echo '</dl>';
	?>
    </div>
    <div id="main_content">
	<?=isset($_REQUEST['msg'])?'<div id="msg" ' . (isset($_REQUEST['msg_state'])?'class="' . $_REQUEST['msg_state'] . '"':'') . '>' . $_REQUEST['msg'] . '</div>':'';?>
