# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

EcoRide est une plateforme de covoiturage écologique construite avec une **architecture POO moderne** (PHP 8.1+, MySQL, MongoDB). Cette branche "Programation_1" représente la version refactorisée en Programmation Orientée Objet.

- **Production URL**: https://ecoride-guillaume.onrender.com
- **Tech Stack**: PHP 8.1+, MySQL 5.7+, MongoDB Atlas, Bootstrap 5.3, Apache
- **Architecture**: POO complète avec classes métier (User, Trip, Vehicle, Admin)
- **Dépendances**: PHPMailer (emails), MongoDB Driver, Dotenv

## Architecture POO - Structure du Projet

### Vue d'ensemble

Le projet a été entièrement refactorisé d'une architecture procédurale vers une **architecture POO**. Le code est organisé en **classes métier réutilisables** qui encapsulent la logique applicative.

```
ecoride/
├── classes/               # NOUVEAU - Classes métier POO
│   ├── User.php          # Gestion utilisateurs (auth, profil, crédits)
│   ├── Trip.php          # Gestion trajets (CRUD, réservations)
│   ├── Vehicle.php       # Gestion véhicules (CRUD, validation)
│   └── Admin.php         # Statistiques et données admin
├── config/
│   ├── database.php      # Classe Database (singleton + helpers)
│   └── mongodb.php       # MongoDBLogger (logs d'activité)
├── includes/
│   ├── session.php       # Classe Session (gestion sessions)
│   ├── header.php        # En-tête HTML
│   ├── navbar.php        # Navigation
│   └── footer.php        # Pied de page
├── pages/                # Pages de l'application
│   ├── home.php, search.php, login.php, register.php
│   ├── profile.php       # Profil utilisateur (POO)
│   ├── create-trip.php   # Création de trajet (POO)
│   ├── add-vehicle.php   # Ajout de véhicule (POO)
│   ├── my-trips.php      # Trajets de l'utilisateur
│   ├── admin-*.php       # Pages admin (dashboard, users, trips, reviews)
│   └── forgot-password.php, reset-password.php
├── index.php             # Front controller (routing)
└── CHANGELOG.md          # Historique des modifications POO
```

### Front Controller Pattern

1. Toutes les requêtes passent par `index.php`
2. Routing via `?page=` query parameter
3. **Output buffering** pour capturer les variables de page (`$page_title`, `$extra_css`)
4. Flow: page content → header → navbar → content → footer

**Pages autorisées**: home, search, login, register, profile, my-trips, create-trip, add-vehicle, admin-dashboard, admin-users, admin-trips, admin-reviews, forgot-password, reset-password, about, contact, logs

## Architecture des Classes

### 1. Classe Database (`config/database.php`)

**Pattern**: Singleton
**Rôle**: Gestion centralisée de la connexion PDO et méthodes utilitaires

```php
// Obtenir la connexion PDO
$pdo = Database::getConnection();

// Méthodes utilitaires
$stats = Database::getGlobalStats();           // Stats globales du site
$exists = Database::emailExists($email);       // Vérifier email
$trips = Database::quickSearch($dep, $arr);    // Recherche rapide
```

**Auto-détection d'environnement**:
- Production: Lit `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SSL` des variables d'environnement
- Local: Utilise configuration par défaut (localhost, ecoride, root, pas de mot de passe)

### 2. Classe Session (`includes/session.php`)

**Pattern**: Méthodes statiques
**Rôle**: Gestion complète des sessions utilisateur

```php
// Vérification connexion
if (Session::isLoggedIn()) {
    $user = Session::getCurrentUser();
}

// Authentification
Session::login($userData);
Session::logout();

// Protection des pages
Session::requireLogin($redirectUrl);

// Gestion crédits
Session::updateCredits($newAmount);

// Remember Me
Session::createRememberToken($userId, $email);
Session::checkRememberMe(); // Auto-appelé au chargement

// Rôles
if (Session::hasRole('admin')) {
    // Code admin
}
```

**Backward Compatibility**: Les anciennes fonctions procédurales (`isLoggedIn()`, `loginUser()`, etc.) redirigent vers `Session::method()` pour compatibilité.

### 3. Classe User (`classes/User.php`)

**Rôle**: Gestion complète des utilisateurs

#### Authentification
```php
$user = new User($pdo);

// Inscription
$userId = $user->register($username, $email, $password, $phone);

// Connexion (retourne les données user ou false)
$userData = $user->login($email, $password);
```

#### Gestion du profil
```php
$user = new User($pdo, $userId);

// Charger les données
$data = $user->getData();

// Mettre à jour le profil
$user->update([
    'username' => 'nouveau_nom',
    'email' => 'nouveau@email.com',
    'phone' => '0612345678'
]);

// Changer le mot de passe
$user->updatePassword($oldPassword, $newPassword);
```

#### Gestion des crédits
```php
$credits = $user->getCredits();
$user->addCredits(50);
$user->removeCredits(20);
User::updateCreditsStatic($pdo, $userId, $newAmount); // Version statique
```

#### Actions administrateur
```php
User::ban($pdo, $userId);
User::unban($pdo, $userId);
User::delete($pdo, $userId);
```

#### Statistiques
```php
$stats = $user->getStatistics();
// Retourne: trips_as_driver, trips_as_passenger, total_spent, total_earned, average_rating
```

### 4. Classe Trip (`classes/Trip.php`)

**Rôle**: Gestion des trajets et réservations

#### Créer un trajet
```php
$trip = new Trip($pdo);
$tripId = $trip->create($driverId, $vehicleId, [
    'departure_city' => 'Paris',
    'arrival_city' => 'Lyon',
    'departure_time' => '2025-07-20 14:00:00',
    'price_per_seat' => 15,
    'available_seats' => 3,
    'preferences' => 'Trajet sympa, non fumeur'
]);
```

#### Rechercher des trajets
```php
$results = Trip::search($pdo, [
    'departure' => 'Paris',
    'arrival' => 'Lyon',
    'date' => '2025-07-20',  // optionnel
    'min_seats' => 2          // optionnel
]);
```

#### Réservation (avec gestion automatique des crédits)
```php
$trip = new Trip($pdo, $tripId);

// Réserver (débite automatiquement les crédits du passager)
$bookingId = $trip->book($passengerId, $seats);

// Annuler un trajet (rembourse automatiquement tous les passagers)
$trip->cancel();
```

#### Actions admin
```php
Trip::cancelTrip($pdo, $tripId);    // Annulation admin avec remboursement
Trip::hideTrip($pdo, $tripId);      // Masquer un trajet
Trip::showTrip($pdo, $tripId);      // Afficher un trajet
```

### 5. Classe Vehicle (`classes/Vehicle.php`)

**Rôle**: Gestion des véhicules

```php
$vehicle = new Vehicle($pdo);

// Créer un véhicule
$vehicleId = $vehicle->create($userId, [
    'brand' => 'Renault',
    'model' => 'Clio',
    'color' => 'Bleu',
    'license_plate' => 'AB-123-CD',
    'fuel_type' => 'Hybride',  // Essence, Diesel, Électrique, Hybride
    'seats' => 4
]);

// Récupérer les véhicules d'un utilisateur
$vehicles = Vehicle::getUserVehicles($pdo, $userId);

// Vérifier la propriété
$isOwner = $vehicle->belongsTo($userId);

// Détecter véhicules écologiques
$isEco = $vehicle->isEcoFriendly(); // true si Électrique ou Hybride
```

### 6. Classe Admin (`classes/Admin.php`)

**Rôle**: Statistiques et données pour l'interface admin

```php
// Statistiques complètes du dashboard
$stats = Admin::getDashboardStats($pdo);
// Retourne: users_count, users_active, trips_count, trips_active,
//           bookings_count, bookings_confirmed, total_credits, average_rating

// Dernières inscriptions
$newUsers = Admin::getLatestUsers($pdo, $limit = 10);

// Dernières réservations
$recentBookings = Admin::getLatestBookings($pdo, $limit = 10);

// Trajets récents
$recentTrips = Admin::getLatestTrips($pdo, $limit = 10);
```

## Development Workflow

### Local Setup

```bash
# 1. Cloner et naviguer
git clone https://github.com/Guillaume555/ecoride.git
cd ecoride

# 2. Basculer sur la branche Programation_1
git checkout Programation_1

# 3. Installer les dépendances PHP
composer install

# 4. Importer la base de données
mysql -u root -p ecoride < sql/database_structure.sql
mysql -u root -p ecoride < sql/database_data.sql

# 5. Créer le fichier .env
# Voir section "Environment Variables" ci-dessous

# 6. Démarrer le serveur
php -S localhost:8000
# Accéder: http://localhost:8000
```

### Environment Variables

**Production (Render.com)**:
```bash
DB_HOST=mysql-ecoride.aivencloud.com
DB_PORT=12345
DB_NAME=ecoride
DB_USER=avnadmin
DB_PASS=******
DB_SSL=true
MONGO_URI=mongodb+srv://user:pass@cluster.mongodb.net/?retryWrites=true&w=majority
```

**Local (.env à créer)**:
```env
# MongoDB Atlas (requis pour logs d'activité)
MONGO_URI=mongodb+srv://username:password@cluster.mongodb.net/?retryWrites=true&w=majority

# MySQL (optionnel - defaults to localhost if not set)
# DB_HOST=localhost
# DB_PORT=3306
# DB_NAME=ecoride
# DB_USER=root
# DB_PASS=
```

Si MongoDB est indisponible, l'app bascule automatiquement sur des logs JSON dans `mongodb/user_logs.json`.

### Testing Connections

```bash
# Test MySQL
php -S localhost:8000 ping-bdd.php
# Accéder: http://localhost:8000/ping-bdd.php

# Test MongoDB + voir les logs
# Accéder: http://localhost:8000/test-mongodb.php
```

## Ajouter de Nouvelles Fonctionnalités

### Pattern Recommandé: Architecture POO

#### 1. Créer une nouvelle classe métier (si nécessaire)

```php
<?php
// classes/Booking.php

class Booking
{
    private $pdo;
    private $id;
    private $data;

    public function __construct($pdo, $id = null)
    {
        $this->pdo = $pdo;
        $this->id = $id;
        $this->data = [];

        if ($id !== null) {
            $this->loadById($id);
        }
    }

    public function create($tripId, $passengerId, $seats)
    {
        // Logique de création
        // Utiliser $this->pdo pour les requêtes
        // Valider les données
        // Retourner l'ID créé
    }

    // Autres méthodes...
}
```

**N'oubliez pas**: Inclure la classe dans `config/database.php` :
```php
require_once __DIR__ . '/../classes/Booking.php';
```

#### 2. Créer une nouvelle page

```php
<?php
// pages/ma-nouvelle-page.php

// Définir les variables de page AVANT le HTML
$page_title = "EcoRide - Ma Nouvelle Page";
$extra_css = ['ma-page.css'];  // Optionnel

// Protection si nécessaire
Session::requireLogin();

// Récupérer les données
$pdo = Database::getConnection();
$user = new User($pdo, $_SESSION['user_id']);
$stats = $user->getStatistics();

?>

<!-- Votre HTML ici -->
<div class="container mt-5">
    <h1>Ma Nouvelle Page</h1>
    <p>Statistiques: <?= $stats['trips_as_driver'] ?> trajets</p>
</div>
```

**Puis ajouter la page dans `index.php`**:
```php
$allowed_pages = [
    'home', 'search', ..., 'ma-nouvelle-page'
];
```

**Accès**: `?page=ma-nouvelle-page`

#### 3. Traiter un formulaire

```php
<?php
// En haut de la page, AVANT la définition des variables

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = Database::getConnection();

        // Utiliser les classes POO
        $trip = new Trip($pdo);
        $tripId = $trip->create($_SESSION['user_id'], $vehicleId, [
            'departure_city' => $_POST['departure'],
            'arrival_city' => $_POST['arrival'],
            // ...
        ]);

        // Logger l'activité
        Session::logAction('trip_created', ['trip_id' => $tripId]);

        $success = "Trajet créé avec succès !";
    } catch (Exception $e) {
        $errors['general'] = $e->getMessage();
    }
}

// Puis définir les variables de page
$page_title = "...";
?>
```

### Bonnes Pratiques POO

1. **Toujours utiliser les classes** au lieu de requêtes SQL directes:
   ```php
   // ❌ MAUVAIS
   $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");

   // ✅ BON
   $user = new User($pdo, $userId);
   $data = $user->getData();
   ```

2. **Transactions pour opérations critiques** (déjà intégrées dans Trip::book(), Trip::cancel())

3. **Validation dans les classes métier**, pas dans les pages

4. **Gestion d'erreurs avec Exceptions**:
   ```php
   try {
       $user->updatePassword($old, $new);
   } catch (Exception $e) {
       $error = $e->getMessage();
   }
   ```

5. **Logger les actions importantes**:
   ```php
   Session::logAction('action_name', ['detail' => 'value']);
   ```

## Code Patterns & Conventions

### Logging User Activity

```php
// Via Session (si utilisateur connecté)
Session::logAction('trip_created', [
    'trip_id' => $tripId,
    'destination' => 'Lyon'
]);

// Direct (si user_id disponible)
logUserActivity($userId, 'profile_updated', [
    'fields' => ['email', 'phone'],
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
]);
```

### Database Queries (Toujours avec prepared statements)

```php
// Via classes POO (préféré)
$user = new User($pdo, $userId);

// Requêtes personnalisées (si nécessaire)
$pdo = Database::getConnection();
$stmt = $pdo->prepare("SELECT * FROM table WHERE column = :value");
$stmt->execute([':value' => $value]);
$result = $stmt->fetch();
```

### Security Practices

- **Passwords**: `password_hash()` et `password_verify()` (géré par User class)
- **XSS**: Toujours `htmlspecialchars()` pour l'affichage
- **SQL Injection**: PDO prepared statements (intégré dans toutes les classes)
- **Session**: `session_regenerate_id(true)` au login (géré par Session class)
- **CSRF**: À implémenter si besoin (tokens CSRF dans les formulaires)

## Test Accounts

- **Passager**: marie@email.com / password123
- **Conducteur**: demo@driver.com / password123
- **Admin**: admin@ecoride.fr / password123

## Common Issues & Solutions

### Erreur "Class not found"

Si une classe n'est pas trouvée, vérifier qu'elle est bien chargée dans `config/database.php`:
```php
require_once __DIR__ . '/../classes/NomClasse.php';
```

### Erreur "Call to undefined function"

Les anciennes fonctions procédurales sont définies à la fin de `includes/session.php`. Si la fonction n'existe pas, utiliser directement la classe:
- `isLoggedIn()` → `Session::isLoggedIn()`
- `loginUser()` → `Session::login()`
- etc.

### MongoDB Connection Failures

L'app bascule automatiquement sur JSON (`mongodb/user_logs.json`). Vérifier:
1. `.env` existe avec `MONGO_URI` valide
2. MongoDB Atlas IP whitelist (0.0.0.0/0 pour dev)

### Transactions qui échouent

Les méthodes critiques (Trip::book(), Trip::cancel()) utilisent des transactions. En cas d'erreur, la transaction est rollback automatiquement. Vérifier les logs d'erreur PHP.

## Database Schema Modifications (v0.2.0)

**Colonnes ajoutées**:
- `trips.updated_at`
- `bookings.updated_at`
- `vehicles.updated_at`

**Renommage**:
- `trips.description` → `trips.preferences`

**Index ajoutés**:
- `idx_trips_status` sur `trips.status`
- `idx_bookings_status` sur `bookings.status`
- `idx_users_email` sur `users.email`

## Testing & Quality Assurance

**Note**: Ce projet inclut maintenant:
- ✅ Tests manuels avec comptes de test
- ✅ Validation intégrée dans les classes (47 méthodes testées)
- ✅ Logging MongoDB pour traçabilité
- ❌ Pas de tests automatisés (PHPUnit) pour l'instant
- ❌ Pas de linting (PHP_CodeSniffer, PHPStan)

Tests manuels recommandés:
- Créer/modifier/supprimer utilisateur, trajet, véhicule
- Tester réservation avec gestion automatique des crédits
- Tester annulation avec remboursement automatique
- Vérifier les logs MongoDB (`test-mongodb.php`)
- Tester interface admin (stats, actions sur users/trips)

## Design System

- **Primary Color**: #4B6B52 (vert)
- **Secondary Color**: #3d5943 (vert foncé)
- **Font**: Inter
- **Icons**: Font Awesome
- **CSS Framework**: Bootstrap 5.3

## Changelog

Voir `CHANGELOG.md` pour l'historique complet des modifications POO.

**Version actuelle**: 0.2.0 (Refactorisation POO complète)
- 1500+ lignes de code POO
- 47 méthodes fonctionnelles
- ~70% de réduction de code dans les pages
- Architecture professionnelle conforme ECF

## Notes Importantes

- **Migration progressive**: Les anciennes fonctions procédurales fonctionnent encore (backward compatibility) mais préférer les classes POO
- **Autoloading**: Les classes sont auto-chargées via `config/database.php`
- **Output buffering**: Le pattern `index.php` capture `$page_title` et `$extra_css` des pages
- **Transactions SQL**: Utilisées automatiquement pour les opérations critiques (réservations, annulations)
- **Validation**: Toujours effectuée dans les classes métier, pas dans les pages
