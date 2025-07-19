<?php
/*
================================================
FICHIER: includes/session.php - Gestion des sessions EcoRide
Développé par: [Votre nom]
Description: Fonctions centralisées pour la gestion des sessions utilisateur
================================================
*/

// Démarrage de la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de la configuration base de données
require_once __DIR__ . '/../config/database.php';

/**
 * Vérifie si un utilisateur est connecté
 * @return bool
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Connecte un utilisateur (création de session)
 * @param array $user_data Données de l'utilisateur depuis la BDD
 * @return bool
 */
function loginUser($user_data)
{
    if (!is_array($user_data) || empty($user_data['id'])) {
        return false;
    }

    // Régénération de l'ID de session pour la sécurité
    session_regenerate_id(true);

    // Stockage des données en session
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['email'] = $user_data['email'];
    $_SESSION['credits'] = $user_data['credits'];
    $_SESSION['role'] = $user_data['role'];
    $_SESSION['login_time'] = time();

    return true;
}

/**
 * Déconnecte l'utilisateur (destruction de session)
 */
function logoutUser()
{
    // Destruction de toutes les variables de session
    $_SESSION = array();

    // IMPORTANT : Destruction du cookie "Se souvenir de moi"
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        unset($_COOKIE['remember_token']);
    }

    // Destruction du cookie de session s'il existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destruction de la session
    session_destroy();
}

/**
 * Récupère les données de l'utilisateur connecté
 * @return array|null
 */
function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'credits' => $_SESSION['credits'],
        'role' => $_SESSION['role'],
        'login_time' => $_SESSION['login_time']
    ];
}

/**
 * Met à jour les crédits de l'utilisateur en session et en BDD
 * @param int $new_credits
 * @return bool
 */
function updateUserCredits($new_credits)
{
    global $pdo;

    if (!isLoggedIn()) {
        return false;
    }

    try {
        // Mise à jour en base de données
        $stmt = $pdo->prepare("UPDATE users SET credits = :credits WHERE id = :user_id");
        $result = $stmt->execute([
            ':credits' => $new_credits,
            ':user_id' => $_SESSION['user_id']
        ]);

        if ($result) {
            // Mise à jour en session
            $_SESSION['credits'] = $new_credits;
            return true;
        }
    } catch (Exception $e) {
        error_log("Erreur mise à jour crédits: " . $e->getMessage());
    }

    return false;
}

/**
 * Redirige vers login si pas connecté
 * @param string $redirect_url URL de redirection après connexion
 */
function requireLogin($redirect_url = null)
{
    if (!isLoggedIn()) {
        if ($redirect_url) {
            $_SESSION['redirect_after_login'] = $redirect_url;
        }
        header('Location: ?page=login');
        exit;
    }
}

/**
 * Récupère l'URL de redirection après connexion
 * @return string|null
 */
function getRedirectAfterLogin()
{
    $redirect = $_SESSION['redirect_after_login'] ?? null;
    unset($_SESSION['redirect_after_login']); // Supprimer après utilisation
    return $redirect;
}

/**
 * Vérifie si la session n'a pas expiré (sécurité)
 * @param int $timeout Durée d'expiration en secondes (par défaut 2h)
 * @return bool
 */
function isSessionValid($timeout = 7200)
{
    if (!isLoggedIn()) {
        return false;
    }

    $login_time = $_SESSION['login_time'] ?? 0;

    // Vérification timeout
    if (time() - $login_time > $timeout) {
        logoutUser();
        return false;
    }

    return true;
}

/**
 * Actualise les données utilisateur depuis la BDD
 * @return bool
 */
function refreshUserData()
{
    global $pdo;

    if (!isLoggedIn()) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, username, email, credits, role 
            FROM users 
            WHERE id = :user_id AND is_active = 1
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['credits'] = $user['credits'];
            $_SESSION['role'] = $user['role'];
            return true;
        } else {
            // Utilisateur supprimé ou désactivé
            logoutUser();
            return false;
        }
    } catch (Exception $e) {
        error_log("Erreur rafraîchissement données utilisateur: " . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 * @param string|array $required_roles
 * @return bool
 */
function hasRole($required_roles)
{
    if (!isLoggedIn()) {
        return false;
    }

    $user_role = $_SESSION['role'];

    if (is_string($required_roles)) {
        return $user_role === $required_roles;
    }

    if (is_array($required_roles)) {
        return in_array($user_role, $required_roles);
    }

    return false;
}

/**
 * Vérifie et gère la connexion automatique par cookie "Se souvenir de moi"
 * À appeler au début de chaque page
 */
function checkRememberMeLogin()
{
    global $pdo;

    // Si déjà connecté, pas besoin de vérifier
    if (isLoggedIn()) {
        return;
    }

    // Vérifier si le cookie remember_token existe
    if (!isset($_COOKIE['remember_token'])) {
        return;
    }

    try {
        // Décoder le token
        $token_data = base64_decode($_COOKIE['remember_token']);
        $parts = explode(':', $token_data);

        if (count($parts) !== 2) {
            // Token invalide, le supprimer
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
            return;
        }

        $user_id = $parts[0];
        $email = $parts[1];

        // Vérifier que l'utilisateur existe toujours
        $stmt = $pdo->prepare("
            SELECT id, username, email, credits, role, is_active 
            FROM users 
            WHERE id = :id AND email = :email AND is_active = 1
        ");
        $stmt->execute([':id' => $user_id, ':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Utilisateur valide, le reconnecter automatiquement
            loginUser($user);
        } else {
            // Utilisateur invalide, supprimer le cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    } catch (Exception $e) {
        // En cas d'erreur, supprimer le cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        error_log("Erreur remember me: " . $e->getMessage());
    }
}

/*
================================================
NOTES DE DÉVELOPPEMENT:

1. FONCTIONNALITÉS IMPLÉMENTÉES:
   ✅ Vérification connexion utilisateur
   ✅ Connexion/déconnexion sécurisée
   ✅ Gestion données utilisateur en session
   ✅ Mise à jour crédits (session + BDD)
   ✅ Protection pages avec redirection
   ✅ Expiration automatique des sessions
   ✅ Rafraîchissement données depuis BDD
   ✅ Vérification des rôles utilisateur

2. SÉCURITÉ:
   ✅ Régénération ID session à la connexion
   ✅ Destruction complète session/cookies
   ✅ Timeout automatique (2h par défaut)
   ✅ Vérification utilisateur actif
   ✅ Protection contre session hijacking

3. UTILISATION:
   require_once 'includes/session.php';
   
   if (isLoggedIn()) {
       $user = getCurrentUser();
       echo "Bonjour " . $user['username'];
   }

4. INTÉGRATION:
   ✅ Compatible avec structure existante
   ✅ Gestion des erreurs et logs
   ✅ Fonctions réutilisables
   ✅ Code documenté et maintenable
================================================
*/
