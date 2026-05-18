<?php
// Developer:  Charles Palmer
// Created:    2022.09.21
// Revision:   2022.10.10

/*
*  2022.10.10  CP  Added fail reasons
*/

class products {
  // ======================================================================================== //
  public function getListOfProducts ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $products = [];

    $results = $db->pec('SELECT productId, title, imgFilename FROM prod_v2_products WHERE active = 1 ORDER BY sortOrder',array(),'',array('productId', 'title', 'imgFilename'));
    if (count($results)) {
      $success = true;
      foreach($results as $row) {
        array_push($products, (object) [
          'productId' => $row['productId'],
          'title' => $row['title'],
          'imgFilename' => CFG_UPLOAD_URL . 'products/' . $row['imgFilename']
        ]);
      }
    }

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
      $templateInfo = $db->pec('SELECT codeset, script FROM prod_v2_products, prod_v2_templates WHERE extTemplateId=templateId AND productId = ? LIMIT 1',array($params->productId),'i',array('codeset', 'script'));
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
  public function getProduct ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $productTitle = '';

    if (isset($params->productId)) {
      $productInfo = $db->pec('SELECT title FROM prod_v2_products WHERE productId = ? LIMIT 1',array($params->productId),'i',array('title'));
      if(count($productInfo)) {
        $productTitle = $productInfo[0]['title'];
        $success = true;
      }
    }

    return (object) [
      'success' => $success,
      'productTitle' => $productTitle,
    ];
  }
  // ======================================================================================== //
  public function getSubStages ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $subStages = [];

    if (isset($params->productId) && isset($params->testerId)) {
      // Get min timestamp for training on all tests part of production app
      $results = $db->pec('SELECT extProcessProcedureId, revisionDate FROM 3026_process_procedure_versions WHERE requiresRetraining = 1 AND extProcessProcedureId IN (SELECT processProcedureId FROM 3026_process_procedures WHERE extSubStageId > 0) ORDER BY revisionDate',array(),'',array('extProcessProcedureId', 'revisionDate'));
      $timeThresholds = [];
      foreach($results as $row) {
        $timeThresholds[$row['extProcessProcedureId']] = strtotime($row['revisionDate']);
      }

      // Get all subStages for this tester
      $results = $db->pec('SELECT dateOfTraining, extSubStageId FROM 3026_training_log, 3026_process_procedures WHERE extProcessProcedureId = processProcedureId AND extSubStageId > 0 AND extEmployeeIdTrained = ? ORDER BY dateOfTraining',array($params->testerId),'i',array('dateOfTraining', 'extSubStageId'));
      $authorizedList = [];
      foreach($results as $row) {
        if (!isset($timeThresholds[$row['extSubStageId']]) || strtotime($row['dateOfTraining']) >= $timeThresholds[$row['extSubStageId']]) {
          $authorizedList[$row['extSubStageId']] = true;
        }
      }

      $results = $db->pec('SELECT subStageId, title, imgFilename FROM prod_v2_sub_stages WHERE extProductId = ? AND active = 1 ORDER BY sortOrder',array($params->productId),'i',array('subStageId', 'title', 'imgFilename'));
      if (count($results)) {
        $success = true;
        foreach($results as $row) {
          array_push($subStages, (object) [
            'subStageId' => $row['subStageId'],
            'title' => $row['title'],
            'imgFilename' => CFG_UPLOAD_URL . 'sub_stages/' . $row['imgFilename'],
            'authorized' => isset($authorizedList[$row['subStageId']]) ? true : false
          ]);
        }
      }
    }

    return (object) [
      'success' => $success,
      'subStages' => $subStages
    ];
  }
  // ======================================================================================== //
  public function getSubStageTemplate ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $instructions = '';
    $templateCode = '';
    $templateScript = '';

    if (isset($params->subStageId)) {
      $templateInfo = $db->pec('SELECT codeset, script, instructions FROM prod_v2_sub_stages, prod_v2_templates WHERE extTemplateId=templateId AND subStageId = ? LIMIT 1',array($params->subStageId),'i',array('codeset', 'script', 'instructions'));
      if(!empty($templateInfo)) {
        $success = true;
        $instructions = $templateInfo[0]['instructions'];
        $templateCode = $templateInfo[0]['codeset'];
        $templateScript = $templateInfo[0]['script'];
      }
    }

    return (object) [
      'success' => $success,
      'instructions' => $instructions,
      'template' => $templateCode,
      'script' => $templateScript
    ];
  }
  // ======================================================================================== //
  public function getProductFailReasons ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $failReasons = [];

    if (isset($params->productId)) {
      $results = $db->pec('SELECT title FROM prod_v2_product_error_types WHERE extProductId = ? AND active = 1 ORDER BY title',array($params->productId),'i',array('title'));
      if (count($results)) {
        $success = true;
        foreach($results as $row) {
          array_push($failReasons, (object) [
            'title' => $row['title']
          ]);
        }
      }
    }

    return (object) [
      'success' => $success,
      'failReasons' => $failReasons
    ];
  }
  // ======================================================================================== //
}
?>