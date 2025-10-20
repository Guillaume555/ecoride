<?php
/*
================================================
FICHIER: pages/register.php - Page d'inscription EcoRide (VERSION POO)
Description: Formulaire d'inscription avec validation sécurisée
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
            if (strpos($error_message, 'email') !== false && strpos($error_message, 'existe') !== false) {
                $errors['email'] = $error_message;
            } elseif (strpos($error_message, 'email') !== false) {
                $errors['email'] = $error_message;
            } elseif (strpos($error_message, 'utilisateur') !== false && strpos($error_message, 'caractères') !== false) {
                $errors['username'] = $error_message;
            } elseif (strpos($error_message, 'pseudo') !== false || strpos($error_message, 'utilisateur') !== false) {
                $errors['username'] = $error_message;
            } elseif (strpos($error_message, 'mot de passe') !== false) {
                $errors['password'] = $error_message;
            } else {
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
                                placeholder="06 12 34 56 78">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['phone']) ?>
                                </div>
                            <?php endif; ?>
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
                                    minlength="8">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password')">
                                    <i class="fas fa-eye" id="eyePassword"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['password']) ?>
                                </div>
                            <?php endif; ?>
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
                            </div>
                            <?php if (isset($errors['password_confirm'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['password_confirm']) ?>
                                </div>
                            <?php endif; ?>
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

<script>
    // Fonction pour basculer la visibilité du mot de passe
    function togglePasswordVisibility(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById('eye' + fieldId.charAt(0).toUpperCase() + fieldId.slice(1));

        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            field.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }

    // Vérification force du mot de passe
    function checkPasswordStrength(password) {
        let strength = 0;
        let feedback = [];

        if (password.length >= 8) strength++;
        else feedback.push("8 caractères minimum");

        if (/[a-z]/.test(password)) strength++;
        else feedback.push("une minuscule");

        if (/[A-Z]/.test(password)) strength++;
        else feedback.push("une majuscule");

        if (/[0-9]/.test(password)) strength++;
        else feedback.push("un chiffre");

        if (/[^A-Za-z0-9]/.test(password)) strength++;
        else feedback.push("un caractère spécial");

        return {
            strength,
            feedback
        };
    }

    // Écouter les changements sur le champ mot de passe
    document.addEventListener('DOMContentLoaded', function() {
        const passwordField = document.getElementById('password');
        if (passwordField) {
            passwordField.addEventListener('input', function() {
                const password = this.value;
                const result = checkPasswordStrength(password);
                const indicator = document.getElementById('passwordStrength');

                let color, text;
                if (result.strength <= 2) {
                    color = 'danger';
                    text = 'Faible';
                } else if (result.strength <= 3) {
                    color = 'warning';
                    text = 'Moyen';
                } else {
                    color = 'success';
                    text = 'Fort';
                }

                indicator.innerHTML = `
                <div class="mt-2">
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-${color}" style="width: ${result.strength * 20}%"></div>
                    </div>
                    <small class="text-${color}">Force : ${text}</small>
                    ${result.feedback.length > 0 ? `<br><small class="text-muted">Manque : ${result.feedback.join(', ')}</small>` : ''}
                </div>
            `;
            });
        }
    });
</script>

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

FONCTIONNALITÉS AVANCÉES :
- Visibilité des mots de passe avec boutons toggle
- Indicateur de force du mot de passe en temps réel
- Conservation des données en cas d'erreur
- Redirection automatique vers login après succès

SÉCURITÉ IMPLÉMENTÉE :
- Validation centralisée dans la classe User (POO)
- Échappement HTML pour prévenir XSS
- Gestion d'exceptions robuste
- Messages d'erreur contextuels sans révéler d'infos sensibles

INTÉGRATION SYSTÈME :
- Compatible avec includes/session.php
- Utilise classes/User.php pour logique métier
- Interface Bootstrap responsive
- JavaScript pour UX améliorée

FLUX UTILISATEUR :
Formulaire → Validation confirmation → User::register() → Succès/Erreur
En cas de succès : Affichage message + redirection login
En cas d'erreur : Affichage erreur spécifique + conservation données
================================================
*/
?>