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
            Email : informatique@bognysurmeuse.fr
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
            Vous pouvez demander l’accès, la rectification ou la suppression de vos données personnelles par email à <a href="mailto:informatique@bognysurmeuse.fr" class="text-blue-600 underline">informatique@bognysurmeuse.fr</a>.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-2">5. Sécurité</h2>
        <p class="mb-4">
            Le service applique les normes de sécurité les plus élevées : chiffrement AES/RSA, transmission sécurisée, stockage contrôlé, architecture Zero Trust, etc.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-2">6. Conditions Générales d’Utilisation (CGU)</h2>
        <p class="mb-4">
            L’utilisation du service <strong>BognyTransfert</strong> implique l’acceptation pleine et entière des présentes conditions générales d’utilisation. Tout utilisateur s’engage à les respecter.
        </p>

        <h3 class="text-lg font-semibold mt-4 mb-2">6.1. Utilisation responsable du service</h3>
        <p class="mb-4">
            Le service est destiné uniquement à l’échange temporaire de fichiers dans un cadre professionnel ou administratif. Il est interdit d’utiliser le service pour :
        </p>
        <ul class="list-disc ml-6 mb-4">
            <li>transférer des fichiers contenant des contenus illégaux, diffamatoires, violents, pornographiques ou incitant à la haine,</li>
            <li>partager des fichiers violant les droits d’auteur, brevets, marques ou secrets commerciaux,</li>
            <li>diffuser des virus, chevaux de Troie, ou tout autre code malveillant,</li>
            <li>utiliser le service à des fins de spamming ou d’attaques informatiques.</li>
        </ul>

        <h3 class="text-lg font-semibold mt-4 mb-2">6.2. Responsabilités de l’utilisateur</h3>
        <p class="mb-4">
            L’utilisateur est seul responsable des fichiers qu’il transfère via la plateforme. Il lui appartient de s’assurer que les fichiers ne portent pas atteinte à des tiers et respectent la législation en vigueur.
        </p>

        <h3 class="text-lg font-semibold mt-4 mb-2">6.3. Durée de conservation</h3>
        <p class="mb-4">
            Les fichiers sont automatiquement supprimés selon les règles suivantes :
        </p>
        <ul class="list-disc ml-6 mb-4">
            <li>Fichiers temporaires : suppression automatique après 15 minutes,</li>
            <li>Fichiers publics : suppression automatique après 30 jours,</li>
            <li>Fichiers archivés : suppression automatique après 90 jours.</li>
        </ul>

        <h3 class="text-lg font-semibold mt-4 mb-2">6.4. Limitation de responsabilité</h3>
        <p class="mb-4">
            Bien que la Ville de Bogny-sur-Meuse mette en œuvre des mesures de sécurité élevées, elle ne peut garantir l’absence totale de défaillance technique, de perte de données ou d’interruption de service. En conséquence :
        </p>
        <ul class="list-disc ml-6 mb-4">
            <li>l’utilisateur accepte d’utiliser le service à ses propres risques,</li>
            <li>la Ville de Bogny-sur-Meuse ne pourra être tenue responsable en cas de perte, altération, ou interception de fichiers,</li>
            <li>aucune indemnisation ne pourra être réclamée en cas de dysfonctionnement ou d’indisponibilité temporaire du service.</li>
        </ul>

        <h3 class="text-lg font-semibold mt-4 mb-2">6.5. Modifications des CGU</h3>
        <p class="mb-4">
            La Ville de Bogny-sur-Meuse se réserve le droit de modifier les présentes conditions à tout moment. Les utilisateurs seront informés des changements via le site. L’utilisation continue du service vaut acceptation des conditions mises à jour.
        </p>


        <p class="text-sm text-gray-500 mt-8">
            Dernière mise à jour : <?= date("d/m/Y") ?>
        </p>
    </div>

    <?php include_once __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
