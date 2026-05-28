<?php
require_once(__DIR__ . '/_cors.php');
pertech_api_cors_headers('application/json');

$basePath = dirname($_SERVER['SCRIPT_NAME']);
$endpoints = array(
    'products.php' => array(
        'methods' => array('POST'),
        'modes' => array('getActiveProducts')
    ),
    'tlas.php' => array(
        'methods' => array('POST'),
        'modes' => array('getActiveTLAs', 'getTLAInfo')
    ),
    'tests.php' => array(
        'methods' => array('POST'),
        'modes' => array('getTests')
    ),
    'version.php' => array(
        'methods' => array('POST'),
        'modes' => array('getCurrentVersion')
    ),
    'shipping-tests.php' => array(
        'methods' => array('POST'),
        'modes' => array('getCurrentOnPallet', 'startNewPallet', 'finalizePallet', 'processScan')
    ),
    '7680-printer-tests.php' => array(
        'methods' => array('POST'),
        'modes' => array(
            'testFormsKey',
            'fctKey',
            'loadFormKey',
            'feederKey',
            'serialKey',
            'checkSerial',
            'saveSerial',
            'checkSerialVault',
            'saveSerialVault',
            'saveTalliesAndConfig',
            'saveImg',
            'saveImgVault'
        )
    ),
    '7680-vcsel-tests.php' => array(
        'methods' => array('POST'),
        'modes' => array('saveVcselResults', 'getNextVcselSN')
    ),
    'vcsel-results.php' => array(
        'methods' => array('POST'),
        'modes' => array('getVcselResults', 'getVcselResultsPage', 'addVcselNote')
    ),
    'db-gateway.php' => array(
        'methods' => array('internal'),
        'modes' => array('legacy database gateway')
    ),
    'apps-api/' => array(
        'methods' => array('GET', 'POST'),
        'modes' => array('Electron production app API contract')
    ),
    '7680-board-tests.php' => array(
        'methods' => array('POST'),
        'modes' => array('saveBoardTestResults')
    )
);

foreach($endpoints as $file => $info){
    $endpoints[$file]['url'] = rtrim($basePath, '/') . '/' . $file;
}

echo json_encode(array(
    'name' => 'Pertech API',
    'database' => 'pertech',
    'endpoints' => $endpoints
), JSON_PRETTY_PRINT);
?>
