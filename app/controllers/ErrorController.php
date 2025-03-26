<?php

class ErrorController
{
    public function notFound()
    {
        http_response_code(404);
        $title = "Page introuvable";
        $message = "La page demandée n'existe pas.";
        $code = 404;
        require_once __DIR__ . '/../views/errors/custom_error.php';
    }

    public function forbidden()
    {
        http_response_code(403);
        $title = "Accès interdit";
        $message = "Vous n'avez pas les droits pour accéder à cette ressource.";
        $code = 403;
        require_once __DIR__ . '/../views/errors/custom_error.php';
    }

    public function internalError()
    {
        http_response_code(500);
        $title = "Erreur serveur";
        $message = "Une erreur interne est survenue.";
        $code = 500;
        require_once __DIR__ . '/../views/errors/custom_error.php';
    }

    public function custom(string $title, string $message, int $code = 400)
    {
        http_response_code($code);
        require_once __DIR__ . '/../views/errors/custom_error.php';
    }
}
