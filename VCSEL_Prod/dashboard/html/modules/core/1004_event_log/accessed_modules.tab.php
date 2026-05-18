<?php
//Developer:    Charles Palmer
//Created:      2014.05.27
//Revision:     2014.05.30
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     Full Control
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    ?>
    <script type="text/javascript">
        $(document).ready(function(){
            $(".chart_load").click(function(){
                $("#chart").load($(this).attr("href"));
                return false;
            });
        });
    </script>
    
    <a class="button chart_load" href="module_usage_by_day_week.chart.php" title="Module Usage By Day Of Week">Module Usage By Day Of Week</a>
    <a class="button chart_load" href="module_usage_by_time_of_day.chart.php" title="Module Usage By Time Of Day">Module Usage By Time Of Day</a>
    <a class="button chart_load" href="modules_by_usage.chart.php" title="Modules By Usage">Modules By Usage</a>
    <div id="chart"><?php include('module_usage_by_day_week.chart.php'); ?></div>
    <?php
}
?>