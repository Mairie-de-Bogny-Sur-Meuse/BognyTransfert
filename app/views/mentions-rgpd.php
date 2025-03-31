<!-- views/mentions-rgpd.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mentions légales et RGPD - BognyTransfert</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800">
    <div class="max-w-4xl mx-auto p-6 bg-white shadow-md mt-8 rounded-lg">
        <h1 class="text-2xl font-bold mb-4">Mentions Légales, RGPD & CGU</h1>

        <h2 class="text-xl font-semibold mt-6 mb-2">1. Responsable du traitement</h2>
        <p class="mb-4">
            Ville de Bogny-sur-Meuse<br>
            1 Place de l'Hôtel de Ville, 08120 Bogny-sur-Meuse<br>
            Email : mairie@bognysurmeuse.fr
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-2">2. Finalité des données collectées</h2>
        <p class="mb-4">
            Les données collectées ont pour but de permettre l’utilisation sécurisée du service d’envoi de fichiers :
            <ul class="list-disc ml-6">
                <li>Gestion des transferts</li>
                <li>Envoi d’e-mails de notification</li>
                <li>Traçabilité et sécurité</li>
            </ul>
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-2">3. Durée de conservation</h2>
        <p class="mb-4">
            <ul class="list-disc ml-6">
                <li>Fichiers temporaires : supprimés après 15 minutes</li>
                <li>Fichiers publics : supprimés après 30 jours</li>
                <li>Fichiers archivés : supprimés après 90 jours</li>
            </ul>
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-2">4. Vos droits</h2>
        <p class="mb-4">
            Vous pouvez demander l’accès, la rectification ou la suppression de vos données personnelles par email à <a href="mailto:mairie@bognysurmeuse.fr" class="text-blue-600 underline">mairie@bognysurmeuse.fr</a>.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-2">5. Sécurité</h2>
        <p class="mb-4">
            Le service applique les normes de sécurité les plus élevées : chiffrement AES/RSA, transmission sécurisée, stockage contrôlé, architecture Zero Trust, etc.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-2">6. Conditions Générales d’Utilisation (CGU)</h2>
        <p class="mb-4">
            En utilisant ce service :
            <ul class="list-disc ml-6">
                <li>Vous acceptez de ne pas transférer de contenus illicites ou dangereux.</li>
                <li>Vous acceptez que vos fichiers soient supprimés automatiquement selon les délais définis.</li>
                <li>Vous êtes seul responsable des données que vous transférez.</li>
                <li>Le service est fourni sans garantie. En cas de perte de données, la Ville de Bogny-sur-Meuse ne pourra être tenue responsable.</li>
            </ul>
        </p>

        <p class="text-sm text-gray-500 mt-8">
            Dernière mise à jour : <?= date("d/m/Y") ?>
        </p>
    </div>

    <?php include_once __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
