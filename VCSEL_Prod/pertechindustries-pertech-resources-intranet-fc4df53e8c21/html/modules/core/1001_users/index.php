<?php
//Developer:    Charles Palmer
//Created:      2014.05.20
//Revision:     2014.05.20
require_once('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Add User
[2]     Edit User
[3]     Delete User
[4]     Password Reset
*/

$page_title='Users';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';
        
        echo $common['window']->begin('Accounts');
            if($common['security']->check_rights(1)){
                echo '<p><a class="button" href="forms/users.frm.php" title="Add User">Add User</a></p>';
            }
        
            $results=$common['db']->pec('SELECT first_name, last_name, username, active, user_id FROM core_users WHERE 1 ORDER BY last_name, first_name',array(),'',array('first_name', 'last_name','username','active','user_id'));
            
            $headings=array('Last Name','First Name','Username','Active');
            $heading_classes=array('','','hide_mobile','hide_mobile');
            if($common['security']->check_rights(4)){ //Password Reset
                $headings[]='&nbsp;';
                $heading_classes[]='no_sort';
            }
            if($common['security']->check_rights(2)){ //Edit
                $headings[]='&nbsp;';
                $heading_classes[]='no_sort';
            }
            if($common['security']->check_rights(3)){ //Delete
                $headings[]='&nbsp;';
                $heading_classes[]='no_sort';
            }
            
            echo $common['table']->begin($headings,'full_table',$heading_classes);
            
            foreach($results as $row){
                $cols=array($row['last_name'],$row['first_name'],$row['username'],$row['active']?'Yes':'No');
                $col_classes=array('','','hide_mobile','hide_mobile center');
                
                if($common['security']->check_rights(4)){ //Password Reset
                    $cols[]='<a href="forms/users.frm.php?mode=reset_pw&amp;user_id=' . $row['user_id'] . '" title="Reset Password for ' . $row['first_name'] . ' ' . $row['last_name'] . '" class="include_alert">Reset Password</a>';
                    $col_classes[]='center';
                }
                if($common['security']->check_rights(2)){ //Edit
                    $cols[]='<a href="forms/users.frm.php?mode=edit&amp;user_id=' . $row['user_id'] . '" title="Edit ' . $row['first_name'] . ' ' . $row['last_name'] . '">Edit</a>';
                    $col_classes[]='center';
                }
                if($common['security']->check_rights(3)){ //Delete
                    $cols[]='<a href="forms/users.frm.php?mode=delete&amp;user_id=' . $row['user_id'] . '" title="Delete ' . $row['first_name'] . ' ' . $row['last_name'] . '" class="include_alert">Delete</a>';
                    $col_classes[]='center';
                }
                
                echo $common['table']->add_row($cols,$col_classes);
            }
            echo $common['table']->end();
        echo $common['window']->end();
        
    require_once('common/includes/footer_inner.inc.php');
}
?>