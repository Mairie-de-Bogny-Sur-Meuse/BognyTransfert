<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - BognyTransfert</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .fade-in {
            animation: fadeIn 0.8s ease-out both;
        }
        .slide-up {
            animation: slideUp 1s ease-out both;
        }
        @keyframes fadeIn {
            from { opacity: 0 }
            to { opacity: 1 }
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen flex flex-col">

    <!-- Header -->
    <?php include_once __DIR__ . '/../partials/header.php'; ?>

    <!-- Contenu principal -->
    <main class="flex-grow flex items-center justify-center p-6">
        <div class="bg-white p-10 rounded-3xl shadow-2xl w-full max-w-3xl text-center relative fade-in slide-up">
            
            <!-- Logo -->
            <div class="mb-6">
                <img src="<?= rtrim($_ENV['BaseUrl'], '/') ?>/assets/img/BOGNY_logo_Gradient.svg"
                     alt="Logo Bogny" class="h-16 mx-auto float">
            </div>

            <!-- Titre -->
            <h1 class="text-4xl font-extrabold text-blue-700 mb-4">Bienvenue sur BognyTransfert</h1>

            <!-- Sous-titre -->
            <p class="text-lg text-gray-600 mb-2">Transférez vos fichiers en toute simplicité et sécurité 📁</p>
            <p class="text-sm text-red-600 font-semibold mb-8">
                Réservé aux agents de <strong>Bogny-sur-Meuse</strong> utilisant une adresse <strong>@bognysurmeuse.fr</strong>
            </p>

            <!-- Boutons -->
            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="/upload"
                   class="bg-blue-600 text-white px-8 py-3 rounded-full text-lg font-medium shadow hover:bg-blue-700 hover:scale-105 transition transform duration-300">
                    📤 Envoyer des fichiers
                </a>
            </div>

            <!-- Illustration décorative -->
            <div class="absolute -bottom-10 right-10 hidden md:block opacity-10 float">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-32 w-32 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 15a4 4 0 004 4h10a4 4 0 004-4M7 10V4m0 0l-2 2m2-2l2 2m10 6v6m0 0l2-2m-2 2l-2-2"/>
                </svg>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include_once __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>
