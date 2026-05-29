<?php
//Developer:    Charles Palmer
//Created:      2014.04.14
//Revision:     2015.07.08
require_once('common/includes/std_lib.inc.php');

//Load page with www if not specified
if (strpos($_SERVER['HTTP_HOST'],'www') === false && strpos(CFG_CMS_BASE_URL,'www')) {
    header('Location: ' . CFG_CMS_BASE_URL);
    exit;
}

if(isset($_REQUEST['logoff']) && $_REQUEST['logoff']=='true'){
    $success=$common['security']->logout();
    if($success){
        header('location: index.php?logoff=false');
        die();
    }
}

//--------------------------------------------------------------------------------------------------------------//
function show($values,$errors,$hidden){
    global $common;
    $body_class='login_page';
    require_once('common/includes/header.inc.php');
    ?>
        <div id="login">
            <div class="login_brand">
                <img src="common/images/login_logo.png?v=20260529" alt="<?=CFG_CMS_NAME; ?>" />
            </div>
            <div class="login_divider"></div>
            <div class="login_content">
                <h1>Sign in</h1>
                <?php
                $frm=new frm($values,$errors,$hidden);
                echo $frm->begin_frm('login_form','login_form');
                    echo $frm->begin_dl();
                        if(!CFG_MULTI_SITE){
                           echo '<div class="hidden">';
                        }
                        echo $frm->text('site','Site',true,64,'','block',(!CFG_MULTI_SITE?(isset($_REQUEST['site'])?$_REQUEST['site']:CFG_SINGLE_SITE):''),'',CFG_MULTI_SITE?true:false);
                        if(!CFG_MULTI_SITE){
                           echo '</div>';
                        }
                        echo $frm->text('username','Username',true,64,'','block','','Enter username',!CFG_MULTI_SITE?true:false);
                        echo $frm->password('password','Password',true,64,'block','Enter password');
                        echo $frm->submit('log_in','Sign in','submit');
                    echo $frm->end_dl();
                echo $frm->end_frm();
                ?>
            </div>
        </div>
    <?php
    require_once('common/includes/footer.inc.php');
}
//--------------------------------------------------------------------------------------------------------------//
function login(){
    global $common;
    if($common['security']->verify_frm()){ //verify form submitted by user
        $status=$common['security']->login($_POST['site'],$_POST['username'],$_POST['password']);
        if($status['success']){
            //log in good
           load_landing();
        }else{
            //log in bad, reshow form
            show($_POST,$status['errors'],array('mode'=>'login'));
        }
    }else{
        //reload form (multiple submit)
        show($_POST,array(),array('mode'=>'login'));
    }
}
//--------------------------------------------------------------------------------------------------------------//
function load_landing(){
    global $common;
    //Check to see if password has expired
    if(isset($_SESSION['local_password_expire_days'])){
        //Password can expire
        $common['db']=new api_db();
        $pw_results=$common['db']->pec('SELECT password_set_date FROM core_users WHERE user_id=? LIMIT 1',array($_SESSION['user_id']),'i',array('password_set_date'));
        if(strtotime($pw_results[0]['password_set_date'].'+'.$_SESSION['local_password_expire_days'].' days')<strtotime(date('c'))){
            //Password expired
            header('location: password_update.php');
            die();
        }else{
            //Password not expired
            header('location: modules/core/1000_dashboard/index.php?mod_id=1000');
            die();
        }
    }else{
        //Using ldap or passwords don't expire
        header('location: modules/core/1000_dashboard/index.php?mod_id=1000');
        die();
    }
}
//--------------------------------------------------------------------------------------------------------------//
$mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';

if((isset($_SESSION['expire_time']) && $_SESSION['expire_time']>=strtotime(date('c'))) && !isset($_REQUEST['logoff'])){
    $mode='load_landing';
}

switch($mode){
    ////////////////////////////////////////////////////////
    case 'show':
        show(array(),array(),array('mode'=>'login'));
    break;
    ////////////////////////////////////////////////////////
    case 'login':
        login();
    break;
    ////////////////////////////////////////////////////////
    case 'load_landing':
        load_landing();
    break;
    ////////////////////////////////////////////////////////
}
?>
