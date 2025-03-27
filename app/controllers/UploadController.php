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
        require_once __DIR__ . "/../views/upload/form.php";
    }

    public function confirmation()
    {
        require_once __DIR__ . "/../views/upload/confirmation.php";
    }

    public function handleUpload()
    {
        if (
            !isset($_POST["csrf_token"]) ||
            $_POST["csrf_token"] !== ($_SESSION["csrf_token"] ?? null)
        ) {
            $title = "Attaque CSRF détectée";
            $message = "❌ Attaque CSRF détectée lors de l'envoi des fichiers.";
            $code = 403;
            require_once __DIR__ . "/../views/errors/custom_error.php";
            return;
        }

        $email = trim($_POST["email"] ?? "");
        $password = trim($_POST["password"] ?? "");
        $uploadOption = $_POST["upload_option"] ?? "email";
        $recipient = trim($_POST["recipient_email"] ?? "");
        $message = trim($_POST["message"] ?? "");
        $encryptionLevel = $_POST["encryption_level"] ?? "aes";

        if (!preg_match('/@bognysurmeuse\.fr$/', $email)) {
            $title = "Email non autorisé";
            $message = "Seules les adresses @bognysurmeuse.fr sont autorisées.";
            $code = 403;
            require_once __DIR__ . "/../views/errors/custom_error.php";
            return;
        }

        if (!in_array($uploadOption, ["email", "link_only"])) {
            $title = "Option invalide";
            $message = "L'option d'envoi choisie est invalide.";
            $code = 400;
            require_once __DIR__ . "/../views/errors/custom_error.php";
            return;
        }

        if ($uploadOption === "email") {
            if (
                empty($recipient) ||
                !filter_var($recipient, FILTER_VALIDATE_EMAIL)
            ) {
                $title = "Email du destinataire manquant";
                $message =
                    "Vous devez fournir une adresse e-mail valide du destinataire.";
                $code = 400;
                require_once __DIR__ . "/../views/errors/custom_error.php";
                return;
            }
        }

        $uuid = bin2hex(random_bytes(16));
        $tempPath = $_ENV["TEMP_PATH"] . $uuid . "/";

        if (!mkdir($tempPath, 0755, true)) {
            $title = "Erreur dossier temporaire";
            $message = "Impossible de créer le dossier temporaire.";
            $code = 500;
            require_once __DIR__ . "/../views/errors/custom_error.php";
            return;
        }

        $savedFiles = [];
        $dangerous = ["php", "exe", "sh", "bat", "cmd"];
        $ignored = [".DS_Store", "Thumbs.db", ".gitkeep"];

        require_once __DIR__ . "/../models/FileKeyModel.php";
        $keyModel = new FileKeyModel();

        foreach (["files_flat", "files_tree"] as $key) {
            if (!empty($_FILES[$key])) {
                $files = $_FILES[$key];

                for ($i = 0; $i < count($files["name"]); $i++) {
                    $relativePath =
                        $files["full_path"][$i] ?? $files["name"][$i];
                    $relativePath = ltrim($relativePath, "/\\");
                    $filename = basename($relativePath);
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    $tmp = $files["tmp_name"][$i];

                    if (
                        empty($relativePath) ||
                        empty($tmp) ||
                        !is_uploaded_file($tmp) ||
                        str_starts_with($filename, ".") ||
                        in_array($filename, $ignored) ||
                        in_array($ext, $dangerous)
                    ) {
                        continue;
                    }

                    $destination = $tempPath . $relativePath;
                    $subDir = dirname($destination);
                    if (!is_dir($subDir)) {
                        mkdir($subDir, 0755, true);
                    }

                    if (!move_uploaded_file($tmp, $destination)) {
                        continue;
                    }

                    // --- Si chiffrement demandé ---
                    $encryptedKey = null;
                    if ($encryptionLevel !== "none") {
                        $aesKey = random_bytes(32); // binaire pur, 256 bits
                        $iv = random_bytes(
                            openssl_cipher_iv_length("aes-256-cbc")
                        );

                        $data = file_get_contents($destination);
                        $encrypted = openssl_encrypt(
                            $data,
                            "aes-256-cbc",
                            $aesKey,
                            0,
                            $iv
                        );
                        file_put_contents($destination, $iv . $encrypted); // IV + données

                        if ($encryptionLevel === "aes") {
                            // Clé AES chiffrée avec une clé maître (en .env)
                            $masterKey =
                                $_ENV["MASTER_ENCRYPTION_KEY"] ??
                                "defaultmasterkey000000000000000000";
                            $encryptedKey = openssl_encrypt(
                                $aesKey,
                                "aes-256-cbc",
                                $masterKey,
                                0,
                                $iv
                            );
                        }

                        if ($encryptionLevel === "aes_rsa") {
                            $publicKeyPath =
                                $_ENV["RSA_PUBLIC_KEY_PATH"] ??
                                "/var/www/keys/rsa_public.pem";
                            $publicKey = file_get_contents($publicKeyPath);

                            if (!$publicKey) {
                                error_log(
                                    "[ERROR] Clé publique RSA introuvable à l’emplacement : $publicKeyPath"
                                );
                                continue;
                            }

                            openssl_public_encrypt(
                                $aesKey,
                                $encryptedKey,
                                $publicKey
                            );
                            $encryptedKey = base64_encode($encryptedKey);
                        }

                        if ($encryptionLevel === "maximum") {
                            // Pas de stockage de la clé côté serveur
                            $_SESSION["maximum_keys"][$relativePath] = base64_encode($aesKey);
                            $encryptedKey = null;
                        }

                        $keyModel->storeKey([
                            "uuid" => $uuid,
                            "file_name" => $relativePath,
                            "encrypted_key" => $encryptedKey,
                            "iv" => $iv,
                            "encryption_level" => $encryptionLevel,
                        ]);
                    }

                    $savedFiles[] = $relativePath;
                }
            }
        }

        // Génération du lien
        $linkBase = rtrim(
            $_ENV["BASE_URL"] ?? "https://dl.bognysurmeuse.fr",
            "/"
        );
        $downloadLink = "$linkBase/d/$uuid";

        // Enregistrement session
        $_SESSION["pending_upload"] = [
            "uuid" => $uuid,
            "email" => $email,
            "password" => $password,
            "files" => $savedFiles,
            "recipient" => $recipient,
            "message" => $message,
            "upload_option" => $uploadOption,
            "download_link" => $downloadLink,
        ];

        // Génération du code de vérification
        $code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        require_once __DIR__ . "/../models/EmailTokenModel.php";
        $tokenModel = new EmailTokenModel();
        $tokenModel->createToken($email, $code, $expires);

        // Envoi du code à l'expéditeur
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = "UTF-8";
            $mail->Encoding = "quoted-printable";
            $mail->isSMTP();
            $mail->Host = $_ENV["EMAIL_HOST"];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV["EMAIL_USER"];
            $mail->Password = $_ENV["EMAIL_PASSWORD"];
            $mail->SMTPSecure = "ssl";
            $mail->Port = (int) $_ENV["EMAIL_PORT"];

            $mail->setFrom($_ENV["EMAIL_FROM"], $_ENV["EMAIL_FROM_NAME"]);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Votre code de vérification";
            $mail->Body = "<p>Bonjour,<br>Votre code de vérification est : <strong>$code</strong><br>Ce code est valable pendant 15 minutes.</p>";

            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur PHPMailer : " . $mail->ErrorInfo);
            $title = "Erreur d'envoi";
            $message = "Impossible d’envoyer le code de vérification à l’adresse : $email";
            $code = 500;
            require_once __DIR__ . "/../views/errors/custom_error.php";
            return;
        }

        // Redirection vers la vérification
        header("Location: /verify?email=" . urlencode($email));
        exit();
    }
}
