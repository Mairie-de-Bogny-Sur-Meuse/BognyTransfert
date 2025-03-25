<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Fichiers reçus :</h2><pre>";
    print_r($_FILES['files']);
    echo "</pre>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Test webkitdirectory</title>
</head>
<body>
  <h1>🧪 Test d'upload avec dossier</h1>
  <form action="" method="POST" enctype="multipart/form-data">
    <input type="file" name="files[]" webkitdirectory directory multiple>
    <button type="submit">Envoyer</button>
  </form>
</body>
</html>
