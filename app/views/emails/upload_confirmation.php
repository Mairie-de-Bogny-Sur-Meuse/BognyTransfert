<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:20px;border:1px solid #ddd;border-radius:10px;">
  <div style="text-align:center;margin-bottom:20px;">
    <img src="<?= rtrim($_ENV['BaseUrl'], '/') ?>/assets/img/BOGNY_logo_Gradient.png" alt="Logo Bogny" style="height:60px;">
  </div>

  <h2 style="color:#2563eb;">✅ Vos fichiers sont prêts au téléchargement</h2>
  <p>Bonjour,</p>
  <p>Vos fichiers ont bien été enregistrés. Voici les informations :</p>

  <ul style="padding-left: 20px;">
    <li><strong>Lien :</strong> <a href="<?= htmlspecialchars($downloadLink) ?>" style="color:#2563eb"><?= htmlspecialchars($downloadLink) ?></a></li>
    <li><strong>Date d’expiration :</strong> <?= $expireDate ?></li>
    <li><strong>Nombre de fichiers :</strong> <?= $fileCount ?></li>
    <li><strong>Taille totale :</strong> <?= $sizeFormatted ?></li>
    <li><strong>Protection :</strong> <?= $hasPassword ? '✅ Par mot de passe' : '❌ Aucune' ?></li>
  </ul>

  <p style="margin-top:30px;">Merci d’utiliser <strong>BognyTransfert</strong> 📦</p>
  <hr style="margin:20px 0;">
  <p style="font-size:12px;color:#888;">Cet email a été généré automatiquement. Ne pas répondre.</p>
</div>
