<?php
/*
================================================
FICHIER: pages/logs.php - Démonstration MongoDB
Description: Affichage des logs NoSQL pour l'ECF
================================================
*/

require_once 'includes/session.php';
require_once 'config/mongodb.php';

// Vérification admin (optionnel)
// requireLogin();

$page_title = "EcoRide - Logs MongoDB";
$extra_css = [];

// Récupération des données MongoDB
$stats = $mongoLogger->getActivityStats();
$recent_logs = $mongoLogger->getLogs(20);
?>

<div class="container my-5">
    <h1><i class="fas fa-database text-success"></i> Démonstration MongoDB</h1>
    <p class="text-muted">Logs d'activité utilisateur stockés en NoSQL</p>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary"><?= $stats['total_activities'] ?></h3>
                    <p>Total activités</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success"><?= $stats['unique_users'] ?></h3>
                    <p>Utilisateurs uniques</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning"><?= $stats['today_activities'] ?></h3>
                    <p>Activités aujourd'hui</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info"><?= count($stats['actions']) ?></h3>
                    <p>Types d'actions</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs récents -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> Logs Récents (MongoDB)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User ID</th>
                            <th>Action</th>
                            <th>Détails</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($recent_logs) as $log): ?>
                            <tr>
                                <td><?= $log['timestamp'] ?></td>
                                <td><?= $log['user_id'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $log['action'] === 'login' ? 'success' : ($log['action'] === 'register' ? 'primary' : 'secondary') ?>">
                                        <?= $log['action'] ?>
                                    </span>
                                </td>
                                <td><?= isset($log['details']['username']) ? $log['details']['username'] : '-' ?></td>
                                <td><?= $log['ip'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>