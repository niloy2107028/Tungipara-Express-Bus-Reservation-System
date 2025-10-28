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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    // Input check korchi
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // User check korbo database theke
        $sql = "SELECT user_id, username, password, email, full_name, user_type 
                FROM users 
                WHERE username = ? OR email = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password (plain text comparison for prototype)
            if ($password === $user['password']) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['login_time'] = time();

                // Redirect based on user type
                if ($user['user_type'] === 'admin') {
                    header("Location: admin/master_schedules.php");
                } else {
                    header("Location: user/dashboard.php");
                }
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
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
                <h4 class="mb-0">Login</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username or Email</label>
                        <div class="input-group">
                            <input type="text"
                                class="form-control"
                                id="username"
                                name="username"
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">

                            <input type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                required>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            Login
                        </button>
                    </div>
                </form>

                <hr>

                <div class="text-center">
                    <p class="mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                </div>

                <hr>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>