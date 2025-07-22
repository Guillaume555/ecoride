-- Structure de la base de données EcoRide
-- Ce fichier crée les tables principales du projet (sans données).

-- Créer la base de données
CREATE DATABASE IF NOT EXISTS ecoride;

USE ecoride;

-- TABLE 1: USERS (Utilisateurs)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    credits INT DEFAULT 20,
    role ENUM(
        'passenger',
        'driver',
        'admin'
    ) DEFAULT 'passenger',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- TABLE 2: VEHICLES (Véhicules)
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    color VARCHAR(30),
    license_plate VARCHAR(20) UNIQUE,
    seats INT NOT NULL,
    fuel_type ENUM(
        'essence',
        'diesel',
        'électrique',
        'hybride'
    ) DEFAULT 'essence',
    year YEAR,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- TABLE 3: TRIPS (Trajets)
CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    departure_city VARCHAR(100) NOT NULL,
    arrival_city VARCHAR(100) NOT NULL,
    departure_time DATETIME NOT NULL,
    available_seats INT NOT NULL,
    price_per_seat DECIMAL(5, 2) NOT NULL,
    preferences TEXT,
    status ENUM(
        'active',
        'completed',
        'cancelled'
    ) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE
);

-- TABLE 4: BOOKINGS (Réservations)
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    passenger_id INT NOT NULL,
    seats_booked INT DEFAULT 1,
    total_price DECIMAL(6, 2) NOT NULL,
    status ENUM(
        'pending',
        'confirmed',
        'cancelled'
    ) DEFAULT 'pending',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    FOREIGN KEY (trip_id) REFERENCES trips (id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES users (id) ON DELETE CASCADE
);

-- TABLE 5: REVIEWS (Avis)
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewed_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_validated BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (trip_id) REFERENCES trips (id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_id) REFERENCES users (id) ON DELETE CASCADE
);

-- INDEX POUR PERFORMANCES
CREATE INDEX idx_trips_cities ON trips (departure_city, arrival_city);

CREATE INDEX idx_trips_date ON trips (departure_time);

CREATE INDEX idx_bookings_trip ON bookings (trip_id);

CREATE INDEX idx_reviews_user ON reviews (reviewed_id);