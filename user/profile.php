<?php
require_once '../config.php';
require_once '../includes/functions.php';

requireLogin();

// Allow both admin and regular users to access profile
$user_id = getCurrentUserId();
$success = '';
$error = '';

// Fetch user details
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($full_name) || empty($email) || empty($phone)) {
        $error = 'Please fill in all required fields';
    } elseif (!isValidEmail($email)) {
        $error = 'Invalid email format';
    } elseif (!isValidPhone($phone)) {
        $error = 'Invalid phone number format';
    } else {
        // Check if email is already used by another user
        $sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email is already registered by another user';
        } else {
            // Update profile using UPDATE query
            $sql = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);

            if ($stmt->execute()) {
                // Update session
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;

                // Handle password change if provided
                if (!empty($current_password) && !empty($new_password)) {
                    // Verify current password
                    if (!verifyPassword($current_password, $user['password'])) {
                        $error = 'Current password is incorrect';
                    } elseif (strlen($new_password) < 6) {
                        $error = 'New password must be at least 6 characters';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'New passwords do not match';
                    } else {
                        $hashed_password = hashPassword($new_password);
                        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $hashed_password, $user_id);
                        $stmt->execute();

                        $success = 'Profile and password updated successfully!';
                    }
                } else {
                    $success = 'Profile updated successfully!';
                }

                // Refresh user data
                $sql = "SELECT * FROM users WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $error = 'Failed to update profile';
            }
        }
    }
}

?>
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col">
            <h2> My Profile</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Profile</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text"
                                class="form-control"
                                id="full_name"
                                name="full_name"
                                value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel"
                                class="form-control"
                                id="phone"
                                name="phone"
                                value="<?php echo htmlspecialchars($user['phone']); ?>"
                                pattern="01[3-9]\d{8}"
                                required>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Change Password (Optional)</h6>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password"
                                class="form-control"
                                id="current_password"
                                name="current_password">
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password"
                                class="form-control"
                                id="new_password"
                                name="new_password"
                                minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password"
                                class="form-control"
                                id="confirm_password"
                                name="confirm_password">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>