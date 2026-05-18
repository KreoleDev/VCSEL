<?php
//Developer:    Charles Palmer
//Created:      2022.09.28
//Revision:     2022.10.05

/*
*   
*/

/*
[0]     View
[1]     Configure
*/

require_once('common/includes/std_lib.inc.php');

$page_title='Tests';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  require_once('common/includes/header_inner.inc.php');
    echo '<h2>'.$page_title.'</h2>';

    echo $common['window']->begin($page_title);
      if($common['security']->check_rights(1)){
        echo '<p><a class="button" href="forms/tests.frm.php" title="Add Test">Add Test</a></p>';
      }
      
      $headings=array('Product', 'Title', 'App Title', 'Active');
      $heading_classes=array('', '', '', 'center');

      if($common['security']->check_rights(1)){ //Edit
        $headings[]='&nbsp;';
        $heading_classes[]='no_sort';
      }

      echo $common['table']->begin($headings,'full_table',$heading_classes);

        $results = $common['db']->pec('SELECT testId, prod_v2_products.title, prod_v2_tests.title, appTitle, prod_v2_tests.active FROM prod_v2_tests, prod_v2_products WHERE extProductId=productId ORDER BY prod_v2_products.title, prod_v2_tests.title', array(), '', array('testId', 'product', 'title', 'appTitle', 'active'));
        foreach($results as $row) {
          $cols=array(
            $row['product'],
            $row['title'],
            $row['appTitle'],
            $row['active']?'Yes':'No');
          $colClasses=array('','', '','center');

          if($common['security']->check_rights(1)){ //Edit
            $cols[]='<a href="forms/tests.frm.php?mode=edit&amp;testId='.$row['testId'].'" title="Edit Test">Edit</a>';
            $colClasses[]='center';
          }

          echo $common['table']->add_row($cols, $colClasses);
        }

      echo $common['table']->end();

    echo $common['window']->end();
  require_once('common/includes/footer_inner.inc.php');
}
?>