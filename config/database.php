<?php

/**
 * ========================================
 * CLASSE DATABASE - GESTIONNAIRE DE CONNEXION
 * ========================================
 * VERSION AVEC CHARGEMENT MANUEL .env
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Chargement Dotenv
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
} catch (Exception $e) {
    // Si Dotenv échoue, charger manuellement
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignorer les commentaires
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parser la ligne
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Retirer les guillemets si présents
                $value = trim($value, '"\'');
                
                // Mettre dans l'environnement
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

class Database
{
    private static $pdo = null;

    private static $localConfig = [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'ecoride',
        'user' => 'root',
        'pass' => '',
        'ssl' => false
    ];

    public static function getConnection()
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        try {
            $config = self::detectEnvironment();

            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            if ($config['ssl']) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            self::$pdo = new PDO($dsn, $config['user'], $config['pass'], $options);

            return self::$pdo;
        } catch (PDOException $e) {
            throw new Exception("Impossible de se connecter à la base : " . $e->getMessage());
        }
    }

    private static function detectEnvironment()
    {
        // Vérifier $_ENV, getenv() ET $_SERVER
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? $_SERVER['DB_HOST'] ?? null;
        
        if ($host) {
            return [
                'host' => $host,
                'port' => $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? $_SERVER['DB_PORT'] ?? '3306',
                'name' => $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? $_SERVER['DB_NAME'] ?? 'ecoride',
                'user' => $_ENV['DB_USER'] ?? getenv('DB_USER') ?? $_SERVER['DB_USER'] ?? 'root',
                'pass' => $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? $_SERVER['DB_PASS'] ?? '',
                'ssl'  => (($_ENV['DB_SSL'] ?? getenv('DB_SSL') ?? $_SERVER['DB_SSL'] ?? 'false') === 'true')
            ];
        }

        return self::$localConfig;
    }

    public static function testConnection()
    {
        try {
            $pdo = self::getConnection();

            $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
            $result = $stmt->fetch();

            $stmt = $pdo->query("SELECT DATABASE() as db_name");
            $dbInfo = $stmt->fetch();

            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px;'>";
            echo "<strong>✅ Connexion réussie !</strong><br>";
            echo "Base de données : <strong>" . $dbInfo['db_name'] . "</strong><br>";
            echo "Utilisateurs enregistrés : <strong>" . $result['total'] . "</strong><br>";
            
            // Afficher quelle config est utilisée
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? $_SERVER['DB_HOST'] ?? null;
            if ($host) {
                echo "Environnement : <strong style='color: #0066cc;'>Production (Aiven) - " . $host . "</strong>";
            } else {
                echo "Environnement : <strong style='color: #cc6600;'>Local (Laragon)</strong>";
            }
            echo "</div>";

            return true;
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px;'>";
            echo "<strong>❌ Connexion échouée :</strong><br>";
            echo $e->getMessage();
            echo "</div>";

            return false;
        }
    }

    public static function getGlobalStats()
    {
        try {
            $pdo = self::getConnection();
            $stats = [];

            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
            $stats['users'] = (int) $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'active' AND departure_time > NOW()");
            $stats['trips'] = (int) $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT AVG(rating) FROM reviews WHERE is_validated = 1");
            $average = $stmt->fetchColumn();
            $stats['average_rating'] = $average ? round($average, 1) : 0;

            return $stats;
        } catch (PDOException $e) {
            return [
                'users' => 0,
                'trips' => 0,
                'average_rating' => 0
            ];
        }
    }

    public static function emailExists($email)
    {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function quickSearch($departure, $arrival, $date = null)
    {
        try {
            $pdo = self::getConnection();

            $sql = "
                SELECT t.id, t.departure_city, t.arrival_city, t.departure_time, 
                       t.price_per_seat, t.available_seats,
                       u.username as driver_name
                FROM trips t
                JOIN users u ON t.driver_id = u.id
                WHERE t.departure_city LIKE :departure
                  AND t.arrival_city LIKE :arrival
                  AND t.status = 'active'
                  AND t.available_seats > 0
                  AND t.departure_time > NOW()
            ";

            $params = [
                ':departure' => "%$departure%",
                ':arrival' => "%$arrival%"
            ];

            if ($date) {
                $sql .= " AND DATE(t.departure_time) = :date";
                $params[':date'] = $date;
            }

            $sql .= " ORDER BY t.departure_time ASC LIMIT 10";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public static function getAdminOverview()
    {
        try {
            $pdo = self::getConnection();

            $stmt = $pdo->query("
                SELECT 
                    (SELECT COUNT(*) FROM users) as total_users,
                    (SELECT COUNT(*) FROM users WHERE is_active = 1) as active_users,
                    (SELECT COUNT(*) FROM trips WHERE status = 'active') as active_trips,
                    (SELECT COUNT(*) FROM bookings WHERE status = 'confirmed') as confirmed_bookings
            ");

            return $stmt->fetch();
        } catch (PDOException $e) {
            return [
                'total_users' => 0,
                'active_users' => 0,
                'active_trips' => 0,
                'confirmed_bookings' => 0
            ];
        }
    }

    public static function cleanup()
    {
        try {
            $pdo = self::getConnection();
            $results = [];

            $stmt = $pdo->prepare("
                DELETE FROM trips 
                WHERE departure_time < DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND status IN ('completed', 'cancelled')
            ");
            $stmt->execute();
            $results['old_trips'] = $stmt->rowCount();

            $stmt = $pdo->prepare("
                DELETE FROM password_resets 
                WHERE expires_at < NOW() OR created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            $results['expired_tokens'] = $stmt->rowCount();

            return $results;
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public static function closeConnection()
    {
        self::$pdo = null;
    }
}

// Chargement automatique des classes
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Trip.php';
require_once __DIR__ . '/../classes/Vehicle.php';
require_once __DIR__ . '/../classes/Admin.php';

// Instance globale pour compatibilité
$pdo = Database::getConnection();