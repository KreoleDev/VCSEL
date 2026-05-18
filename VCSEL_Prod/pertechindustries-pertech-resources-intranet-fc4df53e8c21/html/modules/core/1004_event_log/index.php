<?php
//Developer:    Charles Palmer
//Created:      2014.05.27
//Revision:     2014.05.30
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     Full Control
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    $page_title='Event Log';
    $additional_head='
	<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="' . CFG_CMS_BASE_URL . 'common/js/jquery.jqplot.1.0.8r1250/excanvas.js"></script><![endif]-->
	<script language="javascript" type="text/javascript" src="' . CFG_CMS_BASE_URL . 'common/js/jquery.jqplot.1.0.8r1250/jquery.jqplot.min.js"></script>
	<link rel="stylesheet" type="text/css" href="' . CFG_CMS_BASE_URL . 'common/js/jquery.jqplot.1.0.8r1250/jquery.jqplot.css" />
	<script type="text/javascript" src="' . CFG_CMS_BASE_URL . 'common/js/jquery.jqplot.1.0.8r1250/plugins/jqplot.barRenderer.min.js"></script>
	<script type="text/javascript" src="' . CFG_CMS_BASE_URL . 'common/js/jquery.jqplot.1.0.8r1250/plugins/jqplot.categoryAxisRenderer.min.js"></script>
	<script type="text/javascript" src="' . CFG_CMS_BASE_URL . 'common/js/jquery.jqplot.1.0.8r1250/plugins/jqplot.pointLabels.min.js"></script>
	<script type="text/javascript" src="' . CFG_CMS_BASE_URL . 'common/js/jquery.jqplot.1.0.8r1250/plugins/jqplot.pieRenderer.min.js"></script>
	<script type="text/javascript" src="' . CFG_CMS_BASE_URL . 'common/js/jquery.jqplot.1.0.8r1250/plugins/jqplot.donutRenderer.min.js"></script>
	<script type="text/javascript" src="' . CFG_CMS_BASE_URL . 'common/js/jquery.jqplot.1.0.8r1250/plugins/jqplot.cursor.min.js"></script>
	<script type="text/javascript" src="' . CFG_CMS_BASE_URL . 'common/js/jquery.jqplot.1.0.8r1250/plugins/jqplot.highlighter.min.js"></script>
    ';
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';
        echo $common['window']->begin('Charts');
            $tabs=array();
	    //==================================================================================================================//
	    $tabs[0]['title']='Authentication Actions';
	    $tabs[0]['mode']='ajax';
	    $tabs[0]['content']='auth_actions.tab.php';
	    //==================================================================================================================//
	    $tabs[1]['title']='Accessed Modules';
	    $tabs[1]['mode']='ajax';
	    $tabs[1]['content']='accessed_modules.tab.php';
	    //==================================================================================================================//
	    echo $common['tabs']->create_tabs($tabs,isset($_REQUEST['selected_tab'])?$_REQUEST['selected_tab']:0);
        echo $common['window']->end();
        
        echo $common['window']->begin('Raw Logs',true,true);
            $tabs=array();
	    //==================================================================================================================//
	    $tabs[0]['title']='Authentication Actions';
	    $tabs[0]['mode']='ajax';
	    $tabs[0]['content']='auth_actions_log.tab.php';
	    //==================================================================================================================//
	    $tabs[1]['title']='Accessed Modules';
	    $tabs[1]['mode']='ajax';
	    $tabs[1]['content']='accessed_modules_log.tab.php';
	    //==================================================================================================================//
	    echo $common['tabs']->create_tabs($tabs,isset($_REQUEST['selected_tab2'])?$_REQUEST['selected_tab2']:0);
        echo $common['window']->end();
    require_once('common/includes/footer_inner.inc.php');
}
?>