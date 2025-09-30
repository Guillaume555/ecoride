<?php

/**
 * ========================================
 * PAGE : pages/admin-reviews.php
 * ========================================
 * 
 * DESCRIPTION :
 * Page de modération des avis pour l'administrateur
 * Permet de valider, refuser ou supprimer les avis utilisateurs
 * 
 * ENTRÉES :
 * - Session admin vérifiée (via admin_guard.php)
 * - GET 'action' : validate, reject, delete
 * - GET 'review_id' : ID de l'avis concerné
 * - GET 'filter_status' : Filtre par statut de validation
 * 
 * TRAITEMENTS :
 * 1. Récupération liste avis avec filtres
 * 2. Gestion actions (valider, refuser, supprimer)
 * 3. Calcul statistiques avis
 * 4. Filtres dynamiques par statut
 * 
 * SORTIES :
 * - Tableau liste avis avec actions
 * - Messages succès/erreur après actions
 * - Statistiques modération
 * 
 * SÉCURITÉ :
 * - Protection admin_guard (rôle 'admin' requis)
 * - Validation ID avis
 * - Vérifications cohérence données
 * ========================================
 */

// Définir le titre de la page
$page_title = "Modération Avis - Admin EcoRide";

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

if (isset($_GET['action']) && isset($_GET['review_id'])) {
    $action = $_GET['action'];
    $review_id = (int)$_GET['review_id'];

    try {
        switch ($action) {
            case 'validate':
                $stmt = $pdo->prepare("UPDATE reviews SET is_validated = 1 WHERE id = ?");
                $stmt->execute([$review_id]);
                $success_message = "Avis validé avec succès.";
                break;

            case 'reject':
                $stmt = $pdo->prepare("UPDATE reviews SET is_validated = 0 WHERE id = ?");
                $stmt->execute([$review_id]);
                $success_message = "Avis refusé.";
                break;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
                $stmt->execute([$review_id]);
                $success_message = "Avis supprimé définitivement.";
                break;
        }
    } catch (PDOException $e) {
        $error_message = "Erreur lors de l'action : " . $e->getMessage();
    }
}

// ========================================
// RÉCUPÉRATION FILTRES
// ========================================

$filter_status = $_GET['filter_status'] ?? '';

// ========================================
// RÉCUPÉRATION LISTE AVIS
// ========================================

$sql = "SELECT r.*, 
               reviewer.username as reviewer_name,
               reviewer.email as reviewer_email,
               reviewed.username as reviewed_name,
               t.departure_city,
               t.arrival_city,
               t.departure_time
        FROM reviews r
        JOIN users reviewer ON r.reviewer_id = reviewer.id
        JOIN users reviewed ON r.reviewed_id = reviewed.id
        JOIN trips t ON r.trip_id = t.id
        WHERE 1=1";

$params = [];

// Filtre statut validation
if ($filter_status === 'validated') {
    $sql .= " AND r.is_validated = 1";
} elseif ($filter_status === 'pending') {
    $sql .= " AND r.is_validated = 0";
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// ========================================
// STATISTIQUES AVIS
// ========================================

$stmt = $pdo->query("SELECT COUNT(*) FROM reviews WHERE is_validated = 1");
$validated_reviews = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM reviews WHERE is_validated = 0");
$pending_reviews = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT AVG(rating) FROM reviews WHERE is_validated = 1");
$average_rating = round($stmt->fetchColumn() ?? 0, 1);

?>

<!-- Contenu de la page -->
<div class="admin-container">

    <!-- En-tête -->
    <div class="admin-header">
        <h1><i class="fas fa-star"></i> Modération des avis</h1>
        <p class="subtitle">Validation et gestion des avis utilisateurs</p>
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
        <a href="?page=admin-trips" class="btn btn-outline-primary me-2">
            <i class="fas fa-car"></i> Trajets
        </a>
        <a href="?page=admin-reviews" class="btn btn-primary me-2">
            <i class="fas fa-star"></i> Avis
        </a>
    </div>

    <!-- Statistiques rapides -->
    <div class="stats-grid mb-4">
        <div class="stat-card success">
            <div class="icon success"><i class="fas fa-check-circle"></i></div>
            <div class="label">Avis Validés</div>
            <div class="value"><?= $validated_reviews ?></div>
        </div>

        <div class="stat-card warning">
            <div class="icon warning"><i class="fas fa-clock"></i></div>
            <div class="label">En Attente</div>
            <div class="value"><?= $pending_reviews ?></div>
        </div>

        <div class="stat-card primary">
            <div class="icon primary"><i class="fas fa-star"></i></div>
            <div class="label">Note Moyenne</div>
            <div class="value"><?= $average_rating ?> / 5</div>
        </div>
    </div>

    <!-- Alerte si avis en attente -->
    <?php if ($pending_reviews > 0): ?>
        <div class="admin-alert warning">
            <i class="fas fa-exclamation-circle"></i>
            <strong><?= $pending_reviews ?> avis en attente de modération</strong>
            <a href="?page=admin-reviews&filter_status=pending" class="btn btn-sm btn-warning ms-3">
                <i class="fas fa-eye"></i> Voir les avis en attente
            </a>
        </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="admin-filters">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="admin-reviews">

            <div class="col-md-8">
                <label class="form-label">Statut de validation</label>
                <select class="form-select" name="filter_status">
                    <option value="">Tous les avis</option>
                    <option value="validated" <?= $filter_status === 'validated' ? 'selected' : '' ?>>Validés</option>
                    <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>En attente</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Tableau des avis -->
    <div class="admin-table-container">
        <h4><i class="fas fa-table"></i> Liste des avis (<?= count($reviews) ?>)</h4>
        <hr>

        <?php if (empty($reviews)): ?>
            <p class="text-center text-muted py-4">Aucun avis trouvé.</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($reviews as $review): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($review['reviewer_name']) ?></strong>
                                    <i class="fas fa-arrow-right text-muted mx-2"></i>
                                    <strong><?= htmlspecialchars($review['reviewed_name']) ?></strong>
                                </div>
                                <div>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>

                                <hr>

                                <small class="text-muted">
                                    <i class="fas fa-route"></i>
                                    <?= htmlspecialchars($review['departure_city']) ?> → <?= htmlspecialchars($review['arrival_city']) ?>
                                    <br>
                                    <i class="fas fa-calendar"></i>
                                    <?= date('d/m/Y', strtotime($review['departure_time'])) ?>
                                    <br>
                                    <i class="fas fa-clock"></i>
                                    Publié le <?= date('d/m/Y à H:i', strtotime($review['created_at'])) ?>
                                </small>
                            </div>
                            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($review['is_validated']): ?>
                                        <span class="status-badge active">
                                            <i class="fas fa-check-circle"></i> Validé
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge pending">
                                            <i class="fas fa-clock"></i> En attente
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if (!$review['is_validated']): ?>
                                        <a href="?page=admin-reviews&action=validate&review_id=<?= $review['id'] ?>"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Valider cet avis ?')">
                                            <i class="fas fa-check"></i> Valider
                                        </a>
                                    <?php else: ?>
                                        <a href="?page=admin-reviews&action=reject&review_id=<?= $review['id'] ?>"
                                            class="btn btn-sm btn-warning"
                                            onclick="return confirm('Refuser cet avis ?')">
                                            <i class="fas fa-times"></i> Refuser
                                        </a>
                                    <?php endif; ?>

                                    <a href="?page=admin-reviews&action=delete&review_id=<?= $review['id'] ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('⚠️ SUPPRIMER définitivement cet avis ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>