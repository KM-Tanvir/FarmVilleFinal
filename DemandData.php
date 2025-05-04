<?php
include 'db.php';

// Get product demand data (total quantity per product)
$sql = "SELECT P.ProductName, SUM(O.Quantity) AS TotalDemand
        FROM Order_T O
        JOIN Product_T P ON O.ProductID = P.ProductID
        GROUP BY O.ProductID";
$result = $conn->query($sql);

// Prepare data arrays
$labels = [];
$data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['ProductName'];
        $data[] = $row['TotalDemand'];
    }
} else {
    $labels[] = "No data";
    $data[] = 0;
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Demand Data Graph</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { display: flex; min-height: 100vh; margin: 0; }
        .sidebar {
            width: 250px;
            background: rgb(108, 189, 246);
            color: white;
            padding: 20px;
        }
        .sidebar a {
            color: white;
            display: block;
            margin: 15px 0;
            padding: 10px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .sidebar a:hover {
            background: rgb(50, 121, 169);
        }
        .sidebar a.active {
            background: rgb(63, 162, 233);
            font-weight: bold;
        }
        .content {
            flex: 1;
            padding: 20px;
        }
        .toggle-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        ul { list-style: none; padding: 0; }
        ul li { margin: 10px 0; }
        ul li a { display: flex; align-items: center; }
        .icon { margin-right: 10px; font-size: 20px; }
    </style>
    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            sidebar.style.display = (sidebar.style.display === 'none') ? 'block' : 'none';
        }
    </script>
</head>
<body>

<div class="sidebar" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">
        <span>☰</span>
    </button>
    <ul>
        <li><a href="vendorHome.php" class="<?php echo ($current_page == 'vendorHome.php') ? 'active' : ''; ?>">Dashboard</a></li>
        <li><a href="vendorProduct.php" class="<?php echo ($current_page == 'vendorProduct.php') ? 'active' : ''; ?>">Product Info</a></li>
        <li><a href="HistoricalData.php" class="<?php echo ($current_page == 'HistoricalData.php') ? 'active' : ''; ?>">Historical Production Data</a></li>
        <li><a href="DemandData.php" class="<?php echo ($current_page == 'DemandData.php') ? 'active' : ''; ?>">Demand Data</a></li>
        <li><a href="SupplyLevel.php" class="<?php echo ($current_page == 'SupplyLevel.php') ? 'active' : ''; ?>">Supply Level</a></li>
        <li><a href="marketPrice.php" class="<?php echo ($current_page == 'marketPrice.php') ? 'active' : ''; ?>">Historical and Current Prices</a></li>
        <li><a href="vendorGraph.php" class="<?php echo ($current_page == 'vendorGraph.php') ? 'active' : ''; ?>">Graph/Chart</a></li>
        <li><a href="Recommendations.php" class="<?php echo ($current_page == 'Recommendations.php') ? 'active' : ''; ?>">Recommendations</a></li>
        <li><a href="buyerAndSellerDirectory.php" class="<?php echo ($current_page == 'buyerAndSellerDirectory.php') ? 'active' : ''; ?>">Buyer/Seller Directory</a></li>
        <li><a href="vendorProfile.php" class="<?php echo ($current_page == 'vendorProfile.php') ? 'active' : ''; ?>">Profile 👤</a></li>
        <li><a href="login.php" class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>"><span class="icon">⏻</span> Sign Out</a></li>
    </ul>
</div> <!-- End of Sidebar -->

<div class="content">
    <div class="container mt-5">
        <h2 class="mb-4">Product Demand Chart</h2>
        <canvas id="demandChart" width="600" height="400"></canvas>
    </div>
</div> <!-- End of Content -->

<script>
    const ctx = document.getElementById('demandChart').getContext('2d');
    const demandChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Total Demand (Quantity)',
                data: <?php echo json_encode($data); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true },
                title: { display: true, text: 'Demand by Product' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Quantity Ordered' }
                },
                x: {
                    title: { display: true, text: 'Product Name' }
                }
            }
        }
    });
</script>

</body>
</html>
