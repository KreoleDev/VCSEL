<?php
//Developer:    Charles Palmer
//Created:      2022.01.31
//Revision:     2022.09.22
require_once('common/includes/std_lib.inc.php');

/*
*    2022.09.22  CP  Added the ability to associate with a production test grouping
*/

/*
[0]     View
[1]     Can Train If Having Current Training
[2]     Can Train Even Without Current Training
[3]     Manage Processes, Procedures, and Departments
[4]     Manage Employees
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(3)){
  //--------------------------------------------------------------------------------------------------------------//
	function show($values,$errors,$hidden){
    global $common;
    $page_title='Process Or Procedure';
    require_once('common/includes/header_inner.inc.php');
      echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['title']:''),false);
				$frm=new frm($values,$errors,$hidden);
        echo $frm->begin_frm();
					echo $frm->begin_fieldset('General Information');
						echo $frm->begin_dl();
              $departments=array();
                                
              $results=$common['db']->pec('SELECT departmentId, title FROM 3026_departments WHERE 1 ORDER BY title',array(),'',array('departmentId', 'title'));
              foreach($results as $row){
                  $departments[$row['departmentId']]=$row['title'];   
              }
              
              echo $frm->list_menu('extDepartmentId','Department',$departments);

              echo $frm->text('title','Title:');

              $subStages=array();
                                
              $results=$common['db']->pec('SELECT subStageId, prod_v2_products.title, prod_v2_sub_stages.title FROM prod_v2_sub_stages, prod_v2_products WHERE extProductId=productId AND prod_v2_sub_stages.active=1 AND prod_v2_products.active=1 ORDER BY prod_v2_products.title, prod_v2_sub_stages.title',array(),'',array('subStageId', 'productTitle', 'subStageTitle'));
              foreach($results as $row){
                  $subStages[$row['subStageId']]=$row['productTitle'] . ': ' . $row['subStageTitle'];   
              }
              
              echo $frm->list_menu('extSubStageId','Manufacturing Test Stage',$subStages, false);

              echo $frm->radio_group('requiresAdvancedTraining','Requires Advanced Training:',array('1'=>'Yes','0'=>'No'),true,'inline','0');
              echo $frm->radio_group('active','Active:',array('1'=>'Yes','0'=>'No'),true,'inline','1');
            echo $frm->end_dl();

          echo $frm->end_fieldset();

          echo $frm->begin_fieldset('Current Released', false);
            echo $frm->begin_dl();
              echo $frm->text('revisionDate','Revision Date:',true,10,'','date','','mm/dd/yyyy');
              echo $frm->radio_group('requiresRetraining','Requires Retraining:',array('1'=>'Yes','0'=>'No'),true,'inline','0');
            echo $frm->end_dl();
          echo $frm->end_fieldset();
                  
          echo $frm->begin_fieldset('');
            echo $frm->begin_dl('submit');
              echo '<dd><a href="../index.php?selected_tab=1" title="Cancel" class="button">Cancel</a></dd>';
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
        
    if(empty($_POST['extDepartmentId'])){
      $errors['extDepartmentId']=array('Department','Please select a department');
    }

    if(empty($_POST['title'])){
      $errors['title']=array('Title','Please include a title');
    }

    if(!$common['validate']->date($_POST['revisionDate'])){
	    $errors['revisionDate']=array('Revision Date','Please enter a valid date for the current revision');	
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
      $affected=$common['db']->pec('INSERT INTO 3026_process_procedures SET extDepartmentId=?, title=?, requiresAdvancedTraining=?, active=?, extSubStageId=?',array($_POST['extDepartmentId'], $_POST['title'], $_POST['requiresAdvancedTraining'], $_POST['active'], $_POST['extSubStageId']),'isiii');
      if(!$affected){ $success=false; }

      if($success) {
        $processProcedureId = $common['db']->last_insert_id();
        $affected=$common['db']->pec('INSERT INTO 3026_process_procedure_versions SET extProcessProcedureId=?, revisionDate=?, requiresRetraining=?',array($processProcedureId, $common['format']->unformat_date($_POST['revisionDate']), $_POST['requiresRetraining']),'isi');
        if(!$affected){ $success=false; }
      }
                    
      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Process or Procedure", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
      
      $state=$common['db']->end_transaction($success)?'success':'fail';
      $msg=$affected?'Process or Procedure was successfully created':'Process or Procedure was NOT successfully created';
      
      //Load main page
      header('location: ../index.php?selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
      die();
    }
  }
  //--------------------------------------------------------------------------------------------------------------//
  function update(){
    global $common;

    //Check for errors
    $errors=validate();
    
    if(!empty($errors) || !$common['security']->verify_frm()){
      //Errors found
      show($_POST,$errors,array('processProcedureId'=>$_POST['processProcedureId'],'mode'=>'update'));
    }else{
      //Perform update
      $success=true;
      $common['db']->start_transaction();
          
      //Update parent
      $affected=$common['db']->pec('UPDATE 3026_process_procedures SET extDepartmentId=?, title=?, requiresAdvancedTraining=?, active=?, extSubStageId=? WHERE processProcedureId=? LIMIT 1',array($_POST['extDepartmentId'], $_POST['title'], $_POST['requiresAdvancedTraining'], $_POST['active'], $_POST['processProcedureId'], $_POST['extSubStageId']),'isiiii');
      if(!$affected){ $success=false; }

      // Determine if we need to update the current version
      $currentVersion=$common['db']->pec('SELECT revisionDate, requiresRetraining FROM 3026_process_procedure_versions WHERE extProcessProcedureId=? ORDER BY revisionDate DESC LIMIT 1',array($_POST['processProcedureId']),'i',array('revisionDate', 'requiresRetraining'));
      if(date('Y-m-d',strtotime($currentVersion[0]['revisionDate']))!=$common['format']->unformat_date($_POST['revisionDate'])){
        // Update the current version
        $affected=$common['db']->pec('INSERT INTO 3026_process_procedure_versions SET extProcessProcedureId=?, revisionDate=?, requiresRetraining=?',array($_POST['processProcedureId'], $common['format']->unformat_date($_POST['revisionDate']), $_POST['requiresRetraining']),'isi');
        if(!$affected){ $success=false; }
      } else if($currentVersion[0]['requiresRetraining']!=$_POST['requiresRetraining'] ){
        // Update the current version
        $affected=$common['db']->pec('UPDATE 3026_process_procedure_versions SET requiresRetraining=? WHERE extProcessProcedureId=? AND revisionDate=?',array($_POST['requiresRetraining'], $_POST['processProcedureId'], $common['format']->unformat_date($_POST['revisionDate'])),'iis');
        if(!$affected){ $success=false; }
      }
      
      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Process or Procedure", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
      
      $state=$common['db']->end_transaction($success)?'success':'fail';
      $msg=$affected?'Process or Procedure was successfully updated':'Process or Procedure was NOT successfully updated';
      
      //Load main page
      header('location: ../index.php?selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
      die();
    }
  }
  //--------------------------------------------------------------------------------------------------------------//
  $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
        
  switch($mode){
    ////////////////////////////////////////////////////////
    case 'show':
      show(array(),array(),array('mode'=>'insert'));
    break;
    ////////////////////////////////////////////////////////
    case 'insert':
      insert();
    break;
    ////////////////////////////////////////////////////////
    case 'edit':
      $info = $common['db']->pec('SELECT extDepartmentId, title, requiresAdvancedTraining, active, extSubStageId FROM 3026_process_procedures WHERE processProcedureId=? LIMIT 1',array($_REQUEST['processProcedureId']),'i',array('extDepartmentId', 'title', 'requiresAdvancedTraining', 'active','extSubStageId'));
      
      // Get most recent version info
      $versionInfo = $common['db']->pec('SELECT revisionDate, requiresRetraining FROM 3026_process_procedure_versions WHERE extProcessProcedureId=? ORDER BY revisionDate DESC LIMIT 1',array($_REQUEST['processProcedureId']),'i',array('revisionDate', 'requiresRetraining'));
      $info[0]['revisionDate'] = $common['format']->format_date($versionInfo[0]['revisionDate']);
      $info[0]['requiresRetraining'] = $versionInfo[0]['requiresRetraining'];
      
      show($info[0],array(),array('mode'=>'update','processProcedureId'=>$_REQUEST['processProcedureId']));
    break;
    ////////////////////////////////////////////////////////
    case 'update':
      update();
    break;
    ////////////////////////////////////////////////////////
  }
}
?>    