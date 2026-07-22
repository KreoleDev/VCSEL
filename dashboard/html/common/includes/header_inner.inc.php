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

// Keep menus current when modules or rights are changed after the user logged in.
if(isset($_SESSION['user_id'])){
    $_SESSION['modules']=array();
    $loaded_module_ids=array();
    $admin_info=$common['db']->pec('SELECT count(*) FROM core_user_group_lookup WHERE ext_user_id=? AND ext_group_id=2 LIMIT 1',array($_SESSION['user_id']),'i',array('count'));
    if(!empty($admin_info) && $admin_info[0]['count']==1){
        $results=$common['db']->pec('SELECT module_id, title, path, ext_panel_id, sort_order FROM core_modules ORDER BY ext_panel_id, sort_order, title',array(),'',array('module_id', 'title', 'path', 'ext_panel_id', 'sort_order'));
        foreach($results as $row){
            $_SESSION['modules'][$row['ext_panel_id']][$row['sort_order'] . '_' . $row['module_id']]['id']=$row['module_id'];
            $_SESSION['modules'][$row['ext_panel_id']][$row['sort_order'] . '_' . $row['module_id']]['title']=$row['title'];
            $_SESSION['modules'][$row['ext_panel_id']][$row['sort_order'] . '_' . $row['module_id']]['path']=$row['path'];
        }
    }else{
        $results=$common['db']->pec('SELECT module_id, title, path, ext_panel_id, sort_order FROM core_modules WHERE module_id IN(SELECT ext_module_id FROM core_group_rights WHERE (ext_group_id=1 OR ext_group_id IN(SELECT ext_group_id FROM core_user_group_lookup WHERE ext_user_id=?))) ORDER BY ext_panel_id, sort_order, title',array($_SESSION['user_id']),'i',array('module_id', 'title', 'path', 'ext_panel_id', 'sort_order'));
        foreach($results as $row){
            $module_key=$row['sort_order'] . '_' . $row['module_id'];
            $_SESSION['modules'][$row['ext_panel_id']][$module_key]['id']=$row['module_id'];
            $_SESSION['modules'][$row['ext_panel_id']][$module_key]['title']=$row['title'];
            $_SESSION['modules'][$row['ext_panel_id']][$module_key]['path']=$row['path'];
            $loaded_module_ids[$row['module_id']]=true;
        }

        $results=$common['db']->pec('SELECT module_id, title, path, ext_panel_id, sort_order FROM core_modules WHERE module_id IN(SELECT ext_module_id FROM core_user_rights WHERE ext_user_id=?) ORDER BY ext_panel_id, sort_order, title',array($_SESSION['user_id']),'i',array('module_id', 'title', 'path', 'ext_panel_id', 'sort_order'));
        foreach($results as $row){
            if(isset($loaded_module_ids[$row['module_id']])){
                continue;
            }
            $module_key=$row['sort_order'] . '_' . $row['module_id'];
            $_SESSION['modules'][$row['ext_panel_id']][$module_key]['id']=$row['module_id'];
            $_SESSION['modules'][$row['ext_panel_id']][$module_key]['title']=$row['title'];
            $_SESSION['modules'][$row['ext_panel_id']][$module_key]['path']=$row['path'];
        }
    }
    foreach($_SESSION['modules'] as $panel_id=>$panel_modules){
        ksort($_SESSION['modules'][$panel_id]);
    }
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

	    //Profile menu
	    //---------------------------------------------------------------------------//
	    $('#profile_menu_btn').click(function(){
		$('#profile_menu').toggle();
		$(this).toggleClass('active');
		return false;
	    });

	    $(document).click(function(event){
		if(!$(event.target).closest('#profile_actions').length){
		    $('#profile_menu').hide();
		    $('#profile_menu_btn').removeClass('active');
		}
	    });
	    //---------------------------------------------------------------------------//

	    //Show loading dialog while selected module pages open
	    //---------------------------------------------------------------------------//
	    $('.page_progress_link').click(function(){
		$('#page_progress_dialog').addClass('is_visible').attr('aria-hidden','false');
	    });
	    //---------------------------------------------------------------------------//
	});
    </script>
    <?php require('common/includes/js.inc.php'); ?>
    <?=isset($additional_head)?$additional_head:''; ?>
</head>
<?php
$body_classes=array();
if(isset($body_class) && $body_class!==''){
    $body_classes[]=$body_class;
}
if(isset($_SESSION['is_vcsel_app_user']) && $_SESSION['is_vcsel_app_user']){
    $body_classes[]='vcsel_app_user';
}
$profile_name_parts=preg_split('/\s+/',trim($_SESSION['user']));
$profile_initials='';
if(!empty($profile_name_parts[0])){
    $profile_initials.=substr($profile_name_parts[0],0,1);
}
if(count($profile_name_parts)>1 && !empty($profile_name_parts[count($profile_name_parts)-1])){
    $profile_initials.=substr($profile_name_parts[count($profile_name_parts)-1],0,1);
}
$profile_initials=strtoupper($profile_initials);
$session_module_ids=array();
if(isset($_SESSION['modules'])){
    foreach($_SESSION['modules'] as $panel_modules){
        foreach($panel_modules as $module){
            if(isset($module['id'])){
                $session_module_ids[]=(int)$module['id'];
            }
        }
    }
}
?>
<body<?=!empty($body_classes)?' class="' . implode(' ',$body_classes) . '"':''; ?>>
    <header>
        <a id="logo" href="<?=isset($_SESSION['is_vcsel_app_user']) && $_SESSION['is_vcsel_app_user']?CFG_CMS_BASE_URL . 'modules/addon/7001_vcsel_results/index.php?mod_id=7001':CFG_CMS_BASE_URL . 'index.php'; ?>" title="Home"><img src="<?=CFG_CMS_BASE_URL; ?>common/images/login_logo.png?v=20260529" alt="<?=CFG_CMS_NAME; ?>" /></a>
	
	<?php if(!isset($_SESSION['is_vcsel_app_user']) || !$_SESSION['is_vcsel_app_user']){ ?>
	    <div id="header_options">
		<a id="main_menu_btn" href="" title="Main Menu"><img src="<?=CFG_CMS_BASE_URL; ?>common/images/header_menu.png" alt="Main Menu" /></a>
	    </div>
	<?php } ?>
	
	<div id="profile_actions">
	    <?php if(isset($_SESSION['is_vcsel_app_user']) && $_SESSION['is_vcsel_app_user']){ ?>
		<a class="vcsel_header_link page_progress_link" href="<?=CFG_CMS_BASE_URL; ?>modules/addon/7001_vcsel_results/index.php?mod_id=7001" title="VCSEL Test Result">VCSEL Test Result</a>
		<?php if(in_array(7014,$session_module_ids)){ ?>
		<a class="vcsel_header_link page_progress_link" href="<?=CFG_CMS_BASE_URL; ?>modules/addon/7014_camera_testing/index.php?mod_id=7014" title="Camera Testing">Camera Testing</a>
		<?php } ?>
		<?php if(in_array(7015,$session_module_ids)){ ?>
		<a class="vcsel_header_link page_progress_link" href="<?=CFG_CMS_BASE_URL; ?>modules/addon/7015_tally_reader/index.php?mod_id=7015" title="Tally Reader">Tally Reader</a>
		<?php } ?>
		<a class="vcsel_header_link" href="<?=CFG_CMS_BASE_URL; ?>vcsel_app.php" title="APPS">APPS</a>
	    <?php } ?>
	    <button id="profile_menu_btn" type="button">
		<span class="profile_initials"><?=htmlspecialchars($profile_initials,ENT_QUOTES); ?></span>
	    </button>
	    <ul id="profile_menu">
		<li class="profile_menu_identity">
		    <strong><?=$_SESSION['user']; ?></strong>
		    <span><?=$_SESSION['site_name']; ?></span>
		</li>
		<li><a href="<?=CFG_CMS_BASE_URL; ?>modules/core/1003_account_settings/index.php?mod_id=1003">Account Settings</a></li>
		<li><a href="<?=CFG_CMS_BASE_URL; ?>index.php?logoff=true">Log Out</a></li>
	    </ul>
	</div>
    </header>
    <div id="page_progress_dialog" class="page_progress_dialog" role="dialog" aria-modal="true" aria-label="Loading" aria-hidden="true">
	<div class="page_progress_panel">
	    <span class="page_progress_spinner" aria-hidden="true"></span>
	</div>
    </div>
    <?php if(!isset($_SESSION['is_vcsel_app_user']) || !$_SESSION['is_vcsel_app_user']){ ?>
    <div id="main_menu">
	<?php
	//Find all panels
	$results=$common['db']->pec('SELECT panel_id, title FROM core_panels ORDER BY sort_order',array(),'',array('panel_id', 'title'));
	foreach($results as $row){
	    if(isset($_SESSION['modules'][$row['panel_id']])){
		//module exist for user in this panel
		$module_links='';
		foreach($_SESSION['modules'][$row['panel_id']] as $key=>$value){
		    $module_title=$_SESSION['modules'][$row['panel_id']][$key]['title'];
		    if(in_array($module_title,array('Dashboard','Account Settings'))){
			continue;
		    }
		    $module_id=$_SESSION['modules'][$row['panel_id']][$key]['id'];
		    $module_progress_class=in_array($module_id,array(7001,7014))?' class="page_progress_link"':'';
		    $module_links.='<dd><a' . $module_progress_class . ' href="' . CFG_CMS_BASE_URL . $_SESSION['modules'][$row['panel_id']][$key]['path'] . '?mod_id=' . $module_id . '" title="' . $module_title . '">' . $module_title . '</a></dd>';
		}
		if(!empty($module_links)){
		    echo '<dl><dt>' . $row['title'] . '</dt>' . $module_links . '</dl>';
		}
	    }
	}
    $vcsel_app_active=basename($_SERVER['SCRIPT_NAME'])=='vcsel_app.php'?' class="active"':'';
    echo '<dl><dt>Downloads</dt>';
        echo '<dd><a' . $vcsel_app_active . ' href="' . CFG_CMS_BASE_URL . 'vcsel_app.php" title="APPS">APPS</a></dd>';
    echo '</dl>';
	?>
    </div>
    <?php } ?>
    <div id="main_content">
	<?=isset($_REQUEST['msg'])?'<div id="msg" ' . (isset($_REQUEST['msg_state'])?'class="' . $_REQUEST['msg_state'] . '"':'') . '>' . $_REQUEST['msg'] . '</div>':'';?>
