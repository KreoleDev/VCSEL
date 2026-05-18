<?php
//Developer:    Charles Palmer
//Created:      2022.10.13
//Revision:     2022.10.14

/*
*   
*/

/*
[0]     View
*/

require_once('common/includes/std_lib.inc.php');

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  $results = $common['db']->pec(
    'SELECT productionId, generatedDateTime, testerName, tla, primarySerialNum, secondarySerialNum, tertiarySerialNum, packed, packedDateTime, prod_v2_products.title, prod_v2_test_sets.title 
    FROM prod_v2_production_tracking, prod_v2_products, prod_v2_test_sets 
    WHERE prod_v2_production_tracking.extProductId=productId AND extTestSetId=testSetId AND passedAllTests=1 AND date(generatedDateTime) BETWEEN date_sub(date(?), INTERVAL 90 DAY) AND date(?) ORDER BY generatedDateTime DESC', 
    [$_GET['date'] ?? date('Y-m-d'), $_GET['date'] ?? date('Y-m-d')], 
    'ss', 
    ['productionId', 'generatedDateTime', 'testerName', 'tla', 'primarySerialNum', 'secondarySerialNum', 'tertiarySerialNum', 'packed', 'packedDateTime', 'productTitle', 'testSetTitle']
  );

  $headings = ['Date/Time', 'Product', 'Test', 'TLA', 'Serial Numbers', 'Tester', 'Packed', ''];
  $headingsClasses = ['','','','','','','',''];
  echo $common['table']->begin($headings,'full_table_desc',$headingsClasses);
    foreach($results as $row) {
      echo $common['table']->add_row(
        [$row['generatedDateTime'], $row['productTitle'], $row['testSetTitle'], $row['tla'], ($row['primarySerialNum'] ? 'Primary: ' . $row['primarySerialNum'] . '<br/>' : '') . ($row['secondarySerialNum'] ? 'Secondary: ' . $row['secondarySerialNum'] . '<br/>' : '') . ($row['tertiarySerialNum'] ? 'Tertiary: ' . $row['tertiarySerialNum'] : '') , $row['testerName'], ($row['packed'] ? $row['packedDateTime'] : ''), '<a href="details.php?productionId=' . $row['productionId'] . '">Details</a>'],
        ['','','','','','','','center']
      );
    }
  echo $common['table']->end();

  require('common/includes/js.inc.php');
}
?>