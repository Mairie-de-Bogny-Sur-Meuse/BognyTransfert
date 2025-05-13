<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un compte - BognyTransfert</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">
<?php include_once __DIR__ . '/../partials/header.php'; ?>

<main class="flex-grow flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center">Créer un compte</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="/register/submit" method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div>
                <label for="email" class="block font-semibold mb-1">Adresse email :</label>
                <input type="email" id="email" name="email" required
                       placeholder="prenom.nom@bognysurmeuse.fr"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label for="password" class="block font-semibold mb-1">Mot de passe :</label>
                <input type="password" id="password" name="password" required
                       placeholder="Mot de passe (8 caractères min.)"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label for="confirm" class="block font-semibold mb-1">Confirmation :</label>
                <input type="password" id="confirm" name="confirm" required
                       placeholder="Confirmez le mot de passe"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div class="flex justify-end text-sm text-blue-600 mt-2">
                <a href="/login" class="hover:underline">Se connecter</a>
            </div>

            <div class="text-center mt-4">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">
                    Créer le compte
                </button>
            </div>
        </form>
    </div>
</main>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
