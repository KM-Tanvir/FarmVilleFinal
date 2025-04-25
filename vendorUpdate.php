<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Product Info</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
        }
        .sidebar {
            background: #28a745;
            color: white;
            padding: 20px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
        }
        .sidebar h2 {
            font-size: 24px;
            margin-bottom: 30px;
        }
        .sidebar a {
            color: white;
            display: block;
            margin: 12px 0;
            text-decoration: none;
            font-size: 16px;
        }
        .sidebar a:hover {
            text-decoration: underline;
        }
        .main-content {
            margin-left: 250px;
            padding: 40px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Product Information</h2>
    <a href="vendorHome.php">Home 🏠</a>
    <a href="vendorProduct.php">Product Info 📦</a>
    <a href="vendorGraph.php">Graph/Chart 📊</a>
    <a href="buyerAndSellerDirectory.php">Buyer & Seller Directory 📖</a>
    <a href="vendorProfile.php">Profile 👤</a>
    <a href="login.php">Sign Out 🚪</a>
</div>

<div class="main-content">
    <h2>Update Product Info</h2>

    <?php
    if (isset($_GET['product_name'])) {
        $productName = urldecode($_GET['product_name']);
        $sql = "SELECT * FROM Product_T WHERE ProductName = '$productName'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $product = $result->fetch_assoc();
        } else {
            echo "<p class='text-danger'>Product not found.</p>";
            exit;
        }
    } else {
        echo "<p class='text-danger'>No product selected.</p>";
        exit;
    }
    ?>

    <form method="POST" action="">
        <input type="hidden" name="original_name" value="<?php echo htmlspecialchars($product['ProductName']); ?>">

        <div class="mb-3">
            <label class="form-label">Product Name</label>
            <input type="text" name="ProductName" value="<?php echo htmlspecialchars($product['ProductName']); ?>" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Type</label>
            <input type="text" name="ProductType" value="<?php echo htmlspecialchars($product['ProductType']); ?>" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Variety</label>
            <input type="text" name="Variety" value="<?php echo htmlspecialchars($product['Variety']); ?>" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Seasonality</label>
            <input type="text" name="Seasonality" value="<?php echo htmlspecialchars($product['Seasonality']); ?>" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Price (per kg)</label>
            <input type="text" name="CurrentPrice" value="<?php echo htmlspecialchars($product['CurrentPrice']); ?>" class="form-control">
        </div>
        <button type="submit" name="update" class="btn btn-success">Update</button>
    </form>

    <?php
    if (isset($_POST['update'])) {
        $originalName = $_POST['original_name'];
        $newName = $_POST['ProductName'];
        $type = $_POST['ProductType'];
        $variety = $_POST['Variety'];
        $seasonality = $_POST['Seasonality'];
        $price = $_POST['CurrentPrice'];

        $updateSQL = "UPDATE Product_T 
                        SET ProductName='$newName', ProductType='$type', Variety='$variety', Seasonality='$seasonality', CurrentPrice='$price' 
                        WHERE ProductName='$originalName'";

        if ($conn->query($updateSQL) === TRUE) {
            echo "<p class='text-success mt-3'>Product Updated Successfully!</p>";
        } else {
            echo "<p class='text-danger mt-3'>Error: " . $conn->error . "</p>";
        }
    }
    ?>
</div>
</body>
</html>
