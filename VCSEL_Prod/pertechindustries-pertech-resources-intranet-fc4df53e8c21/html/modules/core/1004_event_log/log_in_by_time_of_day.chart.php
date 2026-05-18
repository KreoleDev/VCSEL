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
    //Find all logins
    $results=$common['db']->pec('SELECT datetime FROM core_user_auth_actions WHERE action="login"',array(),'',array('datetime'));
    $hours=array(0=>0,1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0,11=>0,12=>0,13=>0,14=>0,15=>0,16=>0,17=>0,18=>0,19=>0,20=>0,21=>0,22=>0,23=>0); //0=Sunday, 1=Monday...
    foreach($results as $row){
        $hours[date('G',strtotime($row['datetime']))]++;
    }
    ?>
    <div id="chartdiv" style="height:400px;width:100%; "></div>
    <script type="text/javascript">
	$(document).ready(function(){
            var s1 = [<?=$hours[0]; ?>, <?=$hours[1]; ?>, <?=$hours[2]; ?>, <?=$hours[3]; ?>,<?=$hours[4]; ?>,<?=$hours[5]; ?>,<?=$hours[6]; ?>,<?=$hours[7]; ?>,<?=$hours[8]; ?>,<?=$hours[9]; ?>,<?=$hours[10]; ?>,<?=$hours[11]; ?>,<?=$hours[12]; ?>,<?=$hours[13]; ?>,<?=$hours[14]; ?>,<?=$hours[15]; ?>,<?=$hours[16]; ?>,<?=$hours[17]; ?>,<?=$hours[18]; ?>,<?=$hours[19]; ?>,<?=$hours[20]; ?>,<?=$hours[21]; ?>,<?=$hours[22]; ?>,<?=$hours[23]; ?>];
            var ticks = ['12AM','1AM', '2AM', '3AM', '4AM','5AM','6AM','7AM','8AM','9AM','10AM','11AM','12PM','1PM','2PM','3PM','4PM','5PM','6PM','7PM','8PM','9PM','10PM','11PM'];
            var plot1 = $.jqplot('chartdiv', [s1], {
                title: 'Log In By Time Of Day',
                animate: true,
                seriesDefaults:{
                    renderer:$.jqplot.LineRenderer,
                    rendererOptions: {fillToZero: true}
                },
                axes: {
                    // Use a category axis on the x axis and use our custom ticks.
                    xaxis: {
                        renderer: $.jqplot.CategoryAxisRenderer,
                        ticks: ticks
                    },
                    // Pad the y axis just a little so bars can get close to, but
                    // not touch, the grid boundaries.  1.2 is the default padding.
                    yaxis: {
                        pad: 1.05,
                        tickOptions: {formatString: '%d'}
                    }
                },
                series:[{
                    pointLabels: {
                        show: false
                    },
                    color:'#FFA83E'
                }],
                highlighter: {
                        show: true,
                        showLabel: true,
                        tooltipAxes: 'y',
                        sizeAdjust: 7.5 , tooltipLocation : 'ne'
                    } 
            });
        });
    </script>
    <?php
}
?>