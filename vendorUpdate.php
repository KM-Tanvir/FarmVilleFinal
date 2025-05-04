<?php
include 'db.php';

$product = null;
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['product_name'])) {
    $productName = urldecode($_GET['product_name']);
    $stmt = $conn->prepare("SELECT * FROM Product_T WHERE ProductName = ?");
    $stmt->bind_param("s", $productName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        $errorMsg = "Product not found.";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $originalName = $_POST['original_name'];
    $newName = $_POST['ProductName'];
    $type = $_POST['ProductType'];
    $variety = $_POST['Variety'];
    $seasonality = $_POST['Seasonality'];
    $price = $_POST['CurrentPrice'];

    $stmt = $conn->prepare("UPDATE Product_T SET ProductName=?, ProductType=?, ProductVariety=?, ProductSeasonality=?, ProductPrice=? WHERE ProductName=?");
    $stmt->bind_param("ssssds", $newName, $type, $variety, $seasonality, $price, $originalName);

    if ($stmt->execute()) {
        $successMsg = "Product updated successfully!";
        header("Location: vendorProduct.php?update=success"); // Optional redirect
        exit;
    } else {
        $errorMsg = "Update failed: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; min-height: 100vh; font-family: Arial, sans-serif; }
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
        .form-container { max-width: 600px; margin: 0 auto; }
        .alert { margin-top: 20px; }
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
    <h2>Update Product Info</h2>

    <?php if ($successMsg): ?>
        <div class="alert alert-success"><?php echo $successMsg; ?></div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
    <?php endif; ?>

    <?php if ($product): ?>
        <div class="form-container">
            <form method="POST" action="">
                <input type="hidden" name="original_name" value="<?php echo htmlspecialchars($product['ProductName']); ?>">

                <div class="mb-3">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="ProductName" value="<?php echo htmlspecialchars($product['ProductName']); ?>" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Product Type</label>
                    <input type="text" name="ProductType" value="<?php echo htmlspecialchars($product['ProductType']); ?>" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Product Variety</label>
                    <input type="text" name="Variety" value="<?php echo htmlspecialchars($product['ProductVariety']); ?>" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Seasonality</label>
                    <input type="text" name="Seasonality" value="<?php echo htmlspecialchars($product['ProductSeasonality']); ?>" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Product Price (per kg)</label>
                    <input type="number" step="0.01" name="CurrentPrice" value="<?php echo htmlspecialchars($product['ProductPrice']); ?>" class="form-control">
                </div>

                <button type="submit" name="update" class="btn btn-success">Update</button>
                <a href="vendorProduct.php" class="btn btn-secondary ms-2">Back to Directory</a>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
