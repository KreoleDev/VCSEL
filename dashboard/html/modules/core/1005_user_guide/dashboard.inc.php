<?php
//Developer:    Charles Palmer
//Created:      2015.07.17
//Revision:     2015.07.17
require_once('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Modify
*/

$common['security']->generate_page_rights(false,1005);

if($common['security']->check_rights(0)){
    echo $common['window']->begin('User Guide: Introduction');
        echo '<div id="user_guide_content">';
            $intro_info=$common['db']->pec('SELECT content, revision_date_time FROM core_user_guide_sections WHERE ext_module_id=1 LIMIT 1',array(),'',array('content', 'revision_date_time'));
            if(!empty($intro_info)){
                //look up all url paths for modules
                $results=$common['db']->pec('SELECT path, module_id FROM core_modules',array(),'',array('path','module_id'));
                $patterns=array();
                $replacements=array();
                foreach($results as $row){
                    $patterns[]='/\[mod_url:'.$row['module_id'].'\]/i';
                    $replacements[]=CFG_CMS_BASE_URL.$row['path'].'?mod_id='.$row['module_id'];
                }
                echo preg_replace($patterns,$replacements,str_replace('[site_title]',CFG_CMS_NAME,$intro_info[0]['content']));
                echo '<div class="modified_date">Last Modified: '.date('m/d/Y @ g:iA',strtotime($intro_info[0]['revision_date_time'])) . '</div>';
            }
        echo '</div>';
    echo $common['window']->end();
}
?>