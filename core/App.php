<?php
require_once __DIR__ . '/Autoloader.php';
Autoloader::register();
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Env.php';


// Charger les variables d'environnement
Env::load();

class App
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $this->router->add('/', 'HomeController', 'index');
        $this->router->add('/home', 'HomeController', 'index');
        $this->router->add('/upload', 'UploadController', 'index');
        $this->router->add('/upload/handleUpload', 'UploadController', 'handleUpload');
        $this->router->add('/upload/confirmation', 'UploadController', 'confirmation');

        $this->router->add('/verify', 'VerifyController', 'index');
        $this->router->add('/verify/submit', 'VerifyController', 'submitCode');

        $this->router->add('/download', 'DownloadController', 'index');
        $this->router->add('/download/file', 'DownloadController', 'file');
        $this->router->add('/download/handleDownload', 'DownloadController', 'handleDownload');
    }

    public function run(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->router->dispatch($uri);
    }
}
