<?php

/**
 * EcoRide - Service d'envoi d'emails
 * Utilise PHPMailer avec SMTP sécurisé
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mailer;
    private $fromEmail;
    private $fromName;
    private $contactEmail;

    public function __construct()
    {
        $this->loadEnvConfig();
        $this->initializeMailer();
    }

    /**
     * Charge la configuration depuis .env
     */
    private function loadEnvConfig()
    {
        // Charger vlucas/phpdotenv si pas déjà fait
        if (class_exists('Dotenv\Dotenv')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->safeLoad();
        }

        $this->fromEmail = $_ENV['SMTP_USERNAME'] ?? 'noreply@ecoride.com';
        $this->fromName = $_ENV['CONTACT_FROM_NAME'] ?? 'EcoRide';
        $this->contactEmail = $_ENV['CONTACT_EMAIL'] ?? 'siteweb5555@gmail.com';
    }

    /**
     * Initialise PHPMailer avec configuration SMTP
     */
    private function initializeMailer()
    {
        $this->mailer = new PHPMailer(true);

        try {
            // Configuration serveur SMTP
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['SMTP_USERNAME'] ?? '';
            $this->mailer->Password = $_ENV['SMTP_PASSWORD'] ?? '';
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $_ENV['SMTP_PORT'] ?? 587;

            // Configuration par défaut
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->isHTML(true);
        } catch (Exception $e) {
            error_log("Erreur configuration PHPMailer: " . $e->getMessage());
        }
    }

    /**
     * Envoie un email de contact depuis le formulaire
     */
    public function sendContactEmail($name, $email, $subject, $message, $phone = null)
    {
        try {
            // Reset du mailer pour nouvel email
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Destinataire
            $this->mailer->addAddress($this->contactEmail);

            // Reply-To vers l'expéditeur
            $this->mailer->addReplyTo($email, $name);

            // Sujet
            $this->mailer->Subject = "[EcoRide Contact] " . $subject;

            // Corps du message HTML
            $htmlBody = $this->buildContactEmailTemplate($name, $email, $subject, $message, $phone);
            $this->mailer->Body = $htmlBody;

            // Version texte brut
            $this->mailer->AltBody = $this->buildContactEmailText($name, $email, $subject, $message, $phone);

            // Envoi
            $result = $this->mailer->send();

            return [
                'success' => true,
                'message' => 'Email envoyé avec succès !'
            ];
        } catch (Exception $e) {
            error_log("Erreur envoi email contact: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi. Veuillez réessayer plus tard.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Template HTML pour email de contact
     */
    private function buildContactEmailTemplate($name, $email, $subject, $message, $phone)
    {
        $phoneSection = $phone ? "<p><strong>Téléphone :</strong> " . htmlspecialchars($phone) . "</p>" : "";

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Nouveau message de contact - EcoRide</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4B6B52; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f8f9fa; padding: 20px; border: 1px solid #ddd; border-top: none; }
                .message-box { background: white; padding: 20px; border-radius: 5px; margin: 15px 0; }
                .footer { text-align: center; padding: 15px; color: #666; font-size: 0.9em; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🌿 Nouveau message de contact EcoRide</h2>
                </div>
                
                <div class='content'>
                    <h3>Détails du contact</h3>
                    <p><strong>Nom :</strong> " . htmlspecialchars($name) . "</p>
                    <p><strong>Email :</strong> " . htmlspecialchars($email) . "</p>
                    {$phoneSection}
                    <p><strong>Sujet :</strong> " . htmlspecialchars($subject) . "</p>
                    <p><strong>Date :</strong> " . date('d/m/Y à H:i') . "</p>
                    
                    <div class='message-box'>
                        <h4>Message :</h4>
                        <p>" . nl2br(htmlspecialchars($message)) . "</p>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>Email automatique envoyé depuis le formulaire de contact d'EcoRide</p>
                    <p>Pour répondre, utilisez directement l'email : " . htmlspecialchars($email) . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Version texte brut pour email de contact
     */
    private function buildContactEmailText($name, $email, $subject, $message, $phone)
    {
        $phoneSection = $phone ? "Téléphone : " . $phone . "\n" : "";

        return "
NOUVEAU MESSAGE DE CONTACT ECORIDE
===================================

Détails du contact :
- Nom : {$name}
- Email : {$email}
{$phoneSection}- Sujet : {$subject}
- Date : " . date('d/m/Y à H:i') . "

MESSAGE :
---------
{$message}

---
Email automatique depuis EcoRide
Pour répondre : {$email}
        ";
    }

    /**
     * Test de la configuration email
     */
    public function testConfiguration()
    {
        try {
            // Test de connexion SMTP
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();

            return [
                'success' => true,
                'message' => 'Configuration email fonctionnelle'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur configuration : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Envoie un email de confirmation à l'utilisateur
     */
    public function sendContactConfirmation($userEmail, $userName)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($userEmail, $userName);

            $this->mailer->Subject = "Confirmation de votre message - EcoRide";

            $this->mailer->Body = "
            <!DOCTYPE html>
            <html>
            <head><meta charset='UTF-8'><title>Confirmation - EcoRide</title></head>
            <body style='font-family: Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #4B6B52;'>🌿 Merci pour votre message !</h2>
                    
                    <p>Bonjour <strong>" . htmlspecialchars($userName) . "</strong>,</p>
                    
                    <p>Nous avons bien reçu votre message via le formulaire de contact d'EcoRide.</p>
                    
                    <p>Notre équipe vous répondra dans les plus brefs délais, généralement sous 24-48 heures.</p>
                    
                    <p>En attendant, n'hésitez pas à découvrir nos trajets disponibles sur <a href='#' style='color: #4B6B52;'>EcoRide</a>.</p>
                    
                    <hr style='margin: 20px 0;'>
                    <p style='color: #666; font-size: 0.9em;'>
                        Ceci est un email automatique. Merci de ne pas y répondre directement.
                    </p>
                </div>
            </body>
            </html>
            ";

            $this->mailer->AltBody = "Bonjour {$userName},\n\nNous avons bien reçu votre message. Notre équipe vous répondra sous 24-48h.\n\nMerci,\nL'équipe EcoRide";

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur envoi confirmation: " . $e->getMessage());
            return false;
        }
    }
}
