<?php
//Developer:    Charles Palmer
//Created:      2014.05.20
//Revision:     2015.06.26
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     Full Control
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    //--------------------------------------------------------------------------------------------------------------//
    function show($values,$errors,$hidden){
        global $common;
        
        $page_title='Account Settings';
        require_once('common/includes/header_inner.inc.php');
            //find user account info
	    $user_info=$common['db']->pec('SELECT first_name, last_name FROM core_users WHERE user_id=? LIMIT 1',array($_SESSION['user_id']),'i',array('first_name', 'last_name'));
            
            echo '<h2>'.$page_title.'</h2>';
            echo $common['window']->begin($page_title . ': ' . $user_info[0]['first_name'] . ' ' . $user_info[0]['last_name'],false);
                $frm=new frm($values,$errors,$hidden);
		echo $frm->begin_frm();
                
		    echo $frm->begin_fieldset('General');
                        echo $frm->begin_dl('float');
                            echo $frm->text('first_name','First Name:',true,24);
			echo $frm->end_dl();
			
			echo $frm->begin_dl('float');
			    echo $frm->text('last_name','Last Name:',true,24);
			echo $frm->end_dl();
			
			echo $frm->begin_dl('clear');
			    echo $frm->text('position_title','Position Title:',true,48);
			    echo $frm->text('phone','Phone Number:',true,20,'','','','(999) 999-9999 x9999');
			    echo $frm->text('email','E-Mail Address:',true,48,'','','','user@domain.com');
			echo $frm->end_dl();
                    echo $frm->end_fieldset();
                    
                    echo $frm->begin_fieldset('Password','Leave blank if you don\'t want to change password');
                        echo $frm->begin_dl();
                            echo $frm->password('current_password','Current Password:',false,24);
                            echo $frm->password('new_password','New Password:',false,24);
			    echo $frm->password('new2_password','Confirm Password:',false,24);
                        echo $frm->end_dl();
                    echo $frm->end_fieldset();
                    
                    echo $frm->begin_fieldset('');
			echo $frm->begin_dl('submit');
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
	
	//Validate first name
	if(empty($_POST['first_name'])){
	    $errors['first_name']=array('First Name','Please include a first name');
	}
	
	//Validate last name
	if(empty($_POST['last_name'])){
	    $errors['last_name']=array('Last Name','Please include a last name');
	}
	
	//Validate position title
	if(empty($_POST['position_title'])){
	    $errors['position_title']=array('Position Title','Please enter a position title');
	}
	
	//Validate phone
	if(!$common['validate']->phone($_POST['phone'])){
	    $errors['phone']=array('Phone Number','Please enter a valid phone number');
	}
	
	//Validate email	
	if(!$common['validate']->email($_POST['email'])){
	    $errors['email']=array('E-Mail Address','Please enter a valid E-mail address');
	}
		
	//Check passwords
	if(!empty($_POST['new_password']) || !empty($_POST['new2_password']) || !empty($_POST['current_password'])){
	    //Check current password info
            if(empty($_POST['current_password'])){
                $errors['current_password']=array('Current Password','Please include your current password');
            }else{
                //check with db to see if password is correct
                $valid_password=$common['db']->pec('SELECT user_id, password, salt FROM core_users WHERE user_id=? LIMIT 1',array($_SESSION['user_id']),'i',array('user_id','password','salt'));
                if(!isset($valid_password[0]['user_id']) || $valid_password[0]['password']!==sha1($_POST['current_password'] . $valid_password[0]['salt'])){
                    $errors['current_password']=array('Current Password','Incorrect password');	
                }
            }
            
            //find password min length
            $db=new api_db();
            $site_info=$db->pec('SELECT local_password_min_length FROM sites WHERE site_name=? LIMIT 1',array($_SESSION['site_name']),'s',array('local_password_min_length'));
            
            //Check new password info
            if(empty($_POST['new_password'])){
                $errors['new_password']=array('New Password','Please specify a new password');
            }elseif($_POST['current_password']==$_POST['new_password']){
                $errors['new_password']=array('New Password','You cannot reuse your old password');
            }elseif(!$common['validate']->strong_password($_POST['new_password'],$site_info[0]['local_password_min_length'])){
                $errors['new_password']=array('New Password','Your password doesn\'t meet the requirements');	
            }
            
            if(empty($_POST['new2_password'])){
                $errors['new2_password']=array('Confirm Password','Please confirm the new password');
            }elseif($_POST['new_password']!=$_POST['new2_password']){
                $errors['new2_password']=array('Confirm Password','Passwords don\'t match');
            }
	}
	
	return $errors;
    }
    //--------------------------------------------------------------------------------------------------------------//
    function update(){
        global $common;
        
        //Check for errors
        $errors=validate();
            
        if(!empty($errors) || !$common['security']->verify_frm()){
            //Errors found
            show($_POST,$errors,array('mode'=>'update'));
        }else{
            if(!empty($_POST['new_password'])){
                //Update with password change
                //Generate salt and pw hash
		if(function_exists(mcrypt_create_iv)){ //Use better random number
			$salt = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM); 
		}else{ //Use universal code
			$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
			$salt = '';
			for ($i = 0; $i < 22; $i++){
				$salt .= $characters[mt_rand(0, 61)];
			}
		}
		$salt = base64_encode($salt);
		$salt = str_replace('+', '.', $salt);
		$db_pw= sha1($_POST['new_password'] . $salt);
                
                $affected=$common['db']->pec('UPDATE core_users SET first_name=?, last_name=?, position_title=?, phone=?, email=?, password=?, salt=?, password_set_date=NOW()  WHERE user_id=? LIMIT 1',
                    array($_POST['first_name'],$_POST['last_name'],$_POST['position_title'],$common['format']->unformat_phone($_POST['phone']),$_POST['email'],$db_pw,$salt,$_SESSION['user_id']),
                    'sssssssi');
            }else{
                //Update without password change
                $affected=$common['db']->pec('UPDATE core_users SET first_name=?, last_name=?, position_title=?, phone=?, email=? WHERE user_id=? LIMIT 1',
                    array($_POST['first_name'],$_POST['last_name'],$_POST['position_title'],$common['format']->unformat_phone($_POST['phone']),$_POST['email'],$_SESSION['user_id']),
                    'sssssi');
            }
            
            //Create log entry
	    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Account Settings", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
            $state=$affected?'success':'fail';
	    $msg=$affected?'Account settings were successfully updated':'Account settings were NOT successfully updated';
		
	    //Load main page
	    header('location: index.php?msg_state=' . $state . '&msg=' . $msg);
	    die();
        }
    }
    //--------------------------------------------------------------------------------------------------------------//
    $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
    
    switch($mode){
        ////////////////////////////////////////////////////////
        case 'show':
            $user_info=$common['db']->pec('SELECT first_name, last_name, position_title, phone, email FROM core_users WHERE user_id=? LIMIT 1',array($_SESSION['user_id']),'i',array('first_name', 'last_name', 'position_title', 'phone', 'email'));
            $user_info[0]['phone']=$common['format']->format_phone($user_info[0]['phone']); //format phone number
            show($user_info[0],array(),array('mode'=>'update'));
        break;
        ////////////////////////////////////////////////////////
        case 'update':
            update();
        break;
        ////////////////////////////////////////////////////////
    }
}
?>
