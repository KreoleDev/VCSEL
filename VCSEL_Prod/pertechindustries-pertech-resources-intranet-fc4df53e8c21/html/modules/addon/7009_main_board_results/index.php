<?php
//Developer:    Charles Palmer
//Created:      2020.09.17
//Revision:     2020.10.05
require_once('common/includes/std_lib.inc.php');

/*
*   2020.10.05  CP  Added notes  
*/

/*
[0]     Full Control
*/

$page_title='Main Board Results';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';
        
        echo $common['window']->begin($page_title);

            $headings=array('Date/Time','Tester','S/N','Notes','');
            $heading_classes=array('','','','','');
            echo $common['table']->begin($headings,'full_table',$heading_classes);

            // Get all notes from db
            $results = $common['db']->pec('SELECT extSerialNumber, dateTime, note FROM 2019_prod_7680_board_test_results_notes ORDER BY dateTime',array(),'',array('extSerialNumber', 'dateTime', 'note'));
            $notes = array();
            foreach($results as $row) {
              if (isset($notes[$row['extSerialNumber']])) {
                $notes[$row['extSerialNumber']] .= '<br/>';
              } else {
                $notes[$row['extSerialNumber']] = '';
              }
              $notes[$row['extSerialNumber']] .= '<strong>' . $row['dateTime'] . ':</strong> ' . $row['note'];
            }

            $results=$common['db']->pec('SELECT testId, testerName, dateTime, serialNumber FROM 2019_prod_7680_board_test_results',array(),'',array('testId', 'testerName', 'dateTime', 'serialNumber'));
            foreach($results as $row) {
                $cols=array($row['dateTime'],$row['testerName'],$row['serialNumber'],(isset($notes[$row['serialNumber']])?$notes[$row['serialNumber']]:''),($row['serialNumber']!=0?'<a href="forms/notes.frm.php?serialNumber=' . $row['serialNumber'] . '">Add Note</a>':''));
                $col_classes=array('','','','','center');
                echo $common['table']->add_row($cols,$col_classes);
            }

            echo $common['table']->end();
        echo $common['window']->end();
        
    require_once('common/includes/footer_inner.inc.php');
}
?>