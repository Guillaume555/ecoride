<?php
// ========== INDEX.PHP CORRIGÉ ECORIDE ==========
// Router principal avec ordre d'inclusion corrigé

session_start();

// Gestion auto-connexion "Se souvenir de moi"
if (file_exists('includes/session.php')) {
    require_once 'includes/session.php';
    checkRememberMeLogin();
}

// Récupération de la page demandée
$page = $_GET['page'] ?? 'home';
$allowed_pages = ['home', 'search', 'login', 'detail', 'register', 'logout', 'profile', 'my-trips'];

// Vérification sécurité
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

// Gestion des pages spéciales
if ($page === 'logout') {
    require_once 'includes/session.php';
    logoutUser();
    header('Location: ?page=home');
    exit;
}

// ========== ÉTAPE 1 : CHARGER LES VARIABLES DE LA PAGE ==========

// On inclut la page TEMPORAIREMENT pour récupérer ses variables
ob_start(); // Commencer la capture de sortie
include "pages/$page.php";
$page_content = ob_get_clean(); // Capturer le contenu et nettoyer

// Maintenant les variables $page_title et $extra_css sont disponibles !

// ========== ÉTAPE 2 : INCLURE LE HEADER (avec les bonnes variables) ==========
include 'includes/header.php';

// ========== ÉTAPE 3 : INCLURE LA NAVBAR ==========
include 'includes/navbar.php';

// ========== ÉTAPE 4 : AFFICHER LE CONTENU DE LA PAGE ==========
echo $page_content;

// ========== ÉTAPE 5 : INCLURE LE FOOTER ==========
include 'includes/footer.php';

/*
EXPLICATION DE LA CORRECTION :

PROBLÈME AVANT :
1. include header.php    ← $extra_css n'existe pas encore
2. include navbar.php
3. include pages/home.php ← $extra_css est défini ici (trop tard!)
4. include footer.php

SOLUTION MAINTENANT :
1. include pages/home.php dans un buffer ← $extra_css est défini
2. include header.php                     ← $extra_css est maintenant disponible
3. include navbar.php
4. echo le contenu de la page            ← Afficher le contenu capturé
5. include footer.php

RÉSULTAT : Les CSS sont chargés dans le bon ordre !
*/
