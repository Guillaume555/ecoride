<?php

/**
 * EcoRide - Configuration & Logger MongoDB Atlas
 * Requiert : extension php_mongodb + "composer require mongodb/mongodb vlucas/phpdotenv"
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Dotenv\Dotenv;

// Alias rétro-compatible : l'ancien code peut continuer d'appeler logActivity()
if (!function_exists('logActivity')) {
    function logActivity($userId, $action, $details = [])
    {
        return logUserActivity((int) $userId, $action, $details);
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charge les variables d'environnement (.env)
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

final class MongoDBLogger
{
    /** URI chargée depuis .env → MONGO_URI */
    private static function uri(): string
    {
        $uri = $_ENV['MONGO_URI'] ?? getenv('MONGO_URI') ?? '';
        if ($uri === '') {
            throw new \RuntimeException('MONGO_URI manquante. Ajoutez-la dans le fichier .env à la racine.');
        }
        return $uri;
    }

    private const DATABASE_NAME   = 'ecoride';
    private const COLLECTION_NAME = 'user_activities';

    private Client $client;
    private \MongoDB\Database $database;
    private Collection $collection;
    private bool $isConnected = false;

    public function __construct()
    {
        $this->connect();
    }

    /** Connexion + index */
    private function connect(): void
    {
        try {
            $this->client = new Client(self::uri(), [
                'retryWrites' => true,
                'w' => 'majority',
                'serverSelectionTimeoutMS' => 3000,
                'socketTimeoutMS' => 5000,
            ]);

            $this->database   = $this->client->selectDatabase(self::DATABASE_NAME);
            $this->collection = $this->database->selectCollection(self::COLLECTION_NAME);

            // Index idempotents
            $this->collection->createIndex(['user_id' => 1]);
            $this->collection->createIndex(['action' => 1]);
            $this->collection->createIndex(['timestamp' => -1]);

            // Test de connectivité
            $this->database->command(['ping' => 1]);
            $this->isConnected = true;
        } catch (\Throwable $e) {
            error_log('MongoDB Connection Error: ' . $e->getMessage());
            $this->isConnected = false;
        }
    }

    /** Enregistrer une activité utilisateur */
    public function logActivity(
        int $userId,
        string $action,
        array|string $details = [],
        ?string $userAgent = null,
        ?string $ip = null
    ): string|false {
        if (!$this->isConnected) {
            return $this->fallbackToJson($userId, $action, $details, $userAgent, $ip);
        }

        try {
            $now = new UTCDateTime(); // now en ms UTC

            $document = [
                'user_id'    => $userId,
                'action'     => $action,
                'details'    => $details,
                'timestamp'  => $now,
                'ip'         => $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
                'user_agent' => $userAgent ?: ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'),
                'session_id' => session_id() ?: null,
            ];

            $res = $this->collection->insertOne($document);
            return (string)$res->getInsertedId();
        } catch (\Throwable $e) {
            error_log('MongoDB Insert Error: ' . $e->getMessage());
            return $this->fallbackToJson($userId, $action, $details, $userAgent, $ip);
        }
    }

    /** Récupérer les logs avec pagination */
    public function getLogs(int $limit = 50, int $skip = 0, ?int $userId = null): array
    {
        if (!$this->isConnected) {
            return $this->getLogsFromJson();
        }

        try {
            $filter = [];
            if ($userId !== null) {
                $filter['user_id'] = $userId;
            }

            $options = [
                'sort'  => ['timestamp' => -1],
                'limit' => $limit,
                'skip'  => $skip,
            ];

            $cursor = $this->collection->find($filter, $options);
            $logs   = [];

            foreach ($cursor as $doc) {
                $logs[] = [
                    'id'         => (string)$doc['_id'],
                    'user_id'    => $doc['user_id'] ?? null,
                    'action'     => $doc['action']  ?? null,
                    'details'    => $doc['details'] ?? [],
                    'timestamp'  => isset($doc['timestamp']) && $doc['timestamp'] instanceof UTCDateTime
                        ? $doc['timestamp']->toDateTime()->format('Y-m-d H:i:s')
                        : null,
                    'ip'         => $doc['ip'] ?? null,
                    'user_agent' => $doc['user_agent'] ?? null,
                    'session_id' => $doc['session_id'] ?? null,
                ];
            }

            return $logs;
        } catch (\Throwable $e) {
            error_log('MongoDB Read Error: ' . $e->getMessage());
            return $this->getLogsFromJson();
        }
    }

    /** Statistiques d'activité */
    public function getActivityStats(): array
    {
        if (!$this->isConnected) {
            return $this->getStatsFromJson();
        }

        try {
            $pipeline = [
                ['$group' => ['_id' => '$action', 'count' => ['$sum' => 1]]],
                ['$sort'  => ['count' => -1]],
            ];

            $cursor = $this->collection->aggregate($pipeline);
            $stats  = [];

            foreach ($cursor as $doc) {
                $stats[(string)$doc['_id']] = (int)$doc['count'];
            }

            return $stats;
        } catch (\Throwable $e) {
            error_log('MongoDB Stats Error: ' . $e->getMessage());
            return $this->getStatsFromJson();
        }
    }

    /** Migration des logs JSON vers MongoDB */
    public function migrateFromJson(): int|false
    {
        if (!$this->isConnected) return false;

        $jsonFile = __DIR__ . '/../mongodb/user_logs.json';
        if (!is_file($jsonFile)) return false;

        @ini_set('memory_limit', '512M');
        @set_time_limit(60);

        try {
            $raw = file_get_contents($jsonFile);
            $jsonData = json_decode($raw, true);
            if (!is_array($jsonData)) return false;

            $migrated = 0;

            foreach ($jsonData as $log) {
                $ts = isset($log['timestamp'])
                    ? new UTCDateTime(strtotime((string)$log['timestamp']) * 1000)
                    : new UTCDateTime();

                $doc = [
                    'user_id'    => isset($log['user_id']) ? (int)$log['user_id'] : 0,
                    'action'     => (string)($log['action'] ?? 'unknown'),
                    'details'    => $log['details'] ?? [],
                    'timestamp'  => $ts,
                    'ip'         => $log['ip'] ?? '127.0.0.1',
                    'user_agent' => $log['user_agent'] ?? ($log['browser'] ?? 'Unknown'),
                    'session_id' => $log['id'] ?? null,
                    'migrated'   => true,
                ];

                $this->collection->insertOne($doc);
                $migrated++;
            }

            @rename($jsonFile, $jsonFile . '.migrated_' . date('Y-m-d_H-i-s'));
            return $migrated;
        } catch (\Throwable $e) {
            error_log('Migration Error: ' . $e->getMessage());
            return false;
        }
    }

    /** Test connexion */
    public function testConnection(): array
    {
        if (!$this->isConnected) {
            return ['success' => false, 'message' => 'Connexion MongoDB échouée'];
        }

        try {
            $count = $this->collection->countDocuments();
            return [
                'success'         => true,
                'message'         => 'Connexion MongoDB réussie',
                'documents_count' => $count,
                'database'        => self::DATABASE_NAME,
                'collection'      => self::COLLECTION_NAME,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Erreur test MongoDB: ' . $e->getMessage()];
        }
    }

    /* =======================
       Fallback JSON (DEV only)
       ======================= */

    private function fallbackToJson(int $userId, string $action, array|string $details, ?string $userAgent, ?string $ip): string
    {
        $jsonDir  = __DIR__ . '/../mongodb';
        $jsonFile = $jsonDir . '/user_logs.json';
        if (!is_dir($jsonDir)) @mkdir($jsonDir, 0777, true);

        $logs = is_file($jsonFile) ? json_decode((string)file_get_contents($jsonFile), true) : [];
        if (!is_array($logs)) $logs = [];

        $newLog = [
            'id'         => uniqid('', true),
            'user_id'    => $userId,
            'action'     => $action,
            'details'    => $details,
            'timestamp'  => date('Y-m-d H:i:s'),
            'ip'         => $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
            'user_agent' => $userAgent ?: ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'),
        ];

        $logs[] = $newLog;
        file_put_contents($jsonFile, json_encode($logs, JSON_PRETTY_PRINT));

        return $newLog['id'];
    }

    private function getLogsFromJson(): array
    {
        $jsonFile = __DIR__ . '/../mongodb/user_logs.json';
        if (!is_file($jsonFile)) return [];
        $data = json_decode((string)file_get_contents($jsonFile), true);
        return is_array($data) ? $data : [];
    }

    private function getStatsFromJson(): array
    {
        $logs = $this->getLogsFromJson();
        $stats = [];
        foreach ($logs as $log) {
            $a = $log['action'] ?? 'unknown';
            $stats[$a] = ($stats[$a] ?? 0) + 1;
        }
        return $stats;
    }
}

/* Instance globale + helpers */
$mongoLogger = new MongoDBLogger();

function logUserActivity(int $userId, string $action, array|string $details = []): string|false
{
    global $mongoLogger;
    return $mongoLogger->logActivity($userId, $action, $details);
}

function testMongoConnection(): array
{
    global $mongoLogger;
    return $mongoLogger->testConnection();
}
