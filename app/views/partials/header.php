<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <!-- Logo / Titre -->
        <a href="/index.php" class="text-xl font-bold text-blue-700 hover:text-blue-900">
            BognyTransfert
        </a>

        <!-- Liens de navigation -->
        <nav class="flex space-x-4">
            <?php if (!isset($_SESSION['user'])): ?>
                <a href="/login.php" class="text-sm text-blue-600 hover:underline font-medium">Connexion</a>
            <?php else: ?>
                <a href="/dashboard.php" class="text-sm text-gray-700 hover:underline">Tableau de bord</a>
                <a href="/logout.php" class="text-sm text-red-600 hover:underline">Déconnexion</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
