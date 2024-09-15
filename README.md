# BlogPHP

BlogPHP est une application de blog simple construite avec PHP et Twig. Ce projet permet aux utilisateurs de lire des articles et de commenter qui sont crée par l'administrateur.

## Fonctionnalités

- Affichage des articles de blog.
- Ajout, édition et suppression d'articles (admin).
- Ajout, édition et suppression de commentaires.
- Authentification des utilisateurs.
- Notifications pour les actions.

## Prérequis

Avant de commencer, assurez-vous d'avoir les éléments suivants installés sur votre machine :

Conseil bonus l'utilisation d'un serveur MAMP vous facilitera l'accès à ce projet avec une base de donnée visible sur PHPMYADMIN et un serveur tout fait.

- PHP >= 7.4
- Composer
- Un serveur web (Apache, Nginx, etc.)
- Une base de données MySQL 


## Installation

1. Clonez le dépôt**

   git clone https://github.com/Gngoyi99/Creez_votre_premier_blog_en_PHP.git
   cd BlogPHP
   
2. Installez les dépendances

   composer install

3. Importez le fichier SQL fourni pour configurer les tables de la base de données.

   Créez un dossier config/ à la racine du projet contenant le fichier db.php:
   return[
    'host' => 'localhost',
    'dbname' => 'dbname',
    'user' => 'user',
    'password' => 'password',
    'charset' => 'utf8mb4',
      ];
   En mettant les coordonnées de votre db, par la suite vous pouvez inclure les tables du dossiers tables/.

   Aller dans le dossier tables (Les identifiant du User Admin sont Idf: "Admin1234!" mdp: "Admin1234!")

