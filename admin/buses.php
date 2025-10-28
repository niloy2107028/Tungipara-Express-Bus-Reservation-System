<?php
require_once '../config.php';
require_once '../includes/functions.php';

requireAdmin();

$success = '';
$error = '';

// Bus delete korar kaj
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $bus_id = intval($_GET['id']);

    // Bus er schedule ache ki na check korbo
    $sql = "SELECT COUNT(*) as count FROM schedules WHERE bus_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bus_id);
    $stmt->execute();
    $has_schedules = $stmt->get_result()->fetch_assoc()['count'] > 0;

    if ($has_schedules) {
        $error = 'Cannot delete bus with existing schedules. Please remove schedules first.';
    } else {
        // DELETE query
        $sql = "DELETE FROM buses WHERE bus_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $bus_id);

        if ($stmt->execute()) {
            $success = 'Bus deleted successfully!';
        } else {
            $error = 'Failed to delete bus.';
        }
    }
}

// Buses gulo niye ashbo filter diye
$search_bus = isset($_GET['search_bus']) ? sanitize($_GET['search_bus']) : '';
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : 'all';

$sql = "SELECT * FROM buses WHERE 1=1";

if (!empty($search_bus)) {
    $sql .= " AND bus_id = " . intval($search_bus);
}

if ($type_filter != 'all') {
    $sql .= " AND bus_type = '" . $conn->real_escape_string($type_filter) . "'";
}

$sql .= " ORDER BY bus_id DESC";
$buses = $conn->query($sql);

// Dropdown er jonno buses list
$buses_dropdown = $conn->query("SELECT bus_id, bus_number, bus_name FROM buses ORDER BY bus_name");

?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid my-4">
    <?php include 'includes/admin_nav_cards.php'; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-bus-front"></i> Bus Management</h2>
        </div>
        <div class="col text-end">
            <a href="add_bus.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Bus
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="search_bus" class="form-label">Bus</label>
                    <select class="form-select" id="search_bus" name="search_bus">
                        <option value="">All Buses</option>
                        <?php while ($bus_opt = $buses_dropdown->fetch_assoc()): ?>
                            <option value="<?php echo $bus_opt['bus_id']; ?>"
                                <?php echo $search_bus == $bus_opt['bus_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bus_opt['bus_name'] . ' - ' . $bus_opt['bus_number']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="type" class="form-label">Bus Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="AC" <?php echo $type_filter == 'AC' ? 'selected' : ''; ?>>AC</option>
                        <option value="Non-AC" <?php echo $type_filter == 'Non-AC' ? 'selected' : ''; ?>>Non-AC</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Buses List -->
    <?php if ($buses->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Bus Number</th>
                        <th>Bus Name</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($bus = $buses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $bus['bus_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($bus['bus_name']); ?></td>
                            <td><span class="badge bg-info"><?php echo htmlspecialchars($bus['bus_type']); ?></span></td>
                            <td><?php echo $bus['capacity']; ?> seats</td>
                            <td><?php echo formatDate($bus['created_at']); ?></td>
                            <td>
                                <a href="edit_bus.php?id=<?php echo $bus['bus_id']; ?>"
                                    class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?delete=1&id=<?php echo $bus['bus_id']; ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    title="Delete"
                                    onclick="return confirm('Are you sure you want to delete this bus?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <p class="text-muted">Total Buses: <strong><?php echo $buses->num_rows; ?></strong></p>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
            <h5 class="mt-3 text-muted">No buses found</h5>
            <p class="text-muted">Add a new bus to get started</p>
            <a href="add_bus.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Bus
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>