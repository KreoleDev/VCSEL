<?php
//Developer:    Charles Palmer
//Created:      2019.01.08
//Revision:     2019.01.09

/*
*   2019.01.09  CP  Moved defines to here
*/

if(!isset($_SESSION)){ session_start(); }

$uploadPath = getenv('PERTECH_PRODUCTION_UPLOAD_PATH');
if ($uploadPath === false || $uploadPath === '') {
    $uploadPath = dirname(__FILE__) . '/../../uploads/';
}

$baseUrl = getenv('PERTECH_PRODUCTION_BASE_URL');
if ($baseUrl === false || $baseUrl === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8010';
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

$common=array();
$common['db']=new db(dirname(__FILE__) . '/../../protected/db.info.php');
?>
