<?php
require_once '../config.php';
require_once '../includes/functions.php';

requireAdmin();

// Auto-regenerate schedules if needed (maintains 7-day rolling window)
require_once '../includes/auto_regenerate_schedules.php';

$success = '';
$error = '';

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $schedule_id = intval($_GET['id']);

    // Check if schedule has bookings
    $sql = "SELECT COUNT(*) as count FROM bookings WHERE schedule_id = ? AND status = 'confirmed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $has_bookings = $stmt->get_result()->fetch_assoc()['count'] > 0;

    if ($has_bookings) {
        $error = 'Cannot delete schedule with confirmed bookings.';
    } else {
        $sql = "DELETE FROM schedules WHERE schedule_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $schedule_id);

        if ($stmt->execute()) {
            $success = 'Schedule deleted successfully!';
        } else {
            $error = 'Failed to delete schedule.';
        }
    }
}

// Handle Deactivate (mark as inactive and cancel bookings)
if (isset($_GET['deactivate']) && isset($_GET['id'])) {
    $schedule_id = intval($_GET['id']);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Cancel all confirmed bookings for this schedule
        $sql = "UPDATE bookings SET status = 'cancelled', cancellation_time = NOW() 
                WHERE schedule_id = ? AND status = 'confirmed'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();

        // Mark schedule as inactive
        $sql = "UPDATE schedules SET status = 'inactive' WHERE schedule_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();

        $conn->commit();
        $success = 'Schedule deactivated and all bookings cancelled successfully!';
    } catch (Exception $e) {
        $conn->rollback();
        $error = 'Failed to deactivate schedule: ' . $e->getMessage();
    }
}

// Handle Activate (reactivate inactive schedule)
if (isset($_GET['activate']) && isset($_GET['id'])) {
    $schedule_id = intval($_GET['id']);

    $sql = "UPDATE schedules SET status = 'scheduled' WHERE schedule_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schedule_id);

    if ($stmt->execute()) {
        $success = 'Schedule activated successfully!';
    } else {
        $error = 'Failed to activate schedule.';
    }
}

// Search functionality
$search_origin = isset($_GET['search_origin']) ? sanitize($_GET['search_origin']) : '';
$search_destination = isset($_GET['search_destination']) ? sanitize($_GET['search_destination']) : '';
$search_date = isset($_GET['search_date']) ? sanitize($_GET['search_date']) : '';

// Fetch schedules with complex INNER JOIN
$sql = "SELECT 
            s.*,
            b.bus_number,
            b.bus_name,
            b.bus_type,
            b.capacity,
            r.origin,
            r.destination,
            r.distance_km,
            (b.capacity - s.available_seats) as booked_seats,
            COUNT(bk.booking_id) as total_bookings,
            SUM(CASE WHEN bk.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings
        FROM schedules s
        INNER JOIN buses b ON s.bus_id = b.bus_id
        INNER JOIN routes r ON s.route_id = r.route_id
        LEFT JOIN bookings bk ON s.schedule_id = bk.schedule_id
        WHERE s.travel_date >= CURDATE()";

if (!empty($search_origin)) {
    $sql .= " AND r.origin = '" . $conn->real_escape_string($search_origin) . "'";
}

if (!empty($search_destination)) {
    $sql .= " AND r.destination = '" . $conn->real_escape_string($search_destination) . "'";
}

if (!empty($search_date)) {
    $sql .= " AND s.travel_date = '" . $conn->real_escape_string($search_date) . "'";
}

$sql .= " GROUP BY s.schedule_id
        ORDER BY s.travel_date, s.departure_time";
$schedules = $conn->query($sql);

// Fetch unique origins and destinations for dropdowns
$origins_list = $conn->query("SELECT DISTINCT origin FROM routes ORDER BY origin");
$destinations_list = $conn->query("SELECT DISTINCT destination FROM routes ORDER BY destination");

// Fetch unique dates from schedules for dropdown
$dates_list = $conn->query("SELECT DISTINCT travel_date FROM schedules WHERE travel_date >= CURDATE() ORDER BY travel_date");

?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid my-4">
    <?php include 'includes/admin_nav_cards.php'; ?>

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
            <h2>Weekly Schedules (Auto-Generated)</h2>
            <span class="text-muted">
                <i class="bi bi-info-circle"></i> Schedules automatically maintained for the next 7 days
            </span>
        </div>
        <div class="col text-end">
            <a href="master_schedules.php" class="btn btn-outline-primary">
                <i class="bi bi-gear"></i> Manage Master Schedules
            </a>
            <a href="master_schedules.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Search Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="search_origin" class="form-label">Origin</label>
                    <select class="form-select" id="search_origin" name="search_origin">
                        <option value="">All Origins</option>
                        <?php while ($org = $origins_list->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($org['origin']); ?>"
                                <?php echo $search_origin == $org['origin'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($org['origin']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="search_destination" class="form-label">Destination</label>
                    <select class="form-select" id="search_destination" name="search_destination">
                        <option value="">All Destinations</option>
                        <?php while ($dest = $destinations_list->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($dest['destination']); ?>"
                                <?php echo $search_destination == $dest['destination'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dest['destination']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="search_date" class="form-label">Date</label>
                    <select class="form-select" id="search_date" name="search_date">
                        <option value="">All Dates</option>
                        <?php while ($date = $dates_list->fetch_assoc()): ?>
                            <option value="<?php echo $date['travel_date']; ?>"
                                <?php echo $search_date == $date['travel_date'] ? 'selected' : ''; ?>>
                                <?php echo formatDate($date['travel_date']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Schedules Table -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Route</th>
                    <th>Bus</th>
                    <th>Date</th>
                    <th>Departure</th>
                    <th>Arrival</th>
                    <th>Fare</th>
                    <th>Available</th>
                    <th>Bookings</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($schedules->num_rows > 0): ?>
                    <?php while ($schedule = $schedules->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $schedule['schedule_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($schedule['origin']); ?></strong> →
                                <strong><?php echo htmlspecialchars($schedule['destination']); ?></strong>
                                <br><small class="text-muted"><?php echo $schedule['distance_km']; ?> km</small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($schedule['bus_number']); ?><br>
                                <small class="text-muted"><?php echo $schedule['bus_type']; ?></small>
                            </td>
                            <td>
                                <?php echo formatDate($schedule['travel_date']); ?><br>
                                <small class="text-muted"><?php echo date('l', strtotime($schedule['travel_date'])); ?></small>
                            </td>
                            <td><?php echo date('g:i A', strtotime($schedule['departure_time'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($schedule['arrival_time'])); ?></td>
                            <td><?php echo formatCurrency($schedule['fare']); ?></td>
                            <td>
                                <span class="badge <?php echo $schedule['available_seats'] > 10 ? 'bg-success' : ($schedule['available_seats'] > 0 ? 'bg-warning' : 'bg-danger'); ?>">
                                    <?php echo $schedule['available_seats']; ?> / <?php echo $schedule['capacity']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($schedule['confirmed_bookings'] > 0): ?>
                                    <span class="badge bg-info"><?php echo $schedule['confirmed_bookings']; ?> confirmed</span>
                                <?php else: ?>
                                    <span class="text-muted">No bookings</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_badge = [
                                    'scheduled' => 'bg-success',
                                    'inactive' => 'bg-secondary',
                                    'departed' => 'bg-warning',
                                    'arrived' => 'bg-info',
                                    'cancelled' => 'bg-danger'
                                ];
                                $status_text = [
                                    'scheduled' => 'Active',
                                    'inactive' => 'Inactive',
                                    'departed' => 'Departed',
                                    'arrived' => 'Arrived',
                                    'cancelled' => 'Cancelled'
                                ];
                                ?>
                                <span class="badge <?php echo $status_badge[$schedule['status']] ?? 'bg-secondary'; ?>">
                                    <?php echo $status_text[$schedule['status']] ?? ucfirst($schedule['status']); ?>
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <?php if ($schedule['status'] === 'scheduled'): ?>
                                    <a href="?deactivate=1&id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-outline-warning" title="Deactivate" onclick="return confirm('Deactivate this schedule? All confirmed bookings will be cancelled.')"><i class="bi bi-x-circle"></i></a>
                                <?php elseif ($schedule['status'] === 'inactive'): ?>
                                    <a href="?activate=1&id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-outline-success" title="Activate" onclick="return confirm('Activate this schedule?')"><i class="bi bi-toggle-on"></i></a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled title="Cannot modify"><i class="bi bi-lock"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center">
                            No schedules found. <a href="master_schedules.php">Create master schedules</a> to auto-generate weekly schedules.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>