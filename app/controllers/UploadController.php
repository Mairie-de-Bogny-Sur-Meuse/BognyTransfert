<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Contrôleur responsable de l'envoi de fichiers :
 * - Affichage du formulaire
 * - Traitement du téléversement
 * - Redirection vers vérification ou confirmation selon le statut
 */
class UploadController
{
    public function index()
    {
        $emailForm = $_SESSION['user_email'];
        require_once __DIR__ . '/../views/upload/form.php';
    }

    public function confirmation()
    {
        require_once __DIR__ . '/../views/upload/confirmation.php';
    }

    public function handleUpload()
    {
        ignore_user_abort(true);
        ob_start();
        $this->stopIfDisconnected();

        require_once __DIR__ . '/../models/SecurityModel.php';
        $security = new SecurityModel();

        // 🔐 Vérification CSRF
        if (!$security->checkCsrfToken($_POST['csrf_token'] ?? null)) {
            return $this->showError("Attaque CSRF détectée", "❌ Le token CSRF est invalide ou manquant.", 403);
        }

        // ❌ Annulation d'envoi
        if (!empty($_POST['cancel_upload']) && $_POST['cancel_upload'] === '1') {
            if (!empty($_SESSION['pending_upload']['uuid'])) {
                $uuid = $_SESSION['pending_upload']['uuid'];
                $this->deleteFolder(rtrim($_ENV['TEMP_PATH'], '/') . '/' . $uuid . '/');

                require_once __DIR__ . '/../models/FileKeyModel.php';
                $keyModel = new FileKeyModel();
                $keyModel->deleteKeysByUuid($uuid);
            }

            unset($_SESSION['pending_upload']);
            echo json_encode(['status' => 'cancelled']);
            return;
        }

        // 📥 Données du formulaire
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $uploadOption = $_POST['upload_option'] ?? 'email';
        $recipient = trim($_POST['recipient_email'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $encryptionLevel = $_POST['encryption_level'] ?? 'none';

        if (!preg_match('/@bognysurmeuse\.fr$/', $email)) {
            return $this->showError("Email non autorisé", "Seules les adresses @bognysurmeuse.fr sont autorisées.", 403);
        }

        if (!in_array($uploadOption, ['email', 'link_only'])) {
            return $this->showError("Option invalide", "L'option d'envoi est invalide.", 400);
        }

        if ($uploadOption === 'email' && (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL))) {
            return $this->showError("Email du destinataire manquant", "Adresse e-mail du destinataire invalide.", 400);
        }

        // 📂 Dossier temporaire
        $uuid = bin2hex(random_bytes(16));
        $tempPath = rtrim($_ENV['TEMP_PATH'], '/') . '/' . $uuid . '/';

        if (!mkdir($tempPath, 0755, true)) {
            return $this->showError("Erreur serveur", "Impossible de créer le dossier temporaire.", 500);
        }

        $savedFiles = [];
        $dangerous = ['php', 'exe', 'sh', 'bat', 'cmd'];
        $ignored = ['.DS_Store', 'Thumbs.db', '.gitkeep'];

        require_once __DIR__ . '/../models/FileKeyModel.php';
        $keyModel = new FileKeyModel();

        foreach (["files_flat", "files_tree"] as $key) {
            $this->stopIfDisconnected();
            if (!empty($_FILES[$key])) {
                $files = $_FILES[$key];
                for ($i = 0; $i < count($files['name']); $i++) {
                    $relativePath = $files['full_path'][$i] ?? $files['name'][$i];
                    $relativePath = ltrim($relativePath, '/\\');
                    $filename = basename($relativePath);
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    $tmp = $files['tmp_name'][$i];

                    if (in_array($ext, $dangerous)) {
                        return $this->showError("Fichier interdit", "❌ L'extension .$ext est interdite.", 400);
                    }

                    if (empty($relativePath) || empty($tmp) || !is_uploaded_file($tmp) || str_starts_with($filename, '.') || in_array($filename, $ignored)) {
                        continue;
                    }

                    $destination = $tempPath . $relativePath;
                    $subDir = dirname($destination);
                    if (!is_dir($subDir)) mkdir($subDir, 0755, true);

                    if (!move_uploaded_file($tmp, $destination)) {
                        continue;
                    }

                    $originalSize = $files['size'][$i];
                    $writtenSize = filesize($destination);
                    if ($originalSize && $writtenSize < $originalSize * 0.95) {
                        continue;
                    }

                    // 🔐 Chiffrement
                    if ($encryptionLevel !== 'none') {
                        $aesKey = random_bytes(32);
                        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
                        $data = file_get_contents($destination);
                        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $aesKey, 0, $iv);
                        file_put_contents($destination, $iv . $encryptedData);

                        $encryptedKey = null;
                        if ($encryptionLevel === 'aes') {
                            $masterKey = $_ENV['MASTER_ENCRYPTION_KEY'];
                            $encryptedKey = openssl_encrypt($aesKey, 'aes-256-cbc', $masterKey, 0, $iv);
                        } elseif ($encryptionLevel === 'aes_rsa') {
                            $publicKeyPath = $_ENV['RSA_PUBLIC_KEY_PATH'];
                            $publicKey = file_get_contents($publicKeyPath);
                            if (!$publicKey) {
                                return $this->showError("Erreur chiffrement", "Clé RSA introuvable.", 500);
                            }
                            openssl_public_encrypt($aesKey, $encryptedKeyRaw, $publicKey);
                            $encryptedKey = base64_encode($encryptedKeyRaw);
                        }

                        $keyModel->storeKey([
                            'uuid' => $uuid,
                            'file_name' => $filename,
                            'encrypted_key' => $encryptedKey,
                            'iv' => $iv,
                            'encryption_level' => $encryptionLevel
                        ]);
                    }

                    $savedFiles[] = $relativePath;
                }
            }
        }

        if (empty($savedFiles)) {
            $this->deleteFolder($tempPath);
            return $this->showError("Aucun fichier valide", "❌ Aucun fichier accepté.", 400);
        }

        // 🧾 Stockage des infos du transfert en session
        $_SESSION['pending_upload'] = [
            'uuid' => $uuid,
            'email' => $email,
            'password' => $password,
            'files' => $savedFiles,
            'recipient' => $recipient,
            'message' => $message,
            'upload_option' => $uploadOption,
            'download_link' => rtrim($_ENV['BaseUrl'], '/') . "/d/$uuid",
            'encryption' => $encryptionLevel,
            'encryption_level' => $encryptionLevel
        ];

        // ✅ Utilisateur connecté = pas de code → redirection vers /upload/confirmation
        if (isset($_SESSION['user_id'])) {
            $_SESSION['confirmation_data'] = [
                'pending_upload' => $_SESSION['pending_upload'],
                'generated_link' => rtrim($_ENV['BaseUrl'], '/') . "/download?token=$uuid"
            ];
//            unset($_SESSION['pending_upload']);
            
            ob_end_clean();
            echo json_encode(['redirect' => '/upload/confirm-session']);
            flush();
            exit;

        }

        // 📧 Utilisateur non connecté → envoi code de vérification
        require_once __DIR__ . '/../models/EmailTokenModel.php';
        $tokenModel = new EmailTokenModel();
        $this->stopIfDisconnected();
        $existingToken = $tokenModel->getValidToken($email);

        if (!$existingToken) {
            $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            if ($tokenModel->getTokenByEmail($email)) {
                $tokenModel->updateToken($email, $code, $expires);
            } else {
                $tokenModel->createToken($email, $code, $expires);
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
            } catch (Exception $e) {
                return $this->showError("Erreur d'envoi", "Impossible d’envoyer le code à $email", 500);
            }
        }

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'redirect' => isset($_SESSION['user_id'])
                ? '/upload/confirmation'
                : '/verify?email=' . urlencode($email)
        ]);
        flush();
        exit;

    }

    private function stopIfDisconnected()
    {
        if (connection_aborted() || connection_status() !== CONNECTION_NORMAL) {
            ob_end_clean();
            exit;
        }
    }

    private function showError(string $title, string $message, int $code)
    {
        http_response_code($code);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode([
                'status' => "$title",
                'error' => "❌ $message"
            ]);
            exit;
        }

        require __DIR__ . '/../views/errors/custom_error.php';
        exit;
    }

    private function deleteFolder(string $path)
    {
        if (!is_dir($path)) return;
        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') continue;
            $fullPath = $path . '/' . $item;
            is_dir($fullPath) ? $this->deleteFolder($fullPath) : unlink($fullPath);
        }
        rmdir($path);
    }
}
