<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmville";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Constants
define('UNIT_CAPACITY', 25000); // Fixed capacity per storage unit in kg

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Process form submissions
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['harvest_submit'])) {
        // Process Harvest Form
        $storage_id = 'STO-' . uniqid();
        $product_name = sanitizeInput($_POST['product_name']);
        $type = sanitizeInput($_POST['type']);
        $quantity = (float)sanitizeInput($_POST['quantity']);
        $store_date = sanitizeInput($_POST['store_date']);
        $location = sanitizeInput($_POST['location']);
        $notes = sanitizeInput($_POST['notes']);
        
        if ($quantity <= 0) {
            $error_message = "Quantity must be a positive number";
        } else {
            $capacityCheck = $conn->prepare("SELECT 
                COUNT(*) as unit_count,
                SUM(quantity) as total_quantity
                FROM storage_unit 
                WHERE location = ?");
            $capacityCheck->bind_param("s", $location);
            $capacityCheck->execute();
            $capacityResult = $capacityCheck->get_result()->fetch_assoc();
            $capacityCheck->close();
            
            $currentUsed = $capacityResult['total_quantity'] ?? 0;
            $unitCount = $capacityResult['unit_count'] ?? 0;
            $totalCapacity = $unitCount * UNIT_CAPACITY;
            $availableSpace = $totalCapacity - $currentUsed;
            
            if ($quantity > $availableSpace) {
                $error_message = "Not enough space in $location. Available: " . number_format($availableSpace, 2) . " kg";
            } else {
                $sql = "INSERT INTO storage_unit (storage_id, product_id, product_name, type, quantity, location, store_date, collection_date, notes) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?)";
                
                $stmt = $conn->prepare($sql);
                $product_id = $storage_id;
                $stmt->bind_param("ssssdsss", $storage_id, $product_id, $product_name, $type, $quantity, $location, $store_date, $notes);
                
                if ($stmt->execute()) {
                    $success_message = "Harvest stored successfully in $location! Storage ID: " . $storage_id;
                } else {
                    $error_message = "Error storing harvest: " . $conn->error;
                }
                $stmt->close();
            }
        }
        
    } elseif (isset($_POST['outgoing_submit'])) {
        // Process Outgoing Stock Form
        $storage_id = 'OUT-' . uniqid();
        $product_name = sanitizeInput($_POST['outgoing_product_name']);
        $type = sanitizeInput($_POST['outgoing_type']);
        $quantity = abs((float)sanitizeInput($_POST['outgoing_quantity']));
        $collection_date = sanitizeInput($_POST['collection_date']);
        $location = sanitizeInput($_POST['outgoing_location']);
        $notes = "Outgoing stock to vendor";
        
        $stockCheck = $conn->prepare("SELECT 
            SUM(quantity) as total_quantity
            FROM storage_unit 
            WHERE location = ? AND product_name = ?");
        $stockCheck->bind_param("ss", $location, $product_name);
        $stockCheck->execute();
        $stockResult = $stockCheck->get_result()->fetch_assoc();
        $stockCheck->close();
        
        $currentStock = $stockResult['total_quantity'] ?? 0;
        
        if ($quantity > $currentStock) {
            $error_message = "Not enough $product_name in $location. Available: " . number_format($currentStock, 2) . " kg";
        } else {
            $sql = "INSERT INTO storage_unit (storage_id, product_id, product_name, type, quantity, location, store_date, collection_date, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $product_id = $storage_id;
            $outgoingQuantity = -$quantity;
            $stmt->bind_param("ssssdsss", $storage_id, $product_id, $product_name, $type, $outgoingQuantity, $location, $collection_date, $notes);
            
            if ($stmt->execute()) {
                $success_message = "Outgoing stock recorded from $location! Shipped: " . number_format($quantity, 2) . " kg of $product_name";
            } else {
                $error_message = "Error recording outgoing stock: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch storage locations for dropdowns
$locations = [];
$locationQuery = "SELECT DISTINCT location FROM storage_unit ORDER BY location";
$locationResult = $conn->query($locationQuery);
if ($locationResult->num_rows > 0) {
    while($row = $locationResult->fetch_assoc()) {
        $locations[] = $row['location'];
    }
}

// Fetch recent incoming and outgoing stock
$incoming_stock = [];
$outgoing_stock = [];

$incoming_query = "SELECT storage_id, product_name, type, quantity, location, store_date, notes 
                   FROM storage_unit 
                   WHERE quantity > 0 
                   ORDER BY store_date DESC 
                   LIMIT 5";
$incoming_result = $conn->query($incoming_query);
if ($incoming_result->num_rows > 0) {
    while($row = $incoming_result->fetch_assoc()) {
        $incoming_stock[] = $row;
    }
}

$outgoing_query = "SELECT storage_id, product_name, type, ABS(quantity) as quantity, location, collection_date 
                   FROM storage_unit 
                   WHERE quantity < 0 
                   ORDER BY collection_date DESC 
                   LIMIT 5";
$outgoing_result = $conn->query($outgoing_query);
if ($outgoing_result->num_rows > 0) {
    while($row = $outgoing_result->fetch_assoc()) {
        $outgoing_stock[] = $row;
    }
}

// Fetch current inventory by location
$inventory_by_location = [];
$inventory_query = "SELECT 
    location,
    product_name,
    SUM(quantity) as total_quantity,
    COUNT(*) as unit_count,
    SUM(quantity) / (COUNT(*) * " . UNIT_CAPACITY . ") * 100 as utilization
    FROM storage_unit
    GROUP BY location, product_name
    ORDER BY location, product_name";
$inventory_result = $conn->query($inventory_query);
if ($inventory_result->num_rows > 0) {
    while($row = $inventory_result->fetch_assoc()) {
        $inventory_by_location[$row['location']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FarmVille - Stock Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>


    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: #ffffff;
      color: #333;
      height: 100vh;
      display: flex;
    }

    /* Sidebar Styles */
    .sidebar {
      width: 250px;
      background-color: #b3e5fc;
      padding: 20px 0;
      height: 100vh;
      border-right: 3px solid #1976d2;
      transition: all 0.3s ease;
    }

    .logo {
      text-align: center;
      padding: 0 20px 20px;
      font-size: 24px;
      font-weight: bold;
      color: #0d47a1;
      border-bottom: 2px solid #1976d2;
      margin-bottom: 20px;
    }

    .logo i {
      margin-right: 10px;
      color: #0288d1;
    }

    .nav-links {
      padding: 0 20px;
    }

    .nav-links li {
      list-style: none;
      margin-bottom: 10px;
    }

    .nav-links li a {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      color: #333;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .nav-links li a:hover,
    .nav-links li a.active {
      background-color: #81d4fa;
      color: #01579b;
    }

    .nav-links li a i {
      margin-right: 10px;
      font-size: 18px;
      color: #0288d1;
    }

    .add-storage-btn {
      background-color: rgb(10, 85, 126);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
      transition: background 0.3s ease;
      margin-right: 15px;
      display: flex;
      align-items: center;
    }

    .add-storage-btn:hover {
      background-color: rgb(13, 90, 134);
    }

    .add-storage-btn i {
      margin-right: 8px;
    }

    /* Main Content Styles */
    .main-content {
      flex: 1;
      padding: 2rem;
      overflow-y: auto;
    }

    .top-nav {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      margin-bottom: 2rem;
    }

    .user-info {
      display: flex;
      align-items: center;
      background-color: #e1f5fe;
      padding: 8px 15px;
      border-radius: 20px;
      border: 1px solid #1976d2;
    }

    .user-info .username {
      margin-right: 10px;
      color: #0d47a1;
    }

    .user-info .user-icon i {
      font-size: 18px;
      color: #0288d1;
    }

    /* Dashboard Header */
    .dashboard-header {
      margin-bottom: 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .dashboard-header h1 {
      font-size: 1.8rem;
      color: #0d47a1;
      display: flex;
      align-items: center;
    }

    .dashboard-header h1 i {
      margin-right: 10px;
      color: #0288d1;
    }

    /* Warehouse Summary Card */
    .warehouse-summary {
      background: #e1f5fe;
      border-radius: 20px;
      padding: 1.5rem;
      box-shadow: 0 8px 16px rgba(3, 169, 244, 0.1);
      margin-bottom: 2rem;
      border: 1px solid #81d4fa;
    }

    .warehouse-summary h2 {
      color: #0d47a1;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
    }

    .warehouse-summary h2 i {
      margin-right: 10px;
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 1.5rem;
    }

    .summary-item {
      background: white;
      padding: 1rem;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      border: 1px solid #b3e5fc;
    }

    .summary-item h3 {
      color: #0d47a1;
      margin-bottom: 0.5rem;
      font-size: 1rem;
    }

    .summary-item p {
      font-size: 1.5rem;
      font-weight: bold;
      color: #333;
    }

    /* Inventory Status Cards */
    .inventory-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .inventory-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      border: 1px solid #b3e5fc;
    }

    .inventory-card h3 {
      color: #0d47a1;
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid #e1f5fe;
      display: flex;
      align-items: center;
    }

    .inventory-card h3 i {
      margin-right: 10px;
    }

    .inventory-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.5rem;
      padding: 0.5rem 0;
      border-bottom: 1px dashed #e1f5fe;
    }

    .inventory-item:last-child {
      border-bottom: none;
    }

    .inventory-item-name {
      font-weight: 500;
    }

    .inventory-item-value {
      font-weight: bold;
    }

    .utilization-good {
      color: #4CAF50;
    }

    .utilization-warning {
      color: #FFC107;
    }

    .utilization-critical {
      color: #F44336;
    }

    /* Form Containers */
    .form-container {
      background: #e1f5fe;
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 8px 16px rgba(3, 169, 244, 0.1);
      border: 1px solid #81d4fa;
      margin-bottom: 2rem;
    }

    .form-header {
      margin-bottom: 2rem;
    }

    .form-header h2 {
      color: #0d47a1;
      display: flex;
      align-items: center;
      margin-bottom: 0.5rem;
    }

    .form-header h2 i {
      margin-right: 10px;
    }

    .form-header p {
      color: #333;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: #0d47a1;
      font-weight: 500;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.75rem;
      border-radius: 8px;
      border: 1px solid #b3e5fc;
      font-size: 1rem;
      transition: border 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #0288d1;
    }

    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }

    .form-actions {
      margin-top: 2rem;
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
    }

    .btn {
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1rem;
      transition: all 0.3s ease;
      border: none;
    }

    .btn-primary {
      background-color: #0288d1;
      color: white;
    }

    .btn-primary:hover {
      background-color: #0277bd;
    }

    .btn-secondary {
      background-color: #e1f5fe;
      color: #0d47a1;
      border: 1px solid #0288d1;
    }

    .btn-secondary:hover {
      background-color: #b3e5fc;
    }

    /* Stock Tables */
    .stock-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 2rem;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .stock-table th,
    .stock-table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #b3e5fc;
    }

    .stock-table th {
      background-color: #e1f5fe;
      color: #0d47a1;
      font-weight: 600;
      position: sticky;
      top: 0;
    }

    .stock-table tr:hover {
      background-color: #f5fbff;
    }

    /* Tabs */
    .form-tabs {
      display: flex;
      margin-bottom: 20px;
      border-bottom: 1px solid #b3e5fc;
    }

    .tab-btn {
      padding: 10px 20px;
      background: none;
      border: none;
      cursor: pointer;
      font-size: 1rem;
      color: #0d47a1;
      border-bottom: 3px solid transparent;
      transition: all 0.3s ease;
    }

    .tab-btn.active {
      border-bottom: 3px solid #0288d1;
      font-weight: 500;
    }

    .tab-btn:hover:not(.active) {
      background-color: rgba(2, 136, 209, 0.1);
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    /* Message Styles */
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 8px;
      font-weight: 500;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    /* Section Headers */
    .section-header {
      margin: 2rem 0 1rem;
      color: #0d47a1;
      display: flex;
      align-items: center;
    }

    .section-header i {
      margin-right: 10px;
    }

    /* Progress Bar */
    .progress-bar {
      width: 100%;
      height: 20px;
      background-color: #e1f5fe;
      border-radius: 10px;
      overflow: hidden;
      margin-top: 10px;
    }

    .progress-bar-inner {
      height: 100%;
      background-color: #0288d1;
      transition: width 0.3s ease;
    }

    /* Status Classes */
    .status-good {
      color: #4CAF50;
    }

    .status-warning {
      color: #FFC107;
    }

    .status-critical {
      color: #F44336;
    }

    /* Capacity Info */
    #capacity-info {
      margin-top: 10px;
      font-size: 0.9rem;
      color: #666;
      padding: 5px;
      border-radius: 4px;
      background-color: #f5fbff;
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
      .sidebar {
        width: 80px;
        overflow: hidden;
      }

      .logo span, .nav-links li a span {
        display: none;
      }

      .nav-links li a {
        justify-content: center;
        padding: 15px 0;
      }

      .nav-links li a i {
        margin-right: 0;
        font-size: 20px;
      }

      .form-actions {
        flex-direction: column;
      }

      .btn {
        width: 100%;
      }

      .stock-table {
        display: block;
        overflow-x: auto;
      }

      .inventory-cards,
      .summary-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Inventory Cards - Enhanced Colorful Style */
.inventory-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.inventory-card {
  background: linear-gradient(135deg, #e1f5fe 0%, #f5fbff 100%);
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 12px rgba(3, 169, 244, 0.15);
  border: 1px solid #b3e5fc;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.inventory-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(25, 118, 210, 0.2);
}

.inventory-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 5px;
  height: 100%;
  background: linear-gradient(to bottom, #0288d1, #4fc3f7);
}

.inventory-card h3 {
  color: #0d47a1;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid rgba(179, 229, 252, 0.5);
  display: flex;
  align-items: center;
  font-size: 1.2rem;
}

.inventory-card h3 i {
  margin-right: 10px;
  color: #0288d1;
}

.inventory-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  padding: 0.75rem;
  border-radius: 8px;
  background-color: rgba(255, 255, 255, 0.7);
  transition: all 0.2s ease;
}

.inventory-item:hover {
  background-color: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.inventory-item-name {
  font-weight: 500;
  color: #333;
}

.inventory-item-value {
  font-weight: bold;
  color: #0d47a1;
}

/* Utilization Color Coding */
.utilization-good {
  color: #4CAF50;
  background-color: rgba(76, 175, 80, 0.1);
  padding: 2px 6px;
  border-radius: 4px;
}

.utilization-warning {
  color: #FFC107;
  background-color: rgba(255, 193, 7, 0.1);
  padding: 2px 6px;
  border-radius: 4px;
}

.utilization-critical {
  color: #F44336;
  background-color: rgba(244, 67, 54, 0.1);
  padding: 2px 6px;
  border-radius: 4px;
}

/* Total Summary Style */
.inventory-card .inventory-item[style*="border-top"] {
  background: linear-gradient(135deg, #e1f5fe 0%, #b3e5fc 100%);
  margin-top: 1.5rem;
  font-weight: 600;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .inventory-cards {
    grid-template-columns: 1fr;
  }
  
  .inventory-card {
    padding: 1.25rem;
  }
  
  .inventory-item {
    padding: 0.5rem;
  }
}
  </style>
</head>
<body>
  <!-- Sidebar Navigation -->
  <div class="sidebar">
    <div class="logo">
      <i class="fas fa-store-alt"></i> <span>FarmVille</span>
    </div>
    <ul class="nav-links">
      <li><a href="storage.php"><i class="fas fa-tachometer-alt"></i> <span>Home</span></a></li>
      <li><a href="storage_units.php"><i class="fas fa-warehouse"></i> <span>Storage Units</span></a></li>
      <li><a href="add_stock.php" class="active"><i class="fas fa-boxes"></i> <span>Add Stock</span></a></li>
      <li><a href="product-movement.php"><i class="fas fa-exchange-alt"></i> <span>Product Movement</span></a></li>
      <li><a href="manage-storage.php"><i class="fas fa-tools"></i> <span>Manage Storage</span></a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Top Navigation -->
    <div class="top-nav">
      <div class="user-info">
        <span class="username">Admin</span>
        <div class="user-icon"><i class="fas fa-user-cog"></i></div>
      </div>
    </div>

    <!-- Page Header -->
    <div class="dashboard-header">
      <h1><i class="fas fa-seedling"></i> Stock Management</h1>
    </div>

    <!-- Display success/error messages -->
    <?php if (!empty($success_message)): ?>
      <div class="alert alert-success">
        <?php echo $success_message; ?>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
      <div class="alert alert-error">
        <?php echo $error_message; ?>
      </div>
    <?php endif; ?>

    <!-- Current Inventory Status -->
    <h3 class="section-header"><i class="fas fa-clipboard-list"></i> Current Inventory by Location</h3>
    <div class="inventory-cards">
      <?php if (!empty($inventory_by_location)): ?>
        <?php foreach ($inventory_by_location as $location => $products): ?>
          <div class="inventory-card">
            <h3><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($location); ?></h3>
            <?php foreach ($products as $product): 
              $utilizationClass = '';
              if ($product['utilization'] > 80) {
                  $utilizationClass = 'utilization-critical';
              } elseif ($product['utilization'] > 50) {
                  $utilizationClass = 'utilization-warning';
              } else {
                  $utilizationClass = 'utilization-good';
              }
            ?>
              <div class="inventory-item">
                <span class="inventory-item-name"><?php echo htmlspecialchars($product['product_name']); ?></span>
                <span class="inventory-item-value">
                  <?php echo number_format($product['total_quantity'], 2); ?> kg
                  <span class="<?php echo $utilizationClass; ?>">
                    (<?php echo number_format($product['utilization'], 1); ?>%)
                  </span>
                </span>
              </div>
            <?php endforeach; ?>
            <?php 
              $totalUnits = count(array_unique(array_column($products, 'unit_count')));
              $totalCapacity = $totalUnits * UNIT_CAPACITY;
              $totalUsed = array_sum(array_column($products, 'total_quantity'));
              $locationUtilization = ($totalCapacity > 0) ? ($totalUsed / $totalCapacity) * 100 : 0;
            ?>
            <div class="inventory-item" style="margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #e1f5fe;">
              <span class="inventory-item-name"><strong>Total</strong></span>
              <span class="inventory-item-value">
                <strong><?php echo number_format($totalUsed, 2); ?> / <?php echo number_format($totalCapacity); ?> kg</strong>
                <span class="<?php 
                  echo $locationUtilization > 80 ? 'utilization-critical' : 
                       ($locationUtilization > 50 ? 'utilization-warning' : 'utilization-good'); 
                ?>">
                  (<?php echo number_format($locationUtilization, 1); ?>%)
                </span>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="inventory-card">
          <h3><i class="fas fa-info-circle"></i> No Inventory Data</h3>
          <p>No products have been stored yet.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Form Tabs -->
    <div class="form-tabs">
      <button class="tab-btn active" onclick="openTab(event, 'incomingTab')">Incoming Stock</button>
      <button class="tab-btn" onclick="openTab(event, 'outgoingTab')">Outgoing Stock</button>
    </div>

    <!-- Incoming Stock Tab -->
    <div id="incomingTab" class="tab-content active">
      <div class="form-container">
        <div class="form-header">
          <h2><i class="fas fa-edit"></i> Incoming Stock Information</h2>
          <p>Enter details about new products coming into storage</p>
        </div>

        <form id="incomingForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
          <div class="form-grid">
            <div class="form-group">
              <label for="product_name">Product Name</label>
              <input type="text" id="product_name" name="product_name" placeholder="e.g. Golden Wheat" required>
            </div>

            <div class="form-group">
              <label for="type">Product Type</label>
              <input type="text" id="type" name="type" placeholder="e.g. Grain" required>
            </div>

            <div class="form-group">
              <label for="quantity">Quantity (kg)</label>
              <input type="number" id="quantity" name="quantity" placeholder="e.g. 500" step="0.01" min="0.01" required>
            </div>

            <div class="form-group">
              <label for="location">Storage Location</label>
              <select id="location" name="location" required>
                <?php if (!empty($locations)): ?>
                  <?php foreach ($locations as $loc): ?>
                    <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="Main Warehouse">Main Warehouse</option>
                <?php endif; ?>
              </select>
              <div id="capacity-info"></div>
            </div>

            <div class="form-group">
              <label for="store_date">Harvest/Store Date</label>
              <input type="date" id="store_date" name="store_date" required>
            </div>

            <div class="form-group" style="grid-column: 1 / -1">
              <label for="notes">Additional Notes</label>
              <textarea id="notes" name="notes" placeholder="Any special instructions or observations"></textarea>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="resetForm('incomingForm')">Clear</button>
            <button type="submit" name="harvest_submit" class="btn btn-primary">Add to Storage</button>
          </div>
        </form>
      </div>

      <!-- Recent Incoming Stock -->
      <h3 class="section-header"><i class="fas fa-history"></i> Recent Incoming Stock</h3>
      <table class="stock-table">
        <thead>
          <tr>
            <th>Storage ID</th>
            <th>Product Name</th>
            <th>Type</th>
            <th>Quantity (kg)</th>
            <th>Location</th>
            <th>Harvest Date</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($incoming_stock)): ?>
            <?php foreach ($incoming_stock as $stock): ?>
              <tr>
                <td><?php echo htmlspecialchars($stock['storage_id']); ?></td>
                <td><?php echo htmlspecialchars($stock['product_name']); ?></td>
                <td><?php echo htmlspecialchars($stock['type']); ?></td>
                <td><?php echo number_format($stock['quantity'], 2); ?></td>
                <td><?php echo htmlspecialchars($stock['location']); ?></td>
                <td><?php echo date('M d, Y', strtotime($stock['store_date'])); ?></td>
                <td><?php echo htmlspecialchars($stock['notes'] ?? '-'); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center;">No incoming stock records found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Outgoing Stock Tab -->
    <div id="outgoingTab" class="tab-content">
      <div class="form-container">
        <div class="form-header">
          <h2><i class="fas fa-truck"></i> Outgoing Stock Information</h2>
          <p>Enter details of products being shipped out</p>
        </div>

        <form id="outgoingForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
          <div class="form-grid">
            <div class="form-group">
              <label for="outgoing_product_name">Product Name</label>
              <input type="text" id="outgoing_product_name" name="outgoing_product_name" placeholder="e.g. Organic Apples" required>
            </div>
            
            <div class="form-group">
              <label for="outgoing_type">Type</label>
              <input type="text" id="outgoing_type" name="outgoing_type" placeholder="e.g. Fruit" required>
            </div>

            <div class="form-group">
              <label for="outgoing_quantity">Quantity (kg)</label>
              <input type="number" id="outgoing_quantity" name="outgoing_quantity" placeholder="e.g. 200" step="0.01" min="0.01" required>
            </div>

            <div class="form-group">
              <label for="outgoing_location">From Location</label>
              <select id="outgoing_location" name="outgoing_location" required>
                <?php if (!empty($locations)): ?>
                  <?php foreach ($locations as $loc): ?>
                    <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="Main Warehouse">Main Warehouse</option>
                <?php endif; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="collection_date">Collection Date</label>
              <input type="date" id="collection_date" name="collection_date" required>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="resetForm('outgoingForm')">Clear</button>
            <button type="submit" name="outgoing_submit" class="btn btn-primary">Record Outgoing</button>
          </div>
        </form>
      </div>

      <!-- Recent Outgoing Stock -->
      <h3 class="section-header"><i class="fas fa-history"></i> Recent Outgoing Stock</h3>
      <table class="stock-table">
        <thead>
          <tr>
            <th>Reference ID</th>
            <th>Product Name</th>
            <th>Type</th>
            <th>Quantity (kg)</th>
            <th>From Location</th>
            <th>Collection Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($outgoing_stock)): ?>
            <?php foreach ($outgoing_stock as $stock): ?>
              <tr>
                <td><?php echo htmlspecialchars($stock['storage_id']); ?></td>
                <td><?php echo htmlspecialchars($stock['product_name']); ?></td>
                <td><?php echo htmlspecialchars($stock['type']); ?></td>
                <td><?php echo number_format($stock['quantity'], 2); ?></td>
                <td><?php echo htmlspecialchars($stock['location']); ?></td>
                <td><?php echo date('M d, Y', strtotime($stock['collection_date'])); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center;">No outgoing stock records found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    // Tab functionality
    function openTab(evt, tabName) {
      const tabContents = document.getElementsByClassName("tab-content");
      for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove("active");
      }

      const tabButtons = document.getElementsByClassName("tab-btn");
      for (let i = 0; i < tabButtons.length; i++) {
        tabButtons[i].classList.remove("active");
      }

      document.getElementById(tabName).classList.add("active");
      evt.currentTarget.classList.add("active");
    }

    // Form reset functionality
    function resetForm(formId) {
      document.getElementById(formId).reset();
    }

    // Auto-focus the first input field when tab changes
    document.querySelectorAll('.tab-btn').forEach(button => {
      button.addEventListener('click', function() {
        const tabId = this.getAttribute('onclick').match(/'([^']+)'/)[1];
        const firstInput = document.querySelector(`#${tabId} input`);
        if (firstInput) {
          firstInput.focus();
        }
      });
    });

    // Set default date to today for date fields
    document.addEventListener('DOMContentLoaded', function() {
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('store_date').value = today;
      document.getElementById('collection_date').value = today;
      
      // Initialize capacity check
      const initialLocation = document.getElementById('location').value;
      if (initialLocation) {
          checkLocationCapacity(initialLocation);
      }
    });

    // Add real-time capacity check for incoming form
    document.getElementById('location').addEventListener('change', function() {
        checkLocationCapacity(this.value);
    });
    
    function checkLocationCapacity(location) {
        fetch('get_location_capacity.php?location=' + encodeURIComponent(location))
            .then(response => response.json())
            .then(data => {
                const capacityInfo = document.getElementById('capacity-info');
                if (!capacityInfo) {
                    const div = document.createElement('div');
                    div.id = 'capacity-info';
                    div.style.marginTop = '10px';
                    div.style.fontSize = '0.9rem';
                    div.style.color = '#666';
                    document.getElementById('location').parentNode.appendChild(div);
                }
                
                const infoElement = document.getElementById('capacity-info');
                infoElement.innerHTML = `Capacity: ${data.available.toFixed(2)} kg available of ${data.capacity.toFixed(2)} kg`;
                
                if (data.available <= 0) {
                    infoElement.style.color = '#F44336';
                    infoElement.innerHTML += ' (FULL)';
                } else if (data.available < data.capacity * 0.2) {
                    infoElement.style.color = '#FFC107';
                    infoElement.innerHTML += ' (LOW SPACE)';
                } else {
                    infoElement.style.color = '#4CAF50';
                }
            })
            .catch(error => {
                console.error('Error checking capacity:', error);
            });
    }
  </script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>