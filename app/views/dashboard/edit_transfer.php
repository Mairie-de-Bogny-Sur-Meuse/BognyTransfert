<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../../models/SecurityModel.php';

$token = $_GET['token'] ?? '';
$csrf = SecurityModel::generateCSRFToken();
$fichiers = $fichiers ?? [];
$expiration = $expiration ?? date('Y-m-d\TH:i');
$currentEncryption = $currentEncryption ?? 'aes';
?>

<main class="max-w-5xl mx-auto mt-8 px-4">
    <h1 class="text-3xl font-bold text-center mb-8">Modifier le transfert</h1>

    <!-- 🔔 Messages -->
    <?php foreach (['error' => 'red', 'success' => 'green', 'warning' => 'yellow'] as $type => $color): ?>
        <?php if (isset($_SESSION[$type])): ?>
            <div class="bg-<?= $color ?>-100 border border-<?= $color ?>-400 text-<?= $color ?>-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($_SESSION[$type]) ?>
                <?php unset($_SESSION[$type]); ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- 📝 Formulaire principal -->
    <form action="/dashboard/editTransfer" method="POST" class="bg-white shadow rounded-lg p-6 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <!-- 📁 Fichiers transférés avec arborescence -->
    <div>
        <h2 class="text-xl font-semibold mb-4">Fichiers</h2>

        <?php
        // Regrouper les fichiers par dossier
        $arborescence = [];
        foreach ($fichiers as $fichier) {
            $relativePath = str_replace('\\', '/', $fichier['file_path']);
            $pathParts = explode('/', $relativePath);
            array_pop($pathParts); // on retire le nom de fichier
            $dir = implode('/', $pathParts);
            if (!isset($arborescence[$dir])) $arborescence[$dir] = [];
            $arborescence[$dir][] = $fichier;
        }
        ?>

        <ul class="space-y-4">
            <?php foreach ($arborescence as $dossier => $fichiersDansDossier): ?>
                <li>
                    <div class="font-semibold text-blue-600 mb-2 flex items-center gap-2">
                        <!-- Dossier icône -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                        </svg>
                        <?= htmlspecialchars($dossier ?: 'racine') ?>
                    </div>
                    <ul class="pl-6 space-y-2">
                        <?php foreach ($fichiersDansDossier as $fichier): ?>
                            <li class="flex items-center justify-between border border-gray-200 rounded px-4 py-2 hover:bg-gray-50">
                                <div class="flex items-center gap-2 w-full">
                                    <!-- Fichier icône -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6.414A2 2 0 0017.414 5L13 0.586A2 2 0 0011.586 0H4zm9 6h-4v1h4V8zm0 2h-4v1h4v-1zm-4 2h4v1h-4v-1z" />
                                    </svg>
                                    <h1><?=  htmlspecialchars($fichier['file_name']) ?></h1>
                                        
                                </div>

                                <label class="ml-4 text-sm text-red-600 flex items-center">
                                    <input type="checkbox" name="delete[]" value="<?= $fichier['uuid'] ?>" class="mr-2">
                                    Supprimer
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>


        <!-- 🔐 Mot de passe -->
        <div>
            <label class="block font-medium mb-1">Mot de passe (laisser vide pour ne pas modifier)</label>
            <input type="password" name="password" placeholder="••••••••"
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-400">
        </div>

        <!-- 📅 Expiration -->
        <div>
            <label class="block font-medium mb-1">Date d'expiration</label>
            <input type="datetime-local" name="expiration" value="<?= htmlspecialchars($expiration) ?>"
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-400">
        </div>

        <!-- 🔒 Niveau de chiffrement -->
        <div>
            <label class="block font-medium mb-1">Niveau de chiffrement actuel : 
                <span class="font-semibold"><?= htmlspecialchars($currentEncryption) ?></span>
            </label>
            <select name="None_disabled"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-400" disabled>
                <option value="none" <?= $currentEncryption === 'none' ? 'selected' : '' ?>>Aucun</option>
                <option value="aes" <?= $currentEncryption === 'aes' ? 'selected' : '' ?>>AES</option>
                <option value="aes_rsa" <?= $currentEncryption === 'aes_rsa' ? 'selected' : '' ?>>AES + RSA</option>
                <option value="maximum" <?= $currentEncryption === 'maximum' ? 'selected' : '' ?>>Sécurité maximale</option>
            </select>
            <p class="text-sm text-gray-500 mt-1">⚠️ Non Modifiable ⚠️</p>
        </div>

        <div class="text-center pt-4">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded transition">
                Enregistrer les modifications
            </button>
        </div>
    </form>
</main>
