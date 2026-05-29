<?php
require_once(__DIR__ . '/_bootstrap.php');

apps_api_emit_json(array(
    'name' => 'Pertech Production Apps API',
    'base_url' => apps_api_base_url(),
    'endpoints' => array(
        'login' => apps_api_base_url() . 'login/',
        'version' => apps_api_base_url() . 'version/',
        'products' => apps_api_base_url() . 'products/',
        'tlas' => apps_api_base_url() . 'tlas/',
        'tests' => apps_api_base_url() . 'tests/',
        'tests_load' => apps_api_base_url() . 'tests/load-tests.php?tla_id={tla_id}',
        '7680_printer_tests' => apps_api_base_url() . '7680-printer-tests/',
        '7680_vcsel_tests' => apps_api_base_url() . '7680-vcsel-tests/',
        '7680_board_tests' => apps_api_base_url() . '7680-board-tests/',
        'shipping_tests' => apps_api_base_url() . 'shipping-tests/',
        'camera_testing' => apps_api_base_url() . 'camera-testing/'
    )
));
?>
