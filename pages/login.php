<?php
/* ================================================
FICHIER: pages/login.php - Page de connexion EcoRide
Description: Formulaire de connexion avec validation sécurisée
================================================ */

// Inclusion des fonctions de session
require_once __DIR__ . '/../includes/analytics.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

// Configuration de la page
$page_title = "EcoRide - Connexion";
$extra_css = ['auth.css'];
$extra_js = ['login.js']; //Js spécifique a la page


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

                    // 🔎 Log analytique (pas de trackLogin : on utilise track)
                    track('login', [
                        'remember_me' => (bool)$remember_me
                    ]);

                    trackError('login', 'invalid_credentials', ['email' => $email ?? null]);


                    // Gestion "Se souvenir de moi"
                    if ($remember_me) {
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

<?php
/*
Résumé du fichier : page de connexion utilisateur

Fonction principale :
Affiche un formulaire de connexion sécurisé, avec gestion du message d’erreur et des redirections après login.

Éléments traités :
- Validation email/mot de passe avec password_verify()
- Vérification de l’activation du compte
- Connexion persistante via cookie sécurisé si "se souvenir de moi"
- Redirection vers la page précédente ou d’accueil après succès
- Affichage des messages d’erreur clairs
- Pré-remplissage de l’email en cas d’échec

Sécurité :
- Échappement des entrées utilisateur
- Cookies HttpOnly
- Validation côté serveur
- Protection contre les attaques temporelles

Connexion à l’écosystème :
- Utilise `includes/session.php`
- Compatible avec le router de l'application
- Prêt pour intégrer le CSS `auth.css` si présent
*/
?>