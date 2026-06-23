<?php

/**
 * EcoRide - Page de réinitialisation mot de passe
 * Permet de saisir un nouveau mot de passe avec token valide
 */

// Variables pour template
$page_title = "Nouveau mot de passe - EcoRide";

require_once 'config/PasswordResetService.php';
require_once 'config/mongodb.php';

$errors = [];
$success = '';
$token = $_GET['token'] ?? '';
$tokenValid = false;
$email = '';

// Validation du token au chargement de la page
if (empty($token)) {
    $errors[] = "Token de réinitialisation manquant.";
} else {
    try {
        $resetService = new PasswordResetService();
        $tokenValidation = $resetService->validateResetToken($token);

        if ($tokenValidation['valid']) {
            $tokenValid = true;
            $email = $tokenValidation['email'];
        } else {
            $errors[] = $tokenValidation['message'];
        }
    } catch (Exception $e) {
        error_log("Reset token validation error: " . $e->getMessage());
        $errors[] = "Erreur de validation du token.";
    }
}

// Traitement du formulaire de réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {

    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['password_confirm'] ?? '';

    if (empty($newPassword)) {
        $errors[] = "Le nouveau mot de passe est obligatoire.";
    } elseif (empty($confirmPassword)) {
        $errors[] = "La confirmation du mot de passe est obligatoire.";
    } else {
        try {
            $resetService = new PasswordResetService();
            $result = $resetService->resetPassword($token, $newPassword, $confirmPassword);

            if ($result['success']) {
                $success = $result['message'];

                // Log MongoDB
                if (function_exists('logUserActivity')) {
                    logUserActivity(0, 'password_reset_completed', [
                        'email' => $email,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                }

                // Redirection différée vers login
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "?page=login&reset=success";
                    }, 3000);
                </script>';
            } else {
                $errors[] = $result['message'];
            }
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            $errors[] = "Erreur lors de la réinitialisation. Veuillez réessayer.";
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
                            <i class="fas fa-lock"></i>
                        </div>
                        <h2 class="auth-title">Nouveau mot de passe</h2>
                        <?php if ($tokenValid): ?>
                            <p class="text-muted">
                                Créez un nouveau mot de passe pour <strong><?= htmlspecialchars($email) ?></strong>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Messages d'erreur -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="text-center mt-3">
                                <a href="?page=forgot-password" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-redo"></i> Demander un nouveau lien
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Message de succès -->
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong><?= htmlspecialchars($success) ?></strong>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Redirection automatique vers la page de connexion...
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire de réinitialisation -->
                    <?php if ($tokenValid && empty($success)): ?>
                        <form method="POST" id="resetPasswordForm">

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock text-success"></i> Nouveau mot de passe
                                </label>
                                <input type="password"
                                    class="form-control form-control-lg"
                                    id="password"
                                    name="password"
                                    placeholder="Votre nouveau mot de passe"
                                    required
                                    minlength="8"
                                    autofocus>
                                <div class="form-text">
                                    Au moins 8 caractères avec lettres, chiffres et caractères spéciaux
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password_confirm" class="form-label">
                                    <i class="fas fa-lock text-success"></i> Confirmer le mot de passe
                                </label>
                                <input type="password"
                                    class="form-control form-control-lg"
                                    id="password_confirm"
                                    name="password_confirm"
                                    placeholder="Confirmez votre nouveau mot de passe"
                                    required
                                    minlength="8">
                                <div class="invalid-feedback" id="passwordMismatch" style="display: none;">
                                    Les mots de passe ne correspondent pas.
                                </div>
                            </div>

                            <div class="d-grid mb-4">
                                <button type="submit" name="reset_password" class="btn btn-success btn-lg" id="submitBtn">
                                    <span class="btn-text">
                                        <i class="fas fa-save"></i> Modifier le mot de passe
                                    </span>
                                    <span class="btn-loading d-none">
                                        <i class="fas fa-spinner fa-spin"></i> Modification en cours...
                                    </span>
                                </button>
                            </div>

                            <!-- Indicateur de force mot de passe -->
                            <div class="progress mb-3" style="height: 5px;">
                                <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted" id="passwordStrengthText">Tapez votre mot de passe pour voir sa force</small>

                        </form>
                    <?php endif; ?>

                    <!-- Actions alternatives -->
                    <div class="text-center mt-4">
                        <a href="?page=login" class="text-muted">
                            <i class="fas fa-arrow-left"></i> Retour à la connexion
                        </a>

                        <?php if (!$tokenValid): ?>
                            <span class="mx-3 text-muted">|</span>
                            <a href="?page=forgot-password" class="text-warning">
                                <i class="fas fa-key"></i> Nouveau lien de réinitialisation
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('resetPasswordForm');
        const password = document.getElementById('password');
        const passwordConfirm = document.getElementById('password_confirm');
        const submitBtn = document.getElementById('submitBtn');
        const strengthBar = document.getElementById('passwordStrength');
        const strengthText = document.getElementById('passwordStrengthText');
        const mismatchMsg = document.getElementById('passwordMismatch');

        if (password && passwordConfirm && submitBtn) {
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');

            // Validation en temps réel des mots de passe
            function validatePasswords() {
                const pwd = password.value;
                const confirm = passwordConfirm.value;

                // Vérification correspondance
                if (confirm && pwd !== confirm) {
                    passwordConfirm.classList.add('is-invalid');
                    mismatchMsg.style.display = 'block';
                    return false;
                } else {
                    passwordConfirm.classList.remove('is-invalid');
                    mismatchMsg.style.display = 'none';
                    return true;
                }
            }

            // Indicateur de force du mot de passe
            password.addEventListener('input', function() {
                const pwd = this.value;
                let strength = 0;
                let text = '';

                if (pwd.length >= 8) strength += 25;
                if (pwd.match(/[a-z]/)) strength += 25;
                if (pwd.match(/[A-Z]/)) strength += 25;
                if (pwd.match(/[0-9]/)) strength += 15;
                if (pwd.match(/[^a-zA-Z0-9]/)) strength += 10;

                if (strength < 25) {
                    strengthBar.className = 'progress-bar bg-danger';
                    text = 'Mot de passe faible';
                } else if (strength < 50) {
                    strengthBar.className = 'progress-bar bg-warning';
                    text = 'Mot de passe moyen';
                } else if (strength < 75) {
                    strengthBar.className = 'progress-bar bg-info';
                    text = 'Mot de passe bon';
                } else {
                    strengthBar.className = 'progress-bar bg-success';
                    text = 'Mot de passe fort';
                }

                strengthBar.style.width = strength + '%';
                strengthText.textContent = text;
            });

            passwordConfirm.addEventListener('input', validatePasswords);

            // Validation au submit
            form.addEventListener('submit', function(e) {
                if (!validatePasswords()) {
                    e.preventDefault();
                    return false;
                }

                const pwd = password.value;
                if (pwd.length < 8) {
                    e.preventDefault();
                    alert('Le mot de passe doit contenir au moins 8 caractères.');
                    return false;
                }

                // Animation loading
                if (btnText && btnLoading) {
                    btnText.classList.add('d-none');
                    btnLoading.classList.remove('d-none');
                    submitBtn.disabled = true;
                }
            });
        }
    });
</script>