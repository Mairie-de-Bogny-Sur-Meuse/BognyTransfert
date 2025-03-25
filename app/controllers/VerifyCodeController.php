<?php
// 🛡️ Vérifier si l'adresse email est validée
        $stmt = $pdo->prepare("SELECT validated FROM email_verification_tokens WHERE email = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email]);
        $validation = $stmt->fetch();

        if (!$validation || !$validation['validated']) {
            // Générer un code à 6 caractères
            $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Enregistrer le code
            $stmt = $pdo->prepare("INSERT INTO email_verification_tokens (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $code, $expires]);

            // Envoi de l'email de vérification
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'quoted-printable';                                           //Send using SMTP
            $mail->isSMTP();
            $mail->Host       = 'ssl0.ovh.net';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = REMOVED                     //SMTP username
            $mail->Password   = 'REMOVED';                               //SMTP password
            $mail->SMTPSecure = 'ssl';          //Enable implicit TLS encryption
            $mail->Port       = 465;
            $mail->setFrom('no-reply@bognysurmeuse.fr', 'Transfert sécurisé');
            $mail->addAddress($email);
            $mail->Subject = 'Code de vérification requis';
            $mail->Body = "Bonjour,\n\nVoici votre code de vérification : $code\nCe code expire dans 15 minutes.\n\nMerci de le saisir sur la page de validation.";
            $mail->send();

            // Redirection vers le formulaire de code
            header("Location: /verify?uuid=" . urlencode($uuid) . "&email=" . urlencode($email));
            exit;
            
        }
?>