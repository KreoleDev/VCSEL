<?php
//Developer:    Charles Palmer
//Created:      2022.10.10
//Revision:     2022.10.10
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     View
    [1]     Manage
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(1)){
  //--------------------------------------------------------------------------------------------------------------//
  function show($values, $errors, $hidden){
    global $common;
        
    $page_title = 'Error Types';
    require_once('common/includes/header_inner.inc.php');
	    // Find product info
	    $productInfo = $common['db']->pec('SELECT title FROM prod_v2_products WHERE productId=? LIMIT 1',array($hidden['extProductId']),'i',array('title'));
            
      echo $common['window']->begin($page_title . ': ' . $productInfo[0]['title'],false);
        $frm = new frm($values, $errors, $hidden);
        echo $frm->begin_frm();
          echo $frm->begin_fieldset('General');
            echo $frm->begin_dl();
              echo $frm->text('title','Title:');
              echo $frm->radio_group('active','Active?:',array('0'=>'No','1'=>'Yes'),true,'','1');
            echo $frm->end_dl();
          echo $frm->end_fieldset();
      
          echo $frm->begin_fieldset('');
            echo $frm->begin_dl('submit');
              echo '<dd><a href="products.frm.php?mode=edit&productId='.$hidden['extProductId'].'&selected_tab=1" title="Cancel" class="button">Cancel</a></dd>';
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
	    show($_POST,$errors,array('extProductId'=>$_POST['extProductId'],'mode'=>'insert'));
	  }else{
      $success = true;
      $common['db']->start_transaction();

      // Create parent object
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_product_error_types SET extProductId=?, title=?, active=?',
        array($_POST['extProductId'], $_POST['title'], $_POST['active']),
        'isi'
      );
      if(!$affected){ $success = false; }
            
      //Create log entry
	    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Error Type", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
      $state = $common['db']->end_transaction($success)?'success':'fail';
	    $msg=$affected?'Error Type was successfully associated':'Error Type was NOT successfully associated';
		
	    //Load main page
	    header('location: products.frm.php?mode=edit&productId='.$_POST['extProductId'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
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
      show($_POST,$errors,array('errorId'=>$_POST['errorId'], 'extProductId'=>$_POST['extProductId'], 'mode'=>'update'));
    }else{
      //Perform update
      $success = true;
      $common['db']->start_transaction();
      
      // Update main entry
      $affected = $common['db']->pec(
        'UPDATE prod_v2_product_error_types SET title=?, active=? WHERE errorId=? LIMIT 1',
        array($_POST['title'], $_POST['active'], $_POST['errorId']),
        'sii'
      );
      if(!$affected){ $success=false; }

      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Error Type", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');

      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Error Type was successfully updated':'Error Type was NOT successfully updated';
            
      //Load main page
      header('location: products.frm.php?mode=edit&productId='.$_POST['extProductId'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
      die();
    }
  }
  //--------------------------------------------------------------------------------------------------------------//
  function delete(){
    global $common;
        
    $affected=$common['db']->pec('DELETE FROM prod_v2_product_error_types WHERE errorId=? LIMIT 1',array($_REQUEST['errorId']),'i');
            
    //Create log entry
	  $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Deleted Error Type", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
    $state=$affected?'success':'fail';
	  $msg=$affected?'Error Type was successfully removed':'Error Type was NOT successfully removed';
		
    //Load main page
    header('location: products.frm.php?mode=edit&productId='.$_REQUEST['extProductId'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
    die(); 
  }
  //--------------------------------------------------------------------------------------------------------------//
  $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
  
  switch($mode){
    ////////////////////////////////////////////////////////
    case 'show':
      show(array(),array(),array('mode'=>'insert','extProductId'=>$_REQUEST['extProductId']));
    break;
    ////////////////////////////////////////////////////////
    case 'insert':
      insert();
    break;
    ////////////////////////////////////////////////////////
    case 'edit':
      // Find form info
      $info = $common['db']->pec('SELECT title, active FROM prod_v2_product_error_types WHERE errorId=? LIMIT 1',array($_REQUEST['errorId']),'i',array('title', 'active'));
      show($info[0],array(),array('errorId'=>$_REQUEST['errorId'], 'extProductId'=>$_REQUEST['extProductId'], 'mode'=>'update'));
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