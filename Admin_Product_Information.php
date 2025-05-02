<?php
include "db.php";
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate product data
function validateProduct($product) {
    $errors = [];
    
    if (empty($product['name'])) {
        $errors[] = 'Product name is required';
    }
    
    if (empty($product['type'])) {
        $errors[] = 'Product type is required';
    }
    
    if (empty($product['variety'])) {
        $errors[] = 'Product variety is required';
    }
    
    if (empty($product['seasonality'])) {
        $errors[] = 'Seasonality is required';
    }
    
    if (!isset($product['price']) || !is_numeric($product['price']) || $product['price'] < 0) {
        $errors[] = 'Valid price is required';
    }
    
    if (empty($product['unit'])) {
        $errors[] = 'Unit is required';
    }
    
    if (empty($product['status'])) {
        $errors[] = 'Status is required';
    }
    
    return $errors;
}

// Get all products with optional filtering
function getProducts($conn, $filters = []) {
    try {
        $sql = "SELECT * FROM products WHERE 1=1";
        $params = [];
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $sql .= " AND (name LIKE ? OR description LIKE ? OR variety LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Apply type filter
        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }
        
        // Apply status filter
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        // Add sorting
        $sql .= " ORDER BY name ASC";
        
        // Add pagination
        if (isset($filters['page']) && isset($filters['limit'])) {
            $page = max(1, (int)$filters['page']);
            $limit = max(1, (int)$filters['limit']);
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Get product count (for pagination)
function getProductCount($conn, $filters = []) {
    try {
        $sql = "SELECT COUNT(*) as total FROM products WHERE 1=1";
        $params = [];
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $sql .= " AND (name LIKE ? OR description LIKE ? OR variety LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Apply type filter
        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }
        
        // Apply status filter
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Get product by ID
function getProductById($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return null;
        }
        
        return $product;
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Create new product
function createProduct($conn, $productData) {
    try {
        $sql = "INSERT INTO products (name, type, variety, seasonality, price, unit, description, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $productData['name'],
            $productData['type'],
            $productData['variety'],
            $productData['seasonality'],
            $productData['price'],
            $productData['unit'],
            $productData['description'],
            $productData['status']
        ]);
        
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Update existing product
function updateProduct($conn, $id, $productData) {
    try {
        $sql = "UPDATE products SET 
                name = ?, 
                type = ?, 
                variety = ?, 
                seasonality = ?, 
                price = ?, 
                unit = ?, 
                description = ?, 
                status = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $productData['name'],
            $productData['type'],
            $productData['variety'],
            $productData['seasonality'],
            $productData['price'],
            $productData['unit'],
            $productData['description'],
            $productData['status'],
            $id
        ]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Delete product
function deleteProduct($conn, $id) {
    try {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Get product price history
function getProductPriceHistory($conn, $productId) {
    try {
        $stmt = $conn->prepare("
            SELECT price, recorded_date 
            FROM product_price_history 
            WHERE product_id = ? 
            ORDER BY recorded_date ASC
        ");
        $stmt->execute([$productId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Update product price and record in history
function updateProductPrice($conn, $productId, $newPrice) {
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Update the current price
        $stmtUpdate = $conn->prepare("UPDATE products SET price = ?, updated_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$newPrice, $productId]);
        
        // Record the price change in history
        $stmtHistory = $conn->prepare("
            INSERT INTO product_price_history (product_id, price, recorded_date)
            VALUES (?, ?, NOW())
        ");
        $stmtHistory->execute([$productId, $newPrice]);
        
        // Commit transaction
        $conn->commit();
        
        return true;
    } catch (PDOException $e) {
        // Roll back transaction on error
        $conn->rollBack();
        
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Get available product types
function getProductTypes($conn) {
    try {
        $stmt = $conn->prepare("SELECT DISTINCT type FROM products ORDER BY type");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// API request handler
// Check request method and handle accordingly
$requestMethod = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

// Set headers for JSON response
header('Content-Type: application/json');

// Handle API requests based on HTTP method
switch ($requestMethod) {
    case 'GET':
        // Check if we're getting a list or a specific product
        if (isset($_GET['id'])) {
            $productId = (int)$_GET['id'];
            $product = getProductById($conn, $productId);
            
            if ($product) {
                if (isset($_GET['include_price_history']) && $_GET['include_price_history'] === 'true') {
                    $product['price_history'] = getProductPriceHistory($conn, $productId);
                }
                echo json_encode(['success' => true, 'data' => $product]);
            } else {
                header('HTTP/1.1 404 Not Found');
                echo json_encode(['error' => 'Product not found']);
            }
        } else {
            // Get filter parameters
            $filters = [
                'search' => isset($_GET['search']) ? sanitizeInput($_GET['search']) : '',
                'type' => isset($_GET['type']) ? sanitizeInput($_GET['type']) : '',
                'status' => isset($_GET['status']) ? sanitizeInput($_GET['status']) : '',
                'page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
                'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : 10
            ];
            
            $products = getProducts($conn, $filters);
            $total = getProductCount($conn, $filters);
            
            echo json_encode([
                'success' => true, 
                'data' => $products,
                'pagination' => [
                    'total' => $total,
                    'page' => $filters['page'],
                    'limit' => $filters['limit'],
                    'pages' => ceil($total / $filters['limit'])
                ]
            ]);
        }
        break;
        
    case 'POST':
        checkAuth(); // Ensure user is authenticated
        
        // Get JSON data from request body
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if (!$data) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Invalid JSON data']);
            break;
        }
        
        // Clean and validate data
        $productData = [
            'name' => sanitizeInput($data['name'] ?? ''),
            'type' => sanitizeInput($data['type'] ?? ''),
            'variety' => sanitizeInput($data['variety'] ?? ''),
            'seasonality' => sanitizeInput($data['seasonality'] ?? ''),
            'price' => isset($data['price']) ? (float)$data['price'] : 0,
            'unit' => sanitizeInput($data['unit'] ?? ''),
            'description' => sanitizeInput($data['description'] ?? ''),
            'status' => sanitizeInput($data['status'] ?? 'Active')
        ];
        
        // Validate product data
        $errors = validateProduct($productData);
        if (!empty($errors)) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Validation failed', 'details' => $errors]);
            break;
        }
        
        // Create new product
        $newProductId = createProduct($conn, $productData);
        
        // Return success response with the new product ID
        echo json_encode(['success' => true, 'message' => 'Product created successfully', 'id' => $newProductId]);
        break;
        
    case 'PUT':
        checkAuth(); // Ensure user is authenticated
        
        // Check if ID is provided
        if (!isset($_GET['id'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Product ID is required']);
            break;
        }
        
        $productId = (int)$_GET['id'];
        
        // Check if product exists
        $existingProduct = getProductById($conn, $productId);
        if (!$existingProduct) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Product not found']);
            break;
        }
        
        // Get JSON data from request body
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if (!$data) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Invalid JSON data']);
            break;
        }
        
        // Clean and validate data
        $productData = [
            'name' => sanitizeInput($data['name'] ?? $existingProduct['name']),
            'type' => sanitizeInput($data['type'] ?? $existingProduct['type']),
            'variety' => sanitizeInput($data['variety'] ?? $existingProduct['variety']),
            'seasonality' => sanitizeInput($data['seasonality'] ?? $existingProduct['seasonality']),
            'price' => isset($data['price']) ? (float)$data['price'] : $existingProduct['price'],
            'unit' => sanitizeInput($data['unit'] ?? $existingProduct['unit']),
            'description' => sanitizeInput($data['description'] ?? $existingProduct['description']),
            'status' => sanitizeInput($data['status'] ?? $existingProduct['status'])
        ];
        
        // Validate product data
        $errors = validateProduct($productData);
        if (!empty($errors)) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Validation failed', 'details' => $errors]);
            break;
        }
        
        // Check if price has changed
        $priceChanged = $productData['price'] != $existingProduct['price'];
        
        // Update product
        $updated = updateProduct($conn, $productId, $productData);
        
        // If price changed, record it in history
        if ($updated && $priceChanged) {
            updateProductPrice($conn, $productId, $productData['price']);
        }
        
        if ($updated) {
            echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['error' => 'Failed to update product']);
        }
        break;
        
    case 'DELETE':
        checkAuth(); // Ensure user is authenticated
        
        // Check if ID is provided
        if (!isset($_GET['id'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Product ID is required']);
            break;
        }
        
        $productId = (int)$_GET['id'];
        
        // Delete the product
        $deleted = deleteProduct($conn, $productId);
        
        if ($deleted) {
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
        } else {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Product not found or could not be deleted']);
        }
        break;
        
    default:
        header('HTTP/1.1 405 Method Not Allowed');
        echo json_encode(['error' => 'Method not allowed']);
        break;
}