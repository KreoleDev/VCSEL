<?php
//Developer:    Charles Palmer
//Created:      2014.05.15
//Revision:     2014.06.18
require_once('common/includes/std_lib.inc.php');

//--------------------------------------------------------------------------------------------------------------//
function show($values,$errors,$hidden){
    global $common;
    $body_class='password_update_page';
    require_once('common/includes/header.inc.php');
        //find password min length
        $db=new api_db();
        $site_info=$db->pec('SELECT local_password_min_length FROM sites WHERE site_name=? LIMIT 1',array($_SESSION['site_name']),'s',array('local_password_min_length'));
    
        ?>
        <div id="login">
            <div class="password_card">
                <div class="password_brand">
                    <img src="common/images/login_logo.png" alt="<?=CFG_CMS_NAME; ?>" />
                </div>
                <h1>Update password</h1>
                <div class="info_box">
                    <p>Every <?=$_SESSION['local_password_expire_days']; ?> days your password must be changed. Your new password needs to be <?=$site_info[0]['local_password_min_length']; ?> or more characters in length and contain at least three of the following:</p>
                    <ul>
                        <li>Lowercase Characters (a-z)</li>
                        <li>Uppercase Characters (A-Z)</li>
                        <li>Numbers (0-9)</li>
                        <li>Symbols (!@#$%^&amp;*)</li> 
                    </ul>
                </div>
                <?php
                $frm=new frm($values,$errors,$hidden);
                echo $frm->begin_frm('password_update_form','password_update_form');
                    echo $frm->begin_fieldset('','');
                        echo $frm->begin_dl('password_update_fields');
                            echo $frm->password('current_password','Current Password');
                            echo $frm->password('new_password','New Password');
                            echo $frm->password('new2_password','Confirm Password');
                            echo $frm->submit('submit','Update Password','submit');
                        echo $frm->end_dl();
                    echo $frm->end_fieldset();
                echo $frm->end_frm();
                ?>
            </div>
        </div>
        <?php
    require_once('common/includes/footer.inc.php');
}
//--------------------------------------------------------------------------------------------------------------//
function validate(){
    global $common;
    $errors=array();
    
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
        //No errors found
       //Create new salt
try {
    $salt = random_bytes(22);
} catch (Exception $e) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $salt = '';
    for ($i = 0; $i < 22; $i++) {
        $salt .= $characters[random_int(0, 61)];
    }
}

$salt = base64_encode($salt);
$salt = str_replace('+', '.', $salt);
$db_pw = sha1($_POST['new_password'] . $salt);
        //Store in db
        $common['db']->pec('UPDATE core_users SET password=?, password_set_date=NOW(), salt=? WHERE user_id=? LIMIT 1',array($db_pw,$salt,$_SESSION['user_id']),'ssi');
            
        //Reload home
        header('location: index.php');
        die();
    }
}
//--------------------------------------------------------------------------------------------------------------//
$mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
switch($mode){
    ////////////////////////////////////////////////////////
    case 'show':
        show(array(),array(),array('mode'=>'update'));
    break;
    ////////////////////////////////////////////////////////
    case 'update':
        update();
    break;
    ////////////////////////////////////////////////////////
}
?>
