<?php
/*
================================================
FICHIER: pages/contact.php - Contact EcoRide
Description: Page de contact avec formulaire et envoi email PHPMailer
================================================
*/

// Configuration de la page
$page_title = "EcoRide - Contact";
$extra_css = ['contact.css']; // Utilise style.css + home.css par défaut
$extra_js = ['form-validation.js']; //Js spécifique a la page

// Inclure le service email
require_once 'config/EmailService.php';
require_once 'config/mongodb.php';

// Variables pour le formulaire
$success_message = '';
$error_message = '';

// Traitement du formulaire de contact
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation simple
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format d'email invalide.";
    } elseif (strlen($message) < 10) {
        $error_message = "Le message doit contenir au moins 10 caractères.";
    } else {
        // Envoi email avec PHPMailer
        try {
            $emailService = new EmailService();

            // Conversion du sujet sélectionné
            $subjectText = match ($subject) {
                'question' => 'Question générale',
                'support' => 'Support technique',
                'suggestion' => 'Suggestion d\'amélioration',
                'partenariat' => 'Partenariat',
                'autre' => 'Autre demande',
                default => $subject
            };

            // Envoi email principal
            $result = $emailService->sendContactEmail($name, $email, $subjectText, $message);

            if ($result['success']) {
                // Envoie email de confirmation à l'utilisateur
                $emailService->sendContactConfirmation($email, $name);

                // Log MongoDB
                if (function_exists('logUserActivity')) {
                    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
                    logUserActivity($userId, 'contact_form', [
                        'name' => $name,
                        'email' => $email,
                        'subject' => $subjectText,
                        'has_account' => isset($_SESSION['user_id'])
                    ]);
                }

                $success_message = "Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais. Un email de confirmation vous a été envoyé.";

                // Reset des champs après succès
                $name = $email = $subject = $message = '';
            } else {
                $error_message = $result['message'];
            }
        } catch (Exception $e) {
            error_log("Erreur contact form: " . $e->getMessage());
            $error_message = "Erreur lors de l'envoi du message. Veuillez réessayer plus tard.";
        }
    }
}
?>

<!-- HERO SECTION CONTACT -->
<section class="contact-hero">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="hero-title">Contactez-nous</h1>
                <p class="fs-5 mb-4 opacity-75">
                    Une question ? Une suggestion ? Notre équipe est là pour vous aider
                </p>
            </div>
        </div>
    </div>
</section>

<!-- SECTION CONTACT -->
<section class="contact-section">
    <div class="container">
        <div class="row">

            <!-- FORMULAIRE DE CONTACT -->
            <div class="col-lg-8">
                <div class="contact-form-card">
                    <h3 class="contact-form-title">
                        <i class="fas fa-envelope text-success"></i>
                        Envoyez-nous un message
                    </h3>

                    <!-- Messages -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($success_message) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire -->
                    <form method="POST" class="contact-form" id="contactForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-user"></i> Nom complet *
                                    </label>
                                    <input type="text"
                                        class="form-control contact-input"
                                        id="name"
                                        name="name"
                                        value="<?= htmlspecialchars($name ?? '') ?>"
                                        placeholder="Votre nom et prénom"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i> Email *
                                    </label>
                                    <input type="email"
                                        class="form-control contact-input"
                                        id="email"
                                        name="email"
                                        value="<?= htmlspecialchars($email ?? '') ?>"
                                        placeholder="votre.email@exemple.com"
                                        required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">
                                <i class="fas fa-tag"></i> Sujet *
                            </label>
                            <select class="form-select contact-input" id="subject" name="subject" required>
                                <option value="">Choisissez un sujet</option>
                                <option value="question" <?= (($subject ?? '') === 'question') ? 'selected' : '' ?>>
                                    Question générale
                                </option>
                                <option value="support" <?= (($subject ?? '') === 'support') ? 'selected' : '' ?>>
                                    Support technique
                                </option>
                                <option value="suggestion" <?= (($subject ?? '') === 'suggestion') ? 'selected' : '' ?>>
                                    Suggestion d'amélioration
                                </option>
                                <option value="partenariat" <?= (($subject ?? '') === 'partenariat') ? 'selected' : '' ?>>
                                    Partenariat
                                </option>
                                <option value="autre" <?= (($subject ?? '') === 'autre') ? 'selected' : '' ?>>
                                    Autre
                                </option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="message" class="form-label">
                                <i class="fas fa-comment"></i> Message *
                            </label>
                            <textarea class="form-control contact-input"
                                id="message"
                                name="message"
                                rows="6"
                                placeholder="Décrivez votre demande en détail... (minimum 10 caractères)"
                                required><?= htmlspecialchars($message ?? '') ?></textarea>
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Soyez précis dans votre demande pour une réponse adaptée
                                </small>
                            </div>
                        </div>

                        <!-- Note RGPD -->
                        <div class="mb-4">
                            <div class="alert alert-light border">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt text-primary"></i>
                                    <strong>Protection des données :</strong> Vos informations sont utilisées uniquement pour traiter votre demande et ne sont pas transmises à des tiers.
                                </small>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-contact-primary" id="submitBtn">
                            <i class="fas fa-paper-plane"></i>
                            <span class="btn-text">Envoyer le message</span>
                            <span class="btn-loading d-none">
                                <i class="fas fa-spinner fa-spin"></i> Envoi en cours...
                            </span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- INFORMATIONS CONTACT -->
            <div class="col-lg-4">
                <div class="contact-info-card">
                    <h4 class="contact-info-title">
                        <i class="fas fa-info-circle text-primary"></i>
                        Informations de contact
                    </h4>

                    <div class="contact-info-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h6>Email</h6>
                            <p><a href="mailto:siteweb5555@gmail.com">siteweb5555@gmail.com</a></p>
                        </div>
                    </div>

                    <div class="contact-info-item">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-details">
                            <h6>Horaires de support</h6>
                            <p>Lundi - Vendredi<br>9h00 - 18h00</p>
                        </div>
                    </div>

                    <div class="contact-info-item">
                        <div class="contact-icon">
                            <i class="fas fa-reply"></i>
                        </div>
                        <div class="contact-details">
                            <h6>Temps de réponse</h6>
                            <p>Moins de 24h en moyenne</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ RAPIDE AVEC ACCORDÉON -->
                <div class="contact-faq-card">
                    <h5 class="faq-title">
                        <i class="fas fa-question-circle text-warning"></i>
                        Questions fréquentes
                    </h5>

                    <div class="accordion accordion-flush" id="contactFaqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq1"
                                    aria-expanded="false" aria-controls="faq1">
                                    <!-- <i class="fas fa-user-plus me-2"></i> -->
                                    Comment créer un compte ?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse"
                                data-bs-parent="#contactFaqAccordion">
                                <div class="accordion-body">
                                    Cliquez sur "Inscription" en haut de page et suivez les étapes.
                                    Vous recevrez automatiquement 20 crédits gratuits pour commencer !
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq2"
                                    aria-expanded="false" aria-controls="faq2">
                                    <!-- <i class="fas fa-search me-2"></i> -->
                                    Comment réserver un trajet ?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse"
                                data-bs-parent="#contactFaqAccordion">
                                <div class="accordion-body">
                                    Utilisez la barre de recherche pour trouver votre trajet,
                                    cliquez sur "Voir détail" puis "Réserver".
                                    Vos crédits seront automatiquement déduits.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq3"
                                    aria-expanded="false" aria-controls="faq3">
                                    <!-- <i class="fas fa-plus-circle me-2"></i> -->
                                    Comment proposer un trajet ?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse"
                                data-bs-parent="#contactFaqAccordion">
                                <div class="accordion-body">
                                    Connectez-vous, ajoutez un véhicule depuis votre profil si nécessaire,
                                    puis cliquez sur "Proposer un trajet" dans le menu utilisateur.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq4"
                                    aria-expanded="false" aria-controls="faq4">
                                    <!-- <i class="fas fa-times-circle me-2"></i> -->
                                    Comment annuler une réservation ?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse"
                                data-bs-parent="#contactFaqAccordion">
                                <div class="accordion-body">
                                    Allez dans "Mes trajets" depuis votre profil.
                                    Vous pouvez annuler jusqu'à 2h avant le départ.
                                    Vos crédits seront automatiquement remboursés.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq5"
                                    aria-expanded="false" aria-controls="faq5">
                                    <!-- <i class="fas fa-exclamation-triangle me-2"></i> -->
                                    Que faire en cas de problème ?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse"
                                data-bs-parent="#contactFaqAccordion">
                                <div class="accordion-body">
                                    Contactez-nous immédiatement via ce formulaire en précisant
                                    le type de problème. Nous répondons sous 24h et priorité
                                    aux urgences de sécurité.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>