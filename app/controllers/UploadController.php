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
            $this->showError("Attaque CSRF détectée", "❌ Attaque CSRF détectée lors de l'envoi des fichiers.", 403);
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $uploadOption = $_POST['upload_option'] ?? 'email';
        $recipient = trim($_POST['recipient_email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (!preg_match('/@bognysurmeuse\.fr$/', $email)) {
            $this->showError("Email non autorisé", "Seules les adresses @bognysurmeuse.fr sont autorisées.", 403);
            return;
        }

        if (!in_array($uploadOption, ['email', 'link_only'])) {
            $this->showError("Option invalide", "L'option d'envoi choisie est invalide.", 400);
            return;
        }

        if ($uploadOption === 'email' && (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL))) {
            $this->showError("Email du destinataire manquant", "Vous devez fournir une adresse e-mail valide du destinataire.", 400);
            return;
        }

        $uuid = bin2hex(random_bytes(16));
        $tempPath = rtrim($_ENV['TEMP_PATH'], '/') . '/' . $uuid . '/';

        if (!mkdir($tempPath, 0755, true)) {
            $this->showError("Erreur dossier temporaire", "Impossible de créer le dossier temporaire.", 500);
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

        $linkBase = rtrim($_ENV['BASE_URL'] ?? 'https://dl.bognysurmeuse.fr', '/');
        $downloadLink = "$linkBase/d/$uuid";

        $_SESSION['pending_upload'] = [
            'uuid' => $uuid,
            'email' => $email,
            'password' => $password,
            'files' => $savedFiles,
            'recipient' => $recipient,
            'message' => $message,
            'upload_option' => $uploadOption,
            'download_link' => $downloadLink
        ];

        // === Gestion du code avec contrôle expiration + envoi conditionnel
        require_once __DIR__ . '/../models/EmailTokenModel.php';
        $tokenModel = new EmailTokenModel();
        $existingToken = $tokenModel->getValidToken($email);

        if ($existingToken) {
            $code = $existingToken['token'];
            error_log("[UPLOAD] Code déjà existant et valide pour $email : $code");
        } else {
            $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            if ($tokenModel->getTokenByEmail($email)) {
                $tokenModel->updateToken($email, $code, $expires);
                error_log("[UPLOAD] Code mis à jour pour $email : $code");
            } else {
                $tokenModel->createToken($email, $code, $expires);
                error_log("[UPLOAD] Nouveau code généré pour $email : $code");
            }

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
                $mail->Body = "<p>Bonjour,<br>Votre code de vérification est : <strong>$code</strong><br>Ce code est valable pendant 15 minutes.</p>";

                $mail->send();
                error_log("[UPLOAD] Email de vérification envoyé à $email");
            } catch (Exception $e) {
                error_log("Erreur PHPMailer : " . $mail->ErrorInfo);
                $this->showError("Erreur d'envoi", "Impossible d’envoyer le code de vérification à l’adresse : $email", 500);
                return;
            }
        }

        header("Location: /verify?email=" . urlencode($email));
        exit;
    }

    private function showError(string $title, string $message, int $code)
    {
        require __DIR__ . '/../views/errors/custom_error.php';
    }
}
