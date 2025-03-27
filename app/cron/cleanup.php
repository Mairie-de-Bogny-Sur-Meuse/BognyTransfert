<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../models/FichierModel.php';

$uploadPath = rtrim($_ENV['UPLOAD_PATH'], '/');
$now = time();
$deleted = 0;

$fichierModel = new FichierModel();
$fichiers = $fichierModel->findAllExpired(); // à implémenter

foreach ($fichiers as $fichier) {
    $path = $fichier['file_path'];
    if (file_exists($path)) {
        unlink($path);
        $deleted++;
    }
    $fichierModel->deleteById($fichier['id']);
}

// Option : supprimer les répertoires vides (uuid)
foreach (scandir($uploadPath) as $uuid) {
    if (in_array($uuid, ['.', '..'])) continue;
    $dir = $uploadPath . '/' . $uuid;
    if (is_dir($dir) && count(glob("$dir/*")) === 0) {
        rmdir($dir);
    }
}

echo "[CRON] Nettoyage terminé. Fichiers supprimés : $deleted\n";
