<?php
require_once __DIR__ . '/../../core/helpers/FileHelper.php';

class DownloadController
{
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
    
        // Calculer le niveau global de chiffrement pour l'affichage
        require_once __DIR__ . '/../models/FileKeyModel.php';
        $keyModel = new FileKeyModel();
        $encryptionLevel = 'none';
        foreach ($fichierBdd as $fichier) {
            $uuid = $fichier['uuid'];
            $fileName = $fichier['file_name'];
        
        
            $keyData = $keyModel->getKey($uuid, $fileName);
        
            if (!$keyData) {
                // echo "<pre style='color:red'>❌ Clé non trouvée pour $fileName</pre>";
            } else {
                // echo "<pre style='color:green'>✅ Clé trouvée : {$keyData['encryption_level']} pour $fileName</pre>";
        
                if ($keyData['encryption_level'] === 'aes_rsa') {
                    $encryptionLevel = 'aes_rsa';
                    break;
                }
        
                if ($keyData['encryption_level'] === 'aes' && $encryptionLevel === 'none') {
                    $encryptionLevel = 'aes';
                }
            }
        }
        
        
    
        foreach ($fichierBdd as &$file) {
            $file['name'] = $file['uuid'] . '/' . $file['file_name'];
        }
    
        $arborescence = FileHelper::buildFileTree($fichierBdd);
        $tokenExpire = $fichierBdd[0]['token_expire'];
    
        require __DIR__ . '/../views/download/tree.php';
    }
    


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

    if ($debug) {
        error_log("[DEBUG] [DownloadController::file] Token reçu : $token");
        error_log("[DEBUG] [DownloadController::file] Fichier demandé (URL) : $fileRequested");
    }

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
        error_log("[ERROR] [DownloadController::file] Aucun fichier trouvé pour le token.");
        http_response_code(404);
        echo "Lien invalide ou expiré.";
        return;
    }

    foreach ($fichiers as $fichier) {
        $expectedPath = FileHelper::getRelativePath($fichier['file_path']);

        if ($debug) {
            error_log("[DEBUG] [DownloadController::file] Comparaison : $fileRequested === $expectedPath");
        }

        if ($fileRequested === $expectedPath) {
            $realPath = $fichier['file_path'];

            if (!file_exists($realPath)) {
                error_log("[ERROR] [DownloadController::file] Fichier introuvable sur le disque.");
                http_response_code(404);
                echo "Fichier introuvable.";
                return;
            }

            $keyData = $keyModel->getKey($fichier['uuid'], $fichier['file_name']);
            $encryptionLevel = $keyData['encryption_level'] ?? 'none';

            if ($debug) error_log("[DEBUG] [DownloadController::file] Chiffrement = $encryptionLevel");

            if ($encryptionLevel === 'none' || !$keyData) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($fichier['file_name']) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($realPath));
                readfile($realPath);
                exit;
            }

            $encryptedData = file_get_contents($realPath);
            $ivLength = openssl_cipher_iv_length('aes-256-cbc');
            $iv = substr($encryptedData, 0, $ivLength);
            $ciphertext = substr($encryptedData, $ivLength);
            $aesKey = null;

            if ($encryptionLevel === 'aes') {
                $masterKey = $_ENV['MASTER_ENCRYPTION_KEY'];
                $aesKey = openssl_decrypt($keyData['encrypted_key'], 'aes-256-cbc', $masterKey, 0, $iv);
            } elseif ($encryptionLevel === 'aes_rsa') {
                $privateKeyPath = $_ENV['RSA_PRIVATE_KEY_PATH'];
                if (!file_exists($privateKeyPath)) {
                    http_response_code(500);
                    echo "Clé privée RSA manquante.";
                    return;
                }
                $privateKey = file_get_contents($privateKeyPath);
                openssl_private_decrypt(base64_decode($keyData['encrypted_key']), $aesKey, $privateKey);
            }

            if (!$aesKey) {
                http_response_code(500);
                echo "Erreur : clé AES invalide.";
                return;
            }

            $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $aesKey, 0, $iv);
            if ($decrypted === false) {
                http_response_code(500);
                echo "Erreur lors du déchiffrement.";
                return;
            }

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($fichier['file_name']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . strlen($decrypted));
            echo $decrypted;
            exit;
        }
    }

    http_response_code(404);
    echo "Fichier non trouvé ou non autorisé.";
}

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
        $parts = explode('/', $relativePath);
        array_shift($parts);
        $cleanedPath = implode('/', $parts);

        if (!file_exists($realPath)) continue;

        $keyData = $keyModel->getKey($fichier['uuid'], $fichier['file_name']);
        $encryptionLevel = $keyData['encryption_level'] ?? 'none';

        if ($encryptionLevel === 'none' || !$keyData) {
            $zip->addFile($realPath, $cleanedPath);
            continue;
        }

        $content = file_get_contents($realPath);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($content, 0, $ivLength);
        $ciphertext = substr($content, $ivLength);

        $aesKey = null;

        if ($encryptionLevel === 'aes') {
            $masterKey = $_ENV['MASTER_ENCRYPTION_KEY'];
            $aesKey = openssl_decrypt($keyData['encrypted_key'], 'aes-256-cbc', $masterKey, 0, $iv);
        } elseif ($encryptionLevel === 'aes_rsa') {
            $privateKeyPath = $_ENV['RSA_PRIVATE_KEY_PATH'];
            if (!file_exists($privateKeyPath)) {
                $zip->addFromString($cleanedPath . '_ERROR.txt', "Erreur : Clé RSA privée manquante.");
                continue;
            }

            $privateKeyContent = file_get_contents($privateKeyPath);
            $privateKey = openssl_pkey_get_private($privateKeyContent);

            if (!$privateKey) {
                $zip->addFromString($cleanedPath . '_ERROR.txt', "Erreur : Chargement de la clé RSA privée échoué.");
                continue;
            }

            $success = openssl_private_decrypt(base64_decode($keyData['encrypted_key']), $aesKey, $privateKey);
            if (!$success) {
                $zip->addFromString($cleanedPath . '_ERROR.txt', "Erreur lors du déchiffrement RSA de la clé AES.");
                continue;
            }
        }

        if (!$aesKey) {
            $zip->addFromString($cleanedPath . '_ERROR.txt', "Erreur : Clé AES non récupérable.");
            continue;
        }

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $aesKey, 0, $iv);

        if ($decrypted === false) {
            $zip->addFromString($cleanedPath . '_DECRYPTION_ERROR.txt', "Erreur lors du déchiffrement AES.");
            continue;
        }

        $zip->addFromString($cleanedPath, $decrypted);
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
