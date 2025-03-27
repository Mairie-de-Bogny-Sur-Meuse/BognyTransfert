<?php

class FileKeyModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Enregistre ou met à jour la clé de chiffrement pour un fichier.
     */
    public function storeKey(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO file_keys (uuid, file_name, encrypted_key, iv, encryption_level)
            VALUES (:uuid, :file_name, :encrypted_key, :iv, :encryption_level)
            ON DUPLICATE KEY UPDATE
                encrypted_key = :encrypted_key_2,
                iv = :iv_2,
                encryption_level = :encryption_level_2
        ");

        return $stmt->execute([
            'uuid' => $data['uuid'],
            'file_name' => $data['file_name'],
            'encrypted_key' => $data['encrypted_key'],
            'iv' => $data['iv'],
            'encryption_level' => $data['encryption_level'],
            'encrypted_key_2' => $data['encrypted_key'],
            'iv_2' => $data['iv'],
            'encryption_level_2' => $data['encryption_level'],
        ]);
    }


    /**
     * Récupère les informations de clé d'un fichier donné.
     */
    public function getKey(string $uuid, string $fileName): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM file_keys WHERE uuid = :uuid AND file_name = :file_name LIMIT 1");
        $stmt->execute([
            'uuid' => $uuid,
            'file_name' => $fileName
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function requiresUserKey(array $fichiers): bool
    {
        foreach ($fichiers as $fichier) {
            $key = $this->getKey($fichier['uuid'], $fichier['file_name']);
            if ($key && $key['encryption_level'] === 'maximum') {
                return true;
            }
        }
        return false;
    }

}
