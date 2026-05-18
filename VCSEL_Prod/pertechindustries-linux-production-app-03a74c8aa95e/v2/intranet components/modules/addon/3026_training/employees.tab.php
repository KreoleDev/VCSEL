<?php
//Developer:    Charles Palmer
//Created:      2022.01.31
//Revision:     2022.01.31
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
  if($common['security']->check_rights(4)) {
    echo '<p><a class="button" href="forms/employees.frm.php" title="Add Employee">Add Employee</a></p>';
  }

  // Get all employees
  $results = $common['db']->pec('SELECT employeeId, firstName, lastName, extIntranetUserId FROM 3026_employees', array(), '', array('employeeId', 'firstName', 'lastName', 'extIntranetUserId'));

  $headings=array('First Name', 'Last Name', 'Associated With Login');
  $heading_classes=array('','','center');

  if($common['security']->check_rights(4)) {
    $headings[]='&nbsp;';
    $heading_classes[]='no_sort';
  }

  echo $common['table']->begin($headings,'full_table',$heading_classes);

  foreach ($results as $row) {
    $cols=array($row['firstName'], $row['lastName'], ($row['extIntranetUserId'] > 0 ? 'X' : ''));
    $col_classes=array('','','center');

    if($common['security']->check_rights(4)){
      $cols[]='<a href="forms/employees.frm.php?mode=edit&amp;employeeId=' . $row['employeeId'] . '" title="Edit ' . $row['firstName'] . ' ' . $row['lastName'] . '">Edit</a>';
      $col_classes[]='center';
    }

    echo $common['table']->add_row($cols,$col_classes);
  }

  echo $common['table']->end();

  require('common/includes/js.inc.php');
}
?>