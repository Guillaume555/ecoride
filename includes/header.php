<?php
// ========== HEADER CORRIGÉ ECORIDE ==========
// Fichier : includes/header.php

$page_title = $page_title ?? "EcoRide - Partageons la route, économisons la planète";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Plateforme de covoiturage écologique et économique">
    <meta name="keywords" content="covoiturage, écologie, transport, économie">
    <meta name="author" content="EcoRide">

    <!-- Titre dynamique -->
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS PRINCIPAL (CHEMIN CORRIGÉ - SANS ../) -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- CSS SPÉCIFIQUE À LA PAGE -->
    <?php if (isset($extra_css) && !empty($extra_css)): ?>
        <?php foreach ($extra_css as $css_file): ?>
            <link rel="stylesheet" href="assets/css/<?= $css_file ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>

<body>

    <?php
    // CHANGEMENTS EFFECTUÉS :
    // 1. Supprimé "../" du chemin style.css
    // 2. Le chemin est maintenant "assets/css/style.css" (depuis la racine)
    ?>