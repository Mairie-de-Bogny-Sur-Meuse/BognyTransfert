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
        $token = bin2hex(random_bytes(32));
        $totalSize = 0;

        foreach ($upload['files'] as $fileRelatif) {
            $chemin = rtrim($upload['uuid'], '/') . '/' . ltrim($fileRelatif, '/\\');
            $cheminComplet = rtrim($_ENV['TEMP_PATH'], '/') . '/' . $chemin;

            if (!file_exists($cheminComplet)) continue;

            $size = filesize($cheminComplet);
            $totalSize += $size;

            $fichierModel->create([
                'uuid' => $upload['uuid'],
                'email' => $upload['email'],
                'file_name' => basename($fileRelatif),
                'file_path' => $cheminComplet,
                'file_size' => $size,
                'password_hash' => !empty($upload['password']) ? password_hash($upload['password'], PASSWORD_DEFAULT) : null,
                'token' => $token,
                'token_expire' => date('Y-m-d H:i:s', strtotime('+30 days')),
            ]);

            if (empty($_SESSION['generated_link'])) {
                $_SESSION['generated_link'] = $_ENV['BaseUrl'] . '/download?token=' . urlencode($token);
            }
        }

        if (empty($_SESSION['generated_link'])) {
            $this->showError("Aucun fichier valide", "Aucun fichier valide n'a été traité. Impossible de générer un lien.", 500);
            return;
        }

        // Envoi de l'email
        $fileCount = count($upload['files']);
        $sizeFormatted = number_format($totalSize / (1024 * 1024), 2) . ' Mo';
        $expireDate = date('d/m/Y à H:i', strtotime('+30 days'));
        $downloadLink = $_SESSION['generated_link'];
        $hasPassword = !empty($upload['password']);

        ob_start();
        require __DIR__ . '/../views/emails/upload_confirmation.php';
        $body = ob_get_clean();

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
            $mail->addAddress($upload['email']);
            $mail->isHTML(true);
            $mail->Subject = '📬 Vos fichiers sont disponibles - BognyTransfert';
            $mail->Body = $body;
            $mail->send();
        } catch (Exception $e) {
            error_log('Erreur PHPMailer : ' . $mail->ErrorInfo);
        }

        unset($_SESSION['pending_upload']);
        header('Location: /upload/confirmation');
        exit;
    }

    private function showError(string $title, string $message, int $code)
    {
        require __DIR__ . '/../views/errors/custom_error.php';
    }
}
