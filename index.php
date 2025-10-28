<?php
require_once 'config.php';
require_once 'includes/functions.php';
?>
<?php include 'includes/header.php'; ?>

<!-- Hero Section with Photo Strip -->
<section class="hero-section-photos">
    <div class="hero-text-section">
        <div class="container text-center">
            <h1 class="hero-title">Tungipara Express - Bus Reservation System</h1>
            <p class="hero-subtitle">Book your Tungipara Express tickets online</p>
        </div>
    </div>
    <div class="photo-strip">
        <div class="photo-item" style="background-image: url('<?php echo SITE_URL; ?>/Photos/1.jpg');"></div>
        <div class="photo-item" style="background-image: url('<?php echo SITE_URL; ?>/Photos/2..jpg');"></div>
        <div class="photo-item" style="background-image: url('<?php echo SITE_URL; ?>/Photos/3.jpg');"></div>
        <div class="photo-item" style="background-image: url('<?php echo SITE_URL; ?>/Photos/4.jpg');"></div>
        <div class="photo-item" style="background-image: url('<?php echo SITE_URL; ?>/Photos/5%20(2).jpg');"></div>
        <div class="photo-item" style="background-image: url('<?php echo SITE_URL; ?>/Photos/6.jpg');"></div>
    </div>
</section>

<!-- Search Section -->
<div class="container mb-5">
    <?php echo getFlashMessage(); ?>

    <div class="search-box fade-in-up">
        <h3 class="text-center mb-4">Search Available Buses</h3>
        <form action="search_results.php" method="GET" id="searchForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="origin" class="form-label">From</label>
                    <select class="form-select" id="origin" name="origin" required>
                        <option value="">Select Origin</option>
                        <?php
                        // Fetch unique origins from routes
                        $sql = "SELECT DISTINCT origin FROM routes ORDER BY origin";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['origin']) . '">' . htmlspecialchars($row['origin']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="destination" class="form-label">To</label>
                    <select class="form-select" id="destination" name="destination" required>
                        <option value="">Select Destination</option>
                        <?php
                        // Fetch unique destinations from routes
                        $sql = "SELECT DISTINCT destination FROM routes ORDER BY destination";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['destination']) . '">' . htmlspecialchars($row['destination']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="travel_date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="travel_date" name="travel_date" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Search Buses</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- All Routes Section -->
<div class="container mb-5">
    <h2 class="text-center mb-5">All Available Routes</h2>
    <div class="row g-4">
        <?php
        // Fetch all routes with schedule information (only future schedules)
        $sql = "SELECT 
                    r.route_id,
                    r.origin,
                    r.destination,
                    r.distance_km,
                    COUNT(DISTINCT s.schedule_id) AS schedule_count
                FROM routes r
                LEFT JOIN schedules s ON r.route_id = s.route_id AND s.travel_date >= CURDATE()
                GROUP BY r.route_id, r.origin, r.destination, r.distance_km
                ORDER BY r.origin, r.destination";
        // group by keno? count dekhar jonno aggregate function
        // schedule na thakleo jeni dekay thats why left join 

        $result = $conn->query($sql);

        // $result->fetch_assoc() is a associative array 

        while ($route = $result->fetch_assoc()):
        ?>
            <div class="col-md-4">
                <div class="card h-100 index-route-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php echo htmlspecialchars($route['origin']); ?> → <?php echo htmlspecialchars($route['destination']); ?>
                        </h5>
                        <p class="card-text">
                            Distance: <?php echo $route['distance_km']; ?> km<br>
                            Schedules: <?php echo $route['schedule_count']; ?>
                        </p>
                        <a href="search_results.php?origin=<?php echo urlencode($route['origin']); ?>&destination=<?php echo urlencode($route['destination']); ?>&travel_date=<?php echo date('Y-m-d'); ?>"
                            class="btn btn-outline-primary">
                            View Schedules
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>