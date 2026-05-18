<?php
// Developer:  Charles Palmer
// Created:    2022.09.22
// Revision:   2022.09.22

class apis {
  // ======================================================================================== //
  public function getApi ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $script = '';

    if (isset($params->apiId)) {
      $apiInfo = $db->pec('SELECT script FROM prod_v2_apis WHERE apiId = ? LIMIT 1',array($params->apiId),'i',array('script'));
      if(!empty($apiInfo)) {
        $success = true;
        $script = $apiInfo[0]['script'];
      }
    }

    return (object) [
      'success' => $success,
      'script' => $script
    ];
  }
  // ======================================================================================== //
}
?>
