<?php

// Demo data insert korar file
// Ei file run korle dummy data database e dhukbe

// Database er info
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bus_reservation_system');

// Database connect korlam
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Connection check korchi
if ($conn->connect_error) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Connection failed: " . $conn->connect_error;
    exit;
}

$conn->set_charset("utf8mb4");
$errors = [];

// Purano data gulo clear korchi
$conn->query("DELETE FROM bookings");
$conn->query("DELETE FROM schedules");
$conn->query("DELETE FROM master_schedules");
$conn->query("DELETE FROM routes");
$conn->query("DELETE FROM buses");
$conn->query("DELETE FROM users");

// Auto increment reset korchi
$conn->query("ALTER TABLE bookings AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE schedules AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE master_schedules AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE routes AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE buses AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE users AUTO_INCREMENT = 1");

// Users insert korchi - password shobai 1234

$users = [
    ['admin', '1234', 'admin@tungiparabus.com', '01700000000', 'Admin', 'admin'],
    ['niloy', '1234', 'niloy@example.com', '01711111111', 'Shoaib Hasan Niloy', 'user'],
    ['ayesha', '1234', 'ayesha@example.com', '01722222222', 'Ayesha Khan', 'user'],
    ['imran', '1234', 'imran@example.com', '01733333333', 'Imran Hassan', 'user'],
    ['fatima', '1234', 'fatima@example.com', '01744444444', 'Fatima Begum', 'user'],
    ['karim', '1234', 'karim@example.com', '01755555555', 'Karim Uddin', 'user']
];

$stmt = $conn->prepare("INSERT INTO users (username, password, email, phone, full_name, user_type) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($users as $user) {
    $stmt->bind_param("ssssss", $user[0], $user[1], $user[2], $user[3], $user[4], $user[5]);
    if ($stmt->execute()) {
        // Insert hoye gese
        //1 return hbe
    } else {
        $errors[] = "Error inserting user '{$user[0]}': " . $stmt->error;
    }
}
$stmt->close();

// Buses insert korchi - Tungipara Express er fleet

$buses = [
    ['TNG-DHK-001', 'Tungipara Express', 40, 'AC'],
    ['TNG-DHK-002', 'Tungipara Express', 45, 'AC'],
    ['TNG-DHK-003', 'Tungipara Express', 40, 'Non-AC'],
    ['TNG-DHK-004', 'Tungipara Express', 36, 'AC'],
    ['TNG-DHK-005', 'Tungipara Express', 40, 'AC'],
    ['TNG-DHK-006', 'Tungipara Express', 42, 'AC'],
    ['TNG-DHK-007', 'Tungipara Express', 38, 'Non-AC'],
    ['TNG-DHK-008', 'Tungipara Express', 40, 'AC'],
    ['TNG-DHK-009', 'Tungipara Express', 40, 'AC'],
    ['TNG-DHK-010', 'Tungipara Express', 35, 'Non-AC'],
    ['TNG-DHK-011', 'Tungipara Express', 40, 'AC'],
    ['TNG-DHK-012', 'Tungipara Express', 45, 'AC'],
    ['TNG-DHK-013', 'Tungipara Express', 38, 'Non-AC'],
    ['TNG-DHK-014', 'Tungipara Express', 40, 'Non-AC'],
    ['TNG-DHK-015', 'Tungipara Express', 42, 'AC']
];

$stmt = $conn->prepare("INSERT INTO buses (bus_number, bus_name, capacity, bus_type) VALUES (?, ?, ?, ?)");
foreach ($buses as $bus) {
    $stmt->bind_param("ssis", $bus[0], $bus[1], $bus[2], $bus[3]);
    if ($stmt->execute()) {
        // Bus insert hoye gese
    } else {
        $errors[] = "Error inserting bus '{$bus[1]}': " . $stmt->error;
    }
}
$stmt->close();

// Routes insert korchi - Tungipara Express er major routes

$routes = [
    ['Dhaka', 'Chittagong', 264.00],
    ['Dhaka', 'Sylhet', 244.00],
    ['Dhaka', 'Rajshahi', 256.00],
    ['Dhaka', 'Khulna', 334.00],
    ['Dhaka', 'Rangpur', 318.00],
    ['Dhaka', 'Barisal', 214.00],
    ['Dhaka', 'Cox\'s Bazar', 400.00],
    ['Dhaka', 'Mymensingh', 120.00],
    ['Chittagong', 'Dhaka', 264.00],
    ['Chittagong', 'Sylhet', 308.00],
    ['Chittagong', 'Cox\'s Bazar', 152.00],
    ['Sylhet', 'Dhaka', 244.00],
    ['Sylhet', 'Chittagong', 308.00],
    ['Rajshahi', 'Dhaka', 256.00],
    ['Khulna', 'Dhaka', 334.00],
    ['Rangpur', 'Dhaka', 318.00],
    ['Barisal', 'Dhaka', 214.00],
    ['Cox\'s Bazar', 'Dhaka', 400.00],
    ['Mymensingh', 'Dhaka', 120.00]
];

$stmt = $conn->prepare("INSERT INTO routes (origin, destination, distance_km) VALUES (?, ?, ?)");
foreach ($routes as $route) {
    $stmt->bind_param("ssd", $route[0], $route[1], $route[2]);
    if ($stmt->execute()) {
        // Route insert hoye gese
    } else {
        $errors[] = "Error inserting route '{$route[0]} → {$route[1]}': " . $stmt->error;
    }
}
$stmt->close();

// Master schedules insert korchi - ki din e bus chale

// [bus_id, route_id, day_of_week, departure, arrival, fare]
$masterSchedules = [
    // Sunday er schedules
    [1, 1, 'Sunday', '08:00:00', '14:00:00', 1200.00],    // Bus 1: Dhaka → Chittagong
    [2, 9, 'Sunday', '08:00:00', '14:00:00', 1200.00],    // Bus 2: Chittagong → Dhaka
    [3, 2, 'Sunday', '07:00:00', '12:30:00', 900.00],     // Bus 3: Dhaka → Sylhet
    [4, 3, 'Sunday', '08:30:00', '14:30:00', 950.00],     // Bus 4: Dhaka → Rajshahi
    [5, 4, 'Sunday', '09:00:00', '16:30:00', 1100.00],    // Bus 5: Dhaka → Khulna
    [6, 7, 'Sunday', '06:00:00', '15:00:00', 1500.00],    // Bus 6: Dhaka → Cox's Bazar
    [7, 5, 'Sunday', '07:30:00', '14:30:00', 1000.00],    // Bus 7: Dhaka → Rangpur
    [8, 6, 'Sunday', '08:00:00', '13:00:00', 800.00],     // Bus 8: Dhaka → Barisal
    [9, 10, 'Sunday', '16:00:00', '23:00:00', 1100.00],   // Bus 9: Chittagong → Sylhet
    [10, 11, 'Sunday', '09:00:00', '13:00:00', 600.00],   // Bus 10: Chittagong → Cox's Bazar
    [11, 12, 'Sunday', '14:00:00', '19:30:00', 900.00],   // Bus 11: Sylhet → Dhaka
    [12, 18, 'Sunday', '16:00:00', '01:00:00', 1500.00],  // Bus 12: Cox's Bazar → Dhaka
    [13, 15, 'Sunday', '16:00:00', '23:30:00', 1100.00],  // Bus 13: Khulna → Dhaka
    [14, 14, 'Sunday', '15:00:00', '21:00:00', 950.00],   // Bus 14: Rajshahi → Dhaka
    [15, 16, 'Sunday', '16:00:00', '23:00:00', 1000.00],  // Bus 15: Rangpur → Dhaka

    // Monday er schedules
    [1, 1, 'Monday', '22:00:00', '04:00:00', 1100.00],    // Bus 1: Dhaka → Chittagong 
    [2, 1, 'Monday', '09:30:00', '15:30:00', 1400.00],    // Bus 2: Dhaka → Chittagong
    [3, 8, 'Monday', '13:00:00', '16:00:00', 500.00],     // Bus 3: Dhaka → Mymensingh
    [4, 14, 'Monday', '22:00:00', '04:00:00', 900.00],    // Bus 4: Rajshahi → Dhaka 
    [5, 15, 'Monday', '22:30:00', '06:00:00', 1050.00],   // Bus 5: Khulna → Dhaka 
    [6, 11, 'Monday', '16:00:00', '20:00:00', 580.00],    // Bus 6: Chittagong → Cox's Bazar
    [7, 13, 'Monday', '08:00:00', '15:00:00', 1100.00],   // Bus 7: Sylhet → Chittagong
    [8, 17, 'Monday', '14:00:00', '19:00:00', 800.00],    // Bus 8: Barisal → Dhaka
    [9, 9, 'Monday', '21:00:00', '03:00:00', 1100.00],    // Bus 9: Chittagong → Dhaka 
    [10, 9, 'Monday', '08:00:00', '14:00:00', 1200.00],   // Bus 10: Chittagong → Dhaka
    [11, 2, 'Monday', '23:00:00', '04:30:00', 850.00],    // Bus 11: Dhaka → Sylhet 
    [12, 7, 'Monday', '19:00:00', '04:00:00', 1400.00],   // Bus 12: Dhaka → Cox's Bazar 
    [13, 4, 'Monday', '21:00:00', '04:30:00', 1050.00],   // Bus 13: Dhaka → Khulna 
    [14, 19, 'Monday', '07:00:00', '10:00:00', 480.00],   // Bus 14: Mymensingh → Dhaka
    [15, 5, 'Monday', '20:30:00', '03:30:00', 950.00],    // Bus 15: Dhaka → Rangpur 

    // Tuesday er schedules
    [1, 6, 'Tuesday', '15:00:00', '20:00:00', 750.00],    // Bus 1: Dhaka → Barisal
    [2, 10, 'Tuesday', '18:00:00', '01:00:00', 1050.00],  // Bus 2: Chittagong → Sylhet
    [3, 12, 'Tuesday', '22:00:00', '03:30:00', 850.00],   // Bus 3: Sylhet → Dhaka 
    [4, 3, 'Tuesday', '20:00:00', '02:00:00', 900.00],    // Bus 4: Dhaka → Rajshahi 
    [5, 4, 'Tuesday', '09:00:00', '16:30:00', 1100.00],   // Bus 5: Dhaka → Khulna
    [6, 18, 'Tuesday', '21:00:00', '06:00:00', 1400.00],  // Bus 6: Cox's Bazar → Dhaka 
    [7, 16, 'Tuesday', '22:00:00', '05:00:00', 950.00],   // Bus 7: Rangpur → Dhaka 
    [8, 19, 'Tuesday', '20:00:00', '23:00:00', 500.00],   // Bus 8: Mymensingh → Dhaka
    [9, 1, 'Tuesday', '08:00:00', '14:00:00', 1200.00],   // Bus 9: Dhaka → Chittagong
    [10, 11, 'Tuesday', '09:00:00', '13:00:00', 600.00],  // Bus 10: Chittagong → Cox's Bazar
    [11, 8, 'Tuesday', '17:00:00', '20:00:00', 480.00],   // Bus 11: Dhaka → Mymensingh
    [12, 7, 'Tuesday', '06:00:00', '15:00:00', 1500.00],  // Bus 12: Dhaka → Cox's Bazar
    [13, 15, 'Tuesday', '16:00:00', '23:30:00', 1100.00], // Bus 13: Khulna → Dhaka
    [14, 14, 'Tuesday', '15:00:00', '21:00:00', 950.00],  // Bus 14: Rajshahi → Dhaka
    [15, 13, 'Tuesday', '19:00:00', '02:00:00', 1050.00], // Bus 15: Sylhet → Chittagong 

    // Wednesday er schedules
    [1, 17, 'Wednesday', '21:00:00', '02:00:00', 750.00],
    [2, 1, 'Wednesday', '09:30:00', '15:30:00', 1400.00],
    [3, 2, 'Wednesday', '07:00:00', '12:30:00', 900.00],
    [4, 3, 'Wednesday', '08:30:00', '14:30:00', 950.00],
    [5, 15, 'Wednesday', '22:30:00', '06:00:00', 1050.00],
    [6, 7, 'Wednesday', '06:00:00', '15:00:00', 1500.00],
    [7, 5, 'Wednesday', '07:30:00', '14:30:00', 1000.00],
    [8, 6, 'Wednesday', '08:00:00', '13:00:00', 800.00],
    [9, 10, 'Wednesday', '16:00:00', '23:00:00', 1100.00],
    [10, 9, 'Wednesday', '08:00:00', '14:00:00', 1200.00],
    [11, 12, 'Wednesday', '14:00:00', '19:30:00', 900.00],
    [12, 18, 'Wednesday', '16:00:00', '01:00:00', 1500.00],
    [13, 4, 'Wednesday', '09:00:00', '16:30:00', 1100.00],
    [14, 19, 'Wednesday', '07:00:00', '10:00:00', 480.00],
    [15, 16, 'Wednesday', '16:00:00', '23:00:00', 1000.00],

    // Thursday er schedules
    [1, 1, 'Thursday', '08:00:00', '14:00:00', 1200.00],
    [2, 9, 'Thursday', '21:00:00', '03:00:00', 1100.00],
    [3, 8, 'Thursday', '13:00:00', '16:00:00', 500.00],
    [4, 14, 'Thursday', '15:00:00', '21:00:00', 950.00],
    [5, 4, 'Thursday', '09:00:00', '16:30:00', 1100.00],
    [6, 11, 'Thursday', '16:00:00', '20:00:00', 580.00],
    [7, 13, 'Thursday', '08:00:00', '15:00:00', 1100.00],
    [8, 17, 'Thursday', '14:00:00', '19:00:00', 800.00],
    [9, 9, 'Thursday', '08:00:00', '14:00:00', 1200.00],
    [10, 11, 'Thursday', '09:00:00', '13:00:00', 600.00],
    [11, 2, 'Thursday', '23:00:00', '04:30:00', 850.00],
    [12, 7, 'Thursday', '19:00:00', '04:00:00', 1400.00],
    [13, 15, 'Thursday', '16:00:00', '23:30:00', 1100.00],
    [14, 3, 'Thursday', '20:00:00', '02:00:00', 900.00],
    [15, 5, 'Thursday', '20:30:00', '03:30:00', 950.00],

    // Friday er schedules
    [1, 1, 'Friday', '22:00:00', '04:00:00', 1100.00],
    [2, 1, 'Friday', '09:30:00', '15:30:00', 1400.00],
    [3, 12, 'Friday', '14:00:00', '19:30:00', 900.00],
    [4, 3, 'Friday', '08:30:00', '14:30:00', 950.00],
    [5, 15, 'Friday', '22:30:00', '06:00:00', 1050.00],
    [6, 18, 'Friday', '21:00:00', '06:00:00', 1400.00],
    [7, 16, 'Friday', '22:00:00', '05:00:00', 950.00],
    [8, 6, 'Friday', '08:00:00', '13:00:00', 800.00],
    [9, 10, 'Friday', '16:00:00', '23:00:00', 1100.00],
    [10, 9, 'Friday', '21:00:00', '03:00:00', 1100.00],
    [11, 8, 'Friday', '17:00:00', '20:00:00', 480.00],
    [12, 7, 'Friday', '06:00:00', '15:00:00', 1500.00],
    [13, 4, 'Friday', '21:00:00', '04:30:00', 1050.00],
    [14, 14, 'Friday', '22:00:00', '04:00:00', 900.00],
    [15, 5, 'Friday', '07:30:00', '14:30:00', 1000.00],

    // Saturday er schedules
    [1, 6, 'Saturday', '15:00:00', '20:00:00', 750.00],
    [2, 10, 'Saturday', '18:00:00', '01:00:00', 1050.00],
    [3, 2, 'Saturday', '07:00:00', '12:30:00', 900.00],
    [4, 14, 'Saturday', '15:00:00', '21:00:00', 950.00],
    [5, 4, 'Saturday', '09:00:00', '16:30:00', 1100.00],
    [6, 7, 'Saturday', '06:00:00', '15:00:00', 1500.00],
    [7, 5, 'Saturday', '07:30:00', '14:30:00', 1000.00],
    [8, 17, 'Saturday', '21:00:00', '02:00:00', 750.00],
    [9, 1, 'Saturday', '08:00:00', '14:00:00', 1200.00],
    [10, 11, 'Saturday', '09:00:00', '13:00:00', 600.00],
    [11, 12, 'Saturday', '22:00:00', '03:30:00', 850.00],
    [12, 18, 'Saturday', '16:00:00', '01:00:00', 1500.00],
    [13, 15, 'Saturday', '16:00:00', '23:30:00', 1100.00],
    [14, 19, 'Saturday', '20:00:00', '23:00:00', 500.00],
    [15, 13, 'Saturday', '19:00:00', '02:00:00', 1050.00],
];

$masterScheduleCount = 0;
$stmt = $conn->prepare("INSERT INTO master_schedules (bus_id, route_id, day_of_week, departure_time, arrival_time, fare) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($masterSchedules as $ms) {
    $stmt->bind_param("iisssd", $ms[0], $ms[1], $ms[2], $ms[3], $ms[4], $ms[5]);
    if ($stmt->execute()) {
        // Master schedule insert hoye gese
        $masterScheduleCount++;
    } else {
        // Skip duplicates silently
        if (strpos($stmt->error, 'Duplicate entry') === false) {
            $errors[] = "Error inserting master schedule: " . $stmt->error;
        }
    }
}
$stmt->close();

// Master schedule theke manually schedules generate korchi (ager 7 din er jonno)
$scheduleCount = 0;
for ($i = 0; $i < 7; $i++) {
    $target_date = date('Y-m-d', strtotime("+$i days"));
    $day_name = date('l', strtotime($target_date)); // Sunday, Monday, etc.

    // Master schedule theke oi din er schedule gulo insert korbo
    $sql = "INSERT INTO schedules (master_schedule_id, bus_id, route_id, departure_time, arrival_time, travel_date, available_seats, fare, status)
            SELECT 
                ms.master_schedule_id,
                ms.bus_id,
                ms.route_id,
                ms.departure_time,
                ms.arrival_time,
                '$target_date',
                b.capacity,
                ms.fare,
                'scheduled'
            FROM master_schedules ms
            JOIN buses b ON ms.bus_id = b.bus_id
            -- join kortesi bus capacity janar jonno 
            WHERE ms.day_of_week = '$day_name'
            AND NOT EXISTS (
                -- already same bus same date and time er jonno inserted kina check 1 hole insert hbe na
                SELECT 1 FROM schedules s 
                WHERE s.bus_id = ms.bus_id 
                AND s.travel_date = '$target_date' 
                AND s.departure_time = ms.departure_time
            )";

    if ($conn->query($sql)) {
        $scheduleCount += $conn->affected_rows;
    }
}

// Koto schedule create hoise count korchi
$totalSchedules = $conn->query("SELECT COUNT(*) as count FROM schedules")->fetch_assoc()['count'];

// Sample bookings insert korchi

// Kichhu schedule ID nichi bookings korar jonno
$sampleScheduleIds = [];
$result = $conn->query("SELECT schedule_id FROM schedules WHERE travel_date >= CURDATE() ORDER BY travel_date, departure_time LIMIT 4");
while ($row = $result->fetch_assoc()) {
    $sampleScheduleIds[] = $row['schedule_id'];
}

// Sample passengers ar tickets
$sampleBookings = [
    ['Rahul Ahmed', 'rahul.ahmed@example.com', '01711111111'],
    ['Ayesha Khan', 'ayesha.khan@example.com', '01722222222'],
    ['Imran Hassan', 'imran.hassan@example.com', '01733333333'],
    ['Fatima Begum', 'fatima.begum@example.com', '01744444444']
];

$bookingCount = 0;
foreach ($sampleBookings as $index => $booking) {
    if (!isset($sampleScheduleIds[$index])) break;

    $userId = $index + 2; // Users 2-5 (admin na)
    $scheduleId = $sampleScheduleIds[$index];
    $bookingRef = 'BRS' . date('Ymd') . str_pad(($index + 1), 5, '0', STR_PAD_LEFT);

    // Booking insert korchi - single table e shob kichu
    $stmt = $conn->prepare("INSERT INTO bookings (schedule_id, user_id, passenger_name, passenger_email, passenger_phone, booking_reference, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iissss", $scheduleId, $userId, $booking[0], $booking[1], $booking[2], $bookingRef);

    if ($stmt->execute()) {
        $bookingCount++;
    }
    $stmt->close();
}

// Shob insert shesh - result dekhabo
header('Content-Type: text/plain; charset=utf-8');
if (count($errors) === 0) {
    echo "TUNGIPARA BUS RESERVATION - DEMO DATA INSERT COMPLETE!\n\n";
    echo "SIMPLIFIED STRUCTURE:\n";
    echo "- Single bookings table (no separate passengers table)\n";
    echo "- Master Schedules: {$masterScheduleCount} ta (din onujayi pattern)\n";
    echo "- Weekly Schedules: {$totalSchedules} ta (ager 7 din er jonno)\n";
    echo "- Ekta bus din e ekbar chale\n\n";
    echo "ONNANNO DATA:\n";
    echo "- Users: 6 jon (admin + 5 demo user)\n";
    echo "- Buses: 15 ta (shob Tungipara Express)\n";
    echo "- Routes: 19 ta\n";
    echo "- Sample Bookings: {$bookingCount} ta\n\n";
    echo "LOGIN KORAR INFO:\n";
    echo "Admin: admin / 1234\n";
    echo "Users: rahul, ayesha, imran, fatima, karim / 1234\n\n";
    echo "Note: Password shobai 1234 (prototype tai simple rakhchi)\n";
    echo "Master Schedules manage korle bus kon din chale sheyta control kora jabe!\n";
} else {
    echo "DATA INSERT COMPLETE KINTU KICHU ERROR ACHE\n";
    foreach ($errors as $err) {
        echo "- " . $err . "\n";
    }
}

$conn->close();
