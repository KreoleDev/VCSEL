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
    $results=$common['db']->pec('SELECT datetime FROM core_user_module_actions',array(),'',array('datetime'));
    $days=array(0=>0,1=>0,2=>0,3=>0,4=>0,5=>0,6=>0); //0=Sunday, 1=Monday...
    foreach($results as $row){
        $days[date('w',strtotime($row['datetime']))]++;
    }
    ?>
    <div id="chartdiv_b" style="height:400px;width:100%; "></div>
    <script type="text/javascript">
	$(document).ready(function(){
            var s1 = [<?=$days[0]; ?>, <?=$days[1]; ?>, <?=$days[2]; ?>, <?=$days[3]; ?>,<?=$days[4]; ?>,<?=$days[5]; ?>,<?=$days[6]; ?>];
            var ticks = ['Sunday', 'Monday', 'Tuesday', 'Wednesday','Thursday','Friday','Saturday'];
            var plot1 = $.jqplot('chartdiv_b', [s1], {
                title: 'Module Usage By Day Of Week',
                animate: true,
                seriesDefaults:{
                    renderer:$.jqplot.BarRenderer,
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