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
    <title>Erreur <?php echo isset($code) ? $code : ''; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white shadow-xl rounded-2xl p-8 max-w-xl w-full text-center">
        <h1 class="text-4xl font-bold text-red-600 mb-4">
            Erreur <?php echo isset($code) ? htmlspecialchars($code) : 'Inconnue'; ?>
        </h1>
        <p class="text-gray-700 mb-6 text-lg">
            <?php echo isset($title) ? htmlspecialchars($title) : "Une erreur est survenue"; ?>
        </p>
        <p class="text-gray-500 mb-8">
            <?php echo isset($message) ? htmlspecialchars($message) : "Veuillez contacter l'administrateur."; ?>
        </p>
        <a href="/" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">
            Retour à l'accueil
        </a>
        <?php include_once __DIR__ . '/../partials/footer.php'; ?>
    </div>
</body>

</html>
