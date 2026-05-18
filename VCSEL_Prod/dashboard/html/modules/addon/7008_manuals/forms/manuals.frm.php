<?php
//Developer:    Charles Palmer
//Created:      2019.12.05
//Revision:     2019.12.09

/*
*   2019.12.09  CP  Changed part number to be optional
*/

require_once('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Admin
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(1)){
    //--------------------------------------------------------------------------------------------------------------//
    function show($values,$errors,$hidden){
        global $common;
        
        $page_title='Manuals';
        require_once('common/includes/header_inner.inc.php');
            echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['part_number'] . ' - ' . $values['title']:''),false);
				$frm=new frm($values,$errors,$hidden);
				echo $frm->begin_frm('','','multipart/form-data'); // to upload form
					echo $frm->begin_fieldset('General');
                        echo $frm->begin_dl();
                            echo $frm->text('part_number','Part Number',false);
							echo $frm->text('title','Title');

							echo $frm->upload('pdf_filename','PDF Manual',($hidden['mode']=='insert'?true:false));
							
							if($hidden['mode']=='update' && $values['has_image']){
								echo '<img src="'.CFG_CMS_BASE_URL . 'sites/'.$_SESSION['site_path'] . 'uploads/7008_manuals/'.$values['manual_id'].'.jpg" style="width:148px; height:148px;" />';
							}
							echo '<div style="display:none">';
                            echo $frm->upload('icon_filename','Icon',false);
                            echo '</div>';
							echo $frm->textarea('keywords','Keywords',false);
							echo $frm->radio_group('active','Active:',array('1'=>'Yes','0'=>'No'),true,'inline','1');
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
        if(empty($_POST['title'])){
			$errors['title']=array('Title','Please include a title');
        }

        if($_POST['mode']=='insert' && empty($_FILES['pdf_filename']['name'])) {
            $errors['pdf_filename']=array('PDF Manual','Please include a pdf');
        } else {
            if(!empty($_FILES['pdf_filename']['name'])&&(!strpos($_FILES['pdf_filename']['name'],'.pdf') && !strpos($_FILES['pdf_filename']['name'],'.PDF'))){
                $errors['pdf_filename']=array('PDF Manual','You must select a .pdf');
            }
        }
		
		if(!empty($_FILES['icon_filename']['name'])&&(!strpos($_FILES['icon_filename']['name'],'.jpg') && !strpos($_FILES['icon_filename']['name'],'.JPG'))){
            $errors['icon_filename']=array('Icon','You must select a .jpg/.png image');
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
            
            //Create directory for icons if not already there
            if(!is_dir(CFG_CMS_INCLUDE_PATH .'sites/' .$_SESSION['site_path'] .'uploads/7008_manuals/')){
                mkdir(CFG_CMS_INCLUDE_PATH .'sites/' .$_SESSION['site_path'] .'uploads/7008_manuals/',0777,true);
            }

            if(!empty($_FILES['icon_filename']['name'])){
                $has_image = 1;
            } else {
                $has_image = 0;
            }

            // Add entry into database...
            $affected=$common['db']->pec('INSERT INTO 7008_manuals SET part_number=?, title=?, rev_number=0, change_date_time=NOW(), has_image=?, keywords=?, active=?',
                array($_POST['part_number'],$_POST['title'],$has_image,$_POST['keywords'],$_POST['active']),
                'ssisi');
            if(!$affected){ $success=false; }

            $last_id = $common['db']->last_insert_id();
            
            $newfilename='';
            
            if(!empty($_FILES['icon_filename']['name'])){
                //File was uploaded
                // Moving the file...
                $success=move_uploaded_file($_FILES['icon_filename']['tmp_name'],CFG_CMS_INCLUDE_PATH .'sites/' .$_SESSION['site_path'] .'uploads/7008_manuals/' .$last_id . '.jpg');
            }
            
            if(!empty($_FILES['pdf_filename']['name'])){
                //File was uploaded
                // Moving the file...
                $success=move_uploaded_file($_FILES['pdf_filename']['tmp_name'],CFG_CMS_INCLUDE_PATH .'sites/' .$_SESSION['site_path'] .'uploads/7008_manuals/' . $last_id . '.pdf');
            }
            
            //Create log entry
            $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Manual", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
            $state=$common['db']->end_transaction($success)?'success':'fail';
            $msg=$success?'Manual was successfully added':'Manual was NOT successfully added';
            
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
            show($_POST,$errors,array('mode'=>'update','manual_id'=>$_POST['manual_id']));
        }else{
            $success=true;
            $common['db']->start_transaction();

            if(!empty($_FILES['pdf_filename']['name'])){
                //File was uploaded

                //Remove old...
                if(file_exists(CFG_CMS_INCLUDE_PATH .'sites/' .$_SESSION['site_path'] . 'uploads/7008_manuals/' . $_POST['manual_id'] . '.pdf')){
                    unlink(CFG_CMS_INCLUDE_PATH .'sites/' .$_SESSION['site_path'] . 'uploads/7008_manuals/' .  $_POST['manual_id'] . '.pdf');
                }
                
                //Moving the file...
                $success=move_uploaded_file($_FILES['pdf_filename']['tmp_name'],CFG_CMS_INCLUDE_PATH .'sites/' .$_SESSION['site_path'] .'uploads/7008_manuals/' . $_POST['manual_id'] . '.pdf');
            }
            
            if(!empty($_FILES['icon_filename']['name'])){
                //File was uploaded

                //Remove old image...
                if(file_exists(CFG_CMS_INCLUDE_PATH .'sites/' .$_SESSION['site_path'] . 'uploads/7008_manuals/' . $_POST['manual_id'] . '.jpg')){
                    unlink(CFG_CMS_INCLUDE_PATH .'sites/' .$_SESSION['site_path'] . 'uploads/7008_manuals/' .  $_POST['manual_id'] . '.jpg');
                }
                
                //Moving the file...
                $success=move_uploaded_file($_FILES['icon_filename']['tmp_name'],CFG_CMS_INCLUDE_PATH .'sites/' .$_SESSION['site_path'] .'uploads/7008_manuals/' . $_POST['manual_id'] . '.jpg');
    
                //Update Entry...
                $affected=$common['db']->pec('UPDATE 7008_manuals SET part_number=?, title=?, rev_number=rev_number+1, change_date_time=NOW(), has_image=1, keywords=?, active=? WHERE manual_id=? LIMIT 1',
                    array($_POST['part_number'],$_POST['title'],$_POST['keywords'],$_POST['active'],$_POST['manual_id']),
                    'sssii');
            }else{
                //File not uploaded
                //Update Entry...
                $affected=$common['db']->pec('UPDATE 7008_manuals SET part_number=?, title=?, rev_number=rev_number+1, change_date_time=NOW(), keywords=?, active=? WHERE manual_id=? LIMIT 1',
                    array($_POST['part_number'],$_POST['title'],$_POST['keywords'],$_POST['active'],$_POST['manual_id']),
                    'sssii');
            }
            if(!$affected){ $success=false; }


            
            
            //Create log entry
            $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Manual", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
        
            $state=$common['db']->end_transaction($success)?'success':'fail';
            $msg=$success?'Manual was successfully updated':'Manual was NOT successfully updated';
        
            //Load main page
            header('location: ../index.php?msg_state=' .$state .'&msg=' .$msg);
            die();
        }

    }
	//--------------------------------------------------------------------------------------------------------------//
	/*function delete(){
		global $common;

        $success=true;
        $common['db']->start_transaction();
            
        //Find old file
        $results=$common['db']->pec('SELECT icon_filename FROM 3001_projects WHERE project_id=? LIMIT 1',array($_REQUEST['project_id']),'i',array('icon_filename'));
            
        //Remove old image...
        if(file_exists(CFG_CMS_INCLUDE_PATH .'sites/' .$_SESSION['site_path'] . 'uploads/3001_projects/' . $results[0]['icon_filename'])){
            unlink(CFG_CMS_INCLUDE_PATH .'sites/' .$_SESSION['site_path'] . 'uploads/3001_projects/' . $results[0]['icon_filename']);
        }
        
        //Remove from main tbl
        $affected=$common['db']->pec('DELETE FROM 3001_projects WHERE project_id=? LIMIT 1', array($_REQUEST['project_id']),'i');
        if(!$affected){ $success=false; }
            
        //Create log entry
        $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Deleted Project", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
        $state=$common['db']->end_transaction($success)?'success':'fail';
        $msg=$success?'Project was successfully deleted':'Project was NOT successfully deleted';
            
        //Load main page
        header('location: ../index.php?msg_state=' .$state .'&msg=' .$msg);
        die();

    }*/
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
			$info=$common['db']->pec('SELECT part_number, title, has_image, keywords, active FROM 7008_manuals WHERE manual_id=? LIMIT 1',array($_REQUEST['manual_id']),'i',array('part_number', 'title', 'has_image', 'keywords', 'active'));
			show($info[0],array(),array('manual_id'=>$_REQUEST['manual_id'],'mode'=>'update'));
		break;
		////////////////////////////////////////////////////////
		case 'update':
			update();
		break;
		////////////////////////////////////////////////////////
		/*case 'delete':
			delete();    
		break;*/
		////////////////////////////////////////////////////////	
    } 
}  
?>