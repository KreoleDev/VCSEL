<?php
//Developer:    Charles Palmer
//Created:      2019.03.01
//Revision:     2022.10.03
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
        
        $page_title='Firmware';
        require_once('common/includes/header_inner.inc.php');
            echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['version']:''),false);
				$frm=new frm($values,$errors,$hidden);
				echo $frm->begin_frm('','','multipart/form-data'); // to upload form
					echo $frm->begin_fieldset('General');
                        echo $frm->begin_dl();
                            //find all products
							$products=array();
							$results=$common['db']->pec('SELECT productId, title FROM prod_v2_products ORDER BY title',array(),'',array('productId', 'title'));
							foreach($results as $row){
								$products[$row['productId']]=$row['title'];
							}
							echo $frm->list_menu('extProductId','Product',$products);

							echo $frm->text('version','Version Number',true,12,' #.##.###.##A');
							
							echo $frm->text('md5','MD5',true,40);
							
							echo $frm->upload('filename','Firmware File',$hidden['mode']=='update'?false:true);
							
						echo $frm->end_dl();
					echo $frm->end_fieldset();
					
					echo $frm->begin_fieldset('','','','submit');
						echo $frm->begin_dl('submit');
							echo '<dd><a class="button" href="../index.php" title="cancel">Cancel</a></dd>';
							echo $frm->submit();
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
	
        //Validate
        if(empty($_POST['extProductId'])){
			$errors['extProductId']=array('Product','Please select a product');
        }
        
		if(empty($_POST['version'])){
			$errors['version']=array('Version','Please include a version');
        }
        
        if(empty($_POST['md5'])){
			$errors['md5']=array('MD5','Please include the MD5 of the file');
        }
        
        if($_POST['mode'] == 'insert') {
            if(empty($_FILES['filename']['name'])) {
                $errors['filename']=array('Firmware File','You must select a firmware file');
            }
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
            show($_POST,$errors,array('mode'=>'insert'));
        }else{
            $success=true;
            $common['db']->start_transaction();

            //Create directory for firmwares if not already there
            if(!is_dir(CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/firmwares/')){
                mkdir(CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/firmwares/',0777,true);
            }

            // Create parent object
            $affected = $common['db']->pec(
              'INSERT INTO prod_v2_firmwares SET extProductId=?, version=?, md5=?',
              array($_POST['extProductId'], $_POST['version'], $_POST['md5']),
              'iss'
            );
            if(!$affected){ $success = false; }

            // Get new ID
            $newId = $common['db']->last_insert_id();

            // Upload file
            $newfilename = $newId .'-'. urlencode(str_replace(' ','-',strtolower($_FILES['filename']['name']))); // Generate new file name to prevent overlap possibility
            $success = move_uploaded_file($_FILES['filename']['tmp_name'], CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/firmwares/' . $newfilename); // Moving the file
            $affected = $common['db']->pec('UPDATE prod_v2_firmwares SET filename=? WHERE firmwareId=? LIMIT 1', array($newfilename, $newId), 'si');
            if(!$affected){ $success=false; }
            
            //Create log entry
            $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Firmware", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
            $state=$common['db']->end_transaction($success)?'success':'fail';
            $msg=$success?'Firmware was successfully added':'Firmware was NOT successfully added';
            
            //Load main page
            header('location: ../index.php?msg_state=' .$state .'&msg=' .$msg);
            die();
        }
    }
    //--------------------------------------------------------------------------------------------------------------//
    function update(){
		global $common;

        //Check for errors
        $errors=validate();
        
        if(!empty($errors) || !$common['security']->verify_frm()){
            //Errors found
            show($_POST,$errors,array('mode'=>'update','firmwareId'=>$_POST['firmwareId']));
        }else{
            $success=true;
            $common['db']->start_transaction();

            // Find current filename
            $firmwareInfo = $common['db']->pec('SELECT filename FROM prod_v2_firmwares WHERE firmwareId=? LIMIT 1',array($_POST['firmwareId']),'i', array('filename'));
            $filename = $firmwareInfo[0]['filename'];

            // Upload firmware if new one was selected
            if (!empty($_FILES['filename']['name'])) {
              $altFilename = $_POST['firmwareId'] .'-'. urlencode(str_replace(' ','-',strtolower($_FILES['filename']['name']))); // Generate new file name to prevent overlap possibility
              // If filename is exact match, remove file before replacing
              if ($altFilename == $filename) {
                unlink(CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/firmwares/' . $filename);
              }
              $success = move_uploaded_file($_FILES['filename']['tmp_name'], CFG_CMS_INCLUDE_PATH .'production/v2/tests/uploads/firmwares/' . $altFilename); // Moving the file
              $filename = $altFilename;
            }

            // Update firmware
            $affected = $common['db']->pec(
              'UPDATE prod_v2_firmwares SET extProductId=?, version=?, md5=?, filename=? WHERE firmwareId=? LIMIT 1',
              array($_POST['extProductId'], $_POST['version'], $_POST['md5'], $filename, $_POST['firmwareId']),
              'isssi'
            );
            if(!$affected){ $success=false; }
            
            //Create log entry
            $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Firmware", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
            $state=$common['db']->end_transaction($success)?'success':'fail';
            $msg=$success?'Firmware was successfully updated':'Firmware was NOT successfully updated';
            
            //Load main page
            header('location: ../index.php?msg_state=' .$state .'&msg=' .$msg);
            die();
        }
    }
	//--------------------------------------------------------------------------------------------------------------//

    $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';

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
            //Find form info
            $firmwareInfo=$common['db']->pec('SELECT extProductId, version, filename, md5 FROM prod_v2_firmwares WHERE firmwareId=? LIMIT 1',array($_REQUEST['firmwareId']),'i',array('extProductId', 'version', 'filename', 'md5'));
            show($firmwareInfo[0],array(),array('firmwareId'=>$_REQUEST['firmwareId'],'mode'=>'update'));
        break;
        ////////////////////////////////////////////////////////
        case 'update':
            update();
        break;
        ////////////////////////////////////////////////////////
    }
}
?>