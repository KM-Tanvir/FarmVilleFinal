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

// Function to get product movement data
function getProductMovement($conn) {
    $movementData = array();
    
    // Query to get all records ordered by date
    $sql = "SELECT *, 
            CASE 
                WHEN quantity > 0 THEN 'IN' 
                ELSE 'OUT' 
            END as movement_type,
            ABS(quantity) as movement_qty
            FROM storage_unit 
            ORDER BY store_date DESC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $movementData[] = $row;
        }
    }
    
    return $movementData;
}

// Function to get daily in/out chart data
function getDailyChartData($conn) {
    $chartData = array(
        'labels' => array(),
        'inData' => array(),
        'outData' => array(),
        'balanceData' => array()
    );
    
    // Get daily aggregated data
    $sql = "SELECT 
                DATE(store_date) as day,
                SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as in_qty,
                SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END) as out_qty
            FROM storage_unit 
            GROUP BY DATE(store_date)
            ORDER BY DATE(store_date)";
    
    $result = $conn->query($sql);
    
    $runningBalance = 0;
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $chartData['labels'][] = date('M j', strtotime($row['day']));
            $chartData['inData'][] = $row['in_qty'];
            $chartData['outData'][] = $row['out_qty'];
            
            $runningBalance += ($row['in_qty'] - $row['out_qty']);
            $chartData['balanceData'][] = $runningBalance;
        }
    }
    
    return $chartData;
}

// Function to get current stock levels
function getCurrentStock($conn) {
    $stockData = array();
    
    $sql = "SELECT 
                product_id,
                product_name,
                SUM(quantity) as current_stock
            FROM storage_unit
            GROUP BY product_id, product_name
            HAVING current_stock > 0
            ORDER BY product_name";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $stockData[] = $row;
        }
    }
    
    return $stockData;
}

// Get data from database
$movementData = getProductMovement($conn);
$dailyChartData = json_encode(getDailyChartData($conn));
$currentStock = getCurrentStock($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FarmVille - Product Movement</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* ================ BASE STYLES ================ */
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

/* ================ SIDEBAR ================ */
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

/* ================ MAIN CONTENT ================ */
.main-content {
  flex: 1;
  margin-left: 250px;
  padding: 2rem;
  overflow-y: auto;
}

/* ================ TOP NAVIGATION ================ */
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

/* ================ PAGE HEADER ================ */
.page-header {
  margin-bottom: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-header h1 {
  font-size: 1.8rem;
  color: #0d47a1;
  display: flex;
  align-items: center;
}

.page-header h1 i {
  margin-right: 10px;
  color: #0288d1;
}

/* ================ SEARCH AND FILTER ================ */
.search-filter {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.search-box {
  flex: 1;
  min-width: 250px;
  position: relative;
}

.search-box input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 40px;
  border-radius: 8px;
  border: 1px solid #b3e5fc;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.search-box input:focus {
  outline: none;
  border-color: #0288d1;
}

.search-box i {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #0288d1;
}

/* ================ STOCK SUMMARY CARDS ================ */
.stock-summary {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stock-card {
  background: white;
  border-radius: 10px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
  border-top: 4px solid #0288d1;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stock-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}

.stock-card h3 {
  color: #0d47a1;
  margin-bottom: 0.5rem;
  font-size: 1rem;
}

.stock-card .stock-value {
  font-size: 1.8rem;
  font-weight: bold;
  color: #333;
}

.stock-card .stock-label {
  font-size: 0.9rem;
  color: #666;
}

/* ================ MOVEMENT TABLE ================ */
.movement-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 2rem;
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.movement-table th,
.movement-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #b3e5fc;
}

.movement-table th {
  background-color: #e1f5fe;
  color: #0d47a1;
  font-weight: 600;
  position: sticky;
  top: 0;
}

.movement-table tr:hover {
  background-color: #f5fbff;
}

.movement-type {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 12px;
  font-weight: 500;
  font-size: 0.8rem;
}

.type-in {
  background-color: #c8e6c9;
  color: #2e7d32;
}

.type-out {
  background-color: #ffcdd2;
  color: #c62828;
}

/* ================ CHART CONTAINER ================ */
.chart-container {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
  border: 1px solid #e1f5fe;
  position: relative;
  height: 400px;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.chart-header h2 {
  color: #0d47a1;
  display: flex;
  align-items: center;
}

.chart-header h2 i {
  margin-right: 10px;
}

.chart-controls {
  display: flex;
  gap: 1rem;
}

.chart-controls button {
  background: #0288d1;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: background 0.3s ease;
}

.chart-controls button:hover {
  background: #0277bd;
}

.chart-controls button.active {
  background: #01579b;
}

/* ================ LOADING SPINNER ================ */
.loading-spinner {
  display: none;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 10;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #0288d1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* ================ RESPONSIVE STYLES ================ */
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

  .main-content {
    margin-left: 80px;
  }

  .movement-table {
    display: block;
    overflow-x: auto;
  }
  
  .stock-summary {
    grid-template-columns: 1fr;
  }
}

/* ================ UTILITY CLASSES ================ */
.text-primary {
  color: #0d47a1;
}

.text-secondary {
  color: #0288d1;
}

.bg-primary {
  background-color: #e1f5fe;
}

.bg-secondary {
  background-color: #b3e5fc;
}

.shadow-sm {
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.shadow-md {
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.rounded {
  border-radius: 8px;
}

.rounded-lg {
  border-radius: 12px;
}

.transition {
  transition: all 0.3s ease;
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
      <li><a href="add_stock.php"><i class="fas fa-boxes"></i> <span>Add Stock</span></a></li>
      <li><a href="product-movement.php" class="active"><i class="fas fa-exchange-alt"></i> <span>Product Movement</span></a></li>
      <li><a href="manage-storage.php"><i class="fas fa-tools"></i> <span>Manage Storage</span></a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Top Navigation -->
    <div class="top-nav">
      <div class="user-info">
        <span class="username">Admin</span>
        <div class="user-icon"><i class="fas fa-user-tie"></i></div>
      </div>
    </div>

    <!-- Page Header -->
    <div class="page-header">
      <h1><i class="fas fa-exchange-alt"></i> Product Movement</h1>
      <div class="search-filter">
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input type="text" placeholder="Search products..." id="productSearch">
        </div>
      </div>
    </div>

    <!-- Stock Summary Cards -->
    <div class="stock-summary">
      <div class="stock-card">
        <h3>Total Products in Stock</h3>
        <div class="stock-value"><?php echo count($currentStock); ?></div>
        <div class="stock-label">Active products</div>
      </div>
      <div class="stock-card">
        <h3>Today's Incoming (Harvest)</h3>
        <div class="stock-value">
          <?php 
          $today = date('Y-m-d');
          // Query for incoming products where store_date is today (harvest)
          $sql = "SELECT SUM(quantity) as total 
                  FROM storage_unit 
                  WHERE quantity > 0 
                  AND DATE(store_date) = '$today'";
          $result = $conn->query($sql);
          $row = $result->fetch_assoc();
          echo $row['total'] ? number_format($row['total']) : '0';
          ?> kg
        </div>
        <div class="stock-label">Harvested today</div>
      </div>
      <div class="stock-card">
        <h3>Today's Outgoing</h3>
        <div class="stock-value">
          <?php 
            $sql = "SELECT SUM(ABS(quantity)) as total FROM storage_unit WHERE quantity < 0 AND DATE(store_date) = '$today'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            echo $row['total'] ? number_format($row['total']) : '0';
          ?> kg
        </div>
        <div class="stock-label">Shipped today</div>
      </div>
      <div class="stock-card">
        <h3>Total Stock Quantity</h3>
        <div class="stock-value">
          <?php 
            $sql = "SELECT SUM(quantity) as total FROM storage_unit";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            echo $row['total'] ? number_format($row['total']) : '0';
          ?> kg
        </div>
        <div class="stock-label">Current inventory</div>
      </div>
    </div>

    <!-- Daily Movement Chart -->
    <div class="chart-container">
      <div class="loading-spinner" id="chartLoading">
        <div class="spinner"></div>
      </div>
      <div class="chart-header">
        <h2><i class="fas fa-chart-line"></i> Daily Stock Movement</h2>
      </div>
      <canvas id="dailyStockChart"></canvas>
    </div>

    <!-- Movement History Table -->
    <h2 style="color: #0d47a1; margin-bottom: 1rem; display: flex; align-items: center;">
      <i class="fas fa-history"></i> Movement History
    </h2>
    <table class="movement-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Product</th>
          <th>Type</th>
          <th>Quantity</th>
          <th>Location</th>
          <th>Notes</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($movementData as $record): ?>
          <tr>
            <td><?php echo date('d M Y H:i', strtotime($record['store_date'])); ?></td>
            <td><?php echo htmlspecialchars($record['product_name']); ?></td>
            <td>
              <?php if ($record['movement_type'] == 'IN'): ?>
                <span class="movement-type type-in">IN</span>
              <?php else: ?>
                <span class="movement-type type-out">OUT</span>
              <?php endif; ?>
            </td>
            <td><?php echo number_format($record['movement_qty'], 2); ?> kg</td>
            <td><?php echo htmlspecialchars($record['location']); ?></td>
            <td><?php echo htmlspecialchars($record['notes']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <script>
    // Initialize daily chart with PHP data
    const dailyChartData = <?php echo $dailyChartData; ?>;
    const dailyCtx = document.getElementById('dailyStockChart').getContext('2d');
    const dailyStockChart = new Chart(dailyCtx, {
      type: 'bar',
      data: {
        labels: dailyChartData.labels,
        datasets: [
          {
            label: 'Incoming',
            data: dailyChartData.inData,
            backgroundColor: 'rgba(75, 192, 192, 0.7)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
          },
          {
            label: 'Outgoing',
            data: dailyChartData.outData,
            backgroundColor: 'rgba(255, 99, 132, 0.7)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
          },
          {
            label: 'Stock Balance',
            data: dailyChartData.balanceData,
            type: 'line',
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            borderWidth: 2,
            pointRadius: 3,
            fill: true,
            yAxisID: 'y1'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: {
              font: {
                size: 14
              }
            }
          },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
              label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                  label += ': ';
                }
                label += context.parsed.y.toFixed(2) + ' kg';
                return label;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Daily Movement (kg)'
            },
            ticks: {
              callback: function(value) {
                return value + ' kg';
              },
              font: {
                size: 12
              }
            },
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            }
          },
          y1: {
            position: 'right',
            beginAtZero: true,
            title: {
              display: true,
              text: 'Stock Balance (kg)'
            },
            ticks: {
              callback: function(value) {
                return value + ' kg';
              },
              font: {
                size: 12
              }
            },
            grid: {
              drawOnChartArea: false
            }
          },
          x: {
            grid: {
              display: false
            },
            ticks: {
              font: {
                size: 12
              }
            }
          }
        },
        animation: {
          duration: 1000
        }
      }
    });

    // Simple search functionality
    document.getElementById('productSearch').addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      const rows = document.querySelectorAll('.movement-table tbody tr');
      
      rows.forEach(row => {
        const productName = row.cells[1].textContent.toLowerCase();
        if (productName.includes(searchTerm)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });

    // Auto-refresh data every 30 seconds
    setInterval(() => {
      fetch('get_movement_data.php')
        .then(response => response.json())
        .then(data => {
          // Update the table with new data
          const tbody = document.querySelector('.movement-table tbody');
          tbody.innerHTML = '';
          
          data.movementData.forEach(record => {
            const row = document.createElement('tr');
            
            const typeClass = record.movement_type === 'IN' ? 'type-in' : 'type-out';
            const typeText = record.movement_type === 'IN' ? 'IN' : 'OUT';
            
            row.innerHTML = `
              <td>${new Date(record.store_date).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
              <td>${escapeHtml(record.product_name)}</td>
              <td><span class="movement-type ${typeClass}">${typeText}</span></td>
              <td>${parseFloat(record.movement_qty).toFixed(2)} kg</td>
              <td>${escapeHtml(record.location)}</td>
              <td>${escapeHtml(record.notes)}</td>
            `;
            
            tbody.appendChild(row);
          });
        })
        .catch(error => {
          console.error('Error refreshing data:', error);
        });
    }, 30000);

    // Helper function to escape HTML
    function escapeHtml(unsafe) {
      return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }
  </script>
</body>
</html>
<?php
$conn->close();
?>