<?php
require_once __DIR__ . '/../../core/helpers/FileHelper.php';

/**
 * Contrôleur responsable de la gestion des téléchargements :
 * - Affichage de l'arborescence de fichiers
 * - Téléchargement de fichiers individuels (avec ou sans chiffrement)
 * - Téléchargement groupé en ZIP avec déchiffrement si nécessaire
 */
class DownloadController
{
    /**
     * Affiche la vue arborescente des fichiers liés à un token.
     * Vérifie que le lien n'a pas expiré et détermine le niveau de chiffrement global.
     */
    public function index()
    {
        if (!isset($_GET['token'])) {
            $title = "Lien invalide";
            $message = "Aucun token fourni.";
            $code = 403;
            require_once __DIR__ . '/../views/errors/custom_error.php';
            return;
        }

        $token = $_GET['token'];

        require_once __DIR__ . '/../models/FichierModel.php';
        $fichierModel = new FichierModel();
        $fichierBdd = $fichierModel->findByToken($token);

        if (!$fichierBdd || strtotime($fichierBdd[0]['token_expire']) < time()) {
            $title = "Lien expiré ou invalide";
            $message = "Ce lien n'est plus valide ou a expiré.";
            $code = 410;
            require_once __DIR__ . '/../views/errors/custom_error.php';
            return;
        }

        session_start();
        $passwordHash = $fichierModel->getPasswordHashByToken($token);

        require_once __DIR__ . '/../models/FileKeyModel.php';
        $keyModel = new FileKeyModel();
        $encryptionLevel = 'none';

        // Déterminer le chiffrement le plus élevé utilisé
        foreach ($fichierBdd as $fichier) {
            $uuid = $fichier['uuid'];
            $fileName = $fichier['file_name'];
            $keyData = $keyModel->getKey($uuid, $fileName);

            if (!$keyData) continue;

            if ($keyData['encryption_level'] === 'aes_rsa') {
                $encryptionLevel = 'aes_rsa';
                break;
            } elseif ($keyData['encryption_level'] === 'aes' && $encryptionLevel === 'none') {
                $encryptionLevel = 'aes';
            }
        }

        foreach ($fichierBdd as &$file) {
            $file['name'] = $file['uuid'] . '/' . $file['file_name'];
        }

        $arborescence = FileHelper::buildFileTree($fichierBdd);
        $tokenExpire = $fichierBdd[0]['token_expire'];

        require __DIR__ . '/../views/download/tree.php';
    }

    /**
     * Permet de télécharger un fichier individuel.
     * Gère le déchiffrement à la volée si nécessaire selon le niveau de chiffrement (none, aes, aes_rsa).
     */
    public function file()
    {
        $debug = ($_ENV['DEBUG_LOG'] ?? 'false') === 'true';

        if (!isset($_GET['token'], $_GET['file'])) {
            http_response_code(400);
            echo "Paramètres manquants.";
            return;
        }

        $token = $_GET['token'];
        $fileRequested = urldecode($_GET['file']);

        require_once __DIR__ . '/../models/FichierModel.php';
        require_once __DIR__ . '/../models/FileKeyModel.php';

        $model = new FichierModel();
        $keyModel = new FileKeyModel();
        $fichiers = $model->findByToken($token);
        session_start();
        $passwordHash = $model->getPasswordHashByToken($token);

        if (!empty($passwordHash) && !isset($_SESSION['access_granted'][$token])) {
            http_response_code(403);
            echo "Mot de passe requis.";
            return;
        }

        if (!$fichiers) {
            http_response_code(404);
            echo "Lien invalide ou expiré.";
            return;
        }

        foreach ($fichiers as $fichier) {
            $expectedPath = FileHelper::getRelativePath($fichier['file_path']);

            if ($fileRequested === $expectedPath) {
                $realPath = $fichier['file_path'];
                if (!file_exists($realPath)) {
                    http_response_code(404);
                    echo "Fichier introuvable.";
                    return;
                }

                $keyData = $keyModel->getKey($fichier['uuid'], $fichier['file_name']);
                $encryptionLevel = $keyData['encryption_level'] ?? 'none';

                if ($encryptionLevel === 'none' || !$keyData) {
                    // Envoi direct sans déchiffrement
                    header('Content-Disposition: attachment; filename="' . basename($fichier['file_name']) . '"');
                    header('Content-Length: ' . filesize($realPath));
                    readfile($realPath);
                    exit;
                }

                // Lecture et extraction IV + déchiffrement
                $encryptedData = file_get_contents($realPath);
                $ivLength = openssl_cipher_iv_length('aes-256-cbc');
                $iv = substr($encryptedData, 0, $ivLength);
                $ciphertext = substr($encryptedData, $ivLength);
                $aesKey = null;

                if ($encryptionLevel === 'aes') {
                    $masterKey = $_ENV['MASTER_ENCRYPTION_KEY'];
                    $aesKey = openssl_decrypt($keyData['encrypted_key'], 'aes-256-cbc', $masterKey, 0, $iv);
                } elseif ($encryptionLevel === 'aes_rsa') {
                    $privateKey = file_get_contents($_ENV['RSA_PRIVATE_KEY_PATH']);
                    openssl_private_decrypt(base64_decode($keyData['encrypted_key']), $aesKey, $privateKey);
                }

                $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $aesKey, 0, $iv);
                if ($decrypted === false) {
                    http_response_code(500);
                    echo "Erreur lors du déchiffrement.";
                    return;
                }

                // Envoi du fichier déchiffré
                header('Content-Disposition: attachment; filename="' . basename($fichier['file_name']) . '"');
                header('Content-Length: ' . strlen($decrypted));
                echo $decrypted;
                exit;
            }
        }

        http_response_code(404);
        echo "Fichier non trouvé ou non autorisé.";
    }

    /**
     * Permet le téléchargement de l'ensemble des fichiers dans un fichier ZIP,
     * avec déchiffrement automatique si nécessaire.
     */
    public function handleDownload()
    {
        if (!isset($_GET['token'])) {
            $title = "Lien invalide";
            $message = "Aucun token fourni.";
            $code = 403;
            require_once __DIR__ . '/../views/errors/custom_error.php';
            return;
        }

        $token = $_GET['token'];

        require_once __DIR__ . '/../models/FichierModel.php';
        require_once __DIR__ . '/../models/FileKeyModel.php';
        require_once __DIR__ . '/../../core/helpers/FileHelper.php';

        $fichierModel = new FichierModel();
        $keyModel = new FileKeyModel();
        $fichiers = $fichierModel->findByToken($token);

        session_start();
        $passwordHash = $fichierModel->getPasswordHashByToken($token);
        if (!empty($passwordHash) && !isset($_SESSION['access_granted'][$token])) {
            http_response_code(403);
            echo "Mot de passe requis.";
            return;
        }

        if (!$fichiers || strtotime($fichiers[0]['token_expire']) < time()) {
            $title = "Lien expiré ou invalide";
            $message = "Ce lien n'est plus valide ou a expiré.";
            $code = 410;
            require_once __DIR__ . '/../views/errors/custom_error.php';
            return;
        }

        $zipFile = tempnam(sys_get_temp_dir(), 'bogny_') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
            http_response_code(500);
            echo "Impossible de créer l'archive ZIP.";
            return;
        }

        foreach ($fichiers as $fichier) {
            $realPath = $fichier['file_path'];
            $relativePath = FileHelper::getRelativePath($realPath);
            array_shift($parts = explode('/', $relativePath));
            $cleanedPath = implode('/', $parts);

            if (!file_exists($realPath)) continue;

            $keyData = $keyModel->getKey($fichier['uuid'], $fichier['file_name']);
            $encryptionLevel = $keyData['encryption_level'] ?? 'none';

            if ($encryptionLevel === 'none' || !$keyData) {
                $zip->addFile($realPath, $cleanedPath);
                continue;
            }

            // Lecture et déchiffrement
            $content = file_get_contents($realPath);
            $ivLength = openssl_cipher_iv_length('aes-256-cbc');
            $iv = substr($content, 0, $ivLength);
            $ciphertext = substr($content, $ivLength);
            $aesKey = null;

            if ($encryptionLevel === 'aes') {
                $masterKey = $_ENV['MASTER_ENCRYPTION_KEY'];
                $aesKey = openssl_decrypt($keyData['encrypted_key'], 'aes-256-cbc', $masterKey, 0, $iv);
            } elseif ($encryptionLevel === 'aes_rsa') {
                $privateKey = openssl_pkey_get_private(file_get_contents($_ENV['RSA_PRIVATE_KEY_PATH']));
                openssl_private_decrypt(base64_decode($keyData['encrypted_key']), $aesKey, $privateKey);
            }

            $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $aesKey, 0, $iv);
            $zip->addFromString($cleanedPath, $decrypted ?: "Erreur déchiffrement.");
        }

        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="fichiers_' . date('Ymd_His') . '.zip"');
        header('Content-Length: ' . filesize($zipFile));
        readfile($zipFile);
        unlink($zipFile);
        exit;
    }
}