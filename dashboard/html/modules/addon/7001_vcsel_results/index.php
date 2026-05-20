<?php
//Developer:    Charles Palmer
//Created:      2019.06.11
//Revision:     2020.10.05
require_once('common/includes/std_lib.inc.php');

/*
*   2019.06.18  CP  Added table from database in
*   2020.09.16  CP  Added VCSEL s/n
*   2020.10.05  CP  Added notes
*/

/*
[0]     View
*/

$page_title='Vcsel Results';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';
        
        echo $common['window']->begin('Calibration Results');

            $headings=array('Date/Time','Tester','VCSEL S/N','Programmer S/N','Transmitter','Collector','Voltage','Notes','');
            $heading_classes=array('','','','','','','','');
            echo $common['table']->begin($headings,'full_table',$heading_classes);

            // Get all notes from db
            $results = $common['db']->pec('SELECT extVcselSerialNumber, dateTime, note FROM 2019_prod_7680_vcsel_results_notes ORDER BY dateTime',array(),'',array('extVcselSerialNumber', 'dateTime', 'note'));
            $notes = array();
            foreach($results as $row) {
              if (isset($notes[$row['extVcselSerialNumber']])) {
                $notes[$row['extVcselSerialNumber']] .= '<br/>';
              } else {
                $notes[$row['extVcselSerialNumber']] = '';
              }
              $notes[$row['extVcselSerialNumber']] .= '<strong>' . $row['dateTime'] . ':</strong> ' . $row['note'];
            }

            $results=$common['db']->pec('SELECT test_id, tester_name, date_time, programmer_serial_num, transmitter_val, collector_val, collector_voltage, vcselSerialNumber FROM 2019_prod_7680_vcsel_results',array(),'',array('test_id', 'tester_name', 'date_time', 'programmer_serial_num', 'transmitter_val', 'collector_val', 'collector_voltage', 'vcselSerialNumber'));
            foreach($results as $row) {
                $cols=array($row['date_time'],$row['tester_name'],$row['vcselSerialNumber'], $row['programmer_serial_num'],$row['transmitter_val'],$row['collector_val'],$row['collector_voltage'],(isset($notes[$row['vcselSerialNumber']])?$notes[$row['vcselSerialNumber']]:''),($row['vcselSerialNumber']!=0?'<a href="forms/notes.frm.php?vcselSerialNumber=' . $row['vcselSerialNumber'] . '">Add Note</a>':''));
                $col_classes=array('','','','','','','','','center');
                echo $common['table']->add_row($cols,$col_classes);
            }

            echo $common['table']->end();
        echo $common['window']->end();
        
    require_once('common/includes/footer_inner.inc.php');
}
?>