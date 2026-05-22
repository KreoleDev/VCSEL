<?php
//Developer:  Charles Palmer
//Created:    2020.10.01
//Revision:   2020.10.15

/*
*   2020.10.15  CP  Corrected bug on counting production test numbers that were not completed
*/

if(!isset($_SESSION)){ session_start(); }

//Auto load php classes
spl_autoload_register(function($class){
  require_once(dirname(__FILE__) .  '/../common/classes/' . $class . '.class.php'); 
});

$common=array();
$common['db']=new api_db();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

function preprocessForJSON($data) {
	return json_encode(str_replace("&amp;","&",str_replace("&amp;amp;","&",str_replace("&amp;#39;","'",str_replace("\'","'",str_replace("&#039;","'",$data))))), JSON_HEX_QUOT | JSON_HEX_TAG);
}

$postdata = file_get_contents("php://input");
if (isset($postdata)) {
  $request = json_decode($postdata);
  
  switch($request->mode){
    //---------------------------------------------------------
    case 'getWeekProduction':
      // Determine monday time and saturday time to select in range from db
      $curDateTimestamp = strtotime(date('Y-m-d'));
      $curDayOfWeek = date('N');
      $startTimestamp = $curDateTimestamp - (($curDayOfWeek - 1) * 24 * 60 * 60);
      $endTimestamp = $startTimestamp + (432000);

      // Get target amounts for current production
      $targetInfo = $common['db']->pec('SELECT firstUsableId, qtyNeeded, dueDate FROM 2019_prod_7680_target_dates WHERE dueDate > NOW() LIMIT 1',array(),'',array('firstUsableId', 'qtyNeeded', 'dueDate'));
      $dailyTarget = 0;
      if(isset($targetInfo[0])) {
        $remaining = $targetInfo[0]['qtyNeeded'];
        // Get count of completed ones for order
        $countInfo = $common['db']->pec('SELECT count(*) FROM 2019_prod_7680_printer_results WHERE test_id >= ? AND date_time < ?', array($targetInfo[0]['firstUsableId'],date('Y-m-d 00:00:00',$curDateTimestamp)),'is',array('count'));
        if(isset($countInfo[0])) {
          $remaining -= $countInfo[0]['count'];
        }

        // Determine remaining days for order including today
        $watchTimestamp = $curDateTimestamp;
        $dueTimestamp = strtotime($targetInfo[0]['dueDate']);
        $daysRemaining = 0;
        while($watchTimestamp < $dueTimestamp) {
          if(date('N', $watchTimestamp) != 6 && date('N', $watchTimestamp) != 7) {
            $daysRemaining++;
          }
          $watchTimestamp += (24 * 60 * 60);
        }

        if ($daysRemaining > 0) {
          $dailyTarget = ceil($remaining / $daysRemaining);
        } else {
          $dailyTarget = $remaining;
        }
      }

      $results = $common['db']->pec('SELECT date_time, test_id FROM 2019_prod_7680_printer_results WHERE date_time >= ? AND date_time < ? AND additional_notes IS NOT NULL ORDER BY date_time',array(date('Y-m-d 00:00:00', $startTimestamp), date('Y-m-d 00:00:00', $endTimestamp)),'ss',array('date_time', 'test_id'));
      
      $monday = 0;
      $tuesday = 0;
      $wednesday = 0;
      $thursday = 0;
      $friday = 0;
      $mondayTarget = 0;
      $tuesdayTarget = 0;
      $wednesdayTarget = 0;
      $thursdayTarget = 0;
      $fridayTarget = 0;

      foreach($results as $row) {
        switch (date('N', strtotime($row['date_time']))) {
          case 1:
            $monday++;
          break;
          case 2:
            $tuesday++;
          break;
          case 3:
            $wednesday++;
          break;
          case 4:
            $thursday++;
          break;
          case 5:
            $friday++;
          break;
        }
      }

      for($i = 1; $i <= 5; $i++) {
        if ($curDayOfWeek <= $i) {
          switch ($i) {
            case 1:
              $mondayTarget = $dailyTarget;
            break;
            case 2:
              $tuesdayTarget = $dailyTarget;
            break;
            case 3:
              $wednesdayTarget = $dailyTarget;
            break;
            case 4:
              $thursdayTarget = $dailyTarget;
            break;
            case 5:
              $fridayTarget = $dailyTarget;
            break;
          }
        }
      }
      echo '{
        "data": ['.$monday.', '.$tuesday.', '.$wednesday.', '.$thursday.', '.$friday.'],
        "dataTarget": ['.$mondayTarget.', '.$tuesdayTarget.', '.$wednesdayTarget.', '.$thursdayTarget.', '.$fridayTarget.'],
        "backgroundColor": [
          "rgba(75, 192, 192, 0.7)",
          "rgba(75, 192, 192, 0.7)",
          "rgba(75, 192, 192, 0.7)",
          "rgba(75, 192, 192, 0.7)",
          "rgba(75, 192, 192, 0.7)"
        ],
        "borderColor": [
          "rgba(75, 192, 192, 1)",
          "rgba(75, 192, 192, 1)",
          "rgba(75, 192, 192, 1)",
          "rgba(75, 192, 192, 1)",
          "rgba(75, 192, 192, 1)"
        ],
        "backgroundColorTarget": [
          "rgba(128, 128, 128, 0.7)",
          "rgba(128, 128, 128, 0.7)",
          "rgba(128, 128, 128, 0.7)",
          "rgba(128, 128, 128, 0.7)",
          "rgba(128, 128, 128, 0.7)"
        ],
        "borderColorTarget": [
          "rgba(128, 128, 128, 1)",
          "rgba(128, 128, 128, 1)",
          "rgba(128, 128, 128, 1)",
          "rgba(128, 128, 128, 1)",
          "rgba(128, 128, 128, 1)"
        ]
      }';
    break;
    //---------------------------------------------------------
    case 'getOrderCompletion':
      $completed = 0;
      $notCompleted = 100;

      //$lastCompleted = $common['db']->pec('SELECT test_id FROM 2019_prod_7680_printer_results WHERE additional_notes IS NOT NULL ORDER BY test_id DESC LIMIT 1',array(),'',array('test_id'));
      //$currentTarget = $common['db']->pec('SELECT firstUsableId, qtyNeeded, dueDate FROM 2019_prod_7680_target_dates WHERE firstUsableId < ? ORDER BY firstUsableId DESC LIMIT 1', array($lastCompleted[0]['test_id']),'i',array('firstUsableId', 'qtyNeeded', 'dueDate'));
      $currentTarget = $common['db']->pec('SELECT firstUsableId, qtyNeeded, dueDate FROM 2019_prod_7680_target_dates WHERE startDate < NOW() && dueDate > NOW() LIMIT 1', array(),'',array('firstUsableId', 'qtyNeeded', 'dueDate'));
      if(isset($currentTarget[0])) {
        $countInfo = $common['db']->pec('SELECT count(*) FROM 2019_prod_7680_printer_results WHERE test_id >= ? AND additional_notes IS NOT NULL', array($currentTarget[0]['firstUsableId']),'i',array('count'));
        if(isset($countInfo[0])) {
          if($countInfo[0]['count'] < $currentTarget[0]['qtyNeeded']) {
            $completed = $countInfo[0]['count'];
            $notCompleted = $currentTarget[0]['qtyNeeded'] - $countInfo[0]['count'];
          } else {
            $completed = 100;
            $notCompleted = 0;
          }
        }
      }

      echo '{
        "data": ['.$completed.', '.$notCompleted.'],
        "dueDate": "' . date('M. jS',strtotime($currentTarget[0]['dueDate'])).', ' . $notCompleted . ' units still needed"
      }';
    break;
    //---------------------------------------------------------
    case 'getYearlyCompletion':
      $yearly = '';
      $yearlyFreq = '';

      $results = $common['db']->pec('SELECT test_id, date_time FROM 2019_prod_7680_printer_results WHERE date_time > ? AND additional_notes IS NOT NULL',array(date('Y-01-01 00:00:00')),'s',array('test_id', 'date_time'));
      $productionDates = array();
    
      foreach($results as $row) {
        $dayOfYear = date('z',strtotime($row['date_time']));

        if(isset($productionDates[$dayOfYear])) {
          $productionDates[$dayOfYear]++;
        } else {
          $productionDates[$dayOfYear]=1;
        }
      }

      $runningTotal = 0;
      for($i = 0; $i <= date('z'); $i++) {
        if (isset($productionDates[$i])) {
          $runningTotal += $productionDates[$i];
          $yearlyFreq .= ($yearlyFreq==''?'':',') . '{"x": ' . $i . ', "y": ' . $productionDates[$i] . '}';
        } else {
          $yearlyFreq .= ($yearlyFreq==''?'':',') . '{"x": ' . $i . ', "y": ' . 0 . '}';
        }
        $yearly .= ($yearly==''?'':',') . '{"x": ' . $i . ', "y": ' . $runningTotal . '}';
      }

      echo '{
        "data": ['.$yearly.'],
        "dataFreq": ['.$yearlyFreq.']
      }';
    break;
    //---------------------------------------------------------
    case 'getOrderByDay':
      $dataset = '';
      $labels = '';

      $productionInfo = $common['db']->pec('SELECT startDate, dueDate FROM 2019_prod_7680_target_dates WHERE startDate < NOW() && dueDate > NOW() LIMIT 1', array(),'',array('startDate', 'dueDate'));
      if(isset($productionInfo[0])) {
        $results = $common['db']->pec('SELECT test_id, date_time FROM 2019_prod_7680_printer_results WHERE date_time > ? AND date_time < ? AND additional_notes IS NOT NULL',array($productionInfo[0]['startDate'], $productionInfo[0]['dueDate']),'ss',array('test_id', 'date_time'));
        $qtyByDate = array();
        foreach($results as $row) {
          $date = date('n/d',strtotime($row['date_time']));
          if (!isset($qtyByDate[$date])) {
            $qtyByDate[$date] = 1;
          } else {
            $qtyByDate[$date]++;
          }
        }

        $selectedDate = strtotime($productionInfo[0]['startDate']);
        $endDate = strtotime($productionInfo[0]['dueDate']);
        while ($selectedDate < $endDate) {
          if(date('N', $selectedDate) != 6 && date('N', $selectedDate) != 7) {
            $label = date('n/d', $selectedDate);
            $dataset .= (empty($dataset)?'':',') . (isset($qtyByDate[$label])?$qtyByDate[$label]:0);
            $labels .= (empty($labels)?'':',') . '"' . $label . '"';
          }
          $selectedDate += (24 * 60 * 60);
        }
      }

      echo '{
        "data": ['.$dataset.'],
        "labels": ['.$labels.']
      }';
    break;
    //---------------------------------------------------------
    case 'getOnTrack':
      $onTrackPercentage = 0;

      $productionInfo = $common['db']->pec('SELECT startDate, dueDate, qtyNeeded, firstUsableId FROM 2019_prod_7680_target_dates WHERE startDate < NOW() && dueDate > NOW() LIMIT 1', array(),'',array('startDate', 'dueDate', 'qtyNeeded', 'firstUsableId'));
      if(isset($productionInfo[0])) {
        // Determine how many working days there are
        $selectedDate = strtotime($productionInfo[0]['startDate']);
        $endDate = strtotime($productionInfo[0]['dueDate']);
        $curDateDB = date('Y-m-d 00:00:00', strtotime("now"));
        $curDate = strtotime($curDateDB); // Ensures it is the start of today
        $workingDays = 0;
        $workingDaysConsumed = 0;
        while ($selectedDate < $endDate) {
          if(date('N', $selectedDate) != 6 && date('N', $selectedDate) != 7) {
            $workingDays++;
            if($selectedDate < $curDate) {
              $workingDaysConsumed++;
            }
          }
          $selectedDate += (24 * 60 * 60);
        }

        $quantityShouldHaveProduced = $workingDaysConsumed * ($productionInfo[0]['qtyNeeded'] / $workingDays);
        $countInfo = $common['db']->pec('SELECT count(*) FROM 2019_prod_7680_printer_results WHERE test_id >= ? AND date_time < ? AND additional_notes IS NOT NULL',array($productionInfo[0]['firstUsableId'], $curDateDB),'is',array('count'));
        $quantityProduced = $countInfo[0]['count'];

        if($quantityShouldHaveProduced > 0) {
          $onTrackPercentage = $quantityProduced * 50 / $quantityShouldHaveProduced;
        }
      }

      echo '{
        "onTrackPercentage": ' . $onTrackPercentage . '
      }';
    break;
    //---------------------------------------------------------
    case 'getMessage':
      $message = '';

      $messageInfo = $common['db']->pec('SELECT message FROM 7011_messages WHERE messageId=1 LIMIT 1',array(),'',array('message'));
      $message = $messageInfo[0]['message'];

      echo '{
        "message": ' . preprocessForJSON($message) . '
      }';
    break;
  }
}
?>
