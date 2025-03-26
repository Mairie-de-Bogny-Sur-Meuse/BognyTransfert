<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UploadController
{
    public function index()
    {
        require_once __DIR__ . '/../views/upload/form.php';
    }

    public function confirmation()
    {
        require_once __DIR__ . '/../views/upload/confirmation.php';
    }

    public function handleUpload()
    {
        

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? null)) {
            $title = "Attaque CSRF détectée";
            $message = "❌ Attaque CSRF détectée lors de l'envoi des fichiers.";
            $code = 403;
            require_once __DIR__ . '/../views/errors/custom_error.php';
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!preg_match('/@bognysurmeuse\.fr$/', $email)) {
            $title = "Email non autorisé";
            $message = "Seules les adresses @bognysurmeuse.fr sont autorisées.";
            $code = 403;
            require_once __DIR__ . '/../views/errors/custom_error.php';
            return;
        }

        $uuid = bin2hex(random_bytes(16));
        $tempPath = $_ENV['TEMP_PATH'] . $uuid . '/';

        if (!mkdir($tempPath, 0755, true)) {
            $title = "Erreur dossier temporaire";
            $message = "Impossible de créer le dossier temporaire.";
            $code = 500;
            require_once __DIR__ . '/../views/errors/custom_error.php';
            return;
        }

        $savedFiles = [];
        $dangerous = ['php', 'exe', 'sh', 'bat', 'cmd'];
        $ignored = ['.DS_Store', 'Thumbs.db', '.gitkeep'];

        foreach (["files_flat", "files_tree"] as $key) {
            if (!empty($_FILES[$key])) {
                $files = $_FILES[$key];

                for ($i = 0; $i < count($files['name']); $i++) {
                    $relativePath = $files['full_path'][$i] ?? $files['name'][$i];
                    $relativePath = ltrim($relativePath, '/\\');
                    $filename = basename($relativePath);
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    $tmp = $files['tmp_name'][$i];

                    if (
                        empty($relativePath) ||
                        empty($tmp) ||
                        !is_uploaded_file($tmp) ||
                        str_starts_with($filename, '.') ||
                        in_array($filename, $ignored) ||
                        in_array($ext, $dangerous)
                    ) continue;

                    $destination = $tempPath . $relativePath;
                    $subDir = dirname($destination);
                    if (!is_dir($subDir)) mkdir($subDir, 0755, true);

                    if (!move_uploaded_file($tmp, $destination)) continue;
                    error_log("[UPLOAD] Fichier déplacé : $destination");
                    $savedFiles[] = $relativePath;
                }
            }
        }

        $_SESSION['pending_upload'] = [
            'uuid' => $uuid,
            'email' => $email,
            'password' => $password,
            'files' => $savedFiles
        ];

        $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        require_once __DIR__ . '/../models/EmailTokenModel.php';
        $tokenModel = new EmailTokenModel();
        $tokenModel->createToken($email, $code, $expires);

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
            $mail->Subject = 'Votre code de vérification';
            $mail->Body = "<p>Bonjour,<br>Votre code est : <strong>$code</strong><br>Valable 15 minutes.</p>";

            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur PHPMailer : " . $mail->ErrorInfo);
        }

        header("Location: /verify?email=" . urlencode($email));
        exit;
    }
}
