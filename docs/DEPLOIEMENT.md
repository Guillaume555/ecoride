# Déploiement d'EcoRide

Ce document explique comment EcoRide est déployé et hébergé. Je décris ici
l'architecture que j'ai mise en place, les services que j'utilise, et la marche
à suivre pour redéployer ou reconfigurer le projet.

## Vue d'ensemble

EcoRide n'est pas hébergé sur un seul serveur : j'ai séparé l'application et
ses données sur trois services distincts, chacun choisi pour une raison précise.

- **Render** héberge l'application web (le code PHP). C'est lui qui sert le site
  à l'adresse https://ecoride-guillaume.onrender.com.
- **Aiven** héberge la base de données MySQL (utilisateurs, trajets, véhicules,
  réservations, avis).
- **MongoDB Atlas** héberge la base NoSQL qui stocke les logs d'activité des
  utilisateurs (connexions, réservations, etc.).

L'application sur Render se connecte donc à deux bases distantes : Aiven pour le
relationnel, Atlas pour les logs. J'ai fait ce choix parce que les offres
gratuites de ces services managés m'évitent d'administrer moi-même un serveur de
base de données, tout en restant proches d'une vraie architecture de production.

## Comment se fait le déploiement

Le déploiement est automatique. Render est relié à mon dépôt GitHub, sur la
branche `Programation_1`. Concrètement : dès que je pousse un commit sur cette
branche, Render détecte le changement et redéploie tout seul.

Render utilise Docker pour construire l'application. Il lit le `Dockerfile` à la
racine du projet, qui part d'une image `php:8.1-apache`, installe les extensions
nécessaires (`pdo_mysql` pour MySQL, `mongodb` pour Atlas), installe les
dépendances avec Composer, puis configure Apache pour écouter sur le port fourni
par Render. Je n'ai donc rien à faire manuellement : un `git push` suffit à
mettre le site à jour.

À noter : le `compose.yaml` présent dans le projet ne sert **pas** à la
production. Il me sert uniquement à recréer l'environnement complet (application
+ base MySQL) en local sur ma machine avec une seule commande. En production,
c'est bien le `Dockerfile` seul qui est utilisé par Render.

## Les variables d'environnement

Aucun identifiant ni mot de passe n'est écrit en dur dans le code, ni versionné
sur GitHub. Tout passe par des variables d'environnement, que je configure
directement dans l'interface de Render (onglet *Environment*). Le fichier `.env`
local, lui, n'est jamais poussé (il est listé dans `.gitignore`).

Voici les variables que le projet attend :

| Variable | Rôle |
|----------|------|
| `DB_HOST` | Adresse du serveur MySQL Aiven |
| `DB_PORT` | Port de connexion MySQL |
| `DB_NAME` | Nom de la base de données |
| `DB_USER` | Utilisateur MySQL |
| `DB_PASS` | Mot de passe MySQL |
| `DB_SSL` | `true` en production (Aiven impose une connexion SSL) |
| `MONGO_URI` | Chaîne de connexion à MongoDB Atlas |
| `SMTP_HOST` | Serveur SMTP pour l'envoi d'emails |
| `SMTP_PORT` | Port SMTP |
| `SMTP_USERNAME` | Identifiant SMTP |
| `SMTP_PASSWORD` | Mot de passe SMTP |
| `CONTACT_EMAIL` | Adresse qui reçoit les messages du formulaire de contact |
| `CONTACT_FROM_NAME` | Nom affiché comme expéditeur des emails |

Le code lit ces variables dans `config/database.php` (pour MySQL) et
`config/mongodb.php` (pour Atlas). Si `DB_HOST` n'est pas défini, l'application
bascule automatiquement sur une configuration locale (Laragon), ce qui me permet
de développer sans changer le code.

## La base de données

La structure de la base se trouve dans `sql/database_structure.sql` (création des
tables) et le jeu de données de démonstration dans `sql/seed_demo_ecoride.sql`.

Sur Aiven, j'administre la base avec DBeaver. Quand je dois mettre à jour les
données de production, j'exécute ces scripts directement depuis DBeaver, connecté
à la base Aiven. Je veille à ce que le contenu réel de la base et les fichiers
`.sql` du dépôt restent cohérents : c'est important pour qu'on puisse recréer la
base à l'identique à partir du dépôt.

## Garder la base Aiven active

L'offre gratuite d'Aiven met la base en veille au bout d'un certain temps
d'inactivité. Pour éviter que le site tombe parce que la base s'est endormie,
j'ai mis en place un petit script, `ping-bdd.php`, qui ouvre une connexion et
exécute une requête `SELECT 1`. Un service externe, UptimeRobot, appelle ce
script à intervalle régulier, ce qui maintient la base réveillée. C'est une
solution simple mais qui règle bien le problème des coupures.

## Reconfigurer ou redéployer

Si je dois changer une variable (par exemple un mot de passe de base), je la
modifie dans l'onglet *Environment* de Render, et le service redémarre avec la
nouvelle valeur. Pour déployer du nouveau code, je pousse simplement sur la
branche `Programation_1` et Render s'occupe du reste. En cas de souci, je
consulte les logs de build et d'exécution dans l'onglet *Logs* de Render.
