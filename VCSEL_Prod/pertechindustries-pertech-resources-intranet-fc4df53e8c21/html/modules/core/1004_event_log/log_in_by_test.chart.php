<?php
//Developer:    Charles Palmer
//Created:      2014.05.28
//Revision:     2014.05.29
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     Full Control
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    ?>
    
    <div id="chartdiv" style="height:400px;width:100%; "></div>
    <script type="text/javascript">
	$(document).ready(function(){
            $.jqplot('chartdiv',  [[[1, 2],[3,5.12],[5,13.1],[7,33.6],[9,85.9],[11,219.9]]],
                { title:'Exponential Line',
                  axes:{yaxis:{min:-10, max:240}},
                  animate: true,
                  cursor: {
                        show: true,
                        zoom: true,
                        looseZoom: true,
                        showTooltip: true
                    },
                  series:[{
                    pointLabels: {
                        show: false
                    },
                    color:'#5FAB78'
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
    
    <div id="chartdiv2" style="height:400px;width:100%; "></div>
    <script type="text/javascript">
	$(document).ready(function(){
            var s1 = [200, 600, 700, 1000];
            var s2 = [460, -210, 690, 820];
            var s3 = [-260, -440, 320, 200];
            // Can specify a custom tick Array.
            // Ticks should match up one for each y value (category) in the series.
            var ticks = ['May', 'June', 'July', 'August'];
             
            var plot1 = $.jqplot('chartdiv2', [s1, s2, s3], {
                // The "seriesDefaults" option is an options object that will
                // be applied to all series in the chart.
                animate: true,
                seriesDefaults:{
                    renderer:$.jqplot.BarRenderer,
                    rendererOptions: {fillToZero: true}
                },
                // Custom labels for the series are specified with the "label"
                // option on the series option.  Here a series option object
                // is specified for each series.
                series:[
                    {label:'Hotel'},
                    {label:'Event Regristration'},
                    {label:'Airfare'}
                ],
                // Show the legend and put it outside the grid, but inside the
                // plot container, shrinking the grid to accomodate the legend.
                // A value of "outside" would not shrink the grid and allow
                // the legend to overflow the container.
                legend: {
                    show: true,
                    placement: 'outsideGrid'
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
                        tickOptions: {formatString: '$%d'}
                    }
                }
            });
        });
    </script>
    <div id="chartdiv3" style="height:400px;width:100%; "></div>
    <script type="text/javascript">
	$(document).ready(function(){
        var data = [
          ['Heavy Industry', 12],['Retail', 9], ['Light Industry', 14],
          ['Out of home', 16],['Commuting', 7], ['Orientation', 9]
        ];
        var plot1 = jQuery.jqplot ('chartdiv3', [data],
            
          {
            animate: true,
            seriesDefaults: {
              // Make this a pie chart.
              renderer: jQuery.jqplot.PieRenderer,
              rendererOptions: {
                // Put data labels on the pie slices.
                // By default, labels show the percentage of the slice.
                showDataLabels: true
              }
            },
            legend: { show:true, location: 'e' }
          }
        );
      });
    </script>
    <?php
}
?>