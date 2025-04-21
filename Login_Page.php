<?php
// Start session
session_start();

// Database connection configuration
$db_host = "localhost";
$db_user = "farmville_user";
$db_pass = "your_secure_password"; // Change this to a secure password in production
$db_name = "farmville_db";

// Define response array
$response = array(
    "success" => false,
    "message" => "",
    "redirect" => ""
);

// Function to validate and sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get user selection
    $user_select = isset($_POST['user_select']) ? sanitize_input($_POST['user_select']) : '';
    
    // Determine which login method was used
    $login_identifier = '';
    $login_type = '';
    
    if (!empty($_POST['email'])) {
        $login_identifier = sanitize_input($_POST['email']);
        $login_type = 'email';
    } elseif (!empty($_POST['phone'])) {
        $login_identifier = sanitize_input($_POST['phone']);
        $login_type = 'phone';
    } else {
        $response["message"] = "Please provide an email or phone number";
        echo json_encode($response);
        exit();
    }
    
    // Get password
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($password)) {
        $response["message"] = "Password is required";
        echo json_encode($response);
        exit();
    }
    
    // Connect to database
    try {
        $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Prepare SQL query based on login type
        if ($login_type == 'email') {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :identifier");
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE phone = :identifier");
        }
        
        $stmt->bindParam(':identifier', $login_identifier);
        $stmt->execute();
        
        // Check if user exists
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct, set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['farm_name'] = $user['farm_name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Determine where to redirect based on user role
                if ($user['role'] == 'admin') {
                    $response["redirect"] = "admin_dashboard.php";
                } else {
                    $response["redirect"] = "dashboard.php";
                }
                
                $response["success"] = true;
                $response["message"] = "Login successful";
            } else {
                $response["message"] = "Invalid password";
            }
        } else {
            $response["message"] = "User not found";
        }
    } catch(PDOException $e) {
        $response["message"] = "Database error: " . $e->getMessage();
    }
    
    // Close connection
    $conn = null;
    
    // Return JSON response
    echo json_encode($response);
    exit();
}

// If the code reaches here, it means the request was not a POST
// Redirect to login page if accessed directly
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    header("Location: index.html");
    exit();
}
?>