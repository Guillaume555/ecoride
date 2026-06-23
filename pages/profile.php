<?php
/*
================================================
FICHIER: pages/profile.php - Espace utilisateur EcoRide (VERSION POO)
Description: Page profil utilisateur avec gestion compte et statistiques POO
================================================
*/

// Inclusion des fonctions de session et classes POO
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'classes/User.php';

// Vérification que l'utilisateur est connecté
requireLogin();

// Configuration de la page
$page_title = "EcoRide - Mon Profil";
$extra_css = ['auth.css', 'profile.css'];

// Récupération des données utilisateur avec POO
$user = getCurrentUser();
$userObj = new User($pdo, $user['id']);
$success_message = '';
$errors = [];

// Traitement de mise à jour du profil avec POO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    try {
        // Utilisation de User::update() avec validation automatique intégrée
        $userObj->update($username, $email, $phone);

        // Recharger les données utilisateur
        $user = getCurrentUser();
        $success_message = "Profil mis à jour avec succès !";
    } catch (Exception $e) {
        $errors['general'] = $e->getMessage();
    }
}

// Récupération des statistiques utilisateur avec POO
try {
    // Statistiques avec les méthodes POO
    $trips_as_passenger_data = $userObj->getTripsAsPassenger();
    $trips_as_driver_data = $userObj->getTripsAsDriver();

    // Compter les trajets confirmés pour passager
    $trips_as_passenger = 0;
    foreach ($trips_as_passenger_data as $trip) {
        if ($trip['status'] === 'confirmed') {
            $trips_as_passenger++;
        }
    }

    // Compter les trajets terminés pour conducteur
    $trips_as_driver = 0;
    foreach ($trips_as_driver_data as $trip) {
        if ($trip['status'] === 'completed') {
            $trips_as_driver++;
        }
    }

    // Note moyenne avec POO
    $average_rating = $userObj->getAverageRating();

    // Compter les avis pour affichage
    $review_count = 0;
    if ($average_rating > 0) {
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM reviews 
                WHERE reviewed_id = ? AND is_validated = 1
            ");
            $stmt->execute([$user['id']]);
            $review_count = $stmt->fetchColumn();
        } catch (Exception $e) {
            $review_count = 0;
        }
    }

    // Historique des transactions (utilise les données passager POO)
    $transactions = [];
    foreach (array_slice($trips_as_passenger_data, 0, 5) as $trip) {
        if ($trip['status'] === 'confirmed') {
            $transactions[] = [
                'type' => 'Réservation trajet',
                'amount' => -$trip['total_price'],
                'date' => $trip['departure_time'], // ou booking_date si disponible
                'description' => $trip['departure_city'] . ' → ' . $trip['arrival_city']
            ];
        }
    }
} catch (Exception $e) {
    $trips_as_passenger = 0;
    $trips_as_driver = 0;
    $average_rating = 0;
    $review_count = 0;
    $transactions = [];
}

// Récupération des données utilisateur actuelles avec POO
$user_profile = $userObj->getData();
?>

<!-- PAGE PROFIL UTILISATEUR -->
<section class="auth-section">
    <div class="container">

        <!-- EN-TÊTE PROFIL -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="auth-header text-center">
                    <h1 class="auth-title">
                        <i class="fas fa-user-circle text-success"></i>
                        Mon Profil EcoRide
                    </h1>
                    <p class="auth-subtitle">
                        Gérez vos informations personnelles et consultez vos statistiques
                    </p>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">

            <!-- COLONNE PRINCIPALE -->
            <div class="col-lg-8">

                <!-- MESSAGES -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <!-- CARTE INFORMATIONS PRINCIPALES -->
                <div class="auth-card mb-4">
                    <h3 class="text-success mb-4">
                        <i class="fas fa-id-card"></i> Informations du Compte
                    </h3>

                    <div class="row mb-4">
                        <!-- Avatar et infos principales -->
                        <div class="col-md-4 text-center">
                            <div class="user-avatar-large mb-3">
                                <?= strtoupper(substr($user['username'], 0, 2)) ?>
                            </div>
                            <h4><?= htmlspecialchars($user['username']) ?></h4>
                            <p class="text-muted"><?= ucfirst($user['role']) ?></p>
                        </div>

                        <!-- Crédits et statistiques -->
                        <div class="col-md-8">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-coins text-warning"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h3><?= $user['credits'] ?></h3>
                                            <p>Crédits</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-route text-primary"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h3><?= $trips_as_passenger ?></h3>
                                            <p>Trajets effectués</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-car text-success"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h3><?= $trips_as_driver ?></h3>
                                            <p>Trajets proposés</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-star text-warning"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h3><?= $average_rating ? $average_rating : '-' ?></h3>
                                            <p>Note moyenne</p>
                                            <?php if ($review_count > 0): ?>
                                                <small class="text-muted">(<?= $review_count ?> avis)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARTE MODIFICATION PROFIL -->
                <div class="auth-card mb-4">
                    <h3 class="text-success mb-4">
                        <i class="fas fa-edit"></i> Modifier mes Informations
                    </h3>

                    <form method="POST" class="auth-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user"></i> Pseudo
                                    </label>
                                    <input type="text"
                                        class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                                        id="username"
                                        name="username"
                                        value="<?= htmlspecialchars($user_profile['username']) ?>"
                                        required>
                                    <?php if (isset($errors['username'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['username']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i> Email
                                    </label>
                                    <input type="email"
                                        class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                        id="email"
                                        name="email"
                                        value="<?= htmlspecialchars($user_profile['email']) ?>"
                                        required>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['email']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone"></i> Téléphone
                            </label>
                            <input type="tel"
                                class="form-control"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars($user_profile['phone'] ?: '') ?>"
                                placeholder="Optionnel">
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-success">
                            <i class="fas fa-save"></i> Mettre à jour
                        </button>
                    </form>
                </div>

                <!-- CARTE HISTORIQUE TRANSACTIONS -->
                <div class="auth-card">
                    <h3 class="text-success mb-4">
                        <i class="fas fa-history"></i> Historique des Transactions
                    </h3>

                    <?php if (count($transactions) > 0): ?>
                        <div class="transaction-list">
                            <?php foreach ($transactions as $transaction): ?>
                                <div class="transaction-item">
                                    <div class="transaction-icon">
                                        <i class="fas fa-minus-circle text-danger"></i>
                                    </div>
                                    <div class="transaction-details">
                                        <h6><?= htmlspecialchars($transaction['description']) ?></h6>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($transaction['date'])) ?>
                                        </small>
                                    </div>
                                    <div class="transaction-amount">
                                        <span class="text-danger">
                                            <?= $transaction['amount'] ?> crédits
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="text-center mt-3">
                            <a href="?page=my-trips" class="btn btn-outline-success">
                                <i class="fas fa-list"></i> Voir tous mes trajets
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucune transaction</h5>
                            <p class="text-muted">Vous n'avez effectué aucun trajet pour le moment.</p>
                            <a href="?page=search" class="btn btn-success">
                                <i class="fas fa-search"></i> Rechercher un trajet
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- SIDEBAR ACTIONS RAPIDES -->
            <div class="col-lg-4">
                <div class="auth-card">
                    <h4 class="text-success mb-4">
                        <i class="fas fa-bolt"></i> Actions Rapides
                    </h4>

                    <div class="d-grid gap-2">
                        <a href="?page=search" class="btn btn-success">
                            <i class="fas fa-search"></i> Rechercher un trajet
                        </a>
                        <a href="?page=my-trips" class="btn btn-outline-success">
                            <i class="fas fa-route"></i> Mes trajets
                        </a>
                        <a href="?page=create-trip" class="btn btn-outline-primary">
                            <i class="fas fa-plus"></i> Proposer un trajet
                        </a>
                        <a href="?page=add-vehicle" class="btn btn-outline-info">
                            <i class="fas fa-car"></i> Mes véhicules
                        </a>
                        <hr>
                        <a href="?page=logout" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt"></i> Se déconnecter
                        </a>
                    </div>

                    <!-- Info membre -->
                    <div class="member-info mt-4 p-3 bg-light rounded">
                        <h6><i class="fas fa-calendar"></i> Membre depuis</h6>
                        <p class="mb-0 text-muted">
                            <?= date('F Y', strtotime($user_profile['created_at'] ?? 'now')) ?>
                        </p>
                    </div>
                </div>

                <!-- Statistiques détaillées -->
                <div class="auth-card mt-4">
                    <h6 class="text-secondary mb-3">
                        <i class="fas fa-chart-line"></i> Mes statistiques
                    </h6>

                    <div class="row text-center">
                        <div class="col-4">
                            <div class="fw-bold text-success"><?= count($trips_as_driver_data) ?></div>
                            <small class="text-muted">Trajets créés</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-primary"><?= count($trips_as_passenger_data) ?></div>
                            <small class="text-muted">Réservations</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-warning"><?= $user['credits'] ?></div>
                            <small class="text-muted">Crédits</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<?php
/*
================================================
AMÉLIORATIONS APPORTÉES PAR LA REFACTORISATION POO

AVANT (PROCÉDURAL) :
- Validation manuelle + UPDATE SQL direct pour mise à jour profil
- Requêtes SQL directes pour statistiques utilisateur
- Requête SQL directe pour note moyenne
- Requête SQL directe pour récupération données profil

APRÈS (POO) :
- User::update() avec validation automatique intégrée
- User::getTripsAsPassenger() et User::getTripsAsDriver() pour statistiques
- User::getAverageRating() pour la note moyenne
- User::getData() pour récupération propre des données

BÉNÉFICES :
- Validation automatique avec messages d'erreur contextuels
- Gestion d'erreurs centralisée avec exceptions
- Code plus maintenable et réutilisable
- Logique métier séparée de la présentation
- Synchronisation automatique de la session
- Architecture cohérente avec le reste de l'application
================================================
*/
?>