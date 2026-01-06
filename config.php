<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gamexchange');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin email (for admin access)
define('ADMIN_EMAIL', 'admin@gamexchange.com');

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    // Fast path: session flag
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        return true;
    }

    // If user is logged in, check Admins table and users.role for authority
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        if (isset($GLOBALS['conn'])) {
            // Check Admins table
            $stmt = $GLOBALS['conn']->prepare("SELECT COUNT(*) as cnt FROM Admins WHERE UserID = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res) {
                    $row = $res->fetch_assoc();
                    if ($row && $row['cnt'] > 0) {
                        $_SESSION['is_admin'] = true;
                        return true;
                    }
                }
            }

            // Also check users.role column for admin status
            $stmt = $GLOBALS['conn']->prepare("SELECT role FROM Users WHERE UserID = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    if ($row && strtolower($row['role']) === 'admin') {
                        $_SESSION['is_admin'] = true;
                        return true;
                    }
                }
            }
        }
    }

    // Fallback: legacy ADMIN_EMAIL constant (one-off)
    if (isset($_SESSION['user_email']) && defined('ADMIN_EMAIL') && $_SESSION['user_email'] === ADMIN_EMAIL) {
        $_SESSION['is_admin'] = true;
        return true;
    }

    return false;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: admin/login.php');
        exit();
    }
}
?>
