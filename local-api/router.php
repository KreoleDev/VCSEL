<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if (is_file($file)) {
    return false;
}

if (is_dir($file) && is_file(rtrim($file, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php')) {
    require rtrim($file, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
    return true;
}

return false;
?>
