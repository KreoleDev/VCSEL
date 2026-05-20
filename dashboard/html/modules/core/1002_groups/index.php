<?php
//Developer:    Charles Palmer
//Created:      2014.05.20
//Revision:     2014.05.22
require_once('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Add Group
[2]     Edit Group
[3]     Delete Group
*/

$page_title='Groups';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';
        
        echo $common['window']->begin('Groups');
            if($common['security']->check_rights(1)){
                echo '<p><a class="button" href="forms/groups.frm.php" title="Add Group">Add Group</a></p>';
            }
            
            $headings=array('Name','Members');
            $heading_classes=array('','');
            
            if($common['security']->check_rights(2)){ //edit
                $headings[]='&nbsp;';
                $heading_classes[]='no_sort';
            }
            
            if($common['security']->check_rights(3)){ //delete
                $headings[]='&nbsp;';
                $heading_classes[]='no_sort';
            }
            
            echo $common['table']->begin($headings,'full_table',$heading_classes);
            
            //Find all users that are members of groups
            $results=$common['db']->pec('SELECT first_name, last_name, ext_group_id FROM core_user_group_lookup, core_users WHERE ext_user_id=user_id ORDER BY last_name, first_name',array(),'',array('first_name', 'last_name', 'ext_group_id'));
            $members=array();
            foreach($results as $row){
                if(isset($members[$row['ext_group_id']])){
                    $members[$row['ext_group_id']].=', ' . $row['first_name'] . ' '.$row['last_name'];
                }else{
                    $members[$row['ext_group_id']]=$row['first_name'] . ' '.$row['last_name'];
                }
            }
                        
            $results=$common['db']->pec('SELECT group_id, name FROM core_groups WHERE 1 ORDER BY name',array(),'',array('group_id','name'));
            foreach($results as $row){
                $cols=array($row['name'],$row['group_id']==1?'Everyone':(isset($members[$row['group_id']])?$members[$row['group_id']]:''));
                $col_classes=array('','');
                
                if($common['security']->check_rights(2)){ //edit
                    $cols[]='<a href="forms/groups.frm.php?mode=edit&amp;group_id='.$row['group_id'].'" title="Edit '.$row['name'].'">Edit</a>';
                    $col_classes[]='center';
                }
                
                if($common['security']->check_rights(3)){ //delete
                    $cols[]=$row['group_id']!=1 && $row['group_id']!=2?(isset($members[$row['group_id']])?'Has Members':'<a href="forms/groups.frm.php?mode=delete&amp;group_id='.$row['group_id'].'" title="Delete '.$row['name'].'" class="include_alert">Delete</a>'):'';
                    $col_classes[]='center';
                }
                
                echo $common['table']->add_row($cols,$col_classes);
            }
            
            echo $common['table']->end();
            
        echo $common['window']->end();
        
    require_once('common/includes/footer_inner.inc.php');
}
?>