<?php
// Helper functions - useful kaj er jonno

// Input data clean korbo
function sanitize($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// User login ache ki na check
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// User admin ki na check
function isAdmin()
{
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Login na thakle login page e pathabo
function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "/login.php");
        exit();
    }
}

// Admin na hole admin dashboard e pathabo
function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        header("Location: " . SITE_URL . "/user/dashboard.php");
        exit();
    }
}

// Redirect to login if admin tries to access user pages
function requireUser()
{
    requireLogin();
    if (isAdmin()) {
        header("Location: " . SITE_URL . "/admin/dashboard.php");
        exit();
    }
}

// Format date
function formatDate($date)
{
    return date('d M Y', strtotime($date));
}

// Format time
function formatTime($time)
{
    return date('h:i A', strtotime($time));
}

// Format datetime
function formatDateTime($datetime)
{
    return date('d M Y h:i A', strtotime($datetime));
}

// Format currency
function formatCurrency($amount)
{
    if ($amount === null || $amount === '') {
        return 'BDT 0.00';
    }
    return 'BDT ' . number_format((float)$amount, 2);
}

// Generate booking reference
function generateBookingReference()
{
    return 'BKG' . strtoupper(substr(uniqid(), -8));
}

// Generate transaction ID
function generateTransactionId()
{
    return 'TXN' . strtoupper(substr(uniqid(), -10));
}

// Show alert message
function showAlert($message, $type = 'info')
{
    $alertClass = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];

    $class = $alertClass[$type] ?? 'alert-info';

    return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

// Set flash message
function setFlashMessage($message, $type = 'info')
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

// Get and clear flash message
function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = showAlert($_SESSION['flash_message'], $_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return $message;
    }
    return '';
}

// Validate email
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number (Bangladesh format)
function isValidPhone($phone)
{
    return preg_match('/^01[3-9]\d{8}$/', $phone);
}

// Password hash
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

// Get current user ID
function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

// Get current user type
function getCurrentUserType()
{
    return $_SESSION['user_type'] ?? null;
}

// Calculate age from date of birth
function calculateAge($dob)
{
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age;
}

// Debug print
function debug($data)
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}
