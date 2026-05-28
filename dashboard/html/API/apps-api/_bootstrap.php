<?php
require_once(dirname(__DIR__) . '/_cors.php');

function apps_api_scheme(){
    if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])){
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]);
    }
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
}

function apps_api_host(){
    if(!empty($_SERVER['HTTP_X_FORWARDED_HOST'])){
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
    }
    return !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1:8001';
}

function apps_api_base_url(){
    return apps_api_scheme() . '://' . apps_api_host() . '/API/apps-api/';
}

function apps_api_production_base_url(){
    return apps_api_scheme() . '://' . apps_api_host() . '/production/';
}

function apps_api_prepare_legacy_endpoint(){
    pertech_api_cors_headers();

    if(getenv('PERTECH_PRODUCTION_BASE_URL') === false || getenv('PERTECH_PRODUCTION_BASE_URL') === ''){
        putenv('PERTECH_PRODUCTION_BASE_URL=' . apps_api_production_base_url());
    }
}

function apps_api_json_request(){
    $body = file_get_contents('php://input');
    $decoded = !empty($body) ? json_decode($body, true) : array();
    if(is_array($decoded)){
        return array_merge($_POST, $decoded);
    }
    return $_POST;
}

function apps_api_identifier($value, $fallback){
    $value = !empty($value) ? $value : $fallback;
    return preg_match('/^[A-Za-z0-9_]+$/', $value) ? $value : $fallback;
}

function apps_api_emit_json($payload){
    pertech_api_cors_headers('application/json');
    echo json_encode($payload);
}
?>
