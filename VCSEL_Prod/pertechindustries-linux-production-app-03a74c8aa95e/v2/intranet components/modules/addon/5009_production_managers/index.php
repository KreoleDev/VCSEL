<?php
//Developer:    Charles Palmer
//Created:      2022.10.11
//Revision:     2022.10.11

/*
*   
*/

/*
[0]     View
[1]     Configure
*/

require_once('common/includes/std_lib.inc.php');

$page_title='Production Managers';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';

        echo $common['window']->begin($page_title);
            if($common['security']->check_rights(1)){
                echo '<p><a class="button" href="forms/production_managers.frm.php" title="Add Production Manager">Add Production Manager</a></p>';
            }

            $headings=array('Name','Email');
            $heading_classes=array('','');

            if($common['security']->check_rights(1)){ // Edit, Delete
                $headings[]='&nbsp;';
                $heading_classes[]='no_sort';
                $headings[]='&nbsp;';
                $heading_classes[]='no_sort';
            }

            echo $common['table']->begin($headings,'full_table',$heading_classes);

            $results = $common['db']->pec('SELECT managerId, name, email FROM prod_v2_production_managers ORDER BY name', [], '', ['managerId', 'name', 'email']);
            foreach($results as $row) {
                $cols = [$row['name'], $row['email']];
                $col_classes=array('','');

                if($common['security']->check_rights(1)){ // Edit, Delete
                    $cols[]='<a href="forms/production_managers.frm.php?mode=edit&amp;managerId='.$row['managerId'].'" title="Edit">Edit</a>';
                    $col_classes[]='no_sort center';
                    $cols[]='<a href="forms/production_managers.frm.php?mode=delete&amp;managerId='.$row['managerId'].'" title="Delete" class="include_alert">Delete</a>';
                    $col_classes[]='no_sort center';
                }

                echo $common['table']->add_row($cols,$col_classes);
            }
            
            echo $common['table']->end();

        echo $common['window']->end();
    require_once('common/includes/footer_inner.inc.php');
}
?>