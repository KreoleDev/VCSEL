<?php
//Developer:    Charles Palmer
//Created:      2022.10.13
//Revision:     2022.10.13

/*
*   
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

$page_title='Daily Production';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    $additional_head = '
    <style>
      #reportWrapper h2 {
        text-align: left;
        border-bottom: 1px solid #ccc;
      }
      #reportWrapper h3 {
        font-size:150%;
        margin-top: 20px;
        margin-bottom: 5px;
      }
    </style>
    <script>
      $(document).ready(function() {
        $("#reportDate").on("change", function() {
          let date = document.getElementById("reportDate").value;
          window.location.href = "index.php?mod_id=5010&date=" + date;
        });
      });
    </script>
    ';

    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';

        $reportDate = date('Y-m-d', strtotime($_GET['date'] ?? 'today'));

        // Get all records for today
        $results = $common['db']->pec(
          'SELECT analyticsProductionId, isFirstRun, generatedDateTime, timelapseInSec, serverFriendlyName, productTitle, testSetTitle, passedAllTests, packed, primarySerialNum, secondarySerialNum, tertiarySerialNum FROM prod_v2_analytics_production_tracking WHERE date(generatedDateTime)=date(?) ORDER BY serverFriendlyName, productTitle, testSetTitle', 
          [$reportDate], 
          's', 
          ['analyticsProductionId', 'isFirstRun','generatedDateTime','timelapseInSec', 'serverFriendlyName', 'productTitle', 'testSetTitle', 'passedAllTests', 'packed', 'primarySerialNum', 'secondarySerialNum', 'tertiarySerialNum']
        );
        $reportStructure = [];
        foreach($results as $row){
            if (!isset($reportStructure[$row['serverFriendlyName']])) {
              $reportStructure[$row['serverFriendlyName']] = [];
            }

            if (!isset($reportStructure[$row['serverFriendlyName']][$row['productTitle']])) {
              $reportStructure[$row['serverFriendlyName']][$row['productTitle']] = [];
            }

            if (!isset($reportStructure[$row['serverFriendlyName']][$row['productTitle']][$row['testSetTitle']])) {
              $reportStructure[$row['serverFriendlyName']][$row['productTitle']][$row['testSetTitle']] = [];
              $reportStructure[$row['serverFriendlyName']][$row['productTitle']][$row['testSetTitle']]['passedAllTests'] = 0;
              $reportStructure[$row['serverFriendlyName']][$row['productTitle']][$row['testSetTitle']]['packed'] = 0;
              $reportStructure[$row['serverFriendlyName']][$row['productTitle']][$row['testSetTitle']]['timelapseInSec'] = 0;
              $reportStructure[$row['serverFriendlyName']][$row['productTitle']][$row['testSetTitle']]['count'] = 0;
              $reportStructure[$row['serverFriendlyName']][$row['productTitle']][$row['testSetTitle']]['unique'] = 0;
            }

            $reportStructure[$row['serverFriendlyName']][$row['productTitle']][$row['testSetTitle']]['passedAllTests'] += $row['passedAllTests'];
            $reportStructure[$row['serverFriendlyName']][$row['productTitle']][$row['testSetTitle']]['packed'] += $row['packed'];
            $reportStructure[$row['serverFriendlyName']][$row['productTitle']][$row['testSetTitle']]['timelapseInSec'] += $row['timelapseInSec'];
            $reportStructure[$row['serverFriendlyName']][$row['productTitle']][$row['testSetTitle']]['count']++;

            // Check to see if unit has previously been through this test and passed
            if ($row['passedAllTests'] == 1 && (!empty($row['primarySerialNum']) || !empty($row['secondarySerialNum']) || !empty($row['tertiarySerialNum']))) {
              switch ($row['isFirstRun']) {
                case 'yes':
                  $reportStructure[$row['serverFriendlyName']][$row['productTitle']][$row['testSetTitle']]['unique']++;
                  break;
                case 'unknown':
                  $row['primarySerialNum'] = empty($row['primarySerialNum']) ? '' : $row['primarySerialNum'];
                  $row['secondarySerialNum'] = empty($row['secondarySerialNum']) ? '' : $row['secondarySerialNum'];
                  $row['tertiarySerialNum'] = empty($row['tertiarySerialNum']) ? '' : $row['tertiarySerialNum'];
                  $verify = $common['db']->pec(
                    'SELECT analyticsProductionId FROM prod_v2_analytics_production_tracking WHERE primarySerialNum=? AND secondarySerialNum=? AND tertiarySerialNum=? AND serverFriendlyName=? AND productTitle=? AND testSetTitle=? AND passedAllTests=1 AND generatedDateTime<? LIMIT 1', 
                    [$row['primarySerialNum'], $row['secondarySerialNum'], $row['tertiarySerialNum'], $row['serverFriendlyName'], $row['productTitle'], $row['testSetTitle'], $row['generatedDateTime']],'sssssss', ['analyticsProductionId']
                  );
                  if (count($verify) == 0) {
                    $reportStructure[$row['serverFriendlyName']][$row['productTitle']][$row['testSetTitle']]['unique']++;
                    $common['db']->pec('UPDATE prod_v2_analytics_production_tracking SET isFirstRun="yes" WHERE analyticsProductionId=? LIMIT 1', [$row['analyticsProductionId']], 'i');
                  } else {
                    $common['db']->pec('UPDATE prod_v2_analytics_production_tracking SET isFirstRun="no" WHERE analyticsProductionId=? LIMIT 1', [$row['analyticsProductionId']], 'i');
                  }
                  break;
              }
            }
        }

        echo $common['window']->begin($page_title);
          $values = [];
          $formattedDate = date('m/d/Y', strtotime($reportDate));
          $values['reportDate'] = $formattedDate;
          $frm=new frm($values,[],[]);
          echo $frm->begin_frm();
            echo $frm->begin_fieldset('');
              echo $frm->begin_dl();
                echo $frm->text('reportDate','Report Date:',false,10,'','date','','mm/dd/yyyy');
              echo $frm->end_dl();
            echo $frm->end_fieldset();
          echo $frm->end_frm();

          echo '<div id="reportWrapper">';
            $headings = ['Test', 'Passed Tests', 'Pass Rate', 'New Units To Pass', 'Packed', 'Avg Timelapse', ''];
            $headingsClasses = ['', '', '', '', '','',''];

            foreach($reportStructure as $key=>$value) {
              echo '<h2>'.$key.'</h2>';
              foreach($value as $products=>$v2) {
                echo '<h3>'.$products.'</h3>';
                echo $common['table']->begin($headings,'dataTable',$headingsClasses);
                foreach($v2 as $tests=>$v3) {
                  echo $common['table']->add_row(
                    [$tests, $v3['passedAllTests'], number_format($v3['passedAllTests'] * 100 / $v3['count'], 2) . '%', $v3['unique'], ($v3['packed'] > 0 ? $v3['packed'] : ''), ($v3['passedAllTests'] > 0 ? formatTimelapsed($v3['timelapseInSec'] / $v3['passedAllTests']) : 'N/A'), '<a href="details.php?serverFriendlyName='.$key.'&productTitle='.$products.'&testSetTitle='.$tests.'&date='.$reportDate.'">Details</a>'],
                    ['','right','right','right','right', 'right', 'center']
                  );
                }
                echo $common['table']->end();
              }
            }
          echo '</div>';
        echo $common['window']->end();
    require_once('common/includes/footer_inner.inc.php');
}
?>