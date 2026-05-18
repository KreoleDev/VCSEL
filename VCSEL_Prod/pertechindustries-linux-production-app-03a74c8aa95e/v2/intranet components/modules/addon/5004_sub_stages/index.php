<?php
//Developer:    Charles Palmer
//Created:      2022.09.27
//Revision:     2022.09.27

/*
*   
*/

/*
[0]     View
[1]     Configure
*/

require_once('common/includes/std_lib.inc.php');

$page_title='Sub Stages';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  require_once('common/includes/header_inner.inc.php');
    echo '<h2>'.$page_title.'</h2>';

    echo $common['window']->begin($page_title);
      if($common['security']->check_rights(1)){
        echo '<p><a class="button" href="forms/sub_stages.frm.php" title="Add Sub Stage">Add Sub Stage</a></p>';
      }
      
      $headings=array('Product', 'Sort Order', 'Sub Stage', 'Template', 'Active');
      $heading_classes=array('', '', '', 'center', 'center');

      if($common['security']->check_rights(1)){ //Edit
        $headings[]='&nbsp;';
        $heading_classes[]='no_sort';
      }

      echo $common['table']->begin($headings,'full_table',$heading_classes);

        $results = $common['db']->pec('SELECT subStageId, prod_v2_sub_stages.title, prod_v2_products.title, prod_v2_sub_stages.sortOrder, prod_v2_sub_stages.active, prod_v2_templates.title FROM prod_v2_sub_stages, prod_v2_products, prod_v2_templates WHERE prod_v2_sub_stages.extTemplateId=templateId AND extProductId=productId ORDER BY prod_v2_sub_stages.sortOrder', array(), '', array('subStageId', 'subStageTitle', 'productTitle', 'sortOrder', 'active', 'templateTitle'));
        foreach($results as $row) {
          $cols=array(
            $row['productTitle'],
            $row['sortOrder'],
            $row['subStageTitle'],
            $row['templateTitle'],
            $row['active']?'Yes':'No');
          $colClasses=array('','center','','','center');

          if($common['security']->check_rights(1)){ //Edit
            $cols[]='<a href="forms/sub_stages.frm.php?mode=edit&amp;subStageId='.$row['subStageId'].'" title="Edit Sub Stage">Edit</a>';
            $colClasses[]='center';
          }

          echo $common['table']->add_row($cols, $colClasses);
        }

      echo $common['table']->end();

    echo $common['window']->end();
  require_once('common/includes/footer_inner.inc.php');
}
?>