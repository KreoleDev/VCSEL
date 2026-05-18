<?php
//Developer:    Charles Palmer
//Created:      2014.04.16
//Revision:     2016.07.14

/*
 *  2016.07.14  CP  Extended session maxlifetime
 */

if(!isset($_SESSION)){
    $session_save_path=getenv('PERTECH_SESSION_SAVE_PATH');
    if(($session_save_path===false || $session_save_path==='') && PHP_SAPI==='cli-server'){
        $default_session_path=session_save_path();
        if(empty($default_session_path) || !is_dir($default_session_path) || !is_writable($default_session_path)){
            $session_save_path=sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pertech-php-sessions';
        }
    }
    if($session_save_path!==false && $session_save_path!==''){
        if(!is_dir($session_save_path)){
            mkdir($session_save_path,0777,true);
        }
        session_save_path($session_save_path);
    }
    session_set_cookie_params(28800);
    session_start();
    $_SESSION['LAST_ACTIVITY'] = time();
}

//extend session life
//ini_set('session.gc_maxlifetime',28800);

//Set unique timezone of set
if(isset($_SESSION['timezone'])){
    ini_set ('date.timezone',$_SESSION['timezone']);
}

require_once('protected/config.inc.php');

//Auto load php classes
spl_autoload_register(function($class){
    require_once 'common/classes/' . $class . '.class.php'; 
});

$common=array();
$common['page_load']=new load_time(); //Start timer for page load
$common['security']=new security(); //Security class for all of CMS
$common['window']=new window();
$common['validate']=new validate();
$common['table']=new table();
$common['tabs']=new tabs();
$common['format']=new format();

if(isset($_SESSION['site_path'])){
    $common['db']=new db('sites/' . $_SESSION['site_path'] . 'protected/db.info.php');
}
?>
