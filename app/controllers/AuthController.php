<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/SecurityModel.php';

/**
 * Contrôleur principal pour la gestion de l'authentification utilisateur :
 * - Affichage des formulaires (login / register)
 * - Traitement de l'inscription
 * - Traitement de la connexion
 * - Vérification de l'adresse e-mail
 * - Déconnexion
 */
class AuthController
{
    /**
     * Redirection par défaut vers la page de connexion.
     */
    public function index()
    {
        header('Location: /login');
        exit;
    }

    /**
     * Affiche le formulaire de connexion.
     */
    public function showLoginForm()
    {
        session_start();
        SecurityModel::generateCSRFToken();
        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Affiche le formulaire d'inscription.
     */
    public function showRegisterForm()
    {
        session_start();
        SecurityModel::generateCSRFToken();
        require __DIR__ . '/../views/auth/register.php';
    }

    /**
     * Traite l'inscription d'un nouvel utilisateur.
     */
    public function register()
    {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Méthode non autorisée');
        }

        if (!SecurityModel::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = "Jeton CSRF invalide.";
            header('Location: /register');
            exit;
        }

        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if (!$email || !str_ends_with($email, '@bognysurmeuse.fr')) {
            $_SESSION['error'] = "Seules les adresses @bognysurmeuse.fr sont autorisées.";
            header('Location: /register');
            exit;
        }

        if (strlen($password) < 8 || $password !== $confirm) {
            $_SESSION['error'] = "Mot de passe invalide ou non confirmé.";
            header('Location: /register');
            exit;
        }

        if (UserModel::findByEmail($email)) {
            $_SESSION['error'] = "Adresse email déjà utilisée.";
            header('Location: /register');
            exit;
        }

        $hash = SecurityModel::hashPassword($password);
        $verifyToken = SecurityModel::generateToken(64);
        $expires = date('Y-m-d H:i:s', strtotime('+1 day'));

        UserModel::create([
            'email' => $email,
            'password_hash' => $hash,
            'verification_token' => $verifyToken,
            'verification_expires' => $expires
        ]);

        // Préparer et envoyer l’e-mail de vérification
        $verificationLink = $_ENV['BaseUrl'] . '/verify?token=' . urlencode($verifyToken);

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
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = '✅ Vérification de votre adresse';
            $mail->Body = "<p>Merci de vous être inscrit sur BognyTransfert.</p>
                           <p>Veuillez cliquer sur ce lien pour valider votre compte :</p>
                           <a href='$verificationLink'>$verificationLink</a>";
            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur PHPMailer (register) : " . $mail->ErrorInfo);
        }

        $_SESSION['success'] = "Inscription réussie. Vérifiez votre email.";
        header('Location: /login');
        exit;
    }

    /**
     * Valide une adresse e-mail à l’aide d’un token unique.
     */
    public function verifyEmail()
    {
        session_start();

        $token = $_GET['token'] ?? '';
        if (!$token) {
            $_SESSION['error'] = "Lien de vérification invalide.";
            header('Location: /login');
            exit;
        }

        $verified = UserModel::verifyEmail($token);

        $_SESSION['success'] = $verified
            ? "Email vérifié avec succès. Vous pouvez vous connecter."
            : "Lien invalide ou expiré.";

        header('Location: /login');
        exit;
    }

    /**
     * Authentifie un utilisateur avec e-mail et mot de passe.
     * Lance la 2FA si activée.
     */
    public function login()
{
    session_start();

    // Vérifie que la méthode est POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée');
    }

    // Vérification CSRF
    if (!SecurityModel::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Jeton CSRF invalide.";
        header('Location: /login');
        exit;
    }

    // Récupère et filtre les identifiants
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Vérifie que les champs sont remplis
    if (!$email || !$password) {
        $_SESSION['error'] = "Veuillez remplir tous les champs.";
        header('Location: /login');
        exit;
    }

    // Recherche l'utilisateur
    $user = UserModel::findByEmail($email);

    // Vérifie les identifiants
    if (!$user || !SecurityModel::verifyPassword($password, $user['password_hash'])) {
        $_SESSION['error'] = "Identifiants incorrects.";
        header('Location: /login');
        exit;
    }

    // Vérifie si l'email est validé
    if (!$user['is_verified']) {
        $_SESSION['error'] = "Adresse email non vérifiée.";
        header('Location: /login');
        exit;
    }

    // Authentification à deux facteurs (2FA)
    if (!empty($user['twofa_enabled']) && $user['twofa_method'] === 'email') {
        // Génère un code sécurisé, clair et sans ambiguïté
        $code = SecurityModel::generateClear2FACode(6);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Met à jour le code en base
        UserModel::updateTwofaEmailCode($user['id'], $code, $expires);

        // Envoie le code par email
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
            $mail->setFrom($_ENV['EMAIL_FROM'], "[".$code."]".$_ENV['EMAIL_FROM_NAME']);
            $mail->addAddress($user['email']);
            $mail->isHTML(true);
            $mail->Subject = '🔐 Code de vérification ['.$code.']';
            $mail->Body = '<div style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <p style="font-size: 16px; color: #111827; margin-bottom: 12px;">
                                Bonjour,
                            </p>
                            <p style="font-size: 16px; color: #111827; margin-bottom: 8px;">
                                Voici votre code de connexion :
                            </p>

                            <div style="text-align: center; margin: 20px 0;">
                                <span style="display: inline-block; background-color: #2563eb; color: white; font-size: 2rem; padding: 12px 24px; border-radius: 8px; letter-spacing: 4px; font-weight: bold;">
                                    '.htmlspecialchars($code).'
                                </span>
                            </div>

                            <p style="font-size: 14px; color: #6b7280;">
                                ⏳ Ce code expirera dans <strong>10 minutes</strong>. Ne le partagez pas.
                            </p>
                        </div>
                        ';

            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur PHPMailer (2FA email) : " . $mail->ErrorInfo);
        }

        // Enregistre la session temporaire 2FA
        $_SESSION['pending_2fa'] = [
            'user_id' => $user['id'],
            'method' => 'email'
        ];

        header('Location: /verify/2fa-submit');
        exit;
    }

    // Si TOTP (Google Authenticator), redirige sans envoyer d'email
    if (!empty($user['twofa_enabled']) && $user['twofa_method'] === 'totp') {
        $_SESSION['pending_2fa'] = [
            'user_id' => $user['id'],
            'method' => 'totp'
        ];
        header('Location: /verify/2fa-submit');
        exit;
    }

    // Connexion directe (sans 2FA)
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];

    header('Location: /dashboard');
    exit;
}



    /**
     * Déconnecte un utilisateur.
     */
    public function logout()
    {
        session_start();
        session_unset();
        session_destroy();
        header('Location: /login');
        exit;
    }
}
