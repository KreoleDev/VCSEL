<?php
//Developer:    Charles Palmer
//Created:      2014.05.27
//Revision:     2014.05.30
require('common/includes/std_lib.inc.php');

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
                $("#chart_b").load($(this).attr("href"));
                return false;
            });
        });
    </script>
    
    <a class="button chart_load" href="log_in_by_day_week.chart.php" title="Log In By Day Of Week">Log In By Day Of Week</a>
    <a class="button chart_load" href="log_in_by_time_of_day.chart.php" title="Log In By Time Of Day">Log In By Time Of Day</a>
    <div id="chart_b"><?php include('log_in_by_day_week.chart.php'); ?></div>
    <?php
}
?>