<?php
require_once 'config/EmailService.php';

echo "Test de configuration email...<br>";

try {
    $emailService = new EmailService();
    echo "EmailService créé avec succès<br>";

    $result = $emailService->testConfiguration();
    if ($result['success']) {
        echo "Configuration SMTP OK<br>";
    } else {
        echo "Erreur configuration: " . $result['message'] . "<br>";
    }
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}
