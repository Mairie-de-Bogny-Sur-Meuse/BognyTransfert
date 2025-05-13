<?php

/**
 * Contrôleur principal de la page d’accueil.
 * Gère l'affichage de la vue d'accueil ainsi que la redirection vers le formulaire d'envoi.
 */
class HomeController
{
    /**
     * Affiche la page d'accueil du site.
     * Cette page peut contenir une présentation du service ou une redirection manuelle.
     */
    public function index(): void
    {
        require_once __DIR__ . '/../views/home/index.php';
    }

    /**
     * Redirige automatiquement l'utilisateur vers la page d'envoi de fichiers.
     * Utile pour les accès directs à la racine ou pour les boutons "Commencer".
     */
    public function redirectToUpload(): void
    {
        header("Location: /upload");
        exit;
    }
}
