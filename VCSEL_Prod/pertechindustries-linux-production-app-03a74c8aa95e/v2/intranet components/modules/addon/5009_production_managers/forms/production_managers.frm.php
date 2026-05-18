<?php
//Developer:    Charles Palmer
//Created:      2022.10.11
//Revision:     2022.10.11
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     View
    [1]     Manage
*/

/*
*   
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(1)){
  //--------------------------------------------------------------------------------------------------------------//
  function show($values,$errors,$hidden){
    global $common;
        
    $page_title='Production Managers';
    require_once('common/includes/header_inner.inc.php');
      echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['name']:''),false);
                
        $frm=new frm($values,$errors,$hidden);
        echo $frm->begin_frm('','','multipart/form-data');
          echo $frm->begin_fieldset('General Information');
            echo $frm->begin_dl();
              echo $frm->text('name','Name:');
              echo $frm->text('email','Email:');
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
    $errors = array();

    if(empty($_POST['name'])){
	    $errors['name'] = array('Name', 'Please include a name.');
		}
		
		if(empty($_POST['email'])){
	    $errors['email'] = array('Email', 'Please include an email address');
		} else if(!$common['validate']->email($_POST['email'])){
	    $errors['email']=array('Email','Please enter a valid email address');
	  }

		return $errors;
	}
  //--------------------------------------------------------------------------------------------------------------//
  function insert(){
    global $common;

    // Check for errors
    $errors = validate();
        
    if(!empty($errors) || !$common['security']->verify_frm()){
      // Errors found
      show($_POST,$errors,array('mode'=>'insert'));
    }else{
      $success = true;
      $common['db']->start_transaction();

      // Create parent object
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_production_managers SET name=?, email=?',
        [
          $_POST['name'],
          $_POST['email']
        ],
        'ss'
      );
      if(!$affected){ $success = false; }
            
      // Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Production Manager", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Production manager was successfully created':'Production manager was NOT successfully created';
            
      //Load edit again with second tab selected
      header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
      die();
    }
	}
  //--------------------------------------------------------------------------------------------------------------//
  function update(){
    global $common;
        
    //Check for errors
    $errors = validate();
        
    if(!empty($errors) || !$common['security']->verify_frm()){
      //Errors found
      show($_POST,$errors,array('managerId'=>$_POST['managerId'],'mode'=>'update'));
    }else{
      //Perform update
      $success = true;
      $common['db']->start_transaction();

      // Update
      $affected = $common['db']->pec(
        'UPDATE prod_v2_production_managers SET name=?, email=? WHERE managerId=? LIMIT 1',
        [
          $_POST['name'],
          $_POST['email'],
          $_POST['managerId']
        ],
        'ssi'
      );
      if(!$affected){ $success=false; }

      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Production Manager", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');

      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Production Manager was successfully updated':'Production Manager was NOT successfully updated';
            
      //Load main page
      header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
      die();
    }
  }
  //--------------------------------------------------------------------------------------------------------------//
  function delete(){
    global $common;

    $success=true;
    $common['db']->start_transaction();
    
    //Remove
    $affected=$common['db']->pec('DELETE FROM prod_v2_production_managers WHERE managerId=? LIMIT 1',array($_REQUEST['managerId']),'i');
    if(!$affected){ $success=false; }
  
    //Create log entry
    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Deleted Production Manager", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
    
    $state=$common['db']->end_transaction($success)?'success':'fail';
    $msg=$affected?'Production Manager account was successfully deleted':'Production Manager account was NOT successfully deleted';
  
    //Load main page
    header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
    die();
  }
  //--------------------------------------------------------------------------------------------------------------//
  $mode = isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';

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
      // Find form info
      $info = $common['db']->pec('SELECT name, email FROM prod_v2_production_managers WHERE managerId=? LIMIT 1',array($_REQUEST['managerId']),'i',['name','email']);
      show($info[0],array(),array('managerId'=>$_REQUEST['managerId'],'mode'=>'update'));
    break;
    ////////////////////////////////////////////////////////
    case 'update':
      update();
    break;
    ////////////////////////////////////////////////////////
    case 'delete':
	    delete();    
	  break;
	  ////////////////////////////////////////////////////////
  }
}
?>