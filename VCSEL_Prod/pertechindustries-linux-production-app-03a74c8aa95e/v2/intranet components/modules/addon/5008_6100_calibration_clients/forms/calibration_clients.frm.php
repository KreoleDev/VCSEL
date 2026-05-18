<?php
//Developer:    Charles Palmer
//Created:      2022.10.06
//Revision:     2022.10.06
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
        
    $page_title='6100 Calibration Client';
    require_once('common/includes/header_inner.inc.php');
      echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['title']:''),false);
                
        $frm=new frm($values,$errors,$hidden);
        echo $frm->begin_frm('','','multipart/form-data');
          echo $frm->begin_fieldset('General Information');
            echo $frm->begin_dl();
              echo $frm->text('ipAddress','IP Address:');
              echo $frm->text('title','Title:');
              echo $frm->text('color','CSS Color:');
              echo $frm->radio_group('active','Active?:',array('0'=>'No','1'=>'Yes'),true,'','1');
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

    if(empty($_POST['ipAddress'])){
	    $errors['ipAddress'] = array('IP Address', 'Please include an ip address.');
		}
		
		if(empty($_POST['title'])){
	    $errors['title'] = array('Title', 'Please include a title');
		}

		if(empty($_POST['color'])){
      $errors['color'] = array('Color', 'Please include a CSS color');
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
        'INSERT INTO prod_v2_6100_calibration_clients SET ipAddress=?, title=?, color=?, active=?',
        [
          $_POST['ipAddress'],
          $_POST['title'],
          $_POST['color'],
          $_POST['active']
        ],
        'sssi'
      );
      if(!$affected){ $success = false; }
            
      // Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Calibration Client", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Calibration Client was successfully created':'Calibration Client was NOT successfully created';
            
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
      show($_POST,$errors,array('clientId'=>$_POST['clientId'],'mode'=>'update'));
    }else{
      //Perform update
      $success = true;
      $common['db']->start_transaction();

      // Update
      $affected = $common['db']->pec(
        'UPDATE prod_v2_6100_calibration_clients SET ipAddress=?, title=?, color=?, active=? WHERE clientId=? LIMIT 1',
        [
          $_POST['ipAddress'],
          $_POST['title'],
          $_POST['color'],
          $_POST['active'],
          $_POST['clientId']
        ],
        'sssii'
      );
      if(!$affected){ $success=false; }

      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Calibration Client", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');

      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Calibration Client was successfully updated':'Calibration Client was NOT successfully updated';
            
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
    $affected=$common['db']->pec('DELETE FROM prod_v2_6100_calibration_clients WHERE clientId=? LIMIT 1',array($_REQUEST['clientId']),'i');
    if(!$affected){ $success=false; }
  
    //Create log entry
    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Deleted Calibration Client", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
    
    $state=$common['db']->end_transaction($success)?'success':'fail';
    $msg=$affected?'Calibration Client account was successfully deleted':'Calibration Client account was NOT successfully deleted';
  
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
      $info = $common['db']->pec('SELECT ipAddress, title, color, active FROM prod_v2_6100_calibration_clients WHERE clientId=? LIMIT 1',array($_REQUEST['clientId']),'i',['ipAddress','title','color','active']);
      show($info[0],array(),array('clientId'=>$_REQUEST['clientId'],'mode'=>'update'));
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