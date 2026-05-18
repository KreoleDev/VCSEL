<?php
//Developer:    Charles Palmer
//Created:      2022.10.14
//Revision:     2022.10.20

/*
*   2022.10.20  CP  Corrected calculation for what happened today
*/

/*
[0]     View
*/

require_once('common/includes/std_lib.inc.php');

$page_title='Production Errors';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  $additional_head = '
  <style>
    .chartWrapper {
      width: 100%;
      height: 30vh;
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  ';

  require_once('common/includes/header_inner.inc.php');
    echo '<h2>'.$page_title.'</h2>';

    $results = $common['db']->pec(
      'SELECT generatedDateTime, productTitle, failMsg, userSelectedFailure FROM prod_v2_analytics_individual_tests WHERE testPassed=0 AND (failMsg IS NOT NULL OR userSelectedFailure IS NOT NULL) AND productTitle IS NOT NULL AND date(generatedDateTime) >= (CURDATE() - INTERVAL 3 MONTH) ORDER BY productTitle ASC', 
      [], 
      '', 
      ['generatedDateTime', 'productTitle', 'failMsg', 'userSelectedFailure']
    );

    $prepReport = [];
    foreach($results as $row) {
      if (!isset($prepReport[$row['productTitle']])) {
        $prepReport[$row['productTitle']] = [];
        $prepReport[$row['productTitle']]['user'] = [];
        $prepReport[$row['productTitle']]['system'] = [];
        $prepReport[$row['productTitle']]['workdays'] = [];
        $prepReport[$row['productTitle']]['workdays']['recentWeek'] = [];
        $prepReport[$row['productTitle']]['workdays']['recentMonth'] = [];
        $prepReport[$row['productTitle']]['workdays']['overall'] = [];
      }

      if (!empty($row['userSelectedFailure']) && !isset($prepReport[$row['productTitle']]['user'][$row['userSelectedFailure']])) {
        $prepReport[$row['productTitle']]['user'][$row['userSelectedFailure']] = [];
      }
      if (!empty($row['failMsg']) && !isset($prepReport[$row['productTitle']]['system'][$row['failMsg']])) {
        $prepReport[$row['productTitle']]['system'][$row['failMsg']] = [];
      }



      // Determine if the error is from  today
      $date = new DateTime($row['generatedDateTime']);
      $now = new DateTime();
      $diff = $now->diff($date);
      $isRecent = date('Y-m-d') == date('Y-m-d', strtotime($row['generatedDateTime']));

      // Determine if the error is within the last week
      $isRecentWeek = $diff->days < 7;

      // Determine if the error is within the last month
      $isRecentMonth = $diff->days < 30;

      // User specified
      if (!empty($row['userSelectedFailure'])) {
        if (!isset($prepReport[$row['productTitle']]['user'][$row['userSelectedFailure']]['today'])) {
          $prepReport[$row['productTitle']]['user'][$row['userSelectedFailure']]['today'] = 0;
          $prepReport[$row['productTitle']]['user'][$row['userSelectedFailure']]['recentWeek'] = 0;
          $prepReport[$row['productTitle']]['user'][$row['userSelectedFailure']]['recentMonth'] = 0;
          $prepReport[$row['productTitle']]['user'][$row['userSelectedFailure']]['overall'] = 0;
        }

        if ($isRecent) {
          $prepReport[$row['productTitle']]['user'][$row['userSelectedFailure']]['today']++;
        }
        if ($isRecentWeek) {
          $prepReport[$row['productTitle']]['user'][$row['userSelectedFailure']]['recentWeek']++;
          if (!isset($prepReport[$row['productTitle']]['workdays']['recentWeek'][date('Y-m-d',strtotime($row['generatedDateTime']))])) {
            $prepReport[$row['productTitle']]['workdays']['recentWeek'][date('Y-m-d',strtotime($row['generatedDateTime']))] = 0;
          }
        }
        if ($isRecentMonth) {
          $prepReport[$row['productTitle']]['user'][$row['userSelectedFailure']]['recentMonth']++;
          if (!isset($prepReport[$row['productTitle']]['workdays']['recentMonth'][date('Y-m-d',strtotime($row['generatedDateTime']))])) {
            $prepReport[$row['productTitle']]['workdays']['recentMonth'][date('Y-m-d',strtotime($row['generatedDateTime']))] = 0;
          }
        }
        
        $prepReport[$row['productTitle']]['user'][$row['userSelectedFailure']]['overall']++;
        if (!isset($prepReport[$row['productTitle']]['workdays']['overall'][date('Y-m-d',strtotime($row['generatedDateTime']))])) {
          $prepReport[$row['productTitle']]['workdays']['overall'][date('Y-m-d',strtotime($row['generatedDateTime']))] = 0;
        }
      }

      // System specified
      if (!empty($row['failMsg'])) {
        if(!isset($prepReport[$row['productTitle']]['system'][$row['failMsg']]['today'])) {
          $prepReport[$row['productTitle']]['system'][$row['failMsg']]['today'] = 0;
          $prepReport[$row['productTitle']]['system'][$row['failMsg']]['recentWeek'] = 0;
          $prepReport[$row['productTitle']]['system'][$row['failMsg']]['recentMonth'] = 0;
          $prepReport[$row['productTitle']]['system'][$row['failMsg']]['overall'] = 0;
        }

        if ($isRecent) {
          $prepReport[$row['productTitle']]['system'][$row['failMsg']]['today']++;
        }
        if ($isRecentWeek) {
          $prepReport[$row['productTitle']]['system'][$row['failMsg']]['recentWeek']++;
          if (!isset($prepReport[$row['productTitle']]['workdays']['recentWeek'][date('Y-m-d',strtotime($row['generatedDateTime']))])) {
            $prepReport[$row['productTitle']]['workdays']['recentWeek'][date('Y-m-d',strtotime($row['generatedDateTime']))] = 0;
          }
        }
        if ($isRecentMonth) {
          $prepReport[$row['productTitle']]['system'][$row['failMsg']]['recentMonth']++;
          if (!isset($prepReport[$row['productTitle']]['workdays']['recentMonth'][date('Y-m-d',strtotime($row['generatedDateTime']))])) {
            $prepReport[$row['productTitle']]['workdays']['recentMonth'][date('Y-m-d',strtotime($row['generatedDateTime']))] = 0;
          }
        }
        
        $prepReport[$row['productTitle']]['system'][$row['failMsg']]['overall']++;
        if (!isset($prepReport[$row['productTitle']]['workdays']['overall'][date('Y-m-d',strtotime($row['generatedDateTime']))])) {
          $prepReport[$row['productTitle']]['workdays']['overall'][date('Y-m-d',strtotime($row['generatedDateTime']))] = 0;
        }
      }
    }

    foreach($prepReport as $product=>$results) {
      echo $common['window']->begin($product);
      ?>
      <div class="chartWrapper">
        <canvas id="chart_<?=$product?>" width="400"></canvas>
      </div>
      <div class="chartWrapper">
        <canvas id="chart_<?=$product?>_system" width="400"></canvas>
      </div>
      <script>
        var ctx = document.getElementById('chart_<?=$product?>').getContext('2d');
        var myChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: ['Today', 'Past 7 days (avg per work day)', 'Past 30 days (avg per work day)', '3 Month (avg per work day)'],
            datasets: [
              <?php
              foreach($results['user'] as $error=>$counts) {
                // build color
                $red = rand(0, 255);
                $green = rand(0, 255);
                $blue = rand(0, 255);
                ?>
                {
                  label: '<?=$error?>',
                  data: [<?=$counts['today']?>, <?=$counts['recentWeek'] / (count($prepReport[$product]['workdays']['recentWeek']) > 0 ? count($prepReport[$product]['workdays']['recentWeek']) : 1)?>, <?=$counts['recentMonth'] / (count($prepReport[$product]['workdays']['recentMonth']) > 0 ? count($prepReport[$product]['workdays']['recentMonth']) : 1)?>, <?=$counts['overall'] / (count($prepReport[$product]['workdays']['overall']) > 0 ? count($prepReport[$product]['workdays']['overall']) : 1)?>],
                  borderWidth: 1,
                  backgroundColor: 'rgba(<?=$red;?>,<?=$green;?>, <?=$blue; ?>, 0.4)',
                },
                <?php
              }
              ?>
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true
              }
            },
            plugins: {
              title: {
                display: true,
                text: 'User Selected Failures',
                font: {
                  size: 20
                }
              }
            }
          }
        });

        var ctx = document.getElementById('chart_<?=$product?>_system').getContext('2d');
        var myChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: ['Today', 'Past 7 days (avg per work day)', 'Past 30 days (avg per work day)', '3 Month (avg per work day)'],
            datasets: [
              <?php
              foreach($results['system'] as $error=>$counts) {
                // build color
                $red = rand(0, 255);
                $green = rand(0, 255);
                $blue = rand(0, 255);
                ?>
                {
                  label: '<?=$error?>',
                  data: [<?=$counts['today']?>, <?=$counts['recentWeek'] / (count($prepReport[$product]['workdays']['recentWeek']) > 0 ? count($prepReport[$product]['workdays']['recentWeek']) : 1)?>, <?=$counts['recentMonth'] / (count($prepReport[$product]['workdays']['recentMonth']) > 0 ? count($prepReport[$product]['workdays']['recentMonth']) : 1)?>, <?=$counts['overall'] / (count($prepReport[$product]['workdays']['overall']) > 0 ? count($prepReport[$product]['workdays']['overall']) : 1)?>],
                  borderWidth: 1,
                  backgroundColor: 'rgba(<?=$red;?>,<?=$green;?>, <?=$blue; ?>, 0.4)',
                },
                <?php
              }
              ?>
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true
              }
            },
            plugins: {
              title: {
                display: true,
                text: 'System Reported Failures',
                font: {
                  size: 20
                }
              }
            }
          }
        });
      </script>
      <?php
      echo $common['window']->end();
    }

    
  require_once('common/includes/footer_inner.inc.php');
}
?>