<?php
//Developer:    Charles Palmer
//Created:      2014.05.30
//Revision:     2014.05.30
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     Full Control
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    //Find all modules
    $modules=array();
    $results=$common['db']->pec('SELECT module_id, title FROM core_modules ORDER BY title',array(),'',array('module_id', 'title'));
    foreach($results as $row){
        $modules[$row['module_id']]['title']=$row['title'];
        $modules[$row['module_id']]['count']=0;
    }
    
    //Find all module actions
    $results=$common['db']->pec('SELECT ext_module_id FROM core_user_module_actions',array(),'',array('ext_module_id'));
    
    foreach($results as $row){
        $modules[$row['ext_module_id']]['count']++;
    }
    
    $data='';
    foreach($modules as $key=>$value){
        $data.=(empty($data)?'':', ') . '[\'' . $modules[$key]['title'] . '\',' . $modules[$key]['count'] . ']';
    }
    ?>
    <div id="chartdiv_b" style="height:400px;width:100%; "></div>
    <script type="text/javascript">
	$(document).ready(function(){
            var data = [
                <?=$data; ?>
              ];
              var plot1 = jQuery.jqplot ('chartdiv_b', [data],
                  
                {
                    title: 'Modules By Usage',
                    seriesDefaults: {
                        // Make this a pie chart.
                        renderer: jQuery.jqplot.PieRenderer,
                        rendererOptions: {
                            // Put data labels on the pie slices.
                            // By default, labels show the percentage of the slice.
                            showDataLabels: true
                        }
                    },
                    legend: { show:true, location: 'w' },
                }
              );
        });
    </script>
    <?php
}
?>