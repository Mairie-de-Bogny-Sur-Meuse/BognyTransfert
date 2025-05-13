<?php

/**
 * Modèle FileKeyModel
 * 
 * Gère la persistance des clés de chiffrement (AES ou RSA) associées aux fichiers.
 * Stocke les clés, vecteurs IV, et niveaux de chiffrement dans la table `file_keys`.
 */
class FileKeyModel
{
    /**
     * Instance PDO de la base de données.
     * @var PDO
     */
    private $db;

    /**
     * Constructeur : initialise la connexion via le singleton Database.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ────────────────────────────────────────────────────────────────────────────────
    // CRÉATION / MISE À JOUR
    // ────────────────────────────────────────────────────────────────────────────────

    /**
     * Enregistre ou met à jour une clé de chiffrement pour un fichier spécifique.
     * Utilise la clause "ON DUPLICATE KEY UPDATE" pour éviter les doublons.
     *
     * @param array $data Données de la clé (uuid, file_name, encrypted_key, iv, encryption_level)
     * @return bool Résultat de l’exécution.
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

    // ────────────────────────────────────────────────────────────────────────────────
    // SUPPRESSION
    // ────────────────────────────────────────────────────────────────────────────────

    /**
     * Supprime une clé de chiffrement pour un fichier spécifique.
     *
     * @param string $uuid Identifiant unique du transfert.
     * @param string $fileName Nom du fichier concerné.
     * @return bool Succès de la suppression.
     */
    public function deleteByUuidAndFile(string $uuid, string $fileName): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM file_keys 
            WHERE uuid = :uuid AND file_name = :file_name
        ");
        return $stmt->execute([
            ':uuid' => $uuid,
            ':file_name' => $fileName
        ]);
    }

    /**
     * Supprime toutes les clés orphelines (celles qui ne correspondent plus à un fichier actif).
     * Compare avec la table `uploads`.
     *
     * @return bool Succès de l’exécution.
     */
    public function deleteOldKey(): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM file_keys 
            WHERE file_keys.file_name NOT IN (
                SELECT uploads.file_name FROM uploads
            )
        ");
        return $stmt->execute();
    }

    // ────────────────────────────────────────────────────────────────────────────────
    // LECTURE
    // ────────────────────────────────────────────────────────────────────────────────

    /**
     * Récupère les métadonnées de chiffrement pour un fichier (clé, IV, niveau).
     *
     * @param string $uuid Identifiant de l’upload.
     * @param string $fileName Nom du fichier.
     * @return array|null Tableau des données ou null si introuvable.
     */
    public function getKey(string $uuid, string $fileName): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM file_keys 
            WHERE uuid = :uuid AND file_name = :file_name 
            LIMIT 1
        ");
        $stmt->execute([
            'uuid' => $uuid,
            'file_name' => $fileName
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
