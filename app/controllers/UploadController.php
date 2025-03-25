<?php
session_start();

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
        $files = $_FILES['files'];

        // ✅ Vérification de l’adresse email professionnelle
        if (!preg_match('/@bognysurmeuse\\.fr$/', $email)) {
            die("Email non autorisé. Seules les adresses @bognysurmeuse.fr sont acceptées.");
        }

        // 📁 Création du dossier temporaire
        $tempPath = $config['temp_upload_path'] . $uuid . '/';
        if (!mkdir($tempPath, 0755, true)) {
            die("Erreur : impossible de créer le dossier temporaire.");
        }

        $savedFiles = []; // Stockera les chemins relatifs

        // 🔄 Traitement de chaque fichier (y compris chemins relatifs type dossier/fichier.jpg)
        for ($i = 0; $i < count($files['name']); $i++) {
            $relativePath = $files['name'][$i];   // Ex: dossier/photo.jpg
            $tmp = $files['tmp_name'][$i];

            $destination = $tempPath . $relativePath;
            $subDir = dirname($destination);

            // 📂 Crée les sous-dossiers si nécessaires
            if (!is_dir($subDir)) {
                mkdir($subDir, 0755, true);
            }

            // 📥 Déplace le fichier
            if (!move_uploaded_file($tmp, $destination)) {
                die("Erreur lors de l’enregistrement temporaire de $relativePath.");
            }

            $savedFiles[] = $relativePath; // Enregistre le chemin relatif
        }

        // 💾 Sauvegarde des infos dans la session pour traitement ultérieur
        $_SESSION['pending_upload'] = [
            'email' => $email,
            'password' => $password,
            'uuid' => $uuid,
            'files' => $savedFiles // juste les noms
        ];

        // 🔐 Génération du code de vérification
        $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $stmt = $pdo->prepare("INSERT INTO email_verification_tokens (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $code, $expires]);

        // ✉️ Envoi du mail AVEC ta configuration exacte
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
        //header("Location: /verify?email=" . urlencode($email));
        exit;
    }
}
