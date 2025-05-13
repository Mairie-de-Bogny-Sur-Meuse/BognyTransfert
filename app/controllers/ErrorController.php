<?php

/**
 * Contrôleur des erreurs HTTP pour afficher des pages personnalisées
 * selon le type d'erreur rencontré (404, 403, 500, etc.).
 */
class ErrorController
{
    /**
     * Affiche une page d'erreur 404 (Not Found).
     * Utilisée lorsque la page demandée n'existe pas.
     */
    public function notFound(): void
    {
        http_response_code(404);

        // Variables pour la vue
        $title = "Page introuvable";
        $message = "La page demandée n'existe pas.";
        $code = 404;

        require_once __DIR__ . '/../views/errors/custom_error.php';
    }

    /**
     * Affiche une page d'erreur 403 (Forbidden).
     * Utilisée lorsque l'utilisateur n'a pas les droits nécessaires.
     */
    public function forbidden(): void
    {
        http_response_code(403);

        $title = "Accès interdit";
        $message = "Vous n'avez pas les droits pour accéder à cette ressource.";
        $code = 403;

        require_once __DIR__ . '/../views/errors/custom_error.php';
    }

    /**
     * Affiche une page d'erreur 500 (Internal Server Error).
     * Utilisée lors d'une erreur inattendue côté serveur.
     */
    public function internalError(): void
    {
        http_response_code(500);

        $title = "Erreur serveur";
        $message = "Une erreur interne est survenue.";
        $code = 500;

        require_once __DIR__ . '/../views/errors/custom_error.php';
    }

    /**
     * Affiche une page d'erreur personnalisée.
     *
     * @param string $title   Titre de l’erreur
     * @param string $message Message explicatif
     * @param int    $code    Code HTTP (par défaut 400)
     */
    public function custom(string $title, string $message, int $code = 400): void
    {
        http_response_code($code);

        // Ces variables seront utilisées dans la vue `custom_error.php`
        require_once __DIR__ . '/../views/errors/custom_error.php';
    }
}