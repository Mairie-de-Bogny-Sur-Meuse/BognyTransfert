<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/vendor/autoload.php'; // Autoload PHPMailer



// Autoload des contrôleurs
spl_autoload_register(function ($class) {
    $paths = ['app/controllers/', 'app/models/'];
    foreach ($paths as $path) {
        $file = __DIR__ . "/$path$class.php";
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

$router = new Router();
$router->handleRequest();
