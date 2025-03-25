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

        if (!preg_match('/@bognysurmeuse\.fr$/', $email)) {
            die("Email non autorisé.");
        }

        $tempPath = $config['temp_upload_path'] . $uuid;

        if (!mkdir($tempPath, 0755, true)) {
            die("Erreur : impossible de créer le dossier temporaire.");
        }

        $savedFiles = [];

        for ($i = 0; $i < count($files['name']); $i++) {
            $name = basename($files['name'][$i]);
            $tmp = $files['tmp_name'][$i];
            $destination = "$tempPath/$name";

            if (!move_uploaded_file($tmp, $destination)) {
                die("Erreur lors de l'enregistrement temporaire de $name.");
            }

            $savedFiles[] = $name;
        }

        $_SESSION['pending_upload'] = [
            'email' => $email,
            'password' => $password,
            'uuid' => $uuid,
            'files' => $savedFiles
        ];

        $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $stmt = $pdo->prepare("INSERT INTO email_verification_tokens (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $code, $expires]);

        $mail = new PHPMailer(true);

        try {
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'quoted-printable';
            $mail->isSMTP();
            $mail->Host = 'ssl0.ovh.net';
            $mail->SMTPAuth = true;
            $mail->Username = REMOVED
            $mail->Password = 'REMOVED';
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

        header("Location: /verify?email=" . urlencode($email));
        exit;
    }
}
