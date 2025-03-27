<?php
$confirmation = $_SESSION['confirmation_data'] ?? null;

if (!$confirmation || empty($confirmation['generated_link']) || empty($confirmation['pending_upload'])) {
    $title = "Erreur de session";
    $message = "Lien de téléchargement indisponible.";
    $code = 400;
    require_once __DIR__ . '/../errors/custom_error.php';
    return;
}

$upload = $confirmation['pending_upload'];
$generatedLink = $confirmation['generated_link'];
$uploadOption = $upload['upload_option'] ?? 'link_only';
$recipient = $upload['recipient'] ?? '';

unset($_SESSION['confirmation_data']);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Confirmation - BognyTransfert</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-xl text-center">
        <h1 class="text-3xl font-bold text-green-600 mb-4">Merci !</h1>

        <?php if ($uploadOption === 'link_only'): ?>
            <p class="mb-2">Vos fichiers ont bien été enregistrés.</p>
            <p class="mb-4">Voici le lien de téléchargement sécurisé :</p>

            <!-- Zone lien de téléchargement -->
            <div class="bg-gray-50 border border-gray-300 rounded-xl p-4 mb-4">
                <label class="block font-semibold mb-2">Lien de téléchargement :</label>
                <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <input id="downloadLink" type="text" readonly value="<?php echo htmlspecialchars($generatedLink); ?>"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-xl bg-white text-sm focus:outline-none">
                    <button onclick="copyLink()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Copier</button>
                </div>
                <a href="<?php echo htmlspecialchars($generatedLink); ?>" target="_blank" class="text-blue-600 hover:underline text-sm text-left">
                    🌐 Ouvrir le lien dans un nouvel onglet
                </a>
            </div>

            </div>
        <?php else: ?>
            <p class="mb-2">Vos fichiers ont bien été envoyés à <strong><?= htmlspecialchars($recipient) ?></strong>.</p>
            <p class="mb-4">Un email contenant le lien de téléchargement a été transmis au destinataire.</p>
        <?php endif; ?>

        <a href="/upload" class="inline-block mt-4 bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">Envoyer d'autres fichiers</a>
    </div>

    <script>
        function copyLink() {
            const input = document.getElementById("downloadLink");
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(() => {
                alert("Lien copié dans le presse-papiers ✅");
            });
        }
    </script>
</body>
</html>
