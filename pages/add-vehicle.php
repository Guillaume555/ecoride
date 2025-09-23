<?php

/**
 * EcoRide - Ajout/Gestion des véhicules
 * Permet aux utilisateurs d'ajouter leurs véhicules
 */

// Variables pour template
$page_title = "Mes véhicules - EcoRide";
$extra_js = ['create-trip.js']; //Js spécifique a la page


require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'config/mongodb.php';

// Vérification connexion utilisateur
requireLogin();
$user = getCurrentUser();

$errors = [];
$success = '';
$formData = [];

// Traitement du formulaire d'ajout
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

    // Validation
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
    } elseif (!preg_match('/^[A-Z]{2}-\d{3}-[A-Z]{2}$/', $formData['license_plate'])) {
        $errors[] = "Format de plaque invalide (ex: AB-123-CD).";
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

    // Vérifier unicité plaque d'immatriculation
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE license_plate = ?");
            $stmt->execute([$formData['license_plate']]);
            if ($stmt->fetch()) {
                $errors[] = "Cette plaque d'immatriculation est déjà enregistrée.";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la vérification.";
        }
    }

    // Insérer le véhicule
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO vehicles (user_id, brand, model, color, license_plate, seats, fuel_type, year)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $user['id'],
                $formData['brand'],
                $formData['model'],
                $formData['color'],
                $formData['license_plate'],
                $formData['seats'],
                $formData['fuel_type'],
                $formData['year']
            ]);

            // Log MongoDB
            logUserActivity($user['id'], 'add_vehicle', [
                'brand' => $formData['brand'],
                'model' => $formData['model'],
                'license_plate' => $formData['license_plate'],
                'fuel_type' => $formData['fuel_type']
            ]);

            $success = "Véhicule ajouté avec succès !";
            $formData = []; // Reset form

        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'ajout du véhicule.";
        }
    }
}

// Récupération des véhicules existants
try {
    $stmt = $pdo->prepare("
        SELECT * FROM vehicles 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $userVehicles = $stmt->fetchAll();
} catch (PDOException $e) {
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
                                            <?php if ($vehicle['fuel_type'] === 'électrique'): ?>
                                                <span class="badge bg-success">Écologique</span>
                                            <?php elseif ($vehicle['fuel_type'] === 'hybride'): ?>
                                                <span class="badge bg-info">Hybride</span>
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
        </div>
    </div>
</section>

<!-- Le javaScript est dans la page create-trip.js -->