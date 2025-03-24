<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Transfert réussi</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">

  <div class="bg-white shadow-md rounded-lg p-8 max-w-xl w-full text-center">
    <h1 class="text-2xl font-bold text-green-600 mb-4">✅ Fichiers envoyés avec succès !</h1>

    <p class="text-gray-700 mb-2">Voici votre lien de téléchargement sécurisé :</p>

    <div class="bg-gray-100 border border-gray-300 rounded-md p-4 break-all mb-4">
      <a href="<?= htmlspecialchars($downloadUrl) ?>" class="text-blue-600 hover:underline">
        <?= htmlspecialchars($downloadUrl) ?>
      </a>
    </div>

    <p class="text-sm text-gray-500 italic">Un e-mail contenant ce lien a été envoyé à votre adresse.</p>

    <a href="/upload-form" class="inline-block mt-6 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded">
      Envoyer un autre fichier
    </a>
  </div>

</body>
</html>
