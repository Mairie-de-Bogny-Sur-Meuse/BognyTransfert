<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Contrôleur dédié à la gestion des réinitialisations de mot de passe :
 * - Demande de lien de réinitialisation
 * - Envoi d’email avec token
 * - Validation du token et mise à jour du mot de passe
 */
class PasswordResetController
{
    /**
     * Affiche le formulaire de demande de réinitialisation ou traite l’envoi.
     */
    public function forgot()
    {
        session_start();

        // Affichage du formulaire (GET)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require __DIR__ . '/../views/auth/forgot.php';
            return;
        }

        // Soumission du formulaire (POST)
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $csrf = $_POST['csrf_token'] ?? '';

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            $_SESSION['error'] = "Jeton CSRF invalide.";
            header('Location: /forgot');
            exit;
        }

        require_once __DIR__ . '/../models/UserModel.php';
        $user = UserModel::findByEmail($email);

        if (!$user) {
            $_SESSION['error'] = "Aucun compte ne correspond à cette adresse.";
            header('Location: /forgot');
            exit;
        }

        // Génération du token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        UserModel::storeResetToken($user['id'], $token, $expires);

        // Envoi de l’e-mail
        $link = $_ENV['BaseUrl'] . '/reset?token=' . urlencode($token);
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $_ENV['EMAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['EMAIL_USER'];
            $mail->Password = $_ENV['EMAIL_PASSWORD'];
            $mail->SMTPSecure = 'ssl';
            $mail->Port = (int) $_ENV['EMAIL_PORT'];
            $mail->setFrom($_ENV['EMAIL_FROM'], $_ENV['EMAIL_FROM_NAME']);
            $mail->addAddress($user['email']);
            $mail->isHTML(true);
            $mail->Subject = '🔁 Réinitialisation de mot de passe';
            $mail->Body = "<p>Voici le lien pour réinitialiser votre mot de passe :</p>
                           <p><a href=\"$link\">$link</a></p>
                           <p>Ce lien est valide pendant 1 heure.</p>";
            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur envoi mail reset: " . $mail->ErrorInfo);
        }

        $_SESSION['success'] = "Un lien de réinitialisation a été envoyé.";
        header('Location: /login');
        exit;
    }

    /**
     * Affiche le formulaire de nouveau mot de passe ou effectue la mise à jour.
     */
    public function reset()
    {
        session_start();

        $token = $_GET['token'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require __DIR__ . '/../views/auth/reset.php';
            return;
        }

        // Traitement POST : modification du mot de passe
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        $csrf = $_POST['csrf_token'] ?? '';

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            $_SESSION['error'] = "Jeton CSRF invalide.";
            header("Location: /reset?token=$token");
            exit;
        }

        if (!$token || strlen($password) < 8 || $password !== $confirm) {
            $_SESSION['error'] = "Informations invalides ou mot de passe non confirmé.";
            header("Location: /reset?token=$token");
            exit;
        }

        require_once __DIR__ . '/../models/UserModel.php';
        $user = UserModel::findByResetToken($token);

        if (!$user || strtotime($user['reset_expires']) < time()) {
            $_SESSION['error'] = "Lien expiré ou invalide.";
            header('Location: /login');
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        UserModel::resetPassword($user['id'], $hash);

        $_SESSION['success'] = "Mot de passe mis à jour. Vous pouvez vous connecter.";
        header('Location: /login');
        exit;
    }
}
