<?php

/**
 * Modèle EmailTokenModel
 * 
 * Gère les opérations relatives aux jetons de vérification par email,
 * notamment la création, la validation, la mise à jour et la suppression automatique
 * des jetons expirés dans la table `email_verification_tokens`.
 */
class EmailTokenModel
{
    /**
     * Instance PDO de la base de données.
     * @var PDO
     */
    private $db;

    /**
     * Constructeur : initialise la connexion à la base de données via le singleton Database.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ────────────────────────────────────────────────────────────────────────────────
    // CRÉATION ET MISE À JOUR
    // ────────────────────────────────────────────────────────────────────────────────

    /**
     * Crée un nouveau jeton de vérification pour une adresse email donnée.
     *
     * @param string $email Adresse email cible.
     * @param string $token Code de vérification généré.
     * @param string $expire Date d'expiration (format SQL DATETIME).
     * @return bool Succès ou échec de l'exécution.
     */
    public function createToken(string $email, string $token, string $expire): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO email_verification_tokens (email, token, expires_at) 
            VALUES (:email, :token, :expire)
        ");
        return $stmt->execute([
            ':email' => $email,
            ':token' => $token,
            ':expire' => $expire,
        ]);
    }

    /**
     * Met à jour un jeton existant (par email), en régénérant le code et l’expiration.
     * Réinitialise également l’état de validation.
     *
     * @param string $email Adresse email.
     * @param string $code Nouveau code.
     * @param string $expires Nouvelle date d’expiration.
     * @return bool Succès ou échec de la requête.
     */
    public function updateToken(string $email, string $code, string $expires): bool
    {
        $stmt = $this->db->prepare("
            UPDATE email_verification_tokens 
            SET token = :code, expires_at = :expires, validated = 0 
            WHERE email = :email
        ");
        return $stmt->execute([
            'code' => $code,
            'expires' => $expires,
            'email' => $email
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────────────
    // VALIDATION
    // ────────────────────────────────────────────────────────────────────────────────

    /**
     * Vérifie si un jeton est valide (existe, associé à l’email et non expiré).
     *
     * @param string $email Adresse email associée.
     * @param string $token Jeton à valider.
     * @return bool True si valide, sinon false.
     */
    public function validateToken(string $email, string $token): bool
    {
        $stmt = $this->db->prepare("
            SELECT * FROM email_verification_tokens 
            WHERE email = :email AND token = :token AND expires_at > NOW() 
            LIMIT 1
        ");
        $stmt->execute([
            ':email' => $email,
            ':token' => $token,
        ]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Marque un jeton comme validé en base (champ `validated` mis à 1).
     *
     * @param string $email Adresse email concernée.
     * @param string $token Jeton concerné.
     * @return bool Succès ou échec.
     */
    public function markAsValidated(string $email, string $token): bool
    {
        $stmt = $this->db->prepare("
            UPDATE email_verification_tokens 
            SET validated = 1 
            WHERE email = :email AND token = :token
        ");
        return $stmt->execute([
            ':email' => $email,
            ':token' => $token,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────────────
    // RÉCUPÉRATION
    // ────────────────────────────────────────────────────────────────────────────────

    /**
     * Récupère un jeton par adresse email, sans vérifier sa validité temporelle.
     *
     * @param string $email Email cible.
     * @return array|null Tableau du jeton ou null si introuvable.
     */
    public function getTokenByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM email_verification_tokens 
            WHERE email = :email 
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Récupère un jeton encore valide (non expiré) pour une adresse email.
     *
     * @param string $email Adresse email.
     * @return array|null Jeton valide ou null.
     */
    public function getValidToken(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM email_verification_tokens 
            WHERE email = :email AND expires_at > NOW() 
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ────────────────────────────────────────────────────────────────────────────────
    // NETTOYAGE AUTOMATIQUE
    // ────────────────────────────────────────────────────────────────────────────────

    /**
     * Supprime tous les jetons expirés de la base.
     *
     * @return int Nombre de lignes supprimées.
     */
    public function purgeExpired(): int
    {
        $stmt = $this->db->prepare("
            DELETE FROM email_verification_tokens 
            WHERE expires_at < NOW()
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }
}
