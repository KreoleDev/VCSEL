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
        
    $page_title='Sub Stages';

    $urlPath = 'sortOrderOptions.php?';
    if ($hidden['mode']=='update'){
      $urlPath.='subStageId='.$hidden['subStageId'].'&';
    }

    $additional_head='
      <script>
        const loadSortOrderOptions = function(productId){
          let xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
              if (this.readyState == 4 && this.status == 200) {
                document.getElementById("sortOrder").innerHTML = this.responseText;
              }
            };
            xmlhttp.open("GET", "'.$urlPath.'productId="+productId, true);
            xmlhttp.send();
        }

        $(document).ready(function(){
          const productEl = document.getElementById("extProductId");
          productEl.addEventListener("change",() => {
            loadSortOrderOptions(productEl.value);
            
          }, false);
        });
      </script>
    ';

    require_once('common/includes/header_inner.inc.php');
      echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['title']:''),false);
        $tabs=array(); 
        $frm=new frm($values,$errors,$hidden);
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
        $tabs[0]['title']='General';
        $tabs[0]['content']=$frm->begin_frm('','','multipart/form-data');
          $tabs[0]['content'].=$frm->begin_fieldset('General Information');
            $tabs[0]['content'].=$frm->begin_dl();
            // Product List  
            $results = $common['db']->pec('SELECT productId, title FROM prod_v2_products ORDER BY title', array(), '', array('productId', 'title'));
              $products=array();
              foreach($results as $row) {
                $products[$row['productId']]=$row['title'];
              }
              $tabs[0]['content'].=$frm->list_menu('extProductId','Product:',$products);

              $tabs[0]['content'].=$frm->text('title','Title:');

              // Templates List
              $results = $common['db']->pec('SELECT templateId, title FROM prod_v2_templates ORDER BY title', array(), '', array('templateId', 'title'));
              $options=array();
              foreach($results as $row) {
                $options[$row['templateId']]=$row['title'];
              }
              $tabs[0]['content'].=$frm->list_menu('extTemplateId','Template:',$options);

              $tabs[0]['content'].=$frm->textarea('instructions','Instructions (HTML):', false);

              if ($hidden['mode']=='update'){
                $tabs[0]['content'].='<div style="height:100px; width: 100px; border:1px solid #aeaeae; background-color: #ffffff; margin: 5px 0px 0px 0px; padding: 5px; text-align: center;">';
                  $tabs[0]['content'].='<img src="'.CFG_CMS_BASE_URL.'production/v2/tests/uploads/sub_stages/'.$values['imgFilename'].'" style="max-height: 100px; max-width: 100px;" />';
                $tabs[0]['content'].='</div>';
              }
              $tabs[0]['content'].=$frm->upload('image','Icon (128x128 pixel png):',($hidden['mode']=='update' ? false : true));

              // Load sort order options on edit
              $sortOptions = array();
              if ($hidden['mode']=='update'){
                $sortOptions['-1'] = '=== TOP LEVEL ===';
                $results = $common['db']->pec('SELECT sortOrder, title FROM prod_v2_sub_stages WHERE extProductId = ? AND subStageId<>? ORDER BY sortOrder', array($values['extProductId'], $hidden['subStageId']),'ii', array('sortOrder', 'title'));
                foreach($results as $row) {
                  $sortOptions[$row['sortOrder']]=$row['title'];
                }
              }

              $tabs[0]['content'].=$frm->list_menu('sortOrder', 'Place After:', $sortOptions);

              $tabs[0]['content'].=$frm->radio_group('active','Active?:',array('0'=>'No','1'=>'Yes'),true,'','1');

            $tabs[0]['content'].=$frm->end_dl();
          $tabs[0]['content'].=$frm->end_fieldset();
                            
          $tabs[0]['content'].= $frm->begin_fieldset('');
            $tabs[0]['content'].=$frm->begin_dl('submit');
              $tabs[0]['content'].='<dd><a href="../index.php" title="Cancel" class="button">Cancel</a></dd>';
              $tabs[0]['content'].=$frm->submit('submit','Submit','submit');
            $tabs[0]['content'].=$frm->end_dl();    
          $tabs[0]['content'].=$frm->end_fieldset();
        $tabs[0]['content'].=$frm->end_frm();
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
        if ($hidden['mode'] == 'update') {
          $tabs[1]['title'] = 'Test Sets';
          $tabs[1]['content']='<p><a class="button" href="test_sets_association.frm.php?extSubStageId='.$hidden['subStageId'].'" title="Add Test Set">Add Test Set</a></p>';
          $tabs[1]['content'].=$common['table']->begin(array('Sort Order', 'Test Set','Active', '&nbsp;','&nbsp;'),'sortable',array('','no_sort', 'no_sort'));

          $results = $common['db']->pec('SELECT associationId, prod_v2_test_sets.title, prod_v2_sub_stage_test_set_associations.sortOrder, prod_v2_sub_stage_test_set_associations.active FROM prod_v2_sub_stage_test_set_associations, prod_v2_test_sets WHERE extSubStageId = ? AND extTestSetId = testSetId ORDER BY prod_v2_sub_stage_test_set_associations.sortOrder',array($hidden['subStageId']), 'i', array('associationId', 'subStageTitle', 'sortOrder', 'active'));
          foreach($results as $row) {
            $tabs[1]['content'].=$common['table']->add_row(
              array(
                $row['sortOrder'], 
                $row['subStageTitle'], 
                $row['active']?'Yes':'No', 
                '<a href="test_sets_association.frm.php?mode=edit&extSubStageId='.$hidden['subStageId'].'&associationId='.$row['associationId'].'" title="Edit Test Set">Edit</a>', 
                '<a href="test_sets_association.frm.php?mode=delete&extSubStageId='.$hidden['subStageId'].'&associationId='.$row['associationId'].'" title="Delete Test Set" class="include_alert">Delete</a>'
              ), array('','','center','center','center'));
          }

          $tabs[1]['content'].=$common['table']->end();
        }
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
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

    if($_POST['mode'] == 'insert'){
      if(empty($_FILES['image']['name'])){
        $errors['image'] = array('Icon', 'Please select an icon');
      }
    }

    if(empty($_POST['sortOrder']) && $_POST['sortOrder'] != '0'){
      $errors['sortOrder'] = array('Place After', 'Please select a sort order');
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

      // Create directory for icons if not already there
			if(!is_dir(CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/sub_stages/')){
        mkdir(CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/sub_stages/',0777,true);
      }

      // Open sort window for entry
      $affected = $common['db']->pec('UPDATE prod_v2_sub_stages SET sortOrder=sortOrder+1 WHERE sortOrder>? AND extProductId=?',array($_POST['sortOrder'], $_POST['extProductId']),'ii');
      if(!$affected){ $success = false; }

      // Create parent object
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_sub_stages SET title=?, extProductId=?, extTemplateId=?, instructions=?, sortOrder=?, active=?',
        array($_POST['title'], $_POST['extProductId'], $_POST['extTemplateId'], $_POST['instructions'], $_POST['sortOrder'] + 1, $_POST['active']),
        'siisii'
      );
      if(!$affected){ $success = false; }

      // Get new ID
      $newId = $common['db']->last_insert_id();
            
      // Upload icon
      $newfilename = $newId .'-'. urlencode(str_replace(' ','-',strtolower($_FILES['image']['name']))); // Generate new file name to prevent overlap possibility
      $success = move_uploaded_file($_FILES['image']['tmp_name'], CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/sub_stages/' . $newfilename); // Moving the file
      $affected = $common['db']->pec('UPDATE prod_v2_sub_stages SET imgFilename=? WHERE subStageId=? LIMIT 1', array($newfilename, $newId), 'si');
      if(!$affected){ $success=false; }

      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_sub_stages_archive SET subStageId=?, title=?, extProductId=?, extTemplateId=?, instructions=?, sortOrder=?, active=?, imgFilename=?, generatedDateTime=NOW()',
        array($newId, $_POST['title'], $_POST['extProductId'], $_POST['extTemplateId'], $_POST['instructions'], $_POST['sortOrder'] + 1, $_POST['active'], $newfilename),
        'isiisiis'
      );
      if(!$affected){ $success=false; }
            
      // Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Sub Stage", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Sub Stage was successfully created':'Sub Stage was NOT successfully created';
            
      //Load edit again with second tab selected
      header('location: sub_stages.frm.php?mode=edit&subStageId=' . $newId . '&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
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
      show($_POST,$errors,array('subStageId'=>$_POST['subStageId'],'mode'=>'update'));
    }else{
      //Perform update
      $success = true;
      $common['db']->start_transaction();
      
      // Find where product is currently sorted at
      $productInfo = $common['db']->pec('SELECT sortOrder, imgFilename, extProductId FROM prod_v2_sub_stages WHERE subStageId=? LIMIT 1',array($_POST['subStageId']),'i', array('sortOrder', 'imgFilename', 'extProductId'));
      $imgFilename = $productInfo[0]['imgFilename'];

      //Collapse old location
      $affected = $common['db']->pec('UPDATE prod_v2_sub_stages SET sortOrder=sortOrder-1 WHERE sortOrder>? AND extProductId=?',array($productInfo[0]['sortOrder'], $productInfo[0]['extProductId']),'ii');
      if(!$affected){ $success=false; }

      if ($productInfo[0]['extProductId'] == $_POST['extProductId'] && $productInfo[0]['sortOrder'] < $_POST['sortOrder']){
        $newSortOrder = $_POST['sortOrder'] - 1;
      } else {
        $newSortOrder = $_POST['sortOrder'];
      }

      //Open new location
      $affected = $common['db']->pec('UPDATE prod_v2_sub_stages SET sortOrder=sortOrder+1 WHERE sortOrder>? AND extProductId=?',array($newSortOrder, $_POST['extProductId']),'ii');
      if(!$affected){ $success=false; }

      // Upload icon if new one was selected
      if (!empty($_FILES['image']['name'])) {
        $altFilename = $_POST['productId'] .'-'. urlencode(str_replace(' ','-',strtolower($_FILES['image']['name']))); // Generate new file name to prevent overlap possibility
        // If filename is exact match, remove file before replacing
        if ($altFilename == $imgFilename) {
          unlink(CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/sub_stages/' . $imgFilename);
        }
        $success = move_uploaded_file($_FILES['image']['tmp_name'], CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/sub_stages/' . $altFilename); // Moving the file
        $imgFilename = $altFilename;
      }

      // Update sub stage
      $affected = $common['db']->pec(
        'UPDATE prod_v2_sub_stages SET title=?, extProductId=?, extTemplateId=?, instructions=?, sortOrder=?, active=?, imgFilename=? WHERE subStageId=? LIMIT 1',
        array($_POST['title'], $_POST['extProductId'], $_POST['extTemplateId'], $_POST['instructions'], $newSortOrder + 1, $_POST['active'], $imgFilename, $_POST['subStageId']),
        'siisiisi'
      );
      if(!$affected){ $success=false; }

      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_sub_stages_archive SET subStageId=?, title=?, extProductId=?, extTemplateId=?, instructions=?, sortOrder=?, active=?, imgFilename=?, generatedDateTime=NOW()',
        array($_POST['subStageId'], $_POST['title'], $_POST['extProductId'], $_POST['extTemplateId'], $_POST['instructions'], $_POST['sortOrder'] + 1, $_POST['active'], $imgFilename),
        'isiisiis'
      );
      if(!$affected){ $success=false; }

      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Sub Stage", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');

      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Sub Stage was successfully updated':'Sub Stage was NOT successfully updated';
            
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
      $info = $common['db']->pec('SELECT title, imgFilename, extProductId, instructions, extTemplateId, sortOrder, active FROM prod_v2_sub_stages WHERE subStageId=? LIMIT 1',array($_REQUEST['subStageId']),'i',array('title', 'imgFilename', 'extProductId', 'instructions', 'extTemplateId', 'sortOrder', 'active'));
      $info[0]['sortOrder'] = $info[0]['sortOrder'] - 1;
      show($info[0],array(),array('subStageId'=>$_REQUEST['subStageId'],'mode'=>'update'));
    break;
    ////////////////////////////////////////////////////////
    case 'update':
      update();
    break;
    ////////////////////////////////////////////////////////
  }
}
?>