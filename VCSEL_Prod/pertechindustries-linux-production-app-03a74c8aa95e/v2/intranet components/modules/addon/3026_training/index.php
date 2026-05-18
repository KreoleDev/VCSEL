<?php
//Developer:    Charles Palmer
//Created:      2022.01.28
//Revision:     2022.01.28
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     View
    [1]     Can Train If Having Current Training
    [2]     Can Train Even Without Current Training
    [3]     Manage Processes, Procedures, and Departments
    [4]     Manage Employees
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  $page_title='Training';

  $additional_head='
    <style>
      .invalid {
        background-color: #ffcccc;
      }
    </style>
  ';

  require_once('common/includes/header_inner.inc.php');
    echo '<h2>'.$page_title.'</h2>';
    echo $common['window']->begin($page_title);
      $tabs=array();
      //==================================================================================================================//
	    $tabs[0]['title']='Training Log';
	    $tabs[0]['mode']='ajax';
	    $tabs[0]['content']='training_log.tab.php';
      //==================================================================================================================//
      $tabs[1]['title']='Processes &amp; Procedures';
	    $tabs[1]['mode']='ajax';
	    $tabs[1]['content']='processes_procedures.tab.php';
      //==================================================================================================================//
      $tabs[2]['title']='Employees';
	    $tabs[2]['mode']='ajax';
	    $tabs[2]['content']='employees.tab.php';
      //==================================================================================================================//
      $tabs[3]['title']='Departments';
	    $tabs[3]['mode']='ajax';
	    $tabs[3]['content']='departments.tab.php';
      //==================================================================================================================//
      echo $common['tabs']->create_tabs($tabs,isset($_REQUEST['selected_tab'])?$_REQUEST['selected_tab']:0);
    echo $common['window']->end();
  require_once('common/includes/footer_inner.inc.php');
}
?>