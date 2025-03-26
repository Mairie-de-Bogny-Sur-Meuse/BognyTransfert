<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Téléchargement de fichier</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
  <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-3xl">
    <h1 class="text-2xl font-bold text-blue-600 mb-6 text-center">Fichiers disponibles au téléchargement</h1>

    <div class="space-y-4">
      <?php if (!empty($fichiers)): ?>
        <?php foreach ($fichiers as $fichier):
          $isFolder = str_ends_with($fichier['name'], '/');
          $ext = pathinfo($fichier['name'], PATHINFO_EXTENSION);
          $icon = match (strtolower($ext)) {
            'pdf' => 'file-text',
            'jpg', 'jpeg', 'png', 'gif' => 'image',
            'doc', 'docx' => 'file-word',
            'xls', 'xlsx' => 'file-spreadsheet',
            'zip', 'rar' => 'file-archive',
            default => $isFolder ? 'folder' : 'file'
          };
        ?>
          <div class="flex items-center justify-between bg-gray-50 px-4 py-3 rounded-xl border border-gray-200">
            <div class="flex items-center gap-3">
              <i data-lucide="<?= $icon ?>" class="w-5 h-5 text-gray-500"></i>
              <span class="text-sm font-medium text-gray-700">
                <?= htmlspecialchars($fichier['name']) ?>
              </span>
            </div>
            <div class="flex items-center gap-3 text-sm text-gray-500">
              <?php if (!$isFolder): ?>
                <span><?= number_format($fichier['size'] / 1024, 2); ?> Ko</span>
                <a href="/download/file?token=<?= urlencode($_GET['token'] ?? '') ?>&file=<?= urlencode($fichier['name']) ?>"
                   class="text-blue-600 hover:underline">Télécharger</a>
              <?php else: ?>
                <span>Dossier</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-center text-gray-500">Aucun fichier disponible pour ce lien.</p>
      <?php endif; ?>
    </div>

    <div class="mt-8 text-center">
      <a href="/download/handleDownload?token=<?= htmlspecialchars($_GET['token'] ?? '') ?>"
         class="inline-block bg-green-600 text-white px-6 py-3 rounded-full hover:bg-green-700 transition">
        Télécharger l'ensemble
      </a>
    </div>
  </div>
  <script>
    lucide.createIcons();
  </script>
</body>
</html>
