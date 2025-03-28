<?php
$logDir = __DIR__ . '/../../log';
$retentionDays = 3;
$now = time();

echo "[LOG-CRON] 🧹 Démarrage du nettoyage des logs...\n";

foreach (scandir($logDir) as $file) {
    if (in_array($file, ['.', '..'])) continue;

    $path = $logDir . '/' . $file;

    // Ne traite que les fichiers
    if (is_file($path)) {
        $modified = filemtime($path);
        $ageInDays = ($now - $modified) / 86400;

        if ($ageInDays > $retentionDays) {
            unlink($path);
            echo "[LOG-CRON] 🗑️ Supprimé : $file (âgé de " . round($ageInDays, 1) . " jours)\n";
        }
    }
}

echo "[LOG-CRON] ✅ Nettoyage terminé.\n";
