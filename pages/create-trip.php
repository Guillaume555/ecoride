<?php

/**
 * EcoRide - Page de création de trajets
 * Permet aux conducteurs de proposer leurs trajets
 */

require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'config/mongodb.php';

$page_title = "Créer un trajet - EcoRide";
$extra_css = ['create-trip.css']; // CSS spécifique à cette page
$extra_js = ['create-trip.js']; //Js spécifique a la page


// Vérification connexion utilisateur
requireLogin();
$user = getCurrentUser();

// Variables pour le formulaire
$errors = [];
$success = '';
$formData = [];

// Récupération des véhicules de l'utilisateur
try {
    $stmt = $pdo->prepare("
        SELECT id, brand, model, color, license_plate, seats, fuel_type, year 
        FROM vehicles 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $userVehicles = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Erreur lors de la récupération des véhicules.";
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_trip'])) {

    // Récupération et nettoyage des données
    $formData = [
        'departure_city' => trim($_POST['departure_city'] ?? ''),
        'arrival_city' => trim($_POST['arrival_city'] ?? ''),
        'departure_date' => $_POST['departure_date'] ?? '',
        'departure_time' => $_POST['departure_time'] ?? '',
        'vehicle_id' => (int)($_POST['vehicle_id'] ?? 0),
        'available_seats' => (int)($_POST['available_seats'] ?? 1),
        'price_per_seat' => (float)($_POST['price_per_seat'] ?? 0),
        'preferences' => trim($_POST['preferences'] ?? '')
    ];

    // Validation des données
    if (empty($formData['departure_city'])) {
        $errors[] = "La ville de départ est obligatoire.";
    }

    if (empty($formData['arrival_city'])) {
        $errors[] = "La ville d'arrivée est obligatoire.";
    }

    if ($formData['departure_city'] === $formData['arrival_city']) {
        $errors[] = "Les villes de départ et d'arrivée doivent être différentes.";
    }

    if (empty($formData['departure_date'])) {
        $errors[] = "La date de départ est obligatoire.";
    } elseif (strtotime($formData['departure_date']) < strtotime('today')) {
        $errors[] = "La date de départ ne peut pas être dans le passé.";
    }

    if (empty($formData['departure_time'])) {
        $errors[] = "L'heure de départ est obligatoire.";
    }

    if ($formData['vehicle_id'] <= 0) {
        $errors[] = "Veuillez sélectionner un véhicule.";
    } else {
        // Vérifier que le véhicule appartient bien à l'utilisateur
        $stmt = $pdo->prepare("SELECT id, seats FROM vehicles WHERE id = ? AND user_id = ?");
        $stmt->execute([$formData['vehicle_id'], $user['id']]);
        $vehicle = $stmt->fetch();

        if (!$vehicle) {
            $errors[] = "Véhicule non trouvé ou non autorisé.";
        } elseif ($formData['available_seats'] > ($vehicle['seats'] - 1)) {
            $errors[] = "Nombre de places disponibles invalide (maximum " . ($vehicle['seats'] - 1) . " places).";
        }
    }

    if ($formData['available_seats'] < 1 || $formData['available_seats'] > 8) {
        $errors[] = "Le nombre de places doit être entre 1 et 8.";
    }

    if ($formData['price_per_seat'] < 0 || $formData['price_per_seat'] > 100) {
        $errors[] = "Le prix par place doit être entre 0€ et 100€.";
    }

    // Si pas d'erreurs, créer le trajet
    if (empty($errors)) {
        try {
            // Formatage datetime
            $departureDateTime = $formData['departure_date'] . ' ' . $formData['departure_time'];

            $stmt = $pdo->prepare("
                INSERT INTO trips (
                    driver_id, vehicle_id, departure_city, arrival_city, 
                    departure_time, available_seats, price_per_seat, 
                    preferences, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");

            $stmt->execute([
                $user['id'],
                $formData['vehicle_id'],
                $formData['departure_city'],
                $formData['arrival_city'],
                $departureDateTime,
                $formData['available_seats'],
                $formData['price_per_seat'],
                $formData['preferences']
            ]);

            $tripId = $pdo->lastInsertId();

            // Log MongoDB de la création
            logUserActivity($user['id'], 'create_trip', [
                'trip_id' => $tripId,
                'departure_city' => $formData['departure_city'],
                'arrival_city' => $formData['arrival_city'],
                'departure_time' => $departureDateTime,
                'seats' => $formData['available_seats'],
                'price' => $formData['price_per_seat']
            ]);

            $success = "Votre trajet a été créé avec succès ! Il sera visible par les autres utilisateurs.";

            // Reset du formulaire
            $formData = [];

            // Redirection après 3 secondes
            echo "<script>
                setTimeout(function() {
                    window.location.href = '?page=my-trips';
                }, 3000);
            </script>";
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la création du trajet. Veuillez réessayer.";
            error_log("Create trip error: " . $e->getMessage());
        }
    }
}

// Suggestion de villes (pour l'autocomplete)
$popularCities = [
    'Paris',
    'Lyon',
    'Marseille',
    'Toulouse',
    'Nice',
    'Nantes',
    'Montpellier',
    'Strasbourg',
    'Bordeaux',
    'Lille',
    'Rennes',
    'Reims',
    'Saint-Étienne',
    'Toulon',
    'Le Havre',
    'Grenoble',
    'Dijon',
    'Angers',
    'Nîmes',
    'Villeurbanne'
];
?>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <!-- Header -->
                <div class="text-center mb-5">
                    <h1 class="display-6 text-success mb-3">
                        <i class="fas fa-plus-circle"></i> Créer un trajet
                    </h1>
                    <p class="lead text-muted">
                        Proposez votre trajet et partagez les frais avec d'autres voyageurs
                    </p>
                </div>

                <!-- Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Erreurs détectées :</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong><?= htmlspecialchars($success) ?></strong>
                        <div class="mt-2">
                            <small class="text-muted">
                                Redirection vers "Mes trajets" dans 3 secondes...
                            </small>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Vérification véhicules -->
                <?php if (empty($userVehicles)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-car"></i>
                        <strong>Aucun véhicule enregistré</strong>
                        <p class="mb-2">Pour créer un trajet, vous devez d'abord ajouter un véhicule à votre profil.</p>
                        <a href="?page=add-vehicle" class="btn btn-warning">
                            <i class="fas fa-plus"></i> Ajouter un véhicule
                        </a>
                    </div>
                <?php else: ?>

                    <!-- Formulaire de création -->
                    <div class="auth-card">
                        <form method="POST" id="createTripForm">

                            <!-- Trajets -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="departure_city" class="form-label">
                                        <i class="fas fa-map-marker-alt text-success"></i> Ville de départ *
                                    </label>
                                    <input type="text"
                                        class="form-control"
                                        id="departure_city"
                                        name="departure_city"
                                        value="<?= htmlspecialchars($formData['departure_city'] ?? '') ?>"
                                        placeholder="Ex: Paris"
                                        list="cities"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label for="arrival_city" class="form-label">
                                        <i class="fas fa-flag-checkered text-danger"></i> Ville d'arrivée *
                                    </label>
                                    <input type="text"
                                        class="form-control"
                                        id="arrival_city"
                                        name="arrival_city"
                                        value="<?= htmlspecialchars($formData['arrival_city'] ?? '') ?>"
                                        placeholder="Ex: Lyon"
                                        list="cities"
                                        required>
                                </div>
                            </div>

                            <!-- Date et heure -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="departure_date" class="form-label">
                                        <i class="fas fa-calendar text-primary"></i> Date de départ *
                                    </label>
                                    <input type="date"
                                        class="form-control"
                                        id="departure_date"
                                        name="departure_date"
                                        value="<?= htmlspecialchars($formData['departure_date'] ?? '') ?>"
                                        min="<?= date('Y-m-d') ?>"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label for="departure_time" class="form-label">
                                        <i class="fas fa-clock text-info"></i> Heure de départ *
                                    </label>
                                    <input type="time"
                                        class="form-control"
                                        id="departure_time"
                                        name="departure_time"
                                        value="<?= htmlspecialchars($formData['departure_time'] ?? '') ?>"
                                        required>
                                </div>
                            </div>

                            <!-- Véhicule -->
                            <div class="mb-4">
                                <label for="vehicle_id" class="form-label">
                                    <i class="fas fa-car text-secondary"></i> Véhicule *
                                </label>
                                <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                    <option value="">Sélectionnez votre véhicule</option>
                                    <?php foreach ($userVehicles as $vehicle): ?>
                                        <option value="<?= $vehicle['id'] ?>"
                                            data-seats="<?= $vehicle['seats'] ?>"
                                            <?= (($formData['vehicle_id'] ?? 0) == $vehicle['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>
                                            (<?= htmlspecialchars($vehicle['color']) ?> - <?= $vehicle['seats'] ?> places - <?= ucfirst($vehicle['fuel_type']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i>
                                    Le nombre de places disponibles sera automatiquement limité selon votre véhicule
                                </div>
                            </div>

                            <!-- Prix et places -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="price_per_seat" class="form-label">
                                        <i class="fas fa-euro-sign text-warning"></i> Prix par place *
                                    </label>
                                    <div class="input-group">
                                        <input type="number"
                                            class="form-control"
                                            id="price_per_seat"
                                            name="price_per_seat"
                                            value="<?= htmlspecialchars($formData['price_per_seat'] ?? '') ?>"
                                            min="0"
                                            max="100"
                                            step="0.5"
                                            placeholder="25"
                                            required>
                                        <span class="input-group-text">€</span>
                                    </div>
                                    <div class="form-text">
                                        Prix recommandé : 0,10€ à 0,15€ par kilomètre
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="available_seats" class="form-label">
                                        <i class="fas fa-users text-info"></i> Places disponibles *
                                    </label>
                                    <select class="form-select" id="available_seats" name="available_seats" required>
                                        <option value="">Sélectionnez d'abord un véhicule</option>
                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                            <option value="<?= $i ?>"
                                                <?= (($formData['available_seats'] ?? 0) == $i) ? 'selected' : '' ?>>
                                                <?= $i ?> place<?= $i > 1 ? 's' : '' ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Préférences -->
                            <div class="mb-4">
                                <label for="preferences" class="form-label">
                                    <i class="fas fa-heart text-danger"></i> Préférences de voyage
                                </label>
                                <textarea class="form-control"
                                    id="preferences"
                                    name="preferences"
                                    rows="3"
                                    placeholder="Ex: Non-fumeur, musique autorisée, animaux acceptés, conversation bienvenue..."><?= htmlspecialchars($formData['preferences'] ?? '') ?></textarea>
                                <div class="form-text">
                                    Décrivez l'ambiance que vous souhaitez pour le trajet (optionnel)
                                </div>
                            </div>

                            <!-- Préférences prédéfinies -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-check-square text-success"></i> Préférences rapides
                                </label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="no_smoking">
                                            <label class="form-check-label" for="no_smoking">
                                                <i class="fas fa-smoking-ban"></i> Non-fumeur
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="pets_allowed">
                                            <label class="form-check-label" for="pets_allowed">
                                                <i class="fas fa-paw"></i> Animaux autorisés
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="luggage_ok">
                                            <label class="form-check-label" for="luggage_ok">
                                                <i class="fas fa-suitcase"></i> Bagages autorisés
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Boutons d'action -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="?page=my-trips" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                                <button type="submit" name="create_trip" class="btn btn-success btn-lg">
                                    <i class="fas fa-plus-circle"></i> Créer le trajet
                                </button>
                            </div>

                        </form>
                    </div>

                <?php endif; ?>

            </div>

            <!-- Sidebar conseils -->
            <div class="col-lg-4">
                <div class="auth-card">
                    <h5 class="text-success mb-3">
                        <i class="fas fa-lightbulb"></i> Conseils pour un bon trajet
                    </h5>

                    <div class="mb-3">
                        <h6><i class="fas fa-euro-sign text-warning"></i> Prix juste</h6>
                        <p class="small text-muted">
                            Calculez 0,10€ à 0,15€ par kilomètre pour couvrir l'essence, péages et usure.
                        </p>
                    </div>

                    <div class="mb-3">
                        <h6><i class="fas fa-clock text-info"></i> Ponctualité</h6>
                        <p class="small text-muted">
                            Respectez l'heure de départ annoncée. Prévenez en cas de retard.
                        </p>
                    </div>

                    <div class="mb-3">
                        <h6><i class="fas fa-shield-alt text-success"></i> Sécurité</h6>
                        <p class="small text-muted">
                            Vérifiez votre véhicule avant le départ. Respectez le code de la route.
                        </p>
                    </div>

                    <div class="mb-0">
                        <h6><i class="fas fa-smile text-primary"></i> Convivialité</h6>
                        <p class="small text-muted">
                            Créez une ambiance agréable. Le covoiturage, c'est du partage !
                        </p>
                    </div>
                </div>

                <!-- Statistiques rapides -->
                <div class="auth-card mt-4">
                    <h6 class="text-secondary mb-3">
                        <i class="fas fa-chart-line"></i> Vos trajets
                    </h6>

                    <!-- REMPLACER LA SECTION STATISTIQUES DANS create-trip.php PAR : -->

                    <?php
                    // Statistiques rapides de l'utilisateur (AVEC PROTECTION NULL)
                    try {
                        $stmt = $pdo->prepare("
                            SELECT 
                                COUNT(*) as total_trips,
                                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_trips,
                                AVG(price_per_seat) as avg_price
                            FROM trips 
                            WHERE driver_id = ?
                        ");
                        $stmt->execute([$user['id']]);
                        $stats = $stmt->fetch();

                        // Protection contre les valeurs NULL
                        $stats['total_trips'] = (int)($stats['total_trips'] ?? 0);
                        $stats['active_trips'] = (int)($stats['active_trips'] ?? 0);
                        $stats['avg_price'] = (float)($stats['avg_price'] ?? 0);
                    } catch (PDOException $e) {
                        $stats = ['total_trips' => 0, 'active_trips' => 0, 'avg_price' => 0];
                    }
                    ?>

                    <div class="row text-center">
                        <div class="col-4">
                            <div class="fw-bold text-success"><?= $stats['total_trips'] ?></div>
                            <small class="text-muted">Total</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-primary"><?= $stats['active_trips'] ?></div>
                            <small class="text-muted">Actifs</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-warning"><?= $stats['avg_price'] > 0 ? number_format($stats['avg_price'], 1) : '0.0' ?>€</div>
                            <small class="text-muted">Prix moy.</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Datalist pour l'autocomplete des villes -->
<datalist id="cities">
    <?php foreach ($popularCities as $city): ?>
        <option value="<?= htmlspecialchars($city) ?>">
        <?php endforeach; ?>
</datalist>