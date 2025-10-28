<?php
require_once '../config.php';
require_once '../includes/functions.php';

requireAdmin();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bus_number = sanitize($_POST['bus_number']);
    $bus_name = sanitize($_POST['bus_name']);
    $capacity = intval($_POST['capacity']);
    $bus_type = sanitize($_POST['bus_type']);

    // Validation
    if (empty($bus_number) || empty($bus_name) || $capacity <= 0) {
        $error = 'Please fill in all required fields correctly';
    } else {
        // Check if bus number already exists using WHERE clause
        $sql = "SELECT bus_id FROM buses WHERE bus_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $bus_number);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Bus number already exists';
        } else {
            // INSERT query
            $sql = "INSERT INTO buses (bus_number, bus_name, capacity, bus_type) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssis", $bus_number, $bus_name, $capacity, $bus_type);

            if ($stmt->execute()) {
                setFlashMessage('Bus added successfully!', 'success');
                header("Location: buses.php");
                exit();
            } else {
                $error = 'Failed to add bus. Please try again.';
            }
        }
    }
}

?>
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col">
            <h2> Add New Bus</h2>
        </div>
        <div class="col text-end">
            <a href="buses.php" class="btn btn-outline-secondary">
                Back to Buses
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bus_number" class="form-label">Bus Number *</label>
                                <input type="text"
                                    class="form-control"
                                    id="bus_number"
                                    name="bus_number"
                                    placeholder="e.g., TE-001"
                                    value="<?php echo isset($_POST['bus_number']) ? htmlspecialchars($_POST['bus_number']) : ''; ?>"
                                    required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="bus_name" class="form-label">Bus Name *</label>
                                <input type="text"
                                    class="form-control"
                                    id="bus_name"
                                    name="bus_name"
                                    placeholder="e.g., Tungipara Express"
                                    value="<?php echo isset($_POST['bus_name']) ? htmlspecialchars($_POST['bus_name']) : ''; ?>"
                                    required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="capacity" class="form-label">Capacity (Total Seats) *</label>
                                <input type="number"
                                    class="form-control"
                                    id="capacity"
                                    name="capacity"
                                    min="1"
                                    max="100"
                                    value="<?php echo isset($_POST['capacity']) ? $_POST['capacity'] : '40'; ?>"
                                    required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="bus_type" class="form-label">Bus Type *</label>
                                <select class="form-select" id="bus_type" name="bus_type" required>
                                    <option value="">Select Type</option>
                                    <option value="AC" <?php echo (isset($_POST['bus_type']) && $_POST['bus_type'] == 'AC') ? 'selected' : ''; ?>>AC</option>
                                    <option value="Non-AC" <?php echo (isset($_POST['bus_type']) && $_POST['bus_type'] == 'Non-AC') ? 'selected' : ''; ?>>Non-AC</option>
                                </select>
                            </div>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Add Bus
                            </button>
                            <a href="buses.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>