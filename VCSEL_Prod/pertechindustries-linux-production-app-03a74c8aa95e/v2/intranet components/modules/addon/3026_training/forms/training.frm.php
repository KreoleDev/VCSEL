<?php
//Developer:    Charles Palmer
//Created:      2022.01.31
//Revision:     2022.08.29

/*
*   2022.08.29  CP  Added in date select field on insert, improved process/procedure list to include department
*/

require_once('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Can Train If Having Current Training
[2]     Can Train Even Without Current Training
[3]     Manage Processes, Procedures, and Departments
[4]     Manage Employees
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(1) || $common['security']->check_rights(2)){
  //--------------------------------------------------------------------------------------------------------------//
	function show($values,$errors,$hidden){
    global $common;
    $page_title='Log Training';
    require_once('common/includes/header_inner.inc.php');
      echo $common['window']->begin($page_title,false);
				$frm=new frm($values,$errors,$hidden);
        echo $frm->begin_frm();
					echo $frm->begin_fieldset('General Information');
						echo $frm->begin_dl();
              $processProcedures=array();
                                
              if ($common['security']->check_rights(2)) {
                // Can train even without current training
                $results=$common['db']->pec('SELECT processProcedureId, 3026_process_procedures.title, 3026_departments.title FROM 3026_process_procedures, 3026_departments WHERE active=1 AND extDepartmentId=departmentId ORDER BY 3026_departments.title, 3026_process_procedures.title, processProcedureId',array(),'',array('processProcedureId', 'processProcedureTitle', 'departmentTitle'));
                foreach($results as $row){
                    $processProcedures[$row['processProcedureId']]=$row['departmentTitle'] . ': ' . $row['processProcedureTitle'];  
                }
              } else {
                // Needs current training and cannot train on advanced

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

                if ($curEmployeeId > 0) {
                  $results = $common['db']->pec(
                    'SELECT extProcessProcedureId, dateOfTraining, 3026_process_procedures.title, 3026_departments.title FROM 3026_training_log, 3026_process_procedures, 3026_departments WHERE requiresAdvancedTraining=0 AND extEmployeeIdTrained=? AND processProcedureId=extProcessProcedureId AND extDepartmentId=departmentId ORDER BY 3026_departments.title, 3026_process_procedures.title, extProcessProcedureId, dateOfTraining DESC', 
                    array($curEmployeeId), 'i', 
                    array('extProcessProcedureId', 'dateOfTraining', 'processProcedureTitle', 'departmentTitle')
                  );

                  $lastProcessProcedureId = 0;
                  foreach ($results as $row) {
                    if ($lastProcessProcedureId != $row['extProcessProcedureId']) {
                      $lastProcessProcedureId = $row['extProcessProcedureId'];
                
                      // Determine if training is still valid
                      $dateForValidity = '1970-01-01';
                      foreach ($versions[$row['extProcessProcedureId']] as $version) {
                        if ($version['retraining'] == 1) {
                          $dateForValidity = $version['date'];
                          break;
                        }
                      }
                
                      $trainingValid = strtotime($dateForValidity) <= strtotime($row['dateOfTraining']) ? 1 : 0;
                
                      if ($trainingValid) {
                        $processProcedures[$row['extProcessProcedureId']]=$row['departmentTitle'] . ': ' . $row['processProcedureTitle'];
                      }
                    }
                  }
                }

              }
              
              echo $frm->list_menu('extProcessProcedureId','Process/Procedure:',$processProcedures);

              $employees = array();

              $results = $common['db']->pec('SELECT employeeId, firstName, lastName FROM 3026_employees WHERE 1 ORDER BY firstName, lastName', array(), '', array('employeeId', 'firstName', 'lastName'));
              foreach($results as $row){
                $employees[$row['employeeId']]=$row['firstName'] . ' ' . $row['lastName'];   
              }
            
              echo $frm->list_menu('extEmployeeIdTrained','Employee Trained:',$employees);
              echo $frm->text('dateOfTraining','Training Date:',true,10,'','date','','mm/dd/yyyy');
            echo $frm->end_dl();

          echo $frm->end_fieldset();
                  
          echo $frm->begin_fieldset('');
            echo $frm->begin_dl('submit');
              echo '<dd><a href="../index.php" title="Cancel" class="button">Cancel</a></dd>';
              echo $frm->submit('submit','Submit','submit');
            echo $frm->end_dl();    
          echo $frm->end_fieldset();
        echo $frm->end_frm();
      echo $common['window']->end();
    require_once('common/includes/footer_inner.inc.php');
  }
  //--------------------------------------------------------------------------------------------------------------//
  function validate(){
    global $common;
    $errors=array();
        
    if(empty($_POST['extProcessProcedureId'])){
      $errors['extProcessProcedureId']=array('Process/Procedure','Please select a process/procedure.');
    }

    if(empty($_POST['extEmployeeIdTrained'])){
      $errors['extEmployeeIdTrained']=array('Employee','Please select an employee.');
    }

    //Check date
    if(!$common['validate']->date($_POST['dateOfTraining'])){
      $errors['dateOfTraining']=array('Training Date','Please enter a valid date for training');	
    }
        
    return $errors;
  }
  //--------------------------------------------------------------------------------------------------------------//
  function insert(){
    global $common;

    //Check for errors
    $errors=validate();
                
    if(!empty($errors) || !$common['security']->verify_frm()){
      //Errors found
      show($_POST,$errors,array('mode'=>'insert'));
    }else{
      $success=true;
      $common['db']->start_transaction();
                    
      //Create parent object
      $affected=$common['db']->pec('INSERT INTO 3026_training_log SET extProcessProcedureId=?, extEmployeeIdTrained=?, extIntranetUserIdTrainer=?, dateOfTraining=?',array($_POST['extProcessProcedureId'], $_POST['extEmployeeIdTrained'], $_SESSION['user_id'], $common['format']->unformat_date($_POST['dateOfTraining'])),'iiis');
      if(!$affected){ $success=false; }
                    
      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Logged Training", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
      
      $state=$common['db']->end_transaction($success)?'success':'fail';
      $msg=$affected?'Training log was successfully created':'Training log was NOT successfully created';
      
      //Load main page
      header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
      die();
    }
  }
  //--------------------------------------------------------------------------------------------------------------//
  function update(){
    global $common;

    $success=true;
    $common['db']->start_transaction();
                  
    //Create parent object
    $affected=$common['db']->pec('INSERT INTO 3026_training_log SET extProcessProcedureId=?, extEmployeeIdTrained=?, extIntranetUserIdTrainer=?, dateOfTraining=NOW()',array($_REQUEST['extProcessProcedureId'], $_REQUEST['extEmployeeIdTrained'], $_SESSION['user_id']),'iii');
    if(!$affected){ $success=false; }
                  
    //Create log entry
    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Logged Training", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
    
    $state=$common['db']->end_transaction($success)?'success':'fail';
    $msg=$affected?'Training log was successfully created':'Training log was NOT successfully created';
    
    //Load main page
    header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
    die();
  }
  //--------------------------------------------------------------------------------------------------------------//
  $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
        
  switch($mode){
    ////////////////////////////////////////////////////////
    case 'show':
      show(array('dateOfTraining'=>date('m/d/Y')),array(),array('mode'=>'insert'));
    break;
    ////////////////////////////////////////////////////////
    case 'insert':
      insert();
    break;
    ////////////////////////////////////////////////////////
    case 'update':
      update();
    break;
    ////////////////////////////////////////////////////////
  }
}
?>    