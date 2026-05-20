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

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';
        
        //look up module title and content
        if($_REQUEST['module_id']==1){
            $info=$common['db']->pec('SELECT content, revision_date_time FROM core_user_guide_sections WHERE ext_module_id=? LIMIT 1',array($_REQUEST['module_id']),'i',array('content', 'revision_date_time'));
            $info[0]['title']='Introduction';
        }else{
            $info=$common['db']->pec('SELECT title, content, revision_date_time FROM core_modules, core_user_guide_sections WHERE ext_module_id=module_id AND module_id=? LIMIT 1',array($_REQUEST['module_id']),'i',array('title','content', 'revision_date_time'));
        }
        
        echo $common['window']->begin($info[0]['title']);
            echo '<p><a class="button" href="index.php" title="Back">Back</a></p>';
            echo '<div id="user_guide_content">';
                //look up all url paths for modules
                $results=$common['db']->pec('SELECT path, module_id FROM core_modules',array(),'',array('path','module_id'));
                $patterns=array();
                $replacements=array();
                foreach($results as $row){
                    $patterns[]='/\[mod_url:'.$row['module_id'].'\]/i';
                    $replacements[]=CFG_CMS_BASE_URL.$row['path'].'?mod_id='.$row['module_id'];
                }
                echo preg_replace($patterns,$replacements,str_replace('[site_title]',CFG_CMS_NAME,$info[0]['content']));
                echo '<div class="modified_date">Last Modified: '.date('m/d/Y @ g:iA',strtotime($info[0]['revision_date_time'])) . '</div>';
            echo '</div>';
        echo $common['window']->end();
        
    require_once('common/includes/footer_inner.inc.php');
}
?>