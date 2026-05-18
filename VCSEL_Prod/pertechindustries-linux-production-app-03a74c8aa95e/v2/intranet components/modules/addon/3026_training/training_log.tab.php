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
  if($common['security']->check_rights(1) || $common['security']->check_rights(2)) {
    echo '<p><a class="button" href="forms/training.frm.php" title="Log Training">Log Training</a></p>';
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

  // Determine user's employee id
  $userInfo = $common['db']->pec('SELECT employeeId FROM 3026_employees WHERE extIntranetUserId=? LIMIT 1', array($_SESSION['user_id']), 'i', array('employeeId'));
  $curEmployeeId = 0;
  if (!empty($userInfo)) {
    $curEmployeeId = $userInfo[0]['employeeId'];
  }
  $userCanTrainOn = array();

  // Get all training logs
  $results = $common['db']->pec(
    'SELECT logId, extProcessProcedureId, extEmployeeIdTrained, firstName, lastName, first_name, last_name, dateOfTraining, 3026_process_procedures.title, 3026_departments.title, requiresAdvancedTraining FROM 3026_training_log, core_users, 3026_employees, 3026_process_procedures, 3026_departments WHERE extIntranetUserIdTrainer=user_id AND extEmployeeIdTrained=employeeId AND processProcedureId=extProcessProcedureId AND extDepartmentId=departmentId ORDER BY extProcessProcedureId, extEmployeeIdTrained, dateOfTraining DESC', 
    array(), '', 
    array('logId', 'extProcessProcedureId', 'extEmployeeIdTrained', 'trainedFirstName', 'trainedLastName', 'trainerFirstName', 'trainerLastName', 'dateOfTraining', 'processProcedureTitle', 'departmentTitle', 'requiresAdvancedTraining')
  );
  $logs = array();
  $lastProcessProcedureId = 0;
  $lastEmployeeId = 0;
  foreach ($results as $row) {
    if ($lastProcessProcedureId != $row['extProcessProcedureId'] || $lastEmployeeId != $row['extEmployeeIdTrained']) {
      $lastProcessProcedureId = $row['extProcessProcedureId'];
      $lastEmployeeId = $row['extEmployeeIdTrained'];

      // Determine if training is still valid
      $dateForValidity = '1970-01-01';
      foreach ($versions[$row['extProcessProcedureId']] as $version) {
        if ($version['retraining'] == 1) {
          $dateForValidity = $version['date'];
          break;
        }
      }

      $row['trainingValid'] = strtotime($dateForValidity) <= strtotime($row['dateOfTraining']) ? 1 : 0;

      if ($row['trainingValid'] == 1 && $curEmployeeId == $row['extEmployeeIdTrained'] && $row['requiresAdvancedTraining'] == 0) {
        $userCanTrainOn[$row['extProcessProcedureId']] = 1;
      }

      array_push($logs, $row);
    }
  }

  $headings=array('Department', 'Title', 'Employee Trained', 'Who Performed Training', 'Date Trained', 'Training Valid');
  $heading_classes=array('', '','', '', '', 'center');

  if($common['security']->check_rights(1) || $common['security']->check_rights(2)) {
    $headings[]='&nbsp;';
    $heading_classes[]='no_sort';
  }

  echo $common['table']->begin($headings,'full_table',$heading_classes);

  foreach ($logs as $row) {
    $rowClass = $row['trainingValid'] == 1 ? ' valid' : ' invalid';
    $cols=array($row['departmentTitle'], $row['processProcedureTitle'], $row['trainedFirstName'] . ' ' . $row['trainedLastName'], $row['trainerFirstName'] . ' ' . $row['trainerLastName'], $row['dateOfTraining'], $row['trainingValid'] ? 'Yes' : 'No');
    $col_classes=array('' . $rowClass,'' . $rowClass,'' . $rowClass,'' . $rowClass,'' . $rowClass,'center' . $rowClass);

    if($common['security']->check_rights(2) || ($common['security']->check_rights(1) && isset($userCanTrainOn[$row['extProcessProcedureId']]))) {
      $cols[]='<a href="forms/training.frm.php?mode=update&amp;extProcessProcedureId=' . $row['extProcessProcedureId'] . '&amp;extEmployeeIdTrained=' . $row['extEmployeeIdTrained'] . '" title="mark ' . $row['trainedFirstName'] . ' ' . $row['trainedLastName'] . ' as trained today for ' . $row['processProcedureTitle'] . '" class="include_alert">Update Training</a>';
      $col_classes[]='center' . $rowClass;
    } else if($common['security']->check_rights(1) && !isset($userCanTrainOn[$row['extProcessProcedureId']])) {
      $cols[]='';
      $col_classes[]='';
    }

    echo $common['table']->add_row($cols,$col_classes);
  }

  echo $common['table']->end();

  require('common/includes/js.inc.php');
}
?>