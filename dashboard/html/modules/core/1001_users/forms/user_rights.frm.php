<?php
//Developer:    Charles Palmer
//Created:      2014.05.22
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
        
        $page_title='User Rights';
        require_once('common/includes/header_inner.inc.php');
            //find user account info
	    $user_info=$common['db']->pec('SELECT first_name, last_name FROM core_users WHERE user_id=? LIMIT 1',array($hidden['user_id']),'i',array('first_name', 'last_name'));
            
            //find module info
            $module_info=$common['db']->pec('SELECT title FROM core_modules WHERE module_id=? LIMIT 1',array($hidden['module_id']),'i',array('title'));
            
            echo $common['window']->begin($page_title . ': ' . $user_info[0]['first_name'] . ' ' . $user_info[0]['last_name'] . ' for ' . $module_info[0]['title'],false);
                $frm=new frm($values,$errors,$hidden);
		echo $frm->begin_frm();
		    echo $frm->begin_fieldset('Rights',false);
			echo $frm->begin_dl();
                            //Find all rights options for module
                            $results=$common['db']->pec('SELECT bit_position, title FROM core_module_rights WHERE ext_module_id=? ORDER BY bit_position',array($hidden['module_id']),'i',array('bit_position', 'title'));
                            $rights=array();
                            foreach($results as $row){
                                $rights[$row['bit_position']]=$row['title'];
                            }
                            echo $frm->checkbox_group('selected_rights','Module Rights',$rights, false);
			echo $frm->end_dl();
		    echo $frm->end_fieldset();
			
		    echo $frm->begin_fieldset('');
			echo $frm->begin_dl('submit');
			    echo '<dd><a href="users.frm.php?mode=edit&user_id='.$hidden['user_id'].'&selected_tab=2" title="Cancel" class="button">Cancel</a></dd>';
			    echo $frm->submit('submit','Submit','submit');
			echo $frm->end_dl();    
		    echo $frm->end_fieldset();
                echo $frm->end_frm();
            echo $common['window']->end();
        
        require_once('common/includes/footer_inner.inc.php');
    }
    //--------------------------------------------------------------------------------------------------------------//
    function update(){
        global $common;
        
        
        $success=true;
	$common['db']->start_transaction();
           
        //Remove existing from db
        $affected=$common['db']->pec('DELETE FROM core_user_rights WHERE ext_user_id=? AND ext_module_id=? LIMIT 1',array($_POST['user_id'],$_POST['module_id']),'ii');
	if(!$affected){ $success=false; }
        
        if(isset($_POST['selected_rights'])){
            $counter=0;
            $rights=array();
            while($counter<=max(array_keys($_POST['selected_rights']))){
                $rights[$counter]=isset($_POST['selected_rights'][$counter])?'1':'0';
                $counter++;
            }
            
            $final_rights=base_convert(strrev(implode($rights)),2,10);
            
            //Add new
            $affected=$common['db']->pec('INSERT INTO core_user_rights SET ext_user_id=?, ext_module_id=?, access_level=?',array($_POST['user_id'],$_POST['module_id'],$final_rights),'iii');
            if(!$affected){ $success=false; }
        }
	
	//Create log entry
	$common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Changed User Rights", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
        
        $state=$common['db']->end_transaction($success)?'success':'fail';
	$msg=$affected?'User rights were successfully updated':'User rights were NOT successfully updated';
		
	//Load main page
	header('location: users.frm.php?mode=edit&user_id='.$_REQUEST['user_id'].'&selected_tab=2&msg_state=' . $state . '&msg=' . $msg);
	die();
    }
    //--------------------------------------------------------------------------------------------------------------//
    function delete(){
        global $common;
        
        $affected=$common['db']->pec('DELETE FROM core_user_rights WHERE ext_user_id=? AND ext_module_id=? LIMIT 1',array($_REQUEST['user_id'],$_REQUEST['module_id']),'ii');
        
	//Create log entry
	$common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Deleted User Rights", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
	
        $state=$affected?'success':'fail';
	$msg=$affected?'User rights were successfully removed':'User rights were NOT successfully removed';
		
	//Load main page
	header('location: users.frm.php?mode=edit&user_id='.$_REQUEST['user_id'].'&selected_tab=2&msg_state=' . $state . '&msg=' . $msg);
	die();
    }
    //--------------------------------------------------------------------------------------------------------------//
    $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'edit';
    
    switch($mode){
        ////////////////////////////////////////////////////////
        case 'edit':
            //find all current rights
            $access_info=$common['db']->pec('SELECT access_level FROM core_user_rights WHERE ext_module_id=? AND ext_user_id=? LIMIT 1',array($_REQUEST['module_id'],$_REQUEST['user_id']),'ii',array('access_level'));
            $access_level=!empty($access_info)?$access_info[0]['access_level']:0;
            $split_rights=str_split(strrev(base_convert($access_level,10,2)));
            $values=array();
            foreach($split_rights as $key=>$value){
                if($value==1){
                    $values['selected_rights'][$key]=$key;
                }
            }
            show($values,array(),array('mode'=>'update','user_id'=>$_REQUEST['user_id'],'module_id'=>$_REQUEST['module_id']));
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
