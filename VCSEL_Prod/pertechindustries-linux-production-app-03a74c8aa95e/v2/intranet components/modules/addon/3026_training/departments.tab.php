<?php
//Developer:    Charles Palmer
//Created:      2022.01.28
//Revision:     2022.01.28
require('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Can Train If Having Current Training
[2]     Can Train Even Without Current Training
[3]     Manage Processes, Procedures, and Departments
[4]     Manage Employees
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  if($common['security']->check_rights(3)) {
    echo '<p><a class="button" href="forms/departments.frm.php" title="Add Department">Add Department</a></p>';
  }

  // Get all departments
  $results = $common['db']->pec('SELECT departmentId, title FROM 3026_departments', array(), '', array('departmentId', 'title'));

  $headings=array('Title');
  $heading_classes=array('');

  if($common['security']->check_rights(3)) {
    $headings[]='&nbsp;';
    $heading_classes[]='no_sort';
  }

  echo $common['table']->begin($headings,'full_table',$heading_classes);

  foreach ($results as $row) {
    $cols=array($row['title']);
    $col_classes=array('');

    if($common['security']->check_rights(3)){
      $cols[]='<a href="forms/departments.frm.php?mode=edit&amp;departmentId=' . $row['departmentId'] . '" title="Edit ' . $row['title'] . '">Edit</a>';
      $col_classes[]='center';
    }

    echo $common['table']->add_row($cols,$col_classes);
  }

  echo $common['table']->end();

  require('common/includes/js.inc.php');
}
?>