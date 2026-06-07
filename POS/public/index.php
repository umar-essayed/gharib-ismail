<?php

declare(strict_types=1);

if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file(__DIR__ . $path)) {
        return false;
    }
}

require dirname(__DIR__) . '/bootstrap.php';

$router = new App\Core\Router();
require base_path('routes/web.php');

$method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];
$router->dispatch(strtoupper($method), $_SERVER['REQUEST_URI']);
