<?php

require_once __DIR__ . '/../core/Database.php';

$pdo = Database::connect();

$stmt = $pdo->prepare("
    SELECT DISTINCT uuid FROM uploads
    WHERE verified_at IS NULL
    AND verification_expires_at < NOW()
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

    // Supprimer entrées BDD
    $del = $pdo->prepare("DELETE FROM uploads WHERE uuid = :uuid");
    $del->execute(['uuid' => $uuid]);
}
