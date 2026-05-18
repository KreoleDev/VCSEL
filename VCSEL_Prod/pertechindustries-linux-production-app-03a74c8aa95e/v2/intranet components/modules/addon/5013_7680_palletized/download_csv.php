<?php
//Developer:    Charles Palmer
//Created:      2022.10.31
//Revision:     2022.10.31

/*
*   
*/

require_once(dirname(__FILE__) . '/../../../common/includes/std_lib.inc.php');

/*
[0]     Full Control
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  $reportInfo = $common['db']->pec('SELECT generatedDateTime FROM prod_v2_7680_palletize_orders WHERE orderId=? LIMIT 1',array($_REQUEST['orderId']),'i',array('generatedDateTime'));

  header("Content-Type: text/csv");
  header("Content-disposition: attachment; filename=" . date('F-j-Y', strtotime($reportInfo[0]['generatedDateTime'])) . ".csv");
  header("Pragma: no-cache");
  header("Expires: 0");

  echo "Pallet,Printer Serial Number,Vault Serial Number\n";

  // Get all pallets associated with this order
  $results = $common['db']->pec('SELECT palletId, friendlyName FROM prod_v2_7680_palletize_order_pallets WHERE extOrderId=? ORDER BY friendlyName',array($_REQUEST['orderId']),'i',array('palletId', 'friendlyName'));
  foreach($results as $palletInfo) {
    $subResults = $common['db']->pec('SELECT primarySerialNum, secondarySerialNum FROM prod_v2_7680_palletize_order_pallet_items WHERE extPalletId=? ORDER BY primarySerialNum',array($palletInfo['palletId']),'i',array('primarySerialNum', 'secondarySerialNum'));
    foreach($subResults as $subRow) {
      echo $palletInfo['friendlyName'] . "," . $subRow['primarySerialNum'] . "," . $subRow['secondarySerialNum'] . "\n";
    }
  }
}

die;
?>