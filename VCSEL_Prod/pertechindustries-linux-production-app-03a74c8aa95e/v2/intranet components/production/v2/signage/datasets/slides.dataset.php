<?php
// Developer:  Charles Palmer
// Created:    2022.10.14
// Revision:   2022.10.16

/*
*  
*/

class slides {
  // ======================================================================================== //
  public function getSlidesForProduct ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $slides = [];

    if (isset($params->productId)) {
      switch($params->productId) {
        case 1:
          array_push($slides, (object) [
            'templateUrl' => CFG_UPLOAD_URL . 'templates/weeklyReport.php',
            'scriptUrl' => CFG_UPLOAD_URL . 'templates/weeklyReport.js',
          ]);
          array_push($slides, (object) [
            'templateUrl' => CFG_UPLOAD_URL . 'templates/signMessage.php',
            'scriptUrl' => CFG_UPLOAD_URL . 'templates/signMessage.js',
          ]);
          $success = true;
          break;
      }
    }

    return (object) [
      'success' => $success,
      'slides' => $slides
    ];
  }
  // ======================================================================================== //
  public function getMessage ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $message = '';

    $messageInfo = $db->pec('SELECT message FROM 7011_messages WHERE messageId=1 LIMIT 1', [], '', ['message']);
    $message = nl2br($messageInfo[0]['message']);
    $success = true;

    return (object) [
      'success' => $success,
      'message' => $message
    ];
  }
  // ======================================================================================== //
}
?>