<?php

class VerificationController
{
    public function handleForm()
    {
        $uuid = $_POST['uuid'] ?? '';
        $code = $_POST['code'] ?? '';
        $pdo = Database::connect();

        $stmt = $pdo->prepare("SELECT * FROM uploads WHERE uuid = :uuid LIMIT 1");
        $stmt->execute(['uuid' => $uuid]);
        $upload = $stmt->fetch();

        if (!$upload || $upload['code_2fa'] !== $code) {
            die("Code invalide ou lien incorrect.");
        }

        if (new DateTime() > new DateTime($upload['verification_expires_at'])) {
            die("Le code a expiré.");
        }

        $stmt = $pdo->prepare("UPDATE uploads SET verified_at = NOW() WHERE uuid = :uuid");
        $stmt->execute(['uuid' => $uuid]);

        echo "<h2 class='text-center text-green-600 mt-10'>✅ Vérification réussie ! Votre lien est maintenant actif.</h2>";
        echo "<p class='text-center mt-4'><a href='/download/" . $upload['token'] . "' class='text-blue-600 underline'>Accéder aux fichiers</a></p>";
    }
}
