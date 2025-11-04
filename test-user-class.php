<?php
require_once 'config/database.php';

echo "<h1>TEST CLASSE TRIP.PHP</h1>";

try {
    // TEST 1 : Recherche de trajets
    echo "<h2>Test 1 : Recherche de trajets</h2>";
    $trips = Trip::search($pdo, 'Paris', 'Lyon');
    echo "✅ Trajets trouvés : " . count($trips) . "<br>";

    if (!empty($trips)) {
        $firstTrip = $trips[0];
        echo "Exemple : {$firstTrip['departure_city']} → {$firstTrip['arrival_city']}<br>";
        echo "Prix : {$firstTrip['price_per_seat']} crédits/place<br>";
    }

    // TEST 2 : Charger un trajet existant
    echo "<h2>Test 2 : Charger un trajet</h2>";
    $stmt = $pdo->query("SELECT id FROM trips LIMIT 1");
    $tripId = $stmt->fetchColumn();

    if ($tripId) {
        $trip = new Trip($pdo, $tripId);
        echo "✅ Trajet chargé : ID {$trip->getId()}<br>";
        echo "🚗 Trajet : {$trip->get('departure_city')} → {$trip->get('arrival_city')}<br>";
        echo "👤 Conducteur : {$trip->get('driver_name')}<br>";
        echo "💺 Places disponibles : {$trip->get('available_seats')}<br>";

        // TEST 3 : Obtenir les réservations
        echo "<h2>Test 3 : Réservations du trajet</h2>";
        $bookings = $trip->getBookings();
        echo "📋 Nombre de réservations : " . count($bookings) . "<br>";
    } else {
        echo "⚠️ Aucun trajet dans la BDD pour tester<br>";
    }

    // TEST 4 : Trajets d'un utilisateur
    echo "<h2>Test 4 : Trajets d'un utilisateur</h2>";
    $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $userId = $stmt->fetchColumn();

    if ($userId) {
        $userTrips = Trip::getByUser($pdo, $userId);
        echo "✅ Trajets de l'utilisateur : " . count($userTrips) . "<br>";
    }

    echo "<hr><h2 style='color: green;'>✅ TOUS LES TESTS PASSÉS !</h2>";
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERREUR : " . htmlspecialchars($e->getMessage()) . "</h2>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
