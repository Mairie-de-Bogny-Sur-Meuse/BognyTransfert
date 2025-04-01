document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('drop-zone');
    const filesFlatInput = document.getElementById('files_flat');
    const filesTreeInput = document.getElementById('files_tree');
    const chooseFilesBtn = document.getElementById('choose-files');
    const chooseFolderBtn = document.getElementById('choose-folder');
    const clearFilesBtn = document.getElementById('clear-files');
    const fileInfo = document.getElementById('file-info');
    const errorMessage = document.getElementById('error-message');
    const uploadForm = document.getElementById('upload-form');
    const cancelBtn = document.getElementById('cancelUpload');

    const maxTotalSize = parseInt(uploadForm.dataset.maxTotalSize);
    const maxFileSize = parseInt(uploadForm.dataset.maxFileSize);

    chooseFilesBtn.addEventListener('click', () => filesFlatInput.click());
    chooseFolderBtn.addEventListener('click', () => filesTreeInput.click());
    clearFilesBtn.addEventListener('click', () => {
        filesFlatInput.value = '';
        filesTreeInput.value = '';
        fileInfo.textContent = '';
        errorMessage.textContent = '';
        clearFilesBtn.classList.add('hidden');
    });

    function updateFileInfo(files) {
        let totalSize = 0;
        let tooBig = false;
        for (const file of files) {
            totalSize += file.size;
            if (file.size > maxFileSize) tooBig = true;
        }

        if (tooBig) {
            errorMessage.textContent = "Un ou plusieurs fichiers dépassent la taille maximale autorisée (2 Go).";
            clearFilesBtn.classList.remove('hidden');
            return false;
        }

        if (totalSize > maxTotalSize) {
            errorMessage.textContent = "La taille totale dépasse la limite de 10 Go.";
            clearFilesBtn.classList.remove('hidden');
            return false;
        }

        const sizeMB = (totalSize / (1024 * 1024)).toFixed(2);
        fileInfo.textContent = `${files.length} fichier(s), ${sizeMB} Mo`;
        errorMessage.textContent = "";

        // ✅ Affiche le bouton uniquement si des fichiers sont présents
        clearFilesBtn.classList.toggle('hidden', files.length === 0);

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
    ['dragenter', 'dragover'].forEach(evt => dropZone.addEventListener(evt, () => dropZone.classList.add('bg-gray-200')));
    ['dragleave', 'drop'].forEach(evt => dropZone.addEventListener(evt, () => dropZone.classList.remove('bg-gray-200')));
    dropZone.addEventListener('drop', e => {
        const dt = e.dataTransfer;
        const files = dt.files;
        filesFlatInput.files = files;
        updateFileInfo(files);
    });

    let xhr;
    uploadForm.addEventListener('submit', e => {
        e.preventDefault();

        const files = filesFlatInput.files.length ? filesFlatInput.files : filesTreeInput.files;
        const isValid = updateFileInfo(files);
        if (!isValid) return;

        const formData = new FormData(uploadForm);
        formData.set('cancel_upload', '0');

        uploadForm.classList.add('fade-out');
        setTimeout(() => uploadForm.classList.add('hidden'), 600);
        document.getElementById('upload-progress').classList.remove('hidden');
        document.getElementById('uploadETA').classList.remove('hidden');
        cancelBtn.classList.remove('hidden');

        xhr = new XMLHttpRequest();

        let lastTime = Date.now();
        let lastLoaded = 0;
        let speedHistory = [];
        let etaHistory = [];
        const maxHistory = 20;

        xhr.upload.addEventListener('progress', e => {
            if (!e.lengthComputable) return;

            const now = Date.now();
            const deltaTime = (now - lastTime) / 1000;
            const deltaLoaded = e.loaded - lastLoaded;
            const speed = deltaLoaded / deltaTime;

            const percent = Math.round((e.loaded / e.total) * 100);
            const sentMB = (e.loaded / 1024 / 1024).toFixed(1);
            const totalMB = (e.total / 1024 / 1024).toFixed(1);

            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressBar').textContent = percent + '%';
            document.getElementById('progressValue').textContent = percent + '%';
            document.getElementById('uploadSpeed').textContent = formatSpeed(speed);
            document.getElementById('uploadSent').textContent = `${sentMB} / ${totalMB} Mo`;

            if (isFinite(speed)) speedHistory.push(speed);
            if (speedHistory.length > maxHistory) speedHistory.shift();

            const avgSpeed = speedHistory.reduce((a, b) => a + b, 0) / speedHistory.length;

            const remaining = e.total - e.loaded;
            const eta = remaining / avgSpeed;
            if (isFinite(eta)) {
                etaHistory.push(eta);
                if (etaHistory.length > maxHistory) etaHistory.shift();
                const avgEta = etaHistory.reduce((a, b) => a + b, 0) / etaHistory.length;
                document.getElementById('etaValue').textContent = `${Math.floor(avgEta / 60)}m ${Math.floor(avgEta % 60)}s`;
            } else {
                document.getElementById('etaValue').textContent = "Calcul...";
            }

            if (percent >= 100) {
                cancelBtn.classList.add('hidden');
                document.getElementById('redirectMessage').classList.remove('hidden');
            }

            lastTime = now;
            lastLoaded = e.loaded;
        });

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 0) return;
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.redirect) {
                        window.location.href = res.redirect;
                    } else {
                        errorMessage.textContent = "Erreur serveur.";
                    }
                } catch {
                    errorMessage.textContent = "Réponse invalide du serveur.";
                }
            }
        };

        xhr.open('POST', uploadForm.action, true);
        xhr.send(formData);
    });

    cancelBtn.addEventListener('click', () => {
        if (xhr && xhr.readyState !== 4) {
            xhr.abort();
            const cancelInput = document.getElementById('cancel_upload');
            if (cancelInput) cancelInput.value = '1';

            document.getElementById('redirectMessage').classList.remove('hidden');
            document.getElementById('redirectMessage').innerHTML = `
                <div class="flex items-center justify-center text-red-600 space-x-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>⛔ Envoi annulé, retour à l’accueil...</span>
                </div>
            `;

            setTimeout(() => {
                window.location.href = '/';
            }, 3000);
        }
    });

    function formatSpeed(bps) {
        const kb = 1024, mb = kb * 1024, gb = mb * 1024;
        if (bps >= gb) return (bps / gb).toFixed(2) + ' Go/s';
        if (bps >= mb) return (bps / mb).toFixed(2) + ' Mo/s';
        if (bps >= kb) return (bps / kb).toFixed(2) + ' ko/s';
        return bps.toFixed(0) + ' o/s';
    }

    const optionRadios = document.querySelectorAll('.option-toggle');
    const recipientSection = document.getElementById('recipient-section');
    const messageSection = document.getElementById('message-section');

    function toggleUploadOption() {
        const selected = document.querySelector('.option-toggle:checked').value;
        const show = selected === 'email';
        recipientSection.style.display = show ? 'block' : 'none';
        messageSection.style.display = show ? 'block' : 'none';
        document.getElementById('recipient_email').required = show;
    }

    optionRadios.forEach(r => r.addEventListener('change', toggleUploadOption));
    toggleUploadOption();
});
