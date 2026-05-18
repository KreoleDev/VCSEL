<?php
// Developer:  Charles Palmer
// Created:    2022.10.14
// Revision:   2022.10.14

?>
<style>
  .centerOnScreen {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
  }
    .centerOnScreen button {
      display: block;
      width: 50vw;
      height: 20vh;
      font-size: 2em;
    }

  #overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 1);
    z-index: 100;
    display:none;
    cursor: none;
  }
    #overlay:hover {
      cursor: none;
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
<div class="centerOnScreen">
  <button onclick="goFullscreen()">Start Player</button>
</div>
<div id="overlay" onclick="leaveFullscreen()"><div id="slideWrapper"></div></div>