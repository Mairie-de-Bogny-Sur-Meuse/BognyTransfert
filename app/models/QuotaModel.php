<?php

class QuotaModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getMonthlyUsage(string $email): int
    {
        $stmt = $this->db->prepare("SELECT SUM(file_size) FROM uploads WHERE email = :email AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function getTotalUploadCount(string $email): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM uploads WHERE email = :email");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function isAllowedToUpload(string $email, int $fileSize, int $maxPerUpload, int $maxPerMonth): bool
    {
        if ($fileSize > $maxPerUpload) return false;
        $used = $this->getMonthlyUsage($email);
        return ($used + $fileSize) <= $maxPerMonth;
    }
}
