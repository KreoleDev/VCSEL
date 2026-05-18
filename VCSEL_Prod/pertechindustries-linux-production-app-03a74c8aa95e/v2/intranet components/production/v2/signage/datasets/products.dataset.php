<?php
// Developer:  Charles Palmer
// Created:    2022.10.14
// Revision:   2022.10.14

/*
*  
*/

class products {
  // ======================================================================================== //
  public function getListOfProducts ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $products = [];

    array_push($products, (object) [
      'productId' => 1,
      'title' => 'Production',
      'imgFilename' => CFG_UPLOAD_URL . 'products/production.png'
    ]);
    $success = true;

    return (object) [
      'success' => $success,
      'serverFriendlyName' => CFG_SERVER_FRIENDLY_NAME,
      'serverName' => CFG_SERVER_NAME,
      'products' => $products
    ];
  }
  // ======================================================================================== //
  public function getProductTemplate ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $templateCode = '';
    $templateScript = '';

    

    if (isset($params->productId)) {
      switch ($params->productId) {
        case 1: /* Production */
          $templateCode = file_get_contents(CFG_UPLOAD_PATH . 'templates/fullscreenPrompt.php');
          $templateScript = file_get_contents(CFG_UPLOAD_PATH . 'templates/fullscreenPrompt.js');
          $success = true;
          break;
        default:
          $success = false;
          break;
      }
    }

    return (object) [
      'success' => $success,
      'template' => $templateCode,
      'script' => $templateScript
    ];
  }
  // ======================================================================================== //
}
?>