<?php
require_once '../config.php';
require_once '../includes/functions.php';

requireAdmin();

$success = '';
$error = '';
$route_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($route_id <= 0) {
    header("Location: routes.php");
    exit();
}

// Fetch route details
$sql = "SELECT * FROM routes WHERE route_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $route_id);
$stmt->execute();
$route = $stmt->get_result()->fetch_assoc();

if (!$route) {
    header("Location: routes.php");
    exit();
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_route'])) {
    $origin = sanitize($_POST['origin']);
    $destination = sanitize($_POST['destination']);
    $distance_km = floatval($_POST['distance_km']);

    if (empty($origin) || empty($destination) || $distance_km <= 0) {
        $error = 'Please fill all fields correctly';
    } else {
        // Check if route already exists (excluding current route)
        $sql = "SELECT route_id FROM routes WHERE origin = ? AND destination = ? AND route_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $origin, $destination, $route_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Route already exists';
        } else {
            $sql = "UPDATE routes SET origin = ?, destination = ?, distance_km = ? WHERE route_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdi", $origin, $destination, $distance_km, $route_id);

            if ($stmt->execute()) {
                $success = 'Route updated successfully!';
                // Refresh route data
                $sql = "SELECT * FROM routes WHERE route_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $route_id);
                $stmt->execute();
                $route = $stmt->get_result()->fetch_assoc();
            } else {
                $error = 'Failed to update route.';
            }
        }
    }
}

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
            <h2><i class="bi bi-pencil-square"></i> Edit Route</h2>
        </div>
        <div class="col text-end">
            <a href="routes.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Routes
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Origin *</label>
                            <input type="text" class="form-control" name="origin"
                                value="<?php echo htmlspecialchars($route['origin']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Destination *</label>
                            <input type="text" class="form-control" name="destination"
                                value="<?php echo htmlspecialchars($route['destination']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Distance (km) *</label>
                            <input type="number" class="form-control" name="distance_km"
                                step="0.01" min="0" value="<?php echo $route['distance_km']; ?>" required>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="update_route" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Route
                            </button>
                            <a href="routes.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Route Information</h5>
                    <hr>
                    <p><strong>Route ID:</strong> <?php echo $route['route_id']; ?></p>
                    <p><strong>Created:</strong> <?php echo formatDateTime($route['created_at']); ?></p>
                    <p class="text-muted mb-0">
                        <small><i class="bi bi-info-circle"></i> Updating this route will not affect existing schedules.</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>