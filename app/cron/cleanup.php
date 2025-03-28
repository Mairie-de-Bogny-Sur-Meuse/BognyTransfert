<?php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../models/FichierModel.php';
// Charger les variables d'environnement depuis .env
$envPath = '/var/www/dl.bognysurmeuse.fr/www/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        putenv(trim($line));
        [$key, $val] = explode('=', $line, 2);
        $_ENV[$key] = $val;
    }
}

// === CONFIG ===
$uploadPath = rtrim($_ENV['UPLOAD_PATH'], '/');
$tempPath   = rtrim($_ENV['TEMP_PATH'], '/');
$debug      = ($_ENV['DEBUG_LOG'] ?? false) === 'true';

$fichierModel = new FichierModel();
$deleted = 0;

// === FICHIERS EXPIRÉS (UPLOAD_PATH) : 30 jours ===
if ($debug) error_log("[CRON] 🔁 Vérification des fichiers expirés...");

$fichiers = $fichierModel->findAllExpired(); // méthode à implémenter dans FichierModel

foreach ($fichiers as $fichier) {
    $path = $fichier['file_path'];
    if (file_exists($path)) {
        unlink($path);
        if ($debug) error_log("[CRON] 🗑️ Fichier supprimé : $path");
        $deleted++;
    }
    $fichierModel->deleteById($fichier['id']);
}

// === Suppression des dossiers vides dans UPLOAD_PATH ===
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
                @rmdir($file->getRealPath());
            }
        }

        if (count(glob("$dir/*")) === 0) {
            @rmdir($dir);
            if ($debug) error_log("[CRON] 🧹 Dossier vide supprimé : $dir");
        }
    }
}

// === TEMP FILES (TEMP_PATH) : 15 minutes ===
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
