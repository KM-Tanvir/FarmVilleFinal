<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmville";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Constants
define('UNIT_CAPACITY', 25000); // Fixed capacity per storage unit in kg

// Get all storage units
$unitsQuery = "SELECT * FROM storage_unit";
$unitsResult = $conn->query($unitsQuery);
$totalUnits = $unitsResult->num_rows;

// Calculate total capacity
$totalCapacity = $totalUnits * UNIT_CAPACITY;

// Calculate total used space
$usedQuery = "SELECT SUM(quantity) as total_used FROM storage_unit";
$usedResult = $conn->query($usedQuery);
$totalUsed = $usedResult->fetch_assoc()['total_used'] ?? 0;

// Calculate available space
$availableSpace = max(0, $totalCapacity - $totalUsed);

// Calculate warehouse utilization
$warehouseUtilization = ($totalCapacity > 0) ? ($totalUsed / $totalCapacity) * 100 : 0;
$warehouseUtilizationFormatted = number_format($warehouseUtilization, 2);

// Status determination
if ($warehouseUtilization <= 50) {
    $warehouseStatus = "Good";
    $warehouseStatusClass = "status-good";
} elseif ($warehouseUtilization <= 80) {
    $warehouseStatus = "Warning";
    $warehouseStatusClass = "status-warning";
} else {
    $warehouseStatus = "Critical";
    $warehouseStatusClass = "status-critical";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FarmVille - Storage Units</title>
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

    /* Storage Unit Cards */
    .unit-card {
      background: #e1f5fe;
      border-radius: 20px;
      padding: 1.5rem;
      box-shadow: 0 8px 16px rgba(3, 169, 244, 0.1);
      margin-bottom: 2rem;
      border: 1px solid #81d4fa;
    }

    .unit-card:hover {
      box-shadow: 0 12px 24px rgba(25, 118, 210, 0.15);
    }

    .unit-info h3 {
      font-size: 1.4rem;
      margin-bottom: 1rem;
      color: #0d47a1;
      display: flex;
      align-items: center;
    }

    .unit-info h3 i {
      margin-right: 10px;
    }

    .status-summary {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 1.2rem;
      list-style: none;
      padding-left: 0;
    }

    .status-summary li {
      display: flex;
      align-items: center;
      background: #ffffff;
      padding: 1rem;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      gap: 0.75rem;
      border: 1px solid #b3e5fc;
    }

    .status-summary li i {
      font-size: 1.2rem;
      color: #0288d1;
    }

    .status-summary li strong {
      color: #333;
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

    /* Unit Grid */
    .units-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 1.5rem;
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
      
      .status-summary, .summary-grid {
        grid-template-columns: 1fr;
      }
      
      .units-grid {
        grid-template-columns: 1fr;
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
      <li><a href="storage_units.php" class="active"><i class="fas fa-warehouse"></i> <span>Storage Units</span></a></li>
      <li><a href="add_stock.php"><i class="fas fa-boxes"></i> <span>Add Stock</span></a></li>
      <li><a href="product-movement.php"><i class="fas fa-exchange-alt"></i> <span>Product Movement</span></a></li>
      <li><a href="manage-storage.php"><i class="fas fa-tools"></i> <span>Manage Storage</span></a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Top Navigation -->
    <div class="top-nav">
      <a href="manage-storage.php" class="add-storage-btn">
        <i class="fas fa-plus"></i> Add Storage Unit
      </a>
      <div class="user-info">
        <span class="username">Admin</span>
        <div class="user-icon"><i class="fas fa-user-cog"></i></div>
      </div>
    </div>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
      <h1><i class="fas fa-warehouse"></i> Storage Units</h1>
    </div>

    <!-- Warehouse Summary -->
    <div class="warehouse-summary">
      <h2><i class="fas fa-warehouse"></i> Warehouse Overview</h2>
      <div class="summary-grid">
        <div class="summary-item">
          <h3>Total Units</h3>
          <p><?php echo $totalUnits; ?></p>
        </div>
        <div class="summary-item">
          <h3>Total Capacity</h3>
          <p><?php echo number_format($totalCapacity); ?> kg</p>
        </div>
        <div class="summary-item">
          <h3>Space Used</h3>
          <p><?php echo number_format($totalUsed, 2); ?> kg</p>
        </div>
        <div class="summary-item">
          <h3>Space Available</h3>
          <p><?php echo number_format($availableSpace, 2); ?> kg</p>
        </div>
        <div class="summary-item">
          <h3>Utilization</h3>
          <p class="<?php echo $warehouseStatusClass; ?>"><?php echo $warehouseUtilizationFormatted; ?>% (<?php echo $warehouseStatus; ?>)</p>
        </div>
      </div>
      
      <div style="margin-top: 1.5rem;">
        <h3>Warehouse Capacity Utilization</h3>
        <div class="progress-bar">
          <div class="progress-bar-inner" style="width: <?php echo $warehouseUtilizationFormatted; ?>%;"></div>
        </div>
        <p style="text-align: right; margin-top:5px;"><?php echo $warehouseUtilizationFormatted; ?>% Used</p>
      </div>
    </div>

    <!-- Storage Units Grid -->
    <h2 style="color: #0d47a1; margin-bottom: 1rem; display: flex; align-items: center;">
      <i class="fas fa-boxes"></i> Storage Units
    </h2>
    
    <div class="units-grid">
      <?php
      if ($unitsResult->num_rows > 0) {
          $unitsResult->data_seek(0); // Reset pointer
          while($row = $unitsResult->fetch_assoc()) {
              $storageId = $row['storage_id'];
              $productName = $row['product_name'];
              $type = $row['type'];
              $quantity = $row['quantity'];
              $location = $row['location'];
              $storeDate = date('d M Y', strtotime($row['store_date']));
              $notes = $row['notes'];
              
              $unitCapacity = UNIT_CAPACITY;
              $usedPercent = ($unitCapacity > 0) ? ($quantity / $unitCapacity) * 100 : 0;
              $usedPercentFormatted = number_format($usedPercent, 2);
              $availableSpace = max(0, $unitCapacity - $quantity);

              if ($usedPercent <= 50) {
                  $status = "Good";
                  $statusClass = "status-good";
              } elseif ($usedPercent <= 80) {
                  $status = "Warning";
                  $statusClass = "status-warning";
              } else {
                  $status = "Critical";
                  $statusClass = "status-critical";
              }
      ?>

      <div class="unit-card">
        <div class="unit-info">
          <h3><i class="fas fa-box-open"></i> <?php echo htmlspecialchars($location); ?></h3>
          <ul class="status-summary">
            <li><i class="fas fa-id-card"></i> ID: <strong>SU-<?php echo str_pad($storageId, 3, '0', STR_PAD_LEFT); ?></strong></li>
            <li><i class="fas fa-boxes"></i> Capacity: <strong><?php echo number_format($unitCapacity); ?> kg</strong></li>
            <li><i class="fas fa-weight-hanging"></i> Used: <strong><?php echo number_format($quantity, 2); ?> kg</strong></li>
            <li><i class="fas fa-archive"></i> Available: <strong><?php echo number_format($availableSpace, 2); ?> kg</strong></li>
            <li><i class="fas fa-percentage"></i> Utilization: <strong><?php echo $usedPercentFormatted; ?>%</strong></li>
            <li><i class="fas fa-carrot"></i> Product: <strong><?php echo htmlspecialchars($productName); ?></strong></li>
            <li><i class="fas fa-calendar-alt"></i> Stored: <strong><?php echo $storeDate; ?></strong></li>
            <li><i class="fas fa-traffic-light"></i> Status: <strong class="<?php echo $statusClass; ?>"><?php echo $status; ?></strong></li>
            <?php if (!empty($notes)): ?>
            <li><i class="fas fa-sticky-note"></i> Notes: <strong><?php echo htmlspecialchars($notes); ?></strong></li>
            <?php endif; ?>
          </ul>
        </div>

        <div style="margin-top: 1.5rem;">
          <h3>Unit Capacity Utilization</h3>
          <div class="progress-bar">
            <div class="progress-bar-inner" style="width: <?php echo $usedPercentFormatted; ?>%;"></div>
          </div>
          <p style="text-align: right; margin-top:5px;"><?php echo $usedPercentFormatted; ?>% Used</p>
        </div>
      </div>

      <?php
          }
      } else {
          echo "<div class='unit-card'><p style='text-align:center;'>No storage units found. Add your first storage unit to get started.</p></div>";
      }
      ?>
    </div>
  </div>
</body>
</html>
<?php
$conn->close();
?>