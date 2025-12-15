# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

EcoRide is an ecological carpooling platform built with PHP, MySQL, and MongoDB. The application enables users to share rides, with a focus on electric vehicles and a credit-based payment system.

- **Production URL**: https://ecoride-guillaume.onrender.com
- **Tech Stack**: PHP 8.1+, MySQL 5.7+, MongoDB Atlas, Bootstrap 5.3, Apache
- **Deployment**: Dockerized on Render.com
- **Local Development**: Laragon (recommended), XAMPP, or WAMP

## Architecture

### Routing & Request Flow

The application uses a **front-controller pattern** with query parameter-based routing:

1. All requests go through `index.php`
2. Page selection via `?page=` query parameter (e.g., `?page=search`)
3. Output buffering is used to capture page-specific variables (title, CSS) before rendering the layout
4. Flow: page content → header (with captured variables) → navbar → content → footer

**Allowed pages**: home, search, login, detail, register, logout, profile, my-trips, about, contact, logs

### Dual Database Architecture

**MySQL (Primary Data)**:
- Configured in `config/database.php`
- Stores: users, trips, vehicles, bookings, reviews
- Global `$pdo` object for queries
- Auto-detects environment (local vs production via `getenv('DB_HOST')`)

**MongoDB (Activity Logs)**:
- Configured in `config/mongodb.php`
- Stores: user activity logs (login, logout, register, etc.)
- Fallback to JSON files (`mongodb/user_logs.json`) if connection fails
- Global `$mongoLogger` instance

### Session Management

Centralized in `includes/session.php`:
- **Must be included BEFORE** any session operations
- Database configs are loaded automatically when session.php is included
- Key functions: `isLoggedIn()`, `loginUser()`, `logoutUser()`, `getCurrentUser()`
- Features: auto-login via remember_token cookie, session timeout (2h default), role-based access
- All login/logout actions are logged to MongoDB

### Important Include Order

```php
// CORRECT order (as of commit a7b148e):
require_once 'config/database.php';   // Load DB first
require_once 'config/mongodb.php';    // Then MongoDB
// Now session_start() can safely access both DBs
```

This order was recently fixed to prevent session errors. Do NOT change it.

## Development Workflow

### Local Setup

```bash
# 1. Clone and navigate
git clone https://github.com/Guillaume555/ecoride.git
cd ecoride

# 2. Install PHP dependencies
composer install

# 3. Import database
# Import sql/database_structure.sql first, then sql/database_data.sql

# 4. Configure environment
# Edit config/database.php if needed (defaults to localhost/root)
# Create .env file with MONGO_URI for MongoDB Atlas connection

# 5. Start server
# Start Laragon/XAMPP/WAMP and access http://localhost/ecoride
```

### Database Initialization

```bash
# Using MySQL command line:
mysql -u root -p ecoride < sql/database_structure.sql
mysql -u root -p ecoride < sql/database_data.sql
```

### Testing Connections

```bash
# Test MySQL connection
php -S localhost:8000 ping-bdd.php

# Test MongoDB connection and view logs
# Access: http://localhost/ecoride/test-mongodb.php
```

### Environment Variables

**Production (Render.com)**:
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` - MySQL connection
- `DB_SSL` - Set to "true" for Aiven MySQL
- `MONGO_URI` - MongoDB Atlas connection string

**Local**:
- Create `.env` file in root with `MONGO_URI` for MongoDB
- MySQL uses defaults in `config/database.php` if no env vars found

## Code Patterns & Conventions

### Logging User Activity

**Always use** `logUserActivity()` for new code:

```php
logUserActivity($user_id, 'action_name', [
    'detail_key' => 'value',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
]);
```

**Note**: `logActivity()` exists as a backward-compatibility alias but prefer `logUserActivity()`.

### Database Queries

**Use prepared statements** (security requirement):

```php
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();
```

**Helper functions** in `config/database.php`:
- `searchTrips($departure, $arrival, $date)` - Search for rides
- `getStatistics()` - Homepage stats
- `userExists($email)` - Check if user exists

### Session Functions

```php
// Check if logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
}

// Require authentication
requireLogin(); // Redirects to login if not authenticated

// Role-based access
if (hasRole('admin')) {
    // Admin-only code
}

// Update credits
updateUserCredits($newAmount);

// Refresh user data from DB
refreshUserData();
```

### Security Practices

- Passwords: Use `password_hash()` for storage, `password_verify()` for validation
- XSS Prevention: Always use `htmlspecialchars()` for output
- SQL Injection: Use PDO prepared statements exclusively
- Session Security: `session_regenerate_id(true)` on login
- Remember Me: Secure, HttpOnly cookies with base64 encoded user_id:email

## Project Structure

```
ecoride/
├── index.php              # Front controller & router
├── config/
│   ├── database.php       # MySQL PDO connection + helper functions
│   └── mongodb.php        # MongoDB logger class + activity functions
├── includes/
│   ├── session.php        # Session management (CRITICAL - load first)
│   ├── header.php         # HTML head (uses $page_title from pages)
│   ├── navbar.php         # Navigation bar
│   └── footer.php         # Footer
├── pages/                 # Individual page views
│   ├── home.php           # Landing page
│   ├── search.php         # Search trips
│   ├── login.php          # Authentication
│   ├── register.php       # User registration
│   ├── profile.php        # User profile
│   ├── my-trips.php       # User's trips
│   ├── detail.php         # Trip details
│   └── logs.php           # Activity logs (admin)
├── assets/
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript
│   └── img/              # Images
├── sql/
│   ├── database_structure.sql  # Schema
│   └── database_data.sql       # Seed data
├── mongodb/              # JSON log fallback directory
├── docs/                 # Project documentation (Word files)
├── Dockerfile            # Production container config
├── render.yaml           # Render.com deployment config
├── ping-bdd.php          # Database health check endpoint
└── test-mongodb.php      # MongoDB connection test UI
```

## Docker & Deployment

### Dockerfile Notes

- Base: `php:8.1-apache`
- Extensions: `pdo`, `pdo_mysql`, `mongodb` (via PECL)
- Port: 10000 (configured for Render.com)
- Composer dependencies installed with `--no-dev --optimize-autoloader`
- Apache rewrite module enabled

### Render.com Deployment

The application auto-deploys from the main branch. Environment variables must be configured in Render dashboard:
- MySQL credentials (Aiven)
- MongoDB URI (Atlas)

Database ping endpoint: `/ping-bdd.php` (returns "OK" with timestamp)

## Test Accounts

- **Passenger**: marie@email.com / password123
- **Driver**: demo@driver.com / password123 (not yet operational)
- **Admin**: admin@ecoride.fr / password123 (not yet operational)

## Common Issues & Solutions

### Session Include Order

If you encounter "Call to undefined function" errors for MongoDB functions, verify that `includes/session.php` loads database configs BEFORE `session_start()`. This was fixed in commit a7b148e.

### MongoDB Connection Failures

The app gracefully degrades to JSON file logging if MongoDB is unavailable. Check:
1. `.env` file exists with valid `MONGO_URI`
2. MongoDB Atlas IP whitelist includes your IP (or use 0.0.0.0/0 for development)
3. Fallback logs in `mongodb/user_logs.json`

### Local Database Connection

Default local config assumes:
- Host: `localhost:3306`
- Database: `ecoride`
- User: `root`
- Password: *(empty)*

Modify `config/database.php` constants if your setup differs.

## Design System

- **Primary Color**: #4B6B52 (green)
- **Secondary Color**: #3d5943 (dark green)
- **Font**: Inter
- **Icons**: Font Awesome
- **CSS Framework**: Bootstrap 5.3

## Additional Notes

- This project was created for the ECF evaluation (French Web Developer certification, 2025)
- The output buffering pattern in `index.php` allows pages to define variables (like `$page_title`) that are used in `includes/header.php`
- MongoDB migration function exists: `$mongoLogger->migrateFromJson()` to bulk-import JSON logs
