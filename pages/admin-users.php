<?php

/**
 * ========================================
 * PAGE : pages/admin-users.php
 * ========================================
 * 
 * DESCRIPTION :
 * Page de gestion des utilisateurs pour l'administrateur
 * Permet de visualiser, modifier, bannir/débannir les utilisateurs
 * 
 * ENTRÉES :
 * - Session admin vérifiée (via admin_guard.php)
 * - GET 'action' : bannir, debannir, ajuster_credits
 * - GET 'user_id' : ID de l'utilisateur concerné
 * - GET 'search' : Recherche par nom/email
 * - GET 'filter_role' : Filtre par rôle (admin/driver/passenger)
 * - GET 'filter_status' : Filtre par statut (actif/inactif)
 * - POST 'credits' : Nouveau montant de crédits
 * 
 * TRAITEMENTS :
 * 1. Récupération liste utilisateurs avec filtres
 * 2. Gestion actions (bannir, débannir, ajuster crédits)
 * 3. Calcul statistiques par utilisateur (trajets, réservations)
 * 4. Recherche et filtres dynamiques
 * 5. Pagination si nombreux utilisateurs
 * 
 * SORTIES :
 * - Tableau liste utilisateurs avec actions
 * - Messages succès/erreur après actions
 * - Statistiques et filtres
 * 
 * SÉCURITÉ :
 * - Protection admin_guard (rôle 'admin' requis)
 * - Validation ID utilisateur
 * - Protection contre auto-bannissement
 * - Transactions SQL pour cohérence données
 * ========================================
 */

// Définir le titre de la page
$page_title = "Gestion Utilisateurs - Admin EcoRide";

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

if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $action = $_GET['action'];
    $user_id = (int)$_GET['user_id'];

    // Protection : empêcher l'admin de se bannir lui-même
    if ($user_id === $_SESSION['user_id'] && $action === 'bannir') {
        $error_message = "Vous ne pouvez pas vous bannir vous-même !";
    } else {
        try {
            switch ($action) {
                case 'bannir':
                    $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $success_message = "Utilisateur banni avec succès.";
                    break;

                case 'debannir':
                    $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $success_message = "Utilisateur débanni avec succès.";
                    break;

                case 'delete':
                    // Vérifier que l'utilisateur n'a pas de trajets actifs
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE driver_id = ? AND status = 'active'");
                    $stmt->execute([$user_id]);
                    $active_trips = $stmt->fetchColumn();

                    if ($active_trips > 0) {
                        $error_message = "Impossible de supprimer : l'utilisateur a des trajets actifs.";
                    } else {
                        // Supprimer les réservations, avis, puis l'utilisateur
                        $pdo->beginTransaction();
                        $pdo->prepare("DELETE FROM bookings WHERE passenger_id = ?")->execute([$user_id]);
                        $pdo->prepare("DELETE FROM reviews WHERE reviewer_id = ? OR reviewed_id = ?")->execute([$user_id, $user_id]);
                        $pdo->prepare("DELETE FROM trips WHERE driver_id = ?")->execute([$user_id]);
                        $pdo->prepare("DELETE FROM vehicles WHERE user_id = ?")->execute([$user_id]);
                        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                        $pdo->commit();
                        $success_message = "Utilisateur supprimé avec succès.";
                    }
                    break;
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'action : " . $e->getMessage();
        }
    }
}

// Ajustement des crédits
if (isset($_POST['ajuster_credits']) && isset($_POST['user_id']) && isset($_POST['credits'])) {
    $user_id = (int)$_POST['user_id'];
    $new_credits = (int)$_POST['credits'];

    if ($new_credits < 0) {
        $error_message = "Le montant de crédits ne peut pas être négatif.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET credits = ? WHERE id = ?");
            $stmt->execute([$new_credits, $user_id]);
            $success_message = "Crédits ajustés avec succès.";
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'ajustement : " . $e->getMessage();
        }
    }
}

// ========================================
// RÉCUPÉRATION FILTRES
// ========================================

$search = $_GET['search'] ?? '';
$filter_role = $_GET['filter_role'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

// ========================================
// RÉCUPÉRATION LISTE UTILISATEURS
// ========================================

$sql = "SELECT id, username, email, phone, credits, role, created_at, is_active FROM users WHERE 1=1";
$params = [];

// Filtre recherche
if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Filtre rôle
if (!empty($filter_role)) {
    $sql .= " AND role = ?";
    $params[] = $filter_role;
}

// Filtre statut
if ($filter_status === 'active') {
    $sql .= " AND is_active = 1";
} elseif ($filter_status === 'inactive') {
    $sql .= " AND is_active = 0";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// ========================================
// STATISTIQUES PAR UTILISATEUR
// ========================================

function getUserStats($pdo, $user_id)
{
    // Nombre de trajets proposés
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE driver_id = ?");
    $stmt->execute([$user_id]);
    $trips_count = $stmt->fetchColumn();

    // Nombre de réservations
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE passenger_id = ?");
    $stmt->execute([$user_id]);
    $bookings_count = $stmt->fetchColumn();

    return [
        'trips' => $trips_count,
        'bookings' => $bookings_count
    ];
}

?>

<!-- Contenu de la page -->
<div class="admin-container">

    <!-- En-tête -->
    <div class="admin-header">
        <h1><i class="fas fa-users"></i> Gestion des utilisateurs</h1>
        <p class="subtitle">Administration des comptes utilisateurs</p>
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
        <a href="?page=admin-users" class="btn btn-primary me-2">
            <i class="fas fa-users"></i> Utilisateurs
        </a>
        <a href="?page=admin-trips" class="btn btn-outline-primary me-2">
            <i class="fas fa-car"></i> Trajets
        </a>
        <a href="?page=admin-reviews" class="btn btn-outline-primary me-2">
            <i class="fas fa-star"></i> Avis
        </a>
    </div>

    <!-- Statistiques rapides -->
    <div class="stats-grid mb-4">
        <div class="stat-card primary">
            <div class="icon primary"><i class="fas fa-users"></i></div>
            <div class="label">Total Utilisateurs</div>
            <div class="value"><?= count($users) ?></div>
        </div>

        <div class="stat-card success">
            <div class="icon success"><i class="fas fa-user-check"></i></div>
            <div class="label">Utilisateurs Actifs</div>
            <div class="value"><?= count(array_filter($users, fn($u) => $u['is_active'] == 1)) ?></div>
        </div>

        <div class="stat-card danger">
            <div class="icon danger"><i class="fas fa-user-slash"></i></div>
            <div class="label">Utilisateurs Bannis</div>
            <div class="value"><?= count(array_filter($users, fn($u) => $u['is_active'] == 0)) ?></div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="admin-filters">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="admin-users">

            <div class="col-md-4">
                <label class="form-label">Rechercher</label>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" name="search" placeholder="Nom ou email..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Rôle</label>
                <select class="form-select" name="filter_role">
                    <option value="">Tous les rôles</option>
                    <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="driver" <?= $filter_role === 'driver' ? 'selected' : '' ?>>Conducteur</option>
                    <option value="passenger" <?= $filter_role === 'passenger' ? 'selected' : '' ?>>Passager</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select class="form-select" name="filter_status">
                    <option value="">Tous les statuts</option>
                    <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Actif</option>
                    <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Banni</option>
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

    <!-- Tableau des utilisateurs -->
    <div class="admin-table-container">
        <h4><i class="fas fa-table"></i> Liste des utilisateurs</h4>
        <hr>

        <?php if (empty($users)): ?>
            <p class="text-center text-muted py-4">Aucun utilisateur trouvé.</p>
        <?php else: ?>
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th>Nom d'utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Crédits</th>
                        <th>Statut</th>
                        <th>Statistiques</th>
                        <th>Inscription</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php $stats = getUserStats($pdo, $user['id']); ?>
                        <tr>
                            <td class="col-id">#<?= $user['id'] ?></td>
                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span class="role-badge <?= $user['role'] ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Ajuster les crédits ?')">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="number" name="credits" value="<?= $user['credits'] ?>" style="width: 80px;" class="form-control form-control-sm d-inline">
                                    <button type="submit" name="ajuster_credits" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="status-badge active">Actif</span>
                                <?php else: ?>
                                    <span class="status-badge inactive">Banni</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small>
                                    <i class="fas fa-car"></i> <?= $stats['trips'] ?> trajets<br>
                                    <i class="fas fa-ticket-alt"></i> <?= $stats['bookings'] ?> réservations
                                </small>
                            </td>
                            <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                            <td class="col-actions">
                                <?php if ($user['is_active']): ?>
                                    <a href="?page=admin-users&action=bannir&user_id=<?= $user['id'] ?>"
                                        class="btn btn-action btn-ban"
                                        onclick="return confirm('Bannir cet utilisateur ?')">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="?page=admin-users&action=debannir&user_id=<?= $user['id'] ?>"
                                        class="btn btn-action btn-validate"
                                        onclick="return confirm('Débannir cet utilisateur ?')">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>

                                <a href="?page=admin-users&action=delete&user_id=<?= $user['id'] ?>"
                                    class="btn btn-action btn-delete"
                                    onclick="return confirm('⚠️ SUPPRIMER définitivement cet utilisateur et toutes ses données ? Cette action est IRRÉVERSIBLE !')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>