<?php
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file) && !str_ends_with($file, '.php')) {
    return false;
}

require __DIR__ . '/../main.php';