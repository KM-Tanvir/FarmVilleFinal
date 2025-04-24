<?php
include "db.php";
/**
 * Inventory Management System - Backend API
 * 
 * This file handles all server-side operations for inventory management including:
 * - Retrieving inventory data
 * - Updating inventory levels
 * - Processing shipments
 * - Applying filters
 */

// Database connection configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'agritech_user');
define('DB_PASSWORD', 'your_secure_password');
define('DB_NAME', 'agritech_db');

// Initialize database connection
$conn = null;

/**
 * Connect to the database
 */
function connectDB() {
    global $conn;
    
    try {
        $conn = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        returnError("Connection failed: " . $e->getMessage());
    }
}

/**
 * Close database connection
 */
function closeDB() {
    global $conn;
    $conn = null;
}

/**
 * Return error response
 */
function returnError($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit();
}

/**
 * Return success response
 */
function returnSuccess($data) {
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);
    exit();
}

/**
 * Process API request based on method and action
 */
function handleRequest() {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Get the request action from URL parameter
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    switch ($method) {
        case 'GET':
            handleGET($action);
            break;
        case 'POST':
            handlePOST($action);
            break;
        case 'PUT':
            handlePUT($action);
            break;
        default:
            returnError("Unsupported HTTP method", 405);
    }
}

/**
 * Handle GET requests
 */
function handleGET($action) {
    switch ($action) {
        case 'getInventory':
            getInventory();
            break;
        case 'getShipments':
            getShipments();
            break;
        case 'getStorageStats':
            getStorageStats();
            break;
        default:
            returnError("Unknown action", 400);
    }
}

/**
 * Handle POST requests
 */
function handlePOST($action) {
    $postData = json_decode(file_get_contents('php://input'), true);
    
    if (!$postData && $action != 'moveInventory') {
        returnError("Invalid request data", 400);
    }
    
    switch ($action) {
        case 'updateInventory':
            updateInventory($postData);
            break;
        case 'moveInventory':
            moveInventory($postData);
            break;
        case 'createShipment':
            createShipment($postData);
            break;
        case 'updateShipmentStatus':
            updateShipmentStatus($postData);
            break;
        default:
            returnError("Unknown action", 400);
    }
}

/**
 * Handle PUT requests
 */
function handlePUT($action) {
    $putData = json_decode(file_get_contents('php://input'), true);
    
    if (!$putData) {
        returnError("Invalid request data", 400);
    }
    
    switch ($action) {
        case 'updateShipment':
            updateShipment($putData);
            break;
        default:
            returnError("Unknown action", 400);
    }
}

/**
 * Get inventory data with optional filters
 */
function getInventory() {
    global $conn;
    connectDB();
    
    // Get filter parameters
    $product = isset($_GET['product']) ? $_GET['product'] : '';
    $storage = isset($_GET['storage']) ? $_GET['storage'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    // Base query
    $query = "SELECT 
                i.id,
                p.name AS product,
                s.name AS storage_location,
                i.current_level,
                s.capacity,
                CASE 
                    WHEN i.current_level/s.capacity < 0.3 THEN 'critical'
                    WHEN i.current_level/s.capacity < 0.7 THEN 'warning'
                    ELSE 'good'
                END AS status,
                i.unit,
                i.last_updated
            FROM inventory i
            JOIN products p ON i.product_id = p.id
            JOIN storage_locations s ON i.storage_id = s.id
            WHERE 1=1";
    
    $params = [];
    
    // Add filters to query
    if (!empty($product)) {
        $query .= " AND p.name = :product";
        $params[':product'] = $product;
    }
    
    if (!empty($storage)) {
        $query .= " AND s.name = :storage";
        $params[':storage'] = $storage;
    }
    
    if (!empty($status)) {
        if ($status == 'critical') {
            $query .= " AND i.current_level/s.capacity < 0.3";
        } elseif ($status == 'warning') {
            $query .= " AND i.current_level/s.capacity >= 0.3 AND i.current_level/s.capacity < 0.7";
        } elseif ($status == 'good') {
            $query .= " AND i.current_level/s.capacity >= 0.7";
        }
    }
    
    try {
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate percentage for UI display
        foreach ($inventory as &$item) {
            $item['percentage'] = ($item['current_level'] / $item['capacity']) * 100;
        }
        
        returnSuccess($inventory);
    } catch (PDOException $e) {
        returnError("Database error: " . $e->getMessage());
    } finally {
        closeDB();
    }
}

/**
 * Get upcoming and in-progress shipments
 */
function getShipments() {
    global $conn;
    connectDB();
    
    try {
        $query = "SELECT 
                    s.id,
                    p.name AS product,
                    s.quantity,
                    s.origin,
                    s.destination,
                    s.expected_date,
                    s.status,
                    s.unit
                FROM shipments s
                JOIN products p ON s.product_id = p.id
                WHERE s.expected_date >= CURDATE()
                ORDER BY s.expected_date ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        returnSuccess($shipments);
    } catch (PDOException $e) {
        returnError("Database error: " . $e->getMessage());
    } finally {
        closeDB();
    }
}

/**
 * Get storage usage statistics
 */
function getStorageStats() {
    global $conn;
    connectDB();
    
    try {
        // Get total storage usage percentage
        $query1 = "SELECT 
                    ROUND((SUM(i.current_level) / SUM(s.capacity)) * 100, 1) AS usage_percentage
                FROM inventory i
                JOIN storage_locations s ON i.storage_id = s.id";
        
        $stmt1 = $conn->prepare($query1);
        $stmt1->execute();
        $storageUsage = $stmt1->fetch(PDO::FETCH_ASSOC);
        
        // Get pending shipments count
        $query2 = "SELECT COUNT(*) AS pending_count
                  FROM shipments
                  WHERE status IN ('On Schedule', 'Delayed', 'Ready')";
        
        $stmt2 = $conn->prepare($query2);
        $stmt2->execute();
        $pendingShipments = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        // Get low stock items count
        $query3 = "SELECT COUNT(*) AS low_stock_count
                  FROM inventory i
                  JOIN storage_locations s ON i.storage_id = s.id
                  WHERE i.current_level/s.capacity < 0.3";
        
        $stmt3 = $conn->prepare($query3);
        $stmt3->execute();
        $lowStock = $stmt3->fetch(PDO::FETCH_ASSOC);
        
        $stats = [
            'storage_usage' => $storageUsage['usage_percentage'],
            'pending_shipments' => $pendingShipments['pending_count'],
            'low_stock_items' => $lowStock['low_stock_count']
        ];
        
        returnSuccess($stats);
    } catch (PDOException $e) {
        returnError("Database error: " . $e->getMessage());
    } finally {
        closeDB();
    }
}

/**
 * Update inventory levels
 */
function updateInventory($data) {
    global $conn;
    connectDB();
    
    if (!isset($data['id']) || !isset($data['new_level'])) {
        returnError("Missing required fields", 400);
    }
    
    try {
        $conn->beginTransaction();
        
        // Update inventory level
        $query = "UPDATE inventory 
                  SET current_level = :new_level, last_updated = NOW() 
                  WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $data['id']);
        $stmt->bindValue(':new_level', $data['new_level']);
        $stmt->execute();
        
        // Check if inventory was found and updated
        if ($stmt->rowCount() == 0) {
            $conn->rollBack();
            returnError("Inventory item not found", 404);
        }
        
        // Log the update in inventory_history
        $query2 = "INSERT INTO inventory_history (inventory_id, previous_level, new_level, updated_by, update_date)
                  VALUES (:inventory_id, :previous_level, :new_level, :updated_by, NOW())";
        
        $stmt2 = $conn->prepare($query2);
        $stmt2->bindValue(':inventory_id', $data['id']);
        $stmt2->bindValue(':previous_level', $data['previous_level']);
        $stmt2->bindValue(':new_level', $data['new_level']);
        $stmt2->bindValue(':updated_by', $data['user_id'] ?? 1); // Default to admin user if not provided
        $stmt2->execute();
        
        $conn->commit();
        
        returnSuccess(["message" => "Inventory updated successfully"]);
    } catch (PDOException $e) {
        $conn->rollBack();
        returnError("Database error: " . $e->getMessage());
    } finally {
        closeDB();
    }
}

/**
 * Move inventory between storage locations
 */
function moveInventory($data) {
    global $conn;
    connectDB();
    
    if (!isset($data['source_id']) || !isset($data['destination_id']) || !isset($data['quantity']) || !isset($data['product_id'])) {
        returnError("Missing required fields", 400);
    }
    
    try {
        $conn->beginTransaction();
        
        // Check if source has enough quantity
        $query1 = "SELECT current_level FROM inventory 
                  WHERE storage_id = :source_id AND product_id = :product_id";
        
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindValue(':source_id', $data['source_id']);
        $stmt1->bindValue(':product_id', $data['product_id']);
        $stmt1->execute();
        $sourceInventory = $stmt1->fetch(PDO::FETCH_ASSOC);
        
        if (!$sourceInventory || $sourceInventory['current_level'] < $data['quantity']) {
            $conn->rollBack();
            returnError("Insufficient quantity in source location", 400);
        }
        
        // Check if destination has enough capacity
        $query2 = "SELECT i.current_level, s.capacity 
                  FROM inventory i
                  JOIN storage_locations s ON i.storage_id = s.id
                  WHERE i.storage_id = :destination_id AND i.product_id = :product_id";
        
        $stmt2 = $conn->prepare($query2);
        $stmt2->bindValue(':destination_id', $data['destination_id']);
        $stmt2->bindValue(':product_id', $data['product_id']);
        $stmt2->execute();
        $destInventory = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        // If no existing inventory at destination, check storage capacity
        if (!$destInventory) {
            $query2b = "SELECT capacity FROM storage_locations WHERE id = :destination_id";
            $stmt2b = $conn->prepare($query2b);
            $stmt2b->bindValue(':destination_id', $data['destination_id']);
            $stmt2b->execute();
            $destStorage = $stmt2b->fetch(PDO::FETCH_ASSOC);
            
            if (!$destStorage) {
                $conn->rollBack();
                returnError("Destination storage location not found", 404);
            }
            
            // Create new inventory entry at destination
            $query3 = "INSERT INTO inventory (product_id, storage_id, current_level, unit, last_updated)
                      VALUES (:product_id, :storage_id, :quantity, :unit, NOW())";
            
            $stmt3 = $conn->prepare($query3);
            $stmt3->bindValue(':product_id', $data['product_id']);
            $stmt3->bindValue(':storage_id', $data['destination_id']);
            $stmt3->bindValue(':quantity', $data['quantity']);
            $stmt3->bindValue(':unit', $data['unit']);
            $stmt3->execute();
        } else {
            // Check if adding would exceed capacity
            if (($destInventory['current_level'] + $data['quantity']) > $destInventory['capacity']) {
                $conn->rollBack();
                returnError("Insufficient capacity in destination location", 400);
            }
            
            // Update destination inventory
            $query3 = "UPDATE inventory 
                      SET current_level = current_level + :quantity, last_updated = NOW() 
                      WHERE storage_id = :destination_id AND product_id = :product_id";
            
            $stmt3 = $conn->prepare($query3);
            $stmt3->bindValue(':quantity', $data['quantity']);
            $stmt3->bindValue(':destination_id', $data['destination_id']);
            $stmt3->bindValue(':product_id', $data['product_id']);
            $stmt3->execute();
        }
        
        // Update source inventory
        $query4 = "UPDATE inventory 
                  SET current_level = current_level - :quantity, last_updated = NOW() 
                  WHERE storage_id = :source_id AND product_id = :product_id";
        
        $stmt4 = $conn->prepare($query4);
        $stmt4->bindValue(':quantity', $data['quantity']);
        $stmt4->bindValue(':source_id', $data['source_id']);
        $stmt4->bindValue(':product_id', $data['product_id']);
        $stmt4->execute();
        
        // Log the movement
        $query5 = "INSERT INTO inventory_movements 
                  (product_id, source_id, destination_id, quantity, unit, moved_by, movement_date)
                  VALUES (:product_id, :source_id, :destination_id, :quantity, :unit, :moved_by, NOW())";
        
        $stmt5 = $conn->prepare($query5);
        $stmt5->bindValue(':product_id', $data['product_id']);
        $stmt5->bindValue(':source_id', $data['source_id']);
        $stmt5->bindValue(':destination_id', $data['destination_id']);
        $stmt5->bindValue(':quantity', $data['quantity']);
        $stmt5->bindValue(':unit', $data['unit']);
        $stmt5->bindValue(':moved_by', $data['user_id'] ?? 1); // Default to admin user if not provided
        $stmt5->execute();
        
        $conn->commit();
        
        returnSuccess(["message" => "Inventory moved successfully"]);
    } catch (PDOException $e) {
        $conn->rollBack();
        returnError("Database error: " . $e->getMessage());
    } finally {
        closeDB();
    }
}

/**
 * Create a new shipment
 */
function createShipment($data) {
    global $conn;
    connectDB();
    
    if (!isset($data['product_id']) || !isset($data['quantity']) || !isset($data['origin']) || 
        !isset($data['destination']) || !isset($data['expected_date'])) {
        returnError("Missing required fields", 400);
    }
    
    try {
        $query = "INSERT INTO shipments 
                  (product_id, quantity, unit, origin, destination, expected_date, status, created_by, created_date)
                  VALUES (:product_id, :quantity, :unit, :origin, :destination, :expected_date, :status, :created_by, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':product_id', $data['product_id']);
        $stmt->bindValue(':quantity', $data['quantity']);
        $stmt->bindValue(':unit', $data['unit'] ?? 'lbs');
        $stmt->bindValue(':origin', $data['origin']);
        $stmt->bindValue(':destination', $data['destination']);
        $stmt->bindValue(':expected_date', $data['expected_date']);
        $stmt->bindValue(':status', $data['status'] ?? 'On Schedule');
        $stmt->bindValue(':created_by', $data['user_id'] ?? 1);
        $stmt->execute();
        
        $shipmentId = $conn->lastInsertId();
        
        returnSuccess([
            "message" => "Shipment created successfully",
            "shipment_id" => $shipmentId
        ]);
    } catch (PDOException $e) {
        returnError("Database error: " . $e->getMessage());
    } finally {
        closeDB();
    }
}

/**
 * Update shipment status
 */
function updateShipmentStatus($data) {
    global $conn;
    connectDB();
    
    if (!isset($data['id']) || !isset($data['status'])) {
        returnError("Missing required fields", 400);
    }
    
    try {
        $query = "UPDATE shipments 
                  SET status = :status, last_updated = NOW(), updated_by = :updated_by 
                  WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $data['id']);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':updated_by', $data['user_id'] ?? 1);
        $stmt->execute();
        
        // If status is "Completed", update inventory accordingly
        if ($data['status'] == 'Completed') {
            // Get shipment details
            $query2 = "SELECT product_id, quantity, origin, destination FROM shipments WHERE id = :id";
            $stmt2 = $conn->prepare($query2);
            $stmt2->bindValue(':id', $data['id']);
            $stmt2->execute();
            $shipment = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if ($shipment) {
                // Handle inventory changes based on origin and destination
                // This is a simplified version - in a real system, you'd want more robust logic
                
                // If origin is a storage location in our system, reduce that inventory
                if (strpos($shipment['origin'], 'Warehouse') !== false || strpos($shipment['origin'], 'Storage') !== false || strpos($shipment['origin'], 'Silo') !== false) {
                    $query3 = "UPDATE inventory i
                              JOIN storage_locations s ON i.storage_id = s.id
                              SET i.current_level = i.current_level - :quantity, i.last_updated = NOW()
                              WHERE s.name = :origin AND i.product_id = :product_id";
                    
                    $stmt3 = $conn->prepare($query3);
                    $stmt3->bindValue(':quantity', $shipment['quantity']);
                    $stmt3->bindValue(':origin', $shipment['origin']);
                    $stmt3->bindValue(':product_id', $shipment['product_id']);
                    $stmt3->execute();
                }
                
                // If destination is a storage location in our system, increase that inventory
                if (strpos($shipment['destination'], 'Warehouse') !== false || strpos($shipment['destination'], 'Storage') !== false || strpos($shipment['destination'], 'Silo') !== false) {
                    // Check if inventory record exists
                    $query4 = "SELECT i.id, i.current_level
                              FROM inventory i
                              JOIN storage_locations s ON i.storage_id = s.id
                              WHERE s.name = :destination AND i.product_id = :product_id";
                    
                    $stmt4 = $conn->prepare($query4);
                    $stmt4->bindValue(':destination', $shipment['destination']);
                    $stmt4->bindValue(':product_id', $shipment['product_id']);
                    $stmt4->execute();
                    $destInventory = $stmt4->fetch(PDO::FETCH_ASSOC);
                    
                    if ($destInventory) {
                        // Update existing inventory
                        $query5 = "UPDATE inventory
                                  SET current_level = current_level + :quantity, last_updated = NOW()
                                  WHERE id = :id";
                        
                        $stmt5 = $conn->prepare($query5);
                        $stmt5->bindValue(':quantity', $shipment['quantity']);
                        $stmt5->bindValue(':id', $destInventory['id']);
                        $stmt5->execute();
                    } else {
                        // Get storage location id
                        $query6 = "SELECT id FROM storage_locations WHERE name = :name";
                        $stmt6 = $conn->prepare($query6);
                        $stmt6->bindValue(':name', $shipment['destination']);
                        $stmt6->execute();
                        $storage = $stmt6->fetch(PDO::FETCH_ASSOC);
                        
                        if ($storage) {
                            // Create new inventory record
                            $query7 = "INSERT INTO inventory (product_id, storage_id, current_level, unit, last_updated)
                                      VALUES (:product_id, :storage_id, :quantity, :unit, NOW())";
                            
                            $stmt7 = $conn->prepare($query7);
                            $stmt7->bindValue(':product_id', $shipment['product_id']);
                            $stmt7->bindValue(':storage_id', $storage['id']);
                            $stmt7->bindValue(':quantity', $shipment['quantity']);
                            $stmt7->bindValue(':unit', $data['unit'] ?? 'lbs');
                            $stmt7->execute();
                        }
                    }
                }
            }
        }
        
        returnSuccess(["message" => "Shipment status updated successfully"]);
    } catch (PDOException $e) {
        returnError("Database error: " . $e->getMessage());
    } finally {
        closeDB();
    }
}

/**
 * Update shipment details
 */
function updateShipment($data) {
    global $conn;
    connectDB();
    
    if (!isset($data['id'])) {
        returnError("Missing shipment ID", 400);
    }
    
    try {
        $query = "UPDATE shipments SET ";
        $params = [];
        
        // Build dynamic update query based on provided fields
        if (isset($data['quantity'])) {
            $query .= "quantity = :quantity, ";
            $params[':quantity'] = $data['quantity'];
        }
        
        if (isset($data['origin'])) {
            $query .= "origin = :origin, ";
            $params[':origin'] = $data['origin'];
        }
        
        if (isset($data['destination'])) {
            $query .= "destination = :destination, ";
            $params[':destination'] = $data['destination'];
        }
        
        if (isset($data['expected_date'])) {
            $query .= "expected_date = :expected_date, ";
            $params[':expected_date'] = $data['expected_date'];
        }
        
        if (isset($data['status'])) {
            $query .= "status = :status, ";
            $params[':status'] = $data['status'];
        }
        
        // Add timestamp and user info
        $query .= "last_updated = NOW(), updated_by = :updated_by WHERE id = :id";
        $params[':updated_by'] = $data['user_id'] ?? 1;
        $params[':id'] = $data['id'];
        
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            returnError("Shipment not found or no changes made", 404);
        }
        
        returnSuccess(["message" => "Shipment updated successfully"]);
    } catch (PDOException $e) {
        returnError("Database error: " . $e->getMessage());
    } finally {
        closeDB();
    }
}

// Main execution
handleRequest();
?>