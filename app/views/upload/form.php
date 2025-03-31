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
  <style>
    .fade-out {
      opacity: 0;
      transform: scale(0.98);
      transition: opacity 0.5s ease, transform 0.5s ease;
      pointer-events: none;
    }
    .progress-bar-animated {
      background-color: #22c55e;
      background-image: linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.2) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255, 255, 255, 0.2) 50%,
        rgba(255, 255, 255, 0.2) 75%,
        transparent 75%,
        transparent
      );
      background-size: 1rem 1rem;
      background-blend-mode: overlay;
      animation: progress-bar-stripes 1s linear infinite;
    }
    @keyframes progress-bar-stripes {
      0% { background-position: 1rem 0; }
      100% { background-position: 0 0; }
    }
  </style>
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
        <input type="email" id="email" name="email" required placeholder="prenom.nom@bognysurmeuse.fr" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-300">
      </div>
      <div>
        <label class="block font-semibold mb-1">Type d’envoi :</label>
        <label class="inline-flex items-center mr-4">
          <input type="radio" name="upload_option" value="email" checked class="mr-2 option-toggle"> Envoyer par email
        </label>
        <label class="inline-flex items-center">
          <input type="radio" name="upload_option" value="link_only" class="mr-2 option-toggle"> Générer un lien uniquement
        </label>
      </div>
      <div class="mb-4">
        <label for="encryption_level" class="block text-sm font-medium text-gray-700">Sécurité des fichiers :</label>
        <select id="encryption_level" name="encryption_level" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
          <option value="none">🔓 Aucune protection</option>
          <option value="aes" selected>🔐 Chiffrement sécurisé (AES)</option>
          <option value="aes_rsa">🔐🔐 Chiffrement avancé (AES + RSA)</option>
        </select>
        <p class="text-sm text-gray-500 mt-1">Choisissez le niveau de sécurité appliqué aux fichiers pendant le transfert.</p>
      </div>
      <div>
        <label for="password" class="block font-semibold mb-1">Mot de passe (optionnel) :</label>
        <input type="password" id="password" name="password" placeholder="Mot de passe pour protéger le fichier" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-300">
      </div>
      <div id="recipient-section">
        <label for="recipient_email" class="block font-semibold mb-1">Adresse e-mail du destinataire :</label>
        <input type="email" id="recipient_email" name="recipient_email" placeholder="destinataire@example.com" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-300">
      </div>
      <div id="message-section">
        <label for="message" class="block font-semibold mb-1">Message (optionnel) :</label>
        <textarea id="message" name="message" rows="3" placeholder="Votre message ici..." class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-300"></textarea>
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

    <!-- Progression séparée du formulaire -->
    <div id="upload-progress" class="mt-6 hidden">
    

      <div id="progressContainer" class="w-full bg-gray-300 rounded h-4 overflow-hidden">
        <div id="progressBar" class="h-4 w-0 text-white text-center text-xs font-semibold transition-all duration-300 ease-in-out progress-bar-animated">0%</div>
      </div>
      <p id="progressText" class="text-sm mt-2 text-center">Chargement : <span id="progressValue">0%</span></p>
      <p id="uploadDetails" class="text-sm text-center mt-1">🔄 <span id="uploadPercent">0%</span> – <span id="uploadSpeed">0</span> | <span id="uploadSent">0 / 0</span></p>
      <div class="text-center mt-4">
        <button id="cancelUpload" class="px-4 py-1 rounded-full bg-red-600 text-white hover:bg-red-700 transition hidden">
          ❌ Annuler l'envoi
        </button>
      </div>
      <div id="redirectMessage" class="text-center text-blue-600 font-semibold mt-4 hidden">
        <div class="flex items-center justify-center space-x-2">
          <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span>Redirection en cours, veuillez patienter...</span>
        </div>
      </div>
    </div>

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
  const cancelBtn = document.getElementById('cancelUpload');

  const maxTotalSize = <?php echo json_encode($maxTotalSize); ?>;
  const maxFileSize = <?php echo json_encode($maxFileSize); ?>;

  chooseFilesBtn.addEventListener('click', () => filesFlatInput.click());
  chooseFolderBtn.addEventListener('click', () => filesTreeInput.click());

  function updateFileInfo(files) {
      let totalSize = 0;
      let tooBig = false;
      for (const file of files) {
          totalSize += file.size;
          if (file.size > maxFileSize) tooBig = true;
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
  ['dragenter', 'dragover'].forEach(evt => dropZone.addEventListener(evt, () => dropZone.classList.add('bg-gray-200')));
  ['dragleave', 'drop'].forEach(evt => dropZone.addEventListener(evt, () => dropZone.classList.remove('bg-gray-200')));
  dropZone.addEventListener('drop', e => {
      const dt = e.dataTransfer;
      const files = dt.files;
      filesFlatInput.files = files;
      updateFileInfo(files);
  });

  let xhr; // pour référence globale

  uploadForm.addEventListener('submit', e => {
      e.preventDefault();

      const files = filesFlatInput.files.length ? filesFlatInput.files : filesTreeInput.files;
      const isValid = updateFileInfo(files);
      if (!isValid) return;

      const formData = new FormData(uploadForm);
      uploadForm.classList.add('fade-out');
      setTimeout(() => uploadForm.classList.add('hidden'), 600);

      document.getElementById('upload-progress').classList.remove('hidden');
      document.getElementById('progressContainer').classList.remove('hidden');
      document.getElementById('progressText').classList.remove('hidden');
      document.getElementById('uploadDetails').classList.remove('hidden');
      cancelBtn.classList.remove('hidden');

      xhr = new XMLHttpRequest();
      let lastTime = Date.now();
      let lastLoaded = 0;

      xhr.upload.addEventListener('progress', function (e) {
          if (e.lengthComputable) {
              const now = Date.now();
              const deltaTime = (now - lastTime) / 1000;
              const deltaLoaded = e.loaded - lastLoaded;

              const speed = deltaLoaded / deltaTime;
              const sentMB = (e.loaded / (1024 * 1024)).toFixed(1);
              const totalMB = (e.total / (1024 * 1024)).toFixed(1);
              const speedHuman = formatSpeed(speed);
              const percent = Math.round((e.loaded / e.total) * 100);

              const progressBar = document.getElementById('progressBar');
              progressBar.style.width = percent + '%';
              progressBar.textContent = percent + '%';

              document.getElementById('progressValue').textContent = percent + '%';
              document.getElementById('uploadPercent').textContent = percent + '%';
              document.getElementById('uploadSpeed').textContent = speedHuman;
              document.getElementById('uploadSent').textContent = `${sentMB} / ${totalMB} Mo`;

              // 🎨 Couleur + texte contrasté
              progressBar.classList.remove(
                  'bg-red-500', 'bg-orange-400', 'bg-yellow-300',
                  'bg-lime-400', 'bg-green-500', 'text-white', 'text-black'
              );
              if (percent <= 20) progressBar.classList.add('bg-red-500', 'text-white');
              else if (percent <= 40) progressBar.classList.add('bg-orange-400', 'text-white');
              else if (percent <= 60) progressBar.classList.add('bg-yellow-300', 'text-black');
              else if (percent <= 80) progressBar.classList.add('bg-lime-400', 'text-black');
              else progressBar.classList.add('bg-green-500', 'text-white');

              if (percent >= 90) cancelBtn.classList.add('hidden');

              if (percent >= 100) {
                  document.getElementById('progressContainer').classList.add('hidden');
                  document.getElementById('progressText').classList.add('hidden');
                  document.getElementById('uploadDetails').classList.add('hidden');
                  document.getElementById('redirectMessage').classList.remove('hidden');
                  cancelBtn.classList.add('hidden');
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

  // 🚨 Annulation
  cancelBtn.addEventListener('click', () => {
      if (xhr && xhr.readyState !== 4) {
          xhr.abort();

          // Afficher message animé
          const redirect = document.getElementById('redirectMessage');
          redirect.innerHTML = `
              <div class="flex items-center justify-center space-x-2 text-red-600 animate-pulse">
                  <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                  </svg>
                  <span>⛔ Envoi annulé, redirection en cours...</span>
              </div>
          `;
          redirect.classList.remove('hidden');

          document.getElementById('uploadDetails').classList.add('hidden');
          cancelBtn.classList.add('hidden');

          setTimeout(() => {
              window.location.href = '/';
          }, 3000);
      }
  });

  function formatSpeed(bytesPerSec) {
      const kb = 1024, mb = kb * 1024, gb = mb * 1024;
      if (bytesPerSec >= gb) return (bytesPerSec / gb).toFixed(2) + ' Go/s';
      if (bytesPerSec >= mb) return (bytesPerSec / mb).toFixed(2) + ' Mo/s';
      if (bytesPerSec >= kb) return (bytesPerSec / kb).toFixed(2) + ' ko/s';
      return bytesPerSec.toFixed(0) + ' o/s';
  }

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
