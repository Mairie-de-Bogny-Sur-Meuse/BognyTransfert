<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>404 - Page introuvable</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <!-- Header fixe en haut -->
    <?php include_once __DIR__ . '/../partials/header.php'; ?>   

    <!-- Contenu principal centré -->
    <main class="flex-grow flex items-center justify-center p-4">
        <div class="bg-white shadow-xl rounded-2xl p-8 max-w-xl w-full text-center">
            <h1 class="text-4xl font-bold text-yellow-500 mb-4">404 - Page introuvable</h1>
            <p class="text-gray-700 mb-6 text-lg">La page que vous recherchez n'existe pas ou a été déplacée.</p>
            <a href="/" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">
                Retour à l'accueil
            </a>
        </div>
    </main>

    <!-- Footer collé en bas -->
    <?php include_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
