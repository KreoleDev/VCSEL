(async () => {
  document.getElementById('messageBg').style.backgroundImage = `url(${window.selectedProduct.serverUrl}uploads/messageBg.webp)`;
  window.gsap.to("#messageBg", { opacity: 1, duration: 0.3, delay: 0.5 })

  const response = await window.axios.post(window.selectedProduct.serverUrl, {
    query: {
      dataset: 'slides',
      action: 'getMessage',
      params: {}
    }
  },
  { headers: { 'Content-Type': 'application/json' } })

  const messageContainer = document.getElementById('message')
  messageContainer.innerHTML = '<div id="innerMessage">' + response.data.message + '</div>';
  const innerMessage = document.getElementById('innerMessage')
  let startFontSize = 200;
  while(innerMessage.scrollHeight > messageContainer.offsetHeight) {
    startFontSize -= 1;
    innerMessage.style.fontSize = startFontSize + 'px'
  }

  window.gsap.to("#message", { opacity: 1, duration: 0.8, filter: 'blur(0px)', delay: 1.4 })

  // delay for 15 seconds
  await new Promise(resolve => setTimeout(resolve, 15000))

  window.gsap.to("#message", { opacity: 0, duration: 0.2 })
  window.gsap.to("#messageBg", { css: { scale:1.4, opacity:0, filter: 'blur(0.9vw)' }, duration: 0.8, onComplete: () => {
    loadSlide();
  }})
})();
