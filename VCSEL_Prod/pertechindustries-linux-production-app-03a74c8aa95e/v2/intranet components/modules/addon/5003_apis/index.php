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

$page_title='APIs';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
  require_once('common/includes/header_inner.inc.php');
    echo '<h2>'.$page_title.'</h2>';

    echo $common['window']->begin($page_title);
      if($common['security']->check_rights(1)){
        echo '<p><a class="button" href="forms/apis.frm.php" title="Add API">Add API</a></p>';
      }
      
      $headings=array('Title', 'Active');
      $heading_classes=array('', 'center');

      if($common['security']->check_rights(1)){ //Edit
        $headings[]='&nbsp;';
        $heading_classes[]='no_sort';
      }

      echo $common['table']->begin($headings,'full_table',$heading_classes);

        $results = $common['db']->pec('SELECT apiId, title, active FROM prod_v2_apis', array(), '', array('apiId', 'title', 'active'));
        foreach($results as $row) {
          $cols=array(
            $row['title'],
            $row['active']?'Yes':'No');
          $colClasses=array('','center');

          if($common['security']->check_rights(1)){ //Edit
            $cols[]='<a href="forms/apis.frm.php?mode=edit&amp;apiId='.$row['apiId'].'" title="Edit API">Edit</a>';
            $colClasses[]='center';
          }

          echo $common['table']->add_row($cols, $colClasses);
        }

      echo $common['table']->end();

    echo $common['window']->end();
  require_once('common/includes/footer_inner.inc.php');
}
?>