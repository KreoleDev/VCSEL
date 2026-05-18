<?php
// Developer:  Charles Palmer
// Created:    2022.11.07
// Revision:   2022.11.07

/*
*  
*/

class printer5300 {
  // ======================================================================================== //
  public function getTlas ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $tlas = [];

    $tlas = $db->pec('SELECT valueString FROM prod_v2_product_config_parameter_options WHERE extParameterId = 43 ORDER BY sortOrder',[], '', ['tlaNumber']);
    $success = true;

    return (object) [
      'success' => $success,
      'tlas' => $tlas
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
      $tla = $params->tla;

      // check final test status
      $results = $db->pec('SELECT productionId FROM prod_v2_production_tracking WHERE primarySerialNum=? AND tla=? AND extProductId=1 AND passedAllTests=1 AND packed=0 AND extTestSetId IN(SELECT extTestSetId FROM prod_v2_sub_stage_test_set_associations WHERE extSubStageId=14) ORDER BY generatedDateTime DESC LIMIT 1', [$params->serial, $tla], 'ss', ['productionId']);
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