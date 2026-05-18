<?php
//Developer:    Charles Palmer
//Created:      2019.09.16
//Revision:     2019.09.16

/*
*   
*/

/*
[0]     View
[1]     Configure
*/

require_once('common/includes/std_lib.inc.php');

$page_title='7680 TLAs';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';

        echo $common['window']->begin('TLAs');
            if($common['security']->check_rights(1)){
                echo '<p><a class="button" href="forms/tlas.frm.php" title="Add TLA">Add TLA</a></p>';
            }
            
            $headings=array('TLA Number','Last Revised','Active');
            $heading_classes=array('','','center');

            if($common['security']->check_rights(1)){ //Edit
                $headings[]='&nbsp;';
                $heading_classes[]='no_sort';
            }

            echo $common['table']->begin($headings,'full_table',$heading_classes);

            //Get all related 7680 TLA info but only keep latest revisions
            $results = $common['db']->pec('SELECT ext_tla_id, rev_timestamp FROM 2019_prod_tla_info_product_4 ORDER BY rev_timestamp ASC',
                array(),'',array('ext_tla_id', 'rev_timestamp'));
            $tlaCfg = array();
            foreach($results as $row) {
                $tlaCfg[$row['ext_tla_id']]['rev_timestamp'] = $row['rev_timestamp'];
            }

            $results = $common['db']->pec('SELECT tla_id, tla_number, active FROM 2019_prod_tlas WHERE ext_product_id=4',array(),'',array('tla_id', 'tla_number', 'active'));
            foreach($results as $row) {
                if(isset($tlaCfg[$row['tla_id']])) {
                    $cols=array(
                        $row['tla_number'],
                        $tlaCfg[$row['tla_id']]['rev_timestamp'],
                        $row['active']?'Yes':'No');
                    $col_classes=array('','','center');
                } else {
                    $cols=array($row['tla_number'],'',$row['active']?'Yes':'No');
                    $col_classes=array('','','center');
                }

                if($common['security']->check_rights(1)){ //Edit
                    $cols[]='<a href="forms/tlas.frm.php?mode=edit&amp;tla_id='.$row['tla_id'].'" title="Edit">Edit</a>';
                    $col_classes[]='no_sort center';
                }

                echo $common['table']->add_row($cols,$col_classes);
            }

            echo $common['table']->end();

        echo $common['window']->end();
    require_once('common/includes/footer_inner.inc.php');
}
?>