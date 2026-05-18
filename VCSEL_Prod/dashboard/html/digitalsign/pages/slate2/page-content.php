<div id="wrapperOuter2">

  <div id="messageOuter">
    <div id="messageInner">
      <pre>
        {{ message }}
      </pre>
    </div>
  </div>

  <div id="weatherWrapperOuter">
    <div v-if="weatherLoaded" id="weatherWrapper">
      <div v-for="(entry, id) in weather.properties.periods.slice(0,6)" :key="id">
        <div class="weatherEntry" :style="{ backgroundImage: 'url(' + entry.icon + ')' }">
          <h2>{{ entry.name }}</h2>
          <h3>{{ entry.temperature }}&deg;{{ entry.temperatureUnit }}</h3>
          <h4>{{ entry.shortForecast }}</h4>
        </div>
      </div>
    </div>
  </div>
</div>