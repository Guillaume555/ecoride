<?php

/**
 * ========================================
 * PAGE : pages/admin/dashboard.php
 * ========================================
 * 
 * DESCRIPTION :
 * Tableau de bord principal de l'espace administrateur
 * Affiche les statistiques globales de la plateforme EcoRide
 * 
 * ENTRÉES :
 * - Session admin vérifiée (via admin_guard.php)
 * - Aucun paramètre GET/POST requis
 * 
 * TRAITEMENTS :
 * 1. Récupération statistiques utilisateurs (total, actifs, inactifs, par rôle)
 * 2. Récupération statistiques trajets (total, actifs, terminés, annulés)
 * 3. Récupération statistiques réservations (total, confirmées, annulées)
 * 4. Calcul total crédits en circulation
 * 5. Récupération dernières inscriptions (7 derniers jours)
 * 6. Récupération dernières réservations
 * 7. Calcul taux d'occupation moyen des trajets
 * 
 * SORTIES :
 * - Affichage HTML des cartes statistiques
 * - Tableaux des dernières activités
 * - Navigation vers autres sections admin
 * 
 * SÉCURITÉ :
 * - Protection admin_guard (rôle 'admin' requis)
 * - Lecture seule (aucune modification de données)
 * - Échappement HTML pour affichage sécurisé
 * ========================================
 */

// Définir le titre de la page
$page_title = "Dashboard Admin - EcoRide";

// Protection : Seuls les admins peuvent accéder
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/admin_guard.php';
requireAdmin();

// Connexion base de données
require_once __DIR__ . '/../config/database.php';

// ========================================
// RÉCUPÉRATION DES STATISTIQUES
// ========================================

try {
    // 1. STATISTIQUES UTILISATEURS
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_users = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
    $active_users = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_active = 0");
    $inactive_users = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'driver'");
    $drivers = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'passenger'");
    $passengers = $stmt->fetchColumn();

    // Nouvelles inscriptions (7 derniers jours)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $new_users_week = $stmt->fetchColumn();

    // 2. STATISTIQUES TRAJETS
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM trips");
    $total_trips = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM trips WHERE status = 'active'");
    $active_trips = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM trips WHERE status = 'completed'");
    $completed_trips = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM trips WHERE status = 'cancelled'");
    $cancelled_trips = $stmt->fetchColumn();

    // Trajets créés cette semaine
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM trips WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $new_trips_week = $stmt->fetchColumn();

    // 3. STATISTIQUES RÉSERVATIONS
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $total_bookings = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'confirmed'");
    $confirmed_bookings = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'cancelled'");
    $cancelled_bookings = $stmt->fetchColumn();

    // Réservations cette semaine
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $new_bookings_week = $stmt->fetchColumn();

    // 4. STATISTIQUES FINANCIÈRES
    $stmt = $pdo->query("SELECT SUM(credits) as total FROM users");
    $total_credits = $stmt->fetchColumn() ?? 0;

    $stmt = $pdo->query("SELECT SUM(total_price) as total FROM bookings WHERE status = 'confirmed'");
    $total_revenue = $stmt->fetchColumn() ?? 0;

    // 5. DERNIÈRES INSCRIPTIONS
    $stmt = $pdo->query("
        SELECT id, username, email, role, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_users = $stmt->fetchAll();

    // 6. DERNIÈRES RÉSERVATIONS
    $stmt = $pdo->query("
        SELECT b.*, 
               u.username as passenger_name,
               t.departure_city, 
               t.arrival_city,
               t.departure_time
        FROM bookings b
        JOIN users u ON b.passenger_id = u.id
        JOIN trips t ON b.trip_id = t.id
        ORDER BY b.booking_date DESC
        LIMIT 5
    ");
    $recent_bookings = $stmt->fetchAll();

    // 7. TAUX D'OCCUPATION MOYEN
    $stmt = $pdo->query("
        SELECT 
            AVG((v.seats - t.available_seats) / v.seats * 100) as avg_occupancy
        FROM trips t
        JOIN vehicles v ON t.vehicle_id = v.id
        WHERE t.status = 'completed'
    ");
    $avg_occupancy = round($stmt->fetchColumn() ?? 0, 1);
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des statistiques : " . $e->getMessage();
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

    <!-- Grille de statistiques -->
    <div class="stats-grid">

        <!-- Card Utilisateurs -->
        <div class="stat-card primary">
            <div class="icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="label">Total Utilisateurs</div>
            <div class="value"><?= number_format($total_users) ?></div>
            <div class="change positive">
                <i class="fas fa-arrow-up"></i> +<?= $new_users_week ?> cette semaine
            </div>
            <hr>
            <small class="text-muted">
                <i class="fas fa-check-circle text-success"></i> <?= $active_users ?> actifs |
                <i class="fas fa-times-circle text-danger"></i> <?= $inactive_users ?> inactifs
            </small>
        </div>

        <!-- Card Trajets -->
        <div class="stat-card success">
            <div class="icon success">
                <i class="fas fa-car"></i>
            </div>
            <div class="label">Total Trajets</div>
            <div class="value"><?= number_format($total_trips) ?></div>
            <div class="change positive">
                <i class="fas fa-arrow-up"></i> +<?= $new_trips_week ?> cette semaine
            </div>
            <hr>
            <small class="text-muted">
                <i class="fas fa-check text-success"></i> <?= $active_trips ?> actifs |
                <i class="fas fa-flag-checkered"></i> <?= $completed_trips ?> terminés
            </small>
        </div>

        <!-- Card Réservations -->
        <div class="stat-card warning">
            <div class="icon warning">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="label">Total Réservations</div>
            <div class="value"><?= number_format($total_bookings) ?></div>
            <div class="change positive">
                <i class="fas fa-arrow-up"></i> +<?= $new_bookings_week ?> cette semaine
            </div>
            <hr>
            <small class="text-muted">
                <i class="fas fa-check text-success"></i> <?= $confirmed_bookings ?> confirmées |
                <i class="fas fa-times text-danger"></i> <?= $cancelled_bookings ?> annulées
            </small>
        </div>

        <!-- Card Crédits -->
        <div class="stat-card danger">
            <div class="icon danger">
                <i class="fas fa-coins"></i>
            </div>
            <div class="label">Crédits en Circulation</div>
            <div class="value"><?= number_format($total_credits) ?></div>
            <div class="change">
                <i class="fas fa-euro-sign"></i> Revenus : <?= number_format($total_revenue) ?> crédits
            </div>
            <hr>
            <small class="text-muted">
                <i class="fas fa-percentage"></i> Taux occupation : <?= $avg_occupancy ?>%
            </small>
        </div>

    </div>

    <!-- Répartition Utilisateurs -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="admin-table-container">
                <h4><i class="fas fa-users"></i> Répartition par rôle</h4>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-steering-wheel text-success"></i> Conducteurs</span>
                    <strong><?= $drivers ?> (<?= $total_users > 0 ? round($drivers / $total_users * 100, 1) : 0 ?>%)</strong>
                </div>
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar bg-success" style="width: <?= $total_users > 0 ? round($drivers / $total_users * 100, 1) : 0 ?>%">
                        <?= $drivers ?>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-user text-primary"></i> Passagers</span>
                    <strong><?= $passengers ?> (<?= $total_users > 0 ? round($passengers / $total_users * 100, 1) : 0 ?>%)</strong>
                </div>
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar bg-primary" style="width: <?= $total_users > 0 ? round($passengers / $total_users * 100, 1) : 0 ?>%">
                        <?= $passengers ?>
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
                    <strong><?= $active_trips ?> (<?= $total_trips > 0 ? round($active_trips / $total_trips * 100, 1) : 0 ?>%)</strong>
                </div>
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar bg-success" style="width: <?= $total_trips > 0 ? round($active_trips / $total_trips * 100, 1) : 0 ?>%">
                        <?= $active_trips ?>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-flag-checkered text-primary"></i> Terminés</span>
                    <strong><?= $completed_trips ?> (<?= $total_trips > 0 ? round($completed_trips / $total_trips * 100, 1) : 0 ?>%)</strong>
                </div>
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar bg-primary" style="width: <?= $total_trips > 0 ? round($completed_trips / $total_trips * 100, 1) : 0 ?>%">
                        <?= $completed_trips ?>
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