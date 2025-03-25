<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Transfert de fichiers sécurisé</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">

  <div class="bg-white shadow-md rounded-lg p-8 max-w-lg w-full">
    <h1 class="text-2xl font-semibold text-center mb-6 text-gray-800">Envoyer un fichier</h1>

    <!-- upload_form.php -->
<form id="uploadForm" enctype="multipart/form-data" class="space-y-4" method="POST">
  <!-- Email -->
  <div>
  <label for="email" class="block font-medium text-gray-700">Votre e-mail* <span class="text-sm text-gray-500">(doit se terminer par @bognysurmeuse.fr)</span></label>
  <input type="email" name="email" id="email" required
           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-2" placeholder="Veuillez entrer votre adresse e-mail" />
  </div>

  <!-- Mot de passe -->
  <div>
    <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe (optionnel)</label>
    <input type="text" name="password" id="password"
           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-2" placeholder="Optionnel – protègera le lien de téléchargement."/>
  </div>

  <!-- Sélection fichiers -->
  <div>
    <label for="files" class="block text-sm font-medium text-gray-700">Fichiers individuels</label>
    <input type="file" name="files[]" id="files" multiple class="mt-1 block w-full" />
  </div>

  <!-- Sélection dossier -->
  <div>
    <label for="folder" class="block text-sm font-medium text-gray-700">Ou dossier complet</label>
    <input type="file" name="files[]" id="folder" webkitdirectory directory multiple class="mt-1 block w-full" />
  </div>

  <!-- Barre de progression -->
  <div id="progressContainer" class="hidden w-full bg-gray-200 rounded h-4 overflow-hidden">
    <div id="progressBar" class="bg-indigo-600 h-full w-0 transition-all duration-300 ease-out"></div>
  </div>

  <!-- Bouton -->
  <button type="submit"
          class="mt-4 w-full bg-indigo-600 text-white font-semibold py-2 px-4 rounded hover:bg-indigo-700">
    Envoyer
  </button>

  <!-- Statut -->
  <div id="uploadStatus" class="mt-3 text-sm text-gray-600"></div>
</form>

<script>
  const form = document.getElementById('uploadForm');
  const progressBar = document.getElementById('progressBar');
  const progressContainer = document.getElementById('progressContainer');
  const status = document.getElementById('uploadStatus');

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(form);
    const xhr = new XMLHttpRequest();

    xhr.open('POST', '/upload', true);

    xhr.upload.addEventListener('progress', function (e) {
      if (e.lengthComputable) {
        const percent = Math.round((e.loaded / e.total) * 100);
        progressContainer.classList.remove('hidden');
        progressBar.style.width = percent + '%';
        status.innerText = `Téléchargement : ${percent}%`;
      }
    });

    xhr.onload = function () {
      if (xhr.status === 200) {
        status.innerText = '✅ Upload terminé. Redirection...';
        const email = form.querySelector('input[name="email"]').value;
        window.location.href = '/verify?email=' + encodeURIComponent(email);
      } else {
        status.innerText = '❌ Erreur pendant l’envoi.';
      }
    };

    xhr.send(formData);
  });
</script>

  </div>

</body>
</html>
