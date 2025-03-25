<?php
session_start();

class VerificationController
{
    public function verify()
    {
        $pdo = Database::connect();
        $email = $_POST['email'] ?? '';
        $code = $_POST['code'] ?? '';
        //$uuid = $_POST['uuid'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM email_verification_tokens WHERE email = ? AND token = ? AND expires_at > NOW()");
        $stmt->execute([$email, $code]);
        $token = $stmt->fetch();

        if ($token) {
            $stmt = $pdo->prepare("UPDATE email_verification_tokens SET validated = 1 WHERE id = ?");
            $stmt->execute([$token['id']]);

            if (isset($_SESSION['pending_upload'])) {
                $data = $_SESSION['pending_upload'];
                $files = $data['files'];
                $uuid = $data['uuid'];
                $email = $data['email'];
                $password = $data['password'];

                $uploadPath = __DIR__ . '/../../storage/' . $uuid;
                if (!is_dir($uploadPath)) {
                    if (!mkdir($uploadPath, 0755, true)) {
                        die("Erreur : le dossier d'upload n'existe pas et n'a pas pu être créé.");
                    }
                }
                

                for ($i = 0; $i < count($files['name']); $i++) {
                    $name = basename($files['name'][$i]);
                    $size = $files['size'][$i];
                    $tmp = $files['tmp_name'][$i];
                    $destination = "$uploadPath/$name";

                    move_uploaded_file($tmp, $destination);

                    $stmt = $pdo->prepare("INSERT INTO uploads (uuid, email, file_name, file_path, file_size, password_hash, token, token_expire) VALUES (?, ?, ?, ?, ?, ?, ?, NOW() + INTERVAL 30 DAY)");
                    $stmt->execute([
                        $uuid,
                        $email,
                        $name,
                        $destination,
                        $size,
                        $password ? password_hash($password, PASSWORD_DEFAULT) : null,
                        bin2hex(random_bytes(32))
                    ]);
                }

                unset($_SESSION['pending_upload']);
                header("Location: /upload_success");
                exit;
            }

            echo "✅ Vérification réussie, mais aucune donnée d'upload trouvée.";
        } else {
            echo "❌ Code invalide ou expiré.";
        }
    }
}
