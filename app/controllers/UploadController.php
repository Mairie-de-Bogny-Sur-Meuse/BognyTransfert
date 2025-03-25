<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


class UploadController
{
    public function handleUpload()
    {
        $pdo = Database::connect();
        $config = require __DIR__ . '/../../config/config.php';
        $uuid = bin2hex(random_bytes(16));
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $files = $_FILES['files'];
       
        // 🔐 1. Validation email
        if (!preg_match('/@bognysurmeuse\.fr$/', $email)) {
            die("Email non autorisé. Seul @bognysurmeuse.fr est accepté.");
        }


        // 📦 2. Calcul taille totale des fichiers
        $totalSize = array_sum($files['size']);
        if ($totalSize > $config['max_upload_size']) {
            die("Limite de 10 Go dépassée pour cet envoi.");
        }

        // 📅 3. Vérification quota mensuel
        $stmt = $pdo->prepare("
            SELECT SUM(file_size) FROM uploads
            WHERE email = :email
            AND MONTH(created_at) = MONTH(NOW())
            AND YEAR(created_at) = YEAR(NOW())
        ");
        $stmt->execute(['email' => $email]);
        $used = $stmt->fetchColumn() ?: 0;

        if (($used + $totalSize) > $config['max_monthly_per_user']) {
            die("Quota mensuel de 200 Go dépassé.");
        }

        // 📂 4. Stockage fichiers
        $uuid = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(32));
        $expire = date('Y-m-d H:i:s', strtotime('+' . $config['token_validity_days'] . ' days'));
        $uploadPath = $config['storage_path'] . $uuid;

        if (!mkdir($uploadPath, 0755, true)) {
            die("Impossible de créer le dossier de stockage.");
        }

        // 🔄 5. Enregistrement fichier + DB
        for ($i = 0; $i < count($files['name']); $i++) {
            $name = basename($files['name'][$i]);
            $size = $files['size'][$i];
            $tmp = $files['tmp_name'][$i];
            $destination = "$uploadPath/$name";

            if (!move_uploaded_file($tmp, $destination)) {
                die("Erreur lors de l’envoi de $name.");
            }

            $stmt = $pdo->prepare("
                INSERT INTO uploads (uuid, email, file_name, file_path, file_size, password_hash, token, token_expire)
                VALUES (:uuid, :email, :file_name, :file_path, :file_size, :password_hash, :token, :token_expire)
            ");
            $stmt->execute([
                'uuid' => $uuid,
                'email' => $email,
                'file_name' => $name,
                'file_path' => $destination,
                'file_size' => $size,
                'password_hash' => $password ? password_hash($password, PASSWORD_DEFAULT) : null,
                'token' => $token,
                'token_expire' => $expire
            ]);
        }

        // 🔗 6. Afficher ou envoyer le lien (à améliorer avec PHPMailer)
        $downloadUrl = "https://dl.bognysurmeuse.fr/download/$token";
       // echo "<p class='text-center text-green-700 font-semibold'>Fichiers envoyés avec succès !</p>";
        //echo "<p class='text-center mt-4'>Lien de téléchargement : <a href='$downloadUrl' class='text-blue-600 underline'>$downloadUrl</a></p>";

        require __DIR__ . '/../views/upload_success.php';

        

        $this->sendEmailNotification($email, $token, $password, $expire);


    }
    private function sendEmailNotification($email, $token, $password, $expire)
{
    $downloadLink = "https://dl.bognysurmeuse.fr/download/$token";

    $mail = new PHPMailer(true);

    try {
        // Config SMTP (à personnaliser selon ton serveur)
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'quoted-printable';                                           //Send using SMTP
        $mail->isSMTP();
        $mail->Host       = 'ssl0.ovh.net';                     //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = REMOVED                     //SMTP username
        $mail->Password   = 'REMOVED';                               //SMTP password
        $mail->SMTPSecure = 'ssl';          //Enable implicit TLS encryption
        $mail->Port       = 465;  

        // Infos expéditeur & destinataire
        $mail->setFrom('NePasRepondre@bognysurmeuse.fr', 'BognyTransfert');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Votre lien de téléchargement sécurisé';
        
        $html = "<p>Bonjour,</p>
        <p>Voici votre lien de téléchargement sécurisé :</p>
        <p><a href='$downloadLink'>$downloadLink</a></p>
        <p>Ce lien expire le <strong>" . (new DateTime($expire))->format('d/m/Y à H:i') . "</strong>.</p>";

        if ($password) {
            $html .= "<p><strong>Mot de passe requis :</strong> <code>$password</code></p>";
        }

        $html .= "<p>Cordialement,<br>Le service de transfert sécurisé</p>";

        $mail->Body = $html;

        $mail->send();

    } catch (Exception $e) {
        error_log("Erreur d'envoi d'email : {$mail->ErrorInfo}");
    }
}

}
