<?php

declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);



session_start();

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/includes/app.php';
require __DIR__ . '/../app/includes/db.php';
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../app/includes/settings.php';

use App\Core\Router;

Router::dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));