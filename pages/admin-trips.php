<?php

/**
 * ========================================
 * PAGE : pages/admin-trips.php
 * ========================================
 * 
 * DESCRIPTION :
 * Page de gestion des trajets pour l'administrateur
 * Permet de visualiser, modifier, masquer ou supprimer les trajets
 * 
 * ENTRÉES :
 * - Session admin vérifiée (via admin_guard.php)
 * - GET 'action' : cancel, hide, show, delete
 * - GET 'trip_id' : ID du trajet concerné
 * - GET 'search' : Recherche par ville
 * - GET 'filter_status' : Filtre par statut (active/completed/cancelled)
 * 
 * TRAITEMENTS :
 * 1. Récupération liste trajets avec filtres
 * 2. Gestion actions (annuler, masquer, supprimer)
 * 3. Remboursement automatique passagers lors annulation
 * 4. Calcul statistiques trajets
 * 5. Recherche et filtres dynamiques
 * 
 * SORTIES :
 * - Tableau liste trajets avec actions
 * - Messages succès/erreur après actions
 * - Statistiques et filtres
 * 
 * SÉCURITÉ :
 * - Protection admin_guard (rôle 'admin' requis)
 * - Validation ID trajet
 * - Transactions SQL pour remboursements
 * - Vérifications cohérence données
 * ========================================
 */

// Définir le titre de la page
$page_title = "Gestion Trajets - Admin EcoRide";

// Protection : Seuls les admins peuvent accéder
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/admin_guard.php';
requireAdmin();

// Connexion base de données
require_once __DIR__ . '/../config/database.php';

// ========================================
// GESTION DES ACTIONS
// ========================================

$success_message = '';
$error_message = '';

if (isset($_GET['action']) && isset($_GET['trip_id'])) {
    $action = $_GET['action'];
    $trip_id = (int)$_GET['trip_id'];

    try {
        switch ($action) {
            case 'cancel':
                // Annuler le trajet et rembourser tous les passagers
                $pdo->beginTransaction();

                // Récupérer toutes les réservations confirmées
                $stmt = $pdo->prepare("
                    SELECT passenger_id, total_price 
                    FROM bookings 
                    WHERE trip_id = ? AND status = 'confirmed'
                ");
                $stmt->execute([$trip_id]);
                $bookings = $stmt->fetchAll();

                // Rembourser chaque passager
                foreach ($bookings as $booking) {
                    $stmt = $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
                    $stmt->execute([$booking['total_price'], $booking['passenger_id']]);

                    // Marquer la réservation comme annulée
                    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE trip_id = ? AND passenger_id = ?");
                    $stmt->execute([$trip_id, $booking['passenger_id']]);
                }

                // Annuler le trajet
                $stmt = $pdo->prepare("UPDATE trips SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$trip_id]);

                $pdo->commit();
                $success_message = "Trajet annulé et " . count($bookings) . " passager(s) remboursé(s).";
                break;

            case 'hide':
                $stmt = $pdo->prepare("UPDATE trips SET status = 'hidden' WHERE id = ?");
                $stmt->execute([$trip_id]);
                $success_message = "Trajet masqué avec succès.";
                break;

            case 'show':
                $stmt = $pdo->prepare("UPDATE trips SET status = 'active' WHERE id = ?");
                $stmt->execute([$trip_id]);
                $success_message = "Trajet réactivé avec succès.";
                break;

            case 'delete':
                // Vérifier qu'il n'y a pas de réservations confirmées
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE trip_id = ? AND status = 'confirmed'");
                $stmt->execute([$trip_id]);
                $confirmed_bookings = $stmt->fetchColumn();

                if ($confirmed_bookings > 0) {
                    $error_message = "Impossible de supprimer : le trajet a des réservations confirmées. Annulez d'abord le trajet.";
                } else {
                    // Supprimer les réservations puis le trajet
                    $pdo->beginTransaction();
                    $pdo->prepare("DELETE FROM bookings WHERE trip_id = ?")->execute([$trip_id]);
                    $pdo->prepare("DELETE FROM trips WHERE id = ?")->execute([$trip_id]);
                    $pdo->commit();
                    $success_message = "Trajet supprimé avec succès.";
                }
                break;
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = "Erreur lors de l'action : " . $e->getMessage();
    }
}

// ========================================
// RÉCUPÉRATION FILTRES
// ========================================

$search = $_GET['search'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

// ========================================
// RÉCUPÉRATION LISTE TRAJETS
// ========================================

$sql = "SELECT t.*, 
               u.username as driver_name,
               u.email as driver_email,
               v.brand,
               v.model,
               v.license_plate,
               (SELECT COUNT(*) FROM bookings WHERE trip_id = t.id AND status = 'confirmed') as bookings_count
        FROM trips t
        JOIN users u ON t.driver_id = u.id
        JOIN vehicles v ON t.vehicle_id = v.id
        WHERE 1=1";

$params = [];

// Filtre recherche
if (!empty($search)) {
    $sql .= " AND (t.departure_city LIKE ? OR t.arrival_city LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Filtre statut
if (!empty($filter_status)) {
    $sql .= " AND t.status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY t.departure_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll();

// ========================================
// STATISTIQUES TRAJETS
// ========================================

$stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'active'");
$active_trips = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'completed'");
$completed_trips = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'cancelled'");
$cancelled_trips = $stmt->fetchColumn();

?>

<!-- Contenu de la page -->
<div class="admin-container">

    <!-- En-tête -->
    <div class="admin-header">
        <h1><i class="fas fa-car"></i> Gestion des trajets</h1>
        <p class="subtitle">Administration des trajets de covoiturage</p>
    </div>

    <!-- Messages -->
    <?php if ($success_message): ?>
        <div class="admin-alert success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="admin-alert danger">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Navigation Admin -->
    <div class="mb-4">
        <a href="?page=admin-dashboard" class="btn btn-outline-primary me-2">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="?page=admin-users" class="btn btn-outline-primary me-2">
            <i class="fas fa-users"></i> Utilisateurs
        </a>
        <a href="?page=admin-trips" class="btn btn-primary me-2">
            <i class="fas fa-car"></i> Trajets
        </a>
        <a href="?page=admin-reviews" class="btn btn-outline-primary me-2">
            <i class="fas fa-star"></i> Avis
        </a>
    </div>

    <!-- Statistiques rapides -->
    <div class="stats-grid mb-4">
        <div class="stat-card success">
            <div class="icon success"><i class="fas fa-check-circle"></i></div>
            <div class="label">Trajets Actifs</div>
            <div class="value"><?= $active_trips ?></div>
        </div>

        <div class="stat-card primary">
            <div class="icon primary"><i class="fas fa-flag-checkered"></i></div>
            <div class="label">Trajets Terminés</div>
            <div class="value"><?= $completed_trips ?></div>
        </div>

        <div class="stat-card danger">
            <div class="icon danger"><i class="fas fa-times-circle"></i></div>
            <div class="label">Trajets Annulés</div>
            <div class="value"><?= $cancelled_trips ?></div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="admin-filters">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="admin-trips">

            <div class="col-md-6">
                <label class="form-label">Rechercher</label>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" name="search" placeholder="Ville ou conducteur..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Statut</label>
                <select class="form-select" name="filter_status">
                    <option value="">Tous les statuts</option>
                    <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Actif</option>
                    <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Terminé</option>
                    <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Annulé</option>
                    <option value="hidden" <?= $filter_status === 'hidden' ? 'selected' : '' ?>>Masqué</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Tableau des trajets -->
    <div class="admin-table-container">
        <h4><i class="fas fa-table"></i> Liste des trajets (<?= count($trips) ?>)</h4>
        <hr>

        <?php if (empty($trips)): ?>
            <p class="text-center text-muted py-4">Aucun trajet trouvé.</p>
        <?php else: ?>
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th>Trajet</th>
                        <th>Conducteur</th>
                        <th>Véhicule</th>
                        <th>Date départ</th>
                        <th>Places</th>
                        <th>Prix</th>
                        <th>Réservations</th>
                        <th>Statut</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td class="col-id">#<?= $trip['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($trip['departure_city']) ?></strong>
                                <i class="fas fa-arrow-right text-muted mx-1"></i>
                                <strong><?= htmlspecialchars($trip['arrival_city']) ?></strong>
                            </td>
                            <td>
                                <?= htmlspecialchars($trip['driver_name']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($trip['driver_email']) ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($trip['brand']) ?> <?= htmlspecialchars($trip['model']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($trip['license_plate']) ?></small>
                            </td>
                            <td>
                                <?= date('d/m/Y', strtotime($trip['departure_time'])) ?><br>
                                <small class="text-muted"><?= date('H:i', strtotime($trip['departure_time'])) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $trip['available_seats'] ?> disponibles</span>
                            </td>
                            <td>
                                <strong><?= $trip['price_per_seat'] ?> €</strong>
                            </td>
                            <td>
                                <?php if ($trip['bookings_count'] > 0): ?>
                                    <span class="badge bg-success"><?= $trip['bookings_count'] ?> réservation(s)</span>
                                <?php else: ?>
                                    <span class="text-muted">Aucune</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_class = [
                                    'active' => 'active',
                                    'completed' => 'pending',
                                    'cancelled' => 'inactive',
                                    'hidden' => 'banned'
                                ][$trip['status']] ?? 'inactive';
                                ?>
                                <span class="status-badge <?= $status_class ?>">
                                    <?= ucfirst($trip['status']) ?>
                                </span>
                            </td>
                            <td class="col-actions">
                                <?php if ($trip['status'] === 'active'): ?>
                                    <a href="?page=admin-trips&action=cancel&trip_id=<?= $trip['id'] ?>"
                                        class="btn btn-action btn-ban"
                                        onclick="return confirm('Annuler ce trajet et rembourser les passagers ?')">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                    <a href="?page=admin-trips&action=hide&trip_id=<?= $trip['id'] ?>"
                                        class="btn btn-action btn-edit"
                                        onclick="return confirm('Masquer ce trajet ?')">
                                        <i class="fas fa-eye-slash"></i>
                                    </a>
                                <?php elseif ($trip['status'] === 'hidden'): ?>
                                    <a href="?page=admin-trips&action=show&trip_id=<?= $trip['id'] ?>"
                                        class="btn btn-action btn-validate"
                                        onclick="return confirm('Réactiver ce trajet ?')">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php endif; ?>

                                <a href="?page=admin-trips&action=delete&trip_id=<?= $trip['id'] ?>"
                                    class="btn btn-action btn-delete"
                                    onclick="return confirm('⚠️ SUPPRIMER définitivement ce trajet ? Cette action est IRRÉVERSIBLE !')">
                                    <i class="fas fa-trash"></i>
                                </a>

                                <a href="?page=detail&id=<?= $trip['id'] ?>"
                                    class="btn btn-action btn-view"
                                    target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>