<?php
// Developer:  Charles Palmer
// Created:    2022.10.15
// Revision:   2022.10.20

/*
*  2022.10.20  CP Modified to count non-serialized devices as first run, non-serialized passing tests are now counted as first run every time.
*/

class reporting {
  // ======================================================================================== //
  public function getWeeklyReport ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $payload = [];

    // Determine monday date based on today
    $today = date('Y-m-d');
    $monday = date('Y-m-d', strtotime('this week', strtotime($today)));

    $request = $db->pec('SELECT analyticsProductionId, productTitle, testSetTitle, generatedDateTime, isFirstRun, primarySerialNum, secondarySerialNum, tertiarySerialNum, serverFriendlyName FROM prod_v2_analytics_production_tracking WHERE generatedDateTime >= date(?) AND packed=1 AND (isFirstRun="yes" OR isFirstRun="unknown") ORDER BY productTitle, generatedDateTime DESC', [$monday], 's', ['analyticsProductionId', 'productTitle', 'testSetTitle', 'generatedDateTime', 'isFirstRun', 'primarySerialNum', 'secondarySerialNum', 'tertiarySerialNum', 'serverFriendlyName']);
    foreach($request as $key=>$row) {
      // Check to see if unit is a first run unit
      if ($row['isFirstRun'] == 'unknown') {
        if (!(empty($row['primarySerialNum']) && empty($row['secondarySerialNum']) && empty($row['tertiarySerialNum']))) {
          $row['primarySerialNum'] = empty($row['primarySerialNum']) ? '' : $row['primarySerialNum'];
          $row['secondarySerialNum'] = empty($row['secondarySerialNum']) ? '' : $row['secondarySerialNum'];
          $row['tertiarySerialNum'] = empty($row['tertiarySerialNum']) ? '' : $row['tertiarySerialNum'];
          $verify = $db->pec(
            'SELECT analyticsProductionId FROM prod_v2_analytics_production_tracking WHERE primarySerialNum=? AND secondarySerialNum=? AND tertiarySerialNum=? AND serverFriendlyName=? AND productTitle=? AND testSetTitle=? AND passedAllTests=1 AND generatedDateTime<? LIMIT 1', 
            [$row['primarySerialNum'], $row['secondarySerialNum'], $row['tertiarySerialNum'], $row['serverFriendlyName'], $row['productTitle'], $row['testSetTitle'], $row['generatedDateTime']],'sssssss', ['analyticsProductionId']
          );
          if (count($verify) == 0) {
            $row['isFirstRun'] = 'yes';
            $db->pec('UPDATE prod_v2_analytics_production_tracking SET isFirstRun="yes" WHERE analyticsProductionId=? LIMIT 1', [$row['analyticsProductionId']], 'i');
          } else {
            $row['isFirstRun'] = 'no';
            $db->pec('UPDATE prod_v2_analytics_production_tracking SET isFirstRun="no" WHERE analyticsProductionId=? LIMIT 1', [$row['analyticsProductionId']], 'i');
          }
        } else {
          // Production with no serial number
          $row['isFirstRun'] = 'yes';
          $db->pec('UPDATE prod_v2_analytics_production_tracking SET isFirstRun="yes" WHERE analyticsProductionId=? LIMIT 1', [$row['analyticsProductionId']], 'i');
        }
      }

      if($row['isFirstRun'] == 'yes') {
        if(!isset($payload[$row['productTitle']])) {
          $payload[$row['productTitle']] = [];
        }

        // Group entries by product and day of week
        if (!isset($payload[$row['productTitle']]['week'])) {
          $payload[$row['productTitle']]['week'] = [];
          $payload[$row['productTitle']]['week']['Monday'] = 0;
          $payload[$row['productTitle']]['week']['Tuesday'] = 0;
          $payload[$row['productTitle']]['week']['Wednesday'] = 0;
          $payload[$row['productTitle']]['week']['Thursday'] = 0;
          $payload[$row['productTitle']]['week']['Friday'] = 0;
          $payload[$row['productTitle']]['week']['Saturday'] = 0;
          $payload[$row['productTitle']]['week']['Sunday'] = 0;
        }
        $payload[$row['productTitle']]['week'][date('l', strtotime($row['generatedDateTime']))]++;

      }
    }

    // Get more detailed data for today
    $request = $db->pec('SELECT analyticsProductionId, productTitle, testSetTitle, generatedDateTime, isFirstRun, primarySerialNum, secondarySerialNum, tertiarySerialNum, serverFriendlyName FROM prod_v2_analytics_production_tracking WHERE date(generatedDateTime) = CURDATE() AND passedAllTests=1 AND (isFirstRun="yes" OR isFirstRun="unknown") ORDER BY productTitle, testSetTitle, generatedDateTime DESC', [], '', ['analyticsProductionId', 'productTitle', 'testSetTitle', 'generatedDateTime', 'isFirstRun', 'primarySerialNum', 'secondarySerialNum', 'tertiarySerialNum', 'serverFriendlyName']);
    foreach($request as $key=>$row) {
      // Check to see if unit is a first run unit
      if ($row['isFirstRun'] == 'unknown') {
        if (!(empty($row['primarySerialNum']) && empty($row['secondarySerialNum']) && empty($row['tertiarySerialNum']))) {
          $row['primarySerialNum'] = empty($row['primarySerialNum']) ? '' : $row['primarySerialNum'];
          $row['secondarySerialNum'] = empty($row['secondarySerialNum']) ? '' : $row['secondarySerialNum'];
          $row['tertiarySerialNum'] = empty($row['tertiarySerialNum']) ? '' : $row['tertiarySerialNum'];
          $verify = $db->pec(
            'SELECT analyticsProductionId FROM prod_v2_analytics_production_tracking WHERE primarySerialNum=? AND secondarySerialNum=? AND tertiarySerialNum=? AND serverFriendlyName=? AND productTitle=? AND testSetTitle=? AND passedAllTests=1 AND generatedDateTime<? LIMIT 1', 
            [$row['primarySerialNum'], $row['secondarySerialNum'], $row['tertiarySerialNum'], $row['serverFriendlyName'], $row['productTitle'], $row['testSetTitle'], $row['generatedDateTime']],'sssssss', ['analyticsProductionId']
          );

          if (count($verify) == 0) {
            $row['isFirstRun'] = 'yes';
            $db->pec('UPDATE prod_v2_analytics_production_tracking SET isFirstRun="yes" WHERE analyticsProductionId=? LIMIT 1', [$row['analyticsProductionId']], 'i');
          } else {
            $row['isFirstRun'] = 'no';
            $db->pec('UPDATE prod_v2_analytics_production_tracking SET isFirstRun="no" WHERE analyticsProductionId=? LIMIT 1', [$row['analyticsProductionId']], 'i');
          }
        } else {
          // Production with no serial number
          $row['isFirstRun'] = 'yes';
          $db->pec('UPDATE prod_v2_analytics_production_tracking SET isFirstRun="yes" WHERE analyticsProductionId=? LIMIT 1', [$row['analyticsProductionId']], 'i');
        }
      }

      if($row['isFirstRun'] == 'yes') {
        if(!isset($payload[$row['productTitle']])) {
          $payload[$row['productTitle']] = [];
        }

        if(!isset($payload[$row['productTitle']]['today'])) {
          $payload[$row['productTitle']]['today'] = [];
        }
        if(!isset($payload[$row['productTitle']]['today'][$row['testSetTitle']])) {
          $payload[$row['productTitle']]['today'][$row['testSetTitle']] = [];
          $payload[$row['productTitle']]['today'][$row['testSetTitle']]['6AM'] = 0;
          $payload[$row['productTitle']]['today'][$row['testSetTitle']]['7AM'] = 0;
          $payload[$row['productTitle']]['today'][$row['testSetTitle']]['8AM'] = 0;
          $payload[$row['productTitle']]['today'][$row['testSetTitle']]['9AM'] = 0;
          $payload[$row['productTitle']]['today'][$row['testSetTitle']]['10AM'] = 0;
          $payload[$row['productTitle']]['today'][$row['testSetTitle']]['11AM'] = 0;
          $payload[$row['productTitle']]['today'][$row['testSetTitle']]['12PM'] = 0;
          $payload[$row['productTitle']]['today'][$row['testSetTitle']]['1PM'] = 0;
          $payload[$row['productTitle']]['today'][$row['testSetTitle']]['2PM'] = 0;
          $payload[$row['productTitle']]['today'][$row['testSetTitle']]['3PM'] = 0;
          $payload[$row['productTitle']]['today'][$row['testSetTitle']]['4PM'] = 0;
          $payload[$row['productTitle']]['today'][$row['testSetTitle']]['5PM'] = 0;
          $payload[$row['productTitle']]['today'][$row['testSetTitle']]['6PM'] = 0;
        }

        // Note the lack of breaks in this switch to create additive chart
        $hour = intval(date('G', strtotime($row['generatedDateTime'])));
        switch (true) {
          case $hour<=6:
            $payload[$row['productTitle']]['today'][$row['testSetTitle']]['6AM']++;
          case $hour==7:
            $payload[$row['productTitle']]['today'][$row['testSetTitle']]['7AM']++;
          case $hour==8:
            $payload[$row['productTitle']]['today'][$row['testSetTitle']]['8AM']++;
          case $hour==9:
            $payload[$row['productTitle']]['today'][$row['testSetTitle']]['9AM']++;
          case $hour==10:
            $payload[$row['productTitle']]['today'][$row['testSetTitle']]['10AM']++;
          case $hour==11:
            $payload[$row['productTitle']]['today'][$row['testSetTitle']]['11AM']++;
          case $hour==12:
            $payload[$row['productTitle']]['today'][$row['testSetTitle']]['12PM']++;
          case $hour==13:
            $payload[$row['productTitle']]['today'][$row['testSetTitle']]['1PM']++;
          case $hour==14:
            $payload[$row['productTitle']]['today'][$row['testSetTitle']]['2PM']++;
          case $hour==15:
            $payload[$row['productTitle']]['today'][$row['testSetTitle']]['3PM']++;
          case $hour==16:
            $payload[$row['productTitle']]['today'][$row['testSetTitle']]['4PM']++;
          case $hour==17:
            $payload[$row['productTitle']]['today'][$row['testSetTitle']]['5PM']++;
          case $hour > 17:
            $payload[$row['productTitle']]['today'][$row['testSetTitle']]['6PM']++;
            break;
        }
      }
    }

    $success = true;

    return (object) [
      'success' => $success,
      'payload' => $payload
    ];
  }
  // ======================================================================================== //
}
?>