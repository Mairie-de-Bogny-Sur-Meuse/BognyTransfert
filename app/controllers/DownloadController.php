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
        require_once __DIR__ . '/../../core/helpers/FileHelper.php';

        $fichierModel = new FichierModel();
        $fichierBdd = $fichierModel->findByToken($token);

        if (!$fichierBdd || strtotime($fichierBdd[0]['token_expire']) < time()) {
            $title = "Lien expiré ou invalide";
            $message = "Ce lien n'est plus valide ou a expiré.";
            $code = 410;
            require_once __DIR__ . '/../views/errors/custom_error.php';
            return;
        }

        $arborescence = FileHelper::buildFileTree($fichierBdd);
        $tokenExpire = $fichierBdd[0]['token_expire'];
        require_once __DIR__ . '/../views/download/tree.php';
    }
    public function file()
    {
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
    
        foreach ($fichiers as $fichier) {
            // Comparaison avec file_name au lieu de file_path
            if ($fichier['file_name'] === $fileRequested) {
                $realPath = $fichier['file_path'];
    
                if (!file_exists($realPath)) {
                    http_response_code(404);
                    echo "Fichier introuvable.";
                    return;
                }
    
                // Récupération de la clé
                $keyData = $keyModel->getKey($fichier['uuid'], $fichier['file_name']);
                $encryptionLevel = $keyData['encryption_level'] ?? 'none';
    
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
    
                // Lecture du fichier chiffré
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
                    $privateKey = file_get_contents($privateKeyPath);
                    openssl_private_decrypt(base64_decode($keyData['encrypted_key']), $aesKey, $privateKey);
                }
    
                $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $aesKey, 0, $iv);
                if ($decrypted === false) {
                    http_response_code(500);
                    echo "Erreur de déchiffrement.";
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
    require_once __DIR__ . '/../../core/helpers/FileHelper.php';

    $fichierModel = new FichierModel();
    $fichiers = $fichierModel->findByToken($token);

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

    require_once __DIR__ . '/../models/FileKeyModel.php';
    $keyModel = new FileKeyModel();

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
            $privateKey = file_get_contents($privateKeyPath);
            openssl_private_decrypt(base64_decode($keyData['encrypted_key']), $aesKey, $privateKey);
        }

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $aesKey, 0, $iv);

        if ($decrypted === false) {
            $zip->addFromString($cleanedPath . '_DECRYPTION_ERROR.txt', "Erreur lors du déchiffrement.");
            continue;
        }

        $zip->addFromString($cleanedPath, $decrypted);
    }

    

    $zip->close();

    // Envoyer le fichier ZIP
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="fichiers_' . date('Ymd_His') . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    readfile($zipFile);

    // Nettoyer
    unlink($zipFile);
    exit;
}

    
    
}
