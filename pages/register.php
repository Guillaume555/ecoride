<?php
/*
================================================
FICHIER: pages/register.php - Page d'inscription EcoRide
Développé par: [Votre nom]
Description: Formulaire d'inscription avec validation sécurisée
================================================
*/

// Inclusion de la configuration base de données
require_once 'config/database.php';

// Configuration de la page
$page_title = "EcoRide - Inscription";
$extra_css = ['auth.css']; // CSS spécifique authentification
$extra_js = ['register.js']; //Js spécifique a la page


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

    // ========== VALIDATION DES DONNÉES ==========

    // Validation du pseudo
    if (empty($username)) {
        $errors['username'] = "Le pseudo est obligatoire.";
    } elseif (strlen($username) < 3) {
        $errors['username'] = "Le pseudo doit contenir au moins 3 caractères.";
    } elseif (strlen($username) > 50) {
        $errors['username'] = "Le pseudo ne peut pas dépasser 50 caractères.";
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        $errors['username'] = "Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores.";
    }

    // Validation de l'email
    if (empty($email)) {
        $errors['email'] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format d'email invalide.";
    } elseif (strlen($email) > 100) {
        $errors['email'] = "L'email ne peut pas dépasser 100 caractères.";
    }

    // Validation du téléphone (optionnel mais si renseigné, doit être valide)
    if (!empty($phone)) {
        if (!preg_match('/^[0-9+\-\s\(\)]{10,20}$/', $phone)) {
            $errors['phone'] = "Format de téléphone invalide.";
        }
    }

    // Validation du mot de passe
    if (empty($password)) {
        $errors['password'] = "Le mot de passe est obligatoire.";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)/', $password)) {
        $errors['password'] = "Le mot de passe doit contenir au moins une lettre et un chiffre.";
    }

    // Validation de la confirmation de mot de passe
    if (empty($password_confirm)) {
        $errors['password_confirm'] = "La confirmation du mot de passe est obligatoire.";
    } elseif ($password !== $password_confirm) {
        $errors['password_confirm'] = "Les mots de passe ne correspondent pas.";
    }

    // ========== VÉRIFICATIONS EN BASE DE DONNÉES ==========

    if (empty($errors)) {
        try {
            // Vérification que l'email n'est pas déjà utilisé
            if (userExists($email)) {
                $errors['email'] = "Cette adresse email est déjà utilisée.";
            }

            // Vérification que le pseudo n'est pas déjà utilisé
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetchColumn() > 0) {
                $errors['username'] = "Ce pseudo est déjà utilisé.";
            }
        } catch (Exception $e) {
            $errors['general'] = "Erreur lors de la vérification des données. Veuillez réessayer.";
        }
    }

    // ========== CRÉATION DU COMPTE ==========

    if (empty($errors)) {
        try {
            // Hachage sécurisé du mot de passe
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insertion de l'utilisateur en base
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, phone, credits, role, created_at, is_active) 
                VALUES (:username, :email, :password, :phone, 20, 'passenger', NOW(), 1)
            ");

            $result = $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashed_password,
                ':phone' => $phone ?: null
            ]);

            // Après l'insertion en BDD réussie (ligne ~90)
            // Après l'insertion en BDD réussie (ligne ~90)
            if ($result) {
                // ✅ LOG MONGODB - INSCRIPTION
                $new_user_id = (int) $pdo->lastInsertId();
                logUserActivity($new_user_id, 'register', [
                    'username'   => $username ?? null,
                    'email'      => $email ?? null,
                    'ip'         => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);

                // Succès de l'inscription
                $success_message = "Inscription réussie !...";

                // Redirection différée vers login
                echo '<script>
                        setTimeout(function() {
                            window.location.href = "?page=login&registered=1";
                        }, 3000);
                      </script>';
            } else {
                $errors['general'] = "Erreur lors de la création du compte. Veuillez réessayer.";
            }
        } catch (Exception $e) {
            $errors['general'] = "Erreur lors de la création du compte. Veuillez réessayer.";
            // En production, logger l'erreur : error_log($e->getMessage());
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
                            <input type="password"
                                class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                id="password"
                                name="password"
                                placeholder="Votre mot de passe"
                                required
                                minlength="6">
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['password']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">
                                Minimum 6 caractères avec au moins une lettre et un chiffre
                            </div>
                        </div>

                        <!-- CONFIRMATION MOT DE PASSE -->
                        <div class="mb-4">
                            <label for="password_confirm" class="form-label">
                                <i class="fas fa-lock"></i> Confirmer le mot de passe *
                            </label>
                            <input type="password"
                                class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                                id="password_confirm"
                                name="password_confirm"
                                placeholder="Confirmer votre mot de passe"
                                required>
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

<?php
/*
================================================
NOTES DE DÉVELOPPEMENT:

1. FONCTIONNALITÉS IMPLÉMENTÉES:
   ✅ Formulaire complet avec tous les champs requis
   ✅ Validation côté serveur sécurisée
   ✅ Vérification email et pseudo uniques
   ✅ Hachage sécurisé du mot de passe
   ✅ Attribution automatique de 20 crédits
   ✅ Messages d'erreur précis et utiles
   ✅ Redirection vers login après succès
   ✅ JavaScript pour validation temps réel

2. SÉCURITÉ:
   ✅ Protection injection SQL (requêtes préparées)
   ✅ Échappement des données (htmlspecialchars)
   ✅ Validation rigoureuse des données
   ✅ Hachage BCRYPT pour mots de passe
   ✅ Nettoyage des entrées utilisateur

3. UX/UI:
   ✅ Messages d'erreur précis selon demande
   ✅ Conservation données en cas d'erreur
   ✅ Feedback visuel Bootstrap
   ✅ Design cohérent avec la charte EcoRide

4. INTÉGRATION:
   ✅ Compatible avec le router existant
   ✅ Utilise config/database.php
   ✅ Prêt pour auth.css
   ✅ Liens vers login.php

PROCHAINE ÉTAPE: Créer includes/session.php pour gérer les sessions
================================================
*/
?>