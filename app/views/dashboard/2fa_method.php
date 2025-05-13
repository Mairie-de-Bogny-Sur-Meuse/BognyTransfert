<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Choisir la méthode 2FA - BognyTransfert</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
        <h1 class="text-xl font-bold text-center mb-6">Choisissez votre méthode 2FA</h1>
        <form action="/dashboard/2fa-method" method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button name="method" value="email" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
                🔐 Par Email
            </button>
            <button name="method" value="totp" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 transition">
                📱 Par Google Authenticator
            </button>
        </form>
    </div>
</body>
</html>
