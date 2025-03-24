# BognyTransfert – Service de transfert sécurisé de fichiers

Ce projet est un système de type **WeTransfer-like** développé en PHP (structure MVC légère), avec vérification par code (2FA) et expiration automatique des fichiers.

---

## 🚀 Fonctionnalités

- Envoi de fichiers jusqu'à **10 Go**
- Vérification 2FA par code email (valide 15 min)
- Lien de téléchargement avec expiration (par défaut : 72h)
- Téléchargement des fichiers un par un ou via une archive ZIP
- Suppression automatique des envois expirés (30j / 90j)
- Interface moderne avec **TailwindCSS**
- Notification email via **PHPMailer**

---

## 📁 Structure du projet

```
/app/
  /controllers/       ← Logique métier (UploadController, DownloadController, etc.)
  /views/             ← Fichiers HTML + Tailwind
  /models/            ← (optionnel) Modèles SQL
/core/                ← Router, Database, etc.
/config/              ← Fichier de configuration
/storage/uploads/     ← Fichiers utilisateurs
/storage/archive/     ← Archives compressées
/cron/                ← Scripts de nettoyage
/public/              ← Webroot (optionnel)
index.php             ← Point d’entrée principal
```

---

## ⚙️ Pré-requis

- PHP ≥ 7.4
- MySQL/MariaDB
- Apache (avec mod_rewrite) ou Nginx
- Composer

---

## 🔒 Fichiers ignorés par Git

Le fichier `.gitignore` exclut les données sensibles et utilisateurs :  
- `/config/config.php`
- `/storage/uploads/`
- `/storage/archive/`
- `/vendor/`

---

## 🔐 Création de `config/config.php`

Crée le fichier `config/config.php` manuellement :

```php
<?php

return [
    'db_host' => 'localhost',
    'db_name' => 'nom_de_ta_base',
    'db_user' => 'utilisateur',
    'db_pass' => 'mot_de_passe',

    'smtp_user' => 'no-reply@tondomaine.fr',
    'smtp_pass' => 'mot_de_passe_smtp',

    'storage_path' => __DIR__ . '/../storage/uploads/',
    'archive_path' => __DIR__ . '/../storage/archive/',
    'max_upload_size' => 10 * 1024 * 1024 * 1024, // 10 Go
    'max_monthly_per_user' => 200 * 1024 * 1024 * 1024, // 200 Go
    'token_validity_hours' => 72,
];
```

---

## 🗄️ Base de données

Voici un schéma SQL de base pour la table `uploads` :

```sql
CREATE TABLE uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(64),
    email VARCHAR(255),
    file_name VARCHAR(255),
    file_path TEXT,
    file_size BIGINT,
    password_hash TEXT,
    token VARCHAR(255),
    token_expire DATETIME,
    code_2fa VARCHAR(10),
    verification_expires_at DATETIME,
    verified_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 📬 Installation des dépendances

```bash
composer install
```

---

## 🕒 Tâches CRON recommandées

```bash
# Supprimer les envois non validés (toutes les 5 min)
*/5 * * * * /usr/bin/php /chemin/vers/projet/cron/delete_unverified.php

# Supprimer les envois publics expirés (tous les jours à 1h)
0 1 * * * /usr/bin/php /chemin/vers/projet/cron/clean_public.php

# Supprimer les archives compressées (tous les jours à 2h)
0 2 * * * /usr/bin/php /chemin/vers/projet/cron/clean_archive.php
```
---
## 🛡 Sécurité

- Les liens sont signés par token UUID (non devinable)
- Protection par mot de passe en option
- Confirmation de propriété email par code 2FA (15 min max)
- Fichiers stockés en dehors de `/public/`
- Téléchargement uniquement via contrôleur sécurisé

---

## ✍️ Auteur

Projet développé par **Kevin Robinet** – Mairie de Bogny-sur-Meuse  
> [https://bognysurmeuse.fr](https://bognysurmeuse.fr)

---

## 📄 Licence

Projet open source — utilisation libre et modifiable.
