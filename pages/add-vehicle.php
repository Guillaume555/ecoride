<?php
/*
================================================
FICHIER: pages/add-vehicle.php - Ajout/Gestion des véhicules (VERSION POO)
Description: Permet aux utilisateurs d'ajouter leurs véhicules avec architecture POO
================================================
*/

// Inclusion des fonctions de session et classes POO
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'classes/Vehicle.php';

// Configuration de la page
$page_title = "Mes véhicules - EcoRide";
$extra_js = ['create-trip.js'];

// Vérification connexion utilisateur
requireLogin();
$user = getCurrentUser();

$errors = [];
$success = '';
$formData = [];

// Traitement du formulaire d'ajout avec POO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {

    $formData = [
        'brand' => trim($_POST['brand'] ?? ''),
        'model' => trim($_POST['model'] ?? ''),
        'color' => trim($_POST['color'] ?? ''),
        'license_plate' => trim(strtoupper($_POST['license_plate'] ?? '')),
        'seats' => (int)($_POST['seats'] ?? 0),
        'fuel_type' => $_POST['fuel_type'] ?? '',
        'year' => (int)($_POST['year'] ?? 0)
    ];

    // Validation de base (Vehicle::create() fera les validations avancées)
    if (empty($formData['brand'])) {
        $errors[] = "La marque est obligatoire.";
    }

    if (empty($formData['model'])) {
        $errors[] = "Le modèle est obligatoire.";
    }

    if (empty($formData['color'])) {
        $errors[] = "La couleur est obligatoire.";
    }

    if (empty($formData['license_plate'])) {
        $errors[] = "La plaque d'immatriculation est obligatoire.";
    }

    if ($formData['seats'] < 2 || $formData['seats'] > 9) {
        $errors[] = "Le nombre de places doit être entre 2 et 9.";
    }

    if (!in_array($formData['fuel_type'], ['essence', 'diesel', 'électrique', 'hybride'])) {
        $errors[] = "Type de carburant invalide.";
    }

    if ($formData['year'] < 1990 || $formData['year'] > date('Y') + 1) {
        $errors[] = "Année invalide.";
    }

    // Si validation de base OK, utiliser Vehicle::create() avec POO
    if (empty($errors)) {
        try {
            // Création du véhicule avec POO (validation automatique intégrée)
            $vehicle = new Vehicle($pdo);
            $vehicleId = $vehicle->create($user['id'], $formData);

            $success = "Véhicule ajouté avec succès !";
            $formData = []; // Reset form

        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// Récupération des véhicules existants avec POO
try {
    $userVehicles = Vehicle::getByUser($pdo, $user['id']);
} catch (Exception $e) {
    $userVehicles = [];
}
?>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <!-- Header -->
                <div class="text-center mb-5">
                    <h1 class="display-6 text-success mb-3">
                        <i class="fas fa-car"></i> Mes véhicules
                    </h1>
                    <p class="lead text-muted">
                        Gérez vos véhicules pour proposer des trajets
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
                    </div>
                <?php endif; ?>

                <!-- Véhicules existants -->
                <?php if (!empty($userVehicles)): ?>
                    <div class="auth-card mb-4">
                        <h5 class="text-success mb-3">
                            <i class="fas fa-list"></i> Vos véhicules enregistrés
                        </h5>

                        <div class="row">
                            <?php foreach ($userVehicles as $vehicle): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>
                                            </h6>
                                            <p class="card-text small text-muted mb-2">
                                                <i class="fas fa-palette"></i> <?= htmlspecialchars($vehicle['color']) ?><br>
                                                <i class="fas fa-id-card"></i> <?= htmlspecialchars($vehicle['license_plate']) ?><br>
                                                <i class="fas fa-users"></i> <?= $vehicle['seats'] ?> places<br>
                                                <i class="fas fa-gas-pump"></i> <?= ucfirst($vehicle['fuel_type']) ?><br>
                                                <i class="fas fa-calendar"></i> <?= $vehicle['year'] ?>
                                            </p>

                                            <!-- Affichage badge écologique avec POO -->
                                            <?php
                                            try {
                                                $vehicleObj = new Vehicle($pdo, $vehicle['id']);
                                                if ($vehicleObj->isEcological()):
                                            ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-leaf"></i> Écologique
                                                    </span>
                                            <?php endif;
                                            } catch (Exception $e) { /* Continue sans badge */
                                            } ?>

                                            <!-- Affichage trajets actifs -->
                                            <?php if (($vehicle['active_trips_count'] ?? 0) > 0): ?>
                                                <span class="badge bg-info ms-1">
                                                    <?= $vehicle['active_trips_count'] ?> trajet(s) actif(s)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Formulaire d'ajout -->
                <div class="auth-card">
                    <h5 class="text-success mb-4">
                        <i class="fas fa-plus"></i> Ajouter un véhicule
                    </h5>

                    <form method="POST">

                        <!-- Marque et modèle -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="brand" class="form-label">Marque *</label>
                                <input type="text"
                                    class="form-control"
                                    id="brand"
                                    name="brand"
                                    value="<?= htmlspecialchars($formData['brand'] ?? '') ?>"
                                    placeholder="Ex: Peugeot"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label for="model" class="form-label">Modèle *</label>
                                <input type="text"
                                    class="form-control"
                                    id="model"
                                    name="model"
                                    value="<?= htmlspecialchars($formData['model'] ?? '') ?>"
                                    placeholder="Ex: 308"
                                    required>
                            </div>
                        </div>

                        <!-- Couleur et plaque -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="color" class="form-label">Couleur *</label>
                                <input type="text"
                                    class="form-control"
                                    id="color"
                                    name="color"
                                    value="<?= htmlspecialchars($formData['color'] ?? '') ?>"
                                    placeholder="Ex: Blanc"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label for="license_plate" class="form-label">Plaque d'immatriculation *</label>
                                <input type="text"
                                    class="form-control"
                                    id="license_plate"
                                    name="license_plate"
                                    value="<?= htmlspecialchars($formData['license_plate'] ?? '') ?>"
                                    placeholder="AB-123-CD"
                                    pattern="[A-Z]{2}-[0-9]{3}-[A-Z]{2}"
                                    maxlength="9"
                                    required>
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i>
                                    Format automatiquement validé par le système
                                </div>
                            </div>
                        </div>

                        <!-- Places et carburant -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="seats" class="form-label">Nombre de places *</label>
                                <select class="form-select" id="seats" name="seats" required>
                                    <option value="">Sélectionnez</option>
                                    <?php for ($i = 2; $i <= 9; $i++): ?>
                                        <option value="<?= $i ?>"
                                            <?= (($formData['seats'] ?? 0) == $i) ? 'selected' : '' ?>>
                                            <?= $i ?> places
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="fuel_type" class="form-label">Type de carburant *</label>
                                <select class="form-select" id="fuel_type" name="fuel_type" required>
                                    <option value="">Sélectionnez</option>
                                    <option value="essence" <?= (($formData['fuel_type'] ?? '') === 'essence') ? 'selected' : '' ?>>Essence</option>
                                    <option value="diesel" <?= (($formData['fuel_type'] ?? '') === 'diesel') ? 'selected' : '' ?>>Diesel</option>
                                    <option value="électrique" <?= (($formData['fuel_type'] ?? '') === 'électrique') ? 'selected' : '' ?>>Électrique</option>
                                    <option value="hybride" <?= (($formData['fuel_type'] ?? '') === 'hybride') ? 'selected' : '' ?>>Hybride</option>
                                </select>
                            </div>
                        </div>

                        <!-- Année -->
                        <div class="mb-4">
                            <label for="year" class="form-label">Année *</label>
                            <input type="number"
                                class="form-control"
                                id="year"
                                name="year"
                                value="<?= htmlspecialchars($formData['year'] ?? '') ?>"
                                min="1990"
                                max="<?= date('Y') + 1 ?>"
                                placeholder="<?= date('Y') ?>"
                                required>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="?page=create-trip" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Retour
                            </a>
                            <button type="submit" name="add_vehicle" class="btn btn-success">
                                <i class="fas fa-plus"></i> Ajouter le véhicule
                            </button>
                        </div>

                    </form>
                </div>

                <!-- Action rapide -->
                <?php if (!empty($userVehicles)): ?>
                    <div class="text-center mt-4">
                        <a href="?page=create-trip" class="btn btn-success btn-lg">
                            <i class="fas fa-plus-circle"></i> Créer un trajet maintenant
                        </a>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Sidebar conseils véhicules -->
            <div class="col-lg-4">
                <div class="auth-card">
                    <h5 class="text-success mb-3">
                        <i class="fas fa-lightbulb"></i> Conseils véhicules
                    </h5>

                    <div class="mb-3">
                        <h6><i class="fas fa-leaf text-success"></i> Écologique</h6>
                        <p class="small text-muted">
                            Les véhicules électriques et hybrides sont marqués comme écologiques et attirent plus de passagers.
                        </p>
                    </div>

                    <div class="mb-3">
                        <h6><i class="fas fa-shield-alt text-primary"></i> Sécurité</h6>
                        <p class="small text-muted">
                            Assurez-vous que votre véhicule est en bon état et que votre assurance couvre le covoiturage.
                        </p>
                    </div>

                    <div class="mb-0">
                        <h6><i class="fas fa-users text-info"></i> Confort</h6>
                        <p class="small text-muted">
                            Un véhicule propre et confortable améliore l'expérience de vos passagers et vos notes.
                        </p>
                    </div>
                </div>

                <!-- Statistiques véhicules -->
                <?php if (!empty($userVehicles)): ?>
                    <div class="auth-card mt-4">
                        <h6 class="text-secondary mb-3">
                            <i class="fas fa-chart-pie"></i> Vos véhicules
                        </h6>

                        <?php
                        // Statistiques des véhicules
                        $totalVehicles = count($userVehicles);
                        $ecologicalCount = 0;
                        $totalActiveTrips = 0;

                        foreach ($userVehicles as $vehicle) {
                            try {
                                $vehicleObj = new Vehicle($pdo, $vehicle['id']);
                                if ($vehicleObj->isEcological()) {
                                    $ecologicalCount++;
                                }
                            } catch (Exception $e) {
                                // Continue without counting
                            }
                            $totalActiveTrips += ($vehicle['active_trips_count'] ?? 0);
                        }
                        ?>

                        <div class="row text-center">
                            <div class="col-4">
                                <div class="fw-bold text-success"><?= $totalVehicles ?></div>
                                <small class="text-muted">Total</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-info"><?= $ecologicalCount ?></div>
                                <small class="text-muted">Écolos</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-primary"><?= $totalActiveTrips ?></div>
                                <small class="text-muted">Trajets</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>

<?php
/*
================================================
AMÉLIORATIONS APPORTÉES PAR LA REFACTORISATION POO

AVANT (PROCÉDURAL) :
- Validation manuelle du format de plaque d'immatriculation
- Vérification d'unicité avec requête SQL directe
- Insertion véhicule avec SQL direct sans validation
- Récupération véhicules avec SQL direct

APRÈS (POO) :
- Vehicle::create() avec validation automatique intégrée
- Vehicle::getByUser() pour récupération propre
- Vehicle::isEcological() pour détection véhicules verts
- Gestion d'erreurs centralisée avec exceptions

BÉNÉFICES :
- Validation automatique de la plaque (format français)
- Vérification automatique de l'unicité
- Gestion d'erreurs plus propre avec try/catch
- Code réutilisable dans d'autres pages
- Logique métier centralisée dans la classe Vehicle
- Interface utilisateur améliorée avec badges automatiques
================================================
*/
?>