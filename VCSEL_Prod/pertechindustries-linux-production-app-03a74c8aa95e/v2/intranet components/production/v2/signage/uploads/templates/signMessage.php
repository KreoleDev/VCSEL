<style>
  #message {
    /* vertically center */
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);

    
    text-align: center;
    border:5px solid #fff;
    border-radius: 2vh;
    background-color:rgba(0,0,0,0.7);
    filter: blur(0.9vw);
    opacity: 0;
    max-height: 80vh;
    max-width: 90vw;
    overflow:hidden;
  }
  #innerMessage {
    height:auto;
    font-size: 200px;
    padding:40px;
  }
  #messageBg {
    position: absolute;
    top:0;
    left:0;
    width:100vw;
    height: 100vh;
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    opacity: 0;
    overflow: hidden;
  }
</style>
<div id="messageBg">
  <div id="message"></div>
</div>