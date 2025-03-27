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
        $fileRequested = $_GET['file'];
    
        require_once __DIR__ . '/../models/FichierModel.php';
        require_once __DIR__ . '/../models/FileKeyModel.php';
        require_once __DIR__ . '/../../core/helpers/FileHelper.php';
    
        $model = new FichierModel();
        $keyModel = new FileKeyModel();
        $fichiers = $model->findByToken($token);
    
        foreach ($fichiers as $fichier) {
            $relativePath = FileHelper::getRelativePath($fichier['file_path']);
    
            if (urldecode($relativePath) === urldecode($fileRequested)) {
                $realPath = $fichier['file_path'];
    
                if (!file_exists($realPath)) {
                    http_response_code(404);
                    echo "Fichier introuvable.";
                    return;
                }
    
                // Vérification du chiffrement
                $keyData = $keyModel->getKey($fichier['uuid'], $fichier['file_name']);
                $encryptionLevel = $keyData['encryption_level'] ?? 'none';
    
                if ($encryptionLevel === 'none' || !$keyData) {
                    // ✅ Pas chiffré, retour direct
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . basename($realPath) . '"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($realPath));
                    readfile($realPath);
                    exit;
                }
    
                // 🔐 Lecture contenu chiffré
                $encryptedContent = file_get_contents($realPath);
                $ivLength = openssl_cipher_iv_length('aes-256-cbc');
                $iv = substr($encryptedContent, 0, $ivLength);
                $ciphertext = substr($encryptedContent, $ivLength);
                $aesKey = null;
    
                if ($encryptionLevel === 'aes') {
                    $masterKey = $_ENV['MASTER_ENCRYPTION_KEY'];
                    $aesKey = openssl_decrypt($keyData['encrypted_key'], 'aes-256-cbc', $masterKey, 0, $iv);
                } elseif ($encryptionLevel === 'aes_rsa') {
                    $privateKey = file_get_contents($_ENV['RSA_PRIVATE_KEY_PATH']);
                    if (!$privateKey) {
                        http_response_code(500);
                        echo "Clé privée manquante.";
                        return;
                    }
                    openssl_private_decrypt(base64_decode($keyData['encrypted_key']), $aesKey, $privateKey);
                } elseif ($encryptionLevel === 'maximum') {
                    $aesKey = base64_decode($_GET['k'] ?? '');
                    if (!$aesKey) {
                        http_response_code(400);
                        echo "Clé de déchiffrement requise pour ce fichier (paramètre ?k=...).";
                        return;
                    }
                }
    
                if (!$aesKey) {
                    http_response_code(500);
                    echo "Erreur : clé AES introuvable.";
                    return;
                }
    
                $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $aesKey, 0, $iv);
                if ($decrypted === false) {
                    http_response_code(500);
                    echo "Erreur lors du déchiffrement du fichier.";
                    return;
                }
    
                // ✅ Envoi du fichier déchiffré
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($realPath) . '"');
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

        if (!$fichiers || strtotime($fichiers[0]['token_expire']) < time()) {
            $title = "Lien expiré ou invalide";
            $message = "Ce lien n'est plus valide ou a expiré.";
            $code = 410;
            require_once __DIR__ . '/../views/errors/custom_error.php';
            return;
        }

        if ($keyModel->requiresUserKey($fichiers)) {
            if (empty($_GET['k'])) {
                require_once __DIR__ . '/../views/download/key_required.php';
                return;
            }
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

            // === Pas de chiffrement
            if ($encryptionLevel === 'none' || !$keyData) {
                $zip->addFile($realPath, $cleanedPath);
                continue;
            }

            // === Lecture du contenu chiffré
            $encryptedContent = file_get_contents($realPath);
            $ivLength = openssl_cipher_iv_length('aes-256-cbc');
            $iv = substr($encryptedContent, 0, $ivLength);
            $ciphertext = substr($encryptedContent, $ivLength);
            $aesKey = null;

            // === Récupération de la clé AES selon le niveau
            if ($encryptionLevel === 'aes') {
                $masterKey = $_ENV['MASTER_ENCRYPTION_KEY'];
                $aesKey = openssl_decrypt($keyData['encrypted_key'], 'aes-256-cbc', $masterKey, 0, $iv);
            }

            elseif ($encryptionLevel === 'aes_rsa') {
                $privateKeyPath = $_ENV['RSA_PRIVATE_KEY_PATH'];
                $privateKey = file_get_contents($privateKeyPath);
                if (!$privateKey) {
                    $zip->addFromString($cleanedPath . '_ERROR.txt', "Clé privée RSA manquante.");
                    continue;
                }
                openssl_private_decrypt(base64_decode($keyData['encrypted_key']), $aesKey, $privateKey);
            }

            elseif ($encryptionLevel === 'maximum') {
                $aesKey = base64_decode($_GET['k'] ?? '');
                if (!$aesKey) {
                    $zip->addFromString($cleanedPath . '_CLE_REQUISE.txt', "Fichier protégé. Fournissez une clé via ?k=...");
                    continue;
                }
            }

            if (!$aesKey) {
                $zip->addFromString($cleanedPath . '_CLE_INVALIDE.txt', "Clé AES manquante ou invalide.");
                continue;
            }

            // === Déchiffrement AES
            $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $aesKey, 0, $iv);

            if ($decrypted === false) {
                $zip->addFromString($cleanedPath . '_DECHIFFREMENT_ECHOUE.txt', "Le fichier n’a pas pu être déchiffré.");
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

    public function submitKey()
    {
        if (!isset($_POST['token'], $_POST['key'])) {
            http_response_code(400);
            echo "Paramètres manquants.";
            return;
        }

        $token = urlencode($_POST['token']);
        $key = urlencode($_POST['key']);

        header("Location: /download?token=$token&k=$key");
        exit;
    }


    
    
}
