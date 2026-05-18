<?php
//Developer:    Charles Palmer
//Created:      2015.07.17
//Revision:     2015.07.17
require_once('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Modify
*/

$page_title='User Guide';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';
        
        echo $common['window']->begin('User Guide');
            if($common['security']->check_rights(1)){
                echo '<p><a class="button" href="modify_guide.php" title="Edit Guide">Edit Guide</a></p>';
            }
        
            ?>
            <ul class="table_of_contents">
                <li><a class="lightbox" href="view_module.php?module_id=1" title="Introduction">Introduction</a></li>
                <?php
                $panels_csv='';
                $modules_csv='';
                foreach($_SESSION['modules'] as $key=>$value){
                    $panels_csv.=(empty($panels_csv)?'':',').$key;
                    foreach($_SESSION['modules'][$key] as $key2=>$value2){
                        $modules_csv.=(empty($modules_csv)?'':',').$_SESSION['modules'][$key][$key2]['id'];
                    }
                }
                
                //find info on all modules
                $results=$common['db']->pec('SELECT module_id, title, ext_panel_id FROM core_modules WHERE module_id IN('.$modules_csv.') ORDER BY sort_order',array(),'',array('module_id', 'title', 'ext_panel_id'));
                $modules_array=array();
                foreach($results as $row){
                    $modules_array[$row['ext_panel_id']][$row['module_id']]=$row['title'];
                }
                
                //find all modules that have guides
                $results=$common['db']->pec('SELECT ext_module_id, revision_date_time FROM core_user_guide_sections WHERE ext_module_id IN('.$modules_csv.')',array(),'',array('ext_module_id', 'revision_date_time'));
                $existing_guides=array();
                foreach($results as $row){
                    $existing_guides[$row['ext_module_id']]=$row['revision_date_time'];
                }
                
                //find info on all panels
                $results=$common['db']->pec('SELECT panel_id, title FROM core_panels WHERE panel_id IN('.$panels_csv.') ORDER BY sort_order',array(),'',array('panel_id', 'title'));
                foreach($results as $row){
                    echo '<li>'.$row['title'];
                        if(isset($modules_array[$row['panel_id']])){
                            echo '<ul>';
                            foreach($modules_array[$row['panel_id']] as $key=>$value){
                                echo '<li>'.(isset($existing_guides[$key])?'<a class="lightbox" href="view_module.php?module_id='.$key.'" title="'.$modules_array[$row['panel_id']][$key].'">':'').$modules_array[$row['panel_id']][$key].(isset($existing_guides[$key])?'</a>':'').'</li>';
                            }
                            echo '</ul>';
                        }
                    echo '</li>';
                }
                ?>
            </ul>
            <?php
        
        echo $common['window']->end();
        
    require_once('common/includes/footer_inner.inc.php');
}
?>