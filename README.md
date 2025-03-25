# BognyTransfert

**BognyTransfert** est une application web développée en PHP qui permet aux utilisateurs de transférer facilement des fichiers et des dossiers via une interface intuitive. L'application offre des fonctionnalités avancées telles que la gestion des sous-dossiers, la prévention des fichiers potentiellement dangereux et la génération de liens de téléchargement temporaires.

## Table des matières

1. [Fonctionnalités](#fonctionnalités)
2. [Technologies utilisées](#technologies-utilisées)
3. [Installation](#installation)
4. [Utilisation](#utilisation)
5. [Structure du projet](#structure-du-projet)
6. [Contribuer](#contribuer)
7. [Licence](#licence)

## Fonctionnalités

- **Transfert de fichiers et de dossiers** : Les utilisateurs peuvent uploader des fichiers individuels ou des dossiers entiers, avec conservation de la structure des sous-dossiers.
- **Sécurité renforcée** : Filtrage automatique des fichiers potentiellement dangereux (par exemple, `.php`, `.sh`, `.exe`) et des fichiers système inutiles (par exemple, `.DS_Store`, `Thumbs.db`).
- **Génération de liens de téléchargement temporaires** : Après l'upload, un lien unique est généré pour le téléchargement des fichiers, avec une durée de validité configurable.
- **Interface utilisateur intuitive** : Affichage clair des fichiers et dossiers uploadés, avec des icônes spécifiques pour différents types de fichiers (PDF, Word, Excel, etc.).

## Technologies utilisées

- **PHP** : Langage principal pour le développement côté serveur.
- **Tailwind CSS** : Framework CSS pour une conception rapide et responsive de l'interface utilisateur.

## Installation

1. **Prérequis** :

   - Serveur web compatible avec PHP (par exemple, Apache).
   - PHP version 7.4 ou supérieure.
   - Base de données MySQL ou MariaDB.

2. **Étapes d'installation** :

   - Clonez le dépôt du projet :

     ```bash
     git clone https://github.com/votre-utilisateur/bognytransfert.git
     ```

   - Accédez au répertoire du projet :

     ```bash
     cd bognytransfert
     ```

   - Configurez la base de données en important le fichier `database.sql` dans votre SGBD.

   - Renommez le fichier `.env.example` en `.env` et configurez les paramètres de connexion à la base de données.

   - Assurez-vous que le répertoire `storage` est accessible en écriture par le serveur web.

   - Configurez votre serveur web pour pointer vers le répertoire `public` du projet.

## Utilisation

- **Upload de fichiers/dossiers** : Sur la page d'accueil, utilisez le bouton "Choisir des fichiers" pour sélectionner des fichiers ou des dossiers à uploader. La structure des dossiers sera préservée lors du transfert.

- **Lien de téléchargement** : Après l'upload, un lien unique vous sera fourni. Vous pouvez le partager avec les destinataires pour qu'ils puissent télécharger les fichiers.

- **Expiration du lien** : Chaque lien de téléchargement a une durée de validité limitée, affichée sur la page de confirmation. Une fois expiré, le lien ne permettra plus l'accès aux fichiers.

## Structure du projet

Le projet est organisé comme suit :

- `app/` : Contient les contrôleurs et la logique métier de l'application.
- `public/` : Répertoire public accessible via le serveur web, contenant le point d'entrée `index.php`.
- `storage/` : Emplacement des fichiers uploadés et des logs.
- `views/` : Fichiers HTML avec intégration de PHP pour l'affichage des pages.

## Contribuer

Les contributions sont les bienvenues ! Si vous souhaitez améliorer BognyTransfert, veuillez suivre les étapes suivantes :

1. Forkez le dépôt.
2. Créez une branche pour votre fonctionnalité ou correction de bug (`git checkout -b ma-nouvelle-fonctionnalité`).
3. Effectuez vos modifications et commitez-les (`git commit -am 'Ajout d'une nouvelle fonctionnalité'`).
4. Poussez vos modifications sur votre fork (`git push origin ma-nouvelle-fonctionnalité`).
5. Créez une Pull Request vers le dépôt principal.

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

---
