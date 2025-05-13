<?php

require_once __DIR__ . '/../models/SecurityModel.php';

/**
 * Contrôleur en charge du tableau de bord utilisateur.
 * Affiche les transferts et permet leur suppression sécurisée.
 */
class DashboardController
{
    /**
     * Affiche le tableau de bord contenant les transferts du compte connecté.
     */
    public function index()
    {
        session_start();

        // Redirige si l'utilisateur n'est pas connecté
        if (!isset($_SESSION['user_id'], $_SESSION['user_email'])) {
            header('Location: /login');
            exit;
        }

        // Génère un token CSRF s’il n’existe pas encore
        SecurityModel::generateCSRFToken();

        require_once __DIR__ . '/../models/UserModel.php';
        require_once __DIR__ . '/../models/FichierModel.php';

        $email = $_SESSION['user_email'];

        // Récupération de l’utilisateur et des fichiers transférés
        $user = UserModel::findByEmail($email);
        $fichiers = (new FichierModel())->findByEmail($email);

        // Regroupe les fichiers par jeton de transfert
        $groupes = [];
        foreach ($fichiers as $fichier) {
            $token = $fichier['token'];
            if (!isset($groupes[$token])) {
                $groupes[$token] = [
                    'files' => [],
                    'expire' => $fichier['token_expire'],
                ];
            }
            $groupes[$token]['files'][] = $fichier;
        }

        // Affiche la vue principale
        require_once __DIR__ . '/../views/dashboard/dashboard.php';
    }

    /**
     * Supprime un transfert (par token) appartenant à l’utilisateur.
     */
    public function deleteTransfer()
    {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Méthode non autorisée');
        }

        if (!isset($_SESSION['user_id'], $_SESSION['user_email'])) {
            header('Location: /login');
            exit;
        }

        $token = $_POST['token'] ?? '';
        $csrf = $_POST['csrf_token'] ?? '';

        // Vérifie la validité du jeton CSRF
        if (!SecurityModel::verifyCSRFToken($csrf)) {
            $_SESSION['error'] = "Jeton CSRF invalide.";
            header('Location: /dashboard');
            exit;
        }

        require_once __DIR__ . '/../models/FichierModel.php';

        $success = FichierModel::deleteByTokenAndEmail($token, $_SESSION['user_email']);

        $_SESSION[$success ? 'success' : 'error'] = $success
            ? "Transfert supprimé avec succès."
            : "Erreur : transfert introuvable ou non autorisé.";

        header('Location: /dashboard');
        exit;
    }
}
