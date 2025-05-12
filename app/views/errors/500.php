<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>500 - Erreur serveur</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <!-- Header -->
    <?php include_once __DIR__ . '/../partials/header.php'; ?>

    <!-- Contenu principal -->
    <main class="flex-grow flex items-center justify-center p-4">
        <div class="bg-white shadow-xl rounded-2xl p-8 max-w-xl w-full text-center">
            <h1 class="text-4xl font-bold text-red-700 mb-4">500 - Erreur interne du serveur</h1>
            <p class="text-gray-700 mb-6 text-lg">
                Une erreur inattendue s'est produite.<br>
                Veuillez réessayer plus tard ou contacter un administrateur.
            </p>
            <a href="/" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">
                Retour à l'accueil
            </a>
        </div>
    </main>

    <!-- Footer -->
    <?php include_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
