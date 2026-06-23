<?php

/**
 * CLASSE VEHICLE - GESTION DES VÉHICULES
 * 
 * Cette classe gère tout ce qui concerne les véhicules :
 * - Ajouter un véhicule
 * - Modifier les infos d'un véhicule
 * - Supprimer un véhicule
 * - Récupérer les véhicules d'un utilisateur
 * - Vérifier la propriété d'un véhicule
 * - Valider les données (plaque d'immatriculation, etc.)
 * 
 * Projet EcoRide - ECF DWWM 2025
 */

class Vehicle
{

    // Variables privées (protégées)
    private $pdo;        // Connexion base de données
    private $id;         // ID du véhicule actuellement chargé
    private $data;       // Toutes les infos du véhicule


    // ========== CONSTRUCTEUR ==========

    /**
     * Créer un objet Vehicle
     * Si tu donnes un ID, il charge automatiquement les infos du véhicule
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


    // ========== CRÉER / MODIFIER UN VÉHICULE ==========

    /**
     * AJOUTER UN NOUVEAU VÉHICULE
     * 
     * Utilisation :
     * $vehicle = new Vehicle($pdo);
     * $vehicleId = $vehicle->create($_SESSION['user_id'], [
     *     'brand' => 'Renault',
     *     'model' => 'Clio',
     *     'color' => 'Bleu',
     *     'license_plate' => 'AB-123-CD',
     *     'fuel_type' => 'Hybride',
     *     'seats' => 4
     * ]);
     */
    public function create($userId, $data)
    {
        // Vérifier que tous les champs obligatoires sont là
        $required = ['brand', 'model', 'color', 'license_plate', 'fuel_type', 'seats'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Le champ '$field' est obligatoire.");
            }
        }

        // Valider la plaque d'immatriculation
        $this->validateLicensePlate($data['license_plate']);

        // Vérifier que la plaque n'existe pas déjà
        $stmt = $this->pdo->prepare("SELECT id FROM vehicles WHERE license_plate = ?");
        $stmt->execute([$data['license_plate']]);

        if ($stmt->fetch()) {
            throw new Exception("Cette plaque d'immatriculation est déjà enregistrée.");
        }

        // Vérifier le nombre de places
        if ($data['seats'] < 1 || $data['seats'] > 8) {
            throw new Exception("Le nombre de places doit être entre 1 et 8.");
        }

        try {
            // Créer le véhicule dans la base de données
            $stmt = $this->pdo->prepare("
                INSERT INTO vehicles (
                    user_id, brand, model, color, license_plate,
                    fuel_type, seats, year, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                )
            ");

            $stmt->execute([
                $userId,
                $data['brand'],
                $data['model'],
                $data['color'],
                strtoupper($data['license_plate']),
                $data['fuel_type'],
                $data['seats'],
                $data['year'] ?? null
            ]);

            $this->id = $this->pdo->lastInsertId();
            $this->loadById($this->id);

            // Enregistrer dans les logs MongoDB
            logUserActivity($userId, 'add_vehicle', "Véhicule ajouté : {$data['brand']} {$data['model']}");

            return $this->id;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'ajout du véhicule : " . $e->getMessage());
        }
    }

    /**
     * MODIFIER UN VÉHICULE EXISTANT
     * 
     * Tu peux modifier : brand, model, color, fuel_type, seats, year
     * IMPORTANT : Tu ne peux modifier que TES véhicules
     */
    public function update($data, $userId)
    {
        if (!$this->id) {
            throw new Exception("Aucun véhicule chargé.");
        }

        // Vérifier que le véhicule appartient bien à l'utilisateur
        if ($this->data['user_id'] != $userId) {
            throw new Exception("Ce véhicule ne vous appartient pas.");
        }

        // Construire la requête UPDATE avec seulement les champs autorisés
        $allowedFields = ['brand', 'model', 'color', 'fuel_type', 'seats', 'year'];
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
            $sql = "UPDATE vehicles SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $this->loadById($this->id);

            logUserActivity($userId, 'update_vehicle', "Véhicule modifié : ID {$this->id}");

            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour : " . $e->getMessage());
        }
    }

    /**
     * SUPPRIMER UN VÉHICULE
     * 
     * ATTENTION : Tu ne peux supprimer un véhicule que si :
     * - Il t'appartient
     * - Il n'a pas de trajets actifs
     */
    public function delete($userId)
    {
        if (!$this->id) {
            throw new Exception("Aucun véhicule chargé.");
        }

        // Vérifier la propriété
        if ($this->data['user_id'] != $userId) {
            throw new Exception("Ce véhicule ne vous appartient pas.");
        }

        // Vérifier qu'il n'y a pas de trajets actifs avec ce véhicule
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM trips 
            WHERE vehicle_id = ? AND status = 'active'
        ");
        $stmt->execute([$this->id]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Impossible de supprimer : ce véhicule a des trajets actifs.");
        }

        try {
            // Supprimer le véhicule
            $stmt = $this->pdo->prepare("DELETE FROM vehicles WHERE id = ?");
            $stmt->execute([$this->id]);

            logUserActivity($userId, 'delete_vehicle', "Véhicule supprimé : ID {$this->id}");

            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression : " . $e->getMessage());
        }
    }


    // ========== RÉCUPÉRER DES VÉHICULES ==========

    /**
     * CHARGER UN VÉHICULE PAR SON ID
     * 
     * Récupère toutes les infos du véhicule
     */
    public function loadById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vehicle) {
            throw new Exception("Véhicule introuvable.");
        }

        $this->id = $id;
        $this->data = $vehicle;

        return $this->data;
    }

    /**
     * RÉCUPÉRER TOUS LES VÉHICULES D'UN UTILISATEUR
     * 
     * Utilisation :
     * $myVehicles = Vehicle::getByUser($pdo, $_SESSION['user_id']);
     * 
     * Retourne la liste de tous tes véhicules
     */
    public static function getByUser($pdo, $userId)
    {
        $stmt = $pdo->prepare("
            SELECT v.*,
                   COUNT(DISTINCT t.id) as active_trips_count
            FROM vehicles v
            LEFT JOIN trips t ON v.id = t.vehicle_id AND t.status = 'active'
            WHERE v.user_id = ?
            GROUP BY v.id
            ORDER BY v.created_at DESC
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // ========== VALIDATION ET VÉRIFICATION ==========

    /**
     * VALIDER UNE PLAQUE D'IMMATRICULATION
     * 
     * Format accepté : AB-123-CD ou AB123CD
     * (format français)
     */
    private function validateLicensePlate($plate)
    {
        // Retirer les espaces et tirets
        $cleanPlate = str_replace([' ', '-'], '', $plate);

        // Vérifier le format : 2 lettres, 3 chiffres, 2 lettres
        if (!preg_match('/^[A-Z]{2}[0-9]{3}[A-Z]{2}$/i', $cleanPlate)) {
            throw new Exception("Format de plaque d'immatriculation invalide. Format attendu : AB-123-CD");
        }

        return true;
    }

    /**
     * VÉRIFIER SI UN VÉHICULE APPARTIENT À UN UTILISATEUR
     * 
     * Utilisation :
     * if (Vehicle::checkOwnership($pdo, $userId, $vehicleId)) {
     *     // OK, c'est son véhicule
     * }
     */
    public static function checkOwnership($pdo, $userId, $vehicleId)
    {
        $stmt = $pdo->prepare("SELECT user_id FROM vehicles WHERE id = ?");
        $stmt->execute([$vehicleId]);
        $vehicle = $stmt->fetch();

        if (!$vehicle) {
            return false;
        }

        return $vehicle['user_id'] == $userId;
    }


    // ========== INFORMATIONS UTILES ==========

    /**
     * VÉRIFIER SI UN VÉHICULE EST ÉCOLOGIQUE
     * 
     * Retourne true si le véhicule est hybride ou électrique
     */
    public function isEcological()
    {
        if (!$this->id) {
            throw new Exception("Aucun véhicule chargé.");
        }

        $ecologicalTypes = ['Électrique', 'Hybride', 'Hybride rechargeable'];
        return in_array($this->data['fuel_type'], $ecologicalTypes);
    }


    // ========== RÉCUPÉRER DES INFOS ==========

    /**
     * Obtenir l'ID du véhicule
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Obtenir toutes les données du véhicule
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Obtenir une donnée spécifique
     * Exemple : $vehicle->get('brand')
     */
    public function get($key)
    {
        return $this->data[$key] ?? null;
    }
}
