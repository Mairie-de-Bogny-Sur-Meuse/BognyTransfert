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

        header('Location: /dashboard');
        exit;
    }
}
