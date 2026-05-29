<?php
require_once(dirname(__DIR__) . '/_bootstrap.php');
apps_api_prepare_legacy_endpoint();
pertech_api_cors_headers('application/json');
require_once(dirname(__DIR__, 3) . '/production/lib/includes/includes.php');
require(dirname(__DIR__, 2) . '/camera-testing.php');
$request = apps_api_json_request();
api_camera_testing_dispatch($request);
?>
