<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'farm_admin');
define('DB_PASSWORD', 'your_secure_password');
define('DB_NAME', 'farm_management_system');

// Establish database connection
function connectDB() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }
    
    return $conn;
}

// Set headers for JSON response
header('Content-Type: application/json');

// Get the request method
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Handle different request methods
switch ($requestMethod) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get specific product
            getProduct($_GET['id']);
        } else {
            // Get all products with optional filters
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $category = isset($_GET['category']) ? $_GET['category'] : '';
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            
            getProducts($search, $category, $status);
        }
        break;
        
    case 'POST':
        // Add new product
        $data = json_decode(file_get_contents('php://input'), true);
        addProduct($data);
        break;
        
    case 'PUT':
        // Update existing product
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($_GET['id'])) {
            updateProduct($_GET['id'], $data);
        } else {
            echo json_encode(['error' => 'Product ID is required for update']);
        }
        break;
        
    case 'DELETE':
        // Delete product
        if (isset($_GET['id'])) {
            deleteProduct($_GET['id']);
        } else {
            echo json_encode(['error' => 'Product ID is required for deletion']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid request method']);
        break;
}

// Function to get all products with optional filters
function getProducts($search = '', $category = '', $status = '') {
    $conn = connectDB();
    
    // Base query
    $sql = "SELECT * FROM products WHERE 1=1";
    
    // Add filters if provided
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
    }
    
    if (!empty($category)) {
        $category = $conn->real_escape_string($category);
        $sql .= " AND category = '$category'";
    }
    
    if (!empty($status)) {
        $status = $conn->real_escape_string($status);
        $sql .= " AND status = '$status'";
    }
    
    $sql .= " ORDER BY name ASC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $products]);
    } else {
        echo json_encode(['error' => 'Error fetching products: ' . $conn->error]);
    }
    
    $conn->close();
}

// Function to get a specific product by ID
function getProduct($id) {
    $conn = connectDB();
    
    $id = $conn->real_escape_string($id);
    $sql = "SELECT * FROM products WHERE id = $id";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        echo json_encode(['error' => 'Product not found']);
    }
    
    $conn->close();
}

// Function to add a new product
function addProduct($data) {
    $conn = connectDB();
    
    // Validate required fields
    if (!isset($data['name']) || !isset($data['category']) || !isset($data['price']) || 
        !isset($data['stock']) || !isset($data['unit']) || !isset($data['status'])) {
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    // Sanitize inputs
    $name = $conn->real_escape_string($data['name']);
    $category = $conn->real_escape_string($data['category']);
    $price = floatval($data['price']);
    $stock = intval($data['stock']);
    $unit = $conn->real_escape_string($data['unit']);
    $description = isset($data['description']) ? $conn->real_escape_string($data['description']) : '';
    $status = $conn->real_escape_string($data['status']);
    
    // Insert product
    $sql = "INSERT INTO products (name, category, price, stock, unit, description, status, created_at) 
            VALUES ('$name', '$category', $price, $stock, '$unit', '$description', '$status', NOW())";
    
    if ($conn->query($sql) === TRUE) {
        $newId = $conn->insert_id;
        echo json_encode(['success' => true, 'message' => 'Product added successfully', 'id' => $newId]);
    } else {
        echo json_encode(['error' => 'Error adding product: ' . $conn->error]);
    }
    
    $conn->close();
}

// Function to update an existing product
function updateProduct($id, $data) {
    $conn = connectDB();
    
    // Sanitize inputs
    $id = $conn->real_escape_string($id);
    
    // Build update fields
    $updateFields = [];
    
    if (isset($data['name'])) {
        $name = $conn->real_escape_string($data['name']);
        $updateFields[] = "name = '$name'";
    }
    
    if (isset($data['category'])) {
        $category = $conn->real_escape_string($data['category']);
        $updateFields[] = "category = '$category'";
    }
    
    if (isset($data['price'])) {
        $price = floatval($data['price']);
        $updateFields[] = "price = $price";
    }
    
    if (isset($data['stock'])) {
        $stock = intval($data['stock']);
        $updateFields[] = "stock = $stock";
    }
    
    if (isset($data['unit'])) {
        $unit = $conn->real_escape_string($data['unit']);
        $updateFields[] = "unit = '$unit'";
    }
    
    if (isset($data['description'])) {
        $description = $conn->real_escape_string($data['description']);
        $updateFields[] = "description = '$description'";
    }
    
    if (isset($data['status'])) {
        $status = $conn->real_escape_string($data['status']);
        $updateFields[] = "status = '$status'";
    }
    
    $updateFields[] = "updated_at = NOW()";
    
    // Execute update if there are fields to update
    if (count($updateFields) > 0) {
        $sql = "UPDATE products SET " . implode(', ', $updateFields) . " WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        } else {
            echo json_encode(['error' => 'Error updating product: ' . $conn->error]);
        }
    } else {
        echo json_encode(['warning' => 'No fields to update']);
    }
    
    $conn->close();
}

// Function to delete a product
function deleteProduct($id) {
    $conn = connectDB();
    
    $id = $conn->real_escape_string($id);
    $sql = "DELETE FROM products WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        echo json_encode(['error' => 'Error deleting product: ' . $conn->error]);
    }
    
    $conn->close();
}

// Function to get price history for a product (for the chart)
function getPriceHistory($id) {
    $conn = connectDB();
    
    $id = $conn->real_escape_string($id);
    $sql = "SELECT price, date_changed FROM product_price_history WHERE product_id = $id ORDER BY date_changed ASC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $priceHistory = [];
        while ($row = $result->fetch_assoc()) {
            $priceHistory[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $priceHistory]);
    } else {
        echo json_encode(['error' => 'Error fetching price history: ' . $conn->error]);
    }
    
    $conn->close();
}

// Check if we need to create the database tables
function setupDatabase() {
    $conn = connectDB();
    
    // Create products table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        stock INT(11) NOT NULL,
        unit VARCHAR(50) NOT NULL,
        description TEXT,
        status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME DEFAULT NULL
    )";
    
    if ($conn->query($sql) !== TRUE) {
        echo json_encode(['error' => 'Error creating products table: ' . $conn->error]);
        return;
    }
    
    // Create price history table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS product_price_history (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        product_id INT(11) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        date_changed DATETIME NOT NULL,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) !== TRUE) {
        echo json_encode(['error' => 'Error creating price history table: ' . $conn->error]);
        return;
    }
    
    echo json_encode(['success' => true, 'message' => 'Database tables created successfully']);
    
    $conn->close();
}

// Uncomment the following line to setup database tables
// if (isset($_GET['setup'])) setupDatabase();
?>