<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

class VerifyController
{
    public function index()
    {
        require_once __DIR__ . '/../views/verify/form.php';
    }

    public function submitCode()
{
    $debug = ($_ENV['DEBUG_LOG'] ?? false) === 'true';

    if (!isset($_POST['email'], $_POST['code'])) {
        $this->showError("Paramètres manquants", "Email ou code manquant.", 400);
        return;
    }

    $email = trim($_POST['email']);
    $code = trim($_POST['code']);

    require_once __DIR__ . '/../models/EmailTokenModel.php';
    $tokenModel = new EmailTokenModel();

    if (!$tokenModel->validateToken($email, $code)) {
        $this->showError("Code invalide ou expiré", "Le code saisi est incorrect ou a expiré.", 403);
        return;
    }

    $tokenModel->markAsValidated($email, $code);

    if (!isset($_SESSION['pending_upload'])) {
        $this->showError("Erreur de session", "Aucun upload présent dans la session.", 400);
        return;
    }

    require_once __DIR__ . '/../models/FichierModel.php';
    $fichierModel = new FichierModel();
    $upload = $_SESSION['pending_upload'];
    $uuid = $upload['uuid'];
    $uploadOption = $upload['upload_option'] ?? 'link_only';
    $recipient = $upload['recipient'] ?? null;
    $message = $upload['message'] ?? '';
    $totalSize = 0;
    $token = bin2hex(random_bytes(32));

    $sourcePath = rtrim($_ENV['TEMP_PATH'], '/') . '/' . $uuid;
    $targetPath = rtrim($_ENV['UPLOAD_PATH'], '/') . '/' . $uuid;

    if (!is_dir($targetPath)) {
        mkdir($targetPath, 0755, true);
        if ($debug) error_log("[VERIFY] 📁 Dossier cible créé : $targetPath");
    }

    foreach ($upload['files'] as $fileRelatif) {
        $src = $sourcePath . '/' . ltrim($fileRelatif, '/\\');
        $dest = $targetPath . '/' . ltrim($fileRelatif, '/\\');

        $subDir = dirname($dest);
        if (!is_dir($subDir)) mkdir($subDir, 0755, true);

        if (file_exists($src)) {
            rename($src, $dest);
            if ($debug) error_log("[VERIFY] ✅ Fichier déplacé : $src -> $dest");

            $size = filesize($dest);
            $totalSize += $size;

            $fichierModel->create([
                'uuid' => $uuid,
                'email' => $email,
                'file_name' => basename($fileRelatif),
                'file_path' => $dest,
                'file_size' => $size,
                'password_hash' => !empty($upload['password']) ? password_hash($upload['password'], PASSWORD_DEFAULT) : null,
                'token' => $token,
                'token_expire' => date('Y-m-d H:i:s', strtotime('+30 days')),
            ]);
        } else {
            if ($debug) error_log("[VERIFY] ❌ Fichier introuvable (pas déplacé) : $src");
        }
    }

    // Nettoyage TEMP
    if (is_dir($sourcePath)) {
        $it = new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($sourcePath);
        if ($debug) error_log("[VERIFY] 🧹 Dossier temporaire supprimé : $sourcePath");
    }

    if (empty($_SESSION['generated_link'])) {
        $_SESSION['generated_link'] = $_ENV['BaseUrl'] . '/download?token=' . urlencode($token);
    }

    if (empty($_SESSION['generated_link'])) {
        $this->showError("Aucun fichier valide", "Aucun fichier valide n'a été traité. Impossible de générer un lien.", 500);
        return;
    }

    // Envoi des emails
    $fileCount = count($upload['files']);
    $sizeFormatted = number_format($totalSize / (1024 * 1024), 2) . ' Mo';
    $expireDate = date('d/m/Y à H:i', strtotime('+30 days'));
    $downloadLink = $_SESSION['generated_link'];
    $hasPassword = !empty($upload['password']);
    $encryptionLevel = $upload['encryption_level'] ?? 'none';

    ob_start();
    require __DIR__ . '/../views/emails/upload_confirmation.php';
    $body = ob_get_clean();

    // Envoi à l’expéditeur
    try {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'quoted-printable';
        $mail->isSMTP();
        $mail->Host = $_ENV['EMAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['EMAIL_USER'];
        $mail->Password = $_ENV['EMAIL_PASSWORD'];
        $mail->SMTPSecure = 'ssl';
        $mail->Port = (int) $_ENV['EMAIL_PORT'];

        $mail->setFrom($_ENV['EMAIL_FROM'], $_ENV['EMAIL_FROM_NAME']);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = '📬 Vos fichiers sont disponibles - BognyTransfert';
        $mail->Body = $body;
        $mail->send();
    } catch (Exception $e) {
        error_log("Erreur PHPMailer (expéditeur) : " . $mail->ErrorInfo);
    }

    // Envoi au destinataire si nécessaire
    if ($uploadOption === 'email' && $recipient) {
        try {
            $encryptionLevel = $upload['encryption_level'] ?? 'none';
            ob_start();
            require __DIR__ . '/../views/emails/upload_notification.php';
            $notifBody = ob_get_clean();

            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'quoted-printable';
            $mail->isSMTP();
            $mail->Host = $_ENV['EMAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['EMAIL_USER'];
            $mail->Password = $_ENV['EMAIL_PASSWORD'];
            $mail->SMTPSecure = 'ssl';
            $mail->Port = (int) $_ENV['EMAIL_PORT'];
            $mail->setFrom($_ENV['EMAIL_FROM'], $_ENV['EMAIL_FROM_NAME']);
            $mail->addAddress($recipient);
            $mail->isHTML(true);
            $mail->Subject = "📁 Fichiers reçus via BognyTransfert";
            $mail->Body = $notifBody;
            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur PHPMailer (destinataire) : " . $mail->ErrorInfo);
        }
    }

    $_SESSION['confirmation_data'] = [
        'generated_link' => $_SESSION['generated_link'],
        'pending_upload' => $upload,
    ];

    unset($_SESSION['pending_upload'], $_SESSION['generated_link']);
    header('Location: /upload/confirmation');
    exit;
}


    private function showError(string $title, string $message, int $code)
    {
        require __DIR__ . '/../views/errors/custom_error.php';
    }
}
