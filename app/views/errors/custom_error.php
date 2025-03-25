<?php
// Variables attendues : $title, $message, $code (optionnel, par défaut 400)
$code = $code ?? 400;
http_response_code($code);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
  <div class="text-center max-w-lg px-6">
    <h1 class="text-6xl font-bold text-red-600"><?= $code ?></h1>
    <h2 class="text-2xl font-semibold mt-4"><?= htmlspecialchars($title) ?></h2>
    <p class="text-gray-600 mt-2"><?= htmlspecialchars($message) ?></p>
    <a href="/" class="inline-block mt-6 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
      Retour à l'accueil
    </a>
  </div>
</body>
</html>
