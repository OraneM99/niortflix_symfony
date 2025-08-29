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

