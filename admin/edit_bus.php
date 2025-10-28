<?php
require_once '../config.php';
require_once '../includes/functions.php';

requireAdmin();

$bus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

// Fetch bus details
$sql = "SELECT * FROM buses WHERE bus_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bus_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: buses.php");
    exit();
}

$bus = $result->fetch_assoc();

// Handle form submission for UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bus_number = sanitize($_POST['bus_number']);
    $bus_name = sanitize($_POST['bus_name']);
    $capacity = intval($_POST['capacity']);
    $bus_type = sanitize($_POST['bus_type']);

    // Validation
    if (empty($bus_number) || empty($bus_name) || $capacity <= 0) {
        $error = 'Please fill in all required fields correctly';
    } else {
        // Check if bus number exists for other buses (not this one)
        $sql = "SELECT bus_id FROM buses WHERE bus_number = ? AND bus_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $bus_number, $bus_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Bus number already exists';
        } else {
            // UPDATE query
            $sql = "UPDATE buses 
                    SET bus_number = ?, bus_name = ?, capacity = ?, bus_type = ? 
                    WHERE bus_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisi", $bus_number, $bus_name, $capacity, $bus_type, $bus_id);

            if ($stmt->execute()) {
                setFlashMessage('Bus updated successfully!', 'success');
                header("Location: buses.php");
                exit();
            } else {
                $error = 'Failed to update bus. Please try again.';
            }
        }
    }
}

?>
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-pencil-square"></i> Edit Bus</h2>
        </div>
        <div class="col text-end">
            <a href="buses.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Buses
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
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
                                    value="<?php echo htmlspecialchars($bus['bus_number']); ?>"
                                    required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="bus_name" class="form-label">Bus Name/Operator *</label>
                                <input type="text"
                                    class="form-control"
                                    id="bus_name"
                                    name="bus_name"
                                    value="<?php echo htmlspecialchars($bus['bus_name']); ?>"
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
                                    value="<?php echo $bus['capacity']; ?>"
                                    required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="bus_type" class="form-label">Bus Type *</label>
                                <select class="form-select" id="bus_type" name="bus_type" required>
                                    <option value="AC" <?php echo $bus['bus_type'] == 'AC' ? 'selected' : ''; ?>>AC</option>
                                    <option value="Non-AC" <?php echo $bus['bus_type'] == 'Non-AC' ? 'selected' : ''; ?>>Non-AC</option>
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <strong>Created:</strong> <?php echo formatDateTime($bus['created_at']); ?><br>
                            <strong>Last Updated:</strong> <?php echo formatDateTime($bus['updated_at']); ?>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Update Bus
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