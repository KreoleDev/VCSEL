<?php
require_once(dirname(__DIR__) . '/_bootstrap.php');
apps_api_prepare_legacy_endpoint();
require(dirname(__DIR__, 2) . '/shipping-tests.php');
?>
