<?php
// Developer:  Charles Palmer
// Created:    2022.10.03
// Revision:   2022.10.14

/*
*  2022.10.05  CP  Added timelapse calculation
*/

class productionTracking {
  // ======================================================================================== //
  public function createInitialTrackingEntry ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $analyticsProductionId = 0;

    if(
      isset($params->serverFriendlyName) &&
      isset($params->productId) && 
      isset($params->productTitle) &&
      isset($params->testSetId) && 
      isset($params->testSetTitle) &&
      isset($params->testerName) && 
      isset($params->macAddress) && 
      isset($params->productionId)
    ) {

      $primarySerialNum = isset($params->primarySerialNum) ? $params->primarySerialNum : '';
      $secondarySerialNum = isset($params->secondarySerialNum) ? $params->secondarySerialNum : '';
      $tertiarySerialNum = isset($params->tertiarySerialNum) ? $params->tertiarySerialNum : '';
      $tla = isset($params->tla) ? $params->tla : '';

      $results = $db->pec('INSERT INTO prod_v2_analytics_production_tracking SET 
        generatedDateTime = NOW(), 
        serverFriendlyName = ?,
        extProductionId = ?, 
        extProductId = ?, 
        productTitle = ?,
        extTestSetId = ?,
        testSetTitle = ?, 
        testerName = ?, 
        macAddress = ?,
        primarySerialNum = ?,
        secondarySerialNum = ?,
        tertiarySerialNum = ?,
        tla = ?
      ', array(
        $params->serverFriendlyName,
        $params->productionId, 
        $params->productId, 
        $params->productTitle,
        $params->testSetId, 
        $params->testSetTitle,
        $params->testerName, 
        $params->macAddress,
        $primarySerialNum,
        $secondarySerialNum,
        $tertiarySerialNum,
        $tla
      ),'siisisssssss');
      if ($results) {
        $analyticsProductionId = $db->lastInsertId();
        $success = true;
      }
    }

    return (object) [
      'success' => $success,
      'analyticsProductionId' => $analyticsProductionId
    ];
  }
  // ======================================================================================== //
  public function updateProduction ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;

    if(isset($params->analyticsProductionId)) {
      // Get current values from db
      $productionInfo = $db->pec('SELECT primarySerialNum, secondarySerialNum, tertiarySerialNum, analyticsResults, tla FROM prod_v2_analytics_production_tracking WHERE analyticsProductionId = ? LIMIT 1', array($params->analyticsProductionId), 'i', ['primarySerialNum', 'secondarySerialNum', 'tertiarySerialNum', 'analyticsResults', 'tla']);
      $primarySerialNum = isset($params->primarySerialNum) ? $params->primarySerialNum : $productionInfo[0]['primarySerialNum'];
      $secondarySerialNum = isset($params->secondarySerialNum) ? $params->secondarySerialNum : $productionInfo[0]['secondarySerialNum'];
      $tertiarySerialNum = isset($params->tertiarySerialNum) ? $params->tertiarySerialNum : $productionInfo[0]['tertiarySerialNum'];
      $analyticsResults = isset($params->analyticsResults) ? json_encode($params->analyticsResults) : $productionInfo[0]['analyticsResults'];
      $tla = isset($params->tla) ? $params->tla : $productionInfo[0]['tla'];

      $success = $db->pec('UPDATE prod_v2_analytics_production_tracking SET 
        primarySerialNum = ?,
        secondarySerialNum = ?,
        tertiarySerialNum = ?,
        analyticsResults = ?,
        tla = ?
        WHERE analyticsProductionId = ? LIMIT 1', array(
          $primarySerialNum,
          $secondarySerialNum,
          $tertiarySerialNum,
          $analyticsResults,
          $tla,
          $params->analyticsProductionId
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
      $productionInfo = $db->pec('SELECT analyticsResults FROM prod_v2_analytics_production_tracking WHERE extProductionId = ? LIMIT 1', array($params->productionId), 'i', ['analyticsResults']);
      $analyticsResults = isset($params->analyticsResults) ? json_encode($params->analyticsResults) : $productionInfo[0]['analyticsResults'];
      if (empty($analyticsResults)) {
        $analyticsResults = json_encode([]);
      }

      $results = $db->pec('UPDATE prod_v2_analytics_production_tracking SET passedAllTests = 1, timelapseInSec = TIMEDIFF(NOW(), generatedDateTime), analyticsResults=? WHERE extProductionId = ? LIMIT 1',array($analyticsResults, $params->productionId),'si');
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
      $results = $db->pec('UPDATE prod_v2_analytics_production_tracking SET packed = 1, packedDateTime=NOW() WHERE extProductionId = ? LIMIT 1',array($params->productionId),'i');
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