📌 Description du projet Symfony – Gestion et découverte de séries

Ce projet est une application web développée avec Symfony permettant la gestion et la découverte de séries télévisées.
Il propose aux utilisateurs une interface intuitive pour explorer, rechercher et gérer leurs séries préférées, avec des fonctionnalités adaptées aussi bien aux simples visiteurs qu’aux administrateurs.

🔑 Fonctionnalités principales

Listing des séries : affichage dynamique de toutes les séries disponibles dans la base de données.

Création de série : formulaire sécurisé pour ajouter une nouvelle série (accessible aux administrateurs).

Recherche de série : moteur de recherche intégré permettant de trouver rapidement une série par son nom.

Filtres : possibilité de filtrer les séries (genre, année, popularité, etc.).

Liens externes : accès direct au site de streaming associé à chaque série.

Suggestion aléatoire : proposition d’une série choisie aléatoirement pour découvrir de nouveaux contenus.

Pages dynamiques avec JavaScript : interactions enrichies côté client (filtrage instantané, chargement asynchrone, etc.).

👥 Gestion des utilisateurs

Authentification & rôles :

ROLE_USER : accès aux fonctionnalités de navigation et gestion de son compte.

ROLE_ADMIN : gestion complète des séries, utilisateurs et contenus.

Sécurité avancée :

Système de login/logout sécurisé.

Changement de mot de passe.

Validation d’inscription par email.

🗄️ Base de données

SQL (MySQL/PostgreSQL) pour la gestion des données (séries, utilisateurs, rôles, etc.).

Relations prévues entre séries, acteurs et producteurs.

🚧 Fonctionnalités en cours de développement

Gestion des acteurs et producteurs : possibilité d’associer chaque série à ses acteurs et producteurs pour enrichir les fiches descriptives.

# Installer PHP et Symfony

Installer phpStorm

Installer WAMPServer (sous Windows) ou MAMPServer (sous MacOS)

Télécharger SYMFONY sous sa dernière version LTS (6.4)

Dans le dossier d'installation de WAMP, il faut glisser le .exe de symfony dans : 'C:\wamp64\bin\php\php8.4.0'

# Initialiser le projet

`symfony composer install` pour installer toutes les dépendances

Si tu es sur WINDOWS :
Il faudra installer WAMPServer pour pouvoir accéder à la base de données phpMyAdmin, avec ses WAMP Packages : 
* vcredist_2010_sp1_x64
* vcredist_2010_sp1_x86
* vcredist_2022_x64
* vcredist_2022_86

# Création de la base de données

dans le `env.dev`, rajouter la ligne suivant : DATABASE_URL="mysql://root@127.0.0.1:3306/niortflix?serverVersion=9.1.0&charset=utf8mb4"

Pour créer la base de données : `symfony console doctrine:database:create`

Pour effectuer les migrations : `symfony console doctrine:migrations:migrate`

Pour charger les données de test (FACULTATIF) : `symfony console doctrine:fixtures:load`

# Lancement de l'application

Lancer l'application avec : `symfony server:start`

