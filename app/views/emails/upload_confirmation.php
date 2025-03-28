<?php
// upload_confirmation.php
?>
<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:20px;border:1px solid #ddd;border-radius:10px;background-color:#f9fafb;">
  <div style="text-align:center;margin-bottom:20px;">
    <img src="<?= rtrim($_ENV['BaseUrl'], '/') ?>/assets/img/BOGNY_logo_Gradient.png" alt="Logo Bogny" style="height:60px;">
  </div>

  <h2 style="color:#2563eb;font-size:20px;">✅ Vos fichiers sont prêts au téléchargement</h2>
  <p style="font-size:15px;color:#333;">Bonjour,</p>
  <p style="font-size:15px;color:#333;">Vos fichiers ont bien été enregistrés. Voici les informations de votre transfert :</p>

  <ul style="padding-left: 20px;font-size:15px;line-height:1.6;">
    <li><strong>🔗 Lien :</strong> <a href="<?= htmlspecialchars($downloadLink) ?>" target="_blank" style="color:#2563eb;text-decoration:none;"><?= htmlspecialchars($downloadLink) ?></a></li>
    <li><strong>⏳ Expiration :</strong> <?= $expireDate ?></li>
    <li><strong>📁 Fichiers :</strong> <?= $fileCount ?> fichier<?= $fileCount > 1 ? 's' : '' ?></li>
    <li><strong>📦 Taille totale :</strong> <?= $sizeFormatted ?></li>

    <li><strong>🔐 Mot de passe :</strong> <?= $hasPassword ? '✅ Oui' : '❌ Non' ?></li>
    
    <?php if (!empty($upload['encryption_level']) && $upload['encryption_level'] !== 'none'): ?>
      <li><strong>🛡️ Chiffrement :</strong>
        <?php
          switch ($upload['encryption_level']) {
            case 'aes':
              echo '✅ Protégé par un chiffrement simple (AES)';
              break;
            case 'aes_rsa':
                echo '✅ Protégé par un chiffrement renforcé (AES + RSA)';
                break;
              default:
                  echo ucfirst($upload['encryption_level']);
          }
        ?>
      </li>
    <?php else: ?>
      <li><strong>🛡️ Chiffrement :</strong> ❌ Aucun</li>
    <?php endif; ?>
  </ul>

  <p style="margin-top:30px;font-size:15px;">Merci d’utiliser <strong style="color:#2563eb;">BognyTransfert</strong> 📦</p>

  <hr style="margin:25px 0;border: none;border-top: 1px solid #eee;">
  <p style="font-size:12px;color:#888;text-align:center;">Cet email a été généré automatiquement. Merci de ne pas y répondre.</p>
</div>
