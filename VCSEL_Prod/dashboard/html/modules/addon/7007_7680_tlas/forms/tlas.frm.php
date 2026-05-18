<?php
//Developer:    Charles Palmer
//Created:      2019.09.16
//Revision:     2019.11.19
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     View
    [1]     Manage
*/

/*
*   2019.11.18  CP  Added in firmware select
*   2019.11.19  CP  Added in wide vault
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(1)){
    //--------------------------------------------------------------------------------------------------------------//
    function show($values,$errors,$hidden){
        global $common;
        
        $page_title='7680 TLA';
        require_once('common/includes/header_inner.inc.php');
            echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['tla_number']:''),false);
                $tabs=array();
                
                $frm=new frm($values,$errors,$hidden);
                /////////////////////////////////////////////////////////////////////////////////
                $tabs[0]['title']='General';
                $tabs[0]['content']=$frm->begin_frm();
                    $tabs[0]['content'].=$frm->begin_fieldset('General Information');
                        $tabs[0]['content'].=$frm->begin_dl();
                            if($hidden['mode']!='insert'){
                                $tabs[0]['content'].='<div style="display:none;">';
                            }
                                $tabs[0]['content'].=$frm->text('tla_number','TLA:',true,11,'','','','',true);
                                
                            if($hidden['mode']!='insert'){
                                $tabs[0]['content'].='</div>';
                            }

                            
                            $tabs[0]['content'].=$frm->radio_group('active','Active?:',array('0'=>'No','1'=>'Yes'),true,'','0');

                            $results = $common['db']->pec('SELECT firmware_id, version FROM 2019_prod_firmwares WHERE ext_product_id=4 ORDER BY version', array(), '', array('firmware_id', 'version'));
                            $firmwares = array();
                            foreach($results as $row){
                                $firmwares[$row['firmware_id']]=$row['version'];
                            }
                            $tabs[0]['content'].=$frm->list_menu('ext_firmware_id','Firmware:',$firmwares);

                            $tabs[0]['content'].=$frm->radio_group('wideVault','Wide Vault?:',array('0'=>'No','1'=>'Yes'),true,'','0');

                        $tabs[0]['content'].=$frm->end_dl();
                     $tabs[0]['content'].=$frm->end_fieldset();
                            
                    $tabs[0]['content'].=$frm->begin_fieldset('');
                        $tabs[0]['content'].=$frm->begin_dl('submit');
                            $tabs[0]['content'].='<dd><a href="../index.php" title="Cancel" class="button">Cancel</a></dd>';
                            $tabs[0]['content'].=$frm->submit('submit','Submit','submit');
                        $tabs[0]['content'].=$frm->end_dl();    
                    $tabs[0]['content'].=$frm->end_fieldset();
                $tabs[0]['content'].=$frm->end_frm();
                /////////////////////////////////////////////////////////////////////////////////
                if($hidden['mode']!='insert'){
                    $tabs[1]['title']='Test Structure';
                    $tabs[1]['content']='<p><a class="button" href="tests_association.frm.php?tla_id='.$hidden['tla_id'].'" title="Add Test">Add Test</a>&nbsp;&nbsp;&nbsp;<a class="button" href="tests_association.frm.php?mode=duplicate&tla_id='.$hidden['tla_id'].'" title="Duplicate Tests From Different TLA">Duplicate Tests From Different TLA</a></p>';
                    $tabs[1]['content'].=$common['table']->begin(array('Sort Order','Title','Description','Active','&nbsp;','&nbsp;'),'searchable',array('','','','','no_sort','no_sort'));
                        $results = $common['db']->pec('SELECT ext_test_id, sort_order, active, title, description FROM 2019_prod_tla_test_assoc, 2019_prod_tests WHERE ext_tla_id=? AND ext_test_id=test_id ORDER BY sort_order',array($hidden['tla_id']),'i',array('ext_test_id', 'sort_order', 'active', 'title', 'description'));
                        foreach($results as $row) {
                            $tabs[1]['content'].=$common['table']->add_row(
                                array(
                                    $row['sort_order'],
                                    $row['title'],
                                    $row['description'],
                                    $row['active']?'Yes':'No',
                                    '<a href="tests_association.frm.php?mode=edit&amp;tla_id='.$hidden['tla_id'].'&amp;ext_test_id='.$row['ext_test_id'] .'" title="Edit '.$row['title'].'" >Edit</a>',
                                    '<a href="tests_association.frm.php?mode=delete&amp;tla_id='.$hidden['tla_id'].'&amp;ext_test_id='.$row['ext_test_id'] .'" title="Remove '.$row['title'].'" class="include_alert">Remove</a>'
                                ),array('','','','center','center','center'));
                        }
                    $tabs[1]['content'].=$common['table']->end();
                }
                /////////////////////////////////////////////////////////////////////////////////

                echo $common['tabs']->create_tabs($tabs,isset($_REQUEST['selected_tab'])?$_REQUEST['selected_tab']:0);
		        echo '<p class="bottom_options"><a href="../index.php" title="Back" class="button">Back</a></p>';
            echo $common['window']->end();
        require_once('common/includes/footer_inner.inc.php');
    }
    //--------------------------------------------------------------------------------------------------------------//
    function validate(){
		global $common;
        $errors=array();
		
		if(empty($_POST['tla_number'])){
	    	$errors['tla_number']=array('TLA','Please include a TLA');
		} elseif(!strpos($_POST['tla_number'],'-')) {
            $errors['tla_number']=array('TLA','Please include the "-" character');
        }elseif($_POST['mode']=='insert') {
            //Verify tla is unique
            $info = $common['db']->pec('SELECT tla_id FROM 2019_prod_tlas WHERE tla_number=? LIMIT 1',array($_POST['tla_number']),'s',array('tla_id'));
            if(!empty($info)){
                $errors['tla_number']=array('TLA','Please specify a unique TLA');
            }
        }

		if(empty($_POST['ext_firmware_id'])){
	    	$errors['ext_firmware_id']=array('Firmware','Please select a firmware');
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
            
            //Create parent object
            $affected=$common['db']->pec('INSERT INTO 2019_prod_tlas SET ext_product_id=4, tla_number=?, active=?',array($_POST['tla_number'],$_POST['active']),'si');
            if(!$affected){ $success=false; }
            
            $tla_id=$common['db']->last_insert_id();
            
            //Create first version
            $affected=$common['db']->pec('INSERT INTO 2019_prod_tla_info_product_4 SET ext_tla_id=?, ext_firmware_id=?, wide_vault=?, rev_timestamp=NOW()',
                                            array($tla_id, $_POST['ext_firmware_id'], $_POST['wide_vault']),
                                            'iii');
            if(!$affected){ $success=false; }
            
            //Create log entry
            $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added TLA", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
            $state=$common['db']->end_transaction($success)?'success':'fail';
            $msg=$affected?'TLA was successfully created':'TLA was NOT successfully created';
            
            //Load edit again with second tab selected
            header('location: tlas.frm.php?mode=edit&selected_tab=1&tla_id='.$tla_id.'&msg_state=' . $state . '&msg=' . $msg);
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
            show($_POST,$errors,array('tla_id'=>$_POST['tla_id'],'mode'=>'update'));
        }else{
            //Perform update
            $success=true;
            $common['db']->start_transaction();
            
            $tla_id=$_POST['tla_id'];

            //Update parent table
            $affected=$common['db']->pec('UPDATE 2019_prod_tlas SET active=? WHERE tla_id=? LIMIT 1',
                array($_POST['active'],$tla_id),
                'ii');
            if(!$affected){ $success=false; }
            
            //Create rev version
            if($success) {
                $affected=$common['db']->pec('INSERT INTO 2019_prod_tla_info_product_4 SET ext_tla_id=?, ext_firmware_id=?, wide_vault=?, rev_timestamp=NOW()',
                                            array($tla_id, $_POST['ext_firmware_id'], $_POST['wide_vault']),
                                            'iii');
                if(!$affected){ $success=false; }
            }
            
            //Create log entry
            $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated TLA", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
            $state=$common['db']->end_transaction($success)?'success':'fail';
            $msg=$affected?'TLA was successfully updated':'TLA was NOT successfully updated';
            
            //Load main page
            header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
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
            $tla_info=$common['db']->pec('SELECT tla_number, active FROM 2019_prod_tlas WHERE tla_id=? LIMIT 1',array($_REQUEST['tla_id']),'i',array('tla_number', 'active'));

            $sub_info=$common['db']->pec('SELECT ext_firmware_id, wide_vault FROM 2019_prod_tla_info_product_4 WHERE ext_tla_id=? ORDER BY rev_timestamp DESC LIMIT 1',
                array($_REQUEST['tla_id']),'i',
                array('ext_firmware_id', 'wide_vault'));
            if(isset($sub_info[0])) {
                $tla_info[0]['ext_firmware_id'] = $sub_info[0]['ext_firmware_id'];
                $tla_info[0]['wide_vault'] = $sub_info[0]['wide_vault'];
            }

            show($tla_info[0],array(),array('tla_id'=>$_REQUEST['tla_id'],'mode'=>'update'));
        break;
        ////////////////////////////////////////////////////////
        case 'update':
            update();
        break;
        ////////////////////////////////////////////////////////
    }
}
?>