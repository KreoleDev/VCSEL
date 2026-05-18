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

if($common['security']->check_rights(2)){
    //--------------------------------------------------------------------------------------------------------------//
    function show($values,$errors,$hidden){
        global $common;
        
        $page_title='Members';
        require_once('common/includes/header_inner.inc.php');
            //Find group info
            $group_info=$common['db']->pec('SELECT name FROM core_groups WHERE group_id=? LIMIT 1',array($hidden['group_id']),'i',array('name'));
            echo $common['window']->begin($page_title . ': ' . $group_info[0]['name'],false);
                $frm=new frm($values,$errors,$hidden);
		echo $frm->begin_frm();
		    echo $frm->begin_fieldset('Users');
			echo $frm->begin_dl();
                            $users=array();
                            
                            $results=$common['db']->pec('SELECT user_id, first_name, last_name FROM core_users WHERE user_id NOT IN(SELECT ext_user_id FROM core_user_group_lookup WHERE ext_group_id=?) ORDER BY last_name, first_name',array($hidden['group_id']),'i',array('user_id', 'first_name', 'last_name'));
                            foreach($results as $row){
                                $users[$row['user_id']]=$row['last_name'] . ', ' . $row['first_name'];   
                            }
                            
                            echo $frm->list_menu('ext_user_id','User',$users);
                        echo $frm->end_dl();
                    echo $frm->end_fieldset();
                    
                    echo $frm->begin_fieldset('');
			echo $frm->begin_dl('submit');
			    echo '<dd><a href="groups.frm.php?mode=edit&group_id='.$hidden['group_id'].'&selected_tab=1" title="Cancel" class="button">Cancel</a></dd>';
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
        
        if(empty($_POST['ext_user_id'])){
	    $errors['ext_user_id']=array('User','Please select a user');
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
	    show($_POST,$errors,array('group_id'=>$_POST['group_id'],'mode'=>'insert'));
	}else{
            $affected=$common['db']->pec('INSERT INTO core_user_group_lookup SET ext_user_id=?, ext_group_id=?',array($_POST['ext_user_id'],$_POST['group_id']),'ii');
            
            //Create log entry
	    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added User To Group", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
            $state=$affected?'success':'fail';
	    $msg=$affected?'User was successfully associated':'User was NOT successfully associated';
		
	    //Load main page
	    header('location: groups.frm.php?mode=edit&group_id='.$_POST['group_id'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
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
	$msg=$affected?'User was successfully removed':'User was NOT successfully removed';
		
	//Load main page
	header('location: groups.frm.php?mode=edit&group_id='.$_REQUEST['group_id'].'&selected_tab=1&msg_state=' . $state . '&msg=' . $msg);
	die(); 
    }
    //--------------------------------------------------------------------------------------------------------------//
    $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
    
    switch($mode){
        ////////////////////////////////////////////////////////
        case 'show':
            show(array(),array(),array('mode'=>'insert','group_id'=>$_REQUEST['group_id']));
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