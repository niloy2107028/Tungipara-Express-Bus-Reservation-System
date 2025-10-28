<?php
require_once '../config.php';
require_once '../includes/functions.php';

requireUser();

$user_id = getCurrentUserId();

// All bookings query
$sql = "SELECT 
            t.booking_id,
            t.booking_reference,
            t.status,
            t.booking_time,
            t.passenger_name,
            t.passenger_phone,
            s.schedule_id,
            s.travel_date,
            s.departure_time,
            s.arrival_time,
            s.fare,
            b.bus_name,
            b.bus_number,
            b.bus_type,
            r.origin,
            r.destination,
            r.distance_km,
            CASE 
                WHEN s.travel_date < CURDATE() THEN 'completed'
                WHEN s.travel_date = CURDATE() THEN 'today'
                ELSE 'upcoming'
            END as trip_status
            -- defaul val set korlam case diye 
        FROM bookings t
        INNER JOIN schedules s ON t.schedule_id = s.schedule_id
        INNER JOIN buses b ON s.bus_id = b.bus_id
        INNER JOIN routes r ON s.route_id = r.route_id
        WHERE t.user_id = ?
        ORDER BY t.booking_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$all_bookings = $stmt->get_result();

?>
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <?php echo getFlashMessage(); ?>

    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-speedometer2"></i> My Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
        </div>
    </div>

    <!-- My Bookings -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-ticket-perforated"></i> My Bookings</h5>
        </div>
        <div class="card-body">
            <?php if ($all_bookings->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Booking Ref</th>
                                <th>Route</th>
                                <th>Date & Time</th>
                                <th>Passenger</th>
                                <th>Fare</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $all_bookings->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong><br>
                                        <small class="text-muted">Booked: <?php echo formatDate($booking['booking_time']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['origin']); ?></strong>
                                        <i class="bi bi-arrow-right text-primary"></i>
                                        <strong><?php echo htmlspecialchars($booking['destination']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-bus-front"></i> <?php echo htmlspecialchars($booking['bus_name']); ?>
                                            (<?php echo htmlspecialchars($booking['bus_number']); ?>)
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?php echo formatDate($booking['travel_date']); ?></strong><br>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> <?php echo formatTime($booking['departure_time']); ?> - <?php echo formatTime($booking['arrival_time']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['passenger_name']); ?><br>
                                        <small class="text-muted"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($booking['passenger_phone']); ?></small>
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
                                        $badgeClass = $statusBadge[$booking['status']] ?? 'info';
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No bookings yet</h5>
                    <p class="text-muted">Start by searching for available buses</p>
                    <a href="../index.php" class="btn btn-primary">Search Buses</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>