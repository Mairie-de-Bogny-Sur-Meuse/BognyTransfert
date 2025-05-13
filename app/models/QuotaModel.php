<?php

/**
 * Modèle QuotaModel
 * 
 * Gère les quotas d'upload par utilisateur.
 * Permet de contrôler la quantité de données envoyées par mois et par transfert.
 */
class QuotaModel
{
    /**
     * Instance de la base de données (PDO)
     * @var PDO
     */
    private $db;

    /**
     * Initialise la connexion via le singleton Database.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ────────────────────────────────────────────────────────────────────────────────
    // MÉTHODES DE MESURE D’UTILISATION
    // ────────────────────────────────────────────────────────────────────────────────

    /**
     * Retourne la taille totale (en octets) des fichiers envoyés ce mois-ci par un utilisateur.
     *
     * @param string $email Adresse email de l’expéditeur.
     * @return int Nombre total d’octets utilisés dans le mois en cours.
     */
    public function getMonthlyUsage(string $email): int
    {
        $stmt = $this->db->prepare("
            SELECT SUM(file_size) 
            FROM uploads 
            WHERE email = :email 
              AND MONTH(created_at) = MONTH(NOW()) 
              AND YEAR(created_at) = YEAR(NOW())
        ");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Retourne le nombre total d'envois effectués par un utilisateur, tout temps confondu.
     *
     * @param string $email Adresse email de l’expéditeur.
     * @return int Nombre total d’envois.
     */
    public function getTotalUploadCount(string $email): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM uploads WHERE email = :email");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    // ────────────────────────────────────────────────────────────────────────────────
    // VALIDATION DES LIMITES
    // ────────────────────────────────────────────────────────────────────────────────

    /**
     * Vérifie si un utilisateur est autorisé à uploader un fichier donné selon les limites.
     *
     * @param string $email Adresse de l'utilisateur.
     * @param int $fileSize Taille du fichier à uploader.
     * @param int $maxPerUpload Limite maximale autorisée par envoi.
     * @param int $maxPerMonth Limite maximale autorisée par mois.
     * @return bool True si l'utilisateur est autorisé à uploader, false sinon.
     */
    public function isAllowedToUpload(string $email, int $fileSize, int $maxPerUpload, int $maxPerMonth): bool
    {
        // Refus immédiat si le fichier dépasse la taille maximale autorisée par envoi
        if ($fileSize > $maxPerUpload) return false;

        // Vérifie si le total déjà envoyé + nouveau dépasse la limite mensuelle
        $used = $this->getMonthlyUsage($email);
        return ($used + $fileSize) <= $maxPerMonth;
    }
}
