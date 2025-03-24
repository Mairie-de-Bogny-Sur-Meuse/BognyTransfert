<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mot de passe requis</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen px-4">
  <form method="POST" class="bg-white p-6 rounded-lg shadow-md max-w-md w-full">
    <h1 class="text-xl font-semibold text-gray-700 mb-4">Mot de passe requis</h1>
    <p class="text-sm text-gray-500 mb-3">Ce lien est protégé par un mot de passe.</p>

    <input type="password" name="password" placeholder="Mot de passe"
           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mb-4">

    <button type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-md font-semibold">
      Télécharger
    </button>
  </form>
</body>
</html>
