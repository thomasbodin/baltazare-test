# Accès au back-office
Login : Candidat

Mot de passe : candidat

# Récupérer le projet
- Forker ce repo github

# Installer le projet sur votre machine
- Cloner le projet sur votre machine
- Installer la BDD (fichier baltazare-test.sql à la racine du projet)
- Configurer votre wp-config.php

# Code
Ce repo est une version light de notre starter-kit WordPress. C'est à partir de cela que nous commencons chaque nouveau projet.

Il comprend une liste de dépendances NPM et Bower dont vous n'aurez pas besoin pour ce test ainsi qu'un task runner GULP.

Un thème avec :
- un répertoire template avec un fichier template d'exemple : example.php
- un répertoire style comprenant les fichiers sources et les fichiers build CSS
- un répertoire script comprenant les fichiers sources et les fichiers build JS
- un répertoire function pour toutes les functions custom de WordPress
- un répertoire assets pour les favicon, fonts et img du site
- les différents fichiers de bases pour un site WordPress : 404, front-page, header, footer, single, ...

# Test
Ce test à pour but de définir ce que vous savez déjà faire avec WordPress et vous montre également le type de missions que vous serrez amenés à réaliser.

Ce test est 100% technique, le design et le style CSS ne sera pas pris en compte donc ne perdez pas de temps là dessus.

A la fin de votre stage ou si vous savez déjà tout faire, ce test ne devrait pas vous prendre plus de 15 minutes !

### Page galerie
- Créer une nouvelle page "Galerie" et lui appliquer un template que vous devrez créer : galerie.php
- Créer un slider bootstrap sur cette page

### Page films
- Créer la page "Films" et son template associé (film.php)
- Créer un Custom Post Type "Films"
- Insérer 4 films depuis le BO comprenant le titre, l'affiche du film et le synopsis
- Dans le template film.php, afficher une liste à puce qui affiche le titre de tous les films et un lien vers chacun de ces films
- Créer un single : single-film.php qui sera le template pour votre Custom Post Type "Films" et qui doit afficher le titre du film, l'affiche et son synopsys


# Envoyer votre test
Une fois votre travaille terminé, il vous suffira de remplacer le fichier baltazare-test.sql par votre base de données puis de faire une pull request.

Si vous avez besoin d'aide ou d'informations supplémentaires : thomas@baltazare.fr


GL & HF
