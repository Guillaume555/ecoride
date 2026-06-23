<?php

/**
 * CLASSE ADMIN - STATISTIQUES ET DONNÉES ADMINISTRATIVES
 * 
 * Cette classe regroupe toutes les fonctions pour l'interface admin :
 * - Statistiques globales (utilisateurs, trajets, réservations)
 * - Listes des derniers éléments (nouveaux users, dernières réservations)
 * - Données pour les graphiques et tableaux de bord
 * 
 * Toutes les méthodes sont statiques (pas besoin d'instancier la classe)
 * 
 * Projet EcoRide - ECF DWWM 2025
 */

class Admin
{

    // ========== STATISTIQUES GLOBALES ==========

    /**
     * RÉCUPÉRER TOUTES LES STATS POUR LE TABLEAU DE BORD ADMIN
     * 
     * Utilisation :
     * $stats = Admin::getDashboardStats($pdo);
     * echo "Utilisateurs : " . $stats['users_count'];
     * 
     * Retourne un tableau avec :
     * - users_count : Nombre total d'utilisateurs
     * - users_active : Utilisateurs actifs (non bannis)
     * - trips_count : Nombre total de trajets
     * - trips_active : Trajets actifs en ce moment
     * - bookings_count : Nombre total de réservations
     * - bookings_confirmed : Réservations confirmées
     * - total_credits : Total des crédits en circulation
     * - average_rating : Note moyenne du site
     */
    public static function getDashboardStats($pdo)
    {
        $stats = [];

        try {
            // Statistiques utilisateurs
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $stats['users_count'] = (int) $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
            $stats['users_active'] = (int) $stmt->fetchColumn();

            // Statistiques trajets
            $stmt = $pdo->query("SELECT COUNT(*) FROM trips");
            $stats['trips_count'] = (int) $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'active'");
            $stats['trips_active'] = (int) $stmt->fetchColumn();

            // Statistiques réservations
            $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
            $stats['bookings_count'] = (int) $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'");
            $stats['bookings_confirmed'] = (int) $stmt->fetchColumn();

            // Crédits en circulation
            $stmt = $pdo->query("SELECT SUM(credits) FROM users");
            $stats['total_credits'] = (int) $stmt->fetchColumn();

            // Note moyenne (si table reviews existe)
            try {
                $stmt = $pdo->query("SELECT AVG(rating) FROM reviews WHERE is_validated = 1");
                $avg = $stmt->fetchColumn();
                $stats['average_rating'] = $avg ? round($avg, 1) : 0;
            } catch (PDOException $e) {
                $stats['average_rating'] = 0;
            }

            return $stats;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des statistiques : " . $e->getMessage());
        }
    }

    /**
     * STATISTIQUES DÉTAILLÉES SUR LES UTILISATEURS
     * 
     * Utilisation :
     * $userStats = Admin::getUsersStats($pdo);
     * 
     * Retourne :
     * - total : Nombre total d'utilisateurs
     * - active : Utilisateurs actifs
     * - banned : Utilisateurs bannis
     * - new_this_month : Nouveaux inscrits ce mois
     * - with_vehicles : Utilisateurs qui ont des véhicules
     * - with_trips : Utilisateurs qui ont créé des trajets
     */
    public static function getUsersStats($pdo)
    {
        $stats = [];

        try {
            // Total et actifs
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $stats['total'] = (int) $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
            $stats['active'] = (int) $stmt->fetchColumn();

            $stats['banned'] = $stats['total'] - $stats['active'];

            // Nouveaux ce mois
            $stmt = $pdo->query("
                SELECT COUNT(*) FROM users 
                WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
                AND YEAR(created_at) = YEAR(CURRENT_DATE())
            ");
            $stats['new_this_month'] = (int) $stmt->fetchColumn();

            // Avec véhicules
            $stmt = $pdo->query("
                SELECT COUNT(DISTINCT user_id) FROM vehicles
            ");
            $stats['with_vehicles'] = (int) $stmt->fetchColumn();

            // Avec trajets
            $stmt = $pdo->query("
                SELECT COUNT(DISTINCT driver_id) FROM trips
            ");
            $stats['with_trips'] = (int) $stmt->fetchColumn();

            return $stats;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des stats utilisateurs : " . $e->getMessage());
        }
    }

    /**
     * STATISTIQUES DÉTAILLÉES SUR LES TRAJETS
     * 
     * Utilisation :
     * $tripStats = Admin::getTripsStats($pdo);
     * 
     * Retourne :
     * - total : Nombre total de trajets
     * - active : Trajets actifs
     * - completed : Trajets terminés
     * - cancelled : Trajets annulés
     * - average_price : Prix moyen par place
     * - total_seats_offered : Total de places proposées
     */
    public static function getTripsStats($pdo)
    {
        $stats = [];

        try {
            // Total
            $stmt = $pdo->query("SELECT COUNT(*) FROM trips");
            $stats['total'] = (int) $stmt->fetchColumn();

            // Par statut
            $stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'active'");
            $stats['active'] = (int) $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'completed'");
            $stats['completed'] = (int) $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'cancelled'");
            $stats['cancelled'] = (int) $stmt->fetchColumn();

            // Prix moyen
            $stmt = $pdo->query("SELECT AVG(price_per_seat) FROM trips WHERE status = 'active'");
            $avg = $stmt->fetchColumn();
            $stats['average_price'] = $avg ? round($avg, 2) : 0;

            // Total places offertes
            $stmt = $pdo->query("SELECT SUM(available_seats) FROM trips WHERE status = 'active'");
            $stats['total_seats_offered'] = (int) $stmt->fetchColumn();

            return $stats;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des stats trajets : " . $e->getMessage());
        }
    }

    /**
     * STATISTIQUES DÉTAILLÉES SUR LES RÉSERVATIONS
     * 
     * Utilisation :
     * $bookingStats = Admin::getBookingsStats($pdo);
     * 
     * Retourne :
     * - total : Nombre total de réservations
     * - confirmed : Réservations confirmées
     * - cancelled : Réservations annulées
     * - total_revenue : Chiffre d'affaires total (crédits échangés)
     * - average_booking : Montant moyen d'une réservation
     */
    public static function getBookingsStats($pdo)
    {
        $stats = [];

        try {
            // Total
            $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
            $stats['total'] = (int) $stmt->fetchColumn();

            // Par statut
            $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'");
            $stats['confirmed'] = (int) $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'");
            $stats['cancelled'] = (int) $stmt->fetchColumn();

            // Chiffre d'affaires
            $stmt = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'confirmed'");
            $revenue = $stmt->fetchColumn();
            $stats['total_revenue'] = $revenue ? (int) $revenue : 0;

            // Montant moyen
            if ($stats['confirmed'] > 0) {
                $stats['average_booking'] = round($stats['total_revenue'] / $stats['confirmed'], 2);
            } else {
                $stats['average_booking'] = 0;
            }

            return $stats;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des stats réservations : " . $e->getMessage());
        }
    }


    // ========== DERNIERS ÉLÉMENTS ==========

    /**
     * RÉCUPÉRER LES DERNIERS UTILISATEURS INSCRITS
     * 
     * Utilisation :
     * $newUsers = Admin::getRecentUsers($pdo, 10);
     * 
     * Retourne les X derniers utilisateurs avec :
     * - id, username, email, created_at, credits, is_banned
     */
    public static function getRecentUsers($pdo, $limit = 5)
    {
        try {
            $stmt = $pdo->prepare("
            SELECT id, username, email, created_at, credits, is_active, role
            FROM users
            ORDER BY created_at DESC
            LIMIT ?
        ");
            $stmt->execute([$limit]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des derniers utilisateurs : " . $e->getMessage());
        }
    }

    /**
     * RÉCUPÉRER LES DERNIÈRES RÉSERVATIONS
     * 
     * Utilisation :
     * $recentBookings = Admin::getRecentBookings($pdo, 10);
     * 
     * Retourne les X dernières réservations avec infos complètes :
     * - Infos réservation (id, date, prix, places)
     * - Infos passager (nom)
     * - Infos trajet (départ, arrivée)
     * - Infos conducteur (nom)
     */
    public static function getRecentBookings($pdo, $limit = 5)
    {
        try {
            $stmt = $pdo->prepare("
            SELECT 
                b.id,
                b.seats_booked,
                b.total_price,
                b.status,
                u.username as passenger_name,
                t.departure_city,
                t.arrival_city,
                t.departure_time,
                d.username as driver_name
            FROM bookings b
            JOIN users u ON b.passenger_id = u.id
            JOIN trips t ON b.trip_id = t.id
            JOIN users d ON t.driver_id = d.id
            ORDER BY b.id DESC
            LIMIT ?
        ");
            $stmt->execute([$limit]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des dernières réservations : " . $e->getMessage());
        }
    }
}
