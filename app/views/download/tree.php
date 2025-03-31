<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Fichiers à télécharger</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    .folder-toggle svg {
      transition: transform 0.2s ease;
    }
    .folder-toggle.open svg {
      transform: rotate(90deg);
    }
  </style>
</head>
<body class="bg-gray-100 min-h-screen p-6 text-gray-800">
  <div class="bg-white p-8 rounded-2xl shadow-xl max-w-5xl mx-auto w-full">
    <h1 class="text-3xl font-bold text-blue-600 text-center mb-4">Téléchargement de fichiers</h1>
    <?php if (!empty($passwordHash) && !isset($_SESSION['access_granted'][$_GET['token']])): ?>
  <?php if (!empty($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-center" role="alert">
      <strong class="font-bold">Erreur :</strong> <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="post" class="max-w-md mx-auto bg-gray-50 p-6 rounded-xl shadow-md border border-gray-200 mb-8">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">🔒 Ce transfert est protégé par mot de passe</h2>
    <div class="mb-4">
      <label for="password" class="block text-sm font-medium text-gray-600 mb-1">Mot de passe</label>
      <input type="password" id="password" name="password" required placeholder="Veuillez entrer le mot de passe de se transfert"
             class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
    </div>
    <button type="submit"
            class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200">
      ✅ Valider
    </button>
  </form>

  <?php return; ?>
<?php endif; ?>

    <p id="countdown" class="text-center text-sm text-gray-500 mb-6"></p>

    <?php
    function getFileIcon(string $filename): string {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => 'file-text',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
            'doc', 'docx' => 'file-word',
            'xls', 'xlsx' => 'file-spreadsheet',
            'ppt', 'pptx' => 'file-presentation',
            'zip', 'rar', '7z', 'tar', 'gz' => 'file-archive',
            'mp3', 'wav', 'ogg' => 'music',
            'mp4', 'avi', 'mkv' => 'video',
            'txt', 'md' => 'file-text',
            default => 'file'
        };
    }

    function getFileIconColor(string $icon): string {
        return match ($icon) {
            'file-word' => 'text-blue-500',
            'file-spreadsheet' => 'text-green-500',
            'file-presentation' => 'text-orange-500',
            'file-text' => 'text-red-500',
            'image' => 'text-pink-500',
            'file-archive' => 'text-yellow-500',
            'music' => 'text-purple-500',
            'video' => 'text-indigo-500',
            default => 'text-gray-400'
        };
    }

    function renderTree(array $tree, int $depth = 0) {
        echo '<ul class="space-y-1">';
        foreach ($tree as $name => $content) {
            $isFolder = is_array($content) && !isset($content['path']);
            $margin = 'ml-' . min($depth * 4, 48); // max ml-48

            echo '<li class="' . $margin . '">';

            if ($isFolder) {
                // Ne pas afficher le dossier racine (UUID)
                if ($depth === 0) {
                    // Sauter l'affichage visuel du dossier racine (UUID)
                    renderTree($content, $depth + 1);
                    continue;
                }
                

                $folderId = uniqid('folder_');
                echo '<div class="flex items-center gap-2 folder-toggle cursor-pointer text-blue-600 font-semibold" onclick="toggleFolder(\'' . $folderId . '\', this)">';
                echo '<i data-lucide="chevron-right" class="w-4 h-4"></i>';
                echo '<i data-lucide="folder" class="w-4 h-4 text-blue-500"></i>';
                echo '<span>' . htmlspecialchars(rtrim($name, '/')) . '</span>';
                echo '</div>';
                echo '<div id="' . $folderId . '" class="pl-4 hidden">';
                renderTree($content, $depth + 1);
                echo '</div>';
            } else {
                $icon = getFileIcon($name);
                $color = getFileIconColor($icon);
                echo '<div class="flex items-center gap-2 text-sm text-gray-700">';
                echo '<i data-lucide="' . $icon . '" class="w-4 h-4 ' . $color . '"></i>';
                echo '<span>' . htmlspecialchars(basename($name)) . '</span>';
                echo '<span class="ml-auto text-xs text-gray-400">' . number_format($content['size'] / 1024, 2) . ' Ko</span>';
                echo '<a href="/download/file?token=' . urlencode($_GET['token']) . '&file=' . urlencode($content['name']) . '" class="text-blue-600 text-sm hover:underline ml-4">Télécharger</a>';
                echo '</div>';
            }

            echo '</li>';
        }
        echo '</ul>';
    }
    ?>
    

    <div class="overflow-x-auto border border-gray-200 rounded-xl bg-white p-4">
      <?php renderTree($arborescence); ?>
    </div>

    <div class="mt-10 text-center">
      <a href="/download/handleDownload?token=<?= htmlspecialchars($_GET['token']) ?>"
         class="inline-block bg-green-600 text-white px-6 py-3 rounded-full hover:bg-green-700 transition">
        📦 Télécharger tous les fichiers
      </a>
    </div>
    <div class="mt-10 text-center">
    <a href="/upload" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-full shadow hover:bg-blue-700 transition duration-200">
        📤 Transférer de nouveaux fichiers
    </a>
</div>
  </div>

  <script>
    lucide.createIcons();

    function toggleFolder(folderId, triggerEl) {
      const folderEl = document.getElementById(folderId);
      folderEl.classList.toggle('hidden');
      triggerEl.classList.toggle('open');
    }

    const countdownEl = document.getElementById("countdown");
    const expireAt = new Date("<?= htmlspecialchars($tokenExpire) ?>").getTime();

    function updateCountdown() {
      const now = new Date().getTime();
      const diff = expireAt - now;

      if (diff <= 0) {
        countdownEl.textContent = "⛔ Ce lien a expiré.";
        return;
      }

      const totalSeconds = Math.floor(diff / 1000);
      const days = Math.floor(totalSeconds / (3600 * 24));
      const hours = Math.floor((totalSeconds % (3600 * 24)) / 3600);
      const minutes = Math.floor((totalSeconds % 3600) / 60);
      const seconds = totalSeconds % 60;

      countdownEl.textContent = `⏳ Temps restant : ${days}j ${hours}h ${minutes}m ${seconds.toString().padStart(2, '0')}s`;

      setTimeout(updateCountdown, 1000);
    }

    updateCountdown();
  </script>
</body>
</html>
