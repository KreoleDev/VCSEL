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

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(1)){
  //--------------------------------------------------------------------------------------------------------------//
  function show($values, $errors, $hidden){
    global $common;
        
    $page_title = 'Sub Stage Association';
    require_once('common/includes/header_inner.inc.php');
	    // Find test set info
	    $testSetInfo = $common['db']->pec('SELECT title FROM prod_v2_test_sets WHERE testSetId=? LIMIT 1',array($hidden['extTestSetId']),'i',array('title'));
            
      echo $common['window']->begin($page_title . ': ' . $testSetInfo[0]['title'],false);
        $frm = new frm($values, $errors, $hidden);
        echo $frm->begin_frm();
          echo $frm->begin_fieldset('General');
            echo $frm->begin_dl();
              // Find all sub stages that are not associated with this test set or is its' self on edit
              if ($hidden['mode'] == 'update') {
                $results = $common['db']->pec('SELECT subStageId, prod_v2_products.title, prod_v2_sub_stages.title FROM prod_v2_sub_stages, prod_v2_products WHERE extProductId=productId AND subStageId NOT IN(SELECT extSubStageId FROM prod_v2_test_set_sub_stage_associations WHERE extTestSetId=? AND associationId<>?) ORDER BY prod_v2_sub_stages.title',array($hidden['extTestSetId'], $hidden['associationId']),'ii',array('subStageId', 'productTitle', 'subStageTitle'));
              } else {
                $results = $common['db']->pec('SELECT subStageId, prod_v2_products.title, prod_v2_sub_stages.title FROM prod_v2_sub_stages, prod_v2_products WHERE extProductId=productId AND subStageId NOT IN(SELECT extSubStageId FROM prod_v2_test_set_sub_stage_associations WHERE extTestSetId=?) ORDER BY prod_v2_sub_stages.title',array($hidden['extTestSetId']),'i',array('subStageId', 'productTitle', 'subStageTitle'));
              }
              
              $subStages = array();
              foreach($results as $row){
                $subStages[$row['subStageId']] = $row['productTitle'] . ': ' . $row['subStageTitle'];
              }
              echo $frm->list_menu('extSubStageId', 'Sub Stage:', $subStages);

              //Find all associated sub stages to determine sort order options
              if($hidden['mode']=='insert') {
                $results = $common['db']->pec('SELECT title, prod_v2_test_set_sub_stage_associations.sortOrder FROM prod_v2_sub_stages, prod_v2_test_set_sub_stage_associations WHERE extSubStageId=subStageId AND extTestSetId=? ORDER BY prod_v2_test_set_sub_stage_associations.sortOrder',array($hidden['extTestSetId']),'i',array('title', 'sortOrder'));
              } else {
                $results = $common['db']->pec('SELECT title, prod_v2_test_set_sub_stage_associations.sortOrder FROM prod_v2_sub_stages, prod_v2_test_set_sub_stage_associations WHERE extSubStageId=subStageId AND extTestSetId=? AND associationId<>? ORDER BY prod_v2_test_set_sub_stage_associations.sortOrder',array($hidden['extTestSetId'], $hidden['associationId']),'ii',array('title', 'sortOrder'));
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
              echo '<dd><a href="test_sets.frm.php?mode=edit&testSetId='.$hidden['extTestSetId'].'&selected_tab=2" title="Cancel" class="button">Cancel</a></dd>';
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
        
    if(empty($_POST['extSubStageId'])){
	    $errors['extSubStageId']=array('Sub Stage','Please select a sub stage');
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
	    show($_POST,$errors,array('extTestSetId'=>$_POST['extTestSetId'],'mode'=>'insert'));
	  }else{
      $success = true;
      $common['db']->start_transaction();

      // Open sort window for entry
      $affected = $common['db']->pec('UPDATE prod_v2_test_set_sub_stage_associations SET sortOrder=sortOrder+1 WHERE sortOrder>? AND extTestSetId=?',array($_POST['sortOrder'], $_POST['extTestSetId']),'ii');
      if(!$affected){ $success = false; }

      // Create parent object
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_test_set_sub_stage_associations SET extTestSetId=?, extSubStageId=?, sortOrder=?, active=?',
        array($_POST['extTestSetId'], $_POST['extSubStageId'], $_POST['sortOrder'] + 1, $_POST['active']),
        'iiii'
      );
      if(!$affected){ $success = false; }

      // Get new ID
      $newId = $common['db']->last_insert_id();
            
      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_test_set_sub_stage_associations_archive SET associationId=?, extTestSetId=?, extSubStageId=?, sortOrder=?, active=?, generatedDateTime=NOW()',
        array($newId, $_POST['extTestSetId'], $_POST['extSubStageId'], $_POST['sortOrder'] + 1, $_POST['active']),
        'iiiii'
      );
      if(!$affected){ $success=false; }
            
      //Create log entry
	    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Sub Stage To Test Set", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
      $state = $common['db']->end_transaction($success)?'success':'fail';
	    $msg=$affected?'Sub Stage was successfully associated':'Sub Stage was NOT successfully associated';
		
	    //Load main page
	    header('location: test_sets.frm.php?mode=edit&testSetId='.$_POST['extTestSetId'].'&selected_tab=2&msg_state=' . $state . '&msg=' . $msg);
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
      show($_POST,$errors,array('associationId'=>$_POST['associationId'], 'extTestSetId'=>$_POST['extTestSetId'], 'mode'=>'update'));
    }else{
      //Perform update
      $success = true;
      $common['db']->start_transaction();
      
      // Find where sub stage is currently sorted at
      $subStageInfo = $common['db']->pec('SELECT sortOrder FROM prod_v2_test_set_sub_stage_associations WHERE associationId=? LIMIT 1',array($_POST['associationId']),'i', array('sortOrder'));
      $imgFilename = $productInfo[0]['imgFilename'];

      //Collapse old location
      $affected = $common['db']->pec('UPDATE prod_v2_test_set_sub_stage_associations SET sortOrder=sortOrder-1 WHERE sortOrder>? AND extTestSetId=?',array($subStageInfo[0]['sortOrder'], $_POST['extTestSetId']),'ii');
      if(!$affected){ $success=false; }

      if ($subStageInfo[0]['sortOrder'] < $_POST['sortOrder']){
        $newSortOrder = $_POST['sortOrder'] - 1;
      } else {
        $newSortOrder = $_POST['sortOrder'];
      }

      //Open new location
      $affected = $common['db']->pec('UPDATE prod_v2_test_set_sub_stage_associations SET sortOrder=sortOrder+1 WHERE sortOrder>? AND extTestSetId=?',array($newSortOrder, $_POST['extTestSetId']),'ii');
      if(!$affected){ $success=false; }

      // Update main entry
      $affected = $common['db']->pec(
        'UPDATE prod_v2_test_set_sub_stage_associations SET extTestSetId=?, extSubStageId=?, sortOrder=?, active=? WHERE associationId=? LIMIT 1',
        array($_POST['extTestSetId'], $_POST['extSubStageId'], $newSortOrder + 1, $_POST['active'], $_POST['associationId']),
        'iiiii'
      );
      if(!$affected){ $success=false; }

      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_test_set_sub_stage_associations_archive SET associationId=?, extTestSetId=?, extSubStageId=?, sortOrder=?, active=?, generatedDateTime=NOW()',
        array($_POST['associationId'], $_POST['extTestSetId'], $_POST['extSubStageId'], $_POST['sortOrder'] + 1, $_POST['active']),
        'iiiii'
      );
      if(!$affected){ $success=false; }

      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Sub Stage Association", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');

      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Sub Stage Association was successfully updated':'Sub Stage Association was NOT successfully updated';
            
      //Load main page
      header('location: test_sets.frm.php?mode=edit&testSetId='.$_POST['extTestSetId'].'&selected_tab=2&msg_state=' . $state . '&msg=' . $msg);
      die();
    }
  }
  //--------------------------------------------------------------------------------------------------------------//
  function delete(){
    global $common;
        
        /*$affected=$common['db']->pec('DELETE FROM core_user_group_lookup WHERE ext_user_id=? AND ext_group_id=? LIMIT 1',array($_REQUEST['user_id'],$_REQUEST['group_id']),'ii');
            
        //Create log entry
	$common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Deleted User From Group", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
        $state=$affected?'success':'fail';
	$msg=$affected?'Group was successfully removed':'Group was NOT successfully removed';*/
		
    //Load main page
    header('location: users.frm.php?mode=edit&user_id='.$_REQUEST['user_id'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
    die(); 
  }
  //--------------------------------------------------------------------------------------------------------------//
  $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
  
  switch($mode){
    ////////////////////////////////////////////////////////
    case 'show':
      show(array(),array(),array('mode'=>'insert','extTestSetId'=>$_REQUEST['testSetId']));
    break;
    ////////////////////////////////////////////////////////
    case 'insert':
      insert();
    break;
    ////////////////////////////////////////////////////////
    case 'edit':
      // Find form info
      $info = $common['db']->pec('SELECT extTestSetId, extSubStageId, sortOrder, active FROM prod_v2_test_set_sub_stage_associations WHERE associationId=? LIMIT 1',array($_REQUEST['associationId']),'i',array('extTestSetId', 'extSubStageId', 'sortOrder', 'active'));
      $info[0]['sortOrder'] = $info[0]['sortOrder'] - 1;
      show($info[0],array(),array('associationId'=>$_REQUEST['associationId'], 'extTestSetId'=>$_REQUEST['testSetId'], 'mode'=>'update'));
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