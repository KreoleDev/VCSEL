<?php
//Developer:    Charles Palmer
//Created:      2019.03.04
//Revision:     2019.03.04
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
        
        $page_title='Individual Production Test';
        require_once('common/includes/header_inner.inc.php');
            echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['title']:''),false);
				$frm=new frm($values,$errors,$hidden);
				echo $frm->begin_frm('','','multipart/form-data'); // to upload form
					echo $frm->begin_fieldset('General');
                        echo $frm->begin_dl();
                            echo $frm->text('title','Title:');
                            echo $frm->textarea('description','Description:');
                            echo $frm->textarea('instructions','Instructions:');
                            echo $frm->textarea('codeset','Codeset:');

                            //Find all products
                            $products=array();
                            $results=$common['db']->pec('SELECT product_id, title FROM 2019_prod_products WHERE 1 ORDER BY title',array(),'',array('product_id', 'title'));
                            foreach($results as $row){
                                $products[$row['product_id']]=$row['title'];
                            }

                            echo $frm->checkbox_group('products','Compatible Products:',$products,false);
							
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
        
		if(empty($_POST['description'])){
			$errors['description']=array('Description','Please include a description');
        }
        
        if(empty($_POST['instructions'])){
			$errors['instructions']=array('Instructions','Please include user instructions');
        }
        
        if(empty($_POST['codeset'])){
			$errors['codeset']=array('Codeset','Please include the code');
        }
	
		return $errors;
    }
    //--------------------------------------------------------------------------------------------------------------//
    function insert(){
        global $common;
        if($common['security']->check_rights(1)){
            //Check for errors
            $errors=validate();
            
            if(!empty($errors) || !$common['security']->verify_frm()){
                //Errors found
                show($_POST,$errors,array('mode'=>'insert'));
            }else{
                $success=true;
                $common['db']->start_transaction();
                
                //Create parent object
                $affected=$common['db']->pec('INSERT INTO 2019_prod_tests SET title=?, description=?, instructions=?, codeset=?',array($_POST['title'],$_POST['description'],$_POST['instructions'],$_POST['codeset']),'ssss');
                if(!$affected){ $success=false; }
                
                $test_id=$common['db']->last_insert_id();
                
                //Create tag associations
                if(isset($_POST['products'])){
                    $common['db']->prepare('INSERT INTO 2019_prod_test_product_assoc SET ext_test_id=?, ext_product_id=?');
                    foreach($_POST['products'] as $key=>$value){
                          $affected=$common['db']->execute(array($test_id,$value),'ii');
                          if(!$affected){ $success=false; }
                    }
                    $common['db']->close();
                }
                
                //Create log entry
                $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Test", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
                
                $state=$common['db']->end_transaction($success)?'success':'fail';
                $msg=$affected?'Test was successfully created':'Test was NOT successfully created';
                
                //Load main page
                header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
                die();
            }
        }
	}
    //--------------------------------------------------------------------------------------------------------------//
    function update(){
        global $common;
        if($common['security']->check_rights(2)){ //Can edit 
            //Check for errors
            $errors=validate();
            
            if(!empty($errors) || !$common['security']->verify_frm()){
                //Errors found
                show($_POST,$errors,array('test_id'=>$_POST['test_id'],'mode'=>'update'));
            }else{
                //Perform update
                $success=true;
                $common['db']->start_transaction();
                
                //Find old info and archive
                //++++++++++++++++++++++++++++++++++++++++++//
                $info=$common['db']->pec('SELECT codeset FROM 2019_prod_tests WHERE test_id=? LIMIT 1',array($_POST['test_id']),'i',array('codeset'));

                if($info[0]['codeset'] != $_POST['codeset']) {
                    $affected = $common['db']->pec('INSERT INTO 2019_prod_tests_archive SET ext_test_id=?, date_time=NOW(), codeset=?',array($_POST['test_id'],$info[0]['codeset']),'is');
                    if(!$affected){ $success=false; }
                }
                //++++++++++++++++++++++++++++++++++++++++++//
                
                //Remove old associations
                $affected=$common['db']->pec('DELETE FROM 2019_prod_test_product_assoc WHERE ext_test_id=?',array($_POST['test_id']),'i');
                if(!$affected){ $success=false; }

                //Create tag associations
                if(isset($_POST['products'])){
                    $common['db']->prepare('INSERT INTO 2019_prod_test_product_assoc SET ext_test_id=?, ext_product_id=?');
                    foreach($_POST['products'] as $key=>$value){
                          $affected=$common['db']->execute(array($_POST['test_id'],$value),'ii');
                          if(!$affected){ $success=false; }
                    }
                    $common['db']->close();
                }
                
                //Update
                $affected=$common['db']->pec('UPDATE 2019_prod_tests SET title=?, description=?, instructions=?, codeset=? WHERE test_id=? LIMIT 1',array($_POST['title'],$_POST['description'],$_POST['instructions'],$_POST['codeset'],$_POST['test_id']),'ssssi');
                if(!$affected){ $success=false; }
                
                //Create log entry
                $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Test", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
                
                $state=$common['db']->end_transaction($success)?'success':'fail';
                $msg=$affected?'Test was successfully updated':'Test was NOT successfully updated';
                
                //Load main page
                header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
                die();
            }
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
            $testInfo=$common['db']->pec('SELECT title, description, instructions, codeset FROM 2019_prod_tests WHERE test_id=? LIMIT 1',array($_REQUEST['test_id']),'i',array('title', 'description', 'instructions', 'codeset'));
            
            //Find all active associations
            $results=$common['db']->pec('SELECT ext_product_id FROM 2019_prod_test_product_assoc WHERE ext_test_id=?',array($_REQUEST['test_id']),'i',array('ext_product_id'));
            $testInfo[0]['products']=array();
            foreach($results as $row){
                $testInfo[0]['products'][$row['ext_product_id']]=$row['ext_product_id'];
            }
            
            show($testInfo[0],array(),array('test_id'=>$_REQUEST['test_id'],'mode'=>'update'));
        break;
        ////////////////////////////////////////////////////////
        case 'update':
            update();
        break;
        ////////////////////////////////////////////////////////
    }
}
?>