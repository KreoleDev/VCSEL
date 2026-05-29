<?php
//Developer:    Charles Palmer
//Created:      2019.01.08
//Revision:     2019.01.09

/*
*   2019.01.09  CP  Moved defines to here
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
}

$uploadPath = getenv('PERTECH_PRODUCTION_UPLOAD_PATH');
if ($uploadPath === false || $uploadPath === '') {
    $uploadPath = dirname(__FILE__) . '/../../uploads/';
}

$baseUrl = getenv('PERTECH_PRODUCTION_BASE_URL');
if ($baseUrl === false || $baseUrl === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8001';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/production/index.php';
    $basePath = dirname($scriptName, 2);
    if ($basePath === DIRECTORY_SEPARATOR || $basePath === '.' || $basePath === '') {
        $basePath = '/production';
    }
    $baseUrl = $scheme . '://' . $host . rtrim($basePath, '/') . '/';
}

define('UPLOAD_URL', rtrim($uploadPath, "/\\") . DIRECTORY_SEPARATOR);
define('BASE_URL', rtrim($baseUrl, '/') . '/');

//Auto load php classes
spl_autoload_register(function($class){
    require_once(dirname(__FILE__) .  '/../../lib/classes/' . $class . '.class.php'); 
});

require_once(dirname(__DIR__, 3) . '/API/db-gateway.php');

$common=array();
$common['db']=api_db_gateway_instance();
?>
