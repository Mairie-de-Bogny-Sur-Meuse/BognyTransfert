<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$maxTotalSize = getenv('MAX_UPLOAD_SIZE') ?: 10 * 1024 * 1024 * 1024; // 10 Go par défaut
$maxFileSize = getenv('MAX_SIZE_PER_TRANSFER') ?: 2 * 1024 * 1024 * 1024; // 2 Go par défaut
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

    <form
      id="upload-form"
      action="/upload/handleUpload"
      method="post"
      enctype="multipart/form-data"
      class="space-y-6"
      data-max-total-size="<?php echo $maxTotalSize; ?>"
      data-max-file-size="<?php echo $maxFileSize; ?>"
    >
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      <input type="hidden" name="cancel_upload" id="cancel_upload" value="0">

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
        <button type="button" id="clear-files" class="text-red-600 underline ml-4 hidden">🗑️ Vider la sélection</button>
      </div>

      <div id="file-info" class="text-sm text-gray-600"></div>
      <div id="error-message" class="text-red-600 text-sm mt-2 font-semibold"></div>

      <div class="text-center">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">Envoyer</button>
      </div>
    </form>

    <div id="upload-progress" class="mt-6 hidden">
      <div id="progressContainer" class="w-full bg-gray-300 rounded h-4 overflow-hidden">
        <div id="progressBar" class="h-4 w-0 text-white text-center text-xs font-semibold transition-all duration-300 ease-in-out progress-bar-animated">0%</div>
      </div>
      <p id="progressText" class="text-sm mt-2 text-center">Chargement : <span id="progressValue">0%</span></p>
      <p id="uploadDetails" class="text-sm text-center mt-1">🔄 <span id="uploadSpeed">0</span> | <span id="uploadSent">0 / 0</span></p>
      <p id="uploadETA" class="text-sm text-center mt-1 text-gray-600 hidden">⏳ Temps estimé restant : <span id="etaValue">Calcul...</span></p>
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

  <script src="/assets/js/upload.js"></script>
</body>
</html>
