<?php
require_once 'app/models/SecurityModel.php';
SecurityModel::log('Accès refusé', $_SESSION['email'] ?? null, [
    'code' => 403,
    'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>403 - Accès interdit</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white shadow-xl rounded-2xl p-8 max-w-xl w-full text-center">
        <h1 class="text-4xl font-bold text-red-600 mb-4">403 - Accès interdit</h1>
        <p class="text-gray-700 mb-6 text-lg">Vous n'avez pas l'autorisation d'accéder à cette ressource.</p>
        <a href="/" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">
            Retour à l'accueil
        </a>
    </div>
</body>
</html>
