<?php
//Developer:    Charles Palmer
//Created:      2019.06.11
//Revision:     2019.12.05

/*
*   2019.12.05  CP  Added in linking to scans, shipped date/time, and notes
*/

require_once('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Edit
*/

$page_title='7680 Results';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';
        
        echo $common['window']->begin('Test Results');

            $headings=array('Date/Time','Tester','Printer Serial','Vault Serial','Scans','Palletized','Notes');
            $heading_classes=array('','','','','','','','','');
            if($common['security']->check_rights(1)){ //Edit
              $headings[]='&nbsp;';
              $heading_classes[]='no_sort';
            }
            echo $common['table']->begin($headings,'full_table_desc',$heading_classes);
                $results = $common['db']->pec('SELECT test_id, tester_name, date_time, printer_serial_num, vault_serial_num, additional_notes, passed_shipping, passed_shipping_date_time FROM 2019_prod_7680_printer_results WHERE 1',array(),'',array('test_id', 'tester_name', 'date_time', 'printer_serial_num', 'vault_serial_num', 'additional_notes', 'passed_shipping', 'passed_shipping_date_time'));
                foreach($results as $row) {
                    if(file_exists(CFG_CMS_INCLUDE_PATH . 'production/uploads/7680printer/'.$row['test_id'].'vault.jpg')) {
                        $scans='<a href="../../../production/uploads/7680printer/'.$row['test_id'].'.jpg" target="_blank">Printer</a><a href="../../../production/uploads/7680printer/'.$row['test_id'].'vault.jpg" target="_blank">Vault</a>';
                    } else {
                        $scans='';
                    }
                    $cols=array($row['date_time'],$row['tester_name'],$row['printer_serial_num'],$row['vault_serial_num'],$scans,($row['passed_shipping']?$row['passed_shipping_date_time']:''),$row['additional_notes']);
                    $col_classes=array('','','','','','','');
                    if($common['security']->check_rights(1)){ //Edit
                      $cols[]='<a href="forms/tests.frm.php?mode=edit&amp;test_id=' . $row['test_id'] . '" title="Edit ' . $row['printer_serial_num'] . '">Edit</a>';
                      $col_classes[]='center';
                    }
                    echo $common['table']->add_row($cols,$col_classes);
                }
            echo $common['table']->end();

        echo $common['window']->end();
        
    require_once('common/includes/footer_inner.inc.php');
}
?>