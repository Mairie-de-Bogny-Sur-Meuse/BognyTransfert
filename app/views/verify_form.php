<?php
session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Vérification d'identité</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen px-4">
  <form method="POST" action="/verify" class="bg-white p-6 rounded-lg shadow-md max-w-md w-full">
    <h1 class="text-xl font-semibold text-gray-700 mb-4">Code de vérification</h1>
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="uuid" value="<?= htmlspecialchars($_GET['uuid'] ?? '') ?>">
    <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
    <label for="code" class="block text-sm text-gray-700">Entrez le code reçu par e-mail :</label>
    <input type="text" name="code" id="code" maxlength="6" required
           class="mt-2 w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mb-4">

    <button type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-md font-semibold">
      Valider
    </button>
  </form>
</body>
</html>
