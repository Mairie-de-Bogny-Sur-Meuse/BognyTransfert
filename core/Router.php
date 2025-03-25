<?php

class Router
{
    public function handleRequest()
    {
        try {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

            if ($uri === '/' || $uri === '/upload-form') {
                require 'app/views/upload_form.php';

            } elseif ($uri === '/upload') {
                $controller = new UploadController();
                $controller->handleUpload();

            } elseif ($uri === '/verify' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                require 'app/views/verify_form.php';

            } elseif ($uri === '/download/file') {
                $controller = new DownloadController();
                $controller->downloadSingle($_GET['uuid'] ?? '', $_GET['file'] ?? '');

            } elseif ($uri === '/download/all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller = new DownloadController();
                $controller->downloadZip($_POST['uuid'] ?? '');

            } elseif (preg_match('#^/download/([a-z0-9]+)$#', $uri, $matches)) {
                $controller = new DownloadController();
                $controller->handleDownload($matches[1]);

            } elseif ($uri === '/verify' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                require_once 'app/controllers/VerificationController.php';
                $controller = new VerificationController();
                $controller->verify();

            } elseif ($uri === '/upload_success') {
                require 'app/views/upload_success.php';

            } else {
                $this->renderError(404);
            }

        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->renderError(500);
        }
    }

    private function renderError($code)
    {
        http_response_code($code);
        $errorView = "app/views/errors/{$code}.php";

        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo "<h1>Erreur {$code}</h1><p>Une erreur est survenue.</p>";
        }
    }
}
