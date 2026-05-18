<?php
//Developer:  Charles Palmer
//Created:    2020.10.01
//Revision:   2020.10.01

?>
<script>
  const slate2 = Vue.component('slate2', function (resolve, reject) {
    resolve({
      template: `
        <div id="slate1">
          <?php require_once('page-content.php'); ?>
        </div>
      `,
      data: function() {
        return {
          weatherLoaded: false,
          weather: null,
          message: 'Is this good for the company? This is some more text to see how well it all wraps.'
        }
      },
      mounted () {
        (async () => {
          const weather = await axios.get('https://api.weather.gov/gridpoints/RIW/117,109/forecast')
          this.weather = weather.data
          this.weatherLoaded = true
          //console.log(this.weather)

          const message = await axios.post(CFG_DATA_SERVICE_URL, { mode: 'getMessage' }, { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
          this.message = message.data.message;

          let t1 = gsap.timeline()
          t1.to("#wrapperOuter2", { duration: 0.5, opacity: 1})
          t1.to("#messageOuter", { duration: 1, opacity: 1})
          t1.to("#weatherWrapperOuter", { delay: -0.2, duration: 1, opacity: 1})

          setTimeout(() => {
            let t1 = gsap.timeline()
            t1.to("#messageOuter", { duration: 0.4, opacity: 0 })
            t1.to("#weatherWrapperOuter", { duration: 0.5, opacity: 0})
            t1.to("#wrapperOuter2", { duration: 0.5, opacity: 0})
            setTimeout(() => {
              router.replace('/');
            }, 1400);
            
          }, 20000);
        })()
      }
    })
  })
</script>
