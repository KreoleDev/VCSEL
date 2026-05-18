<?php
//Developer:    Charles Palmer
//Created:      2022.10.31
//Revision:     2022.10.31

/*
*   
*/

/*
[0]     View
*/

require_once('common/includes/std_lib.inc.php');

$page_title='7680 Palletized';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  require_once('common/includes/header_inner.inc.php');
    echo '<h2>'.$page_title.'</h2>';
    echo $common['window']->begin($page_title);
      echo $common['table']->begin(['Start Date/Time', 'QTY', '', ''],'full_table',['','','no_sort','no_sort']);

      $results = $common['db']->pec('SELECT orderId, generatedDateTime, targetQty, currentQty FROM prod_v2_7680_palletize_orders ORDER BY generatedDateTime DESC', [], '', ['orderId', 'generatedDateTime', 'targetQty', 'currentQty']);
      foreach($results as $row) {
        $cols = [$row['generatedDateTime'], $row['currentQty'] . ' / ' . $row['targetQty'], '<a href="download_csv.php?orderId='.$row['orderId'].'" title="Download CSV">Download CSV</a>', '<a href="view_report.php?orderId='.$row['orderId'].'" title="View Report">View Report</a>'];
        $col_classes=['','right','no_sort center','no_sort center'];
        echo $common['table']->add_row($cols,$col_classes);
      }

      echo $common['table']->end();
    echo $common['window']->end();
  require_once('common/includes/footer_inner.inc.php');
}
?>