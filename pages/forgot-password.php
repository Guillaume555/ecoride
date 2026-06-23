<?php

/**
 * EcoRide - Page "Mot de passe oublié"
 * Demande de réinitialisation par email
 */

// Variables pour template
$page_title = "Mot de passe oublié - EcoRide";

require_once 'config/PasswordResetService.php';
require_once 'config/mongodb.php';

$errors = [];
$success = '';
$email = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    // Validation basique
    if (empty($email)) {
        $errors[] = "L'adresse email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide.";
    } else {
        try {
            $resetService = new PasswordResetService();

            $result = $resetService->requestPasswordReset(
                $email,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );

            if ($result['success']) {
                $success = $result['message'];

                // Log MongoDB AVANT de vider $email
                if (function_exists('logUserActivity')) {
                    logUserActivity(0, 'password_reset_request', [
                        'email' => $email,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                }

                $email = ''; // Reset champ APRÈS le log

            } else {
                $errors[] = $result['message'];
            }
        } catch (Exception $e) {
            error_log("Forgot password error: " . $e->getMessage());
            $errors[] = "Erreur système. Veuillez réessayer plus tard.";
        }
    }
}
?>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">

                <!-- Card principale -->
                <div class="auth-card">

                    <!-- Header -->
                    <div class="text-center mb-4">
                        <div class="auth-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <h2 class="auth-title">Mot de passe oublié ?</h2>
                        <p class="text-muted">
                            Saisissez votre adresse email pour recevoir un lien de réinitialisation
                        </p>
                    </div>

                    <!-- Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
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
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Vérifiez votre boîte email (y compris les spams)
                                </small>
                            </div>
                        </div>

                        <!-- Actions après succès -->
                        <div class="text-center mt-4">
                            <a href="?page=login" class="btn btn-outline-success">
                                <i class="fas fa-arrow-left"></i> Retour à la connexion
                            </a>
                        </div>

                    <?php else: ?>

                        <!-- Formulaire de demande -->
                        <form method="POST" id="forgotPasswordForm">

                            <div class="mb-4">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope text-success"></i> Adresse email
                                </label>
                                <input type="email"
                                    class="form-control form-control-lg"
                                    id="email"
                                    name="email"
                                    value="<?= htmlspecialchars($email) ?>"
                                    placeholder="votre.email@exemple.com"
                                    required
                                    autofocus>
                                <div class="form-text">
                                    L'email associé à votre compte EcoRide
                                </div>
                            </div>

                            <div class="d-grid mb-4">
                                <button type="submit" name="reset_request" value="1" class="btn btn-success btn-lg">
                                    <span class="btn-text">
                                        <i class="fas fa-paper-plane"></i> Envoyer le lien
                                    </span>
                                    <span class="btn-loading d-none">
                                        <i class="fas fa-spinner fa-spin"></i> Envoi en cours...
                                    </span>
                                </button>
                            </div>

                            <!-- Informations sécurité -->
                            <div class="bg-light p-3 rounded">
                                <h6 class="text-secondary mb-2">
                                    <i class="fas fa-shield-alt"></i> Sécurité
                                </h6>
                                <small class="text-muted">
                                    • Le lien sera valide pendant <strong>1 heure</strong> uniquement<br>
                                    • Il ne peut être utilisé qu'<strong>une seule fois</strong><br>
                                    • Si vous ne recevez pas l'email, vérifiez vos spams
                                </small>
                            </div>

                        </form>

                    <?php endif; ?>

                    <!-- Actions alternatives -->
                    <div class="text-center mt-4">
                        <a href="?page=login" class="text-muted">
                            <i class="fas fa-arrow-left"></i> Retour à la connexion
                        </a>

                        <span class="mx-3 text-muted">|</span>

                        <a href="?page=register" class="text-success">
                            <i class="fas fa-user-plus"></i> Créer un compte
                        </a>
                    </div>

                    <!-- Aide -->
                    <div class="mt-4 p-3 bg-warning bg-opacity-10 rounded">
                        <h6 class="text-warning mb-2">
                            <i class="fas fa-question-circle"></i> Problème persistant ?
                        </h6>
                        <small class="text-muted">
                            Si vous ne recevez toujours pas l'email après plusieurs minutes,
                            <a href="?page=contact" class="text-decoration-none">contactez notre support</a>.
                        </small>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>