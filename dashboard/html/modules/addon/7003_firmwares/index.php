<?php
//Developer:    Charles Palmer
//Created:      2019.03.01
//Revision:     2019.03.01

/*
*   
*/

/*
[0]     View
[1]     Configure
*/

require_once('common/includes/std_lib.inc.php');

$page_title='Firmwares';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';

        echo $common['window']->begin('Firmware Versions');
            if($common['security']->check_rights(1)){
                echo '<p><a class="button" href="forms/firmware.frm.php" title="Add Firmware">Add Firmware</a></p>';
            }

            $headings=array('Product','Version','MD5');
            $heading_classes=array('','','');

            if($common['security']->check_rights(1)){ //Edit
                $headings[]='&nbsp;';
                $heading_classes[]='no_sort';
            }

            echo $common['table']->begin($headings,'full_table',$heading_classes);

            $results = $common['db']->pec('SELECT firmware_id, version, md5, title FROM 2019_prod_firmwares, 2019_prod_products WHERE ext_product_id=product_id ORDER BY title, version',array(),'',array('firmware_id', 'version', 'md5', 'title'));
            foreach($results as $row) {
                $cols=array($row['title'],$row['version'],$row['md5']);
                $col_classes=array('','','');

                if($common['security']->check_rights(1)){ //Edit
                    $cols[]='<a href="forms/firmware.frm.php?mode=edit&amp;firmware_id='.$row['firmware_id'].'" title="Edit">Edit</a>';
                    $col_classes[]='no_sort center';
                }

                echo $common['table']->add_row($cols,$col_classes);
            }
            
            echo $common['table']->end();

        echo $common['window']->end();
    require_once('common/includes/footer_inner.inc.php');
}
?>