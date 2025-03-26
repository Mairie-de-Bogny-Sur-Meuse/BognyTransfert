<?php
session_start();
include_once 'Function.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UploadController
{
    public function handleUpload()
    {
        $pdo = Database::connect();
        $config = require __DIR__ . '/../../config/config.php';

        $uuid = bin2hex(random_bytes(16));
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // ✅ Vérification de l’adresse email professionnelle
        if (!preg_match('/@bognysurmeuse\\.fr$/', $email)) {
            $title = "Email non autorisé";
            $message = "Email non autorisé. Seules les adresses @bognysurmeuse.fr sont acceptées.";
            $code = 403;
            require 'app/views/errors/custom_error.php';
            return;
        }

        // 📁 Création du dossier temporaire
        $tempPath = $config['temp_upload_path'] . $uuid . '/';
        if (!mkdir($tempPath, 0755, true)) {
            $title = "Création de dossier temporaire Imposible";
            $message = "Erreur : impossible de créer le dossier temporaire.";
            $code = 500;
            require 'app/views/errors/custom_error.php';
            return;

        }

        $savedFiles = [];

        // 📁 Fichiers simples
        if (!empty($_FILES['files_flat'])) {
            $flat = $_FILES['files_flat'];
            for ($i = 0; $i < count($flat['name']); $i++) {
                $name = basename($flat['name'][$i]);
                $tmp = $flat['tmp_name'][$i];
                if (empty($name) || empty($tmp) || !is_uploaded_file($tmp)) continue;
        
                $destination = $tempPath . $name;
                if (!move_uploaded_file($tmp, $destination)) {
                    $title = "Erreur lors du déplacement d'un fichier";
                    $message = "Une erreur est survenu lors du déplacement de $name";
                    $code = 500;
                    require 'app/views/errors/custom_error.php';
                    return;
                }
        
                $savedFiles[] = $name;
            }
        }
        
        // 📂 Fichiers avec structure de dossier
        if (!empty($_FILES['files_tree'])) {
            $files = $_FILES['files_tree'];
            
            for ($i = 0; $i < count($files['name']); $i++) {
                $relativePath = $files['full_path'][$i] ?? $files['name'][$i];
                $relativePath = ltrim($relativePath, "/\\");
                $tmp = $files['tmp_name'][$i];
            
                // Sécurité : ignorer les fichiers vides, masqués ou temporaires
                $filename = basename($relativePath);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $ignored = ['.DS_Store', 'Thumbs.db', '.gitkeep'];
                $dangerous = ['php', 'sh', 'exe', 'bat', 'cmd'];
            
                if (
                    empty($relativePath) ||
                    empty($tmp) ||
                    !is_uploaded_file($tmp) ||
                    str_starts_with($filename, '.') ||
                    in_array($filename, $ignored) ||
                    in_array($ext, $dangerous)
                ) {
                    continue;
                }
            
                $destination = $tempPath . $relativePath;
                $subDir = dirname($destination);
                if (!is_dir($subDir)) mkdir($subDir, 0755, true);
            
                if (!move_uploaded_file($tmp, $destination)) {
                    error_log("❌ Erreur déplacement de $relativePath");
                }
            
                $savedFiles[] = $relativePath;
            }
            
        }
        
        // Stockage temporaire
        $_SESSION['pending_upload'] = [
            'uuid' => $uuid,
            'email' => $email,
            'password' => $password,
            'files' => $savedFiles
        ];
        

        // 🔐 Génération du code de vérification
        $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $stmt = $pdo->prepare("INSERT INTO email_verification_tokens (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([SecureSql($email), SecureSql($code), SecureSql($expires)]);

        // ✉️ Envoi du mail AVEC ta configuration exacte
        error_log("[DEBUG] Tentative d'envoi du code de vérification à $email");
        $mail = new PHPMailer(true);
        try {
            $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'quoted-printable';
            $mail->isSMTP();
            $mail->Host = 'ssl0.ovh.net';
            $mail->SMTPAuth = true;
            $mail->Username = $config['Email_user'];
            $mail->Password = $config['Email_password'];
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('no-reply@bognysurmeuse.fr', 'BognyTransfert');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Code de vérification pour votre envoi';
            $mail->Body = "<p>Bonjour,<br>Voici votre code de vérification : <strong>$code</strong><br>Ce code est valable 15 minutes.</p>";

            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi de mail : " . $mail->ErrorInfo);
        }

        // 🔁 Redirection vers la page de saisie du code
        header("Location: /verify?email=" . urlencode($email));
        exit;
    }
}
