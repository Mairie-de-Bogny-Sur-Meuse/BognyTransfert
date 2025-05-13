<?php

/**
 * SecurityModel
 *
 * Fournit des méthodes utilitaires liées à la sécurité :
 * - CSRF (génération et validation)
 * - Hachage de mots de passe
 * - Génération de tokens
 * - Journalisation des actions sensibles
 * - Chiffrement et déchiffrement de fichiers
 */
class SecurityModel
{
    // ───────────────────────────────────────────────────────────────
    // GESTION DES TOKENS CSRF
    // ───────────────────────────────────────────────────────────────

    /**
     * Génère et stocke un token CSRF dans la session si absent.
     *
     * @return string Le token CSRF généré ou existant.
     */
    public static function generateCSRFToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Vérifie la validité d’un token CSRF fourni.
     *
     * @param string $token Le token à valider.
     * @return bool True si le token correspond à celui en session.
     */
    public static function verifyCSRFToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // ───────────────────────────────────────────────────────────────
    // HACHAGE ET VÉRIFICATION DE MOTS DE PASSE
    // ───────────────────────────────────────────────────────────────

    /**
     * Hache un mot de passe en utilisant l'algorithme par défaut.
     *
     * @param string $password Le mot de passe en clair.
     * @return string Le hash généré.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Vérifie la correspondance entre un mot de passe et son hash.
     *
     * @param string $password Le mot de passe en clair.
     * @param string $hash Le hash à vérifier.
     * @return bool True si le mot de passe est valide.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    // ───────────────────────────────────────────────────────────────
    // GÉNÉRATION DE TOKEN SÉCURISÉ
    // ───────────────────────────────────────────────────────────────

    /**
     * Génère un token cryptographiquement sécurisé.
     *
     * @param int $length Longueur souhaitée du token (par défaut : 64).
     * @return string Le token hexadécimal.
     */
    public static function generateToken(int $length = 64): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Génère un code alphanumérique sécurisé de $length caractères,
     * en évitant les lettres/chiffres ambigus (ex : I, l, 1, O, 0).
     */
    public static function generateClear2FACode(int $length = 6): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Sans I, l, 1, 0, O
        $code = '';

        while (strlen($code) < $length) {
            $char = $characters[random_int(0, strlen($characters) - 1)];

            // Évite 2 caractères visuellement proches à la suite
            if (strlen($code) > 0 && abs(ord(substr($code, -1)) - ord($char)) <= 1) {
                continue;
            }

            $code .= $char;
        }

        return $code;
    }



    // ───────────────────────────────────────────────────────────────
    // LOG DE SÉCURITÉ
    // ───────────────────────────────────────────────────────────────

    /**
     * Enregistre une action de sécurité dans un fichier de log.
     *
     * @param string $action Description de l’action.
     * @param string|null $email Adresse e-mail associée, si applicable.
     * @param array $context Données additionnelles (optionnelles).
     */
    public static function log(string $action, ?string $email = null, array $context = []): void
    {
        $entry = date('[Y-m-d H:i:s]') . " ACTION: $action";
        if ($email) $entry .= " | EMAIL: $email";
        if (!empty($context)) $entry .= " | CONTEXT: " . json_encode($context);
        file_put_contents(__DIR__ . '/../../storage/logs/security.log', $entry . PHP_EOL, FILE_APPEND);
    }

    // ───────────────────────────────────────────────────────────────
    // CHIFFREMENT / DÉCHIFFREMENT DE FICHIERS
    // ───────────────────────────────────────────────────────────────

    /**
     * Chiffre un fichier sur disque avec AES-256-CBC.
     *
     * @param string $filePath Le chemin du fichier à chiffrer.
     * @param string $key La clé de chiffrement (en clair).
     * @return bool True si le chiffrement s’est bien déroulé.
     */
    public static function encryptFile(string $filePath, string $key): bool
    {
        if (!file_exists($filePath)) return false;

        $data = file_get_contents($filePath);
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $cipherText = openssl_encrypt($data, 'aes-256-cbc', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);

        if ($cipherText === false) return false;

        $encryptedData = base64_encode($iv . $cipherText);
        return file_put_contents($filePath, $encryptedData) !== false;
    }

    /**
     * Déchiffre un fichier précédemment chiffré avec AES-256-CBC.
     *
     * @param string $filePath Le chemin du fichier chiffré.
     * @param string $key La clé de déchiffrement (en clair).
     * @return bool True si le déchiffrement s’est bien déroulé.
     */
    public static function decryptFile(string $filePath, string $key): bool
    {
        if (!file_exists($filePath)) return false;

        $data = base64_decode(file_get_contents($filePath));
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $cipherText = substr($data, $ivLength);

        $plainText = openssl_decrypt($cipherText, 'aes-256-cbc', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);

        if ($plainText === false) return false;

        return file_put_contents($filePath, $plainText) !== false;
    }
}
