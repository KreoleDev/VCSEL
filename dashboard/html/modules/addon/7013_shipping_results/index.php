<?php
//Developer:    Charles Palmer
//Created:      2020.10.08
//Revision:     2020.10.08
require_once('common/includes/std_lib.inc.php');

/*
*   
*/

/*
[0]     View
*/

$page_title='Shipping Results';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';
        
        echo $common['window']->begin('Shipping Results');

            $headings=array('Start Date','Due Date','QTY','');
            $heading_classes=array('','','','');
            echo $common['table']->begin($headings,'full_table',$heading_classes);

            $results = $common['db']->pec('SELECT extTargetId FROM 2019_prod_pallet_items, 2019_prod_pallets WHERE extPalletId=palletId',array(),'',array('extTargetId'));
            $finishedQty = array();
            foreach($results as $row) {
              if(!isset($finishedQty[$row['extTargetId']])) {
                $finishedQty[$row['extTargetId']]=1;
              } else {
                $finishedQty[$row['extTargetId']]++;
              }
            }

            $results=$common['db']->pec('SELECT targetId, qtyNeeded, startDate, dueDate FROM 2019_prod_7680_target_dates',array(),'',array('targetId', 'qtyNeeded', 'startDate', 'dueDate'));
            foreach($results as $row) {
                $cols=array(date('Y-m-d',strtotime($row['startDate'])),date('Y-m-d',strtotime($row['dueDate'])),(isset($finishedQty[$row['targetId']])?$finishedQty[$row['targetId']]:'0') . '/' . $row['qtyNeeded'],'<a href="report.php?targetId=' . $row['targetId'] . '">View Report</a>');
                $col_classes=array('','','right','center');
                echo $common['table']->add_row($cols,$col_classes);
            }

            echo $common['table']->end();
        echo $common['window']->end();
        
    require_once('common/includes/footer_inner.inc.php');
}
?>