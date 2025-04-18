<?php

class EmailTokenModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function createToken(string $email, string $token, string $expire): bool
    {
        $stmt = $this->db->prepare("INSERT INTO email_verification_tokens (email, token, expires_at) VALUES (:email, :token, :expire)");
        return $stmt->execute([
            ':email' => $email,
            ':token' => $token,
            ':expire' => $expire,
        ]);
    }

    public function validateToken(string $email, string $token): bool
    {
        $stmt = $this->db->prepare("SELECT * FROM email_verification_tokens 
            WHERE email = :email AND token = :token AND expires_at > NOW() 
            LIMIT 1");

        $stmt->execute([
            ':email' => $email,
            ':token' => $token,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markAsValidated(string $email, string $token): bool
    {
        $stmt = $this->db->prepare("UPDATE email_verification_tokens 
            SET validated = 1 
            WHERE email = :email AND token = :token");

        return $stmt->execute([
            ':email' => $email,
            ':token' => $token,
        ]);
    }

    public function purgeExpired(): int
    {
        $stmt = $this->db->prepare("DELETE FROM email_verification_tokens WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }
    public function getTokenByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM email_verification_tokens WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function getValidToken(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM email_verification_tokens WHERE email = :email AND expires_at > NOW() LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateToken(string $email, string $code, string $expires): bool
    {
        $stmt = $this->db->prepare("UPDATE email_verification_tokens SET token = :code, expires_at = :expires, validated = 0 WHERE email = :email");
        return $stmt->execute([
            'code' => $code,
            'expires' => $expires,
            'email' => $email
        ]);
    }

}
