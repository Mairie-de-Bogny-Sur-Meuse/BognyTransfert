<?php

class FichierModel
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("INSERT INTO uploads (
            uuid, email, file_name, file_path, file_size, password_hash, token, token_expire, created_at
        ) VALUES (
            :uuid, :email, :file_name, :file_path, :file_size, :password_hash, :token, :token_expire, NOW()
        )");

        return $stmt->execute([
            ':uuid'          => $data['uuid'],
            ':email'         => $data['email'],
            ':file_name'     => $data['file_name'],
            ':file_path'     => $data['file_path'],
            ':file_size'     => intval($data['file_size']),
            ':password_hash' => $data['password_hash'] ?? null,
            ':token'         => $data['token'],
            ':token_expire'  => $data['token_expire'],
        ]);
    }
    public function findByToken(string $token): array
    {
        $stmt = $this->db->prepare("SELECT * FROM uploads WHERE token = :token");
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // ✅ récupère tous les fichiers liés
    }
    public function findByUuid(string $token): array
    {
        $stmt = $this->db->prepare("SELECT * FROM uploads WHERE uuid = :uuid");
        $stmt->bindValue(':uuid', $token, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function findByEmail(string $email): array
    {
        $stmt = $this->db->prepare("SELECT * FROM uploads WHERE email = :email ORDER BY created_at DESC");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function findAllExpired(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM uploads WHERE token_expire < NOW()");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function deleteExpired(): int
    {
        $stmt = $this->db->prepare("DELETE FROM uploads WHERE token_expire IS NOT NULL AND token_expire < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }
    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM uploads WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    public function countByEmailThisMonth(string $email): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM uploads WHERE email = :email AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
    public function getPasswordHash($uuid, $fileName)
    {
        $stmt = $this->db->prepare("SELECT password_hash FROM uploads WHERE uuid = ? AND file_name = ?");
        $stmt->execute([$uuid, $fileName]);
        return $stmt->fetchColumn();
    }
    public function getPasswordHashByToken($token)
    {
        $stmt = $this->db->prepare("SELECT password_hash FROM uploads WHERE token = ?");
        $stmt->execute([$token]);
        return $stmt->fetchColumn();
    }
    public static function deleteByTokenAndEmail(string $token, string $email): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM uploads WHERE token = :token AND email = :email");
        return $stmt->execute([':token' => $token, ':email' => $email]);
    }
    public static function findTransfersByEmail(string $email): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT token, file_name, file_size, token_expire, created_at
                              FROM uploads 
                              WHERE email = ?
                              ORDER BY created_at DESC");
        $stmt->execute([$email]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
}
