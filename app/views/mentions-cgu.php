<!-- views/cgu.php -->

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Conditions Générales d’Utilisation - BognyTransfert</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800">
    <?php include_once __DIR__ . '/partials/header.php'; ?>
    <div class="max-w-4xl mx-auto p-6 bg-white shadow-md mt-8 rounded-lg">
        <h1 class="text-3xl font-bold mb-6">Conditions Générales d’Utilisation (CGU)</h1>

        <p class="mb-4">
            Les présentes Conditions Générales d’Utilisation (CGU) régissent l’utilisation du service <strong>BognyTransfert</strong>, mis à disposition par la Ville de Bogny-sur-Meuse, destiné à faciliter l’échange temporaire de fichiers dans un cadre professionnel et institutionnel.
        </p>

        <h2 class="text-2xl font-semibold mt-8 mb-2">1. Objet du service</h2>
        <p class="mb-4">
            BognyTransfert permet aux utilisateurs autorisés de transmettre des fichiers volumineux via une plateforme web sécurisée, hébergée par la commune de Bogny-sur-Meuse. Ce service est strictement réservé à un usage administratif, professionnel ou public encadré.
        </p>

        <h2 class="text-2xl font-semibold mt-8 mb-2">2. Accès au service</h2>
        <p class="mb-4">
            L’accès au service BognyTransfert est restreint aux utilisateurs disposant d’une adresse e-mail professionnelle se terminant par <strong>@bognysurmeuse.fr</strong>. Toute tentative d’envoi depuis une adresse externe sera bloquée automatiquement.
        </p>
        <p class="mb-4">
            L’utilisation du service nécessite une vérification d’adresse e-mail par envoi d’un lien sécurisé, garantissant l’authenticité de l’utilisateur.
        </p>

        <h2 class="text-2xl font-semibold mt-8 mb-2">3. Acceptation des CGU</h2>
        <p class="mb-4">
            En accédant au service BognyTransfert, l’utilisateur reconnaît avoir lu, compris et accepté sans réserve l’intégralité des présentes CGU.
        </p>

        <h2 class="text-2xl font-semibold mt-8 mb-2">4. Fonctionnalités du service</h2>
        <ul class="list-disc ml-6 mb-4">
            <li>Envoi de fichiers jusqu’à 10 Go par transfert</li>
            <li>Quota de 200 Go par utilisateur et par mois</li>
            <li>Chiffrement des fichiers (aucun, AES, AES+RSA)</li>
            <li>Notification par e-mail aux destinataires</li>
            <li>Liens de téléchargement avec expiration automatique</li>
            <li>Gestion sécurisée des fichiers temporaires et archivés</li>
        </ul>

        <h2 class="text-2xl font-semibold mt-8 mb-2">5. Engagements de l’utilisateur</h2>
        <p class="mb-4">
            L’utilisateur s’engage à :
        </p>
        <ul class="list-disc ml-6 mb-4">
            <li>utiliser le service uniquement à des fins professionnelles ou administratives,</li>
            <li>ne pas transmettre de contenus illégaux ou sensibles sans autorisation,</li>
            <li>respecter les droits d’auteur et la confidentialité des données transférées,</li>
            <li>ne pas usurper l’identité d’un tiers ou utiliser une adresse e-mail frauduleuse.</li>
        </ul>

        <h2 class="text-2xl font-semibold mt-8 mb-2">6. Interdictions spécifiques</h2>
        <p class="mb-4">
            Il est formellement interdit :
        </p>
        <ul class="list-disc ml-6 mb-4">
            <li>d’utiliser le service pour transmettre des logiciels malveillants,</li>
            <li>de détourner le service pour contourner des systèmes de filtrage,</li>
            <li>d’en faire un usage commercial non autorisé,</li>
            <li>d’effectuer du spamming via les notifications d’envoi.</li>
        </ul>

        <h2 class="text-2xl font-semibold mt-8 mb-2">7. Durée de conservation des fichiers</h2>
        <p class="mb-4">
            Les fichiers envoyés via BognyTransfert sont supprimés automatiquement :
        </p>
        <ul class="list-disc ml-6 mb-4">
            <li>au bout de 15 minutes dans le dossier temporaire (si non confirmés),</li>
            <li>au bout de 30 jours pour les transferts publics,</li>
            <li>au bout de 90 jours pour les transferts archivés.</li>
        </ul>

        <h2 class="text-2xl font-semibold mt-8 mb-2">8. Sécurité et confidentialité</h2>
        <p class="mb-4">
            La Ville de Bogny-sur-Meuse met en œuvre des mesures de sécurité avancées :
        </p>
        <ul class="list-disc ml-6 mb-4">
            <li>Chiffrement AES-256 côté serveur</li>
            <li>Option de chiffrement hybride AES+RSA</li>
            <li>Transmission HTTPS avec validation TLS</li>
            <li>Authentification par token</li>
            <li>Architecture Zero Trust</li>
            <li>Traçabilité des accès et opérations critiques</li>
        </ul>

        <h2 class="text-2xl font-semibold mt-8 mb-2">9. Limitation de responsabilité</h2>
        <p class="mb-4">
            La Ville de Bogny-sur-Meuse ne pourra être tenue responsable en cas :
        </p>
        <ul class="list-disc ml-6 mb-4">
            <li>de perte de fichiers due à une erreur de l’utilisateur,</li>
            <li>d’interruption temporaire du service,</li>
            <li>de dysfonctionnement technique indépendant de sa volonté,</li>
            <li>d’intrusion extérieure malgré les protections mises en place.</li>
        </ul>

        <h2 class="text-2xl font-semibold mt-8 mb-2">10. Propriété intellectuelle</h2>
        <p class="mb-4">
            L’ensemble des éléments composant le service BognyTransfert (logo, code source, interface, contenus) sont protégés par le droit d’auteur. Toute reproduction, totale ou partielle, sans autorisation préalable est interdite.
        </p>

        <h2 class="text-2xl font-semibold mt-8 mb-2">11. Évolutions du service</h2>
        <p class="mb-4">
            La Ville de Bogny-sur-Meuse se réserve le droit de faire évoluer les fonctionnalités du service à tout moment, notamment pour des raisons de sécurité, de performance ou de conformité réglementaire.
        </p>

        <h2 class="text-2xl font-semibold mt-8 mb-2">12. Suspension ou arrêt du service</h2>
        <p class="mb-4">
            Le service peut être suspendu temporairement ou définitivement en cas :
        </p>
        <ul class="list-disc ml-6 mb-4">
            <li>de maintenance critique,</li>
            <li>d’incident de sécurité majeur,</li>
            <li>d’usage frauduleux constaté,</li>
            <li>ou à l’initiative de la collectivité, avec ou sans préavis.</li>
        </ul>

        <h2 class="text-2xl font-semibold mt-8 mb-2">13. Loi applicable et juridiction</h2>
        <p class="mb-4">
            Les présentes CGU sont régies par le droit français. Tout litige relatif à leur interprétation ou leur exécution sera soumis aux tribunaux compétents d’instance dans le ressort de la Ville de Bogny-sur-Meuse.
        </p>

        <h2 class="text-2xl font-semibold mt-8 mb-2">14. Contact</h2>
        <p class="mb-4">
            Pour toute question relative au service ou à ces CGU, vous pouvez contacter :
            <br>
            <strong>informatique@bognysurmeuse.fr</strong>
        </p>

        <p class="text-sm text-gray-500 mt-8">
            Dernière mise à jour : <?= date("d/m/Y") ?>
        </p>
    </div>

    <?php include_once __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
