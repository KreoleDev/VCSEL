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
    echo $common['table']->begin(array('Date/Time','Username','Action','Status','Reason','Remote Address'),'full_table',array());
        $results=$common['db']->pec('SELECT username, datetime, action, status, reason, remote_address FROM core_user_auth_actions, core_users WHERE user_id=ext_user_id ORDER BY datetime',array(),'',array('username', 'datetime', 'action', 'status', 'reason', 'remote_address'));
        foreach($results as $row){
            echo $common['table']->add_row(array(date('m/d/Y @ H:i',strtotime($row['datetime'])),$row['username'],$row['action'],$row['status'],$row['reason'],$row['remote_address']),array());
        }
    echo $common['table']->end();
    echo '<a href="auth_actions.xls.php" title="Download" class="save">Download</a>';
    require('common/includes/js.inc.php');
}
?>