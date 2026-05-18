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
  if($common['security']->check_rights(3)) {
    echo '<p><a class="button" href="forms/process-procedures.frm.php" title="Add Process Or Procedure">Add Process Or Procedure</a></p>';
  }

  // Get all versions
  $results = $common['db']->pec('SELECT extProcessProcedureId, revisionDate, requiresRetraining FROM 3026_process_procedure_versions ORDER BY extProcessProcedureId, revisionDate DESC', array(), '', array('extProcessProcedureId', 'revisionDate', 'requiresRetraining'));
  $versions = array();
  $counter = 0;
  $lastId = 0;
  foreach ($results as $row) {
    if ($lastId != $row['extProcessProcedureId']) {
      $counter = 0;
      $lastId = $row['extProcessProcedureId'];
      $versions[$row['extProcessProcedureId']] = array();
    }
    $versions[$row['extProcessProcedureId']][$counter] = array();
    $versions[$row['extProcessProcedureId']][$counter]['date'] = $row['revisionDate'];
    $versions[$row['extProcessProcedureId']][$counter]['retraining'] = $row['requiresRetraining'];
    $counter++;
  }

  // Get all processes & procedures
  $results = $common['db']->pec('SELECT processProcedureId, 3026_departments.title, 3026_process_procedures.title, requiresAdvancedTraining, active FROM 3026_process_procedures, 3026_departments WHERE extDepartmentId=departmentId', array(), '', array('processProcedureId', 'department', 'title', 'requiresAdvancedTraining', 'active'));

  $headings=array('Department', 'Title', 'Revision', 'Requires Advanced Training', 'Active');
  $heading_classes=array('', '','', 'center', 'center');

  if($common['security']->check_rights(3)) {
    $headings[]='&nbsp;';
    $heading_classes[]='no_sort';
  }

  echo $common['table']->begin($headings,'full_table',$heading_classes);

  foreach ($results as $row) {
    $cols=array($row['department'], $row['title'], $versions[$row['processProcedureId']][0]['date'], $row['requiresAdvancedTraining'] ? 'Yes' : 'No', $row['active'] ? 'Yes' : 'No');
    $col_classes=array('','','','center','center');

    if($common['security']->check_rights(3)){
      $cols[]='<a href="forms/process-procedures.frm.php?mode=edit&amp;processProcedureId=' . $row['processProcedureId'] . '" title="Edit ' . $row['title'] . '">Edit</a>';
      $col_classes[]='center';
    }

    echo $common['table']->add_row($cols,$col_classes);
  }

  echo $common['table']->end();

  require('common/includes/js.inc.php');
}
?>