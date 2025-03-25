<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

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

        // 🔐 Vérification du domaine autorisé
        if (!preg_match('/@bognysurmeuse\.fr$/', $email)) {
            die("Email non autorisé. Seul @bognysurmeuse.fr est accepté.");
        }

        // 🧠 Enregistrer temporairement les infos dans la session
        $_SESSION['pending_upload'] = [
            'email' => $email,
            'password' => $password,
            'files' => $files,
            'uuid' => $uuid
        ];

        // 🔐 Génération d’un code à usage unique
        $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // 🔐 Stocker le code dans la base
        $stmt = $pdo->prepare("INSERT INTO email_verification_tokens (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $code, $expires]);

        // 📧 Envoi du code par mail
        $mail = new PHPMailer(true);

        try {
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'quoted-printable';
            $mail->isSMTP();
            $mail->Host       = 'ssl0.ovh.net';
            $mail->SMTPAuth   = true;
            $mail->Username   = REMOVED
            $mail->Password   = 'REMOVED';
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = 465;

            $mail->setFrom('no-reply@bognysurmeuse.fr', 'BognyTransfert');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Code de vérification pour votre envoi';
            $mail->Body = "<p>Bonjour,</p><p>Voici votre code de vérification : <strong>$code</strong></p><p>Ce code est valable 15 minutes.</p>";

            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi de code : " . $mail->ErrorInfo);
        }

        // 🔁 Rediriger vers la page de vérification
        header("Location: /verify?email=" . urlencode($email));
        exit;
    }
}
