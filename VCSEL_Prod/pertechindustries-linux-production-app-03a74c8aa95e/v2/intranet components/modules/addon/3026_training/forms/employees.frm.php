<?php
//Developer:    Charles Palmer
//Created:      2022.01.31
//Revision:     2022.01.31
require_once('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Can Train If Having Current Training
[2]     Can Train Even Without Current Training
[3]     Manage Processes, Procedures, and Departments
[4]     Manage Employees
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(4)){
  //--------------------------------------------------------------------------------------------------------------//
	function show($values,$errors,$hidden){
    global $common;
    $page_title='Employees';
    require_once('common/includes/header_inner.inc.php');
      echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['firstName'] . ' ' . $values['lastName']:''),false);
				$frm=new frm($values,$errors,$hidden);
        echo $frm->begin_frm();
					echo $frm->begin_fieldset('General Information');
						echo $frm->begin_dl('float');
              echo $frm->text('firstName','First Name:');
            echo $frm->end_dl();

            echo $frm->begin_dl('float');
              echo $frm->text('lastName','Last Name:');
            echo $frm->end_dl();

            echo $frm->begin_dl('clear');
              $users=array();
                              
              $results=$common['db']->pec('SELECT user_id, first_name, last_name FROM core_users WHERE 1 ORDER BY first_name, last_name',array(),'',array('user_id', 'first_name', 'last_name'));
              foreach($results as $row){
                  $users[$row['user_id']]=$row['first_name'] . ', ' . $row['last_name'];   
              }
              
              echo $frm->list_menu('extIntranetUserId','Intranet Account',$users, false);
            echo $frm->end_dl();
          echo $frm->end_fieldset();
                  
          echo $frm->begin_fieldset('');
            echo $frm->begin_dl('submit');
              echo '<dd><a href="../index.php?selected_tab=2" title="Cancel" class="button">Cancel</a></dd>';
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
        
    if(empty($_POST['firstName'])){
      $errors['firstName']=array('First Name','Please include a first name');
    }

    if(empty($_POST['lastName'])){
      $errors['lastName']=array('Last Name','Please include a last name');
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
      $affected=$common['db']->pec('INSERT INTO 3026_employees SET firstName=?, lastName=?, extIntranetUserId=?',array($_POST['firstName'], $_POST['lastName'], $_POST['extIntranetUserId']),'ssi');
      if(!$affected){ $success=false; }
                    
      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Employee", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
      
      $state=$common['db']->end_transaction($success)?'success':'fail';
      $msg=$affected?'Employee was successfully created':'Employee was NOT successfully created';
      
      //Load main page
      header('location: ../index.php?selected_tab=2&msg_state=' . $state . '&msg=' . $msg);
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
      show($_POST,$errors,array('employeeId'=>$_POST['employeeId'],'mode'=>'update'));
    }else{
      //Perform update
      $success=true;
      $common['db']->start_transaction();
          
      //Update category
      $affected=$common['db']->pec('UPDATE 3026_employees SET firstName=?, lastName=?, extIntranetUserId=? WHERE employeeId=? LIMIT 1',array($_POST['firstName'], $_POST['lastName'], $_POST['extIntranetUserId'],  $_POST['employeeId']),'ssii');
      if(!$affected){ $success=false; }
      
      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Employee", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
      
      $state=$common['db']->end_transaction($success)?'success':'fail';
      $msg=$affected?'Employee was successfully updated':'Employee was NOT successfully updated';
      
      //Load main page
      header('location: ../index.php?selected_tab=2&msg_state=' . $state . '&msg=' . $msg);
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
      $info = $common['db']->pec('SELECT firstName, lastName, extIntranetUserId FROM 3026_employees WHERE employeeId=? LIMIT 1',array($_REQUEST['employeeId']),'i',array('firstName', 'lastName', 'extIntranetUserId')); 
      show($info[0],array(),array('mode'=>'update','employeeId'=>$_REQUEST['employeeId']));
    break;
    ////////////////////////////////////////////////////////
    case 'update':
      update();
    break;
    ////////////////////////////////////////////////////////
  }
}
?>    