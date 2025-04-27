<?php
include 'db.php';

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Initialize variables
$product = [];
$message = "";

// Fetch product details if editing
if (isset($_GET['product_id'])) {
    $productID = intval($_GET['product_id']);
    $query = "SELECT * FROM Product_T WHERE ProductID = $productID";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        $message = "<p class='text-danger'>Product not found!</p>";
    }
}

// Update product details if form is submitted
if (isset($_POST['save'])) {
    $productID = intval($_POST['product_id']);
    $productName = $conn->real_escape_string($_POST['product_name']);
    $yield = floatval($_POST['yield']);
    $acreage = floatval($_POST['acreage']);
    $cost = floatval($_POST['cost']);
    
    $updateSQL = "UPDATE Product_T
                    SET ProductName='$productName', Yield=$yield, Acreage=$acreage, Cost=$cost
                    WHERE ProductID=$productID";
    
    if ($conn->query($updateSQL) === TRUE) {
        $message = "<p class='text-success'>Record updated successfully!</p>";
        // Refresh product details
        $query = "SELECT * FROM Product_T WHERE ProductID = $productID";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $product = $result->fetch_assoc();
        }
    } else {
        $message = "<p class='text-danger'>Error updating record: " . $conn->error . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: rgb(108, 189, 246); color: white; padding: 20px; }
        .sidebar a { 
            color: white; 
            display: block; 
            margin: 15px 0; 
            padding: 10px; 
            border-radius: 8px; 
            text-decoration: none; 
            transition: background 0.3s; 
        }
        .sidebar a:hover { background: rgb(50, 121, 169); }
        .sidebar a.active { background: rgb(63, 162, 233); font-weight: bold; }
        .content { flex: 1; padding: 20px; }
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
        <li><a href="login.php" class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">⏻ Sign Out</a></li>
    </ul>
</div>

<div class="content">
    <div class="container">
        <h2>Edit Product</h2>
        <?php if (!empty($message)) echo $message; ?>

        <?php if (!empty($product)) { ?>
            <form method="post">
                <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                <div class="mb-3">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="product_name" class="form-control" value="<?php echo htmlspecialchars($product['ProductName']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Yield</label>
                    <input type="number" step="0.01" name="yield" class="form-control" value="<?php echo htmlspecialchars($product['Yield']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Acreage</label>
                    <input type="number" step="0.01" name="acreage" class="form-control" value="<?php echo htmlspecialchars($product['Acreage']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cost</label>
                    <input type="number" step="0.01" name="cost" class="form-control" value="<?php echo htmlspecialchars($product['Cost']); ?>" required>
                </div>
                <button type="submit" name="save" class="btn btn-primary">Save Changes</button>
                <a href="HistoricalData.php" class="btn btn-secondary ms-2">Back to Directory</a>
            </form>
        <?php } ?>
    </div>
</div>

</body>
</html>
