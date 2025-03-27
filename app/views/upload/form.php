<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$maxTotalSize = getenv('MAX_UPLOAD_SIZE') ?: 10 * 1024 * 1024 * 1024; // 10 Go par défaut
$maxFileSize = getenv('MAX_SIZE_PER_TRANSFER') ?: 10 * 1024 * 1024 * 1024; // 2 Go par défaut
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Envoyer un fichier - BognyTransfert</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-2xl">
        <div class="flex justify-center mb-6">
            <img src="/assets/img/BOGNY_logo_Gradient.svg" alt="Logo Bogny-sur-Meuse" class="h-16">
        </div>

        <h1 class="text-2xl font-bold mb-6 text-center">Envoyer un ou plusieurs fichiers</h1>

        <form id="upload-form" action="/upload/handleUpload" method="post" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div>
                <label for="email" class="block font-semibold mb-1">Votre adresse email :</label>
                <input type="email" id="email" name="email" required placeholder="prenom.nom@bognysurmeuse.fr"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Type d’envoi :</label>
                <label class="inline-flex items-center mr-4">
                    <input type="radio" name="upload_option" value="email" checked class="mr-2 option-toggle">
                    Envoyer par email
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="upload_option" value="link_only" class="mr-2 option-toggle">
                    Générer un lien uniquement
                </label>
            </div>
            <div class="mb-4">
                <label for="encryption_level" class="block text-sm font-medium text-gray-700">
                    Sécurité des fichiers :
                </label>
                <select id="encryption_level" name="encryption_level" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    
                    <option value="none">🔓 Aucune protection</option>
                    <option value="aes">🔐 Chiffrement sécurisé (AES)</option>
                    <option value="aes_rsa">🔐🔐 Chiffrement avancé (AES + RSA)</option>
                </select>

                <p class="text-sm text-gray-500 mt-1">
                    Choisissez le niveau de sécurité appliqué aux fichiers pendant le transfert.
                </p>
            </div>

            <div>
                <label for="password" class="block font-semibold mb-1">Mot de passe (optionnel) :</label>
                <input type="password" id="password" name="password" placeholder="Mot de passe pour protéger le fichier"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-300">
            </div>
            
            <div id="recipient-section">
                <label for="recipient_email" class="block font-semibold mb-1">Adresse e-mail du destinataire :</label>
                <input type="email" id="recipient_email" name="recipient_email" placeholder="destinataire@example.com"
                    class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div id="message-section">
                <label for="message" class="block font-semibold mb-1">Message (optionnel) :</label>
                <textarea id="message" name="message" rows="3" placeholder="Votre message ici..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-300"></textarea>
            </div>


            <div id="drop-zone" class="w-full border-4 border-dashed border-gray-300 rounded-xl p-6 text-center transition hover:bg-gray-50 cursor-pointer">
                <p class="text-gray-500 mb-2">Glissez-déposez vos fichiers ici ou cliquez pour sélectionner</p>
                <input type="file" name="files_flat[]" id="files_flat" multiple class="hidden">
                <input type="file" name="files_tree[]" id="files_tree" multiple webkitdirectory class="hidden">
                <button type="button" id="choose-files" class="text-blue-600 underline mt-2">Choisir des fichiers</button>
                <button type="button" id="choose-folder" class="text-blue-600 underline ml-4">Choisir un dossier</button>
            </div>

            <div id="file-info" class="text-sm text-gray-600"></div>
            <div id="error-message" class="text-red-600 text-sm mt-2 font-semibold"></div>

            <div class="text-center">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">Envoyer</button>
            </div>
        </form>
    </div>

    <script>
        const dropZone = document.getElementById('drop-zone');
        const filesFlatInput = document.getElementById('files_flat');
        const filesTreeInput = document.getElementById('files_tree');
        const chooseFilesBtn = document.getElementById('choose-files');
        const chooseFolderBtn = document.getElementById('choose-folder');
        const fileInfo = document.getElementById('file-info');
        const errorMessage = document.getElementById('error-message');
        const uploadForm = document.getElementById('upload-form');

        const maxTotalSize = <?php echo json_encode($maxTotalSize); ?>;
        const maxFileSize = <?php echo json_encode($maxFileSize); ?>;

        chooseFilesBtn.addEventListener('click', () => filesFlatInput.click());
        chooseFolderBtn.addEventListener('click', () => filesTreeInput.click());

        function updateFileInfo(files) {
            let totalSize = 0;
            let tooBig = false;
            for (const file of files) {
                totalSize += file.size;
                if (file.size > maxFileSize) {
                    tooBig = true;
                }
            }

            if (tooBig) {
                errorMessage.textContent = "Un ou plusieurs fichiers dépassent la taille maximale autorisée (2 Go).";
                return false;
            }

            if (totalSize > maxTotalSize) {
                errorMessage.textContent = "La taille totale dépasse la limite de 10 Go.";
                return false;
            }

            const sizeMB = (totalSize / (1024 * 1024)).toFixed(2);
            fileInfo.textContent = `${files.length} fichier(s), ${sizeMB} Mo`;
            errorMessage.textContent = "";
            return true;
        }

        filesFlatInput.addEventListener('change', () => {
            updateFileInfo(filesFlatInput.files);
        });

        filesTreeInput.addEventListener('change', () => {
            updateFileInfo(filesTreeInput.files);
        });

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
            dropZone.addEventListener(evt, e => {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        ['dragenter', 'dragover'].forEach(evt => {
            dropZone.addEventListener(evt, () => dropZone.classList.add('bg-gray-200'));
        });

        ['dragleave', 'drop'].forEach(evt => {
            dropZone.addEventListener(evt, () => dropZone.classList.remove('bg-gray-200'));
        });

        dropZone.addEventListener('drop', e => {
            const dt = e.dataTransfer;
            const files = dt.files;
            filesFlatInput.files = files;
            updateFileInfo(files);
        });

        uploadForm.addEventListener('submit', e => {
            const isValid = updateFileInfo(filesFlatInput.files.length ? filesFlatInput.files : filesTreeInput.files);
            if (!isValid) {
                e.preventDefault();
            }
        });


        const optionRadios = document.querySelectorAll('.option-toggle');
        const recipientSection = document.getElementById('recipient-section');
        const messageSection = document.getElementById('message-section');

        function toggleUploadOption() {
            const selected = document.querySelector('.option-toggle:checked').value;
            const showExtra = selected === 'email';
            recipientSection.style.display = showExtra ? 'block' : 'none';
            messageSection.style.display = showExtra ? 'block' : 'none';

            // Activation/désactivation validation
            document.getElementById('recipient_email').required = showExtra;
        }
        optionRadios.forEach(r => r.addEventListener('change', toggleUploadOption));
        window.addEventListener('DOMContentLoaded', toggleUploadOption);

    </script>
</body>
</html>
