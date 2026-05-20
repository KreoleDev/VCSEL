<?php
//Developer:    Charles Palmer
//Created:      2020.10.06
//Revision:     2020.10.21

/*
*   2020.10.15  CP  Modified finalized pallet to better target order id (due to some production results not being finalized)
*   2020.10.16  CP  Changed palletId displayed in shipping to be the friendly label that will be used  
*   2020.10.21  CP  Corrected bug where closing up the first pallet of shipments after the first were not being found properly 
*/

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require_once(dirname(__FILE__) .'/../lib/includes/includes.php');

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$postdata = file_get_contents("php://input");
if (isset($postdata)) {
  $request = json_decode($postdata);
  if(isset($request->mode)) {
    switch($request->mode){
      //------------------------------------------------------------------------------------
      case 'getCurrentOnPallet':
        $items = '';
        $palletId = 0;

        // Determine which is current pallet
        $palletInfo = $common['db']->pec('SELECT palletId FROM 2019_prod_pallets ORDER BY palletId DESC LIMIT 1',array(),'',array('palletId'));
        if (isset($palletInfo[0]['palletId'])) {
          $palletId = $palletInfo[0]['palletId'];

          $results = $common['db']->pec('SELECT extPrinterSerialNum, extVaultSerialNum, dateTime FROM 2019_prod_pallet_items WHERE extPalletId=?',array($palletId),'i',array('extPrinterSerialNum', 'extVaultSerialNum', 'dateTime'));
          foreach($results as $row) {
            $items .= (empty($items)?'':',') . '
              {
                "main": "'.$row['extPrinterSerialNum'].'",
                "vault": "'.$row['extVaultSerialNum'].'"
              }
            ';
          }
        }

        // Determine order pallet id number
        //Get newest order id in db and find count of that order; if count is full, start new id set at 1. other wise it will be +1 of the last pallet
        $currentInfo = $common['db']->pec('SELECT extTargetId FROM 2019_prod_pallets ORDER BY extTargetId DESC LIMIT 1',array(),'',array('extTargetId'));
        $currentInfoCount = $common['db']->pec('SELECT count(*) FROM 2019_prod_pallet_items WHERE extPalletId IN(SELECT palletId FROM 2019_prod_pallets WHERE extTargetId=?)',array($currentInfo[0]['extTargetId']),'i',array('count'));
        $currentOrderTotal = $common['db']->pec('SELECT qtyNeeded FROM 2019_prod_7680_target_dates WHERE targetId=? LIMIT 1',array($currentInfo[0]['extTargetId']),'i',array('qtyNeeded'));
        if ($currentInfoCount[0]['count'] < $currentOrderTotal[0]['qtyNeeded']) {
          $lastPallet = $common['db']->pec('SELECT friendlyLabel FROM 2019_prod_pallets WHERE extTargetId=? ORDER BY friendlyLabel DESC LIMIT 1',array($currentInfo[0]['extTargetId']),'i',array('friendlyLabel'));
          $palletId = $lastPallet[0]['friendlyLabel'] + 1;
        } else {
          $palletId = 1;
        }
        echo '{
          "palletId": ' . $palletId . ',
          "items":[' . $items . ']
        }';
      break;
      //------------------------------------------------------------------------------------
      case 'startNewPallet':
        $common['db']->pec('INSERT INTO 2019_prod_pallets SET startDateTime = NOW()');
        // Note!!! this case falls into next case
      //break;
      //------------------------------------------------------------------------------------
      case 'finalizePallet':
        // Finalize Old Pallets
        $results = $common['db']->pec('SELECT palletId FROM 2019_prod_pallets WHERE extTargetId=0 ORDER BY startDateTime',array(),'',array('palletId'));
        foreach($results as $row) {
          // Get all identified on pallet
          $results2 = $common['db']->pec('SELECT extPrinterSerialNum, extVaultSerialNum FROM 2019_prod_pallet_items WHERE extPalletId=?',array($row['palletId']),'i',array('extPrinterSerialNum', 'extVaultSerialNum'));
          if (!empty($results2)) {
            // Determine which order this belongs to with first entry
            $testInfo = $common['db']->pec('SELECT test_id FROM 2019_prod_7680_printer_results WHERE printer_serial_num=? AND vault_serial_num=? ORDER BY test_id DESC LIMIT 1',array($results2[0]['extPrinterSerialNum'], $results2[0]['extVaultSerialNum']),'ss',array('test_id'));
            if (!empty($testInfo)) {
              // Determine which order target date this matches up with
              //$targetInfo = $common['db']->pec('SELECT targetId, firstUsableId, qtyNeeded FROM 2019_prod_7680_target_dates WHERE ? >= firstUsableId AND ? < (firstUsableId+qtyNeeded) LIMIT 1',array($testInfo[0]['test_id'],$testInfo[0]['test_id']),'ii',array('targetId', 'firstUsableId', 'qtyNeeded'));
              $processedInfo = $common['db']->pec('SELECT count(*) FROM 2019_prod_pallets, 2019_prod_pallet_items WHERE palletId=extPalletId AND extTargetId IN(SELECT extTargetId FROM 2019_prod_pallets ORDER BY extTargetId DESC LIMIT 1)',array(),'',array('count'));
              // Determine if processedInfo was collected for last shipment that is complete
              $lastShipmentInfo = $common['db']->pec('SELECT qtyNeeded FROM 2019_prod_7680_target_dates WHERE targetId IN(SELECT extTargetId FROM 2019_prod_pallets ORDER BY extTargetId DESC LIMIT 1)', array(), '', array('qtyNeeded'));
              if(isset($lastShipmentInfo[0]['qtyNeeded']) && $lastShipmentInfo[0]['qtyNeeded'] <= $processedInfo[0]['count']) {
                $qtyProcessed = 0;
              } else {
                $qtyProcessed = $processedInfo[0]['count'];
              }
              $targetInfo = $common['db']->pec('SELECT targetId, firstUsableId, qtyNeeded FROM 2019_prod_7680_target_dates WHERE ? >= firstUsableId AND qtyNeeded > ? ORDER BY startDate LIMIT 1',array($testInfo[0]['test_id'],$qtyProcessed),'ii',array('targetId', 'firstUsableId', 'qtyNeeded'));
              if(!empty($targetInfo)) {
                $targetId = $targetInfo[0]['targetId'];

                // Determine next friendly label
                $lastFriendlyLabel = $common['db']->pec('SELECT friendlyLabel FROM 2019_prod_pallets WHERE extTargetId=? ORDER BY friendlyLabel DESC LIMIT 1',array($targetId),'i',array('friendlyLabel'));
                if(isset($lastFriendlyLabel[0]['friendlyLabel'])) {
                  $friendlyLabel = $lastFriendlyLabel[0]['friendlyLabel'] + 1;
                } else {
                  $friendlyLabel = 1;
                }

                // Update entry
                $common['db']->pec('UPDATE 2019_prod_pallets SET extTargetId=?, friendlyLabel=? WHERE palletId=? LIMIT 1',array($targetId, $friendlyLabel, $row['palletId']),'iii');

                //Find all serial numbers that match that order
                /*$serialResults = $common['db']->pec('SELECT test_id, printer_serial_num, vault_serial_num FROM 2019_prod_7680_printer_results WHERE test_id >= ? AND test_id < ?',array($targetInfo[0]['firstUsableId'], ($targetInfo[0]['firstUsableId']+$targetInfo[0]['qtyNeeded'])),'ii',array('test_id', 'printer_serial_num', 'vault_serial_num'));
                $printerSerialNumbers = array();
                foreach($serialResults as $serialRow) {
                  $printerSerialNumbers[$serialRow['printer_serial_num']] = array();
                  $printerSerialNumbers[$serialRow['printer_serial_num']]['vault'] = $serialRow['vault_serial_num'];
                  $printerSerialNumbers[$serialRow['printer_serial_num']]['testId'] = $serialRow['test_id'];
                }

                $lowestTestId = 999999999999;
                $highestTestId = 0;

                foreach($results2 as $palletRow) {
                  if(isset($printerSerialNumbers[$palletRow['extPrinterSerialNum']]) && $printerSerialNumbers[$palletRow['extPrinterSerialNum']]['vault'] == $palletRow['extVaultSerialNum']) {
                    if($lowestTestId > $printerSerialNumbers[$palletRow['extPrinterSerialNum']]['testId']) {
                      $lowestTestId = $printerSerialNumbers[$palletRow['extPrinterSerialNum']]['testId'];
                    }
                    if($highestTestId < $printerSerialNumbers[$palletRow['extPrinterSerialNum']]['testId']) {
                      $highestTestId = $printerSerialNumbers[$palletRow['extPrinterSerialNum']]['testId'];
                    }
                  }
                }

                echo $lowestTestId . '-' . $highestTestId . '<br/>';

                $friendlyLabel = (($lowestTestId - $targetInfo[0]['firstUsableId']) / 24) + 1;
                echo $friendlyLabel . '<br/>';*/
              }
            }
            

          }
          

          //foreach($results2 as $row2) {

          //}
        }
      break;
      //------------------------------------------------------------------------------------
      case 'processScan':
        $passed = true;
        $failMsg = '';

        if (empty($request->mainBarcode) || empty($request->vaultBarcode)) {
          $passed = false;
          $failMsg = 'Bad barcode scan';
        }

        // Check if any part of it has shipped before
        $results = $common['db']->pec('SELECT test_id, printer_serial_num, vault_serial_num FROM 2019_prod_7680_printer_results WHERE (printer_serial_num=? OR vault_serial_num=?) AND passed_shipping=1',array($request->mainBarcode, $request->vaultBarcode),'ss',array('test_id', 'printer_serial_num', 'vault_serial_num'));
        foreach($results as $row) {
          $passed = false;
          if ($row['printer_serial_num'] == $request->mainBarcode && $row['vault_serial_num'] == $request->vaultBarcode) {
            $failMsg = 'Both printer and vault serial numbers have already shipped. ('.$request->mainBarcode.'/'.$request->vaultBarcode.')';
          } else if ($row['printer_serial_num'] == $request->mainBarcode){
            $failMsg = 'Printer serial number has already shipped. ('.$request->mainBarcode.')';
          } else if ($row['vault_serial_num'] == $request->vaultBarcode){
            $failMsg = 'Vault serial number has already shipped. ('.$request->vaultBarcode.')';
          }
        }

        if ($passed) {
          // Ensure there is a unit that matches in db that hasn't shipped
          $results = $common['db']->pec('SELECT passed_shipping FROM 2019_prod_7680_printer_results WHERE printer_serial_num=? AND vault_serial_num=?',array($request->mainBarcode, $request->vaultBarcode),'ss',array('passed_shipping'));
          
          if(empty($results)) {
            $passed = false;
            $failMsg = 'This unit has NOT been through final test!';
          }

          foreach($results as $row) {
            if ($row['passed_shipping']) {
              $passed = false;
              $failMsg = 'Already shipped!';
            }
          }
        }

        // Get current pallet id
        $palletInfo = $common['db']->pec('SELECT palletId FROM 2019_prod_pallets ORDER BY palletId DESC LIMIT 1',array(),'',array('palletId'));
        if (isset($palletInfo[0]['palletId'])) {
          $palletId = $palletInfo[0]['palletId'];
          if ($passed) {
            $success=true;
            $common['db']->start_transaction();

            // Mark shipped in primary production db
            $affected=$common['db']->pec('UPDATE 2019_prod_7680_printer_results SET passed_shipping=1, passed_shipping_date_time=NOW() WHERE printer_serial_num=? AND vault_serial_num=?',array($request->mainBarcode, $request->vaultBarcode),'ss');
            if(!$affected){ $success=false; }
            
            // Add entry to pallet items db
            $affected=$common['db']->pec('INSERT INTO 2019_prod_pallet_items SET extPalletId=?, extPrinterSerialNum=?, extVaultSerialNum=?, dateTime=NOW(), testerName=?',array($palletId, $request->mainBarcode, $request->vaultBarcode, $request->testerName),'isss');
            if(!$affected){ $success=false; }
            
            $passed = $common['db']->end_transaction($success);
            if (!$passed) {
              $failMsg = 'Database error';
            }
          }
        } else {
          $passed = false;
          $failMsg = 'No pallet set';
        }

        echo '{
          "passed": ' . ($passed?'true':'false') . ',
          "failMsg": "' . $failMsg . '"
        }';
      break;
      //------------------------------------------------------------------------------------
    }
  } else {
    //Handle other data passing
    if(isset($_POST['mode'])){
      switch($_POST['mode']){
        //===============================================

        //===============================================
      }
    }
  }
}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
?>