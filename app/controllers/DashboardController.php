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
    /**
 * Supprime un transfert appartenant à l'utilisateur connecté.
 * Cela inclut la suppression des enregistrements en base et des fichiers sur le disque.
 */
    public function deleteTransfer()
    {
        session_start();

        // Vérifie la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Méthode non autorisée');
        }

        // Vérifie que l'utilisateur est bien authentifié
        if (!isset($_SESSION['user_id'], $_SESSION['user_email'])) {
            header('Location: /login');
            exit;
        }

        $token = $_POST['token'] ?? '';
        $csrf = $_POST['csrf_token'] ?? '';

        // Vérifie le jeton CSRF
        if (!SecurityModel::verifyCSRFToken($csrf)) {
            $_SESSION['error'] = "Jeton CSRF invalide.";
            header('Location: /dashboard');
            exit;
        }

        require_once __DIR__ . '/../models/FichierModel.php';

        // Récupère tous les fichiers liés à ce token et à l'utilisateur
        $fichierModel = new FichierModel();
        $fichiers = $fichierModel->findByTokenAndEmail($token, $_SESSION['user_email']);


        // Supprime les fichiers du disque
        foreach ($fichiers as $fichier) {
            $filePath = $fichier['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath); // Supprime le fichier
            }
        }

        // Supprime le dossier parent s'il est vide
        if (!empty($fichiers)) {
            $uuid = $fichiers[0]['uuid'];
            $uploadDir = rtrim($_ENV['UPLOAD_PATH'], '/') . '/' . $uuid;
            if (is_dir($uploadDir)) {
                // Supprime récursivement le dossier s’il est vide
                @rmdir($uploadDir);
            }
        }

        // Supprime les enregistrements en base
        $success = FichierModel::deleteByTokenAndEmail($token, $_SESSION['user_email']);

        $_SESSION[$success ? 'success' : 'error'] = $success
            ? "Transfert supprimé avec succès."
            : "Erreur : transfert introuvable ou non autorisé.";

        header('Location: /dashboard');
        exit;
    }

}
