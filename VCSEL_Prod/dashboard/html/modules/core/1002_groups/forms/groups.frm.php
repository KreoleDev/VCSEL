<?php
//Developer:    Charles Palmer
//Created:      2014.05.22
//Revision:     2014.05.27
require_once('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Add Group
[2]     Edit Group
[3]     Delete Group
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    //--------------------------------------------------------------------------------------------------------------//
    function show($values,$errors,$hidden){
        global $common;
        
        $page_title='Groups';
        require_once('common/includes/header_inner.inc.php');
            echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['name']:''),false);
                $tabs=array();
                
                $frm=new frm($values,$errors,$hidden);
                
                /////////////////////////////////////////////////////////////////////////////////
                if($hidden['mode']=='insert' || ($hidden['group_id']!=1 && $hidden['group_id']!=2)){
                    $tabs[0]['title']='General';
                    $tabs[0]['content']=$frm->begin_frm();
                        $tabs[0]['content'].=$frm->begin_fieldset('General Information');
                            $tabs[0]['content'].=$frm->begin_dl();
                                    $tabs[0]['content'].=$frm->text('name','Name:',true,32,'','','','',true);
                            $tabs[0]['content'].=$frm->end_dl();
                        $tabs[0]['content'].=$frm->end_fieldset();
                        
                        $tabs[0]['content'].=$frm->begin_fieldset('');
                            $tabs[0]['content'].=$frm->begin_dl('submit');
                                $tabs[0]['content'].='<dd><a href="../index.php" title="Cancel" class="button">Cancel</a></dd>';
                                $tabs[0]['content'].=$frm->submit('submit','Submit','submit');
                            $tabs[0]['content'].=$frm->end_dl();    
                        $tabs[0]['content'].=$frm->end_fieldset();
                    $tabs[0]['content'].=$frm->end_frm();
                }
                /////////////////////////////////////////////////////////////////////////////////
                if($hidden['mode']=='update' && $hidden['group_id']!=1){
                    $tabs[1]['title']='Members';
                    $tabs[1]['content']='<p><a class="button" href="members.frm.php?group_id='.$hidden['group_id'].'" title="Add User">Add User</a></p>';
                    $tabs[1]['content'].=$common['table']->begin(array('Last Name','First Name','Username','&nbsp;'),'sortable',array('','','','no_sort'));
                        //Find all associated users
			$results=$common['db']->pec('SELECT user_id, first_name, last_name, username FROM core_users WHERE user_id IN(SELECT ext_user_id FROM core_user_group_lookup WHERE ext_group_id=?) ORDER BY last_name, first_name',array($hidden['group_id']),'i',array('user_id', 'first_name','last_name','username'));
			foreach($results as $row){
			    $tabs[1]['content'].=$common['table']->add_row(array($row['last_name'],$row['first_name'],$row['username'],'<a href="members.frm.php?mode=delete&amp;user_id='.$row['user_id'].'&amp;group_id='.$hidden['group_id'] .'" title="Delete '.$row['first_name'] . ' ' . $row['last_name'] .'" class="include_alert">Delete</a>'),array('','','','center'));
			}
                    $tabs[1]['content'].=$common['table']->end();
                }
                /////////////////////////////////////////////////////////////////////////////////
                if($hidden['mode']=='update' && $hidden['group_id']!=2){
                    $tabs[2]['title']='Group Rights';
                    $tabs[2]['content']=$common['table']->begin(array('Module','Rights','&nbsp;','&nbsp;'),'sortable',array('','','no_sort','no_sort'));
                        //Find all module rights to group
                        $results=$common['db']->pec('SELECT ext_module_id, access_level FROM core_group_rights WHERE ext_group_id=?',array($hidden['group_id']),'i',array('ext_module_id', 'access_level'));
                        $rights=array();
                        $lookup_list='';
                        foreach($results as $row){
                            $rights[$row['ext_module_id']]=$row['access_level'];
                            $lookup_list.=(!empty($lookup_list)?',':'') . $row['ext_module_id'];
                        }
                        
                        //Find all rights levels that group has some rights to
			$available_rights=array();
                        if(!empty($lookup_list)){
                            $results=$common['db']->pec('SELECT ext_module_id, bit_position, title FROM core_module_rights WHERE ext_module_id IN(' . $lookup_list . ')',array(),'',array('ext_module_id', 'bit_position', 'title'));
                            foreach($results as $row){
                                $available_rights[$row['ext_module_id']][$row['bit_position']]=$row['title'];
                            }
                        }
                        
                        //Convert rights to combined binary
			foreach($rights as $key=>$value){
			    $combined_rights=str_split(strrev(base_convert($rights[$key],10,2)));
			    
			    //Translate bits to friendly text
			    foreach($combined_rights as $right_id=>$right_value){
				if($combined_rights[$right_id]==1){
				    $combined_rights[$right_id]=$available_rights[$key][$right_id];
				}else{
				    unset($combined_rights[$right_id]);
				}
			    }
			    
			    //flatten array of combined rights
			    $rights[$key]=implode(', ',$combined_rights);
			}
                        
                        //Find all modules
                        $results=$common['db']->pec('SELECT module_id, title FROM core_modules WHERE 1 ORDER BY title',array(),'',array('module_id', 'title'));
                        foreach($results as $row){
                            $tabs[2]['content'].=$common['table']->add_row(array($row['title'],isset($rights[$row['module_id']])?$rights[$row['module_id']]:'','<a href="group_rights.frm.php?mode=edit&amp;group_id=' . $hidden['group_id'] . '&amp;module_id=' . $row['module_id'] . '" title="Edit ' . $row['title'] . ' Rights">Edit</a>',isset($rights[$row['module_id']])?'<a href="group_rights.frm.php?mode=delete&amp;group_id=' . $hidden['group_id'] . '&amp;module_id=' . $row['module_id'] . '" title="Delete ' . $row['title'] . ' Rights" class="include_alert">Delete</a>':''),array('','','center','center'));
                        }
                    $tabs[2]['content'].=$common['table']->end();
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
        
	if(empty($_POST['name'])){
	    $errors['name']=array('Name','Please include a name');
	}
        
        return $errors;
    }
    //--------------------------------------------------------------------------------------------------------------//
    function insert(){
        global $common;
        if($common['security']->check_rights(1)){ //Can add
	    //Check for errors
	    $errors=validate();
	    
	    if(!empty($errors) || !$common['security']->verify_frm()){
		//Errors found
		show($_POST,$errors,array('mode'=>'insert'));
	    }else{
                $affected=$common['db']->pec('INSERT INTO core_groups SET name=?',array($_POST['name']),'s');
		
		//Create log entry
		$common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Group", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
                
                $state=$affected?'success':'fail';
		$msg=$affected?'Group was successfully created':'Group was NOT successfully created';
		
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
		show($_POST,$errors,array('mode'=>'update','group_id'=>$_POST['group_id']));
	    }else{
                $affected=$common['db']->pec('UPDATE core_groups SET name=? WHERE group_id=? LIMIT 1',array($_POST['name'],$_POST['group_id']),'si');
                
		//Create log entry
		$common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Group", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
		
                $state=$affected?'success':'fail';
		$msg=$affected?'Group was successfully updated':'Group was NOT successfully updated';
		
		//Load main page
		header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
		die();
            }
        }
    }
    //--------------------------------------------------------------------------------------------------------------//
    function delete(){
        global $common;
        if($common['security']->check_rights(3)){ //Can delete
            $success=true;
	    $common['db']->start_transaction();
            
            //Remove from main tbl
            $affected=$common['db']->pec('DELETE FROM core_groups WHERE group_id=? LIMIT 1',array($_REQUEST['group_id']),'i');
            if(!$affected){ $success=false; }
            
            //Remove from lookup tbl
            $affected=$common['db']->pec('DELETE FROM core_user_group_lookup WHERE ext_group_id=?',array($_REQUEST['group_id']),'i');
            if(!$affected){ $success=false; }
            
            //Remove from rights tbl
            $affected=$common['db']->pec('DELETE FROM core_group_rights WHERE ext_group_id=?',array($_REQUEST['group_id']),'i');
            if(!$affected){ $success=false; }
	    
	    //Create log entry
	    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Deleted User", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
            $state=$common['db']->end_transaction($success)?'success':'fail';
	    $msg=$affected?'Group was successfully deleted':'Group was NOT successfully deleted';
		
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
	    $frm_info=$common['db']->pec('SELECT name FROM core_groups WHERE group_id=? LIMIT 1',array($_REQUEST['group_id']),'i',array('name'));
	    show($frm_info[0],array(),array('group_id'=>$_REQUEST['group_id'],'mode'=>'update'));
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