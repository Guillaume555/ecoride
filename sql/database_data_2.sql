-- ========================================
-- INSERTION UTILISATEURS (Conducteurs)
-- ========================================
INSERT INTO users (username, email, password, phone, credits, role)
VALUES
('alice_driver', 'alice@example.com', 'password123', '0612345678', 50, 'driver'),
('bob_driver', 'bob@example.com', 'password123', '0698765432', 60, 'driver'),
('carol_driver', 'carol@example.com', 'password123', '0687654321', 70, 'driver'),
('david_driver', 'david@example.com', 'password123', '0676543210', 80, 'driver');

-- ========================================
-- INSERTION VÉHICULES
-- ========================================
INSERT INTO vehicles (user_id, brand, model, color, license_plate, seats, fuel_type, year)
VALUES
(1, 'Tesla', 'Model 3', 'Blanc', 'AA-123-AA', 4, 'électrique', 2022),
(2, 'Peugeot', '308', 'Bleu', 'BB-234-BB', 4, 'diesel', 2020),
(3, 'Renault', 'Clio', 'Rouge', 'CC-345-CC', 3, 'essence', 2019),
(4, 'Toyota', 'Prius', 'Noir', 'DD-456-DD', 4, 'hybride', 2021);

-- ========================================
-- INSERTION TRAJETS
-- ========================================

-- 🚗 Paris → Lyon
INSERT INTO trips (driver_id, vehicle_id, departure_city, arrival_city, departure_time, available_seats, price_per_seat, preferences)
VALUES
(1, 1, 'Paris', 'Lyon', '2025-08-25 08:30:00', 3, 35.00, 'Pas d’animaux'),
(2, 2, 'Paris', 'Lyon', '2025-08-30 14:00:00', 2, 30.00, 'Pause café en route'),
(3, 3, 'Paris', 'Lyon', '2025-09-02 09:00:00', 1, 40.00, 'Silence apprécié');

-- 🚗 Marseille → Nice
INSERT INTO trips (driver_id, vehicle_id, departure_city, arrival_city, departure_time, available_seats, price_per_seat, preferences)
VALUES
(2, 2, 'Marseille', 'Nice', '2025-08-28 10:00:00', 3, 20.00, 'Musique pendant le trajet'),
(3, 3, 'Marseille', 'Nice', '2025-09-03 16:00:00', 2, 18.00, 'Non-fumeur'),
(4, 4, 'Marseille', 'Nice', '2025-09-07 08:30:00', 4, 22.00, 'Animaux acceptés');

-- 🚗 Bordeaux → Toulouse
INSERT INTO trips (driver_id, vehicle_id, departure_city, arrival_city, departure_time, available_seats, price_per_seat, preferences)
VALUES
(1, 1, 'Bordeaux', 'Toulouse', '2025-08-29 07:00:00', 3, 28.00, 'Silence apprécié'),
(2, 2, 'Bordeaux', 'Toulouse', '2025-09-05 13:30:00', 2, 25.00, 'Pause repas en route'),
(4, 4, 'Bordeaux', 'Toulouse', '2025-09-10 18:00:00', 4, 30.00, 'Musique OK');


INSERT INTO trips (driver_id, vehicle_id, departure_city, arrival_city, departure_time, available_seats, price_per_seat, preferences)
VALUES
(
  1,
  (SELECT id FROM vehicles WHERE user_id = 1 LIMIT 1),
  'Paris', 'Lyon', '2025-08-25 08:30:00', 3, 35.00, 'Pas d’animaux'
);
