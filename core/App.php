<?php

// Autoloading des classes (contrôleurs, modèles, etc.)
require_once __DIR__ . '/Autoloader.php';
Autoloader::register();

// Chargement des composants fondamentaux
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Env.php';

// Chargement des variables d’environnement (.env)
Env::load();

/**
 * Classe principale de l’application BognyTransfert.
 * Elle initialise le routeur et enregistre toutes les routes disponibles.
 */
class App
{
    /**
     * @var Router Instance du routeur principal
     */
    private Router $router;

    /**
     * Constructeur de l’application : instancie le routeur et enregistre les routes.
     */
    public function __construct()
    {
        $this->router = new Router();
        $this->registerRoutes();
    }

    /**
     * Enregistre toutes les routes de l’application :
     * - Pages publiques
     * - Authentification
     * - Téléversement
     * - Téléchargement
     * - Vérification email et 2FA
     * - Espace utilisateur (dashboard)
     * - Réinitialisation de mot de passe
     */
    private function registerRoutes(): void
    {
        // ------------------------
        // 🏠 Pages publiques
        // ------------------------
        $this->router->add('/', 'HomeController', 'index');
        $this->router->add('/home', 'HomeController', 'index');
        $this->router->add('/mentions-rgpd', 'MentionController', 'rgpd');
        $this->router->add('/cgu', 'MentionController', 'cgu');

        // ------------------------
        // 📤 Téléversement (public)
        // ------------------------
        $this->router->add('/upload', 'UploadController', 'index'); // Formulaire
        $this->router->add('/upload/handleUpload', 'UploadController', 'handleUpload'); // Traitement POST
        $this->router->add('/upload/confirmation', 'UploadController', 'confirmation'); // Page de confirmation


        // ------------------------
        // 📥 Téléchargement de fichiers
        // ------------------------
        $this->router->add('/download', 'DownloadController', 'index'); // Page d’un transfert
        $this->router->add('/download/file', 'DownloadController', 'file'); // Téléchargement direct
        $this->router->add('/download/handleDownload', 'DownloadController', 'handleDownload'); // ZIP

        // ------------------------
        // ✅ Vérification de compte et d’email (après inscription)
        // ------------------------
        $this->router->add('/verify', 'AuthController', 'verifyEmail'); // GET ?token=...

        // ------------------------
        // 🛡️ Authentification à deux facteurs (2FA)
        // ------------------------
        $this->router->add('/verify/2fa-submit', 'TwoFactorController', 'show2FAForm');     // Affiche le formulaire (GET)
        $this->router->add('/verify/2fa-check', 'TwoFactorController', 'verify2FACode');    // Vérifie le code saisi (POST)
        $this->router->add('/dashboard/2fa-choice', 'TwoFactorController', 'chooseMethod'); // Choix méthode
        $this->router->add('/dashboard/2fa-method', 'TwoFactorController', 'enableMethod'); // Activation méthode
        $this->router->add('/dashboard/2fa-setup', 'TwoFactorController', 'totpSetup');     // QR code pour TOTP
        $this->router->add('/dashboard/2fa-enable', 'TwoFactorController', 'enableTOTP');   // Enregistrement du code TOTP

        // ------------------------
        // 🔐 Authentification de l’utilisateur
        // ------------------------
        $this->router->add('/register', 'AuthController', 'showRegisterForm'); // GET : formulaire
        $this->router->add('/register/submit', 'AuthController', 'register');  // POST : traitement
        $this->router->add('/login', 'AuthController', 'showLoginForm');       // GET
        $this->router->add('/login/submit', 'AuthController', 'login');        // POST
        $this->router->add('/logout', 'AuthController', 'logout');
        $this->router->add('/upload/confirm-session', 'VerifyController', 'confirmUploadFromSession'); //Téléversement (Privée)


        // ------------------------
        // 👤 Espace personnel (dashboard)
        // ------------------------
        $this->router->add('/dashboard', 'DashboardController', 'index');
        $this->router->add('/dashboard/delete-transfer', 'DashboardController', 'deleteTransfer');

        // ------------------------
        // 🔁 Réinitialisation de mot de passe
        // ------------------------
        $this->router->add('/forgot', 'PasswordResetController', 'forgot'); // Demande du lien
        $this->router->add('/reset', 'PasswordResetController', 'reset');   // Réinitialisation via token
    }

    /**
     * Lance le dispatching de l’URL courante via le routeur.
     */
    public function run(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->router->dispatch($uri);
    }
}
