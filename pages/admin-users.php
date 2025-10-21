<?php
/*
================================================
FICHIER: pages/admin-users.php - Gestion utilisateurs administrateur
Description: Interface d'administration des comptes utilisateurs avec actions POO
================================================
*/

// Inclusion des fonctions de session et classes POO
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/admin_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';

// Protection : Seuls les admins peuvent accéder
requireAdmin();

// Configuration de la page
$page_title = "Gestion Utilisateurs - Admin EcoRide";

// ========================================
// Gestion des actions avec classes POO
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
            $userObj = new User($pdo, $user_id);

            switch ($action) {
                case 'bannir':
                    $userObj->ban();
                    $success_message = "Utilisateur banni avec succès.";
                    break;

                case 'debannir':
                    $userObj->unban();
                    $success_message = "Utilisateur débanni avec succès.";
                    break;

                case 'delete':
                    // Vérifier que l'utilisateur n'a pas de trajets actifs
                    $userTrips = $userObj->getTripsAsDriver();
                    $activeTrips = 0;

                    foreach ($userTrips as $trip) {
                        if ($trip['status'] === 'active') {
                            $activeTrips++;
                        }
                    }

                    if ($activeTrips > 0) {
                        $error_message = "Impossible de supprimer : l'utilisateur a des trajets actifs.";
                    } else {
                        $userObj->delete();
                        $success_message = "Utilisateur supprimé avec succès.";
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de l'action : " . $e->getMessage();
        }
    }
}

// Ajustement des crédits avec POO
if (isset($_POST['ajuster_credits']) && isset($_POST['user_id']) && isset($_POST['credits'])) {
    $user_id = (int)$_POST['user_id'];
    $new_credits = (int)$_POST['credits'];

    if ($new_credits < 0) {
        $error_message = "Le montant de crédits ne peut pas être négatif.";
    } else {
        try {
            $userObj = new User($pdo, $user_id);
            $userObj->updateCredits($new_credits);
            $success_message = "Crédits ajustés avec succès.";
        } catch (Exception $e) {
            $error_message = "Erreur lors de l'ajustement : " . $e->getMessage();
        }
    }
}

// ========================================
// Récupération des filtres et données
// ========================================

$search = $_GET['search'] ?? '';
$filter_role = $_GET['filter_role'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

// Récupération liste utilisateurs avec filtres
$sql = "SELECT id, username, email, phone, credits, role, created_at, is_active FROM users WHERE 1=1";
$params = [];

// Application des filtres
if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_role)) {
    $sql .= " AND role = ?";
    $params[] = $filter_role;
}

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
// Fonction pour récupérer les statistiques utilisateur avec POO
// ========================================

function getUserStatsPOO($pdo, $user_id)
{
    try {
        $userObj = new User($pdo, $user_id);
        $trips = $userObj->getTripsAsDriver();
        $bookings = $userObj->getTripsAsPassenger();

        return [
            'trips' => count($trips),
            'bookings' => count($bookings)
        ];
    } catch (Exception $e) {
        return ['trips' => 0, 'bookings' => 0];
    }
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
    <?php
    $total_users = count($users);
    $active_users = count(array_filter($users, fn($u) => $u['is_active'] == 1));
    $banned_users = $total_users - $active_users;
    ?>

    <div class="stats-grid mb-4">
        <div class="stat-card primary">
            <div class="icon primary"><i class="fas fa-users"></i></div>
            <div class="label">Total Utilisateurs</div>
            <div class="value"><?= $total_users ?></div>
        </div>

        <div class="stat-card success">
            <div class="icon success"><i class="fas fa-user-check"></i></div>
            <div class="label">Utilisateurs Actifs</div>
            <div class="value"><?= $active_users ?></div>
        </div>

        <div class="stat-card danger">
            <div class="icon danger"><i class="fas fa-user-slash"></i></div>
            <div class="label">Utilisateurs Bannis</div>
            <div class="value"><?= $banned_users ?></div>
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
                        <?php $stats = getUserStatsPOO($pdo, $user['id']); ?>
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

<?php
/*
================================================
REFACTORISATION ADMIN-USERS.PHP

PROBLÈME RÉSOLU :
- Actions utilisateur (ban, unban, delete) avec SQL direct
- Ajustement crédits avec requêtes manuelles
- Fonction getUserStats() procédurale
- Gestion d'erreurs dispersée

SOLUTION IMPLÉMENTÉE :
- User::ban() et User::unban() pour les actions de modération
- User::updateCredits() pour l'ajustement sécurisé des crédits
- User::delete() avec gestion automatique des dépendances
- User::getTripsAsDriver() et User::getTripsAsPassenger() pour les stats

ARCHITECTURE :
- Logique métier centralisée dans la classe User
- Gestion d'erreurs cohérente avec exceptions
- Validation automatique des opérations
- Code réutilisable et maintenable

SÉCURITÉ :
- Protection contre l'auto-bannissement
- Vérification des dépendances avant suppression
- Validation des montants de crédits
- Transactions automatiques pour la cohérence
================================================
*/
?>