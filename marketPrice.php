<?php
include 'db.php';
$query = "SELECT Price, Date FROM ProductPriceHistory_T WHERE ProductID = ? ORDER BY Date ASC";

// Fetch all product names for dropdown
$product_sql = "SELECT ProductID, ProductName FROM Product_T";
$product_result = $conn->query($product_sql);

// Initialize empty data
$labels = $prices = [];
$selected_id = "";

// Check if 'product' is selected
if (isset($_GET['product']) && is_numeric($_GET['product'])) {
    $selected_id = $_GET['product'];

    // Fetch historical prices for the selected product
    $query = "SELECT Price, Date FROM ProductPriceHistory_T WHERE ProductID = ? ORDER BY Date ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $selected_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch price history
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['Date'];
        $prices[] = $row['Price'];
    }

    // If no data is found
    if (empty($labels)) {
        $error_message = "No historical data found for this product.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Price History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #6cbee7; color: white; padding: 20px; height: 100%; position: fixed; top: 0; left: 0; }
        ul { list-style-type: none; padding-left: 0; }
        .sidebar a { color: white; display: block; margin: 15px 0; padding: 10px; border-radius: 8px; text-decoration: none; transition: background 0.3s; }
        .sidebar a:hover { background: #3279a9; }
        .sidebar a.active { background: #3fa2e9; font-weight: bold; }
        .content { margin-left: 270px; padding: 20px; flex: 1; background-color: #f7f7f7; min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; }
        .toggle-btn { background: none; border: none; color: white; font-size: 24px; cursor: pointer; margin-bottom: 20px; }
        .input-group { max-width: 400px; margin-bottom: 20px; }
        .alert { margin-top: 20px; }
        canvas { max-width: 100%; height: 400px; }
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
        <li><a href="HistoricalData.php" class="<?php echo ($current_page =='HistoricalData.php') ? 'active' : ''; ?>">Historical Production Data</a></li>
        <li><a href="DemandData.php" class="<?php echo ($current_page =='DemandData.php') ? 'active' : ''; ?>">Demand Data</a></li>
        <li><a href="SupplyLevel.php" class="<?php echo ($current_page =='SupplyLevel.php') ? 'active' : ''; ?>">Supply Level</a></li>
        <li><a href="marketPrice.php" class="<?php echo ($current_page =='marketPrice.php') ? 'active' : ''; ?>">Historical and Current prices</a></li>
        <li><a href="vendorGraph.php" class="<?php echo ($current_page == 'vendorGraph.php') ? 'active' : ''; ?>">Graph/Chart</a></li>
        <li><a href="Recommendations.php" class="<?php echo ($current_page == 'Recommendations.php') ? 'active' : ''; ?>">Recommendations</a></li>
        <li><a href="buyerAndSellerDirectory.php" class="<?php echo ($current_page == 'buyerAndSellerDirectory.php') ? 'active' : ''; ?>">Buyer/Seller Directory</a></li>
        <li><a href="vendorProfile.php" class="<?php echo ($current_page == 'vendorProfile.php') ? 'active' : ''; ?>">Profile 👤</a></li>
        <li><a href="login.php" class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>"><span class="icon">⏻</span> Sign Out</a></li>
    </ul>
</div>

<div class="content">
    <div class="container">
        <h2 class="mb-4">Product Price History Graph</h2>

        <form method="get" class="mb-4">
            <div class="input-group">
                <select name="product" class="form-select" required>
                    <option value="">Select a product</option>
                    <?php while ($row = $product_result->fetch_assoc()): ?>
                        <option value="<?= $row['ProductID'] ?>" <?= ($row['ProductID'] == $selected_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['ProductName']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-primary">Show Graph</button>
            </div>
        </form>

        <?php if (!empty($labels)): ?>
            <canvas id="priceChart"></canvas>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                const ctx = document.getElementById('priceChart').getContext('2d');
                const priceChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($labels) ?>,
                        datasets: [{
                            label: 'Price (Tk.)',
                            data: <?= json_encode($prices) ?>,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Price Over Time',
                                font: { size: 18 }
                            }
                        },
                        scales: {
                            x: { title: { display: true, text: 'Date' } },
                            y: { title: { display: true, text: 'Price (Tk.)' } }
                        }
                    }
                });
            </script>
        <?php elseif (isset($error_message)): ?>
            <p class="alert alert-warning"><?= $error_message ?></p>
        <?php endif; ?>

        <?php
if (!empty($_GET['product']) && is_numeric($_GET['product'])) {
    $product_id = $_GET['product'];

    // Correct query for fetching price summary
    $stmt = $conn->prepare("SELECT Product_T.ProductID, Product_T.ProductName,
        MIN(ProductPriceHistory_T.Price) AS LowestPrice,
        MAX(ProductPriceHistory_T.Price) AS MaximumPrice,
        (SELECT Price FROM ProductPriceHistory_T WHERE ProductID = ? ORDER BY Date DESC LIMIT 1) AS CurrentPrice
        FROM Product_T
        LEFT JOIN ProductPriceHistory_T ON Product_T.ProductID = ProductPriceHistory_T.ProductID
        WHERE Product_T.ProductID = ?
        GROUP BY Product_T.ProductID");

    $stmt->bind_param("ii", $product_id, $product_id); // Bind ProductID parameter
    $stmt->execute();
    $result = $stmt->get_result();
    $price_info = $result->fetch_assoc();
    $stmt->close();

    if ($price_info): ?>
        <h4 class="mt-5">Product Price Summary</h4>
        <table class="table table-bordered w-500">
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Lowest Price (Tk.)</th>
                    <th>Highest Price (Tk.)</th>
                    <th>Current Price (Tk.)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= $price_info['ProductID'] ?></td>
                    <td><?= htmlspecialchars($price_info['ProductName']) ?></td>
                    <td><?= number_format($price_info['LowestPrice'], 2) ?></td>
                    <td><?= number_format($price_info['MaximumPrice'], 2) ?></td>
                    <td><?= number_format($price_info['CurrentPrice'], 2) ?></td>
                </tr>
            </tbody>
        </table>
    <?php endif;
}
?>
    </div>
</div>
</body>
</html>
