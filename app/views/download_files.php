<?php
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

    <ul class="divide-y divide-gray-200 mb-6">
      <?php foreach ($uploads as $file): ?>
        <li class="py-3 flex justify-between items-center">
          <div>
            <p class="font-semibold text-gray-700"><?= htmlspecialchars($file['file_name']) ?></p>
            <p class="text-sm text-gray-500"><?= round($file['file_size'] / 1024 / 1024, 2) ?> Mo</p>
          </div>
          <a href="/download/file?uuid=<?= $file['uuid'] ?>&file=<?= urlencode($file['file_name']) ?>" class="text-blue-600 hover:underline">Télécharger</a>
        </li>
      <?php endforeach; ?>
    </ul>

    <form action="/download/all" method="POST">
      <input type="hidden" name="uuid" value="<?= htmlspecialchars($uuid) ?>">
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
