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
        
    $page_title='Templates';
    require_once('common/includes/header_inner.inc.php');
      echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['title']:''),false);
                
        $frm=new frm($values,$errors,$hidden);
        echo $frm->begin_frm('','','multipart/form-data');
          echo $frm->begin_fieldset('General Information');
            echo $frm->begin_dl();
              echo $frm->text('title','Title:');

              echo $frm->textarea('codeset','HTML:', false);
              echo $frm->textarea('script','Javascript:', false);

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
        'INSERT INTO prod_v2_templates SET title=?, codeset=?, script=?, active=?',
        array($_POST['title'], $_POST['codeset'], $_POST['script'], $_POST['active']),
        'sssi'
      );
      if(!$affected){ $success = false; }

      // Get new ID
      $newId = $common['db']->last_insert_id();

      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_templates_archive SET templateId=?, title=?, codeset=?, script=?, active=?, generatedDateTime=NOW()',
        array($newId, $_POST['title'], $_POST['codeset'], $_POST['script'], $_POST['active']),
        'isssi'
      );
      if(!$affected){ $success=false; }
            
      // Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Template", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Template was successfully created':'Template was NOT successfully created';
            
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
      show($_POST,$errors,array('templateId'=>$_POST['templateId'],'mode'=>'update'));
    }else{
      //Perform update
      $success = true;
      $common['db']->start_transaction();

      // Update product
      $affected = $common['db']->pec(
        'UPDATE prod_v2_templates SET title=?, codeset=?, script=?, active=? WHERE templateId=? LIMIT 1',
        array($_POST['title'], $_POST['codeset'], $_POST['script'], $_POST['active'], $_POST['templateId']),
        'sssii'
      );
      if(!$affected){ $success=false; }

      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_templates_archive SET templateId=?, title=?, codeset=?, script=?, active=?, generatedDateTime=NOW()',
        array($_POST['templateId'], $_POST['title'], $_POST['codeset'], $_POST['script'], $_POST['active']),
        'isssi'
      );
      if(!$affected){ $success=false; }

      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Template", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');

      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Template was successfully updated':'Template was NOT successfully updated';
            
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
      $info = $common['db']->pec('SELECT title, codeset, script, active FROM prod_v2_templates WHERE templateId=? LIMIT 1',array($_REQUEST['templateId']),'i',array('title', 'codeset', 'script', 'active'));
      show($info[0],array(),array('templateId'=>$_REQUEST['templateId'],'mode'=>'update'));
    break;
    ////////////////////////////////////////////////////////
    case 'update':
      update();
    break;
    ////////////////////////////////////////////////////////
  }
}
?>