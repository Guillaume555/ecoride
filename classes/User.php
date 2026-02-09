<?php
/**
 * Classe User - Gestion complète des utilisateurs en POO
 * 
 * Cette classe encapsule toute la logique métier liée aux utilisateurs :
 * - Authentification (register, login, logout)
 * - Gestion du compte (update, delete)
 * - Gestion des crédits (add, remove, get)
 * - Actions admin (ban, unban, update credits)
 * - Statistiques utilisateur (trips, ratings)
 * 
 * @author EcoRide - ECF DWWM 2025
 * @version 1.1 - Correction bug inscription role + crédits
 */

class User {
    
    // ==================== ATTRIBUTS ====================
    
    /**
     * @var PDO Connexion à la base de données
     */
    private $pdo;
    
    /**
     * @var int|null ID de l'utilisateur chargé
     */
    private $id;
    
    /**
     * @var array Données de l'utilisateur
     */
    private $data;
    
    
    // ==================== CONSTRUCTEUR ====================
    
    /**
     * Constructeur de la classe User
     * 
     * @param PDO $pdo Connexion PDO à la base de données
     * @param int|null $id ID de l'utilisateur à charger (optionnel)
     */
    public function __construct($pdo, $id = null) {
        $this->pdo = $pdo;
        $this->id = $id;
        $this->data = [];
        
        // Si un ID est fourni, charger automatiquement les données
        if ($id !== null) {
            $this->loadById($id);
        }
    }
    
    
    // ==================== AUTHENTIFICATION ====================
    
    /**
     * Inscription d'un nouvel utilisateur
     * 
     * @param string $username Nom d'utilisateur
     * @param string $email Email
     * @param string $password Mot de passe (sera hashé)
     * @param string $phone Téléphone (optionnel)
     * @return bool True si inscription réussie
     * @throws Exception Si validation échoue ou email existe déjà
     */
    public function register($username, $email, $password, $phone = null) {
        // Validation des données
        $this->validateEmail($email);
        $this->validatePassword($password);
        
        if (empty($username) || strlen($username) < 3) {
            throw new Exception("Le nom d'utilisateur doit contenir au moins 3 caractères.");
        }
        
        // Vérifier si l'email existe déjà
        if (self::exists($this->pdo, $email)) {
            throw new Exception("Un compte existe déjà avec cet email.");
        }
        
        // Hashage du mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertion en base de données
        try {
            // ✅ CORRECTION : 'passenger' au lieu de 'user', 20 crédits au lieu de 50
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password, phone, credits, role) 
                VALUES (:username, :email, :password, :phone, 20, 'passenger')
            ");
            
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':phone' => $phone
            ]);
            
            // Récupérer l'ID du nouvel utilisateur
            $this->id = $this->pdo->lastInsertId();
            
            // Charger les données complètes
            $this->loadById($this->id);
            
            // Log de l'inscription
            $this->logAction('register', "Nouvel utilisateur inscrit : $email");
            
            return true;
            
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'inscription : " . $e->getMessage());
        }
    }
    
    /**
     * Connexion d'un utilisateur
     * 
     * @param string $email Email
     * @param string $password Mot de passe
     * @return array Données utilisateur si connexion réussie
     * @throws Exception Si identifiants invalides ou compte banni
     */
    public function login($email, $password) {
        // Validation de l'email
        $this->validateEmail($email);
        
        // Récupération de l'utilisateur
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vérifications
        if (!$user) {
            throw new Exception("Identifiants invalides.");
        }
        
        if (!password_verify($password, $user['password'])) {
            throw new Exception("Identifiants invalides.");
        }
        
        if ($user['is_banned']) {
            throw new Exception("Votre compte a été suspendu. Contactez l'administrateur.");
        }
        
        // Charger les données dans l'objet
        $this->id = $user['id'];
        $this->data = $user;
        
        // Créer la session (utilise les helpers existants de session.php)
        loginUser($user);
        
        // Log de la connexion
        $this->logAction('login', "Connexion réussie");
        
        return $this->data;
    }
    
    /**
     * Déconnexion de l'utilisateur
     * 
     * @return bool True si déconnexion réussie
     */
    public function logout() {
        if ($this->id) {
            $this->logAction('logout', "Déconnexion");
        }
        
        // Utilise la fonction helper existante
        logoutUser();
        
        // Reset des données
        $this->id = null;
        $this->data = [];
        
        return true;
    }
    
    
    // ==================== GESTION COMPTE ====================
    
    /**
     * Charger les données d'un utilisateur par son ID
     * 
     * @param int $id ID de l'utilisateur
     * @return array Données de l'utilisateur
     * @throws Exception Si utilisateur introuvable
     */
    public function loadById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("Utilisateur introuvable.");
        }
        
        $this->id = $id;
        $this->data = $user;
        
        return $this->data;
    }
    
    /**
     * Mettre à jour les informations du profil
     * 
     * @param string $username Nouveau nom d'utilisateur
     * @param string $email Nouvel email
     * @param string|null $phone Nouveau téléphone
     * @return bool True si mise à jour réussie
     * @throws Exception Si validation échoue ou email déjà utilisé
     */
    public function update($username, $email, $phone = null) {
        if (!$this->id) {
            throw new Exception("Aucun utilisateur chargé.");
        }
        
        // Validation
        $this->validateEmail($email);
        
        if (empty($username) || strlen($username) < 3) {
            throw new Exception("Le nom d'utilisateur doit contenir au moins 3 caractères.");
        }
        
        // Vérifier si l'email est déjà utilisé par un autre compte
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt->execute([':email' => $email, ':id' => $this->id]);
        
        if ($stmt->fetch()) {
            throw new Exception("Cet email est déjà utilisé par un autre compte.");
        }
        
        // Mise à jour
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET username = :username, email = :email, phone = :phone
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':phone' => $phone,
                ':id' => $this->id
            ]);
            
            // Recharger les données
            $this->loadById($this->id);
            
            // Synchroniser la session si c'est l'utilisateur connecté
            if (isLoggedIn() && $_SESSION['user_id'] == $this->id) {
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
            }
            
            $this->logAction('update_profile', "Profil mis à jour");
            
            return true;
            
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour : " . $e->getMessage());
        }
    }
    
    /**
     * Supprimer le compte utilisateur
     * 
     * @return bool True si suppression réussie
     * @throws Exception Si erreur lors de la suppression
     */
    public function delete() {
        if (!$this->id) {
            throw new Exception("Aucun utilisateur chargé.");
        }
        
        try {
            // Démarrer une transaction (important pour l'intégrité)
            $this->pdo->beginTransaction();
            
            // Annuler tous les trajets actifs de cet utilisateur
            $stmt = $this->pdo->prepare("
                UPDATE trips 
                SET status = 'cancelled' 
                WHERE driver_id = :user_id AND status = 'active'
            ");
            $stmt->execute([':user_id' => $this->id]);
            
            // Annuler toutes les réservations actives
            $stmt = $this->pdo->prepare("
                UPDATE bookings 
                SET status = 'cancelled' 
                WHERE passenger_id = :user_id AND status = 'confirmed'
            ");
            $stmt->execute([':user_id' => $this->id]);
            
            // Supprimer l'utilisateur
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $this->id]);
            
            // Valider la transaction
            $this->pdo->commit();
            
            $this->logAction('delete_account', "Compte supprimé");
            
            // Si c'est l'utilisateur connecté, le déconnecter
            if (isLoggedIn() && $_SESSION['user_id'] == $this->id) {
                $this->logout();
            }
            
            // Reset de l'objet
            $this->id = null;
            $this->data = [];
            
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Erreur lors de la suppression : " . $e->getMessage());
        }
    }
    
    
    // ==================== GESTION CRÉDITS ====================
    
    /**
     * Ajouter des crédits à l'utilisateur
     * 
     * @param int $amount Montant à ajouter (positif)
     * @return int Nouveau solde de crédits
     * @throws Exception Si montant invalide
     */
    public function addCredits($amount) {
        if (!$this->id) {
            throw new Exception("Aucun utilisateur chargé.");
        }
        
        if ($amount <= 0) {
            throw new Exception("Le montant doit être positif.");
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET credits = credits + :amount 
                WHERE id = :id
            ");
            $stmt->execute([':amount' => $amount, ':id' => $this->id]);
            
            // Recharger pour obtenir le nouveau solde
            $this->loadById($this->id);
            
            // Synchroniser la session
            if (isLoggedIn() && $_SESSION['user_id'] == $this->id) {
                updateUserCredits($this->data['credits']);
            }
            
            $this->logAction('add_credits', "Ajout de $amount crédits");
            
            return $this->data['credits'];
            
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'ajout de crédits : " . $e->getMessage());
        }
    }
    
    /**
     * Retirer des crédits à l'utilisateur
     * 
     * @param int $amount Montant à retirer (positif)
     * @return int Nouveau solde de crédits
     * @throws Exception Si solde insuffisant ou montant invalide
     */
    public function removeCredits($amount) {
        if (!$this->id) {
            throw new Exception("Aucun utilisateur chargé.");
        }
        
        if ($amount <= 0) {
            throw new Exception("Le montant doit être positif.");
        }
        
        // Vérifier le solde
        if ($this->data['credits'] < $amount) {
            throw new Exception("Crédits insuffisants. Solde actuel : " . $this->data['credits']);
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET credits = credits - :amount 
                WHERE id = :id
            ");
            $stmt->execute([':amount' => $amount, ':id' => $this->id]);
            
            // Recharger
            $this->loadById($this->id);
            
            // Synchroniser la session
            if (isLoggedIn() && $_SESSION['user_id'] == $this->id) {
                updateUserCredits($this->data['credits']);
            }
            
            $this->logAction('remove_credits', "Retrait de $amount crédits");
            
            return $this->data['credits'];
            
        } catch (PDOException $e) {
            throw new Exception("Erreur lors du retrait de crédits : " . $e->getMessage());
        }
    }
    
    /**
     * Obtenir le solde de crédits de l'utilisateur
     * 
     * @return int Solde actuel
     */
    public function getCredits() {
        if (!$this->id) {
            throw new Exception("Aucun utilisateur chargé.");
        }
        
        return (int) $this->data['credits'];
    }
    
    
    // ==================== ACTIONS ADMIN ====================
    
    /**
     * Bannir l'utilisateur (action admin)
     * 
     * @return bool True si bannissement réussi
     * @throws Exception Si erreur
     */
    public function ban() {
        if (!$this->id) {
            throw new Exception("Aucun utilisateur chargé.");
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET is_banned = 1
                WHERE id = :id
            ");
            $stmt->execute([':id' => $this->id]);
            
            $this->loadById($this->id);
            
            // Si l'utilisateur est connecté, le déconnecter
            if (isLoggedIn() && $_SESSION['user_id'] == $this->id) {
                $this->logout();
            }
            
            $this->logAction('ban', "Utilisateur banni par admin");
            
            return true;
            
        } catch (PDOException $e) {
            throw new Exception("Erreur lors du bannissement : " . $e->getMessage());
        }
    }
    
    /**
     * Débannir l'utilisateur (action admin)
     * 
     * @return bool True si débannissement réussi
     * @throws Exception Si erreur
     */
    public function unban() {
        if (!$this->id) {
            throw new Exception("Aucun utilisateur chargé.");
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET is_banned = 0
                WHERE id = :id
            ");
            $stmt->execute([':id' => $this->id]);
            
            $this->loadById($this->id);
            
            $this->logAction('unban', "Utilisateur débanni par admin");
            
            return true;
            
        } catch (PDOException $e) {
            throw new Exception("Erreur lors du débannissement : " . $e->getMessage());
        }
    }
    
    /**
     * Mettre à jour directement le solde de crédits (action admin)
     * 
     * @param int $newAmount Nouveau montant de crédits
     * @return int Nouveau solde
     * @throws Exception Si montant négatif
     */
    public function updateCredits($newAmount) {
        if (!$this->id) {
            throw new Exception("Aucun utilisateur chargé.");
        }
        
        if ($newAmount < 0) {
            throw new Exception("Le montant ne peut pas être négatif.");
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET credits = :amount
                WHERE id = :id
            ");
            $stmt->execute([':amount' => $newAmount, ':id' => $this->id]);
            
            $this->loadById($this->id);
            
            // Synchroniser la session
            if (isLoggedIn() && $_SESSION['user_id'] == $this->id) {
                updateUserCredits($newAmount);
            }
            
            $this->logAction('update_credits_admin', "Crédits mis à jour par admin : $newAmount");
            
            return $this->data['credits'];
            
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour des crédits : " . $e->getMessage());
        }
    }
    
    
    // ==================== STATISTIQUES ====================
    
    /**
     * Obtenir les trajets où l'utilisateur est conducteur
     * 
     * @return array Liste des trajets
     */
    public function getTripsAsDriver() {
        if (!$this->id) {
            throw new Exception("Aucun utilisateur chargé.");
        }
        
        $stmt = $this->pdo->prepare("
            SELECT t.*, v.brand, v.model, v.color,
                   COUNT(DISTINCT b.id) as bookings_count
            FROM trips t
            LEFT JOIN vehicles v ON t.vehicle_id = v.id
            LEFT JOIN bookings b ON t.id = b.trip_id AND b.status != 'cancelled'
            WHERE t.driver_id = :user_id
            GROUP BY t.id
            ORDER BY t.departure_time DESC
        ");
        $stmt->execute([':user_id' => $this->id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtenir les trajets où l'utilisateur est passager
     * 
     * @return array Liste des réservations
     */
    public function getTripsAsPassenger() {
        if (!$this->id) {
            throw new Exception("Aucun utilisateur chargé.");
        }
        
        $stmt = $this->pdo->prepare("
            SELECT b.*, t.departure_city, t.arrival_city, t.departure_time,
                   u.username as driver_name, v.brand, v.model, v.fuel_type
            FROM bookings b
            JOIN trips t ON b.trip_id = t.id
            JOIN users u ON t.driver_id = u.id
            LEFT JOIN vehicles v ON t.vehicle_id = v.id
            WHERE b.passenger_id = :user_id
            ORDER BY t.departure_time DESC
        ");
        $stmt->execute([':user_id' => $this->id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtenir la note moyenne de l'utilisateur
     * 
     * @return float Note moyenne (0 si aucun avis)
     */
    public function getAverageRating() {
        if (!$this->id) {
            throw new Exception("Aucun utilisateur chargé.");
        }
        
        $stmt = $this->pdo->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
            FROM reviews
            WHERE reviewed_user_id = :user_id AND status = 'validated'
        ");
        $stmt->execute([':user_id' => $this->id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
    }
    
    
    // ==================== MÉTHODES STATIQUES ====================
    
    /**
     * Vérifier si un email existe déjà
     * 
     * @param PDO $pdo Connexion PDO
     * @param string $email Email à vérifier
     * @return bool True si l'email existe
     */
    public static function exists($pdo, $email) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        
        return (bool) $stmt->fetch();
    }
    
    
    // ==================== MÉTHODES PRIVÉES (VALIDATION) ====================
    
    /**
     * Valider le format d'un email
     * 
     * @param string $email Email à valider
     * @throws Exception Si format invalide
     */
    private function validateEmail($email) {
        if (empty($email)) {
            throw new Exception("L'email est obligatoire.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format d'email invalide.");
        }
    }
    
    /**
     * Valider un mot de passe
     * 
     * @param string $password Mot de passe à valider
     * @throws Exception Si mot de passe trop faible
     */
    private function validatePassword($password) {
        if (empty($password)) {
            throw new Exception("Le mot de passe est obligatoire.");
        }
        
        if (strlen($password) < 8) {
            throw new Exception("Le mot de passe doit contenir au moins 8 caractères.");
        }
    }
    
    /**
     * Logger une action utilisateur dans MongoDB
     * 
     * @param string $action Type d'action
     * @param string $details Détails de l'action
     */
    private function logAction($action, $details) {
        if ($this->id) {
            logUserActivity($this->id, $action, $details);
        }
    }
    
    
    // ==================== GETTERS ====================
    
    /**
     * Obtenir l'ID de l'utilisateur
     * 
     * @return int|null ID ou null si non chargé
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Obtenir toutes les données de l'utilisateur
     * 
     * @return array Données complètes
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * Obtenir une donnée spécifique
     * 
     * @param string $key Clé de la donnée
     * @return mixed Valeur ou null si inexistante
     */
    public function get($key) {
        return $this->data[$key] ?? null;
    }
}