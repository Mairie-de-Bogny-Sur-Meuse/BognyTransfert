<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord - BognyTransfert</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">
<?php include_once __DIR__ . '/../partials/header.php'; ?>

<main class="flex-grow flex flex-col items-center justify-start p-6">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-4xl mb-6">
        <h1 class="text-2xl font-bold mb-4 text-center">Mon compte</h1>

        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <p><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p>
            <strong>Authentification 2FA :</strong>
            <?= $user['twofa_enabled'] ? '✅ Activée (' . strtoupper($user['twofa_method']) . ')' : '❌ Désactivée' ?>
        </p>
        <p class="mb-4">
            <strong>Quota (Mensuel) : </strong> <?= $quotaUtiliser ?>/<?= $quotaTotal ?> (<?= round(($fichierModel->sumStorageForMonthByEmail($user['email'])/$_ENV['MAX_TOTAL_SIZE_PER_MONTH']*100),4) ?> %)
        </p>
        <div class="mb-6 text-center">
            <?php if (!$user['twofa_enabled']): ?>
                <a href="/dashboard/2fa-choice"
                class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">
                    🔐 Activer la 2FA
                </a>
            <?php else: ?>
                <form action="/dashboard/2fa-disable" method="post" onsubmit="return confirm('Confirmer la désactivation de la 2FA ?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <button type="submit"
                            class="bg-red-600 text-white px-6 py-2 rounded-full hover:bg-red-700 transition">
                        🔓 Désactiver la 2FA
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="text-center">
            <a href="/reset?token=<?= $tokenPassword ?>" class="text-sm text-blue-600 hover:underline">🔒 Modifier le mot de passe</a>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-4xl">
        <div class="w-full max-w-4xl mb-4 text-right">
            <a href="/upload" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-full shadow transition">
                ➕ Nouveau transfert
            </a>
        </div>

        <h2 class="text-xl font-bold mb-4 text-center">📁 Mes transferts</h2>
        <?php if (empty($groupes)): ?>
            <p class="text-gray-500 text-center">Aucun transfert enregistré pour l’instant.</p>
        <?php else: ?>
            <table class="w-full text-sm text-left border">
                <thead>
                    <tr class="bg-gray-100 text-gray-700">
                        <th class="px-3 py-2">Transfert (Token)</th>
                        <th class="px-3 py-2">Taille Totale</th>
                        <th class="px-3 py-2">Lien</th>
                        <th class="px-3 py-2">Expire</th>
                        <th class="px-3 py-2">Statut</th>
                        <th class="px-3 py-2 text-center" colspan="2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groupes as $token => $infos): ?>
                        <?php
                            $totalSize = array_sum(array_column($infos['files'], 'file_size'));
                            $isExpired = strtotime($infos['expire']) < time();
                        ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-3 py-2 break-all"><?= htmlspecialchars($token) ?></td>
                            <td class="px-3 py-2"><?= number_format($totalSize / 1048576, 2) ?> Mo</td>
                            <td class="px-3 py-2">
                                <a href="/download?token=<?= urlencode($token) ?>" target="_blank" class="text-blue-600 hover:underline">
                                    Voir
                                </a>
                            </td>
                            <td class="px-3 py-2"><?= date('d/m/Y', strtotime($infos['expire'])) ?></td>
                            <td class="px-3 py-2">
                                <?= $isExpired
                                    ? '<span class="text-red-600 font-semibold">Expiré</span>'
                                    : '<span class="text-green-600 font-semibold">Actif</span>' ?>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <form method="post" action="/dashboard/delete-transfer" onsubmit="return confirm('Supprimer ce transfert ?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Supprimer</button>
                                </form>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <a href="/dashboard/edit?token=<?= urlencode($token) ?>&csrf_token=<?= urlencode($_SESSION['csrf_token'] ?? '') ?>"
                                class="text-yellow-600 hover:underline text-sm">
                                    Éditer
                                </a>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>