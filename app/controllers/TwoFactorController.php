<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Contrôleur responsable de la gestion de l’authentification à deux facteurs (2FA),
 * avec délégation de toute logique SQL aux modèles (notamment UserModel).
 */
class TwoFactorController
{
    /**
     * Affiche le formulaire de choix de la méthode 2FA (email ou TOTP).
     */
    public function chooseMethod()
    {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        require __DIR__ . '/../views/dashboard/2fa_method.php';
    }

    /**
     * Active la méthode de 2FA choisie par l’utilisateur.
     */
    public function enableMethod()
    {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit("Méthode non autorisée");
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $method = $_POST['method'] ?? '';
        $csrf = $_POST['csrf_token'] ?? '';

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            $_SESSION['error'] = "Jeton CSRF invalide.";
            header('Location: /dashboard');
            exit;
        }

        require_once __DIR__ . '/../models/UserModel.php';

        if ($method === 'email') {
            UserModel::enableTwoFA($_SESSION['user_id'], 'email');
            $_SESSION['success'] = "2FA par e-mail activée.";
            header('Location: /dashboard');
            exit;
        } elseif ($method === 'totp') {
            header('Location: /dashboard/2fa-setup');
            exit;
        } else {
            $_SESSION['error'] = "Méthode inconnue.";
            header('Location: /dashboard');
            exit;
        }
    }

    /**
     * Génère et affiche un QR Code TOTP à scanner dans Google Authenticator.
     */
    public function totpSetup()
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        require_once __DIR__ . '/../../vendor/autoload.php';
        $tfa = new \RobThree\Auth\TwoFactorAuth('BognyTransfert');

        $secret = $tfa->createSecret();
        $_SESSION['2fa_temp_secret'] = $secret;

        $email = $_SESSION['user_email'];
        $qrCode = $tfa->getQRCodeImageAsDataUri("BognyTransfert:$email", $secret);

        require __DIR__ . '/../views/dashboard/2fa_totp_setup.php';
    }

    /**
     * Vérifie et active le TOTP après saisie correcte du code par l’utilisateur.
     */
    public function enableTOTP()
    {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Méthode non autorisée');
        }

        if (!isset($_SESSION['user_id'], $_SESSION['2fa_temp_secret'])) {
            $_SESSION['error'] = "Session invalide.";
            header('Location: /dashboard');
            exit;
        }

        $code = trim($_POST['code'] ?? '');
        $csrf = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            $_SESSION['error'] = "Jeton CSRF invalide.";
            header('Location: /dashboard');
            exit;
        }

        require_once __DIR__ . '/../../vendor/autoload.php';
        $tfa = new \RobThree\Auth\TwoFactorAuth('BognyTransfert');
        $secret = $_SESSION['2fa_temp_secret'];

        if (!$tfa->verifyCode($secret, $code)) {
            $_SESSION['error'] = "Code incorrect.";
            header('Location: /dashboard/2fa-setup');
            exit;
        }

        require_once __DIR__ . '/../models/UserModel.php';
        UserModel::enableTwoFATOTP($_SESSION['user_id'], $secret);
        unset($_SESSION['2fa_temp_secret']);

        $_SESSION['success'] = "2FA TOTP activée avec succès.";
        header('Location: /dashboard');
        exit;
    }

    public function disable2FA()
    {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        require_once __DIR__ . '/../models/UserModel.php';
        UserModel::disable2FA($_SESSION['user_id']);

        $_SESSION['success'] = "L’authentification 2FA a bien été désactivée.";
        header('Location: /dashboard');
        exit;
    }


    /**
     * Affiche le formulaire de vérification du code 2FA.
     */
    public function show2FAForm()
    {
        session_start();

        if (!isset($_SESSION['pending_2fa'])) {
            $_SESSION['error'] = "Session 2FA invalide.";
            header('Location: /login');
            exit;
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        require __DIR__ . '/../views/auth/2fa_form.php';
    }

    /**
     * Vérifie le code saisi (email ou TOTP) lors de l’authentification.
     */
    public function verify2FACode()
    {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Méthode non autorisée');
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            $_SESSION['error'] = "Jeton CSRF invalide.";
            header('Location: /verify/2fa-submit');
            exit;
        }

        $session = $_SESSION['pending_2fa'] ?? null;
        if (!$session || empty($session['user_id']) || empty($session['method'])) {
            $_SESSION['error'] = "Session 2FA invalide.";
            header('Location: /login');
            exit;
        }

        $code = trim($_POST['code'] ?? '');
        if ($code === '') {
            $_SESSION['error'] = "Code requis.";
            header('Location: /verify/2fa-submit');
            exit;
        }

        require_once __DIR__ . '/../models/UserModel.php';
        $user = UserModel::findById($session['user_id']);
        if (!$user) {
            $_SESSION['error'] = "Utilisateur introuvable.";
            header('Location: /login');
            exit;
        }

        if ($session['method'] === 'email') {
            if (!UserModel::validateEmail2FACode($user, $code)) {
                $_SESSION['error'] = "Code incorrect ou expiré.";
                header('Location: /verify/2fa-submit');
                exit;
            }
        } elseif ($session['method'] === 'totp') {
            $tfa = new \RobThree\Auth\TwoFactorAuth('BognyTransfert');
            if (!$tfa->verifyCode($user['twofa_totp_secret'], $code)) {
                $_SESSION['error'] = "Code TOTP invalide.";
                header('Location: /verify/2fa-submit');
                exit;
            }
        }

        // Authentification réussie
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        unset($_SESSION['pending_2fa']);
        require_once __DIR__ . '/../models/UserModel.php';
        UserModel::resetTwofaResendAttempts($user['id']);
        header('Location: /dashboard');
        exit;
    }
    public function resend2FACode()
    {
        session_start();
    
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Méthode non autorisée');
        }
    
        $csrf = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            $_SESSION['error'] = "Jeton CSRF invalide.";
            header('Location: /verify/2fa-submit');
            exit;
        }
    
        $session = $_SESSION['pending_2fa'] ?? null;
        if (!$session || empty($session['user_id']) || $session['method'] !== 'email') {
            $_SESSION['error'] = "Session 2FA invalide.";
            header('Location: /login');
            exit;
        }
    
        require_once __DIR__ . '/../models/UserModel.php';
        $user = UserModel::findById($session['user_id']);
        if (!$user) {
            $_SESSION['error'] = "Utilisateur introuvable.";
            header('Location: /login');
            exit;
        }
    
        $delays = [30, 30, 30, 120, 900, 3600, 259200]; // 30s x3, 2min, 15min, 1h, 3j
        $attempt = $user['twofa_resend_attempts'] ?? 0;
        $lastResend = $user['twofa_last_resend_time'] ? strtotime($user['twofa_last_resend_time']) : 0;
    
        $delayIndex = min($attempt, count($delays) - 1);
        $requiredDelay = $delays[$delayIndex];
    
        $now = time();
        $timeSinceLast = $now - $lastResend;
    
        if ($timeSinceLast < $requiredDelay) {
            $remaining = $requiredDelay - $timeSinceLast;
            $minutes = floor($remaining / 60);
            $seconds = $remaining % 60;
            $message = "Merci d’attendre encore ";
    
            if ($minutes > 0) {
                $message .= "$minutes minute" . ($minutes > 1 ? "s" : "") . " ";
            }
            if ($seconds > 0 || $minutes === 0) {
                $message .= "$seconds seconde" . ($seconds > 1 ? "s" : "");
            }
    
            $_SESSION['error'] = trim($message) . " avant de pouvoir renvoyer un nouveau code.";
            header('Location: /verify/2fa-submit');
            exit;
        }
    
        // Génère un nouveau code
        require_once __DIR__ . '/../models/SecurityModel.php';
        $code = SecurityModel::generateClear2FACode(6);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
        UserModel::updateTwofaEmailCode($user['id'], $code, $expires);
    
        // Envoi du mail
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
            $mail->Subject = '🔐 Nouveau code de vérification [' . $code . ']';
            $mail->Body = '<div style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; max-width: 480px; margin: auto; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);">
        <div style="text-align: center; margin-bottom: 24px;">
            <img src="' . $_ENV['BaseUrl'] . '/assets/img/BOGNY_logo_Gradient.svg" alt="Logo" style="height: 50px;">
        </div>
        <p style="font-size: 18px; color: #111827; margin-bottom: 16px; text-align: center;">
            Bonjour,
        </p>
        <p style="font-size: 16px; color: #374151; margin-bottom: 24px; text-align: center;">
            Voici votre nouveau code de connexion :
        </p>
        <div style="text-align: center; margin: 30px 0;">
            <span style="display: inline-block; background-color: #2563eb; color: #ffffff; font-size: 2.2rem; padding: 16px 32px; border-radius: 12px; letter-spacing: 6px; font-weight: bold; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);">
                ' . htmlspecialchars($code) . '
            </span>
        </div>
        <p style="font-size: 14px; color: #6b7280; text-align: center; margin-bottom: 24px;">
            ⏳ Ce code expirera dans <strong>10 minutes</strong>. Ne le partagez avec personne.
        </p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
        <p style="font-size: 12px; color: #9ca3af; text-align: center;">
            Vous n\'êtes pas à l\'origine de cette demande ? Ignorez simplement ce message.
        </p>
    </div>';
            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur PHPMailer (2FA resend) : " . $mail->ErrorInfo);
        }
    
        // Mise à jour tentatives
        UserModel::updateTwofaResendAttempts($user['id'], $attempt + 1, date('Y-m-d H:i:s'));
    
        $_SESSION['success'] = "Un nouveau code a été envoyé à votre adresse e-mail.";
        header('Location: /verify/2fa-submit');
        exit;
    }
    

}
