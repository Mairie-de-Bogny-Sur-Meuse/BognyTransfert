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
        $uploadOption = $upload['upload_option'] ?? 'link_only';
        $recipient = $upload['recipient'] ?? null;
        $message = $upload['message'] ?? '';

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
        
        if ($uploadOption === 'email' && $recipient) {
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
                $mail->addCustomHeader('X-Mailer', 'BognyTransfert');
                $mail->addCustomHeader('X-Originating-IP', $_SERVER['SERVER_ADDR']);
                $mail->setFrom($_ENV['EMAIL_FROM'], $_ENV['EMAIL_FROM_NAME']);
                $mail->addAddress($recipient);
                $mail->isHTML(true);
                $mail->Subject = "📁 Fichier disponible via BognyTransfert";
        
                $body = '
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                  <meta charset="UTF-8">
                  <title>Fichier partagé - BognyTransfert</title>
                </head>
                <body style="font-family:Arial, sans-serif; background-color:#f9f9f9; padding:20px; margin:0;">
                  <div style="max-width:600px; margin:auto; background-color:#ffffff; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.05);">
                    
                    <div style="text-align:center; margin-bottom:20px;">
                      <img src="' . rtrim($_ENV['BaseUrl'], '/') . '/assets/img/BOGNY_logo_Gradient.png" alt="Logo Bogny-sur-Meuse" style="height:60px;">
                    </div>
                
                    <h2 style="color:#003366;">Un fichier est à votre disposition</h2>
                
                    <p style="color:#333;">Bonjour,</p>
                
                    <p style="color:#333;">Vous avez reçu un ou plusieurs fichiers via <strong>BognyTransfert</strong>.</p>
                
                    <p style="color:#333;">
                      <strong>Lien de téléchargement :</strong><br>
                      <a href="' . htmlspecialchars($downloadLink) . '" style="color:#1a73e8; word-break:break-all;">' . htmlspecialchars($downloadLink) . '</a>
                    </p>';
                
                if (!empty($message)) {
                    $body .= '
                    <p style="color:#333;"><strong>Message de l’expéditeur :</strong></p>
                    <blockquote style="border-left:4px solid #ccc; margin:10px 0; padding-left:10px; color:#555;">' . nl2br(htmlspecialchars($message)) . '</blockquote>';
                }
                
                $body .= '
                    <ul style="color:#333; padding-left:20px;">
                      <li><strong>Nombre de fichiers :</strong> ' . $fileCount . '</li>
                      <li><strong>Taille totale :</strong> ' . $sizeFormatted . '</li>
                      <li><strong>Expiration du lien :</strong> ' . $expireDate . '</li>';
                
                if ($hasPassword) {
                    $body .= '<li><strong>Mot de passe requis :</strong> <code style="background:#f3f3f3; padding:2px 4px;">' . htmlspecialchars($upload['password']) . '</code></li>';
                }
                
                $body .= '
                    </ul>
                
                    <p style="font-size:13px; color:#888;">Ce lien est valide pour une durée de 30 jours. Passé ce délai, il ne sera plus accessible.</p>
                
                    <p style="color:#333;">Cordialement,<br>📮 BognyTransfert<br>📍 Mairie de Bogny-sur-Meuse</p>
                  </div>
                </body>
                </html>';
                
        
                $mail->Body = $body;
                $mail->send();
            } catch (Exception $e) {
                error_log('Erreur PHPMailer destinataire : ' . $mail->ErrorInfo);
            }
        }
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

        $_SESSION['confirmation_data'] = [
            'generated_link' => $_SESSION['generated_link'],
            'pending_upload' => $_SESSION['pending_upload']
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
