<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'farmville');

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 1 day
        'cookie_secure'   => false, // Should be true in production with HTTPS
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// PDO Connection
try {
    $pdo = new PDO("mysql:host=".DB_SERVER.";dbname=".DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}

// MySQLi Connection (for backward compatibility)
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("MySQLi Connection failed: " . $conn->connect_error);
}

// Helper function to check login status
function isLoggedIn() {
    return isset($_SESSION['CustomerID']); // Changed to CustomerID
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Set default timezone if not set
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}
?>