<?php
/*
================================================
FICHIER: pages/admin-reviews.php - Modération des avis administrateur
Description: Interface de validation et gestion des avis utilisateurs avec POO
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
$page_title = "Modération Avis - Admin EcoRide";

// ========================================
// Classe Review pour les actions spécifiques aux avis
// ========================================

class Review
{
    private $pdo;
    private $id;
    private $data;

    public function __construct($pdo, $id = null)
    {
        $this->pdo = $pdo;
        $this->id = $id;
        if ($id !== null) {
            $this->loadById($id);
        }
    }

    public function loadById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$review) {
            throw new Exception("Avis introuvable.");
        }

        $this->id = $id;
        $this->data = $review;
        return $this->data;
    }

    public function validate()
    {
        if (!$this->id) {
            throw new Exception("Aucun avis chargé.");
        }

        $stmt = $this->pdo->prepare("UPDATE reviews SET is_validated = 1 WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    public function reject()
    {
        if (!$this->id) {
            throw new Exception("Aucun avis chargé.");
        }

        $stmt = $this->pdo->prepare("UPDATE reviews SET is_validated = 0 WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    public function delete()
    {
        if (!$this->id) {
            throw new Exception("Aucun avis chargé.");
        }

        $stmt = $this->pdo->prepare("DELETE FROM reviews WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    public static function getStats($pdo)
    {
        $stats = [];

        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM reviews WHERE is_validated = 1");
            $stats['validated'] = (int)$stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM reviews WHERE is_validated = 0");
            $stats['pending'] = (int)$stmt->fetchColumn();

            $stmt = $pdo->query("SELECT AVG(rating) FROM reviews WHERE is_validated = 1");
            $avg = $stmt->fetchColumn();
            $stats['average_rating'] = $avg ? round($avg, 1) : 0;
        } catch (Exception $e) {
            $stats = ['validated' => 0, 'pending' => 0, 'average_rating' => 0];
        }

        return $stats;
    }

    public static function getAll($pdo, $filter_status = '')
    {
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

        if ($filter_status === 'validated') {
            $sql .= " AND r.is_validated = 1";
        } elseif ($filter_status === 'pending') {
            $sql .= " AND r.is_validated = 0";
        }

        $sql .= " ORDER BY r.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ========================================
// Gestion des actions avec POO
// ========================================

$success_message = '';
$error_message = '';

if (isset($_GET['action']) && isset($_GET['review_id'])) {
    $action = $_GET['action'];
    $review_id = (int)$_GET['review_id'];

    try {
        $reviewObj = new Review($pdo, $review_id);

        switch ($action) {
            case 'validate':
                $reviewObj->validate();
                $success_message = "Avis validé avec succès.";
                break;

            case 'reject':
                $reviewObj->reject();
                $success_message = "Avis refusé.";
                break;

            case 'delete':
                $reviewObj->delete();
                $success_message = "Avis supprimé définitivement.";
                break;
        }
    } catch (Exception $e) {
        $error_message = "Erreur lors de l'action : " . $e->getMessage();
    }
}

// ========================================
// Récupération des données avec POO
// ========================================

$filter_status = $_GET['filter_status'] ?? '';

// Récupération des statistiques via POO
$reviewStats = Review::getStats($pdo);
$validated_reviews = $reviewStats['validated'];
$pending_reviews = $reviewStats['pending'];
$average_rating = $reviewStats['average_rating'];

// Récupération de la liste des avis via POO
$reviews = Review::getAll($pdo, $filter_status);
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

<?php
/*
================================================
REFACTORISATION ADMIN-REVIEWS.PHP

PROBLÈME RÉSOLU :
- Actions de validation/rejet avec SQL direct
- Statistiques avis avec requêtes manuelles répétées
- Récupération liste avis avec JOIN complexe non centralisé
- Logique métier dispersée dans la page

SOLUTION IMPLÉMENTÉE :
- Classe Review avec méthodes validate(), reject(), delete()
- Review::getStats() pour centraliser les statistiques
- Review::getAll() avec filtres intégrés
- Architecture POO cohérente avec les autres pages admin

ARCHITECTURE :
- Logique métier encapsulée dans la classe Review
- Méthodes statiques pour les opérations globales
- Gestion d'erreurs unifiée avec exceptions
- Code réutilisable et maintenable

FINALISATION :
Cette page termine la refactorisation POO de l'interface admin.
Toutes les pages critiques utilisent maintenant une architecture POO cohérente.
================================================
*/
?>