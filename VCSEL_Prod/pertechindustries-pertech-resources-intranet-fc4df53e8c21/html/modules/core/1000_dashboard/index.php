<?php
//Developer:    Charles Palmer
//Created:      2014.05.02
//Revision:     2015.07.22
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     View
*/

$server_file_check=array(CFG_CMS_INCLUDE_PATH.'modules/core/1000_dashboard/index.php');

$page_title='Dashboard';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
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
        
        //Cycle through each module that user has rights for to see if they have a dashboard component
        foreach($_SESSION['modules'] as $key=>$value){
            foreach($_SESSION['modules'][$key] as $key2=>$value2){
                if(file_exists(CFG_CMS_INCLUDE_PATH . $_SESSION['modules'][$key][$key2]['path'] . 'dashboard.inc.php')){
                    include_once(CFG_CMS_INCLUDE_PATH . $_SESSION['modules'][$key][$key2]['path'] . 'dashboard.inc.php');
                }
            }
        }
        
    require_once('common/includes/footer_inner.inc.php');
}
?>