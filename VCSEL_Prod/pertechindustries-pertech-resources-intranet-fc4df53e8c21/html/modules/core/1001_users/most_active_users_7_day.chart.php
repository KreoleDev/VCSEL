<?php
//Developer:    Charles Palmer
//Created:      2014.07.03
//Revision:     2015.10.10

/*
 *  2015.10.10  CP  Added window resize function to rescale graph on window resize
 */

require_once('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Add User
[2]     Edit User
[3]     Delete User
[4]     Password Reset
*/

$common['security']->generate_page_rights(false,1001); //Generate user rights for page

if($common['security']->check_rights(0)){
    //Find all user actions
    $results=$common['db']->pec('SELECT count(*) as count, first_name, last_name FROM core_user_module_actions, core_users WHERE ext_user_id=user_id AND action<>"Load Page" AND (datetime <= NOW() AND datetime >=DATE_SUB(NOW(), INTERVAL 7 DAY)) GROUP BY ext_user_id ORDER BY count DESC',array(),'',array('count','first_name','last_name'));
    $s1='';
    $ticks='';
    foreach($results as $row){
        $s1.=(!empty($s1)?',':'').$row['count']; //CSV no quotes
        $ticks.=(!empty($ticks)?',':'') . '\'' . $row['first_name'] . ' '.$row['last_name'].'\''; //CSV w/quotes
    }
    ?>
    <div id="chartdiv" style="height:200px;width:100%; "></div>
    <script type="text/javascript">
	$(document).ready(function(){
            var s1 = [<?=$s1; ?>];
            var ticks = [<?=$ticks; ?>];
            var plot1 = $.jqplot('chartdiv', [s1], {
                title: 'Active Users Past 7 Days',
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
			$(window).resize(function() {
                    $.each(plot1.series, function(index, series) { series.barWidth = undefined; });
                    plot1.replot( { resetAxes: true } );
            });
        });
    </script>
    <?php
}
?>