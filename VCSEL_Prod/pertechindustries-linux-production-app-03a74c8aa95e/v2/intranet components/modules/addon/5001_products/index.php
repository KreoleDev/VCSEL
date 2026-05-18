<?php
//Developer:    Charles Palmer
//Created:      2022.09.26
//Revision:     2022.09.26

/*
*   
*/

/*
[0]     View
[1]     Configure
*/

require_once('common/includes/std_lib.inc.php');

$page_title='Products';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  require_once('common/includes/header_inner.inc.php');
    echo '<h2>'.$page_title.'</h2>';

    echo $common['window']->begin($page_title);
      if($common['security']->check_rights(1)){
        echo '<p><a class="button" href="forms/products.frm.php" title="Add Product">Add Product</a></p>';
      }
      
      $headings=array('Title', 'Template', 'Sort Order', 'Active');
      $heading_classes=array('','','center', 'center');

      if($common['security']->check_rights(1)){ //Edit
        $headings[]='&nbsp;';
        $heading_classes[]='no_sort';
      }

      echo $common['table']->begin($headings,'full_table',$heading_classes);

        $results = $common['db']->pec('SELECT productId, prod_v2_products.title, sortOrder, prod_v2_products.active, prod_v2_templates.title FROM prod_v2_products, prod_v2_templates WHERE extTemplateId=templateId', array(), '', array('productId', 'productTitle', 'sortOrder', 'active', 'templateTitle'));
        foreach($results as $row) {
          $cols=array(
            $row['productTitle'],
            $row['templateTitle'],
            $row['sortOrder'],
            $row['active']?'Yes':'No');
          $colClasses=array('','','center','center');

          if($common['security']->check_rights(1)){ //Edit
            $cols[]='<a href="forms/products.frm.php?mode=edit&amp;productId='.$row['productId'].'" title="Edit Product">Edit</a>';
            $colClasses[]='center';
          }

          echo $common['table']->add_row($cols, $colClasses);
        }

      echo $common['table']->end();

    echo $common['window']->end();
  require_once('common/includes/footer_inner.inc.php');
}
?>