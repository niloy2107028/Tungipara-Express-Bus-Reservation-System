<?php
require_once '../config.php';
require_once '../includes/functions.php';

requireAdmin();

$success = '';
$error = '';

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $route_id = intval($_GET['id']);

    // First delete all schedules associated with this route
    $sql = "DELETE FROM schedules WHERE route_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $route_id);
    $stmt->execute();

    // Then delete the route
    $sql = "DELETE FROM routes WHERE route_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $route_id);

    if ($stmt->execute()) {
        $success = 'Route and all associated schedules deleted successfully!';
    } else {
        $error = 'Failed to delete route.';
    }
}

// Handle Add Route
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_route'])) {
    $origin = sanitize($_POST['origin']);
    $destination = sanitize($_POST['destination']);
    $distance_km = floatval($_POST['distance_km']);

    if (empty($origin) || empty($destination) || $distance_km <= 0) {
        $error = 'Please fill all fields correctly';
    } else {
        // Check if route exists using multiple column conditions
        $sql = "SELECT route_id FROM routes WHERE origin = ? AND destination = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $origin, $destination);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Route already exists';
        } else {
            $sql = "INSERT INTO routes (origin, destination, distance_km) 
                    VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssd", $origin, $destination, $distance_km);

            if ($stmt->execute()) {
                $success = 'Route added successfully!';
            } else {
                $error = 'Failed to add route.';
            }
        }
    }
}

// Search functionality
$search_origin = isset($_GET['search_origin']) ? sanitize($_GET['search_origin']) : '';
$search_destination = isset($_GET['search_destination']) ? sanitize($_GET['search_destination']) : '';

// Fetch all routes with schedule count using LEFT JOIN and GROUP BY
$sql = "SELECT 
            r.*,
            COUNT(DISTINCT s.schedule_id) as schedule_count
        FROM routes r
        LEFT JOIN schedules s ON r.route_id = s.route_id
        WHERE 1=1";

if (!empty($search_origin)) {
    $sql .= " AND r.origin = '" . $conn->real_escape_string($search_origin) . "'";
}

if (!empty($search_destination)) {
    $sql .= " AND r.destination = '" . $conn->real_escape_string($search_destination) . "'";
}

$sql .= " GROUP BY r.route_id
        ORDER BY r.route_id DESC";
$routes = $conn->query($sql);

// Fetch unique origins and destinations for dropdowns
$origins = $conn->query("SELECT DISTINCT origin FROM routes ORDER BY origin");
$destinations = $conn->query("SELECT DISTINCT destination FROM routes ORDER BY destination");

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
            <h2><i class="bi bi-signpost"></i> Route Management</h2>
        </div>
        <div class="col text-end">

            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRouteModal">
                <i class="bi bi-plus-circle"></i> Add New Route
            </button>
        </div>
    </div>

    <!-- Search Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-5">
                    <label for="search_origin" class="form-label">Origin</label>
                    <select class="form-select" id="search_origin" name="search_origin">
                        <option value="">All Origins</option>
                        <?php while ($org = $origins->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($org['origin']); ?>"
                                <?php echo $search_origin == $org['origin'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($org['origin']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-5">
                    <label for="search_destination" class="form-label">Destination</label>
                    <select class="form-select" id="search_destination" name="search_destination">
                        <option value="">All Destinations</option>
                        <?php while ($dest = $destinations->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($dest['destination']); ?>"
                                <?php echo $search_destination == $dest['destination'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dest['destination']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($routes->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Origin</th>
                        <th>Destination</th>
                        <th>Distance (km)</th>
                        <th>Schedules</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($route = $routes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $route['route_id']; ?></td>
                            <td><?php echo htmlspecialchars($route['origin']); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($route['destination']); ?></strong></td>
                            <td><?php echo $route['distance_km']; ?> km</td>
                            <td><span class="badge bg-primary"><?php echo $route['schedule_count']; ?></span></td>
                            <td>
                                <a href="edit_route.php?id=<?php echo $route['route_id']; ?>"
                                    class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?delete=1&id=<?php echo $route['route_id']; ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    title="Delete"
                                    onclick="return confirm('Are you sure? This will delete the route and all its associated schedules.')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
            <h5 class="mt-3 text-muted">No routes found</h5>
        </div>
    <?php endif; ?>
</div>

<!-- Add Route Modal -->
<div class="modal fade" id="addRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Route</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Origin *</label>
                        <input type="text" class="form-control" name="origin" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Destination *</label>
                        <input type="text" class="form-control" name="destination" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Distance (km) *</label>
                        <input type="number" class="form-control" name="distance_km" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_route" class="btn btn-primary">Add Route</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>