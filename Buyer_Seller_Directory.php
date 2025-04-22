<?php
// Database connection
$host = "localhost";
$username = "root";
$password = "password";
$database = "agricultural_management";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");

// Function to get all buyers with filters
function getAllBuyers($conn, $limit = 10, $offset = 0, $search = "", $region = "", $product = "") {
    $sql = "SELECT * FROM buyers WHERE 1=1";
    
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " AND (company_name LIKE '%$search%' OR contact_person LIKE '%$search%')";
    }
    
    if (!empty($region)) {
        $region = $conn->real_escape_string($region);
        $sql .= " AND region = '$region'";
    }
    
    if (!empty($product)) {
        $product = $conn->real_escape_string($product);
        $sql .= " AND products LIKE '%$product%'";
    }
    
    $sql .= " ORDER BY company_name LIMIT $offset, $limit";
    
    $result = $conn->query($sql);
    $buyers = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $buyers[] = $row;
        }
    }
    
    return $buyers;
}

// Function to get all sellers with filters
function getAllSellers($conn, $limit = 10, $offset = 0, $search = "", $region = "", $product = "") {
    $sql = "SELECT * FROM sellers WHERE 1=1";
    
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " AND (company_name LIKE '%$search%' OR contact_person LIKE '%$search%')";
    }
    
    if (!empty($region)) {
        $region = $conn->real_escape_string($region);
        $sql .= " AND region = '$region'";
    }
    
    if (!empty($product)) {
        $product = $conn->real_escape_string($product);
        $sql .= " AND products LIKE '%$product%'";
    }
    
    $sql .= " ORDER BY company_name LIMIT $offset, $limit";
    
    $result = $conn->query($sql);
    $sellers = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sellers[] = $row;
        }
    }
    
    return $sellers;
}

// Count total buyers for pagination
function countBuyers($conn, $search = "", $region = "", $product = "") {
    $sql = "SELECT COUNT(*) as total FROM buyers WHERE 1=1";
    
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " AND (company_name LIKE '%$search%' OR contact_person LIKE '%$search%')";
    }
    
    if (!empty($region)) {
        $region = $conn->real_escape_string($region);
        $sql .= " AND region = '$region'";
    }
    
    if (!empty($product)) {
        $product = $conn->real_escape_string($product);
        $sql .= " AND products LIKE '%$product%'";
    }
    
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Count total sellers for pagination
function countSellers($conn, $search = "", $region = "", $product = "") {
    $sql = "SELECT COUNT(*) as total FROM sellers WHERE 1=1";
    
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " AND (company_name LIKE '%$search%' OR contact_person LIKE '%$search%')";
    }
    
    if (!empty($region)) {
        $region = $conn->real_escape_string($region);
        $sql .= " AND region = '$region'";
    }
    
    if (!empty($product)) {
        $product = $conn->real_escape_string($product);
        $sql .= " AND products LIKE '%$product%'";
    }
    
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Get buyer by ID
function getBuyerById($conn, $id) {
    $id = $conn->real_escape_string($id);
    $sql = "SELECT * FROM buyers WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get seller by ID
function getSellerById($conn, $id) {
    $id = $conn->real_escape_string($id);
    $sql = "SELECT * FROM sellers WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Add new buyer
function addBuyer($conn, $data) {
    $company_name = $conn->real_escape_string($data['company_name']);
    $contact_person = $conn->real_escape_string($data['contact_person']);
    $contact_title = $conn->real_escape_string($data['contact_title']);
    $email = $conn->real_escape_string($data['email']);
    $phone = $conn->real_escape_string($data['phone']);
    $address = $conn->real_escape_string($data['address']);
    $region = $conn->real_escape_string($data['region']);
    $operating_since = $conn->real_escape_string($data['operating_since']);
    $products = $conn->real_escape_string(implode(", ", $data['products']));
    $notes = $conn->real_escape_string($data['notes']);
    
    $sql = "INSERT INTO buyers (company_name, contact_person, contact_title, email, phone, address, region, operating_since, products, notes) 
            VALUES ('$company_name', '$contact_person', '$contact_title', '$email', '$phone', '$address', '$region', '$operating_since', '$products', '$notes')";
    
    if ($conn->query($sql) === TRUE) {
        return $conn->insert_id;
    } else {
        return false;
    }
}

// Add new seller
function addSeller($conn, $data) {
    $company_name = $conn->real_escape_string($data['company_name']);
    $contact_person = $conn->real_escape_string($data['contact_person']);
    $contact_title = $conn->real_escape_string($data['contact_title']);
    $email = $conn->real_escape_string($data['email']);
    $phone = $conn->real_escape_string($data['phone']);
    $address = $conn->real_escape_string($data['address']);
    $region = $conn->real_escape_string($data['region']);
    $operating_since = $conn->real_escape_string($data['operating_since']);
    $products = $conn->real_escape_string(implode(", ", $data['products']));
    $notes = $conn->real_escape_string($data['notes']);
    
    $sql = "INSERT INTO sellers (company_name, contact_person, contact_title, email, phone, address, region, operating_since, products, notes) 
            VALUES ('$company_name', '$contact_person', '$contact_title', '$email', '$phone', '$address', '$region', '$operating_since', '$products', '$notes')";
    
    if ($conn->query($sql) === TRUE) {
        return $conn->insert_id;
    } else {
        return false;
    }
}

// Update buyer
function updateBuyer($conn, $id, $data) {
    $id = $conn->real_escape_string($id);
    $company_name = $conn->real_escape_string($data['company_name']);
    $contact_person = $conn->real_escape_string($data['contact_person']);
    $contact_title = $conn->real_escape_string($data['contact_title']);
    $email = $conn->real_escape_string($data['email']);
    $phone = $conn->real_escape_string($data['phone']);
    $address = $conn->real_escape_string($data['address']);
    $region = $conn->real_escape_string($data['region']);
    $operating_since = $conn->real_escape_string($data['operating_since']);
    $products = $conn->real_escape_string(implode(", ", $data['products']));
    $notes = $conn->real_escape_string($data['notes']);
    
    $sql = "UPDATE buyers SET 
            company_name = '$company_name', 
            contact_person = '$contact_person', 
            contact_title = '$contact_title', 
            email = '$email', 
            phone = '$phone', 
            address = '$address', 
            region = '$region', 
            operating_since = '$operating_since', 
            products = '$products', 
            notes = '$notes' 
            WHERE id = $id";
    
    return $conn->query($sql) === TRUE;
}

// Update seller
function updateSeller($conn, $id, $data) {
    $id = $conn->real_escape_string($id);
    $company_name = $conn->real_escape_string($data['company_name']);
    $contact_person = $conn->real_escape_string($data['contact_person']);
    $contact_title = $conn->real_escape_string($data['contact_title']);
    $email = $conn->real_escape_string($data['email']);
    $phone = $conn->real_escape_string($data['phone']);
    $address = $conn->real_escape_string($data['address']);
    $region = $conn->real_escape_string($data['region']);
    $operating_since = $conn->real_escape_string($data['operating_since']);
    $products = $conn->real_escape_string(implode(", ", $data['products']));
    $notes = $conn->real_escape_string($data['notes']);
    
    $sql = "UPDATE sellers SET 
            company_name = '$company_name', 
            contact_person = '$contact_person', 
            contact_title = '$contact_title', 
            email = '$email', 
            phone = '$phone', 
            address = '$address', 
            region = '$region', 
            operating_since = '$operating_since', 
            products = '$products', 
            notes = '$notes' 
            WHERE id = $id";
    
    return $conn->query($sql) === TRUE;
}

// Delete buyer
function deleteBuyer($conn, $id) {
    $id = $conn->real_escape_string($id);
    $sql = "DELETE FROM buyers WHERE id = $id";
    return $conn->query($sql) === TRUE;
}

// Delete seller
function deleteSeller($conn, $id) {
    $id = $conn->real_escape_string($id);
    $sql = "DELETE FROM sellers WHERE id = $id";
    return $conn->query($sql) === TRUE;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $response = [];
    $type = $_POST['type'];
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update existing entity
        $id = $_POST['id'];
        
        if ($type == 'buyer') {
            if (updateBuyer($conn, $id, $_POST)) {
                $response = ['status' => 'success', 'message' => 'Buyer updated successfully'];
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to update buyer: ' . $conn->error];
            }
        } else {
            if (updateSeller($conn, $id, $_POST)) {
                $response = ['status' => 'success', 'message' => 'Seller updated successfully'];
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to update seller: ' . $conn->error];
            }
        }
    } else {
        // Add new entity
        if ($type == 'buyer') {
            $result = addBuyer($conn, $_POST);
            if ($result) {
                $response = ['status' => 'success', 'message' => 'Buyer added successfully', 'id' => $result];
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to add buyer: ' . $conn->error];
            }
        } else {
            $result = addSeller($conn, $_POST);
            if ($result) {
                $response = ['status' => 'success', 'message' => 'Seller added successfully', 'id' => $result];
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to add seller: ' . $conn->error];
            }
        }
    }
    
    // Return JSON response for AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Redirect for form submissions
    $redirect = $response['status'] == 'success' ? 'buyer_seller_directory.php?status=success&message=' . urlencode($response['message']) : 'buyer_seller_directory.php?status=error&message=' . urlencode($response['message']);
    header("Location: $redirect");
    exit;
}

// Handle AJAX request to get data
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action'])) {
    $response = [];
    
    switch ($_GET['action']) {
        case 'get_buyers':
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $offset = ($page - 1) * $limit;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $region = isset($_GET['region']) ? $_GET['region'] : '';
            $product = isset($_GET['product']) ? $_GET['product'] : '';
            
            $buyers = getAllBuyers($conn, $limit, $offset, $search, $region, $product);
            $total = countBuyers($conn, $search, $region, $product);
            
            $response = [
                'status' => 'success',
                'data' => $buyers,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ];
            break;
            
        case 'get_sellers':
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $offset = ($page - 1) * $limit;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $region = isset($_GET['region']) ? $_GET['region'] : '';
            $product = isset($_GET['product']) ? $_GET['product'] : '';
            
            $sellers = getAllSellers($conn, $limit, $offset, $search, $region, $product);
            $total = countSellers($conn, $search, $region, $product);
            
            $response = [
                'status' => 'success',
                'data' => $sellers,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ];
            break;
            
        case 'get_buyer':
            if (isset($_GET['id'])) {
                $buyer = getBuyerById($conn, $_GET['id']);
                if ($buyer) {
                    $response = ['status' => 'success', 'data' => $buyer];
                } else {
                    $response = ['status' => 'error', 'message' => 'Buyer not found'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'ID is required'];
            }
            break;
            
        case 'get_seller':
            if (isset($_GET['id'])) {
                $seller = getSellerById($conn, $_GET['id']);
                if ($seller) {
                    $response = ['status' => 'success', 'data' => $seller];
                } else {
                    $response = ['status' => 'error', 'message' => 'Seller not found'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'ID is required'];
            }
            break;
            
        case 'delete_buyer':
            if (isset($_GET['id'])) {
                if (deleteBuyer($conn, $_GET['id'])) {
                    $response = ['status' => 'success', 'message' => 'Buyer deleted successfully'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Failed to delete buyer: ' . $conn->error];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'ID is required'];
            }
            break;
            
        case 'delete_seller':
            if (isset($_GET['id'])) {
                if (deleteSeller($conn, $_GET['id'])) {
                    $response = ['status' => 'success', 'message' => 'Seller deleted successfully'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Failed to delete seller: ' . $conn->error];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'ID is required'];
            }
            break;
            
        default:
            $response = ['status' => 'error', 'message' => 'Invalid action'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Close connection
$conn->close();
?>