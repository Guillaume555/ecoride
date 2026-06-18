# EcoRide - Plateforme de covoiturage écologique

## Description du projet

EcoRide est une application web de covoiturage conçue pour encourager les déplacements responsables. Elle permet à des utilisateurs de proposer ou de réserver des trajets entre particuliers, en mettant en avant les véhicules électriques et une logique de paiement par crédits.

## Démonstration en ligne

URL de production : https://ecoride-guillaume.onrender.com

## Comptes de test

Tous les comptes de démonstration utilisent le même mot de passe : **`Test1234`**

| Rôle         | Email                    | Mot de passe |
|--------------|--------------------------|--------------|
| Passager     | passager@ecoride.fr      | Test1234     |
| Conducteur   | conducteur@ecoride.fr    | Test1234     |
| Administrateur | admin@ecoride.fr       | Test1234     |

> Les mots de passe sont stockés hachés (BCRYPT) en base. Le jeu de données de
> démonstration (`sql/seed_demo_ecoride.sql`) crée des trajets datés du 10 au
> 20 juillet 2026, afin que la recherche et les filtres renvoient des résultats
> pendant la période d'évaluation.

## Installation locale

### Prérequis

- Serveur local : Laragon (recommandé), XAMPP ou WAMP
- PHP : 8.1 ou version supérieure
- MySQL : 5.7 ou version supérieure
- Composer (pour les dépendances : PHPMailer, MongoDB, phpdotenv)
- Git

### Étapes

1. Cloner le dépôt
```bash
git clone https://github.com/Guillaume555/ecoride.git
cd ecoride
```

2. Installer les dépendances PHP
```bash
composer install
```

3. Créer la base de données
- Importer la structure : `sql/database_structure.sql`
- Importer le jeu de démonstration : `sql/seed_demo_ecoride.sql`

4. Configurer les variables d'environnement
- Créer un fichier `.env` à la racine (voir variables ci-dessous)
- En local sur Laragon, les valeurs par défaut conviennent généralement
  (hôte `localhost`, base `ecoride`, utilisateur `root`, mot de passe vide)

5. Lancer le projet
- Démarrer Laragon ou équivalent
- Accéder à l'adresse : http://localhost/ecoride

### Variables d'environnement (.env)

```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ecoride
DB_USER=root
DB_PASS=
DB_SSL=false

MONGO_URI=mongodb+srv://...        # MongoDB Atlas (logs d'activité)

SMTP_HOST=smtp.gmail.com           # Envoi d'emails (PHPMailer)
SMTP_PORT=587
SMTP_USERNAME=...
SMTP_PASSWORD=...
```

Le fichier `.env` ne doit jamais être versionné (il est listé dans `.gitignore`).

## Technologies utilisées

- HTML5, CSS3, Bootstrap 5.3
- JavaScript natif (validation, interactions, requêtes asynchrones)
- PHP 8.1 (architecture orientée objet, PDO)
- MySQL (données relationnelles)
- MongoDB (logs d'activité, base NoSQL)
- Apache
- Docker (containerisation), Render.com (hébergement), Aiven (MySQL managé), MongoDB Atlas

## Structure du projet

```
ecoride/
├── index.php              # Point d'entrée et routeur
├── config/               # Connexions BDD (MySQL, MongoDB), services
├── classes/              # Classes POO (User, Trip, Vehicle, Admin)
├── includes/             # header, navbar, footer, session, admin_guard
├── pages/                # Pages de l'application
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── mongodb/              # Logs (fallback JSON local)
├── docs/                 # Documentation (dont déploiement)
├── sql/                  # Scripts SQL (structure + données de démo)
├── Dockerfile
└── composer.json
```

## Fonctionnalités principales

- Authentification et création de compte avec attribution de crédits (20 à l'inscription)
- Recherche de trajets avec filtres (prix maximum, type de véhicule)
- Fiche détail d'un trajet
- Réservation de trajets avec gestion automatique des crédits
- Espace utilisateur (profil, mes trajets, mes véhicules)
- Espace administrateur (tableau de bord, gestion utilisateurs / trajets / avis)
- Mise en avant des véhicules électriques

## Sécurité

- Mots de passe hachés avec `password_hash()` / BCRYPT
- Requêtes SQL préparées (PDO) pour prévenir les injections
- Échappement des sorties avec `htmlspecialchars()` (protection XSS)
- Sessions PHP sécurisées, HTTPS en production
- Variables sensibles externalisées dans `.env` (jamais versionnées)

## Workflow Git

- Branche `main` : code stable
- Branche `development` : fonctionnalités en cours

## Charte graphique

- Couleur principale : `#4B6B52`
- Couleur secondaire : `#3d5943`
- Police : Inter
- Icônes : Font Awesome

## Notes

Projet réalisé dans le cadre de l'évaluation ECF pour le Titre Professionnel
Développeur Web et Web Mobile (RNCP37674).
