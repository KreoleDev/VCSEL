<?php
//Developer:    Charles Palmer
//Created:      2022.10.13
//Revision:     2022.10.25

/*
*   2022.10.19  CP  Improved formatting of test results
*   2022.10.24  CP  Modified to format further nested arrays
*   2022.10.25  CP  Further formatting improvements
*/

/*
[0]     View
*/

require_once('common/includes/std_lib.inc.php');

function formatTimelapsed($seconds) {
  $minutes = floor(($seconds / 60)) % 60;
  $seconds = intval($seconds) % 60;
  return ($minutes == 0 ? '' : $minutes . 'min ') . $seconds . 'sec';
}

function formatValue($value) {
  if (is_array($value)) {
    echo '<td>';
    echo '<table class="tblReport">';
    foreach($value as $key2 => $value2) {
      echo '<tr>';
      echo '<th>'.preg_replace('/(?<!\ )[A-Z]/', ' $0', $key2).'</th>';
      echo '<td>'.formatValue($value2).'</td>';
      echo '</tr>';
    }
    echo '</table>';
    echo '</td>';
  } else {
    echo '<td>'.(strpos($value,',') ? str_replace(',',', ',$value) : $value).'</td>';
  }
}

$page_title='Daily Production Details';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  $additional_head = '
    <style>
      .error {
        background-color: rgba(255, 0, 0, 0.1);
      }
      .listReport dt{
        font-weight: bold;
        clear: left;
        float: left;
        width: 250px;
      }
      .tblReport {
        border-top: 1px solid #ccc;
        border-left: 1px solid #ccc;
      }
      .tblReport th{
        font-weight: bold;
        text-align: left;
        text-transform: uppercase;
        padding: 10px;
        border-right: 1px solid #ccc;
        border-bottom: 1px solid #ccc;
      }
      .tblReport td{
        padding: 10px;
        border-right: 1px solid #ccc;
        border-bottom: 1px solid #ccc;
      }
        .tblReport td table {
          border: 0;
        }
          .tblReport td table td, .tblReport td table th {
            border: 0;
          }
    </style>
  ';
  require_once('common/includes/header_inner.inc.php');
    
    echo '<h2>'.$page_title.'</h2>';
    echo '<p><a class="button" href="details.php?mod_id=5010&date=' . $_GET['date'] . '&productTitle=' . $_GET['productTitle'].'&testSetTitle='.$_GET['testSetTitle'].'&serverFriendlyName='.$_GET['serverFriendlyName'].'" title="Back">&lt; Back</a></p>';
    echo $common['window']->begin('#' . $_GET['extProductionId'] . ' = ' . $_GET['productTitle'] . ': ' . $_GET['testSetTitle'] . ' on ' . $_GET['date'] . ' (' . $_GET['serverFriendlyName'] . ')');

    // Get all top level records for this test
    $testInfo = $common['db']->pec(
      'SELECT analyticsProductionId, generatedDateTime, timelapseInSec, testerName, macAddress, tla, primarySerialNum, secondarySerialNum, tertiarySerialNum, analyticsResults, passedAllTests, packed, packedDateTime FROM prod_v2_analytics_production_tracking WHERE extProductionId=? ORDER BY generatedDateTime DESC LIMIT 1', 
      [$_GET['extProductionId']], 
      'i', 
      ['analyticsProductionId','generatedDateTime', 'timelapseInSec', 'testerName', 'macAddress', 'tla', 'primarySerialNum', 'secondarySerialNum', 'tertiarySerialNum', 'analyticsResults', 'passedAllTests', 'packed', 'packedDateTime']
    );
    ?>
    <dl class="listReport">
      <dt>Generated Date/Time:</dt>
      <dd><?=$testInfo[0]['generatedDateTime']; ?></dd>
      <dt>Timelapse:</dt>
      <dd><?=formatTimelapsed($testInfo[0]['timelapseInSec']); ?></dd>
      <dt>Tester Name:</dt>
      <dd><?=$testInfo[0]['testerName']; ?></dd>
      <dt>MAC Address:</dt>
      <dd><?=$testInfo[0]['macAddress']; ?></dd>
      <dt>TLA:</dt>
      <dd><?=!empty($testInfo[0]['tla']) ? $testInfo[0]['tla'] : '&nbsp;'; ?></dd>
      <dt>Primary Serial Number:</dt>
      <dd><?=!empty($testInfo[0]['primarySerialNum']) ? $testInfo[0]['primarySerialNum'] : '&nbsp;'; ?></dd>
      <dt>Secondary Serial Number:</dt>
      <dd><?=!empty($testInfo[0]['secondarySerialNum']) ? $testInfo[0]['secondarySerialNum'] : '&nbsp;'; ?></dd>
      <dt>Tertiary Serial Number:</dt>
      <dd><?=!empty($testInfo[0]['tertiarySerialNum']) ? $testInfo[0]['tertiarySerialNum'] : '&nbsp;'; ?></dd>
      <dt>Passed All Tests:</dt>
      <dd><?=$testInfo[0]['passedAllTests'] ? 'Yes' : 'No'; ?></dd>
      <dt>Packed:</dt>
      <dd><?=$testInfo[0]['packed'] ? $testInfo[0]['packedDateTime'] : '&nbsp;'; ?></dd>
    </dl><br/>
    <h3>Test Results</h3>
    <?php
    $testResults = !empty($testInfo[0]['analyticsResults']) ? json_decode($testInfo[0]['analyticsResults'], true) : [];
    echo '<table class="tblReport">';
    foreach($testResults as $key => $value) {
      if ($key != 'files') {
        echo '<tr>';
        echo '<th>'.preg_replace('/(?<!\ )[A-Z]/', ' $0', $key) .'</th>';

        formatValue($value);

        echo '</tr>';
      }
    }
    echo '</table><br/>';
    echo '<h3>Test Steps</h3>';

    $headings = ['Test', 'Passed', 'Timelapse', 'Generated Error', 'Tester Error'];
    $headingsClasses = ['', '', '', '', ''];
    echo $common['table']->begin($headings,'dataTable',$headingsClasses);
    
    $results = $common['db']->pec('SELECT generatedDateTime, testTitle, timelapsed, testPassed, failMsg, userSelectedFailure FROM prod_v2_analytics_individual_tests WHERE extAnalyticsProductionId=? ORDER BY generatedDateTime ASC', [$testInfo[0]['analyticsProductionId']], 'i', ['generatedDateTime', 'testTitle', 'timelapsed', 'testPassed', 'failMsg', 'userSelectedFailure']);
    foreach($results as $row) {
      $formatError = $row['testPassed'] ? '' : 'error';
      echo $common['table']->add_row(
        [$row['testTitle'], $row['testPassed'] ? 'Yes' : 'No', formatTimelapsed($row['timelapsed'] / 1000), $row['failMsg'], $row['testPassed'] ? '' : $row['userSelectedFailure']],
        [$formatError,$formatError.' center',$formatError.' right',$formatError,$formatError]
      );
    }
    echo $common['table']->end();

    echo $common['window']->end();
  require_once('common/includes/footer_inner.inc.php');
}
?>