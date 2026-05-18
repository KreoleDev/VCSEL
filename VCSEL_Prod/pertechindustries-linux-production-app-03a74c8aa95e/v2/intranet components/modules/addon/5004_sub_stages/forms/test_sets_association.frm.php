<?php
//Developer:    Charles Palmer
//Created:      2022.09.28
//Revision:     2022.10.26

/*
*  2022.10.26  CP  Corrections to collapsing sort order
*/

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
        
    $page_title = 'Test Set Association';
    require_once('common/includes/header_inner.inc.php');
	    // Find sub stage info
	    $subStageInfo = $common['db']->pec('SELECT prod_v2_sub_stages.title, prod_v2_products.title, productId FROM prod_v2_sub_stages, prod_v2_products WHERE extProductId=productId AND subStageId=? LIMIT 1',array($hidden['extSubStageId']),'i',array('title', 'product', 'productId'));
            
      echo $common['window']->begin($page_title . ': ' . $subStageInfo[0]['product'] . ' ' . $subStageInfo[0]['title'],false);
        $frm = new frm($values, $errors, $hidden);
        echo $frm->begin_frm();
          echo $frm->begin_fieldset('General');
            echo $frm->begin_dl();
              // Find all test sets that are not associated with this sub stage or is its' self on edit
              if ($hidden['mode'] == 'update') {
                $results = $common['db']->pec('SELECT testSetId, prod_v2_test_sets.title FROM prod_v2_test_sets, prod_v2_products WHERE extProductId=productId AND productId=? AND testSetId NOT IN(SELECT extTestSetId FROM prod_v2_sub_stage_test_set_associations WHERE extSubStageId=? AND associationId<>?) ORDER BY prod_v2_test_sets.title',array($subStageInfo[0]['productId'], $hidden['extSubStageId'], $hidden['associationId']),'iii',array('testSetId', 'testSetTitle'));
              } else {
                $results = $common['db']->pec('SELECT testSetId, prod_v2_test_sets.title FROM prod_v2_test_sets, prod_v2_products WHERE extProductId=productId AND productId=? AND testSetId NOT IN(SELECT extTestSetId FROM prod_v2_sub_stage_test_set_associations WHERE extSubStageId=?) ORDER BY prod_v2_test_sets.title',array($subStageInfo[0]['productId'], $hidden['extSubStageId']),'ii',array('testSetId', 'testSetTitle'));
              }
              
              $testSets = array();
              foreach($results as $row){
                $testSets[$row['testSetId']] = $row['testSetTitle'];
              }
              echo $frm->list_menu('extTestSetId', 'Test Set:', $testSets);

              //Find all associated test sets to determine sort order options
              if($hidden['mode']=='insert') {
                $results = $common['db']->pec('SELECT title, prod_v2_sub_stage_test_set_associations.sortOrder FROM prod_v2_test_sets, prod_v2_sub_stage_test_set_associations WHERE extTestSetId=testSetId AND extSubStageId=? ORDER BY prod_v2_sub_stage_test_set_associations.sortOrder',array($hidden['extSubStageId']),'i',array('title', 'sortOrder'));
              } else {
                $results = $common['db']->pec('SELECT title, prod_v2_sub_stage_test_set_associations.sortOrder FROM prod_v2_test_sets, prod_v2_sub_stage_test_set_associations WHERE extTestSetId=testSetId AND extSubStageId=? AND associationId<>? ORDER BY prod_v2_sub_stage_test_set_associations.sortOrder',array($hidden['extSubStageId'], $hidden['associationId']),'ii',array('title', 'sortOrder'));
              }
              $placeAfterOptions = array();
              $placeAfterOptions['-1'] = '=== TOP LEVEL ===';
              foreach($results as $row) {
                  $placeAfterOptions[$row['sortOrder']] = $row['title'];
              }
              echo $frm->list_menu('sortOrder','Place After:',$placeAfterOptions);

              echo $frm->radio_group('active','Active?:',array('0'=>'No','1'=>'Yes'),true,'','1');
            echo $frm->end_dl();
          echo $frm->end_fieldset();
      
          echo $frm->begin_fieldset('');
            echo $frm->begin_dl('submit');
              echo '<dd><a href="sub_stages.frm.php?mode=edit&subStageId='.$hidden['extSubStageId'].'&selected_tab=1" title="Cancel" class="button">Cancel</a></dd>';
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
        
    if(empty($_POST['extTestSetId'])){
	    $errors['extTestSetId']=array('Test Set','Please select a test set');
	  }

    if(empty($_POST['sortOrder']) && $_POST['sortOrder'] != '0'){
      $errors['sortOrder'] = array('Place After', 'Please select a sort order');
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
	    show($_POST,$errors,array('extSubStageId'=>$_POST['extSubStageId'],'mode'=>'insert'));
	  }else{
      $success = true;
      $common['db']->start_transaction();

      // Open sort window for entry
      $affected = $common['db']->pec('UPDATE prod_v2_sub_stage_test_set_associations SET sortOrder=sortOrder+1 WHERE sortOrder>? AND extSubStageId=?',array($_POST['sortOrder'], $_POST['extSubStageId']),'ii');
      if(!$affected){ $success = false; }

      // Create parent object
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_sub_stage_test_set_associations SET extTestSetId=?, extSubStageId=?, sortOrder=?, active=?',
        array($_POST['extTestSetId'], $_POST['extSubStageId'], $_POST['sortOrder'] + 1, $_POST['active']),
        'iiii'
      );
      if(!$affected){ $success = false; }

      // Get new ID
      $newId = $common['db']->last_insert_id();
            
      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_sub_stage_test_set_associations_archive SET associationId=?, extTestSetId=?, extSubStageId=?, sortOrder=?, active=?, generatedDateTime=NOW()',
        array($newId, $_POST['extTestSetId'], $_POST['extSubStageId'], $_POST['sortOrder'] + 1, $_POST['active']),
        'iiiii'
      );
      if(!$affected){ $success=false; }
            
      //Create log entry
	    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Test Set to Sub Stage", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
      $state = $common['db']->end_transaction($success)?'success':'fail';
	    $msg=$affected?'Test Set was successfully associated':'Test Set was NOT successfully associated';
		
	    //Load main page
	    header('location: sub_stages.frm.php?mode=edit&subStageId='.$_POST['extSubStageId'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
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
      show($_POST,$errors,array('associationId'=>$_POST['associationId'], 'extSubStageId'=>$_POST['extSubStageId'], 'mode'=>'update'));
    }else{
      //Perform update
      $success = true;
      $common['db']->start_transaction();
      
      // Find where sub stage is currently sorted at
      $testSetInfo = $common['db']->pec('SELECT sortOrder FROM prod_v2_sub_stage_test_set_associations WHERE associationId=? LIMIT 1',array($_POST['associationId']),'i', array('sortOrder'));

      //Collapse old location
      $affected = $common['db']->pec('UPDATE prod_v2_sub_stage_test_set_associations SET sortOrder=sortOrder-1 WHERE sortOrder>? AND extSubStageId=?',array($testSetInfo[0]['sortOrder'], $_POST['extSubStageId']),'ii');
      if(!$affected){ $success=false; }

      if ($testSetInfo[0]['sortOrder'] < $_POST['sortOrder']){
        $newSortOrder = $_POST['sortOrder'] - 1;
      } else {
        $newSortOrder = $_POST['sortOrder'];
      }

      //Open new location
      $affected = $common['db']->pec('UPDATE prod_v2_sub_stage_test_set_associations SET sortOrder=sortOrder+1 WHERE sortOrder>? AND extSubStageId=?',array($newSortOrder, $_POST['extSubStageId']),'ii');
      if(!$affected){ $success=false; }

      // Update main entry
      $affected = $common['db']->pec(
        'UPDATE prod_v2_sub_stage_test_set_associations SET extTestSetId=?, extSubStageId=?, sortOrder=?, active=? WHERE associationId=? LIMIT 1',
        array($_POST['extTestSetId'], $_POST['extSubStageId'], $newSortOrder + 1, $_POST['active'], $_POST['associationId']),
        'iiiii'
      );
      if(!$affected){ $success=false; }

      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_sub_stage_test_set_associations_archive SET associationId=?, extTestSetId=?, extSubStageId=?, sortOrder=?, active=?, generatedDateTime=NOW()',
        array($_POST['associationId'], $_POST['extTestSetId'], $_POST['extSubStageId'], $_POST['sortOrder'] + 1, $_POST['active']),
        'iiiii'
      );
      if(!$affected){ $success=false; }

      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Test Set Association", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');

      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Test Set Association was successfully updated':'Test Set Association was NOT successfully updated';
            
      //Load main page
      header('location: sub_stages.frm.php?mode=edit&subStageId='.$_POST['extSubStageId'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
      die();
    }
  }
  //--------------------------------------------------------------------------------------------------------------//
  function delete(){
    global $common;

    $success = true;
    $common['db']->start_transaction();
    
    // Find where sub stage is currently sorted at
    $testSetInfo = $common['db']->pec('SELECT sortOrder, extSubStageId FROM prod_v2_sub_stage_test_set_associations WHERE associationId=? LIMIT 1',array($_REQUEST['associationId']),'i', array('sortOrder', 'extSubStageId'));
        
    $affected=$common['db']->pec('DELETE FROM prod_v2_sub_stage_test_set_associations WHERE associationId=? LIMIT 1',array($_REQUEST['associationId']),'i');
    if(!$affected){ $success=false; }

    //Collapse old location
    $affected = $common['db']->pec('UPDATE prod_v2_sub_stage_test_set_associations SET sortOrder=sortOrder-1 WHERE sortOrder>? AND extSubStageId=?',array($testSetInfo[0]['sortOrder'], $testSetInfo[0]['extSubStageId']),'ii');
    if(!$affected){ $success=false; }
            
    //Create log entry
	  $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Deleted Test Set from Sub Stage", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
    $state = $common['db']->end_transaction($success)?'success':'fail';
	  $msg=$affected?'Test Set was successfully removed':'Test Set was NOT successfully removed';
		
    //Load main page
    header('location: sub_stages.frm.php?mode=edit&subStageId='.$_REQUEST['extSubStageId'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
    die(); 
  }
  //--------------------------------------------------------------------------------------------------------------//
  $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
  
  switch($mode){
    ////////////////////////////////////////////////////////
    case 'show':
      show(array(),array(),array('mode'=>'insert','extSubStageId'=>$_REQUEST['extSubStageId']));
    break;
    ////////////////////////////////////////////////////////
    case 'insert':
      insert();
    break;
    ////////////////////////////////////////////////////////
    case 'edit':
      // Find form info
      $info = $common['db']->pec('SELECT extTestSetId, extSubStageId, sortOrder, active FROM prod_v2_sub_stage_test_set_associations WHERE associationId=? LIMIT 1',array($_REQUEST['associationId']),'i',array('extTestSetId', 'extSubStageId', 'sortOrder', 'active'));
      $info[0]['sortOrder'] = $info[0]['sortOrder'] - 1;
      show($info[0],array(),array('associationId'=>$_REQUEST['associationId'], 'extSubStageId'=>$_REQUEST['extSubStageId'], 'mode'=>'update'));
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