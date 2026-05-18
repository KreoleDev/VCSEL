<?php
//Developer:  Charles Palmer
//Created:    2020.10.01
//Revision:   2020.10.15

/*
*   2020.10.15  CP  Removed targets
*/

?>
<script>
  const slate1 = Vue.component('slate1', function (resolve, reject) {
    resolve({
      template: `
        <div id="slate1">
          <?php require_once('page-content.php'); ?>
        </div>
      `,
      data: function() {
        return {
          weekProductionChartData: {
            data: [0, 0, 0, 0, 0],
            backgroundColor: [
              'rgba(255, 99, 132, 0.2)',
              'rgba(54, 162, 235, 0.2)',
              'rgba(255, 206, 86, 0.2)',
              'rgba(75, 192, 192, 0.2)',
              'rgba(153, 102, 255, 0.2)'
            ],
            borderColor: [
              'rgba(255, 99, 132, 1)',
              'rgba(54, 162, 235, 1)',
              'rgba(255, 206, 86, 1)',
              'rgba(75, 192, 192, 1)',
              'rgba(153, 102, 255, 1)'
            ]
          },
          orderCompletionChartData: {
            data: [70, 30]
          },
          yearlyChartData: {
            data: [1, 2, 5, 6]
          },
          orderByDayChartData: {
            data:[1,2],
            labels:["a","b"]
          },
          onTrackPercentage: 0
        }
      },
      mounted () {
        (async () => {
          const weekProduction = await axios.post(CFG_DATA_SERVICE_URL, { mode: 'getWeekProduction' }, { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
          this.weekProductionChartData = weekProduction.data;

          const orderCompletion = await axios.post(CFG_DATA_SERVICE_URL, { mode: 'getOrderCompletion' }, { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
          this.orderCompletionChartData = orderCompletion.data;

          //const yearlyCompletion = await axios.post(CFG_DATA_SERVICE_URL, { mode: 'getYearlyCompletion' }, { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
          //this.yearlyChartData = yearlyCompletion.data;

          const orderByDay = await axios.post(CFG_DATA_SERVICE_URL, { mode: 'getOrderByDay' }, { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
          this.orderByDayChartData = orderByDay.data;

          const onTrack = await axios.post(CFG_DATA_SERVICE_URL, { mode: 'getOnTrack' }, { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
          this.onTrackPercentage = onTrack.data.onTrackPercentage;

          let t1 = gsap.timeline()
          //t1.to("#yearlyWrapperA", { duration: 0.8, opacity: 1})
          //t1.to("#yearlyFreqWrapperA", { delay: -0.8, duration: 0.8, opacity: 1})
          t1.to("#wrapperOuter", { duration: 0.5, opacity: 1})
          t1.to("#titleLabel", { duration: 0.5, opacity: 1})
          t1.to("#onTrackWrapperA", { duration: 0.8, opacity: 1})
          t1.to("#orderByDayWrapperA", { duration: 0.8, opacity: 1})
          t1.to("#orderCompletionWrapperA", { duration: 0.8, opacity: 1})
          t1.to("#myChartWrapperA", { duration: 0.8, opacity: 1 })


          //this.loadChartYearly()
          //this.loadChartYearlyFreq()
          setTimeout(() => {
            this.loadChartOrderByDay()
          }, 1800);

          setTimeout(() => {
            this.loadChartOrder()
          }, 2600);

          setTimeout(() => {
            this.loadChartWeekly()
          }, 3400);
          

          setTimeout(() => {
            let t1 = gsap.timeline()
            //t1.to("#yearlyWrapperA", { duration: 0.4, opacity: 0})
            //t1.to("#yearlyFreqWrapperA", { delay: -0.4, duration: 0.4, opacity: 0})
            t1.to("#onTrackWrapperA", { duration: 0.4, opacity: 0})
            t1.to("#orderByDayWrapperA", { delay: -0.4, duration: 0.4, opacity: 0})
            t1.to("#orderCompletionWrapperA", { delay: -0.4, duration: 0.4, opacity: 0})
            t1.to("#myChartWrapperA", { delay: -0.4, duration: 0.4, opacity: 0 })
            t1.to("#wrapperOuter", { duration: 0.5, opacity: 0})
            t1.to("#titleLabel", { duration: 0.5, opacity: 0})
            setTimeout(() => {
              router.replace('/slate2');
            }, 1800);
            
          }, 30000);
        })()
      },
      methods: {
        //------------------------------------------------------------------------------------------------------
        loadChartWeekly: function() {
          var ctx = 'myChart';
          var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
              labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
              datasets: [{
                  label: 'Current',
                  data: this.weekProductionChartData.data,
                  backgroundColor: this.weekProductionChartData.backgroundColor,
                  borderColor: this.weekProductionChartData.borderColor,
                  borderWidth: 1
              }/*,
              {
                  label: 'Target',
                  data: this.weekProductionChartData.dataTarget,
                  backgroundColor: this.weekProductionChartData.backgroundColorTarget,
                  borderColor: this.weekProductionChartData.borderColorTarget,
                  borderWidth: 1
              }*/]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                yAxes: [{
                  ticks: {
                      beginAtZero: true
                  }
                }]
              },
              title: {
                display: true,
                text: 'This Week\'s Production'
              },
              legend: {
                display: true
              },
              tooltips: {
                  enabled: false
              },
              hover: {
                  animationDuration: 0
              },
              animation: {
                  duration: 1000,
                  easing: 'easeInQuad',
                  onComplete: function () {
                      var chartInstance = this.chart,
                          ctx = chartInstance.ctx;
                      ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, Chart.defaults.global.defaultFontStyle, Chart.defaults.global.defaultFontFamily);
                      ctx.textAlign = 'center';
                      ctx.textBaseline = 'bottom';

                      this.data.datasets.forEach(function (dataset, i) {
                          var meta = chartInstance.controller.getDatasetMeta(i);
                          meta.data.forEach(function (bar, index) {
                              var data = dataset.data[index];                            
                              ctx.fillText(data, bar._model.x, bar._model.y - 5);
                          });
                      });
                  }
              }
            }
          });
        },
        //------------------------------------------------------------------------------------------------------
        loadChartYearly: function() {
          var ctx = 'yearly';
          var yearly = new Chart(ctx, {
            type: 'line',
            data: {
              datasets: [{
                label: 'Running Total',
                data: this.yearlyChartData.data,
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                pointBorderColor: 'rgba(0,0,0,0)',
                pointBackgroundColor:'rgba(0,0,0,0)'
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              title: {
                display: true,
                text: 'Yearly Produced'
              },
              legend: {
                display: true
              },
              animation: {
                  duration: 1000,
                  easing: 'easeInQuad',
              },
              scales: {
                    xAxes: [{
                          type: 'linear', // MANDATORY TO SHOW YOUR POINTS! (THIS IS THE IMPORTANT BIT) 
                          display: false, // mandatory
                          scaleLabel: {
                              display: true, // mandatory
                              labelString: 'Your label' // optional 
                          },
                    }], 
                    yAxes: [{ // and your y axis customization as you see fit...
                      display: true,
                      scaleLabel: {
                            display: true,
                            labelString: 'Count'
                      }
                  }],
              }
            }
          })
        },
        //------------------------------------------------------------------------------------------------------
        loadChartYearlyFreq: function() {
          var ctx = 'yearlyFreq';
          var yearlyFreq = new Chart(ctx, {
            type: 'line',
            data: {
              datasets: [{
                label: 'Frequency',
                data: this.yearlyChartData.dataFreq,
                backgroundColor: 'rgba(128, 128, 128, 0.5)',
                pointBorderColor: 'rgba(0,0,0,0)',
                pointBackgroundColor:'rgba(0,0,0,0)'
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              title: {
                display: false,
                text: 'Yearly Produced'
              },
              legend: {
                display: true
              },
              animation: {
                  duration: 1000,
                  easing: 'easeInQuad',
              },
              scales: {
                    xAxes: [{
                          type: 'linear', // MANDATORY TO SHOW YOUR POINTS! (THIS IS THE IMPORTANT BIT) 
                          display: false, // mandatory
                          scaleLabel: {
                              display: true, // mandatory
                              labelString: 'Your label' // optional 
                          },
                    }], 
                    yAxes: [{ // and your y axis customization as you see fit...
                      display: true,
                      scaleLabel: {
                            display: true,
                            labelString: 'Count'
                      }
                  }],
              }
            }
          })
        },
        //------------------------------------------------------------------------------------------------------
        loadChartOrder: function() {
          var ctx = 'orderCompletion';
          var orderCompletion = new Chart(ctx, {
            type: 'pie',
            data: {
              datasets: [{
                data: this.orderCompletionChartData.data,
                backgroundColor: [
                  'rgba(75, 192, 192, 0.7)',
                  'rgba(255, 255, 255, 1)'
                ]
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              title: {
                display: true,
                text: 'Next Shipment Completion (Shipping: ' + this.orderCompletionChartData.dueDate + ')'
              },
              legend: {
                display: false
              },
              animation: {
                  duration: 1000,
                  easing: 'easeInQuad',
              }
            }
          })
        },
        //------------------------------------------------------------------------------------------------------
        loadChartOrderByDay: function () {
          var ctx = 'orderByDay';
          var orderByDay = new Chart(ctx, {
            type: 'bar',
            data: {
              labels: this.orderByDayChartData.labels,
              datasets: [{
                  label: 'Current',
                  data: this.orderByDayChartData.data,
                  backgroundColor: 'rgba(75, 192, 192, 0.7)',
                  borderColor: "rgba(75, 192, 192, 1)",
                  borderWidth: 1
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                yAxes: [{
                  ticks: {
                      beginAtZero: true
                  }
                }]
              },
              title: {
                display: true,
                text: 'Next Shipment Production Days'
              },
              legend: {
                display: false
              },
              tooltips: {
                  enabled: false
              },
              hover: {
                  animationDuration: 0
              },
              animation: {
                  duration: 1000,
                  easing: 'easeInQuad',
                  onComplete: function () {
                      var chartInstance = this.chart,
                          ctx = chartInstance.ctx;
                      ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, Chart.defaults.global.defaultFontStyle, Chart.defaults.global.defaultFontFamily);
                      ctx.textAlign = 'center';
                      ctx.textBaseline = 'bottom';

                      this.data.datasets.forEach(function (dataset, i) {
                          var meta = chartInstance.controller.getDatasetMeta(i);
                          meta.data.forEach(function (bar, index) {
                              var data = dataset.data[index];                            
                              ctx.fillText(data, bar._model.x, bar._model.y - 5);
                          });
                      });
                  }
              }
            }
          });
        }
        //------------------------------------------------------------------------------------------------------
      }
    })
  })
</script>
