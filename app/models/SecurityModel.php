<?php

class SecurityModel
{
    public static function generateCSRFToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCSRFToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function generateToken(int $length = 64): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    public static function log(string $action, ?string $email = null, array $context = []): void
    {
        $entry = date('[Y-m-d H:i:s]') . " ACTION: $action";
        if ($email) $entry .= " | EMAIL: $email";
        if (!empty($context)) $entry .= " | CONTEXT: " . json_encode($context);
        file_put_contents(__DIR__ . '/../../storage/logs/security.log', $entry . PHP_EOL, FILE_APPEND);
    }

    public static function encryptFile(string $filePath, string $key): bool
    {
        if (!file_exists($filePath)) return false;

        $data = file_get_contents($filePath);
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $cipherText = openssl_encrypt($data, 'aes-256-cbc', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);

        if ($cipherText === false) return false;

        $encryptedData = base64_encode($iv . $cipherText);
        return file_put_contents($filePath, $encryptedData) !== false;
    }

    public static function decryptFile(string $filePath, string $key): bool
    {
        if (!file_exists($filePath)) return false;

        $data = base64_decode(file_get_contents($filePath));
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $cipherText = substr($data, $ivLength);

        $plainText = openssl_decrypt($cipherText, 'aes-256-cbc', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);

        if ($plainText === false) return false;

        return file_put_contents($filePath, $plainText) !== false;
    }
}
