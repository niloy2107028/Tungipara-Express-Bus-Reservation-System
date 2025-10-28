<?php
require_once 'config.php';
require_once 'includes/functions.php';

$origin = isset($_GET['origin']) ? sanitize($_GET['origin']) : '';
$destination = isset($_GET['destination']) ? sanitize($_GET['destination']) : '';
$travel_date = isset($_GET['travel_date']) ? sanitize($_GET['travel_date']) : '';

$schedules = [];
$error_message = '';

if (!empty($origin) && !empty($destination) && !empty($travel_date)) {
    // Validate date is not more than 7 days in future
    $today = date('Y-m-d');
    $max_date = date('Y-m-d', strtotime('+7 days'));

    if ($travel_date < $today) {
        $error_message = 'Cannot search for past dates. Please select today or a future date.';
    } elseif ($travel_date > $max_date) {
        $error_message = 'Cannot book more than 7 days in advance. Please select a date within the next week.';
    } else {
        // Get bus schedules for this route (based on pattern, not specific date)
        // We'll use any existing schedule as a template since buses run daily
        $sql = "SELECT 
                    s.schedule_id,
                    s.departure_time,
                    s.arrival_time,
                    s.fare,
                    b.bus_id,
                    b.bus_number,
                    b.bus_name,
                    b.bus_type,
                    b.capacity,
                    r.route_id,
                    r.origin,
                    r.destination,
                    r.distance_km
                FROM schedules s
                INNER JOIN buses b ON s.bus_id = b.bus_id
                INNER JOIN routes r ON s.route_id = r.route_id
                WHERE r.origin = ?
                    AND r.destination = ?
                    AND s.status = 'scheduled'
                GROUP BY s.departure_time, b.bus_id
                ORDER BY s.departure_time ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $origin, $destination);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Check how many seats are booked for this specific date and time
            $check_sql = "SELECT COUNT(*) as booked 
                         FROM bookings bk 
                         JOIN schedules s ON bk.schedule_id = s.schedule_id 
                         WHERE s.bus_id = ? 
                         AND s.departure_time = ? 
                         AND s.travel_date = ? 
                         AND bk.status = 'confirmed'";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("iss", $row['bus_id'], $row['departure_time'], $travel_date);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $booked_data = $check_result->fetch_assoc();
            $booked_seats = $booked_data['booked'];
            $check_stmt->close();

            $available_seats = $row['capacity'] - $booked_seats;

            // Add to results with calculated availability
            $row['travel_date'] = $travel_date;
            $row['available_seats'] = $available_seats;
            $row['booked_seats'] = $booked_seats;
            $row['status'] = 'scheduled';

            if ($available_seats == 0) {
                $row['availability_status'] = 'Full';
            } elseif ($available_seats <= 5) {
                $row['availability_status'] = 'Few Seats Left';
            } else {
                $row['availability_status'] = 'Available';
            }

            // Only show if seats available
            if ($available_seats > 0) {
                $schedules[] = $row;
            }
        }

        $stmt->close();
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-search"></i> Search Buses</h5>
        </div>
        <div class="card-body">
            <form action="search_results.php" method="GET" id="searchForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="origin" class="form-label">From</label>
                        <select class="form-select" id="origin" name="origin" required>
                            <option value="">Select Origin</option>
                            <?php
                            $sql = "SELECT DISTINCT origin FROM routes ORDER BY origin";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['origin'] == $origin) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($row['origin']) . '" ' . $selected . '>'
                                    . htmlspecialchars($row['origin']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="destination" class="form-label">To</label>
                        <select class="form-select" id="destination" name="destination" required>
                            <option value="">Select Destination</option>
                            <?php
                            $sql = "SELECT DISTINCT destination FROM routes ORDER BY destination";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['destination'] == $destination) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($row['destination']) . '" ' . $selected . '>'
                                    . htmlspecialchars($row['destination']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="travel_date" class="form-label">Date</label>
                        <input type="date"
                            class="form-control"
                            id="travel_date"
                            name="travel_date"
                            value="<?php echo htmlspecialchars($travel_date); ?>"
                            required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($origin) && !empty($destination) && !empty($travel_date)): ?>
        <!-- Search Results -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    Available Buses: <?php echo htmlspecialchars($origin); ?>
                    →
                    <?php echo htmlspecialchars($destination); ?>
                    <span class="badge bg-success"><?php echo date('d M Y', strtotime($travel_date)); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                <?php elseif (count($schedules) > 0): ?>
                    <div class="row g-3">
                        <?php foreach ($schedules as $schedule): ?>
                            <div class="col-12">
                                <div class="card bus-item">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-3">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($schedule['bus_name']); ?></h5>
                                                <small class="text-muted"><?php echo htmlspecialchars($schedule['bus_number']); ?></small>
                                                <br>
                                                <span class="badge bg-info mt-2">
                                                    <?php echo htmlspecialchars($schedule['bus_type']); ?>
                                                </span>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="text-center">
                                                        <h4 class="mb-0"><?php echo formatTime($schedule['departure_time']); ?></h4>
                                                        <small class="text-muted">Departure</small>
                                                    </div>
                                                    <div class="mx-3">
                                                        <i class="bi bi-arrow-right text-primary" style="font-size: 1.5rem;"></i>
                                                    </div>
                                                    <div class="text-center">
                                                        <h4 class="mb-0"><?php echo formatTime($schedule['arrival_time']); ?></h4>
                                                        <small class="text-muted">Arrival</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-2 text-center">
                                                <div class="mb-2">
                                                    <i class="bi bi-people-fill"></i>
                                                    <strong><?php echo $schedule['available_seats']; ?></strong>
                                                    / <?php echo $schedule['capacity']; ?>
                                                </div>
                                                <?php
                                                $statusClass = 'success';
                                                if ($schedule['availability_status'] == 'Few Seats Left') {
                                                    $statusClass = 'warning';
                                                } elseif ($schedule['availability_status'] == 'Full') {
                                                    $statusClass = 'danger';
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo $schedule['availability_status']; ?>
                                                </span>
                                            </div>

                                            <div class="col-md-2 text-center">
                                                <h3 class="text-primary mb-0">
                                                    <?php echo formatCurrency($schedule['fare']); ?>
                                                </h3>
                                                <small class="text-muted">per seat</small>
                                            </div>

                                            <div class="col-md-2 text-center">
                                                <?php if (isLoggedIn() && !isAdmin()): ?>
                                                    <a href="user/book_ticket.php?schedule_id=<?php echo $schedule['schedule_id']; ?>"
                                                        class="btn btn-primary">
                                                        <i class="bi bi-ticket-perforated"></i> Book Now
                                                    </a>
                                                <?php elseif (isLoggedIn() && isAdmin()): ?>
                                                    <button class="btn btn-secondary" disabled>
                                                        Admin Account
                                                    </button>
                                                <?php else: ?>
                                                    <a href="login.php" class="btn btn-primary">
                                                        Login to Book
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <h5>No buses available</h5>
                        <p class="mb-0">Sorry, no buses are available for the selected route and date. Please try another date or route.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>