<?php
require_once __DIR__ . '/../core/Database.php';

$pdo = Database::connect();

// Récupère les UUID des fichiers validés depuis plus de 30 jours
$stmt = $pdo->prepare("
    SELECT DISTINCT uuid FROM uploads
    WHERE verified_at IS NOT NULL
    AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute();
$uuids = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($uuids as $uuid) {
    // Supprimer fichiers
    $uploadDir = __DIR__ . '/../storage/uploads/' . $uuid;
    if (is_dir($uploadDir)) {
        array_map('unlink', glob("$uploadDir/*"));
        rmdir($uploadDir);
    }

    // Supprimer enregistrements BDD
    $del = $pdo->prepare("DELETE FROM uploads WHERE uuid = :uuid");
    $del->execute(['uuid' => $uuid]);
}
