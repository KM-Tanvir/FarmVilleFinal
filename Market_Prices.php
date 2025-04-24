<?php
include "db.php";
$host = "localhost";
$dbname = "farm_management";
$username = "root";
$password = "";

// Establish database connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["error" => "Connection failed: " . $e->getMessage()]);
    exit();
}

// Function to get market prices with optional filters
function getMarketPrices($conn, $category = "", $trend = "", $elasticity = "", $search = "") {
    // Start building the SQL query
    $sql = "SELECT p.product_id, p.product_name, c.category_name as category, 
            mp.current_price, mp.previous_price, mp.price_elasticity, 
            DATE_FORMAT(mp.last_updated, '%M %d, %Y') as last_updated
            FROM market_prices mp
            JOIN products p ON mp.product_id = p.product_id
            JOIN categories c ON p.category_id = c.category_id
            WHERE 1=1";
    
    $params = [];
    
    // Add category filter if specified
    if (!empty($category)) {
        $sql .= " AND c.category_name = :category";
        $params[':category'] = $category;
    }
    
    // Add price trend filter if specified
    if (!empty($trend)) {
        switch ($trend) {
            case 'up':
                $sql .= " AND mp.current_price > mp.previous_price";
                break;
            case 'down':
                $sql .= " AND mp.current_price < mp.previous_price";
                break;
            case 'stable':
                $sql .= " AND mp.current_price = mp.previous_price";
                break;
        }
    }
    
    // Add elasticity filter if specified
    if (!empty($elasticity)) {
        switch ($elasticity) {
            case 'high':
                $sql .= " AND mp.price_elasticity > 1.5";
                break;
            case 'medium':
                $sql .= " AND mp.price_elasticity BETWEEN 0.5 AND 1.5";
                break;
            case 'low':
                $sql .= " AND mp.price_elasticity < 0.5";
                break;
        }
    }
    
    // Add search filter if specified
    if (!empty($search)) {
        $sql .= " AND (p.product_name LIKE :search OR c.category_name LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    // Order by product name
    $sql .= " ORDER BY p.product_name ASC";
    
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return ["error" => "Query failed: " . $e->getMessage()];
    }
}

// Function to get price history for a specific product
function getPriceHistory($conn, $productId) {
    $sql = "SELECT 
            DATE_FORMAT(ph.price_date, '%M %d, %Y') as date,
            ph.price,
            ph.percent_change,
            ph.market_conditions
            FROM price_history ph
            WHERE ph.product_id = :product_id
            ORDER BY ph.price_date DESC
            LIMIT 10";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return ["error" => "Query failed: " . $e->getMessage()];
    }
}

// Check which endpoint is being requested
$requestUri = $_SERVER['REQUEST_URI'];

// Handle "get_market_prices.php" endpoint
if (strpos($requestUri, 'get_market_prices.php') !== false) {
    // Get filter parameters from query string
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $trend = isset($_GET['trend']) ? $_GET['trend'] : '';
    $elasticity = isset($_GET['elasticity']) ? $_GET['elasticity'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Get market prices data
    $prices = getMarketPrices($conn, $category, $trend, $elasticity, $search);
    
    // Return data as JSON
    header('Content-Type: application/json');
    echo json_encode($prices);
    exit();
}

// Handle "get_price_history.php" endpoint
if (strpos($requestUri, 'get_price_history.php') !== false) {
    // Get product ID from query string
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    
    if ($productId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Invalid product ID"]);
        exit();
    }
    
    // Get price history data
    $history = getPriceHistory($conn, $productId);
    
    // Return data as JSON
    header('Content-Type: application/json');
    echo json_encode($history);
    exit();
}

// Handle the main market_prices.php page
// This is the default that will be executed if the URI doesn't match any of the endpoints above

// Include the HTML/CSS content here if you want to combine both files
// Otherwise, this script will just prepare the data and the HTML file will include it
?>