<style>
  #weeklyChartWrapper{
    position: absolute;
    top: 6vh;
    left: 1vw;
    width: 98vw;
    height: 42vh;
  }
  #todayChartWrapper{
    position: absolute;
    top: 56vh;
    left: 1vw;
    width: 98vw;
    height: 42vh;
  }
</style>
<div id="weeklyHeading" style="position: absolute; top: 0; left: 0; width: 100vw; height: 6vh; background-color: #000; color: #fff; font-size: 4vh; text-align: center; line-height: 50px; opacity: 0;"></div>
<div id="weeklyChartWrapper">
  <canvas id="weeklyChart"></canvas>
</div>
<div id="todayHeading" style="position: absolute; top: 50vh; left: 0; width: 100vw; height: 6vh; background-color: #000; color: #fff; font-size: 4vh; text-align: center; line-height: 50px; opacity: 0;">Today's Progress (Additive)</div>
<div id="todayChartWrapper">
  <canvas id="todayChart"></canvas>
</div>