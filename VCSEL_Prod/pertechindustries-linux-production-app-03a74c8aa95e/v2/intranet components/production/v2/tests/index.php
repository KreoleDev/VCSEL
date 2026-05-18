<?php
// Developer:   Charles Palmer
// Created:     2022.09.08
// Revision:    2022.09.21
// Description: Ported from I Made It website

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With, Token, Content-Type');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

define('CFG_VERSION', '1.00.000');
define('CFG_SERVER_FRIENDLY_NAME', 'Development Server');
define('CFG_SERVER_NAME', 'productionDevServer');

$scheme = !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
  ? $_SERVER['HTTP_X_FORWARDED_PROTO']
  : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
$host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/production/v2/tests/index.php')), '/');

define('CFG_UPLOAD_URL', $scheme . '://' . $host . $scriptDir . '/uploads/');
define('CFG_UPLOAD_PATH', __DIR__ . '/uploads/');

$trustedDomains = [
  
];

$ipWhitelist = [
  
];

require_once(__DIR__ . '/classes/db.class.php');
$db = new db(__DIR__ . '/protected/db.inc.php');

$headers = function_exists('getallheaders')
  ? getallheaders()
  : (function_exists('apache_request_headers') ? apache_request_headers() : []);
$origin = $headers['Origin'] ?? ($headers['origin'] ?? '');

// Determine if the request is coming from a trusted domain
$isTrustedDomain = false;
foreach ($trustedDomains as $domain) {
  if ($origin !== '' && strpos($origin, $domain) !== false) {
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
