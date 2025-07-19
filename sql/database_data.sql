-- ========================================
-- ECORIDE - DONNÉES DE TEST
-- Fichier: sql/database_data.sql
-- Description: Données de démonstration pour l'ECF
-- ========================================

USE ecoride;

-- ========================================
-- DONNÉES DE TEST - UTILISATEURS
-- ========================================
-- Mots de passe : password123 (hachage à faire lors de l'installation)
INSERT INTO
    users (
        username,
        email,
        password,
        phone,
        credits,
        role
    )
VALUES (
        'demo_passager',
        'demo@passenger.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0123456789',
        25,
        'passenger'
    ),
    (
        'demo_conducteur',
        'demo@driver.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0234567890',
        18,
        'driver'
    ),
    (
        'marie_dupont',
        'marie@email.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0345678901',
        32,
        'passenger'
    ),
    (
        'admin_demo',
        'admin@ecoride.fr',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0456789012',
        100,
        'admin'
    );

-- ========================================
-- DONNÉES DE TEST - VÉHICULES
-- ========================================
INSERT INTO
    vehicles (
        user_id,
        brand,
        model,
        color,
        license_plate,
        seats,
        fuel_type,
        year
    )
VALUES (
        2,
        'Peugeot',
        '308',
        'Blanc',
        'AB-123-CD',
        4,
        'essence',
        2020
    ),
    (
        2,
        'Tesla',
        'Model 3',
        'Noir',
        'EF-456-GH',
        4,
        'électrique',
        2022
    ),
    (
        4,
        'Renault',
        'Clio',
        'Rouge',
        'IJ-789-KL',
        4,
        'essence',
        2019
    );

-- ========================================
-- DONNÉES DE TEST - TRAJETS
-- ========================================
INSERT INTO
    trips (
        driver_id,
        vehicle_id,
        departure_city,
        arrival_city,
        departure_time,
        available_seats,
        price_per_seat,
        preferences,
        status
    )
VALUES (
        2,
        1,
        'Paris',
        'Lyon',
        '2025-07-20 08:00:00',
        3,
        25.00,
        'Non-fumeur, musique autorisée',
        'active'
    ),
    (
        2,
        2,
        'Marseille',
        'Nice',
        '2025-07-21 14:30:00',
        2,
        15.00,
        'Animaux acceptés, trajet écologique',
        'active'
    ),
    (
        4,
        3,
        'Bordeaux',
        'Toulouse',
        '2025-07-22 09:15:00',
        1,
        20.00,
        'Conversation bienvenue',
        'active'
    ),
    (
        2,
        1,
        'Lyon',
        'Paris',
        '2025-07-23 18:00:00',
        4,
        23.00,
        'Non-fumeur, départ ponctuel',
        'active'
    );

-- ========================================
-- DONNÉES DE TEST - RÉSERVATIONS
-- ========================================
INSERT INTO
    bookings (
        trip_id,
        passenger_id,
        seats_booked,
        total_price,
        status,
        payment_status
    )
VALUES (
        1,
        1,
        1,
        25.00,
        'confirmed',
        'paid'
    ),
    (
        2,
        3,
        2,
        30.00,
        'confirmed',
        'paid'
    );

-- ========================================
-- DONNÉES DE TEST - AVIS
-- ========================================
INSERT INTO
    reviews (
        trip_id,
        reviewer_id,
        reviewed_id,
        rating,
        comment,
        is_validated
    )
VALUES (
        1,
        1,
        2,
        5,
        'Excellent conducteur, très ponctuel et véhicule confortable !',
        TRUE
    ),
    (
        2,
        3,
        2,
        4,
        'Trajet agréable, conversation intéressante. Véhicule électrique un plus.',
        TRUE
    );

-- ========================================
-- COMPTES DE DÉMONSTRATION ECF
-- ========================================
/*
IDENTIFIANTS POUR TESTS ECF :

PASSAGER :
- Email: demo@passenger.com
- Mot de passe: password123

CONDUCTEUR :
- Email: demo@driver.com  
- Mot de passe: password123

ADMIN :
- Email: admin@ecoride.fr
- Mot de passe: password123

Tous les mots de passe sont hachés avec password_hash() PHP
*/