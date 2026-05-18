<?php
//Developer:    Charles Palmer
//Created:      2022.09.27
//Revision:     2022.10.17

/*
*   2022.10.17  CP  Added sorting on query
*/

/*
[0]     View
[1]     Configure
*/

require_once('common/includes/std_lib.inc.php');

$page_title='Test Sets';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  require_once('common/includes/header_inner.inc.php');
    echo '<h2>'.$page_title.'</h2>';

    echo $common['window']->begin($page_title);
      if($common['security']->check_rights(1)){
        echo '<p><a class="button" href="forms/test_sets.frm.php" title="Add Test Set">Add Test Set</a></p>';
      }
      
      $headings=array('Product', 'Title', 'Description', 'Active');
      $heading_classes=array('', '', '', 'center');

      if($common['security']->check_rights(1)){ //Edit
        $headings[]='&nbsp;';
        $heading_classes[]='no_sort';
      }

      echo $common['table']->begin($headings,'full_table',$heading_classes);

        $results = $common['db']->pec('SELECT testSetId, prod_v2_products.title, prod_v2_test_sets.title, description, prod_v2_test_sets.active FROM prod_v2_test_sets, prod_v2_products WHERE extProductId=productId ORDER BY prod_v2_products.title, prod_v2_test_sets.title', array(), '', array('testSetId', 'product', 'title', 'description', 'active'));
        foreach($results as $row) {
          $cols=array(
            $row['product'],
            $row['title'],
            $row['description'],
            $row['active']?'Yes':'No');
          $colClasses=array('', '','','center');

          if($common['security']->check_rights(1)){ //Edit
            $cols[]='<a href="forms/test_sets.frm.php?mode=edit&amp;testSetId='.$row['testSetId'].'" title="Edit Template">Edit</a>';
            $colClasses[]='center';
          }

          echo $common['table']->add_row($cols, $colClasses);
        }

      echo $common['table']->end();

    echo $common['window']->end();
  require_once('common/includes/footer_inner.inc.php');
}
?>