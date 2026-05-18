<div id="wrapperOuter">
  <div id="titleLabel">
    <div>
      7680 Production
    </div>
  </div>

  <!-- <div id="yearlyWrapperA">
    <div id="yearlyWrapperB">
      <canvas id="yearly"></canvas>
    </div>
  </div>

  <div id="yearlyFreqWrapperA">
    <div id="yearlyFreqWrapperB">
      <canvas id="yearlyFreq"></canvas>
    </div>
  </div> -->

  <div id="onTrackWrapperA">
    <div id="onTrackWrapperB">
      <h3>Production Status (As Of Yesterday)</h3>
      <div>
        <div style="float: left; width:33.3%">
          Behind Schedule
        </div>
        <div style="float: left; width:33.3%; text-align:center;">
          Just in Time
        </div>
        <div style="float: right; width:33.3%; text-align:right;">
          Ahead of Schedule
        </div>
      </div>
      <div class="barTrack">
        <div class="bar" :style="{ marginLeft: (onTrackPercentage - 0.5) + '%' }" :class="[{ barAhead: onTrackPercentage > 50 }, { barBehind: onTrackPercentage < 50 }]"></div>
      </div>
    </div>
  </div>

  <div id="orderByDayWrapperA">
    <div id="orderByDayWrapperB">
      <canvas id="orderByDay"></canvas>
    </div>
  </div>

  <div id="myChartWrapperA">
    <div id="myChartWrapperB">
      <canvas id="myChart"></canvas>
    </div>
  </div>

  <div id="orderCompletionWrapperA">
    <div id="orderCompletionWrapperB">
      <canvas id="orderCompletion"></canvas>
    </div>
  </div>
</div>