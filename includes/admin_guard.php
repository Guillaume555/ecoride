<?php

/**
 * ========================================
 * FICHIER : includes/admin_guard.php
 * ========================================
 * 
 * DESCRIPTION :
 * Middleware de protection pour les pages administrateur
 * Vérifie que l'utilisateur connecté a le rôle 'admin'
 * 
 * ENTRÉES :
 * - Session utilisateur active (vérifie via session.php)
 * - Rôle de l'utilisateur dans la session
 * 
 * TRAITEMENTS :
 * 1. Vérification que l'utilisateur est connecté
 * 2. Vérification que le rôle est 'admin'
 * 3. Redirection si non autorisé
 * 4. Logging des tentatives d'accès non autorisées
 * 
 * SORTIES :
 * - Autorisation d'accès si admin
 * - Redirection vers login si non connecté
 * - Redirection vers home avec message erreur si non admin
 * 
 * SÉCURITÉ :
 * - Double vérification (connexion + rôle)
 * - Logging des tentatives d'accès
 * - Messages d'erreur génériques (pas de détails)
 * - Arrêt immédiat du script si non autorisé
 * ========================================
 */

// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie que l'utilisateur connecté est un administrateur
 * Redirige vers login ou home si non autorisé
 * 
 * @return void
 */
function requireAdmin()
{
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        // Log de la tentative d'accès non autorisée
        logAdminAccess('UNAUTHORIZED_ATTEMPT', 'Not logged in', $_SERVER['REQUEST_URI']);

        // Message d'erreur dans la session
        $_SESSION['error_message'] = "Vous devez être connecté pour accéder à cette page.";

        // Redirection vers la page de connexion
        header('Location: ?page=login');
        exit;
    }

    // Vérifier le rôle de l'utilisateur
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        // Log de la tentative d'accès non autorisée
        logAdminAccess('FORBIDDEN', $_SESSION['username'] ?? 'Unknown', $_SERVER['REQUEST_URI']);

        // Message d'erreur dans la session
        $_SESSION['error_message'] = "Accès refusé. Vous n'avez pas les autorisations nécessaires.";

        // Redirection vers la page d'accueil
        header('Location: ?page=home');
        exit;
    }

    // Log de l'accès autorisé
    logAdminAccess('ACCESS_GRANTED', $_SESSION['username'], $_SERVER['REQUEST_URI']);
}

/**
 * Enregistre les tentatives d'accès à l'espace admin
 * Utile pour l'audit et la détection d'intrusions
 * 
 * @param string $type Type d'événement (ACCESS_GRANTED, UNAUTHORIZED_ATTEMPT, FORBIDDEN)
 * @param string $username Nom de l'utilisateur (ou 'Unknown')
 * @param string $page Page tentée d'accéder
 * @return void
 */
function logAdminAccess($type, $username, $page)
{
    // Chemin du fichier de log
    $logFile = __DIR__ . '/../logs/admin_access.log';

    // Créer le dossier logs s'il n'existe pas
    $logDir = dirname($logFile);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // Préparer les informations de log
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User Agent';

    // Format du log
    $logEntry = sprintf(
        "[%s] %s | User: %s | IP: %s | Page: %s | User-Agent: %s\n",
        $timestamp,
        $type,
        $username,
        $ip,
        $page,
        $userAgent
    );

    // Écrire dans le fichier de log
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Vérifie si l'utilisateur actuel est admin (sans redirection)
 * Utile pour afficher/masquer des éléments dans l'interface
 * 
 * @return bool True si l'utilisateur est admin
 */
function isAdmin()
{
    return isset($_SESSION['user_id']) &&
        isset($_SESSION['role']) &&
        $_SESSION['role'] === 'admin';
}

/**
 * Récupère les informations de l'admin connecté
 * 
 * @return array|null Informations de l'admin ou null si non connecté
 */
function getAdminInfo()
{
    if (!isAdmin()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}

/**
 * Affiche un badge "ADMINISTRATEUR" dans la navbar
 * À appeler dans navbar.php
 * 
 * @return string HTML du badge admin
 */
function displayAdminBadge()
{
    if (!isAdmin()) {
        return '';
    }

    return '<span class="badge bg-danger ms-2" title="Vous êtes administrateur">
                <i class="fas fa-shield-alt"></i> ADMIN
            </span>';
}

// ========================================
// EXEMPLE D'UTILISATION
// ========================================
/*

// En haut de chaque page admin (dashboard.php, users.php, etc.)
<?php
require_once 'includes/session.php';
require_once 'includes/admin_guard.php';
requireAdmin(); // ← Bloque l'accès si non admin
?>

// Dans navbar.php pour afficher le badge
<?php if (isLoggedIn()): ?>
    <span>Bonjour <?= getCurrentUser()['username'] ?></span>
    <?= displayAdminBadge() ?>
<?php endif; ?>

// Pour vérifier si admin dans une condition
<?php if (isAdmin()): ?>
    <a href="?page=admin-dashboard">Espace Admin</a>
<?php endif; ?>

*/
