<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
use RobThree\Auth\TwoFactorAuth;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$tfa = new TwoFactorAuth('BognyTransfert');
$secret = $_SESSION['2fa_temp_secret'] ?? $tfa->createSecret();

$_SESSION['2fa_temp_secret'] = $secret;
$qrCodeUrl = $tfa->getQRCodeImageAsDataUri($_SESSION['user_email'], $secret);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Activer 2FA - BognyTransfert</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex items-center justify-center">
    <div class="bg-white p-6 rounded-xl shadow-md text-center max-w-md w-full">
        <h1 class="text-xl font-bold mb-4">Activer la validation en 2 étapes (TOTP)</h1>
        <p class="mb-2">1. Scannez ce QR code avec Google Authenticator :</p>
        <img src="<?= $qrCodeUrl ?>" alt="QR Code" class="mx-auto my-4" />
        <p class="mb-2">2. Entrez le code généré ci-dessous :</p>
        <form action="/dashboard/2fa-enable" method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="text" name="code" maxlength="6" required placeholder="Code à 6 chiffres"
                   class="w-full px-4 py-2 border border-gray-300 rounded-xl">
            <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">
                Activer la 2FA
            </button>
        </form>
    </div>
</body>
</html>
