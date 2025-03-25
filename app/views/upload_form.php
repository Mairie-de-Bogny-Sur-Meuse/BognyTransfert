<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Transfert de fichiers sécurisé</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen flex items-center justify-center px-4 py-8">

  <div class="bg-white shadow-xl rounded-2xl p-10 max-w-xl w-full space-y-6">
    <!-- Logo / Icône -->
    <div class="flex justify-center">
    <img src="..\public\img\BOGNY_logo_Gradient.svg" alt="Logo BognyTransfert" class="h-20 w-auto mb-4">
    
    </div>

    <h1 class="text-2xl font-bold text-center text-gray-800">Transfert sécurisé</h1>
    <p class="text-sm text-center text-gray-500">Envoyez des fichiers ou un dossier complet jusqu'à <strong>10 Go</strong>.</p>

    <form id="uploadForm" action="/upload" enctype="multipart/form-data" method="POST" class="space-y-5">

      <!-- Email -->
      <div>
        <label for="email" class="block font-medium text-gray-700">Votre e-mail <span class="text-sm text-gray-500">(doit se terminer par @bognysurmeuse.fr)</span></label>
        <input type="email" name="email" id="email" required
               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="prenom.nom@bognysurmeuse.fr" />
      </div>

      <!-- Mot de passe -->
      <div>
        <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe (optionnel)</label>
        <input type="text" name="password" id="password"
               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="Protège l'accès au lien de téléchargement" />
      </div>

      <!-- Drag & Drop Zone -->
      <div id="dropZone" class="flex flex-col items-center justify-center border-2 border-dashed border-indigo-400 rounded-md p-6 text-center bg-indigo-50 hover:bg-indigo-100 cursor-pointer transition">
        <p class="text-indigo-600 font-medium">Glissez & déposez vos fichiers ici</p>
        <p class="text-sm text-gray-500 mt-1">ou utilisez les champs ci-dessous</p>
      </div>

      <!-- Sélection fichiers -->
      <div>
        <label for="files" class="block text-sm font-medium text-gray-700">Fichiers individuels</label>
        <input type="file" name="files_flat[]" id="files" multiple class="mt-1 block w-full file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200" />
      </div>

      <!-- Sélection dossier -->
      <div>
        <label for="folder" class="block text-sm font-medium text-gray-700">Ou dossier complet</label>
        <input type="file" name="files_tree[]" id="folder" webkitdirectory directory multiple class="mt-1 block w-full file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200" />
      </div>

      <!-- Taille totale -->
      <div id="totalSizeDisplay" class="text-sm text-gray-600">Taille totale : 0 Mo</div>

      <!-- Progress bar -->
      <div id="progressContainer" class="hidden w-full bg-gray-200 rounded-full h-4 overflow-hidden">
        <div id="progressBar" class="bg-indigo-600 h-full w-0 transition-all duration-300 ease-out"></div>
      </div>

      <!-- Bouton -->
      <button type="submit"
              class="mt-2 w-full bg-indigo-600 text-white font-semibold py-3 px-4 rounded-md hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
        📤 Envoyer les fichiers
      </button>

      <!-- Statut -->
      <div id="uploadStatus" class="mt-3 text-sm text-red-600 text-center"></div>
    </form>
  </div>

  <script>
    const form = document.getElementById('uploadForm');
    const fileInputs = [document.getElementById('files'), document.getElementById('folder')];
    const dropZone = document.getElementById('dropZone');
    const progressBar = document.getElementById('progressBar');
    const progressContainer = document.getElementById('progressContainer');
    const submitBtn = form.querySelector('button[type="submit"]');
    const uploadStatus = document.getElementById('uploadStatus');
    const totalSizeDisplay = document.getElementById('totalSizeDisplay');

    const MAX_SIZE_BYTES = 10 * 1024 * 1024 * 1024; // 10 Go

    submitBtn.disabled = true;

    function updateProgress() {
      let totalSize = 0;
      let totalFiles = 0;
      fileInputs.forEach(input => {
        for (let file of input.files) {
          totalSize += file.size;
          totalFiles++;
        }
      });

      const sizeMo = (totalSize / (1024 * 1024)).toFixed(2);
      totalSizeDisplay.textContent = `Taille totale : ${sizeMo} Mo`;

      if (totalFiles > 0) {
        progressContainer.classList.remove('hidden');
        progressBar.style.width = '100%';
      } else {
        progressContainer.classList.add('hidden');
        progressBar.style.width = '0%';
      }

      if (totalSize > MAX_SIZE_BYTES) {
        uploadStatus.textContent = "🚫 La taille totale dépasse la limite de 10 Go.";
        submitBtn.disabled = true;
      } else {
        uploadStatus.textContent = "";
        submitBtn.disabled = totalFiles === 0;
      }
    }

    fileInputs.forEach(input => input.addEventListener('change', updateProgress));

    // Drag & Drop
    dropZone.addEventListener('dragover', (e) => {
      e.preventDefault();
      dropZone.classList.add('bg-indigo-200');
    });

    dropZone.addEventListener('dragleave', () => {
      dropZone.classList.remove('bg-indigo-200');
    });

    dropZone.addEventListener('drop', (e) => {
      e.preventDefault();
      dropZone.classList.remove('bg-indigo-200');

      // On répartit les fichiers dans le champ fichiers
      const files = e.dataTransfer.files;
      document.getElementById('files').files = files;

      updateProgress();
    });
  </script>
</body>
</html>
