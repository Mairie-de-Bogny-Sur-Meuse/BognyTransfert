<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Code 2FA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-lg rounded-xl p-6 w-full max-w-md">
        <h1 class="text-2xl font-bold mb-4 text-center">Vérification 2FA</h1>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="bg-red-100 text-red-700 p-2 rounded mb-4"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="/verify/2fa-check" method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div>
                <label for="code" class="block font-medium">Code reçu :</label>
                <input type="text" id="code" name="code" maxlength="10" placeholder="Ex: 1A8NB5" required
                       class="w-full border border-gray-300 rounded px-4 py-2">
            </div>

            <div class="text-center">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Valider
                </button>
            </div>
        </form>
    </div>
</body>
</html>
