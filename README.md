# 📦 BognyTransfert

**BognyTransfert** est une plateforme de transfert de fichiers sécurisée et moderne développée pour la commune de Bogny-sur-Meuse. Le projet permet d’envoyer des fichiers volumineux via un lien ou par email, avec une protection optionnelle par mot de passe et chiffrement.

---

## 🔧 Fonctionnalités

- Envoi de fichiers via lien ou par email
- Vérification de l’expéditeur par code
- Protection par mot de passe
- Chiffrement des fichiers : `aucun`, `AES`, `AES + RSA`
- Expiration automatique des transferts
- Suppression automatique via CRON
- Interface moderne avec TailwindCSS
- Journalisation et rotation des logs

---

## 📁 Arborescence principale

```
BognyTransfert/
├── app/
│   ├── controllers/
│   ├── models/
│   ├── views/
│   ├── cron/
│   └── ...
├── core/
│   ├── Database.php
│   └── ...
├── public/
│   └── index.php
├── storage/
│   ├── uploads/
│   ├── tempUploads/
│   └── ...
├── .env
├── .env.sample
├── .gitignore
└── README.md
```

---

## ✅ Pré-requis

- PHP ≥ 8.1
- Composer
- MySQL/MariaDB
- Serveur Web (Apache, Nginx, etc.)
- OpenSSL (pour le chiffrement)
- PHPMailer (via Composer)
- Accès CRON (pour la suppression automatique)

---

## 🚀 Installation

```bash
git clone https://github.com/Mairie-de-Bogny-Sur-Meuse/BognyTransfert.git
cd BognyTransfert
cp .env.sample .env
composer install
```

1. Crée ta base de données MySQL (ex : `bognytransfert`)
2. Configure ton fichier `.env`
3. Lance ton serveur local ou configure ton VirtualHost

---

## ⚙️ Configuration `.env`

Copie le fichier `.env.sample` puis adapte les valeurs :

```bash
cp .env.sample .env
nano .env
```

Exemple de contenu :

```
# BASE
BaseUrl=https://UrlDeBase.exemple.com
UPLOAD_PATH=/chemin/vers/le/dossier/uploads
TEMP_PATH=/chemin/vers/le/dossier/tempUploads

# DATABASE
DB_HOST=127.0.0.1
DB_NAME=NomDeLaBase
DB_USER=UtilisateurBDD
DB_PASS=MotDePasseBDD

# EMAIL
EMAIL_HOST=smtp.exemple.com
EMAIL_PORT=465
EMAIL_USER=UtilisateurSMTP
EMAIL_PASSWORD=MotDePasseSMTP
EMAIL_FROM=no-reply@exemple.com
EMAIL_FROM_NAME=BognyTransfert

# SÉCURITÉ
MASTER_ENCRYPTION_KEY=base64masterkeyhere==
RSA_PUBLIC_KEY_PATH=/chemin/vers/cles/public.pem
RSA_PRIVATE_KEY_PATH=/chemin/vers/cles/private.pem

# OPTIONS
DEBUG_LOG=true
```

---

## 🔐 Génération des clés RSA (pour AES + RSA)

Pour utiliser le chiffrement AES + RSA, tu dois générer une paire de clés :

```bash
# Crée un dossier sécurisé
mkdir -p /chemin/vers/cles && cd /chemin/vers/cles

# Génère la clé privée (2048 bits)
openssl genrsa -out private.pem 2048

# Génère la clé publique à partir de la clé privée
openssl rsa -in private.pem -pubout -out public.pem

# Donne les bons droits
chmod 600 private.pem
chmod 644 public.pem
```

Puis configure les chemins dans `.env` :

```
RSA_PRIVATE_KEY_PATH=/chemin/vers/cles/private.pem
RSA_PUBLIC_KEY_PATH=/chemin/vers/cles/public.pem
```

---

## 👨‍💻 Utilisation

- Accéder à `/upload` pour soumettre un transfert
- L’expéditeur valide avec un **code envoyé par email**
- Le destinataire (ou le lien) permet de **télécharger** les fichiers
- Si un **mot de passe** est défini, il est requis
- Fichiers supprimés automatiquement après 30 jours (uploads), 15 minutes (temporaires)

---

## ⏲️ Tâches CRON

```bash
# Exécuter toutes les 5 minutes
*/5 * * * * php /chemin/vers/app/cron/cleanup.php >> /chemin/vers/logs/Cron-log.log 2>&1
```

---

## 📜 Logrotate (facultatif)

Créer `/etc/logrotate.d/bognytransfert` :

```conf
/chemin/vers/logs/log/*.log {
    daily
    rotate 3
    compress
    missingok
    notifempty
    su www-data www-data
}
```

---

## 🛡️ Sécurité

- Chiffrement AES-256-CBC (avec ou sans RSA)
- Hash des mots de passe avec `password_hash()`
- Tokens aléatoires (256 bits)
- Vérification CSRF
- Accès limité aux domaines `@bognysurmeuse.fr`

---

## 📬 Support

En cas de problème, contactez l’administrateur :  
📧 **informatique@bognysurmeuse.fr**

---

## 🏛️ Licence

Projet développé pour la commune de **Bogny-sur-Meuse**. Usage restreint.
```
