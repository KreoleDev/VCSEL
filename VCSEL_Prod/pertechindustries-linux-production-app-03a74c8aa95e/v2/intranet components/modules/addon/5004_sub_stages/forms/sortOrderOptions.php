<?php
//Developer:    Charles Palmer
//Created:      2022.09.27
//Revision:     2022.09.27
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     View
    [1]     Manage
*/

/*
*   
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(1)){
  if(isset($_REQUEST['subStageId'])) {
    $subStageId = $_REQUEST['subStageId'];
  } else {
    $subStageId = 0;
  }

  if(isset($_REQUEST['productId'])) {
    // Find new list for sort order
    $results = $common['db']->pec('SELECT sortOrder, title FROM prod_v2_sub_stages WHERE extProductId = ? AND subStageId<>? ORDER BY sortOrder', array($_REQUEST['productId'], $subStageId),'ii', array('sortOrder', 'title'));
    echo '<option>-- Select One --</option>';
    echo '<option value="-1">=== TOP LEVEL ===</option>';
    foreach($results as $row) {
      echo '<option value="'.$row['sortOrder'].'">'.$row['title'].'</option>';
    }
  }
}
?>