<p>Bonjour,</p>

<p>Vous avez reçu un transfert de fichiers via <strong>BognyTransfert</strong> de la part de <strong><?= htmlspecialchars($email) ?></strong>.</p>

<ul>
    <li><strong>Nombre de fichiers :</strong> <?= $fileCount ?></li>
    <li><strong>Taille totale :</strong> <?= $sizeFormatted ?></li>
    <li><strong>Protégé par mot de passe :</strong> <?= $hasPassword ? 'Oui' : 'Non' ?></li>
    <li><strong>Date d’expiration :</strong> <?= $expireDate ?></li>
</ul>

<p>➡️ <strong>Lien de téléchargement :</strong> <a href="<?= htmlspecialchars($downloadLink) ?>" target="_blank"><?= htmlspecialchars($downloadLink) ?></a></p>

<?php if (!empty($message)): ?>
    <p><strong>Message de l’expéditeur :</strong><br><?= nl2br(htmlspecialchars($message)) ?></p>
<?php endif; ?>

<p>Bonne journée,<br>L’équipe BognyTransfert</p>
