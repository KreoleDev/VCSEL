<?php
//Developer:    Charles Palmer
//Created:      2014.05.20
//Revision:     2020.05.05
require_once('common/includes/std_lib.inc.php');

/*
*   2020.01.14  CP  Corrected issue on nulling pw set date on password reset for newer server software version
*   2020.05.05  CP  Corrected issue on setting pw set date on new server on user create
*/

/*
$rights
    [0]     View
    [1]     Add User
    [2]     Edit User
    [3]     Delete User
    [4]     Password Reset
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    //--------------------------------------------------------------------------------------------------------------//
    function show($values,$errors,$hidden){
        global $common;
        
        $page_title='Users';
        require_once('common/includes/header_inner.inc.php');
            echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['first_name'] . ' ' . $values['last_name']:''),false);
                $tabs=array();
                
                $frm=new frm($values,$errors,$hidden);
                /////////////////////////////////////////////////////////////////////////////////
                $tabs[0]['title']='General';
                $tabs[0]['content']=$frm->begin_frm();
                    $tabs[0]['content'].=$frm->begin_fieldset('General Information');
                        $tabs[0]['content'].=$frm->begin_dl();
                            $tabs[0]['content'].=$frm->text('username','Username:',true,16,'','','','',true);
                        $tabs[0]['content'].=$frm->end_dl();
                        
                        $tabs[0]['content'].=$frm->begin_dl('float');
                            $tabs[0]['content'].=$frm->text('first_name','First Name:',true,24);
			$tabs[0]['content'].=$frm->end_dl();
			
			$tabs[0]['content'].=$frm->begin_dl('float');
			    $tabs[0]['content'].=$frm->text('last_name','Last Name:',true,24);
			$tabs[0]['content'].=$frm->end_dl();
			
			$tabs[0]['content'].=$frm->begin_dl('clear');
			    $tabs[0]['content'].=$frm->text('position_title','Position Title:',true,48);
			    $tabs[0]['content'].=$frm->text('phone','Phone Number:',true,20,'','','','(999) 999-9999 x9999');
			    $tabs[0]['content'].=$frm->text('email','E-Mail Address:',true,48,'','','','user@domain.com');
			$tabs[0]['content'].=$frm->end_dl();
                    $tabs[0]['content'].=$frm->end_fieldset();
                    
                    $tabs[0]['content'].=$frm->begin_fieldset('Login Information',false);
			$tabs[0]['content'].=$frm->begin_dl();
			    $tabs[0]['content'].=$frm->password('password1','Password:',$hidden['mode']=='insert'?true:false,24);
			    $tabs[0]['content'].=$frm->password('password2','Confirm Password:',$hidden['mode']=='insert'?true:false,24);
			$tabs[0]['content'].=$frm->end_dl();
			
			$tabs[0]['content'].=$frm->begin_dl('float');
			    $tabs[0]['content'].=$frm->radio_group('account_expires','Account Expires:',array('1'=>'Yes','0'=>'No'),true,'inline','0');
			$tabs[0]['content'].=$frm->end_dl();
			
			$tabs[0]['content'].=$frm->begin_dl('float');
			    $tabs[0]['content'].=$frm->text('expiry_date','Expiry Date:',false,10,'','date','','mm/dd/yyyy');
			$tabs[0]['content'].=$frm->end_dl();
			   
			$tabs[0]['content'].=$frm->begin_dl('clear');
			    $tabs[0]['content'].=$frm->radio_group('active','Active:',array('1'=>'Yes','0'=>'No'),true,'inline','1');
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
                if($hidden['mode']=='update'){
                    $tabs[1]['title']='Member Of';
                    $tabs[1]['content']='<p><a class="button" href="group_association.frm.php?user_id='.$hidden['user_id'].'" title="Add Group">Add Group</a></p>';
                    $tabs[1]['content'].=$common['table']->begin(array('Name','&nbsp;'),'sortable',array('','no_sort'));
			//Find all groups user is associated with
			$results=$common['db']->pec('SELECT group_id, name FROM core_groups WHERE group_id IN(SELECT ext_group_id FROM core_user_group_lookup WHERE ext_user_id=?) ORDER BY name',array($_REQUEST['user_id']),'i',array('group_id', 'name'));
			foreach($results as $row){
			    $tabs[1]['content'].=$common['table']->add_row(array($row['name'],'<a href="group_association.frm.php?mode=delete&amp;user_id='.$hidden['user_id'].'&amp;group_id='.$row['group_id'] .'" title="Delete '.$row['name'].'" class="include_alert">Delete</a>'),array('','center'));
			}
		    $tabs[1]['content'].=$common['table']->end();
		    /////////////////////////////////////////////////////////////////////////////////
                    $tabs[2]['title']='User Rights';
                    $tabs[2]['content']=$common['table']->begin(array('Module','Rights','Source','&nbsp;','&nbsp;'),'sortable',array('','','','no_sort','no_sort'));
			//find rights
			$rights=array();
			$lookup_list='';
			$results=$common['db']->pec('SELECT ext_module_id, name, access_level FROM core_group_rights, core_groups WHERE group_id=ext_group_id AND (group_id=1 OR group_id IN(SELECT ext_group_id FROM core_user_group_lookup WHERE ext_user_id=?)) ORDER BY name',array($hidden['user_id']),'i',array('ext_module_id', 'name', 'access_level'));
			foreach($results as $row){
			    if(isset($rights[$row['ext_module_id']])){
				$rights[$row['ext_module_id']]['name'].=', '.$row['name'];
				$rights[$row['ext_module_id']]['access_level'].=','.$row['access_level'];
			    }else{
				$rights[$row['ext_module_id']]['name']=$row['name'];
				$rights[$row['ext_module_id']]['access_level']=$row['access_level'];
			    }
			    $lookup_list.=(!empty($lookup_list)?',':'') . $row['ext_module_id'];
			}
			
			$results=$common['db']->pec('SELECT ext_module_id, access_level FROM core_user_rights WHERE ext_user_id=?',array($hidden['user_id']),'i',array('ext_module_id', 'access_level'));
			foreach($results as $row){
			    if(isset($rights[$row['ext_module_id']])){
				$rights[$row['ext_module_id']]['name'].=', User Specific';
				$rights[$row['ext_module_id']]['access_level'].=','.$row['access_level'];
			    }else{
				$rights[$row['ext_module_id']]['name']='User Specific';
				$rights[$row['ext_module_id']]['access_level']=$row['access_level'];
			    }
			    $rights[$row['ext_module_id']]['user_specific']=true;
			    $lookup_list.=(!empty($lookup_list)?',':'') . $row['ext_module_id'];
			}
			
			//Find all rights levels that user has some rights to
			$available_rights=array();
			if(!empty($lookup_list)){
			    $results=$common['db']->pec('SELECT ext_module_id, bit_position, title FROM core_module_rights WHERE ext_module_id IN(' . $lookup_list . ')',array(),'',array('ext_module_id', 'bit_position', 'title'));
			    foreach($results as $row){
				$available_rights[$row['ext_module_id']][$row['bit_position']]=$row['title'];
			    }
			}
			
			//Convert rights to combined binary
			foreach($rights as $key=>$value){
			    $combined_rights=array();
			    $rights_set=explode(',',$rights[$key]['access_level']);
			    foreach($rights_set as $key2=>$value2){
				$split_rights=str_split(strrev(base_convert($value2,10,2)));
				foreach($split_rights as $key3=>$value3){
				    if(!isset($combined_rights[$key3])|| $value3>$combined_rights[$key3]){
					$combined_rights[$key3]=$value3;
				    }
				}
			    }
			    
			    //Translate bits to friendly text
			    foreach($combined_rights as $right_id=>$right_value){
				if($combined_rights[$right_id]==1){
				    $combined_rights[$right_id]=$available_rights[$key][$right_id];
				}else{
				    unset($combined_rights[$right_id]);
				}
			    }
			    
			    //flatten array of combined rights
			    $rights[$key]['access_level']=implode(', ',$combined_rights);
			}
			
			//find all modules
			$results=$common['db']->pec('SELECT module_id, title FROM core_modules ORDER BY title',array(),'',array('module_id', 'title'));
			foreach($results as $row){
			    $tabs[2]['content'].=$common['table']->add_row(array($row['title'],isset($rights[$row['module_id']])?$rights[$row['module_id']]['access_level']:'',isset($rights[$row['module_id']])?$rights[$row['module_id']]['name']:'','<a href="user_rights.frm.php?mode=edit&amp;user_id='.$hidden['user_id'].'&amp;module_id='.$row['module_id'].'" title="Edit '.$row['title'].' Rights">Edit</a>',isset($rights[$row['module_id']]['user_specific'])?'<a href="user_rights.frm.php?mode=delete&amp;user_id='.$hidden['user_id'].'&amp;module_id='.$row['module_id'].'" title="Delete '.$row['title'].' Rights" class="include_alert">Delete</a>':''),array('','','','center','center'));
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
	
	//Validate username
	if(empty($_POST['username'])){
	    $errors['username']=array('Username','Please include a username');
	}else{
	    //Check database to make sure user account isn't already used
	    if($_POST['mode']=='update'){
		//on update
		$user_info=$common['db']->pec('SELECT count(*) FROM core_users WHERE username=? AND user_id<>? LIMIT 1',array($_POST['username'],$_POST['user_id']),'si',array('count'));
	    }else{
		//on insert
		$user_info=$common['db']->pec('SELECT count(*) FROM core_users WHERE username=? LIMIT 1',array($_POST['username']),'s',array('count'));
	    }
	    
	    if($user_info[0]['count']!=0){
		$errors['username']=array('Username','Username is already in use');	
	    }
	}
	
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
	
	//On insert check passwords
	if($_POST['mode']=='insert'){
	    if(empty($_POST['password1'])){
		$errors['password1']=array('Password','Please include a password');
	    }elseif(empty($_POST['password2'])){
		$errors['password2']=array('Confirm Password','Please confirm password');
	    }
	}
	
	//Check passwords
	if(!empty($_POST['password1']) || !empty($_POST['password2'])){
	    if($_POST['password1']!=$_POST['password2']){
		$errors['password2']=array('Confirm Password','Password doesn\'t match');
	    }
	}
	
	//Check expiry date
	if($_POST['account_expires'] && !$common['validate']->date($_POST['expiry_date'])){
	    $errors['expiry_date']=array('Expiry Date','Please enter a valid date for expiration');	
	}
	
	return $errors;
    }
    //--------------------------------------------------------------------------------------------------------------//
    function insert(){
	global $common;
	if($common['security']->check_rights(1)){ //Can add user
	    //Check for errors
	    $errors=validate();
	    
	    if(!empty($errors) || !$common['security']->verify_frm()){
		//Errors found
		show($_POST,$errors,array('mode'=>'insert'));
	    }else{
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
		$db_pw= sha1($_POST['password1'] . $salt);
		
		//Add user (don't set date for password set so user must change password)
		$affected=$common['db']->pec('INSERT INTO core_users SET creation_date=NOW(), password_set_date=\'1970-01-01 00:00:00\', username=?, first_name=?, last_name=?, position_title=?, phone=?, email=?, password=?, account_expires=?, expiry_date=?, active=?, salt=?',
		    array($_POST['username'],$_POST['first_name'],$_POST['last_name'],$_POST['position_title'],$common['format']->unformat_phone($_POST['phone']),$_POST['email'],$db_pw,$_POST['account_expires'],$common['format']->unformat_date($_POST['expiry_date']),$_POST['active'],$salt),
		    'sssssssisis');
		
		//Create log entry
		$common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added User", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
		
		$state=$affected?'success':'fail';
		$msg=$affected?'User account was successfully created':'User account was NOT successfully created';
		
		//Load main page
		header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
		die();
	    }
	}
    }
    //--------------------------------------------------------------------------------------------------------------//
    function update(){
	global $common;
	if($common['security']->check_rights(2)){ //Can edit user
	    //Check for errors
	    $errors=validate();
	    
	    if(!empty($errors) || !$common['security']->verify_frm()){
		//Errors found
		show($_POST,$errors,array('user_id'=>$_POST['user_id'],'mode'=>'update'));
	    }else{
		//Perform update
		if(!empty($_POST['password1'])){
		    //Password changed
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
		    $db_pw= sha1($_POST['password1'] . $salt);
		
		    $affected=$common['db']->pec('UPDATE core_users SET username=?, first_name=?, last_name=?, position_title=?, phone=?, email=?, active=?, account_expires=?, expiry_date=?, password=?, salt=?, password_set_date=\'1970-01-01 00:00:00\' WHERE user_id=? LIMIT 1',
			array($_POST['username'],$_POST['first_name'],$_POST['last_name'],$_POST['position_title'],$common['format']->unformat_phone($_POST['phone']),$_POST['email'],$_POST['active'],$_POST['account_expires'],$common['format']->unformat_date($_POST['expiry_date']),$db_pw,$salt,$_POST['user_id']),
			'ssssssiisssi'
		    );
		}else{
		    //Password NOT changed
		    $affected=$common['db']->pec('UPDATE core_users SET username=?, first_name=?, last_name=?, position_title=?, phone=?, email=?, active=?, account_expires=?, expiry_date=? WHERE user_id=? LIMIT 1',
			array($_POST['username'],$_POST['first_name'],$_POST['last_name'],$_POST['position_title'],$common['format']->unformat_phone($_POST['phone']),$_POST['email'],$_POST['active'],$_POST['account_expires'],$common['format']->unformat_date($_POST['expiry_date']),$_POST['user_id']),
			'ssssssiisi'
		    );
		}
		
		//Create log entry
		$common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated User", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
		
		$state=$affected?'success':'fail';
		$msg=$affected?'User account was successfully updated':'User account was NOT successfully updated';
		
		//Load main page
		header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
		die();
	    }
	}
    }
    //--------------------------------------------------------------------------------------------------------------//
    function delete(){
	global $common;
	if($common['security']->check_rights(3)){ //Can delete user
	    $success=true;
	    $common['db']->start_transaction();
	    
		//Remove from users
		$affected=$common['db']->pec('DELETE FROM core_users WHERE user_id=? LIMIT 1',array($_REQUEST['user_id']),'i');
		if(!$affected){ $success=false; }
		
		//Remove from user_auth_actions
		$affected=$common['db']->pec('DELETE FROM core_user_auth_actions WHERE ext_user_id=?',array($_REQUEST['user_id']),'i');
		if(!$affected){ $success=false; }
		
		//Remove from user_group_lookup
		$affected=$common['db']->pec('DELETE FROM core_user_group_lookup WHERE ext_user_id=?',array($_REQUEST['user_id']),'i');
		if(!$affected){ $success=false; }
		
		//Remove from user_sessions
		$affected=$common['db']->pec('DELETE FROM core_user_sessions WHERE ext_user_id=? LIMIT 1',array($_REQUEST['user_id']),'i');
		if(!$affected){ $success=false; }
		
		//Remove from user_rights
		$affected=$common['db']->pec('DELETE FROM core_user_rights WHERE ext_user_id=?',array($_REQUEST['user_id']),'i');
		if(!$affected){ $success=false; }
		
	    //Create log entry
	    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Deleted User", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
	    
	    $state=$common['db']->end_transaction($success)?'success':'fail';
	    $msg=$affected?'User account was successfully deleted':'User account was NOT successfully deleted';
		
	    //Load main page
	    header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
	    die();
	}
    }
    //--------------------------------------------------------------------------------------------------------------//
    function reset_pw($values,$errors,$hidden){
	global $common;
	if($common['security']->check_rights(4)){ //Can reset pw
	    $page_title='Users';
	    require_once('common/includes/header_inner.inc.php');
		//find user account info
		$user_info=$common['db']->pec('SELECT first_name, last_name FROM core_users WHERE user_id=? LIMIT 1',array($_REQUEST['user_id']),'i',array('first_name', 'last_name'));
	    
		echo $common['window']->begin($page_title . ': ' . $user_info[0]['first_name'] . ' ' . $user_info[0]['last_name'],false);
		    $frm=new frm($values,$errors,$hidden);
		    echo $frm->begin_frm();
			echo $frm->begin_fieldset('Password Reset');
			    echo $frm->begin_dl();
				echo $frm->password('password1','Password:',true,24);
				echo $frm->password('password2','Confirm Password:',true,24);
			    echo $frm->end_dl();
			echo $frm->end_fieldset();
			
			echo $frm->begin_fieldset('');
			    echo $frm->begin_dl('submit');
				echo '<dd><a href="../index.php" title="Cancel" class="button">Cancel</a></dd>';
				echo $frm->submit('submit','Submit','submit');
			    echo $frm->end_dl();    
			echo $frm->end_fieldset();
		    echo $frm->end_frm();
		echo $common['window']->end();
	    require_once('common/includes/footer_inner.inc.php');
	}
    }
    //--------------------------------------------------------------------------------------------------------------//
    function validate_pw(){
	global $common;
	$errors=array();
	
	if(empty($_POST['password1'])){
	    $errors['password1']=array('Password','Please include a password');
	}elseif(empty($_POST['password2'])){
	    $errors['password2']=array('Confirm Password','Please confirm password');
	}elseif($_POST['password1']!=$_POST['password2']){
	    $errors['password2']=array('Confirm Password','Password doesn\'t match');
	}
	
	return $errors;
    }
    //--------------------------------------------------------------------------------------------------------------//
    function update_pw(){
	global $common;
	if($common['security']->check_rights(4)){ //Can reset pw
	    //Check for errors
	    $errors=validate_pw();
	    
	    if(!empty($errors) || !$common['security']->verify_frm()){
		//Errors found
		reset_pw($_POST,$errors,array('user_id'=>$_POST['user_id'],'mode'=>'update_pw'));
	    }else{
		//Perform update
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
		$db_pw= sha1($_POST['password1'] . $salt);
		
		$affected=$common['db']->pec('UPDATE core_users SET password=?, salt=?, password_set_date=\'1970-01-01 00:00:00\' WHERE user_id=? LIMIT 1',array($db_pw,$salt,$_POST['user_id']),'ssi');
		
		//Create log entry
		$common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Reset User Password", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
		
		$state=$affected?'success':'fail';
		$msg=$affected?'User account password was reset':'User account password was NOT reset';
		
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
	    $user_info=$common['db']->pec('SELECT username, first_name, last_name, position_title, phone, email, active, account_expires, expiry_date FROM core_users WHERE user_id=? LIMIT 1',array($_REQUEST['user_id']),'i',array('username', 'first_name', 'last_name', 'position_title', 'phone', 'email', 'active', 'account_expires', 'expiry_date'));
	    $user_info[0]['phone']=$common['format']->format_phone($user_info[0]['phone']); //format phone number
	    $user_info[0]['expiry_date']=$common['format']->format_date($user_info[0]['expiry_date']); //format expiry date
	    show($user_info[0],array(),array('user_id'=>$_REQUEST['user_id'],'mode'=>'update'));
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
	case 'reset_pw':
	    reset_pw(array(),array(),array('user_id'=>$_REQUEST['user_id'],'mode'=>'update_pw'));
	break;
	////////////////////////////////////////////////////////
	case 'update_pw':
	    update_pw();
	break;
	////////////////////////////////////////////////////////
	
    }
}
?>