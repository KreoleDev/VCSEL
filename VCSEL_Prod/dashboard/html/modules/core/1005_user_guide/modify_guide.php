<?php
//Created:      2015.07.17
//Revision:     2015.07.17
require_once('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Modify
*/

$page_title='User Guide';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(1)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';
        
        echo $common['window']->begin('User Guide');
            echo '<p><a class="button" href="index.php" title="Back">Back</a></p>';
            
            $headings=array('Panel','Module','&nbsp;');
            $heading_classes=array('','','no_sort');
            
            echo $common['table']->begin($headings,'searchable',$heading_classes);
                $cols=array('','Introduction','<a href="forms/guide.frm.php?mode=edit&amp;ext_module_id=1" title="Edit Introduction">Edit</a>');
                $col_classes=array('','','center');
                echo $common['table']->add_row($cols,$col_classes);
            
                //find all modules
                $results=$common['db']->pec('SELECT module_id, core_modules.title, core_panels.title FROM core_modules, core_panels WHERE panel_id=ext_panel_id ORDER BY core_panels.sort_order, core_modules.sort_order',array(),'',array('module_id','module_title','panel_title'));
                foreach($results as $row){
                    $cols=array($row['panel_title'],$row['module_title'],'<a href="forms/guide.frm.php?mode=edit&amp;ext_module_id='.$row['module_id'].'" title="Edit '.$row['module_title'].'">Edit</a>');
                    $col_classes=array('','','center');
                    echo $common['table']->add_row($cols,$col_classes);
                }
            echo $common['table']->end();
            
        echo $common['window']->end();
        
    require_once('common/includes/footer_inner.inc.php');
}
?>