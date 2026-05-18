<?php
// Developer:  Charles Palmer
// Created:    2022.09.28
// Revision:   2022.10.05

/*
*  2022.10.05  CP  Changed to use app title instead of title
*/

class testSets {
  // ======================================================================================== //
  public function getTestSetsForSubStage ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $testSets = [];

    if(isset($params->subStageId)) {
      $results = $db->pec('SELECT testSetId, title, extTemplateId, extApiId, description, script FROM prod_v2_test_sets, prod_v2_sub_stage_test_set_associations WHERE extTestSetId = testSetId AND extSubStageId = ? AND prod_v2_sub_stage_test_set_associations.active=1 AND prod_v2_test_sets.active=1 ORDER BY sortOrder',array($params->subStageId),'i',array('testSetId', 'title', 'extTemplateId', 'extApiId', 'description', 'script'));
      if (count($results)) {
        $success = true;
        foreach($results as $row) {
          array_push($testSets, (object) [
            'testSetId' => $row['testSetId'],
            'title' => $row['title'],
            'extTemplateId' => $row['extTemplateId'],
            'extApiId' => $row['extApiId'],
            'description' => $row['description'],
            'script' => $row['script']
          ]);
        }
      }
    }

    return (object) [
      'success' => $success,
      'testSets' => $testSets
    ];
  }
  // ======================================================================================== //
  public function getProductConfigForTestSet ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $productConfig = (object) [];

    if(isset($params->testSetId)) {
      $results = $db->pec('SELECT configureProduct, extFirmwareId, productConfig FROM prod_v2_test_sets WHERE testSetId=? LIMIT 1', [$params->testSetId], 'i', ['configureProduct', 'extFirmwareId', 'productConfig']);
      if ($results[0]['configureProduct']) {
        $productConfig = json_decode($results[0]['productConfig']);
        
        // Get FW filename
        $firmwareInfo = $db->pec('SELECT filename, version, md5 FROM prod_v2_firmwares WHERE firmwareId = ? LIMIT 1', [$results[0]['extFirmwareId']], 'i', ['filename', 'version', 'md5']);
        $productConfig->firmware = $firmwareInfo[0]['filename'];
        $productConfig->firmwareVersion = $firmwareInfo[0]['version'];
        $productConfig->firmwareMd5 = $firmwareInfo[0]['md5'];
      }
      $success = true;
    }

    return (object) [
      'success' => $success,
      'productConfig' => $productConfig
    ];
  }
  // ======================================================================================== //
  public function getTestsForTestSet ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $tests = [];

    if(isset($params->testSetId)) {
      $results = $db->pec('SELECT testId, appTitle, codeset, script FROM prod_v2_tests, prod_v2_test_set_test_associations WHERE extTestId = testId AND extTestSetId = ? AND prod_v2_test_set_test_associations.active=1 AND prod_v2_tests.active=1 ORDER BY sortOrder',array($params->testSetId),'i',array('testId', 'appTitle', 'codeset', 'script'));
      if (count($results)) {
        $success = true;
        foreach($results as $row) {
          array_push($tests, (object) [
            'testId' => $row['testId'],
            'title' => $row['appTitle'],
            'codeset' => $row['codeset'],
            'script' => $row['script']
          ]);
        }
      }
    }

    return (object) [
      'success' => $success,
      'tests' => $tests
    ];
  }
  // ======================================================================================== //
}
?>