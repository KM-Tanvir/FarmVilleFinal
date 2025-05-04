<?php
include 'db.php';

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product</title>
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
        .sidebar a:hover { 
            background: rgb(50, 121, 169); 
        }
        .sidebar a.active { 
            background: rgb(63, 162, 233);
            font-weight: bold; 
        }
        .content { flex: 1; padding: 20px; }
        .toggle-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            margin-bottom: 20px;
        }
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
        <li><a href="marketPrice.php" class="<?php echo ($current_page == 'marketPrice.php') ? 'active' : ''; ?>">Historical and Current prices</a></li>
        <li><a href="vendorGraph.php" class="<?php echo ($current_page == 'vendorGraph.php') ? 'active' : ''; ?>">Graph/Chart</a></li>
        <li><a href="Recommendations.php" class="<?php echo ($current_page == 'Recommendations.php') ? 'active' : ''; ?>">Recommendations</a></li>
        <li><a href="buyerAndSellerDirectory.php" class="<?php echo ($current_page == 'buyerAndSellerDirectory.php') ? 'active' : ''; ?>">Buyer/Seller Directory</a></li>
        <li><a href="vendorProfile.php" class="<?php echo ($current_page == 'vendorProfile.php') ? 'active' : ''; ?>">Profile 👤</a></li>
        <li><a href="login.php" class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">Sign Out</a></li>
    </ul>
</div>

<div class="content">
    <h2>Add New Product</h2>

    <?php
    if (isset($_POST['add'])) {
        $productName = $_POST['ProductName'];
        $productPrice = $_POST['ProductPrice'];
        $productType = $_POST['ProductType'];
        $productVariety = $_POST['ProductVariety'];
        $productSeasonality = $_POST['ProductSeasonality'];
        $productYield = $_POST['ProductYield'];
        $productAcreage = $_POST['ProductAcreage'];
        $cost = $_POST['Cost'];

        // Validate inputs
        if (empty($productName) || empty($productPrice) || empty($productType) || empty($productVariety) || empty($productSeasonality) || empty($productYield) || empty($productAcreage) || empty($cost)) {
            echo "<p class='text-danger'>All fields are required!</p>";
        } else {
            // Prepare the SQL query for inserting data
            $insertSQL = "INSERT INTO Product_T (ProductName, ProductPrice, ProductType, ProductVariety, ProductSeasonality, ProductYield, ProductAcreage, Cost) 
                          VALUES ('$productName', '$productPrice', '$productType', '$productVariety', '$productSeasonality', '$productYield', '$productAcreage', '$cost')";

            // Execute the query
            if ($conn->query($insertSQL) === TRUE) {
                echo "<p class='text-success mt-3'>Product Added Successfully!</p>";
            } else {
                echo "<p class='text-danger mt-3'>Error: " . $conn->error . "</p>";
            }
        }
    }
    ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label>Product Name</label>
            <input type="text" name="ProductName" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Product Price</label>
            <input type="text" name="ProductPrice" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Product Type</label>
            <input type="text" name="ProductType" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Product Variety</label>
            <input type="text" name="ProductVariety" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Product Seasonality</label>
            <input type="text" name="ProductSeasonality" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Product Yield</label>
            <input type="number" step="0.01" name="ProductYield" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Product Acreage</label>
            <input type="number" step="0.01" name="ProductAcreage" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Cost</label>
            <input type="number" step="0.01" name="Cost" class="form-control" required>
        </div>
        <button type="submit" name="add" class="btn btn-success">Add Product</button>
        <a href="vendorProduct.php" class="btn btn-secondary ms-2">Back</a>
    </form>
</div>

</body>
</html>
