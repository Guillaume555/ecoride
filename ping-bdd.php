<?php

/**
 * ==============================================
 * SCRIPT : ping-bdd.php
 * UTILITÉ : Empêche Aiven (base MySQL gratuite) de se désactiver
 * ==============================================
 * 
 * CONTEXTE TECHNIQUE :
 * - Le projet EcoRide utilise :
 *   • Aiven comme hébergeur de base de données MySQL (plan gratuit, qui coupe les services inactifs)
 *   • Render comme hébergeur web pour le site
 *   • DBeaver comme client SQL pour administrer la base
 *   • UptimeRobot pour effectuer un ping automatique régulier sur ce fichier
 * 
 * - Ce script est appelé toutes les 5 à 10 minutes par UptimeRobot
 * - Il établit une connexion sécurisée à la base MySQL hébergée sur Aiven
 * - Il exécute une requête simple `SELECT 1` pour que le service reste actif
 */

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connexion réussie à la base de données.";
} catch (PDOException $e) {
    echo "Erreur ping : " . $e->getMessage();
}
