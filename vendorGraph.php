<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Graphs</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #28a745; color: white; padding: 20px; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; }
        .sidebar a:hover { text-decoration: underline; }
        .content { flex: 1; padding: 20px; }
    </style>
</head>
<body>
<div class="sidebar">
    <h2>Graph according to Price and Seasonality</h2>
    <a href="vendorHome.php">Home 🏠</a>
    <a href="vendorProduct.php">Product Info 📦</a>
    <a href="vendorGraph.php">Graph/Chart 📊</a>
    <a href="buyerAndSellerDirectory.php">Buyer & Seller Directory 📖</a>
    <a href="vendorProfile.php">Profile 👤</a>
    <a href="login.php">Sign Out 🚪</a>
</div>

<div class="content">
    <h2>Product Data Charts</h2>

    <canvas id="priceChart" width="1000" height="500"></canvas>
    <canvas id="seasonChart" width="1000" height="500" class="mt-5"></canvas>

    <?php
    // Get price data
    $priceQuery = "SELECT ProductName, CurrentPrice FROM Product_T";
    $priceResult = $conn->query($priceQuery);
    $productNames = [];
    $productPrices = [];

    while ($row = $priceResult->fetch_assoc()) {
        $productNames[] = $row['ProductName'];
        $productPrices[] = $row['CurrentPrice'];
    }

    // Get seasonality data (count of products per season)
    $seasonQuery = "SELECT Seasonality, COUNT(*) as count FROM Product_T GROUP BY Seasonality";
    $seasonResult = $conn->query($seasonQuery);
    $seasonLabels = [];
    $seasonCounts = [];

    while ($row = $seasonResult->fetch_assoc()) {
        $seasonLabels[] = $row['Seasonality'];
        $seasonCounts[] = $row['count'];
    }
    ?>
</div>

<script>
    // Price Bar Chart
    const ctx1 = document.getElementById('priceChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($productNames); ?>,
            datasets: [{
                label: 'Price (Tk per kg)',
                data: <?php echo json_encode($productPrices); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.6)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: false, // disables auto-resizing
            plugins: {
                title: { display: true, text: 'Product Price Comparison' }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Season Pie Chart
    const ctx2 = document.getElementById('seasonChart').getContext('2d');
    new Chart(ctx2, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($seasonLabels); ?>,
            datasets: [{
                label: 'Seasonality Distribution',
                data: <?php echo json_encode($seasonCounts); ?>,
                backgroundColor: [
                    '#ff6384', '#36a2eb', '#ffcd56', '#4bc0c0', '#9966ff'
                ]
            }]
        },
        options: {
            responsive: false, // disables auto-resizing
            plugins: {
                title: { display: true, text: 'Product Seasonality' }
            }
        }
    });
</script>
</body>
</html>
