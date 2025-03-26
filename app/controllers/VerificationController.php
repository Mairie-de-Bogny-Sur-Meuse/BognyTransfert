<?php
session_start();
include_once 'Function.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class VerificationController
{
    public function verify()
    {
        $pdo = Database::connect();
        $config = require __DIR__ . '/../../config/config.php';

        $email = $_POST['email'] ?? '';
        $code = $_POST['code'] ?? '';

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $title = "Attaque CSRF détecter";
            $message = "❌ Attaque CSRF détecter.";
            $code = 403;
            require 'app/views/errors/custom_error.php';
            return;
        }
        

        // ✅ Vérification du code
        $stmt = $pdo->prepare("SELECT * FROM email_verification_tokens WHERE email = ? AND token = ? AND expires_at > NOW()");
        $stmt->execute([SecureSql($email), SecureSql($code)]);
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
        $stmt->execute([SecureSql($token['id'])]);

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
                SecureSql($uuid),
                SecureSql($email),
                SecureSql($relativePath),
                SecureSql($dst),
                SecureSql($size),
                $password ? SecureSql(password_hash($password, PASSWORD_DEFAULT)) : null,
                SecureSql($tokenDownload),
                SecureSql($expire)
            ]);
        }

            // 🧹 Nettoyage session et suppression récursive du dossier temporaire
    unset($_SESSION['pending_upload']);
    $this->deleteFolderRecursively($tempPath);

        // ✅ Redirection vers la confirmation
        $this->sendConfirmationMail($email, $tokenDownload);
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
    

private function sendConfirmationMail($destinataire, $uuid)
{
    $config = require __DIR__ . '/../../config/config.php';
    $downloadLink = "https://dl.bognysurmeuse.fr/download/$uuid";
    $dateExpiration = (new DateTime('+30 days'))->format('d/m/Y');
    $logoUrl = 'https://www.bognysurmeuse.fr/wp-content/uploads/2022/03/cropped-logo-site.png';

    $html = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
      <div style="text-align: center; margin-bottom: 20px;">
        <img src="' . $logoUrl . '" alt="Logo Bogny-sur-Meuse" style="height: 60px;">
        <h2 style="color: #111827; margin-top: 10px;">Votre envoi a bien été pris en compte ✅</h2>
      </div>
      <p style="color: #374151;"><strong>Lien de téléchargement :</strong></p>
      <div style="background-color: #e0f2fe; padding: 10px; border-radius: 5px; word-wrap: break-word;">
        <a href="' . $downloadLink . '" style="color: #2563eb;">' . $downloadLink . '</a>
      </div>
      <p style="color: #374151; margin-top: 20px;"><strong>Date d\'expiration :</strong> ' . $dateExpiration . '</p>
      <hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">
      <p style="font-size: 12px; color: #6b7280; text-align: center;">
        Cet e-mail a été généré automatiquement depuis <strong>BognyTransfert</strong><br>
        Un service de la Ville de <a href="https://www.bognysurmeuse.fr" style="color:#2563eb;">Bogny-sur-Meuse</a>
      </p>
    </div>';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'quoted-printable';
        $mail->Host = 'ssl0.ovh.net';
        $mail->SMTPAuth = true;
        $mail->Username = $config['Email_user'];
        $mail->Password = $config['Email_password'];
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('no-reply@bognysurmeuse.fr', 'BognyTransfert');
        $mail->addAddress($destinataire);

        $mail->isHTML(true);
        $mail->Subject = 'Votre envoi sur BognyTransfert';
        $mail->Body    = $html;

        $mail->send();
    } catch (Exception $e) {
        error_log("Erreur envoi mail : " . $mail->ErrorInfo);
    }
}

    
}
