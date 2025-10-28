<?php

// Database setup korar file
// Ei file run korle database create hobe

// Error dekhabe jate debug kora jabe
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);
ini_set('memory_limit', '256M');

// Database er info
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bus_reservation_system');

// MySQL er sathe connect korlam
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Connection thik ache ki na check
if ($conn->connect_error) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Connection failed: " . $conn->connect_error;
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo "MySQL connected!\n";
flush();
//sathe sathe print korbe so debug easy 

$errors = [];

// Database create korchi
echo "Database create korchi...\n";
flush();
if (! $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME)) {
    $errors[] = "Error creating database: " . $conn->error;
}

// Database select korlam
$conn->select_db(DB_NAME);

// Purano tables gulo delete korchi
echo "Purano tables delete korchi...\n";
flush();
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
//jehutu delete korbo apatoto foreign key bhule jao
$conn->query("DROP TABLE IF EXISTS bookings");
$conn->query("DROP TABLE IF EXISTS schedules");
$conn->query("DROP TABLE IF EXISTS master_schedules");
$conn->query("DROP TABLE IF EXISTS routes");
$conn->query("DROP TABLE IF EXISTS buses");
$conn->query("DROP TABLE IF EXISTS users");

// Database notun kore banalam
echo "Database notun kore banachi...\n";
flush();
$conn->query("DROP DATABASE IF EXISTS " . DB_NAME);
$conn->query("CREATE DATABASE " . DB_NAME);
$conn->select_db(DB_NAME);

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Charset ar timezone set korlam
$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+06:00'");

echo "Tables create korchi...\n";
flush();

// Tables gulo create korchi

$tables = [
    // Users er table
    "CREATE TABLE users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(15),
        full_name VARCHAR(100) NOT NULL,
        user_type ENUM('admin', 'user') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_user_type (user_type)
    )" => "users",

    // Buses er table
    "CREATE TABLE buses (
        bus_id INT AUTO_INCREMENT PRIMARY KEY,
        bus_number VARCHAR(20) UNIQUE NOT NULL,
        bus_name VARCHAR(100) NOT NULL,
        capacity INT NOT NULL CHECK (capacity > 0),
        bus_type ENUM('AC', 'Non-AC') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_bus_number (bus_number)
    )" => "buses",

    // Routes er table
    "CREATE TABLE routes (
        route_id INT AUTO_INCREMENT PRIMARY KEY,
        origin VARCHAR(100) NOT NULL,
        destination VARCHAR(100) NOT NULL,
        distance_km DECIMAL(10, 2) NOT NULL CHECK (distance_km > 0),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_origin (origin),
        INDEX idx_destination (destination),
        INDEX idx_route_search (origin, destination),
        CONSTRAINT unique_route UNIQUE (origin, destination)
        -- ei pair unique hve
    )" => "routes",

    // Master Schedules table - ki din e bus jabe oi pattern
    "CREATE TABLE master_schedules (
        master_schedule_id INT AUTO_INCREMENT PRIMARY KEY,
        bus_id INT NOT NULL,
        route_id INT NOT NULL,
        day_of_week ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
        departure_time TIME NOT NULL,
        arrival_time TIME NOT NULL,
        fare DECIMAL(10, 2) NOT NULL CHECK (fare > 0),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (bus_id) REFERENCES buses(bus_id) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE CASCADE ON UPDATE CASCADE,
        INDEX idx_day_of_week (day_of_week),
        INDEX idx_bus_day (bus_id, day_of_week),
        CONSTRAINT unique_bus_day UNIQUE (bus_id, day_of_week),
        CONSTRAINT unique_master_schedule UNIQUE (bus_id, route_id, day_of_week, departure_time)
    )" => "master_schedules",

    // Schedules table - actual date wise schedule
    "CREATE TABLE schedules (
        schedule_id INT AUTO_INCREMENT PRIMARY KEY,
        master_schedule_id INT NULL,
        bus_id INT NOT NULL,
        route_id INT NOT NULL,
        departure_time TIME NOT NULL,
        arrival_time TIME NOT NULL,
        travel_date DATE NOT NULL,
        available_seats INT NOT NULL,
        fare DECIMAL(10, 2) NOT NULL CHECK (fare > 0),
        status ENUM('scheduled', 'inactive', 'departed', 'arrived', 'cancelled') DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (master_schedule_id) REFERENCES master_schedules(master_schedule_id) ON DELETE SET NULL ON UPDATE CASCADE,
        FOREIGN KEY (bus_id) REFERENCES buses(bus_id) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE CASCADE ON UPDATE CASCADE,
        INDEX idx_travel_date (travel_date),
        INDEX idx_status (status),
        INDEX idx_bus_schedule (bus_id, travel_date),
        INDEX idx_master_schedule (master_schedule_id),
        CONSTRAINT unique_bus_schedule UNIQUE (bus_id, travel_date, departure_time)
    )" => "schedules",

    // Bookings table - single table for all booking info
    "CREATE TABLE bookings (
        booking_id INT AUTO_INCREMENT PRIMARY KEY,
        schedule_id INT NOT NULL,
        user_id INT NOT NULL,
        passenger_name VARCHAR(100) NOT NULL,
        passenger_email VARCHAR(100) NOT NULL,
        passenger_phone VARCHAR(15) NOT NULL,
        seat_number VARCHAR(10) NULL,
        booking_reference VARCHAR(20) UNIQUE NOT NULL,
        booking_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
        cancellation_time TIMESTAMP NULL,
        FOREIGN KEY (schedule_id) REFERENCES schedules(schedule_id) ON DELETE RESTRICT ON UPDATE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
        INDEX idx_booking_reference (booking_reference),
        INDEX idx_user_bookings (user_id, booking_time),
        INDEX idx_schedule_bookings (schedule_id, status)
    )" => "bookings"
];

foreach ($tables as $sql => $tableName) {
    echo "Table create korchi: $tableName...\n";
    flush();
    if (! $conn->query($sql)) {
        $errors[] = "Error creating table '$tableName': " . $conn->error;
    }
}

// Setup complete hoye gese - result dekhabo
header('Content-Type: text/plain; charset=utf-8');
if (count($errors) === 0) {
    echo "DATABASE SETUP SHESH HOYE GESE!\n";
    echo "Database '" . DB_NAME . "' ready hoy gese sob tables shometo.\n";
    echo "Tables: 6 ta (users, buses, routes, master_schedules, schedules, bookings)\n";
    echo "\nSimplified structure - single bookings table!\n";
    echo "Removed: passengers table (merged into bookings)\n";
    echo "\nEkhon insert_demo_data.php run korte paren demo data insert korar jonno.\n";
} else {
    echo "SETUP COMPLETE KINTU KICHU ERROR ACHE\n";
    foreach ($errors as $err) {
        echo "- " . $err . "\n";
    }
}

$conn->close();
