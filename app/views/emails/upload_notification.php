<p>Bonjour,</p>

<p>Vous avez reçu un transfert de fichiers via <strong>BognyTransfert</strong> de la part de <strong><?= htmlspecialchars($email) ?></strong>.</p>

<ul style="padding-left: 20px;">
    <li><strong>📄 Nombre de fichiers :</strong> <?= $fileCount ?></li>
    <li><strong>💾 Taille totale :</strong> <?= $sizeFormatted ?></li>
    <li><strong>🔐 Mot de passe :</strong> <?= $hasPassword ? '✅ Oui' : '❌ Non' ?></li>
    <li><strong>🛡️ Chiffrement :</strong>
        <?php
        switch ($upload['encryption_level']) {
            case 'aes':
                echo '✅ Chiffrement standard (AES) ';
                break;
            case 'aes_rsa':
                echo '🔒 Chiffrement renforcé (AES + RSA)';
                break;
            default:
                echo '❌ Aucun chiffrement';
        }
        ?>
        <!-- <br><small><a href="https://dl.bognysurmeuse.fr/securite" target="_blank">En savoir plus</a></small> -->
    </li>
    <li><strong>⏳ Expiration :</strong> <?= $expireDate ?></li>
</ul>

<p>➡️ <strong>Lien de téléchargement :</strong><br>
<a href="<?= htmlspecialchars($downloadLink) ?>" target="_blank" style="color:#2563eb"><?= htmlspecialchars($downloadLink) ?></a></p>

<?php if (!empty($message)): ?>
    <p><strong>📩 Message de l’expéditeur :</strong><br><?= nl2br(htmlspecialchars($message)) ?></p>
<?php endif; ?>

<p style="margin-top: 30px;">Bonne journée 👋<br>L’équipe <strong>BognyTransfert</strong></p>
