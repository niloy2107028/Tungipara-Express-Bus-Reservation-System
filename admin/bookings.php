<?php
require_once '../config.php';
require_once '../includes/functions.php';

requireAdmin();

// Handle Cancel Booking
if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $booking_id = intval($_GET['id']);

    try {
        // Booking er current status ar schedule ID nibo
        $check_sql = "SELECT status, schedule_id FROM bookings WHERE booking_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $booking_id);
        $check_stmt->execute();
        $booking_data = $check_stmt->get_result()->fetch_assoc();

        // Booking status update korbo
        $sql = "UPDATE bookings SET status = 'cancelled', cancellation_time = NOW() WHERE booking_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        // Manually seat available baran dibo (trigger remove korechi)
        if ($booking_data['status'] === 'confirmed') {
            $conn->query("UPDATE schedules SET available_seats = available_seats + 1 WHERE schedule_id = " . $booking_data['schedule_id']);
        }

        setFlashMessage('Booking cancelled successfully!', 'success');
        header("Location: bookings.php");
        exit();
    } catch (Exception $e) {
        $error = 'Failed to cancel booking.';
    }
}

// Handle Confirm Booking
if (isset($_GET['confirm']) && isset($_GET['id'])) {
    $booking_id = intval($_GET['id']);

    try {
        // Booking er current status ar schedule ID nibo
        $check_sql = "SELECT status, schedule_id FROM bookings WHERE booking_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $booking_id);
        $check_stmt->execute();
        $booking_data = $check_stmt->get_result()->fetch_assoc();

        // Booking status update korbo
        $sql = "UPDATE bookings SET status = 'confirmed' WHERE booking_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        // Manually seat kom korbo jodi age pending thake (trigger remove korechi)
        if ($booking_data['status'] === 'pending') {
            $conn->query("UPDATE schedules SET available_seats = available_seats - 1 WHERE schedule_id = " . $booking_data['schedule_id']);
        }

        setFlashMessage('Booking confirmed successfully!', 'success');
        header("Location: bookings.php");
        exit();
    } catch (Exception $e) {
        $error = 'Failed to confirm booking.';
    }
}

// Filters
$search_origin = isset($_GET['search_origin']) ? sanitize($_GET['search_origin']) : '';
$search_destination = isset($_GET['search_destination']) ? sanitize($_GET['search_destination']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Simplified query - ekta table theke shobar data
$sql = "SELECT 
            bk.booking_id,
            bk.booking_reference,
            bk.status,
            bk.booking_time,
            bk.cancellation_time,
            bk.passenger_name,
            bk.passenger_email,
            bk.passenger_phone,
            u.user_id,
            u.full_name as user_name,
            u.email as user_email,
            s.schedule_id,
            s.travel_date,
            s.departure_time,
            s.fare,
            b.bus_name,
            b.bus_number,
            r.origin,
            r.destination
        FROM bookings bk
        INNER JOIN users u ON bk.user_id = u.user_id
        INNER JOIN schedules s ON bk.schedule_id = s.schedule_id
        INNER JOIN buses b ON s.bus_id = b.bus_id
        INNER JOIN routes r ON s.route_id = r.route_id
        WHERE 1=1";

// Add origin filter
if (!empty($search_origin)) {
    $sql .= " AND r.origin = '" . $conn->real_escape_string($search_origin) . "'";
}

// Add destination filter
if (!empty($search_destination)) {
    $sql .= " AND r.destination = '" . $conn->real_escape_string($search_destination) . "'";
}

// Add status filter
if ($status_filter != 'all') {
    $sql .= " AND bk.status = '" . $conn->real_escape_string($status_filter) . "'";
}

// Add date range filter
if (!empty($date_from)) {
    $sql .= " AND s.travel_date >= '" . $conn->real_escape_string($date_from) . "'";
}
if (!empty($date_to)) {
    $sql .= " AND s.travel_date <= '" . $conn->real_escape_string($date_to) . "'";
}

$sql .= " ORDER BY bk.booking_time DESC";
$bookings = $conn->query($sql);

// Fetch unique origins and destinations for dropdowns
$origins_list = $conn->query("SELECT DISTINCT origin FROM routes ORDER BY origin");
$destinations_list = $conn->query("SELECT DISTINCT destination FROM routes ORDER BY destination");

?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid my-4">
    <?php include 'includes/admin_nav_cards.php'; ?>

    <?php echo getFlashMessage(); ?>

    <div class="row mb-4">
        <div class="col">
            <h2>Booking Management</h2>
        </div>
        <div class="col text-end">
            <a href="master_schedules.php" class="btn btn-outline-secondary">Dashboard</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Origin</label>
                    <select class="form-select" name="search_origin">
                        <option value="">All Origins</option>
                        <?php while ($org = $origins_list->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($org['origin']); ?>"
                                <?php echo $search_origin == $org['origin'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($org['origin']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Destination</label>
                    <select class="form-select" name="search_destination">
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
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="bookings.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bookings List -->
    <?php if ($bookings->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>User</th>
                        <th>Passenger</th>
                        <th>Route</th>
                        <th>Date/Time</th>
                        <th>Fare</th>
                        <th>Status</th>
                        <th>Booked On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                            <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($booking['passenger_name']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($booking['passenger_phone']); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($booking['origin'] . ' → ' . $booking['destination']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($booking['bus_name']); ?></small>
                            </td>
                            <td>
                                <?php echo formatDate($booking['travel_date']); ?>
                                <br><small class="text-muted"><?php echo formatTime($booking['departure_time']); ?></small>
                            </td>
                            <td><strong class="text-success"><?php echo formatCurrency($booking['fare']); ?></strong></td>
                            <td>
                                <?php
                                $statusBadge = [
                                    'pending' => 'warning',
                                    'confirmed' => 'success',
                                    'cancelled' => 'danger',
                                    'completed' => 'secondary'
                                ];
                                $badge = $statusBadge[$booking['status']] ?? 'info';
                                ?>
                                <span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($booking['status']); ?></span>
                            </td>
                            <td><?php echo formatDateTime($booking['booking_time']); ?></td>
                            <td class="text-nowrap">
                                <?php if ($booking['status'] == 'pending'): ?>
                                    <a href="?confirm=1&id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-success" title="Confirm" onclick="return confirm('Confirm this booking?')"><i class="bi bi-check-circle"></i></a>
                                    <a href="?cancel=1&id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-danger" title="Cancel" onclick="return confirm('Cancel this booking?')"><i class="bi bi-x-circle"></i></a>
                                <?php elseif ($booking['status'] == 'confirmed'): ?>
                                    <a href="?cancel=1&id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-danger" title="Cancel" onclick="return confirm('Cancel this booking?')"><i class="bi bi-x-circle"></i></a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <p class="text-muted">Showing: <strong><?php echo $bookings->num_rows; ?></strong> bookings</p>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
            <h5 class="mt-3 text-muted">No bookings found</h5>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>