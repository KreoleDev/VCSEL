<?php require_once(dirname(__DIR__) . '/protected/config.inc.php'); ?>
<!DOCTYPE html>
<html>
  <head>
    <title>PDF Viewer PDF</title>
    <meta charset="UTF-8" />
    <link rel="stylesheet" href="./styles.css" />
  </head>

  <body>
    <div id="app">
      <div role="toolbar" id="toolbar">
        <div id="pager">
          <button data-pager="prev" class="prevBtn">< Page</button>
          <a href="../manuals.php">Back to Manuals</a>
          <button data-pager="next" class="nextBtn">Page ></button>
        </div>
        <div id="page-mode">
          <label>Page Mode <input type="number" value="1" min="1"/></label>
        </div>
      </div>
      <div id="viewport-container"><div role="main" id="viewport"></div></div>
    </div>
    <script src="https://unpkg.com/pdfjs-dist@2.0.489/build/pdf.min.js"></script>
    <script src="render.js"></script>
    <script>
    initPDFViewer("<?=CFG_CMS_BASE_URL; ?>sites/pertech/uploads/7008_manuals/<?=rawurlencode(isset($_REQUEST['id'])?$_REQUEST['id']:''); ?>.pdf");
    </script>
  </body>
</html>
