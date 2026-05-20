<?php
//Developer:    Charles Palmer
//Created:      2014.05.27
//Revision:     2014.05.27
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     Full Control
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
   header("Content-Type: application/vnd.ms-excel");
   header("Content-disposition: attachment; filename=accessed_modules.xls");

    echo 'Date/Time' . "\t" . 'Module' . "\t" . 'Username' . "\t" . 'Action' . "\t" . 'Remote Address' . "\n";
    
    $results=$common['db']->pec('SELECT username, datetime, title, action, remote_address FROM core_user_module_actions, core_users, core_modules WHERE module_id=ext_module_id AND user_id=ext_user_id ORDER BY datetime',array(),'',array('username', 'datetime', 'title', 'action', 'remote_address'));
    foreach($results as $row){
        echo date('m/d/Y @ H:i',strtotime($row['datetime'])) . "\t" . $row['title'] . "\t" . $row['username'] . "\t" . $row['action'] . "\t" . $row['remote_address'] . "\n";
    } 
}
?>