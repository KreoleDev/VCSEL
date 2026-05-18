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

if($common['security']->check_rights(3)){
  //--------------------------------------------------------------------------------------------------------------//
	function show($values,$errors,$hidden){
    global $common;
    $page_title='Departments';
    require_once('common/includes/header_inner.inc.php');
      echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['title']:''),false);
				$frm=new frm($values,$errors,$hidden);
        echo $frm->begin_frm();
					echo $frm->begin_fieldset('General Information');
						echo $frm->begin_dl();
              echo $frm->text('title','Title:');
            echo $frm->end_dl();
          echo $frm->end_fieldset();
                  
          echo $frm->begin_fieldset('');
            echo $frm->begin_dl('submit');
              echo '<dd><a href="../index.php?selected_tab=3" title="Cancel" class="button">Cancel</a></dd>';
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
        
    if(empty($_POST['title'])){
      $errors['title']=array('Title','Please include a title');
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
      $affected=$common['db']->pec('INSERT INTO 3026_departments SET title=?',array($_POST['title']),'s');
      if(!$affected){ $success=false; }
                    
      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Department", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
      
      $state=$common['db']->end_transaction($success)?'success':'fail';
      $msg=$affected?'Department was successfully created':'Department was NOT successfully created';
      
      //Load main page
      header('location: ../index.php?selected_tab=3&msg_state=' . $state . '&msg=' . $msg);
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
      show($_POST,$errors,array('departmentId'=>$_POST['departmentId'],'mode'=>'update'));
    }else{
      //Perform update
      $success=true;
      $common['db']->start_transaction();
          
      //Update category
      $affected=$common['db']->pec('UPDATE 3026_departments SET title=? WHERE departmentId=? LIMIT 1',array($_POST['title'],  $_POST['departmentId']),'si');
      if(!$affected){ $success=false; }
      
      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Department", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
      
      $state=$common['db']->end_transaction($success)?'success':'fail';
      $msg=$affected?'Department was successfully updated':'Department was NOT successfully updated';
      
      //Load main page
      header('location: ../index.php?selected_tab=3&msg_state=' . $state . '&msg=' . $msg);
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
      $info = $common['db']->pec('SELECT title FROM 3026_departments WHERE departmentId=? LIMIT 1',array($_REQUEST['departmentId']),'i',array('title')); 
      show($info[0],array(),array('mode'=>'update','departmentId'=>$_REQUEST['departmentId']));
    break;
    ////////////////////////////////////////////////////////
    case 'update':
      update();
    break;
    ////////////////////////////////////////////////////////
  }
}
?>    