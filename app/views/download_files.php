<?php
session_start();
// Variables transmises par le contrôleur
$uploads = $_uploads ?? [];
$token = $_token ?? '';
$uuid = $_uuid ?? '';
$expireIso = (new DateTime($uploads[0]['token_expire'], new DateTimeZone('Europe/Paris')))
->format(DateTime::ATOM); // ISO 8601 avec timezone
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Téléchargement des fichiers</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen px-4 py-10">
  <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-lg">
    
    <h1 class="text-2xl font-bold mb-2 text-gray-800 text-center">📁 Vos fichiers sont prêts</h1>

    <!-- 🔗 Lien de téléchargement + bouton copier -->
    <div class="text-center mb-6">
      <p class="text-sm text-gray-600 mb-1">Lien de partage :</p>
      <div class="flex items-center justify-center space-x-2">
        <input id="share-link" type="text" readonly value="<?= htmlspecialchars("https://dl.bognysurmeuse.fr/download/" . $token) ?>"
               class="w-full max-w-[80%] border border-gray-300 px-3 py-1 rounded text-sm text-gray-700 bg-gray-100">
        <button onclick="copyLink()" class="bg-indigo-600 text-white px-3 py-1 rounded text-sm hover:bg-indigo-700">Copier</button>
      </div>
    </div>

    <!-- ⏳ Compte à rebours -->
    <div class="text-center mb-4">
      <p class="text-sm text-gray-600">⏳ Ce lien expirera dans <span id="countdown" class="font-semibold text-red-600">...</span></p>
    </div>

   

    <?php
// 1. Extraire les dossiers uniques à partir des fichiers
$folders = [];

foreach ($uploads as $file) {
    $path = $file['file_name'];
    if (str_contains($path, '/')) {
        $parts = explode('/', $path);
        $folderPath = '';
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $folderPath .= $parts[$i] . '/';
            $folders[$folderPath] = true;
        }
    }
}
$folders = array_keys($folders);
sort($folders);
?>

<?php
$tree = [];

foreach ($uploads as $file) {
  $path = $file['file_name'];
  $parts = explode('/', $path);

  // Gestion du cas où le fichier est à la racine (pas de dossier)
  if (count($parts) > 1) {
      array_shift($parts); // Supprime le dossier racine (ex : Rapport Quotidien)
  }

  $current = &$tree;

  // Construction de l’arborescence des dossiers
  for ($i = 0; $i < count($parts) - 1; $i++) {
      if (!isset($current['children'][$parts[$i]])) {
          $current['children'][$parts[$i]] = [];
      }
      $current = &$current['children'][$parts[$i]];
  }

  // Ajout du fichier (même si le chemin est vide)
  if (!empty($parts)) {
      $current['files'][] = [
          'name' => end($parts),
          'full_path' => $path,
          'size' => $file['file_size'],
          'uuid' => $file['uuid']
      ];
  }
}


function renderTree($tree, $depth = 0) {
    if (!isset($tree['children']) && !isset($tree['files'])) return;

    // 📁 Sous-dossiers
    if (!empty($tree['children'])) {
        foreach ($tree['children'] as $folderName => $subtree) {
            echo '<li class="py-2 text-gray-600 font-semibold flex items-center" style="padding-left: ' . ($depth * 12) . 'px;">📂 ' . htmlspecialchars($folderName) . '</li>';
            renderTree($subtree, $depth + 1);
        }
    }

    // 📄 Fichiers
    if (!empty($tree['files'])) {
        foreach ($tree['files'] as $file) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $icon = match ($ext) {
                'pdf' => '📄',
                'doc', 'docx' => '📝',
                'xls', 'xlsx' => '📊',
                'ppt', 'pptx' => '📈',
                'zip', 'rar' => '🗜️',
                'jpg', 'jpeg', 'png', 'gif', 'webp' => '🖼️',
                'mp4', 'avi', 'mkv' => '🎞️',
                'mp3', 'wav' => '🎵',
                default => '📁',
            };
            echo '<li class="py-3 flex justify-between items-center" style="padding-left: ' . ($depth * 12) . 'px;">';
            echo '<div class="flex items-center">';
            echo '<span class="mr-2 text-xl">' . $icon . '</span>';
            echo '<div>';
            echo '<p class="font-semibold text-gray-700 break-all">' . htmlspecialchars($file['name']) . '</p>';
            echo '<p class="text-sm text-gray-500">' . round($file['size'] / 1024 / 1024, 2) . ' Mo</p>';
            echo '</div></div>';
            echo '<a href="/download/file?uuid=' . $file['uuid'] . '&file=' . urlencode($file['full_path']) . '" class="text-blue-600 hover:underline">Télécharger</a>';
            echo '</li>';
        }
    }
}
?><ul class="divide-y divide-gray-200 mb-6">
<?php renderTree($tree); ?>
</ul>



    <form action="/download/all" method="POST">
      <input type="hidden" name="uuid" value="<?= htmlspecialchars($uuid) ?>">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded font-semibold">
        📦 Télécharger tous les fichiers (.zip)
      </button>
    </form>
  </div>

  <!-- 📋 JS Copier le lien -->
  <script>
    function copyLink() {
      const input = document.getElementById('share-link');
      input.select();
      document.execCommand('copy');
      alert("Lien copié !");
    }
  </script>

  <!-- ⏱️ JS Compte à rebours -->
  <script>
  const expireAt = new Date("<?= $expireIso ?>").getTime();

  function updateCountdown() {
    const now = new Date().getTime();
    const distance = expireAt - now;

    if (distance <= 0) {
      document.getElementById("countdown").innerText = "expiré";
      return;
    }

    const d = Math.floor(distance / (1000 * 60 * 60 * 24));
    const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const s = Math.floor((distance % (1000 * 60)) / 1000);

    let text = '';
    if (d > 0) text += `${d}j `;
    text += `${h}h ${m}m ${s}s`;

    document.getElementById("countdown").innerText = text;
  }

  updateCountdown();
  setInterval(updateCountdown, 1000);
</script>

</body>
</html>
