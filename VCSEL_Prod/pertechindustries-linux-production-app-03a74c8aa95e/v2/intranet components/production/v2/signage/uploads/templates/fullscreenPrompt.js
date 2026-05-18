async function loadSlide () {
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
// --------------------------------------------------------------- //
async function goFullscreen () {
  try {
    await window.AppFullscreen.request()
    document.body.style.cursor = 'none'
    window.gsap.set("#overlay", { opacity: 0, display: "block" })
    window.gsap.to("#overlay", { opacity: 1, duration: 0.5 })
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
  } catch (err) {
    console.error(err)
  }
}
// --------------------------------------------------------------- //
async function leaveFullscreen () {
  try {
    await window.AppFullscreen.exit()
    document.body.style.cursor = 'auto'
    window.gsap.to("#overlay", { opacity: 0, duration: 0.5, onComplete: () => {
      window.gsap.set("#overlay", { display: "none" })
      window.selectedProduct.slides = []
      window.selectedProduct.slideIndex = 0
    } })
  } catch (err) {
    console.error(err)
  }
}
// --------------------------------------------------------------- //
(async () => {
  try {
    window.gsap.set("#templateWrapper", { opacity: 0 });

    // Inject chartjs
    const chartjs = await window.axios.get('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js')
    const chartjsElement = document.createElement('script')
    chartjsElement.text = chartjs.data
    document.body.appendChild(chartjsElement)

    window.gsap.to("#templateWrapper", { opacity: 1, duration: 0.3 });
  } catch (err) {
    console.log(err);
    window.Notify.create(err)
  }
})();