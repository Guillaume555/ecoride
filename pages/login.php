<?php
/*
================================================
FICHIER: pages/login.php - Page de connexion EcoRide
Description: Formulaire de connexion avec validation sécurisée
================================================
*/

// Inclusion des fonctions de session et classes POO
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'classes/User.php';

// Configuration de la page
$page_title = "EcoRide - Connexion";
$extra_css = ['auth.css'];
$extra_js = ['login.js'];

// Si l'utilisateur est déjà connecté, redirection
if (isLoggedIn()) {
    header('Location: ?page=home');
    exit;
}

// Variables pour gérer les erreurs et messages
$errors = [];
$success_message = '';
$email = '';

// Message de bienvenue si vient de s'inscrire
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success_message = "Inscription réussie ! Vous pouvez maintenant vous connecter avec vos identifiants.";
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupération et nettoyage des données
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    // Validation basique des champs obligatoires
    if (empty($email)) {
        $errors['email'] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format d'email invalide.";
    }

    if (empty($password)) {
        $errors['password'] = "Le mot de passe est obligatoire.";
    }

    // Authentification POO
    if (empty($errors)) {
        try {
            $user = new User($pdo);
            $userData = $user->login($email, $password);

            // Gestion "Se souvenir de moi"
            if ($remember_me) {
                setcookie(
                    'remember_token',
                    base64_encode($userData['id'] . ':' . $userData['email']),
                    time() + (30 * 24 * 60 * 60),
                    '/',
                    '',
                    false,
                    true
                );
            }

            // Redirection intelligente
            $redirect_url = getRedirectAfterLogin();
            if ($redirect_url) {
                header('Location: ' . $redirect_url);
            } else {
                header('Location: ?page=home');
            }
            exit;
        } catch (Exception $e) {
            $errors['general'] = $e->getMessage();
        }
    }
}
?>

<!-- PAGE DE CONNEXION -->
<section class="auth-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">

                <!-- EN-TÊTE -->
                <div class="auth-header text-center mb-4">
                    <h1 class="auth-title">
                        <i class="fas fa-sign-in-alt text-success"></i>
                        Connexion EcoRide
                    </h1>
                    <p class="auth-subtitle">
                        Connectez-vous pour accéder à votre espace
                    </p>
                </div>

                <!-- CARTE FORMULAIRE -->
                <div class="auth-card">

                    <!-- MESSAGE DE SUCCÈS -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($success_message) ?>
                        </div>
                    <?php endif; ?>

                    <!-- MESSAGE D'ERREUR GÉNÉRAL -->
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($errors['general']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- FORMULAIRE DE CONNEXION -->
                    <form method="POST" action="?page=login" class="auth-form" novalidate>

                        <!-- EMAIL -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Adresse email
                            </label>
                            <input type="email"
                                class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($email) ?>"
                                placeholder="votre.email@exemple.com"
                                required
                                autofocus>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['email']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- MOT DE PASSE -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> Mot de passe
                            </label>
                            <div class="input-group">
                                <input type="password"
                                    class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                    id="password"
                                    name="password"
                                    placeholder="Votre mot de passe"
                                    required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['password']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- SE SOUVENIR DE MOI -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input"
                                    type="checkbox"
                                    id="remember_me"
                                    name="remember_me">
                                <label class="form-check-label" for="remember_me">
                                    Se souvenir de moi (30 jours)
                                </label>
                            </div>
                        </div>

                        <!-- BOUTON CONNEXION -->
                        <button type="submit" class="btn btn-success btn-lg w-100 mb-3">
                            <i class="fas fa-sign-in-alt"></i>
                            Se connecter
                        </button>

                        <!-- MOTS DE PASSE OUBLIÉ -->
                        <div class="text-center">
                            <small class="text-muted">
                                <a href="?page=forgot-password" class="auth-link">
                                    <i class="fas fa-key"></i> Mot de passe oublié ?
                                </a>
                            </small>
                        </div>

                    </form>

                    <!-- LIEN VERS INSCRIPTION -->
                    <div class="auth-footer text-center">
                        <p class="mb-0">
                            Vous n'avez pas encore de compte ?
                            <a href="?page=register" class="auth-link">Créer un compte</a>
                        </p>
                        <div class="auth-info mt-3">
                            <i class="fas fa-coins text-success"></i>
                            <strong>Nouveau ?</strong> 20 crédits offerts à l'inscription !
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<!-- DEMO : Comptes de test (à supprimer en production) -->
<?php if ($_SERVER['SERVER_NAME'] === 'localhost'): ?>
    <section class="py-4 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Comptes de démonstration</h5>
                        <p class="mb-2"><strong>Pour tester :</strong></p>
                        <ul class="mb-0">
                            <li><strong>Passager :</strong> marie@email.com / motdepasse</li>
                            <li><strong>Conducteur :</strong> pierre@email.com / motdepasse</li>
                            <li><strong>Admin :</strong> admin@ecoride.fr / motdepasse</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
/*
================================================
FONCTIONNEMENT DU FICHIER LOGIN.PHP

Ce fichier gère l'authentification des utilisateurs sur EcoRide.

LOGIQUE PRINCIPALE :
1. Validation basique des champs email et mot de passe
2. Utilisation de la classe User pour l'authentification sécurisée
3. Gestion des sessions et cookies "se souvenir de moi"
4. Redirection intelligente après connexion réussie

SÉCURITÉ IMPLÉMENTÉE :
- Validation email côté serveur
- Authentification via classe POO (encapsulation)
- Gestion des exceptions pour erreurs claires
- Cookies sécurisés HttpOnly pour "remember me"
- Protection CSRF via méthode POST

INTÉGRATION SYSTÈME :
- Compatible avec includes/session.php (helpers existants)
- Utilise classes/User.php pour logique métier
- Responsive Bootstrap pour interface utilisateur
- Messages d'erreur contextuels et visuels

FLUX UTILISATEUR :
Formulaire → Validation → User::login() → Session → Redirection
En cas d'erreur : Affichage message + conservation email saisi
================================================
*/
?>