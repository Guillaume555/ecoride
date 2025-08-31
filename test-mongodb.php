<?php

declare(strict_types=1);
/**
 * EcoRide - Test & Migration MongoDB Atlas
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/mongodb.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EcoRide - Test MongoDB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-success {
            background: #d4edda;
            color: #155724;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
        }

        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">

                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h3>Test MongoDB Atlas - EcoRide</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">Page de test pour valider l'implémentation MongoDB Atlas.</p>
                    </div>
                </div>

                <!-- Test Connexion -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>🔗 Test de Connexion MongoDB</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $connectionTest = testMongoConnection();
                        $ok = !empty($connectionTest['success']);
                        $statusClass = $ok ? 'status-success' : 'status-error';
                        $icon = $ok ? '✅' : '❌';
                        ?>
                        <div class="alert <?= $statusClass ?>">
                            <strong><?= $icon ?> <?= htmlspecialchars($connectionTest['message'] ?? ''); ?></strong>
                            <?php if ($ok): ?>
                                <br>📊 Documents existants: <?= (int)($connectionTest['documents_count'] ?? 0); ?>
                                <br>🗄️ Base: <?= htmlspecialchars($connectionTest['database'] ?? ''); ?>
                                <br>📁 Collection: <?= htmlspecialchars($connectionTest['collection'] ?? ''); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($ok): ?>
                    <!-- Migration JSON -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>📦 Migration des logs JSON vers MongoDB</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['migrate'])) {
                                global $mongoLogger;
                                $migrated = $mongoLogger->migrateFromJson();

                                if ($migrated !== false) {
                                    echo '<div class="alert status-success">';
                                    echo "✅ <strong>Migration réussie !</strong><br>";
                                    echo "📊 " . (int)$migrated . " logs migrés vers MongoDB<br>";
                                    echo "💾 Fichier JSON sauvegardé avec suffixe .migrated";
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert status-error">';
                                    echo "❌ <strong>Échec de la migration</strong><br>";
                                    echo "Vérifiez les logs d'erreur";
                                    echo '</div>';
                                }
                            }
                            ?>
                            <p>Migrer les logs JSON existants vers MongoDB Atlas :</p>
                            <form method="POST">
                                <button type="submit" name="migrate" class="btn btn-warning">Migrer les logs JSON</button>
                            </form>
                        </div>
                    </div>

                    <!-- Test Log -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>📝 Test d'enregistrement d'activité</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_log'])) {
                                $logId = logUserActivity(
                                    999,
                                    'test_activity',
                                    ['test' => true, 'timestamp' => date('Y-m-d H:i:s'), 'action' => 'Manual test from admin panel']
                                );
                                echo '<div class="alert status-success">';
                                echo "✅ <strong>Test log enregistré !</strong><br>";
                                echo "🆔 Log ID: " . htmlspecialchars((string)$logId);
                                echo '</div>';
                            }
                            ?>
                            <form method="POST">
                                <button type="submit" name="test_log" class="btn btn-primary">Créer un log de test</button>
                            </form>
                        </div>
                    </div>

                    <!-- Logs récents -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>📋 Logs récents (10 derniers)</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            global $mongoLogger;
                            $recentLogs = $mongoLogger->getLogs(10);
                            if (empty($recentLogs)) {
                                echo '<div class="alert alert-info">ℹ️ Aucun log trouvé</div>';
                            } else {
                                echo '<div class="table-responsive"><table class="table table-sm table-striped">';
                                echo '<thead><tr><th>ID User</th><th>Action</th><th>Timestamp</th><th>IP</th><th>Détails</th></tr></thead><tbody>';
                                foreach ($recentLogs as $log) {
                                    $details = is_array($log['details']) ? json_encode($log['details']) : (string)$log['details'];
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars((string)($log['user_id'] ?? '')) . '</td>';
                                    echo '<td><span class="badge bg-primary">' . htmlspecialchars((string)($log['action'] ?? '')) . '</span></td>';
                                    echo '<td><small>' . htmlspecialchars((string)($log['timestamp'] ?? '')) . '</small></td>';
                                    echo '<td><code>' . htmlspecialchars((string)($log['ip'] ?? '')) . '</code></td>';
                                    echo '<td><small>' . htmlspecialchars($details) . '</small></td>';
                                    echo '</tr>';
                                }
                                echo '</tbody></table></div>';
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Instructions -->
                <div class="card">
                    <div class="card-header">
                        <h5>📖 Instructions d'utilisation</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li><strong>Secrets :</strong> Mets l’URI dans <code>.env</code> (clé <code>MONGO_URI</code>).</li>
                            <li><strong>Connexion :</strong> Le bloc vert ci-dessus doit afficher ✅.</li>
                            <li><strong>Migration :</strong> Clique “Migrer les logs JSON”.</li>
                            <li><strong>Intégration :</strong> Utilise <code>logUserActivity()</code> dans l’app.</li>
                            <li><strong>Nettoyage :</strong> Supprime cette page après validation.</li>
                        </ol>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>

</html>