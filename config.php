<?php
// Database configuration

// Database er info
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bus_reservation_system');

// Application settings
define('SITE_NAME', 'Tungipara Express - Bus Reservation System');
define('SITE_URL', 'http://localhost/bus-reservation-system');

// Session timeout - 1 ghonta
define('SESSION_TIMEOUT', 3600);

// Timezone Bangladesh er
date_default_timezone_set('Asia/Dhaka');

// Error dekhabe - production e off kore dibo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection include korlam
require_once __DIR__ . '/includes/db_connect.php';

// Session start korbo jodi already na kora thake
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
