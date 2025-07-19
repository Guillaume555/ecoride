<?php
/*
================================================
FICHIER: pages/my-trips.php - Mes trajets EcoRide
Développé par: [Votre nom]
Description: Page historique des trajets utilisateur (conducteur + passager)
================================================
*/

// Inclusion des fonctions de session et BDD
require_once 'includes/session.php';
require_once 'config/database.php';

// Vérification que l'utilisateur est connecté
requireLogin();

// Configuration de la page
$page_title = "EcoRide - Mes Trajets";
$extra_css = ['auth.css']; // Réutilise les styles d'authentification

// Récupération des données utilisateur
$user = getCurrentUser();
$success_message = '';
$error_message = '';

// Traitement des actions (annulation de trajet)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $trip_id = $_POST['trip_id'] ?? null;
    $booking_id = $_POST['booking_id'] ?? null;

    if ($action === 'cancel_trip' && $trip_id) {
        // Annuler un trajet en tant que conducteur
        try {
            $stmt = $pdo->prepare("UPDATE trips SET status = 'cancelled' WHERE id = :trip_id AND driver_id = :user_id");
            $result = $stmt->execute([':trip_id' => $trip_id, ':user_id' => $user['id']]);

            if ($result) {
                $success_message = "Trajet annulé avec succès.";
                // TODO: Envoyer notification aux passagers
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de l'annulation du trajet.";
        }
    } elseif ($action === 'cancel_booking' && $booking_id) {
        // Annuler une réservation en tant que passager
        try {
            // Récupérer les détails de la réservation
            $stmt = $pdo->prepare("
                SELECT b.total_price, b.seats_booked, t.available_seats 
                FROM bookings b 
                JOIN trips t ON b.trip_id = t.id 
                WHERE b.id = :booking_id AND b.passenger_id = :user_id
            ");
            $stmt->execute([':booking_id' => $booking_id, ':user_id' => $user['id']]);
            $booking = $stmt->fetch();

            if ($booking) {
                // Annuler la réservation
                $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :booking_id");
                $stmt->execute([':booking_id' => $booking_id]);

                // Remettre les places disponibles
                $stmt = $pdo->prepare("UPDATE trips SET available_seats = available_seats + :seats WHERE id = (SELECT trip_id FROM bookings WHERE id = :booking_id)");
                $stmt->execute([':seats' => $booking['seats_booked'], ':booking_id' => $booking_id]);

                // Rembourser les crédits
                updateUserCredits($user['credits'] + $booking['total_price']);
                $user = getCurrentUser(); // Recharger les données

                $success_message = "Réservation annulée avec succès. Vos crédits ont été remboursés.";
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de l'annulation de la réservation.";
        }
    }
}

// Récupération des trajets en tant que conducteur
try {
    $stmt = $pdo->prepare("
        SELECT t.*, v.brand, v.model, v.fuel_type,
               COUNT(b.id) as passengers_count,
               SUM(CASE WHEN b.status = 'confirmed' THEN b.seats_booked ELSE 0 END) as confirmed_seats
        FROM trips t
        JOIN vehicles v ON t.vehicle_id = v.id
        LEFT JOIN bookings b ON t.id = b.trip_id
        WHERE t.driver_id = :user_id
        GROUP BY t.id
        ORDER BY t.departure_time DESC
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $my_trips_driver = $stmt->fetchAll();
} catch (Exception $e) {
    $my_trips_driver = [];
}

// Récupération des trajets en tant que passager
try {
    $stmt = $pdo->prepare("
        SELECT b.*, t.departure_city, t.arrival_city, t.departure_time, t.price_per_seat,
               u.username as driver_name, v.brand, v.model, v.fuel_type
        FROM bookings b
        JOIN trips t ON b.trip_id = t.id
        JOIN users u ON t.driver_id = u.id
        JOIN vehicles v ON t.vehicle_id = v.id
        WHERE b.passenger_id = :user_id
        ORDER BY t.departure_time DESC
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $my_trips_passenger = $stmt->fetchAll();
} catch (Exception $e) {
    $my_trips_passenger = [];
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
                                                <small class="text-muted">Passagers :</small><br>
                                                <strong><?= $trip['confirmed_seats'] ?> / <?= $trip['available_seats'] + $trip['confirmed_seats'] ?></strong>
                                            </div>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-6">
                                                <small class="text-muted">Prix par place :</small><br>
                                                <strong><?= number_format($trip['price_per_seat'], 0) ?>€</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Gain estimé :</small><br>
                                                <strong class="text-success">
                                                    <?= number_format($trip['confirmed_seats'] * $trip['price_per_seat'], 0) ?>€
                                                </strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="trip-actions mt-3">
                                        <a href="?page=detail&id=<?= $trip['id'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> Voir détail
                                        </a>

                                        <?php if ($trip['status'] === 'active' && strtotime($trip['departure_time']) > time()): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce trajet ?')">
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

                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <small class="text-muted">Statut paiement :</small><br>
                                                <span class="badge bg-<?= $booking['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                                    <?= ucfirst($booking['payment_status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="trip-actions mt-3">
                                        <a href="?page=detail&id=<?= $booking['trip_id'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> Voir détail
                                        </a>

                                        <?php if ($booking['status'] === 'confirmed' && strtotime($booking['departure_time']) > time()): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')">
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
    /* Styles spécifiques pour la liste des trajets */
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

    /* Scrollbar personnalisée */
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

    /* Responsive */
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
NOTES DE DÉVELOPPEMENT:

1. FONCTIONNALITÉS IMPLÉMENTÉES:
   ✅ Trajets en tant que conducteur avec détails complets
   ✅ Réservations en tant que passager avec statuts
   ✅ Annulation de trajets avec logique métier
   ✅ Annulation de réservations avec remboursement
   ✅ Liens vers détails et autres pages
   ✅ États vides avec actions suggérées

2. LOGIQUE MÉTIER:
   ✅ Remboursement automatique des crédits
   ✅ Remise en disponibilité des places
   ✅ Gestion des statuts (active, completed, cancelled)
   ✅ Vérification des droits (seul le propriétaire peut annuler)

3. SÉCURITÉ:
   ✅ Vérification connexion utilisateur
   ✅ Requêtes préparées pour éviter injection SQL
   ✅ Validation des actions et des IDs
   ✅ Confirmations JavaScript pour les annulations

4. UX/UI:
   ✅ Design cohérent avec auth.css
   ✅ Badges colorés pour les statuts
   ✅ Actions contextuelles selon l'état
   ✅ Messages de feedback
   ✅ Responsive design

PROCHAINE ÉTAPE: Intégrer les liens dans la navbar
================================================
*/
?>