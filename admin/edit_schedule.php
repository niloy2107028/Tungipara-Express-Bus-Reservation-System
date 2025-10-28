<?php
require_once '../config.php';
require_once '../includes/functions.php';

requireAdmin();

$success = '';
$error = '';
$schedule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($schedule_id <= 0) {
    header("Location: schedules.php");
    exit();
}

// Fetch schedule details
$sql = "SELECT 
            s.*,
            b.bus_name,
            b.bus_number,
            b.capacity,
            r.origin,
            r.destination
        FROM schedules s
        INNER JOIN buses b ON s.bus_id = b.bus_id
        INNER JOIN routes r ON s.route_id = r.route_id
        WHERE s.schedule_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();

if (!$schedule) {
    header("Location: schedules.php");
    exit();
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_schedule'])) {
    $bus_id = intval($_POST['bus_id']);
    $route_id = intval($_POST['route_id']);
    $departure_time = sanitize($_POST['departure_time']);
    $arrival_time = sanitize($_POST['arrival_time']);
    $travel_date = sanitize($_POST['travel_date']);
    $fare = floatval($_POST['fare']);
    $status = sanitize($_POST['status']);

    // Get bus capacity
    $sql = "SELECT capacity FROM buses WHERE bus_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bus_id);
    $stmt->execute();
    $capacity = $stmt->get_result()->fetch_assoc()['capacity'];

    // Check for duplicate schedule (excluding current schedule)
    $sql = "SELECT schedule_id FROM schedules 
            WHERE bus_id = ? AND travel_date = ? AND departure_time = ? AND schedule_id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $bus_id, $travel_date, $departure_time, $schedule_id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        $error = 'Schedule already exists for this bus on this date and time.';
    } else {
        // Calculate available seats based on bookings
        $sql = "SELECT COUNT(*) as booked FROM bookings WHERE schedule_id = ? AND status = 'confirmed'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $booked = $stmt->get_result()->fetch_assoc()['booked'];
        $available_seats = $capacity - $booked;

        $sql = "UPDATE schedules SET 
                    bus_id = ?, 
                    route_id = ?, 
                    departure_time = ?, 
                    arrival_time = ?, 
                    travel_date = ?, 
                    available_seats = ?, 
                    fare = ?,
                    status = ?
                WHERE schedule_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssidsi", $bus_id, $route_id, $departure_time, $arrival_time, $travel_date, $available_seats, $fare, $status, $schedule_id);

        if ($stmt->execute()) {
            $success = 'Schedule updated successfully!';
            // Refresh schedule data
            $sql = "SELECT 
                        s.*,
                        b.bus_name,
                        b.bus_number,
                        b.capacity,
                        r.origin,
                        r.destination
                    FROM schedules s
                    INNER JOIN buses b ON s.bus_id = b.bus_id
                    INNER JOIN routes r ON s.route_id = r.route_id
                    WHERE s.schedule_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $schedule_id);
            $stmt->execute();
            $schedule = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Failed to update schedule.';
        }
    }
}

// Fetch buses for dropdown
$buses_list = $conn->query("SELECT bus_id, bus_number, bus_name, capacity FROM buses ORDER BY bus_name");

// Fetch routes for dropdown
$routes_list = $conn->query("SELECT route_id, origin, destination FROM routes ORDER BY origin, destination");

?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid my-4">
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-pencil-square"></i> Edit Schedule</h2>
        </div>
        <div class="col text-end">
            <a href="schedules.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Schedules
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bus *</label>
                                <select class="form-select" name="bus_id" required>
                                    <option value="">Select Bus</option>
                                    <?php while ($bus = $buses_list->fetch_assoc()): ?>
                                        <option value="<?php echo $bus['bus_id']; ?>"
                                            <?php echo $schedule['bus_id'] == $bus['bus_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($bus['bus_name'] . ' - ' . $bus['bus_number']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Route *</label>
                                <select class="form-select" name="route_id" required>
                                    <option value="">Select Route</option>
                                    <?php while ($route = $routes_list->fetch_assoc()): ?>
                                        <option value="<?php echo $route['route_id']; ?>"
                                            <?php echo $schedule['route_id'] == $route['route_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($route['origin'] . ' → ' . $route['destination']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Travel Date *</label>
                                <input type="date" class="form-control" name="travel_date"
                                    value="<?php echo $schedule['travel_date']; ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Departure Time *</label>
                                <input type="time" class="form-control" name="departure_time"
                                    value="<?php echo $schedule['departure_time']; ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Arrival Time *</label>
                                <input type="time" class="form-control" name="arrival_time"
                                    value="<?php echo $schedule['arrival_time']; ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fare (BDT) *</label>
                                <input type="number" class="form-control" name="fare"
                                    step="0.01" min="0" value="<?php echo $schedule['fare']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status *</label>
                                <select class="form-select" name="status" required>
                                    <option value="scheduled" <?php echo $schedule['status'] == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="departed" <?php echo $schedule['status'] == 'departed' ? 'selected' : ''; ?>>Departed</option>
                                    <option value="arrived" <?php echo $schedule['status'] == 'arrived' ? 'selected' : ''; ?>>Arrived</option>
                                    <option value="cancelled" <?php echo $schedule['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="update_schedule" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Schedule
                            </button>
                            <a href="schedules.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Schedule Information</h5>
                    <hr>
                    <p><strong>Schedule ID:</strong> <?php echo $schedule['schedule_id']; ?></p>
                    <p><strong>Current Route:</strong><br><?php echo htmlspecialchars($schedule['origin'] . ' → ' . $schedule['destination']); ?></p>
                    <p><strong>Current Bus:</strong><br><?php echo htmlspecialchars($schedule['bus_name'] . ' (' . $schedule['bus_number'] . ')'); ?></p>
                    <p><strong>Available Seats:</strong> <?php echo $schedule['available_seats']; ?> / <?php echo $schedule['capacity']; ?></p>
                    <hr>
                    <p class="text-muted mb-0">
                        <small><i class="bi bi-info-circle"></i> Available seats are automatically calculated based on confirmed bookings.</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>