<?php
/*
================================================
FICHIER: pages/contact.php - Contact EcoRide
Description: Page de contact avec formulaire
================================================
*/

// Configuration de la page
$page_title = "EcoRide - Contact";
$extra_css = ['contact.css']; // Utilise style.css + home.css par défaut
$extra_js = ['form-validation.js']; //Js spécifique a la page


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
    } else {
        // Simulation envoi email (en production : mail() ou service email)
        $success_message = "Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.";

        // Reset des champs après succès
        $name = $email = $subject = $message = '';
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
                    <form method="POST" class="contact-form">
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
                                placeholder="Décrivez votre demande en détail..."
                                required><?= htmlspecialchars($message ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-contact-primary">
                            <i class="fas fa-paper-plane"></i>
                            Envoyer le message
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
                            <p><a href="mailto:contact@ecoride.fr">contact@ecoride.fr</a></p>
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

                <!-- FAQ RAPIDE -->
                <div class="contact-faq-card">
                    <h5 class="faq-title">
                        <i class="fas fa-question-circle text-warning"></i>
                        Questions fréquentes
                    </h5>

                    <div class="faq-item">
                        <strong>Comment créer un compte ?</strong>
                        <p>Cliquez sur "Inscription" et suivez les étapes. Vous recevrez 20 crédits gratuits !</p>
                    </div>

                    <div class="faq-item">
                        <strong>Comment réserver un trajet ?</strong>
                        <p>Recherchez votre trajet, cliquez sur "Voir détail" puis "Réserver".</p>
                    </div>

                    <div class="faq-item">
                        <strong>Que faire en cas de problème ?</strong>
                        <p>Contactez-nous immédiatement via ce formulaire ou par email.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>