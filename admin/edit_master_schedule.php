<?php
session_start();
require_once '../config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$pageTitle = "Edit Master Schedule";

// Get schedule ID
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Schedule ID not provided";
    header('Location: master_schedules.php');
    exit;
}

$schedule_id = (int)$_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bus_id = sanitize($_POST['bus_id']);
    $route_id = sanitize($_POST['route_id']);
    $day_of_week = sanitize($_POST['day_of_week']);
    $departure_time = sanitize($_POST['departure_time']);
    $arrival_time = sanitize($_POST['arrival_time']);
    $fare = sanitize($_POST['fare']);
    $status = sanitize($_POST['status']);

    // Validate bus is not already assigned to this day (excluding current schedule)
    $check_stmt = $conn->prepare("SELECT master_schedule_id FROM master_schedules WHERE bus_id = ? AND day_of_week = ? AND master_schedule_id != ?");
    $check_stmt->bind_param("isi", $bus_id, $day_of_week, $schedule_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "This bus is already assigned to {$day_of_week}. Each bus can only be assigned to one schedule per day.";
    } else {
        $stmt = $conn->prepare("UPDATE master_schedules SET bus_id=?, route_id=?, day_of_week=?, departure_time=?, arrival_time=?, fare=?, status=? WHERE master_schedule_id=?");
        $stmt->bind_param("iisssdsi", $bus_id, $route_id, $day_of_week, $departure_time, $arrival_time, $fare, $status, $schedule_id);

        if ($stmt->execute()) {
            // Regenerate weekly schedules
            $conn->query("CALL generate_weekly_schedules()");
            $_SESSION['success'] = "Master schedule updated successfully! Weekly schedules regenerated.";
            header('Location: master_schedules.php');
            exit;
        } else {
            $_SESSION['error'] = "Error updating schedule: " . $conn->error;
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Get schedule data
$stmt = $conn->prepare("SELECT * FROM master_schedules WHERE master_schedule_id = ?");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Schedule not found";
    header('Location: master_schedules.php');
    exit;
}

$schedule = $result->fetch_assoc();
$stmt->close();

// Get buses for dropdown
$buses = $conn->query("SELECT bus_id, bus_name, bus_number, bus_type, capacity FROM buses ORDER BY bus_number");

// Get routes for dropdown
$routes = $conn->query("SELECT route_id, origin, destination, distance_km FROM routes ORDER BY origin, destination");

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Master Schedule</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Day of Week *</label>
                            <select name="day_of_week" class="form-select" required>
                                <option value="">Select Day</option>
                                <?php
                                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                foreach ($days as $day):
                                ?>
                                    <option value="<?php echo $day; ?>" <?php echo ($schedule['day_of_week'] === $day) ? 'selected' : ''; ?>>
                                        <?php echo $day; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bus *</label>
                            <select name="bus_id" class="form-select" required>
                                <option value="">Select Bus</option>
                                <?php while ($bus = $buses->fetch_assoc()): ?>
                                    <option value="<?php echo $bus['bus_id']; ?>" <?php echo ($schedule['bus_id'] == $bus['bus_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($bus['bus_number']) . ' - ' . htmlspecialchars($bus['bus_name']) . ' (' . $bus['bus_type'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Each bus can only be assigned to one schedule per day</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Route *</label>
                            <select name="route_id" class="form-select" required>
                                <option value="">Select Route</option>
                                <?php while ($route = $routes->fetch_assoc()): ?>
                                    <option value="<?php echo $route['route_id']; ?>" <?php echo ($schedule['route_id'] == $route['route_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($route['origin']) . ' → ' . htmlspecialchars($route['destination']) . ' (' . $route['distance_km'] . ' km)'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Departure Time *</label>
                                <input type="time" name="departure_time" class="form-control" value="<?php echo $schedule['departure_time']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Arrival Time *</label>
                                <input type="time" name="arrival_time" class="form-control" value="<?php echo $schedule['arrival_time']; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fare (BDT) *</label>
                            <input type="number" name="fare" class="form-control" step="0.01" min="0" value="<?php echo $schedule['fare']; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="active" <?php echo ($schedule['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($schedule['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="master_schedules.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Master Schedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>