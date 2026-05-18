<?php
//Developer:    Charles Palmer
//Created:      2022.10.13
//Revision:     2022.10.19

/*
*   2022.10.19  CP  Bug fix on mispelling of tertiary
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

$page_title='Daily Production Details';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  $additional_head = '
    <style>
      .error {
        background-color: rgba(255, 0, 0, 0.1);
      }
    </style>
  ';
  require_once('common/includes/header_inner.inc.php');
    
    echo '<h2>'.$page_title.'</h2>';
    echo '<p><a class="button" href="index.php?mod_id=5010&date=' . $_GET['date'] . '" title="Back">&lt; Back</a></p>';
    echo $common['window']->begin($_GET['productTitle'] . ': ' . $_GET['testSetTitle'] . ' on ' . $_GET['date'] . ' (' . $_GET['serverFriendlyName'] . ')');

      $results = $common['db']->pec(
        'SELECT generatedDateTime, timelapseInSec, extProductionId, testerName, macAddress, tla, primarySerialNum, secondarySerialNum, tertiarySerialNum, passedAllTests, packed, packedDateTime 
        FROM prod_v2_analytics_production_tracking 
        WHERE date(generatedDateTime)=date(?) AND serverFriendlyName=? AND productTitle=? AND testSetTitle=? ORDER BY generatedDateTime', 
        [$_GET['date'], $_GET['serverFriendlyName'], $_GET['productTitle'], $_GET['testSetTitle']], 
        'ssss', 
        ['generatedDateTime', 'timelapseInSec', 'extProductionId', 'testerName', 'macAddress', 'tla', 'primarySerialNum', 'secondarySerialNum', 'tertiarySerialNum', 'passedAllTests', 'packed', 'packedDateTime']
      );

      $headings = ['Time', 'Passed', 'Tester', 'TLA', 'Serial Numbers', 'Packed', 'Timelapse', ''];
      $headingsClasses = ['','','','','','','','','','no_sort'];
      echo $common['table']->begin($headings,'sortable',$headingsClasses);

      foreach($results as $row) {
        $hasError = $row['passedAllTests'] == 0 ? 'error' : '';
        echo $common['table']->add_row(
          [date('H:i', strtotime($row['generatedDateTime'])), $row['passedAllTests'] == 1 ? 'Yes' : '', $row['testerName'] . '<br>MAC Address: '.$row['macAddress'], $row['tla'], (!empty($row['primarySerialNum']) ? 'Primary: ' . $row['primarySerialNum'] . '<br>' : '') . (!empty($row['secondarySerialNum']) ? 'Secondary: ' . $row['secondarySerialNum'] . '<br>' : '') . (!empty($row['tertiarySerialNum']) ? 'Teriary: ' . $row['tertiarySerialNum'] : ''), $row['packed'] == 1 ? date('Y-m-d H:i', strtotime($row['packedDateTime'])) : '', ($row['timelapseInSec'] > 0 ? formatTimelapsed($row['timelapseInSec']) : ''), '<a href="test-details.php?mod_id=5010&date=' . $_GET['date'] . '&serverFriendlyName=' . $_GET['serverFriendlyName'] . '&productTitle=' . $_GET['productTitle'] . '&testSetTitle=' . $_GET['testSetTitle'] . '&extProductionId=' . $row['extProductionId'] . '" title="View Details">View Details</a>'],
          [$hasError, $hasError . ' center', $hasError, $hasError . ' right', $hasError, $hasError, $hasError . ' right', $hasError . ' center']
        );
      }

      echo $common['table']->end();

    echo $common['window']->end();
  require_once('common/includes/footer_inner.inc.php');
}
?>