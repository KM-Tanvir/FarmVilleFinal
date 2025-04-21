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

// Function to get all products for the current farmer
function getProducts($conn, $user_id, $search = "", $page = 1, $itemsPerPage = 10) {
    $offset = ($page - 1) * $itemsPerPage;
    
    $searchCondition = "";
    if (!empty($search)) {
        $search = "%" . $search . "%";
        $searchCondition = "AND (
            p.product_name LIKE ? OR 
            p.product_type LIKE ? OR 
            p.product_variety LIKE ?
        )";
    }
    
    $sql = "SELECT p.*, GROUP_CONCAT(s.season) as seasons
            FROM products p
            LEFT JOIN product_seasonality s ON p.id = s.product_id
            WHERE p.farmer_id = ? $searchCondition
            GROUP BY p.id
            ORDER BY p.product_name
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($search)) {
        $stmt->bind_param("issii", $user_id, $search, $search, $search, $itemsPerPage, $offset);
    } else {
        $stmt->bind_param("iii", $user_id, $itemsPerPage, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $row['seasons'] = explode(',', $row['seasons']);
        $products[] = $row;
    }
    
    return $products;
}

// Function to count total products for pagination
function countProducts($conn, $user_id, $search = "") {
    $searchCondition = "";
    if (!empty($search)) {
        $search = "%" . $search . "%";
        $searchCondition = "AND (
            product_name LIKE ? OR 
            product_type LIKE ? OR 
            product_variety LIKE ?
        )";
    }
    
    $sql = "SELECT COUNT(*) as total FROM products WHERE farmer_id = ? $searchCondition";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($search)) {
        $stmt->bind_param("isss", $user_id, $search, $search, $search);
    } else {
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'];
}

// Default page and search parameters
$search = isset($_GET['search']) ? $_GET['search'] : "";
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$itemsPerPage = 10;

// Process search if submitted
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $page = 1; // Reset to first page on new search
}

// Get products and total count for pagination
$products = getProducts($conn, $user_id, $search, $page, $itemsPerPage);
$totalProducts = countProducts($conn, $user_id, $search);
$totalPages = ceil($totalProducts / $itemsPerPage);

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<!-- The HTML code would normally go here, but we've provided it separately in the HTML+CSS file -->