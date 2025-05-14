<?php

require_once __DIR__ . '/../../core/Database.php';

/**
 * Modèle UserModel
 *
 * Ce modèle gère toutes les interactions avec la table `users`,
 * incluant la création de comptes, la vérification d'email, la gestion du 2FA, etc.
 */
class UserModel
{
    /**
     * Crée un nouvel utilisateur avec les données fournies.
     *
     * @param array $data Clés attendues : email, password_hash, verification_token, verification_expires
     * @return int ID de l'utilisateur nouvellement créé
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO users (email, password_hash, verification_token, verification_expires)
            VALUES (:email, :password_hash, :verification_token, :verification_expires)
        ");
        $stmt->execute([
            ':email' => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':verification_token' => $data['verification_token'],
            ':verification_expires' => $data['verification_expires']
        ]);
        return $db->lastInsertId();
    }

    /**
     * Recherche un utilisateur par son adresse email.
     *
     * @param string $email
     * @return array|null
     */
    public static function findByEmail(string $email): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Recherche un utilisateur par son ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function findById(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Vérifie un utilisateur via son token de vérification d'email.
     *
     * @param string $token
     * @return bool
     */
    public static function verifyEmail(string $token): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM users WHERE verification_token = :token AND verification_expires >= NOW()");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $stmt = $db->prepare("
                UPDATE users
                SET is_verified = 1, verification_token = NULL, verification_expires = NULL
                WHERE id = :id
            ");
            return $stmt->execute([':id' => $user['id']]);
        }

        return false;
    }

    /**
     * Met à jour le code 2FA email et sa date d’expiration.
     *
     * @param int $userId
     * @param string $code
     * @param string $expires
     * @return bool
     */
    public static function updateTwofaEmailCode(int $userId, string $code, string $expires): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE users
            SET twofa_email_code = :code,
                twofa_email_expires = :expires
            WHERE id = :id
        ");
        return $stmt->execute([
            ':code' => $code,
            ':expires' => $expires,
            ':id' => $userId
        ]);
    }

    /**
     * Active la 2FA pour un utilisateur avec la méthode spécifiée (email ou totp).
     *
     * @param int $userId
     * @param string $method
     * @return bool
     */
    public static function enableTwoFA(int $userId, string $method): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE users SET twofa_enabled = 1, twofa_method = ? WHERE id = ?");
        return $stmt->execute([$method, $userId]);
    }

    /**
     * Active la 2FA TOTP et enregistre la clé secrète.
     *
     * @param int $userId
     * @param string $secret
     * @return bool
     */
    public static function enableTwoFATOTP(int $userId, string $secret): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE users
            SET twofa_enabled = 1,
                twofa_method = 'totp',
                twofa_totp_secret = :secret
            WHERE id = :id
        ");
        return $stmt->execute([
            ':secret' => $secret,
            ':id' => $userId
        ]);
    }

    /**
     * Désactive l'authentification à deux facteurs (2FA) pour un utilisateur donné.
     *
     * @param int $userId Identifiant de l'utilisateur.
     * @return bool True si la désactivation a réussi.
     */
    public static function disable2FA(int $userId): bool
    {
        $db = Database::getInstance();
        $sql = "UPDATE users SET twofa_enabled = 0, twofa_method = NULL WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute(['id' => $userId]);
    }


    /**
     * Valide un code 2FA email.
     *
     * @param array $user
     * @param string $code
     * @return bool
     */
    public static function validateEmail2FACode(array $user, string $code): bool
    {
        return $user['twofa_email_code'] === $code && strtotime($user['twofa_email_expires']) >= time();
    }
    /**
     * Enregistre le token de reset
     * 
     * @param int $user
     * @param string $token
     * @param string $expires
     * @return bool
     */
    public static function storeResetToken(int $user,string $token,string $expires) : bool{
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE users set verification_token = :token,
            verification_expires= :expires
            where id= :user
        ");
        return $stmt->execute([
            ':token' => $token,
            ':user' => $user,
            'expires' => $expires
        ]);
    }

    /**
     * recherche à partir du token de reset
     * 
     * @param int $token
     * @return array $user
     */
    public static function findByResetToken($token) : array{
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * from users where verification_token = :token 
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    
    /**
     * Mets à jour le mot de passe
     * 
     * @param int $userId
     * @param string $hash
     * @return bool True|False
     */
    public static function resetPassword($userId, $hash) : bool{
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE users SET password_hash = :hash where id = :userId
        ");
        return $stmt->execute([
            ':hash' => $hash,
            ':userId' => $userId,
        ]);
    }

    
}
