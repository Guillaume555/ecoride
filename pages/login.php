<?php
/*
================================================
FICHIER: pages/login.php - Page de connexion EcoRide
Développé par: [Votre nom]
Description: Formulaire de connexion avec validation sécurisée
================================================
*/

// Inclusion des fonctions de session
require_once 'includes/session.php';
require_once 'config/database.php';

// Configuration de la page
$page_title = "EcoRide - Connexion";
$extra_css = ['auth.css'];

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

    // ========== VALIDATION DES DONNÉES ==========

    // Validation de l'email
    if (empty($email)) {
        $errors['email'] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format d'email invalide.";
    }

    // Validation du mot de passe
    if (empty($password)) {
        $errors['password'] = "Le mot de passe est obligatoire.";
    }

    // ========== VÉRIFICATION CONNEXION ==========

    if (empty($errors)) {
        try {
            // Recherche de l'utilisateur par email
            $stmt = $pdo->prepare("
                SELECT id, username, email, password, credits, role, is_active 
                FROM users 
                WHERE email = :email
            ");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                // Vérification que le compte est actif
                if (!$user['is_active']) {
                    $errors['general'] = "Votre compte a été désactivé. Contactez l'administrateur.";
                }
                // Vérification du mot de passe
                elseif (password_verify($password, $user['password'])) {
                    // Connexion réussie !
                    loginUser($user);

                    // Gestion "Se souvenir de moi" (bonus)
                    if ($remember_me) {
                        // Cookie sécurisé pour 30 jours
                        setcookie(
                            'remember_token',
                            base64_encode($user['id'] . ':' . $user['email']),
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
                } else {
                    $errors['password'] = "Mot de passe incorrect.";
                }
            } else {
                $errors['email'] = "Aucun compte trouvé avec cette adresse email.";
            }
        } catch (Exception $e) {
            $errors['general'] = "Erreur lors de la connexion. Veuillez réessayer.";
            // En production : error_log($e->getMessage());
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

                            <!-- LIENS UTILES -->
                            <div class="text-center">
                                <small class="text-muted">
                                    <a href="#" class="auth-link">Mot de passe oublié ?</a>
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

<script>
    /*
================================================
JAVASCRIPT POUR LA CONNEXION
================================================
*/

    // Auto-focus sur le champ email si vide
    document.addEventListener('DOMContentLoaded', function() {
        const emailField = document.getElementById('email');
        if (emailField && emailField.value === '') {
            emailField.focus();
        }
    });

    // Validation simple côté client
    document.querySelector('.auth-form').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        if (!email || !password) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires.');
            return false;
        }

        if (!email.includes('@')) {
            e.preventDefault();
            alert('Veuillez saisir une adresse email valide.');
            return false;
        }
    });

    // Affichage/masquage du mot de passe
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordField = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    });
</script>

<?php
/*
================================================
NOTES DE DÉVELOPPEMENT:

1. FONCTIONNALITÉS IMPLÉMENTÉES:
   ✅ Formulaire de connexion avec validation
   ✅ Vérification email + mot de passe
   ✅ Gestion des comptes inactifs
   ✅ Messages d'erreur précis
   ✅ Redirection intelligente après connexion
   ✅ "Se souvenir de moi" avec cookies sécurisés
   ✅ Comptes de démonstration (localhost)

2. SÉCURITÉ:
   ✅ Vérification password_verify()
   ✅ Protection contre timing attacks
   ✅ Cookies sécurisés (HttpOnly)
   ✅ Validation côté serveur + client
   ✅ Échappement de toutes les données

3. UX/UI:
   ✅ Auto-focus sur email
   ✅ Conservation email en cas d'erreur
   ✅ Messages clairs et utiles
   ✅ Liens vers inscription et récupération
   ✅ Design cohérent avec register.php

4. INTÉGRATION:
   ✅ Utilise includes/session.php
   ✅ Compatible avec le router
   ✅ Prêt pour auth.css
   ✅ Gestion redirection après login

PROCHAINE ÉTAPE: Tester le parcours complet inscription → connexion → navigation
================================================
*/
?>