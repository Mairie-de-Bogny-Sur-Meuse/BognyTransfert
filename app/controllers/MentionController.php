<?php
/**
 * Contrôleur responsable de l'affichage des pages légales (RGPD et CGU).
 * Ces pages sont statiques et accessibles à tous les utilisateurs.
 */
class MentionController
{
    /**
     * Affiche la page relative à la politique de confidentialité (RGPD).
     */
    public function rgpd(): void
    {
        require_once __DIR__ . '/../views/mentions-rgpd.php';
    }

    /**
     * Affiche la page des conditions générales d’utilisation (CGU).
     */
    public function cgu(): void
    {
        require_once __DIR__ . '/../views/mentions-cgu.php';
    }
}
