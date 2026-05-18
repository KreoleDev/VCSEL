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

$page_title='Production Tests';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';

        echo $common['window']->begin('Individual Tests');
            if($common['security']->check_rights(1)){
                echo '<p><a class="button" href="forms/tests.frm.php" title="Add Test">Add Test</a></p>';
            }

            $headings=array('Products','Title','Description');
            $heading_classes=array('','','');

            if($common['security']->check_rights(1)){ //Edit
                $headings[]='&nbsp;';
                $heading_classes[]='no_sort';
            }

            echo $common['table']->begin($headings,'full_table',$heading_classes);

            $results = $common['db']->pec('SELECT ext_test_id, title FROM 2019_prod_test_product_assoc, 2019_prod_products WHERE ext_product_id=product_id ORDER BY title',array(),'',array('ext_test_id', 'title'));
            $productsAssoc = array();
            foreach($results as $row) {
                if(!isset($productsAssoc[$row['ext_test_id']])) {
                    $productsAssoc[$row['ext_test_id']] = $row['title'];
                } else {
                    $productsAssoc[$row['ext_test_id']] .= ', ' . $row['title'];
                }
            }

            $results = $common['db']->pec('SELECT test_id, title, description FROM 2019_prod_tests WHERE 1 ORDER BY title',array(),'',array('test_id', 'title', 'description'));
            foreach($results as $row) {
                $cols=array(isset($productsAssoc[$row['test_id']])?$productsAssoc[$row['test_id']]:'',$row['title'],$row['description']);
                $col_classes=array('','','');

                if($common['security']->check_rights(1)){ //Edit
                    $cols[]='<a href="forms/tests.frm.php?mode=edit&amp;test_id='.$row['test_id'].'" title="Edit">Edit</a>';
                    $col_classes[]='no_sort center';
                }

                echo $common['table']->add_row($cols,$col_classes);
            }
            
            echo $common['table']->end();

        echo $common['window']->end();
    require_once('common/includes/footer_inner.inc.php');
}
?>