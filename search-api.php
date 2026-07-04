<?php
/*
================================================
FICHIER: search-api.php - Point d'accès JSON pour la recherche asynchrone
Description: Reçoit les critères de recherche et les filtres, renvoie les
             trajets correspondants au format JSON (sans aucune page HTML).
             Appelé en fetch() depuis search-trip.js pour filtrer sans
             recharger la page.

Placé à la racine du projet (à côté de index.php) pour que les chemins
relatifs (config/, classes/) fonctionnent exactement comme dans index.php.
================================================
*/

// On annonce dès le départ qu'on renvoie du JSON.
header('Content-Type: application/json; charset=utf-8');

require_once 'config/database.php';   // fournit $pdo
require_once 'classes/Trip.php';      // fournit la classe Trip

// Récupération et nettoyage des paramètres
$depart    = trim($_GET['depart'] ?? '');
$arrivee   = trim($_GET['arrivee'] ?? '');
$date      = trim($_GET['date'] ?? '');
$maxPrice  = $_GET['max_price'] ?? '';
$fuelType  = $_GET['fuel_type'] ?? '';

// La recherche exige au minimum un départ et une arrivée.
if ($depart === '' || $arrivee === '') {
    echo json_encode([
        'success' => false,
        'message' => "Veuillez indiquer une ville de départ et d'arrivée.",
        'count'   => 0,
        'trips'   => []
    ]);
    exit;
}

try {
    // Recherche de base (même méthode que la page classique)
    $trips = Trip::search($pdo, $depart, $arrivee, $date !== '' ? $date : null);

    // Filtre prix maximum
    if ($maxPrice !== '') {
        $trips = array_filter($trips, function ($trip) use ($maxPrice) {
            return floatval($trip['price_per_seat']) <= floatval($maxPrice);
        });
    }

    // Filtre type de carburant
    if ($fuelType !== '') {
        $trips = array_filter($trips, function ($trip) use ($fuelType) {
            return $trip['fuel_type'] === $fuelType;
        });
    }

    // array_filter conserve les clés d'origine : on les réindexe pour un vrai tableau JSON.
    $trips = array_values($trips);

    echo json_encode([
        'success' => true,
        'count'   => count($trips),
        'trips'   => $trips
    ]);
} catch (Exception $e) {
    // On ne renvoie jamais le détail technique de l'erreur au client.
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Une erreur est survenue lors de la recherche.",
        'count'   => 0,
        'trips'   => []
    ]);
}
