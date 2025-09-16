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
        require_once __DIR__ . '/../models/UserModel.php';
        require_once __DIR__ . '/../models/FichierModel.php';
        session_start();

        // Redirige si l'utilisateur n'est pas connecté
        if (!isset($_SESSION['user_id'], $_SESSION['user_email'])) {
            header('Location: /login');
            exit;
        }

        // Génère un token CSRF s’il n’existe pas encore
        SecurityModel::generateCSRFToken();
        $email = $_SESSION['user_email'];
        // Récupération de l’utilisateur et des fichiers transférés
        $user = UserModel::findByEmail($email);
    
        
        // Regroupe les fichiers par jeton de transfert
        $groupes = [];
        $fichierModel = new FichierModel();
        $fichiers = $fichierModel->findByEmail($user['email']);
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
        function formatStorage($data):string{

            $kb = 1024; 
            $mb = $kb * 1024;
            $gb = $mb * 1024;
                if ($data >= $gb){
                    return round(($data / $gb),2) . ' Go ';
                }elseif ($data >= $mb){
                    return round(($data / $mb),2) . ' Mo ';
                }elseif ($data >= $kb){
                    return round(($data / $kb),2) . ' ko ';
                }else {
                    return round($data,2).' o ';
                }   
        }    
        $tokenPassword = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        UserModel::storeResetToken($_SESSION['user_id'], $tokenPassword, $expires);
        $user = UserModel::findById($_SESSION['user_id']);
        $quotaUtiliser = formatStorage($fichierModel->sumStorageForMonthByEmail($user['email']));
        $quotaTotal = formatStorage($_ENV['MAX_TOTAL_SIZE_PER_MONTH']);
        // Regrouper les fichiers par token
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

        $error = $_SESSION['error'] ?? null;
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']);
   

        //

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

    public function showEditForm()
    {
        session_start();
        $csrf = $_GET['csrf_token'] ?? '';
        if (!\SecurityModel::verifyCSRFToken($csrf)) {
            $_SESSION['error'] = "Jeton CSRF invalide.";
            header('Location: /dashboard');
            exit;
        }

        if (!isset($_SESSION['user_id'], $_SESSION['user_email'])) {
            header('Location: /login');
            exit;
        }
    
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            $_SESSION['error'] = "Lien invalide.";
            header('Location: /dashboard');
            exit;
        }
    
        require_once __DIR__ . '/../models/FichierModel.php';
    
        $fichiers = FichierModel::getByToken($token, $_SESSION['user_email']);
        if (!$fichiers || count($fichiers) === 0) {
            $_SESSION['error'] = "Aucun transfert trouvé.";
            header('Location: /dashboard');
            exit;
        }
    
        // Utiliser le 1er fichier pour extraire les infos globales (expiration / chiffrement)
        $expiration = $fichiers[0]['token_expire'] ?? date('Y-m-d\TH:i');
        $currentEncryption = $fichiers[0]['encryption_level'] ?? 'none';
    
        require __DIR__ . '/../views/dashboard/edit_transfer.php';
    }
    
        public function editTransfer()
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

        require_once __DIR__ . '/../models/SecurityModel.php';
        require_once __DIR__ . '/../models/FichierModel.php';

        $token = $_POST['token'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        $files = $_POST['files'] ?? [];
        $deleteUuids = $_POST['delete'] ?? [];
        $newPassword = $_POST['password'] ?? '';
        $expiration = $_POST['expiration'] ?? null;
        $newLevel = $_POST['encryption_level'] ?? null;
        $userEmail = $_SESSION['user_email'];
        $isAdmin = $_SESSION['is_admin'] ?? false;

        if (!SecurityModel::verifyCSRFToken($csrfToken)) {
            $_SESSION['error'] = "Jeton CSRF invalide.";
            header('Location: /dashboard');
            exit;
        }

        // ✅ 1. Renommage de fichiers
        foreach ($files as $uuid => $fileData) {
            $newName = trim($fileData['new_name'] ?? '');
            $oldName = trim($fileData['old_name'] ?? '');
            if ($newName && $newName !== $oldName) {
                FichierModel::updateFileName($uuid, $newName, $userEmail);
            }
        }

        // ✅ 2. Suppression unitaire ou groupée
        foreach ($deleteUuids as $uuid) {
            FichierModel::deleteFile($uuid, $userEmail);
        }

        // ✅ 3. Mot de passe
        if (!empty($newPassword)) {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            FichierModel::updateTransferPassword($token, $hashed, $userEmail);
        }

        // ✅ 4. Expiration
        if (!empty($expiration)) {
            $date = date('Y-m-d H:i:s', strtotime($expiration));
            FichierModel::updateExpirationDate($token, $date, $userEmail);
        }


        $_SESSION['success'] = "Transfert mis à jour avec succès.";
        header("Location: /dashboard");
        exit;
    }

}
