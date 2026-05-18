<?php
//Developer:    Charles Palmer
//Created:      2022.10.06
//Revision:     2022.10.06

/*
*   
*/

/*
[0]     View
[1]     Configure
*/

require_once('common/includes/std_lib.inc.php');

$page_title='6100 Calibration Clients';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';

        echo $common['window']->begin($page_title);
            if($common['security']->check_rights(1)){
                echo '<p><a class="button" href="forms/calibration_clients.frm.php" title="Add Calibration Client">Add Calibration Client</a></p>';
            }

            $headings=array('IP Address','Title','Color', 'Active');
            $heading_classes=array('','','','');

            if($common['security']->check_rights(1)){ // Edit, Delete
                $headings[]='&nbsp;';
                $heading_classes[]='no_sort';
                $headings[]='&nbsp;';
                $heading_classes[]='no_sort';
            }

            echo $common['table']->begin($headings,'full_table',$heading_classes);

            $results = $common['db']->pec('SELECT clientId, ipAddress, title, color, active FROM prod_v2_6100_calibration_clients ORDER BY ipAddress', [], '', ['clientId', 'ipAddress', 'title', 'color', 'active']);
            foreach($results as $row) {
                $cols = [$row['ipAddress'], $row['title'], $row['color'], $row['active'] ? 'Yes' : 'No'];
                $col_classes=array('','','','center');

                if($common['security']->check_rights(1)){ // Edit, Delete
                    $cols[]='<a href="forms/calibration_clients.frm.php?mode=edit&amp;clientId='.$row['clientId'].'" title="Edit">Edit</a>';
                    $col_classes[]='no_sort center';
                    $cols[]='<a href="forms/calibration_clients.frm.php?mode=delete&amp;clientId='.$row['clientId'].'" title="Delete" class="include_alert">Delete</a>';
                    $col_classes[]='no_sort center';
                }

                echo $common['table']->add_row($cols,$col_classes);
            }
            
            echo $common['table']->end();

        echo $common['window']->end();
    require_once('common/includes/footer_inner.inc.php');
}
?>