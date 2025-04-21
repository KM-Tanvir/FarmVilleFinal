<?php
// Start session
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
$servername = "localhost";
$username = "db_username";
$password = "db_password";
$dbname = "farmer_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$farmer_data = [];

// Fetch farmer data
$sql = "SELECT * FROM farmers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $farmer_data = $result->fetch_assoc();
} else {
    // Handle case where farmer data is not found
    echo "Farmer data not found.";
    exit;
}

// Handle profile update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['first_name'])) {
    // Collect form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $farm_name = $_POST['farm_name'];
    $address = $_POST['address'];
    $farm_size = $_POST['farm_size'];
    $farm_type = $_POST['farm_type'];
    
    // Prepare SQL statement
    $update_sql = "UPDATE farmers SET 
                  first_name = ?, 
                  last_name = ?, 
                  email = ?, 
                  phone = ?, 
                  farm_name = ?, 
                  address = ?, 
                  farm_size = ?, 
                  farm_type = ? 
                  WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssssdsi", 
                          $first_name, 
                          $last_name, 
                          $email, 
                          $phone, 
                          $farm_name, 
                          $address, 
                          $farm_size, 
                          $farm_type, 
                          $user_id);
    
    // Execute the statement
    if ($update_stmt->execute()) {
        // Handle profile picture upload if present
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $target_dir = "uploads/profiles/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
            $new_filename = $user_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // Check file type
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                    // Update profile picture path in database
                    $pic_sql = "UPDATE farmers SET profile_picture = ? WHERE id = ?";
                    $pic_stmt = $conn->prepare($pic_sql);
                    $pic_stmt->bind_param("si", $target_file, $user_id);
                    $pic_stmt->execute();
                }
            }
        }
        
        // Redirect to prevent form resubmission
        header("Location: farmer_profile.php?update=success");
        exit;
    } else {
        $error_message = "Error updating profile: " . $conn->error;
    }
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<!-- The HTML code would normally go here, but we've provided it separately in the HTML+CSS file -->