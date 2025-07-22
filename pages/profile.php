<?php
/*
================================================
FICHIER: pages/profile.php - Espace utilisateur EcoRide
Développé par: [Votre nom]
Description: Page profil utilisateur avec gestion compte et statistiques
================================================
*/

// Inclusion des fonctions de session et BDD
require_once 'includes/session.php';
require_once 'config/database.php';

// Vérification que l'utilisateur est connecté
requireLogin();

// Configuration de la page
$page_title = "EcoRide - Mon Profil";
$extra_css = ['auth.css', 'profile.css']; // Réutilise les styles d'authentification

// Récupération des données utilisateur
$user = getCurrentUser();
$success_message = '';
$errors = [];

// Traitement de mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Validation basique
    if (empty($username)) {
        $errors['username'] = "Le pseudo ne peut pas être vide.";
    } elseif (strlen($username) < 3) {
        $errors['username'] = "Le pseudo doit contenir au moins 3 caractères.";
    }

    if (empty($email)) {
        $errors['email'] = "L'email ne peut pas être vide.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format d'email invalide.";
    }

    // Vérification unicité (sauf pour l'utilisateur actuel)
    if (empty($errors)) {
        try {
            // Vérifier email unique
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :user_id");
            $stmt->execute([':email' => $email, ':user_id' => $user['id']]);
            if ($stmt->fetchColumn() > 0) {
                $errors['email'] = "Cette adresse email est déjà utilisée.";
            }

            // Vérifier pseudo unique
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username AND id != :user_id");
            $stmt->execute([':username' => $username, ':user_id' => $user['id']]);
            if ($stmt->fetchColumn() > 0) {
                $errors['username'] = "Ce pseudo est déjà utilisé.";
            }
        } catch (Exception $e) {
            $errors['general'] = "Erreur lors de la vérification des données.";
        }
    }

    // Mise à jour si pas d'erreurs
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = :username, email = :email, phone = :phone 
                WHERE id = :user_id
            ");

            $result = $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':phone' => $phone ?: null,
                ':user_id' => $user['id']
            ]);

            if ($result) {
                // Mettre à jour la session
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $user = getCurrentUser(); // Recharger les données
                $success_message = "Profil mis à jour avec succès !";
            }
        } catch (Exception $e) {
            $errors['general'] = "Erreur lors de la mise à jour.";
        }
    }
}

// Récupération des statistiques utilisateur
try {
    // Nombre de trajets en tant que passager
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM bookings b 
        JOIN trips t ON b.trip_id = t.id 
        WHERE b.passenger_id = :user_id AND b.status = 'confirmed'
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $trips_as_passenger = $stmt->fetchColumn();

    // Nombre de trajets en tant que conducteur
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM trips 
        WHERE driver_id = :user_id AND status = 'completed'
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $trips_as_driver = $stmt->fetchColumn();

    // Note moyenne en tant que conducteur
    $stmt = $pdo->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
        FROM reviews 
        WHERE reviewed_id = :user_id AND is_validated = 1
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $rating_data = $stmt->fetch();
    $average_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : null;
    $review_count = $rating_data['review_count'];

    // Historique des transactions (simulation)
    $stmt = $pdo->prepare("
        SELECT 'Réservation trajet' as type, -b.total_price as amount, b.booking_date as date,
               CONCAT(t.departure_city, ' → ', t.arrival_city) as description
        FROM bookings b
        JOIN trips t ON b.trip_id = t.id
        WHERE b.passenger_id = :user_id AND b.status = 'confirmed'
        ORDER BY b.booking_date DESC
        LIMIT 5
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $transactions = $stmt->fetchAll();
} catch (Exception $e) {
    $trips_as_passenger = 0;
    $trips_as_driver = 0;
    $average_rating = null;
    $review_count = 0;
    $transactions = [];
}

// Récupération des données utilisateur actuelles pour le formulaire
$stmt = $pdo->prepare("SELECT username, email, phone FROM users WHERE id = :user_id");
$stmt->execute([':user_id' => $user['id']]);
$user_profile = $stmt->fetch();
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
                        <hr>
                        <a href="?page=logout" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt"></i> Se déconnecter
                        </a>
                    </div>

                    <!-- Info membre -->
                    <div class="member-info mt-4 p-3 bg-light rounded">
                        <h6><i class="fas fa-calendar"></i> Membre depuis</h6>
                        <p class="mb-0 text-muted">
                            <?= date('F Y', strtotime($user['login_time'] ?? 'now')) ?>
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<?php
/*
================================================
NOTES DE DÉVELOPPEMENT:

1. FONCTIONNALITÉS IMPLÉMENTÉES:
   ✅ Affichage informations utilisateur complètes
   ✅ Statistiques personnelles (trajets, note moyenne)
   ✅ Modification profil avec validation
   ✅ Historique des transactions
   ✅ Actions rapides (sidebar)
   ✅ Design responsive et cohérent

2. SÉCURITÉ:
   ✅ Vérification connexion (requireLogin)
   ✅ Validation des modifications
   ✅ Requêtes préparées
   ✅ Échappement des données

3. UX/UI:
   ✅ Interface intuitive et claire
   ✅ Statistiques visuelles
   ✅ Messages de feedback
   ✅ Actions rapides accessibles
   ✅ Design cohérent avec le reste du site

4. INTÉGRATION:
   ✅ Compatible avec système d'authentification
   ✅ Liens vers autres pages (my-trips, search)
   ✅ Utilise auth.css existant
   ✅ Responsive design

PROCHAINE ÉTAPE: Créer pages/my-trips.php pour la gestion des trajets
================================================
*/
?>