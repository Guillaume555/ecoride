<?php
// ======================================================
// Fichier : includes/header.php
// Rôle : définit l'en-tête HTML de chaque page (balises <head> et début <body>)
// ======================================================

// Si aucune variable de titre n’a été définie depuis la page, on affecte un titre par défaut
$page_title = $page_title ?? "EcoRide - Partageons la route, économisons la planète";
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <!-- Encodage des caractères -->
    <meta charset="UTF-8">

    <!-- Adaptation mobile (responsive design) -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Métadonnées SEO (référencement naturel) -->
    <meta name="description" content="Plateforme de covoiturage écologique et économique">
    <meta name="keywords" content="covoiturage, écologie, transport, économie">
    <meta name="author" content="EcoRide">

    <!-- Titre de la page (affiché dans l’onglet) -->
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Feuille de style Bootstrap (framework CSS) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bibliothèque d’icônes Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Police Google Fonts (Inter, utilisée dans la charte graphique) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Feuille de style principale du site -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Feuilles de style spécifiques à certaines pages (si définies dynamiquement depuis la page concernée) -->
    <?php if (isset($extra_css) && !empty($extra_css)): ?>
        <?php foreach ($extra_css as $css_file): ?>
            <link rel="stylesheet" href="assets/css/<?= $css_file ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>

<body>