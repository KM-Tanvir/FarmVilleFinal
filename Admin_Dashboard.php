<?php
include "db.php";
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    // Redirect to login page if not logged in as admin
    header('Location: Login_Page.php');
    exit();
}

// Database connection
function connectToDatabase() {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = ''; // Set your password in production
    $db_name = 'farm_management';
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Get total number of farmers
function getTotalFarmers() {
    $conn = connectToDatabase();
    $sql = "SELECT COUNT(*) as total FROM farmers";
    $result = $conn->query($sql);
    
    if ($result) {
        $row = $result->fetch_assoc();
        $conn->close();
        return $row['total'];
    }
    
    $conn->close();
    return 0;
}

// Get total number of products
function getTotalProducts() {
    $conn = connectToDatabase();
    $sql = "SELECT COUNT(*) as total FROM products";
    $result = $conn->query($sql);
    
    if ($result) {
        $row = $result->fetch_assoc();
        $conn->close();
        return $row['total'];
    }
    
    $conn->close();
    return 0;
}

// Get total farm acreage
function getTotalAcres() {
    $conn = connectToDatabase();
    $sql = "SELECT SUM(size_acres) as total FROM farms";
    $result = $conn->query($sql);
    
    if ($result) {
        $row = $result->fetch_assoc();
        $conn->close();
        return $row['total'] ? number_format($row['total']) : 0;
    }
    
    $conn->close();
    return 0;
}

// Get recent farmers (most recently added/modified)
function getRecentFarmers($limit = 5) {
    $conn = connectToDatabase();
    $sql = "SELECT f.id, f.name, fa.farm_name, fa.farm_type, fa.size_acres 
            FROM farmers f 
            JOIN farms fa ON f.id = fa.farmer_id 
            ORDER BY f.date_added DESC 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $farmers = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $farmers[] = $row;
        }
    }
    
    $stmt->close();
    $conn->close();
    
    return $farmers;
}

// Update farmer information
function updateFarmer($farmerId, $data) {
    $conn = connectToDatabase();
    
    // Update farmer table
    $sql = "UPDATE farmers SET name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $data['name'], $farmerId);
    $farmerUpdated = $stmt->execute();
    $stmt->close();
    
    // Update farm table
    $sql = "UPDATE farms SET farm_name = ?, farm_type = ?, size_acres = ? WHERE farmer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdi", $data['farm_name'], $data['farm_type'], $data['size_acres'], $farmerId);
    $farmUpdated = $stmt->execute();
    $stmt->close();
    
    $conn->close();
    
    return $farmerUpdated && $farmUpdated;
}

// Delete farmer
function deleteFarmer($farmerId) {
    $conn = connectToDatabase();
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete from farms table first (foreign key constraint)
        $sql = "DELETE FROM farms WHERE farmer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $farmerId);
        $stmt->execute();
        $stmt->close();
        
        // Delete from farmers table
        $sql = "DELETE FROM farmers WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $farmerId);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        $conn->close();
        
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->close();
        return false;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'update_farmer':
            $farmerId = isset($_POST['farmer_id']) ? intval($_POST['farmer_id']) : 0;
            $data = [
                'name' => $_POST['name'],
                'farm_name' => $_POST['farm_name'],
                'farm_type' => $_POST['farm_type'],
                'size_acres' => floatval($_POST['size_acres'])
            ];
            
            $success = updateFarmer($farmerId, $data);
            echo json_encode(['success' => $success]);
            break;
            
        case 'delete_farmer':
            $farmerId = isset($_POST['farmer_id']) ? intval($_POST['farmer_id']) : 0;
            $success = deleteFarmer($farmerId);
            echo json_encode(['success' => $success]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
    exit();
}

// Get dashboard data for HTML rendering
$totalFarmers = getTotalFarmers();
$totalProducts = getTotalProducts();
$totalAcres = getTotalAcres();
$recentFarmers = getRecentFarmers(3); // Get 3 most recent farmers

// Include the HTML template
include 'Admin_Dashboard_template.php';
?>