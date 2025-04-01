<?php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../models/FichierModel.php';
require_once __DIR__ . '/../models/FileKeyModel.php';

// Charger les variables d'environnement
$envPath = '/var/www/dl.bognysurmeuse.fr/www/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[$key] = $val;
        putenv(trim($line));
    }
}

// === CONFIGURATION ===
$uploadPath = rtrim($_ENV['UPLOAD_PATH'], '/');
$tempPath   = rtrim($_ENV['TEMP_PATH'], '/');
$debug      = ($_ENV['DEBUG_LOG'] ?? false) === 'true';

$fichierModel = new FichierModel();
$fileKeyModel = new FileKeyModel();
$deleted = 0;

// === 1. Nettoyage des fichiers expirés (UPLOAD_PATH > 30 jours) ===
if ($debug) error_log("[CRON] 🔁 Vérification des fichiers expirés...");

$fichiers = $fichierModel->findAllExpired(); // doit retourner fichiers où token_expire < NOW()

foreach ($fichiers as $fichier) {
    $path = $fichier['file_path'];

    if (file_exists($path)) {
        unlink($path);
        if ($debug) error_log("[CRON] 🗑️ Fichier supprimé : $path");
        $deleted++;
    }

    // Suppression en base
    $fichierModel->deleteById($fichier['id']);
    $fileKeyModel->deleteByUuidAndFile($fichier['uuid'], $fichier['file_name']);
    $fileKeyModel->deleteOldKey();
}

// Suppression des dossiers UUID vides
foreach (scandir($uploadPath) as $uuid) {
    if (in_array($uuid, ['.', '..'])) continue;
    $dir = "$uploadPath/$uuid";

    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            }
        }

        if (count(glob("$dir/*")) === 0) {
            @rmdir($dir);
            if ($debug) error_log("[CRON] 🧹 Dossier vide supprimé : $dir");
        }
    }
}

// === 2. Nettoyage des fichiers temporaires (TEMP_PATH > 15 minutes) ===
if ($debug) error_log("[CRON] ⏳ Nettoyage des fichiers temporaires...");

foreach (scandir($tempPath) as $uuid) {
    if (in_array($uuid, ['.', '..'])) continue;
    $dir = "$tempPath/$uuid";

    if (is_dir($dir) && filemtime($dir) < (time() - 15 * 60)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $path = $file->getRealPath();
            if ($file->isDir()) {
                @rmdir($path);
            } else {
                unlink($path);
                if ($debug) error_log("[CRON] ❌ Fichier temporaire supprimé : $path");
            }
        }

        @rmdir($dir);
        if ($debug) error_log("[CRON] 🗑️ Dossier temporaire supprimé : $dir");
    }
}

echo "[CRON] Nettoyage terminé. Fichiers supprimés : $deleted\n";
