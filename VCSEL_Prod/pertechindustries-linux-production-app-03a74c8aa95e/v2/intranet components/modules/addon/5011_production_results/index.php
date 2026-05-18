<?php
//Developer:    Charles Palmer
//Created:      2022.10.13
//Revision:     2022.10.14

/*
*   
*/

/*
[0]     View
*/

require_once('common/includes/std_lib.inc.php');

$page_title='Production Results';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  $additional_head = '
    <style>
      .error {
        background-color: rgba(255, 0, 0, 0.1);
      }
    </style>
  ';
  require_once('common/includes/header_inner.inc.php');
    echo '<h2>'.$page_title.'</h2>';
    echo $common['window']->begin($page_title);
      $serialNum = '';
      if (isset($_POST['serialNum'])) {
        $serialNum = $_POST['serialNum'];
      }

      $tabs=array();
	    //==================================================================================================================//
	    $tabs[0]['title']='Search';
	    $tabs[0]['mode']='ajax';
	    $tabs[0]['content']='search.tab.php?serialNum='.$serialNum;
	    //==================================================================================================================//
	    $tabs[1]['title']='Last 90 Days (Passing Only)';
	    $tabs[1]['mode']='ajax';
	    $tabs[1]['content']='last_90_days.tab.php';
	    //==================================================================================================================//
	    echo $common['tabs']->create_tabs($tabs,isset($_REQUEST['selected_tab'])?$_REQUEST['selected_tab']:0);

    echo $common['window']->end();
  require_once('common/includes/footer_inner.inc.php');
}
?>