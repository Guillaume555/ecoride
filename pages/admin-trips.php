<?php
/*
================================================
FICHIER: pages/admin-trips.php - Gestion trajets administrateur
Description: Interface d'administration des trajets avec actions POO centralisées
================================================
*/

// Inclusion des fonctions de session et classes POO
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/admin_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Trip.php';

// Protection : Seuls les admins peuvent accéder
requireAdmin();

// Configuration de la page
$page_title = "Gestion Trajets - Admin EcoRide";

// ========================================
// Gestion des actions avec classes POO
// ========================================

$success_message = '';
$error_message = '';

if (isset($_GET['action']) && isset($_GET['trip_id'])) {
    $action = $_GET['action'];
    $trip_id = (int)$_GET['trip_id'];

    try {
        $tripObj = new Trip($pdo, $trip_id);

        switch ($action) {
            case 'cancel':
                // Annulation avec remboursement automatique via Trip::cancel()
                $tripObj->cancel();
                $success_message = "Trajet annulé et passagers remboursés automatiquement.";
                break;

            case 'hide':
                $tripObj->hide();
                $success_message = "Trajet masqué avec succès.";
                break;

            case 'show':
                $tripObj->show();
                $success_message = "Trajet réactivé avec succès.";
                break;

            case 'delete':
                // Vérification via les méthodes POO avant suppression
                $bookings = $tripObj->getBookings();
                $confirmed_bookings = 0;

                foreach ($bookings as $booking) {
                    if ($booking['status'] === 'confirmed') {
                        $confirmed_bookings++;
                    }
                }

                if ($confirmed_bookings > 0) {
                    $error_message = "Impossible de supprimer : le trajet a des réservations confirmées. Annulez d'abord le trajet.";
                } else {
                    $tripObj->delete();
                    $success_message = "Trajet supprimé avec succès.";
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = "Erreur lors de l'action : " . $e->getMessage();
    }
}

// ========================================
// Récupération des filtres et données
// ========================================

$search = $_GET['search'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

// Récupération liste trajets avec filtres
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

// Application des filtres
if (!empty($search)) {
    $sql .= " AND (t.departure_city LIKE ? OR t.arrival_city LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_status)) {
    $sql .= " AND t.status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY t.departure_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll();

// ========================================
// Statistiques trajets avec sécurité
// ========================================

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'active'");
    $active_trips = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'completed'");
    $completed_trips = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'cancelled'");
    $cancelled_trips = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $active_trips = $completed_trips = $cancelled_trips = 0;
}
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
                                $status_classes = [
                                    'active' => 'active',
                                    'completed' => 'pending',
                                    'cancelled' => 'inactive',
                                    'hidden' => 'banned'
                                ];
                                $status_class = $status_classes[$trip['status']] ?? 'inactive';
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

<?php
/*
================================================
REFACTORISATION ADMIN-TRIPS.PHP

PROBLÈME RÉSOLU :
- Annulation trajet avec remboursements manuels complexes
- Actions hide/show avec SQL direct
- Suppression avec vérifications multiples non centralisées
- Gestion d'erreurs dispersée

SOLUTION IMPLÉMENTÉE :
- Trip::cancel() gère automatiquement l'annulation et les remboursements
- Trip::hide() et Trip::show() pour la gestion de visibilité
- Trip::delete() avec vérifications intégrées
- Trip::getBookings() pour les statistiques de réservation

ARCHITECTURE :
- Logique métier centralisée dans la classe Trip
- Transactions automatiques pour la cohérence des données
- Gestion d'erreurs unifiée avec exceptions
- Code plus maintenable et réutilisable

SÉCURITÉ :
- Vérification automatique des dépendances
- Remboursements automatiques sécurisés
- Validation des opérations avant exécution
- Messages d'erreur contextuels pour l'admin
================================================
*/
?>