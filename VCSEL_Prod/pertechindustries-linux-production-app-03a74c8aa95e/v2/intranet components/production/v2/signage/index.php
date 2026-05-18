<?php
// Developer:   Charles Palmer
// Created:     2022.09.08
// Revision:    2022.09.21
// Description: Ported from I Made It website

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With, Token, Content-Type');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

define('CFG_VERSION', '1.00.000');
define('CFG_UPLOAD_URL', 'http://192.168.1.55/production/v2/signage/uploads/');
define('CFG_UPLOAD_PATH', '/var/www/html/production/v2/signage/uploads/');
define('CFG_SERVER_FRIENDLY_NAME', 'Digital Signage');
define('CFG_SERVER_NAME', 'signageServer');

$trustedDomains = [
  
];

$ipWhitelist = [
  
];

require_once(__DIR__ . '/classes/db.class.php');
$db = new db(__DIR__ . '/protected/db.inc.php');

$headers = apache_request_headers();

// Determine if the request is coming from a trusted domain
$isTrustedDomain = false;
foreach ($trustedDomains as $domain) {
  if (strpos($headers['Origin'], $domain) !== false) {
    $isTrustedDomain = true;
    break;
  }
}

// Decrypt request
$postdata = file_get_contents("php://input");
if (isset($postdata)) {
  $request = json_decode($postdata);

  
  if (isset($request->query) && isset($request->query->action) && isset($request->query->params) && isset($request->query->dataset)) {
    // Handle queries
    // ================================================= //
    if (file_exists(__DIR__ . '/datasets/' . $request->query->dataset . '.dataset.php')) {
      require_once(__DIR__ . '/datasets/' . $request->query->dataset . '.dataset.php');
      $datasetInstance = new $request->query->dataset();
      if (method_exists($datasetInstance, $request->query->action)) {
        $response = $datasetInstance->{$request->query->action}($request->query->params);
        echo json_encode($response);
      } else {
        http_response_code(501);
      }
    } else {
      http_response_code(501);
    }
    // ================================================= //
  } else if (empty($request) && isset($_POST)) {
    // Handle uploads
    // ================================================= //
    if (isset($_POST['dataset']) && isset($_POST['action'])) {
      $params = (object) [];
  
      foreach($_POST as $key => $value) {
        if($key != 'dataset' && $key != 'action') {
          $params->{$key} = $value;
        }
      }
  
      if (file_exists(__DIR__ . '/datasets/' . $_POST['dataset'] . '.dataset.php')) {
        require_once(__DIR__ . '/datasets/' . $_POST['dataset'] . '.dataset.php');
        $datasetInstance = new $_POST['dataset']();
        if (method_exists($datasetInstance, $_POST['action'])) {
          $response = $datasetInstance->{$_POST['action']}($params);
          echo json_encode($response);
        } else {
          http_response_code(501);
        }
      } else {
        http_response_code(501);
      }
    }
    // ================================================= //
  } else {
    http_response_code(400);
  }
  // ================================================= //
}
?>