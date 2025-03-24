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

    <form action="/upload" method="POST" enctype="multipart/form-data" class="space-y-4">
      
      <!-- Email -->
      <div>
        <label for="email" class="block font-medium text-gray-700">Votre e-mail <span class="text-sm text-gray-500">(doit se terminer par @bognysurmeuse.fr)</span></label>
        <input type="email" name="email" id="email" required placeholder="exemple@bognysurmeuse.fr"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
      </div>

      <!-- Fichiers -->
      <div>
        <label for="files" class="block font-medium text-gray-700">Fichiers à envoyer</label>
        <input type="file" name="files[]" id="files" multiple required
               class="mt-1 block w-full text-sm text-gray-600 bg-gray-50 rounded-md border border-gray-300 shadow-sm">
        <p class="text-sm text-gray-500 mt-1">Taille totale max : 10 Go</p>
      </div>

      <!-- Mot de passe -->
      <div>
        <label for="password" class="block font-medium text-gray-700">Mot de passe (facultatif)</label>
        <input type="password" name="password" id="password"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <p class="text-sm text-gray-500 mt-1">Optionnel – protègera le lien de téléchargement.</p>
      </div>

      <!-- Bouton -->
      <div>
        <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md shadow">
          Envoyer les fichiers
        </button>
      </div>

    </form>
  </div>

</body>
</html>
