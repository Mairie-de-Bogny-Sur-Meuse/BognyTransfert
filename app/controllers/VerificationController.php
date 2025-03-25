<?php
session_start();

class VerificationController
{
    public function verify()
    {
        $pdo = Database::connect();
        $config = require __DIR__ . '/../../config/config.php';

        $email = $_POST['email'] ?? '';
        $code = $_POST['code'] ?? '';

        // ✅ Vérification du code
        $stmt = $pdo->prepare("SELECT * FROM email_verification_tokens WHERE email = ? AND token = ? AND expires_at > NOW()");
        $stmt->execute([$email, $code]);
        $token = $stmt->fetch();

        if (!$token || !isset($_SESSION['pending_upload'])) {
            $title = "Code invalide ou expiré.";
            $message = "❌ Code invalide ou expiré.";
            $code = 400;
            require 'app/views/errors/custom_error.php';
            return;
        }

        // ✅ Marquer le code comme utilisé
        $stmt = $pdo->prepare("UPDATE email_verification_tokens SET validated = 1 WHERE id = ?");
        $stmt->execute([$token['id']]);

        // 🧠 Récupérer les données de session
        $data = $_SESSION['pending_upload'];
        $uuid = $data['uuid'];
        $password = $data['password'];
        $fileNames = $data['files'];

        $tempPath = $config['temp_upload_path'] . $uuid . '/';
        $finalPath = $config['storage_path'] . $uuid . '/';

        // 📁 Créer le dossier final
        if (!mkdir($finalPath, 0755, true)) {
            $title = "Création du dossier de stockage";
            $message = "Erreur : impossible de créer le dossier d’upload final.";
            $code = 500;
            require 'app/views/errors/custom_error.php';
            return;
        }

        // 🔑 Générer un token de téléchargement unique
        $tokenDownload = bin2hex(random_bytes(32));
        $expire = date('Y-m-d H:i:s', strtotime('+' . $config['token_validity_days'] . ' days'));

        // 📂 Déplacement des fichiers et enregistrement en base
        foreach ($fileNames as $relativePath) {
            $filename = basename($relativePath);
        
            // 🚫 Sécurité : fichiers interdits
            $dangerousExtensions = ['php', 'sh', 'exe', 'bat', 'cmd', 'js', 'py', 'pl'];
            $ignoredFiles = ['Thumbs.db'];
        
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
            if (
                str_starts_with($filename, '.') ||
                in_array($filename, $ignoredFiles) ||
                in_array($ext, $dangerousExtensions)
            ) {
                continue; // fichier ignoré
            }
            
            $src = $tempPath . $relativePath;
            $dst = $finalPath . $relativePath;

            // Créer les sous-dossiers si nécessaire
            $dstDir = dirname($dst);
            if (!is_dir($dstDir)) {
                mkdir($dstDir, 0755, true);
            }

            if (!rename($src, $dst)) {
                $title = "Erreur lors du déplacement d'un fichier";
                $message = "Une erreur est survenue lors du transfert de $relativePath.";
                $code = 400;
                require 'app/views/errors/custom_error.php';
                return;
            }

            $size = filesize($dst);

            $stmt = $pdo->prepare("
                INSERT INTO uploads (uuid, email, file_name, file_path, file_size, password_hash, token, token_expire)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $uuid,
                $email,
                $relativePath,
                $dst,
                $size,
                $password ? password_hash($password, PASSWORD_DEFAULT) : null,
                $tokenDownload,
                $expire
            ]);
        }

            // 🧹 Nettoyage session et suppression récursive du dossier temporaire
    unset($_SESSION['pending_upload']);
    $this->deleteFolderRecursively($tempPath);

        // ✅ Redirection vers la confirmation
        $downloadUrl = "https://dl.bognysurmeuse.fr/download/$tokenDownload";
        require __DIR__ . '/../views/upload_success.php';
        exit;
    }
    private function deleteFolderRecursively($folder) {
        if (!is_dir($folder)) return;
    
        $items = scandir($folder);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $folder . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteFolderRecursively($path);
            } else {
                unlink($path);
            }
        }
        rmdir($folder);
    }
    
}
