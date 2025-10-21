<?php
// Inclusion de la classe Session
require_once __DIR__ . '/session.php';

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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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

            <!-- Menu utilisateur -->
            <?php if (Session::isLoggedIn()): ?>
                <?php
                $user = Session::getCurrentUser();
                $isAdmin = Session::hasRole('admin');
                ?>

                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i>
                            <?= htmlspecialchars($user['username']) ?>
                            <span class="badge bg-success ms-1"><?= $user['credits'] ?> crédits</span>

                            <?php if ($isAdmin): ?>
                                <span class="badge bg-danger ms-1">ADMIN</span>
                            <?php endif; ?>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($isAdmin): ?>
                                <!-- Menu Admin -->
                                <li><a class="dropdown-item fw-bold text-danger" href="?page=admin-dashboard">
                                        <i class="fas fa-chart-line"></i> Tableau de bord Admin
                                    </a></li>
                                <li><a class="dropdown-item" href="?page=admin-users">
                                        <i class="fas fa-users"></i> Gestion utilisateurs
                                    </a></li>
                                <li><a class="dropdown-item" href="?page=admin-trips">
                                        <i class="fas fa-car"></i> Gestion trajets
                                    </a></li>
                                <li><a class="dropdown-item" href="?page=admin-reviews">
                                        <i class="fas fa-star"></i> Modération avis
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="?page=home">
                                        <i class="fas fa-arrow-left"></i> Retour au site
                                    </a></li>
                            <?php else: ?>
                                <!-- Menu Utilisateur Normal -->
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
                            <?php endif; ?>

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
/*
=== CHANGEMENTS APPORTÉS ===

1. Ajout de require_once pour la classe Session
2. Session::isLoggedIn() au lieu de isLoggedIn()
3. Session::getCurrentUser() au lieu de getCurrentUser()
4. Session::hasRole('admin') au lieu de vérifier $_SESSION['role']

=== AVANTAGES ===

- Code plus propre et organisé
- Méthodes centralisées dans la classe Session
- Plus de logique dispersée dans la navbar
- Facilite les modifications futures

Le reste du code reste identique, juste les appels aux fonctions
qui changent pour utiliser la classe Session.
*/
?>