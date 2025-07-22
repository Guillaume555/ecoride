<?php
/*
================================================
FICHIER: pages/detail.php - Page détail d'un trajet
Description: Affichage détaillé d'un trajet avec toutes les infos
================================================
*/

// Inclusion de la configuration base de données
require_once 'config/database.php';

// Configuration de la page
$page_title = "EcoRide - Détail du trajet";
$extra_css = ['detail.css']; // CSS spécifique à cette page
$extra_js = ['detail.js']; //Js spécifique a la page

// Traitement de la réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve'])) {
    // Inclusion des fonctions de session
    if (file_exists('includes/session.php')) {
        require_once 'includes/session.php';
    }

    // Vérification que l'utilisateur est connecté
    if (function_exists('isLoggedIn') && isLoggedIn()) {
        $user = getCurrentUser();
        $seats = intval($_POST['seats'] ?? 1);
        $total_price = $seats * floatval($_GET['price'] ?? 0); // Temporaire

        // Vérification crédits suffisants
        if ($user['credits'] >= $total_price) {
            $reservation_success = "Réservation simulée réussie ! (À implémenter)";
        } else {
            $reservation_error = "Crédits insuffisants. Vous avez {$user['credits']} crédits, il en faut {$total_price}.";
        }
    } else {
        header('Location: ?page=login');
        exit;
    }
}

// Récupération de l'ID du trajet depuis l'URL
$trip_id = $_GET['id'] ?? null;

// Si pas d'ID fourni, redirection vers la recherche
if (!$trip_id) {
    header('Location: ?page=search');
    exit;
}

try {
    // Récupération des détails complets du trajet
    $stmt = $pdo->prepare("
        SELECT t.*, 
                u.username as driver_name, 
               u.phone as driver_phone,
               u.credits as driver_credits,
               v.brand, v.model, v.color, v.license_plate, v.fuel_type, v.year,
               (SELECT AVG(rating) FROM reviews WHERE reviewed_id = t.driver_id AND is_validated = 1) as driver_rating,
               (SELECT COUNT(*) FROM reviews WHERE reviewed_id = t.driver_id AND is_validated = 1) as driver_reviews_count
        FROM trips t 
        JOIN users u ON t.driver_id = u.id 
        JOIN vehicles v ON t.vehicle_id = v.id 
        WHERE t.id = :trip_id AND t.status = 'active'
    ");
    $stmt->execute([':trip_id' => $trip_id]);
    $trip = $stmt->fetch();

    // Si le trajet n'existe pas, redirection
    if (!$trip) {
        header('Location: ?page=search');
        exit;
    }

    // Récupération des avis sur le conducteur
    $stmt = $pdo->prepare("
        SELECT r.rating, r.comment, r.created_at, u.username as reviewer_name
        FROM reviews r
        JOIN users u ON r.reviewer_id = u.id
        WHERE r.reviewed_id = :driver_id AND r.is_validated = 1
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([':driver_id' => $trip['driver_id']]);
    $reviews = $stmt->fetchAll();
} catch (Exception $e) {
    // En cas d'erreur, redirection avec message
    header('Location: ?page=search&error=1');
    exit;
}
?>

<!-- PAGE DE DÉTAIL D'UN TRAJET -->
<section class="trip-detail-section">
    <div class="container">

        <!-- BOUTON RETOUR -->
        <div class="mb-4">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Retour aux résultats
            </a>
        </div>

        <!-- EN-TÊTE DU TRAJET -->
        <div class="trip-detail-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="trip-title">
                        <?= htmlspecialchars($trip['departure_city']) ?>
                        <i class="fas fa-arrow-right text-success"></i>
                        <?= htmlspecialchars($trip['arrival_city']) ?>
                    </h1>
                    <div class="trip-datetime">
                        <p class="datetime-info">
                            <i class="fas fa-calendar text-success"></i> <!-- VERT = CORRECT -->
                            <?= date('l d F Y', strtotime($trip['departure_time'])) ?>
                            <span class="ms-4">
                                <i class="fas fa-clock text-success"></i> <!-- VERT = CORRECT -->
                                <?= date('H:i', strtotime($trip['departure_time'])) ?>
                            </span>
                        </p>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="trip-price-big">
                        <span class="price-amount"><?= number_format($trip['price_per_seat'], 0) ?>€</span>
                        <small class="price-label">par place</small>
                    </div>
                    <div class="available-seats">
                        <i class="fas fa-users text-success"></i>
                        <?= $trip['available_seats'] ?> place<?= $trip['available_seats'] > 1 ? 's' : '' ?> disponible<?= $trip['available_seats'] > 1 ? 's' : '' ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- COLONNE PRINCIPALE -->
            <div class="col-lg-8">

                <!-- CARTE CONDUCTEUR -->
                <div class="detail-card">
                    <h3 class="card-title">
                        <i class="fas fa-user text-success"></i> Conducteur
                    </h3>
                    <div class="driver-profile">
                        <div class="driver-avatar-large">
                            <?= strtoupper(substr($trip['driver_name'], 0, 2)) ?>
                        </div>
                        <div class="driver-info-large">
                            <h4><?= htmlspecialchars($trip['driver_name']) ?></h4>

                            <?php if ($trip['driver_rating']): ?>
                                <div class="rating-display">
                                    <div class="stars-large">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= round($trip['driver_rating']) ? 'text-warning' : 'text-muted' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-text">
                                        <?= number_format($trip['driver_rating'], 1) ?>/5
                                        (<?= $trip['driver_reviews_count'] ?> avis)
                                    </span>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Nouveau conducteur</p>
                            <?php endif; ?>

                            <div class="driver-badges">
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-coins"></i> <?= $trip['driver_credits'] ?> crédits
                                </span>
                                <?php if ($trip['fuel_type'] === 'électrique'): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-leaf"></i> Conducteur éco
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARTE VÉHICULE -->
                <div class="detail-card">
                    <h3 class="card-title">
                        <i class="fas fa-car text-success"></i> Véhicule
                    </h3>
                    <div class="vehicle-details">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="vehicle-name">
                                    <?= htmlspecialchars($trip['brand']) ?> <?= htmlspecialchars($trip['model']) ?>
                                    <?php if ($trip['fuel_type'] === 'électrique'): ?>
                                        <span class="badge bg-success ms-2">⚡ Électrique</span>
                                    <?php endif; ?>
                                </h4>
                                <div class="vehicle-specs">
                                    <div class="spec-item">
                                        <strong>Couleur :</strong> <?= htmlspecialchars($trip['color']) ?>
                                    </div>
                                    <div class="spec-item">
                                        <strong>Année :</strong> <?= $trip['year'] ?>
                                    </div>
                                    <div class="spec-item">
                                        <strong>Carburant :</strong>
                                        <?= ucfirst($trip['fuel_type']) ?>
                                        <?php if ($trip['fuel_type'] === 'électrique'): ?>
                                            <i class="fas fa-leaf text-success ms-1"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="vehicle-icon">
                                    <i class="fas fa-car fa-3x text-muted"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARTE PRÉFÉRENCES -->
                <?php if (!empty($trip['preferences'])): ?>
                    <div class="detail-card">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle text-success"></i> Préférences du conducteur
                        </h3>
                        <div class="preferences-content">
                            <p class="preferences-text"><?= htmlspecialchars($trip['preferences']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- CARTE AVIS -->
                <?php if (count($reviews) > 0): ?>
                    <div class="detail-card">
                        <h3 class="card-title">
                            <i class="fas fa-star text-success"></i> Avis sur le conducteur
                        </h3>
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <div class="reviewer-avatar">
                                                <?= strtoupper(substr($review['reviewer_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($review['reviewer_name']) ?></strong>
                                                <div class="review-rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="review-date text-muted">
                                            <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                                        </small>
                                    </div>
                                    <p class="review-comment"><?= htmlspecialchars($review['comment']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PANNEAU DE RÉSERVATION -->
            <div class="col-lg-4">
                <div class="booking-panel">

                    <div class="booking-panel">
                        <!-- AFFICHAGE CRÉDITS SI CONNECTÉ -->
                        <?php
                        if (file_exists('includes/session.php')) {
                            require_once 'includes/session.php';
                        }
                        if (function_exists('isLoggedIn') && isLoggedIn()):
                        ?>
                            <div class="user-credits-display mb-3 p-3 bg-light rounded">
                                <i class="fas fa-coins text-success"></i>
                                <strong>Vos crédits : <?= getCurrentUser()['credits'] ?></strong>
                                <small class="d-block text-muted">Prix de ce trajet : <?= $trip['price_per_seat'] ?>€ par place</small>
                            </div>
                        <?php endif; ?>

                        <div class="booking-header">

                            <div class="booking-header">
                                <h4><i class="fas fa-ticket-alt"></i> Réservation</h4>
                            </div>

                            <div class="booking-summary">
                                <div class="summary-row">
                                    <span>Prix par place :</span>
                                    <strong><?= number_format($trip['price_per_seat'], 0) ?>€</strong>
                                </div>
                                <div class="summary-row">
                                    <span>Places disponibles :</span>
                                    <strong class="text-success"><?= $trip['available_seats'] ?></strong>
                                </div>
                                <div class="summary-row">
                                    <span>Date de départ :</span>
                                    <strong><?= date('d/m/Y', strtotime($trip['departure_time'])) ?></strong>
                                </div>
                                <div class="summary-row">
                                    <span>Heure de départ :</span>
                                    <strong><?= date('H:i', strtotime($trip['departure_time'])) ?></strong>
                                </div>
                            </div>

                            <div class="booking-form">
                                <form id="bookingForm">
                                    <div class="mb-3">
                                        <label for="seats" class="form-label">Nombre de places</label>
                                        <select class="form-select" id="seats" name="seats" data-price-per-seat="<?= $trip['price_per_seat'] ?>">
                                            <?php for ($i = 1; $i <= min(4, $trip['available_seats']); $i++): ?>
                                                <option value="<?= $i ?>"><?= $i ?> place<?= $i > 1 ? 's' : '' ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <div class="total-price-display">
                                        <div class="total-row">
                                            <span>Total :</span>
                                            <strong id="total-price" class="total-amount">
                                                <?= $trip['price_per_seat'] ?>€
                                            </strong>
                                        </div>
                                    </div>

                                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                                        <!-- Utilisateur connecté -->
                                        <button type="submit" name="reserve" class="btn btn-success btn-lg w-100 mt-3">
                                            <i class="fas fa-check"></i> Réserver ce trajet
                                        </button>
                                    <?php else: ?>
                                        <!-- Visiteur non connecté -->
                                        <a href="?page=login" class="btn btn-success btn-lg w-100 mt-3">
                                            <i class="fas fa-sign-in-alt"></i> Se connecter pour réserver
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>

                            <div class="booking-info">
                                <div class="info-item">
                                    <i class="fas fa-info-circle text-success"></i>
                                    <small>Vous devez être connecté pour réserver un trajet.</small>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-shield-alt text-primary"></i>
                                    <small>Paiement sécurisé avec vos crédits EcoRide.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</section>

<?php
/*
Résumé du fichier : pages/detail.php

Cette page affiche tous les détails d’un trajet sélectionné par l’utilisateur :
- Informations générales (villes, horaire, tarif, places restantes)
- Profil du conducteur, note moyenne, badges et crédits
- Détails du véhicule associé au trajet
- Préférences spécifiques du conducteur (si renseignées)
- Avis des passagers précédents
- Formulaire de réservation avec calcul dynamique du prix total

La page est sécurisée contre les accès invalides (ID de trajet manquant ou inexistant), et toutes les données sont échappées.

Améliorations à prévoir :
- Connecter à la base réelle le système de réservation
- Remplacer la simulation par un enregistrement en BDD
- Ajouter une messagerie conducteur/passager
- Intégrer les photos du véhicule si disponible

Ce fichier utilise un CSS et un JS spécifiques : `detail.css` et `detail.js`.
*/
?>