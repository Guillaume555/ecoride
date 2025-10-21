<?php
/*
================================================
FICHIER: pages/admin-dashboard.php - Mon tableau de bord admin
Description: Page principale du back-office avec toutes les stats importantes
================================================
*/

// Inclusion des fonctions de session et classes POO
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/admin_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Admin.php';

// Protection : Seuls les admins peuvent accéder
requireAdmin();

// Configuration de la page
$page_title = "Dashboard Admin - EcoRide";

// ========================================
// RÉCUPÉRATION DES STATISTIQUES AVEC POO
// ========================================

try {
    // Statistiques globales avec Admin::getDashboardStats()
    $dashboardStats = Admin::getDashboardStats($pdo);
    
    // Statistiques détaillées avec les méthodes spécialisées
    $userStats = Admin::getUsersStats($pdo);
    $tripStats = Admin::getTripsStats($pdo);
    $bookingStats = Admin::getBookingsStats($pdo);
    
    // Derniers éléments avec Admin::getRecentUsers() et Admin::getRecentBookings()
    $recent_users = Admin::getRecentUsers($pdo, 5);
    $recent_bookings = Admin::getRecentBookings($pdo, 5);
    
    // Calcul du taux d'occupation moyen (logique spécifique qui reste en SQL)
    $stmt = $pdo->query("
        SELECT 
            AVG((v.seats - t.available_seats) / v.seats * 100) as avg_occupancy
        FROM trips t
        JOIN vehicles v ON t.vehicle_id = v.id
        WHERE t.status = 'completed'
    ");
    $avg_occupancy = round($stmt->fetchColumn() ?? 0, 1);
    
} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des statistiques : " . $e->getMessage();
    
    // Valeurs par défaut en cas d'erreur
    $dashboardStats = [
        'users_count' => 0, 'users_active' => 0, 'trips_count' => 0, 
        'trips_active' => 0, 'bookings_count' => 0, 'bookings_confirmed' => 0,
        'total_credits' => 0, 'average_rating' => 0
    ];
    $userStats = ['total' => 0, 'active' => 0, 'new_this_month' => 0, 'with_vehicles' => 0, 'with_trips' => 0];
    $tripStats = ['total' => 0, 'active' => 0, 'completed' => 0, 'cancelled' => 0, 'average_price' => 0, 'total_seats_offered' => 0];
    $bookingStats = ['total' => 0, 'confirmed' => 0, 'cancelled' => 0, 'total_revenue' => 0, 'average_booking' => 0];
    $recent_users = [];
    $recent_bookings = [];
    $avg_occupancy = 0;
}
?>

<div class="admin-container">

    <!-- En-tête Admin -->
    <div class="admin-header">
        <h1><i class="fas fa-chart-line"></i> Tableau de bord administrateur</h1>
        <p class="subtitle">Vue d'ensemble de la plateforme EcoRide</p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="admin-alert danger">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Navigation Admin -->
    <div class="mb-4">
        <a href="?page=admin-dashboard" class="btn btn-primary me-2">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="?page=admin-users" class="btn btn-outline-primary me-2">
            <i class="fas fa-users"></i> Utilisateurs
        </a>
        <a href="?page=admin-trips" class="btn btn-outline-primary me-2">
            <i class="fas fa-car"></i> Trajets
        </a>
        <a href="?page=admin-reviews" class="btn btn-outline-primary me-2">
            <i class="fas fa-star"></i> Avis
        </a>
        <a href="?page=home" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Retour au site
        </a>
    </div>

    <!-- Grille de statistiques principales -->
    <div class="stats-grid">

        <!-- Card Utilisateurs -->
        <div class="stat-card primary">
            <div class="icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="label">Total Utilisateurs</div>
            <div class="value"><?= number_format($dashboardStats['users_count']) ?></div>
            <div class="change positive">
                <i class="fas fa-arrow-up"></i> +<?= $userStats['new_this_month'] ?> ce mois
            </div>
            <hr>
            <small class="text-muted">
                <i class="fas fa-check-circle text-success"></i> <?= $dashboardStats['users_active'] ?> actifs |
                <i class="fas fa-times-circle text-danger"></i> <?= $dashboardStats['users_count'] - $dashboardStats['users_active'] ?> inactifs
            </small>
        </div>

        <!-- Card Trajets -->
        <div class="stat-card success">
            <div class="icon success">
                <i class="fas fa-car"></i>
            </div>
            <div class="label">Total Trajets</div>
            <div class="value"><?= number_format($dashboardStats['trips_count']) ?></div>
            <div class="change positive">
                <i class="fas fa-arrow-up"></i> <?= $tripStats['total_seats_offered'] ?> places offertes
            </div>
            <hr>
            <small class="text-muted">
                <i class="fas fa-check text-success"></i> <?= $dashboardStats['trips_active'] ?> actifs |
                <i class="fas fa-flag-checkered"></i> <?= $tripStats['completed'] ?> terminés
            </small>
        </div>

        <!-- Card Réservations -->
        <div class="stat-card warning">
            <div class="icon warning">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="label">Total Réservations</div>
            <div class="value"><?= number_format($dashboardStats['bookings_count']) ?></div>
            <div class="change positive">
                <i class="fas fa-arrow-up"></i> <?= $bookingStats['average_booking'] ?> € moy.
            </div>
            <hr>
            <small class="text-muted">
                <i class="fas fa-check text-success"></i> <?= $dashboardStats['bookings_confirmed'] ?> confirmées |
                <i class="fas fa-times text-danger"></i> <?= $bookingStats['cancelled'] ?> annulées
            </small>
        </div>

        <!-- Card Crédits -->
        <div class="stat-card danger">
            <div class="icon danger">
                <i class="fas fa-coins"></i>
            </div>
            <div class="label">Crédits en Circulation</div>
            <div class="value"><?= number_format($dashboardStats['total_credits']) ?></div>
            <div class="change">
                <i class="fas fa-euro-sign"></i> Revenus : <?= number_format($bookingStats['total_revenue']) ?> crédits
            </div>
            <hr>
            <small class="text-muted">
                <i class="fas fa-percentage"></i> Taux occupation : <?= $avg_occupancy ?>% |
                <i class="fas fa-star"></i> Note moy. : <?= $dashboardStats['average_rating'] ?>/5
            </small>
        </div>

    </div>

    <!-- Répartition Utilisateurs et Trajets -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="admin-table-container">
                <h4><i class="fas fa-users"></i> Répartition utilisateurs</h4>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-car text-success"></i> Avec véhicules</span>
                    <strong><?= $userStats['with_vehicles'] ?> (<?= $userStats['total'] > 0 ? round($userStats['with_vehicles'] / $userStats['total'] * 100, 1) : 0 ?>%)</strong>
                </div>
                <div class="progress mb-3" style="height: 25px;">
                    <?php $vehicles_percent = $userStats['total'] > 0 ? round($userStats['with_vehicles'] / $userStats['total'] * 100, 1) : 0; ?>
                    <div class="progress-bar bg-success" style="width: <?= $vehicles_percent ?>%">
                        <?= $userStats['with_vehicles'] ?>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-route text-primary"></i> Conducteurs actifs</span>
                    <strong><?= $userStats['with_trips'] ?> (<?= $userStats['total'] > 0 ? round($userStats['with_trips'] / $userStats['total'] * 100, 1) : 0 %>%)</strong>
                </div>
                <div class="progress" style="height: 25px;">
                    <?php $trips_percent = $userStats['total'] > 0 ? round($userStats['with_trips'] / $userStats['total'] * 100, 1) : 0; ?>
                    <div class="progress-bar bg-primary" style="width: <?= $trips_percent ?>%">
                        <?= $userStats['with_trips'] ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="admin-table-container">
                <h4><i class="fas fa-chart-pie"></i> Statistiques trajets</h4>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-check-circle text-success"></i> Actifs</span>
                    <strong><?= $tripStats['active'] ?> (<?= $tripStats['total'] > 0 ? round($tripStats['active'] / $tripStats['total'] * 100, 1) : 0 ?>%)</strong>
                </div>
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar bg-success" style="width: <?= $tripStats['total'] > 0 ? round($tripStats['active'] / $tripStats['total'] * 100, 1) : 0 ?>%">
                        <?= $tripStats['active'] ?>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-flag-checkered text-primary"></i> Terminés</span>
                    <strong><?= $tripStats['completed'] ?> (<?= $tripStats['total'] > 0 ? round($tripStats['completed'] / $tripStats['total'] * 100, 1) : 0 %>%)</strong>
                </div>
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar bg-primary" style="width: <?= $tripStats['total'] > 0 ? round($tripStats['completed'] / $tripStats['total'] * 100, 1) : 0 ?>%">
                        <?= $tripStats['completed'] ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dernières Inscriptions -->
    <div class="admin-table-container mb-4">
        <h4><i class="fas fa-user-plus"></i> Dernières inscriptions</h4>
        <hr>
        <table class="table admin-table">
            <thead>
                <tr>
                    <th class="col-id">ID</th>
                    <th>Nom d'utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Date d'inscription</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_users)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Aucune inscription récente</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td class="col-id">#<?= $user['id'] ?></td>
                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span class="role-badge <?= $user['role'] ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="?page=admin-users" class="btn btn-sm btn-primary">
            <i class="fas fa-users"></i> Voir tous les utilisateurs
        </a>
    </div>

    <!-- Dernières Réservations -->
    <div class="admin-table-container">
        <h4><i class="fas fa-ticket-alt"></i> Dernières réservations</h4>
        <hr>
        <table class="table admin-table">
            <thead>
                <tr>
                    <th class="col-id">ID</th>
                    <th>Passager</th>
                    <th>Trajet</th>
                    <th>Date départ</th>
                    <th>Places</th>
                    <th>Prix</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_bookings)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Aucune réservation récente</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_bookings as $booking): ?>
                        <tr>
                            <td class="col-id">#<?= $booking['id'] ?></td>
                            <td><strong><?= htmlspecialchars($booking['passenger_name']) ?></strong></td>
                            <td>
                                <small>
                                    <?= htmlspecialchars($booking['departure_city']) ?>
                                    <i class="fas fa-arrow-right"></i>
                                    <?= htmlspecialchars($booking['arrival_city']) ?>
                                </small>
                            </td>
                            <td><?= date('d/m/Y', strtotime($booking['departure_time'])) ?></td>
                            <td><?= $booking['seats_booked'] ?></td>
                            <td><strong><?= $booking['total_price'] ?> crédits</strong></td>
                            <td>
                                <span class="status-badge <?= $booking['status'] ?>">
                                    <?= ucfirst($booking['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php
/*
===============================================
Bon, j'ai pas mal simplifié cette page !

Avant j'avais 15 requêtes SQL partout dans le code, c'était le bordel.
Maintenant j'utilise mes classes Admin avec getDashboardStats() qui me récupère
tout d'un coup. Beaucoup plus propre.

J'ai aussi séparé les calculs de pourcentages dans des variables PHP séparées
pour éviter les erreurs de syntaxe dans les attributs style.

La logique est maintenant centralisée dans la classe Admin, donc si je veux
réutiliser ces stats ailleurs, c'est facile.

Et si une erreur arrive, j'ai des valeurs par défaut pour que la page plante pas.

Prochaine étape: refactoriser admin-users.php avec User::ban(), User::delete(), etc.
===============================================
*/
?>