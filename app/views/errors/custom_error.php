<?php
require_once __DIR__ . '/../../models/SecurityModel.php';

SecurityModel::log('Erreur personnalisée', $_SESSION['email'] ?? null, [
    'code' => $code ?? 'non défini',
    'title' => $title ?? 'Erreur sans titre',
    'message' => $message ?? 'Erreur sans message',
    'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'inconnu',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'non défini'
]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Erreur <?= isset($code) ? htmlspecialchars($code) : 'inconnue'; ?> - BognyTransfert</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php include_once __DIR__ . '/../partials/header.php'; ?>

    <main class="flex-grow flex items-center justify-center p-4">
        <div class="bg-white shadow-xl rounded-2xl p-8 max-w-xl w-full text-center">
            <h1 class="text-4xl font-bold text-red-600 mb-4">
                Erreur <?= isset($code) ? htmlspecialchars($code) : 'inconnue'; ?>
            </h1>
            <p class="text-lg font-semibold text-gray-800 mb-2">
                <?= isset($title) ? htmlspecialchars($title) : "Une erreur est survenue" ?>
            </p>
            <p class="text-gray-600 mb-6">
                <?= isset($message) ? htmlspecialchars($message) : "Veuillez contacter un administrateur si le problème persiste." ?>
            </p>
            <a href="/" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">
                Retour à l'accueil
            </a>
        </div>
    </main>

    <?php include_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
