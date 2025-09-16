<?php
// Fichier principal de routage, gère l'affichage des pages avec un ordre logique

session_start();

// Vérifie si l'utilisateur a choisi l'option 'Se souvenir de moi' et tente une reconnexion automatique
if (file_exists('includes/session.php')) {
    require_once 'includes/session.php';
    checkRememberMeLogin();
}

// On récupère le nom de la page à afficher via l'URL, ou on redirige vers l'accueil si non valide
$page = $_GET['page'] ?? 'home';
$allowed_pages = ['home', 'search', 'login', 'detail', 'register', 'logout', 'profile', 'my-trips', 'about', 'contact', 'logs'];

// On filtre les pages autorisées pour éviter toute tentative d'injection ou d'accès interdit
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

// Cas particulier : si la page demandée est 'logout', on déconnecte l'utilisateur
if ($page === 'logout') {
    require_once 'includes/session.php';
    logoutUser();
    header('Location: ?page=home');
    exit;
}

// Étape 1 : On inclut la page dans un tampon pour en récupérer les variables comme le titre ou les styles CSS
ob_start(); // On démarre la capture du contenu de la page
include "pages/$page.php";
$page_content = ob_get_clean(); // On récupère le contenu généré et on vide le tampon de sortie

// Grâce à l'inclusion préalable, on peut maintenant utiliser les variables définies dans la page

// Étape 2 : On insère l'en-tête HTML en utilisant les variables récupérées
include 'includes/header.php';

// Étape 3 : On ajoute la barre de navigation de l'application
include 'includes/navbar.php';

// Étape 4 : On affiche le contenu HTML spécifique à la page demandée
echo $page_content;

// Étape 5 : On termine par le pied de page (footer) commun à toutes les pages
include 'includes/footer.php';
