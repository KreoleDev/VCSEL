<?php
// Developer:  Charles Palmer
// Created:    2022.09.28
// Revision:   2022.09.28

class templates {
  // ======================================================================================== //
  public function getTemplate ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $templateCode = '';
    $templateScript = '';

    if (isset($params->templateId)) {
      $templateInfo = $db->pec('SELECT codeset, script FROM prod_v2_templates WHERE templateId = ? LIMIT 1',array($params->templateId),'i',array('codeset', 'script'));
      if(!empty($templateInfo)) {
        $success = true;
        $templateCode = $templateInfo[0]['codeset'];
        $templateScript = $templateInfo[0]['script'];
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