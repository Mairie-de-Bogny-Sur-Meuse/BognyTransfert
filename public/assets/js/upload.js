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
    const submitButton = document.querySelector('#upload-form button[type="submit"]');

    const maxTotalSize = parseInt(uploadForm.dataset.maxTotalSize);
    const maxFileSize = parseInt(uploadForm.dataset.maxFileSize);
    const dangerousExtensions = ['php', 'exe', 'sh', 'bat', 'cmd'];

    let fileList = [];

    function setSubmitEnabled(state) {
        submitButton.disabled = !state;
        submitButton.classList.toggle('opacity-50', !state);
        submitButton.classList.toggle('cursor-not-allowed', !state);
    }

    chooseFilesBtn.addEventListener('click', () => filesFlatInput.click());
    chooseFolderBtn.addEventListener('click', () => filesTreeInput.click());
    document.getElementById('add-more-files').addEventListener('click', () => filesFlatInput.click());

    clearFilesBtn.addEventListener('click', () => {
        fileList = [];
        updateInputFiles();
        updateFileInfo();
        errorMessage.textContent = '';
        clearFilesBtn.classList.add('hidden');
        setSubmitEnabled(false);
    });

    filesFlatInput.addEventListener('change', (e) => {
        const newFiles = Array.from(e.target.files);
        fileList = mergeFileLists(fileList, newFiles);
        updateInputFiles();
        updateFileInfo();
    });

    filesTreeInput.addEventListener('change', (e) => {
        const newFiles = Array.from(e.target.files);
        fileList = mergeFileLists(fileList, newFiles);
        updateInputFiles();
        updateFileInfo();
    });

    function mergeFileLists(listA, listB) {
        const names = new Set(listA.map(f => f.webkitRelativePath || f.name));
        return [...listA, ...listB.filter(f => !names.has(f.webkitRelativePath || f.name))];
    }

    function updateInputFiles() {
        const dataTransfer = new DataTransfer();
        fileList.forEach(file => dataTransfer.items.add(file));
        filesFlatInput.files = dataTransfer.files;
        filesTreeInput.files = dataTransfer.files;
    }

    function updateFileInfo() {
        let totalSize = 0;
        let tooBig = false;
        let dangerousFound = false;

        for (const file of fileList) {
            const ext = file.name.split('.').pop().toLowerCase();
            if (dangerousExtensions.includes(ext)) {
                dangerousFound = true;
                break;
            }
            totalSize += file.size;
            if (file.size > maxFileSize) tooBig = true;
        }

        if (dangerousFound) {
            errorMessage.textContent = "❌ Un ou plusieurs fichiers ont une extension interdite.";
            clearFilesBtn.classList.remove('hidden');
            setSubmitEnabled(false);
            return false;
        }

        if (tooBig) {
            errorMessage.textContent = "❌ Un ou plusieurs fichiers dépassent 2 Go.";
            clearFilesBtn.classList.remove('hidden');
            setSubmitEnabled(false);
            return false;
        }

        if (totalSize > maxTotalSize) {
            errorMessage.textContent = "❌ La taille totale dépasse 10 Go.";
            clearFilesBtn.classList.remove('hidden');
            setSubmitEnabled(false);
            return false;
        }

        const sizeMB = (totalSize / 1024 / 1024).toFixed(2);
        fileInfo.textContent = `${fileList.length} fichier(s), ${sizeMB} Mo`;
        errorMessage.textContent = "";
        clearFilesBtn.classList.toggle('hidden', fileList.length === 0);
        document.getElementById('add-more-files').classList.toggle('hidden', fileList.length === 0);
        document.getElementById('choose-files').classList.toggle('hidden', fileList.length > 0);
        document.getElementById('choose-folder').classList.toggle('hidden', fileList.length > 0);
        setSubmitEnabled(fileList.length > 0);
        return true;
    }

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
        dropZone.addEventListener(evt, e => {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    ['dragenter', 'dragover'].forEach(evt =>
        dropZone.addEventListener(evt, () => dropZone.classList.add('bg-gray-200'))
    );
    ['dragleave', 'drop'].forEach(evt =>
        dropZone.addEventListener(evt, () => dropZone.classList.remove('bg-gray-200'))
    );

    dropZone.addEventListener('drop', e => {
        const droppedFiles = Array.from(e.dataTransfer.files);
        fileList = mergeFileLists(fileList, droppedFiles);
        updateInputFiles();
        updateFileInfo();
    });

    let xhr;

    uploadForm.addEventListener('submit', e => {
        e.preventDefault();
        const isValid = updateFileInfo();
        if (!isValid) return;

        const formData = new FormData(uploadForm);
        formData.set('cancel_upload', '0');

        formData.delete('files_flat[]');
        formData.delete('files_tree[]');

        fileList.forEach(file => {
            const key = file.webkitRelativePath ? 'files_tree[]' : 'files_flat[]';
            formData.append(key, file);
        });

        xhr = new XMLHttpRequest();

        let lastTime = Date.now();
        let lastLoaded = 0;
        let speedHistory = [];
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
            const etaText = isFinite(eta)
                ? `${Math.floor(eta / 60)}m ${Math.floor(eta % 60)}s`
                : "Calcul...";
            document.getElementById('etaValue').textContent = etaText;

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
                        document.getElementById('upload-progress').classList.add('hidden');
                        uploadForm.classList.remove('fade-out', 'hidden');
                        errorMessage.textContent = res.error || "❌ Une erreur est survenue.";
                        setSubmitEnabled(true);
                    }
                } catch {
                    document.getElementById('upload-progress').classList.add('hidden');
                    uploadForm.classList.remove('fade-out', 'hidden');
                    errorMessage.textContent = "❌ Réponse invalide du serveur.";
                    setSubmitEnabled(true);
                }
            }
        };

        uploadForm.classList.add('fade-out');
        setTimeout(() => uploadForm.classList.add('hidden'), 600);
        document.getElementById('upload-progress').classList.remove('hidden');
        document.getElementById('uploadETA').classList.remove('hidden');
        cancelBtn.classList.remove('hidden');
        setSubmitEnabled(false);
        xhr.open('POST', uploadForm.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formData);
    });

    cancelBtn.addEventListener('click', () => {
        if (xhr && xhr.readyState !== 4) {
            xhr.abort();
            document.getElementById('cancel_upload').value = '1';

            document.getElementById('redirectMessage').classList.remove('hidden');
            document.getElementById('redirectMessage').innerHTML = `
                <div class="flex items-center justify-center text-red-600 space-x-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>⛔ Envoi annulé, retour à l’accueil...</span>
                </div>`;
            setTimeout(() => window.location.href = '/', 3000);
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

    setSubmitEnabled(false);
});
