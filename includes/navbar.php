<?php
// ========== NAVBAR PRINCIPALE ECORIDE ==========
// Fichier : includes/navbar.php

// Déterminer la page active pour le style
$current_page = $_GET['page'] ?? 'home';
?>

<!-- Navigation principale -->
<nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
    <div class="container">
        <!-- Logo EcoRide -->
        <a class="navbar-brand" href="?page=home">
            <i class="fas fa-leaf"></i>
            EcoRide
        </a>

        <!-- Bouton hamburger mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu navigation -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'home') ? 'active' : '' ?>" href="?page=home">
                        <i class="fas fa-home"></i> Accueil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'search') ? 'active' : '' ?>" href="?page=search">
                        <i class="fas fa-search"></i> Covoiturages
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'about') ? 'active' : '' ?>" href="?page=about">
                        <i class="fas fa-info-circle"></i> À propos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'contact') ? 'active' : '' ?>" href="?page=contact">
                        <i class="fas fa-envelope"></i> Contact
                    </a>
                </li>
            </ul>

            <!-- MODIFICATION À APPORTER DANS includes/navbar.php -->
            <!-- Remplacer la section utilisateur connecté par : -->

            <?php if (isLoggedIn()): ?>
                <!-- Utilisateur connecté -->
                <div class="navbar-nav ms-auto">
                    <!-- Dropdown menu utilisateur -->
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i>
                            <?= getCurrentUser()['username'] ?>
                            <span class="badge bg-success ms-1"><?= getCurrentUser()['credits'] ?> crédits</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?page=profile">
                                    <i class="fas fa-user"></i> Mon profil
                                </a></li>
                            <li><a class="dropdown-item" href="?page=my-trips">
                                    <i class="fas fa-route"></i> Mes trajets
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="?page=search">
                                    <i class="fas fa-search"></i> Rechercher un trajet
                                </a></li>
                            <li><a class="dropdown-item" href="?page=create-trip">
                                    <i class="fas fa-plus"></i> Proposer un trajet
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="?page=logout">
                                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                                </a></li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <!-- Visiteur -->
                <div class="navbar-nav ms-auto">
                    <a href="?page=login" class="btn btn-outline-success me-2">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </a>
                    <a href="?page=register" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Inscription
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php
// FONCTIONNALITÉS DE CETTE NAVBAR :
// 1. Responsive (hamburger menu mobile)
// 2. Page active mise en évidence
// 3. Menu différent selon si connecté ou pas
// 4. Icons pour améliorer l'UX
// 5. Dropdown pour l'utilisateur connecté
?>