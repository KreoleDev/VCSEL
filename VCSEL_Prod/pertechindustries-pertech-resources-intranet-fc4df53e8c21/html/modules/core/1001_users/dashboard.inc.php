<?php
//Developer:    Charles Palmer
//Created:      2014.05.02
//Revision:     2014.07.03
require_once('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Add User
[2]     Edit User
[3]     Delete User
[4]     Password Reset
*/

$common['security']->generate_page_rights(false,1001);

if($common['security']->check_rights(0)){
    echo $common['window']->begin('Users');
        echo '<div id="chart">';
            include('most_active_users_7_day.chart.php');
        echo '</div>';
    echo $common['window']->end();
}
?>