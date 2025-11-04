# Changelog - Projet EcoRide

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

---

## [0.2.0] - 10 juillet 2025 - Refactorisation POO

### ✨ Ajouté

#### Classes POO principales
- **User.php** : Gestion complète des utilisateurs
  - Authentification sécurisée (register, login, logout)
  - Gestion du profil utilisateur
  - Système de crédits (ajout, retrait, consultation)
  - Actions administrateur (ban, unban)
  - Statistiques utilisateur (trajets, notes)

- **Trip.php** : Gestion complète des trajets
  - CRUD trajets complet
  - Recherche avancée de trajets
  - Système de réservation avec gestion automatique des crédits
  - Annulation de trajet avec remboursement automatique des passagers
  - Récupération des réservations

- **Vehicle.php** : Gestion des véhicules
  - Ajout et modification de véhicules
  - Validation automatique de la plaque d'immatriculation
  - Vérification de propriété
  - Détection des véhicules écologiques

- **Admin.php** : Statistiques et tableaux de bord
  - Statistiques globales (users, trips, bookings)
  - Statistiques détaillées par catégorie
  - Listes des derniers éléments (nouveaux inscrits, réservations récentes)

#### Sécurité
- Requêtes préparées sur 100% des requêtes SQL
- Hashage BCRYPT des mots de passe
- Validation stricte de toutes les données utilisateur
- Transactions SQL pour garantir l'intégrité des données
- Vérification de propriété avant modification/suppression

#### Tests
- Suite de tests unitaires pour chaque classe
- Validation complète des 47 méthodes
- 0 erreur détectée

### 🔧 Modifié

#### Base de données
- Ajout de la colonne `updated_at` sur `trips`, `bookings`, `vehicles`
- Ajout d'index de performance :
  - `idx_trips_status` pour la recherche de trajets actifs
  - `idx_bookings_status` pour les réservations confirmées
  - `idx_users_email` pour accélérer les connexions
- Renommage `trips.description` → `trips.preferences` (cohérence architecture)

#### Configuration
- `config/database.php` : Ajout de l'autoloader pour les classes POO

### 📊 Métriques
- **Code créé** : 1500+ lignes
- **Méthodes** : 47 méthodes fonctionnelles
- **Réduction code** : ~70% dans les pages (après adaptation)
- **Temps** : 2h (gain de 2h sur planning initial)

### 🎯 Impact
- Architecture professionnelle conforme aux standards ECF
- Code maintenable et évolutif
- Réutilisabilité maximale
- Performance optimisée (requêtes + index)

---

## [0.1.0] - Avant 10 juillet 2025 - Version initiale

### Structure initiale
- Architecture procédurale
- Pages PHP avec logique métier intégrée
- Fonctions utilitaires dans database.php
- Services existants (EmailService, MongoDBLogger, PasswordResetService)
- 