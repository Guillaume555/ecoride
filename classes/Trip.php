<?php

/**
 * CLASSE TRIP - GESTION DES TRAJETS
 * 
 * Cette classe gère tout ce qui concerne les trajets :
 * - Créer un trajet
 * - Rechercher des trajets disponibles
 * - Réserver un trajet (avec gestion automatique des crédits)
 * - Annuler un trajet (avec remboursement automatique des passagers)
 * - Gérer les réservations
 * 
 * Projet EcoRide - ECF DWWM 2025
 */

class Trip
{

    // Variables privées (protégées)
    private $pdo;        // Connexion base de données
    private $id;         // ID du trajet actuellement chargé
    private $data;       // Toutes les infos du trajet


    // ========== CONSTRUCTEUR ==========

    /**
     * Créer un objet Trip
     * Si tu donnes un ID, il charge automatiquement les infos du trajet
     */
    public function __construct($pdo, $id = null)
    {
        $this->pdo = $pdo;
        $this->id = $id;
        $this->data = [];

        if ($id !== null) {
            $this->loadById($id);
        }
    }


    // ========== CRÉER / MODIFIER UN TRAJET ==========

    /**
     * CRÉER UN NOUVEAU TRAJET
     * 
     * Utilisation :
     * $trip = new Trip($pdo);
     * $tripId = $trip->create($driverId, $vehicleId, [
     *     'departure_city' => 'Paris',
     *     'arrival_city' => 'Lyon',
     *     'departure_time' => '2025-07-20 14:00:00',
     *     'price_per_seat' => 15,
     *     'available_seats' => 3,
     *     'preferences' => 'Trajet sympa !'
     * ]);
     */
    public function create($driverId, $vehicleId, $data)
    {
        // Vérifier que tous les champs obligatoires sont là
        $required = ['departure_city', 'arrival_city', 'departure_time', 'price_per_seat', 'available_seats'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Le champ '$field' est obligatoire.");
            }
        }

        // Vérifier que les valeurs sont correctes
        if ($data['price_per_seat'] <= 0) {
            throw new Exception("Le prix par siège doit être positif.");
        }

        if ($data['available_seats'] <= 0 || $data['available_seats'] > 8) {
            throw new Exception("Le nombre de places doit être entre 1 et 8.");
        }

        // Vérifier que le véhicule appartient bien au conducteur
        $stmt = $this->pdo->prepare("SELECT user_id FROM vehicles WHERE id = ?");
        $stmt->execute([$vehicleId]);
        $vehicle = $stmt->fetch();

        if (!$vehicle || $vehicle['user_id'] != $driverId) {
            throw new Exception("Ce véhicule ne vous appartient pas.");
        }

        // Vérifier que la date est dans le futur
        $departureTime = strtotime($data['departure_time']);
        if ($departureTime <= time()) {
            throw new Exception("La date de départ doit être dans le futur.");
        }

        try {
            // Créer le trajet dans la base de données
            $stmt = $this->pdo->prepare("
                INSERT INTO trips (
                    driver_id, vehicle_id, departure_city, arrival_city,
                    departure_time, price_per_seat, available_seats,
                    preferences, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW()
                )
            ");

            $stmt->execute([
                $driverId,
                $vehicleId,
                $data['departure_city'],
                $data['arrival_city'],
                $data['departure_time'],
                $data['price_per_seat'],
                $data['available_seats'],
                $data['preferences'] ?? null
            ]);

            $this->id = $this->pdo->lastInsertId();
            $this->loadById($this->id);

            // Enregistrer dans les logs MongoDB
            logUserActivity($driverId, 'create_trip', "Trajet créé : {$data['departure_city']} → {$data['arrival_city']}");

            return $this->id;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la création du trajet : " . $e->getMessage());
        }
    }

    /**
     * MODIFIER UN TRAJET EXISTANT
     * 
     * Tu peux modifier : departure_city, arrival_city, departure_time, price_per_seat, preferences
     * SEULEMENT si le trajet n'est pas encore passé et qu'il est actif
     */
    public function update($data)
    {
        if (!$this->id) {
            throw new Exception("Aucun trajet chargé.");
        }

        // Vérifier que le trajet peut encore être modifié
        if ($this->data['status'] !== 'active') {
            throw new Exception("Ce trajet ne peut plus être modifié.");
        }

        if (strtotime($this->data['departure_time']) <= time()) {
            throw new Exception("Un trajet passé ne peut pas être modifié.");
        }

        // Construire la requête UPDATE avec seulement les champs autorisés
        $allowedFields = ['departure_city', 'arrival_city', 'departure_time', 'price_per_seat', 'preferences'];
        $updates = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }

        if (empty($updates)) {
            throw new Exception("Aucune donnée à mettre à jour.");
        }

        $params[] = $this->id;

        try {
            $sql = "UPDATE trips SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $this->loadById($this->id);

            logUserActivity($this->data['driver_id'], 'update_trip', "Trajet modifié : ID {$this->id}");

            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour : " . $e->getMessage());
        }
    }

    /**
     * SUPPRIMER UN TRAJET
     * 
     * Attention : on ne supprime pas vraiment, on marque juste comme "deleted"
     * Si des passagers ont réservé, il faut utiliser cancel() à la place
     */
    public function delete()
    {
        if (!$this->id) {
            throw new Exception("Aucun trajet chargé.");
        }

        // Vérifier qu'il n'y a pas de réservations
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE trip_id = ? AND status = 'confirmed'
        ");
        $stmt->execute([$this->id]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Impossible de supprimer : des réservations sont confirmées. Annulez le trajet à la place.");
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE trips 
                SET status = 'deleted', updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$this->id]);

            logUserActivity($this->data['driver_id'], 'delete_trip', "Trajet supprimé : ID {$this->id}");

            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression : " . $e->getMessage());
        }
    }


    // ========== RECHERCHER ET CHARGER DES TRAJETS ==========

    /**
     * CHARGER UN TRAJET PAR SON ID
     * 
     * Récupère toutes les infos du trajet + infos du conducteur + infos du véhicule
     */
    public function loadById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT t.*,
                u.username as driver_name,
                u.phone as driver_phone,
                v.brand, v.model, v.color, v.fuel_type, v.license_plate, v.year
            FROM trips t
            JOIN users u ON t.driver_id = u.id
            LEFT JOIN vehicles v ON t.vehicle_id = v.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trip) {
            throw new Exception("Trajet introuvable.");
        }

        $this->id = $id;
        $this->data = $trip;

        return $this->data;
    }

    /**
     * RECHERCHER DES TRAJETS DISPONIBLES
     * 
     * Utilisation :
     * $trips = Trip::search($pdo, 'Paris', 'Lyon');
     * $trips = Trip::search($pdo, 'Paris', 'Lyon', '2025-07-20');
     * 
     * Retourne tous les trajets actifs qui correspondent + qui sont dans le futur
     */
    public static function search($pdo, $departure, $arrival, $date = null)
    {
        $sql = "
            SELECT t.*, 
                   u.username AS driver_name,
                   v.brand, v.model, v.fuel_type, v.color
            FROM trips t
            JOIN users u ON t.driver_id = u.id
            LEFT JOIN vehicles v ON t.vehicle_id = v.id
            WHERE t.departure_city LIKE ?
              AND t.arrival_city LIKE ?
              AND t.status = 'active'
              AND t.available_seats > 0
              AND t.departure_time > NOW()
        ";

        $params = ["%$departure%", "%$arrival%"];

        // Si une date est précisée, filtrer aussi par date
        if ($date) {
            $sql .= " AND DATE(t.departure_time) = ?";
            $params[] = $date;
        }

        $sql .= " ORDER BY t.departure_time ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * RÉCUPÉRER TOUS LES TRAJETS D'UN UTILISATEUR
     * 
     * Utilisation :
     * $myTrips = Trip::getByUser($pdo, $_SESSION['user_id']);
     * 
     * Retourne les trajets où l'utilisateur est conducteur + compte les réservations
     */
    public static function getByUser($pdo, $userId)
    {
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   v.brand, v.model, v.color,
                   COUNT(DISTINCT b.id) as bookings_count
            FROM trips t
            LEFT JOIN vehicles v ON t.vehicle_id = v.id
            LEFT JOIN bookings b ON t.id = b.trip_id
            WHERE t.driver_id = ?
            GROUP BY t.id
            ORDER BY t.departure_time DESC
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * RÉCUPÉRER TOUS LES TRAJETS ACTIFS (pour admin)
     */
    public static function getActiveTrips($pdo)
    {
        $stmt = $pdo->query("
            SELECT t.*, 
                   u.username as driver_name,
                   v.brand, v.model,
                   COUNT(DISTINCT b.id) as bookings_count
            FROM trips t
            JOIN users u ON t.driver_id = u.id
            LEFT JOIN vehicles v ON t.vehicle_id = v.id
            LEFT JOIN bookings b ON t.id = b.trip_id
            WHERE t.status = 'active'
            GROUP BY t.id
            ORDER BY t.departure_time ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // ========== RÉSERVER UN TRAJET ==========

    /**
     * RÉSERVER UN TRAJET (créer une réservation)
     * 
     * Cette fonction fait AUTOMATIQUEMENT :
     * 1. Vérifie que le passager a assez de crédits
     * 2. Retire les crédits du passager
     * 3. Ajoute les crédits au conducteur
     * 4. Crée la réservation
     * 5. Réduit le nombre de places disponibles
     * 
     * Si une erreur arrive, TOUT est annulé (transaction SQL)
     * 
     * Utilisation :
     * $trip = new Trip($pdo, $tripId);
     * $bookingId = $trip->book($_SESSION['user_id'], 2); // Réserver 2 places
     */
    public function book($passengerId, $seatsCount)
    {
        if (!$this->id) {
            throw new Exception("Aucun trajet chargé.");
        }

        // Vérifications de sécurité
        if ($this->data['driver_id'] == $passengerId) {
            throw new Exception("Vous ne pouvez pas réserver votre propre trajet.");
        }

        if ($this->data['status'] !== 'active') {
            throw new Exception("Ce trajet n'est plus disponible.");
        }

        if (strtotime($this->data['departure_time']) <= time()) {
            throw new Exception("Ce trajet est déjà passé.");
        }

        if ($seatsCount <= 0 || $seatsCount > $this->data['available_seats']) {
            throw new Exception("Nombre de places invalide ou insuffisant.");
        }

        // Calculer le prix total
        $totalPrice = $this->data['price_per_seat'] * $seatsCount;

        // Vérifier que le passager a assez de crédits
        $passenger = new User($this->pdo, $passengerId);
        if ($passenger->getCredits() < $totalPrice) {
            throw new Exception("Crédits insuffisants. Il vous faut $totalPrice crédits.");
        }

        try {
            // TRANSACTION SQL : tout ou rien
            $this->pdo->beginTransaction();

            // 1. Retirer les crédits du passager
            $passenger->removeCredits($totalPrice);

            // 2. Ajouter les crédits au conducteur
            $driver = new User($this->pdo, $this->data['driver_id']);
            $driver->addCredits($totalPrice);

           // 3. Créer la réservation dans la table bookings
            $stmt = $this->pdo->prepare("
                INSERT INTO bookings (
                    trip_id, passenger_id, seats_booked, total_price, status
                ) VALUES (
                    ?, ?, ?, ?, 'confirmed'
                )
            ");

            $stmt->execute([
                $this->id,
                $passengerId,
                $seatsCount,
                $totalPrice
            ]);

            $bookingId = $this->pdo->lastInsertId();

            // 4. Réduire les places disponibles
            $stmt = $this->pdo->prepare("
                UPDATE trips 
                SET available_seats = available_seats - ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$seatsCount, $this->id]);

            // Si tout s'est bien passé, valider la transaction
            $this->pdo->commit();
            
            // AJOUTE CECI : Synchroniser la session maintenant que la transaction est validée
            if (isLoggedIn() && $_SESSION['user_id'] == $passengerId) {
                $passenger->loadById($passengerId);  // Recharger les données
                updateUserCredits($passenger->get('credits'));  // Mettre à jour la session
            }

            // Recharger les données du trajet
            $this->loadById($this->id);

            // Enregistrer dans les logs
            logUserActivity($passengerId, 'book_trip', "Réservation trajet ID {$this->id} : $seatsCount place(s)");

            return $bookingId;
        } catch (Exception $e) {
            // Si erreur, annuler TOUTE la transaction
            $this->pdo->rollBack();
            throw new Exception("Erreur lors de la réservation : " . $e->getMessage());
        }
    }

    /**
     * RÉCUPÉRER TOUTES LES RÉSERVATIONS D'UN TRAJET
     * 
     * Utilisation :
     * $trip = new Trip($pdo, $tripId);
     * $bookings = $trip->getBookings();
     * 
     * Retourne la liste des passagers qui ont réservé ce trajet
     */
    public function getBookings()
    {
        if (!$this->id) {
            throw new Exception("Aucun trajet chargé.");
        }

        $stmt = $this->pdo->prepare("
        SELECT b.*, u.username as passenger_name, u.phone as passenger_phone
        FROM bookings b
        JOIN users u ON b.passenger_id = u.id
        WHERE b.trip_id = ?
        ORDER BY b.id DESC    
    ");
        $stmt->execute([$this->id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    // ========== ANNULER UN TRAJET ==========

    /**
     * ANNULER UN TRAJET COMPLET
     * 
     * Cette fonction fait AUTOMATIQUEMENT :
     * 1. Rembourse TOUS les passagers
     * 2. Retire les crédits du conducteur
     * 3. Annule toutes les réservations
     * 4. Marque le trajet comme "cancelled"
     * 
     * TOUT est fait dans une transaction (tout ou rien)
     * 
     * Utilisation :
     * $trip = new Trip($pdo, $tripId);
     * $trip->cancel();
     */
    public function cancel()
    {
        if (!$this->id) {
            throw new Exception("Aucun trajet chargé.");
        }

        if ($this->data['status'] === 'cancelled') {
            throw new Exception("Ce trajet est déjà annulé.");
        }

        try {
            // TRANSACTION SQL : tout ou rien
            $this->pdo->beginTransaction();

            // 1. Rembourser tous les passagers + retirer au conducteur
            $this->refundAllPassengers();

            // 2. Annuler toutes les réservations
            $this->cancelAllBookings();

            // 3. Marquer le trajet comme annulé
            $stmt = $this->pdo->prepare("
                UPDATE trips 
                SET status = 'cancelled', updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$this->id]);

            // Valider la transaction
            $this->pdo->commit();

            // Recharger les données
            $this->loadById($this->id);

            logUserActivity($this->data['driver_id'], 'cancel_trip', "Trajet annulé : ID {$this->id}");

            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Erreur lors de l'annulation : " . $e->getMessage());
        }
    }

    /**
     * FONCTION PRIVÉE : Rembourser tous les passagers
     * (appelée automatiquement par cancel())
     */
    private function refundAllPassengers()
    {
        // Récupérer toutes les réservations confirmées
        $stmt = $this->pdo->prepare("
            SELECT passenger_id, total_price 
            FROM bookings 
            WHERE trip_id = ? AND status = 'confirmed'
        ");
        $stmt->execute([$this->id]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Rembourser chaque passager
        foreach ($bookings as $booking) {
            $passenger = new User($this->pdo, $booking['passenger_id']);
            $passenger->addCredits($booking['total_price']);
        }

        // Retirer les crédits du conducteur
        if (!empty($bookings)) {
            $totalToRefund = array_sum(array_column($bookings, 'total_price'));
            $driver = new User($this->pdo, $this->data['driver_id']);

            // Si le conducteur a assez de crédits, on retire
            if ($driver->getCredits() >= $totalToRefund) {
                $driver->removeCredits($totalToRefund);
            } else {
                // Sinon on met à 0 (cas limite, ne devrait pas arriver)
                $stmt = $this->pdo->prepare("UPDATE users SET credits = 0 WHERE id = ?");
                $stmt->execute([$this->data['driver_id']]);
            }
        }
    }

    /**
     * FONCTION PRIVÉE : Annuler toutes les réservations
     * (appelée automatiquement par cancel())
     */
    private function cancelAllBookings()
    {
        $stmt = $this->pdo->prepare("
            UPDATE bookings 
            SET status = 'cancelled', updated_at = NOW()
            WHERE trip_id = ? AND status = 'confirmed'
        ");
        $stmt->execute([$this->id]);
    }


    // ========== ACTIONS ADMIN ==========

    /**
     * MASQUER UN TRAJET (action admin)
     * Le trajet n'apparaît plus dans les recherches
     */
    public function hide()
    {
        if (!$this->id) {
            throw new Exception("Aucun trajet chargé.");
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE trips 
                SET status = 'hidden', updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$this->id]);

            $this->loadById($this->id);

            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors du masquage : " . $e->getMessage());
        }
    }

    /**
     * AFFICHER UN TRAJET MASQUÉ (action admin)
     * Le trajet redevient visible dans les recherches
     */
    public function show()
    {
        if (!$this->id) {
            throw new Exception("Aucun trajet chargé.");
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE trips 
                SET status = 'active', updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$this->id]);

            $this->loadById($this->id);

            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'affichage : " . $e->getMessage());
        }
    }


    // ========== RÉCUPÉRER DES INFOS ==========

    /**
     * Obtenir les infos complètes du conducteur
     */
    public function getDriver()
    {
        if (!$this->id) {
            throw new Exception("Aucun trajet chargé.");
        }

        $driver = new User($this->pdo, $this->data['driver_id']);
        return $driver->getData();
    }

    /**
     * Obtenir les infos du véhicule
     */
    public function getVehicle()
    {
        if (!$this->id) {
            throw new Exception("Aucun trajet chargé.");
        }

        $stmt = $this->pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
        $stmt->execute([$this->data['vehicle_id']]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir l'ID du trajet
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Obtenir toutes les données du trajet
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Obtenir une donnée spécifique
     * Exemple : $trip->get('departure_city')
     */
    public function get($key)
    {
        return $this->data[$key] ?? null;
    }
}
