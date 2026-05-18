<?php
//Developer:    Charles Palmer
//Created:      2019.12.05
//Revision:     2019.12.05

/*
*   
*/

/*
[0]     View
[1]     Admin
*/

require_once('common/includes/std_lib.inc.php');

$page_title='Manuals';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';

        echo $common['window']->begin($page_title);
            if($common['security']->check_rights(1)){
                echo '<p><a class="button" href="forms/manuals.frm.php" title="Add Manual">Add Manual</a></p>';
            }
            
            $headings=array('Part Number','Title','Last Updated','Keywords','Active','');
            $heading_classes=array('','','','','center','');

            if($common['security']->check_rights(1)){ //Edit
                $headings[]='&nbsp;';
                $heading_classes[]='no_sort';
            }

            echo $common['table']->begin($headings,'full_table',$heading_classes);

            $results = $common['db']->pec('SELECT manual_id, part_number, title, rev_number, change_date_time, has_image, keywords, active FROM 7008_manuals WHERE 1',array(),'',array('manual_id', 'part_number','title', 'rev_number', 'change_date_time', 'has_image', 'keywords', 'active'));
            foreach($results as $row) {  
                $cols=array($row['part_number'],$row['title'],$row['change_date_time'],$row['keywords'],$row['active']?'Yes':'No');
                $col_classes=array('','','','','center');

                if($common['security']->check_rights(1)){ //Edit
                    $cols[]='<a href="forms/manuals.frm.php?mode=edit&amp;manual_id='.$row['manual_id'].'" title="Edit">Edit</a>';
                    $col_classes[]='no_sort center';
                }

                $cols[] = '<a href="' . CFG_CMS_BASE_URL . 'sites/' . $_SESSION['site_path'] . 'uploads/7008_manuals/' . $row['manual_id'] . '.pdf" target="_blank">Manual</a>';
                $col_classes[]='center';

                echo $common['table']->add_row($cols,$col_classes);
            }

            echo $common['table']->end();

        echo $common['window']->end();
    require_once('common/includes/footer_inner.inc.php');
}
?>