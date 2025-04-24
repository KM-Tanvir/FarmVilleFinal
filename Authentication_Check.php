<?php
include "db.php";
// auth_check.php - Include this at the top of all protected pages

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if user is authenticated and has required role
 * 
 * @param array $allowed_roles Array of roles allowed to access the page
 * @return boolean True if authenticated and authorized, false otherwise
 */
function check_auth($allowed_roles = []) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"]) || !isset($_SESSION["user_type"])) {
        // Not logged in, redirect to login page
        header("Location: ../index.html");
        exit();
    }
    
    // If no specific roles are required or user's role is in allowed list
    if (empty($allowed_roles) || in_array($_SESSION["user_type"], $allowed_roles)) {
        return true;
    }
    
    // User doesn't have required role
    header("Location: ../unauthorized.php");
    exit();
}

// Example usage:
// At the top of admin/dashboard.php:
// require_once('../auth_check.php');
// check_auth(['admin']);
?>