<?php

/**
 * ========================================
 * CLASSE DATABASE - GESTIONNAIRE DE CONNEXION
 * ========================================
 * 
 * Cette classe centralise tout ce qui concerne la base de données :
 * - Connexion PDO sécurisée (local + production)
 * - Méthodes utilitaires pour les requêtes courantes
 * - Configuration automatique selon l'environnement
 * 
 * Version POO moderne qui remplace l'ancien database.php procédural
 * Projet EcoRide - ECF DWWM 2025
 */

class Database
{
    // ==================== ATTRIBUTS ====================

    /**
     * @var PDO Instance de connexion PDO
     */
    private static $pdo = null;

    /**
     * @var array Configuration base locale (Laragon)
     */
    private static $localConfig = [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'ecoride',
        'user' => 'root',
        'pass' => '',
        'ssl' => false
    ];


    // ==================== CONNEXION ====================

    /**
     * Obtenir la connexion PDO (singleton pattern)
     * 
     * Se connecte automatiquement à la bonne base selon l'environnement :
     * - Production : Aiven (variables d'environnement)
     * - Local : Laragon (configuration par défaut)
     * 
     * @return PDO Instance de connexion
     * @throws Exception Si connexion échoue
     */
    public static function getConnection()
    {
        // Si déjà connecté, retourner la connexion existante
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        try {
            // Détection environnement et configuration
            $config = self::detectEnvironment();

            // Construction du DSN (Data Source Name)
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset=utf8mb4";

            // Options PDO pour sécurité et performance
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,     // Exceptions sur erreurs
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Tableaux associatifs par défaut
                PDO::ATTR_EMULATE_PREPARES => false               // Vraies requêtes préparées
            ];

            // SSL pour production (Aiven nécessite une connexion sécurisée)
            if ($config['ssl']) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            // Création de la connexion
            self::$pdo = new PDO($dsn, $config['user'], $config['pass'], $options);

            return self::$pdo;
        } catch (PDOException $e) {
            throw new Exception("Impossible de se connecter à la base : " . $e->getMessage());
        }
    }

    /**
     * Détecter l'environnement et retourner la bonne configuration
     * 
     * @return array Configuration base de données
     */
    private static function detectEnvironment()
    {
        // Production : variables d'environnement présentes (Render + Aiven)
        if (getenv('DB_HOST')) {
            return [
                'host' => getenv('DB_HOST'),
                'port' => getenv('DB_PORT'),
                'name' => getenv('DB_NAME'),
                'user' => getenv('DB_USER'),
                'pass' => getenv('DB_PASS'),
                'ssl'  => getenv('DB_SSL') === 'true'
            ];
        }

        // Local : configuration Laragon par défaut
        return self::$localConfig;
    }


    // ==================== MÉTHODES UTILITAIRES ====================

    /**
     * Tester la connexion et afficher les infos
     * 
     * Utile pendant le développement pour vérifier que tout fonctionne
     * Affiche le nombre d'utilisateurs et le nom de la base utilisée
     * 
     * @return bool True si connexion OK
     */
    public static function testConnection()
    {
        try {
            $pdo = self::getConnection();

            // Compter les utilisateurs pour test
            $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
            $result = $stmt->fetch();

            // Obtenir le nom de la base
            $stmt = $pdo->query("SELECT DATABASE() as db_name");
            $dbInfo = $stmt->fetch();

            // Affichage sympa pour debug
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px;'>";
            echo "<strong>✅ Connexion réussie !</strong><br>";
            echo "Base de données : <strong>" . $dbInfo['db_name'] . "</strong><br>";
            echo "Utilisateurs enregistrés : <strong>" . $result['total'] . "</strong><br>";
            echo "Environnement : " . (getenv('DB_HOST') ? 'Production (Aiven)' : 'Local (Laragon)');
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

    /**
     * Obtenir des statistiques globales du site
     * 
     * Retourne les chiffres clés pour la page d'accueil :
     * - Nombre d'utilisateurs actifs
     * - Nombre de trajets disponibles  
     * - Note moyenne des avis
     * 
     * @return array Statistiques
     */
    public static function getGlobalStats()
    {
        try {
            $pdo = self::getConnection();
            $stats = [];

            // Utilisateurs actifs (non bannis)
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
            $stats['users'] = (int) $stmt->fetchColumn();

            // Trajets disponibles actuellement
            $stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'active' AND departure_time > NOW()");
            $stats['trips'] = (int) $stmt->fetchColumn();

            // Note moyenne des avis validés
            $stmt = $pdo->query("SELECT AVG(rating) FROM reviews WHERE is_validated = 1");
            $average = $stmt->fetchColumn();
            $stats['average_rating'] = $average ? round($average, 1) : 0;

            return $stats;
        } catch (PDOException $e) {
            // En cas d'erreur, retourner des stats par défaut
            return [
                'users' => 0,
                'trips' => 0,
                'average_rating' => 0
            ];
        }
    }

    /**
     * Vérifier si un email existe déjà en base
     * 
     * Utile pour éviter les doublons lors de l'inscription
     * 
     * @param string $email Email à vérifier
     * @return bool True si l'email existe
     */
    public static function emailExists($email)
    {
        try {
            $pdo = self::getConnection();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            // En cas d'erreur, considérer que l'email n'existe pas
            return false;
        }
    }

    /**
     * Recherche rapide de trajets (méthode simplifiée)
     * 
     * Version basique pour la barre de recherche de l'accueil
     * Pour recherche avancée, utiliser Trip::search()
     * 
     * @param string $departure Ville de départ
     * @param string $arrival Ville d'arrivée  
     * @param string|null $date Date optionnelle
     * @return array Liste des trajets trouvés
     */
    public static function quickSearch($departure, $arrival, $date = null)
    {
        try {
            $pdo = self::getConnection();

            // Requête de base
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

            // Filtre par date si spécifiée
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


    // ==================== MÉTHODES ADMIN ====================

    /**
     * Obtenir un aperçu rapide pour l'admin
     * 
     * Statistiques de base pour le tableau de bord admin
     * Version simplifiée - pour stats complètes utiliser Admin::getDashboardStats()
     * 
     * @return array Statistiques admin
     */
    public static function getAdminOverview()
    {
        try {
            $pdo = self::getConnection();

            // Requête groupée pour performance
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


    // ==================== NETTOYAGE ET MAINTENANCE ====================

    /**
     * Nettoyer les données obsolètes
     * 
     * Supprime automatiquement :
     * - Trajets passés depuis plus de 30 jours
     * - Tokens de reset expirés
     * - Logs MongoDB anciens (si applicable)
     * 
     * À appeler périodiquement (cron job)
     * 
     * @return array Résumé du nettoyage
     */
    public static function cleanup()
    {
        try {
            $pdo = self::getConnection();
            $results = [];

            // Trajets anciens (passés depuis 30 jours)
            $stmt = $pdo->prepare("
                DELETE FROM trips 
                WHERE departure_time < DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND status IN ('completed', 'cancelled')
            ");
            $stmt->execute();
            $results['old_trips'] = $stmt->rowCount();

            // Tokens de reset expirés
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

    /**
     * Fermer la connexion (utile pour les tests)
     */
    public static function closeConnection()
    {
        self::$pdo = null;
    }
}

// ==================== CHARGEMENT AUTOMATIQUE DES CLASSES ====================

// Inclure toutes les classes métier pour utilisation dans les pages
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Trip.php';
require_once __DIR__ . '/../classes/Vehicle.php';
require_once __DIR__ . '/../classes/Admin.php';

// Créer une instance globale pour compatibilité avec l'ancien code
// Cela permet aux pages qui utilisent encore $pdo de continuer à fonctionner
$pdo = Database::getConnection();

/*
==================== EXEMPLES D'UTILISATION ====================

// Nouvelle façon (POO) :
$pdo = Database::getConnection();
$stats = Database::getGlobalStats();
$trips = Database::quickSearch('Paris', 'Lyon');

// Ancienne façon (toujours compatible) :
require_once 'config/database.php';
// $pdo est automatiquement disponible

==================== MIGRATION PROGRESSIVE ====================

1. Les anciennes pages continuent de fonctionner avec $pdo global
2. Les nouvelles pages peuvent utiliser Database::getConnection()
3. Migration douce sans casser l'existant

Le fichier garde la compatibilité totale avec l'architecture actuelle
tout en offrant une approche POO moderne et maintenable.
*/