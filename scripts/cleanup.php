<?php

require_once __DIR__ . '/../core/Autoloader.php';
Autoloader::register();
require_once __DIR__ . '/../core/Env.php';
Env::load();

use DateTime;

\$deletedFiles = 0;
\$deletedRecords = 0;

\$fichierModel = new FichierModel();

// Supprimer les enregistrements expirés de la base de données
\$deletedRecords = \$fichierModel->deleteExpired();

// Supprimer les fichiers physiques correspondants (optionnel : nettoyage temp)
\$tmpDir = $_ENV['TEMP_PATH'] ?? 'storage/tmp/';

function deleteOldFolders(string \$dir, int \$maxAgeDays = 30): int
{
    \$count = 0;
    \$now = time();

    if (!is_dir(\$dir)) return 0;

    \$folders = array_filter(glob(\$dir . '*'), 'is_dir');
    foreach (\$folders as \$folder) {
        \$lastModified = filemtime(\$folder);
        \$ageDays = (\$now - \$lastModified) / 86400;
        if (\$ageDays > \$maxAgeDays) {
            system('rm -rf ' . escapeshellarg(\$folder));
            \$count++;
        }
    }
    return \$count;
}

\$deletedFiles = deleteOldFolders(\$tmpDir);

// Log du nettoyage
SecurityModel::log('Nettoyage planifié', null, [
    'dossiers_temp_supprimes' => \$deletedFiles,
    'enregistrements_bdd_supprimes' => \$deletedRecords,
    'executed_at' => (new DateTime())->format('Y-m-d H:i:s')
]);

// Affichage CLI
echo "Cleanup terminé : \$deletedFiles dossiers supprimés, \$deletedRecords entrées supprimées.\n";
