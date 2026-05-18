<?php
// Developer:  Charles Palmer
// Created:    2022.10.31
// Revision:   2022.10.31

/*
*  
*/

class palletize7680 {
  // ======================================================================================== //
  public function getStep ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $currentStep = '';
    $currentId = 0;

    $results = $db->pec("SELECT orderId FROM prod_v2_7680_palletize_orders WHERE currentQty < targetQty ORDER BY generatedDateTime DESC LIMIT 1", [], '', ['orderId']);
    if (count($results) == 0) {
      $currentStep = 'newOrder';
      $success = true;
    } else {
      $orderId = $results[0]['orderId'];
      $results = $db->pec("SELECT palletId FROM prod_v2_7680_palletize_order_pallets WHERE extOrderId = ? AND currentQty < targetQty ORDER BY generatedDateTime DESC LIMIT 1", [$orderId], 'i', ['palletId']);
      if (count($results) == 0) {
        $currentStep = 'newPallet';
        $success = true;
        $currentId = $orderId;
      } else {
        $currentStep = 'newItem';
        $success = true;
        $currentId = $results[0]['palletId'];
      }
    }

    return (object) [
      'success' => $success,
      'currentStep' => $currentStep,
      'currentId' => $currentId
    ];
  }
  // ======================================================================================== //
  public function createOrder ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;

    if (isset($params->targetQty) && isset($params->testerName) && isset($params->macAddress)) {
      $targetQty = $params->targetQty;
      $testerName = $params->testerName;
      $macAddress = $params->macAddress;
      $results = $db->pec("INSERT INTO prod_v2_7680_palletize_orders SET generatedDateTime=NOW(), targetQty=?, testerName=?, macAddress=?, currentQty=0", [$targetQty, $testerName, $macAddress], 'iss');
      if ($results) {
        $success = true;
      }
    }

    return (object) [
      'success' => $success
    ];
  }
  // ======================================================================================== //
  public function createPallet ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $errorMsg = 'Could not create pallet';

    if (isset($params->orderId) && isset($params->targetQty) && isset($params->testerName) && isset($params->macAddress)) {
      $orderId = $params->orderId;
      $targetQty = $params->targetQty;
      $testerName = $params->testerName;
      $macAddress = $params->macAddress;

      // Check if order qty needed is less or equal to the target qty
      $results = $db->pec("SELECT targetQty, currentQty FROM prod_v2_7680_palletize_orders WHERE orderId = ? LIMIT 1", [$orderId], 'i', ['targetQty', 'currentQty']);
      if($results[0]['currentQty'] + $targetQty <= $results[0]['targetQty']) {
        // Get next friendlyName for pallet
        $results = $db->pec("SELECT friendlyName FROM prod_v2_7680_palletize_order_pallets WHERE extOrderId = ? ORDER BY generatedDateTime DESC LIMIT 1", [$orderId], 'i', ['friendlyName']);
        if (count($results) == 0) {
          $friendlyName = 1;
        } else {
          $friendlyName = ++$results[0]['friendlyName'];
        }
        
        $results = $db->pec("INSERT INTO prod_v2_7680_palletize_order_pallets SET generatedDateTime=NOW(), extOrderId=?, targetQty=?, testerName=?, macAddress=?, currentQty=0, friendlyName=?", [$orderId, $targetQty, $testerName, $macAddress, $friendlyName], 'iissi');
        if ($results) {
          $success = true;
        }
      } else {
        $errorMsg = 'Pallet quantity will cause order to exceed target quantity';
      }
    }

    return (object) [
      'success' => $success,
      'errorMsg' => $errorMsg
    ];
  }
  // ======================================================================================== //
  public function addItem ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $errorMsg = 'Could not add unit to pallet';

    if (isset($params->palletId) && isset($params->testerName) && isset($params->macAddress) && isset($params->primarySerialNum) && isset($params->secondarySerialNum)) {
      $palletId = $params->palletId;
      $testerName = $params->testerName;
      $macAddress = $params->macAddress;
      $primarySerialNum = $params->primarySerialNum;
      $secondarySerialNum = $params->secondarySerialNum;

      // Check if unit has been marked packed and has not been palletized
      $results = $db->pec(
        "SELECT productionId FROM prod_v2_production_tracking WHERE packed=1 AND primarySerialNum = ? AND secondarySerialNum = ? LIMIT 1", 
        [$primarySerialNum, $secondarySerialNum], 'ss', ['productionId']
      );
      if (empty($results)) {
        $errorMsg = 'Unit has not been marked packed';
      } else {
        $results = $db->pec(
          'SELECT itemId FROM prod_v2_7680_palletize_order_pallet_items WHERE primarySerialNum = ? AND secondarySerialNum = ? LIMIT 1', 
          [$primarySerialNum, $secondarySerialNum], 'ss', ['itemId']
        );
        if (!empty($results)) {
          $errorMsg = 'Unit has already been palletized';
        } else {
          // Determine how many will be on pallet
          $palletInfo = $db->pec('SELECT targetQty, extOrderId FROM prod_v2_7680_palletize_order_pallets WHERE palletId = ? LIMIT 1', [$palletId], 'i', ['targetQty', 'extOrderId']);

          // Get what is currently on pallet to determine if item fits within range of serial number valid for pallet
          $results = $db->pec(
            'SELECT primarySerialNum FROM prod_v2_7680_palletize_order_pallet_items WHERE extPalletId = ? ORDER BY primarySerialNum ASC', 
            [$palletId], 'i', ['primarySerialNum']
          );
          if (count($results) == 0 || 
            ((intval($primarySerialNum) >= intval($results[count($results) - 1]['primarySerialNum']) - $palletInfo[0]['targetQty'] + 1) ||
            (intval($primarySerialNum) <= intval($results[0]['primarySerialNum']) + $palletInfo[0]['targetQty'] - 1))
          ) {
            $success = $db->pec(
              'INSERT INTO prod_v2_7680_palletize_order_pallet_items SET generatedDateTime=NOW(), extPalletId=?, testerName=?, macAddress=?, primarySerialNum=?, secondarySerialNum=?', 
              [$palletId, $testerName, $macAddress, $primarySerialNum, $secondarySerialNum], 
              'issss'
            );
            $db->pec('UPDATE prod_v2_7680_palletize_order_pallets SET currentQty = currentQty + 1 WHERE palletId = ?', [$palletId], 'i');
            $db->pec('UPDATE prod_v2_7680_palletize_orders SET currentQty = currentQty + 1 WHERE orderId = ?', [$palletInfo[0]['extOrderId']], 'i');
          } else {
            $errorMsg = 'Unit does not fit within range of serial numbers valid for pallet';
          }
        }
      }
    }

    return (object) [
      'success' => $success,
      'errorMsg' => $errorMsg
    ];
  }
  // ======================================================================================== //
  public function progressReport ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $items = [];
    $palletTargetQty = 0;
    $palletCurrentQty = 0;
    $orderTargetQty = 0;
    $orderCurrentQty = 0;

    if (isset($params->palletId)) {
      $items = $db->pec('SELECT primarySerialNum, secondarySerialNum FROM prod_v2_7680_palletize_order_pallet_items WHERE extPalletId = ? ORDER BY primarySerialNum', [$params->palletId], 'i', ['primarySerialNum', 'secondarySerialNum']);
      $success = true;

      // Get pallet fill info
      $palletInfo = $db->pec('SELECT targetQty, currentQty, extOrderId FROM prod_v2_7680_palletize_order_pallets WHERE palletId = ? LIMIT 1', [$params->palletId], 'i', ['targetQty', 'currentQty', 'extOrderId']);
      $palletTargetQty = $palletInfo[0]['targetQty'];
      $palletCurrentQty = $palletInfo[0]['currentQty'];

      // Get order fill info
      $results = $db->pec('SELECT targetQty, currentQty FROM prod_v2_7680_palletize_orders WHERE orderId = ? LIMIT 1', [$palletInfo[0]['extOrderId']], 'i', ['targetQty', 'currentQty']);
      $orderTargetQty = $results[0]['targetQty'];
      $orderCurrentQty = $results[0]['currentQty'];
    }

    return (object) [
      'success' => $success,
      'items' => $items,
      'palletTargetQty' => $palletTargetQty,
      'palletCurrentQty' => $palletCurrentQty,
      'orderTargetQty' => $orderTargetQty,
      'orderCurrentQty' => $orderCurrentQty
    ];
  }
  // ======================================================================================== //
}
?>