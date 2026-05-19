<?php
session_start();

// Include database connection to clear cart
require_once 'includes/db_connection.php';

// Clear cart if customer is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'CUSTOMER') {
    try {
        $stmt = $pdo->prepare("DELETE FROM Cart WHERE customer_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        // Log error but continue with logout
        error_log("Error clearing cart on logout: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect to home page with logout message
header('Location: index.php?logout=success');
exit();
?>