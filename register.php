<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Age theke login thakle redirect korbo
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);

    // Input validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($phone)) {
        $error = 'Please fill in all fields';
    } elseif (!isValidEmail($email)) {
        $error = 'Invalid email format';
    } elseif (!isValidPhone($phone)) {
        $error = 'Invalid phone number format. Use: 01XXXXXXXXX';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if username already exists using WHERE clause
        $sql = "SELECT user_id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Username already exists';
        } else {
            // Check if email already exists
            $sql = "SELECT user_id FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = 'Email already registered';
            } else {
                // Insert new user (plain text password for prototype)
                $user_type = 'user'; // Default user type

                $sql = "INSERT INTO users (username, password, email, phone, full_name, user_type) 
                        VALUES (?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $username, $password, $email, $phone, $full_name, $user_type);

                if ($stmt->execute()) {
                    $success = 'Registration successful! You can now login.';

                    // Clear form data
                    $_POST = array();
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }

        $stmt->close();
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="auth-card">
        <div class="card shadow-lg">
            <div class="card-header text-center">
                <h4 class="mb-0"><i class="bi bi-person-plus-fill"></i> Register</h4>
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
                        <a href="login.php" class="alert-link">Login now</a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="registerForm" novalidate>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text"
                            class="form-control"
                            id="full_name"
                            name="full_name"
                            value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text"
                            class="form-control"
                            id="username"
                            name="username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            required>
                        <small class="text-muted">Only letters, numbers, and underscores</small>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number *</label>
                        <input type="tel"
                            class="form-control"
                            id="phone"
                            name="phone"
                            placeholder="01XXXXXXXXX"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                            pattern="01[3-9]\d{8}"
                            required>
                        <small class="text-muted">Format: 01XXXXXXXXX</small>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            minlength="6"
                            required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password"
                            class="form-control"
                            id="confirm_password"
                            name="confirm_password"
                            required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus-fill"></i> Register
                        </button>
                    </div>
                </form>

                <hr>

                <div class="text-center">
                    <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Password confirmation validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
    });
</script>

<?php include 'includes/footer.php'; ?>