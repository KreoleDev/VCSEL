<?php
//Developer:    Charles Palmer
//Created:      2022.09.27
//Revision:     2022.09.27
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
        
    $page_title='APIs';
    require_once('common/includes/header_inner.inc.php');
      echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['title']:''),false);
                
        $frm=new frm($values,$errors,$hidden);
        echo $frm->begin_frm('','','multipart/form-data');
          echo $frm->begin_fieldset('General Information');
            echo $frm->begin_dl();
              echo $frm->text('title','Title:');

              echo $frm->textarea('script','Javascript:');

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
		
		if(empty($_POST['title'])){
	    $errors['title'] = array('Title', 'Please include a title');
		}

    if(empty($_POST['script'])){
	    $errors['script'] = array('Javascript', 'Please include a script');
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
        'INSERT INTO prod_v2_apis SET title=?, script=?, active=?',
        array($_POST['title'], $_POST['script'], $_POST['active']),
        'ssi'
      );
      if(!$affected){ $success = false; }

      // Get new ID
      $newId = $common['db']->last_insert_id();

      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_apis_archive SET apiId=?, title=?, script=?, active=?, generatedDateTime=NOW()',
        array($newId, $_POST['title'], $_POST['script'], $_POST['active']),
        'issi'
      );
      if(!$affected){ $success=false; }
            
      // Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added API", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'API was successfully created':'API was NOT successfully created';
            
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
      show($_POST,$errors,array('apiId'=>$_POST['apiId'],'mode'=>'update'));
    }else{
      //Perform update
      $success = true;
      $common['db']->start_transaction();

      // Update product
      $affected = $common['db']->pec(
        'UPDATE prod_v2_apis SET title=?, script=?, active=? WHERE apiId=? LIMIT 1',
        array($_POST['title'], $_POST['script'], $_POST['active'], $_POST['apiId']),
        'ssii'
      );
      if(!$affected){ $success=false; }

      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_apis_archive SET apiId=?, title=?, script=?, active=?, generatedDateTime=NOW()',
        array($_POST['apiId'], $_POST['title'], $_POST['script'], $_POST['active']),
        'issi'
      );
      if(!$affected){ $success=false; }

      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated API", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');

      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'API was successfully updated':'API was NOT successfully updated';
            
      //Load main page
      header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
      die();
    }
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
      $info = $common['db']->pec('SELECT title, script, active FROM prod_v2_apis WHERE apiId=? LIMIT 1',array($_REQUEST['apiId']),'i',array('title', 'script', 'active'));
      show($info[0],array(),array('apiId'=>$_REQUEST['apiId'],'mode'=>'update'));
    break;
    ////////////////////////////////////////////////////////
    case 'update':
      update();
    break;
    ////////////////////////////////////////////////////////
  }
}
?>