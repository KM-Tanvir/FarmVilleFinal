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

// Process form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'add':
            addProduct($conn, $user_id);
            break;
        case 'edit':
            editProduct($conn, $user_id);
            break;
        case 'delete':
            deleteProduct($conn, $user_id);
            break;
        default:
            // Invalid action
            $_SESSION['error'] = "Invalid action.";
            header("Location: product_information.php");
            exit;
    }
}

// Function to add a new product
function addProduct($conn, $user_id) {
    $product_name = trim($_POST['product_name']);
    $product_type = trim($_POST['product_type']);
    $product_variety = trim($_POST['product_variety']);
    $seasonality = isset($_POST['seasonality']) ? $_POST['seasonality'] : [];
    
    // Validate input
    if (empty($product_name) || empty($product_type) || empty($product_variety)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: product_information.php");
        exit;
    }
    
    // Insert product into database
    $stmt = $conn->prepare("INSERT INTO products (farmer_id, product_name, product_type, product_variety) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $product_name, $product_type, $product_variety);
    
    if ($stmt->execute()) {
        $product_id = $conn->insert_id;
        
        // Add seasonality if provided
        if (!empty($seasonality)) {
            addSeasonality($conn, $product_id, $seasonality);
        }
        
        $_SESSION['success'] = "Product added successfully.";
    } else {
        $_SESSION['error'] = "Error adding product: " . $conn->error;
    }
    
    header("Location: product_information.php");
    exit;
}

// Function to edit an existing product
function editProduct($conn, $user_id) {
    $product_id = $_POST['product_id'];
    $product_name = trim($_POST['product_name']);
    $product_type = trim($_POST['product_type']);
    $product_variety = trim($_POST['product_variety']);
    $seasonality = isset($_POST['seasonality']) ? $_POST['seasonality'] : [];
    
    // Validate input
    if (empty($product_id) || empty($product_name) || empty($product_type) || empty($product_variety)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: product_information.php");
        exit;
    }
    
    // Verify that this product belongs to the current user
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND farmer_id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error'] = "You do not have permission to edit this product.";
        header("Location: product_information.php");
        exit;
    }
    
    // Update product in database
    $stmt = $conn->prepare("UPDATE products SET product_name = ?, product_type = ?, product_variety = ? WHERE id = ?");
    $stmt->bind_param("sssi", $product_name, $product_type, $product_variety, $product_id);
    
    if ($stmt->execute()) {
        // Delete existing seasonality
        $stmt = $conn->prepare("DELETE FROM product_seasonality WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        
        // Add new seasonality if provided
        if (!empty($seasonality)) {
            addSeasonality($conn, $product_id, $seasonality);
        }
        
        $_SESSION['success'] = "Product updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating product: " . $conn->error;
    }
    
    header("Location: product_information.php");
    exit;
}

// Function to delete a product
function deleteProduct($conn, $user_id) {
    $product_id = $_POST['product_id'];
    
    // Verify that this product belongs to the current user
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND farmer_id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error'] = "You do not have permission to delete this product.";
        header("Location: product_information.php");
        exit;
    }
    
    // Delete product from database (seasonality will be deleted via foreign key constraint)
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting product: " . $conn->error;
    }
    
    header("Location: product_information.php");
    exit;
}

// Helper function to add seasonality for a product
function addSeasonality($conn, $product_id, $seasons) {
    $stmt = $conn->prepare("INSERT INTO product_seasonality (product_id, season) VALUES (?, ?)");
    
    foreach ($seasons as $season) {
        $stmt->bind_param("is", $product_id, $season);
        $stmt->execute();
    }
}

// Close database connection
$conn->close();
?>