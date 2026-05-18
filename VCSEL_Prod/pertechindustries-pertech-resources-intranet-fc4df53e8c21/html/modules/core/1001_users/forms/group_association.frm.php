<?php
//Developer:    Charles Palmer
//Created:      2014.05.21
//Revision:     2014.05.27
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     View
    [1]     Add User
    [2]     Edit User
    [3]     Delete User
    [4]     Password Reset
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(2)){
    //--------------------------------------------------------------------------------------------------------------//
    function show($values,$errors,$hidden){
        global $common;
        
        $page_title='Group Association';
        require_once('common/includes/header_inner.inc.php');
	    //find user account info
	    $user_info=$common['db']->pec('SELECT first_name, last_name FROM core_users WHERE user_id=? LIMIT 1',array($hidden['user_id']),'i',array('first_name', 'last_name'));
            
            echo $common['window']->begin($page_title . ': ' . $user_info[0]['first_name'] . ' ' . $user_info[0]['last_name'],false);
                $frm=new frm($values,$errors,$hidden);
		echo $frm->begin_frm();
		    echo $frm->begin_fieldset('Groups');
			echo $frm->begin_dl();
                            //find all groups user doesn't belong to
                            $results=$common['db']->pec('SELECT group_id, name FROM core_groups WHERE group_id<>1 AND group_id NOT IN(SELECT ext_group_id FROM core_user_group_lookup WHERE ext_user_id=?) ORDER BY name',array($hidden['user_id']),'i',array('group_id', 'name'));
                            $groups=array();
                            foreach($results as $row){
                                $groups[$row['group_id']]=$row['name'];
                            }
                            echo $frm->list_menu('ext_group_id','Group',$groups);
			echo $frm->end_dl();
		    echo $frm->end_fieldset();
			
		    echo $frm->begin_fieldset('');
			echo $frm->begin_dl('submit');
			    echo '<dd><a href="users.frm.php?mode=edit&user_id='.$hidden['user_id'].'&selected_tab=1" title="Cancel" class="button">Cancel</a></dd>';
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
        
        if(empty($_POST['ext_group_id'])){
	    $errors['ext_group_id']=array('Group','Please select a group');
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
	    show($_POST,$errors,array('user_id'=>$_POST['user_id'],'mode'=>'insert'));
	}else{
            $affected=$common['db']->pec('INSERT INTO core_user_group_lookup SET ext_user_id=?, ext_group_id=?',array($_POST['user_id'],$_POST['ext_group_id']),'ii');
            
            //Create log entry
	    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added User To Group", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
            $state=$affected?'success':'fail';
	    $msg=$affected?'Group was successfully associated':'Group was NOT successfully associated';
		
	    //Load main page
	    header('location: users.frm.php?mode=edit&user_id='.$_POST['user_id'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
	    die();   
        }
    }
    //--------------------------------------------------------------------------------------------------------------//
    function delete(){
        global $common;
        
        $affected=$common['db']->pec('DELETE FROM core_user_group_lookup WHERE ext_user_id=? AND ext_group_id=? LIMIT 1',array($_REQUEST['user_id'],$_REQUEST['group_id']),'ii');
            
        //Create log entry
	$common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Deleted User From Group", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
        $state=$affected?'success':'fail';
	$msg=$affected?'Group was successfully removed':'Group was NOT successfully removed';
		
	//Load main page
	header('location: users.frm.php?mode=edit&user_id='.$_REQUEST['user_id'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
	die(); 
    }
    //--------------------------------------------------------------------------------------------------------------//
    $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
    
    switch($mode){
        ////////////////////////////////////////////////////////
        case 'show':
            show(array(),array(),array('mode'=>'insert','user_id'=>$_REQUEST['user_id']));
        break;
        ////////////////////////////////////////////////////////
        case 'insert':
            insert();
        break;
        ////////////////////////////////////////////////////////
        case 'delete':
            delete();
        break;
        ////////////////////////////////////////////////////////
    }
}
?>