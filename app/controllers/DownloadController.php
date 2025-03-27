<?php
require_once __DIR__ . '/../../core/helpers/FileHelper.php';
class DownloadController
{
    
    public function index()
    {
        if (!isset($_GET['token'])) {
            $title = "Lien invalide";
            $message = "Aucun token fourni.";
            $code = 403;
            require_once __DIR__ . '/../views/errors/custom_error.php';
            return;
        }

        $token = $_GET['token'];

        require_once __DIR__ . '/../models/FichierModel.php';
        require_once __DIR__ . '/../../core/helpers/FileHelper.php';

        $fichierModel = new FichierModel();
        $fichierBdd = $fichierModel->findByToken($token);

        if (!$fichierBdd || strtotime($fichierBdd[0]['token_expire']) < time()) {
            $title = "Lien expiré ou invalide";
            $message = "Ce lien n'est plus valide ou a expiré.";
            $code = 410;
            require_once __DIR__ . '/../views/errors/custom_error.php';
            return;
        }

        $arborescence = FileHelper::buildFileTree($fichierBdd);
        $tokenExpire = $fichierBdd[0]['token_expire'];
        require_once __DIR__ . '/../views/download/tree.php';
    }
    public function file()
    {
       

        if (!isset($_GET['token'], $_GET['file'])) {
            http_response_code(400);
            echo "Paramètres manquants.";
            return;
        }

        $token = $_GET['token'];
        $fileRequested = $_GET['file'];

        require_once __DIR__ . '/../models/FichierModel.php';
        $model = new FichierModel();
        $fichiers = $model->findByToken($token);

        foreach ($fichiers as $fichier) {
            $relativePath = FileHelper::getRelativePath($fichier['file_path']);
        
            if (urldecode($relativePath) === urldecode($fileRequested)) {
                $path = $fichier['file_path'];
        
                if (!file_exists($path)) {
                    http_response_code(404);
                    echo "Fichier introuvable.";
                    return;
                }
        
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($path) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($path));
                readfile($path);
                exit;
            }
        }
        

        http_response_code(404);
        echo "Fichier non trouvé ou non autorisé.";
    }


    public function handleDownload()
{
    if (!isset($_GET['token'])) {
        $title = "Lien invalide";
        $message = "Aucun token fourni.";
        $code = 403;
        require_once __DIR__ . '/../views/errors/custom_error.php';
        return;
    }

    $token = $_GET['token'];

    require_once __DIR__ . '/../models/FichierModel.php';
    require_once __DIR__ . '/../../core/helpers/FileHelper.php';

    $fichierModel = new FichierModel();
    $fichiers = $fichierModel->findByToken($token);

    if (!$fichiers || strtotime($fichiers[0]['token_expire']) < time()) {
        $title = "Lien expiré ou invalide";
        $message = "Ce lien n'est plus valide ou a expiré.";
        $code = 410;
        require_once __DIR__ . '/../views/errors/custom_error.php';
        return;
    }

    $zipFile = tempnam(sys_get_temp_dir(), 'bogny_') . '.zip';
    $zip = new ZipArchive();

    if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
        http_response_code(500);
        echo "Impossible de créer l'archive ZIP.";
        return;
    }

    foreach ($fichiers as $fichier) {
        $realPath = $fichier['file_path'];
        $relativePath = FileHelper::getRelativePath($realPath);
    
        // Supprimer l'UUID de la racine
        $parts = explode('/', $relativePath);
        array_shift($parts); // remove UUID
        $cleanedPath = implode('/', $parts);
    
        if (file_exists($realPath)) {
            $zip->addFile($realPath, $cleanedPath);
        }
    }
    

    $zip->close();

    // Envoyer le fichier ZIP
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="fichiers_' . date('Ymd_His') . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    readfile($zipFile);

    // Nettoyer
    unlink($zipFile);
    exit;
}

    
    
}
