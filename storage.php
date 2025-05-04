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
define('UNIT_CAPACITY', 25000); // 25,000kg per unit

// Function to get dashboard statistics using your storage unit logic
function getDashboardStats($conn) {
    $stats = array();
    
    try {
        // Get all storage units
        $unitsQuery = "SELECT * FROM storage_unit";
        $unitsResult = $conn->query($unitsQuery);
        $totalUnits = $unitsResult ? $unitsResult->num_rows : 0;
        
        // Calculate total capacity (25,000kg per unit)
        $totalCapacity = $totalUnits * UNIT_CAPACITY;
        
        // Calculate used capacity
        $usedQuery = "SELECT SUM(quantity) as total_used FROM storage_unit";
        $usedResult = $conn->query($usedQuery);
        $totalUsed = $usedResult ? ($usedResult->fetch_assoc()['total_used'] ?? 0) : 0;
        
        // Calculate utilization (with division by zero protection)
        $utilization = ($totalCapacity > 0) ? ($totalUsed / $totalCapacity) * 100 : 0;
        
        // Determine status
        if ($utilization < 50) {
            $status = 'good';
            $statusClass = 'status-good';
            $statusMessage = 'Optimal storage levels';
        } elseif ($utilization < 80) {
            $status = 'warning';
            $statusClass = 'status-warning';
            $statusMessage = 'Storage reaching capacity';
        } else {
            $status = 'critical';
            $statusClass = 'status-critical';
            $statusMessage = 'Storage nearly full!';
        }
        
        // Get locations
        $locationsQuery = "SELECT DISTINCT location FROM storage_unit";
        $locationsResult = $conn->query($locationsQuery);
        $totalLocations = $locationsResult ? $locationsResult->num_rows : 0;
        
        // Get top products
        $productsQuery = "SELECT product_name, SUM(quantity) as total 
                          FROM storage_unit 
                          WHERE quantity > 0 
                          GROUP BY product_name 
                          ORDER BY total DESC 
                          LIMIT 5";
        $productsResult = $conn->query($productsQuery);
        $topProducts = array();
        if ($productsResult) {
            while($row = $productsResult->fetch_assoc()) {
                $topProducts[] = $row;
            }
        }
        
        return array(
            'total_units' => $totalUnits,
            'total_capacity' => $totalCapacity / 1000, // in tons
            'used_capacity' => $totalUsed / 1000, // in tons
            'available_capacity' => ($totalCapacity - $totalUsed) / 1000, // in tons
            'utilization_rate' => round($utilization),
            'storage_status' => $status,
            'status_class' => $statusClass,
            'status_message' => $statusMessage,
            'total_locations' => $totalLocations,
            'products' => $topProducts
        );
    } catch (Exception $e) {
        // Return empty stats if there's an error
        return array(
            'total_units' => 0,
            'total_capacity' => 0,
            'used_capacity' => 0,
            'available_capacity' => 0,
            'utilization_rate' => 0,
            'storage_status' => 'good',
            'status_class' => 'status-good',
            'status_message' => 'Data not available',
            'total_locations' => 0,
            'products' => array()
        );
    }
}

$dashboardStats = getDashboardStats($conn);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FarmVille Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    /* Sidebar Styles - Updated to match example */
    .sidebar {
      width: 250px;
      background-color: #b3e5fc;
      padding: 20px 0;
      height: 100vh;
      border-right: 3px solid #1976d2;
      transition: all 0.3s ease;
      position: fixed;
      left: 0;
      top: 0;
      z-index: 100;
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

    /* Main Content Styles */
    .main-content {
      flex: 1;
      margin-left: 250px;
      padding: 2rem;
      overflow-y: auto;
    }

    /* Top Navigation */
    .top-nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #b3e5fc;
    }

    .dashboard-header {
      display: flex;
      align-items: center;
    }

    .dashboard-header h1 {
      font-size: 1.8rem;
      color: #0d47a1;
      margin-right: 20px;
      display: flex;
      align-items: center;
    }

    .dashboard-header h1 i {
      margin-right: 10px;
      color: #0288d1;
    }

    .date-filter {
      display: flex;
      align-items: center;
    }

    .date-filter span {
      margin-right: 10px;
      color: #666;
    }

    .date-filter select {
      padding: 8px 12px;
      border-radius: 6px;
      border: 1px solid #b3e5fc;
      background-color: white;
      transition: border 0.3s ease;
    }

    .date-filter select:focus {
      outline: none;
      border-color: #0288d1;
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

    /* Card Styles - Updated to match example */
    .card {
      background: #e1f5fe;
      border-radius: 20px;
      padding: 1.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 8px 16px rgba(3, 169, 244, 0.1);
      border: 1px solid #81d4fa;
    }

    .card h2 {
      color: #0d47a1;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid #b3e5fc;
    }

    .card h2 i {
      margin-right: 10px;
    }

    /* Mini Cards - Updated to match example */
    .mini-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .mini-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      border: 1px solid #b3e5fc;
      text-align: center;
      transition: transform 0.3s ease;
    }

    .mini-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(25, 118, 210, 0.1);
    }

    .mini-card-value {
      font-size: 1.8rem;
      font-weight: bold;
      color: #0d47a1;
      margin: 10px 0;
    }

    .mini-card-label {
      font-size: 0.9rem;
      color: #666;
    }

    /* Capacity Meter - Updated to match example */
    .capacity-meter {
      width: 100%;
      height: 20px;
      background-color: #e1f5fe;
      border-radius: 10px;
      overflow: hidden;
      margin: 15px 0;
    }

    .capacity-fill {
      height: 100%;
      background: linear-gradient(90deg, #0288d1, #4fc3f7);
      width: var(--utilization, 0%);
      transition: width 0.5s ease;
      border-radius: 10px;
    }

    .capacity-labels {
      display: flex;
      justify-content: space-between;
      margin-top: 5px;
      font-size: 0.8rem;
      color: #666;
    }

    /* Status Colors - Updated to match example */
    .status-good {
      color: #4CAF50;
    }

    .status-warning {
      color: #FFC107;
    }

    .status-critical {
      color: #F44336;
    }

    /* Health Cards - Updated to match example */
    .health-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .health-card {
      background: linear-gradient(135deg, #e1f5fe 0%, #f5fbff 100%);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 4px 12px rgba(3, 169, 244, 0.15);
      border: 1px solid #b3e5fc;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .health-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(25, 118, 210, 0.2);
    }

    .health-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
      background: linear-gradient(to bottom, #0288d1, #4fc3f7);
    }

    .health-card h3 {
      color: #0d47a1;
      margin-bottom: 1rem;
      font-size: 1.2rem;
    }

    .health-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 15px;
      font-size: 1.5rem;
    }

    .health-good {
      background-color: rgba(76, 175, 80, 0.1);
      color: #4CAF50;
    }

    .health-warning {
      background-color: rgba(255, 193, 7, 0.1);
      color: #FFC107;
    }

    .health-critical {
      background-color: rgba(244, 67, 54, 0.1);
      color: #F44336;
    }

    /* Product Distribution - Updated to match example */
    .product-item {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
      padding: 0.75rem;
      border-radius: 8px;
      background-color: rgba(255, 255, 255, 0.7);
      transition: all 0.2s ease;
    }

    .product-item:hover {
      background-color: white;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .product-name {
      width: 150px;
      font-weight: 500;
      color: #333;
    }

    .product-bar {
      flex-grow: 1;
      height: 10px;
      background: #e1f5fe;
      border-radius: 5px;
      margin: 0 10px;
      overflow: hidden;
    }

    .product-fill {
      height: 100%;
      background: linear-gradient(90deg, #0d47a1, #0288d1);
      border-radius: 5px;
    }

    .product-value {
      font-weight: bold;
      color: #0d47a1;
    }

    /* Charts Container */
    .charts-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
      gap: 1.5rem;
      margin-top: 2rem;
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
      .health-cards {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 80px;
      }
      
      .logo span,
      .nav-links li a span {
        display: none;
      }
      
      .logo {
        justify-content: center;
        padding: 20px 0;
      }
      
      .nav-links li a {
        justify-content: center;
        padding: 15px 0;
      }
      
      .nav-links li a i {
        margin-right: 0;
        font-size: 20px;
      }
      
      .main-content {
        margin-left: 80px;
      }
      
      .health-cards,
      .mini-cards,
      .charts-container {
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
      <li><a href="storage.php" class="active"><i class="fas fa-home"></i> <span>Home</span></a></li>
      <li><a href="storage_units.php"><i class="fas fa-warehouse"></i> <span>Storage Units</span></a></li>
      <li><a href="add_stock.php"><i class="fas fa-plus-circle"></i> <span>Add Stock</span></a></li>
      <li><a href="product-movement.php"><i class="fas fa-exchange-alt"></i> <span>Product Movement</span></a></li>
      <li><a href="manage-storage.php"><i class="fas fa-tools"></i> <span>Manage Storage</span></a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Top Navigation -->
    <div class="top-nav">
      <div class="dashboard-header">
        <h1><i class="fas fa-chart-line"></i> Storage Dashboard</h1>
        <div class="date-filter">
          <span>View:</span>
          <select id="timePeriod">
            <option value="7">Last 7 Days</option>
            <option value="30" selected>Last 30 Days</option>
            <option value="90">Last Quarter</option>
          </select>
        </div>
      </div>
      <div class="user-info">
        <span class="username">Admin</span>
        <div class="user-icon"><i class="fas fa-user-cog"></i></div>
      </div>
    </div>

    

    <!-- Storage Health Cards -->
    <div class="card">
      <h2><i class="fas fa-heartbeat"></i> Storage Health Overview</h2>
      <div class="health-cards">
        <div class="health-card">
          <div class="health-icon <?php echo htmlspecialchars($dashboardStats['status_class']); ?>">
            <i class="fas fa-percentage"></i>
          </div>
          <h3>Capacity Utilization</h3>
          <p style="font-size: 1.5rem; font-weight: bold;" class="<?php echo htmlspecialchars($dashboardStats['status_class']); ?>">
            <?php echo htmlspecialchars($dashboardStats['utilization_rate']); ?>%
          </p>
          <p style="font-size: 0.9rem; color: #666;">
            <?php echo htmlspecialchars($dashboardStats['status_message']); ?>
          </p>
        </div>
        <div class="health-card">
        <div class="health-icon health-good">
          <i class="fas fa-map-marker-alt"></i>
        </div>
        <h3>Storage Locations</h3>
        <p style="font-size: 1.5rem; font-weight: bold; color: #0d47a1;">
          <?php echo $dashboardStats['total_locations']; ?>
        </p>
        </div>
        
        <div class="health-card">
          <div class="health-icon health-good">
            <i class="fas fa-box-open"></i>
          </div>
          <h3>Available Space</h3>
          <p style="font-size: 1.5rem; font-weight: bold; color: #0d47a1;">
            <?php echo number_format($dashboardStats['available_capacity'], 1); ?> tons
          </p>
          <p style="font-size: 0.9rem; color: #666;">
            <?php echo ($dashboardStats['total_capacity'] > 0) ? round(($dashboardStats['available_capacity'] / $dashboardStats['total_capacity']) * 100) : 0; ?>% of total
          </p>
        </div>
      </div>
    </div>

    <!-- Product Distribution -->
    <div class="card">
      <h2><i class="fas fa-boxes"></i> Top Products in Storage</h2>
      <div style="margin-top: 20px;">
        <?php if (!empty($dashboardStats['products'])): ?>
          <?php foreach ($dashboardStats['products'] as $product): 
            $percentage = ($dashboardStats['used_capacity'] > 0) ? round(($product['total'] / $dashboardStats['used_capacity'] / 1000) * 100) : 0;
          ?>
            <div class="product-item">
              <span class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></span>
              <div class="product-bar">
                <div class="product-fill" style="width: <?php echo $percentage; ?>%"></div>
              </div>
              <span class="product-value"><?php echo number_format($product['total'] / 1000, 1); ?>t</span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No products currently in storage</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-container">
      <div class="card">
        <h2><i class="fas fa-chart-pie"></i> Capacity Distribution</h2>
        <div style="height: 300px;">
          <canvas id="capacityChart"></canvas>
        </div>
      </div>
      <div class="card">
        <h2><i class="fas fa-chart-bar"></i> Product Volume</h2>
        <div style="height: 300px;">
          <canvas id="productChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Capacity Pie Chart
      const capacityCtx = document.getElementById('capacityChart').getContext('2d');
      new Chart(capacityCtx, {
        type: 'doughnut',
        data: {
          labels: ['Used Capacity', 'Available Capacity'],
          datasets: [{
            data: [
              <?php echo $dashboardStats['used_capacity']; ?>, 
              <?php echo max(0, $dashboardStats['available_capacity']); ?>
            ],
            backgroundColor: [
              '#0288d1',
              '#b3e5fc'
            ],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '70%',
          plugins: {
            legend: {
              position: 'bottom'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const total = <?php echo $dashboardStats['total_capacity']; ?>;
                  const value = context.raw;
                  const percentage = Math.round((value / total) * 100);
                  return `${context.label}: ${value.toFixed(1)} tons (${percentage}%)`;
                }
              }
            }
          }
        }
      });

      // Product Bar Chart
      const productCtx = document.getElementById('productChart').getContext('2d');
      new Chart(productCtx, {
        type: 'bar',
        data: {
          labels: [<?php 
            if (!empty($dashboardStats['products'])) {
              echo "'" . implode("','", array_map(function($p) { 
                return addslashes($p['product_name']); 
              }, $dashboardStats['products'])) . "'";
            }
          ?>],
          datasets: [{
            label: 'Quantity (tons)',
            data: [<?php 
              if (!empty($dashboardStats['products'])) {
                echo implode(',', array_map(function($p) { 
                  return $p['total'] / 1000; 
                }, $dashboardStats['products']));
              }
            ?>],
            backgroundColor: [
              '#0288d1',
              '#4fc3f7',
              '#81d4fa',
              '#b3e5fc',
              '#e1f5fe'
            ],
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Tons'
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    });
  </script>
</body>
</html>