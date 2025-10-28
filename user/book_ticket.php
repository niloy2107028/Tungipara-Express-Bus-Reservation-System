<?php
require_once '../config.php';
require_once '../includes/functions.php';

requireUser();

$schedule_id = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : 0;
$user_id = getCurrentUserId();
$error = '';

// Get user details from database
$sql = "SELECT full_name, email, phone FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch schedule details with JOIN
$sql = "SELECT 
            s.schedule_id, s.travel_date, s.departure_time, s.arrival_time, s.fare, s.available_seats,
            b.bus_id, b.bus_name, b.bus_number, b.bus_type, b.capacity,
            r.route_id, r.origin, r.destination, r.distance_km
        FROM schedules s
        INNER JOIN buses b ON s.bus_id = b.bus_id
        INNER JOIN routes r ON s.route_id = r.route_id
        WHERE s.schedule_id = ? AND s.status = 'scheduled'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('Schedule not found or not available for booking.', 'error');
    header("Location: ../index.php");
    exit();
}

$schedule = $result->fetch_assoc();

// Check if seats available
if ($schedule['available_seats'] <= 0) {
    setFlashMessage('No seats available for this schedule.', 'error');
    header("Location: ../search_results.php");
    exit();
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $passenger_name = sanitize($_POST['passenger_name']);
    $passenger_phone = sanitize($_POST['passenger_phone']);

    // Validation
    if (empty($passenger_name) || empty($passenger_phone)) {
        $error = 'Please fill in all required fields';
    } elseif (!isValidPhone($passenger_phone)) {
        $error = 'Invalid phone number format';
    } else {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // Generate booking reference
            $booking_reference = generateBookingReference();

            // Insert booking - single table e shob kichu
            $sql = "INSERT INTO bookings (schedule_id, user_id, passenger_name, passenger_email, passenger_phone, booking_reference, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissss", $schedule_id, $user_id, $passenger_name, $user['email'], $passenger_phone, $booking_reference);
            $stmt->execute();

            // Manually update available seats
            $conn->query("UPDATE schedules SET available_seats = available_seats - 1 WHERE schedule_id = $schedule_id");

            // Commit transaction
            $conn->commit();

            setFlashMessage('Ticket booked successfully! Booking Reference: ' . $booking_reference . '. Fare: ৳' . number_format($schedule['fare'], 0) . ' (Pay on arrival)', 'success');
            header("Location: dashboard.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Booking failed. Please try again.';
        }
    }
}

?>
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bus-front"></i> Bus Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4><?php echo htmlspecialchars($schedule['bus_name']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($schedule['bus_number']); ?></p>
                            <p><span class="badge bg-info"><?php echo htmlspecialchars($schedule['bus_type']); ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <h5>
                                <?php echo htmlspecialchars($schedule['origin']); ?>
                                <i class="bi bi-arrow-right"></i>
                                <?php echo htmlspecialchars($schedule['destination']); ?>
                            </h5>
                            <p><i class="bi bi-calendar3"></i> <?php echo formatDate($schedule['travel_date']); ?></p>
                            <p><i class="bi bi-clock"></i> <?php echo formatTime($schedule['departure_time']); ?> - <?php echo formatTime($schedule['arrival_time']); ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Available Seats:</strong> <span class="text-success"><?php echo $schedule['available_seats']; ?></span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Fare:</strong> <span class="text-primary h5">৳<?php echo number_format($schedule['fare'], 0); ?></span></p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted small">Pay on arrival</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person-fill"></i> Passenger Details</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="passenger_name" class="form-label">Full Name *</label>
                            <input type="text"
                                class="form-control"
                                id="passenger_name"
                                name="passenger_name"
                                value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="passenger_phone" class="form-label">Phone Number *</label>
                            <input type="tel"
                                class="form-control"
                                id="passenger_phone"
                                name="passenger_phone"
                                value="<?php echo htmlspecialchars($user['phone']); ?>"
                                pattern="01[3-9]\d{8}"
                                required>

                        </div>



                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle"></i> Confirm Booking
                            </button>
                            <a href="../search_results.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>