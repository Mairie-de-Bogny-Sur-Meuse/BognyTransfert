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

        $stmt = $pdo->prepare("SELECT * FROM email_verification_tokens WHERE email = ? AND token = ? AND expires_at > NOW()");
        $stmt->execute([$email, $code]);
        $token = $stmt->fetch();

        if ($token && isset($_SESSION['pending_upload'])) {
            $stmt = $pdo->prepare("UPDATE email_verification_tokens SET validated = 1 WHERE id = ?");
            $stmt->execute([$token['id']]);

            $data = $_SESSION['pending_upload'];
            $uuid = $data['uuid'];
            $email = $data['email'];
            $password = $data['password'];
            $fileNames = $data['files'];

            $tempPath = $config['temp_upload_path'] . $uuid;
            $finalPath = $config['storage_path'] . $uuid;

            if (!is_dir($finalPath)) {
                mkdir($finalPath, 0755, true);
            }

            $tokenDownload = bin2hex(random_bytes(32));
            $expire = date('Y-m-d H:i:s', strtotime('+' . $config['token_validity_days'] . ' days'));

            foreach ($fileNames as $name) {
                $src = "$tempPath/$name";
                $dst = "$finalPath/$name";
                $size = filesize($src);

                if (!rename($src, $dst)) {
                    die("Erreur lors du déplacement de $name.");
                }

                $stmt = $pdo->prepare("
                    INSERT INTO uploads (uuid, email, file_name, file_path, file_size, password_hash, token, token_expire)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $uuid,
                    $email,
                    $name,
                    $dst,
                    $size,
                    $password ? password_hash($password, PASSWORD_DEFAULT) : null,
                    $tokenDownload,
                    $expire
                ]);
            }

            unset($_SESSION['pending_upload']);

            // Supprimer le dossier temporaire
            array_map('unlink', glob("$tempPath/*"));
            rmdir($tempPath);

            $downloadUrl = "https://dl.bognysurmeuse.fr/download/$tokenDownload";
            require __DIR__ . '/../views/upload_success.php';
            exit;
        } else {
            echo "❌ Code invalide ou expiré.";
        }
    }
}
