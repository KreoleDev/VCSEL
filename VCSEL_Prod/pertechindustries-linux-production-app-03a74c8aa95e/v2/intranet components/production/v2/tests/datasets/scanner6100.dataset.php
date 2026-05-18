<?php
// Developer:  Charles Palmer
// Created:    2022.10.05
// Revision:   2022.10.12

class scanner6100 {
  // ======================================================================================== //
  public function getCalibrationClients ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = true;

    $clients = $db->pec('SELECT ipAddress, title, color FROM prod_v2_6100_calibration_clients WHERE active=1 ORDER BY title', [], '', ['ipAddress','title','color']);

    return (object) [
      'success' => $success,
      'clients' => $clients
    ];
  }
  // ======================================================================================== //
  public function saveRawCalibration ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;

    if (isset($params->productionId)) {
      // Create directory if not already there
			if(!is_dir(CFG_UPLOAD_PATH .'6100-test-results/')){
        mkdir(CFG_UPLOAD_PATH .'6100-test-results/',0777,true);
      }

      // Upload calibration
      $success = move_uploaded_file($_FILES['file']['tmp_name'], CFG_UPLOAD_PATH .'6100-test-results/' . $params->productionId . '.dat'); // Moving the file
    }

    return (object) [
      'success' => $success
    ];
  }
  // ======================================================================================== //
  public function uploadFile ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;

    if (isset($params->filename)) {
      // Create directory if not already there
      if(!is_dir(CFG_UPLOAD_PATH .'6100-test-results/')){
        mkdir(CFG_UPLOAD_PATH .'6100-test-results/',0777,true);
      }

      // Upload calibration
      $success = move_uploaded_file($_FILES['file']['tmp_name'], CFG_UPLOAD_PATH .'6100-test-results/' . $params->filename); // Moving the file
    }

    return (object) [
      'success' => $success
    ];
  }
  // ======================================================================================== //
  public function verifyPassProductionAndNotPacked ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $okToPack = false;
    $productionId = 0;

    if (isset($params->tla) && isset($params->serial)) {
      // Transform tla to include dash
      $tla = strtoupper($params->tla);
      $tla = substr($tla,0,6) . '-' . substr($tla,6,4);

      // check final test status
      $results = $db->pec('SELECT productionId FROM prod_v2_production_tracking WHERE primarySerialNum=? AND tla=? AND extProductId=3 AND passedAllTests=1 AND packed=0 AND extTestSetId IN(SELECT extTestSetId FROM prod_v2_sub_stage_test_set_associations WHERE extSubStageId=3) ORDER BY generatedDateTime DESC LIMIT 1', [$params->serial, $tla], 'ss', ['productionId']);
      if (isset($results[0])) {
        $okToPack = true;
        $productionId = $results[0]['productionId'];
      }
      $success = true;
    }

    return (object) [
      'success' => $success,
      'okToPack' => $okToPack,
      'productionId' => $productionId
    ];
  }
  // ======================================================================================== //
}
?>