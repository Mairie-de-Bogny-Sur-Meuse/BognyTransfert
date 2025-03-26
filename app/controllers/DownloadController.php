<?php
include_once 'Function.php';
class DownloadController
{
    public function handleDownload($token)
{
    $pdo = Database::connect();

    // 1. Récupération des fichiers liés au token
    $stmt = $pdo->prepare("SELECT * FROM uploads WHERE token = :token");
    $stmt->execute(['token' => SecureSql($token)]);
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$uploads) {
        $title = "Lien Invalide";
        $message = "Votre lien est invalide";
        $code = 400;
        require 'app/views/errors/custom_error.php';
        return;
    }

    // 2. Expiration
    $expire = new DateTime($uploads[0]['token_expire']);
    if (new DateTime() > $expire) {
        $title = "Lien Expirer";
        $message = "Votre lien est expirer";
        $code = 403;
        require 'app/views/errors/custom_error.php';
        return;
    }

    // 3. Mot de passe
    $passwordHash = $uploads[0]['password_hash'];
    if ($passwordHash && !isset($_POST['password'])) {
        require __DIR__ . '/../views/download_password.php';
        return;
    }

    if ($passwordHash && !password_verify($_POST['password'], $passwordHash)) {
        echo "<p class='text-red-600 text-center'>Mot de passe incorrect.</p>";
        require __DIR__ . '/../views/download_password.php';
        return;
    }

    // 4. Fichiers
    $token = $uploads[0]['token'];
    $uuid = $uploads[0]['uuid'];

    // On rend les variables accessibles dans la vue
    $_uploads = $uploads;
    $_token = $token;
    $_uuid = $uuid;

    require __DIR__ . '/../views/download_files.php';


}


private function serveZip(array $uploads)
{
    $zip = new ZipArchive();
    $tmpZipPath = sys_get_temp_dir() . '/download_' . uniqid() . '.zip';

    if ($zip->open($tmpZipPath, ZipArchive::CREATE) !== true) {
        $title = "Création d'archive ZIP'";
        $message = "Erreur : Impossible de créer l’archive ZIP.";
        $code = 500;
        require 'app/views/errors/custom_error.php';
        return;
    }

    foreach ($uploads as $file) {
        if (file_exists($file['file_path'])) {
            $zip->addFile($file['file_path'], $file['file_name']);
        }
    }

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="fichiers_BognyTransfert.zip"');
    header('Content-Length: ' . filesize($tmpZipPath));
    readfile($tmpZipPath);

    unlink($tmpZipPath);
    exit;
}
private function serveFile($path, $originalName)
{
    if (!file_exists($path)) {
        http_response_code(404);
        echo "Fichier introuvable.";
        return;
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($originalName) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

public function downloadSingle($uuid, $fileName)
{
    $pdo = Database::connect();

    $stmt = $pdo->prepare("SELECT * FROM uploads WHERE uuid = :uuid AND file_name = :file_name");
    $stmt->execute([
        'uuid' => SecureSql($uuid),
        'file_name' => SecureSql($fileName)
    ]);

    $file = $stmt->fetch();
    if (!$file || !file_exists($file['file_path'])) {
        $title = "Fichier Introuvable";
        $message = "Le fichier est introuvable";
        $code = 400;
        require 'app/views/errors/custom_error.php';
        return;
    }

    $this->serveFile($file['file_path'], $file['file_name']);
}
public function downloadZip($uuid)
{
    $pdo = Database::connect();
    $stmt = $pdo->prepare("SELECT * FROM uploads WHERE uuid = :uuid");
    $stmt->execute(['uuid' => SecureSql($uuid)]);
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$uploads) {
        $title = "Fichier non trouver";
        $message = "Le fichier non trouver";
        $code = 400;
        require 'app/views/errors/custom_error.php';
        return;
    }

    $this->serveZip($uploads);
}
}
