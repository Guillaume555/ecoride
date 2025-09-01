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

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// 1) En DEV : charger .env si le fichier existe (safeLoad = silencieux si absent)
$envPath = __DIR__ . '/.env';
if (is_file($envPath)) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

// 2) Lire les variables depuis l'environnement (Render ou .env local)
function env(string $key, ?string $default = null): ?string
{
    $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : $v;
}

$host = env('DB_HOST');
$port = env('DB_PORT', '3306');
$db   = env('DB_NAME');
$user = env('DB_USER');
$pass = env('DB_PASS');

// 3) Validation minimale
if (!$host || !$db || !$user) {
    http_response_code(500);
    echo "Config manquante: vérifiez DB_HOST, DB_NAME, DB_USER (Render → Environment).";
    exit;
}

// 4) Connexion + ping
try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Ping simple
    $pdo->query('SELECT 1');
    header('Content-Type: text/plain; charset=utf-8');
    echo "OK " . date('Y-m-d H:i:s');
} catch (PDOException $e) {
    http_response_code(500);
    echo "Erreur ping: " . $e->getMessage();
}
