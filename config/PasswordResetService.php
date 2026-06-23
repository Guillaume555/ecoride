<?php

/**
 * EcoRide - Service de récupération de mot de passe
 * Sécurité maximale : tokens cryptographiquement sûrs, expiration, usage unique
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/EmailService.php';

class PasswordResetService
{
    private $pdo;
    private $emailService;

    // Constantes de sécurité
    private const TOKEN_EXPIRY_HOURS = 1;           // Expiration 1 heure
    private const TOKEN_LENGTH = 64;                // Longueur token (256 bits)
    private const MAX_ATTEMPTS_PER_HOUR = 3;        // Max 3 demandes/heure par IP
    private const MAX_ATTEMPTS_PER_EMAIL = 5;       // Max 5 demandes/heure par email

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->emailService = new EmailService();

        // Nettoyage automatique des tokens expirés
        $this->cleanupExpiredTokens();
    }

    /**
     * Demande de réinitialisation mot de passe
     * Sécurité : Rate limiting + validation email + token cryptographique
     */
    public function requestPasswordReset($email, $ipAddress = null, $userAgent = null)
    {
        try {
            // Nettoyage et validation email
            $email = strtolower(trim($email));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Format d\'email invalide.'
                ];
            }

            // Vérification que l'email existe dans la base
            if (!$this->emailExists($email)) {
                // SÉCURITÉ : Ne pas révéler si l'email existe ou non
                // Retourner succès même si email inexistant (contre énumération)
                return [
                    'success' => true,
                    'message' => 'Si cet email est enregistré, vous recevrez un lien de réinitialisation.'
                ];
            }

            // Rate limiting par IP
            if ($ipAddress && !$this->checkRateLimitIP($ipAddress)) {
                return [
                    'success' => false,
                    'message' => 'Trop de tentatives depuis cette adresse IP. Réessayez dans 1 heure.'
                ];
            }

            // Rate limiting par email
            if (!$this->checkRateLimitEmail($email)) {
                return [
                    'success' => false,
                    'message' => 'Trop de demandes pour cet email. Réessayez dans 1 heure.'
                ];
            }

            // Invalidation des anciens tokens pour cet email
            $this->invalidateExistingTokens($email);

            // Génération token cryptographiquement sûr
            $token = $this->generateSecureToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_HOURS . ' hours'));

            // Stockage du token en base
            $stmt = $this->pdo->prepare("
                INSERT INTO password_resets (email, token, expires_at, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $email,
                hash('sha256', $token), // Stocker hash du token, pas le token brut
                $expiresAt,
                $ipAddress ?: $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $userAgent ?: $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            // Envoi email avec token
            $emailResult = $this->sendResetEmail($email, $token);

            if ($emailResult['success']) {
                return [
                    'success' => true,
                    'message' => 'Un lien de réinitialisation a été envoyé à votre adresse email.'
                ];
            } else {
                // Supprimer le token si email échoue
                $this->invalidateToken($token);

                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.'
                ];
            }
        } catch (Exception $e) {
            error_log("Password reset request error: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'ERREUR DEBUG: ' . $e->getMessage() . ' (Ligne: ' . $e->getLine() . ', Fichier: ' . basename($e->getFile()) . ')'
            ];
        }
    }

    /**
     * Validation d'un token de réinitialisation
     * Sécurité : Vérification expiration + usage unique + hash comparison
     */
    public function validateResetToken($token)
    {
        if (empty($token) || strlen($token) !== self::TOKEN_LENGTH) {
            return [
                'valid' => false,
                'message' => 'Token invalide.'
            ];
        }

        try {
            $hashedToken = hash('sha256', $token);

            $stmt = $this->pdo->prepare("
                SELECT email, expires_at, is_used, created_at
                FROM password_resets 
                WHERE token = ? AND is_used = FALSE
            ");

            $stmt->execute([$hashedToken]);
            $reset = $stmt->fetch();

            if (!$reset) {
                return [
                    'valid' => false,
                    'message' => 'Token invalide ou déjà utilisé.'
                ];
            }

            // Vérification expiration
            if (strtotime($reset['expires_at']) <= time()) {
                // Marquer comme utilisé pour éviter réutilisation
                $this->markTokenAsUsed($hashedToken);

                return [
                    'valid' => false,
                    'message' => 'Ce lien a expiré. Demandez un nouveau lien.'
                ];
            }

            return [
                'valid' => true,
                'email' => $reset['email'],
                'expires_in' => strtotime($reset['expires_at']) - time()
            ];
        } catch (Exception $e) {
            error_log("Token validation error: " . $e->getMessage());

            return [
                'valid' => false,
                'message' => 'Erreur de validation du token.'
            ];
        }
    }

    /**
     * Réinitialisation effective du mot de passe
     * Sécurité : Validation token + hashage BCRYPT + invalidation token
     */
    public function resetPassword($token, $newPassword, $confirmPassword)
    {
        // Validation du token
        $tokenValidation = $this->validateResetToken($token);
        if (!$tokenValidation['valid']) {
            return $tokenValidation;
        }

        $email = $tokenValidation['email'];

        // Validation du nouveau mot de passe
        $passwordValidation = $this->validateNewPassword($newPassword, $confirmPassword);
        if (!$passwordValidation['valid']) {
            return $passwordValidation;
        }

        try {
            // Transaction pour garantir cohérence
            $this->pdo->beginTransaction();

            // Mise à jour du mot de passe utilisateur
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET password = ?
                WHERE email = ? AND is_active = TRUE
            ");

            $updateResult = $stmt->execute([$hashedPassword, $email]);

            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Utilisateur introuvable ou compte inactif.'
                ];
            }

            // Marquer le token comme utilisé
            $this->markTokenAsUsed(hash('sha256', $token));

            // Invalider tous les autres tokens pour cet email
            $this->invalidateExistingTokens($email);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Mot de passe modifié avec succès. Vous pouvez maintenant vous connecter.'
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Password reset error: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de la modification. Veuillez réessayer.'
            ];
        }
    }

    /**
     * Génération de token cryptographiquement sûr
     * Utilise random_bytes() pour entropie maximale
     */
    private function generateSecureToken()
    {
        // Génération 32 bytes aléatoires (256 bits)
        $randomBytes = random_bytes(32);

        // Conversion en hexadécimal (64 caractères)
        return bin2hex($randomBytes);
    }

    /**
     * Validation stricte du nouveau mot de passe
     */
    private function validateNewPassword($password, $confirmPassword)
    {
        if ($password !== $confirmPassword) {
            return [
                'valid' => false,
                'message' => 'Les mots de passe ne correspondent pas.'
            ];
        }

        if (strlen($password) < 8) {
            return [
                'valid' => false,
                'message' => 'Le mot de passe doit contenir au moins 8 caractères.'
            ];
        }

        // Vérification complexité (au moins 3 types de caractères)
        $hasLower = preg_match('/[a-z]/', $password);
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);

        $complexity = $hasLower + $hasUpper + $hasNumber + $hasSpecial;

        if ($complexity < 3) {
            return [
                'valid' => false,
                'message' => 'Le mot de passe doit contenir au moins 3 types : minuscules, majuscules, chiffres, caractères spéciaux.'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Vérification rate limiting par IP
     */
    private function checkRateLimitIP($ipAddress)
    {
        // PUIS LE CODE ORIGINAL :
        $stmt = $this->pdo->prepare("
        SELECT COUNT(*) 
        FROM password_resets 
        WHERE ip_address = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");

        $stmt->execute([$ipAddress]);
        $count = $stmt->fetchColumn();

        return $count < self::MAX_ATTEMPTS_PER_HOUR;
    }

    /**
     * Vérification rate limiting par email
     */
    private function checkRateLimitEmail($email)
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM password_resets 
            WHERE email = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");

        $stmt->execute([$email]);
        $count = $stmt->fetchColumn();

        return $count < self::MAX_ATTEMPTS_PER_EMAIL;
    }

    /**
     * Vérification existence email
     */
    private function emailExists($email)
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM users 
            WHERE email = ? AND is_active = TRUE
        ");

        $stmt->execute([$email]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Invalidation des tokens existants pour un email
     */
    private function invalidateExistingTokens($email)
    {
        $stmt = $this->pdo->prepare("
            UPDATE password_resets 
            SET is_used = TRUE, used_at = NOW()
            WHERE email = ? AND is_used = FALSE
        ");

        $stmt->execute([$email]);
    }

    /**
     * Marquer un token comme utilisé
     */
    private function markTokenAsUsed($hashedToken)
    {
        $stmt = $this->pdo->prepare("
            UPDATE password_resets 
            SET is_used = TRUE, used_at = NOW()
            WHERE token = ?
        ");

        $stmt->execute([$hashedToken]);
    }

    /**
     * Invalidation d'un token spécifique
     */
    private function invalidateToken($token)
    {
        $hashedToken = hash('sha256', $token);
        $this->markTokenAsUsed($hashedToken);
    }

    /**
     * Nettoyage automatique des tokens expirés
     */
    private function cleanupExpiredTokens()
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM password_resets 
                WHERE expires_at < NOW() OR created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");

            $stmt->execute();
        } catch (Exception $e) {
            error_log("Token cleanup error: " . $e->getMessage());
        }
    }

    /**
     * Envoi email de réinitialisation
     */
    private function sendResetEmail($email, $token)
    {
        // URL de réinitialisation (à adapter selon votre domaine)
        $resetUrl = $this->getBaseUrl() . "?page=reset-password&token=" . urlencode($token);

        $subject = "Réinitialisation de votre mot de passe EcoRide";

        $message = "
        Bonjour,

        Vous avez demandé la réinitialisation de votre mot de passe sur EcoRide.

        Cliquez sur ce lien pour créer un nouveau mot de passe :
        $resetUrl

        Ce lien est valide pendant " . self::TOKEN_EXPIRY_HOURS . " heure uniquement.

        Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.

        Cordialement,
        L'équipe EcoRide
        ";

        return $this->emailService->sendContactEmail(
            'EcoRide',
            $email,
            $subject,
            $message,
            null  // Ajouter ce paramètre $phone
        );
    }

    /**
     * Obtention URL de base de l'application
     */
    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME'] ?? '');

        return $protocol . '://' . $host . $path;
    }
}
