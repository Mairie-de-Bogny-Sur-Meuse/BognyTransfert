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
                    <option value="aes" selected>🔐 Chiffrement sécurisé (AES)</option>
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
            <p id="redirectMessage" class="text-center text-blue-600 font-semibold mt-4 hidden">
                ✅ Fichier envoyé. Redirection en cours, veuillez patienter…
            </p>

            <!-- Barre de progression -->
            <div id="progressContainer" class="w-full bg-gray-300 rounded h-4 mt-4 hidden">
                <div id="progressBar" class="h-4 bg-green-500 rounded w-0 transition-all duration-300 ease-in-out"></div>
            </div>
            <p id="progressText" class="text-sm mt-2 hidden text-center">
                Chargement : <span id="progressValue">0%</span>
            </p>
            <p id="uploadDetails" class="text-sm text-center mt-1 hidden">
                🔄 <span id="uploadPercent">0%</span> – <span id="uploadSpeed">0 Mo/s</span> | <span id="uploadSent">0 / 0 Mo</span>
            </p>


            <div class="text-center">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">Envoyer</button>
            </div>
                        <!-- Barre de progression -->
           

        </form>
        <?php include_once __DIR__ . '/../partials/footer.php'; ?>
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

        filesFlatInput.addEventListener('change', () => updateFileInfo(filesFlatInput.files));
        filesTreeInput.addEventListener('change', () => updateFileInfo(filesTreeInput.files));

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
            e.preventDefault();

            const files = filesFlatInput.files.length ? filesFlatInput.files : filesTreeInput.files;
            const isValid = updateFileInfo(files);
            if (!isValid) return;

            const formData = new FormData(uploadForm);

            document.getElementById('progressContainer').classList.remove('hidden');
            document.getElementById('progressText').classList.remove('hidden');
            document.getElementById('uploadDetails').classList.remove('hidden');

            const xhr = new XMLHttpRequest();
            let lastTime = Date.now();
            let lastLoaded = 0;

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    const now = Date.now();
                    const deltaTime = (now - lastTime) / 1000;
                    const deltaLoaded = e.loaded - lastLoaded;

                    const speed = deltaLoaded / deltaTime; // bytes/sec
                    const speedMB = (speed / (1024 * 1024)).toFixed(2);

                    const sentMB = (e.loaded / (1024 * 1024)).toFixed(1);
                    const totalMB = (e.total / (1024 * 1024)).toFixed(1);
                    const percent = Math.round((e.loaded / e.total) * 100);

                    document.getElementById('progressBar').style.width = percent + '%';
                    document.getElementById('progressValue').textContent = percent + '%';

                    document.getElementById('uploadPercent').textContent = percent + '%';
                    document.getElementById('uploadSpeed').textContent = speedMB + ' Mo/s';
                    document.getElementById('uploadSent').textContent = `${sentMB} / ${totalMB} Mo`;

                    // Si 100%, cacher tout et afficher le message
                    if (percent >= 100) {
                        document.getElementById('progressContainer').classList.add('hidden');
                        document.getElementById('progressText').classList.add('hidden');
                        document.getElementById('uploadDetails').classList.add('hidden');
                        document.getElementById('redirectMessage').classList.remove('hidden');
                    }


                    lastTime = now;
                    lastLoaded = e.loaded;
                }
            });

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (xhr.status === 200 && res.redirect) {
                            window.location.href = res.redirect;
                        } else {
                            errorMessage.textContent = "Une erreur est survenue lors de l'envoi.";
                        }
                    } catch (err) {
                        errorMessage.textContent = "Erreur de réponse serveur.";
                    }
                }
            };

            xhr.open('POST', uploadForm.action, true);
            xhr.send(formData);
        });

        const optionRadios = document.querySelectorAll('.option-toggle');
        const recipientSection = document.getElementById('recipient-section');
        const messageSection = document.getElementById('message-section');

        function toggleUploadOption() {
            const selected = document.querySelector('.option-toggle:checked').value;
            const showExtra = selected === 'email';
            recipientSection.style.display = showExtra ? 'block' : 'none';
            messageSection.style.display = showExtra ? 'block' : 'none';
            document.getElementById('recipient_email').required = showExtra;
        }
        optionRadios.forEach(r => r.addEventListener('change', toggleUploadOption));
        window.addEventListener('DOMContentLoaded', toggleUploadOption);
</script>


</body>
</html>
