# 🌱 EcoRide - Plateforme de Covoiturage Écologique

**Application web de covoiturage** développée dans le cadre de l'ECF du Titre Professionnel Développeur Web et Web Mobile.

## 📋 Description du Projet

EcoRide est une plateforme de covoiturage qui encourage les déplacements écologiques et économiques. L'application permet aux utilisateurs de proposer ou rechercher des trajets en voiture, avec un système de crédits intégré.

## ⚡ Installation et Configuration

### Prérequis
- **Serveur local** : Laragon, XAMPP ou WAMP
- **PHP** : Version 7.4 ou supérieure
- **MySQL** : Version 5.7 ou supérieure
- **Navigateur** : Chrome, Firefox, Safari (dernières versions)

### Étapes d'installation

1. **Cloner le projet**
```bash
git clone [URL_DU_DEPOT]
cd ecoride
```

2. **Configuration de la base de données**
- Ouvrir HeidiSQL ou phpMyAdmin
- Importer le fichier `sql/database_structure.sql`
- Importer le fichier `sql/database_data.sql`

3. **Configuration PHP**
- Vérifier que `config/database.php` pointe vers votre base MySQL
- Paramètres par défaut Laragon :
  - Host: `localhost`
  - Database: `ecoride`
  - User: `root`
  - Password: (vide)

4. **Lancer l'application**
- Démarrer Laragon/XAMPP
- Accéder à `http://localhost/ecoride`

## 👥 Comptes de Test

### Passager
- **Email** : `marie@email.com`
- **Mot de passe** : `password123`
- **Crédits** : 20

### Conducteur
- **Email** : `pierre@email.com`
- **Mot de passe** : `password123`
- **Crédits** : 15

### Administrateur
- **Email** : `admin@ecoride.fr`
- **Mot de passe** : `password123`
- **Crédits** : 100

## 🚀 Fonctionnalités Implémentées

### ✅ US Réalisées (80% du projet)

- **US1** : Page d'accueil avec présentation entreprise ✅
- **US2** : Menu de navigation responsive ✅
- **US3** : Vue des covoiturages avec recherche ✅
- **US4** : Filtres des covoiturages (prix, type véhicule) ✅
- **US5** : Vue détaillée d'un covoiturage ✅
- **US6** : Système de réservation (en cours) ⚠️
- **US7** : Création de compte et authentification ✅
- **US8** : Espace utilisateur avec profil ✅

### Fonctionnalités principales

1. **Authentification sécurisée**
   - Inscription avec 20 crédits offerts
   - Connexion avec sessions PHP
   - Hachage des mots de passe

2. **Recherche de trajets**
   - Filtres par ville, date, prix
   - Badge écologique pour véhicules électriques
   - Pagination et tri des résultats

3. **Espace utilisateur**
   - Profil avec statistiques personnelles
   - Historique des trajets (conducteur/passager)
   - Gestion des réservations

4. **Système de crédits**
   - Paiement en crédits EcoRide
   - Conversion 1 crédit = 1 euro
   - Remboursement automatique en cas d'annulation

## 🏗️ Architecture Technique

### Stack Technologique
- **Frontend** : HTML5, CSS3, Bootstrap 5.3, JavaScript
- **Backend** : PHP 8.x avec PDO
- **Base de données** : MySQL 8.x
- **Serveur** : Apache (Laragon)

### Structure du projet
```
ecoride/
├── index.php              # Router principal
├── config/
│   └── database.php       # Configuration BDD
├── includes/
│   ├── header.php         # En-tête HTML
│   ├── navbar.php         # Navigation
│   ├── footer.php         # Pied de page
│   └── session.php        # Gestion sessions
├── pages/
│   ├── home.php           # Page d'accueil
│   ├── search.php         # Recherche trajets
│   ├── detail.php         # Détail trajet
│   ├── login.php          # Connexion
│   ├── register.php       # Inscription
│   ├── profile.php        # Profil utilisateur
│   └── my-trips.php       # Mes trajets
├── assets/
│   └── css/
│       ├── home.css       # Styles accueil
│       ├── search.css     # Styles recherche
│       ├── detail.css     # Styles détail
│       └── auth.css       # Styles authentification
└── sql/
    ├── database_structure.sql
    └── database_data.sql
```

## 🎨 Charte Graphique

### Couleurs principales
- **Primaire** : #4B6B52 (Vert EcoRide)
- **Secondaire** : #3d5943 (Vert foncé)
- **Arrière-plan** : #F5F8FA (Gris clair)

### Typography
- **Police** : Inter (Google Fonts)
- **Icônes** : Font Awesome 6.4.0

## 🔒 Sécurité

- **Protection CSRF** : Tokens de session
- **Injection SQL** : Requêtes préparées PDO
- **XSS** : Échappement avec `htmlspecialchars()`
- **Mots de passe** : Hachage `password_hash()` BCRYPT

## 📱 Responsive Design

L'application est entièrement responsive :
- **Mobile** : < 576px
- **Tablette** : 576px - 992px  
- **Desktop** : > 992px

## 🧪 Tests

### Parcours utilisateur testés
1. **Visiteur** → Recherche → Détail → Inscription → Connexion
2. **Utilisateur** → Recherche → Réservation → Profil
3. **Conducteur** → Création trajet → Gestion passagers

### Navigateurs testés
- Chrome 120+ ✅
- Firefox 118+ ✅
- Safari 16+ ✅
- Edge 119+ ✅

## 📞 Support

**Développeur** : [Votre nom]  
**Email** : [Votre email]  
**Projet ECF** : Titre Professionnel DWWM 2025  

## 📄 Licence

Projet éducatif - ECF Studi 2025