<?php

/**
 * includes/analytics.php
 * Couche très légère au-dessus de logUserActivity()
 * pour standardiser les logs fonctionnels (search, reservation, etc.).
 */

require_once __DIR__ . '/session.php';   // pour isLoggedIn(), getCurrentUser()
require_once __DIR__ . '/../config/mongodb.php'; // expose logUserActivity()

/** ID utilisateur courant (0 si invité) */
function currentUserId(): int
{
    $u = getCurrentUser();
    return $u['id'] ?? 0;
}

/** Log générique (action libre) */
function track(string $action, array $details = []): string|bool
{
    return logUserActivity(currentUserId(), $action, $details);
}

/** Spécifiques (sucre syntaxique) */
function trackSearch(string $from, string $to, array $filters = []): string|bool
{
    return track('search', [
        'from'    => $from,
        'to'      => $to,
        'filters' => $filters
    ]);
}

function trackReservation(int $tripId, int $seats, float $price, array $meta = []): string|bool
{
    return track('reservation', [
        'trip_id' => $tripId,
        'seats'   => $seats,
        'price'   => $price,
        ...$meta
    ]);
}

function trackView(string $page, array $meta = []): string|bool
{
    return track('view', ['page' => $page, ...$meta]);
}

function trackError(string $where, string $message, array $meta = []): string|bool
{
    return track('error', ['where' => $where, 'message' => $message, ...$meta]);
}
