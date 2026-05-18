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
        
    $page_title = 'Test Association';
    require_once('common/includes/header_inner.inc.php');
	    // Find sub stage info
	    $testSetInfo = $common['db']->pec('SELECT prod_v2_test_sets.title, prod_v2_products.title, productId FROM prod_v2_test_sets, prod_v2_products WHERE extProductId=productId AND testSetId=? LIMIT 1',array($hidden['extTestSetId']),'i',array('title', 'product', 'productId'));
            
      echo $common['window']->begin($page_title . ': ' . $testSetInfo[0]['product'] . ' ' . $testSetInfo[0]['title'],false);
        $frm = new frm($values, $errors, $hidden);
        echo $frm->begin_frm();
          echo $frm->begin_fieldset('General');
            echo $frm->begin_dl();
              // Find all tests that are not associated with this test set or is its' self on edit
              if ($hidden['mode'] == 'update') {
                $results = $common['db']->pec('SELECT testId, prod_v2_tests.title FROM prod_v2_tests, prod_v2_products WHERE extProductId=productId AND productId=? AND testId NOT IN(SELECT extTestId FROM prod_v2_test_set_test_associations WHERE extTestSetId=? AND associationId<>?) ORDER BY prod_v2_tests.title',array($testSetInfo[0]['productId'], $hidden['extTestSetId'], $hidden['associationId']),'iii',array('testId', 'testTitle'));
              } else {
                $results = $common['db']->pec('SELECT testId, prod_v2_tests.title FROM prod_v2_tests, prod_v2_products WHERE extProductId=productId AND productId=? AND testId NOT IN(SELECT extTestId FROM prod_v2_test_set_test_associations WHERE extTestSetId=?) ORDER BY prod_v2_tests.title',array($testSetInfo[0]['productId'], $hidden['extTestSetId']),'ii',array('testId', 'testTitle'));
              }
              
              $tests = array();
              foreach($results as $row){
                $tests[$row['testId']] = $row['testTitle'];
              }
              echo $frm->list_menu('extTestId', 'Test:', $tests);

              //Find all associated tests to determine sort order options
              if($hidden['mode']=='insert') {
                $results = $common['db']->pec('SELECT title, prod_v2_test_set_test_associations.sortOrder FROM prod_v2_tests, prod_v2_test_set_test_associations WHERE extTestId=testId AND extTestSetId=? ORDER BY prod_v2_test_set_test_associations.sortOrder',array($hidden['extTestSetId']),'i',array('title', 'sortOrder'));
              } else {
                $results = $common['db']->pec('SELECT title, prod_v2_test_set_test_associations.sortOrder FROM prod_v2_tests, prod_v2_test_set_test_associations WHERE extTestId=testId AND extTestSetId=? AND associationId<>? ORDER BY prod_v2_test_set_test_associations.sortOrder',array($hidden['extTestSetId'], $hidden['associationId']),'ii',array('title', 'sortOrder'));
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
              echo '<dd><a href="test_sets.frm.php?mode=edit&testSetId='.$hidden['extTestSetId'].'&selected_tab=1" title="Cancel" class="button">Cancel</a></dd>';
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
        
    if(empty($_POST['extTestId'])){
	    $errors['extTestId']=array('Test','Please select a test');
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
      $affected = $common['db']->pec('UPDATE prod_v2_test_set_test_associations SET sortOrder=sortOrder+1 WHERE sortOrder>? AND extTestSetId=?',array($_POST['sortOrder'], $_POST['extTestSetId']),'ii');
      if(!$affected){ $success = false; }

      // Create parent object
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_test_set_test_associations SET extTestSetId=?, extTestId=?, sortOrder=?, active=?',
        array($_POST['extTestSetId'], $_POST['extTestId'], $_POST['sortOrder'] + 1, $_POST['active']),
        'iiii'
      );
      if(!$affected){ $success = false; }

      // Get new ID
      $newId = $common['db']->last_insert_id();
            
      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_test_set_test_associations_archive SET associationId=?, extTestSetId=?, extTestId=?, sortOrder=?, active=?, generatedDateTime=NOW()',
        array($newId, $_POST['extTestSetId'], $_POST['extTestId'], $_POST['sortOrder'] + 1, $_POST['active']),
        'iiiii'
      );
      if(!$affected){ $success=false; }
            
      //Create log entry
	    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Test to Test Set", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
      $state = $common['db']->end_transaction($success)?'success':'fail';
	    $msg=$affected?'Test was successfully associated':'Test was NOT successfully associated';
		
	    //Load main page
	    header('location: test_sets.frm.php?mode=edit&testSetId='.$_POST['extTestSetId'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
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
      $testInfo = $common['db']->pec('SELECT sortOrder FROM prod_v2_test_set_test_associations WHERE associationId=? LIMIT 1',array($_POST['associationId']),'i', array('sortOrder'));

      //Collapse old location
      $affected = $common['db']->pec('UPDATE prod_v2_test_set_test_associations SET sortOrder=sortOrder-1 WHERE sortOrder>? AND extTestSetId=?',array($testInfo[0]['sortOrder'], $_POST['extTestSetId']),'ii');
      if(!$affected){ $success=false; }

      if ($testInfo[0]['sortOrder'] < $_POST['sortOrder']){
        $newSortOrder = $_POST['sortOrder'] - 1;
      } else {
        $newSortOrder = $_POST['sortOrder'];
      }

      //Open new location
      $affected = $common['db']->pec('UPDATE prod_v2_test_set_test_associations SET sortOrder=sortOrder+1 WHERE sortOrder>? AND extTestSetId=?',array($newSortOrder, $_POST['extTestSetId']),'ii');
      if(!$affected){ $success=false; }

      // Update main entry
      $affected = $common['db']->pec(
        'UPDATE prod_v2_test_set_test_associations SET extTestSetId=?, extTestId=?, sortOrder=?, active=? WHERE associationId=? LIMIT 1',
        array($_POST['extTestSetId'], $_POST['extTestId'], $newSortOrder + 1, $_POST['active'], $_POST['associationId']),
        'iiiii'
      );
      if(!$affected){ $success=false; }

      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_test_set_test_associations_archive SET associationId=?, extTestSetId=?, extTestId=?, sortOrder=?, active=?, generatedDateTime=NOW()',
        array($_POST['associationId'], $_POST['extTestSetId'], $_POST['extTestId'], $_POST['sortOrder'] + 1, $_POST['active']),
        'iiiii'
      );
      if(!$affected){ $success=false; }

      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Test Association", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');

      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Test Association was successfully updated':'Test Association was NOT successfully updated';
            
      //Load main page
      header('location: test_sets.frm.php?mode=edit&testSetId='.$_POST['extTestSetId'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
      die();
    }
  }
  //--------------------------------------------------------------------------------------------------------------//
  function delete(){
    global $common;

    $success = true;
    $common['db']->start_transaction();

    // Find where test is currently sorted at
    $testInfo = $common['db']->pec('SELECT sortOrder, extTestSetId FROM prod_v2_test_set_test_associations WHERE associationId=? LIMIT 1',array($_REQUEST['associationId']),'i', array('sortOrder', 'extTestSetId'));
        
    $affected=$common['db']->pec('DELETE FROM prod_v2_test_set_test_associations WHERE associationId=? LIMIT 1',array($_REQUEST['associationId']),'i');
    if(!$affected){ $success=false; }

    //Collapse old location
    $affected = $common['db']->pec('UPDATE prod_v2_test_set_test_associations SET sortOrder=sortOrder-1 WHERE sortOrder>? AND extTestSetId=?',array($testInfo[0]['sortOrder'], $testInfo[0]['extTestSetId']),'ii');
    if(!$affected){ $success=false; }

    //Create log entry
	  $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Deleted Test from Test Set", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
    $state = $common['db']->end_transaction($success)?'success':'fail';
	  $msg=$affected?'Test was successfully removed':'Test was NOT successfully removed';
		
    //Load main page
    header('location: test_sets.frm.php?mode=edit&testSetId='.$_REQUEST['extTestSetId'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
    die(); 
  }
  //--------------------------------------------------------------------------------------------------------------//
  function showDuplicate($values,$errors,$hidden){
    global $common;
    
    $page_title='Test Association';
    require_once('common/includes/header_inner.inc.php');
    //find test set info
    $testSetInfo=$common['db']->pec('SELECT title, extProductId FROM prod_v2_test_sets WHERE testSetId=? LIMIT 1',array($hidden['extTestSetId']),'i',array('title', 'extProductId'));
        
    echo $common['window']->begin($page_title . ': ' . $testSetInfo[0]['title'],false);
      $frm=new frm($values,$errors,$hidden);
      echo $frm->begin_frm();
        echo $frm->begin_fieldset('General Information');
          echo $frm->begin_dl();

              //Find all other similar test sets
              $results = $common['db']->pec('SELECT testSetId, title FROM prod_v2_test_sets WHERE extProductId=? AND testSetId<>? ORDER BY title',array($testSetInfo[0]['extProductId'], $hidden['extTestSetId']),'ii',array('testSetId', 'title'));
              $availableTestSets = array();
              foreach($results as $row) {
                $availableTestSets[$row['testSetId']] = $row['title'];
              }
              echo $frm->list_menu('copyTestSetId','Test set to copy from:', $availableTestSets);

              echo $frm->end_dl();
            echo $frm->end_fieldset();
  
                echo $frm->begin_fieldset('');
                    echo $frm->begin_dl('submit');
                        echo '<dd><a href="test_sets.frm.php?mode=edit&testSetId='.$hidden['extTestSetId'].'&selected_tab=1" title="Cancel" class="button">Cancel</a></dd>';
                        echo $frm->submit('submit','Submit','submit');
                    echo $frm->end_dl();    
                echo $frm->end_fieldset();
            echo $frm->end_frm();
        echo $common['window']->end();    
    require_once('common/includes/footer_inner.inc.php');
}
//--------------------------------------------------------------------------------------------------------------//
function performDuplication(){
    global $common;

    //Check for errors
    $errors=array();
    if(empty($_POST['copyTestSetId'])){
        $errors['copyTestSetId']=array('Test set to copy from','Please select a test set');
    }

    if(!empty($errors) || !$common['security']->verify_frm()){
        //Errors found
        showDuplicate($_POST,$errors,array('mode'=>'insert','extTestSetId'=>$_POST['extTestSetId']));
    }else{
        $success=true;
        $testSetId=$_POST['extTestSetId'];

        $common['db']->start_transaction();

        //Make sure there are no tests currently associated
        $affected = $common['db']->pec('DELETE FROM prod_v2_test_set_test_associations WHERE extTestSetId=?',array($_POST['extTestSetId']),'i');
        if(!$affected){ $success=false; }

        if($success) {
          //Get all test info from other Test set
          $results = $common['db']->pec('SELECT extTestId, sortOrder, active FROM prod_v2_test_set_test_associations WHERE extTestSetId=?',array($_POST['copyTestSetId']),'i',array('extTestId', 'sortOrder', 'active'));
          foreach($results as $row) {

            // Insert new test association
            $affected = $common['db']->pec('INSERT INTO prod_v2_test_set_test_associations SET extTestSetId=?, extTestId=?, sortOrder=?, active=?',array($_POST['extTestSetId'],$row['extTestId'],$row['sortOrder'],$row['active']),'iiii');
            if(!$affected){ $success=false; }

            // Insert new archive record
            $lastId = $common['db']->last_insert_id();
            $affected = $common['db']->pec('INSERT INTO prod_v2_test_set_test_associations_archive SET extTestSetId=?, extTestId=?, sortOrder=?, active=?, associationId=?, generatedDateTime=NOW()',array($_POST['extTestSetId'],$row['extTestId'],$row['sortOrder'],$row['active'], $lastId),'iiiii');
            if(!$affected){ $success=false; }
          }
        }
        
        //Create log entry
        $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Duplicated test set", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
        
        $state=$common['db']->end_transaction($success)?'success':'fail';
        $msg=$affected?'Tests were successfully associated':'Tests were NOT successfully associated';
        
        //Load edit again with second tab selected
        header('location: test_sets.frm.php?mode=edit&selected_tab=1&testSetId='.$testSetId.'&msg_state=' . $state . '&msg=' . $msg);
        die();
    }

}
//--------------------------------------------------------------------------------------------------------------//
  $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
  
  switch($mode){
    ////////////////////////////////////////////////////////
    case 'show':
      show(array(),array(),array('mode'=>'insert','extTestSetId'=>$_REQUEST['extTestSetId']));
    break;
    ////////////////////////////////////////////////////////
    case 'insert':
      insert();
    break;
    ////////////////////////////////////////////////////////
    case 'edit':
      // Find form info
      $info = $common['db']->pec('SELECT extTestSetId, extTestId, sortOrder, active FROM prod_v2_test_set_test_associations WHERE associationId=? LIMIT 1',array($_REQUEST['associationId']),'i',array('extTestSetId', 'extTestId', 'sortOrder', 'active'));
      $info[0]['sortOrder'] = $info[0]['sortOrder'] - 1;
      show($info[0],array(),array('associationId'=>$_REQUEST['associationId'], 'extTestSetId'=>$_REQUEST['extTestSetId'], 'mode'=>'update'));
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
    case 'duplicate':
      showDuplicate(array(),array(),array('mode'=>'performDuplication','extTestSetId'=>$_REQUEST['extTestSetId']));
    break;
    ////////////////////////////////////////////////////////
    case 'performDuplication':
        performDuplication();
    break;
    ////////////////////////////////////////////////////////
  }
}
?>