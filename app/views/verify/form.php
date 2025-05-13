<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$email = $_GET['email'] ?? '';
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérification de l'adresse email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

<?php include_once __DIR__ . '/../partials/header.php'; ?>

<main class="flex-grow flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
        <h1 class="sr-only">Formulaire de vérification du code</h1>
        <h2 class="text-2xl font-bold mb-6 text-center">🔐 Vérification par code</h2>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="/verify/submit" method="post" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

            <div>
                <label for="code" class="block font-semibold mb-1">Code de vérification :</label>
                <input type="text" id="code" name="code" maxlength="10" required placeholder="Ex : 6A9F42"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl text-center uppercase tracking-widest focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div class="text-center">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">
                    Valider le code
                </button>
            </div>
        </form>
    </div>
</main>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
