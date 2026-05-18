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
   header("Content-disposition: attachment; filename=auth_actions.xls");
    echo 'Date/Time' . "\t" . 'Username' . "\t" . 'Action' . "\t" . 'Status' . "\t" . 'Reason' . "\t" . 'Remote Address' . "\n";
    
    $results=$common['db']->pec('SELECT username, datetime, action, status, reason, remote_address FROM core_user_auth_actions, core_users WHERE user_id=ext_user_id ORDER BY datetime',array(),'',array('username', 'datetime', 'action', 'status', 'reason', 'remote_address'));
    foreach($results as $row){
        echo date('m/d/Y @ H:i',strtotime($row['datetime'])) . "\t" . $row['username'] . "\t" . $row['action'] . "\t" . $row['status'] . "\t" . $row['reason'] . "\t" . $row['remote_address'] . "\n";
    } 
}
?>