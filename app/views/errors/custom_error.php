<?php
// Variables attendues : $title, $message, $code (optionnel, par défaut 400)
$code = $code ?? 400;
http_response_code($code);

// Icônes SVG inline selon le code
$icon = match ($code) {
    403 => '<svg class="w-20 h-20 mx-auto mb-4 text-red-600" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none"/>
  <line x1="16" y1="16" x2="48" y2="48" stroke="currentColor" stroke-width="4"/>
</svg>
',
    404 => '<svg class="w-16 h-16 text-blue-500 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.75v14.5M4.75 12h14.5" /></svg>',
    500 => '<svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" /></svg>',
    default => '<svg class="w-16 h-16 text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" /></svg>'
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title) ?> (<?= $code ?>)</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center px-4">
  <div class="bg-white shadow-xl rounded-xl p-8 text-center max-w-xl w-full border-t-8 <?= $code === 403 ? 'border-orange-500' : ($code === 404 ? 'border-blue-500' : ($code === 500 ? 'border-red-500' : 'border-gray-400')) ?>">
    
    <?= $icon ?>

    <h1 class="text-5xl font-bold mb-2 text-gray-800"><?= $code ?></h1>
    <h2 class="text-xl font-semibold text-gray-700"><?= htmlspecialchars($title) ?></h2>
    <p class="text-gray-500 mt-2"><?= htmlspecialchars($message) ?></p>

    <div class="mt-6">
      <a href="/" class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
        Retour à l'accueil
      </a>
    </div>

    <?php if ($code === 403): ?>
      <p class="text-sm text-gray-400 mt-4">Si vous pensez que c’est une erreur, contactez votre administrateur système.</p>
    <?php endif; ?>
  </div>
</body>
</html>
