<?php
if (!isset($_GET['token'])) {
    http_response_code(400);
    echo "Token manquant.";
    exit;
}

$token = htmlspecialchars($_GET['token']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Clé de déchiffrement requise - BognyTransfert</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
    <?php include_once __DIR__ . '/../partials/header.php'; ?>
    <div class="bg-white p-8 rounded-xl shadow-xl w-full max-w-md text-center">
        <h1 class="text-xl font-bold text-gray-800 mb-4">🔐 Clé de déchiffrement requise</h1>
        <p class="text-gray-600 mb-6">
            Pour télécharger ce fichier, vous devez entrer la clé de déchiffrement qui vous a été fournie.
        </p>
        <form action="/download/key-submit" method="post" class="space-y-4">
            <input type="hidden" name="token" value="<?= $token ?>">
            <input type="text" name="key" required
                   placeholder="Collez ici votre clé AES"
                   class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-400">

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">
                Accéder au téléchargement
            </button>
        </form>
    </div>
</body>
</html>
