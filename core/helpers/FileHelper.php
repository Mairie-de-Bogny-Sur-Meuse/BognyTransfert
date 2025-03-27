<?php

class FileHelper
{
    public static function getRelativePath(string $filePath): string
    {
        $uploadPath = rtrim($_ENV['UPLOAD_PATH'], '/');
        $tempPath = rtrim($_ENV['TEMP_PATH'], '/');

        if (str_starts_with($filePath, $uploadPath)) {
            return ltrim(substr($filePath, strlen($uploadPath)), '/');
        }

        if (str_starts_with($filePath, $tempPath)) {
            return ltrim(substr($filePath, strlen($tempPath)), '/');
        }

        return basename($filePath); // fallback
    }

    public static function buildFileTree(array $fichiers): array
    {
        $tree = [];

        foreach ($fichiers as $fichier) {
            $relativePath = self::getRelativePath($fichier['file_path']);
            $parts = explode('/', $relativePath);
            $current = &$tree;

            foreach ($parts as $index => $part) {
                if ($index === count($parts) - 1) {
                    $current[$part] = [
                        'name' => $relativePath,
                        'size' => $fichier['file_size'],
                        'path' => $fichier['file_path'],
                    ];
                } else {
                    if (!isset($current[$part . '/'])) {
                        $current[$part . '/'] = [];
                    }
                    $current = &$current[$part . '/'];
                }
            }
        }

        return $tree;
    }
}
