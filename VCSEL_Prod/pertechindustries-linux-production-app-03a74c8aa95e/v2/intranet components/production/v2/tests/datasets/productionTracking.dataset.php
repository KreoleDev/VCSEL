<?php
// Developer:  Charles Palmer
// Created:    2022.10.03
// Revision:   2022.10.14

class productionTracking {
  // ======================================================================================== //
  public function createInitialTrackingEntry ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $productionId = 0;

    if(isset($params->productId) && isset($params->testSetId) && isset($params->testerName) && isset($params->macAddress)) {
      $primarySerialNum = isset($params->primarySerialNum) ? $params->primarySerialNum : '';
      $secondarySerialNum = isset($params->secondarySerialNum) ? $params->secondarySerialNum : '';
      $tertiarySerialNum = isset($params->tertiarySerialNum) ? $params->tertiarySerialNum : '';
      $tla = isset($params->tla) ? $params->tla : '';

      $results = $db->pec('INSERT INTO prod_v2_production_tracking SET generatedDateTime = NOW(), extProductId = ?, extTestSetId = ?, testerName = ?, macAddress = ?, primarySerialNum = ?, secondarySerialNum = ?, tertiarySerialNum = ?, tla = ?',array($params->productId, $params->testSetId, $params->testerName, $params->macAddress, $primarySerialNum, $secondarySerialNum, $tertiarySerialNum, $tla),'iissssss');
      if ($results) {
        $productionId = $db->lastInsertId();

        $success = true;
      }
    }

    return (object) [
      'success' => $success,
      'productionId' => $productionId
    ];
  }
  // ======================================================================================== //
  public function updateProduction ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;

    if(isset($params->productionId)) {
      // Get current values from db
      $productionInfo = $db->pec('SELECT primarySerialNum, secondarySerialNum, tertiarySerialNum, productionResults, tla FROM prod_v2_production_tracking WHERE productionId = ? LIMIT 1', array($params->productionId), 'i', ['primarySerialNum', 'secondarySerialNum', 'tertiarySerialNum', 'productionResults', 'tla']);
      $primarySerialNum = isset($params->primarySerialNum) ? $params->primarySerialNum : $productionInfo[0]['primarySerialNum'];
      $secondarySerialNum = isset($params->secondarySerialNum) ? $params->secondarySerialNum : $productionInfo[0]['secondarySerialNum'];
      $tertiarySerialNum = isset($params->tertiarySerialNum) ? $params->tertiarySerialNum : $productionInfo[0]['tertiarySerialNum'];
      $productionResults = isset($params->productionResults) ? json_encode($params->productionResults) : $productionInfo[0]['productionResults'];
      $tla = isset($params->tla) ? $params->tla : $productionInfo[0]['tla'];

      $success = $db->pec('UPDATE prod_v2_production_tracking SET 
        primarySerialNum = ?,
        secondarySerialNum = ?,
        tertiarySerialNum = ?,
        productionResults = ?,
        tla =? 
        WHERE productionId = ? LIMIT 1', array(
          $primarySerialNum,
          $secondarySerialNum,
          $tertiarySerialNum,
          $productionResults,
          $tla,
          $params->productionId
        ),'sssssi');
    }

    return (object) [
      'success' => $success
    ];
  }
  // ======================================================================================== //
  public function passProduction ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;

    if(isset($params->productionId)) {
      $productionInfo = $db->pec('SELECT productionResults FROM prod_v2_production_tracking WHERE productionId = ? LIMIT 1', array($params->productionId), 'i', ['productionResults']);
      $productionResults = isset($params->productionResults) ? json_encode($params->productionResults) : $productionInfo[0]['productionResults'];
      if (empty($productionResults)) {
        $productionResults = json_encode([]);
      }

      $results = $db->pec('UPDATE prod_v2_production_tracking SET passedAllTests = 1, productionResults=? WHERE productionId = ? LIMIT 1',array($productionResults, $params->productionId),'si');
      if ($results) {
        $success = true;
      }
    }

    return (object) [
      'success' => $success
    ];
  }
  // ======================================================================================== //
  public function markPacked ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;

    if(isset($params->productionId)) {
      $results = $db->pec('UPDATE prod_v2_production_tracking SET packed = 1, packedDateTime=NOW() WHERE productionId = ? LIMIT 1',array($params->productionId),'i');
      if ($results) {
        $success = true;
      }
    }

    return (object) [
      'success' => $success
    ];
  }
  // ======================================================================================== //
}
?>