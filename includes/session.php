<?php

// Configuration base de données et MongoDB
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mongodb.php';

// Démarre la session si c'est pas encore fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Classe Session - Gestion complète des sessions utilisateur
 * 
 * J'ai centralisé toute la logique de session ici pour éviter de répéter
 * le même code partout. Ça gère la connexion, déconnexion, vérifications
 * et aussi les logs MongoDB pour tracer les actions.
 */
class Session
{
    // Durée par défaut avant expiration (2 heures)
    private static $timeout = 7200;

    /**
     * Vérifier si quelqu'un est connecté
     * Simple et efficace, juste vérifier que user_id existe en session
     */
    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Connecter un utilisateur après validation login
     * Je régénère l'ID session pour éviter les attaques de fixation
     * 
     * @param array $userData - Les données user récupérées de la base
     * @return bool
     */
    public static function login($userData)
    {
        if (!is_array($userData) || empty($userData['id'])) {
            return false;
        }

        // Sécurité : nouveau ID session pour éviter les hijacks
        session_regenerate_id(true);

        // Stocker les infos en session
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['email'] = $userData['email'];
        $_SESSION['credits'] = $userData['credits'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['login_time'] = time();

        // Logger dans MongoDB pour traçabilité
        logUserActivity($userData['id'], 'login', [
            'username' => $userData['username'],
            'role' => $userData['role']
        ]);

        return true;
    }

    /**
     * Déconnecter l'utilisateur et tout nettoyer
     * Important de bien supprimer tous les cookies et sessions
     */
    public static function logout()
    {
        // Logger avant de supprimer les données session
        if (self::isLoggedIn()) {
            logUserActivity($_SESSION['user_id'], 'logout');
        }

        // Vider toutes les variables de session
        $_SESSION = array();

        // Supprimer le cookie "remember me" si il existe
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
            unset($_COOKIE['remember_token']);
        }

        // Supprimer le cookie de session PHP
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

        // Détruire la session complètement
        session_destroy();
    }

    /**
     * Récupérer les données de l'utilisateur connecté
     * Pratique pour afficher le nom, crédits, etc. dans navbar
     */
    public static function getCurrentUser()
    {
        if (!self::isLoggedIn()) {
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
     * Mettre à jour les crédits en session ET en base
     * Utile après achat/vente de trajets pour garder l'affichage à jour
     */
    public static function updateCredits($newCredits)
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        try {
            $pdo = Database::getConnection();

            // Mise à jour en base d'abord
            $stmt = $pdo->prepare("UPDATE users SET credits = :credits WHERE id = :user_id");
            $result = $stmt->execute([
                ':credits' => $newCredits,
                ':user_id' => $_SESSION['user_id']
            ]);

            if ($result) {
                // Puis en session pour l'affichage
                $_SESSION['credits'] = $newCredits;
                return true;
            }
        } catch (Exception $e) {
            error_log("Erreur update crédits: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Rediriger vers login si pas connecté
     * Je sauvegarde l'URL pour y revenir après connexion
     */
    public static function requireLogin($redirectUrl = null)
    {
        if (!self::isLoggedIn()) {
            if ($redirectUrl) {
                $_SESSION['redirect_after_login'] = $redirectUrl;
            }
            header('Location: ?page=login');
            exit;
        }
    }

    /**
     * Récupérer l'URL de redirection après login
     * Pour renvoyer l'user là où il était avant la connexion
     */
    public static function getRedirectAfterLogin()
    {
        $redirect = $_SESSION['redirect_after_login'] ?? null;
        unset($_SESSION['redirect_after_login']); // Supprimer après usage
        return $redirect;
    }

    /**
     * Vérifier que la session n'a pas expiré
     * Par défaut 2h, mais ça peut être configuré
     */
    public static function isSessionValid($timeout = null)
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $sessionTimeout = $timeout ?? self::$timeout;
        $loginTime = $_SESSION['login_time'] ?? 0;

        // Si ça fait trop longtemps, déconnecter automatiquement
        if (time() - $loginTime > $sessionTimeout) {
            self::logout();
            return false;
        }

        return true;
    }

    /**
     * Recharger les données user depuis la base
     * Utile si admin modifie un compte ou si données changent
     */
    public static function refreshUserData()
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare("
                SELECT id, username, email, credits, role 
                FROM users 
                WHERE id = :user_id AND is_active = 1
            ");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user) {
                // Mettre à jour les données en session
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['credits'] = $user['credits'];
                $_SESSION['role'] = $user['role'];
                return true;
            } else {
                // L'user a été supprimé ou banni, le déconnecter
                self::logout();
                return false;
            }
        } catch (Exception $e) {
            error_log("Erreur refresh user data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     * Pratique pour les pages admin ou fonctionnalités restreintes
     */
    public static function hasRole($requiredRoles)
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $userRole = $_SESSION['role'];

        // Un seul rôle à vérifier
        if (is_string($requiredRoles)) {
            return $userRole === $requiredRoles;
        }

        // Plusieurs rôles possibles
        if (is_array($requiredRoles)) {
            return in_array($userRole, $requiredRoles);
        }

        return false;
    }

    /**
     * Gestion du "Remember Me" avec cookies
     * Vérification automatique au chargement des pages
     */
    public static function checkRememberMe()
    {
        // Si déjà connecté, pas besoin
        if (self::isLoggedIn()) {
            return;
        }

        // Pas de cookie remember_token
        if (!isset($_COOKIE['remember_token'])) {
            return;
        }

        try {
            $pdo = Database::getConnection();

            // Décoder le token (j'encode user_id:email en base64)
            $tokenData = base64_decode($_COOKIE['remember_token']);
            $parts = explode(':', $tokenData);

            if (count($parts) !== 2) {
                // Token corrompu, le supprimer
                setcookie('remember_token', '', time() - 3600, '/', '', false, true);
                return;
            }

            $userId = $parts[0];
            $email = $parts[1];

            // Vérifier que l'user existe toujours et est actif
            $stmt = $pdo->prepare("
                SELECT id, username, email, credits, role, is_active 
                FROM users 
                WHERE id = :id AND email = :email AND is_active = 1
            ");
            $stmt->execute([':id' => $userId, ':email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                // Reconnecter automatiquement
                self::login($user);
            } else {
                // User inexistant/inactif, supprimer le cookie
                setcookie('remember_token', '', time() - 3600, '/', '', false, true);
            }
        } catch (Exception $e) {
            // En cas d'erreur, supprimer le cookie pour sécurité
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
            error_log("Erreur remember me: " . $e->getMessage());
        }
    }

    /**
     * Créer un token "Remember Me" lors du login
     * Appelé si l'user coche "Se souvenir de moi"
     */
    public static function createRememberToken($userId, $email, $duration = 2592000) // 30 jours par défaut
    {
        // Encoder les données en base64 (simple mais suffisant)
        $tokenData = base64_encode($userId . ':' . $email);

        // Créer le cookie sécurisé
        setcookie(
            'remember_token',
            $tokenData,
            time() + $duration,
            '/',
            '',
            false, // HTTPS en production
            true   // HttpOnly pour sécurité
        );
    }

    /**
     * Logger une action utilisateur spécifique
     * Wrapper pour simplifier l'usage dans les autres classes
     */
    public static function logAction($action, $details = [])
    {
        if (self::isLoggedIn()) {
            logUserActivity($_SESSION['user_id'], $action, $details);
        }
    }
}

// Au chargement de ce fichier, vérifier remember me automatiquement
Session::checkRememberMe();

/*
=== NOTES POUR MOI ===

J'ai gardé la logique principale en méthodes statiques pour faciliter l'usage.
Pas besoin d'instancier la classe, on peut directement faire Session::isLoggedIn().

La compatibilité avec navbar.php est simple :
- isLoggedIn() devient Session::isLoggedIn()  
- getCurrentUser() devient Session::getCurrentUser()
- etc.

Le remember me fonctionne automatiquement grâce au checkRememberMe() 
qui s'exécute à chaque chargement de page.

Pour la sécurité j'ai gardé :
- Régénération ID session
- Suppression complète des cookies
- Validation timeout
- Logs MongoDB pour traçabilité

=== MIGRATION ===

Les anciennes pages qui utilisent les fonctions procédurales vont planter.
Il faut soit :
1. Ajouter des fonctions wrapper pour compatibilité
2. Modifier toutes les pages (recommandé)

Je pense qu'on devrait faire le 2 pour avoir du code propre.
*/