<?php
//Developer:    Charles Palmer
//Created:      2022.09.27
//Revision:     2022.10.05
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     View
    [1]     Manage
*/

/*
*  2022.10.05  CP  Added ability to duplicate a test set
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(1)){
  //--------------------------------------------------------------------------------------------------------------//
  function show($values,$errors,$hidden){
    global $common;

    $additional_head='
      <script>
        const loadConfigOptions = function(productId, configureProduct){
          // Clear view first
          const container = document.getElementById("configProductWrapper");
          container.innerHTML = "";
          
          let xmlhttp = new XMLHttpRequest();
          xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
              container.innerHTML = this.responseText;
            }
          };
          xmlhttp.open("GET", "configure_product_options.php?extProductId=" + productId + "&configureProduct=" + configureProduct + "' . ($hidden['mode']=='update' ? '&testSetId=' . $hidden['testSetId'] : '') . '", true);
          xmlhttp.send();
        }

        $(document).ready(function(){
          const productEl = document.getElementById("extProductId");
          const configureEl = document.getElementById("configureProduct0");
          const configureEl2 = document.getElementById("configureProduct1");
          productEl.addEventListener("change",() => {
            loadConfigOptions(productEl.value, configureEl2.checked);
          }, false);

          configureEl.addEventListener("change",() => {
            loadConfigOptions(productEl.value, configureEl2.checked);
          }, false);

          configureEl2.addEventListener("change",() => {
            loadConfigOptions(productEl.value, configureEl2.checked);
          }, false);

          if(productEl.value > 0 && configureEl2.checked) {
            loadConfigOptions(productEl.value, configureEl2.checked);
          }
        });
      </script>
    ';
        
    $page_title='Test Sets';
    require_once('common/includes/header_inner.inc.php');
      echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['title']:''),false);
        $tabs=array();
                
        $frm=new frm($values,$errors,$hidden);
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
        $tabs[0]['title']='General';
        $tabs[0]['content']=$frm->begin_frm('','','multipart/form-data');
          $tabs[0]['content'].=$frm->begin_fieldset('General Information');
            $tabs[0]['content'].=$frm->begin_dl();

              // Get Products
              $results = $common['db']->pec('SELECT productId, title FROM prod_v2_products ORDER BY title', array(), '', array('productId', 'title'));
              $productOptions=array();
              foreach($results as $row) {
                $productOptions[$row['productId']]=$row['title'];
              }
              $tabs[0]['content'].=$frm->list_menu('extProductId', 'Product:', $productOptions);

              $tabs[0]['content'].=$frm->text('title','Title:');

              // Get Templates
              $results = $common['db']->pec('SELECT templateId, title FROM prod_v2_templates ORDER BY title', array(), '', array('templateId', 'title'));
              $options=array();
              foreach($results as $row) {
                $options[$row['templateId']]=$row['title'];
              }
              $tabs[0]['content'].=$frm->list_menu('extTemplateId','Template:',$options);

              // Get APIs
              $results = $common['db']->pec('SELECT apiId, title FROM prod_v2_apis ORDER BY title', array(), '', array('apiId', 'title'));
              $apiOptions=array();
              foreach($results as $row) {
                $apiOptions[$row['apiId']]=$row['title'];
              }
              $tabs[0]['content'].=$frm->list_menu('extApiId','API:',$apiOptions);

              $tabs[0]['content'].=$frm->textarea('description','Description (HTML):', false);
              $tabs[0]['content'].=$frm->textarea('script','Script (Javascript):', false);

              $tabs[0]['content'].=$frm->radio_group('active','Active?:',array('0'=>'No','1'=>'Yes'),true,'','1');
              $tabs[0]['content'].=$frm->radio_group('configureProduct','Configure Product?:',array('0'=>'No','1'=>'Yes'),true,'','0');

            $tabs[0]['content'].=$frm->end_dl();
          $tabs[0]['content'].=$frm->end_fieldset();

          $tabs[0]['content'].='<div id="configProductWrapper"></div>';
                            
          $tabs[0]['content'].=$frm->begin_fieldset('');
            $tabs[0]['content'].=$frm->begin_dl('submit');
              $tabs[0]['content'].='<dd><a href="../index.php" title="Cancel" class="button">Cancel</a></dd>';
              $tabs[0]['content'].=$frm->submit('submit','Submit','submit');
            $tabs[0]['content'].=$frm->end_dl();    
          $tabs[0]['content'].=$frm->end_fieldset();
        $tabs[0]['content'].=$frm->end_frm();
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
        if ($hidden['mode']=='update'){
          $tabs[1]['title']='Tests To Run';
          $tabs[1]['content']='<p><a class="button" href="tests_association.frm.php?extTestSetId='.$hidden['testSetId'].'" title="Add Test">Add Test</a>&nbsp;&nbsp;&nbsp;<a class="button" href="tests_association.frm.php?mode=duplicate&extTestSetId='.$hidden['testSetId'].'" title="Duplicate Tests From Different Test Set">Duplicate Tests From Different Test Set</a></p>';
          $tabs[1]['content'].=$common['table']->begin(array('Sort Order', 'Test','Active', '&nbsp;','&nbsp;'),'sortable',array('','no_sort', 'no_sort'));

          $results = $common['db']->pec('SELECT associationId, prod_v2_tests.title, prod_v2_test_set_test_associations.sortOrder, prod_v2_test_set_test_associations.active FROM prod_v2_test_set_test_associations, prod_v2_tests WHERE extTestSetId = ? AND extTestId = testId ORDER BY prod_v2_test_set_test_associations.sortOrder',array($hidden['testSetId']), 'i', array('associationId', 'Title', 'sortOrder', 'active'));
          foreach($results as $row) {
            $tabs[1]['content'].=$common['table']->add_row(
              array(
                $row['sortOrder'], 
                $row['Title'], 
                $row['active']?'Yes':'No', 
                '<a href="tests_association.frm.php?mode=edit&extTestSetId='.$hidden['testSetId'].'&associationId='.$row['associationId'].'" title="Edit Test">Edit</a>', 
                '<a href="tests_association.frm.php?mode=delete&extTestSetId='.$hidden['testSetId'].'&associationId='.$row['associationId'].'" title="Delete Test" class="include_alert">Delete</a>'
              ), array('','','center','center','center'));
          }

          $tabs[1]['content'].=$common['table']->end();
          // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
        }

        echo $common['tabs']->create_tabs($tabs,isset($_REQUEST['selected_tab'])?$_REQUEST['selected_tab']:0);
		    echo '<p class="bottom_options"><a href="../index.php" title="Back" class="button">Back</a></p>';
      echo $common['window']->end();
    require_once('common/includes/footer_inner.inc.php');
  }
  //--------------------------------------------------------------------------------------------------------------//
  function validate(){
		global $common;
    $errors = array();

    if(empty($_POST['extProductId'])){
	    $errors['extProductId'] = array('Product', 'Please select a product');
		}
		
		if(empty($_POST['title'])){
	    $errors['title'] = array('Title', 'Please include a title');
		}

    if(empty($_POST['extTemplateId'])){
	    $errors['extTemplateId'] = array('Template', 'Please select a template');
		}

    if(empty($_POST['extApiId'])){
	    $errors['extApiId'] = array('API', 'Please select an API');
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
        'INSERT INTO prod_v2_test_sets SET extProductId=?, title=?, extTemplateId=?, extApiId=?, description=?, script=?, active=?, configureProduct=?',
        array($_POST['extProductId'], $_POST['title'], $_POST['extTemplateId'], $_POST['extApiId'], $_POST['description'], $_POST['script'], $_POST['active'], $_POST['configureProduct']),
        'isiissii'
      );
      if(!$affected){ $success = false; }

      // Get new ID
      $newId = $common['db']->last_insert_id();

      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_test_sets_archive SET testSetId=?, extProductId=?, title=?, extTemplateId=?, extApiId=?, description=?, script=?, active=?, configureProduct=?, generatedDateTime=NOW()',
        array($newId, $_POST['extProductId'], $_POST['title'], $_POST['extTemplateId'], $_POST['extApiId'], $_POST['description'], $_POST['script'], $_POST['active'], $_POST['configureProduct']),
        'iisiissii'
      );
      if(!$affected){ $success=false; }

      // Get archive ID
      $archiveId = $common['db']->last_insert_id();

      // Add config info if set
      if ($_POST['configureProduct']) {
        // Prepare config object
        $cfgObj = [];

        $results = $common['db']->pec('SELECT variableName FROM prod_v2_product_config_parameters WHERE extProductId=?', [$_POST['extProductId']], 'i', ['variableName']);
        foreach($results as $row) {
          if(isset($_POST[$row['variableName']])) {
            $cfgObj[$row['variableName']] = $_POST[$row['variableName']];
          }
        }

        $affected = $common['db']->pec(
          'UPDATE prod_v2_test_sets SET extFirmwareId=?, productConfig=? WHERE testSetId=? LIMIT 1',
          array($_POST['extFirmwareId'], json_encode($cfgObj), $newId),
          'isi'
        );
        if(!$affected){ $success = false; }

        $affected = $common['db']->pec(
          'UPDATE prod_v2_test_sets_archive SET extFirmwareId=?, productConfig=? WHERE archiveId=? LIMIT 1',
          array($_POST['extFirmwareId'], json_encode($cfgObj), $archiveId),
          'isi'
        );
        if(!$affected){ $success = false; }
      }
            
      // Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Test Set", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Test Set was successfully created':'Test Set was NOT successfully created';
            
      //Load edit again with second tab selected
      header('location: test_sets.frm.php?msg_state=' . $state . '&msg=' . $msg . '&selected_tab=1&mode=edit&testSetId=' . $newId);
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
      show($_POST,$errors,array('testSetId'=>$_POST['testSetId'],'mode'=>'update'));
    }else{
      //Perform update
      $success = true;
      $common['db']->start_transaction();

      $cfgObj = [];
      $extFirmwareId = isset($_POST['extFirmwareId']) ? $_POST['extFirmwareId'] : 0;

      // Add config info if set
      if ($_POST['configureProduct']) {
        $results = $common['db']->pec('SELECT variableName FROM prod_v2_product_config_parameters WHERE extProductId=?', [$_POST['extProductId']], 'i', ['variableName']);
        foreach($results as $row) {
          if(isset($_POST[$row['variableName']])) {
            $cfgObj[$row['variableName']] = $_POST[$row['variableName']];
          }
        }
      }

      // Update product
      $affected = $common['db']->pec(
        'UPDATE prod_v2_test_sets SET extProductId=?, title=?, extTemplateId=?, extApiId=?, description=?, script=?, active=?, configureProduct=?, extFirmwareId=?, productConfig=?  WHERE testSetId=? LIMIT 1',
        array($_POST['extProductId'], $_POST['title'], $_POST['extTemplateId'], $_POST['extApiId'], $_POST['description'], $_POST['script'], $_POST['active'], $_POST['configureProduct'], $extFirmwareId, json_encode($cfgObj), $_POST['testSetId']),
        'isiissiiisi'
      );
      if(!$affected){ $success=false; }

      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_test_sets_archive SET testSetId=?, extProductId=?, title=?, extTemplateId=?, extApiId=?, description=?, script=?, active=?, configureProduct=?, generatedDateTime=NOW(), extFirmwareId=?, productConfig=?',
        array($_POST['testSetId'], $_POST['extProductId'], $_POST['title'], $_POST['extTemplateId'], $_POST['extApiId'], $_POST['description'], $_POST['script'], $_POST['active'], $_POST['configureProduct'], $extFirmwareId, json_encode($cfgObj)),
        'iisiissiiis'
      );
      if(!$affected){ $success=false; }

      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Test Set", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');

      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Test Set was successfully updated':'Test Set was NOT successfully updated';
            
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
      $info = $common['db']->pec('SELECT extProductId, title, extTemplateId, extApiId, description, script, active, configureProduct FROM prod_v2_test_sets WHERE testSetId=? LIMIT 1',array($_REQUEST['testSetId']),'i',array('extProductId', 'title', 'extTemplateId', 'extApiId', 'description', 'script', 'active', 'configureProduct'));
      show($info[0],array(),array('testSetId'=>$_REQUEST['testSetId'],'mode'=>'update'));
    break;
    ////////////////////////////////////////////////////////
    case 'update':
      update();
    break;
    ////////////////////////////////////////////////////////
  }
}
?>