<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérification de l'adresse email</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center">Vérification par code</h1>

        <form action="/verify/submit" method="post" class="space-y-6">
           
                <input type="hidden" id="email" name="email" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-300"
                       value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
            

            <div>
                <label for="code" class="block font-semibold mb-1">Code de vérification :</label>
                <input type="text" id="code" name="code" maxlength="10" required placeholder="Veuillez entrer le code de vérification"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div class="text-center">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">
                    Valider le code
                </button>
            </div>
        </form>
    </div>
</body>
</html>
