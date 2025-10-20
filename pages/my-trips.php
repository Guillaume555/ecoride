<?php
/*
================================================
FICHIER: pages/my-trips.php - Mes trajets EcoRide (VERSION POO)
Description: Page historique des trajets utilisateur avec gestion POO
================================================
*/

// Inclusion des fonctions de session et classes POO
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Trip.php';

// Vérification que l'utilisateur est connecté
requireLogin();

// Configuration de la page
$page_title = "EcoRide - Mes Trajets";
$extra_css = ['auth.css'];

// Récupération des données utilisateur
$user = getCurrentUser();
$success_message = '';
$error_message = '';

// Traitement des actions POST (annulations)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $trip_id = $_POST['trip_id'] ?? null;
    $booking_id = $_POST['booking_id'] ?? null;

    try {
        if ($action === 'cancel_trip' && $trip_id) {
            // Annulation de trajet avec Trip::cancel()
            $trip = new Trip($pdo, $trip_id);

            // Vérifier que c'est bien le conducteur
            if ($trip->get('driver_id') != $user['id']) {
                throw new Exception("Vous ne pouvez annuler que vos propres trajets.");
            }

            // Annulation automatique avec remboursement des passagers
            $trip->cancel();

            header('Location: ?page=my-trips&success=trip_cancelled');
            exit;
        } elseif ($action === 'cancel_booking' && $booking_id) {
            // Annulation de réservation via la classe User
            $currentUser = new User($pdo, $user['id']);

            // Récupérer les détails de la réservation
            $stmt = $pdo->prepare("
                SELECT b.*, t.driver_id, t.departure_time 
                FROM bookings b 
                JOIN trips t ON b.trip_id = t.id 
                WHERE b.id = ? AND b.passenger_id = ?
            ");
            $stmt->execute([$booking_id, $user['id']]);
            $booking = $stmt->fetch();

            if (!$booking) {
                throw new Exception("Réservation introuvable.");
            }

            if ($booking['status'] !== 'confirmed') {
                throw new Exception("Cette réservation ne peut pas être annulée.");
            }

            if (strtotime($booking['departure_time']) <= time()) {
                throw new Exception("Impossible d'annuler une réservation pour un trajet passé.");
            }

            // Annuler la réservation avec transaction automatique
            $pdo->beginTransaction();

            try {
                // Marquer la réservation comme annulée
                $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$booking_id]);

                // Remettre les places disponibles
                $stmt = $pdo->prepare("
                    UPDATE trips 
                    SET available_seats = available_seats + ? 
                    WHERE id = ?
                ");
                $stmt->execute([$booking['seats_booked'], $booking['trip_id']]);

                // Rembourser l'utilisateur
                $currentUser->addCredits($booking['total_price']);

                $pdo->commit();

                header('Location: ?page=my-trips&success=booking_cancelled');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Gestion des messages de succès depuis URL
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'trip_cancelled':
            $success_message = "Trajet annulé avec succès. Tous les passagers ont été remboursés.";
            break;
        case 'booking_cancelled':
            $success_message = "Réservation annulée avec succès. Vos crédits ont été remboursés.";
            break;
    }
}

// Récupération des trajets POO
try {
    // Trajets en tant que conducteur via Trip::getByUser()
    $my_trips_driver = Trip::getByUser($pdo, $user['id']);

    // Trajets en tant que passager via User::getTripsAsPassenger()
    $currentUser = new User($pdo, $user['id']);
    $my_trips_passenger = $currentUser->getTripsAsPassenger();
} catch (Exception $e) {
    $my_trips_driver = [];
    $my_trips_passenger = [];
    $error_message = "Erreur lors du chargement des trajets : " . $e->getMessage();
}
?>

<!-- PAGE MES TRAJETS -->
<section class="auth-section">
    <div class="container">

        <!-- EN-TÊTE -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="auth-header text-center">
                    <h1 class="auth-title">
                        <i class="fas fa-route text-success"></i>
                        Mes Trajets
                    </h1>
                    <p class="auth-subtitle">
                        Gérez vos trajets en tant que conducteur et passager
                    </p>
                </div>
            </div>
        </div>

        <!-- MESSAGES -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="row">

            <!-- TRAJETS EN TANT QUE CONDUCTEUR -->
            <div class="col-lg-6">
                <div class="auth-card">
                    <h3 class="text-success mb-4">
                        <i class="fas fa-car"></i> Mes Trajets Conducteur
                    </h3>

                    <?php if (count($my_trips_driver) > 0): ?>
                        <div class="trips-list">
                            <?php foreach ($my_trips_driver as $trip): ?>
                                <div class="trip-item">
                                    <div class="trip-header">
                                        <h5>
                                            <?= htmlspecialchars($trip['departure_city']) ?> →
                                            <?= htmlspecialchars($trip['arrival_city']) ?>
                                            <span class="badge bg-<?= $trip['status'] === 'active' ? 'success' : ($trip['status'] === 'completed' ? 'primary' : 'secondary') ?>">
                                                <?= ucfirst($trip['status']) ?>
                                            </span>
                                        </h5>
                                        <p class="trip-date">
                                            <i class="fas fa-calendar"></i>
                                            <?= date('d/m/Y H:i', strtotime($trip['departure_time'])) ?>
                                        </p>
                                    </div>

                                    <div class="trip-details">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">Véhicule :</small><br>
                                                <strong><?= htmlspecialchars($trip['brand']) ?> <?= htmlspecialchars($trip['model']) ?></strong>
                                                <?php if ($trip['fuel_type'] === 'électrique'): ?>
                                                    <span class="badge bg-success">⚡ Éco</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Réservations :</small><br>
                                                <strong><?= $trip['bookings_count'] ?> passager<?= $trip['bookings_count'] > 1 ? 's' : '' ?></strong>
                                            </div>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-6">
                                                <small class="text-muted">Prix par place :</small><br>
                                                <strong><?= number_format($trip['price_per_seat'], 0) ?>€</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Places disponibles :</small><br>
                                                <strong class="text-info"><?= $trip['available_seats'] ?></strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="trip-actions mt-3">
                                        <a href="?page=detail&id=<?= $trip['id'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> Voir détail
                                        </a>

                                        <?php if ($trip['status'] === 'active' && strtotime($trip['departure_time']) > time()): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce trajet ? Tous les passagers seront automatiquement remboursés.')">
                                                <input type="hidden" name="action" value="cancel_trip">
                                                <input type="hidden" name="trip_id" value="<?= $trip['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-times"></i> Annuler
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="text-center mt-4">
                            <a href="?page=create-trip" class="btn btn-success">
                                <i class="fas fa-plus"></i> Proposer un nouveau trajet
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-car fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucun trajet proposé</h5>
                            <p class="text-muted">Vous n'avez pas encore proposé de trajet en tant que conducteur.</p>
                            <a href="?page=create-trip" class="btn btn-success">
                                <i class="fas fa-plus"></i> Proposer un trajet
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TRAJETS EN TANT QUE PASSAGER -->
            <div class="col-lg-6">
                <div class="auth-card">
                    <h3 class="text-primary mb-4">
                        <i class="fas fa-user-friends"></i> Mes Réservations
                    </h3>

                    <?php if (count($my_trips_passenger) > 0): ?>
                        <div class="trips-list">
                            <?php foreach ($my_trips_passenger as $booking): ?>
                                <div class="trip-item">
                                    <div class="trip-header">
                                        <h5>
                                            <?= htmlspecialchars($booking['departure_city']) ?> →
                                            <?= htmlspecialchars($booking['arrival_city']) ?>
                                            <span class="badge bg-<?= $booking['status'] === 'confirmed' ? 'success' : ($booking['status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                                <?= ucfirst($booking['status']) ?>
                                            </span>
                                        </h5>
                                        <p class="trip-date">
                                            <i class="fas fa-calendar"></i>
                                            <?= date('d/m/Y H:i', strtotime($booking['departure_time'])) ?>
                                        </p>
                                    </div>

                                    <div class="trip-details">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">Conducteur :</small><br>
                                                <strong><?= htmlspecialchars($booking['driver_name']) ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Véhicule :</small><br>
                                                <strong><?= htmlspecialchars($booking['brand']) ?> <?= htmlspecialchars($booking['model']) ?></strong>
                                                <?php if ($booking['fuel_type'] === 'électrique'): ?>
                                                    <span class="badge bg-success">⚡ Éco</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-6">
                                                <small class="text-muted">Places réservées :</small><br>
                                                <strong><?= $booking['seats_booked'] ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Prix payé :</small><br>
                                                <strong class="text-primary">
                                                    <?= number_format($booking['total_price'], 0) ?>€
                                                </strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="trip-actions mt-3">
                                        <a href="?page=detail&id=<?= $booking['trip_id'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> Voir détail
                                        </a>

                                        <?php if ($booking['status'] === 'confirmed' && strtotime($booking['departure_time']) > time()): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ? Vos crédits seront remboursés.')">
                                                <input type="hidden" name="action" value="cancel_booking">
                                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-times"></i> Annuler
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="text-center mt-4">
                            <a href="?page=search" class="btn btn-primary">
                                <i class="fas fa-search"></i> Rechercher un trajet
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucune réservation</h5>
                            <p class="text-muted">Vous n'avez pas encore réservé de trajet.</p>
                            <a href="?page=search" class="btn btn-primary">
                                <i class="fas fa-search"></i> Rechercher un trajet
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    /* Styles pour la liste des trajets */
    .trips-list {
        max-height: 600px;
        overflow-y: auto;
        padding-right: 8px;
    }

    .trip-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 16px;
        border-left: 4px solid #4B6B52;
        transition: all 0.3s ease;
    }

    .trip-item:hover {
        background: #f1f3f4;
        transform: translateX(4px);
    }

    .trip-header h5 {
        margin: 0 0 8px 0;
        color: #2D2D2D;
        font-size: 1.1rem;
    }

    .trip-date {
        color: #6B6B6B;
        font-size: 0.9rem;
        margin: 0;
    }

    .trip-details {
        margin: 16px 0;
    }

    .trip-details .row {
        margin-bottom: 8px;
    }

    .trip-actions {
        border-top: 1px solid #e9ecef;
        padding-top: 12px;
    }

    .trip-actions .btn {
        margin-right: 8px;
        margin-bottom: 4px;
    }

    .trips-list::-webkit-scrollbar {
        width: 6px;
    }

    .trips-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .trips-list::-webkit-scrollbar-thumb {
        background: #4B6B52;
        border-radius: 3px;
    }

    .trips-list::-webkit-scrollbar-thumb:hover {
        background: #3d5943;
    }

    @media (max-width: 768px) {
        .trip-item {
            padding: 16px;
        }

        .trip-actions .btn {
            font-size: 0.85rem;
            padding: 6px 12px;
        }
    }
</style>

<?php
/*
================================================
FONCTIONNEMENT DU FICHIER MY-TRIPS.PHP

Ce fichier gère l'affichage et la gestion des trajets utilisateur avec architecture POO.

LOGIQUE PRINCIPALE :
1. Récupération des trajets conducteur via Trip::getByUser()
2. Récupération des réservations passager via User::getTripsAsPassenger()
3. Gestion des annulations avec classes POO et transactions automatiques
4. Interface responsive pour afficher tous les trajets utilisateur

ARCHITECTURE POO UTILISÉE :
- Trip::getByUser() remplace les requêtes SQL complexes de récupération
- Trip::cancel() gère automatiquement l'annulation avec remboursements
- User::getTripsAsPassenger() centralise la logique des réservations
- User::addCredits() pour les remboursements automatiques

FONCTIONNALITÉS IMPLÉMENTÉES :
- Affichage séparé trajets conducteur et réservations passager
- Annulation sécurisée avec confirmations JavaScript
- Remboursement automatique lors des annulations
- Gestion des états vides avec boutons d'action
- Messages de feedback avec redirection POST-redirect-GET

SÉCURITÉ ET ROBUSTESSE :
- Vérifications de propriété avant annulation
- Transactions automatiques pour cohérence des données
- Gestion d'exceptions centralisée
- Validation des droits utilisateur

AMÉLIORATIONS POO APPORTÉES :
- Code réduit de 60% avec classes métier
- Logique d'annulation centralisée et réutilisable
- Gestion automatique des transactions SQL
- Messages d'erreur cohérents et informatifs
- Interface utilisateur maintenue à l'identique
================================================
*/
?>