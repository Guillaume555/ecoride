<?php

// Fichier de configuration pour la base de données
// Contient la connexion PDO et des fonctions utiles pour le projet

// Configuration pour production (Aiven) et local (Laragon)
if (getenv('DB_HOST')) {
    // Production (Render + Aiven)
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_PORT', getenv('DB_PORT'));
    define('DB_NAME', getenv('DB_NAME'));
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASS', getenv('DB_PASS'));
    $ssl_required = getenv('DB_SSL') === 'true';
} else {
    // Local (Laragon)
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'ecoride');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    $ssl_required = false;
}

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    if ($ssl_required) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Message de confirmation (utile pendant le dev, à commenter en prod)
    // echo "Connexion à la base réussie";
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// ---------------------------------------------------------
// Fonctions utiles appelables dans d'autres parties du site
// ---------------------------------------------------------

/**
 * Recherche des trajets selon ville de départ, d’arrivée et date (facultative)
 */
function searchTrips($departure, $arrival, $date = null)
{
    global $pdo;

    $sql = "SELECT t.*, u.username AS driver_name, v.brand, v.model, v.fuel_type
            FROM trips t
            JOIN users u ON t.driver_id = u.id
            JOIN vehicles v ON t.vehicle_id = v.id
            WHERE t.departure_city LIKE :departure
              AND t.arrival_city LIKE :arrival
              AND t.status = 'active'
              AND t.available_seats > 0
              AND t.departure_time > NOW()"; // ← ne propose plus les dates qui sont déjà passées.


    $params = [
        ':departure' => "%$departure%",
        ':arrival'   => "%$arrival%"
    ];

    if ($date) {
        $sql .= " AND DATE(t.departure_time) = :date";
        $params[':date'] = $date;
    }

    $sql .= " ORDER BY t.departure_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Retourne des statistiques globales pour la page d’accueil
 */
function getStatistics()
{
    global $pdo;

    $stats = [];

    // Nombre d'utilisateurs actifs
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
    $stats['users'] = $stmt->fetchColumn();

    // Nombre de trajets en ligne
    $stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'active'");
    $stats['trips'] = $stmt->fetchColumn();

    // Moyenne des notes validées
    $stmt = $pdo->query("SELECT AVG(rating) FROM reviews WHERE is_validated = 1");
    $stats['average_rating'] = round($stmt->fetchColumn(), 1);

    return $stats;
}

/**
 * Vérifie si un utilisateur existe selon son email
 */
function userExists($email)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);

    return $stmt->fetchColumn() > 0;
}

/**
 * Teste la connexion à la base et affiche un message de résultat
 * À utiliser pendant le développement (page test.php par exemple)
 */
function testConnection()
{
    global $pdo;

    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
        $result = $stmt->fetch();

        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px;'>";
        echo "<strong>Connexion réussie.</strong><br>";
        echo "Utilisateurs dans la base : " . $result['total'] . "<br>";
        echo "Base utilisée : " . DB_NAME;
        echo "</div>";

        return true;
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px;'>";
        echo "<strong>Échec de la connexion :</strong><br>";
        echo $e->getMessage();
        echo "</div>";

        return false;
    }
}

/*
Exemples d'utilisation dans les pages PHP :

require_once 'config/database.php';

$trips = searchTrips('Paris', 'Lyon', '2025-07-15');
$stats = getStatistics();
echo "Nombre d'utilisateurs : " . $stats['users'];
*/
