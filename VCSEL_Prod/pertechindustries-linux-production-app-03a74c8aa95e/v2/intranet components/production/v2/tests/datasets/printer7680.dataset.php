<?php
// Developer:  Charles Palmer
// Created:    2022.10.19
// Revision:   2022.10.27

/*
*  2022.10.24  CP  Added get next vcsel serial number request
*  2022.10.25  CP  Added more security seed code, and previously packed check
*  2022.10.26  CP  Added previously packed check on secondary serial number, added upload file option
*  2022.10.27  CP  Added check to see if ready to be packed
*/

class printer7680 {
  // ======================================================================================== //
  public function getSecuritySeed ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $securitySeed = [];

    if (isset($params->requestType) && isset($params->token)) {
      switch ($params->requestType) {
        //------------------------------------------------------------------------------------ //
        case 'loadFormKey':
          array_push($securitySeed, ((($params->token[0] << 2) & 0xFB) | $params->token[1]) + 3);
          array_push($securitySeed, (($params->token[0]^$params->token[1]) << 1) - 1);
          $success = true;
          break;
        //------------------------------------------------------------------------------------ //
        case 'feederKey':
          array_push($securitySeed, (((~($params->token[0] << 1)) & $params->token[1]) + 1) & 0xFF);
          array_push($securitySeed, (~((($params->token[1] << 3) | $params->token[0]) + 1)) & 0xFF);
          $success = true;
          break;
        //------------------------------------------------------------------------------------ //
        case 'fctKey':
          array_push($securitySeed, ((($params->token[0] << 3) & 0xAB) | $params->token[1]) + 2);
          array_push($securitySeed, (($params->token[0]^$params->token[1]) << 3) - 1);
          $success = true;
          break;
        //------------------------------------------------------------------------------------ //
        case 'serialKey':
          $checksum = 0;

          $checksum ^= $params->token[0];
          $checksum ^= $params->token[1];
          $checksum ^= $params->token[2];
          $checksum ^= $params->token[3];
          $checksum ^= $params->token[4];
          $checksum ^= $params->token[5];
          $checksum ^= $params->token[6];
          $checksum ^= $params->token[7];
          $checksum ^= $params->token[8];
          $checksum ^= $params->token[9];
          $checksum ^= $params->token[10];
          $checksum ^= $params->token[11];
          $checksum ^= $params->token[12];
          $checksum ^= $params->token[13];

          $checksum |= 0xED;

          array_push($securitySeed, $checksum);
          $success = true;
          break;
        //------------------------------------------------------------------------------------ //
      }
    }

    return (object) [
      'success' => $success,
      'securitySeed' => $securitySeed
    ];
  }
  // ======================================================================================== //
  public function getNextVcselSerialNumber ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $serialNumber = 0;

    $success = $db->pec('INSERT INTO prod_v2_7680_vcsel_serial_numbers SET generatedDateTime=NOW()', [], '');
    $serialNumber = $db->lastInsertId();

    return (object) [
      'success' => $success,
      'serialNumber' => $serialNumber
    ];
  }
  // ======================================================================================== //
  public function checkIfPackedBefore ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $packedBefore = true;

    if (isset($params->primarySerialNum)) {
      $results = $db->pec('SELECT productionId FROM prod_v2_production_tracking WHERE primarySerialNum=? AND packed=1 LIMIT 1', [$params->primarySerialNum], 's', ['productionId']);
      if(!isset($results[0])) {
        $success = true;
        $packedBefore = false;
      }
    } else if (isset($params->secondarySerialNum)) {
      $results = $db->pec('SELECT productionId FROM prod_v2_production_tracking WHERE secondarySerialNum=? AND packed=1 LIMIT 1', [$params->secondarySerialNum], 's', ['productionId']);
      if(!isset($results[0])) {
        $success = true;
        $packedBefore = false;
      }
    }

    return (object) [
      'success' => $success,
      'packedBefore' => $packedBefore
    ];
  }
  // ======================================================================================== //
  public function uploadFile ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;

    if (isset($params->filename)) {
      // Create directory if not already there
      if(!is_dir(CFG_UPLOAD_PATH .'7680-test-results/')){
        mkdir(CFG_UPLOAD_PATH .'7680-test-results/',0777,true);
      }

      // Upload calibration
      $success = move_uploaded_file($_FILES['file']['tmp_name'], CFG_UPLOAD_PATH .'7680-test-results/' . $params->filename); // Moving the file
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
    $labelValues = [];

    if (isset($params->primarySerialNum) && isset($params->secondarySerialNum)) {
      // check final test status
      $results = $db->pec('SELECT productionId, primarySerialNum, secondarySerialNum, productionResults FROM prod_v2_production_tracking WHERE primarySerialNum=? AND secondarySerialNum=? AND extProductId=4 AND passedAllTests=1 AND packed=0 AND extTestSetId IN(SELECT extTestSetId FROM prod_v2_sub_stage_test_set_associations WHERE extSubStageId=9) ORDER BY generatedDateTime DESC LIMIT 1', [$params->primarySerialNum, $params->secondarySerialNum], 'ss', ['productionId', 'primarySerialNum', 'secondarySerialNum', 'productionResults']);
      if (isset($results[0])) {
        $okToPack = true;
        $productionId = $results[0]['productionId'];
        $productionResults = json_decode($results[0]['productionResults']);
        $labelValues['primarySerialNum'] = $results[0]['primarySerialNum'];
        $labelValues['secondarySerialNum'] = $results[0]['secondarySerialNum'];
        $labelValues['firmwareVersion'] = $productionResults->finalFirmwareVersion;
      }
      $success = true;
    }

    return (object) [
      'success' => $success,
      'okToPack' => $okToPack,
      'productionId' => $productionId,
      'labelValues' => $labelValues
    ];
  }
  // ======================================================================================== //
  public function verifyPassRmaMainAndVaultAndNotPacked ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $okToPack = false;
    $productionId = 0;
    $labelValues = [];

    if (isset($params->primarySerialNum) && isset($params->secondarySerialNum)) {
      // check final test status
      $results = $db->pec('SELECT productionId, primarySerialNum, secondarySerialNum, productionResults FROM prod_v2_production_tracking WHERE primarySerialNum=? AND secondarySerialNum=? AND extProductId=4 AND passedAllTests=1 AND packed=0 AND extTestSetId IN(SELECT extTestSetId FROM prod_v2_sub_stage_test_set_associations WHERE extSubStageId=12) ORDER BY generatedDateTime DESC LIMIT 1', [$params->primarySerialNum, $params->secondarySerialNum], 'ss', ['productionId', 'primarySerialNum', 'secondarySerialNum', 'productionResults']);
      if (isset($results[0])) {
        $okToPack = true;
        $productionId = $results[0]['productionId'];
        $productionResults = json_decode($results[0]['productionResults']);
        $labelValues['primarySerialNum'] = $results[0]['primarySerialNum'];
        $labelValues['secondarySerialNum'] = $results[0]['secondarySerialNum'];
        $labelValues['firmwareVersion'] = $productionResults->finalFirmwareVersion;
      }
      $success = true;
    }

    return (object) [
      'success' => $success,
      'okToPack' => $okToPack,
      'productionId' => $productionId,
      'labelValues' => $labelValues
    ];
  }
  // ======================================================================================== //
  public function verifyPassRmaMainOnlyAndNotPacked ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $okToPack = false;
    $productionId = 0;
    $labelValues = [];

    if (isset($params->primarySerialNum)) {
      // check final test status
      $results = $db->pec('SELECT productionId, primarySerialNum, productionResults FROM prod_v2_production_tracking WHERE primarySerialNum=? AND extProductId=4 AND passedAllTests=1 AND packed=0 AND extTestSetId IN(SELECT extTestSetId FROM prod_v2_sub_stage_test_set_associations WHERE extSubStageId=12) ORDER BY generatedDateTime DESC LIMIT 1', [$params->primarySerialNum], 's', ['productionId', 'primarySerialNum', 'productionResults']);
      if (isset($results[0])) {
        $okToPack = true;
        $productionId = $results[0]['productionId'];
        $productionResults = json_decode($results[0]['productionResults']);
        $labelValues['primarySerialNum'] = $results[0]['primarySerialNum'];
        $labelValues['firmwareVersion'] = $productionResults->finalFirmwareVersion;
      }
      $success = true;
    }

    return (object) [
      'success' => $success,
      'okToPack' => $okToPack,
      'productionId' => $productionId,
      'labelValues' => $labelValues
    ];
  }
  // ======================================================================================== //
}
?>