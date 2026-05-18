<?php
//Developer:    Charles Palmer
//Created:      2022.09.26
//Revision:     2022.10.10
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
        
    $page_title='Products';
    require_once('common/includes/header_inner.inc.php');
      echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['title']:''),false);
                
        $frm=new frm($values,$errors,$hidden);
        $tabs = array();

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
        $tabs[0]['title'] = 'General';
        $tabs[0]['content'] = $frm->begin_frm('','','multipart/form-data');
          $tabs[0]['content'] .= $frm->begin_fieldset('General Information');
            $tabs[0]['content'] .= $frm->begin_dl();
              $tabs[0]['content'] .= $frm->text('title','Title:');

              $results = $common['db']->pec('SELECT templateId, title FROM prod_v2_templates ORDER BY title', array(), '', array('templateId', 'title'));
              $options=array();
              foreach($results as $row) {
                $options[$row['templateId']]=$row['title'];
              }
              $tabs[0]['content'] .= $frm->list_menu('extTemplateId','Template:',$options);

              if ($hidden['mode']=='update'){
                $tabs[0]['content'] .= '<div style="height:100px; width: 100px; border:1px solid #aeaeae; background-color: #ffffff; margin: 5px 0px 0px 0px; padding: 5px; text-align: center;">';
                  $tabs[0]['content'] .= '<img src="'.CFG_CMS_BASE_URL.'production/v2/tests/uploads/products/'.$values['imgFilename'].'" style="max-height: 100px; max-width: 100px;" />';
                $tabs[0]['content'] .= '</div>';
              }
              $tabs[0]['content'] .= $frm->upload('image','Icon (128x128 pixel png):',($hidden['mode']=='update' ? false : true));

              //Find all associated tests to determine sort order options
              if($hidden['mode']=='insert') {
                $results = $common['db']->pec('SELECT title, sortOrder FROM prod_v2_products  ORDER BY sortOrder',array(),'',array('title', 'sortOrder'));
              } else {
                $results = $common['db']->pec('SELECT title, sortOrder FROM prod_v2_products WHERE productId<>? ORDER BY sortOrder',array($hidden['productId']),'i',array('title', 'sortOrder'));
              }
              $placeAfterOptions = array();
              $placeAfterOptions['-1'] = '=== TOP LEVEL ===';
              foreach($results as $row) {
                  $placeAfterOptions[$row['sortOrder']] = $row['title'];
              }
              $tabs[0]['content'] .= $frm->list_menu('sortOrder','Place After:',$placeAfterOptions);

              $tabs[0]['content'] .= $frm->radio_group('active','Active?:',array('0'=>'No','1'=>'Yes'),true,'','1');

            $tabs[0]['content'] .= $frm->end_dl();
          $tabs[0]['content'] .= $frm->end_fieldset();
                            
          $tabs[0]['content'] .= $frm->begin_fieldset('');
            $tabs[0]['content'] .= $frm->begin_dl('submit');
              $tabs[0]['content'] .= '<dd><a href="../index.php" title="Cancel" class="button">Cancel</a></dd>';
                $tabs[0]['content'] .= $frm->submit('submit','Submit','submit');
              $tabs[0]['content'] .= $frm->end_dl();    
            $tabs[0]['content'] .= $frm->end_fieldset();
          $tabs[0]['content'] .= $frm->end_frm();
          // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
          if ($hidden['mode']=='update'){
            $tabs[1]['title']='Error Types';
            $tabs[1]['content']='<p><a class="button" href="error_types.frm.php?extProductId='.$hidden['productId'].'" title="Add Error Type">Add Error Type</a></p>';
            $tabs[1]['content'].=$common['table']->begin(array('Title','Active', '&nbsp;','&nbsp;'),'sortable',array('', '','no_sort', 'no_sort'));

            $results = $common['db']->pec('SELECT errorId, title, active FROM prod_v2_product_error_types WHERE extProductId = ? ORDER BY title',array($hidden['productId']), 'i',['errorId', 'title', 'active']);
            foreach($results as $row) {
              $tabs[1]['content'].=$common['table']->add_row(
                array(
                  $row['title'], 
                  $row['active']?'Yes':'No', 
                  '<a href="error_types.frm.php?mode=edit&extProductId='.$hidden['productId'].'&errorId='.$row['errorId'].'" title="Edit Error Type">Edit</a>', 
                  '<a href="error_types.frm.php?mode=delete&extProductId='.$hidden['productId'].'&errorId='.$row['errorId'].'" title="Delete Error Type" class="include_alert">Delete</a>'
                ), array('','center','center','center'));
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
			if(!is_dir(CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/products/')){
        mkdir(CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/products/',0777,true);
      }

      // Open sort window for entry
      $affected = $common['db']->pec('UPDATE prod_v2_products SET sortOrder=sortOrder+1 WHERE sortOrder>?',array($_POST['sortOrder']),'i');
      if(!$affected){ $success = false; }

      // Create parent object
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_products SET title=?, extTemplateId=?, sortOrder=?, active=?',
        array($_POST['title'], $_POST['extTemplateId'], $_POST['sortOrder'] + 1, $_POST['active']),
        'siii'
      );
      if(!$affected){ $success = false; }

      // Get new ID
      $newId = $common['db']->last_insert_id();
            
      // Upload icon
      $newfilename = $newId .'-'. urlencode(str_replace(' ','-',strtolower($_FILES['image']['name']))); // Generate new file name to prevent overlap possibility
      $success = move_uploaded_file($_FILES['image']['tmp_name'], CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/products/' . $newfilename); // Moving the file
      $affected = $common['db']->pec('UPDATE prod_v2_products SET imgFilename=? WHERE productId=? LIMIT 1', array($newfilename, $newId), 'si');
      if(!$affected){ $success=false; }

      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_products_archive SET productId=?, title=?, extTemplateId=?, sortOrder=?, active=?, imgFilename=?, generatedDateTime=NOW()',
        array($newId, $_POST['title'], $_POST['extTemplateId'], $_POST['sortOrder'] + 1, $_POST['active'], $newfilename),
        'isiiis'
      );
      if(!$affected){ $success=false; }
            
      // Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Product", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Product was successfully created':'Product was NOT successfully created';
            
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
      show($_POST,$errors,array('productId'=>$_POST['productId'],'mode'=>'update'));
    }else{
      //Perform update
      $success = true;
      $common['db']->start_transaction();
      
      // Find where product is currently sorted at
      $productInfo = $common['db']->pec('SELECT sortOrder, imgFilename FROM prod_v2_products WHERE productId=? LIMIT 1',array($_POST['productId']),'i', array('sortOrder','imgFilename'));
      $imgFilename = $productInfo[0]['imgFilename'];

      //Collapse old location
      $affected = $common['db']->pec('UPDATE prod_v2_products SET sortOrder=sortOrder-1 WHERE sortOrder>?',array($productInfo[0]['sortOrder']),'i');
      if(!$affected){ $success=false; }

      if ($productInfo[0]['sortOrder'] < $_POST['sortOrder']){
        $newSortOrder = $_POST['sortOrder'] - 1;
      } else {
        $newSortOrder = $_POST['sortOrder'];
      }

      //Open new location
      $affected = $common['db']->pec('UPDATE prod_v2_products SET sortOrder=sortOrder+1 WHERE sortOrder>?',array($newSortOrder),'i');
      if(!$affected){ $success=false; }

      // Upload icon if new one was selected
      if (!empty($_FILES['image']['name'])) {
        $altFilename = $_POST['productId'] .'-'. urlencode(str_replace(' ','-',strtolower($_FILES['image']['name']))); // Generate new file name to prevent overlap possibility
        // If filename is exact match, remove file before replacing
        if ($altFilename == $imgFilename) {
          unlink(CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/products/' . $imgFilename);
        }
        $success = move_uploaded_file($_FILES['image']['tmp_name'], CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/products/' . $altFilename); // Moving the file
        $imgFilename = $altFilename;
      }

      // Update product
      $affected = $common['db']->pec(
        'UPDATE prod_v2_products SET title=?, extTemplateId=?, sortOrder=?, active=?, imgFilename=? WHERE productId=? LIMIT 1',
        array($_POST['title'], $_POST['extTemplateId'], $newSortOrder + 1, $_POST['active'], $imgFilename, $_POST['productId']),
        'siiisi'
      );
      if(!$affected){ $success=false; }

      // Create archive entry for tracking changes
      $affected = $common['db']->pec(
        'INSERT INTO prod_v2_products_archive SET productId=?, title=?, extTemplateId=?, sortOrder=?, active=?, imgFilename=?, generatedDateTime=NOW()',
        array($_POST['productId'], $_POST['title'], $_POST['extTemplateId'], $_POST['sortOrder'] + 1, $_POST['active'], $imgFilename),
        'isiiis'
      );
      if(!$affected){ $success=false; }

      //Create log entry
      $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Product", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');

      $state = $common['db']->end_transaction($success)?'success':'fail';
      $msg = $affected?'Product was successfully updated':'Product was NOT successfully updated';
            
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
      $info = $common['db']->pec('SELECT title, imgFilename, extTemplateId, sortOrder, active FROM prod_v2_products WHERE productId=? LIMIT 1',array($_REQUEST['productId']),'i',array('title', 'imgFilename', 'extTemplateId', 'sortOrder', 'active'));
      $info[0]['sortOrder'] = $info[0]['sortOrder'] - 1;
      show($info[0],array(),array('productId'=>$_REQUEST['productId'],'mode'=>'update'));
    break;
    ////////////////////////////////////////////////////////
    case 'update':
      update();
    break;
    ////////////////////////////////////////////////////////
  }
}
?>