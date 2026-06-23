<?php
/*
================================================
FICHIER: pages/register.php - Page d'inscription EcoRide (VERSION POO)
Description: Formulaire d'inscription avec validation sécurisée complète
================================================
*/

// Inclusion des fonctions de session et classes POO
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'classes/User.php';

// Configuration de la page
$page_title = "EcoRide - Inscription";
$extra_css = ['auth.css'];
$extra_js = ['register.js'];

// Variables pour gérer les erreurs et messages
$errors = [];
$success_message = '';
$form_data = [
    'username' => '',
    'email' => '',
    'phone' => ''
];

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupération et nettoyage des données
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Conservation des données pour réaffichage en cas d'erreur
    $form_data = [
        'username' => $username,
        'email' => $email,
        'phone' => $phone
    ];

    // Validation confirmation mot de passe (seule validation front-end)
    if ($password !== $password_confirm) {
        $errors['password_confirm'] = "Les mots de passe ne correspondent pas.";
    }

    // Inscription POO si pas d'erreur de confirmation
    if (empty($errors)) {
        try {
            // Créer un objet User et tenter l'inscription
            $user = new User($pdo);
            $result = $user->register($username, $email, $password, $phone);

            if ($result) {
                // Succès de l'inscription
                $success_message = "Inscription réussie ! Vous allez être redirigé vers la page de connexion...";

                // Redirection différée vers login
                echo '<script>
                        setTimeout(function() {
                            window.location.href = "?page=login&registered=1";
                        }, 2000);
                      </script>';
            }
        } catch (Exception $e) {
            // Gestion intelligente des erreurs POO
            $error_message = $e->getMessage();

            // Dispatcher les erreurs selon le contenu du message
            // Erreurs liées à l'email
            if (stripos($error_message, 'email') !== false) {
                if (stripos($error_message, 'existe') !== false || stripos($error_message, 'déjà') !== false || stripos($error_message, 'utilisée') !== false) {
                    $errors['email'] = $error_message;
                } elseif (stripos($error_message, 'format') !== false || stripos($error_message, 'invalide') !== false) {
                    $errors['email'] = $error_message;
                } elseif (stripos($error_message, 'long') !== false || stripos($error_message, 'dépasser') !== false) {
                    $errors['email'] = $error_message;
                } elseif (stripos($error_message, 'obligatoire') !== false) {
                    $errors['email'] = $error_message;
                } else {
                    $errors['email'] = $error_message;
                }
            }
            // Erreurs liées au nom d'utilisateur
            elseif (stripos($error_message, 'utilisateur') !== false || stripos($error_message, 'pseudo') !== false || stripos($error_message, 'username') !== false) {
                $errors['username'] = $error_message;
            }
            // Erreurs liées au mot de passe
            elseif (stripos($error_message, 'mot de passe') !== false || stripos($error_message, 'password') !== false) {
                $errors['password'] = $error_message;
            }
            // Erreurs liées au téléphone
            elseif (stripos($error_message, 'téléphone') !== false || stripos($error_message, 'phone') !== false) {
                $errors['phone'] = $error_message;
            }
            // Autres erreurs
            else {
                $errors['general'] = $error_message;
            }
        }
    }
}
?>

<!-- PAGE D'INSCRIPTION -->
<section class="auth-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">

                <!-- EN-TÊTE -->
                <div class="auth-header text-center mb-4">
                    <h1 class="auth-title">
                        <i class="fas fa-user-plus text-success"></i>
                        Créer un compte EcoRide
                    </h1>
                    <p class="auth-subtitle">
                        Rejoignez la communauté du covoiturage écologique
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

                    <!-- FORMULAIRE D'INSCRIPTION -->
                    <form method="POST" action="?page=register" class="auth-form" novalidate>

                        <!-- PSEUDO -->
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user"></i> Pseudo *
                            </label>
                            <input type="text"
                                class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                                id="username"
                                name="username"
                                value="<?= htmlspecialchars($form_data['username']) ?>"
                                placeholder="Votre pseudo"
                                required
                                maxlength="50">
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['username']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">
                                3-50 caractères, lettres, chiffres, tirets et underscores uniquement
                            </div>
                        </div>

                        <!-- EMAIL -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Adresse email *
                            </label>
                            <input type="email"
                                class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($form_data['email']) ?>"
                                placeholder="votre.email@exemple.com"
                                required
                                maxlength="100">
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['email']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- TÉLÉPHONE -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone"></i> Téléphone (optionnel)
                            </label>
                            <input type="tel"
                                class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars($form_data['phone']) ?>"
                                placeholder="0690123456"
                                maxlength="20">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['phone']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">
                                Format : 10 à 20 caractères (chiffres, espaces, tirets acceptés)
                            </div>
                        </div>

                        <!-- MOT DE PASSE -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> Mot de passe *
                            </label>
                            <div class="input-group">
                                <input type="password"
                                    class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                    id="password"
                                    name="password"
                                    placeholder="Votre mot de passe"
                                    required
                                    minlength="8"
                                    maxlength="255">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password')">
                                    <i class="fas fa-eye" id="eyePassword"></i>
                                </button>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback">
                                        <?= htmlspecialchars($errors['password']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="form-text">
                                Minimum 8 caractères avec au moins une lettre et un chiffre
                            </div>
                            <!-- Indicateur de force du mot de passe -->
                            <div id="passwordStrength"></div>
                        </div>

                        <!-- CONFIRMATION MOT DE PASSE -->
                        <div class="mb-4">
                            <label for="password_confirm" class="form-label">
                                <i class="fas fa-lock"></i> Confirmer le mot de passe *
                            </label>
                            <div class="input-group">
                                <input type="password"
                                    class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                                    id="password_confirm"
                                    name="password_confirm"
                                    placeholder="Confirmer votre mot de passe"
                                    required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password_confirm')">
                                    <i class="fas fa-eye" id="eyePassword_confirm"></i>
                                </button>
                                <?php if (isset($errors['password_confirm'])): ?>
                                    <div class="invalid-feedback">
                                        <?= htmlspecialchars($errors['password_confirm']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- BOUTON INSCRIPTION -->
                        <button type="submit" class="btn btn-success btn-lg w-100 mb-3">
                            <i class="fas fa-user-plus"></i>
                            Créer mon compte
                        </button>

                        <!-- INFORMATIONS CRÉDITS -->
                        <div class="auth-info">
                            <i class="fas fa-coins text-success"></i>
                            <strong>Bonus inscription :</strong> 20 crédits offerts pour commencer !
                        </div>

                    </form>

                    <!-- LIEN VERS CONNEXION -->
                    <div class="auth-footer text-center">
                        <p class="mb-0">
                            Vous avez déjà un compte ?
                            <a href="?page=login" class="auth-link">Se connecter</a>
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
FONCTIONNEMENT DU FICHIER REGISTER.PHP

Ce fichier gère l'inscription des nouveaux utilisateurs sur EcoRide.

LOGIQUE PRINCIPALE :
1. Validation basique de la confirmation des mots de passe
2. Utilisation de la classe User pour l'inscription sécurisée
3. Toute la validation métier est déléguée à User::register()
4. Gestion intelligente des erreurs avec dispatch selon le contenu

VALIDATION COMPLÈTE IMPLÉMENTÉE :
- Nom d'utilisateur : 3-50 caractères, lettres/chiffres/tirets/underscores uniquement
- Email : format valide, 100 caractères maximum, unicité vérifiée
- Téléphone : 10-20 caractères, chiffres/espaces/tirets/+ uniquement (optionnel)
- Mot de passe : 8-255 caractères, au moins 1 lettre et 1 chiffre

GESTION D'ERREURS AMÉLIORÉE :
- Messages conviviaux pour l'utilisateur (pas d'erreurs SQL brutes)
- Dispatch intelligent des erreurs vers le bon champ
- Conservation des données en cas d'erreur

FONCTIONNALITÉS AVANCÉES :
- Visibilité des mots de passe avec boutons toggle
- Indicateur de force du mot de passe en temps réel
- Redirection automatique vers login après succès
- Interface Bootstrap responsive

SÉCURITÉ IMPLÉMENTÉE :
- Validation centralisée dans la classe User (POO)
- Échappement HTML pour prévenir XSS
- Gestion d'exceptions robuste
- Messages d'erreur contextuels sans révéler d'infos sensibles

FLUX UTILISATEUR :
Formulaire → Validation confirmation → User::register() → Succès/Erreur
En cas de succès : Affichage message + redirection login après 2s
En cas d'erreur : Affichage erreur spécifique + conservation données
================================================
*/
?>