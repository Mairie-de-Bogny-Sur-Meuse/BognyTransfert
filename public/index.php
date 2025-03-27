<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Point d'entrée public
require_once '../core/Autoloader.php';
require_once __DIR__ . '/../vendor/autoload.php';

Autoloader::register();

require_once  '../core/Env.php';
Env::load('../.env');

require_once '../core/App.php';

$app = new App();
$app->run();
