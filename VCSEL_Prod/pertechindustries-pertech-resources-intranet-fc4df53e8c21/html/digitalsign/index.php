<?php
//Developer:  Charles Palmer
//Created:    2020.10.01
//Revision:   2020.10.01

require_once(dirname(__DIR__) . '/protected/config.inc.php');
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>7680 Digital Sign</title>
    <link href="https://fonts.googleapis.com/css2?family=Overpass:wght@900&family=Piazzolla:wght@600&display=swap" rel="stylesheet"> 
    <link href="./lib/css/reset.css" rel="stylesheet">
    <link href="./lib/css/screen.css" rel="stylesheet">
    <link href="./lib/css/responsive.css?v=1" rel="stylesheet">
    <script src="./lib/js/vue-2-5-21.js"></script>
    <script src="./lib/js/vue-router-3-0-2.js"></script>
    <script src="./lib/js/axios-0-18-0.js"></script>
    <script src="./lib/js/gsap.min.js"></script>
    <script src="./lib/js/chart.min.js"></script>
    <?php require_once(dirname(__FILE__) .'/pages/slate1/slate1-loader.php'); ?>
    <?php require_once(dirname(__FILE__) .'/pages/slate2/slate2-loader.php'); ?>
    <script>
      const CFG_DATA_SERVICE_URL = '<?=CFG_CMS_BASE_URL; ?>digitalsign/data-service.php';
    </script>
    <script src="./router.js"></script>
  </head>
  <body>
    <div id="app">
      <router-view></router-view>
    </div>
    <script src="./app.js"></script>
  </body>
</html>
