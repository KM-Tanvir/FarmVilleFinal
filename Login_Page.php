<?php
include "db.php";
session_start();

// Database connection parameters
$host = "localhost";
$dbname = "farmville_db";
$username = "farmville_user";
$password = "your_secure_password";

// Function to sanitize user inputs
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get login credentials based on which tab was active
    if (!empty($_POST["email"])) {
        $identifier = sanitize_input($_POST["email"]);
        $identifier_type = "email";
    } else if (!empty($_POST["phone"])) {
        $identifier = sanitize_input($_POST["phone"]);
        $identifier_type = "phone";
    } else {
        // Both email and phone are empty
        $_SESSION["login_error"] = "Please provide either email or phone number.";
        header("Location: index.html");
        exit();
    }
    
    // Get password
    $password = sanitize_input($_POST["password"]);
    
    // Validate inputs
    if (empty($password)) {
        $_SESSION["login_error"] = "Password is required.";
        header("Location: index.html");
        exit();
    }
    
    try {
        // Create database connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        
        // Set the PDO error mode to exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Prepare SQL statement based on identifier type
        // Note: We don't filter by user_type here, we'll determine that from the database
        $sql = "SELECT * FROM users WHERE $identifier_type = :identifier";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":identifier", $identifier);
        $stmt->execute();
        
        // Get the user record
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Verify password
            if (password_verify($password, $user["password_hash"])) {
                // Authentication successful
                // Set session variables
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["user_type"] = $user["user_type"];
                $_SESSION["user_name"] = $user["first_name"] . " " . $user["last_name"];
                
                // Log the successful login
                $log_sql = "INSERT INTO login_logs (user_id, login_time, ip_address) VALUES (:user_id, NOW(), :ip)";
                $log_stmt = $pdo->prepare($log_sql);
                $log_stmt->bindParam(":user_id", $user["user_id"]);
                $log_stmt->bindParam(":ip", $_SERVER['REMOTE_ADDR']);
                $log_stmt->execute();
                
                // Redirect based on user type
                switch ($user["user_type"]) {
                    case "admin":
                        header("Location: admin/dashboard.php");
                        break;
                    case "farmer":
                        header("Location: farmer/dashboard.php");
                        break;
                    case "vendor":
                        header("Location: vendor/dashboard.php");
                        break;
                    case "agricultural-officer":
                        header("Location: officer/dashboard.php");
                        break;
                    case "customer":
                        header("Location: customer/dashboard.php");
                        break;
                    case "storage-manager":
                        header("Location: storage/dashboard.php");
                        break;
                    default:
                        header("Location: dashboard.php");
                }
                exit();
            } else {
                // Invalid password
                $_SESSION["login_error"] = "Invalid password.";
                header("Location: index.html");
                exit();
            }
        } else {
            // User not found
            $_SESSION["login_error"] = "User not found.";
            header("Location: index.html");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION["login_error"] = "Connection failed: " . $e->getMessage();
        header("Location: index.html");
        exit();
    }
    
    // Close connection
    $pdo = null;
} else {
    // If someone tries to access this page directly
    header("Location: index.html");
    exit();
}
?>