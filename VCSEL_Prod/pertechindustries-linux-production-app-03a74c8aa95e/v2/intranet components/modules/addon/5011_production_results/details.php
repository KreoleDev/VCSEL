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

$page_title='Production Details';
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
    echo '<p><a class="button" href="index.php" title="Back">&lt; Back</a></p>';

    $productionInfo = $common['db']->pec(
      'SELECT productionId, generatedDateTime, testerName, tla, primarySerialNum, secondarySerialNum, tertiarySerialNum, packed, packedDateTime, prod_v2_products.title, prod_v2_test_sets.title, productionResults, passedAllTests 
      FROM prod_v2_production_tracking, prod_v2_products, prod_v2_test_sets 
      WHERE prod_v2_production_tracking.extProductId=productId AND extTestSetId=testSetId AND productionId=? LIMIT 1', 
      [$_GET['productionId']], 
      'i', 
      ['productionId', 'generatedDateTime', 'testerName', 'tla', 'primarySerialNum', 'secondarySerialNum', 'tertiarySerialNum', 'packed', 'packedDateTime', 'productTitle', 'testSetTitle', 'productionResults', 'passedAllTests']
    );

    echo $common['window']->begin('#' . $_GET['productionId'] . ' = ' . $productionInfo[0]['productTitle'] . ': ' . $productionInfo[0]['testSetTitle'] . ' on ' . $productionInfo[0]['generatedDateTime']);

    ?>
    <dl class="listReport">
      <dt>Generated Date/Time:</dt>
      <dd><?=$productionInfo[0]['generatedDateTime']; ?></dd>
      <dt>Tester Name:</dt>
      <dd><?=$productionInfo[0]['testerName']; ?></dd>
      <dt>TLA:</dt>
      <dd><?=!empty($productionInfo[0]['tla']) ? $productionInfo[0]['tla'] : '&nbsp;'; ?></dd>
      <dt>Primary Serial Number:</dt>
      <dd><?=!empty($productionInfo[0]['primarySerialNum']) ? $productionInfo[0]['primarySerialNum'] : '&nbsp;'; ?></dd>
      <dt>Secondary Serial Number:</dt>
      <dd><?=!empty($productionInfo[0]['secondarySerialNum']) ? $productionInfo[0]['secondarySerialNum'] : '&nbsp;'; ?></dd>
      <dt>Tertiary Serial Number:</dt>
      <dd><?=!empty($productionInfo[0]['tertiarySerialNum']) ? $productionInfo[0]['tertiarySerialNum'] : '&nbsp;'; ?></dd>
      <dt>Passed All Tests:</dt>
      <dd><?=$productionInfo[0]['passedAllTests'] ? 'Yes' : 'No'; ?></dd>
      <dt>Packed:</dt>
      <dd><?=$productionInfo[0]['packed'] ? $productionInfo[0]['packedDateTime'] : '&nbsp;'; ?></dd>
    </dl><br/>
    <h3>Test Results</h3>
    <?php
    $testResults = !empty($productionInfo[0]['productionResults']) ? json_decode($productionInfo[0]['productionResults'], true) : [];
    echo '<table class="tblReport">';
    foreach($testResults as $key => $value) {
      if ($key == 'files') {
        echo '<tr><th>Files</th>';
        echo '<td><table class="tblReport">';

        foreach($value as $key2 => $value2) {
          echo '<tr>';
          echo '<th><a href="'.CFG_CMS_BASE_URL.'production/v2/tests/uploads/'.$value2.'">'.$key2.'</a></th>';
          echo '</tr>';
        }

        echo '</table></td></tr>';
      } else {
        echo '<tr>';
        echo '<th>'.preg_replace('/(?<!\ )[A-Z]/', ' $0', $key).'</th>';

        formatValue($value);

        echo '</tr>';
      }
    }
    echo '</table><br/>';

    echo $common['window']->end();
  require_once('common/includes/footer_inner.inc.php');
}
?>