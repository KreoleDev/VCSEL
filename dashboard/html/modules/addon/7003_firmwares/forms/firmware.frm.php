<?php
//Developer:    Charles Palmer
//Created:      2019.03.01
//Revision:     2019.12.11

/*
*   2019.12.11  CP  Added in disclaimer for 7680 version of firmware
*/

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
							$results=$common['db']->pec('SELECT product_id, title FROM 2019_prod_products ORDER BY title',array(),'',array('product_id', 'title'));
							foreach($results as $row){
								$products[$row['product_id']]=$row['title'];
							}
							echo $frm->list_menu('ext_product_id','Product',$products);

							echo $frm->text('version','Version Number',true,12,' #.##.###.##A');
							
              echo $frm->text('md5','MD5',true,40);
							
							echo $frm->upload('filename','Firmware File <span style="color:#f00;">(For 7680, the firmware file should be the "p_" version.)</span>',$hidden['mode']=='update'?false:true);
							
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
        if(empty($_POST['ext_product_id'])){
			$errors['ext_product_id']=array('Product','Please select a product');
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

            //Determine folder name
            $productInfo = $common['db']->pec('SELECT directory_name FROM 2019_prod_products WHERE product_id=? LIMIT 1',array($_POST['ext_product_id']),'i',array('directory_name'));
            
            //Create directory for firmwares if not already there
            if(!is_dir(CFG_CMS_INCLUDE_PATH .'production/uploads/firmwares/' . $productInfo[0]['directory_name'])){
                mkdir(CFG_CMS_INCLUDE_PATH .'production/uploads/firmwares/' . $productInfo[0]['directory_name'],0777,true);
            }

            //File was uploaded
            // Generate new file name...
            $newfilename='f' . str_replace('.','-',strtolower($_POST['version'])) . '.bin';
            
            // Moving the file...
            $success=move_uploaded_file($_FILES['filename']['tmp_name'],CFG_CMS_INCLUDE_PATH .'production/uploads/firmwares/' . $productInfo[0]['directory_name'] .$newfilename);
            
            // Add entry into database...
            $affected=$common['db']->pec('INSERT INTO 2019_prod_firmwares SET ext_product_id=?, version=?, filename=?, md5=?',
                array($_POST['ext_product_id'],$_POST['version'],$newfilename,$_POST['md5']),
                'isss');
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
            show($_POST,$errors,array('mode'=>'update','firmware_id'=>$_POST['firmware_id']));
        }else{
            $success=true;
            $common['db']->start_transaction();

            //Determine folder name
            $productInfo = $common['db']->pec('SELECT directory_name FROM 2019_prod_products WHERE product_id=? LIMIT 1',array($_POST['ext_product_id']),'i',array('directory_name'));
            
            //Create directory for firmwares if not already there
            if(!is_dir(CFG_CMS_INCLUDE_PATH .'production/uploads/firmwares/' . $productInfo[0]['directory_name'])){
                mkdir(CFG_CMS_INCLUDE_PATH .'production/uploads/firmwares/' . $productInfo[0]['directory_name'],0777,true);
            }

            if(!empty($_FILES['filename']['name'])){
                //File was uploaded

                //Find and remove old
                $oldInfo = $common['db']->pec('SELECT filename, directory_name FROM 2019_prod_firmwares, 2019_prod_products WHERE firmware_id=? AND ext_product_id=product_id LIMIT 1',array($_POST['firmware_id']),'i',array('filename','directory_name'));
                //Remove old file...
                if(file_exists(CFG_CMS_INCLUDE_PATH .'production/uploads/firmwares/' . $oldInfo[0]['directory_name'] . $oldInfo[0]['filename'])){
                    unlink(CFG_CMS_INCLUDE_PATH .'production/uploads/firmwares/' . $oldInfo[0]['directory_name'] . $oldInfo[0]['filename']);
                }

                // Generate new file name...
                $newfilename='f' . str_replace('.','-',strtolower($_POST['version'])) . '.bin';
                
                // Moving the file...
                $success=move_uploaded_file($_FILES['filename']['tmp_name'],CFG_CMS_INCLUDE_PATH .'production/uploads/firmwares/' . $productInfo[0]['directory_name'] .$newfilename);
                
                // Update entry in database...
                $affected=$common['db']->pec('UPDATE 2019_prod_firmwares SET ext_product_id=?, version=?, filename=?, md5=? WHERE firmware_id=? LIMIT 1',
                    array($_POST['ext_product_id'],$_POST['version'],$newfilename,$_POST['md5'],$_POST['firmware_id']),
                    'isssi');
                if(!$affected){ $success=false; }
            } else {
                //File was not uploaded

                //Determine if directory has changed for if file needs moved
                $oldInfo = $common['db']->pec('SELECT filename, directory_name, ext_product_id FROM 2019_prod_firmwares, 2019_prod_products WHERE firmware_id=? AND ext_product_id=product_id LIMIT 1',array($_POST['firmware_id']),'i',array('filename','directory_name','ext_product_id'));

                if($oldInfo[0]['ext_product_id']!=$_POST['ext_product_id']) {
                    //File needs moved
                    $success=move_uploaded_file(CFG_CMS_INCLUDE_PATH .'production/uploads/firmwares/' . $oldInfo[0]['directory_name'] .$oldInfo[0]['filename'],CFG_CMS_INCLUDE_PATH .'2019_production/uploads/firmwares/' . $productInfo[0]['directory_name'] .$oldInfo[0]['filename']);
                }

                // Update entry in database...
                $affected=$common['db']->pec('UPDATE 2019_prod_firmwares SET ext_product_id=?, version=?, md5=? WHERE firmware_id=? LIMIT 1',
                array($_POST['ext_product_id'],$_POST['version'],$_POST['md5'],$_POST['firmware_id']),
                'issi');
                if(!$affected){ $success=false; }
            }
            
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
            $firmwareInfo=$common['db']->pec('SELECT ext_product_id, version, filename, md5 FROM 2019_prod_firmwares WHERE firmware_id=? LIMIT 1',array($_REQUEST['firmware_id']),'i',array('ext_product_id', 'version', 'filename', 'md5'));
            show($firmwareInfo[0],array(),array('firmware_id'=>$_REQUEST['firmware_id'],'mode'=>'update'));
        break;
        ////////////////////////////////////////////////////////
        case 'update':
            update();
        break;
        ////////////////////////////////////////////////////////
    }
}
?>