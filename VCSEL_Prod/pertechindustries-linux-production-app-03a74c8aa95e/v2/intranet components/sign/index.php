<?php
// Developer: Charles Palmer
// Created:   2022.10.16
// Revision:  2022.10.16

require_once('../protected/config.inc.php');
?>
<!DOCTYPE html>
<html>
<head>
<title>Digital Sign</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.1.3/axios.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.3/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
  window.axios = axios;
  window.gsap = gsap;
  window.selectedProduct = {
    serverUrl: '<?=CFG_CMS_BASE_URL?>production/v2/signage/',
    productId: 1,
  };
  window.analyticsServer = '<?=CFG_CMS_BASE_URL?>production/v2/analytics/';

  async function loadSlide() {
    if(window.selectedProduct && window.selectedProduct.slides && window.selectedProduct.slides.length > 0) {
      window.gsap.set("#slideWrapper", { opacity: 0, display: "block" })
      // Get template for slide
      const template = await window.axios.get(window.selectedProduct.slides[window.selectedProduct.slideIndex].templateUrl)
      document.getElementById('slideWrapper').innerHTML = template.data

      // delete old script if exists
      const oldScript = document.getElementById('slideScript' + window.selectedProduct.slideIndex)
      if (oldScript) {
        oldScript.remove()
      }

      window.gsap.to("#slideWrapper", { opacity: 1, duration: 0.5, delay:0.5, onComplete: async () => {
        // load script for slide
        const script = await window.axios.get(window.selectedProduct.slides[window.selectedProduct.slideIndex].scriptUrl)
        const scriptElement = document.createElement('script')
        scriptElement.id = 'slideScript' + window.selectedProduct.slideIndex
        scriptElement.text = script.data
        document.body.appendChild(scriptElement)

        // prep for next slide
        window.selectedProduct.slideIndex++
        if (window.selectedProduct.slideIndex >= window.selectedProduct.slides.length) {
          window.selectedProduct.slideIndex = 0
        }
      }})
    }
  }

  (async ()=> {
    const response = await window.axios.post(window.selectedProduct.serverUrl, {
      query: {
        dataset: 'slides',
        action: 'getSlidesForProduct',
        params: {
          productId: window.selectedProduct.productId
        }
      }
    },
    { headers: { 'Content-Type': 'application/json' } })

    if (response.data.success) {
      // Start slides
      window.selectedProduct.slideIndex = 0
      window.selectedProduct.slides = response.data.slides

      loadSlide()
    }
  })()
</script>
<style>
  #overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 1);
    z-index: 100;
  }
  #slideWrapper {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 101;
    display: none;
    color:#fff;
  }  
</style>
</head>
<body>

<div id="overlay"><div id="slideWrapper"></div></div>

</body>
</html> 