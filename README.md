# EcoRide - Plateforme de covoiturage écologique

## Description du projet

EcoRide est une application web de covoiturage conçue pour encourager les déplacements responsables. Elle permet à des utilisateurs de proposer ou de réserver des trajets entre particuliers, en mettant en avant les véhicules électriques et une logique de paiement par crédits.

## Démonstration en ligne

URL de production : https://ecoride-guillaume.onrender.com

## Installation locale

### Prérequis

- Serveur local : Laragon (recommandé), XAMPP ou WAMP
- PHP : 8.1 ou version supérieure
- MySQL : 5.7 ou version supérieure
- Git

### Étapes

1. Cloner le dépôt
```bash
git clone https://github.com/Guillaume555/ecoride.git
cd ecoride
```

2. Créer la base de données
- Importer le fichier `sql/database_structure.sql`
- Puis importer `sql/database_data.sql`

3. Vérifier la configuration
- Modifier les accès dans `config/database.php` si besoin :
  - hôte : `localhost`
  - base : `ecoride`
  - utilisateur : `root`
  - mot de passe : *(vide par défaut sur Laragon)*

4. Lancer le projet
- Démarrer Laragon ou équivalent
- Accéder à l’adresse : http://localhost/ecoride

## Comptes de test

- Passager : demo@passenger.com / password123
- Conducteur : demo@driver.com / password123
- Administrateur : admin@ecoride.fr / password123

## Technologies utilisées

- HTML, CSS, Bootstrap 5.3
- JavaScript
- PHP 8 avec PDO
- MySQL
- Apache

## Structure du projet

```
ecoride/
├── index.php
├── config/
├── includes/
├── pages/
├──monhodb/
├──docs
├── assets/
│   └── css/
    └── img/
    └── js/
├── sql/
```

## Fonctionnalités principales

- Authentification et création de compte avec attribution de crédits
- Recherche de trajets avec filtres
- Fiche détail d’un trajet
- Réservation de trajets (simulation en cours)
- Affichage des avis sur les conducteurs
- Profil utilisateur et gestion des trajets passés
- Mise en avant des véhicules électriques

## Charte graphique

- Couleur principale : #4B6B52
- Couleur secondaire : #3d5943
- Police : Inter
- Icônes : Font Awesome

## Sécurité

- Connexions sécurisées avec password_hash()
- Requêtes SQL préparées (PDO)
- Échappement des données avec htmlspecialchars()
- Cookies sécurisés pour l’option "Se souvenir de moi"

## Notes

Ce projet a été réalisé dans le cadre de l’évaluation ECF pour le Titre Professionnel Développeur Web et Web Mobile (2025).
