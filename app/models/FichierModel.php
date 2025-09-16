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
    /**
     * Récupère les fichiers liés à un token et une adresse email (expéditeur).
     *
     * @param string $token Le token de téléchargement.
     * @param string $email L'adresse e-mail de l'expéditeur.
     * @return array Liste des fichiers associés.
     */
    public function findByTokenAndEmail(string $token, string $email): array
    {
        $sql = "SELECT * FROM uploads WHERE token = :token AND email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'token' => $token,
            'email' => $email,
        ]);
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

        // Récupère les UUIDs et noms de fichiers concernés avant suppression
        $stmt = $db->prepare("SELECT uuid, file_name FROM uploads WHERE token = :token AND email = :email");
        $stmt->execute([':token' => $token, ':email' => $email]);
        $fichiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Supprime les clés associées dans file_keys
        foreach ($fichiers as $fichier) {
            $deleteKeyStmt = $db->prepare("DELETE FROM file_keys WHERE uuid = :uuid AND file_name = :file_name");
            $deleteKeyStmt->execute([
                ':uuid' => $fichier['uuid'],
                ':file_name' => $fichier['file_name']
            ]);
        }

        // Supprime les fichiers dans uploads
        $deleteUploadStmt = $db->prepare("DELETE FROM uploads WHERE token = :token AND email = :email");
        return $deleteUploadStmt->execute([':token' => $token, ':email' => $email]);
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
    public function sumStorageForMonthByEmail(string $email): int
    {
        $sql = "SELECT SUM(file_size) FROM uploads WHERE email = :email AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return (int) $stmt->fetchColumn();
    }
    public static function updateFileName($uuid, $newName, $userEmail)
    {
    $db = Database::getInstance();
    $stmt = $db->prepare("UPDATE uploads SET file_name = :newName WHERE uuid = :uuid AND email = :email");
    return $stmt->execute([
        'newName' => $newName,
        'uuid' => $uuid,
        'email' => $userEmail
    ]);
    }
    public static function deleteFile($uuid, $userEmail)
    {
        $db = Database::getInstance();

        // Récupérer chemin
        $stmt = $db->prepare("SELECT file_path FROM uploads WHERE uuid = :uuid AND email_expediteur = :email");
        $stmt->execute(['uuid' => $uuid, 'email' => $userEmail]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($file && file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }

        // Suppression en base
        $stmt = $db->prepare("DELETE FROM uploads WHERE uuid = :uuid AND email_expediteur = :email");
        return $stmt->execute(['uuid' => $uuid, 'email' => $userEmail]);
    }
    public static function updateTransferPassword($token, $hashedPassword, $userEmail)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE uploads SET password_hash = :password WHERE token = :token AND email = :email");
        return $stmt->execute([
            'password' => $hashedPassword,
            'token' => $token,
            'email' => $userEmail
        ]);
    }

    public static function updateExpirationDate($token, $date, $userEmail)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE uploads SET token_expire = :date WHERE token = :token AND email = :email");
        return $stmt->execute([
            'date' => $date,
            'token' => $token,
            'email' => $userEmail
        ]);
    }

    public static function updateEncryptionLevelIfAllowed($token, $newLevel, $isAdmin, $userEmail)
    {
        $db = Database::getInstance();

        // Récupère tous les UUID du transfert
        $stmt = $db->prepare("SELECT uuid FROM uploads WHERE token = :token AND email = :email");
        $stmt->execute(['token' => $token, 'email' => $userEmail]);
        $uuids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!$uuids) return false;

        // Vérifie le niveau de chiffrement des fichiers associés dans file_keys
        $placeholders = implode(',', array_fill(0, count($uuids), '?'));
        $inStmt = $db->prepare("SELECT DISTINCT encryption_level FROM file_keys WHERE uuid IN ($placeholders)");
        $inStmt->execute($uuids);
        $levels = $inStmt->fetchAll(PDO::FETCH_COLUMN);

        $order = ['none' => 0, 'aes' => 1, 'aes_rsa' => 2, 'maximum' => 3];
        $currentMax = max(array_map(fn($lvl) => $order[$lvl] ?? -1, $levels));
        $newValue = $order[$newLevel] ?? -1;

        if ($newValue < 0 || $newValue < $currentMax) {
            if (!$isAdmin) return false;
        }

        // Mise à jour du niveau dans file_keys
        $updateStmt = $db->prepare("UPDATE file_keys SET encryption_level = :level WHERE uuid IN ($placeholders)");
        return $updateStmt->execute(array_merge(['level' => $newLevel], $uuids));
    }

    public static function getByToken($token, $userEmail)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT u.uuid, u.file_name, u.file_path ,u.token_expire, fk.encryption_level
            FROM uploads u
            LEFT JOIN file_keys fk ON u.uuid = fk.uuid AND u.file_name = fk.file_name
            WHERE u.token = :token AND u.email = :email
        ");
        $stmt->execute([
            'token' => $token,
            'email' => $userEmail
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    




}
