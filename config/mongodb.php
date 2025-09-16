<?php
/*
================================================
FICHIER: config/mongodb.php - Connexion MongoDB EcoRide
Description: Gestion logs utilisateur en NoSQL
================================================
*/

class MongoDBLogger
{
    private $logs_file;

    public function __construct()
    {
        $this->logs_file = __DIR__ . '/../mongodb/user_logs.json';

        // Créer le fichier s'il n'existe pas
        if (!file_exists($this->logs_file)) {
            file_put_contents($this->logs_file, '[]');
        }
    }

    /**
     * Ajouter un log d'activité utilisateur
     */
    public function logUserActivity($user_id, $action, $details = [])
    {
        $log_entry = [
            'id' => uniqid(),
            'user_id' => $user_id,
            'action' => $action,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'localhost',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        // Lire les logs existants
        $existing_logs = $this->getLogs();

        // Ajouter le nouveau log
        $existing_logs[] = $log_entry;

        // Garder seulement les 100 derniers logs (performance)
        if (count($existing_logs) > 100) {
            $existing_logs = array_slice($existing_logs, -100);
        }

        // Sauvegarder
        file_put_contents($this->logs_file, json_encode($existing_logs, JSON_PRETTY_PRINT));

        return true;
    }

    /**
     * Récupérer tous les logs
     */
    public function getLogs($limit = null)
    {
        $logs = json_decode(file_get_contents($this->logs_file), true) ?? [];

        if ($limit) {
            return array_slice($logs, -$limit);
        }

        return $logs;
    }

    /**
     * Récupérer les logs d'un utilisateur
     */
    public function getUserLogs($user_id, $limit = 10)
    {
        $all_logs = $this->getLogs();
        $user_logs = array_filter($all_logs, function ($log) use ($user_id) {
            return $log['user_id'] == $user_id;
        });

        return array_slice($user_logs, -$limit);
    }

    /**
     * Statistiques d'activité
     */
    public function getActivityStats()
    {
        $logs = $this->getLogs();

        $stats = [
            'total_activities' => count($logs),
            'unique_users' => count(array_unique(array_column($logs, 'user_id'))),
            'actions' => [],
            'today_activities' => 0
        ];

        $today = date('Y-m-d');

        foreach ($logs as $log) {
            // Compter par action
            $action = $log['action'];
            $stats['actions'][$action] = ($stats['actions'][$action] ?? 0) + 1;

            // Activités d'aujourd'hui
            if (strpos($log['timestamp'], $today) === 0) {
                $stats['today_activities']++;
            }
        }

        return $stats;
    }
}

// Instance globale
$mongoLogger = new MongoDBLogger();

/**
 * Fonction helper pour logger facilement
 */
function logActivity($user_id, $action, $details = [])
{
    global $mongoLogger;
    return $mongoLogger->logUserActivity($user_id, $action, $details);
}
