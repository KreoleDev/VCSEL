<?php
function pertech_api_cors_headers($contentType = ''){
    $origin = getenv('PERTECH_API_CORS_ORIGIN');
    if($origin === false || $origin === ''){
        $origin = '*';
    }

    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Authorization');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

    if($contentType !== ''){
        header('Content-Type: ' . $contentType);
    }

    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
        exit;
    }
}
?>
