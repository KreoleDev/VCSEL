<?php
//Developer:    Charles Palmer
//Created:      2020.10.01
//Revision:     2020.10.02

/*
*    2020.10.02   CP  Added Start date
*/

/*
[0]     Full Control
*/

require_once('common/includes/std_lib.inc.php');

$page_title='7680 Schedule';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';

        echo $common['window']->begin('Schedule');
            echo '<p><a class="button" href="forms/orders.frm.php" title="Add Order">Add Order</a></p>';
            
            $headings=array('Start Date','Due Date','QTY','');
            $heading_classes=array('','','','center');

            echo $common['table']->begin($headings,'full_table',$heading_classes);

            //Get all related 7680 orders
            $results = $common['db']->pec('SELECT targetId, firstUsableId, qtyNeeded, startDate, dueDate FROM 2019_prod_7680_target_dates ORDER BY startDate, dueDate ASC',
                array(),'',array('targetId', 'firstUsableId', 'qtyNeeded', 'startDate', 'dueDate'));
            foreach($results as $row) {
                $cols=array(
                  date('Y-m-d',strtotime($row['startDate'])),
                  date('Y-m-d',strtotime($row['dueDate'])),
                  $row['qtyNeeded']);
                $col_classes=array('','');

                $cols[]='<a href="forms/orders.frm.php?mode=edit&amp;targetId='.$row['targetId'].'" title="Edit">Edit</a>';
                $col_classes[]='no_sort center';

                echo $common['table']->add_row($cols,$col_classes);
            }

            echo $common['table']->end();

        echo $common['window']->end();
    require_once('common/includes/footer_inner.inc.php');
}
?>