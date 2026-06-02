<?php
//Developer:    Charles Palmer
//Created:      2014.04.15
//Revision:     2015.05.26

function pertech_cms_env_value($name){
    $value=getenv($name);
    return $value!==false?$value:null;
}

function pertech_cms_detect_scheme(){
    $scheme=pertech_cms_env_value('PERTECH_CMS_SCHEME');
    if(!empty($scheme)){
        return $scheme;
    }

    if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])){
        return trim(explode(',',$_SERVER['HTTP_X_FORWARDED_PROTO'])[0]);
    }

    if(!empty($_SERVER['REQUEST_SCHEME'])){
        return $_SERVER['REQUEST_SCHEME'];
    }

    if(!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS'])!='off'){
        return 'https';
    }

    return 'http';
}

function pertech_cms_detect_host(){
    $host=pertech_cms_env_value('PERTECH_CMS_HOST');
    if(!empty($host)){
        return $host;
    }

    if(!empty($_SERVER['HTTP_X_FORWARDED_HOST'])){
        return trim(explode(',',$_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
    }

    if(!empty($_SERVER['HTTP_HOST'])){
        return $_SERVER['HTTP_HOST'];
    }

    $serverName=!empty($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'127.0.0.1';
    $serverPort=!empty($_SERVER['SERVER_PORT'])?(int)$_SERVER['SERVER_PORT']:8001;

    if(($serverPort===80 && pertech_cms_detect_scheme()==='http') || ($serverPort===443 && pertech_cms_detect_scheme()==='https')){
        return $serverName;
    }

    return $serverName . ':' . $serverPort;
}

$cmsName=pertech_cms_env_value('PERTECH_CMS_NAME');
$cmsName=!empty($cmsName)?$cmsName:'Pertech Products';

$baseUrl=pertech_cms_env_value('PERTECH_CMS_BASE_URL');
if(empty($baseUrl)){
    $baseUrl=pertech_cms_detect_scheme() . '://' . pertech_cms_detect_host();
}

$includePath=pertech_cms_env_value('PERTECH_CMS_INCLUDE_PATH');
if(empty($includePath)){
    $includePath=dirname(__DIR__);
}

$singleSite=pertech_cms_env_value('PERTECH_CMS_SINGLE_SITE');
$singleSite=!empty($singleSite)?$singleSite:'pertech';

define('CFG_CMS_NAME',$cmsName);
define('CFG_CMS_BASE_URL',rtrim(str_replace('\\','/',$baseUrl),'/') . '/');
define('CFG_CMS_INCLUDE_PATH',rtrim(str_replace('\\','/',$includePath),'/') . '/');

define('CFG_MULTI_SITE',false);
define('CFG_SINGLE_SITE',$singleSite); //Only used if MULTI_SITE is set to false

?>
