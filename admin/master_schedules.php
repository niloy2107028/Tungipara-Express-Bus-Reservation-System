<?php
require_once '../config.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = "Master Schedules (Pattern-Based)";

// Handle schedule creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $bus_id = sanitize($_POST['bus_id']);
    // sanitize function defination includes e ase 
    $route_id = sanitize($_POST['route_id']);
    $day_of_week = sanitize($_POST['day_of_week']);
    $departure_time = sanitize($_POST['departure_time']);
    $arrival_time = sanitize($_POST['arrival_time']);
    $fare = sanitize($_POST['fare']);

    // Validate bus is not already assigned to this day
    $check_stmt = $conn->prepare("SELECT master_schedule_id FROM master_schedules WHERE bus_id = ? AND day_of_week = ?");
    $check_stmt->bind_param("is", $bus_id, $day_of_week);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "This bus is already assigned to {$day_of_week}. Each bus can only be assigned to one schedule per day.";
    } else {
        $stmt = $conn->prepare("INSERT INTO master_schedules (bus_id, route_id, day_of_week, departure_time, arrival_time, fare) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssd", $bus_id, $route_id, $day_of_week, $departure_time, $arrival_time, $fare);

        if ($stmt->execute()) {
            // Weekly schedules generate korchi manually
            for ($i = 0; $i < 7; $i++) {
                $target_date = date('Y-m-d', strtotime("+$i days"));
                $check_day = date('l', strtotime($target_date));

                if ($check_day === $day_of_week) {
                    // Ei bus er capacity nibo
                    $capacity_result = $conn->query("SELECT capacity FROM buses WHERE bus_id = $bus_id");
                    $capacity = $capacity_result->fetch_assoc()['capacity'];

                    // Schedule insert korbo if already nei
                    $check_existing = $conn->query("SELECT 1 FROM schedules WHERE bus_id = $bus_id AND travel_date = '$target_date' AND departure_time = '$departure_time'");
                    // select 1 kno data return korbena just check korbe data ase kina thakle 1 return korbe 
                    if ($check_existing->num_rows == 0) {
                        $conn->query("INSERT INTO schedules (master_schedule_id, bus_id, route_id, departure_time, arrival_time, travel_date, available_seats, fare, status) 
                                     VALUES (LAST_INSERT_ID(), $bus_id, $route_id, '$departure_time', '$arrival_time', '$target_date', $capacity, $fare, 'scheduled')");
                    }
                }
            }
            $_SESSION['success'] = "Master schedule created successfully! Weekly schedules auto-generated.";
        } else {
            $_SESSION['error'] = "Error creating schedule: " . $conn->error;
        }
        $stmt->close();
    }
    $check_stmt->close();
    header('Location: master_schedules.php');
    exit;
}

// Handle schedule deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM master_schedules WHERE master_schedule_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Master schedule deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting schedule: " . $conn->error;
    }
    $stmt->close();
    header('Location: master_schedules.php');
    exit;
}

// Status toggle removed - master schedules can only be edited or deleted

// Search functionality
$search_day = isset($_GET['search_day']) ? $_GET['search_day'] : '';
$search_bus = isset($_GET['search_bus']) ? $_GET['search_bus'] : '';
$search_route = isset($_GET['search_route']) ? $_GET['search_route'] : '';

// Get all master schedules with search filters
$schedules_query = "
    SELECT 
        ms.*,
        r.origin,
        r.destination,
        r.distance_km,
        b.bus_name,
        b.bus_number,
        b.bus_type,
        b.capacity
    FROM master_schedules ms
    JOIN routes r ON ms.route_id = r.route_id
    JOIN buses b ON ms.bus_id = b.bus_id
    WHERE 1=1";

if ($search_day) {
    $schedules_query .= " AND ms.day_of_week = '" . $conn->real_escape_string($search_day) . "'";
}
if ($search_bus) {
    $schedules_query .= " AND ms.bus_id = " . intval($search_bus);
}
if ($search_route) {
    $schedules_query .= " AND ms.route_id = " . intval($search_route);
}

$schedules_query .= " ORDER BY 
        FIELD(ms.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
        ms.departure_time";

$schedules = $conn->query($schedules_query);

// Get buses for dropdown
$buses = $conn->query("SELECT bus_id, bus_name, bus_number, bus_type, capacity FROM buses ORDER BY bus_number");

// Get routes for dropdown
$routes = $conn->query("SELECT route_id, origin, destination, distance_km FROM routes ORDER BY origin, destination");

require_once '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <?php include 'includes/admin_nav_cards.php'; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Master Schedules (Pattern-Based)</h2>
                    <span class="text-muted small">
                        <i class="bi bi-info-circle"></i> Weekly schedules are auto-generated from these patterns
                    </span>
                </div>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                    <i class="bi bi-plus-circle"></i> Add Master Schedule
                </button>
            </div>

            <!-- Search Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Search Master Schedules</h5>
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Day of Week</label>
                            <select name="search_day" class="form-select">
                                <option value="">All Days</option>
                                <option value="Sunday" <?php echo $search_day === 'Sunday' ? 'selected' : ''; ?>>Sunday</option>
                                <option value="Monday" <?php echo $search_day === 'Monday' ? 'selected' : ''; ?>>Monday</option>
                                <option value="Tuesday" <?php echo $search_day === 'Tuesday' ? 'selected' : ''; ?>>Tuesday</option>
                                <option value="Wednesday" <?php echo $search_day === 'Wednesday' ? 'selected' : ''; ?>>Wednesday</option>
                                <option value="Thursday" <?php echo $search_day === 'Thursday' ? 'selected' : ''; ?>>Thursday</option>
                                <option value="Friday" <?php echo $search_day === 'Friday' ? 'selected' : ''; ?>>Friday</option>
                                <option value="Saturday" <?php echo $search_day === 'Saturday' ? 'selected' : ''; ?>>Saturday</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bus</label>
                            <select name="search_bus" class="form-select">
                                <option value="">All Buses</option>
                                <?php
                                $buses_search = $conn->query("SELECT bus_id, bus_number, bus_name FROM buses ORDER BY bus_number");
                                while ($bus = $buses_search->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $bus['bus_id']; ?>" <?php echo $search_bus == $bus['bus_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($bus['bus_number'] . ' - ' . $bus['bus_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Route</label>
                            <select name="search_route" class="form-select">
                                <option value="">All Routes</option>
                                <?php
                                $routes_search = $conn->query("SELECT route_id, origin, destination FROM routes ORDER BY origin, destination");
                                while ($route = $routes_search->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $route['route_id']; ?>" <?php echo $search_route == $route['route_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($route['origin'] . ' → ' . $route['destination']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <a href="master_schedules.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Route</th>
                            <th>Bus</th>
                            <th>Departure</th>
                            <th>Arrival</th>
                            <th>Fare</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($schedules->num_rows > 0): ?>
                            <?php while ($schedule = $schedules->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge bg-primary"><?php echo $schedule['day_of_week']; ?></span></td>
                                    <td><?php echo htmlspecialchars($schedule['origin']) . ' → ' . htmlspecialchars($schedule['destination']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($schedule['bus_number']); ?><br>
                                        <small class="text-muted"><?php echo $schedule['bus_type']; ?> (<?php echo $schedule['capacity']; ?> seats)</small>
                                    </td>
                                    <td><?php echo date('g:i A', strtotime($schedule['departure_time'])); ?></td>
                                    <td><?php echo date('g:i A', strtotime($schedule['arrival_time'])); ?></td>
                                    <td><?php echo formatCurrency($schedule['fare']); ?></td>
                                    <td>
                                        <a href="edit_master_schedule.php?id=<?php echo $schedule['master_schedule_id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?delete=<?php echo $schedule['master_schedule_id']; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Delete"
                                            onclick="return confirm('Delete this master schedule? This will also remove all generated weekly schedules.')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No master schedules found. Add one to get started!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Master Schedule Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Add Master Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Day of Week *</label>
                        <select name="day_of_week" class="form-select" required>
                            <option value="">Select Day</option>
                            <option value="Sunday">Sunday</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bus *</label>
                        <select name="bus_id" class="form-select" required>
                            <option value="">Select Bus</option>
                            <?php while ($bus = $buses->fetch_assoc()): ?>
                                <option value="<?php echo $bus['bus_id']; ?>">
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
                                <option value="<?php echo $route['route_id']; ?>">
                                    <?php echo htmlspecialchars($route['origin']) . ' → ' . htmlspecialchars($route['destination']) . ' (' . $route['distance_km'] . ' km)'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Departure Time *</label>
                            <input type="time" name="departure_time" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Arrival Time *</label>
                            <input type="time" name="arrival_time" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fare (BDT) *</label>
                        <input type="number" name="fare" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Master Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>